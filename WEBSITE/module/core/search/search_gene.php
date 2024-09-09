<?php


$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['GENE']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';


$SEARCH_TYPE='GENE_ANY';
$SOURCE='';
try{
$LIST_TYPES=array('GENE_ANY','GENE_SYMBOL','GENE_NAME','GENE_NCBI','GENE_ENS','GENE_TR','SNP');
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		if ($VAL=='ALL')$SP='ALL';
		else if ($VAL=='HUMAN')$SP='HUMAN';
		else if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}

if ($SEARCH_TYPE=='GENE_NCBI'||$SEARCH_TYPE=='GENE_ANY'){
if (is_numeric($SEARCH_VALUE))
{

	$line=gene_portal_geneID($SEARCH_VALUE);
	
		
	if ($line!==false &&!isset($SEARCH_RESULTS['GENE'][$line['GENE_ID']])){

		$ENTRY=array('Symbol'=>$line['SYMBOL'],
						'Gene Name'=>$line['FULL_NAME'],
						'Gene ID'=>$line['GENE_ID'],
						'Organism'=>$line['SCIENTIFIC_NAME'],
						'Synonyms'=>implode("|",$line['SYN_VALUE']),
					'Source'=>'GeneID');
						
		$SEARCH_RESULTS['GENE'][$line['GENE_ID']]=$ENTRY;
		
	}
	$result['TIME']['geneID']=microtime_float()-$time;$time=microtime_float();
}
}
if ($SEARCH_TYPE=='GENE_SYMBOL'||$SEARCH_TYPE=='GENE_ANY'){
	if (strlen($SEARCH_VALUE)<10)
	{
		$res=gene_portal_gene($SEARCH_VALUE);
		
		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['GENE'][$line['GENE_ID']]))continue;
		
			$ENTRY=array('Symbol'=>$line['SYMBOL'],
							'Gene Name'=>$line['FULL_NAME'],
							'Gene ID'=>$line['GENE_ID'],
							'Organism'=>$line['SCIENTIFIC_NAME'],
							'Synonyms'=>str_replace('|',' ; ',$line['SYN']),
						'Source'=>'Symbol');
							if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
			$SEARCH_RESULTS['GENE'][$line['GENE_ID']]=$ENTRY;
		
		}
		$result['TIME']['gene']=microtime_float()-$time;$time=microtime_float();
	}
	}

if ($SEARCH_TYPE=='GENE_TR'||$SEARCH_TYPE=='GENE_ANY'){
	$matches=array();
	$matchesT=array();
	$TR=false;
	foreach ($GLB_CONFIG['REGEX']['TRANSCRIPT'] as $T)
	{
		if (preg_match('/'.$T.'/',$SEARCH_VALUE,$matchesT)!==false)	$TR=true;
	}
	if ($TR)
	{
		
		$res=gene_portal_transcript($SEARCH_VALUE);
		
		foreach ($res as $line){
		//	if (isset($SEARCH_RESULTS['GENE'][$line['GENE_ID']]))continue;
		
			$ENTRY=array('Symbol'=>$line['SYMBOL'],
							'Gene Name'=>$line['FULL_NAME'],
							'Gene ID'=>$line['GENE_ID'],
							'Organism'=>$line['SCIENTIFIC_NAME'],
							'Synonyms'=>str_replace('|',' ; ',$line['SYN']),
						'Source'=>'Transcript');
							if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
			$SEARCH_RESULTS['GENE'][$line['GENE_ID']]=$ENTRY;
			
		}
		
		$result['TIME']['ensembl']=microtime_float()-$time;$time=microtime_float();

	}
}
if ($SEARCH_TYPE=='GENE_ENS'||$SEARCH_TYPE=='GENE_ANY')
{
	if (preg_match('/'.$GLB_CONFIG['REGEX']['ENSEMBL'][0].'/',$SEARCH_VALUE,$matches)!==false)
	{
		$res=gene_portal_ensembl($SEARCH_VALUE);
		
		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['GENE'][$line['GENE_ID']]))continue;
		
			if ($line['GENE_ID']!='')
			{
			$ENTRY=array('Symbol'=>$line['SYMBOL'],
							'Gene Name'=>$line['FULL_NAME'],
							'Gene ID'=>$line['GENE_ID'],
							'Organism'=>$line['SCIENTIFIC_NAME'],
							'Synonyms'=>str_replace('|',' ; ',$line['SYN']),
						'Source'=>'Ensembl');
							if ( $SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
			}
			else
			{
				$ENTRY=array('Symbol'=>'N/A',
							'Gene Name'=>$line['GENE_SEQ_NAME'],
							'Gene ID'=>'',
							'Organism'=>'N/A',
							'Synonyms'=>'N/A',
						'Source'=>'Ensembl');
			}
			$SEARCH_RESULTS['GENE'][$line['GENE_ID']]=$ENTRY;
			
		}
		
		$result['TIME']['ensembl']=microtime_float()-$time;$time=microtime_float();
	}
}
if ($SEARCH_TYPE=='SNP'||$SEARCH_TYPE=='GENE_ANY')
{
	if (preg_match('/'.$GLB_CONFIG['REGEX']['SNP'][0].'/',$SEARCH_VALUE,$matches)!==false)
	{
		$res=gene_portal_rsid($SEARCH_VALUE);
		
		if (count($res)==1)
		{
			
			if ($SEARCH_TYPE=='SNP')
			{
			removeBlock("search_gene","MULTI");
			if ($res[0]['GENE_ID']=='')
			changeValue("search_gene","ADDON",'VARIANT/'.$res[0]['rsid']);
			else changeValue("search_gene","ADDON",'/GENEID/'.$res[0]['GENE_ID'].'/VARIANT/'.$res[0]['RSID']);
			removeBlock("search_gene","MULTI");
			removeBlock("search_gene","INVALID");
			cleanRules("search_gene");
			if (ob_get_contents())ob_end_clean();

			if ($USER_INPUT['VTYPE']=='JSON'){
				header('Content-type: application/json');
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Methods: GET, POST');
				header("Access-Control-Allow-Headers: X-Requested-With");
				echo json_encode($SEARCH_RESULTS['GENE']);exit;
			}
			$result['code']=$HTML["search_gene"];

				echo json_encode($result);
				exit;
			}

		}
		else {		

		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['GENE'][$line['GENE_ID']]))continue;
		
			$ENTRY=array('Symbol'=>$line['SYMBOL'],
							'Gene Name'=>$line['FULL_NAME'],
							'Gene ID'=>$line['GENE_ID'],
							'Organism'=>$line['SCIENTIFIC_NAME'],
							'Synonyms'=>str_replace('|',' ; ',$line['SYN']),
						'Source'=>'SNP');
							if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
			$SEARCH_RESULTS['GENE'][$line['GENE_ID']]=$ENTRY;
			
		}
	}
		
		$result['TIME']['SNP']=microtime_float()-$time;$time=microtime_float();
	}
}
if ($SEARCH_TYPE=='GENE_NAME'||$SEARCH_TYPE=='GENE_ANY'){
	if (strlen($SEARCH_VALUE)>10)
	{
		$res=gene_portal_geneName($SEARCH_VALUE);
		
		foreach ($res as $line)
		{
				if (isset($SEARCH_RESULTS['GENE'][$line['GENE_ID']]))continue;
				
					$ENTRY=array('Symbol'=>$line['SYMBOL'],
									'Gene Name'=>$line['FULL_NAME'],
									'Gene ID'=>$line['GENE_ID'],
									'Organism'=>$line['SCIENTIFIC_NAME'],
									'Synonyms'=>str_replace('|',' ; ',$line['SYN']),
								'Source'=>'Name');
									if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
					$SEARCH_RESULTS['GENE'][$line['GENE_ID']]=$ENTRY;
		}
		$SOURCE='NAME';
		$result['TIME']['name']=microtime_float()-$time;$time=microtime_float();
	}
}

