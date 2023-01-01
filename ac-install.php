<?php
/*
Script		: Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: Chris Bolson www.cbolson.com

File		: ac-install.php
Date Add    : 2009-06-08
Date Mod    : 2023-01-01
Use         : 1. Define config values (check file is writable first)
		      2. Install database
		      3. Check files exist?
*/


define("FILE_CONFIG"	, dirname(__FILE__)."/ac-config.inc.php");
define("CAL_VERSION"	, "v3.04.02");

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
	
	//	check connection
	//	try to connect to db
	//	general config
	$the_file=FILE_CONFIG;
	if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
	else		require_once($the_file);
	
	
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
				<table style="font-size:0.8em;">
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
	if(!mysql_is_table(AC_DB_PREFIX."bookings")){
			echo "here¿¿¿";
		//	add tables.....
		if(create_tables())		$check_item_states[3]="ok";
		else 					$check_item_states[3]="ko";
	}else{
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
		
	//	admin functions
	//$the_file=AC_INCLUDES_ROOT."functions-admin.inc.php";
	//if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
	//else		require_once($the_file);
	
	if(isset($_POST["add_config"])){
		//	insert calendar config - TO DO
		$mod_item=mod_item(T_BOOKINGS_CONFIG,1,$_POST["mod"],false);
		if($mod_item=="OK")	$check_item_states[4]="ok";
		else 				$check_item_states[4]="ko";
	}else{
	
		//	define calendar configuration settings
		
		//	include lang file
		//$the_file=AC_DIR_AC_LANG."en.lang.php";
		//if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
		//else		require_once($the_file);
		
		
		$cal_config_form='
		Define your calendar options:
		<div style="padding:20px;">
			<form method="post" action="" >
			<input type="hidden" name="add_config" value="1">
			<table style="font-size:0.8em;">
				'.rowData('<label for="id_title">Calendar Title</label>' 				, '<input id="id_title" type="text" name="mod[title]" value="'.$row_config["title"].'" style="width:300px;" />').'
				'.rowData('<label for="id_cal_url">Local Calendar root</label>'			, '<input id="id_cal_url" type="text" name="mod[cal_url]" value="'.dirname($_SERVER["SCRIPT_NAME"]).'" style="width:150px;" /> <span class="note">'.$lang["note_cal_url"].'</span>').'
				
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
	Congratulations, your calendar is now ready to use.
	<h3>Now what?</h3>
	<ol>
		<li><span style="color:red;">Remove</span> this file (<strong>ac-install.php</strong>) from your FTP - the calendar will not be shown until you do this.</li>
		<li>Reset the <strong>ac-config.inc.php</strong> file write permissions (eg set to 644). (linux)</li>
		<li>
			<a href="ac-admin/index.php" target="_blank">Login</a> to your admin panel to administrate your calendar(s)
			<br> - Username : "admin"
			<br> - Password : "demo" (You should change these as soon as possible).
		</li>
		<li>Click <a href="index.php">here</a> to view/test your calendar.</li>
		<li>Check the css files @ <strong>/ac-contents/themes/default/css/avail-calendar.css</strong> to define the image path to the special state background image.</li>
		<li>To add the calendar in your site, read the <a href="http://www.ajaxavailabilitycalendar.com/implementation" target="_blank">implementation instructions</a>.</li>
	</ol>
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
            
            <link rel="stylesheet" href="ac-admin/assets/admin.css?v2" />
		</head>
		<body class="install">
	    <main>
			<header class="header">
			
                <div class="header__logo"><img src="/ac-assets/logo-acc.svg" title="Availability Calendar - Admin" width="300"></div>
                <h1 class="header__version"><?php echo CAL_VERSION; ?></h1>
            </header>
			<section>
				<h2>
				    Follow these steps to install the calendar in your server.
                </h2>
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
					<input type="image" src="/ac-assets/donate-paypal.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online." style="border:none; width:80px;">
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
  `the_date` date NOT NULL default '0000-00-00',
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
  `id` int(11) NOT NULL auto_increment,
  `level` tinyint(1) NOT NULL default '2',
  `username` varchar(20) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `state` tinyint(1) NOT NULL default '1',
  `date_visit` datetime NOT NULL default '0000-00-00 00:00:00',
  `visits` int(11) NOT NULL default '0',
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
	`date_visit`=	'0000-00-00 00:00:00',
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
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
";

//	Dumping data for table `bookings_config`
$sql["Insert Data - CONFIG"]="
INSERT INTO `".AC_DB_PREFIX."ac_config` SET
	`id`				=	1,
	`title`				=	'Availability Calendar',
	`num_months`		=	3,
	`default_lang`		=	'en',
	`start_day`			=	'sun',
	`date_format`		=	'eu',
	`click_past_dates`	=	'off',
	`cal_url`			=	'/calendar',
	`version`			= '".CAL_VERSION."'
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
	`id`		=	1,
	`id_user`	=	1,
	`id_ref_external`=0,
	`desc_en`	=	'Demo Item',
	`desc_es`	=	'Demo',
	`list_order`=	1,
	`state`		=	1
";

//	Table structure for table `bookings_last_update`
$sql["Create Table - UPDATE TIME"]="
CREATE TABLE IF NOT EXISTS `".AC_DB_PREFIX."ac_last_update` (
  `id` int(10) NOT NULL auto_increment,
  `id_item` int(10) NOT NULL default '0',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
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
	(3, 'Booked pm', 'Reservado pm', 'b_pm', 1, 2, 'booked_pm'),
	(4, 'Provisional', 'Provisional', 'pr', 1, 3, 'booked_pr'),
	(5, 'Provisional am', 'Provisional am', 'pr_am', 1, 4, 'booked_pr_am'),
	(6, 'Provisional pm', 'Provisional pm', 'pr_pm', 1, 5, 'booked_pr_pm');

";
	
// texts
$sql["Create Table - TEXTS"]="
CREATE TABLE `ac_texts` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `code` char(50) DEFAULT NULL,
 `langcode` char(2) DEFAULT NULL,
 `txt` varchar(255) CHARACTER SET utf8 NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
";
	//	loop through table create and inserts
	foreach($sql AS $type=>$query){
	//	echo "<br>".$type;
		mysqli_query($db_cal,$query) or die("Error creating database table - ".$type."<br>".$query."<br>".mysqli_error($db_cal));
	}
	return true;

}
?>