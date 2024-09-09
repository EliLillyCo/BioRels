<?php



/**
 SCRIPT NAME: wh_eco
 PURPOSE:     Process ECO ontology, push to DB and move to production
 
*/

/// Job name - Do not change
$JOB_NAME='wh_eco';

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
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_eco_rel')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);

	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Check the archive dir
	$ARCHIVE=$W_DIR.'/ARCHIVE';
	if (!is_dir($ARCHIVE) && !mkdir($ARCHIVE)) 											failProcess($JOB_ID."005",'Unable to create '.$ARCHIVE.' directory');
	
	
	if (!checkFileExist('eco.owl'))														failProcess($JOB_ID."007",'Unable to find eco.owl');


addLog("Working directory: ".$W_DIR);
	
	
addLog("Getting data from database");
	/// First we load all the data from the database
	/// We will use the ECO ID as the key
	/// And add a STATUS field to know if we need to update or insert
	$res=runQuery("SELECT eco_entry_Id,eco_id,eco_name,eco_description FROM eco_entry");
	if ($res===false)																	failProcess($JOB_ID."008",'Unable to fetch from database');
	$DATA=array();
	$MAX_DBID=-1;
	foreach ($res as $tab)
	{
		$DATA[$tab['eco_id']]=array(
			'DB'=>$tab['eco_entry_id'],
			'NAME'=>$tab['eco_name'],
			'DESC'=>$tab['eco_description'],
			'STATUS'=>'FROM_DB');

			/// MAX_DBID will be used to insert new records easily
		 if ($tab['eco_entry_id']>$MAX_DBID)$MAX_DBID=$tab['eco_entry_id'];
	}
	
	
addLog("Processing file");
	$fp=fopen('eco.owl','r');if (!$fp)													failProcess($JOB_ID."009",'Unable to open eco.owl');

/* FILE FORMAT:

  <owl:Class rdf:about="http://purl.obolibrary.org/obo/ECO_0000051">	=> START => ECO ID: ECO_0000051
        <rdfs:subClassOf rdf:resource="http://purl.obolibrary.org/obo/ECO_0000041"/>  => PARENT
        <obo:IAO_0000115 rdf:datatype="http://www.w3.org/2001/XMLSchema#string">A type of similarity based on genotype without respect to expression.</obo:IAO_0000115> => DESCRIPTION
        <oboInOwl:hasExactSynonym rdf:datatype="http://www.w3.org/2001/XMLSchema#string">IGTS</oboInOwl:hasExactSynonym>
        <oboInOwl:hasOBONamespace rdf:datatype="http://www.w3.org/2001/XMLSchema#string">eco</oboInOwl:hasOBONamespace>
        <oboInOwl:hasRelatedSynonym rdf:datatype="http://www.w3.org/2001/XMLSchema#string">inferred from genetic similarity</oboInOwl:hasRelatedSynonym>
        <oboInOwl:id rdf:datatype="http://www.w3.org/2001/XMLSchema#string">ECO:0000051</oboInOwl:id>
        <rdfs:comment rdf:datatype="http://www.w3.org/2001/XMLSchema#string">A genetic similarity analysis might consider genetic markers, polymorphisms, alleles, or other characteristics so
metimes considered as part of the field of traditional genetics. Although an attempt has been made to treat as distinct the concepts of &quot;genetic&quot;, &quot;genotypic&quot;, &quot;geno
mic&quot;, and &quot;sequence&quot;, there is considerable overlap in usage throughout the field of biology.</rdfs:comment>
        <rdfs:label rdf:datatype="http://www.w3.org/2001/XMLSchema#string">genetic similarity evidence</rdfs:label> => LABEL
    </owl:Class>
    <owl:Axiom>
        <owl:annotatedSource rdf:resource="http://purl.obolibrary.org/obo/ECO_0000051"/>
        <owl:annotatedProperty rdf:resource="http://purl.obolibrary.org/obo/IAO_0000115"/>
        <owl:annotatedTarget rdf:datatype="http://www.w3.org/2001/XMLSchema#string">A type of similarity based on genotype without respect to expression.</owl:annotatedTarget>
        <oboInOwl:hasDbXref rdf:datatype="http://www.w3.org/2001/XMLSchema#string">ECO:MCC</oboInOwl:hasDbXref>
        <oboInOwl:hasDbXref rdf:datatype="http://www.w3.org/2001/XMLSchema#string">PhenoScape:IGTS</oboInOwl:hasDbXref>
    </owl:Axiom>

	*/
	$STATS=array('ENTRY'=>0,'PARENT'=>0);

	$ROOTS=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n"); if ($line=='')continue;
		/// Each record is a block of lines, starting with <owl:Class
		if (strpos($line,'<owl:Class')===false)continue;
		
		/// Getting the ID
		$P1=strrpos($line,'/');
		$P2=strrpos($line,'"');
		$ID=substr($line,$P1+1,$P2-$P1-1);
		
		$N_OPEN=0;
		$DESC='';
		$NAME='';
		$PARENTS=array();

		/// Now that we are in a BLOCK we are going to process it until we reach the end of the block
		do
		{
			$line=stream_get_line($fp,1000,"\n");
			if (strpos($line,'IAO_0000115')!==false)$DESC=getInBetween($line,'obo:IAO_0000115');
			if (strpos($line,'<rdfs:label')!==false)$NAME=getInBetween($line,'rdfs:label');
			/// Getting parent
			if (strpos($line,'<rdfs:subClassOf')!==false)
			{
				$tstr=getTag($line,'rdf:resource');
				$PARENT=substr($tstr,strrpos($tstr,'/')+1);
				if ($PARENT!='' && substr($PARENT,0,4)=='ECO_')$PARENTS[]=$PARENT;
				
			}
			
			if (strpos($line,'<owl:Class')!==false)++$N_OPEN;
			if (strpos($line,'</owl:Class')!==false)
			{
				if ($N_OPEN==0)				break;
				else --$N_OPEN;
			}
		}while(!feof($fp));

		/// There's a lot of additional data, we are going to ignore them
		if (substr($ID,0,3)!='ECO')continue;
		$STATS['ENTRY']++;
		/// Test existence of this record against the database:
		if (!isset($DATA[$ID]) || !isset($DATA[$ID]['DB']))
		{
			++$MAX_DBID;
			$DATA[$ID]=array(
				'DB'=>$MAX_DBID,
				'NAME'=>$NAME,
				'DESC'=>$DESC,
				'STATUS'=>'TO_INS'/// We are going to insert this record
			);
		}
		/// Existing record, check if we need to update it.
		else  if ($DATA[$ID]['STATUS']!='TO_INS')
		{
			
			$DATA[$ID]['STATUS']='VALID';
			if ($NAME!=$DATA[$ID]['NAME'])
			{
				print_r($DATA[$ID]);
				echo '|'.$NAME.'|'.$DATA[$ID]['NAME']."|\n";
				$DATA[$ID]['NAME']=$NAME;
				$DATA[$ID]['STATUS']='TO_UPD';
			}
			if ($DESC!=$DATA[$ID]['DESC'])
			{
				$DATA[$ID]['DESC']=$DESC;
				$DATA[$ID]['STATUS']='TO_UPD';
			}
			
		}
		else {echo "ISSUE\n";exit;}

		/// No parent? So it's a root entry
		if ($PARENTS==array())$ROOTS[$ID]=true;
		/// Otherwise, add childs
		else foreach ($PARENTS as $C)
		{
			//echo $C."\n";
			$DATA[$C]['CHILD'][$ID]=true;
		}
	//
	}
	fclose($fp);


	
