<?php

/// Job name - Do not change
$JOB_NAME='prd_ot';


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
	///	get Parent info:
	$CK_OT_INFO=$GLB_TREE[getJobIDByName('db_ot_clintrial')];

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$OT_DIR=$W_DIR.'/'.$CK_OT_INFO['DIR'].'/';   if (!is_dir($OT_DIR) && !mkdir($OT_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$OT_DIR);
	$W_DIR=$OT_DIR.$CK_OT_INFO['TIME']['DEV_DIR'];if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
											  if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

addLog("Working directory: ".$W_DIR);
$PROCESS_CONTROL['DIR']=$CK_OT_INFO['TIME']['DEV_DIR'];



addLog("Push to prod");
	updateReleaseDate($JOB_ID,'OT',getCurrDate());

	pushToProd();
successProcess();
?>
