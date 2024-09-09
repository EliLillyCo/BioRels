<?php

if (!defined("BIORELS")) header("Location:/");
//changeValue("pathway","SYMBOL",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
if ($MODULE_DATA['PATHWAYS']==array())
{
	removeBlock("pathway","VALID");
}else removeBlock("pathway","INVALID");
print_r($MODULE_DATA);
$PW_ID=array_keys($MODULE_DATA['PATHWAYS'])[0];
changeValue("pathway","PATHWAY_NAME",$MODULE_DATA['PATHWAYS'][$PW_ID]['PW_NAME']);
changeValue("pathway","INI_PATHWAY",$USER_INPUT['PAGE']['VALUE']);
if (isset($USER_INPUT['PORTAL']['DATA']['SYMBOL']))changeValue("pathway","HIGHLIGHT",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
else if (isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']))changeValue("pathway","HIGHLIGHT",$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
?>