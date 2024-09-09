<?php
ini_set("memory_limit", '2000M');
if (!defined("BIORELS")) header("Location:/");
$TMP_PARAMS = ($USER_INPUT['PARAMS']);

$GN_ENTRY_ID=null;
if (isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']))
$GN_ENTRY_ID = $USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
$start_memory = memory_get_usage();


$N_P= count($USER_INPUT['PARAMS']);
for ($I = 0; $I < count($USER_INPUT['PARAMS']); ++$I) {
	$V = &$USER_INPUT['PARAMS'][$I];
	
	
	if ($USER_INPUT['PARAMS'][$I]=="GENE_ID")
	{
	  if ($I+1>=$N_P)throw new Exception("Matching sequence is not provided",ERR_TGT_SYS);
	  $GENE_ID=$USER_INPUT['PARAMS'][$I+1];
	  $USER_INPUT['PORTAL']['DATA']=gene_portal_geneID($GENE_ID);
	  $GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
	 
	  $I+=1;
	}
	
	
}


if ($GN_ENTRY_ID==null) throw new Exception("Gene ID not provided",ERR_TGT_USR);
$MODULE_DATA['BOUNDS'] = array();
$MODULE_DATA = getListTranscripts($GN_ENTRY_ID,array(),false);


$ASSEMBLIES = array();
foreach ($MODULE_DATA['TRANSCRIPTS'] as $K => $TR) {
	

		$GS = &$MODULE_DATA['GENE_SEQ'][$TR['GENE_SEQ_ID']];
    if ($GS['ASSEMBLY_UNIT']!='Primary Assembly')continue;
		$ASSEMBLIES[$GS['ASSEMBLY_NAME'] . '|' . $GS['ASSEMBLY_UNIT'].'|'.$GS['MAP_LOCATION']]['TR'][$TR['TRANSCRIPT_ID']] = $TR;
		$ASSEMBLIES[$GS['ASSEMBLY_NAME'] . '|' . $GS['ASSEMBLY_UNIT'].'|'.$GS['MAP_LOCATION']]['GS'][$TR['GENE_SEQ_ID']]=$GS;
    $ASSEMBLIES[$GS['ASSEMBLY_NAME'] . '|' . $GS['ASSEMBLY_UNIT'].'|'.$GS['MAP_LOCATION']]['CHR_SEQ_ID']=$GS['CHR_SEQ_ID'];
    $ASSEMBLIES[$GS['ASSEMBLY_NAME'] . '|' . $GS['ASSEMBLY_UNIT'].'|'.$GS['MAP_LOCATION']]['STRAND']=$GS['STRAND'];
    
	
}


$COUNT = count($ASSEMBLIES) + count($MODULE_DATA['TRANSCRIPTS']);
// echo '<pre>';
// print_R($MODULE_DATA['TRANSCRIPTS']);

foreach ($ASSEMBLIES as $ASSEMBLY_NAME => &$ASSEMBLY_INFO) {
	$MIN_POS = 10000000000000;
	$MAX_POS = 0;
	$CHR_SEQ_ID = -1;
	$IS_POSITIVE = true;
	foreach ($ASSEMBLY_INFO['GS'] as $GENE_SEQ_ID => $DUMMY) {
		$GS = &$MODULE_DATA['GENE_SEQ'][$GENE_SEQ_ID];
		if ($GS['STRAND'] == "-") $IS_POSITIVE = false;
		if ($GS['START_POS'] < $MIN_POS) $MIN_POS = $GS['START_POS'];
		if ($GS['END_POS'] > $MAX_POS) $MAX_POS = $GS['END_POS'];
	}
	$ASSEMBLY_INFO['RANGE'] = array($MIN_POS, $MAX_POS);

	$TMP = getTranscriptBoundaries(array_keys($ASSEMBLY_INFO['TR']), $ASSEMBLY_INFO['STRAND']);
	//print_r($TMP);

	foreach ($ASSEMBLY_INFO['TR'] as $TR_ID => $K) {

		if (isset($TMP[$TR_ID]))
			$ASSEMBLY_INFO['TR'][$TR_ID]['BOUNDARIES'] = $TMP[$TR_ID];
	}

  echo $ASSEMBLY_INFO['STRAND'];
  $ASSEMBLY_INFO['SEQUENCE']=getPreMRNASequence($ASSEMBLY_INFO['CHR_SEQ_ID'],$ASSEMBLY_INFO['RANGE'][0],$ASSEMBLY_INFO['RANGE'][1], $ASSEMBLY_INFO['STRAND']);
}
$MODULE_DATA = null;
$MODULE_DATA['ASSEMBLY'] = $ASSEMBLIES;
$MODULE_DATA['COUNT'] = $COUNT;
$USER_INPUT['PARAMS'] = $TMP_PARAMS;





?>