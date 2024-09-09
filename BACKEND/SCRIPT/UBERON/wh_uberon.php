<?php

ini_set('memory_limit','500M');

/**
 SCRIPT NAME: wh_uberon
 PURPOSE:     Process uberon - push to production
 
*/
$JOB_NAME='wh_uberon';

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

addLog("Setting up directory");
	/// Gettting the parent job info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_uberon_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 						failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; if (!is_dir($W_DIR) && !mkdir($W_DIR))		 				failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR))		 							failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	///	Setting up the process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];


addLog("Working directory: ".$W_DIR);
	
	 
	if (!checkFileExist('uberon.owl'))																failProcess($JOB_ID."005",'Unable to find uberon.owl');
	



addLog("Getting data from database");
	

	/// Get the max primary key value for each table of interest
	/// This allow us to quickly create new records 
	$DBIDS=array('anatomy_entry'=>0,
	'anatomy_extdb'=>0,
	'anatomy_syn'=>0);

	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)										failProcess($JOB_ID."006",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}
	print_r($DBIDS);

	
	/// Just statistics of the different operations:
	$STATS=array('ENTRY'=>0,'VALID_ENTRY'=>0,'UPDATED_ENTRY'=>0,'DELETED_ENTRY'=>0,'INSERTED_ENTRY'=>0,'NONAME_ENTRY'=>0,
	'SYN'=>0,'VALID_SYN'=>0,'UPDATED_SYN'=>0,'DELETED_SYN'=>0,'INSERTED_SYN'=>0,
	'EXTDB'=>0,'VALID_EXTDB'=>0,'UPDATED_EXTDB'=>0,'DELETED_EXTDB'=>0,'INSERTED_EXTDB'=>0);

	
	/// Download the data already existing from the database
	$DATA=loadFromDB();



