<?php

if (!defined("BIORELS")) header("Location:/");


$MODULE_DATA=array();
if (isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']))
{

	$gn_entry_Id=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];

	$MODULE_DATA=getGeneAssays($gn_entry_Id);
}
else if (isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']))
{
	$PROT_IDENTIFIER=$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER'];

	$MODULE_DATA=getProtAssays($PROT_IDENTIFIER);
}



?>