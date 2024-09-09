<?php
if (!defined("BIORELS")) header("Location:/");

$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];

$MODULE_DATA=getGeneClinvar($GN_ENTRY_ID);
?>