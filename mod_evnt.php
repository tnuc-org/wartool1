<?php
require("Wsql.php");
require("Wsql_evt.php");
require("CookiMgr.php");
$sql = new Wsql_evt();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";

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
	$userlevel = ($accdata['isadmin']==1)?4:(($accdata['acctype']==0)?0:2);

	$from=(isset($_GET['page']))?($_GET['page']-1)*100:false;
	$page=(isset($_GET['page']))?$_GET['page']:1;
	$pagetitle = "Wartool - event log";
	$navpath = "<a href='./'>main menu</a> - event log";
	require("inc_head.php");
	$output .= "<a href='./'>back</a>\n<br>\n";
	if($userlevel==4)
		$eventarr=$sql->get_all_admin_events($from);
	elseif($userlevel>0)
		$eventarr=$sql->get_all_user_events($from,$username);
	else
		$eventarr=$sql->get_all_guest_events($from,$username);
	if($eventarr)
	{
		$xprev=($page>1)?"<a href='./mod_evnt.php?page=".($page-1)."'>newer</a>":"<strike>newer</strike>";
		$xnext=(count($eventarr)==100)?"<a href='./mod_evnt.php?page=".($page+1)."'>older</a>":"<strike>older</strike>";
		$output .= $xprev . " :: " . $xnext . "\n<br><br>\n";
		$eventarr = $sql->process_events($eventarr);
		for($i=0;$i<count($eventarr[0]);$i+=1)
			$output .= $eventarr[0][$i] . " :: " . $eventarr[1][$i] . "<br>\n";
	}
	else $output.= "<strong>EVENT LOG EMPTY.</strong>";

	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}
?>