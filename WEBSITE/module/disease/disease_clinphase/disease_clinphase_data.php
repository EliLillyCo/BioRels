<?php

if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit', '400M');

if ($USER_INPUT['PORTAL']['DATA']['DISEASE_ENTRY_ID']) {
  $MODULE_DATA['CLINICAL_STAT']=getClinicalTrialsCountsDisease($USER_INPUT['PORTAL']['DATA']['DISEASE_ENTRY_ID']);
    
}


?>