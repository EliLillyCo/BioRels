<?php

if (!defined("BIORELS")) header("Location:/");


changeValue("domain","PROT_IDENTIFIER",$MODULE_DATA['DOMAIN']['PROT_IDENTIFIER']);
changeValue("domain","DOMAIN_NAME",$MODULE_DATA['DOMAIN']['DOMAIN_NAME']);
changeValue("domain","DESCRIPTION",$MODULE_DATA['DOMAIN']['DESCRIPTION']);
changeValue("domain","RANGE",'['.$MODULE_DATA['DOMAIN']['POS_START'].'-'.$MODULE_DATA['DOMAIN']['POS_END'].']');
changeValue("domain","START",$MODULE_DATA['DOMAIN']['POS_START']);
changeValue("domain","END",$MODULE_DATA['DOMAIN']['POS_END']);
changeValue("domain","SEQID",$MODULE_DATA['DOMAIN']['ISO_ID']);
changeValue("domain","GENE_ID",$MODULE_DATA['DOMAIN']['GENE_ID']);



$mapping=array('Homologous_superfamily'=>'H','Superfamily'=>'S','Domain'=>'D','Active_site'=>'A','Binding_site'=>'B','Conserved_site'=>'C');
$DOM_TYPE_IMG=array("CHAIN"=>array("C","orange"),"REPEAT"=>array("R","#ed7d31"),"DOMAIN"=>array("D","#4000ff"),"REGION"=>array("G","#843c0c"));

$STR='';
foreach($MODULE_DATA['IP_ENTRY'] as $IP_ENTRY_ID=>&$IP_INFO)
{

	$RID=0;
	//echo $DOM_INFO['INFO']['ABSTRACT'];
	preg_match_all("/\{([A-Za-z:0-9, ]){1,1000}\}/",$IP_INFO['INFO']['ABSTRACT'],$matches);
	$IP_INFO['INFO']['ABSTRACT'].='<h5>Relevant publications - <span style="text-align:left;cursor:pointer" onclick="$(\'#publi_'.$IP_ENTRY_ID.'\').toggle();if ($(this).html()==\'Hide\'){$(this).html(\'Show\');}else {$(this).html(\'Hide\');}">Show</span></h5><div id="publi_'.$IP_ENTRY_ID.'" style="display:none;font-size:0.8em">';
	$LT=array();$RIDs=array();
	foreach ($matches[0] as $match)
	{
		++$RID;
		$IP_INFO['INFO']['ABSTRACT']=str_replace($match,'<sup>['.$RID.']</sup>',$IP_INFO['INFO']['ABSTRACT']);
	//	echo $match;
		preg_match_all("/Pubmed:([0-9]{1,10})/",$match,$pub_match);
	//	echo "<pre>";
	//	print_r($pub_match);
		$IP_INFO['INFO']['ABSTRACT'].='<span class="bold">['.$RID.']</span>';
		foreach ($pub_match[1] as $PMID){$LT[]=$PMID;$RIDs[]=$RID;}
		
		
		
	}
	$USER_INPUT['PAGE']['VALUE']=implode("_",$LT);
	$USER_INPUT['PARAMS']=array('RID',implode("_",$RIDs));
	print_r($USER_INPUT);
	$STR_P=loadHTMLAndRemove('PUBLICATION_BATCH');
echo strlen($STR_P);
	$DOM_INFO['INFO']['ABSTRACT'].=$STR_P;
	$IP_INFO['INFO']['ABSTRACT'].='</div>';

	$STR.='<div class="container-grey w3-container w3-padding-16 w3-col s12">
	<div style="width:100%;max-width:50px;display:inline-block"><div class="sprite_img src_dom_'.$mapping[$IP_INFO['INFO']['ENTRY_TYPE']].'"></div></div>
<h3 style="display:inline">'.$IP_INFO['INFO']['NAME']."</h3>";
$STR.='<p>'.$IP_INFO['INFO']['ABSTRACT'].'<p>';

$STR.='<table class="table" style="width:98%; margin:0 auto"><thead><tr><th>Database Name</th><th>Entry</th><th>Name</th><th>Evidence</th><th>Score</th><th>Range</th></tr></thead><tbody>';
foreach ($IP_INFO['SIGN'] as &$SIGN)
{
$STR.='<tr><td>'.$SIGN['IP_SIGN_DBNAME'].'</td><td>'.$SIGN['IP_SIGN_DBKEY'].'</td><td>'.$SIGN['IP_SIGN_NAME'].'</td>';

$STR.="<td>".$SIGN['EVIDENCE']."</td><td>".$SIGN['SCORE']."</td><td>".$SIGN['START_POS']."-".$SIGN['END_POS']."</td></tr>";
}
$STR.='</tbody></table></div>';
}
changeValue("domain","DOM_DESC",$STR);
changeValue("domain","N_SIM_DOM",$MODULE_DATA['SIM_DOM']);
changeValue("domain","NPAGE_SIMDOM",floor($MODULE_DATA['SIM_DOM']/10)+1);
changeValue("domain","N_3DSTRUCT",$MODULE_DATA['XRAY']);
changeValue("domain","NPAGE_3Dstruct",floor($MODULE_DATA['XRAY']/10)+1);




?>