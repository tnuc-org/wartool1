<?php
class Wsql_ajx extends Wsql
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
	
	
	/*****************************************************************************
	**                            GENERIC FUNCTIONS                             **
	*****************************************************************************/
	
	function fix_rtime($time)
	{
		$m = substr($time,5,2);
		
		if($m=='01') $m="Jan";
		elseif($m=='02') $m="Feb";
		elseif($m=='03') $m="Mar";
		elseif($m=='04') $m="Apr";
		elseif($m=='05') $m="May";
		elseif($m=='06') $m="Jun";
		elseif($m=='07') $m="Jul";
		elseif($m=='08') $m="Aug";
		elseif($m=='09') $m="Sep";
		elseif($m=='10') $m="Oct";
		elseif($m=='11') $m="Nov";
		elseif($m=='12') $m="Dec";
		
		$d = substr($time,8,2);
		return("$m $d");
	}
	
	function fix_rguild($guild)
	{
		if($guild===null||$guild==''||$guild==' ')
			return(" guildless");
		else return(" from ".preg_replace("~\'>~","' target='_blank'>",$guild));
	}
	
	function find_ol_db_matches($ar)
	{
		$ret=array();
		$implosion = "(".implode(",",$ar).")";
		$query = "SELECT `name` FROM `characters` WHERE `name` IN ".$implosion;
		$result = mysql_query($query);
		if(!mysql_error())
		{
			while($line = mysql_fetch_array($result,MYSQL_ASSOC)) $ret[]="'".mysql_real_escape_string($line['name'])."'";
			return($ret);
		}
		else return($ret);
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
	
	function is_in_charlist($name)
	{
		$query = sprintf("SELECT `name` FROM `characters` WHERE `name`='%s' AND `e_is_enemy`=1 LIMIT 1",
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
	
	function is_in_requests($name)
	{
		$query = sprintf("SELECT `name` FROM `requests` WHERE `name`='%s' LIMIT 1",
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
	
	function get_last_comment($name,$user)
	{
		$query = sprintf("SELECT `comment` FROM `requests` WHERE `name`='%s' AND `user`='%s' ORDER BY `time` DESC LIMIT 1",
			mysql_real_escape_string($name),
			mysql_real_escape_string($user));
		$result = mysql_query($query);
		if(mysql_error()) return(null);
		else
		{
			if ($line = mysql_fetch_array($result,MYSQL_ASSOC)) $com = $line['comment'];
			else $com = '';
			return($com);
		}
	}
	
	/*****************************************************************************
	**                              AJAX FUNCTIONS                              **
	*****************************************************************************/
	
	function ajax_mantchar2_insert($pname,$pcomment,$psec,$pnok,$pkhp,$pexp,$pbls,$admname)
	{
		$profile = $this->leeDatosChar($pname);
		if($profile[0]===true) 
		$query = sprintf("INSERT INTO `characters` (`name`,`level`,`voc`,`guild`,`residence`,`init_level`,`init_date`,`last_updated`,`last_login`,`e_comment`,`e_is_second`,`e_nokill`,`e_killhp`,`e_explimit`,`e_blacklist`,`e_is_enemy`) VALUES ('%s',%s,'%s','%s','%s',%s,'%f','%f','%s','%s',$psec,$pnok,$pkhp,$pexp,$pbls,1) %s%s%s",
			mysql_real_escape_string($profile[1][0]),
			mysql_real_escape_string($profile[1][1]),
			mysql_real_escape_string($profile[1][2]),
			mysql_real_escape_string($profile[1][3]),
			mysql_real_escape_string($profile[1][4]),
			mysql_real_escape_string($profile[1][1]),
			mysql_real_escape_string(date("YmdHis")),
			mysql_real_escape_string(date("YmdHis")),
			mysql_real_escape_string($profile[1][5]),
			mysql_real_escape_string($pcomment),
			"ON DUPLICATE KEY UPDATE `e_comment`=VALUES(`e_comment`),`e_is_second`=VALUES(`e_is_second`),`e_nokill`=VALUES(`e_nokill`),`e_killhp`=VALUES(`e_killhp`),",
			"`e_explimit`=VALUES(`e_explimit`),`e_blacklist`=VALUES(`e_blacklist`),`last_updated`=VALUES(`last_updated`),`level`=VALUES(`level`),`voc`=VALUES(`voc`),",
			"`guild`=VALUES(`guild`),`residence`=VALUES(`residence`),`last_login`=VALUES(`last_login`),`e_is_enemy`=1");
		else
		$query = sprintf("INSERT INTO `characters` (`name`,`e_comment`,`init_level`,`init_date`,`e_is_second`,`e_nokill`,`e_killhp`,`e_explimit`,`e_blacklist`,`e_is_enemy`) VALUES ('%s','%s',0,'%f',$psec,$pnok,$pkhp,$pexp,$pbls,1) %s%s",
			mysql_real_escape_string($pname),
			mysql_real_escape_string($pcomment),
			mysql_real_escape_string(date("YmdHis")),
			"ON DUPLICATE KEY UPDATE `e_comment`=VALUES(`e_comment`),`e_is_second`=VALUES(`e_is_second`),`e_nokill`=VALUES(`e_nokill`),`e_killhp`=VALUES(`e_killhp`),",
			"`e_explimit`=VALUES(`e_explimit`),`e_blacklist`=VALUES(`e_blacklist`),`e_is_enemy`=1");
		mysql_query($query);
		if(!mysql_error())
		{
			$change=mysql_affected_rows();
			if($change==1)
			{
				$query3 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`time`,`comment`) VALUES (15,0,'%s','%f','%s')",
					mysql_real_escape_string($admname),
					mysql_real_escape_string(date("YmdHis")),
					mysql_real_escape_string($pname));
				mysql_query($query3);
			}
			if($profile[2]!==0) mysql_query($profile[2]);
			return($change);
		}
		else return('sqlerr');
	}
	
	function ajax_mantchar2_update($pname,$fieldname,$value,$admname)
	{
		$pname=mysql_real_escape_string($pname);
		$admname=mysql_real_escape_string($admname);
		if($fieldname=='e_comment')
			$value="'".mysql_real_escape_string($value)."'";
		$query = "UPDATE `characters` SET `".$fieldname."`=".$value." WHERE `name`='".$pname."'";
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()>0)
			{
				return("1");
			}
			else return("2");
		}
		else return("0");
	}
	
	function ajax_mantchar2_delete($pname,$admname)
	{
		$query = sprintf("UPDATE `characters` SET `e_is_enemy`=0 WHERE `name`='%s'",
			mysql_real_escape_string($pname));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`time`,`comment`) VALUES (15,1,'%s','%f','%s')",
					mysql_real_escape_string($admname),
					mysql_real_escape_string(date("YmdHis")),
					mysql_real_escape_string($pname));
				mysql_query($query2);
				return('1');
			}
			else return('0');
		}
		else return('0');
	}
	
	function ajax_magelist_hide($pname,$admname)
	{
		$query = sprintf("UPDATE `magelist` SET `hidden`=1 WHERE `name`='%s'",
			mysql_real_escape_string($pname));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`) VALUES (13,0,'%s','%s','%f')",
					mysql_real_escape_string($admname),
					mysql_real_escape_string($pname),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
				return("1");
			}
			else return("2");
		}
		else return("0");
	}
	
	function ajax_magelist_unhide($pname,$admname)
	{
		$query = sprintf("UPDATE `magelist` SET `hidden`=0 WHERE `name`='%s'",
			mysql_real_escape_string($pname));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`) VALUES (13,1,'%s','%s','%f')",
					mysql_real_escape_string($admname),
					mysql_real_escape_string($pname),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
				return("1");
			}
			else return("2");
		}
		else return("0");
	}
	
	function ajax_magelist_tag($pname,$ptag,$admname)
	{
		$query = sprintf("UPDATE `magelist` SET `type`=$ptag WHERE `name`='%s'",
			mysql_real_escape_string($pname));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`,`comment`) VALUES (13,2,'%s','%s','%f','$ptag')",
					mysql_real_escape_string($admname),
					mysql_real_escape_string($pname),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
				return("1");
			}
			else return("2");
		}
		else return("0");
	}
	
	function ajax_requests_add($name,$type,$comment,$admname,$userlevel)
	{
		if($type==0)
		{
			if($this->is_in_charlist($name)===false)
			{
				if($this->is_in_requests($name)===false)
				{
					$profile = $this->leeDatosChar($name);
					if($profile[0]===true)
					{
						$query = sprintf("INSERT INTO `requests` (`name`,`level`,`voc`,`guild`,`type`,`time`,`user`,`comment`) VALUES ('%s',%s,'%s','%s',0,'%f','%s','%s')",
							mysql_real_escape_string($profile[1][0]),
							mysql_real_escape_string($profile[1][1]),
							mysql_real_escape_string($profile[1][2]),
							mysql_real_escape_string($profile[1][3]),
							mysql_real_escape_string(date("YmdHis")),
							mysql_real_escape_string($admname),
							mysql_real_escape_string($comment));
						mysql_query($query);
						if(!mysql_error())
						{
							if(mysql_affected_rows()==1)
							{
								$enc = urlencode($profile[1][0]);
								$ret='';
								if($userlevel>=3)
								{
									$ret.="<div onclick='showPanel(this);'>[<span style='color:#0000ff;'>menu</span>]</div>";
									$ret.="<div style='display:none;'>";
									$ret.="<img src='./img/b_blue.gif' onClick='processReq(\"".$enc."\",0,0,0,this)'> ";
									$ret.="<img src='./img/b_green.gif' onClick='processReq(\"".$enc."\",0,0,1,this)'> ";
									$ret.="<img src='./img/b_red.gif' onClick='processReq(\"".$enc."\",0,0,2,this)'> ";
									$ret.="<img src='./img/b_black.gif' onClick='processReq(\"".$enc."\",0,0,3,this)'> ";
									$ret.="<img src='./img/b_yellow.gif' onClick='processReq(\"".$enc."\",0,0,4,this)'> add main<br>";
									$ret.="<img src='./img/b_blue.gif' onClick='processReq(\"".$enc."\",0,1,0,this)'> ";
									$ret.="<img src='./img/b_green.gif' onClick='processReq(\"".$enc."\",0,1,1,this)'> ";
									$ret.="<img src='./img/b_red.gif' onClick='processReq(\"".$enc."\",0,1,2,this)'> ";
									$ret.="<img src='./img/b_black.gif' onClick='processReq(\"".$enc."\",0,1,3,this)'> ";
									$ret.="<img src='./img/b_yellow.gif' onClick='processReq(\"".$enc."\",0,1,4,this)'> add second<br>";
									$ret.="<img src='./img/b_cross.gif' onClick='declineReq(\"".$enc."\",this);'> decline request";
									$ret.="</div>";
								}
								else $ret.="<span></span><span></span>";
								$ret.= "<div><u><b>".$profile[1][0]."</b> (".$profile[1][1]." ".$profile[1][2].($this->fix_rguild($profile[1][3])).")</u>";
								$ret.="<br><i>".($this->fix_rtime(date("Y-m-d H:i:s"))).", ".$admname."</i> &#187; ".((substr($comment,0,2)=="a$")?"<span class='admmsg'>".substr(htmlentities($comment),2,strlen($comment)-2)."</span>":htmlentities($comment))."</div>";
								$ret.="<div onclick='showComment(this);'>[<span style='color:#0000ff;'>post comment</span>]</div>";
								$ret.="<div style='display:none'><input type='text' value='' maxlength='40'><input type='button' value='post' onclick='commentReq(\"".$enc."\",0,this);'></div>";
								return($ret);
							}
							else return('6');
						}
						else return('5');
					}
					else return('4');
				}
				else return('3');
			}
			else return('2');
		}
		else
		{
			if($this->is_in_charlist($name)===true)
			{
				if($this->is_in_requests($name)===false)
				{
					$profile = $this->leeDatosChar($name);
					if($profile[0]===true)
					{
						$query = sprintf("INSERT INTO `requests` (`name`,`level`,`voc`,`guild`,`type`,`time`,`user`,`comment`) VALUES ('%s',%s,'%s','%s',1,'%f','%s','%s')",
							mysql_real_escape_string($profile[1][0]),
							mysql_real_escape_string($profile[1][1]),
							mysql_real_escape_string($profile[1][2]),
							mysql_real_escape_string($profile[1][3]),
							mysql_real_escape_string(date("YmdHis")),
							mysql_real_escape_string($admname),
							mysql_real_escape_string($comment));
						mysql_query($query);
						if(!mysql_error())
						{
							if(mysql_affected_rows()==1)
							{
								$enc = urlencode($profile[1][0]);
								$ret='';
								if($userlevel>=3)
								{
									$ret.="<div onclick='showPanel(this);'>[<span style='color:#0000ff;'>menu</span>]</div>";
									$ret.="<div style='display:none;'>";
									$ret.="<img src='./img/b_blue.gif' onClick='processReq(\"".$enc."\",1,0,0,this)'> ";
									$ret.="<img src='./img/b_green.gif' onClick='processReq(\"".$enc."\",1,0,1,this)'> ";
									$ret.="<img src='./img/b_red.gif' onClick='processReq(\"".$enc."\",1,0,2,this)'> ";
									$ret.="<img src='./img/b_black.gif' onClick='processReq(\"".$enc."\",1,0,3,this)'> ";
									$ret.="<img src='./img/b_yellow.gif' onClick='processReq(\"".$enc."\",1,0,4,this)'> add main<br>";
									$ret.="<img src='./img/b_blue.gif' onClick='processReq(\"".$enc."\",1,1,0,this)'> ";
									$ret.="<img src='./img/b_green.gif' onClick='processReq(\"".$enc."\",1,1,1,this)'> ";
									$ret.="<img src='./img/b_red.gif' onClick='processReq(\"".$enc."\",1,1,2,this)'> ";
									$ret.="<img src='./img/b_black.gif' onClick='processReq(\"".$enc."\",1,1,3,this)'> ";
									$ret.="<img src='./img/b_yellow.gif' onClick='processReq(\"".$enc."\",1,1,4,this)'> add second<br>";
									$ret.="<img src='./img/b_cross.gif' onClick='declineReq(\"".$enc."\",this)'> decline request";
									$ret.="</div>";
								}
								else $ret.="<span></span><span></span>";
								$ret.= "<div><u><b>".$profile[1][0]."</b> (".$profile[1][1]." ".$profile[1][2].($this->fix_rguild($profile[1][3])).")</u>";
								$ret.="<br><i>".($this->fix_rtime(date("Y-m-d H:i:s"))).", ".$admname."</i> &#187; ".((substr($comment,0,2)=="a$")?"<span class='admmsg'>".substr(htmlentities($comment),2,strlen($comment)-2)."</span>":htmlentities($comment))."</div>";
								$ret.="<div onclick='showComment(this);'>[<span style='color:#0000ff;'>post comment</span>]</div>";
								$ret.="<div style='display:none'><input type='text' value='' maxlength='40'><input type='button' value='post' onclick='commentReq(\"".$enc."\",1,this);'></div>";
								return($ret);
							}
							else return('6');
						}
						else return('5');
					}
					else return('4');
				}
				else return('3');
			}
			else return('2');
		}
	}
	
	function ajax_requests_process($name,$rtype,$atype,$issec,$admname)
	{
		$nok = ($atype==1)?1:0;
		$khp = ($atype==2)?1:0;
		$blk = ($atype==3)?1:0;
		$exp = ($atype==4)?1:0;
		
		if($this->is_in_requests($name)===true)
		{
			$comment = $this->get_last_comment($name,$admname);
			if($comment!==null)
			{
				if($rtype==0&&$this->is_in_charlist($name)===true) $rtype=1;
				if($rtype==0)
				{
					$profile = $this->leeDatosChar($name);
					if($profile[0]===true)
					{
						$query = sprintf("INSERT INTO `characters` (`name`,`level`,`voc`,`guild`,`residence`,`init_level`,`init_date`,`last_updated`,`last_login`,`e_comment`,`e_is_second`,`e_nokill`,`e_killhp`,`e_explimit`,`e_blacklist`,`e_is_enemy`) VALUES ('%s',%s,'%s','%s','%s',%s,'%f','%f','%s','%s',$issec,$nok,$khp,$exp,$blk,1) %s%s",
							mysql_real_escape_string($profile[1][0]),
							mysql_real_escape_string($profile[1][1]),
							mysql_real_escape_string($profile[1][2]),
							mysql_real_escape_string($profile[1][3]),
							mysql_real_escape_string($profile[1][4]),
							mysql_real_escape_string($profile[1][1]),
							mysql_real_escape_string(date("YmdHis")),
							mysql_real_escape_string(date("YmdHis")),
							mysql_real_escape_string($profile[1][5]),
							mysql_real_escape_string($comment),
							"ON DUPLICATE KEY UPDATE `level`=VALUES(`level`),`voc`=VALUES(`voc`),`guild`=VALUES(`guild`),`last_updated`=VALUES(`last_updated`),`last_login`=VALUES(`last_login`),`e_is_enemy`=VALUES(`e_is_enemy`),",
							"`e_comment`=VALUES(`e_comment`),`e_is_second`=VALUES(`e_is_second`),`e_nokill`=VALUES(`e_nokill`),`e_killhp`=VALUES(`e_killhp`),`e_blacklist`=VALUES(`e_blacklist`),e_explimit=VALUES(`e_explimit`)");
						mysql_query($query);
						if(mysql_error()) return('6');
						else
						{
							if(!in_array(mysql_affected_rows(),array(1,2))) return('7');
							else
							{
								if($profile[2]!='') mysql_query($profile[2]);
								$this->ajax_requests_decline($name);
								return('OK');
							}
						}
					}
					else return('5');
				}
				else
				{
					$query = sprintf("UPDATE `characters` SET %s,%s,%s,%s, %s%s WHERE `name`='%s'",
						"`e_is_second`=$issec",
						"`e_nokill`=$nok",
						"`e_killhp`=$khp",
						"`e_blacklist`=$blk",
						"`e_explimit`=$exp",
						(($comment!='')?",`e_comment`='".mysql_real_escape_string($comment)."'":""),
						mysql_real_escape_string($name));
					mysql_query($query);
					if(mysql_error()) return('3');
					else
					{
						if(mysql_affected_rows()!=1) return('4');
						else
						{
							$this->ajax_requests_decline($name);
							return('OK');
						}
					}
				}
			}
			else return('2');
		}
		else return('1');
	}
	
	function ajax_requests_decline($name)
	{
		$query = sprintf("DELETE FROM `requests` WHERE name='%s'",
			mysql_real_escape_string($name));
		mysql_query($query);
		if(!mysql_error())
		{
			return('2');
		}
		else return('1');
	}
	
	function ajax_requests_comment($name,$type,$comment,$admname,$userlevel)
	{
		if($this->is_in_requests($name))
		{
			if($comment!='')
			{
				$query = sprintf("INSERT INTO `requests` (`name`,`type`,`user`,`time`,`comment`) VALUES ('%s',$type,'%s','%f','%s')",
					mysql_real_escape_string($name),
					mysql_real_escape_string($admname),
					mysql_real_escape_string(date("YmdHis")),
					mysql_real_escape_string($comment));
				mysql_query($query);
				if(!mysql_error())
				{
					if(mysql_affected_rows()==1)
					{
						$ret="<br><i>".($this->fix_rtime(date("Y-m-d H:i:s"))).", ".$admname."</i> &#187; ".((substr($comment,0,2)=="a$")?"<span class='admmsg'>".substr(htmlentities($comment),2,strlen($comment)-2)."</span>":htmlentities($comment));
						return($ret);
					}
					else return('4');
				}
				else return('3');
			}
			else return('2');
		}
		else return('1');
	}
	
	function ajax_myacc_togglerdy($cname,$par,$owner)
	{
		$query = sprintf("UPDATE `characters` SET `o_rdy`=$par WHERE `name`='%s' AND `o_owner`='%s' AND `o_is_ours`=1",
			mysql_real_escape_string($cname),
			mysql_real_escape_string($owner));
		mysql_query($query);
		if(mysql_error()) return '3';
		else
		{
			if(mysql_affected_rows()==1) return 'OK';
			else return '4';
		}
	}
	
	function ajax_myacc_delete($name,$owner)
	{
		$a=0;
		$query1 = sprintf("UPDATE `characters` SET `o_is_ours`=0,`o_owner`=NULL WHERE `name`='%s' AND `o_owner`='%s' AND `o_is_second`=1",
			mysql_real_escape_string($name),
			mysql_real_escape_string($owner));
		$query2 = sprintf("UPDATE `characters` SET `o_owner`=NULL WHERE `name`='%s' AND `o_owner`='%s' AND `o_is_second`=0",
			mysql_real_escape_string($name),
			mysql_real_escape_string($owner));
		mysql_query($query1);
		if(!mysql_error()) $a+=mysql_affected_rows(); else return '1';
		mysql_query($query2);
		if(!mysql_error()) $a+=mysql_affected_rows(); else return '1';
		if($a == 1)
		{
			return 'OK';
		}
		else return '2';
	}
	
	/*****************************************************************************
	**                             CRON FUNCTIONS                               **
	*****************************************************************************/
	
	function cron_availability_process($code,$d,$h)
	{
		$days = explode("-",$code);
		$dat = array();
		for($i=0;$i<7;$i+=1)
		{
			$str = base_convert($days[$i],16,2);
			while(strlen($str)<24) $str = "0" . $str;
			$temp=array();
			for($j=0;$j<24;$j+=1)
			{
				$t = substr($str,$j,1);
				$temp[]=$t;
			}
			$dat[]=$temp;
			unset($temp);
		}
		if($dat[$d-1][$h]==1) return(true); else return(false);
	}
	
	function cron_availability_refresh()
	{
		$offset = $this->starttime;
		$curday = date("N",time()+300);
		$curhour= date("G",time()+300);
		if($curhour-$offset<0) $curday-=1;  if($curday==0) $curday=7;
		$hour = $curhour-$offset; if ($hour<0) $hour+=24;
		
		$query = "SELECT `name`,`availability` AS `av` FROM `users` WHERE 1=1";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			$data = array();
			$av = array();
			$count = mysql_num_rows($result);
			while($line = mysql_fetch_array($result, MYSQL_ASSOC))
				$data[$line['name']]=$line['av'];
			foreach($data as $n => $a)
				if($this->cron_availability_process($a,$curday,$hour)) $av[]="'".mysql_real_escape_string($n)."'";
			$query2 = "UPDATE `users` SET `availability_cur`=0 WHERE 1=1";
			mysql_query($query2);
			if(count($av)>0)
			{
				$query3 = "UPDATE `users` SET `availability_cur`=1 WHERE `name`";
				if(count($av)>1)
				{
					$av=implode(",",$av);
					$query3.=" IN (".$av.")";
				}
				else
				{
					$query3 .= "=".$av[0];
				}
				mysql_query($query3);
			}
			return("OK: AVAILABILITY REFRESHED<br>");
		}
		else return("ERROR: AVAILABILITY REFRESH FAILED<br>");
	}
	
	function cron_cleanup_tokens()
	{
		$query = sprintf("DELETE FROM `tokens` WHERE `expires`<'%s'",
			mysql_real_escape_string(date("YmdHis")));
		mysql_query($query);
		if(!mysql_error())
			return("OK: TOKEN CLEANUP, ".mysql_affected_rows()." TOKENS<br>");
		else return("ERROR: TOKEN CLEANUP FAILED<br>");
	}
	
	function cron_cleanup_chars()
	{
		$query = "SELECT `name` FROM `characters` WHERE `e_is_enemy`=0 AND `o_is_ours`=0";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			$count = mysql_num_rows($result);
			if($count>0)
			{
				if($count>1)
				{
					$ar=array();
					while($line = mysql_fetch_array($result,MYSQL_ASSOC)) $ar[]="'".mysql_real_escape_string($line['name'])."'";
					$suffix = " IN (".implode(",",$ar).")";
				}
				else
				{
					$line = mysql_fetch_array($result,MYSQL_ASSOC);
					$suffix = "='".mysql_real_escape_string($line['name'])."'";
				}
				$query2 = "DELETE FROM `deaths` WHERE `name`".$suffix;
				$query3 = "DELETE FROM `onlinetimes` WHERE `name`".$suffix;
				$query4 = "DELETE FROM `characters` WHERE `name`".$suffix;
				mysql_query($query2);
				mysql_query($query3);
				mysql_query($query4);
			}
			return("OK: CHAR CLEANUP, $count CHARS<br>");
		}
		else return("ERROR: CHAR CLEANUP, SQLERR<br>");
	}
	
	function cron_cleanup_initlv()
	{
		$query = "UPDATE `characters` SET `init_level`=`level` WHERE `init_level`=0";
		if(mysql_error()) return "ERROR: INITLV CLEANUP, SQLERR<br>";
		else {
		$c = mysql_affected_rows();
		return "OK: INITLV CLEANUP, $c rows<br>";
		}
	}
	
	function cron_optimize_tables()
	{
		$success=0;
		$query = "OPTIMIZE TABLE `characters`,`deaths`,`events`,`ips`,`ips_watched`,`magelist`,`onlinelist`,";
		$query.= "`onlinetimes`,`requests`,`tokens`,`users`";
		mysql_query($query);

		if (!mysql_error()) return("OK: ALL TABLES OPTIMIZED<br>");
		else return("ERROR: FAILED TO OPTIMIZE TABLES<br>");
	}
	
	//cron/5min, gets onlinelist, updates onlinetimes, updates magelist
	function cron_get_onlinelist()
	{
		$direcc = "http://www.tibia.com/community/?subtopic=whoisonline&world=".$this->server;
		
		$ch = curl_init(); 
		curl_setopt( $ch , CURLOPT_URL , $direcc );
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);  
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$html = curl_exec($ch); 
		curl_close($ch); 
		if($html===null) $html='';
		
		//$html = file_get_contents($direcc);
		$html = str_replace('&#160;',' ',$html);
		$html = str_replace('&nbsp;',' ',$html);   //for offline version only?
		$html = str_replace('&#39;',"'",$html);    //for offline version only?
		$html = str_replace('&percnt;',"%",$html); //for offline version only?
		preg_match_all('~&name=([A-Za-z\%27\+]*)">([^<]*)<\/A><[^>]*><[^>]*>([^<]*)<[^>]*><[^>]*>([^<]*)<[^>]*><\/TR>~i',$html,$matches);
		unset($matches[0]);
		unset($matches[1]);
		unset($html);
		$mages=array();
		$now="'".mysql_real_escape_string(date("YmdHis"))."'";
		for($i=0;$i<count($matches[2]);$i+=1)
		{
			$matches[2][$i]="'".mysql_real_escape_string($matches[2][$i])."'";
			$matches[4][$i]="'".$this->fix_vocations($matches[4][$i])."'";
			if(in_array($matches[4][$i],array("'D'","'ED'","'S'","'MS'"))&&$matches[3][$i]>$this->magereq)
			{
				$mages[]=array($matches[2][$i],$matches[3][$i],$matches[4][$i],$now);
			}
		}
		unset($matches[3]);
		unset($matches[4]);
		
		$query0 = "TRUNCATE TABLE `onlinelist`";
		mysql_query($query0);
		if(count($matches[2]>0))
		{
			$implosion="(".implode("),(",$matches[2]).")";
			$query1 = "INSERT INTO `onlinelist` (`name`) VALUES ".$implosion;
			mysql_query($query1);
			if(count($mages)>0)
			{
				if(count($mages)>1)
				{
					for($j=0;$j<count($mages);$j+=1) $mages[$j]="(".implode(",",$mages[$j]).")";
					$mages=implode(",",$mages);
				}
				else
				{
					$mages="(".implode(",",$mages[0]).")";
				}
				$query2 = "INSERT INTO `magelist` (`name`,`level`,`voc`,`lastseen`) VALUES ".$mages." ON DUPLICATE KEY UPDATE ";
				$query2.= "`level`=VALUES(`level`),`voc`=VALUES(`voc`),`lastseen`=VALUES(`lastseen`)";
				mysql_query($query2);
			}
			$d=date("N");
			$h=date("H");
			$matches[2]=$this->find_ol_db_matches($matches[2]);
			if(count($matches[2])>0)
			{
				$implosion="(".implode(",$d,1),(",$matches[2]).",$d,1)";
				$query3 = "INSERT INTO `onlinetimes` (`name`,`day`,`h".$h."`) VALUES ".$implosion." ON DUPLICATE KEY UPDATE `h".$h."`=`h".$h."`+1";
				mysql_query($query3);
			}
			return("OK: ONLINELIST PROCESSED<br>");
		}
		else return("ERROR: ONLINELIST EMPTY/INACCESSIBLE<br>");
	}
	
	function cron_refresh_chars($num,$param)
	{
		$ret = '';
		if($param!='') $param=' '.$param;
		$query = "SELECT `name`,`last_updated` FROM `characters`$param ORDER BY `last_updated` ASC,`name` ASC LIMIT $num";
		$result = mysql_query($query);
		if(mysql_error()) return("ERROR: CHAR REFRESH MYSQL ERROR1");
		else
		{
			$names = array();
			$data = array();
			$ret = '';
			while($line = mysql_fetch_array($result,MYSQL_ASSOC)) $names[]=$line['name'];
			set_time_limit(count($names)*10+5);
			for($i=0;$i<count($names);$i+=1)
			{
				$data[$i] = $this->leeDatosChar($names[$i]);
				if($data[$i][0]===true)
				{
					$query = sprintf("UPDATE `characters` SET %s,%s,%s,%s,%s,%s,%s WHERE `name`='%s'",
							"`name`='".mysql_real_escape_string($data[$i][1][0])."'",
							"`level`=".mysql_real_escape_string($data[$i][1][1]),
							"`voc`='".mysql_real_escape_string($data[$i][1][2])."'",
							"`guild`='".mysql_real_escape_string($data[$i][1][3])."'",
							"`residence`='".mysql_real_escape_string($data[$i][1][4])."'",
							"`last_updated`='".mysql_real_escape_string(date("YmdHis"))."'",
							"`last_login`='".mysql_real_escape_string($data[$i][1][5])."'",
							mysql_real_escape_string($names[$i]));
					mysql_query($query);
					if(!mysql_error())
						$ret .= "<span class='evtgood'>Update of <b>".$data[$i][1][0]."</b> successful.</span><br>";
					else $ret .= "<span class='evtbad'>Update of <b>".$data[$i][1][0]."</b> failed.</span><br>";
					if($data[$i][2]!=='') mysql_query($data[$i][2]);
				}
				else $ret .= "<span class='evtbad'>Update of <b>".$names[$i]."</b> failed.</span><br><b>".$names[$i]." does not exist.</b><br>";
			}
		}
		if($ret=='') return("ERROR: Failed to update characters.");
		else return($ret);
	}
	
	function cron_refresh_geodata() //test3.php
	{
		$qpref="INSERT INTO `ips` (`name`,`ip`,`country`) VALUES ";
		$qvals=array();
		$qsuff=" ON DUPLICATE KEY UPDATE `country`=VALUES(`country`)";
		$query1 = "SELECT `name`,`ip` FROM `ips` WHERE `country`='' ORDER BY `lastseen` LIMIT 10";
		$result = mysql_query($query1);
		while($line = mysql_fetch_array($result,MYSQL_ASSOC))
		{
			set_time_limit(15);
			$ch = curl_init(); 
			$direcc="http://www.geody.com/geoip.php?ip=".$line['ip'];
			curl_setopt( $ch , CURLOPT_URL , $direcc );
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);  
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$html = curl_exec($ch); 
			curl_close($ch);
			if($html!==null)
			{
				preg_match_all('~Location: <br>(.*\))~iU',$html,$matches);
				if(isset($matches[1][0]))
				{
					$cdata = strip_tags($matches[1][0],'<img></img>');
					$qvals[]= sprintf("('%s','%s','%s')",
						mysql_real_escape_string($line['name']),
						mysql_real_escape_string($line['ip']),
						mysql_real_escape_string($cdata));
				}
				elseif(strpos($html,"Too many accesses")===false)
				{
					$qvals[]= sprintf("('%s','%s','%s')",
						mysql_real_escape_string($line['name']),
						mysql_real_escape_string($line['ip']),
						mysql_real_escape_string("<b>ERROR</b>"));
				}
			}
		}
		$c=count($qvals);
		if($c>0)
		{
			$qvals = implode(",",$qvals);
			$query2 = "$qpref$qvals$qsuff";
			mysql_query($query2);
			if(mysql_error()) return "ERROR: IP GEODATA REFRESH,SQLERR<br>";
			else return "OK: IP GEODATA REFRESH, ".$c." ".mysql_affected_rows()."<br>";
		}
		else return "ERROR: IP GEODATA REFRESH, RETRIEVED 0<br>";
	}
	
	function cron_get_forum_ips()
	{
		set_time_limit(40);
		$postfields = "act=Login&CODE=01&s=&referer=&CookieDate=1&UserName=".$this->forumacc."&PassWord=".$this->forumpw."&submit=Log%20In"; 
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/cookie"); 
		curl_setopt($ch, CURLOPT_URL, $this->forumurl."index.php"); 
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, "$postfields"); 
		curl_exec($ch);
		$postfields = "act=Online&CODE=listall&sort_key=click"; 
		curl_setopt($ch, CURLOPT_URL, $this->forumurl."index.php?act=Online&CODE=listall&sort_key=click");
		curl_setopt($ch, CURLOPT_POSTFIELDS, "$postfields");
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		$page=curl_exec($ch);
		curl_close($ch);
		if($page!==null)
		{
			preg_match_all('~<span(.*)>(.*)</span></a>(\*)*  \( (.*) \)</td>~iU',$page,$matches);
			if(sizeof($matches[2])>0)
			{
				$ipar = array();
				$qpar = array();
				$wpar = array();
				$now=date("YmdHis");
				$ipsfound = "'".implode("','",$matches[4])."'";
				$query1 = "SELECT DISTINCT(`ip`),`watched` FROM `ips` WHERE `ip` IN (".$ipsfound.") AND `watched`=1";
				$result = mysql_query($query1);
				if(!mysql_error())
				{
					while($line = mysql_fetch_array($result,MYSQL_ASSOC)) $ipar[$line['ip']]=$line['watched'];
					for($i=0;$i<sizeof($matches[2]);$i+=1)
					{
						$qpar[]=sprintf("('%s','%s',1,%s,'$now','$now')",
							mysql_real_escape_string(str_replace("&#39;","'",$matches[2][$i])),
							mysql_real_escape_string($matches[4][$i]),
							isset($ipar[$matches[4][$i]])?($ipar[$matches[4][$i]]):'0');
						if(isset($ipar[$matches[4][$i]]) && $ipar[$matches[4][$i]]==1)
						$wpar[]=sprintf("('%s','%s','$now',1)",
							mysql_real_escape_string(str_replace("&#39;","'",$matches[2][$i])),
							mysql_real_escape_string($matches[4][$i]));
					}
				}
				$qpar = implode(",",$qpar);
				$query2 = "INSERT INTO `ips` (`name`,`ip`,`forum`,`watched`,`firstseen`,`lastseen`) VALUES $qpar ON DUPLICATE KEY UPDATE `lastseen`=VALUES(`lastseen`),`forum`=1";
				if(count($wpar)>0)
				{
					$wpar = implode(",",$wpar);
					$query3 = "INSERT INTO `ips_watched` (`ip`,`acc`,`time`,`forum`) $wpar";
					mysql_query($query3);
				}
				mysql_query($query2);
				if(!mysql_error())
				return "OK: GET FORUM IPS, ".mysql_affected_rows()."<br>";
				else return "ERROR: GET FORUM IPS, MYSQLERR<br>";
			}
			else return "ERROR: GET FORUM IPS, ZERO MATCHES<br>";
		}
		else return "ERROR: GET FORUM IPS, 404<br>";
	}
	
	function testy()
	{echo "yoyoyo";}
}