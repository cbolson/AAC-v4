<?php
/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: 	Chris Bolson www.cbolson.com

File		: 	ac-transaltions.inc.php
Date		: 	2021-09-20
Use			: 	define array of translations according to language defined
*/


// create array of local translations
$ac_lang=[];

// get language codes and translations
$sql="
SELECT 
	t.code,
	tt.txt	AS local_txt,
	ttd.txt AS default_txt
FROM 
	".AC_TBL_TEXTS." AS t
	LEFT JOIN ".AC_TBL_TRANSLATIONS." AS tt 	ON tt.id_text=t.id 	AND tt.type='texts' 	AND tt.langcode='".AC_LANG."'
	LEFT JOIN ".AC_TBL_TRANSLATIONS." AS ttd 	ON ttd.id_text=t.id AND ttd.type='texts' 	AND ttd.langcode='en'
WHERE 
	t.state=1
ORDER BY ttd.txt ASC
";

$res=mysqli_query($db_cal,$sql) or die("Error - text translations");
if(mysqli_num_rows($res)==0){
	$txt='_text_not_found_';
}else{
	while($row=mysqli_fetch_assoc($res)){
		if(!empty($row["local_txt"]))			$txt = $row["local_txt"];		# show local lang
		else if(!empty($row["default_txt"]))	$txt = '_'.$row["default_txt"]; # show english
		else 									$txt = '['.$row["code"].']'; 	# show code
		$ac_lang[$row["code"]]=nl2br($txt);
	}
}
?>