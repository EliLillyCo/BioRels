<?php


if (!defined("BIORELS")) header("Location:./");

$TRANSCRIPT_NAME=$MODULE_DATA['INFO']['TRANSCRIPT_NAME'].(($MODULE_DATA['INFO']['TRANSCRIPT_VERSION']!=null)?'.'.$MODULE_DATA['INFO']['TRANSCRIPT_VERSION']:'');

if (count($MODULE_DATA['SEQ'])==0)
{
    removeBlock("premrna_sel","VALID");
    changeValue("premrna_sel","TRANSCRIPT_NAME",$TRANSCRIPT_NAME);
    return;
}
removeBlock("premrna_sel","INVALID");
if ($MODULE_DATA['INFO']['STRAND']=="+")
{
	changeValue("premrna_sel","LEFT","5'");
	changeValue("premrna_sel","RIGHT","3'");
}
if ($MODULE_DATA['INFO']['STRAND']=="-")
{
	changeValue("premrna_sel","LEFT","3'");
	changeValue("premrna_sel","RIGHT","5'");
}
$SEQ='<div class="seq">';
$STAT=array('EX'=>array(),'UT'=>array(),'IN'=>array());$K=-1;$INTRON=-1;$STARTED=false;$START_UT=false;$UT=-1;
foreach ($MODULE_DATA['SEQ'] as &$ENTRY)
{
	++$K;
	if (isset($ENTRY['SEL']))
	{
		if (isset($ENTRY['T']))$SEQ.='<span class="bold grey_bc">'.$ENTRY['T'].'</span>';
	else $SEQ.='<span class="bold grey_bc">'.$ENTRY['P'].'</span>';	
	}
	else {
	if (isset($ENTRY['T']))$SEQ.=$ENTRY['T'];
	else $SEQ.=$ENTRY['P'];
	}
	if (isset($ENTRY['EXON']))
	{
		$STARTED=false;
	if (!isset($STAT['EX'][$ENTRY['EXON']]))$STAT['EX'][$ENTRY['EXON']]=array('MIN'=>$K,'MAX'=>$K,'DONE'=>false);
	else $STAT['EX'][$ENTRY['EXON']]['MAX']=$K;
	}else 
	{
		$START_UT=false;
		if ($STARTED)$STAT['IN'][$INTRON]['MAX']=$K;
		else {++$INTRON;$STARTED=true;$STAT['IN'][$INTRON]=array('MIN'=>$K,'MAX'=>$K,'DONE'=>false);}
	}
	if (isset($ENTRY['TYPE']))
	{
		if ($START_UT)
		{
            if ($ENTRY['TYPE']==$STAT['UT'][$UT]['TYPE'])			$STAT['UT'][$UT]['MAX']=$K;
            else
            {
                ++$UT;$START_UT=true;
			    $STAT['UT'][$UT]=array('MIN'=>$K,'MAX'=>$K,'TYPE'=>$ENTRY['TYPE']);
            }
		}
		else {
			++$UT;$START_UT=true;
			$STAT['UT'][$UT]=array('MIN'=>$K,'MAX'=>$K,'TYPE'=>$ENTRY['TYPE']);}
	
	}else  $START_UT=false;
}
// print_r($MODULE_DATA['SEQ']);
// print_r($STAT);
$SEQ.='</div><div style="display:flex">';
$ratio=9.601907;
$CURR_V=1000000;$CURR_T='';
$HAS_CHANGE=false;
do
{
    $CURR_V=1000000;$CURR_T='';$CURR_ID=-1;
    foreach ($STAT['EX'] as $EX_ID=>$RANGE)
    {
        if ($RANGE['DONE'])continue;
        if ($RANGE['MIN']>$CURR_V)continue;
        $CURR_V=$RANGE['MIN'];
        $CURR_T='EX';
        $CURR_ID=$EX_ID;
    }
    foreach ($STAT['IN'] as $EX_ID=>$RANGE)
    {
        if ($RANGE['DONE'])continue;
        if ($RANGE['MIN']>$CURR_V)continue;
        $CURR_V=$RANGE['MIN'];
        $CURR_T='IN';
        $CURR_ID=$EX_ID;
    }
    if ($CURR_V==1000000)break;
    $HAS_CHANGE=true;
    $STAT[$CURR_T][$CURR_ID]['DONE']=true;
    $RANGE=$STAT[$CURR_T][$CURR_ID];
    if ($CURR_T=='EX')$SEQ.='<div class="transcript_seq_info '.(($CURR_ID%2)?"exon_even":"exon_odd").'" style="position:unset;left:'.($RANGE['MIN']*$ratio).'px; width:'.(($RANGE['MAX']-$RANGE['MIN']+1)*$ratio).'px">Exon '.$EX_ID.'</div>';
    else $SEQ.='<div class="transcript_seq_info intron" style="position:unset;left:'.($RANGE['MIN']*$ratio).'px; width:'.(($RANGE['MAX']-$RANGE['MIN']+1)*$ratio).'px">Intron</div>';

}while($HAS_CHANGE);
/*foreach ($STAT['EX'] as $EX_ID=>$RANGE)
{
$SEQ.='<div class="transcript_seq_info '.(($EX_ID%2)?"exon_even":"exon_odd").'" style="position:unset;left:'.($RANGE['MIN']*$ratio).'px; width:'.(($RANGE['MAX']-$RANGE['MIN']+1)*$ratio).'px">Exon '.$EX_ID.'</div>';
}

foreach ($STAT['IN'] as $EX_ID=>$RANGE)
{
$SEQ.='<div class="transcript_seq_info intron" style="position:unset;left:'.($RANGE['MIN']*$ratio).'px; width:'.(($RANGE['MAX']-$RANGE['MIN']+1)*$ratio).'px">Intron</div>';
}*/	
$SEQ.='</div><div class="">';
foreach ($STAT['UT'] as $EX_ID=>$RANGE)
{
    if ($RANGE['TYPE']=="3'UTR"||$RANGE['TYPE']=="5'UTR")$CLASS='UTR';
    else $CLASS='CDS';
$SEQ.='<div  class="transcript_seq_info trsq_'.$CLASS.'_view" style="left:'.($RANGE['MIN']*$ratio).'px; width:'.(($RANGE['MAX']-$RANGE['MIN']+1)*$ratio).'px">'.$RANGE['TYPE'].'</div>';
}	
$SEQ.='</div>';

changeValue("premrna_sel","SEQ",$SEQ);

?>