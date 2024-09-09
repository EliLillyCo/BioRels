<?php
/*
 SCRIPT NAME: process_dbsnp
 PURPOSE:     Processing dbsnp file
 
*/
error_reporting(E_ALL);
ini_set('memory_limit','1200M');

/// Job name - Do not change
$JOB_NAME='process_dbsnp';

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


$CHR_FILE=$argv[1];

addLog("Setting up");

	/// Get parent information:
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_dbsnp')];


	/// Setting up directory path:
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; 	if (!is_dir($U_DIR)) 								failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/DBSNP/';   		if (!is_dir($U_DIR))				 				failProcess($JOB_ID."002",'Unable to find  '.$U_DIR);
	$U_DIR.='/'.$CK_INFO['TIME']['DEV_DIR'].'/';   	if (!is_dir($U_DIR))				 				failProcess($JOB_ID."003",'Unable to find '.$U_DIR);
	if (!chdir($U_DIR))				 																	failProcess($JOB_ID."004",'Unable to access '.$U_DIR);


	/// Get job info:
	$TASK_ID=$argv[1];
	$TASK_INFO=$argv[2];
	echo $TASK_ID."\t".$TASK_INFO."\n";

	/// Specify the chromosome(s) and the range of records to process within those chromosome
	$TASK_SNP=array();
	$tmp=explode("_",$TASK_INFO);
	foreach ($tmp as $t)
	{
		if ($t=='')continue;
		$tmp2=explode("-",$t);
		$TASK_SNP[$tmp2[0]]=array($tmp2[1],$tmp2[2],-1);
	}

	
addLog("Get primary key values:");
///	Those are all the tables we are going to insert into. 
//// DBIDS map each table to their highest primary key value
	$DBIDS=array('variant_allele'=>-1,
		'variant_entry'=>-1,
		'variant_position'=>-1,
		'variant_change'=>-1,
		'variant_frequency'=>-1,
		'variant_pmid_map'=>-1,
		'variant_transcript_map'=>-1,
		'variant_prot_allele'=>-1,
		'variant_protein_map'=>-1);
	
	/// Getting max primary key
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$res=array();
		$TBL_N=$TBL;
		if ($TBL=='variant_transcript_map')$TBL_N='variant_transcript';
		if ($TBL=='variant_protein_map')$TBL_N='variant_protein';
		$res=runQuery('SELECT MAX('.$TBL_N.'_id) co FROM '.$TBL);
		if ($res===false)																failProcess($JOB_ID."005",'Unable to get Max ID for '.$TBL);

		$DBIDS[$TBL]=($res[0]['co']=='')?0:$res[0]['co'];
	}

addLog("Get Variant types:");
	$res=runQuery("SELECT * FROM variant_type");if ($res===false)						failProcess($JOB_ID."006",'Unable to get the variant types');
	$TYPES=array();
	foreach ($res as $line)$TYPES[$line['variant_type_id']]=$line['variant_name'];
		


///	Those are all the tables we are going to insert into.  with the corresponding columns
	$COL_ORDER=array('variant_allele'=>'(variant_allele_id,variant_seq)',
			'variant_entry'			=>'(variant_entry_id,rsid,date_created,date_updated)',
			'variant_position'		=>'(variant_position_id,variant_entry_id,ref_all,chr_seq_pos_id )',	
			'variant_change'		=>'(variant_change_id  ,variant_position_id,alt_all,variant_type_id)',
			'variant_frequency'		=>'(variant_frequency_id,variant_change_id ,variant_freq_study_id,ref_count,alt_count)',
			'variant_pmid_map'		=>'(variant_pmid_map_id,variant_entry_id   ,pmid_entry_id)',
			'variant_transcript_map'=>'(variant_transcript_id,variant_change_id,transcript_id  ,transcript_pos_id ,  so_entry_id,tr_ref_all,tr_alt_all)',
			'variant_prot_allele'	=>'(variant_prot_allele_id,variant_prot_seq)',
			'variant_protein_map'	=>'(variant_protein_id,variant_transcript_id,prot_seq_id,prot_Seq_pos_id,so_entry_id,prot_ref_all,prot_alt_all)'
		);




addLog("Opening output files:");	
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$FILES[$TBL]=fopen($TBL.'.csv','w');
		if (!$FILES[$TBL])															failProcess($JOB_ID."007",'Unable to open '.$TBL.'.csv');
	}


	/// This is used to speed up initial insertions
	$res=runQuery("SELECT * FROM variant_protein_map LIMIT 1");
	if($res===false)																					failProcess($JOB_ID."008",'Unable to get variant_protein_map information');
	$HAS_TRANSCRIPT_MAP_DATA=(count($res)==1);

	
addLog("Update source");
	$SOURCE_ID=getSource('dbSNP');



addLog("Getting all studies:");	
	///Getting all the studies
	$res=runQuery("SELECT * FROM variant_freq_study where source_id=".$SOURCE_ID); 
	if ($res===false)																					failProcess($JOB_ID."009",'Unable to get variant frequency studies');
	$DB_STUDIES=array();
	foreach ($res as $line)
	{
		$DB_STUDIES[$line['variant_freq_study_name']]=$line['variant_freq_study_id'];
	}
		
	/// Get RefSeq names primary keys for human	
	$res=runQuery("SELECT REFSEQ_NAME,REFSEQ_VERSION,CHR_SEQ_ID 
		FROM CHR_SEQ CS, CHROMOSOME C, TAXON T
		WHERE CS.CHR_ID = C.CHR_ID 
		AND C.TAXON_ID = T.TAXON_ID 
		AND TAX_ID='9606'");
	if ($res===false)																					failProcess($JOB_ID."010",'Unable to get chromosome sequences');
	$CHR_SEQ_IDS=array();
	foreach ($res as $line)
	{
		$CHR_SEQ_IDS[$line['refseq_name'].'.'.$line['refseq_version']]=$line['chr_seq_id'];
	}
			
	/// Getting the transcript names	
	$res=runQuery("SELECT TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID, CS.CHR_SEQ_ID , STRAND
					FROM TRANSCRIPT T, GENE_SEQ GS, CHR_SEQ CS, CHROMOSOME C, TAXON TT 
					WHERE T.GENE_SEQ_ID = GS.GENE_SEQ_ID 
					AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID 
					AND CS.CHR_ID = C.CHR_ID AND C.TAXON_ID = TT.TAXON_ID 
					AND TAX_ID='9606'");
	if ($res===false)																					failProcess($JOB_ID."011",'Unable to get transcripts');
	$TRANSCRIPTS=array();
	$TRANSCRIPT_IDS=array();
	foreach ($res as $line)
	{
		if ($line['transcript_version']!='')
		{
			$pos=strpos($line['transcript_version'],'-');
			if ($pos!==false)$line['transcript_version']=substr($line['transcript_version'],0,$pos);
		}
		$TRANSCRIPTS[$line['transcript_name'].(($line['transcript_version']!='')?'.'.$line['transcript_version']:'')][$line['chr_seq_id']]=array($line['transcript_id'],$line['strand']);
		$TRANSCRIPT_IDS[$line['transcript_id']]=array($line['transcript_name'],$line['transcript_version']);
	}





	$SO_ENTRIES=array();
	$SO_IDS=array();
	$res=runQuery("SELECT SO_ENTRY_ID, SO_ID FROM SO_ENTRY");
	if ($res===false)																					failProcess($JOB_ID."012",'Unable to get Sequence ontology');
	foreach ($res as $line)
	{
		$SO_ENTRIES[$line['so_entry_id']]=$line['so_id'];
		$SO_IDS[$line['so_id']]=$line['so_entry_id'];
	}






	$UNI_MAPPING=array();
	$fp=fopen('uniprot_mapping.csv','r');
	if (!$fp)																					failProcess($JOB_ID."013",'Unable to open uniprot_mapping');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);if (!isset($tab[2]))continue;
		$UNI_MAPPING[$tab[2]]=$tab[1];
	}
	fclose($fp);

	/// All errors will be saved here:
	$FILE_ISSUE=fopen('SNP_ISSUE_'.$TASK_ID.'.csv','w');if (!$FILE_ISSUE)								failProcess($JOB_ID."014",'Unable to open error file');



	///statistics
	$STATS=array(
			'ENTRY_PROCESSED'=>0,/// json successfully parsed-> to be processed
			'ENTRY_JSON_ISSUE'=>0,/// Failed to parse json
			'TEST_ENTRY'=>0,
			'EXISTING_ENTRY'=>0,
			'NEW_VARIANT_ENTRY'=>0,
			'UPDATE_CREATION_DATE'=>0,
			'UPDATE_UPDATE_DATE'=>0,
			'CHANGE_TEST'=>0,
			'EXISTING_CHANGE'=>0,
			'NEW_VARIANT_CHANGE'=>0,
			'DEL_CHANGE'=>0,
			'PMIDS'=>0,
			'EXISTING_PMID'=>0,
			'PMID_NOT_IN_DB'=>0,
			'DEL_PMID'=>0,
			'NEW_VARIANT_PMID'=>0,
			'ALLELE_TEST'=>0,
			'EXISTING_ALLELE'=>0,
			'DEL_ALLELE'=>0,
			'ALLELE_NO_DNA'=>0,
			'ALLELE_DIFF_FROM_DNA'=>0,
			'NEW_VARIANT_ALLELE'=>0,
			'NEW_VARIANT_POSITION'=>0,
			'MISSING_ALLELE_NAME'=>0,
			'TEST_FREQUENCY'=>0,
			'EXISTING_FREQUENCY'=>0,
			'TEST_TRANSCRIPT'=>0,
			'EXISTING_TRANSCRIPT'=>0,
			'NEW_TRANSCRIPT'=>0,
			'UNKNOWN_TRANSCRIPT'=>0,
			'MISMATCH_TRANSCRIPT_POSITION_DNA'=>0,
			'UNKNOWN_PROT_SO'=>0,
			'PROT_SEQ'=>0,
			'PROT_SEQ_VALID'=>0,
			'PROT_SEQ_NEW'=>0,
			'PROT_SEQ_NOT_IN_DB'=>0,
			'PROT_SEQ_POS_NOT_FOUND'=>0,
			'PROT_SEQ_POS_NOT_IN_DB'=>0,
			'PROT_SEQ_DIFF_AA'=>0
			
		);





















	
	$fpRESULTS=fopen('DATA/RESULTS_'.$TASK_ID,'w');if (!$fpRESULTS)										failProcess($JOB_ID."015",'Unable to open results file');

	/// Each task is now processed, a task being a chromosome and a range of records
	foreach ($TASK_SNP as $CHR=>&$RANGE)
	{	
		addLog("################################################\n");
		addLog("################################################\n");
		addLog("################################################\n");
		addLog("########### OPENING '.$CHR.' ###################\n");
		addLog("################################################\n");
		addLog("################################################\n");
		addLog("################################################\n");
		/// Opening the chromosome dbsnp file
		$fp=fopen('refsnp-chr'.$CHR.'.json','r');if (!$fp)				 								failProcess($JOB_ID."016",'Unable to open json file');
		$RECORDS=array();
		$time=microtime_float();$SUM_LEN=0;$N_P=0;$MAX_LEN=0;
		//fseek($fp,498467866145);
		/// Reading file
		while(!feof($fp))
		{
			/// a record is in one line, a VERY LONG line:
			$line=stream_get_line($fp,10000000,"\n");
			
			/// This helps us to evaluate the longuest length:
			$SUM_LEN+=strlen($line);
			$N_P+=1;
			$MAX_LEN=max($MAX_LEN,strlen($line));
			/// Line empty? let's keep going
			if ($line=='')continue;

			/// $RANGE[0]=> first record to process in that file.
			/// $RANGE[1]=> Last record to process
			/// $RANGE[2]=> Current record
			$RANGE[2]++;
			/// We print some stats every 10K records
			if($RANGE[2]%10000==0)
			{
				echo "STATUS\t".$CHR."\t".$RANGE[0]."\t".$RANGE[2]."\t".$RANGE[1]."\t".round(microtime_float()-$time,3)."\t".round($SUM_LEN/$N_P,2)."\t".$MAX_LEN."\n";
				$time=microtime_float();
				/// This is a dummy query to ensure that the connection still exist
				$res=runQuery("select * FROM taxon LIMIT 1");
			
			}
			/// Below range continue. Above range stop
			if ($RANGE[2]<$RANGE[0])continue;
			if ($RANGE[2]>$RANGE[1])break;

			/// Decode the json string
			$arr=json_decode($line,true);
			/// Failed, we continue. Most of th etime, it will be because the json string is longer than what we anticipated.
			if ($arr==null)
			{
				$STATS['ENTRY_JSON_ISSUE']++;
				continue;
			}
			$STATS['ENTRY_PROCESSED']++;
			if ($STATS['ENTRY_PROCESSED']%500==0)echo $STATS['ENTRY_PROCESSED']."\n";
			

			/// Process the record and convert it into an array
			$ENTRY=processEntry($arr);
			
			/// Push the record into the RECORDS array 
			$RECORDS[$ENTRY['ENTRY']['rsid']]=$ENTRY;
			if (count($RECORDS)<2000)continue;
			processAllRecords($RECORDS);
			echo "FILE POS :".ftell($fp)."\n";
		//	exit;	

		//	print_r($ENTRY);

		}
		fclose($fp);
		addLog("################################################\n");
		addLog("################################################\n");
		addLog("################################################\n");
		addLog("########### CLOSING ".$CHR." ###################\n");
		addLog("################################################\n");
		addLog("################################################\n");
		addLog("################################################\n");
	}
	addLog("Last batch");
	processAllRecords($RECORDS);
	addLog("################################################\n");
	addLog("################################################\n");
	addLog("################################################\n");
	addLog("########### ALL DONE ###################\n");
	addLog("################################################\n");
	addLog("################################################\n");
	addLog("################################################\n");














function processAllRecords(&$RECORDS)
{
	global $JOB_ID;
	global $TASK_ID;
	global $STATS;
	global $fpRESULTS;
			
	$RECORDS=processRecord($RECORDS);
			
	/// and save them in the output file as json
	foreach ($RECORDS as &$R) fputs($fpRESULTS,json_encode($R)."\n");
				 
	/// Cleaning
	$RECORDS=null;
	$RECORDS=array();
	
	/// Cleaning up memory for optimal use
	gc_collect_cycles();
	 
	/// Update statistics:
	$fpSTATS=fopen('STATS_'.$TASK_ID,'w'); if ($fpSTATS===false)										failProcess($JOB_ID."A02",'Unable to open STATS file');
	fputs($fpSTATS,json_encode($STATS)."\n");
	fclose($fpSTATS);
	print_r($STATS);
			
}





