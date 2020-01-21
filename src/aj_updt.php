<?php
require("Wsql.php");
require("Wsql_ajx.php");
require("CookiMgr.php");
$sql = new Wsql_ajx();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$perm_name = "perm_refresh";

if($cmgr->allset())
{
	$accdata = $sql->token_auth($cmgr->get_user(),$cmgr->get_hash());
	if(!$accdata) {$sql=null; echo "0"; exit;}
	$username=$accdata['name'];
	$userlevel=($accdata['isadmin']==1)?4:$accdata[$perm_name];
	if($userlevel>=3) $loggedin=true;
}
else {$sql=null; echo "0"; exit;}

if($loggedin)
{
	if(isset($_GET['type']) && in_array($_GET['type'],array('0','1','2','3','4','5')))
	{
		if($_GET['type'] == '0') $par = '';
		elseif($_GET['type'] == '1') $par = 'WHERE ((`o_is_ours`=1 AND `o_is_second`=0) OR (`e_is_enemy`=1 AND `e_is_second`=0))';
		elseif($_GET['type'] == '2') $par = 'WHERE (`e_is_enemy`=1 AND `e_is_second`=0)';
		elseif($_GET['type'] == '3') $par = 'WHERE (`o_is_ours`=1 AND `o_is_second`=0)';
		elseif($_GET['type'] == '4') $par = 'WHERE (`e_is_enemy`=1 AND `e_is_second`=1)';
		elseif($_GET['type'] == '5') $par = 'WHERE (`o_is_ours`=1 AND `o_is_second`=1)';
		echo $sql->cron_refresh_chars(15,$par);
	}
	else echo "<span class='evtbad'><b>ERROR: INVALID REQUEST</b></span>";
}
else echo "<span class='evtbad'><b>ERROR: ACCESS DENIED</b></span>";
?>