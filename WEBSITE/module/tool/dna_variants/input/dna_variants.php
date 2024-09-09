<?php

if (!defined("BIORELS")) header("Location:/");



echo '<pre>';//print_R($MODULE_DATA);exit;

$DATA=array();
foreach ($MODULE_DATA['INFO'] as &$E)
{
$DATA[$E['CHR_NUM']][$E['SEQ_ROLE']][]=$E;
}

//exit;


$CHRS=array();
for ($I=1;$I<=21;++$I)$CHRS[]=$I;
$CHRS[]='MT';$CHRS[]='X';$CHRS[]='Y';$CHRS[]='Un';
$STR='<option value=""></option>';
foreach ($CHRS as $CHR_NUM)
{
	$LIST=&$DATA[$CHR_NUM];
	if (isset($LIST['assembled-molecule']))
	{
		$E=&$LIST['assembled-molecule'][0];
		$STR.='<optgroup label="Chromosome '.$CHR_NUM.' - Assembled molecule">';
		$STR.='<option value="'.$E['REFSEQ_NAME'].'.'.$E['REFSEQ_VERSION'].'">'.$E['REFSEQ_NAME'].'.'.$E['REFSEQ_VERSION'].'</option>';
		$STR.='</optgroup>';
	}
	
}
foreach ($CHRS as $CHR_NUM)
{
	$LIST=&$DATA[$CHR_NUM];
foreach ($LIST as $type=>&$LIST_T)
	{
		if ($type=='assembled-molecule')continue;
		$STR.='<optgroup label="Chromosome '.$CHR_NUM.' - '.$type.'">';
		foreach ($LIST_T as &$E)
		$STR.='<option value="'.$E['REFSEQ_NAME'].'.'.$E['REFSEQ_VERSION'].'">'.$E['REFSEQ_NAME'].'.'.$E['REFSEQ_VERSION'].'</option>';
		$STR.='</optgroup>';
	}
}


changeValue("dna_variants",'CHROMOSOME',$STR);

$USER_INPUT['PARAMS']=array();
$USER_INPUT['PARAMS'][0]='1';
changeValue("dna_variants","GENE_VALIDATE",loadHTMLAndRemove('GENE_VALIDATE'));


 if (isset($MODULE_DATA['INPUT']))
 {
 	if (isset($MODULE_DATA['INPUT']['START_POS']))changeValue("dna_variants",'START_POS',$MODULE_DATA['INPUT']['START_POS']);
	 if (isset($MODULE_DATA['INPUT']['END_POS']))changeValue("dna_variants",'END_POS',$MODULE_DATA['INPUT']['END_POS']);
	 if (isset($TITLE))changeValue("dna_variants",'NAME',$TITLE);
	 if (isset($DESCRIPTION))changeValue("dna_variants",'NAME',$DESCRIPTION);
	 if (isset($INPUT['CHR_INFO']['REFSEQ_NAME']))changeValue("dna_variants",'SELECTED','<option value="'.$INPUT['CHR_INFO']['REFSEQ_NAME'].'.'.$INPUT['CHR_INFO']['REFSEQ_VERSION'].'">'.$INPUT['CHR_INFO']['REFSEQ_NAME'].'.'.$INPUT['CHR_INFO']['REFSEQ_VERSION'].'</option>');
	

 }

if (isset($MODULE_DATA['ERROR']))
{
	changeValue("dna_variants",'ALERT','<div class="w3-container alert alert-info">'.$MODULE_DATA['ERROR'].'</div>');
}
// //$MODULE_DATA['HASH']='286b046cf8e3b9846ac40322bff69e66';
if (isset($MODULE_DATA['HASH']))
changeValue("dna_variants",'HASH',$MODULE_DATA['HASH']);
else removeBlock("dna_variants",'MONITOR');


// for ($i=100;$i>=0;--$i)
// {
// 	$STR.='<option value="'.$i.'"' .(($i==80)?' selected="selected"':'').'>'.$i.'%</option>';
// }
// changeValue("dna_variants",'LIST',$STR);

?>