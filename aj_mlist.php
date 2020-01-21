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
			case 'hide':
				$r = $sql->ajax_magelist_hide(stripslashes(urldecode($_GET['name'])),$username);
				echo $r;
			break;
			case 'unhide':
				$r = $sql->ajax_magelist_unhide(stripslashes(urldecode($_GET['name'])),$username);
				echo $r;
			break;
			case 'tag':
				if(in_array($_GET['newTag'],array('0','1','2','3','4','5'))) {
				$r = $sql->ajax_magelist_tag(stripslashes(urldecode($_GET['name'])),$_GET['newTag'],$username);
				echo $r;
				}
				else echo "0";
			break;
			default:
				echo "0";
			break;
		}
	}
	else echo "0";
}
else echo "0";