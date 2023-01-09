<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-items.php
Use			: 	list, edit and add ITEMS
*/	


//	define admin page table
$this_table	= AC_TBL_ITEMS;
$txt_type 	= "item";

//	add new item
if(isset($_POST["add"])){
	//print_arr($_POST["add"]);
	//	define next list order
	$_POST["add"]["list_order"]	= getNextOrder($this_table);
	$_POST["add"]["id_user"]	= $_SESSION["admin"]["id"];
	//if($_POST["add"]["id_ref_external"]==='') $_POST["add"]["id_ref_external"] = NULL;

	if(addItem($this_table,$_POST["add"],true))	{
		$id_item=mysqli_insert_id($db_cal);
		
		$insert_data	= '';
		// define translations insert data along with the new code "id"	
		foreach($_POST["add_lang"] AS $langcode=>$val){
			if(!empty($val)){
				$insert_data.="(
					'".$txt_type."',
					'".$id_item."',
					'".mysqli_real_escape_string($db_cal, $langcode)."',
					'".mysqli_real_escape_string($db_cal, $val)."'
				),";
			}
		}
		if(!empty($insert_data)){
			// insert new texts to translations table
			$insert="
			INSERT INTO ".AC_TBL_TRANSLATIONS." 
				(type,id_text,langcode,txt) 
			VALUES 
				".trim($insert_data,",")."
			";
			//echo $insert;
			mysqli_query($db_cal,$insert) or die("Error - insert texts");
		}
		header("Location:index.php?p=".AC_PAGE."&id=".$id_item."&msg=add_OK");
	}else{
		$warning=$ac_lang["msg_add_KO"];
		$warning_type="alert";
	}
}

//	modify item
if(isset($_POST["mod"])){
	$id_item	= $_POST["id"];
	
	// delete previoous translations for this text
	$del="DELETE FROM ".AC_TBL_TRANSLATIONS." WHERE type='".$txt_type."' AND id_text=".$id_item."";
	mysqli_query($db_cal,$del) or die("Error - delete translations");
	
	
	// insert mew texts
	if(count($_POST["mod_lang"])==0){
		die("no texts to insert");	
	}else{
		$insert_data	= '';
		// define translations insert data along with the new code "id"	
		foreach($_POST["mod_lang"] AS $langcode=>$val){
			if(!empty($val)){
				$insert_data.="(
					'".$txt_type."',
					'".$id_item."',
					'".mysqli_real_escape_string($db_cal, $langcode)."',
					'".mysqli_real_escape_string($db_cal, $val)."'
				),";
			}
		}
		if(!empty($insert_data)){
			// insert new texts to translations table
			$insert="
			INSERT INTO ".AC_TBL_TRANSLATIONS." 
				(type,id_text,langcode,txt) 
			VALUES 
				".trim($insert_data,",")."
			";
			mysqli_query($db_cal,$insert) or die("Error - insert texts");
		}
	}
	
	if(modItem($this_table,$id_item,$_POST["mod"]))		header("Location:index.php?p=".AC_PAGE."&id=".$id_item."&msg=mod_OK");
	else 												$msg=$ac_lang["msg_mod_KO"];
}

//	delete item
if(isset($_POST["delete_it"])){
	$id_item=$_POST["id"];
	if(deleteItem($this_table,$id_item)){
		
		// delete translations
		// delete previoous translations for this text
		$del="DELETE FROM ".AC_TBL_TRANSLATIONS." WHERE type='".$txt_type."' AND id_text=".$id_item."";
		mysqli_query($db_cal,$del) or die("Error - delete translations");
	
	
	
		//	delete bookings for this item
		$del="DELETE FROM ".AC_TBL_AVAIL." WHERE id_item=".mysqli_real_escape_string($db_cal,$id_item)."";
		mysqli_query($db_cal,$del) or die("Error deleting item bookings");
		header("Location:index.php?p=".AC_PAGE."&msg=delete_OK");
	}else{
		$msg=$ac_lang["msg_delete_KO"];
	}
}






