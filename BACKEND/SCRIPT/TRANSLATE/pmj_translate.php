<?php
/*
 SCRIPT NAME: pmj_translate
 PURPOSE:     Prepare script for Transcript to protein translation
 
*/
ini_set('memory_limit','1000M');

/// Job name - Do not change
$JOB_NAME='pmj_translate';

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
	/// Defines working directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	 if ( !chdir($W_DIR)) 																failProcess($JOB_ID."004",'Unable to access new process dir '.$W_DIR);
	
	 /// Update process control date so the next process knows where to work:
	$PROCESS_CONTROL['DIR']=getCurrDate();

	/// Checking for the presence of setenv.sh which has the configuration info:
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."005",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."006",'SCRIPT_DIR not found ');
	
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."007",'Setenv file not found ');
	

	/// Checking for the script computing the translation:
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_translate.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."008",$RUNSCRIPT.' file not found');
	

	/// Checking for the job array script:
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."009",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."010",'JOBARRAY file NOT FOUND '.$JOBARRAY);


addLog("Working directory: ".$W_DIR);
	
	/// We search for all genes for which their is a uniprot record
	/// with external identifiers from Ensembl or RefSeq
	/// So we can create a file listing Ensembl/Refseq <-> uniprot pairs
	/// This will be used to augment the list of translation
	$query='SELECT DISTINCT gene_id 
			FROM gn_entry ge, gn_prot_map gpm, prot_seq ps, prot_extdb_map pem, prot_extdb pe 
			where ps.prot_seq_id = pem.prot_seq_id 
			AND pem.prot_extdb_id = pe.prot_extdbid 
			AND prot_extdbabbr IN (\'Ensembl\',\'RefSeq\') 
			AND ge.gn_entry_id = gpm.gn_entry_id 
			AND gpm.prot_entry_id = ps.prot_entry_id';
	$res=runQuery($query);
	if ($res===false)																	failProcess($JOB_ID."011",'Unable to get the list of genes');
	$list_g=array();
	foreach ($res as $line)$list_g[]=$line['gene_id'];
	
	
	$CHUNKS=array_chunk($list_g,500);
	echo "TOTAL NUMBER OF GENES : ".count($list_g)."\n";
	
	$fp=fopen('uniprot_mapping.csv','w');if (!$fp)										failProcess($JOB_ID."012",'Unable to open uniprot_mapping.csv');
	fputs($fp,"GENE_ID\tISO_ID\tTRANSCRIPT_INFO\n");
	
	foreach ($CHUNKS as $NC=>$CHUNK)
	{
		echo $NC."/".count($CHUNKS)."\t";
		/// Some mRNA<->protein association are already provided by RefSeq and ensembl, so we are going to list them
		$query='SELECT gene_id, iso_id, prot_extdb_value 
		FROM gn_entry ge, gn_prot_map gpm, prot_seq ps, prot_extdb_map pem, prot_extdb pe 
		where ps.prot_seq_id = pem.prot_seq_id 
		AND pem.prot_extdb_id = pe.prot_extdbid 
		AND prot_extdbabbr IN (\'Ensembl\',\'RefSeq\')
		AND ge.gn_entry_id = gpm.gn_entry_id 
		AND gpm.prot_entry_id = ps.prot_entry_id 
		AND gene_id IN ('.implode(',',$CHUNK).')
		ORDER BY gene_id ASC';
		$res=runQuery($query);
		echo count($res)."\t";
		if ($res===false)																	failProcess($JOB_ID."013",'Unable to fetch uniprot mapping');
		$NV=0;
		foreach ($res as $line)
		{
			if (!checkRegex($line['prot_extdb_value'],"TRANSCRIPT"))continue;
			
			++$NV;
			fputs($fp,$line['gene_id']."\t".$line['iso_id']."\t".$match[0]."\n");
			
		}
		echo $NV."\n";
		
	 }
	 fclose($fp);
	 
	 /// Opening master script:
	$fpA=fopen("all.sh",'w'); if(!$fpA)													failProcess($JOB_ID."014",'Unable to open all.sh');
	if (!is_dir("jobs") && !mkdir("jobs"))												failProcess($JOB_ID."015",'Unable to create jobs directory');
	if (!is_dir("DATA") && !mkdir("DATA"))												failProcess($JOB_ID."016",'Unable to create jobs directory');
	
	
	$N_JOB=100;
	
	for($I=0;$I<$N_JOB;++$I)
	{
		$JOB_NAME="jobs/job_".$I.".sh";
		
		///Create individual job file
		$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."017",'Unable to open jobs/job_'.$I.'.sh');
		
		/// Add the job to the master script:
		fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");

		fputs($fp,'#!/bin/sh'."\n");
		fputs($fp,"source ".$SETENV."\n"); /// Define the environment
		fputs($fp,'cd '.$W_DIR."\n");	 /// Go to working directory
		//Call the script
		fputs($fp,'biorels_php '.$RUNSCRIPT.' '.$I.' &> LOG_'.$I."\n");
		/// Return the status of the script to ensure it was run successfully:
		fputs($fp,'echo $? > status_'.$I."\n");
		fclose($fp);
	}
	fclose($fpA);

successProcess();

?>
