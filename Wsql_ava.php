<?php
class Wsql_ava extends Wsql
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

	function get_table($onlyavail)
	{
		$ret=array();
		if($onlyavail) {
			$query = "SELECT `u`.`name` as `name`,`u`.`phone` AS `phone`,`u`.`msn` AS `msn`,`c`.`name` as `main`,`u`.`country_code` as `cc`,";
			$query.= "`c`.`o_rdy` as `rdy` FROM `users` AS `u` LEFT OUTER JOIN `characters` AS `c` ON (`u`.`name`=`c`.`o_owner` AND `c`.`o_is_ours`=1 AND `c`.`o_is_second`=0)";
			$query.= " WHERE (`u`.`validate` IS NULL) AND `u`.`availability_cur`=1 AND `u`.`availability_na`=0 ORDER BY `name` ASC,`rdy` DESC,`c`.`level` DESC";}
		else {
			$query = "SELECT `u`.`name` as `name`,`u`.`phone` AS `phone`,`u`.`availability_cur`AS `cur`,`u`.`availability_na` AS `na`,`u`.`msn` AS `msn`,`c`.`name` as `main`,";
			$query.= "`country_code` as `cc`,`c`.`o_rdy` as `rdy` FROM `users` AS `u` LEFT OUTER JOIN `characters` AS `c` ON (`u`.`name`=`c`.`o_owner` AND `c`.`o_is_ours`=1";
			$query.= " AND `c`.`o_is_second`=0) WHERE `u`.`validate` IS NULL ORDER BY `na` ASC,`cur` DESC,`name` ASC,`rdy` DESC,`c`.`level` DESC";}
		$result = mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_num_rows($result)>0)
			{
				$limiter=array();
				while($line = mysql_fetch_array($result, MYSQL_ASSOC)) if(!in_array($line['name'],$limiter)) {$limiter[]=$line['name']; $ret[]=$line;}
				return($ret);
			}
			else return(false);
		}
		else return(false);
	}
	
	function get_all_user_na_status()
	{
		$query = "SELECT `name`,`availability_na` as `na` FROM `users` WHERE `validate` IS NULL ORDER BY `name` ASC";
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
	
	function toggle_na_other($usrname,$na,$admname)
	{
		$na=($na==false||$na==0||$na=='0')?0:1;
		$na2=$na+2;
		$query = sprintf("UPDATE `users` SET `availability_na`=$na WHERE `name`='%s'",
			mysql_real_escape_string($usrname));
		mysql_query($query);
		if(!mysql_error())
			if(mysql_affected_rows()>0)
			{
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`user2`,`time`) VALUES (10,$na2,'%s','%s','%f')",
					mysql_real_escape_string($usrname),
					mysql_real_escape_string($admname),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
				return(true);
			}
			else return(false);
		else return(false);
	}
	
	function get_other_pattern($uname)
	{
		$query = sprintf("SELECT `availability`,`availability_na` FROM `users` WHERE `name`='%s'",
			mysql_real_escape_string($uname));
		$result = mysql_query($query);
		if(!mysql_error())
			if(mysql_num_rows($result)>0)
				return(mysql_fetch_array($result, MYSQL_ASSOC));
			else return(false);
		else return(false);
	}
	
	function toggle_na($uname,$setfrom)
	{
		$setfrom=($setfrom==0||$setfrom==1)?$setfrom:0;
		$setto=($setfrom==1)?0:1;
		$query = sprintf("UPDATE `users` SET `availability_na`=%d WHERE `name`='%s'",
			mysql_real_escape_string($setto),
			mysql_real_escape_string($uname));
		mysql_query($query);
		if(!mysql_error())
		{
			if(mysql_affected_rows()>0)
			{
				$query2 = sprintf("INSERT INTO `events` (`type1`,`type2`,`user1`,`time`) VALUES (10,$setto,'%s','%f')",
					mysql_real_escape_string($uname),
					mysql_real_escape_string(date("YmdHis")));
				mysql_query($query2);
				return(true);
			}
		}
		else return(false);
	}
	
	function encode_pattern($dat)
	{
		
		$code = "";
		for($i=0;$i<7;$i+=1)
		{
			$str = "";
			for($j=0;$j<24;$j+=1)
			{
				$str .= $dat[$i][$j];
			}
			$str = base_convert($str,2,16);
			while(strlen($str)<6) $str = "0" . $str;
			$code .= $str;
			if ($i<6) $code .= "-";
		}
		return $code;
	}

	function decode_pattern($code)
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
		return $dat;
	}
	
	function read_pattern()
	{
		if(isset($_POST['pattern']))
		{
			$dat=array();
			$pattern=($_POST['pattern']!==null)? $_POST['pattern'] : null;
			for($i=0;$i<7;$i+=1)
			{
				$temp=array();
				for($j=0;$j<24;$j+=1)
				{
					$par="c".$i.$j;
					$ae = (in_array($par,$pattern))? 1 : 0;
					$temp[]=$ae;
				}
				$dat[]=$temp;
				unset($temp);
			}
			return($dat);
		}
		else return(false);
	}
	
	function draw_pattern($dat,$tarname)
	{
		$timezone = "CET";
		$curday = date("N");
		$curtime = date("G");
		if($curtime-$this->starttime<0) $curday-=1;
		$content = "";
		$days = array("MON","TUE","WED","THU","FRI","SAT","SUN");
		$actionadd = ($tarname==false)?'':'_other';
		$content .= "<form name='pattern' action='./mod_avai.php?view=edit$actionadd' method='post'>\n<table style='text-align:center;' width='600'>\n<tr>\n<th colspan='2' rowspan='2' class='bggen'>$timezone</th>\n";
		for($n=0;$n<7;$n+=1)
			$content .= ($curday-1==$n||($curday==0&&$n==6))?"<th class='bggenfocus'>$days[$n]</th>\n":"<th class='bggen'>$days[$n]</th>\n";
		$content .= "</tr>\n<tr>\n";
		for($n=0;$n<7;$n+=1)
			$content .= ($curday-1==$n||($curday==0&&$n==6))?"<td class='bggenfocus'><input type='checkbox' value='$n' OnClick='avail_tickall(this,0);'></td>\n":"<td class='bggen'><input type='checkbox' value='$n' OnClick='avail_tickall(this,0);'></td>\n";
		$content .= "</tr>\n";
		$time=$this->starttime;
		for($i=0;$i<24;$i+=1)
		{
			$next=$time+1;
			if($time>23) $time-=24;
			if($next>23) $next-=24;
			if($time==9) $content .= "<tr>\n<td colspan='9' class='bgdark'><b>SERVER SAVE</b></td>\n</tr>\n";
			$ccode=($curtime==$time)?"bggenfocus":"bggen";
			$content .= ($time<10)?"<tr>\n<td class='$ccode'>0$time - ":"<tr>\n<td class='$ccode'>$time - ";
			$content .= ($next<10)?"0$next</td>\n<td class='$ccode'>\n<input type='checkbox' value='$i' OnClick='avail_tickall(this,1);'>\n</td>\n":"$next</td>\n<td class='$ccode'>\n<input type='checkbox' value='$i' OnClick='avail_tickall(this,1);'>\n</td>\n";
			
			foreach($dat as $ind => $day)
			{
				if($day[$i]==1) $content .= "<td class='bggreen'>\n<input type='checkbox' id='$ind$i' OnClick='avail_clrself(this);' name='pattern[]' value='c$ind$i' CHECKED>\n</td>\n";
				else $content .= "<td class='bgred'>\n<input type='checkbox' id='$ind$i' OnClick='avail_clrself(this);' name='pattern[]' value='c$ind$i'>\n</td>\n";
			}
			$content .= "</tr>\n";
			$time+=1;
			if ($time>=24) $time-=24;
		}
		$posttar=($tarname==false)?"self_tim":"othr_tim";
		$targetadd=($tarname==false)?"":"<input type='hidden' name='targetname' value=\"$tarname\">\n";
		$content .= "</table>\n<input type='hidden' name='act' value='$posttar'>\n$targetadd<br>\n<input type='submit' value=' Save Changes '>\n</form>\n";
		$content .= "<form name='abort' action='mod_avai.php' method='get'><input type='submit' value=' back without Saving '></form>\n";
		return($content);
	}
	
	function find_cur($data,$isencoded)
	{
		if($isencoded) $data=$this->decode_pattern($data);
		$offset = $this->starttime;
		$curday = date("N");
		$curhour = date("G");
		if($curhour-$offset<0) $curday-=1;  if($curday==0) $curday=7;
		$hour = $curhour-$offset; if ($hour<0) $hour+=24;
		if($data[$curday-1][$hour]==1) return(1); else return(0);
	}
	
	function save_pattern($puser,$data)
	{
		$cur = $this->find_cur($data,false);
		$query = sprintf("UPDATE `users` SET `availability`='%s',`availability_cur`=%d WHERE `name`='%s'",
			mysql_real_escape_string($this->encode_pattern($data)),
			mysql_real_escape_string($cur),
			mysql_real_escape_string($puser));
		mysql_query($query);
		if(!mysql_error())
			if(mysql_affected_rows()>0)
				return(true);
			else return(false);
		else return(false);
	}
	
	function load_pattern($puser)
	{
		$query = sprintf("SELECT `availability` FROM `users` WHERE `name`='%s'",
			mysql_real_escape_string($puser));
		$result=mysql_query($query);
		if(!mysql_error())
			if($line = mysql_fetch_array($result, MYSQL_ASSOC))
				return($line['availability']);
			else return(false);
		else return false;
	}
	
	function get_userlist()
	{
		$query = "SELECT `name` FROM `users` WHERE 1=1 ORDER BY `name` ASC";
		mysql_query($query);
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
}