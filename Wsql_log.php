<?php
class Wsql_log extends Wsql
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
	
	function login_auth($f_name,$f_pass)
	{
		$query = sprintf("SELECT `perm_usermgr`,`perm_ourchars`,`perm_refresh`,`perm_traps`,`perm_vent`,`perm_availability`,`perm_charlist`,`perm_stats`,`isadmin`,`users`.`name`,`forum_alias`,`acctype` FROM `users` WHERE `name`='%s' AND `pw`='%s' AND (`banned`=0 OR `isadmin`=1) LIMIT 1",
			mysql_real_escape_string($f_name),
			mysql_real_escape_string(md5($f_pass)));
		$result = mysql_query($query);
		if($line = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			//if($_SERVER['REMOTE_ADDR']!='127.0.0.1'&&$_SERVER['REMOTE_ADDR']!='localhost')
			{
				$alias=($line['forum_alias']!==null)?$line['forum_alias']:"WT__".$line['name'];
				$query3 = sprintf("SELECT `wartool` FROM `ips` WHERE `ip`='%s' AND `watched`=1 LIMIT 1",
					mysql_real_escape_string($_SERVER['REMOTE_ADDR']));
				$r=mysql_query($query3);
				$w=0;
				if(!mysql_error() && $l=mysql_fetch_array($r,MYSQL_ASSOC))
				{
					$w=1;
					$query4 = sprintf("INSERT INTO `ips_watched` (`ip`,`time`,`acc`,`forum`) VALUES ('%s','%f','%s',0)",
						mysql_real_escape_string($_SERVER['REMOTE_ADDR']),
						mysql_real_escape_string(date("YmdHis")),
						mysql_real_escape_string($alias));
				}
				$query2 = sprintf("INSERT INTO `ips` (`name`,`ip`,`wartool`,`firstseen`,`lastseen`,`watched`) VALUES ('%s','%s',1,'%f','%f',$w) ON DUPLICATE KEY UPDATE `wartool`=VALUES(`wartool`),`lastseen`=VALUES(`lastseen`)",
					mysql_real_escape_string($alias),
					mysql_real_escape_string($_SERVER['REMOTE_ADDR']),
					mysql_real_escape_string(date("YmdHis")),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
			}
			return($line);
		}
		else return(false);
		
	}
	
	function token_create($c_name,$c_hash)
	{
		$query = sprintf("INSERT INTO `tokens` (`name`,`hash`,`ip`,`expires`) VALUES ('%s','%s','%s','%f') ON DUPLICATE KEY UPDATE `expires`=VALUES(`expires`)",
			mysql_real_escape_string($c_name),
			mysql_real_escape_string($c_hash),
			mysql_real_escape_string($_SERVER['REMOTE_ADDR']),
			mysql_real_escape_string(date("YmdHis",time()+3600)));
		mysql_query($query);
		if(mysql_affected_rows()>0) return(true); else return(false);
	}
	
	function token_delete($c_name)
	{
		$query = sprintf("DELETE FROM `tokens` WHERE `name`='%s'",
			mysql_real_escape_string($c_name));
		mysql_query($query);
	}
	
	function report_wrong_pw($f_name)
	{
		$query1 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`comment`,`time`) VALUES (0,0,'%s','%s','%f')",
			mysql_real_escape_string($f_name),
			mysql_real_escape_string($_SERVER['REMOTE_ADDR']),
			mysql_real_escape_string(date("YmdHis")));
		$query2 = sprintf("UPDATE `users` SET `fail_logins`=`fail_logins`+1 WHERE `name`='%s'",
			mysql_real_escape_string($f_name));
		$query3 = sprintf("UPDATE `users` SET `banned`=1 WHERE `fail_logins`>=10 AND `name`='%s' AND `isadmin`=0",
			mysql_real_escape_string($f_name));
		mysql_query($query1);
		mysql_query($query2);
		mysql_query($query3);
		if(!mysql_error())
		{	if(mysql_affected_rows()>0)
			{
				$query4 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`time`) VALUES (8,0,'%s','%f')",
					mysql_real_escape_string($f_name),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query4);
			}
		}
	}
	
	function set_pw($f_name,$f_hash,$f_old)
	{
		$query1 = sprintf("UPDATE `users` SET `pw`='%s',`fail_logins`=0 WHERE `name`='%s' AND `pw`='%s'",
			mysql_real_escape_string($f_hash),
			mysql_real_escape_string($f_name),
			mysql_real_escape_string($f_old));
		$query2 = sprintf("UPDATE `tokens` SET `hash`='%s' WHERE `name`='%s'",
			mysql_real_escape_string($f_hash),
			mysql_real_escape_string($f_name),
			mysql_real_escape_string($f_old));
		mysql_query($query2);
		mysql_query($query1);
		return(mysql_error())?false:true;
	}
}
?>