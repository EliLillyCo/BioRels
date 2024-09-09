<?php

ini_set('memory_limit','500M');


/**
 SCRIPT NAME: wh_efo
 PURPOSE:     Process EFO ontology, push to DB, move to production
 
*/

/// Job name - Do not change
$JOB_NAME='wh_efo';

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

	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_efo_rel')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 						failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	/// Take parent directory
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; if (!is_dir($W_DIR) && !mkdir($W_DIR))		 				failProcess($JOB_ID."004",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR))		 							failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	
	 /// Update the process control so that the next job can access the directory 
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	

	
	if (!checkFileExist('efo.owl'))																	failProcess($JOB_ID."008",'Unable to find efo.owl');

addLog("Working directory: ".$W_DIR);
	

	
addLog("Getting data from database");
	
	$STATS=array('ENTRY'=>0,'EXTDB'=>0);


	/// First we get the existing data from the database
	$DATA=array();$MAX_DBID=0;
	$res=runQuery("SELECT efo_entry_id,efo_tag_id,efo_label,is_org_class,efo_definition,efo_id  FROM efo_entry");
	if ($res===false)																				failProcess($JOB_ID."009",'Unable to fetch from database');
	
	foreach ($res as $tab)
	{
		$DATA[$tab['efo_tag_id']]=array(
			'DB'=>$tab['efo_entry_id'],
			'NAME'=>$tab['efo_label'],
			'DESC'=>$tab['efo_definition'],
			'STATUS'=>'FROM_DB',	/// FROM_DB means that the record is from the database
			'EXTDB'=>array());
		///$MAX_DBID is used to easily add new records
		 if ($tab['efo_entry_id']>$MAX_DBID)$MAX_DBID=$tab['efo_entry_id'];
	}


	/// Then we get all external identifiers from the database
	$res=runQuery("SELECT EE.efo_entry_id,efo_extdb_id,S.source_id,efo_extdb_name,efo_Tag_id, source_name
	 FROM efo_extdb EE, efo_entry EF ,source S
	 WHERE EF.efo_entry_id = EE.efo_entry_id AND S.source_id = EE.source_id");
	if ($res===false)																				failProcess($JOB_ID."010",'Unable to fetch from database');
	
	
	$MAX_EXTDBID=0;	// Get Max primary key value for EFO_EXTDB table so that we can add new records easily
	foreach ($res as $tab)
	{
		$MAX_EXTDBID=max($MAX_EXTDBID,$tab['efo_extdb_id']);
		$DATA[$tab['efo_tag_id']]['EXTDB'][strtolower($tab['source_name'])][$tab['efo_extdb_name']]=array('DBID'=>$tab['efo_extdb_id'],'STATUS'=>'FROM_DB');
	}
	
	
addLog("Processing file");

	/// Now we process the file.
	$fp=fopen('efo.owl','r');if (!$fp)																failProcess($JOB_ID."011",'Unable to open eco.owl');
	$ROOTS=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')	continue;
		/// Each EFO record starts with a <owl:Class
		if (strpos($line,'<owl:Class')===false)continue;

		/// Getting the identifier
		$P1=strrpos($line,'/');
		$P2=strrpos($line,'"');
		$ID=substr($line,$P1+1,$P2-$P1-1);
		if ($P1===false||$P2===false)continue;
		
		$N_OPEN=0;
		$DESC=array();
		$NAME='';
		$PARENTS=array();
		$VALID=true;
		$EXTDB=array();
		do
		{
			$line=stream_get_line($fp,1000,"\n");
			if (strpos($line,'IAO_0000115')!==false)$DESC[]=getInBetween($line,'obo:IAO_0000115')."\n";
			if (strpos($line,'<rdfs:label')!==false)$NAME=getInBetween($line,'rdfs:label');
			if (strpos($line,'owl:deprecated')!==false){$VALID=false;break;}

			/// Subclass of provides the parent of this entry
			if (strpos($line,'<rdfs:subClassOf')!==false)
			{
				$tstr=getTag($line,'rdf:resource');
				$PARENT=substr($tstr,strrpos($tstr,'/')+1);
				if ($PARENT!='' //&& substr($PARENT,0,4)=='ECO_'
				)
				{
					if ($PARENT!='owl#Thing')
					$PARENTS[]=$PARENT;
				}
				 //rdf:resource="http://identifiers.org/mesh/C535421"/>	
			}

			///External identifiers
			else if (strpos($line,'<oboInOwl:hasDbXref')!==false)
			{
				$V=getInBetween($line,'oboInOwl:hasDbXref');
				//echo $line." ".$V."\n";
				if ($V=='')continue;
				$tab=explode(":",$V);
				if (count($tab)==1)$tab=explode(" ",$V);
				if (count($tab)==1)continue;
				$EXT_DBN=trim($tab[0]);
				
				//// Checking if the source is in the database
				getSource($EXT_DBN);
				
				$EXTDB[strtolower($EXT_DBN)][$tab[1]]=array('DBID'=>-1,'STATUS'=>'TO_INS');
				
				
			}
			
			if (strpos($line,'<owl:Class')!==false)++$N_OPEN;
			if (strpos($line,'</owl:Class')!==false)
			{
				if ($N_OPEN==0)				break;
				else --$N_OPEN;
			}
		}while(!feof($fp));

		/// If it's deprecated, we don't process it
		if (!$VALID)continue;

		/// Getting stats
		$STATS['ENTRY']++;	
		foreach ($EXTDB as $K=>$V) $STATS['EXTDB']+=count($V);
		
		
		
		/// Since the description can be from various sources, 
		//// we compare them against one another to check if they are similar enough. If so, we take the longest one
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
		$DESC=implode("\n",array_unique($DESC));
	

		/// Then we check if this record is already in the database or not:
		if (!isset($DATA[$ID]))
		{
			++$MAX_DBID;
			$DATA[$ID]=array(
				'DB'=>$MAX_DBID,
				'NAME'=>$NAME,
				'DESC'=>$DESC,
				'STATUS'=>'TO_INS',
				'EXTDB'=>$EXTDB);
			
		}
		/// In some instance, a record is created because of it is a parent of another record
		/// Those incomplete records don't have a DB id
		else if (isset($DATA[$ID]) && !isset($DATA[$ID]['DB']))
		{
			++$MAX_DBID;
			$DATA[$ID]['DB']=$MAX_DBID;
			$DATA[$ID]['NAME']=$NAME;
			$DATA[$ID]['DESC']=$DESC;
			$DATA[$ID]['STATUS']='TO_INS';
			$DATA[$ID]['EXTDB']=$EXTDB;
		}
		/// Record exist -> Check if anything changed.
		else 
		{
			//if ($DATA[$ID]['STATUS']=='TO_INS'){print_r($DATA[$ID]);echo $line."\n";exit;}
			
			/// Since we found the record in the file, we can consider it valid
			$DATA[$ID]['STATUS']='VALID';
			if ($NAME!=$DATA[$ID]['NAME'])
			{
				//echo "IDD".$NAME.'/'.$DATA[$ID]['NAME']."\n";
				$DATA[$ID]['NAME']=$NAME;	
				$DATA[$ID]['STATUS']='TO_UPD';
			}
			if ($DESC!=$DATA[$ID]['DESC'])
			{
				$DATA[$ID]['DESC']=$DESC;	
				$DATA[$ID]['STATUS']='TO_UPD';
			}
		
				
			/// Then we compare external identifiers against the database
			foreach ($EXTDB as $F_DB_NAME=>&$F_LIST)
			{

				$FOUND_D=false;
				foreach ($DATA[$ID]['EXTDB'] as $D_DB_NAME=>&$D_LIST)
				{
					/// First we compare the source
					if (strtolower($D_DB_NAME)!=strtolower($F_DB_NAME))continue;
					$FOUND_D=true;
					
					/// Then the values
					foreach ($F_LIST as $F_VAL=>$F_INFO)
					{	
						$FOUND=false;
						foreach ($D_LIST as $D_VAL=>&$D_INFO)
						{
							//echo "\t".$D_VAL."\n";
							if ($F_VAL!=$D_VAL)continue;
							//echo "IN\n";
							$FOUND=true;
							$D_INFO['STATUS']='VALID';
							break;
						}
						if ($FOUND)continue;
						
						/// Source exist but not the external identifier -> add
						$D_LIST[$F_VAL]=$F_INFO;

					}
					
					break;
				}
				if ($FOUND_D)continue;
				/// Source  AND external identifier never added to this record-> add
				$DATA[$ID]['EXTDB'][$F_DB_NAME]=$F_LIST;
			}

		}
		//HP_0001651 fyler 110
		
		if ($PARENTS==array())$ROOTS[$ID]=true;
		else foreach ($PARENTS as $C)
		{
			//echo $NAME."\t".$C."\n";
			$DATA[$C]['CHILD'][$ID]=true;
		}

		

	//
	}
	fclose($fp);


