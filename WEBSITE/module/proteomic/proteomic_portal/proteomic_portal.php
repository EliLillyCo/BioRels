<?php
if (!defined("BIORELS")) header("Location:/");


global $USER_INPUT;

$TARGET=$USER_INPUT['PORTAL']['DATA'];

changeValue("proteomic_portal","ORGANISM",$TARGET['SCIENTIFIC_NAME']);
changeValue("proteomic_portal","UNIPROT_ID",$TARGET['PROT_IDENTIFIER']);


$STR='';

foreach ($MODULE_DATA['PROT_NAME'][1] as $PNAME)
{
	changeValue("proteomic_portal","PROT_NAME",$PNAME['PROTEIN_NAME'].(($PNAME['EC_NUMBER']!='')?' - '.$PNAME['EC_NUMBER']:''));
}


$UNIP="";$N_SP=0;
$PRIM_SEQ='';

foreach ($MODULE_DATA['UNIPROT'] as $N=>$L)
{
	if ($N==6)
	{
	$UNIP.='<div id="tog_unip" style="display:none">';
	}
	else if ($N>1 )$UNIP.='<br/>';
	
	if ($L['STATUS']=='T')
	{
		
		changeValue("proteomic_portal","PRIM_UNI",$L['PROT_IDENTIFIER']);		
		continue;
	}
	$UNIP.='<a href="https://uniprot.org/uniprot/'.$L['PROT_IDENTIFIER'].'"  class="link" target="_blank" >'.$L['PROT_IDENTIFIER'].'</a>';
}
$PRIM_UN_ID=$MODULE_DATA['UNIPROT'][0]['PROT_ENTRY_ID'];



if (isset($MODULE_DATA['UNIPROT_DESC']['FUNCTION']))
{
	changeValue("proteomic_portal","FUNCTION",convertUniprotText($MODULE_DATA['UNIPROT_DESC']['FUNCTION']));
}



if (count($MODULE_DATA['UNIPROT'])>6) $UNIP.='</div><br/><span onclick="$(\'#tog_unip\').toggle();">Show '.(count($MODULE_DATA['UNIPROT'])-6).' more</span>';
		changeValue("proteomic_portal","UNIPROT",$UNIP);
		changeValue("proteomic_portal","ALT_UNI",count($MODULE_DATA['UNIPROT'])-$N_SP);





	
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

changeValue("proteomic_portal","PATHWAY",$STR);
/////////// Summary
$TIME['PATHWAY']=round(microtime_float()-$ts,2);$ts=microtime_float();



changeValue("proteomic_portal","SYMBOL",$MODULE_DATA['GENE_INFO']['SYMBOL']);
changeValue("proteomic_portal","PROT_NAME",$MODULE_DATA['GENE_INFO']['FULL_NAME']);
  

$STR_CH='';
$STR_LOC='';
foreach ($MODULE_DATA['GENE_LOCATION'] as $LOC)
{
$STR_CH.=$LOC['CHR_NUM'].' | ';
$STR_LOC.=$LOC['MAP_LOCATION'].' | ';
}
changeValue("proteomic_portal","CHROMOSOME",substr($STR_CH,0,-3));
changeValue("proteomic_portal","LOCUS",substr($STR_LOC,0,-3));
changeValue("proteomic_portal","GENE_TYPE",str_replace("_",' ',$MODULE_DATA['GENE_LOCATION'][0]['GENE_TYPE']));

if (is_array($MODULE_DATA['GENE_INFO']['SYN_VALUE']))
changeValue("proteomic_portal","ALT_GENE",implode(', ',$MODULE_DATA['GENE_INFO']['SYN_VALUE']));
else changeValue("proteomic_portal","ALT_GENE",$MODULE_DATA['GENE_INFO']['SYN_VALUE']);
		changeValue("proteomic_portal","ORGANISM",$MODULE_DATA['GENE_INFO']['SCIENTIFIC_NAME']);
		changeValue("proteomic_portal","GENE_ID",$MODULE_DATA['GENE_INFO']['GENE_ID']);
		changeValue("proteomic_portal","PRIM_GENE",$MODULE_DATA['GENE_INFO']['SYMBOL']);




$DR_ST=array(1=>0,2=>0,3=>0,4=>0);
foreach ($MODULE_DATA['DRUGS'] as $DR)	$DR_ST[$DR['MAX_CLIN_PHASE']]++;
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

changeValue("proteomic_portal","SM_DRUG",$STR_N.'</div>'.$STR);




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

?>
