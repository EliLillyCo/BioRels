<?php


error_reporting(E_ALL);
ini_set('memory_limit','5000M');



$JOB_RUNID=$argv[1];

/// Job name - Do not change
$JOB_NAME='process_${DATASOURCE}';

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




addLog("Go to directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_${DATASOURCE}')];
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 					failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$U_DIR);
	$U_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$U_DIR);
	
	$W_DIR=$U_DIR.'/JSON/';						if (!is_dir($W_DIR) && !chdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create job dir '.$W_DIR);
	if (!chdir($W_DIR)) 																failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	
	echo $W_DIR."\n";
addLog("Check inputs");

	// Check all the files are present
	


addLog("Load static data");

	/// If you have data from some relatively small tables that you wish to load in memory to speed up the process:

	$STATIC_DATA=array('ECO'=>array(),'GO'=>array());

	// $QUERY='select eco_id,eco_entry_id FROM eco_entry';
	// $res=runQuery($QUERY);if ($res===false)												failProcess($JOB_ID."011","Unable to run query ",$QUERY);
	// foreach ($res as $tab)	$STATIC_DATA['ECO'][$tab['eco_id']]=$tab['eco_entry_id'];
	
	// $QUERY='select ac,go_entry_id FROM GO_ENTRY ';
	// $res=runQuery($QUERY);if ($res===false)												failProcess($JOB_ID."012","Unable to run query ",$QUERY);
	// foreach ($res as $tab)	$STATIC_DATA['GO'][$tab['ac']]=$tab['go_entry_id'];



	
addLog("Get list to process");
	
	/// Modify this line below to retrieve the number of records you need to process
	$ENTRIES_TO_PROCESS=0;

	/// We get the number of lines in this file, Which defines the total number of jobs:
	$TOT_JOBS=getLineCount($U_DIR.'/SCRIPTS/all.sh');


	$N_P_JOB=ceil($LINE_C/$TOT_JOBS);
	/// Based on the Job ID and the number of records to process, we can get the first and last records to process
	$START=$N_P_JOB*($JOB_RUNID);
	$END=$N_P_JOB*($JOB_RUNID+1);
	
	
	echo $LINE_C."\t".$START."\t".$END."\n";
	

	/// Load the list of what needs to be processed for this job:
	$TO_PROCESS=array();



addLog("Processing records");
	$fpO=fopen($JOB_RUNID.'.json','w');if (!$fpO)									failProcess($JOB_ID."014",'Unable to open  '.$JOB_RUNID.'.json');
	$fpE=fopen($JOB_RUNID.'.err','w');if (!$fpE)									failProcess($JOB_ID."015",'Unable to open  '.$JOB_RUNID.'.err');
	
	$N_PROCESS=0;
	
	/// Loop over what needs to be processed:
	foreach ($TO_PROCESS as $ENTRY)
	{
	
		//	if ($ENTRY[0]!='CAPSD_HEVCT')continue;

		++$N_PROCESS;
		echo "###### ".$ENTRY[0]."\t".$N_PROCESS."\n";
	 	processEntry($ENTRY,$fpO,$fpE);

		/// Cleaning up memory for optimal use
		gc_collect_cycles();
		
		
	  }

	echo "END\t".$N_PROCESS."\n";
	
	fclose($fpO);


/// fpO = file output
/// fpE: File error
function processEntry(&$ENTRY,&$fpO,$fpE)
{

}




?>
