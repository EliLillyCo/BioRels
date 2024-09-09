<?php

if (!defined("BIORELS")) header("Location:/");



$STR='';
foreach ($MODULE_DATA['PRE_INPUT']['GENOME'] as $TAX_ID=>&$TAX_INFO)
{
	
$STR.='<tr><th><input type="checkbox" name="organism[]" id="'.$TAX_ID.'" value="'.$TAX_ID.'"';

if (isset($MODULE_DATA['INPUT']['ORGANISM']) && !in_array($TAX_ID,$MODULE_DATA['INPUT']['ORGANISM'])) $STR.='';
else $STR.=' checked="checked" ';
$STR.='/><label style="margin-left:5px" for="'.$TAX_ID.'">'.$TAX_INFO[0]['SCIENTIFIC_NAME'].'</label></th></tr>';
}
changeValue("sirna_orthologs",'ORGANISMS',$STR);

if (isset($MODULE_DATA['INPUT']))
{
	if (isset($MODULE_DATA['INPUT']['SEQUENCE']))changeValue("sirna_orthologs",'input_sequence',$MODULE_DATA['INPUT']['SEQUENCE']);
	
	if (isset($MODULE_DATA['INPUT']['REGION']))
	foreach ($MODULE_DATA['INPUT']['REGION'] as $region)
	changeValue("sirna_orthologs",$region,'checked="checked"');


}
if ($TITLE!='')changeValue("sirna_orthologs",'TITLE',$TITLE);
if ($DESCRIPTION!='')changeValue("sirna_orthologs",'DESCRIPTION',$DESCRIPTION);
if (isset($MODULE_DATA['ERROR']))
{
	changeValue("sirna_orthologs",'ALERT','<div class="w3-container alert alert-info">'.$MODULE_DATA['ERROR'].'</div>');
	removeBlock("sirna_orthologs",'MONITOR');
}

if (isset($MODULE_DATA['HASH']))
changeValue("sirna_orthologs",'HASH',$MODULE_DATA['HASH']);
else removeBlock("sirna_orthologs",'MONITOR');

$USER_INPUT['PARAMS']=array();
$USER_INPUT['PARAMS'][0]='1';
changeValue("sirna_orthologs","GENE_VALIDATE",loadHTMLAndRemove("GENE_VALIDATE"));
?>