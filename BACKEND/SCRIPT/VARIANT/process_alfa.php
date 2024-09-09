<?php
/*
 SCRIPT NAME: process_alfa
 PURPOSE:     Processing alfa file
 
*/
error_reporting(E_ALL);
ini_set('memory_limit','1200M');

/// Job name - Do not change
$JOB_NAME='process_alfa';

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

addLog("Go to directory");

	/// Get parent information:
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_alfa')];


	/// Setting up directory path:
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 									failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/DBSNP/';   			if (!is_dir($U_DIR))				 			failProcess($JOB_ID."002",'Unable to find  '.$U_DIR);
	$U_DIR.='/'.$CK_INFO['TIME']['DEV_DIR'].'/';   if (!is_dir($U_DIR))				 					failProcess($JOB_ID."003",'Unable to find '.$U_DIR);
	if (!chdir($U_DIR))				 																	failProcess($JOB_ID."004",'Unable to access '.$U_DIR);

	/// Check static directory and ALFA_POP file
	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$JOB_INFO['DIR'];
										    	
	$ALFA_STUDY	  =$STATIC_DIR.'/ALFA_POP';
	if (!checkFileExist($ALFA_STUDY))											   			 			failProcess($JOB_ID."005",'Missing ALFA_STUDY setup file ');


	/// Checking master script:
	$ALL_FILE=$U_DIR.'/SCRIPTS/all.sh';
	if (!is_file($ALL_FILE))																			failProcess($JOB_ID."006",'Unable to find master job file at '.$ALL_FILE);
	$TOT_JOBS=getLineCount($ALL_FILE);



	$TASK_ID=$argv[1];
	

