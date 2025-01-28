<?php

/**
 SCRIPT NAME: db_ot_drug
 PURPOSE:     Process Drug related information from Open Targets
 
*/
ini_set('memory_limit','1000M');
error_reporting(E_ALL);


/// Job name - Do not change
$JOB_NAME='db_ot_drug';


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


$GLOBAL_MAP=array();

addLog("Check directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_ot_rel')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];


addLog("Working directory: ".$W_DIR);	

	if (!is_dir('STD') && !mkdir('STD'))												failProcess($JOB_ID."005",'Unable to create STD');
	if (!is_dir('INSERT') && !mkdir('INSERT'))											failProcess($JOB_ID."006",'Unable to create INSERT');


	/// Get the source: OpenTargets
	$SOURCE_ID=getSource('OpenTargets');

	/// Check if Drugbank is enabled
	$DRUGBANK_INFO=$GLB_TREE[getJobIDByName('dl_drugbank')];
	$DRUGBANK_ENABLED=($DRUGBANK_INFO['ENABLED']=='T');
	

addLog("Process drugs");
	$STATUS_CODE=array('NO_CPD_MATCH'=>0,
	'CPD_SEARCH_FAILED'=>0,
	'SUCCESS_CPD'=>0,
	'NOT_SM'=>0,
	'DRUG_SEARCH_FAILED'=>0,
	'NEW_DRUG'=>0,
	'TARGET_SEARCH_FAILED'=>0, 
	'drug_entry_UPDATE_FAILED'=>0,
	'drug_entry_UPDATED'=>0,
	'DRUG_INSERT_FAILED'=>0,
	'NEW_drug_name'=>0,
	'NEW_DRUG_DISEASE'=>0,
	'DISEASE_SEARCH_FAILED'=>0,
	'DRUG_DISEASE_INSERT_FAILED'=>0);
	

	processMolecularStructure();


	$MAPPING_RULES=array();
	$fp=fopen($TG_DIR.'/BACKEND/STATIC_DATA/DRUG/DRUGBANK_CHEMBL.map','r'); 
	if (!$fp)																		failProcess($JOB_ID."007",'Unable to open DRUGBANK_CHEMBL.map');
	$line=stream_get_line($fp,10000,"\n");
	$head=array_values(array_filter(explode("\t",$line)));
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=array_values(array_filter(explode("\t",$line)));
		if (count($tab)!=count($head))													failProcess($JOB_ID."008",'Not the same number of columns - Did you use spaces?');

		$ENTRY=array_combine($head,$tab);
		
		$MAPPING_RULES[]=$ENTRY;
	}
	fclose($fp);
	




addLog("Preparation step");

	// We will record how many changes we have done to each table
	$STATS=array('drug_ext_db:MATCH'=>0,'drug_ext_db:NEW'=>0,'drug_ext_db:DEL'=>0,
				'drug_name:MATCH'=>0,'drug_name:NEW'=>0,'drug_name:DEL'=>0,
				'drug_desc:MATCH'=>0,'drug_desc:NEW'=>0,'drug_desc:DEL'=>0,
				'drug_type:MATCH'=>0,'drug_type:NEW'=>0,'drug_type:DEL'=>0,
				'drug_mol_entity_map:MATCH'=>0,'drug_mol_entity_map:NEW'=>0,'drug_mol_entity_map:DEL'=>0,'drug_entry:NEW'=>0,
				'drug_atc_map:MATCH'=>0,'drug_atc_map:NEW'=>0,'drug_atc_map:DEL'=>0);

	// This will contain all the records from the database that we didn't find in the same, so we can delete them
	$TO_DEL=array('drug_entry'=>array(),'drug_name'=>array(),'drug_target'=>array(),
					'drug_extdb'=>array(),'drug_mol_entity_map'=>array(),'drug_description'=>array(),
					'drug_type_map'=>array(),'drug_atc_map'=>array());			

	// Max primary key values for each table
	$DBIDS=array
		('drug_entry'=>-1,
		'drug_name'=>-1,
		'drug_extdb'=>-1,
		'drug_mol_entity_map'=>-1,
		'drug_description'=>-1,
		'drug_type_map'=>-1,
		'drug_atc_map'=>-1);

	// This will contain the order of the columns for the COPY command
	$COL_ORDER=array(
		'drug_entry'=>'(drug_entry_id, is_approved, is_withdrawn, max_clin_phase, drug_primary_name, is_experimental, is_investigational, is_nutraceutical, is_illicit, is_vet_approved,drugbank_id,chembl_id)',
		'drug_name'=>'(drug_name_id,drug_entry_id,drug_name,is_primary,is_tradename,source_id)',
		'drug_extdb'=>'(drug_extdb_id,drug_entry_id,drug_extdb_value,source_id,source_origin_id)',
		'drug_description'=>'(drug_description_id,drug_entry_id,text_description,text_type,source_id)',
		'drug_mol_entity_map'=>'(drug_mol_entity_map_id,drug_entry_id,molecular_entity_id,is_preferred,source_id)',
		'drug_type_map'=>'(drug_type_map_id,drug_entry_id,drug_type_id)',
		'drug_atc_map'=>'(drug_atc_map_id,drug_entry_id,atc_entry_id)'
		);


	/// So first, we are going to get the max Primary key values for each of those tables for faster insert.
	/// FILE_STATUS will tell us for each file if we need to trigger the data insertion or  not
	$FILE_STATUS=array();
	/// FILES will be the file handlers for each of the files we are going to insert into
	$FILES=array();
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) CO FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."009",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
		$FILE_STATUS[$TBL]=0;
		$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');if (!$FILES[$TBL])				failProcess($JOB_ID."010",'Unable to open file '.$TBL.'.csv');
	}


addLog("Get source");
	$SOURCE_ID=getSource('OpenTargets');


addLog("Load data");


	$FROM_FILES=array();	// This will contain all the records from the files
	$FROM_DB=array();		// This will contain all the records from the DB
	$MOL_SOURCE=array();	// This will contain all the molecules from the DB

	loadFromDB();			// We load all the records from the DB
	loadOpenTargetsDrug();			// We load all the records from the files
	compareRecords();		// We compare the records from the files to the records from the DB
	pushToDB(true);			// We push the records to the DB

	print_R($STATS);		// We print the stats

successProcess();		// We are done












