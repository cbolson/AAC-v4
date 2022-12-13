<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1); 
$the_file="../ac-config.inc.php";
if(!file_exists($the_file)) die("<b>".$the_file."</b> not found");
else require_once($the_file);
//header("Location:index.php");
echo "ehre";
?>