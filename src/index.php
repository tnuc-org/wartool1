<?php
require("Wsql.php");
require("Wsql_log.php");
require("CookiMgr.php");
$sql = new Wsql_log();
$cmgr = new CookiMgr();

$accdata = false;
$username = false;
$loggedin = false;
$output = "";

if(isset($_GET['user'])&&isset($_GET['override']))
{
	$accdata = $sql->login_auth($_GET['user'],$_GET['override']);
	if($accdata)
	{
		$sql->token_create($_GET['user'],md5($_GET['override']));
		$cmgr->create($_GET['user'],md5($_GET['override']));
		$username = $accdata['name'];
		$loggedin = true;
	}
	else
	{
		$respfail=1;
		$response="Account name or password incorrect.";
		$sql->report_wrong_pw($_GET['user']);
	}
}
elseif(isset($_POST['user'])&&isset($_POST['pass']))
{
	$accdata = $sql->login_auth($_POST['user'],$_POST['pass']);
	if($accdata)
	{
		$sql->token_create($_POST['user'],md5($_POST['pass']));
		$cmgr->create($_POST['user'],md5($_POST['pass']));
		$username = $accdata['name'];
		$loggedin = true;
	}
	else
	{
		$respfail=1;
		$response="Account name or password incorrect.";
		$sql->report_wrong_pw($_POST['user']);
	}
}
elseif($cmgr->allset())
{
	$accdata = $sql->token_auth($cmgr->get_user(),$cmgr->get_hash());
	if($accdata)
	{
		$username = $accdata['name'];
		$loggedin = true;
	}
	else
	{
		$cmgr = new CookiMgr();
		$cmgr->delete();
	}
}

if($loggedin)
{
/*********************
**     MAIN MENU    **
*********************/

$pagetitle = "Wartool - main menu";
require("inc_head.php");
if($accdata['acctype']>0) $output .= "<a href='./mod_avai.php'>phone list</a>\n<br><br>\n";
$output .= "<a href='./mod_enem.php'>enemy characters</a>".(($accdata['isadmin']==1||$accdata['perm_charlist']==3)?"\n(<a href='./mod_enem.php?view=mod' class='admfkt'>mod cp</a>)":"")."\n<br>\n";
if($accdata['acctype']>0) $output .= "<a href='./mod_addc.php'>add enemy character</a>\n<br>\n";
if($accdata['acctype']>1) $output .= "<a href='./mod_enem.php?view=magelist'>list of neutral mages</a>\n<br>\n";
if($accdata['isadmin']==1||$accdata['perm_refresh']==3) $output .= "<a href='./mod_updt.php' class='admfkt'>update characters</a>\n<br>\n";
if($accdata['isadmin']==1||$accdata['perm_refresh']==3) $output .= "<a href='./mod_vent.php' class='admfkt'>enemy ventrilo</a>\n<br>\n";
if($accdata['acctype']>0) $output .= "<br>\n<a href='./mod_ours.php'>alliance characters</a>".(($accdata['isadmin']==1||$accdata['perm_ourchars']==3)?"\n(<a href='mod_ours.php?view=mod' class='admfkt'>mod cp</a>)":"")."\n<br>\n";
//if($accdata['acctype']>1) $output .= "<a href='./mod_trap.php'>(boat) traps</a>\n<br>\n";
//if($accdata['acctype']>0) $output .= "<br>\n<a href='./mod_kiny.php'>hall of shame</a>\n<br>\n<a href='./mod_stat.php'>statistics &amp; information</a>\n<br>";
if($accdata['acctype']>0) $output .= "<br>\n<a href='./mod_kiny.php'>hall of shame</a>\n<br>";
$output.= "<br>\n<a href='./mod_evnt.php'>event log</a>\n<br>\n<a href='./mod_accm.php'>my account</a>\n<br>";
if($accdata['isadmin']==1||$accdata['perm_usermgr']==3) $output .= "\n<a href='./mod_umgr.php' class='admfkt'>manage accounts</a>\n<br>";
if($accdata['isadmin']==1) $output .= "\n<a href='./mod_admn.php' class='admfkt'>admin cp</a>\n<br>";
$output .= "<br>\n<a href='./mod_quit.php'>log out</a>";
}
else
{
/*********************
**   LOGIN SCREEN   **
*********************/
$pagetitle = "Wartool - login";
$username = "guest";
$navpath = "Please log in first.";
$centerbox=1;
$usr=(isset($_GET['user']))?htmlentities(stripslashes(urldecode($_GET['name']))):'';
require("inc_head.php");
/*$output.=<<<EOD

<br><br>
<strong>WARTOOL 2.0 beta</strong><br>
<strong>by Marcos &amp; Flo</strong><br><br>
<form action='./' method='post' name='wtool'>
<table style='padding:5px; border:1px solid black;'>
<tr>
<td align='right'><input type='text' name='user' maxlength='30' value="$usr"></td>
<td>username</td>
</tr>
<tr>
<td align='right'><input type='password' name='pass'></td>
<td>password</td>
</tr>
<tr>
<td colspan='2' align='center'><input type='submit' value=' log in '></td>
</tr>
</table>
</form>
<br><br>
<strong>And remember:</strong>
<br>
<strike>Respect is everything.</strike>
<br>
<strike>Night is not allied with anyone.</strike>
<br>
If you are using IE6, go fuck yourself,<br>
don't care if the layout is screwed up.
EOD; */
$output .= "<h1 style='margin-top:200px;color:#f00;font-size:48px;'><nobr>IT'S OKAY, BECAUSE JAMIE IS A BAD FATHER.</nobr></h1>
<p>Not okay, really... I mean, think of the children, but yeh...</p>";
}

$sql=null;
require("inc_foot.php");
echo $output;
?>