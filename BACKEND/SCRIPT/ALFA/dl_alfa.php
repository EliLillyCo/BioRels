<?php

/**
 SCRIPT NAME: dl_alfa
 PURPOSE:     Download all alfa files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_alfa';

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
	/// GEt parent info
	$CK_alfa_INFO=$GLB_TREE[getJobIDByName('ck_alfa_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_alfa_INFO['DIR'].'/';if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_alfa_INFO['TIME']['DEV_DIR'];	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_alfa_INFO['TIME']['DEV_DIR'];;

addLog("Working directory: ".$W_DIR);

	///Check FTP path:
	if (!isset($GLB_VAR['LINK']['FTP_DBSNP']))											failProcess($JOB_ID."005",'FTP_DBSNP path no set');

	if (checkFileExist('freq.vcf')) successProcess();


addLog("Download alfa");
	$WLINK=$GLB_VAR['LINK']['FTP_DBSNP'].'/population_frequency/latest_release/';
	
	
	/// Download ALFA frequency file:
	if (!checkFileExist('freq.vcf.gz') &&
	!dl_file($WLINK.'/freq.vcf.gz',3,'freq.vcf.gz'))									failProcess($JOB_ID."006",'Unable to download freq.vcf.gz');
	
	/// Download hash:
	if (!checkFileExist('freq.vcf.gz.md5') &&
	!dl_file($WLINK.'/freq.vcf.gz.md5',3,'freq.vcf.gz.md5'))							failProcess($JOB_ID."007",'Unable to download freq.vcf.gz.md5');
	
	/// Compare hash:
	/// The md5 file contains the hash and the file name, so we need to extract the hash only
	if (md5_file('freq.vcf.gz') != explode(" ",file_get_contents('freq.vcf.gz.md5'))[0])failProcess($JOB_ID."008",'md5 hash different');
	
	/// Remove hash file and extract archive:
	if (!unlink('freq.vcf.gz.md5'))														failProcess($JOB_ID."009",'Unable to remove freq.vcf.gz.md5');
	if (!ungzip('freq.vcf.gz'))															failProcess($JOB_ID."010",'Unable to extract archive');
	
successProcess();

?>
