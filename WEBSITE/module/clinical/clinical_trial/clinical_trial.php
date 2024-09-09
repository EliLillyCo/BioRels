<?php

if (!defined("BIORELS")) header("Location:/");

if ($MODULE_DATA==array()|| isset($MODULE_DATA['ERROR']))
{
	removeBlock("clinical_trial","VALID");
	return;
}
else removeBlock("clinical_trial","INVALID");


$PROTOCOL_SECTION=&$MODULE_DATA['protocolSection'];
$ID_MOD=&$PROTOCOL_SECTION['identificationModule'];
$ST_MOD=&$PROTOCOL_SECTION['statusModule'];
$DESC_MOD=&$PROTOCOL_SECTION['descriptionModule'];
$COND_MOD=&$PROTOCOL_SECTION['conditionsModule'];
$DESIGN_MOD=&$PROTOCOL_SECTION['designModule'];
$ELIGIBILTY_MOD=&$PROTOCOL_SECTION['eligibilityModule'];
$ARMS_MOD=&$PROTOCOL_SECTION['armsInterventionsModule'];
$REF_MOD=&$PROTOCOL_SECTION['referencesModule'];
$CONTACT_MOD=&$PROTOCOL_SECTION['contactsLocationsModule'];

changeValue("clinical_trial","OFFICIAL_TITLE",$ID_MOD['officialTitle']);
$STR='<h4 style="margin-top:50px;">Brief summary:</h4><p>'.str_replace("\n","<br/>",$DESC_MOD['briefSummary']).'</p>';
if (isset($DESC_MOD['detailedDescription']))
{
	$STR.="<br/><h4>Detailed description</h4><p>".str_replace("\n","<br/>",$DESC_MOD['detailedDescription']).'</p>';
}
if (isset($PROTOCOL_SECTION['outcomesModule']))
{
	$OUT=$PROTOCOL_SECTION['outcomesModule'];
	
	if (isset($OUT['primaryOutcomes']))
	{
		$STR.='<br/><br/><h4>Primary outcomes:</h4>';
		foreach ($OUT['primaryOutcomes'] as $OCT)
		{
			$STR.='<table class="table table-sm" style="margin-bottom:2rem">
			<tr><th style="max-width:10%;width:10%">Measure:</th><td>'.$OCT['measure'].'</td></tr>
			<tr><th>Description:</th><td>'.$OCT['description'].'</td></tr>
			<tr><th>Time Frame:</th><td>'.$OCT['timeFrame'].'</td></tr></table>';

		}
		
	}
	if (isset($OUT['secondaryOutcomes']))
	{
		$STR.='<br/><h4>Secondar outcomes:</h4>';
		foreach ($OUT['secondaryOutcomes'] as $OCT)
		{
			$STR.='<table class="table table-sm" style="margin-bottom:2rem">
			<tr><th style="max-width:10%;width:10%">Measure:</th><td>'.$OCT['measure'].'</td></tr>
			<tr><th>Description:</th><td>'.$OCT['description'].'</td></tr>
			<tr><th>Time Frame:</th><td>'.$OCT['timeFrame'].'</td></tr></table>';

		}
		
	}
}
function cleanUp($STR){
$tab=explode("\n",$STR);

$STR='';//echo "<pre>";
for ($I=0;$I<count($tab);++$I)
{
	$tab[$I]=trim($tab[$I]);$tab[$I+1]=trim($tab[$I+1]);
	if ($tab[$I]==''&& $tab[$I+1]==''){$STR.='<br/>';continue;}
	if ($tab[$I]=='')continue;
	
	//echo $tab[$I]."|".$I.' |'.substr($tab[$I+1],1,1).'| |'.strtoupper(substr($tab[$I+1],1,1))."|\n";
	if (substr($tab[$I+1],0,1)!=strtoupper(substr($tab[$I+1],0,1)))$STR.=$tab[$I];
	else $STR.=$tab[$I]."<br/>";
}

preg_match_all('/\b([A-Z ]+)\b/', $STR, $matches);
$T=array_filter($matches[0]);
foreach ($T as $V) if (strlen($V)>5)$STR=str_replace($V,'<br/><span class="bold">'.$V.'</span>',$STR);
return $STR;
}

