<?php
error_reporting(E_ALL);

/**
 SCRIPT NAME: db_gtex
 PURPOSE:     Process gtex rna expression files
 
*/

/// Job name - Do not change
$JOB_NAME='db_gtex';


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




addLog("Check directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_gtex')];
	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR))																			failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if ( !chdir($W_DIR))																			failProcess($JOB_ID."002",'Unable to access '.$W_DIR);

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Get Static data");
	
	/// Get the static directory
	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/GENE_EXPR/';	
	if (!is_dir($STATIC_DIR))																		failProcess($JOB_ID."003",'GENE_EXPR static dir not found '.$STATIC_DIR);
	
	/// Get the source_id for GTEX
	$RNA_SOURCE_ID=getSource('GTEX');


	/// This is to keep track of the changes:
	$STAT=array(
		'ENSG_NOT_FOUND'=>0,
		'ENSG_FOUND'=>0,
		'NEW_GENE_TPM'=>0,
		'TRANSCRIPT_NOT_FOUND'=>0,
		'NEW_TRANSCRIPT_TPM'=>0,
		'TRANSCRIPT_DEL'=>0,
		'TRANSCRIPT_FOUND'=>0,
		'TRANSCRIPT_UPD'=>0,
		'TRANSCRIPT_VALID'=>0);


addLog("Preload annotation");

	$DB_TISSUE=array();
	$GTEX_TISSUE=prepareTissues();
	

addLog("Process Samples");

	$SAMPLES=prepareSamples();


 	

addLog("Process Gene Data");
	processGeneData();
// 	exit;
		


addLog("process Transcript");
	processTranscriptData();

	print_r($STAT);
	successProcess();













