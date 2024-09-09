<?php



error_reporting(E_ALL);
ini_set('memory_limit','1000M');


/**
 SCRIPT NAME: db_genome
 PURPOSE:     Push Chromosome sequence and gene sequence to the database
 PARAMETERS:  Please review CONFIG_USER - GENOME section

*/

/// Job name - Do not change
$JOB_NAME='db_genome';


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
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];			if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';   				if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();  		   					if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					  			if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();
	if (!is_dir($W_DIR.'/INSERT/') && !mkdir($W_DIR.'/INSERT/'))									failProcess($JOB_ID."005",'Unable to create directory '.$W_DIR.'/INSERT/');
	if (!isset($GLB_VAR['TOOL']['NCBI_DATASET']))													failProcess($JOB_ID."006",'NCBI_DATASET tool not set');
	if (!is_executable($GLB_VAR['TOOL']['NCBI_DATASET']))											failProcess($JOB_ID."007",'NCBI_DATASET tool not executable');


	/// We need to check that the prd_gene job has been run
	/// To map Ensembl genes to NCBI genes
	$JOB_GENE=$GLB_TREE[getJobIDByName('prd_gene')];
	$GENE_PRD_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'].'/'.$JOB_GENE['DIR'].'/';
	if (!is_dir($GENE_PRD_DIR))																		failProcess($JOB_ID."008",'Unable to find prod directory for prd_gene');
	if (!is_file($GENE_PRD_DIR.'/gene2ensembl'))													failProcess($JOB_ID."009",'Unable to find gene2ensembl');
	


	addLog("Preparing translation table (for future use)");
	prepareTranslationTable();
	
	

	/// Biotypes are used to describe a given gene sequence or transcript.
	/// the seq_btype table is used as a bridge between the sequence ontology which describes those biotypes
	/// and the biotypes defined by Ensembl and RefSeq
	/// We use a pre-defined mapping stored in STATIC_DIR to simplify the work
	addLog("Preparing biotypes");	
	$BIOTYPES=loadBiotypes();


	/// Here we are going to retrieve the max id for the primary key of each table
	/// This make things easier when doing batch isnert
	addLog("Get Database primary key values");

	$DBIDS=array('genome_assembly'=>-1,
	'chr_seq'=>-1,
	'gene_seq'=>-1,
	'transcript'=>-1);

	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)									failProcess($JOB_ID."010",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}

	addLog("Checking existing assembly directories");
	$WITH_REFSEQ=false;
	$WITH_ENSEMBL=false;
	// $TAXON_INFO is a reference to the global GENOME variable
	$TAXON_INFO=&$GLB_VAR['GENOME'];
	foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
	foreach ($LIST as &$INFO)	
	{
		if ($INFO['Source']=='ENSEMBL')$WITH_ENSEMBL=true;
		else if ($INFO['Source']=='REFSEQ')$WITH_REFSEQ=true;
	}

	//Define root directory for each source
	$ENS_ROOT_DIR='';
	$REF_ROOT_DIR='';
	if ($WITH_ENSEMBL)
	{
		addLog("\tChecking Ensembl root directory");
		$ENS_CURR_DIR=$GLB_TREE[getJobIDByName('dl_ensembl')]['TIME']['DEV_DIR'];
		if ($ENS_CURR_DIR==-1) 																		failProcess($JOB_ID."011",'NO Ensembl directory found at '.$ENS_CURR_DIR);
		$ENS_ROOT_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$GLB_TREE[getJobIDByName('dl_ensembl')]['DIR'].'/'.$ENS_CURR_DIR;
		if (!is_dir($ENS_ROOT_DIR)) 																failProcess($JOB_ID."012",'NO Ensembl directory found at '.$ENS_ROOT_DIR);
	}
	if ($WITH_REFSEQ)
	{
		addLog("\tChecking RefSeq root directory");
		$REF_CURR_DIR=$GLB_TREE[getJobIDByName('dl_refseq')]['TIME']['DEV_DIR'];
		if ($REF_CURR_DIR==-1) 																		failProcess($JOB_ID."013",'NO RefSeq directory found at '.$REF_CURR_DIR);
		$REF_ROOT_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$GLB_TREE[getJobIDByName('dl_refseq')]['DIR'].'/'.$REF_CURR_DIR;
		if (!is_dir($REF_ROOT_DIR)) 																failProcess($JOB_ID."014",'NO RefSeq directory found at '.$REF_ROOT_DIR);
	}
	

addLog("Get existing assemblies from database");
	/// Now we need to know if the assembly is already in the system
	/// So we download them from the database
	$res=runQuery("SELECT genome_assembly_id FROM genome_assembly");
	if ($res===false)																		failProcess($JOB_ID."015",'Unable to fetch current assemblies');
	$DB_ASSEMBLIES=array();
	foreach ($res as $l)$DB_ASSEMBLIES[$l['genome_assembly_id']]=false;



addLog("List current assemblies");
	$CURR_ASSEMBLIES=array();
	/// Here we are going to check all the genomes and the directories
	// Looping over each taxon
	foreach ($TAXON_INFO as $TAX_ID=>&$LIST)
	{
		// Looping over each assembly
		foreach ($LIST as $K=> &$INFO)	
		{
			// We set the path depending on the source
			$BUILD_DIR=$TAX_ID.'__'.$INFO['Assembly_Acc'].'__'.$INFO['Assembly_name'];
			$CURR_DIR='';
			if 		($INFO['Source']=='ENSEMBL')$CURR_DIR=$ENS_ROOT_DIR.'/'.$BUILD_DIR;
			else if ($INFO['Source']=='REFSEQ')$CURR_DIR=$REF_ROOT_DIR.'/'.$BUILD_DIR;
			if (!is_dir($CURR_DIR)) 																	failProcess($JOB_ID."016",'NO '.$CURR_DIR.' found ');
			
			/// And we transfer the information from the global variable to CURR_ASSEMBLIES which we will use afterwards	
			$NEW_INFO=$INFO;
			$NEW_INFO['DIR']=$CURR_DIR;
			
			$CURR_ASSEMBLIES[$TAX_ID][$BUILD_DIR]['SOURCES'][]=$NEW_INFO;
		}
	}