changeValue("clinical_trial","SUMMARY",$STR);
changeValue("clinical_trial","NCT_ID",$ID_MOD['nctId']);


$DEF=array(
	'startDateStruct'=>array('type'=>'struct','color'=>'blue','label'=>'Starting date'),
	'primaryCompletionDateStruct'=>array('type'=>'struct','color'=>'purple','label'=>'Primary completion date'),
	'completionDateStruct'=>array('type'=>'struct','color'=>'purple','label'=>'Completion date'),
	'studyFirstSubmitDate'=>array('type'=>'date','color'=>'blue','label'=>'Study First submit date'),
	'studyFirstSubmitQCDate'=>array('type'=>'date','color'=>'blue','label'=>'Study First submit QC date'),
	'studyFirstPostDateStruct'=>array('type'=>'struct','color'=>'blue','label'=>'Study First post date'),
	'resultsFirstSubmitDate'=>array('type'=>'date','color'=>'orange','label'=>'Results First submit date'),
	'resultsFirstSubmitQCDate'=>array('type'=>'date','color'=>'orange','label'=>'Results First submit QC date'),
	'resultsFirstPostDateStruct'=>array('type'=>'struct','color'=>'orange','label'=>'Results First post date'),
	'resultsFirstPostedQCCommentsDataStruct'=>array('type'=>'struct','color'=>'orange','label'=>'Results First posted QC comments data'),
	'distFirstSubmitDate'=>array('type'=>'date','color'=>'green','label'=>'Dist First submit date'),
	'distFirstSubmitQCDate'=>array('type'=>'date','color'=>'green','label'=>'Dist First submit QC date'),
	'distFirstPostDateStruct'=>array('type'=>'struct','color'=>'green','label'=>'Dist First post date'),
	'lastUpdateSubmitDate'=>array('type'=>'date','color'=>'brown','label'=>'Last Update submit date'),
	'lastUpdatePostDateStruct'=>array('type'=>'struct','color'=>'brown','label'=>'Last Update posted'),
);

$STR='';
$YEAR=strtotime(date("Y"));
$END=0;
foreach ($DEF as $RULE=>&$INFO)
{
	if (!isset($ST_MOD[$RULE]))continue;
	if ($INFO['type']=='struct')
	{
		$Y=strtotime($ST_MOD[$RULE]['date']);
		if ($Y<$YEAR)$YEAR=$Y;
		if ($Y>$END)$END=$Y;
		$STR.='<li data-timeline-node=\'{"start":"'.$ST_MOD[$RULE]['date'].'","end":"'.date("Y-m-d",strtotime($ST_MOD[$RULE]['date'].' +1 week')).'","row":1,"bgColor":"'.$INFO['color'].'","color":"#101010","label":"'.$INFO['label'].'"}\'></li>'."\n";
	}else
	{
		 $STR.='<li data-timeline-node=\'{"start":"'.$ST_MOD[$RULE].'","end":"'.date("Y-m-d",strtotime($ST_MOD[$RULE].' +1 week')).'","row":1,"bgColor":"'.$INFO['color'].'","color":"#101010","label":"'.$INFO['label'].'"}\'></li>'."\n";
		 $Y=strtotime($ST_MOD[$RULE]);
		if ($Y<$YEAR)$YEAR=$Y;
		if ($Y>$END)$END=$Y;
	}

}


changeValue("clinical_trial","START_TIME",date('Y-m',$YEAR).'-01');
changeValue("clinical_trial","END_TIME",date('Y',$END).'-12');
changeValue("clinical_trial","TIMELINE",$STR);


