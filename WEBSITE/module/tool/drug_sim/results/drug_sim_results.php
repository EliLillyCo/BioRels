<?php


if (!defined("BIORELS")) header("Location:/");



$LOG='';
foreach ($MODULE_DATA['INFO']['JOB_STATUS']['LOG'] as $LOG_V)
{
$LOG.='<tr><td style="width:175px">'.$LOG_V[1].'</td><td>'.$LOG_V[0].'</td></tr>';
}
changeValue("drug_sim_results",'LOG',$LOG);


changeValue("drug_sim_results","THRESHOLD",$MODULE_DATA['INFO']['PARAMS']['THRESHOLD'].'%');

changeValue("drug_sim_results","HASH",$MD5_HASH);

if (!isset($MODULE_DATA['INFO']['JOB_STATUS']))
{
	removeBlock("drug_sim_results","READY");
	changeValue("drug_sim_results","MONITOR_STR","Job is in queue");
	return;
}
else if (strpos($MODULE_DATA['INFO']['JOB_STATUS']['STATUS'],'Success')===false
&& $MODULE_DATA['INFO']['JOB_STATUS']['STATUS']!='Failed')
{
	removeBlock("drug_sim_results","READY");
	changeValue("drug_sim_results","MONITOR_STR","Job is currently running - This page will refresh in 3s");
	return;

}
else removeBlock("drug_sim_results","MONITOR");

$STR='';
$STR_JS='getCompoundImage("'.$MODULE_DATA['INFO']['PARAMS']['STRUCTURE'].'","ref_struct");'."\n";
$MODULE_DATA['INPUT']['PARAMS']['SEARCH_TYPE']='SUBSTRUCTURE';
if ($MODULE_DATA['INPUT']['PARAMS']['SEARCH_TYPE']=='SIMILARITY')
{
foreach ($MODULE_DATA['FILES'][0]['DOCUMENT_CONTENT'] as $sim=> &$list)
{
	
	
		
		foreach ($list as $id=>&$record)
		{
			$NAME='';
			if (isset($record['NAME']['PRIMARY']))$NAME=$record['DRUG_PRIMARY_NAME'];
			else if (isset($record['NAME']['SYNONYM']))$NAME=$record['NAME']['SYNONYM'][0];

		$STR.='<tr><td>'.$sim.'%</td><td id="cpd_'.$id.'" style="width:300px;max-width:300px"></td>
		<td><a href="/DRUG/'.$NAME.'" target="_blank">'.((isset($record['NAME']['PRIMARY']))?implode("<br/>",$record['NAME']['PRIMARY']):'N/A').'</a></td>
		<td><a href="/DRUG/'.$NAME.'" target="_blank">'.((isset($record['NAME']['SYNONYM']))?implode("<br/>",$record['NAME']['SYNONYM']):'N/A').'</a></td>
		<td>'.$record['is_approved'].'</td>
		<td>'.$record['is_withdrawn'].'</td>
		<td>'.$record['max_clin_phase'].'</td></tr>';
$STR_JS.='getCompoundImage("'.$record['smiles'].'","cpd_'.$id.'",300);'."\n";
		}
	
}
}
else
{
	$N=0;
	foreach ($MODULE_DATA['FILES'][0]['DOCUMENT_CONTENT'] as $id=> &$record)
{
	$NAME='';
			if (isset($record['NAME']['PRIMARY']))$NAME=$record['DRUG_PRIMARY_NAME'];
			else if (isset($record['NAME']['SYNONYM']))$NAME=$record['NAME']['SYNONYM'][0];

	
		$STR.='<tr><td data-id="cpd_'.$id.'" data-smi="'.$record['smiles'].'" id="cpd_'.$id.'" style="width:300px;max-width:300px"></td>
		<td><a href="/DRUG/'.$NAME.'" target="_blank">'.((isset($record['NAME']['PRIMARY']))?implode("<br/>",$record['NAME']['PRIMARY']):'N/A').'</a></td>
		<td><a href="/DRUG/'.$NAME.'" target="_blank">'.((isset($record['NAME']['SYNONYM']))?implode("<br/>",$record['NAME']['SYNONYM']):'N/A').'</a></td>
		<td>'.$record['is_approved'].'</td>
		<td>'.$record['is_withdrawn'].'</td>
		<td>'.$record['max_clin_phase'].'</td></tr>';
//$STR_JS.='getCompoundImage("'.$record['smiles'].'","cpd_'.$id.'",300);'."\n";
	++$N;if ($N>30)	break;
	
}
removeBlock("drug_sim_results","similarity");
}

changeValue("drug_sim_results","RESULTS",$STR);
changeValue("drug_sim_results","LIST_IMGS",$STR_JS);
		
?>