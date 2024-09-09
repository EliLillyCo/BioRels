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
		$MODULE_DATA['GENE']=$USER_INPUT['PORTAL']['DATA'];
		if (ob_get_contents())ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
	case 'FASTA':
	
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
		$FILE='';
		$HAS_PARAMS=(count($USER_INPUT['PARAMS'])!=0);
		
		foreach ($MODULE_DATA['TRANSCRIPTS'] as $TR)
		{
			$NAME=$TR['TRANSCRIPT_NAME'];
			if ($TR['TRANSCRIPT_VERSION']!=null)$NAME.='.'.$TR['TRANSCRIPT_VERSION'];
			if ($HAS_PARAMS && !in_array($NAME,$USER_INPUT['PARAMS']))continue;
			
			$USER_INPUT['PAGE']['VALUE']=$TR['TRANSCRIPT_NAME'];
			$RES=preloadData('TRANSCRIPT');
			$STR='';
			foreach ($RES['SEQUENCE']['SEQUENCE'] as $N)$STR.=$N['NUCL'];	
			$FILE.= '>'.$RES['INFO']['TRANSCRIPT_NAME'].'.'.$RES['INFO']['TRANSCRIPT_VERSION'].' '.$RES['INFO']['GENE_ID'].':'.$RES['INFO']['SYMBOL']."\n";
			$FILE.= implode(str_split($STR,500),"\n")."\n";
		}
		if (ob_get_contents())	ob_end_clean();
		
		
		header('Content-type: application/x-fasta');
		header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'.'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'_transcripts.fasta"');
		echo $FILE."\n";

		exit;

}


?>
