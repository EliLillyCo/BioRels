<?php


/**
 SCRIPT NAME: wh_livertox
 PURPOSE:     Process livertox documents
 
*/

/// Job name - Do not change
$JOB_NAME='wh_livertox';


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

 
addLog("Preparation");

	/// Get Parent info
	$DL_INFO=$GLB_TREE[getJobIDByName('ck_livertox_rel')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_INFO['TIME']['DEV_DIR'];	
	
	/// Go to the directory set up by parent
	if (!is_dir($W_DIR)) 																	failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 																	failProcess($JOB_ID."002",'NO '.$W_DIR.' found ');

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$DL_INFO['TIME']['DEV_DIR'];

	/// Disclaimer required for the documents
	$DISCLAIMER='
	<h4>This is a document-based news, please select the document in the supporting files below </h4>
	<div class="post-content"><div class="half_rhythm">This publication is in the public domain. For more information, see the <a href="https://www.ncbi.nlm.nih.gov/books/about/copyright/">NCBI Bookshelf Copyright Notice.</a><br/>
		LiverTox: Clinical and Research Information on Drug-Induced Liver Injury [Internet]. Bethesda (MD): National Institute of Diabetes and Digestive and Kidney Diseases; 2012-. Available from: https://www.ncbi.nlm.nih.gov/books/NBK547852/<br/><p style="    text-align: justify;line-height: 1.5em;">'.file_get_contents('livertox_NBK547852/license.txt').'</p></div></div>';

			
	$SOURCE_ID=getSource('Liver Tox');
	
	

	
	/// Push to public schema
	processLiverTox($GLB_VAR['PUBLIC_SCHEMA']);
	
	/// Push to private schema
	if ($GLB_VAR['PRIVATE_ENABLED']=='T')processLiverTox($GLB_VAR['SCHEMA_PRIVATE']);


pushToProd();

	successProcess();






















function processLiverTox($SCHEMA)
{
	addLog("Process entries for ".$SCHEMA);
	global $SOURCE_ID;
	global $DB_CONN;
	global $DISCLAIMER;
	global $GLB_VAR;
	global $W_DIR;



	addLog("Get existing entries for ".$SCHEMA);
	$res=runQuery("SELECT news_id , news_title ,news_release_date , news_added_date , user_id  
	FROM ".$SCHEMA.".news n, source s 
	where s.source_Id = n.source_id 
	AND source_name='Liver Tox'");
	if ($res===false)																failProcess($JOB_ID."A01",'Unable to fetch from database');
	$CURR_DATA=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$CURR_DATA[$line['news_id']]=$line;
	}

	if ($CURR_DATA!=array())
	{
		addLog("Get existing drug map for ".$SCHEMA);
		$res=runQuery("SELECT * FROM ".$SCHEMA.".news_drug_map where news_id IN (".implode(',',array_keys($CURR_DATA)).')');
		foreach ($res as $line)
		{
			$CURR_DATA[$line['news_id']]['DRUG'][$line['drug_entry_id']]=array(
				'DB_STATUS'=>'FROM_DB',
				'DB_ID'=>$line['news_drug_map_id']
			);
		}

	
		addLog("Get existing documents for ".$SCHEMA);
		$res=runQuery("SELECT news_document_id,document_name,document_description,document_hash,creation_date,news_id,document_version,mime_type
		FROM ".$SCHEMA.".news_document
		WHERE news_id IN (".implode(',',array_keys($CURR_DATA)).')');
		if ($res===false)																failProcess($JOB_ID."A02",'Unable to fetch news_document from database');
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$CURR_DATA[$line['news_id']]['FILE'][$line['document_name']][$line['document_version']]=$line;
		}
	}	



	addLog("Listing files ".$SCHEMA);
	exec('ls '.$W_DIR.'/livertox_NBK547852/*.pdf',$f,$return_code);
	if ($return_code!=0)																failProcess($JOB_ID."A03",'Unable to list files');
	foreach ($f as $file)
	{
		if ($file=='.'||$file=='..')continue;
		
		$posS=strrpos($file,'/');
		$name=substr($file,$posS+1);echo $file."\t".$name."\t";
		$pos=strrpos($name,'.');
		$fullname=$name;
		$name=substr($name,0,$pos);
			if ($name=='abbreviation'||$name=='aboutus')continue;
			echo $name."\n";
		
		
	//	echo $file."\t".$name."\t".mime_content_type($dir.'/'.$file)."\n";
		$DATA["'".strtolower(str_replace("'","''",$name))."'"]=array(
			'NAME'=>$name,
			"FILES"=>array(
				$fullname=>array(
					'PATH'=>$file,
					'MIME'=>mime_content_type($file),
					'FULLNAME'=>$fullname,
					'MD5'=>md5_file($file)
				)
			)
		);
		
	}


	addLog("Search for drug name");
	$res=runQuery("SELECT DISTINCT drug_entry_id, LOWER(drug_name) dr
		FROM ".$GLB_VAR['PUBLIC_SCHEMA'].".drug_name dn 
		where  LOWER(drug_name) IN (".implode(',',array_keys($DATA)).")");
	if ($res===false)																failProcess($JOB_ID."A04",'Unable to fetch drug_name from database');
	foreach ($res as $line)$EXT_DATA['DRUG'][$line['dr']]=$line['drug_entry_id'];

	addLog("Search for drug name:".count($res).'/'.count($DATA)."\n");
	
	


	/// Processing files from the directory
	foreach ($DATA as $name=>&$INFO)
	{
		$ENTRY_NAME='LiverTox - '.$INFO['NAME'];
		$FOUND=false;
		
		/// Check if the entry already exists
		foreach ($CURR_DATA as $DBID=>&$CURR_INFO)
		{
			/// If the entry has not the same title, we skip
			if ($CURR_INFO['news_title']!=$ENTRY_NAME)continue;

			$FOUND=true;
			
			$CURR_INFO['DB_STATUS']='VALID';
			
			/// Checking if drug mapping exists:
			if (isset($EXT_DATA['DRUG'][strtolower($INFO['NAME'])]))
			{
				$DRUG_ID=$EXT_DATA['DRUG'][strtolower($INFO['NAME'])];
				$FOUND_D=false;

				/// By comparing the drug entry id
				foreach ($CURR_INFO['DRUG'] as $CURR_DRUG_ENTRY_ID=>&$INFO_D)
				{
					if ($CURR_DRUG_ENTRY_ID!=$DRUG_ID)continue;
					$FOUND_D=true;
					$INFO_D['DB_STATUS']='VALID';
				}
				if ($FOUND_D)continue;

				/// Otherwise, we insert the drug mapping
				$res=runQuery("SELECT nextval('".$SCHEMA.".news_drug_map_sq') n");
				if ($res===false)															failProcess($JOB_ID."A05",'Unable to fetch news_drug_map_sq from database');
				$NEWS_DRUG_ID=$res[0]['n'];
				$query='INSERT INTO  '.$SCHEMA.'.news_drug_map(news_drug_map_id , news_id,drug_entry_id)
							 VALUES ('.$NEWS_DRUG_ID.','.$DBID.','.$DRUG_ID.')';
				if (!runQueryNoRes($query)) 												failProcess($JOB_ID."A06",'Unable to insert news disease '.$query);
			}
			
			/// Reviewing the files:
			foreach ($INFO['FILES'] as $FILE_NAME=>&$FILE_INFO)
			{
				
				// Comparing the file name
				if (isset($CURR_INFO['FILE'][$FILE_NAME]))	
				{
					$FOUND_DOC=false;$MAX_CURR_V=0;
					foreach ($CURR_INFO['FILE'][$FILE_NAME] as $CURR_V=>&$CURR_FILE_INFO)
					{
						$MAX_CURR_V=max($MAX_CURR_V,$CURR_V);
						if ($CURR_FILE_INFO['document_hash']==$FILE_INFO['MD5'])
						{
							$CURR_FILE_INFO['DB_STATUS']='VALID';
							$FOUND_DOC=true;
						}
					}
					if ($FOUND_DOC)continue;
					echo $FILE_NAME."\tNEW VERSION\n";
					$content=file_get_contents($FILE_INFO['PATH']);
					
					
					$stmt = $DB_CONN->prepare("INSERT INTO ".$SCHEMA.".news_document (news_document_id,document_name,document_content,document_description,document_hash,creation_date,news_id,document_version,mime_type)  VALUES
					(nextval('".$SCHEMA.".news_document_sq'),'".utf8_encode($FILE_NAME)."',:document_content,:document_description,'".$FILE_INFO['MD5']."',CURRENT_TIMESTAMP,".$DBID.",".($MAX_CURR_V+1).",'".$FILE_INFO['MIME']."') ");
					$stmt->bindParam(':document_content', $content, PDO::PARAM_LOB);
					$stmt->bindParam(':document_description', $DISCLAIMER, PDO::PARAM_STR);
					$stmt->execute();
					
				}
				else
				{
					$content=file_get_contents($FILE_INFO['PATH']);

					$query="INSERT INTO ".$SCHEMA.".news_document (news_document_id,document_name,document_content,document_description,document_hash,creation_date,news_id,document_version,mime_type) VALUES
					(nextval('".$SCHEMA.".news_document_sq'),'".utf8_encode($FILE_NAME)."',:document_content,:document_description,'".$FILE_INFO['MD5']."',CURRENT_TIMESTAMP,".$DBID.",1,'".$FILE_INFO['MIME']."') ";
				//	echo $query."\n";
					$stmt = $DB_CONN->prepare($query);
					
					//echo strlen($content)."\n";
					$stmt->bindParam(':document_content', $content, PDO::PARAM_LOB);
					$stmt->bindParam(':document_description', $DISCLAIMER, PDO::PARAM_STR);
					$stmt->execute();
				
				}
			}
		
			
		}
		
		if ($FOUND)continue;


		/// Adding a new record:

		/// Start with the news itself:
		$res=runQuery("SELECT nextval('".$SCHEMA.".news_sq') n");
		$NEWS_ID=$res[0]['n'];
		global $DISCLAIMER;
		$md5=md5(microtime_float());
		$query='INSERT INTO  '.$SCHEMA.'.news(news_id , news_title ,news_release_date , news_added_date ,source_Id,news_content,news_hash)
			VALUES ('.$NEWS_ID.',
			\''.$ENTRY_NAME.'\',
			CURRENT_TIMESTAMP,
			CURRENT_TIMESTAMP,
			'.$SOURCE_ID.',
			\''.str_replace("'","''",$DISCLAIMER).'\',
			\''.$md5.'\')';
		//echo $query."\n";
		if (!runQueryNoRes($query)) failProcess($JOB_ID."A07",'Unable to insert news '.$query);
		
		
		/// Then the drug mapping
		if (isset($EXT_DATA['DRUG'][strtolower($INFO['NAME'])]))
		{
			$DRUG_ID=$EXT_DATA['DRUG'][strtolower($INFO['NAME'])];
			
			$res=runQuery("SELECT nextval('".$SCHEMA.".news_drug_map_sq') n");
			$NEWS_DRUG_ID=$res[0]['n'];
			$query='INSERT INTO  '.$SCHEMA.'.news_drug_map(news_drug_map_id , news_id,drug_entry_id) 
					VALUES ('.$NEWS_DRUG_ID.','.$NEWS_ID.','.$DRUG_ID.')';
			if (!runQueryNoRes($query)) failProcess($JOB_ID."A08",'Unable to insert news drug '.$query);
		}
	
		/// Then the documents
		foreach ($INFO['FILES'] as $FILE_NAME=>&$FILE_INFO)
		{
			
				
				$query="INSERT INTO ".$SCHEMA.".news_document (news_document_id,document_name,document_content,
				document_description,document_hash,creation_date,news_id,document_version,mime_type) VALUES
				(nextval('".$SCHEMA.".news_document_sq'),
				'".utf8_encode($FILE_NAME)."',
				:document_content,
				:document_description,
				'".$FILE_INFO['MD5']."',
				CURRENT_TIMESTAMP,
				".$NEWS_ID.",
				1,
				'".$FILE_INFO['MIME']."') ";
				//echo $query."\n";
				$stmt = $DB_CONN->prepare($query);
				$content=file_get_contents($FILE_INFO['PATH']);
				//echo strlen($content)."\n";
				$stmt->bindParam(':document_content', $content, PDO::PARAM_LOB);
				$stmt->bindParam(':document_description', $DISCLAIMER, PDO::PARAM_STR);
				$stmt->execute();
			
		}

	
	
	}

	/// Then deleting:
	$DEL=array('GENE'=>array(),'DISEASE'=>array());
	foreach ($CURR_DATA as &$ENTRY)
	{
		if (isset($ENTRY['GENE']))
		foreach ($ENTRY['GENE'] as &$GN_INFO)
		{
			if ($GN_INFO['DB_STATUS']=='FROM_DB')$DEL['GENE'][]=$GN_INFO['DB_ID'];
		}
		if (isset($ENTRY['DISEASE']))
		foreach ($ENTRY['DISEASE'] as &$DS_INFO)
		{
			if ($DS_INFO['DB_STATUS']=='FROM_DB')$DEL['DISEASE'][]=$DS_INFO['DB_ID'];
		}
	}
	if (count($DEL['GENE']))
	{
		$res=runQueryNoRes("SELECT FROM ".$SCHEMA.".news_gn_map where news_gn_map_id IN (".implode(',',$DEL['GENE']).')');
		if ($res===false)															failProcess($JOB_ID."A09",'Unable to delete news_gn_map');
	}
	if (count($DEL['DISEASE']))
	{
		$res=runQueryNoRes("SELECT FROM ".$SCHEMA.".news_disease_map where news_disease_map_id IN (".implode(',',$DEL['DISEASE']).')');
		if ($res===false)															failProcess($JOB_ID."A10",'Unable to delete news_disease_map');
	}
}


?>
