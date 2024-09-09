<?php

if (!defined("BIORELS")) {
    header("Location:/");
}


$MODULE_DATA=array();

if (!isset($USER_INPUT['PORTAL']['DATA']['GENE_ID']) && !isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']) ) {
    $MODULE_DATA['ERROR']='No Gene or protein provided';
}
else if (isset($USER_INPUT['PORTAL']['DATA']['GENE_ID']))
{
$MODULE_DATA = getProteinSequences($USER_INPUT['PORTAL']['DATA']['GENE_ID']);
$MODULE_DATA['INPUT']='GENE';
}
else if (isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']))
{
    
$MODULE_DATA = getProteinSequencesFromProtEntry($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
$MODULE_DATA['INPUT']='PROTEIN';
}
?>