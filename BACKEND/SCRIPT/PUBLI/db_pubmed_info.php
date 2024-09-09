<?php
ini_set('memory_limit','6000M');
error_reporting(E_ALL);
/**
 SCRIPT NAME: db_pubmed_info
 PURPOSE:     Associate authors, institutions, abstracts and citations to publications
 
*/
$JOB_NAME='db_pubmed_info';

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


addLog("Set up directory");
	///	get Parent info:
	$DL_PUBMED_INFO=$GLB_TREE[getJobIDByName('db_pubmed')];

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_PUBMED_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 																				failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 																				failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$DL_PUBMED_INFO['TIME']['DEV_DIR'];
	


addLog("Working directory: ".$W_DIR);

	/// PUBLI_W_ABSTRACT will push the abstract into the database
	if (!isset($GLB_VAR['PUBLI_W_ABSTRACT']))															failProcess($JOB_ID."003",'NO PUBLI_W_ABSTRACT in CONFIG_GLOBAL');
	if (!in_array($GLB_VAR['PUBLI_W_ABSTRACT'],array('Y','N')))											failProcess($JOB_ID."004",'WRONG value for PUBLI_W_ABSTRACT. Either Y or N');
	$PUBLI_W_ABSTRACT=($GLB_VAR['PUBLI_W_ABSTRACT']=='Y');


	if (!isset($GLB_VAR['PUBLI_W_CITATIONS']))															failProcess($JOB_ID."004",'NO PUBLI_W_CITATIONS in CONFIG_GLOBAL');
	if (!in_array($GLB_VAR['PUBLI_W_CITATIONS'],array('Y','N')))										failProcess($JOB_ID."005",'WRONG value for PUBLI_W_CITATIONS. Either Y or N');
	
	$PUBLI_W_CITATIONS=($GLB_VAR['PUBLI_W_CITATIONS']=='Y');


	$COMPANIES=getCompanies();

addLog("Prepare files and ID")	;
	
	
	$STATS=array(
		'NEW_ABSTR'=>0,
		'NEW_INSTIT'=>0,
		'NEW_AUTHOR'=>0,
		'NEW_AUTHOR_MAP'=>0,
		'UPD_ABSTR'=>0);


		
	/// Those are the tables we are going to insert into
	/// DBIDS will hold the max primary key valu for each table
	$DBIDS=array(
		'pmid_abstract'=>-1,
		'pmid_instit'=>-1,
		'pmid_author'=>-1,
		'pmid_company_map'=>-1,
		'pmid_author_map'=>-1
	);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		if ($TBL=='pmid_company_map')continue;
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();
		$res=runQuery($query);if ($res===false)										failProcess($JOB_ID."005",'Unable to run query '.$query);
		
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}
	
	

	
	/// $COL_ORDER precises the order of files to be inserted in the database
	/// The key is the table name and the value is the list of columns to be inserted
	$COL_ORDER=array(
	'pmid_abstract'=>'(pmid_abstract_id , pmid_entry_Id ,abstract_Type , abstract_text)',
	'pmid_instit'=>'(pmid_instit_id , instit_name,instit_hash)',
	'pmid_author'=>'(pmid_author_id , last_name, first_name, initials, pmid_instit_id , orcid_id , is_valid_author,md5_hash)',
	'pmid_author_map'=>'(pmid_author_map_id , pmid_entry_id , pmid_author_id , position )',
	'pmid_citation'=>'(pmid_entry_id,citation_pmid_entry_id)',
	'pmid_company_map'=>'(pmid_entry_Id,company_entry_id)');

	/// $FILE_STATUS will hold the status of the files to be inserted
	/// The key is the table name and the value is the status of the file
	$FILE_STATUS=array();

addLog("Opening files");

	$FILES=array();
	foreach ($COL_ORDER as $TYPE=>$CTL)
	{
		$FILE_STATUS[$TYPE]=false;
		$FILES[$TYPE]=fopen($TYPE.'.csv','w');
		if (!$FILES[$TYPE])																			failProcess($JOB_ID."006",'Unable to open '.$TYPE.'.csv');
	}

