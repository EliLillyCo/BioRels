<?php
/*
 SCRIPT NAME: pmj_${DATASOURCE}
 PURPOSE:     Prepare scripts for ${DATASOURCE} Processing
 
*/

/// Job name - Do not change
$JOB_NAME='pmj_${DATASOURCE}';

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
	
	/// GEt parent info	
	$CK_INFO=$GLB_TREE[getJobIDByName('${PP_PARENT}')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Check SCRIPT_DIR
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."005",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."006",'SCRIPT_DIR not found ');

	/// Check setenv.sh
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."007",'Setenv file not found ');

	/// Check process_${DATASOURCE}.php
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_${DATASOURCE}.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."008",$RUNSCRIPT.' file not found');

	$RUNSCRIPT_PATH='$TG_DIR/'.$GLB_VAR['SCRIPT_DIR'].'/'.$JOB_INFO['DIR'].'/process_${DATASOURCE}.php';
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."009",'JOBARRAY NOT FOUND ');
	
	
	/// Check JOBARRAY
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."010",'JOBARRAY file NOT FOUND '.$JOBARRAY);

addLog("Working directory: ".$W_DIR);	


	/// Working path:
	$W_DIR_PATH='$TG_DIR/'.$GLB_VAR['PROCESS_DIR'].'/'.$CK_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];
	
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))										failProcess($JOB_ID."015",'Unable to create jobs directory');
	if (!is_dir("JSON") && !mkdir("JSON"))												failProcess($JOB_ID."016",'Unable to create jobs directory');
	
	
	/// Create master script:
	$fpA=fopen("SCRIPTS/all.sh",'w'); if(!$fpA)											failProcess($JOB_ID."017",'Unable to open all.sh');
	$N_JOB=50;


	for($I=0;$I<$N_JOB;++$I)
	{
		$JOB_NAME="SCRIPTS/job_".$I.".sh";
	
		/// Create job script
		$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."018",'Unable to open jobs/job_'.$I.'.sh');
		
		/// Add to master script
		fputs($fpA," sh ".$W_DIR_PATH.'/'.$JOB_NAME."\n");

		fputs($fp,'#!/bin/sh'."\n");
		fputs($fp,"source ".$SETENV."\n");/// Load environment
		fputs($fp,'cd '.$W_DIR_PATH."\n");/// Change to working directory
		fputs($fp,'biorels_${LANGUAGE} '.$RUNSCRIPT_PATH.' '.$I.' F &> SCRIPTS/LOG_'.$I."\n");/// Run the script
		fputs($fp,'echo $? > SCRIPTS/status_'.$I."\n");/// Save the status
		fclose($fp);
	}
	fclose($fpA);





successProcess();

?>


