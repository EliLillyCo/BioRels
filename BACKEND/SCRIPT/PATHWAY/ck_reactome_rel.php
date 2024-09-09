<?php


/**
 SCRIPT NAME: ck_reactome_rel
 PURPOSE:     Check for Reactome release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_reactome_rel';


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

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);

	/// Check if the FTP_REACTOME path is set
	if (!isset($GLB_VAR['LINK']['FTP_REACTOME']))										failProcess($JOB_ID."004",'FTP_REACTOME path no set');

	


addLog("Download content");
	/// Get the index.html from the FTP page:
	$content=array();
	exec('wget --no-check-certificate -O - "'.$GLB_VAR['LINK']['FTP_REACTOME'].'"',$content,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."005",'Unable to download file');

	$NEW_RELEASE='';
	foreach ($content as $line)
	{
		/// Based on ReactomePathways.txt, find the release date
		if (strpos($line,'ReactomePathways.txt')===false)continue;
		if (!preg_match("/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/",$line,$info)) continue;
		$NEW_RELEASE=$info[0];
	}


	if ($NEW_RELEASE=='')																failProcess($JOB_ID."006",'Unable to find date'); 


addLog("Validate release note");
	///Ensure it's a date - we could probably do that with regex ...
	$tab2=explode("-",$NEW_RELEASE);

	if ($tab2[0]!=date("Y") && $tab2[0]!=(date("Y")-1))									failProcess($JOB_ID."007",'Unexpected year format');
	if ($tab2[1]<1 || $tab2[1]>12)														failProcess($JOB_ID."008",'Unexpected month format');
	if ($tab2[2]<1 || $tab2[1]>31)														failProcess($JOB_ID."009",'Unexpected day format');

addLog("Get current release date for REACTOME");
	$CURR_RELEASE=getCurrentReleaseDate('REACTOME',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($CURR_RELEASE == $NEW_RELEASE) successProcess('VALID');
	

addLog("Update release tag for REACTOME");
	updateReleaseDate($JOB_ID,'REACTOME',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."010",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."011",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."012",'Unable to create new process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();

successProcess();

?>
