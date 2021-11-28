<?php
include "vars.inc.php";

switch($_GET['DO'])
{
  default:
  case "status": include("site_func/status.inc.php"); break;
  case "serverconfig": include("site_func/serverconfig.inc.php"); break;
  case "modselections": include("site_func/modselection.inc.php"); break;
  case "modconfig": include("site_func/modconfig.inc.php"); break;
  case "logs": include("site_func/logs.inc.php"); break;
  case "adminips": include("site_func/adminips.inc.php"); break;
  case "update7dtd": include("site_func/update7dtd.inc.php"); break;
  case "updatesmm": include("site_func/updatesmm.inc.php"); break;
  case "backupsets": include("site_func/backupsets.inc.php"); break;
  case "backupserver": include("site_func/backupserver.inc.php"); break;
}
mainscreen($screen);

/////////////////////////////////////////////////////////////////////////////

function mainscreen($CONTENT)
{
global $INSTALL_DIR, $SERVER_IP, $SERVER_PORT, $SERVER_STATUS, $SERVER_NAME;

$SERVER_STATUS="Online";

?>
<html>

<head>

  <link rel="stylesheet" href="main.css">

  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
  <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.js"></script>

  <title></title>


</head>

<body>
<div class="page-wrapper">
        <div class="left-column">
                <img src=images/smm_logo.png width=200>
                <br>
                <h3>SETTINGS</h3>
                        <div class="leftmenu" onclick="location.href='index.php?DO=status';"><span><img src=images/menu_status.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Status</span></div>
                        <div class="leftmenu" onclick="location.href='index.php?DO=serverconfig';"><span><img src=images/menu_serverconfig.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Server Config</span></div>
                        <div class="leftmenu" onclick="location.href='index.php?DO=modselection';"><span><img src=images/menu_modselect.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Mod Selections</span></div>
                        <div class="leftmenu" onclick="location.href='index.php?DO=modconfig';"><span><img src=images/menu_modconfig.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Mod Config</span></div>
                        <div class="leftmenu" onclick="location.href='index.php?DO=logs';"><span><img src=images/menu_logs.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Logs</span></div>


                <h3>ADMINISTRATION</h3>
                        <div class="leftmenu" onclick="location.href='index.php?DO=adminips';"><span><img src=images/menu_adminips.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Admin IP Whitelist</span></div>
                        <div class="leftmenu" onclick="location.href='index.php?DO=update7dtd';"><span><img src=images/menu_update7dtd.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Update 7DTD</span></div>
                        <div class="leftmenu" onclick="location.href='index.php?DO=updatesmm';"><span><img src=images/menu_updatesmm.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Update SMM</span></div>
                        <div class="leftmenu" onclick="location.href='index.php?DO=backupmodsets';"><span><img src=images/menu_backupmodsets.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Backup Mod Selections</span></div>
                        <div class="leftmenu" onclick="location.href='index.php?DO=backupserver';"><span><img src=images/menu_backupserver.jpg width=40></span><span style="padding-left: 20px;">&nbsp;</span><span style="position:relative; top: -15px">Backup Server</span></div>

                <div class="left-bottom">
                        <span><img src=images/menu_logout.jpg width=40></span><span style="padding-left: 10px;">&nbsp;</span><span style="position:relative; top: -15px">Logout</span>
                </div>
        </div>

        <div class="top-right">
                <span style="position:relative; top: 10px; left: 10px;"><img src=images/7dtd_logo.png height=120px></span>
                <div style="color: #000000; padding-right: 15px; padding-top: 15px; float: right; top: -5px;">

<?php
$xmldata=file_get_contents("$INSTALL_DIR/serverconfig.xml");
$xml = new SimpleXMLElement($xmldata);
$xmlentry=$xml->xpath("//property[@name='ServerName']/@value"); $SERVER_NAME=$xmlentry[0]->value;
$xmlentry=$xml->xpath("//property[@name='ServerPort']/@value"); $SERVER_PORT=$xmlentry[0]->value;
$SERVER_ADDRESS=$_SERVER['HTTP_HOST'];

echo "\t\t<span style=\"font-size: 32px; text-shadow: 1px 2px #212121;\">$SERVER_NAME</span><br>";
echo "\t\t<span style=\"\"><b>Status:</b> $SERVER_STATUS | <b>IP:</b> $SERVER_ADDRESS | <b>Port:</b> $SERVER_PORT</span>";
?>
                </div>
        </div>

        <div class="bottom-right">
                <div class="pad"><?php echo $CONTENT; ?></div>
        </div>
</div>


</body>
</html>
<?php

}


?>
