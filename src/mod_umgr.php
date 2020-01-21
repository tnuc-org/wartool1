<?php
require("Wsql.php");
require("Wsql_usr.php");
require("CookiMgr.php");
$sql = new Wsql_usr();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";
$perm_name = "perm_usermgr";

if($cmgr->allset())
{
	$accdata = $sql->token_auth($cmgr->get_user(),$cmgr->get_hash());
	if(!$accdata) {$sql = null; $par = "Location: ./?name=".$cmgr->get_user(); header($par);}
	else $loggedin = true;
}
else header("Location: ./");

if($loggedin)
{
	$username = $accdata['name'];
	$userlevel = ($accdata['isadmin']==1)?4:$accdata[$perm_name];
	
	if(isset($_POST['act']))
	{
		switch($_POST['act'])
		{
			case 'delacc':
				if($userlevel<4) header("Location: ./mod_deny.php");
				if($sql->delete_user($_POST['delname'],$username))
					$response = "Account " . $_POST['delname'] . " has been deleted.";	
				else {$respfail=1; $response="Failed to delete account " . $_POST['delname'] . ".";}
			break;
			case 'banusr':
				if($userlevel<3) header("Location: ./mod_deny.php");
				if($sql->toggle_ban_user($_POST['uname'],1,$username))
					$response = $_POST['uname'] . " has been banned.";
				else {$respfail=1; $response="Failed to ban " . $_POST['uname'] . ".";}
			break;
			case 'unbanusr':
				if($userlevel<3) header("Location: ./mod_deny.php");
				if($sql->toggle_ban_user($_POST['uname'],0,$username))
					$response = $_POST['uname'] . " has been unbanned.";
				else {$respfail=1; $response="Failed to unban " . $_POST['uname'] . ".";}
			break;
			case 'create':
				if($userlevel<3) header("Location: ./mod_deny.php");
				if(!preg_match('~^[A-Za-z0-9\.\_\-][A-Za-z0-9\n\_\.\-]*[A-Za-z0-9\.\_\-]$~',$_POST['uname'])) {$respfail=1; $response="Invalid name formatting."; break;}
				if($sql->create_account($_POST['uname'],$_POST['forum'],$_POST['perm'],$username))
				{
					$response = "Account " . $_POST['uname'] . " has been created.";
				}
				else {$respfail=1; $response="Failed to create account " . $_POST['uname'] . ".";}
			break;
			case 'qcreate':
				if($userlevel<4) header("Location: ./mod_deny.php");
				if(!preg_match('~^[A-Za-z0-9\.\_\-][A-Za-z0-9\n\_\.\-]*[A-Za-z0-9\.\_\-]$~',$_POST['uname'])) {$respfail=1; $response="Invalid name formatting."; break;}
				$xpass=trim($_POST['pwd']);
				if($xpass=='') {$respfail=1; $response="No password set."; break;}
				if($sql->qcreate_account($_POST['uname'],$_POST['forum'],$xpass,$_POST['perm'],$username))
					$response = "Account " . $_POST['uname'] . " has been created.";
				else {$respfail=1; $response="Failed to create account " . $_POST['uname'] . ".";}
			break;
			case 'alias':
				if($userlevel<4) header("Location: ./mod_deny.php");
				$xname=$_POST['fname'];
				$xalias=$_POST['falias'];
				if($sql->set_user_alias($xname,$xalias))
					$response = $xname."'s forum alias set to ".$xalias.".";
				else {$respfail=1; $response="Failed to edit ".$xname."'s alias.";}
			break;
			case 'rst':
				if($userlevel<3) header("Location: ./mod_deny.php");
				if($sql->reset_pw($_POST['fname']))
					$response = $_POST['fname'] . "'s password has been reset.";
				else {$respfail=1; $response="Failed to reset ".$_POST['fname']."'s password.";}
			break;
			case 'promo':
				if($userlevel<3) header("Location: ./mod_deny.php");
				$name = $_POST['fname'];
				$prev = $_POST['status'];
				$new = $_POST['setto'];
				if($sql->set_acctype($name,$new,$username))
				{
					if($new>$prev) $response=$name." has been promoted.";
					else $response=$name." has been demoted.";
				}
				else
				{
					$respfail=1;
					if($new<$prev) $response="Failed to demote".$name.".";
					else $response="Failed to promote".$name.".";
				}
			break;
			case 'mkmod':
				if($userlevel<4) header("Location: ./mod_deny.php");
				$mname=$_POST['fname'];
				$module=$_POST['ftype'];
				if($sql->set_mod_rights($mname,$module,true,$username))
					$response = $mname." is now mod for ".$module.".";
				else
				{
					$respfail=1;
					$response="Failed to make ".$mname." a mod.";
				}
			break;
			case 'rmmod':
				if($userlevel<4) header("Location: ./mod_deny.php");
				$tmp=explode(",",$_POST['fname']);
				if($sql->set_mod_rights($tmp[0],$tmp[1],false,$username))
					$response = "Removed ".$tmp[0]."'s mod rights.";
				else
				{
					$respfail=1;
					$response="Failed to remove ".$tmp[0]."'s mod rights.";
				}
			break;
		}
	}
	
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	switch($view)
	{
		case 'listall':
			if($userlevel<3) header("Location: ./mod_deny.php"); 
			$pagetitle = "Wartool - manage accounts - list all accounts";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - list all accounts";
			$centerbox=1;
			if($allaccs = $sql->get_account_list())
			{
				require("inc_head.php");
				$output .= "<a href='./mod_umgr.php'>back</a>\n<br><br>\n";
				$output .= "<table>\n<tr>\n<th colspan='5'>USER</th>\n<th colspan='5'>MAIN CHAR + SDer</th>\n</tr>\n";
				$output .= "<tr>\n<th>name</th>\n<th>avail.</th>\n<th>phone</th>\n<th>msn</th>\n<th>banned</th>\n<th>admin</th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>rdy</th>\n<th>SDer</th>\n</tr>\n";
				foreach($allaccs as $key => $val)
				{
					if($val['isadmin']==1) $colorclass=" class=nokill";
					elseif($val['valid']!==null) $colorclass=" class='explimit'";
					elseif($val['banned']==1) $colorclass=" class='killhp'";
					else $colorclass='';
					$phone = ($val['phone']!==null)?$val['phone']:'';
					$msn = ($val['msn']!==null)?$val['msn']:'';
					$isadm = ($val['isadmin']==1)?"<img src='./img/stat_norm.gif'>":"<img src='./img/stat_na.gif'>";
					$isavail = ($val['cur']==1&&$val['na']==0)?"<img src='./img/stat_norm.gif'>":"<img src='./img/stat_na.gif'>";
					$banned = ($val['banned']==1)?"<span style='color:#FF0000; font-weight:bold;'>Y</span>":"<span style='color:#00BB00;font-weight:bold;'>N</span>";
					$hassder = ($val['sder']!==null)?"<img src='./img/stat_norm.gif'>":"<img src='./img/stat_na.gif'>";
					$mainname = ($val['mainname']!==null)?$val['mainname']:'';
					$mainlevel = ($val['mainlevel']!==null)?$val['mainlevel']:'';
					$mainvoc = ($val['mainvoc']!==null)?$val['mainvoc']:'';
					$rdy = ($val['mainrdy']!==null)?(($val['mainrdy']==1)?"<img src='./img/stat_norm.gif'>":"<img src='./img/stat_na.gif'>"):'';
					$output .= "<tr$colorclass align='left'>\n<td>".$val['cname']."</td>\n<td align='center'>$isavail</td>\n<td>$phone</td>\n<td>$msn</td>\n<td align='center'>$banned</td>\n";
					$output .= "<td align='center'>$isadm</td><td align='right'>$mainname</td>\n<td align='right'>$mainlevel</td>\n<td>$mainvoc</td>\n<td align='center'>$rdy</td>\n<td align='center'>$hassder</td>\n</tr>\n";
				}
				$output .= "</table>\n<br><br><br>\n<strong>Color Legend:</strong>\n<br><span class='nokill'>&nbsp;Admin&nbsp;</span>,\n <span class='killhp'>&nbsp;Banned&nbsp;</span>,\n ";
				$output .= "<span class='explimit'>&nbsp;not activated yet&nbsp;</span>.\n";
			}
			else
			{
				$respfail=1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
			}			
		break;
		
		case 'create':
			if($userlevel<3) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - manage accounts - create account";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - create account";
			$centerbox=1;
			require("inc_head.php");
			$output .= "<form action='./mod_umgr.php?view=pending' method='post'>\n<table style='font-weight:bold;'>\n<tr>\n<th colspan='2'>CREATE ACCOUNT</th>\n</tr>\n<tr>\n<th colspan='2'>&nbsp;</th>\n</tr>\n";
			$output .= "<tr>\n<td align='right'>Account name </td>\n<td><input type='text' name='uname' maxlength='30'></td>\n</tr>\n";
			$output .= "<tr>\n<td align='right' valign='top'>Forum alias <br><span style='font-weight:normal;font-size:12px;'>(leave blank if none) </span></td>\n<td valign='top'><input type='text' name='forum'></td>\n</tr>\n";
			$output .= "<tr>\n<td align='right'>Permissions </td>\n<td>\n<select size='1' name='perm'>\n<option value='0'>Guest User\n<option value='1'>Limited User\n<option value='2' SELECTED>Full User\n</td>\n</tr>\n";
			$output .= "<tr>\n<td colspan='2' align='center'>\n<input type='hidden' name='act' value='create'><input type='submit' value=' create account '>\n</tr>\n";
			$output .= "<tr>\n<td colspan='2'>&nbsp;</td></tr>\n<tr><td colspan='2'>&nbsp;</td></tr>\n<tr>\n<td colspan='2' align='center'><strong>Help</strong></td></tr>\n";
			$output .= "<tr style=font-weight:normal;'>\n<td align='right'>Account name: </td>\n<td>Can only contain A-Z a-z 0-9 _ . and -</td>\n</tr>\n";
			$output .= "<tr style=font-weight:normal;' valign='top'>\n<td align='right'>Forum alias: </td>\n<td><b>IMPORTANT!!</b><br>If the user you are making an account for<br>\n";
			$output .= "has a forum account also, put the correct<br>\nname here. (mind caps and spelling)<br>\n(<i>check this yourself,<br>\nthe user shouldn't know about it</i>)</td>\n</tr>\n";
			$output .= "<tr style=font-weight:normal;' valign='top'>\n<td align='right'>Permissions: </td>\n<td>Default is &quot;Full User&quot;.<br>\nView <a href='./mod_help.php'>help</a> for info on limited and guest accounts.</td>\n</tr>\n";
			$output .= "</table>\n</form>\n";
		break;
		
		case 'qcreate':
			if($userlevel<4) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - manage accounts - create account (adm)";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - create account (adm)";
			$centerbox=1;
			require("inc_head.php");
			$output .= "<form action='./mod_umgr.php' method='post'>\n<table style='font-weight:bold;'>\n<tr>\n<th colspan='2'>CREATE ACCOUNT</th>\n</tr>\n<tr>\n<th colspan='2'>&nbsp;</th>\n</tr>\n";
			$output .= "<tr>\n<td align='right'>Account name </td>\n<td><input type='text' name='uname' maxlength='30'></td>\n</tr>\n";
			$output .= "<tr>\n<td align='right' valign='top'>Forum alias <br><span style='font-weight:normal;font-size:12px;'>(leave blank if none) </span></td>\n<td valign='top'><input type='text' name='forum'></td>\n</tr>\n";
			$output .= "<tr>\n<td align='right'>Password </td>\n<td><input type='text' name='pwd'></td>\n</tr>\n";
			$output .= "<tr>\n<td align='right'>Permissions </td>\n<td>\n<select size='1' name='perm'>\n<option value='0'>Guest User\n<option value='1'>Limited User\n<option value='2' SELECTED>Full User\n</td>\n</tr>\n";
			$output .= "<tr>\n<td colspan='2' align='center'>\n<input type='hidden' name='act' value='qcreate'><input type='submit' value=' create account '>\n</tr>\n";
			$output .= "<tr>\n<td colspan='2'>&nbsp;</td>\n</tr>\n<tr>\n<td colspan='2'>&nbsp;</td>\n</tr>\n";
			$output .= "<tr>\n<td colspan='2' style='font-weight:normal;'><strong>Note:</strong><br>\nCreating an account this way allows less secure passwords<br>\nand does not force the user to specify his msn/phone/mainchar<br>\n";
			$output .= "which we want for statistics.<br>\nDo it the normal way if possible.</td>\n</tr>\n</table>\n</form>";
		break;
		
		case 'pending':
			if($userlevel<3) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - manage accounts - list pending accounts";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - list all accounts";
			$centerbox=1;
			$list = $sql->get_pending_accounts();
			if($list==0||$list==true)
			{
				require("inc_head.php");
				$output .= "<strong>LIST OF PENDING ACCOUNTS</strong>\n<br>\n<i>(not activated by the user yet)</i>\n<br><br>\n";
				
				if($list==0)
					$output .= "There are no pending accounts.\n";
				else
				{
					$output .= "<table><tr><th>activation link with <strong>bolded name</b></th><tr>";
					foreach($list as $key => $val)
					$output .= "<tr><td>http://wartool.tnuc.org/activate.php?v=".$val['validate']."&amp;u=<strong>".urlencode($val['name'])."</strong></td></tr>";
				}
			}
			else
			{
				$respfail = 1;
				$response = "Database inaccessible";
				require("inc_head.php");
				$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
			}
		break;
		
		case 'reset':
			if($userlevel<3) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - manage accounts - reset password";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - reset password";
			$centerbox=1;
			if($userlevel==4) $names=$sql->get_all_names($username); else $names=$sql->get_all_non_admins($username);
			if($names)
			{
				require("inc_head.php");
				$output .= "<strong>RESET PASSWORD</strong>\n<br><br><br>\n";
				$output .= "<form action='./mod_umgr.php?view=pending' method='post'>\n<select size='1' name='fname'>\n";
				$q=true;
				foreach($names as $key => $val) if($q) {$output .= "<option value='".$val."' SELECTED>".$val."\n"; $q=false;} else $output .= "<option value='".$val."'>".$val."\n";
				$output .= "</select>\n<input type='hidden' name='act' value='rst'>\n<input type='submit' value=' reset password '>\n</form>\n";
				$output .= "<br><br>\n<strong>Note:</strong><br>\nResetting a user's password will get set his account back in to validation mode.<br>\n";
				$output .= "He will not be able to log in or show up in any of our user-based statistics until reactivation.<br>\n";
				$output .= "After resetting the password you will be forwarded to the list of pending accounts.<br>\n";
				$output .= "Give the corresponding link to the user for him to reactivate the account.";
			}
			else
				{
					$respfail=1;
					$response = "Database inaccessible.";
					require("inc_head.php");
					$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
				}
		break;
		
		case 'promote':
			if($userlevel<3) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - manage accounts - promote/demote users";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - promote/demote users";
			$centerbox=1;
			if($list=$sql->get_non_admins_by_acctype())
			{
				require("inc_head.php");
				$output .= "<strong>PROMOTE/DEMOTE USERS</strong>\n<br><br><br>\n<table width='100%' style='text-align:center;'>\n<tr>\n<td valign='top'>\n<strong>GUEST ACCOUNTS</strong>\n<br><br>\n";
				if(count($list[0])>0)
				{
					$output .= "<form action='./mod_umgr.php?view=promote' method='post'>\n";
					$output .= "<select size='1' name='fname'>\n";
					$q=true;
					foreach($list[0] as $key => $val) if($q) {$output .= "<option value='".$val."' SELECTED>".$val."\n"; $q=false;} else $output .= "<option value='".$val."'>".$val."\n";
					$output .= "</select>\n<br>\n<select size='1' name='setto'>\n<option value='1' SELECTED>Promote to LIMITED ACCOUNT\n<option value='2'>Promote to FULL ACCOUNT\n</select>\n";
					$output .= "<input type='hidden' name='act' value='promo'><input type='hidden' name='status' value='0'>\n<br><br>\n<input type='submit' value=' go '>\n</form>\n";
				}
				else $output .= "There are no guest accounts.";
				$output .= "</td>\n<td valign='top'>\n<strong>LIMITED ACCOUNTS</strong>\n<br><br>\n";
				if(count($list[1])>0)
				{
					$output .= "<form action='./mod_umgr.php?view=promote' method='post'>\n";
					$output .= "<select size='1' name='fname'>\n";
					$q=true;
					foreach($list[1] as $key => $val) if($q) {$output .= "<option value='".$val."' SELECTED>".$val."\n"; $q=false;} else $output .= "<option value='".$val."'>".$val."\n";
					$output .= "</select>\n<br>\n<select size='1' name='setto'>\n<option value='0' SELECTED>Demote to GUEST ACCOUNT\n<option value='2'>Promote to FULL ACCOUNT\n</select>\n";
					$output .= "<input type='hidden' name='act' value='promo'><input type='hidden' name='status' value='1'>\n<br><br>\n<input type='submit' value=' go '>\n</form>\n";
				}
				else $output .= "There are no limited accounts.";
				$output .= "</td>\n<td valign='top'>\n<strong>FULL ACCOUNTS</strong>\n<br><br>\n";
				if(count($list[2])>0)
				{
					$output .= "<form action='./mod_umgr.php?view=promote' method='post'>\n";
					$output .= "<select size='1' name='fname'>\n";
					$q=true;
					foreach($list[2] as $key => $val) if($q) {$output .= "<option value='".$val."' SELECTED>".$val."\n"; $q=false;} else $output .= "<option value='".$val."'>".$val."\n";
					$output .= "</select>\n<br>\n<select size='1' name='setto'>\n<option value='0' SELECTED>Demote to GUEST ACCOUNT\n<option value='1'>Demote to LIMITED ACCOUNT\n</select>\n";
					$output .= "<input type='hidden' name='act' value='promo'><input type='hidden' name='status' value='2'>\n<br><br>\n<input type='submit' value=' go '>\n</form>\n";
				}
				else $output .= "There are no full accounts.";
				$output .= "</td>\n</tr>\n</table>\n<br><br>\n";
			}
			else
			{
				$respfail=1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
			}
			
		break;
		
		case 'edit_alias':
			if($userlevel<4) header("Location: ./mod_deny.php");
			if(!isset($_POST['tname']))
			{
				$pagetitle = "Wartool - manage accounts - edit forum alias";
				$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - edit forum alias";
				$centerbox=1;
				if($allaccs=$sql->get_all_names(false))
				{
					require("inc_head.php");
					$output .= "<strong>EDIT FORUM ALIAS</strong>\n<br><br>\n";
					$output .= "<form action='./mod_umgr.php?view=edit_alias' method='post'>";
					$output .= "<select size='1' name='tname'>\n";
					$q=true;
					foreach($allaccs as $key => $val) if($q) {$output.="<option value='".$val."' SELECTED>".$val."\n"; $q=false;} else $output.="<option value='".$val."'>".$val."\n";
					$output .= "</select>\n&nbsp;\n<input type='submit' value=' edit this user&apos;s alias '></form>\n";
				}
				else
				{
					$respfail=1;
					$response = "Database inaccessible.";
					require("inc_head.php");
					$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
				}
			}
			else
			{
				$tarname=$_POST['tname'];
				$alias=$sql->get_user_alias($tarname);
				$pagetitle = "Wartool - manage accounts - edit forum alias - user: ".$tarname;
				$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - <a href='./mod_umgr.php?view=edit_alias'>edit forum alias</a> - user:".$tarname;
				if($alias!==-1)
				{
					$centerbox=1;
					require("inc_head.php");
					$output .= "<strong>EDIT FORUM ALIAS</strong>\n<br><br>\n";
					$output .= "<form action='./mod_umgr.php' method='post'>\n".$tarname."'s alias:\n<input name='falias' type='text'".(($alias==='null')?'':" value=\"".$alias."\"").">\n";
					$output .= "&nbsp;\n<input type='hidden' name='fname' value='".$tarname."'><input type='hidden' name='act' value='alias'><input type='submit' value=' apply new alias '>\n</form>\n<br><br><br>\n<strong>Warning:</strong>\n";
					$output .= "<br>\nThis can cause severe damage in the IP database,<br>\nas DB entries may be renamed accordingly.<br>\n<strong>Proceed only if you know what you are doing.</strong>\n";
				}
				else
				{
					$respfail=1;
					$response = "Database inaccessible.";
					require("inc_head.php");
					$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
				}
			}
		break;
		
		case 'delete':
			if($userlevel<4) header("Location: ./mod_deny.php");
			if(isset($_POST['delname']))
			{
				$pagetitle = "Wartool - manage accounts - delete account - " . $_POST['delname'];
				$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - <a href='./mod_umgr.php?view=delete'>delete account</a> - user: " . $_POST['delname'];
				$centerbox=1;
				require("inc_head.php");
				$output .= "<strong>DELETE USER</strong>\n<br><br>\n";
				$output .= "<strong>Are you sure you want to delete user " . $_POST['delname'] . "?</strong>\n<br>\n(this decision is irreversible)<br><br>\n";
				$output .= "<table border='0'>\n<tr>\n<td><form action='./mod_umgr.php' method='post'>\n<input type='submit' value=' no, cancel '>\n</form>\n";
				$output .= "</td><td>\n<form action='./mod_umgr.php' method='post'>\n<input type='hidden' name='act' value='delacc'>\n";
				$output .= "<input type='hidden' name='delname' value='". $_POST['delname'] . "'>\n<input type='submit' value=' yes, delete '>\n</form>\n</td></tr>\n</table>\n";
				
			}
			else
			{
				$pagetitle = "Wartool - manage accounts - delete account";
				$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - delete account";
				$centerbox=1;
				if($namelist = $sql->get_all_non_admins(false))
				{
					require("inc_head.php");
					$output .= "<strong>DELETE USER</strong>\n<br><br>\n";
					$output .= "<form action='./mod_umgr.php?view=delete' method='post'>\n<select name='delname' size='1'>\n";
					$output .= "<option value='".$namelist[0]."' SELECTED>".$namelist[0]."<br>\n";
					for($i=1;$i<count($namelist);$i+=1)
						$output .= "<option value='".$namelist[$i]."'>".$namelist[$i]."<br>\n";
					$output .= "</select>\n<input type='submit' value=' DELETE this user '>\n</form>\n";
				}
				else
				{
					$respfail = 1;
					$response = "Database inaccessible";
					require("inc_head.php");
					$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
				}
			}
			
		break;
		
		case 'banlist':
			if($userlevel<3) header("Location: ./mod_deny.php"); 
			$pagetitle = "Wartool - manage accounts - ban/unban account";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - ban/unban account";
			$centerbox=1;
			$banned = array();
			$notbanned = array();
			if($allaccs = $sql->get_all_user_ban_status())
			{
				require("inc_head.php");
				foreach($allaccs as $ind => $user)
					if($user['banned']==1) $banned[]=$user['name']; else $notbanned[]=$user['name'];
				$output .= "<table width='90%' border='0' align='center'>\n<tr>\n<td valign='baseline'>\n";
				$output .= "<strong>BAN USERS</strong>\n<br><br>\n";
				if(count($notbanned)>0)
				{
					$output .= "<form action='./mod_umgr.php?view=banlist' method='post'>\n";
					$output .= "<input type='hidden' name='act' value='banusr'><select size='1' name='uname'>\n";
					$output .= "<option value='" . $notbanned[0] . "' SELECTED>" . $notbanned[0] . "\n";
					if(count($notbanned)>1)
						for($i=1;$i<count($notbanned);$i+=1)
							$output .= "<option value='" . $notbanned[$i] . "'>" . $notbanned[$i] . "\n";
					$output .= "</select>\n";
					$output .= " <input type='submit' value=' ban this user '>\n</form>\n";
					$output .= "<strong>Note:</strong>\n<br>\nAdmins won't show up in the list.\n<br>\n";
					$output .= "In order to ban an admin, first remove his admin status.\n";
				}
				else $output .= "There are no users that are not banned.\n";
				$output .= "</td><td valign='baseline'>\n";
				$output .= "<strong>UNBAN USERS</strong>\n<br><br>\n";
				if(count($banned)>0)
				{
					$output .= "<form action='./mod_umgr.php?view=banlist' method='post'>\n";
					$output .= "<input type='hidden' name='act' value='unbanusr'><select size='1' name='uname'>\n";
					$output .= "<option value='" . $banned[0] . "' SELECTED>" . $banned[0] . "\n";
					if(count($banned)>1)
						for($i=1;$i<count($banned);$i+=1)
							$output .= "<option value='" . $banned[$i] . "'>" . $banned[$i] . "\n";
					$output .= "</select>\n";
					$output .= " <input type='submit' value=' unban this user '>\n</form>\n";
				}
				else $output .= "There are no banned users.\n";
				$output .= "</td></tr></table>";
			}
			else
			{
				$respfail=1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
			}				
		break;
		
		case 'mods':
			if($userlevel<4) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - manage accounts - moderators";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_umgr.php'>manage accounts</a> - moderators";
			$centerbox=1;
			$data=$sql->get_mod_data();
			if(isset($data[2]))
			{
				require("inc_head.php");
				$output .= "<strong>MODERATORS</strong>\n<br><br><br>\n<table width='100%' style='text-align:center;'>\n<tr>\n<td valign='top'>\n<strong>APPOINT MODERATORS</strong>\n<br><br>\n";
				if(count($data[1])>0)
				{
					$output .= "<form action='./mod_umgr.php?view=mods' method='post'>\n<input type='hidden' name='act' value='mkmod'>\n<select size='1' name='fname'>\n";
					for($j=0;$j<count($data[1]);$j+=1)
						$output .= "<option value='".$data[1][$j]."'".(($j===0)?' SELECTED':'').">".$data[1][$j]."\n";
					$output .= "</select>\n<select size='1' name='ftype'>\n";
					$output .= "<option value='availability' SELECTED>phone list\n";
					$output .= "<option value='charlist'>enemy chars\n";
					$output .= "<option value='ourchars'>alliance chars\n";
					$output .= "<option value='stats'>statistics\n";
					$output .= "<option value='traps'>(boat) traps\n";
					$output .= "<option value='usermgr'>account manager\n";
					$output .= "<option value='refresh'>update chars\n";
					$output .= "<option value='vent'>ventrilo\n";
					$output .= "</select>\n<input type='submit' value=' give mod rights '>\n</form>\n";
				}
				else $output .= "No potential moderators found.";
				$output .= "</td>\n<td valign='top'>\n<strong>REMOVE MODERATORS</strong>\n<br><br>\n";
				if(count($data[0][0])>0)
				{
					$output .= "<form action='./mod_umgr.php?view=mods' method='post'>\n<input type='hidden' name='act' value='rmmod'>\n<select size='1' name='fname'>\n";
					for($i=0;$i<count($data[0][0]);$i+=1)
					{
						switch($data[0][1][$i])
						{
							case 'availability': $o="phone list"; break;
							case 'charlist': $o="enemy chars"; break;
							case 'ourchars': $o="alliance chars"; break;
							case 'stats': $o="statistics"; break;
							case 'traps': $o="(boat) traps"; break;
							case 'usermgr': $o="account manager"; break;
							case 'refresh': $o="phone list"; break;
							case 'vent': $o="ventrilo"; break;
							case 'finances': $o='finances'; break;
							default: $o='#ERROR#'; break; 
						}
						$output .= "<option value='".$data[0][0][$i].",".$data[0][1][$i]."'".(($i===0)?' SELECTED':'').">".$data[0][0][$i]." (".$o.")\n";
					}
					$output .= "</select>\n<input type='submit' value=' remove mod rights '>\n</form>\n";
				}
				else $output .= "There are no moderators.\n";
				$output .= "</td>\n</tr>\n</table>\n<br><br><br>\n<strong>Note:</strong>\n<br>\nBeing a 'moderator' does not necessarily involve having moderative ";
				$output .= "or administrative rights.<br>\nThe <b>vent</b> module can only be seen if you have mod rights there or are an admin.<br>\n";
				$output .= "On the <b>statistics(+info)</b> module mods can see additional information (make leadership mod here).<br>\n";
				$output .= "For more information on moderators see the <b>help</b> module.<br>\n";
			}
			else
			{
				$respfail=1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE FAILURE</strong>\n<br><br>\nClick <a href='./'>here</a> to get back to the main menu.";
			}	
			
		break;
		
		default:
			if($userlevel<3) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - manage accounts";
			$navpath = "<a href='./'>main menu</a> - manage accounts";
			$centerbox = 1;
			require("inc_head.php");
			$output .= "<a href='./'>back</a>\n<br><br>\n";
			$output .= "<a href='./mod_umgr.php?view=listall'>list all accounts</a>\n<br>\n";
			$output .= "<a href='./mod_umgr.php?view=create'>create account</a>\n<br>\n";
			if($userlevel==4) $output .= "<a href='./mod_umgr.php?view=qcreate' class='admfkt'>create account (skip verification)</a>\n<br>\n";
			$output .= "<a href='./mod_umgr.php?view=pending'>list pending accounts</a>\n<br>\n";
			$output .= "<a href='./mod_umgr.php?view=reset'>reset password</a>\n<br>\n";
			$output .= "<a href='./mod_umgr.php?view=promote'>promote/demote user</a>\n<br>\n";
			if($userlevel==4) $output .= "<a href='./mod_umgr.php?view=edit_alias' class='admfkt'>edit forum alias</a>\n<br>\n";
			if($userlevel==4) $output .= "<a href='./mod_umgr.php?view=delete' class='admfkt'>delete account</a>\n<br>\n";
			$output .= "<a href='./mod_umgr.php?view=banlist'>ban/unban account</a>\n<br>\n";
			if($userlevel==4) $output .= "<a href='./mod_umgr.php?view=mods' class='admfkt'>moderators</a>\n<br>\n";
		break;
	}
	
	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}
?>