<?php



// Need extra memory
ini_set('memory_limit','5000M');
error_reporting(E_ALL);

/**
 SCRIPT NAME: db_gene
 PURPOSE:     Process part of NCBI gene annotation
 
*/

/// Job name - Do not change
$JOB_NAME='db_gene';


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
	/// dl_gene is the parent script that has downloaded the files.
	/// So we are going to get the working directory from dl_gene
	$DL_GENE_INFO=$GLB_TREE[getJobIDByName('dl_gene')];
	
	/// We are going to work in the directory set up by dl_gene
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_GENE_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."002",'Unable to access '.$W_DIR);
	
	/// We are going to update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$DL_GENE_INFO['TIME']['DEV_DIR'];

	/// We need two files: gene_info and gene_history, so we check there's in the processing directory
	$F_FILE=$W_DIR.'/gene_info';			if (!checkFileExist($F_FILE))			failProcess($JOB_ID."003",'NO '.$F_FILE.' found ');
	$F_HIST=$W_DIR.'/gene_history';			if (!checkFileExist($F_HIST))			failProcess($JOB_ID."004",'NO '.$F_HIST.' found ');

	/// We have the possibility to limit the number of organism to process.
	/// IN CONFIG_USER, you can provide a list of taxonomy ID corresponding to the organisms you want to consider
	$TAXON_LIMIT_LIST=defineTaxonList();
	//print_R($TAXON_LIMIT_LIST);exit;
	//$TAXON_LIMIT_LIST=array(10085,10089,10090);
	$TAXON_KEYS=array_flip($TAXON_LIMIT_LIST);
	
addLog("Working directory: ".$W_DIR);

addLog("Load taxon merged entries");

	/// In the case where a taxon has been merged with another or replaced, we need to know 
	/// so MERGED_TAXON will have a list of the former taxon ID and the new taxon ID
	$MERGED_TAXON=array();

	//// Based on CONFIG_JOB, db_gene cannot run until the taxonomy has been processed
	//// So there must be a PRD directory for the taxonomy
	$TAXON_INFO=$GLB_TREE[getJobIDByName('wh_taxonomy')];
	$TAXON_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$TAXON_INFO['DIR'].'/'.$TAXON_INFO['TIME']['DEV_DIR'];	
		
	// /// merged.dmp contains the list of merged records
	$fp=fopen($TAXON_DIR.'/merged.dmp','r');if (!$fp)																failProcess($JOB_ID."006",'Unable to open merged.dmp'); 
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;
		$tab=explode("|",$line);
		if (count($tab)!=3)continue;

		$FORMER=trim($tab[0]);/// The former taxon ID
		$NEW=trim($tab[1]);	  /// The new taxon ID
		$MERGED_TAXON[$FORMER]=$NEW;
	}
	fclose($fp);







addLog("Filtering gene_info file");
	/// gene_info is a large file, so we are going to filter it to keep only the records that are relevant to the taxon we are going to process
	if (!checkFileExist('gene_info_ordered'))	filterGeneInfo();
	

addLog("Process History");
	processHistory();


	/// In some cases, some genes will be mapped to a taxon that has been replaced with a new tax id.
	//// To ensure integrity of the data, such genes will be listed in DIFF_TAX_GENES array
	/// So that when we check the list of genes are the same in the database and in the file
	/// we can take those cases into consideration.

	$DIFF_TAX_GENES=array();
	


// 	/// Now we are in a state where all former gene IDs are either deleted or updated
// 	/// so now we can process the gene_info file
	
addLog("Preparing files");
	$DEBUG=false;
	/// To speed up the insertion by doing batch insert, we first need to get the maximal value for the table's primary key. 
	$DBIDS=array(
		'chromosome'=>-1,
		'chr_map'=>-1,
		'gn_entry'=>-1,
		'chr_gn_map'=>-1,
		'gn_syn'=>-1,
		'gn_syn_map'=>-1);


	foreach ($DBIDS as $TBL=>$R)
	{
		$N=$TBL;
		if ($TBL=='chromosome')$N='chr';
		$res=runQuery("SELECT MAX(".$N.'_id) co FROM '.$TBL.' WHERE '.$N.'_ID < 999999995');
		if ($res===false )																failProcess($JOB_ID."007",'Unable to get max value for '.$TBL);
		if ($res==array())$DBIDS[$TBL]=0;
		else $DBIDS[$TBL]=$res[0]['co'];
	}


	/// We also open all necessary files, one for each table we are going to treat in the script:
	$fpFILES=array(
		'GN'=>fopen('GN_ENTRY.csv','w'),
		'GN_SYN_MAP'=>fopen('GN_SYN_MAP.csv','w'),
		'CHR_GN_MAP'=>fopen('CHR_GN_MAP.csv','w'));
	foreach ($fpFILES as $N=>$F)if ($F==null)											ailProcess($JOB_ID."008",'Unable to open '.$N.' file ');



