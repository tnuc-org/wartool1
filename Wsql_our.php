<?php
class Wsql_our extends Wsql
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
	
	function get_full_charlist()
	{
		$ret=array();
		//$query = "SELECT `characters`.`name` as `cname`,`characters`.`level`,`characters`.`voc`,`guild`,`o_hidden`,`o_owner`,`o_is_second`,`o_rdy`,`onlinelist`.`name` AS `oname` FROM `characters` LEFT OUTER JOIN `onlinelist` ON (`onlinelist`.`name`=`characters`.`name`) WHERE `o_is_ours`=1 AND `o_verify` IS NULL ORDER BY `o_hidden` ASC,`characters`.`level` DESC";
		$query = "SELECT `characters`.`name` as `cname`,`characters`.`level`,`characters`.`voc`,`guild`,`o_hidden`,`o_owner`,`o_is_second`,`o_rdy`,`onlinelist`.`name` AS `oname` FROM `characters` LEFT OUTER JOIN `onlinelist` ON (`onlinelist`.`name`=`characters`.`name`) WHERE `o_is_ours`=1 AND `o_verify` IS NULL ORDER BY `characters`.`level` DESC";
		$result = mysql_query($query);
		if(!mysql_error())
		{
				while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[]=$line;
				return $ret;
		}
		else return false;
	}
	
	function get_user_chars($username)
	{
		$ret = array();
		$query = sprintf("SELECT `name`,`level`,`voc`,`o_is_second`,`o_rdy`,`o_hidden` FROM `characters` WHERE `o_is_ours`=1 AND `o_owner`='%s'",
			mysql_real_escape_string($username));
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_num_rows($result)>0)
			{
				while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[]=$line;
				return($ret);
			}
			else return(false);
		}
		else return(false);
	}
	
	function verification_code($cname)
	{
		$str = str_split(md5($cname),16);
		return($str[1]);
	}
	
	function add_our_char($oname,$cname,$issecond)
	{
		$issecond = ($issecond!=false&&$issecond!=0)?1:0;
		$query = sprintf("INSERT INTO `characters` (`name`,`o_is_ours`,`o_owner`,`o_is_second`,`o_verify`) VALUES ('%s',1,'%s',$issecond,'%s') ON DUPLICATE KEY UPDATE `o_is_ours`=VALUES(`o_is_ours`),`o_owner`=VALUES(`o_owner`),`o_is_second`=VALUES(`o_is_second`),`o_verify`=VALUES(`o_verify`) WHERE `o_is_ours`=0",
			mysql_real_escape_string($cname),
			mysql_real_escape_string($oname),
			mysql_real_escape_string($this->verification_code($cname)));
		mysql_query($query);
		if(!mysql_error())
			if(mysql_affected_rows()>0)
				return(true);
			else return(false);
		else return(false);
	}
	
	function verify_our_char()
	{
		;
	}
	
	function add_our_char_no_verify($oname,$cname,$issecond)
	{
		$issecond = ($issecond!=false&&$issecond!=0)?1:0;
		$query = sprintf("INSERT INTO `characters` (`name`,`o_is_ours`,`o_owner`,`o_is_second`) VALUES ('%s',1,'%s',$issecond,'%s') ON DUPLICATE KEY UPDATE `o_is_ours`=VALUES(`o_is_ours`),`o_owner`=VALUES(`o_owner`),`o_is_second`=VALUES(`o_is_second`) WHERE `o_is_ours`=0",
			mysql_real_escape_string($cname),
			mysql_real_escape_string($oname));
		mysql_query($query);
		if(!mysql_error())
			if(mysql_affected_rows()>0)
				return(true);
			else return(false);
		else return(false);
	}
	
	
}
?>