<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$NAME = $USER_INPUT['PARAMS'][0];
$MODULE_DATA = getDiseaseEntry($NAME, true, true);

?>