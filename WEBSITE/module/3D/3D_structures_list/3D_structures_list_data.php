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

$N_PARAMS=count($USER_INPUT['PARAMS']);
$PER_PAGE=0;
$N_PAGE=0;
$TYPE='';
$VALID_TYPE=array('BY_GENE','BY_SIMDOM','BY_SIMSEQ');
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


$MODULE_DATA['LIST_UNIPROT']=$LIST_UNIP;
$MODULE_DATA['RESULTS']=searchXrayFromUniprot($LIST_UNIP,array('MIN'=>($N_PAGE-1)*$PER_PAGE,'MAX'=>($N_PAGE)*$PER_PAGE,'TYPE'=>$TYPE));

?>