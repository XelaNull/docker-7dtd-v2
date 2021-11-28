<?php
include "vars.inc.php";

$filename = "7dtdreinstall.log";  //about 500MB
$output = shell_exec('exec tail -n22 ' . $filename);  //only print last 50 lines
echo str_replace(PHP_EOL, '<br />', $output);         //add newlines
?>
