<?php


$SEARCH_VALUE = htmlentities(strip_tags(trim(str_replace("___", "/", $USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['DRUG'] = array();
$time = microtime_float();
$result['TIME'] = array();
$result['count'] = 0;
$FIRST = true;
$SP = 'ALL';


$SEARCH_TYPE = 'DRUG_ANY';
$SOURCE = '';
try {
	$LIST_TYPES = array('DRUG_ANY', 'DRUG_NAME');
	if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS']) > 0) {

		foreach ($USER_INPUT['PARAMS'] as $VAL) {
			if (in_array($VAL, $LIST_TYPES)) $SEARCH_TYPE = $VAL;
		}
	}

	if ($SEARCH_TYPE == 'DRUG_ANY' || $SEARCH_TYPE == 'DRUG_NAME') {

		$ini_data = searchDrugByName($SEARCH_VALUE);
		

		foreach ($ini_data as $line) {
			if (!isset($SEARCH_RESULTS['DRUG'][$line['DRUG_ENTRY_ID']])) {

				// 
				$ENTRY = array(
					'Drug Name' => $line['DRUG_PRIMARY_NAME'],
					'Is Approved' => $line['IS_APPROVED'],
					'Is Investigational' => $line['IS_INVESTIGATIONAL'],
					
					'Source' => 'Name'

				);
				if (isset($line['SM']))
				{
					$ENTRY['Structure']= $line['SM'][0]['FULL_SMILES'];
				}

				$SEARCH_RESULTS['DRUG'][$line['DRUG_ENTRY_ID']] = $ENTRY;
			}
		}

		$result['TIME']['NAME'] = microtime_float() - $time;
		$time = microtime_float();
	}

	if (count($SEARCH_RESULTS['DRUG']) == 1) {

		removeBlock("search_drug", "MULTI");
		foreach ($SEARCH_RESULTS['DRUG'] as $K) {
			changeValue("search_drug", "COUNT", 1);
			changeValue("search_drug", "ADDON", '/DRUG/' . $SEARCH_VALUE);
		}
		$result['count'] = 1;
	} else {
		
		//echo '<pre>';print_R($SEARCH_RESULTS);exit;
		changeValue("search_drug", "result", str_replace("'","\\'",str_replace("\\", "\\\\", json_encode(array_values($SEARCH_RESULTS['DRUG'])))));
		changeValue("search_drug", "COUNT", count($SEARCH_RESULTS['DRUG']));
		changeValue("search_drug", "NPAGE", ceil(count($SEARCH_RESULTS['DRUG']) / 10));
		changeValue("search_drug", "QUERY_NAME", $SEARCH_VALUE);
		removeBlock("search_drug", "SINGLE");

		$result['count'] = count($SEARCH_RESULTS['DRUG']);
		removeBlock("search_drug", "SINGLE");

		$result['count'] = count($SEARCH_RESULTS['DRUG']);
	}
	removeBlock("search_drug", "INVALID");
} catch (Exception $e) {
	removeBlock("search_drug", "SINGLE");
	removeBlock("search_drug", "MULTI");
}

cleanRules("search_drug");
if ($USER_INPUT['VTYPE'] == 'JSON') {
	if (ob_get_contents()) ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
	echo json_encode($SEARCH_RESULTS['DRUG']);
	exit;
}
$result['code'] = $HTML["search_drug"];

if (ob_get_contents()) ob_end_clean();
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

echo json_encode($result);
exit;
