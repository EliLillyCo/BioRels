<?php


$co=$MODULE_DATA['STAT']['CO'];
//foreach ($MODULE_DATA['STAT'] as $IN)$co+=$IN['CO'];
changeValue("publi_news","COUNT",$co);
changeValue("publi_news","NPAGE",(ceil($co/10)));


$USER_INPUT['PARAMS']=array();
$USER_INPUT['PARAMS'][0]='1';
changeValue("publi_news","LIST_GENES",loadHTMLAndRemove('GENE_VALIDATE'));
$USER_INPUT['PARAMS']=array();
$USER_INPUT['PARAMS'][0]='2';
changeValue("publi_news","LIST_DISEASES",loadHTMLAndRemove('DISEASE_VALIDATE'));
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
changeValue("publi_news","LIST_TOPICS",$STR);


if ($FILTERS!=array())
{
	$STR='';
	foreach ($FILTERS as $type=>$list)
	foreach ($list as $val)
$STR.=$type.'-'.$val.';';
changeValue("publi_news","INI_FILTERS",$STR);
}

if (isset($FILTERS['source']))
{
	$TITLE='<h3 style="width:100%;text-align:center">';
	{
		$TITLE.=$FILTERS['source'][0];
	}
	changeValue("publi_news","TITLE",$TITLE.'</h3>');
}
?>