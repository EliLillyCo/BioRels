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
 
$MODULE_DATA['GENE_SEQ']=getListTranscripts($GN_ENTRY_ID)['GENE_SEQ'];
$ENSG_ID=-1;
foreach ($MODULE_DATA['GENE_SEQ'] as $T)
if (substr($T['GENE_SEQ_NAME'],0,3)=='ENS')$ENSG_ID=$T['GENE_SEQ_ID'];
if ($ENSG_ID==-1) throw new Exception("Unable to find Gene Sequence",ERR_TGT_USR);


$res=getGeneGTEXExpr($ENSG_ID);
$MODULE_DATA=array();
foreach ($res as $line)
{
	$MODULE_DATA[$TISSUES[$line['RNA_TISSUE_ID']]][]=$line['TPM'];
}



?>