<?php

/**
 SCRIPT NAME: pmj_transcript
 PURPOSE:     Prepare script for transcript process
*/

/// Job name - Do not change
$JOB_NAME='pmj_transcript';


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

addLog("Setting up");
	
	/// Get Parent info	
	$CK_INFO=$GLB_TREE[getJobIDByName('db_transcriptome')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Check if SCRIPT_DIR is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."005",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."006",'SCRIPT_DIR not found ');

	/// Getting the path of the script to run
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."007",'Setenv file not found ');

	/// Getting the path of the script to run
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_transcript.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."008",$RUNSCRIPT.' file not found');
	
	/// Create the full path:
	$RUNSCRIPT_PATH='$TG_DIR/'.$GLB_VAR['SCRIPT_DIR'].'/'.$JOB_INFO['DIR'].'/process_transcript.php';

	/// Check if JOBARRAY is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."009",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."010",'JOBARRAY file NOT FOUND '.$JOBARRAY);

	
	addLog("Working directory:".$W_DIR);
	
/// get the count of transcripts for each taxon

	$res=runQuery("SELECT count(*) co, assembly_accession,assembly_version
		FROM genome_assembly ga,  chr_seq cs, transcript t 
		WHERE ga.genome_assembly_id=cs.genome_assembly_id 
		AND cs.chr_seq_id=t.chr_seq_id
		GROUP BY assembly_accession, assembly_version");

	if ($res===false)																	failProcess($JOB_ID."011",'Unable to get count of transcripts per taxon');
	
	/// Getting the total count of transcripts:
	$TOT=0;
	$ASSEMBLY_STAT=array();
	foreach ($res as $line)
	{
		$TOT+=$line['co'];
		$ASSEMBLY_STAT[$line['assembly_accession'].'.'.$line['assembly_version']]=array($line['co'],0,$line['co']);
	}

	/// Set up the path
	$W_DIR_PATH='$TG_DIR/'.$GLB_VAR['PROCESS_DIR'].'/'.$CK_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];
	/// Create directory
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))												failProcess($JOB_ID."012",'Unable to create jobs directory');
	if (!is_dir("JSON") && !mkdir("JSON"))														failProcess($JOB_ID."013",'Unable to create jobs directory');
	if (!is_dir("LOG") && !mkdir("LOG"))														failProcess($JOB_ID."014",'Unable to create LOG directory');

	/// all.sh will get all the job paths to use for batch submission
	$fpA=fopen("SCRIPTS/all.sh",'w'); if(!$fpA)													failProcess($JOB_ID."015",'Unable to open all.sh');

	/// Total number of jobs:
	$N_JOB=100;

	/// Therefore total number of transcripts to process per job:
	$N_PER_JOB=ceil($TOT/$N_JOB);

	/// Create the jobs
	for($I=0;$I<$N_JOB;++$I)
	{

		$STR='';
		$JOB_CO=$N_PER_JOB;
		/// This will find the next range of transcript to process, based on the number of tanscripts per taxon
		foreach ($ASSEMBLY_STAT as $ASSEMBLY_ACC=>&$CO)
		{
			if ($CO[0]==0)continue;
			$STR.=$ASSEMBLY_ACC.'-';
			if ($CO[0]>$JOB_CO){
				$STR.=$CO[1]."-".($CO[1]+$JOB_CO).'__';
				$CO[0]-=$JOB_CO;
				$CO[1]+=$JOB_CO+1;
				break;
				}
			else {$STR.=$CO[1].'-'.$CO[2].'__';$CO[0]=0;$JOB_CO-=$CO[2]-$CO[1];}
		}
		/// Create the script
		$JOB_NAME="SCRIPTS/job_".$I.".sh";
		$fp=fopen($JOB_NAME,"w");if(!$fpA)												failProcess($JOB_ID."016",'Unable to open jobs/job_'.$I.'.sh');
		/// Push the script to all.sh
		fputs($fpA," sh ".$W_DIR_PATH.'/'.$JOB_NAME."\n");
		
		/// Create the script:
		fputs($fp,'#!/bin/sh'."\n");
		fputs($fp,"source ".$SETENV."\n");
		fputs($fp,'cd '.$W_DIR_PATH."\n");
		fputs($fp,'biorels_php '.$RUNSCRIPT_PATH.' '.$I." ".$STR.' &> LOG/LOG_'.$I."\n");
		fputs($fp,'echo $? > SCRIPTS/status_'.$I."\n");
		fclose($fp);
	}
	fclose($fpA);

successProcess();

?>


