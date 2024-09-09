<?php

if ($MODULE_DATA['CLINICAL_STAT']['ITSELF']==array()&&$MODULE_DATA['CLINICAL_STAT']['ALL']==array())
{
	removeBlock("disease_clinphase","HAS_DATA");
	return;
}else removeBlock("disease_clinphase","NODATA");
//print_r($MODULE_DATA['CLINICAL_STAT']);exit;
$ID=substr(md5(microtime_float()),0,7);
changeValue("disease_clinphase",'ID',$ID);


if ($MODULE_DATA['CLINICAL_STAT']['ITSELF']==array())
{
	removeBlock("disease_clinphase","HAS_SELF");
	changeValue("disease_clinphase","HAS_ALL_SHOW","block");
}else
{
	 changeValue("disease_clinphase","HAS_SELF_SHOW","block");
	 changeValue("disease_clinphase","HAS_ALL_SHOW","none");
}

if ($MODULE_DATA['CLINICAL_STAT']['ALL']==array())
{
removeBlock("disease_clinphase","HAS_ALL");
}
	


foreach ($MODULE_DATA['CLINICAL_STAT'] as $CS_DATA_TYPE=>&$CS_DATA)
{
	if ($CS_DATA==array())
	{
		continue;
	
	}
	$DATA=array();
	$COLS=array('DATE_PART'=>'Year','CLINICAL_STATUS'=>'Clinical status');
	$LIST_COLS=array('YEAR'=>array(),'CLINICAL_PHASE'=>array(),'CLINICAL_STATUS'=>array());
	print_R($CS_DATA);
	foreach ($CS_DATA as &$ENTRY)
	{
		//print_R($ENTRY);
		$ENTRY['DATE_PART']=floor($ENTRY['DATE_PART']/5)*5;
		$LIST_COLS['DATE_PART'][$ENTRY['DATE_PART']]=true;
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
	
	ksort ($LIST_COLS['DATE_PART']);
	ksort($LIST_COLS['CLINICAL_STATUS']);
	ksort($DATA);

	foreach ($COLS as $C=>$N)
	{
		//echo "###".$C."\n";
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
		$NAME=$C.(($CS_DATA_TYPE=='ALL')?'_ALL':'');
		//echo $CS_DATA_TYPE.' '.$NAME."\n";
		changeValue("disease_clinphase",$NAME,$GR);
	}
}
//exit;

?>