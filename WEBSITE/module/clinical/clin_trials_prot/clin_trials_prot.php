<?php

if (!defined("BIORELS")) header("Location:/");
if (count($MODULE_DATA) == 0) {
	removeBlock("clin_trials_prot", "VALID");

	return;
} else removeBlock("clin_trials_prot", "INVALID");

$PATH_T = '';
if ($USER_INPUT['PORTAL']['NAME'] == 'GENE') {
	changeValue("clin_trials_prot", "PATH", '/GENEID/' . $USER_INPUT['PORTAL']['VALUE']);
	removeBlock("clin_trials_prot", "VALID_DISEASE");
	removeBlock("clin_trials_prot", "VALID_DRUG");
	$PATH_T = 'DRUG';
} else if ($USER_INPUT['PORTAL']['NAME'] == 'DISEASE') {
	changeValue("clin_trials_prot", "PATH", '/DISEASE/' . $USER_INPUT['PORTAL']['VALUE']);
	removeBlock("clin_trials_prot", "VALID_GENE");
	removeBlock("clin_trials_prot", "VALID_DRUG");
	$PATH_T = 'DRUG';
} else {
	if ($USER_INPUT['PORTAL']['TYPE'] == 'DRUG') $PATH_T = 'DRUG';
	else $PATH_T = 'COMPOUND';

	changeValue("clin_trials_prot", "PATH", '/COMPOUND/' . $USER_INPUT['PORTAL']['VALUE']);
	removeBlock("clin_trials_prot", "VALID_DISEASE");
	removeBlock("clin_trials_prot", "VALID_GENE");
}
$STATUS = array(
	'Unknown status' => array('#000'),
	'Not yet recruiting' => array('#a1a1a1'),

	'Enrolling by invitation' => array('#a4dd74'),
	'Recruiting' => array('#a4dd74'),
	'Active, not recruiting' => array('#33ae10'),
	'Suspended' => array('#e85342'),
	'Withdrawn' => array('#e85342'),
	'Terminated' => array('#e85342'),
	'Completed' => array('#5454E8')
);

