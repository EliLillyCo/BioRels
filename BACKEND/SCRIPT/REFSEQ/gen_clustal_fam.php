<?php
ini_set('memory_limit','2000M');
/**
 SCRIPT NAME: gen_clustal_fam
 PURPOSE:     Compute clustal omega on gene
 
*/
$JOB_NAME='gen_clustal_fam';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];

addLog("Create directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_genome_rel')];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'].'/';	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR']; if (!chdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);
	if (!is_dir('CLUSTALW') && !mkdir('CLUSTALW'))									failProcess($JOB_ID."003",'Unable to create CLUSTALW dir in '.$W_DIR);
	//if (!chdir('CLUSTALW'))															failProcess($JOB_ID.'004','Unable to create CLUSTALW dir in '.$W_DIR);

addLog("Check Gene List");
	$CKG_INFO=$GLB_TREE[getJobIDByName('prd_gene')];
	$GN_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'].'/'.$CKG_INFO['DIR'].'/';	
	if (!is_dir($GN_DIR))															failProcess($JOB_ID."004",'Unable to find GENE PRD dir in '.$GN_DIR);
	if (!checkFileExist($GN_DIR.'/HUMAN_GENES.csv'))								failProcess($JOB_ID."005",'Unable to find HUMAN GENES file');

	$fp=fopen($GN_DIR.'/HUMAN_GENES.csv','r');if (!$fp)								failProcess($JOB_ID."006",'Cannot open HUMAN_GENES.csv');
	$line=stream_get_line($fp,100,"\n");
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100,"\n");
		echo $line."\n";
		echo getcwd();
		$tab=explode("\t",$line);
		$query="SELECT DISTINCT COMP_GN_ENTRY_ID, COMP_SYMBOL, COMP_GENE_ID,COMP_GENE_NAME, COMP_SPECIES ,COMP_TAX_ID FROM V_ORTHOLOGS".
" WHERE REF_GENE_ID='".$tab[1]."' AND COMP_TAX_ID IN ('10116','10090','9913','9615','9541')";
		$res=runQuery($query);
		$GENES=array();
		foreach ($res as $entry)	$GENES[]=$entry['COMP_GENE_ID'];
		print_r($GENES);
		mkdir('CLUSTALW/'.$tab[1]);
		$GENE_ID=$tab[1];
		$fpI=fopen('CLUSTALW/'.$tab[1].'/INPUT.fa','w');
		$fpIN=fopen('All_Transcript_index.csv','r');if (!$fpIN) failPRocess($JOB_ID,'Unable to open All_Transcript_index.csv');
		$TR=array();
		while(!feof($fpIN))
		{
			$line=stream_get_line($fpIN,3000,"\n");
			$tab=explode(":",$line);
			if (in_array($tab[2],$GENES))$TR[]=$line;
		}
		fclose($fpIN);
		print_r($TR);
		$fpTR=fopen('All_Transcriptome.fa','r');
		foreach ($TR as $line)
		{
			$seq='';
			$tab=explode(":",$line);
			fseek($fpTR,$tab[5]);
			do{
				$seq.=stream_get_line($fpTR,1000,"\n")."\n";
				if(strlen($seq)>=$tab[6])break;
			}while(!feof($fpTR));
			assert(strlen($seq)==$tab[6]);
			fputs($fpI,'>'.$line."\n".$seq);
		}
		fclose($fpTR);
		chdir('CLUSTALW/'.$GENE_ID);
		exec('clustalw2 -INFILE=INPUT.fa -ALIGN',$res,$return_code);
		print_r($res);
		chdir($W_DIR);
		
	}
	fclose($fp);


	?>