addLog("Comparing assemblies and creating directories");

	/// We create a file that will contain the list of assemblies and their source to be used as a summary
	$fp=fopen('INFO_VERSION','w');if (!$fp)													failProcess($JOB_ID."017",'Unable to open INFO_VERSION');
	foreach ($CURR_ASSEMBLIES as $TAX_ID => &$LIST)
	{
		/// We create a directory for each taxon in the GENOME directory
		$TAX_DIR=$W_DIR.'/'.$TAX_ID;
		if (!is_dir($TAX_DIR) && !mkdir($TAX_DIR))											failProcess($JOB_ID."018",'Unable to create '.$TAX_ID.' directory for '.$TAX_ID);
		if (!chdir($TAX_DIR) )																failProcess($JOB_ID."019",'Unable to get to '.$TAX_ID.' directory for '.$TAX_ID);

	
		foreach ($LIST as $ASSEMBLY=>&$ASSEMBLY_INFO)
		{
			/// We create a directory for each assembly
			foreach ($ASSEMBLY_INFO['SOURCES'] as &$SOURCE_INFO)
			{
				// $SOURCE_INFO has the accession number, that we need to retrieve the assembly information
				// so if the file does not exist, we download it
				if (!checkFileExist($ASSEMBLY.'.json'))
				{
					$res=array();
					exec($GLB_VAR['TOOL']['NCBI_DATASET'].' summary genome accession '.$SOURCE_INFO['Assembly_Acc'].' > '.$ASSEMBLY.'.json',$res,$return_code);
					if ($return_code!=0) 																failProcess($JOB_ID."020",'Unable to download assembly information for '.$SOURCE_INFO['Assembly_Acc'].' '.print_R($res,true));
				}
				/// We then retrieve the assembly information
				$TMP = json_decode(file_get_contents($ASSEMBLY.'.json'),TRUE);if ($TMP==null)			failProcess($JOB_ID."021",'Unable to get assembly information for '.$SOURCE_INFO['Assembly_Acc']);
				// And we save it in the global variable
				$ASSEMBLY_INFO['SUMMARY']=$TMP['reports'][0];
	
				// We create a symlink to the RefSeq or Ensembl directory into our Genome directory
				$SYMLINK_PATH=$ASSEMBLY.'__'.$SOURCE_INFO['Source'];
				if (is_link($SYMLINK_PATH) && !unlink($SYMLINK_PATH))																failProcess($JOB_ID."022",'Unable to delete RefSeq symlink for taxon '.$TAX_ID);	
				if (!symlink($SOURCE_INFO['DIR'],$SYMLINK_PATH)) 																failProcess($JOB_ID."023",'Unable to create RefSeq symlink for taxon '.$TAX_ID);	
				fputs($fp,str_replace("__","\t",$ASSEMBLY)."\t".$SOURCE_INFO['Source']."\t".$SOURCE_INFO['DIR']."\n");
			}
			/// Once done, we use the accession to retrieve the id from the database or create the record if it does not exist
			$ASSEMBLY_INFO['DBID']=checkAssembly($ASSEMBLY_INFO['SUMMARY']);
			
			// We confirm we need that assembly that is defined (or just been created) in the database
			if (isset($DB_ASSEMBLIES[$ASSEMBLY_INFO['DBID']])) $DB_ASSEMBLIES[$ASSEMBLY_INFO['DBID']]=true;
		}
		
	}

	fclose($fp);
	/// All taxonomic information with their assembly is then saved in overview.json as a snapshot
	$fp=fopen($W_DIR.'/overview.json','w');if (!$fp)																			failProcess($JOB_ID."024",'Unable to get assembly information for ensembl');
	fputs($fp,json_encode($CURR_ASSEMBLIES)."\n");
	fclose($fp);
	
	

	/// DB_ASSEMBLIES contains the list of assemblies from the database
	/// If any of them is part of the current list of assemblies, we will have to delete them
	$query='';
	$TO_DEL['GENOMIC_ASSEMBLY']=array();
	foreach ($DB_ASSEMBLIES as $GENOME_SEQ_ID=>$STATUS)if (!$STATUS)$TO_DEL['GENOMIC_ASSEMBLY'][]=$GENOME_SEQ_ID;
	/// If an assembly needs to be deleted - we delete it
	if ($TO_DEL['GENOMIC_ASSEMBLY']!=array()) cleanupGenomeAssembly($TO_DEL);


	

	/// COL_ORDER is an array that list all tables that needs to be inserted as keys and the list of columns as values.
	$COL_ORDER=array('gene_seq'=>'(gene_seq_id , gene_seq_name , gene_seq_version , strand , start_pos , end_pos , biotype_id , chr_seq_id , gn_entry_id)' );
   
	$FILES=array();$N_PROCESSED=0;
	foreach ($COL_ORDER as $TYPE=>$CTL)
	{
		$FILES[$TYPE]=fopen($W_DIR.'/INSERT/'.$TYPE.'.csv','w');
		if (!$FILES[$TYPE])																										failProcess($JOB_ID."025",'Unable to open '.$TYPE.'.csv');
	}
	
	/// Now that the assembly are saved, we can proceed with each chromosome sequence
	foreach ($CURR_ASSEMBLIES as $TAX_ID => &$LIST)
	{
		foreach ($LIST as $ASSEMBLY=>&$ASSEMBLY_INFO)
		{
			processChromosomeSeq($TAX_ID,$ASSEMBLY,$ASSEMBLY_INFO);
			processGeneSequence($TAX_ID,$ASSEMBLY,$ASSEMBLY_INFO);
		}
		
	}

	
	
echo "SUCCESS\n";

	successProcess();













	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



