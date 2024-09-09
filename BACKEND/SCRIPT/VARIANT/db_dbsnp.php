<?php

error_reporting(E_ALL);
ini_set('memory_limit','1200M');
/**
 SCRIPT NAME: db_dbsnp
 PURPOSE:     Push all data from dbsnp to the database
 
*/

/// Job name - Do not change
$JOB_NAME='db_dbsnp';

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


addLog("Preparation step");
	/// Get parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_dbsnp')];

	/// Setting up directory path:
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; 	if (!is_dir($U_DIR)) 					failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/DBSNP/';   		if (!is_dir($U_DIR))					failProcess($JOB_ID."002",'Unable to find  '.$U_DIR);
	$U_DIR.='/'.$CK_INFO['TIME']['DEV_DIR'].'/';   	if (!is_dir($U_DIR))				 	failProcess($JOB_ID."003",'Unable to find '.$U_DIR);
	if (!chdir($U_DIR))				 														failProcess($JOB_ID."004",'Unable to access '.$U_DIR);


	/// This array use the tables we are going to insert into as key and the max primary key value as value.
	$DBIDS=array('variant_allele'=>-1,
				'variant_entry'=>-1,
				'variant_position'=>-1,
				'variant_change'=>-1,
				'variant_frequency'=>-1,
				'variant_pmid_map'=>-1,
				'variant_transcript_map'=>-1,
				'variant_protein_map'=>-1,
				'variant_prot_allele'=>-1);
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$res=array();
		$TBL_N=$TBL;
		// Those two tables have a different name in the database
		if ($TBL=='variant_transcript_map')$TBL_N='variant_transcript';
		if ($TBL=='variant_protein_map')$TBL_N='variant_protein';
		$res=runQuery('SELECT MAX('.$TBL_N.'_id) co FROM '.$TBL);
		if ($res===false)																failProcess($JOB_ID."005",'Unable to get Max ID for '.$TBL);
	
		$DBIDS[$TBL]=($res[0]['co']=='')?0:$res[0]['co'];
	}


	/// Each table we are going to insert into and the column order
	$COL_ORDER=array('variant_allele'		=>'(variant_allele_id,variant_seq)',
					'variant_entry'			=>'(variant_entry_id,rsid,date_created,date_updated)',
					'variant_position'		=>'(variant_position_id,variant_entry_id,ref_all,chr_seq_pos_id )',	
					'variant_change'		=>'(variant_change_id  ,variant_position_id,alt_all,variant_type_id)',
					'variant_frequency'		=>'(variant_frequency_id,variant_change_id ,variant_freq_study_id,ref_count,alt_count)',
					'variant_pmid_map'		=>'(variant_pmid_map_id,variant_entry_id   ,pmid_entry_id)',
					'variant_transcript_map'=>'(variant_transcript_id,variant_change_id,transcript_id  ,transcript_pos_id ,  so_entry_id,tr_ref_all,tr_alt_all)',
					'variant_prot_allele'	=>'(variant_prot_allele_id, variant_prot_seq)',
					'variant_protein_map'	=>'(variant_protein_id,variant_transcript_id  ,prot_seq_id,prot_seq_pos_id, so_entry_id, prot_ref_all,prot_alt_all)',
	);

	

	/// Opening output files for each table
	$FILES=array();
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$FILES[$TBL]=fopen($TBL.'.csv','w');
		if (!$FILES[$TBL])																failProcess($JOB_ID."006",'Unable to open '.$TBL.'.csv');
	}
	
	/// Getting variant studies
	$res=runQuery("SELECT * FROM variant_freq_study"); if ($res===false)				failProcess($JOB_ID."007",'Unable to get existing studies');
	$DB_STUDIES=array();
	foreach ($res as $line)	
	{
		$DB_STUDIES[$line['variant_freq_study_name']]=$line['variant_freq_study_id'];
	}

	/// Checking master script:
	$ALL_FILE=$U_DIR.'/SCRIPTS/all.sh';
	if (!is_file($ALL_FILE))															failProcess($JOB_ID."008",'Unable to find master job file at '.$ALL_FILE);
	$N_JOBS=getLineCount($ALL_FILE);
	


	$STATS=array(
		'ENTRY_PROCESSED'=>0,		'ENTRY_JSON_ISSUE'=>0,	'TEST_ENTRY'=>0,
		'EXISTING_ENTRY'=>0,		'NEW_VARIANT_ENTRY'=>0,	'UPDATE_CREATION_DATE'=>0,
		'UPDATE_UPDATE_DATE'=>0,	'CHANGE_TEST'=>0,		'DEL_CHANGE'=>0,
		'EXISTING_CHANGE'=>0,		'NEW_VARIANT_CHANGE'=>0,'PMIDS'=>0,
		'PMID_NOT_IN_DB'=>0,		'DEL_PMID'=>0,			'NEW_VARIANT_PMID'=>0,
		'EXISTING_PMID'=>0,			'DEL_ALLELE'=>0,		'EXISTING_ALLELE'=>0,
		'ALLELE_TEST'=>0,			'ALLELE_NO_DNA'=>0,		'ALLELE_DIFF_FROM_DNA'=>0,
		'NEW_VARIANT_POSITION'=>0,	'NEW_VARIANT_ALLELE'=>0,'MISSING_ALLELE_NAME'=>0,
		'TEST_FREQUENCY'=>0,		'EXISTING_FREQUENCY'=>0,'TEST_TRANSCRIPT'=>0,
		'EXISTING_TRANSCRIPT'=>0);
		

