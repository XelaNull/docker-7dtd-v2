<?php
include "vars.inc.php";
// Ensure that this code is only called from the index.php
if($_SERVER['SCRIPT_NAME']!='/index.php') exit;

$screen="<h3>Logs</h3>
<div style=\"color: #c7c9c8;\">Showing Log File: smmreinstall.log</div>";

$screen.="

<!------------>
<div id=\"smmreinstalllog\" style=\"background-color: #1f1f1f; font-size: 14p; padding: 5px;\"></div>
<br>
<script type=\"text/javascript\">
var refreshtime=10;
function tc()
{
asyncAjax(\"GET\",\"smmreinstalllog.php\",Math.random(),display,{});
setTimeout(tc,refreshtime);
}
function display(xhr,cdat)
{
 if(xhr.readyState==4 && xhr.status==200)
 {
   document.getElementById(\"smmreinstalllog\").innerHTML=xhr.responseText;
 }
}
function asyncAjax(method,url,qs,callback,callbackData)
{
    var xmlhttp=new XMLHttpRequest();
    //xmlhttp.cdat=callbackData;
    if(method==\"GET\")
    {
        url+=\"?\"+qs;
    }
    var cb=callback;
    callback=function()
    {
        var xhr=xmlhttp;
        //xhr.cdat=callbackData;
        var cdat2=callbackData;
        cb(xhr,cdat2);
        return;
    }
    xmlhttp.open(method,url,true);
    xmlhttp.onreadystatechange=callback;
    if(method==\"POST\"){
            xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            xmlhttp.send(qs);
    }
    else
    {
            xmlhttp.send(null);
    }
}
tc();
</script>
<!------------>

";


?>
