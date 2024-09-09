<?php


error_reporting(E_ALL);
ini_set('memory_limit','5000M');



$JOB_RUNID=$argv[1];
$RUN_MISSING=($argv[2]=='T');


/// Job name - Do not change
$JOB_NAME='process_uniprot';

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

require_once($TG_DIR.'/BACKEND/SCRIPT/UNIPROT/uniprot_function.php');



addLog("Go to directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('pp_uniprot')];
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 					failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$U_DIR);
	$U_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$U_DIR);
	
	$W_DIR=$U_DIR.'/JSON/';						if (!is_dir($W_DIR) && !chdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create job dir '.$W_DIR);
	if (!chdir($W_DIR)) 																failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	if (!isset($GLB_VAR['WITH_UNIPROT_SP']))											failProcess($JOB_ID."006",'WITH_UNIPROT_SP Not set in CONFIG_GLOBAL');
	if (!in_array($GLB_VAR['WITH_UNIPROT_SP'],array('Y','N')))							failProcess($JOB_ID."007",'WITH_UNIPROT_SP  value must be either Y or N');
	if (!isset($GLB_VAR['WITH_UNIPROT_TREMBL']))										failProcess($JOB_ID."008",'WITH_UNIPROT_TREMBL Not set in CONFIG_GLOBAL');
	if (!in_array($GLB_VAR['WITH_UNIPROT_TREMBL'],array('Y','N')))						failProcess($JOB_ID."009",'WITH_UNIPROT_TREMBL must be either Y or N');
	echo $W_DIR."\n";
addLog("Check inputs");

	if (!checkFileExist($U_DIR.'/unique_pointers.csv')) 								failProcess($JOB_ID."010",'No unique pointer found');
	
	if (checkFileExist('PROTEOMES/proteome_list'))
	{
	if (!checkFileExist($U_DIR.'/PROTEOMES/ALL_SEQ.txt')) successProcess('VALID');
	if (!checkFileExist($U_DIR.'/PROTEOMES/ALL_PROT_UNIPROT.txt')) successProcess('VALID');
	
	}
	if (checkFileExist('ALT/ALT_list'))
	{
	if (!checkFileExist($U_DIR.'/ALT/ALT_ENTRIES.fasta')) successProcess('VALID');
	if (!checkFileExist($U_DIR.'/ALT/ALT_ENTRIES.txt')) successProcess('VALID');
	}

	if ($GLB_VAR['WITH_UNIPROT_SP']=='Y')
	{
		
		if (!checkFileExist($U_DIR.'/SPROT/sprot_list')) successProcess('VALID');
		if (!checkFileExist($U_DIR.'/SPROT/uniprot_sprot.dat')) successProcess('VALID');
		if (!checkFileExist($U_DIR.'/SPROT/uniprot_all.fasta')) successProcess('VALID');
		
	}
	if ($GLB_VAR['WITH_UNIPROT_TREMBL']=='Y')
	{
		
		if (!checkFileExist($U_DIR.'/TREMBL/trembl_list')) successProcess('VALID');
		if (!checkFileExist($U_DIR.'/TREMBL/uniprot_trembl.dat')) successProcess('VALID');
		if (!checkFileExist($U_DIR.'/TREMBL/uniprot_trembl.fasta')) successProcess('VALID');
		
	}
	

	$STATIC_DATA=array('ECO'=>array());

	$QUERY='select eco_id,eco_entry_id FROM eco_entry';
	$res=runQuery($QUERY);if ($res===false)												failProcess($JOB_ID."011","Unable to run query ",$QUERY);
	foreach ($res as $tab)	$STATIC_DATA['ECO'][$tab['eco_id']]=$tab['eco_entry_id'];
	
	$QUERY='select ac,go_entry_id FROM GO_ENTRY ';
	$res=runQuery($QUERY);if ($res===false)												failProcess($JOB_ID."012","Unable to run query ",$QUERY);
	foreach ($res as $tab)	$STATIC_DATA['GO'][$tab['ac']]=$tab['go_entry_id'];



	
addLog("Get list to process");
	/// By default we look at the unique pointers file for all records to process 
	$INPUT_FILE='unique_pointers.csv';
	
	/// We get the number of lines in this file, that we are going to divide by the number of jobs to get the number of records to process
	$LINE_C=getLineCount($U_DIR.'/unique_pointers.csv');
	/// We get the number of lines in this file, Which defines the total number of jobs:
	$TOT_JOBS=getLineCount($U_DIR.'/SCRIPTS/all.sh');


	$N_P_JOB=ceil($LINE_C/$TOT_JOBS);
	/// Based on the Job ID and the number of records to process, we can get the first and last records to process
	$START=$N_P_JOB*($JOB_RUNID);
	$END=$N_P_JOB*($JOB_RUNID+1);
	$N_LINE=-1;
	$TO_PROCESS=array();
	echo $LINE_C."\t".$START."\t".$END."\n";
	

	$fp=fopen($U_DIR.'/unique_pointers.csv','r'); if (!$fp)									failProcess($JOB_ID."013",'Unable to open  '.$U_DIR.'/'.$INPUT_FILE);
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=="")continue;
		
		$tab=explode("\t",$line);
		if (count($tab)!=3)continue;
		/// Checking if we need to process that record or not
		$N_LINE++;
		if ($N_LINE<$START || $N_LINE>=$END)continue;
		/// All records that need to be processed gets there
		$TO_PROCESS[$tab[0]]=$tab;
	}
	fclose($fp);


addLog("Processing records");
	$fpO=fopen($JOB_RUNID.'.json','w');if (!$fpO)									failProcess($JOB_ID."014",'Unable to open  '.$JOB_RUNID.'.json');
	$fpE=fopen($JOB_RUNID.'.err','w');if (!$fpE)									failProcess($JOB_ID."015",'Unable to open  '.$JOB_RUNID.'.err');
	
	$N_PROCESS=0;
	
	$NTEST=0;
	foreach ($TO_PROCESS as $ENTRY)
	{
	
		//	if ($ENTRY[0]!='CAPSD_HEVCT')continue;

		++$N_PROCESS;
		echo "###### ".$ENTRY[0]."\t".$N_PROCESS."\n";
	 	processEntry($ENTRY,$fpO,$fpE);

		/// Cleaning up memory for optimal use
		gc_collect_cycles();
		
		++$NTEST;
		// if ($NTEST==100)break;
		
	  }

	echo "END\t".$N_PROCESS."\n";
	
	fclose($fpO);


?>
