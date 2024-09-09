<?php

///////////////////
/////////////////// file: r_secure_params.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 11/26/18
/////////////////// purpose: Secure all parameters and store them in $USER_INPUT

if (!defined("BIORELS")) header("Location:/"); /// BIORELS defined in index.php. Not existing? Go to index.php

$USER_INPUT = array(
	'VTYPE' => 'W',
	'TOPIC' => 'GLOBAL',
	'GROUP' => 'GLOBAL',
	'PORTAL' => array('NAME' => '', 'VALUE' => array()),
	'PAGE' => array('NAME' => '', 'VALUE' => array()),
	'PARAMS' => array()
);
$QUERY_STR = '';
if (isset($_GET['draw'])) {
	$USER_INPUT['GET'] = $_GET;
	$USER_INPUT['VTYPE'] = 'd';
	$USER_INPUT['PAGE'] = 'draw';
	$USER_INPUT['PARAMS'][0] = $_GET['columns'][0]['name'];
	$USER_INPUT['TARGET'] = array('GENEID', $_GET['columns'][1]['name']);
} else if (isset($_GET['query']) && $_GET['query'] != "") {
	if (count($_GET) > 1) {
		foreach ($_GET as $K => $V) {
			if ($K != 'query' && $V == '') $_GET['query'] .= '& ' . stripslashes(htmlspecialchars($K));
		}
	}
	$QUERY = explode("/", $_GET['query']);

	foreach ($QUERY as $K=>&$V) {
		$V = stripslashes(htmlspecialchars($V, ENT_QUOTES));
		if ($V=='BIORELS_LOWER_ACCESS')
		{
			$_SESSION['LOWER_ACCESS']=true;
			unset($QUERY[$K]);

			unset($_SESSION['HIGHER_ACCESS']);
		}
		if ($V=='BIORELS_HIGHER_ACCESS')
		{
			$_SESSION['HIGHER_ACCESS']=true;
			unset($_SESSION['LOWER_ACCESS']);
			unset($QUERY[$K]);
		}
	};

	

	$QUERY=array_Values($QUERY);
	$N_GET = count($QUERY);
	$QUERY_STR = implode("|", $QUERY);

	$POS = 0;
	/// NEXT : Portal
	for ($I = $POS; $I < $N_GET; ++$I) {
		$VAL = htmlspecialchars($QUERY[$I], ENT_QUOTES);

		if ($VAL == 'PARAMS') break;
		if ($VAL != 'TOPIC') continue;

		if ($I + 1 == $N_GET) throw new Exception("/TOPIC found without value", ERR_TGT_ACC);
		$VAL = &$QUERY[$I + 1];
		$POS = $I + 1;

		foreach ($GLB_CONFIG['TOPIC'] as $NAME => &$INFO) {
			if ($VAL != $INFO['TAG'][0][0]) continue;
			$USER_INPUT['TOPIC'] = $NAME;
			$USER_INPUT['TOPIC_INFO'] = $INFO;
			$POS = $I + 2;
			break;
		}
	}

	/// Next: Subteam:
	for ($I = $POS; $I < $N_GET; ++$I) {
		$VAL = htmlspecialchars($QUERY[$I], ENT_QUOTES);
		if ($VAL == 'PARAMS') break;
		if ($VAL != 'GROUP') continue;

		if ($I + 1 == $N_GET) throw new Exception("/GROUP found without value", ERR_TGT_ACC);
		$VAL = &$QUERY[$I + 1];
		$POS = $I + 1;
		foreach ($GLB_CONFIG['GROUP'] as $NAME => &$INFO) {
			if ($VAL != $INFO['TAG']) continue;
			$GLB_CONFIG['GROUP'] = $NAME;
			break;
		}
	}

	/// Next: Export tag
	for ($I = $POS; $I < $N_GET; ++$I) {

		$VAL = htmlspecialchars($QUERY[$I], ENT_QUOTES);
		if ($VAL == 'PARAMS') break;
		if (in_array($VAL, $GLB_CONFIG['EXPORT_TAG'])) {
			$USER_INPUT['VTYPE'] = $VAL;
			$POS = $I + 1;
			break;
		}
	}
	// echo "PORTAL";
	/// Next: Portal
	for ($I = $POS; $I < $N_GET; ++$I) {
		$VAL = htmlspecialchars($QUERY[$I], ENT_QUOTES);
		if ($VAL == 'PARAMS') break;
		$FOUND = false;
		//// echo $VAL."\n";exit;

		//	// echo "POS:".$I."\n";


		foreach ($GLB_CONFIG['PORTAL'] as $NAME => &$PRE_INFO) {
			// echo '<pre>';
			// echo $NAME;
			// print_r($PRE_INFO);
			foreach ($PRE_INFO as &$INFO) {
				if (isset($INFO['TAG']))
					foreach ($INFO['TAG'] as $TAGS) {

						// //// echo $I.' '.count($QUERY)."\n";
						// if ($I + 1 >= $N_GET) throw new Exception("TAG PORTAL " . $NAME . " expect a value", ERR_TGT_ACC);
						// $TAG=&$QUERY[$I];
						// $VAL = &$QUERY[$I + 1];
						// // // echo $TAG.':'. $VAL.' '.$TAGS[0]."\t".$TAGS[1]."\n";

						//  if ($TAG != $TAGS[0]) continue;

						if ($VAL != $TAGS[0]) continue;
						// echo "TADA\n";

						//// echo 'in';exit;

						if (count($TAGS) == 1) {

							$POS = $I + 1;
							$FOUND = true;
							$USER_INPUT['PAGE']['NAME'] = $NAME;
							continue;
						}
						if ($I + 1 == $N_GET) throw new Exception("PORTAL " . $NAME . " expect a value", ERR_TGT_ACC);



						// echo "TADAS\n";
						//$POS=$I+2;
						$VAL2 = &$QUERY[$I + 1];
						if (strpos($TAGS[1], 'REGEX:') !== false) {

							// echo $TAGS[1]." ".$VAL2."\n";
							$res = checkRegex($VAL2, $TAGS[1]);

							if ($res === false) continue;
							$POS = $I + 2;
							$USER_INPUT['PORTAL']['NAME'] = $NAME;
							$USER_INPUT['PORTAL']['TYPE'] = $TAGS[0];
							$USER_INPUT['PORTAL']['VALUE'] = $res[0];
							$FOUND = true;
							break;
						} else {


							if (preg_match('/' . $TAGS[1] . '/', $VAL2, $matches) == 0) continue;

							$POS = $I + 2;
							$USER_INPUT['PORTAL']['NAME'] = $NAME;
							$USER_INPUT['PORTAL']['TYPE'] = $TAGS[0];
							$USER_INPUT['PORTAL']['VALUE'] = $matches[0];
							$FOUND = true;
							break;
						}
						// echo "TADAX".$FOUND."\n";
					}
				if ($FOUND) break;
			}
		}
		if ($FOUND) break;
	}

	if ($POS == $N_GET) $USER_INPUT['PAGE']['NAME'] = 'WELCOME';
	//// echo "PAGE";
	///Next: PAGE:

	for ($I = $POS; $I < $N_GET; ++$I) {

		$VAL = htmlspecialchars($QUERY[$I], ENT_QUOTES);
		if ($VAL == 'PARAMS') break;
		$FOUND = false;
		// echo '<pre>';
		//  echo "TEST PAGE:".$I."\n";		

		foreach ($GLB_CONFIG['PAGE'] as $NAME => &$PRE_INFO)
			foreach ($PRE_INFO as &$INFO) {
				//echo $NAME."\t".$VAL."\t".(($NAME==$VAL)?"MATCH":'')."\n";
				if (isset($INFO['TAG'])) {
					//	// echo $NAME."\t".$VAL."\n";
					foreach ($INFO['TAG'] as $TAGS) {
						//// echo "\t\t".$TAGS[0]."\n";
						if ($VAL != $TAGS[0]) continue;


						//// echo 'in';exit;

						if (count($TAGS) == 1) {

							$POS = $I + 1;
							$FOUND = true;
							$USER_INPUT['PAGE']['NAME'] = $NAME;
							continue;
						}
						if ($I + 1 == $N_GET) throw new Exception("TAG " . $NAME . " expect a value", ERR_TGT_ACC);


						$VAL2 = &$QUERY[$I + 1];
						//// echo $VAL2;exit;
						if (strpos($TAGS[1], 'REGEX:') !== false) {

							$res = checkRegex($VAL2, $TAGS[1]);
							if ($res === false) continue;
							$POS = $I + 2;
							$USER_INPUT['PAGE']['NAME'] = $NAME;
							$USER_INPUT['PAGE']['VALUE'] = $res[0];
							$FOUND = true;
							break;
						} else {



							if (preg_match('/' . $TAGS[1] . '/', $VAL2, $matches) == 0) continue;

							$POS = $I + 2;
							$USER_INPUT['PAGE']['NAME'] = $NAME;
							$USER_INPUT['PAGE']['VALUE'] = $matches[0];
							$FOUND = true;
							break;
						}
					}

					//if (!$FOUND)throw new Exception("Wrong format for page ".$NAME." value: ".$VAL,ERR_TGT_ACC);
				}
				// if ($FOUND) break;
			}
		if ($FOUND) break;
	}
	// exit;
	//// echo "NEXT";
	for ($I = $POS; $I < $N_GET; ++$I) {
		$VAL = htmlspecialchars($QUERY[$I], ENT_QUOTES);

		if ($VAL != 'PARAMS') continue;
		for ($J = $POS + 1; $J < $N_GET; ++$J) {
			$USER_INPUT['PARAMS'][] = $QUERY[$J];
			$POS = $J + 1;
		}
		break;
	}

	if ($POS != $N_GET) {
		$USER_INPUT['STATUS'] = array('ISSUE', 'Unrecognized inputs');
	}

	if (count($_POST) > 0) {
		foreach ($_POST as $K => $V) {
			if ($V == '') continue;
			$USER_INPUT['PARAMS'][] = $K;
			if (!is_array($V)) $USER_INPUT['PARAMS'][] = htmlspecialchars($V, ENT_QUOTES);
			else $USER_INPUT['PARAMS'][] = $V;
		}
	}
} else $USER_INPUT['PAGE']['NAME'] = 'WELCOME';



function checkRegex($VALUE, $TAG)
{
	global $GLB_CONFIG;

	$REGEX = $GLB_CONFIG['REGEX'][substr($TAG, strpos($TAG, ':') + 1)];

	foreach ($REGEX as $RGX) {

		$matches = array();
		if (preg_match('/' . $RGX . '/', $VALUE, $matches) == 0) continue;
		return $matches;
	}
	return false;
}
unset($_GET);
unset($_POST);