addLog("Processing file");

	$fp=fopen('uberon.owl','r');if (!$fp)													failProcess($JOB_ID."007",'Unable to open uberon.owl');
	$ROOTS=array();$NL=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");++$NL;
		
		/// Each record start with <owl:Class
		if (strpos($line,'<owl:Class')===false)continue;
		if (strpos($line,'/>')!==false)continue;
		
		/// Getting the Identifier:
		$P1=strrpos($line,'/');
		$P2=strrpos($line,'"');
		$ID=substr($line,$P1+1,$P2-$P1-1);
		if (strpos($ID,'gene_symbol_report')!==false)continue;
		if ($P1===false||$P2===false)continue;
		//echo $P1.' '.$p2.' '.$ID."\n";
		
		
		$N_OPEN=0;
		$DESC=array();
		$NAME='';
		$PARENTS=array();
		$VALID=true;
		$EXTDB=array();
		$SYN=array();

		/// We add the ID as an external identifier
		$tab=explode("_",$ID);
		if (count($tab)==2)
		{
			/// Create the source record if it's not in the database
			/// Otherwise, load its Id
			getSource(trim($tab[0]));
			
			$STATS['EXTDB']++;
			$EXTDB[]=array($tab[0],$tab[1],-1,'TO_INS');
		}

		/// So we read the record
		do
		{
			$line=stream_get_line($fp,1000,"\n");++$NL;
			
			/// IAO_0000115 is the tag for description
			if (strpos($line,'IAO_0000115')!==false)$DESC[]=getInBetween($line,'obo:IAO_0000115')."\n";
			if (strpos($line,'<rdfs:label')!==false)$NAME=getInBetween($line,'rdfs:label');
			
			/// Deprecated record - skip it
			if (strpos($line,'owl:deprecated')!==false)
			{
				$VALID=false;
				break;
			}
			if (strpos($line,'<rdfs:subClassOf')!==false)
			{
				$tstr=getTag($line,'rdf:resource');
				$PARENT=substr($tstr,strrpos($tstr,'/')+1);
				if ($PARENT!='' && $PARENT!='owl#Thing')
				{
					$PARENTS[]=$PARENT;
				}
				 //rdf:resource="http://identifiers.org/mesh/C535421"/>	
			}
			/// External reference
			else if (strpos($line,'<oboInOwl:hasDbXref')!==false)
			{
				$V=getInBetween($line,'oboInOwl:hasDbXref');
				//echo $line." ".$V."\n";
				if ($V=='')continue;
				$tab=explode(":",$V);
				if (count($tab)==1)$tab=explode(" ",$V);
				if (count($tab)==1)continue;
				$EXT_DBN=trim($tab[0]);
				getSource($EXT_DBN);

				/// Sometimes, the external reference is duplicated
				/// So we check:
				$FOUND=false;
				foreach ($EXTDB as $D)
				{
					if ($D[0]==$tab[0] &&$D[1]==$tab[1])$FOUND=true;
				}
				if ($FOUND)continue;
				$STATS['EXTDB']++;
				$EXTDB[]=array($tab[0],$tab[1],-1,'TO_INS');
				
				
			}
			else if (strpos($line,'<oboInOwl:hasExactSynonym')!==false)
			{
				$V=getInBetween($line,'oboInOwl:hasExactSynonym');
				$STATS['SYN']++;
				$SYN[]=array($V,'EXACT',-1,'TO_INS');
			}
			else if (strpos($line,'<oboInOwl:hasBroadSynonym')!==false)
			{
				$STATS['SYN']++;
				$V=getInBetween($line,'oboInOwl:hasBroadSynonym');
				$SYN[]=array($V,'BROAD',-1,'TO_INS');
			}
			else if (strpos($line,'<oboInOwl:hasRelatedSynonym')!==false)
			{
				$STATS['SYN']++;
				$V=getInBetween($line,'oboInOwl:hasRelatedSynonym');
				$SYN[]=array($V,'RELATED',-1,'TO_INS');
			}
			else if (strpos($line,'<oboInOwl:hasNarrowSynonym')!==false)
			{
				$STATS['SYN']++;
				$V=getInBetween($line,'oboInOwl:hasNarrowSynonym');
				$SYN[]=array($V,'NARROW',-1,'TO_INS');
			}
			if (strpos($line,'<owl:Class')!==false)++$N_OPEN;
			if (strpos($line,'</owl:Class')!==false)
			{
				if ($N_OPEN==0)				break;
				else --$N_OPEN;
			}
		}while(!feof($fp));
		
		$STATS['ENTRY']++;
		/// If it's depreciated we ignore the entry.
		if (!$VALID)
		{
			$STATS['INVALID_ENTRY']++;
			$STATS['INVALID_SYN']+=count($SYN);
			$STATS['INVALID_EXTDB']+=count($EXTDB);
			continue;
		}
	
		

		//	print_r($EXTDB);

		/// Many descriptions can be provided for a given entry.
		/// Some descriptions are coming from different sources, yet are almost identical description
		//// So to remove redundancy, we do pairwise comparison, looking for close match, and taking the longest one.
		sort($DESC);
		for ($I=0;$I<count($DESC);++$I)
		for ($J=0;$J<count($DESC);++$J)
		{
			if ($I==$J)continue;
			similar_text($DESC[$I],$DESC[$J],$perc);
			if ($perc<90)continue;
			$M=max(strlen($DESC[$I]),strlen($DESC[$J]));
			if (strlen($DESC[$I])==$M)unset($DESC[$J]);
			else unset($DESC[$I]);
			$DESC=array_values($DESC);
			$I=0;$J=0;break;
		
		}
		/// Then we merge it into a single Description text
		$DESC=implode("\n",array_unique($DESC));
	
		if ($NAME=='')
		{
			$STATS['INVALID_ENTRY']++;
				$STATS['INVALID_SYN']+=count($SYN);
				$STATS['INVALID_EXTDB']+=count($EXTDB);
			continue;
		}

		
		/// Now that we have all data for a given record, we check if it's in the database or not.
		if (!isset($DATA[$ID]))
		{
			///Not in the database, then create all records
			++$DBIDS['anatomy_entry'];
			$STATS['INSERTED_ENTRY']++;
			foreach ($EXTDB as &$S)
			{
				$STATS['INSERTED_EXTDB']++;
				
				++$DBIDS['anatomy_extdb'];
				$S[2]=$DBIDS['anatomy_extdb'];;
			}
			foreach ($SYN as &$S)
			{
				$STATS['INSERTED_SYN']++;
				++$DBIDS['anatomy_syn'];
				$S[2]=$DBIDS['anatomy_syn'];;
			}
			$DATA[$ID]=array(
				'DB'=>$DBIDS['anatomy_entry'],
				'NAME'=>$NAME,
				'DESC'=>$DESC,
				'STATUS'=>'TO_INS',
				'EXTDB'=>$EXTDB,
				'SYN'=>$SYN);
		}
		/// Some entries can be created in the $DATA array without being the database yet because we also stored parent-child relationship
		/// So we check that the field DB is in the entry, which ensure that this entry is actually in the database
		/// And if not, we create the record
		else if (isset($DATA[$ID]) && !isset($DATA[$ID]['DB']))
		{
			
			$STATS['INSERTED_ENTRY']++;
			++$DBIDS['anatomy_entry'];
			
			foreach ($EXTDB as &$S)
			{
				$STATS['INSERTED_EXTDB']++;
				++$DBIDS['anatomy_extdb'];
				$S[2]=$DBIDS['anatomy_extdb'];;
			}
			foreach ($SYN as &$S)
			{
				$STATS['INSERTED_SYN']++;
				++$DBIDS['anatomy_syn'];
				$S[2]=$DBIDS['anatomy_syn'];;
			}
			$DATA[$ID]['DB']=$DBIDS['anatomy_entry'];;
			$DATA[$ID]['NAME']=$NAME;
			$DATA[$ID]['SYN']=$SYN;
			$DATA[$ID]['DESC']=$DESC;
			$DATA[$ID]['STATUS']='TO_INS';
			$DATA[$ID]['EXTDB']=$EXTDB;
			
		}
		else 
		{
			// By default, we set the database record to valid, unless some data is changed.
			$DATA[$ID]['STATUS']='VALID';

			/// Then we check if the name and description are the same as in the database

			if ($NAME!=$DATA[$ID]['NAME'])
			{
				//echo "IDD".$NAME.'/'.$DATA[$ID]['NAME']."\n";
				$DATA[$ID]['NAME']=$NAME;
				$DATA[$ID]['STATUS']='TO_UPD';
			}
			if ($DESC!=$DATA[$ID]['DESC'])
			{
				$DATA[$ID]['DESC']=$DESC;
				//echo "IDD".$DESC.'/'.$DATA[$ID]['DESC']."\n";
				$DATA[$ID]['STATUS']='TO_UPD';
			}
		
			if ($DATA[$ID]['STATUS']=='VALID')$STATS['VALID_ENTRY']++;
			else $STATS['UPDATED_ENTRY']++;


			/// We also compare the external database identifiers against the records in the database
			foreach ($EXTDB as $EDB)
			{
				$FOUND=false;
				foreach ($DATA[$ID]['EXTDB'] as &$ER)
				{
					//echo $ER[0]."::".$EDB[0]."\t".$ER[1]."::".$EDB[1]."\n";

					/// Source is compared lowercase
					if (strtolower($ER[0])!=strtolower($EDB[0]))continue;
					if ($ER[1]!=$EDB[1])continue;
					$STATS['VALID_EXTDB']++;
					$FOUND=true;
					$ER[3]='VALID';
					break;
				}
				
				if ($FOUND)continue;
				$STATS['INSERTED_EXTDB']++;
				++$DBIDS['anatomy_extdb'];
				$EDB[2]=$DBIDS['anatomy_extdb'];
				$DATA[$ID]['EXTDB'][]=$EDB;
			}
			foreach ($SYN as $EDB)
			{
				$FOUND=false;
				foreach ($DATA[$ID]['SYN'] as &$ER)
				{
					
					if ($ER[0]!=$EDB[0])continue;
					if ($ER[1]!=$EDB[1])continue;
					$STATS['VALID_SYN']++;
					$FOUND=true;
					$ER[3]='VALID';
					break;
				}
				
				if ($FOUND)continue;
				
				$STATS['INSERTED_SYN']++;
				++$DBIDS['anatomy_syn'];
				$EDB[2]=$DBIDS['anatomy_syn'];
				$DATA[$ID]['SYN'][]=$EDB;
			}


		}
		
		
		/// Here we check if that record has any parent. If not, it's the root of the hierarchy
		if ($PARENTS==array())$ROOTS[$ID]=true;
		else foreach ($PARENTS as $C)
		{
			//echo $NAME."\t".$C."\n";
			$DATA[$C]['CHILD'][$ID]=true;
		}	
		
	//
	}
	fclose($fp);
	
	//print_r($DATA);exit;

	
