<?php


/**
 SCRIPT NAME: ck_go_rel
 PURPOSE:     Check for new Gene Ontology release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_go_rel';


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
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	if (!isset($GLB_VAR['LINK']['FTP_GO']))												failProcess($JOB_ID."004",'FTP_GO path no set');
	if (!isset($GLB_VAR['LINK']['REL_LINK_GO']))										failProcess($JOB_ID."005",'REL_LINK_GO path no set');
	


addLog("Download release note");
	exec('wget -q -O - '.$GLB_VAR['LINK']['REL_LINK_GO'],$raw_info,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."006",'Unable to download release notes');

addLog("Process release note");
	$info=json_decode(implode("\n",$raw_info),true);
	if ($info===NULL || $info===false)													failProcess($JOB_ID."007",'Unable to extract release notes');

addLog("Validate release note");
	$NEW_RELEASE=$info['date'];

	$tab2=explode("-",$NEW_RELEASE);
	if ($tab2[0]!=date("Y") && $tab2[0]!=(date("Y")-1))									failProcess($JOB_ID."008",'Unexpected year format');
	


	
addLog("Download License");
	
	if (is_file($W_DIR.'/LICENSE') && !unlink($W_DIR.'/LICENSE'))						failProcess($JOB_ID."009",'Unable to delete LICENSE');
	if (!dl_file('http://geneontology.org/docs/go-citation-policy/',3,'LICENSE'))		failProcess($JOB_ID."010",'Unable to download license file');
	
	/// First time: set it as the license
	if (!is_file('CURRENT_LICENSE'))
	{
		rename('LICENSE','CURRENT_LICENSE');
	}
	else 
	{
		addLog("Compare License");
		$file=file_get_contents($W_DIR.'/LICENSE');
		$VALID=true;
		if (strpos($file,'Creative Commons Attribution 4.0 Unported License')===false)
		{
			$VALID=false;
		}
		if (!unlink($W_DIR.'/LICENSE'))												failProcess($JOB_ID."013",'Unable to delete LICENSE');
		if (!$VALID)																	failProcess($JOB_ID."014",'License file is different');
		
	}	




addLog("Get current release date for " .$info['date']);
	$CURR_RELEASE=getCurrentReleaseDate('GO',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($CURR_RELEASE == $NEW_RELEASE) successProcess('VALID');
	

addLog("Update release tag for GO");
	updateReleaseDate($JOB_ID,'GO',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Redefining the working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."011",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."012",'Unable to find and create '.$W_DIR);
	/// Setting the directory with current date:
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."013",'Unable to create new process dir '.$W_DIR);

	/// Change to the new directory
	$PROCESS_CONTROL['DIR']=getCurrDate();

successProcess();

?>
