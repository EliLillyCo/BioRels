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
		case 'MOL2':
		
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		
		ob_end_clean();
		header('Content-type: chemical/x-mol2');
		header('Content-Disposition: attachment; filename="'.$MODULE_DATA['ENTRY']['PDB_ID'].'.mol2"');
		header('Content-length: '.strlen($MODULE_DATA['MOL2']));
	
		echo $MODULE_DATA['MOL2'];
		exit;
		case 'PDB':
		
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		//print_r($MODULE_DATA['ENTRY']);exit;
		ob_end_clean();
		header('Content-type: chemical/x-pdb');
		header('Content-Disposition: attachment; filename="'.$MODULE_DATA['ENTRY']['PDB_ID'].'.pdb"');
		header('Content-length: '.strlen($MODULE_DATA['PDB']));
	
		echo $MODULE_DATA['PDB'];
		exit;
	

}


?>