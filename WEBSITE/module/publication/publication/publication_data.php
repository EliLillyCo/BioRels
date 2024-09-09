<?php
 if (!defined("BIORELS")) header("Location:/");


$PMID=$USER_INPUT['PAGE']['VALUE'];
$MODULE_DATA=array();
if (strpos($PMID,'PMID')!==false)$PMID=str_replace('PMID','',$PMID);
if (!preg_match("/([0-9]{2,11})/",$PMID,$matches))
{

}
else 
{

	
$MODULE_DATA=loadPublicationData($PMID);
}

//11250746


?>