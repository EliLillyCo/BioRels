<?php

if (!defined("BIORELS")) header("Location:/");

echo '<pre>';

$FIELD=array('statusVerifiedDate','studyFirstSubmitDate','studyFirstSubmitQCDate','resultsFirstSubmitDate','resultsFirstSubmitQCDate','dispFirstSubmitDate','dispFirstSubmitQCDate','lastUpdateSubmitDate');
$FIELDS=array('startDateStruct','primaryCompletionDateStruct','completionDateStruct','studyFirstPostDateStruct','resultsFirstPostDateStruct','dispFirstPostDateStruct','lastUpdatePostDateStruct');
    
$FIELD_MAP=array(
    
    'studyFirstSubmitDate'=>'First submission date',
    'studyFirstSubmitQCDate'=>'First QC Date submission',
    'resultsFirstSubmitDate'=>'Submission Date when results were received',
    'resultsFirstSubmitQCDate'=>'Submission date where the submitted data met quality control criteria',
    'dispFirstSubmitDate'=>'Date where sponsor releasing data',
    'dispFirstSubmitQCDate'=>'Date of the version of record that met quality control criteria was released by sponsor',
    'lastUpdateSubmitDate'=>'Last submit date',
    'startDateStruct'=>'Start date','primaryCompletionDateStruct'=>'Primary completion date','completionDateStruct'=>'Completion date',
    'studyFirstPostDateStruct'=>'Study First Post date'
    ,'resultsFirstPostDateStruct'=>'Posted results date',
    'dispFirstPostDateStruct'=>'Disposition first posted date',
    'lastUpdatePostDateStruct'=>'Last update');
    

    $FIELD_COL=array(
    
        'studyFirstSubmitDate'=>'red',
        'studyFirstSubmitQCDate'=>'red',
        'resultsFirstSubmitDate'=>'blue',
        'resultsFirstSubmitQCDate'=>'blue',
        'dispFirstSubmitDate'=>'orange',
        'dispFirstSubmitQCDate'=>'orange',
        'lastUpdateSubmitDate'=>'grey',
        'startDateStruct'=>'green',
        'primaryCompletionDateStruct'=>'purple',
        'completionDateStruct'=>'purple',
        'studyFirstPostDateStruct'=>'blue'
        ,'resultsFirstPostDateStruct'=>'blue',
        'dispFirstPostDateStruct'=>'blue',
        'lastUpdatePostDateStruct'=>'green');
        

$MAX_DATE=0;
foreach ($MODULE_DATA  as $PHASE => &$LIST_CT)
    
    foreach ($LIST_CT as &$TMP)
{
    $CT=$TMP['STATUS'];

    foreach ($FIELD as $F)
    if (isset($CT[$F]))
    {
        
        $MAX_DATE=max($MAX_DATE,strtotime($CT[$F]));
       // echo $F." ".$CT[$F]."\t".$MAX_DATE."\n";
    }
    
    foreach ($FIELDS as $F)
    if (isset($CT[$F]['date']))
    {
        
        $MAX_DATE=max($MAX_DATE,strtotime($CT[$F]['date']));
       // echo $F." ".$CT[$F]['date']."\t".$MAX_DATE."\n";
    }
    
}


$CURR_YEAR=date('Y');
$DATE_END=date('Y',$MAX_DATE);
if ($DATE_END>$CURR_YEAR+5)$DATE_END=$CURR_YEAR+5;
echo $CURR_YEAR.' '.$DATE_END;


$DATA_TTL = array();
$N_TTL=0;

