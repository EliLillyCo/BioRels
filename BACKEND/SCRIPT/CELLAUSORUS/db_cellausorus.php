<?php

/**
 SCRIPT NAME: db_cellausorus
 PURPOSE:     Process all cellausorus files & push to database
 
*/

/// Name of the job:
$JOB_NAME='db_cellausorus';

/// Get biorels Root directory
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);

/// Load the loader - loading all necessary files
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

/// Get job id & info
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];

addLog("Access working directory");

	/// Get parent job info:
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_cellausorus')];

	/// Go to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];	   if (!is_dir($W_DIR) ||!chdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);

	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
	/// Create INSERT directory
	if (!is_dir('INSERT') && !mkdir('INSERT'))											failProcess($JOB_ID."004",'Unable to create INSERT dir in '.$W_DIR);
	


addLog("Update source");
	//// Publication associated to cell lines can come from various sources.
	//// So here we are going to ge tall cellausorus records
	$source_id=getSource('Cellausorus');


addLog("Verify tissue - anatomy mapping");
	/// Tissue is a free text, so we have a mapping table in static data that allows us
	/// to map cell lines to anatomy. HEre we are going to check that the data is valid
	$res=runQuery("SELECT cell_tissue_id, cell_tissue_name,ct.anatomy_entry_id, anatomy_tag, anatomy_name 
					FROM cell_tissue ct 
					LEFT JOIN anatomy_entry a ON  a.anatomy_entry_Id =ct.anatomy_entry_Id");
					if ($res===false)												failProcess($JOB_ID."005",'Unable to get current tissue names');
	$CELL_TISSUE_MAP=array();
	/// Max value to add new records
	$MAX_CELL_TISSUE_ID=-1;
	foreach ($res as $line)
	{
		$CELL_TISSUE_MAP[$line['cell_tissue_name']]=array($line['anatomy_tag'],$line['anatomy_name'],$line['anatomy_entry_id'],$line['cell_tissue_id'],'FROM_DB');
		$MAX_CELL_TISSUE_ID=max($MAX_CELL_TISSUE_ID,$line['cell_tissue_id']);
	}


	$fp=fopen($TG_DIR.'/BACKEND/STATIC_DATA/CELLAUSORUS/mapping_cell_tissue','r');
	if (!$fp)																			failProcess($JOB_ID."006",'Unable to open mapping file');

	//// Here we read the mapping file and check if the tissues in that mapping file are already in the DB (Via CELL_TISSUE_MAP array)
	//// or if we need to add them
	$query='SELECT anatomy_entry_id, anatomy_tag FROM anatomy_entry where anatomy_tag IN (';
	$NEW_ENTRIES=false;
	while(!feof($fp))
	{
		/// Readline - ignored if empty
		$line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		/// Already in the mapping file - continue
		if (isset($CELL_TISSUE_MAP[$tab[0]]))continue;
		$NEW_ENTRIES=true;

		if (isset($tab[1])){
			$CELL_TISSUE_MAP[$tab[0]]=array($tab[1],$tab[2],-1,-1,'TO_INS');
			$query.= "'".$tab[1]."',";
		}
		else $CELL_TISSUE_MAP[$tab[0]]=array('','',-1,-1,'TO_INS');
	}
	fclose($fp);

	if ($NEW_ENTRIES)
	{
		/// To add them, we are going to get the anatomy_entry_id from anatomy_entry based on the mapping file
		if (substr($query,-1)!='('){
		$query=substr($query,0,-1).')';
		$res=runQuery($query);
		if ($res===false)															failProcess($JOB_ID."007",'Unable to find anatomy entries');
		$MAP=array();
		foreach ($res as $l)$MAP[$l['anatomy_tag']]=$l['anatomy_entry_id'];
		}
		foreach ($CELL_TISSUE_MAP as $T=>&$N)
		{
			if (isset($MAP[$N[0]]))$N[2]=$MAP[$N[0]];
		}
		/// And then insert
		foreach ($CELL_TISSUE_MAP as $TN=>&$INFO)
		{
			if ($INFO[4]!='TO_INS')continue;
			$MAX_CELL_TISSUE_ID++;

			$INFO[3]=$MAX_CELL_TISSUE_ID;
			$query='INSERT INTO cell_Tissue (cell_tissue_Id, cell_tissue_name,anatomy_Entry_id) VALUES (';
			$query.=$MAX_CELL_TISSUE_ID.",'".str_replace("'","''",$TN)."',";
			if ($INFO[2]!=-1)$query.=$INFO[2]; else $query.='NULL';
			if (!runQueryNoRes($query.')'))												 failPRocess($JOB_ID.'008', 'Unable to insert new tissue');
		}
	}





addLog("Get Database id List");


	/// This is an array where keys are the table names and values are the max primary key value
	/// We will use this to assign id to new records
	$DBIDS=array('cell_entry'=>-1,
	'cell_pmid_map'=>-1,
	'patent_entry'=>-1,
	'cell_patent_map'=>-1,
	'cell_taxon_map'=>-1,
	'cell_disease'=>-1,
	'cell_syn'=>-1,
	);


	/// This is an array where keys are the table names and values are boolean
	/// We will use this to know if we need to insert new records and call psql COPY function
	$NEW_RECORD=array('cell_entry'=>false,
	'cell_pmid_map'=>false,
	'patent_entry'=>false,
	'cell_patent_map'=>false,
	'cell_taxon_map'=>false,
	'cell_disease'=>false,
	'cell_syn'=>false,
	);


	/// Getting the max primary key values for each table to easily insert new records
	foreach ($DBIDS as $TBL=>&$POS)
	{
		///Exceptions to the rules:
		if ($TBL=='cell_pmid_map')$query='SELECT MAX(cell_pmid_id) co FROM '.$TBL;
		else if ($TBL=='cell_taxon_map')$query='SELECT MAX(cell_taxon_id) co FROM '.$TBL;
		
		else $query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."009",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}


	/// This is the order in which tables MUST be inserted, with the description of the column order for the input files	
	$COL_ORDER=array(
	'cell_entry'=>'(cell_entry_id , cell_acc  ,      cell_name      ,  cell_type, cell_donor_sex ,  cell_donor_age  ,cell_tissue_id, cell_version ,    date_updated)',
	'patent_entry'=>'(patent_entry_id,patent_application)',
	'cell_patent_map'=>'(cell_patent_map_id , cell_entry_id , patent_entry_id , source_id )',
	'cell_pmid_map'=>'( cell_pmid_id , cell_entry_id , pmid_entry_id , source_id)',
	'cell_taxon_map'=>'(cell_taxon_id , taxon_id,cell_entry_id  , source_id )',
	'cell_disease'=>'(cell_disease_id ,cell_entry_id,disease_entry_id,source_id)',
	'cell_syn'=>'(cell_syn_id, cell_syn_name,cell_entry_id,source_id)'
   );
   
   /// We open files that will contain the new records
   $FILES=array();
   foreach ($COL_ORDER as $TYPE=>$CTL)
   {
	   $FILES[$TYPE]=fopen($W_DIR.'/INSERT/'.$TYPE.'.csv','w');
	   if (!$FILES[$TYPE])															failProcess($JOB_ID."010",'Unable to open '.$TYPE.'.csv');
   }


addLog("Process cellausorus file");
		
	/* File format:
	 ID         Identifier (cell line name)     Once; starts an entry
 AC         Accession (CVCL_xxxx)           Once
 AS         Secondary accession number(s)   Optional; once /
 SY         Synonyms                        Optional; once /
 DR         Cross-references                Optional; once or more	/
 RX         References identifiers          Optional: once or more	/
 WW         Web pages                       Optional; once or more	/
 CC         Comments                        Optional; once or more	/
 ST         STR profile data                Optional; once or more
 DI         Diseases                        Optional; once or more	/
 OX         Species of origin               Once or more	/
 HI         Hierarchy                       Optional; once or more	
 OI         Originate from same individual  Optional; once or more	/
 SX         Sex of cell                     Optional; once	/
 AG         Age of donor at sampling        Optional; once	/
 CA         Category                        Once	/
 DT         Date (entry history)            Once	/
 //         Terminator                      Once; ends an entry

 CC   Anecdotal
CC   Biotechnology
CC   Breed/subspecies
CC   Caution
CC   Characteristics
CC   Derived from metastatic site
CC   Derived from sampling site
CC   Discontinued
CC   Doubling time
CC   From
CC   Genome ancestry
CC   Group
CC   HLA typing
CC   Karyotypic information
CC   Knockout cell
CC   Microsatellite instability
CC   Miscellaneous
CC   Misspelling
CC   Monoclonal antibody isotype
CC   Monoclonal antibody target
CC   Omics
CC   Part of
CC   Population
CC   Problematic cell line
CC   Registration
CC   Selected for resistance to
CC   Sequence variation
CC   Transfected with
CC   Transformant

____________________________________________________________________________
ID   #132 PC3-1-SC-E8
AC   CVCL_B0T9
SY   Z48-5MG-70
RX   Patent=EP0501779A1;
CC   Group: Patented cell line.
CC   Registration: International Depositary Authority, American Type Culture Collection (ATCC); HB-10564.
CC   Monoclonal antibody isotype: IgG2a.
CC   Monoclonal antibody target: UniProtKB; P47712; Human PLA3G4A.
OX   NCBI_TaxID=10090; ! Mus musculus
HI   CVCL_D145 ! HL-1 Friendly Myeloma-653
CA   Hybridoma
DT   Created: 23-09-21; Last updated: 23-09-21; Version: 1
 */



		
   /// This is a main statistics array
	$STATS=array('ENTRY'=>0,'INSERT_ENTRY'=>0,'UPDATE_ENTRY'=>0,'DELETE_ENTRY'=>0,
	'PUBLI'=>0,'INSERT_PUBLI'=>0,'UPDATE_PUBLI'=>0,'DELETE_PUBLI'=>0,
	'PATENT'=>0,'INSERT_PATENT'=>0,'UPDATE_PATENT'=>0,'DELETE_PATENT'=>0,
	'TAXON'=>0,'INSERT_TAXON'=>0,'UPDATE_TAXON'=>0,'DELETE_TAXON'=>0,
	'DISEASE'=>0,'INSERT_DISEASE'=>0,'UPDATE_DISEASE'=>0,'DELETE_DISEASE'=>0,
	'VALID_SYN'=>0,'VALID_ENTRY'=>0,'VALID_PATENT'=>0,'INSERT_SYN'=>0,'UPDATE_SYN'=>0,'DELETE_SYN'=>0,'VALID_DISEASE'=>0
	);

	$fpM=fopen('MISSING_TISSUE','w');if (!$fpM)		 						failProcess($JOB_ID."011",'Unable to open MISSING_TISSUE');
	$fp=fopen('cellosaurus.txt','r');if (!$fp)		 						failProcess($JOB_ID."012",'Unable to open cellosaurus.txt');
	$N_E=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		/// Each cellausorus record is a block of line,
		/// starting with the ID line
		if (substr($line,0,2)!='ID')continue;
		
		/// We initialize the record
		$ENTRY=array();
		$ENTRY['ID']=substr($line,5);
		++$N_E;
		$STATS['ENTRY']++;

		/// Then we read all lines for that record, until we reach //, the end of the record
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			if ($line=='//')break;
			
			/// The first two letters are the header for each line
			switch (substr($line,0,2))
			{
				/// Accession
				case 'AC':$ENTRY['AC']=substr($line,5);break;

				/// Accession synonyms
				case 'AS':$ENTRY['AC_SYN'][]=substr($line,5);break;

				/// Synonyms
				case 'SY':$ENTRY['SYNONYM'][]=substr($line,5);break;


				/// Comments
				case 'CC':
			
					$pos=strpos(substr($line,5),':');
					$head=substr($line,5,$pos);
					$val=trim(substr($line,$pos+6));
					if (substr($val,-1)=='.')$val=substr($val,0,-1);
					$ENTRY['COMMENTS'][$head][]=$val;
				//	echo $head.'|||'.$val."\n";
					if ($head=='Derived from sampling site')
					{	//echo $line."\n";
						if (isset($CELL_TISSUE_MAP[$val]))$ENTRY['TISSUE']=$CELL_TISSUE_MAP[$val][3];
						else fputs($fpM,$val."\n");
					}
					break;
				case 'WW':$ENTRY['COMMENTS'][]=substr($line,5);break;

				/// Gender:
				case 'SX':
					switch (substr($line,5))
					{
						case 'Female':$ENTRY['SEX']='F';break;
						case 'Male':$ENTRY['SEX']='M';break;
						case 'Mixed sex':$ENTRY['SEX']='X';break;
						case 'Sex ambiguous':$ENTRY['SEX']='A';break;
						case 'Sex unspecified':break;
						
						
					}

				break;
				/// Age:
				case 'AG':$ENTRY['AGE']=substr($line,5);break;
				case 'ST':$ENTRY['ST']=substr($line,5);break;

				// Origin:
				case 'OI':$ENTRY['ORIGIN'][]=substr($line,5);break;

				//Category:
				case 'CA':$ENTRY['CATEGORY']=substr($line,5);break;
				case 'DT':
					$tab=explode(";",substr($line,5));
					foreach ($tab as $K)
					{
						$tab2=explode(":",$K);
						$ENTRY[trim(strtoupper($tab2[0]))]=trim($tab2[1]);
					}
				break;
				case 'DR':$tab=explode(";",substr($line,5));$ENTRY['EXT_DB'][$tab[0]]=trim($tab[1]);break;
				
				case 'DI':$tab=explode(";",substr($line,5));$ENTRY['disease'][$tab[0]][]=trim($tab[1]);$STATS['DISEASE']++;break;
				case 'RX':$tab=explode("=",substr($line,5));$ENTRY[strtoupper(trim($tab[0]))][]=substr(trim($tab[1]),0,-1);break;
				case 'OX':$tab=explode(";",substr($line,5));$tab2=explode("=",$tab[0]);$ENTRY['SPECIES'][]=$tab2[1];$STATS['TAXON']++;break;
				
			};


		}
		/// If the Accession already exists, which should not happen, we fail
		if (isset($BLOCK["'".$ENTRY['AC']."'"])) 										failProcess($JOB_ID."013",'Already existing AC');

		/// We save the record into a stack.
		// Once we reach 1000 records, we process
		$BLOCK["'".$ENTRY['AC']."'"]=$ENTRY;
		if (count($BLOCK)<1000)continue;
		foreach ($NEW_RECORD as $K=>&$V)$V=false;

		/// We process the block
		processBlock($BLOCK);
		

		/// We reset the block
		$BLOCK=array();
	}

	/// We process the last block
	if ($BLOCK!=array())processBlock($BLOCK);

	/// We close the files
	fclose($fp);






	
