<?php

if (!defined("BIORELS")) header("Location:/");

try{
	$PMID=$USER_INPUT['PAGE']['VALUE'];

	$MODULE_DATA=publication_getFullText($PMID);

}catch(Exception $e)
{
$MODULE_DATA['ERROR']=$e->getMessage();
}



?>