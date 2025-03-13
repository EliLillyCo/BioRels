<?php

error_reporting(E_ALL);
ini_set('memory_limit','5000M');

/**
 SCRIPT NAME: db_insert_dom_sim
 PURPOSE:     Insert sdomain alignment statistics and amino-acid pairs in the database
 
*/
$JOB_NAME='db_insert_dom_sim';

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
	/// Get parent job information
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_dom_sim')];

	/// Setting up directory path:
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 					failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$U_DIR);
	$U_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$U_DIR);
	
	$W_DIR=$U_DIR.'/JSON/';						if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create job dir '.$W_DIR);
	if (!chdir($W_DIR)) 																failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);

	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory:".$W_DIR);


	/// $DBIDS is an array that will store the last id used for each table
	$DBIDS=array(
		'prot_dom_al'=>-1,
		'prot_dom_al_seq'=>-1
	);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."006",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}
	

	
	/// $COL_ORDER is an array that will store the order of the columns in the csv files
	/// It also provides the order of the files to be processed
	$COL_ORDER=array(
		'prot_dom_al'=>'(prot_dom_al_id , prot_dom_ref_id , prot_dom_comp_id , perc_sim , perc_identity , length , e_value , bit_score , perc_sim_com , perc_identity_com)',
		'prot_dom_al_seq'=>'(prot_dom_al_seq_id , prot_dom_al_id , prot_dom_seq_id_ref , prot_dom_seq_id_comp )',
	);

	/// New records are going to be stored in these files
	/// Key is the table name and value is the file pointer
	$FILES=array();
	/// We are going to store the status of the files
	/// Key is the table name and value is a boolean
	/// True if new data has been added to the file
	$FILE_STATUS=array();
	

	//// Here we are going to loop over all the json files for both SEQ and DOM sequence alignments
	
	$VALID_ALL=true;
	
	for ($Ijob=0;$Ijob<50;++$Ijob)
	{

		/// In blast, a pair of sequences can appear multiple times because multiple matches have been found
		//// but since we do a sequence alignment which takes the whole sequence, we don't repeat it
		print_r($DBIDS);
		
	
		echo "####################### START FILE : Domain ".$Ijob."\n";

		/// We open the files in which we are going to put the records that needs to be added to the database
		$FILES=array();
		foreach ($COL_ORDER as $TBL=>$CTL)
		{
			$FILE_STATUS[$TBL]=false;
			$FILES[$TBL]=fopen($TBL.'.csv','w');
			if (!$FILES[$TBL])																failProcess($JOB_ID."007",'Unable to open DOM.csv');
		}
		$VALID=true;
		$N=0;


		/// We open the json file
		$fp=fopen('JOB_DOM_'.$Ijob.'.json','r');if (!$fp)					failProcess($JOB_ID."008",'Unable to open JOB_DOM_'.$Ijob.'.json');
		$fpO=fopen('failed','w');if (!$fpO)													failProcess($JOB_ID."009",'Unable to open failed');
		$STR_F='';
		$STR_S='';
		

		$DONE=array();
		
		$TIMES=array('JSON'=>0,'SAVE'=>0,'PRE'=>0,'SCRIPT'=>0,'READ'=>0);
		$BULK=array();
		while(!feof($fp))
		{
			++$N;
			$time=microtime_float();
			/// Because each line is a json record, 
			///the line can be pretty long, reason why we put a large cut-off for the number of characters
			
			$line=stream_get_line($fp,2000000,"\n");
			$TIMES['READ']+=microtime_float()-$time; $time=microtime_float();
			if ($line=='')continue;
			
			/// Decode it
			$ENTRY_T=json_decode($line,true);
			if ($ENTRY_T===false)															failProcess($JOB_ID."010",'Unable to decode json string'."\n".$line."\n");
			
			$BULK[]=$ENTRY_T;
			
			
			//f ($N%50==0)print_r($TIMES);
			
			if (count($BULK)<5000)continue;

			if (!processBulk($BULK,$Ijob,$N)) $VALID_ALL=false;


		}
		fclose($fp);
		echo "\n";
		/// then we insert
		if (!processBulk($BULK,$Ijob,$N)) $VALID_ALL=false;

	}
	if ($VALID_ALL)successProcess();
	else failProcess($JOB_ID."011",'Some process has failed');
	






