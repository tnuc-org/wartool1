<?php
require("Wsql.php");
require("Wsql_ajx.php");
require("CookiMgr.php");
$sql = new Wsql_ajx();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$perm_name = "perm_charlist";

if($cmgr->allset())
{
	$accdata = $sql->token_auth($cmgr->get_user(),$cmgr->get_hash());
	if(!$accdata) {$sql=null; echo "0"; exit;}
	$username=$accdata['name'];
	$userlevel=($accdata['isadmin']==1)?4:$accdata[$perm_name];
	if($userlevel>=1) $loggedin=true;
}
else {$sql=null; echo "0"; exit;}

if($loggedin)
{
	if(isset($_GET['act']))
	{
		switch($_GET['act'])
		{
			case 'add':
				$tname = trim(stripslashes(urldecode($_GET['name'])));
				if(!preg_match("~^[A-Za-z\'\-\s]*$~",$tname)||$tname==='')
					echo("1");
				else
				{
					$tcom = trim(stripslashes(urldecode($_GET['comment'])));
					$type = ($_GET['type']==0)?0:1;
					$r = $sql->ajax_requests_add($tname,$type,$tcom,$username,$userlevel);
					echo $r;
				}
			break;
			
			case 'comment':
				$tname = trim(stripslashes(urldecode($_GET['name'])));
				$tcom = trim(stripslashes(urldecode($_GET['comment'])));
				$type = ($_GET['type']==0)?0:1;
				$r = $sql->ajax_requests_comment($tname,$type,$tcom,$username,$userlevel);
				echo $r;
			break;
			
			case 'decline':
				if($userlevel<3) echo "0";
				else
				{
					$tname = trim(stripslashes(urldecode($_GET['name'])));
					$r = $sql->ajax_requests_decline($tname);
					echo $r;
				}
			break;
			case 'process':
				if($userlevel<3) echo "0";
				else
				{
					$tname = trim(stripslashes(urldecode($_GET['name'])));
					$rtype = ($_GET['reqType']==0)?0:1;
					$issec = ($_GET['issec']==0)?0:1;
					$atype = (in_array($_GET['addType'],array('0','1','2','3','4')))?$_GET['addType']:0;	
					$r = $sql->ajax_requests_process($tname,$rtype,$atype,$issec,$username);
					echo $r;
				}
			break;
			
			default:
				echo "0";
			break;
		}
	}
	else echo "0";
}
else echo "0";
?>