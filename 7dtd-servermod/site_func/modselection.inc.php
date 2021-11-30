<?php
include("vars.inc.php");
// Ensure that this code is only called from the index.php
if($_SERVER['SCRIPT_NAME']!='/index.php') exit;

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

$screen.="<h3>Mods Available</h3>";

$screen.='
<div class="pager">
        <img src="https://mottie.github.io/tablesorter/addons/pager/icons/first.png" class="first"/>
        <img src="https://mottie.github.io/tablesorter/addons/pager/icons/prev.png" class="prev"/>
        <span class="pagedisplay"></span> <!-- this can be any element, including an input -->
        <img src="https://mottie.github.io/tablesorter/addons/pager/icons/next.png" class="next"/>
        <img src="https://mottie.github.io/tablesorter/addons/pager/icons/last.png" class="last"/>
        <select class="pagesize" title="Select page size">
            <option selected="selected" value="10">10</option>
            <option value="20">20</option>
            <option value="30">30</option>
            <option value="all">All</option>
        </select>
        <select class="gotoPage" title="Select page number"></select>
</div>

<table id="myTable" class=tablesorter border=0 cellpadding=0 cellspacing=1>
<thead>
   <tr>
     <th>&nbsp;</th>
     <th align=left><b>Name</b></th>
     <th align=left width=120><b>DL / Update</b></th>
     <th align=left><b>Description</b></th>
     <th align=left><b>Author</b></th>
   </tr>
 </thead>
 <tfoot>
    <tr>
      <th>&nbsp;</th>
      <th align=left><b>Name</b></th>
      <th align=left width=120><b>DL / Update</b></th>
      <th align=left><b>Description</b></th>
      <th align=left><b>Author</b></th>
    </tr>
  </tfoot>
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
$screen.='</tbody></table>

<div class="pager">
        <img src="https://mottie.github.io/tablesorter/addons/pager/icons/first.png" class="first"/>
        <img src="https://mottie.github.io/tablesorter/addons/pager/icons/prev.png" class="prev"/>
        <span class="pagedisplay"></span> <!-- this can be any element, including an input -->
        <img src="https://mottie.github.io/tablesorter/addons/pager/icons/next.png" class="next"/>
        <img src="https://mottie.github.io/tablesorter/addons/pager/icons/last.png" class="last"/>
        <select class="pagesize" title="Select page size">
            <option selected="selected" value="10">10</option>
            <option value="20">20</option>
            <option value="30">30</option>
            <option value="all">All</option>
        </select>
        <select class="gotoPage" title="Select page number"></select>
</div>
';

?>
