<?php

/**
 SCRIPT NAME: dl_drugbank
 PURPOSE:     Download all drubank files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_drugbank';

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


addLog("Create directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_drugbank_rel')];
	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID.'001','NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID.'002','Unable to find and create '.$W_DIR);
	/// Go to the directory set up by parent
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']		;	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID.'003','Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID.'004','Unable to access process dir '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Check if FTP_DRUGBANK path is set in CONFIG_GLOBAL
    if (!isset($GLB_VAR['LINK']['FTP_DRUGBANK']))										failProcess($JOB_ID.'005','FTP_DRUGBANK path no set');
	/// Check if DRUGBANK_LOGIN path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['DRUGBANK_LOGIN']))												failProcess($JOB_ID.'006','DRUGBANK_LOGIN not found');
	/// Check if DRUGBANK_LOGIN path is set in CONFIG_GLOBAL
	if ($GLB_VAR['DRUGBANK_LOGIN']=='N/A')												failProcess($JOB_ID.'007','DRUGBANK_LOGIN not set');

	/// Define the login
	$DRUGBANK_LOGIN=$GLB_VAR['DRUGBANK_LOGIN'];
	
	/// Check the format of the login
	$pos=strpos($DRUGBANK_LOGIN,':');if ($pos===false)									failProcess($JOB_ID.'008','Wrong format for DRUGBANK_LOGIN');
    $PWD=array(substr($DRUGBANK_LOGIN,0,$pos),substr($DRUGBANK_LOGIN,$pos+1));
	
	/// Get the current release
    $CURR_RELEASE=getCurrentReleaseDate('DRUGBANK',$JOB_ID);
    $rel=explode(";",$CURR_RELEASE);

addLog("Download drugbank files file");
	/// Download the file
	$query='wget --user '.$PWD[0].' --password '.$PWD[1].' -O file.zip '.$GLB_VAR['LINK']['FTP_DRUGBANK'].'/downloads/'.$rel[0];
	echo $query;
	exec($query,$res,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID.'009','Unable to download latest drugbank release');


addLog("Unzip archive");
	if ( !unzip('file.zip'))															failProcess($JOB_ID.'010','Unable to extract archive for ');
	

successProcess();

?>