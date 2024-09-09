<?php

if ($MODULE_DATA['CLINICAL_STAT']==array())
{
	removeBlock("drug_clintrial_year","HAS_DATA");
	return;
}else removeBlock("drug_clintrial_year","NODATA");

$ID=substr(md5(microtime_float()),0,7);
changeValue("drug_clintrial_year",'ID',$ID);
$DATA=array();
$COLS=array('ARM_TYPE'=>'Arm type','CLINICAL_PHASE'=>'Clinical phase','CLINICAL_STATUS'=>'Clinical status');
$LIST_COLS=array('ARM_TYPE'=>array(),'CLINICAL_PHASE'=>array(),'CLINICAL_STATUS'=>array());
foreach ($MODULE_DATA['CLINICAL_STAT'] as &$ENTRY)
{
	$LIST_COLS['ARM_TYPE'][$ENTRY['ARM_TYPE']]=true;
	$LIST_COLS['CLINICAL_PHASE'][$ENTRY['CLINICAL_PHASE']]=true;
	$LIST_COLS['CLINICAL_STATUS'][$ENTRY['CLINICAL_STATUS']]=true;
	foreach ($COLS as $C=>&$N)
		if (!isset($DATA[$ENTRY['DATE_PART']][$C][$ENTRY[$C]]))
			$DATA[$ENTRY['DATE_PART']][$C][$ENTRY[$C]]=$ENTRY['CO'];
		else $DATA[$ENTRY['DATE_PART']][$C][$ENTRY[$C]]+=$ENTRY['CO'];
}
// echo '<pre>';
// print_R($LIST_COLS);
// print_R($DATA);exit;
ksort ($LIST_COLS['ARM_TYPE']);
ksort($LIST_COLS['CLINICAL_PHASE']);
ksort($LIST_COLS['CLINICAL_STATUS']);
ksort($DATA);
$MIN=min(array_keys($DATA));
$MAX=min(array_keys($DATA));
for ($Y=$MIN;$Y<=$MAX;++$Y)
{
	if (isset($DATA[$Y]))continue;
	$DATA[$Y]=array();
	foreach ($COLS as $C=>&$N)
		$DATA[$Y][$C]=array();
}
foreach ($COLS as $C=>$N)
{
	$STR='group';
	foreach ($LIST_COLS[$C] as $AR=>$DUMMY)
	{
		if ($AR=='N/A')$STR.=',N/A';
		else $STR.=','.str_replace("_"," ",str_replace(","," ",ucfirst(strtolower($AR))));
	}
	$STR.="\n";
	foreach ($DATA as $Y=>$D)
	{
		$STR.=$Y;
		
		foreach ($LIST_COLS[$C] as $AR=>$DUMMY)
		{
			if (isset($D[$C][$AR]))$STR.=','.$D[$C][$AR];
			else $STR.=',0';
		}
		$STR.="\n";
	}
	
	$USER_INPUT['PARAMS']=array('DATA',$STR,'PARENT','dr_'.$ID.'_arm');
	$GR=loadHTMLAndRemove("STACKED_BARCHART");
	changeValue("drug_clintrial_year",$C,$GR);
}

?>