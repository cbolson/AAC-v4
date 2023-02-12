<?php
/*
Script		: Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: Chris Bolson www.cbolson.com

File		: settings.ajax.php
Date mod	: 2023-01-04
Use			: get calendar settings such as styles and texts to be sent to js file for calendar settings
*/
session_start();

// get vars sent (GET)
$lang = isset($_GET["lang"]) ? $_GET["lang"] : "en";
define("AC_LANG",$lang);


// include common file (db connection, functions etc.)
$inc_translations	= true;
$inc_functions		= true;
$the_file=dirname(__FILE__)."/common.ajax.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);

// define array settings
$arr_settings=[];

// define texts used in calendar (define as array as we may add more texts later as required)
$arr_settings["texts"]=array(
	"today"					=> $ac_lang["today"],
	"next"					=> $ac_lang["next"],
	"back"					=> $ac_lang["back"],
	"day_1" 				=> $ac_lang["cal_day_1"],
	"day_2" 				=> $ac_lang["cal_day_2"],
	"day_3" 				=> $ac_lang["cal_day_3"],
	"day_4" 				=> $ac_lang["cal_day_4"],
	"day_5" 				=> $ac_lang["cal_day_5"],
	"day_6" 				=> $ac_lang["cal_day_6"],
	"day_7" 				=> $ac_lang["cal_day_7"],
	"min_nights" 			=> str_replace("{x}","".AC_MIN_NIGHTS."",$ac_lang["alert_min_nughts"]),
	"end_before_start" 		=> $ac_lang["alert_end_before_start"],
	"dates_not_available" 	=> $ac_lang["alert_dates_not_avail"]
);
$arr_settings["min_nights"] =AC_MIN_NIGHTS;
if(isset($_SESSION["admin"])){
	$arr_settings["min_nights"] =0; # allow single date selection for admin
}else{
	$arr_settings["min_nights"] =AC_MIN_NIGHTS;
}
// convert serialized styles to array ($row_config is defined in common.inc.php)
$styles = unserialize($row_config["styles"]);
foreach($styles AS $key=>$val){
	if(substr($key,-7)=="-radius") $val=$val."px";
	$arr_settings["styles"][]=array(
		"name"	=> $key, 
		"val"	=> $val
	);
}
// return settings as json
echo json_encode($arr_settings);
?>