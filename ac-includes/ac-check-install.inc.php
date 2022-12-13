<?php
/*
added in version 3.0.06
Check that ac-install.php file has been deleted for security.
*/
$the_file=AC_ROOT."ac-install.php";
if(file_exists($the_file)){
	if($is_ajax){
		returnError('2.01',"install script exists");
	}else{
		echo '
		<!DOCTYPE html>
			<html>
				<head>
				<title>Ajax Availability Calendar - Remove Install</title>
				<style type="text/css">
					body{font-family:verdana;font-size:0.8em;}
					#wrapper{width:600px;margin:20px auto;border:1px solid #006699;}
					#header			{background:#006699; position:relative;}
					#header #slogan { position:absolute; left:90px; top:30px;color:#FFF; font-size:1.6em;margin:0;padding:0;}
					#contents{ padding:20px;}
					#footer{background: #EEE; clear:both; padding:10px; font-size:0.8em;}
				</style>
			</head>
			<body>
			<body id="page_install">
			<div id="wrapper">
				<div id="header">
					<img src="https://www.ajaxavailabilitycalendar.com/images/logo-icon.png" width="50" style="padding:10px 20px;"  stitle="Availability Calendar - Administration">
					<span id="slogan">Ajax Availability Calendar</span>
				</div>
				<div id="contents">Please remove the <strong>ac-install.php</strong> file.</div>
				
			</div>
		</body>
		</html>
		';
		exit();
	}
}
?>