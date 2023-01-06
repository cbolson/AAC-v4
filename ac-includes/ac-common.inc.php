<?php
/*
Script		: Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: Chris Bolson www.cbolson.com

File		: ac-common.inc.php
Date		: 2021-10-01
Use			: get calendar configuration data and define constants
			  get available languages from db
*/


// display errors
// NOTE - comment these out for production
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1); 

//	define tables
define("AC_TBL_CONFIG"				, AC_DB_PREFIX."ac_config");				# general config
define("AC_TBL_AVAIL"				, AC_DB_PREFIX."ac_availability"); 			# bookings dates
define("AC_TBL_USERS"				, AC_DB_PREFIX."ac_users");					# admin users
define("AC_TBL_STATES"				, AC_DB_PREFIX."ac_states");				# booking types (am, pm, etc)
define("AC_TBL_ITEMS"				, AC_DB_PREFIX."ac_items");					# calendar items
define("AC_TBL_LANGUAGES"			, AC_DB_PREFIX."ac_languages");				# languages
define("AC_TBL_TEXTS"				, AC_DB_PREFIX."ac_texts");					# text codes
define("AC_TBL_TRANSLATIONS"		, AC_DB_PREFIX."ac_translations");			# translations (texts, lang, items and states)

//	include - db connection
$the_file=AC_INCLUDES_ROOT."ac-db-connect.inc.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else		require_once($the_file);

//	get config from database - can be defined manually below if needed
$sql="
SELECT 
	cal_url,
	title,
	default_lang,
	start_day,
	date_format,
	click_past_dates,
	num_months,
	theme,
	version,
	styles,
	min_nights
FROM 
	".AC_TBL_CONFIG."
";
$res=mysqli_query($db_cal,$sql) or returnError('1.03',"calendar database tables not created<br>".mysqli_error($db_cal));
$row_config=mysqli_fetch_assoc($res);

//	define  constants
define("AC_URL"		, "".$row_config["cal_url"]);
define("AC_INCLUDES_PUBLIC"		, "".AC_URL."ac-includes/");
define("AC_TITLE"				, "".$row_config["title"]."");
define("AC_DEFAULT_LANG"		, "".$row_config["default_lang"]."");
define("AC_START_DAY"			, "".$row_config["start_day"]."");	
define("AC_DATE_DISPLAY_FORMAT"	, "".$row_config["date_format"]."");	
define("AC_ACTIVE_PAST_DATES"	, "".$row_config["click_past_dates"]."");
define("CAL_VERSION"			, "".$row_config["version"]."");
define("AC_LOGO"				, "".AC_URL."ac-assets/logo-acc.svg");
define("AC_LOGO_EMAIL"			, "".AC_URL."ac-admin/assets/logo-email.png");
define("AC_CSS_BASE_COLOR"		, "200");
define("AC_MIN_NIGHTS"			, $row_config["min_nights"]); # CHANGE TO DB VAL

//	define directories
define("AC_CONTENTS_ROOT"		, AC_ROOT."ac-contents/");
define("AC_CONTENTS_PUBLIC"		, AC_URL."ac-contents/");	#	content - themes, languages etc.
define("AC_DIR_ADMIN"			, AC_ROOT."ac-admin/");					#	administration

// current timestamp - used to check if date is in past)
$cur_date=mktime(0,0,0,date('m'),date('d'),date('Y'));
define("CUR_DATE", $cur_date);	



if($inc_languages){
	if(isset($_REQUEST["lang"])) 	$cur_lang=$_REQUEST["lang"];
	else 							$cur_lang=AC_DEFAULT_LANG;
	
	// define language to use BEFORE other includes but AFTER common file incase we need default language
	if(isset($_SESSION["admin"]["lang"]))	define("AC_LANG", $_SESSION["admin"]["lang"]);
	else 									define("AC_LANG", AC_DEFAULT_LANG);

	// get active languages for items that require translations
	$ac_languages=array();
	$sql="
	SELECT 
		l.code,
		lt.txt 
	FROM 
		".AC_TBL_LANGUAGES." AS l
		LEFT JOIN ".AC_TBL_TRANSLATIONS." AS lt ON lt.id_text=l.id AND type='lang' AND lt.langcode='".AC_LANG."'
	WHERE 
		l.state=1
	ORDER BY lt.txt ASC
	";
	$res=mysqli_query($db_cal,$sql) or die("Error - languages");
	while($row=mysqli_fetch_assoc($res)){
		$ac_languages[$row["code"]]=$row["txt"];
	}
}

if($inc_translations){
	// include - lang translations from db
	$the_file=AC_INCLUDES_ROOT."ac-translations.inc.php";
	if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
	else		require_once($the_file);
}

if($inc_functions){	
	// include - functions
	$the_file=AC_INCLUDES_ROOT."ac-functions.inc.php";
	if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
	else		require_once($the_file);
}

// define error codes for admin
$error_codes=array();
$error_codes["1.03"]= array(
	"issue"	=> "Calendar database tables have not been created",
	"fix"	=> "Check database connection data and run the install script if required"
);
$error_codes["2.01"] = array(
	"issue"	=> "Install script has not been removed",
	"fix"	=> "Remove instal script from FTP for security reasons."
);
?>