<?php


$co = $MODULE_DATA['STAT']['CO'];
if (isset($MODULE_DATA['STAT']['CO'])) {
	changeValue("publi_compound", "COUNT", $co);
	changeValue("publi_compound", "NPAGE", (ceil($co / 10)));
	changeValue("publi_compound", "COMPOUND_NAME", $USER_INPUT['PORTAL']['DATA']['SM_NAME']);


	$USER_INPUT['PARAMS'] = array();
	$USER_INPUT['PARAMS'][0] = '1';
	changeValue("publi_compound", "LIST_GENES", loadHTMLAndRemove('GENE_VALIDATE'));
	$USER_INPUT['PARAMS'] = array();
	$USER_INPUT['PARAMS'][0] = '2';
	changeValue("publi_compound", "LIST_DISEASES", loadHTMLAndRemove('DISEASE_VALIDATE'));
	$USER_INPUT['PARAMS'] = array();
	$USER_INPUT['PARAMS'][0] = '3';
	changeValue("publi_compound", "LIST_DRUGS", loadHTMLAndRemove('DRUG_VALIDATE'));
	$USER_INPUT['PARAMS'][0] = '4';
	changeValue("publi_compound", "LIST_COMPOUNDS", loadHTMLAndRemove('COMPOUND_VALIDATE'));

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
	changeValue("publi_compound", "LIST_TOPICS", $STR);
}