function checkAssembly(&$ASSEMBLY_INFO)
{
	
	global $DBIDS;

	addLog("\tCheck assembly");
	/// We retrieve the full accession name
	$ASSEMBLY_FULL_ACC=$ASSEMBLY_INFO['accession'];
	$pos=strpos($ASSEMBLY_FULL_ACC,'.');
	
	/// And try to distinguish the name from the version
	$ASSEMBLY_ACC=$ASSEMBLY_FULL_ACC;$ASSEMBLY_VER='';
	if ($pos!==false)
	{
		$ASSEMBLY_ACC=substr($ASSEMBLY_FULL_ACC,0,$pos);
		$ASSEMBLY_VER=substr($ASSEMBLY_FULL_ACC,$pos+1);
	}

	$ASSEMBLY_NAME=$ASSEMBLY_INFO['assembly_info']['assembly_name'];
	$ASSEMBLY_CREATION_DATE=$ASSEMBLY_INFO['assembly_info']['release_date'];
	
	$TAX_ID=$ASSEMBLY_INFO['organism']['tax_id'];
	
	
	addLog("\t\tName: ".$ASSEMBLY_NAME."\tTax_id: ".$TAX_ID);


	/// Now we retrieve the genome assembly based on the assembly accession
	$res=runQuery("SELECT genome_assembly_id , assembly_accession , assembly_version , 
	assembly_name , t.taxon_id , tax_Id, last_update_date , creation_date , annotation
	FROM genome_assembly g,taxon t 
	where t.taxon_id=  g.taxon_Id 
	AND  assembly_accession ='".$ASSEMBLY_ACC."'");
	
	if ($res===false)																										failProcess($JOB_ID."A01",'Unable to retrieve genome assembly information');
	if (count($res)==1)
	{
		/// If it exists, we check that the name, version, taxon are correct
		$ENTRY=$res[0];$TO_UPD=false;
		if ($ENTRY['assembly_version']!=$ASSEMBLY_VER){$TO_UPD=true;}
		if ($ENTRY['assembly_name']!=$ASSEMBLY_NAME){$TO_UPD=true;}
		if ($ENTRY['tax_id']!=$TAX_ID){$TO_UPD=true;}
		if ($ENTRY['annotation']!=json_encode($ASSEMBLY_INFO)){$TO_UPD=true;}
		/// And update if necessary
		if ($TO_UPD)
		{
			
			
			$query='UPDATE genome_assembly 
			SET assembly_version='.$ASSEMBLY_VER.", 
			assembly_name = '".str_replace("'","''",$ASSEMBLY_NAME)."', 
			taxon_id = (SELECT taxon_Id FROM taxon where tax_id ='".$TAX_ID."') ,
			annotation= '".str_replace("'","''",json_encode($ASSEMBLY_INFO))."'
			WHERE genome_assembly_id =".$ENTRY['genome_assembly_id'];
			echo $query."\n";
			if (!runQueryNoRes($query)) 																				failProcess($JOB_ID."A02",'Unable to update genome_assembly');	

		}
		return $ENTRY['genome_assembly_id'];

	}
	else if (count($res)==0)
	{
		/// Otherwise we create it
			$DBIDS['genome_assembly']++;
			$query='INSERT INTO genome_assembly(genome_assembly_id , assembly_accession , assembly_version , assembly_name , taxon_id , last_update_date , creation_date,annotation) VALUES ('.
			$DBIDS['genome_assembly'].",
			'".$ASSEMBLY_ACC."',
			".$ASSEMBLY_VER.",
			'".str_replace("'","''",$ASSEMBLY_NAME)."',
			(SELECT taxon_Id FROM taxon where tax_id = '".$TAX_ID."'), 
			CURRENT_DATE, 
			'".$ASSEMBLY_CREATION_DATE."',
			'".str_replace("'","''",json_encode($ASSEMBLY_INFO))."')";
			if (!runQueryNoRes($query)) 																				failProcess($JOB_ID."A03",'Unable to insert genome_assembly'."\n".$query);	
			return $DBIDS['genome_assembly'];
	}
	
	/// If we have more than one result, it's an error
	
	failProcess($JOB_ID."A04",'Unexpected number of genome assembly results');
}





////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// Given a Taxon and an assembly, we are going to process the chromsome sequences, patches, scaffolds, etc	
function processChromosomeSeq(&$TAX_ID,&$ASSEMBLY,&$ASSEMBLY_INFO)
{

	global $W_DIR;
	global $GENE_PRD_DIR;
	global $FILES;
	global $BIOTYPES;
	global $DBIDS;
	global $COL_ORDER;
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;

	addLog("###Processing Chromosome Assembly ".$ASSEMBLY. " for ".$TAX_ID)	;
	
	$ENS_INFO=null;
	$RS_INFO=null;
	foreach ($ASSEMBLY_INFO['SOURCES'] as &$SOURCE_INFO)
	{
		if 	  ($SOURCE_INFO['Source']=='ENSEMBL')$ENS_INFO=&$SOURCE_INFO;
		else if ($SOURCE_INFO['Source']=='REFSEQ')$RS_INFO=&$SOURCE_INFO;
	}

	/// Each chromosome sequence or scaffold is associated with a taxon and an assembly
	/// It is also associated with a chromosome entry.
	/// Therefore, we will need to retrieve the chromosome entry id for the taxon - which is completely independent from the assembly


	addLog("\tGet all chromosomes from Database for Tax id ".$TAX_ID."\n");
	$GENE_SEQS=array();
	$res=runQuery("SELECT chr_num, chr_id 
					FROM chromosome c, taxon t 
					where t.taxon_Id = c.taxon_id AND tax_id = '".$TAX_ID."'");
	if ($res===false)																								failProcess($JOB_ID."B01",'Unable to get chromosomes for '.$TAX_ID);
	foreach ($res as $l)$GENE_SEQS[$l['chr_num']]=array('CHR_ID'=>$l['chr_id'],'CHR_SEQ'=>array());
	
	/// We are then getting the already recorded chromosome sequences for the assembly and map them to their chromosome for simplicity
	$res=runQuery("SELECT  chr_seq_id , refseq_name , refseq_version , genbank_name , genbank_version , 
							cs.chr_id , chr_num , chr_seq_name,seq_role,assembly_unit,chr_start_pos,chr_end_pos
					FROM chr_seq cs, chromosome c
					WHERE  c.chr_id = cs.chr_id AND genome_assembly_id = ".$ASSEMBLY_INFO['DBID']);
	if ($res===false)																								failProcess($JOB_ID."B02",'Unable to get chromosome sequences for '.$TAX_ID);				
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$GENE_SEQS[$line['chr_num']]['CHR_SEQ'][$line['genbank_name']]=$line;
	}
	
	/// Then we process refseq assembly file if it is considered
	if ($RS_INFO!=null)processRefSeqChromosomeSeq($TAX_ID,$ASSEMBLY,$RS_INFO,$GENE_SEQS,$ASSEMBLY_INFO);
		
	

	/// If ensembl is considered, we process the assembly file
	if ($ENS_INFO!=null) processEnsemblChromosomeSeq($TAX_ID,$ASSEMBLY,$ENS_INFO,$GENE_SEQS,$ASSEMBLY_INFO);
			
}


function processEnsemblChromosomeSeq(&$TAX_ID,&$ASSEMBLY,&$ENS_INFO,&$GENE_SEQS,&$ASSEMBLY_INFO)
{
	global $W_DIR;
	global $DBIDS;
	global $JOB_ID;
	


	addLog("Processing Ensembl  Chromosome Assembly for ".$TAX_ID)	;

	/// First we need to create the build path:
	$BUILD_DIR=$W_DIR.'/'.$TAX_ID.'/'.$TAX_ID.'__'.$ENS_INFO['Assembly_Acc'].'__'.$ENS_INFO['Assembly_name'].'__ENSEMBL/';
	if (!checkFileExist($BUILD_DIR.$TAX_ID.'_assembly.txt'))											failProcess($JOB_ID."C01",'Unable to find '.$TAX_ID.'_assembly.txt at '.$BUILD_DIR.$TAX_ID.'_assembly.txt');

	/// Getting the assembly information
	$fp=fopen($BUILD_DIR.$TAX_ID.'_assembly.txt','r');if(!$fp)											failProcess($JOB_ID."C02",'Unable to open '.$TAX_ID.'_assembly.txt');
	
	/// Ignoring the first line - header
	$line=stream_get_line($fp,10000,"\n");
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		if (substr($line,0,1)=='#')continue;
		
		$tab=explode("\t",$line);
		
		echo $line."\n";
		
		/// First getting the chromosome
		if ($tab[4]=='NA')$tab[4]='Un';
		
		/// Obviously the chromosome should exist for that taxon
		if (!isset($GENE_SEQS[$tab[4]]))																failProcess($JOB_ID."C03",'Chromosome '.$tab[4].' doesn\'t exist for tax id'.$TAX_ID);
		$CHR_SEQS=&$GENE_SEQS[$tab[4]];

		/// Get refseq name
		$REFSEQ=array('N/A',0);
		
		/// Get genbank name
		$GENBANK=array('N/A',0);
		if ($tab[0]!='na')			$GENBANK=explode(".",$tab[0]);

		/// Does the record exist?
		if (!isset($CHR_SEQS['CHR_SEQ'][$GENBANK[0]]))
		{
			/// No, we create it
			$DBIDS['chr_seq']++;
			$query='INSERT INTO chr_seq (chr_seq_id,refseq_name,refseq_version,genbank_name,genbank_version,
					chr_id,chr_seq_name,seq_role,assembly_unit,genome_assembly_id) VALUES ('.
			$DBIDS['chr_seq'].",'".
			$REFSEQ[0]."',".
			$REFSEQ[1].",'".
			$GENBANK[0]."',".
			$GENBANK[1].",".
			$GENE_SEQS[$tab[4]]['CHR_ID'].",'".
			$tab[1]."','".
			$tab[3]."','".
			$tab[6]."',".$ASSEMBLY_INFO['DBID'].")";
			
			
			if (!runQueryNoRes($query))																			failProcess($JOB_ID."C04",'Fail to insert new chromosome sequence');
			
			/// Then we update the array:
			$CHR_SEQS['CHR_SEQ'][$GENBANK[0]]=array('refseq_name'=>'',
				'refseq_version'=>'',
				'genbank_name'=>$GENBANK[0],
				'genbank_version'=>$GENBANK[1],
				'chr_id'=>$GENE_SEQS[$tab[4]]['CHR_ID'],
				'chr_seq_name'=>$tab[1],
				'seq_role'=>$tab[3],
				'assembly_unit'=>$tab[6],
				'chr_seq_id'=>$DBIDS['chr_seq']);

			/// We add the different names to $ENS_INFO['CHR_SEQ_IDS'] so we can easily search for it when processing genes
			$ENS_INFO['CHR_SEQ_IDS'][$tab[1]]=$DBIDS['chr_seq'];
			$ENS_INFO['CHR_SEQ_IDS'][implode(".",$GENBANK)]=$DBIDS['chr_seq'];
			$ENS_INFO['CHR_SEQ_IDS_TO_GENBANK'][$DBIDS['chr_seq']]=array($tab[4],$GENBANK[0]);
		}
		else 
		{
			/// Otherwise, we check if anything needs to be updated.
			/// Note: Here we don't update the refseq information, because it's not provided in Ensembl 
			$ENTRY=&$CHR_SEQS['CHR_SEQ'][$GENBANK[0]];
			
			$IS_NEW=($ENTRY['DB_STATUS']=='INSERTED');
			$ENTRY['DB_STATUS']='VALID';
			$TO_UPD=false;
			$query='UPDATE chr_seq SET ';
			
			if ($ENTRY['genbank_name']!=$GENBANK[0])			{$TO_UPD=true;		$query .= "genbank_name = '".$GENBANK[0]."',";		}
			if ($ENTRY['genbank_version']!=$GENBANK[1])			{$TO_UPD=true;		$query .= "genbank_version = ".$GENBANK[1].",";		}
			if ($ENTRY['chr_id']!=$GENE_SEQS[$tab[4]]['CHR_ID']){$TO_UPD=true;		$query .= "chr_id = ".$GENE_SEQS[$tab[4]]['CHR_ID'].',';}
			if ($ENTRY['chr_seq_name']!=$tab[1])				{$TO_UPD=true;		$query .= "chr_seq_name = '".$tab[1]."',";		}
			if ($ENTRY['assembly_unit']!=$tab[6])				{$TO_UPD=true;		$query .= "assembly_unit = '".$tab[6]."',";		}
			if ($ENTRY['seq_role']!=$tab[3])					
			{
				/// Specific rule for unlocalized-scaffold
				if (!($tab[3]=='unlocalised-scaffold' && $ENTRY['seq_role']=='unlocalized-scaffold'))
				{
					$TO_UPD=true;		$query .= "seq_role = '".$tab[3]."',";		
				}
			}
			
			/// We add the different names to $ENS_INFO['CHR_SEQ_IDS'] so we can easily search for it when processing genes
			$ENS_INFO['CHR_SEQ_IDS'][$tab[1]]=$ENTRY['chr_seq_id'];
			$ENS_INFO['CHR_SEQ_IDS'][implode(".",$GENBANK)]=$ENTRY['chr_seq_id'];
			
			$ENS_INFO['CHR_SEQ_IDS_TO_GENBANK'][$tab[1]]=array($tab[4],$GENBANK[0]);
			$ENS_INFO['CHR_SEQ_IDS_TO_GENBANK'][$ENTRY['chr_seq_id']]=array($tab[4],$GENBANK[0]);
			
			/// Nothing to do - we skip
			if ($IS_NEW && $TO_UPD){continue;}
			if (!$TO_UPD)continue;
			
			$query=substr($query,0,-1).' WHERE chr_seq_id = '.$ENTRY['chr_seq_id'];
			echo $query."\n";
			if (!runQueryNoRes($query))																						failProcess($JOB_ID."C05",'Fail to update  chromosome sequence'."\n".$query);
		}

	}
	fclose($fp);
	echo "END\n";


	//exit;
	//print_r($ENS_INFO['CHR_SEQ_IDS']);



	addLog("Processing Ensembl Chromosome Sequence for ".$TAX_ID.' '.$BUILD_DIR.$TAX_ID.'_gene.gff3')	;
	if (!checkFileExist($BUILD_DIR.$TAX_ID.'_gene.gff3'))																	failProcess($JOB_ID."C06",'Unable to find '.$TAX_ID.'_gene.gff');
	$fp=fopen($BUILD_DIR.$TAX_ID.'_gene.gff3','r');if (!$fp)																failProcess($JOB_ID."C07",'Unable to open '.$TAX_ID.'_gene.gff');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100000,"\n");
		if ($line==''||substr($line,0,1)=='#')continue;
		/// chr_info is an array with all the necessary information
		
		$chr_info=explode("\t",$line);
		
		/// We only want chromosome information
		if (!in_array($chr_info[2],array('chromosome')))continue;
		
		/// Column 8 has a lot of metadata information, so we conver that column value into an array of values
		$chr_info[8]=convertGFFLine($chr_info[8]);

		//print_r($chr_info);

		/// Now, based on the names, we find the chromosome sequence id
		$CHR_SEQ_ID=null;
		if (isset($ENS_INFO['CHR_SEQ_IDS'][$chr_info[0]]))$CHR_SEQ_ID=$ENS_INFO['CHR_SEQ_IDS'][$chr_info[0]];
		else 
		{
			$tab=explode(",",$chr_info[8]['Alias']);
			foreach ($tab as $alias)
			{
				if (isset($ENS_INFO['CHR_SEQ_IDS'][$alias]))$CHR_SEQ_ID=$ENS_INFO['CHR_SEQ_IDS'][$alias];
			}
		}
		
		if ($CHR_SEQ_ID==null)																									failProcess($JOB_ID."C08",'Unable to find chromosome sequence:: '.$chr_info[0]."\n");
		
		//echo $CHR_SEQ_ID."\n";
		if (!isset($ENS_INFO['CHR_SEQ_IDS_TO_GENBANK'][$CHR_SEQ_ID]))															failProcess($JOB_ID."C09",'Unable to find mapping to genbank for: '.$chr_info[0]."\n");
		$GENBANK_INFO=$ENS_INFO['CHR_SEQ_IDS_TO_GENBANK'][$CHR_SEQ_ID];
		
		if (!isset($GENE_SEQS[$GENBANK_INFO[0]]['CHR_SEQ'][$GENBANK_INFO[1]]))													failProcess($JOB_ID."C10",'Unable to find chromosome seq from genbank : '.$chr_info[0]."\n");
		
		/// Getting the corresponding record in the DB:
		$CHR_SEQ_ENTRY=&$GENE_SEQS[$GENBANK_INFO[0]]['CHR_SEQ'][$GENBANK_INFO[1]];

		/// We check if start and end pos have changed and update accordingly
		$STR='';
		if ((isset($CHR_SEQ_ENTRY['chr_start_pos']) &&$CHR_SEQ_ENTRY['chr_start_pos']!=$chr_info[3])
		||(!isset($CHR_SEQ_ENTRY['chr_start_pos'])))
		$STR.=' chr_start_pos = '.$chr_info[3].',';


		if ((isset($CHR_SEQ_ENTRY['chr_end_pos']) &&$CHR_SEQ_ENTRY['chr_end_pos']!=$chr_info[4])
		||(!isset($CHR_SEQ_ENTRY['chr_end_pos'])))$STR.=' chr_end_pos = '.$chr_info[4].',';
		
		if ($STR!='')
		{
			$query='UPDATE chr_seq SET '.substr($STR,0,-1).' WHERE chr_seq_id='.$CHR_SEQ_ENTRY['chr_seq_id'];
			if (!runQueryNoRes($query))																							failProcess($JOB_ID."C11",'Unable to update chromosome sequence range');
		}
	}
	fclose($fp);
}



