<?php


/**
 SCRIPT NAME: db_ot_clintrial
 PURPOSE:     Process the clinical trials from Open Targets
 
*/
ini_set('memory_limit','500M');
/// Job name - Do not change
$JOB_NAME='db_ot_clintrial';


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


addLog("Setting up");
	
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_ot_rel')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Set the process control directory to the current version so the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];		

	/// Create the directories we are going to use
	if (!is_dir('INSERT') && !mkdir('INSERT'))														failProcess($JOB_ID."005",'Unable to create INSERT directory');


addLog("Working directory: ".$W_DIR);

addLog("Data preparation")	;
	/// Those are the 3 tables we are going to insert into.
	/// So we are opening a file for each them where we are going to put the new data into
	$FILES=array('drug_disease'=>fopen('drug_disease.csv','w'),
				'clinical_trial'=>fopen('clinical_trial.csv','w'),
				'clinical_trial_drug'=>fopen('clinical_trial_drug.csv','w'),
	);

	/// Then we are going to get the max value for the primary key of each table to speed things up
	$DBIDS=array();
	foreach (array_keys($FILES) as $P)
	{
	$res=runQuery("SELECT MAX(".$P."_id) CO FROM ".$P);
	if ($res===false)																				failProcess($JOB_ID."006",'Unable to get max id from '.$P);
	if (count($res)==0)$DBIDS[$P]=0;else $DBIDS[$P]=$res[0]['co'];

	}

	
	/// For each table we are going to insert into, provides the column order in the output fule
	$CTL=array('drug_disease'=>'(drug_disease_id,drug_entry_id,disease_entry_id,gn_entry_Id,max_disease_phase)',
			'clinical_trial'=>'(clinical_trial_id,trial_id,clinical_phase,clinical_status,start_date,source_id)',
			'clinical_trial_drug'=>'(clinical_trial_drug_id,clinical_trial_id,drug_disease_id,ot_score)');

addLog("Getting gene list");
	$TARGETS=getGeneList();

addLog("Process File");
	$LIST=array();

	$MAPPER=array();
	
	// Chembl.json contains all clinical trials
	$fp=fopen('chembl.json','r');if (!$fp)															failProcess($JOB_ID."008",'Unable to open chembl.json');
		
	$N=0;
	$STATS=array();
	$BULK=array(); 	
	while(!feof($fp))
	{
		/// Each line is a json string that we going to decode
		$line=str_replace('\r',"",stream_get_line($fp,10000000,"\n"));
		
		if ($line=='')continue;
		$RAW_DATA=json_decode($line,true);
		
		if ($RAW_DATA===false)																		failProcess($JOB_ID."009",'Unable to interpret json string');
		
		$RAW_DATA['STATUS']='VALID';
		
		
		$BULK[]=$RAW_DATA;
		/// To speed up the process and reduce the amount of queries, we process by bulk of 1000 records
		if (count($BULK)<1000)continue;
		processBulk($BULK);
		echo "FILEPOS : ".ftell($fp);
		foreach ($BULK as &$B)$STATS[$B['STATUS']]++;
		print_R($STATS);
		$BULK=array();
	}
	
	processBulk($BULK);
	foreach ($BULK as &$B)$STATS[$B['STATUS']]++;
	fclose($fp);


addLog("Process indication.json");

	processIndications();
	
addLog("Cleanup duplicates");
	cleanUp();


	print_R($STATS);


successProcess();

	












function getGeneList()
{
	global $GLB_TREE;
	global $TG_DIR;
	global $GLB_VAR;

	addLog("Listing Ensembl genes");
	$fp=fopen('chembl.json','r');if (!$fp)															failProcess($JOB_ID."A01",'Unable to open chembl.json');
		
	$TARGETS=array();
	while(!feof($fp))
	{
		/// Each line is a json string that we going to decode
		$line=str_replace('\r',"",stream_get_line($fp,10000000,"\n"));
		
		if ($line=='')continue;
		$RAW_DATA=json_decode($line,true);
		
		if ($RAW_DATA===false)																		failProcess($JOB_ID."A02",'Unable to interpret json string');
		
		/// We are only interested in the targetId, which are Ensembl Gene Id
		if (!isset($RAW_DATA['targetId']) || $RAW_DATA['targetId']=='')continue;
		
		$TARGETS[$RAW_DATA['targetId']]=array('Gene_id'=>-1,'gn_entry_id'=>-1);
		
	}
	fclose($fp);




	addLog("Mapping Ensembl genes to NCBI Gene");
	/// To map the Ensembl gene to NCBI gene, we need to find the gene2ensembl file from the production directory

	$JOB_GENE=$GLB_TREE[getJobIDByName('prd_gene')];
	$GENE_PRD_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'].'/'.$JOB_GENE['DIR'].'/';
	if (!is_dir($GENE_PRD_DIR))																		failProcess($JOB_ID."A03",'Unable to find prod directory for prd_gene');
	if (!is_file($GENE_PRD_DIR.'/gene2ensembl'))													failProcess($JOB_ID."A04",'Unable to find gene2ensembl');

	$ENS_GENE_MAP=array();
	$fp=fopen($GENE_PRD_DIR.'/gene2ensembl','r');if (!$fp)											failProcess($JOB_ID."A05",'Unable to open gene2ensembl');
	
	
	$line=stream_get_line($fp,1000,"\n");
	


	/// LIST_GENES will be used for querying only
	$LIST_GENES=array();
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		$tab=explode("\t",$line);
		if (!isset($TARGETS[$tab[2]]))continue;
		
		$TARGETS[$tab[2]]['Gene_id']=$tab[1];
		$LIST_GENES[$tab[1]]=$tab[2];
		
	}
	fclose($fp);

	if ($LIST_GENES==array())return $TARGETS;


	addLog("Finding gene records");
	
	
	$res=runQuery("SELECT gn_Entry_Id,gene_id 
					FROM gn_entry
					where gene_id IN (".implode(',',array_keys($LIST_GENES)).")");
	if ($res===false)																				failProcess($JOB_ID."A06",'Unable to search for genes');
	
	foreach ($res as $line)
	{
		$TARGETS[$LIST_GENES[$line['gene_id']]]['gn_entry_id']=$line['gn_entry_id'];
	}
	
	return $TARGETS;
}





