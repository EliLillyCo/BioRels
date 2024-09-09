<?php

/**
 SCRIPT NAME: ck_surechembl_rel
 PURPOSE:     Check for new release of the SureChembl database
 
*/
$JOB_NAME='ck_surechembl_rel';

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
	
	/// Removing previous index.html:
	if (is_file('index.html') && !unlink($W_DIR.'/index.html'))							failProcess($JOB_ID."004",'Unable to delete index.html');
	
	/// Ensuring we have the path and Downloading the index.html file:
	if (!isset($GLB_VAR['LINK']['FTP_SURECHEMBL']))										failProcess($JOB_ID."005",'FTP_SURECHEMBL path no set');
	if (!dl_file($GLB_VAR['LINK']['FTP_SURECHEMBL'],3))									failProcess($JOB_ID."006",'Unable to download index.html');
	
	
	

addLog("Process index.html");
	
	$fp=fopen('index.html','r')	;if (!$fp)											failProcess($JOB_ID."007",'Unable to open index.html');
	$NEW_RELEASE=-1;$TIMEST='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		/// Looking at all the files in the directory
		if (!preg_match('/SureChEMBL_([0-9]{1,8})_([0-9]{1,3}).txt.gz/',$line,$matches))continue;
		
		if (count($matches)!=3)continue;
		/// Getting the latest created file date:
		if ($NEW_RELEASE>= $matches[2])continue;
		
		$NEW_RELEASE= $matches[2];
		$TIMEST=$matches[1];		
	}
	fclose($fp);
	if (!unlink($W_DIR.'/index.html'))												failProcess($JOB_ID."008",'Unable to delete index.html');
	
	echo $NEW_RELEASE.' '.$TIMEST."\n";

	$NEW_RELEASE.='-'.$TIMEST;
	

addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('SURECHEMBL',$JOB_ID);


addLog("Compare release");
	
	if ($CURR_RELEASE == $NEW_RELEASE){	successProcess('VALID');}
	
addLog("Compare License");
	

	if (!dl_file($GLB_VAR['LINK']['FTP_SURECHEMBL'].'/LICENSE',3))					failProcess($JOB_ID."009",'Unable to download license file');
	if (!is_file('CURRENT_LICENSE'))
	{
		rename('LICENSE','CURRENT_LICENSE');
	}
	else 
	{
		if (md5_file('LICENSE') != md5_file('CURRENT_LICENSE'))	
		{
			if (!unlink($W_DIR.'/LICENSE'))												failProcess($JOB_ID."012",'Unable to delete LICENSE');
			///we stop here because you will need to review the new license
			/// Once reviewed and approved, just rename LICENSE to CURRENT_LICENSE
																						failProcess($JOB_ID."013",'License file is different');
		}
		
	}	


addLog("Update release tag");
	updateReleaseDate($JOB_ID,'SURECHEMBL',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."014",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."015",'Unable to find and create '.$W_DIR);
	/// Create the process directory with the current date:
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."016",'Unable to create new process dir '.$W_DIR);
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>
