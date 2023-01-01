<?php
/*
Script		: Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: Chris Bolson www.cbolson.com

File		: ac-check-install.inc.php
Date add	: 2021-09-27 (added in version 3.0.06)
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
					padding:.5rem 1rem;
					font-size:0.8em;
					display:flex;
					justify-content:space-between;
					align-items:center;
				}
			</style>
		</head>
		<body>
		<main>
			<header>
				<img src="/ac-assets/logo-acc.svg" title="Availability Calendar - Administration" width="200">
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
					<input type="image" src="/ac-assets/donate-paypal.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online." style="border:none; width:80px;">
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