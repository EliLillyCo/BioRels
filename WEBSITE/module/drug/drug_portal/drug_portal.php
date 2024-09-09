<?php

// SET PAGE TITLE
changeValue("drug_portal", "DRUG_NAME", $USER_INPUT['PORTAL']['DATA']['DRUG_PRIMARY_NAME']);
changeValue("drug_portal","DRUG_PRIM_NAME",$USER_INPUT['PORTAL']['DATA']['DRUG_PRIMARY_NAME']);

$PORTAL_DATA=&$USER_INPUT['PORTAL']['DATA'];


function processATC(&$LIST,$CURR_ATC,$LEVEL,&$STR)
{

	$CODE=$CURR_ATC;
	$TITLE=$LIST[$CURR_ATC]['TITLE'];
	if ($CURR_ATC=='ATC_V_ROOT'||$CURR_ATC=='ATC_ROOT')
	{
		
	}
	else
	$STR.='<tr><td style="padding-left:'.($LEVEL*10).'px">'.$CODE.'</td><td>'.$TITLE.'</td></tr>';
	if (!isset($LIST[$CURR_ATC]['CHILD']))return;
	sort($LIST[$CURR_ATC]['CHILD']);
	foreach ($LIST[$CURR_ATC]['CHILD'] as $C)
	{
		processATC($LIST,$C,$LEVEL+1,$STR);
	}

}

