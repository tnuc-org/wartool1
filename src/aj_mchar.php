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
	if($userlevel>=3) $loggedin=true;
}
else {$sql=null; echo "0"; exit;}

if($loggedin)
{
	if(isset($_GET['act']))
	{
		switch($_GET['act'])
		{
			case 'del':
				$name=stripslashes(urldecode($_GET['nameId']));
				$result = $sql->ajax_mantchar2_delete($name,$username);
				echo $result;
			break;
			case 'ins':
				$name=trim(stripslashes(urldecode($_GET['nameId'])));
				if($name!=''&&preg_match("~^[A-Za-z\'\-\.\s ]*$~",$name)) {
				$comment=stripslashes(urldecode($_GET['comment']));
				$sec=$_GET['secChar'];
				$nok=$_GET['dontKill'];
				$khp=$_GET['killHP'];
				$exp=$_GET['explimit'];
				$bls=$_GET['blackList'];
				$result = $sql->ajax_mantchar2_insert($name,$comment,$sec,$nok,$khp,$exp,$bls,$username);
				echo $result;
				}else echo "0";
			break;
			case 'upd':
				$name=stripslashes(urldecode($_GET['nameId']));
				switch($_GET['itemId'])
				{
					case 0: $field='e_comment'; break;
					case 1: $field='e_is_second'; break;
					case 2: $field='e_explimit'; break;
					case 3: $field='e_nokill'; break;
					case 4: $field='e_killhp'; break;
					case 5: $field='e_blacklist'; break;
					default: $field=null;
				}
				$value=($field!='e_comment')?$_GET['itemValue']:stripslashes(urldecode($_GET['itemValue']));
				if($field!==null)
				{
					$result = $sql->ajax_mantchar2_update($name,$field,$value,$username);
					echo $result;
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
?>