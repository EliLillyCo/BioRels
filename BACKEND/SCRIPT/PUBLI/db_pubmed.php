<?php
ini_set('memory_limit','3000M');
error_reporting(E_ALL);



/**
 SCRIPT NAME: db_pubmed
 PURPOSE:     Process pubmed data
 
*/
$JOB_NAME='db_pubmed';

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
	/// Get parent job information
	$DL_PUBMED_INFO=$GLB_TREE[getJobIDByName('dl_pubmed')];

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_PUBMED_INFO['TIME']['DEV_DIR'];	

	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);


	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$DL_PUBMED_INFO['TIME']['DEV_DIR'];



 addLog("Processing journals")	;
	
 	$JOURNALS=array();
 	$J_ABBR=array();
	getJournals($J_ABBR,$JOURNALS);

// exit;


addLog("Get MAx DBIDS")	;
	/// At this step, we are just going to insert  entries
	$DBIDS=array(
		'pmid_entry'=>-1,
		'pmid_journal'=>-1,
	);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) co FROM '.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)										failProcess($JOB_ID."003",'Unable to get max primary key for '.$TBL);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	}
	print_r($DBIDS);
	$DBIDS['DESC_FILES']=0;
	
	
	
	
	/// Those are the tables we are going to insert into and the corresponding columns in the output file
	$COL_ORDER=array(
		'pmid_entry'=>'( pmid_entry_id , pmid , mdate , publication_date , title , doi , abstract , volume , pages , pmid_journal_id , month_1910)');



	$FILES=array();
	$FILE_STATUS=array();
	foreach ($COL_ORDER as $TYPE=>$CTL)
	{
		$FILE_STATUS[$TYPE]=false;
		$FILES[$TYPE]=fopen($TYPE.'.csv','w');
		if (!$FILES[$TYPE])																				failProcess($JOB_ID."004",'Unable to open '.$TYPE.'.csv');
	}
	
	$fp=fopen('ENTRIES.xml','r');if (!$fp)																failProcess($JOB_ID."005",'Unable to open ENTRIES.xml');
	$fpE=fopen('ISSUES','w');	if (!$fpE)																failProcess($JOB_ID."006",'Unable to open ISSUES');
	$BLOCK=array();$N_BLOCK=0;$START=false;
	
	
	
	$UPD_DATES=array();
	while(!feof($fp))
	{
		
		$fpos=ftell($fp);
		
		/// If it starts with PubMedArticle, it's a new record
		$line=stream_get_line($fp,100000,"\n");
		if (strpos($line,'<PubmedArticle>')===false)continue;
		$XML_RECORD=array($line);
		/// So we keep reading until we get to </PubmedArticle> and push it in XML_RECORD
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000000,"\n");
			if (strpos($line,'</PubmedArticle>')!==false)break;
			$XML_RECORD[]=$line;
		}
		$XML_RECORD[]=$line;
		/// This will parse the XML string
		$xml = simplexml_load_string(implode("\n",$XML_RECORD), "SimpleXMLElement", LIBXML_NOCDATA);
		/// By encoding it in json and decoding it, we can convert it as an array
		$json = json_encode($xml);
		$RECORD = json_decode($json,TRUE);
		if ($RECORD===false)
		{
			print_r($XML_RECORD);
			continue;
		}
		processRecord($RECORD,$BLOCK,$XML_RECORD);
	
		if (count($BLOCK)<5000)continue;
		++$N_BLOCK;
		echo "######## PROCESSING ".(($N_BLOCK-1)*5000).' -> '.(($N_BLOCK)*5000)."\t".
			"FILE POS :".$fpos."\t".
			count($BLOCK)."\t".
			array_keys($BLOCK)[0]."\t".
			array_keys($BLOCK)[count($BLOCK)-1]."\n";;


		// if (isset($BLOCK[34237538]))$START=true;
		
		// if (!$START){$BLOCK=array();continue;}
		/// Once we have enough to process, we pe
		processBlock($BLOCK);
		
		//exit;
		$BLOCK=array();
			foreach ($COL_ORDER as $TYPE=>$CTL)
		{
			$FILES[$TYPE]=fopen($TYPE.'.csv','w');
			if (!$FILES[$TYPE])		failProcess($JOB_ID."007",'Unable to open '.$TYPE.'.csv');
		}

		//$FILES['pmid_entry']

		//$VOLUME=

		
