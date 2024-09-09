<?php
/**
 SCRIPT NAME: dl_surechembl
 PURPOSE:     Download SureChembl files
 
*/
$JOB_NAME='dl_surechembl';

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
	/// Get parent job info:
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_surechembl_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 							failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory: ".$W_DIR);

addLog("Download SureChembl file");

	/// To download the SureChembl files:
	/// we will need to download the root ftp to get the list of all the files first:
	if (is_file('index.html') && !unlink('index.html'))										failProcess($JOB_ID."005",'Unable to delete index.html');
	if (!isset($GLB_VAR['LINK']['FTP_SURECHEMBL']))											failProcess($JOB_ID."006",'FTP_SURECHEMBL path no set');
	if (!dl_file($GLB_VAR['LINK']['FTP_SURECHEMBL'],3))										failProcess($JOB_ID."007",'Unable to download index.html');

	/// Then read that root file and download all the files that are not in the current directory:
	$fp=fopen('index.html','r')	;if (!$fp)													failProcess($JOB_ID."008",'Unable to open index.html');
	$NEW_RELEASE=-1;$TIMEST='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		
		if (!preg_match('/(SureChEMBL_[0-9]{1,8}_[0-9]{1,3}.txt.gz)/',$line,$matches))continue;
		if (is_file(substr($matches[0],0,-3)))											continue;
		if (!is_file($matches[0]) && 
			!dl_file($GLB_VAR['LINK']['FTP_SURECHEMBL'].'/'.$matches[0],3))					failProcess($JOB_ID."009",'Unable to download file '.$matches[0]);
		if (!ungzip($matches[0]))															failProcess($JOB_ID."010",'Unable to ungzip  file '.$matches[0]);
		
	}
	fclose($fp);
	
	if (!unlink($W_DIR.'/index.html'))														failProcess($JOB_ID."011",'Unable to delete index.html');

addLog("Download SureChembl map file");
	/// Then we download the map files. For that we follow the same process,
	/// we will need to download the root ftp to get the list of all the files first:
	if (is_file('map') && !unlink('map'))													failProcess($JOB_ID."012",'Unable to delete map');
	if (!dl_file($GLB_VAR['LINK']['FTP_SURECHEMBL'].'map',3))								failProcess($JOB_ID."013",'Unable to download index.html');

	$fp=fopen('map','r');
	if (!$fp)																				failProcess($JOB_ID."014",'Unable to open index.html');
	$NEW_RELEASE=-1;$TIMEST='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		echo $line."\n";
		if (!preg_match('/(SureChEMBL_map_[0-9]{1,8}.txt.gz)/',$line,$matches))continue;
		if (is_file(substr($matches[0],0,-3)))											continue;
		if (!is_file($matches[0]) && 
			!dl_file($GLB_VAR['LINK']['FTP_SURECHEMBL'].'map/'.$matches[0],3))				failProcess($JOB_ID."015",'Unable to download file '.$matches[0]);
		if (!ungzip($matches[0]))															failProcess($JOB_ID."016",'Unable to ungzip  file '.$matches[0]);
	}
	fclose($fp);
	if (!unlink($W_DIR.'/map'))																failProcess($JOB_ID."017",'Unable to delete map');
	

	
successProcess();

?>
