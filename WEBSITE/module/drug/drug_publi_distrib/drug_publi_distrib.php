<?php

if (!defined("BIORELS")) header("Location:/");
if (isset($MODULE_DATA['PUBLICATION'])){
    ksort($MODULE_DATA['PUBLICATION']);
    $DL=array();$MISS=0;
    $PUBS=&$MODULE_DATA['PUBLICATION'];
	if (isset($PUBS['1969']))unset($PUBS['1969']);
    $MIN=min(array_keys($PUBS));
    $MAX=max(array_keys($PUBS));
    for ($I=$MIN;$I<=$MAX;++$I)
    {
        if (isset($PUBS[$I]))
        $DL[]=array('name'=>$I,'value'=>$PUBS[$I]);
    else $DL[]=array('name'=>$I,'value'=>0);
    }
    
    $USER_INPUT['PARAMS']=array('DATA',json_encode($DL));
    $STR=loadHTMLAndRemove("BARCHART");
    
    
    changeValue("drug_publi_distrib",'distrib',$STR);
    
  
    }

?>