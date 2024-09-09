<?php

if (!defined("BIORELS")) {
    header("Location:/");
}



changeValue("sunburst",'TAG',$MODULE_DATA['TAG']);
changeValue("sunburst",'WIDTH',$MODULE_DATA['WIDTH']);
changeValue("sunburst",'ID',$MODULE_DATA['ID']);
changeValue("sunburst",'DATA',json_encode($MODULE_DATA['DATA']));
?>