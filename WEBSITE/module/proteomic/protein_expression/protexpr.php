<?php
if (!defined("BIORELS")) header("Location:/");
changeValue("protexpr","SYMBOL",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
changeValue("protexpr","GENE_ID",$USER_INPUT['PORTAL']['DATA']['GENE_ID']);
if (count($MODULE_DATA)==0)
{
    removeBlock("protexpr","VALID_PROTEXPR");
    return;
}
removeBlock("protexpr","INVALID_PROTEXPR");

$str='';

foreach ($MODULE_DATA as $DATA)
{

$str.='<tr><td>'.$DATA['TISSUE_NAME'].'</td><td>'.$DATA['CELL_TYPE'].'</td><td data-order="'.$DATA['EXPRESSION'].'">';
switch ($DATA['EXPRESSION'])
{
	case 0:$str.='N/A';break;
	case 1:$str.='Not detected';break;
	case 2:$str.='Low';break;
	case 3:$str.='<span class="ora_c">Medium</span>';break;
	case 4:$str.='<span class="gree_c">High</span>';break;
}
$str.='</td><td data-order="'.$DATA['CONFIDENCE'].'">';
switch ($DATA['CONFIDENCE'])
{
	
	case 1:$str.='Uncertain';break;
	case 2:$str.='Approved';break;
	case 3:$str.='Supported';break;
	case 4:$str.='Enhanced';break;
}
$str.='</td></tr>';

}
changeValue("protexpr","TBL_PROTEXPR",$str);

$USER_INPUT['PAGE']['VALUE']='25613900';
$STR=loadHTMLAndRemove('PUBLICATION');
changeValue("protexpr","PUBLI",$STR);

?>