<?php

/**
 SCRIPT NAME: dl_proteome
 PURPOSE:     Process all proteomes files
  
 This script will work in different steps:
	1/ Defining the proteomes to download based on Uniprot proteomes and CONFIG_USER proteome section
	2/ Download the individual proteomes
	3/ Concatenate the proteomes
	4/ Create a blast database
	5/ Clean up the individual proteomes
	6/ Create pointers for the sequences and the entries for faster access
*/

/// Job name - Do not change
$JOB_NAME='dl_proteome';

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


addLog("Access directory");
	///Get parent info:
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_uniprot_rel')];

	/// Setting up working directory:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												   if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory: ".$W_DIR);


	/// checking ftp paths:
	if (!isset($GLB_VAR['LINK']['FTP_UNIPROT']))										failProcess($JOB_ID."005",'FTP_UNIPROT path no set');
	if (!isset($GLB_VAR['LINK']['FTP_UNIPROTEOME']))									failProcess($JOB_ID."006",'FTP_UNIPROTEOME path no set');
	$UNI_LINK=$GLB_VAR['LINK']['FTP_UNIPROT'].'/knowledgebase/complete/';
	
	/// Checking for makeblastdb tool:
	if (!isset($GLB_VAR['TOOL']['MAKEBLAST']))											failProcess($JOB_ID."007",'MAKEBLASTDB tool no set');

addLog("Download speclist");
	$SPEC_LIST_PATH=$GLB_VAR['LINK']['FTP_UNIPROT'].'/knowledgebase/complete/docs/speclist.txt';
	if (!checkFileExist('speclist.txt') &&
	   !dl_file($SPEC_LIST_PATH ))														failProcess($JOB_ID."008",'Unable to download speclist in '.$W_DIR);
	   
	///Creating PROTEOMES sub-directory:
	$W_DIR.='/PROTEOMES';
	if (!is_dir($W_DIR) && !mkdir($W_DIR))												failProcess($JOB_ID."009",'Unable to create PROTEOMES dir '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."010",'Unable to access PROTEOMES dir '.$W_DIR);


	


addLog("Get Proteome list");
	$HAS_PROTEOME=false;

	/// Looking over the different PROTEOMES defined in the configuration file (CONFIG_USER)
	foreach ($GLB_VAR['PROTEOME'] as $TAX=>&$INFO)
	foreach ($INFO as $I=>&$V) 
	{
		$V['STATUS']=false;
		$V['DOWNLOADED']=false;
		$V['FOUND']=false;
		$HAS_PROTEOME=true;
	}
	// When there is no proteome to download, we can consider the process successfully done.
	if (!$HAS_PROTEOME) successProcess();



addLog("Download Proteome file");

	///Proteomes are not defined by taxons but by a Uniprot proteome ID.
	///So we need to download the file containing ALL proteomes to retrieve the ones we need
	if (!checkFileExist('proteome.txt'))
	if (!dl_file($GLB_VAR['LINK']['FTP_UNIPROTEOME'].'/README',3,'proteome.txt'))		failProcess($JOB_ID."011",'Unable to download archive');
	if (!validateLineCount('proteome.txt',15460))										failProcess($JOB_ID."012",'proteome.txt is smaller than expected'); 
	
addLog("Extracting Proteome Ids");
	// FILE FORMAT:
	//Proteome_ID	Tax_ID	OSCODE	SUPERREGNUM	#(1)	#(2)	#(3)	Species Name
	//UP000202840	683179	None	viruses	3	0	3	Saccharum streak virus
	/// So we check the Tax ID and get Proteome ID and superregn
	$fp=fopen('proteome.txt','r');if (!$fp)												failProcess($JOB_ID."013",'Unable to open proteome.txt'); 
	$HEAD=array();
	while (!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		
		if ($line=="")continue;	
		
		///Get the header:
		if (substr($line,0,11)=='Proteome_ID')
		{
			$HEAD=explode("\t",$line);
			continue;
		}
		/// Otherwise we only keep the lines that starts with UP	
		if (substr($line,0,2)!='UP')continue;


		$tab=explode("\t",$line);
		if (count($HEAD)!=count($tab))													failProcess($JOB_ID."014",'Not the same number of columns in proteome.txt');
		/// Combine the header with the line
		$record=array_combine($HEAD,$tab);
		
		///Check we actually have the taxon associated to this proteome in our list in CONFIG_USER:
		if (!isset($GLB_VAR['PROTEOME'][$record['Tax_ID']]))continue;

		$PROTEOME_TAX=&$GLB_VAR['PROTEOME'][$record['Tax_ID']];
		
		foreach ($PROTEOME_TAX as &$PROTEOME_RECORD)
		{
			/// Then we check it's actually the same Proteome ID (usually is)
			if ($PROTEOME_RECORD['Proteome_ID']!=$record['Proteome_ID'])continue;
			$PROTEOME_RECORD['STATUS']=true;
			break;
		}
	
	}
	fclose($fp);
	

