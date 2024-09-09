<?php



/**
 SCRIPT NAME: ck_eco_rel
 PURPOSE:     Check for new ECO ontology release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_eco_rel';

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

	///Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	/// Check if FTP_EBI_ECO path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_EBI_ECO']))										failProcess($JOB_ID."004",'FTP_EBI_ECO path no set');

	


addLog("Download content");

	/// Download the file
	$content=array();
	$FNAME=$GLB_VAR['LINK']['FTP_EBI_ECO'].'/eco.owl';
	exec('wget "'.$FNAME.'"',$content,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."005",'Unable to download file');
	
	/// Getting version
	$content=array();
	exec('grep "owl:versionIRI" eco.owl',$content);
	if (count($content)!=1)																failProcess($JOB_ID."006",'Unable to verify date');

	/// Getting date
	$NEW_RELEASE='';
	if (!preg_match("/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/",$content[0],$info))failProcess($JOB_ID."007",'Unable to find date');
	$NEW_RELEASE=$info[0];
	
	if ($NEW_RELEASE=='')																failProcess($JOB_ID."008",'Unable to find date'); 


addLog("Validate release note");
	$tab2=explode("-",$NEW_RELEASE);

	if ($tab2[0]!=date("Y") && $tab2[0]!=(date("Y")-1))									failProcess($JOB_ID."009",'Unexpected year format');
	if ($tab2[1]<1 || $tab2[1]>12)														failProcess($JOB_ID."010",'Unexpected month format');
	if ($tab2[2]<1 || $tab2[1]>31)														failProcess($JOB_ID."011",'Unexpected day format');

addLog("Get current release date for ECO");
	$CURR_RELEASE=getCurrentReleaseDate('ECO',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if ($CURR_RELEASE == $NEW_RELEASE){unlink('eco.owl'); successProcess('VALID');}
	

addLog("Update release tag for ECO");
	updateReleaseDate($JOB_ID,'ECO',$NEW_RELEASE);

	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."012",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."013",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."014",'Unable to create new process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();
addLog("Move file to ".$W_DIR.'/eco.owl');
	if (!rename('eco.owl',$W_DIR.'/eco.owl'))								 			failProcess($JOB_ID."015",'Unable to move eco.owl to '.$W_DIR);
successProcess();

?>
