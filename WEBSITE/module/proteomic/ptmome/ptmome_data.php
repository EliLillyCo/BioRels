<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$MODULE_DATA=array();

if (isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']))
{
    
	$MODULE_DATA['PTM_SITES'] = getPtmSitesFromIsoName($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
	$MODULE_DATA['PTM_DISEASE'] = getDiseasePtmSites($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
	$MODULE_DATA['INPUT']='PROTEIN';

}

else if (isset($USER_INPUT['PORTAL']['DATA']['GENE_ID']))
{
	$MODULE_DATA['SITES'] = getPTMSitesFromGene($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']);
	$MODULE_DATA['INPUT']='GENE';

}

else {
	$MODULE_DATA['ERROR']='No Gene or protein provided';
}

?>
