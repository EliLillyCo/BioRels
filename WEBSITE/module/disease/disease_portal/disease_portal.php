<?php

if (!defined("BIORELS")) header("Location:/");



changeValue("disease_portal","DISEASE_NAME",ucfirst($MODULE_DATA['DISEASE_NAME']));
changeValue("disease_portal","DISEASE_DEFINITION",$MODULE_DATA['DISEASE_DEFINITION']);
changeValue("disease_portal","DISEASE_TAG",$MODULE_DATA['DISEASE_TAG']);


	$STR='';
	$GROUPS=array();
	foreach ($MODULE_DATA['SYN'] AS $S=>$V)
		$GROUPS[ucfirst($V['SYN_TYPE'])][]=$V['SYN_VALUE'];

	foreach ($GROUPS as $S=>&$LIST)
	{
		$STR.='<div class="w3-col s12 m4 l4">';
		switch ($S)
		{
			case 'EXACT':$STR.='<h5>Exact Synonyms:</h5>';break;
			case 'REL':$STR.='<h5>Related Synonyms:</h5>';break;
			case 'BRO':$STR.='<h5>Broad Synonyms:</h5>';break;
			case 'NAR':$STR.='<h5>Narrowed Synonyms:</h5>';break;
		}
		$STR.='<ul>';
		foreach ($LIST as $L)$STR.='<li>'.$L.'</li>';
		$STR.='</ul>';
		$STR.='</div>';
	}
	
	if (isset($MODULE_DATA['EXTDB']) &&$MODULE_DATA['EXTDB']!=array())
	{
		$STR.='<div class="w3-col s12 m4 l4">';
		$STR.='<h5>External Database Links:</h5>';
		$STR.='<ul>';
		foreach ($MODULE_DATA['EXTDB'] as $S)
		{
			switch (strtolower($S['SOURCE_NAME']))
			{
				case 'ncti':
				case 'doid':
				case 'icd10':
				case 'icd9':
				$STR.='<li>'.$S['SOURCE_NAME'].':'.$S['DISEASE_EXTDB'].' </li> ';break;
				case 'mesh':$STR.='<li><a href="'.str_replace('${LINK}',$S['DISEASE_EXTDB'],$GLB_CONFIG['LINK']['CLINVAR']['MESH']).'">'.$S['SOURCE_NAME'].':'.$S['DISEASE_EXTDB'].'</a> </li> ';break;
				case 'efo':$STR.='<li><a href="'.str_replace('${LINK}',$S['SOURCE_NAME'].'_'.$S['DISEASE_EXTDB'],$GLB_CONFIG['LINK']['OLS']['EFO']).'">'.$S['SOURCE_NAME'].':'.$S['DISEASE_EXTDB'].'</a> </li> ';break;
				case 'omim':$STR.='<li><a href="'.str_replace('${LINK}',$S['DISEASE_EXTDB'],$GLB_CONFIG['LINK']['OMIM']['OMIM']).'">'.$S['SOURCE_NAME'].':'.$S['DISEASE_EXTDB'].'</a> </li> ';break;
			}
		}
		$STR.='</ul></div>';
	}
	changeValue("disease_portal","SYNONYMS",$STR);




	$STR_OPT=array();
	$STR='';
	$N=0;
	$GROUPS=array();

	if (isset($MODULE_DATA['INFO']['OMIM']))
	{
		
		foreach ($MODULE_DATA['INFO']['OMIM'] as $OM=>$TXT)
		{
			$tab=explode("-",$OM);
			$GROUPS[trim($tab[0])][]=$OM;
		}
		$N=count($GROUPS);
	}
	if (isset($MODULE_DATA['DESCRIPTION']))$N++;
	
	
	
	if (isset($MODULE_DATA['INFO']['OMIM']))
	{
		if ($N>1)
		{
			foreach ($GROUPS as $G=>&$LIST_BLOCK)
			$STR_OPT.='<option value="OMIM_'.$G.'">OMIM Record - '.$G.'</option>';
		}
		foreach ($GROUPS as $G=>&$LIST_BLOCK)
		{
			$STR.='<div id="OMIM_'.$G.'" '; if ($N>1)$STR.='style="display:none">';
			else $STR.='><h4 style="width:100%;text-align:center">OMIM Record - '.$G.'</h4>';
			foreach ($MODULE_DATA['INFO']['OMIM'] as $OM=>$TXT)
			{
				$tab=explode("-",$OM);
				$STR.='<h5 style="margin-top:15px;margin-bottom:5px;">'.$tab[1].':</h5>'.convertBiorelsTags($TXT).'<br/>';
			}
			$STR.='</div>';
		}
	}
	if (isset($MODULE_DATA['DESCRIPTION']))
	{
		if ($N>1)
		{
			$STR_OPT.='<option value="GeneReviews">Gene Reviews</option>';
			$STR.='<div id="GeneReviews" style="display:none">';
		}
		$STR.=$MODULE_DATA['DESCRIPTION']['ABSTRACT'];


		
		if (isset($MODULE_DATA['INFO']['DOCS']))
		{
			$STR.='<br/><br/><h5>Learn more:</h5>';
			foreach ($MODULE_DATA['INFO']['DOCS'] as &$D)
			{
				if (!isset($D['application/pdf']))continue;
				if ($D['application/pdf']['SOURCE_NAME']=='Liver Tox')
				$STR.='<a target="_blank" href="/NEWS_FILE/'.$D['application/pdf']['DOCUMENT_HASH'].'"><img style="width:120px" src="https://www.ncbi.nlm.nih.gov/corehtml/pmc/pmcgifs/bookshelf/thumbs/th-livertox-lrg.png"/></a>';
				else
				$STR.='<a target="_blank"  href="/NEWS_FILE/'.$D['application/pdf']['DOCUMENT_HASH'].'"><img style="width:120px"  src="https://www.ncbi.nlm.nih.gov/corehtml/pmc/pmcgifs/bookshelf/thumbs/th-gene-lrg.png"/></a>';		
			}
		}
		if ($N>1)$STR.='</div>';
	}

	if ($N>1)
	{
		changeValue("disease_portal","MARGIN","5");
		changeValue("disease_portal",'OPTS_DEF','<select style="width:100%" id="DESC_SELECT" onchange="showDesc(this)">'.$STR_OPT.'</select>');
	}else changeValue("disease_portal","MARGIN","44");
	if ($STR!='')changeValue("disease_portal","DEFS",$STR);
	else changeValue("disease_portal","DEFS",'No description available');



