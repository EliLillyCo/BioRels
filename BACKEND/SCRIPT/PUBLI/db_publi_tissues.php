<?php
ini_set('memory_limit','3000M');
/**
 SCRIPT NAME: db_publi_tissues
 PURPOSE:     Match tissues to publications
 
*/
$JOB_NAME='db_publi_tissues';

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

addLog("Setting up");

	/// Getting patent information:
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
	$fpFAILED=fopen('FAILED_SEARCH_TISSUE','w'); if(!$fpFAILED)							    failProcess($JOB_ID."003",'Unable to open FAILED_SEARCH');


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


processAllTissues();
//


function processAllTissues()
{
	global $JOB_ID;
	$NEW_ENTRIES=array();

	/// Getting all anatomy entries
	$res=runQuery("SELECT DISTINCT EE.anatomy_ENTRY_Id, EE.anatomy_name
	FROM anatomy_ENTRY EE, anatomy_HIERARCHY EF, anatomy_HIERARCHY EPH, anatomy_ENTRY EP
	WHERE EP.anatomy_ENTRY_ID = EPH.anatomy_ENTRY_ID 
	AND EF.anatomy_ENTRY_ID = EE.anatomy_ENTRY_ID
	AND EF.anatomy_LEVEL_LEFT >=EPH.anatomy_LEVEL_LEFT
	AND EF.anatomy_LEVEL_RIGHT <= EPH.anatomy_LEVEL_RIGHT
	AND EP.anatomy_name='material anatomical entity'");
	if ($res===false)														failProcess($JOB_ID."A01",'Unable to query for anatomy entries');

	foreach ($res as $line)
	{
		$NEW_ENTRIES[$line['anatomy_entry_id']][]=$line['anatomy_name'];
	}
	if ($NEW_ENTRIES==array())return;

	/// Getting all synonyms that are not broad synonyms
	$res=runQuery("SELECT syn_value,anatomy_entry_id 
		FROM anatomy_syn 
		where syn_type!= 'BROAD' 
		AND anatomy_entry_id IN (".implode(',',array_keys($NEW_ENTRIES)).')');
	if ($res===false)													failProcess($JOB_ID."A02",'Unable to query for anatomy synonyms');
	foreach ($res as $line)
	{
		$NEW_ENTRIES[$line['anatomy_entry_id']][]=$line['syn_value'];
	}


	
	echo "NUMBER OF Anatomy records: ".count($NEW_ENTRIES)."\n";
	$NG=0;
	
	foreach ($NEW_ENTRIES as $ANATOMY_ENTRY_ID=>$S)
	{
		++$NG;
		
		echo "\n".$NG."/".count($NEW_ENTRIES)."\t".$ANATOMY_ENTRY_ID."\n";
		processAnatomy($ANATOMY_ENTRY_ID,$S);
	}	





	addLog("delete table");
	echo "DELETE\n";
	if (!runQueryNoRes("DROP TABLE IF EXISTS MV_ANATOMY_PUBLI"))																failProcess($JOB_ID."A03",'Unable to delete MV_GENE_PUBLI'); 

	addLog("create table");
	echo "CREATE\n";
	if (!runQueryNoRes("CREATE TABLE MV_ANATOMY_PUBLI AS SELECT ANATOMY_ENTRY_ID,PE.PMID_ENTRY_ID,PMID,PUBLICATION_DATE
	FROM PMID_ENTRY PE, PMID_ANATOMY_MAP PGM
	WHERE PGM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID ORDER BY ANATOMY_ENTRY_ID ASC,PUBLICATION_DATE DESC"))								failProcess($JOB_ID."A04",'Unable to create MV_GENE_PUBLI'); 


	if (!runQueryNoRes("CREATE INDEX MV_ANATOMY_PUBLI_IDX1 ON MV_ANATOMY_PUBLI (ANATOMY_ENTRY_ID,PUBLICATION_DATE)")	)					failProcess($JOB_ID."A05",'Unable to create index1 MV_GENE_PUBLI'); 
	if (!runQueryNoRes("CREATE INDEX MV_ANATOMY_PUBLI_IDX2 ON MV_ANATOMY_PUBLI (ANATOMY_ENTRY_ID,PMID)")			)						failProcess($JOB_ID."A06",'Unable to create index2  MV_GENE_PUBLI'); 
}
	
	
	
	
function processAnatomy($ANATOMY_ENTRY_ID,$LISTS)
{
	global $NEW_ENTRIES;
	global $GLB_VAR;
	global $DB_INFO;
	global $PRD_DATE_TIMESTAMP;
	global $EXEMPT;
	global $fpFAILED;
	global $JOB_ID;
	
	/// No synonyms -> no point
	if (count($LISTS)==0)return;
	
	$LIST_ALL=array();
	//print_r($LISTS);
	$CURR_LEN=200;
	$CHUNKS_NAME=array();
	$CHUNK_GROUP=0;

	/// we allow some terms to be put in plurals if necessary
	$PLURALS=array('cell','gland','lobe','vessel','duct','vein','canal','follicle','zone','system','tract');
	foreach ($LISTS  as $L)
	{
		//echo $L."\n";
		foreach ($PLURALS as $PLUR)
		{
			if (substr($L,-strlen($PLUR))==$PLUR &&strlen($L)>strlen($PLUR)+5)
			{
				echo $L."\tPLURALS\t".trim(substr($L,0,strlen($L)-strlen($PLUR)-1))."\n";;
				$LISTS[]=trim(substr($L,0,strlen($L)-strlen($PLUR)-1));
				$LISTS[]=$L.'s';
			}
		}
		
	}
	sort($LISTS);
	$LISTS=array_unique($LISTS);



	/// We try to ensure that the queries are not too long, so we work by length
	foreach ($LISTS as $L)
	{
		if (strlen($L)<4)continue;
		$NEW_LEN=$CURR_LEN+strlen($L)*3+50;
		echo $CURR_LEN.' ' .$NEW_LEN."\n";
		/// To ensure that it's not more thank 2K characters
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

	//print_r($CHUNKS_NAME);//exit;
	//
	/// Now for each chunk, we build the query:
	foreach ($CHUNKS_NAME as $CHUNK)
	{

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
				/// Not additional rule => ignore
				if ($EXEMPT[$L]=='""')continue;	
				/// Additional rule => add to the query
				$ADD.=' '.$EXEMPT[$L];
			}
			$L=str_replace("&","",$L);
			
			$INCLUDE=true;
			$PUB_QUERY.='"'.$L.'"[TIAB] OR "'.$L.'"[TW] OR "'.$L.'"[MH] OR ';
			
		}
		if ($PUB_QUERY==' (')return;
		if ($ADD!='')echo "ADDITIONAL RULES:".$ADD."\n";
		echo "\n";
		/// Removing the last OR
		$PUB_QUERY=substr($PUB_QUERY,0,-4).')'.$ADD;
		
		
		$PUB_QUERY=str_replace("'","'\"'\"'",$PUB_QUERY);
		
		

		/// We query pubmed for the publications
		$result=array();
		$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'ts.csv');

		/// Sometime query will timeout, so we try again
		if (!$result['SUCCESS'])
		{
			$TEST=0;
			for ($TEST=0;$TEST<5;++$TEST)
			{
				sleep(1);
				$result=queryPubmed($PUB_QUERY,$PRD_DATE_TIMESTAMP,time(),0,'ts.csv');
				if ($result['SUCCESS'])break;
			}
			
		}
		if (!$result['SUCCESS'])
		{
			fputs($fpFAILED,$ANATOMY_ENTRY_ID."\t".$PUB_QUERY."\n");
			echo" Unable to query "."\n";
			return;
		}
		/// We have the list of publications from pubmed for this query
		$LIST_T=&$result['LIST'];
		/// We add it to the list of all publications, so we can compare it to the existing list
		foreach ($LIST_T as $ID=>$V)$LIST_ALL[$ID]=false;
	}
	echo "NUMBER OF PUBLICATIONS: ".count($LIST_ALL)."\n";
	$LIST_T=array();
	$LIST_T=$LIST_ALL;

	/// Getting current publications associated to that tissue
	$res=array();
	$res=runQuery('SELECT pmid 
	FROM pmid_anatomy_map PM, pmid_entry PE 
	WHERE PE.pmid_entry_id= PM.pmid_entry_id
	AND anatomy_entry_id='.$ANATOMY_ENTRY_ID);
	if ($res===false)														    failProcess($JOB_ID."B01",'Unable to query for existing pmid map ');
	$LIST_E=array();$N_F=0;
	foreach ($res as $tab)
	{
		$LIST_E[$tab['pmid']]=isset($LIST_T[$tab['pmid']]);

		
		if (!$LIST_E[$tab['pmid']])continue;
		/// We mark the publication as already in the system
		$LIST_T[$tab['pmid']]=true;
		++$N_F;
		
	}
	echo "NUMBER OF PUBLICATIONS ALREADY IN THE SYSTEM: ".count($LIST_E)."\n";
	echo "NUMBER OF PUBLICATIONS OVERLAPPING: ".$N_F."\n";

	/// Now we list all the publications that are not in the system
	$MISSING=array();
	foreach ($LIST_T as $ID=>&$STATUS)
	{
		if ($STATUS)continue;
		$MISSING[]=$ID;

	}


	/// If there are no missing publications, then we are done
	if (count($MISSING)==0)return;

	/// We get the pmid ids for the missing publications
	/// We do it in chunks of 30K to avoid too long queries
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
	
	/// We push the new entries to a file to be inserted
	$N_D=0;
	$fp=fopen('anatomy_insert.csv','w');if (!$fp)										failProcess($JOB_ID."B03",'Unable to open anatomy_insert.csv');
	foreach ($MISSING as $ID)
	{	
		/// Only if we find the pmid_entry_Id in the database
		if (!isset($MAP[$ID]))continue;
		$NEW_ENTRIES[$ID]=true;
		++$N_D;
		fputs($fp,$MAP[$ID]."\t".$ANATOMY_ENTRY_ID."\n");
	}
	fclose($fp);

	/// No new entries -> we are done
	if ($N_D==0)return ;

	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_anatomy_map(pmid_entry_id,anatomy_entry_id) FROM \''."anatomy_insert.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."B04",'Unable to insert pmid_anatomy_map'); 

	

}




successProcess();
?>

