<?php
/*
 SCRIPT NAME: pmj_dbsnp
 PURPOSE:     Prepare script for dbnsp processing
 
*/
ini_set('memory_limit','2000M');


/// Job name - Do not change
$JOB_NAME='pmj_dbsnp';

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

	/// Get parent information:
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_dbsnp_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/DBSNP/';   	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if ( !chdir($W_DIR))				 	failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
	/// Check SCRIPT_DIR
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."004",'SCRIPT_DIR not set ');
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."005",'SCRIPT_DIR not found ');
	
	/// Check setenv.sh
	$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."006",'Setenv file not found ');

	/// Check the script to run
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_dbsnp.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."007",$RUNSCRIPT.' file not found');

	/// Check JOBARRAY that allow to run multiple jobs
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."008",'JOBARRAY NOT FOUND ');
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."009",'JOBARRAY file NOT FOUND '.$JOBARRAY);


	addLog("Working directory: ".$W_DIR);

	addLog("Removing withdrawn variants");
	removeWithdrawn();
	

	$SOURCE_ID=getSource("dbSNP");



	processStudies();


	checkImpacts();

	createMapping();

	$TOT=0;
	$VARIANTS=getVariantCounts($TOT);


	$N_JOB=50;
	$N_PER_JOB=ceil($TOT/$N_JOB);





	/// Create the jobs directory
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))										failProcess($JOB_ID."010",'Unable to create jobs directory');
	if (!is_dir("DATA") && !mkdir("DATA"))												failProcess($JOB_ID."011",'Unable to create jobs directory');

	/// Open the job array file
	$fpA=fopen("SCRIPTS/all.sh",'w'); if(!$fpA)													failProcess($JOB_ID."012",'Unable to open all.sh');


	/// Now for each job, we create a job file
	/// A job will process a set of variants that can come from one or more chromosomes based on $N_PER_JOB
	for ($I=0;$I<$N_JOB;++$I)
	{
		$STR='';
		$JOB_CO=$N_PER_JOB;
		foreach ($VARIANTS as $CHR=>&$CO)
		{
			if ($CO[0]==0)continue;
			$STR.=$CHR.'-';
			if ($CO[0]>$JOB_CO)
			{
				$STR.=$CO[1]."-".($CO[1]+$JOB_CO).'_';
				$CO[0]-=$JOB_CO;
				$CO[1]+=$JOB_CO+1;
				break;
			}
			else 
			{
				$STR.=$CO[1].'-'.$CO[2].'_';
				$CO[0]=0;
				$JOB_CO-=$CO[2]-$CO[1];
			}
		}
		echo $I."\t".$STR."\n";
		
	
		$JOB_NAME="SCRIPTS/job_".$I.".sh";
		$fp=fopen($JOB_NAME,"w");if(!$fp)												failProcess($JOB_ID."013",'Unable to open jobs/job_'.$I.'.sh');
		
		fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");
		
		fputs($fp,'#!/bin/sh'."\n");
		fputs($fp,'cd '.$W_DIR."\n");
		fputs($fp,"source ".$SETENV."\n");
		fputs($fp,'biorels_php '.$RUNSCRIPT.' '.$I.' '.$STR.' &> SCRIPTS/LOG_'.$I."\n");
		fputs($fp,'echo $? > SCRIPTS/status_'.$I."\n");
		fclose($fp);
	}
	fclose($fpA);

successProcess();




function removeWithdrawn()
{
	global $JOB_ID;
	$fp=fopen('refsnp-withdrawn.json','r'); if (!$fp)	failProcess($JOB_ID."A01",'Unable to open refsnp-withdrawn.json');
	$LIST_DEL=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000000000,"\n");
		$data=json_decode($line,true);
		/// We only need the rsid
		if ($data['refsnp_id']=='')	continue;
		$LIST_DEL[$data['refsnp_id']]='';
	}
	fclose($fp);

	echo "List of potential records to delete: ".count($LIST_DEL)."\n";
	
	$chunks=array_chunk(array_keys($LIST_DEL),10000);
	
	$NCS=count($chunks);
	
	foreach ($chunks as $nc=>$chnk)
	{
		$res=runQuery("SELECT variant_entry_id,rsid FROM variant_entry where rsid IN (".implode(',',$chnk).')');
		
		if ($res===false)	failProcess($JOB_ID."A02",'Unable to get the list of variant entries to delete');
		
		echo "Chunk:\t".$nc.'/'.$NCS."\t".count($res)."/10000 to delete\n";
		
		foreach ($res as $line)
		{
			if (runQueryNoRes('DELETE FROM variant_entry where variant_entry_id = '.$line['variant_entry_id']))continue;
			failProcess($JOB_ID."A03",'Unable to delete variant entry '.$line['variant_entry_id']);
		}

	}
}





