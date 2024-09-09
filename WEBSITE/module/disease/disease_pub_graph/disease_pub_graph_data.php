<?php

if (!defined("BIORELS")) header("Location:/");


$MODULE_DATA['ALL']=getDiseasePubStat($USER_INPUT['PORTAL']['DATA']['DISEASE_ENTRY_ID'],true);
$MODULE_DATA['SELF']=getDiseasePubStat($USER_INPUT['PORTAL']['DATA']['DISEASE_ENTRY_ID'],false);

?>