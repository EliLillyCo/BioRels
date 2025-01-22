<?php
/*
 SCRIPT NAME: pmj_uniprot
 PURPOSE:     Prepare scripts for UniProt Processing
 
*/

/// Job name - Do not change
$JOB_NAME='pmj_uniprot';

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

addLog("Create directory");
	
	/// GEt parent info	
	$CK_INFO=$GLB_TREE[getJobIDByName('pp_uniprot')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	
	/// Check process_uniprot.php
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_uniprot.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."008",$RUNSCRIPT.' file not found');

	$RUNSCRIPT_PATH='$TG_DIR/'.$GLB_VAR['SCRIPT_DIR'].'/'.$JOB_INFO['DIR'].'/process_uniprot.php';
	


addLog("Working directory: ".$W_DIR);	

	$STATIC_DATA=array('ECO'=>array());


	/// Ensure we have ECO entries:
	$QUERY='select count(*) co FROM eco_entry';
	$res=runQuery($QUERY);
	if ($res===false)																	failProcess($JOB_ID."011","Unable to run query ",$QUERY);
	if ($res[0]['co']==0)																failProcess($JOB_ID."012","No ECO Entry record found");
	

	/// Ensure we have GO entries:
	$QUERY='select count(*) co FROM GO_ENTRY ';
	$res=runQuery($QUERY);if ($res===false)												failProcess($JOB_ID."013","Unable to run query ",$QUERY);
	if ($res[0]['co']==0)																failProcess($JOB_ID."014","No Gene Ontology record found");
	

	
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))										failProcess($JOB_ID."015",'Unable to create jobs directory');
	if (!is_dir("JSON") && !mkdir("JSON"))												failProcess($JOB_ID."016",'Unable to create jobs directory');
	
	
	/// Create master script:
	$N_JOB=50;
	if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')$N_JOB=1;


	for($I=0;$I<$N_JOB;++$I)
	{
		$COMMANDS[$I][]='biorels_php '.$RUNSCRIPT_PATH.' '.$I.' '.$N_JOB.' &> SCRIPTS/LOG_'.$I;
	}
	prepare_batch($COMMANDS,$W_DIR);





successProcess();

?>


