<?php
ini_set('memory_limit','4000M');
/**
 SCRIPT NAME: wh_taxonomy
 PURPOSE:     Download and process organism data
 In this case, we don't refresh the taxonomy table but rather recreate it.
 This process follows a few steps:
	-> Download data
	-> Process data to generate flat files to push in database.
	-> Create a new TAXON_NEW table in the database and fill it.
	-> Move current TAXON table to TAXONOMY_PREV table
	-> Move new TAXON_NEW to current TAXON
*/

$JOB_NAME='wh_taxonomy';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);

/// File/parameters verifications:
addLog("Static file check");
	if (!isset($GLB_VAR['LINK']['FTP_NCBI']))																failProcess($JOB_ID."001",'FTP_NCBI path no set');

addLog("Create directory");
	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];						if (!is_dir($W_DIR)) 					failProcess($JOB_ID."002",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();			   								if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create new process dir '.$W_DIR);
						   											if (!chdir($W_DIR)) 					failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();
	


	
try{

	

	$DB_CONN->beginTransaction();
	
	/// Because some taxons can be merged, we need to be able to redirect records from other tables accordingly
	/// Using foreign key constraints, we are going to find all tables having a column referencing taxon_id
addLog("Find dependent table");
	$DEP_TABLES=getDepTables('taxon',$GLB_VAR['DB_SCHEMA']);
	
	
	/// If we haven't already downloaded the files (for instance, if the previous run crashed for some reason)
	if (!is_file('names.dmp') && !is_file('nodes.dmp')){
addLog("Download Taxonomy file");
	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/pub/taxonomy/taxdump.tar.gz',3))							failProcess($JOB_ID."006",'Unable to download archive');



addLog("Untar archive");
	if (!untar('taxdump.tar.gz'))																			failProcess($JOB_ID."007",'Unable to extract archive');


addLog("Remove all unnecessary files");
	$FILES_TO_DEL=
	array('citations.dmp',	'delnodes.dmp',	'division.dmp',	'gc.prt',
	      'gencode.dmp',     'readme.txt',   'taxdump.tar.gz');

	foreach ($FILES_TO_DEL as $FILE) 
		if (!unlink($FILE))																					failProcess($JOB_ID."008",'Unable to remove '.$FILE); 


addLog("File check");
	if (!validateLineCount('names.dmp',2800000))															failProcess($JOB_ID."009",'names.dmp is smaller than expected. '.getLineCount('names.dmp').'/2800000'); 
	if (!validateLineCount('nodes.dmp',2000000))															failProcess($JOB_ID."010",'nodes.dmp is smaller than expected. '.getLineCount('nodes.dmp').'/2000000'); 
	}///END IF FILE


addLog("Load taxons from database");
	
	$res=runQuery("SELECT tax_id, taxon_id, scientific_name,rank FROM taxon");
	if ($res===false)																						failProcess($JOB_ID."011",'Unable to query the database'); 
	
	$DATA=array();$MAX_DBID=-1;/// $DATA contains all the current taxons
	/// To space memory space, we use a hash for the ranks:
	$CLASS=array();	//Array([no rank] => 1 ; [superkingdom] => 2;	[genus] => 3; ;[species] => 4....
	$N_CLASS=0;
	$INV_CLASS=array();//Array ([1] => no rank ;[2] => superkingdom ; [3] => genus ; [4] => species....
	
	foreach ($res as $line)
	{
		///Check if rank exists or not and associate a number
		$RANK=$line['rank'];
		if (!isset($CLASS[$RANK]))
		{
			++$N_CLASS;
			$CLASS[$RANK]=$N_CLASS;
			$INV_CLASS[$N_CLASS]=$RANK;
		}

		/// Key is the NCBI taxonomy ID
		/// 4 columns: taxon_Id (database id), scientific name, status: FROM_DB, TO_INSERT, TO_UPD, and rank id
		$DATA[$line['tax_id']]=array($line['taxon_id'],$line['scientific_name'],'FROM_DB',$CLASS[$RANK],'');
		///We need to control the database identifiers for new records so we can speed up the creation of the tree,
		/// so we try to find the max database taxon_id
		$MAX_DBID=max($MAX_DBID,$line['taxon_id']);
	}

addLog("Load taxons");
	
	/// names.dmp contains the name and synonyms of all taxon entries
	/// We are just goind to keep the scientific name
	$fp=fopen('names.dmp','r');if (!$fp)																	failProcess($JOB_ID."012",'Unable to open names.dmp'); 
	$N_INSERT=0;
	$EXPECTED_TAXONS=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");	if ($line=='')continue;
		$tab =explode("|",$line);				if (count($tab)!=5)continue;
		$type=trim($tab[3]);					if ($type!='scientific name')continue;
		$taxid=trim($tab[0]);
		$name=trim($tab[1]);
		++$EXPECTED_TAXONS;
		/// We check if the record already exist by looking up if the NCBI Taxonomy ID is existing in the $DATA array
		if (isset($DATA[$taxid]))
		{
			$ENTRY=&$DATA[$taxid];
			$ENTRY[2]='VALID';/// TSince we found it, the status is set by default VALID, unless something changed
			/// So we check if anything has changed
			/// In this case, only the name can change
			/// If so, the status is set to TO_UPD
			if ($ENTRY[1]==$name)continue;
			
			echo $name."\t".$ENTRY[1]."\n";
			$ENTRY[1]=$name;
			$ENTRY[2]='TO_UPD';
		}
		else 
		{
			/// Otherwise we create the record:
			$MAX_DBID++;
			/// N_INSERT will tell us if we need to do bulk insert later on
			++$N_INSERT;
			$DATA[$taxid]=array($MAX_DBID,$name,'TO_INSERT','','');
		}
	}
	fclose($fp);
	


addLog("Load merged entries");
	$MERGED=loadMerged();



addLog("Load tree");
	/// nodes.dmp contains the relationships between the nodes and the rank of each taxon
	$fp=fopen('nodes.dmp','r');if (!$fp)																	failProcess($JOB_ID."014",'Unable to open nodes.dmp'); 
	$N=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("|",$line);			  if (count($tab)!=14)continue;
		$TAX_ID   =trim($tab[0]);
		$PARENT_ID=trim($tab[1]);
		$RANK     =trim($tab[2]);

		if (!isset($CLASS[$RANK]))
		{
			++$N_CLASS;
			$CLASS[$RANK]=$N_CLASS;
			$INV_CLASS[$N_CLASS]=$RANK;
		}
		/// Technically, the record should already exist, either to be created (TO_INSERT), VALID, TO_UPD, or FROM_DB
		if (!isset($DATA[$TAX_ID]))																			failProcess($JOB_ID."015",$TAX_ID.' not found in dataset - Unexpected behavior'); 
		$ENTRY=&$DATA[$TAX_ID];
		/// if it's a new record, we just assign the rank
		if ($ENTRY[2]=='TO_INSERT')$ENTRY[3]=$CLASS[$RANK];
		/// Otherwise we want to make sure that the rank is the same as in the database
		else if ($ENTRY[2]=='VALID'||$ENTRY[2]=='TO_UPD')
		{
			/// And if not, we will need to update the entry
			if ($ENTRY[3]!=$CLASS[$RANK])
			{
				$ENTRY[3]=$CLASS[$RANK];
				$ENTRY[2]=='TO_UPD';
			}
		}
		
		/// Here we save the childs taxon into the parent. A string is less memory expensive to use
		if ($TAX_ID!=$PARENT_ID && $TAX_ID!='')$DATA[$PARENT_ID][4].=$TAX_ID.'|';
	}
	fclose($fp);

	

