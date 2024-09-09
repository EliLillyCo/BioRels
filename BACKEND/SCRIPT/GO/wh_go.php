<?php
ini_set('memory_limit','3000M');

/**
 SCRIPT NAME: wh_go
 PURPOSE:     Processing gene ontology
 
*/

/// Job name - Do not change
$JOB_NAME='wh_go';


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
	///	Parent job:
	$CK_GO_INFO=$GLB_TREE[getJobIDByName('ck_go_rel')];

	/// Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_GO_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);

	$W_DIR.=getCurrDate();			   		   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create new process dir '.$W_DIR);
	if (!chdir($W_DIR)) 						failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=getCurrDate();

	/// Define archive:
	$ARCHIVE=$W_DIR.'/ARCHIVE';
	if (!is_dir($ARCHIVE) && !mkdir($ARCHIVE)) 											failProcess($JOB_ID."003",'Unable to create '.$ARCHIVE.' directory');
	
	/// Check if PRD_DIR path is set in CONFIG_GLOBAL
	$PRD_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'];
	if (!is_dir($PRD_DIR))			 													failProcess($JOB_ID."006",'Unable to find '.$PRD_DIR.' directory');
	  
	

	

	/// These are rules for the different relationships between go entries
	$RULE=array(
		"disjoint_from"=>         "DJF",
		"alt_id"=>                "ALT",
		"intersection_of"=>       "ITO",
		"inverse_of"=>            "IVO",
		"is_a"=>                  "ISA",
		"transitive_over"=>       "TRO",
		"ends_during"=>           "END",
		"has_part"=>              "HAP",
		"negatively_regulates"=>  "NER",
		"replaced_by"=>           "REB",
		"occurs_in"=>             "OCI",
		"part_of"=>               "PO" ,
		"positively_regulates"=>  "PR" ,
		"relationship"=>          "REL" ,
		"regulates"=>             "REG",
		"consider"=>              "CON",
		"happens_during"=>        "HAD",
	
	);

addLog("Download Go File");
	
	if (!isset($GLB_VAR['LINK']['FTP_GO']))												failProcess($JOB_ID."007",'FTP_GO path no set');
	

	if (!dl_file($GLB_VAR['LINK']['FTP_GO'].'/go.obo',3,'go.obo'))						failProcess($JOB_ID."008",'Unable to download gene ontology');



	
addLog("Get data from DB");
	$DATA=array();
	$MAXDBID=array();
	loadDataFromDB($DATA,$MAXDBID);

	$STATS=array(
		'ENTRY_GO'=>0,'ENTRY_DBREF'=>0,'ENTRY_REL'=>0,'ENTRY_SYN'=>0,'ENTRY_PMID'=>0,
		'DEL_GO'=>0,'UPD_GO'=>0,'INS_GO'=>0,'DEL_SYN'=>0,'INS_SYN'=>0,'DEL_REL'=>0,
		'INS_REL'=>0,'REL_MISS'=>0,'DEL_PMID'=>0,'INS_PMID'=>0,'DEL_DBREF'=>0,'INS_DBREF'=>0,
		'MAP_MISS'=>0,'MAP_FAIL'=>0,'MAP_SUCCESS'=>0);


addLog("Process File");
	$N_REC=0;
	$fp=fopen('go.obo','r'); if (!$fp)													failProcess($JOB_ID."009",'Unable to open Go.obo file');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		/// Each record is a set of lines starting with [Term]
		if ($line!='[Term]')continue;
		$RAW_DATA=array();

		while(!feof($fp))
		{

			///So we read each line
		$line=stream_get_line($fp,1000,"\n");
		/// Until we get to an empty line, signifying the end of the block
		if ($line=='')break;
		/// Each line has a tag, a header in a way at the beginning of the line
		$pos=strpos($line,':');
		$head=substr($line,0,$pos);
		/// So we create a hash array with $head as key and all lines for that header as values
		$RAW_DATA[$head][]=substr($line,$pos+2);
		}
		++$N_REC;

		$STATS['ENTRY_GO']++;
		processEntry($RAW_DATA);
		
	}

	fclose($fp);
	
	
	addLog("Update Database");
	
	updateDatabase($DATA);
	
	print_r($STATS);
	
	echo "MEM:".memory_get_usage()."\n";
	


	$CLEANUP=array('go_dbref.csv','go_entry.csv','go_pmid_map.csv','go_rel.csv','go_syn.csv');
	foreach ($CLEANUP as $file) if (!unlink($file))failProcess($JOB_ID."010",'Unable to delete '.$file); 

