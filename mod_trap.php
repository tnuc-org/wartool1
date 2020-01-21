<?php
require("Wsql.php");
require("Wsql_trp.php");
require("CookiMgr.php");
$sql = new Wsql_trp();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";
$perm_name = "perm_traps";

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
	if($userlevel<2) header("Location: ./mod_deny.php");
	
	if(isset($_POST['act']))
	{
		switch($_POST['act'])
		{
			case '':
				;
			break;
		}
	}
	
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	switch($view)
	{
		case 'horde':
			;
		break;
		
		case 'horde_mod':
			;
		break;
		
		case 'horde':
			;
		break;
		
		case 'edit':
			;
		break;
		
		case 'new':
			;
		break;
		
		default:
			$pagetitle = "Wartool - traps";
			$navpath = "<a href='./'>main menu</a> - traps";
			$centerbox = 1;
			$javascript = "<script src=\"ajax.js\"></script>";
			require("inc_head.php");
			$output .= "<br><br>\n<strong>UNDER CONSTRUCTION</strong>\n<br><br>Click <a href='./'>here</a> to go back to the main menu.";
			
		break;
	}
	
	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}
?>