addLog("Process Gene Info");
	
	/*
		Array
		(
			[0] => #tax_id
			[1] => GeneID
			[2] => Symbol
			[3] => LocusTag
			[4] => Synonyms
			[5] => dbXrefs
			[6] => chromosome
			[7] => map_location
			[8] => description
			[9] => type_of_gene
			[10] => Symbol_from_nomenclature_authority
			[11] => Full_name_from_nomenclature_authority
			[12] => Nomenclature_status
			[13] => Other_designations
			[14] => Modification_date
			[15] => Feature_type
		)
		*/


	$UN_CHROMS=array("Un","undetermined","Unknown","-");
	
	
	
	$fp=fopen($W_DIR.'/gene_info_ordered','r'); if (!$fp)								failProcess($JOB_ID."009",'Unable to open gene_info');
	//fseek($fp,4594320019);


	$CURR_TAX_ID=-1;
	$LIST_ALL_GENES=array();
	$CHR_INFOS=array();
	$N_DONE=0;
	$N_L=0;
	$tax_status=false;
	$valid_taxon=true;
	$BULK=array();
	$HAS_FILE_DATA=false;
	$STATS=array('N_GENE'=>0, 'ALL_GENE'=>0,'DB_GENE'=>0,'N_L'=>0);
	$COVERED_TAXON=array();
	$START=false;
	while(!feof($fp))
	{
		$fpos=ftell($fp);
		/// Each line is a new record
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$STATS['N_L']++;
		//echo $line."\n";

		$tab=explode("\t",$line);

		
		/// gene_info is ordered by taxonomy ID.
		/// So to minimize the number of queries to be performed, if we start processing a new taxon
		/// we are going to doing all chromosome and locus in memory
		$TAX_ID=$tab[0];
		
		
		
		/// If you need to focus on a specific taxon, uncomment those two lines:
		//if ($TAX_ID==366646)$START=true;
		//	if (!$START)continue;
		


		/// If the taxon is a newly processed taxon, we first need to push the data from the previous taxon
		/// Then load the information for this taxon before processing the gene
		if ($TAX_ID!=$CURR_TAX_ID)
		{
			echo "FILE POS :".$fpos."\n";

			/// If we have already processed a taxon, we are going to push the data
			if ($CURR_TAX_ID!=-1)
			{

				/// Data from previous taxon -> push
				if ($BULK!=array())processBulk($BULK,$CURR_TAX_ID);
				///Then clean up
				unset($BULK);
				$BULK=array();
				
				/// If we have data to push to the database, we do it
				if ($HAS_FILE_DATA) pushToDB();
				
				//addLog("Verification");
				/// This will verify that the data has been correctly inserted
				/// And we have the proper counts
				verifyData($CURR_TAX_ID,$LIST_ALL_GENES,$DIFF_TAX_GENES,$STATS);

				
			//if (!$VALID)exit;
			}
			
			
			if ($STATS['DB_GENE']!=$STATS['ALL_GENE'])
			{
				print_R($LIST_ALL_GENES);
				echo "All gene vs DB gene different count\t".$STATS['DB_GENE'].'<>'.$STATS['ALL_GENE']."\n";exit;
			}
			if ($STATS['DB_GENE']!=$STATS['N_GENE'])
			{
				print_R($LIST_ALL_GENES);
				echo "N gene vs DB gene different count\t".$STATS['DB_GENE'].'>'.$STATS['N_GENE']."\n";exit;
			}
			
			echo "\t=>".$STATS['DB_GENE']." processed\n";


			/// Now that we are all good, we can reset the variables for this new taxon

			$LIST_ALL_GENES=array();
			unset($CHR_INFOS);
			$CHR_INFOS=null;
			$CHR_INFOS=array();
			gc_collect_cycles();
			$tax_status=false;

			$CURR_TAX_ID=$TAX_ID;
			echo "NEW TAXON - ".$TAX_ID." ".$fpos."\t";
			/// If there is a list of taxons specified in CONFIG_GLOBAL and it's not in that list, 
			///then we ignore that taxon
			if ($TAXON_LIMIT_LIST!=array())
			{
				$valid_taxon=in_array($TAX_ID,$TAXON_LIMIT_LIST);
				if (!$valid_taxon)
				{
					echo "INVALID TAXON\n";
					continue;
				}
			}else  $valid_taxon=true;
			/// Otherwise we load the data:
			$tax_status= loadTax($CHR_INFOS,$TAX_ID);
			echo "TAXID:".$TAX_ID."\tMEM:".memory_get_usage ()."\n";
			
			
			
		}

		if (!$valid_taxon)continue;
		if (!$tax_status)continue;
		//echo "IN\n";
		/// Are we covering this taxon?
		$COVERED_TAXON[$TAX_ID]=false;

		// if ($tab[1]!=7857739)continue;
		$STATS['ALL_GENE']++;
		
		/// Add this gene to the list of genes for this taxon to be processed and check later on
		$LIST_ALL_GENES[$tab[1]]=false;
		
		/// running queries for individual genes is time consuming
		/// So we process them by bulk of 500 to limit the number of queries to perform
		$BULK[$tab[1]]=$tab;

		if (count($BULK)<500)continue;

		/// If we have 500 genes, we process them
		processBulk($BULK,$CURR_TAX_ID);
		
		$BULK=array();
	}/// END OF FILE

	fclose($fp);



	addLog("END OF FILE - Process Last bulk ".count($BULK) );
	if ($BULK !=array())processBulk($BULK,$CURR_TAX_ID);
	unset($BULK);
	$BULK=array();
	
	if ($HAS_FILE_DATA)pushToDB();




