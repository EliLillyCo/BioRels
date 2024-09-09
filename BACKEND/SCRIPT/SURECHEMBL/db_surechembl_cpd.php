<?php
error_reporting(E_ALL);
ini_set('memory_limit','500M');
/**
 SCRIPT NAME: db_surechembl_cpd
 PURPOSE:     Download all surechembl files
 
*/
$JOB_NAME='db_surechembl_cpd';

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




addLog("Defining directory");
	/// Get parent job info:
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_surechembl')];
	
	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; 		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
											 	if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
addLog("Working directory:".$W_DIR);
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Create the directories
	if (!is_dir('STD') && !mkdir('STD'))												failProcess($JOB_ID."005",'Unable to create STD');

	// Get release date
	$CURR_RELEASE=explode("-",getCurrentReleaseDate('SURECHEMBL',$JOB_ID))[1];
	
addLog("Update source");
	$SOURCE_ID=getSource('SureChEMBL');
	
	//Change all names status to F. When processing the molecules, they will be set to T if they are found
	if (!runQueryNoRes("UPDATE ".$GLB_VAR['PUBLIC_SCHEMA'].".sm_source SET sm_name_status='F' WHERE source_id = ".$SOURCE_ID)) 	failProcess($JOB_ID."006",'Unable to update SureChEMBL name source');
	//Do the same for the private schema if it is enabled
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("UPDATE ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source SET sm_name_status='F' WHERE source_id = ".$SOURCE_ID)) 	failProcess($JOB_ID."007",'Unable to update SureChEMBL name source');
	}
	


addLog("Process chemreps");
	// There are multiple files, so we process them all
	if ($handle = opendir('.')) {

		while (false !== ($entry = readdir($handle))) {
	
			if (!preg_match('/(SureChEMBL_[0-9]{1,8}_[0-9]{1,3}.txt)/',$entry,$matches))continue;
			if (strpos($entry,'.gz')!==false)continue;
			
			/// preprocess the file to get the smiles and counterions
			addLog("Processing File");
			processFile($entry);
			addLog("Processing Patent data");
			// Add additional patent data
			processSureChembl($GLB_VAR['PUBLIC_SCHEMA'],$entry);
			if ($GLB_VAR['PRIVATE_ENABLED']=='T')
			processSureChembl($GLB_VAR['SCHEMA_PRIVATE'],$entry);
			
		}
	
		closedir($handle);
	}
	
addLog("Cleanup source");
	if (!runQueryNoRes("DELETE FROM sm_source WHERE sm_name_Status='F' AND source_Id = ".$SOURCE_ID))	failProcess($JOB_ID.'008','Unable to delete sm_source');
	if($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!runQueryNoRes("DELETE FROM ".$GLB_VAR['SCHEMA_PRIVATE'].".sm_source WHERE sm_name_status='F' AND source_id = ".$SOURCE_ID)) 	failProcess($JOB_ID."009",'Unable to update sm_source');
	}



	successProcess();


	




function processFile($fpath)
{
	global $GLB_VAR;
	global $FILECONV;
	global $PREF_SMI;
	global $FILECONV_COUNTER_PARAM;
	global $PREF_SMI_COUNTER;
	global $FILECONV_PARAM;
	global $JOB_ID;
	try{
		addLog( "######## PROCESSING ".$fpath);

		// We are going to put all the smiles into molecule.smi so we can standardize them
		$fpO=fopen('STD/molecule.smi','w');if (!$fpO)								failProcess($JOB_ID."A01",'Unable to open molecule.smi');

		// Open the file
		$fp=fopen($fpath,'r');if (!$fp)												failProcess($JOB_ID."A02",'Unable to open file '.$entry);

		/// Get the header
		$line=stream_get_line($fp,10000,"\n");
		$HEAD=array_flip(explode("\t",$line));
		if (!isset($HEAD['SureChEMBL_ID'])||!isset($HEAD['SMILES']))				failProcess($JOB_ID."A03",'Unable to find proper columns ');


		/// All counterions will be put in COUNTERION_MAP first (to make them unique) and then in counterion.smi
		$COUNTERION_MAP=array();
		$fpC=fopen('STD/counterion.smi','w');if (!$fpO)								failProcess($JOB_ID."A04",'Unable to open STD/counterion.smi');
		addLog("\tProcessing file\n");
		
		while(!feof($fp))
		{
			// Get the line
			$line=stream_get_line($fp,100000,"\n");if ($line=='')continue;
			$tab=explode("\t",$line);
			
			// Break the smiles by its molecule
			$tabS=explode(".",$tab[$HEAD['SMILES']]);

			// We are going to consider the longuest SMILES string as the primary molecule and the rest as counterions
			$MAX_LEN=0;
			foreach ($tabS as $t)$MAX_LEN=max($MAX_LEN,strlen($t));
			$ALT=array();
			$SMI='';
			$N_MAX=0;
			foreach ($tabS as $t)
			{
				if (strlen($t)==$MAX_LEN)
				{
					$N_MAX++;
					$SMI=$t;
				}
				else $ALT[]=$t;
			}
			// If there is more than one molecule of the same length, we skip it
			if ($N_MAX>1)continue;

			/// Sort the counterions
			sort($ALT);
			if ($ALT==array())$ALT[]='NULL';

			/// Put in file
			fputs($fpO,$tab[$HEAD['SMILES']].' '.$tab[$HEAD['SureChEMBL_ID']]."|".$tab[$HEAD['InChI']]."|".$tab[$HEAD['InChIKey']]."|".implode(".",$ALT)."|".$SMI."\n");

			/// Save the counterion in counterion_map. Note we use the smiles as key to make them unique
			$STR_C=implode(".",$ALT);
			if ($STR_C!='NULL')$COUNTERION_MAP[$STR_C.' '.$STR_C]='';
	
		}
		/// Write the counterions
		fputs($fpC,implode("\n",array_keys($COUNTERION_MAP)))."\n";
		unset($COUNTERION_MAP);
		fclose($fp);
		fclose($fpO);
		fclose($fpC);
		
		
		standardizeCompounds();


	}catch(Exception $e)
	{
		echo " ERROR ".$e->getCode()."\t".$e->getMessage()."\n";
		return false;
	}
	return true;
}

