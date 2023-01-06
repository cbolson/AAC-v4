<?php
/*
Script		: Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: Chris Bolson www.cbolson.com

File		: ac-install.php
Date Add    : 2009-06-08
Date Mod    : 2023-01-02
Use         : 1. Define config values (check file is writable first)
		      2. Install database
		      3. Check files exist?
*/
$is_install = true; # define to prevent check for this file in db-connect file

define("FILE_CONFIG"	, dirname(__FILE__)."/ac-config.inc.php");


$db_cal         = '';
$check_items    = [];

$check_items[1]=array(
	"pend"	=> "Check configuration file <u>".FILE_CONFIG."</u> exists.",
	"ok"	=> "Configuration file exists - OK.",
	"ko"	=> "Configuration file <u>".FILE_CONFIG."</u> does not exist."
	);

$check_items[2]=array(
	"pend"	=> "Define database configuration.",
	"ok"	=> "Database configuration defined.",
	"ko"	=> "Database configuration not defined",
	"current"	=> "Define database configuration."
	);
$check_items[3]=array(
	"pend"	=> "Create Database Tables",
	"ok"	=> "Database tables created",
	"ko"	=> "Unable to create database tables"
	);
$check_items[4]=array(
	"pend"	=> "Define calendar config",
	"ok"	=> "Calendar Configured for use.",
	"ko"	=> "Calendar configuration not defined."
	);
	
	
	
$check_item_states=array();

//	check config file exists
if(!file_exists(FILE_CONFIG))	$check_item_states[1]="ko";	
else 							$check_item_states[1]="ok";


//	is config writable?
if($check_item_states[1]=="ok"){
	//	config file exists
	/*
	1-check db connection  - if user has modified manually we can move on - no need to check if writable etc
	2-check is writable
	3-check has values
	4-c
	*/
	/*
	$check_items[2]=array(
	"pend"	=> "Check confiuration <u>".FILE_CONFIG."</u> file is writable",
	"ok"	=> "Configuration file is writable.",
	"ko"	=> "<u>".FILE_CONFIG."</u> is not writable - chmod to 777 then <a href='ac-install.php'>click here</a>."
	);*/
	
	//	if form posted - write file
	if(isset($_POST["db"])){
		//	write db connect values to config file
		$fh = fopen(FILE_CONFIG, 'w') or die("can't open file");
		$stringData = '<?php
//	 database settings
define("AC_DB_HOST",		"'.$_POST["db"]["host"].'");
define("AC_DB_NAME",		"'.$_POST["db"]["name"].'");
define("AC_DB_USER",		"'.$_POST["db"]["username"].'");
define("AC_DB_PASS",		"'.$_POST["db"]["password"].'");
define("AC_DB_PREFIX",		"");
//	do not alter these lines
define("AC_ROOT"			, dirname(__FILE__). "/");
define("AC_INCLUDES_ROOT"	, AC_ROOT."ac-includes/");
?>
';
		fwrite($fh, $stringData);
		fclose($fh);
	}
	
	//	general config
	$the_file=FILE_CONFIG;
	if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
	else		require_once($the_file);
	
	//	check connection
	if(check_db_connection()){
		//	db connection ok - move on to next state
		$check_items_states[2]="ok";
	}else{
		//	check for values
		if( 
            empty(AC_DB_HOST) 
            || empty(AC_DB_USER)
            || empty(AC_DB_PASS)
            || empty(AC_DB_NAME)
        ){
			//	config values not set - show form
			$show_config_form=true;
		}else{
			//	values there but NOT correct
			$show_config_form=true;
			$waring='<div class="warning">Unable to connect to database.  Please check your data.</div>';
		}
	}
	
	if($show_config_form){
		//	check file is writable
		if(!is_writable(FILE_CONFIG)){
			$check_item_states[2]="ko";	
			$check_items[2]["ko"]="File <u>".FILE_CONFIG."</u> is not writable - chmod to 777 then <a href='ac-install.php'>click here</a>";
		}else{
			
			
			
			$check_item_states[2]		= 'current';	
			$check_items[2]["current"]	= '
			Define your database configuration settings:
			<div style="padding:20px;">
				'.$waring.'
				<form method="post" action="">
				<table>
					'.rowData('<label for="id_title">Database Host</label>'			, '<input type="text" name="db[host]" value="'.AC_DB_HOST.'" placeholder="Database Host eg. Localhost">').'
					'.rowData('<label for="id_title">Database Name</label>'			, '<input type="text" name="db[name]" value="'.AC_DB_NAME.'" placeholder="Database Name">').'
					'.rowData('<label for="id_title">Database Username</label>'		, '<input type="text" name="db[username]" value="'.AC_DB_USER.'"" placeholder="Database Username">').'
					'.rowData('<label for="id_title">Database Password</label>'		, '<input type="text" name="db[password]" value="'.AC_DB_PASS.'"" placeholder="Database Password">').'
					<tr>
						<td>&nbsp;</td>
						<td><input type="submit" class="submit" value="Save/Confirm Configuration"></td>
					</tr>
				</table>
				</form>
			</div>			
			';
		}
	}else{
		$check_item_states[2]="ok";
	}
}

//	database tables
if($check_item_states[2]=="ok"){
	//	check if tables exist
	if(!mysql_is_table(AC_DB_PREFIX."ac_availability")){
		//	add tables.....
		if(create_tables())		$check_item_states[3]="ok";
		else 					$check_item_states[3]="ko";
	}else{
		// move on to next check
		$check_item_states[3]="ok";
	}	
}

