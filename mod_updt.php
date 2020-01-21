<?php
require("Wsql.php");
require("Wsql_ene.php");
require("CookiMgr.php");
$sql = new Wsql_ene();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";
$perm_name = "perm_refresh";

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
	if($userlevel<3) header("Location: ./mod_deny.php");
	{
		$pagetitle = "Wartool - update characters";
		$navpath = "<a href='./'>main menu</a> - update characters";
		$javascript = "<script src=\"ajax.js\"></script><script>var gBusy=0;</script>";
		$centerbox = 1;
		require("inc_head.php");
		$output .= "<a href='./'>back</a>\n<br><br><br>\n";
		$output .= "<div onclick='forceUpdate(0);' class='fklnk'>update all characters</div><br>";
		$output .= "<div onclick='forceUpdate(1);' class='fklnk'>update all mains</div>";
		$output .= "<div onclick='forceUpdate(2);' class='fklnk'>update enemy mains</div>";
		$output .= "<div onclick='forceUpdate(3);' class='fklnk'>update allied mains</div><br>";
		$output .= "<div onclick='forceUpdate(4);' class='fklnk'>update enemy seconds</div>";
		$output .= "<div onclick='forceUpdate(5);' class='fklnk'>update allied seconds</div>";
		$output .= "<div style='margin-top:30px;'><strong>Result:</strong>";
		$output .= "<div id='result' style='border:1px solid black;'>--</div></div>";
		
	}
	
	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}
?>