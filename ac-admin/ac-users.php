<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-users.php
Use			: 	list, edit and add USERS
*/	


//	define admin page table
$this_table=AC_TBL_USERS;
		
//	delete item
if(isset($_POST["delete_it"])){
	$item_id	= $_POST["id"];
	
	//	see if user has items
	$sql="SELECT id FROM ".AC_TBL_ITEMS." WHERE id_user=".mysqli_real_escape_string($db_cal,$item_id)."";
	$res=mysqli_query($db_cal,$sql);
	while($row=mysqli_fetch_assoc($res)){
		// if user has items - delete calemdar availability
		$del="DELETE FROM ".AC_TBL_AVAIL." WHERE id_item=".mysqli_real_escape_string($db_cal,$row["id"])." LIMIT 1";
		mysqli_query($db_cal,$del) or die("Error deelting item bookings");
	}
	
	//	delete calendar items
	$del="DELETE FROM ".AC_TBL_ITEMS." WHERE id_user=".mysqli_real_escape_string($db_cal,$item_id)."";
	mysqli_query($db_cal,$del) or die("Error deleting items");
	
	//	delete user (only once all items have been deleted
	if(deleteItem($this_table,$item_id))	header("Location:index.php?p=".AC_PAGE."&msg=delete_OK");
	else 									$warning=$ac_lang["msg_delete_KO"];
	
}

//	modify item
if(isset($_POST["mod"])){
	if (!filter_var($_POST["mod"]["email"], FILTER_VALIDATE_EMAIL)) {
		$msg		= $ac_lang["msg_email_KO"];
		$msg_type	= 'alert';
	}
	
	if(!empty($_POST["password"])){

		if($_POST["password"]!=$_POST["password-repeat"]){
			$msg		= $ac_lang["msg_password_KO"];
			$msg_type	= 'alert';
		}
		//	add password to array - this is not in the array as it is need for formchecking
		$_POST["mod"]["password"]=$_POST["password"]; 
	}
	
	if(modItem($this_table,$_POST["id"],$_POST["mod"],false))	header("Location:index.php?p=".AC_PAGE."&id_mod=".$_POST["id"]."&msg=mod_OK");
	else 														$warning=$ac_lang["msg_mod_KO"];

}

//	add new item
if(isset($_POST["add"])){
	if (!filter_var($_POST["add"]["email"], FILTER_VALIDATE_EMAIL)) {
		$msg		= $ac_lang["msg_email_KO"];
		$msg_type	= 'alert';
	}
	
	if(empty($_POST["password"])){
		$msg		= $ac_lang["msg_password_KO"];
		$msg_type	= 'alert';
	}else if($_POST["password"]!=$_POST["password-repeat"]){
		$msg		= $ac_lang["msg_password_KO"];
		$msg_type	= 'alert';
	}else{
		//	add password to array - this is not in the array as it is need for formchecking
		$_POST["add"]["password"]=$_POST["password"]; 
		
		if(addItem($this_table,$_POST["add"],false)) 			header("Location:index.php?p=".AC_PAGE."&id_add=".mysqli_insert_id($db_cal)."&msg=add_OK");
		else 													$warning=$ac_lang["msg_add_KO"];
	}
	
	
}


