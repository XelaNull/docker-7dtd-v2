<?php
function servercontrol() {
  global $INSTALL_DIR;

  $savedCommand=file("$INSTALL_DIR/server.expected_status");
  $savedCommand=trim($savedCommand[0]);

  // Determine if the 7DTD Server is STARTED or STOPPED
  $SERVER_PID=exec("ps awwux | grep 7DaysToDieServer | grep -v sudo | grep -v grep");
  if(strlen($SERVER_PID)>2)
    {
      $server_started=str_replace("\n","",exec("grep 'GameServer.Init successful' $INSTALL_DIR/7dtd.log | wc -l"));
      if($server_started==1)
        {
          $status="STARTED";
          // Print how many WRN and ERR there were, if the $status=STARTED
          $WRN=exec("grep WRN $INSTALL_DIR/7dtd.log | wc -l");
          $ERR=exec("grep ERR $INSTALL_DIR/7dtd.log | wc -l");
          $serverWarningsErrors="<br><font color=yellow>warnings</font>: $WRN | <font color=red>errors</b>: $ERR";
        }
      else $status="STARTING";
    }
  else $status="STOPPED";


  if(@$_GET['control']!='')
    {
      if($_GET['control']=='FORCE_STOP'/* && ($savedCommand=='stop' || $savedCommand=='')*/)
        { exec("echo 'force_stop' > $INSTALL_DIR/server.expected_status"); $status="FORCEFUL STOPPING"; }

      if($_GET['control']=='STOP') { exec("echo 'stop' > $INSTALL_DIR/server.expected_status"); $status="STOPPING"; }

      if($_GET['control']=='START')
        { exec("echo 'start' > $INSTALL_DIR/server.expected_status"); $status="STARTING"; $status_link="<a href=?do=serverstatus&control=FORCE_STOP><img border=0 width=40 src=images/force-stop.png></a>"; }
      $serverStatus=$status;
    }
  else
    {
      if($savedCommand=='stop' && $status=="STARTED") $status='STOPPING';
      $serverStatus="$status ";
      switch($status)
      {
        case "STARTED":
          if($savedCommand!='stop') $status_link="<a href=?do=serverstatus&control=STOP><img border=0 width=40 src=images/stop.jpg></a>";
          else $status_link="<a href=?do=serverstatus&control=FORCE_STOP><img border=0 width=40 src=images/force-stop.png></a>";
        break;
        case "STARTING": $status_link="<a href=?do=serverstatus&control=FORCE_STOP><img border=0 width=40 src=images/force-stop.png></a>"; break;
        case "STOPPED": $status_link="<a href=?do=serverstatus&control=START><img border=0 width=40 src=images/start.png></a>"; break;
      }
    }


$rtn="
<html>
<head>
  <script type = \"text/JavaScript\">
    <!--
    function AutoRefresh( t ) { setTimeout(\"window.location.replace('http://".$_SERVER['HTTP_HOST'].":".$_SERVER['SERVER_PORT']."/?do=serverstatus')\", t); }
    //-->
  </script>
  <style type=\"text/css\">
  body, td {
    font: 12px Arial, Sans-serif;
    margin: 2px;
  }
  </style>
</head>
<body onload = \"JavaScript:AutoRefresh(5000);\" BGCOLOR=\"#525252\" TEXT=white>
<table cellspacing=0 cellpadding=0 width=280>
  <tr>
    <td valign=top>
      <b>Server Status:</b>
      $serverStatus ".date("H:i:s")."<br>
      $serverWarningsErrors
    </td>
    <td align=right>$status_link</td>
  </tr>
</table>
</body>
</html>";
echo $rtn;
}

?>
