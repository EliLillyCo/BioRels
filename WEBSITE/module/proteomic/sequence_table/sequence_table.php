<?php

if (!defined("BIORELS")) header("Location:/");/// BIORELS defined in index.php. Not existing? Go to index.php


 changeValue("prot_seq","TITLE",$MODULE_DATA['INFO'][0]['DESCRIPTION']);
 changeValue("prot_seq","SEQ_NAME",$MODULE_DATA['INFO'][0]['ISO_NAME']);
 changeValue("prot_seq","GENE_ID",$MODULE_DATA['INFO'][0]['GENE_ID']);
 changeValue("prot_seq","SYMBOL",$MODULE_DATA['INFO'][0]['SYMBOL']);
 changeValue("prot_seq","FullName",$MODULE_DATA['INFO'][0]['FULL_NAME']);
 
 changeValue("prot_seq","LEN",count($MODULE_DATA['SEQ']));
 changeValue("prot_seq","PROTEIN_SEQUENCE",json_encode(array_values($MODULE_DATA['SEQ'])));
 changeValue("prot_seq","FEATS",json_encode($MODULE_DATA['FT']));
 $str=$MODULE_DATA['INFO'][0]['UN_IDENTIFIER'];
 if ($MODULE_DATA['INFO'][0]['CONFIDENCE']!='')
 {
	 $str.='<div style="    position: relative;
	 cursor: pointer;height:5px;
	 top: -18px;"  title="Please wait..."  class="helptt"><span style="color:gold" data-link="/CONTENT/HELP/uniprot_evidence" id="'.$MODULE_DATA['INFO'][0]['ISO_ID'].'">';
	 for ($I=5;$I>=1;--$I)
	 {
		 $str.='&#8902;';
		 if ($I==$MODULE_DATA['INFO'][0]['CONFIDENCE'])$str.='</span>';
	 }
	 $str.='</div>';
 }
 changeValue("prot_seq","UNIPROT_NAME",$str);
if ($MODULE_DATA['INFO'][0]['NOTE']!='')
{
	$str='<p><span class="bold">Compared to the primary sequence:</span> '.$MODULE_DATA['INFO'][0]['NOTE'].'</p>';
	changeValue("prot_seq","NOTE",$str);
}
 $MW_M=array(
	'A'=>89.09,
	'R'=>174.2,
	'N'=>132.1,
	'D'=>133.1,
	'C'=>121.2,
	'E'=>147.1,
	'Q'=>146.2,
	'G'=>75.07,
	'H'=>155.2,
	'I'=>131.2,
	'L'=>131.2,
	'K'=>146.2,
	'M'=>149.2,
	'F'=>165.2,
	'P'=>115.1,
	'S'=>105.09,
	'T'=>119.1,
	'W'=>204.2,
	'Y'=>181.2,
	'V'=>117.1,
 );
 $MW=0;

 foreach ($MODULE_DATA['SEQ'] as $T) $MW+=$MW_M[$T['AA']];
 changeValue("prot_seq","WEIGHT",$MW);

$NRID=0;
$str='';

 foreach ($MODULE_DATA['FT']['FEATS'] as $FT)
 {
	 if (!isset($FT['PMID']))continue;
	$TMP_STR='';
	foreach ($FT['PMID'] as $PMID=>$ECO_ID)
	{
		$TMP_STR.=$MODULE_DATA['FT']['ECO'][$ECO_ID]['ECO_ID'].':PubMed:'.$PMID.'|';
	}

	echo $TMP_STR."\n";

	 $USER_INPUT['PAGE']['VALUE']=$TMP_STR;
		++$NRID;
		$USER_INPUT['PARAMS']=array('ID',$NRID);
		echo strlen(loadHTMLAndRemove('PUBLI_ECO'));
		$str.='<div>'.loadHTMLAndRemove('PUBLI_ECO').'</div>';
 }
 
 //exit;
 changeValue("prot_seq","PUBLI",$str);
?>
