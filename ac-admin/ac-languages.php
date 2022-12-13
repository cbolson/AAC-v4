<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-texts.php
Use			: 	list, edit and add TEXTS and TRANSLATIONS (mainly used in admin)
*/	

$txt_type = "lang"; # identify text type in translations table


// add item
if(isset($_POST["add"])){
	//print_arr($_POST);
	//exit();
	if(empty($_POST["add"]["langcode"])){
		// code is required
		$msg		= 'No code defined';
		$msg_type	= 'alert';
	}else{
		// insert code into texts table
		if(!addItem(AC_TBL_LANGUAGES,$_POST["add"],false)){
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
						'".$id_insert."',
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
				if(mysqli_query($db_cal,$insert)) {
					$msg		= $ac_lang["msg_add_new_lang_definition"];
					$msg_type	= "advice";
					header("location:?p=".AC_PAGE."&action=edit&id=".$id_insert."&msg=add_OK");
				}else{
					$msg=$ac_lang["msg_add_KO"];
					$msg_type	= "alert";
				}
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
	
	if($_POST["orig_langcode"]!=$_POST["mod"]["code"]){
		// update code
		$update="UPDATE ".AC_TBL_LANGUAGES." SET code='".mysqli_real_escape_string($db_cal, $_POST["mod"]["code"])."' WHERE id=".$id_item." LIMIT 1";
		mysqli_query($db_cal,$update) or die("Error - code update");
		
	}
	// if we have got this far everything has worked - redirect to list
	header("Location:index.php?p=".AC_PAGE."&id=".$id_item."&msg=mod_OK");
}




//print_arr($ac_languages);


if(isset($_REQUEST["action"])){
	switch($_REQUEST["action"]){
		case "new":
			$contents.='
			'.blockMsg($ac_lang["note_lang_add"],"advice").'
			<div class="block">
				<form method="post" id="item_form">
					'.fieldRow($ac_lang["code"],'langcode','<input type="text" id="langcode" name="add[code]" minlength="2" maxlength="2" style="width:60px;" required><spam class="note">'.$ac_lang["note_langcode"].'</span>').'
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
			//print_arr($ac_languages);
			$item_id 		= $_REQUEST["id"];
			//	get item data
			if(!$row=getItem(AC_TBL_LANGUAGES,$item_id,$sql_cond_user)){
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
					<input type="hidden" name="orig_langcode" value="'.$row["code"].'"> 
					'.fieldRow($ac_lang["code"],'code','<input type="text" id="code" name="mod[code]" value="'.$row["code"].'"  minlength="2" maxlength="2" style="width:60px;" required>').'
					';
					foreach($ac_languages AS $langcode=>$langdesc){
						// get lang value
					$tmp_txt=getTextLocal($row["code"],$langcode,$txt_type,false);
						$contents.=fieldRow($langdesc,'desc_'.$langcode.'','<input type="text" id="desc_'.$langcode.'" name="mod_lang['.$langcode.']" value="'.$tmp_txt.'" required>');
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
			case "code" : $order_by='t.langcode';	break;
		}
	}
//	print_arr($ac_languages);
	$sql="
	SELECT 
		t.id,
		t.code,
		t.state,
		tt.txt	AS local_txt
	FROM 
		".AC_TBL_LANGUAGES." 				AS t
		LEFT JOIN ".AC_TBL_TRANSLATIONS." 	AS tt 	ON tt.id_text=t.id 	AND tt.type='".$txt_type."'		AND tt.langcode='".AC_LANG."'
	ORDER BY ".$order_by." ASC
	";
	//echo $sql;
	$res=mysqli_query($db_cal,$sql) or die("Error getting languages");
	if(mysqli_num_rows($res)==0){
		$msg		= $ac_lang["msg_no_results"];
		$msg_type	= 'alert';
	}else{

		while($row=mysqli_fetch_assoc($res)){
			$row_class 	= '';
			$id_item 	= $row["id"];
			if($id_item==$_GET["id"]){
				if($msg_end=="_KO")	$row_class 		= 'class="item-alert"';
				else				$row_class 		= 'class="item-modified"';
			}
			$local_txt=$row["local_txt"];
			if(empty($local_txt))	$local_txt='<span class="note" style="color:red;">'.$ac_lang["not_translated"].'</span>';
			$list_items.='
			<tr '.$row_class.'>
				<td class="center">'.$id_item.'</td>
				<td class="center">'.$row["code"].'</td>
				';
				foreach($ac_languages AS $langcode=>$langdesc){
					// check if text has been translated
					$tmp_txt=getTextLocal($row["code"],$langcode,$txt_type,false);
					if(!empty($tmp_txt)) 	$icon=icon("checkmark","","small");
					else					$icon=icon("cross","","small");
					$list_items.='<td class="col-lang-translated">'.$icon.'</td>';
				}
				$list_items.='<td>'.$local_txt.'</td>
				<td class="center">'.activeState($row["state"],$id_item,"languages").'</td>
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
						$contents.='
						<td><a href="?p='.AC_PAGE.'&o=value" 	title="'.$ac_lang["order_by"].' : '.$ac_lang["value"].'">'.$ac_lang["value"].'</a> ('.$ac_languages[AC_LANG].')</td>
						<td>'.$ac_lang["state"].'</td>
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