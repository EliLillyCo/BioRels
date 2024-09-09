<?php

if ($MODULE_DATA==array())
{
	removeBlock("variant","VALID");
	changeValue("variant","MSG","No variant with this identifier could be retrieved");
	return;
}
else if (isset($MODULE_DATA['ERROR']))
{
	removeBlock("variant","VALID");
	changeValue("variant","MSG","An issue happened during the information retrieval ");
	return;
}
removeBlock("variant","INVALID");


$RSID=$USER_INPUT['PAGE']['VALUE'];
$RSID_REC=&$MODULE_DATA[$RSID];
changeValue("variant","RSID",$RSID);
changeValue("variant","DBSNP_LINK",str_replace('${LINK}',$RSID,$GLB_CONFIG['LINK']['DBSNP']['DBSNP']));

if (count($RSID_REC['PMID'])!=0)
{
changeValue("variant","LIST_PUBLI",implode("_",$RSID_REC['PMID']));
removeBlock("variant","NO_PMID");
}
else removeBlock("variant","HAS_PMID");



$TOT_LEVEL=5+count($RSID_REC['VARIANT']);
changeValue("variant","TOT_LEV",$TOT_LEVEL);
changeValue("variant","PUB_LEVEL",$TOT_LEVEL-2);
changeValue("variant","REL_LEVEL",$TOT_LEVEL-1);
changeValue("variant","CLINICAL_LEVEL",$TOT_LEVEL);
changeValue("variant","GWAS",$TOT_LEVEL-3);





$PAGE_GENE_ID=-1;
if (isset($USER_INPUT['PORTAL']['DATA']['GENE_ID']))$PAGE_GENE_ID=$USER_INPUT['PORTAL']['DATA']['GENE_ID'];


$GWAS_CHANGE='';

