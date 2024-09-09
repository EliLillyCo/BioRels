<?php

/**
 SCRIPT NAME: dl_ot
 PURPOSE:     Download all open targets files
 
*/

/// This is the list of evidence sources to download
/// And commented out are the ones you can potentially add but are not currently processed by the pipeline
$EVIDENCE_LIST=array('chembl','europepmc');
//'cancer_biomarkers','cancer_gene_census','clingen','crispr','crispr_screen','eva','eva_somatic','expression_atlas','gene2phenotype','gene_burden','genomics_england','impc','intogen','orphanet','ot_genetics_portal','progeny','slapenrich','sysbio','mousePhenotypes');



/// Job name - Do not change
$JOB_NAME='dl_ot';


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

addLog("Set up directory");
	
	///	get Parent info:
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_ot_rel')];

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Update process control directory to the current release
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	if (!isset($GLB_VAR['LINK']['FTP_OPEN_TARGETS']))									failProcess($JOB_ID."005",'FTP_OPEN_TARGETS path no set');

	/// Get current release
	$CURR_RELEASE=getCurrentReleaseDate('OPEN_TARGET',$JOB_ID);
	
	/// The path to download the files is directly dependent on the current release
	$PATH=$GLB_VAR['LINK']['FTP_OPEN_TARGETS'].'/'.$CURR_RELEASE.'/';

	/// Open Targets releases the files 24h after the release date
	$DONE=false;
	for ($N=0;$N<=576;++$N)///576*300s=48h
	{
		if (checkFileExist($PATH.'/evidence.html'))
		{
			$DONE=true;
			break;
		}
		/// If the file is not there, wait 5 minutes and check again
		if (dl_file($PATH.'/output/etl/json/evidence',3,'evidence.html'))	
		{
			$DONE=true;
			break;
		}
		sleep(300);
	}
	if (!$DONE)																		failProcess($JOB_ID."006",'Unable to download '.$outfile.'.html');
	
	/// Provides the list of evidence, and data sources
	$fpB=fopen('evidence.html','r');if (!$fpB) 										failProcess($JOB_ID."007",'Unable to open '.$outfile.'.html');
	 
	while(!feof($fpB))
	{
		$line=stream_get_line($fpB,10000,"\n");
		$tab=explode(">",$line);if (!isset($tab[5]))continue;
		
		$name=explode('"',$tab[5])[1];
		$pos=strpos($name,'=');
		$source_name=substr($name,$pos+1,-1);
		echo $source_name."\n";

		/// If the source name is not in the list, skip it
		if (!in_array($source_name,$EVIDENCE_LIST))continue;
		
		/// Download the file
		downloadFTPFile($PATH.'/output/etl/json/evidence/'.$name,$source_name,'.json');

		exec('cat '.$source_name.'/*.json > '.$source_name.'.json',$res,$return_code);
		if ($return_code!=0)														failProcess($JOB_ID."008",'Unable to create '.$source_name.'.json');
	}
	fclose($fpB);
	 


	/// Those are additional directories:

	 $DIRS=array('output/etl/json/molecule'=>'molecule',
	 
	 'output/etl/json/indication'=>'indication',
	 'output/etl/json/mechanismOfAction'=>'mechanismOfAction',
	 
	 'output/etl/json/diseaseToPhenotype'=>'diseaseToPhenotype');
	 foreach ($DIRS as $DIR=>$FN)
	 {
		
	 	 downloadFTPFile($PATH.$DIR,$FN,'.json');
	 }

	  exec('cat molecule/*.json > molecules.json',$res,$return_code);if ($return_code!=0)failProcess($JOB_ID."009",'Unable to create molecule.json');
	  exec('cat indication/*.json > indication.json',$res,$return_code);if ($return_code!=0)failProcess($JOB_ID."010",'Unable to create indication.json');
	



	
successProcess();

?>

