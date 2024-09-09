<?php

if (!defined("BIORELS")) header("Location:/");




switch ($USER_INPUT['VTYPE'])
{
	case 'W':
		preloadHTML($USER_INPUT['PAGE']['NAME']);

	break;
	case 'CONTENT':
		$result['code']='';
		preloadHTML($USER_INPUT['PAGE']['NAME']);
		ob_end_clean();
		$result['code']=$HTML[$GLB_CONFIG['PAGE'][$USER_INPUT['PAGE']['NAME']]['HTML_TAG']];
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

		$res=runQuery("SELECT document_name,document_description,document_content ,mime_Type
		FROM web_job_document wh, web_job wj where wj.web_job_id = wh.web_job_id AND  md5id = '".$USER_INPUT['PAGE']['VALUE']."' AND document_name ='".$USER_INPUT['PARAMS'][0]."'");
		foreach ($res as &$F)
		{
			$F['DOCUMENT_CONTENT']=stream_get_contents($F['DOCUMENT_CONTENT']);
			ob_end_clean();
			header('Content-type: '.$F['MIME_TYPE']);
			echo $F['DOCUMENT_CONTENT'];
			exit;
		}
		echo "Unable to find file";
		exit;

}


?>