function processRefSeqChromosomeSeq(&$TAX_ID,&$ASSEMBLY,&$RS_INFO,&$GENE_SEQS,&$ASSEMBLY_INFO)
{
	global $W_DIR;
	global $DBIDS;
	global $JOB_ID;
	

	addLog("\tProcessing RefSeq Chromosome Assembly for ".$ASSEMBLY)	;
	$BUILD_DIR=$W_DIR.'/'.$TAX_ID.'/'.$TAX_ID.'__'.$RS_INFO['Assembly_Acc'].'__'.$RS_INFO['Assembly_name'].'__REFSEQ/';
	
	if (!checkFileExist($BUILD_DIR.$TAX_ID.'_assembly.txt'))												failProcess($JOB_ID."D01",'Unable to find '.$TAX_ID.'_assembly.txt at '.$BUILD_DIR.$TAX_ID.'_assembly.txt');
	
	$fp=fopen($BUILD_DIR.$TAX_ID.'_assembly.txt','r');if(!$fp)												failProcess($JOB_ID."D02",'Unable to open '.$TAX_ID.'_assembly.txt');
	///header line
	$line=stream_get_line($fp,10000,"\n");
	while(!feof($fp))
	{
		/// Now we process each chromosome sequence
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		if (substr($line,0,1)=='#')continue;
		$tab=explode("\t",$line);
		if ($tab[2]=='na')$tab[2]='Un';
		
		/// Obviously we expect the chromosome to exist from processing NCBI Gene 
		if (!isset($GENE_SEQS[$tab[2]]))																	failProcess($JOB_ID."D03",'Chromosome '.$tab[2].' doesn\'t exist for tax id'.$TAX_ID);
		$CHR_SEQS=&$GENE_SEQS[$tab[2]];
		/// Get REFSEQ name
		$REFSEQ=array('N/A',0);
		if (strpos($tab[6],'.')!==false)$REFSEQ=explode(".",$tab[6]);
		
		///Get GENBANK Name
		$GENBANK=array('N/A',0);
		if ($tab[4]!='na')			$GENBANK=explode(".",$tab[4]);

		/// Genbank name doesn't exist -> the chromosome sequence is new -> insertion
		if (!isset($CHR_SEQS['CHR_SEQ'][$GENBANK[0]]))
		{
			
			$DBIDS['chr_seq']++;
			$query='INSERT INTO chr_seq (chr_seq_id,refseq_name,refseq_version,genbank_name,genbank_version,chr_id,chr_seq_name,seq_role,assembly_unit,genome_assembly_id) VALUES ('.
			$DBIDS['chr_seq'].",'".
			$REFSEQ[0]."',".
			$REFSEQ[1].",'".
			$GENBANK[0]."',".
			$GENBANK[1].",".
			$GENE_SEQS[$tab[2]]['CHR_ID'].",'".
			$tab[0]."','".
			$tab[1]."','".
			$tab[7]."',".$ASSEMBLY_INFO['DBID'].")";
			echo $query."\n";
			if (!runQueryNoRes($query))																			failProcess($JOB_ID."D04",'Fail to insert new chromosome sequence');
			$CHR_SEQS['CHR_SEQ'][$GENBANK[0]]=array('refseq_name'=>$REFSEQ[0],
			'refseq_version'=>$REFSEQ[1],
			'genbank_name'=>$GENBANK[0],
			'genbank_version'=>$GENBANK[1],
			'chr_id'=>$GENE_SEQS[$tab[2]]['CHR_ID'],
			'chr_seq_name'=>$tab[0],
			'seq_role'=>$tab[1],
			'assembly_unit'=>$tab[7],
			'chr_seq_id'=>$DBIDS['chr_seq'],'DB_STATUS'=>'INSERTED');
			$RS_INFO['CHR_SEQ_IDS'][$tab[0]]=$DBIDS['chr_seq'];
			$RS_INFO['CHR_SEQ_IDS'][implode(".",$GENBANK)]=$DBIDS['chr_seq'];
			//$ENS_INFO['CHR_SEQ_IDS_TO_GENBANK'][$DBIDS['chr_seq']]=array($tab[2],$GENBANK[0]);
		}
		else 
		{
			/// If it exist, we check if anything has changed and update accordingly
			$ENTRY=&$CHR_SEQS['CHR_SEQ'][$GENBANK[0]];
			$ENTRY['DB_STATUS']='VALID';
			$RS_INFO['CHR_SEQ_IDS'][$tab[0]]=$ENTRY['chr_seq_id'];
			$RS_INFO['CHR_SEQ_IDS'][implode(".",$GENBANK)]=$ENTRY['chr_seq_id'];
			//$ENS_INFO['CHR_SEQ_IDS_TO_GENBANK'][$ENTRY['chr_seq_id']]=array($tab[2],$GENBANK[0]);
			
			$TO_UPD=false;
			$query='UPDATE chr_seq SET ';
			if ($ENTRY['refseq_name']!=$REFSEQ[0])	 			{$TO_UPD=true;		$query .= "refseq_name = '".$REFSEQ[0]."',";		}
			if ($ENTRY['refseq_version']!=$REFSEQ[1])			{$TO_UPD=true;		$query .= "refseq_version = ".$REFSEQ[1].",";		}

			if ($ENTRY['genbank_name']!=$GENBANK[0])			{$TO_UPD=true;		$query .= "genbank_name = '".$GENBANK[0]."',";		}
			if ($ENTRY['genbank_version']!=$GENBANK[1])			{$TO_UPD=true;		$query .= "genbank_version = ".$GENBANK[1].",";		}
			if ($ENTRY['chr_id']!=$GENE_SEQS[$tab[2]]['CHR_ID']){$TO_UPD=true;		$query .= "chr_id = ".$GENE_SEQS[$tab[2]]['CHR_ID'].',';}
			if ($ENTRY['chr_seq_name']!=$tab[0])				{
				if (strpos($tab[0],'chr')!==false )$tab[0]=substr($tab[0],3);
				$TO_UPD=true;		$query .= "chr_seq_name = '".$tab[0]."',";		
			}
			if ($ENTRY['seq_role']!=$tab[1])					{$TO_UPD=true;		$query .= "seq_role = '".$tab[1]."',";		}
			if ($ENTRY['assembly_unit']!=$tab[7])				{$TO_UPD=true;		$query .= "assembly_unit = '".$tab[7]."',";		}
			if (!$TO_UPD)continue;
			
			$query=substr($query,0,-1).' WHERE chr_seq_id = '.$ENTRY['chr_seq_id'];
			
			echo $query."\n";
			
			if (!runQueryNoRes($query))																		failProcess($JOB_ID."D05",'Fail to update  chromosome sequence'."\n".$query);
		}

	}
	fclose($fp);
}


