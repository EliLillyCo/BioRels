<?php

ini_set('memory_limit','500M');

/**
 SCRIPT NAME: db_mondo_tree
 PURPOSE:     Add EFO entries to MONDO (if Open targets is used) and create the hierarchy
 
*/

/// Job name - Do not change
$JOB_NAME='db_mondo_tree';


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

	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_mondo_rel')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	  
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
addLog("Working directory:".$W_DIR);
	

addLog("Setting up database id/files");
	
	///$DBIDS contains as key the list of tables we are going to insert new records into
	/// the vlaues of DBIDS are the max primary value for each of those tables.
	$DBIDS=array('disease_entry'=>-1,
				'disease_extdb'=>-1,
				'disease_syn'=>-1);

	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_ID) CO FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."005",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}

				
	/// Getting all the disease entries from the database
	$DATA=array();
	$res=runQuery("SELECT disease_entry_id,
		disease_tag,
		disease_name,
		disease_definition,S.source_id,source_name  
	FROM disease_entry DE,source S 
	WHERE S.source_id = DE.source_id");
	if ($res===false)																	failProcess($JOB_ID."006",'Unable to fetch from database');
	
	foreach ($res as $tab)
	{
		$DATA[$tab['disease_tag']]=array(
			'DB'=>$tab['disease_entry_id'],
			'NAME'=>$tab['disease_name'],
			'DESC'=>$tab['disease_definition'],
			'SOURCE'=>$tab['source_name'],
			'STATUS'=>'FROM_DB',
			'EXTDB'=>array(),
			'SYN'=>array());
	}
echo count($DATA).' diseases in the database'."\n";


addLog("Check & Process entries from Open Targets");
	
	$OT_INFO=$GLB_TREE[getJobIDByName('pp_ot_eco')];
	/// If Open targets is not used or has not been run: DEV_DIV is set to -1 so we do not process it
	if ($OT_INFO['TIME']['DEV_DIR']!=-1)processOpenTargets($OT_INFO);
	



addLog("Create tree");
	/// Getting the missing relationships
	$fp=fopen('RELATIONS.csv','r');if (!$fp)											failProcess($JOB_ID."007",'Unable to open RELATIONS.csv file');
	$ROOTS=array();
	$N_REL=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,300,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		++$N_REL;
	if ($tab[1]=='ROOT')$ROOTS[$tab[0]]=true;
		else $DATA[$tab[1]]['CHILD'][$tab[0]]=true;
	}
	fclose($fp);
		echo " Number of relationships: ".$N_REL."\n";
		

addLog("Create tree");

	$fp=fopen('TREE.csv','w');if (!$fp)											failProcess($JOB_ID."008",'Unable to open TREE.csv file');
	
	/// We are going to use a nested set representation to store the hierarchy. This will generate left and right boundaries for each node using this $VALUE
	$VALUE=0;

	/// We force the root to be MONDO_0000001
	$ROOTS=array();
	$ROOTS['MONDO_0000001']=true;
	
	/// We are going to generate the tree
	genTree($DATA,$ROOTS,0,$VALUE);
	fclose($fp);

	if ($N_REL==0)																	failProcess($JOB_ID."009",'No relationship found');


addLog("Delete and insert hierarchy");
	/// Delete the hierarchy
	if (!runQueryNoRes('TRUNCATE TABLE disease_hierarchy'))							failProcess($JOB_ID."010",'Unable to truncate DISEASE_HIERARCHY');
	$res=array();

	/// And insert the new one
	
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.disease_hierarchy(disease_entry_Id,disease_level,disease_level_left,disease_level_right) FROM \''."TREE.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."011",'Unable to insert disease_hierarchy'); 
	

	/// Check if the hierarchy has been inserted
	$res=runQuery("SELECT count(*) co from disease_hierarchy");
	if ($res[0]['co']==0)															failProcess($JOB_ID.'012','No disease hierarchy');

addLog("Delete non disease information");
	$res=runQueryNoRes("DELETE FROM disease_entry where disease_entry_id not IN (
			SELECT dh.disease_entry_id FROM disease_hierarchy dh,
			disease_hierarchy droot,
			disease_entry de
			Where de.disease_tag='MONDO_0000001'
			AND DE.DISEASE_ENTRY_ID = Droot.DISEASE_ENTRY_ID
			AND DH.DISEASE_LEVEL_LEFT>=DROOT.DISEASE_LEVEL_LEFT AND DH.DISEASE_LEVEL_RIGHT <= DROOT.DISEASE_LEVEL_RIGHT)");
	if ($res===false)																failProcess($JOB_ID."013",'Unable to delete non disease information');


successProcess();











