<?php

if ($MODULE_DATA['CLINICAL_STAT']==array())
{
	removeBlock("drug_clinphase","HAS_DATA");
	return;
}else removeBlock("drug_clinphase","NODATA");

$ID=substr(md5(microtime_float()),0,7);
changeValue("drug_clinphase",'ID',$ID);
$DATA=array();
$COLS=array('DATE_PART'=>'Year','ARM_TYPE'=>'Arm type','CLINICAL_STATUS'=>'Clinical status');
$LIST_COLS=array('YEAR'=>array(),'CLINICAL_PHASE'=>array(),'CLINICAL_STATUS'=>array());
foreach ($MODULE_DATA['CLINICAL_STAT'] as &$ENTRY)
{
	$ENTRY['DATE_PART']=floor($ENTRY['DATE_PART']/5)*5;
	$LIST_COLS['DATE_PART'][$ENTRY['DATE_PART']]=true;
	$LIST_COLS['ARM_TYPE'][$ENTRY['ARM_TYPE']]=true;
	$LIST_COLS['CLINICAL_STATUS'][$ENTRY['CLINICAL_STATUS']]=true;
	foreach ($COLS as $C=>&$N)
	{
		$V=$ENTRY[$C];
		if ($C=='DATE_PART')$V=floor($V/5)*5;
		if (!isset($DATA[$ENTRY['CLINICAL_PHASE']][$C][$V]))
			$DATA[$ENTRY['CLINICAL_PHASE']][$C][$V]=$ENTRY['CO'];
		else $DATA[$ENTRY['CLINICAL_PHASE']][$C][$V]+=$ENTRY['CO'];
	}
}
 echo '<pre>';
// print_R($LIST_COLS);
 
ksort ($LIST_COLS['DATE_PART']);


ksort($LIST_COLS['ARM_TYPE']);
ksort($LIST_COLS['CLINICAL_STATUS']);
ksort($DATA);

foreach ($COLS as $C=>$N)
{
	$STR='group';
	foreach ($LIST_COLS[$C] as $AR=>$DUMMY)
	{
		if ($AR=='N/A')$STR.=',N/A';
		else if ($C=='DATE_PART')$STR.=','.$AR.'-'.($AR+5);
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
//	echo $STR."\n\n";
	$USER_INPUT['PARAMS']=array('DATA',$STR,'PARENT','dr_'.$ID.'_arm');
	$GR=loadHTMLAndRemove("STACKED_BARCHART");
	changeValue("drug_clinphase",$C,$GR);
}

//exit;

//exit;
// isset($MODULE_DATA['CLINICAL_STAT']['YEAR'])){
// 	ksort($MODULE_DATA['CLINICAL_STAT']['YEAR']);
// 	$DL=array();$MISS=0;
// 	foreach ($MODULE_DATA['CLINICAL_STAT']['YEAR'] as $Y=>$V)
// 	{
// 		if ($Y=='-1'||$Y==''){$MISS+=$V;continue;}
// 		$DL[]=array('name'=>$Y,'value'=>$V);
// 	}
	
// 	
	
	
// 	changeValue("drug_clinphase",'ct_distrib',$STR);
// 	changeValue("drug_clinphase",'MISS',$MISS.' clinical trials without year information');

// }
?>