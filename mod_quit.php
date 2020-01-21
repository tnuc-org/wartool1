<?php
require("Wsql.php");
require("CookiMgr.php");
require("Wsql_log.php");
$cmgr = new CookiMgr();

if($cmgr->allset()) 
{
	$sql = new Wsql_log();
	$username=$cmgr->get_user();
	$sql->token_delete($username);
	$cmgr->delete();
	$sql=null;
}
$username = (isset($username))?$username:"Guest";
$navpath = "Please log in again first.";
$pagetitle = "Wartool - logout";
require("inc_head.php");
$output .= "Logged out.<br><br>Click <a href=\"./\">here</a> to login again.<br><br>";
require("inc_foot.php");

echo $output;

?>