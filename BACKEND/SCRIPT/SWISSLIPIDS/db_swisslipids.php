<?php
error_reporting(E_ALL);
ini_set('memory_limit','3000M');

/**
 SCRIPT NAME: db_swisslipids
 PURPOSE:     Process SwissLipids files 
 
*/

/// Job name - Do not change
$JOB_NAME='db_swisslipids';

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
 

addLog("Setting directory");
	/// Get Parent job info:
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_swisslipids_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
	/// Create the STD directory
	if (!is_dir('STD') && !mkdir('STD'))															failProcess($JOB_ID."005",'Unable to create STD');


	$SOURCE_ID=getSource('SwissLipids');

	
	addLog("Update source");
	/// We want to ensure that all names in the database coming from SwissLipids are valid
	/// So we update all of them so their sm_name_status is F.
	/// When we process them, we will update that sm_name_status to T
	/// All of those records with sm_name_status = F are those that were not found in the file
	/// and will be deleted
	if (runQueryNoRes("UPDATE sm_source 
			set sm_name_Status='F'
			 where sm_source_id=".$SOURCE_ID)===false)												failProcess($JOB_ID.'006','Unable to update sm_source');
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("UPDATE ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source 
				SET sm_name_status='F' 
				WHERE source_id = ".$SOURCE_ID)) 													failProcess($JOB_ID."007",'Unable to update   source');
	}

addLog("Process molecules");
	// So we are going to read the lipids.tsv file and extract all smiles/inchi, inchi key and the counterions
	// and write it in STD/lipids_ini.smi
	$fp=fopen('lipids.tsv','r');if (!$fp)															failProcess($JOB_ID."008",'Unable to open lipids.tsv');
	$HEAD=array_flip(explode("\t",stream_get_line($fp,10000,"\n")));
	
	
	/// Ensure that all the columns are present
	if (!isset($HEAD['Lipid ID']))																	failProcess($JOB_ID."009",'Unable to find Lipid ID column ');
	if (!isset($HEAD['SMILES (pH7.3)']))															failProcess($JOB_ID."010",'Unable to find SMILES (pH7.3) column ');
	if (!isset($HEAD['InChI (pH7.3)']))																failProcess($JOB_ID."011",'Unable to find InChI (pH7.3) column ');
	if (!isset($HEAD['InChI key (pH7.3)']))															failProcess($JOB_ID."012",'Unable to find InChI key (pH7.3) column ');
	
	
	
	$fpO=fopen('STD/molecule.smi','w');if (!$fp)													failProcess($JOB_ID."013",'Unable to open molecule.smi');
	$fpC=fopen('STD/counterion.smi','w');if (!$fpO)													failProcess($JOB_ID."014",'Unable to open STD/counterion.smi');
	$COUNTERION_MAP=array();
	
	
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;/// Expecting a non emtpy string
		$tab=explode("\t",$line);if (count($tab)!=count($HEAD))continue; /// Expecting the same number of columns as the header
		
		$tabS=explode(".",$tab[$HEAD['SMILES (pH7.3)']]);
		/// We find the longuest molecule to be the primary molecule
		$MAX_LEN=0;
		foreach ($tabS as $t)$MAX_LEN=max($MAX_LEN,strlen($t));
		$ALT=array();$SMI='';
		foreach ($tabS as $t)
		{
			if (strlen($t)==$MAX_LEN)$SMI=$t;
			else $ALT[]=$t;
		}
		/// Then for the counterions, we sort them:
		sort($ALT);
		/// If there's no counterions, we put NULL
		if ($ALT==array())$ALT[]='NULL';
		
		/// Save it in the file
		fputs($fpO,$tab[$HEAD['SMILES (pH7.3)']].' '.
			$tab[$HEAD['Lipid ID']]."|".
			$tab[$HEAD['InChI (pH7.3)']]."|".
			$tab[$HEAD['InChI key (pH7.3)']]."|".
			implode(".",$ALT)."|".
			$SMI."\n");
		
		/// Then we concatenate the counterions to create a unique string:
		$STR_C=implode(".",$ALT);
		if ($STR_C!='NULL')
		{
			/// We add it to the map as key, so that we can get the unique counterions
			$COUNTERION_MAP[$STR_C.' '.$STR_C]='';
		}
	}
	/// We write the counterions in the file
	fputs($fpC,implode("\n",array_keys($COUNTERION_MAP)))."\n";
	unset($COUNTERION_MAP);
	fclose($fp);
	fclose($fpO);
	fclose($fpC);

// 	exit;
	$CURR_RELEASE=getCurrentReleaseDate('SWISSLIPIDS',$JOB_ID);

	
	
	addLog("Standardize SMILES");
	standardizeCompounds();
	


	createLipidTree();


addLog("Process information");
	$W_PRIVATE=($GLB_VAR['PRIVATE_ENABLED']=='T');
	$DBIDS=array();
	$FILES=array();
	$STATS=array();
	processSwissLipids($GLB_VAR['PUBLIC_SCHEMA']);
	$DBIDS=array();
	$FILES=array();
	$STATS=array();
	
	if ($W_PRIVATE)processSwissLipids($GLB_VAR['SCHEMA_PRIVATE']);
	
	addLog("Cleanup source");
	if (!runQueryNoRes("DELETE FROM sm_source 
		WHERE sm_name_Status='F' 
		AND source_Id = ".$SOURCE_ID))								failProcess($JOB_ID.'015','Unable to delete sm_source');
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("DELETE FROM ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source 
					WHERE sm_name_status='F' 
					AND source_id = ".$SOURCE_ID)) 						failProcess($JOB_ID."016",'Unable to update sm_source');
	}




successProcess();






function processSwissLipids($SCHEMA)
{
	global $SOURCE_ID;
	global $FILES;
	global $DBIDS;global $COUNTERIONS;
	global $STATS;
	global $DB_INFO;
	global $GLB_VAR;

 	addLog("Processing SwissLipid for ".$SCHEMA);
 	

	/// Now that we have inserted the molecules, we can add the related informations,
	/// so we are going to read the lipids.tsv file against, but this time we are going to focus and publications
	

	addLog("Add names");
	$fp=fopen('lipids.tsv','r');if (!$fp)												failProcess($JOB_ID."A01",'Unable to open lipids.tsv');
	$HEAD=array_flip(explode("\t",stream_get_line($fp,10000,"\n")));
	
	
	if (!isset($HEAD['Lipid ID']))														failProcess($JOB_ID."A02",'Unable to find Lipid ID column ');
	if (!isset($HEAD['SMILES (pH7.3)']))												failProcess($JOB_ID."A03",'Unable to find SMILES (pH7.3) column ');
	$N_BLOCK=0;
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);if (count($tab)!=count($HEAD))continue;
		//print_r($tab);
		$BLOCK[$tab[0]]=$tab;
		if (count($BLOCK)<10000)continue;
		++$N_BLOCK;
		processNames($BLOCK,$SCHEMA,$HEAD);
		$BLOCK=array();
	}
	fclose($fp);
	
		
		


	 addLog("Add Tree");
	 /// Next, SwissLipids provides a hierarchy for the lipids
	 /// So we are going to extract that information.
	 /// Only virtual compounds are part of that hierarhcy
	 /// all non-virtual compounds are mapped to one or multiple virtual compounds
	$fp=fopen('lipids.tsv','r');if (!$fp)												failProcess($JOB_ID."A04",'Unable to open sm_source.csv');
	$HEAD=array_flip(explode("\t",stream_get_line($fp,10000,"\n")));
	
	if (!isset($HEAD['Lipid ID']))														failProcess($JOB_ID."A05",'Unable to find Lipid ID column ');
	if (!isset($HEAD['SMILES (pH7.3)']))												failProcess($JOB_ID."A06",'Unable to find SMILES (pH7.3) column ');
	$TREE=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);if (count($tab)!=count($HEAD))continue;
		/// Get SMILES
		$SMI=&$tab[$HEAD['SMILES (pH7.3)']];
		///Virtual compound if in the SMILES there's an R Group
		$IS_NODE=((strpos($SMI,'*')!==false)||$SMI=='');
		/// We associate the parent
		if ($tab[$HEAD['Parent']]!='')
		{
			$T2=explode("|",str_replace(" ","",$tab[$HEAD['Parent']]));
			foreach ($T2 as $T)$TREE[$T]['CHILD'][$IS_NODE][$tab[$HEAD['Lipid ID']]]=true;
		}
		/// And the class
		if ($tab[$HEAD['Lipid class*']]!='')
		{
			$T2=explode("|",str_replace(" ","",$tab[$HEAD['Lipid class*']]));
			foreach ($T2 as $T)$TREE[$T]['CHILD'][$IS_NODE][$tab[$HEAD['Lipid ID']]]=true;
		}
		/// If it's a node, we add supplementary informations
		if (!$IS_NODE)continue;
		
		if (!isset($TREE[$tab[$HEAD['Lipid ID']]]))$TREE[$tab[$HEAD['Lipid ID']]]=array();
		$ENTRY=&$TREE[$tab[$HEAD['Lipid ID']]];
		$ENTRY['NAME']=$tab[2];
		$ENTRY['CLASS']=$tab[1];
		$ENTRY['SMI']=$SMI;
		$ENTRY['STATUS']='TO_INS';
		$ENTRY['HAS_PARENT']=($tab[$HEAD['Parent']]!='')||($tab[$HEAD['Lipid class*']]!='');
	
	
	}
	fclose($fp);
	/// Now we detect the roots of that hierarchy, i.e. those without any parents
	$ROOTS=array();
	foreach ($TREE as $T=>&$R)
	{
		//if(!isset($R['SMI']) && isset($R['CHILD'])){echo $T."\n";}
		if (isset($R['HAS_PARENT'])&& !$R['HAS_PARENT'])$ROOTS[$T]=true;
	}

	echo count($TREE);

	/// Output files for each table we are going to insert into

	$fpO=fopen('INSERT/lipid_sm_map.csv','w');if (!$fp)											failProcess($JOB_ID."A07",'Unable to open lipid_sm_map.csv');

	/// We assign DB ID for existing entries:
	$res=runQuery("SELECT lipid_entry_id, lipid_tag FROM ".$GLB_VAR['PUBLIC_SCHEMA'].".lipid_entry");
	if ($res===false)																			failProcess($JOB_ID."A08",'Unable to get lipid entries');
	foreach ($res as $line)$TREE[$line['lipid_tag']]['DBID']=$line['lipid_entry_id'];

	/// Getting max id for each table
	$DBIDS=array('lipid_sm_map'=>-1);
	foreach (array_keys($DBIDS) as $P)
	{
		$res=runQuery("SELECT MAX(".$P."_ID) co FROM ".$SCHEMA.".".$P);
		if ($res===false)																	failProcess($JOB_ID."A09",'Unable to get max id from '.$P);
		if (count($res)==0)$DBIDS[$P]=0;else $DBIDS[$P]=$res[0]['co'];
		
	}

	$HAS_NEW=false; /// Boolean stating whether we have new entries to insert
	$CHUNKS=array_chunk(array_keys($TREE),30000);
	foreach ($CHUNKS as $CHUNK)
	{
		if ($CHUNK==array())continue;
		/// Create the query for this chunk:
		$query="SELECT * FROM ".$GLB_VAR['PUBLIC_SCHEMA'].".lipid_entry WHERE lipid_tag IN (";
		
		foreach ($CHUNK as $CH)
		{
			$query.="'".$CH."',";
		}
		
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false)																	failProcess($JOB_ID."A10",'Unable to get lipid entries from tag');
		
		/// So we can list the existing entries:
		$EXISTING_ENTRY=array();
		foreach ($res as $line)$EXISTING_ENTRY[$line['lipid_entry_id']]=$line;
		
		
		/// Then we look for the child molecules: of the current chunk
		$LIST_CHILDS=array();
		foreach ($CHUNK as $TAG)
		{
			$ENTRY=&$TREE[$TAG];
			if(!isset($ENTRY['STATUS']))continue;
			if (!isset($ENTRY['CHILD'])||!isset($ENTRY['CHILD'][0]))continue;
			foreach ($ENTRY['CHILD'][0] as $N=>$PV)$LIST_CHILDS["'".$N."'"]=-1;
		}

		$CHUNKS2=array_chunk(array_keys($LIST_CHILDS),30000);
		
		foreach ($CHUNKS2 as &$CHUNK2)
		{
			$query='SELECT sm_name,sm_entry_id 
					FROM '.$SCHEMA.'.sm_source 
					WHERE sm_name IN ('.implode(",",$CHUNK2).')';
			$res=runQuery($query);
			if ($res===false)																	failProcess($JOB_ID."A11",'Unable to get sm entries from name');
			foreach ($res as $l)
			{
				$LIST_CHILDS["'".$l['sm_name']."'"]=$l['sm_entry_id'];
			}
		}

		$MAP=array();
		/// And if somehow there are already existing entries, we get the map id
		if ($EXISTING_ENTRY!=array())
		{
			$res=runQuery("SELECT lipid_sm_map_id,
			lipid_entry_id,
			sm_entry_id FROM ".$SCHEMA.".lipid_sm_map 
			WHERE lipid_entry_id IN (".implode(",",array_keys($EXISTING_ENTRY)).')');
			if ($res===false)																		failProcess($JOB_ID."A12",'Unable to get lipid sm map');
			
			foreach ($res as $line)
			{
				$MAP[$line['sm_entry_id']][$line['lipid_entry_id']]=$line['lipid_sm_map_id'];
			}
		}
		
		/// Now we can compare between the file for this chunk and the database
		foreach ($CHUNK as $TAG)
		{
			$ENTRY=&$TREE[$TAG];
			if(!isset($ENTRY['STATUS']))continue;
			if (!isset($ENTRY['CHILD'][0]))continue;

			foreach ($ENTRY['CHILD'][0] as $TAG_CH=>$PV)
			{
				if ($LIST_CHILDS["'".$TAG_CH."'"]==-1)continue;
				/// Getting the DB ID of the small molecule:
				$sm_entry_id=$LIST_CHILDS["'".$TAG_CH."'"];

				/// Getting the DB ID of the lipid:
				$LP_ENTRY_ID=$ENTRY['DBID'];

				/// Checking the map between the small molecule and the lipid 
				if (isset($MAP[$sm_entry_id][$LP_ENTRY_ID])){continue;}
				else
				{
					/// If it's not in the map, we add it
					++$DBIDS['lipid_sm_map'];
					$HAS_NEW=true;
					$MAP[$sm_entry_id][$LP_ENTRY_ID]=$DBIDS['lipid_sm_map'];
					fputs($fpO,$DBIDS['lipid_sm_map']."\t".$LP_ENTRY_ID."\t".$sm_entry_id."\n");
					
				}
			}
		}
	}
	fclose($fpO);
	if (!$HAS_NEW) return;
	$command='\COPY '.$SCHEMA.'.lipid_sm_map(lipid_sm_map_id,lipid_entry_Id,sm_entry_id)  FROM \''.'INSERT/lipid_sm_map.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )																				failProcess($JOB_ID."A13",'Unable to insert lipid_sm_map'); 
}