function processBulk(&$BULK,$Ijob,$N)
{
	global $DBIDS;
	global $JOB_ID;
	global $TIMES;
	global $FILES;
	global $COL_ORDER;
	global $GLB_VAR;
	global $DB_INFO;

	echo "####################### FILE : DOM ".$Ijob."\n";
	$PREV_DB=$DBIDS;
	echo "##### START BULK\t".$N."\n";

	/// Getting identifiers and ensuring we have them in the database
	$LIST_ID=array();
	foreach ($BULK as &$B)
	{
		if (!isset($B['prot_dom_ref_id'])||!isset($B['prot_dom_comp_id']))continue;
		$LIST_ID[$B['prot_dom_ref_id']]=false;
		$LIST_ID[$B['prot_dom_comp_id']]=false;
	}
	
	if ($LIST_ID!=array())
	{
		$res=runQuery('SELECT prot_dom_id 
		FROM prot_dom 
		WHERE prot_dom_id IN ('.implode(',',array_keys($LIST_ID)).')');
		if ($res===false)																failProcess($JOB_ID."A01",'Unable to query for existing dom ids ');
		foreach ($res as $line)
		{
			$LIST_ID[$line['prot_dom_id']]=true;
		}
	}

	$STR_F='';
	$STR_S='';
	$VALID=true;
	foreach ($BULK as &$ENTRY)
	{

		$TIMES['JSON']+=microtime_float()-$time; $time=microtime_float();
		//print_r($ENTRY);

		
		$TIMES['SAVE']+=microtime_float()-$time; $time=microtime_float();
		/// In blast, a pair of sequences can appear multiple times because multiple matches have been found
		//// but since we do a sequence alignment which takes the whole sequence, we don't repeat it
		if (isset($DONE[$ENTRY['prot_dom_ref_id']][$ENTRY['prot_dom_comp_id']]))continue;
		
		$DONE[$ENTRY['prot_dom_ref_id']][$ENTRY['prot_dom_comp_id']]=true;
		
		$TIMES['PRE']+=microtime_float()-$time; $time=microtime_float();
		
		/// We check if we already had this record in the database
		if ($LIST_ID[$ENTRY['prot_dom_ref_id']]==false||$LIST_ID[$ENTRY['prot_dom_comp_id']]==false)
		{
			echo "INVALID\t";
			echo $ENTRY['prot_dom_ref_id']."::".$LIST_ID[$ENTRY['prot_dom_ref_id']]."\t".
				$ENTRY['prot_dom_comp_id']."::".$LIST_ID[$ENTRY['prot_dom_comp_id']]."\t";
			echo "\n";
			continue;
		}

		/// Nope => insert it
		if ($ENTRY['DB_STATUS']=='TO_INS')
		{
			
			$DBIDS['prot_dom_al']++;
			$ENTRY['prot_dom_al_id']=$DBIDS['prot_dom_al'];
			
			$STR_F.=$ENTRY['prot_dom_al_id']
			."\t".$ENTRY['prot_dom_ref_id']
			."\t".$ENTRY['prot_dom_comp_id']
			."\t".$ENTRY['perc_sim']
			."\t".$ENTRY['perc_identity']
			."\t".$ENTRY['length']
			."\t0\t0\t".$ENTRY['perc_sim_com']
			."\t".$ENTRY['perc_identity_com']."\n";
			
		}
		else if ($ENTRY['DB_STATUS']=='VALID'){}
		else if ($ENTRY['DB_STATUS']=='TO_UPD')
		{
			$query='UPDATE prot_dom_al SET perc_sim='.$ENTRY['perc_sim'].','.
			'perc_identity='.$ENTRY['perc_identity'].','.
			'length='.$ENTRY['length'].','.
			'perc_sim_com='.$ENTRY['perc_sim_com'].','.
			'perc_identity_com='.$ENTRY['perc_identity_com'].' '.
			'WHERE prot_dom_al_id='.$ENTRY['prot_dom_al_id'];
			if (!runQueryNoRes($query))
			{
				echo "FAILED UPDATE\t".$query."\n";
				$VALID=false;
				break;
			}
			

		}
		else {print_r($ENTRY);exit;}
		/// If we need to insert or update it, then we also need to update the alignment itself
		if ($ENTRY['SEQ_STATUS']=='TO_INS'||$ENTRY['SEQ_STATUS']=='TO_UPD')
		{
			foreach ($ENTRY['ALIGN'] as $AL)
			{
				$DBIDS['prot_dom_al_seq']++;
				// fputs($FILES['prot_dom_al_seq'],
				// $DBIDS['prot_dom_al_seq']."\t".
				// $ENTRY['prot_dom_al_id']."\t".
				// $AL[0]."\t".$AL[1]."\n");
				$STR_S.=$DBIDS['prot_dom_al_seq']."\t".
				$ENTRY['prot_dom_al_id']."\t".
				$AL[0]."\t".$AL[1]."\n";
			}
		}else if ($ENTRY['SEQ_STATUS']=='VALID')continue;
		else 
		{
			echo "ISSUE\n";
			print_r($ENTRY);
			exit;
		}
		
		$TIMES['SCRIPT']+=microtime_float()-$time; $time=microtime_float();
	}

	$BULK=array();
	$LIST_ID=array();
	
	/// Saving results to files:
	fputs($FILES['prot_dom_al'],$STR_F);$STR_F='';
	fputs($FILES['prot_dom_al_seq'],$STR_S);$STR_S='';


	foreach ($FILES as $F)fclose($F);
	
	/// If we are still good -> we insert
	if ($VALID)
	{
		$ERROR_MSG=array();
		foreach ($COL_ORDER as $NAME=>$CTL)
		{
		//	if (in_array($NAME,$TO_FILTER))continue;
		
			addLog("inserting ".$NAME." records");
			$res=array();
			//fclose($FILES[$NAME]);
			$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$ERROR_MSG,$return_code);
			if ($return_code !=0 )
			{
				print_r($ERROR_MSG);
				$VALID=false;
				break;
			}
			
		}
	}
	/// The VALID here is important. We don't want to have a half sequence or just the stats and no alignment
	/// so it's either everything or nothing.

	echo ($VALID)?"SUCCESS":"FAILURE";
	echo "\n";
	$FILES=array();
	foreach ($COL_ORDER as $TBL=>$CTL)
	{
		$FILES[$TBL]=fopen($TBL.'.csv','w');
		if (!$FILES[$TBL])																failProcess($JOB_ID."A02",'Unable to open dom.csv');
	}
	if ($VALID)return true;
	$VALID_ALL=false;
	$res=runQueryNoRes('DELETE FROM prot_dom_al WHERE prot_dom_al_id>='.$PREV_DB['prot_dom_al']);
	///Saving 
	foreach ($ERROR_MSG as $MSG)fputs($fpE,"ERROR\t".$MSG."\n");
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
	
		$fpI=fopen($FILES[$NAME],'r');
		if ($fpI)
		while(!feof($fpI))
		{
			$line=stream_get_line($fpI,10000,"\n");
			fputs($fpE,$NAME."\t".$line."\n");
		}
		fclose($fpI);
		
	}
	return false;
	

}


?>
