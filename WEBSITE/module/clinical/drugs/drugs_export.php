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
	header('Content-type: text/csv');
	if ($USER_INPUT['PORTAL']['NAME']=='GENE')
	header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'-'.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'-DRIGS.csv"');
	else 
	header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['VALUE'].'-DRUGS.csv"');
	
	ob_end_clean();
	
	

$str="Drug_Name,Structure,Is Approved,Is Withdrawn, Max drug clinical phase, Description,Disease name,Max Disease Clinical phase\n";
foreach ($MODULE_DATA as $CLT)
{
	foreach ($CLT['DISEASE'] as $DI)
$str.='"'.$CLT['DRUG_PRIMARY_NAME'].'","'.$CLT['SMILES'].'","'.$CLT['IS_APPROVED'].'","'.$CLT['IS_WITHDRAWN'].'","'.$CLT['MAX_CLIN_PHASE'].'","'.$CLT['DESCRIPTION'].'","'.$DI['DISEASE_NAME'].'","'.$DI['MAX_DISEASE_PHASE'].'"'."\n";

}
header("Content-Length: ".strlen($str));
echo $str;
		
		exit;

}


?>