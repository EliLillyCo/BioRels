<?php

if (!defined("BIORELS")) header("Location:/");

if (!isset($MODULE_DATA['CLINICAL_STAT']['ONGOING']))
{
removeBlock("disease_ongoing_trial",'HAS_RECORD');
return;
}
else removeBlock("disease_ongoing_trial",'NO_RECORD');


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
$PATH_T='DISEASE';
$STATS = array(0 => array(), 'I' => array(), 'II' => array(), 'III' => array(), 'IV' => array());
$PHASE_MAP = array(0 => 0, 1 => 'I', 2 => 'II', 3 => 'III', '4' => 'IV');
$LIST_TYPES = array();
$str = '';
$SINGLE_DS=(count($MODULE_DATA['CLINICAL_STAT']['DISEASE'])==1);
if ($SINGLE_DS)removeBlock("disease_ongoing_trial",'DISEASE');
//echo '<pre>';print_r($MODULE_DATA['CLINICAL_STAT']);exit;
$DRUGS=&$MODULE_DATA['CLINICAL_STAT']['DRUG'];
$GENES=&$MODULE_DATA['CLINICAL_STAT']['GENE'];
$DISEASE=array();
foreach ($MODULE_DATA['CLINICAL_STAT']['DISEASE'] as $DS)$DISEASE[$DS['DISEASE_ENTRY_ID']]=$DS['DISEASE_NAME'];

foreach ($MODULE_DATA['CLINICAL_STAT']['ONGOING'] as $ENTRY) {
	$LIST_TYPES[$ENTRY['CLINICAL_STATUS']] = true;
	if ($ENTRY['CLINICAL_STATUS'] == '') $ENTRY['CLINICAL_STATUS'] = 'Unknown status';
	$STATS[$PHASE_MAP[$ENTRY['CLINICAL_PHASE']]][$ENTRY['CLINICAL_STATUS']]++;
	$str.='<tr>';
	if (!$SINGLE_DS)
	{
		$str.='<td>'.$DISEASE[$ENTRY['DISEASE_ENTRY_ID']].'</td>';
	}
	if (substr($ENTRY['TRIAL_ID'], 0, 3) == 'NCT') {
		$str .= '<td class="blk_font" onclick="relocate(\'/' . $PATH_T . '/' . $USER_INPUT['PORTAL']['VALUE'] . '/CLINICAL_TRIAL/' . $ENTRY['TRIAL_ID'] . '\')">' . $ENTRY['TRIAL_ID'] . '</td>';
	} else
		$str .= '<td class="blk_font" onclick="relocate(\'' . str_replace('${LINK}', $ENTRY['TRIAL_ID'], $GLB_CONFIG['LINK']['CLINICAL']['TRIAL']) . '\')">' . $ENTRY['TRIAL_ID'] . '</td>';

    $str .= '<td class="blk_font" onclick="relocate(\'/DRUG/' . $DRUGS[$ENTRY['DRUG_ENTRY_ID']]['DRUG_PRIMARY_NAME'] . '\')">' .$DRUGS[$ENTRY['DRUG_ENTRY_ID']]['DRUG_PRIMARY_NAME']  . '</td>';
    if (isset($GENES[$ENTRY['GN_ENTRY_ID']]))
	$str .= '<td class="blk_font" onclick="relocate(\'/GENEID/' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '\')">' . $GENES[$ENTRY['GN_ENTRY_ID']]['SYMBOL'] . '</td><td  class="blk_font" onclick="relocate(\'/GENEID/' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '\')">' . $GENES[$ENTRY['GN_ENTRY_ID']]['GENE_ID'] . '</a></td>';
	else $str.='<td>N/A</td><td>N/A</td>';
	$str .= '<td>' . $ENTRY['CLINICAL_PHASE'] . '</td>
	<td>' . $ENTRY['START_DATE'] . '</td>
	<td>' . $ENTRY['CLINICAL_STATUS'] . '</td>
</tr>';
}

changeValue("disease_ongoing_trial", "TRIALS", $str);

if (count($MODULE_DATA['CLINICAL_STAT']['ONGOING'])==2000)
{
	changeValue("disease_ongoing_trial","ALERT","<div class='alert alert-info'>Maximum number of trials reached - capped to 2000</div>");
}


?>