$STR=array();
foreach ($DESIGN_MOD['phases'] as $f)
{
	switch ($f)
	{
		case 'NA':$STR[]='Non Applicable';break;
		case 'EARLY_PHASE1':$STR[]='Early Phase 1';break;
		case 'PHASE1':$STR[]='Phase 1';break;
		case 'PHASE2':$STR[]='Phase 2';break;
		case 'PHASE3':$STR[]='Phase 3';break;
		case 'PHASE4':$STR[]='Phase 4';break;
	}
}



changeValue("clinical_trial","CLINICAL_PHASE",implode("<br/>",$STR));
changeValue("clinical_trial","START_DATE",$ST_MOD['startDateStruct']['date']);
if (isset($ST_MOD['completionDateStruct']))
changeValue("clinical_trial","COMPLETION_DATE",$ST_MOD['completionDateStruct']['date'].(($ST_MOD['completionDateStruct']['type']=='ESTIMATED')?' (estimated)':''));

$MAP=array('EXPANDED_ACCESS' => 'Expanded Access',
'INTERVENTIONAL'=>'Interventional',
'OBSERVATIONAL'=>'Observational');
changeValue("clinical_trial","STUDY_TYPE",$MAP[$DESIGN_MOD['studyType']]);
changeValue("clinical_trial","LINK",'https://classic.clinicaltrials.gov/ct2/show/'.$ID_MOD['nctId']);


$MAP=array('green'=>array('ACTIVE_NOT_RECRUITING'=>'Active, not recruiting',
'COMPLETED'=>'Completed'),
'deep-orange'=>array(
'ENROLLING_BY_INVITATION'=>'Enrolling by invitation',
'NOT_YET_RECRUITING'=>'Not yet recruiting',
'RECRUITING'=>'Recruiting'),
'red'=>array(
'SUSPENDED'=>'Suspended',
'TERMINATED'=>'Terminated',
'WITHDRAWN'=>'Withdrawn',
'NO_LONGER_AVAILABLE'=>'No longer available',
'WITHHELD'=>'Withheld'),
'blue'=>array(
'AVAILABLE'=>'Available',
'TEMPORARILY_NOT_AVAILABLE'=>'Temporarily not available',
'APPROVED_FOR_MARKETING'=>'Approved for marketing',
'UNKNOWN'=>'Unknown status'));



$STR='';
foreach ($MAP as $COLOR=>&$LIST)
{
	if (!isset($LIST[$ST_MOD['overallStatus']]))continue;
	$STR.='<span class="w3-text-'.$COLOR.'">'.$LIST[$ST_MOD['overallStatus']].'</span>';
}


changeValue("clinical_trial","STATUS",$STR);
changeValue("clinical_trial","ELIGIBILITY",
	str_replace("Inclusion Criteria:","<span style='font-weight:bold'>Inclusion Criteria:</span>",
	str_replace("Exclusion Criteria:","<span style='font-weight:bold'>Exclusion Criteria:</span>",
	convertSimpleText($ELIGIBILTY_MOD['eligibilityCriteria']))));

$MAP=array('FEMALE'=> 'Female',
'MALE'=>'Male',
'ALL'=>'Any gender');
changeValue("clinical_trial","GENDER",$MAP[$ELIGIBILTY_MOD['sex']]);
changeValue("clinical_trial","MIN_AGE",$ELIGIBILTY_MOD['minimumAge']);
changeValue("clinical_trial","MAX_AGE",$ELIGIBILTY_MOD['maximumAge']);
changeValue("clinical_trial","H_V",$ELIGIBILTY_MOD['healthyVolunteers']);
if (is_array($COND_MOD))
changeValue("clinical_trial","CONDITION",implode("<br/>",$COND_MOD['conditions']));

changeValue("clinical_trial","SOURCE",$ID_MOD['organization']['fullName']);



