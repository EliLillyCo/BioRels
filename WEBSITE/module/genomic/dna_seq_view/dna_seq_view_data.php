<?php

if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit','1000M');


$PARAMS=array('CHR'=>'','POSITION'=>'','TAXON'=>9606,'STRAND'=>"+",'RANGE'=>100);
try{

for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
{
	$V=$USER_INPUT['PARAMS'][$I];
	if ($V=='CHR')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No chromosome provided");
		++$I;
		$PARAMS['CHR']=$USER_INPUT['PARAMS'][$I];
	}
	if ($V=='POSITION')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No position provided");
		++$I;
		$PARAMS['POSITION']=$USER_INPUT['PARAMS'][$I];
		if (!is_numeric($PARAMS['POSITION']))throw new Exception("A chromosomal position must be numeric");
	}
	if ($V=='TAXON')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No chromosome provided");
		++$I;
		$PARAMS['TAXON']=$USER_INPUT['PARAMS'][$I];
		if (!is_numeric($PARAMS['TAXON']))throw new Exception("A taxonomic identifier must be numeric");
	}
	if ($V=='STRAND')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No strand provided");
		++$I;
		$PARAMS['STRAND']=$USER_INPUT['PARAMS'][$I];
		if ($PARAMS['STRAND']!='+' && $PARAMS['STRAND']!='-')throw new Exception("Strand must be positive or negative");
	}
	if ($V=='RANGE')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No strand provided");
		++$I;
		$PARAMS['RANGE']=$USER_INPUT['PARAMS'][$I];
		if (!is_numeric($PARAMS['RANGE']))throw new Exception("A range  must be numeric");
	}
}




$MODULE_DATA['DNA_INFO']=getChrInfo($PARAMS['TAXON'],$PARAMS['STRAND'],$PARAMS['CHR'],$PARAMS['POSITION']);
$MODULE_DATA['DNA_SEQUENCE']=getChrSequence($PARAMS['TAXON'],$PARAMS['STRAND'],$PARAMS['CHR'],$PARAMS['POSITION'],$PARAMS['RANGE']) ;
if (isset($MODULE_DATA['DNA_SEQUENCE']['TRANSCRIPT']))
{
	$MODULE_DATA['TRANSCRIPT']=$MODULE_DATA['DNA_SEQUENCE']['TRANSCRIPT'];
	unset($MODULE_DATA['DNA_SEQUENCE']['TRANSCRIPT']);
}
}catch(Exception $e)
{
	$MODULE_DATA['ERROR']=$e->getMessage();
}

?>