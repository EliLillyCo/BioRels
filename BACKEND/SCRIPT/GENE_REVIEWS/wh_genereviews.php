<?php



/**
 SCRIPT NAME: wh_genereviews
 PURPOSE:     Download, process and push Gene Reviews to production
 
*/

/// Job name - Do not change
$JOB_NAME='wh_genereviews';


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




/// This is the disclaimer required by GeneReviews to share their content
$DISCLAIMER='
<h4>This is a document-based news, please select the document in the supporting files below </h4>
<div class="post-content"><div><div class="half_rhythm"><a href="/books/about/copyright/">Copyright</a> © 1993-2022, University of Washington, Seattle. GeneReviews is
a registered trademark of the University of Washington, Seattle. All rights
reserved.<p class="small">GeneReviews® chapters are owned by the University of Washington. Permission is
hereby granted to reproduce, distribute, and translate copies of content materials for
noncommercial research purposes only, provided that (i) credit for source (<a href="http://www.genereviews.org/" ref="pagearea=meta&amp;targetsite=external&amp;targetcat=link&amp;targettype=uri">http://www.genereviews.org/</a>) and copyright (© 1993-2022 University of
Washington) are included with each copy; (ii) a link to the original material is provided
whenever the material is published elsewhere on the Web; and (iii) reproducers,
distributors, and/or translators comply with the <a href="https://www.ncbi.nlm.nih.gov/books/n/gene/GRcopyright_permiss/" ref="pagearea=meta&amp;targetsite=external&amp;targetcat=link&amp;targettype=uri">GeneReviews® Copyright Notice and Usage
Disclaimer</a>';

 

