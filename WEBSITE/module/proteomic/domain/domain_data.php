<?php


if (!defined("BIORELS")) header("Location:/");


$GENE_ID=$USER_INPUT['PORTAL']['DATA']['GENE_ID'];
$res=getListDomain($GENE_ID);

$START_POS=0;$END_POS=0;$ISO_ID='';

$INPUT=$USER_INPUT['PAGE']['VALUE'];
if (count($USER_INPUT['PARAMS'])!=3)throw new Exception("Expected 3 values",ERR_TGT_USR);
$START_POS=$USER_INPUT['PARAMS'][0]; if (!is_numeric($START_POS))throw new Exception("Expected Start position to be numeric",ERR_TGT_USR);
$END_POS=$USER_INPUT['PARAMS'][1]; if (!is_numeric($END_POS))throw new Exception("Expected end position to be numeric",ERR_TGT_USR);
$ISO_ID=$USER_INPUT['PARAMS'][2]; if (!is_string($ISO_ID))throw new Exception("Expected Start position to be numeric",ERR_TGT_USR);
$MODULE_DATA=array();

foreach ($res as $info)
{
	if ($info['DOMAIN_NAME']!=$INPUT||$info['POS_START']!=$START_POS||$info['POS_END']!=$END_POS||$info['ISO_ID']!=$ISO_ID)continue;
	$MODULE_DATA['DOMAIN']=$info;
	break;
}
if ($MODULE_DATA==array())throw new Exception("Unable to find record",ERR_TGT_USR);



$MODULE_DATA=array_merge($MODULE_DATA,getAllDomainInfo($MODULE_DATA['DOMAIN']['PROT_DOM_ID'],$MODULE_DATA['DOMAIN']['PROT_SEQ_ID'],true));


?>