addLog("Pushing changes to database");
	foreach ($DATA as $ID=>$INFO)
	{
		//echo $ID."\t".$INFO['NAME']."\t".$INFO['DESC']."\n";	
		if ($INFO['STATUS']=='FROM_DB')
		{
			$QUERY=' DELETE FROM eco_entry WHERE eco_entry_id = '.$INFO['DB'].'';
			if (!runQueryNoRes($QUERY))									failProcess($JOB_ID."010",'Unable to run query '.$QUERY);
			
		}
		
		else if ($INFO['STATUS']=='TO_UPD')
		{
			echo $ID."\n";
			print_r($INFO);
			$QUERY=" UPDATE eco_entry 
					SET eco_name='".prepString($INFO['NAME'])."', 
					eco_description='".prepString($INFO['DESC'])."' 
					WHERE eco_entry_id = ".$INFO['DB'];
			if (!runQueryNoRes($QUERY))									failProcess($JOB_ID."011",'Unable to run query '.$QUERY);
		}
		
		else if ($INFO['STATUS']=='TO_INS')
		{
			
			$QUERY=' INSERT INTO eco_entry (eco_entry_id,eco_id,eco_name,eco_description) VALUES
					('.$INFO['DB'].",'".$ID."','".$INFO['NAME']."','".$INFO['DESC']."')".'';
			
			if (!runQueryNoRes($QUERY))									failProcess($JOB_ID."012",'Unable to run query '.$QUERY);
		}
	}



/// Create a nested tree
	addLog("Create tree");
	$fp=fopen('TREE.csv','w');if (!$fp)									failProcess($JOB_ID."013",'Unable to open TREE.csv');
	fputs($fp,"ECO_ENTRY_ID\tECO_LEVEL\tLEVEL_LEFT\tLEVEL_RIGHT\n");
	
	$VALUE=0;
	
	genTree($DATA,$ROOTS,0,$VALUE);
	fclose($fp);


	/// Now that we have the tree, we need to delete the previous version of the tree
	/// and load the new one


addLog("delete content of ECO_hierarchy tree");
	if (!runQueryNoRes("TRUNCATE TABLE eco_hierarchy"))					failProcess($JOB_ID."014",'Unable to truncate eco_hierarchy'); 
	
addLog("load tree");
	
	$FCAV_NAME='TREE.csv';
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.eco_hierarchy(eco_entry_id,eco_level,level_left,level_right)FROM \''.$FCAV_NAME."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV, HEADER )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )													failProcess($JOB_ID."015",'Unable to insert tree'); 


	updateStat('eco_entry','Evidence',$STATS['ENTRY'],$JOB_ID);


	if (!unlink('TREE.csv'))											failProcess($JOB_ID."016",'Unable to delete TREE.csv');


addLog("Push to prod");
	pushToProd();

	
	
successProcess();














function genTree(&$DATA,$ROOTS,$LEVEL,&$LEVEL_V)
{
	global $fp;
	++$LEVEL;
	foreach ($ROOTS as $RID=>$T)
	{
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


?>

