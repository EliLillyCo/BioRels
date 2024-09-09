<?php

/**
 SCRIPT NAME: ck_swisslipids_rel
 PURPOSE:     Cleanup SwissLipids files & push to production
 
*/

/// Job name - Do not change
$JOB_NAME='ck_swisslipids_rel';

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
 
addLog("Download release note");
	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	/// Checking for the FTP path and downloading the release notes:
	if (!isset($GLB_VAR['LINK']['FTP_SWISSLIPIDS']))									failProcess($JOB_ID."004",'FTP_SWISSLIPIDS path no set');
	if (is_file('relnotes.txt') && !unlink('relnotes.txt'))								failProcess($JOB_ID."005",'Unable to delete relnotes.txt');
	
	if (!dl_file('https://www.swisslipids.org/api/index.php/downloadData',3,'relnotes.txt'))	failProcess($JOB_ID."006",'Unable to download release notes');
	
	

addLog("Process release note");

	/// Relnotes is a json file, we will read the file, then do some replacements to make it a valid json file
	$tab=json_decode(file_get_contents('relnotes.txt'),true);
	

addLog("Validate release note");
	if (!isset($tab[0]['date']))															failProcess($JOB_ID."007",'Unexpected date format');
	$NEW_RELEASE=$tab[0]['date'];
	

addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('SWISSLIPIDS',$JOB_ID);


addLog("Compare release");
	if (!unlink($W_DIR.'relnotes.txt'))												failProcess($JOB_ID."008",'Unable to delete relnotes.txt');
	if ($CURR_RELEASE == $NEW_RELEASE){	successProcess('VALID');}
	
	


addLog("Update release tag");
	updateReleaseDate($JOB_ID,'SWISSLIPIDS',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	
	/// Create the directory for the job
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."009",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."010",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."011",'Unable to create new process dir '.$W_DIR);
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>

