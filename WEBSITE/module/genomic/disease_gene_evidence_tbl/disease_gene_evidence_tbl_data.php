<?php
// $USER_INPUT['PARAMS']=array( 
// 	0=> 'DISEASE',
// 	1 => 'acute myeloid leukemia',
// 	2 => 'GENE',
// 	3 => '2322',
// 	4 => 'PER_PAGE',
// 	5 => '10',
// 	6 => 'PAGE',
// 	7 => '1');
	
$PER_PAGE=-1;$PAGE=-1;$GENE=-1;$DISEASE='';
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
		if 	($USER_INPUT['PARAMS'][$I]=='GENE')
		{
			if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for GENE",ERR_TGT_USR);
			$GENE=$USER_INPUT['PARAMS'][$I+1];
			if (!is_numeric($GENE))throw new Exception("Expected numeric value  for GENE",ERR_TGT_USR);
			$I+=1;
		}
		if 	($USER_INPUT['PARAMS'][$I]=='DISEASE')
		{
			if ($I+1==count($USER_INPUT['PARAMS']))throw new Exception("No value provided for DISEASE",ERR_TGT_USR);
			$DISEASE=$USER_INPUT['PARAMS'][$I+1];
			$I+=1;
		}
	}

	$MODULE_DATA['DISEASE']=getDiseaseEntry($DISEASE,true,true);
	$MODULE_DATA['GENE']=gene_portal_geneID($GENE);

	if ($MODULE_DATA['GENE']!=array()&& $MODULE_DATA['DISEASE']!=array())
	{
		$MODULE_DATA['RESULTS']=getPubliFromDiseaseGene($MODULE_DATA['DISEASE']['DISEASE_ENTRY_ID'],$MODULE_DATA['GENE']['GN_ENTRY_ID'],array('MIN'=>($PAGE-1)*$PER_PAGE,'MAX'=>($PAGE)*$PER_PAGE));
//echo '<pre>';print_R($MODULE_DATA);
		$LIST=array();
		foreach ($MODULE_DATA['RESULTS'] as &$R)$LIST[]=$R['PMID'];
		$TMP=loadBatchPublicationData($LIST);
		$MAP=array();foreach ($TMP as $K=>&$V)$MAP[$V['ENTRY']['PMID']]=$K;
		foreach ($MODULE_DATA['RESULTS'] as &$R)
		{
			$R['PUBLI']=$TMP[$MAP[$R['PMID']]];
		}
		unset($TMP);
//		exit;
	}else if ($MODULE_DATA['GENE']==array())$MODULE_DATA['ERROR']='Unrecognized gene';
else if ($MODULE_DATA['DISEASE']==array())$MODULE_DATA['ERROR']='Unrecognized disease';
	
	
	
	?>