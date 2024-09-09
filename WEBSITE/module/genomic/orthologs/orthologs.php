<?php

 if (!defined("BIORELS")) header("Location:/");

$str='';
foreach ($MODULE_DATA as $DATA)
{
	$str.='<tr><td><a class="blk_font" href="/GENEID/'.$DATA['COMP_GENE_ID'].'">'.$DATA['COMP_SYMBOL'].'</a></td><td><a class="blk_font" href="/GENEID/'.$DATA['COMP_GENE_ID'].'">'.$DATA['COMP_GENE_ID'].'</a></td><td>'.$DATA['COMP_GENE_NAME'].'</td><td>'.$DATA['COMP_SPECIES'].'</td></tr>';
}
changeValue("orthologs","TBL_ORTHO",$str);
changeValue("orthologs","GENE_ID",$USER_INPUT['PORTAL']['DATA']['GENE_ID']);
changeValue("orthologs","TITLE","List of orthologs for ".$USER_INPUT['PORTAL']['DATA']['SYMBOL']);






?>
