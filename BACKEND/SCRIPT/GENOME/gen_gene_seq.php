<?php
ini_set('memory_limit','5000M');
/**
 SCRIPT NAME: gen_gene_seq
 PURPOSE:     Generate gene DNA sequences and subsequent bowtie/bowtie2/blastn
 
*/

/// Job name - Do not change
$JOB_NAME='gen_gene_seq';

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
	///	Get Parent job info
	$CK_INFO=$GLB_TREE[getJobIDByName('db_dna')];
	
	/// Set up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];						if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];							if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 																					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	$W_DIR.='/DATA';
	if (!is_dir($W_DIR) ||!chdir($W_DIR)) 															failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];			
	
	$G_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$CK_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($G_DIR)) 																					failProcess($JOB_ID."006",'NO '.$G_DIR.' found ');


addLog("Working directory: ".$W_DIR);

	$TAXON_INFO=&$GLB_VAR['GENOME'];


	
$START=false;;
	foreach ($TAXON_INFO as $TAX_ID=>&$TAX_INFO)
	{

		/// Create directory specific for the taxon:
		$T_DIR=$W_DIR.'/'.$TAX_ID;
		if (!is_dir($T_DIR))															failProcess($JOB_ID."008",'Unable to find '.$TAX_ID);
		if (!chdir($T_DIR))																failProcess($JOB_ID."009",'Unable to access '.$TAX_ID);
	
		foreach ($TAX_INFO as $GS_DUMMY=>&$GS_RULES)
		{
			//
			// if ($TAX_ID==9606)$START=true;
			// if (!$START)continue;
			


			/// Based on CONFIG_USER/GENOME parameters, defines whether to output pre-mRNA sequences and promoter regions
			$OPT_W_PREMRNA=false; 
			if ($GLB_VAR['pre-mRNA']=='Y')$OPT_W_PREMRNA=true;
			
			$OPT_W_PROMOTER=false; 
			if ($GLB_VAR['promoter']=='Y')$OPT_W_PROMOTER=true;
			
			addLog("Output pre-mRNA sequences ".($OPT_W_PREMRNA)?"YES":"NO");
			addLog("Output promotor region ".($OPT_W_PROMOTER)?"YES":"NO");
			

			/// Based on CONFIG_USER parameters, defines whether to include unknown genes and/or LOC genes
			$W_UNK_GENES=true;
			if ($GLB_VAR['Filter_UNK']=='Y')$W_UNK_GENES=false;
			
			
			$W_LOC_GENES=true;
			if ($GLB_VAR['Filter_LOC']=='Y')$W_LOC_GENES=false;


///////////////// TODO
//// MANAGE PATHS FROM GENOME ASSEMBLY FILES

			
			/// Get all the gene sequences:
			$query="SELECT GENE_SEQ_ID,STRAND,cs.chr_seq_name,c.chr_num,START_POS,END_POS,GENE_ID,SYMBOL,FULL_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION,refseq_name,refseq_version
			FROM genome_Assembly gas, GENE_SEQ GS 
			LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GS.GN_ENTRY_ID, CHR_SEQ CS, CHROMOSOME C, taxon T
			WHERE gas.genome_assembly_id = cs.genome_assembly_id
			AND T.taxon_id = C.taxon_id
			AND C.CHR_ID = CS.CHR_ID
			AND CS.CHR_SEQ_ID = GS.CHR_SEQ_ID
			AND TAX_ID='".$TAX_ID."'";
			
			/// With additional filters as specified in CONFIG_USER
			if (!$W_LOC_GENES) $query .= " AND symbol NOT LIKE 'LOC%' ";
			if (!$W_UNK_GENES) $query .= " AND gs.gn_entry_id is not null ";
			$query.=" ORDER BY chr_seq_name, START_POS ASC";
			$res=runQuery($query);
			if ($res===false)															failProcess($JOB_ID."010",'Unable to get gene list for '.$TAX_ID);
			
			
			
			$GENE_INFOS=array();
			$KN=0;
			foreach ($res as $tab)
			{
				///	0			1		2				3				4			5		6		7		8
				/// GENE_SEQ_ID,STRAND,cs.REFSEQ_NAME,cs.REFSEQ_VERSION,START_POS,END_POS,GENE_ID,SYMBOL,FULL_NAME
				

				/// In the case of ensembl genes, gene sequence start position are relative to the chromosome and not the chromosome sequence (scaffold/pathc).
				/// So we use the chromosome start position to shift the gene sequence start position to the chromosome sequence
				if ($tab['seq_role']!='assembled-molecule' && substr($tab['gene_seq_name'],0,3)=='ENS')
				{

					if ($tab['chr_start_pos']!=1)
					{
						$tab['start_pos']-=$tab['chr_start_pos']-1;
						$tab['end_pos']-=$tab['chr_start_pos']-1;
					}
				
				}
				

				/// We need to check that it's actually associated to a gene or not.
				if (isset($tab['gene_id']) && $tab['gene_id']!='')
				{

					/// First time we see the gene, just create the array
					if (!isset($GENE_INFOS[$tab['gene_id']]))
					{
						$GENE_INFOS[$tab['gene_id']][]=
							/// The name is a composite of taxon, gene id, symbol,chromosome, chromosome sequence, gene sequence
							array('NAME'=>'taxId='.$TAX_ID.
										';gid='.$tab['gene_id'].
										';symbol='.$tab['symbol'].
										';chr='.$tab['chr_num'].
										';chr_seq_name='.$tab['chr_seq_name'].
										';gene_seqname='.$tab['gene_seq_name'].((isset($tab['gene_seq_version']))?'.'.$tab['gene_seq_version']:''),
									'S'=>$tab['strand'],
									'GN'=>$tab['full_name'],
									'C'=>$tab['refseq_name'].'.'.$tab['refseq_version'],
									'START'=>$tab['start_pos'],
									'END'=>$tab['end_pos']
								);
					}
					else
					{
						/// However, if it's not the first time, we need to do additional checks to ensure whether
						/// this is the same gene sequence or an alternative one.
						/// To do so, we need to check the refseq name, the strand
						/// And that it is within the same region. However, we stopped doing this as it caused too many issues.
						/// So we just assume that the same chromosome sequence/scaffold/path and strand is good enough.
						$FOUND=false;
						foreach ($GENE_INFOS[$tab['gene_id']] as &$V)
						{
							/// If the refseq name is different, it's a different gene sequence
							if ($tab['refseq_name'].'.'.$tab['refseq_version']!=$V['C'])continue;
							/// If the strand is different, it's a different gene sequence
							if ($tab['strand']!=$V['S'])continue;

							/// If the start of this gs is before the end of the V gs
							if ($tab['end_pos']<$V['START'])
							{
								if ($tab['seq_role']!='assembled-molecule')continue;
								if (strpos($V['NAME'],'LOC')!==false ||
									strpos($tab['gene_seq_name'],'LOC')!==false)continue;
								echo "\n\n\n\n######\n";
								echo $tab['gene_id']."\t".$tab['start_pos']."\t".$tab['end_pos']."\n";
								echo $tab['symbol']."\t".
									$V['START']."\t".
									$V['END']."\n";
								print_R($V);
								print_R($tab);
								echo "END BEFORE START\n";;
								
								++$N_ISSUE;continue;
								
							}
							///The end of this gs is before the start of the V gs
							if ($tab['start_pos']>$V['END'])
							{
								if ($tab['seq_role']!='assembled-molecule')continue;
								if (strpos($V['NAME'],'LOC')!==false ||
									strpos($tab['gene_seq_name'],'LOC')!==false)continue;
								if(abs($tab['start_pos']-$V['END'])>100000)
								{
									echo "\n\n\n\n######\n";
									echo $tab['gene_id']."\t".$tab['start_pos']."\t".$tab['end_pos']."\n";
									echo $tab['symbol']."\t".$V['START']."\t".$V['END']."\n";print_R($V);print_R($tab);
									echo "START AFTER END\t".abs($tab['start_pos']-$V['END'])."\n";;
									++$N_ISSUE;
									continue; 
								}
							}///The start of this gs is after the end of the V gs
							$TM=max($V['START'],$tab['start_pos']);
							$TE=min($V['END'],$tab['end_pos']);
							
							$FOUND=true;
							$V['START']=$TM;
							$V['END']=$TE;
							$V['NAME'].=';gene_seqname='.$tab['gene_seq_name'].((isset($tab['gene_seq_version']))?'.'.$tab['gene_seq_version']:'');
							break;
						}
						if ($FOUND)continue;
						/// If it's a new one, we create it.
						$GENE_INFOS[$tab['gene_id']][]=
						array('NAME'=>'taxId='.$TAX_ID.
									';gid='.$tab['gene_id'].
									';symbol='.$tab['symbol'].
									';refseq_name='.$tab['refseq_name'].'.'.$tab['refseq_version'].
									';gene_seqname='.$tab['gene_seq_name'].((isset($tab['gene_seq_version']))?'.'.$tab['gene_seq_version']:''),
								'S'=>$tab['strand'],
								'GN'=>$tab['full_name'],
								'C'=>$tab['refseq_name'].'.'.$tab['refseq_version'],
								'START'=>$tab['start_pos'],
								'END'=>$tab['end_pos']);	
						
					}
					
					
				}
				else
				{
					///Not a gene? We create a pseudogene
					++$KN;
					$GENE_INFOS['GENE_'.$KN][]=
						array('NAME'=>'taxId='.$TAX_ID.
								';refseq_name='.$tab['refseq_name'].'.'.$tab['refseq_version'].
								';gene_seqname='.$tab['gene_seq_name'].((isset($tab['gene_seq_version']))?'.'.$tab['gene_seq_version']:''),
							'S'=>$tab['strand'],
							'C'=>$tab['refseq_name'].'.'.$tab['refseq_version'],
							'START'=>$tab['start_pos'],
							'END'=>$tab['end_pos']);
				}

			}
			
			/// Now that we have the different regions, we can create the sequences
			/// However, if we requested pre-mRNA or promoter regions, we need to create those.

			$GENE_SEQS=array();
			foreach ($GENE_INFOS as $GENE_ID=>&$GENE_LIST)
			foreach ($GENE_LIST as &$GENE_DATA)
			{
				
				$GENE_DATA['GN']=str_replace(";","__",$GENE_DATA['GN']);
				if ($OPT_W_PREMRNA)
				{
					$GENE_SEQS[$GENE_DATA['C']][$GENE_DATA['START']][]=array(
						'STRAND'=>$GENE_DATA['S'],
						'NAME'=>$GENE_DATA['NAME'].';range='.$GENE_DATA['START'].'-'.$GENE_DATA['END'].(isset($GENE_DATA['GN'])?';gname="'.$GENE_DATA['GN'].'"':''),
						'END_POS'=>$GENE_DATA['END'],
						'SEQ'=>'',
						'T'=>'G');
				}
				if ($OPT_W_PROMOTER)
				{
					if ($GENE_DATA['S']=="+")
					{
						$ST=max($GENE_DATA['START']-$GLB_VAR['PROMOTER_RANGE'],1);
						$GENE_SEQS[$GENE_DATA['C']][$ST][]=array(
							'STRAND'=>$GENE_DATA['S'],
							'NAME'=>$GENE_DATA['NAME'].';range='.$ST.'-'.$GENE_DATA['START'].(isset($GENE_DATA['GN'])?';gname="'.$GENE_DATA['GN'].'"':''),
							'END_POS'=>$GENE_DATA['START'],
							'SEQ'=>'',
							'T'=>'P');
					}
					else 					
					{
						$GENE_SEQS[$GENE_DATA['C']][$GENE_DATA['END']][]=array(
							'STRAND'=>$GENE_DATA['S'],
							'NAME'=>$GENE_DATA['NAME'].';range='.$GENE_DATA['END'].'-'.($GENE_DATA['END']+$GLB_VAR['PROMOTER_RANGE']).(isset($GENE_DATA['GN'])?';gname="'.$GENE_DATA['GN'].'"':''),
							'END_POS'=>($GENE_DATA['END']+$GLB_VAR['PROMOTER_RANGE']),
							'SEQ'=>'',
							'T'=>'P');
					}
				}


			}
			unset($GENE_INFOS);

			/// Now we are going to actually create the sequences.
			/// But we don't want to do many iterations of the file if multiple genes overlaps
			/// So we sort the gene sequences by start position within a chromosome sequence
			foreach ($GENE_SEQS as $K=>&$LIST)
			{
				echo $K.' '.count($LIST)."\n";
				ksort($LIST);
			}



			$fpO=null;
			$fpP=null;
			/// Opening the files:
			if ($OPT_W_PREMRNA)$fpO=fopen($TAX_ID.'_GENE_SEQ.fna','w');if (!$fpO)								failProcess($JOB_ID."011",'Unable to open GENE_SEQ.fna for '.$TAX_ID);
			if ($OPT_W_PROMOTER)$fpP=fopen($TAX_ID.'_PROMOTER.fna','w');if (!$fpP)								failProcess($JOB_ID."012",'Unable to open PROMOTER.fna for '.$TAX_ID);
			
			/// Getting the DNA file path. By default we use the RefSeq version
			$DNA_FILE=$G_DIR.'/'.$TAX_ID.'/'.$TAX_ID.'_RS/'.$TAX_ID.'_seq.fa';
			
			if (!checkFileExist($DNA_FILE))
			{
				/// Checking if we have the ensembl version:
				$DNA_FILE=$G_DIR.'/'.$TAX_ID.'/'.$TAX_ID.'_ENS/'.$TAX_ID.'_seq.fa';
				if (!checkFileExist($DNA_FILE))																	failProcess($JOB_ID."013",'Unable to find DNA FILE for '.$TAX_ID);
			}
			
			$fp=fopen($DNA_FILE,'r');		
			if (!$fp)																							failProcess($JOB_ID."014",'Unable to open '.$TAX_ID.'_seq.fna');
			
			$valid=false;
			$NAME='';
			$CURR_POS=1;
			$CURR_LOOKUP=array();
			$DEB_STR='';
			$DEB_PUSH=false;
			
			$N_DONE=0;


			while(!feof($fp))
			{
					
					
				$line=stream_get_line($fp,1000,"\n");
				if ($line[0]=='>')
				{
					/// We are getting the header line:
					$NAME=substr($line,1,strpos($line,' ')-1);
					/// And check it is in the list of chromosome sequences we need to process.
					/// Sometime it is not because of it's a small scaffold or patch with only LOC gene
					/// So depending on the situation and the user parameters, we can sometime skip it.
					$valid =(isset($GENE_SEQS[$NAME]));
					
					echo $NAME."\t".$valid."\t".ftell($fp)."\n";;

					$CURR_POS=1;
					/// Clean up the array that contains the list of gene seq that are currently being processed.
					$CURR_LOOKUP=array();
					sleep(1);
					continue;
				}else if (!$valid)continue;
				/// Getting line length
				$LEN=strlen($line);
				/// To define the position of the last character:
				$END_POS=$CURR_POS+$LEN;
				$DEB_PUSH=false;
				
				/// Counting how many gene sequences are currently ongoing:
				$N_T=0;
				foreach ($CURR_LOOKUP as $K)$N_T+=count($GENE_SEQS[$NAME][$K]);
				$DEB_STR= "\t".$TAX_ID.":".$NAME.".[".$CURR_POS."+".$LEN."=>".$END_POS."]\t".count($CURR_LOOKUP)."|".$N_T." ; DONE:".$N_DONE;
				
				/// First, check if we have any new gene_seq.
				foreach ($GENE_SEQS[$NAME] as $START_POS=>&$LIST)
				{
					//print_r($LIST);
					/// So for that, since we have the start and end position of the line we are looking at,
					/// we are going to check if the start position of the gene seq is within that region
					if ($START_POS >=$CURR_POS && $START_POS <$END_POS)
					{
						if (!$DEB_PUSH){$DEB_PUSH=true;echo $DEB_STR;}
						
						/// If so, then great, we need to add it:
						$CURR_LOOKUP[]=$START_POS;
						echo $DEB_STR."\tADD ".$START_POS;
						
						foreach ($LIST as $T)echo '-'.$T['END_POS'].'='.($T['END_POS']-$START_POS).'|';
						echo "\tCURR LOOKUP:".count($CURR_LOOKUP)."\t".count($GENE_SEQS[$NAME])."\n";
					}
					/// If the gene seq start position is about the End position of our line,
					/// we are not going to find any new gene seq to process so we can stop
					if ($START_POS > $END_POS)break;
				}
				
				
				/// Nothing to process? Then we update the current position to the last character position
				/// and keep going
				if (count($CURR_LOOKUP)==0){$CURR_POS=$END_POS;continue;}
				
				
					
				/// Second, we add any sequence needed in the curr_lookup table.
				/// We only keep in $CURR_LOOKUP the start position.
				/// Now we need to go in it, loop over the different genes defined in that chromosome sequence ($NAME)
				/// and see what we need to do.
				foreach ($CURR_LOOKUP as $CL=>$START_POS)
				{
					/// Boolean confirming the $START_POS is within the range of our line.
					/// If true, that means we are starting to get the gene seq
					$RANGE_START=($START_POS >=$CURR_POS && $START_POS<=$END_POS);
					
					foreach ($GENE_SEQS[$NAME][$START_POS] as $K=>&$INFO)
					{
						/// Boolean confirming the $END_POS iswithin the range of our line.
						/// If true, that means we are finishing to get the gene seq
						$RANGE_END=($INFO['END_POS'] >=$CURR_POS && $INFO['END_POS']<$END_POS);

						/// So different situations arise:
						/// 1/ It's the start of the gene seq but not the end.
						if ($RANGE_START && !$RANGE_END)
						{
							//// The starting position is within the line, but it is probably not the first character
							/// so we do $START_POS-$CURR_POS and get hte rest of the line
							$INFO['SEQ'].=substr($line,$START_POS-$CURR_POS);
							
													
						}
						/// 2/ It's neither the start nor the end - we can read the whole line
						else if (!$RANGE_START && !$RANGE_END)
						{
							$INFO['SEQ'].=$line;
							
						}
						/// 3/ It is BOTH the start and the end. That's very rare
						/// We need to only cover that range
						else if ($RANGE_START && $RANGE_END)
						{
							//echo "\nA".$END_POS.' ' .$INFO['END_POS']."\n";
							$INFO['SEQ'].=substr($line,$START_POS-$CURR_POS,$INFO['END_POS']-$START_POS+1);
						}
						/// 4/ It is not the start but it is the end
						else if (!$RANGE_START && $RANGE_END)
						{
							////	echo "\nB".$END_POS.' ' .$INFO['END_POS']."\n".$line." ".($INFO['END_POS']-$CURR_POS+1)." ".strlen($line)."\n";

							/// We only go from the beginning of the line to the END_POS
							$INFO['SEQ'].=substr($line,0,$INFO['END_POS']-$CURR_POS+1);
						}


						/// If it's not the end position, we keep going to the next gene seq
						if (!$RANGE_END)continue;
						
						if (!$DEB_PUSH){$DEB_PUSH=true;echo $DEB_STR;}
						
						/// This should never happen:
						if (strlen($INFO['SEQ'])==0)
						{
							print_r($INFO);
							failProcess($JOB_ID."015",'No sequence extracted for '.$INFO['NAME'].';'.$INFO['T']);
						}

						/// We ensure the sequence length is the same as the length from the provided boundaried
						if (strlen($INFO['SEQ'])!= $INFO['END_POS']-$START_POS+1)
						{
							failProcess($JOB_ID.'016',"ISSUE - Sequence length different\t".$INFO['NAME'].';'.$INFO['T'].' '.strlen($INFO['SEQ'])."\t". ($INFO['END_POS']-$START_POS+1)."\n".$INFO['SEQ']);
						}

						/// Now based on the type of sequence it is, pre-mRNA, promoter, we write it down in different file
						/// Sequences are split in lines of 80 characters.
						/// for negative strand genes, we take the reverse complement
						if ($INFO['T']=='G')
						{						
							
							fputs($fpO,'>'.$INFO['NAME']."\n");
							if ($INFO['STRAND']=='+')	fputs($fpO,implode("\n",str_split($INFO['SEQ'],80))."\n");	
							else 						fputs($fpO,implode("\n",str_split(revComp($INFO['SEQ']),80))."\n");	
						}
						else
						{
							fputs($fpP,'>'.$INFO['NAME']."|PROMOTER\n");
							if ($INFO['STRAND']=='+')	fputs($fpP,implode("\n",str_split($INFO['SEQ'],80))."\n");	
							else 						fputs($fpP,implode("\n",str_split(revComp($INFO['SEQ']),80))."\n");	
						}
						++$N_DONE;

						/// The sequence is currently stored in $GENE_SEQS.
						/// However, if we keep all sequences, we will run out of memory
						/// So we unset that specific gene seq with its information and the corresponding sequence since we don't need it
						unset($GENE_SEQS[$NAME][$START_POS][$K]);

						/// We also clean the parent key if it's empty - again to save space
						if (count($GENE_SEQS[$NAME][$START_POS])==0)
						{
							unset($GENE_SEQS[$NAME][$START_POS]);
							unset($CURR_LOOKUP[$CL]);
						}
						echo $DEB_STR."\tEND\t".$INFO['END_POS']."\t".strlen($INFO['SEQ'])."\t".($INFO['END_POS']-$START_POS+1)."\t".count($CURR_LOOKUP)."\t".count($GENE_SEQS[$NAME])."\n";
					

					}

				}
				
				
				$CURR_POS=$END_POS;
			}
			fclose($fp);

			if ($OPT_W_PREMRNA)fclose($fpO);
			if ($OPT_W_PROMOTER)fclose($fpP);
			

			if ($N_DONE==0)continue;


			/// Now we can create the Blast/Bowtie/Bowtie2 databases
			if ($OPT_W_PREMRNA)
			{
				addLog("Create Blast files");
				if (!is_dir($T_DIR.'/GENE_SEQ_BLASTN') && !mkdir($T_DIR.'/GENE_SEQ_BLASTN'))failProcess($JOB_ID."010",'Unable to create  directory GENE_SEQ_BLASTN for '.$TAX_ID);
				if (!chdir($T_DIR.'/GENE_SEQ_BLASTN'))										failProcess($JOB_ID."011",'Unable to get to GENE_SEQ_BLASTN for '.$TAX_ID);
				
				system($GLB_VAR['TOOL']['MAKEBLAST'].' -in ../'.$TAX_ID.'_GENE_SEQ.fna  -dbtype nucl -out '.$TAX_ID.'_GENE_SEQ_BLASTN'.' &> PREP_LOG',$return_code);
				if ($return_code !=0)														failProcess($JOB_ID."012",'Unable to create blastn files for '.$TAX_ID);
			addLog("Create bowtie files for Taxonomy ".$TAX_ID);
				if (!is_dir($T_DIR.'/GENE_SEQ_BOWTIE') && !mkdir($T_DIR.'/GENE_SEQ_BOWTIE'))failProcess($JOB_ID."013",'Unable to create directory GENE_SEQ_BOWTIE for '.$TAX_ID);
				if (!chdir($T_DIR.'/GENE_SEQ_BOWTIE'))										failProcess($JOB_ID."014",'Unable to get to GENE_SEQ_BOWTIE for '.$TAX_ID);
				system($GLB_VAR['TOOL']['BOWTIE_BUILD'].' -r ../'.$TAX_ID.'_GENE_SEQ.fna BOWTIE_GENE_SEQ_'.$TAX_ID.' &> PREP_LOG',$return_code);
				if ($return_code !=0)														failProcess($JOB_ID."015",'Unable to create bowtie files for '.$TAX_ID);
				addLog("Create bowtie2 files for Taxonomy ".$TAX_ID);
				if (!is_dir($T_DIR.'/GENE_SEQ_BOWTIE2') && !mkdir($T_DIR.'/GENE_SEQ_BOWTIE2'))failProcess($JOB_ID."016",'Unable to create directory GENE_SEQ_BOWTIE2 for '.$TAX_ID);
				if (!chdir($T_DIR.'/GENE_SEQ_BOWTIE2'))										failProcess($JOB_ID."017",'Unable to get to GENE_SEQ_BOWTIE2 for '.$TAX_ID);
				system($GLB_VAR['TOOL']['BOWTIE2_BUILD'].' -r ../'.$TAX_ID.'_GENE_SEQ.fna BOWTIE2_GENE_SEQ_'.$TAX_ID.' &> PREP_LOG',$return_code);
				if ($return_code !=0)														failProcess($JOB_ID."018",'Unable to create bowtie2 files for '.$TAX_ID);

			}
			if ($OPT_W_PROMOTER)
			{
				addLog("Create Blast files for promoter");
				if (!is_dir($T_DIR.'/PROMOTER_BLASTN') && !mkdir($T_DIR.'/PROMOTER_BLASTN'))failProcess($JOB_ID."019",'Unable to create directory PROMOTER_BLASTN for '.$TAX_ID);
				if (!chdir($T_DIR.'/PROMOTER_BLASTN'))										failProcess($JOB_ID."020",'Unable to get to PROMOTER_BLASTN for '.$TAX_ID);
				system($GLB_VAR['TOOL']['MAKEBLAST'].' -in ../'.$TAX_ID.'_PROMOTER.fna -dbtype nucl -out '.$TAX_ID.'_PROMOTER'.' &> PREP_LOG',$return_code);
				if ($return_code !=0)														failProcess($JOB_ID."021",'Unable to create blastn files for '.$TAX_ID);
				addLog("Create bowtie files for promoter");
				if (!is_dir($T_DIR.'/PROMOTER_BOWTIE') &&  !mkdir($T_DIR.'/PROMOTER_BOWTIE'))failProcess($JOB_ID."022",'Unable to create directory PROMOTER_BOWTIE for '.$TAX_ID);
				if (!chdir($T_DIR.'/PROMOTER_BOWTIE'))										 failProcess($JOB_ID."023",'Unable to get to PROMOTER_BOWTIE for '.$TAX_ID);
				system($GLB_VAR['TOOL']['BOWTIE_BUILD'].' -r ../'.$TAX_ID.'_PROMOTER.fna BOWTIE_PROMOTER_'.$TAX_ID.' &> PREP_LOG',$return_code);
				if ($return_code !=0)														failProcess($JOB_ID."024",'Unable to create bowtie files for '.$TAX_ID);
				addLog("Create botwie2 files for promoter");
				if (!is_dir($T_DIR.'/PROMOTER_BOWTIE2') &&  !mkdir($T_DIR.'/PROMOTER_BOWTIE2'))failProcess($JOB_ID."025",'Unable to create directory PROMOTER_BOWTIE2 for '.$TAX_ID);
				if (!chdir($T_DIR.'/PROMOTER_BOWTIE2'))										 failProcess($JOB_ID."026",'Unable to get to PROMOTER_BOWTIE 2for '.$TAX_ID);
				system($GLB_VAR['TOOL']['BOWTIE2_BUILD'].' -r ../'.$TAX_ID.'_PROMOTER.fna BOWTIE2_PROMOTER_'.$TAX_ID.' &> PREP_LOG',$return_code);
				if ($return_code !=0)														failProcess($JOB_ID."027",'Unable to create bowtie2 files for '.$TAX_ID);
			}

			if (!chdir('..'))																failProcess($JOB_ID."028",'Unable to move back from '.$TAX_ID);
			
		}
	}	

successProcess();






function revComp($str)
{


	$REV_TABLE=array(
	"A"=>"T",
	"T"=>"A",
	"U"=>"A",
	"G"=>"C",
	"C"=>"G",
	"Y"=>"R",
	"R"=>"Y",
	"K"=>"M",
	"M"=>"K",
	"B"=>"V",
	"D"=>"H",
	"H"=>"D",
	"V"=>"B",
	"N"=>"N",
	"a"=>"t",
	"t"=>"a",
	"u"=>"a",
	"g"=>"c",
	"c"=>"g",
	"y"=>"r",
	"r"=>"y",
	"k"=>"m",
	"m"=>"k",
	"b"=>"v",
	"d"=>"h",
	"h"=>"d",
	"v"=>"b",
	"n"=>"n"
);

	$LEN=strlen($str);
	$REV_STR=strrev($str);
	$REV='';
	for($I=0;$I<$LEN;++$I)
	{
		//echo $I.' '.$REV_STR[$I].' '.$REV_TABLE[$REV_STR[$I]]."\n";
		$REV.=$REV_TABLE[$REV_STR[$I]];
	}
	return $REV;
}


	

?>

