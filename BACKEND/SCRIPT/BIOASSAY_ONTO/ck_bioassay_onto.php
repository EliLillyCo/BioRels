<?php
$JOB_NAME='ck_bioassay_onto';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');


$JOB_ID=getJobIDByName($JOB_NAME);

$PROCESS_CONTROL['DIR']='N/A';


addLog("Download release note");
	/// Get job info
	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	/// Create directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	/// Check if FTP_BAO path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_BAO']))											failProcess($JOB_ID."004",'FTP_BAO path no set');
	/// Download file
	if (!dl_file($GLB_VAR['LINK']['FTP_BAO'].'/bao_complete_merged.owl',3))				failProcess($JOB_ID."005",'Unable to download archive');
	
	
	

addLog("Process release note");

	/// Versionn info is the tag we search in the owl file
	exec('grep "owl:versionInfo" bao_complete_merged.owl',$content);
	if (count($content)!=1)																failProcess($JOB_ID."006",'Unable to verify date');
	
	/// It's a version info so we need to extract the date
	$NEW_RELEASE='';
	if (!preg_match("/([0-9]{0,4}\.[0-9]{0,3}\.[0-9]{0,3})/",$content[0],$info))		failProcess($JOB_ID."007",'Unable to find date');
	$NEW_RELEASE=$info[0];



addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('BIOASSAY',$JOB_ID);


addLog("Compare release");
	if ($CURR_RELEASE == $NEW_RELEASE)
	{
		/// Remove file since it's the same file
		if (!unlink($W_DIR.'/bao_complete_merged.owl'))									failProcess($JOB_ID."008",'Unable to remove bao_complete_merged.owl');
		successProcess('VALID');
	}

addLog("Compare License");
	$content=array();	
	
	/// We are going to extract the license information from the file
	/// We are going to compare it with the current license
	/// If it's different we are going to fail the process
	/// But first, if this file already exist (previous run that failed), we delete it
	if (is_file('LICENSE') && !unlink('LICENSE')) 										failProcess($JOB_ID."009",'Unable to remove LICENSE');
	
	/// Extract license
	exec('grep "dc:license" bao_complete_merged.owl|uniq > LICENSE',$content,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."010",'Unable to extract license');

	/// If the file doesn't exist we rename it to be the current license
	if (!is_file('CURRENT_LICENSE'))
	{
		rename('LICENSE','CURRENT_LICENSE');
	}
	else 
	{
		/// We read line by line the two files and compare them
		$fp=fopen('LICENSE','r');if (!$fp)												failProcess($JOB_ID."011",'Unable to open LICENSE');
		$fp2=fopen('CURRENT_LICENSE','r'); if (!$fp2)									failProcess($JOB_ID."012",'Unable to open CURRENT_LICENSE');
		$VALID=true;
		while(!feof($fp))
		{
			$line=trim(stream_get_line($fp,10000,"\n"));
			$line2=trim(stream_get_line($fp2,10000,"\n"));
		
			if ($line!=$line2){$VALID=false;break;}
		}
		fclose($fp);
		fclose($fp2);

		if (!unlink($W_DIR.'/LICENSE')) 												failProcess($JOB_ID."013",'Unable to remove LICENSE');
		if (!$VALID)																	failProcess($JOB_ID."014",'License file is different');
	}	

	/// If we get to that stage, the file is new and the license is valid

addLog("Update release tag");
	updateReleaseDate($JOB_ID,'BIOASSAY',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';
	/// So we create a new directory with the current date

	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."015",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."016",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."017",'Unable to create new process dir '.$W_DIR);
	
	/// We move the file to the new directory
	if (!rename('bao_complete_merged.owl',$W_DIR.'/bao_complete_merged.owl'))failProcess($JOB_ID."018",'Unable to move go.obo to '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>
