<?php
if (!defined("BIORELS")) header("Location:./");


ini_set('memory_limit','2500M');
$TRANSCRIPT_INPUT=$USER_INPUT['PAGE']['VALUE'];
if (!checkRegex($TRANSCRIPT_INPUT,'REGEX:TRANSCRIPT'))throw new Exception("Unrecognized Transcript Name",ERR_TGT_USR); // echo json_encode($USER_INPUT['PARAMS']);exit;
 
$MODULE_DATA=array();
$TR_DATA=getTranscriptInfo($TRANSCRIPT_INPUT);
if (count($TR_DATA)==0)throw new Exception("Transcript ".$TRANSCRIPT_INPUT.' not found');
$MODULE_DATA['INFO']=$TR_DATA[0];




$MODULE_DATA['MATCHING_SEQ']=array();

$RANGE_DNA=array('START'=>$MODULE_DATA['INFO']['START_POS'],'END'=>$MODULE_DATA['INFO']['END_POS']);$HIGHLIGHT=array();
$RANGE_RNA=array('START'=>-1,'END'=>-1);$HAS_RNA=false;
$W_HEAD=false;
$W_MUT=false;
$HAS_DNA=false;
$W_PROT=false;

$MAX_MISMATCH=2;
$W_CLINVAR=false;
$SEQ_DELIM="\t";
$SEQ_TYPE='2';
$W_PROT_ORTHO=false;
$W_PROT_ANNOT=false;
$W_PROT_SIM=false;
$list=array();
$N_P=count($USER_INPUT['PARAMS']);
for ($I=0;$I<$N_P;++$I)
{
  if ($USER_INPUT['PARAMS'][$I]=="DNA")
  {
    if ($I+2>=$N_P)throw new Exception("DNA Range is not provided",ERR_TGT_SYS);
    $RANGE_DNA['START']=$USER_INPUT['PARAMS'][$I+1];
    $RANGE_DNA['END']=$USER_INPUT['PARAMS'][$I+2];
    $MODULE_DATA['LENGTH']=$RANGE_DNA['END']-$RANGE_DNA['START']+1;
    $HAS_DNA=true;
    $I+=2;
  }
  if ($USER_INPUT['PARAMS'][$I]=="RNA")
  {
    if ($I+2>=$N_P)throw new Exception("RNA Range is not provided",ERR_TGT_SYS);
    $RANGE_RNA['START']=$USER_INPUT['PARAMS'][$I+1];
    $RANGE_RNA['END']=$USER_INPUT['PARAMS'][$I+2];
    $MODULE_DATA['LENGTH']=$RANGE_RNA['END']-$RANGE_RNA['START']+1;
    $HAS_RNA=true;
    $I+=2;
  }
  if ($USER_INPUT['PARAMS'][$I]=="WITH_PROTEIN")$W_PROT=true;
  if ($USER_INPUT['PARAMS'][$I]=="WITH_PROTEIN_ANNOT")$W_PROT_ANNOT=true;
  if ($USER_INPUT['PARAMS'][$I]=="WITH_CLINVAR")$W_CLINVAR=true;
  if ($USER_INPUT['PARAMS'][$I]=="WITH_VARIANT")$W_MUT=true;
  if ($USER_INPUT['PARAMS'][$I]=="WITH_HEADER")$W_HEAD=true;
  if ($USER_INPUT['PARAMS'][$I]=="WITH_PROTEIN_ORTHOLOG")$W_PROT_ORTHO=true;
  if ($USER_INPUT['PARAMS'][$I]=="WITH_PROTEIN_SIM")$W_PROT_SIM=true;
  
  if ($USER_INPUT['PARAMS'][$I]=="HIGHLIGHT")
  {
    if ($I+1>=$N_P)throw new Exception("DNA Range is not provided",ERR_TGT_SYS);
    $HIGHLIGHT[]=$USER_INPUT['PARAMS'][$I+1];

    $I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=="SEQ_DELIM")
  {
    if ($I+1>=$N_P)throw new Exception("Sequence delimiter not provided",ERR_TGT_SYS);
    $V=$USER_INPUT['PARAMS'][$I+1];
    if ($V=='tab')$SEQ_DELIM="\t";
    else if ($V=='comma')$SEQ_DELIM=',';
    else if ($V=='space')$SEQ_DELIM=' ';
    else if ($V=='semicolon')$SEQ_DELIM=';';
    else throw new Exception("unrecognized delimiter");
    $I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=="MATCHING")
  {
    if ($I+1>=$N_P)throw new Exception("Matching sequence is not provided",ERR_TGT_SYS);
    $MODULE_DATA['MATCHING_SEQ'][$USER_INPUT['PARAMS'][$I+1]]=array();
    $I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=="MAX_MISMATCH")
  {
    if ($I+1>=$N_P)throw new Exception("Maximum number of mismatch is not provided",ERR_TGT_SYS);
    $V=$USER_INPUT['PARAMS'][$I+1];
    if (!is_numeric($V))throw new Exception("Maximum number of mismatch must be numeric",ERR_TGT_SYS);
    if ($V<0||$V>5)throw new Exception("Maximum number of mismatch must be between 0 and 5",ERR_TGT_SYS);
    $MAX_MISMATCH=$V;
    $I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=="SEQ_TYPE")
  {
    if ($I+1>=$N_P)throw new Exception("Match sequence input format not provided",ERR_TGT_SYS);
    $V=$USER_INPUT['PARAMS'][$I+1];
    if (!is_numeric($V))throw new Exception("Match sequence input format must be numeric",ERR_TGT_SYS);
    if ($V<0||$V>4)throw new Exception("Match sequence input must be between 0 and 4",ERR_TGT_SYS);
    $SEQ_TYPE=$V;
    $I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=='SEQ_MATCH')
  {
    if ($I+1>=$N_P)throw new Exception("Matching sequence list is not provided",ERR_TGT_SYS);
    $list=explode("\n",str_replace("\r","",$USER_INPUT['PARAMS'][$I+1]));
   // foreach ($list as $sq)$MODULE_DATA['MATCHING_SEQ'][$sq]=array();
    $I+=1;
  }
}


