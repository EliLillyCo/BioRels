<?php
ini_set('memory_limit','3000M');
/**
 SCRIPT NAME: db_publi_drug
 PURPOSE:     Query pubmed for publications related to drugs
 
*/
$JOB_NAME='db_publi_drug';

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

addLog("Set up directory");

	/// Get parent job information
	$DL_PUBMED_INFO=$GLB_TREE[getJobIDByName('dl_pubmed')];

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_PUBMED_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 																	failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 																	failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);


	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$DL_PUBMED_INFO['TIME']['DEV_DIR'];
	

addLog("Get last refresh date");
	/// Get the last refresh date from prd_pubmed job
	$PRD_PUBLI=$GLB_TREE[getJobIDByName('prd_pubmed')];
	$PRD_DATE=$PRD_PUBLI['TIME']['DEV_DIR'];

	/// If you wish to reprocess all:
	// $PRD_DATE=-1;

	/// All queries that failed will be stored in this file:
	$fpFAILED=fopen('FAILED_SEARCH_DRUG','w'); if(!$fpFAILED)							    failProcess($JOB_ID."003",'Unable to open FAILED_SEARCH');
	
	
	$PRD_DATE_TIMESTAMP=0;
	/// Here we convert the PRD_DATE to a timestamp to compare to today's date
	if ($PRD_DATE!=-1) {
		$now = time(); 
		$PRD_DATE_TIMESTAMP = strtotime($PRD_DATE)-60*60*24*7;///We add a week just to be safe
	}
	

addLog("Get Exemption List");	
	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$JOB_INFO['DIR'];								    	
	$GENE_RULES_FILE	  =$STATIC_DIR.'/PUBLI_GENE_RULE.csv';
	if (!checkFileExist($GENE_RULES_FILE))											    failProcess($JOB_ID."004",'Missing PUBLI_GENE_RULE.csv setup file ');

	$EXEMPT=array();
	$fp=fopen($GENE_RULES_FILE,'r');if (!$fp)								    failProcess($JOB_ID."005",'Unable to open PUBLI_GENE_RULE.csv file ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line==""||$line[0]=="#")continue;
		$tab=array_values(array_filter(explode("\t",$line)));
		$EXEMPT[$tab[0]]=$tab[1];
	}
	fclose($fp);


 processAllDrug();



 successProcess();