$JS='';
foreach ($RSID_REC['VARIANT'] as &$VAR_POS)
{
	++$NLEV;
	$POS=$VAR_POS['CHR_SEQ_NAME'].'_'.$VAR_POS['POSITION'].'_'.$VAR_POS['REF_ALL'];
	$STR_LEV[]='<li id="mut'.$RSID.'_tab_'.$NLEV.'" class="'.(($NLEV==1)?'active':'').' nav-item nav-link"><a onclick="showMenu(\'mut'.$RSID.'\','.$NLEV.','.$TOT_LEVEL.')">'.$POS.'</a></li>';
	$STR_ALL[$NLEV]='';
	$STR=&$STR_ALL[$NLEV];
	$STR.='<div id="mut'.$RSID.'_view_'.$NLEV.'" class="container-grey w3-container w3-padding-16 w3-col s12 s-always-show m12 l12" '.(($NLEV>1)?'style="display:none"':'').' >';
	
	++$N_DT_POS;
	$DATA_POS[$N_DT_POS]='Chromosome Type: '.$VAR_POS['ASSEMBLY_UNIT'];

	$STR.='<div class="w3-col s12 l6 m6">
	<div class="w3-col s12 l6 m12"><h5>Location:</h5>
	<table class="w3-table "><tr><th class="w3-right">Chromosome:</th><td>'.$VAR_POS['CHR_NUM'].'</td></tr>
	<tr><th class="w3-right">Chromosome Name:</th><td class="ttl_tr" title="change" data-pos="'.$N_DT_POS.'">'.$VAR_POS['CHR_SEQ_NAME'].'</td></tr>
	<tr><th class="w3-right">Position:</th><td>'.$VAR_POS['POSITION'].'</td></tr>
	<tr><th class="w3-right">Reference allele:</th><td>'.$VAR_POS['REF_ALL'].'</td></tr></table></div>
	';

	$REF_ALL=$VAR_POS['REF_ALL'];

	$STR_FREQ_TH='';
	$STR_LIST='';
	$STR_IMPACT='<table class="w3-table ">';
	$FREQ_INFO=array('TOTAL'=>array('DESCRIPTION'=>'Average of all studies','TOTAL'=>0,'CHANGE'=>array(),'SHORT_NAME'=>'Overall average'));
	$TOT_STUDY=array();
	foreach ($VAR_POS['CHANGE'] as &$CHANGE)
	{
		$STR_LIST.=$CHANGE['ALT_ALL'].'<br/>';
		++$N_DT_POS;
		$STR_IMPACT.='<tr><th class="w3-right">'.$CHANGE['ALT_ALL'].' </td><td class="ttl_tr" title="change" data-pos="'.$N_DT_POS.'">'.str_replace('_',' ',$CHANGE['SO_NAME']).'</td></tr>';
		//echo $CHANGE['ALT_ALL'];
		
		$DATA_POS[$N_DT_POS]=$CHANGE['SO_DESCRIPTION'];
		$STR_FREQ_TH.='<th>'.$CHANGE['ALT_ALL'].'</th>';

		if (isset($CHANGE['FREQ']))
		foreach ($CHANGE['FREQ'] as $STUDY_NAME=>&$FREQ){
			
		if (!isset($TOT_STUDY[$STUDY_NAME]))
		{
			$FREQ_INFO['TOTAL']['TOTAL']+=$FREQ['ALT_COUNT'];
			$TOT_STUDY[$STUDY_NAME]=1;
		}
		if (!isset($FREQ_INFO['TOTAL']['CHANGE'][$CHANGE['ALT_ALL']]))$FREQ_INFO['TOTAL']['CHANGE'][$CHANGE['ALT_ALL']]=0;
		$FREQ_INFO['TOTAL']['CHANGE'][$CHANGE['ALT_ALL']]+=$FREQ['REF_COUNT'];
		
		$FREQ_INFO[$STUDY_NAME]['SHORT_NAME']=$FREQ['SHORT_NAME'];
		$FREQ_INFO[$STUDY_NAME]['TOTAL']=$FREQ['ALT_COUNT'];
		$FREQ_INFO[$STUDY_NAME]['CHANGE'][$CHANGE['ALT_ALL']]=$FREQ['REF_COUNT'];
		}

	}
	$STR.='<div class="w3-col  s12 m12 l6"><h5>Allele</h5>'.$STR_IMPACT.'</table></div>';

	//// PRINTING STUDIES
	if ($TOT_STUDY!=array())
	{
		$JS.='$(\'#mut'.$RSID.'_tbl_'.$POS.'\').dataTable(
			{
				"responsive":true,
				"ordering": true,
				"searching": false,
				"info":false,
				"order":[[3,"desc"]]
			});';
		$STR.='</div><div class="w3-col s6 m6 l6"><h5>Frequency</h5>

		<table style="width:100%; font-size:0.875rem" id="mut'.$RSID.'_tbl_'.$POS.'" class="compact dataTable">
			<thead>
				<tr><th class="boldright padr10">Study:</th><th>Ratio</th>'.$STR_FREQ_TH.'</tr>
			</thead>
			<tbody>
			';
		
		if (count($FREQ_INFO)>1)
		{
			$MAX_FREQ=0;$MAX_FREQ_ALL='';
			foreach ($FREQ_INFO['TOTAL']['CHANGE'] as $ALL=>$CO)
			{
				if ($CO<$MAX_FREQ)continue;
				$MAX_FREQ=$CO;
				$MAX_FREQ_ALL=$ALL;
			}

			if ($MAX_FREQ_ALL!=$REF_ALL)
			{
				$STR_A='<div class="alert alert-info" role="alert">
				Looking at the frequency, it looks like the reference allele '.$REF_ALL.' is not the most frequent one - Allele '.$MAX_FREQ_ALL.' is the most frequent one<br/>';
				$STR_A.='This is due to the fact that the reference genome is mainly based on one individual who is not representative of the overall population
			</div>';
			changeValue("variant","ALERT",$STR_A);
			
			}



			
			$N=0;
			$COLORS=array('w3-red','w3-yellow','w3-grey','w3-green','w3-orange');
		
			foreach ($FREQ_INFO as $STUDY=>&$FREQ_STUDY)
			{
				++$N_DT_POS;

				// if (substr($STUDY,0,4)=='SAMN')
				// {
				// 	$T=$STUDY;
				// 	$STUDY=$FREQ_STUDY['DESCRIPTION'];
				// 	$FREQ_STUDY['DESCRIPTION']=$T;
				// }
				$STR.='<tr><td style="padding-top:0px !important;padding-bottom:0px !important;text-align:right;" class="boldright padr10 ttl_tr" title="'.$STUDY.'" data-pos="'.$N_DT_POS.'">'.$FREQ_STUDY['SHORT_NAME'].':</td><td style="display:flex ;padding-top:0px !important;padding-bottom:0px !important;">';
				$DATA_POS[$N_DT_POS]=$FREQ_STUDY['DESCRIPTION'];
				$STR2='';$N=0;
				foreach ($VAR_POS['CHANGE'] as $ALL=> &$CHANGE_D)
				{
					if (!isset($FREQ_STUDY['CHANGE'][$CHANGE_D['ALT_ALL']])){++$N;$STR2.='<td>N/A</td>';continue;}
					if ($FREQ_STUDY['TOTAL']!=0)$PERC=round($FREQ_STUDY['CHANGE'][$CHANGE_D['ALT_ALL']]/$FREQ_STUDY['TOTAL']*100,3);else $PERC=0;
					++$N_DT_POS;
					$STR.='<div style="width:'.($PERC*0.98).'%;height:10px;position:relative;top:8px;" class="'.$COLORS[$N].' ttl_tr" data-pos="'.$N_DT_POS.'" title="freq info"></div>';++$N;
					$DATA_POS[$N_DT_POS]=$CHANGE_D['ALT_ALL'];
					$STR2.='<td  style="padding-top:0px !important;padding-bottom:0px !important;">'.$PERC.'%</td>';
				}
				$STR.='</td>'.$STR2.'</tr>';
			}
			unset($FREQ_INFO);

		}else $STR.='<td><td>No Data</td></tr>';
		$STR.='</tbody>
			</table></div>';
	}
	else
	{
		$STR.='</div><div class="w3-col w3-half"><h5>Frequency</h5><div class="alert alert-info">No reported frequency information</div></div>';
	}
	


	
	$IMPACT='';
	$IMPACT_MENU='';
	$NLEV_I=0;
	$TOT_LEVEL_I=count($VAR_POS['CHANGE']);
	foreach ($VAR_POS['CHANGE'] as &$CHANGE)
	{

		$TREE_IMPACT=array();
		foreach ($CHANGE['TRANSCRIPT'] as &$IMPACT_TR)
		{
			if (!isset($TREE_IMPACT[$IMPACT_TR['GENE_ID']]))
			{
				$TREE_IMPACT[$IMPACT_TR['GENE_ID']]
				=array('INFO'=>array('SYMBOL'=>$IMPACT_TR['SYMBOL'],
																	'FULL_NAME'=>$IMPACT_TR['FULL_NAME']),'CHILD'=>array());
			}
			$GENE=&$TREE_IMPACT[$IMPACT_TR['GENE_ID']];
			if (!isset($GENE['CHILD'][$IMPACT_TR['GENE_SEQ_NAME']]))
			{
				$GENE['CHILD'][$IMPACT_TR['GENE_SEQ_NAME']]=array('INFO'=>array('VERSION'=>$IMPACT_TR['GENE_SEQ_VERSION'],
																						'STRAND'=>$IMPACT_TR['STRAND'],
																						'RANGE'=>'['.$IMPACT_TR['GENE_SEQ_START_POS'].'-'.$IMPACT_TR['GENE_SEQ_END_POS'].']'),
																					'CHILD'=>array());
			}
			$GENE_SEQ=&$GENE['CHILD'][$IMPACT_TR['GENE_SEQ_NAME']];
			if (!isset($GENE_SEQ['CHILD'][$IMPACT_TR['TRANSCRIPT_NAME']]))
			{
				$GENE_SEQ['CHILD'][$IMPACT_TR['TRANSCRIPT_NAME']]=array('INFO'=>array('VERSION'=>$IMPACT_TR['TRANSCRIPT_VERSION'],
				'SUPPORT_LEVEL'=>$IMPACT_TR['SUPPORT_LEVEL'],
				'RANGE'=>'['.$IMPACT_TR['START_POS'].'-'.$IMPACT_TR['END_POS'].']'),
			'CHILD'=>array());
			}
			$TRANSCRIPT=&$GENE_SEQ['CHILD'][$IMPACT_TR['TRANSCRIPT_NAME']];
			$TRANSCRIPT['CHILD']=array('SO_ID'=>$IMPACT_TR['SO_ID'],
												'SO_NAME'=>$IMPACT_TR['SO_NAME'],
												'SEQ_POS'=>$IMPACT_TR['SEQ_POS'],
												'SO_DESCRIPTION'=>$IMPACT_TR['SO_DESCRIPTION'],
												'REF_ALL'=>$IMPACT_TR['TR_REF_ALL'],
												'ALT_ALL'=>$IMPACT_TR['TR_ALT_ALL']);
			if (isset($IMPACT_TR['PROTEIN']))$TRANSCRIPT['CHILD']['PROTEIN']=$IMPACT_TR['PROTEIN'];
			
		}
	if (count($TREE_IMPACT)==0)continue;
		$NLEV_I++;
		$POS_C=$POS.'_IMPACT_ALLELE';
		$IMPACT_MENU.='<li id="mut'.$POS_C.'_tab_'.$NLEV_I.'" class="'.(($NLEV_I==1)?'active':'').' nav-item nav-link" onclick="showMenu(\'mut'.$POS_C.'\','.$NLEV_I.','.$TOT_LEVEL_I.')">'.$CHANGE['ALT_ALL'].'</li>';
		$IMPACT.='<div id="mut'.$POS_C.'_view_'.$NLEV_I.'" class="container-grey w3-container w3-padding-16" '.(($NLEV_I>1)?'style="display:none"':'').' >';

		

		//echo '<pre>';print_r($TREE_IMPACT);exit;
		foreach ($TREE_IMPACT as $GENE_ID=>&$TREE_GENE)
		{
			$IMPACT.='<p style="font-weight:bold;text-align:center;font-size:1.3em;">Gene <a href="/GENEID/'.$GENE_ID.'">'.$TREE_GENE['INFO']['SYMBOL'].'</a>  - '.$TREE_GENE['INFO']['FULL_NAME'].' (Gene ID:<a href="/GENEID/'.$GENE_ID.'">'.$GENE_ID.'</a>)</p>';
			foreach ($TREE_GENE['CHILD'] as $GS_NAME=>&$TREE_GS)
			{
				$IMPACT.='<p style="font-weight:bold;text-align:center;font-size:1.2em">Gene Sequence '.$GS_NAME.'  on  '.(($TREE_GS['INFO']['STRAND']=='-')?'negative (-)':'positive (+)').' DNA Strand</p>
				
				<table class="table table-sm"><thead><tr style="text-align:center"><th>Transcript Name</th><th>Position</th><th>Ref Allele</th><th>Alt allele</th><th>pre-mRNA view</th><th>mRNA View</th><th>Impact</th></tr></thead>	<tbody>
				';
				foreach ($TREE_GS['CHILD'] as $TR_NAME=>&$TREE_TR)
				{
					++$N_L;
					$INFO=&$TREE_TR['CHILD'];
					$USER_INPUT['PAGE']['VALUE']=$TR_NAME;
					$USER_INPUT['PARAMS']=array("DNA",$VAR_POS['POSITION']-10,$VAR_POS['POSITION']+10,"HIGHLIGHT",$VAR_POS['POSITION']);
					$STR2='';$STR3='';
					//echo $TR_NAME."/PARAMS/".implode("/",$USER_INPUT['PARAMS'])."\n";
					try{
					$STR2='<div id="clin_View_prem">'.loadHTMLAndRemove('PREMRNA_SEL').'</div>';
					}catch(Exception $e){}
					try{
					$STR3='<div id="clin_View_mrna">'.loadHTMLAndRemove('TRANSCRIPT_SEL').'</div>';
					}catch(Exception $e){}

					$TR_FULL_NAME=$TR_NAME;
					if ($TREE_TR['INFO']['VERSION']!='')$TR_FULL_NAME.='.'.$TREE_TR['INFO']['VERSION'];
					$IMPACT.='<tr>
					<td style="padding-left:40px"><a  href="/GENEID/'.$GENE_ID.'/TRANSCRIPT/'.$TR_FULL_NAME.'">'.$TR_FULL_NAME;
					
					++$N_DT_POS;
					$DATA_POS[$N_DT_POS]=$INFO['SO_DESCRIPTION'];

					$IMPACT.='</a>  '.$TREE_TR['INFO']['RANGE'].'</td><td>'.$INFO['SEQ_POS'].'</td><td>'.$INFO['REF_ALL'].'</td><td>'.$INFO['ALT_ALL'].'</td><td>'.$STR2.'</td><td>'.$STR3.'</td><td class="ttl_tr" title="t" data-pos="'.$N_DT_POS.'">'.str_replace('_',' ',$INFO['SO_NAME']).'</td>
					</tr>'."\n";
					if (!isset($TREE_TR['CHILD']['PROTEIN']))continue;
					$IMPACT.='<tr><td colspan="8"><span style="font-weight:bold;font-size:1.1em;padding-left:40px;">Protein impact:</span>'."\n";
					$IMPACT.='<div><table style="width:90%;margin:0 auto; margin-bottom:10px;text-align:center">
					<thead><tr><th>Sequence Name</th><th>Position</th><th>Ref Amino acid</th><th>Alt amino acid</th><th>Impact</th></tr>
					</thead><tbody>';
					foreach ($TREE_TR['CHILD']['PROTEIN'] as $PROT_E)
					{
						$IMPACT.='<tr><td>'.$PROT_E['ISO_ID'].'</td><td>'.$PROT_E['SEQ_POS'].'</td><td>'.$PROT_E['REF_PROT_ALL'].'</td><td>'.$PROT_E['COMP_PROT_ALL'].'</td><td>'.$PROT_E['SO_ID'].'</td></tr>'."\n";
					}
					$IMPACT.='</tbody></table>
					</div>
					</td></tr>';
					
					
				}
				$IMPACT.='</tbody></table>';
			}
		}
		$IMPACT.='</div>';
	}

