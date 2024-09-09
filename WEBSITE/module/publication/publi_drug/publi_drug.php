<?php


$co = $MODULE_DATA['STAT']['CO'];
if (isset($MODULE_DATA['STAT']['CO'])) {
	changeValue("publi_drug", "COUNT", $co);
	changeValue("publi_drug", "NPAGE", (ceil($co / 10)));
	// TODO - CONFIRM THIS IS WORKING
	changeValue("publi_drug", "DRUG_NAME", $USER_INPUT['PORTAL']['DATA']['DRUG_PRIMARY_NAME']);


	$USER_INPUT['PARAMS'] = array();
	$USER_INPUT['PARAMS'][0] = '1';
	changeValue("publi_drug", "LIST_GENES", loadHTMLAndRemove('GENE_VALIDATE'));
	$USER_INPUT['PARAMS'] = array();
	$USER_INPUT['PARAMS'][0] = '2';
	changeValue("publi_drug", "LIST_DISEASES", loadHTMLAndRemove('DISEASE_VALIDATE'));
	$USER_INPUT['PARAMS'] = array();
	$USER_INPUT['PARAMS'][0] = '3';
	changeValue("publi_drug", "LIST_DRUGS", loadHTMLAndRemove('DRUG_VALIDATE'));
	$USER_INPUT['PARAMS'][0] = '4';
	changeValue("publi_drug", "LIST_COMPOUNDS", loadHTMLAndRemove('COMPOUND_VALIDATE'));

	$PUBLI_RULES = getAllPubliRules();
	$STR = '';
	foreach ($PUBLI_RULES as $RULESETN => &$RULESET)
		foreach ($RULESET as $RULESUBN => &$RULESUB) {
			$STR .= '<optgroup label="' . $RULESETN . '-' . $RULESUBN . '">';
			foreach ($RULESUB as $RULENAME => $INFO) {
				$STR .= '<option value="' . $RULENAME . '">' . $INFO[0] . '</option>';
			}
			$STR .= '</optgroup>';
		}
	changeValue("publi_drug", "LIST_TOPICS", $STR);
}
