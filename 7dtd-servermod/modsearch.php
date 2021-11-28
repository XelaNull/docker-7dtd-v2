#!/usr/bin/php
<?php



////////////////////////////////////////

$INSTALL_DIR="/data/7DTD";
$MODS_DIR="$INSTALL_DIR/Mods-Available";

// Build array of ModInfo.xml instances installed
$it = new RecursiveDirectoryIterator($MODS_DIR);
foreach(new RecursiveIteratorIterator($it) as $file)
  { if(basename($file)=='ModInfo.xml') $MOD_ARRAY[]=$file; }

echo "Name, Version, DownloadURL, Author, Website, Description\n";

$modcnt=0;
// Loop through all the mods
foreach($MOD_ARRAY as $ModPath)
{
  $modcnt++;
  $FullModPath_ModInfoXML=$ModPath;
  $FullModDir=str_replace('/ModInfo.xml','',$ModPath);
  $modInfo_Array=readModInfo($FullModPath_ModInfoXML);
  $ShortModPath=str_replace($MODS_DIR.'/','',$ModPath); // Strip off the MODS_DIR path prefix

  $Name=$modInfo_Array['Name'];
  $Version=$modInfo_Array['Version'];
  $Author=$modInfo_Array['Author'];
  @$Website=$modInfo_Array['Website'];
  $Description=$modInfo_Array['Description'];

  // Collect the URL that we downloaded this mod from
  $modPath_Pieces=explode('/',$ShortModPath);
  @$DownloadURL=str_replace("\n","",file_get_contents($MODS_DIR.'/'.$modPath_Pieces[0].'/ModURL.txt'));

  echo "\"$Name\", $Version, $DownloadURL, \"$Author\", $Website, \"$Description\"\n";
}

echo "Total Modlets: ".number_format(count($MOD_ARRAY));

?>
