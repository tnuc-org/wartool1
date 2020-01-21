<?php
class Wsql_ene extends Wsql
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

	function get_full_charlist()
	{
		$ret=array();
		$query = "SELECT `characters`.`name` as `cname`,`characters`.`level`,`characters`.`voc`,`guild`,`e_comment`,`e_is_second`,`e_killhp`,`e_nokill`,`e_blacklist`,`e_explimit`,`onlinelist`.`name` AS `oname` FROM `characters` LEFT OUTER JOIN `onlinelist` ON (`onlinelist`.`name`=`characters`.`name`) WHERE `e_is_enemy`=1 ORDER BY `characters`.`level` DESC";
		$result = mysql_query($query);
		if(!mysql_error())
		{
				while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[]=$line;
				return($ret);
		}
		else return(false);
	}
	
	function get_recent_deathlist()
	{
		$ret=array(array(),array());
		$query = "SELECT `d`.`name` AS `cname`,`d`.`time` AS `time`,`d`.`text` AS `text`,`d`.`killer1` AS `killer1`,`d`.`killer2` AS `killer2`,`c`.`e_is_second` AS `issec`,`c`.`guild` AS `guild` FROM `deaths` AS `d` LEFT OUTER JOIN `characters` AS `c` ON (`c`.`name`=`d`.`name`) WHERE `c`.`e_is_enemy`=1 ORDER BY `time` DESC LIMIT 250";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[$line['issec']][]=$line;
			return($ret);
		}
		else return(false);
	}
	
	function get_levelchanges()
	{
		$ret=array(array(),array());
		$query = "SELECT `name`,`level`,`voc`,`guild`,`e_is_second` AS `issec`,`init_level`,`level`-`init_level` AS `difference`,`e_comment` FROM `characters` WHERE `e_is_enemy`=1 AND ((`level`-`init_level`)<>0) AND `e_nokill`=0 ORDER BY `difference` DESC";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[$line['issec']][]=$line;
			return($ret);
		}
		else return(false);
	}
	
	function get_mod_charlist()
	{
		$ret=array();
		$query = 'SELECT `name`,`level`,`voc`,`guild`,`e_is_second`,`e_nokill`,`e_killhp`,`e_explimit`,`e_comment`,`e_blacklist` FROM `characters` WHERE `e_is_enemy`=1 ORDER BY `level` DESC';
		$result = mysql_query($query);
		if(!mysql_error())
		{
			while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[]=$line;
			return($ret);
		}
		else return(false);
	}
	
	function get_magelist($par)
	{
		$ret=array();
		if($par) $query = "SELECT * FROM `magelist` WHERE `hidden`=1 ORDER BY `level` DESC,`name` ASC";
		else $query = "SELECT * FROM `magelist` WHERE `hidden`=0 ORDER BY `level` DESC,`name` ASC";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[]=$line;
			$ret=$this->last_seen($ret);
			return($ret);
		}
		else return(false);
	}
	
	function last_seen($ar)
	{
		$now = date("Y-m-d H:i:s",time()-280);
		$today = date("Y-m-d") . " 00:00:00";
		$yday = date("Y-m-d",time()-86400) . " 00:00:00";
		$tweek = date("Y-m-d",time()-86400*7) . " 00:00:00";
		$lweek = date("Y-m-d",time()-86400*14) . " 00:00:00";
		$lmonth = date("Y-m-d",time()-86400*30) . " 00:00:00";
		foreach($ar as $key => $val)
		{
			if($val['lastseen']>=$now) $ar[$key]['lastseen']="<td align='center' class='nokill'><b>online</b></td>\n";
			elseif($val['lastseen']>=$today) $ar[$key]['lastseen']="<td align='center' class='nokill'>today</td>\n";
			elseif($val['lastseen']>=$yday) $ar[$key]['lastseen']="<td align='center' class='explimit'>yesterday</td>\n";
			elseif($val['lastseen']>=$tweek) $ar[$key]['lastseen']="<td align='center' class='explimit'>this week</td>\n";
			elseif($val['lastseen']>=$lweek) $ar[$key]['lastseen']="<td align='center' class='killhp'>last week</td>\n";
			elseif($val['lastseen']>=$lmonth) $ar[$key]['lastseen']="<td align='center' class='killhp'>&gt;2 weeks ago</td>\n";
			else $ar[$key]['lastseen']="<td align='center' class='killhp'>&gt;1 month ago</td>\n";
		}
		return($ar);
	}
	
	function force_onlinelist_update()
	{
		$direcc = "http://www.tibia.com/community/?subtopic=whoisonline&world=".$this->server;
		$html = file_get_contents($direcc);
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
			if(in_array($matches[4][$i],array("'D'","'ED'","'S'","'MS'"))&&$matches[3][$i]>65)
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
		}
	}
}
?>