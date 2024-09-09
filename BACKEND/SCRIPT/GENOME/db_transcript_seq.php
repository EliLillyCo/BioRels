<?php

/**
 SCRIPT NAME: db_transcript_seq
 PURPOSE:     Push Transcript nucleotides and their annotation to the database

*/

/// Job name - Do not change
$JOB_NAME='db_transcript_seq';


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

/// Get Parent info
$PARENT_INFO=$GLB_TREE[getJobIDByName('db_genome')];
/// Get to working directory
$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];			if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
$W_DIR.='/'.$JOB_INFO['DIR'].'/';   				if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
$W_DIR.=$PARENT_INFO['TIME']['DEV_DIR'];  		   	if (!is_dir($W_DIR) || !chdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);

/// Set process control directory to current date so the next job can access it
$PROCESS_CONTROL['DIR']=$PARENT_INFO['TIME']['DEV_DIR'];



addLog("Working directory:".$W_DIR);


	/// This is the table that will be used to insert the data
	/// The first column is the name of the table
	/// The value is the max id in the table
	$DBIDS=array('transcript_pos'=>-1);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."004",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}


	
	$COL_ORDER=array(
	'transcript_pos'=>'(transcript_pos_id ,transcript_id     ,nucl              ,seq_pos           ,seq_pos_type_id   ,exon_id           ,chr_seq_pos_id)',);
   
addLog("Load data");


for ($I=0;$I<100;++$I)
{
	if (!is_file('JSON/RESULTS_'.$I))
	{
		echo "NO FILE ".$I."\n";
		continue;
	}
	addLog("FILE ".$I);
	$fp=fopen('JSON/RESULTS_'.$I,'r'); if ($fp===false) failProcess($JOB_ID.'005','Unable to open file JSON/RESULTS_'.$I);
	$fpO=fopen('TMP_RESULTS','w');		if ($fpO===false) failProcess($JOB_ID.'006','Unable to open file TMP_RESULTS');
	$N_LINE=0;$N_R=0;
	$STR='';$N_STR=0;
	while(!feof($fp))
	{
		/// Increasing primary key
		++$DBIDS['transcript_pos'];

		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;
		$STR.=$DBIDS['transcript_pos']."\t".$line."\n";
		++$N_STR;
		///Reduce the I/O
		if ($N_STR<1000)continue;
		fputs($fpO,$STR);
		$STR='';
		$N_STR=0;
		++$N_LINE;
		if ($N_LINE<4000000)continue;
		fclose($fpO);
		
		++$N_R;
		echo "FILE ".$I." LOADING ".($N_LINE*($N_R-1)).'->'.($N_LINE*($N_R))."\n";
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.transcript_pos '.$COL_ORDER['transcript_pos'].' FROM \''.'TMP_RESULTS'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		$res=array();
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		print_r($res);
		$N_LINE=0;
		if ($return_code !=0 )
		{
			echo "FAILED\t".$I."\t".($N_LINE*($N_R-1)).'->'.($N_LINE*($N_R))."\n";	failProcess($JOB_ID.'007','Unable to insert '); 
		}
		$fpO=fopen('TMP_RESULTS','w');if (!$fpO)									failProcess($JOB_ID.'008','Unable to open file TMP_RESULTS');
		
	}
	fclose($fp);
	fclose($fpO);
		
	/// Last batch:
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.transcript_pos '.$COL_ORDER['transcript_pos'].' FROM \''.'TMP_RESULTS'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	$res=array();
	exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
	print_r($res);
	
	if ($return_code !=0 )
	{
		echo "FAILED\t".$I."\t".($N_LINE*($N_R-1)).'->'.($N_LINE*($N_R))."\n";	failProcess($JOB_ID.'009','Unable to insert '); 
	}

}
	successProcess();
?>
