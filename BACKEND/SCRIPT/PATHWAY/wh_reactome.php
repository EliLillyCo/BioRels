<?php

ini_set('memory_limit','1000M');

/**
 SCRIPT NAME: wh_reactome
 PURPOSE:     Process reactome and push to production
 
*/

/// Job name - Do not change
$JOB_NAME='wh_reactome';


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



addLog("Create directory");
	///	get Parent info:
	$CK_REAC_INFO=$GLB_TREE[getJobIDByName('ck_reactome_rel')];

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_REAC_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_REAC_INFO['TIME']['DEV_DIR'] ;	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	/// Update process control directory to the current release
	$PROCESS_CONTROL['DIR']=$CK_REAC_INFO['TIME']['DEV_DIR'];

	/// Check if the FTP_REACTOME path is set
	if (!isset($GLB_VAR['LINK']['FTP_REACTOME']))										failProcess($JOB_ID."006",'FTP_REACTOME path no set');
	

	
addLog("Working directory: ".$W_DIR);

	addLog("Listing selected Taxons based on config");
		/// We have the possibility to limit the number of organism to process.
		/// IN CONFIG_USER, you can provide a list of taxonomy ID corresponding to the organisms you want to consider
		/// We flip the array to have a list of taxon ID as key because it's much fasterto perform searches on keys rather than values
		$TAXON_LIMIT_LIST=array_flip(defineTaxonList());
	
	
	addLog(count($TAXON_LIMIT_LIST).' taxons defined');


addLog("Download Reactome pathways.txt");
	if (!dl_file($GLB_VAR['LINK']['FTP_REACTOME'].'/ReactomePathways.txt',3))			failProcess($JOB_ID."007",'Unable to download ReactomePathways');
	
addLog("Download ReactomePathwaysRelation.txt");
	if (!dl_file($GLB_VAR['LINK']['FTP_REACTOME'].'/ReactomePathwaysRelation.txt',3))	failProcess($JOB_ID."008",'Unable to download ReactomePathwaysRelation');

addLog("Download reactome_stable_ids.txt");
	if (!dl_file($GLB_VAR['LINK']['FTP_REACTOME'].'/NCBI2Reactome.txt',3))				failProcess($JOB_ID."009",'Unable to download NCBI2Reactome');


