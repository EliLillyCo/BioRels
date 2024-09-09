<?php


if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit','1000M');

$FILTERS=array();
for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
{
	if ($USER_INPUT['PARAMS'][$I]=='SOURCE')
	$FILTERS['source'][]=$USER_INPUT['PARAMS'][$I+1];
	if ($USER_INPUT['PARAMS'][$I]=='CLINICAL')
	$FILTERS['clinical'][]=$USER_INPUT['PARAMS'][$I+1];
}




 if (hasPrivateAccess())$MODULE_DATA['STAT']=private_getCountPubliNews($FILTERS);
 else 
$MODULE_DATA['STAT']=getCountPubliNews($FILTERS);




?>