<?php
error_reporting(E_ALL);
ini_set('memory_limit','5000M');

/**
 SCRIPT NAME: db_drugbank
 PURPOSE:     Process all drubank files
 
*/

/// Job name - Do not change
$JOB_NAME='db_drugbank';

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

 

addLog("Define directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_drugbank')];
	
	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];	if (!is_dir($W_DIR)) 								failProcess($JOB_ID.'001','NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID.'002','Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; 		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID.'003','Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID.'004','Unable to access process dir '.$W_DIR);



addLog("Working directory: ". $W_DIR);
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Creation of the INSERT directory
	if (!is_dir('INSERT') && !mkdir('INSERT')) 														failProcess($JOB_ID.'005','Unable to create INSERT directory');
	
addLog("Preparation step");

	// We will record how many changes we have done to each table
	$STATS=array('drug_ext_db:MATCH'=>0,'drug_ext_db:NEW'=>0,'drug_ext_db:DEL'=>0,
				'drug_name:MATCH'=>0,'drug_name:NEW'=>0,'drug_name:DEL'=>0,
				'drug_desc:MATCH'=>0,'drug_desc:NEW'=>0,'drug_desc:DEL'=>0,
				'drug_type:MATCH'=>0,'drug_type:NEW'=>0,'drug_type:DEL'=>0,
				'drug_mol_entity_map:MATCH'=>0,'drug_mol_entity_map:NEW'=>0,'drug_mol_entity_map:DEL'=>0,'drug_entry:NEW'=>0,
				'drug_atc_map:MATCH'=>0,'drug_atc_map:NEW'=>0,'drug_atc_map:DEL'=>0);

	// This will contain all the records from the database that we didn't find in the same, so we can delete them
	$TO_DEL=array(
		'drug_entry'=>array(),
		'drug_name'=>array(),
		'drug_target'=>array(),
		'drug_extdb'=>array(),
		'drug_sm_map'=>array(),
		'drug_description'=>array(),
		'drug_type_map'=>array(),
		'drug_atc_map'=>array());			

	// Max primary key values for each table
	$DBIDS=array
		('drug_entry'=>-1,
		'drug_name'=>-1,
		'drug_extdb'=>-1,
		'drug_mol_entity_map'=>-1,
		'drug_description'=>-1,
		'drug_type_map'=>-1,
		'drug_atc_map'=>-1,
		'clinical_trial_intervention_drug_map'=>-1);

	// This will contain the order of the columns for the COPY command
	$COL_ORDER=array(
		'drug_entry'=>'(drug_entry_id, is_approved, is_withdrawn, max_clin_phase, drug_primary_name, is_experimental, is_investigational, is_nutraceutical, is_illicit, is_vet_approved,drugbank_id,chembl_id)',
		'drug_name'=>'(drug_name_id,drug_entry_id,drug_name,is_primary,is_tradename,source_id)',
		'drug_extdb'=>'(drug_extdb_id,drug_entry_id,drug_extdb_value,source_id,source_origin_id)',
		'drug_description'=>'(drug_description_id,drug_entry_id,text_description,text_type,source_id)',
		'drug_mol_entity_map'=>'(drug_mol_entity_map_id,drug_entry_id,molecular_entity_id,is_preferred,source_id)',
		'drug_type_map'=>'(drug_type_map_id,drug_entry_id,drug_type_id)',
		'drug_atc_map'=>'(drug_atc_map_id,drug_entry_id,atc_entry_id)',
		'clinical_trial_intervention_drug_map'=>'(clinical_trial_intervention_drug_map_id,clinical_trial_intervention_id,drug_entry_id,source_id)'
		);


	/// So first, we are going to get the max Primary key values for each of those tables for faster insert.
	/// FILE_STATUS will tell us for each file if we need to trigger the data insertion or  not
	$FILE_STATUS=array();
	/// FILES will be the file handlers for each of the files we are going to insert into
	$FILES=array();
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) CO FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."006",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
		$FILE_STATUS[$TBL]=0;
		$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');if (!$FILES[$TBL])				failProcess($JOB_ID."007",'Unable to open file '.$TBL.'.csv');
	}


addLog("Update source");
	$SOURCE_ID=getSource("DrugBank");

addLog("Process additional files");

//$ARTICLES=array();	 loadArticles($ARTICLES);

	/// Those first 3 functions will load contextual data that we will use to process the drugbank data
	$CLIN_INFO=array();  loadClinicalInfo($CLIN_INFO);
	$BIOTECH_MAP=array();loadBiotechCats($BIOTECH_MAP);
	$ATC_CODES=array();	 loadATCCode($ATC_CODES);
	


addLog("Load data");
	

	$FROM_FILES=array();	// This will contain all the records from the files
	$FROM_DB=array();		// This will contain all the records from the DB
	$SM_SOURCE=array();		// This will contain all the molecules from the DB
	
	
	loadFromDB();			// We load all the records from the DB
	loadDrugBank();			// We load all the records from the files
	compareRecords();		// We compare the records from the files to the records from the DB
	pushToDB();				// We push the records to the DB
	processClinicalTrials();	// We process the clinical trials
	pushToDB(true);
	

	print_R($STATS);		// We print the stats

	successProcess();		// We are done









