<?php
 if (!defined("BIORELS")) header("Location:/");


 
if (hasPrivateAccess())$MODULE_DATA=private_getNewsfile($USER_INPUT['PAGE']['VALUE']);
else 
$MODULE_DATA=getNewsfile($USER_INPUT['PAGE']['VALUE']);

?>