addLog("Update/Insert");
$TO_DEL=array();

/// Now we look at the data snapshot, which should be a mix between the data in the database and the file
foreach ($DATA as $tax_id=>$info)
{

	/// If anything was updated we run the query
	if ($info[2]=='TO_UPD')
	{
		$res=runQueryNoRes("UPDATE taxon 
			SET scientific_name='".str_replace("'","''",$info[1])."', 
				rank='".str_replace("'","''",$INV_CLASS[$info[3]])."' 
			WHERE taxon_id=".$info[0]);
		if ($res===false)																	failProcess($JOB_ID."016",'Unable to update tax ID '.$tax_id);
	}
	/// If it's a new record and there's a handful of those, we just to individual insertion
	/// Otherwise it will go in the bulk insert below
	else if ($info[2]=='TO_INSERT' && $N_INSERT<100)
	{
		echo "NEW TAXON ".$tax_id."\n";
		$query='INSERT INTO taxon (taxon_id, tax_Id, scientific_name,rank) VALUES ('.$info[0]
		.",'".$tax_id."','".str_replace("'","''",$info[1])."','".str_replace("'","''",$INV_CLASS[$info[3]])."')";
		$res=runQueryNoRes($query);
		if ($res===false)																	failProcess($JOB_ID."017",'Unable to insert tax ID '.$tax_id);
	}
	/// The record from the database (initially set to FROM_DB), was not found in the file (which would have changed the status to VALID or TO_UPD), so we will delete it
	else if ($info[2]=='FROM_DB')
	{
		$TO_DEL[$tax_id]=$info[0];
	}
}
	
addLog("Bulk Insert");
if ($N_INSERT >100)
{
	/// Opens a file , stores all new records in it, and insert the data
	$fp=fopen('insert.csv','w');if (!$fp)													failProcess($JOB_ID."018",'Unable to update tax ID '.$tax_id);
	fputs($fp,"taxon_id\ttax_Id\tscientific_name\trank\n");
	
	foreach ($DATA as $tax_id=>$info)
	{
	
		if ($info[2]!='TO_INSERT')continue;
		echo "NEW TAXON ".$tax_id."\n";
		fputs($fp,$info[0]."|".$tax_id."|".$info[1]."|".$INV_CLASS[$info[3]]."\n");
	}
	fclose($fp);
	
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.taxon(taxon_id,tax_id,scientific_name,rank)FROM \'insert.csv\''."  (DELIMITER E'|', QUOTE '~', null \\\"NULL\\\" ,format CSV, HEADER )";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code!=0)																	failProcess($JOB_ID."019",'Unable to insert taxons');

}


