<?php

if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA['INPUT']=array();

try{
	for ($K=0;$K<count($USER_INPUT['PARAMS']);++$K)
	{
		$V=$USER_INPUT['PARAMS'][$K];
		if ($V=='smiles_input')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No input structure provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			if ($VAL=='')	throw new Exception("Empty input structure",ERR_TGT_USR);	
			$MODULE_DATA['INPUT']['STRUCTURE']=str_replace("\r","",$VAL);
			
			++$K;
		}
		if ($V=='title')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No title provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			
			$TITLE=$VAL;
			++$K;
		}
		
		if ($V=='description')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No description provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			
			$DESCRIPTION=$VAL;
			++$K;
		}
		if ($V=='threshold')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No threshold provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			$MODULE_DATA['INPUT']['THRESHOLD']=str_replace("\r","",$VAL);
			
			
			++$K;
		}
		if ($V=='search_type')
		{
	
			if (!isset($USER_INPUT['PARAMS'][$K+1]))throw new Exception("No search type provided",ERR_TGT_SYS);
			$VAL=$USER_INPUT['PARAMS'][$K+1];
			$MODULE_DATA['INPUT']['SEARCH_TYPE']=str_replace("\r","",$VAL);
			
			
			++$K;
		}
		
	}
	if ($MODULE_DATA['INPUT']!=array())
	{
		
		if ($TITLE=='')	throw new Exception("Please provide a job name",ERR_TGT_USR);	
		if (!isset($MODULE_DATA['INPUT']['STRUCTURE']))	throw new Exception("A structure must be provided",ERR_TGT_USR);	
		if (!isset($MODULE_DATA['INPUT']['SEARCH_TYPE']))	throw new Exception("A search type must be provided",ERR_TGT_USR);	
		if ($MODULE_DATA['INPUT']['SEARCH_TYPE']=='SIMILARITY' && !isset($MODULE_DATA['INPUT']['THRESHOLD']))throw new Exception("A threshold must be provided",ERR_TGT_USR);	
		
		$MODULE_DATA['HASH']=submitJob('simdrug_search',$MODULE_DATA['INPUT'],$DESCRIPTION,$TITLE);
		if ($MODULE_DATA['HASH']===false)throw new Exception("Unable to submit job",ERR_TGT_USR);	
	}
	
	}catch(Exception $e)
	{
		$MODULE_DATA['ERROR']=$e->getMessage();
	}
	
?>