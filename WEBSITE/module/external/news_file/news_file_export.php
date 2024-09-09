<?php

if (!defined("BIORELS")) header("Location:/");



$MODULE_DATA=preloadData($USER_INPUT['PAGE']['NAME']);
		ob_end_clean();

		if ($MODULE_DATA==null)
		{
			echo ("File not found\n");
		}
		else if ( $MODULE_DATA['DOCUMENT_CONTENT']==null)echo ("File not found\n");

		else 
		{
		header('Content-type: '.$MODULE_DATA['MIME_TYPE']);
		header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
		header("Cache-Control: public"); // needed for internet explorer
		//header("Content-Transfer-Encoding: Binary");
		header("Content-Length:".strlen($MODULE_DATA['DOCUMENT_CONTENT']));
		
		//header("Content-Disposition: attachment; filename=".$MODULE_DATA['DOCUMENT_NAME']);
		echo $MODULE_DATA['DOCUMENT_CONTENT'];
		}
		exit;
		die();        
?>