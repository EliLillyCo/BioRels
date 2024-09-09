<?php
 if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA=&$USER_INPUT['PORTAL']['DATA'];

$MODULE_DATA['FT']=runQuery("SELECT COUNT(*) co FROM pmid_fulltext p WHERE pmid_entry_id = ".$MODULE_DATA['ENTRY']['PMID_ENTRY_ID'])[0];

?>