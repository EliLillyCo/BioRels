<?php
ini_set('memory_limit','1000M');

$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['NEWS']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';

$SEARCH_TYPE='NEWS_NAME';
$SOURCE='';
try{
$LIST_TYPES=array('NEWS_ANY','NEWS_NAME','NEWS_COMPLEX');
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		if ($VAL=='ALL')$SP='ALL';
		else if ($VAL=='HUMAN')$SP='HUMAN';
		else if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}


if ($SEARCH_TYPE=='NEWS_ANY'||$SEARCH_TYPE=='NEWS_NAME'){

	$res=array();
	
	if (hasPrivateAccess())
	{
		
		$res=private_news_search_name($SEARCH_VALUE);
	}
	
	
	$res=news_search_name($SEARCH_VALUE);
	
	foreach ($res as $line)
	{
		
	if (isset($SEARCH_RESULTS['NEWS'][$line['NEWS_HASH']]))continue;

		$ENTRY=array('Title'=>$line['NEWS_TITLE'],
						'Source'=>$line['SOURCE_NAME'],
						'Date'=>$line['NEWS_ADDED_DATE']);
					
		
		$SEARCH_RESULTS['NEWS'][$line['NEWS_HASH']]=$ENTRY;
		
	}

	$result['TIME']['NEWS NAME']=microtime_float()-$time;$time=microtime_float();

}

