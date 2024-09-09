<?php

if (!defined("BIORELS")) header("Location:/");

$STR='';
$ORDER=array('MIN_VALUE','Q1','MED_VALUE','Q3','MAX_VALUE');
foreach ($MODULE_DATA['RESULTS'] as $res)
{
//$STR.='<tr><td onclick="loadViewInTable($(this).closest(\'tr\'),\'/CONTENT/GENEID/'.$res['GENE_ID'].'/GENE_TISSUE_EXPR/PARAMS/'.$USER_INPUT['PORTAL']['VALUE'].'/normal\')"><img src="require/img/view.png" style="width:20px"/><td><a class="blk_font" href="/GENEID/'.$res['GENE_ID'].'">'.$res['SYMBOL'].'</a></td><td><a class="blk_font" href="/GENEID/'.$res['GENE_ID'].'">'
$STR.='<tr><td onclick="loadViewInTable($(this).closest(\'tr\'),\'/CONTENT/TISSUE/'.$USER_INPUT['PORTAL']['VALUE'].'/TISSUE_RNA_EXPR_GENESUM/'.$res['GENE_ID'].'\')"><img src="require/img/view.png" style="width:20px"/><td><a class="blk_font" href="/GENEID/'.$res['GENE_ID'].'">'.$res['SYMBOL'].'</a></td><td><a class="blk_font" href="/GENEID/'.$res['GENE_ID'].'">'
.$res['GENE_ID'].'</a></td>';
foreach ($ORDER as $T)
{
	$V=$res[$T];
	if ($V<50)$STR.='<td class="w3-text-red">'.$V.'</td>';
	else if ($V>=50 && $V<100)$STR.='<td class="w3-text-orange">'.$V.'</td>';
	else if ($V>=100)$STR.='<td class="w3-text-green">'.$V.'</td>';
}$STR.='<td>'
.$res['N_SAMPLE'].'</td></tr>';
}
changeValue("tissue_rna_expr_tbl","RESULTS",$STR);
?>