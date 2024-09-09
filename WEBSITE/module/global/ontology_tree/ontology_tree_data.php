<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

if (!isset($USER_INPUT['PARAMS']) || $USER_INPUT['PARAMS'] == array()) {
    $ENTRY = getOntologyEntry('Root');
    $MODULE_DATA = array();
    $MODULE_DATA[] = array("id" => $ENTRY['ONTOLOGY_TAG'], 'parent' => '#', 'text' => $ENTRY['ONTOLOGY_NAME'], 'data' => array('level' => $ENTRY['ONTOLOGY_LEVEL']));
    $CHILDS = getChildOntology($ENTRY['ONTOLOGY_ENTRY_ID'], $ENTRY['ONTOLOGY_LEVEL']);
    foreach ($CHILDS as $CHILD) {
        $MODULE_DATA[] = array("id" => $CHILD['ONTOLOGY_TAG'], 'parent' => $ENTRY['ONTOLOGY_TAG'], 'text' => ucfirst($CHILD['ONTOLOGY_NAME']), 'children' => true, 'data' => array('level' => $CHILD['ONTOLOGY_LEVEL']/*,'left'=>$CHILD['EFO_LEVEL_LEFT'],'right'=>$CHILD['EFO_LEVEL_RIGHT']*/));
    }
} else if (count($USER_INPUT['PARAMS']) == 2) {
    $NAME = $USER_INPUT['PARAMS'][0];
    $LEVEL = $USER_INPUT['PARAMS'][1];
    $ENTRY = getOntologyEntry($NAME, true);
    $TMP = getChildOntology($ENTRY['ONTOLOGY_ENTRY_ID'], $LEVEL);
    $CHILDS = array();
    foreach ($TMP as $T) {
        $CHILDS[$T['ONTOLOGY_TAG']] = $T;
    }

    foreach ($CHILDS as $CHILD) {
        $MODULE_DATA[] = array("id" => $CHILD['ONTOLOGY_TAG'], 'parent' => $ENTRY['ONTOLOGY_TAG'], 'text' => ucfirst($CHILD['ONTOLOGY_NAME']), 'data' => array('level' => $CHILD['ONTOLOGY_LEVEL']/*,'left'=>$CHILD['EFO_LEVEL_LEFT'],'right'=>$CHILD['EFO_LEVEL_RIGHT']*/), 'children' => ($CHILD['ONTOLOGY_LEVEL_RIGHT'] - $CHILD['ONTOLOGY_LEVEL_LEFT'] > 1));
    }

} else if (count($USER_INPUT['PARAMS']) == 1) {
    $NAME = $USER_INPUT['PARAMS'][0];

    $ENTRY = getDiseaseEntry($NAME, true);
    $TMP = getDiseaseHierarchy($ENTRY['ONTOLOGY_ENTRY_ID'], true);

    foreach ($TMP as $CHILD) {
        $MODULE_DATA[] = array("id" => $CHILD['ONTOLOGY_TAG'], 'parent' => isset($CHILD['PARENT']) ? $CHILD['PARENT'] : "#", 'text' => ucfirst($CHILD['ONTOLOGY_NAME']));
    }
}

?>