//echo $list;


if ($list!=array())
{
  
  $NM=0;
  $MODULE_DATA['POTENCY_RANGE']=array('MAX'=>-100000,'MIN'=>100000);
  foreach ($list as $line)
  {
    if ($line=='')continue;
    $line=str_replace("\r","",$line);
    $tab=explode($SEQ_DELIM,$line);
    
    ++$NM;
    switch ($SEQ_TYPE)
    {
      case 1:
        if ($tab[0]=='')break;
        $MODULE_DATA['MATCHING_SEQ'][$NM]=array('INPUT'=>array('SEQ'=>$tab[0],'NAME'=>''),'RES'=>array());break;
      case 2: 
        if ($tab[0]=='')break;
        if ($tab[1]=='')break;
        $MODULE_DATA['MATCHING_SEQ'][$NM]=array('INPUT'=>array('SEQ'=>$tab[0],'NAME'=>$tab[1]),'RES'=>array());break;
      case 3: 
        if ($tab[0]=='')break;
        if ($tab[1]=='')break;
        if ($tab[2]=='')break;
        $MODULE_DATA['MATCHING_SEQ'][$NM]=array('INPUT'=>array('SEQ'=>$tab[0],'NAME'=>$tab[1],'POTENCY'=>$tab[2]),'RES'=>array());
      $MODULE_DATA['POTENCY_RANGE']['MIN']=min($MODULE_DATA['POTENCY_RANGE']['MIN'],$tab[2]);
      $MODULE_DATA['POTENCY_RANGE']['MAX']=max($MODULE_DATA['POTENCY_RANGE']['MAX'],$tab[2]);
      break;
      case 4: 
        if ($tab[0]=='')break;
        if ($tab[1]=='')break;
        $MODULE_DATA['MATCHING_SEQ'][$NM]=array('INPUT'=>array('SEQ'=>'','NAME'=>$tab[0],'POTENCY'=>$tab[1]),'RES'=>array());
      $MODULE_DATA['POTENCY_RANGE']['MIN']=min($MODULE_DATA['POTENCY_RANGE']['MIN'],$tab[1]);
      $MODULE_DATA['POTENCY_RANGE']['MAX']=max($MODULE_DATA['POTENCY_RANGE']['MAX'],$tab[1]);
      break;
    
    }
  }

  
 
  
}


