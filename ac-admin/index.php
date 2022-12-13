<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	index.php (admin)
Use			: 	main admin index page to include admin page and common elements.
*/	
ob_start();
session_start();

// define vars
$block_nav			= '';
$block_msg			= '';
$block_contents		= '';
$block_footer		= '';
$msg_type			= '';
$page_buttons		= '';
$list_items			= '';
$sql_cond_user		= '';
$admin_page_permit	= true;

$inc_languages		= true;
$inc_functions		= true;
$inc_translations	= true;

//	include - general config
$the_file="../ac-config.inc.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);

// include - common vars
$the_file=AC_INCLUDES_ROOT."ac-common.inc.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);


// define page warning message if sent via url (eg after item edit)
if(isset($_REQUEST["msg"])){
	$msg.=$ac_lang["msg_".$_REQUEST["msg"].""];
	// get message type to show alert color
	$msg_end=substr($_REQUEST["msg"], -3);
	switch($msg_end){
		case "_OK": 	$msg_type='OK';		break;
		case "_KO":		$msg_type='alert';	break;
	}
}

//	define page to show
if(!isset($_SESSION["admin"]["id"])){
	if(isset($_GET["p"])&& $_GET["p"]=="login-reminder"){
		$page="login-reminder";
	}else{
		$page="login";
	}
}else{
	if(isset($_REQUEST["p"])) 	$page=$_REQUEST["p"];
	else						$page="items";			# default start page
}

define("AC_PAGE", $page);

// define page to include
switch(AC_PAGE){
	case "login":
		// login
		$body_id 		= 'login';
		$ac_admin_page	= AC_DIR_ADMIN."ac-login.php";
		break;
		
	case "login-reminder";
		// password reminder
		$body_id 		= 'login';
		$ac_admin_page	= AC_DIR_ADMIN."ac-login-reminder.php";
		break;
	
	default:
		// all other admin pages
		$body_id 		= AC_PAGE;
		$ac_admin_page	= AC_DIR_ADMIN."ac-".AC_PAGE.".php";
		
		//	define condition for users
		if($_SESSION["admin"]["level"]>1){
			// limit items table to only get user defined items
			$sql_cond_user=" AND t.id_user=".$_SESSION["admin"]["id"];
		}
		
		// include nav BEFORE admin page
		$the_file		= AC_DIR_ADMIN."ac-nav.inc.php";
		if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
		else		require_once($the_file);
			
		// define page title and buttons (defined in ac-nav.inc.php)
		$block_title='
		<div class="block-title">
			<h1>'.$page_title.'</h1>
			<div class="block-title-buttons">'.$page_buttons.'</div>
		</div>
		';
}

// include the admin page if user has permission
if($admin_page_permit){
	if(!file_exists($ac_admin_page)) die("<b>".$ac_admin_page."</b> not found");
	else		require_once($ac_admin_page);
}
		
// block - page message		
if(isset($msg)){
	$block_msg='
	<div class="msg '.$msg_type.'">
		'.$msg.'
	</div>
	';
}
// block - contents
if(!empty($contents)){
	$block_contents='
	<main>
		'.$block_title.'
		'.$contents.'
	</main>
	';
}
// block - extra js code
if(!empty($xtra_js)){
	$block_xtra_js='
	<script>
		'.$xtra_js.'
	</script>
	';
}

$block_footer='
<footer>
	<ul>
		<li><a href="https://www.ajaxavailabilitycalendar.com" target="_blank">Ajax Availability Calendar</a></li>
		<li id="version">'.CAL_VERSION.'</li>
		<li>by <a href="https://www.cbolson.com" target="_blank">cbolson</a></li>
	</nav>
</footer>
';

echo '
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="user-scalable=yes, width=device-width">
		<title>Admin : '.$page_title.' | Ajax Availability Calendar</title>
		<link rel="stylesheet" href="assets/admin.css?v='.time().'">
	</head>
	<body id="'.$body_id.'">
		<header>
			<div id="logo"><img src="'.AC_LOGO.'" title="Availability Calendar - Admin" width="300"></div>
			'.$block_nav.'
		</header>
		'.$block_msg.'
		'.$block_contents.'
		'.$block_footer.'
		<script defer src="assets/svgxuse.min.js"></script>
		<script defer src="assets/ac-functions.js?'.time().'"></script>
		'.$xtra_js_files.'
		'.$block_xtra_js.'
	</body>
</html>
';
ob_end_flush();
?>