addLog("Opening ENTRIES");
	
	$fp=fopen('ENTRIES.xml','r');if (!$fp)															failProcess($JOB_ID."007",'Unable to open ENTRIES.xml');
	
	if (is_file('restart_info'))
	{
		$fpos=file_get_contents('restart_info');
		addLog("Restarting at position ".$fpos);
		fseek($fp,$fpos);
	}
	
	//fseek($fp,11604212007);
	
	$BLOCK=array();$N_BLOCK=0;
	while(!feof($fp))
	{
		
		$line=stream_get_line($fp,100000,"\n");
		/// Each publication record starts with PubmedArticle	
		if (strpos($line,'<PubmedArticle>')===false)continue;
		/// So we create an array
		$XML_RECORD=array($line);
		
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000000,"\n");
			/// And add to that array until we get to </PubmedArticle>
			
			$XML_RECORD[]=$line;
			if (strpos($line,'</PubmedArticle>')!==false)break;
		}
		/// Then we parse the xml file
		$xml = simplexml_load_string(implode("\n",$XML_RECORD), "SimpleXMLElement");
		
		/// An easy way to convert it to an array is to convert it to json and then decode it
		$json = json_encode($xml);
		
		$RECORD = json_decode($json,TRUE);

		/// If we can read it, json_Decode will return false.
		if ($RECORD===false)
		{
			print_r($XML_RECORD);
			continue;
		}

		processRecord($RECORD,$BLOCK,$xml);
		if (count($BLOCK)%250==0)echo count($BLOCK).' ';

		/// Wait to have 500 records before processing
		if (count($BLOCK)<500)continue;
		
		++$N_BLOCK;
		addLog("######## PROCESSING ".(($N_BLOCK-1)*500).' -> '.(($N_BLOCK)*500));
	 	processBlock($BLOCK);
		echo "FILE POS\t".ftell($fp)."\n";//exit;
		$BLOCK=null;
		$BLOCK=array();
		foreach ($COL_ORDER as $TYPE=>$CTL)
		{
			$FILES[$TYPE]=fopen($TYPE.'.csv','w');
			if (!$FILES[$TYPE])		failProcess($JOB_ID."008",'Unable to open '.$TYPE.'.csv');
		}
		echo "FILE POS:".ftell($fp)."\n";
		$fpP=fopen('restart_info','w');
		fputs($fpP,ftell($fp));
		fclose($fpP);
		
	}
	fclose($fp);
	echo "END OF FILE\n";
	/// Processing last block
	processBlock($BLOCK);
	addLog("END PROCESS");

	/// Because of the long quering time, we update the timestamp table to show that the job is still running
	$JOB_INFO=$GLB_TREE[$JOB_ID];
	$query='UPDATE '.$GLB_VAR['PUBLIC_SCHEMA'].".biorels_timestamp SET processed_date='".date('Y-m-d H:i:s')."', current_dir='".$JOB_INFO['TIME']['DEV_DIR']."', last_check_date='".date('Y-m-d H:i:s')."' WHERE job_name='".$JOB_INFO['NAME']."'";
	if (!runQueryNoRes($query))sendKillMail('000060','Failed to update timestamp '."\n".$query);


	 connectDB();
	 successProcess();






	 

