<?php

$STR = '';

if ($MODULE_DATA['RESULTS'] != array()) {
	$USER_INPUT['PAGE']['VALUE'] = implode("_", $MODULE_DATA['RESULTS']);
	$STR .= loadHTMLAndRemove('PUBLICATION_BATCH');
	// todo change out publi_gene_search with publi_compound_search
	changeValue("publi_compound_search", "PUBLI", $STR);
	// todo change out publi_gene_search with publi_compound_search
} else {
	changeValue("publi_compound_search", "PUBLI", '<div class="alert alert-info">No publication retrieved with these parameters');
}
