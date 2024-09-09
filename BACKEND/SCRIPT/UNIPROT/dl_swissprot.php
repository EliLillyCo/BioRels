<?php

/**
 SCRIPT NAME: dl_swissprot
 PURPOSE:     Process all swissprot files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_swissprot';

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


addLog("Setting up:");
	/// GEt parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_uniprot_rel')];
	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);

	/// Create SPROT subdirectory
	$W_DIR.='/SPROT';							if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create new process dir '.$W_DIR);
												   if (!chdir($W_DIR)) 					failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory: ".$W_DIR);

	///Check FTP path:
	if (!isset($GLB_VAR['TOOL']['MAKEBLAST']))											failProcess($JOB_ID."006",'MAKEBLASTDB tool no set');											   
	if (!isset($GLB_VAR['LINK']['FTP_UNIPROT']))										failProcess($JOB_ID."007",'FTP_UNIPROT path no set');
	$UNI_LINK=$GLB_VAR['LINK']['FTP_UNIPROT'].'/knowledgebase/complete/';


	/// Check CONFIG_USER for WITH_UNIPROT_SP
	if (!isset($GLB_VAR['WITH_UNIPROT_SP']))											failProcess($JOB_ID."008",'WITH_UNIPROT_SP Not set in CONFIG_GLOBAL');
	if (!in_array($GLB_VAR['WITH_UNIPROT_SP'],array('Y','N')))							failProcess($JOB_ID."009",'WITH_UNIPROT_SP  value must be either Y or N');
	
	
	/// WITH_UNIPROT_SP not requested -> stop there
	if ($GLB_VAR['WITH_UNIPROT_SP']=='N')successProcess();



addLog("Download swissprot");
	if (!is_file('uniprot_sprot.dat'))
	{
		$WEB_PATH=$UNI_LINK.'/uniprot_sprot.dat.gz';
		if (!dl_file($WEB_PATH,3,'uniprot_sprot.dat.gz'))									failProcess($JOB_ID."010",'Unable to download swissprot ');
		if (!ungzip('uniprot_sprot.dat.gz'))		   										failProcess($JOB_ID."011",'Unable to extract swissprot ');
		// Check the file size to have at least those number of lines
		/// This is from a very old version of uniprot, so the number of lines is not the same as the current version
		if (!validateLineCount('uniprot_sprot.dat',63266226))								failProcess($JOB_ID."012",'uniprot_sprot.dat is smaller than expected'); 
	
	}
	if (!is_file('uniprot_all.fasta'))
	{
	addLog("Download swissprot sequences");
		if (!is_file('uniprot_sprot.fasta'))
		{
			$WEB_PATH=$UNI_LINK.'/uniprot_sprot.fasta.gz';
			if (!dl_file($WEB_PATH,3,'uniprot_sprot.fasta.gz'))								failProcess($JOB_ID."013",'Unable to download swissprot ');
			if (!ungzip('uniprot_sprot.fasta.gz'))		   									failProcess($JOB_ID."014",'Unable to extract swissprot ');
		}

	addLog("Download swissprot  varsplic");
		if (!is_file('uniprot_sprot_varsplic.fasta'))
		{
			$WEB_PATH=$UNI_LINK.'/uniprot_sprot_varsplic.fasta.gz';
			if (!dl_file($WEB_PATH,3,'uniprot_sprot_varsplic.fasta.gz'))					failProcess($JOB_ID."015",'Unable to download swissprot varsplic ');
			if (!ungzip('uniprot_sprot_varsplic.fasta.gz'))		  							failProcess($JOB_ID."016",'Unable to extract swissprot varsplic ');
		}

		if (!validateLineCount('uniprot_sprot.fasta',4196296))								failProcess($JOB_ID."017",'uniprot_sprot.fasta is smaller than expected'); 
		if (!validateLineCount('uniprot_sprot_varsplic.fasta',459162))						failProcess($JOB_ID."018",'uniprot_sprot_varsplic.fasta is smaller than expected'); 

	}	

	
addLog("Create Blast Database");
	/// Merge the two fasta files
	system('cat uniprot_sprot.fasta uniprot_sprot_varsplic.fasta > uniprot_all.fasta',$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."019",'Unable to merge files'); 
	
	/// Create the blast database
	system($GLB_VAR['TOOL']['MAKEBLAST'].' -in uniprot_all.fasta -parse_seqids -dbtype prot',$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."020",'Unable to create blast db'); 
	if (is_file('uniprot_sprot.fasta') && !unlink('uniprot_sprot.fasta'))				failProcess($JOB_ID."021",'Unable to delete uniprot_sprot.fasta'); 
	if (is_file('uniprot_sprot_varsplic.fasta') && !unlink('uniprot_sprot_varsplic.fasta'))	failProcess($JOB_ID."022",'Unable to delete uniprot_sprot_varsplic.fasta'); 

addLog("Extract Gene Ids");

	/// This section will create pointers for the records
	/// This will help pp_uniprot to look up for new/obsolete records


	$fp=fopen('uniprot_sprot.dat','r');if (!$fp)										failProcess($JOB_ID."023",'Unable to open uniprot_sprot data ');
	$fpO=fopen('sprot_list','w');	   if (!$fpO)										failProcess($JOB_ID."024",'Unable to open sprot_list');
	$fpE=fopen('sprot_ensembl','w');   if (!$fpE)										failProcess($JOB_ID."025",'Unable to open sprot_ensembl');
	
	
	
	$CURR_UID='';$AC=array();$TAX_ID=array();$GENE_ID=array();
	$START_P=0;;
	while(!feof($fp))
	{
		$pos=ftell($fp);
		$line=stream_get_line($fp,1000,"\n");
		if ($line=="")continue;
		$head=substr($line,0,2);

		/// ID   001R_FRG3G              Reviewed;         256 AA.
		/// Getting the Uniprot Identifier
		if ($head=='ID')
		{
			$START_P=$pos;
			/// Split the line into words, remove empty elements and re-index the array
			$tab=array_values(array_filter(explode(" ",$line)));
			$CURR_UID=$tab[1];
		}

		/// AC   P0C6U8; Q91G88;
		/// Getting the accession numbers
		else if ($head=='AC')
		{
			$tab=array_values(array_filter(explode(";",substr($line,5))));
			
			foreach ($tab as $I) $AC[]=trim($I);
				
			
		}
		/// Getting the tax id:
		/// OX   NCBI_TaxID=9606;
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
		/// DR   Ensembl; ENST00000361390; ENSP00000354668; ENSG00000198804.
		/// DR   Ensembl; ENST00000361390; ENSP00000354668; ENSG00000198804. [P0C6U8-1]
		/// Getting the gene id and ensembl id
		else if ($head=='DR')
		{
			$tab=array_values(array_filter(explode(" ",$line)));//print_r($tab);
			if ($tab[1]=='GeneID;')
			{
					$tab=array_values(array_filter(explode(";",substr($line,13))));		
					$GENE_ID[]=$tab[0];

			}
			else if ($tab[1]=="Ensembl;")
			{
				for($i=2;$i<=4;++$i)
				{
					$str=substr($tab[$i],0,-1)."\t".$CURR_UID."\t";
					if (isset($tab[5])) $str.= substr($tab[5],1,-1);
					$str.="\n";
					fputs($fpE,$str);
				}
			}
		}
		/// End of the record
		/// We save the information in the file:
		else if ($head=='//')
		{
			fputs($fpO,
				implode("|",$GENE_ID)."\t".
				$CURR_UID."\t".
				implode("|",$AC)."\t".
				implode("|",$TAX_ID)."\t".
				$START_P."\n");
			/// Then we reset the variables
			$CURR_UID='';
			$AC=array();
			$TAX_ID=array();
			$GENE_ID=array();
		}
	}
	fclose($fp);
	fclose($fpO);
	fclose($fpE);


	/// Now we create a pointer for the fasta sequences:
	$fp=fopen('uniprot_all.fasta','r');if (!$fp)									failProcess($JOB_ID."026",'Unable to open uniprot_all.fasta'); 
	$fpR=fopen('uniprot_all_fasta.pointers','w');if (!$fpR)							failProcess($JOB_ID."027",'Unable to open uniprot_all_fasta.pointers'); 
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

successProcess();
exit;


?>

