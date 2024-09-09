<?php

/**
 SCRIPT NAME: dl_ot_g
 PURPOSE:     Download all open targets genetics files
 
*/
$JOB_NAME='dl_ot_g';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];

addLog("Create directory");
	///	get Parent info:
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_ot_g_rel')];

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Check if the FTP_OPEN_TARGETS_G path is set
	if (!isset($GLB_VAR['LINK']['FTP_OPEN_TARGETS_G']))									failProcess($JOB_ID."005",'FTP_OPEN_TARGETS_G path no set');
	$CURR_RELEASE=getCurrentReleaseDate('OPEN_TARGET_G',$JOB_ID);
	

	 /// List of all evidence files to be downloaded:
	 $EVIDENCE_LIST=array('v2d','v2g','l2g','d2v2g','v2d_coloc','v2d_credset','v2g_scored','variant-index','lut','manhattan','OTGenetics.tsv.gz');

	///Define the weblink:
	 $PATH=$GLB_VAR['LINK']['FTP_OPEN_TARGETS_G'].'/'.$CURR_RELEASE.'/';

	if (is_file('index.html') && !unlink('index.html'))	 								failProcess($JOB_ID."006",'Unable to delete index.html');

	 if (!dl_file($PATH,3,'index.html'))	 											failProcess($JOB_ID."007",'Unable to download index.html');
	 $fpB=fopen('index.html','r');if (!$fpB) 											failProcess($JOB_ID."008",'Unable to open index.html');
	 

	/// Reading the index.html file to get the list of files to download
	 while(!feof($fpB))
	 {
		 $line=stream_get_line($fpB,10000,"\n");
		 $tab=explode(">",$line);
		 if (!isset($tab[5]))continue;
		 
		 $name=explode('"',$tab[5])[1];
		 $pos=strpos($name,'=');
		 $source_name=substr($name,$pos,-1);
		 echo $source_name."\n";

		 /// Not all files are needed, so we only download the ones that are in the list
		 if (!in_array($source_name,$EVIDENCE_LIST))continue;
		 
		 /// Download the file
		 downloadFTPFile($PATH.'/'.$name,$source_name,'.parquet');

	}
	 
	
successProcess();

?>