function loadBiotechCats(&$BIOTECH_MAP)
{

	// We are going to load the biotech categories
	/// Which corresponds to drug types
	$fp=fopen('biotech_categories.csv','r');if (!$fp)								failProcess($JOB_ID."A01",'Unable to open biotech_categories file');
	// We are going to store the header in an array so we can access the columns by name
	$HEAD=array_flip(fgetcsv($fp));

	// We are going to store the biotech categories in an array
	$BIOTECH_CATS=array();
	while(!feof($fp))
	{
	
		$tab=fgetcsv($fp);if ($tab===false)continue;
		//Creating the entry
		$ENTRY=array();
		foreach ($HEAD as $K=>$V)$ENTRY[$K]=$tab[$V];
		// We are going to remove the trailing | from the group name
		if (substr($ENTRY['group_name|'],strlen($ENTRY['group_name|'])-1)=='|')$ENTRY['group_name|']=substr($ENTRY['group_name|'],0,-1);
		
		$BIOTECH_CATS[$ENTRY['name']]=$ENTRY;
	}
	fclose($fp);

	// Small molecule is not part of Biotech categories, but we need it to be in the database
	$BIOTECH_CATS['Small molecule']=array('name'=>'Small molecule','group_name|'=>'Small molecule');

	
	// We are going to get the current biotech categories in the database
	$res=runQuery("SELECT * FROM drug_type"); if ($res===false)										failProcess($JOB_ID."A02",'Unable to run query '.$query);
	$DRUG_TYPES=array();
	// MAX_ID will be used to generate new IDs for new entries
	$MAX_ID=0;
	foreach ($res as $line)
	{
		// We are going to keep track of the status of the entry
		$line['DB_STATUS']='FROM_DB';
		$DRUG_TYPES[$line['drug_type_name']]=$line;
		//  keep track of the max primary key value
		$MAX_ID=max($MAX_ID,$line['drug_type_id']);
	}
	
	// We are going to compare the database entries with the biotech categories
	foreach ($BIOTECH_CATS as $NAME=>&$ENTRY)
	{
		// If the entry is not in the database, we create a new entry
		if (!isset($DRUG_TYPES[$NAME]))
		{
			$MAX_ID++;
			addLog("BIOTECH_CATS\tNEW\t".$NAME."\t".$ENTRY['group_name|'])."\n";
			if (!runQueryNoRes("INSERT INTO drug_type (drug_type_id,drug_type_name,drug_type_Group) 
				VALUES (".$MAX_ID.",
				'".str_replace("'","''",$NAME)."',
				'".str_replace("'","''",$ENTRY['group_name|'])."')"))								failProcess($JOB_ID."A03",'Unable to insert new drug type '.$NAME);
		}
		else
		{
			// We are going to store the database ID in the entry so we can create the mapping
			$BIOTECH_CATS[$NAME]['DB_ID']=$DRUG_TYPES[$NAME]['drug_type_id'];

				// If the entry is in the database, we update the status
			$DRUG_TYPES[$NAME]['DB_STATUS']='VALID';

			// If the group name has changed, we update the database
			if ($DRUG_TYPES[$NAME]['drug_type_group']!=$ENTRY['group_name|'])
			{
				echo ("BIOTECH_CATS\tALT_GROUP_NAME\tCURRENT:".$DRUG_TYPES[$NAME]['drug_type_group']."\tNEW:".$ENTRY['group_name|'])."\n";
				$query="UPDATE drug_type 
				set drug_type_Group= '".str_replace("'","''",$ENTRY['group_name|'])."' 
				where drug_type_id = '".$DRUG_TYPES[$NAME]['drug_type_id']."'";
				
				if (!runQueryNoRes($query))															failProcess($JOB_ID."A04",'Unable to update drug type '.$NAME);
			}
		}
	}
	
	// We are going to remove all the entries that are not in the database anymore
	foreach ($DRUG_TYPES as $NAME=>&$ENTRY)
	{
		
		if ($ENTRY['DB_STATUS']=='VALID')continue;
		echo ("BIOTECH_CATS\tDELETE\t".$NAME."\t".$ENTRY['drug_type_group'])."\n";
		if (!runQueryNoRes("DELETE FROM drug_type where drug_type_id = '".$ENTRY['drug_type_id']."'"))failProcess($JOB_ID."A05",'Unable to delete drug type '.$NAME);
	}

	// We are going to create the mapping between the biotech categories and the database IDs
	foreach ($BIOTECH_CATS as $NAME=>&$ENTRY)
	{
		if (isset($ENTRY['id']))	$BIOTECH_MAP[$ENTRY['id']]=$ENTRY['DB_ID'];
		else $BIOTECH_MAP[$NAME]=$ENTRY['DB_ID'];
	}
	

}	
	
	
function loadATCCode(&$ATC_CODES)
{
	addLog("Process ATC Codes");
	
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	$ATC_CODES=array();
	
	/// The ATC file contains 2 ATC nomencaltures, the human and the veterinary
	addLog("\tLoading ATC Codes from file");
	$fp=fopen('atc.csv','r');
	if (!$fp) 																						failProcess($JOB_ID."B01",'Unable to open atc.csv');	
	$HEAD=array_flip(fgetcsv($fp));

	/// To create the nested tree, we are going to store the entries in an array
	/// The roots will be here predefined, one for each nomenclature
	$ROOTS=array('ATC_ROOT'=>true,'ATC_V_ROOT'=>true);
	/// All entries will be stored in $ATC_LIST, including the roots
	$ATC_LIST=array(
		'ATC_ROOT'=>array(
			'id'	=>'ATC_ROOT',
			'code'	=>'ATC_ROOT',
			'title'	=>'ATC_ROOT',
			'name'	=>'ATC Root',
			'parent_code|'=>'',
			'level'	=>0,
			'vocabulary'=>'ATC'),
		'ATC_V_ROOT'=>array(
			'id'=>'ATC_V_ROOT',
			'code'=>'ATC_V_ROOT',
			'title'=>'ATC_V_ROOT',
			'name'=>'ATC Veterinary Root',
			'parent_code|'=>'',
			'level'=>0,
			'vocabulary'=>'ATCVetNode'));


	/// Then we can read the file
	while(!feof($fp))
	{
		$tab=fgetcsv($fp);
		if ($tab===false)continue;
		
		$ENTRY=array();
		foreach ($HEAD as $K=>$V)$ENTRY[$K]=str_replace("\t","",$tab[$V]);
			
		// We are going to remove the trailing | from the parent code
		if (substr($ENTRY['parent_code|'],strlen($ENTRY['parent_code|'])-1)=='|')$ENTRY['parent_code|']=substr($ENTRY['parent_code|'],0,-1);
		
		// Level 1 records are the roots of the tree
		if ($ENTRY['level']==1)
		{
			if ($ENTRY['vocabulary']=='ATC') $ENTRY['parent_code|']='ATC_ROOT';
			else $ENTRY['parent_code|']='ATC_V_ROOT';
		}
		$ENTRY['title']=str_replace('"','',$ENTRY['title']);
		// Storing all the entries in an array
		if (isset($ATC_LIST[$ENTRY['code']]))											failProcess("B02","DUPLICATE CODE : |".$ENTRY['code']."|\n");
		$ATC_LIST[$ENTRY['code']]=$ENTRY;
		
	}
	fclose($fp);
	
	// Assigning the children to their parents
	foreach ($ATC_LIST as $CODE=>&$ENTRY)
	{
		if (!isset($ENTRY['parent_code|'])){echo $CODE."\n";print_r($ENTRY);exit;}
		if ($ENTRY['parent_code|']!='')$ATC_LIST[$ENTRY['parent_code|']]['CHILD'][$CODE]=true;
	}

	addLog("\tLoading ATC Codes from database");
	// Getting current entries in the database
	$res=runQuery("SELECT * FROM ATC_entry");
	if ($res===false)																			failProcess($JOB_ID."B03",'Unable to get ATC entries');
	$ATC_DB=array();
	// MAX_ID will be used to generate new IDs for new entries
	$MAX_ID=0;
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$ATC_DB[$line['atc_code']]=$line;
		//  keep track of the max primary key value
		$MAX_ID=max($MAX_ID,$line['atc_entry_id']);
	}
	//echo implode("\t",array_keys($ATC_DB));exit;
	

	addLog("\tComparing DB<->File");
	$fpo=fopen('INSERT/ATC_entry.csv','w'); 
	if (!$fpo)																					failProcess($JOB_ID."B04",'Unable to open ATC_entry.csv');
	$HAS_NEW=false;
	foreach ($ATC_LIST as $CODE=>&$ENTRY)
	{
		
		// If the entry is already in the database, we just update the status
		if (isset($ATC_DB[$CODE]))
		{
			
			$ATC_DB[$CODE]['DB_STATUS']='VALID';
			// Assigning the database ID to the entry so we can update the hierarchy
			$ENTRY['DB']=$ATC_DB[$CODE]['atc_entry_id'];

			// If the title has changed, we update the database
			if (strtolower($ATC_DB[$CODE]['atc_title'])!=strtolower($ENTRY['title']))
			{
				echo ("\t\tATC\tALT_TITLE\tCURRENT:".$ATC_DB[$CODE]['atc_title']."\tNEW:".$ENTRY['title'])."\n";
				if (!runQueryNoRes(
					"UPDATE ATC_ENTRY 
					set ATC_title= '".str_replace("'","''",$ENTRY['title'])."' 
					where ATC_code = '".$CODE."'"))											failProcess($JOB_ID."B05",'Unable to update ATC title '.$CODE);
			}
		}
		else
		{
			// If the entry is not in the database, we create a new entry
			++$MAX_ID;
			$ENTRY['DB']=$MAX_ID;
			$HAS_NEW=true;
		//	addLog("\t\tATC\tNEW\t".$CODE."\t".$ENTRY['title']);
			// We add the entry to the file that will be used to insert the data into the database
			fputs($fpo,$ENTRY['DB']."\t".$CODE."\t".$ENTRY['title']."\n");
		}
	}

	
	fclose($fpo);

	addLog("\tDeleting old entries");
	// We are going to remove all the entries that are not in the database anymore
	foreach ($ATC_DB as &$ENTRY)
	{
		if ($ENTRY['DB_STATUS']!='FROM_DB')continue;
		echo ("\t\tATC\tDELETE\t".$ENTRY['atc_code']."\t".$ENTRY['atc_title'])."\n";
		if (!runQueryNoRes("DELETE FROM DRUG_ATC_MAP where ATC_entry_id = '".$ENTRY['atc_entry_id']."'"))	failProcess($JOB_ID."B06",'Unable to delete ATC entry '.$ENTRY['atc_code']);

		if (!runQueryNoRes("DELETE FROM ATC_ENTRY where ATC_entry_id = '".$ENTRY['atc_entry_id']."'"))	failProcess($JOB_ID."B06",'Unable to delete ATC entry '.$ENTRY['atc_code']);
	}
	//print_R($ATC_LIST);
	addLog("\tCreating mapping for next steps");
	foreach ($ATC_LIST as $CODE=>&$ENTRY)
	{
		$ATC_CODES[$CODE]=$ENTRY['DB'];
	}
	
	addLog("\tPushing records to the database");
	// If we have new entries, we insert them into the database
	if ($HAS_NEW)
	{
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.ATC_entry (atc_entry_id,atc_code,atc_title) FROM \'INSERT/ATC_entry.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		//echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		$res=array();	
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		if ($return_code !=0 )	 																		failProcess($JOB_ID."B07",'Unable to insert data into ATC_entry '.print_r($res,true));
	}

	addLog("\tCreating hierarchy");
	// We are going to generate the tree.csv file that will be used to insert the hierarchy
	$fp=fopen('INSERT/ATC_tree.csv','w');if (!$fp)														failProcess($JOB_ID."B08",'Unable to open tree.csv'); 
	
	// VALUE is the boundary value for the tree traversal
	$VALUE=0;
	// We are going to generate the tree from the roots
	genTree($fp,$ATC_LIST,$ROOTS,0,$VALUE);
	fclose($fp);

	addLog("\tDeleting former hierarchy");
	// We are going to truncate the hierarchy table
	if (!runQueryNoRes("truncate table ATC_hierarchy"))												failProcess($JOB_ID."B09",'Unable to truncate ATC_hierarchy');

	addLog("\tSaving new hierarchy");
	// We are going to insert the hierarchy 
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.ATC_hierarchy (atc_entry_id,atc_level,atc_level_left,atc_level_right) FROM \'INSERT/ATC_tree.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	$res=array();	
	exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
	if ($return_code !=0 )	 																		failProcess($JOB_ID."B10",'Unable to insert data into ATC_Tree '.print_r($res,true));
}
	


/// Create a nested set representation of the tree
/// This will be used as represent the hierarchy.
/// Each record will have a left and right value that will be used to traverse the tree
/// any record within the left and right value will be a child of the record
/// Any record with left value lower and right value higher than the record will be a parant
function genTree(&$fp,&$DATA,$ROOTS,$LEVEL,&$LEVEL_V)
{
	global $JOB_ID;
	++$LEVEL;
	foreach ($ROOTS as $RID=>$T)
	{
		
		if (!isset($DATA[$RID])){echo "ENTRY NOT FOUND : |".$RID."|\n";continue;}
		
		//if (!isset($DATA[$RID]['DB'])){echo "DB:".$RID."\n";continue;}
		// for($I=0;$I<$LEVEL;++$I)echo "\t";
		// echo "PROCESSING ".$RID."\n";
		++$LEVEL_V;$LEVEL_LEFT=$LEVEL_V;
		if (isset($DATA[$RID]['CHILD']))genTree($fp,$DATA,$DATA[$RID]['CHILD'],$LEVEL,$LEVEL_V);
		//for($I=0;$I<$LEVEL;++$I)echo "\t";

		++$LEVEL_V;$LEVEL_RIGHT=$LEVEL_V;
		//echo $RID."\t".$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n";
		if (!isset($DATA[$RID]['DB']) || $DATA[$RID]['DB']=='') 
		{
			echo "DATABASE ENTRY NOT FOUND : ".$RID."\n";return ;
			failProcess($JOB_ID."C01",'Database ID not found for '.$RID."\n");
		}
		fputs($fp,$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n");
	}
}
		




function loadClinicalInfo(&$CLIN_INFO)
{
	addLog("Load clinical info");
	$CLIN_INFO=array('CLIN'=>array(),'DRUG'=>array());


	addLog("\tLoad clinical_trials");
	$fp=fopen('clinical_trials.csv','r');if (!$fp)											failProcess($JOB_ID."D01",'Unable to open clinical_trials file');
	$HEAD=array_flip(fgetcsv($fp));
	while(!feof($fp))
	{
		$tab=fgetcsv($fp);if ($tab===false)continue;
		$ENTRY=array();foreach ($HEAD as $K=>$V)$ENTRY[$K]=$tab[$V];
		$ENTRY['end_date_kind|']=substr($ENTRY['end_date_kind|'],0,-1);
		/// ENTRY['identifier'] is the NCT id
		$CLIN_INFO['CLIN'][$ENTRY['identifier']]=$ENTRY;
	}
	fclose($fp);
	


	addLog("\tLoad clinical_trial_phases");
	$fp=fopen('clinical_trial_phases.csv','r');if (!$fp)									failProcess($JOB_ID."D02",'Unable to open clinical_trial_phases file');
	$HEAD=array_flip(fgetcsv($fp));
	while(!feof($fp))
	{
		$tab=fgetcsv($fp);if ($tab===false)continue;
		$ENTRY=array();foreach ($HEAD as $K=>$V)$ENTRY[$K]=$tab[$V];
		//print_R($ENTRY);
		/// Assign the phase
		$CLIN_INFO['CLIN'][$ENTRY['trial_id']]['phase']=substr($ENTRY['phase|'],0,-1);
	}
	fclose($fp);



	addLog("\tLoad clinical_trial_interventions");
	$fp=fopen('clinical_trial_interventions.csv','r');if (!$fp)								failProcess($JOB_ID."D03",'Unable to open clinical_trial_interventions file');
	$HEAD=array_flip(fgetcsv($fp));

	/// Intervention hash to trial id
	$INT_MAP=array();
	while(!feof($fp))
	{
		$tab=fgetcsv($fp);if ($tab===false)continue;
		$ENTRY=array();foreach ($HEAD as $K=>$V)$ENTRY[$K]=$tab[$V];
		$INT_MAP[$ENTRY['id']]=$ENTRY['trial_id'];
		/// Assign the different interventions
		$ARR=array(
			'kind'=>$ENTRY['kind'],
			'title'=>$ENTRY['title'],
			'description'=>substr($ENTRY['description|'],0,-1));
		$CLIN_INFO['CLIN'][$ENTRY['trial_id']]['interventions'][$ENTRY['id']]=$ARR;
	}
	fclose($fp);



	addLog("\tLoad clinical_trial_interventions_drugs");
	$fp=fopen('clinical_trial_interventions_drugs.csv','r');if (!$fp)								failProcess($JOB_ID."D04",'Unable to open clinical_trial_interventions_drugs file');
	$HEAD=array_flip(fgetcsv($fp));
	
	while(!feof($fp))
	{
		$tab=fgetcsv($fp);if ($tab===false)continue;
		$ENTRY=array();foreach ($HEAD as $K=>$V)$ENTRY[$K]=$tab[$V];

		/// We use the intervention hash to get the trial id
		$TRIAL_ID=$INT_MAP[$ENTRY['intervention_id']];
		
		$ENTRY['drug_id']=substr($ENTRY['drug_id|'],0,-1);
		/// Assign the different drugs to the intervention
		$CLIN_INFO['CLIN'][$TRIAL_ID]['interventions'][$ENTRY['intervention_id']]['drug_id'][$ENTRY['drug_id']]=array('DRUG_ID'=>$ENTRY['drug_id'],'DB_STATUS'=>'FROM_DB');

		// We are only interested in the phase of the drug, so we are going to keep the max phase for each drug
		if (!isset($CLIN_INFO['CLIN'][$TRIAL_ID]['phase']))continue;
		if (isset($CLIN_INFO['DRUG'][$ENTRY['drug_id']]))$CLIN_INFO['DRUG'][$ENTRY['drug_id']]=max($CLIN_INFO['DRUG'][$ENTRY['drug_id']],$CLIN_INFO['CLIN'][$TRIAL_ID]['phase']);
		else $CLIN_INFO['DRUG'][$ENTRY['drug_id']]=$CLIN_INFO['CLIN'][$TRIAL_ID]['phase'];
	}
	fclose($fp);
	addLog("\tEND Load clinical info");
	//print_R($CLIN_INFO['DRUG']);
	
}





function loadFromDB()
{
	global $SOURCE_ID;
	global $FROM_DB;
	global $SM_SOURCE;


	/// Get the snapshot of Drugbank information from Biorels.



	addLog("\tGet Drugs");
	$res=runQuery("SELECT * FROM drug_entry");
	if ($res===false)																		failProcess($JOB_ID."E01",'Unable to run query '.$query);
	$DATA=&$FROM_DB;
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$line['NAME']=array();
		$line['EXTDB']=array();
		$line['DESC']=array();
		$line['SM']=array();
		$DATA[$line['drug_entry_id']]=$line;
	}





	addLog("\tGet Drug Name");
	$res=runQuery("SELECT * FROM drug_name");
	if ($res===false)																		failProcess($JOB_ID."E02",'Unable to run query '.$query);
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['drug_entry_id']]['NAME'][]=$line;
	}




	addLog("\tGet Drugs external identifiers");
	$res=runQuery("SELECT * FROM drug_extdb de 
					where source_id = ".$SOURCE_ID." 
					OR source_origin_id = ".$SOURCE_ID);
	if ($res===false)																	failProcess($JOB_ID."E03",'Unable to run query '.$query);
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['drug_entry_id']]['EXTDB'][]=$line;
	}




	addLog("\tGet Drugs description");
	$res=runQuery("SELECT * FROM drug_description de where source_id = ".$SOURCE_ID);
	if ($res===false)																	failProcess($JOB_ID."E04",'Unable to run query '.$query);
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['drug_entry_id']]['DESC'][]=$line;
	}




	addLog("\tGet Drugs Type");
	$res=runQuery("SELECT * FROM drug_type_map de ");
	if ($res===false)																	failProcess($JOB_ID."E05",'Unable to run query '.$query);
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['drug_entry_id']]['TYPE'][]=$line;
	}




	addLog("\tGet Drugs ATC Mapping");
	$res=runQuery("SELECT * FROM drug_atc_map de ");
	if ($res===false)																	failProcess($JOB_ID."E06",'Unable to run query '.$query);
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['drug_entry_id']]['ATC'][]=$line;
	}




	addLog("\tGet Small molecule data");
	// This connect Small molecule structure to the drug entry
	$res=runQuery("SELECT * FROM drug_mol_entity_map dsm, molecular_entity me, sm_entry se 
				LEFT JOIN sm_counterion sc on sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm
				wHERE dsm.molecular_entity_id = me.molecular_entity_id 
				AND se.md5_hash = me.molecular_structure_hash 
				AND se.sm_molecule_id = sm.sm_molecule_id");
	if ($res===false)																failProcess($JOB_ID."E07",'Unable to run query '.$query);
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['drug_entry_id']]['SM'][$line['molecular_entity_id']]=$line;
	}
	// addLog("\tGet Small molecule names");
	// $res=runQuery("SELECT dsm.drug_entry_id, sm_entry_Id FROM drug_sm_map dsm");
	// foreach ($res as $line)
	// {
	// 	$line['DB_STATUS']='FROM_DB';
	// 	$DATA[$line['drug_entry_id']]['SM'][$line['sm_entry_id']]=$line;
	// }
	addLog("\tLoad Small molecules");
	$res=runQuery('SELECT * FROM sm_source ss, sm_entry se, molecular_entity me 
	where se.md5_hash = me.molecular_structure_hash 
	AND se.sm_entry_Id =ss.sm_entry_id
		AND source_id = '.$SOURCE_ID);
	if ($res===false)																failProcess($JOB_ID."E08",'Unable to run query '.$query);

	/// SM_SOURCE is a global variable that will contain all the small molecules from the database
	$SM_SOURCE=array();
	foreach	 ($res as $line)$SM_SOURCE[$line['sm_name']]=
		array('DB_ID'=>$line['molecular_entity_id'],
		'DB_STATUS'=>'FROM_DB');


}
	




