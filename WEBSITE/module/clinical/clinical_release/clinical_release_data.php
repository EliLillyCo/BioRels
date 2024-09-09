<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

//print_r($USER_INPUT);exit;

$WITH_CHILD= (in_array('WITH_CHILD',$USER_INPUT['PARAMS']));

if ($USER_INPUT['PORTAL']['NAME'] == 'GENE') {
    $GN_ENTRY_ID = $USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
    $MODULE_DATA = getClinicalTrialGene($GN_ENTRY_ID);
} else if ($USER_INPUT['PORTAL']['NAME'] == 'DISEASE') {
    $DISEASE_ENTRY = getDiseaseEntry($USER_INPUT['PORTAL']['VALUE'], true, true);
    $MODULE_DATA = getClinicalTrialDisease($DISEASE_ENTRY['DISEASE_ENTRY_ID'],$WITH_CHILD);
} else if ($USER_INPUT['PORTAL']['NAME'] == 'COMPOUND') {
    $COMPOUND_ENTRY = getCompoundInfo($USER_INPUT['PORTAL']['VALUE']);
    $MODULE_DATA = getClinicalTrialCompound($COMPOUND_ENTRY[0]['STRUCTURE']['SM_ENTRY_ID']);
} else if ($USER_INPUT['PORTAL']['NAME'] == 'DRUG') {

        $MODULE_DATA = getClinicalTrialDrug( $USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID']);
    
}


$FIELD=array('statusVerifiedDate','studyFirstSubmitDate','studyFirstSubmitQCDate','resultsFirstSubmitDate','resultsFirstSubmitQCDate','dispFirstSubmitDate','dispFirstSubmitQCDate','lastUpdateSubmitDate');
$FIELDS=array('startDateStruct','primaryCompletionDateStruct','completionDateStruct','studyFirstPostDateStruct','resultsFirstPostDateStruct','dispFirstPostDateStruct','lastUpdatePostDateStruct');
     
$DATA=array();
$NOW=time();
foreach ($MODULE_DATA as &$CT)
{

    $STATUS=isset($CT['CLINICAL_STATUS'])?strtolower($CT['CLINICAL_STATUS']):'N/A';
    //if (!in_array($STATUS,array('active, not recruiting','recruiting','not yet recruiting','enrolling by invitation','withheld','suspended')))continue;
    if (!isset($CT['STATUS']))continue;
    $CT['STATUS']=json_decode($CT['STATUS'],true);
    
    $MAX_DATE=0;
    foreach ($FIELD as $F)
    if (isset($CT['STATUS'][$F]))
    {
        
        $MAX_DATE=max($MAX_DATE,strtotime($CT['STATUS'][$F]));
       // echo $F." ".$CT[$F]."\t".$MAX_DATE."\n";
    }
    
    foreach ($FIELDS as $F)
    if (isset($CT['STATUS'][$F]['date']))
    {
        
        $MAX_DATE=max($MAX_DATE,strtotime($CT['STATUS'][$F]['date']));
       // echo $F." ".$CT[$F]['date']."\t".$MAX_DATE."\n";
    }
    if ($MAX_DATE<$NOW)continue;
    $DATA[$CT['CLINICAL_PHASE']][$CT['TRIAL_ID']]=$CT;

}

$MODULE_DATA=$DATA;
?>
