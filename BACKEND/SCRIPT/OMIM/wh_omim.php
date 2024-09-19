<?php
error_reporting(E_ALL);
ini_set('memory_limit','1000M');


/**
 SCRIPT NAME: wh_omim
 PURPOSE:     Download & Process OMIM files, push to database, push to production
 
*/

/// Job name - Do not change
$JOB_NAME='wh_omim';


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

addLog("Check variables");

	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_omim_rel')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."004",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	if (!isset($GLB_VAR['OMIM_API_KEY']))												failProcess($JOB_ID."005",'OMIM_API_KEY not found in CONFIG_GLOBAL');
	if ($GLB_VAR['OMIM_API_KEY']=='N/A')												failProcess($JOB_ID."006",'OMIM_API_KEY not set');

	/// Check for variables FTP_OMIM and FTP_OMIM_STATIC in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_OMIM']))											failProcess($JOB_ID."007",'FTP_OMIM path no set');
	if (!isset($GLB_VAR['LINK']['FTP_OMIM_STATIC']))									failProcess($JOB_ID."008",'FTP_OMIM_STATIC path no set');


addLog("Working directory:".$W_DIR);


	  if (!is_dir('INSERT') && !mkdir('INSERT')) 										failProcess($JOB_ID."009",'Unable to create INSERT directory');
	

addLog("Download OMIM files");
	$FILES=array(
		'mimTitles.txt',
		'genemap2.txt',
		'morbidmap.txt',
		'phenotypicSeries.txt',
		'allelicVariants.txt',
		'omim.xml.gz');
	foreach ($FILES as $F)
	{
		if (file_exists($F)) continue;
		$FNAME=$GLB_VAR['LINK']['FTP_OMIM'].'/'.$GLB_VAR['OMIM_API_KEY'].'/'.$F;
		echo $FNAME."\n";
		if (!dl_file($FNAME,3))														failProcess($JOB_ID."010",'Unable to download file '.$F);
		if ($F=='omim.xml.gz' && !ungzip($F))										failProcess($JOB_ID."011",'Unable to unzip file '.$F);

	}
	

addLog("Prepare files and get Database identifiers");
	
	// This will contain all the records from the database that we didn't find in the same, so we can delete them
	$TO_DEL=array('disease_info'=>array(),'gn_info'=>array(),'variant_info'=>array());

	// Max primary key values for each table
	$DBIDS=array('disease_info'=>-1,'gn_info'=>-1,'variant_info'=>-1);

	// This will contain the order of the columns for the COPY command
	$COL_ORDER=array(
		'disease_info'=>'(disease_info_id , disease_entry_id , info_type , source_id , info_text , info_status)',
		'gn_info'=>'(gn_info_id , gn_entry_id , source_id, source_entry,info_type ,  info_text)',
		'variant_info'=>'(variant_info_id , variant_entry_id , source_id, source_entry,info_type ,  info_text,prot_variant)');


	/// So first, we are going to get the max Primary key values for each of those tables for faster insert.
	/// FILE_STATUS will tell us for each file if we need to trigger the data insertion or  not
	$FILE_STATUS=array();
	/// FILES will be the file handlers for each of the files we are going to insert into
	$FILES=array();
	foreach ($DBIDS as $TBL=>&$POS)
	{
	$query='SELECT MAX('.$TBL.'_id) CO FROM '.$TBL;
	$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."012",'Unable to run query '.$query);
	$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	$FILE_STATUS[$TBL]=0;
	$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');if (!$FILES[$TBL])				failProcess($JOB_ID."013",'Unable to open file '.$TBL.'.csv');
	}

	/// We are going to get the source_id for OMIM
	/// This will also insert that source if it doesn't exist
	$SOURCE_ID=getSource("OMIM");

addLog("Get OMIM to Gene mapping");
	$MAP_GENES=processGeneMap();


addLog("Get OMIM to Allele mapping");
	$MAP_VARIANTS=processAllelicVariants();