function convertSimpleText($str)
{
$res=explode("\n",$str);
$UL_OPEN=false;
foreach ($res as &$line)
{

        if (substr($line,0,1)=='*')
        {
                
                if (!$UL_OPEN)
                {
                        $UL_OPEN=true;
$line='<ul><li>'.substr($line,1).'</li>';
                }
                else $line='<li>'.substr($line,1).'</li>';
        }
        if ($line=='' && $UL_OPEN){$line='</ul>';$UL_OPEN=false;}
}
if ($UL_OPEN)$res[]= '</ul>';

return str_replace('</li><br/>','</li>',implode("<br/>",$res));
}


				
$STR='';
if (isset($DESIGN_MOD['enrollmentInfo']))$STR.='<tr><th class="w3-right-align">Enrollment:</th><td>'.$DESIGN_MOD['enrollmentInfo']['count'].(($DESIGN_MOD['enrollmentInfo']['type']=='ESTIMATED')?' (estimated)':'').'</td></tr>'."\n";
if (isset($MODULE_DATA['study_design_info']))
{
	$E=&$MODULE_DATA['study_design_info'];
	foreach ($E as $K=>$V)
	{
		$STR.='<tr><th class="w3-right-align">'.ucfirst(str_replace("_"," ",$K)).':</th><td>'.$V.'</td></tr>'."\n";
	}
	

}




if (isset($ARMS_MOD['armGroups']))$STR.='<tr><th class="w3-right-align">Number of arms:</th><td>'.count($ARMS_MOD['armGroups']).'</td></tr>'."\n";
changeValue("clinical_trial","ADDITIONAL_DESCRIPTORS",$STR);


























$CLEAN='';
$STR_JS='';
$DRUG_ID=0;
$DRUG_MAP=array();
$ORDER=array(array('DrugBank','Complete'),array('DrugBank','Clinical'),array('DrugBank','Simple'),array('OpenTargets','Summary'));
		
