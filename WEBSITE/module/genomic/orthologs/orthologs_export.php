<?php

 if (!defined("BIORELS")) header("Location:/");

switch ($USER_INPUT['VTYPE'])
{
	case 'W':
		preloadHTML($USER_INPUT['PAGE']['NAME']);

	break;
	case 'CONTENT':
	$result['code']=loadHTMLAndRemove($USER_INPUT['PAGE']['NAME']);
	if (ob_get_contents()) ob_end_clean();
	echo json_encode($result);
		exit;
	break;
	case 'JSON':
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
		//foreach ($MODULE_DATA as &$ENTRY){unset($ENTRY['COMP_GN_ENTRY_ID']);}
		if (ob_get_contents())ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
	case 'CSV':
	header('Content-type: text/csv');
		header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'-'.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'-ORTHOLOGS.csv"');
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
		$str="GENE_ID,SYMBOL,NAME,SPECIES\n";
		foreach ($MODULE_DATA as $DATA)
		$str.=$DATA['COMP_GENE_ID'].",".$DATA['COMP_SYMBOL'].",".$DATA['COMP_GENE_NAME'].",".$DATA['COMP_SPECIES']."\n";
		if (ob_get_contents())ob_end_clean();
		header("Content-Length: ".strlen($str));
		echo $str;
		exit;

}


?>
