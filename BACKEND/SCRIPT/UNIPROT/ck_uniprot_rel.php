<?php
/**
 SCRIPT NAME: ck_uniprot_rel
 PURPOSE:     Check for new release of the Uniprot
 
*/

/// Job name - Do not change
$JOB_NAME='ck_uniprot_rel';

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




addLog("Setting up:");
	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	
	/// Checking FTP path:
	if (!isset($GLB_VAR['LINK']['FTP_UNIPROT']))										failProcess($JOB_ID."004",'FTP_UNIPROT path no set');
	/// Removing previous release notes:
	if (is_file('relnotes.txt') && !unlink('relnotes.txt'))								failProcess($JOB_ID."005",'Unable to remove previous release notes');
	if (!dl_file($GLB_VAR['LINK']['FTP_UNIPROT'].'/current_release/relnotes.txt',3))	failProcess($JOB_ID."006",'Unable to download release notes');
	
	
	

addLog("Process release note");

	/// Read the content of the release notes - break it in lines:
	$tab=explode("\n",file_get_contents('relnotes.txt'));if (count($tab)==1)			failProcess($JOB_ID."007",'Not enought lines');

	/// Analyzing the first line by breaking it into words, remove empty elements and re-index the array:
	$line=array_values(array_filter(explode(" ",$tab[0])));if (count($line)!=3)			failProcess($JOB_ID."008",'Unexpected line format');

addLog("Validate release note");
	$NEW_RELEASE=$line[2];
	
	$tab2=explode("_",$NEW_RELEASE);
	if ($tab2[0]!=date("Y") && $tab2[0]!=(date("Y")-1))									failProcess($JOB_ID."009",'Unexpected year format');

addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('UNIPROT',$JOB_ID);


addLog("Compare release");
	/// No need to keep the release note file
	if (!unlink($W_DIR.'relnotes.txt'))													failProcess($JOB_ID."010",'Unable to delete relnotes.txt');
	
	/// If the release is the same as the current release, we are done
	if ($CURR_RELEASE == $NEW_RELEASE)	successProcess('VALID');
	
	
addLog("Compare License");
	
	/// Download the license file:
	if (!dl_file($GLB_VAR['LINK']['FTP_UNIPROT'].'/LICENSE',3))							failProcess($JOB_ID."011",'Unable to download license file');
	if (!is_file('CURRENT_LICENSE'))
	{
		rename('LICENSE','CURRENT_LICENSE');
	}
	else 
	{
		///Compare the license file:
		if (md5_file('LICENSE')!=md5_file('CURRENT_LICENSE'))							failProcess($JOB_ID."012",'License file is different');
		if (!unlink($W_DIR.'/LICENSE'))													failProcess($JOB_ID."013",'Unable to delete LICENSE');
	}	


addLog("Update release tag");
	updateReleaseDate($JOB_ID,'UNIPROT',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Create directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."014",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."015",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."016",'Unable to create new process dir '.$W_DIR);
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>