function processClinicalTrials()
{
	global $SOURCE_ID;
	global $FILES;
	global $DBIDS;
	global $FILE_STATUS;
	
	/// Now that we have all the data, we are going to process the clinical trials

	
	addLog("\tReLoad Drugs.csv");
	$fp=fopen('drugs.csv','r');if (!$fp)								failProcess($JOB_ID.'F01','Unable to open drugbank  file');
	
	/// Get the ID/Drugbank ID from the drugs.csv file
	$MAP_DRUG=array();
	$DR_IDS=array();
	$HEAD=array_flip(fgetcsv($fp));
	while(!feof($fp))
	{
		$tab=fgetcsv($fp);if ($tab===false)continue;
		$ENTRY=array();
		foreach ($HEAD as $K=>$V)$ENTRY[$K]=$tab[$V];
	  	$MAP_DRUG[$ENTRY['id']]=array($ENTRY['drugbank_id'],-1);
		$DR_IDS[$ENTRY['drugbank_id']]=$ENTRY['id'];
	}


	/// Getting the corresponding drug_entry_id from the database
	$res=runQuery("SELECT drug_entry_id, drugbank_id 
				FROM drug_entry 
				WHERE drugbank_id IN ('".implode("','",array_keys($DR_IDS))."')");
	if ($res===false)													failProcess($JOB_ID."F02",'Unable to run query '.$query);
	foreach ($res as &$line)
	{
		$MAP_DRUG[$DR_IDS[$line['drugbank_id']]][1]=$line['drug_entry_id'];
	}
	fclose($fp);

	
	

	global $CLIN_INFO;
	
	/// Too many so we chunk it
	$CHUNKS=array_chunk(array_keys($CLIN_INFO['CLIN']),500);
	
	$N_FROM_DRUGBANK=0;
	$N_FROM_DB=0;
	$N_MATCH=0;
	$N_ADD=0;
	$FROM_DB_INFO=array();
	foreach ($CHUNKS as $N_RUN=>$CHUNK)
	{

		///Build the query
		$query='SELECT DISTINCT trial_id, cti.clinical_trial_intervention_id, intervention_name, intervention_type, intervention_description, drug_entry_id
		FROM clinical_trial ct, clinical_trial_intervention cti
		LEFT JOIN clinical_trial_intervention_drug_map cd ON cti.clinical_trial_intervention_id = cd.clinical_trial_intervention_id 
		Where  ct.clinical_trial_id =cti.clinical_trial_id 
		AND trial_Id IN (';

		foreach ($CHUNK as $K)
		{
			$Y=true;
			$query.="'".$K."',";
		}
		//if (!$Y)continue;
		$res=runQuery(substr($query,0,-1).')');
		
		if ($res===false)															failProcess($JOB_ID."F03",'Unable to run query '.$query);
		if ($res==array())continue;
		//print_R($res);
			
		//print_r($CLIN_INFO['CLIN']['NCT00120471']);
		//exit;
		foreach ($res as &$line)
		{
			//echo "####"
			$N_FROM_DB++;
			$FROM_DB_INFO[$line['trial_id']][]=$line;
			//[$line['clinical_trial_intervention_id']]['drug_id'][]=array('DRUG_ID'=>$line['drug_entry_id'],'DB_STATUS'=>'FROM_DB');
			//
			/// In some instance, there is a difference between clinical trial intervention and drug bank intervention
			/// So we use a similarity algorithm to find the best match
			$MATCH=array();$N_CHOICE=0;
			foreach($CLIN_INFO['CLIN'][$line['trial_id']]['interventions'] as $K=>&$CLIN_INTER)
			{
				if (strtolower($CLIN_INTER['title'])!=strtolower($line['intervention_name']))continue;
				if (strtolower($CLIN_INTER['kind'])!=strtolower($line['intervention_type']))continue;
				
				$SCORE=0.0;
				similar_text($CLIN_INTER['description'],$line['intervention_description'],$SCORE);
				if ($CLIN_INTER['description']=='' && $line['intervention_description']=='')$SCORE=100;
				$MATCH[ceil($SCORE)][]=$K;
				++$N_CHOICE;
			}
			if ($MATCH==array())continue;
			krsort($MATCH);
			//print_r($MATCH);
			$BEST_SCORE=array_keys($MATCH)[0];
			//echo $line['trial_id']."\t".$line['intervention_name']."\t".$line['intervention_type']."\t".$line['intervention_description']."\t".$line['drug_entry_id']."\n";
			
			/// Ensuring we have the best possible match
			if ($BEST_SCORE<90 && $N_CHOICE>1)
			{
				print_R($MATCH);
				echo "NO MATCH FOUND\n";
				continue;
			
			}
			//if (count($MATCH[$BEST_SCORE])!=1) failProcess($JOB_ID."011",'Unable to find best match for '.$line['trial_id']);
			$CLIN_INTER=&$CLIN_INFO['CLIN'][$line['trial_id']]['interventions'][$MATCH[$BEST_SCORE][0]];
				
			$CLIN_INTER['DB_ID']=$line['clinical_trial_intervention_id'];
			if (isset($CLIN_INTER['drug_id']) && $line['drug_entry_id']!='')
			foreach ($CLIN_INTER['drug_id'] as &$DRUG_ID)
			{
				if ($MAP_DRUG[$DRUG_ID['DRUG_ID']][1]!=$line['drug_entry_id'])continue;
				//echo "FOUND";
				if ($DRUG_ID['DB_STATUS']=='FROM_DB')
				{
				$N_MATCH++;
				$DRUG_ID['DB_STATUS']='VALID';
				}
				break;
			}
			
			//echo "\n";
		}


		foreach ($CHUNK as $trial_id)
			if (isset($CLIN_INFO['CLIN'][$trial_id]['interventions']))
			foreach($CLIN_INFO['CLIN'][$trial_id]['interventions'] as $INTER_ID=>&$CLIN_INTER)
			{
				
				if (!isset($CLIN_INTER['drug_id']))continue;
				if (!isset($CLIN_INTER['DB_ID']))
				{
					if (!isset($FROM_DB_INFO[$trial_id]))continue;
					addLog("No intervention match\t".$INTER_ID."\t".$trial_id)."\n";
					continue;
				}
				foreach ($CLIN_INTER['drug_id'] as &$DRUG_ID)
				{
					$N_FROM_DRUGBANK++;
					if ($DRUG_ID['DB_STATUS']=='VALID')continue;
					++$N_ADD;
					$FILE_STATUS['clinical_trial_intervention_drug_map']=1;
					++$DBIDS['clinical_trial_intervention_drug_map'];
					fputs($FILES['clinical_trial_intervention_drug_map'],
						$DBIDS['clinical_trial_intervention_drug_map']."\t".
						$CLIN_INTER['DB_ID']."\t".
						$MAP_DRUG[$DRUG_ID['DRUG_ID']][1]."\t".
						$SOURCE_ID."\n");
					
				}
			}
		//exit;
		echo $N_RUN."/".count($CHUNKS)."\t".$N_FROM_DB."\t".$N_FROM_DRUGBANK."\t".$N_MATCH."\t".$N_ADD."\n";
	}
	
	
}


function compareRecords()
{
	global $FROM_DB;
	global $FROM_FILES;

	// We are going to compare the records from the files to the records from the DB
	// Each drug_entry record has a drugbank_id, so we are going to use it as a key
	$DRUGBANK_ID_MAP=array();
	$CHEMBL_ID_MAP=array();
	foreach ($FROM_DB as $DB_ID=>&$RECORD)
	{
		if ($RECORD['drugbank_id']!='')$DRUGBANK_ID_MAP[$RECORD['drugbank_id']]=$DB_ID;
		if ($RECORD['chembl_id']!='')$CHEMBL_ID_MAP[$RECORD['chembl_id']]=$DB_ID;
	}
	
	foreach ($FROM_FILES as &$FILE_RECORD)
	{
		echo ("############### PROCESSING ".$FILE_RECORD['drugbank_id']."\n");
		$time=microtime_float();
		//If the record exist, we compare	
		if (isset($DRUGBANK_ID_MAP[$FILE_RECORD['drugbank_id']])) 
		{
			echo ("\tFOUND DRUGBANK MATCH IN DB")."\n";
			compareRecord($FILE_RECORD,$DRUGBANK_ID_MAP[$FILE_RECORD['drugbank_id']]);
		}
		//If not, we insert
		else if (isset($CHEMBL_ID_MAP[$FILE_RECORD['chembl_id']]))
		{
			echo ("\tFOUND CHEMBL MATCH IN DB")."\n";
			compareRecord($FILE_RECORD,$CHEMBL_ID_MAP[$FILE_RECORD['chembl_id']]);
		}
		else 
		{
			echo ("\tNEW RECORD")."\n";
			insertRecord($FILE_RECORD);
		}
		echo round(microtime_float()-$time,2)."\n";
	}
	
}


function loadArticles(&$ARTICLES)
{
	addLog("Load articles");
	$fp=fopen('cited_articles.csv','r'); if (!$fp)								failProcess($JOB_ID."I011",'Unable to open cited_articles file');
	$HEAD=array_flip(fgetcsv($fp));
	$MAP=array();
	while(!feof($fp))
	{
		$tab=fgetcsv($fp);if ($tab===false)continue;
		//print_R($tab);
		$ENTRY=array();foreach ($HEAD as $K=>$V)$ENTRY[$K]=$tab[$V];
		$ARTICLES[$ENTRY['ref_id']]=$ENTRY;
		if ($ENTRY['pubmed_id']!='')		$MAP[$ENTRY['pubmed_id']]=$ENTRY['ref_id'];
		else print_R($ENTRY);
	}
	fclose($fp);
	//exit;
	// echo "END\n";
	// $res=runQuery("SELECT pmid_entry_id, pmid FROM pmid_entry WHERE pmid IN ('".implode("','",array_keys($MAP))."')");
	// foreach ($res as $line)
	// {
	// 	$ARTICLES[$MAP[$line['pmid']]]['pmid_entry_id']=$line['pmid_entry_id'];
	// }
}


function pushToDB($LAST_CALL=false)
{
	global $COL_ORDER;
	global $FILES;
	global $FILE_STATUS;
	global $GLB_VAR;
	global $DBIDS;
	global $JOB_ID;
	global $DB_INFO;
	global $TO_DEL;

	/// We are going to delete all the records that have been marked for deletion
	foreach ($TO_DEL as $TBL=>&$LIST)
	{
		if ($LIST==array())continue;
		echo ("Deleting ".$TBL." ".count($LIST).' records')."\n";
		$res=runQueryNoRes("DELETE FROM ".$TBL." WHERE ".$TBL."_id IN (".implode(",",$LIST).")");
		if ($res===false)																			failProcess($JOB_ID."J01",'Unable to delete records from '.$TBL);
		// We don't forget to reset the list of records to delete
		$TO_DEL[$TBL]=array();
	}

	/// We are going to insert all the records that have been marked for insertion
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
		// If no records have been written to the file we don't need to insert it
		if (!$FILE_STATUS[$NAME]){echo "SKIPPING ".$NAME."\t";continue;}
		
		// We close the file handler
		fclose($FILES[$NAME]);

		// Preparing the COPY command
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \'INSERT/'.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		
		echo $NAME."\t".$FILE_STATUS[$NAME]."\t";
		$res=array();
	
		// We run the command
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		if ($return_code !=0 )	 failProcess($JOB_ID."J02",'Unable to insert data into '.$NAME.' '.print_r($res,true));
	}
	// We reset the file status
	$FILES=array();

	//If it's the last call we don't need to reopen the files
	if ($LAST_CALL)return;
	
	// We reopen the files
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$FILE_STATUS[$TBL]=0;
		$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');if (!$FILES[$TBL])				failProcess($JOB_ID."J03",'Unable to open file '.$TBL.'.csv');
	}
	

}

