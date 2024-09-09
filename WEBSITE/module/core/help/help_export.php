<?php

if (!defined("BIORELS")) header("Location:/");


$type = 'PAGE';
$fname = $USER_INPUT['PAGE']['VALUE'];
try{
$CURRENT_MODULE = null;
if (!isset(($GLB_CONFIG[$type][$fname]))) throw new Exception("Unable to find help for " . $fname, ERR_TGT_SYS);
foreach ($GLB_CONFIG[$type][$fname] as $K_MOD => $TEST_MOD) {
	for ($I = 0; $I < strlen($TEST_MOD['LEVEL']); ++$I) {
		if (substr($TEST_MOD['LEVEL'], $I, 1) == 1 && $USER['Access'][$I] == 1) {
			$CURRENT_MODULE = &$GLB_CONFIG[$type][$fname][$K_MOD];
		}
	}
}
if ($CURRENT_MODULE == null) throw new Exception("Unable to find help for " . $fname, ERR_TGT_SYS);

$DIR = '';
if (isset($CURRENT_MODULE['IS_PRIVATE'])) {
	$DIR = 'private/';
}

if (!isset($CURRENT_MODULE['HTML_TAG'])) {
	throw new Exception("Unable to find HTML_TAG for " . $fname, ERR_TGT_SYS);
}
if (!isset($CURRENT_MODULE['FNAME'])) {
	throw new Exception("Unable to find FNAME for " . $fname, ERR_TGT_SYS);
}
if (!isset($CURRENT_MODULE['LOC'])) {
	throw new Exception("Unable to find LOC for " . $fname, ERR_TGT_SYS);
}

$DIR .= 'module/' . $CURRENT_MODULE['LOC'];
if (!is_dir($DIR)) {
	throw new Exception("Unable to find directory " . getcwd() . $DIR . " for " . $fname, ERR_TGT_SYS);
}
$TAG = $CURRENT_MODULE['HTML_TAG'] . '_help';
$FNAME = $CURRENT_MODULE['FNAME'];
$HTML_PATH = $DIR . '/' . $FNAME . '_help.html';

global $HTML;
global $HTML_ORDER;
global $HTML_BLOCKS;

switch ($USER_INPUT['VTYPE']) {
	case 'W':
		$HTML[$TAG] = file_get_contents($HTML_PATH);
		if ($TAG != "HEADER") {
			$HTML_ORDER[$HTML_BLOCKS] = $TAG;
			$HTML_BLOCKS++;
		} else $HTML_ORDER[0] = $TAG;

		break;
	case 'CONTENT':
		$result['code'] = file_get_contents($HTML_PATH);
		if (ob_get_contents()) ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($result);
		exit;
		break;
	case 'JSON':
		$MODULE_DATA = file_get_contents($HTML_PATH);
		if (ob_get_contents()) ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
}
}
catch(Exception $e)
{
	switch ($USER_INPUT['VTYPE']) {
		case 'W':
			$HTML[$TAG] = '<div class="alert alert-info">Unable to retrieve the help for '.$fname.'</div>';
			if ($TAG != "HEADER") {
				$HTML_ORDER[$HTML_BLOCKS] = $TAG;
				$HTML_BLOCKS++;
			} else $HTML_ORDER[0] = $TAG;
	
			break;
		case 'CONTENT':
			header('Content-type: application/json');
			$result['code'] = '<div class="alert alert-info">Unable to retrieve the help for '.$fname.'</div>';
			if (ob_get_contents()) ob_end_clean();
			echo json_encode($result);
			exit;
			break;
	
	}
}
?>