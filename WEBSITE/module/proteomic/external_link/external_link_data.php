<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

if (isset($USER_INPUT['PORTAL']['DATA']['GENE_ID']))
{
$GENE_ID = $USER_INPUT['PORTAL']['DATA']['GENE_ID'];
$MODULE_DATA = getExternalLinksFromGene($GENE_ID);
}
else 
{
    $MODULE_DATA = getExternalLinksFromProtein($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
}
?>