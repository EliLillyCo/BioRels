<?php
ini_set('memory_limit','1000M');

/**
 SCRIPT NAME: db_transcriptome
 PURPOSE:     Push Transcript information to the database

*/

/// Job name - Do not change
$JOB_NAME='db_transcriptome';


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


addLog("Set uo directory");
	/// Get Parent info
	$PARENT_INFO=$GLB_TREE[getJobIDByName('db_genome')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];			if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';   				if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$PARENT_INFO['TIME']['DEV_DIR'];  		   	if (!is_dir($W_DIR) && !chdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);

	/// Create the INSERT directory
	if (!is_dir($W_DIR.'/INSERT/') && !mkdir($W_DIR.'/INSERT/'))									failProcess($JOB_ID."004",'Unable to create directory '.$W_DIR.'/INSERT/');

	/// Set process control directory to current date so the next job can access it
	$PROCESS_CONTROL['DIR']=$PARENT_INFO['TIME']['DEV_DIR'];



/// Here we are going to retrieve the max id for the primary key of each table
/// This make things easier when doing batch isnert
addLog("Get Database id List");

	$DBIDS=array('genome_assembly'=>-1,
	'chr_seq'=>-1,
	'gene_seq'=>-1,
	'transcript'=>-1,
	);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)										failProcess($JOB_ID."005",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}

	//// overview.json contains all the information about the current genome assembly and directories
	if (!is_file($W_DIR.'/overview.json'))															failProcess($JOB_ID."006",'Unable to find overview.json ');
	$TAXON_INFO=json_decode(file_get_contents($W_DIR.'/overview.json'),true);
	if ($TAXON_INFO==null)																			failProcess($JOB_ID."007",'Unable to obtain information from overview.json ');
	

	/// Here we only insert transcript information
	$COL_ORDER=array(
	'transcript'=>'(transcript_id , transcript_name , transcript_version , start_pos , end_pos , biotype_id , feature_id , gene_seq_id , seq_hash , chr_seq_id , support_level)',
   );
   
   /// So we open a transcript.csv file
   $FILES=array();
   foreach ($COL_ORDER as $TYPE=>$CTL)
   {
	   $FILES[$TYPE]=fopen($W_DIR.'/INSERT/'.$TYPE.'.csv','w');
	   if (!$FILES[$TYPE])																			failProcess($JOB_ID."008",'Unable to open '.$TYPE.'.csv');
   }


   /// Biotypes characterizes gene and transcripts.
   /// The ontology is Sequence ontology, but we need a mapping table, stored in the mapping file
   $BIOTYPES=loadBiotypes();
   


	foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
	{
		addLog("##### START TRANSCRIPT INSERT FOR ".$TAX_ID);
		// Looping over each assembly
		foreach ($LIST as $ASSEMBLY=>&$ASSEMBLY_INFO)
		{
			addLog("####### START TRANSCRIPT INSERT FOR ASSEMBLY ".$ASSEMBLY);

			processTranscript($TAX_ID,$ASSEMBLY_INFO);
		}
	}

	


	successProcess();










	
	

