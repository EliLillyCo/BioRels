<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

//print_r($USER_INPUT);exit;

if ($USER_INPUT['PORTAL']['NAME'] == 'GENE') {
    $GN_ENTRY_ID = $USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
    $MODULE_DATA = getClinicalTrialGene($GN_ENTRY_ID);
    
} else if ($USER_INPUT['PORTAL']['NAME'] == 'DISEASE') {
    $DISEASE_ENTRY = getDiseaseEntry($USER_INPUT['PORTAL']['VALUE'], true, true);
    $MODULE_DATA = getClinicalTrialDisease($DISEASE_ENTRY['DISEASE_ENTRY_ID']);
} else if ($USER_INPUT['PORTAL']['NAME'] == 'COMPOUND') {
    $COMPOUND_ENTRY = getCompoundInfo($USER_INPUT['PORTAL']['VALUE']);
    $MODULE_DATA = getClinicalTrialCompound($COMPOUND_ENTRY[0]['STRUCTURE']['SM_ENTRY_ID']);
} else if ($USER_INPUT['PORTAL']['NAME'] == 'DRUG') {

        $MODULE_DATA = getClinicalTrialDrug( $USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID']);
    
}
