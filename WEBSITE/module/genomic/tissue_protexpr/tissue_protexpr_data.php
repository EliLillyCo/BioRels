<?php
if (!defined("BIORELS")) header("Location:/");


$TISSUE=$USER_INPUT['PORTAL']['VALUE'];

$FOUND=false;
foreach ($GLB_CONFIG['REGEX']['TISSUE'] as $REG)
{
	if (preg_match("/".$REG."/",$TISSUE))$FOUND=true;
}
if (!$FOUND)throw new Exception("Wrong format for tissue ".$TISSUE,ERR_TGT_USR);

switch (strtolower($TISSUE))
{
	//case 'Heart'
}

echo "<pre>";print_r($GLB_CONFIG);
exit;
$MODULE_DATA=array();
$MODULE_DATA=getProtExpressionsTissue($USER_INPUT['PORTAL']['DATA']['GENE_ID']);

?>