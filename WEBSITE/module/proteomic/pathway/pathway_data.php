<?php

if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA=array();
$MODULE_DATA=getPathwayFromReacID($USER_INPUT['PAGE']['VALUE']);
/*
if (isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']) )
{
	$TMP=getPathwayFromGene($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']);
	
	foreach ($TMP['PATHWAYS'] as $PW)
	{
		if ($PW['REAC_ID']!=$USER_INPUT['PAGE']['VALUE'])continue;
		$MODULE_DATA['PATHWAY']=$PW;
		break;
	}
}
else
{
	
}*/

?>