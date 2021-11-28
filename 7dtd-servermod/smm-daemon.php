#!/usr/bin/php
<?php
include("vars.inc.php");

$db = new PDO('sqlite:smmControl.sqlite');
chown('smmControl.sqlite', 'nobody'); // Ensure that the website can also read/write to this file

$query = "CREATE TABLE IF NOT EXISTS smmcontrol (command TEXT, payload TEXT, executed INTEGER)";
$db->query($query);

// ModName,ModPath,Activated,OriginationPath
$query = "CREATE TABLE IF NOT EXISTS installedmods (ModName TEXT, ModPath TEXT, Activated INTEGER, OriginationPath TEXT)";
$db->query($query);

// Create default file is there is not web page just yet
if(!file_exists("$INSTALL_DIR/index.php"))
    file_put_contents("$INSTALL_DIR/index.php", "<html><head> <meta http-equiv=\"refresh\" content=\"30\" /></head><body><center>7DaysToDie is currently installing in the background.<br><br>This page will automatically refresh every 30 seconds until the server is installed.<center></body></html>");

// Ensure the Steam client is up-to-date, provided that it is not already running
exec("ps awwux | grep steamcmd | grep -v grep && steamcmd +quit");

// Install 7DTD if it isn't initialized
if(!file_exists("/7dtd.initialized"))
  {
    # Set up the installation directory
    if(!is_dir("$INSTALL_DIR/.local")) exec("mkdir -p $INSTALL_DIR/.local");
    if(!is_dir("$INSTALL_DIR/Mods")) exec("mkdir -p $INSTALL_DIR/Mods");
    // Set up Steam .local directory
    exec("rm -rf /root/.steam/.local && ln -s $INSTALL_DIR/.local /root/.steam/.local");

    // Install 7DTD
    install7dtd();

    // Install SMM
    reinstallsmm();

    touch("/7dtd.initialized");
  }

// Before beginning infinite loop, let's search for installed modlets
searchForInstalledMods();

while(true)
{
  $query = "SELECT rowid,command,payload FROM smmcontrol WHERE executed = 0 ORDER by rowid asc LIMIT 1";
  $results = $db->query($query);
  while($result = $results->fetchArray())
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

sleep(1);
}

function startserver()
{
  global $INSTALL_DIR;
  exec("cd $INSTALL_DIR; ./7DaysToDieServer.x86_64 -configfile=$INSTALL_DIR/serverconfig.xml -logfile $INSTALL_DIR/7dtd.log -quit -batchmode -nographics -dedicated;");

}

function reinstallsmm()
{
  global $INSTALL_DIR;
  $SMM_REINSTALL_LOG="/smmreinstall.log";
  exec("rm -rf $SMM_REINSTALL_LOG");

  // Clone the docker-7dtd repo
  $cmd="rm -rf /docker-7dtd";
  writeEntryToLog("Deleting the existing/local GIT repo.\n> $cmd", $SMM_REINSTALL_LOG); exec($cmd);

  $cmd="cd /; git clone https://github.com/XelaNull/docker-7dtd.git";
  writeEntryToLog("Re-Cloning the docker-7dtd GIT repo.\n> $cmd", $SMM_REINSTALL_LOG); exec($cmd);

  // Link all the files from the GIT repo to the HTML directory
  if(!is_dir("$INSTALL_DIR/html"))
    {
      $cmd="mkdir -p $INSTALL_DIR/html";
      writeEntryToLog("Create the base html directory.\n> $cmd", $SMM_REINSTALL_LOG); exec($cmd);
    }
  writeEntryToLog("SymLink the images directory.\n> ln -s /docker-7dtd/7dtd-servermod/images $INSTALL_DIR/html/images", $SMM_REINSTALL_LOG);
  @link("/docker-7dtd/7dtd-servermod/images", "$INSTALL_DIR/html/images");
  writeEntryToLog("SymLink the site_func directory.\n> ln -s /docker-7dtd/7dtd-servermod/site_func $INSTALL_DIR/html/site_func", $SMM_REINSTALL_LOG);
  @link("/docker-7dtd/7dtd-servermod/site_func", "$INSTALL_DIR/html/site_func");

  writeEntryToLog("SymLink the index.php file.\n> ln -s /docker-7dtd/7dtd-servermod/index.php $INSTALL_DIR/html/index.php", $SMM_REINSTALL_LOG);
  @link("/docker-7dtd/7dtd-servermod/index.php", "$INSTALL_DIR/html/index.php");
  writeEntryToLog("SymLink the vars.inc.php file.\n> ln -s /docker-7dtd/7dtd-servermod/vars.inc.php $INSTALL_DIR/html/vars.inc.php", $SMM_REINSTALL_LOG);
  @link("/docker-7dtd/7dtd-servermod/vars.inc.php", "$INSTALL_DIR/html/vars.inc.php");
  writeEntryToLog("SymLink the main.css.\n> ln -s /docker-7dtd/7dtd-servermod/main.css $INSTALL_DIR/html/main.css", $SMM_REINSTALL_LOG);
  @link("/docker-7dtd/7dtd-servermod/main.css", "$INSTALL_DIR/html/main.css");

  // Set file ownership to allow website to write to key files
  writeEntryToLog("Set the ownership on the serverconfig.xml to allow the Nginx webserver to modify it.\n> chown nobody $INSTALL_DIR/serverconfig.xml", $SMM_REINSTALL_LOG);
  chown("$INSTALL_DIR/serverconfig.xml", "nobody");
  writeEntryToLog("Set the ownership on the Mods directories to allow the Nginx website to manage them.\n> chown nobody $INSTALL_DIR/Mods* -R", $SMM_REINSTALL_LOG);
  exec("chown nobody $INSTALL_DIR/Mods* -R");
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
  exec("steamcmd +login anonymous +force_install_dir $INSTALL_DIR +app_update 294420 $beta $betapassword $validate +quit > $REINSTALL_LOG");
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

    writeEntryToLog("\"$Name\", $Version, $DownloadURL, \"$Author\", $Website, \"$Description\"");

    $query = "INSERT INTO installedmods (ModName, ModVersion, ModDownloadURL, ModAuthor, ModWebsite, ModDescription, Activated, ModPath) VALUES
    (\"$Name\", $Version, $DownloadURL, \"$Author\", $Website, \"$Description\", 0, $ShortModPath)";
    $db->query($query);
  }

  writeEntryToLog("Total Modlets: ".number_format(count($MOD_ARRAY)));
}
?>
