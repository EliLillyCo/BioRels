<?php

error_reporting(E_ALL);
ini_set('memory_limit','5000M');

/// total number of jobs - if you change it, don't forget to change it in pmj_translate
$TOT_JOB=100;
$JOB_RUNID=$argv[1];

/// Job name - Do not change
$JOB_NAME='process_translate';

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
 

/// Raw code is the conversion table for eukaryotes
$RAW_CODE=array('FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG',
			    '---M------**--*----M---------------M----------------------------',
				'TTTTTTTTTTTTTTTTCCCCCCCCCCCCCCCCAAAAAAAAAAAAAAAAGGGGGGGGGGGGGGGG',
				'TTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGG',
				'TCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAG');
$HUMAN_CODE=array();
for($I=0;$I<strlen($RAW_CODE[0]);++$I)
{
	$HUMAN_CODE[$RAW_CODE[2][$I].$RAW_CODE[3][$I].$RAW_CODE[4][$I]]=$RAW_CODE[0][$I];
}

addLog("Go to directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_translate')];
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 					failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$U_DIR);
	$U_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$U_DIR);
	echo $U_DIR."\n";
	$W_DIR=$U_DIR.'/DATA/';						if (!is_dir($W_DIR) && !chdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create job dir '.$W_DIR);
	if (!chdir($W_DIR)) 																failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	if (!isset($GLB_VAR['TOOL']['SEQALIGN']))											failProcess($JOB_ID."006",'seqalign tool not set');
	
	if (!is_executable($GLB_VAR['TOOL']['SEQALIGN']))									failProcess($JOB_ID."007",'Unable to execute seqalign');
	if (!isset($GLB_VAR['TOOL']['TRANSEQ']))											failProcess($JOB_ID."008",'transeq tool not set');
	if (!is_executable($GLB_VAR['TOOL']['TRANSEQ']))									failProcess($JOB_ID."009",'Unable to execute transeq');
	
	
	$TRANSLATION_TABLE=array(
		"TTT"=>"F","TTC"=>"F","TTA"=>"L","TTG"=>"L",
		"CTT"=>"L","CTC"=>"L","CTA"=>"L","CTG"=>"L",
		"ATT"=>"I","ATC"=>"I","ATA"=>"I","ATG"=>"M",
		"GTT"=>"V","GTC"=>"V","GTA"=>"V","GTG"=>"V",
		"TCT"=>"S","TCC"=>"S","TCA"=>"S","TCG"=>"S",
		"CCT"=>"P","CCC"=>"P","CCA"=>"P","CCG"=>"P",
		"ACT"=>"T","ACC"=>"T","ACA"=>"T","ACG"=>"T",
		"GCT"=>"A","GCC"=>"A","GCA"=>"A","GCG"=>"A",
		"TAT"=>"Y","TAC"=>"Y","TAA"=>"*","TAG"=>"*",
		"CAT"=>"H","CAC"=>"H","CAA"=>"Q","CAG"=>"Q",
		"AAT"=>"N","AAC"=>"N","AAA"=>"K","AAG"=>"K",
		"GAT"=>"D","GAC"=>"D","GAA"=>"E","GAG"=>"E",
		"TGT"=>"C","TGC"=>"C","TGA"=>"*","TGG"=>"W",
		"CGT"=>"R","CGC"=>"R","CGA"=>"R","CGG"=>"R",
		"AGT"=>"S","AGC"=>"S","AGA"=>"R","AGG"=>"R",
		"GGT"=>"G","GGC"=>"G","GGA"=>"G","GGG"=>"G",
	);
	

addLog("Get list of genes to process");
	//// Since transcriptome data is smaller than proteome, we fetch genes from transcriptomes
	/// The order is important to have consistent chunks between jobs
	$LIST_GENES=runQuery("SELECT DISTINCT gene_id 
					FROM gn_entry GE, gene_seq GS 
					WHERE GS.gn_entry_id = GE.gn_entry_id 
					ORDER BY gene_id ASC");
	if ($LIST_GENES===false)																	failProcess($JOB_ID."010",'Unable to get genes');
	
	

	/// Breaking down of jobs based on job id input
	$LINE_C=count($LIST_GENES);
	$N_P_JOB=ceil($LINE_C/100);
	$START=$N_P_JOB*($JOB_RUNID);
	$END=$N_P_JOB*($JOB_RUNID+1);
	$N_LINE=-1;
	$TO_PROCESS=array();
	echo $LINE_C."\t".$START."\t".$END."\n";
	
	$UNIPROT_TRANSCRIPTS=array();
	for ($IG=$START;$IG<$END;++$IG)
	{
		$UNIPROT_TRANSCRIPTS[$LIST_GENES[$IG]['gene_id']]=array();
		
	}
	$fp=fopen('../uniprot_mapping.csv','r');if (!$fp)									failProcess($JOB_ID."011",'Unable to open uniprot_mapping');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		$tab=explode("\t",$line);
		if (!isset($UNIPROT_TRANSCRIPTS[$tab[0]]))continue;
		
		$UNIPROT_TRANSCRIPTS[$tab[0]][$tab[1]][$tab[2]]=true;
	}


	


addLog("Running genes");
	$fpO=fopen($JOB_RUNID.'.json','w');if (!$fpO)										failProcess($JOB_ID."012",'Unable to open json output file');
	for ($IG=$START;$IG<$END;++$IG)
	{
		echo $START.'<'.$IG.'<'.$END.'=>GENE:'.$LIST_GENES[$IG]['gene_id'];
		if (isset($LIST_GENES[$IG]))
		fputs($fpO,json_encode(processGene($LIST_GENES[$IG]['gene_id']))."\n");
	}	
	fclose($fpO);

///END



  

///Given a gene, find all transcripts and their sequences, but only the CDS portion
function getListTranscripts($gn_entry_id)
{
	global $GLB_VAR;
	$res=runQuery("SELECT transcript_id,transcript_name, transcript_version 
				FROM transcript T, gene_seq GS, gn_entry GE 
				WHERE GE.gn_entry_id = GS.gn_entry_id 
				AND GS.gene_seq_id = T.gene_seq_id 
				AND gene_id=".$gn_entry_id);
	if ($res===false)																failProcess($JOB_ID."013",'Unable to get transcripts for a given gene');
	$RESULTS=array();
	foreach ($res as $LINE){$RESULTS[$LINE['transcript_id']]['INFO']=$LINE;}
	if (count($RESULTS)==0)return array();
	$res=array();
	$res=runQuery("SELECT transcript_pos_id,
					transcript_id,
					nucl,
					seq_pos,
					transcript_pos_type,
					exon_id
					FROM transcript_pos TP, transcript_pos_type TT 
					WHERE transcript_pos_type='CDS' 
					AND TT.transcript_pos_type_ID = TP.seq_pos_type_id 
					AND transcript_id IN (".implode(',',array_keys($RESULTS)).') 
					ORDER BY transcript_id , seq_pos ASC');
	if ($res===false)																failProcess($JOB_ID."014",'Unable to get transcripts positions');
	foreach ($res as $line)$RESULTS[$line['transcript_id']]['SEQ'][$line['seq_pos']]=$line;
	return $RESULTS;
}

/// Given a gene ID, find all sequences
function getProteinSeq($gn_entry)
{
	$res=runQuery("
		SELECT UP.prot_seq_id,iso_id, UP.prot_seq_pos_id, position,letter 
		FROM gn_prot_map GUM, gn_entry GE, prot_seq US, prot_seq_pos UP
		 WHERE GE.gn_entry_id = GUM.gn_entry_id AND GUM.prot_entry_id = US.prot_entry_id 
		 AND US.prot_seq_id = UP.prot_seq_id 
		 AND gene_id=".$gn_entry." 
		 ORDER BY US.prot_seq_id, position ASC");
		 if ($res===false)																failProcess($JOB_ID."015",'Unable to get protein sequences');
	$RESULTS=array();
	foreach ($res as $line)
	{
		if (!isset($RESULTS[$line['prot_seq_id']]['SEQ']))$RESULTS[$line['prot_seq_id']]['SEQ']='';
		$RESULTS[$line['prot_seq_id']]['NAME']=$line['iso_id'];
		unset($line['iso_id']);
		$RESULTS[$line['prot_seq_id']]['SEQ'].=$line['letter'];
		$RESULTS[$line['prot_seq_id']]['POS'][$line['position']]=$line;
	}
	return $RESULTS;
}

function getAlignments($gn_entry)
{
	global $GLB_VAR;
	$res=runQuery("select TU.prot_seq_id,TU.transcript_id 
	FROM gn_entry GE, gene_seq GS, transcript T, tr_protseq_al TU 
	WHERE GE.gn_entry_id = GS.gn_entry_id AND GS.gene_seq_id = T.gene_seq_id 
	AND T.transcript_id =TU.transcript_id AND gene_id=".$gn_entry);
	if ($res===false)																failProcess($JOB_ID."016",'Unable to get previous alignments');
	$TR=array();
	foreach ($res as $l)
	{
		$TR[$l['transcript_id']][$l['prot_seq_id']]=true;
	}
	return $TR;

}




function processGene($gene_id)
{
	global $GLB_VAR;
	global $JOB_RUNID;
	global $HUMAN_CODE;
	global $UNIPROT_TRANSCRIPTS;

	$UNI_MAP=array();
	if (isset($UNIPROT_TRANSCRIPTS[$gene_id]))$UNI_MAP=$UNIPROT_TRANSCRIPTS[$gene_id];

	/// Step1: find all transcripts
	$transcript=getListTranscripts($gene_id);
	if (count($transcript)==0){echo "\t\t\tNO TR\n";return;}
	/// Step2: find all protein sequences
	$PROT=(getProteinSeq($gene_id));
	if (count($PROT)==0){echo "\t\t\tNO PROT\n";return;}

	/// Find all previous alignments:
	echo "\tN TRANSCRIPTS:".count($transcript)."\tN PROTEIN:".count($PROT)."\n";
	$PREV_MATCH=getAlignments($gene_id);

//print_r($UNI_MAP);
	/// Find from Uniprot Extdb 

	global $TRANSLATION_TABLE;
	$MATCH=array();
	/// Now we look over transcripts:
	foreach ($transcript as $TR_ID=>&$INFO)
	{
		//echo "########\n";
		//print_r($INFO['INFO']);
		$STR_LOG=$TR_ID;
		if (!isset($INFO['SEQ'])){echo "MISSING SEQUENCE\t".$STR_LOG."\n";continue;}
	//	print_r($INFO);exit;
		$TRANSCRIPT_NAME=$INFO['INFO']['transcript_name'];
		if ($INFO['INFO']['transcript_version']!='')$TRANSCRIPT_NAME.='.'.$INFO['INFO']['transcript_version'];

		$STR_LOG.="\t".$TRANSCRIPT_NAME;

		$STR='';
		///We generate the transcript sequence, ensuring the type is CDS:
		foreach ($INFO['SEQ'] as $P)if ($P['transcript_pos_type']=='CDS')$STR.=$P['nucl'];
		if ($STR==''){echo "NO CDS\t".$STR_LOG."\n";continue;}

		//echo $INFO['transcript_name'].' '.strlen($STR).' '.($STR%3)."\n";
		/// We just remove the last few nucleotides to ensure its length is a factor of 3, since a codon requires 3 nucleotides
		if (strlen($STR)%3!=0)$STR=substr($STR,0,floor(strlen($STR)/3)*3);

		$str_p='';
		for ($I=0;$I<=strlen($STR);$I+=3)
		{
			$CODON=substr($STR,$I,3);
			if ($TRANSLATION_TABLE[$CODON]=='*')break;
			$str_p.=$TRANSLATION_TABLE[$CODON];
		}
	//	echo $str_p."\n";
		
		/// Save the sequence in a file:
		$fp=fopen('test'.$JOB_RUNID.'.fasta','w');fputs($fp,$STR."\n");fclose($fp);
		if (is_file('test'.$JOB_RUNID.'.pep'))unlink('test'.$JOB_RUNID.'.pep');
		/// To convert it to protein sequence:
		exec($GLB_VAR['TOOL']['TRANSEQ'].' -sequence test'.$JOB_RUNID.'.fasta -outseq test'.$JOB_RUNID.'.pep -trim ',$res,$return_code);
		if ($return_code!=0) {print_r($res);echo "FAILED_TRANSLATION\t".$STR_LOG."\n";continue;}
		$res=array();
		exec('cat test'.$JOB_RUNID.'.pep',$res);
		unset($res[0]);
		
		/// The translated protein sequence
		$str_p=implode('',$res);
		
		/// Now we loop over all protein entries
		foreach ($PROT as $prot_seq_id=>$T)
		{
			/// Ensuring this is not a previously computed match
			if (isset($PREV_MATCH[$TR_ID][$prot_seq_id]))continue;
			/// Perfect match - store it and move on
			
			if ($T['SEQ']==$str_p)
			{
				$MATCH[$prot_seq_id][$TR_ID]['ST']=array(1,1,1,1,1);
				echo "\t\t\t".$TR_ID."\t".$prot_seq_id."\t".$INFO['INFO']['transcript_name']."\t".$T['NAME']."=> IDENTICAL\n";
				continue;
			}
			/// If Uniprot defines it as a match
			else if (isset($UNI_MAP[$TRANSCRIPT_NAME][$T['NAME']])){
				
			}
			/// Otherwise we need to make sure that the length are about the same
			else if (abs(strlen($T['SEQ'])-strlen($str_p))/strlen($T['SEQ'])>0.1)continue;

			///So we do a protein alignment
			$QUERY=$GLB_VAR['TOOL']['SEQALIGN'].' -id -i -rn '.$INFO['INFO']['transcript_name']
			.' -cn '.$T['NAME'].' "'.$str_p.'" "'.$T['SEQ'].'" ';
			//echo $QUERY."\n";
			$res=array();
			exec($QUERY,$res,$return_code);
			
			$data=explode("\t",$res[0]);
			/// And ensure %identity is at least 99%
			if ($data[0]<0.99 && !isset($UNI_MAP[$TRANSCRIPT_NAME][$T['NAME']]))continue;

			/// So we run the alignment again and store it.
			echo "\t\t\t".$TR_ID."\t".$prot_seq_id."\t".$INFO['INFO']['transcript_name']."\t".$T['NAME']."=> ".$res[0]."\n";
			$MATCH[$prot_seq_id][$TR_ID]['ST']=$data;
			//echo $res[0]."\t=>".(($T['SEQ']==$str_p)?"YES":"NO")."\n";
			$QUERY=$GLB_VAR['TOOL']['SEQALIGN'].' -i -rn '.$INFO['INFO']['transcript_name'].' -cn '.$T['NAME'].' "'.$str_p.'" "'.$T['SEQ'].'" ';
			
			exec($QUERY,$MATCH[$prot_seq_id][$TR_ID]['AL'],$return_code);
			
		}
	//	echo "END ########\n";

	}
	if (count($MATCH)==0)return;
	//print_r($MATCH);

	/// RESULTS will contain all final data
	$RESULTS=array();
	$N_RES=0;
	foreach ($MATCH as $PR_ID=>&$LIST_TR)
	{

		echo "\t\t\tPROCESSING ".$PR_ID."\t".$PROT[$PR_ID]['NAME']."\n";
		/// We order by decreasing % identity
		$ORDER=array();
		foreach ($LIST_TR as $TR_ID=>&$INFO)	$ORDER[$INFO['ST'][0]][]=$TR_ID;
		krsort($ORDER);
		
		foreach ($ORDER as $V=>$LTS)
		{
		foreach ($LTS as $TR_ID)
		{
		++$N_RES;
			$INFO=&$MATCH[$PR_ID][$TR_ID];
			$RESULTS[$N_RES]['INFO']=array($TR_ID,$PR_ID,$INFO['ST'][0],$INFO['ST'][1],$INFO['ST'][2],$INFO['ST'][3]);
			/// we only keep perfect match for now
			if (!isset($INFO['AL']) &&$INFO['ST'][0]==1)
			{
				
				echo "\t\t\t\tINSERTING\t".$TR_ID.' '.$PR_ID.' '.$transcript[$TR_ID]['INFO']['transcript_name'].' ' .$PROT[$PR_ID]['NAME']."\n";
				$N_TR=0;$N_P=1;
				$N_F=0;
				if (!isset($transcript[$TR_ID]['SEQ'])){print_r($transcript[$TR_ID]);exit;}
				///So we make the translation:
				$TRS=&$transcript[$TR_ID]['SEQ'];
				foreach($transcript[$TR_ID]['SEQ'] as $K=> $TR_POS)
				{
					
					++$N_TR;if ($N_TR==4){$N_TR=1;$N_P++;}
					if (isset($PROT[$PR_ID]['POS'][$N_P]))
					{
						if ($N_TR==1)
						{
							$TRIPLET=$TRS[$K]['nucl'].$TRS[$K+1]['nucl'].$TRS[$K+2]['nucl'];
							$HC=$HUMAN_CODE[$TRIPLET];
						$P=$PROT[$PR_ID]['POS'][$N_P]['letter'];
							//echo '|'.$TRIPLET.'|'.$HC.'|'.$P."|\n";
							assert($HUMAN_CODE[$TRS[$K]['nucl'].$TRS[$K+1]['nucl'].$TRS[$K+2]['nucl']]==$PROT[$PR_ID]['POS'][$N_P]['letter']);
						}
						$RESULTS[$N_RES]['AL'][]=array($TR_POS['transcript_pos_id'],$N_TR,$PROT[$PR_ID]['POS'][$N_P]['prot_seq_pos_id']);
					}
					else $N_F++;;
					
				}
				
				//assert($N_F==3 || $N_F==0);
			}
			else
			{
				print_r($INFO);
				echo "\t\t\t\tINSERTING\t".$TR_ID.' '.$PR_ID.' '.$transcript[$TR_ID]['INFO']['transcript'].' ' .$PROT[$PR_ID]['NAME']."=>ALT\n";
				$REF_SEQ=$INFO['AL'][1];
				$COMP_SEQ=$INFO['AL'][3];
				$LEN_AL=strlen($REF_SEQ);
				
				$TRS=&$transcript[$TR_ID]['SEQ'];
				$TR_POS=min(array_keys($TRS));$PROT_POS=1;$TRIPLET=0;
				
				for ($I=0;$I<$LEN_AL;++$I)
				{
					
					$AA_FROMTR=$REF_SEQ[$I];
					$AA_FROMPR=$COMP_SEQ[$I];
					echo $I."\t".$AA_FROMTR."\t".$AA_FROMPR."\t".$TR_POS."\t".$PROT_POS."\t";
					if ($AA_FROMPR=='-'){$TR_POS+=3; echo "PROT_GAP\n";}
					else if ($AA_FROMTR=='-'){$PROT_POS++;echo "TR_GAP\n";}
					else if ($AA_FROMPR!=$AA_FROMTR){$PROT_POS++;$TR_POS+=3; echo "DIFF\n";}
					else {
						
						$PROT_AA=&$PROT[$PR_ID]['POS'][$PROT_POS];
						$TRIPLET=$TRS[$TR_POS]['nucl'].$TRS[$TR_POS+1]['nucl'].$TRS[$TR_POS+2]['nucl'];
						$HC=$HUMAN_CODE[$TRIPLET];
						$P=$PROT_AA['letter'];
						echo $HC.'<>'.$P."\n";
						assert($HC==$P);
						for ($SHIFT=0;$SHIFT<3;$SHIFT++)
						{
							$TR_POS_INFO=&$TRS[$TR_POS+$SHIFT];
							$RESULTS[$N_RES]['AL'][]=array($TR_POS_INFO['transcript_pos_id'],$SHIFT+1,$PROT_AA['prot_seq_pos_id']);
							
						}
						$PROT_POS++;$TR_POS+=3;}
						
				}
			//	exit;
			} 
		}
		break;///We want the best ones only
	}
}

return $RESULTS;
}
//print_r($MATCH);

?>
