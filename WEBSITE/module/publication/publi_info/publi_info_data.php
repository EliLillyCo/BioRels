<?php

if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit','1000M');

$ALLOWED=array('DRUG','CLINVAR','PATHWAY','GENE','DISEASE','PROT_FEAT','ASSAY','CELL','TISSUE','EVIDENCE');
$TYPE='';
for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
{
if (in_array($USER_INPUT['PARAMS'][$I],$ALLOWED))$TYPE=$USER_INPUT['PARAMS'][$I];
}


$PMID=$USER_INPUT['PAGE']['VALUE'];
$MODULE_DATA=array('TYPE'=>$TYPE,'PMID'=>$PMID);
if (strpos($PMID,'PMID')!==false)$PMID=str_replace('PMID','',$PMID);
if (!preg_match("/([0-9]{2,11})/",$PMID,$matches))
{

}
else 
{

	$MODULE_DATA['RESULT']=getPublicationInfo($PMID,$TYPE);

}
?>