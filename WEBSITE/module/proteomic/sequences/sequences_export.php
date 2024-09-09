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
	
	case 'CSV':
		header('Content-type: text/tab-separated-values');
		header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'-'.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'-PROTEIN_SEQUENCES.tsv"');
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		ob_end_clean();
		
$str="Uniprot Identifier\tPrimary Entry\tSequence Name\tCanonical Sequence\tDescription\tTranscripts\n";
foreach ($MODULE_DATA['SEQ'] as $ID=> $SEQ)
{
	
$str.=$SEQ['SEQ']['UN_IDENTIFIER']."\t".$SEQ['SEQ']['STATUS']."\t".$SEQ['SEQ']['ISO_NAME']."\t".$SEQ['SEQ']['IS_PRIMARY']."\t".$SEQ['SEQ']['DESCRIPTION']."\t";
if (isset($SEQ['TRANSCRIPT']))
{
	foreach ($SEQ['TRANSCRIPT'] as $TR)
	{
		$NAME=$TR['TRANSCRIPT_NAME'];
		if ($TR['TRANSCRIPT_VERSION']!='')$NAME.='.'.$TR['TRANSCRIPT_VERSION'];
		$str.=$NAME.';';
	}
	$str=substr($str,0,-1);
}
$str.="\n";
}
header("Content-Length: ".strlen($str));
echo $str;
exit;

}
?>