<?php

ini_set('memory_limit','2000M');

/**
 SCRIPT NAME: db_clinvar
 PURPOSE:     Process all clinvar files
 
*/

/// Job name - Do not change
$JOB_NAME='db_clinvar';

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
	$CK_CLINVAR_INFO=$GLB_TREE[getJobIDByName('ck_clinvar_rel')];
	/// Get the working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_CLINVAR_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_CLINVAR_INFO['TIME']['DEV_DIR'];if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

addLog("Working directory:".$W_DIR);
	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=$CK_CLINVAR_INFO['TIME']['DEV_DIR'];
	
	/// Static data from other data sources or Clinvar we need to load in memory
	$STATIC_DATA=array('SIGN'=>array(),'TYPE'=>array(),'DISEASE'=>array(),'GENES'=>array());
	$UNK_DISEASE=array();
	

addLog("Check static data");
	/// Check if static_dir is set in CONFIG_GLOBAL
	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$CK_CLINVAR_INFO['DIR']; 
	if (!is_dir($STATIC_DIR))															failProcess($JOB_ID."005",'Unable to find clinvar static dir at '.$STATIC_DIR);

	/// Check if static files are present
	/// TYPE_SO contains mapping between clinvar types and Sequence Ontology records
	$TYPE_SO=$STATIC_DIR.'/type_so_map.csv';if (!checkFileExist($TYPE_SO))				failProcess($JOB_ID."006",'Unable to find clinvar static file type_so_map.csv  at '.$TYPE_SO);


/// Loading all static data, i.e. data we use as annotations that are coming from either Clinvar or other sources
	preloadClinvarData($TYPE_SO);



addLog("Get MAx DBIDS")	;
	/// For each table that we are going to insert into, 
	/// we want to know the highest primary key value to do quick insertion
	$DBIDS=array('clinical_variant_entry'=>-1,   
	'clinical_variant_submission'=>-1,      
	'clinical_variant_disease_map'=>-1,   
	'clinical_variant_gn_map'=>-1,
	'clinical_variant_map'=>-1,
	'clinical_variant_pmid_map'=>-1);

	/// We also need to know the column name of the primary key for each table
	$DBIDS_MAP=array('clinical_variant_entry'=>'clinvar_entry_id',   
	'clinical_variant_submission'=>'clinvar_submission_id',      
	'clinical_variant_disease_map'=>'clinvar_disease_map_id',   
	'clinical_variant_gn_map'=>'clinvar_gn_map_id',
	'clinical_variant_map'=>'clinical_variant_map_id',
	'clinical_variant_pmid_map'=>'clinvar_pmid_map_id');


	/// Get max primary key value for each table
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$DBIDS_MAP[$TBL].') CO FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."007",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}
	$DBIDS['DESC_FILES']=0;
	
	/// To insert properly, we need to provide the column order for each table
	$COL_ORDER=array(
		'clinical_variant_entry'=>'(clinvar_entry_id,clinvar_variation_id,clinical_variant_type_id,clinical_variant_review_status,n_submitters,clinical_variant_name,last_submitted_date)',   
		'clinical_variant_submission'=>'(clinvar_submission_id,clinvar_entry_id,clin_sign_id,clinical_variant_review_status,scv_id,collection_method,submitter,interpretation,last_evaluation_date)',      
		'clinical_variant_disease_map'=>'(clinvar_disease_map_id,clinvar_submission_id,disease_entry_id)',   
		'clinical_variant_gn_map'=>'(clinvar_gn_map_id,clinvar_submission_id,gn_entry_id)',
		'clinical_variant_map'=>'(clinical_variant_map_id,clinvar_entry_id,variant_entry_id)',
		'clinical_variant_pmid_map'=>'(clinvar_pmid_map_id  ,clinvar_submission_id,pmid_entry_id )'
	);
	
	/// Status for each file, so we know if we need to trigger an insertion
	$F_STATUS=array();
	foreach ($COL_ORDER as $NAME=>$CTL)	$F_STATUS[$NAME]=false;

	addLog("Open files");
	if (!is_dir('INSERT') && !mkdir('INSERT'))										failProcess($JOB_ID."008",'Unable to create INSERT directory');
	
	

	/// Opening files
	$FILES=array();
	foreach ($COL_ORDER as $TYPE=>$CTL)
	{
		$FILES[$TYPE]=fopen('INSERT/'.$TYPE.'.csv','w');
		if (!$FILES[$TYPE])														failProcess($JOB_ID."009",'Unable to open '.$TYPE.'.csv');
	}
	

	addLog("Processing Clinvar");
		processVariantsSummary(); 
	addLog("Process Submission ");	
		processSubmissionFile();
	addLog("Process Variant ");	
		processVariantMapFile();
		processCitationFile();
		processInterpretation();





successProcess();




/////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		
		
		
		
		
		
		
		
		
		



