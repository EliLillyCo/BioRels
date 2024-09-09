<?php

if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA['INFO']=getChrTaxonInfo('9606');
$MODULE_DATA['INPUT']=array();

try{

	for ($K=0;$K<count($USER_INPUT['PARAMS']);++$K)
	{
		$V=$USER_INPUT['PARAMS'][$K];
		if ($V=='chromosome')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No input structure provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			if ($VAL=='')	throw new Exception("Empty Chromosome value",ERR_TGT_USR);	
			$MODULE_DATA['INPUT']['CHROMOSOME']=$VAL;
			$FOUND=false;
			foreach ($MODULE_DATA['INFO'] as $CHR)
			{
			if ($CHR['REFSEQ_NAME'].'.'.$CHR['REFSEQ_VERSION']==$VAL)
			{
				$MODULE_DATA['INPUT']['CHR_INFO']=$CHR;
				$FOUND=true;
			}
			}
			if (!$FOUND)throw new Exception("Selected chromosome has not been found",ERR_TGT_USR);	
			
			++$K;
		}
		if ($V=='start_pos')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No starting position provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			$MODULE_DATA['INPUT']['START_POS']=$VAL;
			if (!is_numeric($VAL))throw new Exception("Starting position not numeric",ERR_TGT_SYS);
			++$K;
		}
		if ($V=='gnval_sel_1')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No gene id provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			$MODULE_DATA['INPUT']['GENE']=$VAL;
			if (!is_numeric($VAL))throw new Exception("Gene ID value not numeric",ERR_TGT_SYS);
			++$K;
		}
		
		if ($V=='end_pos')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No end position provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			$MODULE_DATA['INPUT']['END_POS']=$VAL;
			if (!is_numeric($VAL))throw new Exception("Ending position not numeric",ERR_TGT_SYS);
			++$K;
		}
		if ($V=='name')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No title provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			$TITLE=str_replace("\r","",$VAL);
			
			
			++$K;
		}
		if ($V=='description')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No description provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			$DESCRIPTION=str_replace("\r","",$VAL);
			
			
			++$K;
		}
		
	}
	if ($MODULE_DATA['INPUT']!=array())
	{
		$INPUT=&$MODULE_DATA['INPUT'];
		if (!isset($INPUT['CHR_INFO']))throw new Exception("Chromosome not found",ERR_TGT_USR);
		if (!isset($MODULE_DATA['INPUT']['START_POS']))	throw new Exception("Starting position must be provided",ERR_TGT_USR);	
		if (!isset($MODULE_DATA['INPUT']['END_POS']))	throw new Exception("End position must be provided",ERR_TGT_USR);	
		if ($INPUT['START_POS'] < 1) throw new Exception("Starting position must be above 0",ERR_TGT_USR);
		if ($INPUT['START_POS'] > $INPUT['CHR_INFO']['SEQ_LEN']) throw new Exception("Starting position is above lengh of chromosome sequence ".$INPUT['CHR_INFO']['SEQ_LEN'],ERR_TGT_USR);
		if ($INPUT['END_POS'] < 1) throw new Exception("Ending position must be above 0",ERR_TGT_USR);
		if ($INPUT['END_POS'] > $INPUT['CHR_INFO']['SEQ_LEN']) throw new Exception("Ending position is above lengh of chromosome sequence ".$INPUT['CHR_INFO']['SEQ_LEN'],ERR_TGT_USR);
		if ($INPUT['END_POS']<$INPUT['START_POS']) throw new Exception("Ending position lower than starting position",ERR_TGT_USR);
		if ($TITLE=='')	throw new Exception("Please provide a job name",ERR_TGT_USR);	
		
		$MODULE_DATA['HASH']=submitJob('dna_Variants',$MODULE_DATA['INPUT'],$DESCRIPTION,$TITLE);
		if ($MODULE_DATA['HASH']===false)throw new Exception("Unable to submit job",ERR_TGT_USR);	
	}
	
	}catch(Exception $e)
	{
		$MODULE_DATA['ERROR']=$e->getMessage();
	}
	
?>