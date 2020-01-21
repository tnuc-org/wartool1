<?php
require("Wsql.php");
require("Wsql_add.php");
require("CookiMgr.php");
$sql = new Wsql_add();
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
	if($userlevel==0) header("Location: ./mod_deny.php");
	
	$view = (isset($_GET['view']))?$_GET['view']:'1';
	switch($view)
	{
		default:
			$pagetitle = "Wartool - add enemy character";
			$navpath = "<a href='./'>main menu</a> - add enemy character";
			$centerbox = 1;
			$javascript = "<script src=\"ajax.js\"></script>";
			$data = $sql->get_request_list();
			if($data!==false)
			{
				require("inc_head.php");
				$output .= "<table width='100%'><tr><th>ADD REQUESTS<br>(suggest chars to be added to the wartool)</th><th>EDIT REQUESTS<br>(suggest changes to chars already ";
				$output .= "on the wartool)</th></tr><tr><td valign='top' align='center'>";
				// START add requests
				
				$output .= "<table style='margin-top:30px;' cellspacing='20'>";
				$output .= "<tr align='left'><td><div>Add a character:</div><input type='text' value=''><div style='display:inline;'>&nbsp;name<br></div><input type='text' value='' maxlength='35'>";
				$output .= "<div style='display:inline;'>&nbsp;comment<br></div><input type='button' value=' post request ' onclick='addReq(0,this);'></td></tr>";
				foreach($data[0] as $kname => $vals)
				{
					$enc=urlencode($kname);
					$output .= "<tr align='left'><td>";
					if($userlevel>=3)
					{
						$output.="<div onclick='showPanel(this);'>[<span style='color:#0000ff;'>menu</span>]</div>";
						$output.="<div style='display:none;'>";
						$output.="<img src='./img/b_blue.gif' onClick='processReq(\"".$enc."\",0,0,0,this)'> ";
						$output.="<img src='./img/b_green.gif' onClick='processReq(\"".$enc."\",0,0,1,this)'> ";
						$output.="<img src='./img/b_red.gif' onClick='processReq(\"".$enc."\",0,0,2,this)'> ";
						$output.="<img src='./img/b_black.gif' onClick='processReq(\"".$enc."\",0,0,3,this)'> ";
						$output.="<img src='./img/b_yellow.gif' onClick='processReq(\"".$enc."\",0,0,4,this)'> add main<br>";
						$output.="<img src='./img/b_blue.gif' onClick='processReq(\"".$enc."\",0,1,0,this)'> ";
						$output.="<img src='./img/b_green.gif' onClick='processReq(\"".$enc."\",0,1,1,this)'> ";
						$output.="<img src='./img/b_red.gif' onClick='processReq(\"".$enc."\",0,1,2,this)'> ";
						$output.="<img src='./img/b_black.gif' onClick='processReq(\"".$enc."\",0,1,3,this)'> ";
						$output.="<img src='./img/b_yellow.gif' onClick='processReq(\"".$enc."\",0,1,4,this)'> add second<br>";
						$output.="<img src='./img/b_cross.gif' onClick='declineReq(\"".$enc."\",this)'> decline request";
						$output.="</div>";
					}
					else $output.="<span></span><span></span>";
					$output .= "<div><u><b>".$kname."</b> (".$vals[0]." ".$vals[1].$vals[2].")</u>";
					for($i=0;$i<count($vals[3]);$i+=1)
					{
						$output.="<br><i>".$vals[3][$i][0].", ".$vals[3][$i][1]."</i> &#187; ".((substr($vals[3][$i][2],0,2)=="a$")?"<span class='admmsg'>".substr(htmlentities($vals[3][$i][2]),2,strlen($vals[3][$i][2])-2)."</span>":htmlentities($vals[3][$i][2]));
					}
					$output.="</div>";
					$output.="<div onclick='showComment(this);'>[<span style='color:#0000ff;'>post comment</span>]</div>";
					$output.="<div style='display:none;'><input type='text' value='' maxlength='40'><input type='button' value='post' onclick='commentReq(\"".$enc."\",0,this);'></div>";
					$output.="</td></tr>";
				}
				$output .= "</table>";
				
				// END add requests
				$output .= "</td><td valign='top' align='center'>";
				// START edit requests
				
				$output .= "<table style='margin-top:30px;' cellspacing='20'>";
				$output .= "<tr align='left'><td><div>Edit a character:</div><input type='text' value=''><div style='display:inline;'>&nbsp;name<br></div><input type='text' value='' maxlength='35'>";
				$output .= "<div style='display:inline;'>&nbsp;comment<br></div><input type='button' value=' post request ' onclick='addReq(1,this);'></td></tr>";
				foreach($data[1] as $kname => $vals)
				{
					$enc=urlencode($kname);
					$output .= "<tr align='left'><td>";
					if($userlevel>=3)
					{
						$output.="<div onclick='showPanel(this);'>[<span style='color:#0000ff;'>menu</span>]</div>";
						$output.="<div style='display:none;'>";
						$output.="<img src='./img/b_blue.gif' onClick='processReq(\"".$enc."\",1,0,0,this)'> ";
						$output.="<img src='./img/b_green.gif' onClick='processReq(\"".$enc."\",1,0,1,this)'> ";
						$output.="<img src='./img/b_red.gif' onClick='processReq(\"".$enc."\",1,0,2,this)'> ";
						$output.="<img src='./img/b_black.gif' onClick='processReq(\"".$enc."\",1,0,3,this)'> ";
						$output.="<img src='./img/b_yellow.gif' onClick='processReq(\"".$enc."\",1,0,4,this)'> add main<br>";
						$output.="<img src='./img/b_blue.gif' onClick='processReq(\"".$enc."\",1,1,0,this)'> ";
						$output.="<img src='./img/b_green.gif' onClick='processReq(\"".$enc."\",1,1,1,this)'> ";
						$output.="<img src='./img/b_red.gif' onClick='processReq(\"".$enc."\",1,1,2,this)'> ";
						$output.="<img src='./img/b_black.gif' onClick='processReq(\"".$enc."\",1,1,3,this)'> ";
						$output.="<img src='./img/b_yellow.gif' onClick='processReq(\"".$enc."\",1,1,4,this)'> add second<br>";
						$output.="<img src='./img/b_cross.gif' onClick='declineReq(\"".$enc."\",this)'> decline request";
						$output.="</div>";
					}
					else $output.="<span></span><span></span>";
					$output .= "<div><u><b>".$kname."</b> (".$vals[0]." ".$vals[1].$vals[2].")</u>";
					for($i=0;$i<count($vals[3]);$i+=1)
					{
						$output.="<br><i>".$vals[3][$i][0].", ".$vals[3][$i][1]."</i> &#187; ".((substr($vals[3][$i][2],0,2)=="a$")?"<span class='admmsg'>".substr(htmlentities($vals[3][$i][2]),2,strlen($vals[3][$i][2])-2)."</span>":htmlentities($vals[3][$i][2]));
					}
					$output.="</div>";
					$output.="<div onclick='showComment(this);'>[<span style='color:#0000ff;'>post comment</span>]</div>";
					$output.="<div style='display:none;'><input type='text' value='' maxlength='40'><input type='button' value='post' onclick='commentReq(\"".$enc."\",1,this);'></div>";
					$output.="</td></tr>";
				}
				$output .= "</table>";
				
				// END edit requests
				$output .= "</td></tr></table>";
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