<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-settings.php
Date mod	: 	2023-02-05
Use			: 	general calendar config options
*/


// DEFAULT COLORS - DO NOT REMOVE AS WE NEED TO INSERT THESE WITH THE INSTALL SCRIPT
// if it all goes wrong you can un-comment these lines and then submit the form (via admin) to reset the defaults manually
// $arr = [];
// $arr['--ac-month-bg'] = "#FFFFFF";
// $arr['--ac-month-title-bg'] = '#046889';
// $arr['--ac-month-title-clr']= '#FFFFFF';
// $arr['--ac-weekday-bg'] 	= '#8fd9f2';
// $arr['--ac-weekday-clr'] 	= '#000000';
// $arr['--ac-day-bg'] 		= '#f0f0f0';
// $arr['--ac-day-clr'] 		= '#000000';
// $arr['--ac-day-clr-hover'] 	= '#ffa500';
// $arr['--ac-weekend-bg'] 	= '#f0f0f0';
// $arr['--ac-weekend-clr'] 	= '#000000';
// $arr['--ac-nav-clr'] 		= '#046889';
// $arr['--ac-nav-clr-hover'] 	= '#000000';
// $arr['--ac-booked-bg'] 		= '#ff9090';
// $arr['--ac-booked-clr'] 	= '#000000';
// $arr['--ac-select-range'] 	= '#ffcc00';
// $arr['--ac-select-between'] = '#fdeeb3';
// $arr['--ac-border-radius'] 	= "10";
// $_POST["mod-styles"] = $arr;


// echo addslashes(serialize($arr)); # for db insert in ac-install
// exit();


//	NOTE - $row_config is defined in the common file
if(isset($_POST["mod"])){
	/*
	we store the colors as a serialized string
	this way we can add more styles without having to create new db fields
	*/
	
	// serialize colors
	$_POST["mod"]["styles"]=serialize($_POST["mod-styles"]);
	
	$mod_item=modItem(AC_TBL_CONFIG,1,$_POST["mod"],false);
	if($mod_item=="OK")	header("Location:index.php?p=".AC_PAGE."&msg=mod_OK");
	else				$msg=$lang["msg_mod_KO"];
}

// unserialize styles
$styles = unserialize($row_config["styles"]);

  



// define array settings
// NOTE - this is currently only returning the styles but it may return more settings in the future
$row_settings='';
// add each style
foreach($styles AS $key=>$val){
	$label=$ac_lang[str_replace("--ac-","style-",$key)];
	if(empty($label)) $label = '<span style="color:red;">'.$key.'</span>';
	if(substr($key,-7)=="-radius"){
		$field_style='<input type="range" id="'.$key.'" name="mod-styles['.$key.']" value="'.$val.'" min="0" max="30" style="width:120px;" class="slider" oninput="rangeChange(this)" onchange="rangeChange(this)">';	
	}else{
		$color_txt=getContrastColor($val);
		$field_style='<input type="text" id="'.$key.'" name="mod-styles['.$key.']" 	value="'.$val.'" style="width:120px;background-color: '.$val.';color:'.$color_txt.'" style-input="1">';

		// Note - I am not using the "color" type as this doesn't show the color value and 
		// some users may want to introduce the colors manually to suit their existing design.
	}
	$row_settings.=fieldRow($label,''.$key.'',''.$field_style.'');
}

$contents.='
<form method="post" action="">
	<input type="hidden" name="page" value="'.AC_PAGE.'">
		
	<div class="block">
		'.fieldRow($ac_lang["cal_url"] 					, 'cal_url'			, '<input type="text" 	id="cal_url" 		name="mod[cal_url]" 		value="'.$row_config["cal_url"].'" placeholder="https://">').'
		'.fieldRow($ac_lang["default_lang"] 			, 'default_lang'	, '<select 				id="default_lang"	name="mod[default_lang]" 	style="width:140px;">'.selectListOptions($ac_languages,$row_config["default_lang"]).'</select> <a href="?p=settings&action=new_lang">'.$ac_lang["bt_new_language"].'</a>').'
		'.fieldRow($ac_lang["min_nights"] 				, 'min_nights'		, '<input type="number" id="min_nights"		name="mod[min_nights]"		value="'.$row_config["min_nights"].'" min="0" max="100" style="width:140px;">').'
		<h2>'.$ac_lang["styles"].'</h2>
		<div class="block-cols">
			<div class="settings-colors">
				'.$row_settings.'
			</div>
			<div>
				<div class="settings-sticky">
					<ac-calendar 
					ac-id="1" 
					ac-months-to-show="1"
					ac-date-start="date-start"
					ac-date-end="date-end"
					></ac-calendar>
					<br>
					<span id="bt-reset-styles" onclick="resetStyles()" class="pseudo-button">'.$ac_lang["bt_reset_styles"].'</span>
					<br>&nbsp;
					<div class="block-msg advice" >
						'.$ac_lang["note_interactive_settings_admin"].'
					</div>
					<!-- required to allow the demo calendar to select dates -->
					<input type="hidden" id="date-start"> 
					<input type="hidden" id="date-end">
				</div>
			</div>
		</div>
	</div>
	<div class="block-buttons">
		<input type="submit" value="'.$ac_lang["save"].'" />
	</div>
</form>
<script>
// define path for ajax files BEFORE including the calendar JavaScript file
let urlRoot="'.AC_URL.'";
</script>
';
		$xtra_css_files.='<link rel="stylesheet" href="/ac-admin/assets/hubee.css">';
		$xtra_js_files.='
		<script src="'.AC_URL.'ac-js/ac-calendar.js" type="module"></script>
		<script src="assets/huebee.pkgd.min.js" defer></script>
		<script src="assets/admin-cal-settings.js" defer></script>
		';
?>