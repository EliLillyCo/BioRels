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
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		ob_end_clean();
		$STR='';
		foreach ($MODULE_DATA['SEQ'] as $N)
		{
			if (isset($N['T']))			$STR.=$N['T'];
			else if (isset($N['P'])) 	$STR.=$N['P'];
		}
		header('Content-type: application/x-fasta');
		header('Content-Disposition: attachment; filename="'.$MODULE_DATA['INFO']['TRANSCRIPT_NAME'].'.'.$MODULE_DATA['INFO']['TRANSCRIPT_VERSION'].'.fasta"');
		echo '>'.$MODULE_DATA['INFO']['TRANSCRIPT_NAME'].'.'.$MODULE_DATA['INFO']['TRANSCRIPT_VERSION'].' '.$MODULE_DATA['INFO']['GENE_ID'].':'.$MODULE_DATA['INFO']['SYMBOL']."\n";
		echo implode(str_split($STR,500),"\n");
		exit;

}


?>