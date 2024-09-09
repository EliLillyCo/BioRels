<?php

/**
 SCRIPT NAME: pmj_pmc
 PURPOSE:  Prepare the list of publications to be processed
*/
ini_set('memory_limit','5000M');

/// Job name - Do not change
$JOB_NAME='pmj_pmc';

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
	// Get job information:
	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];

	// Get parent job information:
	$PARENT_INFO=$GLB_TREE[getJobIDByName('dl_pmc')];

	// Setting up directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];						if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$PARENT_INFO['TIME']['DEV_DIR'];;			   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   											if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Setting up the process control directory to the current release	so that the next job can pick it up
	$PROCESS_CONTROL['DIR']=$PARENT_INFO['TIME']['DEV_DIR'];


	/// Setting up PMC directory where all the archive will be downloaded in
	$PMC_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/PMC';
	if (!is_dir($PMC_DIR) && !mkdir($PMC_DIR)) 						failProcess($JOB_ID."005",'Unable to create PMC directory '.$PMC_DIR);
	

addLog("Working directory:".$W_DIR);
	
		/// To prepare a batch script, we need a few things:
		/// 1. The script directory
		/// 2. The setenv.sh file that will be used to set up the environment
		/// 3. The job array script to allow batch run
		/// 4. The numbe of jobs to run
		/// 5. The last date we processed the data
		/// This will help us generate the following:
		/// 1. The script to run
		/// 2. The list of files to process
		
		
	/// Getting the setenv.sh file
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."006",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."007",'SCRIPT_DIR not found ');
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."008",'Setenv file not found ');
	
	/// The script to run to process the files
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_pmc.php';
	
	/// Get the current release date
	$CURR_RELEASE=getCurrentReleaseDate('PMC',$JOB_ID);
	$CURR_RELEASE_TIME=-1;
	if ($CURR_RELEASE!=-1)$CURR_RELEASE_TIME=strtotime($CURR_RELEASE);

	if (!is_file('oa_file_list.csv'))													failProcess($JOB_ID."009",'Unable to find oa_file_list.csv');
	$fp=fopen('oa_file_list.csv','r');if (!$fp)											failProcess($JOB_ID."010",'Unable to open oa_file_list.csv file ');
	
	
	addLog("CURRENT RELEASE:".$CURR_RELEASE);
	

	$LIST_TO_PROCESS=array();
	$HEAD=fgetcsv($fp);
	while(!feof($fp))
	{
		$fpos=ftell($fp);
		$line=fgetcsv($fp);
		if ($line==false)continue;
		/// Combines the header with the line so the header becomes the key and the line becomes the value
		$tab=array_combine($HEAD,$line);
		if ($tab['PMID']=='')continue;
		/// If the time is less than the current release time, then we skip
		$time=strtotime($tab['Last Updated (YYYY-MM-DD HH:MM:SS)']);
		if ($time<=$CURR_RELEASE_TIME)continue;

		$BATCH[$tab['Accession ID']]=$fpos;
		if (count($BATCH)<1000)continue;
		
		$res=runQuery("SELECT status_code, pmc_id FROM pmc_entry WHERE pmc_id IN ('".implode("','",array_keys($BATCH))."')");
		foreach ($res as $line)
		{
			/// If it's the first time we are processing the file,
			/// then we skip records already processed (in case it's a rerun)
			if ($CURR_RELEASE_TIME==-1 && $line['status_code']==1)
			{
				unset($BATCH[$line['pmc_id']]);
			}
			/// Too many failures, we don't process it again
			if ($line['status_code']>=4)
			{
				unset($BATCH[$line['pmc_id']]);
			}
		}

		foreach ($BATCH as $B=>$F)
		{
			$LIST_TO_PROCESS[$B]=$F;
		}
		$BATCH=array();

		
	}

		
	
	fclose($fp);
	
	echo "Initial list:".count($LIST_TO_PROCESS)."\n";
	
	
	
	$N_C=count($LIST_TO_PROCESS);


	$FPOSITION=array_flip($LIST_TO_PROCESS);

	
	addLog("Number to process:".$N_C);
	

	/// If there is nothing to process, then we are done
	if ($N_C==0) successProcess("VALID");
	
	/// We need to split the list into a number of jobs
	$N_JOB=200;
	if ($N_C<100)$N_JOB=10;
	else if ($N_C<1000)$N_JOB=25;
	else if ($N_C<10000)$N_JOB=50;
	else if ($N_C<20000)$N_JOB=100;
	$N_J=ceil($N_C/$N_JOB);
	
	/// Create the SCRIPTS directory if it does not exist
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))										failProcess($JOB_ID."011",'Unable to create SCRIPTS directory');


	$fp=fopen('oa_file_list.csv','r');if (!$fp)											failProcess($JOB_ID."010",'Unable to open oa_file_list.csv file ');
	
	
	addLog("CURRENT RELEASE:".$CURR_RELEASE);
	
	$HEAD=fgetcsv($fp);
	/// Create the process.csv file that will be list all the files to process
	$fpO=fopen('SCRIPTS/process.csv','w');if (!$fpO)										failProcess($JOB_ID."012",'Unable to open SCRIPTS/oa_file_list.csv');
	$HEAD[]='job_id';
	fputcsv($fpO,$HEAD);
	$I=0;
	while(!feof($fp))
	{
		$fpos=ftell($fp);
		$line=fgetcsv($fp);
		if (!isset($FPOSITION[$fpos]))continue;
		$line['job_id']=$I%$N_JOB;
		fputcsv($fpO,$line);
		++$I;

	}

	
	fclose($fp);
	fclose($fpO);
	
	
	///Create batch script:
	$fpA=fopen("SCRIPTS/all.sh",'w'); if(!$fpA)											failProcess($JOB_ID."013",'Unable to open all.sh');
	
	for($I=0;$I<$N_JOB;++$I)
	{
		/// And the individual job script
		$JOB_NAME="SCRIPTS/job_".$I.".sh";
		$fp=fopen($JOB_NAME,"w");if(!$fpA)												failProcess($JOB_ID."014",'Unable to open jobs/job_'.$I.'.sh');
		
		/// Add the job to the batch script
		fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");

		/// Populate the job script
		fputs($fp,'#!/bin/sh'."\n");
		fputs($fp,"source ".$SETENV."\n");	/// Set up the environment
		fputs($fp,'cd '.$W_DIR."\n");		/// Go to the working directory
		fputs($fp,'biorels_php '.$RUNSCRIPT.' '.$I.' &> SCRIPTS/'.'LOG_'.$I."\n");	/// Run the script
		fputs($fp,'echo $? > SCRIPTS/status_'.$I."\n");	/// Save the status of the script
		fclose($fp);
	
		
	}
	fclose($fpA);




successProcess();
?>

