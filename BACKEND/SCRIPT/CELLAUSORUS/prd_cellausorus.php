<?php

/**
 SCRIPT NAME: prd_cellausorus
 PURPOSE:     Push all cellausorus files to production
 
*/

/// Name of the job:
$JOB_NAME='prd_cellausorus';

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




addLog("Access directory");
	$PAR_INFO=$GLB_TREE[getJobIDByName('db_cellausorus')];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];						if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	
	$W_DIR.=$PAR_INFO['TIME']['DEV_DIR'];	   if (!is_dir($W_DIR) ||!chdir($W_DIR)) 						failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=$PAR_INFO['TIME']['DEV_DIR'];

	
addLog("Push to prod");
	pushToProd();
	


successProcess();
?>