if($check_item_states[3]=="ok"){
	//	common vars (db and lang)
	$the_file=AC_INCLUDES_ROOT."ac-common.inc.php";
	if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
	else		require_once($the_file);
	

	//	calendar functions
	$the_file=AC_INCLUDES_ROOT."ac-functions.inc.php";
	if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
	else		require_once($the_file);
	
	if(isset($_POST["add_config"])){
		//	insert calendar config
		$insert="
		UPDATE `".AC_DB_PREFIX."ac_config` SET 
			title	= '".mysqli_real_escape_string($db_cal,$_POST["add_config"]["title"])."',
			cal_url = '".mysqli_real_escape_string($db_cal,$_POST["add_config"]["cal_url"])."'
		WHERE id=1 LIMIT 1
		";
		mysqli_query($db_cal,$insert) or die("error - cal config insert<br>".mysqli_error($db_cal));
		
		$user_email = $_POST["add_user"]["email"];
		$user_pass 	= $_POST["add_user"]["pass"];
		
		// insert user email and password
		$update="
		UPDATE `".AC_DB_PREFIX."ac_users` SET
			`email`		= '".mysqli_real_escape_string($db_cal, $user_email)."',
			`password`	=	md5('".$user_pass."')
		WHERE id=1 LIMIT 1
		";
		mysqli_query($db_cal,$update) or die("Error setting user data");
		
		// send password to user
		$subject		= "Calendar user data";
		$txt_email_body = 'Your admin user data:
		<br>Email: <strong>'.$user_email.'</strong>
		<br>Pass:  <strong>'.$user_pass.'</strong>
		';
		$html_msg		= $txt_email_body;
			
				
		// temp
		$from_email	= "admin@";
		$from_name	= 'AAC ADMIN';
		
		if(!htmlEmail($from_email,$from_name,$user_email,$subject,$html_msg)){
			$msg		= getTextLocal("msg_passwrord_sent_KO","en");
			$msg_type	= 'alert';
		}
		
		
		$check_item_states[4]="ok";
		
	}else{
	
		//	define calendar configuration settings
		
		$cal_config_form='
		Define your calendar options:
		<div style="padding:20px;">
			<form method="post" action="" >
			<input type="hidden" name="add_config" value="1">
			<table>
				'.rowData('<label for="email">Admin Email</label>' 				, '<input id="email" 		type="email" 	name="add_user[email]" required placeholder="your@email.com" style="width:300px;" />').'
				'.rowData('<label for="pass">Password</label>' 					, '<input id="pass" 		type="password" name="add_user[pass]" required style="width:300px;" />').'
				'.rowData('<label for="id_title">Calendar Title</label>' 		, '<input id="id_title" 	type="text" 	name="add_config[title]" value="'.$row_config["title"].'" style="width:300px;" />').'
				'.rowData('<label for="id_cal_url">Local Calendar root</label>'	, '<input id="id_cal_url" 	type="text" 	name="add_config[cal_url]" value="'.dirname($_SERVER["SCRIPT_NAME"]).'" style="width:150px;" /> <span class="note">'.$lang["note_cal_url"].'</span>').'
				<tr>
					<td>&nbsp;</td>
					<td><input type="submit" class="submit" value="Save Configuration" style="width:240px;"></td>
				</tr>
			</table>
			</form>
		</div>
		';
		$check_item_states[4]="current";	
		$check_items[4]["current"]=$cal_config_form;
	}
	
}

$final_message='';
if($check_item_states[4]=="ok"){

	$final_message='
	<div class="msg OK">
		Congratulations, your calendar is now ready to use.
		'.$msg.'
		<h3>Now what?</h3>
		<ol>
			<li><span style="color:red;">Remove</span> this file (<strong>ac-install.php</strong>) from your FTP - the calendar will not be shown until you do this.</li>
			<li>Reset the <strong>ac-config.inc.php</strong> file write permissions (eg set to 644). (linux)</li>
			<li>
				<a href="ac-admin/index.php" target="_blank">Login</a> to your admin panel to administrate your calendar(s)
				<br> - Username : "<strong>admin</strong>"
				<br> - Password : "<strong>'.$new_pass.'</strong>" (You should change these as soon as possible).
			</li>
			<li>Click <a href="index.html">here</a> to view/test your calendar.</li>
			<li>To add the calendar in your site, read the <a href="http://www.ajaxavailabilitycalendar.com/implementation" target="_blank">implementation instructions</a>.</li>
		</ol>
	</div>
	';
}

$check_list="";
foreach($check_items as $id=>$text){
	//echo "<br>".$id;
	if(array_key_exists($id,$check_item_states))	$this_state=$check_item_states[$id];
	else 											$this_state="pend";
	
	$check_list.='<li class="'.$this_state.'">'.$text["".$this_state.""].'</li>';
}

?>
<!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="UTF-8" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<title>Ajax Availability Calendar - Install</title>
            
            <link rel="stylesheet" href="/ac-admin/assets/admin.css?v3" />
		</head>
		<body class="install">
	    <main>
			<header class="header">
			 <div class="header__logo"><img src="/ac-assets/logo-acc.svg" title="Availability Calendar - Admin" width="300"></div>
                <h1 class="header__version"><?php echo CAL_VERSION; ?></h1>
            </header>
			<section>
				<h2>Follow these steps to install the calendar in your server.</h2>
                <?php 
                echo '
                <ol class="install-steps">
                    '.$check_list.'
                </ol>
                <p>'.$final_message.'</p>
                ';
                ?>
            </section>
			<footer>
				<div>
					<a href="https://www.ajaxavailabilitycalendar.com/">Availability Calendar</a> developed by <a href="http://www.cbolson.com" target="_blank">Chris Bolson</a>
				</div>
				
				<div>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="5972777">
					<input type="image" src="/ac-assets/donate-paypal.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online." style="border:none; width:120px;">
					<img alt="" border="0" src="https://www.paypal.com/es_ES/i/scr/pixel.gif" width="1" height="1">
					</form>
				</div>
			</footer>
		</main>
	
	</body>
</html>


<?php
function rowData($label,$data){
	$r='
	<tr>
		<td class="side">'.$label.'</td>
		<td>'.$data.'</td>
	</tr>
	';
	return $r;
}


//	check db connection
function check_db_connection(){
	global $db_cal; 
	//	db connection
	if(!$db_cal = @mysqli_connect(AC_DB_HOST,AC_DB_USER,AC_DB_PASS,AC_DB_NAME)) {
		//echo "can't connect";
		return false;
	}
	if(!@mysqli_select_db($db_cal,AC_DB_NAME)){
		//echo "can't find database";
		return false;
	}
	return true;
}

//	check table is created
function mysql_is_table($tbl){
	global $db_cal;
	
	$sql="SHOW TABLES LIKE '".$tbl."'";
	if($res=mysqli_query($db_cal,$sql)){
		if(mysqli_num_rows($res)==1) return TRUE;
	}
	return FALSE;
}

