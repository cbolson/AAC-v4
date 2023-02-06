<?php
/*
Script		: Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: Chris Bolson www.cbolson.com

File		: ac-check-install.inc.php
Date mod 	: 2023-01-01
Use			: Check that ac-install.php file has been deleted for security.
*/

$the_file=$_SERVER['DOCUMENT_ROOT'].'/ac-install.php';
if(file_exists($the_file)){
	if($is_ajax){
		returnError('2.01','The <a href="/ac-install.php'.'">install script</a> needs to be removed once you have installed the calendar.');
	}else{
		echo '
	<!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="UTF-8" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<title>Ajax Availability Calendar - Install</title>
			 <link rel="stylesheet" href="/ac-admin/assets/admin.css?v3" />
		</head>
		<body>
		<main>
			<header class="header">
				<div class="header__logo">
					<img src="/ac-assets/logo-acc.svg" title="Availability Calendar - Administration" width="200">
				</div>
				<h1 class="header__version">'.CAL_VERSION.'</h1>
			</header>
			<section>
				<h1>install script</h1>
				Once you have run the <a href="/ac-install.php">installation script</a> you must delete it to be able to continue.</section>
			<footer>
				<div>
					<a href="https://www.ajaxavailabilitycalendar.com/">Availability Calendar</a> developed by <a href="http://www.cbolson.com" target="_blank">Chris Bolson</a>
				</div>
				
				<div>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="5972777">
					<input type="image" src="/ac-assets/donate-paypal.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online." style="border:none; width:120px;">
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
?>