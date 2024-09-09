<?php
ini_set('memory_limit','3000M');
/**
 SCRIPT NAME: pp_refseq
 PURPOSE:     Prepare a pointer file for each RefSeq assembly to speed up the process of extracting sequences and alignments
 
*/
$JOB_NAME='pp_refseq';

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


addLog("Set up directory");
	/// Get Parent job information
	$CK_GENOME_INFO=$GLB_TREE[getJobIDByName('ck_refseq_rel')];
	
	/// We define the working directory based on ck_Refseq_rel directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_GENOME_INFO['TIME']['DEV_DIR'].'/';	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);
	/// Setting up the process control directory to the current release so that the next job can pick it up
	$PROCESS_CONTROL['DIR']=$CK_GENOME_INFO['TIME']['DEV_DIR'];
					   					   
	
addLog("Working directory: ".$W_DIR);

	/// Check if weblink exist
	if (!isset($GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY']))							failProcess($JOB_ID."003",'FTP_REFSEQ_ASSEMBLY path no set');
	if (!isset($GLB_VAR['SCRIPT_DIR']))												failProcess($JOB_ID."004",'SCRIPT_DIR NOT SET');
	

	// If no GENOME defined, we can stop here
	/// This should never be triggered automatically
	if (!isset($GLB_VAR['GENOME']))														failProcess($JOB_ID."005",'GENOME NOT SET');
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

		// A build is uniquely identified by its Gene_build and Assembly_name and tax id
		$BUILD_NAME=$INFO['Assembly_Acc'].'_'.$INFO['Assembly_name'];
		// Internal directory name
		$BUILD_DIR=$TAX_ID.'__'.$INFO['Assembly_Acc'].'__'.$INFO['Assembly_name'];
		addLog("####Processing build ".$BUILD_NAME);

		// We get the organism name and group
		$ORG_NAME=$INFO['organism_name'];
		$ORG_GROUP=$INFO['group'];

		// We check if the directory is already created
		if (!is_dir($W_DIR.'/'.$BUILD_DIR))												failProcess($JOB_ID."006",'Unable to find build directory for TAX_ID:'.$TAX_ID.' at '.$BUILD_DIR.' in '.getcwd());
		if (!chdir($W_DIR.'/'.$BUILD_DIR))												failProcess($JOB_ID."007",'Unable to access build directory for TAX_ID:'.$TAX_ID.' at '.$BUILD_DIR);
		
		
		// We check the status file, ensuring it is new 
		if (!checkFileExist('status.txt'))												failProcess($JOB_ID."009",'Unable to find status.txt');
		$status=explode("\n",file_get_contents('status.txt'));
		addLog("\tCurrent status: ".$status[0]);
		// If the status is not new, we can skip downloading this build
		if ($status[0]=='new')															failProcess($JOB_ID."009",'Found new status for TAX_ID:'.$TAX_ID.' at '.$BUILD_DIR);

		$POINTERS=array();


		if (!checkFileExist($TAX_ID.'_rna.fa'))											failProcess($JOB_ID."010",'Unable to find '.$TAX_ID.'_rna.fa');

		addLog("\tProcess RNA file");
		processRNAFile($TAX_ID,$POINTERS);
		


		addLog("\tProcess alignment files");;
		processAlignmentFile($TAX_ID,$POINTERS);

	
		addLog("\tProcess model alignment files");;
		processModelFile($TAX_ID,$POINTERS);

		addLog("\tProcess gene.gff file");;
		$MIR_BASE=array();
		processGeneFile($TAX_ID,$POINTERS,$MIR_BASE);
		
		
		

		addLog("\tRemoving miRNA parent mRNA sequences (Usually NR_XXXXX)");
		cleanMiRNA($POINTERS,$MIR_BASE);
	
		addLog("\tSaving pointers");
		$fp=fopen($TAX_ID.'_pointers.csv','w');if (!$fp)										failProcess($JOB_ID."011",'Unable to open '.$TAX_ID.'_pointers.csv');
		foreach ($POINTERS as $NAME=>$INFO)
		fputs($fp,$NAME."\t".json_encode($INFO)."\n");
		fclose($fp);

	}
}
successProcess();








