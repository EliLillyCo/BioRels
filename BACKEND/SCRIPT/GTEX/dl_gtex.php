<?php

/**
 SCRIPT NAME: dl_gtex
 PURPOSE:     This script downloads all the gtex files.
				GTEX files does not allow to manage per version,
				so it hard code the paths
 
The data used for the analyses described  were obtained from  the GTEx Portal,  dbGaP accession number phs000424.vN.pN

*/

/// Job name - Do not change
$JOB_NAME='ck_gtex_rel';


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
	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR))															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if ( !chdir($W_DIR))															failProcess($JOB_ID."002",'Unable to access '.$W_DIR);

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
echo $W_DIR;


addLog("Download files");


$FILES=array('https://storage.googleapis.com/gtex_analysis_v8/annotations/GTEx_Analysis_v8_Annotations_SampleAttributesDS.txt',
'https://storage.googleapis.com/gtex_analysis_v8/rna_seq_data/GTEx_Analysis_2017-06-05_v8_RNASeQCv1.1.9_gene_tpm.gct.gz',
'https://storage.googleapis.com/gtex_analysis_v8/rna_seq_data/GTEx_Analysis_2017-06-05_v8_RSEMv1.3.0_transcript_tpm.gct.gz',);


foreach ($FILES as $file)
{
	if (!dl_file($file)) failProcess($JOB_ID."003",'Unable to download file '.$file);
}

if (!ungzip('GTEx_Analysis_2017-06-05_v8_RNASeQCv1.1.9_gene_tpm.gct.gz'))failProcess($JOB_ID."004",'Unable to ungzip gene tmp file ');
if (!ungzip('GTEx_Analysis_2017-06-05_v8_RSEMv1.3.0_transcript_tpm.gct.gz'))failProcess($JOB_ID."005",'Unable to ungzip transcript tmp file ');
if (!rename('GTEx_Analysis_v8_Annotations_SampleAttributesDS.txt','Sample.csv'))failProcess($JOB_ID."006",'Unable to rename sample');
if (!rename('GTEx_Analysis_2017-06-05_v8_RNASeQCv1.1.9_gene_tpm.gct','GeneTPM.csv'))failProcess($JOB_ID."007",'Unable to rename gene TPM');
if (!rename('GTEx_Analysis_2017-06-05_v8_RSEMv1.3.0_transcript_tpm.gct','TranscriptTPM.csv'))failProcess($JOB_ID."008",'Unable to rename Transcript TPM');
	successProcess();

?>