// for ($I=0;$I<$N_JOBS;++$I)
// {
	
// 	$time=microtime_float();
	
// 	/// All errors will be saved here:
// 	$FILE_ISSUE=fopen('SNP_ISSUE_'.$I.'_db.csv','w');if (!$FILE_ISSUE)					failProcess($JOB_ID."009",'Unable to open error file');

// 	addLog("PROCESS ".$I."\n");
// 	$fp=fopen('DATA/RESULTS_'.$I,'r');if (!$fp)											failProcess($JOB_ID."010",'Unable to open RESULTS_'.$I);
// 	$RECORDS=array();$N=0;
// 	while(!feof($fp))
// 	{	
// 		/// Each record is on one line, so it can be pretty long lines
// 		$line=stream_get_line($fp,100000000,"\n");
// 		if ($line=='')continue;
// 		/// Decode json string and put it in an array for batch processing
// 		$ENTRY=json_decode($line,true);
		
// 		/// Cannot decode json string - issue with the json string
// 		if ($ENTRY===false)
// 		{
// 			$STATS['ENTRY_JSON_ISSUE']++;
// 			continue;
// 		}
// 		$RECORDS[]=$ENTRY;
		
// 		///
// 		if (count($RECORDS)<20000)continue;
		
// 		++$N;
// 		echo $I."\tPUSH TO DB\t".(20000*($N-1))."\t".(20000*$N)."\n";
		
// 		pushToDB($RECORDS);
		
// 		///Clean up the batch
// 		$RECORDS=null;
// 		$RECORDS=array();
			
			
// 	}
// 	fclose($fp);

// 	/// Push the last batch
// 	pushToDB($RECORDS);
// 	print_r($STATS);
// 	echo "TIME\t".round(microtime_float()-$time,3)."\n";
	
// }

/// Sometimes the database time out, so we reconnect to the db before updating the status
connectDB();

successProcess();


		
			
	







