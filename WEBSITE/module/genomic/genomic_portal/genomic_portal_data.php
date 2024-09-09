<?php

if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA['GENE_INFO']=$USER_INPUT['PORTAL']['DATA'];

$MODULE_DATA['GENE_LOCATION']=getGeneLocation($MODULE_DATA['GENE_INFO']['GENE_ID']);
$MODULE_DATA['RNA_EXPR']=getGeneGTEXExprStat($MODULE_DATA['GENE_INFO']['GN_ENTRY_ID']);
$MODULE_DATA['TRANSCRIPTS']=getTranscriptStats($MODULE_DATA['GENE_INFO']['GN_ENTRY_ID']);
$MODULE_DATA['ORTHOLOGS']=getOrthologs($MODULE_DATA['GENE_INFO']['GENE_ID'],true);
$MODULE_DATA['UNIPROT']=geneToUniprot($MODULE_DATA['GENE_INFO']['GN_ENTRY_ID']);

$MODULE_DATA['PATHWAY']=getPathwayStats($MODULE_DATA['GENE_INFO']['GN_ENTRY_ID']);
//$MODULE_DATA['DRUGS']=getDrugGene($MODULE_DATA['GENE_INFO']['GN_ENTRY_ID']);
//$MODULE_DATA['GENIE']=getGenie($MODULE_DATA['GENE_INFO']['GN_ENTRY_ID']);




if (hasPrivateAccess())
{
    $MODULE_DATA['NEWS']=private_getGeneNews($MODULE_DATA['GENE_INFO']['GN_ENTRY_ID']);
}

else $MODULE_DATA['NEWS']=getGeneNews($MODULE_DATA['GENE_INFO']['GN_ENTRY_ID']);
global $USER_INPUT;

$TIME=array();$ts=microtime_float();




?>