function cleanUp()
{
	/// Drugbank will create drug_disease records without a gene
	/// So we are going to remove those when there's a record with a gene provided by open targets
	if (!runQueryNoRes("DELETE FROM clinical_trial_drug 
	where clinical_trial_drug_id IN (
		SELECT ctd2.clinical_trial_drug_id 
		FROM clinical_trial_Drug ctd1, drug_disease dd1, clinical_trial_Drug ctd2, drug_disease dd2
		WHERE 
		Ctd1.drug_disease_id = dd1.drug_disease_id AND
		Ctd2.drug_disease_id = dd2.drug_disease_id AND
		Ctd1.clinical_trial_id =ctd2.clinical_trial_id AND
		dd1.drug_entry_id = dd2.drug_entry_id AND
		dd1.disease_entry_id = dd2.disease_entry_id AND
		dd1.gn_entry_id IS NOT NULL AND 
		dd2.gn_entry_id IS NULL)")) 																failProcess($JOB_ID."B01",'Unable to remove duplicates');

	if (!runQueryNoRes("DELETE FROM drug_disease 
		where drug_disease_id NOT IN (
			SELECT distinct drug_disease_id 
			FROM clinical_trial_drug
		)")) 																						failProcess($JOB_ID."B02",'Unable to remove duplicates');
	
}






function processBulk(&$BULK)
{
	global $GLB_VAR;
	global $CTL;
	global $DB_INFO;
	global $JOB_ID;
	global $DBIDS;
	global $FILES;


	$DRUGS=loadDrugs($BULK);
	
	$DISEASES=loadDiseases($BULK);

	/// We already processed the genes earlier
	/// Now that we have the triplet gene/disease/drug, we can lookup for each entry if that triplet exist
	processTriplets($BULK,$DRUGS,$DISEASES);
echo "R\n";
	processClinicalTrials($BULK);


	foreach ($FILES as $NAME=>&$FP)
	{

		fclose($FP);
		$res=array();
		echo "inserting ". $NAME."\n";
		system('wc -l '.$NAME.'.csv');
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL[$NAME].' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																	failProcess($JOB_ID."C01",'Unable to insert '.$NAME); 
		$FP=fopen($NAME.'.csv','w');if (!$FP)													failProcess($JOB_ID."C02",'Unable to open '.$NAME); 
	}
	
}

	



function loadDrugs(&$BULK)
{
	/// We are first going to search the drug by their molecule name.
	/// Most of them will work here since they use chembl ID, unless they are large molecule
	$DRUGS=array();
	$query="SELECT drug_extdb_value, drug_entry_id 
		FROM drug_extdb dn 
		WHERE LOWER(drug_extdb_value) IN (";
	foreach ($BULK as &$E)
	{
		if (!isset($E['drugId']))
		{
			$E['STATUS']='MISSING DRUG';
			continue;
		}
		$DRUGS[$E['drugId']]=-1;
		/// Otherwise we add it to the query. We also escape the single quote
		$query.="'".strtolower(str_replace("'","''",$E['drugId']))."',";
	}
	$res=runQuery(substr($query,0,-1).')');
	
	if ($res===false)																		failProcess($JOB_ID."D01",'Unable to get drug from molecule names');
	
	foreach ($res as $line)$DRUGS[$line['drug_extdb_value']]=$line['drug_entry_id'];




	$query="SELECT * FROM drug_entry de where LOWER(chembl_id) IN (";
	foreach ($BULK as &$E)
	{
		if (!isset($E['drugId']))
		{
			$E['STATUS']='MISSING DRUG';
			continue;
		}
	
		/// Otherwise we add it to the query. We also escape the single quote
		$query.="'".strtolower(str_replace("'","''",$E['drugId']))."',";
	}
	$res=runQuery(substr($query,0,-1).')');
	if ($res===false)																		failProcess($JOB_ID."D02",'Unable to get drug from molecule names');
	foreach ($res as $line)$DRUGS[$line['chembl_id']]=$line['drug_entry_id'];
	



	/// If it's a large molecule, then we go to the drug name table, which also contains CHEMBL Ids
	$query='SELECT * 
		FROM drug_entry GE, drug_name DN 
		WHERE DN.drug_entry_id = GE.drug_entry_id 
		AND drug_name IN (';
	
	$HAS_MISSING=false;
	foreach ($DRUGS as $DRUG_NAME=>$ID)
	{
		/// Already found it -> continue;
		if ($ID!=-1)continue;

		/// Otherwise we add it to the query. We also escape the single quote
		$query.= "'".str_replace("'","''",$DRUG_NAME)."',";
		$HAS_MISSING=true;
	}
	
	if ($HAS_MISSING)
	{
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false)																failProcess($JOB_ID."D03",'Unable to get drug from drug names');
		foreach ($res as $line)
		{
			$DRUGS[$line['drug_name']]=$line['drug_entry_id'];
		}
	}

	return $DRUGS;
}








function loadDiseases(&$BULK)
{
	/// Next we search by disease
	$DISEASES=array();
	
	$query="SELECT disease_entry_id,disease_tag 
		FROM disease_entry 
		WHERE disease_tag IN (";

	foreach ($BULK as &$E)
	{
		if ($E['STATUS']!='VALID')continue;
		
		/// No disease, then we flag it and continue
		if (!isset($E['diseaseFromSourceMappedId']))
		{
			$E['STATUS']='MISSING DISEASE';
			continue;
		}

		/// Empty, can't do anything
		if ($E['diseaseFromSourceMappedId']=='')continue;

		/// We add it to the list of disease to search
		$DISEASES[strtolower($E['diseaseFromSourceMappedId'])]=-1;
		
		/// We also add it to the query
		$query.= "'".$E['diseaseFromSourceMappedId']."',";

	}
	$res=runQuery(substr($query,0,-1).')');
	if ($res===false )																		failProcess($JOB_ID."E01",'Unable to get diseases');
	
	
	foreach ($res as $line)$DISEASES[strtolower($line['disease_tag'])]=$line['disease_entry_id'];


	/// In many instances, those disease tag are in the format DBNAME_DBID
	/// so we query those to find the corresponding disease

	$query='SELECT disease_entry_id,source_name,disease_extdb 
		FROM  disease_extdb DX, source S
		WHERE DX.source_id = S.source_id 
		AND  (LOWER(source_name),disease_extdb) IN (';

	$HAS_MISSING=false;

	foreach ($DISEASES as $DISEASE=>$ID)
	{
		if ($ID!=-1)continue;;

		$TAG_TAB=explode("_",$DISEASE);

		$query.= "(LOWER('".$TAG_TAB[0]."'),'".$TAG_TAB[1]."'),";
		
		$HAS_MISSING=true;
	}
	if (!$HAS_MISSING) return $DISEASES;
	
	$res=runQuery(substr($query,0,-1).')');
	if ($res===false )																	failProcess($JOB_ID."E02",'Unable to get disease from identifier');
	foreach ($res as $line)
	{
		$DISEASES[strtolower($line['source_name']."_".$line['disease_extdb'])]=$line['disease_entry_id'];
	}
	
	return $DISEASES;
}



function processTriplets(&$BULK,&$DRUGS,&$DISEASES)
{

	global $TARGETS;
	global $DBIDS;
	global $FILES;


	$query= "SELECT drug_disease_id,drug_entry_id,disease_entry_id,max_disease_phase,gn_entry_id 
		FROM drug_disease 
		WHERE (disease_entry_id,gn_entry_id,drug_entry_id) IN (";
	
	$HAS_MISSING=false;
	
	$MAPPING=array();
	
	$UPDATE=array();
	
	foreach ($BULK as $K=> &$RAW_DATA)
	{
		if ($RAW_DATA['STATUS']!='VALID')continue;
		
		// If either one are not existing, we are flagging the entry and ignore it from now on
		if ($TARGETS[$RAW_DATA['targetId']]['gn_entry_id']==-1)
		{
			$RAW_DATA['STATUS']='NO TARGET FOUND';
			continue;
		}else $TARGET_ID=$TARGETS[$RAW_DATA['targetId']]['gn_entry_id'];

		if ($DISEASES[strtolower($RAW_DATA['diseaseFromSourceMappedId'])]==-1)
		{
		//	echo $RAW_DATA['diseaseFromSourceMappedId']."\n";
			$RAW_DATA['STATUS']='NO DISEASE FOUND';
			continue;
		}else $DISEASE_ID=$DISEASES[strtolower($RAW_DATA['diseaseFromSourceMappedId'])];


		if ($DRUGS[$RAW_DATA['drugId']]==-1)
		{
			$RAW_DATA['STATUS']='NO DRUG FOUND';
			continue;
		}else $DRUG_ID=$DRUGS[$RAW_DATA['drugId']];


		$RAW_DATA['DRUG_ID']		 =$DRUG_ID;
		$RAW_DATA['DISEASE_ENTRY_ID']=$DISEASE_ID;
		$RAW_DATA['GENE_ENTRY_ID']	 =$TARGET_ID;
		
		/// We add it to the query
		$query.='('.$RAW_DATA['DISEASE_ENTRY_ID'].','.$RAW_DATA['GENE_ENTRY_ID'].','.$DRUGS[$RAW_DATA['drugId']].'),';

		/// Trigger to boolean so we know we have at least one record that is missing
		$HAS_MISSING=true;
		/// And we also add it to the mapping so we can retrieve the record later
		$MAPPING[$RAW_DATA['DISEASE_ENTRY_ID'].','.$RAW_DATA['GENE_ENTRY_ID'].','.$DRUGS[$RAW_DATA['drugId']]][]=$K;
	}


	if ($HAS_MISSING)
	{
		$res=runQuery(substr($query,0,-1).')');if ($res===false )											failProcess($JOB_ID."F01",'Unable to search for drug diseases');
		
		/// Now the queried those triplets so we know which record has a corresponding db entry
		foreach ($res as $line)
		{
			$t=$line['disease_entry_id'].','.$line['gn_entry_id'].','.$line['drug_entry_id'];
			foreach ($MAPPING[$t] as $K)
			{
				
				$RAW_DATA=&$BULK[$K];
				if ($RAW_DATA['STATUS']!='VALID')continue;

				$RAW_DATA['DRUG_DISEASE_ID']=$line['drug_disease_id'];

				if ($RAW_DATA['clinicalPhase']<=$line['max_disease_phase'])continue;

				$UPDATE[$RAW_DATA['clinicalPhase']][]=$line['drug_disease_id'];
				
			}
		}
	}

	if ($UPDATE!=array())
	{
		foreach ($UPDATE as $MAX_PHASE=>&$LIST_UPD)
		{
			addLog("Updating max phase to ".$MAX_PHASE." for ".count($LIST_UPD)." records");
			$query='UPDATE drug_disease 
					SET max_disease_phase='.$MAX_PHASE.' 
					WHERE drug_disease_id IN ('.implode(',',$LIST_UPD).')';
			if (!runQueryNoRes($query))																failProcess($JOB_ID."F02",'Unable to update drug disease table');
		}
	}


	// Now the issue is that some drugs comes in different salt form.
	/// We don't make that distinction here.
	/// therefore, what open targets consider as different drugs, we consider them as one and the same.
	/// Thus, multiple open targets records can describe the same triplet
	/// So to ensure unicity, we create a MISSING array containing unique triplets to add

	$MISSING=array();
	foreach ($BULK as $K=> &$RAW_DATA)
	{
		if ($RAW_DATA['STATUS']!= 'VALID')continue;
		if (isset($RAW_DATA['DRUG_DISEASE_ID']))continue;
		
		$T=$DRUGS[$RAW_DATA['drugId']]."\t".$DISEASES[strtolower($RAW_DATA['diseaseFromSourceMappedId'])]."\t".$RAW_DATA['GENE_ENTRY_ID'];
		
		if (!isset($MISSING[$T]))$MISSING[$T]=array($RAW_DATA['clinicalPhase'],array($K));
		else 					 $MISSING[$T][0]=max($RAW_DATA['clinicalPhase'],$MISSING[$T][0]);
		
		$MISSING[$T][1][]=$K;
	}

	/// And then we push them in the file for insertion
	foreach ($MISSING as $T=>$P)
	{	
		$DBIDS['drug_disease']++;
		foreach ($P[1] as $K)$BULK[$K]['DRUG_DISEASE_ID']=$DBIDS['drug_disease'];
		
		fputs($FILES['drug_disease'],$DBIDS['drug_disease']."\t".$T."\t".$RAW_DATA['clinicalPhase']."\n");
	}
}



function processClinicalTrials(&$BULK)
{
	global $JOB_ID;
	global $DBIDS;
	global $FILES;
	$ADDED_CTD=array();
	/// Now we look for clinical trials
	$query="SELECT * FROM clinical_trial WHERE trial_id IN (";
	$HAS_QUERY=false;
	foreach ($BULK as &$RAW_DATA)
	{
		/// But only for records that haven't been discarded before
		if ($RAW_DATA['STATUS']!= 'VALID')continue;
		
		/// Defines the trial ID after looking at different rules:
		$TRIAL_ID=-1;

		/// Each clinical trial has its own id and Open targets provides the URL, not the ID
		/// so we extract the id
		if ($RAW_DATA['urls'][0]['niceName']=='ClinicalTrials')
		{
			/// US Clinical trials are in the format NCTXXXXXXXX
			if(!preg_match("/(NCT[0-9]{8})/",$RAW_DATA['urls'][0]['url'],$matches))
			{
				$RAW_DATA['STATUS']= "NO_CLINTRIAL_MATCH";
				continue;
			}
			
			$TRIAL_ID=$matches[0];
		}
		else if ($RAW_DATA['urls'][0]['niceName']=='ATC')
		{
			$TRIAL_ID=substr($RAW_DATA['urls'][0]['url'],strrpos($RAW_DATA['urls'][0]['url'],'=')+1);
		}	
		else if ($RAW_DATA['urls'][0]['niceName']=='DailyMed')
		{
			$TRIAL_ID=substr($RAW_DATA['urls'][0]['url'],strrpos($RAW_DATA['urls'][0]['url'],'=')+1);
		}
		else if ($RAW_DATA['urls'][0]['niceName']=='FDA')
		{
			if (strpos($RAW_DATA['urls'][0]['url'],'accessdata.fda.gov')!==false)
			{
				$RAW_DATA['STATUS']= "FDA_ACCESS_DATA";
				continue;
			}

			$TRIAL_ID=substr($RAW_DATA['urls'][0]['url'],strrpos($RAW_DATA['urls'][0]['url'],':')+1);
		}
		else 
		{
			$RAW_DATA['STATUS']= "UNRECOGNIZED_CLINICAL_SOURCE";
			continue;
		}

		/// We didn't find it -> flag it and continue
		if ($TRIAL_ID==-1) 
		{
			$RAW_DATA['STATUS']= "NOTFOUND_CLINICAL_SOURCE";
			continue;
		}

		/// Add the trial_id to the record
		$RAW_DATA['TRIAL_ID']=$TRIAL_ID;

		/// And add it to the query
		$query.="'".$TRIAL_ID."',";
		
		/// Trigger to boolean so we know we have at least one record that is missing
		$HAS_QUERY=true;
	}
	/// And search for it.
	$TRIALS=array();



	if ($HAS_QUERY)
	{
		$res=runQuery(substr($query,0,-1).')');		
		if ($res===false)																		failProcess($JOB_ID."G01",'Unable to search for clinical trials.');
	
		foreach ($res as $line)$TRIALS[$line['trial_id']]=$line;
	}


	$CLINICAL_TRIAL_DRUG=array();
	
	
	/// Now that we have the clinical trial id, we can add the new ones to the database

	$ADDED=array();
	foreach ($BULK as &$RAW_DATA)
	{
		if ($RAW_DATA['STATUS']!= 'VALID')continue;
		
		$SOURCE_ID=getSource($RAW_DATA['urls'][0]['niceName']);
		
		if (!isset($RAW_DATA['clinicalStatus']))$RAW_DATA['clinicalStatus']='';
		
		
		/// If the clinical trial is not the database, then we add it.
		if (!isset($TRIALS[$RAW_DATA['TRIAL_ID']]))
		{
			$DBIDS['clinical_trial']++;
			fputs($FILES['clinical_trial'],$DBIDS['clinical_trial'].
			"\t".$RAW_DATA['TRIAL_ID'].
			"\t".$RAW_DATA['clinicalPhase'].
			"\t".$RAW_DATA['clinicalStatus'].
			"\t".(isset($RAW_DATA['studyStartDate'])?$RAW_DATA['studyStartDate']:"NULL").
			"\t".$SOURCE_ID."\n");
		
			$CLIN_TRIAL_ID=$DBIDS['clinical_trial'];
			$TRIALS[$RAW_DATA['TRIAL_ID']]=array('clinical_trial_id'=>$DBIDS['clinical_trial'],
			'clinical_phase'=>$RAW_DATA['clinicalPhase'],
			'clinical_status'=>$RAW_DATA['clinicalStatus']
			);
			if (isset($ADDED_CTD[$CLIN_TRIAL_ID][$RAW_DATA['DRUG_DISEASE_ID']]))continue;
			/// And therefore the mapping to the drug is also missing so we add it
			$DBIDS['clinical_trial_drug']++;
			$ADDED_CTD[$CLIN_TRIAL_ID][$RAW_DATA['DRUG_DISEASE_ID']]=true;
			

			/// Increment the id
			$DBIDS['clinical_trial_drug']++;
			fputs($FILES['clinical_trial_drug'],
				$DBIDS['clinical_trial_drug']."\t".
				$CLIN_TRIAL_ID."\t".
				$RAW_DATA['DRUG_DISEASE_ID']."\t".
				$RAW_DATA['score']."\n");
			continue;
		}

		/// Otherwise we compare to see if there's any change
		$ENTRY=&$TRIALS[$RAW_DATA['TRIAL_ID']];
		$CLIN_TRIAL_ID=$ENTRY['clinical_trial_id'];
		
		$CLINICAL_TRIAL_DRUG[$RAW_DATA['DRUG_DISEASE_ID'].','.$CLIN_TRIAL_ID]=array(-1,$RAW_DATA['score']);
	}
	
	/// Now we look for the clinical trial drug of existing clinical trials:
	if ($CLINICAL_TRIAL_DRUG!=array())
	{
		$query='SELECT clinical_trial_drug_id,clinical_trial_id,drug_disease_id,ot_score 
				FROM clinical_trial_drug 
				WHERE (drug_disease_id,clinical_trial_id) IN (';
		
		foreach ($CLINICAL_TRIAL_DRUG as $N=>$DUMMY)	$query.='('.$N.'),';
		
		$res=runQuery(substr($query,0,-1).')');	
		if ($res===false)																				failProcess($JOB_ID."G02",'Unable to search for clinical trials.');
		$UPDATE=array();
		foreach ($res as $line)
		{
			$N=$line['drug_disease_id'].','.$line['clinical_trial_id'];
			$CTD_ENTRY=&$CLINICAL_TRIAL_DRUG[$N];
			$CTD_ENTRY[0]=$line['clinical_trial_drug_id'];
			if ($CTD_ENTRY[1]==$line['ot_score'])continue;
			$UPDATE[$CTD_ENTRY[1]][]=$line['clinical_trial_drug_id'];
			
		
			
		}
		if ($UPDATE!=array())
		foreach ($UPDATE as $SCORE=>&$LIST_UPD)
		{
			addLog("Updating score to ".$SCORE." for ".count($LIST_UPD)." records");
			
			$query='UPDATE clinical_trial_drug 
					SET ot_score='.$SCORE.' 
					WHERE clinical_trial_drug_id IN ('.implode(',',$LIST_UPD).')';
			if (!runQueryNoRes($query))																failProcess($JOB_ID."G03",'Unable to update clinical trial drug table');
		}
		echo "#####X#####\n";

		foreach ($CLINICAL_TRIAL_DRUG as $N=>&$INFO)
		{
			
			if ($INFO[0]!=-1)continue;
			/// If the clinical trial drug is not in the database, then we add it.
			$T=explode(',',$N);
			if (isset($ADDED_CTD[$T[1]][$T[0]]))
			{
				echo "OUT\n";
				continue;
			}
			echo $T[1]."\t".$T[0]."\n";

			/// Increment the id
			$DBIDS['clinical_trial_drug']++;
			$ADDED_CTD[$T[1]][$T[0]]=true;
			fputs($FILES['clinical_trial_drug'],
				$DBIDS['clinical_trial_drug']."\t".
				$T[1]."\t".
				$T[0]."\t".
				$INFO[1]."\n");
				
		}
	}
	echo "#\n";
}





function processIndications()
{
	global $GLB_VAR;
	global $CTL;
	global $DB_INFO;
	global $JOB_ID;
	global $DBIDS;
	global $FILES;
	/// Next we are going to process indication.json


	$STATS=array(
		'CPD_NOT_FOUND'=>0,
		'DISEASE_NOT_FOUND'=>0,
		'NEW_DRUG_DISEASE'=>0,
		'NEW_CLINICAL_TRIAL'=>-1);


	$fp=fopen('indication.json','r');	if (!$fp)													failProcess($JOB_ID."H01",'Unable to indication.json'); 
	$STACK=array();
	
	$N=0;
	$DATA=array();
	$LIST_CPD=array();
	$LIST_DISEASE=array();
	$LIST_DRUG=array();
	
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000000,"\n");
		if ($line=='')continue;
		
		$RAW_DATA=json_decode($line,true);if ($RAW_DATA===false)									failProcess($JOB_ID."H02",'Unable to interpret json'); 
		
		$DATA[]=$RAW_DATA;
		
		$LIST_CPD[$RAW_DATA['id']]=-1;
		
		foreach ($RAW_DATA['indications'] as &$IND)
		{
			$LIST_DISEASE[$IND['disease']]=array($IND['efoName'],-1);
			foreach ($IND['references'] as &$REF)
			{
				foreach ($REF['ids'] as $C_ID)$LIST_CLIN[$REF['source']][$C_ID]=-1;
			}
		}
	}
	fclose($fp);



	/// Getting the list of clinical trials based on their ID and source:
	foreach ($LIST_CLIN as $CLIN_SOURCE=>&$LIST_CS)
	{
		$query="SELECT clinical_trial_id,trial_id 
			FROM clinical_trial CT,source S 
			WHERE S.source_id = CT.source_Id 
			AND LOWER(source_name)='".strtolower($CLIN_SOURCE)."' 
			AND trial_id IN (";
		
		// We consider all trials:
		foreach ($LIST_CS as $C_ID=>$ID)$query.="'".$C_ID."',";
		/// Remove the last comma
		$query=substr($query,0,-1).')';
		
		$res=runQuery($query);
		
		if ($res===false)																			failProcess($JOB_ID."H03",'Unable to search for clinical trial');
		
		foreach ($res as $line)$LIST_CS[$line['trial_id']]=$line['clinical_trial_id'];
	}

	/// Then we do a search but this time without the source name
	foreach ($LIST_CLIN as $CLIN_SOURCE=>&$LIST_CS)
	{
		$query="SELECT clinical_trial_id,trial_id, source_name 
				FROM clinical_trial CT,source S 
				WHERE S.source_id = CT.source_Id 
				AND trial_id IN (";
		
		$HAS_NAMES=false;

		foreach ($LIST_CS as $C_ID=>$ID)
		{
			/// Already found it -> continue;
			if ($ID!=-1)continue;
			
			$query.="'".$C_ID."',";
			$HAS_NAMES=true;
		}

		/// No new clinical trial to query -> continue;
		if (!$HAS_NAMES)continue;
		
		$query=substr($query,0,-1).')';
		$res=runQuery($query);
		if ($res===false)																			failProcess($JOB_ID."H04",'Unable to search for clinical trial');
		
		foreach ($res as $line)
		{
			// We alert the user that the source name is different
			echo $line['trial_id']."\tMISMATCH SOURCE\t".$CLIN_SOURCE."\t".$line['source_name']."\n";
			$LIST_CS[$line['trial_id']]=$line['clinical_trial_id'];
		}
		
	}

	/// Any clinical trial we didn't find, we add it to the database
	foreach ($LIST_CLIN as $CLIN_SOURCE=>&$LIST_CS)
	{
		
		foreach ($LIST_CS as $C_ID=>&$ID)
		{
			if ($ID!=-1)continue;
			$CLIN_SOURCE_ID=getSource($CLIN_SOURCE);
			$DBIDS['clinical_trial']++;
			fputs($FILES['clinical_trial'],
				$DBIDS['clinical_trial']."\t".
				$C_ID."\tNULL\tNULL\tNULL\t".
				$CLIN_SOURCE_ID."\n");

			$ID=$DBIDS['clinical_trial'];
		}
	}
	


	/// Then we search for drugs based on smal molecule names:
	$query = "SELECT drug_entry_id, sm_name 
		FROM sm_source ss, sm_entry se, molecular_entity me, drug_mol_entity_map dm 
		where se.md5_hash = me.molecular_structure_hash 
		AND dm.molecular_entity_id = me.molecular_entity_id
		AND se.sm_entry_Id =ss.sm_entry_id
		AND sm_name IN (";
	
	foreach ($LIST_CPD as $S=>$ID)$query.="'".$S."',";
	$res=runQuery(substr($query,0,-1).')');
	if ($res===false)																			failProcess($JOB_ID."H05",'Unable to search for drugs by chembl id');

	foreach ($res as $line) $LIST_CPD[$line['sm_name']]=$line['drug_entry_id'];



	/// Then we search for drugs based on their name
	$query="SELECT DISTINCT de.drug_entry_id, drug_name 
		FROM drug_entry de,drug_name dr  
		WHERE dr.drug_entry_id = de.drug_entrY_id 
		AND drug_name IN (";
	$HAS_NAMES=false;
	foreach ($LIST_CPD as $S=>$ID)
	{
		/// Only if we didn't find it already
		if ($ID!=-1)continue;
		$HAS_NAMES=true;
		$query.="'".$S."',";
	}
	if ($HAS_NAMES)
	{
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false)																			failProcess($JOB_ID."H06",'Unable to search for drugs by chembl id');

		foreach ($res as $line) $LIST_CPD[$line['drug_name']]=$line['drug_entry_id'];
	}
	


	/// Then we search for disease based on tags:
	$query="SELECT disease_entry_id,disease_tag 
		FROM disease_entry 
		WHERE DISEASE_TAG IN (";
	$HAS_NAMES=false;
	foreach ($LIST_DISEASE as $S=>$ID)
	{
		if ($ID[1]!=-1)continue;
		$HAS_NAMES=true;
		$query.="'".$S."',";
	}
	if ($HAS_NAMES)
	{
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false)																			failProcess($JOB_ID."H07",'Unable to search for drugs by chembl id');

		foreach ($res as $line) $LIST_DISEASE[$line['disease_tag']][1]=$line['disease_entry_id'];
	}


	//// Or by their names:
	$query="SELECT disease_entry_id, disease_name 
			FROM disease_entry 
			WHERE LOWER(disease_name) IN (";
	
	$HAS_NAMES=false;
	$MAP=array();
	
	foreach ($LIST_DISEASE as $S=>$ID)
	{
		if ($ID[1]!=-1)continue;
		$HAS_NAMES=true;
		
		$MAP[strtolower($ID[0])]=$S;
		/// We also escape the single quote and do lowercase
		$query.="'".strtolower(str_replace("'","''",$ID[0]))."',";
	}


	if ($HAS_NAMES)
	{
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false)																			failProcess($JOB_ID."H08",'Unable to search for drugs by chembl id');

		foreach ($res as $line) $LIST_DISEASE[$MAP[strtolower($line['disease_name'])]][1]=$line['disease_entry_id'];
	}


	/// Or by synonynms:
	$query="SELECT disease_entry_id, syn_value 
		FROM disease_syn 
		WHERE LOWER(syn_value) IN (";
	
	$HAS_NAMES=false;
	$MAP=array();
	foreach ($LIST_DISEASE as $S=>$ID)
	{
		if ($ID[1]!=-1)continue;
		$HAS_NAMES=true;$MAP[strtolower($ID[0])]=$S;
		$query.="'".strtolower(str_replace("'","''",$ID[0]))."',";
	}
	if ($HAS_NAMES)
	{
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false)																			failProcess($JOB_ID."H09",'Unable to search for drugs by chembl id');

		foreach ($res as $line) $LIST_DISEASE[$MAP[strtolower($line['syn_value'])]][1]=$line['disease_entry_id'];
	}


	$DRUG_DISEASES=array();
	
	foreach ($DATA as &$RAW_DATA)
	{

		if ($LIST_CPD[$RAW_DATA['id']]==-1)continue;
		
		$DRUG_ENTRY_ID=&$LIST_CPD[$RAW_DATA['id']];
		foreach ($RAW_DATA['indications'] as &$IND)
		{

			if ($LIST_DISEASE[$IND['disease']][1]==-1)continue;
			
			$DISEASE_ID=&$LIST_DISEASE[$IND['disease']][1];
			
			if (!isset($DRUG_DISEASES[$DRUG_ENTRY_ID][$DISEASE_ID]))
			$DRUG_DISEASES[$DRUG_ENTRY_ID][$DISEASE_ID]=array(-1,$IND['maxPhaseForIndication'],array());
			else $DRUG_DISEASES[$DRUG_ENTRY_ID][$DISEASE_ID][1]=max($DRUG_DISEASES[$DRUG_ENTRY_ID][$DISEASE_ID][1],$IND['maxPhaseForIndication']);

			foreach ($IND['references'] as &$REF)
			{
			
				foreach ($REF['ids'] as &$C_ID)
				{
					//echo $REF['source'].'|'.$C_ID."\t".isset($LIST_CLIN[$REF['source']])."\t".isset($LIST_CLIN[$REF['source']][$C_ID])."\n";
					if ($LIST_CLIN[$REF['source']][$C_ID]==-1)continue;
				$CLIN_ID=&$LIST_CLIN[$REF['source']][$C_ID];
				//echo $CLIN_ID."\n";
				if (is_array($CLIN_ID)){print_R($CLIN_ID);continue;}
					$DRUG_DISEASES[$DRUG_ENTRY_ID][$DISEASE_ID][2][$CLIN_ID]=-1;
				}
			}

		}	
		

	}


	if ($DRUG_DISEASES!=array())
	{
		$query="SELECT drug_disease_id,disease_entry_id,drug_entry_id 
		FROM drug_disease 
		WHERE gn_entry_id IS NULL 
		AND (disease_entry_id,drug_entry_id) IN (";
		$N=0;
		foreach ($DRUG_DISEASES as $DRUG_ID=>&$LIST_DS)
		foreach ($LIST_DS as $DS_ID=>&$INFO)
		{
			$query.="(".$DS_ID.','.$DRUG_ID.'),';
			++$N;
			if ($N<5000)continue;
			$query=substr($query,0,-1).')';
			$res=runQuery($query);
			
			foreach ($res as $line)$DRUG_DISEASES[$line['drug_entry_id']][$line['disease_entry_id']][0]=$line['drug_disease_id'];
			
			$query="SELECT drug_disease_id,disease_entry_id,drug_entry_id 
				FROM drug_disease 
				WHERE gn_entry_id IS NULL 
				AND (disease_entry_id,drug_entry_id) IN (";
			$N=0;
		}
	 	if ($N>0)
		{
			$query=substr($query,0,-1).')';
			$res=runQuery($query);
			
			foreach ($res as $line)$DRUG_DISEASES[$line['drug_entry_id']][$line['disease_entry_id']][0]=$line['drug_disease_id'];
		}
	
	
		foreach ($DRUG_DISEASES as $DRUG_ID=>&$LIST_DS)
		foreach ($LIST_DS as $DS_ID=>&$INFO)
		{
			if ($INFO[0]!=-1)continue;
			$DBIDS['drug_disease']++;
			$INFO[0]=$DBIDS['drug_disease'];
			
			fputs($FILES['drug_disease'],
				$DBIDS['drug_disease']."\t".
				$DRUG_ID."\t".
				$DS_ID."\tNULL\t".
				$INFO[1]."\n");
			
		}


		
			$SEL=array();
		$N=0;$MAP=array();
		foreach ($DRUG_DISEASES as $DRUG_ID=>&$LIST_DS)
		foreach ($LIST_DS as $DS_ID=>&$INFO)
		foreach ($INFO[2] as $CL_ID=>$CL_DBID)
		{
			if (!isset($INFO[0]))continue;
			$SEL[]="(".$CL_ID.','.$INFO[0].')';
			$MAP[$INFO[0]]=array($DRUG_ID,$DS_ID);
			++$N;
			if ($N<5000)continue;
			
			$query="SELECT clinical_trial_drug_id, clinical_trial_id, drug_disease_id 
			FROM clinical_trial_drug 
			WHERE (clinical_trial_id ,drug_disease_id) IN  (".implode(',',$SEL).')';
			//echo $query."\n";
			$SEL=array();
			$res=runQuery($query);
			
			foreach ($res as $line)
			{
				$DR_R=$MAP[$line['drug_disease_id']][0];
				$DS_R=$MAP[$line['drug_disease_id']][1];
				$DRUG_DISEASES[$DR_R][$DS_R][2][$line['clinical_trial_id']]=$line['clinical_trial_drug_id'];
			}
			// $query="SELECT clinical_trial_drug_id, clinical_trial_id, drug_disease_id FROM clinical_trial_drug WHERE (clinical_trial_id ,drug_disease_id) IN  (";
			// $N=0;$MAP=array();
		}
		 
		if ($N>0)
		{
			$query=substr($query,0,-1).')';
			$res=runQuery($query);
			if ($res===false)																	failProcess($JOB_ID."H10",'Unable to search for clinical trial drug');
			foreach ($res as $line)
			{
				$DR_R=&$MAP[$line['drug_disease_id']][0];
				$DS_R=&$MAP[$line['drug_disease_id']][1];
				$DRUG_DISEASES[$DR_R][$DS_R][2][$line['clinical_trial_id']]=$line['clinical_trial_drug_id'];
			}
		 }
	

		foreach ($DRUG_DISEASES as $DRUG_ID=>&$LIST_DS)
		foreach ($LIST_DS as $DS_ID=>&$INFO)
		foreach ($INFO[2] as $CL_ID=>$CL_DBID)
		{
			if ($CL_DBID!=-1||!isset($INFO[0]))continue;
			$DBIDS['clinical_trial_drug']++;
			
			fputs($FILES['clinical_trial_drug'],$DBIDS['clinical_trial_drug']."\t".$CL_ID."\t".$INFO[0]."\tNULL\n");
		}
	}



	foreach ($FILES as $NAME=>&$FP)
	{

		fclose($FP);
		$res=array();
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL[$NAME].' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																	failProcess($JOB_ID."H11",'Unable to insert '.$NAME); 
		
	}

}
?>
