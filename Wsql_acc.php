<?php
class Wsql_acc extends Wsql
{
	function __construct()
	{
		parent::__construct();
	}
	
	function __destruct()
	{
		parent::__destruct();
	}
	
	function is_connected()
	{
		return(parent::is_connected());
	}
	
	function token_auth($pc_name,$pc_hash)
	{
		return(parent::token_auth($pc_name,$pc_hash));
	}
	
	function mesAtoI($mes)
	{
		switch($mes)
		{
		case 'Jan': return '01';
					break;
		case 'Feb': return '02';
					break;
		case 'Mar': return '03';
					break;
		case 'Apr': return '04';
					break;
		case 'May': return '05';
					break;
		case 'Jun': return '06';
					break;
		case 'Jul': return '07';
					break;
		case 'Aug': return '08';
					break;
		case 'Sep': return '09';
					break; 
		case 'Oct': return '10';
					break;
		case 'Nov': return '11';
					break;
		case 'Dec': return '12';
					break;  
		}
	}
	
	function formateaFecha($cadena)
	{
		$mes=substr($cadena,0,3);
		$dia=substr($cadena,4,2);
		$ano=substr($cadena,7,4);
		$hora=substr($cadena,13,8);
		$mes=$this->mesAtoI($mes);
		$fecha="$ano-$mes-$dia $hora";  
		return $fecha;
	}
	
	function fix_vocations($voc)
	{
		if($voc===null)
		return(null);
		else {
			switch($voc)
			{
				case 'Druid': $voc="D"; break;
				case 'Elder Druid': $voc="ED"; break;
				case 'Sorcerer': $voc="S"; break;
				case 'Master Sorcerer': $voc="MS"; break;
				case 'Knight': $voc="K"; break;
				case 'Elite Knight': $voc="EK"; break;
				case 'Paladin': $voc="P"; break;
				case 'Royal Paladin': $voc="RP"; break;
				default: $voc="NO"; break;
			}
			return($voc);
		}
	}
	
	function leeDatosChar($pname)
	{
		$ret=array(false,array(),array());
		$direcc="http://www.tibia.com/community/?subtopic=character&name=". urlencode($pname);
		
		$ch = curl_init(); 
		curl_setopt( $ch , CURLOPT_URL , $direcc );
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);  
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$html = curl_exec($ch); 
		curl_close($ch); 
		