$RESULTS='';
foreach ($SEARCH_RESULTS['GENE'] as $N=> $line)
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
		if (($HEAD=='Symbol' || $HEAD=='Gene ID') && isset($line['Gene ID']))$RESULTS.='<a href="/GENEID/'.$line['Gene ID'].'">'.$V.'</a>';
		else $RESULTS.=$V;
		$RESULTS.='</td>';
	}
	$RESULTS.='</tr>'."\n";
}
if (count($SEARCH_RESULTS['GENE'])==1)
{
	removeBlock("search_gene","MULTI");
	
	$GENE_ID=array_keys($SEARCH_RESULTS['GENE'])[0];
	$SOURCE=$SEARCH_RESULTS['GENE'][$GENE_ID]['Source'];

	
	
	if ($SOURCE =='SNP')
	{
		if (isset($SEARCH_RESULTS['GENE'][$GENE_ID]['rsid']))	changeValue("search_gene","ADDON",'/GENEID/'.$GENE_ID.'/VARIANT/'.$SEARCH_VALUE);
		else changeValue("search_gene","ADDON",'/GENEID/'.$GENE_ID);
	}
	else if ($SOURCE =='Ensembl')
	{
		if ($GENE_ID!='')changeValue("search_gene","ADDON",'/GENEID/'.$GENE_ID.'/TRANSCRIPTS');
		
	}
	else if ($SOURCE =='Transcript')
	{
		if ($GENE_ID!='')	changeValue("search_gene","ADDON",'/GENEID/'.$GENE_ID.'/TRANSCRIPT/'.$SEARCH_VALUE);
		else 				changeValue("search_gene","ADDON",'/TRANSCRIPT/'.$SEARCH_VALUE);
	}
	else if ($SOURCE =='GeneID'|| $SOURCE=='Symbol'||$SOURCE=='Name')
	{
		changeValue("search_gene","ADDON",'/GENEID/'.$GENE_ID);
	}

}
else
{
changeValue("search_gene","result",$RESULTS);
removeBlock("search_gene","SINGLE");

$result['count']=count($SEARCH_RESULTS['GENE']);
}
removeBlock("search_gene","INVALID");
}
catch(Exception $e)
{
	removeBlock("search_gene","SINGLE");
	removeBlock("search_gene","MULTI");
}


cleanRules("search_gene");
if ($USER_INPUT['VTYPE']=='JSON'){
	if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
	echo json_encode($SEARCH_RESULTS['GENE']);exit;
}
$result['code']=$HTML["search_gene"];

if (ob_get_contents())ob_end_clean();
	echo json_encode($result);
	exit;
?>