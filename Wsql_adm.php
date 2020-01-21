<?php
class Wsql_adm extends Wsql
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
	
	function get_ips_compact()
	{
		$ret = array();
		$query = "SELECT `name`,count(*) AS `times_seen`,MAX(`ip`) as `iphigh`,MIN(`ip`) as `iplow`,DATE_FORMAT(MIN(`firstseen`),'%b %e, %H:%i') AS `firstseen`,`forum`,`wartool`,MAX(`lastseen`) AS `real_lastseen`,DATE_FORMAT(MAX(`lastseen`),'%b %e, %H:%i') AS `lastseen`,`country` FROM `ips` GROUP by `name`,`country` ORDER BY `name` ASC,`real_lastseen` ASC";
		$result = mysql_query($query);
		if(mysql_error()) $ret=false;
		else while($line=mysql_fetch_array($result,MYSQL_ASSOC)) $ret[]=$line;
		return $ret;
	}
	
	function get_unres()
	{
		$query="SELECT count(*) AS `testy` FROM `ips` WHERE `country`='' GROUP BY `country`";
		$res=mysql_query($query);
		if(mysql_error()) return null;
		else if($line=mysql_fetch_array($res,MYSQL_ASSOC)) return $line['testy']; else return 0;
	}
	
	function get_ips_full()
	{
		return false;
	}
}
?>