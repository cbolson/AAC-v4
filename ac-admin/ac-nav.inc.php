<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-nav.inc.php
Use			: 	define admin nav items according to user level
				define current page variables
*/	


// define admin levels here as we need the translations
$admin_levels=array();
$admin_levels[1]= $ac_lang["admin_level_administrator"];
$admin_levels[2]= $ac_lang["admin_level_user"];


// define array of nav items - the value must coincide with the file name
$nav_items=[];
// common nav items
$nav_items["items"] 		= array("buttons"=>true);
$nav_items["availability"] 	= array("buttons"=>false);



if($_SESSION["admin"]["level"]==1){
	// main admin - show config and user admin
	$nav_items["settings"]	= array("buttons"=>false);
	$nav_items["users"] 	= array("buttons"=>true);
	$nav_items["languages"]	= array("buttons"=>true);
	$nav_items["texts"] 	= array("buttons"=>true);
}
	
		
$nav_items["profile"] 		= array("buttons"=>false);
$nav_items["logout"] 		= array("buttons"=>true);
//print_arr($nav_items);
if(!array_key_exists(AC_PAGE, $nav_items)){
	// ensure that user is only accessing pages according to their admin level
	//die("this page does not exist");
	$msg				= $ac_lang["msg_no_permited"];
	$msg_type			= "alert";
	$admin_page_permit	= false;
}


$list_nav_items='';
foreach($nav_items AS $nav_type=>$nav_data){
	$item_class	='';
	if($nav_type=="logout")		$item_link='ac-logout.php';
	else 						$item_link='?p='.$nav_type;
	
	$item_anchor 	= $ac_lang["admin_nav_".$nav_type.""];
	
	if(AC_PAGE=="".$nav_type.""){
		// current page
		
		// mark nav item as active
		$item_class=' class="active"';
		
		// define page title ("admin_$nav_type)
		$page_title=$item_anchor;
		
		// define top buttons
		if($nav_data["buttons"]){
			if(isset($_REQUEST["action"])){
				// back to list
				$page_buttons	= '<a href="?p='.AC_PAGE.'" class="icons" title="'.$ac_lang["back"].'">'.icon("arrow-left2").'</a>';
				// add text to page title
				switch($_REQUEST["action"]){
					case "new"		:	$page_title	.= ' : '.$ac_lang["add"]; break;
					case "edit"		:	$page_title	.= ' : '.$ac_lang["edit"]; break;
					case "delete"	:	$page_title	.= ' : '.$ac_lang["delete"]; break;
				}
			}else{
				// add new item
				$page_buttons	= '<a href="?p='.AC_PAGE.'&action=new" class="icons" title="'.$ac_lang["bt_add"].'">'.icon("plus").'</a>';
			}
		}
	}
	
	// create nav list item
	$list_nav_items.='
	<li'.$item_class.'>
		<a href="'.$item_link.'">'.$item_anchor.'</a>
	</li>
	';
}


//	create menu
if(!empty($list_nav_items)){
	$block_nav='
	
	<nav>
						<button id="hamburger" class="hamburger" aria-controls="primary-navigation" aria-expanded="false" aria-label="Menu">
							<svg class="hamburger" viewBox="0 0 100 100" width="30">
								<rect class="line line__top" width="80" height="10" x="10" y="25" rx="5"></rect>
								<rect class="line line__middle" width="80" height="10" x="10" y="45" rx="5"></rect>
								<rect class="line line__bottom" width="80" height="10" x="10" y="65" rx="5"></rect>
							</svg>
						</button>
						<ul id="primary-navigation" class="menu">
							'.$list_nav_items.'
						</ul>
					</nav>

	';
}
/*
<input type="checkbox" id="nav-check">
	<label for="nav-check">
		<div id="nav-hamburger">
			<span class="bar bar1"></span>
			<span class="bar bar2"></span>
			<span class="bar bar3"></span>
			<span class="bar bar4"></span>
		</div>
	</label>
	<nav>
		<ul id="nav-menu" class="list-unstyled align-items-center d-md-flex m-0 p-0">
			'.$list_nav_items.'
		</ul>
	</nav>
*/
?>