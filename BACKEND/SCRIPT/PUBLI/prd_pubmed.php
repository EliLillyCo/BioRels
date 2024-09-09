<?php
/**
 SCRIPT NAME: prd_pubmed
 PURPOSE:     Push version to production
 
*/
$JOB_NAME='prd_pubmed';

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
	/// Get parent job information
	$CK_PUBMED_INFO=$GLB_TREE[getJobIDByName('db_pubmed')];
	
	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$PUB_DIR=$W_DIR.'/'.$CK_PUBMED_INFO['DIR'].'/';   if (!is_dir($PUB_DIR) && !mkdir($PUB_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$PUB_DIR);
	$W_DIR=$PUB_DIR.$CK_PUBMED_INFO['TIME']['DEV_DIR'];if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	if (!chdir($W_DIR)) 																			failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

addLog("Working directory: ".$W_DIR);

	/// Setting up the process control directory to the current release so that the next job can pick it up
	$PROCESS_CONTROL['DIR']=$CK_PUBMED_INFO['TIME']['DEV_DIR'];


addLog("Delete files");
	$FILE_TO_DEL=array(
'anatomy_insert.csv',
'disease_insert.csv',
'dr.csv',
'drug_insert.csv',
'ds.csv',
'GENE_ACCELERATION.csv',
'gene_insert.csv',
'gs.csv',
'pmid_abstract.csv',
'Journals_db.csv',
'pmid_author.csv',
'pmid_author_map.csv',
'pmid_citation.csv',
'pmid_company_map.csv',
'pmid_entry.csv',
'pmid_instit.csv',
'pmid_journal.csv',
'ts.csv');

		foreach ($FILE_TO_DEL as $FILE)
		{
			if (file_exists($FILE) && !unlink($FILE)) 		failProcess($JOB_ID."005",'Unable to delete '.$FILE);
		}


addLog("Push to prod");
	updateReleaseDate($JOB_ID,'PUBMED',$CK_PUBMED_INFO['TIME']['DEV_DIR']);
	pushToProd();

successProcess();
?>
