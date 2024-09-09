<?php
ini_set('memory_limit','1000M');


/**
 SCRIPT NAME: db_dna
 PURPOSE:     Push DNA sequence to the database
 
*/

/// Job name - Do not change
$JOB_NAME='db_dna';


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
	/// Get Parent info
	$PARENT_INFO=$GLB_TREE[getJobIDByName('db_genome')];
	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];			if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';   				if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$PARENT_INFO['TIME']['DEV_DIR'];  		   	if (!is_dir($W_DIR) && !chdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);
	
	/// Create INSERT directory
	if (!is_dir($W_DIR.'/INSERT/') && !mkdir($W_DIR.'/INSERT/'))									failProcess($JOB_ID."004",'Unable to create directory '.$W_DIR.'/INSERT/');
	if (!chdir($W_DIR.'/INSERT/'))																	failProcess($JOB_ID."005",'Unable to access directory '.$W_DIR.'/INSERT/');

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$PARENT_INFO['TIME']['DEV_DIR'];

addLog("Working directory: ".$W_DIR);


	
addLog("Get Database id List");

	/// Here we are going to retrieve the max id for the primary key of each table
	/// This make things easier when doing batch insert
	$DBIDS=array('chr_seq_pos'=>-1);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)										failProcess($JOB_ID."006",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}




	/// Overview that json contains the list of taxons as well as file informations
	if (!is_file($W_DIR.'/overview.json'))															failProcess($JOB_ID."007",'Unable to find overview.json ');
	$TAXON_INFO=json_decode(file_get_contents($W_DIR.'/overview.json'),true);
	if ($TAXON_INFO==null)																			failProcess($JOB_ID."008",'Unable to obtain information from overview.json ');
	

	
foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
{
	addLog("##### START DNA INSERT FOR ".$TAX_ID);

// Looping over each assembly
	foreach ($LIST as $ASSEMBLY=>&$ASSEMBLY_INFO)
	{
		addLog("####### START DNA INSERT FOR ASSEMBLY ".$ASSEMBLY);

		/// Getting the current chromosomes for this taxon
		/// This needs to be done here and not above (at the taxon level) because the next query is going to be different depending on the assembly
		addLog("\tGet chromosomes");
		$GENE_SEQS=array();
		$res=runQuery("SELECT chr_num, chr_id 
					FROM chromosome c, taxon t 
					where t.taxon_Id = c.taxon_id 
					AND tax_id = '".$TAX_ID."'");
		if ($res===false)																			failProcess($JOB_ID."009",'Unable to get chromosomes for '.$TAX_ID);
		foreach ($res as $l)$GENE_SEQS[$l['chr_num']]=array('CHR_ID'=>$l['chr_id'],'CHR_SEQ'=>array());


		addLog("\tGet chromosome sequence ");
		$res=runQuery("SELECT  chr_seq_id , refseq_name , refseq_version , genbank_name , genbank_version , cs.chr_id , chr_num , chr_seq_name,seq_role,assembly_unit
						FROM chr_seq cs, chromosome c
						WHERE  c.chr_id = cs.chr_id AND genome_assembly_id = ".$ASSEMBLY_INFO['DBID']);
		if ($res===false)																			failProcess($JOB_ID."010",'Unable to get chromosome sequence for '.$TAX_ID);
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$GENE_SEQS[$line['chr_num']]['CHR_SEQ'][$line['genbank_name']]=$line;
		}


		/// Now we are ready to push the DNA files in the database
		/// If the user has requested both resources, we will choose to push Refseq because of their bam files. Only Ensembl specific sequences will be added
		/// If the user has requested only one resource, we will push that one either way
		$DNA_DONE=false;
		/// 
		foreach ($ASSEMBLY_INFO['SOURCES'] as &$SOURCE_INFO)
		{
			if ($SOURCE_INFO['Source']!='REFSEQ')continue;
			addLog("######### START DNA INSERT FOR SOURCE ".$SOURCE_INFO['Source']);
			processDNA($TAX_ID,$SOURCE_INFO['DIR'],$GENE_SEQS,false);
			$DNA_DONE=true;
			
		}
		
		
		foreach ($ASSEMBLY_INFO['SOURCES'] as &$SOURCE_INFO)
		{
			if ($SOURCE_INFO['Source']=='REFSEQ')continue;
			addLog("######### START DNA INSERT FOR SOURCE ".$SOURCE_INFO['Source']);
			processDNA($TAX_ID,$SOURCE_INFO['DIR'],$GENE_SEQS,$DNA_DONE);
			$DNA_DONE=true;
			
		}
	}
}


successProcess();














	
function loadInDB($CHR_SEQ_ID)
{
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;

	$res=array();
	$time=microtime_float();
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.chr_seq_pos  ( chr_seq_pos_id ,chr_seq_id ,nucl,chr_pos) FROM \''."chr_seq_pos.csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
	echo "TIME\t".round(microtime_float()-$time,3)."\n";
	echo "END LOADING\n";
	if ($return_code !=0 )																		failProcess($JOB_ID."A01",'Unable to insert chr_seq_pos '.$CHR_SEQ_ID); 

}


function processDNA($TAX_ID,&$INFO_DIR,$GENE_SEQS,$DNA_DONE)
{
	
	global $JOB_ID;
	global $DBIDS;
	global $DB_INFO;
	global $GLB_VAR;
	

	/// First we create the file that will be used to load the data
	if (is_file('chr_seq_pos.csv'))unlink('chr_seq_pos.csv');
	$fpO=fopen('chr_seq_pos.csv','w'); if (!$fpO) 				failProcess($JOB_ID."B01",'Unable to open chr_seq_pos.csv');

	/// Depending on the source, some uses RefSeq Name others chromosome name of genbank name
	/// To make sure we don't miss any of them, we list the different possibility
	$MAP=array();
	foreach ($GENE_SEQS as $CHR=>&$LIST)
	foreach ($LIST['CHR_SEQ'] as $T )
	{
		$MAP[$T['refseq_name'].'.'.$T['refseq_version']]=$T['chr_seq_id'];
		$MAP[$T['genbank_name'].'.'.$T['genbank_version']]=$T['chr_seq_id'];
		$MAP[$T['genbank_name']]=$T['chr_seq_id'];
		$MAP[$T['chr_seq_name']]=$T['chr_seq_id'];
		if  ($T['seq_role']=='assembled-molecule')$MAP[$T['chr_num']]=$T['chr_seq_id'];
		
	}
	if (!is_dir($INFO_DIR))																		failProcess($JOB_ID."B02",'Unable to find Tax dir '.$INFO_DIR);


	/// Then we read the DNA file
	if (!checkFileExist($INFO_DIR.'/'.$TAX_ID.'_seq.fa'))										failProcess($JOB_ID."B03",'Unable to find DNA file in '.$INFO_DIR);
	$fp=fopen($INFO_DIR.'/'.$TAX_ID.'_seq.fa','r'); if (!$fp)									failProcess($JOB_ID."B04",'Unable to open DNA file: '.$INFO_DIR);
	
	
	$CHR_SEQ_ID=-1;/// Primary key of the chromosome sequence
	$time=0;
	$STR='';	/// String that will be used to load the data in the database
	$N_LINE=0;
	$N_POS=0;	/// Chromosomal position
	$IS_VALID=false;/// Boolean: True is the sequence is new  OR the sequence exist but its length and hash is different in the database
	$md5='';
	$LEN_CHR=0;
	$CURR_CO=array();
	$START=false;
	while(!feof($fp))
	{
		
		$line=stream_get_line($fp,1000,"\n");
		/// Each line starting with > is the beginning of a new sequence
		if (substr($line,0,1)=='>')
		{
			
			if ($CHR_SEQ_ID!=-1 && $START){/// Finalize the previous sequence, if any
				if ($NEW_REC)
				{
					echo 'LAST LOAD CHR'."\n";
					echo "|".count(explode("\n",$STR))." records|\n";
					fputs($fpO,$STR);
					loadInDB($CHR_SEQ_ID);
				}
				
				/// Then we update the hash and length
				$query ='UPDATE chr_seq
					 SET md5_seq_hash = \''.$md5.'\', seq_len = '.$LEN_CHR.' 
					 WHERE chr_seq_id='.$CHR_SEQ_ID;
				
				$res=runQueryNoRes($query);
				if ($res===false)																failProcess($JOB_ID."B05",'Unable to set hash and length');
			
				echo "########################################### END ".$CHR_SEQ_ID."\n";
				
			}
			echo "\n\n".$line."\n";
			$NEW_REC=false; /// Boolean: True if nucleotides are saved in the file and that we will need to push in the database
			$IS_VALID=false; /// Boolean: True is the sequence is new  OR the sequence exist but its length and hash is different in the database
			$TO_UPD=false;
			$N_POS=0;/// Length of the sequence
			/// Get the name
			$pos=strpos($line,' ');
			$name=substr($line,1,$pos-1);
			if (substr($name,0,4)=='CHR_')$name=substr($name,4);
			echo "NAME:".$name."\t";


			///DEBUG : IF YOU NEED TO RESTART AT A GIVEN CHROMOSOME:
			// if ($name=='JAANEP010001441.1')$START=true;
			// else if (!$START)continue;


			/// The name should be recorded in the CHR_SEQ table.
			if (!isset($MAP[$name]))															failProcess($JOB_ID."B06",'Unable to find Chromosome sequence : '.$name);
			$CHR_SEQ_ID=$MAP[$name];
			echo "CHR_SEQ_ID:".$CHR_SEQ_ID."\t";

			/// Now we get the length and hash for that sequence
			$res=runQuery("SELECT seq_len, md5_seq_hash FROM CHR_SEQ WHERE CHR_SEQ_ID=".$CHR_SEQ_ID);
			if ($res===false)																failProcess($JOB_ID."B07",'Unable to get hash and length');
			$CURR_CO=$res[0];
			echo "CURRENT_LEN/HASH:".$CURR_CO['seq_len'].'/'.$CURR_CO['md5_seq_hash']."\t";
			if ($DNA_DONE && $CURR_CO['md5_seq_hash']!=''){$IS_VALID=true;continue;}/// If the sequence is already in the database and we have already processed the DNA, then we skip it
			
			
			/// We want to validate the hash and length. So we save that specific sequence in a file, compute the length,and call md5_file function to get the hash
			$STR='';
			$fpos=ftell($fp);
			$fpT=fopen('tmp_file','w');if (!$fpT)											failProcess($JOB_ID."B08",'Unable to open tmp_file');
			$LEN_CHR=0;
			while(!feof($fp))
			{
				$line=stream_get_line($fp,1000,"\n");
				if (substr($line,0,1)=='>')break;
				/// We do the md5 has on lower case sequence because of difference in masking between RefSeq and Ensembl
				fputs($fpT,strtolower($line)."\n");
				$LEN_CHR+=strlen($line);
			}
			fclose($fpT);

			$md5=md5_file('tmp_file');
			if (!unlink('tmp_file'))															failProcess($JOB_ID."B09",'Unable to delete tmp_file');
			echo "FILE LEN/HASH:".$LEN_CHR.'/'.$md5."|CHECK SEQ TABLE:";
			/// Now we can compare it against the record in the database if it exist:
			
			/// To double check, we also compute the actual number of rows for that chromosome sequence
			$res=runQuery("SELECT COUNT(*) co FROM chr_seq_pos where chr_seq_id=".$CHR_SEQ_ID);
			if ($res===false)																failProcess($JOB_ID."B10",'Unable to set hash and length');
			$CO=$res[0]['co'];
			/// And we compare
			echo $CURR_CO['seq_len']."<>".$LEN_CHR."<>".$CO."\t".$CURR_CO['md5_seq_hash']."<>".$md5;
			
			
			if ($CO==0)
			{
				$TO_UPD=false;
				$START=true;
			}
			else if (!($CURR_CO['seq_len']==$LEN_CHR &&$CURR_CO['md5_seq_hash']==$md5) || $CO!=$CURR_CO['seq_len'])
			{
				/// Trying to delete is not going to work due to size contraint and foreign keys.
				/// so we do an update.
				echo $LEN_CHR."\t".$md5."\n";
				$TO_UPD=true;
				$START=true;
				// addLog("DELETION");
				// for ($I=0;$I<=$LEN_CHR;$I+=100)
				// {
				// 	echo $I.' '.($I+100)."\n";
				// if (!runQueryNoRes("DELETE FROM CHR_SEQ_POS WHERE CHR_SEQ_ID = ".$CHR_SEQ_ID.' AND chr_pos >='.$I.' AND chr_pos <='.($I+100)))failProcess($JOB_ID.'020','Unable to delete chr_seq_pos');
				// }
				// addLog("END DELETION");
			}else 
			{
				echo "\tVALID";
				$IS_VALID=true;
			}
			
			echo "\n";
			fseek($fp,$fpos);
			fclose($fpO);
			
			$fpO=fopen('chr_seq_pos.csv','w');if (!$fp)												failProcess($JOB_ID."B11",'Unable to open chr_seq_pos');
			continue;
		}
		//echo "V:".$IS_VALID." ".$START."\n";
		if ($IS_VALID)continue;/// If it's valid, no need to process that sequence
		if (!$START)continue;
		++$N_LINE;
		//echo "LINE:".$N_LINE."\t|".$IS_VALID."|\t".$CHR_SEQ_ID."\n";
		$LEN=strlen($line);
		$NEW_REC=true;
		
		
		
		if ($TO_UPD==false)
		{
			for ($I=0;$I<$LEN;++$I)// We add each individual nucleotide as a row in that file
			{
				$DBIDS['chr_seq_pos']++;
				++$N_POS;
				//echo $CHR_SEQ_ID."\tADD\t".$N_POS."\n";
				$STR.=$DBIDS['chr_seq_pos']."\t".$CHR_SEQ_ID."\t".substr($line,$I,1)."\t".$N_POS."\n";
				
			}
			echo '.';
		}
		else
		{
			/// Getting the next batch of positions
			$res=runQuery("SELECT * FROM chr_seq_pos 
							where chr_seq_id =".$CHR_SEQ_ID.' 
							AND chr_pos >='.($N_POS-1).' 
							AND chr_pos<='.($N_POS+$LEN));
			if ($res===false)															failProcess($JOB_ID."B12",'Unable to get chr_seq_pos');
			$temp=array();
			$UPD_MAP=array();
			$HAS_NEW=false;$HAS_UPD=false;
			foreach ($res as $k)$temp[$k['chr_pos']]=$k['nucl'];

			for ($I=0;$I<$LEN;++$I)// We look at each individual nucleotide as a row in that file
			{
				
				++$N_POS;
				if (isset($temp[$N_POS]))/// Already in the db, check if its the same nucleotide
				{
					// if not => add to an array nucleotide => list of positions to minimize the number of update queries
					if ($temp[$N_POS]!=substr($line,$I,1))
					{
						$HAS_UPD=true;
						/// We group them by nucleotide so we reduce the number of queries
						$UPD_MAP[substr($line,$I,1)][]=$N_POS;
					}
					continue;
				}
				else
				{
					$HAS_NEW=true;
					/// when it's not in db, then ewe add it.
					$DBIDS['chr_seq_pos']++;
					$STR.=$DBIDS['chr_seq_pos']."\t".$CHR_SEQ_ID."\t".substr($line,$I,1)."\t".$N_POS."\n";
				}
			}
			if ($HAS_UPD)echo 'U';
			else if ($HAS_NEW) echo '.';
			else echo '|';


			if ($UPD_MAP==array())continue;
		
			/// When there is anything to update, we run the queries
			foreach ($UPD_MAP as $NUCL=>&$LIST_NUCL_CH)
			{
				$query='UPDATE chr_seq_pos 
					set nucl = \''.$NUCL.'\' 
					WHERE chr_seq_id = '.$CHR_SEQ_ID .' 
					AND chr_pos IN ('.implode(',',$LIST_NUCL_CH).')';
				if (!runQueryNoRes($query))											failProcess($JOB_ID."B13",'Unable to update chr_seq_pos');
			}
			
		
		}///END ELSE
		
		if ($N_LINE%1000!=0)continue;
		/// And save it to a file every 1K line
		fputs($fpO,$STR);
		echo "\n";
		
		$STR='';
		/// When we reach 50K lines, we push it to the database
		if ($N_LINE%50000!=0)continue;
		addLog("NEWLOAD :".$N_POS);
		fclose($fpO);
		loadInDB($CHR_SEQ_ID);
		if (!unlink('chr_seq_pos.csv'))												failProcess($JOB_ID."B14",'Unable to delete chr_seq_pos.csv');
		$fpO=fopen('chr_seq_pos.csv','w');
		if (!$fpO )																					failProcess($JOB_ID."B15",'Unable to open chr_seq_pos'); 
	

	}
	fclose($fp);
	
	/// Once the file is processed, we still have the latest chromosome to save
	if (!$NEW_REC)
	{
		fclose($fpO);
		return;
	}
	
	echo 'LAST LOAD'."\n";
	echo "|".$STR."|\n";
	/// We save it to a file
	fputs($fpO,$STR);
	/// And push it to the database
	fclose($fpO);
	loadInDB($CHR_SEQ_ID);

	/// And we update the hash and length
	$query ='UPDATE chr_seq SET md5_seq_hash = \''.$md5.'\', seq_len = '.$LEN_CHR.' WHERE chr_seq_id='.$CHR_SEQ_ID;
	echo $query."\n";
	$res=runQueryNoRes($query);
	if ($res===false)failProcess($JOB_ID."B16",'Unable to set hash and length');
	

}


?>