addLog("Get OMIM to Disease mapping");
	// For that, we will use the mapping already provided by MONDO
	$res=runQuery("SELECT d.disease_entry_id, disease_extdb ,disease_tag
	FROM disease_extdb d, disease_entry de
	where DE.disease_entry_id = d.disease_entry_id
	AND d.source_id=".$SOURCE_ID);
	if ($res===false)															failProcess($JOB_ID."014",'Unable to fetch from database');

	
	$OMIM_TO_DISEASE=array();
	foreach ($res as $line)
	{
		$OMIM_TO_DISEASE[$line['disease_extdb']][$line['disease_tag']]=$line['disease_entry_id'];
	}
	
	
	$fp=fopen('omim.xml','r');	if (!$fp)										 failProcess($JOB_ID."015",'Unable to open omim.xml');
	$N=0;$N_ALL=0;
	//fseek($fp,11422642);
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;

		// This is an xml file. We read until we see an entry starting with <entry> tag
		if ($line!='<entry>')continue;

		/// We read the whole entry
		$RECORD=array($line);
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			if ($line=='')continue;
			$RECORD[]=$line;
			if ($line=='</entry>')break;
			
			
		}	
		// To convert it into a php array, we need a little trick
		$xml = simplexml_load_string(implode("\n",$RECORD), "SimpleXMLElement", LIBXML_NOCDATA);		
		$json = json_encode($xml);
		$ENTRY = json_decode($json,TRUE);
		if ($ENTRY==null)continue;
		
		$OMIM_ID=$ENTRY['mimNumber'];
		
		/// Now we check if that record is a disease or a gene record
		if (isset($OMIM_TO_DISEASE[$OMIM_ID]))
		{
			++$N;++$N_ALL;
			// "DS\t".ftell($fp)."\t".$OMIM_ID."\t".$N."\t".$N_ALL."\t".implode("|",$OMIM_TO_DISEASE[$OMIM_ID])."\n";
			processDiseaseInfo($ENTRY);
		}
		if (isset($MAP_GENES[$OMIM_ID]))
		{
			++$N;++$N_ALL;
			//echo "GN\t".ftell($fp)."\t".$OMIM_ID."\t".$N."\t".$N_ALL."\t".implode("|",$MAP_GENES[$OMIM_ID])."\n";
			processGeneInfo($ENTRY);
		}
		if (isset($MAP_VARIANTS[$OMIM_ID]))
		{
			++$N;++$N_ALL;
			//echo "VARIANT\t".ftell($fp)."\t".$OMIM_ID."\t".$N."\t".$N_ALL."\n";
			processVariantInfo($ENTRY);
		}
		//echo "\n";
	
		/// And we push every 100 records
		if ($N<100)continue;
		pushToDB(false);
		$N=0;
	}
	/// Then we push the last batch
	pushToDB(true);
	fclose($fp);




	successProcess();









function processGeneMap()
{
	$fp=fopen('genemap2.txt','r');	if (!$fp)									 	failProcess($JOB_ID."A01",'Unable to open genemap2.txt');
	$HEADER=array();

	
	// This array will contain the list of all the genes that are associated with a MIM number
	// It will only be used to get the list of genes that are already in the database
	$GENES=array();

	/// This array will contain the mapping between MIM number and Gene IDs
	/// This will be updated with the list of genes that are already in the database and their identifiers
	$MAP_GENES=array();

	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;
		/// We read line until we find the header
		if ($HEADER==array() && substr($line,0,12)=='# Chromosome')
		{
			$HEADER=explode("\t",substr($line,2));
			continue;
		}
		if (substr($line,0,1)=='#'||$HEADER==array())continue;

		//We process the line so we can have the appropriate header as key
		$tab=explode("\t",$line);
		$ENTRY=array_combine($HEADER,$tab);
		
		
		/// We only keep the entries that have a MIM number and a Gene ID
		if ($ENTRY['Entrez Gene ID']=='')continue;


		$MAP_GENES[$ENTRY['MIM Number']][$ENTRY['Entrez Gene ID']]=-1;
		
		$GENES[$ENTRY['Entrez Gene ID']][]=$ENTRY['MIM Number'];
	}
	fclose($fp);
	

