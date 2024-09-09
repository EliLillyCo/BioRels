<?php
/**
 SCRIPT NAME: ck_chembl_rel
 PURPOSE:     Check for new release of chembl & license
 
*/

/// Job name - Do not change
$JOB_NAME='ck_chembl_rel';

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


addLog("Download release note");
	/// Define working directory in PROCESS
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);

	/// Remove index.html file if it already exists
	if (is_file('index.html'))unlink($W_DIR.'/index.html');

	/// Check if FTP_CHEMBL path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_CHEMBL']))											failProcess($JOB_ID."004",'FTP_CHEMBL path no set');

	/// Download file - which is the root ftp directory, which will be a index.html file
	if (!dl_file($GLB_VAR['LINK']['FTP_CHEMBL'],3))										failProcess($JOB_ID."005",'Unable to download index.html');
	
	
	

addLog("Process index.html");
	
	/// Open the index.html file and search for the release notes file
	$fp=fopen('index.html','r')	;if (!$fp)											failProcess($JOB_ID."006",'Unable to open index.html');
	$NEW_RELEASE='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		/// There should be a release note file, from whihc we extract the year
		if (!preg_match('/chembl_([0-9]{1,3})_release_notes.txt/',$line,$matches))continue;
		
		$tab=array_values(array_filter(explode(" ",$line)));
		$NEW_RELEASE=$matches[1];
		if (!is_numeric($NEW_RELEASE))												failProcess($JOB_ID."007",'Unexpected release format');
		foreach ($tab as $t)
		{
			$d=date_parse($t);
			if ($d['error_count']!=0||$d['year']==''||$d['month']==''||$d['day']=='')continue;
			$NEW_RELEASE.='-'.$d['year'].'-'.$d['month'].'-'.$d['day'];
		}
		
		
		break;
	}
	fclose($fp);
	if (!unlink($W_DIR.'/index.html'))												failProcess($JOB_ID."008",'Unable to remove index.html');



	
addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('CHEMBL',$JOB_ID);


addLog("Compare release");
	if ($CURR_RELEASE == $NEW_RELEASE)	successProcess('VALID');
	
addLog("Compare License");
	/// Downloading the license file
	if (!dl_file($GLB_VAR['LINK']['FTP_CHEMBL'].'/LICENSE',3))								failProcess($JOB_ID."009",'Unable to download license file');
	/// First time download -> we set it as the current license
	if (!is_file('CURRENT_LICENSE'))
	{
		rename('LICENSE','CURRENT_LICENSE');
	}
	else 
	{
		/// Otherwise we compare line by line
		$fp=fopen('LICENSE','r'); if (!$fp)												failProcess($JOB_ID."010",'Unable to open LICENSE');
		$fp2=fopen('CURRENT_LICENSE','r'); if (!$fp2)									failProcess($JOB_ID."011",'Unable to open CURRENT_LICENSE');
		$VALID=true;
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			$line2=stream_get_line($fp2,10000,"\n");
			if ($line!=$line2){$VALID=false;break;}
		}
		fclose($fp);
		fclose($fp2);
		if (!unlink($W_DIR.'/LICENSE'))													failProcess($JOB_ID."012",'Unable to remove LICENSE');
		if (!$VALID)																	failProcess($JOB_ID."013",'License file is different');
	}	


addLog("Update release tag");
	updateReleaseDate($JOB_ID,'CHEMBL',$NEW_RELEASE);


addLog("Create working directory");
	$PROCESS_CONTROL['DIR']='N/A';

	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."014",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."015",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."016",'Unable to create new process dir '.$W_DIR);
	
	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>
