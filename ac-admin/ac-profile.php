<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-profile.php
Use			: 	edit current user email and password
*/	

//	define admin page table
$this_table=AC_TBL_USERS;

//	modify item
if(isset($_POST["mod"])){
	$msg='';
	
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
		
		// NOTE - password will be encytped on db insert
	}
	
	if($_POST["mod"]["default_lang"]!=$_SESSION["admin"]["lang"]){
		// change session lang to new language so that the new language will be used once the page is reloaded
		$_SESSION["admin"]["lang"]=$_POST["mod"]["default_lang"];
	}
	
	if(empty($msg)){
		if(modItem($this_table,$_SESSION["admin"]["id"],$_POST["mod"],false))	header("Location:index.php?p=".AC_PAGE."&msg=mod_OK");
		else 																	$warning=$lang["msg_mod_KO"];
	}

}

//	get item data
$row=getItem($this_table,$_SESSION["admin"]["id"]);
$contents.='
<form method="POST">
	<div class="block">
		'.fieldRow($ac_lang["language"]			, 'default_lang', '<select name="mod[default_lang]">'.selectListOptions($ac_languages,$row["default_lang"]).'</select>').'
		'.fieldRow($ac_lang["email"]			, 'email'		, '<input type="email" id="email" name="mod[email]" value="'.$row["email"].'" required>').'
		'.fieldRow($ac_lang["password"]			, 'password'	, '<input type="password" id="password" name="password" 		autocomplete="off" minlength="8" maxlength="15" placeholder="'.$ac_lang["note_password_mod"].'">').'
		'.fieldRow($ac_lang["password_repeat"]	, 'password2'	, '<input type="password" id="password" name="password-repeat"  autocomplete="off" minlength="8" maxlength="15" placeholder="'.$ac_lang["note_password_repeat"].'">').'
	</div>
	<div class="block-buttons">
		<input type="submit" value="'.$ac_lang["save"].'">
	</div>
</form>
';
?>