if ($SEARCH_TYPE=='NEWS_ANY'||$SEARCH_TYPE=='NEWS_COMPLEX'){

	$MODULE_DATA['NEWS_LIST']=array();
	changeValue("search_news",'QUERY',$SEARCH_VALUE);
	$prev_pos=0;
	$pos=strpos($SEARCH_VALUE,' AND ');
	$N=0;
	$GROUPS=array();
	do
	{
		if ($pos===false)break;
		$query=substr($SEARCH_VALUE,$prev_pos,$pos-$prev_pos);
		
		$GROUPS[]=trim($query);
		$prev_pos=$pos+5;
		$pos=strpos($SEARCH_VALUE,' AND ',$prev_pos);
		
		++$N;
		if ($N>100)break;
	}while ($pos!==false);
	$GROUPS[]=trim(substr($SEARCH_VALUE,$prev_pos));
try{
	$ANNOT_RES=array();
	foreach ($GROUPS as $GRP)
	{
		$pos=strpos($GRP,'=');
		if ($pos===false)continue;
		$head=strtolower(trim(substr($GRP,0,$pos)));
		$value=trim(substr($GRP,$pos+1));
		switch ($head)
		{
			case 'gene':
			{
				if (is_numeric($value))
				{

					$line=gene_portal_geneID($value);
					if ($line!==false &&!isset($ANNOT_RES['GENE'][$line['GENE_ID']]))
					{
						$ENTRY=array('Symbol'=>$line['SYMBOL'],
						'Gene Name'=>$line['FULL_NAME'],
						'Gene ID'=>$line['GENE_ID'],
						'Organism'=>$line['SCIENTIFIC_NAME'],
						'Synonyms'=>implode("|",$line['SYN_VALUE']),
						'Source'=>'GeneID');
						if ( $line['SCIENTIFIC_NAME']=='Homo sapiens')
						$ANNOT_RES['GENE'][$line['GENE_ID']]=$ENTRY;
					}

				}//END GENE NUMERIC
				if (strlen($value)<10)
				{
					$res=gene_portal_gene($value);
					foreach ($res as $line){
						if (isset($ANNOT_RES['GENE'][$line['GENE_ID']]))continue;
					
						$ENTRY=array('Symbol'=>$line['SYMBOL'],
										'Gene Name'=>$line['FULL_NAME'],
										'Gene ID'=>$line['GENE_ID'],
										'Organism'=>$line['SCIENTIFIC_NAME'],
										'Synonyms'=>str_replace('|',' ; ',$line['SYN']),
									'Source'=>'Symbol');
						if ( $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
						$ANNOT_RES['GENE'][$line['GENE_ID']]=$ENTRY;
					
					}
					
				}
				if (preg_match('/'.$GLB_CONFIG['REGEX']['ENSEMBL'][0].'/',$value,$matches)!==false)
				{
					$res=gene_portal_ensembl($value);
					
					foreach ($res as $line){
						if (isset($ANNOT_RES['GENE'][$line['GENE_ID']]))continue;
					
						if ($line['GENE_ID']!='')
						{
						$ENTRY=array('Symbol'=>$line['SYMBOL'],
										'Gene Name'=>$line['FULL_NAME'],
										'Gene ID'=>$line['GENE_ID'],
										'Organism'=>$line['SCIENTIFIC_NAME'],
										'Synonyms'=>'N/A',
									'Source'=>'Ensembl');
										if ( $line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
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
						$ANNOT_RES['GENE'][$line['GENE_ID']]=$ENTRY;
						
					}
					
					
				}
				if (strlen($value)>10)
				{
					$res=gene_portal_geneName($value);
					
					foreach ($res as $line)
					{
							if (isset($ANNOT_RES['GENE'][$line['GENE_ID']]))continue;
							
								$ENTRY=array('Symbol'=>$line['SYMBOL'],
												'Gene Name'=>$line['FULL_NAME'],
												'Gene ID'=>$line['GENE_ID'],
												'Organism'=>$line['SCIENTIFIC_NAME'],
												'Synonyms'=>str_replace('|',' ; ',$line['SYN']),
											'Source'=>'Name');
								if ($line['SCIENTIFIC_NAME']!='Homo sapiens')continue;
								$ANNOT_RES['GENE'][$line['GENE_ID']]=$ENTRY;
					}
					
				}
				if (!isset($ANNOT_RES['GENE']))break;

				if (hasPrivateAccess())
				{
					
					$res=private_searchNewsByGeneIds(array_keys($ANNOT_RES['GENE']));
				}
				else
				$res=searchNewsByGeneIds(array_keys($ANNOT_RES['GENE']));

				foreach ($res as $line)
				{
					$ANNOT_RES['GENE'][$line['GENE_ID']]['MATCH'][]=$line['NEWS_HASH'];
					if (!isset($MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]))$MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]=1;
					else $MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]++;
				}
				break;
			}
			case 'disease':
			{
				$res=disease_portal_disease_name($value);
	
				if (isset($res['DISEASE_TAG']))
				{
					$ENTRY=array('Disease ID'=>$res['DISEASE_TAG'],
					'Disease Name'=>$res['DISEASE_NAME'],
					'Synonyms'=>str_replace('|',' ; ',$res['SYN_V']),
					'Source'=>'Name');

					$ANNOT_RES['DISEASE'][$line['DISEASE_TAG']]=$ENTRY;
				}
				else 
				foreach ($res as $line){
					if (isset($ANNOT_RES['DISEASE'][$line['DISEASE_TAG']]))continue;
				
					$ENTRY=array('Disease ID'=>$line['DISEASE_TAG'],
									'Disease Name'=>$line['DISEASE_NAME'],
									'Synonyms'=>str_replace('|',' ; ',$line['SYN_V']),
								'Source'=>'Name');

					$ANNOT_RES['DISEASE'][$line['DISEASE_TAG']]=$ENTRY;
				
				}
				$res=disease_portal_disease_tag($value);
				
				foreach ($res as $line){
					if (isset($ANNOT_RES['DISEASE'][$line['DISEASE_TAG']]))continue;
					$SOURCES=explode("|",$line['SOURCES']);
					$SOURCES=array_unique($SOURCES);
						$ENTRY=array('Disease ID'=>$line['DISEASE_TAG'],
										'Disease Name'=>$line['DISEASE_NAME'],
										'Synonyms'=>str_replace('|',' ; ',$line['SYN_V']),
										'External ID'=>implode(' ; ',$SOURCES),
									'Source'=>'ID');

						$ANNOT_RES['DISEASE'][$line['DISEASE_TAG']]=$ENTRY;
				
				}
				
				
				if (!isset($ANNOT_RES['DISEASE']))break;

				if (hasPrivateAccess())
				{
					
					$res=private_searchNewsByDiseaseTag(array_keys($ANNOT_RES['DISEASE']));
				}
				else
				$res=searchNewsByDiseaseTag(array_keys($ANNOT_RES['DISEASE']));

				foreach ($res as $line)
				{
					$ANNOT_RES['DISEASE'][$line['DISEASE_TAG']]['MATCH'][]=$line['NEWS_HASH'];
					if (!isset($MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]))$MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]=1;
					else $MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]++;
				}
				break;
			}///END DISEASE
			case 'drug':
			{
				$ini_data = searchDrugByName($value);
				$res = array();
				if ($ini_data) {
					$res = getDrugInfo($ini_data['DRUG_ENTRY_ID']);
				}
		
				foreach ($res as $line) {
					
					if (!isset($ANNOT_RES['DRUG'][$line['DRUG_ENTRY_ID']])) {
		
						// 
						$ENTRY = array(
							'Drug Name' => $line['DRUG_PRIMARY_NAME'],
							'Is Approved' => $line['IS_APPROVED'],
							'Is Investigational' => $line['IS_INVESTIGATIONAL'],
							
							'Source' => 'Name'
						);
		
						$ANNOT_RES['DRUG'][$line['DRUG_ENTRY_ID']] = $ENTRY;
					}
				}

				if (!isset($ANNOT_RES['DRUG']))break;

				if (hasPrivateAccess())
				{
					
					$res=private_searchNewsByDrugEntryId(array_keys($ANNOT_RES['DRUG']));
				}
				else 
				$res=searchNewsByDrugEntryId(array_keys($ANNOT_RES['DRUG']));

				foreach ($res as $line)
				{
					$ANNOT_RES['DRUG'][$line['DRUG_ENTRY_ID']]['MATCH'][]=$line['NEWS_HASH'];
					if (!isset($MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]))$MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]=1;
					else $MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]++;
				}
				break;

			}///END DruG

			case 'clinical':
					$res=getClinicalTrialById($value);
					
					foreach ($res as $line)
					{
						$prim_name='';$alias=array();
						foreach ($line as &$v)if ($v['ALIAS_TYPE']=='Primary')$prim_name=$v['ALIAS_NAME'];
						else $alias[$v['ALIAS_NAME']]=$v['ALIAS_TYPE'];
						$ENTRY=array('trial_id'=>$prim_name ,'Alias'=>$alias,'Title'=>$line[0]['OFFICIAL_TITLE'],
						 'clinical_phase'=>$line[0]['CLINICAL_PHASE'],
						 'clinical_status'=>$line[0]['CLINICAL_STATUS'],
						 'start_date'=>$line[0]['START_DATE']);					
						$ANNOT_RES['CLINICAL'][$prim_name]=$ENTRY;
							
					}
				
					$res=getClinicalTrialByName($value);
					
					foreach ($res as $line)
					{
						$prim_name='';$alias=array();
						foreach ($line as &$v)if ($v['ALIAS_TYPE']=='Primary')$prim_name=$v['ALIAS_NAME'];
						else $alias[$v['ALIAS_NAME']]=$v['ALIAS_TYPE'];
						$ENTRY=array('trial_id'=>$prim_name ,'Alias'=>$alias,'Title'=>$line[0]['OFFICIAL_TITLE'],
						 'clinical_phase'=>$line[0]['CLINICAL_PHASE'],
						 'clinical_status'=>$line[0]['CLINICAL_STATUS'],
						 'start_date'=>$line[0]['START_DATE']);					
						$ANNOT_RES['CLINICAL'][$prim_name]=$ENTRY;
							
					}
				
					if (!isset($ANNOT_RES['CLINICAL']))break;

					if (hasPrivateAccess())
					{
						
						$res=private_searchNewsByClinicalTrial(array_keys($ANNOT_RES['CLINICAL']));
					}
					else 
					$res=searchNewsByClinicalTrial(array_keys($ANNOT_RES['CLINICAL']));
	
					foreach ($res as $line)
					{
						$ANNOT_RES['CLINICAL'][$line['ALIAS_NAME']]['MATCH'][]=$line['NEWS_HASH'];
						if (!isset($MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]))$MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]=1;
						else $MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]++;
					}
					

				break;
				case 'company':
					$res=getCompanyByName($value);
	
					if ($res!=array())$ANNOT_RES['COMPANY']=$res;
					if (!isset($ANNOT_RES['COMPANY']))break;

					if (hasPrivateAccess())
					{
						
						$res=private_searchNewsByCompany(array_keys($ANNOT_RES['COMPANY']));
					}
					else 
					$res=searchNewsByCompany(array_keys($ANNOT_RES['COMPANY']));
	
					foreach ($res as $line)
					{
						$ANNOT_RES['COMPANY'][$line['COMPANY_NAME']]['MATCH'][]=$line['NEWS_HASH'];
						if (!isset($MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]))$MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]=1;
						else $MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]++;
					}
					

				break;					


					
				case 'title':
						
						if (hasPrivateAccess())
						{
							
							$res=private_news_search_name($value);
						}
						else 
						
						$res=news_search_name($value);
						
						foreach ($res as $line)
						{
							
						if (isset($ANNOT_RES['NEWS'][$line['NEWS_HASH']]))continue;

							$ENTRY=array('Title'=>$line['NEWS_TITLE'],
											'Source'=>$line['SOURCE_NAME'],
											'Date'=>$line['NEWS_ADDED_DATE']);
										
							
							$ANNOT_RES['NEWS'][$line['NEWS_HASH']]=$ENTRY;
							
						}
						
						break;
					case 'source':
						
							
							$res=source_search($value);
							
							foreach ($res as $line)
							{
								
							if (isset($ANNOT_RES['SOURCE'][$line['SOURCE_NAME']]))continue;
							switch($line['SOURCE_TYPE'])
							{
								case 'D':$line['SOURCE_TYPE']='Database';break;
								case 'C':$line['SOURCE_TYPE']='Conference';break;
								case 'N':$line['SOURCE_TYPE']='News';break;
							}
								$ENTRY=array('SubGroup'=>$line['SUBGROUP'],
												'Type'=>$line['SOURCE_TYPE']);
											
								
								$ANNOT_RES['SOURCE'][$line['SOURCE_NAME']]=$ENTRY;
								
							}
							if (!isset($ANNOT_RES['SOURCE']))break;
							if (hasPrivateAccess())
							{
								
								$res=private_searchNewsBySource(array_keys($ANNOT_RES['SOURCE']));
							}
							else 
							$res=searchNewsBySource(array_keys($ANNOT_RES['SOURCE']));
			
							foreach ($res as $line)
							{
								$ANNOT_RES['SOURCE'][$line['SOURCE_NAME']]['MATCH'][]=$line['NEWS_HASH'];
								if (!isset($MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]))$MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]=1;
								else $MODULE_DATA['NEWS_LIST'][$line['NEWS_HASH']]++;
							}
							

						
							
							break;
		}//END SWITCH
	}
	$MODULE_DATA['ANNOT']=$ANNOT_RES;
	$MODULE_DATA['ANNOT_ENTRIES']=array();
	removeBlock("search_news","MULTI");
	removeBlock("search_news","SINGLE");
	removeBlock("search_news","INVALID");


	if ($MODULE_DATA['NEWS_LIST']!=array())
	{
		removeBlock("search_news","CPLX_NO_RESULTS");
	
	if (hasPrivateAccess())
	{
	$MODULE_DATA['ANNOT_ENTRIES']=private_getNewsAnnot(array_keys($MODULE_DATA['NEWS_LIST']));
	}else $MODULE_DATA['ANNOT_ENTRIES']=getNewsAnnot(array_keys($MODULE_DATA['NEWS_LIST']));

	
	$STR='';
	$JSON=array();
	$HEAD=array('SOURCE'=>'Source','SOURCE_TYPE'=>'Source Type','SOURCE_SUBGROUP'=>'Source Group','GENE'=>'Gene','COMPANY'=>'Company','CLINICAL'=>'Clinical','DRUG'=>'Drug','DISEASE'=>'Disease','NEWS'=>'News','DATE'=>'Date');

	foreach ($HEAD as $TYPE_NAME=>$TYPE_TEXT)
	{
		if (!isset($MODULE_DATA['ANNOT'][$TYPE_NAME]) && !isset($MODULE_DATA['ANNOT_ENTRIES'][$TYPE_NAME]))continue;
		$STR.='<div class="w3-container w3-col s3p m10p l10p" style="min-height:240px;font-size:0.9em">';
		$STR.='<h5 style="height:50px">'.$TYPE_TEXT.'</h5>';
		
		$STR.='<select style="width:100%;height:200px;" multiple="multiple;font-size:0.9em" id="search_n_'.$TYPE_NAME.'" class="search_news_sel">';
		if (isset($MODULE_DATA['ANNOT'][$TYPE_NAME]))
		{
			$STR.='<optgroup label="Your query:">';
			$INFO=&$MODULE_DATA['ANNOT'][$TYPE_NAME];
			foreach ($INFO as $ID=>&$RECORD)
			{
				if ($TYPE_NAME!='NEWS' && !isset($RECORD['MATCH']))continue;
				if (isset($RECORD['MATCH']))$RECORD['MATCH']=array_filter($RECORD['MATCH']);
				switch ($TYPE_NAME)
				{
					case 'GENE':$STR.='<option selected="selected" value="gn-'.$ID.'">'.$RECORD['Symbol'].' '.$RECORD['Gene Name'].' ('.count($RECORD['MATCH']).')</option>';
					$JSON["gn-".$ID]=$RECORD['MATCH'];
					break;
				
					case 'DRUG':
						$TXT=$RECORD['Drug Name'].' ('.count($RECORD['MATCH']).')';
						$STR.='<option selected="selected"  value="dr-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
					$JSON["dr-".$ID]=$RECORD['MATCH'];
					break;
					case 'SOURCE':
						$TXT=$ID.' ('.count($RECORD['MATCH']).')';
						$STR.='<option selected="selected"  value="sc-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
					$JSON["sc-".$ID]=$RECORD['MATCH'];
					break;
					case 'COMPANY':
						$TXT=$ID.' ('.count($RECORD['MATCH']).')';
						$STR.='<option selected="selected"  value="co-'.$ID.'" title="'.$TXT.'">'.$ID.' ('.count($RECORD['MATCH']).')</option>';
					$JSON["co-".$ID]=$RECORD['MATCH'];
					break;
					case 'CLINICAL':
						$TXT=$ID.' ('.count($RECORD['MATCH']).')';
						$STR.='<option selected="selected"  value="ci-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
					$JSON["ci-".$ID]=$RECORD['MATCH'];
					break;
					case 'DISEASE':
						$TXT=$RECORD['Disease Name'].' ('.count($RECORD['MATCH']).')';
						$STR.='<option selected="selected"  value="ds-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
					$JSON["ds-".$ID]=$RECORD['MATCH'];
					break;
					case 'NEWS':$STR.='<option selected="selected"  value="ne-'.$ID.'" title="'.$RECORD['Title'].'">'.$RECORD['Title'].' (1)</option>';
					$JSON["ne-".$ID]=array($ID);
					
					break;
					
				}
			}
			$STR.='</optgroup>';
		}
		if (isset($MODULE_DATA['ANNOT_ENTRIES'][$TYPE_NAME]))
		{
			
			$STR.='<optgroup label="Related match:">';
			
			$INFO=&$MODULE_DATA['ANNOT_ENTRIES'][$TYPE_NAME];
			foreach ($INFO as $ID=>&$RECORD)
			{
				if (isset($MODULE_DATA['ANNOT'][$TYPE_NAME][$ID]))continue;
				if ($TYPE_NAME!='NEWS' && !isset($RECORD['MATCH']))continue;
				if (isset($RECORD['MATCH']))$RECORD['MATCH']=array_filter($RECORD['MATCH']);
				switch ($TYPE_NAME)
				{
					case 'GENE':
						$TXT=$RECORD['Symbol'].' '.$RECORD['Gene Name'].' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="gn-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
					$JSON["gn-".$ID]=$RECORD['MATCH'];
					break;
					
					case 'DRUG':
						$TXT=$RECORD['Drug Name'].' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="dr-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
						$JSON["dr-".$ID]=$RECORD['MATCH'];
					break;
					case 'SOURCE':
						$TXT=$ID.' '.$RECORD['SubGroup'].' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="sc-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
						$JSON["sc-".$ID]=$RECORD['MATCH'];
					break;
						
					case 'COMPANY':
						$TXT=$ID.' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="co-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
						$JSON["co-".$ID]=$RECORD['MATCH'];
					break;
					case 'CLINICAL':
						$TXT=$RECORD['clinical_phase'].' '.$ID.' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="ci-'.$ID.'" title="'.$TXT.' - '.$RECORD['Title'].'">'.$TXT.'</option>';
						$JSON["ci-".$ID]=$RECORD['MATCH'];
					break;
					
					case 'DISEASE':
						$TXT=$RECORD['Disease Name'].' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="ds-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
						$JSON["ds-".$ID]=$RECORD['MATCH'];
					break;
					case 'NEWS':
						$TXT=$RECORD['Title'].' (1)';
						$STR.='<option value="ne-'.$ID.'" title="'.$TXT.'">'.$TXT.'</option>';
						$JSON["ne-".$ID]=array($ID);
					break;
					case 'SOURCE_TYPE':
						$TXT=$ID.' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="st-'.$ID.'"  title="'.$TXT.'">'.$TXT.'</option>';
						$JSON["st-".$ID]=array($ID);
						break;
					case 'SOURCE_SUBGROUP':
						$TXT=$ID.' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="sg-'.$ID.'"  title="'.$TXT.'">'.$TXT.'</option>';
						$JSON["sg-".$ID]=array($ID);
					break;
					case 'DATE':
						$TXT=$ID.' ('.count($RECORD['MATCH']).')';
						$STR.='<option value="dt-'.$ID.'"  title="'.$TXT.'" >'.$TXT.'</option>';
						$JSON["dt-".$ID]=$RECORD['MATCH'];
					break;
					
				}
			}
			$STR.='</optgroup>';
			
		}
		$STR.='</select>';
		$STR.='</div>';

	}

	
	
	changeValue("search_news","FILTERS",$STR);
	changeValue("search_news","JSON",json_encode($JSON));
	}else removeBlock("search_news","CPLX_RESULTS");
}catch(Exception $e)
{
	echo '<pre>';
	print_r($e);exit;
}
cleanRules("search_news");
$result['code'] = $HTML["search_news"];