$STATUS = array(
	'Unknown status' => array('#000'),
	'Not yet recruiting' => array('#a1a1a1'),

	'Enrolling by invitation' => array('#a4dd74'),
	'Recruiting' => array('#a4dd74'),
	'Active, not recruiting' => array('#33ae10'),
	'Suspended' => array('#e85342'),
	'Withdrawn' => array('#e85342'),
	'Terminated' => array('#e85342'),
	'Completed' => array('#5454E8')
);
$PATH_T='DRUG';
$STATS = array(0 => array(), 'I' => array(), 'II' => array(), 'III' => array(), 'IV' => array());
$PHASE_MAP = array(0 => 0, 1 => 'I', 2 => 'II', 3 => 'III', '4' => 'IV');
$LIST_TYPES = array();
$str = '';
//echo '<pre>';print_r($MODULE_DATA['CLINICAL_STAT']);exit;
$DRUGS=&$MODULE_DATA['CLINICAL_STAT']['DRUG'];
$GENES=&$MODULE_DATA['CLINICAL_STAT']['GENE'];
foreach ($MODULE_DATA['CLINICAL_STAT']['ONGOING'] as $ENTRY) {
	$LIST_TYPES[$ENTRY['CLINICAL_STATUS']] = true;
	if ($ENTRY['CLINICAL_STATUS'] == '') $ENTRY['CLINICAL_STATUS'] = 'Unknown status';
	$STATS[$PHASE_MAP[$ENTRY['CLINICAL_PHASE']]][$ENTRY['CLINICAL_STATUS']]++;
	if (substr($ENTRY['TRIAL_ID'], 0, 3) == 'NCT') {
		$str .= '<td class="blk_font" onclick="relocate(\'/' . $PATH_T . '/' . $USER_INPUT['PORTAL']['VALUE'] . '/CLINICAL_TRIAL/' . $ENTRY['TRIAL_ID'] . '\')">' . $ENTRY['TRIAL_ID'] . '</td>';
	} else
		$str .= '<td class="blk_font" onclick="relocate(\'' . str_replace('${LINK}', $ENTRY['TRIAL_ID'], $GLB_CONFIG['LINK']['CLINICAL']['TRIAL']) . '\')">' . $ENTRY['TRIAL_ID'] . '</td>';

    $str .= '<td class="blk_font" onclick="relocate(\'/DRUG/' . $DRUGS[$ENTRY['DRUG_ENTRY_ID']]['DRUG_PRIMARY_NAME'] . '\')">' .$DRUGS[$ENTRY['DRUG_ENTRY_ID']]['DRUG_PRIMARY_NAME']  . '</td>';
    $str .= '<td class="blk_font" onclick="relocate(\'/GENEID/' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '\')">' . $GENES[$ENTRY['GN_ENTRY_ID']]['SYMBOL'] . '</td><td  class="blk_font" onclick="relocate(\'/GENEID/' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '\')">' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '</a></td>';

	$str .= '<td>' . $ENTRY['CLINICAL_PHASE'] . '</td>
	<td>' . $ENTRY['START_DATE'] . '</td>
	<td>' . $ENTRY['CLINICAL_STATUS'] . '</td>
</tr>';
}
changeValue("disease_portal", "TRIALS", $str);









	
	$STR='';
	$STR_N='<div style="display:flex">';
	$DR_I=array(1=>'I',2=>'II',3=>'III',4=>'IV');
	$MAX_LEV=0;
	if (isset($MODULE_DATA['TRIALS']))
	{
	foreach ($MODULE_DATA['TRIALS'] as $K=>$T)if ($T!=0)$MAX_LEV=max($K,$MAX_LEV);
	}
	for ($I=1;$I<=4;++$I)
	{
		$STR_N.='<div class="w3-col s3_1 m3_1 l3_1" style="
    margin-right: 1%
    margin-bottom: 5px;"><div class="text-circle blk_font" style="margin:0 auto">'.(isset($MODULE_DATA['TRIALS'][$I])?$MODULE_DATA['TRIALS'][$I]:0).'</div></div>';
	$STR.='<div  class="chevron w3-col s3_1 m3_1 l3_1" style="';
	if ($I>$MAX_LEV)$STR.='background-color:grey';
	$STR.='">'.$DR_I[$I].'</div>';

		// $STR_N.='<div style="width: 27%;
		// margin-right: -30px;
		// margin-bottom: 5px;"><div class="text-circle" style="margin:0 auto">'.(isset($MODULE_DATA['TRIALS'][$I])?$MODULE_DATA['TRIALS'][$I]:0).'</div></div>';
		// $STR.='<div  class="chevron" style="width:27%;';
		// if ($I>$MAX_LEV)$STR.='background-color:grey';
		// $STR.='">'.$DR_I[$I].'</div>';
	}
	changeValue("disease_portal","TRIALS",$STR_N.'</div>'.$STR);
	
	$STR='';
	foreach ($MODULE_DATA['INFO']['CELL_LINE'] as $CL)
	{
		$STR.='<tr><td>'.$CL['CELL_ACC'].'</td><td>'
		.$CL['CELL_NAME'].'</td><td>'
		.$CL['CELL_TYPE'].'</td><td>'
		.$CL['CELL_DONOR_SEX'].'</td><td>'
		.$CL['CELL_DONOR_AGE'].'</td><td>'
		.$CL['CELL_TISSUE_NAME'].'</td></tr>';
	}
	changeValue("disease_portal","CELL_LINES",$STR);



	$STR='year,value'."\n";
	foreach ($MODULE_DATA['INFO']['PUB_DATE'] as $Y=>$N)
	$STR.=$Y.','.$N."\n";
	changeValue("disease_portal","PUB_DATA",$STR);

	$USER_INPUT['PARAMS']=array('RID');
	$STR='';
	foreach ($MODULE_DATA['INFO']['NEWS'] as $NEWS)$STR.=$NEWS['NEWS_ID'].'_';
	$USER_INPUT['PARAMS'][]=$STR;
	$D=loadHTMLAndRemove('NEWS_BATCH');
	changeValue("disease_portal","NEWS",$D);

?>