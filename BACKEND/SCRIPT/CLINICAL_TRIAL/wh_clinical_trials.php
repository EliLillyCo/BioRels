<?php
ini_set('memory_limit','4000M');
/**
 SCRIPT NAME: wh_clinical_trial
 PURPOSE:     Download and save clinical trial information
 
*/

$JOB_NAME='wh_clinical_trials';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);

/// File/parameters verifications:
addLog("Static file check");
	$PRD_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'];
	if (!is_dir($PRD_DIR))			 																		failProcess($JOB_ID."001",'Unable to find '.$PRD_DIR.' directory');
	if (!isset($GLB_VAR['LINK']['FTP_CLINICAL_TRIAL']))														failProcess($JOB_ID."002",'FTP_CLINICAL_TRIAL path no set');

addLog("Create directory");
	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];						if (!is_dir($W_DIR)) 					failProcess($JOB_ID."003",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to find and create '.$W_DIR);
	$ARCHIVE=$W_DIR.'/ARCHIVE';										if (!is_dir($ARCHIVE)&&!mkdir($ARCHIVE))failProcess($JOB_ID."005",'Unable to create '.$ARCHIVE.' directory');

	//$W_DIR.='2023-12-12';
	$W_DIR.=getCurrDate();			   								if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."006",'Unable to create new process dir '.$W_DIR);
						   											if (!chdir($W_DIR)) 					failProcess($JOB_ID."007",'Unable to access process dir '.$W_DIR);

	$PROCESS_CONTROL['DIR']=getCurrDate();
	$DATE=$JOB_INFO['TIME']['DEV_DIR'];

	addLog("Working directory: ".$W_DIR);		
	
	addLog("Loading static data");

	// STATIC_DATA will be used to replace some values in the JSON file
$STATIC_DATA=array(
	'STATUS'=>array(
		'ACTIVE_NOT_RECRUITING'=>'Active, not recruiting',
		'COMPLETED'=>'Completed',
		'TEMPORARILY_NOT_AVAILABLE'=>'Temporarily not available' ,
		'AVAILABLE'=>'Available',
		'NOT_YET_RECRUITING'=>'Not yet recruiting',
		'TERMINATED'=>'Terminated',
		'UNKNOWN'=>'Unknown',
		'ENROLLING_BY_INVITATION'=>'Enrolling by invitation',
		'RECRUITING'=>'Recruiting',
		'APPROVED_FOR_MARKETING'=>'Approved for marketing',
		'WITHHELD'=>'Withheld',
		'SUSPENDED'=>'Suspended',
		'NO_LONGER_AVAILABLE'=>'No longer available'
	),
	'PHASE'=>array(
		'NA'=>'N/A',
		'EARLY_PHASE1'=>'0.5',
		'PHASE3'=>'3',
		'PHASE2'=>'2',
		'PHASE4'=>'4',
		'PHASE1'=>'1',
		''=>'N/A')
);

	// Max ID for each table
	$DBIDS=array(
		'clinical_trial'=>-1,
		'clinical_trial_alias'=>-1,
		'company_entry'=>-1,
		'clinical_trial_intervention'=>-1,
		'clinical_trial_arm'=>-1,
		'clinical_trial_arm_intervention_map'=>-1,
		'clinical_trial_condition'=>-1,
		'clinical_trial_intervention_drug_map'=>-1
	);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$res=array();
		$res=runQuery('SELECT MAX('.$TBL.'_id) co FROM '.$TBL);
		if ($res===false)																failProcess($JOB_ID."008",'Unable to get Max ID for '.$TBL);
		$DBIDS[$TBL]=($res[0]['co']=='')?0:$res[0]['co'];
	}

	$DEP_TABLES=getDepTables('clinical_trial',$GLB_VAR['DB_SCHEMA']);
	$SOURCE_ID=getSource("ClinicalTrials");
	
	
	// list of column for each table - not used
	$COL_ORDER=array(
		'clinical_trial'=>'(clinical_trial_id,trial_id,clinical_phase,clinical_status,start_date,source_id,brief_title,official_title,org_study_id,brief_summary,details)',
		'clinical_trial_alias'=>'(clinical_trial_alias_id,clinical_trial_id,alias_name,alias_type)',
		'clinical_trial_condition'=>'(clinical_trial_condition_id,clinical_trial_id,disease_entry_id,condition_name)',
		'clinical_trial_intervention'=>'(clinical_trial_intervention_id,clinical_trial_id,intervention_type,intervention_name,intervention_description)',
		'clinical_trial_arm'=>'(clinical_trial_arm_id,clinical_trial_id,arm_type,arm_label,arm_description)',
		'clinical_trial_arm_intervention_map'=>'(clinical_trial_arm_intervention_map_id,clinical_trial_id,clinical_trial_arm_id,clinical_trial_intervention_id)',
		'clinical_trial_intervention_drug_map'=>'(clinical_trial_intervention_drug_map_id,clinical_trial_intervention_id,drug_entry_id,source_id)',
		'clinical_trial_company_map'=>'(clinical_trial_id,company_entry_id)'
	);
			
	
	// Clean the string to avoid special characters
	function cleanName($N)
	{
		$LIST=array('-',',');
		foreach ($LIST as $L)$N=str_replace($L,' ',$N);
		$N=str_replace('  ',' ',$N);
		return strtolower($N);

	}