if ($RANGE_DNA['START'] < $MODULE_DATA['INFO']['START_POS'])$RANGE_DNA['START']=$MODULE_DATA['INFO']['START_POS'];
if ($RANGE_DNA['END'] >$MODULE_DATA['INFO']['END_POS'])$RANGE_DNA['END']=$MODULE_DATA['INFO']['END_POS'];

$MODULE_DATA['PARAMS']=array('WITH_PROTEIN'=>$W_PROT,'WITH_MUTATION'=>$W_MUT,'WITH_HEADER'=>$W_HEAD,'RANGE'=>$RANGE_RNA);

$MODULE_DATA['SEQ']=array();

$RAW_SEQ=array();
if ($HAS_RNA)
{
  
  $RAW_SEQ=getTranscriptSequenceRange($TRANSCRIPT_INPUT,$RANGE_RNA['START'],$RANGE_RNA['END'],'RNA',isset($USER_INPUT['PORTAL']['DATA']['GENE_ID'])?$USER_INPUT['PORTAL']['DATA']['GENE_ID']:-1);
  $MAX_POS=0;
  
  foreach ($RAW_SEQ['SEQUENCE'] as &$SQ)$MAX_POS=max($SQ['SEQ_POS'],$MAX_POS);
  
  if ($RANGE_RNA['END']>$MAX_POS)
  {
    $RANGE_RNA['END']=$MAX_POS;
    $MODULE_DATA['LENGTH']=$RANGE_RNA['END']-$RANGE_RNA['START']+1;
  }
}
else         $RAW_SEQ=getTranscriptSequenceRange($TRANSCRIPT_INPUT,$RANGE_DNA['START'],$RANGE_DNA['END'],'DNA');