function create_tables(){
	global $db_cal;


	$sql=array();
	//	Table structure for table `bookings`
	$sql["Create Table - BOOKINGS"]="
	CREATE TABLE IF NOT EXISTS `".AC_DB_PREFIX."ac_availability` (
	`id` int(11) NOT NULL auto_increment,
	`id_item` int(20) NOT NULL default '0',
	`the_date` date NOT NULL,
	`id_state` int(11) NOT NULL default '0',
	`id_booking` int(10) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	KEY `id_item` (`id_item`),
	KEY `id_state` (`id_state`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 ;
	";


	//	Table structure for table `bookings_admin`
	$sql["Create Table - ADMIN"]="
	CREATE TABLE IF NOT EXISTS `".AC_DB_PREFIX."ac_users` (
	  	`id` int(11) NOT NULL,
		`level` tinyint(1) NOT NULL DEFAULT '2',
		`username` varchar(20) NOT NULL DEFAULT '',
		`password` varchar(32) NOT NULL DEFAULT '',
		`email` varchar(100) DEFAULT NULL,
		`default_lang` char(2) NOT NULL DEFAULT 'en',
		`state` tinyint(1) NOT NULL DEFAULT '1',
		`date_visit` datetime NOT NULL,
		`visits` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY  (`id`)
	) ENGINE=MyISAM AUTO_INCREMENT=2 ;
	";
	
	//	Dumping data for table `bookings_admin`
	$sql["Insert data - ADMIN"]="
	INSERT INTO `".AC_DB_PREFIX."ac_users` SET
		`id`		=	1,
		`level`		=	1,
		`username`	=	'admin',
		`password`	=	'fe01ce2a7fbac8fafaed7c982a04e229',
		`state`		=	1,
		`date_visit`=	now(),
		`visits`	=	0
	";


	// Table structure for table `bookings_config`
	$sql["Create Table - CONFIG"]="
	CREATE TABLE IF NOT EXISTS `".AC_DB_PREFIX."ac_config` (
	`id` int(11) NOT NULL auto_increment,
	`title` varchar(255) NOT NULL default '',
	`num_months` tinyint(3) NOT NULL default '3',
	`default_lang` varchar(6) NOT NULL default 'en',
	`theme` varchar(50) NOT NULL default 'default',
	`start_day` enum('mon','sun') NOT NULL default 'sun',
	`date_format` enum('us','eu') NOT NULL default 'eu',
	`click_past_dates` enum('on','off') NOT NULL default 'off',
	`cal_url` varchar(255) NOT NULL default '',
	`local_path` varchar(255) NOT NULL default '/calendar',
	`version` varchar(10) NOT NULL,
	`styles` text NOT NULL,
	`min_nights` tinyint(2) NOT NULL DEFAULT '0',
	PRIMARY KEY  (`id`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 ;
	";

	//	Dumping data for table `bookings_config`
	$sql["Insert Data - CONFIG"]="
	INSERT INTO `".AC_DB_PREFIX."ac_config` SET
		`id`				= 1,
		`title`				= 'Availability Calendar',
		`num_months`		= 3,
		`default_lang`		= 'en',
		`start_day`			= 'mon',
		`date_format`		= 'eu',
		`click_past_dates`	= 'off',
		`cal_url`			= '/calendar',
		`version`			= '".CAL_VERSION."',
		`styles` 			= 'a:14:{s:14:\"--ac-color1-bg\";s:7:\"#8fd9f2\";s:15:\"--ac-color1-txt\";s:7:\"#046889\";s:14:\"--ac-color2-bg\";s:7:\"#046889\";s:15:\"--ac-color2-txt\";s:7:\"#FFFFFF\";s:15:\"--ac-numbers-bg\";s:7:\"#FFFFFF\";s:16:\"--ac-numbers-txt\";s:7:\"#046889\";s:22:\"--ac-numbers-txt-hover\";s:7:\"#000000\";s:14:\"--ac-booked-bg\";s:7:\"#ff9090\";s:15:\"--ac-booked-txt\";s:7:\"#333333\";s:17:\"--ac-select-range\";s:7:\"#FFCC00\";s:19:\"--ac-select-between\";s:7:\"#fdeeb3\";s:12:\"--ac-nav-txt\";s:7:\"#046889\";s:18:\"--ac-nav-txt-hover\";s:7:\"#000000\";s:18:\"--ac-border-radius\";s:2:\"12\";}',
		`min_nights`		= 2
		
	";

	//	Table structure for table `bookings_items`
	$sql["Create Table  - ITEMS"]="


	CREATE TABLE `".AC_DB_PREFIX."ac_items` (
	`id` int(11) NOT NULL auto_increment,
	`id_user` int(11) NOT NULL default '1',
	`id_ref_external` int(11) NOT NULL COMMENT 'link to external db table',
	`desc_en` varchar(100) NOT NULL default '',
	`desc_es` varchar(100) NOT NULL default '',
	`list_order` int(11) NOT NULL default '0',
	`state` tinyint(1) NOT NULL default '1',
	PRIMARY KEY  (`id`),
	KEY `id_user` (`id_user`),
	KEY `id_ref_external` (`id_ref_external`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 ;
	";

	//	Dumping data for table `bookings_items` - DEMO ITEM
	$sql["Insert Data - ITEMS"]="
	INSERT INTO `".AC_DB_PREFIX."ac_items` SET 
		`id`		= 1,
		`id_user`	= 1,
		`id_ref_external`=0,
		`desc_en`	= 'Demo Item',
		`desc_es`	= 'Demo',
		`list_order`= 1,
		`state`		= 1
	";
	
	// Languages (new in version 4)
	$sql["Create Table - LANGUAGES"]="
	CREATE TABLE `ac_languages` (
		`id` int(11) NOT NULL,
		`code` char(2) NOT NULL,
		`description` varchar(50) NOT NULL,
		`state` tinyint(1) NOT NULL DEFAULT '1',
		PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";

	$sql["Insert Data - LANGUAGES"] = "
	INSERT INTO `ac_languages` (`id`, `code`, `description`, `state`) VALUES
		(1, 'en', 'English', 1),
		(2, 'es', 'Spanish', 1),
		(3, 'de', 'German', 1),
		(4, 'fr', 'French', 1);
	";


	//	Table structure for table `bookings_last_update`
	$sql["Create Table - UPDATE TIME"]="
	CREATE TABLE IF NOT EXISTS `".AC_DB_PREFIX."ac_last_update` (
	`id` int(10) NOT NULL auto_increment,
	`id_item` int(10) NOT NULL default '0',
	`date_mod` datetime NOT NULL,
	PRIMARY KEY  (`id`),
	KEY `id_item` (`id_item`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 ;
	";

	//	Table structure for table `bookings_states`
	$sql["Create Table - STATES"]="
	CREATE TABLE IF NOT EXISTS `".AC_DB_PREFIX."ac_states` (
	`id` int(11) NOT NULL auto_increment,
	`desc_en` varchar(100) NOT NULL default '',
	`desc_es` varchar(100) NOT NULL default '',
	`code` varchar(10) NOT NULL default '',
	`state` tinyint(1) NOT NULL default '1',
	`list_order` int(11) NOT NULL default '0',
	`class` varchar(30) NOT NULL default '',
	`show_in_key` tinyint(1) NOT NULL default '1',
	PRIMARY KEY  (`id`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 ;
	";

	//	Dumping data for table `bookings_states`
	$sql["Insert Data - STATES"]="
	INSERT INTO `".AC_DB_PREFIX."ac_states` 
		(`id`, `desc_en`, `desc_es`, `code`, `state`, `list_order`, `class`) 
	VALUES 
		(1, 'Booked', 'Reservado', 'b', 1, 0, 'booked'),
		(2, 'Booked am', 'Reservado am', 'b_am', 1, 1, 'booked_am'),
		(3, 'Booked pm', 'Reservado pm', 'b_pm', 1, 2, 'booked_pm')
	";
	// translations (new in version 4)
	$sql["Create table - TRANSLATIONS"] = "
	CREATE TABLE `".AC_DB_PREFIX."ac_translations` (
	`id` int(11) NOT NULL,
	`type` char(20) NOT NULL DEFAULT 'texts',
	`id_text` int(11) DEFAULT NULL,
	`langcode` char(2) DEFAULT 'en',
	`txt` varchar(255) CHARACTER SET utf8 NOT NULL,
	PRIMARY KEY  (`id`)
	) ENGINE=MyISAM AUTO_INCREMENT=1;
	";


	// insert translations
	$sql["Insert Data - TRANSLATIONS"]="
	INSERT INTO `".AC_DB_PREFIX."ac_translations` (`id`, `type`, `id_text`, `langcode`, `txt`) VALUES
(347,'texts',1,'fr','Aujourd\'hui'),
(507,'texts',2,'fr','Suivant'),
(511,'texts',3,'fr','ArriÃ¨re'),
(903,'texts',4,'fr','Envoyer'),
(891,'texts',5,'fr','Réglages'),
(591,'texts',7,'fr','_Items'),
(883,'texts',8,'fr','Des textes'),
(775,'texts',9,'fr','Se déconnecter'),
(843,'texts',10,'fr','Utilisateurs'),
(899,'texts',11,'fr','Sauver'),
(531,'texts',12,'fr','Ajouter'),
(683,'texts',13,'fr','Effacer'),
(655,'texts',14,'fr','Calendrier'),
(703,'texts',15,'fr','ID'),
(739,'texts',17,'fr','L\'article a été ajouté avec succès'),
(719,'texts',18,'fr','Il n\'a PAS été possible d\'ajouter l\'article'),
(727,'texts',20,'fr','Il n\'a PAS été possible de modifier l\'article'),
(743,'texts',21,'fr','L\'élément a été supprimé avec succès'),
(723,'texts',22,'fr','Il n\'a PAS été possible de supprimer l\'élément'),
(686,'texts',23,'en','Drag to change order'),
(735,'texts',24,'fr','L\'article n\'existe pas'),
(639,'texts',25,'fr','Voulez-vous vraiment supprimer cet élément ?'),
(691,'texts',26,'fr','Éditer'),
(631,'texts',27,'fr','Un nouveau mot de passe a été envoyé à votre email'),
(731,'texts',28,'fr','Il n\'a PAS été possible d\'envoyer votre nouveau mot de passe'),
(807,'texts',29,'fr','Rappel de mot de passe'),
(837,'texts',30,'es','Ha olvidado su contraseña para el panel de administración del calendario.\r\nPor razones de seguridad se ha creado una nueva contraseña.\r\nSu nueva contraseña es {new_password}\r\n\r\nInicie sesión y cambie esto lo antes posible.'),
(855,'texts',31,'fr','Titre'),
(887,'texts',32,'fr','État'),
(787,'texts',33,'fr','Choix'),
(695,'texts',34,'fr','E-mail'),
(803,'texts',35,'fr','Mot de passe'),
(811,'texts',36,'fr','Répéter le mot de passe'),
(779,'texts',37,'fr','Saisissez le mot de passe uniquement si vous souhaitez le modifier.'),
(823,'texts',38,'fr','Vous devez utiliser un email valide'),
(875,'texts',39,'fr','Le mot de passe n\'est pas valide'),
(767,'texts',40,'fr','Niveau'),
(747,'texts',41,'fr','Les utilisateurs ne peuvent modifier que leurs propres éléments et informations de connexion.'),
(667,'texts',42,'fr','Code'),
(839,'texts',43,'fr','Évaluer'),
(53,'texts',44,'en','This will add the code to the database - you must then add the new text to the calendar script manually via the FTP'),
(54,'texts',44,'es','pues eso.....'),
(879,'texts',49,'fr','Le code est utilisé dans le code PHP pour référencer le texte'),
(878,'texts',49,'en','The code is used in the PHP code to reference the text'),
(530,'texts',12,'en','Add'),
(643,'texts',50,'fr','Disponibilité'),
(642,'texts',50,'en','Availability'),
(867,'texts',51,'fr','Répétez le mot de passe'),
(866,'texts',51,'en','Repeat the password'),
(791,'texts',52,'fr','Commandé par'),
(790,'texts',52,'en','Order by'),
(711,'texts',53,'fr','IMPORTANT - Si vous modifiez ce code, assurez-vous de modifier également sa référence dans le code PHP ou JavaScript'),
(710,'texts',53,'en','IMPORTANT - If you modify this code ensure that you also modify it\'s reference in the PHP or JavaScript code'),
(75,'texts',54,'en','The text should contain the variable {new_password} as this will be replaced with the new password being sent to the user.'),
(76,'texts',54,'es','El texto debe contener el variable {new_password} (sin traducir) ya que eso serÃ¡ reemplazado con la nueva contraseÃ±a.'),
(758,'texts',55,'en','Language'),
(783,'texts',56,'fr','Non traduit'),
(782,'texts',56,'en','Not translated'),
(786,'texts',33,'en','Options'),
(886,'texts',32,'en','State'),
(898,'texts',11,'en','Save'),
(766,'texts',40,'en','Level'),
(666,'texts',42,'en','Code'),
(802,'texts',35,'en','Password'),
(902,'texts',4,'en','Send'),
(694,'texts',34,'en','Email'),
(346,'texts',1,'en','Today'),
(654,'texts',14,'en','Calendar'),
(690,'texts',26,'en','Edit'),
(838,'texts',43,'en','Value'),
(854,'texts',31,'en','Title'),
(510,'texts',3,'en','Back'),
(506,'texts',2,'en','Next'),
(682,'texts',13,'en','Delete'),
(702,'texts',15,'en','ID'),
(815,'texts',6,'fr','Le profil'),
(882,'texts',8,'en','Texts'),
(842,'texts',10,'en','Users'),
(774,'texts',9,'en','Logout'),
(890,'texts',5,'en','Settings'),
(590,'texts',7,'en','Items'),
(706,'texts',16,'en','ID external'),
(742,'texts',21,'en','Item has been deleted successfully'),
(814,'texts',6,'en','Profile'),
(806,'texts',29,'en','Password reminder'),
(722,'texts',22,'en','It has NOT been possible to delete the item'),
(718,'texts',18,'en','It has NOT been possible to add the item'),
(630,'texts',27,'en','A new password has been sent to your email'),
(638,'texts',25,'en','Are you sure that you want to delete this item?'),
(778,'texts',37,'en','Only type the password if you want to modify it.'),
(738,'texts',17,'en','Item has been added successfully'),
(822,'texts',38,'en','You must use a valid email'),
(726,'texts',20,'en','It has NOT been possible to modify the item'),
(810,'texts',36,'en','Password repeat'),
(734,'texts',24,'en','Item does not exist'),
(874,'texts',39,'en','The password is not valid'),
(730,'texts',28,'en','It has NOT been possible to send your new password'),
(746,'texts',41,'en','Users can only modify their own i­tems and login details.'),
(836,'texts',30,'de','Sie haben Ihr Passwort für das Kalender-Admin-Panel vergessen. Aus Sicherheitsgründen wurde ein neues Passwort erstellt. <br>Ihr neues Passwort ist {new_password}  <br>Bitte melden Sie sich an und ändern Sie dies so schnell wie möglich.'),
(663,'texts',57,'fr','URL du calendrier'),
(662,'texts',57,'en','Calendar url'),
(679,'texts',58,'fr','Langage par défaut'),
(678,'texts',58,'en','Default language'),
(771,'texts',59,'fr','Connexion'),
(770,'texts',59,'en','Login'),
(699,'texts',60,'fr','J\'ai oublié mon mot de passe'),
(698,'texts',60,'en','I have forgotten my password'),
(895,'texts',61,'fr','Envoyer le mot de passe'),
(894,'texts',61,'en','Send password'),
(847,'texts',62,'fr','L\'utilisateur n\'existe pas'),
(846,'texts',62,'en','User does not exist'),
(635,'texts',63,'fr','Administrateur'),
(634,'texts',63,'en','Administrator'),
(851,'texts',64,'fr','Utilisateur'),
(850,'texts',64,'en','User'),
(763,'texts',65,'fr','Langue'),
(762,'texts',65,'en','Languages'),
(715,'texts',66,'fr','Code ISO à 2 lettres'),
(714,'texts',66,'en','ISO 2 letter code'),
(798,'texts',67,'en','To add a new language define it here first then modify it with the new language value. <br>Don\'t forget to then modify the texts and item names to add the new language'),
(799,'texts',67,'fr','Pour ajouter une nouvelle langue, définissez-la d\'abord ici, puis modifiez-la avec la nouvelle valeur de langue. <br>N\'oubliez pas de modifier ensuite les textes et les noms des éléments pour ajouter la nouvelle langue'),
(827,'texts',68,'fr','Vous devez maintenant ajouter la nouvelle définition de langue'),
(826,'texts',68,'en','You must now add the new language definition'),
(796,'texts',69,'de','Seite nicht gefunden'),
(795,'texts',69,'fr','Page non trouvée'),
(794,'texts',69,'en','Page not found'),
(515,'texts',70,'fr','Couleur 1 - fond'),
(514,'texts',70,'en','Color 1 - background'),
(519,'texts',71,'fr','Couleur 1 - texte'),
(518,'texts',71,'en','Color 1 - text'),
(527,'texts',72,'fr','Couleur 2 - fond'),
(523,'texts',73,'fr','Couleur 2 - texte'),
(522,'texts',73,'en','Color 2 - text'),
(526,'texts',72,'en','Color 2 - background'),
(670,'texts',74,'en','Days - background'),
(675,'texts',75,'fr','Jours - texte'),
(674,'texts',75,'en','Days - text'),
(646,'texts',76,'en','Booked - background'),
(651,'texts',77,'fr','Réservé - texte'),
(650,'texts',77,'en','Booked - text'),
(863,'texts',78,'fr','modes'),
(862,'texts',78,'en','Styles'),
(871,'texts',79,'fr','Réinitialiser les styles par défaut'),
(870,'texts',79,'en','Reset styles to default'),
(659,'texts',80,'fr','Élément de calendrier'),
(658,'texts',80,'en','Calendar item'),
(819,'texts',81,'fr','Rayon'),
(818,'texts',81,'en','Radius'),
(628,'lang',2,'de','Spanisch'),
(624,'lang',3,'de','Deutsch'),
(620,'lang',4,'de','Französisch'),
(262,'item',1,'en','Demo item'),
(263,'item',1,'fr','Ã‰lÃ©ment de dÃ©monstration'),
(264,'item',1,'de','Demoartikel'),
(265,'item',1,'es','ArtÃ­culo de demostraciÃ³n'),
(627,'lang',2,'fr','Espagnole'),
(626,'lang',2,'en','Spanish'),
(619,'lang',4,'fr','Français'),
(618,'lang',4,'en','French'),
(623,'lang',3,'fr','Allemande'),
(622,'lang',3,'en','German'),
(316,'texts',82,'fr','Texte de navigation'),
(315,'texts',82,'es','Texto de navegaciÃ³n'),
(314,'texts',82,'de','Navigationstext'),
(320,'texts',83,'fr','Survol du texte de navigation'),
(319,'texts',83,'es',' Desplazamiento del texto de navegaciÃ³n'),
(318,'texts',83,'de','Navi-Text-Hover'),
(317,'texts',82,'en','Nav text'),
(321,'texts',83,'en','Nav text hover'),
(322,'texts',84,'en','Days - text - hover'),
(323,'texts',84,'fr',' Survol du texte des jours'),
(324,'texts',84,'de','Tage Text schweben'),
(325,'texts',84,'es','Dias - texto hover'),
(326,'texts',85,'en','You don\'t have any items yet.<br>Add your calendar item by clicking on the + symbol above.'),
(327,'texts',85,'fr','Vous n\'avez pas encore d\'Ã©lÃ©ments.<br>Ajoutez votre Ã©lÃ©ment de calendrier en cliquant sur le symbole + ci-dessus.'),
(328,'texts',85,'de','Sie haben noch keine EintrÃ¤ge.<br>FÃ¼gen Sie Ihren Kalendereintrag hinzu, indem Sie oben auf das +-Symbol klicken.'),
(329,'texts',85,'es','AÃºn no tienes ningÃºn elemento. <br> Agrega tu elemento de calendario haciendo clic en el sÃ­mbolo + arriba.'),
(349,'texts',1,'es','Hoy'),
(348,'texts',1,'de','Heute'),
(433,'texts',86,'es','L'),
(432,'texts',86,'de','Mo'),
(431,'texts',86,'fr','Lun'),
(430,'texts',86,'en','Mon'),
(441,'texts',87,'es','M'),
(504,'texts',88,'de','Di'),
(444,'texts',89,'de','Do'),
(443,'texts',89,'fr','Jeu'),
(428,'texts',90,'de','Fr'),
(427,'texts',90,'fr','Ven'),
(426,'texts',90,'en','Fri'),
(424,'texts',91,'de','Sa'),
(423,'texts',91,'fr','Sam'),
(861,'texts',92,'es','Dom'),
(860,'texts',92,'de','So'),
(859,'texts',92,'fr','Dim'),
(440,'texts',87,'de','Mi'),
(422,'texts',91,'en','Sat'),
(439,'texts',87,'fr','Mer'),
(503,'texts',88,'fr','Mar'),
(442,'texts',89,'en','Thurs'),
(858,'texts',92,'en','Sun'),
(425,'texts',91,'es','S'),
(429,'texts',90,'es','V'),
(438,'texts',87,'en','Wed'),
(445,'texts',89,'es','J'),
(450,'texts',93,'en','January'),
(451,'texts',93,'fr','janvier'),
(452,'texts',93,'de','Januar'),
(453,'texts',93,'es','enero'),
(454,'texts',94,'en','February'),
(455,'texts',94,'fr','fÃ©vrier'),
(456,'texts',94,'de','Februar'),
(457,'texts',94,'es','febrero'),
(458,'texts',95,'en','March'),
(459,'texts',95,'fr','mars'),
(460,'texts',95,'de','MÃ¤rz'),
(461,'texts',95,'es','marzo'),
(462,'texts',96,'en','April'),
(463,'texts',96,'fr','avril'),
(464,'texts',96,'de','April'),
(465,'texts',96,'es','abril'),
(466,'texts',97,'en','May'),
(467,'texts',97,'fr','mai'),
(468,'texts',97,'de','Mai'),
(469,'texts',97,'es','mayo'),
(470,'texts',98,'en','June'),
(471,'texts',98,'fr','juin'),
(472,'texts',98,'de','Juni'),
(473,'texts',98,'es','junio'),
(474,'texts',99,'en','July'),
(475,'texts',99,'fr','juillet'),
(476,'texts',99,'de','Juli'),
(477,'texts',99,'es','julio'),
(478,'texts',100,'en','August'),
(479,'texts',100,'fr','aoÃ»t'),
(480,'texts',100,'de','August'),
(481,'texts',100,'es','agosto'),
(482,'texts',101,'en','September'),
(483,'texts',101,'fr','septembre'),
(484,'texts',101,'de','September'),
(485,'texts',101,'es','septiembre'),
(486,'texts',102,'en','October'),
(487,'texts',102,'fr','octobre'),
(488,'texts',102,'de','Oktober'),
(489,'texts',102,'es','octubre'),
(490,'texts',103,'en','November'),
(491,'texts',103,'fr','novembre'),
(492,'texts',103,'de','November'),
(493,'texts',103,'es','noviembre'),
(494,'texts',104,'en','December'),
(495,'texts',104,'fr','dÃ©cembre'),
(496,'texts',104,'de','Dezember'),
(497,'texts',104,'es','diciembre'),
(502,'texts',88,'en','Tues'),
(505,'texts',88,'es','M'),
(508,'texts',2,'de','NÃ¤chste'),
(509,'texts',2,'es','Siguiente'),
(512,'texts',3,'de','ZurÃ¼ck'),
(513,'texts',3,'es','Volver'),
(516,'texts',70,'de','Farbe 1 - Hintergrund'),
(517,'texts',70,'es','Color 1 - fondo'),
(520,'texts',71,'de','Farbe 1 - Text'),
(521,'texts',71,'es','Color 1 - texto'),
(524,'texts',73,'de','Farbe 1 - Text'),
(525,'texts',73,'es','Color 2 - texto'),
(528,'texts',72,'de','Farbe 2 - Hintergrund'),
(529,'texts',72,'es','Color 2 - fondo'),
(532,'texts',12,'de','HinzufÃ¼gen'),
(533,'texts',12,'es','AÃ±adir'),
(534,'texts',105,'en','Minimum stay is {x} nights'),
(535,'texts',105,'fr','Le sÃ©jour minimum est de {x} nuits'),
(536,'texts',105,'de','Mindestaufenthalt ist {x} NÃ¤chte'),
(537,'texts',105,'es','Estancia minima es {x} noches'),
(538,'texts',106,'en','The end date can not be before the start date'),
(539,'texts',106,'fr','La date de fin ne peut pas Ãªtre antÃ©rieure Ã  la date de dÃ©but'),
(540,'texts',106,'de','Das Enddatum darf nicht vor dem Startdatum liegen'),
(541,'texts',106,'es','La fecha de salida no puede ser antes de la fecha de entrada'),
(542,'texts',107,'en','Some of the dates selected are not available'),
(543,'texts',107,'fr','Certaines des dates sÃ©lectionnÃ©es ne sont pas disponibles'),
(544,'texts',107,'de','Einige der ausgewÃ¤hlten Daten sind nicht verfÃ¼gbar'),
(545,'texts',107,'es','Algunas de las fechas seleccionadas no estÃ¡n disponibles.'),
(546,'texts',108,'en','Min nights'),
(547,'texts',108,'fr','Nuits minimum'),
(548,'texts',108,'de','Min NÃ¤chte'),
(549,'texts',108,'es','Estancia minima'),
(565,'texts',109,'es','Selecciona fechas - fondo'),
(564,'texts',109,'de','Bereichsauswahl - bg'),
(563,'texts',109,'fr','SÃ©lectionnez les dates - arriÃ¨re-plan'),
(562,'texts',109,'en','Range select - bg'),
(566,'texts',110,'en','Range select - bg2'),
(567,'texts',110,'fr','SÃ©lectionnez les dates - arriÃ¨re-plan 2'),
(568,'texts',110,'de','Bereichsauswahl - bg2'),
(569,'texts',110,'es','Selecciona fechas - fondo 2'),
(588,'texts',111,'de','Notiz:\r\n\r\n - Datumsauswahl und Hover-Stile sind nicht interaktiv. Um den Effekt zu sehen, mÃ¼ssen Sie Ihre Ã„nderungen speichern und dann den Live-Kalender testen.\r\n\r\n- Wenn Sie die Kalendernavigation auf dieser Seite verwenden, um den Monat zu Ã¤ndern, w'),
(589,'texts',111,'es','Nota:\r\n\r\n - Los estilos de selecciÃ³n de fecha y desplazamiento no son interactivos. Para ver el efecto, debes guardar tus cambios y  probar el calendario en vivo.\r\n\r\n- Si utilizas la navegaciÃ³n del calendario en esta pÃ¡gina para cambiar el mes, los nue'),
(586,'texts',111,'en','Notes:\r\n\r\n- Date range select and hover styles are not interactive.  To see the effect you must save your changes then test the live calendar.\r\n\r\n- If you use the calendar navigation on this page to change the month the new months will not show any modifi'),
(587,'texts',111,'fr','Noter:\r\n\r\n- Les styles de sÃ©lection de date et de survol ne sont pas interactifs. Pour voir l\'effet, vous devez enregistrer vos modifications, puis tester le calendrier en direct.\r\n\r\n- Si vous utilisez la navigation dans le calendrier sur cette page pour'),
(592,'texts',7,'de','_Items'),
(593,'texts',7,'es','Ãtems'),
(594,'texts',112,'en','Name'),
(595,'texts',112,'fr','Nom'),
(596,'texts',112,'de','Name'),
(597,'texts',112,'es','Nombre'),
(598,'texts',113,'en','Telephone'),
(599,'texts',113,'fr','TÃ©lÃ©phone'),
(600,'texts',113,'de','Telefon'),
(601,'texts',113,'es','TelÃ©fono'),
(602,'texts',114,'en','Dates'),
(603,'texts',114,'fr','Date'),
(604,'texts',114,'de','Datumsauswahl und Hover-Stile sind nicht interaktiv. Um den Effekt zu sehen, mÃ¼ssen Sie Ihre Ã„nderungen speichern und dann den Live-Kalender testen.'),
(605,'texts',114,'es','Fechas'),
(606,'texts',115,'en','Observations'),
(607,'texts',115,'fr','Observations'),
(608,'texts',115,'de','Beobachtungen'),
(609,'texts',115,'es','Comentarios'),
(616,'lang',1,'de','Englisch'),
(615,'lang',1,'fr','Anglaise'),
(614,'lang',1,'en','English'),
(617,'lang',1,'es','Inglés'),
(621,'lang',4,'es','Francés'),
(625,'lang',3,'es','Alemán'),
(629,'lang',2,'es','Español'),
(632,'texts',27,'de','Ein neues Passwort wurde an Ihre E-Mail gesendet'),
(633,'texts',27,'es','Una contraseña nueva ha sido enviado a su email'),
(636,'texts',63,'de','Administrator'),
(637,'texts',63,'es','Administrador'),
(640,'texts',25,'de','Möchten Sie dieses Element wirklich löschen?'),
(641,'texts',25,'es','¿Estas seguro que deseas borrar este ítem?'),
(644,'texts',50,'de','Verfügbarkeit'),
(645,'texts',50,'es','Disponibilidad'),
(647,'texts',76,'fr','Réservé - arrière-plan'),
(648,'texts',76,'de','Gebucht - Hintergrund'),
(649,'texts',76,'es','Reservado - fondo'),
(652,'texts',77,'de','Gebucht - SMS'),
(653,'texts',77,'es','Reservado - texto'),
(656,'texts',14,'de','Kalender'),
(657,'texts',14,'es','Calendario'),
(660,'texts',80,'de','Kalenderelement'),
(661,'texts',80,'es','Ãtem para mostrar'),
(664,'texts',57,'de','Kalender-URL'),
(665,'texts',57,'es','Url calendario'),
(668,'texts',42,'de','Code'),
(669,'texts',42,'es','Código'),
(671,'texts',74,'fr','Jours - arrière-plan'),
(672,'texts',74,'de','Tage - Hintergrund'),
(673,'texts',74,'es','Dias - fondo'),
(676,'texts',75,'de','Tage - Text'),
(677,'texts',75,'es','Dias - texto'),
(680,'texts',58,'de','Standardsprache'),
(681,'texts',58,'es','Idioma por defecto'),
(684,'texts',13,'de','Löschen'),
(685,'texts',13,'es','Borrar'),
(687,'texts',23,'fr','Faites glisser pour modifier l\'ordre'),
(688,'texts',23,'de','Ziehen, um die Reihenfolge zu ändern'),
(689,'texts',23,'es','Arrastra para cambiar el orden'),
(692,'texts',26,'de','Bearbeiten'),
(693,'texts',26,'es','Modificar'),
(696,'texts',34,'de','Email'),
(697,'texts',34,'es','Email'),
(700,'texts',60,'de','Ich habe mein Passwort vergessen'),
(701,'texts',60,'es','He olvidado mi contraseña'),
(704,'texts',15,'de','ID'),
(705,'texts',15,'es','ID'),
(707,'texts',16,'fr','ID externe'),
(708,'texts',16,'de','ID extern'),
(709,'texts',16,'es','ID externo'),
(712,'texts',53,'de','WICHTIG - Wenn Sie diesen Code ändern, stellen Sie sicher, dass Sie auch seine Referenz im PHP- oder JavaScript-Code ändern'),
(713,'texts',53,'es','IMPORTANTE - Si modificas este código no olvides de modificarlo en el código PHP o JavaScript'),
(716,'texts',66,'de','ISO 2-Buchstaben-Code'),
(717,'texts',66,'es','ISO código 2 letras'),
(720,'texts',18,'de','Es war NICHT möglich, den Artikel hinzuzufügen'),
(721,'texts',18,'es','NO ha sido posible añadir el ítem'),
(724,'texts',22,'de','Es war NICHT möglich, das Element zu löschen'),
(725,'texts',22,'es','NO ha sido posible borrar el ítem'),
(728,'texts',20,'de','Es war NICHT möglich, den Artikel zu ändern'),
(729,'texts',20,'es','NO ha sido posible modificar el í­tem'),
(732,'texts',28,'de','Es war NICHT möglich, Ihr neues Passwort zu senden'),
(733,'texts',28,'es','NO ha sido posible enviar la contraseña nueva'),
(736,'texts',24,'de','Artikel existiert nicht'),
(737,'texts',24,'es','Este ítem no existe'),
(740,'texts',17,'de','Artikel wurde erfolgreich hinzugefügt'),
(741,'texts',17,'es','Ítem ha sido añadido con éxito'),
(744,'texts',21,'de','Artikel wurde erfolgreich gelöscht'),
(745,'texts',21,'es','item ha sido borrado con éxito'),
(748,'texts',41,'de','Benutzer können nur ihre eigenen Elemente und Anmeldedaten ändern.'),
(749,'texts',41,'es','Usuarios solo pueden administrar sus propios í­tems y datos de login.'),
(756,'texts',19,'de','Artikel wurde erfolgreich geändert'),
(755,'texts',19,'fr','L\'élément a été modifié avec succès'),
(754,'texts',19,'en','Item has been modified successfully'),
(757,'texts',19,'es','Ítem ha sido añdido con éxito'),
(759,'texts',55,'fr','Langue'),
(760,'texts',55,'de','Sprache'),
(761,'texts',55,'es','Idioma'),
(764,'texts',65,'de','Sprache'),
(765,'texts',65,'es','Idiomas'),
(768,'texts',40,'de','Niveau'),
(769,'texts',40,'es','Nivel'),
(772,'texts',59,'de','Anmeldung'),
(773,'texts',59,'es','Login'),
(776,'texts',9,'de','Ausloggen'),
(777,'texts',9,'es','Salir'),
(780,'texts',37,'de','Geben Sie das Kennwort nur ein, wenn Sie es ändern möchten.'),
(781,'texts',37,'es','Solo introduces la contraseÃ±a si desea modificarlo.'),
(784,'texts',56,'de','Nicht übersetzt'),
(785,'texts',56,'es','Sin traducir'),
(788,'texts',33,'de','Optionen'),
(789,'texts',33,'es','Opciones'),
(792,'texts',52,'de','Sortieren nach'),
(793,'texts',52,'es','Ordenar por'),
(797,'texts',69,'es','Página no existe'),
(800,'texts',67,'de','Um eine neue Sprache hinzuzufügen, definieren Sie sie zuerst hier und ändern Sie sie dann mit dem neuen Sprachwert. <br>Vergessen Sie nicht, anschließend die Texte und Artikelnamen zu ändern, um die neue Sprache hinzuzufügen'),
(801,'texts',67,'es','Para agregar un nuevo idioma, primero defínalo aquí y luego modifíquelo con el nuevo valor de idioma. <br>No olvide modificar los textos y los nombres de los elementos para agregar el nuevo idioma'),
(804,'texts',35,'de','Passwort'),
(805,'texts',35,'es','Contraseña'),
(808,'texts',29,'de','Passwort-Erinnerung'),
(809,'texts',29,'es','Recordatorio de contraseña'),
(812,'texts',36,'de','Passwort wiederholen'),
(813,'texts',36,'es','Repite la contraseña'),
(816,'texts',6,'de','Profil'),
(817,'texts',6,'es','Perfíl'),
(820,'texts',81,'de','Radius'),
(821,'texts',81,'es','Radio'),
(824,'texts',38,'de','Sie müssen eine gültige E-Mail-Adresse verwenden'),
(825,'texts',38,'es','Debes introducir un email vá¡lido'),
(828,'texts',68,'de','Sie müssen nun die neue Sprachdefinition hinzufügen'),
(829,'texts',68,'es','Ahora debe agregar la nueva definición de idioma'),
(835,'texts',30,'fr','Vous avez oublié votre mot de passe pour le panneau d\'administration du calendrier. Pour des raisons de sécurité, un nouveau mot de passe a été créé.<br>Votre nouveau mot de passe est {new_password}<br>Veuillez vous connecter et modifier cela dès que poss'),
(834,'texts',30,'en','You have forgotten your password for the calendar admin panel.\r\nFor security reasons a new password has been created.\r\nYour new password is {new_password}\r\n\r\nPlease login and change this as soon as possible.\r\n'),
(840,'texts',43,'de','Wert'),
(841,'texts',43,'es','Valor'),
(844,'texts',10,'de','Benutzer'),
(845,'texts',10,'es','Usuarios'),
(848,'texts',62,'de','Benutzer existiert nicht'),
(849,'texts',62,'es','El usuario no existe'),
(852,'texts',64,'de','Benutzer'),
(853,'texts',64,'es','Usuario'),
(856,'texts',31,'de','Titel'),
(857,'texts',31,'es','Título'),
(864,'texts',78,'de','Stile'),
(865,'texts',78,'es','Estilos'),
(868,'texts',51,'de','Wiederholen Sie das Passwort'),
(869,'texts',51,'es','Repite la contraseña'),
(872,'texts',79,'de','Stile auf Standard zurücksetzen'),
(873,'texts',79,'es','Resetear estilos por defecto'),
(876,'texts',39,'de','Das Passwort ist ungültig'),
(877,'texts',39,'es','La contraseña no es válido'),
(880,'texts',49,'de','Der Code wird im PHP-Code verwendet, um auf den Text zu verweisen'),
(881,'texts',49,'es','El código se usa en el código PHP para hacer referencia al texto.'),
(884,'texts',8,'de','Texte'),
(885,'texts',8,'es','Textos'),
(888,'texts',32,'de','Zustand'),
(889,'texts',32,'es','Estado'),
(892,'texts',5,'de','Einstellungen'),
(893,'texts',5,'es','Ajustes'),
(896,'texts',61,'de','Passwort senden'),
(897,'texts',61,'es','Envia contraseña'),
(900,'texts',11,'de','Speichern'),
(901,'texts',11,'es','Guardar'),
(904,'texts',4,'de','Schicken'),
(905,'texts',4,'es','Enviar')
	";

	// texts
	$sql["Create Table - TEXTS"]="
	CREATE TABLE `".AC_DB_PREFIX."ac_texts` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`code` char(50) DEFAULT NULL,
  		`state` tinyint(1) NOT NULL DEFAULT '1',
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1
	";
	
	// texts - insert
	$sql["Insert data - TEXTS"] = "
	INSERT INTO `".AC_DB_PREFIX."ac_texts` (`id`, `code`, `state`) VALUES
		(1, 'today', 1),
		(2, 'next', 1),
		(3, 'back', 1),
		(4, 'bt_submit', 1),
		(5, 'admin_nav_settings', 1),
		(6, 'admin_nav_profile', 1),
		(7, 'admin_nav_items', 1),
		(8, 'admin_nav_texts', 1),
		(9, 'admin_nav_logout', 1),
		(10, 'admin_nav_users', 1),
		(11, 'save', 1),
		(12, 'add', 1),
		(13, 'delete', 1),
		(14, 'calendar', 1),
		(15, 'id', 1),
		(16, 'id_external', 1),
		(17, 'msg_add_OK', 1),
		(18, 'msg_add_KO', 1),
		(19, 'msg_mod_OK', 1),
		(20, 'msg_mod_KO', 1),
		(21, 'msg_delete_OK', 1),
		(22, 'msg_delete_KO', 1),
		(23, 'drag_to_order', 0),
		(24, 'msg_item_not_exist', 1),
		(25, 'msg_delete_confirm', 1),
		(26, 'edit', 1),
		(27, 'msg_passwrord_sent_OK', 1),
		(28, 'msg_password_sent_KO', 1),
		(29, 'email_password_subject', 1),
		(30, 'email_password_body', 1),
		(31, 'title', 1),
		(32, 'state', 1),
		(33, 'options', 1),
		(34, 'email', 1),
		(35, 'password', 1),
		(36, 'password_repeat', 1),
		(37, 'note_password_mod', 1),
		(38, 'msg_email_KO', 1),
		(39, 'msg_password_KO', 1),
		(40, 'level', 1),
		(41, 'note_admin_level', 1),
		(42, 'code', 1),
		(43, 'value', 1),
		(44, 'note_text_add', 1),
		(52, 'order_by', 1),
		(51, 'note_password_repeat', 1),
		(50, 'admin_nav_availability', 1),
		(49, 'note_text_code', 1),
		(53, 'note_id_ref_external_mod', 1),
		(54, 'note_variable_password', 1),
		(55, 'language', 1),
		(56, 'not_translated', 1),
		(57, 'cal_url', 1),
		(58, 'default_lang', 1),
		(59, 'bt_login', 1),
		(60, 'bt_password_reminder', 1),
		(61, 'bt_send_password', 1),
		(62, 'msg_user_not_exist', 1),
		(63, 'admin_level_administrator', 1),
		(64, 'admin_level_user', 1),
		(65, 'admin_nav_languages', 1),
		(66, 'note_langcode', 1),
		(67, 'note_lang_add', 1),
		(68, 'msg_add_new_lang_definition', 1),
		(69, 'msg_no_permited', 1),
		(70, 'style-color1-bg', 1),
		(71, 'style-color1-txt', 1),
		(72, 'style-color2-bg', 1),
		(73, 'style-color2-txt', 1),
		(74, 'style-numbers-bg', 1),
		(75, 'style-numbers-txt', 1),
		(76, 'style-booked-bg', 1),
		(77, 'style-booked-txt', 1),
		(78, 'styles', 1),
		(79, 'bt_reset_styles', 1),
		(80, 'item_to_show', 1),
		(81, 'style-border-radius', 1),
		(82, 'style-nav-txt', 1),
		(83, 'style-nav-txt-hover', 1),
		(84, 'style-numbers-txt-hover', 1),
		(85, 'msg_add_item', 1),
		(86, 'cal_day_1', 1),
		(87, 'cal_day_3', 1),
		(88, 'cal_day_2', 1),
		(89, 'cal_day_4', 1),
		(90, 'cal_day_5', 1),
		(91, 'cal_day_6', 1),
		(92, 'cal_day_7', 1),
		(93, 'cal_month_01', 1),
		(94, 'cal_month_02', 1),
		(95, 'cal_month_03', 1),
		(96, 'cal_month_04', 1),
		(97, 'cal_month_05', 1),
		(98, 'cal_month_06', 1),
		(99, 'cal_month_07', 1),
		(100, 'cal_month_08', 1),
		(101, 'cal_month_09', 1),
		(102, 'cal_month_10', 1),
		(103, 'cal_month_11', 1),
		(104, 'cal_month_12', 1),
		(105, 'alert_min_nughts', 1),
		(106, 'alert_end_before_start', 1),
		(107, 'alert_dates_not_avail', 1),
		(108, 'min_nights', 1),
		(109, 'style-select-range', 1),
		(110, 'style-select-between', 1),
		(111, 'note_interactive_settings_admin', 1),
		(112, 'name', 1),
		(113, 'tel', 1),
		(114, 'dates', 1),
		(115, 'comments', 1);
	";


	//	loop through table create and inserts
	foreach($sql AS $type=>$query){
		//echo "<br>".$type;
		mysqli_query($db_cal,$query) or die("Error creating database table - ".$type."<br>".$query."<br>".mysqli_error($db_cal));
	}
	return true;

}
?>