echo "\tPreparing statements\n";

	$PREP_STAT=array();
	$FILES=array();
	$FILE_STATUS=array();
	
	$PREP_STAT['clinical_trial']=$DB_CONN->prepare("INSERT INTO clinical_trial (clinical_trial_id   ,
		trial_id            ,
		clinical_phase      ,
		clinical_status     ,
		start_date          ,
		source_id           ,
		brief_title         ,
		official_title      ,
		org_study_id        ,
		brief_summary       ,
		details) VALUES (:dbid,:nctid,:phase,:status,:date,:source,:brief,:official,:org_id,:summary,:json)");
		if ($PREP_STAT['clinical_trial']===false)failProcess($JOB_ID."009",'Unable to prepare statement for clinical_trial');


	$PREP_STAT['clinical_trial_arm']=$DB_CONN->prepare('INSERT INTO clinical_trial_arm 
		(clinical_trial_arm_id,clinical_trial_id,arm_label,arm_type,arm_description)
		VALUES (:dbid,:trial_id,:label,:type,:desc)');
		if ($PREP_STAT['clinical_trial_arm']===false)failProcess($JOB_ID."010",'Unable to prepare statement for clinical_trial_arm');

	$PREP_STAT['clinical_trial_intervention']=$DB_CONN->prepare('INSERT INTO clinical_trial_intervention 
		(clinical_trial_intervention_id,clinical_trial_id,intervention_type,intervention_name,intervention_description) 
		VALUES (:dbid,:trial_id,:type,:name,:desc)');
		if ($PREP_STAT['clinical_trial_intervention']===false)failProcess($JOB_ID."011",'Unable to prepare statement for clinical_trial_intervention');

	$PREP_STAT['clinical_trial_intervention_drug_map']=$DB_CONN->prepare('INSERT INTO clinical_trial_intervention_drug_map
		 (clinical_trial_intervention_drug_map_id,clinical_trial_intervention_id,drug_entry_id,source_id)
		VALUES (:dbid,:inter_id,:drug_id,:source_id)');
		if ($PREP_STAT['clinical_trial_intervention_drug_map']===false)failProcess($JOB_ID."012",'Unable to prepare statement for clinical_trial_intervention_drug_map');

	$PREP_STAT['clinical_trial_arm_intervention_map']=$DB_CONN->prepare('INSERT INTO clinical_trial_arm_intervention_map 
			(clinical_trial_arm_intervention_map_id,clinical_trial_id,clinical_trial_arm_id,clinical_trial_intervention_id)
			VALUES (:dbid,:trial_id,:arm_id,:inter_id)');
		if ($PREP_STAT['clinical_trial_arm_intervention_map']===false)failProcess($JOB_ID."013",'Unable to prepare statement for clinical_trial_arm_intervention_map');

	$PREP_STAT['clinical_trial_alias']=$DB_CONN->prepare('INSERT INTO clinical_trial_alias VALUES (:dbid,:trial_id,:val,:type)');
		if ($PREP_STAT['clinical_trial_alias']===false)failProcess($JOB_ID."014",'Unable to prepare statement for clinical_trial_alias');
	
	
		if (!is_dir('INSERT') && !mkdir('INSERT'))failProcess($JOB_ID."015",'Unable to create INSERT directory');
		foreach ($COL_ORDER as $TBL=>&$POS)
		{
			$FILE_STATUS[$TBL]=false;
			$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');
			if ($FILES[$TBL]===false)failProcess($JOB_ID."016",'Unable to create INSERT/'.$TBL.'.csv file');
		}
	
	


addLog("\tLoading company names");
	$res=runQuery("SELECT company_name, company_entry_id FROM company_entry");
	if ($res===false)failProcess($JOB_ID."015",'Unable to load company names');
	foreach ($res as $line)$COMPANY[strtolower($line['company_name'])]=$line['company_entry_id'];


addLog("\tLoading disease names");
	$res=runQuery("SELECT disease_entry_id, disease_name FROM disease_entry");
	if ($res===false)failProcess($JOB_ID."016",'Unable to load disease names');
	foreach ($res as $line)
	{
		$N=cleanName($line['disease_name']);
		if (!isset($DISEASES['MAIN'][$N])
		|| !in_array($line['disease_entry_id'],$DISEASES['MAIN'][$N]))
		$DISEASES['MAIN'][$N][]=$line['disease_entry_id'];
	}


addLog("\tLoading disease synonyms");
	$res=runQuery("SELECT disease_entry_id, syn_value FROM disease_syn");
	if ($res===false)failProcess($JOB_ID."017",'Unable to load disease synonyms');
	foreach ($res as $line)
	{
		$N=cleanName($line['syn_value']);
		if (!isset($DISEASES['SYN'][$N])
		|| !in_array($line['disease_entry_id'],$DISEASES['SYN'][$N]))
		$DISEASES['SYN'][$N][]=$line['disease_entry_id'];
	}
addLog("\tLoading drug names");
	$res=runQuery("SELECT drug_entry_id, drug_primary_name FROM drug_entry");
	if ($res===false)failProcess($JOB_ID."012",'Unable to load drug names');
	foreach ($res as $line)$DRUG['MAIN'][strtolower($line['drug_primary_name'])][]=$line['drug_entry_id'];

addLog("\tLoading drug synonyms");
	$res=runQuery("SELECT drug_entry_id, drug_name FROM drug_name");
	if ($res===false)failProcess($JOB_ID."013",'Unable to load drug names');
	foreach ($res as $line)$DRUG['SYN'][strtolower($line['drug_name'])][]=$line['drug_entry_id'];


	$NEWS_INFO=array();

addLog("Initializing query");

	// The baseline of the query:
	$START_WEBLINK='https://www.clinicaltrials.gov/api/v2/studies?format=json&pageSize=1000';

	/// Now we create the query depending on the date
	$WEBLINK=$START_WEBLINK;
	if ($DATE!='-1')$WEBLINK.='&query.term=AREA%5BLastUpdatePostDate%5DRANGE%5B'.$DATE.'%2CMAX%5D';
	/// We want to know how many file we will download
	$WEBLINK.='&countTotal=true';

	// We query the API
	exec('wget -O search "'.$WEBLINK.'"',$res,$return_code);
	if ($return_code!=0) failProcess($JOB_ID."014",'Unable to query API');

	// We load the JSON file
	$DATA=json_decode(file_get_contents('search'),true);
	if ($DATA===null) failProcess($JOB_ID."015",'Unable to parse JSON file');

	// Now we can know how many files we will download
	$N_FILE_TO_DO=ceil($DATA['totalCount']/1000);
addLog("Total count: ".$DATA['totalCount']."\n");
	
	/// Process the first batch
	processJson($DATA,1);
	
	// If there is no more file to download we can exit
	if (!isset($DATA['nextPageToken']) || $DATA['nextPageToken']=='')successProcess();

	// We will download the rest of the files
	$N_FILE=1;
	do
	{
		addLog("###################### ".$N_FILE."/".$N_FILE_TO_DO." ######################");
		++$N_FILE;
		
		$WEBLINK=$START_WEBLINK;
		if ($DATE!='-1')$WEBLINK.='&query.term=AREA%5BLastUpdatePostDate%5DRANGE%5B'.$DATE.'%2CMAX%5D';
	
		$WEBLINK.='&pageToken='.$DATA['nextPageToken'];
		
		exec('wget -q -O search_'.$N_FILE.' "'.$WEBLINK.'"',$res,$return_code);
		
		if ($return_code!=0) failProcess($JOB_ID."016",'Unable to query API');

		$DATA=json_decode(file_get_contents('search_'.$N_FILE),true);

		echo count($DATA['studies'])."\t".$DATA['nextPageToken']."\n";
		
		processJson($DATA,$N_FILE);
		
		addLog("###################### END ".$N_FILE."/".$N_FILE_TO_DO." ######################");
	}while (isset($DATA['nextPageToken']) && $DATA['nextPageToken']!='');



	if ($DATE!=-1)createNews($NEWS_INFO);

	successProcess();
	exit;





function processJson(&$JSON_DATA,$N_FILE)
{
	global $NEWS_INFO;
	global $COMPANY;
	global $SOURCE_ID;
	global $DB_CONN;
	global $DBIDS;
	global $STATIC_DATA;
	global $DATE;
	
	// DATA will have the current content of the database
	// each key will be the trial_id
	$DATA=array();$N=0;
	foreach ($JSON_DATA['studies'] as &$ENTRY)
	{
		$DATA[$ENTRY['protocolSection']['identificationModule']['nctId']]=array();
	}

	// We preload the data from the database
	preloadBatch($DATA,$N_FILE);
	
	echo "\tProcessing data\n";
	$N=0;
	foreach ($JSON_DATA['studies'] as &$ENTRY)
	{
		++$N;
		
		

		$DESIGN=&$ENTRY['protocolSection']['designModule'];
		$STATUS_MODULE=&$ENTRY['protocolSection']['statusModule'];
		$ID_MODULE=&$ENTRY['protocolSection']['identificationModule'];
		$DESC_MODULE=&$ENTRY['protocolSection']['descriptionModule'];
		$ARMS_MODULE=&$ENTRY['protocolSection']['armsInterventionsModule'];
		if (isset($DESIGN['phases']))
		{
	
		if (isset($STATIC_DATA['PHASE'][$DESIGN['phases'][0]]))$phase=$STATIC_DATA['PHASE'][$DESIGN['phases'][0]];
		else $phase= $DESIGN['phases'][0];	
		}
		else $phase='N/A';
		
		$status=$STATUS_MODULE['overallStatus'];
		if (isset($STATIC_DATA['STATUS'][$STATUS_MODULE['overallStatus']]))$status=$STATIC_DATA['STATUS'][$STATUS_MODULE['overallStatus']];
	
		
		if (isset($STATUS_MODULE['startDateStruct'])) $start_date=date('Y-m-d',strtotime($STATUS_MODULE['startDateStruct']['date']));
		else 										  $start_date=date('Y-m-d',strtotime($STATUS_MODULE['studyFirstSubmitDate']));
		
		$nct_id=$ID_MODULE['nctId'];
		
		
		/// In some instances the interventions are duplicated so we remove the duplicates
		if (isset($ARMS_MODULE['interventions']))
		{
		
			foreach ($ARMS_MODULE['interventions'] as $K=>&$INTERVENTION)
			foreach ($ARMS_MODULE['interventions'] as $V=>&$INTERVENTION_2)
			{
				
				if ($V==$K)continue;
				
				if (!isset($INTERVENTION['description']))$INTERVENTION['description']='';
				if (!isset($INTERVENTION_2['description']))$INTERVENTION_2['description']='';
				if ($INTERVENTION['type']!=$INTERVENTION_2['type'])continue;
				if ($INTERVENTION['name']!=$INTERVENTION_2['name'])continue;
				if ($INTERVENTION['description']!=$INTERVENTION_2['description'])continue;
			
				foreach ($INTERVENTION_2['armGroupLabels'] as $lab)$INTERVENTION['armGroupLabels'][]=$lab;
				echo "REMOVE DUPLICATED INTERVENTION \n";
				unset($ARMS_MODULE['interventions'][$V]);
			}
		}

		//If you want to look at a specific trial
		//if ($nct_id!='NCT00897871')continue;

		// We create the entry for the clinical trial table if it doesn't exist
		if (!isset($DATA[$nct_id]))
		{
			
			insertRecord($ENTRY,$N_FILE);
		}else 
		// If the record already exist we compare the data
		{
			
			compareRecord($ENTRY,$DATA,$N_FILE);
		}
	

	}
	if ($DATE==-1)
	{
		chdir('./INSERT');
		pushFilesToDB(false);
		chdir('..');
	}
	// Then we update drug_disease and clinical_trial_Drug tables
	updateDrugDisease($JSON_DATA,$N_FILE);
	
}
	
	
function preloadBatch(&$DATA,$N_FILE)
 {
	echo "\tFILE ".$N_FILE." - Preloading data\n";
	global $STATIC_DATA;
	echo "\t\tPreloading clinical_trial\n";
	$res=runQuery("SELECT * ,MD5(details::text) hash_v FROM clinical_trial ca where trial_id IN ('".str_replace(',',"','",implode(',',array_keys($DATA)))."')");
	if ($res===false) failProcess($JOB_ID."017",'Unable to preload clinical_trial');
	$DATA=array();
	$MAP=array();
	foreach ($res as $line)
	{
		$MAP[$line['clinical_trial_id']]=$line['trial_id'];
		$DATA[$line['trial_id']]=array('INFO'=>array('clinical_phase'=>$line['clinical_phase'],
		'clinical_status'=>$line['clinical_status'],
		'start_date'=>$line['start_date'],
		'brief_title'=>$line['brief_title'],
		'official_title'=>$line['official_title'],
		'org_study_id'=>$line['org_study_id'],
		'brief_summary'=>$line['brief_summary'],
		'hash'=>$line['hash_v'],
		'DB_ID'=>$line['clinical_trial_id'],
		'DB_STATUS'=>'FROM_DB'
		),'ALIAS'=>array());
		
	}
	
	if ($MAP==array())
	{
		echo "\tFILE ".$N_FILE." - No entries - End Preloading data\n";
		return;
	} 



	echo "\t\tPreloading clinical_trial_alias\n";
	$res=runQuery("SELECT * FROM clinical_trial_alias where clinical_trial_id in (".implode(',',array_keys($MAP)).')');
	if ($res===false) failProcess($JOB_ID."018",'Unable to preload clinical_trial_alias');
	foreach ($res as $line)
	{
		$DATA[$MAP[$line['clinical_trial_id']]]['ALIAS'][$line['alias_name']]=array('alias_type'=>$line['alias_type'],'DB_STATUS'=>'FROM_DB');
	}



	echo ("\t\tPreloading clinical_trial_condition\n");
	$res=runQuery("SELECT * FROM clinical_trial_condition where clinical_trial_id in (".implode(',',array_keys($MAP)).')');
	if ($res===false) failProcess($JOB_ID."019",'Unable to preload clinical_trial_condition');
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$MAP[$line['clinical_trial_id']]]['DISEASE'][]=$line;
	}



	echo "\t\tPreloading clinical_trial_company\n";
	$res=runQuery("SELECT * FROM clinical_trial_company_map where clinical_trial_id in (".implode(',',array_keys($MAP)).')');
	if ($res===false) failProcess($JOB_ID."020",'Unable to preload clinical_trial_company_map');
	foreach ($res as $line)
	{
		$DATA[$MAP[$line['clinical_trial_id']]]['COMPANY'][$line['company_entry_id']]=array('DB_STATUS'=>'FROM_DB');
	}

	echo "\t\tPreloading clinical trial intervention\n";
	$res=runQuery("SELECT * FROM clinical_trial_intervention where clinical_trial_id in (".implode(',',array_keys($MAP)).')');
	
	$MAP_INTER=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$MAP[$line['clinical_trial_id']]]['INTERVENTION'][$line['clinical_trial_intervention_id']]=$line;
		$MAP_INTER[$line['clinical_trial_intervention_id']]=$MAP[$line['clinical_trial_id']];
	}
	if ($MAP_INTER!=array())
	{
		echo "\t\tPreloading clinical trial intervention drug map\n";
		$res=runQuery("SELECT * FROM clinical_trial_intervention_drug_map where clinical_trial_intervention_id IN (".implode(',',array_keys($MAP_INTER)).')');
		if ($res===false) failProcess($JOB_ID."021",'Unable to preload clinical_trial_intervention_drug_map');
		foreach ($res as $line)
		{
			$DATA[$MAP_INTER[$line['clinical_trial_intervention_id']]]['INTERVENTION'][$line['clinical_trial_intervention_id']]['DRUG'][$line['drug_entry_id']]=array('DB_STATUS'=>'FROM_DB','SOURCE_ID'=>$line['source_id']);
		}
	}

	echo "\t\tPreloading clinical trial arm\n";
	$res=runQuery("SELECT * FROM clinical_trial_arm where clinical_trial_id in (".implode(',',array_keys($MAP)).')');
	if ($res===false) failProcess($JOB_ID."022",'Unable to preload clinical_trial_arm');
	echo count($res)."\n";
	foreach ($res as $line)
	{
		$DATA[$MAP[$line['clinical_trial_id']]]['ARM'][$line['clinical_trial_arm_id']]=array('arm_label'=>$line['arm_label'],'arm_type'=>$line['arm_type'],'arm_description'=>$line['arm_description'],'DB_STATUS'=>'FROM_DB');
	}

	echo "\t\tPreloading clinical trial arm intervention map\n";
	$res=runQuery("SELECT * FROM clinical_trial_arm_intervention_map where clinical_trial_id in (".implode(',',array_keys($MAP)).')');
	if ($res===false ) failProcess($JOB_ID."023",'Unable to preload clinical_trial_arm_intervention_map');
	echo count($res)."\n";
	foreach ($res as $line)
	{
		$DATA[$MAP[$line['clinical_trial_id']]]['ARM_INTERVENTION'][$line['clinical_trial_arm_id']][$line['clinical_trial_intervention_id']]=array('DB_STATUS'=>'FROM_DB','DB_ID'=>$line['clinical_trial_arm_intervention_map_id']);
	}

	echo "\tFILE ".$N_FILE." - End Preloading data\n";
 }




