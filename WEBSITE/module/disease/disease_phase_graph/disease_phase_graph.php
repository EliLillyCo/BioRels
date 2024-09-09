<?php

if (!defined("BIORELS")) header("Location:/");


if (isset($MODULE_DATA['CLINICAL_STAT']['PHASES'])){
    ksort($MODULE_DATA['CLINICAL_STAT']['PHASES']);
    $DL=array();$MISS=0;
    foreach ($MODULE_DATA['CLINICAL_STAT']['PHASES'] as $Y=>$V)
    {
        if ($Y=='-1'||$Y==''){$MISS+=$V;continue;}
        $DL[]=array('name'=>$Y,'value'=>$V);
    }
    
    $USER_INPUT['PARAMS']=array('DATA',json_encode($DL));
    $STR=loadHTMLAndRemove("BARCHART");
   
    
    changeValue("disease_phase_graph",'ct_phase',$STR);
    
    changeValue("disease_phase_graph",'MISS_PHASE',$MISS.' clinical trials without Clinical phase');
    }
    ?>