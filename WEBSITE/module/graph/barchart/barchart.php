<?php

if (!defined("BIORELS")) {
    header("Location:/");
}



changeValue("barchart",'TAG',$MODULE_DATA['TAG']);
changeValue("barchart",'WIDTH',$MODULE_DATA['WIDTH']);
changeValue("barchart",'ID',$MODULE_DATA['ID']);
changeValue("barchart",'DATA',str_replace("'","\\'",json_encode($MODULE_DATA['DATA'])));
if (!isset($MODULE_DATA['PARENT']))removeBlock("barchart",'W_PARENT');
else changeValue("barchart","PARENT",$MODULE_DATA['PARENT']);

if (isset($MODULE_DATA['COLOR']))
{
    changeValue("barchart","COLOR",'function(d) { return d.color; }');
}else changeValue("bartchar","COLOR",'"#263f6a"');
?>