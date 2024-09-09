<?php


$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['CLINICAL']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';


$SEARCH_TYPE='CLINICAL_ANY';
$SOURCE='';
 try{
$LIST_TYPES=array('CLINICAL_ANY','CLINICAL_ID','CLINICAL_TITLE');

if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		 if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}

if ($SEARCH_TYPE=='CLINICAL_ANY'||$SEARCH_TYPE=='CLINICAL_ID'){

	
	$res=getClinicalTrialById($SEARCH_VALUE);
	
	foreach ($res as $line)
	{
		$prim_name='';$alias=array();
		foreach ($line as &$v)if ($v['ALIAS_TYPE']=='Primary')$prim_name=$v['ALIAS_NAME'];
		else $alias[$v['ALIAS_NAME']]=$v['ALIAS_TYPE'];
		$ENTRY=array('trial_id'=>$prim_name ,'Alias'=>$alias,'Title'=>$line[0]['OFFICIAL_TITLE'],
		 'clinical_phase'=>$line[0]['CLINICAL_PHASE'],
		 'clinical_status'=>$line[0]['CLINICAL_STATUS'],
		 'start_date'=>$line[0]['START_DATE']);					
		$SEARCH_RESULTS['CLINICAL'][$prim_name]=$ENTRY;
			
	}
	
	$result['TIME']['CLINICAL_ID']=microtime_float()-$time;$time=microtime_float();

}

if ($SEARCH_TYPE=='CLINICAL_ANY'||$SEARCH_TYPE=='CLINICAL_TITLE'){

	
	$res=getClinicalTrialByName($SEARCH_VALUE);
	
	foreach ($res as $line)
	{
		$prim_name='';$alias=array();
		foreach ($line as &$v)if ($v['ALIAS_TYPE']=='Primary')$prim_name=$v['ALIAS_NAME'];
		else $alias[$v['ALIAS_NAME']]=$v['ALIAS_TYPE'];
		$ENTRY=array('trial_id'=>$prim_name ,'Alias'=>$alias,'Title'=>$line[0]['OFFICIAL_TITLE'],
		 'clinical_phase'=>$line[0]['CLINICAL_PHASE'],
		 'clinical_status'=>$line[0]['CLINICAL_STATUS'],
		 'start_date'=>$line[0]['START_DATE']);					
		$SEARCH_RESULTS['CLINICAL'][$prim_name]=$ENTRY;
			
	}
	
	$result['TIME']['CLINICAL_ID']=microtime_float()-$time;$time=microtime_float();

}



$RESULTS='';
$MAP=array('trial_id'=>'Identifier','Alias'=>'Alias','Title'=>'Official title','clinical_phase'=>'Clinical Phase','clinical_status'=>'Status','start_date'=>'Start date');
foreach ($SEARCH_RESULTS['CLINICAL'] as $N=> $line)
{
	if ($FIRST)
	{
		$FIRST=false;

		foreach ($line as $K=>$T)$RESULTS.='<th>'.$MAP[$K].'</th>';
		$RESULTS.='</tr></thead>'."\n".'<tbody>';

	}
	$RESULTS.='<tr>';
	foreach ($line as $HEAD=>$V)
	{
		
		

		$RESULTS.='<td>';
		if (($HEAD=='trial_id' || $HEAD=='Title'))$RESULTS.='<a href="/CLINICAL_TRIAL/'.$line['trial_id'].'">'.$V.'</a>';
		else if ($HEAD=='Alias')
		{
			foreach ($V as $t=>&$dummy)$RESULTS.=$t.' <br/>';
		}
		//else 
		else $RESULTS.=$V;
		$RESULTS.='</td>';
	}
	$RESULTS.='</tr>'."\n";
}


if (count($SEARCH_RESULTS['CLINICAL'])==1)
{
	removeBlock("search_clinical","MULTI");
		foreach ($SEARCH_RESULTS['CLINICAL'] as $K)
	changeValue("search_clinical","ADDON",'/CLINICAL_TRIAL/'.$K['trial_id']);
	

}
else
{
	changeValue("search_clinical","result",$RESULTS);
		changeValue("search_clinical","COUNT",count($SEARCH_RESULTS['CLINICAL']));
		changeValue("search_clinical","NPAGE",ceil(count($SEARCH_RESULTS['CLINICAL'])/10));
		changeValue("search_clinical","QUERY_NAME",$SEARCH_VALUE);
		

		$result['count']=count($SEARCH_RESULTS['CLINICAL']);
removeBlock("search_clinical","SINGLE");


}
removeBlock("search_clinical","INVALID");
}
catch(Exception $e)
{
	removeBlock("search_clinical","SINGLE");
	removeBlock("search_clinical","MULTI");
}


cleanRules("search_clinical");

if ($USER_INPUT['VTYPE']=='JSON'){
	if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
	echo json_encode($SEARCH_RESULTS['CLINICAL']);exit;
}
$result['code']=$HTML["search_clinical"];

if (ob_get_contents())ob_end_clean();
	echo json_encode($result);
	exit;

?>