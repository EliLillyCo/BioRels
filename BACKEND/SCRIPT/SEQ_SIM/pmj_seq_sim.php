<?php

ini_set('memory_limit','5000M');

/**
 SCRIPT NAME: pmj_seq_sim
 PURPOSE:     Create the blastp database for the jobs
 
*/
$JOB_NAME='pmj_seq_sim';

/// Get root directories
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

/// Get job id
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
/// Get job info
$JOB_INFO=$GLB_TREE[$JOB_ID];

addLog("Create directory");
	/// Setting up directory path:
	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];						if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();			   								if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   											if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

	/// Check for the makeblastdb																	
	$MAKEBLAST=$GLB_VAR['TOOL']['MAKEBLAST']; 			if(!is_executable($MAKEBLAST))						failProcess($JOB_ID."005",'Unable to Find makeblastdb '.$MAKEBLAST);
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();

addLog("Working directory:".$W_DIR);

	prepareSequences();
	
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];
	/// Check for the run script
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_seq_sim.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."006",$RUNSCRIPT.' file not found');

	
	///Creating directories:
	if (!is_dir("JSON") && !mkdir("JSON"))												failProcess($JOB_ID."007",'Unable to create jobs directory');

	
	/// If you change the number of jobs, you need to change the number of jobs in the process_seq_sim.php file
	$N_JOB=50;
	if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')$N_JOB=1;
	
	$N_JOB_ID=0;
	$COMMANDS=array();
	for($I=0;$I<$N_JOB;++$I)
	{
		$COMMANDS[$N_JOB_ID][]='biorels_exe php '.$RUNSCRIPT.' '.$I.' '.$N_JOB;
		++$N_JOB_ID;	
	}
	prepare_batch($COMMANDS,$W_DIR);




successProcess();

function prepareSequences()
{
	global $MAKEBLAST;
	/// Listing all protein sequences that are not set for deletion (status=9)
	$res=runQuery("SELECT prot_seq_id FROM prot_seq WHERE STATUS!=9 ORDER BY prot_seq_id ASC");
	if ($res===false)																						failProcess($JOB_ID."A01",'Unable to get list of protein sequences');
	$UN_SEQ_LIST=array();
	foreach ($res as $line)$UN_SEQ_LIST[]=$line['prot_seq_id'];

	/// Split the list into chunks of 300
	$CHUNKS=array_chunk($UN_SEQ_LIST,300);

	/// Open the file to write the sequences
	$fp=fopen('SEQ.fasta','w');if (!$fp)																	failProcess($JOB_ID."A02",'Unable to open SEQ.fasta');
	$fpP=fopen('SEQ_pointer.csv','w');if (!$fpP)															failProcess($JOB_ID."A03",'Unable to open SEQ_pointer.csv');

	$time_all=0;
	foreach ($CHUNKS as $N=>$CHUNK)
	{
		echo $N."\t".count($CHUNKS)."\t";
		$time=microtime_float();

		/// Getting the sequences from the database for the current chunk
		$res=runQuery("SELECT prot_seq_id, position, letter 
						FROM prot_seq_pos 
						where prot_seq_id  IN (".implode(',',$CHUNK).')');
						if ($res===false)																	failProcess($JOB_ID."A04",'Unable to get protein sequences');
		
		/// Storing the sequences in an array
		/// The array is a 2D array where the first key is the sequence id and the second key is the position
		/// The value is the letter
		$SEQS=array();
		foreach ($res as $line)
		{
			$SEQS[$line['prot_seq_id']][$line['position']]=$line['letter'];
		}
		
		
		foreach ($SEQS as $SEQ_ID=>&$LIST)
		{
			///Since the position are not order by default, we need to sort them by the key, i.e. the position
			ksort($LIST);
			/// Get the file position
			$FPOS=ftell($fp);
			/// Write the whole sequence in chunks of 100 characters with the header:
			$STR='>'.$SEQ_ID."\n".implode("\n",str_split(implode('',$LIST),100))."\n";
			/// Write the sequence to the file
			fputs($fp,$STR);
			/// Write the file position and the length of the sequence to the pointer file
			fputs($fpP,$SEQ_ID."\t".$FPOS."\t".strlen($STR)."\n");
		}
		/// Provide some time expectation:
		$time_run=round(microtime_float()-$time,2);
		echo $time_run;
		$time_all+=$time_run;
		if ($N>0)
		{
			$avg=round($time_all/$N,3);
			echo "\tAVG=".$avg;
			echo "\tREMAINING=".round($avg*(count($CHUNKS)-$N)/60,2).'m';
		}
		echo "\n";

		$SEQS=array();
		unset($SEQS);

		
	}
	fclose($fp);
	fclose($fpP);

	addLog("Create Blast Database");
	exec($MAKEBLAST.' -in SEQ.fasta -parse_seqids -dbtype prot',$res,$return_code);
	if ($return_code!=0)																					failProcess($JOB_ID."A05",'Unable to create blast db'); 

}

?>

