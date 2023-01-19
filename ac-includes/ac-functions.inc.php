<?php
/*
Script		: Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: Chris Bolson www.cbolson.com

File		: functions.inc.php
Date		: 2021-09-20
Date mod	: 2023-01-04
Use			: common functions for all pages
*/

// set local time for newer php installations that require it (function at bottom of page)
//date_default_timezone_set(getLocalTimezone());

// util - readable array output
if(!function_exists("print_arr")){
	function print_arr($arr){
		echo "<pre>";
		print_r($arr);
		echo "</pre>";
	}
}

// convert date to a string - this way we don't need to worry about diferences in internation date display
if(!function_exists("date2string")){
	function date2string($date){
		return strftime("%A %e %B, %Y", strtotime($date));  
	}
}
// ensure date is correctly formated for date calulations and db storage yyyy-mm-dd 
if(!function_exists("date2db")){	
	function date2db($year,$month,$this_day_counter){
		return $year."-".sprintf("%02s",$month)."-".sprintf("%02s",$this_day_counter);
	}
}
// get weekday number (1-7)
if(!function_exists("getDateDayNumber")){	
	function getDateDayNumber($date_db){
		 return date("N", strtotime($date_db));
	}
}
// get total number of days in month
if(!function_exists("getTotalMonthDays")){
	function getTotalMonthDays($month,$year){
		return cal_days_in_month(CAL_GREGORIAN, $month, $year);
		//return date("t", strtotime($date_db));
	}
}

// get booked dates from db between given dates (date + num months)
if(!function_exists("getBookings")){
	function getBookings(
		$id_item,
		$start_month,
		$start_year,
		$num_months=1,
		$tmp_lang=AC_DEFAULT_LANG
	){
		global $db_cal;
		
		//	get bookings for this month and item from database
		$booked_days=array();
		$sql = "
		SELECT 
			t1.the_date,
			t2.class,
			t2.id AS id_state
		FROM 
			".AC_TBL_AVAIL." AS t1
			LEFT JOIN ".AC_TBL_STATES." AS t2 ON t2.id=t1.id_state
		WHERE 
			t1.id_item				= ".mysqli_real_escape_string($db_cal,$id_item)." 
			AND t1.the_date BETWEEN ('01-".$start_month."-".$start_year."') AND ('01-".$start_month."-".$start_year."'  + INTERVAL ".$num_months." MONTH)	
		";
		/*
			AND MONTH(t1.the_date)	= ".mysqli_real_escape_string($db_cal,$month)." 
			AND YEAR(t1.the_date)	= ".mysqli_real_escape_string($db_cal,$year)."
		*/
		//echo $sql;
		//exit();
		$res=mysqli_query($db_cal,$sql) or die("ERROR checking id item availability dates");
		while($row=mysqli_fetch_assoc($res)){
			$booked_days[$row["the_date"]]=array(
				"class"=>$row["class"],
				"state"=>$row["id_state"]
			);
		}
		return $booked_days;
	}
}

