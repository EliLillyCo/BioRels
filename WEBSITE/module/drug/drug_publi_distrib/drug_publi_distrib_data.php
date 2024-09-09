<?php

if (!defined("BIORELS")) header("Location:/");


if ($USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID']) {
   
    $MODULE_DATA = getDrugPubliStat($USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID']);
   
    
}


?>