<?php

if (!defined("BIORELS")) header("Location:/");

$res=getListTissues();
$TISSUE_ID=-1;
foreach ($res as $line)
{
if ($line['TISSUE_NAME']==$USER_INPUT['PORTAL']['VALUE'])$TISSUE_ID=$line['TISSUE_ID'];
}

$MODULE_DATA['STAT']=(getCountDiseaseForTissue($TISSUE_ID));
$MODULE_DATA['RESULTS'][60]=getDiseaseForTissue($TISSUE_ID,array('MIN'=>0,'MAX'=>10,'ORDER'=>'SPEED60 DESC,ACCEL60, SPEED30  '),$FILTERS);
$MODULE_DATA['RESULTS'][30]=getDiseaseForTissue($TISSUE_ID,array('MIN'=>0,'MAX'=>10,'ORDER'=>'SPEED30 DESC,ACCEL30 DESC, SPEED60  '),$FILTERS);

?>