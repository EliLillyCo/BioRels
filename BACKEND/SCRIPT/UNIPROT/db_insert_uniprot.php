<?php

/**
 SCRIPT NAME: db_insert_uniprot
 PURPOSE:     Insert new uniprot data
 
*/
ini_set('memory_limit','4000M');
error_reporting(E_ALL);

/// Job name - Do not change
$JOB_NAME='db_insert_uniprot';

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


require_once($TG_DIR.'/BACKEND/SCRIPT/UNIPROT/uniprot_function.php');


addLog("Access directory");

	/// GEt parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('pp_uniprot')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	
												   if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	/// Update process control directory to the current release so that the next job can use it								   
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Working directory: ".$W_DIR);

	/// There is a few static data that need to be loaded in memory to know the corresponding database Ids
addLog("Database pre-load data");
	$STATIC_DATA=array('EXTDB'=>array(),'TAXON'=>array(),'FEAT'=>array());
	preloadData();


addLog("Get MAx DBIDS")	;
	/// For each table that we are going to insert into, we want to know the highest primary key value to do quick insertion
	$DBIDS=array('prot_entry'=>-1,
	'prot_ac'=>-1,
	'prot_seq'=>-1,
	'prot_dom'=>-1,
	'prot_dom_seq'=>-1,
	'prot_seq_pos'=>-1,
	'prot_feat'=>-1,
	'prot_feat_seq'=>-1,
	'prot_feat_pmid'=>-1,
	'prot_extdb_map'=>-1,
	'prot_name'=>-1,
	'prot_name_map'=>-1,
	'prot_desc'=>-1,
	'prot_go_map'=>-1,
	'prot_desc_pmid'=>-1,
	'gn_prot_map'=>-1
);
	///	Everytime we have a new record, we update $FILE_STATUS to true for the given file.
	$FILE_STATUS=array();
	$FILE_VALID=$DBIDS;
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) CO FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."005",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
		$FILE_STATUS[$TBL]=0;
	}
	$DBIDS['DESC_FILES']=0;
	
	/// To insert properly, we need to provide the column order for each table
	$COL_ORDER=array('prot_entry'=>'(prot_entry_id, prot_identifier, date_created, date_updated, status, taxon_id , confidence)',
	'prot_ac'=>'(prot_ac_id,prot_entry_Id, ac,is_primary)',
	'prot_seq'=>'(prot_seq_id,prot_entry_Id,iso_name,iso_id,is_primary,description,modification_date,note)',
	'prot_dom'=>'(prot_dom_id,prot_entry_id,domain_name,modification_date,domain_type,pos_start,pos_end)',
	'prot_seq_pos'=>'(prot_seq_pos_id,prot_seq_id,position,letter)',
	'prot_dom_seq'=>'(prot_dom_Seq_id,prot_dom_id,prot_seq_pos_id,position)',
	 'prot_name'=>'(prot_name_id, protein_name , ec_number )',
	 'prot_name_map'=>'(prot_name_map_id , prot_entry_id , prot_name_id , group_id , class_name , name_type , name_subtype , name_link , is_primary)',
	'prot_feat'=>'(prot_feat_id,prot_feat_Type_id,feat_value,feat_link,prot_seq_id,start_pos,end_pos)',
	'prot_feat_seq'=>'(prot_feat_seq_id,prot_feat_id,prot_seq_pos_id,position)',
	'prot_feat_pmid'=>'(prot_feat_pmid_id,prot_feat_id     ,pmid_entry_id,eco_entry_id     )',
	'prot_extdb_map'=>'(prot_extdb_map_Id,prot_extdb_id,prot_entry_id,prot_extdb_value,prot_seq_id)',
	'prot_desc'=>'(prot_desc_id, prot_entry_id, description,desc_type)',
	'prot_desc_pmid'=>'(prot_desc_pmid_id,prot_desc_id, pmid_entry_Id,eco_entry_id)',
	'prot_go_map'=>'(prot_go_map_id,go_entry_id,prot_entry_id,evidence,source_id)',
	'gn_prot_map'=>'(gn_prot_map_id,gn_entry_id,prot_entry_id)'
);