// create month view from given month & year and show date states
if(!function_exists("drawCalJSON")){
	function drawCalJSON(
		$start_month,
		$start_year,
		$arr_dates_booked = []
	){
		global $ac_lang;
		
		// arr - weekend day numbers
		$arr_weekend= array(6,7); # The ISO-8601 numeric representation of a day (1 for Monday, 7 for Sunday)
		
		// arr - data to be returned
		$arr_month				= array();
		
		// ensure month number is 2 digits
		$start_month			= sprintf("%02s",$start_month);

		// db format start date
		$start_month_db			= date2db($start_year,$start_month,1);
		
		// get month name
		//$start_month_name		= dateMonth($start_month_db);
		// I have opted to use admin defined month names to allow them to personalise more rather than depending on server config
		$start_month_name		= $ac_lang["cal_month_".$start_month.""].' '.$start_year;
		
		// get first weekday (1-7) of month sent
		$start_month_start_day 	= getDateDayNumber($start_month_db);
		
		// get total number of days in month
		$start_month_num_days 	= getTotalMonthDays($start_month,$start_year);
		
		// add month basic data to array (month num not needed but keep it in case)
		$arr_month	= array(
			"month_num"		=> $start_month,
			"month_title"	=> $start_month_name,
			"days"			=> array()
		);
		
		// counters for day and week filling
		$week_row_counter		= 0;
		$week_day				= 1;
	
		//	Fill the first week of the month with the appropriate number of blanks - ONLY if the start day is not "1" (monday)
		if($start_month_start_day>1){
			// define last month number and year
			$last_month	= $start_month-1;
			if($last_month<1){
				$last_month	= 12;
				$last_year	= $start_year-1;	
			}else{
				$last_year	= $start_year;
			}
			
			// number of days in previous month
			$last_month_last_day = getTotalMonthDays($last_month,$last_year);
			
			// previous monday number to start loop
			$last_month_start_num= date('d', strtotime('previous monday', strtotime($start_month_db)));
			
			for($k = $last_month_start_num; $k <= $last_month_last_day; ++$k){  
				// format date as string 
				$date_str=date2string($last_year.'-'.$last_month.'-'.$last_month_start_num);
				// add date to array
				$arr_month["days"][]=array(
					'n'	=> $last_month_start_num,
					'd'	=> ''.$date_str.'',
					's'	=> '',
					'c'	=> array('empty')
				);
				++$last_month_start_num;
				++$week_day;
			}
		}
		
		//	define defined month days (til max in month) to draw calendar
		for($this_day_counter = 1; $this_day_counter <= $start_month_num_days; $this_day_counter++){
			//	reset xtra classes for each day
			//	note - these classes acumulate for each day according to state, current and clickable
			$this_day_classes	= 'available,';
			$this_date_state	= '';
			
			
			//	turn date into timestamp for comparison with current timestamp (defined as constant in common.inc.php)
			$date_timestamp =   mktime(0,0,0, $start_month,($this_day_counter),$start_year);
			
			//	format date for db modifying - the date is passed via ajax
			$date_db	=	date2db($start_year,$start_month,$this_day_counter);
			
			// default date classes (eg. today, past)
			if($date_timestamp==CUR_DATE){
				// current date
				$this_day_classes.='today,';
			}elseif($date_timestamp<CUR_DATE){
				// past date
				$this_day_classes.='past,';	#add "past" class to be modified via mootools if required
			}
			
			//Get the day of the week using PHP's date function.
			$day_of_week = getDateDayNumber($date_db);
			if(in_array($day_of_week,$arr_weekend)){
				// weekend
				$this_day_classes.='weekend,';
			}
			
			//	add date state if retrieved from db
			if(array_key_exists($date_db,$arr_dates_booked)){
				$this_day_classes.=$arr_dates_booked[$date_db]["state"].',';
				$this_date_state	= $arr_dates_booked["state"];
			}
		
			//TESTING
			
			// if($start_month==date("m", strtotime('+2 months'))){
			// 	//if($this_day_counter==12){
			// 	if( ($this_day_counter >11) && ($this_day_counter <14)){
			// 		$this_day_classes.='booked,';
			// 		$this_date_state='BOOKED';
			// 	}
			// 	if($this_day_counter==14){
			// 		$this_day_classes.='booked-am,';
			// 		$this_date_state='BOOKED pm';
			// 	}
			// 	if($this_day_counter==11){
			// 		$this_day_classes.='booked-pm,';
			// 	}
			// }
			
			
			
			
			// return date classes as array
			if(!empty($this_day_classes)){
				$this_day_classes=explode(',',trim($this_day_classes,','));
			}
			$arr_month["days"][]=array(
				'n'	=> ''.$this_day_counter.'',		# this date number
				'df'=> ''.$date_db.'',			# db date format
				'ds'=> ''.date2string($date_db).'', # format date as string 
				's'	=> ''.$this_date_state.'',		# local lang date state (from db)
				'c'	=> $this_day_classes			# date css classes
			);
			
			if($week_day % 7==0){
				// reset weekdays
				$week_day=1;
				
				// add week row counter (we need to always have 6 rows)
				++$week_row_counter;
			}else{
				++$week_day;
			}
		}
	
		//	add empty rows and days till end of max number of rows (6)
		$next_month_day	= 1;
		$next_month		= $start_month+1;
		if($next_month>12){
			$next_month	= 1;
			$next_year	= $start_year+1;	
		}else{
			$next_year=$start_year;
		}
		while($week_row_counter<6){
			//add days until we have 6 rows
			while($week_day<=7){
				// ad next month day to array
				$date_str=date2string($next_year.'-'.$next_month.'-'.$next_month_day);
				$arr_month["days"][]=array(
					'n'	=> $next_month_day,
					'df'	=> ''.$date_db.'',
					'ds'	=> ''.$date_str.'',
					's'	=> '',
					'c'	=> array('empty')
				);
				++$next_month_day;  
				++$week_day;
			}
			// reset week day number for next row
			$week_day=1;
			// add 1 to week rows
			++$week_row_counter;	
		}
		
		// return month array
		return $arr_month;
	}
}

