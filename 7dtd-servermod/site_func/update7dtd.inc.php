<?php

$screen="<h3>Logs</h3>
<div style=\"color: #c7c9c8;\">Showing Log File: 7dtdreinstall.log</div>";

$screen.="

<!------------>
<div id=\"7dtdreinstalllog\" style=\"background-color: #1f1f1f; font-size: 14p; padding: 5px;\"></div>
<br>
<script type=\"text/javascript\">
var refreshtime=10;
function tc()
{
asyncAjax(\"GET\",\"7dtdreinstalllog.php\",Math.random(),display,{});
setTimeout(tc,refreshtime);
}
function display(xhr,cdat)
{
 if(xhr.readyState==4 && xhr.status==200)
 {
   document.getElementById(\"7dtdreinstalllog\").innerHTML=xhr.responseText;
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
