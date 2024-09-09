<?php

/**
 SCRIPT NAME: ck_livertox_rel
 PURPOSE:     Check for new Livertox release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_livertox_rel';


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


addLog("Setting up");
	///Define directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	/// Checking the FTP link is set
	if (!isset($GLB_VAR['LINK']['FTP_LIVERTOX']))										failProcess($JOB_ID."004",'FTP_GENEREVIEWS path no set');
	

	/// Removing old files
	if (checkFileExist('index.html') && !unlink('index.html'))							failProcess($JOB_ID."005",'Unable to delete index.html');

	/// Downloading the release notes
	if (!dl_file($GLB_VAR['LINK']['FTP_LIVERTOX'].'/',3))								failProcess($JOB_ID."006",'Unable to download release notes');
	
addLog("Process index.html");
	$fp=fopen('index.html','r');if (!$fp)												failProcess($JOB_ID."007",'Unable to open index.html');
	$FNAME='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
		if (strpos($line,'livertox_')!==false) 
		{
			
			$tab=array_values(array_filter(explode(" ",$line)));
			
			$NEW_RELEASE= $tab[2];
			$FNAME=explode('"',$tab[1])[1];
			echo $FNAME.'|'.$NEW_RELEASE."\n";
			
		}
	}
	
	fclose($fp);
	if (!unlink('index.html'))														failProcess($JOB_ID."005",'Unable to delete index.html');

addLog("Get current release date for LIVER TOX");
	$CURR_RELEASE=getCurrentReleaseDate('LIVER TOX',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);

	if ($CURR_RELEASE == $NEW_RELEASE) successProcess('VALID');
	


	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Creating the new directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."007",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."008",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."009",'Unable to create new process dir '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."010",'Unable to change to new process dir '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=getCurrDate();
	

	/// Download the file
	if (!dl_file($GLB_VAR['LINK']['FTP_LIVERTOX'].'/'.$FNAME,3))						failProcess($JOB_ID."011",'Unable to download file');
	

	/// Untar the file
	if (!untar($FNAME))																	failProcess($JOB_ID."012",'Unable to untar files');


	
addLog("Update release tag for GENE REVIEWS");
	updateReleaseDate($JOB_ID,'LIVER TOX',$NEW_RELEASE);


successProcess();
?>