addLog("Open files");
	if (!is_dir('INSERT') && !mkdir('INSERT'))										failProcess($JOB_ID."006",'Unable to create INSERT directory');
	if (!chdir('INSERT'))															failProcess($JOB_ID."007",'Unable to access INSERT directory');
	


	$MEM=memory_get_usage();;

addLog("Process files");
	$fpF=fopen('failed_insert.csv','w');if (!$fpF) 									failProcess($JOB_ID."008",'Unable to open failed_insert.csv');
	$LEN=0;$N_PROCESSED=0;$N_ALL=0;
	/// Used for protein names:
	$PROT_NAMES=array();
	$TMP_PNAMES=array();
	
	$FILES_SEQ=array(
		'SEQ'=>fopen('PROT_seq.fasta','w'),
		'SEQ_P'=>fopen('PROT_seq.pointers','w'),
		'DOM'=>fopen('PROT_dom.fasta','w'),
		'DOM_P'=>fopen('PROT_dom.pointers','w'));
		foreach ($FILES_SEQ as $TYPE=>&$FP) if (!$FP)								failProcess($JOB_ID."009",'Unable to open failed_insert.csv');

		$ALL_SUCCESS=true;
	$FIRST=true;
	/// There are 50 scripts, so we need to process the results of each of them 
	for($I=0;$I<50;++$I)
	{
		echo "PROCESSING FILE ".$I."\n";
		print_r($DBIDS);
		
		/// First, we open the output files
		$FILES=array();
		$N_PROCESSED=0;
		foreach ($COL_ORDER as $TYPE=>$CTL)
		{
			$FILES[$TYPE]=fopen($TYPE.'.csv','w');
			if (!$FILES[$TYPE])														failProcess($JOB_ID."010",'Unable to open '.$TYPE.'.csv');
		}
		/// Then we open the result file
		$fp=fopen('../JSON/'.$I.'.json','r');if (!$fp) 								failProcess($JOB_ID."011",'Unable to open '.$I.'.json');
		$DONE=array();$N_BLOCK=0;
	
		while(!feof($fp))
		{
			/// Each line is a uniprot record. It can be pretty long, so we consider long lines
			$line=stream_get_line($fp,1000000000,"\n");
			if (strlen($line)==1000000000) 										failProcess($JOB_ID.'012','MAximum length limit reached');
			if ($line=='')continue;
			/// Then we decode the record:
			$ENTRY=json_decode($line,true);

			///Can't read the record? then there is an issue
			if ($ENTRY==null)
			{	
				echo $line."\n";
				failProcess($JOB_ID."013",'Unable to parse json string: '.json_last_error_msg());
				
			}
			
			if (preg_match('/\-[0-9]{0,1}_/',$ENTRY['prot_entry']['prot_identifier']))continue;
			
		//	echo $I."\t".$ENTRY['prot_entry']['prot_identifier']."\t";
			$N_PROCESSED++;;++$N_ALL;
			
		
			
			/// Then we insert:
			if (!insertEntry($ENTRY))
			{
				
				fputs($fpF,$ENTRY['prot_entry']['prot_identifier']."\n");
			}else 
			{ 
				/// Sequence and domain are stored in separate files
				//echo "SUCCESS\n";
				$N_BLOCK++;
				
				$ALL_SQ=$ENTRY['prot_seq'];
				foreach ($ALL_SQ as $SQ)
				{
					
					$STR_SQ='';
					//print_r($SQ);
					foreach ($SQ['SEQ'] as $SEQ_POS)
					{
						$STR_SQ.=$SEQ_POS['AA'];
						$MAP_SEQ[$SEQ_POS['DBID']]=$SEQ_POS['AA'];
					}
					$STR='>'.$SQ['DBID']."\n".implode("\n",str_split($STR_SQ,100))."\n";
					$FPOS=ftell($FILES_SEQ['SEQ']);
					fputs($FILES_SEQ['SEQ_P'],$SQ['DBID']."\t".$FPOS."\t".strlen($STR)."\n");
					fputs($FILES_SEQ['SEQ'],$STR);
					

				}
				$ALL_SQ=null;	
				
				foreach ($ENTRY['prot_dom'] as &$SQ)
				{

					if (count($SQ['SEQ'])<30)continue;
					$STR_SQ='';
					
					foreach ($SQ['SEQ'] as &$SEQ_POS)
					{
						$STR_SQ.=$SEQ_POS['AA'];
						
					}
					
					$STR='>'.$SQ['DBID']."-".$SQ['type']."\n".implode("\n",str_split($STR_SQ,100))."\n";
					$FPOS=ftell($FILES_SEQ['DOM']);
					fputs($FILES_SEQ['DOM_P'],$SQ['DBID']."\t".$FPOS."\t".strlen($STR)."\t".$SQ['type']."\n");
					fputs($FILES_SEQ['DOM'],$STR);
					

				}
				
				$STR=null;$FPOS=null;$MAP_SEQ=null;

			
			}
			
			if ($N_BLOCK%500==0)echo $N_PROCESSED."\t".$N_ALL."\t".$N_BLOCK."\tMEM:".memory_get_usage()."\t".count($PROT_NAMES)."\t".count($TMP_PNAMES)."\t".memory_get_usage()."\tUSAGE:".(memory_get_usage()-$MEM)."\n";;
			$ENTRY=null;
			gc_collect_cycles();
			

			/// $PROT_NAMES is a list of protein names that we need to insert in the database
			/// It follow a different process then the rest of the data due to the possibility 2 records shares the same protein name
			if (count($PROT_NAMES)>1000)
			{
				pushNames();
			}
				/// Every 1000 records, we push to the database
			if ($N_BLOCK < 1000)continue;
	
			echo "FILE POS :".$I."\t".ftell($fp)."\n";
			
			/// Push to the database
			pushAllToDB();

			/// Clean up the arrays
			$TMP_PNAMES=null;
			$PROT_NAMES=null;
			$N_BLOCK=0;
			$TMP_PNAMES=array();
			$N_PROCESSED=0;
			$PROT_NAMES=array();
			echo "FILE POS :".ftell($fp)."\n";
			
		}fclose($fp);
	
	
	
		/// Cleaning up memory for optimal use
		gc_collect_cycles();

		/// Once we are done with a file, we don't forget to process the last few records
		echo $N_ALL."\t".$N_PROCESSED."\tMEM:".memory_get_usage()."\t".count($PROT_NAMES)."\t".count($TMP_PNAMES)."\n";
		$MEM=memory_get_usage();
		
		pushAllToDB();
		/// Clean up the arrays
		$TMP_PNAMES=null;
		$PROT_NAMES=null;
		$N_BLOCK=0;
		$TMP_PNAMES=array();
		$PROT_NAMES=array();
		
		echo "##############\n##############\n##############\n##############\n";
		
	//	exit;
	}

	///Clean up unused protein names:
	if (!runQueryNoRes("DELETE FROM prot_name where prot_name_id NOT IN (SELECT DISTINCT prot_name_id FROM prot_name_map)"))$ALL_SUCCESS=false;



	if ($ALL_SUCCESS)successProcess();