//exit;
	}
	fclose($fp);
	
	processBlock($BLOCK);
	

	

successProcess();





function getJournals(&$J_ABBR,&$JOURNALS)
{
	global $JOB_ID;
	global $GLB_VAR;
	global $DB_INFO;



	$HAS_NEW=false;


	$query="SELECT pmid_journal_id, journal_name, journal_abbr,issn_print, issn_online,iso_abbr,nlmid FROM pmid_journal";
	$res=array();
	$res=runQuery($query);
	if ($res===false)															failProcess($JOB_ID."A01",'Unable to get journals');
	
	$TMP_JOURNAL=array();
	
	$MAX_JOURNAL_ID=-1;	/// This is the maximum journal primary key value
	
	$JOURNALS=array();	/// This is a map of journal name to journal id
	
	$J_ABBR=array();
	foreach ($res as $tab)
	{
		
		$TMP_JOURNAL[$tab['journal_name']]=$tab;
		$JOURNALS[$tab['journal_name']]=$tab['pmid_journal_id'];
		$J_ABBR[$tab['journal_abbr']]=$tab['pmid_journal_id'];
		$MAX_JOURNAL_ID=max($tab['pmid_journal_id'],$MAX_JOURNAL_ID);
	}
	
	addLog("Processing Journal")	;

	if (!checkFileExist('Journals.csv'))															failProcess($JOB_ID."A02",'No Journals.csv found');
	$fp=fopen('Journals.csv','r');if(!$fp)															failProcess($JOB_ID."A03",'Unable to open Journals.csv');
	
	/// Create template array:
	$INFO=array(
		'JrId'=>'NULL',
		'JournalTitle'=>'NULL',
		'MedAbbr'=>'NULL',
		'ISSN (Print)'=>'NULL', 
		'ISSN (Online)'=>'',
		'IsoAbbr'=>'',
		'NlmId'=>'');


	$ST=true;
	$N_JUPD=0;
	$N_JINS=0;

	$fpO=fopen('Journals_db.csv','w');if (!$fpO)													failProcess($JOB_ID."A04",'Unable to open Journals_db.csv');
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
		
		/// End of record -> we process
		if (substr($line,0,2)!='--')
		{
			// Part of record, we insert into info
			$pos=strpos($line,':');
			$tag=substr($line,0,$pos);
			$value=substr($line,$pos+2);
			$INFO[$tag]=trim($value);
			continue;
		}
		
		if ($ST==false)
		{
			/// Entry exist, let's check for change
			if (isset($TMP_JOURNAL[$INFO['JournalTitle']]))
			{
				$CHANGE=false;
				$ENTRY=&$TMP_JOURNAL[$INFO['JournalTitle']];
				
				if ($ENTRY['journal_abbr']!=$INFO['MedAbbr'])		
				{
					echo "abbr|".$ENTRY['journal_abbr'].'|'.$INFO['MedAbbr']."|\t";
					$ENTRY['journal_abbr']=$INFO['MedAbbr'];
					$CHANGE=true;
				}
				if ($ENTRY['issn_print']!=$INFO['ISSN (Print)'])	
				{
					echo "Print|".$ENTRY['issn_print'].'|'.$INFO['ISSN (Print)']."|\t";
					$ENTRY['issn_print']=$INFO['ISSN (Print)'];
					$CHANGE=true;
				}
				if ($ENTRY['issn_online']!=$INFO['ISSN (Online)'])	
				{
					echo "online|".$ENTRY['issn_online'].'|'.$INFO['ISSN (Online)']."|\t";
					$ENTRY['issn_online']=$INFO['ISSN (Online)'];
					$CHANGE=true;
				}
				if ($ENTRY['iso_abbr']!=$INFO['IsoAbbr'])		
				{
					echo "|".$ENTRY['iso_abbr']."|".$INFO['IsoAbbr']."|";
					$ENTRY['iso_abbr']=$INFO['IsoAbbr'];
					$CHANGE=true;
				}
				if ($ENTRY['nlmid']!=$INFO['NlmId'])			
				{
					echo "|".$ENTRY['nlmid']."\t".$INFO['NlmId']."|\t";
					$ENTRY['nlmid']=$INFO['NlmId'];
					$CHANGE=true;
				}
				
				//Any change -> update
				if ($CHANGE)
				{

					$query="UPDATE PMID_JOURNAL SET ";
					$query.="journal_abbr='".str_replace("'","''",str_replace('"','\"',$INFO['MedAbbr']))."',".
					"issn_print='".str_replace("'","''",str_replace('"','\"',$INFO['ISSN (Print)']))."',".
					"issn_online='".str_replace("'","''",str_replace('"','\"',$INFO['ISSN (Online)']))."',".
					"iso_abbr='".str_replace("'","''",str_replace('"','\"',$INFO['IsoAbbr']))."',".
					"nlmid='".str_replace("'","''",str_replace('"','\"',$INFO['NlmId']))."'
					 WHERE pmid_journal_id=".$ENTRY['pmid_journal_id'];
				
					//echo "\t".$query."\n";
					++$N_JUPD;
					if (!runQueryNoRes($query)) 												failProcess($JOB_ID."A05",'Unable to update journal');
				

				}
			}
			else
			{
				/// New -> insert
				++$MAX_JOURNAL_ID;
				$JOURNALS[$INFO['JournalTitle']]=$MAX_JOURNAL_ID;
				fputs($fpO,
					$MAX_JOURNAL_ID.
				"\t".str_replace('"','""',$INFO['JournalTitle'])
				."\t".str_replace('"','""',$INFO['MedAbbr'])
				."\t".str_replace('"','""',$INFO['ISSN (Print)'])
				."\t".str_replace('"','""',$INFO['ISSN (Online)'])
				."\t".str_replace('"','""',$INFO['IsoAbbr'])
				."\t".str_replace('"','""',$INFO['NlmId'])."\n");
				$HAS_NEW=true;
				
			}
		}
		$ST=false;
		foreach ($INFO as $K=>$V)$INFO[$K]='NULL';
			
		
		
			
		
	}
	fclose($fp);
	fclose($fpO);
	if ($HAS_NEW)
	{
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_journal (pmid_journal_id,journal_name,journal_abbr,issn_print,issn_online,iso_abbr,nlmid)  FROM \''."Journals_db.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )																			failProcess($JOB_ID."A06",'Unable to insert into pmid_journal'); 
	}


}