/// For a given taxon, process gff files to get gene sequence and process them
function processGeneSequence(&$TAX_ID,&$ASSEMBLY,&$ASSEMBLY_INFO)
{
	global $W_DIR;
	global $GENE_PRD_DIR;
	global $FILES;
	global $BIOTYPES;
	global $DBIDS;
	global $COL_ORDER;
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	
	$T_DIR=$W_DIR.'/'.$TAX_ID;
	if (!is_dir($T_DIR))																						failProcess($JOB_ID."E01",'Unable to find '.$TAX_ID);
	if (!chdir($T_DIR))																							failProcess($JOB_ID."E02",'Unable to access '.$TAX_ID);
	

	$ENS_INFO=null;
	$RS_INFO=null;
	foreach ($ASSEMBLY_INFO['SOURCES'] as &$SOURCE_INFO)
	{
		if ($SOURCE_INFO['Source']=='ENSEMBL')$ENS_INFO=&$SOURCE_INFO;
		else if ($SOURCE_INFO['Source']=='REFSEQ')$RS_INFO=&$SOURCE_INFO;
	}


	$NEW_GENE_SEQ=false;

	/// First we get all chromosome sequences from the database
	$CHR_SEQ=array();
	$res=runQuery("SELECT chr_seq_id , refseq_name  , refseq_version,chr_seq_name 
					FROM chr_seq cs
					WHERE genome_assembly_id = ".$ASSEMBLY_INFO['DBID']);
					if ($res===false)																			failProcess($JOB_ID."E03",'Unable to get chromosome sequence for taxon '.$TAX_ID);
	foreach ($res as $l)
	{
		$CHR_SEQ[$l['refseq_name'].(($l['refseq_version']!='')?'.'.$l['refseq_version']:'')]=$l['chr_seq_id'];
		$CHR_SEQ[$l['chr_seq_name']]=$l['chr_seq_id'];
	}
	
	///  we get all genes from the database
	$GENE_IDS=array();
	$res=runQuery("SELECT DISTINCT gene_id, gn_entry_Id FROM MV_GENE WHERE tax_Id = '".$TAX_ID."'");
	if ($res===false)																							failProcess($JOB_ID."E04",'Unable to get genes for taxon '.$TAX_ID);
	foreach ($res as $l)$GENE_IDS[$l['gene_id']]=$l['gn_entry_id'];
	

	///  we get all gene sequences from the database
	$GENE_SEQS=array();
	$res=runQuery("select gene_seq_id, gene_seq_name, gene_seq_version,strand,gs.start_pos,gs.end_pos,
			biotype_id, gs.chr_seq_id, gs.gn_entry_id, cs.chr_seq_name, refseq_name, 
			refseq_version, genbank_name,genbank_version, g.gene_id 
			FROM chr_seq cs, gene_seq gs 
			LEFT JOIN gn_entry g on g.gn_entry_id = gs.gn_entry_id 
			where gs.chr_seq_id = cs.chr_seq_id AND cs.chr_seq_id IN (".implode(",",array_unique($CHR_SEQ)).")");
	$STR='';
	if ($res===false)																							failProcess($JOB_ID."E05",'Unable to get gene sequences for taxon '.$TAX_ID);
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$GENE_SEQS[$line['gene_seq_name']]=$line;
	// echo "T\t".$line['gene_seq_name']."\t".$TAX_ID."\t".$line['gene_seq_id']."\n";
		$STR.=$line['gene_seq_id'].',';
	}
	
	

	/// We check if RefSeq is considered
	if ($RS_INFO!=NULL)
	{
		$NEW=processRefSeqGenes($TAX_ID,$RS_INFO,$GENE_PRD_DIR,$GENE_IDS,$GENE_SEQS,$CHR_SEQ);
		if ($NEW)$NEW_GENE_SEQ=true;
	}

	/// We check if Ensembl is considered and process them
	if ($ENS_INFO!=null)
	{
		$NEW=processEnsemblGenes($TAX_ID,$ENS_INFO,$GENE_PRD_DIR,$GENE_IDS,$GENE_SEQS);
		if ($NEW)$NEW_GENE_SEQ=true;
	}
	
	/// All Gene Seqs that haven't been processed (i.e. DB_STATUS=FROM_DB) should be deleted 
	$query='DELETE FROM gene_seq WHERE gene_seq_id IN (';
	$TO_DEL=false;
	foreach ($GENE_SEQS as $GS)
	{
		if ($GS['DB_STATUS']=='VALID')continue;
		$TO_DEL=true;
		$query.=$GS['gene_seq_id'].',';
	}
	if ($TO_DEL)
	{
		echo "DEL\n";
		echo $query;
		if(!runQueryNoRes(substr($query,0,-1).')'))																failProcess($JOB_ID."E06",'Unable to delete gene seq');
	}

		/// Then we insert
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
	
		addLog("inserting ".$NAME." records");
		$res=array();
		fclose($FILES[$NAME]);
		if ($NAME=='gene_seq' && !$NEW_GENE_SEQ)continue;
		
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$W_DIR.'/INSERT/'.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		print_r($res);
		if ($return_code !=0 )																					failProcess($JOB_ID."E07",'Unable to insert '.$NAME); 
	}
		
		
	$FILES=array();
	foreach ($COL_ORDER as $TYPE=>$CTL)
	{
		$FILES[$TYPE]=fopen($W_DIR.'/INSERT/'.$TYPE.'.csv','w');
		if (!$FILES[$TYPE])																						failProcess($JOB_ID."E08",'Unable to open '.$TYPE.'.csv');
	}
}