function processMolecularStructure()
{
	global $SOURCE_ID;
	global $GLB_VAR;

	addLog("Update sm_source");

	if (!runQueryNoRes("UPDATE sm_source 
						set sm_name_Status='F' 
						where sm_source_id=".$SOURCE_ID))								failProcess($JOB_ID.'A01','Unable to update sm_source');
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("UPDATE ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source 
				SET sm_name_status='F' 
				WHERE source_id = ".$SOURCE_ID)) 										failProcess($JOB_ID."A02",'Unable to update   source');
	}


	// We are going to store all the molecule structures in chembl.smi
	$fpO=fopen('STD/molecule.smi','w');if (!$fpO)										failProcess($JOB_ID."A03",'Unable to open STD/molecule.smi');
	/// And all the counterions first in counterion_map then in counterion.smi
	$COUNTERION_MAP=array();
	$fpC=fopen('STD/counterion.smi','w');if (!$fpC)										failProcess($JOB_ID."A04",'Unable to open STD/counterion.smi');

	/// We read the file
	$fp=fopen('molecules.json','r');if (!$fp)											failProcess($JOB_ID."A05",'Unable to open molecules.json');

	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		$ENTRY=json_decode($line,true);
		if ($ENTRY===false)																failProcess($JOB_ID."A06",'Unable to read json string');
		if (!isset($ENTRY['canonicalSmiles']))continue;
		$tabS=explode(".",$ENTRY['canonicalSmiles']);
		/// We are going to consider the longuest SMILES string as the primary molecule and the rest as counterions
		/// counterions are standardized, but follow another manual process
		$MAX_LEN=0;
		foreach ($tabS as $t)$MAX_LEN=max($MAX_LEN,strlen($t));
		$ALT=array();
		$SMI='';
		/// The longest SMILES string is the primary molecule
		foreach ($tabS as $t)if (strlen($t)==$MAX_LEN)$SMI=$t;else $ALT[]=$t;

		/// The rest are counterions, sorted alphabetically
		sort($ALT);if ($ALT==array())$ALT[]='NULL';

		/// We store the primary molecule in molecule.smi
		fputs($fpO,$ENTRY['canonicalSmiles'].' '.$ENTRY['id']."|NULL|".$ENTRY['inchiKey']."|".implode(".",$ALT)."|".$SMI."\n");
		

		
		/// We construct a string that will be used as a key in the counterion map
		/// So that all counterions are uniquely defined.
		$STR_C=implode(".",$ALT);
		if ($STR_C!='NULL')$COUNTERION_MAP[$STR_C.' '.$STR_C]='';
		//break;
	}
	fclose($fp);	

	/// We are going to store all the counterions in counterion.smi
	fputs($fpC,implode("\n",array_keys($COUNTERION_MAP)))."\n";

	/// We don't need the array anymore
	unset($COUNTERION_MAP);

	fclose($fpO);
	fclose($fpC);

	standardizeCompounds(true);


	/// Now that all existing names have their status updated, the remaining ones are obsolete
	if (!runQueryNoRes("DELETE FROM sm_source 
						WHERE sm_name_Status='F' 
						AND source_Id = ".$SOURCE_ID))										failProcess($JOB_ID.'A07','Unable to delete sm_source');
	
	
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("DELETE FROM ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source 
						WHERE sm_name_status='F'
						 AND source_id = ".$SOURCE_ID)) 									failProcess($JOB_ID."A08",'Unable to update sm_source');
	}

}







function compareDrugBankCrossRef(&$FILE_RECORD,&$POS_RECORD,&$FROM_DB_MAP,&$DRUGBANK_ID_MAP)
{
	global $MAPPING_RULES;
	$FOUND_MAP=array();
	foreach ($FILE_RECORD['id'] as $chembl_id)
	{
		foreach ($MAPPING_RULES as &$ENTRY)
		{
			//print_R($ENTRY);
			
			if ((($ENTRY['REF_CHEMBL_ID']==$chembl_id && $ENTRY['REF_CHEMBL_ID']!='N/A')
			||  ($ENTRY['ALT_CHEMBL_ID']==$chembl_id && $ENTRY['ALT_CHEMBL_ID']!='N/A'))
			&& $ENTRY['REF_DRUGBANK_ID']!='N/A')
			{
				$DB_ID=$ENTRY['REF_DRUGBANK_ID'];
				if (!isset($DRUGBANK_ID_MAP[$DB_ID]))continue;
				if (isset($FOUND_MAP[$DB_ID]))continue;
				$FOUND_MAP[$DB_ID]=true;
				$DB_REC=$DRUGBANK_ID_MAP[$DB_ID];
				$FROM_DB_REC=&$FROM_DB_MAP[$DB_REC];
				if (!isset($FROM_DB_REC[$POS_RECORD]))
				$FROM_DB_REC[$POS_RECORD]=array('SCORE'=>19,'RULE'=>array('db_id'=>true));
				else
				{
					$FROM_DB_REC[$POS_RECORD]['SCORE']+=19;
					$FROM_DB_REC[$POS_RECORD]['RULE']['db_id']=true;
				}
			}
			
		}
	}

	if (!isset($FILE_RECORD['crossReferences']['drugbank']))return;
	
		
	foreach ($FILE_RECORD['crossReferences']['drugbank'] as $DB_ID=>&$DUMMY)
	{
		if (!isset($DRUGBANK_ID_MAP[$DB_ID]))continue;
		if (isset($FOUND_MAP[$DB_ID]))continue;
		$FOUND_MAP[$DB_ID]=true;
		$DB_REC=$DRUGBANK_ID_MAP[$DB_ID];
		$FROM_DB_REC=&$FROM_DB_MAP[$DB_REC];
		if (!isset($FROM_DB_REC[$POS_RECORD]))
		$FROM_DB_REC[$POS_RECORD]=array('SCORE'=>19,'RULE'=>array('db_id'=>true));
		else
		{
			$FROM_DB_REC[$POS_RECORD]['SCORE']+=19;
			$FROM_DB_REC[$POS_RECORD]['RULE']['db_id']=true;
		}
	}
	
}

function compareChemblId(&$FILE_RECORD,&$POS_RECORD,&$FROM_DB_MAP,&$CHEMBL_ID_MAP)
{
	foreach ($FILE_RECORD['id'] as $chembl_id)
	{
		if (!isset($CHEMBL_ID_MAP[$chembl_id]))continue;
		
		if (!isset($FROM_DB_MAP[$CHEMBL_ID_MAP[$chembl_id]][$POS_RECORD]))
		$FROM_DB_MAP[$CHEMBL_ID_MAP[$chembl_id]][$POS_RECORD]=array('SCORE'=>19,'RULE'=>array('chembl_id'=>$chembl_id));
		
		else
		{
			$FROM_DB_MAP[$CHEMBL_ID_MAP[$chembl_id]][$POS_RECORD]['SCORE']+=19;
			$FROM_DB_MAP[$CHEMBL_ID_MAP[$chembl_id]][$POS_RECORD]['RULE']['chembl_id']=$chembl_id;
		} 

		
	}
}

function compareMolId($FILE_RECORD,$POS_RECORD,$FROM_DB_MAP,$SM_MOL_MAP,$MOL_SOURCE){
		
	/// STEP 2 - We match by chembl_id


	/// STEP 3- We match by sm_molecule_id
	foreach ($FILE_RECORD['id'] as $chembl_id)
	{
		if (!isset($MOL_SOURCE[$chembl_id]))continue;
		
		$MOL_ID=$MOL_SOURCE[$chembl_id]['DB_ID'];
		if (!isset($SM_MOL_MAP[$MOL_ID]))continue;
		if (count($SM_MOL_MAP[$MOL_ID])>1)	continue;
		
		$DB_ID=$SM_MOL_MAP[$MOL_ID][0];
		
		if (!isset($FROM_DB_MAP[$DB_ID][$POS_RECORD]))
		{
		$FROM_DB_MAP[$DB_ID][$POS_RECORD]=array('SCORE'=>10,'RULE'=>array('mol_id'=>$MOL_ID));
		}
		else 
		{
			$FROM_DB_MAP[$DB_ID][$POS_RECORD]['SCORE']+=10;
			$FROM_DB_MAP[$DB_ID][$POS_RECORD]['RULE']['mol_id']=$MOL_ID;
		}
		
	}
}

function comparePrimName(&$FILE_RECORD,&$POS_RECORD,&$FROM_DB_MAP,&$NAME_MAP)
{
		
		
	if (isset($NAME_MAP[strtolower($FILE_RECORD['name'])]))
	{
		
		foreach ($NAME_MAP[strtolower($FILE_RECORD['name'])] as $DBID=>&$DUMMY)
		{
			
			if (!isset($FROM_DB_MAP[$DBID][$POS_RECORD]))
			{
				$FROM_DB_MAP[$DBID][$POS_RECORD]=array('SCORE'=>8,'RULE'=>array('PRIM_NAME'=>array($FILE_RECORD['name'])));
			}
			else 
			{
				$FROM_DB_MAP[$DBID][$POS_RECORD]['SCORE']+=8;
				$FROM_DB_MAP[$DBID][$POS_RECORD]['RULE']['PRIM_NAME'][]=$FILE_RECORD['name'];
			}
		}
	}
}
function  compareSynonyms(&$FILE_RECORD,&$POS_RECORD,&$FROM_DB_MAP,&$SYN_MAP)
{
if (isset($FILE_RECORD['synonyms']))
foreach ($FILE_RECORD['synonyms'] as $S=>$RULE_NAME)
{
	if ($RULE_NAME[0]=='T')continue;
	$S=strtolower($S);
	if (!isset($SYN_MAP[$S]))continue;
	
	if (count($SYN_MAP[$S])>1)continue;
	$DB_ID=array_keys($SYN_MAP[$S])[0];
	if (!isset($FROM_DB_MAP[$DB_ID][$POS_RECORD]))
	{
		$FROM_DB_MAP[$DB_ID][$POS_RECORD]=array('SCORE'=>6,'RULE'=>array('NAME'=>array($S)));
	}
	else 
	{
		$FROM_DB_MAP[$DB_ID][$POS_RECORD]['SCORE']+=6;
		$FROM_DB_MAP[$DB_ID][$POS_RECORD]['RULE']['NAME'][]=$S;
	}
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
	global $DRUGBANK_ENABLED;
	$DB_RECORD=$FROM_DB[$DB_RECORD_ID];
	
	echo "COMPARE RECORD\t".$FILE_RECORD['id'][0]."\t".$DB_RECORD['chembl_id']."||".$DB_RECORD['drugbank_id']."\n";
	// exit;
	$IS_DRUGBANK_REC=false;
	if ($DB_RECORD['drugbank_id']!='')$IS_DRUGBANK_REC=true;


	$QUERY='UPDATE drug_Entry SET ';
	$TO_UPDATE=false;
	$MAP=array(1=>'T',0=>'F',''=>'F');
	if (!isset($FILE_RECORD['isApproved']))$FILE_RECORD['isApproved']='';
	if (!isset($FILE_RECORD['hasBeenWithdrawn']))$FILE_RECORD['hasBeenWithdrawn']='';
	 if (!$IS_DRUGBANK_REC)
	 {
		if ($DB_RECORD['is_approved']!=$MAP[$FILE_RECORD['isApproved']])
		{
			addLog($DB_RECORD['chembl_id']."\tUPDATE\tis_approved\t".$DB_RECORD['is_approved'].'>'.$MAP[$FILE_RECORD['isApproved']]);
			$QUERY.='is_approved=\''.$MAP[$FILE_RECORD['isApproved']].'\', '; 
			$TO_UPDATE=true;
		}
	 	if ($DB_RECORD['is_withdrawn']!=$MAP[$FILE_RECORD['hasBeenWithdrawn']])
		{
			addLog($DB_RECORD['chembl_id']."\tUPDATE\tis_withdrawn\t".$DB_RECORD['is_withdrawn'].'>'.$MAP[$FILE_RECORD['hasBeenWithdrawn']]);
			$QUERY.='is_withdrawn=\''.$MAP[$FILE_RECORD['hasBeenWithdrawn']].'\', '; 
			$TO_UPDATE=true;
		}
	 	if (isset($FILE_RECORD['maximumClinicalTrialPhase'])&& $FILE_RECORD['maximumClinicalTrialPhase']!=-1
		 &&$DB_RECORD['max_clin_phase']!=$FILE_RECORD['maximumClinicalTrialPhase'])
		 {

			addLog($DB_RECORD['chembl_id']."\tUPDATE\tmax_clin_phase\t".$DB_RECORD['max_clin_phase'].'>'.$FILE_RECORD['maximumClinicalTrialPhase']);
			$QUERY.='max_clin_phase=\''.$FILE_RECORD['maximumClinicalTrialPhase'].'\', '; 
			$TO_UPDATE=true;
		}
	 	if ($DB_RECORD['drug_primary_name']!=$FILE_RECORD['name'])
		{
			addLog($DB_RECORD['chembl_id']."\tUPDATE\tdrug_primary_name\t".$DB_RECORD['drug_primary_name'].'>'.$FILE_RECORD['name']);
			$QUERY.='drug_primary_name=\''.$FILE_RECORD['name'].'\', '; 
			$TO_UPDATE=true;
		}
	 }
	 else
	 {
		if (isset($FILE_RECORD['maximumClinicalTrialPhase'])&& $FILE_RECORD['maximumClinicalTrialPhase']!=-1)
		{ 

			if ($DB_RECORD['max_clin_phase']=='N/A' ){
				addLog($DB_RECORD['drugbank_id']."\tUPDATE\tmax_clin_phase\t".$DB_RECORD['max_clin_phase'].'>'.$FILE_RECORD['maximumClinicalTrialPhase']);
				$QUERY.='max_clin_phase=\''.$FILE_RECORD['maximumClinicalTrialPhase'].'\', '; $TO_UPDATE=true;}
			else if ($DB_RECORD['max_clin_phase']<$FILE_RECORD['maximumClinicalTrialPhase'])
			{
				addLog($DB_RECORD['drugbank_id']."\tUPDATE\tmax_clin_phase\t".$DB_RECORD['max_clin_phase'].'>'.$FILE_RECORD['maximumClinicalTrialPhase']);
				$QUERY.='max_clin_phase=\''.$FILE_RECORD['maximumClinicalTrialPhase'].'\', '; 
				$TO_UPDATE=true;}
	 	}
	 }
	
	if ($TO_UPDATE)
	{
		$QUERY=substr($QUERY,0,-2);
		$QUERY.=' WHERE drug_entry_id='.$DB_RECORD_ID;
		// print_R($FILE_RECORD);
		// print_r($DB_RECORD);
echo $QUERY."\n";
		$res=runQueryNoRes($QUERY);
		if ($res===false)failProcess($JOB_ID."010",'Unable to update drug_entry table');
	}
	
	foreach ($FILE_RECORD['id'] as $ID)
	{
		
	$FILE_RECORD['crossReferences']['ChEMBL'][$ID][0]=true;
	}
	$EXTDB_VAL=array();
	 if (isset($FILE_RECORD['crossReferences']))
	foreach($FILE_RECORD['crossReferences'] as $SOURCE_NAME=>&$LIST_ID)
	{
		
		$SOURCE_EXTID=getSource($SOURCE_NAME);
		foreach ($LIST_ID as $ID=>$STATUS)
		{
			$FOUND=false;
			foreach ($DB_RECORD['EXTDB'] as &$DB_EXTDB)
			{
				if ($DB_EXTDB['source_origin_id']!=$SOURCE_ID)continue;
				if ($DB_EXTDB['source_id']!=$SOURCE_EXTID)continue;
				if ($DB_EXTDB['drug_extdb_value']!=$ID)continue;
				$FOUND=true;
				$STATS['drug_ext_db:MATCH']++;
				$DB_EXTDB['DB_STATUS']='VALID';
			}
			if ($FOUND) continue;
			if (isset($EXTDB_VAL[$SOURCE_NAME][$ID]))continue;
			$EXTDB_VAL[$SOURCE_NAME][$ID]=true;
			addLog($DB_RECORD['drugbank_id'].'|'.$DB_RECORD['chembl_id']."\tNEW\tEXTDB\t|".$SOURCE_EXTID."|\t|".$ID.'|');
		
			++$DBIDS['drug_extdb'];
			$FILE_STATUS['drug_extdb']=1;
			$STATS['drug_ext_db:NEW']++;
			fputs($FILES['drug_extdb'],$DBIDS['drug_extdb']."\t".$DB_RECORD_ID."\t".$ID."\t".$SOURCE_EXTID."\t".$SOURCE_ID."\n");
		}
	}
	foreach ($DB_RECORD['EXTDB'] as &$DB_EXTDB)
	{
		if ($DB_EXTDB['DB_STATUS']=='VALID')continue;
		if ($DB_EXTDB['source_origin_id']!=$SOURCE_ID)continue;
		$STATS['drug_ext_db:DEL']++;
		echo $DB_RECORD['drugbank_id'].'|'.$DB_RECORD['chembl_id']."\tDEL:|".$SOURCE_NAME.'|'.$ID."|\n";
		$TO_DEL['drug_extdb'][]=$DB_EXTDB['drug_extdb_id'];
	}

	
	$NAMES=array();
	if (isset($FILE_RECORD['synonyms']))
	foreach($FILE_RECORD['synonyms'] as $ALT_NAME=>$RULE_NAME)
	{
		$FOUND=false;
		foreach ($DB_RECORD['NAME'] as &$DB_ALT_NAME)
		{
			if ($DB_ALT_NAME['source_id']!=$SOURCE_ID)continue;
			if ($ALT_NAME!=$DB_ALT_NAME['drug_name'])continue;
			if ($RULE_NAME[0]!=$DB_ALT_NAME['is_primary'])continue;
			if ($RULE_NAME[1]!=$DB_ALT_NAME['is_tradename'])continue;
			$FOUND=true;
			$STATS['drug_name:MATCH']++;
			$DB_ALT_NAME['DB_STATUS']='VALID';
		}
		if ($FOUND) continue;
		
		if (isset($NAMES[$ALT_NAME]))continue;
		$STATS['drug_name:NEW']++;

		$NAMES[$ALT_NAME]=true;
		++$DBIDS['drug_name'];
		addLog($DB_RECORD['drugbank_id'].'|'.$DB_RECORD['chembl_id']."\tNEW\tNAME\t".$ALT_NAME);
		$FILE_STATUS['drug_name']=1;
		fputs($FILES['drug_name'],
		$DBIDS['drug_name']."\t".
		$DB_RECORD_ID."\t\"".
		str_replace('"','""',$ALT_NAME)."\"\t".$RULE_NAME[0]."\t".$RULE_NAME[1]."\t".
		$SOURCE_ID."\n");
	}
	foreach ($DB_RECORD['NAME'] as &$DB_ALT_NAME)
	{
		if ($DB_ALT_NAME['source_id']!=$SOURCE_ID)continue;
		if ($DB_ALT_NAME['DB_STATUS']=='VALID')continue;
		$STATS['drug_name:DEL']++;
		$TO_DEL['drug_name'][]=$DB_ALT_NAME['drug_name_id'];
	}

	
	if (isset($FILE_RECORD['description']))
	{
		$FOUND=false;
		foreach ($DB_RECORD['DESC'] as &$DB_DESC)
		{
			if ($DB_DESC['source_id']!=$SOURCE_ID)continue;
			if ($FILE_RECORD['description']!=$DB_DESC['text_description'])continue;
			$FOUND=true;
			$DB_DESC['DB_STATUS']='VALID';
			$STATS['drug_desc:MATCH']++;
		}
		if (!$FOUND)
		{
			$STATS['drug_desc:NEW']++;
			addLog($DB_RECORD['drugbank_id'].'|'.$DB_RECORD['chembl_id']."\tNEW\t".$DB_RECORD_ID."\tSummary\t".$FILE_RECORD['description']);
			++$DBIDS['drug_description'];
			$FILE_STATUS['drug_description']=1;
			fputs($FILES['drug_description'],$DBIDS['drug_description']."\t".$DB_RECORD_ID."\t\"".str_replace('"','""',$FILE_RECORD['description'])."\"\tSummary\t".$SOURCE_ID."\n");
		}
	}
	foreach ($DB_RECORD['DESC'] as &$DB_DESC)
	{
		if ($DB_DESC['source_id']!=$SOURCE_ID)continue;
		if ($DB_DESC['DB_STATUS']=='VALID')continue;
		$STATS['drug_desc:DEL']++;
		addLog("DEL\t".$DB_RECORD_ID."\t".$DB_DESC['text_type']."\t".$DB_DESC['text_description']);
		$TO_DEL['drug_description'][]=$DB_DESC['drug_description_id'];
	}


	if (isset($FILE_RECORD['drugType'])&& $FILE_RECORD['drugType']=='Small molecule')
	{
		
		global $MOL_SOURCE;
		$ADDED=array();
		if (isset($FILE_RECORD['CPD']))
		foreach ($FILE_RECORD['CPD'] as &$FILE_SM)
		{
			//print_R($FILE_SM);
			$ID=$FILE_SM['id'];
			if (is_array($ID))
			{
				if (count($FILE_SM['id'])>1)continue;
				
				$ID=$FILE_SM['id'][0];
			}
			$SM_ENTRY=&$MOL_SOURCE[$ID];
			if (!isset($SM_ENTRY['DB_ID']))	continue;
			
			$MOL_ENTITY_DBID=$SM_ENTRY['DB_ID'];
			$FOUND=false;
			
			if (isset($DB_RECORD['SM'])) 
			{
				
				foreach ($DB_RECORD['SM'] as &$DB_SM)
				{
					
					//if ($DB_SM['source_id']!=$SOURCE_ID)continue;
					if ($MOL_ENTITY_DBID!=$DB_SM['molecular_entity_id'])continue;
					$FOUND=true;
					//echo "FOUND\n";
					$DB_SM['DB_STATUS']='VALID';
					$STATS['drug_mol_entity_map:MATCH']++;
					break;
				}
				
			}
			if ($FOUND) continue;
			if (isset($ADDED[$MOL_ENTITY_DBID]))continue;
			$ADDED[$MOL_ENTITY_DBID]=true;
			addLog("NEW\t".$DB_RECORD_ID."\tSM\t".$MOL_ENTITY_DBID);
			$STATS['drug_mol_entity_map:NEW']++;
			++$DBIDS['drug_mol_entity_map'];
			$FILE_STATUS['drug_mol_entity_map']=1;
			fputs($FILES['drug_mol_entity_map'],$DBIDS['drug_mol_entity_map']."\t".$DB_RECORD_ID."\t".$MOL_ENTITY_DBID."\t0\t".$SOURCE_ID."\n");
		}

		if (isset($DB_RECORD['SM']))
		foreach ($DB_RECORD['SM'] as &$SM_INFO)
		{
			if ($SM_INFO['source_id']!=$SOURCE_ID)continue;
			if ($SM_INFO['DB_STATUS']=='VALID')continue;
			
			$STATS['drug_mol_entity_map:DEL']++;
			addLog("DEL\t".$DB_RECORD_ID."\tSM\t".$SM_INFO['sm_entry_id']);
			$TO_DEL['drug_mol_entity_map'][]=$SM_INFO['drug_mol_entity_map_id'];
		}
	}

	//print_R($FILE_RECORD);exit;
	// if (isset($FILE_RECORD['TYPE']))
	// foreach ($FILE_RECORD['TYPE'] as &$TYPE_R)
	// {
	// 	$FOUND=false;
	// 	if (isset($DB_RECORD['TYPE']))
	// 	foreach ($DB_RECORD['TYPE'] as &$DB_TYPE_R)
	// 	{
	// 		if ($TYPE_R!=$DB_TYPE_R['drug_type_id'])continue;
	// 		$FOUND=true;
	// 		$DB_TYPE_R['DB_STATUS']='VALID';
	// 		$STATS['drug_type:MATCH']++;
	// 		break;
			
	// 	}
	// 	if ($FOUND) continue;
	// 	addLog("NEW\t".$DB_RECORD_ID."\tTYPE\t".$TYPE_R);
	// 	$STATS['drug_type:NEW']++;
	// 	++$DBIDS['drug_type_map'];
	// 	$FILE_STATUS['drug_type_map']=1;
	// 	fputs($FILES['drug_type_map'],$DBIDS['drug_type_map']."\t"
	// 	.$DB_RECORD_ID."\t".$TYPE_R."\n");
	// }
	// if (isset($DB_RECORD['TYPE']))
	// foreach ($DB_RECORD['TYPE'] as $DB_TYPE_R)
	// {
	// 	if ($DB_TYPE_R['DB_STATUS']=='VALID')continue;
	// 	addLog("DEL\t".$DB_RECORD_ID."\tTYPE\t".$DB_TYPE_R['drug_type_id']);
	// 	$STATS['drug_type:DEL']++;
	// 	$TO_DEL['drug_type_map'][]=$DB_TYPE_R['drug_type_map_id'];
	// }




	
	

	// foreach ($DB_RECORD['SM'] as $FILE_SM_ENTRY_ID=>&$SM_INFO)
	// {
	// 	if ($SM_INFO['DB_STATUS']=='VALID')continue;
	// 	$STATS['drug_mol_entity_map:DEL']++;
	// 	addLog("DEL\t".$DB_RECORD_ID."\tSM\t".$FILE_SM_ENTRY_ID);
	// 	$TO_DEL['drug_mol_entity_map'][]=$SM_INFO['drug_mol_entity_map_id'];
	// }


}




function compareRecords()
{
	$TYPES=array('Antibody'=>array('Monoclonal antibody (mAb)','Polyclonal antibody (pAb)'),
				'Small molecule'=>array('Small molecule'),
				'Oligonucleotide'=>array('Oligonucleotides','Antisense oligonucleotides'),
				'Gene'=>array('Other gene therapies','Gene therapies'),
				 'Enzyme'=>array('Recombinant Enzymes'),
				 'Cell'=>array('Autologous cell transplant','Other cell transplant therapies'),
				 'Protein'=>array('Fusion proteins','Hormones','Other protein based therapies','Peptides'));
		



	global $FROM_DB;
	global $FROM_FILES;
	global $MOL_SOURCE;
	$FROM_DB_MAP=array();
	// We are going to compare the records from the files to the records from the DB
	// Each drug_entry record has a drugbank_id, so we are going to use it as a key
	$DRUGBANK_ID_MAP=array();
	$CHEMBL_ID_MAP=array();
	$SM_MOL_MAP=array();
	$SYN_MAP=array();
	$NAME_MAP=array();
	$DRUG_TYPE_MAP=array();

	foreach ($FROM_DB as $DB_ID=>&$RECORD)
	{
		$FROM_DB_MAP[$DB_ID]=array();
		if ($RECORD['drugbank_id']!='')$DRUGBANK_ID_MAP[$RECORD['drugbank_id']]=$DB_ID;
		if ($RECORD['chembl_id']!='')$CHEMBL_ID_MAP[$RECORD['chembl_id']]=$DB_ID;
		if (isset($RECORD['SM']))
		foreach ($RECORD['SM'] as $S)$SM_MOL_MAP[$S['molecular_entity_id']][]=$DB_ID;
		foreach ($RECORD['NAME'] as $N)$SYN_MAP[strtolower($N['drug_name'])][$DB_ID]=true;
		//$SYN_MAP[strtolower($RECORD['drug_primary_name'])][$DB_ID]=true;
		$NAME_MAP[strtolower($RECORD['drug_primary_name'])][$DB_ID]=true;
		if (isset($RECORD['TYPE']))
		foreach ($RECORD['TYPE'] as $T)$DRUG_TYPE_MAP[$T['drug_type_name']][]=$DB_ID;
	}
	
	
	echo count($DRUGBANK_ID_MAP)." ".count($CHEMBL_ID_MAP)."\n";
	
	$N_BY_DRUGBANK_ID=0;
	$N_BY_CHEMBL_ID=0;

	$FROM_FILE_STATUS=array();
	foreach ($FROM_FILES as $POS_RECORD=> &$FILE_RECORD)
	{
		$FROM_FILE_STATUS[$POS_RECORD]=false;
		compareDrugBankCrossRef($FILE_RECORD,$POS_RECORD,$FROM_DB_MAP,$DRUGBANK_ID_MAP);
		compareChemblId($FILE_RECORD,$POS_RECORD,$FROM_DB_MAP,$CHEMBL_ID_MAP);
		compareMolId($FILE_RECORD,$POS_RECORD,$FROM_DB_MAP,$SM_MOL_MAP,$MOL_SOURCE);
		comparePrimName($FILE_RECORD,$POS_RECORD,$FROM_DB_MAP,$NAME_MAP);
		
		compareSynonyms($FILE_RECORD,$POS_RECORD,$FROM_DB_MAP,$SYN_MAP);
		
		
		//print_R($FILE_RECORD);
		//$VAL= str_replace("\n","",fgets(STDIN));
		
		
	}
	$N_T=array();
	
	foreach ($FROM_DB_MAP as $K=>&$V)
	{
		
		$FROM_DB_RECORD=$FROM_DB[$K];
		echo "#### START ".$K."\t".$FROM_DB_RECORD['chembl_id']."\t".$FROM_DB_RECORD['drugbank_id']."\n";;	
		$N_T[count($V)]++;
		if (count($V)==0)continue;
		if (count($V)==1)
		{
			$FROM_FILE_STATUS[array_keys($V)[0]]=true;
			$FROM_FILE_RECORD=&$FROM_FILES[array_keys($V)[0]];
			$FROM_FILE_RECORD['PROCESSED']=true;
			echo $FROM_FILES[array_keys($V)[0]]['id'][0]."\tUNIQUE MATCH\n";
			if ($FROM_FILES[array_keys($V)[0]]['id'][0]=='CHEMBL3707227')print_r($FROM_FILES[array_keys($V)[0]]);
			compareRecord($FROM_FILES[array_keys($V)[0]],$K);
			continue;
		}
	
		foreach ($V as $K2=>&$V2)
		{
			$FROM_FILE_RECORD=$FROM_FILES[$K2];
			if (!isset($FROM_FILE_RECORD['drugType']))continue;
			$RECORD_TYPE=$FROM_FILE_RECORD['drugType'];
			$FOUND=false;
			if (isset($FROM_DB_RECORD['TYPE']))
			foreach ($FROM_DB_RECORD['TYPE'] as $T)
			{
				if (!isset($TYPES[$RECORD_TYPE]))continue;
				if (!in_array($T['drug_type_name'],$TYPES[$RECORD_TYPE]))continue;
				$FOUND=true;
				break;
			}
			if (!$FOUND)continue;
			$V2['SCORE']+=($RECORD_TYPE=='Small molecule')?5:7;
			$V2['RULE']['TYPE']=true;
		}
		
		foreach ($V as $K2=>&$V2)
		{
			$FROM_FILE_RECORD=&$FROM_FILES[$K2];
			echo "\tSCORE:".$V2['SCORE']."\t".$FROM_FILE_RECORD['id'][0]."\t";
			foreach ($V2['RULE'] as $TYPE=>$LIST_MATCH) {echo $TYPE; if( is_array($LIST_MATCH)) echo implode("|",$LIST_MATCH);echo "\t";}
			echo "\n";
		}

		/// Rule 1: If it has a drugbank or a chembl match, we chose that one and move on.
		$MATCH_IDS=array();
		foreach ($V as $K2=>&$V2)
		{
			if (!isset($V2['RULE']['db_id']) && !isset($V2['RULE']['chembl_id']))continue;
			
			$FROM_FILE_RECORD=&$FROM_FILES[$K2];
			echo "\tHAS CHEMBL/DRUGBANK MATCH: ".$FROM_FILE_RECORD['id'][0]."\n";
			$MATCH_IDS[]=$FROM_FILE_RECORD;
			$FROM_FILE_STATUS[$K2]=true;
			$FROM_FILE_RECORD['PROCESSED']=true;
			
		}
		if ($MATCH_IDS!=array())
		{
			$MERGED=mergeRecord($MATCH_IDS);
			compareRecord($MERGED,$K);
			continue;
		}
		

	$COUNT=count($V);

	$HAS_UP_SCORE=0;
	$HAS_DOWN_SCORE=0;
	foreach ($V as $K2=>&$V2)
	{
		if ($V2['SCORE']>=19)$HAS_UP_SCORE++;
		if ($V2['SCORE']<=19)$HAS_DOWN_SCORE++;
	}
	$N_15=0;$N_13=0;
	$CHEMBL_MATCH=true;
	$CHEMBL_TYPE_MATCH=true;
	foreach ($V as $K2=>&$V2)
	{
		if ($V2['SCORE']==15)$N_15++;
		if ($V2['SCORE']==13)$N_13++;
		if (count($V2['RULE'])!=1 || !isset($V2['RULE']['chembl_id']))$CHEMBL_MATCH=false;
		if (count($V2['RULE'])!=2 || !isset($V2['RULE']['chembl_id'])|| !isset($V2['RULE']['TYPE']))$CHEMBL_TYPE_MATCH=false;
	}
	if ($N_15==$COUNT){echo "\tNO VALID MATCH\n";continue;}
	if ($N_13==$COUNT){echo "\tNO VALID MATCH\n";continue;}
	if ($CHEMBL_MATCH||$CHEMBL_TYPE_MATCH)
	{
		echo "\tCHEMBL MATCH (".$CHEMBL_MATCH.','.$CHEMBL_TYPE_MATCH.')'.":";
		foreach ($V as $K2=>&$V2)
		{
			$FROM_FILE_RECORD=&$FROM_FILES[$K2];
			echo "\tSCORE:".$V2['SCORE']." (".$FROM_FILE_RECORD['id'][0].")\t";
			$FROM_FILE_RECORD['PROCESSED']=true;
			$FROM_FILE_STATUS[$K2]=true;
		}
		echo "\n";
		$N_T[$COUNT]--;
		$N_T[1]++;
		$RECS=array();
		foreach ($V as $K2=>&$V2)$RECS[]=$FROM_FILES[$K2];
		$MERGED=mergeRecord($RECS);
		compareRecord($MERGED,$K);
		// echo "INTERMEDIATE SCORING D\n";
		// if ($FROM_DB_RECORD['chembl_id']=='CHEMBL888')print_R($V);
		
		continue;
	}
	// echo "INTERMEDIATE SCORING E\n";
	// 	if ($FROM_DB_RECORD['chembl_id']=='CHEMBL888')print_R($V);
	// if ($FROM_DB_RECORD['chembl_id']=='CHEMBL888')
	// {
	// 	echo "NEXT SCORING\n";
	// 	print_R($V);
		
	// 	echo "SCORING ".$N_15.'| '.$N_13.'| '.$CHEMBL_MATCH.'| '.$CHEMBL_TYPE_MATCH.'| '.$HAS_UP_SCORE.'| '.$HAS_DOWN_SCORE."\n";
	// 	//foreach ($V as $K2=>$V2)print_r($FROM_FILES[$K2]);
	// 	exit;
		
	// }
	
	

	if ($HAS_UP_SCORE==1)
	{
		echo "\tHAS UP SCORE\t";
		foreach ($V as $K2=>&$V2)
		{
			if ($V2['SCORE']<19)continue;
			$FROM_FILE_STATUS[$K2]=true;
			$FROM_FILE_RECORD=&$FROM_FILES[$K2];
			echo "\tSCORE:".$V2['SCORE']." (".$FROM_FILE_RECORD['id'][0].")\n";
			$FROM_FILE_RECORD['PROCESSED']=true;
			compareRecord($FROM_FILE_RECORD,$K);
			continue;
			
		}
		$N_T[$COUNT]--;
		$N_T[1]++;
		continue;
	}
	if ($HAS_UP_SCORE>=2)
	{
		echo "\tMULTIPLE UP SCORE\t";
		/// We check that the records are not just based on name
		$VALID=0;
		foreach ($V as $K2=>&$V2)
		{
			if ($V2['SCORE']<19)continue;
			foreach ($V2 as $K3=>$V3){ if ($K3!='NAME')$VALID++;break;	}
		}
		echo "SCC:".$VALID."\t";
		if ($VALID==$HAS_UP_SCORE)
		{
			foreach ($V as $K2=>$V2)
			{
				$FROM_FILE_STATUS[$K2]=true;
				$FROM_FILE_RECORD=&$FROM_FILES[$K2];
				echo "\tSCORE:".$V2['SCORE']." (".$FROM_FILE_RECORD['id'][0].")\n";
				$FROM_FILE_RECORD['PROCESSED']=true;
			}
			$N_T[$COUNT]--;
			$N_T[1]++;
			$RECS=array();
			foreach ($V as $K2=>$V2)$RECS[]=$FROM_FILES[$K2];
			$MERGED=mergeRecord($RECS);
			if ($FROM_DB_RECORD['chembl_id']=='CHEMBL1568')
			{
				print_R($RECS);
				print_R($MERGED);
				exit;
			}
			
			
			compareRecord($MERGED,$K);
			
			continue;
		}else { echo "BUT BASED ON NAME ONLY - SKIPPING\n";}
		
	}
	
}

foreach ($FROM_FILE_STATUS as $K=>&$V)
{
	if ($V)continue;
	if (isset($FROM_FILES[$K]['PROCESSED']))continue;
	
	insertRecord($FROM_FILES[$K]);
}
// $N_F=0;
// foreach ($FROM_FILE_STATUS as $K=>$V)if ($V)$N_F++;
// echo $N_F."\t".count($FROM_FILE_STATUS)."\n";
// print_R($N_T);
	//echo count($FROM_FILES)."\t".count($FROM_DB)."\t".$N_BY_DRUGBANK_ID."\t".$N_BY_CHEMBL_ID."\t".$N_BY_SM_MOL_ID."\n";
	//exit;
}



function insertRecord(&$FILE_RECORD)
{
	
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
	
	addLog("NEW\t".$DBIDS['drug_entry']."\t".$FILE_RECORD['name']."\t".$FILE_RECORD['id'][0]);
	$DB_RECORD_ID=$DBIDS['drug_entry'];
	// Conversion 0/1 to F/T
	$MAP=array(1=>'T',0=>'F',''=>'F');

	// We insert the record into the drug_entry table
	fputs($FILES['drug_entry'],$DBIDS['drug_entry']."\t".
	(isset($FILE_RECORD['isApproved'])?$MAP[$FILE_RECORD['isApproved']]:'F')."\t".
	$MAP[$FILE_RECORD['hasBeenWithdrawn']]."\t".
	(isset($FILE_RECORD['MAX_CLIN_PHASE'])?$FILE_RECORD['MAX_CLIN_PHASE']:'N/A')."\t".
	$FILE_RECORD['name']."\t".
	"NULL"."\t".
	"NULL"."\t".
	"NULL"."\t".
	"NULL"."\t".
	"NULL"."\t".
	"NULL"."\t".
	$FILE_RECORD['id'][0]."\n");

	// We insert synonyms.
	// $NAMES is going to be used to avoid duplicates
	$NAMES=array();
	if (isset($FILE_RECORD['synonyms']))
	foreach($FILE_RECORD['synonyms'] as $ALT_NAME=>$RULE_NAME)
	{
		if (isset($NAMES[$ALT_NAME]))continue;
		$STATS['drug_name:NEW']++;

		$NAMES[$ALT_NAME]=true;
		++$DBIDS['drug_name'];
		//addLog('N/A|'.$FILE_RECORD['id'][0]."\tNEW\tNAME\t".$ALT_NAME);
		$FILE_STATUS['drug_name']=1;
		fputs($FILES['drug_name'],
		$DBIDS['drug_name']."\t".
		$DB_RECORD_ID."\t\"".
		str_replace('"','""',$ALT_NAME)."\"\t".$RULE_NAME[0]."\t".$RULE_NAME[1]."\t".
		$SOURCE_ID."\n");
	}

	$EXTDB_VAL=array();
	if (isset($FILE_RECORD['crossReferences']))
   foreach($FILE_RECORD['crossReferences'] as $SOURCE_NAME=>&$LIST_ID)
   {
	   
	   $SOURCE_EXTID=getSource($SOURCE_NAME);
	   foreach ($LIST_ID as $ID=>$STATUS)
	   {
		   if (isset($EXTDB_VAL[$SOURCE_NAME][$ID]))continue;
		   $EXTDB_VAL[$SOURCE_NAME][$ID]=true;
		  // addLog('N/A|'.$FILE_RECORD['id'][0]."\tNEW\tEXTDB\t|".$SOURCE_EXTID."|\t|".$ID.'|');
	   
		   ++$DBIDS['drug_extdb'];
		   $FILE_STATUS['drug_extdb']=1;
		   $STATS['drug_ext_db:NEW']++;
		   fputs($FILES['drug_extdb'],$DBIDS['drug_extdb']."\t".$DB_RECORD_ID."\t".$ID."\t".$SOURCE_EXTID."\t".$SOURCE_ID."\n");
	   }
   }

   if (isset($FILE_RECORD['description']))
   {
	   
		   $STATS['drug_desc:NEW']++;
		 //  addLog('N/A|'.$FILE_RECORD['id'][0]."\tNEW\t".$DB_RECORD_ID."\tSummary\t".$FILE_RECORD['description']);
		   ++$DBIDS['drug_description'];
		   $FILE_STATUS['drug_description']=1;
		   fputs($FILES['drug_description'],$DBIDS['drug_description']."\t".$DB_RECORD_ID."\t\"".str_replace('"','""',$FILE_RECORD['description'])."\"\tSummary\t".$SOURCE_ID."\n");
	   
   }


   if (isset($FILE_RECORD['drugType'])&& $FILE_RECORD['drugType']=='Small molecule')
	{

		global $MOL_SOURCE;
		if (isset($FILE_RECORD['SM']))
		foreach ($FILE_RECORD['SM'] as &$FILE_SM)
		{
			$SM_ENTRY=&$MOL_SOURCE[$FILE_SM['id']];
			$SM_ENTRY_ID=$SM_ENTRY['DB_ID'];
			//addLog("NEW\t".$DB_RECORD_ID."\tSM\t".$SM_ENTRY_ID);
			$STATS['drug_mol_entity_map:NEW']++;
			++$DBIDS['drug_mol_entity_map'];
			$FILE_STATUS['drug_mol_entity_map']=1;
			fputs($FILES['drug_mol_entity_map'],$DBIDS['drug_mol_entity_map']."\t".$DB_RECORD_ID."\t".$SM_ENTRY_ID."\t0\t".$SOURCE_ID."\n");
		
		}
		
	}
	// print_R($FILE_RECORD);
	// exit;

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
		addLog("Deleting ".$TBL." ".count($LIST).' records');
		$res=runQuery("DELETE FROM ".$TBL." WHERE ".$TBL."_id IN (".implode(",",$LIST).")");
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
		if ($return_code !=0 )	 failProcess($JOB_ID."008",'Unable to insert data into '.$NAME.' '.print_r($res,true));
	}
	// We reset the file status
	$FILES=array();

	//If it's the last call we don't need to reopen the files
	if ($LAST_CALL)return;
	
	// We reopen the files
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$FILE_STATUS[$TBL]=0;
		$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');if (!$FILES[$TBL])				failProcess($JOB_ID."005",'Unable to open file '.$TBL.'.csv');
	}
	

}




	function mergeRecord(&$LIST)
	{
		$RECORD=array();
		$cols=array('drugType','name','description','hasBeenWithdrawn','isApproved','maximumClinicalTrialPhase');
		foreach ($LIST as &$REC)
		{
			//print_r($REC);
			foreach ($cols as $c) 
			{
				if (!isset($REC[$c]))continue;
				if (isset($RECORD[$c]) && $REC[$c]!=$RECORD[$c])
				{
					//echo "DIFFERENT $c for ".$REC['id']."\n".$REC[$c]." ".$RECORD[$c]."\n";
					if (is_numeric($REC[$c])) $RECORD[$c]=max($REC[$c],$RECORD[$c]);
					else if (strlen($REC[$c]) >strlen($RECORD[$c])) $RECORD[$c]=$REC[$c];
					
				} 
				else $RECORD[$c]=$REC[$c];
			}
			if (is_array($REC['id']))
			foreach ($REC['id'] as $id)$RECORD['id'][]=$id;
			else $RECORD['id'][]=$REC['id'];
			if (isset($REC['drugType']) && $REC['drugType']=='Small molecule')
			{
				if (!isset($REC['canonicalSmiles']))$REC['canonicalSmiles']='';
				if (!isset($REC['inchiKey']))$REC['inchiKey']='';
				if (!isset($REC['id']))$REC['id']='';
				if (isset($REC['CPD']))
				{
					$FOUND=false;
					if (isset($RECORD['CPD']))
					foreach ($RECORD['CPD'] as &$C)
					{
						if ($C['id']!= $REC['id'])continue;
						if ($C['canonicalSmiles']!=$REC['canonicalSmiles'])continue;
						if ($C['inchiKey']!=$REC['inchiKey'])continue;
						$FOUND=true;
					}
					if ($FOUND)continue;
				}
				$RECORD['CPD'][]=array('canonicalSmiles'=>$REC['canonicalSmiles'],
							'id'=>$REC['id'],'inchiKey'=>$REC['inchiKey']);
			}
			
			$RECORD['synonyms'][$REC['name']]=array('T','T');

			if (isset($REC['tradeName']))
			foreach ($REC['tradeName'] as $TN)
			{
				$RECORD['synonyms'][$TN]=array('F','T');
			}
			
			if (isset($REC['synonyms']))
			{
				foreach ($REC['synonyms'] as $TN=>$RULE_NAME)
				{	
					if (is_array($RULE_NAME))$RECORD['synonyms'][$TN]=$RULE_NAME;
					else $RECORD['synonyms'][$RULE_NAME]=array('F','F');
				}
			}
			if (isset($REC['linkedTargets']['rows']))
			foreach ($REC['linkedTargets']['rows'] as $TN)	$RECORD['linkedTargets'][$TN]=true;
			if (isset($REC['linkedDiseases']['rows']))
			foreach ($REC['linkedDiseases']['rows'] as $TN)	$RECORD['linkedDiseases'][$TN]=true;
		if (isset($REC['crossReferences']))
			foreach ($REC['crossReferences'] as $DB=>$LIST_IDS)
			{
				if (!isset($RECORD['crossReferences'][$DB]))$RECORD['crossReferences'][$DB]=array();
				foreach ($LIST_IDS as $K=>$V)
				{
					
					if ($V===null)$RECORD['crossReferences'][$DB][$K]=null;
					else if (is_array($V))$RECORD['crossReferences'][$DB][array_keys($V)[0]]=null;
					else $RECORD['crossReferences'][$DB][$V]=null;
				}
			}
			
		}
		//exit;
		return $RECORD;
	}


		// $IS_SM			=($RAW_DATA['drugType']=="Small molecule");
		// $IS_APPROVED	=isset($RAW_DATA['isApproved'])?($RAW_DATA['isApproved']	   ==1)?'T':'F':'F';
		// $IS_WITHDRAWN	=($RAW_DATA['hasBeenWithdrawn']==1)?'T':'F';
		// $CLIN_PHASE		=&$RAW_DATA['maximumClinicalTrialPhase'];
		// if ($CLIN_PHASE=='')$CLIN_PHASE=0;
		// $DRUG_TYPE='';
		/// Mapping drug type
		// if (!isset($RAW_DATA['drugType']))$DRUG_TYPE='UN';
		// else 
		// switch ($RAW_DATA['drugType'])
		// {
		// 	case "Antibody": 		$DRUG_TYPE='A'; break;
		// 	case "Small molecule": 	$DRUG_TYPE='S'; break;
		// 	case "Oligonucleotide": $DRUG_TYPE='O'; break;
		// 	case "Oligosaccharide": $DRUG_TYPE='OS';break;
		// 	case "Gene": 			$DRUG_TYPE='GN';break;
		// 	case "Enzyme": 			$DRUG_TYPE='EN';break;
		// 	case "unknown":
		// 	case "Unknown": 		$DRUG_TYPE='UN';break;
		// 	case "Cell": 			$DRUG_TYPE='CE';break;
		// 	case "Protein": 		$DRUG_TYPE='PR';break;
		// 	default:																		failProcess($JOB_ID."008",'Unknown Drug type'.$DRUG_TYPE);
		// }
		


	function loadFromDB()
	{
		global $SOURCE_ID;
		global $FROM_DB;
		global $MOL_SOURCE;
		addLog("\tGet Drugs");
		$res=runQuery("SELECT * FROM drug_entry");
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
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['drug_entry_id']]['NAME'][]=$line;
		}
		addLog("\tGet Drugs external identifiers");
		$res=runQuery("SELECT * FROM drug_extdb de where source_id = ".$SOURCE_ID." OR source_origin_id = ".$SOURCE_ID);
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['drug_entry_id']]['EXTDB'][]=$line;
		}
		addLog("\tGet Drugs description");
		$res=runQuery("SELECT * FROM drug_description de where source_id = ".$SOURCE_ID);
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['drug_entry_id']]['DESC'][]=$line;
		}
		addLog("\tGet Drugs Type");
		$res=runQuery("SELECT * FROM drug_type_map de, drug_type dt where dt.drug_type_id= de.drug_type_id ");
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['drug_entry_id']]['TYPE'][]=$line;
		}
		addLog("\tGet Drugs ATC Mapping");
		$res=runQuery("SELECT * FROM drug_atc_map de ");
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['drug_entry_id']]['ATC'][]=$line;
		}
		addLog("\tGet Small molecule data");
		$res=runQuery("SELECT * FROM drug_mol_entity_map dsm, molecular_entity me, sm_entry se 
		LEFT JOIN sm_counterion sc on sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm
		wHERE dsm.molecular_entity_id = me.molecular_entity_id 
		AND se.md5_hash = me.molecular_structure_hash 
		AND se.sm_molecule_id = sm.sm_molecule_id");
		echo "SM FROM DB:".count($res)."\n";
		
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['drug_entry_id']]['SM'][$line['molecular_entity_id']]=$line;
		}
		// addLog("\tGet Small molecule names");
		// $res=runQuery("SELECT dsm.drug_entry_id, dsm.sm_entry_Id,sm_molecule_id,source_Id FROM drug_mol_entity_map dsm , sm_entry se where se.sm_entry_id = dsm.sm_entry_id");
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
			$MOL_SOURCE=array();
			foreach ($res as $line)$MOL_SOURCE[$line['sm_name']]=array('DB_ID'=>$line['molecular_entity_id']);

	
	}
	

	function loadOpenTargetsDrug()
	{
		global $FROM_FILES;
		global $MAPPING_RULES;
		$FROM_FILES=array();
		/// NAMES will be a hash of all the names and their record we have in the database
		$NAMES=array();

		$UNSET_RULES=array("CHEMBL2109680"=>"IDEC-159","CHEMBL2109679"=>"IDEC-159","CHEMBL3393588"=>"MEDI-564", "CHEMBL52885"=>"ENMD-2076","CHEMBL4113131"=>"X-396");
		$CHANGE_NAME=array("CHEMBL3330650"=>"GDC-0623");
		$MERGE_RULES=array(array('CHEMBL3545025','CHEMBL313972'),
		array('CHEMBL3989873','CHEMBL119709'),
		array('CHEMBL180101','CHEMBL14370'),
		array('CHEMBL3545138','CHEMBL2177736'),
		array('CHEMBL3545282','CHEMBL1208829','CHEMBL3402567'),
		array('CHEMBL3545250','CHEMBL2216859'),
		array('CHEMBL3545175','CHEMBL1215331'),
		array('CHEMBL2368861','CHEMBL511142','CHEMBL560511'),
		array('CHEMBL2109608','CHEMBL4297740'),
		array('CHEMBL2146883','CHEMBL2364607'),
		array('CHEMBL2138625','CHEMBL5095024','CHEMBL5095492'),
		array('CHEMBL1199540','CHEMBL1197091','CHEMBL559362','CHEMBL1162013'),
		array('CHEMBL2109514','CHEMBL2109673'));

		foreach ($MAPPING_RULES as &$RULE_MERGE)
			if ($RULE_MERGE['REF_CHEMBL_ID']!='N/A')$MERGE_RULES[]=array($RULE_MERGE['ALT_CHEMBL_ID'],$RULE_MERGE['REF_CHEMBL_ID']);
		

		


		$fp=fopen('molecules.json','r');if (!$fp)											failProcess($JOB_ID."006",'Unable to open molecules.json');
		$TMP_MAP=array();
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
			$ENTRY=json_decode($line,true);
			if ($ENTRY===false)																failProcess($JOB_ID."007",'Unable to read json string');
			$ID=$ENTRY['id'];
			$FROM_FILE_ID[$ID]=array();
			if (isset($CHANGE_NAME[$ID]))
			{
				echo "CHANGE NAME FROM ".$ENTRY['name'].' TO '.$CHANGE_NAME[$ID]."\n";
				$ENTRY['name']=$CHANGE_NAME[$ID];
			}
			if (isset($UNSET_RULES[$ID]))
			{
				
				foreach ($ENTRY['synonyms'] as $K=>$E)
				{
					if ($E!=$UNSET_RULES[$ID])continue;
					echo "REMOVING SYNONYM FROM ".$ID."\t".$E."\n";
					unset($ENTRY['synonyms'][$K]);
				}
			}
			$TMP_MAP[$ENTRY['id']]=$ENTRY['name'];
			$NAMES[$ENTRY["name"]][]=$ENTRY; 
		}
		fclose($fp);	

		foreach ($MERGE_RULES as &$LIST_MERGE)
		{
			$NAME='';
			$LIST_DEL=array();
			$NEW_REC=array();echo "MERGING ". implode("\t",$LIST_MERGE)."\n";
			foreach ($LIST_MERGE as $M)
			{
				if(!isset($TMP_MAP[$M]))
				{
					echo "\tMISSING |".$M."|\n";
					continue;
				}
				if ($NAME=='')$NAME=$TMP_MAP[$M];
				$LIST_DEL[]=$TMP_MAP[$M];
				foreach ($NAMES[$TMP_MAP[$M]] as &$E)$NEW_REC[]=$E;
				echo "\t".$TMP_MAP[$M].':'.count($NAMES[$TMP_MAP[$M]])."\t";
			}
			foreach ($LIST_DEL as $N)unset($NAMES[$N]);
			$NAMES[$NAME]=$NEW_REC;
			echo "\n";
		}

		foreach ($NAMES as $NAME=>&$LIST)
		{
			$FROM_FILES[]=mergeRecord($LIST);
		}

	}

	
	successProcess();	

?>