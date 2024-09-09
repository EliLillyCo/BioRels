<?php
/**
 SCRIPT NAME: prd_translate
 PURPOSE:     Clean up directory and push to production
 
*/

/// Job name - Do not change
$JOB_NAME='prd_translate';

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
	$DL_INFO=$GLB_TREE[getJobIDByName('db_translate')];

	/// Define working directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/';
	$W_DIR.=$DL_INFO['TIME']['DEV_DIR'];
	
	if (!is_dir($W_DIR) || !chdir($W_DIR)) 											failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	
	/// Update process control directory so the next script knows the working directory
	$PROCESS_CONTROL['DIR']=$DL_INFO['TIME']['DEV_DIR'];



addLog("Cleanup");

	exec('rm -f '.$W_DIR.'/status',$res,$return_code);if ($return_code!=0)			failProcess($JOB_ID."002",'Unable to remove status file');
	if (is_dir($W_DIR.'/jobs') && !deleteDir($W_DIR.'/jobs'))						failProcess($JOB_ID."003",'Unable to delete job directory');
	if (is_dir($W_DIR.'/INSERT') && !deleteDir($W_DIR.'/INSERT'))					failProcess($JOB_ID."004",'Unable to INSERT job directory');
	exec('rm -f '.$W_DIR.'/DATA/*.fasta',$res,$return_code);if ($return_code!=0)	failProcess($JOB_ID."005",'Unable to remove fasta file');
	exec('rm -f '.$W_DIR.'/DATA/*.pep',$res,$return_code);if ($return_code!=0)		failProcess($JOB_ID."006",'Unable to remove pep file');


addLog("Push to prod");
	pushToProd();
	

successProcess();
?>

