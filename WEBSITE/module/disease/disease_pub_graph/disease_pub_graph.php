<?php

if (!defined("BIORELS")) header("Location:/");

$DATA=array();
foreach ($MODULE_DATA['ALL']['PUB_DATE'] as $Y=>$C)    $DATA[$Y]['ALL']=$C;
foreach ($MODULE_DATA['SELF']['PUB_DATE'] as $Y=>$C)    $DATA[$Y]['SELF']=$C;

if (count($DATA)==0)
{
    removeBlock("disease_pub_graph","HAS_DATA");
    return;
}
ksort($DATA);
if (isset($DATA['1969']))unset($DATA['1969']);
//echo '<pre>';print_R($DATA);exit;
$STR='group,Self,With sub-diseases'."\n";
	
	foreach ($DATA as $Y=>$D)
	{
		$STR.=$Y;
		if (isset($D['SELF']))$STR.=','.$D['SELF'];else $STR.=','.'0';
        if (isset($D['ALL']))
        {
            $STR.=',';
            if (isset($D['SELF']))$STR.=$D['ALL']-$D['SELF'];else $STR.=$D['ALL'];
        }else $STR.=',0';
		
		$STR.="\n";
	}
	
	$USER_INPUT['PARAMS']=array('DATA',$STR,'PARENT','ds_pub_graph');
	$GR=loadHTMLAndRemove("STACKED_BARCHART");
	changeValue("disease_pub_graph",'ct_pub',$GR);

    ?>