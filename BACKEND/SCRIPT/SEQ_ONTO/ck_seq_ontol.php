<?php

/**
 SCRIPT NAME: ck_seq_ontol
 PURPOSE:     Check for new release of the sequence ontology
 
*/
$JOB_NAME='ck_seq_ontol';

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
	
	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	/// Checking the ftp path
	if (!isset($GLB_VAR['LINK']['FTP_SEQ_ONTO']))										failProcess($JOB_ID."004",'FTP_SEQ_ONTO path no set');


	/// License terms are found in the README on github
	if (!isset($GLB_VAR['LINK']['FTP_SEQ_ONTO_GT']))									failProcess($JOB_ID."005",'FTP_SEQ_ONTO_G path no set');
	if (is_file($W_DIR.'/README.md'))unlink($W_DIR.'/README.md');

	/// So we download the file
	if (!dl_file($GLB_VAR['LINK']['FTP_SEQ_ONTO_GT'].'/README.md',3))					failProcess($JOB_ID."006",'Unable to download README');

	/// Grep the license
	exec('grep "This work is licensed under the Creative Commons Attribution-ShareAlike 4.0 International License. To view a copy of this license," README.md',$res);
	
	if (count($res)==0)																	failProcess($JOB_ID."007",'License is different');
	

	/// Download the sequence ontology file to get the release date
	if (!dl_file($GLB_VAR['LINK']['FTP_SEQ_ONTO'].'/so.obo',3))							failProcess($JOB_ID."008",'Unable to download archive');
	
	
	

addLog("Process release note");
	/// Read the file to get the version
	$fp=fopen('so.obo','r')	;if (!$fp)													failProcess($JOB_ID."009",'Unable to open so.obo');
	$NEW_RELEASE='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");///Getting the next line
		if (strpos($line,'data-version:')===false)continue; /// Checking if that line contains data-version:
		$NEW_RELEASE=explode(" ",$line)[1];
		break;
	}
	fclose($fp);


addLog("Validate release note");
	$tab2=explode("-",$NEW_RELEASE);
	if ($tab2[0]!=date("Y") && $tab2[0]<(date("Y")-3))									failProcess($JOB_ID."010",'Unexpected year format '. $NEW_RELEASE);
	

addLog("Get current release date");
		///Fetching from the database the current version of the data source
	$CURR_RELEASE=getCurrentReleaseDate('SEQ_ONTO',$JOB_ID);


addLog("Compare release");
	/// When it's the same, we don't want to keep the file, so we remove it and say the process is VALID (not SUCCESS)
	//// Meaning nothing to do
	//// This also exit the script
	if ($CURR_RELEASE == $NEW_RELEASE)
	{
		if (!unlink($W_DIR.'/so.obo'))												failProcess($JOB_ID."011",'Unable to remove so.obo');
		successProcess('VALID');
	}


addLog("Update release tag");
	///Otherwise we update hte release state in the database
	updateReleaseDate($JOB_ID,'SEQ_ONTO',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';
	/// And we create the corresponding directory
	
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."012",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."013",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."014",'Unable to create new process dir '.$W_DIR);
	
	/// We move the file to the new directory
	if (!rename('so.obo',$W_DIR.'/so.obo'))											failProcess($JOB_ID."015",'Unable to move go.obo to '.$W_DIR);
	
	/// We update the process control directory path so that the next process can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>
