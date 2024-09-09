<?php


if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit','1000M');
$GN_ENTRY_ID='';
$GENE_INFO=array();
if (isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']))
{
	$GENE_INFO=$USER_INPUT['PORTAL']['DATA'];
$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
}
else
{
	$GENE_INFO=gene_portal_geneID($USER_INPUT['PARAMS'][0]);
	$GN_ENTRY_ID=$GENE_INFO['GN_ENTRY_ID'];
}
$MODULE_DATA['STAT']=getCountPubliGene($GN_ENTRY_ID);
