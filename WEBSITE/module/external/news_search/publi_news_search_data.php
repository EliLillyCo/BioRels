<?php


if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit','1000M');

$RECENT_PARAM = FALSE;
$FILTERS=array();
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
	if 	($USER_INPUT['PARAMS'][$I]=='FILTERS')
	{
		if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for FILTERS",ERR_TGT_USR);
		$FILTER=$USER_INPUT['PARAMS'][$I+1];		
		$I+=1;
		// 'gene-1585;gene-1585'
		$tabR=array_filter(explode(";",$FILTER));
		foreach ($tabR as $value)
		{
			
			// eg gene-4557
			
			$pos=strpos($value,'-');
			
			if ($pos===false){exit;throw new Exception("Missing value for FILTERS",ERR_TGT_USR);}
			// if ($tab[0]!='gene' && $tab[0]!='topic' && $tab[0]!='disease')throw new Exception("Wrong format for FILTERS",ERR_TGT_USR);
			// FILTERS['gene][]= 4557; -- 
			$FILTERS[substr($value,0,$pos)][]=substr($value,$pos+1);
		}
	}
	if 	($USER_INPUT['PARAMS'][$I]=='RECENT'){
		$RECENT_PARAM = $USER_INPUT['PARAMS'][$I];
	}

	
}

$time=microtime_float();


if (hasPrivateAccess())$MODULE_DATA['STAT']=private_getCountPubliNews($FILTERS); 
else 
$MODULE_DATA['STAT']=getCountPubliNews($FILTERS); 

$MODULE_DATA['RESULTS']=array();
if ($MODULE_DATA['STAT']['CO']!=0){

	$MODULE_DATA['STAT']['MIN_PAGE']=($PAGE-1)*$PER_PAGE;
	$MODULE_DATA['STAT']['MAX_PAGE']=($PAGE)*$PER_PAGE;
$time=microtime_float();
$PARAMS_ARRAY= array('MIN'=>($PAGE-1)*$PER_PAGE,'MAX'=>($PAGE)*$PER_PAGE);
if ($RECENT_PARAM){
	$PARAMS_ARRAY['RECENT'] = $RECENT_PARAM;
}

if (hasPrivateAccess())$MODULE_DATA['RESULTS']=private_getPubliFromNews($PARAMS_ARRAY,$FILTERS);
else 
$MODULE_DATA['RESULTS']=getPubliFromNews($PARAMS_ARRAY,$FILTERS);
}

$MODULE_DATA['TIME']['RESULTS']=round(microtime_float()-$time,2);

?>