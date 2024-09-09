<?php

/**
 SCRIPT NAME: dl_gene
 PURPOSE:     Download gene files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_gene';


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
	
	///Since this is the first script for the GENE process, it is not referred to another script to get the directory but will rather create the directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);

	/// by getting the current date
	$W_DIR.=getCurrDate();			   		   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												  if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	echo $W_DIR."\n";
	$PROCESS_CONTROL['DIR']=getCurrDate();



addLog("Download Gene file");
///We check that we have the ftp weblink
	if (!isset($GLB_VAR['LINK']['FTP_NCBI']))											failProcess($JOB_ID."005",'FTP_NCBI_GENE path no set');
	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/gene/DATA/gene_info.gz',3))				failProcess($JOB_ID."006",'Unable to download archive');


addLog("Untar archive");
	if (!ungzip('gene_info.gz'))														failProcess($JOB_ID."007",'Unable to extract archive');

addLog("File check");
	if (!validateLineCount('gene_info',24000000))										failProcess($JOB_ID."008",'gene_info is smaller than expected'); 

addLog("Download and Extract Gene History");
	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/gene/DATA/gene_history.gz',3)) 			failProcess($JOB_ID."009",'Unable to download gene history archive');
	if (!ungzip('gene_history.gz'))														failProcess($JOB_ID."010",'Unable to extract archive');
	if (!validateLineCount('gene_history',11305361))									failProcess($JOB_ID."011",'gene_history smaller than expected');

addLog("Download and Extract Gene Ensembl Mapping");
	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/gene/DATA/gene2ensembl.gz',3)) 			failProcess($JOB_ID."012",'Unable to download gene2ensembl archive');
	if (!ungzip('gene2ensembl.gz'))														failProcess($JOB_ID."013",'Unable to extract archive');
	if (!validateLineCount('gene2ensembl',2500000))										failProcess($JOB_ID."014",'gene2ensembl smaller than expected');


successProcess();

?>

