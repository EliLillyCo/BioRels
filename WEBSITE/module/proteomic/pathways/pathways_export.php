<?php

if (!defined("BIORELS")) header("Location:/");




switch ($USER_INPUT['VTYPE'])
{
	case 'W':
		preloadHTML($USER_INPUT['PAGE']['NAME']);

	break;
	case 'CONTENT':
	$result['code']=loadHTMLAndRemove($USER_INPUT['PAGE']['NAME']);
	if (ob_get_contents())ob_end_clean();
	echo json_encode($result);
		exit;
	break;
	case 'JSON':
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		if (ob_get_contents())ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
	case 'CSV':
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
	
		header('Content-type: text/csv');
		if ($MODULE_DATA['INPUT']=='GENE')
		{
		header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'-'.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'-PATHWAYS.csv"');
		}
		else
		{
			header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER'].'-PATHWAYS.csv"');
		}
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		
				
		$str="Reactome Identifier\tPathway Name\n";
		foreach ($MODULE_DATA['PATHWAYS'] as $ID=> $SEQ)
		{
			
		$str.=$SEQ['REAC_ID'].",\"".$SEQ['PW_NAME']."\"\n";
		}
		header("Content-Length: ".strlen($str));
		if (ob_get_contents())ob_end_clean();
		echo $str;
				exit;
			

}
?>