<?php

 if (!defined("BIORELS")) header("Location:/");

changeValue("rna_gene_expr_bp","TAG",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);

$STR="TISSUE_ID,TPM\n";

$MIN=0;$MAX=-1;
$STR_C=array();
foreach ($MODULE_DATA as $TID=>&$LIST){
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
		foreach ($MODULE_DATA[$N] as $P)
		{
		if ($LOG_SCALE)	$STR.=$N.",".round(log($P+1,10),4)."\n";
		else 	$STR.=$N.",".round($P,4)."\n";
		}
	}

}
if ($LOG_SCALE)changeValue("rna_gene_expr_bp","YAXIS","log10(TPM+1)");
else changeValue("rna_gene_expr_bp","YAXIS","TPM");
changeValue("rna_gene_expr_bp","CONTENT",$STR);
changeValue("rna_gene_expr_bp","DOMAIN",substr($STR_C,0,-1));
changeValue("rna_gene_expr_bp","MINMAX",floor($MIN).','.ceil($MAX));

return;
if (count($MODULE_DATA['RNA_EXPR'])==0)
{
	removeBlock("rna_gene_expr_bp","VALID");
	
	return;
}
else removeBlock("rna_gene_expr_bp","INVALID");
$MIN_VALUE=10000000;
$MAX_VALUE=-1;
foreach ($MODULE_DATA['RNA_EXPR'] as $entry)
{
	if ($entry['MAX_VALUE']>$MAX_VALUE)$MAX_VALUE=$entry['MAX_VALUE'];
	if ($entry['MIN_VALUE']<$MIN_VALUE)$MIN_VALUE=$entry['MIN_VALUE'];
}

$DIFF=ceil($MAX_VALUE-$MIN_VALUE);
$STEP=ceil($DIFF/10);
if ($STEP>1000)$STEP=floor($STEP/100)*100;
else if ($STEP>100)$STEP=floor($STEP/10)*10;

$STR='<div style="width:96%;display:flex;position:relative;margin-top:3px;height:20px;">';
for ($I=0;$I<=10;++$I)
{
	$STR.='<div style="border-left:2px solid black; position:absolute;height:20px;left:'.($I*10).'%;width:1px"></div>
	<div style="top:-8px;font-size:0.7em; position:absolute;left:'.($I*10+0.5).'%;width:1px">'.($STEP*$I).'</div> ';
	if ($I<10)$STR.='<div style="border-top:2px solid black; font-size:0.7em;position:absolute;height:1px;top:9.5px;left:'.($I*10).'%;width:10%"></div> ';
}
changeValue("rna_gene_expr_bp","RANGE",$STR.'</div>');



