<?php
include("vars.inc.php");

$db = new PDO("sqlite:$INSTALL_DIR/smmControl.sqlite");
/*
installedmods:

These fields are extracted from the ModInfo.xml file provided by the Mod Author:
ModName (TEXT)
ModVersion (TEXT)
ModAuthor (TEXT)
ModWebsite (TEXT)
ModDescription (TEXT)

These fields are provided by the vars.inc.php when the Mod is downloaded:
DownloadURL (TEXT)
Method (TEXT)
DownloadFile (TEXT)
Extract (TEXT)
CMD (TEXT)

These fields are extrapolated based on our local install of the mod:
Activated (INTEGER)
ModPath (TEXT)
*/

$screen.="Mods Available:";

$screen.='<table id="myTable" class=tablesorter border=0 cellpadding=0 cellspacing=1>
<thead>
   <tr>
     <th>&nbsp;</th>
     <th align=left><b>Name</b></th>
     <th align=left width=120><b>DL / Update</b></th>
     <th align=left><b>Description</b></th>
     <th align=left><b>Author</b></th>
   </tr>
 </thead>
 <tbody>
';
$results=$db->query('SELECT * FROM installedmods'); $modcnt=0;
while($row = $results->fetch() )
{
  $screen.="<tr>";
  $screen.="<td><input $checkTXT name=modID$modcnt type=checkbox onChange=\"this.form.submit();\"></td>";

  $screen.="<td width=350><b>".$row['ModName']."</b><br>Version: ".$row['ModVersion']."</td>";

  $screen.="<td><a href=".$row['DownloadURL']."><img src=images/direct-download.png border=0 width=20></a></td>";
  $screen.="<td width=auto><font size=2>".$row['ModDescription']."</font></td>";
  $screen.="<td><font size=2>".$row['ModAuthor']."</td>";

  $screen.="</tr>\n";
  $modcnt++;
}
$screen.="</tbody></table>\n";

?>
