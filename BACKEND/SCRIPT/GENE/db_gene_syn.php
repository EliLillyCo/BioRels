<?php




// Need extra memory
ini_set('memory_limit','10000M');
error_reporting(E_ALL);

/**
 SCRIPT NAME: db_gene_syn
 PURPOSE:     Process part of NCBI gene annotation related to gene symbols and names
 
*/

/// Job name - Do not change
$JOB_NAME='db_gene_syn';


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


addLog("Check directory");
	/// Get Parent info
	$DB_GENE_INFO=$GLB_TREE[getJobIDByName('db_gene')];
	/// Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DB_GENE_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."002",'Unable to access '.$W_DIR);

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$DB_GENE_INFO['TIME']['DEV_DIR'];
	

	/// Check if the files are there
	$F_FILE=$W_DIR.'/gene_info_ordered';	if (!checkFileExist($F_FILE))			failProcess($JOB_ID."003",'NO '.$F_FILE.' found ');

	/// Check if the files are there
	$F_HIST=$W_DIR.'/gene_history';			if (!checkFileExist($F_HIST))			failProcess($JOB_ID."004",'NO '.$F_HIST.' found ');
	
	
	
addLog("Working directory: ".$W_DIR);

	$DEBUG=false;

	/// We get the max ID for each table
	$DBIDS=array('gn_syn_map'=>-1,'gn_syn'=>-1);
	foreach ($DBIDS as $TBL=>$R)
	{
		$N=$TBL;
		$res=runQuery("SELECT MAX(".$N.'_id) co FROM '.$TBL.' WHERE '.$N.'_ID < 999999995');
		if ($res===false)															failProcess($JOB_ID."006",'Unable to get max value');
		if ($res==array())$DBIDS[$TBL]=0;
		else $DBIDS[$TBL]=$res[0]['co'];
	}
	
	$STATS['N_SYN']=0;


	$fpFILES=array(
		'GN_SYN_MAP'=>fopen('GN_SYN_MAP.csv','w'),
		'GN_SYN'=>fopen('GN_SYN.csv','w'));
	foreach ($fpFILES as $N=>$F)if ($F==null)										failProcess($JOB_ID."007",'Unable to open '.$N);

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
	$fp=fopen($W_DIR.'/gene_info_ordered','r'); if (!$fp)							failProcess($JOB_ID."008",'Unable to open gene_info');
	//fseek($fp,4594320019);
	$line=stream_get_line($fp,1000,"\n");


	$N_L=0;
	$valid_taxon=true;
	$BLOCK=array();
	$CURR_TAX_ID=-1;
	
	$N_PER_TAX=0;
	$GENE_TAXON=array();
	while(!feof($fp))
	{
		$fpos=ftell($fp);
		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;
		$N_L++;
		
		$tab=explode("\t",$line);
		//echo $line."\n";
		/// First we check the tax ID and see if the user as requested to only consider some organisms
		$TAX_ID=$tab[0];

		/// New taxon, let's get some info first
		if ($TAX_ID!=$CURR_TAX_ID)
		{
			
			
			if ($BLOCK!=array())	processBlock($BLOCK); 

			/// We get the number of synonyms we have in the db for the genes in the current taxon (CURR_TAX_ID)
			$CHUNKS=array_chunk(array_keys($GENE_TAXON),10000);$N=0;
			foreach ($CHUNKS as $CHUNK)
			{
				$res=runQuery("select COUNT(DISTINCT GSM.gn_syn_map_id) co
				FROM gn_syn GS, gn_syn_map GSM, gn_entry ge
				WHERE GS.gn_syn_id = GSM.gn_syn_id  AND ge.gn_entry_id = gsm.gn_entry_id
				AND gene_id IN (".implode(',',$CHUNK).')');
				if ($res===false)													failProcess($JOB_ID."009",'Unable to get gene synonyms');
				$N+=$res[0]['co'];
			}

			/// We get the number of synonyms we have in the database, but this time based on the taxon
			$query="SELECT count(DISTINCT gn_syn_map_id) CO FROM gn_syn_map gsm, gn_entry g, chr_gn_map cgm, chr_map cm, chromosome c, taxon t
			where t.taxon_id = c.taxon_id AND c.chr_id = cm.chr_id
			AND cm.chr_map_id= cgm.chr_map_id AND g.gn_entry_id = cgm.gn_entry_id 
			AND gsm.gn_entry_Id = g.gn_entry_Id
			and tax_id='".$CURR_TAX_ID."'";
			$res=runQuery($query);
			if ($res===false)												failProcess($JOB_ID."010",'Unable to get gene synonyms');
			/// If the number of synonyms is different, then we have a problem
			if ($N!=$N_PER_TAX )echo ($CURR_TAX_ID.' DIFFERENT COUNT BETWEEN '.$N_PER_TAX.'/'.$res[0]['co']."/".$N."\n");
			if ($N!=$res[0]['co'] )echo ($CURR_TAX_ID.' DIFFERENT COUNT '.$N_PER_TAX.'/'.$res[0]['co']."/".$N."\n");



			$GENE_TAXON=array();
			$N_PER_TAX=0;
			$BLOCK=array();
			$CURR_TAX_ID=$TAX_ID;
			echo "\n\n\n\nNEW TAXON - ".$TAX_ID." ".$fpos."\t";
			$res=runQuery("SELECT taxon_id 
						FROM taxon 
						WHERE tax_id ='".$TAX_ID."'");
			if ($res===false)												failProcess($JOB_ID."011",'Unable to get taxon');
			if (count($res)==0)
			{
				$valid_taxon=false;
				echo "INVALID\n";
				if (!$valid_taxon)continue;
			}
			$valid_taxon=true;
			echo "VALID\n\n\n";	
		}
			/// If the taxon is not in the list -> continue
		if (!$valid_taxon)continue;
		//if ($tab[1]!=100912557)continue;

		/// Now we gather the symbols (S) and names (N) from both the nomenclature authority AND NCBI
		$list_symbols=array();
		/// We get the symbols and names from the nomenclature authority, only if they are not empty
		if ($tab[2]!='-' && $tab[2]!='')$list_symbols[$tab[2]]='S';
		if ($tab[4]!='-')
		{
			$tabS=explode("|",$tab[4]);
			foreach ($tabS as $T)if ($T!='')$list_symbols[$T]='S';
		}
		///[10] => Symbol_from_nomenclature_authority
		if ($tab[10]!='-' && $tab[10]!='')$list_symbols[$tab[10]]='S';

		//[8] => description
		if ($tab[8]!='-' && $tab[8]!='')$list_symbols[$tab[8]]='N';

		//	[11] => Full_name_from_nomenclature_authority
		if ($tab[11]!='-')
		{
			$tabS=explode("|",$tab[11]);
			foreach ($tabS as $T)if ($T!='')$list_symbols[$T]='N';
		}

		//[13] => Other_designations
		if ($tab[13]!='-')
		{
			$tabS=explode("|",$tab[13]);
			foreach ($tabS as $T)if ($T!='')$list_symbols[$T]='N';
		}

		///[5] => dbXrefs
		if ($tab[5]!='-')
		{
			$tabS=explode("|",$tab[5]);
			foreach ($tabS as $T)
			{
				$tab2=strpos($T,':');
				if ($tab2!==false)
				{
					$t3=substr($T,$tab2+1);
					if ($t3!='')$list_symbols[$t3]='E';
				}
				else if ($T!='') $list_symbols[$T]='E';
			}
			//VGNC:VGNC:12958|Ensembl:ENSPTRG00000015954	
		}
		/// we track the number of synonyms for the taxon
		$N_PER_TAX+=count($list_symbols);

		$STATS['N_SYN']+=count($list_symbols);
		
		$BLOCK[$tab[1]]=$list_symbols;
		$GENE_TAXON[$tab[1]]=true;
	//	echo "COUNT GENES: ".count($LIST_GENES)."\n";
		
		/// To speed up the process, we do batches
		if (count($BLOCK)<50000)continue;
		processBlock($BLOCK);
		$BLOCK=array();
			
	}



	if (count($BLOCK)>0)	processBlock($BLOCK);


	fclose($fp);




/// After cleaning up the genes and their symbols/names, some gn_syn records might not be connected 
//// to any gn_syn_map records, so we delete those
if (!runQueryNoRes("DELETE FROM gn_syn where gn_syn_id NOT IN (select distinct gn_syn_id FROM gn_syn_map)"))
failProcess($JOB_ID."012",'Unable to delete obsolete gene synonyms	');

echo "TOTAL NUMBER OF SYNONYMS:".$STATS['N_SYN']."\n";
//updateStat('gn_syn_map','gene_syn',$STATS['N_SYN'],$JOB_ID);


successProcess();



















function processBlock(&$BLOCK)
{
	global $fpFILES;
	global $DBIDS;
	global $GLB_VAR;
	global $DB_INFO;
	
	global $JOB_ID;
	global $DATA_ALL;
	addLog('  Process BLock '.count($BLOCK));
	
	/// So we first get the database ID for the genes
	$res=runQuery("SELECT gn_entry_id, gene_id 
					FROM gn_entry 
					where gene_id IN (".implode(',',array_keys($BLOCK)).')');
	if ($res===false)									failProcess($JOB_ID."A01",'Unable to get genes');
	if (count($res)==0){addLog("No gene found");return false;}
	
	$MAP_ID=array();
	$ENTRIES=array();
	$NEW_DATA=false;
	
	
	addLog('  |-->'.count($res).' genes');
	foreach ($res as $line)
	{
		$MAP_ID[$line['gene_id']]=$line['gn_entry_id'];
		$ENTRIES[$line['gn_entry_id']]=array();
	}


	/// then we get ALL synonyms for those genes.
	$res=runQuery("SELECT GS.gn_syn_Id,syn_type,syn_value,gn_syn_map_id,gn_entry_id
					FROM gn_syn GS, gn_syn_map GSM
					WHERE GS.gn_syn_id = GSM.gn_syn_id 
					AND gn_entry_id IN (".implode(',',$MAP_ID).')');
	if ($res===false)									failProcess($JOB_ID."A02",'Unable to get gene synonyms');
	
	addLog('  |-->'.count($res).' existing synonyms');
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$ENTRIES[$line['gn_entry_id']][]=$line;
	}

	$NEW=array();$SYN_TO_DEL=array();
	/// And the we compare
	foreach ($BLOCK as $GENE_ID=>$list_symbols)
	{
		
		/// Technically, the db_gn_syn must be run after db_gene script.
		/// If not, then there is no database record for the gene entry we are working on so it crash 
			if (!isset($MAP_ID[$GENE_ID]))			failProcess($JOB_ID."A03","gene ID ".$GENE_ID.' not found in database');
		
		$GN_ENTRY_ID=$MAP_ID[$GENE_ID];
		$ENTRY=&$ENTRIES[$GN_ENTRY_ID];
		foreach ($list_symbols as $KT=>$TYPE)
		{
			
			if ($KT=='')continue;
			$S=$KT;
			
			$FOUND=false;
			// We compare the type of synonym and the value against all recorded entries in the database			
			foreach ($ENTRY as &$S_E)
			{
				
				if ($S_E['syn_type']!=$TYPE)continue;
				if ($S_E['syn_value']!=$S)continue;
				
				$S_E['DB_STATUS']='VALID';
				//$DATA_ALL[$S_E['gn_syn_map_id']]=true;
				$FOUND=true;
			}
			if ($FOUND)continue;
			/// Those are not found in the database will foregoe another process
			$NEW[$S.'||||'.$TYPE][]=$GN_ENTRY_ID;
		}
				/// And those that we didn't find in the file are going to removed
		foreach ($ENTRY as &$S_E)
		{		
			if ($S_E['DB_STATUS']!='FROM_DB')continue;
	
			$SYN_TO_DEL[]=$S_E['gn_syn_map_id'];
		}
		
	}
	
	
	/// So we delete those records in the database not found in the file
	if ($SYN_TO_DEL!=array())
	{
		addLog("  |--> DELETE ".count($SYN_TO_DEL));
		$query='DELETE FROM gn_syn_map WHERE gn_syn_map_id IN ('.implode(",",$SYN_TO_DEL).')';
		//	echo $query."\n";
		if (!runQueryNoRes($query)) 												failProcess($JOB_ID."A04",'Unable to delete new syn map '.$query);
	}


	/// Because a given synonym can exist in multiple gene records
	/// The synonym can exist for a given gene but not for ours,
	/// So we are going to search for all new synonyms to see if they are in reality already in the system
	addLog('  |--> new synonyms: '.count($NEW));
	$IDS=array();
	$CHUNKS=array_chunk(array_keys($NEW),5000);
	foreach ($CHUNKS as $CHUNK)
	{
		$query='SELECT gn_syn_id, syn_type,syn_value FROM gn_syn WHERE (syn_type,syn_value) IN (';
		foreach ($CHUNK as $t)
		{
			$tab=explode('||||',$t);
			$query.="('".str_replace("'","''",$tab[1])."','".str_replace("'","''",$tab[0])."'),";
		}
		$query=substr($query,0,-1).')';
		$res=runQuery($query);if ($res===false)										failProcess($JOB_ID."A05",'Unable to check for existing  syn '.$query);
		foreach ($res as $line)$IDS[$line['syn_value'].'||||'.$line['syn_type']]=$line['gn_syn_id'];
	}



	/// Then we look again at those new synonyms
	foreach ($NEW as $SYN=>$LIST_GN)
	{
		$SYN_ID=-1;
		/// And see if we found them in the database
		if (isset($IDS[$SYN]))$SYN_ID=$IDS[$SYN];
		else 
		{
			/// No? It's a brand new synonym then we insert it
			$DBIDS['gn_syn']++;
			$SYN_ID=$DBIDS['gn_syn'];
			$tab=explode('||||',$SYN);
			$MAP_ID[$GENE_ID];
			fputs($fpFILES['GN_SYN'],$DBIDS['gn_syn']."\t".$tab[1]."\t".'"'.str_replace('"','""',$tab[0]).'"'."\n");
		}
		/// Insert gene to synonyms mapping
		foreach ($LIST_GN as $GN)
		{
			$DBIDS['gn_syn_map']++;
			$NEW_DATA=true;
			fputs($fpFILES['GN_SYN_MAP'],$DBIDS['gn_syn_map']."\t".$SYN_ID."\t".$GN."\n");
		}
	}
	if (!$NEW_DATA)return;
	/// Once all are processed, we run psql to load the records in the database
	fclose($fpFILES['GN_SYN_MAP']);
	fclose($fpFILES['GN_SYN']);
	
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.gn_syn(gn_syn_id, syn_type,syn_value) FROM \''.'GN_SYN.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."A06",'Unable to insert tree'); 
	
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.gn_syn_map(gn_syn_map_id, gn_syn_Id,gn_entry_id) FROM \''.'GN_SYN_MAP.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."A07",'Unable to insert tree'); 
	
	$fpFILES=array(
		'GN_SYN_MAP'=>fopen('GN_SYN_MAP.csv','w'),
		'GN_SYN'=>fopen('GN_SYN.csv','w')
	);
	foreach ($fpFILES as $N=>$F)if ($F==null)										failProcess($JOB_ID."A08",'Unable to open '.$N);

	
		
}
//print_r($LIST_GENES);



?>