addLog("Setting up");
	/// Get Parent info
	$DL_INFO=$GLB_TREE[getJobIDByName('ck_genereviews_rel')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_INFO['TIME']['DEV_DIR'];	
	
	if (!is_dir($W_DIR)) 																	failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 																	failProcess($JOB_ID."002",'NO '.$W_DIR.' found ');
	
	/// Set the process control directory so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$DL_INFO['TIME']['DEV_DIR'];


	/// Getting the source id corresponding to GeneReviews:
	$SOURCE_ID=getSource('Gene Reviews');
	
	
	$EXT_DATA=array('PUBLI'=>array(),'GENE'=>array(),'DISEASE'=>array());
	$DATA=array();
	preloadDataFromFiles($EXT_DATA,$DATA);

	/// Process to public schema
	processGeneReviews($GLB_VAR['PUBLIC_SCHEMA']);
	
	/// Process to private schema - if enabled
	if ($GLB_VAR['PRIVATE_ENABLED']=='T')processGeneReviews($GLB_VAR['SCHEMA_PRIVATE']);

addLog("Push to prod");
	pushToProd();

	successProcess();












function loadFromDB($SCHEMA)
{
	/// Getting all the current data for GeneReviews from the database
	$res=runQuery("SELECT news_id , news_title ,news_release_date , news_added_date , user_id  
					FROM ".$SCHEMA.".news n, source s 
					where s.source_Id = n.source_id 
					AND source_name='Gene Reviews'");
	if ($res===false) 																	failProcess($JOB_ID."A01",'Unable to get news');

	$CURR_DATA=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$CURR_DATA[$line['news_id']]=$line;
	}

	if ($CURR_DATA==array())return $CURR_DATA;

	/// Getting the gene, disease and file information for each news
	$res=runQuery("SELECT * FROM ".$SCHEMA.".news_gn_map 
					WHERE news_id IN (".implode(',',array_keys($CURR_DATA)).')');
	if ($res===false)																failProcess($JOB_ID."A02",'Unable to get news gene map');
	foreach ($res as $line)
	{
		
		$CURR_DATA[$line['news_id']]['GENE'][$line['gn_entry_id']]=
			array('DB_STATUS'=>'FROM_DB',
					'DB_ID'=>$line['news_gn_map_id']);
	}

	$res=runQuery("SELECT * FROM ".$SCHEMA.".news_disease_map 
					where news_id IN (".implode(',',array_keys($CURR_DATA)).')');
	if ($res===false)																failProcess($JOB_ID."A03",'Unable to get news disease map');
	foreach ($res as $line)
	{
		
		$CURR_DATA[$line['news_id']]['DISEASE'][$line['disease_entry_id']]=
			array('DB_STATUS'=>'FROM_DB',
					'DB_ID'=>$line['news_disease_map_id']);
	}


	/// Getting the document information
	$res=runQuery("SELECT news_document_id,document_name,document_description,
					document_hash,creation_date,news_id,document_version,mime_type
					FROM ".$SCHEMA.".news_document 
					WHERE news_id IN (".implode(',',array_keys($CURR_DATA)).')');
	if ($res===false)															failProcess($JOB_ID."A04",'Unable to get news document');
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$CURR_DATA[$line['news_id']]['FILE'][$line['document_name']][$line['document_version']]=$line;
	}
	
	return $CURR_DATA;
}




function cleanData($SCHEMA,$CURR_DATA)
{
	/// We are going to loop over all the current data and remove the ones that are still FROM_DB
	/// i.e loaded from the database but not found in the files:
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
		$res=runQueryNoRes("SELECT FROM ".$SCHEMA.".news_gn_map 
							where news_gn_map_id IN (".implode(',',$DEL['GENE']).')');
		if ($res===false)															failProcess($JOB_ID."B01",'Unable to delete news gene map for schema '.$SCHEMA);
	}
	if (count($DEL['DISEASE']))
	{
		$res=runQueryNoRes("SELECT FROM ".$SCHEMA.".news_disease_map 
							where news_disease_map_id IN (".implode(',',$DEL['DISEASE']).')');
		if ($res===false)															failProcess($JOB_ID."B02",'Unable to delete news disease map for schema '.$SCHEMA);
	}
}



function preloadDataFromFiles(&$EXT_DATA,&$DATA)
{
	/// All the GeneReviews document & annotations will be stored in $DATA
	/// All the annotations provided by GeneRevies: genes, disease, OMIM, PMID will be stored in $EXT_DATA
	/// In $EXT_DATA the first level key is the type of annotation and the second level key is the value of the annotation
	/// The value of the second level key is by default -1. Then we query the database to get the actual primary key value of the annotation


	//// Reading the file names and the corresponding GeneReviews title
	$fp=fopen('GRtitle_shortname_NBKid.txt','r');
	if (!$fp) 																		failProcess($JOB_ID."C01",'Unable to open GRtitle_shortname_NBKid');
	$head=array_flip(explode("\t",stream_get_line($fp,5000,"\n")));

	while(!feof($fp))
	{
		$line=stream_get_line($fp,5000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		if (!isset($DATA[$tab[$head['NBK_id']]]))
			$DATA[$tab[$head['NBK_id']]]=array(
				'GR_shortname'=>$tab[$head['#GR_shortname']],
				'GR_Title'=>$tab[$head['GR_Title']]);

		$DATA[$tab[$head['NBK_id']]]['PMID'][]=$tab[$head['PMID']];
		
		$EXT_DATA['PUBLI'][$tab[$head['PMID']]]=-1;
	}
	fclose($fp);
	

	/// Reading the gene symbols mapped to the GeneReviews
	$fp=fopen('NBKid_shortname_genesymbol.txt','r');
	if (!$fp)												 failProcess($JOB_ID."C02",'Unable to open NBKid_shortname_genesymbol.txt');
	$head=array_flip(explode("\t",stream_get_line($fp,5000,"\n")));

	while(!feof($fp))
	{
		$line=stream_get_line($fp,5000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		
		if (!isset($DATA[$tab[$head['#NBK_id']]]))$DATA[$tab[$head['#NBK_id']]]=
			array('GR_shortname'=>$tab[$head['GR_shortname']]);
		
		$DATA[$tab[$head['#NBK_id']]]['GENE'][]=$tab[$head['genesymbol']];

		$EXT_DATA['GENE']["'".$tab[$head['genesymbol']]."'"]=-1;
	}
	fclose($fp);


	/// Mapping the OMIM to the GeneReviews
	$fp=fopen('NBKid_shortname_OMIM.txt','r');
	if (!$fp) 												failProcess($JOB_ID."C03",'Unable to open NBKid_shortname_OMIM.txt');
	$head=array_flip(explode("\t",stream_get_line($fp,5000,"\n")));

	while(!feof($fp))
	{
		$line=stream_get_line($fp,5000,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		if (!isset($DATA[$tab[$head['#NBK_id']]]))
			$DATA[$tab[$head['#NBK_id']]]=array('GR_shortname'=>$tab[$head['GR_shortname']]);


		$DATA[$tab[$head['#NBK_id']]]['OMIM'][]=$tab[$head['OMIM']];

		$EXT_DATA['OMIM']["'".$tab[$head['OMIM']]."'"]=-1;
	}
	fclose($fp);


	/// Mapping the diseases to the GeneReviews
	$fp=fopen('GRshortname_NBKid_genesymbol_dzname.txt','r');
	if (!$fp) 									failProcess($JOB_ID."C04",'Unable to open GRshortname_NBKid_genesymbol_dzname.txt');
	$head=array_flip(explode("\t",stream_get_line($fp,5000,"\n")));
	while(!feof($fp))
	{
		$line=stream_get_line($fp,5000,"\n");if ($line=='')continue;
		$tab=explode("|",$line);
		
		$DATA[$tab[1]]['DISEASE'][$tab[3]]=-1;
		$EXT_DATA['DISEASE']["'".utf8_encode(str_replace("'","''",strtolower($tab[3])))."'"]=-1;
	}
	fclose($fp);


	/// Scanning the directory to get the files
	$dir='';
	$f=scandir('.');
	foreach ($f as $file)
	{
		if ($file!='.'&&$file!='..'&&is_dir($file))$dir=$file;
	}



	$MAP=array();
	foreach ($DATA as $name=>&$INFO)
	{
			$MAP[$INFO['GR_shortname']]=$name;
	}


	/// Here we are looking at the files in the directory and we are going to store the file name, the mime type and the md5 hash
	$f=scandir($dir);
	foreach ($f as $file)
	{
		if ($file=='.'||$file=='..')continue;
		
		$pos=strrpos($file,'.');
		$name=substr($file,0,$pos);	$fullname=$name;
		$pos=strrpos($name,'-');
		if ($pos!==false)
		{
			if (!isset($MAP[$name]))
			$name=substr($name,0,$pos);
		}
		if (!isset($MAP[$name]))continue;
	//	echo $file."\t".$name."\t".mime_content_type($dir.'/'.$file)."\n";
		$DATA[$MAP[$name]]["FILES"][$file]=array('PATH'=>$dir.'/'.$file,'MIME'=>mime_content_type($dir.'/'.$file),'FULLNAME'=>$fullname,'MD5'=>md5_file($dir.'/'.$file));
	}


	$res=runQuery("SELECT distinct symbol,gn_entry_Id 
					FROM mv_gene_sp 
					where tax_id='9606' 
					and symbol IN (".implode(',',array_keys($EXT_DATA['GENE'])).')');
	if ($res===false) 															failProcess($JOB_ID."C05",'Unable to get gene data');
	foreach ($res as $line)$EXT_DATA['GENE']["'".$line['symbol']."'"]=$line['gn_entry_id'];


	/// Query diseases by OMIM ID
	$res=runQuery("SELECT disease_entry_id,disease_extdb 
				FROM disease_extdb de, source s 
				where s.source_Id = de.source_id 
				AND source_name='OMIM' 
				AND disease_extdb IN (".implode(',',array_keys($EXT_DATA['OMIM'])).')');
	if ($res===false)														failProcess($JOB_ID."C06",'Unable to get OMIM data');
	foreach ($res as $line)$EXT_DATA['OMIM']["'".$line['disease_extdb']."'"]=$line['disease_entry_id'];

	/// Query diseases by name
	$res=runQuery("SELECT disease_entry_Id, disease_name 
				FROM disease_entry 
				where LOWER(disease_name) in (".implode(',',array_keys($EXT_DATA['DISEASE'])).')');
	if ($res===false)													failProcess($JOB_ID."C07",'Unable to get disease data');
	foreach ($res as $line)
		$EXT_DATA['DISEASE']["'".str_replace("'","''",$line['disease_name'])."'"]=$line['disease_entry_id'];


	/// Query diseases by synonym
	$res=runQuery("SELECT disease_entry_Id, syn_value 
					FROM disease_syn where LOWER(syn_value) in (".implode(',',array_keys($EXT_DATA['DISEASE'])).')');
	if ($res===false)												failProcess($JOB_ID."C08",'Unable to get disease data');
	foreach ($res as $line)
		$EXT_DATA['DISEASE']["'".str_replace("'","''",$line['syn_value'])."'"]=$line['disease_entry_id'];




}


function compareRecords(&$CURR_INFO,&$INFO,&$DS_LIST,&$SCHEMA)
{

	/// We are going to compare the current data with the new data

	/// Setting the current data as valid
	$CURR_INFO['DB_STATUS']='VALID';

	/// Comparing diseases from the file:
	foreach ($DS_LIST as $DISEASE_ENTRY_ID=>$dummy)
	{
		$FOUND_D=false;

		/// Against the current data in the database
		foreach ($CURR_INFO['DISEASE'] as $CURR_DISEASE_ENTRY_ID=>&$INFO_D)
		{
			if ($CURR_DISEASE_ENTRY_ID!=$DISEASE_ENTRY_ID)continue;
			$FOUND_D=true;
			$INFO_D['DB_STATUS']='VALID';
		}
		if ($FOUND_D)continue;

		/// If the disease is not found in the current data, we are going to insert it
		$res=runQuery("SELECT nextval('".$SCHEMA.".news_disease_map_sq') n");
		if ($res===false)														failProcess($JOB_ID."D01",'Unable to get news disease map id');
		$NEWS_DISEASE_ID=$res[0]['n'];
		$query='INSERT INTO  '.$SCHEMA.'.news_disease_map
			(news_disease_map_id , news_id,disease_entry_id) 
			VALUES ('.$NEWS_DISEASE_ID.','.$DBID.','.$DISEASE_ENTRY_ID.')';
		if (!runQueryNoRes($query)) 											failProcess($JOB_ID."D02",'Unable to insert news disease '.$query);
	}




	/// Comparing genes from the file:
	if (isset($INFO['GENE']))
	foreach ($INFO['GENE'] as $GENE_SYMBOL)
	{

		/// No need if the $GENE_SYMBOL is not found in the db
		if ($EXT_DATA['GENE']["'".$GENE_SYMBOL."'"]==-1)continue;
		$GN_ENTRY_ID=$EXT_DATA['GENE']["'".$GENE_SYMBOL."'"];
		if (isset($CURR_INFO['GENE'][$GN_ENTRY_ID]))
		{
			$CURR_INFO['GENE'][$GN_ENTRY_ID]['DB_STATUS']='VALID';
		}
		else 
		{
			$res=runQuery("SELECT nextval('".$SCHEMA.".news_gn_map_sq') n");
			$NEWS_GENE_ID=$res[0]['n'];
			$query='INSERT INTO  '.$SCHEMA.'.news_gn_map(news_gn_map_id , news_id,gn_entry_Id) 
					VALUES ('.$NEWS_GENE_ID.','.$DBID.','.$GN_ENTRY_ID.')';
			if (!runQueryNoRes($query)) failProcess($JOB_ID."D03",'Unable to insert news gene '.$query);
		}
	}


	/// Comparing files from the file:
	foreach ($INFO['FILES'] as $FILE_NAME=>&$FILE_INFO)
	{
		
		//
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
			if (!$stmt->execute()) 											failProcess($JOB_ID."D04",'Unable to insert news document');
			
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
			if (!$stmt->execute())											failProcess($JOB_ID."D05",'Unable to insert news document');
		
		}
	}
		
			
}




function processGeneReviews($SCHEMA)
{
	addLog("Processing GeneReviews for ".$SCHEMA);
	global $SOURCE_ID;
	global $DB_CONN;
	global $DISCLAIMER;
	global $JOB_ID;
	global $EXT_DATA;
	global $DATA;

	
	$CURR_DATA=loadFromDB($SCHEMA);


	/// Looking at each data from GeneReview files
	foreach ($DATA as $name=>&$INFO)
	{
		$ENTRY_NAME=$name.' - '.utf8_encode($INFO['GR_Title']);
		$FOUND=false;
		//print_r($INFO);
		

		/// Those 2 steps below are to make sure we don't duplicate diseases records:
		/// Mapping the diseases:
		$DS_LIST=array();
		if (isset($INFO['DISEASE']))
		foreach ($INFO['DISEASE'] as $DISEASE_NAME=>&$T)
		{
			$DN="'".utf8_encode(str_replace("'","''",strtolower($DISEASE_NAME)))."'";
			if ($EXT_DATA['DISEASE'][$DN]!=-1)$DS_LIST[$EXT_DATA['DISEASE'][$DN]]=true;
		}
		
		
		/// Mapping OMIM
		if (isset($INFO['OMIM']))
		foreach ($INFO['OMIM'] as $OMIM)
		{
			if ($EXT_DATA['OMIM']["'".$OMIM."'"]!=-1)$DS_LIST[$EXT_DATA['OMIM']["'".$OMIM."'"]]=true;;
		}
		//print_R($DS_LIST);
		
		
		
		foreach ($CURR_DATA as $DBID=>&$CURR_INFO)
		{
			
			if ($CURR_INFO['news_title']!=$ENTRY_NAME)continue;
			/// We found a document with the same title so we are going to compare them
			$FOUND=true;
			compareRecords($CURR_INFO,$INFO,$DS_LIST,$SCHEMA);
			break;
		}
		
		if ($FOUND)continue;
		/// Not found - we insert:
		

		/// Getting a news_id primary key:
		$res=runQuery("SELECT nextval('".$SCHEMA.".news_sq') n");
		if ($res===false)														failProcess($JOB_ID."E01",'Unable to get news id');
		$NEWS_ID=$res[0]['n'];
		
		$md5=md5(microtime_float());
		
		/// Inserting the news
		$query='INSERT INTO  '.$SCHEMA.'.news(news_id , news_title ,news_release_date , news_added_date ,source_Id, news_hash, news_content) 
				VALUES ('.$NEWS_ID.',\''.$ENTRY_NAME.'\',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'.$SOURCE_ID.',\''.$md5.'\',\''.str_replace("'","''",$DISCLAIMER).'\')';
		if (!runQueryNoRes($query)) failProcess($JOB_ID."E02",'Unable to insert news '.$query);

		/// Inserting the diseases
		foreach ($DS_LIST as $DISEASE_ENTRY_ID=>$dummy)
		{
			$res=runQuery("SELECT nextval('".$SCHEMA.".news_disease_map_sq') n");
			$NEWS_DISEASE_ID=$res[0]['n'];
			$query='INSERT INTO  '.$SCHEMA.'.news_disease_map(news_disease_map_id , news_id,disease_entry_id) 
					VALUES ('.$NEWS_DISEASE_ID.','.$NEWS_ID.','.$DISEASE_ENTRY_ID.')';
			if (!runQueryNoRes($query)) failProcess($JOB_ID."E03",'Unable to insert news disease '.$query);
		}

		/// Inserting the genes
		if (isset($INFO['GENE']))
		foreach ($INFO['GENE'] as $GENE_SYMBOL)
		{
			if ($EXT_DATA['GENE']["'".$GENE_SYMBOL."'"]==-1)continue;
			$GN_ENTRY_ID=$EXT_DATA['GENE']["'".$GENE_SYMBOL."'"];
			
			$res=runQuery("SELECT nextval('".$SCHEMA.".news_gn_map_sq') n");
			$NEWS_GENE_ID=$res[0]['n'];
			$query='INSERT INTO  '.$SCHEMA.'.news_gn_map(news_gn_map_id , news_id,gn_entry_Id) VALUES ('.$NEWS_GENE_ID.','.$NEWS_ID.','.$GN_ENTRY_ID.')';
			if (!runQueryNoRes($query)) failProcess($JOB_ID."E04",'Unable to insert news gene '.$query);
			
		}


		/// Inserting the files
		foreach ($INFO['FILES'] as $FILE_NAME=>&$FILE_INFO)
		{
				
			$query="INSERT INTO ".$SCHEMA.".news_document (news_document_id,document_name,document_content,document_description,document_hash,creation_date,news_id,document_version,mime_type) VALUES
			(nextval('".$SCHEMA.".news_document_sq'),'".utf8_encode($FILE_NAME)."',:document_content,:document_description,'".$FILE_INFO['MD5']."',CURRENT_TIMESTAMP,".$NEWS_ID.",1,'".$FILE_INFO['MIME']."') ";
			$stmt = $DB_CONN->prepare($query);
			$content=file_get_contents($FILE_INFO['PATH']);
			//echo strlen($content)."\n";
			$stmt->bindParam(':document_content', $content, PDO::PARAM_LOB);
			$stmt->bindParam(':document_description', $DISCLAIMER, PDO::PARAM_STR);
			if (!$stmt->execute())												failProcess($JOB_ID."E05",'Unable to insert news document '.$query);
			
		}
	}


	/// Then we are going to remove the data that are still marked as FROM_DB
	cleanData($SCHEMA,$CURR_DATA);
}





?>