//	get calendar items for select list
function selectListItems($id_item_current,$sql_cond=''){
	global $db_cal;
	$list_items='';
	$sql="
	SELECT 
		t.id, 
		tt.txt AS item_name 
	FROM 
		".AC_TBL_ITEMS." AS t 
		LEFT JOIN ".AC_TBL_TRANSLATIONS." AS tt ON tt.id_text=t.id 	AND tt.type='item'	AND tt.langcode='".AC_LANG."'
	WHERE 
		t.state=1 
		".$sql_cond."
	ORDER BY tt.txt ASC
	";
	$res=mysqli_query($db_cal,$sql) or die("Error - list items");
	while($row=mysqli_fetch_assoc($res)){
		$list_items.='<option value="'.$row["id"].'"';
		if($row["id"]==$id_item_current) $list_items.=' selected="selected"';
		$list_items.='>'.$row["item_name"].'</option>';
	}
	return $list_items;
}

/*
function lastUpdate($id_item){
	global $db_cal,$lang;
	if(AC_DATE_DISPLAY_FORMAT=="us")	$date_str	= "%m-%d-%Y";
	else 								$date_str	= "%d-%m-%Y";
	
	$sql="SELECT DATE_FORMAT(date_mod, '%b %d, %Y') as date_mod FROM `".T_BOOKING_UPDATE."` WHERE id_item=".mysqli_real_escape_string($db_cal,$id_item)."";
	$res=mysqli_query($db_cal,$sql) or die("error getting last calendar update date");
	if(mysqli_num_rows($res)==0){
		return '';
	}else{
		$row=mysqli_fetch_assoc($res);
		return $lang["last_update"]." <strong>".$row["date_mod"].'</strong>';
	}
}
*/






/*******************  ADMIN FUNCTIONS ***********************/


// convet array to select list options
if(!function_exists("selectListOptions")){
	function selectListOptions($arr,$selected_id=""){
		//print_arr($arr);
		//echo "<br>: ".$selected_id;
		$list_items='';
		foreach($arr AS $arr_id => $arr_desc){
			$list_items.='<option value="'.$arr_id.'"';
			if($arr_id==$selected_id) $list_items.=' selected="selected"';
			$list_items.='>'.$arr_desc.'</option>';
		}
		return $list_items;
	}
}




function selectListNumbers($start,$end,$num){
	$list_numbers='';
	for($k=$start;$k<=$end;$k++){
		$list_numbers.='<option value="'.$k.'"';
		if($k==$num) $list_numbers.=' selected="selected"';
		$list_numbers.='>'.$k.'</option>';
	}
	return $list_numbers;
}

// html email
if(!function_exists("htmlEmail")){
	function htmlEmail($from_email,$from_name,$to_email,$subject,$html_msg){
		global $lang,$logo_email;
		$htmlContent = ' 
		<html> 
			<head> 
				<title>Ajax Availability Calendar</title> 
			</head> 
			<body>
				<div style="width:80%; max-width:340px; margin:10px auto;">
					<div style="text-align:center; background:#006699; padding:20px; margin-bottom:20px; border-radius:10px;">
						<img src="'.AC_LOGO.'" width="200">
					</div>
					'.$html_msg.'
				</div>
			</body> 
		</html>
		'; 
		 
		//echo $htmlContent;
		//exit();
		// Set content-type header for sending HTML email 
		$headers = "MIME-Version: 1.0" . "\r\n"; 
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
		 
		// Additional headers 
		//$headers .= 'From: '.$from_name.'<'.$from_email.'>' . "\r\n"; 
		//$headers .= 'Cc: welcome@example.com' . "\r\n"; 
		//$headers .= 'Bcc: welcome2@example.com' . "\r\n"; 
		 
		// Send email 
		if(@mail($to_email, $subject, $htmlContent, $headers)){ 
		   return true;
		}else{ 
		   return false;
		}
	}
}

