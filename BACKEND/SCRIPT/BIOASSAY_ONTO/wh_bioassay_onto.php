<?php
$JOB_NAME='wh_bioassay_onto';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');


$JOB_ID=getJobIDByName($JOB_NAME);



addLog("Check directory");

	
	/// Get parent job info
	$CK_SEQ_ONTOL_INFO=$GLB_TREE[getJobIDByName('ck_bioassay_onto')];

	/// Get job info
	$JOB_INFO=$GLB_TREE[$JOB_ID];

	/// Get to the directory set by ck_bioassay_onto
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_SEQ_ONTOL_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."002",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR))																failProcess($JOB_ID."003",'Unable to chdir to '.$W_DIR);
	
	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=$CK_SEQ_ONTOL_INFO['TIME']['DEV_DIR'];

	/// We need to check if the file bao_complete_merged.owl is present
	$F_FILE=$W_DIR.'/bao_complete_merged.owl';	if (!checkFileExist($F_FILE))		failProcess($JOB_ID."004",'NO '.$F_FILE.' found ');
	
	

	$STATS=array('ENTRY'=>0);

	
addLog("Load data from database");
	/// We are going to load the data from the database
	$DATA=array();
	/// MAX_DBID is going to be used to create new records
	$MAX_DBID=0;
	$res=runQuery("SELECT bioassay_onto_entry_id,bioassay_tag_id,bioassay_label,bioassay_definition 
	 FROM bioassay_onto_entry");
	if ($res===false)																failProcess($JOB_ID."007",'Unable to fetch from database');
	
	foreach ($res as $tab)
	{
		$DATA[$tab['bioassay_tag_id']]=array(
			'DB'=>$tab['bioassay_onto_entry_id'],
			'NAME'=>$tab['bioassay_label'],
			'DESC'=>$tab['bioassay_definition'],
			'STATUS'=>'FROM_DB', /// STATUS is used to know if the record is in the file or not
		'EXTDB'=>array());
		/// Update MAX_DBID so we can create new records
		 if ($tab['bioassay_onto_entry_id']>$MAX_DBID)$MAX_DBID=$tab['bioassay_onto_entry_id'];
	}

addLog("Load data from file");
	/// This is an owl file, which is an xml file
	/// However, we are going to treat it as a text file
	$fp=fopen('bao_complete_merged.owl','r');if (!$fp)										failProcess($JOB_ID."008",'Unable to open bao_complete_merged.owl');
	$ROOTS=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		/// Each record start with class
		if (strpos($line,'<owl:Class')===false)continue;
		
		/// Extracting the ID:
		$P1=strrpos($line,'/');
		$P2=strrpos($line,'"');
		$ID=substr($line,$P1+5,$P2-$P1-5);
		if ($P1===false||$P2===false)continue;
		/// We only want BAO records
		if (substr($ID,0,4)!='BAO_'){continue;}
			
		$STATS['ENTRY']++;
		
		/// Initialize variables
		$N_OPEN=0;
		$DESC=array();
		$NAME='';
		$PARENTS=array();
		$VALID=true;
		$EXTDB=array();
		/// Since a record is a set of lines, we read all lines until we get to </owl:Class
		do
		{
			$line=stream_get_line($fp,1000,"\n");
			///Description text
			if (strpos($line,'IAO_0000115')!==false)$DESC[]=getInBetween($line,'obo:IAO_0000115')."\n";
			if (strpos($line,'<rdfs:label')!==false)$NAME=getInBetween($line,'rdfs:label');
			
			/// We don't want deprecated entries
			if (strpos($line,'owl:deprecated')!==false){$VALID=false;break;}

			/// Get the parent
			if (strpos($line,'<rdfs:subClassOf')!==false)
			{
				$tstr=getTag($line,'rdf:resource');
				$PARENT=substr($tstr,strrpos($tstr,'/')+1);
				if (substr($PARENT,0,7)!='bao#BAO')continue;
				$PARENT=substr($PARENT,4);
				if ($PARENT!=''&& $PARENT!='owl#Thing' )$PARENTS[]=$PARENT;
			
			}
			
			
			if (strpos($line,'<owl:Class')!==false)++$N_OPEN;
			if (strpos($line,'</owl:Class')!==false)
			{
				if ($N_OPEN==0)				break;
				else --$N_OPEN;
			}
		}while(!feof($fp));

		//Deprecated -> continue
		if (!$VALID)continue;

		// Not existing in the database -> create the record
		if (!isset($DATA[$ID]))
		{
			++$MAX_DBID;
			$DATA[$ID]=array('DB'=>$MAX_DBID,'NAME'=>$NAME,'DESC'=>$DESC,'STATUS'=>'TO_INS','EXTDB'=>$EXTDB);
			
		}
		/// Some records in DATA can be created because of the child/parent, but don't have a DB ID, in that case, we create the record
		else if (isset($DATA[$ID]) && !isset($DATA[$ID]['DB']))
		{
			++$MAX_DBID;
			$DATA[$ID]['DB']=$MAX_DBID;
			$DATA[$ID]['NAME']=$NAME;
			$DATA[$ID]['DESC']=$DESC;
			$DATA[$ID]['STATUS']='TO_INS';
			$DATA[$ID]['EXTDB']=$EXTDB;
		}
		else 
		{
			/// The record exist -> set to valid, unless some data has changed
			$DATA[$ID]['STATUS']='VALID';
			if ($NAME!=$DATA[$ID]['NAME']){
				$DATA[$ID]['NAME']=$NAME;
				
				$DATA[$ID]['STATUS']='TO_UPD';}
			if (implode("",$DESC)!=$DATA[$ID]['DESC']){
				
				$DATA[$ID]['DESC']=$DESC;
				

				$DATA[$ID]['STATUS']='TO_UPD';}
		
		}

		/// That record has no parent -> Root 
		/// We are going to use this information to create the nested set representation
		if ($PARENTS==array()){$ROOTS[$ID]=true;}
		else foreach ($PARENTS as $C)
		{
			
			//echo $NAME."\t".$C."\n";
			/// This is causing the creation of a record in $DATA without name/description. See case above
			$DATA[$C]['CHILD'][$ID]=true;
		}

	}
	
