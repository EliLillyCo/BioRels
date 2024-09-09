<?php

changeValue("publi_topic","QUERY_NAME",$MODULE_DATA['QUERY'][0]['RULE_DESC']);
changeValue("publi_topic","TOPIC_NAME",$MODULE_DATA['QUERY'][0]['RULE_NAME']);


$co=$MODULE_DATA['STAT']['CO'];
//foreach ($MODULE_DATA['STAT'] as $IN)$co+=$IN['CO'];
$today = new DateTime(); // This will create a DateTime object with the current date

changeValue("publi_topic","COUNT",$co);
changeValue("publi_topic","DATE",$today->format('Y-d-m'));
changeValue("publi_topic","NPAGE",(ceil($co/10)));


$USER_INPUT['PARAMS']=array();
$USER_INPUT['PARAMS'][0]='1';
changeValue("publi_topic","LIST_GENES",loadHTMLAndRemove('GENE_VALIDATE'));

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
changeValue("publi_topic","LIST_TOPICS",$STR);

?>