<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-settings.php
Date		: 	2021-10-04
Use			: 	general calendar config options
*/


// DEFAULT COLORS - DO NOT REMOVE AS WE NEED TO INSERT THESE WITH THE INSTALL SCRIPT

/*
$arr=[];
$arr["--ac-color1-bg"] 			= "#8fd9f2";
$arr["--ac-color1-txt"] 		= "#046889";
$arr["--ac-color2-bg"] 			= "#046889";
$arr["--ac-color2-txt"] 		= "#FFFFFF";
$arr["--ac-numbers-bg"] 		= "#FFFFFF";
$arr["--ac-numbers-txt"] 		= "#046889";
$arr["--ac-numbers-txt-hover"] 	= "#000000";
$arr["--ac-booked-bg"] 			= "#ff9090";
$arr["--ac-booked-txt"] 		= "#333333";
$arr["--ac-select-range"]		= "#FFCC00";
$arr["--ac-select-between"]		= "#fdeeb3";
$arr["--ac-nav-txt"] 			= "#046889";
$arr["--ac-nav-txt-hover"] 		= "#000";
$arr["--ac-border-radius"]		= "8px";
echo serialize($arr);
*/



//	NOTE - $row_config is defined in the common file
if(isset($_POST["mod"])){
	
	/*
	we will store the colors as a serialized stting
	this way we can add more styles without having to create new db fields
	
	*/
	
	// serialize colors
	$_POST["mod"]["styles"]=serialize($_POST["mod-styles"]);
	
	//print_arr($_POST);
	//exit();
	
	
	
	$mod_item=modItem(AC_TBL_CONFIG,1,$_POST["mod"],false);
	if($mod_item=="OK")	header("Location:index.php?p=".AC_PAGE."&msg=mod_OK");
	else				$msg=$lang["msg_mod_KO"];
}
//echo $row_config["styles"];
// unserialize styles
$styles = unserialize($row_config["styles"]);

// define array settings
// NOTE - this is currently only returning the styles but it may return more settings in the future
$row_settings='';
// add each style
foreach($styles AS $key=>$val){
	$label=$ac_lang[str_replace("--ac-","style-",$key)];
	if(substr($key,-7)=="-radius"){
		$field_style='<input type="range" id="'.$key.'" name="mod-styles['.$key.']" value="'.$val.'" min="0" max="30" style="width:120px;" class="slider" oninput="rangeChange(this)" onchange="rangeChange(this)">';	
	}else{
		$field_style='<input type="text" id="'.$key.'" name="mod-styles['.$key.']" 	value="'.$val.'" style="width:120px;background-color: '.$val.';" class="style-input">';
		
	}
	$row_settings.=fieldRow($label,''.$key.'',''.$field_style.'');
}


// '.fieldRow($ac_lang["title"]					, 'title'			, '<input type="text" 	id="title" 			name="mod[title]" 			value="'.$row_config["title"].'" >').'
	
$contents.='
<div class="block">
	<form method="post" action="">
		<input type="hidden" name="page" value="'.AC_PAGE.'">
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
				<div id="demo-calendar"   style="margin-bottom:10px;"></div>
				
				<br>
				<span id="bt-reset-styles" onclick="resetStyles()" class="pseudo-button">'.$ac_lang["bt_reset_styles"].'</span>
				<br>&nbsp;
				<div class="block-msg advice" >
					'.$ac_lang["note_interactive_settings_admin"].'
				</div>
			</div>
			</div>
		</div>
		<div style="border-top:1px solid #DDD; margin-top:2rem; padding-top:2rem; text-align:center">
			'.fieldRowButton('<input type="submit" value="'.$ac_lang["save"].'" />').'
		</div>
	</form>
	