addLog("Pushing changes to database");
	pushToDB($DATA);
	
addLog("Update hierarchy");
	$ROOTS=array('UBERON_0001062'=>true);
	updateHierarchy($DATA,$ROOTS);

	updateStat('anatomy_entry','anatomy',$STATS['ENTRY']-$STATS['INVALID_ENTRY'],$JOB_ID);
	updateStat('anatomy_syn','anatomy syn',$STATS['SYN']-$STATS['INVALID_SYN'],$JOB_ID);
	updateStat('anatomy_extdb','anatomy extdb',$STATS['EXTDB']-$STATS['INVALID_EXTDB'],$JOB_ID);




	$list_files=array(
		'anatomy_entry.csv',
		'anatomy_ext.csv',
		'anatomy_syn.csv',
		'RELATIONS.csv',
		'TREE.csv'      );
	foreach ($list_files as $F)
	if (checkFileExist($F) && !unlink($F))															failProcess($JOB_ID."008",'Unable to delete '.$F);


		if (!runQueryNoRes(" DELETE FROM anatomy_entry where anatomy_entry_Id NOT IN (
			SELECT distinct ah2.anatomy_entry_Id 
			FROM anatomy_entry ae1, anatomy_hierarchy ah1 , anatomy_hierarchy ah2, anatomy_entry ae2 
			where ae1.anatomy_entry_Id = ah1.anatomy_entry_Id  
			AND ae1.anatomy_tag='UBERON_0001062' 
			and ae2.anatomy_entry_Id = ah2.anatomy_Entry_id 
			AND ah1.anatomy_level_left <=ah2.anatomy_level_left 
			and ah2.anatomy_level_right >=ah2.anatomy_level_right)"))	failProcess($JOB_ID."009",'Unable to delete orphan entries');


addLog("Push to prod");
   pushToProd();
   
	successProcess();














/// Create nested set representation that is going to assign boundary numbers.
//// Let's say that the root has for boundary 1 10.
//// The two childs:  A 2-5 and B 6-9
/// And the A has a child C 3-4
/// If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
//// By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
//// Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for theleft side and <10 for the right side, leading to A B and C.

function genTree(&$DATA,$ROOTS,$LEVEL,&$LEVEL_V,&$fp)
{
	
	++$LEVEL;
	foreach ($ROOTS as $RID=>$T)
	{
		if (!isset($DATA[$RID])){echo $RID."\n";continue;}
		//if (!isset($DATA[$RID]['DB'])){echo "DB:".$RID."\n";continue;}
	//	for($I=0;$I<$LEVEL;++$I)echo "\t";
	//	echo "PROCESSING ".$RID."\n";
		++$LEVEL_V;$LEVEL_LEFT=$LEVEL_V;
		if (isset($DATA[$RID]['CHILD']))genTree($DATA,$DATA[$RID]['CHILD'],$LEVEL,$LEVEL_V,$fp);
		//for($I=0;$I<$LEVEL;++$I)echo "\t";

		++$LEVEL_V;$LEVEL_RIGHT=$LEVEL_V;
		echo "HIERARCHY\t".$LEVEL."\t".$RID."\t".$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n";
		fputs($fp,$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n");
	}
}


function loadFromDB()
{
	global $JOB_ID;
	$DATA=array();
	$SOURCE_ID=getSource("UBERON");


	/// Getting anatomy records:
	$res=runQuery("SELECT anatomy_entry_id,
	anatomy_tag,
	anatomy_name,
	anatomy_definition  
	FROM anatomy_entry 
	WHERE source_id=".$SOURCE_ID);
	if ($res===false)																				failProcess($JOB_ID."A01",'Unable to fetch from database');
	
	foreach ($res as $tab)
	{
		$DATA[$tab['anatomy_tag']]=array(
			'DB'=>$tab['anatomy_entry_id'],
			'NAME'=>$tab['anatomy_name'],
			'DESC'=>$tab['anatomy_definition'],
			'STATUS'=>'FROM_DB',
			'EXTDB'=>array(),
			'SYN'=>array()
		);
		 
	}

	/// Getting anatomy external references
	$res=runQuery("SELECT anatomy_tag, anatomy_extdb_id,
	EE.anatomy_entry_ID,
	S.source_id,
	anatomy_extdb,
	source_name
	 FROM anatomy_extdb EE, anatomy_entry EF , source S
	 WHERE EF.anatomy_entry_ID = EE.anatomy_entry_id 
	 AND S.source_id = EE.source_id");// 
	if ($res===false)																			failProcess($JOB_ID."A02",'Unable to fetch external id from database');
	
	foreach ($res as $tab)
		$DATA[$tab['anatomy_tag']]['EXTDB'][]=array(
			strtolower($tab['source_name']),
			$tab['anatomy_extdb'],
			$tab['anatomy_extdb_id'],
			'FROM_DB');
	


	///Getting anatomy synonyms
	$res=runQuery("SELECT anatomy_tag, syn_type,syn_value,anatomy_syn_id
		FROM anatomy_syn EE, anatomy_entry EF 
		WHERE EF.anatomy_entry_ID = EE.anatomy_entry_id 
		AND  EE.source_id =".$SOURCE_ID);// 
	if ($res===false)																	failProcess($JOB_ID."A03",'Unable to fetch synonyms from database');
	

	foreach ($res as $tab)
		$DATA[$tab['anatomy_tag']]['SYN'][]=array(
			$tab['syn_value'],
			$tab['syn_type'],
			$tab['anatomy_syn_id'],
			'FROM_DB');
	
	return $DATA;
}



function pushToDB(&$DATA)
{
	global $JOB_ID;
	global $GLB_VAR;
	global $DB_INFO;
	global $DBIDS;
	global $STATS;


	$FILE_STATUS=array('ENTRY'=>false,'SYN'=>false,'EXTDB'=>false);
	$N=0;
		
	/// Now we open files to push new records in those files
	$fpO=fopen('anatomy_entry.csv','w');if (!$fpO)					failProcess($JOB_ID."B01",'Unable to open anatomy_entry.csv');
	$fpS=fopen('anatomy_syn.csv','w');if (!$fpS)					failProcess($JOB_ID."B02",'Unable to open anatomy_syn.csv');
	$fpE=fopen('anatomy_ext.csv','w');if (!$fpE)					failProcess($JOB_ID."B03",'Unable to open anatomy_ext.csv');

	/// Array with the id of the records to delete
	$TO_DEL=array('ENTRY'=>array(),'EXTDB'=>array(),'SYN'=>array());

	/// $DATA now contains both the data from the database and from the file
	/// So we look at each of those to see what needs to be deleted, updated, inserted
	foreach ($DATA as $ID=>&$INFO)
	{
		
		++$N; if ($N%1000==0)echo $N."\t".count($DATA)."\n";
		//echo $ID."\t".$INFO['NAME']."\t".$INFO['DESC']."\n";	
		if (!isset($INFO['STATUS']))
		{
			echo $ID." MISSING STATUS\n";
			continue;
		}
		if ($INFO['STATUS']=='FROM_DB')
		{
			$TO_DEL['ENTRY'][]=$INFO['DB'];
		} 
		else if ($INFO['STATUS']=='TO_UPD')
		{
			
			$QUERY=' UPDATE anatomy_entry SET 
			anatomy_tag =\''.$ID.'\',
			anatomy_name =\''.str_replace("'","''",$INFO['NAME']).'\',
			anatomy_definition=\''.str_replace("'","''",$INFO['DESC']).'\' 
			WHERE anatomy_entry_id = '.$INFO['DB'];
			
			if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."B04",'Unable to run query '.$QUERY);
		}else if ($INFO['STATUS']=='TO_INS')
		{
			$FILE_STATUS['ENTRY']=true;
			fputs($fpO,
				$INFO['DB']."\t".
				$ID."\t".
				'"'.str_replace('"','""',$INFO['NAME']).'"'."\t".
				'"'.str_replace('"','""',$INFO['DESC']).'"'."\t".
				getSource("UBERON")."\n");
			
		}

		foreach ($INFO['EXTDB'] as &$EX)
		{
			if ($EX[3]=='TO_INS')
			{
				$FILE_STATUS['EXTDB']=true;
				fputs($fpE,
					$EX[2]."\t".
					$INFO['DB']."\t".
					getSource($EX[0])."\t".
					'"'.$EX[1].'"'."\n");
			}
			else if ($EX[3]=='FROM_DB')$TO_DEL['EXTDB'][]=$EX[2];
			
		}
		foreach ($INFO['SYN'] as &$EX)
		{
			
			if ($EX[3]=='TO_INS')
			{
				$FILE_STATUS['SYN']=true;
				fputs($fpS,
					$EX[2]."\t".
					$INFO['DB']."\t".
					$EX[0]."\t".
					$EX[1]."\t".
					getSource("UBERON")."\n");
			}
			else if ($EX[3]=='FROM_DB')$TO_DEL['SYN'][]=$EX[2];
			
		}
	}



	fclose($fpO);
	fclose($fpS);
	fclose($fpE);

	if ($TO_DEL['ENTRY']!=array())
	{
		addLog("DELETE ENTRIES");
		$STATS['DEL_ENTRY']=count($TO_DEL['ENTRY']);
		$QUERY=' DELETE FROM anatomy_entry 
				WHERE anatomy_entry_id IN ('.implode(',',$TO_DEL['ENTRY']).')';
		if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."B05",'Unable to run query '.$QUERY);
	}

	if ($TO_DEL['EXTDB']!=array())
	{
		addLog("DELETE EXTDB");
		$STATS['DEL_EXTDB']=count($TO_DEL['EXTDB']);
		print_r($TO_DEL['EXTDB']);
		$QUERY=' DELETE FROM anatomy_extdb WHERE anatomy_extdb_id IN ('.implode(',',$TO_DEL['EXTDB']).')';
		
		if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."B06",'Unable to run query '.$QUERY);
	}
	if ($TO_DEL['SYN']!=array())
	{
		addLog("DELETE SYN");
		$STATS['DEL_SYN']=count($TO_DEL['SYN']);
		$QUERY=' DELETE FROM anatomy_syn WHERE anatomy_syn_id IN ('.implode(',',$TO_DEL['SYN']).')';
		echo $QUERY."\n";
		if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."B07",'Unable to run query '.$QUERY);
	}
	print_r($STATS);

	print_r($DBIDS);

	

	addLog("Insert content in table");
	if ($FILE_STATUS['ENTRY'])
	{
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.anatomy_entry (anatomy_entry_id,anatomy_tag,anatomy_name,anatomy_definition,source_id) FROM \''."anatomy_entry.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."B08",'Unable to insert anatomy_entry'); 
	}

	if ($FILE_STATUS['EXTDB'])
	{
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.anatomy_extdb (anatomy_extdb_id,anatomy_entry_id,source_id,anatomy_extdb)  FROM \''."anatomy_ext.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."B09",'Unable to insert anatomy_exct'); 
	}	

	if ($FILE_STATUS['SYN'])
	{
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.anatomy_syn (anatomy_syn_id,anatomy_entry_id,syn_value,syn_type,source_id)  FROM \''."anatomy_syn.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."B10",'Unable to insert anatomy_syn'); 
	}		

}



function updateHierarchy(&$DATA,&$ROOTS)
{
	global $JOB_ID;
	global $GLB_VAR;
	global $DB_INFO;
	$fp=fopen('TREE.csv','w');if (!$fp)					failProcess($JOB_ID."C01",'Unable to open TREE.csv');
	
	$VALUE=0;
	genTree($DATA,$ROOTS,0,$VALUE,$fp);
	fclose($fp);

	
	addLog("delete content of anatomy hierarchy");
	if (!runQueryNoRes("TRUNCATE TABLE anatomy_hierarchy"))failProcess($JOB_ID."C02",'Unable to truncate anatomy_hierarchy'); 
	
	addLog("load tree");
	
	$FCAV_NAME='TREE.csv';
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.anatomy_hierarchy(anatomy_entry_id,anatomy_level,anatomy_level_left,anatomy_level_right)FROM \''.$FCAV_NAME."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV, HEADER )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."C03",'Unable to insert tree'); 

	
}
?>
