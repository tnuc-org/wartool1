<?php
ini_set('date.timezone',"Europe/Berlin");

$global_title = "Wartool - unnamed";
$global_user = "USER NOT SET";
$global_navpath = "<a href='./'>main menu</a>";

$title = (isset($pagetitle))?$pagetitle:$global_title;
$nvuser = (isset($username))?$username:$global_user;
$nvpath = (isset($navpath))?$navpath:$global_navpath;
$jscript = (isset($javascript))?$javascript:"";
$centerbox = (isset($centerbox))?" align='center'":"";

if(isset($response))
{
	if(isset($respfail))
		$r = "<span class='response2'>" . date("G:i:s") . " ERROR: $response</span>\n";
	else
		$r = date("G:i:s") . " OK: $response\n";
}
else $r='';

$output = <<<EOD
<!-- oh hallo mr quirks -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"> 
<html>
<meta name="robots" content="none">
<link rel="stylesheet" type="text/css" href="./style.css">
<head>
<title>$title</title>
$jscript
</head>
<body>
<div id='website'>
<div id='navbar'>
Welcome, <strong>$nvuser</strong>.<br>
<strong>Navigation:</strong> $nvpath
</div>
<div id='resp'>$r</div>
<div id='content'$centerbox>


EOD;

//$output .= "</td>\n</tr>\n</table>\n<hr>\n";

/*
<body>
<div id='content'$centerbox>
<table width='100%'>
<tr>
<td align='left' valign=top>Welcome, <strong>$nvuser</strong>.<br>
<strong>Navigation:</strong> $nvpath
</td>
<td rowspan='2' id='resp' align='right' valign='bottom'>

EOD;
if(isset($response))
{
	if(isset($respfail))
		$output .= "<span class='response2'>" . date("G:i:s") . " ERROR: $response</span>\n";
	else
		$output .= "<span class='response1'>" . date("G:i:s") . " OK: $response</span>\n";
}
$output .= "</td>\n</tr>\n</table>\n<hr>\n";*/
?>