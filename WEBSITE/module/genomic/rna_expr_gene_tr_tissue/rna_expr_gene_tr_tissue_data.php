<?php

if (!defined("BIORELS")) header("Location:./");



///Get Gene Info:
$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
$res=getListTissues();
$TISSUE=explode("__",$USER_INPUT['PARAMS'][0]);
if (isset($USER_INPUT['PARAMS'][1] )&&$USER_INPUT['PARAMS'][1]=="log")$LOG_SCALE=true;
else $LOG_SCALE=false;
$TISSUE_ID=-1;


foreach ($res as $line)
{

	if ($TISSUE[0]==$line['SCIENTIFIC_NAME'] && $TISSUE[1]==$line['ORGAN_NAME'] && $TISSUE[2]==$line['TISSUE_NAME'])
	{

		$TISSUE_ID=$line['RNA_TISSUE_ID'];
		break;
	}
}





$res=runQuery("select TPM FROM RNA_GENE RG,RNA_SAMPLE RS, GENE_SEQ GS
WHERE RS.RNA_TISSUE_ID=".$TISSUE_ID." AND RS.RNA_SAMPLE_ID = RG.RNA_SAMPLE_ID AND RG.GENE_SEQ_ID = GS.GENE_SEQ_ID AND GN_ENTRY_ID= ".$GN_ENTRY_ID);
foreach ($res as $line)
{
	$MODULE_DATA['EXPR']['Gene '.$USER_INPUT['PORTAL']['DATA']['SYMBOL']][]=$line['TPM'];
}
$query="select TRANSCRIPT_NAME, TRANSCRIPT_VERSION, TPM FROM RNA_TRANSCRIPT RG,RNA_SAMPLE RS, TRANSCRIPT T, GENE_SEQ GS
WHERE RS.RNA_TISSUE_ID=".$TISSUE_ID." AND RS.RNA_SAMPLE_ID = RG.RNA_SAMPLE_ID
AND RG.TRANSCRIPT_ID = T.TRANSCRIPT_ID
AND T.GENE_SEQ_ID = GS.GENE_SEQ_ID  AND GN_ENTRY_ID= ".$GN_ENTRY_ID;
//echo $query;
$res=runQuery($query);
//print_r($res)//
foreach ($res as $line)
{
	$TR_N=$line['TRANSCRIPT_NAME'];
	if ($line['TRANSCRIPT_VERSION']!='')$TR_N.='.'.$line['TRANSCRIPT_VERSION'];
	$MODULE_DATA['EXPR']['Transcript '.$TR_N][]=$line['TPM'];
}
// print_r($MODULE_DATA);
// exit;

?>