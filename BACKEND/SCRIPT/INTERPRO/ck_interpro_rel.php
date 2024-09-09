<?php

/**
 SCRIPT NAME: ck_interpro_rel
 PURPOSE:     Check for new InterPro release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_interpro_rel';


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
	/// Set up the working directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	/// Check for the FTP path
	if (!isset($GLB_VAR['LINK']['FTP_INTERPRO']))										failProcess($JOB_ID."004",'FTP_INTERPRO path no set');

	


addLog("Download content");
	$content=array();
	$FNAME=$GLB_VAR['LINK']['FTP_INTERPRO'].'/release_notes.txt';
	if (!dl_file($FNAME,3,'release_notes.txt'))											failProcess($JOB_ID."005",'Unable to download file');


addLog("Process release note");
	$content=explode("\n",file_get_contents('release_notes.txt'));
	if (count($content)==1)																failProcess($JOB_ID."006",'Not enought lines');

	/// Line 5 should contain the release date
	$line=array_values(array_filter(explode(" ",$content[4])));
	///
	if (count($line)!=5)																failProcess($JOB_ID."007",'Unexpected line format');

addLog("Validate release note");
	$NEW_RELEASE=substr($line[1],0,strpos($line[1],','));
	echo $NEW_RELEASE."\n";
	$pos=strpos($NEW_RELEASE,',');
	if ($pos!==false)$NEW_RELEASE=substr($NEW_RELEASE,0,$pos);
	if (!is_numeric($NEW_RELEASE))														failProcess($JOB_ID."008",'Unexpected version format');

	/// Remove the file
	if (!unlink('release_notes.txt'))													failProcess($JOB_ID."009",'Unable to delete release_notes.txt');

addLog("Get current release date for INTERPRO");
	$CURR_RELEASE=getCurrentReleaseDate('INTERPRO',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($CURR_RELEASE == $NEW_RELEASE) successProcess('VALID');
	

addLog("Update release tag for INTERPRO");
	updateReleaseDate($JOB_ID,'INTERPRO',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Creating the new directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."010",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."011",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."012",'Unable to create new process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();
	
successProcess();

?>

