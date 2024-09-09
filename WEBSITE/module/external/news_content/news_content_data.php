<?php
 if (!defined("BIORELS")) header("Location:/");


 $NO_TITLE=false;
 if (in_array('NO_TITLE',$USER_INPUT['PARAMS']))$NO_TITLE=true;
 $NEWS_HASH = $USER_INPUT['PARAMS'][1];
 

if (hasPrivateAccess())$MODULE_DATA=private_getNewsByHash($NEWS_HASH);
else 
$MODULE_DATA=getNewsByHash($NEWS_HASH);

?>