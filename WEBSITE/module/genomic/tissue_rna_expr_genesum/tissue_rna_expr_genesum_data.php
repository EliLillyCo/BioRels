<?php

if (!defined("BIORELS")) header("Location:/");



$GENE_ID=$USER_INPUT['PAGE']['VALUE'];

$MODULE_DATA['GENE_INFO']=gene_portal_geneID($GENE_ID);
$GN_ENTRY_ID=$MODULE_DATA['GENE_INFO']['GN_ENTRY_ID'];
$res=geneToUniprot($GN_ENTRY_ID);
$PRIM_UN=$res[0]['UN_IDENTIFIER'];
$MODULE_DATA['UNIPROT_INFO']=getUniprotDescription($PRIM_UN);


function convertUniprotText($list_t)
{
	global $NRID;
	global $USER_INPUT;
	$str='';
	if (count($list_t)>1)$str.='<ul>';
	foreach ($list_t as $t)
	{
		
		if (count($list_t)>1)$str.='<li>';
		$t=str_replace('Note=','<br/><span class="bold">Note: </span>',$t);
		$refs='';
		
		$pos=strpos($t,'{');
		while($pos!==false) {
			$pos2=strpos($t,'}',$pos);
			if ($pos2!==false)$refs.=substr($t,$pos+1,$pos2-$pos-1).' ';
			$t=substr($t,0,$pos).substr($t,$pos2+1);
			$pos=strpos($t,'{');
		};
		
		preg_match_all("/PubMed:([0-9]{4,9})/",$t,$matches);
		
		foreach ($matches[1] as $K)
		{
			$refs.=', PubMed:'.$K;
		}
		
		$t=preg_replace("/PubMed:([0-9]{4,9})/",'PubMed:<a href="/PUBLI_LINK/$1" target="_blank">$1</a>',$t);
		$USER_INPUT['PAGE']['VALUE']=$refs;
		++$NRID;
		
		$USER_INPUT['PARAMS']=array('ID',$NRID);
		
		$str.=$t.'<br/>'.loadHTMLAndRemove('PUBLI_ECO');
		if (count($list_t)>1)$str.='</li>';
	}
	if (count($list_t)>1)$str.='</ul>';
	return $str;
}

?>