<?php
require("Wsql.php");
require("Wsql_ajx.php");
require("CookiMgr.php");
$sql = new Wsql_ajx();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$perm_name = "acctype";

if($cmgr->allset())
{
	$accdata = $sql->token_auth($cmgr->get_user(),$cmgr->get_hash());
	if(!$accdata) {$sql=null; echo "0"; exit;}
	$username=$accdata['name'];
	$userlevel=($accdata['isadmin']==1)?4:$accdata[$perm_name];
	if($userlevel>=2) $loggedin=true;
}
else {$sql=null; echo "0"; exit;}

if($loggedin)
{
	if(isset($_GET['act']))
	{
		switch($_GET['act'])
		{
			case 'rdy':
				if(!in_array($_GET['par'],array('0','1'))) echo '1';
				else
				{
					$r = $sql->ajax_myacc_togglerdy(stripslashes(urldecode($_GET['name'])),$_GET['par'],$username);
					echo $r;
				}
			break;
			case 'del':
				$r = $sql->ajax_myacc_delete(stripslashes(urldecode($_GET['name'])),$username);
				echo $r;
			break;
			default:
				echo "0";
			break;
		}
	}
	else echo "0";
}
else echo "0";