<?php


ini_set('memory_limit','500M');

/**
 SCRIPT NAME: prd_mondo
 PURPOSE:     Move mondo files to production
 
*/

/// Job name - Do not change
$JOB_NAME='prd_mondo';


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
	$CK_INFO=$GLB_TREE[getJobIDByName('db_mondo_tree')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; if (!is_dir($W_DIR) ) 							failProcess($JOB_ID."004",'Unable to find new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
addLog("Working directory: ".$W_DIR);

addLog("Cleanup files");
	$FILES=array('disease_entry.csv',
				'disease_syn.csv',
				'disease_ext.csv',
				'disease_anatomy.csv');

				
	foreach($FILES as $f) if (file_exists($f) && !unlink($f))						failProcess($JOB_ID."006",'Unable to delete '.$f);


addLog("Push to prod");
	pushToProd();


	successProcess();


?>