function processEnsemblGenes(&$TAX_ID,&$ENS_INFO,&$GENE_PRD_DIR,&$GENE_IDS,&$GENE_SEQS)
{
	global $BIOTYPES;
	global $W_DIR;
	global $DBIDS;
	global $JOB_ID;
	global $FILES;
	
	/// First we create the build path based on ensembl release and assembly:
	$BUILD_DIR=$W_DIR.'/'.$TAX_ID.'/'.$TAX_ID.'__'.$ENS_INFO['Assembly_Acc'].'__'.$ENS_INFO['Assembly_name'].'__ENSEMBL/';
		
	/// We need to get the mapping between Ensembl and NCBI Gene from NCBI Gene:
	$ENS_GENE_MAP=array();
	$fp=fopen($GENE_PRD_DIR.'/gene2ensembl','r');if (!$fp)																		failProcess($JOB_ID."F01",'Unable to open gene2ensembl');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		$tab=explode("\t",$line);
		if ($tab[0]!=$TAX_ID)continue;
		$ENS_GENE_MAP[$tab[2]]=$tab[1];
	}
	fclose($fp);
	echo count($ENS_GENE_MAP).' Ensembl - NCBI Gene Mapping|'.count($GENE_IDS).' Gene IDs'."\n";

	


	addLog("Processing Ensembl Gene Sequence for ".$TAX_ID.' '.$BUILD_DIR.$TAX_ID.'_gene.gff3')	;



	if (!checkFileExist($BUILD_DIR.$TAX_ID.'_gene.gff3'))																	failProcess($JOB_ID."F02",'Unable to find '.$TAX_ID.'_gene.gff');
	$fp=fopen($BUILD_DIR.$TAX_ID.'_gene.gff3','r');if (!$fp)																failProcess($JOB_ID."F03",'Unable to open '.$TAX_ID.'_gene.gff');
	
	
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100000,"\n");
		if ($line==''||substr($line,0,1)=='#')continue;
		/// gene_info is an array with all the necessary information
		
		$gene_info=explode("\t",$line);
		
		/// We only want gene and pseudogene and ncRNA_gene
		if (!in_array($gene_info[2],array('gene','pseudogene','ncRNA_gene')))continue;
		
		/// Column 8 has a lot of metadata information, so we conver that column value into an array of values
		$gene_info[8]=convertGFFLine($gene_info[8]);
		
		///Process entry
		$NAME=substr($gene_info[8]['ID'],5);
		
		$VERSION=$gene_info[8]['version'];
		//print_r($gene_info);
		$GENE_SEQ_ID=-1;
		$GN_ENTRY_ID='NULL';
		$GENE_ID='NULL';


		///Find the gene
		if (isset($ENS_GENE_MAP[$NAME]) && isset($GENE_IDS[$ENS_GENE_MAP[$NAME]]))
		{
		
			$GENE_ID=$ENS_GENE_MAP[$NAME];
			
		}
		else if (isset($ENS_GENE_MAP[$NAME.'.'.$VERSION]))$GENE_ID=$ENS_GENE_MAP[$NAME.'.'.$VERSION];
		
		/// If we don't find NCBI gene ID from the mapping file,
		/// we will be trying to use HGNC, MGI, RGD, SGD, BGD, CGNC, FLYBASE, miRBase, VGNC Identifiers:
		if ($GENE_ID=='NULL' && isset($gene_info[8]['description']))
		{
			
			$match=array();
			preg_match('/((HGNC|MGI|RGD|SGD|BGD|CGNC|FLYBASE|miRBase|VGNC):[0-9]{1,10})/',$gene_info[8]['description'],$match); 
			if ($match!=array())
			{
				
				$resHGNC=runQuery("SELECT gene_id 
					FROM gn_entry ge, gn_syn_map gsm,gn_syn gs 
					where ge.gn_entry_id = gsm.gn_entry_id 
					AND gsm.gn_syn_Id = gs.gn_syn_id 
					AND syn_value = '".$match[0]."'");
				if ($resHGNC===false)																			failProcess($JOB_ID."F04",'Unable to get gene id from HGNC');
				if ($resHGNC!=array())
				{
					echo "MATCHING VIA 3RD PARTY\t".$match[0]."\t".$resHGNC[0]['gene_id']."\n";
					$GENE_ID=$resHGNC[0]['gene_id'];
				}
			}
			
		}
		
		if ($GENE_ID=='NULL')	addLog($gene_info[8]['ID']."\t".'NO GENE ID FOUND');
		else 
		{
			/// The Gene ID is in the list of current genes for the taxon -> Great!
			if (isset($GENE_IDS[$GENE_ID]))$GN_ENTRY_ID=$GENE_IDS[$GENE_ID];
			/// Otherwise it's an obsolete gene ID and we need to find the new one:
			else
			{
				$res=runQuery("SELECT gn_entry_id FROM gn_history where gene_id=".$GENE_ID);
				if ($res===false)																		failProcess($JOB_ID."F05",'Unable to get gene entry id');
				if ($res!==false && count($res)==1)$GN_ENTRY_ID=$res[0]['gn_entry_id'];
			}
		
			if ($GN_ENTRY_ID=='NULL')
			{
				addLog($gene_info[8]['ID']."\t".'NO GN_ENTRY_ID ID FOUND|GENEID:'.$GENE_ID);
				//print_r($gene_info[8]);exit;
			}
		}

		/// Ensembl has a different way to name chromosomes, so we need to try different things to match
		$IS_NEW=false;
		$CHR_SEQ_ID=NULL;
		/// Trying the name by default
		if (!isset($ENS_INFO['CHR_SEQ_IDS'][$gene_info[0]]))
		{
			//print_r($ENS_INFO['CHR_SEQ_IDS']);
			if (substr($gene_info[0],0,4)=='CHR_')
			{
				/// Or by removing the prefix CHR_
				if (!isset($ENS_INFO['CHR_SEQ_IDS'][substr($gene_info[0],4)]))
				{
					echo "MISSING\t".($gene_info[0])."\n";
					continue;
				}else $CHR_SEQ_ID=$ENS_INFO['CHR_SEQ_IDS'][substr($gene_info[0],4)];

				/// Of by using RefSeq info if available
			}
			else if ($gene_info[0]=='MT')
			{
				//echo "IN\n";
				if (isset($TAX_INFO['RS']['CHR_SEQ_IDS']['MT']))
				{
					$CHR_SEQ_ID=$TAX_INFO['RS']['CHR_SEQ_IDS']['MT'];
				}
				else 
				{
					echo "MISSING\t".($gene_info[0])."\n";
					continue;
				}
				
			}
			else 
			{
				echo "MISSING\t".($gene_info[0])."\n";
				continue;
			}
			
			
		}
		else $CHR_SEQ_ID=$ENS_INFO['CHR_SEQ_IDS'][$gene_info[0]];
		

		/// Then we look at the biotype
		$BT_ID='NULL';
		if (!isset($BIOTYPES[$gene_info[8]['biotype']]))
		{
			//print_r($gene_info);
			echo "MISSING GENE BIOTYPE\t".$gene_info[8]['biotype']."\n";continue;
		}

		/// Now we check if it exist already in the db or not
		if (!isset($GENE_SEQS[$NAME]))
		{
			/// No -> we create it
			$IS_NEW=true;
			
			$DBIDS['gene_seq']++;
			$GENE_SEQ_ID=$DBIDS['gene_seq'];
			$GENE_SEQS[$NAME]=array('DB_STATUS'=>'VALID','gene_seq_id'=>$DBIDS['gene_seq']);
			//
			if (!isset($gene_info[3]))
			{

				echo "NO STARTING POSITION FOR ".$NAME."\n";
				print_r($gene_info);
				continue;
			}
			
			fputs($FILES['gene_seq'],
				$DBIDS['gene_seq']
				."\t".$NAME
				."\t".$VERSION
				."\t".$gene_info[6]
				."\t".$gene_info[3]
				."\t".$gene_info[4]
				."\t".$BIOTYPES[$gene_info[8]['biotype']]
				."\t".$CHR_SEQ_ID
				."\t".$GN_ENTRY_ID."\n");
			$NEW_GENE_SEQ=true;
		}
		else
		{
			/// Yes -> we update it
			$ENTRY=&$GENE_SEQS[$NAME];
			$GENE_SEQ_ID=$ENTRY['gene_seq_id'];
			$ENTRY['DB_STATUS']='VALID';
			$query='UPDATE gene_seq SET ';$TO_UPD=false;
			if ($ENTRY['gn_entry_id'] =='')				$ENTRY['gn_entry_id']='NULL';
			if ($ENTRY['strand']	 !=$gene_info[6])						{$TO_UPD=true; $query.=" strand = '".$gene_info[6]."',";}
			if ($ENTRY['start_pos']	 !=$gene_info[3])						{$TO_UPD=true; $query.=" start_pos = ".$gene_info[3].",";}
			if ($ENTRY['end_pos']	 !=$gene_info[4])						{$TO_UPD=true; $query.=" end_pos = ".$gene_info[4].",";}
			if ($ENTRY['biotype_id'] !=$BIOTYPES[$gene_info[8]['biotype']]) {$TO_UPD=true; $query.=" biotype_id = ".$BIOTYPES[$gene_info[8]['biotype']].",";}
			if ($ENTRY['chr_seq_id'] !=$CHR_SEQ_ID)							{$TO_UPD=true; $query.=" chr_seq_id = ".$CHR_SEQ_ID.",";}
			if ($ENTRY['gn_entry_id']!=$GN_ENTRY_ID)						{$TO_UPD=true; $query.=" gn_entry_id = ".$GN_ENTRY_ID.",";}
			if ($TO_UPD)
			{
				$query=substr($query,0,-1).' WHERE gene_seq_id = '.$ENTRY['gene_seq_id'];
				echo $query."\n";
				if (!runQueryNoRes($query))														failProcess($JOB_ID."F06",'Unable to update gene seq');
			}
		}

		
		
		
		
	}
	fclose($fp);
	return $NEW_GENE_SEQ;
}