if (isset($MODULE_DATA['DRUG']))
{
	$STR='';
	$DR_LIST=&$MODULE_DATA['DRUG'];
	$NDRUG=count($DR_LIST);$STEP=12/$NDRUG;
	if ($NDRUG>3)$STEP=4;
	foreach ($DR_LIST as $NDR=>$DR)
	{
		$DRUG_ID++;
		$DRUG_MAP[$DR['DRUG_ENTRY_ID']]=$DRUG_ID;
		$CLEAN.='$("#drug_'.$DRUG_ID.'").removeClass("highlight");$("#drug_'.$DRUG_ID.'").removeClass("highlight_u");';
		$STR.='<div id="drug_'.$DRUG_ID.'" class="w3-container w3-center w3-col-600 w3-border w3-col s12 l12 m12">';
		$STR.='<h5>'.$DR['DRUG_PRIMARY_NAME'].'</h5>';
		$STR.='<div class="w3-col s12 l3 m3">';
		$STR.='<div id="CPD_IMG_'.$NDR.'"';
		if ($DR['DRUG_TYPE']!='S'){
			$STR.=' style="max-width:250px; margin:0 auto">';
			switch($DR['DRUG_TYPE'])
			{
				case "A": $STR.='<div class="sprite antibody_icon_l"></div>';break;
				case "S": $STR.='<div class="sprite sm_icon_l"></div>';break;
				case "O":$STR.='<div class="sprite o_icon_l"></div>';break;break;
				case "OS":$STR.='<div class="sprite os_icon_l"></div>';break;break;
				case "unknown":
				case "Unknown": $DRUG_TYPE='UN';break;
				case "Cell": $DRUG_TYPE='CE';break;
				case "Gene": $DRUG_TYPE='GN';break;
				case "Enzyme":
				case 'PR': $STR.='<div class="sprite protein_icon_l"></div>';break;
				break;
			}
		}else $STR.='>';
		
		$STR.='</div></div><div class="w3-col s12 l3 m3">';
		$STR.='<table class="table">';
		
		$DESC_STR='';
		$FOUND=false;
		foreach ($ORDER as $T)
		{
			foreach ($DR['DESC']['DESCRIPTION'] as &$DESC)
			{
				if ($DESC['SOURCE_NAME']!=$T[0]|| $DESC['TEXT_TYPE']!=$T[1])continue;
				if($DESC['TEXT_DESCRIPTION']=='')continue;
				$FOUND=true;
				$DESC_STR=$DESC['TEXT_DESCRIPTION'].'<br/>Source:'.$DESC['SOURCE_NAME'].' - '.$DESC['TEXT_TYPE'];
				break;
			}
			if ($FOUND)break;
		}
		if (!$FOUND)
		foreach ($DR['DESC']['DESCRIPTION'] as &$DESC)
		{
			if (isset($DESC['FOUND']))continue;

			if($DESC['TEXT_DESCRIPTION']=='')continue;
			$DESC_STR=$DESC['TEXT_DESCRIPTION'].'<br/>Source:'.$DESC['SOURCE_NAME'].' - '.$DESC['TEXT_TYPE'];
			break;
		}

		
		$STR.='<tr><th>Is Approved:</th><td>'.(($DR['IS_APPROVED']=='T')?"Yes":"No").'</td></tr>
		<tr><th>Is Withdrawn:</th><td>'.(($DR['IS_WITHDRAWN']=='T')?"Yes":"No").'</td></tr>
		<tr><th>Maximal clinical phase:</th><td>'.$DR['MAX_CLIN_PHASE'].'</td></tr></table>
		</div>
		<div class="w3-col s12 l6 m6">
		<span class="bold " style="float:left;padding-right:3px; ">Description: </span><p class="w3-justify">'.$DESC_STR.'</p>';
		$STR.='</div>';
		if (isset($DR['SM'])) {
			foreach ($DR['SM'] as &$SM_E)
			if (isset($SM_E['FULL_SMILES']))
			{
				$STR_JS.='getCompoundImage("'.$SM_E['FULL_SMILES'].'","CPD_IMG_'.$NDR.'",250,230);'."\n";
					break;
			}

		}
		$STR.='</div>';
	}
	changeValue("clinical_trial","DRUGS",$STR);
	

}else changeValue("clinical_trial","DRUGS",'<div class="alert alert-info">No reported drug(s)</div>');
/*
if (isset($MODULE_DATA['intervention']['intervention_name']))
changeValue("clinical_trial","INTER_TYPE",$MODULE_DATA['intervention']['intervention_name'].' '.$MODULE_DATA['intervention']['intervention_type']); */



$STR='<h4 style="width:100%;text-align:center;margin-top:50px;">Arms</h4><table class="table">
<thead><tr><th>Label</th><th>Type</th><th>Description</th></tr></thead><tbody>';

$MAP_ARM=array();
foreach ($MODULE_DATA['ARMS'] as $K=> &$ARM)
{
	$MAP_ARM[$ARM['CLINICAL_TRIAL_ARM_ID']]=$K;
	$CLEAN.='$("#arm_'.$K.'").removeClass("highlight");$("#arm_'.$K.'").removeClass("highlight_u");';
	$STR.='<tr id="arm_'.$K.'"><th>'.$ARM['ARM_LABEL'].'</th><td>'.$ARM['ARM_TYPE'].'</td><td>'.$ARM['ARM_DESCRIPTION'].'</td></tr>';
	
}
$STR.='</tbody></table>';
$STR.='<h4 style="width:100%;text-align:center;margin-top:50px;">Interventions</h4><table class="table">
<thead><tr><th>Type</th><th>Name</th><th>Description</th></tr></thead><tbody>';
$MAP_INTER=array();
foreach ($MODULE_DATA['INTERVENTION'] as $K=>&$INTER)
{
	$CLEAN.='$("#inter_'.$K.'").removeClass("highlight");$("#inter_'.$K.'").removeClass("highlight_u");';
	$MAP_INTER[$INTER['CLINICAL_TRIAL_INTERVENTION_ID']]=$K;
	$STR.='<tr id="inter_'.$K.'"><th>'.$INTER['INTERVENTION_TYPE'].'</th><td>'.$INTER['INTERVENTION_NAME'].'</td><td>'.$INTER['INTERVENTION_DESCRIPTION'].'</td></tr>';
}
$STR.='</tbody></table>';


