<?php

/**
 SCRIPT NAME: dl_refseq
 PURPOSE:     Download new genome assembly from RefSeq
 
*/
$JOB_NAME='dl_refseq';

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
	// Get Parent job information
	$CK_GENOME_INFO=$GLB_TREE[getJobIDByName('ck_refseq_rel')];
	
	// We define the working directory based on ck_Refseq_rel directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_GENOME_INFO['TIME']['DEV_DIR'].'/';	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);
	
	/// Setting up the process control directory to the current release so that the next job can pick it up
	$PROCESS_CONTROL['DIR']=$CK_GENOME_INFO['TIME']['DEV_DIR'];
	

	addLog("Working directory:".$W_DIR);

	// Check if weblink exist
	if (!isset($GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY']))								failProcess($JOB_ID."004",'FTP_REFSEQ_ASSEMBLY path no set');
	// Ensure SAMTOOLS are set
	if (!isset($GLB_VAR['TOOL']['SAMTOOLS']))											failProcess($JOB_ID."005",'SAMTOOLS tool not set');
	if (!is_executable($GLB_VAR['TOOL']['SAMTOOLS']))									failProcess($JOB_ID."006",'Unable to execute SAMTOOLS');

	// If no GENOME defined, we can stop here
	if (!isset($GLB_VAR['GENOME']))														failProcess($JOB_ID."007",'GENOME NOT SET');
	if ($GLB_VAR['GENOME']==array())successProcess("VALID");

	// $TAXON_INFO is a reference to the global GENOME variable
	$TAXON_INFO=&$GLB_VAR['GENOME'];

	// Looping over each taxon
	foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
	{
		
		addLog("##Processing Taxon ".$TAX_ID);
		foreach ($LIST as &$INFO)	
		{
			// We only want the RefSeq genomes
			if ($INFO['Source']!='REFSEQ')continue;

			// A build is uniquely identified by its Assembly_Acc and Assembly_name and tax id
			$BUILD_NAME=$INFO['Assembly_Acc'].'_'.$INFO['Assembly_name'];
			// Internal directory name
			$BUILD_DIR=$TAX_ID.'__'.$INFO['Assembly_Acc'].'__'.$INFO['Assembly_name'];
			// RefSeq ftp path
			$BUILD_PATH=$INFO['Gene_build'].'_'.$INFO['Assembly_name'];
			addLog("####Processing build ".$BUILD_NAME);

			// We get the organism name and group
			$ORG_NAME=$INFO['organism_name'];
			$ORG_GROUP=$INFO['group'];

			// We check if the directory is already created
			if (!is_dir($BUILD_DIR))														failProcess($JOB_ID."008",'Unable to find build directory for TAX_ID:'.$TAX_ID.' at '.$BUILD_DIR.' in '.getcwd());
			if (!chdir($BUILD_DIR))															failProcess($JOB_ID."009",'Unable to access build directory for TAX_ID:'.$TAX_ID.' at '.$BUILD_DIR);
			
			
			// We check the status file, ensuring it is new 
			if (!checkFileExist('status.txt'))												failProcess($JOB_ID."010",'Unable to find status.txt');
			$status=explode("\n",file_get_contents('status.txt'));
			addLog("\tCurrent status: ".$status[0]);
			// If the status is not new, we can skip downloading this build
			if ($status[0]!='new')
			{
				addLog("All done!");
				if (!chdir($W_DIR))															failProcess($JOB_ID."011",'Unable to access process dir '.$W_DIR);
				continue;
			}
			
			// Web path to the build with the prefix
			$PATH=$GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY'].$ORG_GROUP.'/'.
				str_replace(" ","_",$ORG_NAME).
				'/all_assembly_versions/'.
				$BUILD_PATH.'/'.
				$BUILD_PATH;
			addLog("\tftp path + prefix:". $PATH);

			// We download the assembly report
			if (!checkFileExist($TAX_ID.'_assembly.txt'))
			{
				addLog("\tDownloading assembly report");
				if (!dl_file($PATH.'_assembly_report.txt',3,$TAX_ID.'_assembly.txt'))		failProcess($JOB_ID."012",'Unable to download assembly report for TAX_ID '.$TAX_ID.' at '.$PATH);
			}else addLog("\tAssembly report already downloaded");
			
			
			// We download the DNA of the genome in fasta format
			if (!checkFileExist($TAX_ID.'_seq.fna'))
			{
				addLog("\tDownloading DNA sequence");
			
				if (!dl_file($PATH.'_genomic.fna.gz',3))										failProcess($JOB_ID."013",'Unable to download DNA for TAX_ID '.$TAX_ID.' at '.$PATH);
			
				if(!ungzip($BUILD_PATH.'_genomic.fna.gz'))										failProcess($JOB_ID."014",'Unable to ungzip DNA for TAX_ID '.$TAX_ID.' at '.$PATH);
			
				if (!rename($BUILD_PATH.'_genomic.fna',$TAX_ID.'_seq.fa'))						failProcess($JOB_ID."015",'Unable to rename DNA for TAX_ID '.$TAX_ID.' at '.$PATH);
			
			}else addLog("\tDNA sequence already downloaded");

			/// Downloading the transcripts in fasta format
			if (!checkFileExist($TAX_ID.'_rna.fna'))
			{
				addLog("\tDownloading RNA sequence");
				
				if (!dl_file($PATH.'_rna.fna.gz',3))										failProcess($JOB_ID."016",'Unable to download RNA for TAX_ID '.$TAX_ID.' at '.$PATH);
			
				if(!ungzip($BUILD_PATH.'_rna.fna.gz'))										failProcess($JOB_ID."017",'Unable to ungzip RNA for TAX_ID '.$TAX_ID.' at '.$PATH);
				
				if (!rename($BUILD_PATH.'_rna.fna',$TAX_ID.'_rna.fa'))						failProcess($JOB_ID."018",'Unable to rename RNA for TAX_ID '.$TAX_ID.' at '.$PATH);
			}else addLog("\tRNA sequence already downloaded");
			

			/// Downloading the gene annotation file.
			if (!checkFileExist($TAX_ID.'_gene.gff'))
			{
				addLog("\tDownloading annotation");
				
				if (!dl_file($PATH.'_genomic.gff.gz',3))									failProcess($JOB_ID."019",'Unable to download annotation for TAX_ID '.$TAX_ID.' at '.$PATH);
			
				if(!ungzip($BUILD_PATH.'_genomic.gff.gz'))									failProcess($JOB_ID."020",'Unable to ungzip annotation for TAX_ID '.$TAX_ID.' at '.$PATH);
				
				if (!rename($BUILD_PATH.'_genomic.gff',$TAX_ID.'_gene.gff'))				failProcess($JOB_ID."021",'Unable to rename annotation for TAX_ID '.$TAX_ID.' at '.$PATH);
			}else addLog("\tAnnotation already downloaded");


			/// We download the alignments - when available
			$PATH=$GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY'].$ORG_GROUP.'/'.
					str_replace(" ","_",$ORG_NAME).'/all_assembly_versions/'.
					$BUILD_PATH.
					'/RefSeq_transcripts_alignments/'.
					$BUILD_PATH;

			/// List of files to download for the alignments
			$FILES=array('_knownrefseq_alns.bam',
						'_knownrefseq_alns.bam.bai',
						'_modelrefseq_alns.bam',
						'_modelrefseq_alns.bam.bai');
			$FAILED=0;
			foreach ($FILES as $F)
			{
				if (checkFileExist($TAX_ID.$F))continue;
				
				addLog("\tDownloading alignment ".$F);
				
				if (!dl_file($PATH.$F,3))									
				{
					$FAILED++;
					addLog("\t\tFailed\n");
					continue;
				}	
				
				if (!rename($BUILD_PATH.$F,$TAX_ID.$F))										failProcess($JOB_ID."022",'Unable to rename annotation for TAX_ID '.$TAX_ID.' at '.$PATH);
			
			}

			// If we failed to download all files, we try to use another prefix
			if ($FAILED==4)
			{
				$PATH=$GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY'].$ORG_GROUP.'/'.
						str_replace(" ","_",$ORG_NAME).
						'/all_assembly_versions/'.
						$BUILD_PATH.
						'/RefSeq_transcripts_alignments/'.
						$INFO['Assembly_Acc'];
				
				$FAILED=0;
				foreach ($FILES as $F)
				{
					if (!dl_file($PATH.$F,3))									
					{
						$FAILED++;
						continue;
					}	
					if (!rename($INFO['Assembly_Acc'].$F,$TAX_ID.$F))						failProcess($JOB_ID."023",'Unable to rename annotation for TAX_ID '.$TAX_ID.' at '.$PATH);
				}
			}
			
			// Either all failed, which means no alignments available and that ok
			///Or we have all the alignment files, which is ok too.
			///But if we have some failed and some not, it means there is a problem 
			if ($FAILED!=0 && $FAILED!=4)													failProcess($JOB_ID."024",'Unable to get alignments for TAX_ID '.$TAX_ID.' at '.$PATH);

			// If none failed, we process the bam files
			if ($FAILED==0)
			{
				addLog("\tProcessing BAM files");
				$res=array();
				/// Here we generate the header and body files for the alignments using same tools
				exec($GLB_VAR['TOOL']['SAMTOOLS'].' view -h '.$TAX_ID.'_knownrefseq_alns.bam | egrep "@" > '.$TAX_ID."_knownrefseq_alns.header",$res,$return_code);
				if ($return_code!=0)	failProcess($JOB_ID."025",'Unable to generate header file for knownrefseq '.$TAX_ID.' at '.$PATH);
				
				exec($GLB_VAR['TOOL']['SAMTOOLS'].' view  '.$TAX_ID.'_knownrefseq_alns.bam  > '.$TAX_ID."_knownrefseq_alns.body",$res,$return_code);
				if ($return_code!=0)	failProcess($JOB_ID."026",'Unable to generate header file for knownrefseq '.$TAX_ID.' at '.$PATH);
				
				exec($GLB_VAR['TOOL']['SAMTOOLS'].' view -h '.$TAX_ID.'_modelrefseq_alns.bam | egrep "@" > '.$TAX_ID."_modelrefseq_alns.header",$res,$return_code);
				if ($return_code!=0)	failProcess($JOB_ID."027",'Unable to generate header file for _modelrefseq_alns '.$TAX_ID.' at '.$PATH);
				
				exec($GLB_VAR['TOOL']['SAMTOOLS'].' view  '.$TAX_ID.'_modelrefseq_alns.bam  > '.$TAX_ID."_modelrefseq_alns.body",$res,$return_code);
				if ($return_code!=0)	failProcess($JOB_ID."028",'Unable to generate header file for _modelrefseq_alns '.$TAX_ID.' at '.$PATH);
				
			}
			
			addLog("\tDownload successfully completed");
			
			// We update the status file
			$fp=fopen('status.txt','w');if (!$fp)											failProcess($JOB_ID."029",'Unable to open status file TAX_ID '.$TAX_ID.' at '.$PATH);
			fputs($fp,"downloaded\n");
			fclose($fp);

			// We go back to the working directory
			if (!chdir($W_DIR))															failProcess($JOB_ID."030",'Unable to access process dir '.$W_DIR);
		}
	}

successProcess();

?>

