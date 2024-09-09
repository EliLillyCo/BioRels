<?php

/**
 SCRIPT NAME: ck_gtex_rel
 PURPOSE:     This script doesn't really check for gtex release.
			  It requires a Version number as argument and updates the release date in the database
			  The version required is 8 for the version that will works with the current code
			  If you need to update the code to work with a new version, you will need to update the code in dl_gtex
 

The data used for the analyses described  were obtained from  the GTEx Portal,  dbGaP accession number phs000424.vN.pN

*/

/// Job name - Do not change
$JOB_NAME='ck_gtex_rel';


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


if (!isset($argv[1])) die("php ck_gtex_rel.php 8\nGTEX Version required. Before running this script, please update the paths in dl_gtex");
$NEW_RELEASE=$argv[1];


addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('GTEX',$JOB_ID);


addLog("Compare release");
	if ($CURR_RELEASE == $NEW_RELEASE){	successProcess('VALID');}
	
	


addLog("Update release tag");
	updateReleaseDate($JOB_ID,'GTEX',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	/// Based on the current date
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);

	/// Set process control directory to current date so the next script can access it
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>