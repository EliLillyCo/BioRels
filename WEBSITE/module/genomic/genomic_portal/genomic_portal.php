<?php
if (!defined("BIORELS")) header("Location:/");




changeValue("genomic_portal","ORGANISM",$MODULE_DATA['GENE_INFO']['SCIENTIFIC_NAME']);
changeValue("genomic_portal","SYMBOL",$MODULE_DATA['GENE_INFO']['SYMBOL']);
changeValue("genomic_portal","PROT_NAME",$MODULE_DATA['GENE_INFO']['FULL_NAME']);
  

$STR_CH='';
$STR_LOC='';
foreach ($MODULE_DATA['GENE_LOCATION']['LOCUS'] as $LOC)
{
$STR_CH.=$LOC['CHR_NUM'].' | ';
$STR_LOC.=$LOC['MAP_LOCATION'].' | ';
}
changeValue("genomic_portal","CHROMOSOME",substr($STR_CH,0,-3));
changeValue("genomic_portal","LOCUS",substr($STR_LOC,0,-3));
changeValue("genomic_portal","GENE_TYPE",str_replace("_",' ',$MODULE_DATA['GENE_LOCATION']['LOCUS'][0]['GENE_TYPE']));

if (is_array($MODULE_DATA['GENE_INFO']['SYN_VALUE']))
changeValue("genomic_portal","ALT_GENE",implode(', ',$MODULE_DATA['GENE_INFO']['SYN_VALUE']));
else changeValue("genomic_portal","ALT_GENE",$MODULE_DATA['GENE_INFO']['SYN_VALUE']);
		changeValue("genomic_portal","ORGANISM",$MODULE_DATA['GENE_INFO']['SCIENTIFIC_NAME']);
		changeValue("genomic_portal","GENE_ID",$MODULE_DATA['GENE_INFO']['GENE_ID']);
		changeValue("genomic_portal","PRIM_GENE",$MODULE_DATA['GENE_INFO']['SYMBOL']);



if (count($MODULE_DATA['RNA_EXPR'])==0)
{
	removeBlock("genomic_portal","HAS_GENE_EXPR");
}
else
{
	$STR='';
	$RNA_EXPR=&$MODULE_DATA['RNA_EXPR'];
for ($I=0;$I<=4;++$I)
{
	$STR.='<tr><td>'.$RNA_EXPR[$I]['ORGAN_NAME'];
	if ($RNA_EXPR[$I]['ORGAN_NAME']!=$RNA_EXPR[$I]['TISSUE_NAME'])$STR.=' - '.$RNA_EXPR[$I]['TISSUE_NAME'];
	$STR.='</td><td style="color:';
	$V=&$RNA_EXPR[$I]['MED_VALUE'];
	if ($V<50)$STR.='red';
	else if ($V<100)$STR.='orange';
	else $STR.='green';

	$STR.='">'.$V.'</td></tr>';

}
$RNA_EXPR=null;
changeValue("genomic_portal","GENE_EXPR",$STR);
}
$TIME['GENE_GTEX']=round(microtime_float()-$ts,2);$ts=microtime_float();


function cleanTRStat($TR_STAT){
	global $GN_ID;
if (isset($TR_STAT[2]))
{
	foreach ($TR_STAT[2] as $TYPE=>$CO)
	if (isset($TR_STAT[1][$TYPE]))$TR_STAT[1][$TYPE]+=$CO;
	else $TR_STAT[1][$TYPE]=$CO;
}
$STR='';
for ($I=3;$I<=4;++$I)
if (isset($TR_STAT[$I]))
foreach ($TR_STAT[$I] as $TYPE=>$CO)
	if (isset($TR_STAT[5][$TYPE]))$TR_STAT[5][$TYPE]+=$CO;
	else $TR_STAT[5][$TYPE]=$CO;
	
	for ($I=1;$I<=5;$I+=4)
	if (isset($TR_STAT[$I]))
	foreach ($TR_STAT[$I] as $TYPE=>$CO)
	{
		
		$COLOR='black';
		if ($I==1)$COLOR='green';
		if ($I==5)$COLOR='red';
		$STR.='
		
		<div style="display: flex;width: fit-content;
		margin: 0 auto;">
				
		<div class="text-circle" style="border: 1px solid '.$COLOR.'">'.$CO.'</div>
		<div class="annot-circle">'.str_replace("_"," ",$TYPE).'</div>	
		</div>';
	}
	return $STR;
}

changeValue("genomic_portal","TR_STAT",cleanTRStat($MODULE_DATA['TRANSCRIPTS']));



$STR='';$N_COL=0;
foreach ($MODULE_DATA['ORTHOLOGS'] as $OT)
{
	
	//$STR_OT=cleanTRStat(getTranscriptStats($OT['COMP_GN_ENTRY_ID']));
	//if ($STR_OT=='')continue;
	$N_COL++;
	$STR.='
	<tr><td><a class="blk_font" href="/GENEID/'.$OT['COMP_GENE_ID'].'/TRANSCRIPTS">'.$OT['COMP_SPECIES'].'</a></td><td>'.$OT['COMP_SYMBOL'].' </td><td> '.$OT['COMP_GENE_ID'].'</td></tr>';
}