function processAllDrug()
{

	global $JOB_ID;
	
	// Get all the drugs with their names from drug_entry
	$NEW_ENTRIES=array();
	$res=runQuery("SELECT drug_entry_id,drug_primary_name FROM drug_entry	");
	if ($res===false)										    									failProcess($JOB_ID."A01",'Unable to get drug names ');
	foreach ($res as $line)
	{
		$NEW_ENTRIES[$line['drug_entry_id']][]=$line['drug_primary_name'];
	}

	/// Select all alternative names
	$res=runQuery("SELECT drug_entry_id,drug_name FROM drug_name	");
	if ($res===false)										    									failProcess($JOB_ID."A02",'Unable to get drug names ');
	foreach ($res as $line)
	{
		$NEW_ENTRIES[$line['drug_entry_id']][]=$line['drug_name'];
	}


	$res=runQuery("SELECT drug_entry_id,drug_extdb_value FROM drug_extdb	");
	if ($res===false)										    									failProcess($JOB_ID."A03",'Unable to get drug names ');
	foreach ($res as $line)
	{
		$NEW_ENTRIES[$line['drug_entry_id']][]=$line['drug_extdb_value'];
	}
	
	/// Getting names from small molecule databases:
	$res=runQuery("SELECT drug_entry_id, sm_name 
					FROM drug_mol_entity_map dmem, molecular_entity me, sm_Entry se,sm_source ss 
					where dmem.molecular_entity_id = me.molecular_entity_id 
					AND me.molecular_structure_hash = se.md5_hash 
					and se.sm_entry_Id = ss.sm_entry_Id ");
	if ($res===false)										    									failProcess($JOB_ID."A04",'Unable to get drug names from sm_source ');
	foreach ($res as $line)$NEW_ENTRIES[$line['drug_entry_id']][]=$line['sm_name'];

	
	
	echo "NUMBER OF DRUGS: ".count($NEW_ENTRIES)."\n";
	if ($NEW_ENTRIES==array())return;
	
	$NG=0;
	/// Processing each of them
	foreach ($NEW_ENTRIES as $ENTRY_ID=>$S)
	{
		++$NG;
	

		echo "\n".$NG."/".count($NEW_ENTRIES)."\t".$ENTRY_ID."\n";
		processDrug($ENTRY_ID,$S);
	}	




	addLog("delete table");
	echo "DELETE\n";
	if (!runQueryNoRes("DROP TABLE IF EXISTS MV_DRUG_PUBLI"))										failProcess($JOB_ID."A06",'Unable to delete MV_DRUG_PUBLI'); 

	addLog("create table");
	echo "CREATE\n";
	if (!runQueryNoRes("CREATE TABLE MV_DRUG_PUBLI AS 
		SELECT DRUG_ENTRY_ID,PE.PMID_ENTRY_ID,PMID,PUBLICATION_DATE
		FROM PMID_ENTRY PE, PMID_DRUG_MAP PGM
		WHERE PGM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID 
		ORDER BY DRUG_ENTRY_ID ASC,PUBLICATION_DATE DESC"))											failProcess($JOB_ID."A07",'Unable to create MV_DRUG_PUBLI'); 


	if (!runQueryNoRes("CREATE INDEX MV_DRUG_PUBLI_IDX1 ON MV_DRUG_PUBLI (DRUG_ENTRY_ID,PUBLICATION_DATE)")	)	failProcess($JOB_ID."A08",'Unable to create index1 MV_DRUG_PUBLI'); 
	if (!runQueryNoRes("CREATE INDEX MV_DRUG_PUBLI_IDX2 ON MV_DRUG_PUBLI (DRUG_ENTRY_ID,PMID)")			)		failProcess($JOB_ID."A09",'Unable to create index2  MV_DRUG_PUBLI'); 
}




/// LISTS => List of names
function processDrug($DRUG_ENTRY_ID,$LISTS)
{
	global $NEW_ENTRIES;
	global $GLB_VAR;
	global $DB_INFO;
	global $PRD_DATE_TIMESTAMP;
	global $JOB_ID;
	global $EXEMPT;
	global $fpFAILED;

	if (count($LISTS)==0)return;
	
	
	/// We will store the list of publications we found
	$LIST_ALL=array();
	
	
	//print_r($LISTS);
	$CURR_LEN=200;
	$CHUNKS_NAME=array();
	$CHUNK_GROUP=0;
	/// We try to ensure that the queries are not too long, so we work by length
	foreach ($LISTS as $L)
	{
		if (strlen($L)<4)continue;
		if (is_numeric($L))continue;
		$NEW_LEN=$CURR_LEN+strlen($L)*3+50;
		echo $CURR_LEN.' ' .$NEW_LEN."\n";
		/// To ensure that it's not more than 2K characters
		if ($NEW_LEN<2000)
		{
			$CURR_LEN=$NEW_LEN;
			$CHUNKS_NAME[$CHUNK_GROUP][]=$L;	
		}
		else{
			$CURR_LEN=200+strlen($L)*3+30;
			$CHUNK_GROUP++;
			$CHUNKS_NAME[$CHUNK_GROUP][]=$L;
		}
	}

		
	foreach ($CHUNKS_NAME as $CHUNK)
	{
			/// Here we build the query
		$PUB_QUERY='(';
		$P=0;
		$ADD='';$INCLUDE=false;
		foreach ($CHUNK as $V=>$L)
		{
			++$P;
			
			/// For those in the exemption list:
			if (isset($EXEMPT[$L]))
			{
				/// If there's no alternative proposed, we skip
				if ($EXEMPT[$L]=='""')continue;
				
				/// Otherwise we add the alternative
				$ADD.=' '.$EXEMPT[$L];
			}
			$L=str_replace("&","",$L);
			$INCLUDE=true;
			/// If the word is long - we only search for the word /// And we query different type of pubmed descriptors
			if (strlen($L)>5) $PUB_QUERY.='"'.$L.'"[TIAB] OR "'.$L.'"[TW] OR "'.$L.'"[MH] OR ';
			else
			{
				/// Otherwise we search for the word with Drug or molecule postfix to ensure accuracy
				$PUB_QUERY.='"'.$L.' drug"[TIAB] OR "'.$L.' drug"[TW] OR "'.$L.' drug"[MH] OR ';
				$PUB_QUERY.='"'.$L.' molecule"[TIAB] OR "'.$L.' molecule"[TW] OR "'.$L.' molecule"[MH] OR ';
			}
		}
		
		$PUB_QUERY=substr($PUB_QUERY,0,-4).')'.$ADD;
		echo "ADD:".$ADD."\n";
		echo "\n";
		echo $PUB_QUERY."\n";
		$PUB_QUERY=str_replace("'","'\"'\"'",$PUB_QUERY);
		
		
		/// Querying
		$result=array();
		$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'dr.csv');
		
		/// If the query failed - sometimes for timeout, we try again
		if (!$result['SUCCESS'])
		{
			$TEST=0;
			for ($TEST=0;$TEST<5;++$TEST)
			{
				sleep(1);
				$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'dr.csv');
				if ($result['SUCCESS'])break;
			}
			
		}
		if (!$result['SUCCESS'])
		{
			fputs($fpFAILED,$DUR_ENTRY_ID."\t".$PUB_QUERY."\n");
			echo" Unable to query "."\n";
			return;
		}
		/// We add the list of publications to the list
		$LIST_T=&$result['LIST'];
		foreach ($LIST_T as $ID=>$V)
		{
			/// By using the id as key, we ensure that we don't have duplicates
			$LIST_ALL[$ID]=false;
		}
	}


	/// Now that we did all the query, we will check which ones are already in the system
	echo "NUMBER OF PUBLICATIONS: ".count($LIST_ALL)."\n";
		
	$res=array();
	$res=runQuery('SELECT pmid 
			FROM pmid_drug_map PM, pmid_entry PE 
			WHERE PE.pmid_entry_id= PM.pmid_entry_id
			AND drug_entry_id='.$DRUG_ENTRY_ID);
	if ($res===false)														    failProcess($JOB_ID."B01",'Unable to query for existing pmid map ');
	
	
	$LIST_E=array();
	$N_F=0;
	
	foreach ($res as $tab)
	{
		$LIST_E[$tab['pmid']]=isset($LIST_ALL[$tab['pmid']]);
		if (!$LIST_E[$tab['pmid']])continue;
		
		$LIST_ALL[$tab['pmid']]=true;
		++$N_F;
		
	}
	echo "NUMBER OF PUBLICATIONS ALREADY IN THE SYSTEM: ".count($LIST_E)."\n";
	echo "NUMBER OF PUBLICATIONS OVERLAPPING: ".$N_F."\n";
	
	/// We list the missing ones:
	$MISSING=array();
	foreach ($LIST_ALL as $ID=>&$STATUS)
	{
		if ($STATUS)continue;
		$MISSING[]=$ID;
	}
	
	/// Split the missing list in chunks of 30K
	$CHUNKS=array_chunk($MISSING,30000);
	$MAP=array();
	
	/// Getting the pmid_Entry_id for the missing ones
	foreach ($CHUNKS as $K=>$CHUNK)
	{
		echo "GETTING ".$K."/".count($CHUNKS)." IDs\n";
		$res=array();
		$res=runQuery('SELECT pmid,pmid_entry_id
			FROM  pmid_entry PE 
			WHERE pmid IN ('.implode(',',$CHUNK).')',$res);
		if ($res===false)														    failProcess($JOB_ID."B02",'Unable to query for pmid ids ');
		
		foreach ($res as $t)
		{
			$MAP[$t['pmid']]=$t['pmid_entry_id'];
		}
	}
	echo "NUMBER OF PUBLICATION FOUND: ".count($MAP)."\n";

	/// Insert the missing ones into a file:
	$N_D=0;
	$fp=fopen('drug_insert.csv','w');if (!$fp)											 failProcess($JOB_ID."B03",'Unable to open drug_insert.csv');
	foreach ($MISSING as $ID)
	{	
		
		if (!isset($MAP[$ID]))continue;
		$NEW_ENTRIES[$ID]=true;
		++$N_D;
		fputs($fp,$MAP[$ID]."\t".$DRUG_ENTRY_ID."\n");
	}
	fclose($fp);
	
	if ($N_D==0)return;
	
	/// Insert the missing ones into the database
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_drug_map(pmid_entry_id,drug_entry_id) FROM \''."drug_insert.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."B04",'Unable to insert pmid_drug_map'); 

	

}




?>