function preloadClinvarData($TYPE_SO)
{
	global $JOB_ID;
	global $STATIC_DATA;
	addLog("Preload diseases");

	/// First getting all the disease name/syn/external database id
	/// Everything is going to be lower case to avoid case sensitivity
	$res=runQuery("SELECT * FROM disease_extdb"); 
	if ($res===false)																	failProcess($JOB_ID."A01",'Unable to get disease_extdb');
	foreach ($res as $line)
	{
		$EXTDB="'".strtolower($line['disease_extdb'])."'";
		$STATIC_DATA['DISEASE']['EXTDB'][$EXTDB]=$line['disease_entry_id'];
	}
				
	$res=runQuery("SELECT disease_name,disease_entry_id FROM disease_entry");
	if ($res === false)																failProcess($JOB_ID."A02",'Unable to get disease_entry');
	foreach ($res as $line)
	{
		$DS_NAME="'".strtolower($line['disease_name'])."'";
		$STATIC_DATA['DISEASE']['NAME'][$DS_NAME]=$line['disease_entry_id'];
	}
	
	$res=runQuery("SELECT syn_value,disease_entry_id FROM disease_syn");
	if ($res === false)																failProcess($JOB_ID."A03",'Unable to get disease_syn');
	foreach ($res as $line)
	{
		$SYN_VALUE="'".strtolower($line['syn_value'])."'";
		$STATIC_DATA['DISEASE']['SYN'][$SYN_VALUE]=$line['disease_entry_id'];
	}

	/// Next we get all the gene symbol/synonym
	$res=runQuery("SELECT DISTINCT gn_entry_id, symbol from mv_gene_sp where  tax_id='9606'");
	if ($res === false)																failProcess($JOB_ID."A04",'Unable to get gene symbol');
	echo count($res)."\n";
	foreach ($res as $line)
	{
		$SYMBOL="'".strtolower($line['symbol'])."'";
		$STATIC_DATA['GENES']['SYMBOL'][$SYMBOL]=$line['gn_entry_id'];
	}

	
	$res=runQuery("SELECT DISTINCT gn_entry_id, syn_value from mv_gene_sp where  tax_id='9606'");
	if ($res === false) 															failProcess($JOB_ID."A05",'Unable to get gene synonym');
	foreach ($res as $line)
	{
		$SYN="'".strtolower($line['syn_value'])."'";
		$STATIC_DATA['GENES']['SYN'][$SYN][]=$line['gn_entry_id'];
	}

	echo count($STATIC_DATA['DISEASE']['EXTDB'])."\t".count($STATIC_DATA['DISEASE']['NAME'])."\t".count($STATIC_DATA['DISEASE']['SYN'])."\n";


		
	addLog("Processing predefined variant type");
	/// Clinvar assign to each record the type describing the variant
	//// This list of types is static, so it has been defined in static directory in the file type_so_map.csv
	//// Each type can be mapped to a sequence ontology record
	//// Here we ensure that all types are correctly storecd in the system

	$res=runQuery("SELECT * FROM clinical_variant_type v 
					LEFT JOIN so_entry s ON s.so_entrY_id=v.so_entry_id");
	if ($res===false)																	failProcess($JOB_ID."A05",'Unable to get clinical variant type');
	$TYPE_SO_ID=-1;
	foreach ($res as $line)
	{
		$STATIC_DATA['TYPE'][strtolower($line['clinical_variant_type'])]=
			array($line['clinical_variant_type_id'],
					$line['so_entry_id'],
					$line['so_id']);
		$TYPE_SO_ID=max($TYPE_SO_ID,$line['clinical_variant_type_id']);
	}


	addLog("Processing ".$TYPE_SO);
	/// TYPE_SO contains mapping between clinvar types and Sequence Ontology records
	$fp=fopen($TYPE_SO,'r');
	if (!$fp)																			failProcess($JOB_ID."A06",'Unable to open clinvar static file type_so_map.csv  at '.$TYPE_SO);
	
	/// Header
	$line=stream_get_line($fp,1000,"\n");
	$MAP_SO=array();
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n"); if ($line=='')continue;
		
		
		$tab=explode("\t",$line);
		//tab[0]:clinvar_type	
		//tab[1]:so_name	
		//tab[2]:so_id
		if (!isset($tab[1])){$tab[1]='';$tab[2]='';}
		/// If we didn't have that relation recorded in the database -> we add it
		if (!isset($STATIC_DATA['TYPE'][$tab[0]]))
		{
			++$TYPE_SO_ID;
			$SO_ENTRY_ID='NULL';
			/// We check that a sequence ontology record is provided in the file for that type
			if ($tab[2]!='')
			{
				/// Then we search for it
				$res=runQuery('SELECT so_entry_id FROM so_entry where so_id = \''.$tab[2].'\'');
				if ($res===false)														failProcess($JOB_ID."A07",'Unable to get so_entry_id');
				/// And if we found it, we assign the so_entry_id to the type
				if (count($res)==1)$SO_ENTRY_ID=$res[0]['so_entry_id'];
			}
			/// Then we add it to the static_data aND insert it in the database
			$STATIC_DATA['TYPE'][strtolower($tab[0])]=array($TYPE_SO_ID,$SO_ENTRY_ID,$tab[2]);
			$query='INSERT INTO clinical_variant_type VALUES ('.$TYPE_SO_ID.',\''.$tab[0].'\','.$SO_ENTRY_ID.')';
			if (!runQueryNoRes($query))													failProcess($JOB_ID."A08",'Unable to insert clinical variant type');
		}
	}
	fclose($fp);
		


/// There is a few static data that need to be loaded in memory to know the corresponding database Ids
addLog("Database pre-load data");
	
	$res=array();$query="SELECT clin_sign_id, clin_sign from clinical_significance";
	$res=runQuery($query);if ($res===false)												failProcess($JOB_ID."A09",'Unable to get clinical significance');
	foreach ($res as $tab) 
	{
		$STATIC_DATA['SIGN'][strtolower($tab['clin_sign'])]=$tab['clin_sign_id'];
	}
	$STATIC_DATA['SIGN']['-']=$STATIC_DATA['SIGN']['not provided'];

	

	$res=array();$query="SELECT clinvar_review_status_id,   clinvar_review_status_name 
						FROM clinical_variant_review_status";
	$res=runQuery($query);
	if ($res===false)																	failProcess($JOB_ID."A10",'Unable to get clinical review status');
	foreach ($res as $tab) 
	{
		$l_stat_name=strtolower($tab['clinvar_review_status_name']);
		$STATIC_DATA['REVIEW'][$l_stat_name]=$tab['clinvar_review_status_id'];
	}
	$STATIC_DATA['REVIEW']['-']=$STATIC_DATA['REVIEW']['no assertion criteria provided'];

} 

function processVariantsSummary()
{


	/// PROCESSING VARIANT SUMMARY FILE that contains clinvar records
	// Since there are about 3M records, we'll do that by chunkcs
	$fp=fopen('variant_summary.txt','r'); if (!$fp) 							failProcess($JOB_ID."B01",'Unable to open variant_summary.txt');
	$head=explode("\t",stream_get_line($fp,10000,"\n"));

	$BLOCK=array();
	$UPD_LIST=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,200000,"\n");if ($line=='')continue;
		$tmp=explode("\t",$line);
		$tab=array_combine($head,$tmp);/// We create an associative array to make it easier to access the data
		
		/// We only want GRCh38 records
		/// TODO: Should we do this based on genome_assembly?
		if ($tab['Assembly'] != 'GRCh38')continue;
		
		/// Then we store the record in the block
		$BLOCK[$tab['VariationID']]=$tab;
		if (count($BLOCK)<10000)continue;
		addLog("process Block");
		/// And we process the block
		processVariantSummary($BLOCK,$UPD_LIST,false);
		/// Then we push the data to the database
		pushToDB();
		
		$BLOCK=array();
	};
	fclose($fp);
	/// Process the last batch
	processVariantSummary($BLOCK,$UPD_LIST,true);
	 pushToDB();

}

function processInterpretation()
{
	global $JOB_ID;
	$fp=fopen('summary_of_conflicting_interpretations.txt','r');
	if (!$fp) 														failProcess($JOB_ID."C01",'Unable to open summary_of_conflicting_interpretations.txt');
	/// Reading the first line, breaking it by tabulation
	$head=explode("\t",stream_get_line($fp,100000,"\n"));

	/// File is to big in one batch, so we process it by block
	$BLOCK=array();
	$prev='';
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100000,"\n");
		if ($line=='')continue;
		$tmp=explode("\t",$line);
		/// Combine so that header are keys and values from tmp are values
		$tab=array_combine($head,$tmp);
		
		if (count($BLOCK)>1000 && $tab['ClinVar_Preferred']!=$prev)
		{
			processInterpretationBlock($BLOCK);
			$BLOCK=array();
		}
		$BLOCK["'".$tab['ClinVar_Preferred']."'"][$tab['Submitter1'].'_'.$tab['Submitter1_ClinSig']]=array('DESC'=>$tab['Submitter1_Description'],'STATUS'=>'FROM_DB');
		$BLOCK["'".$tab['ClinVar_Preferred']."'"][$tab['Submitter2'].'_'.$tab['Submitter2_ClinSig']]=array('DESC'=>$tab['Submitter2_Description'],'STATUS'=>'FROM_DB');
		$prev=		$tab['ClinVar_Preferred'];
	}
	fclose($fp);
	processInterpretationBlock($BLOCK);

}








