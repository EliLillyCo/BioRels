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
	prepareDomains();
	
	

	
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."006",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."007",'SCRIPT_DIR not found ');
	
	/// Check for the setenv file
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."008",'Setenv file not found ');

	/// Check for the run script
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_seq_sim.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."009",$RUNSCRIPT.' file not found');

	/// Check for the job array
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."010",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."011",'JOBARRAY file NOT FOUND '.$JOBARRAY);

	
	
	///Creating directories:
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))										failProcess($JOB_ID."012",'Unable to create SCRIPTS directory');
	if (!is_dir("JSON") && !mkdir("JSON"))												failProcess($JOB_ID."013",'Unable to create jobs directory');

	/// Create the job array
	$fpA=fopen("SCRIPTS/all.sh",'w'); if(!$fpA)											failProcess($JOB_ID."014",'Unable to open all.sh');
	
	/// If you change the number of jobs, you need to change the number of jobs in the process_seq_sim.php file
	$N_JOB=50;
	$JOB_TYPE=array('DOM','SEQ');
	
	for($I=0;$I<$N_JOB;++$I)
	{
		foreach ($JOB_TYPE as $TYPE)
		{
			$JOB_NAME="SCRIPTS/job_".$I."_".$TYPE.".sh";
			/// Create the job file
			$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."015",'Unable to open jobs/job_'.$I.'.sh');
			/// Add the job to the all.sh file
			fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");

			/// Write the job file
			fputs($fp,'#!/bin/sh'."\n");
			fputs($fp,"source ".$SETENV."\n");/// Load the environment
			fputs($fp,'cd '.$W_DIR."\n");/// Go to the working directory
			fputs($fp,'biorels_exe php '.$RUNSCRIPT.' '.$I.' '.$TYPE.' &> SCRIPTS/'.$TYPE.'_LOG_'.$I."\n");/// Run the script
			fputs($fp,'echo $? > SCRIPTS/status_'.$TYPE.'_'.$I."\n");/// Write the status
			fclose($fp);
		}
		
	}
	fclose($fpA);




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



function prepareDomains()
{
	global $MAKEBLAST;


	//Status = 9 means set for deletion
	$res=runQuery("SELECT prot_dom_id, domain_type 
				from prot_dom where status !=9 AND (pos_end-pos_start+1)>=30");
				if ($res===false)																			failProcess($JOB_ID."B01",'Unable to get protein domains');
	
	/// Store the domain id and the domain type in an array
	$UN_SEQ_LIST=array();
	foreach ($res as $line)
	{
		$UN_SEQ_LIST[$line['prot_dom_id']]=$line['domain_type'];
	}
	
	/// Split the list into chunks of 70
	$CHUNKS=array_chunk(array_keys($UN_SEQ_LIST),70);
	
	$fp=fopen('DOM.fasta','w');if (!$fp)																	failProcess($JOB_ID."B02",'Unable to open DOM.fasta');
	$fpP=fopen('DOM_pointer.csv','w');if (!$fpP)															failProcess($JOB_ID."B03",'Unable to open DOM_pointer.csv');
	
	
	
	foreach ($CHUNKS as $N=>$CHUNK)
	{
		$time=microtime_float();
		echo $N."\t".count($CHUNKS)."\n";
		/// Getting the sequences from the database for the current chunk
		$res=runQuery("SELECT prot_dom_id,udp.position,letter  
						FROM prot_dom_seq udp, prot_seq_pos usp   
						WHERE udp.prot_seq_pos_id = usp.prot_seq_pos_id 
						AND  prot_dom_id IN (".implode(',',$CHUNK).')');
						if ($res===false)																failProcess($JOB_ID."B04",'Unable to get protein domain');
		
		/// Storing the sequences in an array
		/// The array is a 2D array where the first key is the sequence id and the second key is the position
		/// The value is the letter
		$SEQS=array();
		foreach ($res as $line)
		{
			$SEQS[$line['prot_dom_id']][$line['position']]=$line['letter'];
		}
		
		foreach ($SEQS as $SEQ_ID=>&$LIST)
		{
			///Since the position are not order by default, we need to sort them by the key, i.e. the position
			ksort($LIST);
			/// Get the file position
			$FPOS=ftell($fp);
			/// We don't consider domain that are less than 30 amino acids
			if (count($LIST)<30)continue;

			/// Write the whole sequence in chunks of 100 characters with the header:
			$STR='>'.$SEQ_ID."-".$UN_SEQ_LIST[$SEQ_ID]."\n".implode("\n",str_split(implode('',$LIST),100))."\n";
			fputs($fp,$STR);
			/// Write the file position and the length of the sequence to the pointer file
			fputs($fpP,$SEQ_ID."\t".$FPOS."\t".strlen($STR)."\t".$UN_SEQ_LIST[$SEQ_ID]."\n");
		}
		$SEQS=array();
		unset($SEQS);

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
		
	}
	fclose($fp);
	fclose($fpP);

	addLog("Create Blast Database");
	exec($MAKEBLAST.' -in DOM.fasta -parse_seqids -dbtype prot',$res,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."B05",'Unable to create DOM blast db'); 
	

}
?>