function processRNAFile($TAX_ID,&$POINTERS)
{

	$fp=fopen($TAX_ID.'_rna.fa','r');
	if (!$fp) 																		failProcess($JOB_ID."A01",'Unable to open '.$TAX_ID.'_rna.fa');
	while(!feof($fp))
	{
		/// File position for each RNA sequence PRIOR to reading the line, so we are at the beginning of the line
		$fpos=ftell($fp);
		$line=stream_get_line($fp,1000,"\n");
		/// We skip the line if it is not a header
		if (substr($line,0,1)!='>')continue;
		/// We get the name of the RNA sequence
		$pos=strpos($line,' ');
		$name=substr($line,1,$pos-1);
		// Defines file position for each RNA sequence
		$POINTERS[$name][0]=$fpos;
	}
	fclose($fp);
}



function processAlignmentFile($TAX_ID,&$POINTERS)
{
	/// We don't necessarily have the alignment files
	if (!checkFileExist($TAX_ID.'_knownrefseq_alns.body'))		return;
	
	$fp=fopen($TAX_ID.'_knownrefseq_alns.body','r');
	if (!$fp)																	failProcess($JOB_ID."B01",'Unable to open '.$TAX_ID.'_knownrefseq_alns.body');
		
	while(!feof($fp))
	{
		/// File position for each alignment sequence PRIOR to reading the line, so we are at the beginning of the line
		$fpos=ftell($fp);
		$line=stream_get_line($fp,10000000000,"\n");
		if ($line=='')continue;
		$tab=explode("\t",$line);
		$name=$tab[0];
		/// IF there is an alignment available, provides the position in the file
		// K is for knownRefSeq
		if (!isset($POINTERS[$name][1]))$POINTERS[$name][1]=$tab[2].'|'.$fpos.'|K';
		else $POINTERS[$name][1].="\t".$tab[2].'|'.$fpos.'|K';
		
	}

	fclose($fp);
	
}


function processModelFile($TAX_ID,&$POINTERS)
{
	/// We don't necessarily have the alignment files
	if (!checkFileExist($TAX_ID.'_modelrefseq_alns.body'))		return;

	$fp=fopen($TAX_ID.'_modelrefseq_alns.body','r');
	if (!$fp)																failProcess($JOB_ID."C01",'Unable to open '.$TAX_ID.'_modelrefseq_alns.body');
			
	while(!feof($fp))
	{
		/// File position for each alignment sequence PRIOR to reading the line, so we are at the beginning of the line
		$fpos=ftell($fp);
		$line=stream_get_line($fp,10000000,"\n");
		if ($line=='')continue;
		
		/// We get the name:
		$tab=explode("\t",$line);
		$name=$tab[0];
		/// IF there is an alignment available, provides the position in the file
		/// M is for model
		if (!isset($POINTERS[$name][1]))$POINTERS[$name][1]=$tab[2].'|'.$fpos.'|M';
		else $POINTERS[$name][1].="\t".$tab[2].'|'.$fpos.'|M';
		
	}

	fclose($fp);
		
}