addLog("Final Verification");
	$res=runQuery("SELECT distinct gene_id 
	FROM gn_entry g, chr_gn_map cgm, chr_map cm, chromosome c, taxon t
	where t.taxon_id = c.taxon_id AND c.chr_id = cm.chr_id
	AND cm.chr_map_id= cgm.chr_map_id AND g.gn_entry_id = cgm.gn_entry_id 
	and tax_id='".$CURR_TAX_ID."'");
	if ($res===false)															failProcess($JOB_ID."010",'Unable to get gene list');
	$STATS['DB_GENE']+=count($res);
	$VALID=true;$LIST_CHR_DEL=array();
	foreach ($res as $line)
	{
	if (isset($LIST_ALL_GENES[$line['gene_id']])){$LIST_ALL_GENES[$line['gene_id']]=true;continue;}
	else if (isset($DIFF_TAX_GENES[$CURR_TAX_ID]) && in_array($line['gene_id'],$DIFF_TAX_GENES[$CURR_TAX_ID])){$LIST_ALL_GENES[$line['gene_id']]=true;continue;}
	 	else {
		$LIST_CHR_DEL[$line['chr_gn_map_id']]=true;
	}
	//$DB_GENE[$line['GENE_ID']]=false;
}

if ($LIST_CHR_DEL!=array())
{
	if (!runQueryNoRes('DELETE FROM chr_gn_map WHERE chr_gn_map_id IN ('.implode(',',array_keys($LIST_CHR_DEL)).')'))
	failProcess($JOB_ID."011",'Unable to delete chr_gn_maps');
	$STATS['DB_GENE']-=count($LIST_CHR_DEL);
}


foreach ($LIST_ALL_GENES as $GENE_ID=>$STATUS) 
{
	if($STATUS)continue;
	echo "\tGENE EXIST IN FILE NOT IN DB\t".$GENE_ID."\n";
	$VALID=false;
}
	
	 
if ($STATS['DB_GENE']!=$STATS['ALL_GENE'])	{echo "All gene vs DB gene differnet count\n";}
if ($STATS['DB_GENE']!=$STATS['N_GENE'])	{echo "N gene vs DB gene differnet count\n";}

echo "\t=>".$STATS['DB_GENE']." processed\n";
print_r($STATS);



$res=runQuery("SELECT distinct tax_id FROM gn_entry g, chr_gn_map cgm, chr_map cm, chromosome c, taxon t
			where t.taxon_id = c.taxon_id AND c.chr_id = cm.chr_id
			AND cm.chr_map_id= cgm.chr_map_id AND g.gn_entry_id = cgm.gn_entry_id");
if ($res===false)															failProcess($JOB_ID."012",'Unable to get taxon list');
foreach ($res as $line)
{
	if (!isset($COVERED_TAXON[$line['tax_id']]))
	{
		echo "ERROR\tTAXON ".$line['tax_id']."\t NOT FOUND IN FILE\n";
	}else $COVERED_TAXON[$line['tax_id']]=true;
}

foreach ($COVERED_TAXON as $TAX_ID=>$STATUS_TAX)
	if (!$STATUS_TAX)echo "ERROR\t TAXON ".$TAX_ID."\tNOT FOUND IN DB\n";
	


	echo $N_L."\t".$N_DONE."\n";


	addLog("DELETE unused chr_map");
	if (!runQueryNoRes("DELETE FROM chr_map where chr_map_id NOT IN (select distinct chr_map_id FROM chr_gn_map)"))
		failProcess($JOB_ID."013",'Unable to remove obsolete chr_map records');
	
	
	
	successProcess();
	























function filterGeneInfo()
{
	global $MERGED_TAXON;
	global $TAXON_KEYS;

	addLog("Filtering gene_info file");

	/// Opening the gene_info file
	$fp=fopen('gene_info','r');			if (!$fp)failProcess($JOB_ID."A01",'Unable to open gene_info');

	/// Creating a temporary file to store the filtered data
	$fp_r=fopen('gene_info_t','w');		if (!$fp_r)failProcess($JOB_ID."A02",'Unable to open gene_info_t');
	stream_get_line($fp,10000,"\n");/// Header


	$N_LINE=0;
	$STR='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		$tab=explode("\t",$line);
		/// We take advantage of this step to update any TAX ID that has been merged with another
		if (isset($MERGED_TAXON[$tab[0]]))	$tab[0]=$MERGED_TAXON[$tab[0]];
		
		/// And if the taxon is not in the list of taxon to process, we skip the record
		if (!isset($TAXON_KEYS[$tab[0]]))continue;
		/// Otherwise we write the record to the temporary file
		/// But to reduce I/O, we batch the records and write them by batch of 10000

		$STR.=implode("\t",$tab)."\n";
		++$N_LINE;
		if ($N_LINE<10000)	continue;

		fputs($fp_r,$STR);
		$STR='';
		$N_LINE=0;
	}
	/// We write the last batch
	if ($STR!='')fputs($fp_r,$STR);
	fclose($fp_r);
	fclose($fp);
	

	addLog("Sorting filtered gene_info file");
	/// Now, due to the fact that some taxon have been merged, the file is not sorted by taxon ID anymore,
	/// which is a problem for the next step. So we are going to sort the file by taxon ID
	exec('sort -k1 -n gene_info_t > gene_info_ordered',$res,$return_code);
	if ($return_code!=0)							failProcess($JOB_ID."A03",'Unable to sort gene_info_t');

	/// We can now delete the temporary file
	if (!unlink('gene_info_t'))						failProcess($JOB_ID."A04",'Unable to delete gene_info_t');
}




function processHistory()
{
	global $W_DIR;
	global $GLB_VAR;
	global $JOB_ID;
	/// Prior to any gene processing, we need to know which one have been updated, i.e. have a new Gene ID defined
	/// or deleted, and therefore should be removed.
	/// So first, we look at the  gene_history file
	$fp=fopen($W_DIR.'/gene_history','r'); if (!$fp)									failProcess($JOB_ID."B01",'Unable to open gene_history');
	$line=stream_get_line($fp,10000,"\n");
	$HIST_GID=array();$N=0;
	while(!feof($fp))
	{
		//echo $line."\n";
		++$N;if ($N%500000==0)echo "LINE:".$N."\n";
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		if (!isset($TAXON_KEYS[$tab[0]]))continue;
		/// Tab[2] contains the former geneID, 
		/// tab[1] the new gene ID or - if the former gene ID has been removed.
		$HIST_GID[$tab[2]]=$tab[1];
	}
	fclose($fp);


	// In the case where a former gene id is updated to an existing gene ID, 
	// we need to update all dependent tables prior to deletion
	/// so first we need to get the dependent tables
	addLog("Get Dependent tables");
	$DEP_TABLES=getDepTableList('gn_entry',$GLB_VAR['DB_SCHEMA'],array('chr_gn_map','gn_syn_map','gn_rel'));
	



	
	addLog("Get Gene List");

	///We then store the list of current gene_ids from the database into GENE_LIST
	if (!checkFileExist('GENE_LIST') &&
	 !runQueryToFile("SELECT gene_id, gn_entry_id 
					   FROM gn_entry","GENE_LIST",$JOB_ID))								failProcess($JOB_ID."B02",'Unable to get GEne List ');
				   

	addLog("Compare current to History");
	$fp=fopen("GENE_LIST",'r');if (!$fp)												failProcess($JOB_ID."B03",'Unable to open GENE_LIST ');
	while(!feof($fp))
	{
		$line_file=stream_get_line($fp,1000,"\n");
		if ($line_file=='')continue;
		
		$tab=explode("\t",$line_file);
		
		if ($N%1000==0)echo "GENE LINES \t".$N."\n";
		$CURR_GN_ID=$tab[0];
		$CURR_GN_DBID=$tab[1];
		/// If the gene ID is not in the list, it hasn't been updated or deleted so we can continue
		if (!isset($HIST_GID[$CURR_GN_ID]))continue;
		/// If it has been deleted, as defined by a -, we delete it
		if ($HIST_GID[$CURR_GN_ID]=='-')
		{
			addLog("\tDELETING GENE ".$CURR_GN_ID);
			if (!runQueryNoRes("DELETE FROM gn_entry WHERE gn_entry_id=".$CURR_GN_DBID))	failProcess($JOB_ID."B04",'Unable to delete GENE '.$CURR_GN_ID);
			continue;
		}
		/// Otherwise there should be an alternative gene ID
		$ALT_GENE_ID=$HIST_GID[$CURR_GN_ID];
		
		/// However, that gene ID might itself have been updated too or even deleted
		/// So we use a loop to loop over the history to see if either we end up with - -> so deletion or that the gene ID is not found in the history
		$NTRY=0;
		do
		{
			++$NTRY;
			if (!isset($HIST_GID[$ALT_GENE_ID]))break;
			$ALT_GENE_ID=$HIST_GID[$ALT_GENE_ID];
			//echo "\t".$ALT_GENE_ID;
			if ($ALT_GENE_ID=='-')break;
			
		}while ($NTRY>=10000);
		
		/// So if we end up with a -, we delete the record
		if ($ALT_GENE_ID=='-'){
			addLog("\tDELETING GENE ".$CURR_GN_ID);
			if (!runQueryNoRes("DELETE FROM gn_entry WHERE gn_entry_id=".$CURR_GN_DBID))	failProcess($JOB_ID."B05",'Unable to delete GENE '.$CURR_GN_ID.' TO '.$ALT_GENE_ID);
			continue;}
		
			/// Otherwise we check if the gene record is already in the system
			/// and in this case we need to update the dependent tables and then delete the former record
		$res=runQuery("SELECT gn_entry_Id FROM gn_entry WHERE GENE_ID = ".$ALT_GENE_ID);
		if ($res===false)																	failProcess($JOB_ID."B06",'Unable to get gene records ');
		echo "FORMER: ".$CURR_GN_ID."\t TO ".$ALT_GENE_ID."\n";
		if (count($res)!=0)
		{
			foreach ($DEP_TABLES as $COL=>&$DEP_INFO_LIST)
			foreach ($DEP_INFO_LIST as &$DEP_INFO)
			{
				foreach ($res as $line)
				{
				$query='UPDATE '.$DEP_INFO['SCHEMA'].'.'.$DEP_INFO['TABLE'].' SET '.$DEP_INFO['COLUMN'].' = '.$line['gn_entry_id'].' WHERE '.$DEP_INFO['COLUMN'].' = '.$CURR_GN_DBID."\n";
				runQueryNoRes($query);
				}
				//echo $query;
			}
			echo "\tDELETION\t"."DELETE FROM gn_entry WHERE gn_entry_id=".$CURR_GN_DBID."\n";
			if (!runQueryNoRes("DELETE FROM gn_entry WHERE gn_entry_id=".$CURR_GN_DBID))	failProcess($JOB_ID."B07",'Unable to delete GENE '.$CURR_GN_ID.' TO '.$ALT_GENE_ID);
		}
		else 
		{
			echo "\tUPDATE";
			if (!runQueryNoRes("UPDATE gn_entry SET gene_id = ".$ALT_GENE_ID." WHERE gn_entry_id=".$CURR_GN_DBID))	failProcess($JOB_ID."B08",'Unable to Update GENE '.$CURR_GN_ID.' TO '.$ALT_GENE_ID);
		}
		echo "\n";
	}
	$HIST_GID=array();
	unset($HIST_GID);
}


function pushToDB()
{
	global $fpFILES;
	global $DB_INFO;
	global $GLB_VAR;
	global $HAS_FILE_DATA;
	global $JOB_ID;


	echo "INSERT DATA\n";
	
	/// Closing files
	fclose($fpFILES['GN']);
	fclose($fpFILES['CHR_GN_MAP']);
	
	/// So we first insert gene
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.gn_entry(gn_entry_id,symbol,full_name,gene_id,gene_type,date_created,date_updated, last_checked,status)FROM \''.'GN_ENTRY.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."C01",'Unable to insert gene'); 
	/// locus to gene mapping
	
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.chr_gn_map(chr_gn_map_id,chr_map_id,gn_entry_id)FROM \''.'CHR_GN_MAP.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."C02",'Unable to insert tree'); 


	/// and open the new files
	$fpFILES=array(
		'GN'=>fopen('GN_ENTRY.csv','w'),
		'CHR_GN_MAP'=>fopen('CHR_GN_MAP.csv','w'));
	foreach ($fpFILES as $N=>$F)
		if ($F==null)failProcess($JOB_ID."C03",'Unable to open '.$N.' file ');



	$HAS_FILE_DATA=false;
	
	

}



function verifyData($CURR_TAX_ID,$LIST_ALL_GENES,$DIFF_TAX_GENES,&$STATS)
{
	/// Here we are going to verify that the number of genes in the file is the same as the number of genes in the database
	/// Via different queries
	/// It will also help us to identify gene to chr_map that are not in the file and should be deleted
	$res=runQuery("SELECT distinct gene_id, chr_gn_map_id 
		FROM gn_entry g, chr_gn_map cgm, chr_map cm, chromosome c, taxon t
		where t.taxon_id = c.taxon_id AND c.chr_id = cm.chr_id
		AND cm.chr_map_id= cgm.chr_map_id AND g.gn_entry_id = cgm.gn_entry_id 
		and tax_id='".$CURR_TAX_ID."'");
	if ($res===false)	failProcess($JOB_ID."D01",'Unable to get gene records ');
	$res2=runQuery("SELECT distinct gene_id 
		FROM gn_entry g, chr_gn_map cgm, chr_map cm, chromosome c, taxon t
		where t.taxon_id = c.taxon_id AND c.chr_id = cm.chr_id
		AND cm.chr_map_id= cgm.chr_map_id AND g.gn_entry_id = cgm.gn_entry_id 
		and tax_id='".$CURR_TAX_ID."'");
	if ($res2===false)	failProcess($JOB_ID."D02",'Unable to get gene records ');


	$STATS['DB_GENE']+=count($res2);


	/// CHR_GN_MAP to del
	$LIST_CHR_DEL=array();

	/// Loop over the records
	foreach ($res as $line)
	{
		/// And check that we can find from the taxon that gene
		if (isset($LIST_ALL_GENES[$line['gene_id']]))
		{
			$LIST_ALL_GENES[$line['gene_id']]=true;
			continue;
		}
		/// In the case where the taxon was merged with another, we need to take that into consideration
		else if (isset($DIFF_TAX_GENES[$CURR_TAX_ID])
		&&	in_array($line['gene_id'],$DIFF_TAX_GENES[$CURR_TAX_ID]))
		{
			echo "FROM MERGED TAXON: ".$line['gene_id']."\n";
			$LIST_ALL_GENES[$line['gene_id']]=true;
			continue;
		}
		/// Now if we don't find it, it means that chr to gn map is not in the file and should be deleted
		else {
			$LIST_CHR_DEL[$line['chr_gn_map_id']]=true;
		}
		
	}
	
	if ($LIST_CHR_DEL!=array())
	{
		addLog("DELETE ".count($LIST_CHR_DEL).' chr_gn_map');
		if (!runQueryNoRes('DELETE FROM chr_gn_map WHERE chr_gn_map_id IN ('.implode(',',array_keys($LIST_CHR_DEL)).')'))
		failProcess($JOB_ID."D03",'Unable to delete chr_gn_maps');
		$STATS['DB_GENE']-=count($LIST_CHR_DEL);
	}

	/// Overall validity of this taxon
	
	foreach ($LIST_ALL_GENES as $GENE_ID=>$STATUS) 
	{
		if($STATUS)continue;
		echo "\tTAX: ".$CURR_TAX_ID."\tGENE EXIST IN FILE NOT IN DB\t".$GENE_ID."\n";
		failProcess($JOB_ID."D04",'GENE EXIST IN FILE NOT IN DB');
	}
}



function processBulk(&$BULK,$TAX_ID)
{
	global $CHR_INFOS;
	echo "\n######################\t".count($BULK)."\n";
	
	// Here we fetch all the data associated to those gene Ids
	$res=runQuery("SELECT G.gn_entry_id,symbol,full_name,gene_id,gene_type,date_created,
	date_updated,last_checked, status,chr_gn_map_id,chr_map_id 
	FROM gn_entry G
	LEFT JOIN chr_gn_map CHM ON CHM.gn_entry_id = G.gn_entry_id 
	WHERE gene_id IN (".implode(',',array_keys($BULK)).')');
	if ($res===false)failProcess($JOB_ID."E01",'Unable to get gene records ');
	$MAP=array();
	foreach ($res as $K=>$line)$MAP[$line['gene_id']][]=$K;

	/// then we iterate each record
	foreach ($BULK as $gene_id=>$tab)
	{
		/// And look if we have data in the database also
		$db=array();
		if (isset($MAP[$gene_id]))
		foreach ($MAP[$gene_id] as $pos)$db[]=$res[$pos];
		///and we send both for processing
		processEntry($tab,$db,$TAX_ID);
	}
}





function processEntry(&$tab,&$res,$TAX_ID)
{
	/// So $tab contains the data from the file
	/// $res contains the data from the database for a given gene
	global $DBIDS;
	global $STATS;
	global $CHR_INFOS;
	global $DEBUG;
	global $fpos;
	global $N_DONE;
	global $UN_CHROMS;
	global $HAS_FILE_DATA;
	global $DIFF_TAX_GENES;
	++$N_DONE;
	$STATS['N_GENE']++;
	$GENE_ID=$tab[1];
	//$DEBUG=true;
	if ($CHR_INFOS[$TAX_ID]['DIFF_TAX'])$DIFF_TAX_GENES[$TAX_ID][]=$GENE_ID;
	//if ($N_DONE%1000==0)echo "#### ".$fpos."\t".$N_DONE."\t".$GENE_ID."\n";
	
	// 	[0] => #tax_id
	// [1] => GeneID
	// [2] => Symbol
	// [3] => LocusTag
	// [4] => Synonyms
	// [5] => dbXrefs
	// [6] => chromosome
	// [7] => map_location
	// [8] => description
	// [9] => type_of_gene
	// [10] => Symbol_from_nomenclature_authority
	// [11] => Full_name_from_nomenclature_authority
	// [12] => Nomenclature_status
	// [13] => Other_designations
	// [14] => Modification_date
	// [15] => Feature_type

/// Column 10 is the symbol from nomenclature authority. We will choose this one by default. If not available, we will take NCBI Symbol in Column 2
	$SYMBOL=($tab[10]!='-'&&$tab[10]!='')?$tab[10]:$tab[2];
		/// Column 11 is the full name from nomenclature authority. We will choose this one by default. If not available, we will take the description in Column 8
	$FULL_NAME=($tab[11]!='-'&&$tab[11]!='')?$tab[11]:$tab[8];
	/// In some instances, the name can be very long, mainly because it is a temp gene, so we just cut it to save space.
	if (strlen($FULL_NAME)>1500)$FULL_NAME=substr($FULL_NAME,0,1499);
	$GENE_TYPE=$tab[9];
	$STATUS=$tab[12];
	////echo "FULL NAME:".$FULL_NAME."\n";
	$ENTRY=array('syn'=>array());
	$CURR_CHRMAP_DBIDS=array();
	if ($DEBUG) echo $GENE_ID.'|'.$SYMBOL.'|'.$FULL_NAME."\n";
	/// No data in the database -> New entry
	if ($res==array())
	{
		
		insertGene($tab);
		$ENTRY['gn_entry_id']=$DBIDS['gn_entry'];
	if ($DEBUG) echo $GENE_ID.'|NEWLY INSERT GN_ENTRY:'.$ENTRY['gn_entry_id']."\n";

	}
	else  /// Entry exist, we compare the data
	{
		if ($DEBUG) echo $GENE_ID.'|EXISTING GENE'."\n";
		foreach ($res as $r_line)
		{
			if ($r_line['chr_map_id']!='')$CURR_CHRMAP_DBIDS[$r_line['chr_map_id']]=false;
		}
		$ENTRY=$res[0];
		/// By default, we consider the entry to be valid
		$ENTRY['DB_STATUS']='VALID';

		/// Then we compare each descriptor and see if they have changed.
		if ($ENTRY['symbol']!=$SYMBOL){
			addLog($GENE_ID."\tSYMBOL\t".$ENTRY['symbol']."\t".$SYMBOL);
			$ENTRY['symbol']=$SYMBOL;$ENTRY['DB_STATUS']='TO_UPD';}
		if ($ENTRY['full_name']!=$FULL_NAME){
			addLog($GENE_ID."\tFULL_NAME\t".$ENTRY['full_name']."\t".$FULL_NAME);
			$ENTRY['full_name']=$FULL_NAME;$ENTRY['DB_STATUS']='TO_UPD';}
		if ($ENTRY['gene_type']!=$GENE_TYPE){
			addLog($GENE_ID."\tGENE_TYPE\t".$ENTRY['gene_type']."\t".$GENE_TYPE);
			$ENTRY['gene_type']=$GENE_TYPE;$ENTRY['DB_STATUS']='TO_UPD';}
		if ($ENTRY['status']!=$STATUS){
			addLog($GENE_ID."\tSTATUS\t".$ENTRY['status']."\t".$STATUS);
			$ENTRY['status']=$STATUS;$ENTRY['DB_STATUS']='TO_UPD';}

			/// If one of them changes -> Update query
		if ($ENTRY['DB_STATUS']=='TO_UPD')
		{
			if(!runQueryNoRes("UPDATE gn_entry SET symbol='".str_replace("'","''",$SYMBOL)."',
				full_name='".str_replace("'","''",$FULL_NAME)."', 
				gene_type='".str_replace("'","''",$GENE_TYPE)."', 
				status='".str_replace("'","''",$STATUS).
				"' WHERE gene_id=".$GENE_ID)) failProcess($JOB_ID."F01",'Unable to update gene entry '.$GENE_ID);

		}

		
		
	}

	/// Whether the record is new or already exist in the database, we now check chromosomes and locus

	/// Check chromsome
	$CHROMS=explode("|",$tab[6]);
	if ($DEBUG){echo $GENE_ID."|CHROMS:".implode('||',$CHROMS)."\n";}
	$CURR_CHROM=array();
	foreach ($CHROMS as $CHROM)
	
	{
		if ($DEBUG)echo "\tTEST CHROM:".$CHROM."\n";
		/// There is a set of chromosome name that we are going to consider as unknown chromosomes
		if (in_array($CHROM,$UN_CHROMS))
		{
			if ($DEBUG)echo "\t\tUNKNOWN CHROM\n";
			/// So we check if the unknown chromosome is listed for this organism
			if (!isset($CHR_INFOS[$TAX_ID]['CHR']['Un']))
			{
				/// And if not, we create it
				insertChromosome($CHR_INFOS[$TAX_ID]['DBID'],'Un');
				if ($DEBUG)echo "\t\tNEW CHR: Un:".$DBIDS['chromosome']."\n";
				$CHR_INFOS[$TAX_ID]['CHR']['Un']=array('STATUS'=>'VALID','DBID'=>$DBIDS['chromosome'],'CHR_MAP'=>array());

			}else 
			{
				/// Otherwise, we make sure that the chromosome  is valid since it's being used
				if ($DEBUG)echo "\t\tVALID CHROM\n";
				$CHR_INFOS[$TAX_ID]['CHR']['Un']['STATUS']='VALID';
			}
			$CURR_CHROM[]='Un';
			continue;
		}
		/// The chromosome is not the unknown chromosome
		/// still need to check if it has already been reported for this organism
		else if (!isset($CHR_INFOS[$TAX_ID]['CHR'][$CHROM]))
		{
			/// If not, we create it
			if ($DEBUG)echo "\t\tNEW CHR-2: ".$CHROM."\n";
			insertChromosome($CHR_INFOS[$TAX_ID]['DBID'],$CHROM);
			if ($DEBUG)echo $DBIDS['chromosome']."\n";
			$CHR_INFOS[$TAX_ID]['CHR'][$CHROM]=array('DBID'=>$DBIDS['chromosome'],'STATUS'=>'VALID','CHR_MAP'=>array());
			
		}else 
		{
			/// Otherwise, we make sure that the chromosome  is valid since it's being used
				
			if ($DEBUG)echo "\t\t".$TAX_ID."\t".$CHROM."\tVALID\n";
			$CHR_INFOS[$TAX_ID]['CHR'][$CHROM]['STATUS']='VALID';
		}
		$CURR_CHROM[]=$CHROM;
		

	}

	/// Now we look at locus. As Locus can take many forms, we only consider clearly defined locus
	/// that follows the standard: Chromosome p|q arm and sub-band
	if ($DEBUG)echo "\n\nCURR CHROMS:\n";
	if ($DEBUG)print_r($CURR_CHROM);
	
	/// Cleaning step: removing +/-
	if (strpos($tab[7],"+/-")!==false)$tab[7]=substr($tab[7],0,strpos($tab[7],"+/-"));
	
	/// cM is not considered and set to Unknown
	if (preg_match("/[0-9]{1,2} cM/",$tab[7]))
	{
		if ($DEBUG)echo $tab[7]."\tcM MATCH\n";
		$tab[7]='Un';
	}


	/// $CHR_MAPS becomes an array of loci
	$tab[7]=str_replace(" and ","|",$tab[7]);
	$CHR_MAPS=explode("|",$tab[7]);
	if ($CHR_MAPS[0]==$tab[7])$CHR_MAPS=explode(";",$tab[7]);
	if ($CHR_MAPS[0]==$tab[7])$CHR_MAPS=explode(" ",$tab[7]);
	sort($CHR_MAPS);$CHR_MAPS=array_unique($CHR_MAPS);
	if ($DEBUG)echo "\n\nINI CHRMAP:\n";
	if ($DEBUG)print_r($CHR_MAPS);
	//print_r($res);
	$need_dummy=false;


	/// Then we map each locus to the chromosome
	$CHR_MAP_DBIDS=array();$HAS_VALID_CHR_MAP=false;
	foreach ($CHR_MAPS as $CHR_M)
	{
		$CHR_M=trim($CHR_M);
		if ($DEBUG)echo $GENE_ID."|CHROM MAP:".$CHR_M."\n";
		/// Similar process as unknown chromosome, we have unknown locus
		if ($CHR_M=="-")
		{
			if ($DEBUG)echo "\t IS - -> DUMMY\n";
			$need_dummy=true;
			continue;
		}
		/// we still validate the locus
		else if (!isValidMapLoc($CHR_M))
		{
			$need_dummy=true;
			if ($DEBUG)echo "\tINVALID\n";
				continue;
		}
		else
		{
			/// Then we compare the chromosome in the locus vs the list of chromosome provided in the previous step
			$CHROM=checkChrom($CHR_M,$CURR_CHROM);
			
			if ($CHROM==$CHR_M)$CHR_M='Un';
			if ($DEBUG)echo "\tASSOCIATED CHROM:|".$CHROM."|\t".$CHR_M."\n";

			/// In some instance, we just can cannot find the chromosome so we are going to set the chromosome to Unknown.
			/// Yet sometimes, the Unknown chromosome is not reported for this organism, so we create it
			/// And create the unknown locus (CHR_MAP)
			if ($CHROM===false && !isset($CHR_INFOS[$TAX_ID]['CHR']['Un']))
			{
				
				if ($DEBUG)echo "\t\tNO CHROM AND NO Un CHROM\n";
				insertChromosome($CHR_INFOS[$TAX_ID]['DBID'],'Un');
				////Create chrom and chr map
				$CHR_INFOS[$TAX_ID]['CHR']['Un']=array('DBID'=>$DBIDS['chromosome'],'STATUS'=>'VALID','CHR_MAP'=>array());
				
				insertChrMap($DBIDS['chromosome'],'Un');
				$CHR_INFOS[$TAX_ID]['CHR']['Un']['CHR_MAP']['Un']=array('chr_map_id'=>$DBIDS['chr_map'],'STATUS'=>'VALID');
				if ($DEBUG) echo "\t\tCREATE UN CHR FOR ".$TAX_ID.' (DBID:'.$DBIDS['chromosome'].')'."\n";
				if ($DEBUG) echo "\t\tCREATE UN CHR_MAP FOR UN CHR FOR ".$TAX_ID.' (DBID:'.$DBIDS['chr_map'].')'."\n";
				$CHR_MAP_DBIDS[$DBIDS['chr_map']]=true;
			}
			else
			{
			
				if ($CHROM===false){
					$CHROM='Un';//echo "->Un";$CHR_M='Un';
					
					if (!isset($CHR_INFOS[$TAX_ID]['CHR']['Un']))
					{
						if ($DEBUG)echo "\t\tNO CHROM AND NO Un CHROM P2\n";	
						
						insertChromosome($CHR_INFOS[$TAX_ID]['DBID'],'Un');
						////Create chrom and chr map
						$CHR_INFOS[$TAX_ID]['CHR'][$CHROM]=array('DBID'=>$DBIDS['chromosome'],'STATUS'=>'VALID','CHR_MAP'=>array());
					}
				//print_R(array_keys($CHR_INFOS[$TAX_ID]['CHR'][$CHROM]['CHR_MAP']));
				}

				//// Now that we have the corresponding chromosome entry
				/// We are going to look if that locus is already assigned to that chromosome in the datbasase
				$CHR_INFO=&$CHR_INFOS[$TAX_ID]['CHR'][$CHROM]['CHR_MAP'];
				
				if ($DEBUG)echo "\t\tCHROM:".$CHROM."\tDBID:".$CHR_INFOS[$TAX_ID]['CHR'][$CHROM]['DBID']."\n";

				/// No? We create it
				if (!isset($CHR_INFO[$CHR_M]))
				{
					if ($DEBUG)echo "\t\t\tNO ".$CHR_M." FOUND\n";
					
					if (!isset($CHR_INFOS[$TAX_ID]['CHR'][$CHROM]['DBID']))
					{
						//echo "ISSUE:".$CHROM."\n";
						if ($DEBUG)print_r($CHR_INFOS[$TAX_ID]['CHR'][$CHROM]);
						exit;
					}
					
					if ($DEBUG)echo "\t\t\tTAXID:".$TAX_ID."\tCHROM:".$CHROM."\tCHR MAP:".$CHR_M."\t";
					insertChrMap($CHR_INFOS[$TAX_ID]['CHR'][$CHROM]['DBID'],$CHR_M);
					if ($DEBUG)echo "\tDBID:".$DBIDS['chr_map']."\n";
					$CHR_INFOS[$TAX_ID]['CHR'][$CHROM]['CHR_MAP'][$CHR_M]=array('chr_map_id'=>$DBIDS['chr_map'],'STATUS'=>'VALID');
					$CHR_MAP_DBIDS[$DBIDS['chr_map']]=true;
					
				}else 
				{
					/// Otherwise it exists - set the status to valid!
					if ($DEBUG)echo "\t\tEXISTING\tTAXID:".$TAX_ID."\tCHROM:".$CHROM."\tCHR MAP:".$CHR_M."\n";
					$CHR_INFO[$CHR_M]['STATUS']='VALID';
					
					if ($DEBUG)						print_r($CHR_INFO);
					$DBID=$CHR_INFO[$CHR_M]['chr_map_id'];
					if (isset($CURR_CHRMAP_DBIDS[$DBID]))$CURR_CHRMAP_DBIDS[$DBID]=true;
					else $CHR_MAP_DBIDS[$DBID]=true;
				}
				
			}
			$need_dummy=false;
		}
			
	}
	
	/// In some instances, we need to create a dummy locus that doesn't specify the chromosome
	/// So we are going back to the list of chromosomes provided the NCBI and create a dummy locus for each of them
	if ($need_dummy)
	{
		if ($DEBUG)echo $GENE_ID."|NEEDED_DUMMY\n";
		
		foreach($CURR_CHROM as $CHR)
		{
			$CHROM_ENTRY=&$CHR_INFOS[$TAX_ID]['CHR'][$CHR];
			if ($DEBUG)echo $GENE_ID."\tTEST CHROM:".$CHR."\n";
			if (isset($CHROM_ENTRY['CHR_MAP']['Un']))
			{
				if ($DEBUG)echo $GENE_ID."\t\tHAS UN CHR_MAP\n";
				$CHROM_ENTRY['CHR_MAP']['Un']['STATUS']='VALID';
				if ($DEBUG)print_r($CHROM_ENTRY['CHR_MAP']['Un']);
				$DBID=$CHROM_ENTRY['CHR_MAP']['Un']['chr_map_id'];
				if (isset($CURR_CHRMAP_DBIDS[$DBID]))$CURR_CHRMAP_DBIDS[$DBID]=true;
				else $CHR_MAP_DBIDS[$DBID]=true;
				continue;
			}
			if ($DEBUG)echo $GENE_ID.":\t\tMISSING UN CHR_MAP\n";
			insertChrMap($CHROM_ENTRY['DBID'],'Un');
			$CHROM_ENTRY['CHR_MAP']['Un']=array('chr_map_id'=>$DBIDS['chr_map'],'STATUS'=>'VALID');
			$CHR_MAP_DBIDS[$DBIDS['chr_map']]=true;
		}	
	}
	if ($DEBUG){echo $GENE_ID."|CURRENT CHRMAP:".implode('|',$CURR_CHRMAP_DBIDS)."\n";
	echo $GENE_ID."|CHR_MAP_DBIDS:".implode('||',$CHR_MAP_DBIDS)."\n";
	}
	/// Now any insert any locus to gene records:
	foreach ($CHR_MAP_DBIDS as $CHR_MAP_ID=>$VALUE)
	{
		//print_r($CHR_MAP_DBIDS);
		if ($DEBUG)echo $GENE_ID."|\tNEW CHRGNMAP:\t".$CHR_MAP_ID."\t".$ENTRY['gn_entry_id']."\n";
		insertChrGnMap($CHR_MAP_ID,$ENTRY['gn_entry_id']);
		//GN_CHR_MAP to insert
		
	}
	/// And remove those that haven't been found
	foreach ($CURR_CHRMAP_DBIDS as $DBID=>$VALUE)
	{
		if ($VALUE)continue;
		if ($DEBUG)echo $GENE_ID."|\tDELETE CHRGNMAP\t".$CHR_MAP_ID."\t".$ENTRY['gn_entry_id']."\n";
		//print_r($CURR_CHRMAP_DBIDS);
		///GN_CHR_MAP TO delete
		$query="DELETE FROM chr_gn_map WHERE chr_map_id=".$DBID.' AND gn_entry_id='.$ENTRY['gn_entry_id'] ;
		if (!runQueryNoRes($query))
		failProcess($JOB_ID."F02",'Unable to delete chr gn map '.$query);
		//echo "\tDELETE CHRGNMAP:".$DBID;
		
	}
	if ($DEBUG)print_r($ENTRY);
	//echo "\n";
	

	

	/*
	[0] => 9606
[1] => 1
[2] => A1BG
[3] => -
[4] => A1B|ABG|GAB|HYST2477
[5] => MIM:138670|HGNC:HGNC:5|Ensembl:ENSG00000121410
[6] => 19
[7] => 19q13.43
[8] => alpha-1-B glycoprotein
[9] => protein-coding
[10] => A1BG
[11] => alpha-1-B glycoprotein
[12] => O
[13] => alpha-1B-glycoprotein|HEL-S-163pA|epididymis secretory sperm binding protein Li 163pA
[14] => 20210506
[15] => -
*/
	
}

	


function insertChrGnMap($CHR_MAP_ID,$gn_entry_id)
{
	global $DBIDS;global $DEBUG;
	if ($DEBUG)print_r($DBIDS);
	$DBIDS['chr_gn_map']++;
	global $fpFILES;;global $HAS_FILE_DATA;
	//echo "INSERT CHR_GN_MAP: ".$CHR_MAP_ID.'|'.$gn_entry_id."\n";
	if ($CHR_MAP_ID!='')
	fputs($fpFILES['CHR_GN_MAP'],$DBIDS['chr_gn_map']."\t".$CHR_MAP_ID."\t".$gn_entry_id."\n");
	$HAS_FILE_DATA=true;
		
}






function insertGene($tab)
{
	global $DBIDS;global $DEBUG;global $HAS_FILE_DATA;
	if ($DEBUG)print_r($DBIDS);
	$DBIDS['gn_entry']++;
	$GENE_ID=$tab[1];
	//echo "\tNEW GENE:".$DBIDS['gn_entry'];
	$SYMBOL=prepString(($tab[10]!='-'&&$tab[10]!='')?$tab[10]:$tab[2]);
	$FULL_NAME=prepString(($tab[11]!='-'&&$tab[11]!='')?$tab[11]:$tab[8]);
	if (strlen($FULL_NAME)>1500)$FULL_NAME=substr($FULL_NAME,0,1499);
	$GENE_TYPE=$tab[9];
	$STATUS=$tab[12];


	global $fpFILES;
	fputs($fpFILES['GN'],$DBIDS['gn_entry']."\t".$SYMBOL."\t".$FULL_NAME."\t".$GENE_ID."\t".$GENE_TYPE."\tNOW()\tNULL\tNOW()\t".$STATUS."\n");
	$HAS_FILE_DATA=true;
	
}








function insertChromosome($TAXDBID,$CHR_NUM)
{
	global $DBIDS;global $DEBUG;
	if ($DEBUG)print_r($DBIDS);
	$DBIDS['chromosome']++;
	//echo "\tNEW CHROM:".$TAXDBID."::".$CHR_NUM.":".$DBIDS['CHROMOSOME'];
	$query='INSERT INTO chromosome (chr_id,
	date_created,
	date_updated,
	taxon_id,
	chr_num) VALUES ('.$DBIDS['chromosome'].', CURRENT_TIMESTAMP,NULL,'.$TAXDBID.",'".$CHR_NUM."')";
	if (!runQueryNoRes($query)) failProcess($JOB_ID."G01",'Unable to insert new chromsome '.$query);
	
}



function insertChrMap($CHR_ID,$map_loc)
{
	global $DBIDS;global $DEBUG;
	if ($DEBUG)	print_r($DBIDS);
	$arm='Un';
	$chr_name='NULL';
	$band='NULL';
	$subband='NULL';

	/// Here we are breaking locus based on chromosome, arm, arm and sub-band
	if ($map_loc!='Un'){
		$pos=-1;
		

		for ($I=0;$I<strlen($map_loc);++$I)
		{
			$L=substr($map_loc,$I,1);
			if ($L!="p"&&$L!="q")continue;
			$pos=$I;break;
		}
		
		if ($pos!=-1)
		{
	
			$chr_name=substr($map_loc,0,$pos);
			$arm=substr($map_loc,$pos,1);
			$pos2=strpos($map_loc,".");
			
			if ($pos2===false)
			{

				if ($pos+1!=strlen($map_loc))
				{
					if ($DEBUG)echo $map_loc.' '.$band."\t1\n";
					$band=substr($map_loc,$pos+1);
				}
			}
			else
			{
				$band=substr($map_loc,$pos+1,$pos2-$pos-1);
				if ($DEBUG)echo $map_loc.' '.$band."\t2\n";
				$pos3=strpos($map_loc,"-",$pos2+1);
				if ($pos3===false)
				{
					$txt=substr($map_loc,$pos2+1);
					if (strlen($txt)<4)
						$subband=$txt;
					else
						$subband=substr($map_loc,$pos2+1,4);
				}
				else $subband=substr($map_loc,$pos2+1,$pos3-$pos2-1);

			}
		}
	}
	
	++$DBIDS['chr_map'];
	if ($DEBUG)echo "MAP LOC:".$map_loc."\nCHR:".$chr_name."\nREGION:".$arm."\nBAND:".$band."\t".$DBIDS['chr_map']."\n";;
	//echo "\tNEW CHRMAP:".$map_loc."::".$DBIDS['CHR_MAP'];
		$query='INSERT INTO chr_map (chr_map_id,
		chr_id,
		map_location,
		position,
		arm,
		band,
		subband) VALUES ('.$DBIDS['chr_map'].', '.$CHR_ID.",".(($map_loc=='NULL')?'NULL':"'".$map_loc."'").","
		.(($chr_name=='NULL')?'NULL':"'".$chr_name."'").","
		.(($arm=='NULL')?'NULL':"'".$arm."'").",'".$band."',".$subband.')';
		if ($DEBUG)echo $query."\n";
		if (!runQueryNoRes($query)) failProcess($JOB_ID."H01",'Unable to insert new chr map '.$query);
		if ($DEBUG)print_r($DBIDS);
		return ;
}








function checkChrom($map_loc,$CURR_CHROM)
{
	$pos=-1;
	if (in_array($map_loc,$CURR_CHROM))return $map_loc;
	for ($I=0;$I<strlen($map_loc);++$I)
	{
		$L=substr($map_loc,$I,1);
		if ($L!="p"&&$L!="q")continue;
		$pos=$I;break;
	}
	if ($pos==-1)return false;
	
	$name=substr($map_loc,0,$pos);
	
	foreach ($CURR_CHROM as $CR) if ($CR==$name)return $name;
	return false;

}




function isValidMapLoc(& $map_loc)
{
	//echo "TEST:".$map_loc."\n";
	if (strpos($map_loc,"cM")!==false)return false;
	if (strpos($map_loc,"cen")!==false)return false;
    if (strpos($map_loc,"C")!==false)return false;
    if (strpos($map_loc,"D")!==false)return false;
    if (strpos($map_loc,"E")!==false)return false;
    if (strpos($map_loc,"F")!==false)return false;
    if (strpos($map_loc,"A")!==false)return false;
    if (strpos($map_loc,"B")!==false)return false;
    if (strpos($map_loc,"G")!==false)return false;
    if (strpos($map_loc,"H")!==false)return false;
    if (strpos($map_loc,"alternate")!==false)return false;
    if (strpos($map_loc,"h")!==false)return false;
    if (strpos($map_loc,"tbd")!==false)return false;
	if (strpos($map_loc,"L")!==false)return false;
	if (!preg_match("/^[0-9|X|Y|MT]{1,2}(([pq]{1}[0-9]{1,3}(\.[0-9]){0,3}){0,1}){0,1}/",$map_loc,$matches))return false;
    return true;
}










/**
 * loadTax
 * 
 * $CURR_TAX_ID => data to load into
 * $TAX_ID => current taxon to load data from
 */
function loadTax(&$CURR_TAX_ID,$TAX_ID)
{
	global $MERGED_TAXON;
	$DIFF_TAX=false;
	if (isset($MERGED_TAXON[$TAX_ID]))
	{
		addLog("ALTER TAXON ".$TAX_ID." ".$MERGED_TAXON[$TAX_ID]."\n");
		$TAX_ID=$MERGED_TAXON[$TAX_ID];
		$DIFF_TAX=true;
	}
	//echo "############## NEW TAXON ".$TAX_ID."\n";
	/// Get the taxon information
	$res=runQuery("SELECT taxon_id 
					FROM taxon 
					WHERE tax_id ='".$TAX_ID."'");
					if ($res===false)failProcess($JOB_ID."I01",'Unable to get taxon');
	if ( count($res)==0){ echo "NO TAXON FOUND: ".$TAX_ID."\n";	return false;}

	$CURR_TAX_ID=array();
	$CURR_TAX_ID[$TAX_ID]=array('DBID'=>$res[0]['taxon_id'],'CHR'=>array(),'DIFF_TAX'=>$DIFF_TAX);

	/// Then all the chromosomes
	$res=runQuery("SELECT chr_id,chr_num 
					FROM chromosome 
					WHERE taxon_id = ".$CURR_TAX_ID[$TAX_ID]['DBID']);
	if ($res===false)	failProcess($JOB_ID."I02",'Unable to get chromosome records ');
	foreach ($res as $line)$CURR_TAX_ID[$TAX_ID]['CHR'][$line['chr_num']]=array('DBID'=>$line['chr_id'],'STATUS'=>'FROM_DB','CHR_MAP'=>array());
	
	/// Then all the locus:
	$res=runQuery("SELECT chr_map_id,chr_num,map_location,position,arm, band,subband
					FROM chr_map CH, chromosome C WHERE C.chr_id = CH.chr_id AND taxon_id = ".$CURR_TAX_ID[$TAX_ID]['DBID']);
	if ($res===false)failProcess($JOB_ID."I03",'Unable to get chromosome records ');
	foreach ($res as $line)
	{
		$line['STATUS']='FROM_DB';
		$CURR_TAX_ID[$TAX_ID]['CHR'][$line['chr_num']]['CHR_MAP'][$line['map_location']]=$line;
	}
	return true;


}
	
?>