<?php

if (!defined("BIORELS")) header("Location:/");



$GENE_ID=$USER_INPUT['PORTAL']['DATA']['GENE_ID'];
$res=getListDomain($GENE_ID);

$START_POS=0;$END_POS=0;$ISO_ID='';

$INPUT=$USER_INPUT['PAGE']['VALUE'];
if (count($USER_INPUT['PARAMS'])<3)throw new Exception("Expected 3 values",ERR_TGT_USR);
$START_POS=$USER_INPUT['PARAMS'][0]; if (!is_numeric($START_POS))throw new Exception("Expected Start position to be numeric",ERR_TGT_USR);
$END_POS=$USER_INPUT['PARAMS'][1]; if (!is_numeric($END_POS))throw new Exception("Expected end position to be numeric",ERR_TGT_USR);
$ISO_ID=$USER_INPUT['PARAMS'][2]; if (!is_string($ISO_ID))throw new Exception("Expected Start position to be numeric",ERR_TGT_USR);
$PER_PAGE=0;
$N_PAGE=0;
$TYPE='';
$VALID_TYPE=array('3D_STRUCT','BY_SIMDOM','3D_LIG');
$N_PARAMS=count($USER_INPUT['PARAMS']);
for ($K=3;$K<$N_PARAMS;++$K)
{
	$V=&$USER_INPUT['PARAMS'][$K];
	if ($V=='PER_PAGE')
	{
		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("Number per page required",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		if (!is_numeric($VAL))throw new Exception("Value per page must be numeric",ERR_TGT_SYS);
		$PER_PAGE=$VAL;++$K;
	}
	if ($V=='PAGE')
	{
		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception(" page number required",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		if (!is_numeric($VAL))throw new Exception("Value per page must be numeric",ERR_TGT_SYS);
		$N_PAGE=$VAL;++$K;
	}
	if ($V=='TYPE')
	{
		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception(" type required",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		if (!in_array($VAL,$VALID_TYPE))throw new Exception("Invalid type",ERR_TGT_SYS);
		$TYPE=$VAL;++$K;
	}
}
$MODULE_DATA=array();

foreach ($res as $info)
{
	if ($info['DOMAIN_NAME']!=$INPUT||$info['POS_START']!=$START_POS||$info['POS_END']!=$END_POS||$info['ISO_ID']!=$ISO_ID)continue;
	$MODULE_DATA['DOMAIN']=$info;
	break;
}
if ($MODULE_DATA==array())throw new Exception("Unable to find record",ERR_TGT_USR);

if ($TYPE=='BY_SIMDOM')
{
$MODULE_DATA['RESULTS']=searchSimDomByDomain($MODULE_DATA['DOMAIN']['PROT_DOM_ID'],array('MIN'=>$N_PAGE*$PER_PAGE,'MAX'=>($N_PAGE+1)*$PER_PAGE,'TYPE'=>$TYPE));
//echo "<pre>";print_r($MODULE_DATA);exit;
}
else if ($TYPE=="3D_STRUCT")
{
	$MODULE_DATA['RESULTS']=searchXrayCoverageByDomain($MODULE_DATA['DOMAIN']['PROT_DOM_ID'],array('MIN'=>($N_PAGE-1)*$PER_PAGE+1,'MAX'=>($N_PAGE)*$PER_PAGE+1,'TYPE'=>$TYPE));
}
?>