addLog("Preload data");
	$res=runQuery("SELECT pw_entry_id,reac_id,pw_name,tt.taxon_id, scientific_name,tax_id
				FROM  pw_entry pe,taxon tt 
				WHERE tt.taxon_id =pe.taxon_id
				ORDER BY reac_id ASC");
	if($res===false)																	failProcess($JOB_ID."010",'Unable to retrieve current pathway');
	$DATA=array();
	$STATS=array('PATHWAY'=>0,'PATHWAY_TO_GENE'=>0,'PATHWAY_REL'=>0,'UPD'=>0,'NEW'=>0,'DEL'=>0);
	$MAX_DBID=-1;
	$TAX=array();
	foreach  ($res as $tab)
	{
		$DATA[$tab['reac_id']]=$tab;
		/// We add this to know whether the entry is updated or to be deleted
		$DATA[$tab['reac_id']]['DB_STATUS']='FROM_DB';
		$TAX[$tab['scientific_name']]=array(
			$tab['taxon_id'],
			/// Boolean to know if the taxon is in the list of taxons wanted by the user or there's no list
			$TAXON_LIMIT_LIST==array()||($TAXON_LIMIT_LIST!=array() && isset($TAXON_LIMIT_LIST[$res[0]['tax_id']])) 
		);
		/// Get the max dbid so we can add new records
		if ($tab['pw_entry_id']>$MAX_DBID)$MAX_DBID=$tab['pw_entry_id'];
	}
	ksort($DATA);



addLog("Process ReactomePathways.txt");
	processReactomePathways();
	processReactomeRelation();
	

	updateDatabase();
	

	mapNCBI2Reactome();



	print_r($STATS);
addLog("Push to prod");
	pushToProd();

	updateStat('pw_entry','pathway',$STATS['PATHWAY'],$JOB_ID);
	updateStat('pw_hierarchy','pathway_hierarchy',$STATS['PATHWAY_REL'],$JOB_ID);
	
	successProcess();
	





	function processReactomePathways()
	{
		global $DATA;
		global $TAX;
		global $STATS;
		global $JOB_ID;
		global $MAX_DBID;
		$fp=fopen('ReactomePathways.txt','r');if (!$fp)										failProcess($JOB_ID."A01",'Unable to open ReactomePathways');

		while(!feof($fp))
		{
			/// Sometimes those files are generated from Windows, so we need to remove the carriage return
			$line=str_replace("\r", '', stream_get_line($fp,10000,"\n"));if ($line=='')continue;

			$tab=explode("\t",$line);
	
			/// Canis familiaris is actually Canis LUPUS familiaris, so to make the appropriate connection
			/// we need to manually change it.
			$tab[2]=trim($tab[2]);
			if ($tab[2]=="Canis familiaris")$tab[2]="Canis lupus familiaris";
			
			if (isset($DATA[$tab[0]]))
			{
	
				$ENTRY=&$DATA[$tab[0]];
				$STATS['PATHWAY']++;
				$ENTRY['DB_STATUS']='VALID';
				/// If the pathway name has changed, then we need to update the entry
				if ($ENTRY['pw_name']!=$tab[1])
				{
					$ENTRY['pw_name']=$tab[1];
					$ENTRY['DB_STATUS']='TO_UPD';
				}
				if ($ENTRY['scientific_name']!=$tab[2])	
				{
					failProcess($JOB_ID."A02",'Unexpected event - Pathway entries are not supposed to change to a different organism'."\n|".$ENTRY['pw_name'].'|::|'.$ENTRY['scientific_name'].'|-|'.$tab[2].'|');
				}
			}
			else
			{
				++$MAX_DBID;
			
				/// When it's a new taxon, we check if it's in the system (should be)
				if (!isset($TAX[$tab[2]])) 
				{
					
					$query="SELECT taxon_id ,tax_id 
							FROM taxon
							WHERE LOWER(scientific_name)=LOWER('".prepString($tab[2])."')";
					
					$res=runQuery($query);if ($res===false)									failProcess($JOB_ID."A03",'Unable to check taxon');
	
					if (count($res)==0)														failProcess($JOB_ID."A04",'Unrecognized organism');
	
	
					/// However, we add a boolean if that specific taxon is not in the list of taxons wanted by the user
					$TAX[$tab[2]]=array($res[0]['taxon_id'],($TAXON_LIMIT_LIST!=array() && isset($TAXON_LIMIT_LIST[$res[0]['tax_id']])||$TAXON_LIMIT_LIST==array()));
				}
				
				/// If it's not in that list of specific taxons wanted by the user, then we continue
				if (!$TAX[$tab[2]][1])continue;
				
				$STATS['PATHWAY']++;
				$DATA[$tab[0]]=array(
					'reac_id'=>$tab[0],
					'pw_name'=>$tab[1],
					'taxon_id'=>$TAX[$tab[2]][0],
					'pw_entry_id'=>$MAX_DBID,
					'DB_STATUS'=>'TO_INS');
					
			}
		}	 
		fclose($fp);
	}



/// Create nested set representation that is going to assign boundary numbers.
//// Let's say that the root has for boundary 1 10.
//// The two childs:  A 2-5 and B 6-9
/// And the A has a child C 3-4
/// If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
//// By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
//// Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for theleft side and <10 for the right side, leading to A B and C.

function defLevels($REAC_ID,$LEVEL,$VALUE)
{
	global $DATA;
	global $STATS;
	global $fp;
	global $INV_CLASS;
	global $MAX_DBID;
	++$LEVEL;$VALUE++;		
	$LEFT=$VALUE;
	// for ($I=0;$I<$LEVEL;++$I)echo "\t";
	// echo $LEVEL.':'.$REAC_ID."\n";
	$RECORD=&$DATA[$REAC_ID];
	//echo $LEVEL."\t".$TAX_ID."\t".count($tab)."\n";
	if (isset($RECORD['CHILD']))
	{
		sort($RECORD['CHILD']);
		$RECORD['CHILD']=array_unique($RECORD['CHILD']);
		foreach ($RECORD['CHILD'] as $CHILD)
		{
			//if ($ENTRY[$CHILD]['PW_ENTRY_ID']==11640)echo $REAC_ID."\t".$RECORD['PW_ENTRY_ID']."\n";
			$VALUE=defLevels($CHILD,$LEVEL,$VALUE);
		}
	}
	++$STATS['PATHWAY_REL'];
	++$VALUE;
	$RECORD['REL'][]=array($LEVEL,$LEFT,$VALUE);
	return $VALUE;
}

function processReactomeRelation()
{
	global $DATA;
	global $JOB_ID;


	/// Once we have the pathways, we want the relationships between those pathways:
	$fp=fopen('ReactomePathwaysRelation.txt','r');if (!$fp)								failProcess($JOB_ID."B01",'Unable to read ReactomePathwaysRelation');
	while(!feof($fp))
	{
		$line=str_replace("\r", '', stream_get_line($fp,10000,"\n"));if ($line=='')continue;
		$tab=explode("\t",$line);

		/// But we only consider them if they have been added previously (due to taxon filter)
		if (!isset($DATA[$tab[0]]))continue;
				
		$DATA[$tab[0]]['CHILD'][]=$tab[1];
		$DATA[$tab[1]]['HAS_PARENT']=true;
		
	}
	fclose($fp);


	/// Because there is no root pathway, we are looping through all records to see if anyone of them have no parent and making them as "roots"
	$CURSOR=0;
	foreach ($DATA as $REAC_ID=>&$ENTRY)
	{
		if (isset($ENTRY['HAS_PARENT']))continue;
		$LEVEL=0;
		
		$CURSOR=defLevels($REAC_ID,0,$CURSOR);
	}

}





function updateDatabase()
{
	/// Now that we have updated the DATA array with information from the file, we need to push the potential changes to the database

	global $DATA;
	global $STATS;
	global $JOB_ID;
	global $MAX_DBID;
	global $DB_INFO;
	global $GLB_VAR;

	$DBID=0;

	$fp=fopen('pw_entry.csv','w');if (!$fp)												failProcess($JOB_ID."C01",'Unable to  open pw_entry ');
	$fpH=fopen('pw_hierarchy.csv','w');if (!$fpH)										failProcess($JOB_ID."C02",'Unable to  open pw_hierarchy ');
	foreach ($DATA as $REAC_ID=>&$ENTRY)
	{
		
		if ($ENTRY['DB_STATUS']=='VALID')
		{/// Nothing to do in that case
			
		}
		else if ($ENTRY['DB_STATUS']=='FROM_DB')
		{
			$STATS['DEL']++;
			/// The entry is from the database but wasn't found in the file so we delete it.
			if (!runQueryNoRes("DELETE FROM pw_entry 
								WHERE pw_entry_id=".$ENTRY['pw_entry_id']))				failProcess($JOB_ID."C03",'Unable to Delete '.$REAC_ID);
			continue;
		}
		else if ($ENTRY['DB_STATUS']=='TO_UPD')
		{


			/// Updates
			$STATS['UPD']++;
			$query="UPDATE pw_entry SET
			reac_id='".prepString($ENTRY['reac_id'])."',
			pw_name='".prepString($ENTRY['pw_name'])."',
			taxon_id=".$ENTRY['taxon_id']." 
			WHERE pw_entry_id=".$ENTRY['pw_entry_id'];
			if (!runQueryNoRes($query))													failProcess($JOB_ID."C04",'Unable to update '.$query);
		}

		/// Saving the new entry in the file
		if ($ENTRY['DB_STATUS']=='TO_INS')
		{
			$STATS['NEW']++;
			fputs($fp,$ENTRY['pw_entry_id']."\t".
					'"'.$ENTRY['reac_id'].'"'."\t".
					'"'.str_replace('"','""',$ENTRY['pw_name']).'"'."\t".
					$ENTRY['taxon_id']."\n");
		}
		
		/// Saving all relationhsips in another file:
		foreach ($ENTRY['REL'] as &$R)
		{
			++$DBID;
			fputs($fpH,$ENTRY['pw_entry_id']."\t".$R[0]."\t".$R[1]."\t".$R[2]."\n");	
		}


	}
	
	fclose($fp);
	fclose($fpH);


	/// Push data to database
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pw_entry(pw_entry_id,reac_id,pw_name,taxon_id) FROM \''."pw_entry.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."C05",'Unable to insert pw_entry'); 

	/// Now we create the files:
	if (!runQueryNoRes("truncate table pw_hierarchy"))									failProcess($JOB_ID."C06",'Unable to clean Hierarchy ');
	

	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pw_hierarchy(pw_entry_id,pw_level,level_left,level_right) FROM \''."pw_hierarchy.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."C07",'Unable to insert pw_hierarchy'); 


}





function mapNCBI2Reactome()
{
	global $DATA;
	global $STATS;
	global $JOB_ID;
	
	global $GLB_VAR;
	global $DB_INFO;



	/// NEXT NCBI2Reactome provides a mapping between NCBI Gene and pathway
	/// So first we load those we already have in teh database
	$res=runQuery("SELECT pw_gn_map_id, p.gn_entry_id, p.pw_entry_Id, evidence_code, gene_id, reac_id 
					FROM pw_entry pe, pw_gn_map p, gn_entry ge
					WHERE  pe.pw_entry_id = p.pw_entry_id 
					and ge.gn_entry_id = p.gn_Entry_id");
	if ($res===false)																		failProcess($JOB_ID."D01",'Unable to retrieve genes');
	$GN_MAP=array();
	$MAX_DBID=0;

	foreach ($res as $line)
	{
		/// Getting max primary key value for pw_gn_map
		if ($line['pw_gn_map_id']>$MAX_DBID)$MAX_DBID=$line['pw_gn_map_id'];

		$line['DB_STATUS']='FROM_DB';

		$DATA[$line['reac_id']]['GN'][$line['gene_id']]=$line;
		$GN_MAP[$line['gene_id']]=$line['gn_entry_id'];
	}

	/// Then we read the file
	$fp=fopen('NCBI2Reactome.txt','r');if (!$fp)											failProcess($JOB_ID."D02",'Unable to read NCBI2Reactome.txt');
	$LIST_GENES=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		
		/// This is because of the taxon rule.
		/// We ignore all records not asssociated to our taxons of interest
		if (!isset($DATA[$tab[1]]))		continue;

		/// We ignore all records where the gene Identifier is not numeric
		if (!is_numeric($tab[0]))
		{
			echo "NOT NUMERIC ".$tab[0]."\n";
			continue;
		}
		$STATS['PATHWAY_TO_GENE']++;
		
		/// Checking for a given pathway and a given gene if the pair exist
		if (isset($DATA[$tab[1]]['GN'][$tab[0]]))
		{
			
			$E=&$DATA[$tab[1]]['GN'][$tab[0]];
			$E['DB_STATUS']='VALID';
			$E['NEW_EVID'][]=$tab[4];
			continue;
		}

		
		/// If not we create the record.
		$E=&$DATA[$tab[1]];
		/// But we don't know the database id for the gene in each new record
		/// So we put that in an array to do a lookup
		$LIST_GENES[$tab[0]]=-1;
		
		$E['GN'][$tab[0]]=array(
			'NEW_EVID'		=>array($tab[4]),
			'pw_gn_map_id'	=>$MAX_DBID,
			'DB_STATUS'		=>'TO_INS');
	}
	fclose($fp);

	/// Lookup the genes:
	$CHUNKS=array_chunk(array_keys($LIST_GENES),20000);
	foreach ($CHUNKS as $CHUNK)
	{
		$res=runQuery("SELECT gn_entry_id,gene_id 
				FROM gn_entry 
				WHERE gene_id IN (".implode(',',$CHUNK).')');
		if ($res===false)																failProcess($JOB_ID."D03",'Unable to find gene');
		foreach ($res as $line)$LIST_GENES[$line['gene_id']]=$line['gn_entry_id'];
	}
	
	
	$TO_DEL=array();

	/// Then we save into a file the new connections:
	$fp=fopen('pw_gn_map.csv','w');
	if (!$fp)																		failProcess($JOB_ID."D04",'Unable to open pw_gn_map.csv ');
	foreach ($DATA as $PW_N=>&$ENTRY)
	{
		if (!isset($ENTRY['GN']))continue;
		foreach ($ENTRY['GN'] as $GENE_ID=>&$REC)
		{
			/// Failed to find the new gene in GN_ENTRY
			if ((!isset($LIST_GENES[$GENE_ID]) || $LIST_GENES[$GENE_ID]==-1) && !isset($REC['gn_entry_id']))
			{
				$res=runQuery("SELECT gene_id, alt_gene_id,gn_entry_id FROM gn_history WHERE gene_id=".$GENE_ID);
				if ($res===false)													failProcess($JOB_ID."D05",'Unable to find gene');
				if ($res!=array() && $res[0]['gn_entry_id']!='')
				{
					$GENE_ID=$res[0]['alt_gene_id'];
					if (isset($ENTRY['GN'][$GENE_ID]))continue;
					$LIST_GENES[$GENE_ID]=$res[0]['gn_entry_id'];
					print_r($res);
				}
				else 
				{
					//	echo "MISSING ".$GENE_ID."\n";
					$STATS['MISSING_GENE']++;
					continue;
				}
			}
			
			$ST='';
			if (isset($REC['NEW_EVID']))
			{
				sort($REC['NEW_EVID']);
				$ST=implode("|",$REC['NEW_EVID']);
			}
			
			if (isset($REC['EVIDENCE_CODE']) && $ST!=$REC['EVIDENCE_CODE'])
			{
				$query="UPDATE pw_gn_map 
						SET evidence_id='".$tab[4]."' 
						WHERE pw_gn_map_id=".$REC['pw_gn_map_id'];
				if(!runQueryNoRes($query))										failProcess($JOB_ID."D06",'Unable to run query: '.$query);
			}
			
			
			if ($REC['DB_STATUS']=='FROM_DB')
			{
				$TO_DEL[]=$REC['pw_gn_map_id'];
				
			}
			else if ($REC['DB_STATUS']=='TO_INS')
			{
				++$MAX_DBID;
				fputs($fp,$MAX_DBID."\t".$LIST_GENES[$GENE_ID]."\t".$ENTRY['pw_entry_id']."\t".$ST."\n");
			}
		}
	}
	
	fclose($fp);
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pw_gn_map (pw_gn_map_id,gn_entry_id,pw_entry_id,evidence_code)  FROM \''."pw_gn_map.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )														failProcess($JOB_ID."D07",'Unable to insert pw_gn_map'); 

	$STATS['DEL']=count($TO_DEL);

	if ($TO_DEL==array())return;
	
	$CHUNKS=array_chunk($TO_DEL,20000);
	foreach ($CHUNKS as $CHUNK)
	{	
		$res=runQueryNoRes("DELETE FROM pw_gn_map 
							WHERE pw_gn_map_id IN (".implode(',',$CHUNK).')');
		if($res===false)													failProcess($JOB_ID."D08",'Unable to run query: '.$query);
	}

}
?>