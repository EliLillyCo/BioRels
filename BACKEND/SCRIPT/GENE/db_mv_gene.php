<?php

/// Increase memory limit
ini_set('memory_limit','8000M');

/**
 SCRIPT NAME: db_mv_gene
 PURPOSE:     This script create materialized views (as table) to get an eaiser access to gene information
 
*/

/// Job name - Do not change
$JOB_NAME='db_mv_gene';


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

	/// Get Parent info
	$DL_GENE_INFO=$GLB_TREE[getJobIDByName('db_gene')];
	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_GENE_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 																	failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 																	failProcess($JOB_ID."002",'NO '.$W_DIR.' found ');
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$DL_GENE_INFO['TIME']['DEV_DIR'];
	

addLog("Working directory: ".$W_DIR);



addLog("Listing selected Taxons based on config");
	/// We have the possibility to limit the number of organism to process.
	/// IN CONFIG_USER, you can provide a list of taxonomy ID corresponding to the organisms you want to consider
	/// We flip the array to have a list of taxon ID as key because it's much fasterto perform searches on keys rather than values
	
	$TAXON_LIMIT_LIST=array_flip(defineTaxonList());
	echo count($TAXON_LIMIT_LIST).' taxons defined'."\n";


	/// Get the max primary key for the gn_history table so we can insert new data
	$MAX_DBID=1;
	$res=runQuery("SELECT max (gn_history_id) c FROM gn_history");
	$MAX_DBID=$res[0]['c'];



