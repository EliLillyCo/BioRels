<?php

/**
 SCRIPT NAME: dl_trembl
 PURPOSE:     Process all TrEmbl files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_trembl';

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
	/// GEt parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_uniprot_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	$W_DIR.='/TREMBL';							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create new TREMBL process dir '.$W_DIR);
												if (!chdir($W_DIR)) 					failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory: ".$W_DIR);

	///Check FTP path:
	if (!isset($GLB_VAR['LINK']['FTP_UNIPROT']))										failProcess($JOB_ID."006",'FTP_UNIPROT path no set');
	$UNI_LINK=$GLB_VAR['LINK']['FTP_UNIPROT'].'/knowledgebase/complete/';
	
	/// Check CONFIG_USER for WITH_UNIPROT_TREMBL
	if (!isset($GLB_VAR['WITH_UNIPROT_TREMBL']))										failProcess($JOB_ID."007",'WITH_UNIPROT_TREMBL Not set in CONFIG_GLOBAL');
	if (!in_array($GLB_VAR['WITH_UNIPROT_TREMBL'],array('Y','N')))						failProcess($JOB_ID."008",'WITH_UNIPROT_TREMBL must be either Y or N');
	
	/// WITH_UNIPROT_TREMBL not requested -> stop there
	if ($GLB_VAR['WITH_UNIPROT_TREMBL']=='N')successProcess();


addLog("Download TrEMBL");

	$WEB_PATH=$UNI_LINK.'/uniprot_trembl.dat.gz';
	if (!dl_file($WEB_PATH,3,'uniprot_trembl.dat.gz'))									failProcess($JOB_ID."009",'Unable to download swissprot protein ');
	
	/// Extract the file:
	if (!ungzip('uniprot_trembl.dat.gz'))		   										failProcess($JOB_ID."010",'Unable to extract trembl protein ');

addLog("Download TrEMBL sequences");
	$WEB_PATH=$UNI_LINK.'/uniprot_trembl.fasta.gz';
	if (!dl_file($WEB_PATH,3,'uniprot_trembl.fasta.gz'))								failProcess($JOB_ID."011",'Unable to download trembl fasta ');
	/// Extract the file:
	if (!ungzip('uniprot_trembl.fasta.gz'))		   										failProcess($JOB_ID."012",'Unable to extract trembl fasta ');


addLog("Extract Gene Ids");

	/// This section will create pointers for the records
	/// This will help pp_uniprot to look up for new/obsolete records

	$fp=fopen('uniprot_trembl.dat','r');
	$fpO=fopen('trembl_list','w');
	if (!$fp)																			failProcess($JOB_ID."013",'Unable to open trembl data ');
	if (!$fpO)																			failProcess($JOB_ID."014",'Unable to open trembl_list');
	
	$CURR_UID='';
	$AC=array();
	$TAX_ID=array();
	$GENE_ID=array();
	$START_P=0;


	while(!feof($fp))
	{
		$pos=ftell($fp);
		$line=stream_get_line($fp,10000,"\n");
		$head=substr($line,0,2);

		/// Handle the ID line
		if ($head=='ID')
		{
			$START_P=$pos;
			$tab=array_values(array_filter(explode(" ",$line)));
			if (!isset($tab[1]))
			{
				print_r($tab);
				$CURR_UID='N/A';
			}
			else $CURR_UID=$tab[1];
		}
		/// Handle the AC line
		/// Get all the accession numbers
		else if ($head=='AC')
		{
			$tab=array_values(array_filter(explode(";",substr($line,5))));
			foreach ($tab as $I) $AC[]=trim($I);
		}
		/// Get the tax id
		else if ($head=='OX')
		{
			if (strpos($line,'OX   NCBI_TaxID=')!==false)
			{
				// Handle two situations:
				//OX   NCBI_TaxID=9606;
				//OX   NCBI_TaxID=9606 {ECO:0000313|Ensembl:ENSP00000474964.1, ECO:0000313|Proteomes:UP000005640};

				$pos=strpos($line,'OX   NCBI_TaxID=');
				$pos2=strpos($line,';',$pos+16);
				$pos3=strpos($line,' ',$pos+16);
				if ($pos3===false)$pos4=$pos2;
				else $pos4=min($pos2,$pos3);
			//	echo substr($line,$pos+16,$pos4-$pos-16)."/".$line."\n";
				$TAX_ID[]=substr($line,$pos+16,$pos4-$pos-16);

			}
		}
		/// Get the Gene ID:
		else if ($head=='DR')
		{
			if (strpos($line,'DR   GeneID;')!==false)
			{
					$tab=array_values(array_filter(explode(";",substr($line,13))));	
					$GENE_ID[]=$tab[0];	

			}
			
		}
		/// Handle the end of the entry
		/// Print the data to the output file
		else if ($head=='//')
		{
			fputs($fpO,implode('|',$GENE_ID)."\t".
						$CURR_UID."\t".
						implode("|",$AC)."\t".
						implode("|",$TAX_ID)."\t".
						$START_P."\n");
			$CURR_UID='';
			$AC=array();
			$TAX_ID=array();
			$GENE_ID=array();
		}
	}
	fclose($fp);
	fclose($fpO);

$fp=fopen('uniprot_trembl.fasta','r');if (!$fp)										failProcess($JOB_ID."015",'Unable to open uniprot_trembl.fasta'); 
	$fpR=fopen('uniprot_trembl_fasta.pointers','w');if (!$fpR)							failProcess($JOB_ID."016",'Unable to open uniprot_trembl_fasta.pointers'); 
	while(!feof($fp))
	{
		///Getting file position BEFORE getting the line
		$Fpos=ftell($fp);
		$line=stream_get_line($fp,1000,"\n");
		/// Getting the name
		if (substr($line,0,1)!='>')continue;
		$pos=strpos($line,' ');
		$tab=explode("|",substr($line,1,$pos-1));

		/// Saving the position and the name
		fputs($fpR,$tab[1]."\t".$tab[2]."\t".$Fpos."\n");
	}
	fclose($fp);

addLog("Create Blast Database");

	exec($GLB_VAR['TOOL']['MAKEBLAST'].'  -in uniprot_trembl.fasta -parse_seqids -dbtype prot',$res,$return_code);
	if ($return_code!=0){print_r($res);													failProcess($JOB_ID."017",'Unable to create blast db'); }
	
	
	


successProcess();
exit;


?>

