<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Wuthor		: 	Chris Bolson www.cbolson.com

File		: 	common.ajax.php
Date		: 	2021-09-27
Use			: 	Inlcuded in all ajax files to connect to database etc.
				Defines settings, connects to db, includes common files
*/

// error reporting
//error_reporting(E_ALL ^ E_NOTICE);
//ini_set("display_errors", 1); 

//	activate this to prevent the url from being accessed via the url
if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
	//	only allow ajax requests - no calling from the url
	//header("location",$_SERVER["DOCUMENT_ROOT"]);
}

if(isset($admin_only)){
	//	some ajax pages should only be reached via the admin panel
	session_start();
	//	check only admin allowed -  no direct calls
	if(!isset($_SESSION["admin"]["id"])){
		die("KO - no permission");
	}

}
//	check we are getting fresh info and define charset
header("Cache-Control: private, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Pragma: private");
header('Content-type: text/html; charset=utf-8');

$is_ajax=true; # define to let other files that this is an ajax call (eg for error messages which need to be json)

//	general config
$the_file="../ac-config.inc.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);
		
//	db connection
$the_file=AC_INCLUDES_ROOT."ac-db-connect.inc.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);
	
//	common vars (db and lang)
$the_file=AC_INCLUDES_ROOT."ac-common.inc.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);
//	define language - NEEDED ?????
//if(!isset($lang)) $lang=AC_DEFAULT_AC_LANG;
//define("AC_LANG", $lang);
?>