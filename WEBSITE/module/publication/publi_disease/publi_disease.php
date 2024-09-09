<?php


$co=$MODULE_DATA['STAT']['CO'];
//foreach ($MODULE_DATA['STAT'] as $IN)$co+=$IN['CO'];
changeValue("publi_disease","COUNT",$co);
changeValue("publi_disease","NPAGE",(ceil($co/10)));

changeValue("publi_disease","SYMBOL",$MODULE_DATA['INFO']['DISEASE_NAME']);
changeValue("publi_disease","TAG",$MODULE_DATA['INFO']['DISEASE_TAG']);


$USER_INPUT['PARAMS']=array();
$USER_INPUT['PARAMS'][0]='1';
changeValue("publi_disease","LIST_GENES",loadHTMLAndRemove('GENE_VALIDATE'));
changeValue("publi_disease","TISSUE_FILTER",loadHTMLAndRemove('TISSUE_VALIDATE'));

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
changeValue("publi_disease","LIST_TOPICS",$STR);
?>