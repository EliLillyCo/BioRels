<?php

if (!defined("BIORELS")) header("Location:/");

$PDB_ID=$USER_INPUT['PAGE']['VALUE'];

$MODULE_DATA=getPDBInfo($PDB_ID);

?>