function processRefSeqGenes(&$TAX_ID,&$RS_INFO,&$GENE_PRD_DIR,&$GENE_IDS,&$GENE_SEQS,&$CHR_SEQ)
{

	global $BIOTYPES;
	global $W_DIR;
	global $DBIDS;
	global $JOB_ID;
	global $FILES;

	addLog("Processing RefSeq Gene Sequence for ".$TAX_ID)	;

	/// First we create the build path based on RefSeq release and assembly:
	$BUILD_DIR=$W_DIR.'/'.$TAX_ID.'/'.$TAX_ID.'__'.$RS_INFO['Assembly_Acc'].'__'.$RS_INFO['Assembly_name'].'__REFSEQ/';
		
	if (!checkFileExist($BUILD_DIR.$TAX_ID.'_gene.gff'))											failProcess($JOB_ID."G01",'Unable to find '.$TAX_ID.'_gene.gff');
			
			
	/// Open and process refseq gff file
	$fp=fopen($BUILD_DIR.$TAX_ID.'_gene.gff','r');if (!$fp)											failProcess($JOB_ID."G02",'Unable to open '.$TAX_ID.'_gene.gff');
	while(!feof($fp))
	{

		$line=stream_get_line($fp,100000,"\n");
		if ($line==''||substr($line,0,1)=='#')continue;
		
		/// gene_info is an array with all the necessary information
		$gene_info=explode("\t",$line);
		/// We only want gene and pseudogene
		if ($gene_info[2]!='gene'&&$gene_info[2]!='pseudogene')continue;
		
		/// Column 8 has a lot of metadata information, so we conver that column value into an array of values
		$gene_info[8]=convertGFFLine($gene_info[8]);

		
		
		/// Process record.
		$NAME=substr($gene_info[8]['ID'],5);
		echo $NAME."\t";
		//print_r($gene_info);
		$GENE_SEQ_ID=-1;
		$GN_ENTRY_ID='NULL';
		$IS_NEW=false;


		/// Getting gene ID
		if ((isset($gene_info[8]['Dbxref']['GeneID']) && isset($GENE_IDS[$gene_info[8]['Dbxref']['GeneID']])))
		{
			echo "FROM SOURCE\n";
			$GN_ENTRY_ID=$GENE_IDS[$gene_info[8]['Dbxref']['GeneID']];
		}
		/// We don't find the gene but it's a LOC gene: we try to find the corresponding gene
		if ($GN_ENTRY_ID=='NULL' && substr($NAME,0,3)=='LOC')
		{
			
			$p_pos=strpos($NAME,'-');
			

			if ($p_pos!==false)
			{
				$loc_id=substr($NAME,3,$p_pos-3);
				echo $NAME."\t".$p_pos."\t".$loc_id."\n";
			}
			else $loc_id=substr($NAME,3);
			echo "\tLOC CHECK\t".$NAME."\t".$loc_id."\t";
			
			$res=runQuery("SELECT * FROM gn_history gh where gh.gene_id = ".$loc_id);
			if ($res===false)																							failProcess($JOB_ID."G03",'Unable to get gene id from LOC');
			if (count($res)==1 && $res[0]['gn_entry_id']!='')
			{
				echo implode("\t",$res[0]); 
				$GN_ENTRY_ID=$res[0]['gn_entry_id'];

			}
			echo "\n";

		}
		echo "\t".$NAME."\t".$gene_info[8]['Dbxref']['GeneID']."\t".$GN_ENTRY_ID."\n";
		/// Checking if that gene sequence is already in the database
		if (!isset($GENE_SEQS[$NAME]))
		{
			$IS_NEW=true;
			
			$DBIDS['gene_seq']++;
			$GENE_SEQ_ID=$DBIDS['gene_seq'];
			$GENE_SEQS[$NAME]=array('DB_STATUS'=>'VALID','gene_seq_id'=>$DBIDS['gene_seq']);
			fputs($FILES['gene_seq'],
				$DBIDS['gene_seq']."\t".
				$NAME."\tNULL\t".
				$gene_info[6]."\t".
				$gene_info[3]."\t".
				$gene_info[4]."\t".
				$BIOTYPES[$gene_info[8]['gene_biotype']]."\t".
				$CHR_SEQ[$gene_info[0]]."\t".
				$GN_ENTRY_ID."\n");
			$NEW_GENE_SEQ=true;
		}
		else
		{
			/// If so, we compare and update if necessary
			$ENTRY=&$GENE_SEQS[$NAME];
			$GENE_SEQ_ID=$ENTRY['gene_seq_id'];
			$ENTRY['DB_STATUS']='VALID';
			$query='UPDATE gene_seq SET ';$TO_UPD=false;
			if ($ENTRY['gn_entry_id']=='')$ENTRY['gn_entry_id']='NULL';
			if ($ENTRY['strand']		!=$gene_info[6])							{$TO_UPD=true; $query.=" strand = '".$gene_info[6]."',";}
			if ($ENTRY['start_pos']		!=$gene_info[3])							{$TO_UPD=true; $query.=" start_pos = ".$gene_info[3].",";}
			if ($ENTRY['end_pos']		!=$gene_info[4])							{$TO_UPD=true; $query.=" end_pos = ".$gene_info[4].",";}
			if ($ENTRY['biotype_id']	!=$BIOTYPES[$gene_info[8]['gene_biotype']])	{$TO_UPD=true; $query.=" biotype_id = ".$BIOTYPES[$gene_info[8]['gene_biotype']].",";}
			if ($ENTRY['chr_seq_id']	!=$CHR_SEQ[$gene_info[0]])					{$TO_UPD=true; $query.=" chr_seq_id = ".$CHR_SEQ[$gene_info[0]].",";}
			if ($ENTRY['gn_entry_id']	!=$GN_ENTRY_ID)								{$TO_UPD=true; $query.=" gn_entry_id = ".$GN_ENTRY_ID.",";}
			if ($TO_UPD){
			$query=substr($query,0,-1).' WHERE gene_seq_id = '.$ENTRY['gene_seq_id'];
			echo "\t".$query."\n";
			if (!runQueryNoRes($query))																								failProcess($JOB_ID."G04",'Unable to update gene seq');
			}
		}

	}///END FEOF
	return $NEW_GENE_SEQ;
}


