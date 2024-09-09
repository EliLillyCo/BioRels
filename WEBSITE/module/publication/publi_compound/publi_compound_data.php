<?php


if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit', '1000M');

$CP_ENTRY_ID = $USER_INPUT['PORTAL']['DATA']['SM_ENTRY_ID'];
if (isset($USER_INPUT['PORTAL']['DATA']['SM_ENTRY_ID'])) {
    $MODULE_DATA['STAT'] = getCountPubliCompound($CP_ENTRY_ID);
}
