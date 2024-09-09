<?php
ini_set('memory_limit','5000M');
/**
 SCRIPT NAME: dl_pmc
 PURPOSE:     Download the list of publications available in PMC
 
*/
$JOB_NAME='dl_pmc';

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
	/// This is the Root directory for PMC
	$R_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'];
	
	/// Create the directory if it does not exist
	if (!is_dir($R_DIR) && !mkdir($R_DIR)) 											failProcess($JOB_ID."001",'Unable to create new process dir '.$R_DIR);	
	
	// This is the working directory for the current process
	$W_DIR=$R_DIR.'/'.getCurrDate();
	
	addLog("Working directory:" .$W_DIR);
	
	// Create the directory if it does not exist
	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 											failProcess($JOB_ID."002",'Unable to create new process dir '.$W_DIR);	
	
	/// Setting up the process control directory
	$PROCESS_CONTROL['DIR']=getCurrDate();

	// Change to the working directory
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);
	
	// Check if the WEB FTP link is set
	if (!isset($GLB_VAR['LINK']['FTP_EUROPEPMC']))									failProcess($JOB_ID."004",'FTP_EUROPEPMC path no set');

	// Download the file
	if (!checkFileExist('oa_file_list.csv') &&
		!dl_file($GLB_VAR['LINK']['FTP_PMC'].'/oa_file_list.csv'))					failProcess($JOB_ID."005",'Unable to download file '.$GLB_VAR['LINK']['FTP_PMC'].'/oa_file_list.csv');
	
successProcess();



	




?>