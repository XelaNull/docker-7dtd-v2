<?php
include "vars.inc.php";

// Ensure that the IP accessing the page has permission.
$db = new PDO("sqlite:$INSTALL_DIR/smmControl.sqlite");
$rowcount = $db->query("SELECT * FROM adminips WHERE IPAddress='".$_SERVER['REMOTE_ADDR']."'")->fetchColumn();
if($rowcount=='') { header('HTTP/1.0 403 Forbidden'); die('Forbidden IP: '.$_SERVER['REMOTE_ADDR']); }
$db=null;

$filename = "7dtdreinstall.log";  //about 500MB
$output = shell_exec('exec tail -n22 ' . $filename);  //only print last 50 lines
echo str_replace(PHP_EOL, '<br />', $output);         //add newlines
?>