addLog("Updating former taxonomy entries");

	$DB_CONN->commit();
			
	if ($TO_DEL!=array()) deleteTaxons($TO_DEL);
	



}catch (PDOException $e)
{
	$DB_CONN->rollBack();
	throw $e;
}


addLog("Create hierarchy");
	createHierarchy();

updateStat('taxon','taxonomy',$EXPECTED_TAXONS,$JOB_ID);

$list_files=array('insert.csv','tree.csv');
foreach ($list_files as $F)
{
	if (!checkFileExist($F))continue;
	if (!unlink($F))	failProcess($JOB_ID."020",'Unable to delete '.$F);
}
 

addLog("Push to prod");
updateReleaseDate($JOB_ID,'TAXONOMY',getCurrDate());


pushToProd();


successProcess();








/// Create nested set representation that is going to assign boundary numbers.
//// Let's say that the root has for boundary 1 10.
//// The two childs:  A 2-5 and B 6-9
/// And the A has a child C 3-4
/// If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
//// By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
//// Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for theleft side and <10 for the right side, leading to A B and C.
function defLevels(&$ENTRY,$TAX_ID,$LEVEL,$VALUE,&$fp)
{
	
	
	global $INV_CLASS;
	global $MAX_DBID;
	++$LEVEL;$VALUE++;
	/// Left boundary		
	$LEFT=$VALUE;
	
	$tab=array_filter(explode("|",$ENTRY[$TAX_ID][4]));
	//echo $LEVEL."\t".$TAX_ID."\t".count($tab)."\n";
	
	foreach ($tab as $CHILD)	$VALUE=defLevels($ENTRY,$CHILD,$LEVEL,$VALUE,$fp);
	///Right boundary
	++$VALUE;
	$RIGHT=$VALUE;

	fputs($fp,$ENTRY[$TAX_ID][0]."\t".$LEVEL."\t".$LEFT."\t".$RIGHT."\n");
	return $VALUE;
}