function processRecord($RECORD,&$BLOCK,&$xml)
{
	global $PUBLI_W_ABSTRACT;
	global $PUBLI_W_CITATIONS;
	$BLOCK_R=array();

	$PMID=$RECORD['MedlineCitation']['PMID'];
	//echo $PMID."\n";
	
	/// Depending on whether there are multiple authors or just 1, the array will be constructed differently
	$E=null;
	if (isset($RECORD['MedlineCitation']['Article']['AuthorList']['Author']['LastName']))
	{
		$E=&$RECORD['MedlineCitation']['Article']['AuthorList'];
		unset($E['@attributes']);
	}
	else $E=&$RECORD['MedlineCitation']['Article']['AuthorList']['Author'];
	/// We check each other
	if (is_array($E))
	foreach ($E as $AUTHOR)
	{
		
		if (!isset($AUTHOR['LastName']))continue;
		/// Find ORCID ID:
		$ORCID='NULL';
		if (isset($AUTHOR['Identifier']))
		{
			if (!is_array($AUTHOR['Identifier']))
			{
			if (preg_match("/[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{4}/",$AUTHOR['Identifier'],$matches))
			$ORCID=$matches[0];
			}
			else
			{
				foreach ($AUTHOR['Identifier'] as $AI)
				if (preg_match("/[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{4}/",$AI,$matches))
			$ORCID=$matches[0];
			}
			
		}
		$INSTIT='Unknown';
		//print_r($AUTHOR);
		if (isset($AUTHOR['AffiliationInfo']['Affiliation'])&& !is_array($AUTHOR['AffiliationInfo']['Affiliation']))
		{
			if (strlen($AUTHOR['AffiliationInfo']['Affiliation'])>10000)
			$AUTHOR['AffiliationInfo']['Affiliation']=substr($AUTHOR['AffiliationInfo']['Affiliation'],0,9000);
			$INSTIT=($AUTHOR['AffiliationInfo']['Affiliation']);
		}
		if (is_array($AUTHOR['LastName']) && $AUTHOR['LastName']==array())continue;
		
			
		/// Sometimes the author's name has an issue and has the institution in it.
		/// We simply truncate it to 300 characters
		if(strlen($AUTHOR['LastName'])>300)$AUTHOR['LastName']=substr($AUTHOR['LastName'],0,300);
		if(isset($AUTHOR['Initials']))
		{
			if (is_array($AUTHOR['Initials']))
			{
				$AUTHOR['Initials']='NULL';
			}
			/// Keep only the 20 first characters for initials - which is already quite long for initials ...
			else if(strlen($AUTHOR['Initials'])>20)$AUTHOR['Initials']=substr($AUTHOR['Initials'],0,20);
		} 
		//Identifier] => https://orcid.org/0000-0003-3222-2102

		$FIRST_NAME='NULL';
		if (isset($AUTHOR['ForeName']))
		{
			if (strlen($AUTHOR['ForeName'])>100)$FIRST_NAME=substr($AUTHOR['ForeName'],0,100);
			else $FIRST_NAME=$AUTHOR['ForeName'];
		}
		

		/// Create the record:
		$REC=array(
			'Last'=>$AUTHOR['LastName'],
			'First'=>$FIRST_NAME,
			'Ini'=>isset($AUTHOR['Initials'])?($AUTHOR['Initials']):"NULL",
			'Valid'=>$AUTHOR['@attributes']['ValidYN'],
			'Instit'=>$INSTIT,
			'ORCID'=>$ORCID,
			'STATUS'=>'INI');
		if (strlen($REC['Instit'])>4000)$REC['Instit']='NULL';
		
		/// Add it to the list
		$BLOCK_R['AUTHORS'][]=$REC;

	}

	/// Finding the citation with the multiple tracks of XML
	if ($PUBLI_W_CITATIONS && isset($RECORD['PubmedData']['ReferenceList']))
	{
		foreach ($xml as $key=>$l1)
		{
			if ($key!='PubmedData')continue;
			foreach ($l1 as $key_2=>$l2)
			{
				if ($key_2!='ReferenceList')continue;
				foreach ($l2 as $key_3=>$l3)
				{
					if ($key_3!='Reference')continue;
					foreach ($l3 as $key_4=>$l4)
					{
						if ($key_4!='ArticleIdList')continue;
						
						foreach ($l4 as $key_5=>$l5)
						{
							if ($key_5!='ArticleId')continue;
							$id_ab=xml_attribute($l5,'IdType');
							if  ($id_ab=='pubmed')
							$BLOCK_R['CITATION'][]=(string)$l5;
						}
					}
				}
			}
		}
	}
	

	/// Getting the abstract with the multiple tracks of XML
	if ($PUBLI_W_ABSTRACT && isset($RECORD['MedlineCitation']['Article']['Abstract']))
	{
		foreach ($xml as $key=>$l1)
		{
			if ($key!='MedlineCitation')continue;
			foreach ($l1 as $key_2=>$l2)
			{
				if ($key_2!='Article')continue;
				foreach ($l2 as $key_3=>$l3)
				{
					if ($key_3!='Abstract')continue;
					foreach ($l3 as $key_4=>$l4)
					{
						if ($key_4!='AbstractText')continue;
						
						$id_ab=xml_attribute($l4,'Label');
					
						if ($id_ab!==false)
						{
							/// PMID 29394359
							if (strlen($id_ab)>50)
							{ 
								if((string)$l4=='.'|| (string)$l4=='')
								{
									$BLOCK_R['ABSTRACT']['Text']=$id_ab;
								}
								else if (strpos($id_ab,'INTRODUCTION')==0)
								{
									$BLOCK_R['ABSTRACT']['INTRODUCTION']=substr($id_ab,strpos($id_ab,' ')).' '.(string)$l4;
								}
							}
							
							else $BLOCK_R['ABSTRACT'][$id_ab]=(string)$l4;
							
						}
						else
						{
							//if ($l4!=htmlspecialchars($l4))echo $l4."\n";
							$BLOCK_R['ABSTRACT']['Text']=(string)$l4;
						}
						
						
					}
				}
			}
		}
		
	}
	$BLOCK[$PMID]=$BLOCK_R;
	}





function processBlock()
{
	global $STATS;
	global $COL_ORDER;
	global $BLOCK;
	global $DBIDS;
	global $FILES;
	global $J_ABBR;
	global $COMPANIES;
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;

	if ($BLOCK==array())return;
	
	$LIST_INSTIT=array();
	echo "SEARCH PMID\n";
	
	
	$STR='SELECT pmid_entry_id, pmid FROM pmid_entry where pmid IN (';
	foreach ($BLOCK as $PMID=>&$INFO_PMID)$STR.=$PMID.',';
	$res=runQuery(substr($STR,0,-1).')');
	if ($res===false)														failProcess($JOB_ID."A01",'Unable to query for pmid ids ');
	
	/// $PMIDS is a mapping between the PMID and the pmid_entry_id
	$PMIDS=array();
	foreach ($res as $line)$PMIDS[$line['pmid']]=$line['pmid_entry_id'];



	echo "Process citation\n";
	processCitation($BLOCK,$PMIDS);

	echo "Process Abstract\n";
	processAbstract($BLOCK,$PMIDS);

	echo "Process institution\n";
	$LIST_INSTIT=array();
	processInstit($BLOCK,$PMIDS,$LIST_INSTIT);
	processAuthor($BLOCK,$PMIDS,$LIST_INSTIT);
	//exit;
	echo "Save files\n";
	pushFilesToDB(false);
	
	
	foreach ($STATS as $KV=>$VV)echo $KV.":".$VV."\t";
	echo "\n";
	echo "##############\n##############\n##############\n##############\n";

}

