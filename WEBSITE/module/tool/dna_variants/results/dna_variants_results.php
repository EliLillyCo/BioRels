<?php


if (!defined("BIORELS")) header("Location:/");



$LOG='';
foreach ($MODULE_DATA['INFO']['JOB_STATUS']['LOG'] as $LOG_V)
{
$LOG.='<tr><td style="width:175px">'.$LOG_V[1].'</td><td>'.$LOG_V[0].'</td></tr>';
}
changeValue("dna_variants_results",'LOG',$LOG);


changeValue("dna_variants_results","CHROMOSOME",$MODULE_DATA['INFO']['PARAMS']['CHROMOSOME']);
changeValue("dna_variants_results","START_POS",$MODULE_DATA['INFO']['PARAMS']['START_POS']);
changeValue("dna_variants_results","END_POS",$MODULE_DATA['INFO']['PARAMS']['END_POS']);

changeValue("dna_variants_results","HASH",$MD5_HASH);

if (!isset($MODULE_DATA['INFO']['JOB_STATUS']))
{
	removeBlock("dna_variants_results","READY");
	changeValue("dna_variants_results","MONITOR_STR","Job is in queue");
	return;
}
else if (strpos($MODULE_DATA['INFO']['JOB_STATUS']['STATUS'],'Success')===false
&& $MODULE_DATA['INFO']['JOB_STATUS']['STATUS']!='Failed')
{
	removeBlock("dna_variants_results","READY");
	changeValue("dna_variants_results","MONITOR_STR","Job is currently running - This page will refresh in 3s");
	return;

}
else removeBlock("dna_variants_results","MONITOR");

foreach ($MODULE_DATA['FILES'] as &$F)
{
	
	if ($F['DOCUMENT_DESCRIPTION']=='Summarized results')changeValue("dna_variants_results","FPATH_SUM",$F['DOCUMENT_NAME']);
	else changeValue("dna_variants_results","FPATH_CL",$F['DOCUMENT_NAME']);


}


		
?>