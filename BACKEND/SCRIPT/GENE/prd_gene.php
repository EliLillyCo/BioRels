<?php

/**
 SCRIPT NAME: prd_gene
 PURPOSE:     This script pushes gene files to production
 
*/

/// Job name - Do not change
$JOB_NAME='prd_gene';


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
	$DL_GENE_INFO=$GLB_TREE[getJobIDByName('db_gene')];

	/// Get to working directory
	$GENE_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/';
	$W_DIR=$GENE_DIR.$DL_GENE_INFO['TIME']['DEV_DIR'];
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	
	
	/// Go to the directory set up by parent
	$PROCESS_CONTROL['DIR']=$DL_GENE_INFO['TIME']['DEV_DIR'];

	addLog("Working directory: ".$W_DIR);

	addLog("Listing selected Taxons based on config");
	/// We have the possibility to limit the number of organism to process.
	/// IN CONFIG_USER, you can provide a list of taxonomy ID corresponding to the organisms you want to consider
	/// We flip the array to have a list of taxon ID as key because it's much faster to perform searches on keys rather than values
	$TAXON_LIMIT_LIST=array_flip(defineTaxonList());
	addLog(count($TAXON_LIMIT_LIST).' taxons defined');
	

	/// In the case of Human, we need to create a list of genes
	if ($TAXON_LIMIT_LIST==array() || isset($TAXON_LIMIT_LIST['9606']))
	{
		addLog("Create Human Gene list");
		
		$FILE=$W_DIR.'/HUMAN_GENES.csv';
		$QUERY="select DISTINCT G.symbol, g.gene_id FROM mv_gene G, gn_Entry GE WHERE g.gn_entry_id = ge.gn_Entry_id 
				AND gene_type ='protein-coding' and tax_id='9606' ORDER BY G.gene_id ASC";
		
		runQueryToFile($QUERY,$FILE,$JOB_ID.'002');
		
	}

	updateReleaseDate($JOB_ID,'GENE',getCurrDate());

addLog("Clean up");
	$list_files=array(
		'CHR_GN_MAP.csv',
		'gene_ortho_file.csv',
		'gene_ortho_tmp',
		'GN_ENTRY.csv',
		'GN_SYN.csv',
		'GN_SYN_MAP.csv',
		'gene_info_t');
	foreach ($list_files as $F)
	{
		echo $W_DIR.'/'.$F."\t".is_file($W_DIR.'/'.$F)."\n";	
	if (is_file($W_DIR.'/'.$F) && !unlink($W_DIR.'/'.$F)) 							failProcess($JOB_ID."003",'Unable to delete '.$W_DIR.'/'.$F.' file');
	}
	



addLog("Push to prod");
	pushToProd();

successProcess();
?>
