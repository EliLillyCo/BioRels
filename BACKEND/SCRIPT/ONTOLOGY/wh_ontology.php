<?php
error_reporting(E_ALL);
ini_set('memory_limit','1000M');
$JOB_NAME='wh_ontology';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false) die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
 

addLog("Create directory");
	
	$CURR_DATE=getCurrDate();
	$PROCESS_CONTROL['DIR']=$CURR_DATE;
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'];   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.='/'.getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR))failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	if (!chdir($W_DIR))																failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=getCurrDate();
	



addLog("Preparing table for batch insert");	;
	/// Get max PK values for each table so we can do batch insert
	$DBIDS=array('ontology_entry'=>-1, 
				'ontology_syn'=>-1);

	foreach ($DBIDS as $TBL=>&$POS)
	{
		$res=array();
		$res=runQuery('SELECT MAX('.$TBL.'_id) co FROM '.$TBL);
		if ($res===false)																failProcess($JOB_ID."009",'Unable to get Max ID for '.$TBL);
		
		$DBIDS[$TBL]=($res[0]['co']=='')?0:$res[0]['co'];
	}

	/// COL_ORDER list all tables that we are going to insert into and their columns 
	$COL_ORDER=array(
		'ontology_entry'=>'(ontology_entry_id,ontology_tag,ontology_name,ontology_definition,ontology_group,w_pubmed)',
		'ontology_syn'=>'(ontology_syn_id,ontology_entry_id,syn_type,syn_value,source_id)',
		'ontology_hierarchy'=>'(ontology_entry_id,ontology_level,ontology_level_left,ontology_level_right)');

	/// FILE_STATUS list each file and a boolean to indicate if new records has been inserted and need to be pushed to the DB
	$FILE_STATUS=array('ontology_entry'=>false,
			'ontology_syn'=>false,
			'ontology_hierarchy'=>false);

	$SOURCE_ID=getSource("BioRels");



addLog("Opening files");
	foreach ($COL_ORDER as $TBL=>&$POS)
	{
		$FILES[$TBL]=fopen($TBL.'.csv','w');
		if (!$FILES[$TBL])															failProcess($JOB_ID."014",'Unable to open '.$TBL.'.csv');
	}

	
	