if(isset($_REQUEST["action"])){
	switch($_REQUEST["action"]){
		case "new":
			$contents.='
			<div class="block">
				<form method="post" id="item_form">
				'.fieldRow($ac_lang["id_external"],'id_ref_external','<input type="text" id="id_ref_external" name="add[id_ref_external]"  style="width:100px;"><span class="note">'.$ac_lang["note_id_ref_external"].'</span>').'
					';
					foreach($ac_languages AS $langcode=>$langdesc){
						$contents.=fieldRow($langdesc,'desc_'.$langcode.'','<input type="text" id="desc_'.$langcode.'" name="add_lang['.$langcode.']" required>');
					}
					$contents.='
					'.fieldRowButton('<input type="submit" value="'.$ac_lang["add"].'">').'
				</form>
			</div>
			';
			break;
			
		case "edit":
			$item_id 		= $_REQUEST["id"];
			//	get item data
			if(!$row=getItem($this_table,$item_id,$sql_cond_user)){
				//	item doesn't exist (or user doesn't have permission to see)
				$msg.=$ac_lang["warning_item_not_exist"];
				$msg_type='alert';
			}else{
				$contents.='
				<div class="block">
					<form method="post" id="item_form">
					<input type="hidden" name="id" value="'.$item_id.'"> 
					'.fieldRow($ac_lang["id_external"],'id_ref_external','<input type="text" id="id_ref_external" name="mod[id_ref_external]" value="'.$row["id_ref_external"].'" style="width:100px;"><span class="note">'.$ac_lang["note_id_ref_external"].'</span>').'
					';
					foreach($ac_languages AS $langcode=>$langdesc){
						// get lang value
						$tmp_txt=getTextLocal($row["id"],$langcode,$txt_type,false);
						$contents.=fieldRow($langdesc,'desc_'.$langcode.'','<input type="text" id="desc_'.$langcode.'" name="mod_lang['.$langcode.']" value="'.$tmp_txt.'" required>');
					}
					$contents.='
					'.fieldRowButton('
						<input type="submit" value="'.$ac_lang["save"].'"> 
						<a href="?p='.AC_PAGE.'&action=delete&id='.$item_id.'" title="'.$ac_lang["delete"].'" class="icon-button">'.icon("bin").'</a>
					').'
				</div>
				';
			}
			break;
			
		case "delete":
			
				
			//	get item details
			if(!$row=getItem($this_table,$_REQUEST["id"],$sql_cond_user)){
				//	item doesn't exist (or user doesn't have permission to see)
				$msg.=$ac_lang["msg_item_not_exist"];
				$msg_type='alert';
			}else{
				$contents.='
				<div class="block">
					<form method="post" onSubmit="return confirm(\''.$ac_lang["msg_delete_confirm"].'\');">
						<input type="hidden" name="delete_it" value="1">
						<input type="hidden" name="id" value="'.$_REQUEST["id"].'"> 
						'.fieldRowData($ac_lang["id_external"],$row["id_ref_external"]).'
						';
						foreach($ac_languages AS $langcode=>$langdesc){
							// get lang value
							$tmp_txt=getTextLocal($row["id"],$langcode,$txt_type,false);
							$contents.=fieldRowData($langdesc,'desc_'.$langcode.'',$tmp_txt);
						}
						$contents.='
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
			case "desc"	: $order_by='tt.txt';	break;
		}
	}
	
	$sql="
	SELECT 
		t.*,
		u.email, 
		tt.txt	AS local_txt
	FROM 
		".$this_table." 					AS t 
		LEFT JOIN ".AC_TBL_USERS." 			AS u 	ON u.id=t.id_user 
		LEFT JOIN ".AC_TBL_TRANSLATIONS." 	AS tt 	ON tt.id_text=t.id 	AND tt.type='".$txt_type."'		AND tt.langcode='".AC_LANG."'
	WHERE 
		t.id<>0 
		".$sql_cond_user." 
	ORDER BY ".$order_by." ASC
	";
	//echo $sql;
	$res=mysqli_query($db_cal,$sql) or die("Error - items");
	if(mysqli_num_rows($res)==0){
		//$msg		= "no results_ ".$ac_lang["msg_no_results"];
		$contents='
		<div class="msg info">
			'.$ac_lang["msg_add_item"].'
			<br><br>
			<a href="?p=items&action=new" class="pseudo-button">'.icon("plus").' '.$ac_lang["add"].'</a>
		</div>
		';
	}else{
	
		$list_items		= '';
		while($row=mysqli_fetch_assoc($res)){
			$row_class='';
			
			$id_item = $row["id"];
			if($id_item==$_REQUEST["id"]){
				
				if($msg_end=="_KO")	$row_class 		= 'class="item-alert"';
				else				$row_class 		= 'class="item-modified"';
			}
			$local_txt=$row["local_txt"];
			if(empty($local_txt))	$local_txt='<span class="note" style="color:red;">'.$ac_lang["not_translated"].'</span>';
			
			
			$list_items.='
			<tr '.$row_class.'>
				<td class="center">'.$id_item.'</td>
				';
				foreach($ac_languages AS $langcode=>$langdesc){
					// check if text has been translated
					$tmp_txt=getTextLocal($row["id"],$langcode,$txt_type,false);
					if(!empty($tmp_txt)) 	$icon=icon("checkmark","","small");
					else					$icon=icon("cross","","small");
					$list_items.='<td class="col-lang-translated">'.$icon.'</td>';
				}
				$list_items.='
				<td>'.$local_txt.'</td>
				';
				if($_SESSION["admin"]["level"]==1){
					// show item owner
					$list_items.='<td><a href="?p=users&id='.$row["id_user"].'&action=edit">'.$row["email"].'</a></td>';
				}
				$list_items.='
				<td class="center">'.activeState($row["state"],$id_item,"items").'</td>
				<td class="options">
					<ul>
						<li><a href="?p='.AC_PAGE.'&action=edit&id='.$id_item.'" title="'.$ac_lang["edit"].'">'.icon("pencil").'</a></li>
						<li><a href="?p=availability&id_item='.$id_item.'" title="'.$ac_lang["calendar"].'">'.icon("calendar").'</a></li>
						<li><a href="?p='.AC_PAGE.'&action=delete&id='.$id_item.'" title="'.$ac_lang["delete"].'">'.icon("bin").'</a></li>
					</ul>
				</td>
			</tr>
			';
		}
		//print_arr($ac_lang);
		$contents.='
		<div class="block">
			<table>
				<thead>
					<tr>
						<td class="id"><a href="?p='.AC_PAGE.'&o=id" 	title="'.$ac_lang["order_by"].' : '.$ac_lang["id"].'">'.$ac_lang["id"].'</a></td>
						';
						foreach($ac_languages AS $langcode=>$langdesc){
							$contents.='<td class="col-lang-translated">'.$langcode.'</td>';
						}
						$contents.='
						<td><a href="?p='.AC_PAGE.'&o=desc" title="'.$ac_lang["order_by"].' : '.$ac_lang["id"].'">'.$ac_lang["title"].'</a></td>
						';
						if($_SESSION["admin"]["level"]==1){
							// show item owner
							$contents.='<td>'.$ac_lang["admin_level_user"].'</td>';
						}
						$contents.='
						<td class="state">'.$ac_lang["state"].'</td>
						<td class="options"><span class="small-screen-no">'.$ac_lang["options"].'</span></td>
					</tr>
				</thead>
				'.$list_items.'
			</table>
		</div>
		';
	}
	
}
?>