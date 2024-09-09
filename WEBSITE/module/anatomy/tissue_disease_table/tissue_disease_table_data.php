<?php

if (!defined("BIORELS")) header("Location:/");



$res=getListTissues();
$TISSUE_ID=-1;
foreach ($res as $line)
{
if ($line['TISSUE_NAME']==$USER_INPUT['PORTAL']['VALUE'])$TISSUE_ID=$line['TISSUE_ID'];
}
$PER_PAGE=10;$PAGE=1;
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
		$FILTERS=array_filter(explode("/",$FILTER));
		
	}
}
$time=microtime_float();
// $FILTERS=array
// (
//    'trans', 'cell_surf','lipid_trans','gpcr_trans'
// );


$MODULE_DATA['STAT']=(getCountDiseaseForTissue($TISSUE_ID,$FILTERS));

$MODULE_DATA['TIME']['RULE']=round(microtime_float()-$time,2);
$time=microtime_float();
$MODULE_DATA['RESULTS']=getDiseaseForTissue($TISSUE_ID,array('MIN'=>($PAGE-1)*$PER_PAGE,'MAX'=>($PAGE)*$PER_PAGE),$FILTERS);
$MODULE_DATA['TIME']['RESULTS']=round(microtime_float()-$time,2);

?>