<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	settings.ajax.php
Date		: 	2021-09-24
Use			: 	general admin ajax file
*/

$admin_only			= true;	# define to verifiy that admin session is active
$is_ajax			= true; # define to let other files that this is an ajax call (eg for error messages which need to be json)
$inc_languages		= false;
$inc_functions		= false;
$inc_translations	= false;


// include common file (db connection, functions etc.)
$the_file=dirname(__FILE__)."/common.ajax.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);

// get content type sent via AJAX (fecth)
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === "application/json") {
	//Receive the RAW post data.
	$content = trim(file_get_contents("php://input"));
	
	$input_data = json_decode($content, true);
	//print_r($input_data);
	
	if(! is_array($input_data)) {
		echo "KO - no data";
		exit();
	} else {
		$action=$input_data["action"];
		if(empty($action)) die("KO - no action");
		
		
		
		switch($action){
			case "mod-state":
				// update state of item semt
				
				// get params sent
				$item_type 	= !empty($input_data["type"]) 		? $input_data["type"] 	: die("KO - no type");
				$item_id	= is_numeric($input_data["id"]) 	? $input_data["id"] 	: die("KO - no id");
				
				$field	= 'state'; # some tables might have a different field for the state
				// define table and field for update - also define more states in specific cases
				switch($item_type){
					case "items":		$tbl = AC_TBL_ITEMS;		break;
					case "users":		$tbl = AC_TBL_USERS;		break;
					case "texts":		$tbl = AC_TBL_TEXTS;		break;
					case "languages":	$tbl = AC_TBL_LANGUAGES;	break;
					
				}
				// get current state - we are not sending the state via post to ensure that we get the current state and to simplify the code
				$sql="SELECT  ".$field." FROM ".$tbl." WHERE id='".$item_id."'";
				$res=mysqli_query($db_cal,$sql) or die("KO - current state");
				$row=mysqli_fetch_assoc($res);
				
				// define new state - default options are 1 (active) or 0 (deactive)
				if($row["state"]==1)	$new_state_id=0;
				else 					$new_state_id=1;
				
				// update table with new state then return new state - no need to complicate this with JSON
				$update="UPDATE ".$tbl." SET ".$field."='".$new_state_id."' WHERE id='".$item_id."' LIMIT 1";
				if(!mysqli_query($db_cal, $update))	die("KO - db update");
				else 								echo $new_state_id;
				exit();
				
				break;
				
				
			case "list-order":
				// change order of items
				echo "dev....";
				brea;
		}
	}
}
?>