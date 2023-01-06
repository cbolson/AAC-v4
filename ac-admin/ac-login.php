<?php
$user_email = '';
$user_pass 	= '';
$page_title	= 'Login';

if(isset($_POST["email"])){
	$user_email	= $_POST["email"];
	$user_pass 	= $_POST["password"];
	
	if(empty($user_email)){
		$msg		= $ac_lang["msg_email_KO"];
		$msg_type	= 'alert';
	}else if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
		// email not valid
		$msg		= $ac_lang["msg_email_KO"];
		$msg_type	= 'alert';
	}else if(empty($user_pass)){
		$msg		= $ac_lang["msg_password_KO"];
		$msg_type	= 'alert';
	}else{
		//	check login details
		//if OK, set session vars and reload page
		$sql="SELECT id,username,level,default_lang FROM ".AC_TBL_USERS." WHERE email='".mysqli_real_escape_string($db_cal,$user_email)."' AND password='".md5(mysqli_real_escape_string($db_cal,$user_pass))."' AND state=1";
		//exit();
		//$sql="SELECT id,username,level FROM ".T_AC_ADMIN." WHERE id=1 AND state=1";
		$res=mysqli_query($db_cal,$sql) or die("Error checking admin user<br>".mysqli_error($db_cal));
		if(mysqli_num_rows($res)==0){
			$msg		= $ac_lang["msg_user_not_exist"];
			$msg_type	= 'alert';
		}else{
			$row=mysqli_fetch_assoc($res);
			$_SESSION["admin"]["id"]	=	$row["id"];
			$_SESSION["admin"]["name"]	=	$row["username"];
			$_SESSION["admin"]["lang"]	=	$row["default_lang"];
			$_SESSION["admin"]["level"]	=	$row["level"];
		
			//	update table with visit
			$update="UPDATE ".AC_TBL_USERS." SET date_visit=now(), visits=visits+1 WHERE id=".mysqli_real_escape_string($db_cal,$row["id"])." LIMIT 1";
			mysqli_query($db_cal,$update) or die("error updating user visit stats");
			
			header("Location:index.php");
		}
	}
}

//	define login form
$contents='
<div class="block block--login">
	<form method="post" action="/ac-admin/">
		'.fieldRowData($ac_lang["email"], '<input type="email" name="email" value="'.$user_email.'"  required>').'
		'.fieldRowData($ac_lang["password"], '<input type="password" name="password" value="'.$user_pass.'" required>').'
		'.fieldRowButton('<input type="submit" id="bt-login" value="'.$ac_lang["bt_login"].'">').'
	</form>
	
	<div class="block-footer">
		<a href="?p=login-reminder">'.$ac_lang["bt_password_reminder"].'</a>
	</div>
</div>
';
?>