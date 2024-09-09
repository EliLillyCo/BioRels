<?php

if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA['PRE_INPUT']=getGenomeInfo();


$LIST_ALLOWED_REGION=array("CDS","3'UTR","5'UTR","transcriptome","premRNA","promoter");



$MODULE_DATA['INPUT']=array();$TITLE='';$DESCRIPTION='';
try{
for ($K=0;$K<count($USER_INPUT['PARAMS']);++$K)
{
	$V=$USER_INPUT['PARAMS'][$K];
	if ($V=='input_sequence')
	{

		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No input sequence provided",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		if ($VAL=='')	throw new Exception("Empty input sequence",ERR_TGT_USR);	
		$MODULE_DATA['INPUT']['SEQUENCE']=str_replace("\r","",$VAL);
		++$K;
	}
	if ($V=='title')
	{

		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No title provided",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		
		$TITLE=$VAL;
		++$K;
	}
	if ($V=='gnval_sel_1')
	{

		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No gene provided",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		
		$MODULE_DATA['INPUT']['GENE']=$VAL;
		++$K;
	}
	if ($V=='description')
	{

		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No description provided",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		
		$DESCRIPTION=$VAL;
		++$K;
	}
	if ($V=='mismatch')
	{
		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No mismatch selected",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		if (!is_numeric($VAL))	throw new Exception("Mismatch is not recognized",ERR_TGT_USR);	
		
		$MODULE_DATA['INPUT']['MISMATCH']=$VAL;	
		++$K;
	}
	if ($V=='sense')
	{
		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No sense selected",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		if ($VAL!='on')throw new Exception("Unreocgnized value for sense",ERR_TGT_USR);
		
		$MODULE_DATA['INPUT']['SENSE']=true;	
		++$K;	
	}
	if ($V=='antisense')
	{
		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No antisense selected",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		if ($VAL!='on')throw new Exception("Unreocgnized value for antisense",ERR_TGT_USR);
		
		$MODULE_DATA['INPUT']['ANTISENSE']=true;
		++$K;	
	}
	if ($V=='organism')
	{
		if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No organism selected",ERR_TGT_SYS);
		$VAL=$USER_INPUT['PARAMS'][$K+1];
		if (!is_array($VAL))	throw new Exception("Organism is not recognized",ERR_TGT_USR);	
		foreach ($VAL as $ORG)
		if (!isset($MODULE_DATA['PRE_INPUT']['GENOME'][$ORG]))throw new Exception($ORG." is not a valid organism",ERR_TGT_USR);
		$MODULE_DATA['INPUT']['ORGANISM']=$VAL;	
		++$K;
	}
}
if ($MODULE_DATA['INPUT']!=array())
{
	
	if ($TITLE=='')	throw new Exception("Please provide a job name",ERR_TGT_USR);	
	if (!isset($MODULE_DATA['INPUT']['SEQUENCE']))	throw new Exception("A sequence must be provided",ERR_TGT_USR);	
	if (!isset($MODULE_DATA['INPUT']['SENSE'])&&!isset($MODULE_DATA['INPUT']['ANTISENSE']))	throw new Exception("At least one strand must be selected",ERR_TGT_USR);	
	if (!isset($MODULE_DATA['INPUT']['ORGANISM']))	throw new Exception("At least one organism must be selected",ERR_TGT_USR);	
	if (!isset($MODULE_DATA['INPUT']['MISMATCH']))	throw new Exception("Maximum number of mismatch must be selected",ERR_TGT_USR);	
	if (!isset($MODULE_DATA['INPUT']['GENE']))	throw new Exception("No gene selected",ERR_TGT_USR);	
	
	$MODULE_DATA['HASH']=submitJob('sirna_orthologs',$MODULE_DATA['INPUT'],$DESCRIPTION,$TITLE);
	if ($MODULE_DATA['HASH']===false)throw new Exception("Unable to submit job",ERR_TGT_USR);	
}

}catch(Exception $e)
{
	$MODULE_DATA['ERROR']=$e->getMessage();
}



?>