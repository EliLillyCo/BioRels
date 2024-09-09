<?php

/**
 SCRIPT NAME: ck_${DATASOURCE}_rel
 PURPOSE:     Check for new release of the ${DATASOURCE}
 
*/
$JOB_NAME='ck_${DATASOURCE}_rel';

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
	/// Setting up working directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	/// Checking ftp file paths:
	if (!isset($GLB_VAR['LINK']['FTP_${DATASOURCE}']))										failProcess($JOB_ID."004",'FTP_${DATASOURCE} path no set');

addLog("Check license from ${DATASOURCE}");	

	/// Download the license file for this data source
	/// and compare it to the current version of the license stored

	if (is_file($W_DIR.'/LICENSE') && !unlink($W_DIR.'/LICENSE'))						failProcess($JOB_ID."009",'Unable to delete LICENSE');
	
	/// Please adapt here the path to the license:
	if (!dl_file($GLB_VAR['LINK']['FTP_${DATASOURCE}'].'/LICENSE',3,'LICENSE'))		failProcess($JOB_ID."010",'Unable to download license file');
	
	/// First time: set it as the license
	if (!is_file('CURRENT_LICENSE'))
	{
		rename('LICENSE','CURRENT_LICENSE');
	}
	else 
	{
		addLog("Compare License");
		/// Otherwise we compare line by line
		$fp=fopen('LICENSE','r'); if (!$fp)												failProcess($JOB_ID."010",'Unable to open LICENSE');
		$fp2=fopen('CURRENT_LICENSE','r'); if (!$fp2)									failProcess($JOB_ID."011",'Unable to open CURRENT_LICENSE');
		$VALID=true;
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			$line2=stream_get_line($fp2,10000,"\n");
			if ($line!=$line2){$VALID=false;break;}
		}
		fclose($fp);
		fclose($fp2);
		if (!unlink($W_DIR.'/LICENSE'))													failProcess($JOB_ID."012",'Unable to remove LICENSE');
		if (!$VALID)																	failProcess($JOB_ID."013",'License file is different');
	}	


addLog("Get release date from ${DATASOURCE}");
	
	/// Please adapt here the path to the license:
	if (!dl_file($GLB_VAR['LINK']['FTP_${DATASOURCE}'].'/VERSION',3,'VERSION'))		failProcess($JOB_ID."010",'Unable to download VERSION file');

	/// Process file to retrieve the license
	
	
addLog("Get current release date for ${DATASOURCE}");
	$CURR_RELEASE=getCurrentReleaseDate('${DATASOURCE_NC}',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($CURR_RELEASE == $NEW_RELEASE)
	{
		successProcess('VALID');
	}
	

addLog("Update release tag for ${DATASOURCE}");
	updateReleaseDate($JOB_ID,'${DATASOURCE_NC}',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Create directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."010",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."011",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."012",'Unable to create new process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();
	


successProcess();

?>