addLog("Working directory: ".$W_DIR);

	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/ONTOLOGY/';	
	if (!is_dir($STATIC_DIR))														failProcess($JOB_ID."003",'ONTOLOGY static dir not found '.$STATIC_DIR);
	
	$RECORDS=array();

	$res=runQuery("SELECT ONTOLOGY_ENTRY_ID,
		ONTOLOGY_TAG,
		ONTOLOGY_NAME,
		ONTOLOGY_DEFINITION, W_PUBMED 
		FROM ONTOLOGY_ENTRY");
	if ($res===false)															failProcess($JOB_ID."004",'Unable to run ONTOLOGY_ENTRY query');
	
	foreach ($res as $line)
	{
		$RECORDS[$line['ontology_tag']]=array(
			'DBID'=>$line['ontology_entry_id'],
			'DB_STATUS'=>'FROM_DB',
			'NAME'=>$line['ontology_name'],
			'DESCRIP'=>$line['ontology_definition'],
			'W_PUBMED'=>$line['w_pubmed']);
		
	}


	$res=runQuery("SELECT ONTOLOGY_SYN_ID,
		ONTOLOGY_TAG,
		SYN_TYPE,SYN_VALUE
		 FROM ONTOLOGY_ENTRY OE, ONTOLOGY_SYN OP
		WHERE  OE.ONTOLOGY_ENTRY_ID = OP.ONTOLOGY_ENTRY_ID");
	if ($res===false)														failProcess($JOB_ID."006",'Unable to run ONTOLOGY_SYN query');

	
	foreach ($res as $line)
	{
		$RECORDS[$line['ontology_tag']]['SYNONYM'][$line['syn_value']]=array('DBID'=>$line['ontology_syn_id'],'DB_STATUS'=>'FROM_DB');
		
	}

	
	
	$FPATH='';
	

	#START		[ONTOLOGY_ID]						-> Start a new block
	#NAME		[NAME]								-> Name of the record. MUST be uniquely found across all records in both NAME and SYN. Only once per block
	#SYNONYM	[SYNONYM]							-> Alternative name. 0, 1 or any numbers allowed. MUST be unique across all records in both NAME and SYN
	#CR_NAME	[LAST_NAME]		[FIRST_NAME]		-> Name of the person that created this record
	#CR_DATE	[DATE: YYYY/MM/DD]					-> Creation date of the record
	#DESCRIP	[TEXT]								-> Description of the record. If multi-line, start each line with the DESCRIP tag
	#CHILDOF	[ONTOLOGT_ID_PARENT]				-> ID of the parent (as defined in Name). Except ROOT,  ALL block must have at least 1 CHILDOF
	#EXTDBID	[DB_NAME]	[DB_ID]		[REL_TYPE]	-> External identifiers (if any). REL_TYPE allowed values: IS_A		EQUIVALENT_TO 	INCLUDE
	#PUBMED	[Y/N]									-> If Y, Biorels will execute a search on pubmed for this record
	#PUBMED_Q   [QUERY]								-> Pubmed query that will be stored as synonym
	#LOG		[DATE: YYYY/MM/DD]		[LAST_NAME:FIRST_NAME]	[LOG]


	// Load the ontology data
		if (!is_file($STATIC_DIR.'/ONTOLOGY_DATA'))										failProcess($JOB_ID."008",'Unable to find '.$STATIC_DIR.'/ONTOLOGY.data');
		$FPATH=$STATIC_DIR.'/ONTOLOGY_DATA';
		loadFile($FPATH);
	
	

	//// Ensuring Root has child, and all parent exists, and that any nodes have a parent (except root)
	
	foreach ($RECORDS as $TAG=>&$ENTRY)
	{
		if ($TAG=='LSO_0000001')
		{
			if (!isset($ENTRY['CHILD']))
			{
				print_r($ENTRY);
				failProcess($JOB_ID."A04",'No child found for root');
			}
			continue;
		}
		if (!isset($ENTRY['NAME']) && isset($ENTRY['CHILD']))	failProcess($JOB_ID."009",'Parent node with no information found: '.$TAG);
	}
	

addLog("Update entries");
	$TO_DEL=array();
	foreach ($RECORDS as $TAG=>&$ENTRY)
	{
		/// New -> we insert in the file
		if ($ENTRY['DB_STATUS']=='TO_INS')
		{
			++$DBIDS['ontology_entry'];
			$ENTRY['DBID']=$DBIDS['ontology_entry'];
			fputs($FILES['ontology_entry'],
				$ENTRY['DBID']."\t".
				$TAG."\t".
				$ENTRY['NAME']."\t".
				$ENTRY['DESCRIP']."\tNULL\t".
				$ENTRY['W_PUBMED']."\n");
			$FILE_STATUS['ontology_entry']=true;
		}
		else if ($ENTRY['DB_STATUS']=='TO_UPD')
		{
			
			$query="UPDATE ONTOLOGY_ENTRY SET
			ONTOLOGY_TAG='".$TAG."',
			ONTOLOGY_NAME='".str_replace("'","''",$ENTRY['NAME'])."',
			ONTOLOGY_DEFINITION='".str_replace("'","''",$ENTRY['DESCRIP'])."',
			W_PUBMED='".((isset($ENTRY['W_PUBMED']) && $ENTRY['W_PUBMED']=='T')?'T':'F')."'
			WHERE  ONTOLOGY_ENTRY_ID=".$ENTRY['DBID'];
			
			if (!runQueryNoRes($query))	failProcess($JOB_ID."010",'Unable to update record '.$TAG);
		}
		else if ($ENTRY['DB_STATUS']=='FROM_DB')
		{
			$TO_DEL[]=$ENTRY['DBID'];
		}
		if (!isset($ENTRY['SYNONYM']))continue;
		foreach ($ENTRY['SYNONYM'] as $S=>$V)
		{
			if ($V['DB_STATUS']=='TO_INS')
			{
				++$DBIDS['ontology_syn'];
				$FILE_STATUS['ontology_syn']=true;
				fputs($FILES['ontology_syn'],$DBIDS['ontology_syn']."\t".$ENTRY["DBID"]."\tEXACT\t\"".str_replace('"','""',$S)."\"\t".$SOURCE_ID."\n");
			}
		}
	}

	if ($TO_DEL!=array())
	{
		addLog("DELETING ".count($TO_DEL).' records');
		$query="DELETE FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_ENTRY_ID IN (".implode(',',$TO_DEL).")";
		if (!runQueryNoRes($query))failProcess($JOB_ID."011",'Unable to delete records');
	
	}

	pushFilesToDB(false);

	
	$VALUE=0;
	//print_r($ROOTS);
	if (!runQueryNoRes("TRUNCATE TABLE ONTOLOGY_HIERARCHY"))failProcess($JOB_ID."011",'Unable to truncate hierarchy');
	$ROOTS['LSO_0000001']=true;

	genTree($RECORDS,$ROOTS,0,$VALUE);
	
	pushFilesToDB(true);

	pushToProd();

	successProcess();

	


	function loadFile($FPATH,$IS_FIRST=true)
	{
		global $RECORDS;
		global $FILES;
		global $JOB_ID;
		global $SOURCE_ID;
		global $DBIDS;
		
		$fp=fopen($FPATH,'r');if (!$fp)									failProcess($JOB_ID."A01",'Unable to open '.$FPATH);
		$fpO=fopen('ONTOLOGY.data',($IS_FIRST)?'w':'a');
		$N_REC=0;
		$TMP_PARENT=array();
		while(!feof($fp))
		{
			$line=stream_get_line($fp,100,"\n");
			fputs($fpO,$line."\n");
			if ($line==''|| substr($line,0,1)=='#')continue;
			$tab=explode("\t",$line); 
			if ($tab[0]!='START')continue;
			$RECORD=array('TAG'=>$tab[1]);
			while(!feof($fp))
			{
				$line=stream_get_line($fp,1000,"\n");
				fputs($fpO,$line."\n");
				if ($line==''|| substr($line,0,1)=='#')continue;
				$tab=explode("\t",$line); 
				if ($tab[0]=='END')break;
				$HEAD=$tab[0];
				unset($tab[0]);
				$tab=array_values($tab);
				
				if ($HEAD=='SYNONYM'||$HEAD=='EXTDBID'||$HEAD=='LOG')$RECORD[$HEAD][]=$tab[0];
				
				else if ( $HEAD=='PMID_Q')$RECORD['SYNONYM'][]='PUBMED::'.$tab[0];
				
				else if ($HEAD=='CHILDOF')$RECORD[$HEAD][]=$tab[0];
				
				else if ($HEAD=='DESCRIP'||$HEAD=='NAME'||$HEAD=='PUBMED')$RECORD[$HEAD]=$tab[0];
				
				else $RECORD[$HEAD]=$tab;
			}



			fputs($fpO,"\n");	
			if (!isset($RECORDS[$RECORD['TAG']]))
			{
				$RECORDS[$RECORD['TAG']]=array(
					'DBID'=>-1,
					'DB_STATUS'=>'TO_INS',
					'NAME'=>$RECORD['NAME'],
					'DESCRIP'=>$RECORD['DESCRIP'],
					'W_PUBMED'=>isset($RECORD['PUBMED']) && $RECORD['PUBMED']=='T'?'T':'F');
				
			}
			else
			{
				
				$ENTRY=&$RECORDS[$RECORD['TAG']];
				
				if ($ENTRY['DB_STATUS']=='TO_INS')
				{
					
					failProcess($JOB_ID.'A02','Duplicated entry '.$RECORD['TAG']);
				}
				$ENTRY['DB_STATUS']='VALID';
				if ($ENTRY['NAME']!=$RECORD['NAME'])
				{
					echo $ENTRY['NAME']."\t".$RECORD['NAME']."\n";
					
					$ENTRY['NAME']=$RECORD['NAME'];
					$ENTRY['DB_STATUS']='TO_UPD';
				}
				if (isset($RECORD['DESCRIP']) && $ENTRY['DESCRIP']!=$RECORD['DESCRIP'])
				{
					echo $ENTRY['DESCRIP']."\t".
					$RECORD['DESCRIP']."\n";
					
					$ENTRY['DESCRIP']=$RECORD['DESCRIP'];
					$ENTRY['DB_STATUS']='TO_UPD';
				}
				if (isset($RECORD['SYNONYM']))
				{
					
					foreach ($RECORD['SYNONYM'] as $S)
					{

						if (!isset($ENTRY['SYNONYM'][$S]))
						{
							$ENTRY['SYNONYM'][$S]=array('DB_STATUS'=>'TO_INS');
						}else $ENTRY['SYNONYM'][$S]['DB_STATUS']='VALID';
					}
				}

			}
			if ($RECORD['TAG']!='LSO_0000001' && !isset($RECORD['CHILDOF']))	failProcess($JOB_ID."A03",'No parent set for '.$TAG);
			if ($RECORD['TAG']=='LSO_0000001')continue;
			if (!isset($RECORD['CHILDOF'])) 									failProcess($JOB_ID."A04",'No parent set for '.$RECORD['TAG']);
			foreach ($RECORD['CHILDOF'] as $PARENT)
			{
				if (isset($RECORDS[$PARENT]))$RECORDS[$PARENT]['CHILD'][$RECORD['TAG']]=true;
				else $TMP_PARENT[$PARENT][]=$RECORD['TAG'];
			}
			++$N_REC;

			

			
		}
		fclose($fp);
		fclose($fpO);


		foreach ($TMP_PARENT as $PARENT=>&$LIST_CHILD)
		foreach ($LIST_CHILD as $C) 
		{

			if (!isset($RECORDS[$PARENT]))										failProcess($JOB_ID."A05",'Parent not found '.$PARENT);
			$RECORDS[$PARENT]['CHILD'][$C]=true;
		}

	


	}
	

	
	function genTree(&$DATA,$ROOTS,$LEVEL,&$LEVEL_V)
	{
		global $JOB_ID;
		global $FILES;
		global $FILE_STATUS;
		global $N_LEVEL;
		//global $fp;
		++$LEVEL;

		foreach ($ROOTS as $RID=>$T)
		{
			
			if (!isset($DATA[$RID])){echo $RID."\n";continue;}

			if ($LEVEL!=1)$LEVEL_V+=pow(10,10-$LEVEL);
			$LEVEL_LEFT=$LEVEL_V;
			if (isset($DATA[$RID]['CHILD'])){	genTree($DATA,$DATA[$RID]['CHILD'],$LEVEL,$LEVEL_V);$LEVEL_V+=pow(10,10-$LEVEL);}
			else $LEVEL_V+=100;
			//for($I=0;$I<$LEVEL;++$I)echo "\t";

			$LEVEL_RIGHT=$LEVEL_V;
			$FILE_STATUS['ontology_hierarchy']=true;
			fputs($FILES['ontology_hierarchy'],$DATA[$RID]['DBID']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n");
		}
	}
	


?>

