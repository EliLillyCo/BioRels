<?php

if (!defined("BIORELS")) header("Location:./");


if ($MODULE_DATA['EXPR']==array())
{
	removeBlock("rna_expr_gene_tr_tissue","VALID");
	return;
}else removeBlock("rna_expr_gene_tr_tissue","INVALID");
changeValue("rna_expr_gene_tr_tissue","TISSUE",$TISSUE);
changeValue("rna_expr_gene_tr_tissue","SYMBOL",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
$STR="TRANSCRIPT,TPM\n";

$MIN=0;$MAX=-1;
$STR_C=array();

foreach ($MODULE_DATA['EXPR'] as $TID=>&$LIST){
	$STR_C[$TID]=array();
foreach ($LIST as $E)
{
	
	if ($LOG_SCALE)$V=round(log($E+1,10),4);
	
	else $V=$E;
	$STR_C[$TID][]=$V;
	//$STR.=$TID.",".$V."\n";
	//if ($V<$MIN)$MIN=$V;
	if ($V>$MAX)$MAX=$V;
}
}

$STAT=array();
foreach ($STR_C as $K=>$V)
{
	
	$STAT[(string)round(array_sum($V)/count($V),4)][]=$K;
}

krsort($STAT);
$STR_C='';
foreach ($STAT as $V)
{
	foreach ($V as $N)
	{
		$STR_C.="'".$N."',";
		foreach ($MODULE_DATA['EXPR'][$N] as $P)
		{
			if ($LOG_SCALE)$STR.=$N.",".round(log($P+1,10),4)."\n";
			else $STR.=$N.",".round($P,4)."\n";
		}
	}

}
if ($LOG_SCALE)changeValue("rna_expr_gene_tr_tissue","YAXIS","log10(TPM+1)");

else changeValue("rna_expr_gene_tr_tissue","YAXIS","TPM");
changeValue("rna_expr_gene_tr_tissue","TAG",md5($TISSUE));
changeValue("rna_expr_gene_tr_tissue","CONTENT",$STR);
changeValue("rna_expr_gene_tr_tissue","DOMAIN",substr($STR_C,0,-1));
changeValue("rna_expr_gene_tr_tissue","MINMAX",floor($MIN).','.ceil($MAX));



$USER_INPUT['PAGE']['VALUE']='32913098';
$STR=loadHTMLAndRemove('PUBLICATION');
$USER_INPUT['PAGE']['VALUE']='20022975';
$STR.='<br/>For more information about the TPM metric:<br/> '.loadHTMLAndRemove('PUBLICATION');
changeValue("rna_expr_gene_tr_tissue","PUBLI",$STR);


?>