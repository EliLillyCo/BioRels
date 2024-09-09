<?php

$RULE_TYPE=$USER_INPUT['PAGE']['VALUE'];
$FILTERS=array('YEAR'=>date('Y'),'SHIFT'=>0);
// $USER_INPUT['PARAMS']=array
// (
//     'PER_PAGE','10','PAGE','1','FILTERS','topic-Pancreas;topic-Delivery;'//gene-1017'
// );
if (!preg_match("/[A-Za-z-_]{1,100}/",$RULE_TYPE))throw new Exception("Wrong format for publication topic ".$RULE_TYPE,ERR_TGT_USR);
for($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
{
	if 	($USER_INPUT['PARAMS'][$I]=='PER_PAGE')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for PER_PAGE",ERR_TGT_USR);
		$PER_PAGE=$USER_INPUT['PARAMS'][$I+1];
		if (!is_numeric($PER_PAGE))throw new Exception("Expected numeric value  for PER_PAGE",ERR_TGT_USR);
		$I+=1;
	}
	if 	($USER_INPUT['PARAMS'][$I]=='PAGE')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for PAGE",ERR_TGT_USR);
		$PAGE=$USER_INPUT['PARAMS'][$I+1];
		if (!is_numeric($PAGE))throw new Exception("Expected numeric value  for PAGE",ERR_TGT_USR);
		$I+=1;
	}
	if 	($USER_INPUT['PARAMS'][$I]=='SHIFT')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for SHIFT",ERR_TGT_USR);
		$FILTERS['SHIFT']=$USER_INPUT['PARAMS'][$I+1];
		
		$I+=1;
	}
	if 	($USER_INPUT['PARAMS'][$I]=='DATE')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for YEAR",ERR_TGT_USR);
		$FILTERS['DATE']=$USER_INPUT['PARAMS'][$I+1];
		
		$I+=1;
	}
	if 	($USER_INPUT['PARAMS'][$I]=='FILTERS')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for FILTERS",ERR_TGT_USR);
		$FILTER=$USER_INPUT['PARAMS'][$I+1];
		$I+=1;
		$tabR=array_filter(explode(";",$FILTER));
		foreach ($tabR as $value)
		{
			
			$tab=explode("-",$value);
			
			if (count($tab)==1){exit;throw new Exception("Missing value for FILTERS",ERR_TGT_USR);}
			if ($tab[0]!='gene' && $tab[0]!='topic')throw new Exception("Wrong format for FILTERS",ERR_TGT_USR);
			$FILTERS[$tab[0]][]=$tab[1];
		}
	}
}
$time=microtime_float();

$MODULE_DATA['QUERY']=getPubliRule($RULE_TYPE);

$MODULE_DATA['STAT']=getCountPubliRule($MODULE_DATA['QUERY'][0]['PUBLI_RULE_ID'],$FILTERS);

$MODULE_DATA['TIME']['RULE']=round(microtime_float()-$time,2);
$time=microtime_float();
$MODULE_DATA['RESULTS']=getPubliFromRule($MODULE_DATA['QUERY'][0]['PUBLI_RULE_ID'],array('PER_PAGE'=>$PER_PAGE),$FILTERS);

$MODULE_DATA['TIME']['RESULTS']=round(microtime_float()-$time,2);

?>