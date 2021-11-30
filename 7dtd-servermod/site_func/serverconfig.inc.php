<?php
include "vars.inc.php";
// Ensure that this code is only called from the index.php
if($_SERVER['SCRIPT_NAME']!='/index.php') exit;

$screen.="Dumping file: $INSTALL_DIR/serverconfig.xml";
$xmldata=file_get_contents("$INSTALL_DIR/serverconfig.xml");
$xml = new SimpleXMLElement($xmldata);

$screen.="
<div style=\"background-color: #1f1f1f; font-size: 14p; padding: 5px;\">
<table border=0>
<tr><th align=left>Variable Name</th><th align=left>Value</th></tr>";

foreach($xml->property as $variable)
{
  $screen.="<tr><td>$variable[name]</td><td>";
if($variable['name']=='ServerDescription')
  $screen.="<textarea rows=2 cols=80>$variable[value]</textarea>";
elseif ($variable['name']=='ServerLoginConfirmationText')
$screen.="<textarea rows=10 cols=80>$variable[value]</textarea>";
else
  $screen.="<input type=text value=\"$variable[value]\" size=".strlen($variable['value']).">";

$screen.="</td></tr>";
}

$screen.="</table>
<input type=submit>
</div>
";


?>
