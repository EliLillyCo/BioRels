<?php

/**
 SCRIPT NAME: pmj_blast
 
*/
ini_set('memory_limit','5000M');
$JOB_NAME='pmj_blast';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);


addLog("Create directory");
	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];						if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$PMJ_INFO=$GLB_TREE[getJobIDByName('rmj_transcriptome')];
	$W_DIR.='/'.$PMJ_INFO['TIME']['DEV_DIR'].'/';if (!is_dir($W_DIR) || !chdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to find and create '.$W_DIR);
	$PROCESS_CONTROL['DIR']=$PMJ_INFO['TIME']['DEV_DIR'];

	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."004",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."005",'SCRIPT_DIR not found ');
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."006",'Setenv file not found ');
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."007",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."008",'JOBARRAY file NOT FOUND '.$JOBARRAY);

echo $W_DIR."\n";

if (!is_dir("SCRIPTS_BLAST") && !mkdir("SCRIPTS_BLAST"))										failProcess($JOB_ID."009",'Unable to create SCRIPTS directory');
	$fpA=fopen("SCRIPTS_BLAST/all.sh",'w'); if(!$fpA)											failProcess($JOB_ID."010",'Unable to open all.sh');
	
	$FTYPE=array('transcriptome','CDS','3UTR','5UTR');
	$JOB_NUM=-1;


	$res=runQuery("SELECT tax_id FROM genome_assembly g, taxon t where t.taxon_id = g.taxon_id");
	foreach ($res as $line)
	{
		$TAXON=$line['tax_id'];
		foreach ($FTYPE as $TYPE)
		{
			++$JOB_NUM;
			$T_DIR=$W_DIR.'/DATA/'.$TAXON;
			
			if (!is_dir($T_DIR.'/'.$TYPE.'_BLASTN') && !mkdir($T_DIR.'/'.$TYPE.'_BLASTN'))failProcess($JOB_ID."011",'Unable to create  directory '.$TYPE.'_BLASTN for '.$TAXON);
			$JOB_NAME="SCRIPTS_BLAST/job_".$JOB_NUM.".sh";
			$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."012",'Unable to open jobs/job_'.$I.'.sh');
			fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");
			fputs($fp,'#!/bin/sh'."\n");
			fputs($fp,"source ".$SETENV."\n");
			fputs($fp,'cd '.$T_DIR.'/'.$TYPE.'_BLASTN'."\n");
			fputs($fp,'biorels_exe '.$GLB_VAR['TOOL']['MAKEBLAST'].' -in ../'.$TAXON.'_'.$TYPE.'.fa  -dbtype nucl -out '.$TAXON.'_'.$TYPE.'_BLASTN'.' &> PREP_LOG'."\n");
			fputs($fp,'echo $? > '.$W_DIR.'/SCRIPTS_BLAST/status_'.$JOB_NUM."\n");
			fclose($fp);

			++$JOB_NUM;
			if (!is_dir($T_DIR.'/'.$TYPE.'_BOWTIE') && !mkdir($T_DIR.'/'.$TYPE.'_BOWTIE'))failProcess($JOB_ID."013",'Unable to create  directory '.$TYPE.'_BOWTIE for '.$TAXON);
			$JOB_NAME="SCRIPTS_BLAST/job_".$JOB_NUM.".sh";
			$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."014",'Unable to open jobs/job_'.$I.'.sh');
			fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");
			fputs($fp,'#!/bin/sh'."\n");
			fputs($fp,"source ".$SETENV."\n");
			fputs($fp,'cd '.$T_DIR.'/'.$TYPE.'_BOWTIE'."\n");
			fputs($fp,'biorels_exe '.$GLB_VAR['TOOL']['BOWTIE_BUILD'].' -r ../'.$TAXON.'_'.$TYPE.'.fa BOWTIE_'.$TYPE.'_'.$TAXON.' &> PREP_LOG'."\n");
			fputs($fp,'echo $? > '.$W_DIR.'/SCRIPTS_BLAST/status_'.$JOB_NUM."\n");
			fclose($fp);

			++$JOB_NUM;
			if (!is_dir($T_DIR.'/'.$TYPE.'_BOWTIE2') && !mkdir($T_DIR.'/'.$TYPE.'_BOWTIE2'))failProcess($JOB_ID."015",'Unable to create  directory '.$TYPE.'_BOWTIE2 for '.$TAXON);
			$JOB_NAME="SCRIPTS_BLAST/job_".$JOB_NUM.".sh";
			$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."016",'Unable to open jobs/job_'.$I.'.sh');
			fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");
			fputs($fp,'#!/bin/sh'."\n");
			fputs($fp,"source ".$SETENV."\n");
			fputs($fp,'cd '.$T_DIR.'/'.$TYPE.'_BOWTIE2'."\n");
			fputs($fp,'biorels_exe '.$GLB_VAR['TOOL']['BOWTIE2_BUILD'].' -r ../'.$TAXON.'_'.$TYPE.'.fa BOWTIE2_'.$TYPE.'_'.$TAXON.' &> PREP_LOG'."\n");
			fputs($fp,'echo $? > '.$W_DIR.'/SCRIPTS_BLAST/status_'.$JOB_NUM."\n");
			fclose($fp);

		}

	//	if (is_dir($line['tax_id'])) cleanDirectory($line['tax_id']);
	//	if (!is_dir($line['tax_id']) && !mkdir($line['tax_id']))				failProcess($JOB_ID.'005','Unable to create taxon directory '.$W_DIR);
	}
	


successProcess();
?>

