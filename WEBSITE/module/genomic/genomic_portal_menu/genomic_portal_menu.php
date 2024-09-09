<?php
if (!defined("BIORELS")) header("Location:/");


global $USER_INPUT;

$TARGET=$USER_INPUT['PORTAL']['DATA'];
changeValue("genomic_portal_menu","ORGANISM",$USER_INPUT['PORTAL']['DATA']['SCIENTIFIC_NAME']);
changeValue("genomic_portal_menu","SYMBOL",$TARGET['SYMBOL']);
changeValue("genomic_portal_menu","GENE_ID",$TARGET['GENE_ID']);
?>