function processStudies()
{
	global $JOB_ID,$SOURCE_ID;
	if (!is_file('frequency_studies.json'))															failProcess($JOB_ID."B01",'Unable to find frequency studies file');

	/// Read the file, decode the json string and convert it into an array
	$STUDIES=json_decode(file_get_contents('frequency_studies.json'),true);
	
	/// Json parsing fail => stop
	if ($STUDIES===false)																			failProcess($JOB_ID."B02",'Unable to decode frequency studies file');

	/// Get all studies from the database for dbSNP
	$res=runQuery("SELECT * FROM variant_freq_study where source_id = ".$SOURCE_ID); 
	if ($res===false)																				failProcess($JOB_ID."B03",'Unable to get frequency studies');
	$DB_STUDIES=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DB_STUDIES[$line['variant_freq_study_name']]=$line;
		
	}

	$MAX_DBSTUDY=-1;
	$res=runQuery("SELECT MAX(variant_freq_study_id) CO FROM variant_Freq_study");
	if($res===false)																					failProcess($JOB_ID."B04",'Unable to get max frequency studies');
	$MAX_DBSTUDY=$res[0]['co'];


	/// For each study in the json file, we check if it is in the database
	foreach ($STUDIES as $STUDY_NAME=>$STUDY)
	{
		/// If the study is in the database, we update it
		if (isset($DB_STUDIES[$STUDY_NAME]))
		{
			$DB_STUDY=&$DB_STUDIES[$STUDY_NAME];
			
			$DB_STUDY['DB_STATUS']='VALID';

			/// Check if the study has changed name or description
			$query="UPDATE variant_freq_study SET ";$UPD=false;
			if ($STUDY['short_name']!=$DB_STUDY['short_name'])
			{
				$UPD=true;
				$query.=" short_name='".$STUDY['short_name']."',";
			}
			if ($STUDY['description']!=$DB_STUDY['description'])
			{
				$UPD=true;
				$query.=" description='".$STUDY['description']."',";
			}
			if ($UPD)
			{
				$query=substr($query,0,-1).' WHERE variant_freq_study_id = '.$DB_STUDIES[$STUDY_NAME]['variant_freq_study_id'];
				if (!runQueryNoRes($query))															failProcess($JOB_ID."B05",'Unable to update variant frequency study');
			}
		}
		else 
		{
			++$MAX_DBSTUDY;
			$query='INSERT INTO variant_freq_study (variant_freq_study_id , variant_freq_study_name , description , short_name,source_Id ) VALUES ('.
			$MAX_DBSTUDY.",
			'".str_replace("'","''",$STUDY_NAME)."',
			'".str_replace("'","''",$STUDY['description'])."',
			'".str_replace("'","''",$STUDY['short_name'])."',
			".$SOURCE_ID.")";
			if (!runQueryNoRes($query))															failProcess($JOB_ID."B06",'Unable to insert variant frequency study');

		}
	}

}


function checkImpacts()
{
	global $JOB_ID;
	/// Get the current variant types:
	$res=runQuery("SELECT variant_type_id, variant_name,so_id 
				FROM variant_type v, so_entry s 
				where s.so_entry_Id = v.so_entry_id");
	if ($res===false)																					failProcess($JOB_ID."C01",'Unable to find variant types');
	
	/// Define the list of variant types.
	/// Should probably go in STATIC_DATA
	$LIST_VAR=array('delins'=>'SO:1000032',
	'del'          => 'SO:0000159',
	'ins'          => 'SO:0000667',
	'mnv'          => 'SO:0002007',
	'ref'          => 'SO:0002073',
	'snv'          => 'SO:0001483');
	$MAX_ID=0;
	foreach ($res as $line)
	{
		unset($LIST_VAR[$line['variant_name']]);
		$MAX_ID=max($MAX_ID,$line['variant_type_id']);
	}

	/// Create missing ones:
	foreach ($LIST_VAR as $K=>$V)
	{
		$MAX_ID++;
		$q='INSERT INTO variant_type (variant_type_id,variant_name,so_entry_id) VALUES ('.$MAX_ID.",'".$K."',(SELECT so_entry_id FROM so_entry where so_id = '".$V."'))";
		$res=runQueryNoRes($q);
		if ($res===false)																				failProcess($JOB_ID."C02",'Unable to insert variant type');
	}
}



function createMapping()
{
	global $JOB_ID;

	/// Get the list of genes and their isoforms
	/// As provided by Uniprot
	$res=runQuery("SELECT gene_id, iso_id, prot_extdb_value 
	FROM gn_entry ge, gn_prot_map gpm, prot_seq ps, prot_extdb_map pem, prot_extdb pe
	WHERE ps.prot_seq_id = pem.prot_seq_id 
	AND pem.prot_extdb_id = pe.prot_extdbid 
	AND prot_extdbabbr IN ('Ensembl','RefSeq') 
	AND ge.gn_entry_id = gpm.gn_entry_id 
	AND gpm.prot_entry_id = ps.prot_entry_id 
	ORDER BY gene_id ASC");
	if ($res===false)																				failProcess($JOB_ID."D01",'Unable to get uniprot mapping');
	$fp=fopen('uniprot_mapping.csv','w');if (!$fp)													failProcess($JOB_ID."D02",'Unable to open uniprot mapping file');
	fputs($fp,"GENE_ID\tISO_ID\tTRANSCRIPT_INFO\n");
	foreach ($res as $line)
	{
		$pos=strpos($line['prot_extdb_value'],' ');
		/// Get the entry name:
		$entry='';
		
		if ($pos===false)$entry=trim($line['prot_extdb_value']);
		else $entry = substr($line['prot_extdb_value'],0,$pos-1);
		
		if (checkRegex($entry,'TRANSCRIPT')===false)continue;
		fputs($fp,$line['gene_id']."\t".$line['iso_id']."\t".$entry."\n");
		
	}
	fclose($fp);
}



function getVariantCounts(&$TOT)
{

	/// Release notes contains the number of variants per chromosome/file
	global $JOB_ID;
	$fp=fopen('release_notes.txt','r');if (!$fp)													failProcess($JOB_ID."D01",'Unable to open release notes');
	$VARIANTS=array();
	$TOT=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100,"\n");
		if (substr($line,0,3)!='chr')continue;
		/// We have a line with the number of variants
		$tab=explode("\t",substr($line,3));
		if ($tab[0]=='M')$tab[0]='MT';
		
		/// We save the number of variants
		$TOT+=$tab[1];

		/// We save the number of variants per chromosome
		/// This will be used for the job creation
		$VARIANTS[$tab[0]]=array($tab[1],0,$tab[1]);;
	}
	fclose($fp);
	return $VARIANTS;
}
?>
