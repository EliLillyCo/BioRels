<?php

if (!defined("BIORELS")) header("Location:/");


$PATH='';
if ($USER_INPUT['PORTAL']['NAME']=='GENE'){
	$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
$MODULE_DATA=getDrugGene($GN_ENTRY_ID);
$PATH='/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'];
}
else if ($USER_INPUT['PORTAL']['NAME']=='DISEASE')
{
	$DISEASE_ENTRY=getDiseaseEntry($USER_INPUT['PORTAL']['VALUE'],false,true);
	//$MODULE_DATA=getDrugDisease(array_keys($DISEASE_ENTRY['ENTRIES']));
	$MODULE_DATA=getDrugDisease($DISEASE_ENTRY['DISEASE_ENTRY_ID']);
	$PATH='/DISEASE/'.$USER_INPUT['PORTAL']['VALUE'];

}

?>