else 	failProcess($JOB_ID."013",'Unable to fully insert uniprot information');














function preloadData()
{
	global $STATIC_DATA;
	global $JOB_ID;


	$res=array();
	$query="SELECT prot_extdbid, prot_extdbabbr FROM prot_extdb";
	$res=runQuery($query);
	if ($res===false)												failProcess($JOB_ID."A01",'Unable to get External databases');
	
	foreach ($res as $tab) 
	{
		$STATIC_DATA['EXTDB'][$tab['prot_extdbabbr']]=$tab['prot_extdbid'];
	}
	
	

		addLog("Pre load prot feat type");
	$res=array();
	$query="SELECT prot_feat_Type_id, tag FROM prot_feat_type";
	
	$res=runQuery($query);
	if ($res===false)												failProcess($JOB_ID."A02",'Unable to get Feature type');
	foreach ($res as $tab) 
	{
		$STATIC_DATA['FEAT'][$tab['tag']]=$tab['prot_feat_type_id'];
	}


	addLog("Pre load taxon");
	$res=array();
	$query="SELECT DISTINCT T.taxon_id, tax_id 
			FROM taxon T, prot_entry U 
			WHERE T.taxon_id=U.taxon_id 
			ORDER BY tax_id ASC" ;
	$res=runQuery($query);
	if ($res===false)												failProcess($JOB_ID."A03",'Unable to get Taxons');
	foreach ($res as $tab) 
	{
		$STATIC_DATA['TAXON'][$tab['tax_id']]=$tab['taxon_id'];
	}

	addLog("Pre load eco entry");
	$res=runQuery("SELECT eco_id,eco_entry_id FROM eco_entry");
	if ($res===false)												failProcess($JOB_ID."A04",'Unable to get ECO entry');
	foreach ($res as $line)
	{
		$STATIC_DATA['ECO'][$line['eco_id']]=$line['eco_entry_id'];
	}

	addLog("prepare gene");
	$STATIC_DATA['GENE']=array();
	

}



	function pushNames()
	{
		addLog("Push names");
		global $PROT_NAMES;
		global $FILES;
		global $DBIDS;
		global $GLB_VAR;
		global $COL_ORDER;
		global $DB_INFO;
		global $JOB_ID;



		/// Because some protein names have EC associated (or not), we need to do two queries
		$Q1='SELECT prot_name_id,protein_name,ec_number FROM prot_name  WHERE (protein_name,ec_number) IN (';$HAS_Q1=false;
		$Q2='SELECT prot_name_id,protein_name FROM prot_name WHERE ec_number IS NULL AND protein_name IN (';$HAS_Q2=false;
		foreach ($PROT_NAMES as $PN=>$LIST)
		{
			$tab=explode("__",$PN);
			if ($tab[1]=='')	{$HAS_Q2=true;$Q2.="'".str_replace("'","''",$tab[0])."',";}
			else {$HAS_Q1=true;$Q1.="('".str_replace("'","''",$tab[0])."','".$tab[1]."'),";}
		}
		/// Running the first query with EC number:
		if ($HAS_Q1)
		{
			$res=runQuery(substr($Q1,0,-1).')');
			if ($res===false)					failProcess($JOB_ID."B01",'Unable to query protein names with EC');

			foreach ($res as $l)$PROT_NAMES[$l['protein_name'].'__'.$l['ec_number']]['ID']=$l['prot_name_id'];
		}
		/// Running the second query without EC number
		if ($HAS_Q2)
		{
			$res=runQuery(substr($Q2,0,-1).')');
			if ($res===false)					failProcess($JOB_ID."B02",'Unable to query protein names without EC');
			foreach ($res as $l)$PROT_NAMES[$l['protein_name'].'__']['ID']=$l['prot_name_id'];
		}

		/// Then we look at all protein names
		foreach ($PROT_NAMES as $PN=>&$LIST)
		{
			$tab=explode("__",$PN);	
			/// To see if that protein name exists in the database or not
			/// No, we insert it
			if (!isset($LIST['ID']))
			{
				$DBIDS['prot_name']++;$LIST['ID']=$DBIDS['prot_name'];
				fputs($FILES['prot_name'],$DBIDS['prot_name']."\t".'"'.$tab[0].'"'."\t".(($tab[1]=='')?'NULL':$tab[1])."\n");
			}
			/// And then we insert the mapping between that protein name and uniprot id
			foreach ($LIST['LIST'] as $L)
			{
				$DBIDS['prot_name_map']++;
				fputs($FILES['prot_name_map'],$DBIDS['prot_name_map']."\t".str_replace('${PROT_NAME_ID}',$LIST['ID'],$L));
			}

			

		}


		$TO_INS=array('prot_entry','prot_name','prot_name_map');
		echo "PUSH NAMES:";
		foreach ($TO_INS as $NAME)
		{
			$CTL=$COL_ORDER[$NAME];
		//	if (in_array($NAME,$TO_FILTER))continue;
			
		//if ($NAME!='prot_feat' && $NAME!='prot_feat_seq')continue;

			$res=array();
			fclose($FILES[$NAME]);
			$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			//echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			echo $NAME."\t";
			system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
			if ($return_code !=0 )													failProcess($JOB_ID."B03",'Unable to insert '.$NAME);
			
				
			$FILES[$NAME]=fopen($NAME.'.csv','w');
			if (!$FILES[$NAME])														failProcess($JOB_ID."B04",'Unable to open '.$NAME.'.csv');
		}
		echo "\n";

		$PROT_NAMES=array();
	}















	function pushAllToDB()
	{
		addLog("Push all to db");
		/// Here we are going to push all the data into the database.
		/// But first, we need to lookup protein names
		global $PROT_NAMES;
		global $COL_ORDER;
		global $FILES;
		global $GLB_VAR;
		global $DB_INFO;
		global $FILE_VALID;
		global $FILE_STATUS;
		global $ALL_SUCCESS;
		global $DBIDS;


		pushNames();
		
		foreach ($FILE_VALID as $F=>&$V)$V=true;
			
		/// Once it's done, we look over each files, close them, and push them to the database
		foreach ($COL_ORDER as $NAME=>$CTL)
		{
		//	if (in_array($NAME,$TO_FILTER))continue;
			
		//if ($NAME!='prot_feat' && $NAME!='prot_feat_seq')continue;
		
			if (!$FILE_VALID[$NAME]){echo "SKIPPING ".$NAME."\t";continue;}
			
			$res=array();
			fclose($FILES[$NAME]);
			$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			
			//echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			echo $NAME."\t".$FILE_STATUS[$NAME]."\t";
			$res=array();
		
			exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
			if ($return_code !=0 )	
			{
				$ALL_SUCCESS=false;
				switch ($NAME)
				{
					case 'prot_entry':foreach ($FILE_VALID as $F=>&$V)$V=false;break;
					#case 'prot_ac'=>-1,
					case 'prot_seq':
						$FILE_VALID['prot_dom']=false;
						$FILE_VALID['prot_dom_seq']=false;
						$FILE_VALID['prot_seq_pos']=false;
						$FILE_VALID['prot_feat']=false;
						$FILE_VALID['prot_feat_seq']=false;
						$FILE_VALID['prot_feat_pmid']=false;
						break;
					case 'prot_dom':
						$FILE_VALID['prot_dom_seq']=false;
						break;
					#case 'prot_dom_seq'=>-1,
					case 'prot_seq_pos':
						$FILE_VALID['prot_dom']=false;
						$FILE_VALID['prot_dom_seq']=false;
						$FILE_VALID['prot_feat']=false;
						$FILE_VALID['prot_feat_seq']=false;
						$FILE_VALID['prot_feat_pmid']=false;
						break;
					case 'prot_feat':
						$FILE_VALID['prot_feat_seq']=false;
						$FILE_VALID['prot_feat_pmid']=false;
						break;
					#case 'prot_feat_seq'=>,
					#case 'prot_feat_pmid'=>-1,
					#case 'prot_extdb_map'=>-1,
					case 'prot_name':
						$FILE_VALID['prot_name_map']=false;
						break;
					#case 'prot_name_map'=>-1,
					case 'prot_desc':
						$FILE_VALID['prot_desc_pmid']=false;
						break;
					// case 'prot_go_map'=>-1,
					// case 'prot_desc_pmid'=>-1,
					// case 'gn_prot_map'=>-1
				}
			}
		}
	
		echo $res[0]."\n";
			
		/// Then we clean up, and reopen the files:
		$FILES=array();$N_PROCESSED=0;
		foreach ($COL_ORDER as $TYPE=>$CTL)
		{
			$FILE_STATUS[$TYPE]=0;
			$FILES[$TYPE]=fopen($TYPE.'.csv','w');
			if (!$FILES[$TYPE])														failProcess($JOB_ID."021",'Unable to open '.$TYPE.'.csv');
			
		}
		
		echo "##############\n##############\n##############\n##############\n";
		print_r($DBIDS);
	}


	
	




?>

