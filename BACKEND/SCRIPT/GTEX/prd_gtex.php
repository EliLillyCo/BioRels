<?php

/**
 SCRIPT NAME: prd_gtex
 PURPOSE:     Push all gtex files to production
 
*/

/// Job name - Do not change
$JOB_NAME='prd_gtex';


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
$CK_INFO=$GLB_TREE[getJobIDByName('db_gtex')];
/// Get to working directory
$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
$OT_DIR=$W_DIR.'/'.$CK_INFO['DIR'].'/';   if (!is_dir($OT_DIR)) 					failProcess($JOB_ID."002",'Unable to find and create '.$OT_DIR);
/// Go to the directory set up by parent
$W_DIR=$OT_DIR.$CK_INFO['TIME']['DEV_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
											  if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory:".$W_DIR);



addLog("Push to prod");
	pushToProd();

successProcess();
?>