function processTranscript($TAX_ID,&$ASSEMBLY_INFO)
{
	
	
	global $W_DIR;
	global $GENE_PRD_DIR;
	global $FILES;
	global $BIOTYPES;
	global $DBIDS;
	global $COL_ORDER;
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	$T_DIR=$W_DIR.'/'.$TAX_ID;
	if (!is_dir($T_DIR))																		failProcess($JOB_ID."A01",'Unable to find '.$TAX_ID. ' '.$T_DIR);
	

	/// Those are the feature we are not interested in:
	$NOT_TRANSCRIPT_LIST=array('exon','CDS','sequence_alteration','biological_region','silencer','cDNA_match','origin_of_replication','region');
	$NOT_TRANSCRIPT_GROUP=array('regulatory','misc_feature','repeat_region','misc_recomb','protein_bind','mobile_element','misc_structure');

	///For a given taxon we are going to need to retrieve all the chromosome sequence, gene sequences and current transcripts
	$GENE_SEQS=array();
	/// So here we get the chromosomes for this taxon, independently of the assembly
	$res=runQuery("SELECT chr_num, chr_id
						FROM chromosome c, taxon t 
					where t.taxon_Id = c.taxon_id 
					AND tax_id = '".$TAX_ID."'");
	if ($res===false)																			failProcess($JOB_ID."A02",'Unable to get chromosomes for '.$TAX_ID);
	foreach ($res as $l)$GENE_SEQS[$l['chr_num']]=array('CHR_ID'=>$l['chr_id'],'CHR_SEQ'=>array());
	
	

	/// Now we get the current chromosome sequences given those assembly
	$res=runQuery("SELECT  chr_seq_id , refseq_name , refseq_version , genbank_name , genbank_version , cs.chr_id , chr_num , chr_seq_name,seq_role,assembly_unit
					FROM chr_seq cs, chromosome c
					WHERE  c.chr_id = cs.chr_id AND genome_assembly_id=".$ASSEMBLY_INFO['DBID']);
	if ($res===false)																			failProcess($JOB_ID."A03",'Unable to get chromosome sequences for '.$TAX_ID);	




	/// Because the different data sources have different nomenclature to name their chromosome sequences, we list all the possible names in CHR_SEQ
	$CHR_SEQ=array();
	foreach ($res as $l)
	{
		
		$l['DB_STATUS']='FROM_DB';
		$GENE_SEQS[$l['chr_num']]['CHR_SEQ'][$l['genbank_name']]=$l;
		$CHR_SEQ[$l['genbank_name'].(($l['genbank_version']!='')?'.'.$l['genbank_version']:'')]=$l['chr_seq_id'];
		$CHR_SEQ[$l['refseq_name'].(($l['refseq_version']!='')?'.'.$l['refseq_version']:'')]=$l['chr_seq_id'];
		$CHR_SEQ[$l['chr_seq_name']]=$l['chr_seq_id'];
	}

	
	
	$NEW_TRANSCRIPT=false;
	
	
	///  the gene sequences
	$res=runQuery("SELECT gene_seq_id, gene_seq_name, gene_seq_version,strand,start_pos,end_pos,
			biotype_id, gs.chr_seq_id, gs.gn_entry_id, cs.chr_seq_name, refseq_name, 
			refseq_version, genbank_name,genbank_version, g.gene_id 
			FROM chr_seq cs, gene_seq gs 
			LEFT JOIN gn_entry g on g.gn_entry_id = gs.gn_entry_id 
			where gs.chr_seq_id = cs.chr_seq_id AND genome_assembly_id=".$ASSEMBLY_INFO['DBID']." ");
			if ($res===false)																		failProcess($JOB_ID."A04",'Unable to get gene sequences for '.$TAX_ID);
	
			$LIST_GENE_SEQ=array();
	foreach ($res as $line)
	{
		
		$GENE_SEQS[$line['gene_seq_name']]=$line;
	// echo "T\t".$line['gene_seq_name']."\t".$TAX_ID."\t".$line['gene_seq_id']."\n";
		$LIST_GENE_SEQ[]=$line['gene_seq_id'];
	}
	
	/// And at last the current transcripts.
	addLog("Get transcripts for ".$TAX_ID);
	$TR_DATA=array();
	if ($LIST_GENE_SEQ!=array())
	{
		$CHUNKS=array_chunk($LIST_GENE_SEQ,5000);
		foreach ($CHUNKS as $CHUNK)
		{
		
			$res=runQuery('SELECT transcript_id , transcript_name , transcript_version , t.start_pos , t.end_pos , t.biotype_id , t.feature_id , t.gene_seq_id , seq_hash , t.chr_seq_id , t.support_level
			FROM transcript t
			where gene_seq_id IN (' .implode(',',$CHUNK).')');
			if ($res===false)																		failProcess($JOB_ID."A05",'Unable to get transcripts for '.$TAX_ID);
			foreach ($res as $line)
			{
				$line['DB_STATUS']='FROM_DB';
				$TR_DATA[$line['transcript_name']][$line['transcript_version']]=$line;
			}
		}
	}


	$ENS_INFO=null;
	$RS_INFO=null;
	foreach ($ASSEMBLY_INFO['SOURCES'] as &$SOURCE_INFO)
	{
		if ($SOURCE_INFO['Source']=='ENSEMBL')$ENS_INFO=&$SOURCE_INFO;
		else if ($SOURCE_INFO['Source']=='REFSEQ')$RS_INFO=&$SOURCE_INFO;
	}

	/// IF a transcript version is updated OR start/end position, gene seq, chromosome sequence changes, we need to remove the transcript sequence from the database to ensure full integrity
	/// This array will list those we need to remove
	$CLEAN_TR_SEQ=array();

	addLog("Ready to process for ".$TAX_ID);
	if ($RS_INFO!=null)
	{
		addLog("Processing RefSeq Gene Sequence for ".$TAX_ID)	;
		$BUILD_DIR=$W_DIR.'/'.$TAX_ID.'/'.$TAX_ID.'__'.$RS_INFO['Assembly_Acc'].'__'.$RS_INFO['Assembly_name'].'__REFSEQ/';
		
		

		if (!checkFileExist($BUILD_DIR.$TAX_ID.'_gene.gff'))							failProcess($JOB_ID."A06",'Unable to find '.$TAX_ID.'_gene.gff');
		$fp=fopen($BUILD_DIR.$TAX_ID.'_gene.gff','r');if (!$fp)							failProcess($JOB_ID."A07",'Unable to open '.$TAX_ID.'_gene.gff');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,100000,"\n");
			if ($line==''||substr($line,0,1)=='#')continue;
			$gene_info=explode("\t",$line);
			
			/// We only consider gene and pseudogene
			if ($gene_info[2]!='gene'&&$gene_info[2]!='pseudogene')continue;
			/// Converting the last column from text to array
			$gene_info[8]=convertGFFLine($gene_info[8]);

			/// Now that we are in a gene section, we want to make sure we have all the transcripts within it
			$fpos=-1;
			while(!feof($fp))
			{
				$fpos=ftell($fp);
				$line=stream_get_line($fp,100000,"\n");
				if ($line==''||substr($line,0,1)=='#')continue;
				
				$tr_info=explode("\t",$line);
				/// If the line describes a gene or pseudogene we stop
				if ($tr_info[2]=='gene'||$tr_info[2]=='pseudogene')break;
				/// the boundaries described in this line MUST be within the boundaries of the gene
				if (!($gene_info[3]<=$tr_info[3] && $tr_info[3] <=$gene_info[4]))break;
				if (!($gene_info[3]<=$tr_info[4] && $tr_info[4] <=$gene_info[4]))break;
				/// And we are not interested in non transcript information
				if (in_array($tr_info[2],$NOT_TRANSCRIPT_LIST))continue;
				$tr_info[8]=convertGFFLine($tr_info[8]);
				if (isset($tr_info[8]['gbkey']) && in_array($tr_info[8]['gbkey'],$NOT_TRANSCRIPT_GROUP))continue;
				$gene_info['tr'][]=$tr_info;
			
			}
			/// Since we read one line too many, we come back to the previous line.
			fseek($fp,$fpos);
		
			/// Process record.
			$NAME=substr($gene_info[8]['ID'],5);
			//print_r($gene_info);
			$GENE_SEQ_ID=-1;
			$IS_NEW=false;
			if (!isset($GENE_SEQS[$NAME])) continue;
			$ENTRY=&$GENE_SEQS[$NAME];
			$GENE_SEQ_ID=$ENTRY['gene_seq_id'];
			
			

		
			/// Now we loop over each transcript that needs to be processed.
			if (isset($gene_info['tr']))
			foreach ($gene_info['tr'] as $TR)
			{
				/// Getting the transcript name
				$FULL_NAME=substr($TR[8]['ID'],strpos($TR[8]['ID'],'-')+1);
				
				/// And split it between name and version
				$pos=strpos($FULL_NAME,'.');
				$NAME=$FULL_NAME;
				$VERSION='NULL';
				if ($pos!==false){
				$NAME=substr($FULL_NAME,0,$pos);
				$VERSION=substr($FULL_NAME,strpos($FULL_NAME,'.')+1);
				}
				//echo $TR[8]['ID']." =>".$FULL_NAME."\t".$NAME."\t".$VERSION."\n";;

				/// Refseq doesn't have a support level. It only distinguish between NM and XM (predicted)
				$SUPPORT_LEVEL=((substr($NAME,0,1)=='N')?'1':'5');

				/// Sequence hash is used in the next step with the transcript sequence, it is a unique identifier taking into account the sequence itself, its DNA position, exon, type
				/// But some of them don't have any sequence, so we want to flag it.
				$SEQ_HASH='NULL';

				/// Getting the biotype
				$BT_ID='NULL';
				if (isset($TR[8]['gbkey'])) 
				{
					if (in_array($TR[8]['gbkey'],array('tRNA','C_region','D_segment','J_segment','tRNA','V_segment')))$SEQ_HASH='N/A';
					if (isset($BIOTYPES[$TR[8]['gbkey']]))$BT_ID=$BIOTYPES[$TR[8]['gbkey']];
					else echo "ISSUE\tNO BIOTYPE FOUND\t".$TR[8]['gbkey']."\n";
				}
				$FT_ID=NULL;
				if (isset($TR[2]) &&
				isset($BIOTYPES[$TR[2]]))
				{
					$FT_ID=$BIOTYPES[$TR[2]];
				}
				else {echo "L847\n";print_r($BIOTYPES);print_r($TR);echo $TR[2]."\n";exit;}


				
				if (!isset($TR[8]['gbkey']) || !isset($BIOTYPES[$TR[8]['gbkey']])){ echo $NAME."\n"	;continue;}

				/// Getting feature type
				$FT_ID=$BIOTYPES[$TR[8]['gbkey']];

				/// And chromosome sequence it
				$CHR_SEQ_ID=$CHR_SEQ[$gene_info[0]];
				$TR_ENTRY=null;
				/// Here we check if the transcript already exist in the database
				//// we first search by name
				if (isset($TR_DATA[$NAME]))
				{
					/// only one version of that transcript => good
					if (count($TR_DATA[$NAME])==1)
					{
						$TR_ENTRY=array_values($TR_DATA[$NAME])[0];
					}
					else  //// Multiple version of the same transcript. we need to compare versions
					{
						
						foreach ($TR_DATA[$NAME] as $TR_VERSION=>$TR_D)
						{
							//echo $TR_VERSION.'<>'.$VERSION."\t".($TR_VERSION==$VERSION)."\n";
							if (strcmp($TR_VERSION,$VERSION)==0){$TR_ENTRY=$TR_D;break;}
							
						}
						if ($TR_ENTRY!=null)continue;
						/// If we still don't find it, we look by chromosome and gene sequence to see if there's a match (rare)
						foreach ($TR_DATA[$NAME] as $TR_VERSION=>$TR_D)
						{
							if ($TR_D['chr_seq_id']==$CHR_SEQ_ID && $TR_D['gene_seq_id']==$GENE_SEQ_ID)
							{
								$TR_ENTRY=$TR_D;
								break;
							}
						}
						
					}
				}

				/// No transcript in DB? So it's a new transcript, we insert it.
				if ($TR_ENTRY==null)
				{
					$DBIDS['transcript']++;
				
				
					//'(transcript_id , transcript_name , transcript_version , start_pos , end_pos , biotype_id , feature_id , gene_seq_id , seq_hash , chr_seq_id , support_level)',
					fputs($FILES['transcript'],
						$DBIDS['transcript']."\t".
						$NAME."\t".
						$VERSION."\t".
						$TR[3]."\t".
						$TR[4]."\t".
						$BT_ID."\t".
						$FT_ID."\t".
						$GENE_SEQ_ID."\t".
						$SEQ_HASH."\t".
						$CHR_SEQ_ID."\t".
						$SUPPORT_LEVEL."\n");
					$NEW_TRANSCRIPT=true;
				}
				else
				{
					/// Otherwise we check if anything has changed. If so, some descriptors might relate to the sequence itself, therefore forcing to delete the sequence
					$TO_UPD=false;$TO_CLEAN=false;
					$STR='UPDATE transcript SET ';
					if ($TR_ENTRY['transcript_version']=='')$TR_ENTRY['transcript_version']='NULL';
					if ($TR_ENTRY['feature_id']=='')$TR_ENTRY['feature_id']='NULL';
					if ($TR_ENTRY['biotype_id']=='')$TR_ENTRY['biotype_id']='NULL';
					if ($TR_ENTRY['seq_hash']!='N/A' && $SEQ_HASH=='N/A')			{$STR.=" seq_hash ='N/A',";							$TO_UPD=true;}
					if ($TR_ENTRY['transcript_version']	!=$VERSION)					{$STR.=" transcript_version ='".$VERSION."',";		$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['start_pos']			!=$TR[3])					{$STR.=" start_pos =".$TR[3].',';					$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['end_pos']			!=$TR[4])					{$STR.=" end_pos =".$TR[4].',';						$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['feature_id']			!=$FT_ID)					{$STR.=" feature_id =".$FT_ID.',';					$TO_UPD=true;}
					if ($TR_ENTRY['biotype_id']			!=$BT_ID)					{$STR.=" biotype_id =".$BT_ID.',';					$TO_UPD=true;}
					if ($TR_ENTRY['gene_seq_id']		!=$GENE_SEQ_ID)				{$STR.=" gene_seq_id =".$GENE_SEQ_ID.',';			$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['chr_seq_id']			!=$CHR_SEQ[$gene_info[0]])	{$STR.=" chr_seq_id =".$CHR_SEQ[$gene_info[0]].',';	$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['support_level']		!=$SUPPORT_LEVEL)			{$STR.=" support_level =".$SUPPORT_LEVEL.',';		$TO_UPD=true;}
					
					/// If some descriptors have changed, we need to remove the transcript's individual nucleotide sequence
					if ($TO_CLEAN)$CLEAN_TR_SEQ[]=$TR_ENTRY['transcript_id'];
					if (!$TO_UPD)continue;
					//  print_r($TR);
					//  print_r($TR_ENTRY);
					
					//exit;
					$STR= substr($STR,0,-1).' WHERE transcript_id = '.$TR_ENTRY['transcript_id'];
					echo $STR."\n";
					if (!runQueryNoRes($STR)) 																		failProcess($JOB_ID."A08",'Unable to update transcript seq');

				}
			}
		
		
		}///END FEOF
	}///END REFSEQ
	if ($ENS_INFO!=null)
	{
		$BUILD_DIR=$W_DIR.'/'.$TAX_ID.'/'.$TAX_ID.'__'.$ENS_INFO['Assembly_Acc'].'__'.$ENS_INFO['Assembly_name'].'__ENSEMBL/';

		addLog("Processing Ensembl Gene Sequence for ".$TAX_ID.' '.$BUILD_DIR.$TAX_ID.'_gene.gff3')	;

		if (!checkFileExist($BUILD_DIR.$TAX_ID.'_gene.gff3'))												failProcess($JOB_ID."A09",'Unable to find '.$TAX_ID.'_gene.gff');
		$fp=fopen($BUILD_DIR.$TAX_ID.'_gene.gff3','r');if (!$fp)											failProcess($JOB_ID."A10",'Unable to open '.$TAX_ID.'_gene.gff');
		
		while(!feof($fp))
		{
			$line=stream_get_line($fp,100000,"\n");
			if ($line==''||substr($line,0,1)=='#')continue;
			$gene_info=explode("\t",$line);
			
			/// We only consider gene and pseudogene
			if (!in_array($gene_info[2],array('gene','pseudogene','ncRNA_gene')))continue;
			
			/// Converting the last column from text to array
			$gene_info[8]=convertGFFLine($gene_info[8]);
			$NAME=substr($gene_info[8]['ID'],5);
			
			/// Now that we are in a gene section, we want to make sure we have all the transcripts within it
			$fpos=-1;
			while(!feof($fp))
			{
				$fpos=ftell($fp);
				$line=stream_get_line($fp,100000,"\n");
				
				if ($line==''||substr($line,0,1)=='#')continue;
				
				$tr_info=explode("\t",$line);
				/// We stop if we reach any of those
				if (in_array($tr_info[2],array('gene','pseudogene','ncRNA_gene','chromosome')))break;
				
				/// Any transcripts should be within the parent gene sequence position
				if (!($gene_info[3]<=$tr_info[3] && $tr_info[3] <=$gene_info[4]))break;
				if (!($gene_info[3]<=$tr_info[4] && $tr_info[4] <=$gene_info[4]))break;
				/// Should we really be about a transcript.
				if (in_array($tr_info[2],array('exon','CDS','sequence_alteration','biological_region','region','silencer','cDNA_match','origin_of_replication','three_prime_UTR','five_prime_UTR')))continue;
				$tr_info[8]=convertGFFLine($tr_info[8]);
				
				/// We store the transcript information into gene_info array
				$gene_info['tr'][]=$tr_info;
			
			}
			fseek($fp,$fpos);
	

			$VERSION=$gene_info[8]['version'];
			
			/// Now we search for the GENE_SEQ_ID
			$GENE_SEQ_ID=-1;
			if (!isset($GENE_SEQS[$NAME]))continue;	
			$ENTRY=&$GENE_SEQS[$NAME];
			$GENE_SEQ_ID=$ENTRY['gene_seq_id'];
				
			

			
			///We process each transcript
			if (isset($gene_info['tr']))
			foreach ($gene_info['tr'] as $TR)
			{
				/// Getting the name and version
				$NAME=substr($TR[8]['ID'],strpos($TR[8]['ID'],':')+1);
				$VERSION=$TR[8]['version'];

				/// By default, support level is NA and SEQ_HASH is null
				$SUPPORT_LEVEL='NA';
				$SEQ_HASH='NULL';
				

				if (!isset($TR[8]['transcript_support_level']))
				{
					if (!isset($TR[8]['transcript_id']))
					{
						echo "#####ISSUE TRANSCRIPT - NO TRANSCRIPT ID FOUND\n";
						echo "### TRANSCRIPT:\n";print_r($TR);
						echo "### GENE: \n";print_r($gene_info);
						echo "#####\n";
						continue;
					}
				}
				else  $SUPPORT_LEVEL=explode(" ",$TR[8]['transcript_support_level'])[0];
				$BT_ID='NULL';
				if (isset($TR[8]['biotype'])) 
				{
					
					if (isset($TR[8]['gbkey']) && in_array($TR[8]['gbkey'],array('tRNA','C_region','D_segment','J_segment','tRNA','V_segment')))$SEQ_HASH='N/A';
					if (isset($BIOTYPES[$TR[8]['biotype']]))$BT_ID=$BIOTYPES[$TR[8]['biotype']];
					else echo "ISSUE\tNO BIOTYPE FOUND\t".$TR[8]['biotype']."\n";
				}
				$FT_ID=NULL;
				if (isset($TR[2]) || isset($BIOTYPES[$TR[2]]))$FT_ID=$BIOTYPES[$TR[2]];
				$TR_ENTRY=null;
				if (isset($TR_DATA[$NAME]))
				{
					if (count($TR_DATA[$NAME])==1)
					{
						$TR_ENTRY=array_values($TR_DATA[$NAME])[0];
					}
					else 
					{
						foreach ($TR_DATA[$NAME] as $TR_VERSION=>$TR_D)
						{
							if (strcmp($TR_VERSION,$VERSION)==0){$TR_ENTRY=$TR_D;break;}
						}
						if ($TR_ENTRY==null)
						{
							foreach ($TR_DATA[$NAME] as $TR_VERSION=>$TR_D)
							{
								if ($TR_D['chr_seq_id']==$CHR_SEQ_ID && $TR_D['gene_seq_id']==$GENE_SEQ_ID){$TR_ENTRY=$TR_D;break;}
							}
						}
					}
				}

				$CHR_SEQ_ID=NULL;
				/// Finding the Chromosome sequence ID
				/// We need to do a couple different tries, because the nomenclature is not consistent
				if (!isset($CHR_SEQ[$TR[0]]))
				{
					//print_r($TAX_INFO['ENS']['CHR_SEQ_IDS']);
					if (substr($TR[0],0,4)=='CHR_')
					{
						if (!isset($CHR_SEQ[substr($TR[0],4)]))
						{
							if (!isset($CHR_SEQ['Chr'.substr($TR[0],4)]))
							{
								print_R($CHR_SEQ);
								echo "MISSING\t TAX INFO ENS CHR_SEQ_IDS WITH CHR_ :: ".($TR[0])."\n";
								continue;
							}
							else $CHR_SEQ_ID=$CHR_SEQ['Chr'.substr($TR[0],4)];
						}else $CHR_SEQ_ID=$CHR_SEQ[substr($TR[0],4)];

					}
					else if ($TR[0]=='MT')
					{
						$CHR_SEQ_ID=$CHR_SEQ['MT'];
					}
					else if (isset($CHR_SEQ['Chr'.$TR[0]]))
					{
						$CHR_SEQ_ID=$CHR_SEQ['Chr'.$TR[0]];
					}
					else
					{
						echo "MISSING\tTAX INFO ENS CHR_SEQ_IDS ".($TR[0])."\n";
						continue;
					}
					
					
				}
				else $CHR_SEQ_ID=$CHR_SEQ[$TR[0]];
				
				if ($TR_ENTRY==null)
				{
					$DBIDS['transcript']++;
					//'(transcript_id , transcript_name , transcript_version , start_pos , end_pos , biotype_id , feature_id , gene_seq_id , seq_hash , chr_seq_id , support_level)',
					//	echo $DBIDS['transcript']."\t".$NAME."\t".$VERSION."\t".$TR[3]."\t".$TR[4]."\tNULL\t".$FT_ID."\t".$GENE_SEQ_ID."\tNULL\t".$CHR_SEQ[$gene_info[0]]."\t".((substr($NAME,0,1)=='N')?'1':'5')."\n";
					fputs($FILES['transcript'],
						$DBIDS['transcript']."\t".
						$NAME."\t".
						$VERSION."\t".
						$TR[3]."\t".
						$TR[4]."\t".
						$BT_ID."\t".
						$FT_ID."\t".
						$GENE_SEQ_ID."\t".
						$SEQ_HASH."\t".
						$CHR_SEQ_ID."\t".
						$SUPPORT_LEVEL."\n");
					$NEW_TRANSCRIPT=true;
				}
				else
				{
					$TO_UPD=false;$TO_CLEAN=false;
					$STR='UPDATE transcript SET ';
					if ($TR_ENTRY['transcript_version']=='')$TR_ENTRY['transcript_version']='NULL';
					if ($TR_ENTRY['feature_id']=='')$TR_ENTRY['feature_id']='NULL';
					
					if ($TR_ENTRY['biotype_id']=='')$TR_ENTRY['biotype_id']='NULL';
					if ($TR_ENTRY['transcript_version']!=$VERSION){$STR.=" transcript_version =".$VERSION.',';$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['seq_hash']!='N/A' && $SEQ_HASH=='N/A'){$STR.=" seq_hash ='N/A',";$TO_UPD=true;}
					if ($TR_ENTRY['start_pos']!=$TR[3]){$STR.=" start_pos =".$TR[3].',';$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['end_pos']!=$TR[4]){$STR.=" end_pos =".$TR[4].',';$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['feature_id']!=$FT_ID){$STR.=" feature_id =".$FT_ID.',';$TO_UPD=true;}
					if ($TR_ENTRY['biotype_id']!=$BT_ID){$STR.=" biotype_Id =".$BT_ID.',';$TO_UPD=true;}
					if ($TR_ENTRY['gene_seq_id']!=$GENE_SEQ_ID){$STR.=" gene_seq_id =".$GENE_SEQ_ID.',';$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['chr_seq_id']!=$CHR_SEQ_ID){$STR.=" chr_seq_id =".$CHR_SEQ_ID.',';$TO_UPD=true;$TO_CLEAN=true;}
					if ($TR_ENTRY['support_level']!=$SUPPORT_LEVEL){$STR.=" support_level =".$SUPPORT_LEVEL.',';$TO_UPD=true;}
					if ($TO_CLEAN)$CLEAN_TR_SEQ[]=$TR_ENTRY['transcript_id'];
					if (!$TO_UPD)continue;
					
					$STR= substr($STR,0,-1).' WHERE transcript_id = '.$TR_ENTRY['transcript_id'];
					//echo $STR."\n";
					if (!runQueryNoRes($STR)) failProcess($JOB_ID."A11",'Unable to update transcript seq');
					

				}
			}
			
		}///END FILE
		fclose($fp);
	}///END ENS
	
	

	if ($CLEAN_TR_SEQ!=array())
	{
		print_r($CLEAN_TR_SEQ);
		
		addLog("Deleting ".count($CLEAN_TR_SEQ)." transcript sequences for ".$TAX_ID)	;
		
		$query='DELETE FROM transcript_pos WHERE transcript_id IN ('.implode(",",$CLEAN_TR_SEQ).')';
		
		if (!runQueryNoRes($query)) failProcess($JOB_ID."A12",'Unable to clean transcript sequences');
	}
		
		

	/// Data insertion:
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
		addLog("inserting ".$NAME." records");
		$res=array();
		fclose($FILES[$NAME]);
		
		if ($NAME=='transcript' && !$NEW_TRANSCRIPT)continue;
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$W_DIR.'/INSERT/'.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		print_r($res);
		if ($return_code !=0 )failProcess($JOB_ID."A13",'Unable to insert '.$NAME); 
	}
		
	/// Reopening files:	
	$FILES=array();
	foreach ($COL_ORDER as $TYPE=>$CTL)
	{
		$FILES[$TYPE]=fopen($W_DIR.'/INSERT/'.$TYPE.'.csv','w');
		if (!$FILES[$TYPE])		failProcess($JOB_ID."A14",'Unable to open '.$TYPE.'.csv');
	}
	
}

?>
	
