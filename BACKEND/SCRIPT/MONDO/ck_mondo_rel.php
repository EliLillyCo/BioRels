<?php

/**
 SCRIPT NAME: ck_mondo_rel
 PURPOSE:     Check for new MONDO ontology release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_mondo_rel';


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

addLog("Check variables");

	/// Define working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);


	/// Check for variables
	if (!isset($GLB_VAR['LINK']['FTP_EBI_OBO']))										failProcess($JOB_ID."004",'FTP_EBI_OBO path no set');

	


addLog("Download content");
	

	/// Download mondo.owl
	$FNAME=$GLB_VAR['LINK']['FTP_EBI_OBO'].'/mondo.owl';
	if (!dl_file($FNAME))																failProcess($JOB_ID."005",'Unable to download file');
	
	/// Get the release date
	$content=array();
	exec('grep "owl:versionIRI" mondo.owl',$content);
	if (count($content)!=1)																failProcess($JOB_ID."006",'Unable to verify date');
	
	/// Extract the date
	$NEW_RELEASE='';
	if (!preg_match("/([0-9]{0,4}-[0-9]{0,3}-[0-9]{0,3})/",$content[0],$info))			failProcess($JOB_ID."007",'Unable to find date');
	
	$NEW_RELEASE=$info[0];
	if ($NEW_RELEASE=='')																failProcess($JOB_ID."008",'Unable to find date'); 

	/// Extract the license
	$content=array();
	exec('grep "terms:license" mondo.owl > LICENSE',$content,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."009",'Unable to generate LICENSE'); 
	


addLog("Compare License");


	if (!is_file('CURRENT_LICENSE'))
	{
		rename('LICENSE','CURRENT_LICENSE');
	}
	else 
	{
		$fp=fopen('LICENSE','r');		if (!$fp)										failProcess($JOB_ID."010",'Unable to open LICENSE');
		$fp2=fopen('CURRENT_LICENSE','r');if (!$fp2)									failProcess($JOB_ID."011",'Unable to open CURRENT_LICENSE');
		$VALID=true;
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			$line2=stream_get_line($fp2,10000,"\n");
			if ($line!=$line2){$VALID=false;break;}
		}
		fclose($fp);
		fclose($fp2);
		if (!unlink($W_DIR.'/LICENSE'))													failProcess($JOB_ID."012",'Unable to delete LICENSE');
		if (!$VALID)																	failProcess($JOB_ID."013",'License file is different');
	}	
	

addLog("Validate release note");
	
addLog("Get current release date for MONDO");
	$CURR_RELEASE=getCurrentReleaseDate('MONDO',$JOB_ID);
	
 addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
 	if ($CURR_RELEASE == $NEW_RELEASE)
	{
		if (!unlink('mondo.owl'))														failProcess($JOB_ID."014",'Unable to delete mondo.owl');
		successProcess('VALID');
	}
	

addLog("Update release tag for MONDO");
	updateReleaseDate($JOB_ID,'MONDO',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	///Redefined working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."015",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."016",'Unable to find and create '.$W_DIR);
	/// Create new directory
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."017",'Unable to create new process dir '.$W_DIR);

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=getCurrDate();

	/// Move the file to the new directory
	if (!rename('mondo.owl',$W_DIR.'/mondo.owl'))								 		failProcess($JOB_ID."018",'Unable to move mondo.owl to '.$W_DIR);



successProcess();

?>