addLog("Verification of ALFA studies");
	/// Then we look for all studies associated to ALFA
	/// check if they are in the database and create them if necessary
	$res=runQuery("SELECT * 
					FROM variant_freq_study v, source s 
					where s.source_id = v.source_id 
					AND source_name = 'dbSNP-ALFA'"); 
	if ($res===false)																					failProcess($JOB_ID."006",'Unable to get dbSNP alfa studies');
	$DB_STUDIES=array();$ALLOWED_STUDIES=array();
	foreach ($res as $line)
	{
		//$DB_STUDIES[$line['short_name']]=$line['variant_freq_study_id'];
		$ALLOWED_STUDIES[$line['variant_freq_study_id']]=$line['variant_freq_study_name'];
		$DB_STUDIES[$line['variant_freq_study_name']]=$line['variant_freq_study_id'];
	}
	
	
	/// Getting the list of chromosome
	$res=runQuery("SELECT chr_seq_id, refseq_name,refseq_version 
	FROM chr_seq cs, chromosome c, taxon t 
	where t.taxon_id = c.taxon_id 
	AND c.chr_id = cs.chr_id 
	and tax_id='9606'");
	if ($res===false)																				failProcess($JOB_ID."007",'Unable to insert chromosome sequences');
	$STATIC_DATA['CHR_SEQ']=array();
	foreach ($res as $line)
	{
		$STATIC_DATA['CHR_SEQ'][$line['refseq_name']]	=$line['chr_seq_id'];
	}
	ksort($STATIC_DATA['CHR_SEQ']);
		
	$STATS=array('N_LINE'=>0,'NO_RSID'=>0,'NO_VARIANT_CHANGE'=>0);
		
	/// Processing file
	$fp=fopen('ALFA/freq.vcf','r');if (!$fp)																failProcess($JOB_ID."008",'Unable to open freq.vcf');

	//  0   		1			2				3	4	5		6		7		8		9
	//#CHROM		POS			ID				REF	ALT	QUAL	FILTER	INFO	FORMAT	SAMN10492695	SAMN10492696	SAMN10492697	SAMN10492698	SAMN10492699	SAMN10492700	SAMN10492701	SAMN10492702	SAMN11605645	SAMN10492703	SAMN10492704	SAMN10492705
	//NC_000001.9	144135212	rs1553120241	G	A	.	.	.	AN:AC	8560:5387	8:8	256:224	336:288	32:24	170:117	32:24	18:13	20:15	344:296	288:248	9432:6100
	//NC_000001.9	144148243	rs2236566		G	T	.	.	.	AN:AC	5996:510	0:0	0:0	0:0	0:0	0:0	0:0	0:0	84:8	0:0	0:0	6080:518
	//NC_000001.9	146267105	rs1553119693			T	G	.	.	.	AN:AC	37168:28800	36:22	56:44	1378:839	18:14	70:60	10:9	4836:3639	452:322	1414:861	66:53	44024:33749
	//NC_000001.9	148488564	.	C	A	.	.	.	AN:AC	8552:0	8:0	256:0	338:0	32:0	170:0	32:0	16:0	20:0	346:0	288:0	9424:0

	
	$FILE=fopen('DATA_ALFA/res_'.$TASK_ID.'.csv','w'); if (!$FILE)							failProcess($JOB_ID."009",'Unable to open res_'.$TASK_ID.'.csv');

	/// Reading the header, which contains a lot of unnecessary information for this process
	$HEADER=array();
	for ($I=0;$I<100;++$I)
	{
		$line=stream_get_line($fp,10000,"\n");
		//echo "TEST:".$line."\n";

		/// We skip the lines starting with ##
		if (substr($line,0,2)=='##')continue;

		/// Once we reach the header, we stop
		if (substr($line,0,6)=='#CHROM')
		{
			$HEADER=explode("\t",substr($line,1));
			
			break;
		}
	}
	if ($HEADER==array())																			failProcess($JOB_ID."010",'No header found');
	
	/// We are going to process the file by starting to the JOB_IDth line, process it
	/// then skipping the next TOT_JOB-1 lines, process the next one, etc.
	for ($I=0;$I<$TASK_ID;++$I)
	{
		$line=stream_get_line($fp,10000,"\n");
	//	echo "SKIP:".$line."\n";
	}

	$N_BLOCK=0;
	$BLOCK=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		//echo $line."\n";
		if (substr($line,0,2)=='##')continue;
		/// Line starting with CHROM is the header
		
		$STATS['N_LINE']++;

		$tab=explode("\t",$line);
		
		$tab[]=$line;
		/// We process by block to 2K lines to reduce the amount of queries to do
		$BLOCK[]=$tab;
		
		$N_L=0;
		for ($I_SHIFT=0;$I_SHIFT<$TOT_JOBS-1;++$I_SHIFT)
		{
			$line=stream_get_line($fp,10000,"\n");
			if (substr($line,0,2)=='##'){$I_SHIFT--;continue;}
			
			//echo $line."\n";
			$N_L++;
		}

		if (count($BLOCK)<1000)continue;

		/// Then we process the block
		processBlock($BLOCK);
		++$N_BLOCK;
		print_r($STATS);
		$BLOCK=null;
		$BLOCK=array();
		/// And once we processed 20 blocks (about 40K lines), we insert the results
		if ($N_BLOCK%20!=0)continue;
		
		echo "FPOS:".ftell($fp)."\n";
		
		//break;
		
	}	

	fclose($fp);
		
	print_r($STATS);







function processBlock(&$BLOCK)
{
	global $ALLOWED_STUDIES;
	global $STATS;
	global $FILE;
	global $HEADER;
	global $DB_STUDIES;
	global $DBIDS;
	
	global $STATIC_DATA;

	/// First, we are going to get all the chromosome and chromosome position listed
	$CHR_POS=array();
	foreach ($BLOCK as $N=>$B)
	{
		/// $B[0] is the chromosome sequence: NC_000001.9
		$CHR_SEQ=explode(".",$B[0]);


		/// We check that we have that chromsome sequence in the STATIC_DATA array
		if (!isset($STATIC_DATA['CHR_SEQ'][$CHR_SEQ[0]]))
		{
			$STATS['NO_CHR_SEQ']++;
			continue;
		}


		/// Then we check that it is associated to a rsid
		if ($B[2]=='.')
		{
			$STATS['NO_RSID']++;
			continue;
		}
		$CHR_SEQ_ID=$STATIC_DATA['CHR_SEQ'][$CHR_SEQ[0]];
		
		//	print_r($B);
		
		/// Here we make the association chromosome sequence + chromosome position and rsid
		$CHR_POS[$CHR_SEQ_ID]['('.$B[1].','.substr($B[2],2).')']=$N;
		
	}
	echo count($CHR_POS)." CHROMOSOMES\n";
	
	$LIST_VAR_POS=array();
	
	/// Then for each chromosome sequence, we query the database using that list of pairs of position/rsid to get the variant position id
	foreach ($CHR_POS as $CHR_SEQ_ID =>$INFO)
	{
		$query='SELECT chr_pos,rsid,variant_position_id 
		FROM variant_entry ve,variant_position vs, chr_seq_pos cs 
		WHERE ve.variant_entry_id = vs.variant_entry_id 
		AND cs.chr_seq_pos_id = vs.chr_seq_pos_id 
		AND chr_seq_id = '.$CHR_SEQ_ID.' 
		AND (chr_pos,rsid) IN ('.implode(',',array_keys($INFO)).')';
		//echo $query."\n";
		echo "\t".$CHR_SEQ_ID." => ".count($INFO)."\n";
		$res=runQuery($query);
		if ($res===false)																failProcess($JOB_ID."A01",'Unable to get variant positions'); 
		////print_r($res);
		foreach ($res as $line)
		{
			$BP=$CHR_POS[$CHR_SEQ_ID]['('.$line['chr_pos'].','.$line['rsid'].')'];
			$ENTRY=&$BLOCK[$BP];
			$ENTRY['POS']=$line['variant_position_id'];
			$LIST_VAR_POS[$line['variant_position_id']]=$BP;
		}
	}
	
	//nothing found -> we get another batch
	if ($LIST_VAR_POS==array())return;
	
	
	$MAP=array();
	echo "\tVAR POS : ".count($LIST_VAR_POS)."\n";
	/// Otherwise we are going to retrieve the alternative alleles
	
	$res=runQuery("SELECT variant_position_id,variant_seq as alt_all,vc.variant_change_id
		FROM variant_change vc, variant_allele va
		WHERE  variant_allele_id = alt_all
		AND variant_position_id IN (".implode(',',array_keys($LIST_VAR_POS)).")");
	if ($res===false)																failProcess($JOB_ID."A02",'Unable to get variant changes');
	foreach ($res as $line)
	{
		$MAP[$line['variant_change_id']]=array($LIST_VAR_POS[$line['variant_position_id']],$line['alt_all']);
		
		$BLOCK[$LIST_VAR_POS[$line['variant_position_id']]]['DATA'][$line['alt_all']]=array('VC_ID'=>$line['variant_change_id'],'FREQ'=>array());
	}



	//// Then we get variant frequencies associated to those variant changes
	$res=runQuery("SELECT variant_change_id,ref_count,alt_count, variant_freq_study_id,variant_frequency_id
		FROM variant_frequency 
		WHERE variant_change_id IN (".implode(",",array_keys($MAP)).')');
	if ($res===false)																failProcess($JOB_ID."A03",'Unable to get variant frequency');
	
	echo "\tFREQ : ".count($MAP)."\n";
	// print_r($res);
	foreach ($res as $line)
	{
		/// But we only look at the frequency associated to ALFA studies
		if (!isset($ALLOWED_STUDIES[$line['variant_freq_study_id']]))continue;
		$BP=$MAP[$line['variant_change_id']];
		//$BLOCK[$BP]['DATA'][$line['alt_all']]['VC_ID']=$line['variant_change_id'];
		$BLOCK[$BP[0]]['DATA'][$BP[1]]['FREQ'][$ALLOWED_STUDIES[$line['variant_freq_study_id']]]=array($line['ref_count'],$line['alt_count'],$line['variant_frequency_id'],'FROM_DB');
	}

	$TO_DEL=array();
	$N_HEADER=count($HEADER);
	foreach ($BLOCK as $K=> &$BLK)
	{
		// echo "##################\n\n\n";
		if (!isset($BLK['DATA'])){
			$STATS['NO_VARIANT_CHANGE']++; 
			unset($BLOCK[$K]);
			continue;	
		}

		/// Here we get all alternative alleles
		$alt_all=explode(",",$BLK[4]);
		$FORMAT=array_flip(explode(":",$BLK[8]));
		/// Starting from column 9 (which is the first study)
		for ($I=9;$I<$N_HEADER;++$I)
		{
			/// Get the study name
			$STUDY_NAME=$HEADER[$I];
			$VALUE=$BLK[$I];
			$tab_t=explode(":",$VALUE);
			
			$tot_stat = $tab_t[$FORMAT['AN']];
			$alt_stat=explode(",",$tab_t[$FORMAT['AC']]);
			
			/// So we loop over each alternative allele 
			foreach($alt_all as $K_ALT=>$ALLELE)
			{
				//echo "ALLELE: " .$ALLELE."\n";
				
				/// Get the existing data
				$ALL_DATA=&$BLK['DATA'][$ALLELE]['FREQ'];
				/// See if we have data associated to that study for that specific allele
				if (isset($ALL_DATA[$STUDY_NAME]))
				{
			
					$VALID=true;
					// We check if the data is the same or not. 
					if ($ALL_DATA[$STUDY_NAME][1]!=$tot_stat)
					{
						$VALID=false;
						$ALL_DATA[$STUDY_NAME][1]=$tot_stat;
					}
					if ($ALL_DATA[$STUDY_NAME][0]!=$alt_stat[$K_ALT])
					{
						$VALID=false;
						$ALL_DATA[$STUDY_NAME][0]=$alt_stat[$K_ALT];
					}
					/// Data different => Invalid -> need deletion and reinsertion
					if (!$VALID)
					{
						$ALL_DATA[$STUDY_NAME][3]='TO_INS';
						
						/// Add it to the list of data to delete
						$TO_DEL[]=$ALL_DATA[$STUDY_NAME][2];
						
						$STATS['UPD_FREQ']++;
						
						if ($BLK['DATA'][$ALLELE]['VC_ID']!='')	
						{
							//echo $DB_STUDIES[$STUDY_NAME].",".$alt_stat[$K_ALT].",".$tot_stat."\n";
							if (!runQueryNoRes("INSERT INTO variant_frequency(variant_frequency_id , variant_change_id , variant_freq_study_id , ref_count , alt_count)
								VALUES (nextval('biorels.variant_frequency_sq'),".$BLK['DATA'][$ALLELE]['VC_ID'].",".$DB_STUDIES[$STUDY_NAME].",".$alt_stat[$K_ALT].",".$tot_stat.')'))
							failProcess($JOB_ID."A04",'Unable to insert variant frequency');
							//	fputs($FILE,$BLK['DATA'][$ALLELE]['VC_ID']."\t".$DB_STUDIES[$STUDY_NAME]."\t".$alt_stat[$K_ALT]."\t".$tot_stat."\n");
						}
					}
					else $STATS['VALID_FREQ']++;
				}
				else /// Not existing, we insert
				{
					$STATS['NEW_FREQ']++;

					//echo "IB";
					if ($BLK['DATA'][$ALLELE]['VC_ID']!='')
					{
						//echo "INSERT ".$DB_STUDIES[$STUDY_NAME].",".$alt_stat[$K_ALT].",".$tot_stat."\n";
						if (!runQueryNoRes("INSERT INTO variant_frequency(variant_frequency_id , variant_change_id , variant_freq_study_id , ref_count , alt_count)
								VALUES (nextval('biorels.variant_frequency_sq'),".$BLK['DATA'][$ALLELE]['VC_ID'].",".$DB_STUDIES[$STUDY_NAME].",".$alt_stat[$K_ALT].",".$tot_stat.')'))
								failProcess($JOB_ID."A05",'Unable to insert variant frequency - 2');
						//fputs($FILE,$BLK['DATA'][$ALLELE]['VC_ID']."\t".$DB_STUDIES[$STUDY_NAME]."\t".$alt_stat[$K_ALT]."\t".$tot_stat."\n");

					}
						// $ALL_DATA[$STUDY_NAME]=array($tot_stat,
						// $alt_stat[$K_ALT],
						// $DBIDS['variant_frequency'],
						// 'TO_INS');
				}
			}
			
			/// Once we processed all alleles, we are left with the reference allele
			$ref_stat=$tot_stat-array_sum($alt_stat);
			
			/// Get the existing data
			$ALL_DATA=&$BLK['DATA'][$BLK[3]]['FREQ'];
			
			
			/// See if we have data associated to that study for that specific allele
			if (isset($ALL_DATA[$STUDY_NAME]))
			{
				// We check if the data is the same or not. 
				$VALID=true;
				if ($ALL_DATA[$STUDY_NAME][1]!=$tot_stat)
				{
					$VALID=false;
					$ALL_DATA[$STUDY_NAME][1]=$tot_stat;
				}
				if ($ALL_DATA[$STUDY_NAME][0]!=$ref_stat)
				{
					$VALID=false;
					$ALL_DATA[$STUDY_NAME][0]=$ref_stat;
				}
				/// Data different => Invalid -> need deletion and reinsertion
				$ALL_DATA[$STUDY_NAME][3]='VALID';


				/// Data different => Invalid -> need deletion and reinsertion
				if (!$VALID){
					if ($BLK['DATA'][$BLK[3]]['VC_ID']!='')
					{
						$STATS['UPD_FREQ']++;
						$query='UPDATE variant_frequency SET ref_count = '.$ref_stat.', alt_count='.$tot_stat.' WHERE variant_frequency_id = '.$ALL_DATA[$STUDY_NAME][2];
						//	echo $query."\n";
						
						if (!runQueryNoRes($query))	 failProcess($JOB_ID."A06",'Unable to update variant frequency');
						//fputs($FILE,$BLK['DATA'][$BLK[3]]['VC_ID']."\t".$DB_STUDIES[$STUDY_NAME]."\t".$ref_stat."\t".$tot_stat."\n");
					}else 
					{
						$TO_DEL[]=$ALL_DATA[$STUDY_NAME][2];
						if (!runQueryNoRes("INSERT INTO variant_frequency(variant_frequency_id , variant_change_id , variant_freq_study_id , ref_count , alt_count)
								VALUES (nextval('biorels.variant_frequency_sq'),".$BLK['DATA'][$BLK[3]]['VC_ID'].",".$DB_STUDIES[$STUDY_NAME].",".$ref_stat.",".$tot_stat.')'))
							failProcess($JOB_ID."A07",'Unable to update variant frequency');
					}
				
				}else $STATS['VALID_FREQ']++;
			}
			else/// not existing, we insert
			{
				$STATS['NEW_FREQ']++;

				if ($BLK['DATA'][$BLK[3]]['VC_ID']!='') 
				{
					$query="INSERT INTO variant_frequency(variant_frequency_id , variant_change_id , variant_freq_study_id , ref_count , alt_count)
					VALUES (nextval('biorels.variant_frequency_sq'),".
							$BLK['DATA'][$BLK[3]]['VC_ID'].",".
							$DB_STUDIES[$STUDY_NAME].",".
							$ref_stat.",".
							$tot_stat.')';
					if (!runQueryNoRes($query))														failProcess($JOB_ID."A08",'Unable to insert variant frequency');
					
				}
			}

			// exit;
		}
		// print_r($BLK);
	}
	if ($TO_DEL!=array())/// Then we delete all old data
	{
		$STATS['DEL_FREQ']+=count($TO_DEL);
		echo count($TO_DEL).' DELETE RECORDS'."\n";
		$query='DELETE FROM VARIANT_FREQUENCY WHERE VARIANT_FREQUENCY_ID IN ('.implode(',',$TO_DEL).')';
		if (!runQueryNoRes($query))		failProcess($JOB_ID."A09",'Unable to delete variant frquency records');
	}
	

}
?>
