<?php


if (!defined("BIORELS")) header("Location:/");


$PMID=$USER_INPUT['PAGE']['VALUE'];
if (!preg_match("/[0-9]{2,11}/",$PMID))throw new Exception("Wrong format for publication ".$RSID,ERR_TGT_USR);

$MODULE_DATA=array();
$MODULE_DATA=loadPublicationData($PMID);


if (isset($MODULE_DATA['ENTRY']['DOI']) 
	   && $MODULE_DATA['ENTRY']['DOI']!='')header("Location: https://dx.doi.org/".$MODULE_DATA['ENTRY']['DOI']);
	   
	   else 
	   header("Location: https://pubmed.ncbi.nlm.nih.gov/".$MODULE_DATA['ENTRY']['PMID']);
	   
		
?>