function processRecord(&$RECORD,&$BLOCK,&$XML_RECORD)
{
	global $JOB_ID;
	global $JOURNALS;
	global $J_ABBR;
	global $DBIDS;
	global $FILES;

	$BLOCK_R=array();
	try{
		
		
		/// PMID must be numeric
		if (!is_numeric($RECORD['MedlineCitation']['PMID']))											failProcess($JOB_ID."B01",'PMID '.$PMID. ' not numeric');
		
		$PMID=$RECORD['MedlineCitation']['PMID'];

		/// Getting the title
		if (preg_match('/\<ArticleTitle\>(.{1,4000})\<\/ArticleTitle\>/',implode("\n",$XML_RECORD),$matches))
		{
			$TITLE=$matches[1];
		}
		else if (preg_match('/\<VernacularTitle\>(.{1,4000})\<\/VernacularTitle\>/',implode("\n",$XML_RECORD),$matches))
		{
			$TITLE=$matches[1];
		}
		
		/// Getting the journal and checking if we have it in the database
		$JOURNAL_ID='NULL';
		/// We look by the abbreviation first
		if (isset($RECORD['MedlineCitation']['Article']['Journal']['ISOAbbreviation']))
		{
			if (isset($J_ABBR[$RECORD['MedlineCitation']['Article']['Journal']['ISOAbbreviation']]))
			$JOURNAL_ID=$J_ABBR[$RECORD['MedlineCitation']['Article']['Journal']['ISOAbbreviation']];
		}
		/// Then by journal name
		if ($JOURNAL_ID=='NULL' && isset($RECORD['MedlineCitation']['Article']['Journal']['Title']))
		{
			if (isset($JOURNALS[$RECORD['MedlineCitation']['Article']['Journal']['Title']]))
			$JOURNAL_ID=$JOURNALS[$RECORD['MedlineCitation']['Article']['Journal']['Title']];
		}
		/// Not found, we create it
		if ($JOURNAL_ID=='NULL')
		{	

			$DBIDS['pmid_journal']++;
			$TITLE='NULL';
			if (isset($RECORD['MedlineCitation']['Article']['Journal']['Title']))
			{
				$TITLE=$RECORD['MedlineCitation']['Article']['Journal']['Title'];
			}
			
			$ISO='NULL';
			if (isset($RECORD['MedlineCitation']['Article']['Journal']['ISOAbbreviation']))
			{
				$ISO=$RECORD['MedlineCitation']['Article']['Journal']['ISOAbbreviation'];
			}
			
			$ISSN='NULL';
			if (isset($RECORD['MedlineCitation']['Article']['Journal']['ISSN']))
			{
				$ISSN=$RECORD['MedlineCitation']['Article']['Journal']['ISSN'];
			}
			
			$query='INSERT INTO pmid_journal (pmid_journal_id,journal_name,journal_abbr,issn_print,issn_online,iso_abbr,nlmid) 
					VALUES ('.$DBIDS['pmid_journal'].",
					'".str_replace("'","''",$TITLE)."',
					NULL,
					'".$ISSN."',
					NULL,
					'".str_replace("'","''",$ISO)."',
					NULL)";

			echo $query."\n";
			if (!runQueryNoRes($query)) 																failProcess($JOB_ID."B02",'Unable to insert new journal');
			
			$JOURNAL_ID=$DBIDS['pmid_journal'];
			
			/// Assigning the journal id to the abbreviation and title
			if ($ISO!='NULL')$JOURNALS[$ISO]=$JOURNAL_ID;
			
			if ($TITLE!='NULL')$JOURNALS[$TITLE]=$JOURNAL_ID;
			
			
		}
		
		$BLOCK_R=array('TITLE'=>trim($TITLE),
					'JOURNAL'=>$JOURNAL_ID,
					'DATE'=>'NULL',
					'VOLUME'=>'NULL',
					'ISSUE'=>'NULL',
					'PAGE'=>'NULL',
					'DOI'=>'NULL',
					'STATUS'=>'NULL','MONTH_1910'=>'NULL',
					'ABSTRACT'=>array(),'DB_STATUS'=>'INI');
		


		
		/// Then we add the issue and volume
		if (isset($RECORD['MedlineCitation']['Article']['Journal']['JournalIssue']))
		{

			$J_ISS=$RECORD['MedlineCitation']['Article']['Journal']['JournalIssue'];
			//print_r($J_ISS);
			if ($J_ISS['@attributes']['CitedMedium']!='Internet')
			{
				$BLOCK_R['VOLUME']=isset($J_ISS['Volume'])?$J_ISS['Volume']:'NULL';
				$BLOCK_R['ISSUE']=isset($J_ISS['Issue'])?$J_ISS['Issue']:'NULL';
			}
			
		}
		/// The page
		if (isset($RECORD['MedlineCitation']['Article']['Journal']['Pagination']['MedlinePgn']))
		{
			$BLOCK_R['PAGE']=$RECORD['MedlineCitation']['Article']['Journal']['Pagination']['MedlinePgn'];
		}
		$DT=$RECORD['MedlineCitation']['Article']['Journal']['JournalIssue'];
		/// The year
		if (isset($DT['Year']))
		{
			$DAY=(isset($DT['Day'])?$DT['Day']:'01');
			$MONTH=(isset($DT['Month'])?$DT['Month']:'Jan');
			$BLOCK_R['DATE']=date('Y-m-d',strtotime($DAY.' '.$MONTH.' '.$DT['Year']));
		}
		else if (isset($DT['PubDate']))
		{
			$DT=$DT['PubDate'];
			if (isset($DT['MedlineDate']))
			{
				$pos=strpos($DT['MedlineDate'],"-");
				if ($pos!==false)
				{
					$DT['MedlineDate']=substr($DT['MedlineDate'],0,$pos);
				}
				$BLOCK_R['DATE']=date('Y-m-d',strtotime($DT['MedlineDate']));
			}

			else 
			{
				$DAY=(isset($DT['Day'])?$DT['Day']:'01');
				$MONTH=(isset($DT['Month'])?$DT['Month']:'Jan');
				$BLOCK_R['DATE']=date('Y-m-d',strtotime($DAY.' '.$MONTH.' '.$DT['Year']));
			}
		}

		/// Compute the number of months from the publication date to 1910
		if ($BLOCK_R['DATE']!='NULL')
		{
			$ts1 = strtotime($BLOCK_R['DATE']);
			$ts2 = strtotime('1910-01-01');

			$year1 = date('Y', $ts1);
			$year2 = date('Y', $ts2);

			$month1 = date('m', $ts1);
			$month2 = date('m', $ts2);

			$BLOCK_R['MONTH_1910'] = (($year2 - $year1) * 12) + ($month2 - $month1);
		}

		/// And the status
		$BLOCK_R['STATUS']=$RECORD['PubmedData']['PublicationStatus'];
		/// The doi
		$ELOC=&$RECORD['MedlineCitation']['Article']['ELocationID'];
		
		/// Finding the doi based on regex
		if (is_array($ELOC))
		{
			
			foreach ($ELOC as $ID)
			{
				if ($ID!=''&&preg_match('/^10.[0-9]{1,5}\/[^\s]+$/i',$ID))$BLOCK_R['DOI']=$ID;

			}
		}
		else 
		{
			if ($ELOC!='' && preg_match('/^10.[0-9]{1,5}\/[^\s]+$/i',$ELOC))$BLOCK_R['DOI']=$ELOC;
		}

		/// And we push it into an array
		$BLOCK[$PMID]=$BLOCK_R;
	}catch (ErrorException $e) 
	{
		var_dump($e);
		print_r($BLOCK_R);
		print_r($RECORD);
		

	}catch (TypeError $e) {
		var_dump($e);
		print_r($BLOCK_R);
		print_r($RECORD);
		//exit;

	}
}








