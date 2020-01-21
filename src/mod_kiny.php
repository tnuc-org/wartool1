<?php
require("Wsql.php");
require("Wsql_kny.php");
require("CookiMgr.php");
$sql = new Wsql_kny();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";
$perm_name = "acctype";

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
	if($userlevel==0) header("Location: ./mod_deny.php");

	$pagetitle = "Wartool - hall of shame";
	$navpath = "<a href='./'>main menu</a> - hall of shame";
	$centerbox=1;
	if($allnames = $sql->get_shame())
	{
		require("inc_head.php");
		$colorcodes=array(1=>'nokill',2=>'explimit',3=>'killhp');
		$output .= "<a href='./'>back</a>\n<br><br>\n<table width='90%' border='0'>\n<tr>\n<th width='33%'>no sd character</th>\n";
		$output .= "<th width='33%'>no yalahar gates on main</th>\n<th width='33%'>no yalahar access on sd char</th>\n</tr>\n";
		$output .= "<tr>\n<td valign='top' align='center' style='font-weight:bold;'>\n";
		for($i=0;$i<count($allnames);$i+=1)
			if($allnames[$i][1]==1)
				$output .= "<span class='".$colorcodes[$allnames[$i][4]]."'>&nbsp;".$allnames[$i][0]."&nbsp;</span><br>\n";
		$output .= "</td>\n<td valign='top' align='center' style='font-weight:bold;'>\n";
		for($i=0;$i<count($allnames);$i+=1)
			if($allnames[$i][2]==1)
				$output .= "<span class='".$colorcodes[$allnames[$i][4]]."'>&nbsp;".$allnames[$i][0]."&nbsp;</span><br>\n";
		$output .= "</td>\n<td valign='top' align='center' style='font-weight:bold;'>\n";
		for($i=0;$i<count($allnames);$i+=1)
			if($allnames[$i][3]==1)
				$output .= "<span class='".$colorcodes[$allnames[$i][4]]."'>&nbsp;".$allnames[$i][0]."&nbsp;</span><br>\n";
		$output .= "</td>\n</tr>\n</table>\n<br><br>\n<strong>Obvious Color Legend:</strong>\n<br>\n<span class='nokill'>&nbsp;single shame (you could do better)&nbsp;\n<br>\n";
		$output .= "<span class='explimit'>&nbsp;double shame (get your shit together)&nbsp;\n<br>\n<span class='killhp'>&nbsp;triple shame (you must be Kiny)&nbsp;\n";
		//$output .= "<br>\n<span class='blacklist'>&nbsp;ultimate failure (you ARE Kiny)&nbsp;\n";
	}
	else
	{
		$respfail = 1;
		$response = "Database inaccessible.";
		require("inc_head.php");
		$output .= "<strong>DATABASE ERROR</strong><br>Click <a href='./'>here</a> to get back to the main menu.";
	}

		
	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}
?>