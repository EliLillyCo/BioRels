<?php
/*
 SCRIPT NAME: pmj_gene_edit
 PURPOSE:     Prepare script for gene editing
 
*/
ini_set('memory_limit','1000M');
$JOB_NAME='pmj_xray';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];

addLog("Create directory");
	
	
$CK_INFO=$GLB_TREE[getJobIDByName('ck_xray_rel')];
$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
											   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."005",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."006",'SCRIPT_DIR not found ');
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."007",'Setenv file not found ');
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_xray.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."008",$RUNSCRIPT.' file not found');
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."009",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];if (!checkFileExist($JOBARRAY))			failProcess($JOB_ID."010",'JOBARRAY file NOT FOUND '.$JOBARRAY);
echo $W_DIR."\n";
	




	/// Her we are searching for all entries for all non finished entries
	$res=runQuery("SELECT DISTINCT FULL_COMMON_NAME,MV,XR_ENTRY_ID,STATUS_VALUE,XR_JOB_NAME 
					FROM (
		SELECT  FULL_COMMON_NAME,MV, XR.XR_ENTRY_ID,XJ.STATUS_VALUE,XT.XR_JOB_NAME 
		FROM XR_ENTRY XR,XR_JOBS XT, XR_STATUS XJ, 
			(SELECT MAX(XR_JOB_ID) MV, XR_ENTRY_ID 
			 FROM XR_STATUS GROUP BY XR_ENTRY_ID) V
			WHERE V.XR_ENTRY_ID = XJ.XR_ENTRY_ID 
			AND  XJ.XR_JOB_ID = XT.XR_JOB_ID 
			AND V.MV = XJ.XR_JOB_ID 
			AND STATUS_VALUE='OK' 
			AND XR.XR_ENTRY_ID = XJ.XR_ENTRY_ID ) t
			WHERE XR_JOB_NAME!='blastp_load' ");
	if ($res===false)																			failProcess($JOB_ID."011",'Unable to create jobs directory');
	

	
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))												failProcess($JOB_ID."012",'Unable to create jobs directory');
	$fpA=fopen("SCRIPTS/all.sh",'w'); if(!$fpA)													failProcess($JOB_ID."013",'Unable to open all.sh');
	$fpL=fopen("LIST_TO_PROCESS",'w');if(!$fpL)													failProcess($JOB_ID."014",'Unable to open LIST_TO_PROCESS');
	foreach ($res as $line) 
	{
		++$I;
		$PDB=$line['full_common_name'];
		$JOB_NAME="SCRIPTS/job_".$PDB.".sh";
		$fp=fopen($JOB_NAME,"w");if(!$fpA)												failProcess($JOB_ID."015",'Unable to open jobs/job_'.$I.'.sh');
		fputs($fpL,$PDB."\n");
		fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");
		fputs($fp,'#!/bin/sh'."\n");
		fputs($fp,"source ".$SETENV."\n");
		fputs($fp,'cd '.$W_DIR."\n");
		fputs($fp,'biorels_php '.$RUNSCRIPT.' '.$PDB.' &> SCRIPTS/LOG_'.$PDB."\n");
		fputs($fp,'echo $? > SCRIPTS/status_'.$PDB."\n");
		fclose($fp);
	}
	fclose($fpL);
	fclose($fpA);

successProcess();

?>