function processBlock()
{
	global $BLOCK;
	global $DBIDS;
	global $FILES;
	global $GLB_VAR;
	global $DB_INFO;

		
	$res=runQuery("SELECT pmid,publication_date,title,doi,volume,pages,pe.pmid_journal_id, issue, iso_abbr, month_1910,pmid_status,pmid_entry_id
		FROM pmid_entry pe
		LEFT JOIN pmid_journal pj ON pj.pmid_journal_id = pe.pmid_journal_id WHERE pmid IN (".implode(',',array_keys($BLOCK)).')');
	if ($res===false)																failProcess($JOB_ID."C01",'Unable to query publications');
	$UPD_DATES=array();
	$UPD_STATUS=array();
	///Here we have all the existing record
	/// so we are going t ocompare the results and update if necessary
	foreach ($res as $line)
	{
		$PMID=$line['pmid'];
		$ENTRY=&$BLOCK[$PMID];
		try{
		
		$ENTRY['DB_STATUS']='VALID';

		if ($line['pages']=='')$line['pages']='NULL';
		if ($line['volume']=='')$line['volume']='NULL';
		if ($line['issue']=='')$line['issue']='NULL';
		if ($line['doi']=='')$line['doi']='NULL';
		if ($line['pmid_status']=='')$line['pmid_status']='NULL';
			/// Here we are going to compare each parameter
			/// and build the update query if necessary
		$query="UPDATE pmid_entry SET ";
		if (strpos($line['publication_date'],$ENTRY['DATE'])===false)
		{
			if(strtotime($ENTRY['DATE'])>strtotime($line['publication_date']))
			{
			$UPD_DATES[$ENTRY['DATE']][]=$line['pmid_entry_id'];
			echo $PMID."\tDATE\t".$ENTRY['DATE']."\t".$line['publication_date']."\n";
			}
			//$ENTRY['DB_STATUS']='TO_UPD';
			//$query.="publication_date='".$ENTRY['DATE']."',";
		}
		if ($line['title']!=$ENTRY['TITLE'])
		{
			echo $PMID."\tTITLE\t".$ENTRY['TITLE']."\t".$line['title']."\n";
			$query.="title='".str_replace("'","''",$ENTRY['TITLE'])."',";
			$ENTRY['DB_STATUS']='TO_UPD';
		}
		if ($line['doi']!=$ENTRY['DOI'])
		{
			echo $PMID."\tDOI\t".$ENTRY['DOI']."\t".$line['doi']."\n";
			$ENTRY['DB_STATUS']='TO_UPD';
			if ($ENTRY['DOI']=='NULL')$query.='doi = null,';
			else 					  $query.=" doi ='".$ENTRY['DOI']."',";
		}
		if ($line['pages']!=$ENTRY['PAGE']){
			echo $PMID."\tPAGE\t".$ENTRY['PAGE']."\t".$line['pages']."\n";
			$ENTRY['DB_STATUS']='TO_UPD';
			if ($ENTRY['PAGE']=='NULL')$query.='pages = null,';else $query.="pages ='".$ENTRY['VOLUME']."',";
		}
		if ($line['pmid_journal_id']!=$ENTRY['JOURNAL'])
		{
		//	echo $PMID."\tJOURNAL\t".$ENTRY['JOURNAL']."\t".$line['pmid_journal_id']."\n";
			$query.='pmid_journal_id ='.$ENTRY['JOURNAL'].','; 
			$ENTRY['DB_STATUS']='TO_UPD';}
		if ($line['pmid_status']!=$ENTRY['STATUS'])
		{
			echo $PMID."\tSTATUS\t".$ENTRY['STATUS']."\t".$line['pmid_status']."\n";//$ENTRY['DB_STATUS']='TO_UPD';
			$UPD_STATUS[$ENTRY['STATUS']][]=$line['pmid_entry_id'];

			//if ($ENTRY['STATUS']=='NULL')$query.='pmid_status = null,';else $query.="pmid_status ='".$ENTRY['STATUS']."',";
		}
		if ($line['volume']!=$ENTRY['VOLUME']){
			echo $PMID."\tVOLUME\t".$ENTRY['VOLUME']."\t".$line['volume']."\n";
			$ENTRY['DB_STATUS']='TO_UPD';
			if ($ENTRY['VOLUME']=='NULL')$query.=' volume = null,';else $query.="volume ='".$ENTRY['VOLUME']."',";
		}
		if ($line['issue']!=$ENTRY['ISSUE']){
			echo $PMID."\tISSUE\t".$ENTRY['ISSUE']."\t".$line['issue']."\n";
			if ($ENTRY['ISSUE']=='NULL')$query.=' issue = null,';else $query.=" issue ='".$ENTRY['ISSUE']."',";
			$ENTRY['DB_STATUS']='TO_UPD';
		}
		/// Entry need to be updated 
		if ($ENTRY['DB_STATUS']=='TO_UPD')
		{
			

			$query=substr($query,0,-1).' WHERE pmid_entry_id = '.$line['pmid_entry_id'];
			
			if (!runQueryNoRes($query))	failProcess($JOB_ID."C02",'Unable to update pmid_entry');
			
		}
	}catch (ErrorException $e) {
		var_dump($e);
	//	exit;
		// print_r($BLOCK_R);
		// print_r($RECORD);

	}

	}
	if (count($UPD_STATUS)!=0)
	{
		echo count($UPD_STATUS)." status to update\n";
		foreach ($UPD_STATUS as $STATUS=>$LIST_D)
		{
			echo "\t".$STATUS."\t".count($LIST_D)."\n";
			$query="UPDATE pmid_entry SET pmid_status=".(($STATUS=='NULL')?"NULL":"'".$STATUS."'"). " WHERE pmid_entry_id IN (".implode(',',$LIST_D).')';
			if (!runQueryNoRes($query))	failProcess($JOB_ID."C03",'Unable to update status'."\n".$query);

		}
	}
	if (count($UPD_DATES)!=0)
	{
		echo count($UPD_DATES)." dates to update\n";
		foreach ($UPD_DATES as $DATE=>$LIST_D)
		{
			echo "\t".$DATE."\t".count($LIST_D)."\n";
			$query="UPDATE pmid_entry SET publication_date='".$DATE."' WHERE pmid_entry_id IN (".implode(',',$LIST_D).')';
			if (!runQueryNoRes($query))	failProcess($JOB_ID."C04",'Unable to update dates'."\n".$query);

		}
	}
		

	$NEW_ENTRY=false;
	/// All the new entries will have a DB_STATUS to INI
	foreach ($BLOCK as $PMID =>&$ENTRY)
	{
		if ($ENTRY['DB_STATUS']!='INI')continue;
		/// So we save the record in the file
		$NEW_ENTRY=true;
		$DBIDS['pmid_entry']++;
		fputs($FILES['pmid_entry'],$DBIDS['pmid_entry']."\t".$PMID."\t".'"'.str_replace('"','""',$ENTRY['TITLE']).'"'."\t"
		.'"'.str_replace('"','""',$ENTRY['DOI']).'"'."\t"
		.'"'.str_replace('"','""',$ENTRY['DATE']).'"'."\t"
		.$ENTRY['JOURNAL']."\t"
		.(($ENTRY['STATUS']=='NULL')?'NULL':'"'.str_replace('"','""',$ENTRY['STATUS']).'"')."\t"
		.(($ENTRY['VOLUME']=='NULL')?'NULL':'"'.str_replace('"','""',$ENTRY['VOLUME']).'"')."\t"
		.(($ENTRY['PAGE']=='NULL')?'NULL':'"'.str_replace('"','""',$ENTRY['PAGE']).'"')."\t"
		.'"'.str_replace('"','""',$ENTRY['MONTH_1910']).'"'."\t"
		.(($ENTRY['ISSUE']=='NULL')?'NULL':'"'.str_replace('"','""',$ENTRY['ISSUE']).'"')."\n");

	}

	/// And then insert all new records
	foreach ($FILES as $F)fclose($F);
	if ($NEW_ENTRY)
	{
		echo "PMID ENTRY:".$DBIDS['pmid_entry']."\n";
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_entry(pmid_entry_id,pmid,title,doi,publication_date,pmid_journal_id,pmid_status,volume,pages,month_1910,issue) FROM \''."pmid_entry.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."C05",'Unable to insert pmid_entry'); 
		
	}

}

?>
