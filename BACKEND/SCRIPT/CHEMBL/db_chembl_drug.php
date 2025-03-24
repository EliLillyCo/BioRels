<?php
error_reporting(E_ALL);
ini_set('memory_limit','1000M');
/**
 SCRIPT NAME: db_chembl_drug
 PURPOSE:     PRocess ChEMBL drug data and push to DB
 
*/

/// Job name - Do not change
$JOB_NAME='db_chembl_drug';

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

 

addLog("Access directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('db_chembl_data')];
	
	/// Create directory in PROCESS if it doesn't exist
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];	if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   		   	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; 	   	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

	/// INSERT will be used for the new data
	if (!is_dir('INSERT') && !mkdir('INSERT')) 														failProcess($JOB_ID."005",'Unable to create INSERT dir '.$W_DIR);

	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Then we look for ChEMBL as a source. Because different databases might have written ChEMBL differently, we do it case insensitive
	$source_id=getSource("ChEMBL");

	$STATIC_DATA=array();


	addLog("Process drugs");
	processDrugs();

	
	
	

function processDrugs()
{
	global $GLB_VAR;
	global $DB_INFO;
	global $source_id;
	global $JOB_ID;
	$SCHEMA=$GLB_VAR['PUBLIC_SCHEMA'];

	$FILES=array(
		'NAME'=>fopen('DRUG_NAME.csv','w'),
		'ENTRY'=>fopen('DRUG_ENTRY.csv','w'),
		'MAP'=>fopen('DRUG_MAP.csv','w'),
		'NAME_ID'=>0,
		'ENTRY_ID'=>0,
		'MAP_ID'=>0
	);
	if (!$FILES['NAME'] || !$FILES['ENTRY'] || !$FILES['MAP'])failProcess($JOB_ID."A01",'Unable to open files');
	$res=runQuery("SELECT max(drug_entry_id) m FROM drug_entry");
	if ($res===false)failProcess($JOB_ID."A02",'Unable to get drug_entry max PK value');
	$FILES['ENTRY_ID']=$res[0]['m'];
	$FILES['ENTRY_ID']=($FILES['ENTRY_ID']=='')?0:$FILES['ENTRY_ID'];

	$res=runQuery("SELECT max(drug_name_id) m FROM drug_name");
	if ($res===false)failProcess($JOB_ID."A03",'Unable to get drug_name max PK value');
	$FILES['NAME_ID']=$res[0]['m'];
	$FILES['NAME_ID']=($FILES['NAME_ID']=='')?0:$FILES['NAME_ID'];

	$res=runQuery("SELECT max(drug_mol_entity_map_id) m FROM drug_mol_entity_map");
	if ($res===false)failProcess($JOB_ID."A04",'Unable to get drug_mol_entity_map max PK value');
	$FILES['MAP_ID']=$res[0]['m'];
	$FILES['MAP_ID']=($FILES['MAP_ID']=='')?0:$FILES['MAP_ID'];



	$res=runQuery("SELECT * FROM public.molecule_dictionary where max_phase is NOT NULL");
	if ($res===false)failProcess($JOB_ID."A05",'Unable to get molecule_dictionary');
	$n=0;
	foreach ($res as  $line)
	{
		processDrugRecord($line,$FILES);
		++$n;
		if ($n!=100)continue;
	
		fclose($FILES['NAME']);
		fclose($FILES['ENTRY']);
		fclose($FILES['MAP']);



		$command='\COPY '.$SCHEMA.'.drug_entry (drug_entry_id,drug_primary_name,is_approved,is_withdrawn,is_investigational, is_experimental, is_nutraceutical,is_illicit, is_vet_approved,max_clin_phase,drugbank_id,chembl_id) FROM \'DRUG_ENTRY.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																		failProcess($JOB_ID."A06",'Unable to insert drug_entry'); 
		
		
		$command='\COPY '.$SCHEMA.'.drug_name (drug_name_id,drug_entry_id,drug_name,is_primary,is_tradename,source_id) FROM \'DRUG_NAME.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\", ESCAPE '\\\\' ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																		failProcess($JOB_ID."A07",'Unable to insert drug_name'); 
		
		$command='\COPY '.$SCHEMA.'.drug_mol_entity_map (drug_mol_entity_map_id,drug_entry_id,molecular_entity_id,is_preferred,source_id) FROM \'DRUG_MAP.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																		failProcess($JOB_ID."A08",'Unable to insert drug_mol_entity_map'); 
	
		$FILES['NAME']=fopen('DRUG_NAME.csv','w');
		$FILES['ENTRY']=fopen('DRUG_ENTRY.csv','w');
		$FILES['MAP']=fopen('DRUG_MAP.csv','w');
	}
	fclose($FILES['NAME']);
		fclose($FILES['ENTRY']);
		fclose($FILES['MAP']);



		$command='\COPY '.$SCHEMA.'.drug_entry (drug_entry_id,drug_primary_name,is_approved,is_withdrawn,is_investigational, is_experimental, is_nutraceutical,is_illicit, is_vet_approved,max_clin_phase,drugbank_id,chembl_id) FROM \'DRUG_ENTRY.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																		failProcess($JOB_ID."A09",'Unable to insert drug_entry'); 
		
		
		$command='\COPY '.$SCHEMA.'.drug_name (drug_name_id,drug_entry_id,drug_name,is_primary,is_tradename,source_id) FROM \'DRUG_NAME.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\", ESCAPE '\\\\' ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																		failProcess($JOB_ID."A10",'Unable to insert drug_name'); 
		
		$command='\COPY '.$SCHEMA.'.drug_mol_entity_map (drug_mol_entity_map_id,drug_entry_id,molecular_entity_id,is_preferred,source_id) FROM \'DRUG_MAP.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																		failProcess($JOB_ID."A11",'Unable to insert drug_mol_entity_map'); 
	
	

}


