<?php
$show_form=true;

if(isset($_POST["bt-submit"])){
	if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
		// email not valid
		$msg		= $ac_lang["msg_email_KO"];
		$msg_type	= 'alert';
	}else{
		// check if email exists
		$sql="SELECT id,default_lang,email FROM ".AC_TBL_USERS." WHERE email='".mysqli_real_escape_string($db_cal,$_POST["email"])."' AND state=1";
		$res=mysqli_query($db_cal,$sql) or die("Error checking admin user<br>".mysqli_error($db_cal));
		if(mysqli_num_rows($res)==0){
			$msg		= $ac_lang["msg_user_not_exist"];
			$msg_type	= 'alert';
		}else{
			$row=mysqli_fetch_assoc($res);
			
			$user_id 	= $row["id"];
			$user_email = $row["email"];
			$user_name	= $row["username"];
			$user_lang	= $row["default_lang"];
			// NOTE - get user lang texts as they may be using a different language to the default admin language
			
			
			
			// create new password
			$user_pass_tmp = createCode(8);
			
			// save password to db
			$update="UPDATE ".AC_TBL_USERS." SET password=md5('".$user_pass_tmp."') WHERE id=".$user_id." LIMIT 1";
			mysqli_query($db_cal,$update) or die("Error - update pass");
			
			// get local lang version of texts according to user lang
			$subject		= getTextLocal("email_password_subject",$user_lang);
			$txt_email_body = getTextLocal("email_password_body",$user_lang);
			$html_msg		= str_replace("{new_password}","<strong>".$user_pass_tmp."</strong>",$txt_email_body);
		
			
			// temp
			$from_email	= "admin@";
			$from_name	= 'AAC ADMIN';
			
			
			if(!htmlEmail($from_email,$from_name,$user_email,$subject,$html_msg)){
				$msg		= getTextLocal("msg_passwrord_sent_KO",$user_lang);
				$msg_type	= 'alert';
			}else{
				// show message and login button
				$msg		= getTextLocal("msg_passwrord_sent_OK",$user_lang);
				$show_form	= false;
				
				$contents.='
				<div class="block">
					<div class="block-inner">
						<a href="?p=login" class="button">Login</a>
					</div>
				</div>
				';
			}
		}
	}
}

if($show_form){
	$contents='
	<div id="login" class="block">
		<div class="block-inner">
			<form method="post" action="">
				<input type="hidden" name="p" value="login-remider">
				'.fieldRowButton('<input type="email" name="email" value="'.$user_email.'" placeholder="'.$ac_lang["email"].'" required>').'
				'.fieldRowButton('<input type="submit" id="bt-submit" name="bt-submit" value="'.$ac_lang["bt_send_password"].'">').'
			</form>
		</div>
		<div class="block-footer">
			<a href="?p=login">'.$ac_lang["bt_login"].'</a>
		</div>
	</div>
	';
}
?>