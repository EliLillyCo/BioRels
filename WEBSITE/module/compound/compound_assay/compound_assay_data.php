<?php
if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA = array();
echo '<pre>';

// CHECK FIRST FOR SM ENTRY (runs for both search by drug name and by sm name)
if ($USER_INPUT['PORTAL']['NAME']=='COMPOUND') {
    $MODULE_DATA['STAT']['CO'] = getAssayCountByCompoundId(array($USER_INPUT['PORTAL']['DATA']));
    if (isset($MODULE_DATA['STAT']['CO']) &&  ($MODULE_DATA['STAT']['CO'] != 0)) {
        $MODULE_DATA['ASSAYS_NAMES'] = getAssaysByCompoundId(array($USER_INPUT['PORTAL']['DATA']));
    }
}

if ($USER_INPUT['PORTAL']['NAME']=='DRUG') {
    $LIST_ID=getCompoundsFromDrug($USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID']);
    
    $MODULE_DATA['STAT']['CO'] = getAssayCountByCompoundId($LIST_ID);
    if (isset($MODULE_DATA['STAT']['CO']) &&  ($MODULE_DATA['STAT']['CO'] != 0)) {
        $MODULE_DATA['ASSAYS_NAMES'] = getAssaysByCompoundId($LIST_ID);
    }
}


// runs after weve checked both types
if (isset($MODULE_DATA['ASSAYS_NAMES'])) {
    $MODULE_DATA = getCompoundAssays($MODULE_DATA['ASSAYS_NAMES']);
}