function updateDrugDisease(&$JSON_DATA)
{
	echo "\tFILE  - Updating drug_disease and clinical_trial_drug\n";
	// Get the list of clinical trial database ID
	$LIST_CLIN_ID=array();
	foreach ($JSON_DATA['studies'] as &$ENTRY)
	{
	   $ID_MODULE=&$ENTRY['protocolSection']['identificationModule'];
		$LIST_CLIN_ID[]="'".$ID_MODULE['nctId']."'";
	}
	 
	// Get the max ID for drug_disease and clinical_trial_drug
	$DBIDS=array('drug_disease'=>-1,'clinical_trial_drug'=>-1);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$res=array();
		$res=runQuery('SELECT MAX('.$TBL.'_id) co FROM '.$TBL);
		if ($res===false)																failProcess($JOB_ID."008",'Unable to get Max ID for '.$TBL);
		
		$DBIDS[$TBL]=($res[0]['co']=='')?0:$res[0]['co'];
	}

	// Get the triplet clinical trial id, drug and disease for the experimental arms of a trial
	$res=runQuery("SELECT DISTINCT cta.clinical_trial_id, clinical_phase, drug_entry_id, disease_entry_id
	FROM clinical_trial_condition ctc,clinical_trial ct, clinical_trial_arm cta,clinical_trial_arm_intervention_map ctai, clinical_trial_intervention_drug_map cd 
	where ctc.clinical_trial_id =cta.clinical_trial_id  and ctc.clinical_trial_id =ct.clinical_trial_id 
	AND cta.clinical_trial_arm_id = ctai.clinical_trial_arm_id 
	AND ctai.clinical_trial_intervention_id = cd.clinical_trial_intervention_id 
	AND arm_type='EXPERIMENTAL' 
	and disease_Entry_Id IS NOT NULL 
	and drug_entry_id IS NOT NULL
	AND trial_id IN (".implode(',',$LIST_CLIN_ID).' )');
	if ($res === false)failProcess($JOB_ID."030",'Unable to get triplet clinical trial id, drug and disease for the experimental arms of a trial');
	$DRUG_DISEASES=array();

	// Storing the max phase for each drug/disease pair
	$DRUG_MAX=array();
	// clinical_trial_id as keys
	$LIST_CLIN_DBID=array();

	// drug disease records from db
	$DD_INFO=array();

	foreach ($res as $line)
	{
		/// We are not interested in trials with no phase
		if ($line['clinical_phase']=='N/A')continue;
		$LIST_CLIN_DBID[$line['clinical_trial_id']]=true;
		$DRUG_DISEASES[$line['clinical_trial_id']][]=$line;
		
		if (!isset($DRUG_MAX[$line['drug_entry_id']][$line['disease_entry_id']]))$DRUG_MAX[$line['drug_entry_id']][$line['disease_entry_id']]=$line['clinical_phase'];
		else $DRUG_MAX[$line['drug_entry_id']][$line['disease_entry_id']]=max($line['clinical_phase'],$DRUG_MAX[$line['drug_entry_id']][$line['disease_entry_id']]);
	}

	if ($DRUG_MAX!=array())
	{
		// Get the current drug_disease_id and max phase for each drug/disease pair from the database
		$query='SELECT * FROM drug_disease where (drug_entry_id,disease_entry_id) IN (';
		foreach ($DRUG_MAX as $DE_ID=>&$LIST_D)
		foreach ($LIST_D as $DS_ID=>&$MAX_PHASE)
		{
			$query.="(".$DE_ID.",".$DS_ID."),";
		}
		
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false) failProcess($JOB_ID."031",'Unable to get drug_disease_id and max phase for each drug/disease pair from the database');
		foreach ($res as $line)
		{
			$DD_INFO[$line['drug_entry_id']][$line['disease_entry_id']]=array($line['drug_disease_id'],$line['max_disease_phase']);
		}
	}
	
	/// Will store drug_diseases that needs to be updated, group by phase
	/// That makes less queries at the end
	$UPDS=array();

	/// We are first going to process drug_disease records

	foreach ($DRUG_MAX as $DE_ID=>&$LIST_D)
	foreach ($LIST_D as $DS_ID=>&$MAX_PHASE)
	{
		//echo $DE_ID."\t".$DS_ID."\t".$MAX_PHASE."\t";

		//$DD_INFO is the list of drug_disease_id and max phase for each drug/disease pair in the db
		// so if the pair is not in the db we create a new record
		if (!isset($DD_INFO[$DE_ID][$DS_ID]))
		{
			
			//echo "NEW";
			++$DBIDS['drug_disease'];
			// and we store the new record in $DD_INFO
			$DD_INFO[$DE_ID][$DS_ID]=array($DBIDS['drug_disease'],$MAX_PHASE);
			
			if (!runQueryNoRes("INSERT INTO drug_disease (drug_disease_id,drug_entry_id,disease_Entry_id,max_disease_phase) VALUES (".$DBIDS['drug_disease'].','.$DE_ID.','.$DS_ID.','.$MAX_PHASE.')'))
			{
				//echo "ERROR";
				failProcess($JOB_ID."032",'Unable to insert new drug_disease record');
			}
		}
		/// If the max phase is higher than the current value we need to update
		else if ($DD_INFO[$DE_ID][$DS_ID][1]<$MAX_PHASE)
		{
			//echo "DIFF";
			$UPDS[$MAX_PHASE][]=$DD_INFO[$DE_ID][$DS_ID][0];
		}	
		//echo "\n";
	}
	/// Then we update the max phase for each drug_disease_id. We group by phase to make less queries
	foreach ($UPDS as $PHASE=>&$LIST)
	{
		if (!runQueryNoRes("UPDATE drug_disease set max_disease_phase =".$PHASE." WHERE drug_disease_id IN (".implode(',',$LIST).')'))
			failProcess($JOB_ID."033",'Unable to update drug_disease record');
		
	}


	//Now we can process clinical_trial_drug

	$res=runQuery("SELECT * FROM clinical_trial_drug ctd where clinical_trial_id IN (".implode(',',array_keys($LIST_CLIN_DBID)).' )');
	if ($res===false) failProcess($JOB_ID."034",'Unable to get clinical_trial_drug records');


	$DB_DATA=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DB_DATA[$line['clinical_trial_id']][]=$line;
	}
	
	/// Now we compare db vs file
	foreach ($DRUG_DISEASES as $CLIN_DBID=>&$DRUG_DISEASE_CLIN)
	{
		//print_R($DRUG_DISEASE_CLIN);
		foreach ($DRUG_DISEASE_CLIN as &$FILE_DD)
		{
			$FOUND=false;
			if (!isset($DB_DATA[$CLIN_DBID]))
			{
				//echo "\tNO DB_DATA\n";
				++$DBIDS['clinical_trial_drug'];
				if (!runQueryNoRes("INSERT INTO clinical_Trial_drug (clinical_trial_drug_id, clinical_trial_id, drug_disease_id) 
				VALUES (".$DBIDS['clinical_trial_drug'].','.$CLIN_DBID.','.$DD_INFO[$FILE_DD['drug_entry_id']][$FILE_DD['disease_entry_id']][0].')'))
					failProcess($JOB_ID."035",'Unable to insert new clinical_trial_drug record');
				
				continue;
			}
			foreach ($DB_DATA[$CLIN_DBID] as &$DB_DD)
			{
				//echo $FILE_DD['drug_entry_id']."\t".$FILE_DD['disease_entry_id']."\t".$DD_INFO[$FILE_DD['drug_entry_id']][$FILE_DD['disease_entry_id']]."<>".$DB_DD['drug_disease_id']."\n";
				if ($DB_DD['drug_disease_id']!=$DD_INFO[$FILE_DD['drug_entry_id']][$FILE_DD['disease_entry_id']][0])continue;
				$DB_DD['DB_STATUS']='VALID';
				$FOUND=true;
				break;
			}
			if ($FOUND)continue;
			++$DBIDS['clinical_trial_drug'];
			if (!runQueryNoRes("INSERT INTO clinical_Trial_drug (clinical_trial_drug_id, clinical_trial_id, drug_disease_id)
			 VALUES (".$DBIDS['clinical_trial_drug'].','.$CLIN_DBID.','.$DD_INFO[$FILE_DD['drug_entry_id']][$FILE_DD['disease_entry_id']][0].')'))
			 				failProcess($JOB_ID."036",'Unable to insert new clinical_trial_drug record');
				
			
		}
	}



}


 function findDisease($NAME)
 {
	global $DISEASES;$n=cleanName($NAME);
	echo ("\t\tSearching disease: ".$NAME.'=>'.$n."\n");
	// We first check if the given name is the main name of a disease
	if (isset($DISEASES['MAIN'][$n]))
	{
		echo ("\t\t\tFOUND EXACT MAIN DISEASE ".$n."\n");
		if (count($DISEASES['MAIN'][$n])==1)return $DISEASES['MAIN'][$n][0];
		echo "\t\t\tMULTIPLE MATCHES - CONTINUE\n";
	}
	// We first check if the given name is one of the synonyms
	if (isset($DISEASES['SYN'][$n]))
	{
		echo ("\t\t\tFOUND SYNONYM DISEASE ".$n."\n");
		if (count($DISEASES['SYN'][$n])==1)return $DISEASES['SYN'][$n][0];
		echo "\t\t\tMULTIPLE MATCHES - CONTINUE\n";
	}

	// Sometimes words are mixed up so we break the name into words and sort them
	// the resulting array MUST be an exact match to a disease name generated array
	echo "\t\t\tTEST BY MIXING WORDS\n";
	$tab=explode(" ",$n);
	$tab=array_filter($tab);
	sort($tab);

	
	foreach ($DISEASES['MAIN'] as $K=>$V)
	{
		$tab2=explode(" ",$K);
		sort($tab2);
		if ($tab!=$tab2)continue;
		echo ("\t\t\tEXACT MATCH FOUND ".$K."\n");
		return $V[0];
		
	}
	foreach ($DISEASES['SYN'] as $K=>$V)
	{
		$tab2=explode(" ",$K);
		sort($tab2);
		if ($tab!=$tab2)continue;
		echo ("\t\t\tSYNONYM MATCH FOUND ".$K."\n");
		return $V[0];
	}
	return 'NULL';
 }





function findDrug($NAME)
{
	echo ("\t\t\tSearching drug: ".$NAME."\n");
	global $DRUG;
	//Clean up

	$RULES=array('[',']','(',')',',');
	foreach ($RULES as $R)
	{
		$T=str_replace($R,'',$NAME);
		$NAME=$T;
	}
	// We first check if the given name is the main name of a drug
	$n=strtolower($NAME);
	if (isset($DRUG['MAIN'][$n]))
	{
		if (count($DRUG['MAIN'][$n])==1)
		{
			echo ("\t\t\t\tFOUND RULE1 ".$NAME."\n");
			return $DRUG['MAIN'][$n];
		}
	}
	// We first check if the given name is one of the synonyms
	if (isset($DRUG['SYN'][$n]))
	{
		if (count($DRUG['SYN'][$n])==1)
		{
			echo ("\t\t\t\tFOUND RULE3 ".$n."\n");
			return $DRUG['SYN'][$n];
		}
	}
	// Sometimes it is not a drug name but a sentence, so we break the name into words
	// we only test long names > 7 characters
	$tab=explode(" ",$NAME);
	
	if (count($tab)==1)
	{
		$tab=explode("/",$NAME);
		if (count($tab)==1)return array();
	}
	echo "\t\t\t\tTesting individual words\n";
	$final_list=array();
	foreach ($tab as $l)
	{
		if (strlen($l)<7) continue;
		echo ("\t\t\t\t\tTesting ".$l."\n");
		$res=findDrug($l);
		foreach ($res as $l)$final_list[$l]=true;
	}
	return array_keys($final_list);
}
function findDrugForIntervention(&$INTERVENTION)
{
	// We only process drugs
	if ($INTERVENTION['type']!='DRUG') return null;
	/// We don't check if it's a placebo
	if (stripos($INTERVENTION['name'],'placebo')!==false)return null;


	echo "\t\tProcess drug ".$INTERVENTION['name']."\n\n";
	
	
	$DRUG_ENTRY_ID=null;
	$LIST=array(findDrug($INTERVENTION['name']));
	
	// If other names are provided we test them too
	if (isset($INTERVENTION['otherNames']))
	foreach ($INTERVENTION['otherNames'] as $DRUG_NAME)
	{
		echo ("\t\tTest alternative drug name ".$DRUG_NAME."\n");
		$LIST[]=findDrug($DRUG_NAME);
		
	}
	$DRUGS_ID=array();
	foreach ($LIST as $L)
	{
		/// Since we test multiple cases, some of them might return nothing
		if ($L==array())continue;

		foreach ($L as $ID)
		{
			if (!isset($DRUGS_ID[$ID]))$DRUGS_ID[$ID]=0;
			++$DRUGS_ID[$ID];
		}
	}
	if ($DRUGS_ID==array())
	{	
		echo "\t".$INTERVENTION['name']."\tDRUG_NOT_FOUND\n";
		return null;
	}
	
	return array_keys($DRUGS_ID);
}

function insertRecord(&$ENTRY,$N_FILE)
{
	global $PREP_STAT;
	global $DB_CONN;
	global $DBIDS;
	global $FILES;
	global $DATE;
	global $COMPANY;
	global $SOURCE_ID;
	global $STATIC_DATA;
	global $FILE_STATUS;
	global $NEWS_INFO;

	$DESIGN=&$ENTRY['protocolSection']['designModule'];
	$STATUS_MODULE=&$ENTRY['protocolSection']['statusModule'];
	$ID_MODULE=&$ENTRY['protocolSection']['identificationModule'];
	$DESC_MODULE=&$ENTRY['protocolSection']['descriptionModule'];
	$ARMS_MODULE=&$ENTRY['protocolSection']['armsInterventionsModule'];
	if (isset($DESIGN['phases']))
	{

	if (isset($STATIC_DATA['PHASE'][$DESIGN['phases'][0]]))$phase=$STATIC_DATA['PHASE'][$DESIGN['phases'][0]];
	else $phase= $DESIGN['phases'][0];	
	}
	else $phase='N/A';
	
	$status=$STATUS_MODULE['overallStatus'];
	if (isset($STATIC_DATA['STATUS'][$STATUS_MODULE['overallStatus']]))$status=$STATIC_DATA['STATUS'][$STATUS_MODULE['overallStatus']];

	
	if (isset($STATUS_MODULE['startDateStruct'])) $start_date=date('Y-m-d',strtotime($STATUS_MODULE['startDateStruct']['date']));
	else 										  $start_date=date('Y-m-d',strtotime($STATUS_MODULE['studyFirstSubmitDate']));
	
	$nct_id=$ID_MODULE['nctId'];
	echo ("\tFILE ".$N_FILE." - Inserting record for ".$nct_id."\n");
	
	if ($DATE!=-1)$DB_CONN->beginTransaction();
	
	try{
		echo "\t\tInserting clinical trial\n";
		$DBIDS['clinical_trial']++;
		
		$NEWS_INFO[$nct_id]=array('STATUS'=>'NEW');
		$JSON=json_encode($ENTRY);
		//echo strlen($content)."\n";

		if ($DATE!=-1)
		{
			$stmt=&$PREP_STAT['clinical_trial'];
			$stmt->bindParam(':dbid', $DBIDS['clinical_trial'], PDO::PARAM_INT);
			$stmt->bindParam(':nctid', $nct_id, PDO::PARAM_STR);
			$stmt->bindParam(':phase', $phase, PDO::PARAM_STR);
			$stmt->bindParam(':status', $status, PDO::PARAM_STR);
			$stmt->bindParam(':date', $start_date, PDO::PARAM_STR);
			$stmt->bindParam(':source', $SOURCE_ID, PDO::PARAM_STR);
			$stmt->bindParam(':brief', $ID_MODULE['briefTitle'], PDO::PARAM_STR);
			$stmt->bindParam(':official', $ID_MODULE['officialTitle'], PDO::PARAM_STR);
			$stmt->bindParam(':org_id', $ID_MODULE['orgStudyIdInfo']['id'], PDO::PARAM_STR);
			$stmt->bindParam(':summary', $DESC_MODULE['briefSummary'], PDO::PARAM_STR);
			$stmt->bindParam(':json', $JSON, PDO::PARAM_LOB);
			$SSTRING= $stmt->execute();
		}
		else
		{
			$FILE_STATUS['clinical_trial']=true;
			fputs($FILES['clinical_trial'],
				$DBIDS['clinical_trial']."\t".
				$nct_id."\t".
				$phase."\t".
				$status."\t".
				$start_date."\t".
				$SOURCE_ID."\t\"".
				str_replace('"','""',$ID_MODULE['briefTitle'])."\"\t\"".
				str_replace('"','""',$ID_MODULE['officialTitle'])."\"\t\"".
				str_replace('"','""',$ID_MODULE['orgStudyIdInfo']['id'])."\"\t\"".
				str_replace('"','""',$DESC_MODULE['briefSummary'])."\"\t\"".str_replace('"','""',$JSON)."\"\n");
		}
		$DB_ENTRY=array();

		echo "\t\tInserting clinical trial arms\n";
	
		$MAP_ARM=array();
	
		if (isset($ARMS_MODULE['armGroups']))
		foreach ($ARMS_MODULE['armGroups'] as &$ARM)
		{
			
			
			++$DBIDS['clinical_trial_arm'];
			if ($DATE!=-1)
			{
				$stmt=&$PREP_STAT['clinical_trial_arm'];
				if (!isset($ARM['type'])||$ARM['type']=='')$ARM['type']='UNKNOWN';
				$stmt->bindParam(':dbid', $DBIDS['clinical_trial_arm'], PDO::PARAM_INT);
				$stmt->bindParam(':trial_id', $DBIDS['clinical_trial'], PDO::PARAM_INT);
				$stmt->bindParam(':type', $ARM['type'], PDO::PARAM_STR);
				$stmt->bindParam(':label', $ARM['label'], PDO::PARAM_STR);
				$stmt->bindParam(':desc', $ARM['description'], PDO::PARAM_STR);
				$SSTRING= $stmt->execute();
			}
			else 
			{
				$FILE_STATUS['clinical_trial_arm']=true;
				fputs($FILES['clinical_trial_arm'],
					$DBIDS['clinical_trial_arm']."\t".
					$DBIDS['clinical_trial']."\t".
					$ARM['type']."\t\"".
					str_replace('"','""',$ARM['label'])."\"\t\"".
					str_replace('"','""',$ARM['description'])."\"\n");
			}
			
			$MAP_ARM[$ARM['label']]=$DBIDS['clinical_trial_arm'];
		}

		echo "\t\tInserting clinical trial interventions\n";
		if (isset($ARMS_MODULE['interventions']))
		foreach ($ARMS_MODULE['interventions'] as &$INTERVENTION)
		{
			$DRUGS=	findDrugForIntervention($INTERVENTION);
			if (!isset($INTERVENTION['name']))$INTERVENTION['name']='Undefined';
			$NAME= $INTERVENTION['name'];
			if ($INTERVENTION['type']=='')$INTERVENTION['type']='UNKNOWN';
			if (!isset($INTERVENTION['description']))$INTERVENTION['description']='';
			// if (isset($INTERVENTION['armGroupLabels'])) $NAME.=' '.implode('/',$INTERVENTION['armGroupLabels']);
			++$DBIDS['clinical_trial_intervention'];
			
			if ($DATE!=-1)
			{
			$stmt_int=&$PREP_STAT['clinical_trial_intervention'];
			$stmt_int->bindParam(':dbid', $DBIDS['clinical_trial_intervention'], PDO::PARAM_INT);
			$stmt_int->bindParam(':trial_id', $DBIDS['clinical_trial'], PDO::PARAM_INT);
			$stmt_int->bindParam(':type', $INTERVENTION['type'], PDO::PARAM_STR);
			$stmt_int->bindParam(':name',$NAME, PDO::PARAM_STR);
			$stmt_int->bindParam(':desc', $INTERVENTION['description'], PDO::PARAM_STR);
			$SSTRING= $stmt_int->execute();
			}
			else
			{
				$FILE_STATUS['clinical_trial_intervention']=true;
				fputs($FILES['clinical_trial_intervention'],
					$DBIDS['clinical_trial_intervention']."\t".
					$DBIDS['clinical_trial']."\t\"".
					str_replace('"','""',$INTERVENTION['type'])."\"\t\"".
					str_replace('"','""',$NAME)."\"\t\"".
					str_replace('"','""',$INTERVENTION['description'])."\"\n");
			}


			$stmt_idm=&$PREP_STAT['clinical_trial_intervention_drug_map'];
			if ($DRUGS!=array())
			foreach ($DRUGS as $DRUG_ENTRY_ID)
			{
				++$DBIDS['clinical_trial_intervention_drug_map'];
				if ($DATE!=-1)
				{
				$stmt_idm->bindParam(':dbid', $DBIDS['clinical_trial_intervention_drug_map'], PDO::PARAM_INT);
				$stmt_idm->bindParam(':inter_id', $DBIDS['clinical_trial_intervention'], PDO::PARAM_INT);
				$stmt_idm->bindParam(':drug_id', $DRUG_ENTRY_ID, PDO::PARAM_INT);
				$stmt_idm->bindParam(':source_id', $SOURCE_ID, PDO::PARAM_INT);
				$SSTRING= $stmt_idm->execute();
				}
				else
				{
					$FILE_STATUS['clinical_trial_intervention_drug_map']=true;
					fputs($FILES['clinical_trial_intervention_drug_map'],
						$DBIDS['clinical_trial_intervention_drug_map']."\t".
						$DBIDS['clinical_trial_intervention']."\t".
						$DRUG_ENTRY_ID."\t".
						$SOURCE_ID."\n");
				}
			}
			

			$NEW_AI=array();
			$stmt_aim=&$PREP_STAT['clinical_trial_arm_intervention_map'];
			if (isset($INTERVENTION['armGroupLabels']))
			foreach ($INTERVENTION['armGroupLabels'] as $N)
			{
				
				if (!isset($MAP_ARM[$N]))failProcess($JOB_ID."016",'Unable to find intervention '.$N.' for arm '.$ARM['label']);
				if (isset($NEW_AI[$N]))continue;
				$NEW_AI[$N]=true;
				++$DBIDS['clinical_trial_arm_intervention_map'];
				if ($DATE!=-1)
				{

					$stmt_aim->bindParam(':dbid', $DBIDS['clinical_trial_arm_intervention_map'], PDO::PARAM_INT);
					$stmt_aim->bindParam(':trial_id', $DBIDS['clinical_trial'], PDO::PARAM_INT);
					$stmt_aim->bindParam(':arm_id', $MAP_ARM[$N], PDO::PARAM_INT);
					$stmt_aim->bindParam(':inter_id',$DBIDS['clinical_trial_intervention'] , PDO::PARAM_INT);
					$SSTRING= $stmt_aim->execute();
				}
				else
				{
					$FILE_STATUS['clinical_trial_arm_intervention_map']=true;
					fputs($FILES['clinical_trial_arm_intervention_map'],
						$DBIDS['clinical_trial_arm_intervention_map']."\t".
						$DBIDS['clinical_trial']."\t".
						$MAP_ARM[$N]."\t".
						$DBIDS['clinical_trial_intervention']."\n");
				}
				
			}
			

			
		}

		
		if (isset($ENTRY['protocolSection']['conditionsModule']))
		{
			echo "\t\tInserting clinical trial conditions\n";
			foreach ($ENTRY['protocolSection']['conditionsModule']['conditions'] as $ds)
			{
				//echo $ds."\n";
				$DISEASE_ENTRY_ID=findDisease($ds);
				++$DBIDS['clinical_trial_condition'];
				if ($DATE!=-1)
				runQueryNoRes("INSERT INTO clinical_trial_condition VALUES (".$DBIDS['clinical_trial_condition'].','.$DBIDS['clinical_trial'].','.$DISEASE_ENTRY_ID.",'".str_replace("'","''",$ds)."')");
				else 
				{
					$FILE_STATUS['clinical_trial_condition']=true;
					fputs($FILES['clinical_trial_condition'],
						$DBIDS['clinical_trial_condition']."\t".
						$DBIDS['clinical_trial']."\t".
						$DISEASE_ENTRY_ID."\t".
						str_replace('"','""',$ds)."\n");
				}
			}
		}
		
		if (isset($ID_MODULE['organization']['fullName']))
		{
			echo "\t\tInserting clinical trial company map\n";
			if (!isset($COMPANY[strtolower($ID_MODULE['organization']['fullName'])]))
			{
				$DBIDS['company_entry']++;
				runQueryNoRes("INSERT INTO company_entry VALUES (".$DBIDS['company_entry'].",'".str_replace("'","''",$ID_MODULE['organization']['fullName'])."')");
				$COMPANY[strtolower($ID_MODULE['organization']['fullName'])]=$DBIDS['company_entry'];
			}
			if ($DATE!=-1)
			runQueryNoRes("INSERT INTO clinical_trial_company_map VALUES (".$DBIDS['clinical_trial'].','.$COMPANY[strtolower($ID_MODULE['organization']['fullName'])].')');
			else 
			{
				$FILE_STATUS['clinical_trial_company_map']=true;
				fputs($FILES['clinical_trial_company_map'],
					$DBIDS['clinical_trial']."\t".
					$COMPANY[strtolower($ID_MODULE['organization']['fullName'])]."\n");
			}
					
				
		}


		echo "\t\tInserting clinical trial alias\n";
		$DBIDS['clinical_trial_alias']++;
		
		
		$alias_type='Primary';

		if ($DATE!=-1)
		{
		$stmt_ctal = &$PREP_STAT['clinical_trial_alias'];
		$stmt_ctal->bindParam(':dbid', $DBIDS['clinical_trial_alias'], PDO::PARAM_INT);
		$stmt_ctal->bindParam(':trial_id', $DBIDS['clinical_trial'], PDO::PARAM_STR);
		$stmt_ctal->bindParam(':val', $nct_id, PDO::PARAM_STR);
		$stmt_ctal->bindParam(':type', $alias_type, PDO::PARAM_STR);
		$SSTRING= $stmt_ctal->execute();
		}
		else
		{
			$FILE_STATUS['clinical_trial_alias']=true;
			fputs($FILES['clinical_trial_alias'],
				$DBIDS['clinical_trial_alias']."\t".
				$DBIDS['clinical_trial']."\t".
				$nct_id."\t".
				$alias_type."\n");
		}
		

		
		if (isset($ID_MODULE['secondaryIdInfos'])){
			
			$alias_type='Secondary';
			$ACR=&$ID_MODULE['secondaryIdInfos'];
			
			$TMP_ID=array();
			
			foreach ($ACR as $ID_t)
			{
				$ID=$ID_t['id'];
				if ($ID==$nct_id)continue;
				if (isset($TMP_ID[$ID]))continue;
				$TMP_ID[$ID]=true;
				if (!isset($DB_ENTRY['ALIAS'][$ID]))
				{
					$DBIDS['clinical_trial_alias']++;
					$DB_ENTRY['ALIAS'][$ID]=true;
					//echo $ID.' '.$alias_type."\n";
					if ($DATE!=-1)
					{
					$stmt_ctal->bindParam(':val', $ID, PDO::PARAM_STR);
					$SSTRING= $stmt_ctal->execute();
					}
					else
					{
						$FILE_STATUS['clinical_trial_alias']=true;
						fputs($FILES['clinical_trial_alias'],
							$DBIDS['clinical_trial_alias']."\t".
							$DBIDS['clinical_trial']."\t".
							str_replace('"','""',$ID)."\t".
							$alias_type."\n");
					}
				}
			}
		}
		if (isset($ID_MODULE['orgStudyIdInfo'])){
			
			$alias_type='Org';
			$ACR=&$ID_MODULE['orgStudyIdInfo'];
			
			$ACR=array_unique($ACR);
			
			foreach ($ACR as $ID)
			{
				if ($ID=='')continue;
				if ($ID==$nct_id)continue;
				if (!isset($DB_ENTRY['ALIAS'][$ID]))
				{
					$DBIDS['clinical_trial_alias']++;
					$DB_ENTRY['ALIAS'][$ID]=true;
					//echo $ID.' '.$alias_type."\n";
					if ($DATE!=-1)
					{
						$stmt_ctal->bindParam(':val', $ID, PDO::PARAM_STR);
						$SSTRING= $stmt_ctal->execute();
					}
					else
					{
						$FILE_STATUS['clinical_trial_alias']=true;
						fputs($FILES['clinical_trial_alias'],
							$DBIDS['clinical_trial_alias']."\t".
							$DBIDS['clinical_trial']."\t".
							str_replace('"','""',$ID)."\t".
							$alias_type."\n");
					}
				}
			}
		}
			
		if (isset($ID_MODULE['nctIdAliases'])){
			$ACR=null;
			if (is_array($ID_MODULE['nctIdAliases']))
			$ACR=&$ID_MODULE['nctIdAliases']
			;
			else $ACR=array($ID_MODULE['nctIdAliases']);
			$ACR=array_unique($ACR);
			$alias_type='Alias';
			foreach ($ACR as $ID)
			{
				if ($ID==$nct_id)continue;
				if (!isset($DB_ENTRY['ALIAS'][$ID]))
				{
					$DBIDS['clinical_trial_alias']++;
					$DB_ENTRY['ALIAS'][$ID]=true;
					//echo $ID.' '.$alias_type."\n";
					if ($DATE!=-1)
					{
						$stmt_ctal->bindParam(':val', $ID, PDO::PARAM_STR);
						$SSTRING= $stmt_ctal->execute();
					}
					else
					{
						$FILE_STATUS['clinical_trial_alias']=true;
						fputs($FILES['clinical_trial_alias'],
							$DBIDS['clinical_trial_alias']."\t".
							$DBIDS['clinical_trial']."\t".
							str_replace('"','""',$ID)."\t".
							$alias_type."\n");
					}
				}
			}
		}
		if (isset($ID_MODULE['acronym']))
		{
			$ACR=null;
			if (is_array($ID_MODULE['acronym']))$ACR=&$ID_MODULE['acronym'];
			else 								$ACR=array($ID_MODULE['acronym']);
			$ACR=array_unique($ACR);
			$alias_type='Acronym';
			foreach ($ACR as $ID)
			{
				if ($ID==$nct_id)continue;
				if (!isset($DB_ENTRY['ALIAS'][$ID]))
				{
					$DBIDS['clinical_trial_alias']++;
					//echo $ID.' '.$alias_type."\n";
					if ($DATE!=-1)
					{
						$stmt_ctal->bindParam(':val', $ID, PDO::PARAM_STR);
						$SSTRING= $stmt_ctal->execute();
					}
					else
					{
						$FILE_STATUS['clinical_trial_alias']=true;
						fputs($FILES['clinical_trial_alias'],
							$DBIDS['clinical_trial_alias']."\t".
							$DBIDS['clinical_trial']."\t".
							str_replace('"','""',$ID)."\t".
							$alias_type."\n");
					}
				}
			}
		}
		if ($DATE!=-1)$DB_CONN->commit();
		echo "\t\tInsertion done\n";
		return true;
	}catch(Exception $e)
	{
		//print_R($ENTRY);
		print_r($e->getMessage());
	echo "\t\tInsertion failed\n";
	
	if ($DATE!=-1)$DB_CONN->rollback();
	return false;
	}
	catch(PDOException $e)
	{
		//print_R($ENTRY);
		print_r($e->getMessage());
		if ($DATE!=-1)$DB_CONN->rollback();
		
		echo "\t\tInsertion failed\n";
		return false;
	}
}



	function compareRecord(&$ENTRY,&$DATA,$N_FILE)
	{
		global $DATE;
		global $FILES;
		global $PREP_STAT;
		global $DB_CONN;
		global $DBIDS;
		global $COMPANY;
		global $SOURCE_ID;
		global $STATIC_DATA;
		global $NEWS_INFO;

		$DESIGN=&$ENTRY['protocolSection']['designModule'];
		$STATUS_MODULE=&$ENTRY['protocolSection']['statusModule'];
		$ID_MODULE=&$ENTRY['protocolSection']['identificationModule'];
		$DESC_MODULE=&$ENTRY['protocolSection']['descriptionModule'];
		$ARMS_MODULE=&$ENTRY['protocolSection']['armsInterventionsModule'];
		if (isset($DESIGN['phases']))
		{

			if (isset($STATIC_DATA['PHASE'][$DESIGN['phases'][0]]))$phase=$STATIC_DATA['PHASE'][$DESIGN['phases'][0]];
			else $phase= $DESIGN['phases'][0];	
		}
		else $phase='N/A';
		
		$status=$STATUS_MODULE['overallStatus'];
		if (isset($STATIC_DATA['STATUS'][$STATUS_MODULE['overallStatus']]))$status=$STATIC_DATA['STATUS'][$STATUS_MODULE['overallStatus']];

		if (isset($DESIGN['phases'])) echo $DESIGN['phases'][0];
		
		if (isset($STATUS_MODULE['startDateStruct']))
		$start_date=date('Y-m-d',strtotime($STATUS_MODULE['startDateStruct']['date']));
		else $start_date=date('Y-m-d',strtotime($STATUS_MODULE['studyFirstSubmitDate']));
		$nct_id=$ID_MODULE['nctId'];


		echo ("\tFILE ".$N_FILE." - Comparing record for ".$nct_id."\n");
		$DB_CONN->beginTransaction();
		try{
			
			$DB_ENTRY=&$DATA[$nct_id]['INFO'];
			$CHANGE=array();
			
			$UPDATE='UPDATE clinical_trial SET ';$TO_UPD=false;
			if ($DB_ENTRY['clinical_phase']!=$phase)
			{
				echo ("\t\t".$nct_id."\tCHANGE\tPHASE\t".$DB_ENTRY['clinical_phase']."\t".$phase."\n");
				$UPDATE .= "clinical_phase = '".$phase."',";
				$TO_UPD=true;
				$CHANGE['PHASE']=array($DB_ENTRY['clinical_phase'],$phase);
			}
			if ($DB_ENTRY['clinical_status']!=$status)
			{
				echo ("\t\t".$nct_id."\tCHANGE\tSTATUS\t".$DB_ENTRY['clinical_status']."\t".$status."\n");
				$UPDATE .= "clinical_status = '".$status."',";
				$TO_UPD=true;
				$CHANGE['STATUS']=array($DB_ENTRY['clinical_status'],$status);
			}
			if (explode(" ",$DB_ENTRY['start_date'])[0]!=$start_date)
			{
				echo ("\t\t".$nct_id."\tCHANGE\tSTART_DATE\t".$DB_ENTRY['start_date']."\t".$start_date."\n");
				if ($start_date!='') $UPDATE .= "start_date = '".$start_date."',";else $UPDATE.='start_date=NULL,';$TO_UPD=true;
			}
			if ($DB_ENTRY['brief_title']!=$ID_MODULE['briefTitle'])
			{
				echo ("\t\t".$nct_id."\tCHANGE\tBRIEF_TITLE\t".$DB_ENTRY['brief_title']."\t".$ID_MODULE['briefTitle']."\n");
				$UPDATE .= "brief_title = '".str_replace("'","''",$ID_MODULE['briefTitle'])."',";$TO_UPD=true;
			}
			//if ($DB_ENTRY['hash']!=md5_file($fpath)){$MAP_FILES[$DB_ENTRY['DB_ID']]=$fpath;}
			if (isset($ID_MODULE['officialTitle']) && $DB_ENTRY['official_title']!=$ID_MODULE['officialTitle'])
			{
				echo ("\t\t".$nct_id."\tCHANGE\tOFFICIAL_TITLE\t".$DB_ENTRY['official_title']."\t".$ID_MODULE['officialTitle']."\n");
				$UPDATE .= "official_title = '".str_replace("'","''",$ID_MODULE['officialTitle'])."',";$TO_UPD=true;
			}
			if ($DB_ENTRY['org_study_id']!=$ID_MODULE['orgStudyIdInfo']['id'])
			{
				echo ("\t\t".$nct_id."\tCHANGE\tORG_STUDY_ID\t".$DB_ENTRY['org_study_id']."\t".$ID_MODULE['orgStudyIdInfo']['id']."\n");
				$UPDATE .= "org_study_id = '".str_replace("'","''",$ID_MODULE['orgStudyIdInfo']['id'])."',";$TO_UPD=true;}
			if ($DB_ENTRY['brief_summary']!=$DESC_MODULE['briefSummary'])
			{
				$UPDATE .= "brief_summary = '".str_replace("'","''",$DESC_MODULE['briefSummary'])."',";
				$TO_UPD=true;
			}
			if ($TO_UPD)
			{
				if ($CHANGE!=array())
				{
				$NEWS_INFO[$nct_id]=$CHANGE;
				//print_R($CHANGE);//exit;
				}
				//++$N_UPD;
				$UPDATE=substr($UPDATE,0,-1).' WHERE clinical_trial_id = '.$DB_ENTRY['DB_ID'];
				echo $UPDATE."\n";
				if (!runQueryNoRes($UPDATE))failProcess($JOB_ID."037","Unable to update query");
			}
			$JSON=json_encode($ENTRY);
			$MD5=md5($JSON);
			if ($MD5!=$DB_ENTRY['hash'])
			{
				echo ("\t\t".$nct_id."\tCHANGE\tJSON\t".$DB_ENTRY['hash']."\t".$MD5."\n");
				$query='UPDATE clinical_trial SET details = :json WHERE clinical_trial_id = '.$DB_ENTRY['DB_ID'];
				$stmt = $DB_CONN->prepare($query);
				$JSON=json_encode($ENTRY);
					
				$stmt->bindParam(':json', $JSON, PDO::PARAM_LOB);
				$SSTRING= $stmt->execute();
						$stmt=null;
			}
			$DB_INFO=&$DATA[$nct_id];

			
			$MAP_ARM=array();
			if (isset($ARMS_MODULE['armGroups']))
			foreach ($ARMS_MODULE['armGroups'] as &$ARM)
			{
				if (!isset($ARM['type'])||$ARM['type']=='')$ARM['type']='UNKNOWN';
				if (!isset($ARM['description']))$ARM['description']='';
				
				$FOUND=false;
				if (isset($DB_INFO['ARM']))
				foreach ($DB_INFO['ARM'] as $DB_ID=>&$ARM_DB)
				{
					if ($ARM_DB['arm_label']==$ARM['label'] && 
					$ARM_DB['arm_type']==$ARM['type'] &&
					 $ARM_DB['arm_description']==$ARM['description'])
					{
						$ARM_DB['DB_STATUS']='VALID';
						
						$MAP_ARM[$ARM['label']]=$DB_ID;
						$FOUND=true;
						break ;
					}
				}
				if ($FOUND)continue;
				$stmt_arm=&$PREP_STAT['clinical_trial_arm'];
				echo ("\t\t".$nct_id."\tNEW\tARM\t".$ARM['label']."\n");
				++$DBIDS['clinical_trial_arm'];
				$MAP_ARM[$ARM['label']]=$DBIDS['clinical_trial_arm'];
				$stmt_arm->bindParam(':dbid', $DBIDS['clinical_trial_arm'], PDO::PARAM_INT);
				$stmt_arm->bindParam(':trial_id', $DB_ENTRY['DB_ID'], PDO::PARAM_INT);
				$stmt_arm->bindParam(':type', $ARM['type'], PDO::PARAM_STR);
				$stmt_arm->bindParam(':label', $ARM['label'], PDO::PARAM_STR);
				$stmt_arm->bindParam(':desc', $ARM['description'], PDO::PARAM_STR);
				$SSTRING= $stmt_arm->execute();
				
			}
			if (isset($DB_INFO['ARM']))
			foreach ($DB_INFO['ARM'] as $DB_ID=>&$ARM_DB)
			{
				if ($ARM_DB['DB_STATUS']!='FROM_DB')continue;
				echo ("\t\t".$nct_id."\tDELETE\tARM\t".$ARM_DB['arm_label']."\n");
				if (!runQueryNoRes("DELETE FROM clinical_trial_arm where clinical_trial_arm_id = ".$DB_ID))failProcess($JOB_ID."038","Unable to delete  arm");
			}


			if (isset($ARMS_MODULE['interventions']))
			foreach ($ARMS_MODULE['interventions'] as &$INTERVENTION)
			{
				
				$DRUGS=	findDrugForIntervention($INTERVENTION);
				$NAME= $INTERVENTION['name'];
				if ($INTERVENTION['type']=='')$INTERVENTION['type']='UNKNOWN';
				// if (isset($INTERVENTION['armGroupLabels'])) $NAME.=' '.implode('/',$INTERVENTION['armGroupLabels']);
				$FOUND=false;
				$DB_ID=null;
				if (!isset($INTERVENTION['description']))$INTERVENTION['description']='';
				if (isset($DB_INFO['INTERVENTION']))
				foreach ($DB_INFO['INTERVENTION'] as $DB_INT_ID=>&$INT)
				{
					if ($INT['intervention_name']!=$NAME)continue;
					if ($INT['intervention_type']!=$INTERVENTION['type']) continue;
					if ($INT['intervention_description']!=$INTERVENTION['description'])continue;
					$DB_ID=$DB_INT_ID;
					$INT['DB_STATUS']='VALID';
					$FOUND=true;
					break ;
				
				}
				if ($FOUND)
				{
					if ($DRUGS!=array())
					foreach ($DRUGS as $DRUG_ENTRY_ID)
					{
						if (!isset($INT['DRUG'][$DRUG_ENTRY_ID]))
						{
							echo ("\t\t".$nct_id."\tNEW\tDRUG\t".$DRUG_ENTRY_ID."\n");
							$stmt_idm=&$PREP_STAT['clinical_trial_intervention_drug_map'];
							++$DBIDS['clinical_trial_intervention_drug_map'];
							$stmt_idm->bindParam(':dbid', $DBIDS['clinical_trial_intervention_drug_map'], PDO::PARAM_INT);
							$stmt_idm->bindParam(':inter_id', $DB_ID, PDO::PARAM_INT);
							$stmt_idm->bindParam(':drug_id', $DRUG_ENTRY_ID, PDO::PARAM_INT);
							$stmt_idm->bindParam(':source_id', $SOURCE_ID, PDO::PARAM_INT);
							$SSTRING= $stmt_idm->execute();
							
						}
						else $INT['DRUG'][$DRUG_ENTRY_ID]['DB_STATUS']='VALID';
					}

					if (isset($INT['DRUG']))
					foreach ($INT['DRUG'] as $DR_ENTRY_ID=>&$DATA)
					{
						if ($DATA['DB_STATUS']=='VALID')continue;
						if ($DATA['SOURCE_ID']!=$SOURCE_ID)continue;
						echo ("\t\t".$nct_id."\tDELETE\tDRUG\t".$DR_ENTRY_ID."\n");
						if (!runQueryNoRes("DELETE FROM clinical_trial_intervention_drug_map where clinical_trial_intervention_drug_map_id = ".$DR_ENTRY_ID))failProcess($JOB_ID."039","Unable to delete intervention drug");
					}

					$NEW_AI=array();
			
					if (isset($INTERVENTION['armGroupLabels']))
					foreach ($INTERVENTION['armGroupLabels'] as $N)
					{
						
						if (!isset($MAP_ARM[$N]))failProcess($JOB_ID."040",'Unable to find intervention '.$N.' for arm '.$ARM['label']);
						$FOUND=false;
						
						if(isset($DB_INFO['ARM_INTERVENTION']))
						foreach ($DB_INFO['ARM_INTERVENTION'] as $DB_AI_DB=>&$DB_AI)
						{
							
							if ($DB_AI_DB!=$MAP_ARM[$N])continue;
							if (!isset($DB_AI[$DB_ID]))continue;
							
							$DB_AI[$DB_ID]['DB_STATUS']='VALID';
							$FOUND=true;
							break;
						}
						if (!$FOUND)
						{
							if (isset($NEW_AI[$N]))continue;
							$NEW_AI[$N]=true;
							echo ("\t\t".$nct_id."\tNEW\tARM_INTERVENTION\t".$N."\n");
							$stmt_aim=&$PREP_STAT['clinical_trial_arm_intervention_map'];
							
							++$DBIDS['clinical_trial_arm_intervention_map'];
							$stmt_aim->bindParam(':dbid', $DBIDS['clinical_trial_arm_intervention_map'], PDO::PARAM_INT);
							$stmt_aim->bindParam(':trial_id', $DB_ENTRY['DB_ID'], PDO::PARAM_INT);
							$stmt_aim->bindParam(':arm_id', $MAP_ARM[$N], PDO::PARAM_INT);
							$stmt_aim->bindParam(':inter_id',$DB_ID , PDO::PARAM_INT);
							$SSTRING= $stmt_aim->execute();
							
						}
						
					}
					if (isset($DB_INFO['ARM_INTERVENTION']))
					foreach ($DB_INFO['ARM_INTERVENTION'] as $DB_AI_DB=>&$DB_AI)
				foreach ($DB_AI as $INT_ID=>&$DATA)
					{
						if ($DATA['DB_STATUS']=='VALID')continue;
						if ($INT_ID!= $DB_ID)continue;
						
						echo ("\t\t".$nct_id."\tDELETE\tARM_INTERVENTION\t".implode("\t",$DATA)."\n");
						if (!runQueryNoRes("DELETE FROM clinical_trial_arm_intervention_map where clinical_trial_arm_intervention_map_id = ".$DATA['DB_ID']))failProcess($JOB_ID."041","Unable to delete arm intervention");
					}

				}
				else 
				{
					echo ("\t\t".$nct_id."\tNEW\tINTERVENTION\t".$NAME."\n");
					$stmt_cti=&$PREP_STAT['clinical_trial_intervention'];
					
					++$DBIDS['clinical_trial_intervention'];
					$stmt_cti->bindParam(':dbid', $DBIDS['clinical_trial_intervention'], PDO::PARAM_INT);
					$stmt_cti->bindParam(':trial_id', $DB_ENTRY['DB_ID'], PDO::PARAM_INT);
					$stmt_cti->bindParam(':type', $INTERVENTION['type'], PDO::PARAM_STR);
					$stmt_cti->bindParam(':name',$NAME, PDO::PARAM_STR);
					$stmt_cti->bindParam(':desc', $INTERVENTION['description'], PDO::PARAM_STR);
					
					$SSTRING= $stmt_cti->execute();
					
					$stmt_idm=&$PREP_STAT['clinical_trial_intervention_drug_map'];
					
					if ($DRUGS!=array())
					foreach ($DRUGS as $DRUG_ENTRY_ID)
					{
						echo ("\t\t".$nct_id."\tNEW\tDRUG\t".$DRUG_ENTRY_ID."\n");
						++$DBIDS['clinical_trial_intervention_drug_map'];
						$stmt_idm->bindParam(':dbid', $DBIDS['clinical_trial_intervention_drug_map'], PDO::PARAM_INT);
						$stmt_idm->bindParam(':inter_id', $DBIDS['clinical_trial_intervention'], PDO::PARAM_INT);
						$stmt_idm->bindParam(':drug_id', $DRUG_ENTRY_ID, PDO::PARAM_INT);
						$stmt_idm->bindParam(':source_id', $SOURCE_ID, PDO::PARAM_INT);
						$SSTRING= $stmt_idm->execute();
						

					}
					

					$stmt_aim=&$PREP_STAT['clinical_trial_arm_intervention_map'];
					
					if (isset($INTERVENTION['armGroupLabels']))
					foreach ($INTERVENTION['armGroupLabels'] as $N)
					{
						
						if (!isset($MAP_ARM[$N]))failProcess($JOB_ID."016",'Unable to find intervention '.$N.' for arm '.$ARM['label']);
						echo ("\t\t".$nct_id."\tNEW\tARM_INTERVENTION\t".$N."\n");
						++$DBIDS['clinical_trial_arm_intervention_map'];
						$stmt_aim->bindParam(':dbid', $DBIDS['clinical_trial_arm_intervention_map'], PDO::PARAM_INT);
						$stmt_aim->bindParam(':trial_id', $DBIDS['clinical_trial'], PDO::PARAM_INT);
						$stmt_aim->bindParam(':arm_id', $MAP_ARM[$N], PDO::PARAM_INT);
						$stmt_aim->bindParam(':inter_id',$DBIDS['clinical_trial_intervention'] , PDO::PARAM_INT);
						$SSTRING= $stmt_aim->execute();
						
					}
					


				}
				
			}
			if (isset($DB_INFO['INTERVENTION']))
			foreach ($DB_INFO['INTERVENTION'] as $DB_ID=>&$INT)
			{
				if ($INT['DB_STATUS']!='FROM_DB')continue;
				echo ("\t\t".$nct_id."\tDELETE\tINTERVENTION\t".$INT['intervention_name']."\n");
				if (!runQueryNoRes("DELETE FROM clinical_trial_intervention where clinical_trial_intervention_id = ".$DB_ID))failProcess($JOB_ID."042","Unable to delete  intervention");
			}









			if (isset($ENTRY['protocolSection']['conditionsModule']))
			{
				//print_r($ENTRY['protocolSection']['conditionsModule']);//['keyword']);
				
				foreach ($ENTRY['protocolSection']['conditionsModule']['conditions'] as $ds)
				{
					//echo $ds."\n";
					$DISEASE_ENTRY_ID=findDisease($ds);
				
					$FOUND=false;
					if ($DISEASE_ENTRY_ID=='NULL')$DISEASE_ENTRY_ID='';
					if (isset($DB_INFO['DISEASE']))
					{
						foreach ($DB_INFO['DISEASE'] as &$DISEASE_DB)
						{
							if ($DISEASE_DB['condition_name']!=$ds)continue;
							if ($DISEASE_DB['disease_entry_id']!= $DISEASE_ENTRY_ID)
							{
								echo ("\t\t".$nct_id."\tCHANGE\tDISEASE\t".$DISEASE_DB['condition_name']."\t".$DISEASE_DB['disease_entry_id']."\t".$DISEASE_ENTRY_ID."\n");
								if ($DISEASE_ENTRY_ID=='')$DISEASE_ENTRY_ID='NULL';
								if (!runQueryNoRes("UPDATE clinical_trial_condition SET disease_entry_id = ".$DISEASE_ENTRY_ID." where clinical_trial_condition_id = ".$DISEASE_DB['clinical_trial_condition_id']))
								failProcess($JOB_ID."043","Unable to update disease");
							}
							$DISEASE_DB['DB_STATUS']='VALID';
							$FOUND=true;
							break;
							
						}
					}
					if ($FOUND)continue;
					if ($DISEASE_ENTRY_ID=='')$DISEASE_ENTRY_ID='NULL';
					echo ("\t\t".$nct_id."\tNEW\tDISEASE\t".$ds."\n");
					++$DBIDS['clinical_trial_condition'];
					if (!runQueryNoRes("INSERT INTO clinical_trial_condition 
					VALUES (".$DBIDS['clinical_trial_condition'].','.$DB_ENTRY['DB_ID'].','.$DISEASE_ENTRY_ID.",'".str_replace("'","''",$ds)."')"))
					failProcess($JOB_ID."043","Unable to insert disease");
					
					
					
				}
			}

			if (isset($DB_INFO['DISEASE']))
			foreach ($DB_INFO['DISEASE'] as $DISEASE_DB)
			{
				if ($DISEASE_DB['DB_STATUS']!='FROM_DB')continue;
				
				echo ("\t\t".$nct_id."\tDELETE\tDISEASE\t".$DISEASE_DB['condition_name']."\n");
				if (!runQueryNoRes("DELETE FROM clinical_trial_condition where clinical_trial_condition_id = ".$DISEASE_DB['clinical_trial_condition_id']))
				failProcess($JOB_ID."044","Unable to delete  disease");
			}
			
			
			
			
			
			if (isset($ID_MODULE['organization']['fullName']))
			{
				if (!isset($COMPANY[strtolower($ID_MODULE['organization']['fullName'])]))
				{
					$DBIDS['company_entry']++;
					echo ("\t\t".$nct_id."\tNEW\tCOMPANY\t".$ID_MODULE['organization']['fullName']."\n");
					if (!runQueryNoRes("INSERT INTO company_entry VALUES (".$DBIDS['company_entry'].",'".str_replace("'","''",$ID_MODULE['organization']['fullName'])."')"))
					failProcess($JOB_ID."045","Unable to insert company");
					$COMPANY[strtolower($ID_MODULE['organization']['fullName'])]=$DBIDS['company_entry'];
					if (!runQueryNoRes("INSERT INTO clinical_trial_company_map VALUES (".$DB_ENTRY['DB_ID'].','.$COMPANY[strtolower($ID_MODULE['organization']['fullName'])].')'))
					failProcess($JOB_ID."046","Unable to insert company map");
				}
				else 
				{
					//print_r($DB_INFO);
					//echo $ID_MODULE['organization']['fullName']."\t".$COMPANY[strtolower($ID_MODULE['organization']['fullName'])]."\n";
					
					if (!isset($DB_INFO['COMPANY'][$COMPANY[strtolower($ID_MODULE['organization']['fullName'])]]))
					{	
						echo ("\t\t".$nct_id."\tNEW\tCOMPANY_MAP\t".$ID_MODULE['organization']['fullName']."\n");
						if (!runQueryNoRes("INSERT INTO clinical_trial_company_map VALUES (".$DB_ENTRY['DB_ID'].','.$COMPANY[strtolower($ID_MODULE['organization']['fullName'])].')'))
						failProcess($JOB_ID."047","Unable to insert company map");
					}
				}
				

				
				}
				if (isset($DB_INFO['COMPANY']))
				foreach ($DB_INFO['COMPANY'] as $D_ID=>&$STATUS)
				if ($STATUS['DB_STATUS']=='FROM_DB')
				if (!runQueryNoRes("DELETE FROM clinical_Trial_company_map where clinical_trial_Id = ".$DB_ENTRY['DB_ID'].' AND company_entry_Id = '.$D_ID))
				failProcess($JOB_ID."048","Unable to delete company map");
			$DB_CONN->commit();
			echo "\t\tComparison done\n";
			return true;
		}catch(Exception $e)
		{
			//print_R($ENTRY);
			print_r($e->getMessage());
			$DB_CONN->rollback();
			echo "\t\tComparison failed\n";
			return false;
		}catch(PDOException $e)
		{
			//print_R($ENTRY);
			print_r($e->getMessage());
			$DB_CONN->rollback();
			echo "\t\tComparison failed\n";
			return false;
		}

	}



	function createNews($JSON){
		$JSON=array_filter($JSON);
		if ($JSON==array())return;
	$query="SELECT clinical_trial_id,trial_id   , clinical_status ,     start_date      , official_title , brief_summary    ,clinical_phase 
	FROM clinical_trial where trial_id IN (";
	foreach ($JSON as $L=>&$ST)$query.="'".$L."',";
	$res=runQuery(substr($query,0,-1).') AND clinical_phase != \'NA\' AND clinical_phase != \'N/A\'');
	$MAP=array();
	foreach ($res as $line)
	{
	
		$T=array();foreach ($line as $K=>$V)$T[strtolower($K)]=$V;$line=$T;
		//if ($line['details']!='')$line['details']= json_decode($line['details'], true);
		$JSON[$line['trial_id']]['INFO']=$line;
		$MAP[$line['clinical_trial_id']]=$line['trial_id'];
	}
	
	
	$res=runQuery("SELECT * FROM clinical_trial_condition ctdm LEFT JOIN disease_entry de ON de.disease_entry_id = ctdm.disease_entry_id WHERE clinical_trial_id IN (".implode(',',array_keys($MAP)).')');
	foreach ($res as $line)
	{
		$T=array();foreach ($line as $K=>$V)$T[strtolower($K)]=$V;$line=$T;
		$JSON[$MAP[$line['clinical_trial_id']]]['DISEASE'][$line['disease_tag']]=$line;
	}
	
	$res=runQuery("SELECT * FROM disease_entry de , drug_disease dd, clinical_trial_drug ctdm where de.disease_entry_id = dd.disease_entry_id AND ctdm.drug_disease_id = dd.drug_disease_id AND clinical_trial_id IN (".implode(',',array_keys($MAP)).')');
	foreach ($res as $line)
	{
		$T=array();foreach ($line as $K=>$V)$T[str_replace("-"," ",strtolower($K))]=$V;$line=$T;
		$JSON[$MAP[$line['clinical_trial_id']]]['DISEASE'][$line['disease_tag']]=$line;
	}
	$res=runQuery("SELECT * FROM drug_name dn, drug_entry de , drug_disease dd, clinical_trial_drug ctdm where  dn.drug_entry_Id =de.drug_entry_id AND is_tradename='T'
	 AND de.drug_entry_id = dd.drug_entry_id AND ctdm.drug_disease_id = dd.drug_disease_id AND clinical_trial_id IN (".implode(',',array_keys($MAP)).')');
	foreach ($res as $line)
	{
		$T=array();foreach ($line as $K=>$V)$T[strtolower($K)]=$V;$line=$T;
	
		
	
	
		$JSON[$MAP[$line['clinical_trial_id']]]['DRUG'][$line['drug_entry_id']]=$line;
	}
	$res=runQuery("SELECT * FROM company_entry de , clinical_trial_company_map ctdm where de.company_entry_id = ctdm.company_entry_id AND clinical_trial_id IN (".implode(',',array_keys($MAP)).')');
	foreach ($res as $line)
	{
		$T=array();foreach ($line as $K=>$V)$T[strtolower($K)]=$V;$line=$T;
		$JSON[$MAP[$line['clinical_trial_id']]]['COMPANY'][]=$line;
	}
	$STR='<div class="w3-col s12 l12 m12" style="min-height:500px;height:500px"><table class="table" id="tbl_t"><thead><tr><th>Trial id</th><th>Clinical Phase</th><th>Status</th><th>Company</th><th>Drug</th><th>Disease</th></tr></thead><tbody>';
	$ENTRY=array();
	echo json_encode($JSON)."\n";
	foreach ($JSON as &$ENTRY)
	{
		if (!isset($ENTRY['INFO']))continue;
	
		//<td>'.$ENTRY['INFO']['official_title'].'</td>
		$STR.='<tr><td><a target="_blank" title="'.$ENTRY['INFO']['official_title'].'" href="/CLINICAL_TRIAL/'.$ENTRY['INFO']['trial_id'].'">'.$ENTRY['INFO']['trial_id'].'</a></td><td>';
		$STR.=str_replace("PHASE","",$ENTRY['INFO']['clinical_phase']).'</td><td>';
		if (isset($ENTRY['STATUS']))
		{
		 if ($ENTRY['STATUS']=='NEW')$STR.='New!';
		 else $STR.=strtolower(str_replace("_"," ",$ENTRY['STATUS'][0])). ' &#8594; '.strtolower(str_replace("_"," ",$ENTRY['STATUS'][1]));
		}else $STR.=$ENTRY['clinical_status'];
		$STR.='</td><td>';
		if (isset($ENTRY['COMPANY'])) foreach ($ENTRY['COMPANY'] as $C)$STR.=$C['company_name'].'<br/>';
		$STR.='</td><td>';
		if (isset($ENTRY['DRUG'])) foreach ($ENTRY['DRUG'] as $C)$STR.='<a href="/DRUG/'.$C['drug_name'].'">'.$C['drug_name'].'</a><br/>';
		$STR.='</td><td>';
		if (isset($ENTRY['DISEASE'])) foreach ($ENTRY['DISEASE'] as $C)$STR.='<a href="/DISEASE/'.$C['disease_tag'].'">'.$C['disease_name'].'</a><br/>';
		$STR.='</td></tr>';
	}
	$STR.='</tbody></table></div>';
	
		$STR.="
					<script>$(document).ready(function(){ $(\"#tbl_t\").DataTable({responsive:true,buttons: [
						{
							
							extend: 'searchPanes',
							config: {
								cascadePanes: true
							}
						}
					],
				
					
					dom: 'Blfrtip',
					language: {
						searchPanes: {
							clearMessage: 'Clear Selections',
							collapse: {0: 'Search Options', _: 'Search Options (%d)'},
							count: '{total} found',
							countFiltered: '{shown} / {total}'
						}
					}
					})});</script>";
	$res=runQuery("SELECT * FROM source where LOWER(source_name) LIKE '%clinicaltrials%'");
	$source_id = $res[0]['source_id'];
	bc_submitNews(array('USER_ID'=>'NULL','SOURCE'=>$source_id,'NEWS_HTML'=>$STR,'NEWS_CONTENT'=>$STR,'TITLE'=>'Clinical trials updates on '.getCurrDate(),'HASH'=>md5(microtime_float().'-CT')));
	
	bc_private_submitNews(array('USER_ID'=>'NULL','SOURCE'=>$source_id,'NEWS_HTML'=>$STR,'NEWS_CONTENT'=>$STR,'TITLE'=>'Clinical trials updates on '.getCurrDate(),'HASH'=>md5(microtime_float().'-CT')));
				}
	
	
	
	

?>