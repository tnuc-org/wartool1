<?php
require("Wsql.php");
require("Wsql_acc.php");
require("CookiMgr.php");
$sql = new Wsql_acc();
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
	
	if(isset($_POST['act']))
	{
		switch($_POST['act'])
		{
			case 'add':
				if($userlevel==0) header("Location: ./mod_deny.php");
				$n=trim(stripslashes($_POST['fname']));
				$p=($_POST['par']==0)?0:1;
				$h=($_POST['par']==2)?1:0;
				if(preg_match("~^[A-Za-z\'\s\-]*$~",$n))
				{
					$x=$sql->add_new_char($n,$p,$h,$username);
					if($x===true)
					{
						$t=($p===1)?'second':'main';
						$response = "$n has been added as your $t character.";
					}
					else
					{
						$respfail=1;
						if($x===0) $response="Character $n belongs to somebody else already.";
						elseif($x===1) $response="Character $n does not exist.";
						elseif($x===2) $response="Database Error. Try again later.";
						else $response="Unknown Error, try again later.";
					}
				}else {$respfail=1; $response="Invalid name formatting.";}
			break;
			case 'accq':
				if($userlevel==0) header("Location: ./mod_deny.php");
				$pdata = $sql->encode_accessq();
				$c = trim(stripslashes(urldecode($_POST['fname'])));
				if($sql->set_accessq($pdata,$c,$username))
					$response = "Access quests for $c edited successfully.";
				else
				{$respfail=1; $response = "Failed to edit $c's access quests.";}
			break;
			case 'phon':
				$rcc=null;
				$rph=null;
				$rms=null;
				$pdata=array();
				$pdata['cc'] = isset($_POST['ignphon']) ? null : trim($_POST['phon1']);
				$pdata['phone'] = isset($_POST['ignphon']) ? null : trim($_POST['phon2']);
				$pdata['msn'] = isset($_POST['ignmsgr']) ? null : trim($_POST['msgr']);
				$check=0;
				//phone syntax validation
				if($pdata['cc']=="?")
				{
					if(strlen($pdata['phone'])>9)
					{
						if(preg_match("~^\+([0-9]*) ([0-9]*)$~",$pdata['phone'],$tmp))
						{
							$rcc=$tmp[1];
							$rph="+".$rcc." ".preg_replace("~^0*~","",$tmp[2]);
							$check+=1;
						}
						else {$respfail=1; $response="Invalid phone number formatting.";}
					}
					else {$respfail=1; $response="Invalid phone number formatting.";}
				}
				elseif($pdata['cc']===null)
				{
					$rcc=null;
					$rph=null;
					$check+=1;
				}
				else
				{
					if(strlen($pdata['phone'])>6)
					{
						$rph="+".$pdata['cc']." ".preg_replace("~^0*~","",$pdata['phone']);
						$rcc=$pdata['cc'];
						if(preg_match("~^[0-9]*$~",$pdata['phone'])) $check+=1;
						else {$respfail=1; $response="Invalid phone number formatting.";}
					}
					else {$respfail=1; $response="Invalid phone number formatting.";}
				}
				//msn syntax validation
				if(preg_match("~^[a-zA-Z0-9\.\-\_]*@[a-zA-Z0-9\.\-\_]*\.[a-zA-Z]{2,4}$~",$pdata['msn'])||$pdata['msn']===null) {$rms=$pdata['msn']; $check+=1;}
				else {$respfail=1; $response="Invalid MSN address formatting.";}
				if($check==2)
				{
					if($rph===null) $rcc=null;
					if($sql->set_avail_info($rcc,$rph,$rms,$username))
						header("Location: ./mod_accm.php");
					else {$respfail=1; $response="Database Error, try again later.";}
				}
			break;
		}
	}
	
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	switch($view)
	{
		case 'phone':
			$pagetitle = "Wartool - my account - phone/msn settings";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_accm.php'>my account</a> - phone/msn settings";
			$centerbox=1;
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
			include("inc_head.php");
			$data=(isset($pdata))?$pdata:$sql->get_avail_info($username);
			if($data===false) $data=array('cc'=>null,'phone'=>null,'msn'=>null);
			elseif($data['cc']!='?'&&array_key_exists($data['cc'],$sql->phonearray)) $data['phone']=preg_replace("~^\+[0-9]*\s~","",$data['phone']);
			else if($data['cc']!==null) $data['cc']='?';
			$output.="<a href='./mod_accm.php'>back</a>\n<br><br>\n<form name='activ' action='./mod_accm.php?view=phone' method='post'><table style='font-weight:bold;'>";
			
			/* */
			
			$output .= "<tr>\n<td align='right' style='padding-right:10px;'>Country code:</td>\n<td align='left'><select size='1' name='phon1'".(($data['cc']===null)?" disabled='true'":"").">\n";
		if($data['cc']===null) {
			$q=0;
			foreach($sql->phonearray as $n => $c) {
				$output.="<option value='$n'".(($q==0)?' SELECTED':'').">+$n ($c)\n";
				$q+=1;
			}
		}
		else foreach($sql->phonearray as $n => $c) $output.="<option value='$n'".(($n==$data['cc'])?' SELECTED':'').">+$n ($c)\n";
		$output .= "</select></td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>Phone number:</td><td align='left'><input type='text' name='phon2'".(($data['cc']===null)?" disabled='true'":" value='".$data['phone']."'")."></td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>I don't have a phone:</td><td align='left'><input type='checkbox' name='ignphon' onClick='togglep(this.checked)'".(($data['cc']===null)?" CHECKED":"")."> <span style='font-weight:normal;font-size:12px;'>(lol yeah, right)</span></td>\n</tr>\n";
		$output .= "<tr>\n<td colspan='2'>&nbsp;</td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>MSN address:</td><td align='left'><input type='text' name='msgr'".(($data['msn']===null)?" disabled='true'":" value='".$data['msn']."'")."></td>\n</tr>\n";
		$output .= "<tr>\n<td align='right' style='padding-right:10px;'>I don't have MSN:</td><td align='left'><input type='checkbox' name='ignmsgr' onClick='togglem(this.checked)'".(($data['msn']===null)?" CHECKED":"")."></td>\n</tr>\n";
		$output .= "<tr>\n<td colspan='2'><input type='hidden' name='act' value='phon'></td>\n</tr>\n";
		$output .= "<tr>\n<td colspan='2' align='center'><input type='submit' value=' save changes '></td>\n</tr>\n";
		$output .= "</table>\n</form>\n";

		$output .= "<br><br>\n<strong>Note:</strong>\n<br>\n";
		$output .= "If your phone country code (eg. +46 for Sweden) is on the list,<br>\n and your number is 079-123456, the <strong>phone number</strong> field should be<br>\n\"79123456\" or \"079123456\".<br><br>\nIf your country code is NOT on the list,select \"+? (OTHER)\" on the list,<br>\n";
		$output .= "then set the phone number itself to \"+X Y\" where X is your country code<br>\nand Y is your number.<br>\n<strong>Example:</strong> Country code +987 (not in list), Number 0123-45678:<br>\n<strong>phone number</strong> field should be \"+987 12345678\" or \"+987 012345678\"";
			
			/* */
			
		break;
		
		case 'accessq':
			if($userlevel==0) header("Location: ./mod_deny.php");
			if(!isset($_GET['c'])) header("Location: ./mod_accm.php");
			else
			{
				$cname = trim(stripslashes(urldecode($_GET['c'])));
				$pagetitle = "Wartool - my account - access quests for $cname";
				$navpath = "<a href='./'>main menu</a> - <a href='./mod_accm.php'>my account</a> - access quests for $cname";
				$centerbox=1;
				include("inc_head.php");
				$data = isset($pdata)?$pdata:$sql->get_accessq($cname,$username);
				if($data===false) header("Location: ./mod_accm.php");
				else
				{
					$a=str_split($data);
					$output.="<form action='./mod_accm.php' method='post'><table>";
					$output.="<tr><td>Ice Islands</td><td><select name='iceisle'><option value='0'".(($a[0]==0)?' SELECTED':'').">Not started<option value='1'".(($a[0]==1)?' SELECTED':'').">Access to Okolnir (Barb Test done)";
					$output.="<option value='2'".(($a[0]==2)?' SELECTED':'').">Access to Nibelor (Dogsled Island)<option value='3'".(($a[3]==3)?' SELECTED':'').">Acess to Tyrsung (Frost Giants)";
					$output.="<option value='4'".(($a[0]==4)?' SELECTED':'').">Access to Chakoyas and Helheim<option value='5'".(($a[0]==5)?' SELECTED':'').">Access to Svargrond Mines";
					$output.="<option value='6'".(($a[0]==6)?' SELECTED':'').">Can use Mines Elevator</select></td></tr>";
					$output.="<tr><td>Sea Serpents</td><td><input type='checkbox' name='seaserp' value='1'".(($a[1]==1)?' CHECKED':'')."></td></tr><tr><td colspan='2'>&nbsp;</td></tr>";
					$output.="<tr><td>Meriana (Peg Leg)</td><td><input type='checkbox' name='pegleg' value='1'".(($a[2]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Goroma main isle</td><td><input type='checkbox' name='goroma' value='1'".(($a[3]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Goroma BB Isle</td><td><input type='checkbox' name='goromabb' value='1'".(($a[4]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Goroma Hydra Isle</td><td><input type='checkbox' name='goromahy' value='1'".(($a[5]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Goroma GS Isle</td><td><input type='checkbox' name='goromags' value='1'".(($a[6]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Tortoises</td><td><input type='checkbox' name='torts' value='1'".(($a[7]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Pirates</td><td><input type='checkbox' name='pirates' value='1'".(($a[8]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td colspan='2'>&nbsp;</td></tr>";
					$output.="<tr><td>Joined Explorer Society</td><td><input type='checkbox' name='expljoin' value='1'".(($a[9]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Calassa</td><td><input type='checkbox' name='explcala' value='1'".(($a[10]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Frozen Trench</td><td><input type='checkbox' name='explfroz' value='1'".(($a[11]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Explorer TP 1 (PH<>NPort)</td><td><input type='checkbox' name='expltp1' value='1'".(($a[12]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Explorer TP 2 (LB<>Svar)</td><td><input type='checkbox' name='expltp2' value='1'".(($a[13]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td colspan='2'>&nbsp;</td></tr>";
					$output.="<tr><td>Forbidden Lands</td><td><input type='checkbox' name='banuforb' value='1'".(($a[14]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Deeper Banuta</td><td><input type='checkbox' name='banudeep' value='1'".(($a[15]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td colspan='2'>&nbsp;</td></tr>";
					$output.="<tr><td>Yalahar Main Quest</td><td><select name='yalahar'><option value='0'".(($a[16]==0)?' SELECTED':'').">Can NOT go to Yalahar (gz moron)";
					$output.="<option value='1'".(($a[16]==1)?' SELECTED':'').">Can go to Yalahar, no gates<option value='2'".(($a[16]==2)?' SELECTED':'').">Access to Alchemist (m3 started)";
					$output.="<option value='3'".(($a[16]==3)?' SELECTED':'').">Access to Trade (m4 started)<option value='4'".(($a[16]==4)?' SELECTED':'').">Access to Arena (m5 started)";
					$output.="<option value='5'".(($a[16]==5)?' SELECTED':'').">Access to Cemetery (m6 started)<option value='6'".(($a[16]==6)?' SELECTED':'').">Access to Sunken (m7 started)";
					$output.="<option value='7'".(($a[16]==7)?' SELECTED':'').">Access to Factory (m8 started)<option value='8'".(($a[16]==8)?' SELECTED':'').">Access to Final Room (m10 started)";
					$output.="</select></td></tr>";
					$output.="<tr><td>Shiproutes to Yalahar</td><td><input type='checkbox' name='yalaboat' value='1'".(($a[17]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Beregar mine cart</td><td><input type='checkbox' name='berecart' value='1'".(($a[18]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Blood Brothers Quest</td><td><select name='bloodbro'><option value='0'".(($a[19]==0)?' SELECTED':'').">Not started";
					$output.="<option value='1'".(($a[19]==1)?' SELECTED':'').">Access to Vengoth (m4 started)<option value='2'".(($a[19]==2)?' SELECTED':'').">Access to the Castle (m5 done)";
					$output.="<option value='3'".(($a[19]==3)?' SELECTED':'').">Access to everything (m10 started)</select></td></tr>";
					$output.="<tr><td colspan='2'>&nbsp;</td></tr>";
					$output.="<tr><td>Anni done</td><td><input type='checkbox' name='anni' value='1'".(($a[20]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>LB Piano Cults</td><td><input type='checkbox' name='lbcults' value='1'".(($a[21]==1)?' CHECKED':'')."></td></tr>";
					$output.="<tr><td>Djinns</td><td><select name='djinn'><option value='0'".(($a[22]==0)?' SELECTED':'').">None";
					$output.="<option value='1'".(($a[22]==1)?' SELECTED':'').">Green Started<option value='2'".(($a[22]==2)?' SELECTED':'').">Green Finished";
					$output.="<option value='3'".(($a[22]==3)?' SELECTED':'').">Blue Started<option value='4'".(($a[22]==4)?' SELECTED':'').">Blue Finished</select></td></tr>";
					$output.="<tr><td>Inquisition</td><td><select name='inq'><option value='0'".(($a[23]==0)?' SELECTED':'').">Not killed Ungreez yet";
					$output.="<option value='1'".(($a[23]==1)?' SELECTED':'').">Demonforge access (Killed Ungreez)<option value='2'".(($a[23]==2)?' SELECTED':'').">Quest done and can buy bless";
					$output.="</select></td></tr><tr><td colspan='2'>&nbsp;</td></tr><tr><td colspan='2' align='center'><input type='hidden' name='act' value='accq'>";
					$output.="<input type='hidden' name='fname' value='$cname'><input type='submit' value=' apply changes '></td></tr></table></form>";
					
				}
			}
		break;
		default:
			$pagetitle = "Wartool - my account";
			$navpath = "<a href='./'>main menu</a> - my account";
			$javascript = "<script src=\"ajax.js\"></script>";
			$centerbox=1;
			if($userlevel==0)
			{
				include("inc_head.php");
				$output .= "<a href='./'>back</a>\n<br><br>\n<strong>MY ACCOUNT</strong>\n<br><br>\n<a href='./mod_accm.php?view=phone'>edit msn/phone#</a>\n<br>\n";
				$output .= "<a href='./mod_pass.php'>change password</a>\n";
			}
			else
			{
				$data = $sql->get_user_chars($username);
				include("inc_head.php");
				$output .= "<a href='./'>back</a>\n<br><br>\n<strong>MY ACCOUNT</strong>\n<br><br>\n<a href='./mod_accm.php?view=phone'>edit msn/phone#</a>\n<br>\n";
				$output .= "<a href='./mod_pass.php'>change password</a>\n<br><br><br>\n";
				$output .= "<strong>MY CHARACTERS</strong>";
				if($data!==false)
				{
					$output .= "<table><tr><td><table><tr><td colspan='8' align='left'><strong>Mains:</strong></td></tr>";
					$output .= "<tr align='left'><th>rdy</th><th>name</th><th align='right'>lvl</th><th>voc</th><th>guild</th><th>residence</th><th align='center'>access quests</th><th align='center'>edit</th></tr>";
					foreach($data[0] as $k => $v)
					{
						$enc = urlencode($v['name']);
						$edt = "[<a href='./mod_accm.php?view=accessq&amp;c=$enc'>access q's</a>] [<span class='fklnk' onclick='delOwn(\"$enc\",this);'>delete</span>]";
						$rdy = $v['o_rdy']==1 ? "<img src='./img/stat_norm.gif' onclick='toggleRdy(0,this,\"$enc\");'>":"<img src='./img/stat_na.gif' onclick='toggleRdy(1,this,\"$enc\");'>";
						$output .= "<tr align='left'><td>".$rdy."</td><td>".$v['name']."</td><td align='right'>".$v['level']."</td><td>".$v['voc']."</td><td>".str_replace("'>","' target='_blank'>",$v['guild'])."</td><td>".$v['residence']."</td><td>".$sql->format_accessq($v['o_accessq'])."</td><td>$edt</td></tr>";
					}
					$output .= "</table></td></tr><tr><td><tr><table><td colspan='8' align='left'><strong>Seconds:</strong></td></tr>";
					$output .= "<tr align='left'><th>rdy</th><th>name</th><th align='right'>lvl</th><th>voc</th><th>guild</th><th>residence</th><th align='center'>access quests</th><th align='center'>edit</th></tr>";
					foreach($data[1] as $k => $v)
					{
						$enc = urlencode($v['name']);
						$nam = $v['o_hidden']==1 ? "*".$v['name']:$v['name'];
						$edt = "[<a href='./mod_accm.php?view=accessq&amp;c=$enc'>access q's</a>] [<span class='fklnk' onclick='delOwn(\"$enc\",this);'>delete</span>]";
						$rdy = $v['o_rdy']==1 ? "<img src='./img/stat_norm.gif' onclick='toggleRdy(0,this,\"$enc\");'>":"<img src='./img/stat_na.gif' onclick='toggleRdy(1,this,\"$enc\");'>";
						$output .= "<tr align='left'><td>".$rdy."</td><td>".$nam."</td><td align='right'>".$v['level']."</td><td>".$v['voc']."</td><td>".str_replace("'>","' target='_blank'>",$v['guild'])."</td><td>".$v['residence']."</td><td>".$sql->format_accessq($v['o_accessq'])."</td><td>$edt</td></tr>";
					}
					$output .= "</table></td></tr></table><br><br><table><tr><td colspan='3' align='left'><strong>Add char:</strong></td></tr>";
					$output .= "<tr><td><form action='./mod_accm.php' method='post'><input type='hidden' name='act' value='add'>Add  <input type='text' maxlength='30' name='fname'> as my ";
					$output .= "<select size='1' name='par'>\n<option value='0' SELECTED>main char\n<option value='1'>second char\n<option value='2'>second char (hide name)\n</select> ";
					$output .= "<input type='submit' value='go'></form></td></tr></table>";
					
					$output .= "<br><br><br>\n<strong>Note:</strong>\n<br>\n\"Rdy\" means your char is blessed, supplied and logged in a +/- normal place.<br>\n";
					$output .= "Click the rdy icon to toggle between ready and not ready.<br>\n(not really important for 2nds)<br><br>\n<strong>PS:</strong>\n<br>\n";
					$output .= "If you fuck about and add f.e. Bubble or shit, I'll enable Tibia.com verification,<br>\n";
					$output .= "so you have to put a certain code in your char profile before you can add it here.";
				}
				else
				{
					$output .= "<br><span class='response2'>DB inaccessible, try again later.</span>";
				}
			}
		break;
	}
	
	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}