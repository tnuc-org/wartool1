<?php
class Wsql_evt extends Wsql
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
	
	function get_all_guest_events($from,$name)
	{
		$from = ($from!=false&&$from!=null)?$from:0;
		$query = sprintf("SELECT * FROM `events` WHERE (`user1`='%s' OR `user2`='%s') ORDER by `time` DESC, `id` DESC LIMIT %d,100",
			mysql_real_escape_string($name),
			mysql_real_escape_string($name),
			mysql_real_escape_string((int) $from));
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
	
	function get_all_user_events($from,$name)
	{
		$from = ($from!=false&&$from!=null)?$from:0;
		$query = sprintf("SELECT * FROM `events` WHERE `type1`>7 OR (`user1`='%s' OR `user2`='%s') ORDER by `time` DESC, `id` DESC LIMIT %d,100",
			mysql_real_escape_string($name),
			mysql_real_escape_string($name),
			mysql_real_escape_string((int) $from));
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

	function get_all_admin_events($from)
	{
		$from = ($from!=false&&$from!=null)?$from:0;
		$query = sprintf("SELECT * FROM `events` ORDER by `time` DESC, `id` DESC LIMIT %d,100",
			mysql_real_escape_string((int) $from));
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
	
	function process_events($sqlevents)
	{
		$ret=array(array(),array());
		foreach($sqlevents as $key => $val)
		{
			$ret[0][]=$val['time'];
			switch($val['type1'])
			{
				case 0:
					$ret[1][]="<span class='evtbad'>FAILED LOGIN ATTEMPT</span> by <strong>".$val['user1']."</strong> from IP <strong>".$val['comment']."</strong>.";
				break;
				case 2:
					switch($val['comment'])
					{
						case 'availability': $o="phone list"; break;
						case 'charlist': $o="enemy chars"; break;
						case 'ourchars': $o="alliance chars"; break;
						case 'stats': $o="statistics"; break;
						case 'traps': $o="(boat) traps"; break;
						case 'usermgr': $o="account manager"; break;
						case 'refresh': $o="phone list"; break;
						case 'vent': $o="ventrilo"; break;
						case 'finances': $o="finances"; break;
						default: $o='#ERROR#'; break; 
					}
					$txt ="<strong>".$val['user2']."</strong> has ";
					$txt.=(($val['type2']==1)?"<span class='evtgood'>granted</span> <strong>".$val['user1']."</strong> <span class='evtgood'>moderator rights</span>":"<span class='evtbad'>removed</span> <strong>".$val['user1']."</strong>'s <span class='evtbad'>moderator rights</span>");
					$txt.=" for the <strong>".$o."</strong> module.";
					$ret[1][]=$txt;
				break;
				case 8:
					$ret[1][]="User <strong>".$val['user1']."</strong> has been <span class='evtbad'>AUTOBANNED</span> by the system.";
				break;
				case 9:
					if($val['type2']==1) {
						$ret[1][]="User <strong>".$val['user1']."</strong> has been <span class='evtbad'>BANNED</span> by <strong>".$val['user2']."</strong>.";
					}
					else {
						$ret[1][]="User <strong>".$val['user1']."</strong> has been <span class='evtgood'>UNBANNED</span> by <strong>".$val['user2']."</strong>.";
					}
				break;
				case 10:
					if($val['type2']==1) {
						$ret[1][]="User <strong>".$val['user1']."</strong> set his status to <span class='evtbad'>not available</span>.";
					}
					elseif($val['type2']==0) {
						$ret[1][]="User <strong>".$val['user1']."</strong> set his status to <span class='evtgood'>available</span> again.";
					}
					elseif($val['type2']==2) {
						$ret[1][]="<strong>".$val['user2']."</strong> set <strong>".$val['user1']."</strong>'s status to <span class='evtgood'>available</span> again.";
					}
					elseif($val['type2']==3) {
						$ret[1][]="<strong>".$val['user2']."</strong> set <strong>".$val['user1']."</strong>'s status to <span class='evtbad'>not available</span>.";
					}
				break;
				case 11:
					if($val['type2']==1)
					{
						if($val['comment']==0) $type="Guest Account";
						elseif($val['comment']==1) $type="Limited Account";
						else $type="Full Account";
						$ret[1][]="<strong>".$val['user2']."</strong> has <span class='evtgood'>created a new account</span> for <strong>".$val['user1']."</strong> (".$type.").";
					}
					elseif($val['type2']==2)
					{
						$ret[1][]="User <strong>".$val['user1']."</strong> has <span class='evtgood'>activated</span> his account.";
					}
					elseif($val['type2']==3)
					{
						if($val['comment']==0) $type="Guest Account";
						elseif($val['comment']==1) $type="Limited Account";
						else $type="Full Account";
						$ret[1][]="<strong>".$val['user2']."</strong> has <span class='evtgood'>pre-registered a new account</span> for <strong>".$val['user1']."</strong> (".$type.").";
					}
					elseif($val['type2']==4)
					{
						$ret[1][]="User <strong>".$val['user1']."</strong> has been <span class='evtbad'>DELETED</span> by <strong>".$val['user2']."</strong>.";
					}
				break;
				case 12:
					$typea=($val['type2']==0)?"<span class='evtbad'>demoted</span>":"<span class='evtgood'>promoted</span>";
					$typeb=($val['comment']==0)?"Guest Account":(($val['comment']==1)?"Limited Account":"Full Account");
					$ret[1][]="<strong>".$val['user2']."</strong> ".$typea." <strong>".$val['user1']."</strong> to ".$typeb.".";
				break;
				case 13:
					if($val['type2']==0)
						$ret[1][]="(mage list) <strong>".$val['user1']."</strong> has <span class='evtbad'>hidden</span> <strong>".$val['user2']."</strong>.";
					elseif($val['type2']==1)
						$ret[1][]="(mage list) <strong>".$val['user1']."</strong> has <span class='evtgood'>unhidden</span> <strong>".$val['user2']."</strong>.";
					elseif($val['type2']==2)
					{
						switch($val['comment'])
						{
							case '1': $tag="<span class='evtgood'>harmless</span>"; break;
							case '2': $tag="<span class='evtbad'>potential threat</span>"; break;
							case '3': $tag="<span class='evtbad'>exp limited</span>"; break;
							case '4': $tag="<span class='evtbad'>enemy</span>"; break;
							case '5': $tag="<span class='evtgood'>ally</span>"; break;
							default: $tag="<strong>unknown</strong>"; break;
						}
						$ret[1][]="(mage list) <strong>".$val['user1']."</strong> <span class='evtgood'>tagged</span> <strong>".$val['user2']."</strong> as ".$tag.".";
					}
					else
						$ret[1][]="Unknown event of type <strong>13-".(($val['type2']!==null&&$val['type2']!='')?$val['type2']:"X")."</strong> involving user <strong>".(($val['user1']!==null)?$val['user1']:"???")."</strong>.";
				break;
				case 15:
					if($val['type2']==0) 
						$ret[1][]="<strong>".$val['user1']."</strong> <span class='evtbad'>added</span> <strong>".$val['comment']."</strong> <span class='evtbad'>to the DB of enemy chars</span>.";
					elseif($val['type2']==1) 
						$ret[1][]="<strong>".$val['user1']."</strong> <span class='evtgood'>removed</span> <strong>".$val['comment']."</strong> <span class='evtgood'>from the DB of enemy chars</span>.";
					else
						$ret[1][]="Unknown event of type <strong>15-".(($val['type2']!==null&&$val['type2']!='')?$val['type2']:"X")."</strong> involving user <strong>".(($val['user1']!==null)?$val['user1']:"???")."</strong>.";
				break;
				case 16: 
					if($val['type2']==0) 
						$ret[1][]="<strong>".$val['user1']."</strong> <span class='evtgood'>added one of his</span> <strong>main</strong> <span class='evtgood'>characters</span>.";
					elseif($val['type2']==1) 
						$ret[1][]="<strong>".$val['user1']."</strong> <span class='evtgood'>added one of his</span> <strong>second</strong> <span class='evtgood'>characters</span>.";
				break;
				default:
					$ret[1][]="Unknown event of type <strong>".$val['type1']."-".(($val['type2']!==null&&$val['type2']!='')?$val['type2']:"X")."</strong> involving user <strong>".(($val['user1']!==null)?$val['user1']:"???")."</strong>.";
				break;
			}
		}
		return($ret);
	}
	
}