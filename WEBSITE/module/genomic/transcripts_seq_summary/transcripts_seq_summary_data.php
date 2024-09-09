<?php
ini_set("memory_limit", '2000M');
if (!defined("BIORELS")) header("Location:/");
$TMP_PARAMS = ($USER_INPUT['PARAMS']);

$GN_ENTRY_ID=null;
if (isset($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']))
$GN_ENTRY_ID = $USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
$start_memory = memory_get_usage();


$SEQ_DELIM="\t";
$SEQ_TYPE='2';
$MAX_MISMATCH=2;
$CODING_ONLY=false;
$TRANSCRIPT_INPUT_INFO=array();
$match_seq_input=array();
$PARENT_DIV = '.w3-main';
$SEL_TRANSCRIPTS = array();
$TRANSCRIPT_IDS = array();
$TRANSCRIPT_INPUT='';
$W_MATCH_TBL=true;
$N_P= count($USER_INPUT['PARAMS']);
for ($I = 0; $I < count($USER_INPUT['PARAMS']); ++$I) {
	$V = &$USER_INPUT['PARAMS'][$I];
	if ($V == 'PARENT_DIV') {
		$I++;
		$PARENT_DIV = $USER_INPUT['PARAMS'][$I];
		continue;
	}
	else if ($USER_INPUT['PARAMS'][$I]=='CODING_ONLY')$CODING_ONLY=true;
	else if ($USER_INPUT['PARAMS'][$I]=="SEQ_DELIM")
	{
	  if ($I+1>=$N_P)throw new Exception("Sequence delimiter not provided",ERR_TGT_SYS);
	  $V=$USER_INPUT['PARAMS'][$I+1];
	  if ($V=='tab')$SEQ_DELIM="\t";
	  else if ($V=='comma')$SEQ_DELIM=',';
	  else if ($V=='space')$SEQ_DELIM=' ';
	  else if ($V=='semicolon')$SEQ_DELIM=';';
	  else throw new Exception("unrecognized delimiter");
	  $I+=1;
	}
	if ($USER_INPUT['PARAMS'][$I]=="TRANSCRIPT_MATCH")
	{
	  if ($I+1>=$N_P)throw new Exception("Matching sequence is not provided",ERR_TGT_SYS);
	  $TRANSCRIPT_INPUT=$USER_INPUT['PARAMS'][$I+1];
	  
	  $I+=1;
	}
	if ($USER_INPUT['PARAMS'][$I]=="GENE_ID")
	{
	  if ($I+1>=$N_P)throw new Exception("Matching sequence is not provided",ERR_TGT_SYS);
	  $GENE_ID=$USER_INPUT['PARAMS'][$I+1];
	  $USER_INPUT['PORTAL']['DATA']=gene_portal_geneID($GENE_ID);
	  $GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
	 
	  $I+=1;
	}
	if ($USER_INPUT['PARAMS'][$I]=="MAX_MISMATCH")
	{
	  if ($I+1>=$N_P)throw new Exception("Maximum number of mismatch is not provided",ERR_TGT_SYS);
	  $V=$USER_INPUT['PARAMS'][$I+1];
	  if (!is_numeric($V))throw new Exception("Maximum number of mismatch must be numeric",ERR_TGT_SYS);
	  if ($V<0||$V>5)throw new Exception("Maximum number of mismatch must be between 0 and 5",ERR_TGT_SYS);
	  $MAX_MISMATCH=$V;
	  $I+=1;
	}
	if ($USER_INPUT['PARAMS'][$I]=="SEQ_TYPE")
	{
	  if ($I+1>=$N_P)throw new Exception("Match sequence input format not provided",ERR_TGT_SYS);
	  $V=$USER_INPUT['PARAMS'][$I+1];
	  if (!is_numeric($V))throw new Exception("Match sequence input format must be numeric",ERR_TGT_SYS);
	  if ($V<0||$V>4)throw new Exception("Match sequence input must be between 0 and 4",ERR_TGT_SYS);
	  $SEQ_TYPE=$V;
	  $I+=1;
	}
	if ($USER_INPUT['PARAMS'][$I]=='SEQ_MATCH')
	{
	  if ($I+1>=$N_P)throw new Exception("Matching sequence list is not provided",ERR_TGT_SYS);
	  $match_seq_input=explode("\n",str_replace("\r","",$USER_INPUT['PARAMS'][$I+1]));
	 // foreach ($list as $sq)$MODULE_DATA['MATCHING_SEQ'][$sq]=array();
	  $I+=1;
	}
	if ($USER_INPUT['PARAMS'][$I]=='NO_TABLE')$W_MATCH_TBL=false;
	if ($USER_INPUT['PARAMS'][$I]=='TRANSCRIPTS')
	{
	  if ($I+1>=$N_P)throw new Exception("list of transcripts is not provided",ERR_TGT_SYS);
	  $list=explode(";",$USER_INPUT['PARAMS'][$I+1]);
	  foreach ($list as $V)
	  {
		if ($V=='')continue;
		if (!checkRegex($V, 'REGEX:TRANSCRIPT')) throw new Exception("Unrecognized Transcript Sequence for " . $V, ERR_TGT_USR);
		$SEL_TRANSCRIPTS[$V] = true; 
	  }
	  $I+=1;
	}
	
}


if ($GN_ENTRY_ID==null) throw new Exception("Gene ID not provided",ERR_TGT_USR);
$MODULE_DATA['BOUNDS'] = array();
$MODULE_DATA = getListTranscripts($GN_ENTRY_ID,array(),$CODING_ONLY);

if ($TRANSCRIPT_INPUT!='')
{
foreach ($MODULE_DATA['TRANSCRIPTS'] as &$TR_INFO)
	  {
		if ($TR_INFO['TRANSCRIPT_VERSION']!='')
		{
			if ($TR_INFO['TRANSCRIPT_NAME'].'.'.$TR_INFO['TRANSCRIPT_VERSION']==$TRANSCRIPT_INPUT)
			{
				$TRANSCRIPT_INPUT_INFO=$TR_INFO;
			}
		}
		else if ($TR_INFO['TRANSCRIPT_NAME']==$TRANSCRIPT_INPUT)
		{
			$TRANSCRIPT_INPUT_INFO=$TR_INFO;
		}
	  }

	if ($TRANSCRIPT_INPUT_INFO==array()) throw new Exception("No transcripts found ", ERR_TGT_USR);
	}

$ASSEMBLIES = array();
foreach ($MODULE_DATA['TRANSCRIPTS'] as $K => $TR) {
	if ($SEL_TRANSCRIPTS==array() || isset($SEL_TRANSCRIPTS[$TR['TRANSCRIPT_NAME']]) || isset($SEL_TRANSCRIPTS[$TR['TRANSCRIPT_NAME'] . '.' . $TR['TRANSCRIPT_VERSION']])) {

		$GS = &$MODULE_DATA['GENE_SEQ'][$TR['GENE_SEQ_ID']];
		$ASSEMBLIES[$GS['ASSEMBLY_NAME'] . '|' . $GS['ASSEMBLY_UNIT']]['TR'][$TR['TRANSCRIPT_ID']] = $K;
		$ASSEMBLIES[$GS['ASSEMBLY_NAME'] . '|' . $GS['ASSEMBLY_UNIT']]['GS'][$TR['GENE_SEQ_ID']] = true;
	} else unset($MODULE_DATA['TRANSCRIPTS'][$K]);
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

	$TMP = getTranscriptBoundaries(array_keys($ASSEMBLY_INFO['TR']));
	//print_r($TMP);

	foreach ($ASSEMBLY_INFO['TR'] as $TR_ID => $K) {

		if (isset($TMP[$TR_ID]))
			$ASSEMBLY_INFO['TRANSCRIPTS'][$TR_ID]['BOUNDARIES'] = $TMP[$TR_ID];
	}

	/// Start by searching the min
	$MIN_MIN = 100000000000000000;
	$MIN_MAX = -1;
	$SUM = 0;
	foreach ($ASSEMBLY_INFO['TRANSCRIPTS'] as $K => &$INFO) {
		foreach ($INFO['BOUNDARIES'] as &$B) {
			if ($B['MIN_POS'] == '' || $B['MAX_POS'] == '') continue;

			if ($B['MIN_POS'] < $MIN_MIN) {
				$MIN_MIN = $B['MIN_POS'];
				$MIN_MAX = $B['MAX_POS'];
			}
		}
		
	}
	foreach ($ASSEMBLY_INFO['TRANSCRIPTS'] as $K => &$INFO) {
		
		
		
		foreach ($INFO['BOUNDARIES'] as &$B) {
			if ($B['MIN_POS'] == '' || $B['MAX_POS'] == '') continue;
			if ($B['MIN_POS'] <= $MIN_MAX + 10 && $B['MAX_POS'] > $MIN_MAX) {
				$MIN_MAX = $B['MAX_POS'];
			}
		}
		
	}
	$ASSEMBLY_INFO['BOUNDS'][] = array($MIN_MIN, $MIN_MAX, $MIN_MAX - $MIN_MIN, 0);
	$SUM += $MIN_MAX - $MIN_MIN;


	$PREV_MAX = $MIN_MAX;
	$N = 0;
	do {
		$MIN_MIN = 100000000000000000;
		$MIN_MAX = -1;
		foreach ($ASSEMBLY_INFO['TRANSCRIPTS'] as $K => &$INFO) {
			//echo "A".$K.' '.$MIN_MIN.' '.$MIN_MAX;
			foreach ($INFO['BOUNDARIES'] as &$B)
			{
				if ($B['MIN_POS'] < $MIN_MIN && $B['MIN_POS'] > $PREV_MAX) {
					$MIN_MIN = $B['MIN_POS'];
					$MIN_MAX = $B['MAX_POS'];
				}
				if ($B['MIN_POS'] < $PREV_MAX && $B['MAX_POS'] > $PREV_MAX) {
					$MIN_MIN = $B['MIN_POS'];
					$MIN_MAX = $B['MAX_POS'];
				}
			}
				//echo '=> '.$MIN_MIN.' '.$MIN_MAX."\n";
		}
		if ($MIN_MIN == 100000000000000000) break;
		foreach ($ASSEMBLY_INFO['TRANSCRIPTS'] as $K => &$INFO) {
			//echo "B".$K.' '.$MIN_MIN.' '.$MIN_MAX;
			foreach ($INFO['BOUNDARIES'] as &$B)
			//echo "\t".$B['MIN_POS'].'/'.$B['MAX_POS'];
				if ($B['MIN_POS'] <= $MIN_MAX + 10 && $B['MAX_POS'] > $MIN_MAX) {
					$MIN_MAX = $B['MAX_POS'];
				}
				//echo '=> '.$MIN_MIN.' '.$MIN_MAX."\n";
		}
		$ASSEMBLY_INFO['BOUNDS'][] = array($MIN_MIN, $MIN_MAX, $MIN_MAX - $MIN_MIN, 0);
		
		$SUM += $MIN_MAX - $MIN_MIN;
		//echo "SUM:\t".$SUM."\n";
		$PREV_MAX = $MIN_MAX;
		++$N;
		//echo "N:".$N."\n";
		if ($N > 100) break;
	} while (1);

	$ASSEMBLY_INFO['LENGTH'] = $SUM;
	$ASSEMBLY_INFO['SUMS'] = array($SUM, $ASSEMBLY_INFO['RANGE'][1] - $ASSEMBLY_INFO['RANGE'][0]);

	$GAP_REMOVE = 0;
	//$GAP_RATIO=0.2;
	
	$GAP_RATIO = $SUM * 0.1 / (($ASSEMBLY_INFO['RANGE'][1] - $ASSEMBLY_INFO['RANGE'][0] - $SUM)==0?1:($ASSEMBLY_INFO['RANGE'][1] - $ASSEMBLY_INFO['RANGE'][0] - $SUM));

	if ($ASSEMBLY_INFO['BOUNDS'][0][0] - $ASSEMBLY_INFO['RANGE'][0] > $GAP_RATIO * $SUM) {
		$ASSEMBLY_INFO['LENGTH'] += $GAP_RATIO * $SUM;
		$GAP_REMOVE = $ASSEMBLY_INFO['BOUNDS'][0][0] - $ASSEMBLY_INFO['RANGE'][0] - $GAP_RATIO * $SUM;
		for ($J = 0; $J < count($ASSEMBLY_INFO['BOUNDS']); ++$J) {
			$ASSEMBLY_INFO['BOUNDS'][$J][3] += $GAP_REMOVE;
		}
	}


	for ($I = 1; $I < count($ASSEMBLY_INFO['BOUNDS']); ++$I) {
		$GAP = $ASSEMBLY_INFO['BOUNDS'][$I][0] - $ASSEMBLY_INFO['BOUNDS'][$I - 1][1];

		if ($GAP > $GAP_RATIO * $SUM) {
			$ASSEMBLY_INFO['LENGTH'] += $GAP_RATIO * $SUM;
			$GAP_REMOVE = $GAP - $GAP_RATIO * $SUM;
			for ($J = $I; $J < count($ASSEMBLY_INFO['BOUNDS']); ++$J) {
				$ASSEMBLY_INFO['BOUNDS'][$J][3] += $GAP_REMOVE;
			}
		} else $ASSEMBLY_INFO['LENGTH'] += $GAP;
		$ASSEMBLY_INFO['GAPS'][] = array($GAP, $GAP_REMOVE);
	}

	foreach ($ASSEMBLY_INFO['TRANSCRIPTS'] as $K => &$INFO) {
		foreach ($INFO['BOUNDARIES'] as &$B) {
			foreach ($ASSEMBLY_INFO['BOUNDS'] as &$BOUND) {
				//print_r($BOUND);
				//echo $B['MIN_POS'].' '.$BOUND[0].' '.$BOUND[1]."\n";
				if ($B['MIN_POS'] >= $BOUND[0] && $B['MIN_POS'] < $BOUND[1]) {
					//echo "IN\n";
					$B['MIN_SHIFT'] = $B['MIN_POS'] - $BOUND[3] - $ASSEMBLY_INFO['RANGE'][0];
					$B['MAX_SHIFT'] = $B['MAX_POS'] - $BOUND[3] - $ASSEMBLY_INFO['RANGE'][0];
					break;
				}
			}
			//echo "\n#####\n";
		}
	}

	foreach ($ASSEMBLY_INFO['TRANSCRIPTS'] as $K => &$INFO) {
		foreach ($INFO['BOUNDARIES'] as &$B) {
			if ($IS_POSITIVE) {
				$B['LEFT'] = round(($B['MIN_SHIFT'] / $ASSEMBLY_INFO['LENGTH']) * 100, 3);
				$B['WIDTH'] = round((($B['MAX_SHIFT'] - $B['MIN_SHIFT']) / $ASSEMBLY_INFO['LENGTH']) * 100, 3);
				//print_r($B);
				if ($B['LEFT']+$B['WIDTH']>102) {echo "HIGHER A";exit;}
			} else {
				$B['LEFT'] = round((($ASSEMBLY_INFO['LENGTH'] - $B['MAX_SHIFT']) / $ASSEMBLY_INFO['LENGTH']) * 100, 3);
				$B['WIDTH'] = round((($B['MAX_SHIFT'] - $B['MIN_SHIFT']) / $ASSEMBLY_INFO['LENGTH']) * 100, 3);
				if ($B['LEFT']+$B['WIDTH']>102) {echo "HIGHER B";exit;}
			}
		}
	}
	
	foreach ($ASSEMBLY_INFO['TR'] as $TR_ID => $K) {
		foreach ($MODULE_DATA['TRANSCRIPTS'][$K] as $N => $P)
			$ASSEMBLY_INFO['TRANSCRIPTS'][$TR_ID][$N] = $P;
	}
}
$MODULE_DATA = null;
$MODULE_DATA['ASSEMBLY'] = $ASSEMBLIES;
$MODULE_DATA['COUNT'] = $COUNT;
$USER_INPUT['PARAMS'] = $TMP_PARAMS;
$MODULE_DATA['MATCHING_TRANSCRIPT']=$TRANSCRIPT_INPUT;


if ($match_seq_input!=array())
{
	
	$MODULE_DATA['MATCHING_TYPE']=$SEQ_TYPE;
	$NM=0;
	$MODULE_DATA['POTENCY_RANGE']=array('MAX'=>-100000,'MIN'=>100000);

  
	foreach ($match_seq_input as $line)
	{
	  if ($line=='')continue;
	  $line=str_replace("\r","",$line);

	
	  $tab=explode($SEQ_DELIM,$line);
	  
	  
	  
	  ++$NM;
	  switch ($SEQ_TYPE)
	  {

		case 1:
		  if ($tab[0]=='')break;
		  $MODULE_DATA['MATCHING_SEQ'][$NM]=array('INPUT'=>array('SEQ'=>$tab[0],'NAME'=>''),'RES'=>array());break;
		case 2: 
			if (!is_numeric($tab[0])&&$NM==0)break;
		  if ($tab[0]=='')break;
		  if ($tab[1]=='')break;
		  $MODULE_DATA['MATCHING_SEQ'][$NM]=array('INPUT'=>array('SEQ'=>$tab[0],'NAME'=>$tab[1]),'RES'=>array());break;
		case 3: 
			if (!is_numeric($tab[0])&&$NM==0)break;
		  if ($tab[0]=='')break;
		  if ($tab[1]=='')break;
		  if ($tab[2]=='')break;
		  $MODULE_DATA['MATCHING_SEQ'][$NM]=array('INPUT'=>array('SEQ'=>$tab[0],'NAME'=>$tab[1],'POTENCY'=>$tab[2]),'RES'=>array());

		$MODULE_DATA['POTENCY_RANGE']['MIN']=min($MODULE_DATA['POTENCY_RANGE']['MIN'],$tab[2]);
		$MODULE_DATA['POTENCY_RANGE']['MAX']=max($MODULE_DATA['POTENCY_RANGE']['MAX'],$tab[2]);
		break;
		case 4: 
			if (!is_numeric($tab[0])&&$NM==0)break;
		  if ($tab[0]=='')break;
		  if ($tab[1]=='')break;
		  $MODULE_DATA['MATCHING_SEQ'][$NM]=array('INPUT'=>array('SEQ'=>'','NAME'=>$tab[0],'POTENCY'=>$tab[1]),'RES'=>array());
		  
		$MODULE_DATA['POTENCY_RANGE']['MIN']=min($MODULE_DATA['POTENCY_RANGE']['MIN'],$tab[1]);
		$MODULE_DATA['POTENCY_RANGE']['MAX']=max($MODULE_DATA['POTENCY_RANGE']['MAX'],$tab[1]);
		break;
	  
	  }
	 
	}
	

	


	$TMP=getTranscriptSequence($TRANSCRIPT_INPUT_INFO['TRANSCRIPT_NAME'].(($TRANSCRIPT_INPUT_INFO['TRANSCRIPT_VERSION']!='')?'.'.$TRANSCRIPT_INPUT_INFO['TRANSCRIPT_VERSION']:''));
	foreach ($MODULE_DATA['ASSEMBLY'] as &$AS_INFO)
	if (isset($AS_INFO['TRANSCRIPTS'][$TRANSCRIPT_INPUT_INFO['TRANSCRIPT_ID']]))

	$MODULE_DATA['MATCHING_QUERY_INFO']=$AS_INFO['TRANSCRIPTS'][$TRANSCRIPT_INPUT_INFO['TRANSCRIPT_ID']]['BOUNDARIES'];
	
	$SEQ_MATCH='';
	foreach ($TMP['SEQUENCE'] as &$F)
	
	{
	  $SEQ_MATCH.=$F['NUCL'];
	}
	
	
	$TMP=array();
	
	$LEN_QUERY=strlen($SEQ_MATCH);
	
	foreach ($MODULE_DATA['MATCHING_SEQ'] as &$SQ_RES)
  {
	
    $TMP=array();
    $LEN=strlen($SQ_RES['INPUT']['SEQ']);
    
    $POSSIBLE_MATCH=array();
    for ($I=0;$I<$LEN_QUERY-$LEN+1;++$I)
    {
      $QUERY_SQ=substr($SEQ_MATCH,$I,$LEN);
      $POSSIBLE_MATCH[$QUERY_SQ]=$I;
    }
    
    genAltMatch(str_replace("U","T",$SQ_RES['INPUT']['SEQ']),$MAX_MISMATCH,$POSSIBLE_MATCH,$SQ_RES,true,1);
   
    $TMP=array();
    genAltMatch(getReverse(str_replace("U","T",$SQ_RES['INPUT']['SEQ'])),$MAX_MISMATCH,$POSSIBLE_MATCH,$SQ_RES,false,1);
    
  }
  foreach ($MODULE_DATA['MATCHING_SEQ'] as &$MATCH_ENTRY)
  {
	foreach ($MATCH_ENTRY['RES'] as &$RES)
	{
		foreach ($MODULE_DATA['MATCHING_QUERY_INFO'] as &$RANGE_INFO)
		{
			if ($RES[0]<$RANGE_INFO['MIN_TR_POS']||$RES[0]>$RANGE_INFO['MAX_TR_POS'])continue;
			$RES['LEFT_T']=round($RANGE_INFO['LEFT']+($RES[0]-$RANGE_INFO['MIN_TR_POS'])*$RANGE_INFO['WIDTH']/($RANGE_INFO['MAX_TR_POS']-$RANGE_INFO['MIN_TR_POS']+1),3);
			//foreach ($RANGE_INFO as $K=>$V)$RES[$K]=$V;
		}
	}
  }
  //print_r($MODULE_DATA['MATCHING_SEQ']);exit;

 // print_r($MODULE_DATA['MATCHING_SEQ']);exit;
}

?>