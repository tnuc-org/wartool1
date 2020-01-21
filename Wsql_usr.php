<?php
class Wsql_usr extends Wsql
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
	
	function get_all_user_ban_status()
	{
		$query = "SELECT `name`,`banned` FROM `users` WHERE `isadmin`=0 AND `validate` IS NULL ORDER BY `name` ASC";
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
	
	function get_all_names($exclude)
	{
		$ret=array();
		if($exclude===false)
		$query = "SELECT `name` FROM `users` ORDER BY `name` ASC";
		else
		$query = sprintf("SELECT `name` FROM `users` WHERE `name`<>'%s' ORDER BY `name` ASC",
			mysql_real_escape_string($exclude));
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_num_rows($result)>0)
			{
				while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[]=$line['name'];
				return($ret);
			}
			else return(false);
		}
		else return(false);
	}
	
	function get_user_alias($name)
	{
		$query = sprintf("SELECT `forum_alias` AS `alias` FROM `users` WHERE `name`='%s'",
			mysql_real_escape_string($name));
		$result = mysql_query($query);
		if(!mysql_error())
		{
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			return(($line['alias']===null)?'null':$line['alias']);
		}
		else return(-1);
	}
	
	function set_user_alias($aname,$aalias)
	{
		$leftover=array();
		$prev=$this->get_user_alias($aname);
		if($prev===null) $prev="WT__".$aname;
		$query1 = sprintf("UPDATE `users` SET `forum_alias`='%s' WHERE `name`='%s'",
			mysql_real_escape_string($aalias),
			mysql_real_escape_string($aname));
		$query2 = sprintf("UPDATE `ips` SET `name`='%s' WHERE `name`='%s'",
			mysql_real_escape_string($aalias),
			mysql_real_escape_string($prev));
		$query3 = sprintf("SELECT `ip` FROM `ips` WHERE `name`='%s'",
			mysql_real_escape_string($prev));
		
		mysql_query($query2);
		$result=mysql_query($query3);
		if(!mysql_error())
		if(mysql_num_rows($result)>0)
		{
			while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $leftover[]=$line['ip'];
			foreach($leftover as $k => $v) $leftover[$k]="'".$v."'";
			$ins=implode($leftover,",");
			$query4 = sprintf("UPDATE `ips` SET `wartool`=1 WHERE `name`='%s' AND `ip` IN (%s)",
				mysql_real_escape_string($aalias),
				$ins);
			$query5 = sprintf("DELETE FROM `ips` WHERE `name`='%s'",
				mysql_real_escape_string($prev));
			mysql_query($query4);
			mysql_query($query5);
		}
		mysql_query($query1);
		if(!mysql_error())
			if(mysql_affected_rows()==1)
				return(true);
			else return(false);
		else return(false);
	}
	
	function get_all_non_admins($exclude)
	{
		$ret=array();
		if($exclude===false)
		$query = "SELECT `name` FROM `users` WHERE `isadmin`=0 ORDER BY `name` ASC";
		else
		$query = sprintf("SELECT `name` FROM `users` WHERE `isadmin`=0 AND `name`<>'%s' ORDER BY `name` ASC",
			mysql_real_escape_string($exclude));
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_num_rows($result)>0)
			{
				while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[]=$line['name'];
				return($ret);
			}
			else return(false);
		}
		else return(false);
	}
	
	function toggle_ban_user($username,$ban,$admname)
	{
		$ban=($ban==false||$ban==0||$ban=='0')?0:1;
		$reset=($ban==0)?",`fail_logins`=0":"";
		$query = sprintf("UPDATE `users` SET `banned`=$ban$reset WHERE `name`='%s'",
			mysql_real_escape_string($username));
		mysql_query($query);
		if(!mysql_error())
			if(mysql_affected_rows()>0)
			{
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`) VALUES (9,$ban,'%s','%s','%f')",
					mysql_real_escape_string($username),
					mysql_real_escape_string($admname),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
				return(true);
			}
			else return(false);
		else return(false);
	}
	
	function get_account_list()
	{
		$ret=array();
			$query = "SELECT `u`.`name` AS `cname`,`u`.`forum_alias` AS `alias`,`c1`.`name` AS `mainname`,`c1`.`level` AS `mainlevel`,`c1`.`voc` AS `mainvoc`,`c1`.`o_rdy` AS `mainrdy`,`c2`.`level` AS `sder`,";
			$query.= "`u`.`banned` AS `banned`,`u`.`isadmin` AS `isadmin`,`u`.`phone` AS `phone`,`u`.`msn` AS `msn`,`u`.`availability_cur` AS `cur`,`u`.`availability_na`";
			$query.= "AS `na`,`u`.`validate` AS `valid` FROM `users` AS `u` LEFT OUTER JOIN `characters` AS `c1` ON (`c1`.`o_owner`=`u`.`name` AND `c1`.`o_is_second`=0) ";
			$query.= "LEFT OUTER JOIN `characters` as `c2` ON (`c2`.`o_owner`=`u`.`name` AND `c2`.`o_is_second`=1 AND (`c2`.`voc`='ED' OR `c2`.`voc`='MS') ";
			$query.= "AND `c2`.`level`>44) ORDER by `cname` ASC,`c1`.`level` DESC";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_num_rows($result)>0)
			{
				$limiter=array();
				while($line = mysql_fetch_array($result, MYSQL_ASSOC)) if(!in_array($line['cname'],$limiter)) {$limiter[]=$line['cname']; $ret[]=$line;}
				return($ret);
			}
			else return(false);
		}
		else return(false);
	}
	
	function delete_user($uname,$admname)
	{
		$query1 = sprintf("DELETE FROM `users` WHERE `name`='%s'",
			mysql_real_escape_string($uname));
		$query2 = sprintf("UPDATE `characters` SET `o_is_ours`=0,`o_owner`=NULL WHERE `o_owner`='%s'",
			mysql_real_escape_string($uname));
		$query3 = sprintf("DELETE FROM `tokens` WHERE `name`='%s'",
			mysql_real_escape_string($uname));
		$query4 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`) VALUES (11,4,'%s','%s','%f')",
			mysql_real_escape_string($uname),
			mysql_real_escape_string($admname),
			mysql_real_escape_string(date("YmdHis")));
		mysql_query($query3);
		mysql_query($query2);
		mysql_query($query1);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				mysql_query($query4);
				return(true);
			}
			else return(false);
		}
		else return(false);
	}
	
	function create_account($aname,$aalias,$arights,$admname)
	{
		$a=(trim($aalias)!='')?true:false;
		if($arights==2) $r=2; elseif($arights==1) $r=1; else $r=0;
		$v=md5(date("Y-md:Hi.s"));
		if($a)
		$query = sprintf("INSERT INTO `users` (`name`,`forum_alias`,`validate`,`perm_charlist`,`perm_availability`,`perm_ourchars`,`perm_usermgr`,`perm_traps`,`perm_stats`,`perm_refresh`,`perm_vent`,`acctype`) VALUES ('%s','%s','%s',$r,$r,$r,$r,$r,$r,$r,$r,$r)",
			mysql_real_escape_string($aname),
			mysql_real_escape_string($aalias),
			mysql_real_escape_string($v));
		else
		$query = sprintf("INSERT INTO `users` (`name`,`validate`,`perm_charlist`,`perm_availability`,`perm_ourchars`,`perm_usermgr`,`perm_traps`,`perm_stats`,`perm_refresh`,`perm_vent`,`acctype`) VALUES ('%s','%s',$r,$r,$r,$r,$r,$r,$r,$r,$r)",
			mysql_real_escape_string($aname),
			mysql_real_escape_string($v));
		$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`,`comment`) VALUES (11,1,'%s','%s','%f',$r)",
			mysql_real_escape_string($aname),
			mysql_real_escape_string($admname),
			mysql_real_escape_string(date("YmdHis")));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				mysql_query($query2);
				return(true);
			}
			else return(false);
		}
		else return(false);
	}
	
	function qcreate_account($aname,$aalias,$apass,$arights,$admname)
	{
		$a=(trim($aalias)!='')?true:false;
		if($arights==2) $r=2; elseif($arights==1) $r=1; else $r=0;
		if($a)
		$query = sprintf("INSERT INTO `users` (`name`,`forum_alias`,`pw`,`banned`,`perm_charlist`,`perm_availability`,`perm_ourchars`,`perm_usermgr`,`perm_traps`,`perm_stats`,`perm_refresh`,`perm_vent`,`acctype`) VALUES ('%s','%s','%s',0,$r,$r,$r,$r,$r,$r,$r,$r,$r)",
			mysql_real_escape_string($aname),
			mysql_real_escape_string(trim($aalias)),
			mysql_real_escape_string(md5($apass)));
		else
		$query = sprintf("INSERT INTO `users` (`name`,`pw`,`banned`,`perm_charlist`,`perm_availability`,`perm_ourchars`,`perm_usermgr`,`perm_traps`,`perm_stats`,`perm_refresh`,`perm_vent`,`acctype`) VALUES ('%s','%s',0,$r,$r,$r,$r,$r,$r,$r,$r,$r)",
			mysql_real_escape_string($aname),
			mysql_real_escape_string(md5($apass)));
		$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`,`comment`) VALUES (11,3,'%s','%s','%f',$r)",
			mysql_real_escape_string($aname),
			mysql_real_escape_string($admname),
			mysql_real_escape_string(date("YmdHis")));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				mysql_query($query2);
				return(true);
			}
			else return(false);
		}
		else return(false);
	}
	
	function get_pending_accounts()
	{
		$query = "SELECT `name`,`validate` FROM `users` WHERE `validate` IS NOT NULL";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_num_rows($result)>0)
			{
				$ret = array();
				while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[]=$line;
				return($ret);
			}
			else return(0);
		}
		else return(false);
	}
	
	function get_validation_info($pu,$pv)
	{
		$query = sprintf("SELECT `name`,`acctype`,`msn`,`country_code` AS `cc`,`phone` FROM `users` WHERE `name`='%s' AND `validate`='%s'",
			mysql_real_escape_string($pu),
			mysql_real_escape_string($pv));
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_num_rows($result)==1)
			{
				$line = mysql_fetch_array($result, MYSQL_ASSOC);
				return($line);
			}
			else return(false);
		}
		else return(false);
	}
	
	function validate($yname,$yhash,$ycc,$yph,$yms)
	{
		$qcc=($ycc===null)?"NULL":sprintf("'%s'",mysql_real_escape_string($ycc));
		$qph=($yph===null)?"NULL":sprintf("'%s'",mysql_real_escape_string($yph));
		$qms=($yms===null)?"NULL":sprintf("'%s'",mysql_real_escape_string($yms));
		$query = sprintf("UPDATE `users` SET `pw`='%s',`validate`=NULL,`banned`=0,`fail_logins`=0,`country_code`=%s,`phone`=%s,`msn`=%s WHERE `name`='%s'",
			mysql_real_escape_string($yhash),
			$qcc,
			$qph,
			$qms,
			mysql_real_escape_string($yname));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`time`) VALUES (11,2,'%s','%f')",
					mysql_real_escape_string($yname),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
				return(true);
			}
			else return(false);
		}
		else return(false);
	}
	
	function reset_pw($pname)
	{
		$query = sprintf("UPDATE `users` SET `banned`=1,`validate`='%s' WHERE `name`='%s'",
			mysql_real_escape_string(md5(date("Ym\x\Dd:Hi,s"))),
			mysql_real_escape_string($pname));
		mysql_query($query);
		if(!mysql_error())
			if(mysql_affected_rows()==1)
				return(true);
			else return(false);
		else return(false);
	}
	
	function get_non_admins_by_acctype()
	{
		$ret=array(array(),array(),array());
		$query = "SELECT `name`,`acctype` FROM `users` WHERE `isadmin`=0";
		$result=mysql_query($query);
		if(!mysql_error())
		{
			while($line = mysql_fetch_array($result, MYSQL_ASSOC)) $ret[$line['acctype']][]=$line['name'];
			if(count($ret[0])==0&&count($ret[1])==0&&count($ret[2])==0) return(false); else return($ret);
		}
		else return(false);
	}
	
	function get_acctype($accname)
	{
		$query = sprintf("SELECT `acctype`,`perm_charlist`,`perm_availability`,`perm_ourchars`,`perm_usermgr`,`perm_traps`,`perm_stats`,`perm_refresh`,`perm_vent` FROM `users` WHERE `name`='%s'",
			mysql_real_escape_string($accname));
		$result=mysql_query($query);
		if(!mysql_error())
			if($line = mysql_fetch_array($result, MYSQL_ASSOC)) return($line);
			else return(false);
		else return(false);
	}
	
	function set_acctype($pname,$pnew,$admname)
	{
		$prev = $this->get_acctype($pname);
		$str="";
		$prev2 = $prev['acctype'];
		unset($prev['acctype']);
		foreach($prev as $field => $perm) {if($perm==3) unset($prev[$field]); else $str.=",`$field`=$pnew";}
		$query = sprintf("UPDATE `users` SET `acctype`=$pnew%s WHERE name='%s'",
			$str,
			mysql_real_escape_string($pname));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				$change=($prev2<$pnew)?1:0;
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`,`comment`) VALUES (12,$change,'%s','%s','%f',$pnew)",
					mysql_real_escape_string($pname),
					mysql_real_escape_string($admname),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
				return(true);
			}
			else return(false);
		}
		else return(false);
	}
	
	function get_mod_data()
	{
		$ret=array(array(array(),array()),array(),true);
		$query = "SELECT `name`,'charlist' AS `module` FROM `users` WHERE `perm_charlist`=3 UNION SELECT `name`,'availability' FROM `users` WHERE `perm_availability`=3 UNION ";
		$query.= "SELECT `name`,'ourchars' FROM `users` WHERE `perm_ourchars`=3 UNION SELECT `name`,'usermgr' FROM `users` WHERE `perm_usermgr`=3 UNION SELECT `name`,'traps' ";
		$query.= "FROM `users` WHERE `perm_traps`=3 UNION SELECT `name`,'stats' FROM `users` WHERE `perm_stats`=3 UNION SELECT `name`,'refresh' FROM `users` WHERE `perm_refresh`=3 ";
		$query.= "UNION SELECT `name`,'vent' FROM `users` WHERE `perm_vent`=3 ORDER BY `name` ASC, `module` DESC";
		$result=mysql_query($query);
		if(!mysql_error())
		{
			while($line = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				$ret[0][0][]=$line['name'];
				$ret[0][1][]=$line['module'];
			}
			if($ret[1]=$this->get_all_non_admins(false))
			{
				return($ret);
			}
			else return(false);
		}
		else return(false);
	}
	
	function set_mod_rights($mname,$module,$appoint,$admname)
	{
		$hlp = "SET `perm_".$module."`=".(($appoint)?3:"`acctype`");
		$query = sprintf("UPDATE `users` %s WHERE `name`='%s'",
			$hlp,
			mysql_real_escape_string($mname));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()==1)
			{
				$hlp=($appoint)?1:0;
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`,`comment`) VALUES (2,$hlp,'%s','%s','%f','%s')",
					mysql_real_escape_string($mname),
					mysql_real_escape_string($admname),
					mysql_real_escape_string(date("YmdHis")),
					mysql_real_escape_string($module));
				mysql_query($query2);
				return(true);
			}
			else return(false);
		}
		else return(false);
	}
}
?>