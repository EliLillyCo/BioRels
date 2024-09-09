<?php
ini_set('memory_limit','3000M');
/**
 SCRIPT NAME: db_publi_disease
 PURPOSE:     Process pubmed data
 
*/
$JOB_NAME='db_publi_disease';

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

	///	get Parent info:
	$DL_PUBMED_INFO=$GLB_TREE[getJobIDByName('dl_pubmed')];
	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_PUBMED_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 																	failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$PROCESS_CONTROL['DIR']=$DL_PUBMED_INFO['TIME']['DEV_DIR'];
	if (!chdir($W_DIR)) 																	failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);

addLog("Get last refresh date");

	/// Get the last refresh date based on prd_pubmed
	$PRD_PUBLI=$GLB_TREE[getJobIDByName('prd_pubmed')];
	$PRD_DATE=$PRD_PUBLI['TIME']['DEV_DIR'];

	/// If you wish to reprocess all:
	// $PRD_DATE=-1;


	$fpFAILED=fopen('FAILED_SEARCH_DISEASE','w'); if(!$fpFAILED)							  failProcess($JOB_ID."003",'Unable to open FAILED_SEARCH');
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
	$fp=fopen($GENE_RULES_FILE,'r');if (!$fp)										    failProcess($JOB_ID."005",'Unable to open PUBLI_GENE_RULE.csv file ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line==""||$line[0]=="#")continue;
		$tab=array_values(array_filter(explode("\t",$line)));
		$EXEMPT[$tab[0]]=$tab[1];
	}
	fclose($fp);


processAllDiseases();



successProcess();






