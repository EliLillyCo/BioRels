<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

echo "IN";
if ($USER_INPUT['PORTAL']['NAME'] == 'GENE') {

    $MODULE_DATA['GENE'] = $USER_INPUT['PORTAL']['DATA'];
    $MODULE_DATA['DISEASE'] = getDiseaseEntry($USER_INPUT['PAGE']['VALUE'], true, true);

    echo "IN;";
    $MODULE_DATA['STAT'] = getDiseasePMIDStat($MODULE_DATA['DISEASE']['DISEASE_ENTRY_ID'], $MODULE_DATA['GENE']['GN_ENTRY_ID']);
} else if ($USER_INPUT['PORTAL']['NAME'] == 'DISEASE') {

    $MODULE_DATA['DISEASE'] = getDiseaseEntry($USER_INPUT['PORTAL']['VALUE'], true, true);
    $MODULE_DATA['GENE'] = gene_portal_geneID($USER_INPUT['PAGE']['VALUE']);
    $MODULE_DATA['STAT'] = getDiseasePMIDStat($MODULE_DATA['DISEASE']['DISEASE_ENTRY_ID'], $MODULE_DATA['GENE']['GN_ENTRY_ID']);

}

?>