<?php

if (!defined("BIORELS")) header("Location:/");


if (isset($MODULE_DATA['CLINICAL_STAT']['YEAR'])){
	ksort($MODULE_DATA['CLINICAL_STAT']['YEAR']);
	$DL=array();$MISS=0;
	foreach ($MODULE_DATA['CLINICAL_STAT']['YEAR'] as $Y=>$V)
	{
		if ($Y=='-1'||$Y==''){$MISS+=$V;continue;}
		$DL[]=array('name'=>$Y,'value'=>$V);
	}
	
	$USER_INPUT['PARAMS']=array('DATA',json_encode($DL),'PARENT','ct_distrib');
	$STR=loadHTMLAndRemove("BARCHART");
	
	changeValue("disease_trial_graph",'ct_distrib',$STR);
	
	changeValue("disease_trial_graph",'MISS',$MISS.' clinical trials without year information');
	}
    ?>