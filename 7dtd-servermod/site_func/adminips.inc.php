<?php
include("vars.inc.php");
// Ensure that this code is only called from the index.php
if($_SERVER['SCRIPT_NAME']!='/index.php') exit;

$db = new PDO("sqlite:$INSTALL_DIR/smmControl.sqlite");

// If the Add IP Address Form was Submitted then we should add the new entry to the database.
if($_POST['Submit'])
{
  //echo "Submitting IP Address";
  if(filter_var($_POST['IPAddress'], FILTER_VALIDATE_IP))
    {
      //echo "Posted value validated as an IP Address..";
      $Timestamp=date("Y/m/d")." ".date("H:i:s");
      //$query = "CREATE TABLE IF NOT EXISTS adminips (IPAddress TEXT, Comment TEXT, TimestampAdded TEXT, LastSeen TEXT)";
      $query="INSERT INTO adminips (IPAddress, Comment, TimestampAdded, LastSeen) VALUES (\"".$_POST['IPAddress']."\", \"".$_POST['Comment']."\", \"".$Timestamp."\", \"\")";
      $results=$db->query($query);
      if($results==FALSE) { echo "$query<br>Error in POST: "; print_r($db->errorInfo()); }
    }
}

// If the removeip GET variable is detected, we should remove that entry from the database
if($_GET['removeip'] && filter_var($_GET['removeip'], FILTER_VALIDATE_IP))
  $db->query("DELETE FROM adminips WHERE IPAddress='".$_GET['removeip']."'");

$screen.='
<form method=post action=?DO=adminips>
<b>Add IP:</b> <input type=text name=IPAddress> <b>Comment:</b> <input type=text name=Comment> <input type=submit name=Submit value=Submit>
</form>

<table id="table" class=tablesorter border=0 cellpadding=0 cellspacing=1>
<thead>
   <tr>
     <th align=left><b>IP Address</b></th>
     <th align=left><b>Comment</b></th>
     <th align=left><b>Timestamp Added</b></th>
     <th align=left><b>Last Seen</b></th>
   </tr>
 </thead>
 <tfoot>
    <tr>
    <th align=left><b>IP Address</b></th>
    <th align=left><b>Comment</b></th>
    <th align=left><b>Timestamp Added</b></th>
    <th align=left><b>Last Seen</b></th>
    </tr>
  </tfoot>
 <tbody>
';

/*
adminips:

IPAddress
Comment
TimestampAdded
LastSeen
*/
$results=$db->query('SELECT * FROM adminips');
while($row = $results->fetch())
{
  $screen.="<tr>
  <td>".$row['IPAddress']." (<A href=?DO=adminips&removeip=".$row['IPAddress'].">remove</a>)</td>
  <td>".$row['Comment']."</td>
  <td>".$row['TimestampAdded']."</td>
  <td>".$row['LastSeen']."</td>
  </tr>";
}


$screen.='</tbody></table>';

?>
