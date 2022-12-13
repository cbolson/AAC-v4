<?php
/*
script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
author		: 	Chris Bolson www.cbolson.com

file		: 	db_connect.inc.php
use			: 	connect to database using variables defined in "config.inc.php"
instructions:	No need to modify this file other than to adjust error messages
*/
$error=false;

// connect to database - no need to adjust
if(!$db_cal = mysqli_connect(AC_DB_HOST,AC_DB_USER,AC_DB_PASS,AC_DB_NAME)){
	$error='ERROR CONNECTING TO THE DATABASE';
}elseif(!mysqli_select_db($db_cal,AC_DB_NAME)){
	$error='ERROR SELECTING THE DATABASE TABLE';	
}

if($error){
	echo '
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
	<html>
		<head>
			<title>Ajax Availability Calendar - Install</title>
			<style type="text/css">
				body{font-family:verdana;font-size:0.8em;}
				#wrapper{width:600px;margin:20px auto;border:1px solid #006699;}
				#header{background:#006699;}
				#contents{ padding:20px;}
				#footer{background: #EEE; clear:both; padding:10px; font-size:0.8em;}
			</style>
		</head>
		<body>
		<body id="page_install">
		<div id="wrapper">
			<div id="header">
				<img src="http://www.ajaxavailabilitycalendar.com/images/logo_aac.png" title="Availability Calendar - Administration">
			</div>
			<div id="contents">
				<h3>'.$error.'</h3>
				<br>The script has been unable to select the database table.
				<br>Please check that you have modified the <strong>ac-config.inc.php</strong> file with your data.
				<br>If you haven\'t yet setup your calendar, click <a href="ac-install.php">here to run the install script.
			</div>
			<div id="footer">
				<div style="float:right;">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="5972777">
					<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online." style="border:none;">
					<img alt="" border="0" src="https://www.paypal.com/es_ES/i/scr/pixel.gif" width="1" height="1">
					</form>
				</div>
				<ul>
					<li><a href="http://www.ajaxavailabilitycalendar.com/">Availability Calendar</a> developed by <a href="http://www.cbolson.com" target="_blank">Chris Bolson</a></li>
					
				</ul>
			</div>
		</div>
	</body>
	</html>
	';
	exit;
}


// error message function (can't include in general functions file as it may be needed before that file is included)
function returnError($error_id,$error_msg){
	global $is_ajax;
	if($is_ajax){
		$error["error"]=array(
			"msg"	=> "".$error_msg."",
			"code"	=> "".$error_id.""
		);
		die(json_encode($error));
	}else{
		die($error_msg.'<br>[ error: '.$error_code.']');
	}
}


// check that ac-install has been deleted
$the_file=AC_INCLUDES_ROOT."ac-check-install.inc.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);
?>