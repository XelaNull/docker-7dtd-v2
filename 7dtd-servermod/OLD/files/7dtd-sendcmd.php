#!/usr/bin/php -q
<?php
$ip=exec("/sbin/ifconfig | grep broad");
$ipParts=array_Filter(explode(" ", $ip));
$cnt=0;
foreach($ipParts as $part) { $cnt++; if($cnt==2) $ip=$part; }
echo file_get_contents("http://$ip:8082/api/executeconsolecommand?adminuser=admin&admintoken=adm1n&raw=true&command=$argv[1]");
?>
