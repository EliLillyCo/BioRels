<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$TRAIT_ID = $USER_INPUT['PAGE']['VALUE'];
$MODULE_DATA = getClinvarFromTrait($TRAIT_ID);

?>