<?php
error_reporting(E_ALL);
ini_set('memory_limit','5000M');
/**
 SCRIPT NAME: db_chembl_cpd
 PURPOSE:     PRocess ChEMBL compounds
 
*/

/// Job name - Do not change
$JOB_NAME='db_chembl_cpd';

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

addLog("Access working directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_chembl')];
	
	// Create directory in PROCESS if it doesn't exist
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];	if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; 		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	// Create STD directory if it doesn't exist - which will be used for the compounds data												
	if (!is_dir('STD') && !mkdir('STD'))															failProcess($JOB_ID."005",'Unable to create STD');

	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// All molecule Identifiers will be mapped to the ChEMBL source
	/// So we need to get the source id and create it if it doesn't exist
	$SOURCE_ID=getSource("ChEMBL");

addLog("Working directory: ".$W_DIR);



addLog("Update source");
	/// We can't keep track of all 2M+ compounds, so we are going to assign status=F to all molecule identifiers associated to that source 
	/// The process will update the existing ones to T and create new ones
	/// Then we can delete the ones that are still F

	/// We are going to update the sm_source table
	if (runQueryNoRes("UPDATE sm_source 
	set sm_name_Status='F' 
	where sm_source_id=".$SOURCE_ID)===false)														failProcess($JOB_ID.'006','Unable to update sm_source');
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("UPDATE ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source 
		SET sm_name_status='F' 
		WHERE source_id = ".$SOURCE_ID)) 															failProcess($JOB_ID."007",'Unable to update sm_source');
	}

addLog("Extract SMILES and counterions from ChEMBL file");
 	/// Names are based on release version, so we need to get the version for ChEMBL
 	$CURR_RELEASE=explode("-",getCurrentReleaseDate('CHEMBL',$JOB_ID))[0];
	/// chemreps contains all small moleculeS:
	$fp=fopen('chembl_'.$CURR_RELEASE.'_chemreps.txt','r');if (!$fp)								failProcess($JOB_ID."008",'Unable to open chembl chemreps file');
	/// We are going to put all the smiles into chembl.smi so we can standardize them
	$fpO=fopen('STD/molecule.smi','w');if (!$fpO)													failProcess($JOB_ID."009",'Unable to open STD/molecule.smi');
	$fpC=fopen('STD/counterion.smi','w');if (!$fpC)													failProcess($JOB_ID."010",'Unable to open STD/counterion.smi');


	/// Extracting header:
	$line=stream_get_line($fp,1000,"\n");
	$HEAD=array_flip(explode("\t",$line));
	if (!isset($HEAD['chembl_id'])
	||!isset($HEAD['canonical_smiles'])
	||!isset($HEAD['standard_inchi'])
	||!isset($HEAD['standard_inchi_key']))															failProcess($JOB_ID."011",'Unable to find proper columns ');
	$N=0;
	$COUNTERION_MAP=array();
	while(!feof($fp))
	{

		$line=stream_get_line($fp,100000,"\n");
		if ($line=='')continue;///definitively don't want an empty line
		$tab=explode("\t",$line);
		$tabS=explode(".",$tab[$HEAD['canonical_smiles']]);
		/// We are going to consider the longuest SMILES string as the primary molecule and the rest as counterions
		/// counterions WILL NOT be standardized, but follow another manual process
		$MAX_LEN=0;
		foreach ($tabS as $t)$MAX_LEN=max($MAX_LEN,strlen($t));
		$ALT=array();$SMI='';
		foreach ($tabS as $t)if (strlen($t)==$MAX_LEN)$SMI=$t;else $ALT[]=$t;

		/// Sort is important here so our counterions are always in the same order
		sort($ALT);if ($ALT==array())$ALT[]='NULL';
		
		/// We are going to write the primary molecule to the molecule.smi file
		fputs($fpO,$tab[$HEAD['canonical_smiles']].' '.$tab[$HEAD['chembl_id']]."|".$tab[$HEAD['standard_inchi']]."|".$tab[$HEAD['standard_inchi_key']]."|".implode(".",$ALT)."|".$SMI."\n");

		/// We are going to write the counterions to the counterion.smi file
		/// But to avoid duplicating the work, we put them into a temporary array
		/// where we write the counterions as key, which ensure that we don't have duplicates
		$STR_C=implode(".",$ALT);
		if ($STR_C!='NULL')$COUNTERION_MAP[$STR_C.' '.$STR_C]='';
		
	}
	
	/// We are going to write the counterions to the counterion.smi file
	fputs($fpC,implode("\n",array_keys($COUNTERION_MAP)))."\n";
	/// No need for the counterion array anymore
	unset($COUNTERION_MAP);

	/// Close the files
	fclose($fp);
	fclose($fpO);
	fclose($fpC);

	/// We are going to standardize the molecules
	standardizeCompounds(true);

addLog("Cleanup source");
	/// We updated all the status to F, so we can delete the ones that are still F after processing
	if (!runQueryNoRes("DELETE FROM sm_source WHERE sm_name_Status='F' AND source_Id = ".$SOURCE_ID))	failProcess($JOB_ID.'012','Unable to delete sm_source');
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("DELETE FROM ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source WHERE sm_name_status='F' AND source_id = ".$SOURCE_ID)) 	failProcess($JOB_ID."013",'Unable to update sm_source');
	}



successProcess();


?>