		//$html = file_get_contents($direcc);
		$html = str_replace('&#160;',' ',$html);
		$html = str_replace('&nbsp;',' ',$html);   //for offline version only?
		$html = str_replace('&#39;',"'",$html);    //for offline version only?
		$html = str_replace('&percnt;',"%",$html); //for offline version only?
		$pos1 = strpos($html, 'Profession:');
		if($pos1 !== false)
		{
			//Matching Profile Data (name, level, voc, guild, residence, last login)
			preg_match_all('~<td[^>]+?>Name:</td><td>(.+)</td>~iU',$html,$lista);
			$ret[1][0]=$lista[1][0];
			preg_match_all('~<td>Level:</td><td>(.+)</td>~iU',$html,$lista);
			$ret[1][1]=$lista[1][0];
			preg_match_all('~<td>Profession:</td><td>(.+)</td>~iU',$html,$lista);
			$ret[1][2]=$this->fix_vocations($lista[1][0]);
			preg_match_all('~<td>Guild membership:</td><td>[^<]*(<A.*)</td>~iU',$html,$lista);
			if(isset($lista[1][0])) $ret[1][3]=str_replace('"',"'",$lista[1][0]); else $ret[1][3]='';
			preg_match_all('~<td>Residence:</td><td>(.+)</td>~iU',$html,$lista);
			$ret[1][4]=$lista[1][0];
			preg_match_all('~<td>Last Login:</td><td>(.+)</td>~iU',$html,$lista);
			$ret[1][5]=$this->formateaFecha(str_replace('&#160;',' ',$lista[1][0]));
			
			//Matching Death Record (new storage method with [a]/[z] hyperlink replacement)
			preg_match_all('~<TD WIDTH=25%>(.*)</td></tr>~iU',$html,$lista);
			for($i=0;$i<count($lista[1]);$i++)
			{
				$entry=array();
				$tmpdeath=str_replace(array('"','</td><td>'),array("'",'</TD><TD>'),$lista[1][$i]);
				$tmpdeath=explode('</TD><TD>',$tmpdeath);
				if($tmpdeath[0]!='')
				{
					$fecha=$this->formateaFecha($tmpdeath[0]);
					$entry[]=$fecha;
					$text=$tmpdeath[0];
					preg_match("~^(Killed|Died) at Level [0-9]* by ~",$tmpdeath[1],$x);
					$text.=" ".$x[0];
					$entry[]=$text;
					$tmpdeath[1]=preg_replace("~^(Killed|Died) at Level [0-9]* by ~","",$tmpdeath[1]);
					$entry[]='';
					$entry[]=$tmpdeath[1];
					$ret[2][]=$entry;
				}
				else
				{
					$tmpdeath[1]=preg_replace("~^and by ~","",$tmpdeath[1]);
					$ret[2][count($ret[2])-1][2]=$ret[2][count($ret[2])-1][3];
					$ret[2][count($ret[2])-1][3]=$tmpdeath[1];
				}
			}
			// Death Record hyperlink replacement
			foreach($ret[2] as $key => $val) {if($val[2]!="''") $ret[2][$key][2]=preg_replace("~<\/?a[^>]*>~","",$val[2]); $ret[2][$key][2]=preg_replace(array("~<a[^>]*>~iU","~<\/a>$~iU"),array("",""),$val[2]); $ret[2][$key][3]=preg_replace(array("~<a[^>]*>~iU","~<\/a>$~iU"),array("[a]","[z]"),$val[3]);}
			
			// Query formatting so deaths can be returned as one full query, or 0 if no deaths
			if(count($ret[2])>1)
			{
				foreach($ret[2] as $key => $val)
				{
					$ret[2][$key][2]=mysql_real_escape_string($ret[2][$key][2]);
					$ret[2][$key][3]=mysql_real_escape_string($ret[2][$key][3]);
					$ret[2][$key]="('".mysql_real_escape_string($ret[1][0])."','".implode("','",$ret[2][$key])."')";
				}
				$ret[2]="INSERT INTO `deaths` (`name`,`time`,`text`,`killer1`,`killer2`) VALUES ".implode(",",$ret[2]);
			}
			elseif(count($ret[2])==1)
			{
					$ret[2][0][2]=mysql_real_escape_string($ret[2][$key][2]);
					$ret[2][0][3]=mysql_real_escape_string($ret[2][$key][3]);
					$ret[2]="INSERT INTO `deaths` (`name`,`time`,`text`,`killer1`,`killer2`) VALUES ('".mysql_real_escape_string($ret[1][0])."','".implode("','",$ret[2][0])."')";
			}
			else $ret[2]=0;
			$ret[0]=true;
		}	
		return($ret);
	}
	
	function get_user_chars($uname)
	{
		$ret = array(array(),array());
		$query = sprintf("SELECT `name`,`level`,`voc`,`guild`,`residence`,`o_is_second` AS `issec`,`o_rdy`,`o_accessq`,`o_hidden` FROM `characters` WHERE `o_is_ours`=1 AND `o_owner`='%s' ORDER BY `level` DESC",
			mysql_real_escape_string($uname));
		$result = mysql_query($query);
		if(mysql_error()) return(false);
		else
		{
			while($line = mysql_fetch_array($result,MYSQL_ASSOC)) $ret[$line['issec']][]=$line;
			return $ret;
		}
	}
	
	function is_in_charlist($name)
	{
		$query = sprintf("SELECT `name` FROM `characters` WHERE `name`='%s' AND `o_is_ours`=1 AND `o_owner` IS NOT NULL LIMIT 1",
			mysql_real_escape_string($name));
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if($line = mysql_fetch_array($result,MYSQL_ASSOC))
				return(true);
			else return(false);
		}
		else return(null);
	}
	
	function add_new_char($cname,$par,$hide,$admname)
	{
		if($this->is_in_charlist($cname)===false)
		{
			$profile = $this->leeDatosChar($cname);
			if($profile[0]===true)
			{
				$query = sprintf("INSERT INTO `characters` (`name`,`level`,`voc`,`guild`,`residence`,`init_level`,`init_date`,`last_updated`,`last_login`,`o_is_ours`,`o_is_second`,`o_owner`,`o_hidden`) VALUES ('%s',%s,'%s','%s','%s',%s,'%f','%f','%s',1,$par,'%s',$hide) %s%s",
					mysql_real_escape_string($profile[1][0]),
					mysql_real_escape_string($profile[1][1]),
					mysql_real_escape_string($profile[1][2]),
					mysql_real_escape_string($profile[1][3]),
					mysql_real_escape_string($profile[1][4]),
					mysql_real_escape_string($profile[1][1]),
					mysql_real_escape_string(date("YmdHis")),
					mysql_real_escape_string(date("YmdHis")),
					mysql_real_escape_string($profile[1][5]),
					mysql_real_escape_string($admname),
					"ON DUPLICATE KEY UPDATE `level`=VALUES(`level`),`voc`=VALUES(`voc`),`guild`=VALUES(`guild`),`residence`=VALUES(`residence`),`o_owner`=VALUES(`o_owner`),",
					"`o_is_second`=VALUES(`o_is_second`),`o_is_ours`=VALUES(`o_is_ours`),`last_updated`=VALUES(`last_updated`),`last_login`=VALUES(`last_login`),`o_hidden`=VALUES(`o_hidden`)");
				mysql_query($query);
				if(!mysql_error())
				{
					$change=mysql_affected_rows();
					if($change==1)
					{
						$query3 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`,`comment`) VALUES (16,0,'%s','%s','%f','%s')",
							mysql_real_escape_string($admname),
							(($hide==0)?mysql_real_escape_string($cname):"#name hidden#"),
							mysql_real_escape_string(date("YmdHis")),
							mysql_real_escape_string($par));
						mysql_query($query3);
					}
					elseif($change==2)
					{
						$query3 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`,`comment`) VALUES (16,1,'%s','%s','%f','%s')",
							mysql_real_escape_string($admname),
							(($hide==0)?mysql_real_escape_string($cname):"#name hidden#"),
							mysql_real_escape_string(date("YmdHis")),
							mysql_real_escape_string($par));
						mysql_query($query3);
					}
					if($profile[2]!==0) mysql_query($profile[2]);
					return true;
				}
				else return 2;
			}else return 1;
		}else return 0;
	}
	
	function acq($par)
	{
		$ret=(isset($_POST[$par]))?$_POST[$par]:0;
		return $ret;
	}
	
	function encode_accessq()
	{
		$values='';
		$values.=$this->acq('iceisle');		//0
		$values.=$this->acq('seaserp');
		$values.=$this->acq('pegleg');
		$values.=$this->acq('goroma');
		$values.=$this->acq('goromabb');
		$values.=$this->acq('goromahy');	//5
		$values.=$this->acq('goromags');
		$values.=$this->acq('torts');
		$values.=$this->acq('pirates');
		$values.=$this->acq('expljoin');
		$values.=$this->acq('explcala');	//10
		$values.=$this->acq('explfroz');
		$values.=$this->acq('expltp1');
		$values.=$this->acq('expltp2');
		$values.=$this->acq('banuforb');
		$values.=$this->acq('banudeep');	//15
		$values.=$this->acq('yalahar');
		$values.=$this->acq('yalaboat');
		$values.=$this->acq('berecart');
		$values.=$this->acq('bloodbro');
		$values.=$this->acq('anni');		//20
		$values.=$this->acq('lbcults');
		$values.=$this->acq('djinn');
		$values.=$this->acq('inq');			//23
		return $values;
	}
	
	function format_accessq($code)
	{
		$a = str_split($code);
		if($a[0]==6) $r="<span class='evtgood'>ONTCME</span>";
		elseif($a[0]==5) $r="<span class='evtgood'>ONTCM</span><span class='evtbad'>E</span>";
		elseif($a[0]==4) $r="<span class='evtgood'>ONTC</span><span class='evtbad'>ME</span>";
		elseif($a[0]==3) $r="<span class='evtgood'>ONT</span><span class='evtbad'>CME</span>";
		elseif($a[0]==2) $r="<span class='evtgood'>ON</span><span class='evtbad'>TCME</span>";
		elseif($a[0]==1) $r="<span class='evtgood'>O</span><span class='evtbad'>NTCME</span>";
		else $r="<span class='evtbad'>ONTCME</span>";
		$r .= $a[1]==1 ? "<span class='evtgood'>S</span>-" : "<span class='evtbad'>S</span>-";
		$r .= $a[2]==1 ? "<span class='evtgood'>M</span>" : "<span class='evtbad'>M</span>";
		$r .= $a[3]==1 ? "<span class='evtgood'>G</span>" : "<span class='evtbad'>G</span>";
		$r .= $a[4]==1 ? "<span class='evtgood'>B</span>" : "<span class='evtbad'>B</span>";
		$r .= $a[5]==1 ? "<span class='evtgood'>H</span>" : "<span class='evtbad'>H</span>";
		$r .= $a[6]==1 ? "<span class='evtgood'>G</span>" : "<span class='evtbad'>G</span>";
		$r .= $a[7]==1 ? "<span class='evtgood'>T</span>" : "<span class='evtbad'>T</span>";
		$r .= $a[8]==1 ? "<span class='evtgood'>P</span>-" : "<span class='evtbad'>P</span>-";
		$r .= $a[9]==1 ? "<span class='evtgood'>X</span>" : "<span class='evtbad'>X</span>";
		$r .= $a[10]==1 ? "<span class='evtgood'>C</span>" : "<span class='evtbad'>C</span>";
		$r .= $a[11]==1 ? "<span class='evtgood'>F</span>" : "<span class='evtbad'>F</span>";
		$r .= $a[12]==1 ? "<span class='evtgood'>1</span>" : "<span class='evtbad'>1</span>";
		$r .= $a[13]==1 ? "<span class='evtgood'>2</span>-" : "<span class='evtbad'>2</span>-";
		$r .= $a[14]==1 ? "<span class='evtgood'>F</span>" : "<span class='evtbad'>F</span>";
		$r .= $a[15]==1 ? "<span class='evtgood'>B</span>-" : "<span class='evtbad'>B</span>-";
		if($a[16]==8) $r.="<span class='evtgood'>YATACSFL</span>-";
		elseif($a[16]==7) $r.="<span class='evtgood'>YATACSF</span><span class='evtbad'>L</span>-";
		elseif($a[16]==6) $r.="<span class='evtgood'>YATACS</span><span class='evtbad'>FL</span>-";
		elseif($a[16]==5) $r.="<span class='evtgood'>YATAC</span><span class='evtbad'>SFL</span>-";
		elseif($a[16]==4) $r.="<span class='evtgood'>YATA</span><span class='evtbad'>CSFL</span>-";
		elseif($a[16]==3) $r.="<span class='evtgood'>YAT</span><span class='evtbad'>ACSFL</span>-";
		elseif($a[16]==2) $r.="<span class='evtgood'>YA</span><span class='evtbad'>TACSFL</span>-";
		elseif($a[16]==1) $r.="<span class='evtgood'>Y</span><span class='evtbad'>ATACSFL</span>-";
		else $r.="<span class='evtbad'>YATACSFL</span>-";
		$r .= $a[17]==1 ? "<span class='evtgood'>B</span>" : "<span class='evtbad'>B</span>";
		$r .= $a[18]==1 ? "<span class='evtgood'>B</span>-" : "<span class='evtbad'>B</span>-";
		if($a[19]==3) $r.="<span class='evtgood'>VCA</span>-";
		elseif($a[19]==2) $r.="<span class='evtgood'>VC</span><span class='evtbad'>A</span>-";
		elseif($a[19]==1) $r.="<span class='evtgood'>V</span><span class='evtbad'>CA</span>-";
		else $r.="<span class='evtbad'>VCA</span>-";
		$r .= $a[20]==1 ? "<span class='evtgood'>A</span>" : "<span class='evtbad'>A</span>";
		$r .= $a[21]==1 ? "<span class='evtgood'>L</span>" : "<span class='evtbad'>L</span>";
		if($a[22]==4) $r.="<span class='fklnk'>B</span>-";
		elseif($a[22]==3) $r.="B-";
		elseif($a[22]==2) $r.="<span class='evtgood'>G</span>-";
		elseif($a[22]==1) $r.="G-";
		else $r.="<span class='evtbad'>N</span>-";
		if($a[23]==2) $r.="<span class='evtgood'>UI</span>";
		elseif($a[23]==1) $r.="<span class='evtgood'>U</span><span class='evtbad'>I</span>";
		else $r.="<span class='evtbad'>UI</span>";
		return $r;
	}
		
	function get_accessq($cname,$owner)
	{
		$query = sprintf("SELECT `o_accessq` FROM `characters` WHERE `name`='%s' AND `o_is_ours`=1 AND `o_owner`='%s'",
			mysql_real_escape_string($cname),
			mysql_real_escape_string($owner));
		$result = mysql_query($query);
		if(mysql_error()) return false;
		else
			if($line = mysql_fetch_array($result,MYSQL_ASSOC)) return $line['o_accessq'];
		else return false;
	}
	
	function set_accessq($data,$cname,$owner)
	{
		$query = sprintf("UPDATE `characters` SET `o_accessq`='%s' WHERE `name`='%s' AND `o_owner`='%s'",
			mysql_real_escape_string($data),
			mysql_real_escape_string($cname),
			mysql_real_escape_string($owner));
		mysql_query($query);
		if(mysql_error()) return false;
		if(mysql_affected_rows()==1) return true;
		else return false;
	}
	
	function get_avail_info($pu)
	{
		$query = sprintf("SELECT `msn`,`country_code` AS `cc`,`phone` FROM `users` WHERE `name`='%s' LIMIT 1",
			mysql_real_escape_string($pu));
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if($line = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				return $line;
			}
			else return false;
		}
		else return false;
	}
	
	function set_avail_info($cc,$ph,$ms,$user)
	{
		$query = sprintf("UPDATE `users` SET %s,%s,%s WHERE `name`='%s'",
			(($cc===null)?"`country_code`=NULL":"`country_code`='$cc'"),
			(($ph===null)?"`phone`=NULL":"`phone`='$ph'"),
			(($ms===null)?"`msn`=NULL":"`msn`='$ms'"),
			mysql_real_escape_string($user));
		mysql_query($query);
		if(!mysql_error() && mysql_affected_rows()>ß) return true;
		else return false;
	}
}
?>