<?php
ini_set('memory_limit','1000M');



/**
 SCRIPT NAME: dl_ensembl
 PURPOSE:     Download ensembl files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_ensembl';


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


addLog("Setting up");

	///Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_ensembl_rel')];

	/// Get the current release date, which in the case of ensembl is the Ensembl release version
	$NEW_RELEASE=getCurrentReleaseDate('ENSEMBL',$JOB_ID);

	/// We will work with 2 directories
	/// $W_DIR is the directory where we will download the files
	/// $DIR_DATE is the directory where we will create symlink to the different versions

	///Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 									failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 					failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$NEW_RELEASE;			   		   if (!is_dir($W_DIR) && !mkdir($W_DIR))			 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 										failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

	/// Create a new directory for the current date											   
	$PROCESS_CONTROL['DIR']=getCurrDate();
	$DATE=getCurrDate();
	/// Create a new directory for the current date
	$DIR_DATE=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$CK_INFO['DIR'].'/'.$DATE.'/';
	if (!is_dir($DIR_DATE) && !mkdir($DIR_DATE))			 											failProcess($JOB_ID."005",'Unable to create new current date dir '.$DIR_DATE);

	/// Check if FTP_ENSEMBL path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_ENSEMBL']))														failProcess($JOB_ID."006",'FTP_ENSEMBL path no set');
	if (!isset($GLB_VAR['LINK']['FTP_ENSEMBL_ASSEMBLY']))												failProcess($JOB_ID."007",'FTP_ENSEMBL_ASSEMBLY path no set');


	


addLog("Download species_EnsemblVertebrates.txt");
	/// First we download all species, to get the information
	$RELEASE_FTP=$GLB_VAR['LINK']['FTP_ENSEMBL'].'/release-'.$NEW_RELEASE.'/';
	if (!dl_file($RELEASE_FTP.'/species_EnsemblVertebrates.txt',3))										failProcess($JOB_ID."008",'Unable to download species_EnsemblVertebrates.txt ');

	/// If the already created a symbolic link to that file
	if (is_link($DIR_DATE.'/species_EnsemblVertebrates.txt'))
	{
		/// We check if the link is to the same file or not
		if (readlink($DIR_DATE.'/species_EnsemblVertebrates.txt')!=$W_DIR.'/species_EnsemblVertebrates.txt')
		{
			/// If not, we remove the link and create a new one
			if (!unlink($DIR_DATE.'/species_EnsemblVertebrates.txt'))									failProcess($JOB_ID."009",'Unable to remove previous symlink for taxon '.$TAX_ID);
			if (!symlink($W_DIR.'/species_EnsemblVertebrates.txt',$DIR_DATE.'/species_EnsemblVertebrates.txt')) 	failProcess($JOB_ID."010",'Unable to create symlink for taxon '.$TAX_ID);	
		}
	}
	/// If the link does not exist, we create it
	else if (!symlink($W_DIR.'/species_EnsemblVertebrates.txt',$DIR_DATE.'/species_EnsemblVertebrates.txt')) failProcess($JOB_ID."011",'Unable to create symlink for taxon '.$TAX_ID);


	/// Reading the ensembl species file
	$fp=fopen('species_EnsemblVertebrates.txt','r');if (!$fp)											failProcess($JOB_ID."012",'Unable to open species_EnsemblVertebrates');
	$HEAD=array_flip(explode("\t",stream_get_line($fp,10000,"\n")));
	/// Ensembl INFO will be a 2D array with the taxonomy id as the first key and the assembly accession as the second key, the value being the complete information
	$ENSEMBL_INFO=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		$ENSEMBL_INFO[$tab[$HEAD['taxonomy_id']]][$tab[$HEAD['assembly_accession']]]=$tab;
	}
	fclose($fp);

	/// Loop over requested taxons
	// If no GENOME defined, we can stop here
	if (!isset($GLB_VAR['GENOME']))																		failProcess($JOB_ID."013",'GENOME NOT SET');
	if ($GLB_VAR['GENOME']==array())successProcess("VALID");

	// $TAXON_INFO is a reference to the global GENOME variable
	$TAXON_INFO=&$GLB_VAR['GENOME'];

	// Looping over each taxon
	foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
	{
		
		addLog("##Processing Taxon ".$TAX_ID);
		if (!isset($ENSEMBL_INFO[$TAX_ID]))																failProcess($JOB_ID."014",'Unable to find Taxon in Ensembl:'.$TAX_ID);
		if (!is_dir($W_DIR.'/'.$TAX_ID) && !mkdir($W_DIR.'/'.$TAX_ID))									failProcess($JOB_ID."015",'Unable to create Tax directory '.$TAX_ID);
		
		/// Looping over the assemblies requested by the user
		foreach ($LIST as &$INFO)	
		{
			// We only want the Ensembl genomes (since we can also have RefSeq)
			if ($INFO['Source']!='ENSEMBL')continue;
			/// This should be done at the start of each build to ensure the right directory is used
			if (!chdir($W_DIR.'/'.$TAX_ID))																failProcess($JOB_ID."016",'Unable to access Tax directory '.$TAX_ID);

			$ASSEMBLY=$INFO['Assembly_Acc'];
			
			if (!isset($ENSEMBL_INFO[$TAX_ID][$ASSEMBLY]))												failProcess($JOB_ID."017",'Unable to find assembly '.$ASSEMBLY.' in Taxon '.$TAX_ID);
			
			/// We create the directory for this assembly if it does not exist
			/// and get in it
			if (!is_dir($ASSEMBLY) && !mkdir($ASSEMBLY))												failProcess($JOB_ID."018",'Unable to create Tax directory '.$ASSEMBLY);
			if (!chdir($ASSEMBLY))																		failProcess($JOB_ID."019",'Unable to access Tax directory '.$ASSEMBLY);
			
			
			$ENS_DIR_NAME=$ENSEMBL_INFO[$TAX_ID][$ASSEMBLY][1];
			
			/// Therefore the full path to the assembly is
			$ASSEMBLY_DIR=$W_DIR.'/'.$TAX_ID.'/'.$ASSEMBLY;
			
			/// And the BUILD_DIR to be used as symlink for the ASSEMBLY_DIR in our DIR_DATE is:
			$BUILD_DIR=$TAX_ID.'__'.$INFO['Assembly_Acc'].'__'.$INFO['Assembly_name'];
			echo $ASSEMBLY_DIR.' ' .$DIR_DATE.'/'.$BUILD_DIR."\n";



			if (is_link($DIR_DATE.'/'.$BUILD_DIR))
			{
				if (readlink($DIR_DATE.'/'.$BUILD_DIR)!=$ASSEMBLY_DIR)
				{
					if (!unlink($DIR_DATE.'/'.$BUILD_DIR))												failProcess($JOB_ID."020",'Unable to remove previous symlink for taxon '.$BUILD_DIR);
					if (!symlink($ASSEMBLY_DIR,$DIR_DATE.'/'.$BUILD_DIR)) 								failProcess($JOB_ID."021",'Unable to create symlink for taxon '.$BUILD_DIR);	
				}
			}
			else if (!symlink($ASSEMBLY_DIR,$DIR_DATE.'/'.$BUILD_DIR)) 									failProcess($JOB_ID."022",'Unable to create symlink for taxon '.$BUILD_DIR);

			/// Update RELEASE_DATA  to reflect the new assembly accession for this taoxn
			$CURR_RELEASE=getCurrentReleaseDate('ENSEMBL__'.$TAX_ID.'__'.$ASSEMBLY,$JOB_ID);
			if ($CURR_RELEASE ==$INFO['Gene_build'])
			{
				addLog($TAX_ID.' - Same gene build');
				continue;
			}
			

			/// Downloading the accession assembly file
			$PATH=$GLB_VAR['LINK']['FTP_ENSEMBL_ASSEMBLY'].'/'.substr($ASSEMBLY,0,7).'/'.substr($ASSEMBLY,0,10).'/'.$ASSEMBLY.'_sequence_report.txt';
			if (!checkFileExist($TAX_ID.'_assembly.txt'))
			if (!dl_file($PATH,3,$TAX_ID.'_assembly.txt'))												failProcess($JOB_ID."023",'Unable to download assembly for '.$TAX_ID);
			
			/// Downloading dna, cdna, ncrna
			downloadFTPFile($GLB_VAR['LINK']['FTP_ENSEMBL'].'/current_fasta/'.$ENS_DIR_NAME.'/dna/','dna','.dna_sm.toplevel.fa.gz',true,false);
			downloadFTPFile($GLB_VAR['LINK']['FTP_ENSEMBL'].'/current_fasta/'.$ENS_DIR_NAME.'/cdna/','cdna','.all.fa.gz',true,false);
			downloadFTPFile($GLB_VAR['LINK']['FTP_ENSEMBL'].'/current_fasta/'.$ENS_DIR_NAME.'/ncrna/','ncrna','.ncrna.fa.gz',true,false);
			
			/// HERE IN SOME INSTANCES THE FILE NAME IS DIFFERENT
			$SUFFIX='';
			if ($TAX_ID==9606)$SUFFIX='_patch_hapl_scaff';
			if ($TAX_ID==10141)$SUFFIX='_'.$NEW_RELEASE;
			
			downloadFTPFile($GLB_VAR['LINK']['FTP_ENSEMBL'].'/current_gff3/'.$ENS_DIR_NAME,'gff','.chr'.$SUFFIX.'.gff3.gz',true,false);
			
			
			// Rename and unzip
			if ($handle = opendir('.')) {
				echo "Directory handle: $handle\n";
				echo "Entries:\n";
			
				// Looking for gz files
				while (false !== ($entry = readdir($handle))) {
					if (substr($entry,-3)!='.gz')continue;
					echo $entry."\n";
					if (!ungzip($entry))																					failProcess($JOB_ID."024",'Unable to ungzip '.$entry.' for '.$TAX_ID);
					// We rename the file
					if (preg_match('/dna_sm.toplevel.fa/',$entry) && !rename(substr($entry,0,-3),$TAX_ID.'_seq.fa'))		failProcess($JOB_ID."025",'Unable to rename '.substr($entry,0,-3).' to '.$TAX_ID.'_seq.fa'.' for '.$TAX_ID);
					if (preg_match('/cdna.all.fa/',$entry) 		  && !rename(substr($entry,0,-3),$TAX_ID.'_cdna_rna.fa'))	failProcess($JOB_ID."026",'Unable to rename '.substr($entry,0,-3).' to '.$TAX_ID.'_cdna_rna.fna'.' for '.$TAX_ID);
					if (preg_match('/ncrna.fa/',$entry) 		  && !rename(substr($entry,0,-3),$TAX_ID.'_ncrna.fa'))		failProcess($JOB_ID."027",'Unable to rename '.substr($entry,0,-3).' to '.$TAX_ID.'_ncrna.fna'.' for '.$TAX_ID);
					if (preg_match('/.gff3/',$entry) 			  && !rename(substr($entry,0,-3),$TAX_ID.'_gene.gff3'))		failProcess($JOB_ID."028",'Unable to rename '.substr($entry,0,-3).' to '.$TAX_ID.'_seq.fna'.' for '.$TAX_ID);

				}
				closedir($handle);
			}

			//// Next, to speed up the transcriptome process, we need to create for each transcript a pointer to its location in the different files
			/// Position 0 in array: pointer to sequence. Because there is two files, we add a prefix NC_ or CDNA_
			$POINTERS=array();
			$fp=fopen($TAX_ID.'_ncrna.fa','r');if (!$fp)																	failProcess($JOB_ID."029",'Unable to open ncrna.fa');
			if ($fp){
				while(!feof($fp))
				{
					$fpos=ftell($fp);/// Position in the file
					$line=stream_get_line($fp,1000,"\n");
					if (substr($line,0,1)!='>')continue;/// We only want to process header lines
					$pos=strpos($line,'.');
					$name=substr($line,1,$pos-1);/// Get the name
					// we add a prefix to avoid confusion with cdna
					$POINTERS[$name][0]='NC_'.$fpos;
				}
				fclose($fp);
			}

			$fp=fopen($TAX_ID.'_cdna_rna.fa','r');if (!$fp)																		failProcess($JOB_ID."030",'Unable to open cfna_ncrna.fa');
			if ($fp){
				while(!feof($fp))
				{
					$fpos=ftell($fp);/// Position in the file
					$line=stream_get_line($fp,1000,"\n");
					if (substr($line,0,1)!='>')continue;/// We only want to process header lines
					$pos=strpos($line,'.');
					$name=substr($line,1,$pos-1);// Get the name
					// we add a prefix to avoid confusion with NC
					$POINTERS[$name][0]='CDNA_'.$fpos;
				}
				fclose($fp);
			}
			
			$fp=fopen($TAX_ID.'_gene.gff3','r');if (!$fp)																	failProcess($JOB_ID."031",'Unable to open gene.ff3');
			if ($fp)	
			{
				while(!feof($fp))
				{
					$line=stream_get_line($fp,100000,"\n");
					if ($line==''||substr($line,0,1)=='#')continue;
					$entry=explode("\t",$line);
					
					// We only do additional process for mRNA with CDS and exon
					if ($entry[2]!='CDS'&&$entry[2]!='exon')continue;
					
					// We convert the info field to an array
					$entry[8]=convertGFFLine($entry[8]);
					

					$tab=explode(":",$entry[8]['Parent']);
					if ($tab[0]!='transcript')continue;
					if ($entry[2]=='exon') $entry[2]=$entry[8]['rank'];
					
					// We add the pointer to the sequence
					if (!isset($POINTERS[$tab[1]][1]))$POINTERS[$tab[1]][1]=$entry[0].'|'.$entry[2].'|'.$entry[3].'|'.$entry[4];
					else $POINTERS[$tab[1]][1].="\t".$entry[0].'|'.$entry[2].'|'.$entry[3].'|'.$entry[4];
					
				}
				fclose($fp);
			}
		
			$fp=fopen($TAX_ID.'_pointers.csv','w');if (!$fp)																	failProcess($JOB_ID."032",'Unable to open pointers.csv');
			foreach ($POINTERS as $NAME=>&$RECORD)
			fputs($fp,$NAME."\t".json_encode($RECORD)."\n");
			fclose($fp);
			
	
			/// Update RELEASE_DATA  to reflect the new assembly accession for this taxon
			updateReleaseDate($JOB_ID,'ENSEMBL__'.$TAX_ID.'__'.$ASSEMBLY,$INFO['Gene_build']);
			




		
		}
	}
	successProcess();











	?>
	
