<?php

if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA=array();


if (!isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'])&&!isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']) )
{
	$MODULE_DATA['ERROR']='No gene or protein specified';
}
else if (isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']))
{
	$MODULE_DATA=getPathwayFromGene($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']);
	$MODULE_DATA['INPUT']='GENE';
}
else if (isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']))
{
	
	$MODULE_DATA=getPathwayFromProtein($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
	$MODULE_DATA['INPUT']='PROTEIN';
}
?>