function processGeneFile($TAX_ID,&$POINTERS,&$MIR_BASE)
{
	$fp=fopen($TAX_ID.'_gene.gff','r');
	if (!$fp)																	failProcess($JOB_ID."D01",'Unable to open '.$TAX_ID.'_gene.gff');

	while(!feof($fp))
	{
		$line=stream_get_line($fp,100000,"\n");
		if ($line==''||substr($line,0,1)=='#')continue;
		$entry=explode("\t",$line);
		/// We convert the last column into an array
		$entry[8]=convertGFFLine($entry[8]);
		
		/// We check if the entry is partial
		$IS_PARTIAL=false;
		if (isset($entry[8]['partial']) && $entry[8]['partial']==true)$IS_PARTIAL=true;


		/// We check if the entry has a substitution or frameshift
		if (isset($entry[8]['Note']) && 
			(strpos($entry[8]['Note'],'substitution')!==false||
			 strpos($entry[8]['Note'],'frameshift')!==false))$IS_PARTIAL=true;

		/// miRNA are redundantly annotated in the gff file
		/// We do additional processing if the entry is a miRNA
		if ($entry[2]=='miRNA' || $entry[2]=='primary_transcript'||$entry[2]=='precursor_RNA')
		{
			$ID=$entry[8]['ID'];
			$PARENT=$entry[8]['Parent'];
			$pos=strpos($ID,'-'); $ID=substr($ID,$pos+1);
			$pos=strpos($PARENT,'-'); $PARENT=substr($PARENT,$pos+1);
			
			/// We have to check if the miRNA is the parent or the child
			if (strtolower(substr($ID,0,3))=='mir')	{$MIR_NAME=$ID;		$MIR_PARENT=$PARENT;}
			else 									{$MIR_NAME=$PARENT;	$MIR_PARENT=$ID;	}

			$MIR_BASE[$MIR_NAME]=$MIR_PARENT;

		}
		/// We do additional processing if we have CDS or exon information
		if ($entry[2]!='CDS'&&$entry[2]!='exon')continue;
		
		
		$tab=explode("-",$entry[8]['Parent']);
		if ($tab[0]!='rna')continue;
		
		if ($entry[2]=='exon')
		{
			$tabE=explode("-",$entry[8]['ID']);
			$entry[2]=$tabE[count($tabE)-1];
		}
		
		unset($tab[0]);
		$NAME=implode("-",$tab);
	
		if (!isset($POINTERS[$NAME][2]))$POINTERS[$NAME][2]=      $entry[0].'|'.$entry[2].'|'.$entry[3].'|'.$entry[4];
		else 							$POINTERS[$NAME][2].="\t".$entry[0].'|'.$entry[2].'|'.$entry[3].'|'.$entry[4];
		if ($IS_PARTIAL)$POINTERS[$NAME][5]='PARTIAL';
	}
	fclose($fp);
	
}




function cleanMiRNA(&$POINTERS,&$MIR_BASE)
{
	$REMOVE_POINTER=array();
	/// We process the miRNA information to remove
	foreach ($MIR_BASE as $MIR=>$TR)
	{
		// echo "MIR INFO:".$MIR."\t".$TR."\n";
		if (!isset($POINTERS[$TR]))continue;
		
		foreach ($POINTERS[$TR] as $K=>$V)
		{
			if (isset($POINTERS[$MIR][$K]))$POINTERS[$MIR][$K].="\t".$V;
			else $POINTERS[$MIR][$K]=$V;
		}
		$POINTERS[$MIR][4]=$TR;
		$REMOVE_POINTER[$TR]=true;
	}
	
	/// Removing the miRNA parent mRNA sequences
	foreach ($REMOVE_POINTER as $TR=>$DUMMY)
	{
		//print_r($POINTERS[$MIR]);
		unset($POINTERS[$TR]);
	}

	addLog("\tProcessing secondary transcripts");
	foreach ($POINTERS as $NAME=>&$INFO)
	{
		/// Checking transcripts with secondary sequence -2, -3 ...
		if (isset($INFO[1]))continue;
		if (strpos($NAME,'-')===false)continue;
		$pos=strpos($NAME,'-');
		$NEW_NAME=substr($NAME,0,$pos);

		//echo $NAME."\t".isset($POINTERS[$NEW_NAME])."\n";
		if (!isset($POINTERS[$NEW_NAME]))continue;
		if (!isset($POINTERS[$NEW_NAME][1]))continue;
	//	print_r($INFO);

		$PARENT_1=explode("\t",$POINTERS[$NEW_NAME][1]);
		
		$CHR_SEQ=substr($INFO[2],0,strpos($INFO[2],'|'));
		$CHILD_T=array();
		$PAR_T=array();
		foreach ($PARENT_1 as $T)
		{
			if (strpos($T,$CHR_SEQ)!==false)$CHILD_T[]= $T;
			else $PAR_T[]=$T;
		}
		$INFO[0]=$POINTERS[$NEW_NAME][0];
		$INFO[1]=implode("\t",$CHILD_T);
		$INFO[4]=$NEW_NAME;
		$PARENT_ENTRY=&$POINTERS[$NEW_NAME];
		if (isset($PARENT_ENTRY[4]))$INFO[4]=$PARENT_ENTRY[4];
		$POINTERS[$NEW_NAME][1]=implode("\t",$PAR_T);
		// print_r($POINTERS[$NEW_NAME]);
		// print_r($POINTERS[$NAME]);
		// echo "###########\n";
	}
}
?>