addLog("Processing gene history");
	
	/// We open the gene_history file
	$fp=fopen($W_DIR.'/gene_history','r'); if (!$fp)									failProcess($JOB_ID."003",'Unable to open gene_history');
	
	/// We skip the header
	$line=stream_get_line($fp,10000,"\n");
	

	/// We are going to process history by taxon, so we need to store the data in an array
	/// And when we encounter a new taxon, we process the previous one
	$DATA=array();
	$CURRENT_TAXON=-1;
	$N=0;
	while(!feof($fp))
	{
		//echo $line."\n";
		++$N;if ($N%500000==0)echo "LINE:".$N."\n";
		
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		
		
		//#tax_id	GeneID	Discontinued_GeneID	Discontinued_Symbol	Discontinue_Date
		$tab=explode("\t",$line);
		
		/// We don't want to process all the taxon, so we check if the current taxon is in the list
		if ($TAXON_LIMIT_LIST!=array() && !isset($TAXON_LIMIT_LIST[$tab[0]]))continue;
		
		/// If we change taxon, we process the previous one
		if ($tab[0]!=$CURRENT_TAXON)
		{
			if ($DATA!=array())processTaxonHistory($DATA,$CURRENT_TAXON);
			/// We reset the data array
			$DATA=array();
			$CURRENT_TAXON=$tab[0];
			
		}
		/// We store the data in an array
		$date=substr($tab[4],0,4).'-'.substr($tab[4],4,2).'-'.substr($tab[4],6);
		$DATA[$tab[2]]=array(
			'DB_ID'=>-1,'ALT'=>$tab[1],'FINAL'=>-1, 'GN_ENTRY_ID'=>-1,'DB_STATUS'=>'TO_INS','DATE'=>$date);
		
		
	}
	fclose($fp);
	/// We process the last taxon
	if ($DATA!=array())processTaxonHistory($DATA,$CURRENT_TAXON);


	/// We create the materialized view
	$DB_CONN->beginTransaction();


	addLog("Insert into MV_GENE");
	$QUERY="CREATE TABLE mv_gene2 AS 
	select symbol, full_name,gene_id, gn.gn_entry_Id, syn_value, scientific_name,tax_id
	FROM taxon tt
	RIGHT JOIN chromosome ch  ON ch.taxon_id= tt.taxon_id
	Full Outer Join Chr_Map Cm ON Cm.Chr_Id = Ch.Chr_Id 
	Full Outer Join Chr_Gn_Map Cgm On Cgm.Chr_Map_Id = Cm.Chr_Map_Id
	Full Outer Join Gn_Entry Gn On Cgm.Gn_Entry_Id = Gn.Gn_Entry_Id 
	FULL OUTER JOIN  gn_syn_map gsm ON gsm.gn_entry_Id = gn.gn_entry_Id
	FULL OUTER JOIN  gn_syn gs ON gs.gn_syn_id =gsm.gn_Syn_id AND syn_type='S'
	WHERE symbol IS NOT NULL";
	
	if (!runQueryNoRes($QUERY))																failProcess($JOB_ID."006",'Unable to create MV_GENE2 '); 
	if (!runQueryNoRes("CREATE INDEX  ON MV_GENE2(gene_id)"))								failProcess($JOB_ID."007",'Unable to create MV_GENE2 index gene_id');
	if (!runQueryNoRes("CREATE INDEX  ON MV_GENE2(gn_entry_id)"))							failProcess($JOB_ID."008",'Unable to create MV_GENE2 index gn_entry_id');
	if (!runQueryNoRes("CREATE INDEX  ON MV_GENE2(tax_id)"))								failProcess($JOB_ID."009",'Unable to create MV_GENE2 index tax_id');
	if (!runQueryNoRes("CREATE INDEX  ON MV_GENE2(symbol)"))								failProcess($JOB_ID."010",'Unable to create MV_GENE2 index symbol');
	if (!runQueryNoRes("CREATE INDEX  ON MV_GENE2(LOWER(symbol))"))							failProcess($JOB_ID."011",'Unable to create MV_GENE2 index LOWER(symbol)');
	
	addLog("Truncate MV_GENE");
	
	 if (!runQueryNoRes("DROP TABLE MV_GENE"))												failProcess($JOB_ID."012",'Unable to delete MV_GENE'); 
	 
	 if (!runQueryNoRes("ALTER TABLE MV_GENE2 RENAME TO MV_GENE"))							failProcess($JOB_ID."013",'Unable to rename MV_GENE2 to MV_GENE'); 
	
	
	addLog("TRUNCATE MV_GENE_TAXON");
	if (!runQueryNoRes("TRUNCATE TABLE MV_GENE_TAXON"))										failProcess($JOB_ID."014",'Unable to delete MV_GENE'); 
	
	
	
	addLog("INSERT MV_GENE_TAXON");
	
	
	if (!runQueryNoRes("INSERT INTO MV_GENE_TAXON 
	Select gene_id, gn.gn_entry_id, tax_id,tt.taxon_id
	From taxon Tt 
	Join Chromosome Ch On ch.taxon_id= tt.taxon_id
	Join Chr_Map Cm On Cm.Chr_Id = Ch.Chr_Id 
	Join Chr_Gn_Map Cgm On Cgm.Chr_Map_Id = Cm.Chr_Map_Id
	Join Gn_Entry Gn On Cgm.Gn_Entry_Id = Gn.Gn_Entry_Id"))									failProcess($JOB_ID."015",'Unable to insert MV_GENE_TAXON'); 
	
	
	addLog("Truncate MV_GENE_SP");
	if (!runQueryNoRes("TRUNCATE TABLE MV_GENE_SP"))										failProcess($JOB_ID."016",'Unable to delete MV_GENE_SP'); 
	
	addLog("Insert MV_GENE_SP");
	
	
	$LIST_TAX=array();
	foreach ($TAXON_LIMIT_LIST as $TAX_ID=>$V)	$LIST_TAX[]="'".$TAX_ID."'";
	if ($LIST_TAX!=array())
	{
		$QUERY="INSERT INTO MV_GENE_SP  Select symbol,full_name,gene_id, gn.gn_entry_id, syn_value, scientific_name, tax_id
		From taxon Tt 
		Right Join Chromosome Ch On ch.taxon_id= tt.taxon_id
		Full Outer Join Chr_Map Cm On Cm.Chr_Id = Ch.Chr_Id 
		Full Outer Join Chr_Gn_Map Cgm On Cgm.Chr_Map_Id = Cm.Chr_Map_Id
		Full Outer Join Gn_Entry Gn On Cgm.Gn_Entry_Id = Gn.Gn_Entry_Id 
		FULL OUTER JOIN  gn_syn_map gsm ON gsm.gn_entry_Id = gn.gn_entry_Id
		FULL OUTER JOIN  gn_syn gs ON gs.gn_syn_id =gsm.gn_Syn_id AND syn_type='S'
		WHERE symbol IS NOT NULL AND TAX_ID IN (".implode(',',$LIST_TAX).")";
		if (!runQueryNoRes($QUERY))																failProcess($JOB_ID."017",'Unable to create MV_GENE_SP'); 
	}
	
	$DB_CONN->commit();
	
	successProcess();







	///
function processTaxonHistory(&$DATA,$TAX_ID)
{
	
	addLog("Processing TAXON ".$TAX_ID);
	global $JOB_ID;
	global $GLB_VAR;
	global $DB_INFO;
	global $MAX_DBID;


	/// The first step is to get the final gene for each discontinued gene
	$LIST_GENES=array();
	foreach ($DATA as &$ENTRY)
	{
		if (!isset($DATA[$ENTRY['ALT']]))
		{
			$ENTRY['FINAL']=$ENTRY['ALT'];
			if ($ENTRY['FINAL']!='-')$LIST_GENES[$ENTRY['FINAL']]=-1;
			continue;
		}

		$N=0;
		$ENTRY['FINAL']=$DATA[$ENTRY['ALT']]['ALT'];
		/// Sometimes the discontinued gene is not the final one, so we loop until we find the final one
		do
		{
			if ($ENTRY['FINAL']=='-')break;
			if (!isset($DATA[$ENTRY['FINAL']]))break;
			$ENTRY['FINAL']=$DATA[$ENTRY['FINAL']]['ALT'];
			++$N;

		}while($N<30);
		if ($ENTRY['FINAL']!='-')$LIST_GENES[$ENTRY['FINAL']]=-1;
	}

	/// Once we know, the final gene, we need to get the gn_entry_id for each gene
	/// We get the list of gene_id for the final genes
	$CHUNKS=array_chunk(array_keys($LIST_GENES),5000);
	foreach ($CHUNKS as $CHUNK)
	{
		$res=runQuery("SELECT gn_entry_id, gene_id 
		FROM gn_entry where gene_id IN (".implode(',',$CHUNK).')');
		if ($res === false )										failProcess($JOB_ID."A01",'Unable to run query');
		foreach ($res as $line)$LIST_GENES[$line['gene_id']]=$line['gn_entry_id'];
	}

	/// We update the data array with the gn_entry_id corresponding to the final gene
	foreach ($DATA as &$ENTRY)
	{
		if ($ENTRY['FINAL']!='-')$ENTRY['GN_ENTRY_ID']=$LIST_GENES[$ENTRY['FINAL']];
	}
	
	
	$CHUNKS=array_chunk(array_keys($DATA),10000);
	/// Now we are going to update the gn_history table
	$TO_DEL=array();
	foreach ($CHUNKS as $CHUNK)
	{

		$query="SELECT gn_history_id,gene_id,alt_gene_id,gn_entry_id,date_discontinued,tax_id 
		FROM gn_history g WHERE gene_id IN (".implode(',',$CHUNK).")";
				
		$res=runQuery($query);
		if ($res ===false ) 										failProcess($JOB_ID."A02",'Unable to run query');
		foreach ($res as $line)
		{
			/// If the gene is not in the list, we can delete it
			if (!isset($DATA[$line['gene_id']]))
			{
				// To be more efficient we will delete all the genes at once
				$TO_DEL[]=$line['gn_history_id'];
				continue;
			}

			$DATA[$line['gene_id']]['DB_STATUS']='VALID';
			$ENTRY=&$DATA[$line['gene_id']];
		
			$TO_UPD=false;
			/// We check if the entry is different from the database and create the query to update
			$query='UPDATE gn_history set ';
			if ($line['date_discontinued']!=$ENTRY['DATE']){$query.=' date_discontinued = \''.$ENTRY['DATE'].'\' , ' ; $TO_UPD=true;}
			if ($line['alt_gene_id']!=$ENTRY['ALT']){$query.=' alt_gene_id = \''.$ENTRY['ALT'].'\' , ' ; $TO_UPD=true;}
			
			if ($line['tax_id']!=$TAX_ID){$query.=' tax_id = \''.$TAX_ID.'\' , ' ; $TO_UPD=true;}
			if ($line['gn_entry_id']!=$ENTRY['GN_ENTRY_ID'] && (!($line['gn_entry_id']=='' && $ENTRY['GN_ENTRY_ID']==-1))){$query.=' gn_entry_id = '.(($ENTRY['GN_ENTRY_ID']=='-1')?'NULL':$ENTRY['GN_ENTRY_ID']).' , ' ; $TO_UPD=true;}
			
			/// If nothing changed, we don't need to update
			if (!$TO_UPD)continue;
			
			if (!runQueryNoRes(substr($query,0,-2).' WHERE gn_history_id='.$line['gn_history_id']))failProcess($JOB_ID."A03",'Unable to update gn_history '.$query);
		}
	}



	$fp=fopen('gene_history_out.csv','w');if (!$fp) failProcess($JOB_ID."A04",'Unable to open gene_history_out.csv');
	// Boolean to check if we have new genes to insert
	$HAS_NEW=false;
	foreach ($DATA as  $GN=>&$ENTRY)
	{
		/// If the gene is already in the database, we don't need to insert it
		if ($ENTRY['DB_STATUS']!='TO_INS')continue;
		
		$HAS_NEW=true;
		++$MAX_DBID;
		fputs($fp,$MAX_DBID."\t".$GN."\t".$ENTRY['ALT']."\t".(($ENTRY['GN_ENTRY_ID']=='-1')?'NULL':$ENTRY['GN_ENTRY_ID'])."\t".$ENTRY['DATE']."\t".$TAX_ID."\n");
	}

	/// We delete the genes that are not in the file anymore
	if ($TO_DEL!=array())
	{
		if (!runQueryNoRes("DELETE FROM gn_history WHERE gn_history_id IN (".implode(',',$TO_DEL).')'))failProcess($JOB_ID."A05",'Unable to delete gn_history');
	}
	/// If we don't have new genes, we don't need to insert
	if (!$HAS_NEW)return;

	/// We insert the new genes
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.gn_history(gn_history_id,gene_id,alt_gene_id,gn_entry_id,date_discontinued,tax_id) FROM \''."gene_history_out.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."A06",'Unable to insert gn_history table'); 

}



?>