//	exit;
	$STR.='<div class="w3-container w3-col s12"><ul class="nav nav-tabs">'.$IMPACT_MENU.'</ul>'.$IMPACT;
	


	$STR.='</div></div>';

	foreach ($VAR_POS['CHANGE'] as &$CHANGE)
	{
		if (!isset($CHANGE['GWAS']))continue;
		foreach ($CHANGE['GWAS'] as $study=>&$study_data)
		{
			$study_info=explode("_",$study);
			//<td>'.$study_info[0].'</td><td>'.$study_info[1].'</td>
			$tk=array();
			$header='<h4>'.$study_info[0].' '.$study_info[1].'</h4>';
			$header.='<table class="table"><thead><tr><th>Phenotype</th>';
			foreach ($study_data as $phenotype=>&$stats)
			{
				foreach ($stats as $type=>$values)$tk[$type]=1;
				//$GWAS_CHANGE.='<tr><td>'.$phenotype.'</td>';
			
			}
			$headers=array_keys($tk);
			foreach ($headers as $t)$header.='<th>'.$t.'</th>';
			$header.='</tr></thead><tbody>';
			foreach ($study_data as $phenotype=>&$stats)
			{
				$header.='<tr><td>'.$phenotype.'</td>';
				foreach ($headers as $t)
				{
					if (!isset($stats[$t]))$header.='<td></td>';
					else $header.='<td>'.$stats[$t][1].'</td>';
				}
				$header.='</tr>';	
			}
			$GWAS_CHANGE.=$header.'</table>';
			
			
		}
	}

}