function processDrugRecord($FROM_CHEMBL,&$FILES)
{
	global $JOB_ID;
	global $source_id;

	$MAP_CLIN_PHASE=array('0.5'=>'0.5',
		'1.0'=>'1',
		'2.0'=>'2',
		'3.0'=>'3',
		'4.0'=>'4',
		'-1.0'=>'N/A');

	$res=runQuery("SELECT * FROM drug_entry where chembl_id = '".$FROM_CHEMBL['chembl_id']."'");
	if ($res===false)failProcess($JOB_ID."B01",'Unable to get drug_entry');
	if (count($res)==0)
	{
		echo "NEW DRUG\t".$FROM_CHEMBL['pref_name'].'::'.$FROM_CHEMBL['chembl_id']."\n";
		++$FILES['ENTRY_ID'];
		fputs($FILES['ENTRY'],
		$FILES['ENTRY_ID']."\t".
		$FROM_CHEMBL['pref_name']."\t".
		(($FROM_CHEMBL['first_approval']!='')?'T':'F')."\t".
		(($FROM_CHEMBL['withdrawn_flag']!=0)?'T':'F')."\tNULL\tNULL\tNULL\tNULL\tNULL\t".
		$MAP_CLIN_PHASE[$FROM_CHEMBL['max_phase']]."\tNULL\t".$FROM_CHEMBL['chembl_id']."\n");
		$FROM_DB['drug_entry_id']=$FILES['ENTRY_ID'];
		$FROM_DB['drug_primary_name']=$FROM_CHEMBL['pref_name'];
		processDrugSynonyms($FROM_CHEMBL,$FROM_DB,$FILES);
		processDrugStructure($FROM_CHEMBL,$FROM_DB,$FILES);
	}
	else
	{
		$FROM_DB=$res[0];
		$query=array();
		if ((int)$MAP_CLIN_PHASE[$FROM_CHEMBL['max_phase']]>(int)$FROM_DB['max_clin_phase'])
		{
			//echo $FROM_DB['drug_primary_name']."\tCLIN PHASE:".$FROM_DB['max_clin_phase']."=>".$MAP_CLIN_PHASE[$FROM_CHEMBL['max_phase']]."\n";
			$query[]='max_clin_phase='.$MAP_CLIN_PHASE[$FROM_CHEMBL['max_phase']];
		}
		if ($FROM_CHEMBL['first_approval']!='' && $FROM_DB['is_approved']!='T')
		{
			//echo $FROM_DB['drug_primary_name']."\tAPPROVED:".$FROM_DB['is_approved']."=>T\n";
			$query[]='is_approved=\'T\'';
		}
		if ($FROM_CHEMBL['withdrawn_flag']!=0 && $FROM_DB['is_withdrawn']=='F')
		{
			//echo $FROM_DB['drug_primary_name']."\tWITHDRAWN:".$FROM_DB['is_withdrawn']."=>".$FROM_CHEMBL['withdrawn_flag']."\n";
			$query[]='is_withdrawn=\'T\'';
		}
		
		if ($query!=array())
		{
			$query='UPDATE drug_entry SET '.implode(',',$query).' WHERE drug_entry_id='.$FROM_DB['drug_entry_id'];
			if (!runQueryNoRes($query))failProcess($JOB_ID."B02",'Unable to update drug_entry');
		}
		processDrugSynonyms($FROM_CHEMBL,$FROM_DB,$FILES);
		processDrugStructure($FROM_CHEMBL,$FROM_DB,$FILES);
		
	}
}

