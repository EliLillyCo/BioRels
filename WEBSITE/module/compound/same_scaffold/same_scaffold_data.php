<?php
if (!defined("BIORELS")) header("Location:/");


$MODULE_DATA['INI']=getCompoundInfo($USER_INPUT['PORTAL']['VALUE']);
$MODULE_DATA['ALT']=getCompoundFromScaffold($MODULE_DATA['INI'][0]['STRUCTURE']['SM_SCAFFOLD_ID']);



?>