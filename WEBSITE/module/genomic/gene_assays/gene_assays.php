<?php

if (!defined("BIORELS")) header("Location:/");



$LINK='';
if (isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']))
{

	$gn_entry_Id=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];

	$LINK='/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'];
}
else if (isset($USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']))
{
	$PROT_IDENTIFIER=$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER'];
	$LINK='/UNIPROT_ID/'.$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER'];
	$MODULE_DATA=getProtAssays($PROT_IDENTIFIER);
}
$DATA_TTL=array();
$DATA_CO=0;
$MAP=array("CN/A"=>"No cell information provided");

$JSON_RANGE=array('CELL_TYPE'=>0,'ASSAY_TYPE'=>0,'TARGET_TYPE'=>0,'SCORE'=>0);
$JSON_INFO=array('CELL_TYPE'=>array("N/A"=>"N/A"),'ASSAY_TYPE'=>array(),'TARGET_TYPE'=>array(),'SCORE'=>array());

foreach ($MODULE_DATA['CELL'] as $CELL_ID=>&$CELL_INFO)
{
	$JSON_INFO['CELL_TYPE'][$CELL_ID]=$CELL_INFO[0];
	++$DATA_CO;
	$DATA_TTL["C".$DATA_CO]='<table><tr><th>Type:</th><td>'.$CELL_INFO[1].'</td></tr>'.
	 '<tr><th>Sex:</th><td>'.$CELL_INFO[2].'</td></tr>'.
	 '<tr><th>Age:</th><td>'.$CELL_INFO[3].'</td></tr>'.
	 '</table>';
	$MAP["C".$CELL_ID]="C".$DATA_CO;
}
foreach ($MODULE_DATA['TYPE'] as $TYPE_ID=>$TYPE_NAME)$JSON_INFO['ASSAY_TYPE'][$TYPE_ID]=$TYPE_NAME;
foreach ($MODULE_DATA['TARGET_TYPE'] as $T_TYPE_ID=>$T_TYPE_INFO)
{
	$JSON_INFO['TARGET_TYPE'][$T_TYPE_ID]=$T_TYPE_INFO[1];
	++$DATA_CO;
	$DATA_TTL["T".$DATA_CO]=$T_TYPE_INFO[0];
	$MAP["T".$T_TYPE_ID]="T".$DATA_CO;
}
foreach ($MODULE_DATA['CONFIDENCE'] as $CONF_ID=>$CONF_INFO)
{
	$JSON_INFO['SCORE'][$CONF_ID]=$CONF_INFO['NAME'];
	++$DATA_CO;
	$DATA_TTL["S".$DATA_CO]=$CONF_INFO['DESC'];
	$MAP["S".$CONF_ID]="S".$DATA_CO;
}

//  echo '<prE>';print_r($JSON_INFO);
//  print_r($DATA_TTL);
//  exit;

$STR='';
foreach ($MODULE_DATA['ASSAYS'] as $assay)
{
	/*
	Array
(
    [ASSAY_NAME] => CHEMBL1001228
    [ASSAY_DESCRIPTION] => Inhibition of CDK2/CyclinA
    [ASSAY_TYPE] => B
    [ASSAY_TARGET_TYPE_ID] => 20
    [SCORE_CONFIDENCE] => 8
    [SOURCE_NAME] => ChEMBL
    [CONFIDENCE_SCORE] => 8
    [CELL_ID] => 
    [ASSAY_TISSUE_NAME] => 
) */
	if ($assay['CELL_ID']=='')$assay['CELL_ID']='N/A';
	$STR.='<tr><td><a href="/ASSAY/'.$assay['ASSAY_NAME'].'">'.$assay['ASSAY_NAME'].'</a></td><td>'.
			$assay['ASSAY_DESCRIPTION'].'</td><td>'.
			$assay['ASSAY_TYPE'].'</td>
			<td  class="ttl_tr ttl_tag" title="Target type" data-pos="'.$MAP["T".$assay['ASSAY_TARGET_TYPE_ID']].'">'.$assay['ASSAY_TARGET_TYPE_ID']	.'</td>
			<td  class="ttl_tr ttl_tag" title="Confidence score" data-pos="'.$MAP["S".$assay['SCORE_CONFIDENCE']].'">'.$assay['SCORE_CONFIDENCE'].'</td>
			<td  class="ttl_tr ttl_tag" title="Cell" data-pos="'.$MAP["C".$assay['CELL_ID']].'">'.$assay['CELL_ID'].'</td><td>'.
			$assay['ASSAY_TISSUE_NAME'].'</td><td>'.
			(($assay['ASSAY_VARIANT_ID']!='')?$MODULE_DATA['VARIANT'][$assay['ASSAY_VARIANT_ID']]:'').'</td><td>'.
			$assay['SOURCE_NAME'].'</td></tr>';
		
			
}

// echo '<pre>';
// print_r($MAP);
// print_r($JSON_INFO);
// print_r($DATA_TTL);
// exit;
changeValue("gene_assays","LIST_ASSAYS",$STR);
changeValue("gene_assays","TOOLTIPS",str_replace("'","\\'",json_encode(str_replace("\n","",$DATA_TTL))));
changeValue("gene_assays","INFO",str_replace("'","\\'",json_encode($JSON_INFO)));

?>