$STATS = array(0 => array(), 'I' => array(), 'II' => array(), 'III' => array(), 'IV' => array());
$PHASE_MAP = array(0 => 0, 1 => 'I', 2 => 'II', 3 => 'III', '4' => 'IV');
$LIST_TYPES = array();
$str = '';
foreach ($MODULE_DATA as $ENTRY) {
	$LIST_TYPES[$ENTRY['CLINICAL_STATUS']] = true;
	if (!isset($STATS[$PHASE_MAP[$ENTRY['CLINICAL_PHASE']]][$ENTRY['CLINICAL_STATUS']])) $STATS[$PHASE_MAP[$ENTRY['CLINICAL_PHASE']]][$ENTRY['CLINICAL_STATUS']] = 0;
	if ($ENTRY['CLINICAL_STATUS'] == '') $ENTRY['CLINICAL_STATUS'] = 'Unknown status';
	$STATS[$PHASE_MAP[$ENTRY['CLINICAL_PHASE']]][$ENTRY['CLINICAL_STATUS']]++;
	if (substr($ENTRY['TRIAL_ID'], 0, 3) == 'NCT') {
		if ($USER_INPUT['PORTAL']['NAME'] == 'GENE') $str .= '<td><a class="blk_font"  href="/GENEID/' . $USER_INPUT['PORTAL']['VALUE'] . '/CLINICAL_TRIAL/' . $ENTRY['TRIAL_ID'].'">' . $ENTRY['TRIAL_ID'] . '</a></td>';
		if ($USER_INPUT['PORTAL']['NAME'] == 'COMPOUND') $str .= '<td><a class="blk_font" href="/' . $PATH_T . '/' . $USER_INPUT['PORTAL']['VALUE'] . '/CLINICAL_TRIAL/' . $ENTRY['TRIAL_ID'] . '">' . $ENTRY['TRIAL_ID'] . '</a></td>';
		if ($USER_INPUT['PORTAL']['NAME'] == 'DRUG') $str .= '<td> <a class="blk_font" href="/' . $PATH_T . '/' . $USER_INPUT['PORTAL']['VALUE'] . '/CLINICAL_TRIAL/' . $ENTRY['TRIAL_ID'] . '">' . $ENTRY['TRIAL_ID'] . '</a></td>';
		if ($USER_INPUT['PORTAL']['NAME'] == 'DISEASE') $str .= '<td><a class="blk_font" href="/DISEASE/' . $USER_INPUT['PORTAL']['VALUE'] . '/CLINICAL_TRIAL/' . $ENTRY['TRIAL_ID'] . '">' . $ENTRY['TRIAL_ID'] . '</a></td>';
	} else
		$str .= '<td><a class="blk_font" onclick="' . str_replace('${LINK}', $ENTRY['TRIAL_ID'], $GLB_CONFIG['LINK']['CLINICAL']['TRIAL']) . '">' . $ENTRY['TRIAL_ID'] . '</td>';

	if ($USER_INPUT['PORTAL']['NAME'] == 'GENE' || $USER_INPUT['PORTAL']['NAME'] == 'DISEASE') $str .= '<td><a  class="blk_font" href="/' . $PATH_T . '/' . $ENTRY['DRUG_PRIMARY_NAME'] . '">' . $ENTRY['DRUG_PRIMARY_NAME'] . '</a></td>';
	if ($USER_INPUT['PORTAL']['NAME'] == 'GENE' || $USER_INPUT['PORTAL']['NAME'] == 'COMPOUND' || $USER_INPUT['PORTAL']['NAME'] == 'DRUG') $str .= '<td><a class="blk_font" href="/DISEASE/' . $ENTRY['DISEASE_TAG'] . '">' . $ENTRY['DISEASE_NAME'] . '</a></td>';
	if ($USER_INPUT['PORTAL']['NAME'] == 'DISEASE' || $USER_INPUT['PORTAL']['NAME'] == 'COMPOUND' || $USER_INPUT['PORTAL']['NAME'] == 'DRUG') $str .= '<td><a class="blk_font" href="/GENEID/' . $ENTRY['GENE_ID'] . '">' . $ENTRY['SYMBOL'] . '</a></td><td><a  class="blk_font" href="/GENEID/' . $ENTRY['GENE_ID'] . '">' . $ENTRY['GENE_ID'] . '</a></td>';

	$str .= '<td>' . $ENTRY['CLINICAL_PHASE'] . '</td>
	<td>' . $ENTRY['START_DATE'] . '</td>
	<td>' . $ENTRY['CLINICAL_STATUS'] . '</td>
</tr>';
}
changeValue("clin_trials_prot", "LIST", $str);

print_r($STATS);
$MAX_V = 0;
foreach ($STATS as $T => $V) $MAX_V = max($MAX_V, array_sum($V));

changeValue("clin_trials_prot", "MAX_V", $MAX_V);

$STR = "Phase";
$LEGEND = '';
$N = -1;
$L = 0;
$COLORS = '';
foreach ($STATUS as $K => $V) {
	if (!isset($LIST_TYPES[$K])) continue;
	$STR .= ',' . $K;
	++$N;
	$COLORS .= "'" . $V[0] . "',";
	$LEGEND .= 'svg.append("circle").attr("cx",' . (10 + $N * 120) . ').attr("cy",' . (390 + $L * 15) . ').attr("r", 4).style("fill", "' . $V[0] . '")' . "\n" .
		'svg.append("text").attr("x", ' . (20 + $N * 120) . ').attr("y", ' . (390 + $L * 15) . ').text("' . $K . '").style("font-size", "10px").attr("alignment-baseline","middle")' . "\n";
	if ($N >= 2) {
		$L++;
		$N = -1;
	}
}
$STR .= "\n";
foreach ($STATS as $CLIN_PHASE => &$LIST_T) {

	$STR .= $CLIN_PHASE;
	foreach ($STATUS as $K => $V) {
		if (!isset($LIST_TYPES[$K])) continue;
		if (!isset($LIST_T[$K])) $STR .= ',0';
		else $STR .= ',' . $LIST_T[$K];
	}
	$STR .= "\n";
}
changeValue("clin_trials_prot", "RESULTS_STR", $STR);
changeValue("clin_trials_prot", "LEGEND", $LEGEND);
changeValue("clin_trials_prot", "COLORS", substr($COLORS, 0, -1));



$USER_INPUT['PAGE']['VALUE'] = '33196847';
$STR = loadHTMLAndRemove('PUBLICATION');
changeValue("clin_trials_prot", "SOURCE", $STR);
