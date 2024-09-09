<?php

if (!defined("BIORELS")) header("Location:/");


changeValue("tissue_rna_expr_genesum","ORGANISM",$MODULE_DATA['GENE_INFO']['SCIENTIFIC_NAME']);
changeValue("tissue_rna_expr_genesum","SYMBOL",$MODULE_DATA['GENE_INFO']['SYMBOL']);
changeValue("tissue_rna_expr_genesum","PROT_NAME",$MODULE_DATA['GENE_INFO']['FULL_NAME']);
changeValue("tissue_rna_expr_genesum","GENE_ID",$MODULE_DATA['GENE_INFO']['GENE_ID']);
changeValue("tissue_rna_expr_genesum","TISSUE",$USER_INPUT['PORTAL']['VALUE']);
changeValue("tissue_rna_expr_genesum","PRIM_GENE",$MODULE_DATA['GENE_INFO']['SYMBOL']);

if (isset($MODULE_DATA['UNIPROT_INFO']['FUNCTION']))
{
	changeValue("tissue_rna_expr_genesum","FUNCTION",convertUniprotText($MODULE_DATA['UNIPROT_INFO']['FUNCTION']));
}


$DRUGS=getDrugGene($GN_ENTRY_ID);
$DR_ST=array(1=>0,2=>0,3=>0,4=>0);
foreach ($DRUGS as $DR)	$DR_ST[$DR['MAX_CLIN_PHASE']]++;
$MAX_LEV=0;
foreach ($DR_ST as $K=>$T)if ($T!=0)$MAX_LEV=$K;
$STR='';
$STR_N='<div style="display:flex">';
$DR_I=array(1=>'I',2=>'II',3=>'III',4=>'IV');
for ($I=1;$I<=4;++$I)
{
	$STR_N.='<div class="w3-col s3_1" style="
    margin-right: 1%
    margin-bottom: 5px;"><div class="text-circle blk_font" style="margin:0 auto">'.$DR_ST[$I].'</div></div>';
	$STR.='<div  class="chevron w3-col s3_1" style="';
	if ($I>$MAX_LEV)$STR.='background-color:grey';
	$STR.='">'.$DR_I[$I].'</div>';
}

changeValue("tissue_rna_expr_genesum","SM_DRUG",$STR_N.'</div>'.$STR);

$CPD_ACT_STAT=getActStat($GN_ENTRY_ID);

$uM=0;$mM=0;$nM=0;$pM=0;
foreach ($CPD_ACT_STAT as $V=>$K)
{
	if ($V<3)$mM+=$K;
	else if ($V>3 && $V<6)$uM+=$K;
	else if ($V>=6 && $V<9)$nM+=$K;
	else if ($V>=9)$pM+=$K;
	

}

changeValue("tissue_rna_expr_genesum","uM",$uM);
changeValue("tissue_rna_expr_genesum","mM",$mM);
changeValue("tissue_rna_expr_genesum","nM",$nM);
changeValue("tissue_rna_expr_genesum","pM",$pM);
changeValue("tissue_rna_expr_genesum","CONTENT",$STR);
changeValue("tissue_rna_expr_genesum","TAG",$MODULE_DATA['GENE_INFO']['GENE_ID'].'_CPD_ACT');
?>