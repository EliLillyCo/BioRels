<?php


$co=$MODULE_DATA['STAT']['CO'];
//foreach ($MODULE_DATA['STAT'] as $IN)$co+=$IN['CO'];
changeValue("publi_gene","COUNT",$co);
changeValue("publi_gene","NPAGE",(ceil($co/10)));

changeValue("publi_gene","SYMBOL",$GENE_INFO['SYMBOL']);
changeValue("publi_gene","GENE_ID",$GENE_INFO['GENE_ID']);

$USER_INPUT['PARAMS']=array();
$USER_INPUT['PARAMS'][0]='1';
changeValue("publi_gene","LIST_GENES",loadHTMLAndRemove('GENE_VALIDATE'));
$USER_INPUT['PARAMS']=array();
$USER_INPUT['PARAMS'][0]='2';
changeValue("publi_gene","LIST_DISEASES",loadHTMLAndRemove('DISEASE_VALIDATE'));
$PUBLI_RULES=getAllPubliRules();
$STR='';
foreach($PUBLI_RULES as $RULESETN=>&$RULESET)
foreach($RULESET as $RULESUBN=>&$RULESUB)
{
	$STR.='<optgroup label="'.$RULESETN.'-'.$RULESUBN.'">';
	foreach ($RULESUB as $RULENAME=>$INFO)
	{
		$STR.='<option value="'.$RULENAME.'">'.$INFO[0].'</option>';
	}
	$STR.='</optgroup>';
}
changeValue("publi_gene","LIST_TOPICS",$STR);
