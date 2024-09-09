<?php

/**
 SCRIPT NAME: ck_clinvar_rel
 PURPOSE:     Check for new release of clinvar & license
 
*/

/// Job name - Do not change
$JOB_NAME='ck_clinvar_rel';

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
	/// Change to working directory
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);

	///Check if FTP_NCBI_CLINVAR path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_NCBI_CLINVAR']))									failProcess($JOB_ID."004",'FTP_SEQ_ONTO path no set');

	/// Download file - root of ftp site -> index.html
	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI_CLINVAR'].'/',3))								failProcess($JOB_ID."005",'Unable to download archive');
	
	
	

addLog("Process release note");
	/// Open the index.html file and search for the variant_summary.txt.gz.md5 file
	/// Then get the release date from the file name
	$fp=fopen('index.html','r')	;if (!$fp)											failProcess($JOB_ID."006",'Unable to open README');
	$NEW_RELEASE='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		if (strpos($line,'variant_summary.txt.gz.md5')===false)continue;
		preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})/',$line,$matches);
		
		$NEW_RELEASE=date('Y-m-d',strtotime($matches[0]));
		
		break;
	}
	fclose($fp);
		



addLog("Validate release note");
	/// Check if the release number is valid
	$tab2=explode("-",$NEW_RELEASE);
	if ($tab2[0]!=date("Y") && $tab2[0]!=(date("Y")-1))									failProcess($JOB_ID."007",'Unexpected year format');
	

addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('CLINVAR',$JOB_ID);


addLog("Compare release");
	if (!unlink($W_DIR.'/index.html')) 													failProcess($JOB_ID."008",'Unable to remove index.html');
	if ($CURR_RELEASE == $NEW_RELEASE){	successProcess('VALID');}
	
	


addLog("Update release tag");
	updateReleaseDate($JOB_ID,'CLINVAR',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';
	/// Create working directory based on today's date
	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."009",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."010",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."011",'Unable to create new process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();

?>
