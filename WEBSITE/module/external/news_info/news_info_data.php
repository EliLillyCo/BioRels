<?php

if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit','1000M');

$ALLOWED=array('DRUG','CLINVAR','PATHWAY','GENE','DISEASE','PROT_FEAT','ASSAY','CELL','TISSUE','EVIDENCE','CLINICAL','COMPANY','NEWS');
$TYPE='';
for ($I=1;$I<count($USER_INPUT['PARAMS']);++$I)
{
if (in_array($USER_INPUT['PARAMS'][$I],$ALLOWED))$TYPE=$USER_INPUT['PARAMS'][$I];
}


$NEWS_HASH=$USER_INPUT['PARAMS'][0];
$MODULE_DATA=array('TYPE'=>$TYPE);




 if (hasPrivateAccess())$MODULE_DATA['RESULT']=private_getNewsInfo($NEWS_HASH,$TYPE);
 else 
$MODULE_DATA['RESULT']=getNewsInfo($NEWS_HASH,$TYPE);


?>