function processOpenTargets($OT_INFO)
{
	global $JOB_ID,$TG_DIR,$GLB_VAR,$SOURCES,$DBIDS,$DATA;


	$OT_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$OT_INFO['DIR'].'/'.$OT_INFO['TIME']['DEV_DIR'];

	if (!is_dir($OT_DIR)) 																failProcess($JOB_ID."A01",'Unable to access Open target dir '.$OT_DIR);
	if (!is_file($OT_DIR.'/EFO_TREE_REL.csv')) 											failProcess($JOB_ID."A02",'Unable to find EFO_TREE_REL.csv in '.$OT_DIR);
	$fp=fopen($OT_DIR.'/EFO_TREE_REL.csv','r');if (!$fp)								failProcess($JOB_ID."A03",'Unable to open EFO_TREE_REL.csv');
	
	
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");if( $line=='')continue;
		$tab=explode("\t",$line);



		// Two situations can occur:
		/// 1/ The EFO record is a child of a MONDO record -> we add the record and associate it as a child of the mondo record
		/// 2/ The EFO record is already existing in the MONDO database, then we add it as an external identifier
		if ($tab[1]=='PARENT')
		{
			///check if this record has already been pushed in the database or not 
			if (!isset($DATA[$tab[0]]))
			{
				/// No, we insert it.
				/// So first we ge the EFO Tag:
				++$DBIDS['disease_entry'];
				$res=runQuery("SELECT efo_tag_id,efo_label,efo_definition 
							FROM efo_entry WHERE efo_tag_id='".$tab[0]."'");
							if ($res===false)									failProcess($JOB_ID."A04",'Unable to get EFO Tag');

			/// So we can insert the disease entry record
				$QUERY=' INSERT INTO disease_entry
				(disease_entry_id,disease_tag,disease_name,disease_definition,source_id) 
				VALUES
				('.$DBIDS['disease_entry'].",'".
				$tab[0]."','".
				str_replace("'","''",$res[0]['efo_label'])."','".
				str_replace("'","''",$res[0]['efo_definition'])."',".
				getSource("EFO").")";
				if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."A05",'Unable to run query '.$QUERY);

				/// We add the record to the DATA array
				$DATA[$tab[0]]=array(
					'DB'=>$DBIDS['disease_entry'],
					'NAME'=>$res[0]['efo_label'],
					'DESC'=>$res[0]['efo_definition'],
					'SOURCE'=>'EFO');

			}
			/// Then we add this record as child of other disease entries
			for ($I=3; $I<count($tab);++$I)
			{
				if ($tab[$I]=='NULL')continue;
				$DATA[$tab[$I]]['CHILD'][$tab[0]]=true;
			}

		}
	
		else if ($tab[1]=='DIRECT')
		{
			if ($tab[2]=='')continue;
			
			++$DBIDS['disease_extdb'];

			/// Finding the disease entry id
			$res=runQuery("SELECT disease_entry_id 
			FROM disease_entry 
			WHERE disease_tag='".$tab[2]."'");if ($res===false)							failProcess($JOB_ID."A06",'Unable to get DISEASE ENTRY');
			if (count($res)==0)continue;
			$disease_entry_id=$res[0]['disease_entry_id'];

			///Getting the source name
			$SOURCE_NAME=strtolower(explode("_",$tab[0])[0]);
			

			/// Checking if this record has already been pushed in the database or not
			$res=runQuery("SELECT * FROM disease_extdb 
			WHERE disease_entry_id=".$disease_entry_id." 
			AND source_id=".getSource($SOURCE_NAME)." 
			AND disease_extdb='".explode("_",$tab[0])[1]."'");if ($res===false)			failProcess($JOB_ID."A07",'Unable to get DISEASE EXTDB');
			if (count($res)>0)continue;

			/// Inserting the record
			$query='INSERT INTO disease_extdb (disease_extdb_id,disease_entry_id,source_id,disease_extdb) 
			VALUES ('.$DBIDS['disease_extdb'].','.
			$disease_entry_id.",".
			getSource($SOURCE_NAME).",'".
			explode("_",$tab[0])[1]."')";if ($res===false)								failProcess($JOB_ID."A08",'Unable to get DISEASE ENTRY');

			if (!runQueryNoRes($query))													failProcess($JOB_ID."A09",'Unable to run query '.$query);
		}
	}
	fclose($fp);
}



/// Create nested set representation that is going to assign boundary numbers.
//// Let's say that the root has for boundary 1 10.
//// The two childs:  A 2-5 and B 6-9
/// And the A has a child C 3-4
/// If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
//// By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
//// Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for theleft side and <10 for the right side, leading to A B and C.
	
function genTree(&$DATA,$ROOTS,$LEVEL,&$LEVEL_V)
{
	global $fp;
	++$LEVEL;
	foreach ($ROOTS as $RID=>$T)
	{
		
		if (!isset($DATA[$RID])){echo "ENTRY NOT FOUND : ".$RID."\n";continue;}
		
		++$LEVEL_V;$LEVEL_LEFT=$LEVEL_V;
		if (isset($DATA[$RID]['CHILD']))genTree($DATA,$DATA[$RID]['CHILD'],$LEVEL,$LEVEL_V);
		
		++$LEVEL_V;$LEVEL_RIGHT=$LEVEL_V;
		
		if (!isset($DATA[$RID]['DB']) || $DATA[$RID]['DB']=='') 
		{
			echo "DATABASE ENTRY NOT FOUND : ".$RID."\n";return ;
			failProcess($JOB_ID."B01",'Database ID not found for '.$RID."\n");
		}
		fputs($fp,$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n");
	}
}
?>

