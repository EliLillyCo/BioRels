<?php

/**
 SCRIPT NAME: pp_seq_sim
 
*/
ini_set('memory_limit','5000M');
$JOB_NAME='pmj_transcriptome';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);


addLog("Create directory");
	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];						if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	
	$CK_INFO=$GLB_TREE[getJobIDByName('db_dna')];
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];							if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];			if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	echo $W_DIR."\n";




 

	$W_UNK_GENES=false;
	if ($GLB_VAR['TRANSCRIPTOME_W_UNK_GENES']=='Y')$W_UNK_GENES=true;
	$W_LOC_GENES=false;
	if ($GLB_VAR['TRANSCRIPTOME_W_LOC_GENES']=='Y')$W_LOC_GENES=true;
	$query="SELECT transcript_id, g.gn_entry_id FROM  transcript t,gene_seq gs
	LEFT JOIN gn_entry  g on g.gn_entry_id = gs.gn_entry_id 
	 where t.gene_seq_id = gs.gene_seq_id ";
	if (!$W_LOC_GENES) $query .= " AND symbol NOT LIKE 'LOC%' ";
	if (!$W_UNK_GENES) $query .= " AND gs.gn_entry_id is not null ";


	
	$query.=" ORDER BY g.gn_entry_id ASC";
	$res=runQuery($query);
	
	$INPUT_DATA=array();
	foreach ($res as $line)
	{
		$INPUT_DATA[$line['gn_entry_id']][]=$line['transcript_id'];
	}
	$N_JOBS=200;
	$N_PER_JOB=ceil(count($res)/$N_JOBS);


	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."005",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."006",'SCRIPT_DIR not found ');
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."007",'Setenv file not found ');
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_transcriptome.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."008",$RUNSCRIPT.' file not found');
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."009",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."010",'JOBARRAY file NOT FOUND '.$JOBARRAY);
echo $W_DIR."\n";
	
	
	
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))										failProcess($JOB_ID."011",'Unable to create SCRIPTS directory');
	if (!is_dir("RESULTS") && !mkdir("RESULTS"))												failProcess($JOB_ID."012",'Unable to create jobs directory');
	$fpA=fopen("SCRIPTS/all.sh",'w'); if(!$fpA)											failProcess($JOB_ID."013",'Unable to open all.sh');
	
	
	for($I=0;$I<$N_JOBS;++$I)
	{
		
		$JOB_NAME="SCRIPTS/job_".$I.".sh";
		$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."014",'Unable to open jobs/job_'.$I.'.sh');
		fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");
		fputs($fp,'#!/bin/sh'."\n");
		fputs($fp,"source ".$SETENV."\n");
		fputs($fp,'cd '.$W_DIR."\n");
		fputs($fp,'biorels_exe php '.$RUNSCRIPT.' '.$I.'  &> SCRIPTS/LOG_'.$I."\n");
		fputs($fp,'echo $? > SCRIPTS/status_'.$I."\n");
		fclose($fp);
		
		
	}
	fclose($fpA);
	$JOB_ID=0;$N_TR=0;
	$fp=fopen('SCRIPTS/job_input_'.$JOB_ID,"w");if(!$fp)												failProcess($JOB_ID."015",'Unable to open '.'SCRIPTS/job_input_'.$JOB_ID);
	foreach ($INPUT_DATA as $gn_entry_id=>$list_tr)
	{
		fputs($fp,$gn_entry_id."\t".json_encode($list_tr)."\n");
		$N_TR+=count($list_tr);
		if ($N_TR<$N_PER_JOB)continue;
		$N_TR=0;
		$JOB_ID++;
		fclose($fp);
		$fp=fopen('SCRIPTS/job_input_'.$JOB_ID,"w");if(!$fp)												failProcess($JOB_ID."016",'Unable to open '.'SCRIPTS/job_input_'.$JOB_ID);
	}

	fclose($fp);



successProcess();
?>