addLog("Get Corresponding Gene database identifiers");
	$res=runQuery("SELECT gn_entry_Id , gene_id 
					FROM gn_entry 
					WHERE gene_id IN (".implode(',',array_keys($GENES)).')');
	if ($res===false)															failProcess($JOB_ID."A02",'Unable to fetch from database');
	foreach ($res as $line)
	{
		foreach ($GENES[$line['gene_id']] as $MIM)
		{
			$MAP_GENES[$MIM][$line['gene_id']]=$line['gn_entry_id'];
		}
	}
	return $MAP_GENES;
	
}










function processAllelicVariants()
{
	$fp=fopen('allelicVariants.txt','r');if (!$fp) failProcess($JOB_ID."B01",'Unable to open allelicVariants.txt');
	
	$HEADER=array();

	/// This array will contain the list of all the variants that are associated with a MIM number
	/// It will only be used to get the list of variants that are already in the database
	$VARIANTS=array();

	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;
		/// We read line until we find the header
		if ($HEADER==array() && substr($line,0,12)=='# MIM Number')
		{
			$HEADER=explode("\t",substr($line,2));
			continue;
		}
		if (substr($line,0,1)=='#'||$HEADER==array())continue;

		//We process the line so we can have the appropriate header as key
		$tab=explode("\t",$line);
		$ENTRY=array_combine($HEADER,$tab);
		
		/// We only keep the entries that have a MIM number and a Gene ID
		if ($ENTRY['dbSNP']=='')continue;
		
		/// Getting the rsid from the dbSNP field
		preg_match_all('/rs([0-9]{1,10})/',$ENTRY['dbSNP'],$matches);
		
		foreach ($matches[1] as $rsid)
		{
			$VARIANTS[$rsid][]=$ENTRY['MIM Number . AV Number'];
		}
	}

	fclose($fp);
	$MAP_VARIANTS=array();
	

	// We get the list of all the genes that are already in the database
	addLog("Get Corresponding Variants database identifiers");
	$CHUNKS=array_chunk(array_keys($VARIANTS),2000);
	foreach ($CHUNKS as $N=>$CHUNK)
	{
		echo "CHUNK $N/".count($CHUNKS)."\n";
		$res=runQuery("SELECT variant_entry_id , rsid 
						FROM variant_entry 
						WHERE rsid IN (".implode(',',$CHUNK).')');
		if ($res===false)															failProcess($JOB_ID."B02",'Unable to fetch from database');
		
		foreach ($res as $line)
		{
			foreach ($VARIANTS[$line['rsid']] as $MIM)
			{
				$tab=explode(".",$MIM);
				$MAP_VARIANTS[$tab[0]][(int)$tab[1]][$line['rsid']]=$line['variant_entry_id'];
			}
		}
	}

	return $MAP_VARIANTS;
	
}




function processDiseaseInfo(&$ENTRY)
{
	global $TO_DEL;
	global $DBIDS;
	global $FILES;
	global $FILE_STATUS;
	global $SOURCE_ID;
	global $OMIM_TO_DISEASE;
	if (!isset($ENTRY['textSectionList']['textSection']))return;
	$OMIM_ID=$ENTRY['mimNumber'];


	/// Finding all OMIM disease_info records that are associated with the disease 
	$res=runQuery("SELECT * 
		FROM disease_info 
		where disease_entry_id in (".implode(',',$OMIM_TO_DISEASE[$OMIM_ID]).")  AND info_type LIKE '".$OMIM_ID."%'
		AND source_id = ".$SOURCE_ID);
	if ($res===false)														failProcess($JOB_ID."C01",'Unable to fetch from database');



	$FROM_DB=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$FROM_DB[]=$line;
	}
	
	/// Because this array is converted from XML
	/// If there's only one entry, it doesn't create an array
	/// So we assume it's an array
	$LIST=$ENTRY['textSectionList']['textSection'];
	/// But if it has textSectionName as key, it's not an array
	if (isset($LIST['textSectionName']))$LIST=array($LIST);
	 
	 //print_R($ENTRY['referenceList']);
	foreach ($LIST as &$TEXT)
	{
		$TEXT['textSectionTitle']=$OMIM_ID.' - '.$TEXT['textSectionTitle'];
		
		/// Convert the text
		convertToBiorelsText($TEXT['textSectionContent'],$ENTRY['referenceList']);
		if (isset($ENTRY['referenceList']))
		{
			convertToBiorelsText($TEXT['textSectionContent'],$ENTRY['referenceList']);
		}

		$FOUND=false;
		/// It is found if it's not only the same type but also the same text
		/// Otherwise if it's the same type, we don't update, we delete and insert
		foreach ($FROM_DB as &$DB_REC)
		{
			
			if ($DB_REC['info_type']==$TEXT['textSectionTitle'] 
			&& $DB_REC['info_text']==$TEXT['textSectionContent'])
			{
				$DB_REC['DB_STATUS']='VALID';
				$FOUND=true;
				continue;
			}
			if ($FOUND)break;
		}
		if ($FOUND)continue;
		
		
		foreach ($OMIM_TO_DISEASE[$OMIM_ID] as &$DISEASE_ID)
		{
			if ($DISEASE_ID==-1)continue;
			/// Change file status to say there's new record to push
			$FILE_STATUS['disease_info']=true;
			
			/// Increment the primary key
			$DBIDS['disease_info']++;

			/// Push to file
			// disease_info_id | disease_entry_id | info_type | source_id | info_text | info_status
			fputs($FILES['disease_info'],$DBIDS['disease_info']."\t".
			$DISEASE_ID."\t".
			$TEXT['textSectionTitle']."\t".
			$SOURCE_ID."\t\"".
			str_replace('"','""',$TEXT['textSectionContent']).
			"\"\tT\n");
			//
		}

		
	}

	/// We are going to delete the records that are not valid anymore
	foreach ($FROM_DB as &$DB_REC)
	{
		if ($DB_REC['DB_STATUS']=='VALID')continue;
		$TO_DEL['disease_info'][]=$DB_REC['disease_info_id'];
	}

}



function processGeneInfo(&$ENTRY)
{
	global $TO_DEL;
	global $DBIDS;
	global $FILES;
	global $FILE_STATUS;
	global $SOURCE_ID;
	global $MAP_GENES;
	if (!isset($ENTRY['textSectionList']['textSection']))return;
	$OMIM_ID=$ENTRY['mimNumber'];


	$query = "SELECT * 
	FROM gn_info 
	where gn_entry_id in (".implode(',',$MAP_GENES[$OMIM_ID]).") 
	AND source_entry='".$OMIM_ID."' 
	AND  source_id = ".$SOURCE_ID;
	
	$res=runQuery($query);

	if ($res===false)															failProcess($JOB_ID."E01",'Unable to fetch from database');
	$FROM_DB=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$FROM_DB[]=$line;
	}
	
	/// Because this array is converted from XML
	/// If there's only one entry, it doesn't create an array
	/// So we assume it's an array
	$LIST=$ENTRY['textSectionList']['textSection'];
	/// But if it has textSectionName as key, it's not an array
	if (isset($LIST['textSectionName']))$LIST=array($LIST);
	 
	 //print_R($ENTRY['referenceList']);
	foreach ($LIST as &$TEXT)
	{
		$TEXT['textSectionTitle']=$OMIM_ID.' - '.$TEXT['textSectionTitle'];
		
		if (!isset($ENTRY['referenceList']))$ENTRY['referenceList']=array();
		
		/// Convert the text by adding tags Biorels can recnognize
		convertToBiorelsText($TEXT['textSectionContent'],$ENTRY['referenceList']);
		

		$FOUND=false;
		foreach ($FROM_DB as &$DB_REC)
		{
			
			if ($DB_REC['info_type']==$TEXT['textSectionTitle'] 
			&& $DB_REC['info_text']==$TEXT['textSectionContent'])
			{
				$DB_REC['DB_STATUS']='VALID';
				$FOUND=true;
				continue;
			}
			if ($FOUND)break;
		}// disease_info_id | disease_entry_id | info_type | source_id | info_text | info_status
		if ($FOUND)continue;
		
		//echo "|".$TEXT['textSectionTitle']."|\n";
		foreach ($MAP_GENES[$OMIM_ID] as &$GN_ENTRY_ID)
		{
			if ($GN_ENTRY_ID==-1)continue;
			//gn_info_id , gn_entry_id , source_id, source_entry,info_type ,  info_text

			/// Change file status to say there's new record to push
			$FILE_STATUS['gn_info']=true;

			/// Increment the primary key
			$DBIDS['gn_info']++;


			fputs($FILES['gn_info'],$DBIDS['gn_info']."\t".
				$GN_ENTRY_ID."\t".
				$SOURCE_ID."\t".$OMIM_ID."\t".
				$TEXT['textSectionTitle']."\t\"".
				str_replace('"','""',$TEXT['textSectionContent']).
				"\"\n");
			//
		}

		
	}
	foreach ($FROM_DB as &$DB_REC)
	{
		if ($DB_REC['DB_STATUS']=='VALID')continue;
		$TO_DEL['gn_info'][]=$DB_REC['gn_info_id'];
	}

}


function processVariantInfo(&$ENTRY)
{
	global $TO_DEL;
	global $DBIDS;
	global $FILES;
	global $FILE_STATUS;
	global $SOURCE_ID;
	global $MAP_VARIANTS;

	if (!isset($ENTRY['referenceList']))$ENTRY['referenceList']=array();
		
	$OMIM_ID=$ENTRY['mimNumber'];
	$VARIANTS=&$MAP_VARIANTS[$OMIM_ID];
	$LIST_ID=array();
	foreach ($VARIANTS as $K=>$LIST)
	foreach ($LIST as $VARIANT_ENTRY_ID)	$LIST_ID[]=$VARIANT_ENTRY_ID;
	if ($LIST_ID==array())return;
	
	$query="SELECT * 
	FROM variant_info 
	where source_entry LIKE '".$OMIM_ID."%' 
	AND  source_id = ".$SOURCE_ID;
	
	$res=runQuery($query);
	if ($res===false)														failProcess($JOB_ID."F01",'Unable to fetch from database');
	

	$FROM_DB=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$FROM_DB[$line['variant_entry_id']][$line['source_entry']][]=$line;
	}
	
	/// Because this array is converted from XML
	/// If there's only one entry, it doesn't create an array
	/// So we assume it's an array
	$LIST=$ENTRY['allelicVariantList']['allelicVariant'];
	/// But if it has number as key, it's not an array
	if (isset($ENTRY['allelicVariantList']['allelicVariant']['number']))
	$LIST=array($ENTRY['allelicVariantList']['allelicVariant']);
	
	foreach ($LIST as &$ALL_REC)
	{
		
		$TEXT=&$ALL_REC['text'];
		
		/// Convert the text by adding tags Biorels can recognize
		convertToBiorelsText($TEXT,$ENTRY['referenceList']);
		
		$ID=$OMIM_ID.'.'.$ALL_REC['number'];
		
		if (!isset($VARIANTS[$ALL_REC['number']]))
		{
			
			if (!isset($ALL_REC['dbSnps']))continue;
			/// We are going to get the list of rsids using a regular expression
			preg_match_all('/rs([0-9]{1,10})/',$ALL_REC['dbSnps'],$matches);


			foreach ($matches[1] as $rsid)
			{
				$res=runQuery("SELECT variant_entry_id 
					FROM variant_entry 
					where rsid = '".substr($rsid,2)."'");
				if ($res===false)														failProcess($JOB_ID."F02",'Unable to fetch from database');
				if (count($res)!=1)continue;
				{
					$VARIANTS[(int)$ALL_REC['number']][substr($rsid,2)]=$res[0]['variant_entry_id'];
				}
			}
			

			/// Loop through the list of variants
			foreach ($VARIANTS[$ALL_REC['number']] as $RSID=>&$VARIANT_ENTRY_ID)
			{
				if ($VARIANT_ENTRY_ID==-1)continue;
				$FOUND=false;
				/// Then check if the record is already in the database
				if (isset($FROM_DB[$VARIANT_ENTRY_ID][$ID]))
				foreach ($FROM_DB[$VARIANT_ENTRY_ID][$ID] as &$DB_REC)
				{
					if ($DB_REC['info_type']==$ALL_REC['name'] 
					&& $DB_REC['prot_variant']==$ALL_REC['mutations']
					&& $DB_REC['info_text']==$TEXT)
					{
						$DB_REC['DB_STATUS']='VALID';
						$FOUND=true;
					
						break;
					}
					if ($FOUND)break;
				}// disease_info_id | disease_entry_id | info_type | source_id | info_text | info_status
				if ($FOUND)continue;

				/// Change file status to say there's new record to push
				$FILE_STATUS['variant_info']=true;

				/// Increment the primary key
				$DBIDS['variant_info']++;

				/// Push to file
				fputs($FILES['variant_info'],$DBIDS['variant_info']."\t".
					$VARIANT_ENTRY_ID."\t".
					$SOURCE_ID."\t".$ID."\t".
					$ALL_REC['name'].' '.$ALL_REC['mutations']."\t\"".
					str_replace('"','""',$TEXT).
					"\"\t\"".str_replace('"','""',$ALL_REC['mutations'])."\"\n");

			}
		}
		//print_R($FROM_DB);

		/// We are going to delete the records that are not valid anymore
		foreach ($FROM_DB as $VARIANT_ENTRY_ID=>$DB_VAR)
		foreach ($DB_VAR as &$DB_LIST)
		foreach ($DB_LIST as &$DB_REC)
		{
			if ($DB_REC['DB_STATUS']=='VALID')continue;
			$TO_DEL['variant_info'][]=$DB_REC['variant_info_id'];
		}
		//exit;
	}

	// print_R($FROM_DB);
	// print_R($LIST);
}


function convertToBiorelsText(&$TEXT,&$REF_LIST)
{
	/// This function converts the text so that it can be recognized by Biorels

	global $MAP_GENES;
	global $MAP_VARIANTS;
	global $OMIM_TO_DISEASE;
	if ($TEXT==null) return;


	$REFS=array();
	if (isset($REF_LIST['reference']))
	{
	$REFS=$REF_LIST['reference'];
	if (isset($REFS['title']))$REFS=array($REFS);
	}

	/// MAtch Enzyme commission numbers
	$matches=array();
	preg_match_all('/{EC ([0-9\.]{1,10})}/',$TEXT,$matches);
	foreach ($matches[0] as $k=>$v )
	{
		$TEXT=str_replace($v,'[[[EC||'.$matches[1][$k].'||'.$matches[1][$k].']]]',$TEXT);
	}

	$matches=array();
	/// Match GenBank records
	preg_match_all('/{GENBANK ([A-Za-z0-9\.]{1,30})}/',$TEXT,$matches);
	foreach ($matches[0] as $k=>$v )
	{
		$TEXT=str_replace($v,'[[[GENBANK||'.$matches[1][$k].'||'.$matches[1][$k].']]]',$TEXT);
	}
	

	/// Match pubmed or books
	$matches=array();
	preg_match_all('/{([0-9,]{1,30})\s{0,4}:([A-Za-z \'\-\(\).0-9,]{1,100})}/', $TEXT, $matches);
	//print_r($matches);
	foreach ($matches[0] as $k=>$v )
	{
		$tab=explode(",",$matches[1][$k]);
		$CHANGE='';
		foreach ($tab as $ref_id)
		foreach ($REFS as $REF)
		{
			//print_R($REF);
			//print_R($matches[1]);
			//echo $REF['referenceNumber'].'|'.$matches[1][$k] ."\n";
			if ($REF['referenceNumber']!=$ref_id)continue;
			
			if (isset($REF['pubmedID']))
			$CHANGE.='[[PUBMED||'.$REF['pubmedID'].'||'.$matches[2][$k].']]';
			else 
			$CHANGE.='[[BOOK||'.$REF['authors'].'||'.$REF['authors'].'||'.$REF['title'].'||'.$REF['source'].']]';
		}
		$TEXT=str_replace($v,'['.$CHANGE.']',$TEXT);
		
	}
	
	$matches=array();
	preg_match_all('/\{([0-9]{1,10})(\.([0-9]{1,10})){1}\}/', $TEXT, $matches);
	
	foreach ($matches[0] as $k=>$v )
	{
		$OMIM_ID=$matches[1][$k];
		$VARIANT_ID=(int)$matches[3][$k];
		$CHANGE='';
		if (isset($MAP_VARIANTS[$OMIM_ID][$VARIANT_ID]))
		foreach ($MAP_VARIANTS[$OMIM_ID][$VARIANT_ID] as $RSID=>$VARIANT_ENTRY_ID)
		{
			$CHANGE.='[[VARIANT||'.$RSID.'||rs'.$RSID.']]';
			
		}
		if ($CHANGE!='')$TEXT=str_replace($v,'['.$CHANGE.']',$TEXT);
		else $TEXT=str_replace($v,'',$TEXT);
	}
	
	$matches=array();
	/// Match rsid
	preg_match_all('/\{dbSNP rs([0-9]{1,10})\}/', $TEXT, $matches);
	
	foreach ($matches[0] as $k=>$v )
	{
		
		$TEXT=str_replace($v,'[[[VARIANT||'.$matches[1][$k].'||rs'.$matches[1][$k].']]]',$TEXT);
		
	}


	$matches=array();
	/// Match NCBI Gene or disease
	preg_match_all('/\{([0-9]{1,10})\}/', $TEXT, $matches);
	
	foreach ($matches[0] as $k=>$v )
	{
		$OMIM_ID=$matches[1][$k];
		$CHANGE='';
		if (isset($MAP_GENES[$OMIM_ID]))
		foreach ($MAP_GENES[$OMIM_ID] as $GENE_ID=>$GN_ENTRY_ID)
		{
			$CHANGE.='[[NCBI_GENE||'.$GENE_ID.'||'.$GENE_ID.']]';
			
		}
		else if(isset($OMIM_TO_DISEASE[$OMIM_ID]))
		foreach ($OMIM_TO_DISEASE[$OMIM_ID] as $DS_TAG=>$DS_ENTRY_ID)
		{
			$CHANGE.='[[DISEASE||'.$DS_TAG.'||'.$DS_TAG.']]';
		}
		if ($CHANGE!='')$TEXT=str_replace($v,'['.$CHANGE.']',$TEXT);
		else $TEXT=str_replace($v,'',$TEXT);
		
	}
	
}


function pushToDB($LAST_CALL=false)
{
	global $COL_ORDER;
	global $FILES;
	global $FILE_STATUS;
	global $GLB_VAR;
	global $DBIDS;
	global $JOB_ID;
	global $DB_INFO;
	global $TO_DEL;

	/// We are going to delete all the records that have been marked for deletion
	foreach ($TO_DEL as $TBL=>&$LIST)
	{
		if ($LIST==array())continue;
		addLog("Deleting ".$TBL." ".count($LIST).' records');
		
		$res=runQuery("DELETE FROM ".$TBL." WHERE ".$TBL."_id IN (".implode(",",$LIST).")");
		if ($res===false)															failProcess($JOB_ID."G01",'Unable to delete from database');
		
		// We don't forget to reset the list of records to delete
		$TO_DEL[$TBL]=array();
	}

	/// We are going to insert all the records that have been marked for insertion
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
		// If no records have been written to the file we don't need to insert it
		if (!$FILE_STATUS[$NAME])
		{
			//echo "SKIPPING ".$NAME."\t";
			continue;
		}
		
		
		// We close the file handler
		fclose($FILES[$NAME]);

		// Preparing the COPY command
		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \'INSERT/'.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		
		echo $NAME."\t".$FILE_STATUS[$NAME]."\t";
		$res=array();
	
		// We run the command
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		if ($return_code !=0 )	 failProcess($JOB_ID."G02",'Unable to insert data into '.$NAME.' '.print_r($res,true));
	}
	// We reset the file status
	$FILES=array();

	//If it's the last call we don't need to reopen the files
	if ($LAST_CALL)return;
	
	// We reopen the files
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$FILE_STATUS[$TBL]=0;
		$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');if (!$FILES[$TBL])				failProcess($JOB_ID."G03",'Unable to open file '.$TBL.'.csv');
	}
	

}

?>