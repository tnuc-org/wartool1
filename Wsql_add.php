<?php
class Wsql_add extends Wsql
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
	
	function get_request_list()
	{
		$ret = array(array(),array());
		$query = "SELECT * FROM `requests` WHERE 1=1 ORDER BY `time`";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			while($line = mysql_fetch_array($result,MYSQL_ASSOC))
			{
				if(!array_key_exists($line['name'],$ret[0])&&!array_key_exists($line['name'],$ret[1]))
				{
					$ret[$line['type']][$line['name']]=array($line['level'],$line['voc'],$this->fix_rguild($line['guild']),array(array($this->fix_rtime($line['time']),$line['user'],$line['comment'])));
				}
				else
				{
					$ret[$line['type']][$line['name']][3][]=array($this->fix_rtime($line['time']),$line['user'],$line['comment']);
				}
			}
			return ($ret);
		}
		else return(false);
	}
}
?>