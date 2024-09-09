<?php

if (!defined("BIORELS")) header("Location:/");




switch ($USER_INPUT['VTYPE'])
{
	case 'W':
		preloadHTML($USER_INPUT['PAGE']['NAME']);

	break;
	case 'CONTENT':
	$result['code']=loadHTMLAndRemove($USER_INPUT['PAGE']['NAME']);
	ob_end_clean();
	echo json_encode($result);
		exit;
	break;
	case 'JSON':
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
	case 'CSV':
	$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		
		header('Content-type: text/tab-separated-values');
		header('Content-Disposition: attachment; filename="'.$MODULE_DATA['SEQUENCE']['PROT_IDENTIFIER'].'-'.$MODULE_DATA['SEQUENCE']['ISO_ID'].'-SEQ_SIM.tsv"');
		
		
		
$str="Organism\tGene_ID\tGene Symbol\tGene Name\tUniprot ID\tUniprot Description\tSequence ID\tPercent Identity\tPercent Similarity\tPercent Identity (aligned)\tPercent Similarity (aligned)\n";
foreach ($MODULE_DATA['RESULTS'] as $ID=> $SEQ)
{
	
$str.=$SEQ['SCIENTIFIC_NAME']."\t".$SEQ['GENE_ID']."\t".$SEQ['SYMBOL']
."\t".$SEQ['FULL_NAME']
."\t".$SEQ['PROT_IDENTIFIER']
."\t".$SEQ['DESCRIPTION']
."\t".$SEQ['ISO_ID']
."\t".$SEQ['PERC_IDENTITY']
."\t".$SEQ['PERC_SIM']
."\t".$SEQ['PERC_IDENTITY_COM']
."\t".$SEQ['PERC_SIM_COM']."\n";
}
ob_end_clean();
header("Content-Length: ".strlen($str));
echo $str;
		exit;


}


?>