$JS=array();
// if ($DATE_END<$DATE)
// {
//     removeBlock("clinical_release","VALID");
//     return;
// }

    $NOW=time();
    if ($DATE_END!=$CURR_YEAR)$MONTHS=12*($DATE_END-$CURR_YEAR+1);
    else $MONTHS=12;

    $STR_ALL='<div class="w3-col s1 l1 m1" style="height:20px;">Clinical trial</div>
    <div class="w3-col s11 l11 m11" style="height:20px;position:relative">';
    $STR_L2='';
    for($Y=$CURR_YEAR;$Y<=$DATE_END;++$Y)
    {
        
        $LEFT=round((12*($Y-$CURR_YEAR))/$MONTHS*100,3);
        
        $STR_ALL.='<div style="height:20px;position:absolute;left:'.$LEFT.'%">|</div>';
        $LEFT=round((12+12*($Y-$CURR_YEAR))/$MONTHS*100,3);
        $STR_ALL.='<div style="height:20px;position:absolute;left:'.$LEFT.'%">|</div>';
        
        $LEFT=round((12*($Y-$CURR_YEAR))/$MONTHS*100,3);
        $WIDTH=round(12/$MONTHS*100,3);
        $STR_ALL.='<div style="height:20px;position:absolute;left:'.$LEFT.'%;width:'.$WIDTH.'%;text-align:center">'.$Y.'</div>';
        for ($Q=1;$Q<=4;++$Q)
        {
            $LEFT=round((($Q-1)*3+12*($Y-$CURR_YEAR))/$MONTHS*100,3);
            $CLASS='';
            $WIDTH=round(3/$MONTHS*100,3);
            if ($Q==1||$Q==3)$CLASS=' class="s_hide m_hide" ';
            $STR_L2.='<div '.$CLASS.' style="height:20px;position:absolute;left:'.$LEFT.'%;width:'.$WIDTH.'%;text-align:center">Q'.$Q.'</div>';
        }
    }
    $STR_ALL.='</div><div class="w3-col s1 l1 m1" style="height:20px;margin-bottom:10px;">Quarter</div>
    <div class="w3-col s11 l11 m11" style="height:20px;position:relative;margin-bottom:10px;">'.$STR_L2.'</div>
    <div class="w3-col s12 l12 m12" style="max-height:800px;overflow-y:auto">';

    $CT_ID=0;
    foreach ($MODULE_DATA  as $PHASE => &$LIST_CT)
    {
        $STR_HEAD='<div class="w3-col s1 l1 m1" style="height:40px;margin-top:20px;cursor:pointer;" onclick="showContent(\''.$PHASE.'\')">Phase ' .$PHASE.'</div>
       <div class="w3-col s11 l11 m11" style="position:relative;height:40px;margin-top:20px">';
        $STAT_HEAD=array();
        $STR_BODY='';
        $PHASE_LIST=array();
        $DS_TR=array();
        if ($USER_INPUT['PORTAL']['NAME'] == 'DISEASE')
        {
            $DS_MAP=array();
            foreach ($LIST_CT as $TRIAL_ID=>&$INFO)
            {
                $DS_TR[$TRIAL_ID]=$INFO['DISEASE_NAME'].'||'.$INFO['DISEASE_TAG'];
                $DS_MAP[$INFO['DISEASE_NAME'].'||'.$INFO['DISEASE_TAG']][]=$TRIAL_ID;
            }
            ksort($DS_MAP);
            $TMP=$LIST_CT;
            $LIST_CT=array();
            foreach ($DS_MAP as $DM=>&$LIST2)
            foreach ($LIST2 as $TR)$LIST_CT[$TR]=$TMP[$TR];
            unset($TMP);
        }
        
        //print_R($DS_TR);exit;
        $CURR_DISEASE='';
    foreach ($LIST_CT as $TR=>&$CT)
    {
        
        if ($USER_INPUT['PORTAL']['NAME'] == 'DISEASE')
        {
            if ($CURR_DISEASE!=$DS_TR[$TR])
            {
                ++$CT_ID;
        $PHASE_LIST[]=$CT_ID;
                $CURR_DISEASE=$DS_TR[$TR];
                $tab=explode("||",$CURR_DISEASE);
                $STR_BODY.='<div class="ct_'.$PHASE.' ct_all w3-col s12 m12 l12" id="CT_'.$CT_ID.'" style="display:none;"><div class="w3-col s2 m2 l2" style="text-align:center;font-weight:bold">'.$tab[0].'</div></div>';
            }
        }
        ++$CT_ID;
        $PHASE_LIST[]=$CT_ID;
        ++$N_TTL;
        $STR.='<div class="ct_'.$PHASE.' ct_all w3-col s12 m12 l12 " id="CT_'.$CT_ID.'" style="display:none;">
        <div class="w3-col s1 l1 m1 ttl_tr ttl_tag" title="'.$N_TTL.'" data-pos="'.$N_TTL.'" style="cursor:pointer;height:20px;">'.$CT['TRIAL_ID'].'</div>';
        $ORDER_DATE=array();
        
        $STR_EVENT='<table class="table"><tr><th>Title:</th></tr><tr><td>'.str_replace("'"," ",$CT['OFFICIAL_TITLE']).'</td></tr>
        <tr><td><a href="/CLINICAL_TRIAL/'.$CT['TRIAL_ID'].'">Link</a></td></tr></table>';
        $DATA_TTL[$N_TTL]=$STR_EVENT;

        foreach ($FIELD as $k)
    if (isset($CT['STATUS'][$k]))$ORDER_DATE[strtotime($CT['STATUS'][$k])][]=$k;
        foreach ($FIELDS as $k)
    if (isset($CT['STATUS'][$k]))$ORDER_DATE[strtotime($CT['STATUS'][$k]['date'])][]=$k;
    ksort($ORDER_DATE);
    $HAS_SOME=false;
    $STR.='<div class="w3-col s11 l11 m11" style="position:relative;height:20px;">';
    foreach ($ORDER_DATE as $DATE=>&$EVENT)
    {
        if ($DATE<$NOW){//echo "PASS\n";
            continue;}
            echo $DATE."\n";
        $Y=date('Y',$DATE);
        
        $M=date('m',$DATE);
        if ($Y>$MAX_DATE)continue;
        $QUARTER=date('Y',($DATE)).'-';
        if ($M>=1 && $M<=3)$QUARTER.=0;
        if ($M>3 && $M<=6)$QUARTER.=3;
        if ($M>6 && $M<=9)$QUARTER.=6;
        if ($M>9 && $M<=12)$QUARTER.=9;

        $STAT_HEAD[$QUARTER][$CT_ID]=true;
        
        $HAS_SOME=true;
        //print_R($EVENT);
        $Y=date('Y',$DATE);
        $M=date('m',$DATE);
       // echo $Y.' '.$M.' '.print_R($EVENT,true)."\n";
        if ($Y==$DATE)$LEFT=round(($M-0.2)/$MONTHS*100,3);
        
        else $LEFT=round(($M-0.2+12*($Y-$CURR_YEAR))/$MONTHS*100,3);
       // echo $Y.' '.($Y-$CURR_YEAR)."\t".$M."\t".$MONTHS."\n";
        //echo $LEFT."\n";
        //exit/;
        ++$N_TTL;
        $STR_EVENT='<table class="table">';
        $K='';
        foreach ($EVENT as $E)
        {
            $K=$E;
            $STR_EVENT.='<tr><td colspan="2">'.$FIELD_MAP[$E].'</td></tr><tr><td>'.$CT['STATUS'][$E]['date'].'</td><td>'.ucfirst(strtolower($CT['STATUS'][$E]['type'])).'</td></tr>';
            
        }
        $STR_EVENT.='</table>';
        

        $DATA_TTL[$N_TTL]=$STR_EVENT;
        $STR.='<div style="width:5px;min-height:16px; cursor:pointer;background-color:'.$FIELD_COL[$K].'; position:absolute; left:'.$LEFT.'%"
        class="ttl_tr ttl_tag" title="'.$N_TTL.'" data-pos="'.$N_TTL.'">|</div>';
        
    }
    $STR.=' </div></div>';
    if ($HAS_SOME)$STR_BODY.=$STR;
    }
    $JS[$PHASE]=$PHASE_LIST;

    
    foreach ($STAT_HEAD as $DATE=>$COUNT)
    {
       // echo $DATE;
        $Y=date('Y',strtotime($DATE));
        $M=date('m',strtotime($DATE));

        if ($Y==$DATE)$LEFT=round((0.5+$M/$MONTHS)*100,3);
        
        else $LEFT=round((0.5+$M+12*($Y-$CURR_YEAR))/$MONTHS*100,3);
        $STR_HEAD.='<div style="
        width: 25px;
min-height: 16px;
position: absolute;
border: 1px solid orange;
border-radius: 100px;cursor:pointer;
text-align: center; left:'.$LEFT.'%"
        data-info="'.$DATE.'" onclick="showContent(\''.$PHASE.'-'.$DATE.'\')">'.count($COUNT).'</div>';
        $JS[$PHASE.'-'.$DATE]=array_keys($COUNT);
    }
    $STR_HEAD.='</div>';
     $STR_ALL.=$STR_HEAD.
     $STR_BODY;

    // }
    }
    changeValue("clinical_release",'JS_DATA',json_encode($JS));
changeValue("clinical_release",'STATUS',$STR_ALL.'</div>');
changeValue("clinical_release", "TOOLTIPS", str_replace('\\','\\\\',str_replace("'", "\\'", json_encode(str_replace("\n", "", $DATA_TTL)))));


//exit;

?>