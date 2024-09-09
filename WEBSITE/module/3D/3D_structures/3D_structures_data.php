<?php

if (!defined("BIORELS")) header("Location:/");
$LIST_UNIP=array();
if (isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']))
{
$LIST_UN=geneToUniprot($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']);

foreach ($LIST_UN as $T)$LIST_UNIP[]=$T['UN_IDENTIFIER'];
}
else if(isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']))
{
	$LIST_UNIP[]=$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER'];
}
$MODULE_DATA=getXrayCountFromUniProt($LIST_UNIP);

?>