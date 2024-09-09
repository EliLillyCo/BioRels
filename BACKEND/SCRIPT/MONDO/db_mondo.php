<?php
//error_reporting(E_ALL);
//error_reporting(0);
ini_set('memory_limit','500M');

/**
 SCRIPT NAME: db_mondo
 PURPOSE:     Process mondo.owl file and insert new records in the database
 
*/

/// Job name - Do not change
$JOB_NAME='db_mondo';


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
	$W_DIR.='/'.$CK_INFO['DIR'].'/';     if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	if (!chdir($W_DIR)) 																failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);


	if (!checkFileExist('mondo.owl'))													failProcess($JOB_ID."005",'Unable to find eco.owl');

	/// Update the process control so that the next job can access the directory
	  $PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	  
addLog("Working directory:".$W_DIR);

	/// disease_syn.csv is a static file that contains rules to add missing synonyms to diseases
	$fp=fopen($TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/DISEASE/disease_syn.csv','r');
	if (!$fp)																		failProcess($JOB_ID."006",'Unable to open disease syn file ');
	$SYN_ADD_RULES=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;
		$tab=explode("\t",$line);
		$SYN_ADD_RULES[$tab[0]][]=$tab[1];
	}
	fclose($fp);

	
addLog("Find dependent table");

	/// Because some taxons can be merged, we need to be able to redirect records from other tables accordingly
	/// Using foreign key constraints, we are going to find all tables having a column referencing taxon_id
	$DEP_TABLES=array();
	$DEP_TABLES=getDepTables('disease_entry',$GLB_VAR['DB_SCHEMA']);
	 
	  

	$SOURCE_ID=getSource("MONDO");
	
	
addLog("Getting data from database");
	///$DBIDS contains as key the list of tables we are going to insert new records into
	/// the vlaues of DBIDS are the max primary value for each of those tables.
	$DBIDS=array('disease_entry'=>0,
				'disease_extdb'=>0,
				'disease_syn'=>0,
			'disease_anatomy_map'=>0);

	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)								failProcess($JOB_ID."007",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}


	

addLog("Load data from database");
	$DATA=loadDataFromDB();

addLog("Processing file");
	
		$STATS=array('ENTRY'=>0,'INSERT_ENTRY'=>0,'DELETE_ENTRY'=>0,'UPDATED_ENTRY'=>0,'OBSOLETE_ENTRY'=>0,'VALID_ENTRY'=>0,
		'SYN'=>0,'INSERT_SYN'=>0,'DELETE_SYN'=>0,'UPDATE_SYN'=>0,'VALID_SYN'=>0,
		'EXTDB'=>0,'INSERT_EXTDB'=>0,'DELETE_EXTDB'=>0,'UPDATE_EXTDB'=>0,'VALID_EXTDB'=>0);


	$DISEASE_ANATOMY_MAP=array();

	processFile($DATA);

	if ($DISEASE_ANATOMY_MAP!=array()) pushAnatomy($DATA,$DISEASE_ANATOMY_MAP);


	
addLog("Pushing changes to database");
	pushToDB($DATA);


successProcess();












