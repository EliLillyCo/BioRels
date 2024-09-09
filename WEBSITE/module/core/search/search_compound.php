<?php


$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['COMPOUND']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';


$SEARCH_TYPE='COMPOUND_ANY';
$SOURCE='';
 try{
$LIST_TYPES=array('COMPOUND_ANY','COMPOUND_NAME','COMPOUND_SMILES','COMPOUND_INCHI','COMPOUND_ID');
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		 if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}

if ($SEARCH_TYPE=='COMPOUND_ANY'||$SEARCH_TYPE=='COMPOUND_NAME'){

	
	$res=getCompoundInfo($SEARCH_VALUE);
	foreach ($res as $line)
	{
		if (!isset($SEARCH_RESULTS['COMPOUND'][$line['INI']['SM_ENTRY_ID']])){

			$ENTRY=array('Compound Name'=>(isset($line['INI']['SM_NAME'])?$line['INI']['SM_NAME']:(isset($line['INI']['DRUG_NAME'])?$line['INI']['DRUG_NAME']:'N/A')),
			'is_drug'=>(isset($line['INI']['DRUG_NAME'])),
							'Structure'=>$line['STRUCTURE']['SMILES'],
							'Valid structure'=>$line['STRUCTURE']['IS_VALID'],
							
						'Source'=>'Name');
							
			$SEARCH_RESULTS['COMPOUND'][$line['INI']['SM_ENTRY_ID']]=$ENTRY;
			
		}
	}
	$result['TIME']['NAME']=microtime_float()-$time;$time=microtime_float();

}

if ($SEARCH_TYPE=='COMPOUND_ANY'||$SEARCH_TYPE=='COMPOUND_INCHI'){

	
	$res=getCompoundInchi($SEARCH_VALUE);
	foreach ($res as $line)
	{
		if (!isset($SEARCH_RESULTS['COMPOUND'][$line['INI']['SM_ENTRY_ID']])){

			$ENTRY=array('Compound Name'=>(isset($line['INI']['SM_NAME'])?$line['INI']['SM_NAME']:(isset($line['INI']['DRUG_NAME'])?$line['INI']['DRUG_NAME']:'N/A')),
			'is_drug'=>(isset($line['INI']['DRUG_NAME'])),
							'Structure'=>$line['STRUCTURE']['SMILES'],
							'Valid structure'=>$line['STRUCTURE']['IS_VALID'],
							
						'Source'=>'Name');
							
			$SEARCH_RESULTS['COMPOUND'][$line['INI']['SM_ENTRY_ID']]=$ENTRY;
			
		}
	}
	$result['TIME']['NAME']=microtime_float()-$time;$time=microtime_float();

}

if (count($SEARCH_RESULTS['COMPOUND'])==1)
{
	removeBlock("search_compound","MULTI");
	changeValue("search_compound","COUNT",1);
		foreach ($SEARCH_RESULTS['COMPOUND'] as $K)
		{
	if ($K['is_drug'])changeValue("search_compound","ADDON",'/DRUG/'.$K['Compound Name']);
	else changeValue("search_compound","ADDON",'/COMPOUND/'.$K['Compound Name']); 
		}
	

}
else
{
//changeValue("search_compound","result",$RESULTS);
changeValue("search_compound","result",str_replace("\\","\\\\",json_encode(array_values($SEARCH_RESULTS['COMPOUND']))));
		changeValue("search_compound","COUNT",count($SEARCH_RESULTS['COMPOUND']));
		changeValue("search_compound","NPAGE",ceil(count($SEARCH_RESULTS['COMPOUND'])/10));
		changeValue("search_compound","QUERY_NAME",$SEARCH_VALUE);
		removeBlock("search_compound","SINGLE");

		$result['count']=count($SEARCH_RESULTS['COMPOUND']);
removeBlock("search_compound","SINGLE");

$result['count']=count($SEARCH_RESULTS['COMPOUND']);
}
removeBlock("search_compound","INVALID");
}
catch(Exception $e)
{
	removeBlock("search_compound","SINGLE");
	removeBlock("search_compound","MULTI");
}


cleanRules("search_compound");
if ($USER_INPUT['VTYPE']=='JSON'){
	if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
	echo json_encode($SEARCH_RESULTS['COMPOUND']);
	exit;
}
$result['code']=$HTML["search_compound"];

if (ob_get_contents())ob_end_clean();
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

	echo json_encode($result);
	exit;
