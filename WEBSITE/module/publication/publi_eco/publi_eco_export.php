<?php

if (!defined("BIORELS")) header("Location:/");




switch ($USER_INPUT['VTYPE'])
{
	case 'W':
		preloadHTML($USER_INPUT['PAGE']['NAME']);

	break;
	case 'HCONTENT':
	case 'CONTENT':
	$result['code']=loadHTMLAndRemove($USER_INPUT['PAGE']['NAME'],($USER_INPUT['VTYPE']=='HCONTENT'));
	if (ob_get_contents()) ob_end_clean();
	echo json_encode($result);
		exit;
	break;
	case 'JSON':
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
		if (ob_get_contents()) ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
	

}


?>
