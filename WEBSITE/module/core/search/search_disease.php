<?php


$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['DISEASE']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';
$SEARCH_TYPE='DISEASE_ANY';
$SOURCE='';
try{
	/*
	<option value="DISEASE_SEARCH/DISEASE_ANY">Any field</option>
							<option value="DISEASE_SEARCH/DISEASE_NAME">Disease Name</option>
							<option value="DISEASE_SEARCH/DISEASE_ID">Disease identifier</option> */
$LIST_TYPES=array('DISEASE_ANY','DISEASE_NAME','DISEASE_ID');
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		 if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}


if ($SEARCH_TYPE=='DISEASE_ANY'||$SEARCH_TYPE=='DISEASE_NAME'){


	$res=disease_portal_disease_name($SEARCH_VALUE);
	
	if (isset($res['DISEASE_TAG']))
	{
		$ENTRY=array('Disease Name'=>$res['DISEASE_NAME'],'Disease ID'=>$res['DISEASE_TAG'],
		
		'Synonyms'=>str_replace('|',' ; ',$res['SYN_V']),
	'Source'=>'Name');

$SEARCH_RESULTS['DISEASE'][$ENTRY['Disease ID']]=$ENTRY;
	}
	else 
	foreach ($res as $line){
		if (isset($SEARCH_RESULTS['DISEASE'][$line['DISEASE_TAG']]))continue;
	
		$ENTRY=array('Disease Name'=>$line['DISEASE_NAME'],
		'Disease ID'=>$line['DISEASE_TAG'],
						
						'Synonyms'=>str_replace('|',' ; ',$line['SYN_V']),
					'Source'=>'Name');

		$SEARCH_RESULTS['DISEASE'][$ENTRY['Disease ID']]=$ENTRY;
	
	}
	
	$result['TIME']['Name']=microtime_float()-$time;$time=microtime_float();

}

	
if ($SEARCH_TYPE=='DISEASE_ANY'||$SEARCH_TYPE=='DISEASE_ID'){


	$res=disease_portal_disease_tag($SEARCH_VALUE);
	
	foreach ($res as $line){
		if (isset($SEARCH_RESULTS['DISEASE'][$line['DISEASE_TAG']]))continue;
	$SOURCES=explode("|",$line['SOURCES']);
	$SOURCES=array_unique($SOURCES);
		$ENTRY=array('Disease Name'=>$line['DISEASE_NAME'],
				'Disease ID'=>$line['DISEASE_TAG'],
						
						'Synonyms'=>str_replace('|',' ; ',$line['SYN_V']),
						'External ID'=>implode(' ; ',$SOURCES),
					'Source'=>'ID');

		$SEARCH_RESULTS['DISEASE'][$line['DISEASE_TAG']]=$ENTRY;
	
	}
	
	$result['TIME']['Tag']=microtime_float()-$time;$time=microtime_float();

}

	
$result['count']=count($SEARCH_RESULTS['DISEASE']);
	if (count($SEARCH_RESULTS['DISEASE'])==1)
	{
		

		removeBlock("search_disease","MULTI");
		foreach ($SEARCH_RESULTS['DISEASE'] as $D_T=>$DI)
		changeValue("search_disease","ADDON",'/DISEASE/'.$D_T);
		
	}
	else
	{
		$RESULTS='';
		foreach ($SEARCH_RESULTS['DISEASE'] as $N=> $line)
		{
			if ($FIRST)
			{
				$FIRST=false;

				foreach ($line as $K=>$T)$RESULTS.='<th>'.$K.'</th>';
				$RESULTS.='</tr></thead>'."\n".'<tbody>';

			}
			$RESULTS.='<tr>';
			foreach ($line as $HEAD=>$V)
			{
				
				$pos=stripos($V,$SEARCH_VALUE);
				if ($pos!==false)
				{
					$T=substr($V,0,$pos).'<span class="w3-text-green">'.substr($V,$pos,strlen($SEARCH_VALUE)).'</span>'.substr($V,$pos+strlen($SEARCH_VALUE));
					$V=$T;
				}

				$RESULTS.='<td>';
				if (($HEAD=='Disease ID') && isset($line['Disease ID']))$RESULTS.='<a href="/DISEASE/'.$line['Disease ID'].'">'.$V.'</a>';
				else $RESULTS.=$V;
				$RESULTS.='</td>';
			}
			$RESULTS.='</tr>'."\n";
		}
		changeValue("search_disease","result",$RESULTS);
		removeBlock("search_disease","SINGLE");

		
	}
 removeBlock("search_disease","INVALID");
}
catch(Exception $e)
{
	removeBlock("search_disease","SINGLE");
	removeBlock("search_disease","MULTI");
}


cleanRules("search_disease");
if ($USER_INPUT['VTYPE']=='JSON'){
	if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
	echo json_encode($SEARCH_RESULTS['DISEASE']);
	exit;
}
 $result['code']=$HTML["search_disease"];

 if (ob_get_contents())ob_end_clean();
 	echo json_encode($result);
 	exit;



?>