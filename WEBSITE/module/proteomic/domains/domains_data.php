<?php

if (!defined("BIORELS")) header("Location:/");

$time=microtime_float();

$TMP=getProteinSequences($USER_INPUT['PORTAL']['DATA']['GENE_ID']);



foreach ($TMP['SEQ'] as $SEQ_ID=>$INFO)
{
	$MODULE_DATA['PROT_ENTRY'][$INFO['SEQ']['PROT_IDENTIFIER']]['SEQS'][$SEQ_ID]=$INFO['SEQ'];
}
if (isset($MODULE_DATA['PROT_ENTRY'])){
$TMP2=getDomainInfo(array_keys($MODULE_DATA['PROT_ENTRY']));

$LIST_DOMS=array();
foreach ($TMP2 as $EN)
{
	$LIST_DOMS[]=$EN['PROT_DOM_ID'];
	$MODULE_DATA['PROT_ENTRY'][$EN['PROT_IDENTIFIER']]['DOMAIN'][$EN['PROT_DOM_ID']]=$EN;
}
$MODULE_DATA['DOM_INFO']=getInterProFromUnDom(array_keys($MODULE_DATA['PROT_ENTRY']));
}
//print_r($MODULE_DATA);exit;

foreach ($MODULE_DATA['DOM_INFO'] as $DOMID=>&$DOM_INFO)
{
	foreach ($DOM_INFO['SEQ'] as $SEQ_ID=>&$SEQ_DATA)
	{
		unset($TMP['SEQ'][$SEQ_ID]['TRANSCRIPT']);
		$SEQ_DATA['SEQ_INFO']=$TMP['SEQ'][$SEQ_ID]['SEQ'];
	}
}







?>