changeValue("genomic_portal","ORTHOLOGS",$STR);
if ($N_COL==0)$N_COL=1;
changeValue("genomic_portal","ORTHO_COL",'s'.max(2,floor(12/$N_COL)));




$UNIP="";$N_SP=0;
$PRIM_SEQ='';
if (isset($MODULE_DATA['UNIPROT']))
{
foreach ($MODULE_DATA['UNIPROT'] as $N=>$L)
{
	if ($N==6)
	{
	$UNIP.='<div id="tog_unip" style="display:none">';
	}
	else if ($N>1 )$UNIP.='<br/>';
	
	if ($L['STATUS']=='T')
	{
		
		changeValue("genomic_portal","PRIM_UNI",$L['PROT_IDENTIFIER']);		
		continue;
	}
	$UNIP.='<a href="https://uniprot.org/uniprot/'.$L['PROT_IDENTIFIER'].'"  class="link" target="_blank" >'.$L['PROT_IDENTIFIER'].'</a>';
}
$PRIM_UN_ID=$MODULE_DATA['UNIPROT'][0]['PROT_ENTRY_ID'];





if (count($MODULE_DATA['UNIPROT'])>6) $UNIP.='</div><br/><span onclick="$(\'#tog_unip\').toggle();">Show '.(count($MODULE_DATA['UNIPROT'])-6).' more</span>';
		changeValue("genomic_portal","UNIPROT",$UNIP);
		changeValue("genomic_portal","ALT_UNI",count($MODULE_DATA['UNIPROT'])-$N_SP);



}

	
		$STR='';
		foreach ($MODULE_DATA['PATHWAY'] as $PW)
		{
		$COLOR='black';
		
		$STR.='
		<div style="display: flex;width: fit-content;">
				
		<div class="text-circle" style="border: 1px solid '.$COLOR.'">'.$PW['CO'].'</div>
		<div class="annot-circle">'.$PW['PW_NAME'].'</div>	
		</div>';
		}

changeValue("genomic_portal","PATHWAY",$STR);
/////////// Summary
$TIME['PATHWAY']=round(microtime_float()-$ts,2);$ts=microtime_float();




$DR_ST=array(1=>0,2=>0,3=>0,4=>0);
foreach ($MODULE_DATA['DRUGS'] as $DR)	$DR_ST[$DR['MAX_CLIN_PHASE']]++;
$MAX_LEV=0;
foreach ($DR_ST as $K=>$T)if ($T!=0)$MAX_LEV=$K;
$STR='';
$STR_N='<div style="display:flex">';
$DR_I=array(1=>'I',2=>'II',3=>'III',4=>'IV');
for ($I=1;$I<=4;++$I)
{
	$STR_N.='<div class="w3-col s3_1 m3_1 l3_1" style="
    margin-right: 1%
    margin-bottom: 5px;"><div class="text-circle blk_font" style="margin:0 auto">'.$DR_ST[$I].'</div></div>';
	$STR.='<div  class="chevron w3-col s3_1 m3_1 l3_1" style="';
	if ($I>$MAX_LEV)$STR.='background-color:grey';
	$STR.='">'.$DR_I[$I].'</div>';
}

changeValue("genomic_portal","SM_DRUG",$STR_N.'</div>'.$STR);




if ($MODULE_DATA['GENIE']!=array() && isset($MODULE_DATA['GENIE'][0]['SPC']))
{
	switch($MODULE_DATA['GENIE'][0]['SPC'])
	{
		case 4:changeValue("genomic_portal","GENIE","<span class='w3-text-green'>Highly confident to be a surface protein (4/4)</span>");break;
		case 3:changeValue("genomic_portal","GENIE","<span class='w3-text-green'>Good confidence to be a surface protein (3/4)</span>");break;
		case 2:changeValue("genomic_portal","GENIE","<span class='w3-text-orange'>Low confidence to be a surface protein (2/4)</span>");break;
		case 1:changeValue("genomic_portal","GENIE","<span class='w3-text-red'>No confidence to be a surface protein (1/4)</span>");break;
		case 0:changeValue("genomic_portal","GENIE","<span class='w3-text-red'>Not a surface protein (0/4)</span>");break;
	}
}

function getLevels($LEV)
{
	$STR='';
	$COLOR=array(0=>'black_bc',1=>'red_bc',2=>'orange_bc',3=>'green_bc');
	for($I=0;$I<=3;++$I)
	{
		if ($I<=$LEV)$STR.='<div class="lvl_box '.$COLOR[$LEV].'"></div>';
		else $STR.='<div class="lvl_box "></div>';
	}
	return $STR;
}

$USER_INPUT['PARAMS']=array('RID');
$STR='';
if (isset($MODULE_DATA['NEWS'] ))
{
foreach ($MODULE_DATA['NEWS'] as $NEWS)$STR.=$NEWS['NEWS_ID'].'_';
$USER_INPUT['PARAMS'][]=$STR;
changeValue("genomic_portal","NEWS",loadHTMLAndRemove('NEWS_BATCH'));
}else changeValue("genomic_portal","NEWS",'No news');
?>
