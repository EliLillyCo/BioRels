<?php
/*
 SCRIPT NAME: pmj_alfa
 PURPOSE:     Prepare script for alfa processing
 
*/
ini_set('memory_limit','2000M');


/// Job name - Do not change
$JOB_NAME='pmj_alfa';

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



addLog("Setting up");
	/// Get parent information:
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_dbsnp_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/DBSNP/';   	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if ( !chdir($W_DIR))				 	failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
	/// Check SCRIPT_DIR
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."004",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."005",'SCRIPT_DIR not found ');

	/// Check setenv.sh
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."006",'Setenv file not found ');

	// Check the script to run
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_alfa.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."007",$RUNSCRIPT.' file not found');

	/// Check JOBARRAY that allow to run multiple jobs
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."008",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."009",'JOBARRAY file NOT FOUND '.$JOBARRAY);


	addLog("Working directory: ".$W_DIR);

		/// Check the static file containing the ALFA studies
	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$JOB_INFO['DIR'];
	$ALFA_STUDY	  =$STATIC_DIR.'/ALFA_POP';
	if (!checkFileExist($ALFA_STUDY))											   		failProcess($JOB_ID."010",'Missing ALFA_STUDY setup file ');



	/// Find dbSNP-ALFA as a source
	$SOURCE_ID=getSource('dbSNP-ALFA');

	/// Review the different studies and add them 
	prepareStudies($SOURCE_ID,$ALFA_STUDY);

		/// Open the job array file
	$fpA=fopen("all_alfa.sh",'w'); if(!$fpA)											failProcess($JOB_ID."011",'Unable to open all.sh');
	
	if (!is_dir("jobs_alfa") && !mkdir("jobs_alfa"))									failProcess($JOB_ID."012",'Unable to create jobs directory');
	if (!is_dir("DATA_ALFA") && !mkdir("DATA_ALFA"))									failProcess($JOB_ID."013",'Unable to create DATA_alfa directory');

	for ($I=0;$I<500;++$I)
	{
		

		///Job name:
		$JOB_NAME="jobs_alfa/job_".$I.".sh";
		
		/// Open the job file
		$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."014",'Unable to open jobs/job_'.$I.'.sh');
		
		/// Add the job to the master script
		fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");
		
		/// Write the job script
		fputs($fp,'#!/bin/sh'."\n");
		fputs($fp,'cd '.$W_DIR."\n");/// Go to the working directory
		fputs($fp,"source ".$SETENV."\n");/// Load the environment
		fputs($fp,'biorels_php '.$RUNSCRIPT.' '.$I.'  &> LOG_ALFA_'.$I."\n");// Run the script
		fputs($fp,'echo $? > status_ALFA_'.$I."\n");/// Save the status
		fclose($fp);
	}
	fclose($fpA);

successProcess();





function prepareStudies($SOURCE_ID,$ALFA_STUDY)
{
	global $JOB_ID;
	/// The max PK for the variant_freq_study is not necessarily the max PK value for dbSNP-Alfa
	$PK=0;
	$res=runQuery("SELECT MAX(variant_Freq_study_id) co FROM variant_freq_study");
	if ($res===false)																failProcess($JOB_ID."A01",'Unable to get Max ID for variant_freq_study');
	$PK=($res[0]['co']=='')?0:$res[0]['co'];


	addLog("Verification of ALFA studies");
	/// Then we look for all studies associated to ALFA
	/// check if they are in the database and create them if necessary
	$res=runQuery("SELECT * 
					FROM variant_freq_study v
					where  v.source_id =".$SOURCE_ID); 
	if ($res===false)																failProcess($JOB_ID."A02",'Unable to get dbSNP alfa studies');
	$DB_STUDIES=array();
	
	foreach ($res as $line)
	{
		$DB_STUDIES[$line['variant_freq_study_name']]=$line['variant_freq_study_id'];
	}



	/// Open static file containing the studies
	$fp=fopen($ALFA_STUDY,'r');if (!$fp)																failProcess($JOB_ID."A03",'Unable to open ALFA_POP');
	$line=stream_get_line($fp,1000,"\n");
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		$tab=explode("\t",$line);
		if ($line=='')continue;
		/// We don't have that study yet? Let's insert it.
		if (isset($DB_STUDIES[$tab[3]]))continue;
		
		$PK++;
		
		$res=runQueryNoRes("INSERT INTO variant_freq_study(variant_freq_study_id, variant_freq_study_name, description, short_name,source_id)
		VALUES (".$PK.",'".$tab[3]."','".$tab[2]."','".$tab[0].' - '.$tab[1]."',".$SOURCE_ID.")");
		if ($res===false)																		failProcess($JOB_ID."A04",'Unable to insert new variant frequency study');
		

	}
	fclose($fp);
}
?>
