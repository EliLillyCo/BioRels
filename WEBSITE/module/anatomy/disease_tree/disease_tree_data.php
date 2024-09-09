<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

ini_set('memory_limit', '1000M');

if (!isset($USER_INPUT['PARAMS']) || $USER_INPUT['PARAMS'] == array()) {
    $ENTRY = getDiseaseEntry('disease');
    $KEY = array_keys($ENTRY['ENTRIES'])[0];
    $MODULE_DATA = array();
    $MODULE_DATA[] = array("id" => $ENTRY['ENTRIES'][$KEY]['DISEASE_TAG'], 'parent' => '#', 'text' => $ENTRY['ENTRIES'][$KEY]['DISEASE_NAME'], 'data' => array('level' => $ENTRY['ENTRIES'][$KEY]['DISEASE_LEVEL']));

    $CHILDS = getChildDisease($ENTRY['ENTRIES'][$KEY]['DISEASE_ENTRY_ID'], $ENTRY['ENTRIES'][$KEY]['DISEASE_LEVEL']);

    foreach ($CHILDS as $CHILD) {
        $MODULE_DATA[] = array("id" => $CHILD['DISEASE_TAG'], 'parent' => $ENTRY['ENTRIES'][$KEY]['DISEASE_TAG'], 'text' => ucfirst($CHILD['DISEASE_NAME']), 'children' => true, 'data' => array('level' => $CHILD['DISEASE_LEVEL']/*,'left'=>$CHILD['EFO_LEVEL_LEFT'],'right'=>$CHILD['EFO_LEVEL_RIGHT']*/));
    }
} else if (count($USER_INPUT['PARAMS']) == 2) {
    $NAME = $USER_INPUT['PARAMS'][0];
    $LEVEL = $USER_INPUT['PARAMS'][1];
    $ENTRY = getDiseaseEntry($NAME, true);
    $KEY = array_keys($ENTRY['ENTRIES'])[0];
    $TMP = getChildDisease($ENTRY['ENTRIES'][$KEY]['DISEASE_ENTRY_ID'], $LEVEL);

    $CHILDS = array();
    foreach ($TMP as $T) {
        $CHILDS[$T['DISEASE_TAG']] = $T;
    }

    foreach ($CHILDS as $CHILD) {
        $MODULE_DATA[] = array("id" => $CHILD['DISEASE_TAG'], 'parent' => $ENTRY['ENTRIES'][$KEY]['DISEASE_TAG'], 'text' => ucfirst($CHILD['DISEASE_NAME']), 'data' => array('level' => $CHILD['DISEASE_LEVEL']/*,'left'=>$CHILD['EFO_LEVEL_LEFT'],'right'=>$CHILD['EFO_LEVEL_RIGHT']*/), 'children' => ($CHILD['DISEASE_LEVEL_RIGHT'] - $CHILD['DISEASE_LEVEL_LEFT'] > 1));
    }
} else if (count($USER_INPUT['PARAMS']) == 1) {
    $ROOT = getDiseaseEntry('disease');
    $NAME = $USER_INPUT['PARAMS'][0];

    $ENTRY = array_values(getDiseaseEntry($NAME, true)['ENTRIES'])[0];
    $TMP = getDiseaseHierarchy($ENTRY['DISEASE_ENTRY_ID'], true);

    foreach ($TMP as $CHILD) {
        $MODULE_DATA[] = array("id" => $CHILD['DISEASE_TAG'], 'parent' => isset($CHILD['PARENT']) ? $CHILD['PARENT'] : "#", 'text' => ucfirst($CHILD['DISEASE_NAME']));
    }
}

/*
{ "id": "ajson1", "parent": "#", "text": "Simple root node" },
{ "id": "ajson2", "parent": "#", "text": "Root node 2" },
{ "id": "ajson3", "parent": "ajson2", "text": "Child 1" },
{ "id": "ajson4", "parent": "ajson2", "text": "Child 2" }, */

?>