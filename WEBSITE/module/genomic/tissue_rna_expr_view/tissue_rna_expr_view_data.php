<?php

if (!defined("BIORELS")) header("Location:/");

$res=getListTissues();
$TISSUE_ID=-1;
foreach ($res as $line)
{
if ($line['TISSUE_NAME']==$USER_INPUT['PORTAL']['VALUE'])$TISSUE_ID=$line['TISSUE_ID'];
}
$FILTERS=array();
$MODULE_DATA['STAT']=getCoRNATissue($TISSUE_ID,$FILTERS);

?>