function processDrugStructure(&$FROM_CHEMBL,$FROM_DB,&$FILES)
{
	global $JOB_ID;
	global $source_id;

	
	$res=runQuery("SELECT molecular_entity_id FROM 
	molecular_entity me, sm_entry se,sm_source ss 
	wHERE  se.md5_hash = me.molecular_structure_hash
	AND se.sm_entry_id = ss.sm_entry_id
	AND ss.source_id = ".$source_id."
	AND sm_name = '".$FROM_CHEMBL['chembl_id']."'");
	if ($res===false)failProcess($JOB_ID."C01",'Unable to get molecular_entity_id');
	if ($res==array())return;

	$mol_entity_id=$res[0]['molecular_entity_id'];
	$res=runQuery("SELECT * FROM drug_mol_entity_map WHERE drug_entry_id = ".$FROM_DB['drug_entry_id']);
	if ($res===false)failProcess($JOB_ID."C02",'Unable to get drug_mol_entity_map');
	$FOUND=false;
	foreach ($res as $line)
	{
		if ($line['molecular_entity_id']==$mol_entity_id)$FOUND=true;
	}
	if ($FOUND)return;
	$FILES['MAP_ID']++;
	fputs($FILES['MAP'],$FILES['MAP_ID']."\t".$FROM_DB['drug_entry_id']."\t".$mol_entity_id."\tT\t".$source_id."\n");
	echo $FROM_CHEMBL['chembl_id']."\t".$FROM_DB['drug_primary_name']."\t".$FROM_DB['drug_entry_id']."\t".$mol_entity_id."\n";
	//if (!runQueryNoRes($query))failProcess($JOB_ID."D04",'Unable to insert drug_mol_entity_map');

}


function processDrugSynonyms(&$FROM_CHEMBL,$FROM_DB,&$FILES)
{
	global $JOB_ID;
	global $source_id;
	$res=runQuery("SELECT * FROM drug_name dn
		WHERE drug_entry_id = ".$FROM_DB['drug_entry_id']."
		AND source_id = ".$source_id);
	if ($res===false)failProcess($JOB_ID."D01",'Unable to get drug_name');


	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$FROM_DB['SYN'][]=$line;
	}

	$res=runQuery("SELECT DISTINCT synonyms 
	FROM public.molecule_synonyms ms, public.molecule_dictionary md 
	WHERE ms.molregno = md.molregno
	AND md.chembl_id = '".$FROM_CHEMBL['chembl_id']."'");
	if ($res===false)failProcess($JOB_ID."D02",'Unable to get molecule_synonyms');
	
	foreach ($res as $line)
	{
		$FOUND=false;
		$line['synonyms']=str_replace('"','',str_replace("\t","",trim($line['synonyms'])));
		if (isset($FROM_DB['SYN']))
		foreach ($FROM_DB['SYN'] as &$SYN_DB)
		{
			if ($SYN_DB['drug_name']==$line['synonyms'])
			{
				$SYN_DB['DB_STATUS']='VALID';
				$FOUND=true;
				break;
			}
		}
		if ($FOUND)continue;
		$FROM_DB['SYN'][]=array('drug_name'=>$line['synonyms'],'DB_STATUS'=>'TO_INS');
	//	echo $FROM_DB['drug_primary_name']."\tNAME:".$line['synonyms']."\n";
		++$FILES['NAME_ID'];
		fputs($FILES['NAME'],$FILES['NAME_ID']."\t".$FROM_DB['drug_entry_id']."\t\"".str_replace('"','""',str_replace("\\","\\\\",$line['synonyms']))."\"\tF\tF\t".$source_id."\n");
		//$query='INSERT INTO drug_name VALUES ('.$FILES['NAME_ID'].','.$FROM_DB['drug_entry_id'].',"'.$line['synonyms'].'","F","F",'.$source_id.')';
	}


}




successProcess();



?>