$STR='';
/*
$STR.='<tr><td>Dummy</td><td></td><td  style="padding-left:0px !important;" >';
$STR.='<div style="width:96%;display:flex;position:relative;margin-top:3px;height:20px;">';
for ($I=0;$I<=10;++$I)
{
	$STR.='<div style="border-left:2px solid black; position:absolute;height:20px;left:'.($I*10).'%;width:1px"></div>
	<div style="top:-8px;font-size:0.7em; position:absolute;left:'.($I*10+0.5).'%;width:1px">'.($STEP*$I).'</div> ';
	if ($I<10)$STR.='<div style="border-top:2px solid black; font-size:0.7em;position:absolute;height:1px;top:9.5px;left:'.($I*10).'%;width:10%"></div> ';
}
$STR.='</td></tr>';*/
$AUC_N=array();
foreach ($MODULE_DATA['RNA_EXPR'] as $entry)
{
	$VALUE=(string)(floor($entry['AUC']/0.05)*0.05);
	$AUC_N[$VALUE][]=$entry['TISSUE_NAME'];
	$MIN_POS=round($entry['MIN_VALUE']*100/$MAX_VALUE,2);
	$MAX_POS=round($entry['MAX_VALUE']*100/$MAX_VALUE,2);
	$MED_POS=round($entry['MED_VALUE']*100/$MAX_VALUE,2);
	$LEFT=round($entry['Q1']*100/$MAX_VALUE,2);
	$WIDTH=round(($entry['Q3']-$entry['Q1'])*100/$MAX_VALUE,2);
	$STR.='<tr><td>'.$entry['ORGAN_NAME'].'</td><td>'.$entry['TISSUE_NAME'].'</td>
		  <td style="padding-left:0px !important;" data-sort="'.$entry['MED_VALUE'].'"><div style="width:96%;display:flex;position:relative;margin:3px">
		  <div style="border-left:2px solid black; position:absolute;height:20px;left:'.$MIN_POS.'%;width:1px" data-toggle="tooltip" data-placement="top" title="'.$entry['MIN_VALUE'].' TPM"></div> 
		  <div style="border-top:2px solid black; position:absolute;height:1px;top:9.5px;left:'.$MIN_POS.'%;width:'.($LEFT-$MIN_POS).'%"></div> 
		  
		  <div style="border:2px solid black; position:absolute;height:15px;top:2.5px;left:'.$LEFT.'%;width:'.$WIDTH.'%" data-toggle="tooltip" data-placement="top"></div>
		  <div style="border-left:3px solid black; position:absolute;height:15px;top:2.5px;left:'.$MED_POS.'%;width:1px" data-toggle="tooltip" data-placement="top" title="Median: '.$entry['MED_VALUE'].' TPM"></div> 
		  <div style="border-top:2px solid black; position:absolute;height:1px;top:9.5px;left:'.($LEFT+$WIDTH).'%;width:'.($MAX_POS-$LEFT-$WIDTH).'%"></div>
		  <div style="border-left:2px solid black; position:absolute;height:20px;left:'.$MAX_POS.'%;width:1px" data-toggle="tooltip" data-placement="left" title="'.$entry['MAX_VALUE'].' TPM"></div> </td></tr>';
}
changeValue("rna_gene_expr_bp","TABLE",$STR);

$colors=array('Whole Blood'=>'rgb(255,0,187)',
'Thyroid'=>'rgb(0,102,0)',
'Testis'=>'rgb(170,170,170)',
'Small Intestine'=>'rgb(85,85,34)',
'Skin'=>'rgb(0,0,255)',
'Brain'=>'rgb(238,238,0)',
'Fallopian Tube'=>'rgb(255,204,204)',
'Bladder'=>'rgb(170,0,0)',
''=>'rgb(,,)',
''=>'rgb(,,)',
''=>'rgb(,,)',
''=>'rgb(,,)',

);


$STR='';
$AUC_NT=0;
for ($I=1;$I>0.8;$I-=0.05)
{
	if (!isset($AUC_N[(string)$I]))continue;
	
	$AUC_NT+=count($AUC_N[(string)$I]);
}
echo $AUC_NT;
if ($AUC_NT==1)
{
	$STR=$USER_INPUT['PORTAL']['DATA']['SYMBOL'].' is found to be selective in ';
	for ($I=1;$I>=0.85;$I-=0.05)if (isset($AUC_N[$I]))$STR.= $AUC_N[(string)$I][0].' tissues';
	changeValue("rna_gene_expr_bp","INFORMATION",$STR);
}
else if ($AUC_NT<=3)
{
	$STR=$USER_INPUT['PORTAL']['DATA']['SYMBOL'].' is found to be relatively selective in ';
	for ($I=1;$I>=0.85;$I-=0.05)
	{
		$T=$I;
		if (!isset($AUC_N[$T]))continue;
		
		foreach ($AUC_N[$T] as $TI)$STR.= $TI.', ';
		
	}
	$STR=substr($STR,0,-2).' tissues';
	changeValue("rna_gene_expr_bp","INFORMATION",$STR);
}


$USER_INPUT['PAGE']['VALUE']='32913098';
$STR=loadHTMLAndRemove('PUBLICATION');
$USER_INPUT['PAGE']['VALUE']='20022975';
$STR.='<br/>For more information about the TPM metric:<br/> '.loadHTMLAndRemove('PUBLICATION');
changeValue("rna_gene_expr_bp","PUBLI",$STR);


?>