function processAllDiseases()
{
	global $JOB_ID;



	$NEW_ENTRIES=array();

	/// Here we get all the diseases that are children of MONDO_0000001, i.e. the root of MONDO diseases
	$res=runQuery("SELECT DISTINCT EE.DISEASE_ENTRY_Id, EE.disease_name
		FROM DISEASE_ENTRY EE, DISEASE_HIERARCHY EF, DISEASE_HIERARCHY EPH, DISEASE_ENTRY EP
		WHERE EP.DISEASE_ENTRY_ID = EPH.DISEASE_ENTRY_ID 
		AND EF.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
		AND EF.DISEASE_LEVEL_LEFT >=EPH.DISEASE_LEVEL_LEFT
		AND EF.DISEASE_LEVEL_RIGHT <= EPH.DISEASE_LEVEL_RIGHT
		AND EP.disease_tag='MONDO_0000001'");
	if ($res===false)															failProcess($JOB_ID."A01",'Unable to query for diseases ');

	foreach ($res as $line)
	{
		$NEW_ENTRIES[$line['disease_entry_id']][]=$line['disease_name'];
	}
	if ($NEW_ENTRIES==array())return;


	/// Here we get all the synonyms for the diseases that are exact synonyms:
	$res=runQuery("SELECT syn_value,disease_entry_id 
					FROM disease_syn 
					where disease_entry_id IN (".implode(',',array_keys($NEW_ENTRIES)).') 
					and syn_type=\'EXACT\'');
	if ($res===false)														failProcess($JOB_ID."A02",'Unable to query for disease synonyms ');
	foreach ($res as $line)
	{
		$NEW_ENTRIES[$line['disease_entry_id']][]=$line['syn_value'];
	}


	
	echo "NUMBER OF DISEASES: ".count($NEW_ENTRIES)."\n";
	$NG=0;
	foreach ($NEW_ENTRIES as $DISEASE_ENTRY_ID=>$S)
	{
		++$NG;
		/// If you wish to debug a specific disease, you can uncomment the following line
		//	if ($NG<3117)continue;
		//if ($DISEASE_ENTRY_ID!=16618)continue;

		echo "\n".$NG."/".count($NEW_ENTRIES)."\t".$DISEASE_ENTRY_ID."\n";
		processDisease($DISEASE_ENTRY_ID,$S);
	}	
	




	addLog("delete table");
	echo "DELETE\n";
	if (!runQueryNoRes("DROP TABLE IF EXISTS MV_DISEASE_PUBLI"))																failProcess($JOB_ID."A03",'Unable to delete MV_GENE_PUBLI'); 

	addLog("create table");
	echo "CREATE\n";
	if (!runQueryNoRes("CREATE TABLE MV_DISEASE_PUBLI AS 
		SELECT DISEASE_ENTRY_ID,PE.PMID_ENTRY_ID,PMID,PUBLICATION_DATE
		FROM PMID_ENTRY PE, PMID_DISEASE_MAP PGM
		WHERE PGM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID 
		ORDER BY DISEASE_ENTRY_ID ASC,PUBLICATION_DATE DESC"))																	failProcess($JOB_ID."A04",'Unable to create MV_GENE_PUBLI'); 


	if (!runQueryNoRes("CREATE INDEX MV_DISEASe_PUBLI_IDX1 ON MV_DISEASe_PUBLI (DISEASE_ENTRY_ID,PUBLICATION_DATE)")	)		failProcess($JOB_ID."A05",'Unable to create index1 MV_GENE_PUBLI'); 
	if (!runQueryNoRes("CREATE INDEX MV_DISEASE_PUBLI_IDX2 ON MV_DISEASE_PUBLI (DISEASE_ENTRY_ID,PMID)")			)			failProcess($JOB_ID."A06",'Unable to create index2  MV_GENE_PUBLI'); 
}
	
	
	
	

function processDisease($DISEASE_ENTRY_ID,$LISTS)
{
	global $NEW_ENTRIES;
	global $GLB_VAR;
	global $DB_INFO;
	global $PRD_DATE_TIMESTAMP;
	global $EXEMPT;
	global $fpFAILED;
	global $JOB_ID;
	
	if (count($LISTS)==0)return;
	$CURR_LEN=200;
	$CHUNKS_NAME=array();
	$CHUNK_GROUP=0;
	//exit;



	/// We try to ensure that the queries are not too long, so we work by length
	foreach ($LISTS as $L)
	{
		if (strlen($L)<4)continue;
		$NEW_LEN=$CURR_LEN+strlen($L)*3+50;
		echo $CURR_LEN.' ' .$NEW_LEN."\n";
		/// To ensure that it's not more thank 3K characters
		if ($NEW_LEN<2000)
		{
			$CURR_LEN=$NEW_LEN;
			$CHUNKS_NAME[$CHUNK_GROUP][]=$L;	
		}
		else
		{
			$CURR_LEN=200+strlen($L)*3+30;
			$CHUNK_GROUP++;
			$CHUNKS_NAME[$CHUNK_GROUP][]=$L;
		}
	}

	$LIST_ALL=array();
	//print_r($CHUNKS_NAME);//exit;
	//

	
	foreach ($CHUNKS_NAME as $CHUNK)
	{
		/// We are going to construct the query:
		$PUB_QUERY=' (';
		$P=0;
		$ADD='';$INCLUDE=false;
		foreach ($CHUNK as $V=>$L)
		{
			++$P;
			/// Word too small -> too generic -> ignore
			if (strlen($L)<4)continue;
			
			/// Exempt ? 
			if (isset($EXEMPT[$L]))
			{
				// Not additional rule => ignore
				if ($EXEMPT_[$L]=='""')continue;
				/// Additional rule => add to the query
				$ADD.=' '.$EXEMPT[$L];
			}
			$L=str_replace("&","",$L);
			
			$INCLUDE=true;
			$PUB_QUERY.='"'.$L.'"[TIAB] OR "'.$L.'"[TW] OR "'.$L.'"[MH] OR ';
			
		}
		if ($PUB_QUERY==' (')return;


		/// Ensuring we have a couple keywords in the query to avoid completely unrelated publications
		$PUB_QUERY='("disease" OR "infection" OR "syndrome") AND ('.substr($PUB_QUERY,0,-4).')'.$ADD;
		echo "DISEASE:".$DISEASE_ENTRY_ID."\n";
		
		$PUB_QUERY=str_replace("'","'\"'\"'",$PUB_QUERY);
		echo $PUB_QUERY."\n";
		//if ($PUBLI_RULE_ID!=1)continue;
		
		/// Getting the list of publications
		$result=array();
		$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'ds.csv');
	
		if (!$result['SUCCESS'])
		{
			/// Sometime when it fails, it is just a network issue, so we try again
			$TEST=0;
			for ($TEST=0;$TEST<5;++$TEST)
			{
				sleep(1);
				$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'ds.csv');
				if ($result['SUCCESS'])break;
			}
			
		}

		if (!$result['SUCCESS'])
		{
			echo" Unable to query "."\n";
			fputs($fpFAILED,$DISEASE_ENTRY_ID."\t".$PUB_QUERY."\n");
			return;
		}

		/// Then we process that batch results and add it to the list of publications we found so far
		$LIST_T=&$result['LIST'];

		foreach ($LIST_T as $ID=>$V)
		{
			$LIST_ALL[$ID]=false;
		}
	}
	echo "NUMBER OF PUBLICATIONS: ".count($LIST_ALL)."\n";
	
	$LIST_T=array();
	$LIST_T=$LIST_ALL;
	// ksort($LIST_T);print_r($LIST_T);
		
	$res=array();
	$res=runQuery('SELECT pmid 
			FROM pmid_disease_map PM, pmid_entry PE 
			WHERE PE.pmid_entry_id= PM.pmid_entry_id
			AND disease_entry_id='.$DISEASE_ENTRY_ID);
	if ($res===false)														    failProcess($JOB_ID."B01",'Unable to query for existing pmid map ');
	
	
	$LIST_E=array();$N_F=0;
	
	
	foreach ($res as $tab)
	{
		$LIST_E[$tab['pmid']]=isset($LIST_T[$tab['pmid']]);
		
		if ($LIST_E[$tab['pmid']])
		{
			$LIST_T[$tab['pmid']]=true;
			++$N_F;
		}
	}


	echo "NUMBER OF PUBLICATIONS ALREADY IN THE SYSTEM: ".count($LIST_E)."\n";
	echo "NUMBER OF PUBLICATIONS OVERLAPPING: ".$N_F."\n";
	
	
	
	$MISSING=array();
	foreach ($LIST_T as $ID=>&$STATUS)
	{
		if ($STATUS)continue;
		$MISSING[]=$ID;
	}
	
	
	
	$CHUNKS=array_chunk($MISSING,30000);$MAP=array();
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
	$N_D=0;



	$fp=fopen('disease_insert.csv','w');if (!$fp)											 failProcess($JOB_ID."B03",'Unable to open disease_insert.csv');
	foreach ($MISSING as $ID)
	{	
		
		if (!isset($MAP[$ID]))continue;
		$NEW_ENTRIES[$ID]=true;
		++$N_D;
		fputs($fp,$MAP[$ID]."\t".$DISEASE_ENTRY_ID."\n");
	}
	fclose($fp);



	if ($N_D==0)return;
	
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_disease_map(pmid_entry_id,disease_entry_id) FROM \''."disease_insert.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."B04",'Unable to insert pmid_disease_map'); 

	

}





?>

