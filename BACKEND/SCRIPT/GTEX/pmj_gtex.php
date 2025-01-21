<?php

/**
 SCRIPT NAME: pmj_gtex
 PURPOSE:     Prepare the jobs for gene expression
 				- Get the list of genes and transcripts
 				- Create the jobs
 
*/

/// Job name - Do not change
$JOB_NAME='pmj_gtex';


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



addLog("Check directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_gtex_rel')];

	/// Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR))																failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if ( !chdir($W_DIR))																failProcess($JOB_ID."002",'Unable to access '.$W_DIR);

	/// Find the run script:
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_gtex.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."003",$RUNSCRIPT.' file not found');

	
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Generate scripts");
	/// Get the list of genes
	$res=runQuery("SELECT DISTINCT gene_seq_id FROM rna_Gene");if ($res===false)		failProcess($JOB_ID."004",'Unable to get rna gene');
	$LIST_GENES=array();
	foreach ($res as $line)$LIST_GENES[]="GENE\t".$line['gene_seq_id'];

	/// Get the list of transcripts
	$res=runQuery("SELECT DISTINCT TRANSCRIPT_ID FROM RNA_TRANSCRIPT");if ($res===false) failProcess($JOB_ID."005",'Unable to get rna transcript');
	foreach ($res as $line)$LIST_GENES[]="TRANSCRIPT\t".$line['transcript_id'];
	
	
	
addLog("Save list genes");
	$fp=fopen('LIST_GENE_SPLIT','w');if (!$fp)											failProcess($JOB_ID."006",'Unable to get rna gene');
	fputs($fp,implode($LIST_GENES,"\n")."\n");
	fclose($fp);

	
	addLog("Create jobs");
	/// Create a master script
	$N_JOB=200;
	if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')$N_JOB=1;
	$COMMANDS=array();
	/// Create the jobs
	for($I=1;$I<=$N_JOB;++$I)
	{
		$COMMANDS[$I]='biorels_php '.$RUNSCRIPT.' '.$I.' '.$N_JOB.' &> LOG_'.$I;	
	}
	prepare_batch($COMMANDS,$W_DIR);


	successProcess();

?>

