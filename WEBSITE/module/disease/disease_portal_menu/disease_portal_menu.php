<?php
if (!defined("BIORELS")) {
    header("Location:/");
}

global $USER_INPUT;

$TARGET = $USER_INPUT['PORTAL']['DATA'];
print_r($TARGET);
changeValue("disease_portal_menu", "DISEASE_NAME", $TARGET['DISEASE_NAME']);
changeValue("disease_portal_menu", "DISEASE_TAG", $TARGET['DISEASE_TAG']);


?>