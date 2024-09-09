<?php


/**
 SCRIPT NAME: ck_omim_rel
 PURPOSE:     Check for new OMIM  release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_omim_rel';


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

	/// Check for variables FTP_OMIM and FTP_OMIM_STATIC in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_OMIM']))											failProcess($JOB_ID."004",'FTP_OMIM path no set');
	if (!isset($GLB_VAR['LINK']['FTP_OMIM_STATIC']))									failProcess($JOB_ID."005",'FTP_OMIM_STATIC path no set');

	

addLog("Download content");
	/// We can get the latest data by downloading the mim2gene.txt file and looking at the date

	$content=array();
	$FNAME=$GLB_VAR['LINK']['FTP_OMIM_STATIC'].'/mim2gene.txt';
	exec('wget "'.$FNAME.'"',$content,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID.'006','Unable to download file');
	$content=array();
	exec('grep "Generated:" mim2gene.txt',$content);
	if (count($content)!=1)																failProcess($JOB_ID."007",'Unable to verify date');
	$NEW_RELEASE='';
	if (!preg_match("/([0-9]{0,4}\-[0-9]{0,3}\-[0-9]{0,3})/",$content[0],$info))		failProcess($JOB_ID."008",'Unable to find date');
	$NEW_RELEASE=$info[0];
	
	if ($NEW_RELEASE=='')																failProcess($JOB_ID."009",'Unable to find date'); 


addLog("Get current release date for OMIM");
	$CURR_RELEASE=getCurrentReleaseDate('OMIM',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($CURR_RELEASE == $NEW_RELEASE)
	{
		if (!unlink('mim2gene.txt'))													failProcess($JOB_ID."010",'Unable to delete mim2gene.txt');
		successProcess('VALID');
	}
	

addLog("Update release tag for OMIM");
	updateReleaseDate($JOB_ID,'OMIM',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	
	///Set up the working directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."011",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."012",'Unable to find and create '.$W_DIR);
	/// Based on current date:
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."013",'Unable to create new process dir '.$W_DIR);

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=getCurrDate();

	/// Check the archive dir
	if (!rename('mim2gene.txt',$W_DIR.'/mim2gene.txt'))								 	failProcess($JOB_ID."014",'Unable to move eco.owl to '.$W_DIR);



	
successProcess();

?>
