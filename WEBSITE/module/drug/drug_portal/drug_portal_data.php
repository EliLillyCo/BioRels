<?php

if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit', '400M');

if ($USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID']) {
    
    $MODULE_DATA = getDrugPortalInfo($USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID']);
   $MODULE_DATA['PORTAL_INFO']=$USER_INPUT['PORTAL']['DATA'];
    $MODULE_DATA['CLINICAL_STAT']=getClinicalTrialStatDrug($USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID']);
    //echo '<pre>';print_R($MODULE_DATA);exit;
    //echo '<pre>';print_r($MODULE_DATA['CLINICAL_STAT']);exit;
}


?>