// get single text translations - mainly used for emails when comunicating with users
if(!function_exists("getTextLocal")){
	function getTextLocal($txt_code,$txt_lang,$txt_type="texts",$return_default=true){
		global $db_cal;
		if(empty($txt_code)) return "no text code or id";
		if(empty($txt_lang)) return "no lang code";
		// get local lang text
		
		//define which base table to get text (texts, languages or items)
		switch($txt_type){
			case "texts": 	
				$tbl=AC_TBL_TEXTS;	
				$sql_cond="t.code='".mysqli_real_escape_string($db_cal,$txt_code)."'";	
				break;
			case "lang"	:	
				$tbl=AC_TBL_LANGUAGES;	
				$sql_cond="t.code='".mysqli_real_escape_string($db_cal,$txt_code)."'";	
				break;
				
			case "item"	:	
				$tbl=AC_TBL_ITEMS;
				$sql_cond="t.id='".mysqli_real_escape_string($db_cal,$txt_code)."'";
				break;
		}
		
		$sql="
		SELECT 
			tt.txt,
			ttd.txt AS default_txt
		FROM 
			".$tbl." AS t
			LEFT JOIN ".AC_TBL_TRANSLATIONS." AS tt 	ON tt.id_text=t.id 	AND tt.type='".$txt_type."'		AND tt.langcode='".$txt_lang."'
			LEFT JOIN ".AC_TBL_TRANSLATIONS." AS ttd 	ON ttd.id_text=t.id AND ttd.type='".$txt_type."'	AND ttd.langcode='en'
		WHERE 
			".$sql_cond."
		LIMIT 1
		";
		//echo $sql;
		$res=mysqli_query($db_cal,$sql) or die("Error - text local");
		if(mysqli_num_rows($res)==0){
			$txt='_text_not_found_';
		}else{
			$row=mysqli_fetch_assoc($res);
			if($return_default){
				if(!empty($row["txt"]))		$txt = $row["txt"];
				else 						$txt = '_'.$row["default_txt"]; # show english text
			}else{
				// only return local version  - used in admin to check if text has been translated
				$txt = $row["txt"];
			}
		}
		return $txt;
	}
}

//	active states
if(!function_exists("activeState")){
	function activeState($state,$id,$type,$field='state'){
		global $icons,$lang;
		if($state==1) 		$icon=icon("checkmark","green");
		elseif($state==0) 	$icon=icon("cross","red");
		elseif($state==2) 	$icon=icon("pending");
		
		return '<span class="update-state" data-id="'.$id.'" data-type="'.$type.'" data-state="'.$state.'"  title="'.$lang["click_update_state"].'">'.$icon.'</a>';
	}
}

//	get item
if(!function_exists("getItem")){
	function getItem($table,$id,$sql_condition=""){
		global $db_cal;
		$sql="SELECT t.* FROM ".$table." AS t WHERE t.id='".mysqli_real_escape_string($db_cal,$id)."' $sql_condition LIMIT 1";
		$res=mysqli_query($db_cal,$sql) or die("Error getting item.<br>".mysqli_error($db_cal));
		if(mysqli_num_rows($res)==0) 	return false;
		else 							return mysqli_fetch_assoc($res);
	}
}

//	get last order number
if(!function_exists("getNextOrder")){
	function getNextOrder($table){
		global $db_cal;
		$sql="SELECT list_order FROM ".$table." WHERE state=1 ORDER BY list_order DESC";
		$res=mysqli_query($db_cal,$sql) or die("Error getting highest list order");
		$row=mysqli_fetch_assoc($res);
		return ($row["list_order"]+1);
	}
}

