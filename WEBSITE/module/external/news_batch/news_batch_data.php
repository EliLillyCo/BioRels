<?php
 if (!defined("BIORELS")) header("Location:/");

 
$MODULE_DATA=array();
$ORDER=array();
$ORDER_DATE=false;
$IS_ID=true;
if (isset($USER_INPUT['PARAMS'])&&$USER_INPUT['PARAMS']!=array())
{
	for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
	{
		if ($USER_INPUT['PARAMS'][$I]=='RID')
		{			
			$ORDER=array_filter(explode("_",$USER_INPUT['PARAMS'][$I+1]));
		}	
		if ($USER_INPUT['PARAMS'][$I]=='IS_HASH')$IS_ID=false;
		if ($USER_INPUT['PARAMS'][$I]=='ORDER_DATE')$ORDER_DATE=true;
	}
}

$LIST=array();
if ($IS_ID)
{
	$LIST=$ORDER;
}
else 
{
	foreach ($ORDER as $K)$LIST[]="'".$K."'";
}


if (hasPrivateAccess())$MODULE_DATA['DATA']=private_loadBatchNewsData($LIST,false,$IS_ID);
else 
$MODULE_DATA['DATA']=loadBatchNewsData($LIST,false,$IS_ID);

if ($ORDER_DATE)
{
	$DATES=array();
	foreach ($MODULE_DATA['DATA'] as $HASH=>&$ENTRY)
	{
		$DATES[strtotime($ENTRY['RELEASE_DATE'])][]=$HASH;
	}

	krsort($DATES);
	$ORDER=array();
	foreach ($DATES as $D=>&$LIST)
	foreach ($LIST as $HASH)
	{
		$ORDER[]=$HASH;
	}
	
}

$MODULE_DATA['ORDER']=$ORDER;	






?>