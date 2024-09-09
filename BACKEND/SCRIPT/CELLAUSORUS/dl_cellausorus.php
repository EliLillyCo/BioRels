<?php

/**
 SCRIPT NAME: dl_cellausorus
 PURPOSE:     Download all cellausorus files
 
*/

/// Name of the job:
$JOB_NAME='dl_cellausorus';

/// Get biorels Root directory
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);

/// Load the loader - loading all necessary files
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

/// Get job id & info
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
 

addLog("Get to directory");
	/// Get parent job info:
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_cellausorus_rel')];

	/// Go to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		 if (!is_dir($W_DIR) || !chdir($W_DIR)) failProcess($JOB_ID."003",'Unable to access new process dir '.$W_DIR);
		
	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Check if FTP_CELLAUSORUS path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_CELLAUSORUS']))									failProcess($JOB_ID."004",'FTP_CELLAUSORUS path no set');

	/// Download file
	if (!dl_file($GLB_VAR['LINK']['FTP_CELLAUSORUS'].'/cellosaurus.txt'))				failProcess($JOB_ID."005",'Unable to download cellausorus.txt');

	successProcess();


?>
