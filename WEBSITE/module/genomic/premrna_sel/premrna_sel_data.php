<?php
if (!defined("BIORELS")) header("Location:./");

ini_set('memory_limit','500M');
$TRANSCRIPT_INPUT=$USER_INPUT['PAGE']['VALUE'];

print_r($USER_INPUT);
$MODULE_DATA=array();
$MODULE_DATA['INFO']=getTranscriptInfo($TRANSCRIPT_INPUT)[0];
$MATCHING='';
$RANGE_DNA=array('START'=>$MODULE_DATA['INFO']['START_POS'],'END'=>$MODULE_DATA['INFO']['END_POS']);$HIGHLIGHT=-1;
$RANGE_RNA=array('START'=>-1,'END'=>-1);$HAS_RNA=false;
$N_P=count($USER_INPUT['PARAMS']);
for ($I=0;$I<$N_P;++$I)
{
  if ($USER_INPUT['PARAMS'][$I]=="DNA")
  {
    if ($I+2>=$N_P)throw new Exception("DNA Range is not provided",ERR_TGT_SYS);
    $RANGE_DNA['START']=$USER_INPUT['PARAMS'][$I+1];
    $RANGE_DNA['END']=$USER_INPUT['PARAMS'][$I+2];
    $I+=2;
  }
  if ($USER_INPUT['PARAMS'][$I]=="RNA")
  {
    if ($I+2>=$N_P)throw new Exception("RNA Range is not provided",ERR_TGT_SYS);
    $RANGE_RNA['START']=$USER_INPUT['PARAMS'][$I+1];
    $RANGE_RNA['END']=$USER_INPUT['PARAMS'][$I+2];
    $HAS_RNA=true;
    $I+=2;
  }
  if ($USER_INPUT['PARAMS'][$I]=="HIGHLIGHT")
  {
    if ($I+1>=$N_P)throw new Exception("DNA Range is not provided",ERR_TGT_SYS);
    $HIGHLIGHT=$USER_INPUT['PARAMS'][$I+1];
    $I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=="MATCHING")
  {
    if ($I+1>=$N_P)throw new Exception("Matching sequence is not provided",ERR_TGT_SYS);
    $MATCHING=$USER_INPUT['PARAMS'][$I+1];
    $I+=1;
  }
}

if ($RANGE_DNA['START'] < $MODULE_DATA['INFO']['START_POS'])$RANGE_DNA['START']=$MODULE_DATA['INFO']['START_POS'];
if ($RANGE_DNA['END'] >$MODULE_DATA['INFO']['END_POS'])$RANGE_DNA['END']=$MODULE_DATA['INFO']['END_POS'];

$RAW_SEQ=getPreMRNASequence($MODULE_DATA['INFO']['CHR_SEQ_ID'],$RANGE_DNA['START'],$RANGE_DNA['END'],$MODULE_DATA['INFO']['STRAND']);
$MODULE_DATA['SEQ']=array();

foreach ($RAW_SEQ as $POS =>$DT)
{
  $MODULE_DATA['SEQ'][$POS]=array('P'=>$DT);
  if (!$HAS_RNA && $POS==$HIGHLIGHT)$MODULE_DATA['SEQ'][$POS]['SEL']=true;
}
if (count($MODULE_DATA['SEQ'])>0)
{
$K=max(array_keys($MODULE_DATA['SEQ']));


$RAW_SEQ=array();
if ($HAS_RNA)$RAW_SEQ=getTranscriptSequenceRange($TRANSCRIPT_INPUT,$RANGE_RNA['START'],$RANGE_RNA['END'],'RNA');
else         $RAW_SEQ=getTranscriptSequenceRange($TRANSCRIPT_INPUT,$RANGE_DNA['START'],$RANGE_DNA['END'],'DNA');
$MAP=array();

foreach ($RAW_SEQ['SEQUENCE'] as $POS_TR=>&$NUCL_INFO)
{
  
  if (!isset($MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]))continue;
// Array ( [TRANSCRIPT_POS_ID] => 1886351012 [NUCL] => C [CHR_POS] => 55966889 ) 
  if ($MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['P']!=$NUCL_INFO['NUCL']) $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['T']=$NUCL_INFO['NUCL'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['TRP']=$POS_TR;
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['SEQ_POS']=$NUCL_INFO['SEQ_POS'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['TRID']=$NUCL_INFO['TRANSCRIPT_POS_ID'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['EXON']=$NUCL_INFO['EXON_ID'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['TYPE']=$NUCL_INFO['TYPE'];
  $MAP[$POS_TR]=$NUCL_INFO['CHR_POS'];
}
if ($HAS_RNA)
{
  foreach ($MODULE_DATA['SEQ'] as $CHR_POS=>&$DATA)
  {
    if (!isset($DATA['TRP']))unset($MODULE_DATA['SEQ'][$CHR_POS]);
    else if ($DATA['SEQ_POS']==$HIGHLIGHT)$MODULE_DATA['SEQ'][$CHR_POS]['SEL']=true;
  }
}
}
if ($MATCHING!='')
{
  
}
?>