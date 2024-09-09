<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$MODULE_DATA = getOrthologs($USER_INPUT['PORTAL']['DATA']['GENE_ID']);

?>