function loadDataFromDB()
{

	$SOURCE_ID=getSource("MONDO");
	
	
	/// First we load all the diseases from the database
	$DATA=array();
	$res=runQuery("SELECT disease_entry_id,
	disease_tag,
	disease_name,
	disease_definition ,
	source_id
	 FROM disease_entry");
	if ($res===false)																	failProcess($JOB_ID."A01",'Unable to fetch entries from database');
	
	foreach ($res as $tab)
	{
		$DATA[$tab['disease_tag']]=array(
			'DB'=>$tab['disease_entry_id'],
			'NAME'=>$tab['disease_name'],
			'DESC'=>$tab['disease_definition'],
			'SOURCE'=>$tab['source_id'],
			'STATUS'=>'FROM_DB',
			'EXTDB'=>array(),
			'SYN'=>array(),
		'ANATOMY'=>array());
		 
	}


	/// We also retrive all diseases from other sources
	$ALT_ENTRIES=array();
	$res=runQuery("SELECT disease_entry_id,
	disease_tag,
	disease_name,
	disease_definition  FROM disease_entry WHERE source_id!=".$SOURCE_ID);
	if ($res===false)																	failProcess($JOB_ID."A02",'Unable to fetch alternative entries from database');
	
	foreach ($res as $tab)
	{
		$ALT_ENTRIES[$tab['disease_tag']]=array(
			'DB'=>$tab['disease_entry_id'],
			'NAME'=>$tab['disease_name'],
			'DESC'=>$tab['disease_definition'],
			'STATUS'=>'FROM_DB',
			'EXTDB'=>array(),
			'SYN'=>array(),
		'ANATOMY'=>array());
		 
	}

	
	///We also retrieve all external identifiers
	$res=runQuery("SELECT disease_tag, disease_extdb_ID,
							EE.disease_entry_ID,
							S.source_id,
							disease_extdb,
							source_name
					FROM disease_extdb EE, disease_entry EF , source S
					WHERE EF.disease_entry_ID = EE.disease_entry_id 
					AND S.source_id = EE.source_id 
					AND EF.source_id=".$SOURCE_ID);
	if ($res===false)																	failProcess($JOB_ID."A03",'Unable to fetch external identifiers from database');
	
	foreach ($res as $tab)
	{
		if (!isset($DATA[$tab['disease_tag']])) continue;
		$DATA[$tab['disease_tag']]['EXTDB'][]=array(
			strtolower($tab['source_name']),
			$tab['disease_extdb'],
			$tab['disease_extdb_id'],
			'FROM_DB');
	}

	/// As well as synonyms for those diseases
	$res=runQuery("SELECT disease_syn_id,disease_tag,syn_type,syn_value
		FROM disease_syn DS,disease_entry DE 
		WHERE DE.disease_entry_id = DS.disease_entry_id");
	if ($res===false)																	failProcess($JOB_ID."A04",'Unable to fetch synonyms from database');
	
	foreach ($res as $tab)
	{
		if (!isset($DATA[$tab['disease_tag']]))continue;
		$DATA[$tab['disease_tag']]['SYN'][]=array(
			$tab['syn_value'],
			$tab['syn_type'],
			$tab['disease_syn_id'],
			'FROM_DB');
	}
		
	/// As well as mapping against anatomy for those diseases
	$res=runQuery("SELECT disease_tag,disease_anatomy_map_id,anatomy_tag,ae.anatomy_entry_id
		FROM disease_anatomy_map DS,disease_entry DE,anatomy_entry ae 
		WHERE ae.anatomy_entry_id = ds.anatomy_entry_id 
		AND DE.disease_entry_id = DS.disease_entry_id 
		AND ds.source_id = ".$SOURCE_ID);
   if ($res===false)																	failProcess($JOB_ID."A05",'Unable to fetch synonyms from database');
   
   foreach ($res as $tab)
   {
	   if (!isset($DATA[$tab['disease_tag']]))continue;
	   $DATA[$tab['disease_tag']]['ANATOMY'][$tab['anatomy_entry_id']]=array(
		$tab['anatomy_tag'],
		$tab['disease_anatomy_map_id'],
		'FROM_DB');
   }

   return $DATA;
}





function convertText($line,$tag)
{
	$TXT=getInBetween($line,$tag);
	$TXT=html_entity_decode($TXT."\n",ENT_QUOTES | ENT_HTML5);
	$TXT=trim($TXT);
	return $TXT;
	
}

function processFile(&$DATA)
{
	global $STATS;
	global $DBIDS;
	global $SYN_ADD_RULES;





	/// This file will contain all Parent->Child relationships for db_mondo_tree.php
	$fpO=fopen('RELATIONS.csv','w');if (!$fpO)													failProcess($JOB_ID."B01",'Unable to open RELATION.csv');

	/// Then we read the file and compare the records from the file against the records from the database, and create those that are missing
	$fp=fopen('mondo.owl','r');if (!$fp)													failProcess($JOB_ID."B02",'Unable to open eco.owl');
	
	$NL=0;
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");++$NL;
		

		/// Each record is a block of xml files, that we are going to read as flat file
		/// A record start with <owl:Class
		if (strpos($line,'<owl:Class')===false)continue;
		if (strpos($line,'/>')!==false)continue;
		
		/// Getting the ID
		$P1=strrpos($line,'/');
		$P2=strrpos($line,'"');
		if ($P1===false||$P2===false)continue;
		$ID=substr($line,$P1+1,$P2-$P1-1);
		
		$STATS['ENTRY']++;
		$XML_GROUP=array('<?xml version="1.0"?>','<rdf:RDF>',trim($line));
		
		$N_OPEN=0;
		$DESC=array();
		$NAME='';
		$PARENTS=array();
		$VALID=true;
		$EXTDB=array();
		$SYN=array();
		$HAS_SUBCLASS=false;
		$INFO_MIX=array();
		/// Reading the rest of the records
		do
		{
			$line=stream_get_line($fp,1000,"\n");++$NL;
			$XML_GROUP[]=trim($line);
			//echo $line."\n";
			if (strpos($line,'IAO_0000231')!==false)$INFO_MIX['TYPE']=convertText($line,'obo:IAO_0000231');;
			if (strpos($line,'IAO_0100001')!==false)
			{
				$INFO_MIX['ID']=convertText($line,'obo:IAO_0100001');
			}
			
			if (strpos($line,'IAO_0000115')!==false)$DESC[]=convertText($line,'obo:IAO_0000115');
			
			if (strpos($line,'<rdfs:label')!==false)$NAME=convertText($line,'rdfs:label');
			
			if (strpos($line,'owl:deprecated')!==false){$VALID=false;break;}

			/// subClassOf list all parents:
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
					if (substr($line,-2)!='/>')$HAS_SUBCLASS=true;
		
					//rdf:resource="http://identifiers.org/mesh/C535421"/>	
			}
			/// List an external identifier
			else if (strpos($line,'<oboInOwl:hasDbXref')!==false)
			{
				$V=getInBetween($line,'oboInOwl:hasDbXref');
				//echo $line." ".$V."\n";
				if ($V=='')continue;
				$tab=explode(":",$V);
				if (count($tab)==1)$tab=explode(" ",$V);
				if (count($tab)==1)continue;
				$EXT_DBN=$tab[0];
				
				/// Now we compare this external identifier against the ones for this record
				$FOUND=false;
				foreach ($EXTDB as $D)
				{
					if ($D[0]==$tab[0] &&$D[1]==$tab[1])$FOUND=true;
				}
				if ($FOUND)continue;
				$EXTDB[]=array($tab[0],$tab[1],-1,'TO_INS');
				
				
			}

			///Getting all types of synonyms
			else if (strpos($line,'<oboInOwl:hasExactSynonym')!==false)
			{
				$V=str_replace('"',"'",html_entity_decode(getInBetween($line,'oboInOwl:hasExactSynonym'),ENT_QUOTES | ENT_HTML5))	;
				if ($V=='')continue;
				$FOUND=false;
				foreach ($SYN as $D)
				{
					if ($D[0]==$V &&$D[1]=='EXACT')$FOUND=true;
				}
				if ($FOUND)continue;
				$SYN[]=array($V,'EXACT',-1,'TO_INS');
			}
			else if (strpos($line,'<oboInOwl:hasRelatedSynonym')!==false)
			{
				$V=str_replace('"',"'",html_entity_decode(getInBetween($line,'oboInOwl:hasRelatedSynonym'),ENT_QUOTES | ENT_HTML5));
				if ($V=='')continue;
				$FOUND=false;
				
				foreach ($SYN as $D)
				{
					if ($D[0]==$V &&$D[1]=='REL')$FOUND=true;
				}
				if ($FOUND)continue;
				$SYN[]=array($V,'REL',-1,'TO_INS');
			}
			else if (strpos($line,'<oboInOwl:hasNarrowSynonym')!==false)
			{
				$V=str_replace('"',"'",html_entity_decode(getInBetween($line,'oboInOwl:hasNarrowSynonym'),ENT_QUOTES | ENT_HTML5));
				if ($V=='')continue;
				
				$FOUND=false;
				foreach ($SYN as $D)
				{
					if ($D[0]= $V  &&$D[1]=='NAR')$FOUND=true;
				}
				if ($FOUND)continue;
				$SYN[]=array($V,'NAR',-1,'TO_INS');
			}
			else if (strpos($line,'<oboInOwl:hasBroadSynonym')!==false)
			{
				$V=str_replace('"',"'",html_entity_decode(getInBetween($line,'oboInOwl:hasBroadSynonym'),ENT_QUOTES | ENT_HTML5));
				
				if ($V=='')continue;
				$FOUND=false;
				foreach ($SYN as $D)
				{
					if ($D[0]==$V &&$D[1]=='BRO')$FOUND=true;
				}
				if ($FOUND)continue;
				$SYN[]=array($V,'BRO',-1,'TO_INS');
			}
			
			if (strpos($line,'<owl:Class')!==false)++$N_OPEN;
			if (strpos($line,'</owl:Class')!==false)
			{
				if ($N_OPEN==0)				break;
				else --$N_OPEN;
			}
		}while(!feof($fp));

		/// In some cases, the term can be obsolete, or merged with another term
		if ($INFO_MIX!=array())
		{
			if (isset($INFO_MIX['TYPE']) && isset($INFO_MIX['ID']))
			{
				if (trim($INFO_MIX['TYPE'])!='' && $INFO_MIX['ID']!='')
				{
					$INFO_MIX['ID']=str_replace(":","_",$INFO_MIX['ID']);
					switch (trim($INFO_MIX['TYPE']))
					{
						case ' https://github.com/monarch-initiative/mondo/issues/141':echo ';OBSOLETE';break;
						case 'out of scope':echo "OUT\n";break;
						case 'duplicate':echo "DUPLICATE	\n";break;
						case 'term split':echo "SPLIT	\n";break;
						case 'term merged':
						case 'terms merge':
						case 'terms merged': 
							echo "MERGE ".trim($INFO_MIX['ID'])."\t".$ID."\n";
							if (isset($DATA[$ID]))
							{
								if (isset($DATA[$INFO_MIX['ID']]))
								{									
									$query='UPDATE news_disease_map 
											SET disease_entry_id = '.$DATA[$INFO_MIX['ID']]['DB'].' 
											WHERE disease_entry_id = '.$DATA[$ID]['DB']."\n";
									if(!runQueryNoRes($query) )failProcess($JOB_ID."B03",'Unable to update the database '.$query); 
									
									
									$query='UPDATE '.$GLB_VAR['SCHEMA_PRIVATE'].'.news_disease_map 
											SET disease_entry_id = '.$DATA[$INFO_MIX['ID']]['DB'].' 
											WHERE disease_entry_id = '.$DATA[$ID]['DB']."\n";
									if(!runQueryNoRes($query) )failProcess($JOB_ID."B04",'Unable to update the database '.$query); 
									
									echo "ENTRY ALREADY EXIST\n";
									break;
								}
								else
								{
									
									$DATA[$INFO_MIX['ID']]=$DATA[$ID];
									unset($DATA[$ID]);
									$query="UPDATE disease_entry 
											set disease_tag = '".$INFO_MIX['ID']."' 
											WHERE disease_tag = '".$ID."'";
									if (!runQueryNoRes($query))			failProcess($JOB_ID."B05",'Unable to update the database ');
								
								}
						}
						break;
						default: 
					}
				}
			}
		}
		
		if (!$VALID){$STATS['OBSOLETE_ENTRY']++;continue;}
		
		if ($HAS_SUBCLASS) getSubClass($XML_GROUP,$ID);
		
		if (isset($SYN_ADD_RULES[$ID]))
		{
			foreach ($SYN_ADD_RULES[$ID] as $S)
			{
				$FOUND=false;
				foreach ($SYN as $D)
				{
					if ($D[0]==$S &&$D[1]=='EXACT')$FOUND=true;
				}
				if ($FOUND)continue;
				$SYN[]=array($S,'EXACT',-1,'TO_INS');
			}
		}
	
	//	print_r($EXTDB);
		$STATS['SYN']+=count($SYN);
		$STATS['EXTDB']+=count($EXTDB);

		/// Some descriptions might be identical. so we compare all against all to see if they are too similar. If so, we keep the longuest one.
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
	
		
		if ($NAME=='')continue;

		if (!isset($DATA[$ID])&& isset($ALT_ENTRIES[$ID]))
		{
			$DATA[$ID]=$ALT_ENTRIES[$ID];
			$res=runQueryNoRes('UPDATE disease_entry SET source_id = '.$SOURCE_ID.' WHERE disease_entry_Id ='.$DATA[$ID]['DB']);
			if ($res===false)																failProcess($JOB_ID."B06",'Unable to update the database ');
		}



		/// Check if the record exist in the datababse
		/// No-> Create it
		
		if (!isset($DATA[$ID]))
		{

			/// Create a new record
			$STATS['INSERT_ENTRY']++;

			/// Define the new ID for the record
			++$DBIDS['disease_entry'];

			/// Define the new ID for the synonyms
			foreach ($SYN as &$S)
			{
				$STATS['INSERT_SYN']++;
				++$DBIDS['disease_syn'];
				$S[2]=$DBIDS['disease_syn'];;
			}

			/// Define the new ID for the external identifiers
			foreach ($EXTDB as &$S)
			{
				$STATS['INSERT_EXTDB']++;
				++$DBIDS['disease_extdb'];
				$S[2]=$DBIDS['disease_extdb'];;

			}

			/// Store the record in the data array
			$DATA[$ID]=array(
				'DB'=>$DBIDS['disease_entry'],
				'NAME'=>$NAME,
				'DESC'=>$DESC,
				'STATUS'=>'TO_INS',
				'EXTDB'=>$EXTDB,
				'SYN'=>$SYN);
		}
		else if (isset($DATA[$ID]) && !isset($DATA[$ID]['DB']))
		{

			/// Create a new record
			$STATS['INSERT_ENTRY']++;

			/// Define the new ID for the record
			++$DBIDS['disease_entry'];

			/// Define the new ID for the synonyms
			foreach ($SYN as &$S)
			{
				$STATS['INSERT_SYN']++;
				++$DBIDS['disease_syn'];
				$S[2]=$DBIDS['disease_syn'];;
			}


			/// Define the new ID for the external identifiers
			foreach ($EXTDB as &$S)
			{
				$STATS['INSERT_EXTDB']++;
				++$DBIDS['disease_extdb'];
				$S[2]=$DBIDS['disease_extdb'];;
			}
			$DATA[$ID]['DB']=$DBIDS['disease_entry'];;
			$DATA[$ID]['NAME']=$NAME;
			$DATA[$ID]['DESC']=$DESC;
			$DATA[$ID]['STATUS']='TO_INS';
			$DATA[$ID]['EXTDB']=$EXTDB;
			$DATA[$ID]['SYN']=$SYN;
		}
		else 
		{

			/// Otherwise we assume the record is vlaid, unless data has changed in it
			$DATA[$ID]['STATUS']='VALID';

			/// Name change -> update it
			if ($NAME!=$DATA[$ID]['NAME'])
			{
				//echo "IDD".$NAME.'/'.$DATA[$ID]['NAME']."\n";
				$DATA[$ID]['NAME']=$NAME;
				$DATA[$ID]['STATUS']='TO_UPD';
			}

			/// Description change -> update it
			if ($DESC!=$DATA[$ID]['DESC'])
			{
				//echo "IDD".$DESC.'/'.$DATA[$ID]['DESC']."\n";
				$DATA[$ID]['DESC']=$DESC;
				$DATA[$ID]['STATUS']='TO_UPD';
			}
		
			if ($DATA[$ID]['STATUS']=='VALID')$STATS['VALID_ENTRY']++;
			else $STATS['UPDATED_ENTRY']++;
			
			/// Within the record, compare external identifiers from the file vs the database
			/// to see which one we need to create/delete
			foreach ($EXTDB as $EDB)
			{
				$FOUND=false;
				foreach ($DATA[$ID]['EXTDB'] as &$ER)
				{
					if (strtolower($ER[0])!=strtolower($EDB[0]))continue;
					if ($ER[1]!=$EDB[1])continue;
					$STATS['VALID_EXTDB']++;
					$FOUND=true;
					$ER[3]='VALID';
					break;
				}
				
				if ($FOUND)continue;

				/// Not found - we need to create it
				$STATS['INSERT_EXTDB']++;

				/// Define the new ID for the external identifiers
				++$DBIDS['disease_extdb'];

				$EDB[2]=$DBIDS['disease_extdb'];
				if (!isset($DATA[$ID]))
				{
					failProcess("B07",'CRITICAL FAILURE:'.$ID."\tNO DATA FOR EXTDB");
				}
				$DATA[$ID]['EXTDB'][]=$EDB;
			}
			
			/// Within the record, compare  synonyms from the file vs the database
			/// to see which one we need to create/delete	
			foreach ($SYN as &$S)
			{
				$FOUND=false;
				foreach ($DATA[$ID]['SYN'] as &$ERS)
				{
					
					if ($ERS[0]!=$S[0])continue;
					if ($ERS[1]!=$S[1])continue;
					$FOUND=true;
					$STATS['VALID_SYN']++;
					$ERS[3]='VALID';
					break;
				}
				if ($FOUND)continue;

				/// Not found - we need to create it
				$STATS['INSERT_SYN']++;

				/// Define the new ID for the synonyms
				++$DBIDS['disease_syn'];

				$S[2]=$DBIDS['disease_syn'];
				if (!isset($DATA[$ID]))
				{
					failProcess("B08",'CRITICAL FAILURE:'.$ID."\tNO DATA FOR SYN");
				}
				$DATA[$ID]['SYN'][]=$S;
			}
			

		}
		
		/// Add the relationships to the file
		if ($PARENTS==array())			fputs($fpO,$ID."\tROOT\n");
		else foreach ($PARENTS as $C)	fputs($fpO,$ID."\t".$C."\n");
			
		
	//
	}
	fclose($fp);
	fclose($fpO);
	
}



function getSubClass($XML_GROUP,&$ID)
{
	global $DISEASE_ANATOMY_MAP;
	$XML_GROUP[]='</rdf:RDF>';
	$xml = @simplexml_load_string(implode("\n",$XML_GROUP), "SimpleXMLElement");
	/// An easy way to convert it to an array is to convert it to json and then decode it
	$json = json_encode($xml);
	$BLOCK_R=array();
	$RECORD = json_decode($json,TRUE);
	//print_r($RECORD);
	$SUBCLASS_INFO=&$RECORD['owl:Class']['rdfs:subClassOf'];
	// echo "@@@@@@@@\n";
	// print_r($SUBCLASS_INFO);
	if (isset($SUBCLASS_INFO[0]))
	{
	//	echo "\tSUBCLASS INFO\n";
		foreach ($SUBCLASS_INFO as &$E)
		{
			
			if (isset($E['owl:Restriction'])
				&&isset($E['owl:Restriction']['owl:onProperty'])
				&&isset($E['owl:Restriction']['owl:onProperty']['@attributes']) 
				&&isset($E['owl:Restriction']['owl:onProperty']['@attributes']['rdf:resource'])
				&&$E['owl:Restriction']['owl:onProperty']['@attributes']['rdf:resource']== 'http://purl.obolibrary.org/obo/RO_0004026'
				)
			{
					///echo "\t\tHAS RESTRICTION\n";
				if (isset($E['owl:Restriction']['owl:allValuesFrom'])
				&&isset($E['owl:Restriction']['owl:allValuesFrom']['@attributes']) 
				&&isset($E['owl:Restriction']['owl:allValuesFrom']['@attributes']['rdf:resource']))
				{
					
					$line=$E['owl:Restriction']['owl:allValuesFrom']['@attributes']['rdf:resource'];
					$P1=strrpos($line,'/');
					
					$ID_DISEASE=explode("_",substr($line,$P1+1));
					$DISEASE_ANATOMY_MAP["('".getSource($ID_DISEASE[0])."','".$ID_DISEASE[1]."')"][]=$ID;
					
				}
				else if (isset($E['owl:Restriction']['owl:someValuesFrom'])
				&&isset($E['owl:Restriction']['owl:someValuesFrom']['@attributes']) 
				&&isset($E['owl:Restriction']['owl:someValuesFrom']['@attributes']['rdf:resource']))
				{
				
					$line=$E['owl:Restriction']['owl:someValuesFrom']['@attributes']['rdf:resource'];
					
					$P1=strrpos($line,'/');
					
					$ID_DISEASE=explode("_",substr($line,$P1+1));
					$DISEASE_ANATOMY_MAP["('".getSource($ID_DISEASE[0])."','".$ID_DISEASE[1]."')"][]=$ID;
				}
				//else print_r($E);
			}
		}
	}
	else
	{
		
		$E=&$SUBCLASS_INFO;
		
		if (isset($E['owl:Restriction'])
		&&isset($E['owl:Restriction']['owl:onProperty'])
		&&isset($E['owl:Restriction']['owl:onProperty']['@attributes']) 
		&&isset($E['owl:Restriction']['owl:onProperty']['@attributes']['rdf:resource'])
		&&$E['owl:Restriction']['owl:onProperty']['@attributes']['rdf:resource']== 'http://purl.obolibrary.org/obo/RO_0004026'
		)
		{
			if (isset($E['owl:Restriction']['owl:allValuesFrom'])
			&&isset($E['owl:Restriction']['owl:allValuesFrom']['@attributes']) 
			&&isset($E['owl:Restriction']['owl:allValuesFrom']['@attributes']['rdf:resource']))
			{

				$line=$E['owl:Restriction']['owl:allValuesFrom']['@attributes']['rdf:resource'];
				$P1=strrpos($line,'/');
				
				$ID_DISEASE=explode("_",substr($line,$P1+1));
				$DISEASE_ANATOMY_MAP["(".getSource($ID_DISEASE[0]).",'".$ID_DISEASE[1]."')"][]=$ID;
			}
			else if (isset($E['owl:Restriction']['owl:someValuesFrom'])
			&&isset($E['owl:Restriction']['owl:someValuesFrom']['@attributes']) 
			&&isset($E['owl:Restriction']['owl:someValuesFrom']['@attributes']['rdf:resource']))
			{

				$line=$E['owl:Restriction']['owl:someValuesFrom']['@attributes']['rdf:resource'];
				echo $line."\n";
				$P1=strrpos($line,'/');
				
				$ID_DISEASE=explode("_",substr($line,$P1+1));
				$DISEASE_ANATOMY_MAP["(".getSource($ID_DISEASE[0]).",'".$ID_DISEASE[1]."')"][]=$ID;
			}
		//else print_r($E);
		}
	}
}



function pushAnatomy(&$DATA,&$DISEASE_ANATOMY_MAP)
{
	global $STATS;
	global $DBIDS;
	global $JOB_ID;


	$res=runQuery("SELECT * FROM anatomy_extdb d, source s
		 WHERE s.source_id = d.source_id AND (d.source_id,anatomy_extdb) IN (".implode(',',array_keys($DISEASE_ANATOMY_MAP)).')');
	if ($res===false)																failProcess($JOB_ID."C01",'Unable to fetch anatomy from database');
	
	
	foreach ($res as $line)
	{
		/// We get the list of diseases that are linked to this anatomy
		$NAMETAG="(".getSource($line['source_name']).",'".$line['anatomy_extdb']."')";

		$LIST=&$DISEASE_ANATOMY_MAP[$NAMETAG];
		if ($LIST!=null)
		foreach ($LIST as $disease_tag)
		{
			$ENTRY=&$DATA[$disease_tag];
			//print_r($ENTRY);
			if (isset($ENTRY['ANATOMY'][$line['anatomy_entry_id']]))
			{
				$ENTRY['ANATOMY'][$line['anatomy_entry_id']][2]='VALID';
			}
			else
			{
				$STATS['INSERT_ANATOMY']++;
				++$DBIDS['disease_anatomy_map'];
				$ENTRY['ANATOMY'][$line['anatomy_entry_id']]=array(-1,$DBIDS['disease_anatomy_map'],'TO_INS');
			}
			
		}
	}
}




function pushToDB(&$DATA)
{
	global $SOURCE_ID;
	global $GLB_VAR;
	global $DB_INFO;
	global $STATS;
	global $JOB_ID;

	$fpO=fopen('disease_entry.csv','w');if (!$fpO)												failProcess($JOB_ID."D01",'Unable to open disease_entry.csv');
	$fpS=fopen('disease_syn.csv','w');if (!$fpS)													failProcess($JOB_ID."D02",'Unable to open disease_syn.csv');
	$fpE=fopen('disease_ext.csv','w');if (!$fpE)													failProcess($JOB_ID."D03",'Unable to open  disease_ext.csv');
	$fpA=fopen('disease_anatomy.csv','w');if (!$fpA)												failProcess($JOB_ID."D04",'Unable to open  disease_anatomy.csv');

	/// All records that are not in the file, we need to delete them
	/// So we store them in an array to delete them later in batches
	$TO_DEL=array('ENTRY'=>array(),'SYN'=>array(),'EXTDB'=>array(),'ANATOMY'=>array());


	/// Loop through all records and see if they need to be updated, inserted or deleted
	foreach ($DATA as $ID=>$INFO)
	{
		++$N; if ($N%1000==0)echo $N."\t".count($DATA)."\n";
		
		if (!isset($INFO['STATUS']))														failProcess($JOB_ID."D05",'Missing status for '.$ID);
		
		/// Record status is FROM_DB -> So it's not in the file, we should delete it
		if ($INFO['STATUS']=='FROM_DB' && $INFO['SOURCE']==$SOURCE_ID) 
		{

			$TO_DEL['ENTRY'][]=$INFO['DB'];
		}
		
		/// Anything changed, we update
		else if ($INFO['STATUS']=='TO_UPD')
		{
			
			$QUERY=' UPDATE disease_entry SET 
			disease_tag =\''.$ID.'\',
			disease_name =\''.str_replace("'","''",$INFO['NAME']).'\',
			disease_definition=\''.str_replace("'","''",$INFO['DESC']).'\' WHERE disease_entry_id = '.$INFO['DB'];
			
			if (!runQueryNoRes($QUERY))														failProcess($JOB_ID."D06",'Unable to run query '.$QUERY);
		
		}
		/// New record we insert in the file
		else if ($INFO['STATUS']=='TO_INS')
		{
			fputs($fpO,$INFO['DB']."\t".
				$ID."\t".
				'"'.str_replace('"','""',$INFO['NAME']).'"'."\t".
				'"'.str_replace('"','""',$INFO['DESC']).'"'."\t".
				$SOURCE_ID."\n");
			
		}
		/// Similar logic for synonyms. New -> file. From_DB -> to delete
		if (isset($INFO['SYN']))
		foreach ($INFO['SYN'] as $EX)
		{
			
			if ($EX[3]=='TO_INS')
				fputs($fpS,$EX[2]."\t".
							$INFO['DB']."\t".
							'"'.str_replace('"','""',$EX[1]).'"'."\t".
							'"'.$EX[0].'"'."\t".
							$SOURCE_ID."\n");
			else if ($EX[3]=='FROM_DB')$TO_DEL['SYN'][]=$EX[2];
			
		}
		/// Similar logic for ext db. New -> file. From_DB -> to delete
		if (isset($INFO['EXTDB']))
		foreach ($INFO['EXTDB'] as $EX)
		{
			if ($EX[3]=='TO_INS')
				fputs($fpE,$EX[2]."\t".
							$INFO['DB']."\t".
							getSource($EX[0])."\t".
							'"'.$EX[1].'"'."\n");
			else if ($EX[3]=='FROM_DB')$TO_DEL['EXTDB'][]=$EX[2];
			
		}
		/// Similar logic for ext db. New -> file. From_DB -> to delete
		if (isset($INFO['ANATOMY']))
		foreach ($INFO['ANATOMY'] as $ANATOMY_DBID=>$EX)
		{
			if ($EX[2]=='TO_INS')
				fputs($fpA,$EX[1]."\t".
							$INFO['DB']."\t".
							$SOURCE_ID."\t".
							$ANATOMY_DBID."\n");
			else if ($EX[2]=='FROM_DB')$TO_DEL['ANATOMY'][]=$EX[1];
			
		}
	}



	fclose($fpO);
	fclose($fpS);
	fclose($fpE);
	fclose($fpA);


	/// Now we look at each table to see what records need to be deleted 
	if ($TO_DEL['ENTRY']!=array())
	{
		$STATS['DELETE_ENTRY']+=count($TO_DEL['ENTRY']);
		$QUERY=' DELETE FROM disease_entry 
				WHERE disease_entry_id IN ('.implode(',',$TO_DEL['ENTRY']).')';
		if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."D07",'Unable to delete disease_entry '.$QUERY);
	}
	if ($TO_DEL['SYN']!=array())
	{
		$STATS['DELETE_SYN']+=count($TO_DEL['SYN']);
		$QUERY=' DELETE FROM disease_syn 
				WHERE disease_syn_id IN ('.implode(',',$TO_DEL['SYN']).')';
		if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."D08",'Unable to delete disease_syn '.$QUERY);
	}
	if ($TO_DEL['EXTDB']!=array())
	{
		$STATS['DELETE_EXTDB']+=count($TO_DEL['EXTDB']);
		$QUERY=' DELETE FROM disease_extdb 
				WHERE disease_extdb_id IN ('.implode(',',$TO_DEL['EXTDB']).')';
		if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."D09",'Unable to delete disease_extdb '.$QUERY);
	}
	if ($TO_DEL['ANATOMY']!=array())
	{
		$STATS['DELETE_ANATOMY']+=count($TO_DEL['ANATOMY']);
		$QUERY=' DELETE FROM disease_anatomy_map 
				WHERE disease_anatomy_map_id IN ('.implode(',',$TO_DEL['ANATOMY']).')';
		if (!runQueryNoRes($QUERY))											failProcess($JOB_ID."D10",'Unable to delete disease_extdb '.$QUERY);
	}
	print_r($STATS);

	/// And we insert new records
	addLog("Insert content in table");
	
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.disease_entry (disease_entry_id,disease_tag,disease_name,disease_definition,source_id) FROM \''."disease_entry.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID."D11",'Unable to insert disease_entry'); 


	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.disease_syn (disease_syn_id, disease_entry_id, syn_type, syn_value,source_id ) FROM \''."disease_syn.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID.'D12','Unable to insert disease_syn'); 
	

	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.disease_extdb (disease_extdb_id,disease_entry_id,source_id,disease_extdb)  FROM \''."disease_ext.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )															failProcess($JOB_ID.'D13','Unable to insert disease_extdb'); 


	/// Now we can insert the anatomy mapping
	$fp=fopen('disease_anatomy.csv','r'); if (!$fp)											failProcess($JOB_ID."D14",'Unable to open disease_anatomy.csv');
	while(!feof($fp))
	{
		$line=stream_Get_line($fp,10000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		$tab[3]="'".str_replace("'","''",substr($tab[3],1,-1))."'";
		$query='INSERT INTO disease_anatomy_map VALUES ('.implode(',',$tab).')';
		if (!runQueryNoRes($query))												failProcess($JOB_ID."D15",'Unable to insert disease_anatomy_map '.$query);
	}
	fclose($fp);
}
?>
