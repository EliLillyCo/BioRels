<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$STR = '';

$LIST_SYN = array();
foreach ($MODULE_DATA['ENTRIES'] as &$EN) {
    if (isset($EN['SYN'])) {
        foreach ($EN['SYN'] as $S) {
            $LIST_SYN[$S['SYN_VALUE']] = true;
        }
    }
}
foreach ($LIST_SYN as $S => $V) {
    $STR .= $S . ' ; ';
}

$STR .= '<br/>';
foreach ($MODULE_DATA['ENTRIES'] as &$EN) {
    foreach ($EN['EXTDB'] as $S) {
        switch ($S['SOURCE_NAME']) {
            case 'NCIT':
            case 'DOID':
            case 'ICD10':
            case 'ICD9':
                $STR .= $S['SOURCE_NAME'] . ':' . $S['DISEASE_EXTDB'] . ' ; ';
                break;
            case 'MESH':$STR .= '<a href="' . str_replace('${LINK}', $S['DISEASE_EXTDB'], $GLB_CONFIG['LINK']['CLINVAR']['MESH']) . '">' . $S['SOURCE_NAME'] . ':' . $S['DISEASE_EXTDB'] . '</a> ; ';
                break;
            case 'EFO':$STR .= '<a href="' . str_replace('${LINK}', $S['SOURCE_NAME'] . '_' . $S['DISEASE_EXTDB'], $GLB_CONFIG['LINK']['OLS']['EFO']) . '">' . $S['SOURCE_NAME'] . ':' . $S['DISEASE_EXTDB'] . '</a> ; ';
                break;
            case 'OMIM':$STR .= '<a href="' . str_replace('${LINK}', $S['DISEASE_EXTDB'], $GLB_CONFIG['LINK']['OMIM']['OMIM']) . '">' . $S['SOURCE_NAME'] . ':' . $S['DISEASE_EXTDB'] . '</a> ; ';
                break;
        }
    }
}

changeValue("disease_overview", "SYNONYMS", substr($STR, 0, -3));

$STR = '';
$STR_N = '<div style="display:flex">';
$DR_I = array(1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV');
$MAX_LEV = 0;
foreach ($MODULE_DATA['TRIALS'] as $K => $T) {
    if ($T != 0) {
        $MAX_LEV = max($K, $MAX_LEV);
    }
}

for ($I = 1; $I <= 4; ++$I) {
    $STR_N .= '<div class="w3-col s3_1" style="
    margin-right: 1%
    margin-bottom: 5px;"><div class="text-circle blk_font" style="margin:0 auto">' . (isset($MODULE_DATA['TRIALS'][$I]) ? $MODULE_DATA['TRIALS'][$I] : 0) . '</div></div>';
    $STR .= '<div  class="chevron w3-col s3_1" style="';
    if ($I > $MAX_LEV) {
        $STR .= 'background-color:grey';
    }

    $STR .= '">' . $DR_I[$I] . '</div>';
}
changeValue("disease_overview", "TRIALS", $STR_N . '</div>' . $STR);
$FIRST = true;
$STR_TAG = '';
$STR_DEF = '';
foreach ($MODULE_DATA['ENTRIES'] as &$ENTRY) {
    if ($FIRST) {
        changeValue("disease_overview", "LABEL", $ENTRY['DISEASE_NAME']);
        changeValue("disease_overview", "DISEASE_TAG_N", $ENTRY['DISEASE_TAG']);
        $FIRST = false;
        $STR_TAG = $ENTRY['DISEASE_TAG'];
        $STR_DEF = $ENTRY['DISEASE_DEFINITION'];
    } else {
        $STR_TAG = ' / ' . $ENTRY['DISEASE_TAG'];
        $STR_DEF . ' / ' . $ENTRY['DISEASE_DEFINITION'];
    }
}
changeValue("disease_overview", "DISEASE_TAG", $STR_TAG);
changeValue("disease_overview", "DEFINITION", $STR_DEF);

?>