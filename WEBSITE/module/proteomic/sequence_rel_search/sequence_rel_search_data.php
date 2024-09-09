<?php

if (!defined("BIORELS")) header("Location:/");



$GENE_ID=$USER_INPUT['PORTAL']['DATA']['GENE_ID'];
$res=getProteinSequences($GENE_ID);

$START_POS=0;$END_POS=0;$ISO_ID='';

$INPUT=$USER_INPUT['PAGE']['VALUE'];
$PER_PAGE=20000;
$N_PAGE=0;
$TYPE='';
$VALID_TYPE=array('BY_SIMSEQ');
$N_PARAMS=count($USER_INPUT['PARAMS']);
for ($K=0;$K<$N_PARAMS;++$K)
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
foreach ($res['SEQ'] as $info)
{
	
	if ($info['SEQ']['ISO_ID']!=$INPUT)continue;
	$MODULE_DATA['SEQUENCE']=$info['SEQ'];
	break;
}
if ($MODULE_DATA==array())throw new Exception("Unable to find record",ERR_TGT_USR);


if ($TYPE=='BY_SIMSEQ')
{
	
$MODULE_DATA['RESULTS']=searchSimSeqBySeq($MODULE_DATA['SEQUENCE']['PROT_SEQ_ID'],array('MIN'=>$N_PAGE*$PER_PAGE,'MAX'=>($N_PAGE+1)*$PER_PAGE,'TYPE'=>$TYPE));

}
else if ($TYPE=="3D_STRUCT")
{
	$MODULE_DATA['RESULTS']=searchXrayCoverageByDomain($MODULE_DATA['DOMAIN']['PROT_DOM_ID'],array('MIN'=>($N_PAGE-1)*$PER_PAGE+1,'MAX'=>($N_PAGE)*$PER_PAGE+1,'TYPE'=>$TYPE));
}
?>