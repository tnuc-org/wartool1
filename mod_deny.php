<?php
require("Wsql.php");
require("CookiMgr.php");
$sql = new Wsql();
$cmgr = new CookiMgr();

$loggedin=false;
if($cmgr->allset())
{
	$accdata = $sql->token_auth($cmgr->get_user(),$cmgr->get_hash());
	if(!$accdata) {$sql=null; $par="Location: ./?name=".$cmgr->get_user(); header($par);}
	else { $loggedin = true; $username = $accdata['name']; }
}
else header("Location: ./");

if($loggedin)
{
	$respfail=1;
	$response="Oh, do fuck off. <.<";
	$pagetitle = "Wartool - ACCESS DENIED";
	$navpath = "<a href='./'>main menu</a> - ACCESS DENIED";
	$centerbox=1;
	require("inc_head.php");
	$output .= "<span style='font-size:72px;color:#ff0000;font-weight:bold;'><br><br>ACCESS DENIED</span>\n<br>\n";
	$output .= "<span style='font-size:36px;color:#ff0000;font-weight:bold;'>You're not supposed to be here. Really.</span>\n<br><br>\n";
	$output .= "Click <a href='./'>here</a> to get back to the main menu.";
	require("inc_foot.php");

	$sql=null;
	echo $output;
}
else {$sql=null; header("Location: ./");}
?>