function insertRecord(&$FILE_RECORD)
{
	/// TO be called when the record does not exist in the database
	global $FILES;
	global $DBIDS;
	global $SOURCE_ID;
	global $FILE_STATUS;
	global $JOB_ID;
	global $ATC_CODES;
	global $CLIN_INFO;
	global $STATS;
	$STATS['drug_entry:NEW']++;
	$FILE_STATUS['drug_entry']=1;
	$DBIDS['drug_entry']++;
	
	// Conversion 0/1 to F/T
	$MAP=array(1=>'T',0=>'F');

	// We insert the record into the drug_entry table
	fputs($FILES['drug_entry'],$DBIDS['drug_entry']."\t".
	$MAP[$FILE_RECORD['approved']]."\t".
	$MAP[$FILE_RECORD['withdrawn']]."\t".
	$FILE_RECORD['MAX_CLIN_PHASE']."\t".
	$FILE_RECORD['name']."\t".
	$MAP[$FILE_RECORD['experimental']]."\t".
	$MAP[$FILE_RECORD['investigational']]."\t".
	$MAP[$FILE_RECORD['nutraceutical']]."\t".
	$MAP[$FILE_RECORD['illicit']]."\t".
	$MAP[$FILE_RECORD['vet_approved']]."\t".
	$FILE_RECORD['drugbank_id']."\t".
	$FILE_RECORD['chembl_id']."\n");

	// We insert synonyms.
	// $NAMES is going to be used to avoid duplicates
	$NAMES=array();
	if (isset($FILE_RECORD['ALT_NAME']))
	foreach ($FILE_RECORD['ALT_NAME'] as &$NAME_R)
	{
		if (isset($NAMES[$NAME_R['synonym']]))continue;
		$NAMES[$NAME_R['synonym']]=true;
		++$DBIDS['drug_name'];
		$STATS['drug_name:NEW']++;
		$FILE_STATUS['drug_name']=1;
		fputs($FILES['drug_name'],
		$DBIDS['drug_name']."\t".
		$DBIDS['drug_entry']."\t\"".
		str_replace('"','""',$NAME_R['synonym'])."\"\tF\tF\t".
		$SOURCE_ID."\n");
	
	}


	// We insert the descriptions
	if ($FILE_RECORD['description']!='')
	{
		$STATS['drug_desc:NEW']++;
		$DBIDS['drug_description']++;
		$FILE_STATUS['drug_description']=1;
		fputs($FILES['drug_description'],
			$DBIDS['drug_description']."\t".
			$DBIDS['drug_entry']."\t\"".
			str_replace('"','""',$FILE_RECORD['description']).
			"\"\tComplete\t".$SOURCE_ID."\n");
	}

	if ($FILE_RECORD['simple_description']!='')
	{
		$STATS['drug_desc:NEW']++;
		$DBIDS['drug_description']++;
		$FILE_STATUS['drug_description']=1;
		fputs($FILES['drug_description'],
			$DBIDS['drug_description']."\t".
			$DBIDS['drug_entry']."\t\"".
			str_replace('"','""',$FILE_RECORD['simple_description']).
			"\"\tSimple\t".$SOURCE_ID."\n");
	}

	if ($FILE_RECORD['clinical_description']!='')
	{
		$DBIDS['drug_description']++;
		$FILE_STATUS['drug_description']=1;
		fputs($FILES['drug_description'],
			$DBIDS['drug_description']."\t".
			$DBIDS['drug_entry']."\t\"".
			str_replace('"','""',$FILE_RECORD['clinical_description']).
			"\"\tClinical\t".$SOURCE_ID."\n");
		$STATS['drug_desc:NEW']++;
	}
	

	if (isset($FILE_RECORD['EXTDB']))
	foreach ($FILE_RECORD['EXTDB'] as &$EXTDB_R)
	{
		++$DBIDS['drug_extdb'];
		$STATS['drug_ext_db:NEW']++;
		$FILE_STATUS['drug_extdb']=1;
		fputs($FILES['drug_extdb'],$DBIDS['drug_extdb']."\t"
		.$DBIDS['drug_entry']."\t".
		$EXTDB_R['drug_extdb_value']."\t".
		$EXTDB_R['source']."\t".
		$SOURCE_ID."\n");
	}

	if (isset($FILE_RECORD['TYPE']))
	foreach ($FILE_RECORD['TYPE'] as &$TYPE_R)
	{
		++$DBIDS['drug_type_map'];
		$STATS['drug_type:NEW']++;
		$FILE_STATUS['drug_type_map']=1;
		fputs($FILES['drug_type_map'],
		$DBIDS['drug_type_map']."\t"
		.$DBIDS['drug_entry']."\t".
		$TYPE_R."\n");
	}


	if (isset($FILE_RECORD['MAPPING']))
	foreach ($FILE_RECORD['MAPPING'] as &$MAPPING_R)
	{
		if ($MAPPING_R['source']!='ATC' && $MAPPING_R['source']!='ATCvet')continue;
		$STATS['drug_atc_map:NEW']++;
		++$DBIDS['drug_atc_map'];
		$FILE_STATUS['drug_atc_map']=1;
		fputs($FILES['drug_atc_map'],$DBIDS['drug_atc_map']."\t"
		.$DBIDS['drug_entry']."\t".$ATC_CODES[$MAPPING_R['code']]."\n");
	}

	
	if ($FILE_RECORD['type']!='SmallMoleculeDrug')return;
	global $SM_SOURCE;
	
	if (isset($SM_SOURCE[$FILE_RECORD['drugbank_id']]))
	{
		$SM_ENTRY=&$SM_SOURCE[$FILE_RECORD['drugbank_id']];
		$MOL_ENTITY_DBID=$SM_ENTRY['DB_ID'];
		$STATS['drug_mol_entity_map:NEW']++;
		++$DBIDS['drug_mol_entity_map'];
		$FILE_STATUS['drug_mol_entity_map']=1;
		fputs($FILES['drug_mol_entity_map'],
			$DBIDS['drug_mol_entity_map']."\t".
			$DBIDS['drug_entry']."\t".
			$MOL_ENTITY_DBID."\t0\t".
			$SOURCE_ID."\n");
	}
	
}









