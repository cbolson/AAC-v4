<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Wuthor		: 	Chris Bolson www.cbolson.com

File		: 	calendar.ajax.php
Date		: 	2021-10-13
Use			: 	Called via ajax to draw calendar months
Variables	: 	"id_item"	- id of the item
				"startDate"	- date to be used to calculate when to start the calendar
				"numMonths"	- number of months to show
				"lang"		- calendar language
				"direction"	- direction to send calendar (back, next, today, current=month currently shown for resize)
*/	

// slow down to show loading so that the user can see that new months have been loaded (optional)
// sleep(0.5); 
// print_r($_GET);
// // get content type sent via AJAX (fetch)
// $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
// if ($contentType === "application/json") {
// 	//Receive the RAW post data.
// 	$content 	= trim(file_get_contents("php://input"));
// 	$input_data = json_decode($content, true);
// }else{
// 	die("KO - no data");
// }

$input_data = $_GET;

$startDate 	= (!empty($input_data['startDate'])) 	? $input_data['startDate'] 	: date('Y-m-d');
$numMonths 	= (!empty($input_data['numMonths'])) 	? $input_data['numMonths'] 	: 3;
$direction	= (!empty($input_data['direction'])) 	? $input_data['direction'] 	: "today";
$user_lang	= (!empty($input_data['lang'])) 		? $input_data['lang'] 		: "".AC_DEFAULT_AC_LANG."";
define("AC_LANG",$user_lang);


// include common file (db connection, functions etc.)
$inc_functions		= true;
$inc_translations	= true;
$the_file=dirname(__FILE__)."/common.ajax.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);

// get vars sent or define detault if empty (eg start date)
// NOTE this is defined AFTER we have included the common file as we may need the returnError function
$id_item 	= (!empty($input_data['id_item'])) 		? $input_data['id_item']	: returnError('3.01','No id item set');



// set local lang to get built-in php day names etc.
setlocale(LC_ALL, "".$user_lang."_".strtoupper($user_lang).".UTF-8");



// define start month depending if we are going forwards or backwards from current
switch($direction){
	case "back"		:	$startDate	= date('Y-m-d', strtotime($startDate. ' - '.$numMonths.' month'));	break; # date sent MINUS number of months
	case "next"		:	$startDate	= date('Y-m-d', strtotime($startDate. ' + '.$numMonths.' month'));	break; # date sent PLUS number of months
	case "current"	:	$startDate	= $startDate;	break; # reloading with currently defined start month (eg window resize)
	default			:
	case " "	: 	$startDate	= date('Y-m-d'); break;
}

// define date given as array
$d 			= date_parse_from_format("Y-m-d", $startDate);
$start_month= $d["month"];
$start_year	= $d["year"];

// array to be returned as JSON
$data				= array();
$data["start-date"]	= $start_year.'-'.$start_month.'-01'; # define start month for JAVASCRIPT to know where to calculate dates to send

// define week day titles
/*
for ($i=1;$i<=7;++$i) {
	$data["weekdays"][] = strftime('%a ', mktime(0, 0, 0, 6, $i+2, 2013));
}
*/

// get array of ALL dates in db within dates given (only call the db once)
$arr_dates_booked	= getBookings($id_item,$start_month,$start_year,$numMonths,AC_LANG);


// create the calendar
$thisMonth	= $start_month;
$thisYear	= $start_year;
for($i=0;$i<$numMonths;++$i){
	// draw calendar for each month defined
	$data["months"][]=drawCalJSON($thisMonth,$thisYear,$arr_dates_booked);
	
	// add one to month - change year if greater than 12
	if($thisMonth==12){
		$thisMonth=01;
		++$thisYear;
	}else{
		++$thisMonth;
	}
}
echo json_encode($data);
?>