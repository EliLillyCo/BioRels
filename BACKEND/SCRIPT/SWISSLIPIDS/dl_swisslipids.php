<?php

/**
 SCRIPT NAME: dl_swisslipids
 PURPOSE:     Download swisslipids files and process them
 
*/

/// Job name - Do not change
$JOB_NAME='dl_swisslipids';

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
 
addLog("Access directory");
	/// Get parent job info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_swisslipids_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
	
	if (!isset($GLB_VAR['LINK']['FTP_SWISSLIPIDS']))									failProcess($JOB_ID."005",'FTP_SWISSLIPIDS path no set');
	
	
	if (!dl_file('https://www.swisslipids.org/api/index.php/downloadData',3,'relnotes.txt'))	failProcess($JOB_ID."006",'Unable to download release notes');
	
addLog("Process release note");
	///relnote contains the list of files. We will download them all
	$tab=json_decode(file_get_contents('relnotes.txt'),true);
	foreach ($tab as $file)
	{
		/// Download the file
		if (!dl_file($file['url'],3,$file['file'].'.gz'))								failProcess($JOB_ID."007",'Unable to download '.$file['file']);
		/// Only go.tsv is not gzipped
		if ($file['file']=='go.tsv') continue;
		/// Ungzip the file
		if (!ungzip($file['file'].'.gz'))												failProcess($JOB_ID."008",'Unable to ungzip '.$file['file']);
	}


successProcess();


	

?>

