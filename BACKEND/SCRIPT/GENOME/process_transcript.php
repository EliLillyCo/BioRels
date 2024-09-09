<?php
ini_set('memory_limit','2000M');

/**
 SCRIPT NAME: process_transcript
 PURPOSE:     Process transcript sequences, match them to the genome.
*/

/// Job name - Do not change
$JOB_NAME='process_transcript';


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
	$PARENT_INFO=$GLB_TREE[getJobIDByName('db_genome')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];			if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';   				if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$PARENT_INFO['TIME']['DEV_DIR'];  		   	if (!is_dir($W_DIR) || !chdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);
	//chdir($W_DIR);

	/// TR_ALIGN is the python script using pysam that allows us to get the alignment DNA/RNA for RefSeq
	if (!isset($GLB_VAR['TOOL']['TR_ALIGN']))														failProcess($JOB_ID."004",'TR_ALIGN tool not set');
	if (!isset($GLB_VAR['TOOL']['PYTHON']))															failProcess($JOB_ID."005",'PYTHON tool not set');
	if (!checkFileExist($GLB_VAR['TOOL']['TR_ALIGN']))												failProcess($JOB_ID."006",'Unable to execute TR_ALIGN');
	
	/// Full path to the python script:
	$GLB_VAR['TOOL']['TR_ALIGN']=$GLB_VAR['TOOL']['PYTHON']. ' '.$GLB_VAR['TOOL']['TR_ALIGN'];



addLog("Preparation tasks");

	if (!isset($argv[1]))																		failProcess($JOB_ID."007",'No task id provided');
	if (!isset($argv[2]))																		failProcess($JOB_ID."008",'No task info provided');


	$TASK_ID=$argv[1];
	/// Task info provides the rules defining which taxon/transcripts to process.
	/// It is ASSEMBLY_ACC-START-END__ASSEMBLY_ACC-START-END
	$TASK_INFO=$argv[2];


	echo $TASK_ID."\t".$TASK_INFO."\n";

	/// TASK_ASSEMBLY will have the list of assemblies  and the range of transcript within an assembly to process
	$TASK_ASSEMBLY=array();
	$tmp=explode("__",$TASK_INFO);
	foreach ($tmp as $t)
	{
		if ($t=='')continue;
		$tmp2=explode("-",$t);
		$TASK_ASSEMBLY[$tmp2[0]]=array($tmp2[1],$tmp2[2],-1);
	}

	/// All transcripts nucleotide information will be save in this file
	$FILE=array();
	$FILE['transcript_pos']=fopen('JSON/RESULTS_'.$TASK_ID,'w');if (!$FILE['transcript_pos'])failProcess($JOB_ID."009",'Unable to open RESULTS file');
	$FILE['failed_tr']=		fopen('JSON/FAILED_'.$TASK_ID,'w'); if (!$FILE['failed_tr'])	 failProcess($JOB_ID."010",'Unable to open failed_tr file');
	$FILE['stats']=			fopen('JSON/stats_'.$TASK_ID,'w');  if (!$FILE['stats'])		 failProcess($JOB_ID."011",'Unable to open stats file');

	/// overview.json contains the file information for each taxon
	if (!is_file($W_DIR.'/overview.json'))												failProcess($JOB_ID."012",'Unable to find overview.json ');
	$TAXON_INFO=json_decode(file_get_contents($W_DIR.'/overview.json'),true);
	if ($TAXON_INFO==null)																failProcess($JOB_ID."013",'Unable to obtain information from overview.json ');
	

	/// Each transcript can be assigned a specific type: CDS,3'UTR,5'UTR etc., we need that list
	$SEQ_TYPES=array();
	$res=runQuery("SELECT TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE FROM TRANSCRIPT_POS_TYPE");
	if ($res===false)																	failProcess($JOB_ID."014",'Unable to get transcript_pos_type');
	foreach ($res as $l)$SEQ_TYPES[$l['transcript_pos_type']]=$l['transcript_pos_type_id'];


	/// Processing transcripts
	$STATS=array();
	foreach ($TASK_ASSEMBLY as $TASK_ASSEMBLY_ACC=>$RANGE)
	{
		$TASK_ASSEMBLY_INFO=null;
		$FULL_ASSEMBLY_ACC=null;
		foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
		foreach ($LIST as $ASSEMBLY_ACC=>&$ASSEMBLY_INFO)
		foreach ($ASSEMBLY_INFO['SOURCES'] as &$SOURCE)
		{
			
			if ($SOURCE['Assembly_Acc']!=$TASK_ASSEMBLY_ACC)continue;
			$TASK_ASSEMBLY_INFO=$ASSEMBLY_INFO;
			$FULL_ASSEMBLY_ACC=$ASSEMBLY_ACC;
			
		}
		processTranscriptSeq($FULL_ASSEMBLY_ACC,$TASK_ASSEMBLY_INFO,$RANGE);
			
		
	}
	
	foreach ($STATS as $ASSEMBLY_ACC=>$STAT)	fputs($FILE['stats'],$ASSEMBLY_ACC."\t".json_encode($STAT)."\n");



/// For a given taxon and the range of transcripts to be processed, process each transcript
function processTranscriptSeq($ASSEMBLY_ACC,&$ASSEMBLY_INFO,&$RANGE)
{
	global $STATS;
	$STATS[$ASSEMBLY_ACC]=
	array(
		'PROCESSED_TRANSCRIPT'=>0,'SUCCESS_TRANSCRIPT'=>0,'PROCESS_TRANSCRIPT_CHR'=>0,
		'MISSING_DNA_POSITION'=>0,'FAILED_TRANSCRIPT'=>0,'ALIGNMENT_RNA_DNA_HIGH'=>0,
		'MISSING_SEQUENCE'=>0,'MISSING_EXONS'=>0,'REF_CHROM_NOT_FOUND'=>0,
		'ABOVE_PATCH_RANGE'=>0,'NO_DNA'=>0,'SEQUENCE_WRONG_NAME'=>0,
		'TRCHR_SEQAL_CHROMOSOME_NOT_FOUND'=>0,'TRCHR_SEQAL_CHR_NOT_PRESENT'=>0,'MISSING_DNA'=>0,
		'TRCHR_SEQAL_TR_NOT_IN_DB'=>0,'SUCCESS_TRANSCRIPT_CHR'=>0,'MISSING_DNA_POSITION'=>0,
		'MULTIPLE_CDS_POS'=>0,'MISSING_DNA_POSITION_EXACT'=>0,
		'SAME_TRANSCRIPT_FROM_DB'=>0,'DIFF_LENGTH_TRANSCRIPT_FROM_DB'=>0,
		'DIFF_TRANSCRIPT_FROM_DB'=>0,'NEW_TRANSCRIPT_FROM_DB'=>0,
		'FAILED_DELETION'=>0,'FAILED_PYTHON'=>0,'MULTIPLE_EXON_POS'=>0,'MULTIPLE_CDS_POS'=>0);


	
	//// ALL DATA related to the assembly will be stored in ASSEMBLY_DATA
	$ASSEMBLY_DATA=array();

/// Then we load all chromosome sequence, gene sequence and transcript information:
	loadAssemblyData($ASSEMBLY_DATA,$ASSEMBLY_INFO['DBID'],$ASSEMBLY_ACC);


	$ENS_INFO=null;
	$RS_INFO=null;
	foreach ($ASSEMBLY_INFO['SOURCES'] as &$SOURCE_INFO)
	{
		if ($SOURCE_INFO['Source']=='ENSEMBL')
		{
			$ENS_INFO=&$SOURCE_INFO;
			$ENS_INFO['DBID']=$ASSEMBLY_INFO['DBID'];
		}
		else if ($SOURCE_INFO['Source']=='REFSEQ')
		{
			$RS_INFO=&$SOURCE_INFO;
			$RS_INFO['DBID']=$ASSEMBLY_INFO['DBID'];
		}
	}
	


	/// Then we process RefSeq
	if ($RS_INFO!=null)processRefSeqTranscripts($ASSEMBLY_DATA,$RS_INFO,$RANGE,$ASSEMBLY_ACC);
	/// And Ensembl
	if ($ENS_INFO!=null)processEnsemblTranscripts($ASSEMBLY_DATA,$ENS_INFO,$RANGE,$ASSEMBLY_ACC);
	
		
	
}


function openRSFiles($TAX_ID,$RS_DIR)
{
	echo "############################# OPEN REFSEQ FILES FOR ".$TAX_ID." #############################\n";
	global $W_DIR;
	global $FILES;

	if (isset($FILES) && is_array($FILES))
	foreach ($FILES as $R=>&$T)if (is_resource($T) && $R!='RS_POINTER')fclose($T);
	
	/// Contains transcript sequences
	echo $RS_DIR.$TAX_ID.'_rna.fa'."\n";
	$FILES['RS_RNA']=fopen($RS_DIR.$TAX_ID.'_rna.fa','r');
	if (!$FILES['RS_RNA'])															 failProcess($JOB_ID."A01",'Unable to open rna');
	

	$BAM_FILES=array(
		'RS_KNOWN_BAM'=>$RS_DIR.$TAX_ID.'_knownrefseq_alns.body',
		'RS_MODEL_BAM'=>$RS_DIR.$TAX_ID.'_modelrefseq_alns.body',
		'MODEL_HEAD'=>$RS_DIR.$TAX_ID.'_modelrefseq_alns.header',
		'KNOWN_HEAD'=>$RS_DIR.$TAX_ID.'_knownrefseq_alns.header'

	);
	$HAS_BAM_FILES=0;
	foreach ($BAM_FILES as $B) if (is_file($B))$HAS_BAM_FILES++;
	if ($HAS_BAM_FILES==0)return false;
	else 
	{

		/// contains RNA/DNA alignment for NM_
		$FILES['RS_KNOWN_BAM']=fopen($RS_DIR.$TAX_ID.'_knownrefseq_alns.body','r');
		if (!$FILES['RS_KNOWN_BAM']) 													failProcess($JOB_ID."A02",'Unable to open _knownrefseq_alns.body');
		
		/// Contains RNA/DNA alignment for XM_
		$FILES['RS_MODEL_BAM']=fopen($RS_DIR.$TAX_ID.'_modelrefseq_alns.body','r');
		if (!$FILES['RS_MODEL_BAM']) 													failProcess($JOB_ID."A03",'Unable to open _modelrefseq_alns.body');

		/// Header file needed to extract alignment for XM_
		if (!is_file($RS_DIR.$TAX_ID.'_modelrefseq_alns.header'))						failProcess($JOB_ID."A04",'Unable to open _modelrefseq_alns.header');
		$FILES['MODEL_HEAD']=file_get_contents($RS_DIR.$TAX_ID.'_modelrefseq_alns.header');

		/// Header file needed to extract alignment for NM_
		if (!is_file($RS_DIR.$TAX_ID.'_knownrefseq_alns.header'))						failProcess($JOB_ID."A05",'Unable to open _knownrefseq_alns.header');
		$FILES['KNOWN_HEAD']=file_get_contents($RS_DIR.$TAX_ID.'_knownrefseq_alns.header');
	}
	
	return true;

}