function preloadData($LIST)
{
	global $NEW_RECORD;
	global $source_id;
	$CELL_ENTRIES=array();

	/// First we load all existing records from the database, starting with the cell entry
	$res=runQuery("SELECT cell_entry_id,
	cell_acc,
	cell_name,
	cell_type,
	cell_donor_sex,
	cell_donor_age,
	cell_version,
	cell_tissue_id,
	date_updated FROM cell_entry where cell_acc IN (".implode(',',$LIST).')');
	if ($res===false)														failProcess($JOB_ID."A01",'Unable to download cell entry');
	$MAPPING=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$CELL_ENTRIES[$line['cell_acc']]['cell_entry']=$line;
		$MAPPING[$line['cell_entry_id']]=$line['cell_acc'];
		
	}
	/// No records, we return an empty array
	if (count($MAPPING)==0)return $CELL_ENTRIES;


	/// Then all publications
	$res=runQuery("SELECT pmid,cell_entry_id,cell_pmid_id 
	FROM cell_pmid_map C, pmid_entry P 
	WHERE P.pmid_entry_id = C.pmid_entry_id 
	AND source_id = ".$source_id.
	' AND cell_entry_id IN ('.implode(',',array_keys($MAPPING)).')');
	if ($res===false)														failProcess($JOB_ID."A02",'Unable to get cell_pmid records '.$query);
	foreach ($res as $line)
	{
		$ACC=$MAPPING[$line['cell_entry_id']];
		$ENTRY=&$CELL_ENTRIES[$ACC];
		$ENTRY['PUBLI'][]=array('pmid'=>$line['pmid'],'cell_pmid_id'=>$line['cell_pmid_id'],'DB_STATUS'=>'FROM_DB');
		
	}


	/// Then all synonyms
	$res=runQuery("SELECT cell_syn_id,cell_entry_id,cell_syn_name 
	FROM cell_syn
	WHERE  source_id = ".$source_id.
	' AND cell_entry_id IN ('.implode(',',array_keys($MAPPING)).')');
	if ($res===false)														failProcess($JOB_ID."A03",'Unable to get cell_syn records '.$query);
	foreach ($res as $line)
	{
		$ACC=$MAPPING[$line['cell_entry_id']];
		$ENTRY=&$CELL_ENTRIES[$ACC];
		$ENTRY['SYN'][]=
		array(
			'syn_name'		=>$line['cell_syn_name'],
			'cell_syn_id'	=>$line['cell_syn_id'],
			'DB_STATUS'		=>'FROM_DB');
		
	}

	/// Then all patents
	$res=runQuery("SELECT cell_patent_map_id,patent_application,cell_entry_id 
	FROM cell_patent_map C, patent_entry P 
	WHERE P.patent_entry_id = C.patent_entry_id
	AND source_id = ".$source_id.
	' AND cell_entry_id IN ('.implode(',',array_keys($MAPPING)).')');
	if ($res===false)														failProcess($JOB_ID."A04",'Unable to get cell_patent records '.$query);
	foreach ($res as $line)
	{
		$ACC=$MAPPING[$line['cell_entry_id']];
		$ENTRY=&$CELL_ENTRIES[$ACC];
		$ENTRY['PATENT'][]=array(
			'patent_application'=>$line['patent_application'],
			'cell_patent_map_id'=>$line['cell_patent_map_id'],
			'DB_STATUS'			=>'FROM_DB');
		
	}


	// Then all organisms
	$res=runQuery("SELECT cell_taxon_id,
	tax_id,
	cell_entry_id 
	FROM cell_taxon_map C, taxon T 
	WHERE T.taxon_id = C.taxon_id 
	AND source_id =".$source_id.
	'AND cell_entry_id IN ('.implode(',',array_keys($MAPPING)).')');
	if ($res===false)													failProcess($JOB_ID."A05",'Unable to get cell taxon map '.$query);
	foreach ($res as $line)
	{
		$ACC=$MAPPING[$line['cell_entry_id']];
		$ENTRY=&$CELL_ENTRIES[$ACC];
		$ENTRY['taxon'][]=array(
			'tax_id'		=>$line['tax_id'],
			'cell_taxon_id'	=>$line['cell_taxon_id'],
			'DB_STATUS'		=>'FROM_DB');
		
	}


	///Then all disease
	$res=runQuery("SELECT cell_disease_id,
	cell_entry_id,
	disease_entry_id
	FROM cell_disease 
	WHERE source_id =".$source_id.
	' AND cell_entry_id IN ('.implode(',',array_keys($MAPPING)).')');
	if ($res===false)												failProcess($JOB_ID."A06",'Unable to get cell disease '.$query);
	foreach ($res as $line)
	{
		$ACC=$MAPPING[$line['cell_entry_id']];
		$ENTRY=&$CELL_ENTRIES[$ACC];
		$line['DB_STATUS']='FROM_DB';
		$ENTRY['disease'][]=$line;
		
	}

	return $CELL_ENTRIES;
}








/// This function will look at all the data provided by the block of records and will
/// query the database to get the ids of the data.
/// For patent, if the record is not in the database, we will add it.
function getIDS(&$BLOCK)
{
	

	global $NEW_RECORD;
	global $DBIDS;
	global $FILES;

	/// First step, we are going to list all diseases, species, publications and patents
	$LIST_DISEASE=array();
	$DATA=array('TAXON'=>array(),'PUBMED'=>array(),'PATENT'=>array());
	foreach ($BLOCK as &$ENTRY)
	{
		/// 2 sources of disease are reported: Orphanet and NCIT.
		/// So we are listing them all.
		if (isset($ENTRY['disease']))
		{
			foreach ($ENTRY['disease'] as $DBN=>$LIST)
			{
			
				foreach ($LIST as $D)
				{
					if ($DBN=='NCIt'){$LIST_DISEASE["('".$D."','ncit')"]=true;;$DATA['MAPD']['ncit'][$D]=-1;$HAS_DISEASE=true;}
					else if ($DBN=='ORDO')
					{
						$tab=explode("_",$D);
						$LIST_DISEASE["('".$tab[1]."','orphanet')"]=true;
						$DATA['MAPD']['orphanet'][$tab[1]]=-1;$HAS_DISEASE=true;
					}
				}
			}
		}
		/// Also listing all reported species
		if (isset($ENTRY['SPECIES']))
		{
			
			foreach ($ENTRY['SPECIES'] as $tax_id)$DATA['TAXON']["'".$tax_id."'"]=-1;
		}
		if (isset($ENTRY['PUBMED']))
		{
			foreach ($ENTRY['PUBMED'] as $pmid)$DATA['PUBMED'][$pmid]=-1;
		}	
		if (isset($ENTRY['PATENT']))
		{
				
			foreach ($ENTRY['PATENT'] as $PATENT_ID)
			{
					$STR_P='';$ST=true;
					for($I=0;$I<strlen($PATENT_ID);++$I)
					{
						$C=substr($PATENT_ID,$I,1);
						if (!is_numeric($C))
						{
							if ($ST==true)$STR_P.=$C;
							else break;
						}
						else 
						{
							if ($ST==true){$ST=false;$STR_P.='-';}
							$STR_P.=$C;
						}
					}
					$DATA['PATENT']["'".$STR_P."'"]=-1;
			}
				
			
		}								
				
	}
	
	/// Once we listed all diseases from this block of records, we query the database to get the disease_entry_id
	if ($LIST_DISEASE!=array())
	{
		$query_disease="SELECT * 
			FROM disease_extdb D,source S 
			WHERE S.source_id = D.source_id 
			AND (disease_extdb,LOWER(source_name)) IN (";
	
		$res=runQuery($query_disease.implode(',',array_keys($LIST_DISEASE)).')');
		if ($res===false)							failProcess($JOB_ID."B01",'Unable to find disease '.$query);
		$disease_entry_ids=array();
		foreach ($res as $line)
		{
			$DATA['MAPD'][strtolower($line['source_name'])][$line['disease_extdb']]=$line['disease_entry_id'];
			
		}
	}
	if ($DATA['TAXON']!=array())
	{
		$res=runQuery("SELECT taxon_id,tax_id 
		FROM taxon 
		WHERE tax_id IN (".implode(",",array_keys($DATA['TAXON'])).")");
		if ($res===false)							failProcess($JOB_ID."B02",'Unable to search taxon '.$query);
		foreach ($res as $line)
		{
			$DATA['TAXON']["'".$line['tax_id']."'"]=$line['taxon_id'];
		}	

	}
	if ($DATA['PUBMED']!=array())
	{
		$res=runQuery("SELECT pmid_entry_id,pmid 
		FROM pmid_entry WHERE pmid IN (".implode(',',array_keys($DATA['PUBMED'])).')');
		if ($res===false)							failProcess($JOB_ID."B03",'Unable to search publi '.$query);
		foreach ($res as $line)
		{
			$DATA['PUBMED'][$line['pmid']]=$line['pmid_entry_id'];
		}	 					
	}
	if ($DATA['PATENT']!=array())
	{
		$res=runQuery("SELECT patent_entry_id, patent_application 
		FROM patent_entry 
		WHERE patent_application IN (".implode(',',array_keys($DATA['PATENT'])).')');
		if ($res===false)							failProcess($JOB_ID."B04",'Unable to search patent '.$query);
		foreach ($res as $line)
		{
			$DATA['PATENT']["'".$line['patent_application']."'"]=$line['patent_entry_id'];
		}	 				
		/// Once we have all the patent_application, we check if we need to add new records	
		foreach ($DATA['PATENT'] as $PATENT_APP=>$PATENT_ID)
		{
			if ($PATENT_ID!=-1)continue;
			$NEW_RECORD['patent_entry']=true;
			++$DBIDS['patent_entry'];
			$DATA['PATENT'][$PATENT_APP]=$DBIDS['patent_entry'];
			fputs($FILES['patent_entry'],$DBIDS['patent_entry']."\t".substr($PATENT_APP,1,-1)."\n");
			
		}
	}
	return $DATA;


}






function processBlock(&$BLOCK)
{
	global $STATS;
	global $FILES;
	global $DBIDS;
	global $source_id;
	global $NEW_RECORD;

	/// Process goes into 3 phases
	/// 1. retrieve the data for the existing records from the database
	/// 2. Perform lookup queries of the data from the files, such as publication, patent, disease,tissue
	/// 3. Compare for each record the data from file and the database 

	/// STEP 1
	$CELL_ENTRIES=preloadData(array_keys($BLOCK));
	
	/// STEP2
	$DATA_IDS=getIDS($BLOCK);
	
	

	//STEP3
	foreach ($BLOCK as &$ENTRY)
	{
		$cell_entry_id=-1;
		$HAS_ENTRY=false;
		
		//// New entry => push it in the file for insertion
		if (!isset($CELL_ENTRIES[$ENTRY['AC']]))
		{

			$STATS['INSERT_ENTRY']++;
			++$DBIDS['cell_entry'];

			fputs($FILES['cell_entry'],
			$DBIDS['cell_entry']."\t".
			'"'.str_replace('"','""',$ENTRY['AC']).'"'."\t".
			'"'.str_replace('"','""',$ENTRY['ID']).'"'."\t".
			'"'.str_replace('"','""',$ENTRY['CATEGORY']).'"'."\t".
			((isset($ENTRY['SEX']))?'"'.str_replace('"','""',$ENTRY['SEX']).'"' :"NULL")."\t".
			((isset($ENTRY['AGE']))?'"'.str_replace('"','""',$ENTRY['AGE']).'"' :"NULL")."\t".
			((isset($ENTRY['TISSUE']))?$ENTRY['TISSUE']:"NULL")."\t".
			$ENTRY['VERSION']."\t".date('Y-m-d',strtotime($ENTRY['LAST UPDATED']))."\n");
			$NEW_RECORD['cell_entry']=true;
			$cell_entry_id=$DBIDS['cell_entry'];
			
		}
		else
		{
			$HAS_ENTRY=true;
			/// Already exist -> compare the data between the file and the database to see if anything changes
			$DB_ENTRY=&$CELL_ENTRIES[$ENTRY['AC']];
			$C_ENTRY=&$DB_ENTRY['cell_entry'];
			$T=$CELL_ENTRIES[$ENTRY['AC']];
			$cell_entry_id=$C_ENTRY['cell_entry_id'];
			$C_ENTRY['DB_STATUS']='VALID';
			if (							$ENTRY['ID']	  != $C_ENTRY['cell_name'])		{$C_ENTRY['DB_STATUS']='TO_UPD';echo "Cell Name\t|".$ENTRY['ID']."|\t|".$C_ENTRY['cell_name'].">".$ENTRY['ID']."|\n";$C_ENTRY['cell_name']		=$ENTRY['ID'];		}
			if (							$ENTRY['CATEGORY']!= $C_ENTRY['cell_type'])		{$C_ENTRY['DB_STATUS']='TO_UPD';echo "Cell Type\t|".$ENTRY['ID']."|\t|".$C_ENTRY['cell_type'].">".$ENTRY['CATEGORY']."|\n";$C_ENTRY['cell_type']		=$ENTRY['CATEGORY'];}
			if (isset($ENTRY['SEX']) && 	$ENTRY['SEX'] 	  != $C_ENTRY['cell_donor_sex']){$C_ENTRY['DB_STATUS']='TO_UPD';echo "Cell Don S\t|".$ENTRY['ID']."|\t|".$C_ENTRY['cell_donor_sex'].">".$ENTRY['SEX']."|\n";$C_ENTRY['cell_donor_sex']	=$ENTRY['SEX'];		}
			if (isset($ENTRY['AGE']) && 	$ENTRY['AGE'] 	  != $C_ENTRY['cell_donor_age']){$C_ENTRY['DB_STATUS']='TO_UPD';echo "Cell Don A\t|".$ENTRY['ID']."|\t|".$C_ENTRY['cell_donor_age'].">".$ENTRY['AGE']."|\n";$C_ENTRY['cell_donor_age']	=$ENTRY['AGE'];		}
			if (isset($ENTRY['TISSUE']) && 	$ENTRY['TISSUE']  != $C_ENTRY['cell_tissue_id']){$C_ENTRY['DB_STATUS']='TO_UPD';echo "Cell Tissu\t|".$ENTRY['ID']."|\t|".$C_ENTRY['cell_tissue_id'].">".$ENTRY['TISSUE']."|\n";$C_ENTRY['cell_tissue_id']	=$ENTRY['TISSUE'];	}
			if (							$ENTRY['VERSION'] != $C_ENTRY['cell_version'])	{echo "Cell Version\t|".$ENTRY['ID']."|\t|".$C_ENTRY['cell_version'].">".$ENTRY['VERSION']."|\n";$C_ENTRY['cell_version']	=$ENTRY['VERSION'];	$C_ENTRY['DB_STATUS']='TO_UPD';}
			if (($ENTRY['LAST UPDATED']) != date("d-m-y", strtotime($C_ENTRY['date_updated'])))
			{
				//echo $ENTRY['LAST UPDATED'].'|'.date("d-m-y", strtotime($C_ENTRY['date_updated']))."\n";
			$C_ENTRY['date_updated']=$ENTRY['LAST UPDATED'];$C_ENTRY['DB_STATUS']='TO_UPD';
			
			//exit;
			}
			
			/// Something changed -> update query
			if ($C_ENTRY['DB_STATUS']=='TO_UPD')
			{
				
				$query="UPDATE cell_entry SET
				cell_acc ='".str_replace("'","''",$ENTRY['AC'])."',
				cell_name='".str_replace("'","''",$ENTRY['ID'])."',
				cell_type='".str_replace("'","''",$ENTRY['CATEGORY'])."', ";
				$query.="cell_donor_sex =";
				if (isset($ENTRY['SEX']))$query.="'".$ENTRY['SEX']."',";else $query.='NULL,';
				
				$query.="cell_donor_age =";
				if (isset($ENTRY['AGE']))$query.="'".$ENTRY['AGE']."',";else $query.='NULL,';
				$query.="cell_tissue_id =";
				if (isset($ENTRY['TISSUE']))$query.=$ENTRY['TISSUE'].',';else $query.='NULL,';
				$query.="cell_version =".$ENTRY['VERSION'].',';
				$query .="date_updated=TO_DATE('".$ENTRY['LAST UPDATED']."', 'dd-mm-YY')";
				$query .=' WHERE cell_entry_id = '.$C_ENTRY['cell_entry_id'];
			//	echo $query."\n";
				$res=runQueryNoRes($query);
				if ($res===false)							failProcess($JOB_ID."C01",'Unable to update cell entry record '.$query);
			//	exit;
				$STATS['UPDATE_ENTRY']++;
			}else $STATS['VALID_ENTRY']++;
		}

		/// We then list of synonyms by including the synonyms, but also the Accession and name
		$SYNS=array();
		$SYNS[$ENTRY['AC']]=-1;
		if (isset($ENTRY['AC_SYN']))
		foreach ($ENTRY['AC_SYN'] as $S)
		{
			$tab=explode(";",$S);
			foreach ($tab as $t)
			$SYNS[trim($t)]=-1;
		}
		if (isset($ENTRY['SYNONYM']))
		foreach ($ENTRY['SYNONYM'] as $S)
		{
			$tab=explode(";",$S);
			foreach ($tab as $t)
			$SYNS[trim($t)]=-1;
		}
			
		/// If we listed any synonyms, we are going to check if they are already in the database
		if (count($SYNS)>0)
		{
			foreach ($SYNS as $syn=>$dummy)
			{

				/// HAS_ENTRY => Cellausorus record already exist in the database - Otherwise,insert all synonyms
				/// Record already exist in the database -> check if the synonym also exists or not
				if ($HAS_ENTRY && isset($DB_ENTRY['SYN']))
				{
					$FOUND=false;
					foreach ($DB_ENTRY['SYN'] as &$syn_entry)
					{
							if ($syn_entry['syn_name']!=$syn)continue;
							$syn_entry['DB_STATUS']='VALID';
							$STATS['VALID_SYN']++;
							$FOUND=true;
							
					}
					if ($FOUND)continue;
				}
				/// Not in the database -> add it
				$NEW_RECORD['cell_syn']=true;
				$STATS['INSERT_SYN']++;
				$DBIDS['cell_syn']++;
				fputs($FILES['cell_syn'],$DBIDS['cell_syn']."\t".'"'.str_replace('"','""',$syn).'"'."\t".$cell_entry_id."\t".$source_id."\n");	
			}

		}


		if (isset($ENTRY['disease']))
		{
			$disease_entry_ids=array();
			/// Diseases ID depends on the source, so we are going to list all the diseases
			/// find the source, and then add the disease_entry_id to the list
			foreach ($ENTRY['disease'] as $DBN=>$LIST)
			{
				foreach ($LIST as $D)
				{
					if ($DBN=='NCIt')
					{
						if ($DATA_IDS['MAPD']['ncit'][$D]!=-1)
						$disease_entry_ids[]=$DATA_IDS['MAPD']['ncit'][$D];
						
					}
					else if ($DBN=='ORDO')
					{
						$tab=explode("_",$D);
						if ($DATA_IDS['MAPD']['orphanet'][$tab[1]]!=-1)
						$disease_entry_ids[]=$DATA_IDS['MAPD']['orphanet'][$tab[1]];
						
					}
				}
			}
			
			
			/// NCIT and Orphaet ID often end up being the same disease_entry_id in the database
			/// So everytime a new record is created, we had it to this this $NEW_DISEASE array 
			/// So the next new record, if it's the same disease, we ignore it
			$NEW_DISEASE=array();
			foreach ($disease_entry_ids as $disease_entry_id)
			{

				/// HAS_ENTRY => Cellausorus record already exist in the database (then compare) - Otherwise,insert all disease
				if ($HAS_ENTRY && isset($DB_ENTRY['disease']))
				{
					$FOUND=false;
					/// Comparing against the database
					foreach ($DB_ENTRY['disease'] as &$disease_ENTRY)
					{
						/// Found it -> then the database record is valid
						if ($disease_ENTRY['disease_entry_id']!=$disease_entry_id)continue;
						$disease_ENTRY['DB_STATUS']='VALID';
						$STATS['VALID_DISEASE']++;
						$FOUND=true;
							
					}
					/// Found it -> moving on
					if ($FOUND)continue;
				}
				
				/// Not found -> have we already added it ? Yes ->Continue
				if (isset($NEW_DISEASE[$disease_entry_id]))continue;
				
				/// Not yet, then we add it
				$NEW_DISEASE[$disease_entry_id]=true;
				$STATS['INSERT_DISEASE']++;
				$DBIDS['cell_disease']++;
				$NEW_RECORD['cell_disease']=true;
				fputs($FILES['cell_disease'],$DBIDS['cell_disease']."\t".$cell_entry_id."\t".$disease_entry_id."\t".$source_id."\n");
					
			}
		}


		/// We then list all species
		if (isset($ENTRY['SPECIES']))
		{
			
			$TAXON_TO_INS=array();
			foreach ($ENTRY['SPECIES'] as $tax_id)
			{
				/// HAS_ENTRY => Cellausorus record already exist in the database (then compare) - Otherwise,insert all taxon
				
				if ($HAS_ENTRY)
				{
					if (isset($DB_ENTRY['taxon']))
					{
						$FOUND=false;
						/// compare against the database to see if it already exists for this record
						foreach ($DB_ENTRY['taxon'] as &$taxon_ENTRY)
						{
								if ($taxon_ENTRY['tax_id']!=$tax_id)continue;
								$taxon_ENTRY['DB_STATUS']='VALID';
								$STATS['VALID_TAXON']++;
								$FOUND=true;
						}
						if ($FOUND)continue;
					}
					$TAXON_TO_INS[$tax_id]=true;
				}else $TAXON_TO_INS[$tax_id]=true;
			}

			/// If there are any taxon to add, we add them
			if ($TAXON_TO_INS!=array()){
				
				foreach ($TAXON_TO_INS as $tax_id=>$dummy)
				{
					if (!isset($DATA_IDS['TAXON']["'".$tax_id."'"])||$DATA_IDS['TAXON']["'".$tax_id."'"]==-1)continue;
					
					$STATS['INSERT_TAXON']++;
					$DBIDS['cell_taxon_map']++;
					$NEW_RECORD['cell_taxon_map']=true;
					
					fputs($FILES['cell_taxon_map'],$DBIDS['cell_taxon_map']."\t".$DATA_IDS['TAXON']["'".$tax_id."'"]."\t".$cell_entry_id."\t".$source_id."\n");
				}	
			}
		}

		if (isset($ENTRY['PUBMED']))
		{
			/// Looking at each publication associated with that record
			$PMID_TO_INS=array();
			foreach ($ENTRY['PUBMED'] as $pmid)
			{
				/// HAS_ENTRY => Cellausorus record already exist in the database (then compare) - Otherwise,insert all publications
				if ($HAS_ENTRY && isset($DB_ENTRY['PUBLI']))
				{
					$FOUND=false;
					/// Compare it agains the database to see if it already exists
					foreach ($DB_ENTRY['PUBLI'] as &$PUBLI_ENTRY)
					{
							if ($PUBLI_ENTRY['pmid']!=$pmid)continue;
							$STATS['VALID_PUBLI']++;
							$PUBLI_ENTRY['DB_STATUS']='VALID';
							$FOUND=true;
					}
					/// Found it -> moving on
					if ($FOUND)continue;

					/// Ensuring we have that pubmed in the database:
					if ($DATA_IDS['PUBMED'][$pmid]==-1)continue;
					/// Then add it
					$DBIDS['cell_pmid_map']++;
					$NEW_RECORD['cell_pmid_map']=true;
					$STATS['INSERT_PUBLI']++;
					fputs($FILES['cell_pmid_map'],$DBIDS['cell_pmid_map']."\t".$cell_entry_id."\t".$DATA['PUBMED'][$pmid]."\t".$source_id."\n");
				}
			}
				
			
		}
		// Check Patent:
		if (isset($ENTRY['PATENT']))
		{
			foreach ($ENTRY['PATENT'] as $PATENT_ID)
			{
				$STR_P='';$ST=true;
				for($I=0;$I<strlen($PATENT_ID);++$I)
				{
					$C=substr($PATENT_ID,$I,1);
					if (!is_numeric($C))
					{
						if ($ST==true)$STR_P.=$C;
						else break;
					}
					else 
					{
						if ($ST==true){$ST=false;$STR_P.='-';}
						$STR_P.=$C;
					}
				}
				
				/// HAS_ENTRY => Cellausorus record already exist in the database (then compare) - Otherwise,insert all patents
				if ($HAS_ENTRY && isset($DB_ENTRY['PATENT']))
				{
					// Compare the patent against the database
					$FOUND=false;
					foreach ($DB_ENTRY['PATENT'] as &$patent_entry)
					{
						//echo 
						if ($patent_entry['patent_application']!=$STR_P)continue;
						$STATS['VALID_PATENT']++;
						$patent_entry['DB_STATUS']='VALID';
						$FOUND=true;
					}
					if ($FOUND)continue;
				}


				$DBIDS['cell_patent_map']++;
				$PATENT_ENTRY_ID=$DATA_IDS['PATENT']["'".$STR_P."'"];
				if ($PATENT_ENTRY_ID==-1)continue;
				$NEW_RECORD['cell_patent_map']=true;
				$STATS['INSERT_PATENT']++;
				fputs($FILES['cell_patent_map'],$DBIDS['cell_patent_map']."\t".$cell_entry_id."\t".$PATENT_ENTRY_ID."\t".$source_id."\n");	
			}
		 }	
	}


	/// We then review all entries to see if one of them needs to be deleted
	$DEL=array('ENTRY'=>array(),'PUBMED'=>array(),'TAXON'=>array(),'SYN'=>array(),'DISEASE'=>array(),'PATENT'=>array());
	foreach ($CELL_ENTRIES as &$K)
	{
		
		if (isset($K['SYN']))
		{
			foreach ($K['SYN'] as &$S) if ($S['DB_STATUS']=='FROM_DB')$DEL['SYN'][]=$S['cell_syn_id'];
		}
		if (isset($K['PATENT']))
		{
			foreach ($K['PATENT'] as &$S) if ($S['DB_STATUS']=='FROM_DB')$DEL['PATENT'][]=$S['cell_patent_map_id'];
		}
		if (isset($K['PUBMED']))
		{
			foreach ($K['PUBMED'] as &$S) if ($S['DB_STATUS']=='FROM_DB')$DEL['PUBMED'][]=$S['cell_pmid_id'];
		}
		if (isset($K['taxon']))
		{
			foreach ($K['taxon'] as &$S) if ($S['DB_STATUS']=='FROM_DB')$DEL['TAXON'][]=$S['cell_taxon_id'];
		}
		if (isset($K['disease']))
		{
			foreach ($K['disease'] as &$S) 
			{
				
				if ($S['DB_STATUS']=='FROM_DB')$DEL['DISEASE'][]=$S['cell_disease_id'];
			}
		}

	}
	/// Then we delete
	foreach ($DEL as $N=>&$LIST_D)
	{
		if ($LIST_D==array())continue;
		switch ($N)
		{
			case 'ENTRY':
				$STATS['DELETE_ENTRY']+=count($LIST_D); 
				if (!runQueryNoRes("DELETE FROM cell_entry where cell_entry_id in (".implode(',',$LIST_D).')'))
					failProcess($JOB_ID."C02",'Unable to delete cell entry');;
				break;
			case 'PUBMED':
				$STATS['DELETE_PUBLI']+=count($LIST_D); 
				if (!runQueryNoRes("DELETE FROM cell_pmid_map where cell_pmid_id in (".implode(',',$LIST_D).')'))
					failProcess($JOB_ID."C03",'Unable to delete cell pmid');
				break;
			case 'TAXON':
				$STATS['DELETE_TAXON']+=count($LIST_D);
				if (!runQueryNoRes("DELETE FROM cell_taxon_map where cell_taxon_id in (".implode(',',$LIST_D).')'))
					failProcess($JOB_ID."C04",'Unable to delete cell taxon');
				break;
			case 'SYN': 
				$STATS['DELETE_SYN']+=count($LIST_D);
				if (!runQueryNoRes("DELETE FROM cell_syn where cell_syn_id in (".implode(',',$LIST_D).')'))
					failProcess($JOB_ID."C05",'Unable to delete cell syn');
				break;
			case 'DISEASE':
				$STATS['DELETE_DISEASE']+=count($LIST_D); 
				if (!runQueryNoRes("DELETE FROM cell_disease where cell_disease_id in (".implode(',',$LIST_D).')'))
					failProcess($JOB_ID."C06",'Unable to delete cell disease');
					break;
			case 'PATENT': 
				$STATS['DELETE_PATENT']+=count($LIST_D);
				if (!runQueryNoRes("DELETE FROM cell_patent_map where cell_patent_map_id in (".implode(',',$LIST_D).')'))
					failProcess($JOB_ID."C07",'Unable to delete cell patent');
					break;
		}
	}
	
	/// Then we insert the new data

	global $COL_ORDER;
	global $W_DIR;
	global $DB_INFO;
	global $GLB_VAR;
	$N_E=0;
	/// So looping through each table
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
		/// Closing file
		$res=array();
		fclose($FILES[$NAME]);

		/// If no new record, we continue
		if (!$NEW_RECORD[$NAME])continue;

		addLog("inserting ".$NAME." records");


		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$W_DIR.'/INSERT/'.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		/// Run copy command to insert new record
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		print_r($res);
		if ($return_code !=0 )failProcess($JOB_ID."C08",'Unable to insert '.$NAME); 
	}

	/// Reopen files
	$FILES=array();
	foreach ($COL_ORDER as $TYPE=>$CTL)
	{
		$FILES[$TYPE]=fopen($W_DIR.'/INSERT/'.$TYPE.'.csv','w');
		if (!$FILES[$TYPE])		failProcess($JOB_ID."C09",'Unable to open '.$TYPE.'.csv');
	}

	print_r($STATS);
//exit;

}
		
successProcess();


?>