if (ob_get_contents()) ob_end_clean();
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
echo json_encode($result);
exit;
}


$RESULTS='';
foreach ($SEARCH_RESULTS['NEWS'] as $N=> $line)
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
		if (($HEAD=='Title' ))$RESULTS.='<a href="/NEWS/'.$line['NEWS_HASH'].'">'.$V.'</a>';
		else $RESULTS.=$V;
		$RESULTS.='</td>';
	}
	$RESULTS.='</tr>'."\n";
}
if (count($SEARCH_RESULTS['NEWS'])==1)
{
	removeBlock("search_news","COMPLEX");
	removeBlock("search_news","MULTI");
	
	$NEWS_ID=array_keys($SEARCH_RESULTS['NEWS'])[0];
	changeValue("search_news","ADDON",'/NEWS/'.$NEWS_ID);
	

}
else
{
changeValue("search_news","result",$RESULTS);
removeBlock("search_news","SINGLE");

removeBlock("search_news","COMPLEX");
$result['count']=count($SEARCH_RESULTS['NEWS']);
}
removeBlock("search_news","INVALID");
}
catch(Exception $e)
{
	removeBlock("search_news","SINGLE");
	removeBlock("search_news","COMPLEX");
	removeBlock("search_news","MULTI");
}


cleanRules("search_news");

if ($USER_INPUT['VTYPE'] == 'JSON') {
	if (ob_get_contents()) ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
	echo json_encode($SEARCH_RESULTS['NEWS']);
	exit;
}
$result['code'] = $HTML["search_news"];

if (ob_get_contents()) ob_end_clean();
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
echo json_encode($result);
exit;
?>