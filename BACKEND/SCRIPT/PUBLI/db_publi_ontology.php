<?php
ini_set('memory_limit','3000M');
/**
 SCRIPT NAME: db_publi_ontology
 PURPOSE:     Query pubmed for publications related to ontology terms
 NOTE: 		  Only ontology terms with W_PUBMED=1 will be queried
 
*/
$JOB_NAME='db_publi_ontology';

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
	 $PRD_DATE=-1;

	/// All queries that failed will be stored in this file:
	$fpFAILED=fopen('FAILED_SEARCH_ONTOLOGY','w'); if(!$fpFAILED)							    failProcess($JOB_ID."003",'Unable to open FAILED_SEARCH');
	
	
	$PRD_DATE_TIMESTAMP=0;
	/// Here we convert the PRD_DATE to a timestamp to compare to today's date
	if ($PRD_DATE!=-1) 
	{
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

addLog("Get List Ontology");
	 processAllOntology();



successProcess();









function processAllOntology()
{
	global $JOB_ID;
	global $EXEMPT;
	


	$NEW_ENTRIES=array();

	
	addLog("Get the list of ontology terms");		



	$res=array();
	/// We get the list of genes that are Human and not LOC with their synonyms
	$res=runQuery(" SELECT oe.ontology_entry_id, ontology_name, syn_value
					 from ontology_entry oe, ontology_syn os
					  where oe.ontology_entry_Id = os.ontology_entry_Id
					   AND w_pubmed='T'; ");
	if ($res===false)															    failProcess($JOB_ID."A01",'Unable to get the list of ontology');

	$LIST_ONTOLOGY_ENTRY=array();
	foreach ($res as $tab)
	{
		$LIST_ONTOLOGY_ENTRY[$tab['ontology_entry_id']][$tab['ontology_name']]=true;
		/// We ensure the gene synonym is not exempt
		if (isset($EXEMPT[$tab['syn_value']]) && $EXEMPT[$tab['syn_value']]=="")	continue;

		$LIST_ONTOLOGY_ENTRY[$tab['ontology_entry_id']][$tab['syn_value']]=true;
	}
	echo "NUMBER OF RECORDS: ".count($LIST_ONTOLOGY_ENTRY)."\n";
	
	$NG=0;
	
	foreach ($LIST_ONTOLOGY_ENTRY as $ONTO_ENTRY_ID=>$S)
	{
		++$NG;
		//if ($NG!=58)continue;
		echo "\n".$NG."/".count($LIST_ONTOLOGY_ENTRY)."\t".$ONTO_ENTRY_ID."\n";
		
		if (!processOntology($ONTO_ENTRY_ID,$S))$FAILED[$ONTO_ENTRY_ID]=$S;
	}	

	

	addLog("delete table");
	echo "DELETE\n";
	if (!runQueryNoRes("DROP TABLE IF EXISTS MV_ONTO_PUBLI"))																failProcess($JOB_ID."A02",'Unable to delete MV_ONTO_PUBLI'); 

	addLog("create table");
	echo "CREATE\n";
	if (!runQueryNoRes("CREATE TABLE MV_ONTO_PUBLI AS 
		SELECT ONTOLOGY_ENTRY_ID,PE.PMID_ENTRY_ID,PMID,PUBLICATION_DATE
		FROM PMID_ENTRY PE, PMID_ONTO_MAP PGM
		WHERE PGM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID 
		ORDER BY ONTOLOGY_ENTRY_ID ASC,PUBLICATION_DATE DESC"))																	failProcess($JOB_ID."A03",'Unable to create MV_ONTO_PUBLI'); 


	if (!runQueryNoRes("CREATE INDEX MV_ONTO_PUBLI_IDX1 ON MV_ONTO_PUBLI (ONTOLOGY_ENTRY_ID,PUBLICATION_DATE)")	)				failProcess($JOB_ID."A04",'Unable to create index1 MV_ONTO_PUBLI'); 
	if (!runQueryNoRes("CREATE INDEX MV_ONTO_PUBLI_IDX2 ON MV_ONTO_PUBLI (ONTOLOGY_ENTRY_ID,PMID)")			)					failProcess($JOB_ID."A05",'Unable to create index2  MV_ONTO_PUBLI'); 
}








function processOntology($ONTO_ENTRY_ID,$LISTS)
{
	
	global $NEW_ENTRIES;
	global $GLB_VAR;
	global $DB_INFO;
	global $LAST_DAYS;
	global $PRD_DATE_TIMESTAMP;
	global $EXEMPT;
	global $fpFAILED;
	global $JOB_ID;

	if (count($LISTS)==0)return;
	$DEBUG=false;

	
	

	///creating the query:
	$PUB_QUERY='';
	$ALT_QUERY='';
	$P=0;
	$ADD='';
	foreach ($LISTS as $L=>$V)
	{
		
		++$P;

		/// Removing synonyms that are too short, numeric or in the exemption list
		if (strlen($L)<3)continue;
		if (is_numeric($L))continue;
		if (isset($EXEMPT[$L]))
		{
			/// If the exemption list offers no replacement then we skip
			if ($EXEMPT[$L]=='""')continue;
			/// If the exemption list offers a replacement then we add it to the query
			$ADD.=' '.$EXEMPT[$L];
		}
		$L=str_replace("&","",$L);
		if (substr($L,0,8)=='PUBMED::')$ALT_QUERY=' '.substr($L,8);
		/// And we query different type of pubmed descriptors
		else $PUB_QUERY.='"'.$L.'"[TIAB] OR "'.$L.'"[TW] OR "'.$L.'"[MH] OR ';
	}
	$PUB_QUERY=substr($PUB_QUERY,0,-4).')'.$ADD;
	if ($ALT_QUERY!='')$PUB_QUERY=$ALT_QUERY;
	if ($DEBUG)
	{
	echo "ADD:".$ADD."\n";
	echo "\n";
	}
	echo $PUB_QUERY."\n";



	$PUB_QUERY=str_replace("'","'\"'\"'",$PUB_QUERY);
	//if ($PUBLI_RULE_ID!=1)continue;

	//// We query pubmed for the publications
	$result=array();
	$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'ot.csv');

	/// If the query failed we try again 5 times
	/// If it still fails we store the query in the failed file
	if (!$result['SUCCESS'])
	{
		$TEST=0;
		for ($TEST=0;$TEST<5;++$TEST)
		{
			sleep(1);
			$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'ot.csv');
			if ($result['SUCCESS'])break;
		}
		
	}
	if (!$result['SUCCESS'])
	{
		fputs($fpFAILED,$ONTO_ENTRY_ID."\t".$PUB_QUERY."\n");
		return;
	}

	/// We have the list of publications from pubmed
	/// Now we need to check which ones are already in the system
	$LIST_T=&$result['LIST'];
	echo "NUMBER OF PUBLICATIONS: ".count($LIST_T)."\t";

	$res=array();
	$res=runQuery('SELECT pmid 
			FROM pmid_onto_map PM, pmid_entry PE 
			WHERE PE.pmid_entry_id= PM.pmid_entry_id
			AND ontology_entry_id='.$ONTO_ENTRY_ID);
	if ($res===false)														    failProcess($JOB_ID."B01",'Unable to query for existing pmid map ');
	$LIST_E=array();$N_F=0;
	foreach ($res as $tab)
	{
		$LIST_E[$tab['pmid']]=isset($LIST_T[$tab['pmid']]);
		if ($LIST_E[$tab['pmid']]){$LIST_T[$tab['pmid']]=true;++$N_F;}
	}
	echo "| ALREADY IN THE SYSTEM: ".count($LIST_E)."\t";
	echo "| OVERLAPPING: ".$N_F."\t";


	$MISSING=array();
	foreach ($LIST_T as $ID=>&$STATUS)
	{
		if ($STATUS)continue;
		$MISSING[]=$ID;
	}
	
	$CHUNKS=array_chunk($MISSING,30000);
	$MAP=array();
	echo "| FETCHING FROM DB: ";
	
	/// Query the database for the pmid_entry_id by chunks of 30000
	foreach ($CHUNKS as $K=>$CHUNK)
	{
		echo $K."/".count($CHUNKS)." IDs ; ";
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
	echo "\t|NUMBER OF PUBLICATION FOUND: ".count($MAP)."\n";

	/// Push the new entries, i.e the ones that are not in the system to a file to be inserted
	$N_D=0;
	$fp=fopen('onto_insert.csv','w');if (!$fp)											 failProcess($JOB_ID."B03",'Unable to open gene_insert.csv');
	foreach ($MISSING as $ID)
	{	
		if (!isset($MAP[$ID]))continue;
		$NEW_ENTRIES[$ID]=true;
		++$N_D;
		fputs($fp,$MAP[$ID]."\t".$ONTO_ENTRY_ID."\n");
	}
	fclose($fp);


	if ($N_D==0)return true;
	
	/// Insert the new entries
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_onto_map(pmid_entry_id,ontology_entry_id) FROM \''."onto_insert.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."B04",'Unable to insert pmid_onto_map'); 


	return true;
}




?>