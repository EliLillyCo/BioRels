<?php

if (!defined("BIORELS")) header("Location:/");


if ($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']) {
	$MODULE_DATA=getGenePubStat($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']);
    
   
    
}


?>