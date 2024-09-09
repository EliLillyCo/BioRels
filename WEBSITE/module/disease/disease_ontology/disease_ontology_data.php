<?php

if (!defined("BIORELS")) header("Location:/");


$MODULE_DATA['PARENT']=getDiseaseParentOntology($USER_INPUT['PORTAL']['DATA']['DISEASE_ENTRY_ID']);
$MODULE_DATA['CHILD']=getDiseaseChildOntology($USER_INPUT['PORTAL']['DATA']['DISEASE_ENTRY_ID']);

?>