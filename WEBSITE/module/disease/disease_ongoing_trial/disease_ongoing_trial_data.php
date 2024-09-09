<?php

if (!defined("BIORELS")) header("Location:/");


$MODULE_DATA['CLINICAL_STAT']=getClinicalTrialStatDisease($USER_INPUT['PORTAL']['DATA']['DISEASE_ENTRY_ID'],true,true);
//DISEASE_ONGOING_TRIAL


?>