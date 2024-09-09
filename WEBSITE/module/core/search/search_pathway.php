<?php
$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['PATHWAY']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';

$SOURCE='';
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
if ($USER_INPUT['PARAMS'][0]=='ALL')$SP='ALL';
else if ($USER_INPUT['PARAMS'][0]=='HUMAN')$SP='HUMAN';
}

if (is_numeric($SEARCH_VALUE)) throw new Exception("Only text allowed",ERR_TGT_SYS);

	
	$res=pathway_portal_pwname($SEARCH_VALUE);
	
	foreach ($res as $line)
	{
	if (isset($SEARCH_RESULTS['PATHWAY'][$line['REAC_ID']]))continue;
	
		$ENTRY=array('Reactome ID'=>$line['REAC_ID'],
						'Pathway Name'=>$line['PW_NAME'],
						'Organism'=>$line['SCIENTIFIC_NAME']);
						if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
		$SEARCH_RESULTS['PATHWAY'][$line['REAC_ID']]=$ENTRY;
		$SOURCE='ID';
	} 
	$result['TIME']['reacid']=microtime_float()-$time;$time=microtime_float();

$RESULTS='';
foreach ($SEARCH_RESULTS['PATHWAY'] as $N=> $line)
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
			$T=substr($V,0,$pos).'<span class="gree_c">'.substr($V,$pos,strlen($SEARCH_VALUE)).'</span>'.substr($V,$pos+strlen($SEARCH_VALUE));
			$V=$T;
		}

		$RESULTS.='<td>';
		if ($HEAD=='Reactome ID' )$RESULTS.='<a href="/PATHWAY/'.$line['Reactome ID'].'">'.$V.'</a>';
		else $RESULTS.=$V;
		$RESULTS.='</td>';
	}
	$RESULTS.='</tr>'."\n";
}
if (count($SEARCH_RESULTS['PATHWAY'])==1)
{
removeBlock("search_pathway","MULTI");
changeValue("search_pathway","REAC_ID",array_keys($SEARCH_RESULTS['PATHWAY'])[0]);


}
else
{
changeValue("search_pathway","result",$RESULTS);
removeBlock("search_pathway","SINGLE");
$result['count']=count($SEARCH_RESULTS['PATHWAY']);
}
cleanRules($TAG);
if ($USER_INPUT['VTYPE']=='JSON'){echo json_encode($SEARCH_RESULTS['PATHWAY']);exit;}
$result['code']=$HTML["search_pathway"];

	echo json_encode($result);
	exit;
?>