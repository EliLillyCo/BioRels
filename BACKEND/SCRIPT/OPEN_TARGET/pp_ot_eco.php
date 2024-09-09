<?php

/**
 SCRIPT NAME: pp_ot_eco
 PURPOSE:     MONDO ontology does not contain all EFO entries that are used by Open Targets to describe the diseases.
			  Therefore, we are fetching all disease records from the different open targets files and see if they are in DISEASE_ENTRY table
			  If not, then we look in EFO the parent and check if it exists in DISEASE_ENTRY so we can create that missing branch
 
*/
error_reporting(E_ALL);


/// Job name - Do not change
$JOB_NAME='pp_ot_eco';


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



addLog("Initialisation");
	
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_ot')];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// List of all diseasesthat are reported in open targets
	$LIST_ENTRIES=array();

addLog("Processing indication.json");
	/// First we process indication.json
	$fp=fopen('indication.json','r');if (!$fp)														failProcess($JOB_ID."005",'Unable to open indication.json');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000000,"\n");
		if ($line=='')continue;
		$RAW_DATA=json_decode($line,true);
		
		foreach ($RAW_DATA['indications'] as $T)
		{
			/// Getting the list of diseases as key (for uniqueness) and counting the number of times it appears (for statistics)
			if (!isset($LIST_ENTRIES[$T['disease']]))$LIST_ENTRIES[$T['disease']]=1;
			else $LIST_ENTRIES[$T['disease']]++;
		}
	
	}
	fclose($fp);

addLog("Processing chembl.json");
	$fp=fopen('chembl.json','r');if (!$fp)															failProcess($JOB_ID."006",'Unable to open chembl.json');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000000,"\n");
		if ($line=='')continue;
		$RAW_DATA=json_decode($line,true);
		if (!isset($RAW_DATA['diseaseFromSourceMappedId']))continue;

		$DS_ID=$RAW_DATA['diseaseFromSourceMappedId'];

		/// Getting the list of diseases as key (for uniqueness) and counting the number of times it appears (for statistics)
		if (!isset($LIST_ENTRIES[$DS_ID]))$LIST_ENTRIES[$DS_ID]=1;
		else $LIST_ENTRIES[$DS_ID]++;
	
	}
	fclose($fp);

addLog("Saving the list of diseases");
	/// This is not a necessary step, it's more for bookkeeping, and a breakpoint
	$fpO=fopen('LIST_DISEASES','w');
	if (!$fpO)																							failProcess($JOB_ID."007",'Unable to open LIST_DISEASES for writing');
	foreach ($LIST_ENTRIES as $NAME=>$CO)
	{
		fputs($fpO,$NAME."\t".$CO."\n");
	}
	fclose($fpO);



