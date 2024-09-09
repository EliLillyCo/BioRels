<?php

/**
 SCRIPT NAME: dl_${DATASOURCE}
 PURPOSE:     Process all ${DATASOURCE} files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_${DATASOURCE}';

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
	/// Get parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_${DATASOURCE}_rel')];
	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	$W_DIR.='/INPUT';							if (!is_dir($W_DIR) && !mkdir($W_DIR))	failProcess($JOB_ID."004",'Unable to create INPUT directory '.$W_DIR);


	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory: ".$W_DIR);

	/// Download your files
	
	if (!isset($GLB_VAR['LINK']['FTP_${DATASOURCE}']))										failProcess($JOB_ID."005",'FTP_${DATASOURCE} path no set');
	$LIST_FILES=array(
		// 'path'=>'file_name_to_store_file_as'
	);
	if ($LIST_FILES!=array())
	foreach($LIST_FILES as $F)
	{
		if (is_file($F[1]))continue;//Already downloaded
		if (!dl_file($GLB_VAR['LINK']['FTP_${DATASOURCE}'].'/'.$F[0],3,$F[1]))		failProcess($JOB_ID."010",'Unable to download file '.$F[1]);
	
	}

successProcess();



?>

