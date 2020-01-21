<?php
require("Wsql.php");
require("Wsql_vnt.php");
require("CookiMgr.php");
$sql = new Wsql_vnt();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";
$perm_name = "perm_vent";

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
	
	/*
	if(isset($_POST['act']))
	{
		switch($_POST['act'])
		{
			default:
				;
			break;
		}
	}
	*/
	
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	switch($view)
	{
		default:
			if($userlevel<3) $header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - enemy ventrilo";
			$navpath = "<a href='./'>main menu</a> - enemy ventrilo";
			$centerbox=1;
			require("inc_head.php");
			$output.="<a href='./'>back</a>\n<br><br><br>\n<strong>ENEMY VENT<br>UNDER CONSTRUCTION</strong>";
		break;
	}
	
	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}
?>