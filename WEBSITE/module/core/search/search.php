<?php

$LIST = array('PROTEIN_SEARCH','CLINICAL_SEARCH', 'GENOMIC_SEARCH', 'PUBLICATION_SEARCH', 'PATHWAY_SEARCH', 'COMPOUND_SEARCH', 'DRUG_SEARCH', 'DISEASE_SEARCH', 'TISSUE_SEARCH', 'ASSAY_SEARCH','NEWS_SEARCH');
$TAG = '';
for ($I = 0; $I < count($USER_INPUT['PARAMS']); ++$I) {
	switch ($USER_INPUT['PARAMS'][$I]) {
		case 'ASSAY_SEARCH':
		case 'PATHWAY_SEARCH':
		case 'PROTEIN_SEARCH':
		case 'GENOMIC_SEARCH':
			case 'CLINICAL_SEARCH':
				
			$VAL = $USER_INPUT['PARAMS'][$I + 2];
			$VAL .= '/PARAMS/' . $USER_INPUT['PARAMS'][$I + 3];;
			if (isset($USER_INPUT['PARAMS'][$I + 3]) && $USER_INPUT['PARAMS'][$I + 3] == "HUMAN" || $USER_INPUT['PARAMS'][$I + 3] == "ALL")
				$VAL .= '/' . $USER_INPUT['PARAMS'][$I + 1];
			changeValue("search", "SEARCH_TARGET", $VAL);
			$TAG = $USER_INPUT['PARAMS'][$I];
			break;
		case 'TISSUE_SEARCH':
		case 'COMPOUND_SEARCH':
		case 'DRUG_SEARCH':
		case 'DISEASE_SEARCH':
		case 'PUBLICATION_SEARCH':
			case 'NEWS_SEARCH':
			$VAL = $USER_INPUT['PARAMS'][$I + 2] . '/PARAMS/' . $USER_INPUT['PARAMS'][$I + 1];
			changeValue("search", "SEARCH_TARGET", $VAL);
			$TAG = $USER_INPUT['PARAMS'][$I];
			break;
	}
}
foreach ($LIST as $T) {
	if ($T == $TAG) continue;
	removeBlock('search', $T);
}
