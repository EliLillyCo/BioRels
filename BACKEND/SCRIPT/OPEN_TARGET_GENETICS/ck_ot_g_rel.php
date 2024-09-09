<?php


/**
 SCRIPT NAME: ck_ot_g_rel
 PURPOSE:     Check for open target genetics release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_ot_g_rel';


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

addLog("Check variables");
	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
addLog("Working directory : ".$W_DIR);
	/// Check if the FTP_OPEN_TARGETS_G path is set
	if (!isset($GLB_VAR['LINK']['FTP_OPEN_TARGETS_G']))									failProcess($JOB_ID."004",'FTP_OPEN_TARGETS_G path no set');

	/// Remove the previous version of the file
	if (is_file('index.html') && !unlink('index.html'))									failProcess($JOB_ID."005",'unable to delete index.html');


	/// Get the index.html from the FTP page:
	if (!dl_file($GLB_VAR['LINK']['FTP_OPEN_TARGETS_G']))								failProcess($JOB_ID."006",'unable to get FTP_OPEN_TARGETS_G page');

	/// Process the file:
	$fp=fopen('index.html','r');if (!$fp)												failProcess($JOB_ID."007",'unable to open page');
	$CURR_VERSION=array(0,0);
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		/// Find the version number based on regex
		if (!preg_match('/([0-9]{2}\.[0-9]{2}(\.[0-9]{1,2}){0,1})/',$line,$matches))continue;
		
		$tab=explode(".",$matches[0]);
		/// Get the highest version
		if ($tab[0]>$CURR_VERSION[0])$CURR_VERSION=$tab;
		else if ($tab[0]==$CURR_VERSION[0] && $tab[1]>$CURR_VERSION[1])$CURR_VERSION=$tab;
	}
	fclose($fp);
	
	if (!unlink('index.html'))															failProcess($JOB_ID."008",'unable to delete index.html');
	
	if ($CURR_VERSION==array(0,0))														failProcess($JOB_ID."009",'unable to find version');
	
	$NEW_RELEASE=implode(".",$CURR_VERSION);
	


addLog("Get current release date for OPEN TARGETS GENETICS");
	$CURR_RELEASE=getCurrentReleaseDate('OPEN_TARGET_G',$JOB_ID);
	
	/// If the release date is the same as the current release date, then we are done

addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($NEW_RELEASE==$CURR_RELEASE)successProcess("VALID");
	

addLog("Update release tag for OPEN TARGETS");
	/// Update the release date
	updateReleaseDate($JOB_ID,'OPEN_TARGET_G',$NEW_RELEASE);


	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Set up directory with current date
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."010",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."011",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."012",'Unable to create new process dir '.$W_DIR);
	
	/// Update process control directory to the current release
	$PROCESS_CONTROL['DIR']=getCurrDate();

successProcess();

?>
