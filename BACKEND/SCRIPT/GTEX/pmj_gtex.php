<?php

/**
 SCRIPT NAME: pmj_gtex
 PURPOSE:     Prepare the jobs for gene expression
 				- Get the list of genes and transcripts
 				- Create the jobs
 
*/

/// Job name - Do not change
$JOB_NAME='pmj_gtex';


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



addLog("Check directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_gtex_rel')];

	/// Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR))																failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if ( !chdir($W_DIR))																failProcess($JOB_ID."002",'Unable to access '.$W_DIR);
	
	/// Find setenv file:
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."003",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."004",'SCRIPT_DIR not found ');
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."005",'Setenv file not found ');


	/// Find the run script:
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_gtex.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."006",$RUNSCRIPT.' file not found');


	/// Check if JOBARRAY is set in CONFIG_GLOBAL
	/// This is for running multiple job in parallel in SGE
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."007",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."008",'JOBARRAY file NOT FOUND '.$JOBARRAY);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Generate scripts");
	/// Get the list of genes
	$res=runQuery("SELECT DISTINCT gene_seq_id FROM rna_Gene");if ($res===false)		failProcess($JOB_ID."009",'Unable to get rna gene');
	$LIST_GENES=array();
	foreach ($res as $line)$LIST_GENES[]="GENE\t".$line['gene_seq_id'];

	/// Get the list of transcripts
	$res=runQuery("SELECT DISTINCT TRANSCRIPT_ID FROM RNA_TRANSCRIPT");if ($res===false) failProcess($JOB_ID."010",'Unable to get rna transcript');
	foreach ($res as $line)$LIST_GENES[]="TRANSCRIPT\t".$line['transcript_id'];
	
	
	
addLog("Save list genes");
	$fp=fopen('LIST_GENE_SPLIT','w');if (!$fp)											failProcess($JOB_ID."011",'Unable to get rna gene');
	fputs($fp,implode($LIST_GENES,"\n")."\n");
	fclose($fp);

	
	addLog("Create jobs");
	/// Create a master script
	$fpA=fopen("all.sh",'w'); if(!$fpA)													failProcess($JOB_ID."012",'Unable to open all.sh');
	if (!is_dir("jobs") && !mkdir("jobs"))												failProcess($JOB_ID."013",'Unable to create jobs directory');
	$N_JOB=200;
	/// Create the jobs
	for($I=1;$I<=$N_JOB;++$I)
	{
		$JOB_NAME="jobs/job_".$I.".sh";
		$fp=fopen($JOB_NAME,"w");if(!$fpA)												failProcess($JOB_ID."014",'Unable to open jobs/job_'.$I.'.sh');
		
		/// Add the script path to master script
		fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");

		/// Add the script to the job
		fputs($fp,'#!/bin/sh'."\n");
		/// Add the environment script
		fputs($fp,"source ".$SETENV."\n");

		/// Add the command to run the script
		fputs($fp,'cd '.$W_DIR."\n");
		fputs($fp,'biorels_php '.$RUNSCRIPT.' '.$I.' &> LOG_'.$I."\n");
		/// Retrieve the job status and save it to a file for post-processing
		fputs($fp,'echo $? > status_'.$I."\n");
		fclose($fp);
	}
	fclose($fpA);


	successProcess();

?>