$JS_ALL=array();
foreach ($MODULE_DATA['ARM_INTERVENTION'] as $K=> &$ARM_I)
{
	$ARM_POS=$MAP_ARM[$ARM_I['CLINICAL_TRIAL_ARM_ID']];
	$INTER_POS=$MAP_INTER[$ARM_I['CLINICAL_TRIAL_INTERVENTION_ID']];
	if (!isset($JS_ALL['A_'.$ARM_POS]))$JS_ALL['A_'.$ARM_POS]='$("#arm_'.$ARM_POS.'").on("mouseover",function(){$("#arm_'.$ARM_POS.'").addClass("highlight_u");';
	if (!isset($JS_ALL['I_'.$INTER_POS]))$JS_ALL['I_'.$INTER_POS]='$("#inter_'.$INTER_POS.'").on("mouseover",function(){$("#inter_'.$INTER_POS.'").addClass("highlight_u");';
	$JS_ALL['A_'.$ARM_POS].='$("#inter_'.$INTER_POS.'").addClass("highlight");';
	$JS_ALL['I_'.$INTER_POS].='$("#arm_'.$ARM_POS.'").addClass("highlight");';
	$INTER_ENTRY=&$MODULE_DATA['INTERVENTION'][$ARM_I['CLINICAL_TRIAL_INTERVENTION_ID']];
	if (isset($INTER_ENTRY['DRUG']))
	foreach ($INTER_ENTRY['DRUG'] as $DRUG_DBID)
	{
		$DRUG_POS=$DRUG_MAP[$DRUG_DBID];
		$JS_ALL['A_'.$ARM_POS].='$("#drug_'.$DRUG_POS.'").addClass("highlight");';
		$JS_ALL['I_'.$INTER_POS].='$("#drug_'.$DRUG_POS.'").addClass("highlight");';
		if (!isset($JS_ALL['D_'.$DRUG_POS]))$JS_ALL['D_'.$DRUG_POS]='$("#drug_'.$DRUG_POS.'").on("mouseover",function(){$("#drug_'.$DRUG_POS.'").addClass("highlight_u");';
		$JS_ALL['D_'.$DRUG_POS].='$("#arm_'.$ARM_POS.'").addClass("highlight");';
		$JS_ALL['D_'.$DRUG_POS].='$("#inter_'.$INTER_POS.'").addClass("highlight");';
	}
		

	
}

foreach ($JS_ALL as $K=>&$V)
{
	$V.='});'."\n";
	$STR_JS.=$V;
	$tab=explode("_",$K);
	if ($tab[0]=='A')	$STR_JS.='$("#arm_'.$tab[1].'").on("mouseout",function(){cleanHighlight()});'."\n";
	else if ($tab[0]=='I') $STR_JS.='$("#inter_'.$tab[1].'").on("mouseout",function(){cleanHighlight()});'."\n";
	else if ($tab[0]=='D') $STR_JS.='$("#drug_'.$tab[1].'").on("mouseout",function(){cleanHighlight()});'."\n";
}


changeValue("clinical_trial","ARMS",$STR."\n".'<script>'.$STR_JS.';'."\n".' function cleanHighlight(){'.$CLEAN.'}</script>');