addLog("Check Proteome Ids");

	/// Technically, there should always be a proteome available for those species
	foreach ($GLB_VAR['PROTEOME'] as $TAX=>&$INFO)
	foreach ($INFO as $I=>&$V) 
	{
		if (!$V['STATUS'])																failProcess($JOB_ID."015",'Unable to find Proteome ID for '.$TAX);
		
	}


addLog("Download archives");
	foreach ($GLB_VAR['PROTEOME'] as $TAX=>&$INFO)
	{
		addLog("Download archives for ".$TAX);
		downloadProteome($GLB_VAR['LINK']['FTP_UNIPROTEOME'],$TAX,$INFO);

	}



addLog("Concatenate");
	$TXT_S='cat ';
	$TXT_U='cat ';
	/// Merge sequences together and records together
	foreach ($GLB_VAR['PROTEOME'] as $TAX=>&$INFO)
	foreach ($INFO as $I=>&$RECORD) 
	{
		$TXT_S.=$RECORD['Tax_Id'].'_SEQ.txt '.$RECORD['Tax_Id'].'_SEQ_add.txt ';
		$TXT_U.=$RECORD['Tax_Id'].'_PROT_UNIPROT.txt '.$RECORD['Tax_Id'].'_PROT_UNIPROT_add.txt ';
		
	}	
	system($TXT_S.' >ALL_SEQ.txt ',$return_code);		if ($return_code!=0)			failProcess($JOB_ID."016",'Failed to concatenate '.$TXT_S); 
	system($TXT_U.' >ALL_PROT_UNIPROT.txt ',$return_code);  if ($return_code!=0)		failProcess($JOB_ID."017",'Failed to concatenate '.$TXT_U); 



addLog("Cleaning");
	
foreach ($GLB_VAR['PROTEOME'] as $TAX=>&$INFO)
foreach ($INFO as $I=>&$RECORD) 
	{
		
		if (!unlink($RECORD['Tax_Id'].'_SEQ.txt'))												failProcess($JOB_ID."018",'Failed to remove '.$RECORD['Tax_Id'].'_SEQ.txt'); 
		if (!unlink($RECORD['Tax_Id'].'_SEQ_add.txt'))											failProcess($JOB_ID."019",'Failed to remove '.$RECORD['Tax_Id'].'_SEQ_add.txt'); 
		if (!unlink($RECORD['Tax_Id'].'_PROT_UNIPROT_add.txt'))									failProcess($JOB_ID."020",'Failed to remove '.$RECORD['Tax_Id'].'_PROT_UNIPROT_add.txt'); 
		if (!unlink($RECORD['Tax_Id'].'_PROT_UNIPROT.txt'))										failProcess($JOB_ID."021",'Failed to remove '.$RECORD['Tax_Id'].'_PROT_UNIPROT.txt'); 
	}
	
addLog("Create Blast Database");
	
	system($GLB_VAR['TOOL']['MAKEBLAST'].' -in ALL_SEQ.txt -parse_seqids -dbtype prot',$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."022",'Unable to create blast db'); 
	


addLog("Extract Gene Ids");
	createPointers();
	

successProcess();