function pushToDB(&$RECORDS)
{
	global $DB_STUDIES;
	addLog( "############## PUSH TO FILES\n");
	global $FILE_ISSUE;
	global $FILES;
	global $DBIDS;
	global $STATS;
	global $JOB_ID;
	global $COL_ORDER;
	global $FILES;
	global $GLB_VAR;
	global $DB_INFO;
 	$STATUS_FILES=array();
	$STR=array();

	/// $LIST_POS is a temporary mapping table listing the different nuclotide alleles
	/// Key is the allele name, value is the allele id
	$LIST_POS=array();
	/// $LIST_PROT_ALLELE is a temporary mapping table listing the different amino-acid alleles
	/// Key is the allele name, value is the allele id
	$LIST_PROT_ALLELE=array();
	
	/// To speed up the process, we will create for each table a string containing all the new entries.
	/// When that string is long enough, it will be push to the file, saving I/O time.
	/// In addition, we don't want to run psql on an empty file, that's a waste of time, so we use STATUS_FILE to tell us if we need to insert a file or not.
	foreach ($FILES as $NAME=>&$FP)
	{
		$STATUS_FILES[$NAME]=false;
		$STR[$NAME]='';
	}

		
	/// Looking at each record
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
			
			/// String is too long, push to file:
			if (strlen($STR['variant_entry'])>10000)
			{
				fputs($FILES['variant_entry'],$STR['variant_entry']);
				$STR['variant_entry']='';
			}
		}


		/// Looking for variant positions:
		if (isset($RECORD['POSITION']))
		foreach ($RECORD['POSITION'] as $POS) 
		{
			//// Listing the ref allele:
			if ($POS['DB_STATUS']=='TO_INS')$LIST_POS["'".$POS['ref_all']."'"]=-1;
			
			foreach ($POS['CHANGE'] as &$CHANGE)
			{
				/// Listing the alt allele:
				if ($CHANGE['DB_STATUS']=='TO_INS' && $CHANGE['alt_all']!='')$LIST_POS["'".$CHANGE['alt_all']."'"]=-1;

				foreach ($CHANGE['TRANSCRIPT'] as &$TR)
				{
					if ($TR['DB_STATUS']=='TO_INS')
					{
						/// Listing the ref allele:
						if ($TR['alt_all']!='')$LIST_POS["'".$TR['alt_all']."'"]=-1;
						/// Listing the alt allele:
						if ($TR['ref_all']!='')$LIST_POS["'".$TR['ref_all']."'"]=-1;
					}
					if (count($TR['PROT'])>0)
					foreach($TR['PROT'] as &$TR_PR)
					{
						if ($TR_PR['DB_STATUS']!='TO_INS')continue;
						/// Listing the ref protein allele:
						if ($TR_PR['ref_prot_all']!='')	$LIST_PROT_ALLELE["'".$TR_PR['ref_prot_all']."'"]=-1;
						/// Listing the comp protein allele:
						if ($TR_PR['comp_prot_all']!='')$LIST_PROT_ALLELE["'".$TR_PR['comp_prot_all']."'"]=-1;
						
					}
				}
			}
		}
		
		foreach ($RECORD['PMID'] as $PMID=>$STATUS)
		{
			if ($STATUS['DB_STATUS']=='TO_INS')
			{
				///Increase the id
				++$DBIDS['variant_pmid_map'];
				
				///Add statistics
				$STATS['NEW_VARIANT_PMID']++;
				
				/// Update status file to notify new data is in that file
				$STATUS_FILES['variant_pmid_map']=true;
				
				/// Add the new data to the string
				$STR['variant_pmid_map'].=$DBIDS['variant_pmid_map']."\t".$RECORD['ENTRY']['variant_entry_id']."\t".$STATUS['pmid_entry_id']."\n";
			}
		}
	}
	
	if ($LIST_POS!=array())
	{
		/// Getting all variant allele
		$chunks=array_chunk(array_keys($LIST_POS),30000);
		foreach ($chunks as $chunk)
		{
			$res=runQuery("SELECT variant_allele_id, variant_seq 
							FROM variant_allele 
							where variant_seq IN (".implode(",",$chunk).')');
			
			if ($res===false)														failProcess($JOB_ID."A01",'Unable to get variant_allele');
			
			foreach ($res as $line)
			{
				$LIST_POS["'".$line['variant_seq']."'"]=$line['variant_allele_id'];
			}
		}

		/// Adding the new variant alleles:
		foreach ($LIST_POS as $NAME=>$POS)
		{
			if ($POS!=-1)continue;

			/// Increase the id
			$DBIDS['variant_allele']++;
			
			/// Update the mapping table
			$LIST_POS[$NAME]=$DBIDS['variant_allele'];
			
			/// Add statistics
			$STATS['NEW_VARIANT_ALLELE']++;
			
			/// Update the status file to notify new data is in that file
			$STATUS_FILES['variant_allele']=true;

			/// Add the new data to the string
			$STR['variant_allele'].=$DBIDS['variant_allele']."\t".substr($NAME,1,-1)."\n";

		}
	}
	if ($LIST_PROT_ALLELE!=array())
	{
		/// Getting all variant protein allele
		$chunks=array_chunk(array_keys($LIST_PROT_ALLELE),30000);

		/// Searching for the protein allele in the database
		foreach ($chunks as &$chunk)
		{
			$res=runQuery("SELECT variant_prot_allele_id, variant_prot_seq 
					FROM variant_prot_allele 
					where variant_prot_seq IN (".implode(",",$chunk).')');
			if ($res===false)														failProcess($JOB_ID."A02",'Unable to get variant_prot_seq');
			
			foreach ($res as $line)
			{
				$LIST_PROT_ALLELE["'".$line['variant_prot_seq']."'"]=$line['variant_prot_allele_id'];
			}
		}

		/// Adding the new variant protein alleles:
		foreach ($LIST_PROT_ALLELE as $NAME=>$POS)
		{
			if ($POS!=-1)continue;

			/// Increase the id
			$DBIDS['variant_prot_allele']++;

			/// Update the mapping table
			$LIST_PROT_ALLELE[$NAME]=$DBIDS['variant_prot_allele'];
			
			/// Add statistics
			$STATS['NEW_VARIANT_PROT_ALLELE']++;

			/// Update the status file to notify new data is in that file
			$STATUS_FILES['variant_prot_allele']=true;

			/// Add the new data to the string
			$STR['variant_prot_allele'].=$DBIDS['variant_prot_allele']."\t".substr($NAME,1,-1)."\n";
			echo 	"NEW VARIANT PROT ALLELE:".	$STR['variant_prot_allele']."\n";

		}
	}

	foreach ($RECORDS as &$RECORD)
	{
	
		if (isset($RECORD['POSITION']))
		foreach ($RECORD['POSITION'] as &$POS) 
		{
			/// New position:
			if ($POS['DB_STATUS']=='TO_INS')
			{
				/// Increase the id
				$DBIDS['variant_position']++;
				
				/// Update the position id
				$POS['variant_position_id']=$DBIDS['variant_position'];

				$REF_ALL_ID=null;

				/// Find the ref allele id, which based on previous code should be in the mapping table
				if (!isset($LIST_POS["'".$POS['ref_all']."'"]))
				{
					$STATS['MISSING_ALLELE_NAME']++;
					fputs($FILE_ISSUE,"MISSING_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$POS['ref_all']."\n");
					continue;
				}

				/// Ensure we find the primary key for chromosome position in the db
				if ($POS['chr_seq_pos_id']==-1)
				{
					$STATS['MISSING_CHR_SEQ_POS']++;
					fputs($FILE_ISSUE,"MISSING_CHR_SEQ_POS\t".$RECORD['ENTRY']['rsid']."\t".$POS['ref_all']."\n");
					continue;
				}
				/// Update the status file to notify new data is in that file
				$STATUS_FILES['variant_position']=true;

				/// Add statistics
				$STATS['NEW_VARIANT_POSITION']++;

				/// Assign the ref allele id
				$REF_ALL_ID=$LIST_POS["'".$POS['ref_all']."'"];

				/// Add the new data to the string
				$STR['variant_position'].=$DBIDS['variant_position']."\t".$RECORD['ENTRY']['variant_entry_id']."\t".$REF_ALL_ID."\t".$POS['chr_seq_pos_id']."\n";

			}

			/// Looking at each change
			foreach ($POS['CHANGE'] as &$CHANGE)
			{
				/// New change
				if ($CHANGE['DB_STATUS']=='TO_INS')
				{
					/// Increase the id
					$DBIDS['variant_change']++;

					/// Assign the change id
					$CHANGE['variant_change_id']=$DBIDS['variant_change'];

					/// Looking at the allele:
					/// By default, the allele id is NULL, in the case it's a deletion for instance
					$ALT_ALL_ID='NULL';
					
					/// But if it's not a deletion, we need to find the allele id
					if ($CHANGE['alt_all']!='')
					{
						/// Ensure we find the primary key for the allele in the db, based on previous code should be in the mapping table
						if (!isset($LIST_POS["'".$CHANGE['alt_all']."'"]))
						{
							$STATS['MISSING_ALLELE_NAME']++;
							fputs($FILE_ISSUE,"MISSING_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$POS['ref_all']."\n");
							continue;
						}else 						$ALT_ALL_ID=$LIST_POS["'".$CHANGE['alt_all']."'"];
					}

					/// Add statistics
					$STATS['NEW_VARIANT_CHANGE']++;
					
					/// Update the status file to notify new data is in that file
					$STATUS_FILES['variant_change']=true;
					
					/// Add the new data to the string
					$STR['variant_change'].=$DBIDS['variant_change']."\t".$POS['variant_position_id']."\t".$ALT_ALL_ID."\t".$CHANGE['variant_type_id']."\n";
					

					/// String is too long, push to file:
					if (strlen($STR['variant_change'])>10000)
					{
						fputs($FILES['variant_change'],$STR['variant_change']);
						/// Reset the string
						$STR['variant_change']='';
					}
	
				}



				/// Look at allele frequency:
				if (isset($CHANGE['FREQUENCY']))
				foreach ($CHANGE['FREQUENCY'] as &$FREQ)
				{
					/// Not new -> continue
					if ($FREQ['DB_STATUS']!='TO_INS')continue;
					

					/// Increase the id
					$DBIDS['variant_frequency']++;	
					
					/// Add statistics
					$STATS['NEW_VARIANT_FREQUENCY']++;
					

					/// Update the status file to notify new data is in that file
					$STATUS_FILES['variant_frequency']=true;
					
					/// Add the new data to the string
					$STR['variant_frequency'].=$DBIDS['variant_frequency']."\t".$CHANGE['variant_change_id']."\t".$FREQ['variant_freq_study_id']."\t".$FREQ['ref_count']."\t".$FREQ['alt_count']."\n";
					
					/// String is too long, push to file:
					if (strlen($STR['variant_frequency'])>10000)
					{

						fputs($FILES['variant_frequency'],$STR['variant_frequency']);
						
						/// Reset the string
						$STR['variant_frequency']='';
					}
				}




				/// Look at the impact on transcript:
				if (isset($CHANGE['TRANSCRIPT']))
				foreach ($CHANGE['TRANSCRIPT'] as &$TR)
				{

					
					if ($TR['DB_STATUS']=='TO_INS')
					{
						/// Default value for the allele id
						$ALT_ALL_ID='NULL';

						/// Now we need to find the allele id
						if ($TR['alt_all']!='')
						{
							/// Ensure we find the primary key for the allele in the db, based on previous code should be in the mapping table
							if (!isset($LIST_POS["'".$TR['alt_all']."'"]))
							{
								$STATS['MISSING_TR_ALLELE_NAME']++;
								fputs($FILE_ISSUE,"MISSING_TR_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$TR['alt_all']."\n");
								continue;
							}else 						
							{
								$ALT_ALL_ID=$LIST_POS["'".$TR['alt_all']."'"];
							}
						}
						/// Do the same for the ref allele
						$REF_ALL_ID='NULL';
						if ($TR['ref_all']!='')
						{
							if (!isset($LIST_POS["'".$TR['ref_all']."'"]))
							{
								$STATS['MISSING_TR_ALLELE_NAME']++;
								fputs($FILE_ISSUE,"MISSING_TR_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$TR['ref_all']."\n");
								continue;
							}else 						
							{
								$REF_ALL_ID=$LIST_POS["'".$TR['ref_all']."'"];
							}
						}
						
						/// Increase the id
						$DBIDS['variant_transcript_map']++;	
						
						///Assign the id
						$TR['variant_transcript_id']=$DBIDS['variant_transcript_map'];
						
						/// Add statistics
						$STATS['NEW_VARIANT_TRANSCRIPT']++;
						
						/// Update the status file to notify new data is in that file
						$STATUS_FILES['variant_transcript_map']=true;
						

						/// If we didn't find the so_entry_id, we just set it to null
						if ($TR['so_entry_id']==-1)$TR['so_entry_id']='NULL';
						

						$STR['variant_transcript_map'].=
							$DBIDS['variant_transcript_map']."\t".
							$CHANGE['variant_change_id']."\t".
							$TR['transcript_id']."\t".
							$TR['transcript_pos_id']."\t".
							$TR['so_entry_id']."\t".
							$REF_ALL_ID."\t".
							$ALT_ALL_ID."\n";

						/// String is too long, push to file:
						if (strlen($STR['variant_transcript_map'])>10000)
						{
							fputs($FILES['variant_transcript_map'],$STR['variant_transcript_map']);
							/// Reset the string
							$STR['variant_transcript_map']='';
						}
					}
					/// No protein change associated to that change -> continue
					if (count($TR['PROT'])==0)continue;

					foreach ($TR['PROT'] as &$TR_PR)
					{

						/// Not new -> continue
						if ($TR_PR['DB_STATUS']!='TO_INS')continue;
						
						/// Default value for the allele id
						$ALT_ALL_ID='NULL';

						///If there is a protein change, we need to find the allele id
						if ($TR_PR['comp_prot_all']!='')
						{
							/// Ensure we find the primary key for the allele in the db, based on previous code should be in the mapping table
							if (!isset($LIST_PROT_ALLELE["'".$TR_PR['comp_prot_all']."'"]))
							{
								$STATS['MISSING_TR_ALLELE_NAME']++;
								fputs($FILE_ISSUE,"MISSING_TR_PR_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$TR_PR['comp_prot_all']."\n");
								continue;
							}else 						
							{
								$ALT_ALL_ID=$LIST_PROT_ALLELE["'".$TR_PR['comp_prot_all']."'"];
							}
						}


						$REF_ALL_ID='NULL';
						
						if ($TR_PR['ref_prot_all']!='')
						{
							if (!isset($LIST_PROT_ALLELE["'".$TR_PR['ref_prot_all']."'"]))
							{
								$STATS['MISSING_TR_ALLELE_NAME']++;
								fputs($FILE_ISSUE,"MISSING_TR_PR_ALLELE_NAME\t".$RECORD['ENTRY']['rsid']."\t".$TR_PR['comp_prot_all']."\n");
								continue;
							}else 						
							{
								$REF_ALL_ID=$LIST_PROT_ALLELE["'".$TR_PR['ref_prot_all']."'"];
							}
						}

						/// Increase the id
						$DBIDS['variant_protein_map']++;	
						
						///Assign the id
						$STATS['NEW_VARIANT_PROTEIN']++;
						
						/// Update the status file to notify new data is in that file
						$STATUS_FILES['variant_protein_map']=true;
						
						/// If we didn't find the protein sequence position in the database, we just set it to null
						if ($TR_PR['prot_seq_pos_id']==-1)$TR_PR['prot_seq_pos_id']='NULL';
						
						/// If we didn't find the so_entry_id, we just set it to null
						if ($TR_PR['so_entry_id']==-1)$TR_PR['so_entry_id']='NULL';
						
						//(variant_protein_id,variant_transcript_id,prot_seq_id,prot_Seq_pos_id,so_entry_id,prot_ref_all,prot_alt_all)'
						
						$STR['variant_protein_map'].=
							$DBIDS['variant_protein_map']."\t".
							$TR['variant_transcript_id']."\t".
							$TR_PR['prot_seq_id']."\t".
							$TR_PR['prot_seq_pos_id']."\t".
							$TR_PR['so_entry_id']."\t".$REF_ALL_ID."\t".
							$ALT_ALL_ID."\n";
						
						if (strlen($STR['variant_protein_map'])>10000)
						{
							fputs($FILES['variant_protein_map'],$STR['variant_protein_map']);
							/// Reset the string
							$STR['variant_protein_map']='';
						}
						
						
					}
				}
				
			}

		}
	}
	
	/// Once we are done, we push the remaining strings to the files
	foreach ($FILES as $NAME=>&$FP)fputs($FP,$STR[$NAME]);


	echo "############## PUSH TO DB\n";
	print_r($STATUS_FILES);
	
	
	foreach ($COL_ORDER as $NAME=>$CTL)
	{

	//	if (in_array($NAME,$TO_FILTER))continue;

	/// If no entries have been added in the file, no need to run psql
	if (!$STATUS_FILES[$NAME])continue;
	
		addLog("inserting ".$NAME." records");
		$res=array();
		fclose($FILES[$NAME]);
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		
		$FILES[$NAME]=fopen($NAME.'.csv','w');
		if ($return_code !=0 )failProcess($JOB_ID."A03",'Unable to insert '.$NAME); 
	}


	/// We don't necessary need this query, but it ensure to keep the connection alive
	$res=runQuery("select * FROM variant_freq_study"); 
	$DB_STUDIES=array();
	foreach ($res as $line)
	{
		//$DB_STUDIES[$line['short_name']]=$line['variant_freq_study_id'];
		
		$DB_STUDIES[$line['variant_freq_study_name']]=$line['variant_freq_study_id'];
	}
		

}

?>
