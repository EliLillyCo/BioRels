<?php

if (!defined("BIORELS")) header("Location:/");

if (isset($MODULE_DATA)){
    ksort($MODULE_DATA);
    $DL=array();$MISS=0;
    $PUBS=&$MODULE_DATA;
	if (isset($PUBS['1969']))
	{
		unset($PUBS['1969']);
	}
	if ($PUBS!=array())
	{
    $MIN=min(array_keys($PUBS));
    $MAX=max(array_keys($PUBS));
    for ($I=$MIN;$I<=$MAX;++$I)
    {
        if (isset($PUBS[$I]))
        $DL[]=array('name'=>$I,'value'=>$PUBS[$I]);
    else $DL[]=array('name'=>$I,'value'=>0);
    }
	}
    $USER_INPUT['PARAMS']=array('DATA',json_encode($DL));
    $STR=loadHTMLAndRemove("BARCHART");
    
    
    changeValue("gene_publi_graph",'pub_distrib',$STR);
    
  
    }

?>