<?php
ini_set('memory_limit','2000M');
if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA=getProteinSequence($USER_INPUT['PAGE']['VALUE']);
?>