addLog("Push to prod");
	pushToProd();

	updateStat('go_entry','GO',$STATS['ENTRY_GO'],$JOB_ID);
	updateStat('go_dbref','GO_DBREF',$STATS['ENTRY_DBREF'],$JOB_ID);
	updateStat('go_syn','GO_SYN',$STATS['ENTRY_SYN'],$JOB_ID);

	successProcess();











	



function loadDataFromDB(&$DATA,&$MAXDBID)
{
	$res=runQuery("SELECT go_entry_id,ac,name,definition,namespace,comments,is_obsolete FROM go_entry");
	if ($res===false)			 																failProcess($JOB_ID."A01",'Unable to get GO Entries from database');
	
	$DATA=array();
	$MAXDBID=array('GO'=>0,'SYN'=>0,'DBREF'=>0,'REL'=>0,'pmid'=>0);
	foreach ($res as $line)
	{
		/// All records from the database will have their status set to FROM_DB by default
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['ac']]=$line;
		$DATA[$line['ac']]['SYN']=array();
		$DATA[$line['ac']]['REL']=array();
		$DATA[$line['ac']]['DBREF']=array();
		$DATA[$line['ac']]['pmid']=array();
		/// MAXDBID['GO']  will get the highest primary key value for GO_ENTRY
		/// that will be used for inserting new records
		$MAXDBID['GO']=max($MAXDBID['GO'],$line['go_entry_id']);
	}

	/// Getting synonyms

	$res=runQuery("SELECT ac, syn_value,go_syn_id,syn_type 
		FROM go_syn GS, go_entry GE 
		WHERE GE.go_entry_id = GS.go_entry_id");
	if ($res===false)			 																failProcess($JOB_ID."A02",'Unable to get GO SYN from database');
	
	foreach ($res as $line)
	{
		$t=array(
			'DB_STATUS'=>'FROM_DB',
			'syn_type'=>$line['syn_type'],
			'go_syn_id'=>$line['go_syn_id'],
			'syn_value'=>$line['syn_value']);
		$DATA[$line['ac']]['SYN'][]=$t;
		/// MAXDBID['SYN']  will get the highest primary key value for GO_SYN
		/// that will be used for inserting new records
		$MAXDBID['SYN']=max($MAXDBID['SYN'],$line['go_syn_id']);
	}


	/// Getting external references
	$res=runQuery("SELECT AC,go_dbref_id,source_name,gs.source_id,db_value 
		FROM go_dbref GS, go_entry GE,source s 
		WHERE GE.go_entry_id = GS.go_entry_id 
		and s.source_id = gs.source_id");
	if ($res===false)			 																failProcess($JOB_ID."A03",'Unable to get GO DBREF from database');
	
	foreach ($res as $line)
	{
		$t=array('DB_STATUS'=>'FROM_DB',
			'source_name'=>strtolower($line['source_name']),
			'go_dbref_id'=>$line['go_dbref_id'],
			'db_value'=>$line['db_value'],
			'source_id'=>$line['source_id']);
		$DATA[$line['ac']]['DBREF'][]=$t;
		$MAXDBID['DBREF']=max($MAXDBID['DBREF'],$line['go_dbref_id']);
	}

	/// Getting relationshipts
	$res=runQuery("SELECT GE.AC as ac_from, GT.AC as ac_to,rel_type,subrel_type,go_rel_id 
					FROM go_entry GE, go_rel GR, go_entry GT 
					WHERE GE.go_entry_id =GR.go_from_id
					AND GR.go_to_id = GT.go_entry_id");
	if ($res===false)			 																failProcess($JOB_ID."A04",'Unable to get GO REL from database');
	
	foreach ($res as $line)
	{
		$t=array(
			'DB_STATUS'=>'FROM_DB',
			'ac_to'=>$line['ac_to'],
			'go_rel_id'=>$line['go_rel_id'],
			'rel_type'=>$line['rel_type'],
			'subrel_type'=>$line['subrel_type']);
		$DATA[$line['ac_from']]['REL'][]=$t;
		$MAXDBID['REL']=max($MAXDBID['REL'],$line['go_rel_id']);
	}


	$res=runQuery("SELECT go_pmid_map_id, pmid,AC,go_def_type
					FROM go_pmid_map GPM, go_entry GE, pmid_entry PE
					WHERE GPM.pmid_entry_id = PE.pmid_entry_id
					AND GPM.go_entry_id = GE.go_entry_id");
	if ($res===false)			 																failProcess($JOB_ID."A05",'Unable to get GO pmid from database');
	
	foreach ($res as $line)
	{
		$t=array(
			'DB_STATUS'=>'FROM_DB',
			'pmid'=>$line['pmid'],
			'go_pmid_map_id'=>$line['go_pmid_map_id'],
			'go_def_type'=>$line['go_def_type']);
		$DATA[$line['ac']]['pmid'][]=$t;
		$MAXDBID['pmid']=max($MAXDBID['pmid'],$line['go_pmid_map_id']);
	}
}





function processEntry($RAW_DATA)
{
	global $STATS;
	global $DATA;
	global $MAXDBID;
	global $RULE;
	global $MAX_SOURCE_ID;
	
	/// We modify is_obsolete value to fit the database schema:
	if (isset($RAW_DATA['is_obsolete']))
	$RAW_DATA['is_obsolete']=(($RAW_DATA['is_obsolete'][0]=='true')?'T':'F');
	else $RAW_DATA['is_obsolete']='F';

	$pos=strpos($RAW_DATA['def'][0],'"',1);
	$RAW_DATA['annot_def']=substr($RAW_DATA['def'][0],$pos);
	$RAW_DATA['def']=substr($RAW_DATA['def'][0],1,$pos-1);
	
	$RECORD=null;
	/// The data is in the database? We compare the results
	if (isset($DATA[$RAW_DATA['id'][0]]))
	{
		$RECORD=&$DATA[$RAW_DATA['id'][0]];
		/// By default the database record is valid, unless some of its data has changed.
		/// then it this case we will set the database record to TO_UPD
		$RECORD['DB_STATUS']='VALID';

		if ($RECORD['name']!=$RAW_DATA['name'][0])
		{
			//echo $RAW_DATA['id'][0]."\tNAME\t|".$RECORD['NAME']."|\t|".$RAW_DATA['name'][0]."|\n";
			$RECORD['name']=$RAW_DATA['name'][0];
			$RECORD['DB_STATUS']='TO_UPD';
		}
		if ($RECORD['namespace']!=$RAW_DATA['namespace'][0])
		{
			//echo $RAW_DATA['id'][0]."\tNAMESPACE\t|".$RECORD['NAMESPACE']."|\t|".$RAW_DATA['namespace'][0]."|\n";
			$RECORD['namespace']=$RAW_DATA['namespace'][0];
			$RECORD['DB_STATUS']='TO_UPD';
		}
		if ($RECORD['definition']!=$RAW_DATA['def'])
		{
			//echo $RAW_DATA['id'][0]."\tDEFINITION\t|".$RECORD['DEFINITION']."|\t|".$RAW_DATA['def']."|\n";
			$RECORD['definition']=$RAW_DATA['def'];
			$RECORD['DB_STATUS']='TO_UPD';
		}
		
		if (isset($RAW_DATA['comment']) && $RECORD['comments']!=$RAW_DATA['comment'][0])
		{
			//echo $RAW_DATA['id'][0]."\tDEFINITION\t|".$RECORD['COMMENTS']."|\t|".$RAW_DATA['comment'][0]."|\n";
			$RECORD['comments']=$RAW_DATA['comment'][0];
			$RECORD['DB_STATUS']='TO_UPD';
		}
		if ($RECORD['is_obsolete']!=$RAW_DATA['is_obsolete'])
		{
			//echo $RAW_DATA['id'][0]."\tIS_OBSOLETE\t|".$RECORD['IS_OBSOLETE']."|\t|".$RAW_DATA['is_obsolete']."|\n";
			$RECORD['is_obsolete']=$RAW_DATA['is_obsolete'];
			$RECORD['DB_STATUS']='TO_UPD';
		}
	}
	else
	{
		/// Not in the database -> Create it
		++$MAXDBID['GO'];
		$DATA[$RAW_DATA['id'][0]]=array(
			'DB_STATUS'=>'TO_INS',
			'go_entry_id'=>$MAXDBID['GO'],
			'ac'=>$RAW_DATA['id'][0],
			'name'=>$RAW_DATA['name'][0],
			'definition'=>$RAW_DATA['def'],
			'namespace'=>$RAW_DATA['namespace'][0],
			'comments'=>isset($RAW_DATA['comment'])?$RAW_DATA['comment'][0]:'', 
			'is_obsolete'=>$RAW_DATA['is_obsolete'],
			'SYN'=>array(),
			'REL'=>array(),
			'DBREF'=>array(),
			'pmid'=>array());
		

		$RECORD=&$DATA[$RAW_DATA['id'][0]];
		
	}
	
	/// Now we process external references if any
	if (isset($RAW_DATA['xref']))
	foreach ($RAW_DATA['xref'] as $XR)
	{
		$tab=explode(" ",$XR);
		$pos=strpos($tab[0],":");
		/// external database name:
		$DBN=substr($tab[0],0,$pos);
		/// we Lowercase the database name to simplify the search for source and minimize duplicate
		$DBN_L=strtolower($DBN);
		$DBV=substr($tab[0],$pos+1);
		$STATS['ENTRY_DBREF']++;
		$FOUND=false;

		/// Then we compare the against existing records:
		foreach ($RECORD['DBREF'] as &$DBREF)
		{
			if ($DBREF['source_name']!=$DBN_L)continue;
			if ($DBREF['db_value']!=$DBV)continue;
			if ($DBREF['DB_STATUS']!='TO_INS')$DBREF['DB_STATUS']='VALID';
			$FOUND=true;
		}
		if ($FOUND)continue;

		$SOURCE_ID=getSource($DBN_L);


		/// And we create the corresponding record
		++$MAXDBID['DBREF'];
		$t=array('DB_STATUS'=>'TO_INS',
			'source_name'=>$DBN,
			'source_id'=>$SOURCE_ID, 
			'go_dbref_id'=>$MAXDBID['DBREF'],
			'db_value'=>$DBV);
		$RECORD['DBREF'][]=$t;


	}


	/// Then we are going to loop over all the distinct rules
	foreach ($RULE as $R=>$RID)
	{
		/// To see if one of them is present in the record
		if (!isset($RAW_DATA[$R]))continue;
		foreach($RAW_DATA[$R] as $line)
		{
			$tab=explode(" ",$line);
			$subT='';$GO_ID='';
			foreach ($tab as $K)
			{
				if (isset($RULE[$K]))$subT=$RULE[$K];
				if (substr($K,0,3)=='GO:')$GO_ID=$K;
			}
			$STATS['ENTRY_REL']++;
			$FOUND=false;
			/// Then we compare the input against the records in the database
			foreach ($RECORD['REL'] as &$REL)
			{

				//		

				if ($REL['ac_to']!=$GO_ID)continue;
				if ($REL['rel_type']!=$RID)continue;
				if ($REL['subrel_type']!=$subT)continue;
				$REL['DB_STATUS']='VALID';
				$FOUND=true;
				break;
			}
			if ($FOUND)continue;
			/// If it's not found we create the record
			++$MAXDBID['REL'];
			
			$t=array('DB_STATUS'=>'TO_INS',
				'ac_to'=>$GO_ID,
				'go_rel_id'=>$MAXDBID['REL'],
				'rel_type'=>$RID,
				'subrel_type'=>$subT);
			$RECORD['REL'][]=$t;
		}
		
	}

	

	/// Here we want extract publications from the annotation 
	//echo $RAW_DATA['annot_def']."\n";
	if (stripos($RAW_DATA['annot_def'],'pmid')!==false)
	{
		
		//	echo "######\n";
		$matches=array();
		/// So we run a regular expression to extract the PMIDs
		preg_match_all('/pmid:([0-9]{1,10})/i',$RAW_DATA['annot_def'],$matches);
		$STATS['ENTRY_PMID']+=count($matches[1]);
		foreach ($matches[1] as $pmid)
		{
			$FOUND=false;
			/// Compare to the records in the database
			foreach ($RECORD['pmid'] as &$REC_PMID)
			{
				if ($REC_PMID['pmid']!=$pmid)continue;
				if ($REC_PMID['go_def_type']!='DF')continue;
				$REC_PMID['DB_STATUS']='VALID';
				$FOUND=true;
				break;
			}
			if ($FOUND)continue;
			++$MAXDBID['pmid'];

			/// Not found? We search the database to get the primary key corresponding to the PMID
			$res=runQuery("SELECT pmid_entry_id FROM pmid_entry WHERE pmid=".$pmid);
			
			/// It can happens that we don't find it in the database, so we sadly ignore it
			if ($res===false || count($res)==0)continue;

			/// And we create the record.
			//echo $RAW_DATA['id'][0]."\tNEW pmid\t".$pmid."\tSY\n";
			$RECORD['pmid'][]=array('DB_STATUS'=>'TO_INS',
			'pmid'=>$pmid,
			'pmid_entry_id'=>$res[0]['pmid_entry_id'],
			'go_pmid_map_id'=>$MAXDBID['pmid'],
			'go_def_type'=>'DF');
		}
	}

	/// Now we process the synonyms
	if (isset($RAW_DATA['synonym']))
	foreach ($RAW_DATA['synonym'] as $syn)
	{
		$pos=strpos($syn,'"',1);
		
		$synv=substr($syn,1,$pos-1);
		$syn_type=substr($syn,$pos+2,strpos($syn,' ',$pos+3)-$pos-2);
		$syn_info=substr($syn,strpos($syn,' ',$pos+3));
		$STATS['ENTRY_SYN']++;
		
		/// Similarly, if there's a publication, we follow the same process
		if (stripos($syn_info,'pmid')!==false)
		{
		
			$matches=array();

			///regular expression
			preg_match_all('/pmid:([0-9]{1,10})/i',$syn_info,$matches);
			$STATS['ENTRY_PMID']+=count($matches[1]);
		

			foreach ($matches[1] as $pmid)
			{
				$FOUND=false;
				//echo $pmid."\n";
				//// Check if the records exist in the database
				foreach ($RECORD['pmid'] as &$REC_PMID)
				{

					//echo "\t".$REC_PMID['pmid']."\t".$REC_PMID['go_def_type']."\n";
					if ($REC_PMID['pmid']!=$pmid)continue;
					if ($REC_PMID['go_def_type']!='SY')continue;
					
					$REC_PMID['DB_STATUS']='VALID';
					$FOUND=true;
					break;
				}
				if ($FOUND)continue;
				/// Not found? We search the database to get the primary key corresponding to the PMID
				++$MAXDBID['pmid'];
				$res=runQuery("SELECT pmid_entry_id FROM pmid_entry WHERE pmid=".$pmid);
				// It can happen that we don't find it, so we ignore it
				if ($res===false || count($res)==0)continue;
				//echo $RAW_DATA['id'][0]."\tNEW pmid\t".$pmid."\tSY\n";

				///Create the publication record
				$RECORD['pmid'][]=array('DB_STATUS'=>'TO_INS',
				'pmid'=>$pmid,
				'pmid_entry_id'=>$res[0]['pmid_entry_id'],
				'go_pmid_map_id'=>$MAXDBID['pmid'],
				'go_def_type'=>'SY');
				
			}
		
		}
		/// Now we search if the synonym is found in the database
		$FOUND=false;
		foreach ($RECORD['SYN'] as &$SYN_REC)
		{
			//echo $synv.'|'.substr($syn_type,0,1).'|'.$SYN_REC['syn_value'].'|'.$SYN_REC['syn_type']."\n";
			if ($SYN_REC['syn_value']!=$synv)continue;
			if ($SYN_REC['syn_type']!=substr($syn_type,0,1))continue;
			$SYN_REC['DB_STATUS']='VALID';
			$FOUND=true;
			break;
		}
		
		if ($FOUND)continue;
		/// No? We create it.
		//echo $RAW_DATA['id'][0]."\tNEW SYNONYM\t|".$synv."|\t|".substr($syn_type,0,1)."|\n"; 
		$MAXDBID['SYN']++;
		$RECORD['SYN'][]=array('DB_STATUS'=>'TO_INS','syn_value'=>$synv,'syn_type'=>substr($syn_type,0,1),'go_syn_id'=>$MAXDBID['SYN']);
		
	}
}


function updateDatabase(&$DATA)
{

global $GLB_VAR;
global $DB_INFO;
global $JOB_ID;

	/// Those describes for each table, what is the column names order for the input files.
	$FINFO=array(
		'go_entry'		=>'(go_entry_id,ac,name,definition,namespace,comments,is_obsolete)',
		'go_syn'		=>'(go_syn_id,go_entry_id,syn_value,syn_type)',
		'go_rel'		=>'(go_rel_id,go_from_id,go_to_id,rel_type,	subrel_type)',
		'go_pmid_map'	=>'(go_pmid_map_id,go_entry_id,pmid_entry_id,go_def_type)',
		'go_dbref'		=> '(go_dbref_id,	go_entry_id,source_id,db_value)');

	/// Files will contain the output files for each table we want to insert into
	$FILES=array();
	foreach ($FINFO as $F=>$D)
	{
		$FILES[$F]=fopen($F.'.csv','w');
		if ($FILES[$F]==null)failProcess($JOB_ID."C01",'Unable to open file '.$F);
	}

	/// All records that we want to delete will be in there:
	$TO_DEL=array('GO_ENTRY'=>array(),'SYN'=>array(),'PMID'=>array(),'DBREF'=>array());

	/// Then we go over the all $DATA records,
	/// which contains all database AND input file records
	foreach ($DATA as &$ENTRY)
	{

	/// If the status is from_db, then it means we didn't find it in the file, so we delete it.
	if ($ENTRY['DB_STATUS']=='FROM_DB')
	{
		$TO_DEL['GO_ENTRY'][]=$ENTRY['go_entry_id'];

		$STATS['DEL_GO']++;
		continue;
	}
	/// New record -> write it in the file
	else if ($ENTRY['DB_STATUS']=='TO_INS')
	{
		fputs($FILES['go_entry'],
			$ENTRY['go_entry_id']."\t".
			$ENTRY['ac']."\t".
			$ENTRY['name']."\t".
			'"'.str_replace('"','""',$ENTRY['definition']).'"'."\t".
			'"'.str_replace('"','""',$ENTRY['namespace']).'"'."\t".
			'"'.str_replace('"','""',$ENTRY['comments']).'"'."\t".
			$ENTRY['is_obsolete']."\n");
		$STATS['INS_GO']++;
	}
	/// Record need to be updated -> Run the query
	else if ($ENTRY['DB_STATUS']=='TO_UPD')
	{
		$query="UPDATE go_entry SET 
		ac='".$ENTRY['ac']."',
		name='".str_replace("'","''",$ENTRY['name'])."',
		definition='".str_replace("'","''",$ENTRY['definition'])."',
		namespace='".$ENTRY['namespace']."',
		comments='".str_replace("'","''",$ENTRY['comments'])."',
		is_obsolete='".$ENTRY['is_obsolete']."' 
		WHERE go_entry_id=".$ENTRY['go_entry_id'];

		if (!runQueryNoRes($query))					failProcess($JOB_ID."C02",'Unable to update Go entry '.$ENTRY['AC']);
		$STATS['UPD_GO']++;
	}





	///Similar process for synonym
	foreach ($ENTRY['SYN'] as $SYN_N=>&$INFO)
	{
		if ($INFO['DB_STATUS']=='FROM_DB')
		{
			$TO_DEL['SYN'][]=$INFO['go_syn_id'];

			$STATS['DEL_SYN']++;
			continue;	
		}
		if ($INFO['DB_STATUS']=='TO_INS')
		{
			
			fputs($FILES['go_syn'],$INFO['go_syn_id']."\t".$ENTRY['go_entry_id']."\t".'"'.$INFO['syn_value'].'"'."\t".$INFO['syn_type']."\n");
			$STATS['INS_SYN']++;
			continue;	
		}
	}
		///Similar process for relationships
	foreach ($ENTRY['REL'] as &$INFO)
	{

		if ($INFO['DB_STATUS']=='FROM_DB')
		{
			if (!runQueryNoRes("DELETE FROM GO_REL 
				WHERE go_rel_id=".$INFO['go_rel_id']))						failProcess($JOB_ID."C03",'Unable to delete Go Rel '.$ENTRY['AC'].' '.$INFO['ac_to']);
			$STATS['DEL_REL']++;
			continue;	
		}
		if ($INFO['DB_STATUS']=='TO_INS')
		{
			//echo "IN\n";
			if (!isset($DATA[$INFO['ac_to']])){$STATS['REL_MISS']++;continue;}
			//print_r($INFO);
			$TO_DBID=$DATA[$INFO['ac_to']];
			//print_r($TO_DBID);
			fputs($FILES['go_rel'],
				$INFO['go_rel_id']."\t".
				$ENTRY['go_entry_id']."\t".
				$TO_DBID['go_entry_id']."\t".
				$INFO['rel_type']."\t".
				$INFO['subrel_type']."\n");
			$STATS['INS_REL']++;
			continue;		
		}
	}


	foreach ($ENTRY['pmid'] as &$INFO)
	{
		if ($INFO['DB_STATUS']=='FROM_DB')
		{
			$TO_DEL['PMID'][]=$INFO['go_pmid_map_id'];
			$STATS['DEL_PMID']++;
			continue;	
		}
		if ($INFO['DB_STATUS']=='TO_INS')
		{
			fputs($FILES['go_pmid_map'],
				$INFO['go_pmid_map_id']."\t".
				$ENTRY['go_entry_id']."\t".
				$INFO['pmid_entry_id']."\t".
				$INFO['go_def_type']."\n");
			$STATS['INS_PMID']++;
			continue;		
		}
	}



	foreach ($ENTRY['DBREF'] as &$INFO)
	{
		if ($INFO['DB_STATUS']=='FROM_DB')
		{
			$TO_DEL['DBREF'][]=$INFO['go_dbref_id'];

			$STATS['DEL_DBREF']++;
			continue;	
		}
		if ($INFO['DB_STATUS']=='TO_INS')
		{
			fputs($FILES['go_dbref'],
				$INFO['go_dbref_id']."\t".
				$ENTRY['go_entry_id']."\t".
				$INFO['source_id']."\t".
				'"'.str_replace('"','""',$INFO['db_value']).'"'."\n");
			$STATS['INS_DBREF']++;
			continue;		
		}
	}



	}

	/// Then here we check if there's any records to delete for each table and run the appropriate query
	if (count($TO_DEL['GO_ENTRY'])>0)
	{
		if (!runQueryNoRes("DELETE FROM go_entry WHERE go_entry_id IN (".implode(',',$TO_DEL['GO_ENTRY']).')'))failProcess($JOB_ID."C04",'Unable to delete Go entry ');
	}
	if (count($TO_DEL['SYN'])>0)
	{
	if (!runQueryNoRes("DELETE FROM go_syn WHERE go_syn_id IN (".implode(',',$TO_DEL['SYN']).')'))failProcess($JOB_ID."C05",'Unable to delete Go syn ');
	}
	if (count($TO_DEL['PMID'])>0)
	{
	if (!runQueryNoRes("DELETE FROM GO_PMID_MAP WHERE go_pmid_map_id IN (".implode(',',$TO_DEL['PMID']).')'))failProcess($JOB_ID."C06",'Unable to delete Go pmid ');
	}
	if (count($TO_DEL['DBREF'])>0)
	{
	if (!runQueryNoRes("DELETE FROM go_dbref WHERE go_dbref_id IN (".implode(',',$TO_DEL['DBREF']).')'))failProcess($JOB_ID."C06",'Unable to delete Go dbref ');
	}	 


	/// Now we loop over the files, close them, and run a psql query to insert them all inthe database
	foreach ($FILES as $N=>$V)
	{
		fclose($V);
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$N.' '.$FINFO[$N].' FROM \''.$N.'.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."C07",'Unable to insert '.$N); 
	}

}

?>