if(isset($_REQUEST["action"])){
	switch($_REQUEST["action"]){
		case "new":
			$contents.='
			<div class="block">
				<form method="POST">
					'.fieldRow($ac_lang["level"]			, 'level'		, '<select id="level" name="add["level"]" style="width:300px;">'.selectListOptions($admin_levels,2).'</select><span class="note">'.$ac_lang["note_admin_level"].'</span>').'
					'.fieldRow($ac_lang["email"]			, 'email'		, '<input type="email" id="email" name="add[email]" 			required>').'
					'.fieldRow($ac_lang["password"]			, 'password'	, '<input type="password" id="password" name="password" 		required autocomplete="off" minlength="8" maxlength="15">').'
					'.fieldRow($ac_lang["password_repeat"]	, 'password2'	, '<input type="password" id="password" name="password-repeat"  required autocomplete="off" minlength="8" maxlength="15" placeholder="'.$ac_lang["note_password_repeat"].'">').'
					'.fieldRowButton('<input type="submit" value="'.$ac_lang["add"].'">').'
				</form>
			</div>
			';
			break;
		case "edit":
			$item_id 	= $_REQUEST["id"];
			//	get item data
			$row=getItem($this_table,$item_id);
			
			if($item_id==1){
				// main admin can't change level
				$row_level	= '';
				$bt_delete	= '';
			}else{
				$row_level=fieldRow($ac_lang["level"]			, 'level'		, '<select id="level" name="mod["level"]" style="width:240px;">'.selectListOptions($admin_levels,$row["level"]).'</select><span class="note">'.$ac_lang["note_admin_level"].'</span>');
				$bt_delete=' <a href="?p='.AC_PAGE.'&action=delete&id='.$item_id.'" title="'.$ac_lang["delete"].'" class="icon-button">'.icon("bin").'</a>';
			}
			
			$contents.='
			<div class="block">
				<form method="POST">
				<input type="hidden" name="id" value="'.$item_id.'"> 
					'.$row_level.'
					'.fieldRow($ac_lang["language"]			, 'default_lang', '<select name="mod[default_lang]" style="width:240px;">'.selectListOptions($ac_languages,$row["default_lang"]).'</select>').'
					'.fieldRow($ac_lang["email"]			, 'email'		, '<input type="email" id="email" name="mod[email]" value="'.$row["email"].'" required>').'
					'.fieldRow($ac_lang["password"]			, 'password'	, '<input type="password" id="password" name="password" 		autocomplete="off" minlength="8" maxlength="15" size="15" placeholder="'.$ac_lang["note_password_mod"].'">').'
					'.fieldRow($ac_lang["password_repeat"]	, 'password2'	, '<input type="password" id="password" name="password-repeat"  autocomplete="off" minlength="8" maxlength="15" size="15" placeholder="'.$ac_lang["note_password_repeat"].'">').'
					'.fieldRowButton('<input type="submit" value="'.$ac_lang["save"].'">'.$bt_delete.'').'
				</form>
			</div>
			';
			break;
		case "delete":
			$item_id 	= $_REQUEST["id"];
			
			//	get item details
			if(!$row=getItem($this_table,$item_id,$sql_cond_user)){
				//	item doesn't exist (or user doesn't have permission to see)
				$msg.=$ac_lang["msg_item_not_exist"];
				$msg_type='alert';
			}elseif($item_id==1){
				$msg='You can not delete the main admin account';		
				$msg_type='alert';
			}else{
				$contents.='
				<div class="block">
					<form method="post" onSubmit="return confirm(\''.$ac_lang["msg_delete_confirm"].'\');">
						<input type="hidden" name="delete_it" value="1">
						<input type="hidden" name="id" value="'.$item_id.'"> 
						'.fieldRowData($ac_lang["id"]	,$row["id"]).'
						'.fieldRowData($ac_lang["email"],$row["email"]).'
						'.fieldRowButton('<input type="submit" value="'.$ac_lang["delete"].'">').'
					</form>
				</div>
				';
			}
			break;
	}
}else{

	// default order
	$order_by='id';
	
	if(isset($_GET["o"])){
		switch($_GET["o"]){
			case "email"	: $order_by='email';	break;
			case "level" 	: $order_by='level';	break;
		}
	}

	$sql="SELECT * FROM ".$this_table." ORDER BY ".$order_by." ASC";
	$res=mysqli_query($db_cal,$sql) or die("Error getting states<br>".mysqli_error($db_cal));
	$cols=3;
	$list_items="";
	while($row=mysqli_fetch_assoc($res)){	$row_class='';
		$row_class='';
		
		if($row["id"]==$_GET["id_mod"]){
			if($msg_end=="_KO")	$row_class 		= 'class="item-alert"';
			else				$row_class 		= 'class="item-modified"';
		}
		if($row["id"]==$_GET["id_add"]){
			if($msg_end=="_KO")	$row_class 		= 'class="item-alert"';
			else				$row_class 		= 'class="item-added"';
		}

		
		$list_items.='
		<tr '.$row_class.'>
			<td>'.$row["id"].'</td>
			<td class="center">'.$admin_levels[$row["level"]].'</td>
			<td>'.$row["email"].'</td>
			<td class="center">
				';
				if($row["id"]==1)	$list_items.='-';
				else 				$list_items.=activeState($row["state"],$id_item,"users");
				$list_items.='
			</td>
			<td class="options">
				<ul>
					<li><a href="?p='.AC_PAGE.'&action=edit&id='.$row["id"].'" title="'.$ac_lang["edit"].'">'.icon("pencil").'</a></li>
					<li>
					';
					if($row["id"]==1) 	$list_items.=icon("bin",'#EEE');
					else 				$list_items.='<a href="?p='.AC_PAGE.'&action=delete&id='.$row["id"].'" title="'.$ac_lang["delete"].'">'.icon("bin").'';
					$list_items.='
					</li>
				</ul>
			</td>
		</tr>
		';
	}
	
	$contents.='
	<div class="block">
		<table>
			<thead>
				<tr>
					<td class="id"><a href="?p='.AC_PAGE.'&o=id" 		title="'.$ac_lang["order_by"].' : '.$ac_lang["id"].'">'.$ac_lang["id"].'</a></td>
					<td><a href="?p='.AC_PAGE.'&o=level"	title="'.$ac_lang["order_by"].' : '.$ac_lang["level"].'">'.$ac_lang["level"].'</a></td>
					<td><a href="?p='.AC_PAGE.'&o=email" 	title="'.$ac_lang["order_by"].' : '.$ac_lang["email"].'">'.$ac_lang["email"].'</a></td>
					<td>'.$ac_lang["state"].'</td>
					<td><span class="small-screen-no">'.$ac_lang["options"].'</span></td>
				</tr>
			</thead>
			<tbody>
				'.$list_items.'
			</tbody>
			
		</table>
	</div>
	';
}
?>