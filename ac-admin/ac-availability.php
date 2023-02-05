<?php

// create array of curent user items
$arr_user_items=[];

// create array of user item ids to verify that they have permision and to show default calendar
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
		<div class="block">
			<div class="block-inner">
				<form>
					<input type="hidden" name="p" value="'.AC_PAGE.'">
					'.fieldRow($ac_lang["item_to_show"],'id_item','<select name="id_item" onchange="this.form.submit();">'.$list_items.'</select>').'
				</form>
				<ac-calendar
						id="my-cal"
						ac-id="2"
						ac-lang="en"
						ac-date-start="date-start"
						ac-date-end="date-end"
						ac-months-to-show="0"
						>
					</ac-calendar>
			</div>
			<div id="block-booking" style="margin-top:10px;">
				<h2>_Reserve dates_</h2>
				'.fieldRow($ac_lang["dates"] 	, ''		, '<input type="date" 	id="date-start" name="add[date-start]" value="" style="max-width:200px;" readonly="readonly"><input type="date" id="date-end" name="add[date-end]"  value=""style="max-width:200px;" readonly="readonly">').'
				'.fieldRow($ac_lang["name"] 	, 'name'	, '<input type="text" 	id="name"	name="add[name]"">').'
				'.fieldRow($ac_lang["email"] 	, 'email'	, '<input type="email" 	id="email"	name="add[email]"">').'
				'.fieldRow($ac_lang["tel"] 		, 'tel'		, '<input type="text" 	id="tel"	name="add[tel]"">').'
				'.fieldRow($ac_lang["comments"] , 'tel'		, '<textarea 			id="comments"	name="add[comments]"" style="width:100%; height:90px;"></textarea>').'
				'.fieldRowButton('<input type="submit" value="'.$ac_lang["save"].'" disabled="disabled" />').'			
			</div>
		</div>
		
		
		
		
		
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