$STR='';
if (isset($MODULE_DATA['TARGET']))
{
	$HAS_DATA=false;
	$STR='<table id="clinical_trial_targets" class="w3-w3-table w3-striped w3-bordered w3-border w3-hoverable w3-white" style="width:100%;text-align:center"><thead><tr>
	<th>Gene ID</th><th>Symbol</th><th>Full Name</th><th>Organism</th></tr></thead><tbody>';

	$DR_LIST=&$MODULE_DATA['TARGET'];
	foreach ($DR_LIST as $E)
	{
		$STR.='<tr>';
		$HAS_DATA=true;
		foreach ($E as $HEAD=>$V)
		{
			if ($HEAD=='GN_ENTRY_ID')continue;
			
			$STR.='<td>';
		if (($HEAD=='SYMBOL' || $HEAD=='GENE_ID'))$STR.='<a class="blk_font" href="/GENEID/'.$E['GENE_ID'].'">'.$V.'</a>';
		else $STR.=$V;
		$STR.='</td>';
		}$STR.='</tr>';
	}
	$STR.='</tbody></table>';
	if ($HAS_DATA){
	if (count($DR_LIST)>5)$STR_JS.='$("#clinical_trial_targets").DataTable({responsive:true});'."\n";
	changeValue("clinical_trial","TARGET",$STR);
}else changeValue("clinical_trial","TARGET",'<div class="alert alert-info">No reported targets(s)</div>');
}else changeValue("clinical_trial","TARGET",'<div class="alert alert-info">No reported targets(s)</div>');


// $STR='';
// if (isset($MODULE_DATA['DISEASE']))
// {
// 	$STR='';

// 	$DR_LIST=&$MODULE_DATA['DISEASE'];
// 	foreach ($DR_LIST as $E)
// 	{
// 		$STR.='<div class="w3-container w3-col s12">';
// 		$USER_INPUT['PAGE']['VALUE']=$E['DISEASE_NAME'];
// 		$STR.=loadHTMLAndRemove("DISEASE_OVERVIEW");;
// 		$STR.='</div>';
// 	}
	
// 	changeValue("clinical_trial","DISEASE",$STR);
// }else changeValue("clinical_trial","DISEASE",'<div class="alert alert-info">No reported disease(s)</div>');



$STR='';
if (isset($REF_MOD['references'])){

$GROUPS=array();
foreach ($REF_MOD['references'] as $RF)
{
	$GROUPS[$RF['type']][]=$RF['pmid'];
}
foreach ($GROUPS as $type=>&$list)
{
	$USER_INPUT['PAGE']['VALUE']=implode("_",$list);
	$STR.='<h4>'.ucfirst(strtolower($type)).'</h4><hr/>';
	$STR.=loadHTMLAndRemove("PUBLICATION_BATCH");
}
changeValue("clinical_trial","PAPERS",$STR);
}
else changeValue("clinical_trial","PAPERS",'<div class="alert alert-info">No reported publication(s)</div>');


changeValue("clinical_trial","JS",$STR_JS);


$USER_INPUT['PARAMS']=array('CLINICAL',$ID_MOD['nctId']);
changeValue("clinical_trial","NEWS_FEED",loadHTMLAndRemove("PUBLI_NEWS"));


/*
<link rel="stylesheet" href="/require/css/jquery.timeline.min.css"/>

<script src="/require/js/jquery.timeline.min.js"></script>
<!-- <div id="my-timeline">
		<ul class="timeline-events">
  ${TIMELINE}    </ul><!-- /.timeline-events -->
</div>
<div class="timeline-event-view"></div>

</div>

<script type="text/javascript">
$("#my-timeline").Timeline({
	type: "point",
	minGridSize: 50,
startDatetime: "${START_TIME}",
endDatetime: "${END_TIME}",
scale: "year",
rows: 1,

ruler: {
	truncateLowers: false,
	top: {
		lines:      [ "year","month" ],
		height:     26,
		fontSize:   15,
		color:      "#333",
		background: "transparent",
		locale:     "en-US",
		format:     {
			timeZone: "UTC", weekday: "short", year: "numeric", month: "numeric", day: "numeric"
		}
	},
   
},}
);

</script> -->*/

?>