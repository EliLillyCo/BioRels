<?php

/**
 SCRIPT NAME: db_${DATASOURCE}
 PURPOSE:     Insert new ${DATASOURCE} data
 
*/
ini_set('memory_limit','4000M');
error_reporting(E_ALL);

/// Job name - Do not change
$JOB_NAME='db_${DATASOURCE}';

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




addLog("Access directory");

	/// GEt parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('${DB_PARENT}')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	
												   if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	/// Update process control directory to the current release so that the next job can use it								   
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory: ".$W_DIR);

	
addLog("Database pre-load data");
	/// If you need to load static data, i.e. data from table that you are going to need because of foreign key constraints, but that you are not modifying
	/// You can preload them
	$STATIC_DATA=array();
	preloadData();


addLog("Get MAx DBIDS")	;
	/// For each table that we are going to insert into, we want to know the highest primary key value to do quick insertion
	$DBIDS=array(
	// 	'prot_entry'=>-1,
	// 'prot_ac'=>-1,
	// 'prot_seq'=>-1,
	// 'prot_dom'=>-1,
	
	);


	///	Everytime we have a new record, we update $FILE_STATUS to true for the given file.
	$FILE_STATUS=array();
	$FILE_VALID=$DBIDS;
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) CO FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."005",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
		$FILE_STATUS[$TBL]=0;
	}
	$DBIDS['DESC_FILES']=0;
	
	$FILES=array();
	foreach ($COL_ORDER as $TYPE=>$CTL)
	{
		$FILES[$TYPE]=fopen($TYPE.'.csv','w');
		if (!$FILES[$TYPE])														failProcess($JOB_ID."010",'Unable to open '.$TYPE.'.csv');
	}


	/// To insert properly, we need to provide the column order for each table
	$COL_ORDER=array(
		/// Table as key, order of columns as value, in brackets	
		// 	'prot_entry'=>'(prot_entry_id, prot_identifier, date_created, date_updated, status, taxon_id , confidence)',
		// 'prot_ac'=>'(prot_ac_id,prot_entry_Id, ac,is_primary)',
		// 'prot_seq'=>'(prot_seq_id,prot_entry_Id,iso_name,iso_id,is_primary,description,modification_date,note)',
		// 'prot_dom'=>'(prot_dom_id,prot_entry_id,domain_name,modification_date,domain_type,pos_start,pos_end)',
		);



addLog("Open files");
	if (!is_dir('INSERT') && !mkdir('INSERT'))										failProcess($JOB_ID."006",'Unable to create INSERT directory');
	if (!chdir('INSERT'))															failProcess($JOB_ID."007",'Unable to access INSERT directory');
	


addLog("Processing data");

		////Process the data from the input file and compare it to whatever you have in the database
		/// Update whichever fields needs to be updated 
		/// And save in the $FILES[] any new record. New records should increment $DBIDS first to assign the proper primary key value.
		/// Every N records, call pushToDb to save the new records in the database
		/// Don't forget to call pushToDb all records have been processed.





successProcess();











function preloadData()
{
	global $STATIC_DATA;
	global $JOB_ID;

	// $res=array();
	// $query="SELECT prot_extdbid, prot_extdbabbr FROM prot_extdb";
	// $res=runQuery($query);
	// if ($res===false)												failProcess($JOB_ID."A01",'Unable to get External databases');
	
	// foreach ($res as $tab) 
	// {
	// 	$STATIC_DATA['EXTDB'][$tab['prot_extdbabbr']]=$tab['prot_extdbid'];
	// }
	
}








function pushToDB()
{
	addLog("Push all to db");
	/// Here we are going to push all the data into the database.
	/// But first, we need to lookup protein names
	global $COL_ORDER;
	global $FILES;
	global $GLB_VAR;
	global $DB_INFO;
	global $FILE_VALID;
	global $FILE_STATUS;
	global $ALL_SUCCESS;
	global $DBIDS;


	
	foreach ($FILE_VALID as $F=>&$V)$V=true;
		
	/// Once it's done, we look over each files, close them, and push them to the database
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
	
		if (!$FILE_VALID[$NAME]){echo "SKIPPING ".$NAME."\t";continue;}
		
		$res=array();
		fclose($FILES[$NAME]);
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		
		//echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		echo $NAME."\t".$FILE_STATUS[$NAME]."\t";
		$res=array();
	
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		if ($return_code !=0 )													failProcess($JOB_ID."B01",'Unable to open '.$TYPE.'.csv');
		
	
		echo $res[0]."\n";
			
		/// Then we clean up, and reopen the files:
		$FILES=array();$N_PROCESSED=0;
		foreach ($COL_ORDER as $TYPE=>$CTL)
		{
			$FILE_STATUS[$TYPE]=0;
			$FILES[$TYPE]=fopen($TYPE.'.csv','w');
			if (!$FILES[$TYPE])														failProcess($JOB_ID."B02",'Unable to open '.$TYPE.'.csv');
			
		}
		
		echo "##############\n##############\n##############\n##############\n";
		print_r($DBIDS);
	}
}


	
	




?>