function cleanupGenomeAssembly(&$TO_DEL)
{
	$res=runQuery("SELECT chr_seq_id 
		FROM chr_seq 
		where genome_assembly_id IN ( ".implode(',',$TO_DEL['GENOMIC_ASSEMBLY']).')');
	if ($res===false)																									failProcess($JOB_ID."H01",'Unable to get chr_seq_id');
	foreach ($res as $line)$TO_DEL['CHR_SEQ'][]=$line['chr_seq_id'];	

	$res=runQuery("SELECT gene_seq_id 
		FROM  gene_seq gs, chr_seq cs 
		where gs.chr_seq_id = cs.chr_seq_id 
		AND genome_assembly_id IN ( ".implode(',',$TO_DEL['GENOMIC_ASSEMBLY']).')');
	if ($res===false)																								failProcess($JOB_ID."H02",'Unable to get gene_seq_id');
	foreach ($res as $line)$TO_DEL['GENE_SEQ'][]=$line['gene_seq_id'];	



	$res=runQuery("SELECT transcript_id 
	FROM  transcript t, gene_seq gs, chr_seq cs 
	where t.gene_seq_id = gs.gene_seq_id 
	AND  gs.chr_seq_id = cs.chr_seq_id 
	AND genome_assembly_id IN ( ".implode(',',$TO_DEL['GENOMIC_ASSEMBLY']).')');
	if ($res===false)																							failProcess($JOB_ID."H03",'Unable to get transcript_id');
	foreach ($res as $line)$TO_DEL['TRANSCRIPT'][]=$line['transcript_id'];
	


	if (isset($TO_DEL['TRANSCRIPT']))
	{
		addLog(count($TO_DEL['TRANSCRIPT']).' to delete');
		
		$CHUNKS=array_chunk($TO_DEL['TRANSCRIPT'],1000);
		foreach ($CHUNKS as  $N=>$CHUNK)
		{
			addLog($N.'/'.count($CHUNKS));
			$res=runQueryNoRes("DELETE FROM transcript where transcript_id IN (".implode(',',$CHUNK).') ');
			if ($res===false)																										failProcess($JOB_ID."H04",'Unable to delete transcript');	
		}
	}


	if (isset($TO_DEL['GENE_SEQ']))
	{
		addLog(count($TO_DEL['GENE_SEQ']).' to delete');
		$CHUNKS=array_chunk($TO_DEL['GENE_SEQ'],1000);
		foreach ($CHUNKS as $N=>$CHUNK)
		{
			addLog($N.'/'.count($CHUNKS));
			echo "DELETE FROM gene_seq where gene_seq_id IN (".implode(',',$CHUNK).') '."\n";
			$res=runQueryNoRes("DELETE FROM gene_seq where gene_seq_id IN (".implode(',',$CHUNK).') ');
			if ($res===false)																										failProcess($JOB_ID."H05",'Unable to delete gene_seq');	
		}
	}


	/// We do not delete chromosome sequence or genome assembly currently due to the overwhelming amount of data required to delete
		// 		if (isset($TO_DEL['CHR_SEQ'])){
		// 		addLog(count($TO_DEL['CHR_SEQ']).' to delete');
		// 		foreach ($TO_DEL['CHR_SEQ'] as $CHR_SEQ_ID)
		// 		{
		// 			$res=runQuery("selecT count(chr_seq_pos_id) co FROM chr_seq_pos WHERE chr_seq_id = ".$CHR_SEQ_ID);
		// 			$CO=$res[0]['co'];
		// 			do
		// 			{
		// 				echo $CHR_SEQ_ID."\t".$CO."\n";
		// 			$res=runQueryNoRes("DELETE FROM chr_seq_pos where chr_seq_pos_id IN (selecT chr_seq_pos_id FROM chr_seq_pos WHERE chr_seq_id = ".$CHR_SEQ_ID." LIMIT 100)");
		// $CO-=100;
		// 			}while($CO>0);
		// 		}

// 		// echo "DELETE FROM genome_assembly where genome_assembly_id IN (".substr($query,0,-1).') ';
// 		 $res=runQueryNoRes("DELETE FROM genome_assembly where genome_assembly_id IN (".implode(',',$TO_DEL['GENOMIC_ASSEMBLY']).') ');
// 		 if ($res===false)																										failProcess($JOB_ID.'038','Unable to delete genome assembly(ies)');	
}

	?>
	
