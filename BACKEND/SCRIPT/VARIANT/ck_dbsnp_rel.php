<?php
/**
 SCRIPT NAME: ck_dbsnp_rel
 PURPOSE:     Check for new release of dbsnp
 
*/

/// Job name - Do not change
$JOB_NAME='ck_dbsnp_rel';

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
	
	$W_DIR.='DBSNP/';
	if (!is_dir("DBSNP") && !mkdir("DBSNP"))											failProcess($JOB_ID."004",'Unable to create DBSNP directory ');
	if (!chdir("DBSNP"))																failProcess($JOB_ID."005",'Unable to get in DBSNP directory ');


	/// Check FTP path:
	if (!isset($GLB_VAR['LINK']['FTP_DBSNP']))											failProcess($JOB_ID."006",'FTP_DBSNP path no set');
	
	/// Step 1: Download DBSNP release notes
	if (is_file('release_notes.txt') && !unlink('release_notes.txt'))					failProcess($JOB_ID."007",'Unable to remove old release note ');

	if (!dl_file($GLB_VAR['LINK']['FTP_DBSNP'].'/latest_release/release_notes.txt',3))	failProcess($JOB_ID."008",'Unable to download release note ');
	
	if (!checkFileExist("release_notes.txt"))											failProcess($JOB_ID."009",'Couldn\'t find release note ');
	
	$NEW_RELEASE=explode(" ",explode("\n",file_get_contents("release_notes.txt"))[0])[2];
	
	if (!is_numeric($NEW_RELEASE))														failProcess($JOB_ID."010",'Unexpected release format for DBSNP');

addLog("Get current release date for DBSNP");
	$CURR_RELEASE=getCurrentReleaseDate('DBSNP',$JOB_ID);
	
	
addLog($CURR_RELEASE."\t".$NEW_RELEASE);

	if ($CURR_RELEASE == $NEW_RELEASE)
	{
		if (!unlink('release_notes.txt'))												failProcess($JOB_ID."011",'Unable to remove release note ');
		successProcess("VALID");
	}

addLog("Update release tag");
	updateReleaseDate($JOB_ID,'DBSNP',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."012",'Unable to create new process dir '.$W_DIR);

	/// Move release note to the new directory
	if (!rename('release_notes.txt',$W_DIR.'/release_notes.txt'))					failProcess($JOB_ID."013",'Unable to move release_notes.txt to '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>
