<?php
include("vars.inc.php");

$db = new PDO("sqlite:$INSTALL_DIR/smmControl.sqlite");

if($argv[1]!='')
  { echo "This script expects you to pass an IP address as a variable.\n\nSyntax: addadminip.php <IP>"; exit; }

if(!filter_var($argv[1], FILTER_VALIDATE_IP))
  { echo "The IP address you provided does not appear to be a valid IP address.\n\nSyntax: addadminip.php <IP>"; exit; }

$Timestamp=date("Y/m/d")." ".date("H:i:s");
//$query = "CREATE TABLE IF NOT EXISTS adminips (IPAddress TEXT, Comment TEXT, TimestampAdded TEXT, LastSeen TEXT)";
$query="INSERT INTO adminips (IPAddress, Comment, TimestampAdded, LastSeen) VALUES (\"".$_argv[1]."\", \"\", \"".$Timestamp."\", \"\")";
$results=$db->query($query);
if($results==FALSE) { echo "$query<br>Error in POST: "; print_r($db->errorInfo()); }
?>