addLog("Create tree representation");
	/// Create nested set representation that is going to assign boundary numbers.
	//// Let's say that the root has for boundary 1 10.
	//// The two childs:  A 2-5 and B 6-9
	/// And the A has a child C 3-4
	/// If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
	//// By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
	//// Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for theleft side and <10 for the right side, leading to A B and C.

	$fp=fopen('TREE.csv','w');if (!$fp)				failProcess($JOB_ID."012",'Unable to open TREE.csv');
	function genTree(&$DATA,$ROOTS,$LEVEL,&$LEVEL_V)
	{
		global $fp;
		++$LEVEL;
		foreach ($ROOTS as $RID=>$T)
		{
			if (!isset($DATA[$RID])){echo $RID."\n";continue;}
			//if (!isset($DATA[$RID]['DB'])){echo "DB:".$RID."\n";continue;}
		//	for($I=0;$I<$LEVEL;++$I)echo "\t";
			//echo "PROCESSING ".$RID."\n";
			++$LEVEL_V;$LEVEL_LEFT=$LEVEL_V;
			if (isset($DATA[$RID]['CHILD']))genTree($DATA,$DATA[$RID]['CHILD'],$LEVEL,$LEVEL_V);
			//for($I=0;$I<$LEVEL;++$I)echo "\t";

			++$LEVEL_V;$LEVEL_RIGHT=$LEVEL_V;
			//echo $RID."\t".$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n";
			fputs($fp,$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n");
		}
	}
	$VALUE=0;
	genTree($DATA,$ROOTS,0,$VALUE);
	fclose($fp);
	

	print_r($STATS);



	
addLog("Pushing changes to database");
	/// Those are the 2 main files we are going to fill with new records
	$fpE=fopen('ENTRY.csv','w');if (!$fpE)														failProcess($JOB_ID."013",'Unable to open ENTRY.csv');
	$fpD=fopen('EXTDB.csv','w');if (!$fpD)														failProcess($JOB_ID."014",'Unable to open EXTDB.csv');


	$TO_DEL=array('ENTRY'=>array(),'EXTDB'=>array());

	/// Now we look at all records to see what need to happen
	foreach ($DATA as $ID=>&$INFO)
	{
		//echo $ID."\t".$INFO['NAME']."\t".$INFO['DESC']."\n"
		
		/// Record is from the database and wasn't found in the file -> Delete
		if ($INFO['STATUS']=='FROM_DB')
		{
			//print_r($INFO);
			$TO_DEL['ENTRY'][]=$INFO['DB'];
			
		}
		else if ($INFO['STATUS']=='TO_UPD')
		{
			$QUERY=" UPDATE efo_entry SET 
			efo_tag_id ='".prepString($ID)."',
			efo_label ='".prepString($INFO['NAME'])."',
			is_org_class ='T',
			efo_definition='".prepString($INFO['DESC'])."' WHERE efo_entry_id = ".$INFO['DB'];
			if (!runQueryNoRes($QUERY))															failProcess($JOB_ID."015",'Unable to run query '.$QUERY);
		}
		else if ($INFO['STATUS']=='TO_INS')
		{
			fputs($fpE,
				$INFO['DB']."\t".
				$ID."\t".
				'"'.str_replace('"','""',$INFO['NAME']).'"'."\t".
				"T\t".
				'"'.str_replace('"','""',$INFO['DESC']).'"'."\t".
				'""'."\n");
			
		}

		/// Then we look at the external identifiers
		foreach ($INFO['EXTDB'] as $DB_N=>&$LIST_N)
		{
			foreach ($LIST_N as $DB_V=>$INFO_E)
			{
				if ($INFO_E['STATUS']=='TO_INS')
				{
				
					++$MAX_EXTDBID;
					
					fputs($fpD,$MAX_EXTDBID."\t".$INFO['DB']."\t".getSource($DB_N)."\t".'"'.str_replace('"','""',utf8_decode($DB_V)).'"'."\n");
				}
				else if ($INFO_E['STATUS']=='FROM_DB')
				{
					$TO_DEL['EXTDB'][]=$INFO_E['DBID'];
				}
			}
		}
	}
	
	fclose($fpE);
	fclose($fpD);

addLog("Deleting records");

	if ($TO_DEL['ENTRY']!=array())
	{
		$QUERY=' DELETE FROM efo_entry WHERE efo_entry_id IN ('.implode(',',$TO_DEL['ENTRY']).')';
			if (!runQueryNoRes($QUERY))																failProcess($JOB_ID."016",'Unable to run query '.$QUERY);
	}
	if ($TO_DEL['EXTDB']!=array())
	{
		$QUERY=' DELETE FROM efo_extdb WHERE efo_extdb_id IN ('.implode(',',$TO_DEL['EXTDB']).')';
			if (!runQueryNoRes($QUERY))																failProcess($JOB_ID."017",'Unable to run query '.$QUERY);
	}


addLog("Inserting records");
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.efo_entry(efo_entry_id,efo_tag_id,efo_label, is_org_class,efo_definition,efo_id)FROM \''."ENTRY.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )																			failProcess($JOB_ID."018",'Unable to insert efo entry'); 


	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.efo_extdb(efo_extdb_id,efo_entry_Id,source_id,efo_extdb_name)FROM \''."EXTDB.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )																			failProcess($JOB_ID."019",'Unable to insert efo extdb'); 
		
	addLog("delete content of EFO tree");

	if (!runQueryNoRes("TRUNCATE TABLE efo_hierarchy"))												failProcess($JOB_ID."020",'Unable to truncate efo_hierarchy'); 
	

addLog("load tree");
	
	$FCAV_NAME='TREE.csv';
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.efo_hierarchy(efo_entry_id,efo_level,efo_level_left,efo_level_right)FROM \''.$FCAV_NAME."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV, HEADER )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )																			failProcess($JOB_ID."021",'Unable to insert tree'); 



addLog("Push to prod");
	pushToProd();

   
	successProcess();




?>

