<?php
if(isset($_POST["add"])){
	// TO DO - add data validation

	$id_item 	= $_POST["id_item"];
	$date_start = $_POST["add"]["date-start"];
	$date_end 	= $_POST["add"]["date-end"];

	// inert booking
	$insert = "INSERT INTO ".AC_TBL_BOOKINGS." SET
	id_user		= '".$_SESSION["admin"]["id"]."',
	id_item  	= '".mysqli_real_escape_string($db_cal,$id_item)."',
	date_add 	= CURDATE(),
	date_start	= '".mysqli_real_escape_string($db_cal,$date_start)."',
	date_end	= '".mysqli_real_escape_string($db_cal,$date_end)."',
	name		= '".mysqli_real_escape_string($db_cal,$_POST["add"]["name"])."',
	email		= '".mysqli_real_escape_string($db_cal,$_POST["add"]["email"])."',
	tel			= '".mysqli_real_escape_string($db_cal,$_POST["add"]["tel"])."',
	comments	= '".mysqli_real_escape_string($db_cal,$_POST["add"]["comments"])."' 
	";
	//echo $insert;
	// if(mysqli_query($db_cal,$insert)){
	// 	$msg=$ac_lang["booking_add_OK"];
	// 	$msg_type="ok";

	// }else{
	// 	//echo mysqli_error($db_cal);
	// 	$msg='Error: 3.01<br>'.$ac_lang["booking_add_KO"];
	// 	$msg_type="alert";
	// }
	//exit();
	
	// update calendar to store booked dates
	// Ideally we really don't need two systems to save the dates - I should really get the calendar date states from this db table
	
	// NO - think that we will have to continue to save all the dates so that wehn we come to displaying the calendar we can check each month date - 
	//for example how would be "know" if a date is avaulable if a booking crosses a complete month
	




	// $startDate = new \DateTime($date_start);
	// $endDate = new \DateTime($date_end);
	/*
	Booking states
	0 = available (no state saved)
	1 = booked pm (start)
	2 = booked
	3 = booked -am (end)

	// what to do if date is already set as end/start date for separate booking?
	options:
		1. set as booked (1)
		2. create new state
		3. save am or pm state and end up with 2 dates for this date - is this really a problem?
			- is this actually an issue?
			- makes deleting a current booking easy as we don't have to check other dates
			- would require changes to calendar availability code....
	*/
	for($date = $startDate; $date <= $endDate; $date->modify('+1 day')){
		$tmp_date 	= $date->format("Y-m-d");
		$new_state 	= 1;
		if($tmp_date==$date_start) {
			// check if this date is already set as an end date
			$new_state = 2;
		}else if($tmp_date==$date_end){
			// check if date is start date of another booking
		}else{
			
			$new_state = 2;
			
		}
		echo "<br>".$new_state;
		$insert="INSERT into ".AC_TBL_AVAIL." SET
		id_item 	= ".$id_item.",
		id_booking 	= ".$id_booking.",
		id_state 	= ".$new_state.",
		the_date 	= '".$tmp_date."'
		";
		echo "<br>".$insert;
	}

}


// temp testing code

// $sql="
// SELECT * FROM ".AC_TBL_BOOKINGS." WHERE 
// id_item = 1 
// AND MONTH(date_start) = 3 || MONTH(date_end)
// ";

//SELECT room_id FROM rooms WHEN room_id NOT IN (SELECT room_id FROM reserveTable WHERE date BETWEEN @StartDate AND @EndDate)


// create array of curent user items
$arr_user_items=[];

// create array of user item ids to verify that they have permission and to show default calendar
$sql="
SELECT 
	t.id
FROM 
	".AC_TBL_ITEMS." AS t 
WHERE 
	t.state=1 
	".$sql_cond_user."
";
$res=mysqli_query($db_cal,$sql) or die("Error - list items");
while($row=mysqli_fetch_assoc($res)){
	$arr_user_items[]=$row["id"];
}

// define calendar item to show - get or first item in array
$id_item = isset($_REQUEST["id_item"]) ? $_REQUEST["id_item"] : $arr_user_items[0];

//	get list of calendar items
$list_items=selectListItems($id_item,$sql_cond_user);

if(count($arr_user_items)==0){
	//	no items in db
	$contents='
	<div class="msg info">
		'.$ac_lang["msg_add_item"].'
		<br><br>
		<a href="?p=items&action=new" class="pseudo-button">'.icon("plus").' '.$ac_lang["add"].'</a>
	</div>
	';
}else{
	
	if( isset($_REQUEST["id_item"]) && !in_array($_REQUEST["id_item"],$arr_user_items) ){
		// this is NOT a user calender
		$msg		= $ac_lang["msg_item_not_exist"];
		$msg_type	= 'alert';
	}else{
		$contents.='
		<form method="POST">
		<div class="block">
			<div class="block-inner">
				<form>
					<input type="hidden" name="p" value="'.AC_PAGE.'">
					'.fieldRow($ac_lang["item_to_show"],'id_item','<select name="id_item" onchange="this.form.submit();">'.$list_items.'</select>').'
				</form>
				<ac-calendar
						id="my-cal"
						ac-id="'.$id_item.'"
						ac-lang="en"
						ac-date-start="date-start"
						ac-date-end="date-end"
						ac-months-to-show="0"
						>
					</ac-calendar>
			</div>
			<div id="block-booking" style="margin-top:10px;">
				<h2>_Reserve dates_</h2>
				'.fieldRow($ac_lang["dates"] 	, ''		, '
					<input type="date" 	id="date-start" name="add[date-start]" value="" style="max-width:200px;" required>
					<input type="date" id="date-end" name="add[date-end]"  value=""style="max-width:200px;" required>
				').'
				'.fieldRowButton('<button type="button" class="button button--small button--green" toggle-id="block-full-booking">'.$ac_lang["toggle_show_booking_details"].'</button>').'
				<div id="block-full-booking" class="hidden">
					'.fieldRow($ac_lang["name"] 	, 'name'	, '<input type="text" 	id="name"	name="add[name]"" disabled>').'
					'.fieldRow($ac_lang["email"] 	, 'email'	, '<input type="email" 	id="email"	name="add[email]"" disabled>').'
					'.fieldRow($ac_lang["tel"] 		, 'tel'		, '<input type="text" 	id="tel"	name="add[tel]"" disabled>').'
					'.fieldRow($ac_lang["comments"] , 'tel'		, '<textarea 			id="comments"	name="add[comments]"" style="width:100%; height:90px;" disabled></textarea>').'
				</div>
			</div>
			<div class="block-buttons">
				<input type="submit" value="'.$ac_lang["save"].'" />
			</div>
		</div>
		</form>
		
		';

		$block_xtra_js='
		<script>
		// define path for ajax files BEFORE including the calendar JavaScript file
		let urlRoot="'.AC_URL.'";
		</script>
		';
	}	
	if(!empty($id_item)){
		
		$xtra_js_files='
		<script src="'.AC_URL.'ac-js/ac-calendar.js" id="ac-cal" type="module" ac-container="demo-calendar" ac-item="'.$id_item.'" ac-dateStart="date-start" ac-dateEnd="date-end"></script>
		';	
	}
}