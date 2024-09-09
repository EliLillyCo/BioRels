<?php

/**
 SCRIPT NAME: dl_clinvar
 PURPOSE:     Download all clinvar files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_clinvar';

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

addLog("Create directory");
	/// Get Parent info
	$CK_CLINVAR_INFO=$GLB_TREE[getJobIDByName('ck_clinvar_rel')];
	// Get the working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_CLINVAR_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();			   		   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();

	/// Check if FTP_NCBI_CLINVAR path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_NCBI_CLINVAR']))									failProcess($JOB_ID."005",'FTP_NCBI_CLINVAR path no set for '.$FILE_NAME);
	

addLog("Download Clinvar files file");
	/// List of files to download
	$LIST_FILES=array(
		'allele_gene.txt.gz',
		'cross_references.txt',
		'submission_summary.txt.gz',
		'summary_of_conflicting_interpretations.txt',
		'var_citations.txt',
		'variant_summary.txt.gz',
		'variation_allele.txt.gz');

	foreach ($LIST_FILES as $FILE_NAME)
	{
		/// Download the file
		if (!dl_file($GLB_VAR['LINK']['FTP_NCBI_CLINVAR'].'/'.$FILE_NAME,3))				failProcess($JOB_ID."006",'Unable to download archive for '.$FILE_NAME);
		/// Download the md5 file
		if (!dl_file($GLB_VAR['LINK']['FTP_NCBI_CLINVAR'].'/'.$FILE_NAME.'.md5',3))			failProcess($JOB_ID."007",'Unable to download md5 for '.$FILE_NAME);
		/// Check the md5 hash
		if (md5_file($FILE_NAME) != explode(" ",file_get_contents($FILE_NAME.'.md5'))[0])	failProcess($JOB_ID."008",'md5 hash different for '.$FILE_NAME);
		/// Remove the md5 file
		if (!unlink($FILE_NAME.'.md5'))														failProcess($JOB_ID."009",'Unable to remove md5 file for '.$FILE_NAME);
		/// Extract the file
		if (substr($FILE_NAME,-3)=='.gz' && !ungzip($FILE_NAME))							failProcess($JOB_ID."010",'Unable to extract archive for '.$FILE_NAME);
	}

successProcess();

?>
