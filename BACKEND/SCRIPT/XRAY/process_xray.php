<?php


error_reporting(E_ALL);
ini_set('memory_limit','5000M');
$TOT_JOB=70;
$PDB_ID=$argv[1];
$JOB_NAME='process_xray';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);

require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
require_once($TG_DIR.'/BACKEND/SCRIPT/XRAY/xray_functions.php');


//addLog("Go to directory");
	if(!isset($GLB_VAR['TOOL']['PDBSEP']))													failProcess($JOB_ID."001",'Unable to Find PDBSEP');
	if (!is_executable($TG_DIR.'/'.$GLB_VAR['TOOL']['PDBSEP']))								failProcess($JOB_ID."002",'Not allowed to execute PDBSEP');
	if(!isset($GLB_VAR['TOOL']['SEQALIGN']))												failProcess($JOB_ID."003",'Unable to Find SEQ_ALIGN');
	if (!is_executable($TG_DIR.'/'.$GLB_VAR['TOOL']['SEQALIGN']))							failProcess($JOB_ID."004",'Not allowed to execute SEQ_ALIGN');
	if(!isset($GLB_VAR['TOOL']['BLASTP']))													failProcess($JOB_ID."005",'Unable to Find BLASTP');
	if (!is_executable($TG_DIR.'/'.$GLB_VAR['TOOL']['BLASTP']))								failProcess($JOB_ID."006",'Not allowed to execute BLASTP');
	if(!isset($GLB_VAR['TOOL']['MOE_PARAMS']))												failProcess($JOB_ID."007",'Unable to Find MOE_PARAMS');
	
	if (!checkFileExist($TG_DIR.'/'.$GLB_VAR['TOOL']['MOE_PARAMS'].'/batch_pdb_prep.svl'))	failProcess($JOB_ID."008",'Not file find at MOE_PARAMS ');
	
	if(!isset($GLB_VAR['TOOL']['BLOSSUM_DIR']))												failProcess($JOB_ID."009",'Unable to Find BLOSSUM_DIR');
	
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_xray')];
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 						failProcess($JOB_ID."010",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR))					 	failProcess($JOB_ID."011",'Unable to find and create '.$U_DIR);

	$E_DIR=$U_DIR.'ENTRIES/'.substr($PDB_ID,1,2).'/'.$PDB_ID;	
	if (!is_dir($E_DIR))				 		
	{
		$E_DIR=$U_DIR.'INTERNAL/'.substr($PDB_ID,1,2).'/'.$PDB_ID;						
		if (!is_dir($E_DIR))				 												failProcess($JOB_ID."012",'Unable to get to ENTRIES at '.$U_DIR);
	}
	if (!chdir($E_DIR))																 		failProcess($JOB_ID."013",'Unable to find PDB entry dir '.$U_DIR);
	if (!is_file('_data'))																	failProcess($JOB_ID."014",'Unable to find _data ');

//addLog("Get _data file");
	$json_str=file_get_contents('_data');		if ($json_str===false)						failProcess($JOB_ID."015",'Unable to load json data');
	$ENTRY=json_decode($json_str,true);			if ($ENTRY==null)							failProcess($JOB_ID."016",'Fail to interpret json data');
	
	processEntry($ENTRY);
	echo "ENTRY\t". $PDB_ID;
	foreach ($ENTRY['PROCESS'] as $K=>$V)echo "\t".$K.":".$V;
	echo "\n";
	//print_r($ENTRY['PROCESS']);
	//print_r($ENTRY);
	exit;


?>
