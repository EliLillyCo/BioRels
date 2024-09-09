<?php

/**
 SCRIPT NAME: prd_surechembl
 PURPOSE:     Cleanup SureChembl files & push to production
 
*/

/// Job name - Do not change
$JOB_NAME='prd_surechembl';

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
 

addLog("Define directory");
$CK_INFO=$GLB_TREE[getJobIDByName('db_surechembl_cpd')];
$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];	if (!is_dir($W_DIR)) 				failProcess($JOB_ID.'001','NO '.$W_DIR.' found ');
$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR)) 				failProcess($JOB_ID.'002','Unable to find and create '.$W_DIR);
$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; 		if (!is_dir($W_DIR)) 				failProcess($JOB_ID.'003','Unable to create new process dir '.$W_DIR);
											if (!chdir($W_DIR)) 				failProcess($JOB_ID.'004','Unable to access process dir '.$W_DIR);


addLog("Working directory: ".$W_DIR);




addLog("Cleanup files");
	if (is_dir('STD'))cleanDirectory('STD');
	if (is_dir('INSERT'))cleanDirectory('INSERT');
	if (is_dir('LOG_INSERT'))cleanDirectory('LOG_INSERT');
	
addLog("Switch to production");
	pushToProd();
	

successProcess();

?>