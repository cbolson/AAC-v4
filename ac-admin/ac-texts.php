<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-texts.php
Use			: 	list, edit and add TEXTS and TRANSLATIONS (mainly used in admin)
*/	

$txt_type = "texts";


// add item
if(isset($_POST["add"])){
	//print_arr($_POST);
	//exit();
	if(empty($_POST["add"]["code"])){
		// code is required
		$msg		= 'No code defined';
		$msg_type	= 'alert';
	}else{
		// insert code into texts table
		if(!addItem(AC_TBL_TEXTS,$_POST["add"],false)){
			$warning	= $ac_lang["msg_add_KO"];
			$msg_type	= 'alert';
		}else{
			// get id of last insert
			$id_insert=mysqli_insert_id($db_cal);
			
			$insert_data	= '';
			// define translations insert data along with the new code "id"	
			foreach($_POST["add_lang"] AS $langcode=>$val){
				if(!empty($val)){
					$insert_data.="(
						'".$txt_type."',
						".$id_insert.",
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
				if(mysqli_query($db_cal,$insert)) 	header("Location:index.php?p=".AC_PAGE."&id_add=".$id_insert."&msg=add_OK");
				else 								$warning=$ac_lang["msg_add_KO"];
			}
		}
	}
}

//	modify item
if(isset($_POST["mod"])){
	//print_arr($_POST);
	//exit();
	
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
					".$id_item.",
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
	
	if($_POST["orig_code"]!=$_POST["mod"]["code"]){
		// update code
		$update="UPDATE ".AC_TBL_TEXTS." SET code='".mysqli_real_escape_string($db_cal, $_POST["mod"]["code"])."' WHERE id=".$id_item." LIMIT 1";
		mysqli_query($db_cal,$update) or die("Error - code update");
		
	}
	// if we have got this far everything has worked - redirect to list
	header("Location:index.php?p=".AC_PAGE."&id_mod=".$id_item."&msg=mod_OK");
}




if(isset($_REQUEST["action"])){
	switch($_REQUEST["action"]){
		case "new":
			$contents.='
			'.blockMsg($ac_lang["note_text_add"],"advice").'
			<div class="block">
				<form method="post" id="item_form">
					'.fieldRow($ac_lang["code"],'code','<input type="text" id="code" name="add[code]"><spam class="note">'.$ac_lang["note_text_code"].'</span>').'
					';
					foreach($ac_languages AS $langcode=>$langdesc){
						$contents.=fieldRow($langdesc,'add_lang_'.$langcode.'','<input type="text" id="add_lang_'.$langcode.'" name="add_lang['.$langcode.']" required>');
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
			if(!$row=getItem(AC_TBL_TEXTS,$item_id,$sql_condition)){
				//	item doesn't exist (or user doesn't have permission to see)
				$msg.=$ac_lang["warning_item_not_exist"];
				$msg_type='alert';
			}else{
				//if(strpos($row["code"],"{")){
				if($row["code"]=="email_password_body"){
					$msg=str_replace("{new_password}","<strong>{new_password}</strong>",$ac_lang["note_variable_password"]);
					$msg_type="alert";
				}
				$contents.='
				<div class="block">
					<form method="post" id="item_form">
					<input type="hidden" name="id" value="'.$item_id.'"> 
					<input type="hidden" name="orig_code" value="'.$row["code"].'"> 
					'.fieldRow($ac_lang["code"],'code','<input type="text" id="code" name="mod[code]" value="'.$row["code"].'"><span class="note">'.$ac_lang["note_id_ref_external_mod"].'</span>').'
					';
					foreach($ac_languages AS $langcode=>$langdesc){
						// get lang value from db - use local lang function to get local version OR create new query???
						$tmp_txt=getTextLocal($row["code"],$langcode,$txt_type,false);
						if(strlen($tmp_txt)>80) $txt_field='<textarea id="desc_'.$langcode.'" name="mod_lang['.$langcode.']"  required style="width:100%;height:160px;">'.$tmp_txt.'</textarea>';
						else					$txt_field='<input type="text" id="desc_'.$langcode.'" name="mod_lang['.$langcode.']" value="'.$tmp_txt.'" required>';
						
						$contents.=fieldRow($langdesc,'desc_'.$langcode.'',''.$txt_field.'');
					}
					$contents.='
					'.fieldRowButton('<input type="submit" value="'.$ac_lang["save"].'">').'
				</div>
				';
			}
			break;
	}
	
	
}else{
	/// list of all texts
	// default order
	$order_by='tt.txt';
	
	if(isset($_GET["o"])){
		switch($_GET["o"]){
			case "id" 	: $order_by='t.id';	break;
			case "code" : $order_by='t.code';	break;
		}
	}
	
	$sql="
	SELECT 
		t.id,
		t.code,
		t.state,
		tt.txt	AS local_txt
	FROM 
		".AC_TBL_TEXTS." 					AS t
		LEFT JOIN ".AC_TBL_TRANSLATIONS." 	AS tt 	ON tt.id_text=t.id 	AND tt.type='".$txt_type."'		AND tt.langcode='".AC_LANG."'
	ORDER BY ".$order_by." ASC
	";
	//echo $sql;
	$res=mysqli_query($db_cal,$sql) or die("Error getting items");
	if(mysqli_num_rows($res)==0){
		$msg		= $ac_lang["msg_no_results"];
		$msg_type	= 'alert';
	}else{

		while($row=mysqli_fetch_assoc($res)){
			$row_class 	= '';
			$id_item 	= $row["id"];
			if($id_item==$_GET["id_mod"]){
				if($msg_end=="_KO")	$row_class 		= 'class="item-alert"';
				else				$row_class 		= 'class="item-modified"';
			}
			if($id_item==$_GET["id_add"]){
				if($msg_end=="_KO")	$row_class 		= 'class="item-alert"';
				else				$row_class 		= 'class="item-added"';
			}
			$local_txt=$row["local_txt"];
			if(empty($local_txt))	$local_txt='<span class="note" style="color:red;">'.$ac_lang["not_translated"].'</span>';
			$list_items.='
			<tr '.$row_class.'>
				<td class="center">'.$id_item.'</td>
				<td>'.$row["code"].'</td>
				';
				foreach($ac_languages AS $langcode=>$langdesc){
					// check if text has been translated
					$tmp_txt=getTextLocal($row["code"],$langcode,$txt_type,false);
					if(!empty($tmp_txt)) 	$icon=icon("checkmark","","small");
					else					$icon=icon("cross","","small");
					$list_items.='<td class="col-lang-translated">'.$icon.'</td>';
				}
				$list_items.='<td class="small-screen-no">'.$local_txt.'</td>
				<td class="center">'.activeState($row["state"],$id_item,"texts").'</td>
				<td class="options">
					<ul>
						<li><a href="?p='.AC_PAGE.'&action=edit&id='.$id_item.'" title="'.$ac_lang["edit"].'">'.icon("pencil").'</a></li>
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
						<td class="id"><a href="?p='.AC_PAGE.'&o=id" 		title="'.$ac_lang["order_by"].' : '.$ac_lang["id"].'">'.$ac_lang["id"].'</a></td>
						<td><a href="?p='.AC_PAGE.'&o=code" 	title="'.$ac_lang["order_by"].' : '.$ac_lang["code"].'">'.$ac_lang["code"].'</a></td>
						';
						foreach($ac_languages AS $langcode=>$langdesc){
							$contents.='<td class="col-lang-translated">'.$langcode.'</td>';
						}
						$contents.='<td class="small-screen-no"><a href="?p='.AC_PAGE.'&o=value" 	title="'.$ac_lang["order_by"].' : '.$ac_lang["value"].'">'.$ac_lang["value"].'</a> ('.$ac_languages[AC_LANG].')</td>
						
						<td>'.$ac_lang["state"].'</td>
						<td><span class="small-screen-no">'.$ac_lang["options"].'</span></td>
					</tr>
				</thead>
				'.$list_items.'
			</table>
		</div>
		';
	}
}

?>