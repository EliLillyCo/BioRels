<?php

if (!defined("BIORELS")) header("Location:/");




switch ($USER_INPUT['VTYPE'])
{
	case 'W':
		preloadHTML($USER_INPUT['PAGE']['NAME']);

	break;
	case 'CONTENT':
		$result['code']='';
		preloadHTML($USER_INPUT['PAGE']['NAME'],'PAGE',true);
		ob_end_clean();
		$result['code']=$HTML[$GLB_CONFIG['PAGE'][$USER_INPUT['PAGE']['NAME']]['HTML_TAG']];
		$result['STAT']=$LATEST_MODULE_DATA['STAT'];
		$result['DATE']=$LATEST_MODULE_DATA['RESULTS']['DATE'];
		$result['SHIFT']=$LATEST_MODULE_DATA['RESULTS']['DIFF'];
		echo json_encode($result);
		exit;
	break;
	case 'JSON':
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
	
 
}


?>