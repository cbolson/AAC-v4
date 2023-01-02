<?php
/*
script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
author		: 	Chris Bolson www.cbolson.com

file		: 	ac-db-connect.inc.php
use			: 	connect to database using variables defined in "config.inc.php"
instructions:	No need to modify this file other than to adjust error messages
*/
$error=false;



// error message function (can't include in general functions file as it may be needed before that file is included)
if(!function_exists("returnError")){
	function returnError($error_id,$error_msg){
		global $is_ajax;
		if($is_ajax){
			$error["error"]=array(
				"msg"	=> "".$error_msg."",
				"code"	=> "".$error_id.""
			);
			die(json_encode($error));
		}else{
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
						<h1>Error: '.$error_id.'</h1>
						'.$error_msg.'
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
	}
}



// check that ac-install has been deleted
if($_SERVER["REQUEST_URI"]!="/ac-install.php"){
	$the_file=$_SERVER['DOCUMENT_ROOT'].'/ac-includes/ac-check-install.inc.php';
	if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
	else		require_once($the_file);
}
// connect to database - no need to adjust
if(!$db_cal = @mysqli_connect(AC_DB_HOST,AC_DB_USER,AC_DB_PASS,AC_DB_NAME)){
	returnError('1.01','ERROR CONNECTING TO THE DATABASE');
}elseif(!mysqli_select_db($db_cal,AC_DB_NAME)){
	returnError('1.02','ERROR SELECTING THE DATABASE TABLE');	
}
?>