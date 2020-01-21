<?php
require("Wsql.php");
require("Wsql_our.php");
require("CookiMgr.php");
$sql = new Wsql_our();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";
$perm_name = "perm_ourchars";

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
		;
	}
	
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	switch($view)
	{
		case 'mod':
			if($userlevel<3) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - alliance characters - mod tool";
			$navpath = "<a href='./'>main menu</a> - <a href='./mod_ours.php'>alliance characters</a> - mod tool";
			$javascript = "<script src=\"ajax.js\"></script>";
			$centerbox = 1;
			require("inc_head.php");
			$output.="<a href='./mod_ours.php'>back</a>\n<br><br>\n";
			$output.="new char &gt; no owner<br>new char &gt; user<br>take ownership<br>not our char anymore<br>";
		break;
		
		default:
			if($userlevel==0) header("Location: ./mod_deny.php");
			$pagetitle = "Wartool - alliance characters";
			$navpath = "<a href='./'>main menu</a> - alliance characters";
			$javascript = "<script src=\"ajax.js\"></script>";
			$centerbox = 1;
			require("inc_head.php");
			if($charlist = $sql->get_full_charlist())
			{
				$help=array('D'=>'','S'=>'','P'=>'','K'=>'','N'=>'','OFF'=>'');
				$content=array($help,$help);
				unset($help);
				foreach($charlist as $key => $val)
				{		
					$icon = ($val['oname']!==null)?"<img src='./img/b_green.gif'>":"<img src='./img/b_red.gif'>";
					
					//if($val['guild']!==null) $val['guild']=($val['o_hidden']==0||$val['o_is_second']==0)?str_replace("'>","' target='_blank'>",$val['guild']):'';
					if($val['guild']!==null) $val['guild']=($val['o_is_second']==0)?str_replace("'>","' target='_blank'>",$val['guild']):'';
					//$name=($val['o_hidden']==0||$val['o_is_second']==0)?"<a href='http://www.tibia.com/community/?subtopic=character&amp;name=".urlencode($val['cname'])."' target='_blank'>".$val['cname']."</a>":"-name hidden-";
					$name=($val['o_is_second']==0)?"<a href='http://www.tibia.com/community/?subtopic=character&amp;name=".urlencode($val['cname'])."' target='_blank'>".$val['cname']."</a>":"-name hidden-";
					$sder=($val['o_is_second']==0)?'':(($val['level']>=54&&in_array($val['voc'],array('D','ED')))?"<td align='center'><img src='./img/para.gif'></td>\n":(($val['level']>=45&&in_array($val['voc'],array('D','ED','S','MS')))?"<td align='center'><img src='./img/sd.gif'></td>\n":"<td></td>\n"));
					//$level=($val['o_hidden']==0)?$val['level']:"??";
					$level=($val['o_is_second']==0)?$val['level']:"??";
					//if($val['o_hidden']==1) $val['voc']='NO';
					if($val['o_is_second']==1) $val['voc']='NO';
					$line = "<tr align='left' onmouseover='resalta(this);' onmouseout='apaga(this);'>\n$sder<td>".$icon."</td><td>$name</td>\n<td>".$val['o_owner']."</td>\n<td align='right'>$level</td>\n<td>".$val['voc']."</td>\n<td>".$val['guild']."</td>\n</tr>\n";
					if($val['oname']!==null)
					switch($val['voc'])
					{
						case 'D':
						case 'ED':
							$content[$val['o_is_second']]['D'].=$line;
						break;
						case 'S':
						case 'MS':
							$content[$val['o_is_second']]['S'].=$line;
						break;
						case 'P':
						case 'RP':
							$content[$val['o_is_second']]['P'].=$line;
						break;
						case 'K':
						case 'EK':
							$content[$val['o_is_second']]['K'].=$line;
						break;
						case 'NO':
							$content[$val['o_is_second']]['N'].=$line;
						break;
					}
					else $content[$val['o_is_second']]['OFF'].=$line;
				}
				$output .= "<a href='./'>back</a>\n<br><br>\n";
				$output .= "<table><tr><td align='left' valign='top' style='padding-right:50px;'>\n<table style='float:left; font-size:13px;' cellpadding='2' align='left'>\n<tr><th colspan='6' style='font-size:15px;' align='center'>MAIN CHARS</th></tr>\n<th colspan='6'>&nbsp;</th></tr>\n";
				$hdr="<tr align='left'>\n<th></th>\n<th>name</th>\n<th>owner</th>\n<th align='right'>lvl</th>\n<th>voc</th>\n<th>guild</th>\n</tr>\n";
				if($content[0]['D']!='') $output.= "<tr><th colspan=6 align='left'>DRUIDS</th></tr>\n$hdr".$content[0]['D']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['S']!='') $output.= "<tr><th colspan=6 align='left'>SORCERERS</th></tr>\n$hdr".$content[0]['S']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['P']!='') $output.= "<tr><th colspan=6 align='left'>PALADINS</th></tr>\n$hdr".$content[0]['P']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['K']!='') $output.= "<tr><th colspan=6 align='left'>KNIGHTS</th></tr>\n$hdr".$content[0]['K']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($content[0]['N']!='') $output.= "<tr><th colspan=6 align='left'>ROOKIES</th></tr>\n$hdr".$content[0]['N']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if(($content[0]['D']!=''||$content[0]['S']!=''||$content[0]['P']!=''||$content[0]['K']!=''||$content[0]['N']!='')&&$content[0]['OFF']!='')
					$output .= "<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($userlevel>1) if($content[0]['OFF']!='') $output.= "<tr><th colspan=6 align='left'>OFFLINE</th></tr>\n$hdr".$content[0]['OFF']."<tr><th colspan='6'>&nbsp;</th></tr>\n";
				
				$hdr="<tr align='left'>\n<th></th><th></th>\n<th>name</th>\n<th>owner</th>\n<th align='right'>lvl</th>\n<th>voc</th>\n<th>guild</th>\n</tr>\n";
				$output .= "</table>\n</td><td valign='top' align='left'>\n<table style='float:right; font-size:13px;' cellpadding='2'>\n<tr><th colspan='7' style='font-size:15px;' align='center'>SECOND CHARS</th></tr>\n<th colspan='6'>&nbsp;</th></tr>\n";
				if($content[1]['D']!='') $output.= "<tr align='left'><th colspan=7>DRUIDS</th></tr>\n$hdr".$content[1]['D']."<tr><th colspan='7'>&nbsp;</th></tr>\n";
				if($content[1]['S']!='') $output.= "<tr align='left'><th colspan=7>SORCERERS</th></tr>\n$hdr".$content[1]['S']."<tr><th colspan='7'>&nbsp;</th></tr>\n";
				if($content[1]['P']!='') $output.= "<tr align='left'><th colspan=7>PALADINS</th></tr>\n$hdr".$content[1]['P']."<tr><th colspan='7'>&nbsp;</th></tr>\n";
				if($content[1]['K']!='') $output.= "<tr align='left'><th colspan=7>KNIGHTS</th></tr>\n$hdr".$content[1]['K']."<tr><th colspan='7'>&nbsp;</th></tr>\n";
				if($content[1]['N']!='') $output.= "<tr align='left'><th colspan=7>ROOKIES AND HIDDEN CHARS</th></tr>\n$hdr".$content[1]['N']."<tr><th colspan='7'>&nbsp;</th></tr>\n";
				if(($content[1]['D']!=''||$content[1]['S']!=''||$content[1]['P']!=''||$content[1]['K']!=''||$content[1]['N']!='')&&$content[1]['OFF']!='')
					$output .= "<tr><th colspan='6'>&nbsp;</th></tr>\n";
				if($userlevel>1) if($content[1]['OFF']!='') $output.= "<tr align='left'><th colspan='7'>OFFLINE</th></tr>\n$hdr".$content[1]['OFF']."<tr><th colspan='7'>&nbsp;</th></tr>\n";
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