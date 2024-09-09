<?php

/**
 SCRIPT NAME: ck_uberon_rel
 PURPOSE:     Check for new release of the Uberon ontology
 
*/
$JOB_NAME='ck_uberon_rel';

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
	
	/// Checking ftp:
	if (!isset($GLB_VAR['LINK']['FTP_EBI_OBO']))										failProcess($JOB_ID."004",'FTP_EBI_OBO path no set');

	


addLog("Download content");
	$content=array();
	if (is_file('uberon.owl') && !unlink('uberon.owl'))									failProcess($JOB_ID."005",'Unable to delete previous version');
	
	/// Creating file path:
	$FNAME=$GLB_VAR['LINK']['FTP_EBI_OBO'].'/uberon.owl';
	

	/// Downloading file:
	exec('wget "'.$FNAME.'"',$content,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."006",'Unable to download file');
	
	/// Getting version:
	$content=array();
	exec('grep "owl:versionIRI" uberon.owl',$content);
	
	if (count($content)!=1)																failProcess($JOB_ID."007",'Unable to verify date');
	
	/// Checking the release matches a date format:
	$NEW_RELEASE='';
	if (!preg_match("/([0-9]{0,4}-[0-9]{0,3}-[0-9]{0,3})/",$content[0],$info))			failProcess($JOB_ID."008",'Unable to find date');
	$NEW_RELEASE=$info[0];
	//print_r($NEW_RELEASE);
	
	if ($NEW_RELEASE=='')																failProcess($JOB_ID."009",'Unable to find date'); 

	
addLog("Get current release date for UBERON");
	$CURR_RELEASE=getCurrentReleaseDate('UBERON',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($CURR_RELEASE == $NEW_RELEASE)
	{
		if (!unlink('uberon.owl'))													failProcess($JOB_ID."010",'Unable to delete file');
		successProcess('VALID');
	}
	

addLog("Update release tag for UBERON");
	updateReleaseDate($JOB_ID,'UBERON',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Create directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."010",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."011",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."012",'Unable to create new process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();
	if (!rename('uberon.owl',$W_DIR.'/uberon.owl'))								 		failProcess($JOB_ID."013",'Unable to move eco.owl to '.$W_DIR);
successProcess();

?>
