<?php

require("Wsql.php");
require("Wsql_ava.php");
require("CookiMgr.php");
$sql = new Wsql_ava();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";
$perm_name = "perm_availability";


if($cmgr->allset())
{
	$accdata = $sql->token_auth($cmgr->get_user(),$cmgr->get_hash());
	if(!$accdata) {$sql=null; $par="Location: ./?name=".$cmgr->get_user(); header($par);}
	else $loggedin = true;
}
else header("Location: ./");

if($loggedin)
{
	$username = $accdata['name'];
	$userlevel = ($accdata['isadmin']==1)?4:$accdata[$perm_name];
/****************************************************
**  POST ACTIONS FOR ALL SUBMODULES   (pre-view)   **
****************************************************/
	if(isset($_POST['act']))
	{
		if($_POST['act']=="self_tim") if($userlevel>0) {$temp=$sql->read_pattern(); if($sql->save_pattern($username,$temp)) {$accdata['availability']=$sql->encode_pattern($temp); $response="Your availability settings have been updated.";} else {$respfail=1; $response="Your availability settings could not be updated.";}} else header("Location: ./mod_deny.php");
		elseif($_POST['act']=="othr_tim") if($userlevel>=3) {$temp=$sql->read_pattern(); if($sql->save_pattern($_POST['targetname'],$temp)) {$response=$_POST['targetname'] . "'s availability settings have been updated.";} else {$respfail=1; $response=$_POST['targetname'] . "'s settings could not be updated.";}} else header("Location: ./mod_deny.php");
		elseif($_POST['act']=="self_na") if($userlevel>0) {if($sql->toggle_na($username,$accdata['na'])) {$accdata['na']=($accdata['na']==1)?0:1; $response=($accdata['na']==1)?"Your status has been set to N/A.":"Your N/A status has been lifted.";} else {$respfail=1; $response="Your status could not bet toggled.";}} else header("Location: ./mod_deny.php");
		elseif($_POST['act']=="dona") if($userlevel>=3) {if($sql->toggle_na_other($_POST['uname'],1,$username)) $response=$_POST['uname']."'s status has been set to N/A."; else {$respfail=1; $response="Failed to toggle ".$_POST['uname']."'s N/A status.";}} else header("Location: ./mod_deny.php");
		elseif($_POST['act']=="unna") if($userlevel>=3) {if($sql->toggle_na_other($_POST['uname'],0,$username)) $response=$_POST['uname']."'s N/A status has been lifted."; else {$respfail=1; $response="Failed to toggle ".$_POST['uname']."'s N/A status.";}} else header("Location: ./mod_deny.php");
	}
/*********************************************************
**  SWITCH THROUGH VIEWS ($userlevel>=X for permission)   **
*********************************************************/
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	switch($view)
	{
		case 'edit_other':
			if($userlevel>=3)
			{
				if(isset($_REQUEST['targetname']))
				{
					
					$targetname = (isset($_POST['targetname']))?$_POST['targetname']:strip_slashes(urldecode($_GET['targetname']));
					$navpath = "<a href='./'>main menu</a> - <a href='./mod_avai.php'>phone list</a> - <a href='./mod_avai.php?view=edit_other'>edit other user's availability</a> - user: $targetname";
					$pagetitle = "Wartool - phone list - edit others' availability - user: $targetname";
					$centerbox = 1;
					$javascript = <<<EOD
			
<script>
function avail_clrself(e)
{
	if(e.checked) e.parentElement.style.background='#C4F4CC';
	else e.parentElement.style.background='#FE9792';
}
function avail_tickall(e,type)
{
	par=e.checked;
	if(type==0)
	{
		for(j=0;j<24;j+=1)
		{
			str=e.value+j;
			x=document.getElementById(str);
			x.checked=par;
			avail_clrself(x,0);
		}
	}
	else
	{
		for(j=0;j<7;j+=1)
		{
			str=j+e.value;
			x=document.getElementById(str);
			x.checked=par;
			avail_clrself(x,0);
		}
	}
}
</script>
EOD;
					require("inc_head.php");
					$output .= ($temp=$sql->read_pattern())?$sql->draw_pattern($temp,$targetname):$sql->draw_pattern($sql->decode_pattern($sql->load_pattern($targetname)),$targetname);
					
				}
				else
				{
					
					if($usrlist=$sql->get_userlist())
					{
						$navpath = "<a href='./'>main menu</a> - <a href='./mod_avai.php'>phone list</a> - edit other user's availability";
						$pagetitle = "Wartool - phone list - edit others' availability";
						$centerbox = 1;
						require("inc_head.php");
						$output .= "<form action='./mod_avai.php?view=edit_other' name='u_select' method='post'>\n<select name='targetname' size='1'>\n";
						foreach($usrlist as $key => $val)
							$output .= ($key==0)?"<option value='$val' SELECTED>$val\n":"<option value='$val'>$val\n";
						$output .= "</select>\n<input type='submit' value=' Edit Selected User '></form>";
					}
				}
			}
			else header("Location: ./mod_deny.php");
		break;
		
		case 'na_other':
			if($userlevel<3) header("Location: ./mod_deny.php"); 
			$pagetitle = "Wartool - phone list - edit others' N/A status";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_avai.php'>phone list</a> - edit others' N/A status";
			$centerbox=1;
			$banned = array();
			$notbanned = array();
			if($allaccs = $sql->get_all_user_na_status())
			{
				require("inc_head.php");
				foreach($allaccs as $ind => $user)
					if($user['na']==1) $banned[]=$user['name']; else $notbanned[]=$user['name'];
				$output .= "<table width='90%' border='0' align='center'>\n<tr>\n<td valign='baseline'>\n";
				$output .= "<strong>AVAILABLE USERS</strong>\n<br><br>\n";
				if(count($notbanned)>0)
				{
					$output .= "<form action='./mod_avai.php?view=na_other' method='post'>\n";
					$output .= "<input type='hidden' name='act' value='dona'><select size='1' name='uname'>\n";
					$output .= "<option value='" . $notbanned[0] . "' SELECTED>" . $notbanned[0] . "\n";
					if(count($notbanned)>1)
						for($i=1;$i<count($notbanned);$i+=1)
							$output .= "<option value='" . $notbanned[$i] . "'>" . $notbanned[$i] . "\n";
					$output .= "</select>\n";
					$output .= " <input type='submit' value=' make user N/A '>\n</form>\n";
				}
				else $output .= "There are no available users.\n";
				$output .= "</td><td valign='baseline'>\n";
				$output .= "<strong>N/A USERS</strong>\n<br><br>\n";
				if(count($banned)>0)
				{
					$output .= "<form action='./mod_avai.php?view=na_other' method='post'>\n";
					$output .= "<input type='hidden' name='act' value='unna'><select size='1' name='uname'>\n";
					$output .= "<option value='" . $banned[0] . "' SELECTED>" . $banned[0] . "\n";
					if(count($banned)>1)
						for($i=1;$i<count($banned);$i+=1)
							$output .= "<option value='" . $banned[$i] . "'>" . $banned[$i] . "\n";
					$output .= "</select>\n";
					$output .= " <input type='submit' value=' lift N/A status '>\n</form>\n";
				}
				else $output .= "There are no N/A users.\n";
				$output .= "</td></tr></table>";
			}
		break;
		
		case 'edit':
			if($userlevel==0) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - phone list - edit availability";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_avai.php'>phone list</a> - edit my availability";
			$centerbox = 1;
			$javascript = <<<EOD
			
<script>
function avail_clrself(e)
{
	if(e.checked) e.parentElement.style.background='#C4F4CC';
	else e.parentElement.style.background='#FE9792';
}
function avail_tickall(e,type)
{
	par=e.checked;
	if(type==0)
	{
		for(j=0;j<24;j+=1)
		{
			str=e.value+j;
			x=document.getElementById(str);
			x.checked=par;
			avail_clrself(x,0);
		}
	}
	else
	{
		for(j=0;j<7;j+=1)
		{
			str=j+e.value;
			x=document.getElementById(str);
			x.checked=par;
			avail_clrself(x,0);
		}
	}
}
</script>

EOD;
			require("inc_head.php");
			$output .= ($temp=$sql->read_pattern())?$sql->draw_pattern($temp,false):$sql->draw_pattern($sql->decode_pattern($accdata['availability']),false);
		break;
		default:
			if($userlevel==0) header("Location: ./mod_deny.php");
			if($view!='avail')
			{
				$pagetitle = "Wartool - phone list";
				$navpath = "<a href='./'>main menu</a> - phone list";
				$centerbox = 1;
				require("inc_head.php");
				
				$output .= "<a href='./'>back</a>\n<br><br>\n";
				$output .= "<a href='./mod_avai.php?view=edit'>edit my availability</a><br>";
				$output .= ($userlevel>=3)?"<a class='admfkt' href='./mod_avai.php?view=edit_other'>edit others' availability</a><br>":"";
				$output .= ($userlevel>=3)?"<a class='admfkt' href='./mod_avai.php?view=na_other'>edit others' N/A status</a><br>":"";
				$output .= "<a href='./mod_avai.php?view=avail'>view available only</a><br><br>";
				$data=$sql->get_table(false);
				if($data)
				{
					$color=array();
					for($i=0;$i<sizeof($data);$i+=1)
					{
						if($data[$i]['cur']==1&&$data[$i]['na']==0)
						$ccode[]="bggreen";
						else $ccode[]="bgred";
					}
					$output .= "<table>\n";
					$output .= "<tr>\n\t<th>N/A</th>\n\t<th>name</th>\n\t<th>number</th>\n\t<th>Skype</th>\n\t<th>MSN</th>\n\t<th colspan='2'>mainchar</th>\n</tr>\n";
					foreach($data as $key => $val)
					{
						$phone = ($val['phone']!=null)?$val['phone']:'';
						$bold = ($val['cc']==$accdata['cc']&&$accdata['cc']!=null&&$val['name']!=$username)?" style='font-weight:bold;'":"";
						$skype = ($phone!=''&&$val['na']==0)?str_replace(array(' ','+',),array('',"<a href=\"callto://00"),$phone) . "\">SkypeOut</a>":"";
						$status = ($val['na']==0)?"<img src='img/stat_norm.gif'>":"<img src='img/stat_na.gif'>";
						$rdy = ($val['rdy']==1)?"<img src='img/stat_norm.gif'>":(($val['rdy']==null)?'':"<img src='img/stat_na.gif'>");
						$output .= "<tr class='" . $ccode[$key] . "'>\n\t<td align=\"center\">".$status."</td>\n\t<td".$bold.">".$val['name']."</td>\n\t<td>".$phone."</td>\n\t<td>".$skype."</td>\n\t<td>".$val['msn']."</td>\n\t<td align='right'>".$val['main']."</td>\n\t<td align='center'>".$rdy."</td>\n</tr>\n";
					}
					$output .= "</table>\n";
					$output .= "<form name='toggle_self_na' action='./mod_avai.php' method='post'><input type='hidden' name='act' value='self_na'><input type='submit' value='Change my N/A status'></form>";
					$output .= "\n<br><br>\nAvailability is automatically updated every hour, based on your availability patterns (edit availability).<br>\n";
					$output .= "These give a rough impression of who can be called at the moment if needed and who cannot.<br>\n";
					$output .= "Use this feature to have yourself marked as red automatically for times you are at school/work/training/etc.<br>\n";
					$output .= "Note: people from your country (phone prefix) have their names bolded.<br><br>\n";
					$output .= "<strong>DO NOT CALL N/A PEOPLE!</strong><br>\n";
					$output .= "A 'STOP' sign in the N/A column denotes that the user is 110% NOT AVAILABLE.<br>\n";
					$output .= "Use N/A mode when you e.g. go on vacation or need to sleep before an exam or something like that,<br>\n";
					$output .= "but don't forget to remove it again afterwards.";
				}
				else $output = "ERROR, DB EMPTY OR INACCESSIBLE";
			}
			else
			{
				$pagetitle = "Wartool - phone list - view available only";
				$navpath = "<a href='./'>main menu</a> - <a href='./mod_avai.php'>phone list</a> - view available only";
				$centerbox = 1;
				require("inc_head.php");
				
				$output .= "<a href='./'>back</a>\n<br><br>\n";
				$output .= "<a href='./mod_avai.php?view=edit'>edit my availability</a><br>";
				$output .= ($userlevel>=3)?"<a class='admfkt' href='./mod_avai.php?view=edit_other'>edit others' availability</a><br>":"";
				$output .= ($userlevel>=3)?"<a class='admfkt' href='./mod_avai.php?view=na_other'>edit others' N/A status</a><br>":"";
				$output .= "<a href='./mod_avai.php'>view all</a><br><br>";
				$data=$sql->get_table(true);
				if($data)
				{
					$output .= "<table>\n";
					$output .= "<tr>\n\t<th>name</th>\n\t<th>number</th>\n\t<th>Skype</th>\n\t<th>MSN</th>\n\t<th colspan='2'>mainchar</th>\n</tr>\n";
					foreach($data as $key => $val)
					{
						$bold = ($val['cc']==$accdata['cc']&&$accdata['cc']!=null&&$val['name']!=$username)?" style='font-weight:bold;'":"";
						$phone = ($val['phone']!=null)?$val['phone']:'';
						$skype = ($phone!='')?str_replace(array(' ','+',),array('',"<a href=\"callto://00"),$phone) . "\">SkypeOut</a>":"";
						$rdy = ($val['rdy']==1)?"<img src='img/stat_norm.gif'>":(($val['rdy']==null)?'':"<img src='img/stat_na.gif'>");
						$output .= "<tr class='bggreen'>\n\t<td".$bold.">" . $val['name'] . "</td>\n\t<td>" . $phone . "</td>\n\t<td>" . $skype . "</td>\n\t<td>".$val['msn']."</td>\n\t<td align='right'>".$val['main']."</td>\n\t<td align='center'>".$rdy."</td>\n</tr>\n";
					}
					$output .= "</table>\n";
					$output .= "\n<br><br>\nAvailability is automatically updated every hour, based on your availability patterns (edit availability).<br>\n";
					$output .= "These give a rough impression of who can be called at the moment if needed and who cannot.<br>\n";
					$output .= "Use this feature to have yourself excluded from this list automatically for times you are at school/work/training/etc.<br>\n";
					$output .= "Note: people from your country (phone prefix) have their names bolded.<br><br>\n";
				}
				else $output .= "Nobody available right now. Go to bed, lol.";
			}
			
		break;
	}
	
	$sql=null;
	
	require("inc_foot.php");
	echo $output;
}
else {$sql=null; header("Location: ./");}

?>