<?php
error_reporting(E_ALL);
ini_set('memory_limit','5000M');

/**
 SCRIPT NAME: db_drugbank_cpd
 PURPOSE:     Process all drubank small molecules
 
*/

/// Job name - Do not change
$JOB_NAME='db_drugbank_cpd';

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
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_drugbank')];
	
	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];	if (!is_dir($W_DIR)) 								failProcess($JOB_ID.'001','NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID.'002','Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; 		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID.'003','Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID.'004','Unable to access process dir '.$W_DIR);
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory:".$W_DIR);

	/// Check if the tool paths are set
	if (!isset($GLB_VAR['TOOL']['FILECONV']))														failProcess($JOB_ID.'005','Unable to Find fileconv paramter');
	if (!isset($GLB_VAR['TOOL']['PREF_SMI']))														failProcess($JOB_ID.'006','Unable to Find pref_smi paramter');
	if (!isset($GLB_VAR['TOOL']['FILECONV_PARAM']))													failProcess($JOB_ID.'007','Unable to Find pref_smi paramter');

	/// Check if the tools are executable
	$FILECONV=$GLB_VAR['TOOL']['FILECONV']; if(!is_executable($FILECONV))							failProcess($JOB_ID.'008','Unable to Find fileconv '.$FILECONV);
	$PREF_SMI=$GLB_VAR['TOOL']['PREF_SMI']; if(!is_executable($PREF_SMI))							failProcess($JOB_ID.'009','Unable to Find PREF_SMI '.$PREF_SMI);
	$FILECONV_PARAM=$GLB_VAR['TOOL']['FILECONV_PARAM'];
	
	/// Create the directories
	if (!is_dir('LOG_INSERT') && !mkdir('LOG_INSERT'))												failProcess($JOB_ID.'010','Unable to create LOG_INSERT');
	if (!is_dir('INSERT') && !mkdir('INSERT'))														failProcess($JOB_ID.'011','Unable to create INSERT');
	if (!is_dir('STD') && !mkdir('STD'))															failProcess($JOB_ID.'012','Unable to create STD');

	
	/// Get the source id
	$SOURCE_ID=getSource("DrugBank");


	/// Update all names coming from drug bank to F
	/// During the process we will update the status to T
	/// The ones left with F will be deleted
	if (runQueryNoRes("UPDATE sm_source set sm_name_Status='F' where sm_source_id=".$SOURCE_ID)===false)	failProcess($JOB_ID.'013','Unable to update sm_source');
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("UPDATE ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source 
							SET sm_name_status='F' 
							WHERE source_id = ".$SOURCE_ID)) 										failProcess($JOB_ID."014",'Unable to update sm_source');
	}

//  addLog("Process chemreps");
 	/// Names are based on release version, so we need to get the version for drugbank
 	$CURR_RELEASE=explode(";",getCurrentReleaseDate('DRUGBANK',$JOB_ID))[0];
	/// chemreps contains all small moleculeS:
	$fp=fopen('drugs.csv','r');if (!$fp)															failProcess($JOB_ID.'015','Unable to open drugbank chemreps file');
	/// We are going to put all the smiles into drugbank.smi so we can standardize them
	$fpO=fopen('STD/molecule.smi','w');if (!$fpO)													failProcess($JOB_ID.'016','Unable to open STD/drugbank.smi');
	/// And all the counterions first in counterion_map then in counterion.smi
	$COUNTERION_MAP=array();
	$fpC=fopen('STD/counterion.smi','w');if (!$fpC)													failProcess($JOB_ID."017",'Unable to open STD/counterion.smi');

	
	$HEAD=array_flip(fgetcsv($fp));
	if (!isset($HEAD['drugbank_id'])
	||!isset($HEAD['moldb_smiles'])
	||!isset($HEAD['moldb_inchi'])
	||!isset($HEAD['moldb_inchikey']))																failProcess($JOB_ID.'018','Unable to find proper columns ');
	$N=0;
	while(!feof($fp))
	{

		$tab=fgetcsv($fp);if ($tab===false)continue;
        if ($tab[$HEAD['moldb_smiles']]=='')continue;
		$tabS=explode(".",$tab[$HEAD['moldb_smiles']]);
		/// We are going to consider the longuest SMILES string as the primary molecule and the rest as counterions
		/// counterions WILL NOT be standardized, but follow another manual process
		$MAX_LEN=0;
		foreach ($tabS as $t)$MAX_LEN=max($MAX_LEN,strlen($t));
		/// Whatever is not the longuest will be in ALT
		$ALT=array();
		/// Longuest SMILES
		$SMI='';
		foreach ($tabS as $t)if (strlen($t)==$MAX_LEN)$SMI=$t;else $ALT[]=$t;
		/// For the counterions, we will keep track of the different counterions
		/// But to keep a combination of counterions, we will sort them
		sort($ALT);
		if ($ALT==array())$ALT[]='NULL';
		//echo "SMI:".$SMI."\t".implode('.',$ALT)."\n";;
		fputs($fpO,$tab[$HEAD['moldb_smiles']].' '.$tab[$HEAD['drugbank_id']]."|".$tab[$HEAD['moldb_inchi']]."|".$tab[$HEAD['moldb_inchikey']]."|".implode(".",$ALT)."|".$SMI."\n");
		
		/// We are going to keep track of the counterions
		/// Make them as key so it is unique
		$STR_C=implode(".",$ALT);
		if ($STR_C!='NULL')$COUNTERION_MAP[$STR_C.' '.$STR_C]='';
	}

	/// We are going to put all the counterions into counterion.smi
	fputs($fpC,implode("\n",array_keys($COUNTERION_MAP)))."\n";
	/// We don't need the map anymore
	unset($COUNTERION_MAP);
	
	fclose($fpO);
	fclose($fpC);

	standardizeCompounds(true);


	
	if (!runQueryNoRes("DELETE FROM sm_source WHERE sm_name_Status='F' AND source_Id = ".$SOURCE_ID))	failProcess($JOB_ID.'019','Unable to delete sm_source');
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("DELETE FROM ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source WHERE sm_name_status='F' AND source_id = ".$SOURCE_ID)) 	failProcess($JOB_ID."020",'Unable to update sm_source');
	}



successProcess();


?>