function processSureChembl($SCHEMA,$entry)
{
	global $SOURCE_ID;
	global $FILES;
	global $DBIDS;
	global $STATS;
	global $DB_INFO;
	global $GLB_VAR;
	global $JOB_ID;

	// Get the max id for the tables
	$DBIDS=array('patent_entry'=>-1,'sm_patent_map'=>-1);
	foreach (array_keys($DBIDS) as $P)
	{
		$ST='';
		/// Patent_entry is not in the public schema
		/// So for sm_patent_map, it is schema dependent
		if ($P=='sm_patent_map')$ST=$SCHEMA.'.';
		$res=runQuery("SELECT MAX(".$P."_ID) CO FROM ".$ST.$P);
		if ($res===false)													failProcess($JOB_ID."B01",'Unable to get max id from sm_entry');
		if (count($res)==0)$DBIDS[$P]=0;else $DBIDS[$P]=$res[0]['co'];
		
	}

	addLog("Processing file ".$entry);
	
	
	$fp=fopen($entry,'r');if (!$fp)										failProcess($JOB_ID."B02",'Unable to open file '.$entry);
	$line=stream_get_line($fp,1000,"\n");
	

	while(!feof($fp))
	{
	
		$line=stream_get_line($fp,1000000,"\n");
		if ($line=='')continue;
		/// We split the line
		$tab=explode("\t",$line);
		if (count($tab)!=8)continue;
		/// We get the ids for the compounds
		$CPD_NAMES[$tab[0]]=-1;
		$t2=explode("-",$tab[4]);
		$tab[4]=$t2[0].'-'.$t2[1];
		$PATENT_NAMES[$t2[0].'-'.$t2[1]]=-1;
		$TYPE=-1;
		switch ($tab[6])
		{
			case 1: $TYPE='D';break;//Description
			case 2: $TYPE='C';break;//Claims
			case 3: $TYPE='A';break;//Abstract
			case 4: $TYPE='T';break;//Title
			case 5: $TYPE='I';break;//Image (for patents after 2007)
			case 6: $TYPE='M';break;//MOL Attachment (US patents after 2007)
		}
		$BLOCK[$tab[0]][$tab[4]][$TYPE]=array($tab[7],-1);

		/// We process the block when it reaches 5000 compounds or 5000 patents
		if (count($CPD_NAMES)<5000 && count($PATENT_NAMES)<5000)continue;


		/// We get the ids for the compounds 
		$query="select sm_name,sm_entry_id FROM ".$SCHEMA.".sm_source WHERE sm_name  IN (";
		foreach ($CPD_NAMES as $N=>$K)$query.="'".prepString($N)."',";
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false)																	failProcess($JOB_ID."B04",'Unable to load sources ');
		$CPD_ID=array();
		foreach ($res as $line)
		{
			$CPD_ID[$line['sm_entry_id']][]=$line['sm_name'];
			$CPD_NAMES[$line['sm_name']]=$line['sm_entry_id'];
		}

		/// We get the ids for the patents
		$query='SELECT patent_entry_id, patent_application FROM patent_entry WHERE patent_application IN (';
		foreach ($PATENT_NAMES as $N=>$K)$query.="'".$N."',";
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false)																	failProcess($JOB_ID."B05",'Unable to load patents ');
		foreach ($res as $line)$PATENT_NAMES[$line['patent_application']]=$line['patent_entry_id'];
	
		$fpO=fopen('INSERT/patent_entry.csv','w');if (!$fpO)								failProcess($JOB_ID."B06",'Unable to open patent_entry ');
		$BLOCK_IDS=array();
		
		foreach ($BLOCK as $CPD_NAME=>&$LIST_PATS)
		{
			/// Per standarization step, molecules should be in the database
			// IF we don't find them, then we skip them
			if (!isset($CPD_NAMES[$CPD_NAME])|| $CPD_NAMES[$CPD_NAME]==-1)continue;
			/// We check the patents and save the new ones
			foreach ($LIST_PATS as $PATENT_ID=>&$DESCS)
			{
				/// Patent not found, we add it to the the database
				if ($PATENT_NAMES[$PATENT_ID]==-1)
				{
					++$DBIDS['patent_entry'];
					fputs($fpO,$DBIDS['patent_entry']."\t".$PATENT_ID."\n");
					$PATENT_NAMES[$PATENT_ID]=$DBIDS['patent_entry'];
				}
				foreach ($DESCS as $DESC=>$CO)
				{
					$BLOCK_IDS[$CPD_NAMES[$CPD_NAME]][$PATENT_NAMES[$PATENT_ID]][$DESC]=$CO;
				}

			}
		}
		fclose($fpO);


		$query="SELECT sm_patent_map_id, sm_entry_id, patent_entry_id, field, field_freq
			FROM ".$SCHEMA.".sm_patent_map 
			WHERE (sm_entry_id, patent_entry_id) IN (";
		$VALID=false;
		foreach ($BLOCK_IDS as $CPD_ID=>$BL) 
		{
			foreach ($BL as $PAT_ID=>$BLT)
			{
				$VALID=true;
				$query.='('.$CPD_ID.','.$PAT_ID.'),';
			}
		}
		
		$PATENT_ID=array_flip($PATENT_NAMES);
		if ($VALID){
			$res=runQuery(substr($query,0,-1).') ORDER BY patent_entry_id ASC');
			if ($res===false)																	failProcess($JOB_ID."B07",'Unable to load sm patent map ');

			foreach ($res as $line)
			{
				if (!isset($BLOCK_IDS[$line['sm_entry_id']]))continue;
				
				$CPD_RECS=&$BLOCK_IDS[$line['sm_entry_id']];
				if (!isset($CPD_RECS[$line['patent_entry_id']]))continue;
				
				$CPD_PATS=&$CPD_RECS[$line['patent_entry_id']];
				if (!isset($CPD_PATS[$line['field']]))continue;
				
				$CPD_PAT_TYPE=&$CPD_PATS[$line['field']];
				$CPD_PAT_TYPE[1]=$line['sm_patent_map_id'];
			}
		}
		

		$fpO=fopen('INSERT/sm_patent_map.csv','w');if (!$fpO)										failProcess($JOB_ID."B08",'Unable to open sm_patent_map ');
		foreach ($BLOCK_IDS as $CPD_ID=>&$LIST_PATS)
		{
			foreach ($LIST_PATS as $PATENT_ID=>&$DESCS)
			{
				foreach ($DESCS as $DESC=>$CO)
				{
					if ($CO[1]!=-1)continue;
					//	echo $CPD_ID."\t".$PATENT_ID."\t".$DESC."\t".$CO[0]."\n";
					++$DBIDS['sm_patent_map'];
					fputs($fpO,$DBIDS['sm_patent_map']."\t".
						$CPD_ID."\t".
						$PATENT_ID."\t".
						'"'.str_replace('"','""',$DESC).'"'."\t".
						$CO[0]."\n");			
				}

			}
		}
		fclose($fpO);

		$command='\COPY patent_entry (patent_entry_id, patent_application) FROM \''."INSERT/patent_entry.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		if ($return_code !=0 )																					failProcess($JOB_ID."B09",'Unable to insert parent_Entry'); 


		$command='\COPY '.$SCHEMA.'.sm_patent_map(sm_patent_map_id,sm_entry_id,patent_entry_Id, field,field_freq) FROM \''."INSERT/sm_patent_map.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		if ($return_code !=0 )																					failProcess($JOB_ID."B10",'Unable to insert sm_entry'); 

		$BLOCK=array();
		$CPD_NAMES=array();
		$CPD_ID=array();
		$PATENT_NAMES=array();
		$PATENT_ID=array();
	
	}
	fclose($fp);
	

}



?>