function processCitation(&$BLOCK,&$PMIDS)
{
	global $FILES;
	global $FILE_STATUS;
	
	$CITATIONS=array();
	foreach ($PMIDS as $PMID=>$PMID_ENTRY_ID)$CITATIONS[$PMID]=array();
	if ($PMIDS!=array())
	{

		/// Get the citations from the database
		$res=runQuery("SELECT pc.pmid_entry_id, citation_pmid_entry_id 
				FROM pmid_citation pc 
				WHERE pc.pmid_entry_id IN (".implode(",",$PMIDS).')');
		if ($res===false)													failProcess($JOB_ID."B01",'Unable to query for pmid_citation ');
		
		$KEYS=array_flip($PMIDS);
		$MAP_CIT=array();
		foreach ($res as $line)
		{
			
			$MAP_CIT[$line['citation_pmid_entry_id']][]=$line['pmid_entry_id'];
		}

		/// Now those are primary key, we need to find the pmid from the pmid_entry_id
		/// We do it by chunks of 1000
		$CHUNKS=array_chunk(array_keys($MAP_CIT),1000);
		foreach ($CHUNKS as $CHUNK)
		{
			$res=runQuery("SELECT pmid,pmid_entry_id FROM pmid_entry where pmid_entry_id IN (".implode(',',$CHUNK).')');
			if ($res===false)													failProcess($JOB_ID."B02",'Unable to query for pmid_citation ');
			foreach ($res as $line)
			{
				foreach ($MAP_CIT[$line['pmid_entry_id']] as $R_ID)
				{
					$R_PMID=$KEYS[$R_ID];
					$CITATIONS[$R_PMID][$line['pmid']]=array('DB_STATUS'=>'FROM_DB','DBID'=>$line['pmid_entry_id']);
				}
			}
		}
		
	}

	
	


	$SEARCH_PMID=array();
	foreach ($BLOCK as $PMID=>&$INFO_PMID)
	{
		/// No pmid_entry_id => continue
		if (!isset($PMIDS[$PMID]))
		{
			echo "MISSING PMID ".$PMID."\n";
			continue;
		}
		
		/// No citation => delete all the citations existing in the database
		if (!isset($INFO_PMID['CITATION']))
		{
			/// No citations in the database => continue
			if (!isset($CITATIONS[$PMID]))continue;

			/// Mark all the citations as to delete
			foreach ($CITATIONS[$PMID] as $PMID_C=>&$C_INFO)
			{
				$C_INFO['DB_STATUS']='TO_DEL';
			}
			continue;
		}
		
		/// If there is no citations in the database, then we need to insert all the citations
		/// so we create the array
		if (!isset($CITATIONS[$PMID]))$CITATIONS[$PMID]=array();
		
		$CIT_LIST=&$CITATIONS[$PMID];
		
		/// Then we compare the citations from the database with the citations from the XML
		foreach ($INFO_PMID['CITATION'] as $PMID_CITED)
		{
			/// Those that are already in the database are marked as valid
			if (isset($CIT_LIST[$PMID_CITED]))
			{
				$CIT_LIST[$PMID_CITED]['DB_STATUS']='VALID';
			}
			else if (!is_numeric($PMID_CITED))continue;
			/// And we create the array to insert the new ones
			//$SEARCH_PMID is a mapping between the PMID and the pmid_entry_id
			else
			{
				$SEARCH_PMID[$PMID_CITED]=-1;
				$CIT_LIST[$PMID_CITED]=array('DB_STATUS'=>'TO_INS','DBID'=>-1);
			}
		}
		

	}

	$TO_DEL=array();
	
	foreach ($CITATIONS as $PMID=>&$LIST)
	{
		foreach ($LIST as $PMID_CITED=>&$C_INFO)
		{
			if (!($C_INFO['DB_STATUS']=='FROM_DB' || $C_INFO['DB_STATUS']=='TO_DEL'))continue;
			
			$TO_DEL[]='('.$PMIDS[$PMID].','.$C_INFO['DBID'].')';
			
		}
	}

	if ($TO_DEL!=array())
	{
		$CHUNKS=array_chunk($TO_DEL,100);
		echo "Deleting ".count($TO_DEL)." citations\n";
		foreach ($CHUNKS as $CHK)
		{
			$res=runQuery("DELETE FROM pmid_citation where (pmid_entry_id,citation_pmid_entry_id) IN (".implode(',',$CHK).')');
			if ($res===false)												failProcess($JOB_ID."B03",'Unable to delete pmid_citation ');
		}
	}


	if ($SEARCH_PMID !=array())
	{
		$STR='SELECT pmid_entry_id, pmid FROM pmid_entry where pmid IN (';
		foreach ($SEARCH_PMID as $PMID=>&$DUMMY)$STR.=$PMID.',';
		$res=runQuery(substr($STR,0,-1).')');
		if ($res===false)												failProcess($JOB_ID."B05",'Unable to query for pmid ids ');
		//echo $STR."\n";
	
		foreach ($res as $line)$SEARCH_PMID[$line['pmid']]=$line['pmid_entry_id'];
	}


	foreach ($CITATIONS as $RPMID=>&$LIST)
	{
		foreach ($LIST as $C_PMID=>&$C_INFO)
		{
			if ($C_INFO['DB_STATUS']!='TO_INS' || $SEARCH_PMID[$C_PMID]==-1)continue;
			$FILE_STATUS['pmid_citation']=true;
			fputs($FILES['pmid_citation'],$PMIDS[$RPMID]."\t".$SEARCH_PMID[$C_PMID]."\n");
		}
	}
	
}


function processAbstract(&$BLOCK,$PMIDS)
{
	global $FILES;
	global $FILE_STATUS;
	global $STATS;
	global $DBIDS;
	global $JOB_ID;


	
	if ($PMIDS!=array())
	{
		$res=runQuery("SELECT  pmid_abstract_id , pmid_entry_id , abstract_type , abstract_text 
			FROM pmid_abstract 
			WHERE pmid_entry_id IN (".implode(",",$PMIDS).')');
		if ($res===false)												failProcess($JOB_ID."C01",'Unable to query for pmid_abstract ');
		$ABSTRACTS=array();
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$ABSTRACTS[$line['pmid_entry_id']][$line['abstract_type']]=$line;
		}
	}

	echo "Compare Abstract\n";
	$TO_DEL=array();
	foreach ($BLOCK as $PMID=>&$INFO_PMID)
	{
		if (!isset($PMIDS[$PMID]))
		{
			echo "MISSING PMID ".$PMID."\n";
		}
		/// There is no abstract in the XML
		if (!isset($INFO_PMID['ABSTRACT']))
		{
			// But there is in the db
			if (isset($ABSTRACTS[$PMIDS[$PMID]]))
			{ 
				/// We delete all the abstracts
				foreach ($ABSTRACTS[$PMIDS[$PMID]] as $T)$TO_DEL[]=$T['pmid_abstract_id'];
			}
			continue;
		}
		
		$ABSTR=&$INFO_PMID['ABSTRACT'];
		if (!isset($PMIDS[$PMID]))continue;
		
		if ( isset($ABSTRACTS[$PMIDS[$PMID]]))
		{
			$DB_ABSTR=&$ABSTRACTS[$PMIDS[$PMID]];
			foreach ($ABSTR as $TYPE=>$TEXT)
			{
				if (isset($DB_ABSTR[$TYPE]))
				{
					if ($TEXT==$DB_ABSTR[$TYPE]['abstract_text'])continue;
					

					///Remove the old abstract
					$TO_DEL[]=$DB_ABSTR[$TYPE]['pmid_abstract_id'];
					
					/// We don't know how to process an array
					if (is_array($TEXT))
					{
						echo "ISSUE WITH ".$PMID."\n";
						print_r($ABSTR);
						print_r($TEXT);
						failProcess($JOB_ID.'C02','Issue with abstract');
					}
					
					/// Add the new abstract
					$DBIDS['pmid_abstract']++;
					
					$STATS['UPD_ABSTR']++;
					$FILE_STATUS['pmid_abstract']=true;
					fputs($FILES['pmid_abstract'],
						$DBIDS['pmid_abstract']."\t".
						$PMIDS[$PMID]."\t".
						$TYPE."\t".'"'.
						str_replace('"','""',$TEXT).'"'."\n");
					
					
				}
				else 
				{
					$DBIDS['pmid_abstract']++;
					
					$FILE_STATUS['pmid_abstract']=true;
					fputs($FILES['pmid_abstract'],
						$DBIDS['pmid_abstract']."\t".
						$PMIDS[$PMID]."\t".
						$TYPE."\t".'"'.
						str_replace('"','""',$TEXT).'"'."\n");
					
				}
			}
		}
		else/// Not in the db -> we add them all
		{
			foreach ($ABSTR as $TYPE=>$TEXT)
			{
				$DBIDS['pmid_abstract']++;
				$FILE_STATUS['pmid_abstract']=true;
				$STATS['NEW_ABSTR']++;
				fputs($FILES['pmid_abstract'],
					$DBIDS['pmid_abstract']."\t".
					$PMIDS[$PMID]."\t".
					$TYPE."\t".'"'.
					str_replace('"','""',$TEXT).'"'."\n");
			}
		}
	

	}

	
	echo ("Delete Abstract\n");
	if ($TO_DEL!=array())
	{
		echo "DELETE FROM pmid_abstract where pmid_Abstract_id IN (".implode(',',$TO_DEL).')'."\n";
		$res=runQueryNoRes("DELETE FROM pmid_abstract where pmid_Abstract_id IN (".implode(',',$TO_DEL).')');
		if ($res===false)											failProcess($JOB_ID."C02",'Unable to delete pmid_abstract ');

	}
	
}





function processInstit(&$BLOCK,&$PMIDS,&$LIST_INSTIT)
{
	global $JOB_ID;
	global $DBIDS;
	global $FILES;
	global $FILE_STATUS;
	global $STATS;
	
	$time=microtime_float();
	$STR='';
	$LIST_INSTIT=array();
	foreach ($BLOCK as $PMID=>&$INFO_PMID)
	{

		if (isset($INFO_PMID['AUTHORS']))
		foreach ($INFO_PMID['AUTHORS'] as $BLK)
		{
			if ($BLK['Instit']=='')continue;
			$LIST_INSTIT[md5($BLK['Instit'])]=array(-1,'INI',$BLK['Instit']);
			$STR.="'".md5($BLK['Instit'])."',";
		}
	}
	
	echo "TIME\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();
	if ($STR!='')
	{
		echo ("Process Instit");
		//	echo "SEARCH INSTIT\t".count($LIST_INSTIT)."\n";
		$query="SELECT pmid_instit_id,instit_hash FROM pmid_instit where instit_hash in (".substr($STR,0,-1).')';
		
		$res=runQuery($query);
		if ($res===false)												failProcess($JOB_ID."D01",'Unable to query for pmid_instit ');
		
		echo ("Found ".count($res).'/'.count($LIST_INSTIT)." Instit\n");
		
		foreach ($res as $line)
		{
			$LIST_INSTIT[$line['instit_hash']]=array($line['pmid_instit_id'],'FROM_DB');
		}
	}



	echo ("Adding new Instit to file\n");
	foreach ($LIST_INSTIT as $INSTIT_HASH=>$ID)
	{
		if ($ID[0]!=-1)continue;
		++$DBIDS['pmid_instit'];
		
		$LIST_INSTIT[$INSTIT_HASH]=array($DBIDS['pmid_instit'],'NEW',$ID[2]);
		
		$FILE_STATUS['pmid_instit']=true;
		$STATS['NEW_INSTIT']++;
				
		fputs($FILES['pmid_instit'],
			$DBIDS['pmid_instit']."\t".
			'"'.str_replace('"','""',$ID[2]).'"'."\t".
			$INSTIT_HASH."\n");
	}
	echo "TIME\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();
	

}


function processAuthor(&$BLOCK,&$PMIDS,&$LIST_INSTIT)
{
	global $JOB_ID;
	global $DBIDS;
	global $FILES;
	global $FILE_STATUS;
	global $STATS;

	echo ("Listing authors\n");
	$time=microtime_float();
	$AUTHORS=array();
	$N_AUTH=0;
	$SEARCH_NAMES=array();
	$AUTHOR_MAP=array();
	foreach ($BLOCK as $PMID=>$INFO_PMID)
	{
		
		if (!isset($INFO_PMID['AUTHORS']))continue;


		foreach ($INFO_PMID['AUTHORS'] as  $POS_AUTHOR=>$BLK)
		{
			$BLK['Instit']=$LIST_INSTIT[md5($BLK['Instit'])][0];
			if (!isset($SEARCH_NAMES[$BLK['Last']]))
			{
				++$N_AUTH;
				$AUTHOR_MAP[$N_AUTH][$PMID]=$POS_AUTHOR;
				$AUTHORS[$N_AUTH]=$BLK;
				$SEARCH_NAMES[$BLK['Last']][]=$N_AUTH;
			}
			else
			{
				$FOUND=false;
				foreach ($SEARCH_NAMES[$BLK['Last']] as $POS_NAMES)
				{
					if ($AUTHORS[$POS_NAMES]!=$BLK)continue;
					$FOUND=true;
					$AUTHOR_MAP[$POS_NAMES][$PMID]=$POS_AUTHOR;
				}
				if ($FOUND)continue;
				++$N_AUTH;
				$AUTHOR_MAP[$N_AUTH][$PMID]=$POS_AUTHOR;
				$AUTHORS[$N_AUTH]=$BLK;
				$SEARCH_NAMES[$BLK['Last']][]=$N_AUTH;
			}
			
			
		}
		
	}
	

	echo "TIME\t".round(microtime_float()-$time,2)."\nN AUTHORS:\t".$N_AUTH."\n";$time=microtime_float();
	echo "Search authors\n";


	$LIST_AUTHORS_ID=array();
	$CHUNKS=array_chunk($AUTHORS,5000);
	
	foreach ($CHUNKS as $CHUNK)
	{
		echo ("Run Author search\n");
		$query='SELECT md5_hash, pmid_author_id, last_name, first_name, initials,is_valid_author,pmid_instit_id,orcid_id FROM pmid_author where md5_hash IN (';	
		$MAP=array();
		foreach ($CHUNK as $K=>$BLK)
		{
			/// We use a combination of the fields to create a unique hash to speed up the search
			$str_author=$BLK['Last'].'___'.$BLK['First'].'___'.$BLK['Ini'].'___'.$BLK['Instit'].'___'.$BLK['Valid'].'___'.$BLK['ORCID'];
			$md5=md5($str_author);
			$query.="'".$md5."',";
			
		}
		$query=substr($query,0,-1).')';
		//	echo $query."\n";

		$res=runQuery($query);
		if ($res===false )											failProcess($JOB_ID."E01",'Unable to query for pmid_author ');
		echo "TIME\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();
		echo ("Process Authors\n");
		
		foreach ($res as $line)
		{
			/// Create the record:
			$BLK=array(
				'Last'=>$line['last_name'],
				'First'=>$line['first_name'],
				'Ini'=>$line['initials'],
				'Valid'=>$line['is_valid_author'],
				'Instit'=>$line['pmid_instit_id']);
		
			if ($line['orcid_id']!='')$BLK['ORCID']=$line['orcid_id'];
			if ($line['first_name']=='')$line['first_name']='NULL';
			if ($line['initials']=='')$line['initials']='NULL';

			/// Compare to existing record:
			foreach ($SEARCH_NAMES[$line['last_name']] as $POS_NAMES)
			{
				$P=&$AUTHORS[$POS_NAMES];
				if ($P['Last']!=$line['last_name'])continue;
				if ($P['First']!=$line['first_name'])continue;
				if ($P['Ini']!=$line['initials'])continue;
				if ($P['Valid']!=$line['is_valid_author'])continue;
				if ($P['Instit']!=$line['pmid_instit_id'])continue;
				
				$P['PMID_AUTHOR_ID']=$line['pmid_author_id'];
				$LIST_AUTHORS_ID[]=$line['pmid_author_id'];
			}
			
		}
		echo "TIME\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();
	}
//print_r($AUTHORS);
	//exit;
	echo ("Process Map\n");
	
	/// Now that we have the authors ID, we can create the map
	$res=runQuery("SELECT * FROM pmid_author_map 
		where ".((count($LIST_AUTHORS_ID)>0)?" pmid_author_id IN (".implode(',',$LIST_AUTHORS_ID).') AND ':'').' pmid_entry_id IN ('.implode(',',$PMIDS).')');
	
	if( $res===false)												failProcess($JOB_ID."E02",'Unable to query for pmid_author_map ');
	
	echo "TIME\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();
	echo ("Compare map\n");
	$AUTHOR_MAP_ID=array();
	foreach ($res as $line)
	{
		$AUTHOR_MAP_ID[$line['pmid_author_id']][$line['pmid_entry_id']]=$line['position'];
	}

	foreach ($AUTHORS as $POS=>$BLK)
	{
		
		//if ($BLK['Last']=='Yoo'){print_r($BLK);}
		if (!isset($BLK['PMID_AUTHOR_ID']))
		{
			//if ($BLK['Last']=='Shabana') {print_r($BLK);echo "\tNEW\n";}
			$str_author=$BLK['Last'].'___'.$BLK['First'].'___'.$BLK['Ini'].'___'.$BLK['Instit'].'___'.$BLK['Valid'].'___'.$BLK['ORCID'];
			
			$BLK['MD5_HASH']=md5($str_author);
			
			$DBIDS['pmid_author']++;;
			
			$NEW_AUTHOR=true;
			
			$STATS['NEW_AUTHOR']++;
			$FILE_STATUS['pmid_author']=true;
			fputs($FILES['pmid_author'],
				$DBIDS['pmid_author']."\t".'"'.
				str_replace('"','""',$BLK['Last']).'"'."\t".
				(($BLK['First']=='NULL')?'NULL':'"'.str_replace('"','""',$BLK['First']).'"')."\t".
				$BLK['Ini']."\t".
				$BLK['Instit']."\t".
				(($BLK['ORCID']=='')?'NULL':$BLK['ORCID'])."\t".
				$BLK['Valid']."\t".
				$BLK['MD5_HASH']."\n");

			$BLK['STATUS']='VALID';
			$BLK['PMID_AUTHOR_ID']=$DBIDS['pmid_author'];
		}
		foreach ($AUTHOR_MAP[$POS] as $PMID=>$POS_AUTHOR)
		{
			if (!isset($PMIDS[$PMID]))continue;
			if (isset($AUTHOR_MAP_ID[$BLK['PMID_AUTHOR_ID']][$PMIDS[$PMID]]))continue;
			
			$DBIDS['pmid_author_map']++;;
			
			$STATS['NEW_AUTHOR_MAP']++;
			
			$NEW_MAP=true;
			$FILE_STATUS['pmid_author_map']=true;
			fputs($FILES['pmid_author_map'],$DBIDS['pmid_author_map']."\t".$PMIDS[$PMID]."\t".$BLK['PMID_AUTHOR_ID']."\t".($POS_AUTHOR+1)."\n");
			//echo $DBIDS['pmid_author_map']."\t".$PMIDS[$PMID]."\t".$BLK['PMID_AUTHOR_ID']."\t".($POS_AUTHOR+1)."\n";
			
		}
	}
	
	echo "TIME\t".round(microtime_float()-$time,2)."\n";$time=microtime_float();
}










function getCompanies()
{
	global $JOB_ID;
	$res=runQuery("SELECT * FROM company_synonym");
	if ($res===false)	failProcess($JOB_ID."011",'Unable to get companies');
	$COMPANIES=array();
	foreach ($res as $N=>$line)
	{
		$COMPANIES[$line['company_entry_id']][]=$line['company_syn_name'];
	}
}


function xml_attribute($object, $attribute)
{
    if(isset($object[$attribute]))
        return (string) $object[$attribute];
	else return false;
}




// $res=runQuery("SELECT max(pmid_instit_id) c from pmid_instit");
// $MAXID=$res[0]['c'];
// $BL=50;
// $STEP=ceil($MAXID/$BL);


// $fp=fopen('stats','w');
// for ($I=0;$I<$STEP;++$I)
// {

// $res=runQuery("SELECT PMID_INSTIT_ID FROM PMID_INSTIT WHERE ((INSTIT_NAME NOT LIKE 'Department%' AND CHAR_LENGTH(INSTIT_NAME)<40) OR CHAR_LENGTH(INSTIT_NAME)>=40) AND pmid_instit_id >=".($I*$BL)." AND pmid_instit_id <= ".(($I+1)*$BL));
// $list=array();
// foreach ($res as $line)$list[]=$line['pmid_instit_id'];
// 	echo $I.'/'.$STEP."::".count($list)."\t";
// $res=runQuery("SELECT co,r_id,c_id, r.instit_name as ref_name, c.instit_name as comp_name
// FROM (
// SELECT COUNT(*) co, r_id, c_id FROM (
// SELECT pi12.pmid_instit_id as r_id,
// pi34.pmid_instit_id as c_id FROM 

// pmid_author a1,
// pmid_author a2,
// pmid_author a3,
// pmid_author a4,
// pmid_author_map pam12_1,
// pmid_author_map pam12_2,
// pmid_author_map pam34_3,
// pmid_author_map pam34_4,
// pmid_instit pi12,
// pmid_instit pi34
// where
// a1.last_name = a3.last_name AND a1.first_name = a3.first_name AND
// a2.last_name = a4.last_name AND a2.first_name = a4.first_name AND
// pam12_1.pmid_author_id = a1.pmid_author_id AND 
// pam12_2.pmid_author_id = a2.pmid_author_id AND 
// pam34_3.pmid_author_id = a3.pmid_author_id AND 
// pam34_4.pmid_author_id = a4.pmid_author_id AND 
// pam12_1.pmid_entry_id = pam12_2.pmid_entry_id AND 
// pam34_3.pmid_entry_id = pam34_4.pmid_entry_id AND 
// pi12.pmid_instit_id = a1.pmid_instit_id AND 
// pi12.pmid_instit_id = a2.pmid_instit_id AND 
// pi34.pmid_instit_id = a3.pmid_instit_id AND 
// pi34.pmid_instit_id = a4.pmid_instit_id AND 
// pi34.pmid_instit_id>pi12.pmid_instit_id AND 
// pi34.pmid_instit_id!= 1 AND 
// pi12.pmid_instit_id!=1 AND
// pi12.pmid_instit_id IN  (".implode(',',$list).")
// ) k GROUP BY r_id,c_id) t, pmid_instit r, pmid_instit c
// where  t.co > 5 AND t.r_id = r.pmid_instit_id
// AND t.c_id = c.pmid_instit_Id");
// echo count($res)."\n";

// foreach ($res as $line)
// {
// fputs($fp,implode("\t",$line)."\n");
// }
// }
//>= ".($I*$BL)." AND pi12.pmid_instit_id < ".(($I+1)*$BL)."

	

?>
