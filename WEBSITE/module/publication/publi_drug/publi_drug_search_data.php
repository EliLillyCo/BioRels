<?php


if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit', '1000M');

$DRUG_ENTRY_ID = $USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID'];
if (isset($USER_INPUT['PORTAL']['DATA']['DRUG_ENTRY_ID'])) {
	$FILTERS = array();
	for ($I = 0; $I < count($USER_INPUT['PARAMS']); ++$I) {

		if ($USER_INPUT['PARAMS'][$I] == 'PER_PAGE') {
			if ($I + 1 == count($USER_INPUT['PARAMS'])) throw new Exception("No value provided for PER_PAGE", ERR_TGT_USR);
			$PER_PAGE = $USER_INPUT['PARAMS'][$I + 1];
			if (!is_numeric($PER_PAGE)) throw new Exception("Expected numeric value  for PER_PAGE", ERR_TGT_USR);
			$I += 1;
		}
		if ($USER_INPUT['PARAMS'][$I] == 'PAGE') {
			if ($I + 1 == count($USER_INPUT['PARAMS'])) throw new Exception("No value provided for PAGE", ERR_TGT_USR);
			$PAGE = $USER_INPUT['PARAMS'][$I + 1];
			if (!is_numeric($PAGE)) throw new Exception("Expected numeric value  for PAGE", ERR_TGT_USR);
			$I += 1;
		}
		if ($USER_INPUT['PARAMS'][$I] == 'FILTERS') {
			if ($I + 1 == count($USER_INPUT['PARAMS'])) throw new Exception("No value provided for FILTERS", ERR_TGT_USR);
			$FILTER = $USER_INPUT['PARAMS'][$I + 1];
			$I += 1;
			$tabR = array_filter(explode(";", $FILTER));
			foreach ($tabR as $value) {

				$tab = explode("-", $value);

				if (count($tab) == 1) {
					exit;
					throw new Exception("Missing value for FILTERS", ERR_TGT_USR);
				}
				if ($tab[0] != 'drug' && $tab[0] != 'compound' && $tab[0] != 'gene' && $tab[0] != 'topic' && $tab[0] != 'disease') throw new Exception("Wrong format for FILTERS", ERR_TGT_USR);
				$FILTERS[$tab[0]][] = $tab[1];
			}
		}
	}

	$time = microtime_float();

	$MODULE_DATA['STAT'] = getCountPubliDrug($DRUG_ENTRY_ID, $FILTERS);


	$MODULE_DATA['RESULTS'] = array();
	if ($MODULE_DATA['STAT']['CO'] != 0) {

		$MODULE_DATA['STAT']['MIN_PAGE'] = ($PAGE - 1) * $PER_PAGE;
		$MODULE_DATA['STAT']['MAX_PAGE'] = ($PAGE) * $PER_PAGE;
		$time = microtime_float();
		$MODULE_DATA['RESULTS'] = getPubliFromDrug($DRUG_ENTRY_ID, array('MIN' => ($PAGE - 1) * $PER_PAGE, 'MAX' => ($PAGE) * $PER_PAGE), $FILTERS);
	}
	$MODULE_DATA['TIME']['RESULTS'] = round(microtime_float() - $time, 2);
}
