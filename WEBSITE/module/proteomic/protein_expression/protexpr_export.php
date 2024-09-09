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
	case 'CSV':
		header('Content-type: text/tab-separated-values');
		header('Content-Disposition: attachment; filename="'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'-'.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'-PROTEIN_EXPRESSION.tsv"');
		$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		ob_end_clean();
		
$str="TISSUE Name\tCell Type\tExpression Level\tConfidence\n";
foreach ($MODULE_DATA as $DATA)
{
$str.=$DATA['TISSUE_NAME']."\t".$DATA['CELL_TYPE']."\t";
switch ($DATA['EXPRESSION'])
{
	case 0:$str.='N/A';break;
	case 1:$str.='Not detected';break;
	case 2:$str.='Low';break;
	case 3:$str.='Medium';break;
	case 4:$str.='High';break;
}
$str.="\t";
switch ($DATA['CONFIDENCE'])
{
	
	case 1:$str.='Uncertain';break;
	case 2:$str.='Approved';break;
	case 3:$str.='Supported';break;
	case 4:$str.='Enhanced';break;
}
$str.="\n";
}
header("Content-Length: ".strlen($str));
echo $str;
exit;



}


?>