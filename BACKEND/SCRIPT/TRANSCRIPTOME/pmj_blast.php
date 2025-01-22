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

	
echo $W_DIR."\n";
	
	$FTYPE=array('transcriptome','CDS','3UTR','5UTR');
	$JOB_NUM=-1;


	$COMMANDS=array();

	$res=runQuery("SELECT tax_id FROM genome_assembly g, taxon t where t.taxon_id = g.taxon_id");
	if ($res===false)failProcess($JOB_ID."004",'Unable to get count of transcripts per taxon');
	foreach ($res as $line)
	{
		$TAXON=$line['tax_id'];
		foreach ($FTYPE as $TYPE)
		{
			++$JOB_NUM;
			$T_DIR=$W_DIR.'/DATA/'.$TAXON;
			
			if (!is_dir($T_DIR.'/'.$TYPE.'_BLASTN') && !mkdir($T_DIR.'/'.$TYPE.'_BLASTN'))failProcess($JOB_ID."005",'Unable to create  directory '.$TYPE.'_BLASTN for '.$TAXON);
			$COMMANDS[$JOB_NUM][]='cd '.$T_DIR.'/'.$TYPE.'_BLASTN'."\n".
				'biorels_exe '.$GLB_VAR['TOOL']['MAKEBLAST'].' -in ../'.$TAXON.'_'.$TYPE.'.fa  -dbtype nucl -out '.$TAXON.'_'.$TYPE.'_BLASTN'.' &> PREP_LOG';
				

			++$JOB_NUM;
			if (!is_dir($T_DIR.'/'.$TYPE.'_BOWTIE') && !mkdir($T_DIR.'/'.$TYPE.'_BOWTIE'))failProcess($JOB_ID."006",'Unable to create  directory '.$TYPE.'_BOWTIE for '.$TAXON);
			$COMMANDS[$JOB_NUM][]='cd '.$T_DIR.'/'.$TYPE.'_BOWTIE'."\n".
				'biorels_exe '.$GLB_VAR['TOOL']['BOWTIE_BUILD'].' -r ../'.$TAXON.'_'.$TYPE.'.fa BOWTIE_'.$TYPE.'_'.$TAXON.' &> PREP_LOG';
				

			++$JOB_NUM;
			if (!is_dir($T_DIR.'/'.$TYPE.'_BOWTIE2') && !mkdir($T_DIR.'/'.$TYPE.'_BOWTIE2'))failProcess($JOB_ID."007",'Unable to create  directory '.$TYPE.'_BOWTIE2 for '.$TAXON);
			$COMMANDS[$JOB_NUM][]='cd '.$T_DIR.'/'.$TYPE.'_BOWTIE2'."\n".
				'biorels_exe '.$GLB_VAR['TOOL']['BOWTIE2_BUILD'].' -r ../'.$TAXON.'_'.$TYPE.'.fa BOWTIE2_'.$TYPE.'_'.$TAXON.' &> PREP_LOG';

		}
	}

	prepare_batch($COMMANDS,$W_DIR);
	


successProcess();
?>

