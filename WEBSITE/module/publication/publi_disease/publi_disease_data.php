<?php

if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit','1000M');

$MODULE_DATA=array();

if ($USER_INPUT['PORTAL']['NAME']=='DISEASE')
{
$MODULE_DATA['INFO']=getDiseaseEntry($USER_INPUT['PORTAL']['VALUE'],true,true);

$MODULE_DATA['STAT']=getCountPubliDisease($MODULE_DATA['INFO']['DISEASE_ENTRY_ID']);
}
else
{
	for($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
	{
		if 	($USER_INPUT['PARAMS'][$I]=='DISEASE')
		{
			if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for DISEASE",ERR_TGT_USR);
			$DISEASE=$USER_INPUT['PARAMS'][$I+1];
			
			$MODULE_DATA['INFO']=getDiseaseEntry($DISEASE,true,true);
			$MODULE_DATA['STAT']=getCountPubliDisease($MODULE_DATA['INFO']['DISEASE_ENTRY_ID']);

			$I+=1;
		}
	}
}

?>