<?php
/**
 SCRIPT NAME: ck_refseq_Rel
 PURPOSE:     Check for new release of RefSeq genomes
 
*/
$JOB_NAME='ck_refseq_rel';

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


addLog("Download release note");
	/// Setting up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	
	/// Checking if the FTP link is set
	if (!isset($GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY']))								failProcess($JOB_ID."004",'FTP_REFSEQ_ASSEMBLY path no set');
	
	// If no GENOME defined, we can stop here
	if (!isset($GLB_VAR['GENOME']))														failProcess($JOB_ID."005",'GENOME NOT SET');
	if ($GLB_VAR['GENOME']==array())successProcess("VALID");

	// $TAXON_INFO is a reference to the global GENOME variable
	$TAXON_INFO=&$GLB_VAR['GENOME'];

	// We check if there is at least one genome from RefSeq
	$PROCESS_NEEDED=false;
	foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
	foreach ($LIST as &$INFO)
	{
		if ($INFO['Source']=='REFSEQ')$PROCESS_NEEDED=true;
	}
	/// If no genome is from RefSeq, we can stop here
	if (!$PROCESS_NEEDED)successProcess("VALID");


	// We check if the directory is already created
	if (!is_dir("TAXON") && !mkdir("TAXON"))											failProcess($JOB_ID."006",'Unable to create TAXON directory ');
	if (!chdir("TAXON"))																failProcess($JOB_ID."007",'Unable to get in TAXON directory ');

	// We download the assembly_summary file containing all the information about the genomes
	if (!dl_file($GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY'].'/assembly_summary_refseq.txt'))failProcess($JOB_ID."008",'Unable to download assembly_summary ');



	/// Here we first aim at downloading the assembly_summary file for each taxon to see if a new version has been released
	$HAS_CHANGE=false;
	
	$WORKDIR=getcwd().'/';

	/// So we loop over each taxon
	foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
	{
		if (processTaxon($TAX_ID,$LIST,$WORKDIR)) $HAS_CHANGE=true;
	}

	if (!$HAS_CHANGE)successProcess('VALID');


addLog("Create directory");


	/// Now that all taxons have in their respective TAXON/TAX_ID directory their latest version
	/// We can create our directory with symlinks to TAXON/TAX_ID/LATEST_VERSION
	$PROCESS_CONTROL['DIR']='N/A';

	/// Setting up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."009",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."010",'Unable to find and create '.$W_DIR);
	$T_DIR=$W_DIR.'/'.getCurrDate();		if (!is_dir($T_DIR) && !mkdir($T_DIR)) 	failProcess($JOB_ID."011",'Unable to create new process dir '.$T_DIR);
	
	addLog("Workding directory: ".$W_DIR."\nTaxon directory:".$T_DIR);
	
	/// Now we create the symlinks
	foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
	{
		foreach ($LIST as &$INFO)	
		{
			if ($INFO['Source']!='REFSEQ')continue;
			
			// A build is uniquely identified by its Assembly_Acc and Assembly_name and tax id
			$BUILD_DIR=$W_DIR.'/TAXON/'.$TAX_ID.'/'.$INFO['Assembly_Acc'].'_'.$INFO['Assembly_name'].'/'.$INFO['DATE'];
			
			// We use __ as a separator because it is not allowed in a taxon name
			$DIR_NAME=$TAX_ID.'__'.$INFO['Assembly_Acc'].'__'.$INFO['Assembly_name'];
			if (is_link($T_DIR.'/'.$DIR_NAME))
			{
				/// If the symlink already exists, we check if it is the same as the one we want to create
				if (readlink($T_DIR.'/'.$DIR_NAME)!=$BUILD_DIR)
				{
					/// If it is not the same, we remove the previous symlink and create a new one
					if (!unlink($T_DIR.'/'.$DIR_NAME))								failProcess($JOB_ID."012",'Unable to remove previous symlink for taxon build '.$DIR_NAME);
					// We create the new symlink
					if (!symlink($BUILD_DIR,$T_DIR.'/'.$DIR_NAME)) 					failProcess($JOB_ID."013",'Unable to create symlink for taxon build '.$DIR_NAME);	
				}
			}
			/// If the symlink does not exist, we create it
			else if (!symlink($BUILD_DIR,$T_DIR.'/'.$DIR_NAME)) 					failProcess($JOB_ID."014",'Unable to create symlink for taxon build '.$DIR_NAME);
			
		}
	}


	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();












/// This function will check if the date format is valid
function checkDateFormatValidUsingRegex($DATE)
{
	$regex = '/^(\d{4})-(\d{2})-(\d{2})$/';
	if(!preg_match($regex, $DATE, $matches)) return false;
	if(!checkdate($matches[2], $matches[3], $matches[1])) return false;
	return true;

}


/// This function will check for a given organism if a new version of the assembly is available
function processTaxon(&$TAX_ID,&$LIST,&$WORKDIR)
{
	global $GLB_VAR;
	global $JOB_ID;

	// We check if there is at least one genome from RefSeq
	$HAS_REFSEQ=false;
	// We also get the organism name and group
	$ORG_NAME='';
	$ORG_GROUP='';
	foreach ($LIST as &$INFO)	
	{
		if ($INFO['Source']!='REFSEQ')continue;
		$HAS_REFSEQ=true;
		$INFO['STATUS']='VALID';
		$ORG_NAME=$INFO['organism_name'];
		$ORG_GROUP=$INFO['group'];
	}
	/// If no genome is from RefSeq, we can stop here
	if (!$HAS_REFSEQ)return false;
	/// We create the directory for the taxon
	if (!is_dir($WORKDIR.$TAX_ID) && !mkdir($WORKDIR.$TAX_ID))								failProcess($JOB_ID."A01",'Unable to create directory TAX_ID'.$TAX_ID);
	/// We get in the directory
	if (!chdir($WORKDIR.$TAX_ID))															failProcess($JOB_ID."A02",'Unable to chdir directory TAX_ID'.$TAX_ID);

	addLog("Download Assembly version for ".$TAX_ID.' '.$ORG_NAME);
	/// We download the list of all assemblies available for this taxon
	$PATH=$GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY'].$ORG_GROUP.'/'.str_replace(" ","_",$ORG_NAME).'/all_assembly_versions/';
	
	echo "Path:\t".$PATH."\n";

	$HAS_CHANGE=false;

	foreach ($LIST as &$INFO)
	{
		// We only want the RefSeq genomes
		if ($INFO['Source']!='REFSEQ')continue;

		// A build is uniquely identified by its Gene_build and Assembly_name and tax id
		$BUILD_NAME=$INFO['Gene_build'].'_'.$INFO['Assembly_name'];
		$BUILD_PATH=$PATH.$BUILD_NAME;

		// We download the list of all files available for this build
		if (!dl_file($BUILD_PATH,3,'file_list.html'))								failProcess($JOB_ID."A03",'Unable to download file_list.html '.$BUILD_PATH);
		
		$fp=fopen('file_list.html','r'); if (!$fp)									failProcess($JOB_ID."A04",'Unable to open file_list.html');
		
		// We get the date of the latest version
		$DATE='';
		
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			if ($line[0]=='#')continue;
			if (strpos($line,'_genomic.gff.gz')===false)continue;
			$tab=array_values(array_filter(explode(" ",$line)));
			$DATE=$tab[2];
			break;
		}
		fclose($fp);
		// We remove the file_list.html
		if (!unlink('file_list.html'))											failProcess($JOB_ID."A05",'Unable to delete file_list.html');
		if ($DATE=='')															failProcess($JOB_ID."A06",'Unable to find version for '.$BUILD_PATH);
		if (!checkDateFormatValidUsingRegex($DATE))								failProcess($JOB_ID."A07",'Unexpected date format for '.$BUILD_PATH.': '.$DATE);
		

		/// However we are going to use the pair Assembly Accession and Name to uniquely identify a build.
		/// This is for better consistency between RefSeq and Ensembl
		$BUILD_NAME=$INFO['Assembly_Acc'].'_'.$INFO['Assembly_name'];
		
		// We check if the date is the same as the current release date
		$CURR_RELEASE=getCurrentReleaseDate('REFSEQ_'.$TAX_ID.'_'.$BUILD_NAME,$JOB_ID);
		$INFO['DATE']=$DATE;
		
		/// check date format
		if ($DATE==$CURR_RELEASE)
		{
			
			$INFO['STATUS']='VALID';
			continue;
		}
		if (!is_dir($BUILD_NAME) && !mkdir($BUILD_NAME))								failProcess($JOB_ID."A08",'Unable to create directory '.$BUILD_NAME);
		if (!chdir($BUILD_NAME))														failProcess($JOB_ID."A09",'Unable to chdir directory '.$BUILD_NAME);
		if (!is_dir($DATE) && !mkdir($DATE))											failProcess($JOB_ID."A10",'Unable to create directory '.$DATE);
		if (!chdir($DATE))																failProcess($JOB_ID."A11",'Unable to chdir directory '.$DATE);

		// We update the release date
		updateReleaseDate($JOB_ID,'REFSEQ_'.$TAX_ID.'_'.$BUILD_NAME,$DATE);
		$INFO['STATUS']='NEW';
		$fp=fopen('status.txt','w');if (!$fp)											failProcess($JOB_ID."A12",'Unable to open status file for '.$TAX_ID);
		fputs($fp,'new'."\n");
		fclose($fp);
		if (!chdir($WORKDIR.$TAX_ID))													failProcess($JOB_ID."A13",'Unable to chdir directory TAX_ID'.$TAX_ID);
		
		// This will trigger the next steps
		$HAS_CHANGE=true;


	}
	return $HAS_CHANGE;
}
?>
