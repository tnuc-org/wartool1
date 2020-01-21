<?php
class Wsql_kny extends Wsql
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
	
	function get_shame()
	{
		$ret=array();
			$query = "SELECT `u`.`name` AS `cname`,`c1`.`level` AS `t1`,`c2`.level AS `t2`,`c3`.`level` AS `t3` FROM `users` as `u` LEFT OUTER JOIN `characters` as `c1` ON ";
			$query.= "(`c1`.`o_owner`=`u`.`name` AND (`c1`.`voc`='ED' OR `c1`.`voc`='MS') AND `c1`.`level`>=45 AND `c1`.`o_is_second`=1) LEFT OUTER JOIN `characters` as `c2` ON ";
			$query.= "(`c2`.`o_owner`=`u`.`name` AND `c2`.`o_is_second`=0 AND (`c2`.`o_accessq` LIKE '________________7%' OR `c2`.`o_accessq` LIKE '________________8%')) LEFT OUTER JOIN `characters` as `c3` ON ";
			$query.= "(`c3`.`o_owner`=`u`.`name` AND `c3`.`o_is_second`=1 AND `c3`.`o_accessq` NOT LIKE '________________0%' AND (`c3`.`voc`='ED' OR `c3`.`voc`='MS') AND `c3`.`level`>=45)";
			$query.= "WHERE `validate` IS NULL ORDER BY `cname` ASC";
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_num_rows($result)>0)
			{
				$limiter=array();
				while($line = mysql_fetch_array($result, MYSQL_ASSOC))
				{
					if(!in_array($line['cname'],$limiter))
					{
						$q=array($line['cname'],null,null,null,0);
						if($line['t1']==null) {$q[4]+=1; $q[1]=1;} else $q[1]=0;
						if($line['t2']==null) {$q[4]+=1; $q[2]=1;} else $q[2]=0;
						if($line['t3']==null) {$q[4]+=1; $q[3]=1;} else $q[3]=0;
						/*if($q[4]>=0)*/ $ret[]=$q;
						$limiter[]=$line['cname'];
					}
				}
				return($ret);
			}
			else return(0);
		}
		else return(false);
	}
}