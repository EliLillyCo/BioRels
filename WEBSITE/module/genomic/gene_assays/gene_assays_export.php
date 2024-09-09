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
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
		if (ob_get_contents())ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
	break;
	case 'CSV':
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
		if (ob_get_contents())ob_end_clean();
		header('Content-type: text/csv');
		$STR='';
		
		$STR.="Assay Name,Description,Assay Type,Assay Type Description,Target Type,Target Type Description,Confidence score,Confidence score description,Source\n";
foreach ($MODULE_DATA as $assay)
{

	
	$STR.=$assay['ASSAY_NAME'].",".
	$assay['ASSAY_DESCRIPTION'].",".
	$assay['ASSAY_TARGET_TYPE_NAME'].",".
	$assay['ASSAY_TYPE_DESC'].",".
	ucfirst(strtolower($assay['ASSAY_TARGET_TYPE_NAME'])).",".
	$assay['ASSAY_TARGET_TYPE_DESC'].",".
	$assay['SCORE_CONFIDENCE'].",".
	$assay['CONFIDENCE_DESCRIPTION'].",".
	$assay['SOURCE_NAME']."\n";
			
}
	echo $STR;
	exit;
}
?>