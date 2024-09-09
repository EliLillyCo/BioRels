<?php
if (!defined("BIORELS")) {
    header("Location:/");
}

global $USER_INPUT;

$TARGET = $USER_INPUT['PORTAL']['DATA'];

changeValue("proteomic_portal_menu", "ORGANISM", $TARGET['SCIENTIFIC_NAME']);
changeValue("proteomic_portal_menu", "UNIPROT_ID", $TARGET['PROT_IDENTIFIER']);

?>