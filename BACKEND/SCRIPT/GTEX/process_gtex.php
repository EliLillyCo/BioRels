<?php
ini_set('memory_limit','5000M');


/**
 SCRIPT NAME: process_gtex
 PURPOSE:     Compute summary statistics for gene expression data
  				- For each gene and transcript, we compute the following statistics:
  					- AUC
  					- Lower value
  					- Likelihood ratio
  					- Min
  					- Q1
  					- Median
  					- Avg
  					- Q3
  					- Max
 
*/

/// Job name - Do not change
$JOB_NAME='process_gtex';


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



$TOT_JOB=200;
$JOB_R_ID=$argv[1];
if ($JOB_R_ID<0 || $JOB_R_ID>=$TOT_JOB)failProcess($JOB_ID."000",'Invalid job id');

addLog("Check directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_gtex')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];	

	/// Check if the directory exists
	if (!is_dir($W_DIR))																failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if ( !chdir($W_DIR))																failProcess($JOB_ID."002",'Unable to access '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];


	/// Open the result file
	$fpO=fopen("RESULTS_".$JOB_R_ID.'.csv','w');if (!$fpO)								failProcess($JOB_ID."003",'Unable to open RESULT file');

	/// List of all genes/transcripts
	$LIST_GENE_SPLIT=explode("\n",file_get_contents('LIST_GENE_SPLIT'));
	if (count($LIST_GENE_SPLIT)==0)														failProcess($JOB_ID."004",'Unable to get content of LIST_GENE_SPLIT');
	
	/// Get total list of genes/transcripts
	$TOT_GENES=count($LIST_GENE_SPLIT);

	/// 200 jobs. 
	$TOT_JOB=200;
	$PER_JOB=ceil($TOT_GENES/$TOT_JOB);



	addLog("Processing gene range:".($PER_JOB*($JOB_R_ID-1))."\t".($PER_JOB*$JOB_R_ID));

	/// We process only the lines within that range
	$N_P=0;$N_G=0;
	for ($I_GENE=$PER_JOB*($JOB_R_ID-1);
		 $I_GENE<$PER_JOB*$JOB_R_ID;
		 ++$I_GENE)
	{
		
		$tab=explode("\t",$LIST_GENE_SPLIT[$I_GENE]);

		if ($tab[0]=="GENE")		   {$N_G++;processGene($tab[1],$fpO);}
		else if ($tab[0]=="TRANSCRIPT"){$N_P++;processTranscript($tab[1],$fpO);}
	}
	addLog("Processed $N_G genes and $N_P transcripts");

	/// Close the file
	fclose($fpO);



	echo memory_get_usage()." ".memory_get_usage(true)."\n";

	exit(0);


function processTranscript($TRANSCRIPT_DBID,&$fpO)
{
	global $JOB_ID;
	echo ("Processing Transcript ".$TRANSCRIPT_DBID)."\n";
	/// Get existing transcript TPM
	$res=runQuery("SELECT tpm,rna_tissue_id 
				FROM RNA_TRANSCRIPT RG, RNA_SAMPLE RS,RNA_SOURCE RO 
				WHERE  RO.RNA_SOURCE_ID = RS.RNA_SOURCE_ID 
				AND RS.RNA_SAMPLE_ID = RG.RNA_SAMPLE_ID 
				AND ro.source_name='GTEX' 
				AND  TRANSCRIPT_ID=".$TRANSCRIPT_DBID);
	if ($res===false)															failProcess($JOB_ID."A01",'Unable to get RNA Transcript data');
	
	/// Format the data for analysis
	foreach ($res as $line)
	{
		$LIST_TISSUES[(int)$line['rna_tissue_id']]=true;

		/// Count the number of times a given TPM is found in a given tissue
		if (!isset($DATA[$line['tpm']][(int)$line['rna_tissue_id']]))
				$DATA[$line['tpm']][(int)$line['rna_tissue_id']]=1;
		else $DATA[$line['tpm']][(int)$line['rna_tissue_id']]++;
	}
	/// Order by decreasing TPM
	krsort($DATA);
	
	/// Run the data for each tissue
	foreach ($LIST_TISSUES as $TS_ID=>$K)
	{
		processTissue($TRANSCRIPT_DBID,$TS_ID,$DATA,$fpO,true);
	}
}


function processGene($GENE_DBID,&$fpO)
{
	global $JOB_ID;
	/// Get all data for a given gene
	echo ("Processing Gene ".$GENE_DBID)."\n";


	$res=runQuery("SELECT tpm,rna_tissue_id 
	FROM RNA_GENE RG, RNA_SAMPLE RS,RNA_SOURCE RO 
	WHERE  RO.RNA_SOURCE_ID = RS.RNA_SOURCE_ID 
	AND RS.RNA_SAMPLE_ID = RG.RNA_SAMPLE_ID 
	AND ro.source_name='GTEX' AND  GENE_SEQ_ID=".$GENE_DBID);
	if ($res===false)														failProcess($JOB_ID."B01",'Unable to get RNA Gene data');
	/// Prepare the data for analysis
	foreach ($res as $line)
	{
		$LIST_TISSUES[(int)$line['rna_tissue_id']]=true;
		/// Count the number of times a given TPM is found in a given tissue
		if (!isset($DATA[$line['tpm']][(int)$line['rna_tissue_id']]))
				$DATA[$line['tpm']][(int)$line['rna_tissue_id']]=1;
		else $DATA[$line['tpm']][(int)$line['rna_tissue_id']]++;
	}
	// Sort by decreasing TPM
	krsort($DATA);
	//print_r($TMP_TISSUE);
	
	/// compute results for each tissue 
	foreach ($LIST_TISSUES as $TS_ID=>$K)
	{
		processTissue($GENE_DBID,$TS_ID,$DATA,$fpO,false);
	}
	
}


function processTissue($DBID,$TS_ID,&$DATA,&$fpO,$IS_TRANSCRIPT)
{
	///$DATA contains all the TPM for all the tissues for that gene or transcript
	///$TS_ID is the tissue id we are interested in
	/// First we are going to retrieve the TPM for the given tissue
	/// And compute statistics on it
	/// Then we can compute the AUC based on the TPM for the given tissue and other tissues

	$TP=0;/// True positive
	$FP=0;/// False positive
	$TN=0;/// True negative
	$FN=0;/// False negative
	$T_ALL=0;/// Total number of true
	$F_ALL=0;/// Total number of false
	$DATA_TISSUE=array();/// List of TPM for the given tissue
	/// VALUE is TPM
	foreach ($DATA as $VALUE=>&$LIST){
		/// TID is RNA_TISSUE_ID
		foreach ($LIST as $TID=>&$COUNT){
			if ($TID==$TS_ID){
				
				$T_ALL+=$COUNT;
				/// We could have multiple TPM for a given tissue
				for ($I=1;$I<=$COUNT;++$I)$DATA_TISSUE[]=$VALUE;
			}
			else 
			{
				$F_ALL+=$COUNT;
				
			}
		}
	}
	
	/// $DATA_TISSUE will have the list of TPM for the given tissue
	sort($DATA_TISSUE,SORT_NUMERIC);
	
	/// First value: total number of TPM
	$NTOT=count($DATA_TISSUE);
	
	/// Compute average
	if ($NTOT==0)$AVG=0;else $AVG=array_sum($DATA_TISSUE)/$NTOT;
	

	/// getting median, Q1, Q3
	$MEDIAN=0;
	$Q1=-1;
	$Q3=-1;
	
	
	if (floor($NTOT/2)==$NTOT/2){//echo "IN\n";
		$Q1_L=array();
		for ($I=0;$I<$NTOT/2;++$I)$Q1_L[]=$DATA_TISSUE[$I];
		$MEDIAN=getMedian($DATA_TISSUE);
			$Q1=getMedian($Q1_L);
			$Q3_L=array();
		for ($I=$NTOT/2;$I<$NTOT;++$I)$Q3_L[]=$DATA_TISSUE[$I];
		$Q3=getMedian($Q3_L);
	}
	else 
	{
		$Q1_L=array();
		for ($I=0;$I<$NTOT/2-1;++$I)$Q1_L[]=$DATA_TISSUE[$I];
		$MEDIAN=getMedian($DATA_TISSUE);
			$Q1=getMedian($Q1_L);
			$Q3_L=array();
		for ($I=$NTOT/2+1;$I<$NTOT;++$I)$Q3_L[]=$DATA_TISSUE[$I];
		$Q3=getMedian($Q3_L);
	}
	


	/// IQR	range
	$IQR=$Q3-$Q1;
	$LOWER_FENCE=$Q1-1.5*$IQR;
	$UPPER_FENCE=$Q3+1.5*$IQR;

	/// Get max and lower value:
	$MAX_VALUE=0;
	$LOWER_VALUE=1000;
	rsort($DATA_TISSUE);
	foreach ($DATA_TISSUE as $VALUE)
	{
		//echo $VALUE."\t".$Q1."\t".$LOWER_FENCE."\n";
		if ($VALUE<=$Q1 && $VALUE>=$LOWER_FENCE){$LOWER_VALUE=$VALUE;}
	}
	$PREV_FPR=0;$AUC=0;$LR=0;


	/// Compute AUC.
	foreach ($DATA as $VALUE=>&$LIST)
	{
		
		
		foreach ($LIST as $TID=>&$COUNT)
		{
			for ($I=0;$I<$COUNT;++$I)
			{
				/// If this is the tissue we are interested in, we increment TP, else FP
				if ($TID==$TS_ID)$TP++;else $FP++;
				$FN=$T_ALL-$TP;
				$TN=$F_ALL-$FP;
				$TPR=$TP/$T_ALL;
				$FPR=$FP/$F_ALL;
				$TNR=$TN/$F_ALL;
				if ($FPR!=$PREV_FPR)
				{
					$AUC+=$TPR*($FPR-$PREV_FPR);
					$PREV_FPR=$FPR;
				}
			}
		}
		
		if ($VALUE==$LOWER_VALUE)
		{
			$LR=$TPR/(1-$TNR);
		}

	}
	
	
	fputs($fpO,
		(($IS_TRANSCRIPT)?"TRANSCRIPT":"GENE")."\t".
		$DBID."\t".
		$TS_ID."\t".
		$NTOT."\t".
		round($AUC,3)."\t".
		round($LOWER_VALUE,3)."\t".
		round($LR,3)."\t".
		round(min($DATA_TISSUE),3)."\t".
		round($Q1,3)."\t".
		round($MEDIAN,3)."\t".
		round($AVG,3)."\t".
		round($Q3,3)."\t".
		max($DATA_TISSUE)."\n");
	
	//print_r($DATA);
}




	
?>