</div>
<script>
// define path for ajax files BEFORE including the calendar JavaScript file
let urlRoot="'.AC_URL.'";
</script>
';
		$xtra_js_files='
		<script src="'.AC_URL.'ac-assets/ac-calendar.js?v=4.51" type="module" id="ac-cal" ac-container="demo-calendar" ac-item="1" ac-months="1"></script>
		<script src="assets/huebee.pkgd.min.js"></script>
		';
		
		$xtra_js.="
		var defaultColors = [
			 ['--ac-color1-bg'			, '#8fd9f2'],
			 ['--ac-color1-txt'			, '#046889'],
			 ['--ac-color2-bg'			, '#046889'],
			 ['--ac-color2-txt'			, '#FFFFFF'],
			 ['--ac-numbers-bg'			, '#FFFFFF'],
			 ['--ac-numbers-txt'		, '#046889'],
			 ['--ac-numbers-txt-hover' 	, '#000000'],
			 ['--ac-booked-bg'			, '#ff9090'],
			 ['--ac-booked-txt'			, '#333333'],
			 ['--ac-select-range'		, '#FFCC00'],
			 ['--ac-select-between'		, '#fdeeb3'],
			 ['--ac-nav-txt' 			, '#046889'],
			 ['--ac-nav-txt-hover'		, '#000000'],
			 ['--ac-border-radius'		, '8px']
		]; 
		
		function resetStyles(){
			// loop through default styles to reset
			for ( var i=0; i < defaultColors.length; i++ ) {
				elId	= defaultColors[i][0];
				elColor	= defaultColors[i][1];
				// update input fields
				document.getElementById(''+elId+'').value=''+elColor+'';
				// update input background to color
				document.getElementById(''+elId+'').style.background=''+elColor+'';
				// update preview
				changeStyle(elId,elColor);
			}
		}
		
		
		document.addEventListener('DOMContentLoaded', function(){
			// hide nav
			//document.getElementById('ac-nav').style.display='hidden';
			 
			 
			// initials on multiple elements with loop
			var elems = document.querySelectorAll('.style-input');
			for ( var i=0; i < elems.length; i++ ) {
				var elem = elems[i];
				let elID=elem.id;
				var hueb = new Huebee( elem, {
					hue0: 210,
				});
				
				hueb.on( 'change', function( color, hue, sat, lum ) {
					changeStyle(elID,color);
				})
			}
			dateClick=false;
			rangeChange = function(el){
				changeStyle(el.id,el.value);
			}
			changeStyle = function(type,val){
				console.log(type);
				switch(type){
					case '--ac-color1-bg':
						// month background and border
						elements=document.querySelectorAll('.ac-month');
					  	for (let el of elements) {
						    el.style.background 	= ''+val+'';
						    el.style.borderColor 	= ''+val+'';
						}
						// weekday titles
						elements=document.querySelectorAll('.ac-day-title li');
					  	for (let el of elements) {
						    el.style.background = ''+val+'';
						}
						break;
	
					case '--ac-color1-txt':
						// controls
						elements=document.querySelectorAll('ul#ac-nav li');
					  	for (let el of elements) {
						    el.style.color = ''+val+'';
						}
						// weekday titles
						elements=document.querySelectorAll('ul.ac-day-title li');
					  	for (let el of elements) {
						    el.style.color = ''+val+'';
						}
						break;
						
					case '--ac-color2-bg':
						// month titile
						elements=document.querySelectorAll('.ac-month h2');
					  	for (let el of elements) {
						    el.style.background = ''+val+'';
						}
						break;
					
					case '--ac-color2-txt':
						// month titile
						elements=document.querySelectorAll('.ac-month h2');
					  	for (let el of elements) {
						    el.style.color = ''+val+'';
						}
						break;
						
					case '--ac-numbers-bg':
						
						
						//li.booked-pm
						
						// weekday title separation lines
						elements=document.querySelectorAll('ul.ac-day-title');
					  	for (let el of elements) {
						    el.style.background = ''+val+'';
						}
						// days bg
						elements=document.querySelectorAll('ul.ac-days li');
					  	for (let el of elements) {
						    el.style.background = ''+val+'';
						}
						break;
					
					case '--ac-numbers-txt':
						// days txt
						elements=document.querySelectorAll('ul.ac-days li');
					  	for (let el of elements) {
						    el.style.color = ''+val+'';
						}
						break;
					case '--ac-numbers-txt-hover':
						// get numbers text color as defined in form field - use for mouseout
						colorNumTxt=document.getElementById('--ac-numbers-txt').value;
								
						elements = document.querySelectorAll('ul.ac-days li');
						for (let elem of elements) {
							elem.addEventListener('mouseover', function(){
								 elem.style.color = ''+val+'';
							});
							elem.addEventListener('mouseout', function(){
								// put color back to default
								elem.style.color = ''+colorNumTxt+'';
							});
						}
						break;
						
					case '--ac-booked-bg':
						// booked days bg - harder as this has half colors.....
						elements=document.querySelectorAll('ul.ac-days li.booked');
					  	for (let el of elements) {
						    el.style.background = ''+val+'';
						}
						break;
						
					case '--ac-booked-txt':
						// booked days txt
						elements=document.querySelectorAll('ul.ac-days li.booked');
					  	for (let el of elements) {
						    el.style.color = ''+val+'';
						}
						break;
					
					case '--ac-nav-txt':
						
						elements=document.querySelectorAll('ul#ac-nav li');
					  	for (let el of elements) {
						    el.style.color = ''+val+'';
						}
						break;
					
					case '--ac-nav-txt-hover':
						// get nav text color as defined in form field - use for mouseout
						colorNavTxt=document.getElementById('--ac-nav-txt').value;
								
						elements = document.querySelectorAll('ul#ac-nav li');
						for (let elem of elements) {
							elem.addEventListener('mouseover', function(){
								 elem.style.color = ''+val+'';
							});
							elem.addEventListener('mouseout', function(){
								// put color back to default
								elem.style.color = ''+colorNavTxt+'';
							});
						}
						break;
						
						
					case '--ac-border-radius':
						// month box
						elements=document.querySelectorAll('.ac-month');
					  	for (let el of elements) {
						    el.style.borderRadius = ''+val+'px';
						}
					
									}
					
			}
		
		});
		";
		
		/*
			
			date range interactive
			case '--ac-select-range':
						
						// get numbers text color as defined in form field - use for mouseout
						//colorNumTxt=document.getElementById('--ac-numbers-txt').value;
								
						elements = document.querySelectorAll('ul.ac-days li');
						for (let elem of elements) {
							elem.addEventListener('click', function(){
								 if(!dateClick){
									 dateClick=true;
								 }else{
									 dateClick=false;
								 }
								 elem.style.background = ''+val+'';
							});
						}
					case '--ac-select-between':
						
						// get numbers text color as defined in form field - use for mouseout
						//colorNumTxt=document.getElementById('--ac-numbers-txt').value;
								
						elements = document.querySelectorAll('ul.ac-days li');
						for (let elem of elements) {
							elem.addEventListener('mouseover', function(){
								 if(dateClick){
									 elem.style.background = ''+val+'';
								}
							});
						}

			
			
			
		*/

?>