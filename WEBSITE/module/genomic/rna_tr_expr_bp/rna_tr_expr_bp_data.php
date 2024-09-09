<?php

 if (!defined("BIORELS")) header("Location:/");




///Get Gene Info:
$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
if (isset($USER_INPUT['PARAMS'][0] )&&$USER_INPUT['PARAMS'][0]=="log")$LOG_SCALE=true;
else $LOG_SCALE=false;
$res=getListTissues();
$TISSUES=array();
$TISSUE_NAME=array();
foreach ($res as $line)
{
	if ($line['ORGAN_NAME'] ==$line['TISSUE_NAME'])$TISSUES[$line['RNA_TISSUE_ID']]=$line['ORGAN_NAME'];
	else $TISSUES[$line['RNA_TISSUE_ID']]=$line['ORGAN_NAME'].' '.$line['TISSUE_NAME'];
	
}

$TNAME=explode(".",$USER_INPUT['PAGE']['VALUE']);

$TMP=getListTranscripts($GN_ENTRY_ID)['TRANSCRIPTS'];

$MODULE_DATA=array();;
foreach ($TMP as $T)
{
	if ($T['TRANSCRIPT_NAME']==$TNAME[0])$MODULE_DATA['TRANSCRIPT']=$T;
}

if ($MODULE_DATA==array()) throw new Exception("Unable to find Transcript",ERR_TGT_USR);


$res=getTranscriptGTEXExpr($MODULE_DATA['TRANSCRIPT']['TRANSCRIPT_ID']);

foreach ($res as $line)
{
	$MODULE_DATA['EXPR'][$TISSUES[$line['RNA_TISSUE_ID']]][]=$line['TPM'];
}

?>