function compareRecord(&$FILE_RECORD,&$DB_RECORD_ID)
{
	global $FROM_DB;
	global $FILES;
	global $DBIDS;
	global $SOURCE_ID;
	global $FILE_STATUS;
	global $JOB_ID;
	global $TO_DEL;
	global $STATS;
	global $ATC_CODES;
	$DB_RECORD=$FROM_DB[$DB_RECORD_ID];
	
	


	$QUERY='UPDATE drug_Entry SET ';
	$TO_UPDATE=false;
	$MAP=array(1=>'T',0=>'F');
	if ($DB_RECORD['chembl_id']=='')$DB_RECORD['chembl_id']='NULL';
	else if ($DB_RECORD['drugbank_id']=='')
	{
		echo ("\tDrugbank_id=>".$FILE_RECORD['drugbank_id'])."\n";
		$QUERY.='drugbank_id=\''.$FILE_RECORD['drugbank_id'].'\', '; 
		$TO_UPDATE=true;
	}
	if ($DB_RECORD['is_approved']!=$MAP[$FILE_RECORD['approved']])
	{
		echo ("\tApproved=>".$DB_RECORD['is_approved']."=>".$MAP[$FILE_RECORD['approved']])."\n";
		$QUERY.='is_approved=\''.$MAP[$FILE_RECORD['approved']].'\', ';
		$TO_UPDATE=true;
	}
	if ($DB_RECORD['is_withdrawn']!=$MAP[$FILE_RECORD['withdrawn']])
	{
		echo ("\tCHANGE\tWITHDRAWN\tFROM:".$DB_RECORD['is_withdrawn'].'=>'.$MAP[$FILE_RECORD['withdrawn']])."\n";
		$QUERY.='is_withdrawn=\''.$MAP[$FILE_RECORD['withdrawn']].'\', '; 
		$TO_UPDATE=true;
	}
	if ($DB_RECORD['max_clin_phase']!=$FILE_RECORD['MAX_CLIN_PHASE'])
	{
		echo ("\tCHANGE\tMAX_CLIN_PHASE\tFROM:".$DB_RECORD['max_clin_phase'].'=>'.$FILE_RECORD['MAX_CLIN_PHASE'])."\n";
		$QUERY.='max_clin_phase=\''.$FILE_RECORD['MAX_CLIN_PHASE'].'\', ';
		$TO_UPDATE=true;
	}
	if ($DB_RECORD['drug_primary_name']!=$FILE_RECORD['name'])
	{
		echo ("\tCHANGE\tDRUG_PRIMARY_NAME\tFROM:".$DB_RECORD['drug_primary_name'].'=>'.$FILE_RECORD['name'])."\n";
		$QUERY.='drug_primary_name=\''.$FILE_RECORD['name'].'\', '; 
		$TO_UPDATE=true;
	}
	if ($DB_RECORD['is_experimental']!=$MAP[$FILE_RECORD['experimental']])
	{
		echo ("\tCHANGE\tIS_EXPERIMENTAL\tFROM:".$DB_RECORD['is_experimental'].'=>'.$MAP[$FILE_RECORD['experimental']])."\n";
		$QUERY.='is_experimental=\''.$MAP[$FILE_RECORD['experimental']].'\', '; $TO_UPDATE=true;
	}
	if ($DB_RECORD['is_investigational']!=$MAP[$FILE_RECORD['investigational']])
	{
		echo ("\tCHANGE\tIS_INVESTIGATIONAL\tFROM:".$DB_RECORD['is_investigational'].'=>'.$MAP[$FILE_RECORD['investigational']])."\n";
		$QUERY.='is_investigational=\''.$MAP[$FILE_RECORD['investigational']].'\', '; 
		$TO_UPDATE=true;
	}
	if ($DB_RECORD['is_nutraceutical']!=$MAP[$FILE_RECORD['nutraceutical']])
	{
		echo ("\tCHANGE\tIS_NUTRACEUTICAL\tFROM:".$DB_RECORD['is_nutraceutical'].'=>'.$MAP[$FILE_RECORD['nutraceutical']])."\n";
		$QUERY.='is_nutraceutical=\''.$MAP[$FILE_RECORD['nutraceutical']].'\', '; 
		$TO_UPDATE=true;
	}
	if ($DB_RECORD['is_illicit']!=$MAP[$FILE_RECORD['illicit']])
	{
		echo ("\tCHANGE\tIS_ILLICIT\tFROM:".$DB_RECORD['is_illicit'].'=>'.$MAP[$FILE_RECORD['illicit']])."\n";
		$QUERY.='is_illicit=\''.$MAP[$FILE_RECORD['illicit']].'\', '; 
		$TO_UPDATE=true;
	}
	if ($DB_RECORD['is_vet_approved']!=$MAP[$FILE_RECORD['vet_approved']])
	{
		echo ("\tCHANGE\tIS_VET_APPROVED\tFROM:".$DB_RECORD['is_vet_approved'].'=>'.$MAP[$FILE_RECORD['vet_approved']])."\n";
		$QUERY.='is_vet_approved=\''.$MAP[$FILE_RECORD['vet_approved']].'\', '; 
		$TO_UPDATE=true;
	}
	
	if ($DB_RECORD['chembl_id']!=$FILE_RECORD['chembl_id'])
	{ 
		echo $DB_RECORD['drugbank_id']."\tCHANGE\tCHEMBL_ID\tFROM:".$DB_RECORD['chembl_id'].'=>'.$FILE_RECORD['chembl_id']."\n";

		$res=runQuery('SELECT * FROM drug_entry where chembl_id=\''.$FILE_RECORD['chembl_id'].'\'');
		if (count($res)==0)
		{
			$QUERY.='chembl_id=\''.$FILE_RECORD['chembl_id'].'\', '; 
			$TO_UPDATE=true;
		}
		else 
		{
			$ALT_ENTRY=$res[0];
			if ($ALT_ENTRY['drugbank_id']=='')
			{
				$res=runQueryNoRes("DELETE FROM drug_entry where drugbank_id='".$DB_RECORD['drugbank_id']."'");
				$res=runQueryNoRes("UPDATE drug_entry SET drugbank_id='".$DB_RECORD['drugbank_id']."' WHERE chembl_id='".$FILE_RECORD['chembl_id']."'");
				///ISSUE WHEN MERGING RECORDS - NO OTHER FIELDS ARE UPDATED UNTIL NEXT UPDATE.
				return;
			}
		}

		
	}
	if ($TO_UPDATE)
	{
		$QUERY=substr($QUERY,0,-2);
		$QUERY.=' WHERE drug_entry_id='.$DB_RECORD_ID;

		$res=runQueryNoRes($QUERY);
		if ($res===false)									failProcess($JOB_ID."K01",'Unable to update drug_entry table');
	}




	/////////// EXT DB
	/// There is no concept of updates for EXT DB
	/// So we are going to compare the records and insert the new ones and delete the old ones
	if (isset($FILE_RECORD['EXTDB']))
	foreach($FILE_RECORD['EXTDB'] as &$EXTDB)
	{
		$FOUND=false;
		foreach ($DB_RECORD['EXTDB'] as &$DB_EXTDB)
		{
			if ($DB_EXTDB['source_origin_id']!=$SOURCE_ID)continue;
			if ($DB_EXTDB['source_id']!=$EXTDB['source'])continue;
			if ($DB_EXTDB['drug_extdb_value']!=$EXTDB['drug_extdb_value'])continue;
			echo ("\tMATCH\tEXTDB\t|".$EXTDB['source']."|\t|".$EXTDB['drug_extdb_value'].'|')."\n";
			$FOUND=true;
			$STATS['drug_ext_db:MATCH']++;
			$DB_EXTDB['DB_STATUS']='VALID';
		}
		if ($FOUND) continue;
		echo ("\t".$DB_RECORD['drugbank_id']."\tNEW\tEXTDB\t|".$EXTDB['source']."|\t|".$EXTDB['drug_extdb_value'].'|')."\n";
		
		++$DBIDS['drug_extdb'];
		$FILE_STATUS['drug_extdb']=1;
		$STATS['drug_ext_db:NEW']++;
		fputs($FILES['drug_extdb'],
			$DBIDS['drug_extdb']."\t".
			$DB_RECORD_ID."\t".
			$EXTDB['drug_extdb_value']."\t".
			$EXTDB['source']."\t".
			$SOURCE_ID."\n");
	}

	foreach ($DB_RECORD['EXTDB'] as &$DB_EXTDB)
	{
		if ($DB_EXTDB['DB_STATUS']=='VALID')continue;
		if ($DB_EXTDB['source_origin_id']!=$SOURCE_ID)continue;
		$STATS['drug_ext_db:DEL']++;
		echo ("\tDEL\tEXTDB\t".$DB_EXTDB['source_id'].'|'.$DB_EXTDB['drug_extdb_value'])."\n";
		$TO_DEL['drug_extdb'][]=$DB_EXTDB['drug_extdb_id'];
	}




	/////// NAMES

	$NAMES=array();
	if (isset($FILE_RECORD['ALT_NAME']))
	foreach($FILE_RECORD['ALT_NAME'] as &$ALT_NAME)
	{
		$FOUND=false;
		foreach ($DB_RECORD['NAME'] as &$DB_ALT_NAME)
		{
			
			if ($ALT_NAME['synonym']!=$DB_ALT_NAME['drug_name'])continue;
			$FOUND=true;
			echo ("\tMATCH\tNAME\t".$ALT_NAME['synonym'])."\n";
			$STATS['drug_name:MATCH']++;
			$DB_ALT_NAME['DB_STATUS']='VALID';
		}
		if ($FOUND) continue;
		
		if (isset($NAMES[$ALT_NAME['synonym']]))continue;
		$STATS['drug_name:NEW']++;

		$NAMES[$ALT_NAME['synonym']]=true;
		++$DBIDS['drug_name'];
		echo ("\tNEW\tNAME\t".$ALT_NAME['synonym'])."\n";
		$FILE_STATUS['drug_name']=1;
		fputs($FILES['drug_name'],
		$DBIDS['drug_name']."\t".
		$DB_RECORD_ID."\t\"".
		str_replace('"','""',$ALT_NAME['synonym'])."\"\tF\tF\t".
		$SOURCE_ID."\n");
	}
	foreach ($DB_RECORD['NAME'] as &$DB_ALT_NAME)
	{
		if ($DB_ALT_NAME['source_id']!=$SOURCE_ID)continue;
		if ($DB_ALT_NAME['DB_STATUS']=='VALID')continue;
		$STATS['drug_name:DEL']++;
		echo ("\tDEL\tNAME\t".$DB_ALT_NAME['drug_name'])."\n";
		$TO_DEL['drug_name'][]=$DB_ALT_NAME['drug_name_id'];
	}





	/// Formatting a little bit for simplification:
	$DESC_LIST=array(array('text_description'=>$FILE_RECORD['description'],'text_type'=>'Complete'),
				array('text_description'=>$FILE_RECORD['simple_description'],'text_type'=>'Simple'),
				array('text_description'=>$FILE_RECORD['clinical_description'],'text_type'=>'Clinical'));

	foreach ($DESC_LIST as &$DESC)
	{
		if ($DESC['text_description']=='')continue;
		$FOUND=false;
		/// Comparing to existing records
		foreach ($DB_RECORD['DESC'] as &$DB_DESC)
		{
			
			if ($DESC['text_description']!=$DB_DESC['text_description'])continue;
			if ($DESC['text_type']!=$DB_DESC['text_type'])continue;
			$FOUND=true;
			echo ("\tMATCH\tDESC\t".$DESC['text_type'])."\n";
			$DB_DESC['DB_STATUS']='VALID';
			$STATS['drug_desc:MATCH']++;
		}
		if ($FOUND) continue;
		$STATS['drug_desc:NEW']++;
		echo ("\tNEW\tDESC\t".$DB_RECORD_ID."\t".$DESC['text_type']."\t".$DESC['text_description'])."\n";
		++$DBIDS['drug_description'];
		$FILE_STATUS['drug_description']=1;
		fputs($FILES['drug_description'],
			$DBIDS['drug_description']."\t".
			$DB_RECORD_ID."\t".
			"\"".str_replace('"','""',$DESC['text_description'])."\""."\t".
			$DESC['text_type']."\t".
			$SOURCE_ID."\n");
	}
	foreach ($DB_RECORD['DESC'] as &$DB_DESC)
	{
		if ($DB_DESC['source_id']!=$SOURCE_ID)continue;
		if ($DB_DESC['DB_STATUS']=='VALID')continue;
		
		$STATS['drug_desc:DEL']++;
		echo ("DEL\tDESC\t".$DB_RECORD_ID."\t".$DB_DESC['text_type']."\t".$DB_DESC['text_description'])."\n";
		$TO_DEL['drug_description'][]=$DB_DESC['drug_description_id'];
	}






	if (isset($FILE_RECORD['TYPE']))
	foreach ($FILE_RECORD['TYPE'] as &$TYPE_R)
	{
		$FOUND=false;
		if (isset($DB_RECORD['TYPE']))
		foreach ($DB_RECORD['TYPE'] as &$DB_TYPE_R)
		{
			if ($TYPE_R!=$DB_TYPE_R['drug_type_id'])continue;
			$FOUND=true;
			$DB_TYPE_R['DB_STATUS']='VALID';
			$STATS['drug_type:MATCH']++;
			break;
			
		}
		if ($FOUND) continue;
		echo ("\tNEW\tTYPE\t".$DB_RECORD_ID."\tTYPE\t".$TYPE_R)."\n";
		$STATS['drug_type:NEW']++;
		++$DBIDS['drug_type_map'];
		$FILE_STATUS['drug_type_map']=1;
		fputs($FILES['drug_type_map'],$DBIDS['drug_type_map']."\t"
		.$DB_RECORD_ID."\t".$TYPE_R."\n");
	}






	if (isset($DB_RECORD['TYPE']))
	foreach ($DB_RECORD['TYPE'] as $DB_TYPE_R)
	{
		if ($DB_TYPE_R['DB_STATUS']=='VALID')continue;
		echo ("\tDEL\tTYPE\y".$DB_RECORD_ID."\tTYPE\t".$DB_TYPE_R['drug_type_id'])."\n";
		$STATS['drug_type:DEL']++;
		$TO_DEL['drug_type_map'][]=$DB_TYPE_R['drug_type_map_id'];
	}




	if (isset($FILE_RECORD['MAPPING']))
	foreach ($FILE_RECORD['MAPPING'] as &$MAPPING_R)
	{
		$FOUND=false;
		//// We are only interested in ATC and ATCvet
		if ($MAPPING_R['source']!='ATC' && $MAPPING_R['source']!='ATCvet')continue;

		/// We are going to compare the records
		if (isset($DB_RECORD['ATC']))
		foreach ($DB_RECORD['ATC'] as &$DB_MAPPING_R)
		{
			if ($ATC_CODES[$MAPPING_R['code']]!=$DB_MAPPING_R['atc_entry_id'])continue;
			$FOUND=true;
			$DB_MAPPING_R['DB_STATUS']='VALID';
			$STATS['drug_atc_map:MATCH']++;
			break;
			
		}
		if ($FOUND) continue;
		echo ("\tNEW\tATC\t".$DB_RECORD_ID."\tATC\t".$ATC_CODES[$MAPPING_R['code']])."\n";
		$STATS['drug_atc_map:NEW']++;
		++$DBIDS['drug_atc_map'];
		$FILE_STATUS['drug_atc_map']=1;
		fputs($FILES['drug_atc_map'],$DBIDS['drug_atc_map']."\t"
		.$DB_RECORD_ID."\t".$ATC_CODES[$MAPPING_R['code']]."\n");
	}





	if (isset($DB_RECORD['ATC']))
	foreach ($DB_RECORD['ATC'] as $ATC_DB)
	{
		if ($ATC_DB['DB_STATUS']=='VALID')continue;
		echo ("\tDEL\tATC\t".$DB_RECORD_ID."\tATC\t".$ATC_DB['atc_entry_id'])."\n";
		$STATS['drug_atc_map:DEL']++;
		$TO_DEL['drug_atc_map'][]=$ATC_DB['drug_atc_map_id'];
	}

	/// The following is only for small molecule drugs
	if ($FILE_RECORD['type']!='SmallMoleculeDrug')return;
	global $SM_SOURCE;
	
	if (isset($SM_SOURCE[$FILE_RECORD['drugbank_id']]))
	{
		$SM_ENTRY=&$SM_SOURCE[$FILE_RECORD['drugbank_id']];
		$MOL_ENTITY_DBID=$SM_ENTRY['DB_ID'];
		if (isset($DB_RECORD['SM'][$MOL_ENTITY_DBID]))
		{
			$DB_RECORD['SM'][$MOL_ENTITY_DBID]['DB_STATUS']='VALID';
			$STATS['drug_mol_entity_map:MATCH']++;
		}
		else 
		{
			echo ("\tNEW\tMOL_ENTITY\t".$DB_RECORD_ID."\tSM\t".$MOL_ENTITY_DBID)."\n";
			$STATS['drug_mol_entity_map:NEW']++;
			++$DBIDS['drug_mol_entity_map'];
			$FILE_STATUS['drug_mol_entity_map']=1;
			fputs($FILES['drug_mol_entity_map'],$DBIDS['drug_mol_entity_map']."\t".$DB_RECORD_ID."\t".$MOL_ENTITY_DBID."\t0\t".$SOURCE_ID."\n");
		}
	}
	

	foreach ($DB_RECORD['SM'] as $FILE_MOL_ENTITY_DBID=>&$SM_INFO)
	{
		if ($SM_INFO['source_id']!=$SOURCE_ID)continue;
		if ($SM_INFO['DB_STATUS']=='VALID')continue;
		$STATS['drug_mol_entity_map:DEL']++;
		echo ("\tDEL\tMOL_ENTITY\t".$DB_RECORD_ID."\tSM\t".$FILE_MOL_ENTITY_DBID)."\n";
		$TO_DEL['drug_mol_entity_map'][]=$SM_INFO['drug_mol_entity_map_id'];
	}


}