addLog("Getting corresponding names:");
	/// Now we search for the names in the EFO Table
	$res=runQuery("SELECT * FROM efo_entry");
	if ($res===false)																					failProcess($JOB_ID."008",'Unable to query efo_entry');
	$NAMES=array();
	foreach ($res as $line)
	{
		$NAMES[$line['efo_tag_id']]=$line['efo_label'];
	}


	$fpO=fopen('EFO_TREE_REL.csv','w');if (!$fpO)														failProcess($JOB_ID."009",'Unable to query efo_entry');

	/// Then we look at each disease entry from Open target and 
	/// look them up in the disease_entry table (which comes from MONDO)
	foreach ($LIST_ENTRIES as $DISEASE_ID=>$CO)
	{
		if (!isset($NAMES[$DISEASE_ID]))continue;
		/// DISEASE_ID is usually in the form of DBNAME_DBID
		$tab=explode("_",$DISEASE_ID);



		$res=runQuery("SELECT * 
					FROM disease_entry EE, disease_extdb E, source S 
					WHERE EE.disease_entry_id = E.disease_entry_id 
					AND S.source_id = E.source_id 
					AND source_name='".$tab[0]."' 
					AND disease_extdb='".$tab[1]."'");
					if ($res===false)																	failProcess($JOB_ID."010",'Unable to query disease');
		if (count($res)!=0)continue;

		fputs($fpO,$DISEASE_ID."\t");
		/// Now we search for a match 
		$MATCH=findMatch($DISEASE_ID,$NAMES[$DISEASE_ID]);

		/// If we find a match, we write it in the file
		if ($MATCH!=null)
		{
			fputs($fpO, "DIRECT\t".$MATCH."\n");
			continue;
		}
		fputs($fpO, "PARENT\t");


		/// Otherwise we try to find a parent in the EFO table
		$res=runQuery("SELECT DISTINCT EE.*
			FROM efo_entry EE, efo_hierarchy EF, efo_hierarchy EPH, efo_entry EP
			WHERE EP.efo_entry_id = EPH.efo_entry_id 
			AND EF.efo_entry_id = EE.efo_entry_id
			AND EF.efo_level_left <EPH.efo_level_left 
			AND EF.efo_level_right > EPH.efo_level_right 
			AND EF.efo_level=EPH.efo_level-1 
			AND EP.efo_tag_id='".$DISEASE_ID."'");
		if ($res===false)																				failProcess($JOB_ID."011",'Unable to query efo hierarchy');
		
		fputs($fpO, count($res));
		
		foreach ($res as $line)
		{
			$T=findMatch($line['efo_tag_id'],$line['efo_label']);
			if ($T!=null)fputs($fpO, "\t".$T);
			else		 fputs($fpO, "\tNULL");
		}
		fputs($fpO, "\n");
	}



print_r($LIST_ENTRIES);

successProcess();

		



function findMatch($diseaseId,$diseaseLabel)
{
	/// The first way is to look at the EFO table to see if it reports an equivalent in MONDO
	$res=runQuery("SELECT * 
					FROM efo_entry EE, efo_extdb E, source S 
					WHERE EE.efo_entry_id = E.efo_entry_id 
					AND S.source_id = E.source_id 
					AND efo_tag_id='".$diseaseId."' 
					AND LOWER(source_name)=LOWER('MONDO')");
					if ($res===false)																		failProcess($JOB_ID."013",'Unable to query efo entry');
					/// We find it -> we stop there and return the result
	if (count($res)!=0)	return 'MONDO_'.$res[0]['efo_extdb_name'];
	
	/// Not found? We go by name and search for it in the disease table
	$res=runQuery("SELECT * 
				FROM disease_entry 
				WHERE LOWER(disease_name)=LOWER('".str_replace("'","''",$diseaseLabel)."')");
	if ($res===false)																						failProcess($JOB_ID."014",'Unable to query disease entry');
	if (count($res)!=0)	return $res[0]['disease_tag'];
		
	
	/// Still not found, let search in the synonyms of disease_entry
	$res=runQuery("SELECT * 
					FROM disease_entry DE,disease_syn DS 
					WHERE DS.disease_entry_id = DE.disease_entry_id 
					AND  LOWER(syn_value)=LOWER('".str_replace("'","''",$diseaseLabel)."')");
	if ($res===false)																						failProcess($JOB_ID."015",'Unable to query disease syn');
	if (count($res)!=0)	return $res[0]['disease_tag'];
		
	
	//else echo "NULL\tNULL\t";
	/// Still nothing? Let's go with the identifier in the disease_entry, 
	/// Some MONDO records are defined with a EFO tag
	$res=runQuery("SELECT * 
				FROM disease_entry 
				WHERE disease_tag='".$diseaseId."' ");
	if ($res===false)																						failProcess($JOB_ID."016",'Unable to query disease entry');
	if (count($res)!=0)
	{
		/// If there's a match, we want to ensure the disease name is relatively the same
		foreach ($res as $line)
		{
			
		$P=0;similar_text(strtolower($line['disease_name']),strtolower($diseaseLabel),$P);
		//echo "DE\t".$res[0]['disease_tag']."\t".$res[0]['disease_name']."\t".$P."\t";
		if ($P>80){return $line['disease_tag'];}
		}
	}



	/// Still nothing? Let's go with disease external identifiers and search for the tag
	$res=runQuery("SELECT * 
				FROM disease_entry DE, disease_extdb D,source S 
				WHERE DE.disease_entry_id = D.disease_entry_id 
				AND S.source_id = D.source_id 
				AND source_name='".explode("_",$diseaseId)[0]."' 
				AND disease_extdb='".explode("_",$diseaseId)[1]."'");
	if ($res===false)																						failProcess($JOB_ID."017",'Unable to query disease entry name');
	if (count($res)!=0)
	{
		foreach ($res as $line)
		{
			/// If there's a match, we want to ensure the disease name is relatively the same
			$P=0;similar_text(strtolower(str_replace(" (disease)","",$line['disease_name'])),strtolower($diseaseLabel),$P);
		
			$res2=runQuery("SELECT * FROM disease_syn WHERE disease_entry_id=".$line['disease_entry_id']);
			if ($res2===false)																				failProcess($JOB_ID."018",'Unable to query disease syn');
			$MAXV=0;
			foreach ($res2 as $l2)
			{
				similar_text(strtolower(str_replace(" (disease)","",$l2['syn_value'])),strtolower($diseaseLabel),$P);
				$MAXV=max($MAXV,$P);
			}
		//	echo $MAXV."\t";
			if ($MAXV>80){return $line['disease_tag'];}
		}
	
	}



	//Nothing? Last chance, looking at 3rd party identifiers to see if there could be a match.
	$res=runQuery("SELECT DD.disease_entry_id ,dd.disease_tag, dd.disease_name,count(*) CO 
					FROM efo_entry EE,efo_extdb EED, disease_extdb DE,disease_entry DD
					WHERE EE.efo_tag_id='".$diseaseId."'
					AND EED.efo_entry_Id = EE.efo_entry_id 
					AND de.source_id=EED.source_id 
					AND DE.disease_extdb=EFO_EXTDB_NAME
					AND DE.disease_entry_id = DD.disease_entry_id
					GROUP BY dd.disease_tag,DD.disease_name ,DD.disease_entry_id 
					ORDER BY CO DESC");
	if ($res===false)																						failProcess($JOB_ID."019",'Unable to query disease/efo');
	
	
	if (count($res)!=0)
	{
		$P=0;
		similar_text(strtolower(str_replace(" (disease)","",$res[0]['disease_name'])),strtolower($diseaseLabel),$P);
	
		if ($res[0]['co']>=5)
		{
			return $res[0]['disease_tag'];
		}
		
		//echo $MAXV."\n";
	}
	//else echo "MA\tNULL\tNULL\tNULL\tNULL\tNULL\n";
	return null;
}

		

?>
