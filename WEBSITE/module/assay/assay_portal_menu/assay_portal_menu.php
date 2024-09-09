<?php
if (!defined("BIORELS")) header("Location:/");


global $USER_INPUT;

$TARGET=$USER_INPUT['PORTAL']['DATA'];

changeValue("assay_portal_menu","ASSAY_NAME",$TARGET['ASSAY_NAME']);
changeValue("assay_portal_menu","LINK",'ASSAY/'.$TARGET['ASSAY_NAME']);
//echo '/ASSAY/'.$TARGET['ASSAY_NAME'];exit;
?>