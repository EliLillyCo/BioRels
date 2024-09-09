<?php

if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit', '400M');

// first pass checks for sm linked data
if ($USER_INPUT['PORTAL']['DATA']['SM_NAME']) {

    $MODULE_DATA = getCompoundInfo($USER_INPUT['PORTAL']['DATA']['SM_NAME']);
}
