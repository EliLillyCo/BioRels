<?php

ini_set('memory_limit', '800M');
$TARGET = $USER_INPUT['PORTAL']['DATA'];
$MODULE_DATA = getGenePubliYearStat($TARGET['GN_ENTRY_ID']);

?>