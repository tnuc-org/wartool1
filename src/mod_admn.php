<?php
require("Wsql.php");
require("Wsql_adm.php");
require("CookiMgr.php");
$sql = new Wsql_adm();
$cmgr = new CookiMgr();

$loggedin = false;
$username = false;
$view = false;
$output = "";
$perm_name = "perm_usermgr";

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
	if($userlevel<4) {header("Location: ./mod_deny.php"); exit(1);}
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	
	switch($view)
	{
		default:
			$pagetitle = "Wartool - admin cp";
			$navpath = "<a href='./'>main menu</a> - admin cp";
			$centerbox = 1;
			$data = $sql->get_ips_compact();
			if($data!==false)
			{
				require("inc_head.php");
				$cnt=$sql->get_unres();
				$output.="<a href='./'>back</a><br><br><b>Unresolved: $cnt</b><br><table border='0' cellpadding='2' cellspacing='1' style='border:1px solid black;'>";
				$alt=true;
				$prevname='';
				
				foreach($data as $k => $v)
				{
					if($v['name']!=$prevname) $alt=!$alt;
					$color=$alt?'white':'#F4F09B';
					$t=preg_match_all('~(<img.*>)~iU',$v['country'],$matches);
					$flag=(isset($matches[1][0])&&$matches[1][0]!='')?$matches[1][0]:'';
					$ipfield=($v['iphigh']==$v['iplow'])?"<td colspan='2' align='center'>".$v['iplow']."</td>":"<td align='center'>".$v['iplow']."</td><td align='center'>".$v['iphigh']."</td>";
					$datefield=($v['lastseen']==$v['firstseen'])?"<td align='center'>".$v['lastseen']."</td>":"<td align='center'>".$v['firstseen']." - ".$v['lastseen']."</td>";
					$forum=($v['forum']==1)?'F':'';
					$wartool=($v['wartool']==1)?'W':'';
					$output.="<tr bgcolor='$color'><td>".$v['name']."</td><td>$flag</td><td>".$v['country']."</td>".$ipfield.$datefield."<td>".$forum."</td><td>".$wartool."</td><tr>";
					$prevname=$v['name'];
				}
				$output.="</table>";
			}
			else
			{
				$response="DB Inaccessible. <.<";
				$respfail=1;
				require("inc_head.php");
				$output.="<h1>db fuxed</h1>";
			}
		break;
	}
	
	$sql = null;
	require("inc_foot.php");
	echo $output;
}
else {$sql = null; header("Location: ./");}
?>