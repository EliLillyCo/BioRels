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
	
		header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'-'.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'-CLINICAL_TRIALS.csv"');
		
		ob_end_clean();
		
$str="Clinical trial identifier,Disease name,Clinical phase,clinical status,start date,drug name,is approved,is withdrawn\n";
foreach ($MODULE_DATA as $CLT)
{
	
$str.='"'.$CLT['TRIAL_ID'].'","'.$CLT['DISEASE_NAME'].'","'.$CLT['CLINICAL_PHASE'].'","'.$CLT['CLINICAL_STATUS'].'","'.$CLT['START_DATE'].'","'.$CLT['DRUG_NAME'].'","'.$CLT['IS_APPROVED'].'","'.$CLT['IS_WITHDRAWN'].'"'."\n";

}
header("Content-Length: ".strlen($str));
echo $str;
		exit;
		exit;

}


?>