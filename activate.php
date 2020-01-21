<?php
require("Wsql.php");
require("Wsql_usr.php");
$sql = new Wsql_usr();

$centerbox=1;
$username="new user";
$pagetitle="Wartool - account activation";
$navpath="account activation";
$javascript=<<<EOD

<script>
function togglep(ae)
{
document.activ.phon1.disabled=ae;
document.activ.phon2.disabled=ae;
}
function togglem(ae)
{
document.activ.msgr.disabled=ae;
}
</script>
EOD;
if(isset($_GET['u'])&&isset($_GET['v']))
{
	$username=stripslashes(urldecode($_GET['u']));
	if($accdata=$sql->get_validation_info($username,$_GET['v']))
	{
		$username=$accdata['name'];
		if($accdata['acctype']==0) $accdata['acctype']="guest account";
		elseif($accdata['acctype']==1) $accdata['acctype']="limited account";
		elseif($accdata['acctype']==2) $accdata['acctype']="full account";
		
		//get pre-set phone+msn data (if account is being RE-activated after pw change) to reset form accordingly
		$defcc=($accdata['cc']===null)?null:$accdata['cc'];
		if($defcc===null) $defno=null;
		else $defno=preg_replace("~^\+[0-9]*\s~","",$accdata['phone']);
		if($defcc!==null&&!array_key_exists($defcc,$sql->phonearray)) {$defno="+".$defcc." ".$defno; $defcc="?";}
		$defms=$accdata['msn'];
		
		if(isset($_POST['process']))
		{
			$check=0;
			//get phone-msn data from POST to reset form accordingly in case of errors
			$pw1=$_POST['new1'];
			$pw2=$_POST['new2'];
			$defcc=(isset($_POST['ignphon']))?null:true;
			if($defcc)
			{
				$defcc=$_POST['phon1'];
				$defno=trim($_POST['phon2']);
			}
			$defms=(isset($_POST['ignmsgr']))?null:true;
			if($defms) $defms=trim($_POST['msgr']);
			
			//pw sysntax check
			if($pw1==$pw2)
			{
				if(strlen($pw1)>=10)
				{
					if(preg_match("~[0-9]~",$pw1)&&preg_match("~[A-Z]~",$pw1)&&preg_match("~[a-z]~",$pw1))
					{
						$check+=1;
					}
					else {$response="Password is not secure enough."; $respfail=1;}
				}
				else {$response="New password is too short."; $respfail=1;}
			}
			else {$response="New passwords do not match."; $respfail=1;}
			//phone syntax check
			if($defcc=="?")
			{
				if(strlen($defno)>9)
				{
					if(preg_match("~^\+([0-9]*) ([0-9]*)$~",$defno,$tmp))
					{
						$defno2=preg_replace("~^0*~","",$tmp[2]);
						$defcc2=$tmp[1];
						$check+=1;
					}
					else {$respfail=1; $response="Invalid phone number formatting.";}
				}
				else {$respfail=1; $response="Invalid phone number formatting.";}
			}
			elseif($defcc===null)
			{
				//aBCdefghI1
				$defcc2=null;
				$defno2=null;
				$check+=1;
			}
			else
			{
				if(strlen($defno)>6)
				{
					$defno2=preg_replace("~^0*~","",$defno);
					$defcc2=$defcc;
					if(preg_match("~^[0-9]*$~",$defno)) $check+=1;
					else {$respfail=1; $response="Invalid phone number formatting.";}
				}
				else {$respfail=1; $response="Invalid phone number formatting.";}
			}
			//msn syntax check
			if(preg_match("~^[a-zA-Z0-9\.\-\_]*@[a-zA-Z0-9\.\-\_]*\.[a-zA-Z]{2,4}$~",$defms)||$defms===null) $check+=1;
			else {$respfail=1; $response="Invalid MSN address formatting.";}
			
			if($check==3)
			{
				$defno2=($defcc2===null)?null:"+".$defcc2." ".$defno2;
				if($sql->validate($username,md5($pw1),$defcc2,$defno2,$defms))
				{
					require("inc_head.php");
					$output .= "<strong>ACCOUNT ACTIVATED</strong>\n<br><br>\n";
					$output .= "Your account has been activated.<br>\nYou can now log in with your name and newly chosen password.<br><br>\n";
					$output .= "Click <a href='./'>here</a> to log in.";
					require("inc_foot.php");
					echo $output;
					exit;
				}
				else {$respfail=1; $response="DB Failure. Try again later.";}
			}
		}
		
		require("inc_head.php");
		
		$output .= "<strong>ACCOUNT ACTIVATION</strong>\n<br><br>\n<form name='activ' action='./activate.php?v=".$_GET['v']."&u=".urlencode($username)."' method='post'>\n<table style='font-weight:bold;'>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>Account name:</td>\n<td align='left'>".$username."</td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>Account type:</td>\n<td align='left'>".$accdata['acctype']."</td>\n</tr>\n";
		$output .= "<tr>\n<td colspan='2'>&nbsp;</td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>Choose new password:</td>\n<td align='left'><input type='password' name='new1'></td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>Repeat new password:</td>\n<td align='left'><input type='password' name='new2'></td>\n</tr>\n";
		$output .= "<tr>\n<td colspan='2'>&nbsp;</td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>Country code:</td>\n<td align='left'><select size='1' name='phon1'".(($defcc===null)?" disabled='true'":"").">\n";
		if($defcc==null) {
			$q=0;
			foreach($sql->phonearray as $n => $c) {
				$output.="<option value='$n'".(($q==0)?' SELECTED':'').">+$n ($c)\n";
				$q+=1;
			}
		}
		else foreach($sql->phonearray as $n => $c) $output.="<option value='$n'".(($n==$defcc)?' SELECTED':'').">+$n ($c)\n";
		$output .= "</select></td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>Phone number:</td><td align='left'><input type='text' name='phon2'".(($defcc===null)?" disabled='true'":" value='$defno'")."></td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>I don't have a phone:</td><td align='left'><input type='checkbox' name='ignphon' onClick='togglep(this.checked)'".(($defcc===null)?" CHECKED":"")."> <span style='font-weight:normal;font-size:12px;'>(lol yeah, right)</span></td>\n</tr>\n";
		$output .= "<tr>\n<td colspan='2'>&nbsp;</td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>MSN address:</td><td align='left'><input type='text' name='msgr'".(($defms===null)?" disabled='true'":" value='$defms'")."></td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>I don't have MSN:</td><td align='left'><input type='checkbox' name='ignmsgr' onClick='togglem(this.checked)'".(($defms===null)?" CHECKED":"")."></td>\n</tr>\n";
		$output .= "<tr>\n<td colspan='2'><input type='hidden' name='process' value='jude'></td>\n</tr>\n";
		$output .= "<tr>\n<td colspan='2' align='center'><input type='submit' value=' activate my account '></td>\n</tr>\n";
		$output .= "</table>\n</form>\n";

		$output .= "<br><br>\n<strong>Note:</strong>\n<br>\n<strong>Passwords</strong> have to be at least 10 characters long and contain at least<br>\none lowercase (a-z) and one uppercase (A-Z) letter and one number (0-9).<br><br>\n";
		$output .= "If your phone country code (eg. +46 for Sweden) is on the list,<br>\n and your number is 079-123456, the <strong>phone number</strong> field should be<br>\n\"79123456\" or \"079123456\".<br><br>\nIf your country code is NOT on the list,select \"+? (OTHER)\" on the list,<br>\n";
		$output .= "then set the phone number itself to \"+X Y\" where X is your country code<br>\nand Y is your number.<br>\n<strong>Example:</strong> Country code +987 (not in list), Number 0123-45678:<br>\n<strong>phone number</strong> field should be \"+987 12345678\" or \"+987 012345678\"";
	}
	else
	{
		$respfail = 1;
		$response = "Invalid activation link. Get lost.";
		require("inc_head.php");
		$output .= "<br><br><br><br>\n<strong>INVALID ACTIVATION LINK</strong><br>Contact an Admin or Mod for help.";
	}
}
else
{
	$respfail = 1;
	$response = "Invalid activation link. Get lost.";
	require("inc_head.php");
	$output .= "<br><br><br><br>\n<strong>INVALID ACTIVATION LINK</strong><br>Contact an Admin or Mod for help.";
}

require("inc_foot.php");

$sql = 0;
echo $output;

?>