function preloadData(&$SEARCH,&$RECORDS,&$DATA)
{
	global $TRANSCRIPTS;
	global $TRANSCRIPT_IDS;
	global $CHR_SEQ_IDS;
	global $UNI_MAPPING;
	global $SO_IDS;
	global $JOB_ID;
	$time=microtime_float();

	/// Getting all the variant types
	$SEARCH=array('POSITION'=>array(),'TYPES'=>array());
	
	$res=runQuery("SELECT * FROM variant_type");if ($res===false)										failProcess($JOB_ID."B01",'Unable to get the variant types');

	foreach ($res as $line)
	{
		$SEARCH['TYPES'][$line['variant_name']]=$line['variant_type_id'];
	}
	echo "TIME\tVARIANT_TYPE\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();


	/// Pushing records in DATA array
	foreach ($RECORDS as &$r)
	{
		//print_r($r);
		$DATA[$r['ENTRY']['rsid']]=array();
		if (isset($r['allele']))
		foreach ($r['allele'] as &$alls)
		foreach ($alls as &$all)
		{

			/// Adding the position to the search
			$SEARCH['POSITION'][$all['seq_id']][$all['position']]=array(-1,'');
			if (!isset($CHR_SEQ_IDS[$all['seq_id']]))
			{
				continue;	
			}
			
			$CHR_SEQ_ID=$CHR_SEQ_IDS[$all['seq_id']];
			/// Adding the study to the search
			foreach ($all['frequencies'] as &$f)	
			{
				$SEARCH['STUDY'][$f['STUDY']]=-1;
			}

			foreach ($all['transcripts'] as $T)
			{

				$TR_ID=-1;$STRAND='+';
				/// The transcript should be in the list of transcripts so we get the db id
				if (isset($TRANSCRIPTS[$T['seq_id']]))
				{
					if (isset($TRANSCRIPTS[$T['seq_id']][$CHR_SEQ_ID]))
					{
						$TR_ID=$TRANSCRIPTS[$T['seq_id']][$CHR_SEQ_ID][0];
						$STRAND=$TRANSCRIPTS[$T['seq_id']][$CHR_SEQ_ID][1];
					}
					//else echo "MISSING TRANSCRIPT ".$T['seq_id'].' in '.$all['seq_id']."\n";
				}
				
				//	echo $T['seq_id']."\t".$STRAND."\t".strlen($T['deleted'])."\n";
				if ($STRAND=='-' && strlen($T['deleted'])>1)$T['position']+=strlen($T['deleted'])-1;
				
				
				/// Then we associate that transcript dbid to the position of the variant
				/// Already existing record? We just add the position:
				if (isset($SEARCH['TRANSCRIPT'][$CHR_SEQ_ID][$T['seq_id']]))$SEARCH['TRANSCRIPT'][$CHR_SEQ_ID][$T['seq_id']]['POS'][$T['position']]=-1;
				/// Otherwise we create the record
				else 														$SEARCH['TRANSCRIPT'][$CHR_SEQ_ID][$T['seq_id']]=array('ID'=>$TR_ID,'POS'=>array($T['position']=>-1));

				/// Adding sequence ontology id to the search
				if ($T['seq_onto_id']!='')
				{
					$SEARCH['SO'][$T['seq_onto_id']]=-1;
				}

				/// Adding the protein info to the search
				if ($T['prot_info']!=array())
				{
					$SEARCH['SO'][$T['prot_info']['PROT_SO_ID']]=-1;
					/// The protein sequence is not in the SEARCH list yet -> we create the record
					if (!isset($SEARCH['PROT'][$T['prot_info']['PROT_SEQ']]))$SEARCH['PROT'][$T['prot_info']['PROT_SEQ']]=array('ID'=>-1,'POS'=>array($T['prot_info']['PROT_POS']=>array(-1,'')));
					/// Otherwise we just add the position
					else 													 $SEARCH['PROT'][$T['prot_info']['PROT_SEQ']]['POS'][$T['prot_info']['PROT_POS']]=array(-1,'');
				}
			}
		}
		/// Adding the pmid to the search
		if (isset($r['PMID']))
		{
			foreach ($r['PMID'] as $pmid)
			{
				$SEARCH['PMID'][$pmid]=-1;
			}
		}
	}
	
	
	/// The searching - first for publications
	if (isset($SEARCH['PMID']))
	{
		echo "\t####### SEARCH ".count($SEARCH['PMID'])." PMID\n";
		$query="SELECT PMID,pmid_entry_id 
				FROM pmid_entry 
				where pmid IN (".implode(',',array_keys($SEARCH['PMID'])).')';
		$res=runQuery($query);
		if ($res===false)															failProcess($JOB_ID."B02",'Unable to get publications');
		
		foreach ($res as $line)
		{
			$SEARCH['PMID'][$line['pmid']]=$line['pmid_entry_id'];
		}
		
	}
	echo "TIME\tPMID\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();





	///Then for DNA position
	if (count($SEARCH['POSITION'])!=0)
	foreach ($SEARCH['POSITION'] as $CHR_SEQ_NAME=>&$LIST)
	{
		$tab=explode(".",$CHR_SEQ_NAME);
		echo "\t####### SEARCH ".count($LIST)." positions in ".$CHR_SEQ_NAME."\n";
		$res=runQuery("SELECT chr_seq_pos_id, chr_pos ,nucl, cs.chr_seq_id
		FROM chr_seq cs, chr_seq_pos csp 
		WHERE cs.chr_seq_id = csp.chr_seq_id 
		AND cs.refseq_name='".$tab[0]."' 
		AND refseq_version='".$tab[1]."' 
		AND chr_pos IN (".implode(',',array_keys($LIST)).")");


		if ($res===false)																failProcess($JOB_ID."B03",'Unable to get chromosomal positions');
		
		foreach ($res as $line)
		foreach ($LIST as $POS=>&$INFO_POS)
		{
			if ($POS!=$line['chr_pos'])continue;
			$INFO_POS[0]=$line['chr_seq_pos_id'];
			$INFO_POS[1]=$line['nucl'];
			$INFO_POS[2]=$line['chr_seq_id'];
		}

	}
	echo "TIME\tCHR_SEQ\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();
	
	
	
	/// Then for gene
	if (isset($SEARCH['GENE']))
	{
		echo "\t####### SEARCH ".count($SEARCH['GENE'])." genes\n";
		$res=runQuery("SELECT gene_id,gn_entry_id 
					FROM gn_entry 
					WHERE gene_id IN (".implode(",",array_keys($SEARCH['GENE'])).')');

		if ($res===false)																failProcess($JOB_ID."B04",'Unable to get gene');

		foreach ($res as $line)
		{
			$SEARCH['GENE'][$line['gene_id']]=$line['gn_entry_id'];
		}
	}
	echo "TIME\tGENE\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();
	



	/// Here we are going to search for transcript and transcript positions:
	if (isset($SEARCH['TRANSCRIPT']))
	{
		echo "\t####### SEARCH ".count($SEARCH['TRANSCRIPT'])." transcripts\n";
		$start=microtime_float();
		
		
		
		$HAS_V=false;
		/// So we build queries based on pairs transcript_id/transcript_position to get transcript_pos_id and chr_seq_pos_id (chromosomal position id)
		$LIST_ALL=array();
		foreach ($SEARCH['TRANSCRIPT'] as $CHR_SEQ_ID=>&$LIST_TR)
		foreach ($LIST_TR as $TR_NAME=>&$LIST)
		{
			$tab=explode(".",$TR_NAME);
			
			foreach ($LIST['POS'] as $POS_ID=>&$T)
			{
				if ($POS_ID=='')continue;
				if (!isset($TRANSCRIPTS[$TR_NAME]))continue;
				
				foreach ($TRANSCRIPTS[$TR_NAME] as $CHR_SEQ_ID=>$TR_ID)
				{
					$LIST_ALL[]="(".$TR_ID[0].",".$POS_ID.')';
				}
				$HAS_V=true;
			}
		
		}
		if ($HAS_V)
		{
			/// Then we chunk that list of pairs to avoid too long queries (and also because it can be faster that way)
			$CHUNKS=array_chunk($LIST_ALL,5000);
			foreach ($CHUNKS as $CHUNK)
			{
				$query='SELECT transcript_id, transcript_pos_id,tp.nucl, seq_pos,csp.chr_seq_pos_id ,chr_seq_id
					FROM  transcript_pos tp , chr_seq_pos csp
					WHERE csp.chr_seq_pos_id = tp.chr_seq_pos_id 
					AND (transcript_id,seq_pos) IN ('.implode(',',$CHUNK).')';
				
				$res=runQuery($query);
				if ($res===false)										failProcess($JOB_ID."B05",'Unable to get transcript information');


				echo "TIME\tTRANSCRIPT\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();
				
				/// Temporary stored the result in an array
				$TMP=array();
				foreach ($res as $line)
				{
					$TMP[$line['chr_seq_id']][$line['transcript_id']][$line['seq_pos']]=array($line['transcript_pos_id'],$line['nucl'],$line['chr_seq_pos_id']);
				}

				/// Then we update the search array with the information
				foreach ($SEARCH['TRANSCRIPT'] as $CHR_SEQ_ID=>&$LIST_TR)
				foreach ($LIST_TR as $TR_NAME=>&$LIST)
				{
					if (!isset($TRANSCRIPTS[$TR_NAME][$CHR_SEQ_ID]))continue;
					$TR_ID=$TRANSCRIPTS[$TR_NAME][$CHR_SEQ_ID][0];
					
					
					if (!isset($TMP[$CHR_SEQ_ID][$TR_ID]))continue;
					
					foreach ($LIST['POS'] as $POS_ID=>&$T)
					{
						if ($POS_ID=='')continue;
						if (isset($TMP[$CHR_SEQ_ID][$TR_ID][$POS_ID]))$T=$TMP[$CHR_SEQ_ID][$TR_ID][$POS_ID];
					}
					
				}
				/// As we are going to process a lot of data, we clean up the memory
				$TMP=array();unset($TMP);
			}
			
			
		}
	
	

	}
	
	echo "TIME\tEND TRANSCRIPT\t".round(microtime_float()-$time,4)."\t".count($res)."\n";$time=microtime_float();
	
	/// Then we search for protein information.
	if (isset($SEARCH['PROT']))
	{
		echo "\t####### SEARCH ".count($SEARCH['PROT'])." proteins\n";
		$time=microtime_float();
		$LIST_PROTS=array();
		foreach ($SEARCH['PROT'] as $P_N=>$P)
		{
			if (isset($UNI_MAPPING[$P_N]))
			{
				$LIST_PROTS[$P_N]=$UNI_MAPPING[$P_N];
			}
			else $LIST_PROTS[$P_N]='';
		}
		
		
		
		echo "TIME\tUNI_MAPPING\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();

		$query='SELECT * 
			FROM prot_extdb_map p, prot_seq pe  
			where pe.prot_seq_id = p.prot_seq_Id 
			AND prot_extdb_value IN ( ';
		
		foreach ($SEARCH['PROT'] as $P_N=>$P)
		{
			$query .= "'".$P_N."',";
		}
		$query=substr($query,0,-1).')';
		$res=array();
		$res=runQuery($query);
		if ($res===false)										failProcess($JOB_ID."B06",'Unable to get protein information');
		
		echo "TIME\tEXTDB_MAP\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();

		//print_r($SEARCH['PROT']);
		
		/// Getting primary key for protein sequence based on protein sequence and position
		$query='SELECT * 
			FROM prot_seq_pos 
			WHERE (prot_seq_id, position) IN (';
		$MAP_P=array();$HAS_P=false;
		foreach ($SEARCH['PROT'] as $P_N=>&$P)
		{
			foreach ($res as $K=>&$line)
			{
				if (strpos($line['prot_extdb_value'],$P_N)===false)continue;
				$P['ID']=$line['prot_seq_id'];
				$MAP_P[$P['ID']][]=$P_N;
				unset($res[$K]);
				foreach ($P['POS'] as $pos=>$dummy)
				{
					$query.='('.$P['ID'].','.$pos.'),';
					$HAS_P=true;
				}
			}
		}
		if ($HAS_P)
		{
			$res=runQuery(substr($query,0,-1).')');
			if ($res===false)										failProcess($JOB_ID.'B07','Unable to get protein position');
		//	echo substr($query,0,-1).')'."\n";
			echo "TIME\tPROT_SEQ_POS\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();
			foreach ($res as $line)
			{
				foreach ($MAP_P[$line['prot_seq_id']] as $P_N)
				{
					$PROT_E=&$SEARCH['PROT'][$P_N];
				
					$PROT_E['POS'][$line['position']]=array($line['prot_seq_pos_id'],$line['letter']);
				}
			}
		}

		
	
	}

	if (isset($SEARCH['SO']))
	{
		echo "\t####### SEARCH ".count($SEARCH['SO'])." SO entry\n";
		
		foreach ($SEARCH['SO'] as $SO_ID=>&$DBID)
		{
			if (isset($SO_IDS[$SO_ID]))$DBID=$SO_IDS[$SO_ID];
		}
		
	}
	echo "TIME\tSO_ID\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();
		

	//print_r($SEARCH);exit;
}





function processRecord(&$RECORDS)
{
	global $STATS;
	global $DB_STUDIES;
	echo "############ PROCESS RECORDS\n ";
	/// Step1: check rsid for existence.
	if (count($RECORDS)==0)return;
	$DATA=array();
	$SEARCH=array();
	
	echo "############ PRELOAD DATA\n ";
	$time=microtime_float();
	
	preloadData($SEARCH,$RECORDS,$DATA);
	
	echo "TIME\tTOTAL\tPRELOAD\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();
	echo "############ LOAD FROM DB\n ";
	
	/// Fetch the current data from the database:
	loadFromDB($DATA);
	echo "TIME\tTOTAL\tLOAD FROM DB\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();
	echo "############ COMPARE\n ";
	
	
	//print_r($RECORDS);exit;
	$DEL=array('POSITION'=>array(),'CHANGE'=>array(),'PMID'=>array(),'FREQ'=>array(),'TRANSCRIPT'=>array(),'PROT'=>array());
	$UPD=array('ENTRY'=>array(),'CHANGE'=>array(),'TRANSCRIPT'=>array());
	
	
	/// Find matches between the data from the file vs from the database
	foreach ($DATA as $DB_RSID=>&$DB_ENTRY)
	{
		
		foreach ($RECORDS as $R_RSID=>&$R_ENTRY)
		{

		
			if ($R_RSID!=$DB_RSID)continue;
			//echo "RSID:".$R_RSID."\n";
			$STATS['TEST_ENTRY']++;
			
			/// Compare entries.
			
			if ($DB_ENTRY['ENTRY']['DB_STATUS']=='TO_INS')
			{
				
				$DB_ENTRY['ENTRY']['rsid']=$R_RSID;
				$DB_ENTRY['ENTRY']['date_created']=substr($R_ENTRY['ENTRY']['CREATION_DATE'],0,strpos($R_ENTRY['ENTRY']['CREATION_DATE'],'T'));
				$DB_ENTRY['ENTRY']['date_updated']=substr($R_ENTRY['ENTRY']['LAST_UPDATE'],0,strpos($R_ENTRY['ENTRY']['LAST_UPDATE'],'T'));
			}
			else if ($DB_ENTRY['ENTRY']['DB_STATUS']=='FROM_DB')
			{
				$STATS['EXISTING_ENTRY']++;
				$DB_ENTRY['ENTRY']['DB_STATUS']='VALID';
				$CR_DATE=date('Y-m-d',strtotime(substr($R_ENTRY['ENTRY']['CREATION_DATE'],0,strpos($R_ENTRY['ENTRY']['CREATION_DATE'],'T'))));
				if ($CR_DATE!=$DB_ENTRY['ENTRY']['date_created'])
				{
					echo $R_RSID."\t".$CR_DATE."\t".$DB_ENTRY['ENTRY']['date_created']."\n";
					$UPD['ENTRY']['date_created'][$CR_DATE][]=$DB_ENTRY['ENTRY']['variant_entry_id'];
					$DB_ENTRY['ENTRY']['DB_STATUS']='TO_UPD';
					$STATS['UPDATE_CREATION_DATE']++;
					
				}
				$CH_DATE=date('Y-m-d',strtotime(substr($R_ENTRY['ENTRY']['LAST_UPDATE'],0,strpos($R_ENTRY['ENTRY']['LAST_UPDATE'],'T'))));
				//echo $CH_DATE."\n";
				if ($CH_DATE!=$DB_ENTRY['ENTRY']['date_updated']){
					$DB_ENTRY['ENTRY']['DB_STATUS']='TO_UPD';
					$UPD['ENTRY']['date_updated'][$CH_DATE][]=$DB_ENTRY['ENTRY']['variant_entry_id'];
					$STATS['UPDATE_UPDATE_DATE']++;
					
				}
			}


			//// Compare position
			comparePositions($DB_ENTRY,$R_ENTRY,$SEARCH,$UPD,$DEL);

			/// Compare PMID
			comparePMID($DB_ENTRY,$R_ENTRY,$SEARCH,$UPD,$DEL);
			// print_r($R_ENTRY);
			// print_r($SEARCH['POSITION']);
			// print_r($DB_ENTRY);
				
		}
		
	}
	echo "###### END COMPARISON\n";

	/// Apply changes:

	if ($UPD['ENTRY']!=array())
	{
		echo "###### ".count($UPD['ENTRY']).' entries to update'."\n";
		foreach ($UPD['ENTRY'] as $COL=>$LIST)
		{
			foreach ($LIST as $NEW_VAL=>$LIST_ENTRIES)
			{
				$query=("UPDATE variant_entry SET ".$COL." = '".$NEW_VAL."' WHERE variant_Entry_id IN (".implode(",",$LIST_ENTRIES).')');
				if (!runQueryNoRes($query))failProcess($JOB_ID."C01", 'Unable to update variant_entry.'.$COL."\n".$query);
			}
		}
	}
	
	
	/// To minimize the number of update queries, we group the CHANGES by type and values. This way we can update all the entries in one query
	if ($UPD['CHANGE']!=array())
	{
		echo "###### ".count($UPD['CHANGE']).' variant_change to update'."\n";
		foreach ($UPD['CHANGE'] as $COL=>$LIST)
		{
			foreach ($LIST as $NEW_VAL=>$LIST_ENTRIES)
			{
				$query=("UPDATE variant_change SET ".$COL." = ".$NEW_VAL." WHERE variant_change_id IN (".implode(",",$LIST_ENTRIES).')');
				if (!runQueryNoRes($query))failProcess($JOB_ID."C02", 'Unable to update variant_change.'.$COL."\n".$query);
			}
		}
	}


	/// Same thing for variant_Transcript table:
	if ($UPD['TRANSCRIPT']!=array())
	{
		echo "###### ".count($UPD['TRANSCRIPT']).' variant_transcript to update'."\n";
		foreach ($UPD['TRANSCRIPT'] as $COL=>$LIST)
		{
			foreach ($LIST as $NEW_VAL=>$LIST_ENTRIES)
			{
				$query=("UPDATE variant_transcript_map SET ".$COL." = ".$NEW_VAL." WHERE variant_transcript_id IN (".implode(",",$LIST_ENTRIES).')');
				if (!runQueryNoRes($query))failProcess($JOB_ID."C03", 'Unable to update variant_transcript_map.'.$COL."\n".$query);
			}
		}
	}
	
	/// Deleting all obsolete positions:
	if ($DEL['POSITION']!=array())
	{
		echo "###### DELETION OF ".count($DEL['POSITION']).' variant_position'."\n";
		$query='DELETE FROM variant_position WHERE variant_position_id IN ('.implode(",",$DEL['POSITION']).')';
		if (!runQueryNoRes($query))failProcess($JOB_ID."C04", 'Unable to delete  variant_position.'."\n".$query);
	}
	/// Deleting all obsolete changes:
	if ($DEL['CHANGE']!=array())
	{
		echo "###### DELETION OF ".count($DEL['CHANGE']).' variant_change'."\n";
		$query='DELETE FROM variant_change WHERE variant_change_id IN ('.implode(",",$DEL['CHANGE']).')';
		if (!runQueryNoRes($query))failProcess($JOB_ID."C05", 'Unable to delete  variant_change.'."\n".$query);
	}

	//// Deleting all obsolete PMID variant map
	if ($DEL['PMID']!=array())
	{
		echo "###### DELETION OF ".count($DEL['PMID']).' variant_pmid_map'."\n";
		$query='DELETE FROM variant_pmid_map WHERE variant_pmid_map_id IN ('.implode(",",$DEL['PMID']).')';
		if (!runQueryNoRes($query))failProcess($JOB_ID."C06", 'Unable to delete  variant_pmid_map_id.'."\n".$query);
	}

	/// Deleting all obsolete variant frequency
	if ($DEL['FREQ']!=array())
	{
		echo "###### DELETION OF ".count($DEL['FREQ']).' variant_frequency'."\n";
		$query='DELETE FROM variant_frequency WHERE variant_frequency_id IN ('.implode(",",$DEL['FREQ']).')';
		if (!runQueryNoRes($query))failProcess($JOB_ID."C07", 'Unable to delete  variant_frequency_id.'."\n".$query);
	}

	/// Deleting all obsolete variant transcript
	if ($DEL['TRANSCRIPT']!=array())
	{
		echo "###### DELETION OF ".count($DEL['TRANSCRIPT']).' variant_transcript'."\n";
		$query='DELETE FROM variant_transcript_map WHERE variant_transcript_id IN ('.implode(",",$DEL['TRANSCRIPT']).')';
		if (!runQueryNoRes($query))failProcess($JOB_ID."C08", 'Unable to delete  variant_transcript_map.'."\n".$query);
	}

	/// Deleting all obsolete variant protein
	if ($DEL['PROT']!=array())
	{
		echo "###### DELETION OF ".count($DEL['PROT']).' variant_protein'."\n";
		$query='DELETE FROM variant_protein_map WHERE variant_protein_id IN ('.implode(",",$DEL['PROT']).')';
		
		if (!runQueryNoRes($query))failProcess($JOB_ID."C09", 'Unable to delete  variant_protein_map.'."\n".$query);
	}
	echo "######  END PROCESS BATCH\n";
	return $DATA;
	


}






function comparePMID(&$DB_ENTRY,&$R_ENTRY,&$SEARCH,&$UPD,&$DEL)
{
	/// Here we compare the PMID related to an entry against the PMID in the database
	global $STATS;
	global $FILE_ISSUE;
	if (!isset($R_ENTRY['PMID'])||count($R_ENTRY['PMID'])==0)return;
	$STATS['PMIDS']+=count($R_ENTRY['PMID']);
	
	/// List of PMID from the record
	foreach ($R_ENTRY['PMID'] as $PMID)
	{
		$FOUND=false;

		/// Tested against the ones in the database
		foreach ($DB_ENTRY['PMID'] as $DB_PMID=>&$DB_INFO)
		{
				if ($PMID!=$DB_PMID)continue;

				$DB_INFO['DB_STATUS']='VALID';
				
				$STATS['EXISTING_PMID']++;
				
				$FOUND=true;
		}
		if ($FOUND)continue;
		/// MEans that the mapping to this PMID for this record is not in the database and should be inserted
		/// But only if that PMID is already in the database
		if (!isset($SEARCH['PMID'][$PMID])||$SEARCH['PMID'][$PMID]==-1)
		{
			
			fputs($FILE_ISSUE,"MISSING_PMID\t".$DB_ENTRY['ENTRY']['rsid']."\t".$PMID."\n");
			
			$STATS['PMID_NOT_IN_DB']++;
			
			continue;
		}

		/// Create the new mapping to PMID
		$DB_ENTRY['PMID'] [$PMID]=array('DB_STATUS'=>'TO_INS','pmid_entry_id'=>$SEARCH['PMID'][$PMID]);
	}

	/// Reviewing the PMID in the database to see if some are not in the record
	/// and should be deleted
	foreach ($DB_ENTRY['PMID'] as $DB_PMID=>&$DB_INFO)
	{
		if ($DB_INFO['DB_STATUS']!='FROM_DB')continue;
		$DEL['PMID'][]=$DB_INFO['variant_pmid_map_id'];
		$STATS['DEL_PMID']++;
		
	}
}






function comparePositions(&$DB_ENTRY,&$R_ENTRY,&$SEARCH,&$UPD,&$DEL)
{
	$DEBUG=false;
	global $STATS;
	global $DB_STUDIES;


	if ($DEBUG)echo "COMPARE ".count($R_ENTRY['allele'])." POSITIONS\n";

	if (!isset($R_ENTRY['allele']))
	{
		if ($DEBUG)echo "No allele \t END COMPARE POSITIONS\n";
		return;
	}

	foreach ($R_ENTRY['allele'] as $R_POS=>&$R_DATA)
	{
		if ($DEBUG) echo "\tfocus on position:".$R_POS."\n";
		
		$STATS['ALLELE_TEST']++;
		
		//print_r($R_DATA);
		$tab=explode("|",$R_POS);
		
		$CHR_SEQ_POS_ID=null;

		/// We check if we have this Chromosomal position in the database
		/// Based on $tab[0]=> chromosome, $tab[1]=>position
		if (!isset($SEARCH['POSITION'][$tab[0]][$tab[1]]))
		{
			if ($DEBUG) echo "\t\tallele not found on DNA\n";
			$STATS['ALLELE_NO_DNA']++;
			continue;

		}


		$POS_INFO=&$SEARCH['POSITION'][$tab[0]][$tab[1]];

		/// We check if the allele is the same as the one in the database
		/// To compare properly, we only take the first letter of the allele
		if (strtoupper($POS_INFO[1])!=substr($tab[2],0,1))
		{
			if ($DEBUG) echo "\t\tallele diff from dna on DNA\n";
			$STATS['ALLELE_DIFF_FROM_DNA']++;
			continue;
		}
		
		/// We get the chromosomal position id
		$CHR_SEQ_POS_ID=$POS_INFO[0];
		
		$FOUND=false;

		/// We check if the position is already in the database
		if (isset($DB_ENTRY['POSITION']))
		foreach ($DB_ENTRY['POSITION'] as &$DB_POS)
		{
			if ($DB_POS['chr_seq_pos_id']!=$CHR_SEQ_POS_ID || $DB_POS['ref_all']!=$tab[2])continue;
			
			if ($DEBUG) echo "\t\tPosition found in DB\n";
			
			$DB_POS['DB_STATUS']='VALID';
			/// And if so, we compare the changes
			compareChange($DB_POS,$R_DATA,$SEARCH,$UPD,$DEL,$CHR_SEQ_POS_ID,$DB_ENTRY['ENTRY']['rsid']);
			
			$FOUND=true;
			
			$STATS['EXISTING_ALLELE']++;
			
		}
		/// Otherwise we create the position and the changes
		if (!$FOUND)
		{
			if ($DEBUG) echo "\t\tposition not found in DB\n";
			$CHANGES=array();

			foreach ($R_DATA as $R_ALT_ALL=>&$R_CHANGE)
			{
				if ($DEBUG) echo "\t\tCreating Change ".$R_ALT_ALL."\n";
				$POS_CHANGE=count($CHANGES);
				$CHANGES[$POS_CHANGE]=array('variant_change_id'=>-1,
									'variant_position_id'=>-1,
									'alt_all'=>$R_ALT_ALL,
									'DB_STATUS'=>'TO_INS',
									'FREQUENCY'=>array(),
									'TRANSCRIPT'=>array(),
									'variant_type_id'=>$SEARCH['TYPES'][$R_CHANGE['type']]);
									compareFrequency($R_CHANGE,$CHANGES[$POS_CHANGE],$UPD,$DEL);
									compareTranscripts($R_CHANGE,$CHANGES[$POS_CHANGE],$SEARCH,$UPD,$DEL,$CHR_SEQ_POS_ID,$DB_ENTRY['ENTRY']['rsid']);
			}
			if ($DEBUG) echo "\t\tCreating position \n";


			$DB_ENTRY['POSITION'][]=array(
				'variant_position_id'=>-1, 
				'ref_all'=>$tab[2], 
				'chr_seq_pos_id'=>$CHR_SEQ_POS_ID,
				'DB_STATUS'=>'TO_INS',
				'CHANGE'=>$CHANGES
			);
			
			
		}
		if ($DEBUG) echo "\t\tEND comparing position ".$R_POS."\n";
	}



	if ($DEBUG) echo "\t\tEND comparing ".count($R_ENTRY['allele'])." positions "."\n";
	
	
	if (isset($DB_ENTRY['POSITION']))
	foreach ($DB_ENTRY['POSITION'] as $R_N=>&$DB_POS)
	if ($DB_POS['DB_STATUS']=='FROM_DB')
	{
		if ($DEBUG) echo "\t\tPosition ".$R_N." to be deleted\n";
		$DEL['POSITION'][]=$DB_POS['variant_position_id'];
		$STATS['DEL_ALLELE']++;
		
	}
		
}







function compareFrequency(&$R_CHANGE,&$DB_CHANGE,&$UPD,&$DEL)
{
	global $STATS;
	global $DB_STUDIES;
	if (!isset($R_CHANGE['frequencies']))return;
	
	
	//	print_R($DB_CHANGE);
	
	$STATS['TEST_FREQUENCY']+=count($R_CHANGE['frequencies']);

	/// Looking at the different frequencies
	foreach ($R_CHANGE['frequencies'] as $R_STUDY=>$R_FREQ)
	{
		$FOUND=false;
		//print_r($R_FREQ);
		/// We ensure we have the study in the database
		if (!isset($DB_STUDIES[$R_STUDY]))
		{
			
			echo "UNKNOWN STUDY\t".$R_STUDY."\t".count($DB_STUDIES)."\n";
			print_r($DB_STUDIES);
			$STATS['UNKNOWN_STUDY']++;
			continue;
		}
		
		/// Then we compare against the frequencies in the database
		foreach ($DB_CHANGE['FREQUENCY'] as &$DB_FREQ )
		{
			//It should obviously be the same study
			if ($DB_FREQ['variant_freq_study_id']!=$DB_STUDIES[$R_STUDY])continue;
			
			/// Since there should be only one value per study per allele we don't need to check anything else

			/// However, if the $DB_FREQ is a new record, then we have a problem
			/// since as stated abouve, there should only be one value per study per allele
			if ($DB_FREQ['DB_STATUS']=='TO_INS')
			{
				echo "UNEXPECTED FREQ ISSUE\n";
				print_r($DB_FREQ);
				print_r($R_FREQ);
				exit;

			}

			
			$DB_FREQ['DB_STATUS']='VALID';
			
			$STATS['EXISTING_FREQUENCY']++;
			
			$FOUND=true;
			$CHANGE=false;
			
			
			$STR='UPDATE variant_frequency SET ';
			/// Compare the current values with the one in the database for the reference allele
			if ($R_FREQ['ALLELE']!=$DB_FREQ['ref_count'])
			{
				$STR.= 'ref_count = '.$R_FREQ['ALLELE'].',';
				$CHANGE=true;
			}
			/// Compare the current values with the one in the database for the total
			if ($R_FREQ['TOT']!=$DB_FREQ['alt_count'])
			{
				$STR.= 'alt_count = '.$R_FREQ['TOT'].',';
				$CHANGE=true;
			}
			
			///no change, we skip
			if (!$CHANGE)continue;
			
			$STATS['UPDATE_FREQUENCY']++;

			/// We update the database
			if (!runQueryNoRes(substr($STR,0,-1).' WHERE variant_frequency_id = '.$DB_FREQ['variant_frequency_id']))
			$STATS['FAILED_UPDATE_FREQUENCY']++;
		}
		if ($FOUND)continue;


		$STATS['NEW_FREQUENCY']++;
		//		echo $R_STUDY."\t".$DB_STUDIES[$R_STUDY]."\n";
		$DB_CHANGE['FREQUENCY'][]=array(
			'variant_freq_study_id'=>$DB_STUDIES[$R_STUDY],
			'ref_count'=>$R_FREQ['ALLELE'],
			'alt_count'=>$R_FREQ['TOT'],
			'DB_STATUS'=>'TO_INS');
				
	}

	/// Then we check if there is any record in the database that should be deleted
	if (isset($DB_CHANGE['FREQUENCY']))
	foreach ($DB_CHANGE['FREQUENCY'] as &$DB_FREQ)
		if ($DB_FREQ['DB_STATUS']=='FROM_DB')
		{
			$STATS['DEL_FREQ']++;
			
			$DEL['FREQ'][]=$DB_FREQ['variant_frequency_id'];
		}
}






function compareChange(&$DB_POS,&$R_DATA,&$SEARCH,&$UPD,&$DEL,&$CHR_SEQ_POS_ID,&$RSID)
{
	global $STATS;
	global $DB_STUDIES;

	$DEBUG=false;
	if ($DEBUG) echo "\t\t\tCompare changes\n";
	//echo "#######\n";
	//variant_transcript_id | variant_change_id | transcript_id | transcript_name | transcript_version | transcript_pos_id | nucl | seq_pos | so_entry_id | so_id | ref_all | alt_all 
			
	$STATS['CHANGE_TEST']+=count($R_DATA);

	/// So from a given position, we are going to compare the changes, i.e. the different alleles
	/// This will then call the comparison of the frequencies and the transcripts, which will trigger the protein change

	/// We go through the different alleles in the record and compare them to the database
	foreach ($R_DATA as $R_ALT_ALL=>&$R_CHANGE)
	{
		$FOUND=false;
		if ($DEBUG) echo "\t\t\tFocus on Change ".$R_ALT_ALL."\n";
		
		//	echo "CHANGE::".$R_ALT_ALL."\n";
		//	print_r($R_CHANGE);
		
		if (isset($R_DATA['frequencies']))$STATS['FREQUENCY_TEST']+=count($R_DATA['frequencies']);
		
		if (isset($R_DATA['transcripts']))$STATS['TRANSCRIPT_TEST']+=count($R_DATA['transcripts']);
		
		
		foreach ($DB_POS['CHANGE'] as &$DB_CHANGE)
		{
			/// They are the same if the alternative allele is the same
			if ($R_ALT_ALL!=$DB_CHANGE['alt_all'])continue;

			if ($DEBUG) echo "\t\t\tFound change in database\n";
			
			$DB_CHANGE['DB_STATUS']='VALID';
			
			$STATS['EXISTING_CHANGE']++;
			
			/// If the type of change is different, we add it to the array $UPD listing the changes to be done:
			if ($SEARCH['TYPES'][$R_CHANGE['type']]!=$DB_CHANGE['variant_type_id'])
			{
				$UPD['CHANGE']['variant_type_id'][$SEARCH['TYPES'][$R_CHANGE['type']]][]=$DB_CHANGE['variant_change_id'];
			}
			$FOUND=true;

			/// We compare the frequencies
			compareFrequency($R_CHANGE,$DB_CHANGE,$UPD,$DEL);
			
			/// We compare the transcripts
			compareTranscripts($R_CHANGE,$DB_CHANGE,$SEARCH,$UPD,$DEL,$CHR_SEQ_POS_ID,$RSID);
		}

		/// So if we found it, we are done
		if ($FOUND)continue;
		
		/// Otherwise we create the change
		if ($DEBUG) echo "\t\t\tchange not found in database\n";
		if ($DEBUG) echo "\t\t\tCreating change\n";
		$POS=count($DB_POS['CHANGE']);
		$DB_POS['CHANGE'][$POS]=array('variant_change_id'=>-1,
						'variant_position_id'=>-1,
						'alt_all'=>$R_ALT_ALL,
						'DB_STATUS'=>'TO_INS',
						'FREQUENCY'=>array(),
						'TRANSCRIPT'=>array(),
						'variant_type_id'=>$SEARCH['TYPES'][$R_CHANGE['type']]);
		
		/// We compare the frequencies, which, since it's a new record, it will automatically create the frequencies
		compareFrequency($R_CHANGE,$DB_POS['CHANGE'][$POS],$UPD,$DEL);
		
		/// We compare the transcripts, which, since it's a new record, it will automatically create the transcripts
		compareTranscripts($R_CHANGE,$DB_POS['CHANGE'][$POS],$SEARCH,$UPD,$DEL,$CHR_SEQ_POS_ID,$RSID);

							
		
	}
	
	/// We check if there are any changes in the database that should be deleted
	foreach ($DB_POS['CHANGE'] as &$DB_CHANGE)
	{
		if ($DB_CHANGE['DB_STATUS']!='FROM_DB')continue;
	
		$STATS['DEL_CHANGE']++;
		if ($DEBUG) echo "\t\t\tDelete change ".$DB_CHANGE['alt_all']."\n";
		$DEL['CHANGE'][]=$DB_CHANGE['variant_change_id'];
	}
	
}







function compareTranscripts(&$R_CHANGE,&$DB_CHANGE,&$SEARCH,&$UPD,&$DEL,$CHR_SEQ_POS_ID,$RSID)
{
	global $CHR_SEQ_IDS;
	global $TRANSCRIPTS;
	// [NM_001199860.2] => Array
	// (
	//     [ID] => 751
	//     [POS] => Array
	//         (
	//             [3587] => 209337940
	//         )

	// )
	global $STATS;
	global $FILE_ISSUE;
	$DEBUG=false;
	if ($DEBUG)  echo "COMP TRANSCRIPTS\t".count($R_CHANGE['transcripts'])."\n";



	/// Here we compare the transcripts for a given change
	if ($R_CHANGE['transcripts']!=array())
	{
		/// We go through the different transcripts in the record
		foreach ($R_CHANGE['transcripts'] as $R_TR)
		{
			if ($R_TR==-1)continue;

			$STATS['TEST_TRANSCRIPT']++;
			
			$R_TR_ID=null;
			
			/// We get the chromosome sequence id
			$CHR_SEQ_ID=$CHR_SEQ_IDS[$R_CHANGE['seq_id']];
			
			$STR_DEBUG=$RSID."::".$R_CHANGE['seq_id']."::".$R_TR['rule']."__".$R_TR['seq_id'].'.'.$R_TR['position'].':'.$R_TR['deleted'];
			
			/// Now if didn't find the corresponding transcript in the db, we can't do anything
			if (!isset($SEARCH['TRANSCRIPT'][$CHR_SEQ_ID][$R_TR['seq_id']]) ||
					   $SEARCH['TRANSCRIPT'][$CHR_SEQ_ID][$R_TR['seq_id']]['ID']==-1)
			{
				echo $STR_DEBUG."\tNO TRANSCRIPT\n";
				fputs($FILE_ISSUE,"NO TRANSCRIPT\t".$STR_DEBUG."\n");
				$STATS['UNKNOWN_TRANSCRIPT']++;
				
				continue;
			}
			$STRAND=null;
			
			$STRAND=$TRANSCRIPTS[$R_TR['seq_id']][$CHR_SEQ_ID][1];
			$STR_DEBUG.=" (".$STRAND.")";
			/// If the strand is negative, we need to adjust the position
			/// As the position is given from the 5' end, we need to adjust it to the 3' end
			/// Example rsid: 1377811093
			if ($STRAND=='-' &&strlen($R_TR['deleted'])>1)
			{
				
				$R_TR['position']+=strlen($R_TR['deleted'])-1;
				$STR_DEBUG.=" SHIFT POS:".$R_TR['position'];
			}
			
			/// Transcript ID
			$R_TR_ID=$SEARCH['TRANSCRIPT'][$CHR_SEQ_ID][$R_TR['seq_id']]['ID'];
			
			$STR_DEBUG.= "\tTRID:".$R_TR_ID;
			
			$R_TR_POS_ID='';

			/// Now we check if the position of the transcript is in the database
			if ($R_TR['position']!='')
			{
				//echo $R_TR['seq_id'].":".$R_TR_ID."\t".$R_TR['position']."\n";
				
				/// Finding the information in the $SEARCH array
				$DB_SEQ_P=&$SEARCH['TRANSCRIPT'][$CHR_SEQ_ID][$R_TR['seq_id']];
				//	print_r($DB_SEQ_P);
				//	echo "POS:".$R_TR['position']."\n";
				//print_r($DB_SEQ_P);

				/// Now based on the position of the transcript, we get the corresponding PK in the database
				/// If it's -1, then we don't have that position.
				/// This whould be pretty rare. If we have the transcript, we should have the transcript sequence.
				if (!isset($DB_SEQ_P['POS'][$R_TR['position']])
				||$DB_SEQ_P['POS'][$R_TR['position']]==-1)
				{
					echo $STR_DEBUG."\tNO POSITION\n";
					fputs($FILE_ISSUE,"NO POSITION\t".$STR_DEBUG."\n");
					$STATS['UNKNOWN_TRANSCRIPT_POSITION']++;
					continue;
				}

				$DB_POS=$DB_SEQ_P['POS'][$R_TR['position']];
				
				$NUCL_T=substr($R_TR['deleted'],0,1);
				
				if ($STRAND=='-')$NUCL_T=substr($R_TR['deleted'],-1);
				
				$STR_DEBUG.= "\t".$DB_POS[0]."\t".$NUCL_T.'<>'.$DB_POS[1]."\t".$CHR_SEQ_POS_ID."<>".$DB_POS[2]."\t";
				
				/// As a safety net, here, we went from the Variant>Variant position (which has the chromosomal position)
				/// > Transcript>Transcript position (which has the position in the transcript)
				/// However, the record for the transcript position should have a chromosomal position as well.
				/// Therefore, we can use this to double check that everything is correct.
				if ($CHR_SEQ_POS_ID!=$DB_POS[2])
				{
					echo $STR_DEBUG."MISMATCH_TRANSCRIPT_POSITION_DNA\n";
					fputs($FILE_ISSUE,"MISMATCH_TRANSCRIPT_POSITION_DNA\t".$STR_DEBUG."\n");
					
					$STATS['MISMATCH_TRANSCRIPT_POSITION_DNA']++;
					continue;
				}
				/// The nucleotide should be the same as the one in the database
				if ($NUCL_T!=$DB_POS[1])
				{
					echo $STR_DEBUG."MISMATCH_TRANSCRIPT_POSITION_NUCL\n";
					fputs($FILE_ISSUE,"MISMATCH_TRANSCRIPT_POSITION_NUCL\t".$STR_DEBUG."\n");
					$STATS['MISMATCH_TRANSCRIPT_POSITION_NUCL']++;
					continue;
				}
				//echo $STR_DEBUG."\tVALID\n";
				$R_TR_POS_ID=$DB_POS[0];	
			}

			/// If the change is not in the database, we can't do much
			if (!isset($SEARCH['SO'][$R_TR['seq_onto_id']]))
			{
				$STATS['UNKNOWN_TRANSCRIPT_SO']++;
				echo $STR_DEBUG."\tUNKNOWN_TRANSCRIPT_SO\n";
				fputs($FILE_ISSUE,"UNKNOWN_TRANSCRIPT_SO\t".$STR_DEBUG."\n");
				continue;
			}
			$SO_ENTRY_ID=$SEARCH['SO'][$R_TR['seq_onto_id']];

			$FOUND=false;

			/// Now that we have all the information, we can check if we find that information in the database
			foreach ($DB_CHANGE['TRANSCRIPT'] as &$DB_TR)
			{
				/// So it should be the same transcript (via the primary key)
				if ($DB_TR['transcript_id']!=$R_TR_ID)continue;
				/// ANd the same position (via the primary key)
				if ($DB_TR['transcript_pos_id']!=$R_TR_POS_ID)continue;


				if ($DB_TR['DB_STATUS']=='TO_INS')
				{
					echo "ISSUE TRANSCRIPT\n";
				//	print_r($R_CHANGE['transcripts']);
					
					print_r($DB_TR);
					print_r($R_TR);
					continue;
				}
				$FOUND=true;

				
				$STATS['EXISTING_TRANSCRIPT']++;
				
				/// We set the status to VALId, which will avoid it to be deleted
				$DB_TR['DB_STATUS']='VALID';
				
				/// Then we compare the changes for the reference "allele" and the alternative "allele"
				/// It's easier to actually delete and reinsert  the tecord if there is a change than updating.
				/// So if there is a change, we add the record to the $DEL array
				/// and change the $FOUND flag back to false so it can be reinserted
				if($DB_TR['ref_all']!=$R_TR['deleted'])
				{
					$STATS['UPD_TRANSCRIPT']++;
					$DEL['TRANSCRIPT'][]=$DB_TR['variant_transcript_id'];
					$FOUND=false;
				}
				if($DB_TR['alt_all']!=$R_TR['inserted'])
				{
					$STATS['UPD_TRANSCRIPT']++;
					$DEL['TRANSCRIPT'][]=$DB_TR['variant_transcript_id'];
					$FOUND=false;
				}
				if($DB_TR['so_id']!=$R_TR['seq_onto_id'])
				{
					$STATS['UPD_TRANSCRIPT']++;

					$UPD['TRANSCRIPT']['so_entry_id'][$SO_ENTRY_ID][]=$DB_TR['variant_transcript_id'];	
				}
				/// If the record is not going to be deleted
				/// AND there is a protein change, we compare the protein change
				if (!$FOUND && isset($R_TR['prot_info']) && $R_TR['prot_info']!=array())
				{
					if (!isset($DB_TR['PROT']))$DB_TR['PROT']=array();
					
					compareProtein($R_TR['prot_info'],$DB_TR['PROT'],$SEARCH,$UPD,$DEL,$RSID);
					
					// print_r($R_TR['prot_info']);
					// exit;
				}
			}
			if ($FOUND)continue;
			
			echo $STR_DEBUG."\t::NEW\n";
			$STATS['NEW_TRANSCRIPT']++;
			$POS_TR=count($DB_CHANGE['TRANSCRIPT']);
			$DB_CHANGE['TRANSCRIPT'][$POS_TR]=array(
				'DB_STATUS'=>'TO_INS',
				'variant_transcript_id'=>-1, 
				'variant_change_id'=>-1, 
				'transcript_id'=>$R_TR_ID, 
				'transcript_pos_id'=>$R_TR_POS_ID,
				'so_entry_id'=>$SO_ENTRY_ID, 
				'ref_all'=>$R_TR['deleted'],
				'alt_all'=>$R_TR['inserted'],
			'PROT'=>array());

			/// If there is a protein change, we compare it
			if (isset($R_TR['prot_info']) && $R_TR['prot_info']!=array())
			{
				
				compareProtein($R_TR['prot_info'],$DB_CHANGE['TRANSCRIPT'][$POS_TR]['PROT'],$SEARCH,$UPD,$DEL,$RSID);
				
				// print_r($R_TR['prot_info']);
				// exit;
			}

		}
		// print_R($R_CHANGE);
	
	}
	
	/// Now that we are done, we can check the records from the db 
	/// to see the one that still are FROM_DB and should be deleted
	foreach ($DB_CHANGE['TRANSCRIPT'] as &$DB_TR)
	{
		if ($DB_TR['DB_STATUS']=='FROM_DB')
		{
			$STATS['DEL_TRANSCRIPT']++;
			
			$DEL['TRANSCRIPT'][]=$DB_TR['variant_transcript_id'];
			
		}
		if (isset($DB_TR['PROT']))
		{
			//echo "TTK\n";
			
			foreach ($DB_TR['PROT'] as &$T)
			{
				if ($T['DB_STATUS']!='FROM_DB')continue;
			
				if ($T['variant_protein_id']=='')print_r($DB_TR['PROT']);
				else $DEL['PROT'][]=$T['variant_protein_id'];
				
			}
		}
	}

}


function compareProtein(&$R_PROT,&$DB_PROT,&$SEARCH,&$UPD,&$DEL,&$RSID)
{
	global $FILE_ISSUE;
	global $STATS;

	$DEBUG=false;
	if ($DEBUG)echo "################\n";
	//print_r($SEARCH['PROT']['NP_001158195.1']);
	if ($DEBUG)print_r($R_PROT);	

	
	$STATS['PROT_SEQ']++;
	$STR_DEBUG= $RSID."\tPROT_INFO:".$R_PROT['PROT_SEQ'].":".$R_PROT['PROT_POS'].":".$R_PROT['PROT_INS'].">".$R_PROT['PROT_DEL']."::".$R_PROT['PROT_SO_ID'];
	$FOUND=false;

	/// We don't have the impact of the protein change, so we can't do much
	if (!isset($SEARCH['SO'][$R_PROT['PROT_SO_ID']]))
	{
		if ($DEBUG)echo "\t\t".$STR_DEBUG."\tNO_SO\n";
		
		$STATS['UNKNOWN_PROT_SO']++;
		
		fputs($FILE_ISSUE,"UNKNOWN_PROT_SO\t".$STR_DEBUG."\n");
		return;
	}
	/// Assign $SO_ENTRY_ID to the SO entry id of the impact
	$SO_ENTRY_ID=$SEARCH['SO'][$R_PROT['PROT_SO_ID']];

	if ($DEBUG)echo "PROTSEQ:\n";
	
	if ($DEBUG)print_r($SEARCH['PROT'][$R_PROT['PROT_SEQ']]);
	
	$PROT_SEQ_ID='';
	/// Now we look at the protein record, do we have it in the database? If not, we stop
	if (!isset($SEARCH['PROT'][$R_PROT['PROT_SEQ']])||$SEARCH['PROT'][$R_PROT['PROT_SEQ']]['ID']==-1)
	{
		if ($DEBUG)echo "\t\t".$STR_DEBUG."\tPROT_SEQ_NOT_IN_DB\n";

		$STATS['PROT_SEQ_NOT_IN_DB']++;
		
		fputs($FILE_ISSUE,"PROT_SEQ_NOT_IN_DB\t".$STR_DEBUG."\n");
		
		return;
	}
	
	/// Assign the PK of the protein sequence to $PROT_SEQ_ID
	$PROT_SEQ_ID=$SEARCH['PROT'][$R_PROT['PROT_SEQ']]['ID'];
	
	$STR_DEBUG.="\tPROT_SEQ_ID::".$PROT_SEQ_ID."\t";
	
	/// Now we look at the position of the protein change
	$PROT_SEQ_POS_ID='';

	/// So we search:
	$D=&$SEARCH['PROT'][$R_PROT['PROT_SEQ']]['POS'];


	if ($R_PROT['PROT_POS']!='')
	{
		/// Not found? We stop
 		if (!isset($D[$R_PROT['PROT_POS']]))
		{
			echo "\t\t".$STR_DEBUG."\tNO_SEQ_POS\n";
			
			$STATS['PROT_SEQ_POS_NOT_FOUND']++;
			fputs($FILE_ISSUE,"PROT_SEQ_POS_NOT_FOUND\t".$STR_DEBUG."\n");
			return;
		}
		/// Exception to the rule if it's a STOP codon
		if ($D[$R_PROT['PROT_POS']][0]==-1 && $R_PROT['PROT_SO_ID']!='SO:0001578' )/// SO:0001578 is a modification on the stop codon, therefore outside the protein sequence
		{
			
			echo "\t\t".$STR_DEBUG."\tNO_SEQ_POS_DB\n";
			//print_r($SEARCH['PROT'][$R_PROT['PROT_SEQ']]);
			
			fputs($FILE_ISSUE,"PROT_SEQ_POS_NOT_IN_DB\t".$STR_DEBUG."\n");
			$STATS['PROT_SEQ_POS_NOT_IN_DB']++;
			return;
		}
		if ($D[$R_PROT['PROT_POS']][1]!=substr($R_PROT['PROT_DEL'],0,1) && $R_PROT['PROT_SO_ID']!='SO:0001578')/// SO:0001578 is a modification on the stop codon
		{
			echo "\t\t".$STR_DEBUG."\tDIFF_SEQ_POS\t".$D[$R_PROT['PROT_POS']][1]."\t".substr($R_PROT['PROT_DEL'],0,1)."\n";
			$STATS['PROT_SEQ_DIFF_AA']++;
			fputs($FILE_ISSUE,"PROT_SEQ_DIFF_AA\t".$STR_DEBUG."\n");
			return;
		}
		
		$PROT_SEQ_POS_ID=$D[$R_PROT['PROT_POS']][0];
	}
	$STR_DEBUG.="\tPROT_SEQ_POS_ID::".$PROT_SEQ_POS_ID."\t";

	/// Now that we have all the information, we can compare it to the database

	if ($DB_PROT!=array())
	foreach ($DB_PROT as &$DB_ENTRY)
	{
		if ($DEBUG)
		{
			echo $DB_ENTRY['prot_seq_id']."|".$PROT_SEQ_ID."\t".$DB_ENTRY['prot_seq_pos_id'].'|'.$PROT_SEQ_POS_ID."\t".$DB_ENTRY['seq_letter'].'|'.$R_PROT['PROT_DEL']."\t".$DB_ENTRY['comp_prot_all'].'|'.$R_PROT['PROT_INS']."\t".$DB_ENTRY['so_entry_id']."|".$SO_ENTRY_ID."\n";
		}
		/// Must be the same protein sequence
		if ($DB_ENTRY['prot_seq_id']!=$PROT_SEQ_ID)continue;
		/// Specific case where it is a stop lost, the comparison is not exact. Example rsid 5065
		if ($R_PROT['PROT_SO_ID']=='SO:0001578')
		{
			if (!($DB_ENTRY['prot_seq_pos_id']==''&&$PROT_SEQ_POS_ID==-1)) continue;
			if (!($DB_ENTRY['seq_letter']==''  && $R_PROT['PROT_DEL']=='*'))continue;
		}
		else
		{
			if ($DB_ENTRY['prot_seq_pos_id']!=$PROT_SEQ_POS_ID)continue;
			if ($DB_ENTRY['seq_letter']!=$R_PROT['PROT_DEL'])continue;
		}

		
		if ($DB_ENTRY['comp_prot_all']!=$R_PROT['PROT_INS'])continue;
		/// Must be the same impact:
		if ($DB_ENTRY['so_entry_id']!=$SO_ENTRY_ID)continue;
		
		$FOUND=true;
		
		echo $STR_DEBUG."::FOUND\tDBID:".$DB_ENTRY['variant_protein_id']."\n";
		
		$DB_ENTRY['DB_STATUS']='VALID';
		$STATS['PROT_SEQ_VALID']++;
		
		break;

	}
	/// Found -> we continue;
	if ($FOUND)return;

	/// Otherwise we add it.
	echo "\t\t".$STR_DEBUG."\tNEW\n";
	//print_r($SEARCH['PROT']);
	$NEW_REC=array(
		'DB_STATUS'=>'TO_INS',
		'variant_protein_id'=>-1, 
		'variant_transcript_id'=>-1,
		'prot_seq_id'=>$PROT_SEQ_ID,
		'iso_id'=>$R_PROT['PROT_SEQ'],
		'ref_prot_all'=>$R_PROT['PROT_DEL'], 
		'comp_prot_all'=>$R_PROT['PROT_INS'],
		'so_entry_id'=>$SO_ENTRY_ID, 
		'so_id'=>$R_PROT['PROT_SO_ID'],
		'seq_pos'=>$R_PROT['PROT_POS'], 
		'seq_letter'=>$R_PROT['PROT_DEL'], 
		'prot_seq_pos_id'=>$PROT_SEQ_POS_ID);
	$STATS['PROT_SEQ_NEW']++;
		
	$DB_PROT[]=$NEW_REC;

}


function loadFromDB(&$DATA)
{
	global $JOB_ID;
	global $HAS_TRANSCRIPT_MAP_DATA;
	global $TYPES;
	global $SOURCE_ID;
	global $TRANSCRIPT_IDS;
	global $SO_ENTRIES;

	$time=microtime_float();


	/// First thing first: get the variant entry from the database based on the rsid, which are the keys of the array
	$STR="SELECT variant_entry_id, rsid, date_updated, datE_created
			FROM variant_entry ve 
			WHERE rsid IN (".implode(',',array_keys($DATA)).')';
	$res=runQuery($STR);
	if ($res ===false) failProcess($JOB_ID."D01",'Unable to find variant entries');
	$MAP=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['rsid']]=array(
			'ENTRY'=>$line,
			'POSITION'=>array(),
			'PMID'=>array());

		/// We create a map to link the rsid to the variant entry id
		$MAP[$line['variant_entry_id']]=$line['rsid'];
		
	}
	echo "TIME\tFROMDB\tVARIANT_ENTRY\t".round(microtime_float()-$time,2)."\t".count($res)."\n";$time=microtime_float();

	/// Now for those that are not in the database, we create the record
	foreach ($DATA as $RSID=>&$ENTRY)
	{
		if ($ENTRY!=array())continue;
		$ENTRY=array(
		'ENTRY'=>array(
			'variant_entry_id'=>'',
			'rsid'=>'', 
			'date_updated'=>'',
			'date_created'=>'',
			'DB_STATUS'=>'TO_INS'),
		'CHANGE'=>array(),
		'PMID'=>array());
	}
	
	/// If we have no variant entry, we stop
	if ($MAP==array())return;



	/// Now from the variant_entry_ids, we get the publications
	$res=runQuery("SELECT pmid,v.pmid_entry_id, variant_pmid_map_id, variant_entry_Id 
				FROM variant_pmid_map v, pmid_entry p 
				where p.pmid_entry_id = v.pmid_entry_id
				AND variant_entry_id IN (".implode(",",array_keys($MAP)).")");
	if ($res===false)										failProcess($JOB_ID."D02",'Unable to get publication from variant');
	
	foreach ($res as $line)
	{
		/// We add a status to say it's from the db
		$line['DB_STATUS']='FROM_DB';
		$ENTRY=&$DATA[$MAP[$line['variant_entry_id']]];
		$ENTRY['PMID'][$line['pmid']]=$line;
	}
	echo "TIME\tFROMDB\tVARIANT_PMID_MAP\t".round(microtime_float()-$time,2)."\t".count($res)." publi\n";$time=microtime_float();
	
	/// In addition to the publication, we get the variant position
	/// With the variant position, we get the reference allele, the chromosome position & the nucleotide,
	/// the reference sequence name and version of the chromosome
	$res=runQuery("SELECT vp.variant_position_id, variant_entry_id, variant_seq as ref_all, 
			vp.chr_seq_pos_id, chr_pos, nucl, refseq_name,refseq_version 
			FROM variant_position vp
			LEFT JOIN variant_allele va ON vp.ref_all = va.variant_allele_id , chr_seq_pos cs,chr_seq c 
			where vp.chr_seq_pos_id = cs.chr_seq_pos_id 
			AND cs.chr_seq_id = c.chr_seq_id
			AND variant_entry_id IN (".implode(",",array_keys($MAP)).")");
	if ($res===false)										failProcess($JOB_ID."D03",'Unable to get  variant positions');
	$MAP_POS=array();
	
	foreach ($res as $line)
	{
		$ENTRY=&$DATA[$MAP[$line['variant_entry_id']]];
		/// We add a status to say it's from the db
		$line['DB_STATUS']='FROM_DB';
		$line['CHANGE']=array();

		
		$ENTRY['POSITION'][$line['variant_position_id']]=$line;
		/// We create a map to link the variant position id to the variant entry id
		$MAP_POS[$line['variant_position_id']]=$MAP[$line['variant_entry_id']];
	}
	
	echo "TIME\tFROMDB\tVARIANT_POSITION\t".round(microtime_float()-$time,2)."\tPosition:".count($res)."\n";$time=microtime_float();
	

	if ($MAP_POS==array())return;
	
	
	/// Now for all the variant positions, we find the variant change
	/// along the the type of change, the alternative allele
	$MAP_CHANGE=array();
	$res=runQuery("SELECT variant_change_id, variant_position_id,vc.variant_type_id,  alt_all
		FROM variant_change vc
		where variant_position_id IN (".implode(",",array_keys($MAP_POS)).")");
	if ($res===false)										failProcess($JOB_ID."D04",'Unable to get variant change');

	/// Since there can be several thousands of allele sequences,
	/// we actually put them in a separate table, called variant_allele,
	/// and have a reference to it in the variant_change table
	/// Therefore, when we get the variant change, we also get the reference to the variant allele
	/// We then get the sequence of the allele from the variant_allele table
	$TMP=array();
	foreach ($res as $line)
	{
		if ($line['alt_all']!='')$TMP[$line['alt_all']]='';
	}

	if (count($TMP))
	{
		$res2=runQuery("SELECT variant_allele_id, variant_seq 
			from variant_allele 
			where variant_allele_id IN (".implode(',',array_keys($TMP)).')');
		if ($res2===false)																					failProcess($JOB_ID."D05",'Unable to get Variant alleles');
		foreach ($res2 as $l2)$TMP[$l2['variant_allele_id']]=$l2['variant_seq'];	
	}

	foreach ($res as $line)
	{
		/// Create the mapping between the variant change and the variant position
		$MAP_CHANGE[$line['variant_change_id']]=$line['variant_position_id'];
		
		/// Get the variant_entry record
		$ENTRY=&$DATA[$MAP_POS[$line['variant_position_id']]];
		
		/// Then get the corresponding position record
		$ENTRY_POS=&$ENTRY['POSITION'][$line['variant_position_id']];
		
		///Map the variant_type_id to the type of change
		$line['variant_name']=$TYPES[$line['variant_type_id']];
		
		/// Ensure that if it's not a deletion we change the alt_all (which is a foreign key)
		/// To the actual sequence of the allele
		if ($line['alt_all']!='')$line['alt_all']=$TMP[$line['alt_all']];
		
		///Add the status to say it's from the db
		$line['DB_STATUS']='FROM_DB';

		$line['FREQUENCY']=array();
		$line['TRANSCRIPT']=array();
		
		/// Assign the change to the position
		$ENTRY_POS['CHANGE'][$line['variant_change_id']]=$line;
		
	}
	
	echo "TIME\tFROMDB\tVARIANT_CHANGE\t".round(microtime_float()-$time,2)."\tChange:".count($res)."\n";$time=microtime_float();
	
	/// No change, we stop
	if (count($MAP_CHANGE)==0)return;
		

	/// Now we get the frequencies of the variant BUT ONLY FOR the frequencies provided by dbSNP
	$res=runQuery("SELECT variant_frequency_id , variant_change_id , v.variant_freq_study_id , ref_count , alt_count 
		FROM variant_frequency v, variant_freq_study   vf
		WHERE vf.variant_freq_study_id = v.variant_freq_study_id 
		AND vf.source_id = ".$SOURCE_ID."
		AND  variant_change_id IN (".implode(",",array_keys($MAP_CHANGE)).')');


	if ($res===false)										failProcess($JOB_ID."D06",'Unable to get frequencies from variant');
	foreach ($res as $line)
	{
		/// Using the multiple mappings, we get the variant record
		$ENTRY=&$DATA[$MAP_POS[$MAP_CHANGE[$line['variant_change_id']]]];
		
		/// Then the position
		$ENTRY_POS=&$ENTRY['POSITION'][$MAP_CHANGE[$line['variant_change_id']]];
		
		/// Then the change:
		$ENTRY_CHANGE=&$ENTRY_POS['CHANGE'][$line['variant_change_id']];
		
		/// Add the status to say it's from the db
		$line['DB_STATUS']='FROM_DB';
		
		/// Add the frequency record to the change
		$ENTRY_CHANGE['FREQUENCY'][]=$line;
	}
	echo "TIME\tFROMDB\tFREQ\t".round(microtime_float()-$time,2)."\t".count($res)." frequencies\n";$time=microtime_float();


	/// Now we get the transcript change information
	/// Which includes the transcript name, the version, the position in the transcript, the nucleotide, the sequence position
	$query='SELECT variant_transcript_id,variant_change_id, transcript_id, transcript_pos_id, so_entry_id, tr_ref_all, tr_alt_all
			FROM variant_transcript_map 
			WHERE variant_change_id IN ('.implode(",",array_keys($MAP_CHANGE)).')';
	$res=runQuery($query);
	if ($res===false)																					failProcess($JOB_ID."D07",'Unable to get variant_transcript_map');
	echo "TIME\tFROMDB\tTR\t".round(microtime_float()-$time,2)."\t".count($res)." transcript\n";$time=microtime_float();
	
	/// Similar to the variant change, we have the reference to the allele in the transcript change
	/// So we need to create a temporary array to get the sequence of the allele => $TMP_LIST['all']
	/// We also need to get the sequence of the nucleotide in the transcript => $TMP_LIST['tr_pos']
	$TMP_LIST=array('tr_pos'=>array(),'all'=>array());
	foreach ($res as $line)
	{
		if ($line['transcript_pos_id']!='')$TMP_LIST['tr_pos'][$line['transcript_pos_id']]=array();
		
		if ($line['tr_ref_all']!='')$TMP_LIST['all'][$line['tr_ref_all']]='';
		if ($line['tr_alt_all']!='')$TMP_LIST['all'][$line['tr_alt_all']]='';
		
	}
	
	/// Here we get the sequence of the nucleotide in the transcript
	if (count($TMP_LIST['tr_pos'])>0)
	{
		$res2=runQuery("SELECT transcript_pos_id,nucl,seq_pos 
						from transcript_pos 
						where transcript_pos_id IN (".implode(',',array_keys($TMP_LIST['tr_pos'])).')');
		if ($res2===false)																					failProcess($JOB_ID."D08",'Unable to get transcript_pos');
		foreach ($res2 as $l2)
		{
			$TMP_LIST['tr_pos'][$l2['transcript_pos_id']]=array($l2['nucl'],$l2['seq_pos']);
		}
	}
	

	/// Here we get the sequence of the allele in the transcript
	if (count($TMP_LIST['all'])>0)
	{
		$res2=runQuery("SELECT variant_seq, variant_allele_id 
						from variant_allele 
						where variant_allele_id IN (".implode(',',array_keys($TMP_LIST['all'])).')');
		if ($res2===false)																					failProcess($JOB_ID."D09",'Unable to get variant_allel');
		foreach ($res2 as $l2)
		{
			$TMP_LIST['all'][$l2['variant_allele_id']]=$l2['variant_seq'];
		}
	
	}
	echo "TIME\tFROMDB\tTR_ADD\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();

	/// Now that we have all of the information, we can process the results from this query:
	////SELECT variant_transcript_id,variant_change_id, transcript_id, transcript_pos_id, so_entry_id, tr_ref_all, tr_alt_all
	///FROM variant_transcript_map 
	///WHERE variant_change_id IN....
	$MAP_VAR_TR=array();
	foreach ($res as $line)
	{
		///Creating the mapping table between the variant transcript and the variant change, position,entry
		$MAP_VAR_TR[$line['variant_transcript_id']]=array($MAP_POS[$MAP_CHANGE[$line['variant_change_id']]],$MAP_CHANGE[$line['variant_change_id']],$line['variant_change_id']);
		
		/// Get the variant record
		$ENTRY=&$DATA[$MAP_POS[$MAP_CHANGE[$line['variant_change_id']]]];
		
		/// Get the position record
		$ENTRY_POS=&$ENTRY['POSITION'][$MAP_CHANGE[$line['variant_change_id']]];
		
		/// Get the change record
		$ENTRY_CHANGE=&$ENTRY_POS['CHANGE'][$line['variant_change_id']];
		
		/// Add the status to say it's from the db
		$line['DB_STATUS']='FROM_DB';
		
		/// Get the transcript name and version
		$line['transcript_name']=$TRANSCRIPT_IDS[$line['transcript_id']][0];
		$line['transcript_version']=$TRANSCRIPT_IDS[$line['transcript_id']][1];
		
		/// Get the change:
		if ($line['so_entry_id']!='')$line['so_id']=$SO_ENTRIES[$line['so_entry_id']];
		else 						 $line['so_id']='';
		
		/// Get the reference allele:
		if ($line['tr_ref_all']!='')$line['ref_all']=$TMP_LIST['all'][$line['tr_ref_all']];
		else $line['ref_all']='';
		
		/// Get the alternative allele:
		if ($line['tr_alt_all']!='')$line['alt_all']=$TMP_LIST['all'][$line['tr_alt_all']];
		else $line['alt_all']='';
		
		/// If we have the position in the transcript, we get the nucleotide and the sequence position
		if ($line['transcript_pos_id']!='')
		{
			$line['nucl']=$TMP_LIST['tr_pos'][$line['transcript_pos_id']][0];
			$line['seq_pos']=$TMP_LIST['tr_pos'][$line['transcript_pos_id']][1];
		}
		else
		{
			$line['nucl']='';
			$line['seq_pos']='';
		}
		$line['PROT']=array();

		/// Add the transcript change to the transcript
		$ENTRY_CHANGE['TRANSCRIPT'][$line['variant_transcript_id']]=$line;
	}




	echo "TIME\tFROMDB\tENDTR\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();

	if (!($MAP_VAR_TR!=array() && $HAS_TRANSCRIPT_MAP_DATA))return;

	/// Now we get the protein change information
	$query='SELECT variant_protein_id, variant_transcript_id, ps.prot_seq_id, iso_id,  vr.variant_prot_seq as ref_prot_all, vc.variant_prot_seq as comp_prot_all,
	vpm.so_entry_id, so_id,psp.position as seq_pos, psp.letter as seq_letter, psp.prot_seq_pos_id
	FROM prot_seq ps, variant_protein_map vpm
	LEFT JOIN so_entry s ON s.so_entry_id = vpm.so_entry_id
	LEFT JOIN variant_prot_allele vr ON vr.variant_prot_allele_id = vpm.prot_ref_all
	LEFT JOIN variant_prot_allele vc ON vc.variant_prot_allele_id = vpm.prot_alt_all
	LEFT JOIN prot_seq_pos psp ON psp.prot_seq_pos_id = vpm.prot_seq_pos_id
	WHERE ps.prot_seq_id = vpm.prot_seq_id 
	AND variant_transcript_id IN ('.implode(',',array_keys($MAP_VAR_TR)).')';
	$res=runQuery($query);
	
	echo "TIME\tFROMDB\tPROT_SEQ_MAP\t".round(microtime_float()-$time,2)."\t".count($res)." traprot seq var\n";$time=microtime_float();
	
	if ($res===false)										failProcess($JOB_ID."D10",'Unable to get transcript impact');
	
	foreach ($res as $line)
	{
		/// Get the variant record
		$ENTRY=&$DATA[$MAP_VAR_TR[$line['variant_transcript_id']][0]];
	
		/// Get the position record
		$ENTRY_POS=&$ENTRY['POSITION'][$MAP_VAR_TR[$line['variant_transcript_id']][1]];
	
		/// Get the change record
		$ENTRY_CHANGE=&$ENTRY_POS['CHANGE'][$MAP_VAR_TR[$line['variant_transcript_id']][2]];
	
		/// Get the transcript record
		$ENTRY_TRANSCRIPT=&$ENTRY_CHANGE['TRANSCRIPT'][$line['variant_transcript_id']];
	
		/// Add the status to say it's from the db
		$line['DB_STATUS']='FROM_DB';
	
		/// Get the protein sequence
		$ENTRY_TRANSCRIPT['PROT'][$line['variant_protein_id']]=$line;
	}
	
	
}


function processEntry(&$arr)
{
	/// $arr is the array containing the data from the JSON file
	/// We are going to parse it by selecting the necessary information we need and format it 
	/// in a way that is compatible with the database

	/// We create an entry
	$ENTRY=array('ENTRY'=>array(
		'variant_entry_id'=>-1,
		'rsid'=>$arr['refsnp_id'],
		'DB_STATUS'=>'TO_INS',
		'LAST_UPDATE'=>$arr['last_update_date'],
		'CREATION_DATE'=>$arr['create_date']),
		'PMID'=>array());


	/// If any citations are provided, we list them
	/// This is the easiest case to handle, so we handle it first
	if (isset($arr['citations']))
	{
		foreach ($arr['citations'] as $PMID)	
		{
			$ENTRY['PMID'][]=$PMID;
		}
	}
	


/*
  "primary_snapshot_data": {
        "placements_with_allele": [
            {
                "seq_id": "NC_000020.11",
                "is_ptlp": true,
                "placement_annot": {
                    "seq_type": "refseq_chromosome",
                    "mol_type": "genomic",
                    "seq_id_traits_by_assembly": [
                        {
                            "assembly_name": "GRCh38.p14",
                            "assembly_accession": "GCF_000001405.40",
                            "is_top_level": true,
                            "is_alt": false,
                            "is_patch": false,
                            "is_chromosome": true
                        }
                    ],
                    "is_aln_opposite_orientation": false,
                    "is_mismatch": false
                },
                "alleles": [
                    {
                        "allele": {
                            "spdi": {
                                "seq_id": "NC_000020.11",
                                "position": 22685494,
                                "deleted_sequence": "C",
                                "inserted_sequence": "C"
                            }
                        },
                        "hgvs": "NC_000020.11:g.22685495="
                    },
                    {
                        "allele": {
                            "spdi": {
                                "seq_id": "NC_000020.11",
                                "position": 22685494,
                                "deleted_sequence": "C",
                                "inserted_sequence": "G"
                            }
                        },
                        "hgvs": "NC_000020.11:g.22685495C>G"
                    }
                ]
            }, */
	

	$var_list = $arr['primary_snapshot_data']['placements_with_allele'];
	
	//echo "RSID:".$arr['refsnp_id']."\n";
	$TRANSCRIPTS=array();
	foreach ($var_list as $x)
	{
		if ($x['placement_annot']['mol_type'] != 'rna')continue;

		$is_aln_opposite_orientation = $x['placement_annot']['is_aln_opposite_orientation'];
		$alleles = $x['alleles'];

		foreach ($alleles as $allele)
		{

			/*
                "alleles": [
                    {
                        "allele": {
                            "spdi": {
                                "seq_id": "NC_000020.11",
                                "position": 22685494,
                                "deleted_sequence": "C",
                                "inserted_sequence": "C"
                            }
                        },
                        "hgvs": "NC_000020.11:g.22685495="
                    },
                    {
                        "allele": {
                            "spdi": {
                                "seq_id": "NC_000020.11",
                                "position": 22685494,
                                "deleted_sequence": "C",
                                "inserted_sequence": "G"
                            }
                        },
                        "hgvs": "NC_000020.11:g.22685495C>G"
                    }
                ]
            }, */
			
			$pos =  ((int)$allele['allele']['spdi']['position'])+1;
			
			$ref = $allele['allele']['spdi']['deleted_sequence'];
			
			$alt = $allele['allele']['spdi']['inserted_sequence'];
			
			$ENTRY['ENTRY']['ref_all']=$ref;
			
			$seq_id = $allele['allele']['spdi']['seq_id'];
			
			$is_mismatch = ($ref!=$alt)?$x['placement_annot']['is_mismatch']:false;
			
			$seq_id_info=get_seq_version($seq_id);
			
			
			# print("adding transcript from rsid", seq_id, pos, ref, alt, is_aln_opposite_orientation, is_mismatch )
			# It will report the reference allele that was a mismatch, too... so G>G when it's supposed to be
			# A>A - no other info is provided for consequence or frequency so I don't see why I should include
			if ($alt != $ref)
			{
				
				$current_transcript =array(
					'seq_id'				=>$seq_id,
					'seq'					=>$seq_id_info[0],
					'version'				=>$seq_id_info[1],
					'inserted'				=>$alt,
					'deleted'				=>$ref,
					'position'				=>$pos,
					'mismatch'				=>$is_mismatch,
					'opposite_orientation'	=>$is_aln_opposite_orientation);

				tr_allele_strand_conversion($current_transcript,($is_aln_opposite_orientation)?'-':'+');
				$TRANSCRIPTS[$seq_id][]=$current_transcript;
				
			}
		}
		
	}/// END FOREACH VAR_LIST
	//print_r($TRANSCRIPTS);exit;
	
	//// GET FREQUENCIES:
	$allele_annots=&$arr['primary_snapshot_data']['allele_annotations'];
	$FREQUENCIES=array();
	foreach ($allele_annots as $allele_annot)
	{
		if (!isset($allele_annot['frequency']))continue;
	
		foreach ($allele_annot['frequency'] as $freq)
		{
			if (!isset($freq['observation']['inserted_sequence']))continue;
			
			$alt=$freq['observation']['inserted_sequence'];
			
			$FREQUENCIES[$alt][$freq['study_name'].'.'.$freq['study_version']]=array(
				'STUDY'		=>$freq['study_name'],
				'V'			=>$freq['study_version'],
				'ALLELE'	=>$freq['allele_count'],
				'TOT'		=>$freq['total_count']);
		}
	
		
	}
	
		
	$var_type =  $arr['primary_snapshot_data']['variant_type'];
	foreach ($var_list as $ref)
	{
		
		# we only care about the DNA alleles
		if ($ref['placement_annot']['seq_type'] != 'refseq_chromosome')					continue;
		
		# Sometimes chrMT will report both 38 and 37 in the same placement annot
		
		if (!isset($ref['placement_annot']['seq_id_traits_by_assembly']))continue;
		
		if (!isset($ref['placement_annot']['seq_id_traits_by_assembly'][0]))continue;
		
		// $reference=$ref['placement_annot']['seq_id_traits_by_assembly'][0]['assembly_name'];
		// if (condense_reference($reference) != 38)					continue;
		$reference=null;
		foreach ($ref['placement_annot']['seq_id_traits_by_assembly'] as &$ref_seq_assembly)
		{
			$reference_t = $ref_seq_assembly['assembly_name'];

			# We only care about the latest reference at the moment
			if (condense_reference($reference_t) != 38)					continue;
			
			$reference=$reference_t;
			break;
		}
		
		if ($reference==null)continue;

		foreach ($ref['alleles'] as $allele)
		{
			$allele_var_type = $var_type;
			
			$seq_id = $allele['allele']['spdi']['seq_id'];
			
			$pos = (int)($allele['allele']['spdi']['position'])+1;
			
			$ref = $allele['allele']['spdi']['deleted_sequence'];
			
			$alt = $allele['allele']['spdi']['inserted_sequence'];
			
			# create an object for this allele
			if ($ref == $alt)$allele_var_type = 'ref';
			
			$freq=array();
			
			if (isset($FREQUENCIES[$alt]))
			{
				$freq=$FREQUENCIES[$alt];
			}
			
			
			$cur_allele=array(
				'reference' => condense_reference($reference),
				'seq_id' => $seq_id,
				'inserted_sequence' => $alt,
				'deleted_sequence' => $ref,
				'frequencies' => $freq,
				'position' => $pos,
				'clinvar_id' => array(),
				'transcripts' => array(),
				'type' =>$allele_var_type
				
			);
			$ENTRY['allele'][$seq_id.'|'.$pos.'|'.$ref][$alt]=$cur_allele;
		}
	}



	$GENES=array();
	foreach ($allele_annots as $K_ALL=>$allele_annot)
	{
		if (!isset($allele_annot['assembly_annotation']))continue;
		

		/*
		"assembly_annotation": [
		{
			"seq_id": "NC_000020.11",
			"annotation_release": "Homo sapiens Annotation Release 110",
			"genes": [
				{
					"name": "long intergenic non-protein coding RNA 1747",
					"id": 105372566,
					"locus": "LINC01747",
					"is_pseudo": false,
					"orientation": "minus",
					"sequence_ontology": [
						{
							"name": "2KB_upstream_variant",
							"accession": "SO:0001636"
						}
					],
					"rnas": [
						{
							"id": "NR_146527.1",
							"sequence_ontology": [
								{
									"name": "upstream_transcript_variant",
									"accession": "SO:0001986"
								}
							]
						},
						{
							"id": "NR_146528.1",
							"sequence_ontology": [
								{
									"name": "upstream_transcript_variant",
									"accession": "SO:0001986"
								}
							]
						}
					]
				}, */

		$assembly_annots=&$allele_annot['assembly_annotation'];
		
		foreach ($assembly_annots as $assembly_annot)
		{
						
			$genes=&$assembly_annot['genes'];
			
			foreach ($genes as &$gene)
			{
				
				$gene_id=$gene['id'];
				$strand=$gene['orientation'];
				$gene_entry=array('gene_id'=>$gene_id,'strand'=>$strand,'Transcripts'=>get_protein_and_rnas($gene['rnas'],$var_list));
				
				foreach ($gene_entry['Transcripts'] as $tr_tag=>&$tr)
				{
					$FOUND=false;
					$tr_minus=$tr;
					if ($strand=='minus') tr_allele_strand_conversion($tr_minus,'-');
					//if ($tr['deleted']==$tr['inserted'])continue;
					if (isset($ENTRY['allele']))
					foreach($ENTRY['allele'] as $all_name=>&$all)
					{
						$tab=explode("|",$all_name);
					//	echo "ALL_NAME:".$tab[2]."\n";
						if ($tab[2]!=$tr_minus['deleted'])continue;
						foreach ($all as $all_ins=>&$info)
						{
							//echo "\tALL_INS:".$all_ins."\n";
							if ($all_ins!=$tr_minus['inserted'])continue;
							//echo $K_ALL."\t".$gene_id."\t".$tr_tag."\t".$all_name."\t".$all_ins."\t=>".$tr_tag."\n";
							$info['transcripts'][$tr_tag]=$tr;
							$FOUND=true;
						}
					}
					//if (!$FOUND){print_r($tr);print_r($tr_minus);die("Unable to find");}
				}
			}
		}		
	}
	
	
	//	print_r($ENTRY);exit;
	// print_r($ENTRY);
	// exit;
	return $ENTRY;
}
	/*
	We want to standardize the reference genome value to 37 or 38
	:param reference: the string representing ref genome
	:return:
	*/
function condense_reference($reference)
{

	if (is_array($reference))
	{
		foreach ($reference as $t)
		{
			if (strpos($t,'GRCh38')!==false)return 38;
			if (strpos($t,'GRCh37')!==false)return 37;
		} 
		return null;
	}
	else if (strpos($reference,'GRCh38')!==false)return 38;
	else if (strpos($reference,'GRCh37')!==false)return 37;
	else return $reference;
}






function get_protein_and_rnas($rnas,$var_list)
{
	$TRANSCRIPTS=array();
	
	foreach ($rnas as $rna)
	{
		$hgvs='';
		if (isset($rna['hgvs'])) $hgvs=$rna['hgvs'];
		# We don't want to process non-variant entries
		//if (strpos($hgvs,'=')!==false)continue;
		
	
		$prot_info=array();
		$seq_id='';
		$seq_onto_id='';
		$seq_onto_name='';
		# If a protein product was included in this change
		
		if (isset($rna['product_id']))
		{
			$prot_ins = '';
			$prot_pos = '';
			$prot_del = '';
			$protein_id = '';
			$protein_so_id = '';
			$protein_id=$rna['product_id'];

			if (isset($rna['protein']))
			{
				$protein_info=&$rna['protein'];
				//UN($protein_info);
				//print_r($rna);
				$IS_F=false;
				if 		(isset($protein_info['sequence_ontology']['accession']))   $protein_so_id=$protein_info['sequence_ontology']['accession'];
				else if (isset($protein_info['sequence_ontology'][0]['accession']))$protein_so_id=$protein_info['sequence_ontology'][0]['accession'];



				if (isset($protein_info['frameshift']))
				{
					$prot_pos=(int)($protein_info['frameshift']['position'])+1;
					$prot_ins='';
					$IS_F=true;
				}
				else if (isset($protein_info['variant']['frameshift']))
				{
					$protein_id=$protein_info['variant']['frameshift']['seq_id'];
					$prot_pos=(int)($protein_info['variant']['frameshift']['position'])+1;
					$prot_ins='';
					$IS_F=true;
				}
				else 
				{
					$prot_pos=(int)($protein_info['variant']['spdi']['position'])+1;
					$prot_ins=$protein_info['variant']['spdi']['inserted_sequence'];
					$prot_del=$protein_info['variant']['spdi']['deleted_sequence'];
				}
				$prot_info=array(
					'PROT_SEQ'=>$protein_id,
					'PROT_POS'=>$prot_pos,
					'PROT_INS'=>$prot_ins,
					'PROT_DEL'=>$prot_del,
					'PROT_SO_ID'=>$protein_so_id);
				
				// echo "##########\n";print_r($prot_info);
				// print_R($prot_info);exit;
			}
		}
		$seq_id=$rna['id'];
		if (isset($rna['sequence_ontology'][0]))
		{
			
			$seq_onto_id= $rna['sequence_ontology'][0]['accession'];
			$seq_onto_name= $rna['sequence_ontology'][0]['name'];
		}

		if (isset($rna['codon_aligned_transcript_change']))
		{
			
			$codon=$rna['codon_aligned_transcript_change'];
			$inserted=$codon['inserted_sequence'];
			$ref=$codon['deleted_sequence'];
			$pos=(int)($codon['position'])+1;
			$rule=1;
			# if it's not an indel/frameshift (note that pos will not be right on frameshift)
			if (strlen($ref)==strlen($inserted))
			{
				///Reported coding change variants will give the entire codon, we just want the location of the variant"""
				///If we are dealing with a substitution, Note: This will not do anything in the case of an indel coding change
				$allele_inserted = '';
				$start_found = False;
				$allele_deleted = '';
				$rule=4;
				if (strlen($ref) == 3 and strlen($inserted) == 3)
				{
					$rule=5;
					for ($x=0;$x<3;++$x)
					{
					
						if (substr($inserted,$x,1)== substr($ref,$x,1))continue;
						
							$allele_inserted .= substr($inserted,$x,1);
							$allele_deleted .= substr($ref,$x,1);
							if (!$start_found){
								
								$pos = $pos+$x;
								$rule="6".'_'.$x.'__'.$pos;
								$start_found = True;
							}
						
					}
					if ($allele_inserted !='' && $allele_deleted!='')
					{
						
						$inserted = $allele_inserted;
						$ref = $allele_deleted;
					}
				}
			}
			$ID=$seq_id.'|'.$pos.'|'.$ref.'|'.$inserted.'|'.$seq_onto_id;
			$ARR=array(
				'seq_id'		=>$seq_id,
				'position'		=>$pos,
				'deleted'		=>$ref,
				'inserted'		=>$inserted,
				'seq_onto_id'	=>$seq_onto_id,
				'prot_info'		=>$prot_info,
				'mismatch'		=>false,
				'rule'			=>$rule);
			$TRANSCRIPTS[$ID]=$ARR;
		}
		else 
		{
// we have a "noncoding" change
			if (strpos($seq_onto_name,'UTR')!==false)
			{
				
				foreach ($var_list as $K=>$x)
				{
					
					if ($x['placement_annot']['mol_type']!='rna')continue;
					$entry=$x['alleles'];
					foreach ($entry as $allele)
					{
						$allele_seq_id = $allele['allele']['spdi']['seq_id'];
						
						if ($seq_id!=$allele_seq_id)continue;
						
						$allele_pos=(int)($allele['allele']['spdi']['position'])+1 ;
						$allele_ref = $allele['allele']['spdi']['deleted_sequence'];
						$allele_alt = $allele['allele']['spdi']['inserted_sequence'];
						
						$ARR=array('seq_id'=>$seq_id,
									'position'=>$allele_pos,
									'deleted'=>$allele_ref,
									'inserted'=>$allele_alt,
									'seq_onto_id'=>$seq_onto_id,
									'prot_info'=>$prot_info,
									'mismatch'=>false,
									'rule'=>2);
						$ID=$seq_id.'|'.$allele_pos.'|'.$allele_ref.'|'.$allele_alt.'|'.$seq_onto_id;
						$TRANSCRIPTS[$ID]=$ARR;
						//if ($prot_info!=array()){print_r($TRANSCRIPTS);exit;}
						
					}
				}
			}
			else
			{
				$ID=$seq_id.'||||'.$seq_onto_id;
				$ARR=array(
					'seq_id'		=>$seq_id,
					'position'		=>'',
					'deleted'		=>'',
					'inserted'		=>'',
					'seq_onto_id'	=>$seq_onto_id,
					'prot_info'		=>$prot_info,
					'mismatch'		=>false,
					'rule'			=>3);
				$TRANSCRIPTS[$ID]=$ARR;
			}
			
		}
	}
	
	
	return $TRANSCRIPTS;
}




	
function pushToDB(&$RECORDS)
{
	echo "############## PUSH TO FILES\n";
	global $DB_STUDIES;
	global $FILE_ISSUE;
	global $FILES;
	global $DBIDS;
	global $STATS;
	global $JOB_ID;
 	
	$STATUS_FILES=array();
	$STR=array();
	$LIST_POS=array();
	$LIST_PROT_ALLELE=array();
	//print_r($RECORDS);exit;
	
	
	foreach ($FILES as $NAME=>&$FP)
	{
		$STATUS_FILES[$NAME]=false;
		$STR[$NAME]='';
	}
	
	
	
	foreach ($RECORDS as &$RECORD)
	{
	
		if ($RECORD['ENTRY']['DB_STATUS']=='TO_INS')
		{
			$RE=&$RECORD['ENTRY'];
			++$DBIDS['variant_entry'];
			$RE['variant_entry_id']=$DBIDS['variant_entry'];
			$STR['variant_entry'].=$DBIDS['variant_entry']."\t".$RE['rsid']."\t".$RE['date_created']."\t".$RE['date_updated']."\n";
			$STATUS_FILES['variant_entry']=true;
			$STATS['NEW_VARIANT_ENTRY']++;
			
			if (strlen($STR['variant_entry'])>10000){fputs($FILES['variant_entry'],$STR['variant_entry']);$STR['variant_entry']='';}
		}
		foreach ($RECORD['POSITION'] as $POS) 
		{
			if ($POS['DB_STATUS']=='TO_INS')$LIST_POS["'".$POS['ref_all']."'"]=-1;
			foreach ($POS['CHANGE'] as &$CHANGE)
			{
				if ($CHANGE['DB_STATUS']=='TO_INS' && $CHANGE['alt_all']!='')$LIST_POS["'".$CHANGE['alt_all']."'"]=-1;

				foreach ($CHANGE['TRANSCRIPT'] as &$TR)
				{
					if ($TR['DB_STATUS']=='TO_INS')
					{
						if ($TR['alt_all']!='')$LIST_POS["'".$TR['alt_all']."'"]=-1;
						if ($TR['ref_all']!='')$LIST_POS["'".$TR['ref_all']."'"]=-1;
					}
					if (count($TR['PROT'])>0)
					foreach($TR['PROT'] as &$TR_PR)
					{
						if ($TR_PR['DB_STATUS']!='TO_INS')continue;
						print_r($TR_PR);
						if ($TR_PR['ref_prot_all']!='')$LIST_PROT_ALLELE["'".$TR_PR['ref_prot_all']."'"]=-1;
						if ($TR_PR['comp_prot_all']!='')$LIST_PROT_ALLELE["'".$TR_PR['comp_prot_all']."'"]=-1;
						
					}
				}
			}
		}
		
		foreach ($RECORD['PMID'] as $PMID=>$STATUS)
		{
			if ($STATUS['DB_STATUS']=='TO_INS')
			{
				++$DBIDS['variant_pmid_map'];
				$STATS['NEW_VARIANT_PMID']++;
				$STATUS_FILES['variant_pmid_map']=true;
			$STR['variant_pmid_map'].=$DBIDS['variant_pmid_map']."\t".$RECORD['ENTRY']['variant_entry_id']."\t".$STATUS['pmid_entry_id']."\n";
			}
		}
	}
	

	$chunks=array_chunk(array_keys($LIST_POS),30000);
	foreach ($chunks as $chunk)
	{
		$res=runQuery("SELECT variant_allele_id, variant_seq 
		FROM variant_allele where variant_seq IN (".implode(",",$chunk).')');
		if ($res===false)														failProcess($JOB_ID."045",'Unable to get variant_allele');
		foreach ($res as $line)$LIST_POS["'".$line['variant_seq']."'"]=$line['variant_allele_id'];
	}
	foreach ($LIST_POS as $NAME=>$POS)
	{
		if ($POS!=-1)continue;
		$DBIDS['variant_allele']++;
		$LIST_POS[$NAME]=$DBIDS['variant_allele'];
		
			$STATS['NEW_VARIANT_ALLELE']++;
			$STATUS_FILES['variant_allele']=true;
		$STR['variant_allele'].=$DBIDS['variant_allele']."\t".substr($NAME,1,-1)."\n";

	}
	$chunks=array_chunk(array_keys($LIST_PROT_ALLELE),30000);
	foreach ($chunks as $chunk)
	{
		$res=runQuery("SELECT variant_prot_allele_id, variant_prot_seq 
		FROM variant_prot_allele where variant_prot_seq IN (".implode(",",$chunk).')');
		if ($res===false)														failProcess($JOB_ID."046",'Unable to get variant_prot_allele');
		foreach ($res as $line)$LIST_PROT_ALLELE["'".$line['variant_prot_seq']."'"]=$line['variant_prot_allele_id'];
	}
	foreach ($LIST_PROT_ALLELE as $NAME=>$POS)
	{
		if ($POS!=-1)continue;
		$DBIDS['variant_prot_allele']++;
		$LIST_PROT_ALLELE[$NAME]=$DBIDS['variant_prot_allele'];
		
			$STATS['NEW_VARIANT_PROT_ALLELE']++;
			$STATUS_FILES['variant_prot_allele']=true;
		$STR['variant_prot_allele'].=$DBIDS['variant_prot_allele']."\t".substr($NAME,1,-1)."\n";

	}
//print_r($RECORDS);
	foreach ($RECORDS as &$RECORD)
	{
	
		foreach ($RECORD['POSITION'] as &$POS) 
		{
			if ($POS['DB_STATUS']=='TO_INS')
			{
				$DBIDS['variant_position']++;
				
				$POS['variant_position_id']=$DBIDS['variant_position'];
				$REF_ALL_ID=null;
				if (!isset($LIST_POS["'".$POS['ref_all']."'"])){
					$STATS['MISSING_ALLELE_NAME']++;
					fputs($FILE_ISSUE,"MISSING_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$POS['ref_all']."\n");
					continue;
				}
				$STATUS_FILES['variant_position']=true;
				$STATS['NEW_VARIANT_POSITION']++;
				$REF_ALL_ID=$LIST_POS["'".$POS['ref_all']."'"];
				$STR['variant_position'].=$DBIDS['variant_position']."\t".$RECORD['ENTRY']['variant_entry_id']."\t".$REF_ALL_ID."\t".$POS['chr_seq_pos_id']."\n";

			}

			foreach ($POS['CHANGE'] as &$CHANGE)
			{
				if ($CHANGE['DB_STATUS']=='TO_INS')
				{
					$DBIDS['variant_change']++;
					$CHANGE['variant_change_id']=$DBIDS['variant_change'];
					$ALT_ALL_ID='NULL';
					
					//echo "IN\n";
					if ($CHANGE['alt_all']!=''){
					if (!isset($LIST_POS["'".$CHANGE['alt_all']."'"])){
						$STATS['MISSING_ALLELE_NAME']++;
						fputs($FILE_ISSUE,"MISSING_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$POS['ref_all']."\n");
						continue;
					}else 						$ALT_ALL_ID=$LIST_POS["'".$CHANGE['alt_all']."'"];}
					$STATS['NEW_VARIANT_CHANGE']++;
					$STATUS_FILES['variant_change']=true;
					//echo $DBIDS['variant_change']."\t".$POS['variant_position_id']."\t".$ALT_ALL_ID."\t".$CHANGE['variant_type_id']."\n";
					//(variant_change_id  ,variant_position_id,alt_all,variant_type_id)',
					$STR['variant_change'].=$DBIDS['variant_change']."\t".$POS['variant_position_id']."\t".$ALT_ALL_ID."\t".$CHANGE['variant_type_id']."\n";
					if (strlen($STR['variant_change'])>10000){fputs($FILES['variant_change'],$STR['variant_change']);$STR['variant_change']='';}
	
				}
				if (isset($CHANGE['FREQUENCY']))
				foreach ($CHANGE['FREQUENCY'] as &$FREQ)
				{
					if ($FREQ['DB_STATUS']!='TO_INS')continue;
					$DBIDS['variant_frequency']++;	
					$STATS['NEW_VARIANT_FREQUENCY']++;
					$STATUS_FILES['variant_frequency']=true;
					//echo $DBIDS['variant_change']."\t".$POS['variant_position_id']."\t".$ALT_ALL_ID."\t".$CHANGE['variant_type_id']."\n";
					//(variant_change_id  ,variant_position_id,alt_all,variant_type_id)',
					$STR['variant_frequency'].=$DBIDS['variant_frequency']."\t".$CHANGE['variant_change_id']."\t".$FREQ['variant_freq_study_id']."\t".$FREQ['ref_count']."\t".$FREQ['alt_count']."\n";
					if (strlen($STR['variant_frequency'])>10000){fputs($FILES['variant_frequency'],$STR['variant_frequency']);$STR['variant_frequency']='';}
				}
				if (isset($CHANGE['TRANSCRIPT']))
				foreach ($CHANGE['TRANSCRIPT'] as &$TR)
				{
					if ($TR['DB_STATUS']=='TO_INS')
					{
					
						$ALT_ALL_ID='NULL';
						if ($TR['alt_all']!=''){
							if (!isset($LIST_POS["'".$TR['alt_all']."'"])){
								$STATS['MISSING_TR_ALLELE_NAME']++;
								fputs($FILE_ISSUE,"MISSING_TR_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$TR['alt_all']."\n");
								continue;
							}else 						$ALT_ALL_ID=$LIST_POS["'".$TR['alt_all']."'"];}
						$REF_ALL_ID='NULL';
						if ($TR['ref_all']!=''){
							if (!isset($LIST_POS["'".$TR['ref_all']."'"])){
								$STATS['MISSING_TR_ALLELE_NAME']++;
								fputs($FILE_ISSUE,"MISSING_TR_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$TR['ref_all']."\n");
								continue;
							}else 						$REF_ALL_ID=$LIST_POS["'".$TR['ref_all']."'"];}
							$DBIDS['variant_transcript_map']++;	
							$TR['variant_transcript_id']=$DBIDS['variant_transcript_map'];
							$STATS['NEW_VARIANT_TRANSCRIPT']++;
							$STATUS_FILES['variant_transcript_map']=true;
						//'variant_transcript_map'=>'(variant_transcript_id,variant_change_id,transcript_id  ,transcript_pos_id ,  so_entry_id,tr_ref_all,tr_alt_all)'
						
						$STR['variant_transcript_map'].=
							$DBIDS['variant_transcript_map']."\t".$CHANGE['variant_change_id']."\t".$TR['transcript_id']."\t".$TR['transcript_pos_id']."\t".$TR['so_entry_id'].
							"\t".$REF_ALL_ID."\t".$ALT_ALL_ID."\n";
						if (strlen($STR['variant_transcript_map'])>10000){fputs($FILES['variant_transcript_map'],$STR['variant_transcript_map']);$STR['variant_transcript_map']='';}
					}
					if (count($TR['PROT'])==0)continue;
					foreach ($TR['PROT'] as &$TR_PR)
					{

						/*
						'DB_STATUS'=>'TO_INS',
		'variant_protein_id'=>-1, 
		'variant_transcript_id'=>-1,
		'prot_seq_id'=>$PROT_SEQ_ID,
		'iso_id'=>$R_PROT['PROT_SEQ'],
		'ref_prot_all'=>$R_PROT['PROT_DEL'], 
		'comp_prot_all'=>$R_PROT['PROT_INS'],
		'so_entry_id'=>$SO_ENTRY_ID, 
		'so_id'=>$R_PROT['PROT_SO_ID'],
		'seq_pos'=>$R_PROT['PROT_POS'], 
		'seq_letter'=>$R_PROT['PROT_DEL'], 
		'prot_seq_pos_id'=>$PROT_SEQ_POS_ID);
		 */		
						if ($TR_PR['DB_STATUS']=='TO_INS')
						{
						$ALT_ALL_ID='NULL';
						if ($TR_PR['comp_prot_all']!=''){
							if (!isset($LIST_PROT_ALLELE["'".$TR_PR['comp_prot_all']."'"])){
								$STATS['MISSING_TR_ALLELE_NAME']++;
								fputs($FILE_ISSUE,"MISSING_TR_PR_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$TR_PR['comp_prot_all']."\n");
								continue;
							}else 						$ALT_ALL_ID=$LIST_PROT_ALLELE["'".$TR_PR['comp_prot_all']."'"];}
						$REF_ALL_ID='NULL';
						if ($TR_PR['ref_prot_all']!=''){
							if (!isset($LIST_PROT_ALLELE["'".$TR_PR['ref_prot_all']."'"])){
								$STATS['MISSING_TR_ALLELE_NAME']++;
								fputs($FILE_ISSUE,"MISSING_TR_PR_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$TR_PR['comp_prot_all']."\n");
								continue;
							}else 						$REF_ALL_ID=$LIST_PROT_ALLELE["'".$TR_PR['ref_prot_all']."'"];}
							$DBIDS['variant_protein_map']++;	
							$STATS['NEW_VARIANT_PROTEIN']++;
							$STATUS_FILES['variant_protein_map']=true;
							if ($TR_PR['prot_seq_pos_id']==-1)$TR_PR['prot_seq_pos_id']='NULL';
						//(variant_protein_id,variant_transcript_id,prot_seq_id,prot_Seq_pos_id,so_entry_id,prot_ref_all,prot_alt_all)'
						$STR['variant_protein_map'].=
							$DBIDS['variant_protein_map']."\t".$TR['variant_transcript_id']."\t".$TR_PR['prot_seq_id']."\t".$TR_PR['prot_seq_pos_id']."\t".$TR_PR['so_entry_id'].
							"\t".$REF_ALL_ID."\t".$ALT_ALL_ID."\n";
						if (strlen($STR['variant_protein_map'])>10000){fputs($FILES['variant_protein_map'],$STR['variant_protein_map']);$STR['variant_protein_map']='';}
						
						}
					}
				}
				
			}

		}
	}
	

	foreach ($FILES as $NAME=>&$FP)fputs($FP,$STR[$NAME]);
	echo "############## PUSH TO DB\n";
	global $COL_ORDER;
	global $FILES;
	global $GLB_VAR;
	global $DB_INFO;
	foreach ($COL_ORDER as $NAME=>$CTL)
		{

		//	if (in_array($NAME,$TO_FILTER))continue;
		
		if (!$STATUS_FILES[$NAME])continue;
		
			addLog("inserting ".$NAME." records");
			$res=array();
			fclose($FILES[$NAME]);
			$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			//echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
			$FILES[$NAME]=fopen($NAME.'.csv','w');
			if ($return_code !=0 )failProcess($JOB_ID."047",'Unable to insert sm_source'); 
		}
		$res=runQuery("select * FROM variant_freq_study"); 
		$DB_STUDIES=array();
		foreach ($res as $line)
		{
			//$DB_STUDIES[$line['short_name']]=$line['variant_freq_study_id'];
			
			$DB_STUDIES[$line['variant_freq_study_name']]=$line['variant_freq_study_id'];
		}
		

}






function get_seq_version($seq)
{
	$pos=strpos($seq,'.');
	if ($pos===false)return array($seq,'');
	return array(substr($seq,0,$pos),substr($seq,$pos+1));
}





/*
	we want to be able to match up to the DNA variant which is pos strand by default
	:param strand: whether it's on the plus or minus strand
	:param reverse: whether we have already reversed and need to go back
	*/



function tr_allele_strand_conversion(&$tr, $strand, $reverse=False)
{    
	if ($tr['inserted'] && $tr['deleted'])
	{
		#  print(self.deleted, self.inserted, strand, reverse)
		if ($strand == '-' || $reverse)
		{
			$tr['inserted']= genReverse($tr['inserted']);
			$tr['deleted'] = genReverse($tr['deleted']);
		}
	}
}





function genReverse($STR)
{
	$REV='';
	$REV_STR=strrev($STR);
	for($I=0;$I<strlen($STR);++$I)
	{
		switch ($REV_STR[$I])
		{
		case 'A':$REV.='T';break;
		case 'T':$REV.='A';break;
		case 'C':$REV.='G';break;
		case 'G':$REV.='C';break;
		}
	}
	return $REV;
}

?>