$MAP=array();
foreach ($RAW_SEQ['SEQUENCE'] as $POS_TR=>&$NUCL_INFO)
{
  
  //if (!isset($MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]))continue;
// Array ( [TRANSCRIPT_POS_ID] => 1886351012 [NUCL] => C [CHR_POS] => 55966889 ) 
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['T']=$NUCL_INFO['NUCL'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['TRP']=$POS_TR;
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['SEQ_POS']=$NUCL_INFO['SEQ_POS'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['TRID']=$NUCL_INFO['TRANSCRIPT_POS_ID'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['EXON']=$NUCL_INFO['EXON_ID'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['TYPE']=$NUCL_INFO['TYPE'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['CHR_NUCL']=$NUCL_INFO['CHR_NUCL'];
  $MODULE_DATA['SEQ'][$NUCL_INFO['CHR_POS']]['CHRP_DBID']=$NUCL_INFO['CHR_SEQ_POS_ID'];
  $MAP[$POS_TR]=$NUCL_INFO['CHR_POS'];
}


$SEQ_MATCH='';$SEQ_SHIFT=0;
if ($W_HEAD||$MODULE_DATA['MATCHING_SEQ']!=array())
{
  

  $TMP=getTranscriptSequence($TRANSCRIPT_INPUT);
  
  $MODULE_DATA['TR_BLOCK']=array('EXONS'=>$TMP['EXONS'],'POS_TYPE'=>$TMP['POS_TYPE'],'LEN'=>count($TMP['SEQUENCE']));
  
  
  /// If we want to match sequences against the transcript, we need the transcript sequence
  /// However, we need to consider query sequence starting before the region or finishing after.
  $SEQ_SHIFT=0;
  foreach ($MODULE_DATA['MATCHING_SEQ'] as $SQ=>&$INFO)$SEQ_SHIFT=max($SEQ_SHIFT,strlen($INFO['INPUT']['SEQ']));
  $START_SEQ_MATCH=max(1,$RANGE_RNA['START']-$SEQ_SHIFT);


  //print_R($TMP);
  $MIN_RANGE=min(max(array_keys($TMP['SEQUENCE'])),$RANGE_RNA['END']+$SEQ_SHIFT);
  for ($I=$START_SEQ_MATCH;$I<=$MIN_RANGE;++$I)
  {
    $SEQ_MATCH.=$TMP['SEQUENCE'][$I]['NUCL'];
  }
  
  $TMP=array();
  


}
if ($MODULE_DATA['MATCHING_SEQ']!=array())
{
  
  $TMP=array();
  foreach ($MODULE_DATA['MATCHING_SEQ'] as &$SQ_RES)
  {
    $TMP=array();
    $LEN=strlen($SQ_RES['INPUT']['SEQ']);
    $LEN_QUERY=strlen($SEQ_MATCH);
    $POSSIBLE_MATCH=array();
    for ($I=0;$I<$LEN_QUERY-$LEN+1;++$I)
    {
      $QUERY_SQ=substr($SEQ_MATCH,$I,$LEN);
      $POSSIBLE_MATCH[$QUERY_SQ]=$I;
    }

    
    genAltMatch(str_replace("U","T",$SQ_RES['INPUT']['SEQ']),$MAX_MISMATCH,$POSSIBLE_MATCH,$SQ_RES,true,$START_SEQ_MATCH);
    
    // for ($I=0;$I<$LEN_QUERY-$LEN;++$I)
    // {
    //   $QUERY_SQ=substr($SEQ_MATCH,$I,$LEN);
    //   for ($MM=0;$MM<=$MAX_MISMATCH;++$MM)
    //   {
    //   if (!isset($TMP[$MM][$QUERY_SQ]))continue;
    //   $SQ_RES['RES'][]=array($START_SEQ_MATCH+$I,$MM,$TMP[$MM][$QUERY_SQ],true);
    //   }
    // }
    $TMP=array();
    genAltMatch(getReverse(str_replace("U","T",$SQ_RES['INPUT']['SEQ'])),$MAX_MISMATCH,$POSSIBLE_MATCH,$SQ_RES,false,$START_SEQ_MATCH);
    // for ($I=0;$I<$LEN_QUERY-$LEN;++$I)
    // {
    //   $QUERY_SQ=substr($SEQ_MATCH,$I,$LEN);
    //   for ($MM=0;$MM<=$MAX_MISMATCH;++$MM)
    //   {
    //   if (!isset($TMP[$MM][$QUERY_SQ]))continue;
    //   $SQ_RES['RES'][]=array($START_SEQ_MATCH+$I,$MM,$TMP[$MM][$QUERY_SQ],false,$QUERY_SQ);
    //   }
    // }
  }
  $TMP=array();
  unset($TMP);

}
if ($W_CLINVAR)
{
  $MODULE_DATA['CLINVAR']= getClinVarFromTr($MODULE_DATA['INFO']['TRANSCRIPT_ID'],$RANGE_RNA);
  
}
if ($HAS_RNA)
{
  foreach ($MODULE_DATA['SEQ'] as $CHR_POS=>&$DATA)
  {
    if (!isset($DATA['TRP']))unset($MODULE_DATA['SEQ'][$CHR_POS]);
    else if (in_array($DATA['SEQ_POS'],$HIGHLIGHT))$MODULE_DATA['SEQ'][$CHR_POS]['SEL']=true;
  }
  if ($W_MUT)
{
 $MODULE_DATA['MUTS']= getTranscriptMutation($MODULE_DATA['INFO']['TRANSCRIPT_ID'],$RANGE_RNA);
 //print_r($MUTS);
}
}
if ($W_PROT)
{
 $MODULE_DATA['PROT']= getProteinInfoFromTr($MODULE_DATA['INFO']['TRANSCRIPT_ID'],$RANGE_RNA,$W_PROT_ANNOT);
 //print_r($MUTS);
}

if ($HAS_DNA)
{
  foreach ($MODULE_DATA['SEQ'] as $CHR_POS=>&$DATA)
  {
   // echo $CHR_POS.' ' .$HIGHLIGHT;
    if ($CHR_POS==$HIGHLIGHT)$MODULE_DATA['SEQ'][$CHR_POS]['SEL']=true;
  }
  
}
if ($W_PROT_ORTHO)
{
  $MODULE_DATA['ORTHO']=getProteinOrthoFromTr($MODULE_DATA['INFO']['TRANSCRIPT_ID'],$RANGE_RNA);
}
if ($W_PROT_SIM)
{
  $MODULE_DATA['SIM']=getProteinSimFromTr($MODULE_DATA['INFO']['TRANSCRIPT_ID'],$RANGE_RNA);
}

//  $STR_TT='';
//  $MODULE_DATA=json_decode($STR_TT,true);

?>