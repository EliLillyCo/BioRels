<?php

 if (!defined("BIORELS")) header("Location:/");




///Get Gene Info:
$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];

$MODULE_DATA['TRANSCRIPTS']=getListExprTranscript($GN_ENTRY_ID);

$res=runQuery("SELECT RNA_TISSUE_ID, TISSUE_NAME,ORGAN_NAME,SCIENTIFIC_NAME FROM RNA_TISSUE RT, TAXON T WHERE RT.TAXON_ID=T.TAXON_ID ORDER BY SCIENTIFIC_NAME,ORGAN_NAME, TISSUE_NAME");
$TISSUES=array();
foreach ($res as $line)
{
	
	$TISSUE_NAME[$line['SCIENTIFIC_NAME']][$line['ORGAN_NAME']][$line['TISSUE_NAME']]=$line['ORGAN_NAME'].' - '.$line['TISSUE_NAME'];
	
}




?>