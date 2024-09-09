<?php


/**
 SCRIPT NAME: ck_ensembl_rel
 PURPOSE:     Check if there is a new release for ENSEMBL
 
*/

/// Job name - Do not change
$JOB_NAME='ck_ensembl_rel';


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


addLog("Setting up");

	///Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	/// Check if FTP_ENSEMBL path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_ENSEMBL']))										failProcess($JOB_ID."004",'FTP_ENSEMBL path no set');
	///	Check if FTP_ENSEMBL_ASSEMBLY path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_ENSEMBL_ASSEMBLY']))								failProcess($JOB_ID."005",'FTP_ENSEMBL_ASSEMBLY path no set');

addLog("Download ftp entry page to find release");

	/// Remove previous current_README
	if (checkFileExist('current_README') && !unlink('current_README'))					failProcess($JOB_ID."006",'Unable to delete previous current_README');

	/// Download the current_README file
	if (!dl_file($GLB_VAR['LINK']['FTP_ENSEMBL'].'/current_README',3))					failProcess($JOB_ID."007",'Unable to download current_README ');

	/// Get the release number
	$NEW_RELEASE=-1;
	$fp=fopen('current_README','r');if (!$fp)											failProcess($JOB_ID."008",'Unable to open current_README ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')	continue;
		/// By using a regular expression we can find the release number
		preg_match('/The current release is Ensembl ([0-9]{1,4})/',$line,$matches);
		if (count($matches)==0)continue;
		$NEW_RELEASE=$matches[1];
		break;
	}
	fclose($fp);

	/// If we can't find the release number, we fail
	if (!is_numeric($NEW_RELEASE)|| $NEW_RELEASE==-1)									failProcess($JOB_ID."009",'Unable to find current release ');

addLog("Get current release date for ENSEMBL");
	$CURR_RELEASE=getCurrentReleaseDate('ENSEMBL',$JOB_ID);

	unlink('current_README');
	if ($CURR_RELEASE==$NEW_RELEASE)successProcess('VALID');

addLog("Update release tag for ENSEMBL");
	updateReleaseDate($JOB_ID,'ENSEMBL',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Creating the directory with today's date
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."010",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."011",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."012",'Unable to create new process dir '.$W_DIR);
	if (!chdir($W_DIR))															 		failProcess($JOB_ID."013",'Unable to get in '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=getCurrDate();



	successProcess();

?>