changeValue("variant","GWAS_DATA",$GWAS_CHANGE);
changeValue("variant","POSITIONS",implode("\n",$STR_LEV));
changeValue("variant","CHANGE_DATA",implode("\n",$STR_ALL));
changeValue("variant","LIST",str_replace("'","\\'",json_encode($DATA_POS)));

changeValue("variant","JS",$JS);




changeValue("variant","LOC",'chr'.$MODULE_DATA[$RSID]['VARIANT']['CHR_NUM'].':'.$MODULE_DATA[$RSID]['VARIANT']['POSITION']);
changeValue("variant","REF",$RSID_REC['VARIANT']['REF_ALL']);

$RULE=array('Benign'=>0,
'Likely benign'=>0,                    
'Uncertain significance'=>1,
'Likely pathogenic'=>4,
'Pathogenic'=>4,          
'Likely pathogenic, low penetrance'=>4,          
'Pathogenic, low penetrance'=>4,                 
'Uncertain risk allele'=>1,                      
'Likely risk allele'=>2,                         
'Established risk allele'=>2,                    
'drug response'=>3,                              
'association'=>3,                                
'protective'=>5,                                 
'Affects'=>3,                                    
'conflicting data from submitters'=>1,           
'not provided'=>1,                        
'risk factor'=>2,                                
'confers sensitivity'=>2,                        
'histocompatibility'=>1,                         
'association not found'=>1,                      
'other'=>1,                                      
);
if ($MODULE_DATA[$RSID]['CLINICAL']!=array())
{
	$STR='';
	foreach ($MODULE_DATA[$RSID]['CLINICAL'] as $NAME=> &$CLIN_E)
	{
		$STR.='<div class="w3-col s12">';
		/// Disease info
		$STR.='<h4 style="text-align:center"><span style="font-weight:bold;">'.$NAME.'</span></h4>';
		foreach ($CLIN_E as &$CLIN_S)
		{

			$CLIN=$CLIN_S['RECORD'];
			$sign='<span style="color:';
			switch($RULE[$CLIN['CLIN_SIGN']])
			{
				
				
					case 0:$sign.='blue;';break;
					case 1:$sign.='grey;';break;
					case 2:$sign.='orange;';break;
					case 3:$sign.='cyan;';break;
					case 4:$sign.='red;font-size:1.2em;';break;
			}
			$sign .= '">';
			

			$STR.= '<div class="w3-col s12">By <span style="font-style:italic">'.$CLIN['SUBMITTER'].'<span> - '.$sign.$CLIN['CLIN_SIGN'].'</span></div>';
			$STR.='<div class="w3-col s6">
			<table class="table"><tr><th>Disease Name:</th><td>'.$CLIN['DISEASE_NAME'].'</td></tr>
			<tr><th>Disease Definition:</th><td>'.$CLIN['DISEASE_DEFINITION'].'</td></tr>
			</table></div>';
	/// Gene info
	$STR.='<div class="w3-col s6">
	<table class="table"><tr><th>Gene Symbol:</th><td>'.$CLIN['SYMBOL'].'</td></tr>
	<tr><th>Gene Name:</th><td>'.$CLIN['FULL_NAME'].'</td></tr>
	</table></div>';
	if ($CLIN['COMMENTS']!='')$STR.='<div class="w3-col s12" style="text-align: justify;margin-bottom: 27px;"><span style="font-weight:bold">Comments:</span> '.$CLIN['COMMENTS'].'</div>';
	print_R($CLIN_S);
	if (isset($CLIN_S['PMID']))
	{
		$USER_INPUT['PAGE']['VALUE']=implode("_",$CLIN_S['PMID']);
		$STR_t='<div class="w3-col s12">'.loadHTMLAndRemove("PUBLICATION_BATCH").'</div>';
		$STR.=$STR_t;
	}
	}
		$STR.='</div>';
	}
	changeValue("variant","CLINICAL",$STR);
}


?>