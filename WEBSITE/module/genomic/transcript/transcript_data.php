<?php
 if (!defined("BIORELS")) header("Location:/");


 $MODULE_DATA=array();
$TRANSCRIPT_INPUT=$USER_INPUT['PAGE']['VALUE'];
if (!checkRegex($TRANSCRIPT_INPUT,'REGEX:TRANSCRIPT'))throw new Exception("Unrecognized Transcript Name",ERR_TGT_USR);
try
{
$HAS_FILTER=false;
  $RANGE=array('3UTR'=>false,'5UTR'=>false,'CDS'=>false,'POLYA'=>false);
  if (isset($USER_INPUT['PARAMS']))
  {  
    foreach ($USER_INPUT['PARAMS'] as $PARAM)
    {
      if ($PARAM=='3UTR'){$RANGE['3UTR']=true;$HAS_FILTER=true;}
      if ($PARAM=='CDS'){$RANGE['CDS']=true;$HAS_FILTER=true;}
      if ($PARAM=='5UTR'){$RANGE['5UTR']=true;$HAS_FILTER=true;}
      if ($PARAM=='POLYA'){$RANGE['POLYA']=true;$HAS_FILTER=true;}
    }
  }


$MODULE_DATA['INFO']=getTranscriptInfo($TRANSCRIPT_INPUT)[0];
if ($MODULE_DATA['INFO']==null)throw new Exception("No transcript found");

if ($USER_INPUT['PORTAL']['NAME']=='GENE' && $MODULE_DATA['INFO']['GENE_ID']!=$USER_INPUT['PORTAL']['DATA']['GENE_ID'])header("Location:/GENEID/".$MODULE_DATA['INFO']['GENE_ID'].'/TRANSCRIPT/'.$TRANSCRIPT_INPUT);
$MODULE_DATA['SEQUENCE']=getTranscriptSequence($TRANSCRIPT_INPUT);
$MODULE_DATA['PROT']=getTranscriptToProtein(array($MODULE_DATA['INFO']['TRANSCRIPT_ID']),true);
//$MODULE_DATA['SNP']=getTranscriptMutation($MODULE_DATA['INFO']['TRANSCRIPT_ID']);
$MODULE_DATA['SNP']=array('LIST'=>array(),'TYPE'=>array());
unset($TRANSCRIPT_INPUT);

if ($HAS_FILTER)
{
  $MODULE_DATA['FILTERS']=$RANGE;
  $MIN_SEQ=10000000;
  $MAX_SEQ=-1;
  foreach ($MODULE_DATA['SEQUENCE']['POS_TYPE'] as $K=>$PT)
  {
    switch ($PT['TYPE'])
    {
      case "3'UTR":  if ($RANGE['3UTR']) {$MIN_SEQ=min($PT['MIN'],$MIN_SEQ);$MAX_SEQ=max($PT['MAX'],$MAX_SEQ);}else unset($MODULE_DATA['SEQUENCE']['POS_TYPE'][$K]);break;
      case "5'UTR":  if ($RANGE['5UTR']) {$MIN_SEQ=min($PT['MIN'],$MIN_SEQ);$MAX_SEQ=max($PT['MAX'],$MAX_SEQ);}else unset($MODULE_DATA['SEQUENCE']['POS_TYPE'][$K]);break;
      case "CDS":    if ($RANGE['CDS'])  {$MIN_SEQ=min($PT['MIN'],$MIN_SEQ);$MAX_SEQ=max($PT['MAX'],$MAX_SEQ);}else unset($MODULE_DATA['SEQUENCE']['POS_TYPE'][$K]);break;
      case "poly-A": if ($RANGE['POLYA']){$MIN_SEQ=min($PT['MIN'],$MIN_SEQ);$MAX_SEQ=max($PT['MAX'],$MAX_SEQ);}else unset($MODULE_DATA['SEQUENCE']['POS_TYPE'][$K]);break;
    }
  }
  echo $MIN_SEQ."\t".$MAX_SEQ."\n";
$EXONS=&$MODULE_DATA['SEQUENCE']['EXONS'];
  foreach ($EXONS as $K=>&$EX)
  {
    if ($EX['MIN']>$MAX_SEQ) {unset($EXONS[$K]);continue;}
    if ($EX['MAX']<$MIN_SEQ) {unset($EXONS[$K]);continue;}
    if ($EX['MIN']<$MIN_SEQ)$EX['MIN']=$MIN_SEQ;
    if ($EX['MAX']>$MAX_SEQ)$EX['MAX']=$MAX_SEQ;
  }
  $SEQUENCE=&$MODULE_DATA['SEQUENCE']['SEQUENCE'];
  foreach ($SEQUENCE as $K=>$V)
  {
    if ($K<$MIN_SEQ || $K>$MAX_SEQ)unset($SEQUENCE[$K]);
  }

}
}catch(Exception $e)

{
  if (strpos($e->getMessage(),'Error while running query')!==false)$MODULE_DATA['ERROR']="Unable to retrieve transcript information";
  else $MODULE_DATA['ERROR']=$e->getMessage();

}
//exit;


?>