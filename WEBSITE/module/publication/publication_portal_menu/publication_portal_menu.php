<?php
if (!defined("BIORELS")) header("Location:/");


global $USER_INPUT;

$TARGET=$USER_INPUT['PORTAL']['DATA'];

changeValue("publication_portal_menu","PMID",$TARGET['ENTRY']['PMID']);

?>