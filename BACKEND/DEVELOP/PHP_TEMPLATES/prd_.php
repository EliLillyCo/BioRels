<?php
/**
 SCRIPT NAME: prd_${DATASOURCE}
 PURPOSE:     Push to production ${DATASOURCE}
 
*/

/// Job name - Do not change
$JOB_NAME='prd_${DATASOURCE}';

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
	/// Get parent info:
	$CK_INFO=$GLB_TREE[getJobIDByName('${PRD_PARENT}')];

	/// Define working directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/';
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];
	
	if (!is_dir($W_DIR) || !chdir($W_DIR)) 											failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	
	/// Update process control directory so the next script knows the working directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];



addLog("Cleanup");

	/// Clean up all intermediate directories.
	cleanDirectory('INSERT');
 	cleanDirectory('JSON');
 	cleanDirectory('SCRIPTS');

addLog("Push to prod");
	pushToProd();
	


		

successProcess();
?>
