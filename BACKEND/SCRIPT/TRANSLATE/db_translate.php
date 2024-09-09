<?php
ini_set('memory_limit','1000M');
/**
 SCRIPT NAME: db_translate
 PURPOSE:     Insert new mRNA to protein translation data
 
*/

/// Job name - Do not change
$JOB_NAME='db_translate';

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
	/// Get parent info:
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_translate')];

	/// Define working directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												   if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);


	/// Update process control directory so the next script knows which directory to work on
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
addLog("Static file check");

	/// Those are the two tables we are going to insert into and their column order
	$COL_ORDER=array(
		'tr_protseq_al'		=>'(tr_protseq_al_id,prot_seq_id,transcript_id,from_uniprot,perc_sim,perc_iden,perc_sim_com    ,perc_iden_com   )',
		'tr_protseq_pos_al' =>'(tr_protseq_pos_al_id,tr_protseq_al_id,prot_seq_pos_id,transcript_pos_id,triplet_pos)');	

	/// These are for each table the max PK value so we can create primary key values for the new records
	$DBIDS=array('tr_protseq_al'=>-1,
				'tr_protseq_pos_al'=>-1);
				
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$res=array();
		$res=runQuery('SELECT MAX('.$TBL.'_id) co FROM '.$TBL);
		if ($res===false)																failProcess($JOB_ID."005",'Unable to get Max ID for '.$TBL);
		
		$DBIDS[$TBL]=($res[0]['co']=='')?0:$res[0]['co'];
	}


addLog("Create directory");
	if (!is_dir('INSERT') && !mkdir('INSERT'))											failProcess($JOB_ID."006",'Unable to create INSERT directory');
	if (!chdir('INSERT'))																failProcess($JOB_ID."007",'Unable to access INSERT directory');
	

addLog("Process files");
	
	
	$STATS=array('AL'=>0,'AL_P'=>0);
	
	
	/// We run 100 scripts, so we need to loop over each of them
	for($I=0;$I<100;++$I)
	{
		processFile($I);

	}



	successProcess();




function processFile($I)
{
	global $JOB_ID;
	global $DBIDS;
	global $DB_INFO;
	global $GLB_VAR;
	global $COL_ORDER;

	echo "PROCESSING FILE ".$I."\n";
	
	/// FILES will contain the file pointers for each table
	$FILES=array();
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$FILES[$TBL]=fopen($TBL.'_'.$I.'.csv','w');
		if (!$FILES[$TBL])																		failProcess($JOB_ID."A01",'Unable to open '.$TBL.'.csv');
	}
	$PREV_DBIDS=$DBIDS;
	/// Opening result file
	$fp=fopen('../DATA/'.$I.'.json','r');if (!$fp) 												failProcess($JOB_ID."A02",'Unable to open '.$I.'.json');
	while(!feof($fp))
	{
		/// Each line correspond to a gene that can have 0, 1 or many translation, so it can be very long
		$line=stream_get_line($fp,10000000,"\n");
		if ($line=='')continue;
		/// We decode the json query
		$ENTRY=json_decode($line,true);
		if ($ENTRY ===false)																	failProcess($JOB_ID."A03",'Unable to interpret json string');
		if ($ENTRY==array())continue;
		
		//print_r($ENTRY);
		/// And we process it
		processEntry($ENTRY,$FILES);
		//exit;

	}fclose($fp);
	/// Just printing how many rows we insert in the two tables
	echo $PREV_DBIDS['tr_protseq_al']."=>".$DBIDS['tr_protseq_al']."\t".
	$PREV_DBIDS['tr_protseq_pos_al']."=>".$DBIDS['tr_protseq_pos_al']."\n";
	//exit;
	/// Then we insert the content of the files for each table
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
		//	if (in_array($NAME,$TO_FILTER))continue;
		echo $NAME."\n";
	
	
		addLog("inserting ".$NAME." records");
		$res=array();
		fclose($FILES[$NAME]);
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME."_".$I.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		$FILES[$NAME]=fopen($NAME.'.csv','w');
		if ($return_code !=0 )																	failProcess($JOB_ID."A04",'Unable to insert data in '.$NAME); 
	}
	echo "##############\n##############\n##############\n##############\n";
}
function processEntry(&$ENTRY,&$FILES)
{
	
	
	global $DBIDS;
	global $STATS;	
	
	foreach ($ENTRY as &$SEQAL)
	{
		/// Inserting translation summary
		++$DBIDS['tr_protseq_al'];
		$STATS['AL']++;
		fputs($FILES['tr_protseq_al'],
			$DBIDS['tr_protseq_al']."\t".
			$SEQAL['INFO'][1]."\t".
			$SEQAL['INFO'][0]."\tfalse\t".
			$SEQAL['INFO'][3]."\t".
			$SEQAL['INFO'][2]."\t".
			$SEQAL['INFO'][5]."\t".
			$SEQAL['INFO'][4]."\n");
		
		if (!isset($SEQAL['AL']))continue;
		/// Inserting individual nucleotide to amino-acid mapping

		$STR='';
		foreach ($SEQAL['AL'] as &$POS)
		{
			$STATS['AL_P']++;
			
			/// We only consider proper match (i.e no gap)
			if ($POS[2]==''|| $POS[0]=='')continue;	
			
			++$DBIDS['tr_protseq_pos_al'];
			
			$STR.=
				$DBIDS['tr_protseq_pos_al']."\t".
				$DBIDS['tr_protseq_al']."\t".
				$POS[2]."\t".
				$POS[0]."\t".
				$POS[1]."\n";
		}
		fputs($FILES['tr_protseq_pos_al'],$STR);
	}
}


?>