function downloadProteome($FTP_PATH,$TAX,$INFO)
{
	global $JOB_ID;
	foreach ($INFO as $I=>&$RECORD) 
	{
		addLog("Download archives for ".$RECORD['Proteome_ID'].' dat');
		$TAX_ID=&$RECORD['Tax_Id'];
		$RAW_PATH=$FTP_PATH.'/'.
					ucfirst($RECORD['SUPERREGNUM']).'/'.
					$RECORD['Proteome_ID'].'/'.
					$RECORD['Proteome_ID'].'_'.$TAX_ID;
		

		if (!checkFileExist($TAX_ID.'_PROT_UNIPROT.txt'))
		{
			if (!dl_file($RAW_PATH.'.dat.gz',3,$TAX_ID.'_PROT_UNIPROT.txt.gz'))			failProcess($JOB_ID."A01",'Unable to download protein archive of taxon '.$TAX.' '.$W_DIR);
			if (!ungzip($TAX_ID.'_PROT_UNIPROT.txt.gz'))								failProcess($JOB_ID."A02",'Unable to extract '.$INFO[2].'_PROT_UNIPROT.txt.gz');
		}
		
		if (!checkFileExist($TAX_ID.'_PROT_UNIPROT_add.txt'))
		{
			addLog("Download archives for ".$RECORD['Proteome_ID'].' additional dat');
			$WEB_PATH=$RAW_PATH.'_additional.dat.gz';
			if (!dl_file($WEB_PATH,3,$TAX_ID.'_PROT_UNIPROT_add.txt.gz'))				failProcess($JOB_ID."A03",'Unable to download protein archive of taxon '.$TAX.' '.$W_DIR);
			if (!ungzip($TAX_ID.'_PROT_UNIPROT_add.txt.gz'))							failProcess($JOB_ID."A04",'Unable to extract '.$INFO[2].'_PROT_UNIPROT_add.txt.gz');
		}
		
		
		
		if (!checkFileExist($TAX_ID.'_SEQ.txt') )
		{
			addLog("Download archives for ".$RECORD['Proteome_ID'].' fasta');
			$WEB_PATH=$RAW_PATH.'.fasta.gz';
			if (!dl_file($WEB_PATH,3,$TAX_ID.'_SEQ.txt.gz'))							failProcess($JOB_ID."A05",'Unable to download sequence archive of taxon '.$TAX.' '.$RECORD['Proteome_ID'].' '.$W_DIR);
			if (!ungzip($TAX_ID.'_SEQ.txt.gz'))											failProcess($JOB_ID."A06",'Unable to extract '.$INFO[2].'_SEQ.txt.gz');
		}
		
		
		if (!checkFileExist($TAX_ID.'_SEQ_add.txt'))
		{ 
			addLog("Download archives for ".$RECORD['Proteome_ID'].' additional fasta');
			$WEB_PATH=$RAW_PATH.'_additional.fasta.gz';
			if (!dl_file($WEB_PATH,3,$TAX_ID.'_SEQ_add.txt.gz'))						failProcess($JOB_ID."A07",'Unable to download sequence archive of taxon '.$TAX.' '.$RECORD['Proteome_ID'].' '.$W_DIR);
			if (!ungzip($TAX_ID.'_SEQ_add.txt.gz'))										failProcess($JOB_ID."A08",'Unable to extract '.$INFO[2].'_SEQ.txt.gz');
		}
		
			
			
	
	}
}


function createPointers()
{
	/// This function will create pointers for the sequences
	/// This will help pp_uniprot to look up for new/obsolete records

	global $JOB_ID;
	$fp=fopen('ALL_PROT_UNIPROT.txt','r');
	$fpO=fopen('proteome_list','w');
	$fpE=fopen('proteome_ensembl','w');
	if (!$fp)																			failProcess($JOB_ID."B01",'Unable to open ALL_PROT_UNIPROT data ');
	if (!$fpO)																			failProcess($JOB_ID."B02",'Unable to open proteome_list');
	if (!$fpE)																			failProcess($JOB_ID."B03",'Unable to open proteome_ensembl');
	
	$CURR_UID='';
	$AC=array();
	$TAX_ID=array();
	$GENE_ID=array();
	$START_P=0;


	while(!feof($fp))
	{
		$pos=ftell($fp);
		$line=stream_get_line($fp,1000,"\n");
		$head=substr($line,0,2);

		/// Getting the Uniprot ID
		if ($head=='ID')
		{
			$START_P=$pos;
			$tab=array_values(array_filter(explode(" ",$line)));
			$CURR_UID=$tab[1];
		}
		/// And all the Accessions
		else if ($head=='AC')
		{
			$tab=array_values(array_filter(explode(";",substr($line,5))));
			foreach ($tab as $I) $AC[]=trim($I);
		}
		/// The taxon ID
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
		// Getting identifiers
		else if ($head=='DR')
		{

			$tab=array_values(array_filter(explode(" ",$line)));//print_r($tab);
			/// We are only interested in NCBI GeneID and Ensembl
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
		/// Once we reach the end of the record, we write the data
		else if ($head=='//')
		{
			fputs($fpO,
				implode("|",$GENE_ID)."\t".
				$CURR_UID."\t".
				implode("|",$AC)."\t".
				implode("|",$TAX_ID)."\t".
				$START_P."\n");
			/// Reset the variables
			$CURR_UID='';
			$AC=array();
			$TAX_ID=array();
			$GENE_ID=array();
		}
	}
	fclose($fp);
	fclose($fpO);
	fclose($fpE);


	/// For the sequences, we will create a pointer file:
	$fp=fopen('ALL_SEQ.txt','r');if (!$fp)										failProcess($JOB_ID."B04",'Unable to open ALL_SEQ.txt'); 
	$fpR=fopen('ALL_SEQ.pointers','w');if (!$fpR)								failProcess($JOB_ID."B05",'Unable to open ALL_SEQ.pointers'); 
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
}
?>
