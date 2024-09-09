<?php
ini_set('memory_limit','3000M');
/**
 SCRIPT NAME: db_publi_gene
 PURPOSE:     Query pubmed for publications related to genes
 
*/
$JOB_NAME='db_publi_gene';

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
	$fpFAILED=fopen('FAILED_SEARCH_GENE','w'); if(!$fpFAILED)							    failProcess($JOB_ID."003",'Unable to open FAILED_SEARCH');
	
	
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

addLog("Get List Genes");
	 processAllGenes();



successProcess();









function processAllGenes()
{
	global $JOB_ID;
	global $EXEMPT;
	


	$NEW_ENTRIES=array();

	
	addLog("Get List Genes");		


	$LIST_GN_ENTRY=array();
	$res=array();
	/// We get the list of genes that are Human and not LOC with their synonyms
	$res=runQuery("SELECT gn_entry_Id,syn_value 
		FROM gn_syn gs, gn_syn_map gsm
		WHERE gsm.gn_syn_id = gs.gn_syn_id 
		AND gn_entry_id IN 
			(select DISTINCT gn_entry_Id 
			FROM mv_gene 
			WHERE tax_Id='9606'
			AND symbol NOT LIKE 'LOC%') 
		ORDER BY gn_entry_id ASC");
	if ($res===false)															    failProcess($JOB_ID."A01",'Unable to get the list of genes');

	$LIST_GN_ENTRY=array();
	foreach ($res as $tab)
	{
		/// We ensure the gene synonym is not exempt
		if (isset($EXEMPT[$tab['syn_value']]) && $EXEMPT[$tab['syn_value']]=="")	continue;
		if (preg_match('/^[A|C|D|E|F|G|H|I|K|L|M|N|P|Q|R|S|T|V|W|Y]{1}[0-9]{1,4}$/',$tab['syn_value'],$matches)==1)continue;

		$LIST_GN_ENTRY[$tab['gn_entry_id']][$tab['syn_value']]=true;
	}
	echo "NUMBER OF GENES: ".count($LIST_GN_ENTRY)."\n";
	
	$NG=0;
	
	foreach ($LIST_GN_ENTRY as $GN_ENTRY_ID=>$S)
	{
		++$NG;
	
		echo "\n".$NG."/".count($LIST_GN_ENTRY)."\t".$GN_ENTRY_ID."\n";
		if (!processGene($GN_ENTRY_ID,$S))$FAILED[$GN_ENTRY_ID]=$S;
	}	
	

	addLog("delete table");
	echo "DELETE\n";
	if (!runQueryNoRes("DROP TABLE IF EXISTS MV_GENE_PUBLI"))																failProcess($JOB_ID."A02",'Unable to delete MV_GENE_PUBLI'); 

	addLog("create table");
	echo "CREATE\n";
	if (!runQueryNoRes("CREATE TABLE MV_GENE_PUBLI AS 
		SELECT GN_ENTRY_ID,PE.PMID_ENTRY_ID,PMID,PUBLICATION_DATE
		FROM PMID_ENTRY PE, PMID_GENE_MAP PGM
		WHERE PGM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID 
		ORDER BY GN_ENTRY_ID ASC,PUBLICATION_DATE DESC"))																	failProcess($JOB_ID."A03",'Unable to create MV_GENE_PUBLI'); 


	if (!runQueryNoRes("CREATE INDEX MV_GENE_PUBLI_IDX1 ON MV_GENE_PUBLI (GN_ENTRY_ID,PUBLICATION_DATE)")	)				failProcess($JOB_ID."A04",'Unable to create index1 MV_GENE_PUBLI'); 
	if (!runQueryNoRes("CREATE INDEX MV_GENE_PUBLI_IDX2 ON MV_GENE_PUBLI (GN_ENTRY_ID,PMID)")			)					failProcess($JOB_ID."A05",'Unable to create index2  MV_GENE_PUBLI'); 
}



function processGene($GN_ENTRY_ID,$LISTS)
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
	$PUB_QUERY='("gene" or "protein") AND (';
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

		/// And we query different type of pubmed descriptors
		$PUB_QUERY.='"'.$L.'"[TIAB] OR "'.$L.'"[TW] OR "'.$L.'"[MH] OR ';
	}
	$PUB_QUERY=substr($PUB_QUERY,0,-4).')'.$ADD;

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
	$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'gn.csv');

	/// If the query failed we try again 5 times
	/// If it still fails we store the query in the failed file
	if (!$result['SUCCESS'])
	{
		$TEST=0;
		for ($TEST=0;$TEST<5;++$TEST)
		{
			sleep(1);
			$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'gn.csv');
			if ($result['SUCCESS'])break;
		}
		
	}
	if (!$result['SUCCESS'])
	{
		fputs($fpFAILED,$GN_ENTRY_ID."\t".$PUB_QUERY."\n");
		return;
	}

	/// We have the list of publications from pubmed
	/// Now we need to check which ones are already in the system
	$LIST_T=&$result['LIST'];
	echo "NUMBER OF PUBLICATIONS: ".count($LIST_T)."\t";

	$res=array();
	$res=runQuery('SELECT pmid 
			FROM pmid_gene_map PM, pmid_entry PE 
			WHERE PE.pmid_entry_id= PM.pmid_entry_id
			AND gn_entry_id='.$GN_ENTRY_ID);
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
	$fp=fopen('gene_insert.csv','w');if (!$fp)											 failProcess($JOB_ID."B03",'Unable to open gene_insert.csv');
	foreach ($MISSING as $ID)
	{	
		if (!isset($MAP[$ID]))continue;
		$NEW_ENTRIES[$ID]=true;
		++$N_D;
		fputs($fp,$MAP[$ID]."\t".$GN_ENTRY_ID."\n");
	}
	fclose($fp);


	if ($N_D==0)return true;
	
	/// Insert the new entries
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_gene_map(pmid_entry_id,gn_entry_id) FROM \''."gene_insert.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."B04",'Unable to insert pmid_gene_map'); 


return true;
}




?>

