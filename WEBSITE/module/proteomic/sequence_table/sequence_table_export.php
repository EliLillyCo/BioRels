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
	case 'FASTA':
		header('Content-type: application/fasta');
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		header('Content-Disposition: attachment; filename="'.$MODULE_DATA['INFO']['ISO_NAME'].'SEQUENCE.fna"');
		
		ob_end_clean();
		$SQ='';
foreach ($MODULE_DATA['SEQ'] as $ID=> $SEQ)$SQ.=$SEQ['AA'];
		$SEQ='>'.$MODULE_DATA['INFO']['ISO_NAME']."\n";
		$SEQ.=implode(str_split($SQ,80),"\n");
header("Content-Length: ".strlen($SEQ));
echo $SEQ;
exit;

}


?>