function processInterpretationBlock(&$BLOCK)
{
	/// This will check if the comments associated to a clinical variant entry are the same as the one in the database
	$N=0;
	

	/// The keys of $BLOCK are the clinical variant names
	/// So we query the database to get the clinical variant entry id, the submitter, the clinical significance and the comments
	$res=runQuery("SELECT  clinical_variant_name, scv_id,  submitter,comments,clinvar_submission_id ,clin_sign
	FROM clinical_variant_entry cve, clinical_variant_submission cvs ,clinical_significance cs
	where cve.clinvar_entry_Id = cvs.clinvar_entry_Id 
	AND cs.clin_sign_id = cvs.clin_sign_id
	AND clinical_variant_name IN (".implode(',',array_keys($BLOCK)).')');
	if ($res===false)													failProcess($JOB_ID."D01",'Unable to get clinical variant entry');
	foreach ($res as $line)
	{
		$ENTRY=$BLOCK["'".$line['clinical_variant_name']."'"];
		$SUBM_SIGN=$line['submitter'].'_'.$line['clin_sign'];
		if (!isset($ENTRY[$SUBM_SIGN]))continue;
		if ($ENTRY[$SUBM_SIGN]['DESC']==$line['comments'])continue;
			
		++$N;
		
		$query = 'UPDATE clinical_variant_submission 
		SET comments = \''.str_replace("'","''",$ENTRY[$SUBM_SIGN]['DESC']).'\'
		WHERE clinvar_submission_id = '.$line['clinvar_submission_id'];
		
		if (!runQueryNoRes($query)) 									failProcess($JOB_ID."D02",'Unable to run query '.$query);
	
		
	}
	if ($N!=0)echo "Updated records: ".$N."\n";
}


function processCitationFile()
{
	global $JOB_ID;
	$fp=fopen('submission_summary.txt','r');
	if (!$fp) 												failProcess($JOB_ID."E01",'Unable to open submission_summary.txt');
	/// Submission summary starts with the explanations of the different columns, starting with #
	/// The header line is the last line that start with #
	
	/// So we are going to read the file until we find the last line starting with #
	/// And store the filepos
	$fpos=array();
	do
	{
		$fpos[]=ftell($fp);
		$line=stream_get_line($fp,1000000,"\n");
		if (substr($line,0,1)!='#')break;

	}while(!feof($fp));
	/// We go back to the last line starting with #
	fseek($fp,$fpos[count($fpos)-2]);
	/// And we read the header line
	$head=explode("\t",stream_get_line($fp,100000,"\n"));

	/// We then ready the file so we can map the VariationID to the SCV
	$MAP=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		$tmp=explode("\t",$line);
		$tab=array_combine($head,$tmp);

		$MAP[$tab['#VariationID']]=$tab['SCV'];
		
	};
	fclose($fp);
	
	/// We then open the citation file
	$fp=fopen('var_citations.txt','r');
	if (!$fp) failProcess($JOB_ID."E02",'Unable to open var_citations.txt');
	
	/// We read the header line
	$head=explode("\t",stream_get_line($fp,1000,"\n"));
	
	$STATS=array();
	$BLOCK=array();
	$TMP=array();
	$N_ALL=0;
	while(!feof($fp))
	{
		/// We read the file line by line
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		/// We split the line by tabulation
		$tmp=explode("\t",$line);

		/// We create an associative array to make it easier to access the data
		$tab=array_combine($head,$tmp);

		/// We only want PubMed records
		if ($tab['citation_source']!='PubMed')continue;
		/// We only want records with a VariationID and a citation_id
		if ($tab['VariationID']==''||$tab['citation_id']=='')continue;

		/// This shouldn't happen: we should have a mapping for each VariationID
		if (!isset($MAP[$tab['VariationID']])){echo "Cannot find ".$tab['VariationID']."\n";continue;}
		if (isset($TMP[$MAP[$tab['VariationID']]][$tab['citation_id']]))continue;

		$TMP[$MAP[$tab['VariationID']]][$tab['citation_id']]=true;
		$BLOCK[]=array(
			'SNP'=>$tab['rs'],
			'SCV'=>"'".$MAP[$tab['VariationID']]."'",
			'DBID'=>-1,
			'PMID'=>$tab['citation_id']);
		/// If we have less than 5000 records, we continue
		if (count($BLOCK)<5000)continue;
		/// Otherwise we process the block
		echo $N_ALL."=>".($N_ALL+5000)."\n";$N_ALL+=5000;
		processCitationBlock($BLOCK);
		/// And we push the data to the database
		pushToDB();
		/// We reset the block
		$BLOCK=array();
	};
	fclose($fp);
	/// We process the last block
	processCitationBlock($BLOCK);
	/// And we push the data to the database
	pushToDB();
}


function processCitationBlock(&$BLOCK)
{
	global $STATS;
	global $FILES;
	global $DBIDS;
	global $F_STATUS;

	/// We are going to process the block
	/// We are first going to get the database ids for the SCV, the SNP and the PMID
	$MAPS=array('SCV'=>array(),'SNP'=>array(),'PMID'=>array());
	$REV_S=array('SCV'=>array(),'SNP'=>array(),'PMID'=>array());

	/// So we list them as key in the different arrays and the vlaue will be -1
	/// Once we query the database, we will replace the -1 by the database id for those that we have found
	foreach ($BLOCK as &$INFO)
	{
		$MAPS['SCV'][$INFO['SCV']]=-1;
		if ($INFO['SNP']!='')$MAPS['SNP'][$INFO['SNP']]=-1;
		$MAPS['PMID'][$INFO['PMID']]=-1;
	}
	$STATS['CITATION_TOT_VAR_ENTRY']+=count($MAPS['SCV']);
	$STATS['CITATION_TOT_RSID']+=count($MAPS['SNP']);
	$STATS['CITATION_TOT_PMID']+=count($MAPS['PMID']);


	$time=microtime_float();
	if ($MAPS['SCV']!=array())
	{
		/// Query submission:
		$res=runQuery("SELECT clinvar_submission_id,scv_id 
						FROM clinical_variant_submission 
						where scv_id IN (".implode(',',array_keys($MAPS['SCV'])).')');
		if ($res===false )												failProcess($JOB_ID."F01",'Unable to get clinical_variant_submission');
		foreach ($res as $line)
		{
			$MAPS['SCV']["'".$line['scv_id']."'"]=$line['clinvar_submission_id'];
			$STATS['CITATION_SCV_ENTRY_EXIST']++;
		}
	}
	echo "TIME:\tSUBMISSION:".round(microtime_float()-$time,3);$time=microtime_float();
	
	/// Get variants:
	if ($MAPS['SNP']!=array()){
		$res=runQuery("SELECT variant_entry_id,rsid 
						FROM variant_entry 
						where rsid IN (".implode(',',array_keys($MAPS['SNP'])).')');
		if ($res===false)												failProcess($JOB_ID."F02",'Unable to get variant_entry');
		foreach ($res as $line)
		{
			$MAPS['SNP'][$line['rsid']]=$line['variant_entry_id'];
			$REV_S[$line['variant_entry_id']]=$line['rsid'];
			$STATS['CITATION_RSID_EXIST']++;
		}
	}
	echo "\tSNP:".round(microtime_float()-$time,3);$time=microtime_float();

	/// Get publications:
	if ($MAPS['PMID']!=array()){
		$res=runQuery("SELECT pmid,pmid_entry_id  
						FROM pmid_entry 
						where pmid IN (".implode(',',array_keys($MAPS['PMID'])).')');
		if ($res===false)											failProcess($JOB_ID."F03",'Unable to get pmid_entry');
		foreach ($res as $line)
		{
			$MAPS['PMID'][$line['pmid']]=$line['pmid_entry_id'];
			$REV_S[$line['pmid_entry_id']]=$line['pmid'];
			$STATS['CITATION_PMID_EXIST']++;
		}
	}
	echo "\tPMID:".round(microtime_float()-$time,3);$time=microtime_float();

	/// Then we get existing data based on the SCV and the PMID
	$query = 'SELECT clinvar_pmid_map_id  ,clinvar_submission_id,pmid_entry_id        	 
			FROM clinical_variant_pmid_map 
			where (clinvar_submission_id,pmid_entry_id ) IN (';
	$HAS=false;
	$MAP_POS=array();
	foreach ($BLOCK as $K=>&$INFO)
	{

		if ($MAPS['PMID'][$INFO['PMID']]==-1) continue;
		if ($MAPS['SCV'][$INFO['SCV']]==-1)continue;
		$MAP_POS[$MAPS['SCV'][$INFO['SCV']]][]=$K;
		$query.='('.$MAPS['SCV'][$INFO['SCV']].','.$MAPS['PMID'][$INFO['PMID']].'),';$HAS=true;
		
	}
	
	/// But we don't want to query the database if we don't have any pairs to query
	if ($HAS){
		$query=substr($query,0,-1).')';
		$res=runQuery($query);
		if ($res===false)											failProcess($JOB_ID."F04",'Unable to get clinical_variant_pmid_map');
		$STATS['CITATION_EXISTING_MAP']+=count($res);;
		
		foreach ($res as $line)
		{
			$VAR_DBID=$line['clinvar_submission_id'];
			foreach ($MAP_POS[$VAR_DBID] as &$K)
			{
				//echo $VAR_DBID."\t".$K."\t".$MAPS['ALL'][$BLOCK[$K]['VAR_ID']]."\t".$line['clinvar_entry_id']."\n";
			if ($MAPS['PMID'][$BLOCK[$K]['PMID']]==$line['pmid_entry_id'])$BLOCK[$K]['DBID']=$line['clinvar_pmid_map_id'];
			}
		}
	}
	echo "\tMAP:".round(microtime_float()-$time,3)."\n";$time=microtime_float();

	$MISSINGS=array('PMID'=>array(),'SCV'=>array());
	/// Then we review the block to find the new pairs
	foreach ($BLOCK as $K=>&$INFO)
	{
		if ($INFO['DBID']!=-1)continue;
		$STATS['CITATION_MISSING']++;
		/// We check that we have pmid_entry_id
		if ($MAPS['PMID'][$INFO['PMID']]==-1) 
		{
			$MISSINGS['PMID'][]=$INFO['PMID'];
			$STATS['CITATION_PMID_NOT_FOUND']++;
			continue;
		}
		/// We check that we have clinvar_submission_id
		if ($MAPS['SCV'][$INFO['SCV']]==-1)
		{
			$MISSINGS['SCV'][]=$INFO['SCV'];
			$STATS['CITATION_SCV_NOT_FOUND']++;
			continue;
		}
		/// Not found in the database, so we create a new entry
		$STATS['CITATION_MAP_NEW']++;
		$DBIDS['clinical_variant_pmid_map']++;
		$F_STATUS['clinical_variant_pmid_map']=true;
		fputs($FILES['clinical_variant_pmid_map'],
			$DBIDS['clinical_variant_pmid_map']."\t".$MAPS['SCV'][$INFO['SCV']]."\t".$MAPS['PMID'][$INFO['PMID']]."\n");
				
		
	}
	addLog("Missing pmid: ".implode("|",$MISSINGS['PMID']));
	addLog("Missing scv: ".implode("|",$MISSINGS['SCV']));

print_r($STATS);
}



function processVariantMapFile()
{

	/// We are going to process variant_summary to get the mapping between AlleleID and Variation ID
	$fp=fopen('variant_summary.txt','r'); 
	if (!$fp) 															failProcess($JOB_ID."G01",'Unable to open variant_summary.txt');
	$head=array_flip(explode("\t",stream_get_line($fp,10000,"\n")));
	
	$MAP_ALLELE=array();
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100000,"\n");if ($line=='')continue;
		$tmp=explode("\t",$line);
		$tab=array();$VALID=true;
		foreach($head as $K=>$V)
		{
			if (!isset($tmp[$V])){$VALID=false;break;}
			$tab[$K]=$tmp[$V];
		}
		if (!$VALID)continue;
		if ($tab['Assembly'] != 'GRCh38')continue;
		$MAP_ALLELE[$tab['#AlleleID']]=$tab['VariationID'];
	}
	fclose($fp);


	$fp=fopen('cross_references.txt','r');
	if (!$fp) 															failProcess($JOB_ID."G02",'Unable to open cross_references.txt');
	
	$head=array_flip(explode("\t",stream_get_line($fp,1000,"\n")));
	
	$STATS=array();
	$BLOCK=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		$tmp=explode("\t",$line);
		$tab=array_combine($head,$tmp);
		/// We only process dbSNP records
		if ($tab['Database']!='dbSNP')continue;
		/// But if we don't have a VariationID, we skip
		if (!isset($MAP_ALLELE[$tab['#AlleleID']]))continue;

		/// Create a record
		$BLOCK[]=array(
			'ALL_ID'=>$tab['#AlleleID'],
			'SNP'=>$tab['ID'],
			'VAR_ID'=>$MAP_ALLELE[$tab['#AlleleID']],
			'DBID'=>-1);

		if (count($BLOCK)<500)continue;
		/// Process the block
		processVariantMap($BLOCK,$MAP_ALLELE);
		/// And push the data to the database
		pushToDB();
		print_r($STATS);
		$BLOCK=array();
	};
	fclose($fp);
	/// Process the last block
	processVariantMap($BLOCK,$MAP_ALLELE);
	/// And push the data to the database
	pushToDB();
}








function processVariantMap(&$BLOCK,&$MAP_ALLELE)
{
	/// This process a list of records that are coming from the cross_references.txt file
	/// which associate a SNP to a VariationID and a AlleleID

	addLog("Process Variant Map:".count($BLOCK));
	global $STATS;
	global $FILES;
	global $F_STATUS;
	global $DBIDS;


	/// Listing all Variant_ID and SNP so we can get their database id
	$MAPS=array('ALL'=>array(),'SNP'=>array());
	foreach ($BLOCK as &$INFO)
	{
		$MAPS['ALL'][$INFO['VAR_ID']]=-1;
		$MAPS['SNP'][$INFO['SNP']]=-1;
	}
	$STATS['VAR_MAP_TOT_VAR_ENTRY']+=count($MAPS['ALL']);
	$STATS['VAR_MAP_TOT_RSID']+=count($MAPS['SNP']);


	/// If we have any variation_id, we query the database to get the clinvar_entry_id
	if ($MAPS['ALL']!=array()){
		$res=runQuery("SELECT clinvar_variation_id,clinvar_entry_id 
					FROM clinical_variant_entry 
					where clinvar_variation_id IN (".implode(',',array_keys($MAPS['ALL'])).')');
		if ($res === false)													failProcess($JOB_ID."H01",'Unable to get clinical_variant_entry');
		foreach ($res as $line)
		{
			$MAPS['ALL'][$line['clinvar_variation_id']]=$line['clinvar_entry_id'];
			$STATS['VAR_MAP_VAR_ENTRY_EXIST']++;
		}
	}


	/// If we have any rsid, we query the database to get the variant_entry_id
	if ($MAPS['SNP']!=array()){
		$res=runQuery("SELECT variant_entry_id,rsid 
					FROM variant_entry
					where rsid IN (".implode(',',array_keys($MAPS['SNP'])).')');
		if ($res===false)													failProcess($JOB_ID."H02",'Unable to get variant_entry');
		foreach ($res as $line)
		{
			$MAPS['SNP'][$line['rsid']]=$line['variant_entry_id'];
			$REV_S[$line['variant_entry_id']]=$line['rsid'];
			$STATS['VAR_MAP_RSID_EXIST']++;
		}
	}

	addLog("Search Map");
	/// Then we get existing data based on the SNP and the VariationID
	$query = 'SELECT * FROM clinical_variant_map where (variant_entry_id,clinvar_entry_id ) IN (';
	$HAS=false;
	$MAP_POS=array();
	foreach ($BLOCK as $K=>&$INFO)
	{

		if ($MAPS['SNP'][$INFO['SNP']]==-1) 
		{
			echo "Variant Map\tSNP\t".$INFO['SNP']."\tnot found\n";
			continue;
		}
		if ($MAPS['ALL'][$INFO['VAR_ID']]==-1)
		{
			echo "Variant Map\tClin\t".$INFO['VAR_ID']."\tnot found\n";
			continue;
		}
		$MAP_POS[$MAPS['SNP'][$INFO['SNP']]][]=$K;
		$query.='('.$MAPS['SNP'][$INFO['SNP']].','.$MAPS['ALL'][$INFO['VAR_ID']].'),';$HAS=true;

	}
	if ($HAS)
	{
		$query=substr($query,0,-1).')';
		
		$res=runQuery($query);$STATS['VAR_MAP_EXISTING_MAP']+=count($res);;
		if ($res===false)													failProcess($JOB_ID."H03",'Unable to get clinical_variant_map');
		/// looking for the corresponding records and assign the database id to the record
		foreach ($res as $line)
		{
			$VAR_DBID=$line['variant_entry_id'];
			foreach ($MAP_POS[$VAR_DBID] as &$K)
			{
				echo $VAR_DBID."\t".$K."\t".$MAPS['ALL'][$BLOCK[$K]['VAR_ID']]."\t".$line['clinvar_entry_id']."\n";

				if ($MAPS['ALL'][$BLOCK[$K]['VAR_ID']]==$line['clinvar_entry_id'])
					$BLOCK[$K]['DBID']=$line['clinical_variant_map_id'];
			}
		}
	}

	/// Then we review the block to find the new pairs
	foreach ($BLOCK as $K=>&$INFO)
	{
		/// A new pair is a pair that is not found in the database
		/// and has a DBID of -1
		if ($INFO['DBID']!=-1)continue;

		$STATS['VAR_MAP_MISSING']++;
		/// And we check that we have a database id for the SNP and the VariationID
		if ($MAPS['SNP'][$INFO['SNP']]==-1)
		{
			echo "Variant Map\tSNP\t".$INFO['SNP']."\tnot found\n";
			$STATS['VAR_MAP_SNP_NOT_FOUND']++;
			continue;
		}
		if ($MAPS['ALL'][$INFO['VAR_ID']]==-1)
		{
			echo "Variant Map\tClin\t".$INFO['VAR_ID']."\tnot found\n";
			$STATS['VAR_MAP_CLIN_NOT_FOUND']++;
			continue;
		}

		/// Add the new pair to the database
		$STATS['VAR_MAP_NEW']++;
		$DBIDS['clinical_variant_map']++;
		
		$F_STATUS['clinical_variant_map']=true;
		
		fputs($FILES['clinical_variant_map'],$DBIDS['clinical_variant_map']."\t".$MAPS['ALL'][$INFO['VAR_ID']]."\t".$MAPS['SNP'][$INFO['SNP']]."\n");	
	}

	print_r($STATS);
	
}


function processSubmissionFile()
{
	$fp=fopen('submission_summary.txt','r');
	if (!$fp) 												failProcess($JOB_ID."I01",'Unable to open submission_summary.txt');

	/// Submission summary starts with the explanations of the different columns, starting with #
	/// The header line is the last line that start with #
	
	/// So we are going to read the file until we find the last line starting with #
	/// And store the file position
	$fpos=array();
	do
	{
		$fpos[]=ftell($fp);
		$line=stream_get_line($fp,1000000,"\n");
		if (substr($line,0,1)!='#')break;

	}while(!feof($fp));
	/// We go back to the last line starting with #
	fseek($fp,$fpos[count($fpos)-2]);

	/// And we read the header line
	$head=explode("\t",stream_get_line($fp,100000,"\n"));
	
	$BLOCK=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		$tmp=explode("\t",$line);
		/// Combine so that header are keys and values from tmp are values
		$tab=array_combine($head,$tmp);
		
		/// Add it to the block
		$BLOCK[$tab['#VariationID']][$tab['SCV']]=$tab;

		/// If we have less than 10000 records, we continue
		if (count($BLOCK)<10000)continue;
		
		/// Otherwise we process the block
		processSubmissionSummary($BLOCK);
		/// And we push the data to the database
		pushToDB();
		
		$BLOCK=array();
	};
	fclose($fp);

	/// Process the last block
	processSubmissionSummary($BLOCK);

	/// And we push the data to the database
	pushToDB();
}















function processSubmissionSummary(&$BLOCK)
{
	##Overview of interpretation, phenotypes, observations, and methods reported in each current submission 
	##Explanation of the columns in this report
	#VariationID:                   the identifier assigned by ClinVar and used to build the URL, namely https://ncbi.nlm.nih.gov/clinvar/VariationID
	#ClinicalSignificance:          interpretation of the variation-condition relationship
	#DateLastEvaluated:             the last date the variation-condition relationship was evaluated by this submitter
	#Description:                   an optional free text description of the basis of the interpretation
	#SubmittedPhenotypeInfo:        the name(s) or identifier(s)  submitted for the condition that was interpreted relative to the variant
	#ReportedPhenotypeInfo:         the MedGen identifier/name combinations ClinVar uses to report the condition that was interpreted. 'na' means there is no public identifer in MedGen for the condition.
	#ReviewStatus:                  the level of review for this submission, namely http//www.ncbi.nlm.nih.gov/clinvar/docs/variation_report/#review_status
	#CollectionMethod:              the method by which the submitter obtained the information provided
	#OriginCounts:                  the reported origin and the number of observations for each origin
	#Submitter:                     the submitter of this record
	#SCV:                           the accession and current version assigned by ClinVar to the submitted interpretation of the variation-condition relationship
	#SubmittedGeneSymbol:           the gene symbol reported in this record
	//#VariationID	ClinicalSignificance	DateLastEvaluated	Description	SubmittedPhenotypeInfo						ReportedPhenotypeInfo							ReviewStatus								CollectionMethod	OriginCounts	Submitter							SCV				SubmittedGeneSymbol	ExplanationOfInterpretation
	//2				Pathogenic				Jun 29, 2010		-			SPASTIC PARAPLEGIA 48, AUTOSOMAL RECESSIVE		C3150901:Hereditary spastic paraplegia 48	no assertion criteria provided				literature only		germline:na		OMIM								SCV000020155.3	AP5Z1	-
	//2				Pathogenic				-					-			OMIM:613647										C3150901:Hereditary spastic paraplegia 48	criteria provided, single submitter			clinical testing	unknown:2		Paris Brain Institute,Inserm - ICM	SCV001451119.1	-						-
	//3				Pathogenic				Jun 29, 2010		-			SPASTIC PARAPLEGIA 48							C3150901:Hereditary spastic paraplegia 48	no assertion criteria provided				literature only		germline:na		OMIM								SCV000020156.5	AP5Z1	-

	global $UNK_DISEASE;
	global $STATIC_DATA; // Contains data from static database tables, such as clinical significance, review status etc.
	global $FILES;		/// File pointers in which new data will be added to be pushed to the database
	global $DBIDS;		//// Max prmary key for each table we want to insert into
	global $F_STATUS;
	$DATA=array();		// Submission records from the database

	$MAP=array();
	$MAP_SUB=array();
	/// Keys in BLOCK array are clinical_varation_id (VariantionID)
	/// So first we get the corresponding primary key, then we get the submission records and push it in data
	$time=microtime_float();
	$res=runQuery("SELECT clinvar_variation_id, clinvar_entry_id 
					FROM clinical_variant_entry 
					where clinvar_variation_id IN ( ".implode(',',array_keys($BLOCK)).')');
	if ($res===false )												failProcess($JOB_ID."J01",'Unable to get clinical_variant_entry');


	/// MAp array will be used to map a VariationID to its PK
	foreach ($res as $line)$MAP[$line['clinvar_variation_id']]=$line['clinvar_entry_id'];
	echo round(microtime_float()-$time,3)."\n";$time=microtime_float();

	if ($MAP!=array()){	/// If it's the first time, we don't need to get submission since there's none
		$res=runQuery("select cvs.* FROM clinical_variant_submission cvs where clinvar_entry_id IN (".implode(',',$MAP).')' );
		if ($res===false)											failProcess($JOB_ID."J02",'Unable to get clinical_variant_submission');
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['clinvar_entry_id']][$line['scv_id']]=$line;
			$MAP_SUB[$line['clinvar_submission_id']]=array($line['clinvar_entry_id'],$line['scv_id']);
		}
		echo "CVS:".round(microtime_float()-$time,3)."\n";$time=microtime_float();
	}

	/// Now $DATA is a bi dimensional array : Clinvar entry id then SCV number
	/// Submission records also provided the phenotype and the gene symbol
	/// We are now going to get them from the database
	///$MAP_SUB is helping us, given the PK for a submission record, where it is located in $DATA array.



	if ($MAP_SUB!=array())
	{
		$res=runQuery("SELECT * 
						FROM clinical_variant_disease_map cvd 
						where clinvar_submission_id IN (".implode(',',array_keys($MAP_SUB)).')');
		if ($res===false)										failProcess($JOB_ID."J03",'Unable to get clinical_variant_disease_map');
		foreach ($res as $line)
		{
			///Using MMAP_SUB to know the location in DATA array of the submission record
			$E=&$MAP_SUB[$line['clinvar_submission_id']];
			$DATA[$E[0]][$E[1]]['DISEASE']=array($line['disease_entry_id'],'FROM_DB');
		}
		echo "CVDM:".round(microtime_float()-$time,3)."\n";$time=microtime_float();
		$res=runQuery("SELECT * FROM clinical_variant_gn_map cvd 
						where clinvar_submission_id IN (".implode(',',array_keys($MAP_SUB)).')');
		if ($res===false)									failProcess($JOB_ID."J04",'Unable to get clinical_variant_gn_map');
		foreach ($res as $line)
		{
			///Using MMAP_SUB to know the location in DATA array of the submission record
			$E=$MAP_SUB[$line['clinvar_submission_id']];
			$DATA[$E[0]][$E[1]]['GENE']=array($line['gn_entry_id'],'FROM_DB');
		}
		echo "CVGM:".round(microtime_float()-$time,3)."\n";$time=microtime_float();
	}

	/// Now we list all diseases and symbols from the batch of submission and find their corresponding match in the database
	$SYMBOLS=array();$N_UNK_DISEASE=0;
	$DISEASE=array();$MAP_D=array();
	foreach ($BLOCK as &$LIST_FROM_F)
	{
		foreach ($LIST_FROM_F as &$INI_DATA)
		{
			if ($INI_DATA['SubmittedGeneSymbol']!='-')
			{
				$tab=explode(";",$INI_DATA['SubmittedGeneSymbol']);
				foreach ($tab as $T)
				{
					$SYMBOLS["'".strtolower($T)."'"]=-1;
					$INI_DATA['GENE'][]="'".strtolower($T)."'";
				}
			}
			if ($INI_DATA['ReportedPhenotypeInfo']!='-')
			{
				///C3150901:Hereditary spastic paraplegia 48
				/// We can have multiple phenotypes reported, separated by ;
				$tab=explode(";",$INI_DATA['ReportedPhenotypeInfo']);
				foreach ($tab as $T)
				{
					///Then we extract the ID from the name
					$t2=explode(":",$T);
					if ($t2[0]=='na')continue;
					if (isset($UNK_DISEASE["'".$t2[0]."'"])){$N_UNK_DISEASE++;continue;}
					$INI_DATA['DISEASE'][]="'".$t2[0]."'";
					
					$DISEASE["'".$t2[0]."'"]=-1;
					/// To avoid some case issue, we put everything lowercase
					if (isset($t2[1]))$MAP_D["'".str_replace("'","''",strtolower($t2[1]))."'"]="'".$t2[0]."'";
				}
			}
		}
	}
	addLog($N_UNK_DISEASE.' unknown diseases');


	if ($SYMBOLS!=array()){
		

		foreach ($SYMBOLS as $gn_symbol=>&$gn_id)
		{
			if (isset($STATIC_DATA['GENES']['SYMBOL'][strtolower($gn_symbol)]))
			{
			$gn_id=$STATIC_DATA['GENES']['SYMBOL'][strtolower($gn_symbol)];
			continue;
			}

			if (isset($STATIC_DATA['GENES']['SYN'][strtolower($gn_symbol)]))
			{
				if (count($STATIC_DATA['GENES']['SYN'][strtolower($gn_symbol)])>1)continue;
				$gn_id=$STATIC_DATA['GENES']['SYN'][strtolower($gn_symbol)][0];
				continue;
			}
			
			echo $gn_symbol."\n";

		}
		
	}
	
	if ($DISEASE !=array())
	{
		
		foreach ($DISEASE as $DS_NAME=>&$DS_ID)
		{
			$L_DS_NAME=strtolower($DS_NAME);
			
			foreach ($STATIC_DATA['DISEASE'] as $DS_TYPE=>&$DS_LIST)
			{
				
				if (!isset($DS_LIST[$L_DS_NAME]))continue;
				
				$DS_ID=$DS_LIST[$L_DS_NAME];
				break;
			}
		}
		
	}
	
	$N_DS_FOUND=0;
	foreach ($DISEASE as $DISEASE_NAME=>&$DISEASE_ID)
	{
		if ($DISEASE_ID==-1)
		{
			if (!isset($UNK_DISEASE[$DISEASE_NAME]))$UNK_DISEASE[$DISEASE_NAME]=1;
			else $UNK_DISEASE[$DISEASE_NAME]++;
			//addLog("WARNING\tDISEASE NOT FOUND\t".$DISEASE_NAME);
		}else $N_DS_FOUND++;
	}
	addLog(count($DISEASE)." total diseases");
	addLog($N_DS_FOUND." diseases found");
	addLog(count($UNK_DISEASE)." total disease unknown");


	foreach ($SYMBOLS as $SYMBOL=>&$GN_ENTRY_ID)
		if ($GN_ENTRY_ID==-1)addLog("WARNING\tGENE NOT FOUND\t".$SYMBOL);



	foreach ($BLOCK as $VARIANT_ID=>&$LIST_FROM_F)
	{
		if (!isset($MAP[$VARIANT_ID]))continue;
		if (!isset($DATA[$MAP[$VARIANT_ID]]))
		{
			foreach ($LIST_FROM_F as $INI_DATA)
			{
				addSubmission($INI_DATA,$MAP[$VARIANT_ID],$SYMBOLS,$DISEASE);
			}
		}
		else 
		{
			foreach ($LIST_FROM_F as $INI_DATA)
			{
				if (isset($DATA[$MAP[$VARIANT_ID]][$INI_DATA['SCV']]))
				{

				}
				else addSubmission($INI_DATA,$MAP[$VARIANT_ID],$SYMBOLS,$DISEASE);
			}
		}
	}
}

function addSubmission(&$INI_DATA,$MAP_ID,&$SYMBOLS,&$DISEASES)
{
	global $DBIDS;
	global $FILES;
	global $STATIC_DATA;
	global $F_STATUS;
	/// Some terms needs correction to be properly mapped to the database
	$CORRECTION=array('no classification for the single variant'=>'no classification for the individual variant');
	$CORRECTION_REV=array('Benign/Likely benign'=>'Likely benign','Pathogenic/Likely pathogenic'=>'Likely pathogenic');


	/// Applying correction

	if (isset($CORRECTION[$INI_DATA['ReviewStatus']]))		
	{	 
		
		$INI_DATA['ReviewStatus']=$CORRECTION[$INI_DATA['ReviewStatus']];
	}
	if (isset($CORRECTION_REV[$INI_DATA['ClinicalSignificance']]))$INI_DATA['ClinicalSignificance']=$CORRECTION_REV[$INI_DATA['ClinicalSignificance']];

	
	/// We expect the clinical significance and the review status to be in the static database
	if (!isset($STATIC_DATA['SIGN'][strtolower($INI_DATA['ClinicalSignificance'])])) 	failProcess($JOB_ID."K01",'Unknown clinical significance: '.$INI_DATA['ClinicalSignificance']);
	if (!isset($STATIC_DATA['REVIEW'][strtolower($INI_DATA['ReviewStatus'])])) 			failProcess($JOB_ID."K02",'Unknown review status: '.$INI_DATA['ReviewStatus']);
	
	/// Increase the primary key for the submission table
	$DBIDS['clinical_variant_submission']++;

	/// Get the primary key for the clinical significance and the review status
	$CLIN_SIGN_ID=$STATIC_DATA['SIGN'][strtolower($INI_DATA['ClinicalSignificance'])];
	$CLIN_REVIEW=$STATIC_DATA['REVIEW'][strtolower($INI_DATA['ReviewStatus'])];

	/// Convert the date:
	if ($INI_DATA['DateLastEvaluated']!='-')$date=date('Y-m-d',strtotime($INI_DATA['DateLastEvaluated']));
	else $date='NULL';

	/// Update $F_STATUS to know that we have new data to push to the database
	$F_STATUS['clinical_variant_submission']=true;
	
	/// Add the new record to the file
	fputs($FILES['clinical_variant_submission'],
		$DBIDS['clinical_variant_submission']."\t".
		$MAP_ID."\t".
		$CLIN_SIGN_ID."\t".
		$CLIN_REVIEW."\t".
		$INI_DATA['SCV']."\t".
		$INI_DATA['CollectionMethod']."\t".
		$INI_DATA['Submitter']."\t\"".
		$INI_DATA['ExplanationOfInterpretation']."\"\t".
		$date."\n");

	/// If we have a gene symbol, we add it to the gene map file
	if (isset($INI_DATA['GENE']))
	foreach ($INI_DATA['GENE'] as $SYMBOL)
	{
		if ($SYMBOLS[$SYMBOL]==-1)continue;
		$DBIDS['clinical_variant_gn_map']++;
		$F_STATUS['clinical_variant_gn_map']=true;
		fputs($FILES['clinical_variant_gn_map'],
		$DBIDS['clinical_variant_gn_map']."\t".
		$DBIDS['clinical_variant_submission']."\t".
		$SYMBOLS[$SYMBOL]."\n");
	}

	/// If we have a disease, we add it to the disease map file
	if (isset($INI_DATA['DISEASE']))
	{
		$MAPD=array();
		foreach ($INI_DATA['DISEASE'] as $DISEASE)
		{
			if ($DISEASES[$DISEASE]==-1)continue;
			$MAPD[$DISEASES[$DISEASE]]=true;
		}
		foreach ($MAPD as $D=>$V)
		{
			// 				'clinical_variant_disease_map'=>'(clinvar_disease_map_id,clinvar_submission_id,disease_entry_id)',   
			// 'clinical_variant_gn_map'=>'(clinvar_gn_map_id,clinvar_submission_id,gn_entry_id)',
			$DBIDS['clinical_variant_disease_map']++;
			$F_STATUS['clinical_variant_disease_map']=true;
			fputs($FILES['clinical_variant_disease_map'],
			$DBIDS['clinical_variant_disease_map']."\t".
			$DBIDS['clinical_variant_submission']."\t".
			$D."\n");
		}
	}
}


function pushToDB()
{
	addLog("Push To DB");
	global $FILES;
	global $CTL_FILE;
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	global $COL_ORDER;
	global $F_STATUS;

	foreach ($COL_ORDER as $NAME=>$CTL)
	{
		/// If we don't have any new data for this table, we skip
		if (!$F_STATUS[$NAME])continue;
		echo $NAME."\n";
		$res=array();

		/// We close the file
		fclose($FILES[$NAME]);

		/// Create the copy command
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \'INSERT/'.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		
		/// Run the command
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )	failProcess($JOB_ID."L01","ERROR:".'Unable to insert '.$NAME."\n");
			
		/// Reopen the file
		$FILES[$NAME]=fopen('INSERT/'.$NAME.'.csv','w');
		if (!$FILES[$NAME])														failProcess($JOB_ID."L02",'Unable to open '.$NAME.'.csv');
	}

	/// Reset the status
	foreach ($COL_ORDER as $NAME=>$CTL) $F_STATUS[$NAME]=false;
}


function processVariantSummary(&$BLOCK,&$UPD_LIST,$LAST_BATCH)
{
	global $F_STATUS;
	global $STATIC_DATA;
	global $FILES;
	global $DBIDS;
	$DATA=array();
	$time=microtime_float();
	$res=runQuery("SELECT * 
		FROM clinical_variant_entry 
		where clinvar_variation_id IN (".implode(',',array_keys($BLOCK)).')');
	if ($res===false)									failProcess($JOB_ID."M01",'Unable to get clinical_variant_entry');
	

	$CORRECTION=array('no classification for the single variant'=>'no classification for the individual variant');
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['clinvar_variation_id']]=$line;

	}

	foreach ($BLOCK as $ID=>&$INI_DATA)
	{
		/// Applying correction
		if (isset($CORRECTION[$INI_DATA['ReviewStatus']]))$INI_DATA['ReviewStatus']=$CORRECTION[$INI_DATA['ReviewStatus']];
		/// If data exist, compare and update
		if (isset($DATA[$ID]))
		{
			$DB_DATA=&$DATA[$ID];
			$DB_DATA['DB_STATUS']='VALID';

			$query='';
			if ($DB_DATA['clinical_variant_type_id']!=$STATIC_DATA['TYPE'][strtolower($INI_DATA['Type'])][0])
			{
				//$query.= 'clinical_variant_type_id='.$STATIC_DATA['TYPE'][strtolower($INI_DATA['Type'])][0].',';
				$UPD_LIST['Type_id'][$STATIC_DATA['TYPE'][strtolower($INI_DATA['Type'])][0]][]=$DB_DATA['clinvar_variation_id'];
			}
			if ($DB_DATA['clinical_variant_review_status']!=$STATIC_DATA['REVIEW'][strtolower($INI_DATA['ReviewStatus'])])
			{
				//$query.=' clinical_variant_review_status='.$STATIC_DATA['REVIEW'][strtolower($INI_DATA['ReviewStatus'])].',';
				$UPD_LIST['review_status'][$STATIC_DATA['REVIEW'][strtolower($INI_DATA['ReviewStatus'])]][]=$DB_DATA['clinvar_variation_id'];
			}
				
			if ($DB_DATA['n_submitters']!=$INI_DATA['NumberSubmitters'])
			{
				//$query.=' n_submitters='.$INI_DATA['NumberSubmitters'].',';
				$UPD_LIST['Submitter'][$INI_DATA['NumberSubmitters']][]=$DB_DATA['clinvar_variation_id'];
			}
			if ($DB_DATA['clinical_variant_name']!=$INI_DATA['Name'])$query.='clinical_variant_name=\''.$INI_DATA['Name'].'\',';
			if ($INI_DATA['LastEvaluated']!='-')$date="'".date('Y-m-d',strtotime($INI_DATA['LastEvaluated']))."'";
				else $date='';
				
			if ("'".$DB_DATA['last_submitted_date']."'"!=$date && $DB_DATA['last_submitted_date']!=$date)
			{
				//echo "'".$DB_DATA['last_submitted_date']."'>>".$date."\n";

				if ($date=='')$date='NULL';
				$UPD_LIST['Last_date'][$date][]=$DB_DATA['clinvar_variation_id'];
				//$query.='last_submitted_date='.$date.',' ;
			}
			if ($query!='')
			{
				if (!runQueryNoRes('UPDATE clinical_variant_entry 
				SET '.substr($query,0,-1).' 
				WHERE clinvar_variation_id = '.$DB_DATA['clinvar_variation_id']))failProcess($JOB_ID."M02",'Unable to update '.$DB_DATA['clinvar_variation_id']);
			}

		}
		/// If not, we create a new entry
		else{
			/// We increase the primary key
			++$DBIDS['clinical_variant_entry'];
			$DATA[$ID]['DB_STATUS']='TO_INS';

			/// Convert the date
			if ($INI_DATA['LastEvaluated']!='-')$date=date('Y-m-d',strtotime($INI_DATA['LastEvaluated']));
			else $date='NULL';

			/// Update the status
			$F_STATUS['clinical_variant_entry']=true;
			
			fputs($FILES['clinical_variant_entry'],
				$DBIDS['clinical_variant_entry']."\t".
				$INI_DATA['VariationID']."\t".
				$STATIC_DATA['TYPE'][strtolower($INI_DATA['Type'])][0]."\t".
				$STATIC_DATA['REVIEW'][strtolower($INI_DATA['ReviewStatus'])]."\t".
				$INI_DATA['NumberSubmitters']."\t".
				$INI_DATA['Name']."\t".
				$date."\n");
		}
	}

	foreach ($UPD_LIST as $type=>&$list)
	{
		foreach ($list as $val=>&$list_item)
		{
			if ((count($list_item)>200 && !$LAST_BATCH)||$LAST_BATCH)
			{
				$query='UPDATE clinical_variant_entry SET ';
				switch ($type)
				{
					case 'Type_id':$query.=' clinical_variant_type_id=';break;
					case 'review_status':$query.=' clinical_variant_review_status=';break;
					case 'Submitter':$query.=' n_submitters=';break;
					case 'Last_date':$query.=' last_submitted_date=';break;
				}
				$query.= $val.' WHERE clinvar_variation_id IN ('.implode(',',$list_item).')';
				echo $query."\n";
				if (!runQueryNoRes($query))failProcess($JOB_ID."M03",'Unable to update '.$type);
				unset($UPD_LIST[$type][$val]);
			}
		}
	}

}


?>
