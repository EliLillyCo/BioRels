<?php
$SEARCH_VALUE=htmlentities(strip_tags( trim($USER_INPUT['PAGE']['VALUE'])));
$FIRST=true;

$SEARCH_RESULTS['TISSUE']=array();
$time=microtime_float();
$result['TIME']=array();


/*
	<option value="TISSUE_SEARCH/TISSUE_ANY">Any field</option>
							<option value="TISSUE_SEARCH/TISSUE_NAME">Tissue Name</option>
							<option value="TISSUE_SEARCH/CELL_LINE">Cell Line Name</option>
							<option value="TISSUE_SEARCH/TISSUE_ID">Tissue Identifier</option>
							 */
$SEARCH_TYPE='TISSUE_ANY';
$SOURCE='';

try{
$LIST_TYPES=array('TISSUE_ANY','TISSUE_NAME','CELL_LINE','TISSUE_ID');
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{

		 if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}

if ($SEARCH_TYPE=='TISSUE_ANY'||$SEARCH_TYPE=='TISSUE_NAME'){

		$res=tissue_portal_name($SEARCH_VALUE);
	
		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['TISSUE'][$line['ANATOMY_TAG']])) continue;
			$list=explode("|",$line['SYN_V']);
			sort($list);
			$list=array_unique($list);
			
			$ENTRY=array('Tissue ID'=>$line['ANATOMY_TAG'],
						 'Tissue Name'=>$line['ANATOMY_NAME'],
						 'Synonyms'=>str_replace("|"," ; ",implode('|',$list)),
						'Source'=>'Name');
						 
				$SEARCH_RESULTS['TISSUE'][$line['ANATOMY_TAG']]=$ENTRY;
			}
			//exit;
	}

	
	if ($SEARCH_TYPE=='TISSUE_ANY'||$SEARCH_TYPE=='TISSUE_ID'){
	
		$res=tissue_portal_id($SEARCH_VALUE);
		
		
		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['TISSUE'][$line['ANATOMY_TAG']])) continue;
			$list=explode("|",$line['SYN_V']);
			sort($list);
			
			$list=array_unique($list);
			$ENTRY=array('Tissue ID'=>$line['ANATOMY_TAG'],
						 'Tissue Name'=>$line['ANATOMY_NAME'],
						 'Synonyms'=>str_replace("|"," ; ",implode('|',$list)),
						'Source'=>'ID');
						 
				$SEARCH_RESULTS['TISSUE'][$line['ANATOMY_TAG']]=$ENTRY;
			}
	}
	
	

	
	$RESULTS='';
	foreach ($SEARCH_RESULTS['TISSUE'] as $N=> $line)
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
				if (($HEAD=='Tissue ID') && isset($line['Tissue ID']))$RESULTS.='<a href="/TISSUE_ID/'.$line['Tissue ID'].'">'.$V.'</a>';
				
				else $RESULTS.=$V;
				$RESULTS.='</td>';
			}
			$RESULTS.='</tr>'."\n";
	}
	if (count($SEARCH_RESULTS['TISSUE'])==1)
	{
		removeBlock("search_tissue","MULTI");
		removeBlock("search_tissue","INVALID");
		changeValue("search_tissue","TISSUE_TAG",$SEARCH_RESULTS['TISSUE'][array_keys($SEARCH_RESULTS['TISSUE'])[0]]['Tissue ID']);
	}
	else
	{
		changeValue("search_tissue","result",$RESULTS);
		removeBlock("search_tissue","SINGLE");
		removeBlock("search_tissue","INVALID");
		
	}
}
catch(Exception $e)
{
	removeBlock("search_tissue","SINGLE");
	removeBlock("search_tissue","MULTI");
}

$result['count']=count($SEARCH_RESULTS['TISSUE']);
cleanRules('search_tissue');
$result['code']=$HTML["search_tissue"];
if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
		echo json_encode($result);
		exit;
?>