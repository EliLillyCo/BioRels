<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$NAME = $USER_INPUT['PAGE']['VALUE'];

$MODULE_DATA = getDiseaseEntry($NAME, false, true);

foreach ($MODULE_DATA['ENTRIES'] as $ID => &$ENTRY) {
    $ENTRY['INFO'] = getDiseaseInfo($ID);
}

?>