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
$perm_name = "perm_charlist";

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
			case '':
				;
			break;
		}
	}
	
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	switch($view)
	{
		case 'deathlist':
			$pagetitle = "Wartool - enemy characters - death list";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_enem.php'>enemy characters</a> - death list";
			$centerbox = 1;
			if($deathlist = $sql->get_recent_deathlist())
			{
				require("inc_head.php");
				$output .= "<a href='./'>main menu</a> ~ <a href='./mod_enem.php'>char list</a> ~ <a href='./mod_enem.php?view=lvlchange'>level change</a> ~ <a href='./mod_enem.php?view=activity'>activity</a>";
				$output .= "<br><br>\n<table>\n<tr>\n<th colspan='3'>MAIN CHARS</th>\n</tr>\n<tr>\n<th align='left'>name</th>\n<th align='left'>guild</th>\n<th align='left'>death record</th>\n</tr>\n";
				foreach($deathlist[0] as $key => $val)
				{
					if($val['killer1']==''&&$val['killer1']==null)
					{
						$encoded = urlencode(preg_replace("~^.*\[a\]~","",str_replace("[z]","",$val['killer2'])));
						$text = $val['text'].str_replace(array("[a]","[z]"),array("<a target='_blank' href='http://www.tibia.com/community/?subtopic=character&amp;name=".$encoded."'>","</a>"),$val['killer2']);
					}
					else
					{
						$encoded1 = urlencode($val['killer1']);
						$encoded2 = urlencode(preg_replace("~^.*\[a\]~","",str_replace("[z]","",$val['killer2'])));
						$text = $val['text']."<a target='_blank' href='http://www.tibia.com/community/?subtopic=character&amp;name=$encoded1'>".$val['killer1']."</a> and by ".str_replace(array("[a]","[z]"),array("<a target='_blank' href='http://www.tibia.com/community/?subtopic=character&amp;name=".$encoded2."'>","</a>"),$val['killer2']);
					}
					$val['guild']=str_replace("'>","' target='_blank'>",$val['guild']);
					$output .= "<tr align='left'>\n<td>".$val['cname']."</td>\n<td>".$val['guild']."</td>\n<td>".$text."</td>\n</tr>\n";
				}
				$output .= "</table><br><br><br>\n<table>\n<tr>\n<th colspan='3'>SECOND CHARS</th>\n</tr>\n<tr>\n<th align='left'>name</th>\n<th align='left'>guild</th>\n<th align='left'>death record</th>\n</tr>\n";
				foreach($deathlist[1] as $key => $val)
				{
					if($val['killer1']==''&&$val['killer1']==null)
					{
						$encoded = urlencode(preg_replace("~^.*\[a\]~","",str_replace("[z]","",$val['killer2'])));
						$text = $val['text'].str_replace(array("[a]","[z]"),array("<a href='./mod_char.php?name=".$encoded."'>","</a>"),$val['killer2']);
					}
					else
					{
						$encoded1 = urlencode($val['killer1']);
						$encoded2 = urlencode(preg_replace("~^.*\[a\]~","",str_replace("[z]","",$val['killer2'])));
						$text = $val['text']."<a href='./mod_char.php?name=$encoded1'>".$val['killer1']."</a> and by ".str_replace(array("[a]","[z]"),array("<a href='./mod_char.php?name=".$encoded2."'>","</a>"),$val['killer2']);
					}
					$val['guild']=str_replace("'>","' target='_blank'>",$val['guild']);
					$output .= "<tr align='left'>\n<td>".$val['cname']."</td>\n<td>".$val['guild']."</td>\n<td>".$text."</td>\n</tr>\n";
				}
			}
			else
			{
				$respfail = 1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE INACCESSIBLE</strong><br>Click <a href='./'>here</a> to get back to the main menu.";
			}
		break;
		
		case 'lvlchange':
			$pagetitle = "Wartool - enemy characters - level change";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_enem.php'>enemy characters</a> - level change";
			$centerbox = 1;
			if($levelchanges = $sql->get_levelchanges())
			{
				require("inc_head.php");
				$output .= "<a href='./'>main menu</a> ~ <a href='./mod_enem.php?view=deathlist'>death list</a> ~ <a href='./mod_enem.php'>char list</a> ~ <a href='./mod_enem.php?view=activity'>activity</a><br>";
				$output .= "<table><tr><td valign='top'>\n<table><tr><td valign='top'><table>\n<tr align='left'>\n<th>name</th><th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>init lvl</th>\n<th>change</th>\n</tr>\n";
				foreach($levelchanges[0] as $key => $val)
				{
					$colorclass=($val['difference']>0)?"nokill":"killhp";
					$val['guild']=str_replace("'>","' target='_blank'>",$val['guild']);
					$output .= "<tr align='left'>\n<td>".$val['name']."</td>\n<td align='right'>".$val['level']."</td>\n<td>".$val['voc']."</td>\n<td>".$val['guild']."</td>\n<td align='center'>".$val['init_level']."</td>\n<td class='$colorclass' align='center'>".$val['difference']."</td>\n</tr>\n";
				}
				$output .= "</table>\n</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td valign='top'>\n<table>\n<tr align='left'>\n<th>name</th><th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>init lvl</th>\n<th>change</th>\n</tr>\n";
				foreach($levelchanges[1] as $key => $val)
				{
					$colorclass=($val['difference']>0)?"nokill":"killhp";
					$val['guild']=str_replace("'>","' target='_blank'>",$val['guild']);
					$output .= "<tr align='left'>\n<td>".$val['name']."</td>\n<td align='right'>".$val['level']."</td>\n<td>".$val['voc']."</td>\n<td>".$val['guild']."</td>\n<td align='center'>".$val['init_level']."</td>\n<td class='$colorclass' align='center'>".$val['difference']."</td>\n</tr>\n";
				}
				$output .= "</table>\n</td></tr></table>";
			}
			else
			{
				$respfail = 1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE INACCESSIBLE</strong><br>Click <a href='./'>here</a> to get back to the main menu.";
			}
		break;
		
		case 'activity':
			$pagetitle = "Wartool - enemy characters - activity";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_enem.php'>enemy characters</a> - activity";
			$centerbox = 1;
			if(1)
			{
				require("inc_head.php");
				$output .= "<a href='./'>main menu</a> ~ <a href='./mod_enem.php?view=deathlist'>death list</a> ~ <a href='./mod_enem.php?view=lvlchange'>level change</a> ~ <a href='./mod_enem.php'>char list</a><br>";
				$output .= "<br><br><br><strong>UNDER RE-CONSTRUCTION</strong>";
			}
			else
			{
				$respfail = 1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE INACCESSIBLE</strong><br>Click <a href='./'>here</a> to get back to the main menu.";
			}
		break;
		
		case 'magelist':
			$userlevel = ($accdata['isadmin']==1)?4:$accdata['acctype'];
			if($userlevel<2) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - mage list";
			$navpath = "<a href='./'>main menu</a> - mage list";
			$javascript = "<script src=\"ajax.js\"></script>";
			$centerbox = 1;
			$par=(isset($_GET['show']))?true:false;
			$mages=$sql->get_magelist($par);
			if($mages!==false)
			{
				require("inc_head.php");
				$output .= "<strong>".strtoupper($sql->server)."N MAGES OVER LEVEL ".$sql->magereq."</STRONG>\n<br>\n";
				$output .= ($par)?"<a href='./mod_enem.php?view=magelist'>show unhidden chars</a>":"<a href='./mod_enem.php?view=magelist&amp;show=hidden'>show hidden chars</a>";
				$output .= "<br><br>\n<table cellpadding='1' cellspacing='1'>\n<tr>\n".(($par)?"<th></th>\n":'')."<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>last seen</th><th>".(($par)?"un":'')."hide</th>\n<th>change tag</th>\n</tr>\n";
				$tcont='';
				$invis = "<img src='./img/mag_inv.gif'>";
				$offs=($par)?1:0;
				foreach($mages as $val)
				{
					$enc = urlencode($val['name']);
					if($val['type']==1) $type="<img src='./img/mag_np.gif' alt='harmless'>";
					elseif($val['type']==2) $type="<img src='./img/mag_warn.gif' alt='potential threat'>";
					elseif($val['type']==3) $type="<img src='./img/mag_lock.gif' alt='exp limited'>";
					elseif($val['type']==4) $type="<img src='./img/mag_enem.gif' alt='enemy'>";
					elseif($val['type']==5) $type="<img src='./img/mag_ally.gif' alt='ally'>";
					else $type="<img src='./img/mag_unk.gif' alt='unknown'>";
					$tcont .= "<tr onMouseOver='resalta(this);' onMouseOut='apaga(this);'>".(($par)?"<td>$invis</td>":'')."<td>$type</td>\n<td align='left'><a href='http://www.tibia.com/community/?subtopic=characters&amp;name=$enc' target='_blank'>".$val['name']."</a></td>\n<td align='right'>".$val['level']."</td>\n<td>".$val['voc'];
					$tcont .= "</td>\n".$val['lastseen']."<td><input type='button' value='".(($par)?"un":'')."hide' onClick=\"".(($par)?"un":'')."hideChar('".$enc;
					$tcont .= "',this);\"></td>\n<td style='border:1px solid black;'>\n";
					$tcont .= "<img onClick=\"changeTag('".$enc."',0,this,$offs);\" src='./img/mag_unk.gif' alt='unknown'> \n";
					$tcont .= "<img onClick=\"changeTag('".$enc."',5,this,$offs);\" src='./img/mag_ally.gif' alt='ally'> \n";
					$tcont .= "<img onClick=\"changeTag('".$enc."',4,this,$offs);\" src='./img/mag_enem.gif' alt='enemy'> \n";
					$tcont .= "<img onClick=\"changeTag('".$enc."',2,this,$offs);\" src='./img/mag_warn.gif' alt='potential threat'> \n";
					$tcont .= "<img onClick=\"changeTag('".$enc."',1,this,$offs);\" src='./img/mag_np.gif' alt='harmless'> \n";
					$tcont .= "<img onClick=\"changeTag('".$enc."',3,this,$offs);\" src='./img/mag_lock.gif' alt='exp limited'>\n";
					$tcont .= "</td>\n</tr>\n";
				}
				$output .= ($tcont!='')?$tcont:"<tr>\n<td colspan='".(($par)?8:7)."' align='center'>No mages recognised yet.</td>\n</tr>\n";
				$output .= "</table>\n";
			}
			else
			{
				$respfail = 1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE INACCESSIBLE</strong><br>Click <a href='./'>here</a> to get back to the main menu.";
			}
			
		break;
		
		case 'mod':
			if($userlevel<3) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - enemy characters - mod tool (mantchar2)";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_enem.php'>enemy characters</a> - mod tool (mantchar2)";
			$javascript = "<script src=\"ajax.js\"></script>";
			require("inc_head.php");
			$output.=<<<EOD

  <div id=tempdiv></div>
<table style="width:100%">
<tr>
  <td>
  	<input type=hidden name=action value="update">
  	<table>
    <tr><td>Name:</td><td> <input id=addName type=text></td></tr>
    <tr><td>Is Second Char:</td><td> <input id=addSecchar type=checkbox value="SI"></td></tr>
    <tr><td>Dont Kill:</td><td> <input id=addNokill type=checkbox value="SI"></td></tr>
    <tr><td>Kill High Priority:</td><td> <input id=addKillhp type=checkbox value="SI"></td></tr>
    <tr><td>Black List:</td><td> <input id=addBlacklist type=checkbox value="SI"></td></tr>
	<tr><td>Exp Limit: </td><td><input id=addExpLimit type=checkbox value="SI"></td></tr>
    <tr><td>Comment:</td><td> <input type=text id=addComment style="width:600;"></td></tr>
    <tr><td colspan=2><input type=button value=" Insert New " onClick="insertNew(this);"></td></tr>
    </tr></table>
  </td>
</tr>
<tr>
  <td>
  
EOD;
			if($data=$sql->get_mod_charlist())
			{
				    $output.="<table  cellspacing=0 style='text-align:center;width:100%'>";
					$output.="<tr><td>&nbsp</td><td>Name</td><td>Level</td><td>Vocation</td><td align='left'>Guild</td><td align='left'>Comment</td><td>IsSecChar</td><td>NoKill</td><td>KillHP</td><td>BlackList</td><td>ExpLimit</td></tr>";
					for($i=0;$i<count($data);$i+=1)
					{
						$checkSecChar = ($data[$i]['e_is_second']==0)?'':'checked';
					    $checkExpLimit = ($data[$i]['e_explimit']==0)?'':'checked';
					    $checkNoKill = ($data[$i]['e_nokill']==0)?'':'checked';
					    $checkKillHP = ($data[$i]['e_killhp']==0)?'':'checked';
					    $checkBlackList = ($data[$i]['e_blacklist']==0)?'':'checked';
						
						$urlencname=urlencode($data[$i]['name']);
						$output.= "<tr onMouseOut='apaga(this);' onMouseOver='resalta(this);' style='cursor:pointer;'>";
					    $output.= "<td><input type=button value=X onclick=\"deleteItem('".$urlencname."',this);\"></td>";
					    $output.= "<td id=name$i>".$data[$i]['name']."</td>";
					    $output.= "<td id=level$i>".$data[$i]['level']."</td>";
					    $output.= "<td id=vocation$i>".$data[$i]['voc']."</td>";
					    $output.= "<td style='text-align:left;'>".(str_replace("'>","' target='_blank'>",$data[$i]['guild']))."</td>";
					    $output.= "<td id=comment$i style='text-align:left;'><input type=text value=\"".htmlentities($data[$i]['e_comment'])."\" style=\"width:200px;\" onblur='updateItem(0,this.value,\"".$urlencname."\",this);'></td>";
					    $output.= "<td id=issecchar$i><input type=checkbox $checkSecChar onClick='updateItem(1,this.checked,\"".$urlencname."\",this);'></td>";
					    $output.= "<td id=nokill$i class='nokill'><input type=checkbox style=\"border:4px none #ff0000;\" $checkNoKill onClick='updateItem(3,this.checked,\"".$urlencname."\",this);'></td>";
					    $output.= "<td id=killhp$i class='killhp'><input type=checkbox style=\"border:4px none #ff0000;\" $checkKillHP onClick='updateItem(4,this.checked,\"".$urlencname."\",this);'></td>";
					    $output.= "<td id=blacklist$i class='blacklist'><input type=checkbox style=\"border:4px none #ff0000;\" $checkBlackList onClick='updateItem(5,this.checked,\"".$urlencname."\",this);'></td>";
						$output.= "<td id=explimit$i class='explimit'><input type=checkbox style=\"border:4px none #ff0000;\" $checkExpLimit onClick='updateItem(2,this.checked,\"".$urlencname."\",this);' ></td>";
					    $output.= "</tr>\n\r";   
					}
			}
			else
			{
				$output.="DB empty or inaccessible.";
			}
			$output .= "</td></tr></table>";
		break;
		
		default:
			$pagetitle = "Wartool - enemy characters";
			$navpath = "<a href='./'>main menu</a> - enemy characters";
			$centerbox = 1;
			require("inc_head.php");
			if($charlist = $sql->get_full_charlist())
			{
				$help=array('D'=>'','S'=>'','P'=>'','K'=>'','N'=>'','OFF'=>'');
				$content=array($help,$help);
				unset($help);
				foreach($charlist as $key => $val)
				{		
					if($val['e_nokill']==1) $colorclass=" class='nokill'";
					elseif($val['e_killhp']==1) $colorclass=" class='killhp'";
					elseif($val['e_explimit']==1) $colorclass=" class='explimit'";
					elseif($val['e_blacklist']==1) $colorclass=" class='blacklist'";	
					else $colorclass='';
					$icon = ($val['oname']!=null)?"<img src='./img/b_green.gif'>":"<img src='./img/b_red.gif'>";
					$val['guild']=str_replace("'>","' target='_blank'>",$val['guild']);
					$line = "<tr".$colorclass.">\n<td>".$icon."</td><td><a href='http://www.tibia.com/community/?subtopic=character&amp;name=".urlencode($val['cname'])."'>".$val['cname']."</a></td>\n<td align='right'>".$val['level']."</td>\n<td>".$val['voc']."</td>\n<td>".$val['guild']."</td>\n<td>".$val['e_comment']."</td>\n</tr>\n";
					if($val['oname']!=null)
					switch($val['voc'])
					{
						case 'D':
						case 'ED':
							$content[$val['e_is_second']]['D'].=$line;
						break;
						case 'S':
						case 'MS':
							$content[$val['e_is_second']]['S'].=$line;
						break;
						case 'P':
						case 'RP':
							$content[$val['e_is_second']]['P'].=$line;
						break;
						case 'K':
						case 'EK':
							$content[$val['e_is_second']]['K'].=$line;
						break;
						case 'NO':
							$content[$val['e_is_second']]['N'].=$line;
						break;
					}
					else $content[$val['e_is_second']]['OFF'].=$line;
				}
				if($userlevel>0) $output .= "<a href='./'>main menu</a>\n ~ <a href='./mod_enem.php?view=deathlist'>death list</a>\n ~ <a href='./mod_enem.php?view=lvlchange'>level change</a>\n ~ <a href='./mod_enem.php?view=activity'>activity</a>\n<br>\n";
				else $output .= "<a href='./'>back</a>\n<br>\n";
				$output .= "<strong>Color Legend: <span class='nokill'>&nbsp;DON'T KILL&nbsp;</span> \n<span class='explimit'>&nbsp;ExpLimit, don't kill&nbsp;</span> \n<span class='killhp'>&nbsp;Kill ASAP&nbsp;</span> \n<span class='blacklist'>&nbsp;Not war related but free kill&nbsp;</span></strong>\n";
				$output .= "<table><tr><td align='left' valign='top' style='padding-right:50px;'>\n<table style='float:left; font-size:13px;' cellpadding='2' align='left'>\n<tr><th colspan='6' style='font-size:15px;' align='center'>MAINCHARS</th></tr>\n<th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['D']!='') $output.= "<tr><th colspan=6 align='left'>DRUIDS</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[0]['D']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['S']!='') $output.= "<tr><th colspan=6 align='left'>SORCERERS</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[0]['S']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['P']!='') $output.= "<tr><th colspan=6 align='left'>PALADINS</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[0]['P']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['K']!='') $output.= "<tr><th colspan=6 align='left'>KNIGHTS</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[0]['K']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['N']!='') $output.= "<tr><th colspan=6 align='left'>ROOKIES</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[0]['N']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if(($content[0]['D']!=''||$content[0]['S']!=''||$content[0]['P']!=''||$content[0]['K']!=''||$content[0]['N']!='')&&$content[0]['OFF']!='')
					$output .= "<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($userlevel>0) if($content[0]['OFF']!='') $output.= "<tr><th colspan=6 align='left'>OFFLINE</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[0]['OFF']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				
				$output .= "</table>\n</td><td valign='top' align='left'>\n<table style='float:right; font-size:13px;' cellpadding='2'>\n<tr><th colspan='6' style='font-size:15px;' align='center'>SECOND CHARS + SUPPORTERS</th></tr>\n<th colspan='6'>&nbsp;</th></tr>\n";
				if($content[1]['D']!='') $output.= "<tr align='left'><th colspan=6>DRUIDS</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[1]['D']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[1]['S']!='') $output.= "<tr align='left'><th colspan=6>SORCERERS</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[1]['S']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[1]['P']!='') $output.= "<tr align='left'><th colspan=6>PALADINS</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[1]['P']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[1]['K']!='') $output.= "<tr align='left'><th colspan=6>KNIGHTS</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[1]['K']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[1]['N']!='') $output.= "<tr align='left'><th colspan=6>ROOKIES</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[1]['N']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if(($content[1]['D']!=''||$content[1]['S']!=''||$content[1]['P']!=''||$content[1]['K']!=''||$content[1]['N']!='')&&$content[1]['OFF']!='')
					$output .= "<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($userlevel>0) if($content[1]['OFF']!='') $output.= "<tr align='left'><th colspan=6>OFFLINE</th></tr>\n<tr align='left'>\n<th></th>\n<th>name</th>\n<th>lvl</th>\n<th>voc</th>\n<th>guild</th>\n<th>comment</th>\n</tr>\n".$content[1]['OFF']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				$output .= "</td><tr><table>\n</table>\n";
			}
			else
			{
				$respfail = 1;
				$response = "Database inaccessible.";
				require("inc_head.php");
				$output .= "<strong>DATABASE INACCESSIBLE</strong><br>Click <a href='./'>here</a> to get back to the main menu.";
			}
		break;
	}
	
	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}
?>