function loadDrugBank()
{
	addLog("Load DrugBank");
	global $FROM_FILES;
	global $BIOTECH_MAP;
	global $CLIN_INFO;
	
	
	$FROM_FILES=array();


	addLog("\tLoad Drugs.csv");
	$fp=fopen('drugs.csv','r');if (!$fp)								failProcess($JOB_ID.'L01','Unable to open drugbank  file');
	
	$HEAD=array_flip(fgetcsv($fp));
	while(!feof($fp))
	{
		/// Getting the record
		$tab=fgetcsv($fp);if ($tab===false)continue;
		$ENTRY=array();
		foreach ($HEAD as $K=>$V)$ENTRY[$K]=$tab[$V];
	  
		/// Clean up records
		$ENTRY['description']=str_replace("\r","",$ENTRY['description']);
		$ENTRY['simple_description']=str_replace("\r","",$ENTRY['simple_description']);
		$ENTRY['clinical_description']=str_replace("\r","",$ENTRY['clinical_description']);

		/// We are going to use the drugbank_id as a key
		$FROM_FILES[$ENTRY['id']]=$ENTRY;
		$FROM_FILES[$ENTRY['id']]['ALT_NAME'][]=array('synonym'=>$ENTRY['name']);
	
	}
	fclose($fp);





	addLog("\tLoad Drugs synonyms");
	$fp=fopen('drug_synonyms.csv','r');if (!$fp)								failProcess($JOB_ID.'L02','Unable to open drugbank synonyms file');
	
	$HEAD=array_flip(fgetcsv($fp));
	while(!feof($fp))
	{

		$tab=fgetcsv($fp);if ($tab===false)continue;
		$ENTRY=array();
		
		if (count($tab)!=7)continue;

		foreach ($HEAD as $K=>$V)$ENTRY[$K]=str_replace("\t","",$tab[$V]);
		$FROM_FILES[$ENTRY['drug_id']]['ALT_NAME'][]=$ENTRY;
		
	}
	fclose($fp);





	addLog("\tLoad External identifiers");
	$fp=fopen('external_identifiers.csv','r');if (!$fp)								failProcess($JOB_ID.'L03','Unable to open drugbank external identifiers file');
	
	$HEAD=array_flip(fgetcsv($fp));
	while(!feof($fp))
	{

	    $tab=fgetcsv($fp);if ($tab===false)continue;
		//echo implode("\t",$tab)."\n";
	    $ENTRY=array();
	    foreach ($HEAD as $K=>$V)$ENTRY[$K]=str_replace("\t","",$tab[$V]);
		
		///Removing the | at the end of the identifier
	    if (substr($ENTRY['identifier|'],strlen($ENTRY['identifier|'])-1)=='|')$ENTRY['identifier|']=substr($ENTRY['identifier|'],0,-1);
		//echo $ENTRY['drug_id']."\t".$ENTRY['identifier|']."\n";
	    $FROM_FILES[$ENTRY['drug_id']]['ALT_NAME'][]=array('synonym'=>$ENTRY['identifier|']);
		
	}
	fclose($fp);




	addLog("\tLoad External resources identifiers");
	$fp=fopen('external_resource_identifiers.csv','r');if (!$fp)								failProcess($JOB_ID.'L04','Unable to open drugbank external resource identifiers file');
	$HEAD=array_flip(fgetcsv($fp));
	while(!feof($fp))
	{

	    $tab=fgetcsv($fp);if ($tab===false)continue;
		//echo implode("\t",$tab)."\n";
	    $ENTRY=array();
	    foreach ($HEAD as $K=>$V)$ENTRY[$K]=str_replace("\t","",$tab[$V]);
		
		if ($ENTRY['record_type']!='Drug')continue;
	    
		///Removing the | at the end of the identifier
		if (substr($ENTRY['identifier|'],strlen($ENTRY['identifier|'])-1)=='|')$ENTRY['identifier|']=substr($ENTRY['identifier|'],0,-1);
		
		//echo $ENTRY['drug_id']."\t".$ENTRY['identifier|']."\n";
	    
		$FROM_FILES[$ENTRY['record_id']]['EXTDB'][]=array('source'=>getSource($ENTRY['source']), 'drug_extdb_value'=>$ENTRY['identifier|']);
		
		if ($ENTRY['source']=='ChEMBL')$FROM_FILES[$ENTRY['record_id']]['chembl_id']=$ENTRY['identifier|'];
		
	}
	fclose($fp);




	addLog("\tLoad Drug mapping");
	$fp=fopen('drug_mappings.csv','r');if (!$fp)								failProcess($JOB_ID.'L05','Unable to open drugbank mapping file');
	
	$HEAD=array_flip(fgetcsv($fp));
	while(!feof($fp))
	{

	    $tab=fgetcsv($fp);if ($tab===false)continue;
		//echo implode("\t",$tab)."\n";
	    $ENTRY=array();
	    foreach ($HEAD as $K=>$V)$ENTRY[$K]=str_replace("\t","",$tab[$V]);
		$FROM_FILES[$ENTRY['drug_id']]['MAPPING'][]=array('source'=>$ENTRY['vocabulary'], 'code'=>$ENTRY['code']);
		
	}
	fclose($fp);

	addLog("\tLoad Biotech categories");
	$fp=fopen('drug_biotech_categories.csv','r');if (!$fp)								failProcess($JOB_ID.'L06','Unable to open drugbank biotech categories file');
	$tab=fgetcsv($fp);
	while (!feof($fp))
	{
		$tab=fgetcsv($fp);if ($tab===false)continue;
		if (count($tab)!=2)continue;
		$FROM_FILES[$tab[0]]['TYPE'][]=$BIOTECH_MAP[substr($tab[1],0,-1)];
	}
	fclose($fp);
	
	$CHEMBL_SOURCE=getSource("ChEMBL");
	foreach ($FROM_FILES as $DRUG_ID=>&$ENTRY)
	{
		if ($ENTRY['type']=='SmallMoleculeDrug')$ENTRY['TYPE'][]=$BIOTECH_MAP['Small molecule'];
		if (isset($ENTRY['EXTDB']))
		{
			$CHEMBL_ID='NULL';
			$FOUND=false;
			foreach ($ENTRY['EXTDB'] as &$EXTDB_R)
			{
				if ($EXTDB_R['source']!=$CHEMBL_SOURCE)continue;
				if ($FOUND) failProcess($JOB_ID."010",'Multiple ChEMBL IDs for '.$ENTRY['drugbank_id']);
				$CHEMBL_ID=$EXTDB_R['drug_extdb_value'];
				
				$FOUND=true;
			}
			$ENTRY['chembl_id']=$CHEMBL_ID;
		}else $ENTRY['chembl_id']='NULL';
		$MAX_CLIN_PHASE='N/A';if (isset($CLIN_INFO['DRUG'][$ENTRY['id']]))$MAX_CLIN_PHASE=$CLIN_INFO['DRUG'][$ENTRY['id']];
		$ENTRY['MAX_CLIN_PHASE']=$MAX_CLIN_PHASE;
	}
	
	addLog("\tEnd Loading");
}






?>