function  prepareTissues()
{
	global $STATIC_DIR;
	global $DB_TISSUE;
	///First step is to assign GTEX tissues to the anatomy and efo entries
	/// For that we have a STATIC mapping file in GENE_EXPR
	/// Here we have a mapping table specific for RNA/Gene Expression
	/// So in case we don't have the corresponding tissue in anatomy table
	/// We can still load the record

	//// Load existing tissues
	$res=runQuery("SELECT * 
					FROM RNA_TISSUE R 
					LEFT JOIN ANATOMY_ENTRY AE ON AE.ANATOMY_ENTRY_ID = R.ANATOMY_ENTRY_ID 
					LEFT JOIN EFO_ENTRY E ON E.EFO_ENTRY_ID = R.EFO_ENTRY_ID");
	if ($res===false)																			failProcess($JOB_ID."A01",'Unable to get all RNA Tissue ');
	
	/// We are going to store the tissues in a hash map
	$RNA_TISSUE=array();

	// MAXDBID is the maximum value of the RNA_TISSUE_ID so that we can insert new tissues
	$MAXDBID=0;
	foreach ($res as $line)
	{
		$RNA_TISSUE[$line['organ_name']][$line['tissue_name']]=$line;
		$MAXDBID=max($MAXDBID,$line['rna_tissue_id']);
		
	}


	/// Get the manual annotation
	if (!is_file($STATIC_DIR.'/GTEX_MAPPING.csv'))												failProcess($JOB_ID."A02",'Unable to find GTEX annotation file');

	$fp=fopen($STATIC_DIR.'/GTEX_MAPPING.csv','r');if (!$fp)									failProcess($JOB_ID."A03",'Unable to open GTEX annotation file');
	
	$GTEX_TISSUE=array();
	
	/// Get header:
	$line=stream_get_line($fp,1000,"\n");
	$HEAD=array_flip(array_values(array_filter(explode("\t",$line))));

	while(!feof($fp))
	{
		/// Looking at each GTEX tissue
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')continue;
		$tab=array_values(array_filter(explode("\t",$line)));
		
		/// Getting the data we need:
		$organ_name=$tab[$HEAD['DB_ORGAN_NAME']];
		$tissue_name=$tab[$HEAD['DB_TISSUE_NAME']];
		$gtex_tissue=$tab[$HEAD['GTEX_TISSUE']];
		$GTEX_TISSUE[$organ_name][$gtex_tissue]=array($organ_name,$tissue_name);


		/// It's not in the database? then we create it
		if (!isset($RNA_TISSUE[$organ_name][$tissue_name]))
		{
			/// We need to get the anatomy and efo entry id
			$anatomy_entry_id='NULL';
			$efo_entry_id='NULL';
			/// If it is mapped to a UBERON record, we search for it
			if ($tab[$HEAD['ANATOMY_DB']]=='UBERON')
			{
				$res=runQuery("SELECT * 
							FROM anatomy_entry 
							where anatomy_tag ='UBERON_".$tab[$HEAD['ANATOMY_DBID']]."'");
				if (count($res)!=1)																failProcess($JOB_ID."A04",'Unrecognized UBERON IDs');
				$anatomy_entry_id=$res[0]['anatomy_entry_id'];
			}


			/// If it is mapped to a EFO record, we search for it
			if ($tab[$HEAD['ANATOMY_DB']]=='EFO')
			{
				$res=runQuery("SELECT * 
							FROM efo_Entry 
							where efo_tag_id='EFO_".$tab[$HEAD['ANATOMY_DBID']]."'");
				if (count($res)!=1)																failProcess($JOB_ID."A05",'Unrecognized EFO IDs');
				$efo_entry_id=$res[0]['efo_entry_id'];
			}


			++$MAXDBID;
			/// Then insert
			$query='INSERT INTO rna_tissue (rna_tissue_id , anatomy_entry_id , efo_entry_id , organ_name , tissue_name)
			 VALUES ('.
			$MAXDBID.','.
			$anatomy_entry_id.','.
			$efo_entry_id.','.
			"'".$organ_name."','".
			$tissue_name."')";
			if (!runQueryNoRes($query))															failProcess($JOB_ID."A06",'Unable to insert RNA TISSUE entry');
			
			
			$DB_TISSUE[$organ_name][$tissue_name]=$MAXDBID;
			
		}else 
		{
			$DBID=&$RNA_TISSUE[$organ_name][$tissue_name]['rna_tissue_id'];
			$DB_TISSUE[$organ_name][$tissue_name]=$DBID;
			
		}

	}
	fclose($fp);
	return $GTEX_TISSUE;

}







function prepareSamples(&$GTEX_TISSUE)
{
	global $DB_TISSUE;
	/// Here we are going to fetch the samples from the database and compare them against the list of samples
	$SAMPLES=array();
	$res=runQuery("SELECT rna_sample_id, sample_id,T.rna_tissue_id,tissue_name,organ_name 
					FROM rna_sample RGS, rna_tissue T 
					WHERE RGS.rna_tissue_id= t.rna_tissue_id");
	if ($res===false)																				failProcess($JOB_ID."B01",'Unable to get samples');
	$MAX_SAMPLE_ID=-1;
	foreach ($res as $line)
	{
		$SAMPLES[$line['sample_id']]=array(
			'DBID'=>$line['rna_sample_id'],
			'TISSUE_ID'=>$line['rna_tissue_id'],
			'TISSUE'=>$line['tissue_name'],
			'ORGAN'=>$line['organ_name'],
			'STATUS'=>'FROM_DB');
		$MAX_SAMPLE_ID=max($line['rna_sample_id'],$MAX_SAMPLE_ID);
	}

	/// Read samples provided by GTEX
	$fp=fopen('Sample.csv','r');if(!$fp)															failProcess($JOB_ID."B02",'Unable to open Sample file');
	/// We are going to create a new file with the samples that are not in the database
	$fpO=fopen('New_samples.csv','w');if(!$fpO)														failProcess($JOB_ID."B03",'Unable to open new_Sample file');
	
	/// Get header
	$line=stream_get_line($fp,10000000,"\n");
	$HEAD=explode("\t",$line);

	// Flag to see if we have new samples and need to call COPY command
	$HAS_NEW_SAMPLE=false;
	while(!feof($fp))
	{
		$line=stream_Get_line($fp,10000000,"\n");if ($line=='')continue;
		$t=explode("\t",$line);
		
		/// Converting to hash map based on header
		$tab=array_combine($HEAD,$t);

		/// We look if the sample already exist
		if (isset($SAMPLES[$tab['SAMPID']]))
		{
			$SAMPLES[$tab['SAMPID']]['STATUS']='VALID';
			$TISSUE_ID=-1;
			/// And ensuring it is the same as in the database
			if (isset($GTEX_TISSUE[$tab['SMTS']][$tab['SMTSD']]))
			{
				$K=$GTEX_TISSUE[$tab['SMTS']][$tab['SMTSD']];
				//print_r($K);
				if ($DB_TISSUE[$K[0]][$K[1]]==$SAMPLES[$tab['SAMPID']]['TISSUE_ID']){//echo "VALID\n";
				}
				else 
				{
					echo $DB_TISSUE[$K[0]][$K[1]]."\t".
						$SAMPLES[$tab['SAMPID']]['TISSUE_ID'].
						"\tMISMATCH\t".
						$SAMPLES[$tab['SAMPID']]['ORGAN'].':'.
						$SAMPLES[$tab['SAMPID']]['TISSUE']."\t".
						$tab['SMTS'].':'.
						$tab['SMTSD']."\n";
																							failProcess($JOB_ID."B04",'Different organ/tissue for given sample - not currently covered');
				}
			}
			else 
			{
				echo $SAMPLES[$tab['SAMPID']]['ORGAN'].':'.
				$SAMPLES[$tab['SAMPID']]['TISSUE']."\t".
				$tab['SMTS'].':'.
				$tab['SMTSD']."\n";
																							failProcess($JOB_ID."B05",' organ/tissue nt found');
			}
		}
		else
		{
			/// New sample, we associate the tissue/organ to the corresponding database identifier and push it to the file
			$HAS_NEW_SAMPLE=true;
			//print_r($line);
			++$MAX_SAMPLE_ID;
			if (!isset($GTEX_TISSUE[$tab['SMTS']][$tab['SMTSD']])) 									failProcess($JOB_ID."B06", 'Unrecognized tissue/organ:'.$tab['SMTS'].'//'.$tab['SMTSD']);
			$G_T=&$GTEX_TISSUE[$tab['SMTS']][$tab['SMTSD']];
			if (!isset($DB_TISSUE[$G_T[0]][$G_T[1]]))												failProcess($JOB_ID."B07", 'tissue/organ mapping not found'.$tab['SMTS'].'//'.$tab['SMTSD']);
			$RNA_TISSUE_ID=$DB_TISSUE[$G_T[0]][$G_T[1]];
			
			
			fputs($fpO,$MAX_SAMPLE_ID."\t".$tab['SAMPID']."\t".$RNA_TISSUE_ID."\t".$RNA_SOURCE_ID."\n");
			$SAMPLES[$tab['SAMPID']]=array('DBID'=>$MAX_SAMPLE_ID,
				'TISSUE_ID'=>$RNA_TISSUE_ID,
				'TISSUE'=>$tab['SMTS'],
				'ORGAN'=>$tab['SMTSD'],
				'STATUS'=>'TO_INS');
			
		}
	}
	fclose($fp);
	fclose($fpO);
	/// If we processed any new sample , we insert in the database
	if ($HAS_NEW_SAMPLE)
	{
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.rna_sample (rna_sample_id , sample_id , rna_tissue_id , rna_source_id) FROM \''."New_samples.csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
	
		if ($return_code !=0 )																		failProcess($JOB_ID."B08",'Unable to insert rna_sample'); 
	}
	return $SAMPLES;
}


function processGeneData()
{
	global $DB_TISSUE;
	global $RNA_SOURCE_ID;
	global $SAMPLES;
	global $STAT;
	global $JOB_ID;

	
	
	addLog("Static file check");
	/// This is the file we are going to write to
	$FILES=array();
	/// This is the max ID for each table
	$DBIDS=array();
	
	/// Get max PK value for that table to speed up insert
	$res=runQuery('SELECT MAX(rna_gene_ID) CO FROM rna_gene');
	if ($res===false)																			failProcess($JOB_ID."C01",'Unable to get Max ID for '.$TBL);
	$DBIDS['rna_gene']=(count($res)==1)?$res[0]['co']:0;
	
	$FILES['rna_gene']=fopen('rna_gene_insert.csv','w');if (!$FILES[$TBL])						failProcess($JOB_ID."C02",'Unable to open '.$TBL.'_insert.csv');



	$fp=fopen('GeneTPM.csv','r');if(!$fp)														failProcess($JOB_ID."C03",'Unable to open GeneTPM.csv');
	$line=stream_Get_line($fp,10000000,"\n");/// not useful lines
	$line=stream_Get_line($fp,10000000,"\n");
	$line=stream_Get_line($fp,10000000,"\n");
	$HEAD=explode("\t",$line);/// Header line
	
	$NL=0;
	$N_FILE=0;
	$START=false;
	$HAS_DATA=false;
	while(!feof($fp))
	{
		$NL++;
		$line=stream_Get_line($fp,10000000,"\n");if ($line=='')continue;
		/// Each line is a gene with all expression data
		$t=explode("\t",$line);
		
		/// Here we convert the array to a hash map using header as key
		$tab=array_combine($HEAD,$t);
		/// Getting Ensembl Gene ID
		$ENSG=$tab['Name'];
		
		/// Get ensembl identifier without version
		$t=explode(".",$ENSG);
		$ENSG_ID=$t[0];
		
		//  if ($ENSG_ID=='ENSG00000228572'){$START=true;continue;}
		//  if (!$START)continue;

		 if (strpos($t[1],'_')!==false)continue;
		echo $ENSG_ID."\t".$ENSG."\n";


		//////// SEARCH FOR GENE
		$res=runQuery("SELECT * FROM gene_seq WHERE gene_seq_name='".$ENSG_ID."'");
		if ($res===false)																			failProcess($JOB_ID."C04",'Unable to  search for gene sequence');
		/// Can't find the gene? we skip it
		if (count($res)==0){$STAT['ENSG_NOT_FOUND']++;continue;}
		
		$STAT['ENSG_FOUND']++;
		$ENSGDBID=$res[0]['gene_seq_id'];

		/// Get existing data
		$res=array();
		$res=runQuery("SELECT rna_gene_id,rg.rna_sample_id,sample_id,tpm 
				FROM rna_gene RG,rna_sample RS 
				WHERE  RS.rna_sample_id = RG.rna_sample_id 
				AND rna_source_id = ".$RNA_SOURCE_ID." 
				AND gene_seq_id=".$ENSGDBID);
		if ($res===false)																			failProcess($JOB_ID."C05",'Unable to  search for rna gene data');

		$TPMS=array();
		$TISSUE_STAT=array();
		foreach ($res as $line)	
			$TPMS[$line['sample_id']]=array(
					'DBID'=>$line['rna_gene_id'],
					'tpm'=>$line['tpm'],
					'STATUS'=>'FROM_DB');

		/// We remove the Name and Description columns as we 
		unset($tab['Name'],$tab['Description']);

		/// Then looking over the vlaues from the file
		foreach ($tab as $SAMPLE=>$VALUE)
		{
			
			if ($VALUE=="")continue;
			/// Not existing yet? we insert it
			if (!isset($TPMS[$SAMPLE]))
			{
				/// The sample doesn't exist=> that's an issue
				if (!isset($SAMPLES[$SAMPLE])) 														failProcess($JOB_ID."C06","Unrecognized Sample ".$SAMPLE);
				++$DBIDS['rna_gene'];
				
				fputs($FILES['rna_gene'],
					$DBIDS['rna_gene']."\t".
					$ENSGDBID."\t".
					$SAMPLES[$SAMPLE]['DBID']."\t".
					$VALUE."\n"
				);
				$STAT['NEW_GENE_TPM']++;
				$HAS_DATA=true;	
			}
			else
			{
				/// Otherwise it's valid, unless the value is different and in this case we need to update
				$RECORD=&$TPMS[$SAMPLE];
				$RECORD['STATUS']='VALID';
				if ($VALUE!=$RECORD['tpm']){$RECORD['tpm']=$VALUE;$RECORD['STATUS']='TO_UPD';}
			}
			
		}
		
		echo $NL."\t".$STAT['NEW_GENE_TPM']."\n";
		++$N_FILE;
		/// We process 100 genes at a time, then insert
		if ($N_FILE<100)continue;
		$N_FILE=0;


		
		if (!$HAS_DATA)continue;
		
		
		addLog("inserting RNA_gene records");

		/// Closing file
		fclose($FILES['rna_gene']);	
		
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.rna_gene (rna_gene_id , gene_seq_id , rna_sample_id , tpm) FROM \''."rna_gene_insert.csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		//print_r($res);
		if ($return_code !=0 )																failProcess($JOB_ID."C07",'Unable to insert rna_gene'); 

		$FILES['rna_gene']=fopen('rna_gene_insert.csv','w');if (!$FILES['rna_gene'])		failProcess($JOB_ID."C08",'Unable to open rna_gene_insert'); 
		
	}
	fclose($fp);

	/// We need to insert the last batch of genes
	fclose($FILES['rna_gene']);


	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.rna_gene (rna_gene_id , gene_seq_id , rna_sample_id , tpm) FROM \''."rna_gene_insert.csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
	
	if ($return_code !=0 )																	failProcess($JOB_ID."C09",'Unable to insert '.$NAME); 

}


function processTranscriptData()
{


	$FILES=array();
	/// This is the max ID for each table
	$DBIDS=array();
	
	/// Get max PK value for that table to speed up insert
	$res=runQuery('SELECT MAX(rna_transcript_ID) CO FROM rna_transcript');
	if ($res===false)																			failProcess($JOB_ID."D01",'Unable to get Max ID for '.$TBL);
	$DBIDS['rna_transcript']=(count($res)==1)?$res[0]['co']:0;
	
	$FILES['rna_transcript']=fopen('rna_transcript_insert.csv','w');if (!$FILES[$TBL])			failProcess($JOB_ID."D02",'Unable to open '.$TBL.'_insert.csv');




	$res=runQuery("select distinct transcript_id from rna_transcript");
	if ($res===false)																		failProcess($JOB_ID."D03",'Unable to get transcript list');
	$LIST_INI_TRANSCRIPTS=array();
	foreach ($res as $l)$LIST_INI_TRANSCRIPTS[$l['transcript_id']]=true;

	$fp=fopen('TranscriptTPM.csv','r');if(!$fp)													failProcess($JOB_ID."D04",'Unable to open Transcript file');
	$line=stream_Get_line($fp,10000000,"\n");
	$line=stream_Get_line($fp,10000000,"\n");
	$line=stream_Get_line($fp,10000000,"\n");
	
	/// Header column
	$HEAD=explode("\t",$line);
	$N_FILE=0;
	$HAS_DATA=false;
	$START=false;
		
	while(!feof($fp))
	{
		$fpos=ftell($fp);
		
		$line=stream_Get_line($fp,10000000,"\n");if ($line=='')continue;
		
		/// Each line is a transcript with all expression data
		$t=explode("\t",$line);
		
		/// Here we convert the array to a hash map using header as key
		$tab=array_combine($HEAD,$t);

		$ENST=$tab['transcript_id'];
		
		$t=explode(".",$ENST);
		$ENST_ID=$t[0];
		echo $fpos."\t".$ENST_ID."\n";
		if (strpos($t[1],'_')!==false)continue;
		
		// if ($ENST_ID=='ENST00000564863')$START=true;
		// if (!$START)continue;
		//	if ($ENST_ID!='ENST00000556464')continue;

		/// Searching for the transcript
		$res=runQuery("SELECT * FROM transcript WHERE transcript_name='".$ENST_ID."'");
		if ($res===false)																			failProcess($JOB_ID."D05",'Failed to search for transcript ');
		if (count($res)==0){$STAT['TRANSCRIPT_NOT_FOUND']++;continue;}
		
		$STAT['TRANSCRIPT_FOUND']++;
		$ENSTDBID=$res[0]['transcript_id'];

		/// This contains the list of transcripts with existing data in the database
		/// By removing the one we processed, we will be left with those in the DB 
		/// but not in the file, so those can be deleted at the end
		unset($LIST_INI_TRANSCRIPTS[$ENSTDBID]);
		/// Getting the existing data
		$res=runQuery("SELECT sample_id,tpm 
						FROM rna_transcript RG,rna_sample RS WHERE  RS.rna_sample_id = RG.rna_sample_id 
						AND rna_source_id = ".$RNA_SOURCE_ID." AND transcript_id=".$ENSTDBID);
		if ($res===false)																			failProcess($JOB_ID."D06",'Failed to search for rna_transcript ');
		
		
		$TPMS=array();
		$TISSUE_STAT=array();
		foreach ($res as $line)	$TPMS[$line['sample_id']]=array('tpm'=>$line['tpm'],'STATUS'=>'FROM_DB');
		
		
		
		//	print_r($res);
		unset($tab['transcript_id'],$tab['gene_id']);
		$N_UPD=0;$SAMPLES_TR=array();
		/// Looking at each sample to see if the value exists/ has been updated
		foreach ($tab as $SAMPLE=>$VALUE)
		{
			
			if ($VALUE=="")continue;
			/// We don't have the value for that sample? 
			if (!isset($TPMS[$SAMPLE]))
			{
				/// Let's check if we have the sample. No? issue
				if (!isset($SAMPLES[$SAMPLE])) 														failProcess($JOB_ID."D07","Unrecognized Sample ".$SAMPLE);
				///Add it
				$SAMPLES_TR[$SAMPLE]=true;
				$HAS_DATA=true;
				fputs($FILES['rna_transcript'],$SAMPLES[$SAMPLE]['DBID']."\t".$ENSTDBID."\t".$VALUE."\n");
				$STAT['NEW_TRANSCRIPT_TPM']++;
				
			}
			else
			{
				/// Already have it, let's check for changes
				$RECORD=&$TPMS[$SAMPLE];
				$RECORD['STATUS']='VALID';
				if (isset($SAMPLES_TR[$SAMPLE]))
				{
					echo $ENST."\t".$ENST_ID."\n";
					echo $SAMPLE."\t".$VALUE."\n";
					print_r($SAMPLES_TR[$SAMPLE]);
					exit;
					
				}$SAMPLES_TR[$SAMPLE]=true;
				

				if ($VALUE!=$RECORD['tpm']){
					echo  $RECORD['tpm'].'=>'.$VALUE;
					$RECORD['tpm']=$VALUE;$RECORD['STATUS']='TO_UPD';
					
					$query='UPDATE rna_transcript 
						SET tpm='.$VALUE.' 
						WHERE transcript_id = '.$ENSTDBID.' 
						AND rna_sample_id='.$SAMPLES[$SAMPLE]['DBID'];
					echo "\t".$query."\n";
					$STAT['TRANSCRIPT_UPD']++;
					if (!runQueryNoRes($query))												 	failProcess($JOB_ID."D08",'unable to update transcript TPM: '.$query);
				}else $STAT['TRANSCRIPT_VALID']++;
			}
			
			
		}
		/// Any TPM values that haven't been found in the file need to be delete
		$LIST_DEL=array();
		echo "NF:".$N_FILE."\n";
		foreach ($TPMS as $sample_id=>$INFO)
		{
			if ($INFO!='FROM_DB')continue;
			$LIST_DEL[]=$sample_id;
		}



		echo $STAT['TRANSCRIPT_VALID']."\t".
		$STAT['TRANSCRIPT_UPD']."\t".
		$STAT['NEW_TRANSCRIPT_TPM']."\t".
		$STAT['TRANSCRIPT_DEL']."\n";


		/// If we have some data to delete, we do it
		if (count($LIST_DEL)!=0)
		{
			echo "TO DEL:".count($LIST_DEL)."\n";
			$STAT['TRANSCRIPT_DEL']+=count($LIST_DEL);
			
			$query='DELETE FROM rna_transcript 
				WHERE transcript_id='.$ENSTDBID.' 
				AND rna_sample_id IN ('.implode(',',$LIST_DEL).')';
			if (!runQueryNoRes($query)) 														failProcess($JOB_ID."D09",'unable to delete transcript TPM: '.$query);
		}
		++$N_FILE;

		/// We do it by batch of 100 transcripts. Then we insert
		if ($N_FILE<100)continue;
		$N_FILE=0;

		
		if (!$HAS_DATA)continue;
		
		addLog("inserting RNA_transcript records");
		
		/// Closing file
		fclose($FILES['rna_transcript']);

		/// Create the command to insert the data
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.rna_transcript (rna_sample_id , transcript_id , tpm ) FROM \''."rna_transcript_insert.csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		//print_r($res);
		if ($return_code !=0 )																failProcess($JOB_ID."D10",'Unable to insert rna_transcript'); 

		/// Reopen file
		$FILES['rna_transcript']=fopen('rna_transcript_insert.csv','w');
		if (!$FILES['rna_transcript'])														failProcess($JOB_ID."D11",'Unable to open rna_transcript_insert'); 
		
	}///END FILE
	fclose($fp);

	if (count($LIST_INI_TRANSCRIPTS)>0){
		$STAT['TRANSCRIPT_DEL']=count($LIST_INI_TRANSCRIPTS);
	if (!runQueryNoRes("DELETE FROM rna_transcript 
						WHERE transcript_id IN (".implode(",",array_keys($LIST_INI_TRANSCRIPTS)).')'))
																									failProcess($JOB_ID."D12",'Unable to delete transcripts');
	}

	if (!$HAS_DATA)return ;



	addLog("inserting RNA_transcript records");

	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.rna_transcript (rna_sample_id , transcript_id , tpm ) FROM \''."rna_transcript_insert.csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
	//print_r($res);
	if ($return_code !=0 )																		failProcess($JOB_ID."D13",'Unable to insert rna_transcript'); 

}
?>