function processRefSeqTranscripts(&$ASSEMBLY_DATA,&$ASSEMBLY_INFO,&$RANGE_ASSEMBLY,$ASSEMBLY_ACC)
{
	global $W_DIR;
	global $STATS;
	global $FILE;
	global $FILES;
	$TAX_ID=$ASSEMBLY_INFO['Tax_Id'];
	$RS_DIR=$ASSEMBLY_INFO['DIR'].'/';
	/// Now we have different files to open for this process, so we open them:
	$W_BAM_FILE=openRSFiles($TAX_ID,$RS_DIR);
	
	/// The pointer file contains file positions in the alignment, rna and gff file
	/// so we use this one to go through the list of transcripts.
	$FILES['RS_POINTER']=fopen($RS_DIR.$TAX_ID.'_pointers.csv','r');
	if (!$FILES['RS_POINTER']) 																failProcess($JOB_ID."B01",'Unabel to open pointers');



	$tRNA_EXCEPTION=array();
	$res=runQuery("SELECT transcript_name,transcript_version 
	FROM chr_seq cs, biorels.transcript t, biorels.seq_btype s 
	where s.seq_btype_id = t.biotype_Id 
		AND seq_type='tRNA' 
		AND cs.chr_seq_id=t.chr_seq_id
		AND cs.genome_assembly_id='".$ASSEMBLY_INFO['DBID']."'
		");
	foreach ($res as $line)$tRNA_EXCEPTION[$line['transcript_name'].(($line['transcript_version']!='')?'.'.$line['transcript_version']:'')]=false;

	
	$START=false;
	global $COL_ORDER;
	while(!feof($FILES['RS_POINTER']))
	{
		
		$line=stream_get_line($FILES['RS_POINTER'],10000000,"\n");
		
		/// RANGE_TAX provide the range of transcripts that we need to process
		$RANGE_ASSEMBLY[2]++;
		if ($RANGE_ASSEMBLY[2]<$RANGE_ASSEMBLY[0])continue;
		if ($RANGE_ASSEMBLY[2]>$RANGE_ASSEMBLY[1])break;

		$tab=explode("\t",$line);
		/// We decode the json string
		$tab[1]=json_decode($tab[1],true);;
		
		
		/// If you want to debug a specific sequence, you can provide the transcript name below and uncomment the couple lines below
			    //   if ($tab[0]=='MIR124-1') $START=true;
			    // if (!$START)continue;
		$result=false;
		echo "RUN\tREFSEQ\t".$RANGE_ASSEMBLY[0]."\t".$RANGE_ASSEMBLY[2]."\t".$RANGE_ASSEMBLY[1]."\t".$tab[0]."\t";
		if ($W_BAM_FILE)
		{
			if (isset($tRNA_EXCEPTION[$tab[0]])) 
			{
				echo "tRNA\n";
				$result=true;
			}
			else 
			/// Then process RefSeq transcript
			$result=processRefSeq($ASSEMBLY_DATA,$FILES,$tab[0],$tab[1],$ASSEMBLY_ACC,$RS_DIR,$TAX_ID);
		}else $result=processRefSeqNoBAM($ASSEMBLY_DATA,$FILES,$tab[0],$tab[1],$ASSEMBLY_ACC);

		/// If you want to debug a specific sequence, uncomment the line below
		

		if ($result)$STATS[$ASSEMBLY_ACC]['SUCCESS_TRANSCRIPT']++;
		else 
		{
			fputs($FILE['failed_tr'],$tab[0]."\n");
			$STATS[$ASSEMBLY_ACC]['FAILED_TRANSCRIPT']++;
		}
		if ($RANGE_ASSEMBLY[2]%100==0)
		{
			echo "STATS\t".$ASSEMBLY_ACC;
			foreach ($STATS[$ASSEMBLY_ACC] as $K=>$V)echo "\t".$K."::".$V;echo "\n";
		}

	}
	
}


function loadAssemblyData(&$ASSEMBLY_DATA,$ASSEMBLY_DBID,$ASSEMBLY_ACC)
{
	/// We need all the chromosome sequences and current transcripts.
	$ASSEMBLY_DATA=array('CHR_SEQ'=>array());
	$res=runQuery("SELECT chr_seq_id , refseq_name  , refseq_version,chr_seq_name , genbank_name,
		 genbank_version ,seq_role,chr_num,chr_start_pos, chr_end_pos, assembly_unit
		FROM chr_seq cs, chromosome c 
		WHERE c.chr_id = cs.chr_id 
		AND  genome_assembly_id = '".$ASSEMBLY_DBID."'");
	if ($res===false)		 																failProcess($JOB_ID."C01",'Unable to get chromosome sequences for  '.$ASSEMBLY_ACC);
	
	
	$STR='';
	foreach ($res as $l)
	{
		if (($l['assembly_unit']=='Primary Assembly'||$l['assembly_unit']=='non-nuclear'  )
		&& $l['seq_role']=='assembled-molecule')$ASSEMBLY_DATA['REF_CHROM'][$l['chr_num']]=$l;

		/// Listing the different names for the chromosome sequences:
		$ASSEMBLY_DATA['CHR_SEQ'][$l['genbank_name'].(($l['genbank_version']!='')?'.'.$l['genbank_version']:'')]=$l['chr_seq_id'];
		$ASSEMBLY_DATA['CHR_SEQ'][$l['refseq_name'].(($l['refseq_version']!='')?'.'.$l['refseq_version']:'')]=$l['chr_seq_id'];
		$ASSEMBLY_DATA['CHR_SEQ'][$l['chr_seq_name']]=$l['chr_seq_id'];
		$ASSEMBLY_DATA['CHR_INFO'][$l['chr_seq_id']]=$l;
		$STR.=$l['chr_seq_id'].',';
		if ($l['chr_num']=='MT')$ASSEMBLY_DATA['MT'][]=$l['chr_seq_id'];
	}
	ksort($ASSEMBLY_DATA['REF_CHROM']);
	
	
	if ($STR=='') return;

	addLog("Get transcripts for ".$ASSEMBLY_ACC);
	$res=runQuery('SELECT transcript_id , transcript_name , transcript_version, seq_hash , t.chr_seq_id,gs.strand,valid_alignment
		FROM transcript t, gene_Seq gs 
		where t.gene_seq_Id = gs.gene_seq_id 
		AND gs.chr_seq_id IN (' .substr($STR,0,-1).')');
	if ($res===false)		 																failProcess($JOB_ID."C02",'Unable to get transcripts for TAX ID '.$ASSEMBLY_ACC);
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$NAME=$line['transcript_name'];
		$ASSEMBLY_DATA['TR'][$NAME][$line['chr_seq_id']]=$line;
	}
	
}








function processEnsemblTranscripts(&$ASSEMBLY_DATA,&$ASSEMBLY_INFO,&$RANGE_ASSEMBLY,&$ASSEMBLY_ACC)
{
	/// If we are already passed the range of transcripts to be processed, no need to process ensembl
	if ($RANGE_ASSEMBLY[2]>$RANGE_ASSEMBLY[1])return;
	global $W_DIR;
	global $STATS;
	global $FILE;
	global $FILES;
	$TAX_ID=$ASSEMBLY_INFO['Tax_Id'];
	$ENS_DIR=$ASSEMBLY_INFO['DIR'].'/';
	
/// Now we have different files to open for this process, so we open them:
	$FILES['ENS_POINTER']=fopen($ENS_DIR.$TAX_ID.'_pointers.csv','r');
	if (!$FILES['ENS_POINTER']) 																failProcess($JOB_ID."D01",'Unable to open pointers');
	$FILES['ENS_CDNA']=fopen($ENS_DIR.$TAX_ID.'_cdna_rna.fa','r');
	if (!$FILES['ENS_CDNA']) 																	failProcess($JOB_ID."D02",'Unable to open cdna');
	$FILES['ENS_NCRNA']=fopen($ENS_DIR.$TAX_ID.'_ncrna.fa','r');
	if (!$FILES['ENS_NCRNA']) 																	failProcess($JOB_ID."D03",'Unabel to open ncrna');
	
//	echo "VALID\n";

	$START=false;
	
	ksort($ASSEMBLY_DATA['CHR_SEQ']);
	
	
	/// The pointer file contains file positions in the alignment, rna and gff file
	/// so we use this one to go through the list of transcripts.
	while(!feof($FILES['ENS_POINTER']))
	{
		
		$line=stream_get_line($FILES['ENS_POINTER'],10000000,"\n");
		/// Checking if we are within the range of transcripts to be processed:
		
			$RANGE_ASSEMBLY[2]++;
		if ($RANGE_ASSEMBLY[2]<$RANGE_ASSEMBLY[0])continue;
		if ($RANGE_ASSEMBLY[2]>$RANGE_ASSEMBLY[1])break;

		echo "ENSEMBL\t".$RANGE_ASSEMBLY[0]."\t".$RANGE_ASSEMBLY[2]."\t".$RANGE_ASSEMBLY[1]."\t";
		
		$tab=explode("\t",$line);
		/// Decoding the information for that transcript
		$tab[1]=json_decode($tab[1],true);;
		
		
		
		// If you wish to debug a specific transcript, uncomment the two lines below
		// if ($tab[0]=='ENSOCUT00000024964') $START=true;
		// if (!$START)continue;
		
		//	echo $tab[0]."\n";
		$result=processEnsembl($ASSEMBLY_DATA,$FILES,$tab[0],$tab[1],$ASSEMBLY_ACC);

		// If you wish to debug a specific transcript, uncomment the  line below
		//exit;


		if ($result)
		{
			echo "SUCCESS\n";
			$STATS[$ASSEMBLY_ACC]['SUCCESS_TRANSCRIPT']++;
		}
		else 
		{
			echo "FAIL\n";
			fputs($FILE['failed_tr'],$tab[0]."\t".$result."\n");	
			$STATS[$ASSEMBLY_ACC]['FAILED_TRANSCRIPT']++;
		}
		//exit;
		if ($RANGE_ASSEMBLY[2]%100==0)
		{
			foreach ($STATS[$ASSEMBLY_ACC] as $K=>$V)echo "STATS\t".$ASSEMBLY_ACC."\t".$K."::".$V."\n";
		}	
	}
	echo "END ENSEMBL\n";
}









function processEnsembl(&$ASSEMBLY_DATA,&$FILES,&$TRANSCRIPT_NAME,&$POINTERS,$ASSEMBLY_ACC)
{
	global $FILES;
	global $FILE;
	global $SEQ_TYPES;
	global $DBIDS;
	global $STATS;

	$LOG_DEBUG='';
// print_r($ASSEMBLY_DATA['CHR_SEQ']);

	$STATS[$ASSEMBLY_ACC]['PROCESSED_TRANSCRIPT']++;
	/// For a given transcript, all 2 are required
	if (!isset($POINTERS[0])){$STATS[$ASSEMBLY_ACC]['MISSING_SEQUENCE']++;echo $TRANSCRIPT_NAME."\tMISSING SEQUENCE\n";return false;}
	if (!isset($POINTERS[1])){$STATS[$ASSEMBLY_ACC]['MISSING_EXONS']++;echo $TRANSCRIPT_NAME."\tMISSING EXONS\n";return false;}


	$TR_DATA=array();
	$tmp_info=explode("\t",$POINTERS[1]);
	//print_r($tmp_info);
	$TR_DATA['EXONS']=array();
	$TR_DATA['DNA_POS']=array();
	
	$query="SELECT CHR_SEQ_POS_ID, nucl,chr_pos,chr_seq_id FROM chr_seq_pos WHERE ";
	$ORDER_EXONS=array();
	$DIFF_DNA_POS=array();

	$ALL_VALID=true;
	foreach ($tmp_info as $t)
	{
		$tab=explode("|",$t);
		
		

		/// We are going to find the chromosome sequence based on its name
		/// since there's different way to name a chromosome sequence, we need to test them all
		$CHR_SEQ_ID=NULL;
		if (!isset($ASSEMBLY_DATA['CHR_SEQ'][$tab[0]]))
		{
			if (isset($ASSEMBLY_DATA['CHR_SEQ']['chr'.$tab[0]]))		$CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ']['chr'.$tab[0]];
			else if (substr($tab[0],0,4)=='CHR_')
			{
				if (!isset($ASSEMBLY_DATA['CHR_SEQ'][substr($tab[0],4)]))
				{
					echo "MISSING\t TAX INFO ENS CHR_SEQ_IDS WITH CHR_ :: ".($tab[0])."\n";
					return false;
				}
				else $CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ'][substr($tab[0],4)];

			}
			else if (isset($ASSEMBLY_DATA['CHR_SEQ']['Chr'.$tab[0]]))
			{
				$CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ']['Chr'.$tab[0]]; 
			}
			else
			{
				echo "MISSING\tTAX INFO ENS CHR_SEQ_IDS ".($tab[0])."\n";
				return false;
			}
		
		
		}
		else $CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ'][$tab[0]];


		/// Range Min Max might not be in order dependin on the strand
		$L=$tab[2];
		$R=$tab[3];
		$LV=min($L,$R);
		$RV=max($L,$R);
		

		/// So now we add to the sequence the chromosome sequence and its range so we can get the DNA
		$CHR_INFO=&$ASSEMBLY_DATA['CHR_INFO'][$CHR_SEQ_ID];
		$LOG_DEBUG.=print_R($CHR_INFO,true);
		

		/// Many situations can arise.
		/// If the chromosome is an assembled-molecule, there is nothing to do
		/// BUT if the chromosome sequence is a patch or a scaffold, then, in some cases, the transcript
		/// can be shared across both the patch/scaffold and the assembled chromosome, so we need to account for this
		$ALT_POS=array();
		if ($CHR_INFO['chr_start_pos']!='' && $CHR_INFO['seq_role']!='assembled-molecule' )
		{
			/// This will be used when doing the alignment to shift properly between the position in the chromosome and the relative position in the scaffold/patch sequence
			$DIFF_DNA_POS[$CHR_SEQ_ID]=$CHR_INFO['chr_start_pos']-1;
			$L=$LV-$CHR_INFO['chr_start_pos']+1;
			$R=$RV-$CHR_INFO['chr_start_pos']+1	;
			$LOG_DEBUG.= "###".$L."\t".$R."\n";

			/// When both relative positions are negative, it means that those positions are located on the actual chromosome
			if ($L<0 && $R<0)
			{
				/// So we ensure we have that chromosome
				if (!isset($ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]))
				{
				$STATS[$ASSEMBLY_ACC]['REF_CHROM_NOT_FOUND']++;
				$ALL_VALID=false;
				}
				/// And we use the original positioning (not the relative one) since those positions are based on the chromosome
				$query .="(chr_seq_id = ".$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id'].' AND chr_pos >= '.$LV.' AND chr_pos <='.$RV.') OR';	
				$LOG_DEBUG.= "LOW BOTH\n".$LV.":".$RV."\t".$L."\t".$R."\t".$CHR_INFO['chr_start_pos']."\t".$CHR_INFO['chr_end_pos']."\n";
			}
			/// Because a gap can exist after the end of a scaffold/patch, we cannot process those cases
			else if ($LV>$CHR_INFO['chr_end_pos'] && $RV>$CHR_INFO['chr_end_pos'])
			{
				$LOG_DEBUG.= "HIGH BOTH\tCASE NOT HANDLED\tINVALID ALL\n";
				$STATS[$ASSEMBLY_ACC]['ABOVE_PATCH_RANGE']++;
				$ALL_VALID=false;
			}
			else if ($L<0 && $RV>$CHR_INFO['chr_end_pos'])
			{
				$LOG_DEBUG.= "OUT BOTH\n".$LV.":".$RV."\t".$L."\t".$R."\t".$CHR_INFO['chr_start_pos']."\t".$CHR_INFO['chr_end_pos']."\n";
				echo $LOG_DEBUG;exit;
			}
			/// If the starting position is on the chromosome but the end position is on the scaffold/patch
			else if ($L<0 && $RV<=$CHR_INFO['chr_end_pos'])
			{
				if (!isset($ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]))
				{
					$STATS[$ASSEMBLY_ACC]['REF_CHROM_NOT_FOUND']++;
					$ALL_VALID=false;
				}

				/// Then we need to do two searches. One on the chromosome based on the original position and up to the starting position of the patch/scaffold
				$query .="(chr_seq_id = ".$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id'].' AND chr_pos >= '.$LV.' AND chr_pos <='.$CHR_INFO['chr_start_pos'].') OR';	
				
				/// And on the scaffold/patch starting for its first position, up until the shifted end position
				$query .="(chr_seq_id = ".$CHR_SEQ_ID.' AND chr_pos >= 1 AND chr_pos <='.($RV-$CHR_INFO['chr_start_pos']+1).') OR';	
				
				$ALT_POS[]=array($LV,$CHR_INFO['chr_start_pos']);
				$ALT_POS[]=array(1,($RV-$CHR_INFO['chr_start_pos']+1));
				
				$LOG_DEBUG.="OUT LOW\n".$L."::".$R."\t".$LV.":".$RV."\t".$CHR_INFO['chr_start_pos']."\t".$CHR_INFO['chr_end_pos']."\n";
			}
			else if ($L>0 && $RV>$CHR_INFO['chr_end_pos'])
			{
				$LOG_DEBUG.= "ABOVE RANGE PATCH\tCASE NOT HANDLED\tINVALID ALL\n";
				$STATS[$ASSEMBLY_ACC]['ABOVE_PATCH_RANGE']++;
				$ALL_VALID=false;
			}
			else
			{
				$ALT_POS[]=array($L,$R);
				$query .="(chr_seq_id = ".$CHR_SEQ_ID.' AND chr_pos >= '.$L.' AND chr_pos <='.$R.') OR';	
			}
		}
		else 
		{
			$query .="(chr_seq_id = ".$CHR_SEQ_ID.' AND chr_pos >= '.$LV.' AND chr_pos <='.$RV.') OR';
			//$EXON_DATA[$tab[0]][]=
		}

		/// In the pointer file, exons might not be in order. So to properly reproduce the sequence
		/// we need to order them first, so we push them into ORDER_EXONS 
		/// but for CDS data, we directly put them in the EXONS array
		
		
		if (is_numeric($tab[1]))$ORDER_EXONS[$tab[0]][$tab[1]]=array($tab[1],$tab[2],$tab[3],$ALT_POS);
		else $TR_DATA['EXONS'][$tab[0]][]=array($tab[1],$tab[2],$tab[3],$ALT_POS);
		
		
	}
	
	//exit;

	
	foreach ($ORDER_EXONS as $N=>$LIST)
	{
		ksort($LIST);
		foreach ($LIST as $T)$TR_DATA['EXONS'][$N][]=$T;
	}


		
	$LOG_DEBUG.= substr($query,0,-4).')'."\n";
	/// we run the query to fill DNA_POS that will now contain for each chromosome sequence and position its corresponding nucleotide and database ID
	$res=runQuery(substr($query,0,-4).')');
	if ($res===false)		 																failProcess($JOB_ID."E01",'Unable to get DNA sequence');
	if (count($res)==0){$STATS[$ASSEMBLY_ACC]['NO_DNA']++;echo "NO_DNA";return false;}
	
	foreach ($res as $line)
		$TR_DATA['DNA_POS'][$line['chr_seq_id']][$line['chr_pos']]=array($line['nucl'],$line['chr_seq_pos_id']);
	
	
	/// Then we look at the transcript name to see which file we should use to get the transcript sequence
	$tab=explode("_",$POINTERS[0]);
	$f=null;
	if ($tab[0]=='NC')$f=&$FILES['ENS_NCRNA'];
	else $f=&$FILES['ENS_CDNA'];
	//Get Fasta sequence
	fseek($f,$tab[1]);
	$line=stream_get_line($f,1000,"\n");
	$pos=strpos($line,'.');
	$name=substr($line,1,$pos-1);

	if ($name!=$TRANSCRIPT_NAME)
	{
		$STATS[$ASSEMBLY_ACC]['SEQUENCE_WRONG_NAME']++;
		return false;
	}

	/// For every nucleotide in the transcript sequence, we create a template array that will
	/// provide the information about the alignment, and the corresponding DNA/DNA position/DNA DB ID
	$TMP_SEQ=array();$POS_SEQ=0;
	while(!feof($f))
	{
		$line=stream_get_line($f,1000,"\n");
		if (substr($line,0,1)=='>')break;
		for ($I=0;$I<strlen($line);++$I)
		{
			$POS_SEQ++;
			//												AL_RNA	AL_ID,	EXON			TYPE   					DNA_BASE		CHR_SEQ_POS_ID
			$TMP_SEQ[$POS_SEQ]=array(substr($line,$I,1),		'',		-1,		-1,		($ALL_VALID)?'':'unknown',		'',				-1);
		}
	}
	
	/// Here there's no alignment compared to refseq, so we just need to look at the exons, which are grouped by chromosome.
	foreach ($TR_DATA['EXONS'] as $CHR_NAME=>&$EXONS_INFO)
	{
		$LOG_DEBUG.="##############################################".$CHR_NAME."\tVALID:".$ALL_VALID."\n";
		/// Initiating.
		/// By default, it's failing until we pass all the steps
		$TR_DATA['SEQ_VALID'][$CHR_NAME]=false;
		/// WE copy the template array previously created and associate it to the chromosome
		$TR_DATA['SEQ'][$CHR_NAME]=$TMP_SEQ;
		$LOG_STR=$TRANSCRIPT_NAME.':'.$CHR_NAME;
		
		
		

		/// finding the chromosome sequence ID
		if (!isset($ASSEMBLY_DATA['CHR_SEQ'][$CHR_NAME]))
		{
			if (isset($ASSEMBLY_DATA['CHR_SEQ']['chr'.$CHR_NAME]))
			{
				$CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ']['chr'.$CHR_NAME];
			}
			else if (substr($CHR_NAME,0,4)=='CHR_')
			{
				if (!isset($ASSEMBLY_DATA['CHR_SEQ'][substr($CHR_NAME,4)]))
				{
					$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHROMOSOME_NOT_FOUND']++;
					echo "CHROMOSOME NOT IN DB\t".$LOG_STR."\n";
					continue;
					
				}else $CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ'][substr($CHR_NAME,4)];

			}
			else if (isset($ASSEMBLY_DATA['CHR_SEQ']['Chr'.$CHR_NAME]))
			{
				$CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ']['Chr'.$CHR_NAME]; 
			}
			else 
			{
				$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHROMOSOME_NOT_FOUND']++;
				echo "CHROMOSOME NOT IN DB\t".$LOG_STR."\n";
				continue;
			}
		}
		else $CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ'][$CHR_NAME];
		$LOG_STR.="::".$ASSEMBLY_DATA['CHR_INFO'][$CHR_SEQ_ID]['seq_role']."\t";
	
		/// $TR_DATA['SEQ'] in previous step contains the same identical sequence repeated for each chromosome sequence
		/// based on the gff file. So technically, we must find that chromosome name in this array
		if (!isset($TR_DATA['SEQ'][$CHR_NAME]))
		{
			$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHR_NOT_PRESENT']++;
			echo "CHROMOSOME NOT MAP TO TRANSCRIPT\t".$LOG_STR."\n";
			continue;
		}
		if (!isset($TR_DATA['DNA_POS'][$CHR_SEQ_ID]))
		{
			$STATS[$ASSEMBLY_ACC]['MISSING_DNA']++;
			echo "WRONG TRANSCRIPT_NAME\t".$LOG_STR."\n";
			continue;
		}
		$SEQUENCE=&$TR_DATA['SEQ'][$CHR_NAME];
		

		$CHR_INFO=&$ASSEMBLY_DATA['CHR_INFO'][$CHR_SEQ_ID];
		
		/// $ASSEMBLY_DATA contains the database info
		/// So we check that there is an entry for this transcript name and this chromosome/patch/scaffold seqience
		if (!isset($ASSEMBLY_DATA['TR'][$TRANSCRIPT_NAME][$CHR_SEQ_ID]))
		{
			$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_TR_NOT_IN_DB']++;
			echo "TRANSCRIPT_CHROMOSOME NOT FOUND\t".$LOG_STR."\n";
			continue;
		}
		
		$TR_DB_INFO=&$ASSEMBLY_DATA['TR'][$TRANSCRIPT_NAME][$CHR_SEQ_ID];

		if ($ALL_VALID)
		{
			/// Now we do the mapping DNA/RNA
			$ALIGNMENT=array();
			$REVERSE=array('A'=>'T','T'=>'A','C'=>'G','G'=>'C','N'=>'N');
			/// getting the DNA sequence based on the chromosome
			$DNA_SEQ=&$TR_DATA['DNA_POS'][$CHR_SEQ_ID];
			$HAS_MISSING_DNA=false;
			$VALID=true;

			/// Depending on the strand, the process is different
			if ($TR_DB_INFO['strand']=="+")
			{
				/// STR_SEQ is the complete transcript sequence
				$STR_SEQ='';
				foreach ($TMP_SEQ as $P=>$D)$STR_SEQ.= $D[0];
				/// STR_DNA is the complete transcript sequence as built from the DNA
				$STR_DNA='';
				/// Position in the transcript sequence.
				$POS=0;

				//												AL_RNA	AL_ID,	EXON	TYPE   	DNA_BASE		CHR_SEQ_POS_ID
				//$TMP_SEQ[$POS_SEQ]=array(substr($line,$I,1),		'',		-1,		-1,		'',		'',		-1);

				/// Here we look at each exon
				foreach ($EXONS_INFO as $EXON_ID=>$EXON)
				{
					if (!is_numeric($EXON[0]))continue;
					$LOG_DEBUG.= "EXONID:".$EXON_ID."\n";

					/// and going at each DNA position of that exon
					for ($I=$EXON[1];$I<=$EXON[2];++$I)
					{
						/// I_SHIFT. If the chromosome sequence is a located scaffold or patch, then there's a shift between the exon number (which are provided based on the overall sequence)
						/// AND the position in the actual scaffold/patch sequence
						/// so we shift that numbering by substracting the starting position of that patch/scaffold
						$I_SHIFT=$I;
						if (isset($DIFF_DNA_POS[$CHR_SEQ_ID]))$I_SHIFT=$I-$DIFF_DNA_POS[$CHR_SEQ_ID];
						$LOG_DEBUG.= $I."\t".$I_SHIFT."\t";
						if ($I_SHIFT<=0 || $I>=$CHR_INFO['chr_end_pos'])
						{
							$LOG_DEBUG.= "RULE2\t";
							++$POS;
							if (!isset($ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']])
							||!isset($TR_DATA['DNA_POS'][$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id']][$I])){$LOG_DEBUG.= "NO\n";$HAS_MISSING_DNA=true;continue;}
							$LOG_DEBUG.= "IN\n";
							$DNA_ALT_POS=$TR_DATA['DNA_POS'][$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id']][$I];
							//	echo $I_SHIFT."\n";
							
							$SEQUENCE[$POS][2]=$I;/// Position in the sequence 
							$SEQUENCE[$POS][5]=$DNA_ALT_POS[0];///DNA Nucleotide
							$SEQUENCE[$POS][6]=$DNA_ALT_POS[1];///DNA DB ID
							
							$STR_DNA.= strtoupper($DNA_ALT_POS[0]);
						}
						else 
						{
							++$POS;
							$LOG_DEBUG.= "RULE1\t";
							/// Now that position should be listed in the DNA Seq
							if (!isset($DNA_SEQ[$I_SHIFT])) {$LOG_DEBUG.="NO\n";$HAS_MISSING_DNA=true;continue;}
							$LOG_DEBUG.= "IN\n";
							
							$SEQUENCE[$POS][2]=$I_SHIFT;/// Position in the sequence 
							$SEQUENCE[$POS][5]=$DNA_SEQ[$I_SHIFT][0];///DNA Nucleotide
							$SEQUENCE[$POS][6]=$DNA_SEQ[$I_SHIFT][1];///DNA DB ID
							
							$STR_DNA.=strtoupper($DNA_SEQ[$I_SHIFT][0]);
						}
						$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$SEQUENCE[$POS])."\t".$TR_DB_INFO['strand']."\n";
					}
				}
				
				if (strtoupper($STR_DNA)!=strtoupper($STR_SEQ) && 
					strpos($STR_DNA,$STR_SEQ)===false && 
					strpos($STR_SEQ,$STR_DNA)===false)
				{
					$LOG_DEBUG.= $STR_DNA."\n#\n";
					$LOG_DEBUG.= $STR_SEQ."\n";
					foreach ($SEQUENCE as $POS=>$INFO)
					{
						//$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[6]."|";
						$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
						
					}
			
					$LOG_DEBUG.= "DIFFERENT ALIGNMENT\t".$LOG_STR."\t+\n";
					$VALID=false;
					
				}
				
				
			}
			else 
			{
				$STR_SEQ='';
				foreach ($TMP_SEQ as $P=>$D)$STR_SEQ.= $D[0];
				$STR_DNA='';
				$POS=0;
				foreach ($EXONS_INFO as $EXON_ID=>$EXON)
				{
					$LOG_DEBUG.= "EXONID:".$EXON_ID."\t".$EXON[0]."\n";
					if (!is_numeric($EXON[0]))continue;
					for ($I=$EXON[2];$I>=$EXON[1];--$I)
					{
						/// I_SHIFT. If the chromosome sequence is a located scaffold or patch, then there's a shift between the exon number (which are provided based on the overall sequence)
						/// AND the position in the actual scaffold/patch sequence
						/// so we shift that numbering by substracting the starting position of that patch/scaffold
						
						$I_SHIFT=$I;
						if (isset($DIFF_DNA_POS[$CHR_SEQ_ID]))$I_SHIFT=$I-$DIFF_DNA_POS[$CHR_SEQ_ID];
						$LOG_DEBUG.= $I."\t".$I_SHIFT."\t";
						/// Now that position should be listed in the DNA Seq
						if ($I_SHIFT<=0 || $I>=$CHR_INFO['chr_end_pos'])
						{
							$LOG_DEBUG.= "RULE2\t";
							++$POS;
							if (!isset($ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]) ||!isset($TR_DATA['DNA_POS'][$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id']][$I]))
							{
								$LOG_DEBUG.="NO\n";
								$HAS_MISSING_DNA=true;
								continue;
							}
							$LOG_DEBUG.="IN\n";
							$DNA_ALT_POS=$TR_DATA['DNA_POS'][$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id']][$I];
						//	echo $I_SHIFT."\n";
							
							$SEQUENCE[$POS][2]=$I;/// Position in the sequence 
							$SEQUENCE[$POS][5]=$REVERSE[strtoupper($DNA_ALT_POS[0])];///DNA Nucleotide
							$SEQUENCE[$POS][6]=$DNA_ALT_POS[1];///DNA DB ID
							
							$STR_DNA.= $REVERSE[strtoupper($DNA_ALT_POS[0])];
						}
						else 
						{
							++$POS;
							$LOG_DEBUG.= "RULE1\t";
						
							if (!isset($DNA_SEQ[$I_SHIFT])) {$LOG_DEBUG.="NO\n";$HAS_MISSING_DNA=true;continue;}
							$LOG_DEBUG.="YES\n";
							$SEQUENCE[$POS][2]=$I_SHIFT;/// Position in the sequence 
							$SEQUENCE[$POS][5]=$REVERSE[strtoupper($DNA_SEQ[$I_SHIFT][0])];///DNA Nucleotide
							$SEQUENCE[$POS][6]=$DNA_SEQ[$I_SHIFT][1];///DNA DB ID
							
							$STR_DNA.= $REVERSE[strtoupper($DNA_SEQ[$I_SHIFT][0])];
						}
						$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$SEQUENCE[$POS])."\t".$TR_DB_INFO['strand']."\n";
					}
				}
				/// The sequences should be identical
				if (strtoupper($STR_DNA)!=strtoupper($STR_SEQ))
				{
					$LOG_DEBUG.="DIFFERENT ALIGNMENT\t".$LOG_STR."\t-\n";
					$LOG_DEBUG.= $STR_DNA."\n";
					$LOG_DEBUG.=$STR_SEQ."\n";
					$VALID=false;
				}
			}
			/// If we missed any position, we stop there.
			if ($HAS_MISSING_DNA)
			{	$VALID=false;
				echo "MISSING_DNA_POSITION\t".$LOG_STR."\n";
			}


			/// When all went well, we assign the type and exon id
			if ($VALID)
			{
				/// But that can fail too!, so we set the outcome of the assignation to $VALID so it can be tested again
				$VALID=assignExonCDS($SEQUENCE,$EXONS_INFO,'ENS',$ASSEMBLY_ACC,$LOG_DEBUG);
			
				$STATS[$ASSEMBLY_ACC]['SUCCESS_TRANSCRIPT_CHR']++;

				$LOG_STR.="PREP_SUCCESS\t";
			}
			/// If it fail, ALL exon will be set to -1, all types to unknown and DNA position to -1
			/// This way, we still have the transcript sequence, but without annotation
			if (!$VALID)
			{
				foreach ($SEQUENCE as $POS=>&$POS_DATA)
				{
					$POS_DATA[3]=-1; 
					$POS_DATA[4]='unknown';
					$POS_DATA[6]=-1;
				}
			}
		}
		
		/// STR_MD5 is a long string containing for each position the position itself the nucleotide, the type, exon id and database ID.
		/// Should any of those values differs, then the md5hash will be different
		$STR_MD5='';
		//if ($HAS_INF)
		foreach ($SEQUENCE as $POS=>$INFO)
		{
			$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[6]."|";
			$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
			
		}
		if (!$VALID|| !$ALL_VALID)echo "#######\n\n\n\nDEBUG\n\n\n\n\n". $LOG_DEBUG;
		
		
		$MD5=md5($STR_MD5);
		$LOG_STR.= $MD5."<>".$TR_DB_INFO['seq_hash']."\t";
		$res=runQuery("SELECT count(*) co FROM transcript_pos where  transcript_id = ".$TR_DB_INFO['transcript_id']);
		
		$LEN_DB_TR=$res[0]['co'];
		$LOG_STR.= $LEN_DB_TR."<>".count($SEQUENCE)."\t";

		/// And so we check that both the length and the md5hash are identical as we is in the database.
		if ($MD5==$TR_DB_INFO['seq_hash'] && $LEN_DB_TR==count($SEQUENCE)){
			echo "SUCCESS\t".$LOG_STR."\tIDENTICAL\n";
			$STATS[$ASSEMBLY_ACC]['SAME_TRANSCRIPT_FROM_DB']++;
			if ($TR_DB_INFO['valid_alignment']!='T')
			{
				$res=runQueryNoRes("UPDATE transcript set valid_alignment='T' WHERE transcript_id = ".$TR_DB_INFO['transcript_id']);
				if ($res===false)	failProcess($JOB_ID."E02",'Unable to update transcript');
			}
			continue;
		}
		/// Otherwise, we will have to delete the sequence and insert it again
		if ($LEN_DB_TR!=count($SEQUENCE))
		{
			$STATS[$ASSEMBLY_ACC]['DIFF_LENGTH_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="LENGTH_DIFFERENT_FROM_DB\t";
		}
		
		if ($TR_DB_INFO['seq_hash']!=''){$STATS[$ASSEMBLY_ACC]['DIFF_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="DIFF_TRANSCRIPT_FROM_DB\t";
		}
		else {$STATS[$ASSEMBLY_ACC]['NEW_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="NEW_TRANSCRIPT\t";
		}
		//echo $LOG_DEBUG;	


		/// So we need to delete a couple of related tables before deleting the trancsript sequence
		$res=runQueryNoRes("DELETE FROM variant_transcript_map where transcript_id = ".$TR_DB_INFO['transcript_id']);
		if ($res ===false)
		{
			echo "FAILED_DELETION VARIANT TRANSCRIPT MAP\t".$LOG_STR."\n";
			$STATS[$ASSEMBLY_ACC]['FAILED_DELETION']++;
			continue;
		}
		$res=runQueryNoRes("DELETE FROM tr_protseq_al where transcript_id = ".$TR_DB_INFO['transcript_id']);
		if ($res ===false)
		{
			echo "FAILED_DELETION PROTSEQ\t".$LOG_STR."\n";
			$STATS[$ASSEMBLY_ACC]['FAILED_DELETION']++;
			continue;
		}
		$res=runQueryNoRes("DELETE FROM transcript_pos where transcript_id = ".$TR_DB_INFO['transcript_id']);
		if ($res ===false)
		{
			echo "FAILED_DELETION\t".$LOG_STR."\n";
			$STATS[$ASSEMBLY_ACC]['FAILED_DELETION']++;
			continue;
		}
		unset($STR_MD5);
		
		/// Then we update the transcript information
		$res=runQueryNoRes(
			"UPDATE transcript 
			set seq_hash = '".$MD5."',
				valid_alignment='".(($ALL_VALID && $VALID)?"T":"F")."' 
			WHERE transcript_id = ".$TR_DB_INFO['transcript_id']);
		$STR_FILE='';$HAS_UNK=false;

		/// And we insert the overall sequence in the output file for insertion
		foreach ($SEQUENCE as $POS=>$INFO)
		{
			if (!isset($SEQ_TYPES[$INFO[4]]))
			{
				$INFO[4]='unknown';
				$HAS_UNK=true;
			}
			
		

			$STR_FILE.=				$TR_DB_INFO['transcript_id']."\t".
			$INFO[0]."\t".$POS."\t".$SEQ_TYPES[$INFO[4]]."\t";
			if ($INFO[3]==-1||$INFO[3]=='')$STR_FILE.="NULL\t";else $STR_FILE.=$INFO[3]."\t";
			if ($INFO[6]==-1)$STR_FILE.="NULL\n";else $STR_FILE.=$INFO[6]."\n";
			
			

			// //	(transcript_pos_id ,transcript_id     ,nucl              ,seq_pos           ,seq_pos_type_id   ,exon_id           ,chr_seq_pos_id)',);
			// 	$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[5]."|";
			// 	echo $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
		}
		if ($HAS_UNK)$LOG_STR.="UNK_POSITION\t";
		echo "SUCCESS\t".$LOG_STR."\n";
		fputs($FILE['transcript_pos'],$STR_FILE);


		// foreach ($SEQUENCE as $POS=>$INFO)
		// {
		// 	echo $TRANSCRIPT_NAME."\t".$CHR_NAME."\t".$POS."\t".implode("\t",$INFO)."\n";
		// }
		
	}



	
	//print_r($TR_DATA);
	//exit;


return true;
}


/// Mitochrondrial transcript does not have a transcript sequence, so we need to get the sequence directly from the DNA
function processRefSeqMT(&$ASSEMBLY_DATA,&$FILES,&$TRANSCRIPT_NAME,&$POINTERS,$ASSEMBLY_ACC,&$TR_DATA)
{
	global $FILES;
	global $FILE;
	global $SEQ_TYPES;
	global $DBIDS;
	global $STATS;
	global $TASK_ID;
	global $GLB_VAR;
	global $W_DIR;
	$LOG_STR=$TRANSCRIPT_NAME."\tMT";

	/// Get CHR_SEQ_ID
	$tmp_info=explode("\t",$POINTERS[2]);
	$CHR_SEQ_ID=-1;$CHR_NAME='';
	foreach ($tmp_info as $t)
	{
		$tab=explode("|",$t);
		$CHR_NAME=$tab[0];
		if (!isset($ASSEMBLY_DATA['CHR_SEQ'][$tab[0]]))
		{
			$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHROMOSOME_NOT_FOUND']++;
			echo "CHROMOSOME NOT IN DB\t".$LOG_STR."\n";
			return false;
		}
		if ($CHR_SEQ_ID!=-1 &&$CHR_SEQ_ID!=$ASSEMBLY_DATA['CHR_SEQ'][$tab[0]] )
		{
			$STATS[$ASSEMBLY_ACC]['MT_MULTI']++;
			echo "MT_MULTI\t".$LOG_STR."\n";
			return false;
		}
		$CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ'][$tab[0]];
	}

	/// Get Transcript info
	$tab=explode(".",$TRANSCRIPT_NAME);
	if (!isset($ASSEMBLY_DATA['TR'][$tab[0]][$CHR_SEQ_ID]))
	{
		$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_TR_NOT_IN_DB']++;
		echo "TRANSCRIPT_CHROMOSOME NOT FOUND\t".$LOG_STR."\n";
		return false;
	}
	$TR_DB_INFO=&$ASSEMBLY_DATA['TR'][$tab[0]][$CHR_SEQ_ID];


	$REVERSE=array('A'=>'T','T'=>'A','C'=>'G','G'=>'C','N'=>'N');
	$SEQUENCE=array();
	$DIFF_SEQ=0;
	$HAS_MISSING_DNA=false;
	
	//echo "STR:".$TR_DB_INFO['strand']."\n";
	if ($TR_DB_INFO['strand']=='+')
	{
		$POS_NUCL=0;
		/// So we loop over each DNA position to create the "transcript"
		foreach ($TR_DATA['DNA_POS'][$CHR_SEQ_ID] as $POS_DNA=>&$INFO_DNA)
		{
			++$POS_NUCL;
			$NUCL=strtoupper($INFO_DNA[0]);
			//						 		AL_RNA	AL_ID,	EXON	TYPE   	DNA_BASE		CHR_SEQ_POS_ID
			$SEQUENCE[$POS_NUCL]=array($NUCL,$NUCL,$POS_DNA,-1,		'',			$NUCL,		$INFO_DNA[1]);	
			
			
		}
	}
	
	else 
	{
		//print_r($TR_DATA['DNA_POS'][$CHR_SEQ_ID]);
		$POS=0;
		$LEN=count($SEQUENCE);
		/// So we loop over each DNA position to create the "transcript" but in reverse order
		$res=array_keys($TR_DATA['DNA_POS'][$CHR_SEQ_ID]);
		$POS_NUCL=0;
		for ($I=count($res)-1;$I>=0;--$I)
		{
			$POS_DNA=$res[$I];
			$INFO_DNA=&$TR_DATA['DNA_POS'][$CHR_SEQ_ID][$POS_DNA];
			
			++$POS_NUCL;
			$NUCL=$REVERSE[strtoupper($INFO_DNA[0])];
			//						 		AL_RNA	AL_ID,	EXON	TYPE   	DNA_BASE		CHR_SEQ_POS_ID
			$SEQUENCE[$POS_NUCL]=array($NUCL,$NUCL,$POS_DNA,-1,'',$NUCL,$INFO_DNA[1]);	
			
			
		}
		//exit;

	}
		
		
	assignExonCDS($SEQUENCE,$TR_DATA['EXONS'][$CHR_NAME],'RS',$ASSEMBLY_ACC,$LOG_DEBUG);
				

	$STATS[$ASSEMBLY_ACC]['SUCCESS_TRANSCRIPT_CHR']++;

	$LOG_STR.="PREP_SUCCESS\t";

	
	/// STR_MD5 is a long string containing for each position the position itself the nucleotide, the type, exon id and database ID.
	/// Should any of those values differs, then the md5hash will be different
	$STR_MD5='';
	foreach ($SEQUENCE as $POS=>$INFO)
	{
		$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[6]."|";
		// echo $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
		// if ($INFO[4]=='' || $INFO[6]=='')exit;
		//	echo $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
		
	}//print_r($TR_DB_INFO);
	
	/// Creating MD5 Hash
	$MD5=md5($STR_MD5);
	$LOG_STR.= $MD5."<>".$TR_DB_INFO['seq_hash']."\t";
	$res=runQuery("SELECT count(*) co FROM transcript_pos where  transcript_id = ".$TR_DB_INFO['transcript_id']);
	
	$LEN_DB_TR=$res[0]['co'];
	$LOG_STR.= $LEN_DB_TR."<>".count($SEQUENCE)."\t";

	/// Now we need to compare whether the hash and the sequence length is the same in the db vs what we generated
	/// If it's the same, we have nothing to do
	if ($MD5==$TR_DB_INFO['seq_hash'] && $LEN_DB_TR==count($SEQUENCE)){
		echo "SUCCESS\t".$LOG_STR."\tIDENTICAL\n";
		$STATS[$ASSEMBLY_ACC]['SAME_TRANSCRIPT_FROM_DB']++;
		if ($TR_DB_INFO['valid_alignment']!='T')$res=runQueryNoRes("UPDATE transcript set valid_alignment='T' WHERE transcript_id = ".$TR_DB_INFO['transcript_id']);
		return true;
	}
	//exit;
	if ($LEN_DB_TR!=count($SEQUENCE))
	{
		$STATS[$ASSEMBLY_ACC]['DIFF_LENGTH_TRANSCRIPT_FROM_DB']++;
		$LOG_STR.="LENGTH_DIFFERENT_FROM_DB\t";
	}
	
	if ($TR_DB_INFO['seq_hash']!='')
	{
		$STATS[$ASSEMBLY_ACC]['DIFF_TRANSCRIPT_FROM_DB']++;
		$LOG_STR.="DIFF_TRANSCRIPT_FROM_DB\t";
	}
	else 
	{
		$STATS[$ASSEMBLY_ACC]['NEW_TRANSCRIPT_FROM_DB']++;
		$LOG_STR.="NEW_TRANSCRIPT\t";
	}
	$res=runQueryNoRes("DELETE FROM transcript_pos where transcript_id = ".$TR_DB_INFO['transcript_id']);
	if ($res ===false)
	{
		echo "FAILED_DELETION\t".$LOG_STR."\n";
		$STATS[$ASSEMBLY_ACC]['FAILED_DELETION']++;
		return true;
	}
	unset($STR_MD5);
	
	$UPDATE_HASH[$TR_DB_INFO['transcript_id']]=$MD5;
	$res=runQueryNoRes("UPDATE transcript set seq_hash = '".$MD5."' WHERE transcript_id = ".$TR_DB_INFO['transcript_id']);
	$STR_FILE='';$HAS_UNK=false;

	foreach ($SEQUENCE as $POS=>$INFO)
	{
		if (!isset($SEQ_TYPES[$INFO[4]]))
		{
			$INFO[4]='unknown';
			$HAS_UNK=true;
		}
		
		
		$STR_FILE.=				$TR_DB_INFO['transcript_id']."\t".
		$INFO[0]."\t".$POS."\t".$SEQ_TYPES[$INFO[4]]."\t";
		if ($INFO[3]==-1||$INFO[3]=='')$STR_FILE.="NULL\t";else $STR_FILE.=$INFO[3]."\t";
		if ($INFO[6]==-1)$STR_FILE.="NULL\n";else $STR_FILE.=$INFO[6]."\n";
		
		

		// //	(transcript_pos_id ,transcript_id     ,nucl              ,seq_pos           ,seq_pos_type_id   ,exon_id           ,chr_seq_pos_id)',);
		// 	$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[5]."|";
		// 	echo $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
	}
	if ($HAS_UNK)$LOG_STR.="UNK_POSITION\t";
	echo "SUCCESS\t".$LOG_STR."\n";
	fputs($FILE['transcript_pos'],$STR_FILE);

	
		
		
	return true;
}

function processRefSeqNoBAM(&$ASSEMBLY_DATA,&$FILES,&$TRANSCRIPT_NAME,&$POINTERS,$ASSEMBLY_ACC)
{
	global $FILES;
	global $FILE;
	global $SEQ_TYPES;
	global $DBIDS;
	global $STATS;
	global $TASK_ID;
	global $GLB_VAR;
	global $W_DIR;

	$LOG_DEBUG="NO BAM FILE\t";
	$STATS[$ASSEMBLY_ACC]['PROCESSED_TRANSCRIPT']++;
	//	print_r($POINTERS);

	if (!isset($POINTERS[2])){$STATS[$ASSEMBLY_ACC]['MISSING_EXONS']++;return false;}


	$TR_DATA=array();
	$tmp_info=explode("\t",$POINTERS[2]);
	$TR_DATA['EXONS']=array();
	$TR_DATA['DNA_POS']=array();
	$query="SELECT CHR_SEQ_POS_ID, nucl,chr_pos,chr_seq_id FROM chr_seq_pos WHERE ";
	foreach ($tmp_info as $t)
	{
		$tab=explode("|",$t);
		$TR_DATA['EXONS'][$tab[0]][]=array($tab[1],$tab[2],$tab[3]);
		
		$L=$tab[2];
		$R=$tab[3];
		$tab[2]=min($L,$R);
		$tab[3]=max($L,$R);
		$query .="(chr_seq_id = ".$ASSEMBLY_DATA['CHR_SEQ'][$tab[0]].' AND chr_pos >= '.$tab[2].' AND chr_pos <='.$tab[3].') OR';
	}
	$LOG_DEBUG.= substr($query,0,-4).')'."\n";
	$res=runQuery(substr($query,0,-4).')');
	foreach ($res as $line)
	$TR_DATA['DNA_POS'][$line['chr_seq_id']][$line['chr_pos']]=array($line['nucl'],$line['chr_seq_pos_id']);
	
	
	foreach ($TR_DATA['DNA_POS'] as $CHR_SEQ_ID=>&$CHR_SEQ_INFO)
	{
	if (in_array($CHR_SEQ_ID,$ASSEMBLY_DATA['MT'])) {return processRefSeqMT($ASSEMBLY_DATA,$FILES,$TRANSCRIPT_NAME,$POINTERS,$ASSEMBLY_ACC,$TR_DATA);}
	}
	
	
	if (!isset($POINTERS[0])){$STATS[$ASSEMBLY_ACC]['MISSING_SEQUENCE']++;return false;}
	
	


	//print_r($TR_DATA);exit;
	//Get Fasta sequence
	fseek($FILES['RS_RNA'],$POINTERS[0]);
	$line=stream_get_line($FILES['RS_RNA'],1000,"\n");
	$pos=strpos($line,' ');
	$name=substr($line,1,$pos-1);
	//echo $name."\t".$TRANSCRIPT_NAME."\n";
	if ($name!=$TRANSCRIPT_NAME)
	{
		if ((!isset($POINTERS[4]) || $POINTERS[4]!=$name) ){$STATS[$ASSEMBLY_ACC]['SEQUENCE_WRONG_NAME']++;return false;}
	} 
	$TMP_SEQ=array();$POS_SEQ=0;
	while(!feof($FILES['RS_RNA']))
	{
		$line=stream_get_line($FILES['RS_RNA'],1000,"\n");
		if (substr($line,0,1)=='>')break;
		
		for ($I=0;$I<strlen($line);++$I)
		{
			$POS_SEQ++;
			//							AL_RNA	AL_ID,	EXON	TYPE   	DNA_BASE		CHR_SEQ_POS_ID
			$TMP_SEQ[$POS_SEQ]=array(substr($line,$I,1),'',-1,-1,'','',-1);
		}
	}
	
	$ALL_VALID=true;
	foreach ($TR_DATA['EXONS'] as $CHR_NAME=>&$EXONS_INFO)
	{
		$LOG_STR=$TRANSCRIPT_NAME.':'.$CHR_NAME."\t";
		$TR_DATA['SEQ_VALID'][$CHR_NAME]=false;
		$TR_DATA['SEQ'][$CHR_NAME]=$TMP_SEQ;
			
			
		/// Now, we must have this chromosome in the database
		if (!isset($ASSEMBLY_DATA['CHR_SEQ'][$CHR_NAME])){$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHROMOSOME_NOT_FOUND']++;echo "CHROMOSOME NOT IN DB\t".$LOG_STR."\n";continue;}
		/// to get its database id
		$CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ'][$CHR_NAME];

		/// $TR_DATA['SEQ'] in previous step contains the same identical sequence repeated for each chromosome sequence
		/// based on the gff file. So technically, we must find that chromosome name in this array
		if (!isset($TR_DATA['SEQ'][$CHR_NAME])){$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHR_NOT_PRESENT']++;echo "CHROMOSOME NOT MAP TO TRANSCRIPT\t".$LOG_STR."\n";continue;}
		if (!isset($TR_DATA['DNA_POS'][$CHR_SEQ_ID])){$STATS[$ASSEMBLY_ACC]['MISSING_DNA']++;echo "WRONG TRANSCRIPT_NAME\t".$LOG_STR."\n";continue;}
		$LOG_STR.="::".$ASSEMBLY_DATA['CHR_INFO'][$CHR_SEQ_ID]['seq_role']."\t";
	
		/// $TR_DATA['SEQ'] in previous step contains the same identical sequence repeated for each chromosome sequence
		/// based on the gff file. So technically, we must find that chromosome name in this array
		if (!isset($TR_DATA['SEQ'][$CHR_NAME])){$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHR_NOT_PRESENT']++;echo "CHROMOSOME NOT MAP TO TRANSCRIPT\t".$LOG_STR."\n";continue;}
		if (!isset($TR_DATA['DNA_POS'][$CHR_SEQ_ID])){$STATS[$ASSEMBLY_ACC]['MISSING_DNA']++;echo "WRONG TRANSCRIPT_NAME\t".$LOG_STR."\n";continue;}
		$SEQUENCE=&$TR_DATA['SEQ'][$CHR_NAME];
		

		$CHR_INFO=&$ASSEMBLY_DATA['CHR_INFO'][$CHR_SEQ_ID];
		
		/// $ASSEMBLY_DATA contains the database info
		/// So we check that there is an entry for this transcript name and this chromosome/patch/scaffold seqience
		$tab=explode(".",$TRANSCRIPT_NAME);
		if (!isset($ASSEMBLY_DATA['TR'][$tab[0]][$CHR_SEQ_ID])){$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_TR_NOT_IN_DB']++;echo "TRANSCRIPT_CHROMOSOME NOT FOUND\t".$LOG_STR."\n";continue;}
		$TR_DB_INFO=&$ASSEMBLY_DATA['TR'][$tab[0]][$CHR_SEQ_ID];
		
		
		
		if ($ALL_VALID)
		{
			/// Now we do the mapping DNA/RNA
			$ALIGNMENT=array();
			$REVERSE=array('A'=>'T','T'=>'A','C'=>'G','G'=>'C','N'=>'N');
			/// getting the DNA sequence based on the chromosome
			$DNA_SEQ=&$TR_DATA['DNA_POS'][$CHR_SEQ_ID];
			$HAS_MISSING_DNA=false;
			$VALID=true;

			/// Depending on the strand, the process is different
			if ($TR_DB_INFO['strand']=="+")
			{
				/// STR_SEQ is the complete transcript sequence
				$STR_SEQ='';
				foreach ($TMP_SEQ as $P=>$D)$STR_SEQ.= $D[0];
				/// STR_DNA is the complete transcript sequence as built from the DNA
				$STR_DNA='';
				/// Position in the transcript sequence.
				$POS=0;

				//												AL_RNA	AL_ID,	EXON	TYPE   	DNA_BASE		CHR_SEQ_POS_ID
				//$TMP_SEQ[$POS_SEQ]=array(substr($line,$I,1),		'',		-1,		-1,		'',		'',		-1);

				/// Here we look at each exon
				foreach ($EXONS_INFO as $EXON_ID=>$EXON)
				{
					if (!is_numeric($EXON[0]))continue;
				$LOG_DEBUG.= "EXONID:".$EXON_ID."\n";

					/// and going at each DNA position of that exon
					for ($I=$EXON[1];$I<=$EXON[2];++$I)
					{
						/// I_SHIFT. If the chromosome sequence is a located scaffold or patch, then there's a shift between the exon number (which are provided based on the overall sequence)
						/// AND the position in the actual scaffold/patch sequence
						/// so we shift that numbering by substracting the starting position of that patch/scaffold
						$I_SHIFT=$I;
						if (isset($DIFF_DNA_POS[$CHR_SEQ_ID]))$I_SHIFT=$I-$DIFF_DNA_POS[$CHR_SEQ_ID];
						$LOG_DEBUG.= $I."\t".$I_SHIFT."\t";
						if ($I_SHIFT<=0 || $I>=$CHR_INFO['chr_end_pos'])
						{
							$LOG_DEBUG.= "RULE2\t";
							++$POS;
							if (!isset($ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']])){echo $ASSEMBLY_ACC."\tChromosome missing: ".$CHR_INFO['chr_num']."\n";continue;}
							if (!isset($TR_DATA['DNA_POS'][$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id']][$I])){echo $ASSEMBLY_ACC."\tChromosome missing: ".$CHR_INFO['chr_num']."\n";$LOG_DEBUG.= "NO\n";$HAS_MISSING_DNA=true;continue;}
							$LOG_DEBUG.= "IN\n";
							$DNA_ALT_POS=$TR_DATA['DNA_POS'][$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id']][$I];
						//	echo $I_SHIFT."\n";
							
							$SEQUENCE[$POS][2]=$I;/// Position in the sequence 
							$SEQUENCE[$POS][5]=$DNA_ALT_POS[0];///DNA Nucleotide
							$SEQUENCE[$POS][6]=$DNA_ALT_POS[1];///DNA DB ID
							
							$STR_DNA.= strtoupper($DNA_ALT_POS[0]);
						}
						else 
						{
							++$POS;
							$LOG_DEBUG.= "RULE1\t";
						/// Now that position should be listed in the DNA Seq
						if (!isset($DNA_SEQ[$I_SHIFT])) {$LOG_DEBUG.="NO\n";$HAS_MISSING_DNA=true;continue;}
						$LOG_DEBUG.= "IN\n";
						
						$SEQUENCE[$POS][2]=$I_SHIFT;/// Position in the sequence 
						$SEQUENCE[$POS][5]=$DNA_SEQ[$I_SHIFT][0];///DNA Nucleotide
						$SEQUENCE[$POS][6]=$DNA_SEQ[$I_SHIFT][1];///DNA DB ID
						
						$STR_DNA.=strtoupper($DNA_SEQ[$I_SHIFT][0]);
						}
						$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$SEQUENCE[$POS])."\t".$TR_DB_INFO['strand']."\n";
					}
				}
				$SCORE=levenshtein(strtoupper($STR_DNA),strtoupper($STR_SEQ));
				$SCORE_FREQ=100;
				if (strlen($STR_DNA)>0)$SCORE_FREQ=round(levenshtein(strtoupper($STR_DNA),strtoupper($STR_SEQ))/strlen($STR_DNA),3)*100;
				if ((!($SCORE<10 || $SCORE > 10 && $SCORE_FREQ<0.5)) && strpos($STR_DNA,$STR_SEQ)===false && strpos($STR_SEQ,$STR_DNA)===false)
				{
					$LOG_DEBUG.= $STR_DNA."\n#\n";
					$LOG_DEBUG.= $STR_SEQ."\n";
					
					foreach ($SEQUENCE as $POS=>$INFO)
					{
						//$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[6]."|";
						$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\t".((strtoupper($INFO[0])!=strtoupper($INFO[5]))?'DIFF':'')."\n";
						
					}
			
					$LOG_DEBUG.= "DIFFERENT ALIGNMENT\t".$LOG_STR."\t+\n";
					$VALID=false;
					
				}
				
				
			}
			else 
			{
				$STR_SEQ='';
				foreach ($TMP_SEQ as $P=>$D)$STR_SEQ.= $D[0];
				$STR_DNA='';
				$POS=0;
				foreach ($EXONS_INFO as $EXON_ID=>$EXON)
				{
					$LOG_DEBUG.= "EXONID:".$EXON_ID."\t".$EXON[0]."\n";
					if (!is_numeric($EXON[0]))continue;
					for ($I=$EXON[2];$I>=$EXON[1];--$I)
					{
						/// I_SHIFT. If the chromosome sequence is a located scaffold or patch, then there's a shift between the exon number (which are provided based on the overall sequence)
						/// AND the position in the actual scaffold/patch sequence
						/// so we shift that numbering by substracting the starting position of that patch/scaffold
						
						$I_SHIFT=$I;
						if (isset($DIFF_DNA_POS[$CHR_SEQ_ID]))$I_SHIFT=$I-$DIFF_DNA_POS[$CHR_SEQ_ID];
						$LOG_DEBUG.= $I."\t".$I_SHIFT."\t";
						/// Now that position should be listed in the DNA Seq
						if ($I_SHIFT<=0 || $I>=$CHR_INFO['chr_end_pos'])
						{
							$LOG_DEBUG.= "RULE2\t";
							++$POS;
							if (!isset($ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']])){echo $ASSEMBLY_ACC."\tChromosome missing: ".$CHR_INFO['chr_num']."\n";continue;}
							if (!isset($TR_DATA['DNA_POS'][$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id']][$I])){$LOG_DEBUG.="NO\n";$HAS_MISSING_DNA=true;continue;}
							$LOG_DEBUG.="IN\n";
							$DNA_ALT_POS=$TR_DATA['DNA_POS'][$ASSEMBLY_DATA['REF_CHROM'][$CHR_INFO['chr_num']]['chr_seq_id']][$I];
						//	echo $I_SHIFT."\n";
							
							$SEQUENCE[$POS][2]=$I;/// Position in the sequence 
							$SEQUENCE[$POS][5]=$REVERSE[strtoupper($DNA_ALT_POS[0])];///DNA Nucleotide
							$SEQUENCE[$POS][6]=$DNA_ALT_POS[1];///DNA DB ID
							
							$STR_DNA.= $REVERSE[strtoupper($DNA_ALT_POS[0])];
						}
						else 
						{
							++$POS;
							$LOG_DEBUG.= "RULE1\t";
						
							if (!isset($DNA_SEQ[$I_SHIFT])) {$LOG_DEBUG.="NO\n";$HAS_MISSING_DNA=true;continue;}
							$LOG_DEBUG.="YES\n";
							$SEQUENCE[$POS][2]=$I_SHIFT;/// Position in the sequence 
							$SEQUENCE[$POS][5]=$REVERSE[strtoupper($DNA_SEQ[$I_SHIFT][0])];///DNA Nucleotide
							$SEQUENCE[$POS][6]=$DNA_SEQ[$I_SHIFT][1];///DNA DB ID
							
							$STR_DNA.= $REVERSE[strtoupper($DNA_SEQ[$I_SHIFT][0])];
						}
						$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$SEQUENCE[$POS])."\t".$TR_DB_INFO['strand']."\n";
					}
				}
				/// The sequences should be identical
				$SCORE=levenshtein(strtoupper($STR_DNA),strtoupper($STR_SEQ));
				$SCORE_FREQ=0;
				if (strlen($STR_DNA)>0)$SCORE_FREQ=round(levenshtein(strtoupper($STR_DNA),strtoupper($STR_SEQ))/strlen($STR_DNA),3)*100;
				if ((!($SCORE<10 || $SCORE > 10 && $SCORE_FREQ<0.5))  && strpos($STR_DNA,$STR_SEQ)===false && strpos($STR_SEQ,$STR_DNA)===false)
				{
					$LOG_DEBUG.="DIFFERENT ALIGNMENT\t".$LOG_STR."\t-\n";
					$LOG_DEBUG.= $STR_DNA."\n";
					$LOG_DEBUG.=$STR_SEQ."\n";
					$VALID=false;
				}
			}
			/// If we missed any position, we stop there.
			if ($HAS_MISSING_DNA){$VALID=false;echo "MISSING_DNA_POSITION\t".$LOG_STR."\n";}


			/// When all went well, we assign the type and exon id
			if ($VALID)
			{
				/// But that can fail too!, so we set the outcome of the assignation to $VALID so it can be tested again
			$VALID=assignExonCDS($SEQUENCE,$EXONS_INFO,'RS_B',$ASSEMBLY_ACC,$LOG_DEBUG);
			
			$STATS[$ASSEMBLY_ACC]['SUCCESS_TRANSCRIPT_CHR']++;

			$LOG_STR.="PREP_SUCCESS\t";
			}
			/// If it fail, ALL exon will be set to -1, all types to unknown and DNA position to -1
			/// This way, we still have the transcript sequence, but without annotation
			if (!$VALID)
			{
				foreach ($SEQUENCE as $POS=>&$POS_DATA){$POS_DATA[3]=-1; $POS_DATA[4]='unknown';$POS_DATA[6]=-1;}
			}
		}



		/// STR_MD5 is a long string containing for each position the position itself the nucleotide, the type, exon id and database ID.
		/// Should any of those values differs, then the md5hash will be different
		$STR_MD5='';
		//if ($HAS_INF)
		foreach ($SEQUENCE as $POS=>$INFO)
		{
			$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[6]."|";
			$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
			
		}
		if (!$VALID|| !$ALL_VALID)
		echo "#######\n\n\n\nDEBUG\n\n\n\n\n". $LOG_DEBUG;
		
		$MD5=md5($STR_MD5);
		$LOG_STR.= $MD5."<>".$TR_DB_INFO['seq_hash']."\t";
		$res=runQuery("SELECT count(*) co FROM transcript_pos where  transcript_id = ".$TR_DB_INFO['transcript_id']);
		
		$LEN_DB_TR=$res[0]['co'];
		$LOG_STR.= $LEN_DB_TR."<>".count($SEQUENCE)."\t";

		/// And so we check that both the length and the md5hash are identical as we is in the database.
		if ($MD5==$TR_DB_INFO['seq_hash'] && $LEN_DB_TR==count($SEQUENCE)){
			echo "SUCCESS\t".$LOG_STR."\tIDENTICAL\n";
			$STATS[$ASSEMBLY_ACC]['SAME_TRANSCRIPT_FROM_DB']++;
			if ($TR_DB_INFO['valid_alignment']!='T')$res=runQueryNoRes("UPDATE transcript set valid_alignment='T' WHERE transcript_id = ".$TR_DB_INFO['transcript_id']);
			continue;
		}
		/// Otherwise, we will have to delete the sequence and insert it again
		if ($LEN_DB_TR!=count($SEQUENCE))
		{
			$STATS[$ASSEMBLY_ACC]['DIFF_LENGTH_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="LENGTH_DIFFERENT_FROM_DB\t";
		}
		
		if ($TR_DB_INFO['seq_hash']!=''){$STATS[$ASSEMBLY_ACC]['DIFF_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="DIFF_TRANSCRIPT_FROM_DB\t";
		}
		else {$STATS[$ASSEMBLY_ACC]['NEW_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="NEW_TRANSCRIPT\t";
		}
		// echo $LOG_DEBUG;		
		// echo $LOG_STR;
		// 			/// So we need to delete a couple of related tables before deleting the trancsript sequence
		$res=runQueryNoRes("DELETE FROM variant_transcript_map where transcript_id = ".$TR_DB_INFO['transcript_id']);
		if ($res ===false){
			echo "FAILED_DELETION VARIANT TRANSCRIPT MAP\t".$LOG_STR."\n";
			$STATS[$ASSEMBLY_ACC]['FAILED_DELETION']++;continue;}
		$res=runQueryNoRes("DELETE FROM tr_protseq_al where transcript_id = ".$TR_DB_INFO['transcript_id']);
		if ($res ===false){
			echo "FAILED_DELETION PROTSEQ\t".$LOG_STR."\n";
			$STATS[$ASSEMBLY_ACC]['FAILED_DELETION']++;continue;}
		$res=runQueryNoRes("DELETE FROM transcript_pos where transcript_id = ".$TR_DB_INFO['transcript_id']);
		if ($res ===false){
			echo "FAILED_DELETION\t".$LOG_STR."\n";
			$STATS[$ASSEMBLY_ACC]['FAILED_DELETION']++;continue;}
		unset($STR_MD5);
		
		/// Then we update the transcript information
		$res=runQueryNoRes("UPDATE transcript set seq_hash = '".$MD5."',valid_alignment='".(($ALL_VALID && $VALID)?"T":"F")."' WHERE transcript_id = ".$TR_DB_INFO['transcript_id']);
		$STR_FILE='';$HAS_UNK=false;

		/// And we insert the overall sequence in the output file for insertion
		foreach ($SEQUENCE as $POS=>$INFO)
		{
			if (!isset($SEQ_TYPES[$INFO[4]]))
			{
				$INFO[4]='unknown';
				$HAS_UNK=true;
			}
			
		

			$STR_FILE.=				$TR_DB_INFO['transcript_id']."\t".
			$INFO[0]."\t".$POS."\t".$SEQ_TYPES[$INFO[4]]."\t";
			if ($INFO[3]==-1||$INFO[3]=='')$STR_FILE.="NULL\t";else $STR_FILE.=$INFO[3]."\t";
			if ($INFO[6]==-1)$STR_FILE.="NULL\n";else $STR_FILE.=$INFO[6]."\n";
			
			

		// //	(transcript_pos_id ,transcript_id     ,nucl              ,seq_pos           ,seq_pos_type_id   ,exon_id           ,chr_seq_pos_id)',);
		// 	$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[5]."|";
		// 	echo $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
		}
		if ($HAS_UNK)$LOG_STR.="UNK_POSITION\t";
		echo "SUCCESS\t".$LOG_STR."\n";
		fputs($FILE['transcript_pos'],$STR_FILE);


		// foreach ($SEQUENCE as $POS=>$INFO)
		// {
		// 	echo $TRANSCRIPT_NAME."\t".$CHR_NAME."\t".$POS."\t".implode("\t",$INFO)."\n";
		// }
	}

return true;


}

function processRefSeq(&$ASSEMBLY_DATA,&$FILES,&$TRANSCRIPT_NAME,&$POINTERS,$ASSEMBLY_ACC,$RS_DIR,$TAX_ID)
{
	global $FILES;
	global $FILE;
	global $SEQ_TYPES;
	global $DBIDS;
	global $STATS;
	global $TASK_ID;
	global $GLB_VAR;
	global $W_DIR;
	
	$LOG_DEBUG='';
	$STATS[$ASSEMBLY_ACC]['PROCESSED_TRANSCRIPT']++;
//	print_r($POINTERS);

	if (!isset($POINTERS[2])){$STATS[$ASSEMBLY_ACC]['MISSING_EXONS']++;return false;}


	$TR_DATA=array();
	$tmp_info=explode("\t",$POINTERS[2]);
	$TR_DATA['EXONS']=array();
	$TR_DATA['DNA_POS']=array();
	$query="SELECT CHR_SEQ_POS_ID, nucl,chr_pos,chr_seq_id FROM chr_seq_pos WHERE ";
	foreach ($tmp_info as $t)
	{
		$tab=explode("|",$t);
		$TR_DATA['EXONS'][$tab[0]][]=array($tab[1],$tab[2],$tab[3]);
		
		$L=$tab[2];
		$R=$tab[3];
		$tab[2]=min($L,$R);
		$tab[3]=max($L,$R);
		$query .="(chr_seq_id = ".$ASSEMBLY_DATA['CHR_SEQ'][$tab[0]].' AND chr_pos >= '.$tab[2].' AND chr_pos <='.$tab[3].') OR';
	}
	$LOG_DEBUG.= substr($query,0,-4).')'."\n";
	echo $LOG_DEBUG."\n";
	$res=runQuery(substr($query,0,-4).')');
	foreach ($res as $line)
	$TR_DATA['DNA_POS'][$line['chr_seq_id']][$line['chr_pos']]=array($line['nucl'],$line['chr_seq_pos_id']);
	
	
	foreach ($TR_DATA['DNA_POS'] as $CHR_SEQ_ID=>&$CHR_SEQ_INFO)
	{
	if (in_array($CHR_SEQ_ID,$ASSEMBLY_DATA['MT'])) {return processRefSeqMT($ASSEMBLY_DATA,$FILES,$TRANSCRIPT_NAME,$POINTERS,$ASSEMBLY_ACC,$TR_DATA);}
	}
	/// For a given transcript, all 3 are required
	if (!isset($POINTERS[0])){$STATS[$ASSEMBLY_ACC]['MISSING_SEQUENCE']++;return false;}
	if (!isset($POINTERS[1])||$POINTERS[1]==''){$STATS[$ASSEMBLY_ACC]['MISSING_ALIGNMENT']++;return false;}
	


	//print_r($TR_DATA);exit;
	//Get Fasta sequence
	fseek($FILES['RS_RNA'],$POINTERS[0]);
	$line=stream_get_line($FILES['RS_RNA'],1000,"\n");
	$pos=strpos($line,' ');
	$name=substr($line,1,$pos-1);
	//echo $name."\t".$TRANSCRIPT_NAME."\n";
	if ($name!=$TRANSCRIPT_NAME)
	{
		if ((!isset($POINTERS[4]) || $POINTERS[4]!=$name) ){$STATS[$ASSEMBLY_ACC]['SEQUENCE_WRONG_NAME']++;return false;}
	} 
	$TMP_SEQ=array();$POS_SEQ=0;
	while(!feof($FILES['RS_RNA']))
	{
		$line=stream_get_line($FILES['RS_RNA'],1000,"\n");
		if (substr($line,0,1)=='>')break;
		for ($I=0;$I<strlen($line);++$I)
		{
			$POS_SEQ++;
			//							AL_RNA	AL_ID,	EXON	TYPE   	DNA_BASE		CHR_SEQ_POS_ID
			$TMP_SEQ[$POS_SEQ]=array(substr($line,$I,1),'',-1,-1,'','',-1);
		}
	}
	
	foreach ($TR_DATA['EXONS'] as $CHR_NAME=>&$dummy)
	{
		$TR_DATA['SEQ_VALID'][$CHR_NAME]=false;
		$TR_DATA['SEQ'][$CHR_NAME]=$TMP_SEQ;
	}



	


	/// $POINTERS[1] contains the starting position of each alignment between the transcript and the different chromsomes/patches/scaffolds
	$tmp_info=explode("\t",$POINTERS[1]);
	//print_R($tmp_info);
	//print_r($ASSEMBLY_DATA);
	foreach ($tmp_info as $pos_align_info)
	{
		
		
		$align_info=explode("|",$pos_align_info);
		$file_p=null;
		if (!isset($align_info[2]))
		{
			echo $pos_align_info."\n";print_r($align_info);exit;
		}
		if ($align_info[2]=='K')$file_p=&$FILES['RS_KNOWN_BAM'];
		else $file_p=&$FILES['RS_MODEL_BAM'];
		
		if ($file_p==null || feof($file_p))openRSFiles($TAX_ID,$RS_DIR);
		
		fseek($file_p,$align_info[1]);
		
		// The first line contains the transcript name, the chromosome sequence
		/// So we are going to compare those two agains the information we have to ensure integrity
		$line=stream_get_line($file_p,1000000,"\n");
		if (is_file($TASK_ID.'_tmp.txt'))unlink($TASK_ID.'_tmp.txt');
		$fp=fopen($TASK_ID.'_tmp.txt','w');
		if ($align_info[2]=='K')fputs($fp,$FILES['KNOWN_HEAD'].$line."\n");
		else fputs($fp,$FILES['MODEL_HEAD'].$line."\n");
		fclose($fp);

					


		$res=array();
		//echo $GLB_VAR['TOOL']['BCFTOOLS'].' mpileup --output-sep  -f '.$RS_DIR.$TAX_ID.'_seq.fa  '.$TASK_ID.'_tmp.txt '."\n";
		//echo 'python3 '.$GLB_VAR['TOOL']['TR_ALIGN'].'  '.$TASK_ID.'_tmp.txt'."\n";
		exec($GLB_VAR['TOOL']['TR_ALIGN'].'  '.$TASK_ID.'_tmp.txt',$res,$return_code);
		if ($return_code!=0){$STATS[$ASSEMBLY_ACC]['FAILED_PYTHON']++;echo "FAILED_PYTHON\t".$LOG_STR."\n";continue;}
if (is_file($TASK_ID.'_tmp.txt'))unlink($TASK_ID.'_tmp.txt');
		$STATS[$ASSEMBLY_ACC]['PROCESS_TRANSCRIPT_CHR']++;
		$CHR_NAME=$align_info[0];
		$LOG_STR=$TRANSCRIPT_NAME.':'.$align_info[0]."\t";


		/// Now, we must have this chromosome in the database
		if (!isset($ASSEMBLY_DATA['CHR_SEQ'][$CHR_NAME])){$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHROMOSOME_NOT_FOUND']++;echo "CHROMOSOME NOT IN DB\t".$LOG_STR."\n";continue;}
		/// to get its database id
		$CHR_SEQ_ID=$ASSEMBLY_DATA['CHR_SEQ'][$CHR_NAME];

		/// $TR_DATA['SEQ'] in previous step contains the same identical sequence repeated for each chromosome sequence
		/// based on the gff file. So technically, we must find that chromosome name in this array
		if (!isset($TR_DATA['SEQ'][$CHR_NAME])){$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_CHR_NOT_PRESENT']++;echo "CHROMOSOME NOT MAP TO TRANSCRIPT\t".$LOG_STR."\n";continue;}
		if (!isset($TR_DATA['DNA_POS'][$CHR_SEQ_ID])){$STATS[$ASSEMBLY_ACC]['MISSING_DNA']++;echo "WRONG TRANSCRIPT_NAME\t".$LOG_STR."\n";print_R($TR_DATA);echo $CHR_SEQ_ID.' '.$CHR_NAME."\n";exit;continue;}
			$SEQUENCE=&$TR_DATA['SEQ'][$CHR_NAME];

		
		/// $ASSEMBLY_DATA contains the database info
		/// So we check that there is an entry for this transcript name and this chromosome/patch/scaffold seqience
		$tab=explode(".",$TRANSCRIPT_NAME);
		if (!isset($ASSEMBLY_DATA['TR'][$tab[0]][$CHR_SEQ_ID])){$STATS[$ASSEMBLY_ACC]['TRCHR_SEQAL_TR_NOT_IN_DB']++;echo "TRANSCRIPT_CHROMOSOME NOT FOUND\t".$LOG_STR."\n";continue;}
		$TR_DB_INFO=&$ASSEMBLY_DATA['TR'][$tab[0]][$CHR_SEQ_ID];

		
		$REVERSE=array('A'=>'T','T'=>'A','C'=>'G','G'=>'C','N'=>'N');
		$VALID=true;
		$DIFF_SEQ=0;
		$HAS_MISSING_DNA=0;
		//	AL_RNA	AL_ID,	EXON	TYPE   	DNA_BASE		CHR_SEQ_POS_ID

		$LOG_DEBUG.= "STRAND:".$TR_DB_INFO['strand']."\n";
		if ($TR_DB_INFO['strand']=='+')
		{
			for ($I=0;$I<count($res);++$I)
			{
				$tab=explode(",",substr(str_replace(' ','',$res[$I]),1,-1));
				$LOG_DEBUG.=	 $tab[0]."\t".$tab[1]."\t".$tab[2]."\n";
				if ($tab[0]=='None')continue;
				if ($tab[2]=="'None'"||$tab[2]=="None")continue;
				$POS_NUCL=$tab[0]+1;
				$POS_DNA=$tab[1]+1;
				$SEQUENCE[$POS_NUCL][2]=$POS_DNA;
				
				$SEQUENCE[$POS_NUCL][1]=trim(substr($tab[2],1,-1));
					$DNA_BASE='';
				if (!isset($TR_DATA['DNA_POS'][$CHR_SEQ_ID][$POS_DNA]))
				{
					// echo "MISSING\n";
					//echo $res[$I];print_r($tab);

					
					$HAS_MISSING_DNA++;
				}
				else
				{
				
				$DNA_BASE=strtoupper($TR_DATA['DNA_POS'][$CHR_SEQ_ID][$POS_DNA][0]);
			
				$SEQUENCE[$POS_NUCL][5]=$DNA_BASE;
				$SEQUENCE[$POS_NUCL][6]=$TR_DATA['DNA_POS'][$CHR_SEQ_ID][$POS_DNA][1];
				if (strtoupper($DNA_BASE)!=strtoupper($SEQUENCE[$POS_NUCL][0]))$DIFF_SEQ++;
				}
			}
		}
		
		else 
		{
			$POS=0;
			$LEN=count($SEQUENCE);
			for ($I=0;$I<count($res);++$I)
			{
				$tab=explode(",",substr(str_replace(' ','',$res[$I]),1,-1));
				if ($tab[0]=='None')continue;
				if ($tab[2]=="'None'"||$tab[2]=="None")continue;
				$LOG_DEBUG.= $tab[0]."\t".$tab[1]."\t".$tab[2]."\n";
				$POS_NUCL=$LEN-$tab[0];
				$SEQUENCE[$POS_NUCL][1]=$REVERSE[strtoupper(trim(substr($tab[2],1,-1)))];
				$POS_DNA=$tab[1]+1;
				$SEQUENCE[$POS_NUCL][2]=$POS_DNA;
					$DNA_BASE='';
				if (!isset($TR_DATA['DNA_POS'][$CHR_SEQ_ID][$POS_DNA]))
				{
					
					$HAS_MISSING_DNA++;
				}
				else 
				{
				$DNA_BASE=$REVERSE[strtoupper($TR_DATA['DNA_POS'][$CHR_SEQ_ID][$POS_DNA][0])];
			
				$SEQUENCE[$POS_NUCL][5]=$DNA_BASE;
				$SEQUENCE[$POS_NUCL][6]=$TR_DATA['DNA_POS'][$CHR_SEQ_ID][$POS_DNA][1];
				if (strtoupper($DNA_BASE)!=strtoupper($SEQUENCE[$POS_NUCL][0]))$DIFF_SEQ++;
				}
				
			}

		}
		
		
		

	
		
			if ($HAS_MISSING_DNA>0)
			{
				$STATS[$ASSEMBLY_ACC]['MISSING_DNA_POSITION']++;
				if (!isset($POINTERS[5]))
				{
					echo "MISSING_DNA_POSITION ON NON PARTIAL SEQUENCE\t".$LOG_STR." \n";
					
					$STATS[$ASSEMBLY_ACC]['MISSING_DNA_POSITION_EXACT']++;
					//continue;
					/// In some situations, the alignment isn't perfect, so we arbitrarily tolerate up to 10 mismatches
					/// unless it is a micro-RNA, which in many cases has only a small portion of it being aligned to the DNA
					if ($HAS_MISSING_DNA > 10 && strtolower(substr($TRANSCRIPT_NAME,0,3))!='mir')$VALID=false;
				}
			}
			
			if ($DIFF_SEQ>count($SEQUENCE)*0.05)
			{
				$STATS[$ASSEMBLY_ACC]['ALIGNMENT_RNA_DNA_HIGH']++;
				if (!isset($POINTERS[5]))
				{
					echo "ALIGNMENT_RNA_DNA_HIGH ON NON PARTIAL SEQUENCE\t".$LOG_STR." \n";$STATS[$ASSEMBLY_ACC]['ALIGNMENT_RNA_DNA_HIGH_EXACT']++;
					$VALID=false;
				}
				
			}

			if ($VALID){
		$VALID=assignExonCDS($SEQUENCE,$TR_DATA['EXONS'][$CHR_NAME],'RS',$ASSEMBLY_ACC,$LOG_DEBUG);
		
		$TR_DATA['SEQ_VALID'][$CHR_NAME]=$VALID;
		
		$STATS[$ASSEMBLY_ACC]['SUCCESS_TRANSCRIPT_CHR']++;

		$LOG_STR.="PREP_SUCCESS\t";
			}
			if (!$VALID)
			{
				foreach ($SEQUENCE as $POS=>&$INFO){$INFO[3]=-1;$INFO[4]='unknown';}
				$LOG_STR.="FAILED_ALIGNMENT\t";
			}
		$STR_MD5='';
		//if ($HAS_INF)
		foreach ($SEQUENCE as $POS=>$INFO)
		{
			$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[6]."|";
			// echo $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
			// if ($INFO[4]=='' || $INFO[6]=='')exit;
			$LOG_DEBUG.= $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
			
		}//print_r($TR_DB_INFO);
		
		$MD5=md5($STR_MD5);
		$LOG_STR.= $MD5."<>".$TR_DB_INFO['seq_hash']."\t";
		$res=runQuery("SELECT count(*) co FROM transcript_pos where  transcript_id = ".$TR_DB_INFO['transcript_id']);
		
		$LEN_DB_TR=$res[0]['co'];
		$LOG_STR.= $LEN_DB_TR."<>".count($SEQUENCE)."\t";
	
		if (!$VALID)echo $LOG_DEBUG;
		/// When a sequence doesn't match perfectly against the DNA BUT overall it is fine
		/// We want to store the sequence but flag it as not being a valid alignment
		/// So we update that VALID flag so the issue can be reflected in the database
		if ($HAS_MISSING_DNA<10 && $HAS_MISSING_DNA>0)$VALID=false;

		if ($MD5==$TR_DB_INFO['seq_hash'] && $LEN_DB_TR==count($SEQUENCE)){
			echo "SUCCESS\t".$LOG_STR."\tIDENTICAL\n";
			$STATS[$ASSEMBLY_ACC]['SAME_TRANSCRIPT_FROM_DB']++;
			if ($TR_DB_INFO['valid_alignment']!='T')$res=runQueryNoRes("UPDATE transcript set valid_alignment='T' WHERE transcript_id = ".$TR_DB_INFO['transcript_id']);
			continue;
		}
		//exit;
		if ($LEN_DB_TR!=count($SEQUENCE))
		{
			$STATS[$ASSEMBLY_ACC]['DIFF_LENGTH_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="LENGTH_DIFFERENT_FROM_DB\t";
		}
		
		if ($TR_DB_INFO['seq_hash']!=''){$STATS[$ASSEMBLY_ACC]['DIFF_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="DIFF_TRANSCRIPT_FROM_DB\t";
		}
		else {$STATS[$ASSEMBLY_ACC]['NEW_TRANSCRIPT_FROM_DB']++;
			$LOG_STR.="NEW_TRANSCRIPT\t";
		}
		$res=runQueryNoRes("DELETE FROM transcript_pos where transcript_id = ".$TR_DB_INFO['transcript_id']);
		if ($res ===false){
			echo "FAILED_DELETION\t".$LOG_STR."\n";
			$STATS[$ASSEMBLY_ACC]['FAILED_DELETION']++;continue;}
		unset($STR_MD5);
		$UPDATE_HASH[$TR_DB_INFO['transcript_id']]=$MD5;
		$res=runQueryNoRes("UPDATE transcript set seq_hash = '".$MD5."',valid_alignment='".(($VALID)?"T":"F")."' WHERE transcript_id = ".$TR_DB_INFO['transcript_id']);
		$STR_FILE='';$HAS_UNK=false;

		foreach ($SEQUENCE as $POS=>$INFO)
		{
			if (!isset($SEQ_TYPES[$INFO[4]]))
			{
				$INFO[4]='unknown';
				$HAS_UNK=true;
			}

			
			
			
			$STR_FILE.=				$TR_DB_INFO['transcript_id']."\t".
			$INFO[0]."\t".$POS."\t".$SEQ_TYPES[$INFO[4]]."\t";
			if ($INFO[3]==-1)$STR_FILE.="NULL\t";else $STR_FILE.=$INFO[3]."\t";
			if ($INFO[6]==-1)$STR_FILE.="NULL\n";else $STR_FILE.=$INFO[6]."\n";
			
			

		// //	(transcript_pos_id ,transcript_id     ,nucl              ,seq_pos           ,seq_pos_type_id   ,exon_id           ,chr_seq_pos_id)',);
		// 	$STR_MD5.=$POS."_".$INFO[0]."_".$INFO[3]."_".$INFO[4]."_".$INFO[5]."|";
		// 	echo $CHR_NAME."\t".$TRANSCRIPT_NAME."\t".$POS."\t".implode("\t",$INFO)."\t".$TR_DB_INFO['strand']."\n";
		}
		if ($HAS_UNK)$LOG_STR.="UNK_POSITION\t";
		echo "SUCCESS\t".$LOG_STR."\n";
		fputs($FILE['transcript_pos'],$STR_FILE);

		
		
		
	}

	return true;
}



function assignExonPos(&$SEQUENCE,$SOURCE,$ASSEMBLY_ACC,$START,$END,$EXON_ID,&$LOG_DEBUG)
{
	global $STATS;
	$VALID=true;
	foreach ($SEQUENCE as $POS=>&$TR_POS)
	{
		
		//echo $TR_POS[2]."\n";
		if ($TR_POS[2]>=$START && $TR_POS[2]<=$END)
		{
			$LOG_DEBUG.="EXONPOS\t".$POS."::".$TR_POS[2];
			//echo "IN\n";
			if (is_numeric($EXON_ID))
			{
				if ($TR_POS[3]!=-1 && $TR_POS[3]!=$EXON_ID){ 
					echo "ISSUE\t".$POS."\t".$START.'<'.$TR_POS[2].'<'.$END."\t".$TR_POS[3]."<>".$EXON_ID."\n";
					// print_r($TR_POS);
					// print_r($tab);
					// foreach ($SEQUENCE as $POS=>$TR_POS)
					// {
					// 	echo $POS."\t".$TR_POS[0]."\t".$TR_POS[1]."\t".$TR_POS[2]."\t".$TR_POS[3]."\t".$TR_POS[4]."\n";
					// }
					// print_r($EXON_INFO);
										//exit;
					$STATS[$ASSEMBLY_ACC]['MULTIPLE_EXON_POS']++;$VALID=false;
				}
				
				$TR_POS[3]=$EXON_ID;
			}
			else 
			{
				if ($TR_POS[4]!=''){ $STATS[$ASSEMBLY_ACC]['MULTIPLE_CDS_POS']++;$VALID=false;}
				$TR_POS[4]=$EXON_ID;
			}
			$LOG_DEBUG.= "\tEXON:".$TR_POS[3]."\n";
		}
		
	}
	return $VALID;
}

function assignExonCDS(&$SEQUENCE,&$EXONS,$SOURCE,$ASSEMBLY_ACC,&$LOG_DEBUG)
{
	//print_r($EXONS);
// print_r($SEQUENCE);
// print_r($EXONS);
	
	foreach ($EXONS as $tab)
		{
			
			$L=$tab[1];
			$R=$tab[2];
			$tab[1]=min($L,$R);
			$tab[2]=max($L,$R);
			//echo "EXON\t".$tab[1]."\t".$tab[2]."\t".$tab[0];
			if (!isset($tab[3]) ||$tab[3]==array())
			{
				if (!assignExonPos($SEQUENCE,$SOURCE,$ASSEMBLY_ACC,$tab[1],$tab[2],$tab[0],$LOG_DEBUG))return false;

			}
			else
			{
				foreach ($tab[3] as $range)
				{
					if (!assignExonPos($SEQUENCE,$SOURCE,$ASSEMBLY_ACC,$range[0],$range[1],$tab[0],$LOG_DEBUG)) return false;
				}
			}
			// if (isset($tab[3])&& isset($tab[4]) && $tab[3]!='')
			// {
			// 	$tab[1]=$tab[3];
			// 	$tab[2]=$tab[4];
			// 	echo "\t".$tab[3]."\t".$tab[4];
			// }
			//echo "\n";
			
			
		}

	$FIRST_CDS=-1;
	$LAST_CDS=-1;
	$START_EXON=false;
	foreach ($SEQUENCE as $POS=>&$TR_POS)
	{
		
		if ($TR_POS[4]!='CDS')continue;
		if ($FIRST_CDS==-1)$FIRST_CDS=$POS;
		$LAST_CDS=$POS;
	}
	
	foreach ($SEQUENCE as $POS=>&$TR_POS)
	{

		//if ($TR_POS[0]!=$TR_POS[5])echo $POS."\t".implode("\t",$TR_POS)."\t";
		if ($FIRST_CDS==-1)$TR_POS[4]='non-coded';
		else {
		if ($POS<$FIRST_CDS)$TR_POS[4]="5'UTR";
		if ($POS>$LAST_CDS)$TR_POS[4]="3'UTR";
		}
		if ($TR_POS[1]==''){
			//if ($TR_POS[0]!=$TR_POS[5])echo "CAS A\t".$SOURCE;
			$HAS_INF=true;
			if ($SOURCE=='RS')
			{
				if ($TR_POS[4]!='')				$TR_POS[4].='-INFERRED';
				else if ($POS>=$FIRST_CDS&& $POS<=$LAST_CDS)		$TR_POS[4].='CDS-INFERRED';
			}
			else if ($SOURCE=='RS_B')// RefSeq no bam file does not have alignment info. 
			{
				//So we check between DNA and RNA if they are the same
				if (strtoupper($TR_POS[0])!=strtoupper($TR_POS[5]))$TR_POS[4].='-DIFFER';
			}

		}
		else if ($TR_POS[1]!=''&&$TR_POS[3]==-1)
		{
			if ($TR_POS[0]!=$TR_POS[5])echo "CAS B\t";
			$HAS_INF=true;
			$EXON=-1;
			for ($T=1;$T<=5;++$T)
			{
				
				if (isset($SEQUENCE[$POS-$T]) && $SEQUENCE[$POS-$T][3]!=-1){$EXON=$SEQUENCE[$POS-$T][3];break;}
			}
				$TR_POS[3]=$EXON;
				if ($SOURCE=='RS'){
			if ($TR_POS[4]!='')				$TR_POS[4].='-INFERRED';
			else if ($POS>=$FIRST_CDS&& $POS<=$LAST_CDS)		$TR_POS[4].='CDS-INFERRED';
				}
		}
		else if (strtoupper($TR_POS[0])!=strtoupper($TR_POS[5])){
			
			$TR_POS[4].='-DIFFER';}
		
	//	if ($TR_POS[0]!=$TR_POS[5])echo "=>".$POS."\t".implode("\t",$TR_POS)."\n";
	
	}
	$MAX=max(array_keys($SEQUENCE));
	$N_A=0;
	for ($I=$MAX;$I>=0;--$I)
	{
		//echo implode("\t",$SEQUENCE[$I])."\n";
		if ($SEQUENCE[$I][5]!="")break;
		if ($SEQUENCE[$I][0]!='A')break;
		$N_A++;
	}
	
	if ($N_A==0)return true;
	for ($I=$MAX;$I>=0;--$I)
	{
		if ($SEQUENCE[$I][5]!="")break;
		if ($SEQUENCE[$I][0]!='A')break;
		$SEQUENCE[$I][4]='poly-A';
	}

	return true;
//	exit;
	
}
?>