function createLipidTree()
{
	global $FILES;
	global $GLB_VAR;
	global $DB_INFO;
	global $DBIDS;

	addLog("Add Tree");
	/// Next, SwissLipids provides a hierarchy for the lipids
	/// So we are going to extract that information.
	/// Only virtual compounds are part of that hierarhcy
	/// all non-virtual compounds are mapped to one or multiple virtual compounds
	$fp=fopen('lipids.tsv','r');if (!$fp)											failProcess($JOB_ID."B01",'Unable to open sm_source.csv');
	$HEAD=array_flip(explode("\t",stream_get_line($fp,10000,"\n")));
	
	if (!isset($HEAD['Lipid ID']))													failProcess($JOB_ID."B02",'Unable to find Lipid ID column ');
	if (!isset($HEAD['SMILES (pH7.3)']))												failProcess($JOB_ID."B03",'Unable to find SMILES (pH7.3) column ');
	$TREE=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);if (count($tab)!=count($HEAD))continue;
		/// Get SMILES
		$SMI=&$tab[$HEAD['SMILES (pH7.3)']];
		///Virtual compound if in the SMILES there's an R Group
		$IS_NODE=((strpos($SMI,'*')!==false)||$SMI=='');
		/// We associate the parent
		if ($tab[$HEAD['Parent']]!='')
		{
			$T2=explode("|",str_replace(" ","",$tab[$HEAD['Parent']]));
			foreach ($T2 as $T)$TREE[$T]['CHILD'][$IS_NODE][$tab[$HEAD['Lipid ID']]]=true;
		}
		/// And the class
		if ($tab[$HEAD['Lipid class*']]!='')
		{
			$T2=explode("|",str_replace(" ","",$tab[$HEAD['Lipid class*']]));
			foreach ($T2 as $T)$TREE[$T]['CHILD'][$IS_NODE][$tab[$HEAD['Lipid ID']]]=true;
		}
		/// If it's a node, we add supplementary informations
		if (!$IS_NODE)continue;
	
		if (!isset($TREE[$tab[$HEAD['Lipid ID']]]))$TREE[$tab[$HEAD['Lipid ID']]]=array();
		$ENTRY=&$TREE[$tab[$HEAD['Lipid ID']]];
		$ENTRY['NAME']=$tab[2];
		$ENTRY['CLASS']=$tab[1];
		$ENTRY['SMI']=$SMI;
		$ENTRY['STATUS']='TO_INS';
		$ENTRY['HAS_PARENT']=($tab[$HEAD['Parent']]!='')||($tab[$HEAD['Lipid class*']]!='');
	
		
	}
	fclose($fp);
	/// Now we detect the roots of that hierarchy, i.e. those without any parents
	$ROOTS=array();
	foreach ($TREE as $T=>&$R)
	{
		//if(!isset($R['SMI']) && isset($R['CHILD'])){echo $T."\n";}
		if (isset($R['HAS_PARENT'])&& !$R['HAS_PARENT'])$ROOTS[$T]=true;
	}

	echo count($TREE);

	/// Output files for each table we are going to insert into
	$fp=fopen('INSERT/lipid_entry.csv','w');if (!$fp)											failProcess($JOB_ID."B04",'Unable to open lipid_entry.csv');

	addLog("Get lipid entry");
	$res=runQuery("SELECT lipid_entry_id, lipid_tag FROM ".$GLB_VAR['PUBLIC_SCHEMA'].".lipid_entry");
	if ($res===false)																			failProcess($JOB_ID."B05",'Unable to get lipid entries');
	foreach ($res as $line)
	{
		$TREE[$line['lipid_tag']]['DBID']=$line['lipid_entry_id'];
	}

	addLog("Get DB Ids");
   	$DBIDS=array('lipid_entry'=>-1,'lipid_sm_map'=>-1);
   	foreach (array_keys($DBIDS) as $P)
   	{
	   $res=runQuery("SELECT MAX(".$P."_ID) co FROM ".$GLB_VAR['PUBLIC_SCHEMA'].".".$P);
	   if ($res===false)																	failProcess($JOB_ID."B06",'Unable to get max id from '.$P);
	   if (count($res)==0)$DBIDS[$P]=0;
	   else $DBIDS[$P]=$res[0]['co'];
	   
   	}

   $CHUNKS=array_chunk(array_keys($TREE),30000);
   foreach ($CHUNKS as $CHUNK)
   {
	   if ($CHUNK==array())continue;
	   $query="SELECT * FROM lipid_entry WHERE lipid_tag IN (";
	   foreach ($CHUNK as $CH)$query.="'".$CH."',";
	   $res=runQuery(substr($query,0,-1).')');
	   if ($res===false)																	failProcess($JOB_ID."B07",'Unable to get lipid entries from tag');
	   $EXISTING_ENTRY=array();
	   foreach ($res as $line)
	   {
		   $ENTRY=&$TREE[$line['lipid_tag']];
		   /// We found it in the database so we set the status to valid
		   $ENTRY['STATUS']='VALID';
		   $ENTRY['DBID']=$line['lipid_entry_id'];
		   $EXISTING_ENTRY[$line['lipid_entry_id']]=$line['lipid_tag'];

		   /// We compare the entry in the database with the entry in the file
		   if ($ENTRY['CLASS']	!=$line['lipid_class_type'])$ENTRY['STATUS']='TO_UPD';
		   if ($ENTRY['SMI']	!=$line['lipid_smiles'])	$ENTRY['STATUS']='TO_UPD';
		   if ($ENTRY['NAME']	!=$line['lipid_name'])		$ENTRY['STATUS']='TO_UPD';
		   /// Anything to update? run the query:
		   if ($ENTRY['STATUS']=='TO_UPD')
		   {
			   if (!runQueryNoRes("UPDATE ".$GLB_VAR['PUBLIC_SCHEMA'].".lipid_entry 
			   			SET lipid_class_type='".str_replace("'","''",$ENTRY['CLASS'])."', 
							lipid_name='".str_replace("'","''",$ENTRY['NAME'])."', 
							lipid_smiles='".$ENTRY['SMI']."'
						WHERE lipid_entry_id=".$line['lipid_entry_id']))					failProcess($JOB_ID."B08",'Unable to update entry '.$line['lipid_tag']);;
		   }
	   }

	   foreach ($CHUNK as $TAG)
	   {
		   $ENTRY=&$TREE[$TAG];
		   if(!isset($ENTRY['STATUS']) ||$ENTRY['STATUS']!='TO_INS')continue;
		   ++$DBIDS['lipid_entry'];
		   $ENTRY['DBID']=$DBIDS['lipid_entry'];
		   fputs($fp,$DBIDS['lipid_entry']."\t".$TAG."\t".$ENTRY['CLASS']."\t".$ENTRY['NAME']."\t".$ENTRY['SMI']."\n");
		   
	   }
   }
   fclose($fp);
   
   $command='\COPY '.$GLB_VAR['PUBLIC_SCHEMA'].'.lipid_entry(lipid_entry_id, lipid_tag, lipid_class_type, lipid_name, lipid_smiles)FROM \''.'INSERT/lipid_entry.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
   echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
   system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
   if ($return_code !=0 )																				failProcess($JOB_ID."B09",'Unable to insert lipid_entry'); 
   																		


   
   //print_r($ROOTS);
   $LEVEL=0;
   if (!runQueryNoRes("TRUNCATE TABLE ".$GLB_VAR['PUBLIC_SCHEMA'].".lipid_hierarchy"))							failProcess($JOB_ID."B10",'Unable to truncate lipid hierarchy');
   $fp=fopen('INSERT/TREE.csv','w');if (!$fp)														failProcess($JOB_ID."B11",'Unable to open TREE.csv'); 
   genTree($TREE,$ROOTS,0,$LEVEL,$fp);
   fclose($fp);
   $command='\COPY '.$GLB_VAR['PUBLIC_SCHEMA'].'.lipid_hierarchy(lipid_entry_id,lipid_level,lipid_level_left,lipid_level_right)  FROM \''.'INSERT/TREE.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
   echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
   system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
   if ($return_code !=0 )																				failProcess($JOB_ID."B12",'Unable to insert lipid hierarchy'); 

}


function processNames($BLOCK,$SCHEMA,$HEAD)
{
	addLog($SCHEMA . "Process Names");
	global $N_BLOCK;
	
	global $FILES;
	global $GLB_VAR;
	global $DB_INFO;
	global $DBIDS;
	global $SOURCE_ID;

	/// Output files for each table we are going to insert into
	$FILES=array(
		'sm_source'=>fopen('INSERT/sm_source.csv','w'),
	'sm_publi_map'=>fopen('INSERT/sm_publi_map.csv','w'));
	foreach ($FILES as $F)if (!$F)																		failProcess($JOB_ID."C01",'Unable to open file');
	
	addLog($SCHEMA . "Process Names - Get DB ID");
	$DBIDS=array();
	foreach (array_keys($FILES) as $P)
	{
		$res=runQuery("SELECT MAX(".$P."_id) CO FROM ".$SCHEMA.".".$P);
		if ($res===false)																		failProcess($JOB_ID."C02",'Unable to get max id from ',$P);
		if (count($res)==0)$DBIDS[$P]=0;
		else $DBIDS[$P]=$res[0]['co'];

	}

	addLog($SCHEMA . "Process Names - PRocess Block");
	$NEW_SOURCE=false;
	$NEW_PUBLI=false;
	$IDS=array();
	$ID_PUBLI=array();
	/// Analyzing the block to get the different names and pmid. 
	/// Note that PMID goes into a different array ID_PUBLI
	foreach ($BLOCK as $BLK)
	{
		
		$tab=explode("|",$BLK[$HEAD['Name']]);			
		foreach ($tab as $T)$IDS["'".$BLK[$HEAD['Lipid ID']]."'"][utf8_encode(trim($T))]=false;
		$tab=explode("|",$BLK[$HEAD['Abbreviation*']]);
			foreach ($tab as $T)$IDS["'".$BLK[$HEAD['Lipid ID']]."'"][utf8_encode(trim($T))]=false;
		$tab=explode("|",$BLK[$HEAD['Synonyms*']]);		foreach ($tab as $T)$IDS["'".$BLK[$HEAD['Lipid ID']]."'"][utf8_encode(trim($T))]=false;
		$tab=explode("|",$BLK[$HEAD['CHEBI']]);			foreach ($tab as $T)$IDS["'".$BLK[$HEAD['Lipid ID']]."'"][utf8_encode(trim($T))]=false;
		$tab=explode("|",$BLK[$HEAD['LIPID MAPS']]);	foreach ($tab as $T)$IDS["'".$BLK[$HEAD['Lipid ID']]."'"][utf8_encode(trim($T))]=false;
		$tab=explode("|",$BLK[$HEAD['PMID']]);			foreach ($tab as $T)$ID_PUBLI["'".$BLK[$HEAD['Lipid ID']]."'"][utf8_encode(trim($T))]=false;
	}
	
	$DBIDS_I=array();
	addLog($SCHEMA . "Process Names - Search by names ");
	/// Find all names associated to that Lipid ID that have already been inserted by SwissLipids
	$res=runQuery("SELECT SS.sm_entry_id,SS.sm_name,SS2.sm_name as name2 
		FROM ".$SCHEMA.".sm_source SS, ".$SCHEMA.".sm_source SS2
		WHERE SS.sm_name IN (".implode(",",array_keys($IDS)).') 
		AND SS.sm_entry_id = SS2.sm_entry_id  AND ss2.source_id='.$SOURCE_ID);
		if ($res===false)															failProcess($JOB_ID."C03",'Unable to get names');
	$LT=array();
	foreach ($res as $line)
	{
		$LT[$line['sm_entry_id']][$line['sm_name']]=true;
		$LT[$line['sm_entry_id']][$line['name2']]=true;
		$DBIDS_I["'".$line['sm_name']."'"]=$line['sm_entry_id'];
		$IDS["'".$line['sm_name']."'"][$line['name2']]=true;
	}
	/// Looking which one are missing and push them in the file
	foreach ($IDS as $INI_NAME=>$LIST)
	foreach ($LIST as $ALT_NAME=>$ST)
	{
		if ($ST)continue;
		if (!isset($DBIDS_I[$INI_NAME]))continue;
		if ($ALT_NAME=='')continue;
		if (isset($LT[$DBIDS_I[$INI_NAME]][$ALT_NAME]))continue;

		/// If it's not in the database, we add it
		$DBIDS['sm_source']++;
		$LT[$DBIDS_I[$INI_NAME]][$ALT_NAME]=true;
		$NEW_SOURCE=true;
		fputs($FILES['sm_source'],
			$DBIDS['sm_source']."\t".
			$DBIDS_I[$INI_NAME]."\t".
			$SOURCE_ID."\t".
			$ALT_NAME."\tT\n");
	}




	addLog($SCHEMA . "Process Names - Search publications");
	/// Looking at all publication associations (from Swiss Lipids) to those LIPID IDs 
	$res=runQuery("SELECT SS.sm_name, SP.*,PE.pmid_entry_id,pmid  
		FROM ".$SCHEMA.".sm_source SS, ".$SCHEMA.".sm_publi_map SP, pmid_entry PE
		WHERE PE.pmid_entry_id = SP.pmid_entry_id 
		AND SP.sm_entry_id = SS.sm_entry_id 
		AND ss.sm_name  IN (".implode(",",array_keys($IDS)).') 
		AND sp.source_id= '.$SOURCE_ID);
	if ($res===false)															failProcess($JOB_ID."C04",'Unable to get names');
	$DBPUB=array();$PMIDs=array();
	foreach ($res as $line)
	{
		$DBPUB["'".$line['sm_name']."'"]=$line['sm_entry_id'];
		$ID_PUBLI["'".$line['sm_name']."'"][$line['pmid']]=true;
		$PMIDs[$line['pmid']]=$line['pmid_entry_id'];
	}

	$MISSING_PUBS=array();
	/// Compare against our list, if not in db, the pubmed id goes in MISSING_PUB
	foreach ($ID_PUBLI as $INI_NAME=>$LIST)
	foreach ($LIST as $ALT_NAME=>$ST)
	{
		if ($ST)continue;
		if (!isset($DBIDS_I[$INI_NAME]) && !isset($DBPUB[$INI_NAME]))continue;
		if ($ALT_NAME=='')continue;
		$MISSING_PUBS[$ALT_NAME]=-1;
	}


	/// Search for pmid_entry_id for missing pubmed id
	$CHUNKS=array_chunk(array_keys($MISSING_PUBS),10000);
	foreach ($CHUNKS as $CHUNK)
	{
		$res=runQuery("SELECT pmid_entry_id,pmid 
					FROM pmid_entry 
					WHERE pmid IN (".implode(",",$CHUNK).')');
		if ($res===false)															failProcess($JOB_ID."C05",'Unable to get pmid');
		foreach ($res as $line)
		{
			$PMIDs[$line['pmid']]=$line['pmid_entry_id'];
		}
	}

	/// Push the missing ones we have a pmid_entry_id for, in the file so we can insert it
	$DONE=array();
	foreach ($ID_PUBLI as $INI_NAME=>$LIST)
	foreach ($LIST as $ALT_NAME=>$ST)
	{
		if ($ST)continue;
		if (!isset($DBIDS_I[$INI_NAME]) && !isset($DBPUB[$INI_NAME]))continue;
		if ($ALT_NAME=='')continue;
		
		$DBIDS['sm_publi_map']++;
		$ID=isset($DBIDS_I[$INI_NAME])?$DBIDS_I[$INI_NAME]:$DBPUB[$INI_NAME];
		if (!isset($PMIDs[$ALT_NAME]) ||$PMIDs[$ALT_NAME]==-1)continue;
		if (isset($DONE[$ID][$ALT_NAME]))continue;
		$DONE[$ID][$ALT_NAME]=true;
		$NEW_PUBLI=true;
		fputs($FILES['sm_publi_map'],
			$DBIDS['sm_publi_map']."\t".
			$ID."\t".
			$PMIDs[$ALT_NAME]."\t".
			$SOURCE_ID."\n");
	}
	/// New source? Then run psql to batch load
	if ($NEW_SOURCE){
		fclose($FILES['sm_source']);	
		$command='\COPY '.$SCHEMA.'.sm_source( sm_source_id , sm_entry_id,source_id,sm_name, sm_name_status )FROM \''.'INSERT/sm_source.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )												failProcess($JOB_ID."C06",'Unable to insert sm_source'); 
		
		$FILES['sm_source']=fopen('INSERT/sm_source.csv','w');
		if (!$FILES['sm_source'])											failProcess($JOB_ID."C07",'Unable to open sm_source.csv'); 
	}
	/// New publication association? Then run psql to batch load
	if ($NEW_PUBLI)
	{
		fclose($FILES['sm_publi_map']);
		$command='\COPY '.$SCHEMA.'.sm_publi_map(sm_publi_map_id , sm_entry_id , pmid_entry_id , source_id)FROM \''.'INSERT/sm_publi_map.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )											failProcess($JOB_ID."C08",'Unable to insert sm_pubmi_map'); 
		
		$FILES['sm_publi_map']=fopen('INSERT/sm_publi_map.csv','w');
		if (!$FILES['sm_publi_map'])									failProcess($JOB_ID."C09",'Unable to open sm_publi_map');
	}
		//print_r($BLOCK);
		// $NAMES=array($BLOCK[1],$BLOCK[2],$BLOCK[3]);
		// print_r($NAMES);
}



/// Generate the tree
/// We are going to generate the tree in a file, so that we can batch load it
/// We are going to use a recursive function to do so that creates a nested set model
/// The nested set model is a way to represent a tree in a relational database
/// The level is the depth of the node in the tree
/// The left and right values are the position of the node in the tree
/// Any child is between its left and right values
/// Any parent is outside of its left and right values
function genTree(&$DATA,$ROOTS,$LEVEL,&$LEVEL_V,&$fp)
{
	global $N_LEVEL;
	
	//global $fp;
	++$LEVEL;
	foreach ($ROOTS as $RID=>$T)
	{
		//print_r($DATA[$RID]);
		if (!isset($DATA[$RID])){echo $RID."\n";continue;}

		if ($LEVEL!=1)$LEVEL_V+=pow(10,10-$LEVEL);
		$LEVEL_LEFT=$LEVEL_V;
		if (isset($DATA[$RID]['CHILD'][1]))
		{	
			genTree($DATA,$DATA[$RID]['CHILD'][1],$LEVEL,$LEVEL_V,$fp);
			/// Here we use a variant of it. Instead of adding 1, we add by a power of 10
			$LEVEL_V+=pow(10,10-$LEVEL);
		}
		else $LEVEL_V+=100;
		//for($I=0;$I<$LEVEL;++$I)echo "\t";

		$LEVEL_RIGHT=$LEVEL_V;
		fputs($fp,$DATA[$RID]['DBID']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n");
		
		
	}
}

	

?>