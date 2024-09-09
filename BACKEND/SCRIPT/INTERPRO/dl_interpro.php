<?php


error_reporting(E_ALL);
/**
 SCRIPT NAME: dl_interpro
 PURPOSE:     Download all interpro files/ 
 
*/

/// Job name - Do not change
$JOB_NAME='dl_interpro';


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



addLog("Set up directory");
	
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_interpro_rel')];


	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
											  if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];echo $W_DIR."\n";

	/// Check if FTP path is set
	if (!isset($GLB_VAR['LINK']['FTP_INTERPRO']))										failProcess($JOB_ID."005",'FTP_INTERPRO path no set');
	



 addLog("Download Interpro file");
	 if (!checkFileExist('interpro.xml'))
	 {
		$WEB_PATH=$GLB_VAR['LINK']['FTP_INTERPRO'].'/interpro.xml.gz';
		if (!dl_file($WEB_PATH,3,'interpro.xml.gz'))										failProcess($JOB_ID."006",'Unable to download archive');
		if (!ungzip('interpro.xml.gz'))														failProcess($JOB_ID."007",'interpro.xml.gz');
	 }

	addLog("Download Interpro tree");
		$WEB_PATH=$GLB_VAR['LINK']['FTP_INTERPRO'].'/ParentChildTreeFile.txt';
		if (!dl_file($WEB_PATH,3,'tree.txt'))												failProcess($JOB_ID."008",'Unable to ParentChildTreeFile');


	addLog("Download Interpro Prot");
		$WEB_PATH=$GLB_VAR['LINK']['FTP_INTERPRO'].'/protein2ipr.dat.gz';
		if (!dl_file($WEB_PATH,3,'protein2ipr.data.gz'))									failProcess($JOB_ID."009",'Unable to Protein2ipr');
		if (!ungzip('protein2ipr.data.gz'))													failProcess($JOB_ID."010",'Unable to ungzip interpro.xml.gz');

	addLog("Download match complete");
		$WEB_PATH=$GLB_VAR['LINK']['FTP_INTERPRO'].'/match_complete.xml.gz';
		if (!dl_file($WEB_PATH,3,'match_complete.xml.gz'))									failProcess($JOB_ID."011",'Unable to download match_complete');
		
		if (!ungzip('match_complete.xml.gz'))												failProcess($JOB_ID."012",'Unable to ungzip match_complete.xml.gz');

	successProcess();

?>
