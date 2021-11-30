<?php
include "vars.inc.php";
// Ensure that this code is only called from the index.php
if($_SERVER['SCRIPT_NAME']!='/index.php') exit;

$screen="<h3>Status</h3>
<div style=\"color: #c7c9c8;\">Server Online Status</div>

<label class=\"switch\">
  <input type=\"checkbox\">
  <span class=\"slider\"></span>
</label>
<br><br>
<div style=\"color: #c7c9c8;\">Showing Server Statistics</div>
";

$GameserverVersion=exec("grep 'INF Version' $INSTALL_DIR/7dtd.log | awk '{print $5\" \"$6\" \"$7}'");
$RAMUsage=round(get_server_memory_usage(),2);


$ds = disk_total_space("/data");
$df = disk_free_space("/data");
$DiskUsage=exec("df -h /data | tail -1 | awk '{print \$5}' | cut -d% -f1");
$DiskTotal=exec("df -h / | awk '{print \$2}' | tail -1");
$CPUUsage=exec('echo "`LC_ALL=C top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk \'{print 100 - $1}\'`"');

$screen.="
<b>Gameserver Version:</b> $GameserverVersion<br>
<b>SMM Version:</b> $SMM_VERSION<br>
<br>
<div>

  <span style=\"background-color: #1f1f1f; color: #c7c9c8; width: 45%; padding: 30px; position: relative; left: 0px; float: left; top: 0px;\">
  <span style=\"color: #c7c9c8;\"><b>CPU Usage</b></span><br>
  <font size=6>$CPUUsage%</font><br>
  <center>
  <div id=cpubar style=\"width: 90%; height: 60px;\"></div>
  </center>
  <script>
  $( function() {
    $( \"#cpubar\" ).progressbar({  value: ".$CPUUsage." });
    progressbar = $( \"#cpubar\" );
    progressbarValue = progressbar.find( \".ui-progressbar-value\" );
    progressbarValue.css({ \"background\": '#3443eb' });
  } );
  </script>
  </span>

  <span style=\"background-color: #1f1f1f; color: #c7c9c8; width: 45%; padding: 30px; position: relative; float: right; top: 0px; right: 0px;\">
  <span style=\"color: #c7c9c8;\"><b>RAM Usage</b></span><br>
  <font size=6>$RAMUsage%</font><br>
  <center>
  <div id=rambar style=\"width: 90%; height: 60px;\"></div>
  </center>
  <script>
  $( function() {
    $( \"#rambar\" ).progressbar({  value: ".$RAMUsage." });
    progressbar = $( \"#rambar\" );
    progressbarValue = progressbar.find( \".ui-progressbar-value\" );
    progressbarValue.css({ \"background\": '#3443eb' });
  } );
  </script>
  </span>

  <br><br><br><Br><br><Br><br><br><br><br>
  <div style=\"background-color: #1f1f1f; color: #c7c9c8; width: 50%; padding: 20px; margin: auto;\">
  <span style=\"color: #c7c9c8;\"><b>Disk Usage (/data)</b></span><br>
  <font size=6>$DiskUsage% of $DiskTotal</font><br>
  <center>
  <div id=diskbar style=\"width: 90%; height: 60px;\"></div>
  <script>
  $( function() {
    $( \"#diskbar\" ).progressbar({  value: ".$DiskUsage." });
    progressbar = $( \"#diskbar\" );
    progressbarValue = progressbar.find( \".ui-progressbar-value\" );
    progressbarValue.css({ \"background\": '#3443eb' });
  } );
  </script>
  </center>
  </div>

</div>

";


function get_server_memory_usage(){

    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    $memory_usage = $mem[2]/$mem[1]*100;

    return $memory_usage;
}

?>
