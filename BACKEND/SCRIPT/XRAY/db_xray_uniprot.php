<?php

/**
 SCRIPT NAME: db_xray_uniprot
 PURPOSE:     Look up process entries to check if any uniprot record is missing
 
*/
error_reporting(E_ALL);
$JOB_NAME='db_xray_uniprot';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
require_once($TG_DIR.'/BACKEND/SCRIPT/XRAY/xray_functions.php');
addLog("Access directory");
$CK_INFO=$GLB_TREE[getJobIDByName('rmj_xray')];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';  		 if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$E_DIR=$W_DIR.'/ENTRIES';
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];


	addLog("Get List of current entries");

	$fp=fopen('LIST_TO_PROCESS','r');
	if(!$fp)failProcess($JOB_ID."005",'Unable to open LIST_TO_PROCESS');
	$N_ENTRY=0;$START=false;$SUCCESS=array('DB_LOAD'=>0,'BLASTP'=>0);
	$ORDER=array('download'=>'GET_STRUCTURE','prepare'=>'PDB_PREP','pdb_sep'=>'PDB_SEP','db_load'=>'PDB_SEP','blastp'=>'BLASTP','blastp_load'=>'BLASTP');//,'VOLSITE','CLUSTERING');
	
	
	$MISSING_U=array();
	while(!feof($fp))
	{
		$PDB_ID=stream_get_line($fp,1000,"\n");
		++$N_ENTRY;
		
		//if ($PDB_ID=='4EFE'){$START=true;continue;}
		//if (!$START)continue;
		if ($N_ENTRY%1000==0)echo $N_ENTRY."\t".count($MISSING_U)."\n";
		//echo "##################\n".$PDB_ID."\t".$N_ENTRY."\t";
		
		$ENTRY=loadEntry($PDB_ID,$E_DIR);
		if (isset($ENTRY['MISSING_UNIP']))
		foreach ($ENTRY['MISSING_UNIP'] as $UNIP)$MISSING_U[$UNIP][]=$PDB_ID;
		
		
	}
	fclose($fp);
	$fpO=fopen('MISSING_UNIPROT','w');
	if(!$fp)failProcess($JOB_ID."006",'Unable to open MISSING_UNIPROT');
	foreach ($MISSING_U as $U=>&$LIST)fputs($fpO,$U."\t".implode("\t",$LIST)."\n");
	fclose($fpO);
	successProcess();
?>