// CONFIRM DATA AVAILABLE
if ($MODULE_DATA == array()) {
	removeBlock("drug_portal", "HAS_DATA");
} else {
	removeBlock("drug_portal", "NO_DATA_MESSAGE");

	// BUILD NAMES COMPONENT
	
if (isset($MODULE_DATA['ATC']))
{
	$STR='';
	foreach ($MODULE_DATA['ATC']['ROOT'] as $R)
	{
		$STR.='<div class="w3-col s12 m12 l6">';
		if ($R=='ATC_V_ROOT')
		$STR.='<h5 style="text-align:center">Anatomical Therapeutic Chemical veterinary (ATCvet)<br/> classification system</h5>';
		else if ($R=='ATC_ROOT')
		$STR.='<h5 style="text-align:center">Anatomical Therapeutic Chemical (ATC)<br/> classification system</h5>';
		$STR.='<table class="table">';
		processATC($MODULE_DATA['ATC']['ENTRY'],$R,0,$STR);
		$STR.='</table></div>';
	}
	
	changeValue("drug_portal",'ATC',$STR);
}
else removeBlock("drug_portal","HAS_ATC");



	$STR='';
	$LIST_SOURCE=array();
	$N_NAME=0;
	$N_SOURCE=1;
	foreach ($MODULE_DATA['NAME'] as $TYPE_NAME=>&$LIST) {
		if ($TYPE_NAME=='PRIMARY')continue;
		$N_NAME+=count($LIST);
		$STR.='<h5>'.ucfirst(strtolower($TYPE_NAME)).':</h5>';
		$STR.='<div class="w3-container w3-col s12 l12 m12" style="margin-bottom:50px">';
		$STR_L='';
		foreach ($LIST as $NAME=>$SOURCES)
		{
			if (strlen($NAME)<=15)$WIDTH=150;
			else $WIDTH=300;
			$STR_P='<div style="display:inline-flex; text-align:center;width:'.$WIDTH.'px;height:30px">'.$NAME;
			foreach ($SOURCES as $S)
			{
				if (!isset($LIST_SOURCE[$S])){
					$LIST_SOURCE[$S]=$N_SOURCE;
					$N_SOURCE++;
				}
				$STR_P.=' <sup>'.$LIST_SOURCE[$S].'</sup>';
				
			}
			$STR_P.='</div>';
			if ($WIDTH==150)$STR.=$STR_P;
			else $STR_L.=$STR_P;
		}
		$STR.=$STR_L;
		$STR.='</div>';
	}

	$STR.='<h5>Identifiers:</h5>';
	$STR.='<div class="w3-container w3-col s12;" style="margin-bottom:50px">';
	$STR_LONG='';
	$N_IDENTIFIER=0;
	foreach ($MODULE_DATA['EXTDB'] as $SOURCE_NAME=>&$LIST) {
		
		foreach ($LIST as $NAME=>$SOURCES)
		{
			$LEN=strlen($NAME)+strlen($SOURCE_NAME)+2;
			$WIDTH=200;
			
			$STR_P='<span style="font-weight:bold;margin-right:3px">'.ucfirst(strtolower($SOURCE_NAME)).': </span> '.$NAME;
			$N_IDENTIFIER++;
			foreach ($SOURCES as $S)
			{
				if (!isset($LIST_SOURCE[$S])){
					$LIST_SOURCE[$S]=$N_SOURCE;
					$N_SOURCE++;
				}
				$STR_P.=' <sup>'.$LIST_SOURCE[$S].'</sup>';
				
			}
			$STR_P.='</div>';
			if ($LEN<30)
			{
				$STR.='<div style="display:inline-flex; text-align:center;height:30px;width:250px;">'.$STR_P;
			}
			else 
			$STR_LONG.='<div style="display:inline-flex; text-align:center;height:30px;width:500px">'.$STR_P;
		}
		
		
	}
	$STR.=$STR_LONG.'</div>';

	if ($LIST_SOURCE!=array()){
		$STR .= '<br/>Sources: <p style="font-weight:bold;font-style:italic;font-size:0.9em">';
	foreach ($LIST_SOURCE  as $N => $V) $STR .=$V.': '. $N . '; ';
	$STR = substr($STR, 0, -2) . '</p>';
	}
	changeValue("drug_portal", "N_NAME", $N_NAME);
	changeValue("drug_portal", "N_IDENTIFIER", $N_IDENTIFIER);


	changeValue("drug_portal", "LIST_NAMES", $STR);

	// BUILD SUMMARY CLINICAL TRIAL PHASE
	if (isset($PORTAL_DATA['MAX_CLIN_PHASE']) && $PORTAL_DATA['MAX_CLIN_PHASE'] != 'N/A') {
		$S = '';
		$C = '';
		switch ($PORTAL_DATA['MAX_CLIN_PHASE']) {
			case 0:
				$C = 'grey';
				break;
			case 1:
				$C = 'orange';
				break;
			case 2:
				$C = 'orange';
				break;
			case 3:
				$C = 'green';
				break;
			case 4:
				$C = 'green';
				break;
		}
		for ($I = 1; $I <= $PORTAL_DATA['MAX_CLIN_PHASE']; ++$I) {
			changeValue("drug_portal", "P" . $I, $C);
		}
	}

	if (isset($PORTAL_DATA['SM'])) {
		foreach ($PORTAL_DATA['SM'] as &$SM_INFO)
		{
			if (!isset($SM_INFO['DESC']))continue;
			foreach ($SM_INFO['DESC'] as &$D)
			$MODULE_DATA['DESCRIPTION'][]=array('SOURCE_NAME'=>$D['SOURCE_NAME'],'TEXT_TYPE'=>$D['DESCRIPTION_TYPE'],'TEXT_DESCRIPTION'=>$D['DESCRIPTION_TEXT']);
		}
	}

	$STR='';
	$ORDER=array(array('OpenTargets','Summary'),array('DrugBank','Simple'),array('DrugBank','Clinical'),array('DrugBank','Complete'));
	foreach ($ORDER as $T)
	{
		foreach ($MODULE_DATA['DESCRIPTION'] as &$DESC)
		{
			if ($DESC['SOURCE_NAME']!=$T[0]|| $DESC['TEXT_TYPE']!=$T[1])continue;
			if($DESC['TEXT_DESCRIPTION']=='')continue;
			$DESC['FOUND']=true;
			$STR.='<div class="w3-container w3-col s12;" style="margin-bottom:50px">';
			$STR.='<h5>'.$DESC['SOURCE_NAME'].' - '.$DESC['TEXT_TYPE'].'</h5>';
			$STR.='<div style="display:inline-flex; text-align:justify;line-height:1.5em">'.$DESC['TEXT_DESCRIPTION'].'</div>';
			$STR.='</div>';
		}
	}
	foreach ($MODULE_DATA['DESCRIPTION'] as &$DESC)
	{
		if (isset($DESC['FOUND']))continue;
		if($DESC['TEXT_DESCRIPTION']=='')continue;
		$STR.='<div class="w3-container w3-col s12;" style="margin-bottom:50px">';
		$STR.='<h5>'.$DESC['SOURCE_NAME'].' - '.$DESC['TEXT_TYPE'].'</h5>';
		$STR.='<div style="display:inline-flex; text-align:justify;line-height:1.5em">'.$DESC['TEXT_DESCRIPTION'].'</div>';
		$STR.='</div>';
	}
	changeValue("drug_portal", "DESC", $STR);

	// BUILD OUT NEWS COMPONENT
	
		$USER_INPUT['PARAMS'] = array(0 => "PER_PAGE", 1 => "10", 2 => "PAGE", 3 => "1", 4 => "FILTERS", 5 => "drug-" . $PORTAL_DATA['DRUG_ENTRY_ID'] . ";");
		removeBlock("drug_portal", "NO_NEWS");
		changeValue("drug_portal", "NEWS", loadHTMLAndRemove('PUBLI_NEWS_SEARCH'));
	

	// BUILD OUT ASSAY COMPONENT

	if (isset($PORTAL_DATA['SM'])) {
		foreach ($PORTAL_DATA['SM'] as &$SM_E)
		if (isset($SM_E['FULL_SMILES']))
		{
			changeValue("drug_portal","JS_CODE", '
				getCompoundImageInfo("'.$SM_E['FULL_SMILES'].'", "P_CPD_IMG", Math.min(500, parseFloat($("#P_CPD_IMG_PARENT").css("width"))));
				');;
				break;
		}


		removeBlock("drug_portal", "NO_ASSAYS");
		changeValue("drug_portal",'SM_MOL_SIZE','8');
		// todo build drug assay module
		//changeValue("drug_portal", "ASSAY", loadHTMLAndRemove('COMPOUND_ASSAY'));
	}
	else 
	{

		
		

	foreach ($MODULE_DATA['TYPE'] as $TYPE)
	{
			$STR='';
			//print_R($TYPE);
			switch($TYPE['DRUG_TYPE_NAME'])
			{

			case 'Blood factors':
			case 'Thrombolytic agents':
			case 'Haematopoietic growth factors':
				case 'Fusion proteins':
			case 'Interferons'               :
			case 'Interleukin-based products':
				$STR.='<div class="sprite pr_icon_l"></div>';break;
			case 'Monoclonal antibody (mAb)':
			case 'Polyclonal antibody (pAb)':
				$STR.='<div  class="sprite antibody_icon_l"></div>';break;
			

				case "A": 
				
				case "O":$STR.='<div class="sprite o_icon_l"</div>';break;break;
				case "OS":$STR.='<div class="sprite os_icon_l"></div>';break;break;
				case "unknown":
				case "Enzyme":
				case 'PR':
				break;
			}
		}
		
		removeBlock("drug_portal","SM_MOL");
		changeValue("drug_portal",'SM_MOL_SIZE','12');
		changeValue("drug_portal","NOT_SM_IMG",$STR);
	}
}
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
$DISEASES=&$MODULE_DATA['CLINICAL_STAT']['DISEASE'];
$GENES=&$MODULE_DATA['CLINICAL_STAT']['GENE'];
foreach ($MODULE_DATA['CLINICAL_STAT']['ONGOING'] as $ENTRY) {
	$LIST_TYPES[$ENTRY['CLINICAL_STATUS']] = true;
	if ($ENTRY['CLINICAL_STATUS'] == '') $ENTRY['CLINICAL_STATUS'] = 'Unknown status';
	
	if (substr($ENTRY['TRIAL_ID'], 0, 3) == 'NCT') {
		$str .= '<td class="blk_font" onclick="relocate(\'/' . $PATH_T . '/' . $USER_INPUT['PORTAL']['VALUE'] . '/CLINICAL_TRIAL/' . $ENTRY['TRIAL_ID'] . '\')">' . $ENTRY['TRIAL_ID'] . '</td>';
	} else
		$str .= '<td class="blk_font" onclick="relocate(\'' . str_replace('${LINK}', $ENTRY['TRIAL_ID'], $GLB_CONFIG['LINK']['CLINICAL']['TRIAL']) . '\')">' . $ENTRY['TRIAL_ID'] . '</td>';

    $str .= '<td class="blk_font" onclick="relocate(\'/DISEASE/' . $DISEASES[$ENTRY['DISEASE_ENTRY_ID']]['DISEASE_NAME'] . '\')">' . $DISEASES[$ENTRY['DISEASE_ENTRY_ID']]['DISEASE_NAME'] . '</td>';
    $str .= '<td class="blk_font" onclick="relocate(\'/GENEID/' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '\')">' . $GENES[$ENTRY['GN_ENTRY_ID']]['SYMBOL'] . '</td><td  class="blk_font" onclick="relocate(\'/GENEID/' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '\')">' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '</a></td>';

	$str .= '<td>' . $ENTRY['CLINICAL_PHASE'] . '</td>
	<td>' . $ENTRY['START_DATE'] . '</td>
	<td>' . $ENTRY['CLINICAL_STATUS'] . '</td>
</tr>';
}
changeValue("drug_portal", "TRIALS", $str);

$STR='';
$I=0;$STR_JS='';

changeValue("drug_portal", "ALT_IMG_JS", $STR_JS);
changeValue("drug_portal", "ALT_IMG", $STR);

if (isset($MODULE_DATA['DOCS']))
{
    $STR='<br/><br/><h5>Learn more:</h5>';

    foreach ($MODULE_DATA['DOCS'] as &$D)
    {
        if ($D['SOURCE_NAME']=='Liver Tox')
        $STR.='<a target="_blank" href="/NEWS_FILE/'.$D['DOCUMENT_HASH'].'"><img style="width:120px" src="https://www.ncbi.nlm.nih.gov/corehtml/pmc/pmcgifs/bookshelf/thumbs/th-livertox-lrg.png"/></a>';
        else
        $STR.='<a target="_blank"  href="/NEWS_FILE/'.$D['DOCUMENT_HASH'].'"><img style="width:120px"  src="https://www.ncbi.nlm.nih.gov/corehtml/pmc/pmcgifs/bookshelf/thumbs/th-gene-lrg.png"/></a>';
         
    }
    changeValue("drug_portal", "DOCS", $STR);

}

$DT=array();
foreach ($MODULE_DATA['CLINICAL_STAT']['GENE'] as $GID=>$GINFO)
$DT['GENE'][$GID]=array('SYMBOL'=>$GINFO['SYMBOL'],'FULL_NAME'=>$GINFO['FULL_NAME'],'GENE_ID'=>$GINFO['GENE_ID']);
foreach ($MODULE_DATA['CLINICAL_STAT']['DISEASE'] as $GID=>$GINFO)
$DT['DISEASE'][$GID]=array('DISEASE_NAME'=>$GINFO['DISEASE_NAME'],'DISEASE_TAG'=>$GINFO['DISEASE_TAG'],'DISEASE_DEFINITION'=>$GINFO['DISEASE_DEFINITION']);

//$USER_INPUT['PARAMS']=array('DATA','[{"name":"2023\/4","value":3270},{"name":"2023\/5","value":9278},{"name":"2023\/6","value":11882},{"name":"2023\/7","value":2306},{"name":"2023\/8","value":13040},{"name":"2023\/9","value":28}]');
//print_R($MODULE_DATA['CL']);



if (isset($MODULE_DATA['ACT_UNIT']))
{
$STR='';
foreach ($MODULE_DATA['ACT_UNIT'] as $A)
$STR.='<tr><td>'.$A['STD_TYPE'].'</td><td>'.$A['STD_UNITS'].'</td><td>'.$A['MIN'].'</td><td>'.$A['MAX'].'</td><td>'.$A['CO'].'</td></tr>';
changeValue("drug_portal",'UNITS',$STR);
}else removeBlock("drug_portal","HAS_EXPR");



if (isset($MODULE_DATA['CLINICAL_STAT']['PHASES'])){
    ksort($MODULE_DATA['CLINICAL_STAT']['PHASES']);
    $DL=array();$MISS=0;
    foreach ($MODULE_DATA['CLINICAL_STAT']['PHASES'] as $Y=>$V)
    {
        if ($Y=='-1'||$Y==''){$MISS+=$V;continue;}
        $DL[]=array('name'=>$Y,'value'=>$V);
    }
    
    $USER_INPUT['PARAMS']=array('DATA',json_encode($DL));
    $STR=loadHTMLAndRemove("BARCHART");
   
    
    changeValue("drug_portal",'ct_phase',$STR);
    
    changeValue("drug_portal",'MISS_PHASE',$MISS.' clinical trials without Clinical phase');
    }
$STR='';
foreach ($MODULE_DATA['CLINICAL_STAT']['GENE'] as $G)
{
$STR.='<tr><td><a target="_blank" href="/GENEID/'.$G['GENE_ID'].'">'.$G['SYMBOL'].'</a></td><td><a href="/GENEID/'.$G['GENE_ID'].'">'.$G['GENE_ID'].'</a></td><td>'.$G['FULL_NAME'].'</td></tr>';
}

changeValue("drug_portal",'TARGETS',$STR);


$STR='';
foreach ($MODULE_DATA['CLINICAL_STAT']['DISEASE'] as $G)
{
$STR.='<tr><td><a target="_blank" href="/DISEASE/'.$G['DISEASE_TAG'].'">'.$G['DISEASE_NAME'].'</a></td><td>'.$G['DISEASE_DEFINITION'].'</td></tr>';
}

changeValue("drug_portal",'DISEASES',$STR);

?>