function deleteTaxons($TO_DEL)
{
	global $GLB_VAR;
	global $MERGED;
	global $DEP_TABLES;
	global $JOB_ID;
	global $DATA;

	$PREV=count($TO_DEL);

	/// Here we check if any entries that exists in the database but not in the files have been merged
	foreach ($TO_DEL as $TAX_ID=> $DBID)
	{
		
		addLog("DELETION OF TAXON ".$TAX_ID.' DBID: '.$DBID);

		/// Those functions can take a while. So we need to call them manually before deleting the taxon itself
		if (!runQueryNoRes('DELETE FROM prot_seq_al 
			WHERE  prot_seq_ref_id  IN (
				SELECT prot_seq_Id 
				FROM taxon t, prot_entry pe, prot_seq ps 
				where t.taxon_id = pe.taxon_id 
				AND pe.prot_entry_id =ps.prot_entry_id 
				and t.taxon_id='.$DBID.')'))				failProcess($JOB_ID."A01",'Unable to delete prot seq ref al for  taxon dbid:'.$DBID); 
		if (!runQueryNoRes('DELETE FROM prot_seq_al 
			WHERE  prot_seq_comp_id  IN (
					SELECT prot_seq_Id 
					FROM taxon t, prot_entry pe, prot_seq ps 
					where t.taxon_id = pe.taxon_id 
					AND pe.prot_entry_id =ps.prot_entry_id 
					and t.taxon_id='.$DBID.')'))			failProcess($JOB_ID."A02",'Unable to delete prot seq comp al for  taxon dbid:'.$DBID); 
		if (!runQueryNoRes('DELETE FROM prot_dom_al 
			where prot_dom_ref_id IN (
					SELECT prot_dom_id 
					FROM taxon t, prot_entry pe, prot_dom pd 
					where t.taxon_id = pe.taxon_id 
					AND pe.prot_entry_id =pd.prot_entry_Id 
					and t.taxon_id='.$DBID.')'))			failProcess($JOB_ID."A03",'Unable to delete prot dom ref al for  taxon dbid:'.$DBID); 
		if (!runQueryNoRes('DELETE FROM prot_dom_al 
			where prot_dom_comp_id IN (
					SELECT prot_dom_id 
					FROM taxon t, prot_entry pe, prot_dom pd 
					where t.taxon_id = pe.taxon_id 
					AND pe.prot_entry_id =pd.prot_entry_Id 
					and t.taxon_id='.$DBID.')'))			failProcess($JOB_ID."A04",'Unable to delete prot dom comp al for  taxon dbid:'.$DBID); 
		if (isset($MERGED[$TAX_ID]))
		{
			/// If so, then we first need to update the dependent table records to go from the previous taxon to the next one
			addLog("MERGED ENTRY:\t".$TAX_ID."\t".$DBID."\t".$MERGED[$TAX_ID]."\t".$DATA[$MERGED[$TAX_ID]][0]);
			
			foreach ($DEP_TABLES as $TBL=>$CNAME)
			{
				$query='UPDATE '.$TBL.' SET taxon_id = '.$DATA[$MERGED[$TAX_ID]][0].' WHERE taxon_id = '.$DBID."\n";
				
				/// When gene  is updated before taxonomy, a specific situation can happen.
				if(!runQueryNoRes($query) )
				{
					if ($TBL!=$GLB_VAR['DB_SCHEMA'].'.chromosome')failProcess($JOB_ID."A05",'Unable to update the database '.$query); 
				}
				//echo $query;
			}
		}
		if (!runQueryNoRes("DELETE FROM taxon WHERE taxon_id =".$DBID))	failProcess($JOB_ID."A06",'Unable to delete tax id '.$TAX_ID); 
		
	}
	/// And then we delete the records
			
	
}


function createHierarchy()
{
	global $DATA;
	global $JOB_ID;
	global $DB_INFO;
	global $GLB_VAR;
	/// Now we create the hierarchy
	$fp=fopen('tree.csv','w');if (!$fp)failProcess($JOB_ID."B01",'Unable to open tree.csv'); 
	defLevels($DATA,1,-1,-1,$fp);
	fclose($fp);

		

	addLog("delete content of taxon tree");

	if (!runQueryNoRes("TRUNCATE TABLE taxon_tree"))failProcess($JOB_ID."B02",'Unable to truncate taxon_tree'); 


	addLog("load tree");

	$FCAV_NAME='tree.csv';
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.taxon_tree(taxon_id,tax_level,level_left,level_right)FROM \''.$FCAV_NAME."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV, HEADER )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."B03",'Unable to insert tree'); 


}


function loadMerged()
{
	$MERGED=array();
	/// merged.dmp contains the list of merged records
	$fp=fopen('merged.dmp','r');if (!$fp)																failProcess($JOB_ID."C01",'Unable to open merged.dmp'); 
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;
		$tab=explode("|",$line);
		if (count($tab)!=3)continue;
		$FORMER=trim($tab[0]);
		$NEW=trim($tab[1]);
		$MERGED[$FORMER]=$NEW;
	}
	fclose($fp);
	return $MERGED;
}
?>

