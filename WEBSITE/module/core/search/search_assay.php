<?php
ini_set('memory_limit','1000M');

$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['ASSAY']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';

$SEARCH_TYPE='ASSAY_ANY';
$SOURCE='';
try{
$LIST_TYPES=array('ASSAY_ANY','ASSAY_NAME');
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		if ($VAL=='ALL')$SP='ALL';
		else if ($VAL=='HUMAN')$SP='HUMAN';
		else if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}


if ($SEARCH_TYPE=='ASSAY_ANY'||$SEARCH_TYPE=='ASSAY_NAME'){

	$res=array();
	
	//
	$res=assay_portal_name($SEARCH_VALUE);
	
	foreach ($res as $line)
	{
		
	if (isset($SEARCH_RESULTS['ASSAY'][$line['ASSAY_NAME']]))continue;

		$ENTRY=array('Assay Name'=>$line['ASSAY_NAME'],
						'Description'=>$line['ASSAY_DESCRIPTION'],
						'Category'=>$line['ASSAY_CATEGORY'],
						'Type'=>$line['ASSAY_TYPE'],
						'Cell Name'=>$line['ASSAY_CELL_NAME'],
						'Organism'=>$line['SCIENTIFIC_NAME'],
					'Source'=>$line['SOURCE_NAME']);
					if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
		
		$SEARCH_RESULTS['ASSAY'][$line['ASSAY_NAME']]=$ENTRY;
		
	}

	$result['TIME']['ASSAY NAME']=microtime_float()-$time;$time=microtime_float();

}


$RESULTS='';
foreach ($SEARCH_RESULTS['ASSAY'] as $N=> $line)
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
		if (($HEAD=='Assay Name' ) && isset($line['Assay Name']))$RESULTS.='<a href="/ASSAY/'.$line['Assay Name'].'">'.$V.'</a>';
		else $RESULTS.=$V;
		$RESULTS.='</td>';
	}
	$RESULTS.='</tr>'."\n";
}
if (count($SEARCH_RESULTS['ASSAY'])==1)
{
	removeBlock("search_assay","MULTI");
	
	$ASSAY_ID=array_keys($SEARCH_RESULTS['ASSAY'])[0]['ASSAY_NAME'];
	$SOURCE=$SEARCH_RESULTS['ASSAY'][$ASSAY_ID]['Source'];

	changeValue("search_assay","ADDON",'/ASSAY/'.$ASSAY_ID);
	

}
else
{
changeValue("search_assay","result",$RESULTS);
removeBlock("search_assay","SINGLE");

$result['count']=count($SEARCH_RESULTS['ASSAY']);
}
removeBlock("search_assay","INVALID");
}
catch(Exception $e)
{
	removeBlock("search_assay","SINGLE");
	removeBlock("search_assay","MULTI");
}


cleanRules("search_assay");
if ($USER_INPUT['VTYPE']=='JSON'){
	if (ob_get_contents())ob_end_clean();
	echo json_encode($SEARCH_RESULTS['ASSAY']);exit;}
$result['code']=$HTML["search_assay"];

if (ob_get_contents())ob_end_clean();
	echo json_encode($result);
	exit;
?>