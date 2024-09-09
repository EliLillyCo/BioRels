<?php


/**
 SCRIPT NAME: ck_efo_rel
 PURPOSE:     Check for new EFO ontology release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_efo_rel';

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

	///Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);

	/// Check if FTP_EFO path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_EFO']))											failProcess($JOB_ID."004",'FTP_EFO path no set');

	


addLog("Download content");
	
	// Cleaning up former version if exists
	if (is_file('efo.owl') && !unlink('efo.owl'))										failProcess($JOB_ID."004",'Unable to remove efo.owl');
	

	$content=array();
	/// Downloading efo.owl:
	$FNAME=$GLB_VAR['LINK']['FTP_EFO'].'/efo.owl';
	if (!dl_file($FNAME))																failProcess($JOB_ID."005",'Unable to download file');
	

	/// Getting the version of the release
	$content=array();
	exec('grep "owl:versionIRI" efo.owl',$content);
	if (count($content)!=1)																failProcess($JOB_ID."006",'Unable to verify date');
	
	$NEW_RELEASE='';
	/// Ensuring we can get a date:
	if (!preg_match("/([0-9]{0,4}\.[0-9]{0,3}\.[0-9]{0,3})/",$content[0],$info))		failProcess($JOB_ID."007",'Unable to find date');
	$NEW_RELEASE=$info[0];	
	if ($NEW_RELEASE=='')																failProcess($JOB_ID."008",'Unable to find date'); 

	
addLog("Get current release date for EFO");
	$CURR_RELEASE=getCurrentReleaseDate('EFO',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($CURR_RELEASE == $NEW_RELEASE)
	{
		if (!unlink('efo.owl'))														failProcess($JOB_ID."009",'Unable to remove efo.owl');
		successProcess('VALID');
	}
	

addLog("Update release tag for ECO");
	updateReleaseDate($JOB_ID,'EFO',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	
	/// Create a directory for today's date
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."010",'Unable to create new process dir '.$W_DIR);

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=getCurrDate();

	/// Move the file to the new directory
	if (!rename('efo.owl',$W_DIR.'/efo.owl'))								 			failProcess($JOB_ID."011",'Unable to move eco.owl to '.$W_DIR);





successProcess();

?>
