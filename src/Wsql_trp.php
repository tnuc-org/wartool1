<?php
class Wsql_trp extends Wsql
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
	
	function get_traps()
	{
		$ret = array();
		$query = "SELECT * FROM `traps` WHERE 1=1";
		$result = mysql_query($query);
		if(mysql_error()) return false;
		else
		{
			while($line = mysql_fetch_array($result,MYSQL_ASSOC)) $ret[]=$line;
			return $ret;
		}
	}
}
?>