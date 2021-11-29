#!/usr/bin/php
<?php
include("vars.inc.php");

$db = new PDO("sqlite:$INSTALL_DIR/smmControl.sqlite");
chown("$INSTALL_DIR/smmControl.sqlite", 'nobody'); // Ensure that the website can also read/write to this file

$query = "CREATE TABLE IF NOT EXISTS smmcontrol (command TEXT, payload TEXT, executed INTEGER)";
$db->query($query);

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
$query = "CREATE TABLE IF NOT EXISTS installedmods (
  ModName TEXT, ModVersion TEXT, ModAuthor TEXT, ModWebsite TEXT, ModDescription TEXT,
  DownloadURL TEXT, Method TEXT, DownloadFile TEXT, Extract TEXT, CMD TEXT,
  Activated INTEGER, ModPath TEXT)";
$db->query($query);


/*
availablemods:
These fields are provided by the vars.inc.php when the Mod is downloaded:
DownloadURL (TEXT)
Method (TEXT)
DownloadFile (TEXT)
Extract (TEXT)
CMD (TEXT)
*/
$query = "CREATE TABLE IF NOT EXISTS availablemods (DownloadURL TEXT, Method TEXT, DownloadFile TEXT, Extract TEXT, CMD TEXT)";
$db->query($query);
$db->query("CREATE unique index modURL on availablemods (DownloadURL)");
// Populate the availablemods
foreach($MODS_TO_INSTALL as $MOD_ARRAY)
  {
    $DownloadURL=$MOD_ARRAY['URL'];
    $Method=$MOD_ARRAY['Method'];
    $DownloadFile=$MOD_ARRAY['File'];
    $Extract=$MOD_ARRAY['Extract'];
    $CMD=$MOD_ARRAY['CMD'];

    $query = "INSERT or IGNORE INTO availablemods (DownloadURL, Method, DownloadFile, Extract, CMD) values
    (\"$DownloadURL\", \"$Method\", \"$DownloadFile\", \"$Extract\", \"$CMD\")";
    $db->query($query);
  }

echo "Check default directories\n";
// Create default game directory if it does not exist
if(!is_dir("$INSTALL_DIR")) { echo "Directory does not exits. Creating $INSTALL_DIR"; mkdir($INSTALL_DIR); }
if(!is_dir("$INSTALL_DIR/html")) { echo "Directory does not exist. Creating $INSTALL_DIR/html"; mkdir("$INSTALL_DIR/html"); }
// Create default file is there is not web page just yet
if(!file_exists("$INSTALL_DIR/html/index.php"))
    {
    echo "PHP detected file does not exist: $INSTALL_DIR/html/index.php";
    file_put_contents("$INSTALL_DIR/html/index.php", "<html><head> <meta http-equiv=\"refresh\" content=\"30\" /></head><body><center>7DaysToDie is currently installing in the background.<br><br>This page will automatically refresh every 30 seconds until the server is installed.<center></body></html>");
    }

chdir("$INSTALL_DIR");
echo "Updating Steam\n";
// Ensure the Steam client is up-to-date, provided that it is not already running
exec("ps awwux | grep steamcmd | grep -v grep && steamcmd +quit");

// Install 7DTD if it isn't initialized
if(!file_exists("/7dtd.initialized"))
  {
    echo "Installing 7DTD\n";
    # Set up the installation directory
    if(!is_dir("$INSTALL_DIR/.local")) exec("mkdir -p $INSTALL_DIR/.local");
    if(!is_dir("$INSTALL_DIR/Mods")) exec("mkdir -p $INSTALL_DIR/Mods");
    if(!is_dir("$INSTALL_DIR/Mods-Available")) exec("mkdir -p $INSTALL_DIR/Mods-Available");
    // Set up Steam .local directory
    exec("rm -rf /root/.steam/.local && ln -s $INSTALL_DIR/.local /root/.steam/.local");

    // Install 7DTD
    install7dtd();

    echo "Reinstalling SMM\n";
    // Install SMM
    reinstallsmm();

    touch("/7dtd.initialized");
  }

echo "searching for installed mods\n";
// Before beginning infinite loop, let's search for installed modlets
searchForInstalledMods();

while(true)
{
  $query = "SELECT rowid,command,payload FROM smmcontrol WHERE executed = 0 ORDER by rowid asc LIMIT 1";
  $results = $db->query($query);
  while($result = $results->fetch())
    {
      switch($result['command'])
        {
          case "startserver":
            startserver();
          break;

          case "restartserver":
          case "stopserver":
            // Make sure that the 7DTD server is currently started
            $SERVER_RUNNING_CHECK=exec('ps awwux | grep -v grep | grep 7DaysToDieServer.x86_64');
            // Break out if 7DTD server is already stopped
            if($SERVER_RUNNING_CHECK=='') break;

            // Make sure that telnet port is up and listening
            $TELNETPORT=exec("grep 'name=\"TelnetPort\"' $INSTALL_DIR/serverconfig.xml | awk '{print $3} | cut -d'\"' -f2");
            $TELNET_CHECK=exec("netstat -anptu | grep LISTEN | grep $TELNETPORT");

            // send the two commands needed to save the world and shutdown the server
            exec("$INSTALL_DIR/7dtd-sendcmd.sh \"saveworld\"");
            sleep(5);
            exec("$INSTALL_DIR/7dtd-sendcmd.sh \"shutdown\"");
            // Delete any core files that may have been created from gameserver crashes
            exec("rm -rf $INSTALL_DIR/core.*");

            // If we are restarting, we should set the touch file to start on next iteration, then sleep to give server a chance to shutdown
            if($result['command']=='restart')
              {
                sleep(15); // Give the server a chance to stop, before continuing to next iteration starting it back up
                startserver();
              }
          break;

          case "forcestopserver":
            // Make sure that the 7DTD server is currently started
            $SERVER_RUNNING_CHECK=exec("ps awwux | grep -v grep | grep 7DaysToDieServer.x86_64 | awk '{print \$1}'");
            // Break out if 7DTD server is already stopped
            if($SERVER_RUNNING_CHECK=='') break;

            exec("kill -9 $SERVER_RUNNING_CHECK");
          break;

          case "reinstallsmm":
          reinstallsmm(); exit;
          break;

          case "disablemod":
          break;

          case "disableallmods":
          break;

          case "enablemod":
          break;

          case "enableallmods":
          break;

          case "updatemod":
          break;

          case "removemod":
          break;

          case "addmod":
          break;

          case "update7dtd":
          install7dtd();
          break;

          case "backupmodpack":
          break;

          case "restoremodpack":
          break;

          case "backupserver":
          break;

          case "restoreserver":
          break;
        }
    }
echo "iterating infinite loop";
sleep(1);
}

function startserver()
{
  global $INSTALL_DIR;
  exec("cd $INSTALL_DIR; ./7DaysToDieServer.x86_64 -configfile=$INSTALL_DIR/serverconfig.xml -logfile $INSTALL_DIR/7dtd.log -quit -batchmode -nographics -dedicated;");

}

function reinstallsmm()
{
  global $INSTALL_DIR, $MODS_TO_INSTALL, $db;
  $SMM_REINSTALL_LOG="/smmreinstall.log";
  exec("rm -rf $SMM_REINSTALL_LOG");

  // Clone the docker-7dtd repo
  //$cmd="rm -rf /docker-7dtd";
  //writeEntryToLog("Deleting the existing/local GIT repo.\n> $cmd", $SMM_REINSTALL_LOG); exec($cmd);

  $cmd="cd /docker-7dtd-v2; git pull origin master;";
  writeEntryToLog("Re-Cloning the docker-7dtd GIT repo.\n> $cmd", $SMM_REINSTALL_LOG); exec($cmd);

  // Link all the files from the GIT repo to the HTML directory
  if(!is_dir("$INSTALL_DIR/html"))
    {
      $cmd="mkdir -p $INSTALL_DIR/html";
      writeEntryToLog("Create the base html directory.\n> $cmd", $SMM_REINSTALL_LOG); exec($cmd);
    }
  writeEntryToLog("SymLink the images directory.\n> ln -s /docker-7dtd-v2/7dtd-servermod/images $INSTALL_DIR/html/images", $SMM_REINSTALL_LOG);
  exec("rm -rf $INSTALL_DIR/html/images; ln -s /docker-7dtd-v2/7dtd-servermod/images $INSTALL_DIR/html/images");
  //@link("/docker-7dtd-v2/7dtd-servermod/images", "$INSTALL_DIR/html/images");
  writeEntryToLog("SymLink the site_func directory.\n> ln -s /docker-7dtd-v2/7dtd-servermod/site_func $INSTALL_DIR/html/site_func", $SMM_REINSTALL_LOG);
  exec("rm -rf $INSTALL_DIR/html/site_func; ln -s /docker-7dtd-v2/7dtd-servermod/site_func $INSTALL_DIR/html/site_func");
  //@link("/docker-7dtd-v2/7dtd-servermod/site_func", "$INSTALL_DIR/html/site_func");

  writeEntryToLog("SymLink the index.php file.\n> ln -s /docker-7dtd-v2/7dtd-servermod/index.php $INSTALL_DIR/html/index.php", $SMM_REINSTALL_LOG);
  //@link("/docker-7dtd-v2/7dtd-servermod/index.php", "$INSTALL_DIR/html/index.php");
  exec("rm -rf $INSTALL_DIR/html/index.php; ln -s /docker-7dtd-v2/7dtd-servermod/index.php $INSTALL_DIR/html/index.php");
  writeEntryToLog("SymLink the vars.inc.php file.\n> ln -s /docker-7dtd-v2/7dtd-servermod/vars.inc.php $INSTALL_DIR/html/vars.inc.php", $SMM_REINSTALL_LOG);
  //@link("/docker-7dtd-v2/7dtd-servermod/vars.inc.php", "$INSTALL_DIR/html/vars.inc.php");
  exec("rm -rf $INSTALL_DIR/html/vars.inc.php; ln -s /docker-7dtd-v2/7dtd-servermod/vars.inc.php $INSTALL_DIR/html/vars.inc.php");
  writeEntryToLog("SymLink the main.css.\n> ln -s /docker-7dtd-v2/7dtd-servermod/main.css $INSTALL_DIR/html/main.css", $SMM_REINSTALL_LOG);
  //@link("/docker-7dtd-v2/7dtd-servermod/main.css", "$INSTALL_DIR/html/main.css");
  exec("rm -rf $INSTALL_DIR/html/main.css; ln -s /docker-7dtd-v2/7dtd-servermod/main.css $INSTALL_DIR/html/main.css");

  // Set file ownership to allow website to write to key files
  writeEntryToLog("Set the ownership on the serverconfig.xml to allow the Nginx webserver to modify it.\n> chown nobody $INSTALL_DIR/serverconfig.xml", $SMM_REINSTALL_LOG);
  chown("$INSTALL_DIR/serverconfig.xml", "nobody");
  writeEntryToLog("Set the ownership on the Mods directories to allow the Nginx website to manage them.\n> chown nobody $INSTALL_DIR/Mods* -R", $SMM_REINSTALL_LOG);
  exec("chown nobody $INSTALL_DIR/Mods* -R");

  $MODS_DIR="$INSTALL_DIR/Mods-Available";
  echo "Deleting $MODS_DIR & Recreating $MODS_DIR\n";
  exec("rm -rf $MODS_DIR; mkdir $MODS_DIR");

  //$query = "CREATE TABLE IF NOT EXISTS availablemods (DownloadURL TEXT, Method TEXT, DownloadFile TEXT, Extract TEXT, CMD TEXT)";

  echo "Iterate of the mods to download\n"; $MODCOUNT=0;
  $results=$db->query('SELECT * FROM availablemods');
  while($row = $results->fetch() )
  {
    echo $row['DownloadURL'].", ".$row['Method'].", ".$row['DownloadFile'].", ".$row['Extract'].", ".$row['CMD']."\n";
    $MOD_ARRAY['DownloadURL']=$row['DownloadURL'];
    $MOD_ARRAY['Method']=$row['Method'];
    $MOD_ARRAY['DownloadFile']=$row['DownloadFile'];
    $MOD_ARRAY['Extract']=$row['Extract'];
    $MOD_ARRAY['CMD']=$row['CMD'];
    download_mod($MODCOUNT, $MOD_ARRAY); $MODCOUNT++;
  }

}

// Download a mod
function download_mod($MODCOUNT, $MOD_ARRAY)
{
  global $INSTALL_DIR;
  $MODS_DIR="$INSTALL_DIR/Mods-Available";

  $URL=$MOD_ARRAY['DownloadURL'];
  $Method=$MOD_ARRAY['Method'];
  $File=$MOD_ARRAY['DownloadFile'];
  $Extract=$MOD_ARRAY['Extract'];
  $CMD=$MOD_ARRAY['CMD'];

  echo "Creating $MODS_DIR/$MODCOUNT\n";
  exec("mkdir $MODS_DIR/$MODCOUNT");
  switch($Method)
  {
    case "wget":
      echo "Using WGET to download $URL and save as $MODS_DIR/$MODCOUNT/$File\n";
      exec("cd $MODS_DIR/$MODCOUNT; wget -O $File \"$URL\" > /dev/null 2>&1");
    break;
  }

  if($Extract=='true')
  {
    // Determine extension of $File
    $EXTENSION = pathinfo("$MODS_DIR/$MODCOUNT/$File", PATHINFO_EXTENSION);
    switch($EXTENSION)
    {
      case "zip": exec("cd $MODS_DIR/$MODCOUNT; unzip -o $File"); break;
    }
  }

  $MOD_INFO="$URL\n$Method\n$File\n$Extract\n$CMD";
  file_put_contents("$MODS_DIR/$MODCOUNT/ModInfo.txt", $MOD_INFO);

  if($CMD!="") { chdir("$MODS_DIR/$MODCOUNT"); exec($CMD); chdir("$INSTALL_DIR"); }
}

// Install 7DTD
function install7dtd()
{
  global $STEAMCMD_NO_VALIDATE, $STEAMCMD_BETA, $STEAMCMD_BETA_PASSWORD;
  global $INSTALL_DIR;

  $REINSTALL_LOG="/7dtdreinstall.log";
  exec("rm -rf $REINSTALL_LOG");

  # Set up extra variables we will use, if they are present
  if($STEAMCMD_NO_VALIDATE!="") $validate="validate";
  if($STEAMCMD_BETA!="") $beta="-beta $STEAMCMD_BETA";
  if($STEAMCMD_BETA_PASSWORD!="") $betapassword="-betapassword $STEAMCMD_BETA_PASSWORD";

  writeEntryToLog("Starting Steam to perform 7DTD game server install into $INSTALL_DIR", $REINSTALL_LOG);
  exec("steamcmd +force_install_dir $INSTALL_DIR +login anonymous +app_update 294420 $beta $betapassword $validate +quit > $REINSTALL_LOG");
}

// Write out message to log
function writeEntryToLog($TEXT, $LOGFILE="/smm.log")
{
  file_put_contents($LOGFILE, $TEXT."\n", FILE_APPEND | LOCK_EX);
}

// Read in the information from the ModInfo.xml file
function readModInfo($fullPathToModInfoXML)
{
$fileArray=file($fullPathToModInfoXML);
foreach($fileArray as $line)
  {
    if(strpos($line,'Name value')!==FALSE) $rtn['Name']=extractValue($line);
    if(strpos($line,'Description value')!==FALSE) $rtn['Description']=extractValue($line);
    if(strpos($line,'Author value')!==FALSE) $rtn['Author']=extractValue($line);
    if(strpos($line,'Version value')!==FALSE) $rtn['Version']=extractValue($line);
    if(strpos($line,'Website value')!==FALSE) $rtn['Website']=extractValue($line);
  }
  return($rtn);
}
// Return only what is after the double-quote
// This is used in the readModInfo function
function extractValue($line)
{ $pieces=explode('"',$line); return($pieces[1]); }

function searchForInstalledMods()
{
  global $db, $INSTALL_DIR;

  $MODS_DIR="$INSTALL_DIR/Mods-Available";

  $db->query("DELETE FROM installedmods");

  // Build array of ModInfo.xml instances installed
  $it = new RecursiveDirectoryIterator($MODS_DIR);
  foreach(new RecursiveIteratorIterator($it) as $file)
    { if(basename($file)=='ModInfo.xml') $MOD_ARRAY[]=$file; }

  writeEntryToLog("Searching for installed mods.");
  writeEntryToLog("Name, Version, DownloadURL, Author, Website, Description\n");

  $modcnt=0;
  // Loop through all the mods
  if(@count($MOD_ARRAY)>0) foreach($MOD_ARRAY as $ModPath)
  {
    $modcnt++;
    $FullModPath_ModInfoXML=$ModPath;
    $FullModDir=str_replace('/ModInfo.xml','',$ModPath);
    $modInfo_Array=readModInfo($FullModPath_ModInfoXML);

    $ModName=$modInfo_Array['Name'];
    $ModVersion=$modInfo_Array['Version'];
    $ModAuthor=$modInfo_Array['Author'];
    @$ModWebsite=$modInfo_Array['Website'];
    $ModDescription=$modInfo_Array['Description'];

    // Collect the URL that we downloaded this mod from
    $modPath_Pieces=explode('/',str_replace($MODS_DIR.'/','',$ModPath));

    // Collect information previously stored into ModInfo.txt
    $ModInfo=file("$MODS_DIR/".$modPath_Pieces[0]."/ModInfo.txt");
    $DownloadURL=$ModInfo[0];
    $Method=$ModInfo[1];
    $DownloadFile=$ModInfo[2];
    $Extract=$ModInfo[3];
    $CMD=$ModInfo[4];

    writeEntryToLog("\"$Name\", $Version, $DownloadURL, \"$Author\", $Website, \"$Description\"");

    /* installedmods:
        These fields are extracted from the ModInfo.xml file provided by the Mod Author:
        ModName, ModVersion, ModAuthor, ModWebsite, ModDescription

        These fields are provided by the vars.inc.php when the Mod is downloaded:
        DownloadURL, Method, DownloadFile, Extract, CMD

        These fields are extrapolated based on our local install of the mod:
        Activated, ModPath */

    $query = "INSERT INTO installedmods (ModName, ModVersion, ModAuthor, ModWebsite, ModDescription,
      DownloadURL, Method, DownloadFile, Extract, CMD,
      Activated, ModPath) VALUES
    (\"$ModName\", \"$ModVersion\", \"$ModAuthor\", \"$ModWebsite\", \"$ModDescription\",
      \"$DownloadURL\", \"$Method\", \"$DownloadFile\", \"$Extract\", \"$CMD\",
      0, $ShortModPath)";
    $db->query($query);
  }

  writeEntryToLog("Total Modlets: ".number_format(@count($MOD_ARRAY)));
}
?>
