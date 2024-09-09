<?php



/**
 SCRIPT NAME: ck_cellausorus_rel
 PURPOSE:     Check for new release of cellausorus & license
 
*/


$JOB_NAME='ck_cellausorus_rel';

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
	/// Set up working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	/// Change to working directory
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);


addLog("Download release note");
	/// Check if FTP_CELLAUSORUS path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_CELLAUSORUS']))									failProcess($JOB_ID."004",'FTP_CELLAUSORUS path no set');
	/// Check if file exist and remove it if it exists
	if (checkFileExist('cellosaurus_relnotes.txt')&&!unlink('cellosaurus_relnotes.txt'))failProcess($JOB_ID."005",'Unable to remove previous relnotes.txt');
	/// Download file
	if (!dl_file($GLB_VAR['LINK']['FTP_CELLAUSORUS'].'/cellosaurus_relnotes.txt',3))	failProcess($JOB_ID."006",'Unable to download relnotes.txt');
	
	

addLog("Process cellosaurus_relnotes");
		
	/// We are going to extract the release number from the file
	$fp=fopen('cellosaurus_relnotes.txt','r')	;if (!$fp)								failProcess($JOB_ID."007",'Unable to open cellosaurus_relnotes.txt');
	$NEW_RELEASE='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if (!preg_match('/This is the release notes for Cellosaurus version ([0-9]{1,3}) of/',$line,$matches))continue;
		
		$NEW_RELEASE=$matches[1];
		
		break;
	}
	fclose($fp);
	/// Check if the release number is valid
	if (!is_numeric($NEW_RELEASE))														failProcess($JOB_ID."008",'Unexpected release format');

addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('CELLAUSORUS',$JOB_ID);
	
	
addLog("Compare release");
	if ($CURR_RELEASE == $NEW_RELEASE){	successProcess('VALID');}
		

addLog("Compare License");
	/// Downloading license file
	if (!dl_file($GLB_VAR['LINK']['FTP_CELLAUSORUS'].'/README',3,'LICENSE'))			failProcess($JOB_ID."009",'Unable to download license file');
	/// We are going to compare the license file with the current license file
	if (!is_file('CURRENT_LICENSE'))
	{
		rename('LICENSE','CURRENT_LICENSE');
	}
	else 
	{
		/// Comparing line by line license file
		$fp=fopen('LICENSE','r'); if (!$fp) 											failProcess($JOB_ID."010",'Unable to open LICENSE');
		$fp2=fopen('CURRENT_LICENSE','r'); if (!$fp2) 									failProcess($JOB_ID."011",'Unable to open CURRENT_LICENSE');
		$VALID=true;
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			$line2=stream_get_line($fp2,10000,"\n");
			if ($line!=$line2){$VALID=false;break;}
		}
		fclose($fp);
		fclose($fp2);
		/// We can remove the license file
		if (!unlink($W_DIR.'/LICENSE'))													failProcess($JOB_ID."012",'Unable to remove LICENSE');
		/// If the license file is different we fail the process
		if (!$VALID)																	failProcess($JOB_ID."013",'License file is different');
	}	
	

addLog("Update release tag");
	updateReleaseDate($JOB_ID,'CELLAUSORUS',$NEW_RELEASE);
	
	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Create the full path for the processing directory for this release
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."011",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."012",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."013",'Unable to create new process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();
	
successProcess();
	

?>
