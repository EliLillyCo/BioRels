<?php

// SET PAGE TITLE
changeValue("compound_portal", "CPD_NAME", $USER_INPUT['PORTAL']['VALUE']);

// CONFIRM DATA AVAILABLE
if ($MODULE_DATA == array()) {
	removeBlock("compound_portal", "HAS_DATA");
} else {
	removeBlock("compound_portal", "NO_DATA_MESSAGE");

	// BUILD SALT/STEROISOMERS COMPONENT
	if ($MODULE_DATA[0]['STRUCTURE']['SMILES']) {

		$SMI = $MODULE_DATA[0]['STRUCTURE']['SMILES'];
		if ($MODULE_DATA[0]['STRUCTURE']['COUNTERION_SMILES'] != '') $SMI .= '.' . $MODULE_DATA[0]['STRUCTURE']['COUNTERION_SMILES'];
		changeValue("compound_portal", "SMI", $SMI);

		changeValue("compound_portal", "SMILES", $MODULE_DATA[0]['STRUCTURE']['SMILES']);
		changeValue("compound_portal", "INCHI", $MODULE_DATA[0]['STRUCTURE']['INCHI']);
		changeValue("compound_portal", "INCHI_KEY", $MODULE_DATA[0]['STRUCTURE']['INCHI_KEY']);

		$STR = '';
		$STR_JS = '';
		foreach ($MODULE_DATA[0]['ALT_SALT'] as $I => $REC) {
			$STR .= '<div style="max-width:250px;position:relative;width:250px;display:inline-block;margin:10px;padding:5px;vertical-align:top;height:250px;" class="boxShadow" id="P_CPD_IMG_' . $I . '" >	<div id="CPD_IMG_' . $I . '" style="margin:0 auto;width:fit-content;position:absolute"></div></div>';
			$SMI = $REC['SMILES'];
			if ($REC['COUNTERION_SMILES'] != '') $SMI .= '.' . $REC['COUNTERION_SMILES'];
			$STR_JS .= 'getCompoundImage("' . $SMI . '","CPD_IMG_' . $I . '",230,250);' . "\n";
		}
		changeValue("compound_portal", "LIST_DIVS", $STR);

		$STR = '';
		foreach ($MODULE_DATA[0]['ALT_SCAFF'] as $I => $REC) {
			$STR .= '<div style="max-width:250px;position:relative;width:250px;display:inline-block;margin:10px;padding:5px;vertical-align:top;height:250px;" class="boxShadow" id="P_CPD_IMG_' . $I . '" >	<div id="CPD_IMG_' . $I . '" style="margin:0 auto;width:fit-content;position:absolute"></div></div>';
			$SMI = $REC['SMILES'];
			if ($REC['COUNTERION_SMILES'] != '') $SMI .= '.' . $REC['COUNTERION_SMILES'];
			$STR_JS .= 'getCompoundImage("' . $SMI . '","CPD_IMG_' . $I . '",230,250);' . "\n";
		}
		// CHECK THAT SCAFFOLD EXISTS
		if ($STR != '') {
			changeValue("compound_portal", "ALT_SCAFF", $STR);
		} else {
			removeBlock("compound_portal", "ALT_SCAFF");
		}

		changeValue("compound_portal", "LIST_IMGS", $STR_JS);
	} else {
		removeBlock("compound_portal", "W_SALT");
		removeBlock("compound_portal", "W_REP");
		removeBlock("compound_portal", "W_IMG");
	}

	// BUILD NAMES COMPONENT
	$NAMES = array();
	$SOURCES = array();
	foreach ($MODULE_DATA[0]['NAME'] as $NAME) {
		$NAMES[$NAME['SM_NAME']][] = $NAME['SOURCE_NAME'];
		$SOURCES[$NAME['SOURCE_NAME']] = true;
	}

	$STR = '';
	foreach ($NAMES  as $N => $V) {
		if (in_array("ChEMBL", $V)) $N = '<a href="' . str_replace('${LINK}', $N, $GLB_CONFIG['LINK']['CHEMBL']['COMPOUND']) . '">' . $N . '</a>';
		else if (in_array("SwissLipids", $V) && preg_match("/SLM\:([0-9]){9}/", $N)) $N = '<a href="' . str_replace('${LINK}', $N, $GLB_CONFIG['LINK']['SWISSLIPIDS']['COMPOUND']) . '">' . $N . '</a>';
		else if (in_array("DrugBank/OpenTarget", $V) && preg_match("/CHEMBL/", $N)) $N = '<a href="' . str_replace('${LINK}', $N, $GLB_CONFIG['LINK']['OPEN_TARGET']['COMPOUND']) . '">' . $N . '</a>';
		$STR .= '<span style="min-width: fit-content;border:1px solid grey; border-radius:20px;margin:2px;padding:5px;display:inline-table;max-width:fit-content" class="item-grid">' . $N . '</span>';
	}

	$STR .= '<br/>Sources: <p style="font-weight:bold;font-style:italic;font-size:0.9em">';
	foreach ($SOURCES  as $N => $V) $STR .= $N . ' ; ';
	$STR = substr($STR, 0, -3) . '</p>';
	changeValue("compound_portal", "LIST_NAMES", $STR);

	// BUILD SUMMARY CLINICAL TRIAL PHASE
	if (isset($MODULE_DATA[0]['DRUG'])) {
		$S = '';
		$C = '';
		switch ($MODULE_DATA[0]['DRUG'][0]['MAX_CLIN_PHASE']) {
			case 0:
				$C = 'grey';
				break;
			case 1:
				$C = 'orange';
				break;
			case 2:
				$C = 'orange';
				break;
			case 3:
				$C = 'green';
				break;
			case 4:
				$C = 'green';
				break;
		}
		for ($I = 1; $I <= $MODULE_DATA[0]['DRUG'][0]['MAX_CLIN_PHASE']; ++$I) {
			changeValue("compound_portal", "P" . $I, $C);
		}
	}

	// BUILD DESCRIPTION COMPONENT
	changeValue("compound_portal", "DESC", $MODULE_DATA[0]['DESC']['DESCRIPTION_TEXT']);

	if (isset($MODULE_DATA[0]['DESC'])) {
		$STR = '<h4>' . $MODULE_DATA[0]['DESC']['DESCRIPTION_TYPE'] . '</h4><p>' . $MODULE_DATA[0]['DESC']['DESCRIPTION_TEXT'] . '</p>';
		changeValue("compound_portal", "DESC", $STR);
	} else removeBlock("compound_portal", "W_DESC");


	// BUILD OUT PUBLICATION COMPONENT
	if (isset($MODULE_DATA[0]['DRUG'][0]['DRUG_ENTRY_ID'])) {
		$USER_INPUT['PARAMS'] = array(0 => "PER_PAGE", 1 => "10", 2 => "PAGE", 3 => "1", 4 => "FILTERS", 5 => "drug-" . $MODULE_DATA[0]['DRUG'][0]['DRUG_ENTRY_ID'] . ";");
		removeBlock("compound_portal", "NO_NEWS");
		changeValue("compound_portal", "NEWS", loadHTMLAndRemove('PUBLI_NEWS_SEARCH'));
	}

	// BUILD OUT ASSAY COMPONENT
	if (isset($MODULE_DATA[0]['NAME'])) {
		removeBlock("compound_portal", "NO_ASSAYS");
		changeValue("compound_portal", "ASSAY", loadHTMLAndRemove('COMPOUND_ASSAY'));
	}
}