//	add item
if(!function_exists("addItem")){
	function addItem($table,$values,$debug=false){
		global $db_cal;
		$add_data="";
		foreach($values AS $field=>$val){
			if($field=="password") 	$add_data.="`".$field."` = md5('".$val."'),";
			else 					$add_data.="`".$field."` = '".mysqli_real_escape_string($db_cal,$val)."',";
		}
		$add_data=substr($add_data,0,-1);
		$add="INSERT INTO `".$table."` SET ".$add_data."";
		if($debug) echo $add."<br>";
		if(mysqli_query($db_cal,$add)) 	return true;
		else{
			if($debug) echo "<br>".mysqli_error($db_cal);
			return false;
		}
	}
}

//	modify item
if(!function_exists("modItem")){
	function modItem($table,$id_item,$values,$debug=false){
		global $db_cal;
		$mod_data="";
		foreach($values AS $field=>$val){
			if($field=="password" && $val!="") 	$mod_data.="`".$field."` = md5('".$val."'),";
			else 								$mod_data.="`".$field."` = '".mysqli_real_escape_string($db_cal,$val)."',";
		}
		$mod_data=substr($mod_data,0,-1);
		$update="UPDATE `".$table."` SET ".$mod_data." WHERE id='".mysqli_real_escape_string($db_cal,$id_item)."' LIMIT 1";
		if($debug) echo $update."<br>";
		if(mysqli_query($db_cal,$update)) 	return true;
		else{
			if($debug) echo "<br>".mysqli_error($db_cal);
			return false;
		}
	}
}

// delete item
if(!function_exists("deleteItem")){
	function deleteItem($table,$id,$debug=false){
		global $db_cal;
		$del="DELETE FROM ".$table." WHERE id='".mysqli_real_escape_string($db_cal,$id)."' LIMIT 1";
		if($debug) echo $del."<br>";
		if(mysqli_query($db_cal,$del)) 	return true;
		else 					return false;
	}
}

// multi_array_key_exists function.
if(!function_exists("multi_array_key_exists")){
	function multi_array_key_exists( $needle, $haystack ) {
		foreach ( $haystack as $key => $value ) :
			if ( $needle == $key )
	            return true;
	        if ( is_array( $value ) ) :
	             if ( multi_array_key_exists( $needle, $value ) == true )
	                return true;
	             else
	                 continue;
	        endif;
	    endforeach;
	    return false;
	}
}
// create random code
if(!function_exists("createCode")){
	function createCode($n) {
		$characters 	= '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $randomString = '';
		for ($i = 0; $i < $n; $i++) {
	        $index = rand(0, strlen($characters) - 1);
	        $randomString .= $characters[$index];
	    }return $randomString;
	    // alternative method using php function uniqid() - I don't use this as it create codes of 13 characters which seems to be overkill for the calendar needs
	    //return uniqid();  
	}
}
// return SVG icon (icon definition must exist in symbol-defs.svg)
if(!function_exists("icon")){
	function icon($icon,$color='',$size=''){
		// uses icons created on https://icomoon.io
		// icons SVG definition 
		if(empty($icon)) return '-';
		$icon_style 		= '';
		$icon_class_size	= '';
		
		if(!empty($color)) 	$icon_style = ' style="color:'.$color.'"';
		if(!empty($size))	$icon_class_size =' icon-'.$size;
		return '<svg class="icon icon-'.$icon.$icon_class_size.'"'.$icon_style.'><use xlink:href="assets/symbol-defs.svg#icon-'.$icon.'"></use></svg>';
	}
}
// return form field row with label
if(!function_exists("fieldRow")){
	function fieldRow($label,$field_id,$field,$field_class=''){
		$row='
		<div class="field-row'.$field_class.'">
			<label for="'.$field_id.'">'.$label.':</label>
			'.$field.'
		</div>
		';
		return $row;
	}
}
// return table row with label and data
if(!function_exists("fieldRowData")){
	function fieldRowData($label,$field){
		$row='
		<div class="field-row">
			<label for="'.$field_id.'">'.$label.':</label>
			<div class="field-data">'.$field.'</div>
		</div>
		';
		return $row;
	}
}
// return form button
if(!function_exists("fieldRowButton")){
	function fieldRowButton($bt){
		$row='
		<div class="field-row">
			'.$bt.'
		</div>
		';
		return $row;
	}
}
// return warning message
if(!function_exists("blockMsg")){
	function blockMsg($msg,$msg_class){
		$data='
		<div class="block-msg '.$msg_class.'">
			'.$msg.'
		</div>
		';
		return $data;
	}
}
?>