<?php
require("Wsql.php");
require("Wsql_log.php");
require("CookiMgr.php");
$sql = new Wsql_log();
$cmgr = new CookiMgr();

$loggedin = false;
$errormsg = null;
$output = "";

if($cmgr->allset())
{
	$accdata = $sql->token_auth($cmgr->get_user(),$cmgr->get_hash());
	if(!$accdata) {$sql = null; $par = "Location: ./?name=".$cmgr->get_user(); header($par);}
	else { $loggedin = true; $username = $accdata['name']; }
}
else header("Location: ./");


if($loggedin)
{
if(isset($_POST['new1'])&&isset($_POST['new2'])&&isset($_POST['old']))
{
	if($_POST['new1']!=""&&$_POST['new2']!=""&&$_POST['old']!="")
	{
		if(md5($_POST['old'])==$cmgr->get_hash())
		{
			if($_POST['new1']===$_POST['new2'])
			{
				if($_POST['old']!=$_POST['new1'])
				{
					if(strlen($_POST['new1'])>=10)
					{
						if(preg_match("~[0-9]~",$_POST['new1'])&&preg_match("~[A-Z]~",$_POST['new1'])&&preg_match("~[a-z]~",$_POST['new1']))
						{
							if($sql->set_pw($cmgr->get_user(),md5($_POST['new1']),$cmgr->get_hash()))
							{
								$cmgr->create($cmgr->get_user(),md5($_POST['new1']));
								header("Location: ./mod_accm.php");
							}
							else {$response="MySQL Error, password could not be changed."; $respfail=1;}
						}
						else {$response="Password is not secure enough."; $respfail=1;}
					}
					else {$response="New password is too short."; $respfail=1;}
				}
				else {$response="Old and new password are the same."; $respfail=1;}
			}
			else {$response="New passwords do not match."; $respfail=1;}
		}
		else {$response="Old password is incorrect."; $respfail=1;}
	}
}
$pagetitle = "Wartool - my account - change password";
$navpath = "<a href='./'>main menu</a> - <a href='./mod_accm.php'>my account</a> - change password";
$centerbox=1;
require("inc_head.php");

$output .= <<<EOD

<a href='./mod_accm.php'>back</a>
<br><br>
<strong>CHANGE PASSWORD</strong>
<br><br>
<form name='changepw' action='./mod_pass.php' method='post'>
<table><tr>
<td align='right'><input type='password' name='new1' maxlength='30'></td>
<td align='left'> new password</td>
</tr><tr>
<td align='right'><input type='password' name='new2' maxlength='30'></td>
<td align='left'> new password again</td>
</tr><tr>
<td colspan='2'>&nbsp;</td>
</tr><tr>
<td align='right'><input type='password' name='old' maxlength='30'></td>
<td align='left'> current password</td>
</tr><tr>
<td colspan='2' align='center'><input type='submit' value=' change password '></td>
</tr></table>
</form>
<br><br>
<strong>Note:</strong><br>
PWs must be at least 10 characters long and contain caps, lowercase and numbers.

EOD;

$sql=null;
require("inc_foot.php");
echo $output;
}
else {$sql=null; header("Location: ./");}
?>