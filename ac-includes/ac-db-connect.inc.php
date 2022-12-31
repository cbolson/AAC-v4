<?php
/*
script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
author		: 	Chris Bolson www.cbolson.com

file		: 	ac-db-connect.inc.php
use			: 	connect to database using variables defined in "config.inc.php"
instructions:	No need to modify this file other than to adjust error messages
*/
$error=false;

// connect to database - no need to adjust
if(!$db_cal = @mysqli_connect(AC_DB_HOST,AC_DB_USER,AC_DB_PASS,AC_DB_NAME)){
	$error='ERROR CONNECTING TO THE DATABASE';
}elseif(!mysqli_select_db($db_cal,AC_DB_NAME)){
	$error='ERROR SELECTING THE DATABASE TABLE';	
}

if($error){
	echo '
	<!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="UTF-8" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<title>Ajax Availability Calendar - Install</title>
			<style type="text/css">
				body{
					font-family:verdana;
					font-size:1rem;
				}
				main{
					max-width:600px;
					margin:20px auto;
					border:1px solid #006699;
					overflow:hidden;
				}
				header{
					background:#006699;
					padding:1rem;
				}
				section{
					padding:1rem;
				}
				footer{
					background:#EEE;
					padding:.5rem;
					font-size:0.8em;
					display:flex;
					justify-content:space-between;
					align-items:center;
				}
			</style>
		</head>
		<body>
		<body id="page_install">
		<main>
			<header>
				<img src="/ac-assets/logo-acc.svg" title="Availability Calendar - Administration" width="200">
			</header>
			<section>
				<h1>'.$error.'</h1>
				<br>The script has been unable to select the database table.
				<br>Please check that you have modified the <strong>ac-config.inc.php</strong> file with your data.
				<br>If you haven\'t yet setup your calendar, click <a href="/ac-install.php">here to run the install script.
			</section>
			<footer>
				<div>
					<a href="https://www.ajaxavailabilitycalendar.com/">Availability Calendar</a> developed by <a href="http://www.cbolson.com" target="_blank">Chris Bolson</a>
				</div>
				
				<div>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="5972777">
					<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online." style="border:none;">
					<img alt="" border="0" src="https://www.paypal.com/es_ES/i/scr/pixel.gif" width="1" height="1">
					</form>
				</div>
			</footer>
		</main>
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