<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

if (!isset($USER_INPUT['PORTAL']['DATA']['GENE_ID'])) {
    throw new Exception("Not gene defined", ERR_TGT_SYS);
}

$MODULE_DATA = getProtExpression($USER_INPUT['PORTAL']['DATA']['GENE_ID']);

?>