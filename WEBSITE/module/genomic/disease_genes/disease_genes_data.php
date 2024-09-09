<?php

if (!defined("BIORELS")) header("Location:/");

if ($USER_INPUT['PORTAL']['NAME']=='GENE'){
	$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
	$MODULE_DATA=getDiseasePMIDStatGene($GN_ENTRY_ID);
}
else if ($USER_INPUT['PORTAL']['NAME']=='DISEASE')
{
	$DISEASE_ENTRY=getDiseaseEntry($USER_INPUT['PORTAL']['VALUE'],true,true);
	$MODULE_DATA=getDiseasePMIDStat($DISEASE_ENTRY['DISEASE_ENTRY_ID']);

}

?>