fclose($fp);


addLog("Create nested set representation");
	if ($ROOTS==array())														failProcess($JOB_ID."009",'No root found');

/// Create nested set representation that is going to assign boundary numbers.
//// Let's say that the root has for boundary 1 10.
//// The two childs:  A 2-5 and B 6-9
/// And the A has a child C 3-4
/// If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
//// By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
//// Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for theleft side and <10 for the right side, leading to A B and C.
	$fp=fopen('TREE.csv','w');if (!$fp)													failProcess($JOB_ID."010",'Unable to open TREE.csv');
	function genTree(&$DATA,$ROOTS,$LEVEL,&$LEVEL_V)
	{
		global $fp;
		++$LEVEL;
		foreach ($ROOTS as $RID=>$T)
		{
			if (!isset($DATA[$RID])){echo $RID."\n";continue;}
		
			++$LEVEL_V;$LEVEL_LEFT=$LEVEL_V;
			if (isset($DATA[$RID]['CHILD']))genTree($DATA,$DATA[$RID]['CHILD'],$LEVEL,$LEVEL_V);
			
			++$LEVEL_V;$LEVEL_RIGHT=$LEVEL_V;
			fputs($fp,$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n");
		}
	}
	$VALUE=0;

	genTree($DATA,$ROOTS,0,$VALUE);
	fclose($fp);

	//// $DATA now contains both the data from the database and from the file
	/// So we look at each of those to see what needs to be deleted, updated, inserted
	
	
addLog("Pushing changes to database");

	/// Open the file that is going to contain the new records
	$fpE=fopen('ENTRY.csv','w');if (!$fpE)														failProcess($JOB_ID."011",'Unable to open ENTRY.csv');
	$HAS_NEW_DATA=false;

	/// We are going to look at each record and see what we need to do
	foreach ($DATA as $ID=>$INFO)
	{
		/// FROM_DB => record is not in the file anymore -> DELETE IT	
		if ($INFO['STATUS']=='FROM_DB')
		{
			
			$QUERY=' DELETE FROM bioassay_onto_entry WHERE bioassay_onto_entry_id = '.$INFO['DB'].'';
			if (!runQueryNoRes($QUERY))														failProcess($JOB_ID."012",'Unable to run query '.$QUERY);
		}
		/// Update the record
		else if ($INFO['STATUS']=='TO_UPD')
		{
			
			$QUERY=" UPDATE bioassay_onto_entry SET 
			bioassay_tag_id ='".prepString($ID)."',
			bioassay_label ='".prepString($INFO['NAME'])."',
			bioassay_definition='".prepString(implode("",$INFO['DESC']))."' 
			WHERE bioassay_onto_entry_id = ".$INFO['DB'];
			if (!runQueryNoRes($QUERY))														failProcess($JOB_ID."013",'Unable to run query '.$QUERY);
		}
		/// New record -> in file
		else if ($INFO['STATUS']=='TO_INS')
		{
			$HAS_NEW_DATA=true;
			fputs($fpE,$INFO['DB']."\t".
			$ID."\t".'"'.
			str_replace('"','""',$INFO['NAME']).'"'."\t".'"'.
			str_replace('"','""',implode("",$INFO['DESC'])).'"'."\n");
			//print_r($INFO);
			
		}

		
	}

	fclose($fpE);


addLog("Pushing new records to database");
	if ($HAS_NEW_DATA)
	{
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.bioassay_onto_entry(bioassay_onto_entry_id,bioassay_tag_id,bioassay_label,bioassay_definition)FROM \''."ENTRY.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																failProcess($JOB_ID."014",'Unable to insert bioassay entry'); 
	}


addLog("delete content of bioassay_onto_hierarchy");

	if (!runQueryNoRes("TRUNCATE TABLE bioassay_onto_hierarchy"))							failProcess($JOB_ID."015",'Unable to truncate bioassay_onto_hierarchy'); 


addLog("Pushing tree to database");
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.bioassay_onto_hierarchy(bioassay_onto_entry_id,bioassay_onto_level,bioassay_onto_level_left,bioassay_onto_level_right)FROM \'TREE.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV, HEADER )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )																	failProcess($JOB_ID."016",'Unable to insert tree'); 



updateStat('bioassay_onto_entry','bioassay_onto',$STATS['ENTRY'],$JOB_ID);
	 


addLog("Delete obsolete files");
 	$list_files=array('TREE.csv','ENTRY.csv');
 	foreach ($list_files as $F)
 	if (is_file($F))unlink($F);

	
addLog("Push to prod");
   pushToProd();
	
	

successProcess();

?>
