<?php


if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit', '1000M');


$DRUG_ENTRY_ID = $USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID'];
if (isset($USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID'])) {
    $MODULE_DATA['STAT'] = getCountPubliDrug($DRUG_ENTRY_ID);
}
