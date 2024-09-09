<?php
global $GLB_CONFIG;

changeValue("header", "F:link", file_get_contents("module/" . $CURRENT_MODULE['LOC'] . '/dev_link.html'));


if (array_key_exists("GRAPH_REDIRECT_URL", $GLB_CONFIG['GLOBAL'])) {
    changeValue("header", "REDIRECT_URL", $GLB_CONFIG['GLOBAL']['GRAPH_REDIRECT_URL']);
    changeValue("header", "AUTHORITY", $GLB_CONFIG['GLOBAL']['GRAPH_AUTHORITY']);
    changeValue("header", "CLIENT_ID", $GLB_CONFIG['GLOBAL']['GRAPH_CLIENT_ID']);
}

if ($GLB_CONFIG['GLOBAL']['PRD_STATUS'] == "F") {
    changeValue("header", "DEBUG", '<div id="body_debug" style="position: fixed;top: 47px;left: 5px;color: white;" onclick="$(\'#debug\').toggle()">debug</div>');
} else {
    changeValue("header", "DEBUG", '');
}
	 removeBlock("header", "FULL_ACCESS");
    
changeValue("header", "USER", $USER['full_name']);

switch ($USER_INPUT['PORTAL']['NAME']) {
    case 'GENE':
        changeValue("header", "PORTAL_COLOR", 'portal_col1');
        break;
    case 'DISEASE':
        changeValue("header", "PORTAL_COLOR", 'portal_col2');
        break;
    case 'PROTEIN':
        changeValue("header", "PORTAL_COLOR", 'portal_col3');
        break;
    case 'MOLECULE':
        changeValue("header", "PORTAL_COLOR", 'portal_col4');
        break;
    case 'TISSUE':
        changeValue("header", "PORTAL_COLOR", 'portal_col5');
        break;
    case 'PUBLICATION':
        changeValue("header", "PORTAL_COLOR", 'portal_col6');
        break;
    case 'ASSAY':
        changeValue("header", "PORTAL_COLOR", 'portal_col7');
        break;
    case 'COMPOUND':
        changeValue("header", "PORTAL_COLOR", 'portal_col9');
        break;
    case 'DRUG':
        changeValue("header", "PORTAL_COLOR", 'portal_col11');
        break;
}

switch ($USER_INPUT['PORTAL']['NAME']) {
    case 'GENE':
        changeValue("header", "PORTAL_COLOR", 'portal_col1');
        break;
    case 'DISEASE':
        changeValue("header", "PORTAL_COLOR", 'portal_col2');
        break;
    case 'PROTEIN':
        changeValue("header", "PORTAL_COLOR", 'portal_col3');
        break;
    case 'MOLECULE':
        changeValue("header", "PORTAL_COLOR", 'portal_col4');
        break;
    case 'TISSUE':
        changeValue("header", "PORTAL_COLOR", 'portal_col5');
        break;
    case 'PUBLICATION':
        changeValue("header", "PORTAL_COLOR", 'portal_col6');
        break;
    case 'ASSAY':
        changeValue("header", "PORTAL_COLOR", 'portal_col7');
        break;
    case 'COMPOUND':
        changeValue("header", "PORTAL_COLOR", 'portal_col9');
        break;
    case 'DRUG':
        changeValue("header", "PORTAL_COLOR", 'portal_col11');
        break;
}
