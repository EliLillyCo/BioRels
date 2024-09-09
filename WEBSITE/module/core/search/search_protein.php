<?php
$SEARCH_VALUE=htmlentities(strip_tags( trim($USER_INPUT['PAGE']['VALUE'])));
$FIRST=true;
$SP='ALL';


$SEARCH_RESULTS['PROTEIN']=array();
$time=microtime_float();
$result['TIME']=array();

$SEARCH_TYPE='PROT_ANY';
$SOURCE='';

try{
$LIST_TYPES=array('PROT_ANY','PROT_NAME','PROT_ID','PROT_AC','PROT_SEQ','PROT_DOM');
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		if ($VAL=='ALL')$SP='ALL';
		else if ($VAL=='HUMAN')$SP='HUMAN';
		else if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}

if ($SEARCH_TYPE=='PROT_ID'||$SEARCH_TYPE=='PROT_ANY'){

		$res=protein_portal_uniprotID($SEARCH_VALUE,($SP=='HUMAN'));
		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']])) continue;
			$ENTRY=array('Uniprot ID'=>$line['PROT_IDENTIFIER'],
						 'Gene ID'=>$line['GENE_ID'],
						 'Reviewed'=>($line['STATUS']=="T")?"YES":"NO",
						 'Organism'=>$line['SCIENTIFIC_NAME'],
						'Source'=>'Protein Entry');
						 if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
				$SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']]=$ENTRY;
			}
			//exit;
	}

	if ($SEARCH_TYPE=='PROT_NAME'||$SEARCH_TYPE=='PROT_ANY'){
	
		$res=protein_portal_protName($SEARCH_VALUE,($SP=='HUMAN'));
		
		
		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']])) continue;
			$ENTRY=array('Uniprot ID'=>$line['PROT_IDENTIFIER'],
						 'Gene ID'=>$line['GENE_ID'],
						 'Reviewed'=>($line['STATUS']=="T")?"YES":"NO",
						 'Organism'=>$line['SCIENTIFIC_NAME'],
						 'Source'=>'Protein Name');
						 if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
				$SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']]=$ENTRY;
			}
	}
	
if (($SEARCH_TYPE=='PROT_ANY' ||$SEARCH_TYPE=='PROT_AC')&& preg_match('/[OPQ][0-9][A-Z0-9]{3}[0-9]|[A-NR-Z][0-9]([A-Z][A-Z0-9]{2}[0-9]){1,2}(-[0-9]{1,2}){0,1}/',$SEARCH_VALUE))
{
	$res=protein_portal_uniprotAC($SEARCH_VALUE,($SP=='HUMAN'));
	foreach ($res as $line)
	{
		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']])) continue;
			$ENTRY=array('Uniprot ID'=>$line['PROT_IDENTIFIER'],
							'Gene ID'=>$line['GENE_ID'],
							'Reviewed'=>($line['STATUS']=="T")?"YES":"NO",
							'Organism'=>$line['SCIENTIFIC_NAME'],
							'Source'=>'Protein Accession');
							if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
				$SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']]=$ENTRY;
			}
	}
}


	if ($SEARCH_TYPE=='PROT_SEQ'||$SEARCH_TYPE=='PROT_ANY'){
	
		
		$res=protein_portal_uniprotSeqName($SEARCH_VALUE);

		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']])) continue;
			$ENTRY=array('Uniprot ID'=>$line['PROT_IDENTIFIER'],
						 'Gene ID'=>$line['GENE_ID'],
						 'Gene Symbol'=>$line['SYMBOL'],
						 'Reviewed'=>($line['STATUS']=="T")?"YES":"NO",
						 'Organism'=>$line['SCIENTIFIC_NAME'],
						 'Sequence Id'=>$line['ISO_ID'],
						 'Sequence description'=>$line['DESCRIPTION'],
						 'Source'=>'Protein Sequence');
						 if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
				$SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']]=$ENTRY;
			}
	}

	if ($SEARCH_TYPE=='PROT_DOM'||$SEARCH_TYPE=='PROT_ANY'){
	
		
		$res=protein_portal_uniprotDomName($SEARCH_VALUE);

		foreach ($res as $line){
			if (isset($SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']])) continue;
			$ENTRY=array('Uniprot ID'=>$line['PROT_IDENTIFIER'],
						 'Gene ID'=>$line['GENE_ID'],
						 'Gene Symbol'=>$line['SYMBOL'],
						 'Reviewed'=>($line['STATUS']=="T")?"YES":"NO",
						 'Organism'=>$line['SCIENTIFIC_NAME'],
						 'Domain name'=>$line['DOMAIN_NAME'],
						 'Source'=>'Protein Domain');
						 if ($SP=='HUMAN' && $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
				$SEARCH_RESULTS['PROTEIN'][$line['PROT_IDENTIFIER']]=$ENTRY;
			}
	}


	
	$RESULTS='';
	foreach ($SEARCH_RESULTS['PROTEIN'] as $N=> $line)
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
				if ($V!='')
				{
				$pos=stripos($V,$SEARCH_VALUE);
				if ($pos!==false)
				{
					$T=substr($V,0,$pos).'<span class="w3-text-green">'.substr($V,$pos,strlen($SEARCH_VALUE)).'</span>'.substr($V,$pos+strlen($SEARCH_VALUE));
					$V=$T;
				}
			}

				$RESULTS.='<td>';
				if (($HEAD=='Gene Symbol' ||$HEAD=='Symbol' || $HEAD=='Gene ID') && isset($line['Gene ID']))$RESULTS.='<a href="/GENEID/'.$line['Gene ID'].'">'.$V.'</a>';
				else if ($HEAD=='Uniprot ID')$RESULTS.='<a href="/UNIPROT_ID/'.$line['Uniprot ID'].'">'.$V.'</a>';
				else $RESULTS.=$V;
				$RESULTS.='</td>';
			}
			$RESULTS.='</tr>'."\n";
	}
	if (count($SEARCH_RESULTS['PROTEIN'])==1)
	{
		removeBlock("search_protein","MULTI");
		removeBlock("search_protein","INVALID");
		changeValue("search_protein","UNIPROT_ID",$SEARCH_RESULTS['PROTEIN'][array_keys($SEARCH_RESULTS['PROTEIN'])[0]]['Uniprot ID']);
	}
	else
	{
		changeValue("search_protein","result",$RESULTS);
		removeBlock("search_protein","SINGLE");
		removeBlock("search_protein","INVALID");
		
	}
}
catch(Exception $e)
{
	removeBlock("search_protein","SINGLE");
	removeBlock("search_protein","MULTI");
}

$result['count']=count($SEARCH_RESULTS['PROTEIN']);
cleanRules('search_protein');
$result['code']=$HTML["search_protein"];
if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
		echo json_encode($result);
		exit;
?>