<?php

/**
 * 
 * 	This script is inserting sequence/domain alignment statistics and amino-acid pairs in the database
 * 
 */
error_reporting(E_ALL);
ini_set('memory_limit','5000M');

$JOB_NAME='gen_transcriptome';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];


addLog("Go to directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_transcriptome')];
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 					failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$U_DIR);
	$U_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$U_DIR);
	
	$W_DIR=$U_DIR.'/DATA/';						if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create job dir '.$W_DIR);
	if (!chdir($W_DIR)) 																failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	echo $W_DIR."\n";

	$TAXON_LIST=array();

	$res=runQuery("SELECT tax_id FROM genome_assembly g, taxon t where t.taxon_id = g.taxon_id");
	foreach ($res as $line)
	{
		$TAXON_LIST[]=$line['tax_id'];
		if (is_dir($line['tax_id'])) cleanDirectory($line['tax_id']);
		if (!is_dir($line['tax_id']) && !mkdir($line['tax_id']))				failProcess($JOB_ID."006",'Unable to create taxon directory '.$W_DIR);
	}
	if (is_dir('SEEDS')) cleanDirectory('SEEDS');
	if (!is_dir('SEEDS') && !mkdir('SEEDS'))				failProcess($JOB_ID."007",'Unable to create SEEDS directory '.$W_DIR);

	$FTYPE=array('transcriptome','CDS','3UTR','5UTR');
	for ($I=0;$I<200;++$I)
	{
		echo ' JOB '.$I."\n";
		$dir=scandir('../RESULTS/DATA_'.$I);
		foreach ($dir as $file)
		{
			if (!in_array($file,$TAXON_LIST))continue;
			foreach ($FTYPE as $F)
			{
				if (is_file('../RESULTS/DATA_'.$I.'/'.$file.'/'.$file.'_'.$F.'.fa'))
				{
					exec('cat '.'../RESULTS/DATA_'.$I.'/'.$file.'/'.$file.'_'.$F.'.fa >> '.$file.'/'.$file.'_'.$F.'.fa',$results,$return_code);
					if ($return_code!=0)			failProcess($JOB_ID."008",'Unable to merge ../RESULTS/DATA_'.$I.'/'.$file.'/'.$file.'_'.$F.'.fa');
				}
			}
				
		}
	}
foreach ($TAXON_LIST as $TAX_ID)
{
	
	chdir($W_DIR.'/'.$TAX_ID);
	echo "PROCESSING ".$TAX_ID;
	foreach ($FTYPE as $TYPE)
	{
		$T_DIR=$W_DIR.'/'.$TAX_ID;
	addLog("Create Blast files");
		if (!is_dir($T_DIR.'/'.$TYPE.'_BLASTN') && !mkdir($T_DIR.'/'.$TYPE.'_BLASTN'))failProcess($JOB_ID."009",'Unable to create  directory '.$TYPE.'_BLASTN for '.$TAX_ID);
		if (!chdir($T_DIR.'/'.$TYPE.'_BLASTN'))										failProcess($JOB_ID."010",'Unable to get to '.$TYPE.'_BLASTN for '.$TAX_ID);
		
		system($GLB_VAR['TOOL']['MAKEBLAST'].' -in ../'.$TAX_ID.'_'.$TYPE.'.fa  -dbtype nucl -out '.$TAX_ID.'_'.$TYPE.'_BLASTN'.' &> PREP_LOG',$return_code);
		if ($return_code !=0)														failProcess($JOB_ID."011",'Unable to create blastn files for '.$TAX_ID);
	addLog("Create bowtie files for Taxonomy ".$TAX_ID);
		if (!is_dir($T_DIR.'/'.$TYPE.'_BOWTIE') && !mkdir($T_DIR.'/'.$TYPE.'_BOWTIE'))failProcess($JOB_ID."012",'Unable to create directory '.$TYPE.'_BOWTIE for '.$TAX_ID);
		if (!chdir($T_DIR.'/'.$TYPE.'_BOWTIE'))										failProcess($JOB_ID."013",'Unable to get to '.$TYPE.'_BOWTIE for '.$TAX_ID);
		system($GLB_VAR['TOOL']['BOWTIE_BUILD'].' -r ../'.$TAX_ID.'_'.$TYPE.'.fa BOWTIE_'.$TYPE.'_'.$TAX_ID.' &> PREP_LOG',$return_code);
		if ($return_code !=0)														failProcess($JOB_ID."014",'Unable to create bowtie files for '.$TAX_ID);
		addLog("Create bowtie2 files for Taxonomy ".$TAX_ID);
		if (!is_dir($T_DIR.'/'.$TYPE.'_BOWTIE2') && !mkdir($T_DIR.'/'.$TYPE.'_BOWTIE2'))failProcess($JOB_ID."015",'Unable to create directory '.$TYPE.'_BOWTIE2 for '.$TAX_ID);
		if (!chdir($T_DIR.'/'.$TYPE.'_BOWTIE2'))										failProcess($JOB_ID."016",'Unable to get to '.$TYPE.'_BOWTIE2 for '.$TAX_ID);
		system($GLB_VAR['TOOL']['BOWTIE2_BUILD'].' -r ../'.$TAX_ID.'_'.$TYPE.'.fa BOWTIE2_'.$TYPE.'_'.$TAX_ID.' &> PREP_LOG',$return_code);
		if ($return_code !=0)														failProcess($JOB_ID."017",'Unable to create bowtie2 files for '.$TAX_ID);
	}
}
successProcess();exit;
?>
