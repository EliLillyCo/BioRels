<?php


function checkRegex($VALUE, $TAG)
{
	global $GLB_VAR;

	$REGEX = $GLB_VAR['REGEX'][$TAG];

	foreach ($REGEX as $RGX) {

		$matches = array();
		if (preg_match('/' . $RGX . '/', $VALUE, $matches) == 0) continue;
		return $matches;
	}
	return false;
}
function bc_submitNews($NEWS)
{
	try {
		global $DB_CONN;
		$CREATED_NEWS_ID = '';
		$USER_ID = $NEWS['USER_ID'];
		$NEWS_CONTENT = $NEWS['NEWS_CONTENT'];
		$NEWS_HTML = $NEWS['NEWS_HTML'];
		$TITLE = $NEWS['TITLE'];

		$query = "INSERT INTO news VALUES (nextval('news_sq'),'" . $TITLE . "',
		:document_html,CURRENT_DATE,CURRENT_TIMESTAMP," . $USER_ID . "," . $NEWS['SOURCE'] . ",:document_content,'".$NEWS['HASH']."') RETURNING news_id";
		$stmt = $DB_CONN->prepare($query);

		//echo strlen($content)."\n";
		$stmt->bindParam(':document_html', $NEWS_HTML, PDO::PARAM_STR);
		$stmt->bindParam(':document_content', $NEWS_CONTENT, PDO::PARAM_STR);
		$SSTRING= $stmt->execute();

		// $SSTRING = runQuery($query);
		// echo "OUTCOME:";

		// print_r($SSTRING);
		if ($SSTRING !== false) {
			
			$row=$stmt->fetch();
			
			$CREATED_NEWS_ID = $row['news_id'];
		}else throw new Exception('Unable to insert news');

		return $CREATED_NEWS_ID;
	} catch (Exception $e) {
		print_r($e);
		return null;
	}
}
function bc_private_submitNews($NEWS)
{

	
	try {
		global $GLB_VAR;
		global $DB_CONN;
		$CREATED_NEWS_ID = '';
		$USER_ID = $NEWS['USER_ID'];
		$NEWS_CONTENT = $NEWS['NEWS_CONTENT'];
		$NEWS_HTML = $NEWS['NEWS_HTML'];
		$TITLE = $NEWS['TITLE'];

		$query = "INSERT INTO ".$GLB_VAR['SCHEMA_PRIVATE'].".news VALUES (nextval('".$GLB_VAR['SCHEMA_PRIVATE'].".news_sq'),'" . $TITLE . "',
		:document_html,CURRENT_DATE,CURRENT_TIMESTAMP," . $USER_ID . "," . $NEWS['SOURCE'] . ",:document_content,'".$NEWS['HASH']."') RETURNING news_id";
		$stmt = $DB_CONN->prepare($query);

		//echo strlen($content)."\n";
		$stmt->bindParam(':document_html', $NEWS_HTML, PDO::PARAM_STR);
		$stmt->bindParam(':document_content', $NEWS_CONTENT, PDO::PARAM_STR);
		$SSTRING= $stmt->execute();

		// $SSTRING = runQuery($query);
		// echo "OUTCOME:";

		// print_r($SSTRING);
		if ($SSTRING !== false) {
			
			$row=$stmt->fetch();
			
			$CREATED_NEWS_ID = $row['news_id'];
		}else throw new Exception('Unable to insert news');

		return $CREATED_NEWS_ID;
	} catch (Exception $e) {
		print_r($e);
		return null;
	}
}



function getMedian($arr) {
	sort($arr);
    //Make sure it's an array.
    if(!is_array($arr)){
        throw new Exception('$arr must be an array!');
    }
    //If it's an empty array, return FALSE.
    if(empty($arr)){
        return false;
    }
    //Count how many elements are in the array.
    $num = count($arr);
    //Determine the middle value of the array.
    $middleVal = floor(($num - 1) / 2);
    //If the size of the array is an odd number,
    //then the middle value is the median.
    if($num % 2) { 
        return $arr[$middleVal];
    } 
    //If the size of the array is an even number, then we
    //have to get the two middle values and get their
    //average
    else {
        //The $middleVal var will be the low
        //end of the middle
        $lowMid = $arr[$middleVal];
        $highMid = $arr[$middleVal + 1];
        //Return the average of the low and high.
        return (($lowMid + $highMid) / 2);
    }
}





/// Download a file using wget and tries multiple times if needed:
function dl_file($path,$max_tries=3,$NAME='',$timeout=-1)
{
	//$TRIES=array('SUCCESS'=>false);
	for ($I=0;$I<$max_tries;++$I)
	{
		
		$job='wget  -q -c --no-check-certificate ';
		if ($NAME!='') $job .= ' -O "'.$NAME.'" ';
		if ($timeout!=-1) $job='timeout '.$timeout.' '.$job;
		system($job." '".$path."'",$return);

		switch ($return)
		{
			case 0: return true; break;
			// case 1: $TRIES[]= array('code'=>1,'msg'=>'Generic error code');break;
			// case 2: $TRIES[]=array('code'=>2,'msg'=>'Error in command line');break;
			// case 3: $TRIES[]=array('code'=>3,'msg'=>'File I/O error');break;
			// case 4: $TRIES[]=array('code'=>4,'msg'=>'Network failure');break;
			// case 5: $TRIES[]=array('code'=>5,'msg'=>'SSL verification failure');break;
			// case 6: $TRIES[]=array('code'=>6,'msg'=>'Authentification failure');break;
			// case 7: $TRIES[]=array('code'=>7,'msg'=>'Protocol error');break;
			// case 8: $TRIES[]=array('code'=>8,'msg'=>'Server issued error');break;
		}
		sleep(1);
	}
	return false;
}


function getAllForeignRel($SCHEMAS=array())
{
	global $GLB_VAR;
	if ($SCHEMAS==array())
	{
		$SCHEMAS=array("'".$GLB_VAR['PUBLIC_SCHEMA']."'");
		if ($GLB_VAR['SCHEMA_PRIVATE']!='')$SCHEMAS[]="'".$GLB_VAR['SCHEMA_PRIVATE']."'";
	}
	
	global $JSON_PARENT_FOREIGN;
	/// Because some taxons can be merged, we need to be able to redirect records from other tables accordingly
	/// Using foreign key constraints, we are going to find all tables having a column referencing taxon_id
//	addLog("Find all dependent table ");
	$query=" SELECT  DISTINCT                           
	tc.table_schema, 
	tc.constraint_name, 
	tc.table_name as source_table_name, 
	kcu.column_name as source_column_name, 
	ccu.constraint_schema,
	ccu.table_schema AS foreign_table_schema,
	ccu.table_name AS foreign_table_name,
	ccu.column_name AS foreign_column_name 
	FROM 
	information_schema.table_constraints AS tc 
	JOIN information_schema.key_column_usage AS kcu
	  ON tc.constraint_name = kcu.constraint_name
	  AND tc.table_schema = kcu.table_schema
	JOIN information_schema.constraint_column_usage AS ccu
	  ON ccu.constraint_name = tc.constraint_name
	  AND ccu.table_schema = tc.table_schema
	WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_schema IN (".implode(',',$SCHEMAS).");";
	
	$res=runQuery($query);if ($res===false)																	failProcess("FCT_001",'Unable to get dependent tables');
	
	$JSON_PARENT_FOREIGN=array();
	foreach ($res as $line)
	{
		if (!in_array("'".$line['table_schema']."'",$SCHEMAS)||!in_array("'".$line['foreign_table_schema']."'",$SCHEMAS))continue;
		
		$JSON_PARENT_FOREIGN['PARENT'][$line['table_schema'].'.'.$line['source_table_name']][$line['source_column_name']]=array('SCHEMA'=>$line['foreign_table_schema'],'TABLE'=>$line['foreign_table_name'],'COLUMN'=>$line['foreign_column_name']);
		$JSON_PARENT_FOREIGN['CHILD'][$line['foreign_table_schema'].'.'.$line['foreign_table_name']][$line['foreign_column_name']][]=array('SCHEMA'=>$line['table_schema'],'TABLE'=>$line['source_table_name'],'COLUMN'=>$line['source_column_name']);
	}

}


function getForeignTables($TABLE,$TABLE_SCHEMA,$FILTER=array())
{
	global $GLB_VAR;
	$SCHEMAS=array($GLB_VAR['PUBLIC_SCHEMA']);
	if ($GLB_VAR['SCHEMA_PRIVATE']!='')$SCHEMAS[]=$GLB_VAR['SCHEMA_PRIVATE'];
	
	
	/// Because some taxons can be merged, we need to be able to redirect records from other tables accordingly
	/// Using foreign key constraints, we are going to find all tables having a column referencing taxon_id
	addLog("Find parent dependent table for ".$TABLE);
	$query=" SELECT  DISTINCT                           
	tc.table_schema, 
	tc.constraint_name, 
	tc.table_name as source_table_name, 
	kcu.column_name as source_column_name, 
	ccu.constraint_schema,
	ccu.table_schema AS foreign_table_schema,
	ccu.table_name AS foreign_table_name,
	ccu.column_name AS foreign_column_name 
	FROM 
	information_schema.table_constraints AS tc 
	JOIN information_schema.key_column_usage AS kcu
	  ON tc.constraint_name = kcu.constraint_name
	  AND tc.table_schema = kcu.table_schema
	JOIN information_schema.constraint_column_usage AS ccu
	  ON ccu.constraint_name = tc.constraint_name
	  AND ccu.table_schema = tc.table_schema
	WHERE tc.constraint_type = 'FOREIGN KEY'  AND tc.table_name='".$TABLE."' AND tc.table_schema='".$TABLE_SCHEMA."';";
	
	$res=runQuery($query);if ($res===false)																	failProcess("FCT_001",'Unable to get dependent tables');
	
	$DEP_TABLES=array();
	foreach ($res as $line)
	{
		if (!in_array($line['table_schema'],$SCHEMAS)||!in_array($line['foreign_table_schema'],$SCHEMAS))continue;
		if ($FILTER!=array() && in_array($line['table_name'],$FILTER))continue;
		$DEP_TABLES[$line['source_column_name']]=array('SCHEMA'=>$line['foreign_table_schema'],'TABLE'=>$line['foreign_table_name'],'COLUMN'=>$line['foreign_column_name']);
	}
	
	return $DEP_TABLES;
}


function getDepTableList($TABLE,$TABLE_SCHEMA,$FILTER=array())
{
	global $GLB_VAR;
	$SCHEMAS=array($GLB_VAR['PUBLIC_SCHEMA']);
	if ($GLB_VAR['SCHEMA_PRIVATE']!='')$SCHEMAS[]=$GLB_VAR['SCHEMA_PRIVATE'];
	
	
	/// Because some taxons can be merged, we need to be able to redirect records from other tables accordingly
	/// Using foreign key constraints, we are going to find all tables having a column referencing taxon_id
	addLog("Find child dependent table for ".$TABLE);
	$query=" SELECT DISTINCT                            
	tc.table_schema, 
	tc.constraint_name, 
	tc.table_name, 
	kcu.column_name, 
	ccu.constraint_schema,
	ccu.table_schema AS foreign_table_schema,
	ccu.table_name AS foreign_table_name,
	ccu.column_name AS foreign_column_name 
	FROM 
	information_schema.table_constraints AS tc 
	JOIN information_schema.key_column_usage AS kcu
	  ON tc.constraint_name = kcu.constraint_name
	  AND tc.table_schema = kcu.table_schema
	JOIN information_schema.constraint_column_usage AS ccu
	  ON ccu.constraint_name = tc.constraint_name
	  AND ccu.table_schema = tc.table_schema
	WHERE tc.constraint_type = 'FOREIGN KEY'  AND ccu.table_name='".$TABLE."' AND ccu.table_schema='".$TABLE_SCHEMA."';";
	
	$res=runQuery($query);if ($res===false)																	failProcess("FCT_001",'Unable to get dependent tables');

	
	$DEP_TABLES=array();
	foreach ($res as $line)
	{
		echo implode("\t",$line)."\n";
		if (!in_array($line['table_schema'],$SCHEMAS)||!in_array($line['foreign_table_schema'],$SCHEMAS))continue;
		if ($FILTER!=array() && in_array($line['table_name'],$FILTER))continue;
		$DEP_TABLES[$line['foreign_column_name']][]=array('SCHEMA'=>$line['table_schema'],'TABLE'=>$line['table_name'],'COLUMN'=>$line['column_name']);
	}
	print_R($DEP_TABLES);
	return $DEP_TABLES;
}



function getDepTables($TABLE,$TABLE_SCHEMA,$FILTER=array())
{
	global $GLB_VAR;
	$SCHEMAS=array($GLB_VAR['PUBLIC_SCHEMA']);
	if ($GLB_VAR['SCHEMA_PRIVATE']!='')$SCHEMAS[]=$GLB_VAR['SCHEMA_PRIVATE'];
	
	
	/// Because some taxons can be merged, we need to be able to redirect records from other tables accordingly
	/// Using foreign key constraints, we are going to find all tables having a column referencing taxon_id
	addLog("Find child dependent table for ".$TABLE);
	$query=" SELECT DISTINCT                            
	tc.table_schema, 
	tc.constraint_name, 
	tc.table_name, 
	kcu.column_name, 
	ccu.constraint_schema,
	ccu.table_schema AS foreign_table_schema,
	ccu.table_name AS foreign_table_name,
	ccu.column_name AS foreign_column_name 
	FROM 
	information_schema.table_constraints AS tc 
	JOIN information_schema.key_column_usage AS kcu
	  ON tc.constraint_name = kcu.constraint_name
	  AND tc.table_schema = kcu.table_schema
	JOIN information_schema.constraint_column_usage AS ccu
	  ON ccu.constraint_name = tc.constraint_name
	  AND ccu.table_schema = tc.table_schema
	WHERE tc.constraint_type = 'FOREIGN KEY'  AND ccu.table_name='".$TABLE."' AND ccu.table_schema='".$TABLE_SCHEMA."';";
	
	$res=runQuery($query);if ($res===false)																	failProcess("FCT_001",'Unable to get dependent tables');
	//print_r($res);exit;
	$DEP_TABLES=array();
	foreach ($res as $line)
	{
		if (!in_array($line['table_schema'],$SCHEMAS)||!in_array($line['foreign_table_schema'],$SCHEMAS))continue;
		if ($FILTER!=array() && in_array($line['table_name'],$FILTER))continue;
		$DEP_TABLES[$line['constraint_schema'].'.'.$line['table_name']]=$line['constraint_name'];
	}
	return $DEP_TABLES;
}


function delTree($dir) 
{
	$files = array_diff(scandir($dir), array('.','..'));
	 foreach ($files as $file) {
	   (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
	 }
	 return rmdir($dir);
}

function unzip($path)
{
	system('unzip '.$path,$return);
	if ($return ==0)return true;
	else return false;
}



function defineTaxonList()
{
	global $GLB_VAR;
	global $GLB_TREE;
	global $TG_DIR;
	$TAXON_LIMIT_LIST=array();
	if ($GLB_VAR['TAXON_LIMIT']!='N/A')
	{
		$tab=explode('|',$GLB_VAR['TAXON_LIMIT']);
		foreach ($tab as $t)
		{
			if (!is_numeric($t))														failProcess("FCT_DEFTAXON_001",'In CONFIG_GLOBAL>TAXON_LIMIT '.$t.' must be numeric');
			$TAXON_LIMIT_LIST[]=$t;
		}
	}


	$molecule_INFO=$GLB_TREE[getJobIDByName('dl_chembl')];
	if ($molecule_INFO['ENABLED']=='T' && $molecule_INFO['TIME']['DEV_DIR']!='N/A')
	{
		
		$res=runQuery("SELECT DISTINCT tax_id FROM public.target_dictionary");
		if ($res===false) failProcess("FCT_DEFTAXON_002",'Unable to get targets from chEMBL');
		foreach ($res as $line)
		{
			if ($line['tax_id']=='')continue;
			if (!in_array($line['tax_id'],$TAXON_LIMIT_LIST))$TAXON_LIMIT_LIST[]=$line['tax_id'];
		}
	
	}
	$UNIPROT_INFO=$GLB_TREE[getJobIDByName('dl_proteome')];
	if ($UNIPROT_INFO['ENABLED']=='T' && $UNIPROT_INFO['TIME']['DEV_DIR']!='-1')
	{
		/// Get the working directory for ChEMBL
		$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];	if (!is_dir($W_DIR)) 			failProcess("FCT_DEFTAXON_003",'NO '.$W_DIR.' found ');
		$W_DIR.='/'.$UNIPROT_INFO['DIR'].'/';   	if (!is_dir($W_DIR)) 			failProcess("FCT_DEFTAXON_004",'Unable to find and create '.$W_DIR);
		$W_DIR.=$UNIPROT_INFO['TIME']['DEV_DIR'];  	if (!is_dir($W_DIR)) 			failProcess("FCT_DEFTAXON_005",'Unable to find new process dir '.$W_DIR);
		$W_DIR.='/PROTEOMES'; 						if (!is_dir($W_DIR)) 			failProcess("FCT_DEFTAXON_006",'Unable to find PROTEOMES');
		if (is_file($W_DIR.'/proteome_list')) 
		{
			$fp=fopen($W_DIR.'/proteome_list','r'); if (!$fp) 						failProcess("FCT_DEFTAXON_007",'Unable to open proteome_list');
			while(!feof($fp))
			{
				$line=stream_get_line($fp,10000,"\n");
				if ($line=='')continue;
				$tab=explode("\t",$line);
				if (!isset($tab[3]) || $tab[3]=='')continue;
				$taxids=explode("|",$tab[3]);
				foreach ($taxids as $t)
				{
					if (!is_numeric($t))continue;
					if (!in_array($t,$TAXON_LIMIT_LIST))$TAXON_LIMIT_LIST[]=$t;
				}
				
			}
			fclose($fp);
		}
	}
	$UNIPROT_INFO=$GLB_TREE[getJobIDByName('dl_swissprot')];
	if ($UNIPROT_INFO['ENABLED']=='T' && $UNIPROT_INFO['TIME']['DEV_DIR']!='-1')
	{
		/// Get the working directory for ChEMBL
		$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];	if (!is_dir($W_DIR)) 			failProcess("FCT_DEFTAXON_008",'NO '.$W_DIR.' found ');
		$W_DIR.='/'.$UNIPROT_INFO['DIR'].'/';   	if (!is_dir($W_DIR)) 			failProcess("FCT_DEFTAXON_009",'Unable to find and create '.$W_DIR);
		$W_DIR.=$UNIPROT_INFO['TIME']['DEV_DIR'];  	if (!is_dir($W_DIR)) 			failProcess("FCT_DEFTAXON_010",'Unable to find new process dir '.$W_DIR);
		$W_DIR.='/SPROT'; 							if (!is_dir($W_DIR)) 			failProcess("FCT_DEFTAXON_011",'Unable to find SPROT');
		if (is_file($W_DIR.'/sprot_list'))
		{
			$fp=fopen($W_DIR.'/sprot_list','r'); if (!$fp) 							failProcess("FCT_DEFTAXON_012",'Unable to open sprot_list');
			while(!feof($fp))
			{
				$line=stream_get_line($fp,10000,"\n");
				if ($line=='')continue;
				$tab=explode("\t",$line);
				if (!isset($tab[3]) || $tab[3]=='')continue;
				$taxids=explode("|",$tab[3]);
				foreach ($taxids as $t)
				{
					if (!is_numeric($t))continue;
					if (!in_array($t,$TAXON_LIMIT_LIST))$TAXON_LIMIT_LIST[]=$t;
				}
				
			
			}
			fclose($fp);
		}
	}


	/// TODO: XRAY

	//if ($GLB_TREE['dl_proteome']=='T')
	sort($TAXON_LIMIT_LIST);
	addLog(count($TAXON_LIMIT_LIST)." taxons to consider: ".implode(";",$TAXON_LIMIT_LIST));
	return $TAXON_LIMIT_LIST;
}


function loadBiotypes()
{
	global $TG_DIR;
	global $GLB_VAR;
	
	$BIOTYPES=array();
	$res=runQuery("SELECT seq_type,seq_btype_id FROM seq_btype");if ($res===false) 							failProcess("FCT_BIOTYPE_001",'Unable to retrieve biotypes');
	foreach ($res as $l)$BIOTYPES[$l['seq_type']]=$l['seq_btype_id'];

	/// So if no biotypes are found we load the mapping file
	//if (count($BIOTYPES)!=0)return $BIOTYPES;

	$T_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/GENOME/mapping';	
	if (!checkFileExist($T_DIR))																			failProcess("FCT_BIOTYPE_002",'Unable to find '.$T_DIR);
	$res=runQuery("SELECT so_entry_id,so_name FROM so_entry ");if ($res===false) 							failProcess("FCT_BIOTYPE_003",'Unable to retrieve sequence ontology');
	$SO=array();
	foreach ($res as $line)$SO[$line['so_name']]=$line['so_entry_id'];
	$fp=fopen($T_DIR,'r');if (!$fp)																		failProcess("FCT_BIOTYPE_004",'Unable to open '.$T_DIR);
	if ($BIOTYPES!=array())$SEQ_TYPE_ID=max($BIOTYPES);
	else $SEQ_TYPE_ID=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')continue;
		$tab=explode("\t",$line);
		if (isset($BIOTYPES[$tab[0]]))continue;
		++$SEQ_TYPE_ID;
		$SO_ENTRY_ID='NULL';
		if (isset($tab[1])&&$tab[1]!='' && isset($SO[$tab[1]]))$SO_ENTRY_ID=$SO[$tab[1]];
		$query='INSERT INTO SEQ_BTYPE (SEQ_BTYPE_ID, SEQ_TYPE,SO_ENTRY_ID) VALUES ('.$SEQ_TYPE_ID.",'".$tab[0]."',".$SO_ENTRY_ID.')';
		if (!runQueryNoRes($query))																							failProcess("FCT_BIOTYPE_005",'Unable to insert in SEQ_BTYPE');
		$BIOTYPES[$tab[0]]=$SEQ_TYPE_ID;
	}
	fclose($fp);
	return $BIOTYPES;

}


function connectDB()
{
	global $DB_CONN;
	global $GLB_VAR;
	global $DB_INFO;
	global $DB_SCHEMA;
	if (!defined('NO_DB_CONNECTION'))
	{
		date_default_timezone_set($GLB_VAR['TIMEZONE']);
		$DB_INFO=array('HOST'=>getenv('DB_HOST'),
		'PORT'=>getenv('DB_PORT'),
		'NAME'=>getenv('DB_NAME'),
		'USER'=>getenv('PGUSER'),
		'PWD'=>getenv('PGPASSWORD'),
			);
			$DB_INFO['COMMAND']='psql -h $DB_HOST -p $DB_PORT -U $PGUSER -d $DB_NAME ';
		
		$DB_CONN=null;
		try{
		//echo 'pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_NAME').' ' .getenv('PGUSER').' '.getenv('PGPASSWORD');
		$DB_CONN = new PDO(
			'pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_NAME'),
				getenv('PGUSER'),
				getenv('PGPASSWORD'),
				array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=> false ));
				unset($str);unset($CONN_DATA);
		} catch(PDOException $e) {
		throw new Exception("Unable to connect to the database\n".$e->getMessage());
		}
		//print_r($GLB_VAR);exit;
		$DB_SCHEMA = $GLB_VAR['DB_SCHEMA'];
		
		try {
			//echo 'SET SESSION search_path TO '.$DB_SCHEMA.';'."\n";;
			runQuery('SET SESSION search_path TO '.$DB_SCHEMA.';');
		}catch(PDOException $e) {
			throw new Exception("Unable to set search_path\n".$e->getMessage(),ERR_TGT_SYS);
		}

	}
}

function downloadFTPFile($path,$outfile,$tag='',$w_path=true,$create_dir=true)
{
	if (!checkFileExist($outfile.'.html') &&
		!dl_file($path,3,$outfile.'.html'))			failProcess("FCT_DL_FTP_001",'Unable to download '.$outfile.'.html');
	echo "DOWNLOAD ".$path.' TO '.$outfile."\n";
		$fpB=fopen($outfile.'.html','r');if (!$fpB) failProcess("FCT_DL_FTP_002",'Unable to open '.$outfile.'.html');
		if ($create_dir && !is_dir($outfile) && !mkdir($outfile))failProcess("FCT_DL_FTP_003",'Unable to create '.$outfile.' directory');
		while(!feof($fpB))
		{
			$line=stream_get_line($fpB,10000,"\n");
			//echo $line."\n";

			$tab=explode(">",$line);
			//print_R($tab);
			if (!isset($tab[5]))continue;
			
			if ($tab[5]=='')continue;
			$name=explode('"',$tab[5])[1];
			//echo $name."\n";
			if ($tag!='' && strpos($name,$tag)===false)continue;
			echo "DOWNLOADING ".$path.'/'.$name."\t";
			$path_all='';
			if ($w_path)$path_all.=$path.'/';
			$path_all.=$name;
			$out='';
			if ($create_dir)$out.=$outfile.'/';
			$out.=$name;
			 if (checkFileExist($out)) {echo "EXISTS\n";continue;}
				if ( !dl_file($path_all,3,$out))                   failProcess("FCT_DL_FTP_004",'Unable to download '.$path.$name);
				echo "DONE\n";
		}
		unlink($outfile.'.html');
}
/// Untar a file
function untar($path)
{
	system('tar -zxf '.$path,$return);
	if ($return ==0)return true;
	else return false;
}
function ungzip($path)
{
	system('gzip -f -d '.$path,$return);
	if ($return ==0)return true;
	else return false;
}
function unbzip2($path)
{
	system('bzip2 -f -d '.$path,$return);
	if ($return ==0)return true;
	else return false;
}

function checkFileExist($FILE)
{
	if (!is_file($FILE)) return false;
	clearstatcache ();
	if (filesize($FILE)==0)return false;
	return true;

}

function getLineCount($FILE)
{
	$linecount = 0;
	$handle = fopen($FILE, "r");
	if (!$handle)failProcess("FCT_LINE_COUNT_001",'Unable to open '.$FILE);
	while(!feof($handle)){
		  $line = fgets($handle, 4096);
		  $linecount = $linecount + substr_count($line, PHP_EOL);
	}
	fclose($handle);
	return  $linecount;
}

function validateLineCount($FILE,$MIN_LINES)
{
	return  (getLineCount($FILE) >= $MIN_LINES);
}



function getCurrDate()			 {return date("Y-m-d");}
function getTSPtoDate($timestamp){return date("Y-m-d-H-i-s",$timestamp);}
function getCurrDateTime()		 {return date("Y-m-d-H-i-s");}



function is_dir_empty($dir) {
  if (!is_readable($dir)) return NULL; 
  return (count(scandir($dir)) == 2);
}


function cleanDirectory($dir)
{
	if ($dir=='/' || $dir=='')return true;
	$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
	$files = new RecursiveIteratorIterator($it,
             RecursiveIteratorIterator::CHILD_FIRST);
	foreach($files as $file) 
	{
    	if ($file->isDir()){
        	if (!rmdir($file->getRealPath())) return false;
		} else {
			if (!unlink($file->getRealPath())) return false;
		}
	}
	if (!rmdir($dir))return false;
	return true;
}



function addLog($INFO='')
{
	global $PROCESS_CONTROL;
	global $TG_DIR;
	global $GLB_VAR;
	$TIME=microtime_float();
	echo $INFO."\n";
	if ($PROCESS_CONTROL['STEP']>0)$PROCESS_CONTROL['LOG'][$PROCESS_CONTROL['STEP']-1].='|'.round($TIME-$PROCESS_CONTROL['STEP_TIME'],2);
	$PROCESS_CONTROL['STEP']++;
	$PROCESS_CONTROL['LOG'][]=getCurrDateTime().'|'.$INFO;
	$PROCESS_CONTROL['STEP_TIME']=$TIME;

	$fp=fopen($TG_DIR.'/'.$GLB_VAR['LOG_DIR'].$PROCESS_CONTROL['JOB_NAME'].'.log','w');
	if ($fp)fputs($fp,serialize($PROCESS_CONTROL));
	fclose($fp);
}

function prepString($name)
{
	//$name=str_replace("'","''",$name);
	$name=str_replace('"','""',$name);
	return $name;
}



function failProcess($ID,$INFO,&$PCC=array())
{
	global $PROCESS_CONTROL;
	global $TG_DIR;
	global $GLB_VAR;
	global $START_SCRIPT_TIME;
	global $START_SCRIPT_TIMESTAMP;
	global $GLB_TREE;
	$GLB=false;
	if ($PCC==array()){$PCC=&$PROCESS_CONTROL;$GLB=true;}

	$PCC['STATUS']='FAIL';

	$TIME=microtime_float();
	$JOB_ID=getJobIDByName($PCC['JOB_NAME']);
	$JOB_INFO=$GLB_TREE[$JOB_ID];
	$SCHEMA=$GLB_VAR['PUBLIC_SCHEMA'];
	if ($JOB_INFO['IS_PRIVATE']==1)$SCHEMA=$GLB_VAR['SCHEMA_PRIVATE'];
	$res=runQueryNoRes("INSERT INTO ".$SCHEMA.".biorels_job_history 
						VALUES (
							(SELECT br_timestamp_id 
							FROM  ".$SCHEMA.".biorels_timestamp 
							WHERE job_name='".$PCC['JOB_NAME']."'),
						'".$START_SCRIPT_TIMESTAMP."',
						".ceil($TIME-$START_SCRIPT_TIME).",
						'F',
						'".$INFO."')");
	

	if ($PCC['STEP']>0)
	{
		if (!isset($PCC['LOG'][$PCC['STEP']]))$PCC['LOG'][$PCC['STEP']]='|'.round($TIME-$PCC['STEP_TIME'],2);
		else $PCC['LOG'][$PCC['STEP']].='|'.round($TIME-$PCC['STEP_TIME'],2);
	}
	$PCC['STEP']++;
	$PCC['LOG'][]=getCurrDateTime().'|'.$INFO;
	$PCC['LOG'][]='TIME_COMPUT|'.round($TIME-$START_SCRIPT_TIME,3)."\n";

	$PCC['STEP_TIME']=$TIME;
	echo $ID."\t".$INFO."\n";
	
	$fp=fopen($TG_DIR.$GLB_VAR['LOG_DIR'].$PCC['JOB_NAME'].'.log','w');
	if ($fp)fputs($fp,serialize($PCC));
	else sendKillMail($ID,"Unable to open ".$TG_DIR.$GLB_VAR['LOG_DIR'].$PCC['JOB_NAME'].'.log');
	fclose($fp);
	if (!defined("MONITOR_JOB"))	qengine_validate(getJobIDByName($PCC['JOB_NAME']));
	
	if ($GLB)sendKillMail($ID,$INFO);
	else sendMail($ID,$INFO);
}


function getSource($SOURCE_NAME)
{
	global $SOURCE_LIST;
	if (isset($SOURCE_LIST[strtolower($SOURCE_NAME)]))return $SOURCE_LIST[strtolower($SOURCE_NAME)];
	$res=runQuery("SELECT source_id FROM source WHERE LOWER(source_name)=LOWER('".str_replace("'","''",$SOURCE_NAME)."')");
	if ($res===false)																		failProcess("FCT_GET_SOURCE_001",'Failed to fetch source '.$SOURCE_NAME);
	$SOURCE_ID=-1;
	if (count($res)==0)
	{
		$res=runQuery("SELECT nextval('source_seq') v");
		if ($res===false)																			failProcess("FCT_GET_SOURCE_002",'Unable to get Max source');
		$MAXDBID=$res[0]['v'];
		
		$query='INSERT INTO source (source_Id,source_name,	version,user_name) VALUES ('.$MAXDBID.",'".str_replace("'","''",$SOURCE_NAME)."',NULL,NULL)";
		if (!runQueryNoRes($query))															failProcess("FCT_GET_SOURCE_003",'Unable to create '.$SOURCE_NAME.' source');
		$SOURCE_ID=$MAXDBID;
	}else $SOURCE_ID=$res[0]['source_id'];
	$SOURCE_LIST[strtolower($SOURCE_NAME)]=$SOURCE_ID;
	return $SOURCE_ID;
}


function pushToProd()
{
	global $TG_DIR;
	global $GLB_VAR;
	global $JOB_INFO;
	global $JOB_ID;
	global $W_DIR;
	global $PROCESS_CONTROL;

	$ARCHIVE='';
	if ($GLB_VAR['KEEP_PREVIOUS_DATA']=='Y')
	{
		/// We define the directory where the previous version is going to be archived
		$ARCHIVE=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/ARCHIVE';
		if (!is_dir($ARCHIVE) && !mkdir($ARCHIVE)) 										failProcess($JOB_ID."_FCT_PUSH_PROD_000",'Unable to create '.$ARCHIVE.' directory');

	}
	$PRD_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'];
	if (!is_dir($PRD_DIR) && !mkdir($PRD_DIR))													failProcess($JOB_ID."_FCT_PUSH_PROD_001",'Unable to find '.$PRD_DIR.' directory');
	 

	$PRD_PATH=$PRD_DIR.'/'.$JOB_INFO['DIR'];
	echo $PRD_PATH."\n";
	echo $PRD_DIR."\n";

	/// Remove the previous symlink
	if (is_link($PRD_PATH))
	{
		system('unlink '.$PRD_PATH,$return_code);
		if ($return_code !=0)												failProcess($JOB_ID."_FCT_PUSH_PROD_002",'Unable to unlink '.$PRD_PATH.' directory');
	}

	/// If the dev directory is -1, it means it's the first time
	/// so we can just create a symlink to the working directory
	if ($JOB_INFO['TIME']['DEV_DIR']==-1)
	{
		system('ln -s '.$W_DIR.' '.$PRD_PATH,$return_code);
		if ($return_code!=0)														failProcess($JOB_ID."_FCT_PUSH_PROD_003",'Unable to create symlink '.$PRD_PATH.' directory');
		return;	
	}
	

	$curr_ini_dir=getcwd();
	
	//echo "PATH: ".$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR']."\n";
	if (!chdir($TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR']))failProcess($JOB_ID."_FCT_PUSH_PROD_004",'Unable to change directory to '.$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR']);

	$dirs=scandir('.');
	foreach ($dirs as $dir)
	{
		if ($dir=='.'||$dir=='..')continue;
		//echo $dir.' '.$PROCESS_CONTROL['DIR']."\n";
		if ($dir==$PROCESS_CONTROL['DIR'])continue;
		
		addLog("Clean up ".$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$dir);

		if ($GLB_VAR['KEEP_PREVIOUS_DATA']=='Y')
		{

			addLog("\tArchiving");
			system('tar -czf '.$ARCHIVE.'/'.$dir.'.tar.gz '.$dir,$return_code);
			if ($return_code !=0)												failProcess($JOB_ID."_FCT_PUSH_PROD_005",'Unable to create '.$ARCHIVE.'/'.$dir.'.tar.gz archive');
		}
		addLog("\tDeleting ".$dir);
		system('rm -rf ./'.$dir,$return_code);	
		if ($return_code!=0)												failProcess($JOB_ID."_FCT_PUSH_PROD_006",'Unable to delete '.$CURR_PROD_DIR.' directory');
		
	}

	if (!chdir($curr_ini_dir))failProcess($JOB_ID."_FCT_PUSH_PROD_007",'Unable to change directory back to '.$curr_ini_dir);
		
	system('ln -s '.$W_DIR.' '.$PRD_PATH,$return_code);
	if ($return_code!=0)														failProcess($JOB_ID."_FCT_PUSH_PROD_008",'Unable to create symlink '.$PRD_PATH.' directory');
	
	
}



function successProcess($STATUS_TAG='SUCCESS',&$PCC=array())
{
	addLog("SUCCESS PROCESS (IS MONITOR:".defined('MONITOR_JOB').')');
	global $MAIL_COMMENTS;
	global $PROCESS_CONTROL;
	global $GLB_VAR;
	global $TG_DIR;
	global $GLB_TREE;
	global $START_SCRIPT_TIME;
	global $START_SCRIPT_TIMESTAMP;
	$GLB=false;
	
	if ($PCC==array()){$PCC=&$PROCESS_CONTROL;$GLB=true;}
		$PCC['STATUS']=$STATUS_TAG;
	$PCC['STEP']++;
	$TIME=microtime_float();

	$JOB_ID=getJobIDByName($PCC['JOB_NAME']);
	$JOB_INFO=$GLB_TREE[$JOB_ID];
	$SCHEMA=$GLB_VAR['PUBLIC_SCHEMA'];
	if ($JOB_INFO['IS_PRIVATE']==1)$SCHEMA=$GLB_VAR['SCHEMA_PRIVATE'];
	$res=runQueryNoRes("INSERT INTO ".$SCHEMA.".biorels_job_history VALUES ((SELECT br_timestamp_id FROM  ".$SCHEMA.".biorels_timestamp where job_name='".$PCC['JOB_NAME']."'),'".$START_SCRIPT_TIMESTAMP."',".ceil($TIME-$START_SCRIPT_TIME).",'T',NULL)");
	
	$PCC['LOG'][]=getCurrDateTime().'|'.round($TIME-$PCC['STEP_TIME'],2).'|END';
	$PCC['LOG'][]='TIME_COMPUT|'.round($TIME-$START_SCRIPT_TIME,3)."\n";
	$PCC['STEP_TIME']=$TIME;
	$LOG_FILE=$TG_DIR.$GLB_VAR['LOG_DIR'].$PCC['JOB_NAME'].'.log';
	$fp=fopen($LOG_FILE,'w');
	if ($fp){fputs($fp,serialize($PCC));echo "PUT IN ".$LOG_FILE."\n";}
	else {print_r(error_get_last());die("Unable to open LOG FILE ".$LOG_FILE);}

	fclose($fp);
	
	if ($MAIL_COMMENTS!=array())
	{
		sendMail($ID,implode("\n",$MAIL_COMMENTS));
	}
	
	//if (!defined("MONITOR_JOB")) {
		
		qengine_validate(getJobIDByName($PCC['JOB_NAME']));
	//}
	if ($GLB)exit(0);
}



function getJobIDByName($name)
{
	global $GLB_TREE;
	foreach ($GLB_TREE as $ID=>$INFO) 
	{
		
		if ($INFO['NAME']==$name)return $ID;
	}

	echo ("No job with the name ".$name."\n");
	print_r(debug_backtrace());
	die(1);
}




/*
	Create a lock file with the PID of monitor_jobs.php to ensure the master script is not run multiple times concurrently.
*/
function createLock()
{
	global $TG_DIR;
	global $GLB_VAR;
	if (checkFileExist($TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/lock'))
	{
		$former_pid=file_get_contents($TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/lock');
		if (file_exists( "/proc/$former_pid" )){die('Process currently running');}
	}
	$PID=getmypid();
	$fp=fopen($TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/lock','w');
	if (!$fp){die('Unable to create lock');}
	fputs($fp,$PID);
	fclose($fp);
}

function updateReleaseDate($ID,$TAG,$VALUE)
{
	global $TG_DIR;
	global $GLB_VAR;


	$res=runQuery("SELECT * FROM biorels_datasource where source_name ='".$TAG."'");
	if ($res===false)	sendKillMail($ID.'_FCT_UPDATE_RELEASE_DATE',"updateReleaseData - Unable to query biorels_datasource ");
	if (count($res)==0)
	{
		if (!runQueryNoRes('INSERT INTO biorels_datasource (source_name,release_version,date_released) VALUES ('."'".$TAG."','".$VALUE."',CURRENT_TIMESTAMP)"))sendKillMail($ID.'_FCT_UPDATE_RELEASE_DATE-INSERT',"updateReleaseData - Unable to insert biorels_datasource ");
	}
	else 
	{
		if (!runQueryNoRes("UPDATE biorels_datasource SET release_version='".$VALUE."',date_released=CURRENT_TIMESTAMP WHERE source_name='".$TAG."'"))sendKillMail($ID.'_FCT_UPDATE_RELEASE_DATE-UPDATE',"updateReleaseData - Unable to update biorels_datasource ");
	}

	
	
}

function encode_data($text)
{
	global $GLB_VAR;
		$ivlen = openssl_cipher_iv_length($cipher="aes-256-ctr");
		if (!isset($GLB_VAR['encode_iv']))
		{
			$iv=file_get_contents($GLB_VAR['WORKDIR']."iv.txt");
			$GLB_VAR['encode_iv']=$key;
		}else $iv=$GLB_VAR['encode_iv'];
		if (!isset($GLB_VAR['encode_key']))
		{
			$key=file_get_contents($GLB_VAR['WORKDIR']."key.txt");
			$GLB_VAR['encode_key']=$key;
		}else $key=$GLB_VAR['encode_key'];
        
        $ciphertext_raw = openssl_encrypt($text, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);

        $ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
        return $ciphertext;
}


function decode_data($ciphertext)
{
	global $GLB_VAR;
	if (!isset($GLB_VAR['encode_key']))
	{
		$key=file_get_contents($GLB_VAR['WORKDIR']."/key.txt");
		$GLB_VAR['encode_key']=$key;
	}else $key=$GLB_VAR['encode_key'];

	//decrypt later....
	$c = base64_decode($ciphertext);
	$ivlen = openssl_cipher_iv_length($cipher="aes-256-ctr");
	$iv = substr($c, 0, $ivlen);
	$hmac = substr($c, $ivlen, $sha2len=32);
	$ciphertext_raw = substr($c, $ivlen+$sha2len);
	$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
	$calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
	if (hash_equals($hmac, $calcmac))//PHP 5.6+ timing attack safe comparison
	{
		return $original_plaintext;
	}

}

function getInBetween($line,$tag)
{
	$pos=strpos($line,'<'.$tag);	if ($pos===false)return "";
	$posS=strpos($line,'>',$pos+1);	if ($posS===false)return "";
	$posE=strpos($line,'</'.$tag,$posS);	if ($posE===false)return "";
	return substr($line,$posS+1,$posE-$posS-1);

}
function getTag($line,$tag)
{
	$pos=strpos($line,$tag.'="');if ($pos===false)return '';
	$posS=strpos($line,'"',$pos+strlen($tag)+3);if ($posS==false)return '';
	return substr($line,$pos+strlen($tag)+2,$posS-$pos-1-strlen($tag)-1);

}

$N_QUERY=0;




function deleteDir($dirPath) 
{
	if (! is_dir($dirPath)) return true;
	
	if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') $dirPath .= '/';
	
	$files = glob($dirPath . '*', GLOB_MARK);
	foreach ($files as $file) {
		if ($file=='.'||$file=='..')continue;
		if (is_dir($file)) {
			deleteDir($file);
		} else {
			unlink($file);
		}
	}
	return rmdir($dirPath);
}

function runClobQuery($query,$pos)
{
	global $N_QUERY;
	++$N_QUERY;
	
	try{
	//echo $query."<br/>";
		global $DB_CONN;
		$stmt=$DB_CONN->prepare($query);
		$stmt->execute();
		if (substr($query,0,6)=="INSERT")return "";
		
		$results=$stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($results!=array())
		foreach ($results as &$l)
		{
		//	print_r($l);
			$T=stream_get_contents($l[$pos]);
			fclose($l[$pos]);
			$l[$pos]=$T;
		}
		$stmt->closeCursor();
		$stmt=null;
		unset($stmt);
		return $results;
	} catch(PDOException $e) {
		echo "Error while running query\n".$e->getMessage()."\n\n".$query."\n";
		return false;
	}
}

function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                if (!rrmdir($full))return false;
            }
            else {
                if (!unlink($full)) return false;
            }
        }
    }
    closedir($dir);
   if (!rmdir($src)) return false;
   return true;
}

function array_change_key_case_recursive($arr)
{
    return array_map(function($item){
        if(is_array($item))
            $item = array_change_key_case_recursive($item);
        return $item;
    },array_change_key_case($arr, CASE_UPPER));
}

function runQuery($query)
{
	$stmt=null;
	try{
//echo $query."<br/>";
		global $DB_CONN;
		
		$stmt=$DB_CONN->prepare($query);
		$stmt->execute();
		$results=$stmt->fetchAll(PDO::FETCH_ASSOC);
		if (defined('SQL_UPPER_PARAM'))return array_change_key_case_recursive($results);
		return $results;
	} catch(PDOException $e) {
		if ($stmt->errorCode()=='HY000')
		{
			sleep(1200);
			return runQuery($query);
		}else throw new Exception("Error while running query\n".$e->getMessage()."\n\n".$query."\n\nERROR CODE:".$stmt->errorCode()."\n",0);//ERR_TGT_SYS);
	}
}

function runQueryNoRes($query)
{

	$stmt=null;
	try{
	//echo $query."<br/>";
		global $DB_CONN;
		$stmt=$DB_CONN->prepare($query);
		$res= $stmt->execute();
		$stmt->closeCursor();
		unset($stmt);
		$stmt=null;
		return $res;
		
	} catch(PDOException $e) {
		echo "Error while running query\n".$e->getMessage()."\n\n".$query."\n\nERROR CODE:".$stmt->errorCode()."\n";
		return false;
	}
}

function runQueryToFile($query,$FILE,$ERR_ID)
{
	try{
	//echo $query."<br/>";
		global $DB_CONN;
		$stmt=$DB_CONN->prepare($query);
		$stmt->execute();
		if (substr($query,0,6)=="INSERT")return false;
		$fp=fopen($FILE,'w'); if (!$fp) failProcess("FCT_007",'Unable to open '.$FILE);
		$FIRST=true;
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
			if ($FIRST){fputs($fp,implode("\t",array_keys($row))."\n");$FIRST=false;}
			fputs($fp,implode("\t",$row)."\n");
		}

		fclose($fp);
		$stmt=null;
		return true;
	} catch(PDOException $e) {
		echo "Error while running query\n".$e->getMessage()."\n\n".$query."\n";
		return false;
	}
}


function getCurrentReleaseDate($NAME,$JOB_ID)
{
	global $GLB_VAR;
	global $TG_DIR;


	$res=runQuery("SELECT * from biorels_datasource where source_name = '".$NAME."'");
	if ($res===false)		failProcess("FCT_CUR_RELEASE_DATE",'Unable to query biorels_datasource'); 
	if (count($res)==0)return -1;
	return $res[0]['release_version'];

}

	

function processCounterion($SCHEMA,$STD_FNAME,$FAILED_FNAME)
{
	global $SOURCE_ID;
	global $DBIDS;
	global $FILES;
	global $STATS;
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	

	addLog("Processing counterion for ".$SCHEMA);
	/// Those are the tables we are going to insert into and which files we are opening for batch insert
	$FILES=array('sm_counterion'=>fopen('INSERT/sm_counterion.csv','w'));
	foreach ($FILES as $N=>$F)if (!$F)																failProcess("FCT_COUNTERION_001",'Unable to open '.$N.'.csv');

	/// Then for each table, we get the max primary key for faster insertions
	foreach (array_keys($FILES) as $P)
	{
		$res=runQuery("SELECT MAX(".$P."_id) CO FROM ".$SCHEMA.".".$P);
		if ($res===false)																failProcess("FCT_COUNTERION_002",'Unable to get max id from '.$P);
		if (count($res)==0)$DBIDS[$P]=0;else $DBIDS[$P]=$res[0]['co'];
		
	}

	$RECORD=array();
	$STATS=array('INI_COUNTER'=>0,'NEW_COUNTER'=>0);
	$MAP_COUNTERIONS=array();
	addLog("\tProcessing standardized counterion for ".$SCHEMA);
	/// Now we start with standardize compounds
	$fp=fopen($STD_FNAME,'r');if (!$fp)										failProcess("FCT_COUNTERION_003",'Unable to open counterion_std.smi');

	//fseek($fp,12932810);
	$DONE_CT=array();
	$HAS_NEW_COUNTER=false;$N_CT=0;
	while(!feof($fp))
	{
		/// Get the line and the corresponding info
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		++$N_CT;
		if ($N_CT%5000==0)addLog("\t\tProcessed ".$N_CT.' counterions');
		//echo $line."\n";
		$tab=explode(" " ,$line);
		//print_R($tab);
		$STD_COUNTER=$tab[0];
		$ORIG_COUNTER=$tab[1];
		//echo "\t".$tab[1]."\t".isset($MAP_COUNTERIONS[$ORIG_COUNTER])."\n";
		if (isset($MAP_COUNTERIONS[$ORIG_COUNTER]))continue;
		$res=runQuery("SELECT * FROM ".$SCHEMA.".sm_counterion where counterion_smiles = '".$STD_COUNTER."'");
		if ($res===false)																failProcess("FCT_COUNTERION_004",'Unable to query sm_counterion');
		//print_r($res);
		if ($res !=array())
		{
			$MAP_COUNTERIONS[$ORIG_COUNTER]=array($res[0]['sm_counterion_id'],$res[0]['counterion_smiles']);
			$DONE_CT[$STD_COUNTER]=$res[0]['sm_counterion_id'];
		}
		else{
			$SM_CT=-1;
			if (isset($DONE_CT[$STD_COUNTER]))
			{
				$SM_CT=$DONE_CT[$STD_COUNTER];
				$MAP_COUNTERIONS[$ORIG_COUNTER]=array($SM_CT,$STD_COUNTER);
				//echo "\tNEW\t".$SM_CT."\n";
				continue;
			}else 
			{	
				$HAS_NEW_COUNTER=true;
				$DBIDS['sm_counterion']++;
				$SM_CT=$DBIDS['sm_counterion'];
				$DONE_CT[$STD_COUNTER]=$SM_CT;
				$MAP_COUNTERIONS[$ORIG_COUNTER]=array($SM_CT,$STD_COUNTER);
				//echo "\tBRAND_NEW\t".$SM_CT."\n";
				fputs($FILES['sm_counterion'],$SM_CT."\t".$STD_COUNTER."\tT\n");
			}
		}
	}
	fclose($fp);


	$RECORD=array();
	/// Proceed to process the rejected records
	if (checkFileExist($FAILED_FNAME)){
		addLog("\tProcessing Rejected counterions records");
		$fp=fopen($FAILED_FNAME,'r');if (!$fp)											failProcess("FCT_COUNTERION_005",'Unable to open molecule_std.smi');
		$N_CT=0;
		
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
			++$N_CT;
			if ($N_CT%5000==0)addLog("\t\tProcessed rejected counterions ".$N_CT);
			$tab=explode(" " ,$line);
			$tab2=explode("|",$tab[1]);
			//$STD_COUNTER=$tab[0];
			$ORIG_COUNTER=$tab[1];
			if (isset($MAP_COUNTERIONS[$ORIG_COUNTER]))continue;
			$res=runQuery("SELECT * FROM ".$SCHEMA.".sm_counterion where counterion_smiles = '".$ORIG_COUNTER."'");
			if ($res===false)																failProcess("FCT_COUNTERION_006",'Unable to query sm_counterion');
			//print_r($res);
			if ($res !=array())
			{
				$MAP_COUNTERIONS[$ORIG_COUNTER]=array($res[0]['sm_counterion_id'],$res[0]['counterion_smiles']);
				$DONE_CT[$ORIG_COUNTER]=$res[0]['sm_counterion_id'];
			}
			else
			{

				$SM_CT=-1;
				if (isset($DONE_CT[$ORIG_COUNTER]))
				{
					
					$SM_CT=$DONE_CT[$ORIG_COUNTER];
					$MAP_COUNTERIONS[$ORIG_COUNTER]=array($SM_CT,$ORIG_COUNTER);
					//echo "\tNEW\t".$SM_CT."\n";
					continue;
				}else 
				{
					$HAS_NEW_COUNTER=true;
					$DBIDS['sm_counterion']++;
					$SM_CT=$DBIDS['sm_counterion'];
					$DONE_CT[$ORIG_COUNTER]=$SM_CT;
					$MAP_COUNTERIONS[$ORIG_COUNTER]=array($SM_CT,$ORIG_COUNTER);
					//echo "\tBRAND_NEW\t".$SM_CT."\n";
					fputs($FILES['sm_counterion'],$SM_CT."\t".$ORIG_COUNTER."\tF\n");
				}	
			}
		}
		fclose($fp);
	}
	if ($HAS_NEW_COUNTER)
	{
		$command='\COPY '.$SCHEMA.'.sm_counterion(sm_counterion_id,counterion_smiles,is_valid) FROM \''."INSERT/sm_counterion.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess("FCT_COUNTERION_007",'Unable to insert sm_counterion'); 
	}
		//print_r($MAP_COUNTERIONS);
		//exit;
	return $MAP_COUNTERIONS;


}


function standardizeCompounds($WITH_MOL_ENTITY=false,$WITH_PUBLIC=true)
{
	global $GLB_VAR;
	global $TG_DIR;
	
	addLog("Standardize Compounds");
	if (!isset($GLB_VAR['TOOL']['FILECONV']))											failProcess("FCT_STD_CPD_001",'Unable to Find fileconv executable');
	if (!isset($GLB_VAR['TOOL']['PYTHON']))												failProcess("FCT_STD_CPD_002",'Unable to Find Python executable');
	if (!isset($GLB_VAR['TOOL']['PREF_SMI']))											failProcess("FCT_STD_CPD_003",'Unable to Find pref_smi tool');
	if (!isset($GLB_VAR['TOOL']['FILECONV_PARAM']))										failProcess("FCT_STD_CPD_004",'Unable to Find fileconv parameter');
	if (!isset($GLB_VAR['TOOL']['FILECONV_COUNTER_PARAM']))								failProcess("FCT_STD_CPD_005",'Unable to Find fileconv parameter for counterion');
	if (!isset($GLB_VAR['TOOL']['PREF_SMI_COUNTER']))									failProcess("FCT_STD_CPD_006",'Unable to Find pref_smi parameter for counterion');
	$PYTHON=$GLB_VAR['TOOL']['PYTHON']; if(!is_executable($PYTHON))						failProcess("FCT_STD_CPD_007",'Unable to execute Python '.$PYTHON);
	$FILECONV=$GLB_VAR['TOOL']['FILECONV']; if(!is_executable($FILECONV))				failProcess("FCT_STD_CPD_008",'Unable to Find fileconv '.$FILECONV);
	$FILECONV_COUNTER_PARAM=$GLB_VAR['TOOL']['FILECONV_COUNTER_PARAM'];
	$PREF_SMI_COUNTER=$GLB_VAR['TOOL']['PREF_SMI_COUNTER'];
	$PREF_SMI=$GLB_VAR['TOOL']['PREF_SMI']; if(!is_executable($PREF_SMI))				failProcess("FCT_STD_CPD_009",'Unable to Find PREF_SMI '.$PREF_SMI);
	$FILECONV_PARAM=$GLB_VAR['TOOL']['FILECONV_PARAM'];
	if (!is_dir('LOG_INSERT') && !mkdir('LOG_INSERT'))									failProcess("FCT_STD_CPD_010",'Unable to create LOG_INSERT');
	if (!is_dir('INSERT') && !mkdir('INSERT'))											failProcess("FCT_STD_CPD_011",'Unable to create INSERT');
	if (!is_dir('STD') && !mkdir('STD'))												failProcess("FCT_STD_CPD_012",'Unable to create STD');
	
	
	
	addLog("\tConvert counterion");
	// /// Standardize molecules with a set of paramts. STD/chembl.smi is input. Valid molecules goes in STD/molecule_out.smi. Bad molecules goes in STD/molecule_rejected.
	$str=$FILECONV.' '.$FILECONV_COUNTER_PARAM.'  -B log=STD/counterion_rejected.smi -S STD/counterion_out.smi -L STD/counterion_rejected.smi  STD/counterion.smi  &> STD/counterion_log.smi';
	exec($str,$res,$return_code);
	if ($return_code!=0)																failProcess("FCT_STD_CPD_013",'Unable to run fileconv');
	
	
	/// Second step for standardization:
		$res=array();
	exec($PREF_SMI.' '. $PREF_SMI_COUNTER.' STD/counterion_out.smi > STD/counterion_std.smi',$res,$return_code);
	if ($return_code!=0)																failProcess("FCT_STD_CPD_014",'Unable to run preferred_smiles');
	if (!is_file('STD/counterion_std.smi'))												failProcess("FCT_STD_CPD_015",'Unable to find counterion_std.smi');


	

	addLog("\tStandardization molecule");

	addLog("\t\tExecute fileconv");
	/////Step1_molecule_inchi STRUCTURE: 	FULL_SMILES [SPACE] ID | Inchi | InchiKey | Counterion | Molecule_smiles
	/// Standardize FULL_SMILES STD/step1_molecule_inchi.smi is input. Valid molecules goes in STD/step2_molecule_std.smi. Bad molecules goes in STD/step2_molecule_wrong.smi
	exec($FILECONV.' '.$FILECONV_PARAM.' -B log=STD/step1_molecule_wrong.smi STD/molecule.smi -S STD/step1_molecule_out.smi &> STD/step1_molecule_log.txt',$res,$return_code);
	if ($return_code!=0)																failProcess("FCT_STD_CPD_017",'Unable to run fileconv');
	$res=array();

	

	addLog("\t\tExecute preferred smiles");
	/// Second step for standardization:
	exec($PREF_SMI.' STD/step1_molecule_out.smi > STD/step1_molecule_std.smi',$res,$return_code);
	if ($return_code!=0)																failProcess("FCT_STD_CPD_018",'Unable to run preferred_smiles');
	if (!is_file('STD/step1_molecule_std.smi'))											failProcess("FCT_STD_CPD_019",'Unable to find step1_molecule_std.smi');

	/////Step1_molecule_out STRUCTURE: 	FULL_SMILES(S) [SPACE] ID | Inchi | InchiKey | Counterion | Molecule_smiles

	addLog("\t\tSwitching full molecule smiles with molecule smiles");
	/// Step 2 merges step1 files and switch FULL_SMILES(s) with molecule_smiles.
	$fpO=fopen('STD/step2_molecule_ini.smi','w');if (!$fpO)								failProcess("FCT_STD_CPD_020",'Unable to open step2_molecule_ini.smi');
	$fp=fopen('STD/step1_molecule_std.smi','r');if (!$fp)								failProcess("FCT_STD_CPD_021",'Unable to open step1_molecule_std.smi');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		$tab=explode(" " ,$line);
		$tab2=explode("|",$tab[1]);
		$FULL_SMILES=$tab[0];
		$NAME=$tab2[0];
		$INCHI=$tab2[1];
		$INCHI_KEY=$tab2[2];
		$COUNTERION=$tab2[3];
		$MOLECULE=$tab2[4];
		fputs($fpO,$MOLECULE.' '.$NAME.'|'.$INCHI.'|'.$INCHI_KEY.'|'.$COUNTERION.'|'.$FULL_SMILES."|T\n");
	}
	fclose($fp);
	$fp=fopen('STD/step1_molecule_wrong.smi','r');if (!$fp)										failProcess("FCT_STD_CPD_022",'Unable to open step1_molecule_wrong.smi');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		$tab=explode(" " ,$line);
		$tab2=explode("|",$tab[1]);
		$FULL_SMILES=$tab[0];
		$NAME=$tab2[0];
		$INCHI=$tab2[1];
		$INCHI_KEY=$tab2[2];
		$COUNTERION=$tab2[3];
		$MOLECULE=$tab2[4];
		fputs($fpO,$MOLECULE.' '.$NAME.'|'.$INCHI.'|'.$INCHI_KEY.'|'.$COUNTERION.'|'.$FULL_SMILES."|F\n");
	}
	fclose($fp);
	fclose($fpO);




 	addLog("\t\tGenerate Inchi");
	///Molecule.smi STRUCTURE: 	Molecule_smiles [SPACE] ID | Inchi | InchiKey | Counterion | FULL_SMILES(s) | FULL_SMILES_STD_SUCCESS
 	$str=$PYTHON.' '.$TG_DIR.'/BACKEND/SCRIPT/LIB_PYTHON/rdkit_utils.py smiles_to_inchi_file  STD/step2_molecule_ini.smi STD/step3_molecule_inchi.smi &> STD/step3_molecule_inchi.log';
 	exec($str,$res,$return_code);
	 if ($return_code!=0)																failProcess("FCT_STD_CPD_016",'Unable to run fileconv');
	 $res=array();
	///Molecule.smi STRUCTURE: 	Molecule_smiles [SPACE] ID | Inchi(s) | InchiKey(s) | Counterion | FULL_SMILES(s) | FULL_SMILES_STD_SUCCESS

	
	/// Now we can standardize our MOLECULE_SMILES

	addLog("\t\tExecute fileconv");
	/// Standardize MOLECULE_SMILES STD/step3_molecule_ini.smi is input. Valid molecules goes in STD/step4_molecule_std.smi. Bad molecules goes in STD/step4_molecule_wrong.smi
	exec($FILECONV.' '.$FILECONV_PARAM.' -B log=STD/step4_molecule_wrong.smi STD/step3_molecule_inchi.smi -S STD/step4_molecule_out.smi &> STD/step4_molecule_log.txt',$res,$return_code);
	if ($return_code!=0)																			failProcess("FCT_STD_CPD_023",'Unable to run fileconv');
	$res=array();

	///// STRUCTURE: 	Molecule_smiles(s) [SPACE] ID | Inchi(s) | InchiKey(s) | Counterion | FULL_SMILES(s) | FULL_SMILES_STD_SUCCESS

	/// Second step for standardization:
	addLog("\t\tExecute preferred smiles");
	exec($PREF_SMI.' STD/step4_molecule_out.smi > STD/step4_molecule_std.smi',$res,$return_code);
	if ($return_code!=0)																			failProcess("FCT_STD_CPD_024",'Unable to run preferred_smiles');
	if (!is_file('STD/step4_molecule_std.smi'))														failProcess("FCT_STD_CPD_025",'Unable to find step4_molecule_std.smi');

	if ($WITH_PUBLIC)

	{	
		addLog("Push to public schema:".$GLB_VAR['PUBLIC_SCHEMA']);
		$DBIDS=array();
		$FILES=array();
		$STATS=array();
		$MAP_COUNTERIONS=processCounterion($GLB_VAR['PUBLIC_SCHEMA'],'STD/counterion_std.smi','STD/counterion_rejected.smi');
		
		processCompounds($GLB_VAR['PUBLIC_SCHEMA'],$MAP_COUNTERIONS,$WITH_MOL_ENTITY);
	}
	$W_PRIVATE=($GLB_VAR['PRIVATE_ENABLED']=='T');
	if ($W_PRIVATE)
	{
		$DBIDS=array();
		$FILES=array();
		$STATS=array();
		$MAP_COUNTERIONS=processCounterion($GLB_VAR['SCHEMA_PRIVATE'],'STD/counterion_std.smi','STD/counterion_rejected.smi');
		processCompounds($GLB_VAR['SCHEMA_PRIVATE'],$MAP_COUNTERIONS,$WITH_MOL_ENTITY);
	}

}

function processCompounds($SCHEMA,&$MAP_COUNTERIONS,$WITH_MOL_ENTITY=false)
{
	global $SOURCE_ID;
	global $DBIDS;
	global $FILES;
	global $STATS;
	global $JOB_ID; 
	
	
	addLog("\t\tProcessing compounds to database");
	/// Those are the tables we are going to insert into and which files we are opening for batch insert
	$FILES=array('sm_molecule'	=>fopen('INSERT/sm_molecule.csv','w'),
				'sm_entry'		=>fopen('INSERT/sm_entry.csv','w'),
				'sm_source'		=>fopen('INSERT/sm_source.csv','w'));
				
	foreach ($FILES as $N=>$F)if (!$F)													failProcess("FCT_PROCESS_CPD_001",'Unable to open '.$N.'.csv');
	
	/// Then for each table, we get the max primary key for faster insertions
	foreach (array_keys($FILES) as $P)
	{
		$res=runQuery("SELECT MAX(".$P."_id) CO FROM ".$SCHEMA.".".$P);
		if ($res===false)																failProcess("FCT_PROCESS_CPD_002",'Unable to get max id from '.$P);
		if (count($res)==0)$DBIDS[$P]=0;else $DBIDS[$P]=$res[0]['co'];
		
	}
	
	$RECORD=array();
	$STATS=array('INI_CPD'=>0,'NEW_NAME'=>0,'VALID_NAME'=>0,'NEW_SMILES'=>0,'NEW_COUNTERION'=>0,'NEW_ENTRY'=>0);

	/// Now we start with standardize compounds
	$fp=fopen('STD/step4_molecule_std.smi','r');if (!$fp)										failProcess("FCT_PROCESS_CPD_003",'Unable to open molecule_std.smi');
	

///// STRUCTURE: 	Molecule_smiles(s) [SPACE] ID | Inchi(s) | InchiKey(s) | Counterion | FULL_SMILES(s) | FULL_SMILES_STD_SUCCESS
	//fseek($fp,12932810);
	while(!feof($fp))
	{
		/// Get the line and the corresponding info
		$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
		$tab=explode(" " ,$line);
		//echo $line."\n";
		$tab2=explode("|",$tab[1]);
		//print_R($tab2);
		$SMI=$tab[0];
		$NAME=$tab2[0];
		$INCHI=$tab2[1];
		$INCHI_KEY=$tab2[2];
		$COUNTERION=($tab2[3]=='NULL')?'':$tab2[3];
		$COUNTERION_ID=($tab2[3]=='NULL')?'NULL':$MAP_COUNTERIONS[$COUNTERION][0];
		$COUNTERION_STD=($tab2[3]=='NULL')?'':$MAP_COUNTERIONS[$COUNTERION][1];
		;
		$FULL_SMILES=$tab2[4];
		$FULL_VALID=($tab2[5]=='T')?true:false;

		
		$MD5=md5($INCHI.'_'.$INCHI_KEY.'_'.$SMI.'_'.$COUNTERION_STD);
		/// We use a HASH made of the inchi, inchi key, smiles, counterions so it uniquely represent a molecule
		$str="'".$MD5."'";
		
		

		/// And we insert that into the record table
		if (!isset($RECORD[$str])) 
		$RECORD[$str]=array(
			'SMILES'		=>$SMI,
			'INCHI'			=>$INCHI,
			'KEY'			=>$INCHI_KEY,
			'COUNTERION'	=>$COUNTERION_STD,
			'COUNTERION_ID'	=>$COUNTERION_ID,
			'NAME'			=>array($NAME=>-1),
			'DBID'			=>-1,
			'FULL_SMILES'	=>$FULL_SMILES,
			'FULL_VALID'	=>$FULL_VALID,
			'VALID'=>true);
		/// Sometimes (Rarely), hte same hash can be representied by  two molecular identifiers
		else $RECORD[$str]['NAME'][$NAME]=-1;
		$STATS['INI_CPD']++;
		
		if ($STATS['INI_CPD']%5000==0)addLog("\t\t\tProcessed ".$STATS['INI_CPD']." compounds\n");
		
		if (count($RECORD)<5000)continue;
		/// then we insert
		/// Error code from 26 to 43
		processCompoundRecord($RECORD,$JOB_ID,26,$SCHEMA,$WITH_MOL_ENTITY);
		
		$RECORD=array();
		addLog("\t\t\tFILE POS:".ftell($fp));
		//exit;
		
	}
	fclose($fp);
	///Wrap up by inserting the last records
	/// Error code from 44 to 60
	if ($RECORD!=array())processCompoundRecord($RECORD,$JOB_ID,44,$SCHEMA,$WITH_MOL_ENTITY);
	
	$RECORD=array();
	/// Proceed to process the rejected records
	if (checkFileExist('STD/step4_molecule_wrong.smi')){
		addLog("\t\tProcess Rejected records");
		$fp=fopen('STD/step4_molecule_wrong.smi','r');if (!$fp)											failProcess("FCT_PROCESS_CPD_004",'Unable to open molecule_wrong.smi');
		
			
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000000,"\n");if ($line=='')continue;
			$tab=explode(" " ,$line);
			$tab2=explode("|",$tab[1]);
			$SMI=$tab[0];
			$NAME=$tab2[0];
			$INCHI=$tab2[1];
			$INCHI_KEY=$tab2[2];
			$COUNTERION=($tab2[3]=='NULL')?'':$tab2[3];
			$COUNTERION_ID=($tab2[3]=='NULL')?'NULL':$MAP_COUNTERIONS[$COUNTERION][0];
			$COUNTERION_STD=($tab2[3]=='NULL')?'':$MAP_COUNTERIONS[$COUNTERION][1];
			$FULL_SMILES=$tab2[4];
			$FULL_VALID=($tab2[5]=='T')?true:false;
			

			$str="'".md5($INCHI.'_'.$INCHI_KEY.'_'.$SMI.'_'.$COUNTERION_STD)."'";
			/// Note here that VALID is false, meaning the SMILES couldn't be standardized
			if (!isset($RECORD[$str]))
			$RECORD[$str]=array(
				'SMILES'		=>$SMI,
				'INCHI'			=>$INCHI,
				'KEY'			=>$INCHI_KEY,
				'COUNTERION'	=>$COUNTERION_STD,
				'COUNTERION_ID'	=>$COUNTERION_ID,
				'NAME'			=>array($NAME=>-1),
				'DBID'			=>-1,
				'FULL_SMILES'	=>$FULL_SMILES,
				'FULL_VALID'	=>$FULL_VALID,
				'VALID'=>false);
			else $RECORD[$str]['NAME'][$NAME]=-1;
			$STATS['INI_CPD']++;
			
			if ($STATS['INI_CPD']%5000==0)addLog("\t\t\t".$STATS['INI_CPD']);
			
			if (count($RECORD)<50000)continue;
			/// Error code from 62 to 69
			processCompoundRecord($RECORD,$JOB_ID,62,$SCHEMA,$WITH_MOL_ENTITY);
			$RECORD=array();
			addLog("\t\t\tFILE POS:".ftell($fp));
		}
		fclose($fp);
		
		/// Erroro code from 70 to 88
		if ($RECORD!=array())processCompoundRecord($RECORD,$JOB_ID,70,$SCHEMA,$WITH_MOL_ENTITY);
	}
}
	


function processCompoundRecord(&$RECORD,$JOB_ID,$START_ERROR_ID,$SCHEMA,$WITH_MOL_ENTITY=false)
{
		
	global $GLB_VAR;
	global $DB_INFO;
	global $STATS;
	global $SOURCE_ID;
	global $JOB_ID;

	$HAS_NEW_ENTRY=false;
	$HAS_NEW_COUNTER=false;
	$HAS_NEW_MOLECULE=false;
	$HAS_NEW_SOURCE=false;
	$DEBUG=false;
		
	


	if ($RECORD==array())return;

	$DBIDS=array
	('sm_molecule'=>-1,
	'sm_entry'=>-1,
	'sm_source'=>-1);



/// So first, we are going to get the max Primary key values for each of those tables for faster insert.
/// FILE_STATUS will tell us for each file if we need to trigger the data insertion or  not
$FILE_STATUS=array();
/// FILES will be the file handlers for each of the files we are going to insert into
$FILES=array();
foreach ($DBIDS as $TBL=>&$POS)
{
	$query='SELECT MAX('.$TBL.'_id) CO FROM '.$SCHEMA.'.'.$TBL;
	$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."010",'Unable to run query '.$query);
	$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
	$FILE_STATUS[$TBL]=0;
	$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');if (!$FILES[$TBL])				failProcess($JOB_ID."005",'Unable to open file '.$TBL.'.csv');
}


	$MOL_ENTITIES=array();

	addLog("\t\t\tProcessing batch to db");
	// Searching all hash
	$query="SELECT md5_hash, se.sm_entry_id, sm_name, smiles,sm_source_id, inchi,inchi_key, source_id, sm.sm_molecule_id, se.sm_counterion_id
		FROM ".$SCHEMA.".sm_molecule SM
		LEFT JOIN ".$SCHEMA.".sm_entry SE ON SM.sm_molecule_id = SE.sm_molecule_id
		LEFT JOIN ".$SCHEMA.".sm_source SS ON SE.sm_entry_id =SS.sm_entry_id
		WHERE md5_hash IN (".implode(',',array_keys($RECORD)).')';

	$VALID_SOURCE_ID=array();

	$res=runQuery($query);if ($res===false) failProcess("FCT_CPD_RECORD_001","unable to retrieve records from hash");
	if ($DEBUG)
	{
		echo "MD5 Search results:\n";
		print_r($res);
	}
	
	foreach ($res as $line)
	{
		$DB_MD5=$line['md5_hash'];
		
		if (!isset($RECORD["'".$DB_MD5."'"]))continue;
		
		$ENTRY=&$RECORD["'".$DB_MD5."'"];
		if ($DEBUG)echo $DB_MD5."\t".$line['sm_name']."\t".$SOURCE_ID."::".$line['source_id']."\n";
		if ($line['sm_counterion_id']=='')$line['sm_counterion_id']='NULL';
		if ($line['smiles']			  !=$ENTRY['SMILES'])		failProcess("FCT_CPD_RECORD_002","Integrity compromised - different smiles. Input:".$ENTRY['SMILES'].' DB :'.$line['SMILES'].' HASH: '.$DB_MD5);
		if ($line['inchi']			  !=$ENTRY['INCHI'])		failProcess("FCT_CPD_RECORD_003","Integrity compromised - different inchi. Input:".$ENTRY['INCHI'].' DB :'.$line['INCHI'].' HASH: '.$DB_MD5);
		if ($line['inchi_key']		  !=$ENTRY['KEY'])			failProcess("FCT_CPD_RECORD_004","Integrity compromised - different inchi key. Input:".$ENTRY['KEY'].' DB :'.$line['KEY'].' HASH: '.$DB_MD5);
		if ($line['sm_counterion_id']!=$ENTRY['COUNTERION_ID'])	failProcess("FCT_CPD_RECORD_005","Integrity compromised - different counterion. Input:".$ENTRY['COUNTERION_ID'].' DB :'.$line['sm_counterion_id'].' HASH: '.$DB_MD5);
		
		$ENTRY['DBID']=$line['sm_entry_id'];
		
		foreach ($ENTRY['NAME'] as $NAME=>&$ID)
		{
			if ($NAME==$line['sm_name'] && $SOURCE_ID==$line['source_id'])
			{
				$ID=$line['sm_source_id'];
				$VALID_SOURCE_ID[]=$line['sm_source_id'];
				$STATS['VALID_NAME']++;
				unset($ENTRY['NAME'][$NAME]);
			}

		}
		if (count($ENTRY['NAME'])==0)
		{
			if ($WITH_MOL_ENTITY)
			{


				$MOL_ENTITY=array(
					'STRUCTURE_HASH'=>$DB_MD5,
					'STRUCTURE'=>$ENTRY['FULL_SMILES'],
					'DB_ID'=>-1,
					'COMPONENT'=>array(
						array(
							'STRUCTURE_HASH'=>$DB_MD5,
							'STRUCTURE'=>$ENTRY['FULL_SMILES'],
							'MOLAR_FRACTION'=>1,
							'DB_ID'=>-1,
							'MOLECULE'=>
							array(
								array(
								'MOL_TYPE'=>'SM',
								'ID'=>$ENTRY['DBID'],
								'HASH'=>$DB_MD5,
								'TYPE'=>'SIN',
								'MOLAR_FRACTION'=>1,
								'DB_ID'=>-1)
							)
						)
				));
				$MOL_ENTITIES[]=$MOL_ENTITY;
			}
			unset($RECORD["'".$DB_MD5."'"]);
		}

	}
	
	if ($DEBUG)
	{
		echo "RECORD AFTER PROCESSING:\n";
		print_r($RECORD);
		exit;
	}
	
	if ($VALID_SOURCE_ID!=array()
		&& !runQueryNoRes("UPDATE ".$SCHEMA.".SM_SOURCE SET sm_name_status='T' WHERE sm_source_id IN (".implode(',',$VALID_SOURCE_ID).')'))
																	failProcess("FCT_CPD_RECORD_006","Unable to update sm_source");
	
	//// We validated all the sm source existing. Now we need to process those missing
	/// Starting at the basic level, checking if the SMILES is already in the database
	/// and whether the counterion in also in the database
	$MISSING_SMILES=array();

	//print_r($COUNTERIONS);
	
	foreach ($RECORD as $MD5_HASH=>&$ENTRY)
	{
		if ($ENTRY['DBID']==-1)$MISSING_SMILES["'".$ENTRY['SMILES']."'"][]=$MD5_HASH;
		if ($ENTRY['COUNTERION_ID']=='')$ENTRY['COUNTERION_ID']='NULL';
	}
	
	if ($MISSING_SMILES!=array())
	{
		$res=runQuery("SELECT SMILES, SM_MOLECULE_ID FROM ".$SCHEMA.".SM_MOLECULE WHERE SMILES IN (".implode(',',array_keys($MISSING_SMILES)).')');
		if ($res===false) failProcess("FCT_CPD_RECORD_007","Unable to get SMILES");


		foreach ($res as $line)
		{
			foreach ($MISSING_SMILES["'".$line['smiles']."'"] as $MD5_HASH)
			{
				$ENTRY=&$RECORD[$MD5_HASH];
				$ENTRY['SM_MOLECULE_ID']=$line['sm_molecule_id'];
			}
			unset($MISSING_SMILES["'".$line['smiles']."'"]);
		}	
		/// Now we insert new smiles.
		foreach ($MISSING_SMILES as $SMI=>&$LIST_RECORD)
		{
			$IS_VALID=true;
			foreach ($LIST_RECORD as $MD5_HASH) if (!$RECORD[$MD5_HASH]['VALID'])$IS_VALID=false;
			++$DBIDS['sm_molecule'];
			$STATS['NEW_SMILES']++;
			$HAS_NEW_MOLECULE=true;
			fputs($FILES['sm_molecule'],$DBIDS['sm_molecule']."\t".substr($SMI,1,-1)."\t".($IS_VALID?"T":"F")."\n");
			foreach ($LIST_RECORD as $MD5_HASH)
			{
				$ENTRY=&$RECORD[$MD5_HASH];
				$ENTRY['SM_MOLECULE_ID']=$DBIDS['sm_molecule'];
			}
		}
	}
	

	
	

	/// For those remaining records that need to be inserted, we need to check if the name already exist, 
	/// This would mean the structure of the compound has been updated.
	$NEW_COMPOUND_NAMES=array();
	foreach ($RECORD as $MD5_HASH=>&$ENTRY)
	{
		foreach ($ENTRY['NAME'] as $NAME=>$ID)$NEW_COMPOUND_NAMES["'".$NAME."'"][]=$MD5_HASH;
	}
	if ($NEW_COMPOUND_NAMES!=array())
	{
		$query="SELECT md5_hash, se.sm_entry_id, sm_name, smiles,sm_source_id, inchi,inchi_key, source_id, sm.sm_molecule_id, se.sm_counterion_id
				FROM ".$SCHEMA.".sm_molecule SM
				LEFT JOIN ".$SCHEMA.".sm_entry SE ON SM.sm_molecule_id = SE.sm_molecule_id
				LEFT JOIN ".$SCHEMA.".sm_source SS ON SE.sm_entry_id =SS.sm_entry_id
				WHERE SM_NAME IN (".implode(',',array_keys($NEW_COMPOUND_NAMES)).') AND source_id='.$SOURCE_ID;
				
		$res=runQuery($query);
		if ($res===false ) failProcess("FCT_CPD_RECORD_008","Unable to get names");
		$query='DELETE FROM sm_source where sm_source_id IN (';
		foreach ($res as &$line)
		{
			$SM_NAME=$line['sm_name'];
			echo "####\n";
			echo "DBV\t".$line['sm_name']."\t".$line['smiles']."\t".$line['inchi']."\t".$line['inchi_key']."\t".$line['sm_counterion_id']."\t".$line['md5_hash']."\n";
			foreach ($NEW_COMPOUND_NAMES["'".$SM_NAME."'"] as $MD5_HASH)
			{
				$ENTRY=&$RECORD[$MD5_HASH];
				echo "ENT\tN/A\t".$ENTRY['SMILES']."\t".$ENTRY['INCHI']."\t".$ENTRY['KEY']."\t".$ENTRY['COUNTERION_ID']."\t".$MD5_HASH."\n";
			}
			$query.=$line['sm_source_id'].',';

			
		}
		if (count($res)>0)
		{
			echo $query."\n";
			
			if (!runQueryNoRes(substr($query,0,-1).')')) failProcess("FCT_CPD_RECORD_009","Unable to delete sm_source records");
		}
	}

	/// Then for those new records, we need to check if the pair sm_molecule_Id, sm_counterion_id is already in the system or not.
	/// If we find a match, then the difference can be in the Inchi and Inchi-Key.
	/// But, because the md5_hash is a combination of molecule,counterion,inchi and inchi-key, there shouldn't be a match
	/// However, it can help us in debugging
	$NEW_COMPOUND_IDS=array();
	$NEW_COMPOUND_IDS_NOCO=array();
	foreach ($RECORD as $MD5_HASH=>&$ENTRY)
	{
		if ($ENTRY['DBID']!=-1)continue;
		if (!isset($ENTRY['COUNTERION_ID'])){print_r($ENTRY);echo $MD5_HASH."\n";}
		if ($ENTRY['COUNTERION_ID']=='NULL')	$NEW_COMPOUND_IDS_NOCO[$ENTRY['SM_MOLECULE_ID']][]=$MD5_HASH;
		else 									$NEW_COMPOUND_IDS["(".$ENTRY['SM_MOLECULE_ID'].','.$ENTRY['COUNTERION_ID'].')'][]=$MD5_HASH;
	}
	//print_r($RECORD);
	$query='SELECT SM_MOLECULE_ID, SM_COUNTERION_ID,SM_ENTRY_ID,INCHI,INCHI_KEY FROM '.$SCHEMA.'.SM_ENTRY WHERE ';
	if ($NEW_COMPOUND_IDS!=array())$query.= '( (SM_MOLECULE_ID,SM_COUNTERION_ID) IN ('.implode(',',array_keys($NEW_COMPOUND_IDS)).')) OR ';
	if ($NEW_COMPOUND_IDS_NOCO!=array())$query.= ' ((SM_MOLECULE_ID) IN ('.implode(',',array_keys($NEW_COMPOUND_IDS_NOCO)).') AND SM_COUNTERION_ID IS NULL) OR ';
	
	if ($NEW_COMPOUND_IDS!=array() || $NEW_COMPOUND_IDS_NOCO!=array())
	{
		
		$query=substr($query,0,-4);
		$res=runQuery($query); if ($res===false)failProcess("FCT_CPD_RECORD_010","Unable to get sm_entries");

			// echo $query."\n";
			// print_r($res);

		foreach ($res as $line)
		{
			if (isset($NEW_COMPOUND_IDS["(".$line['sm_molecule_id'].','.$line['sm_counterion_id']]))
			foreach ($NEW_COMPOUND_IDS["(".$line['sm_molecule_id'].','.$line['sm_counterion_id']] as $MD5_HASH)
			{
				$ENTRY=&$RECORD[$MD5_HASH];
			//	echo "#####\n".$ENTRY['INCHI']."\n".$line['inchi']."\n".$ENTRY['KEY']."\n".$line['inchi_key']."\n";

				if ($ENTRY['INCHI']==$line['inchi'] && $ENTRY['KEY']==$line['inchi_key'])
				{
				//	echo "\t\tFOUND IDENTITY\tISSUE\n";
					$ENTRY['DBID']=$line['sm_entry_id'];
				}
				
			}
			if (isset($NEW_COMPOUND_IDS_NOCO[$line['sm_molecule_id']]))
			foreach ($NEW_COMPOUND_IDS_NOCO[$line['sm_molecule_id']] as $MD5_HASH)
			{
				$ENTRY=&$RECORD[$MD5_HASH];
				//echo "#####\n".$ENTRY['INCHI']."\n".$line['inchi']."\n".$ENTRY['KEY']."\n".$line['inchi_key']."\n";
				if ($ENTRY['INCHI']==$line['inchi'] && $ENTRY['KEY']==$line['inchi_key'])
				{
					$ENTRY['DBID']=$line['sm_entry_id'];
					//echo "\t\tFOUND IDENTITY\tISSUE\n";
				}
				
			}
		}
	}

	foreach ($RECORD as $MD5_HASH => &$ENTRY)
	{
		if ($ENTRY['DBID']==-1)
		{
			++$DBIDS['sm_entry'];
			$ENTRY['DBID']=$DBIDS['sm_entry'];
			$STATS['NEW_ENTRY']++;
			$HAS_NEW_ENTRY=true;
			
			if ($DEBUG)	echo "\tCREATE ENTRY\t".$DBIDS['sm_entry']."\n";
			fputs($FILES['sm_entry'],$DBIDS['sm_entry']."\t".$ENTRY['INCHI']."\t".$ENTRY['KEY']."\t".$ENTRY['COUNTERION_ID']."\t".$ENTRY['SM_MOLECULE_ID']."\t".substr($MD5_HASH,1,-1)."\t".$ENTRY['FULL_VALID']."\t".$ENTRY['FULL_SMILES']."\n");
			foreach ($ENTRY['NAME'] as $NAME=>&$ID)
			{
				$STATS['NEW_NAME']++;
				$DBIDS['sm_source']++;
				$ID=$DBIDS['sm_source'];
				$HAS_NEW_SOURCE=true;
				
				if ($DEBUG)echo "\tNEW_NAME\t".$DBIDS['sm_source']."\t|".$SOURCE_ID."\t".$NAME."\tT\n";
				fputs($FILES['sm_source'],$DBIDS['sm_source']."\t".$ENTRY['DBID']."\t".$SOURCE_ID."\t".$NAME."\tT\n");	
			}
		}
		else
		{
			foreach ($ENTRY['NAME'] as $NAME=>&$ID)
			{
				if ($ID!=-1)continue;
				$STATS['NEW_NAME']++;
				$DBIDS['sm_source']++;
				$ID=$DBIDS['sm_source'];
				$HAS_NEW_SOURCE=true;
				
				
				if ($DEBUG)echo "\tNEW_NAME\t".$DBIDS['sm_source']."\t|".$SOURCE_ID."\t".$NAME."\tT\n";
				fputs($FILES['sm_source'],$DBIDS['sm_source']."\t".$ENTRY['DBID']."\t".$SOURCE_ID."\t".$NAME."\tT\n");	
			}
		}


		if (!$WITH_MOL_ENTITY)continue;


		$MOL_ENTITY=array(
			'STRUCTURE_HASH'=>str_replace("'","",$MD5_HASH),
			'STRUCTURE'=>$ENTRY['FULL_SMILES'],
			'DB_ID'=>-1,
			'COMPONENT'=>array(
				array(
					'STRUCTURE_HASH'=>str_replace("'","",$MD5_HASH),
					'STRUCTURE'=>$ENTRY['FULL_SMILES'],
					'MOLAR_FRACTION'=>1,
					'DB_ID'=>-1,
					'MOLECULE'=>
					array(
						array(
						'MOL_TYPE'=>'SM',
						'ID'=>$ENTRY['DBID'],
						'HASH'=>str_replace("'","",$MD5_HASH),
						'TYPE'=>'SIN',
						'MOLAR_FRACTION'=>1,
						'DB_ID'=>-1)
					)
				)
		));
		$MOL_ENTITIES[]=$MOL_ENTITY;
	}

	//print_R($MOL_ENTITIES);




	fclose($FILES['sm_source']);
	fclose($FILES['sm_molecule']);
	fclose($FILES['sm_entry']);

		
	if ($HAS_NEW_MOLECULE){
	$command='\COPY '.$SCHEMA.'.sm_molecule(sm_molecule_id,smiles,is_valid) FROM \''."INSERT/sm_molecule.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess("FCT_CPD_RECORD_011",'Unable to insert sm_molecule'); 
	}
	if ($HAS_NEW_ENTRY)
	{
	$command='\COPY '.$SCHEMA.'.sm_entry(sm_entry_id,inchi,inchi_key,sm_counterion_id,sm_molecule_id,md5_hash,is_valid,full_smiles) FROM \''."INSERT/sm_entry.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess("FCT_CPD_RECORD_012",'Unable to insert sm_entry'); 
	}
	if ($HAS_NEW_SOURCE)
	{
	$command='\COPY '.$SCHEMA.'.sm_source (sm_source_id,sm_entry_id,source_id,sm_name,sm_name_status) FROM \''."INSERT/sm_source.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess("FCT_CPD_RECORD_013",'Unable to insert sm_source'); 
	}
		
	if ($WITH_MOL_ENTITY) createMolEntities($MOL_ENTITIES,$SCHEMA);

	$FILES['sm_source']		=fopen('INSERT/sm_source.csv','w');		if (!$FILES['sm_source'])failProcess("FCT_CPD_RECORD_014",'Unable to open sm_source'); 
	$FILES['sm_molecule']	=fopen('INSERT/sm_molecule.csv','w');	if (!$FILES['sm_molecule'])failProcess("FCT_CPD_RECORD_015",'Unable to open sm_molecule'); 
	$FILES['sm_entry']		=fopen('INSERT/sm_entry.csv','w');		if (!$FILES['sm_entry'])failProcess("FCT_CPD_RECORD_016",'Unable to open sm_entry'); 
		
}

function createMolEntities(&$MOL_ENTITIES,$SCHEMA)
{
	if ($MOL_ENTITIES==array())return;
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	
	global $STATS;
	global $SOURCE_ID;

	
	$DBIDS=array
		('molecular_entity'=>-1,
		'molecular_component'=>-1,
		'molecular_component_sm_map'=>-1,
		'molecular_component_na_map'=>-1,
		'molecular_component_conj_map'=>-1,
		'molecular_entity_component_map'=>-1);

	// This will contain the order of the columns for the COPY command
	$COL_ORDER=array(
		'molecular_entity'=>'(molecular_entity_id,molecular_entity_hash,molecular_structure_hash,molecular_components,molecular_structure)',
		'molecular_component'=>'( molecular_component_id,molecular_component_hash,molecular_component_structure_hash,molecular_component_structure,components,ontology_entry_id)',
		'molecular_component_sm_map'=> '(molecular_component_sm_map_id,molecular_component_id,sm_entry_id,molar_fraction,compound_type)',
		'molecular_component_na_map'=> '(molecular_component_na_map_id,molecular_component_id,nucleic_acid_seq_id,molar_fraction)',
		'molecular_component_conj_map'=> '(molecular_component_conj_map_id,molecular_component_id,conjugate_entry_id,molar_fraction)',
		'molecular_entity_component_map'=>'(molecular_entity_component_map_id,molecular_entity_id,molecular_component_id,molar_fraction)'
		);

	addLog("\t\t\tPreparing files and Id");
	/// So first, we are going to get the max Primary key values for each of those tables for faster insert.
	/// FILE_STATUS will tell us for each file if we need to trigger the data insertion or  not
	$FILE_STATUS=array();
	/// FILES will be the file handlers for each of the files we are going to insert into
	$FILES=array();
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$query='SELECT MAX('.$TBL.'_id) CO FROM '.$SCHEMA.'.'.$TBL;
		$res=array();$res=runQuery($query);if ($res===false)							failProcess($JOB_ID."010",'Unable to run query '.$query);
		$DBIDS[$TBL]=(count($res)>0)?$res[0]['co']:1;
		$FILE_STATUS[$TBL]=0;
		$FILES[$TBL]=fopen('INSERT/'.$TBL.'.csv','w');if (!$FILES[$TBL])				failProcess($JOB_ID."005",'Unable to open file '.$TBL.'.csv');
	}
	
	/// STEP1 => Generating the hash for each of the molecular entity
	foreach ($MOL_ENTITIES as $ENTITY_POS=>&$MOL_ENTITY)
	{
		$STR_ENTITY='';
		foreach ($MOL_ENTITY['COMPONENT'] as $COMPONENT_POS=>&$MOL_COMPONENT)
		{
			$STR_COMPONENT='';
			$MOL_COMPONENT['MAP_ID']=-1;
			foreach ($MOL_COMPONENT['MOLECULE'] as $MOL_POS=>&$MOLECULE)
			{
				$STR_COMPONENT.=$MOLECULE['HASH'].':'.$MOLECULE['MOLAR_FRACTION'].'|';
			}
			
			$MOL_COMPONENT['HASH']=md5(substr($STR_COMPONENT,0,-1));
			$STR_ENTITY.=$MOL_COMPONENT['HASH'].':'.$MOL_COMPONENT['MOLAR_FRACTION'].'|';
		}
		$MOL_ENTITY['HASH']=md5(substr($STR_ENTITY,0,-1));
	}
	$MOL_ENTITIES_MAP=array();
	$MOL_COMPONENTS_MAP=array();
	foreach ($MOL_ENTITIES as $ENTITY_POS=>&$MOL_ENTITY)
	{
		
		$MOL_ENTITIES_MAP["'".$MOL_ENTITY['HASH']."'"][]=$ENTITY_POS;
		foreach ($MOL_ENTITY['COMPONENT'] as $COMPONENT_POS=>&$MOL_COMPONENT)
		{
			$MOL_COMPONENTS_MAP["'".$MOL_COMPONENT['HASH']."'"][]=array($ENTITY_POS,$COMPONENT_POS);
		}

	}

	if ($MOL_ENTITIES_MAP!=array())
	{
		addLog("\t\t\tSearching molecular entity");
		$res=runQuery("SELECT molecular_entity_id, molecular_entity_hash FROM ".$SCHEMA.".molecular_entity WHERE molecular_entity_hash IN (".implode(',',array_keys($MOL_ENTITIES_MAP)).')');
		if ($res===false)failProcess("FCT_CREATE_MOL_ENT_001","Unable to get molecular_entity");
		foreach ($res as $line)
		{
			foreach ($MOL_ENTITIES_MAP["'".$line['molecular_entity_hash']."'"] as $ENTITY_POS)
			{
				$MOL_ENTITY=&$MOL_ENTITIES[$ENTITY_POS];
				$MOL_ENTITY['DB_ID']=$line['molecular_entity_id'];
			}
		}
	}
	
	

	if ($MOL_COMPONENTS_MAP !=array())
	{
		addLog("\t\t\tSearching molecular component");
		$res=runQuery("SELECT molecular_component_id, molecular_component_hash FROM ".$SCHEMA.".molecular_component WHERE molecular_component_hash IN (".implode(',',array_keys($MOL_COMPONENTS_MAP)).')');
		if ($res===false)failProcess("FCT_CREATE_MOL_ENT_002","Unable to get mol_component");
		foreach ($res as $line)
		{
			foreach ($MOL_COMPONENTS_MAP["'".$line['molecular_component_hash']."'"] as $COMPONENT_POS_INFO)
			{
				$MOL_ENTITY=&$MOL_ENTITIES[$COMPONENT_POS_INFO[0]];
				$MOL_COMPONENT=&$MOL_ENTITY['COMPONENT'][$COMPONENT_POS_INFO[1]];
				$MOL_COMPONENT['DB_ID']=$line['molecular_component_id'];
			}
		}
	
	}

	$NA_MAP=array();
	$CO_MAP=array();
	$SM_MAP=array();
	$CONJ_MAP=array();
	foreach ($MOL_ENTITIES as $ENTITY_POS=>&$MOL_ENTITY)
	{
		
		foreach ($MOL_ENTITY['COMPONENT'] as $COMPONENT_POS=>&$MOL_COMPONENT)
		{
			if ($MOL_COMPONENT['DB_ID']==-1)continue;
			if ($MOL_ENTITY['DB_ID']!=-1)
			{
				$CO_MAP["(".$MOL_ENTITY['DB_ID'].','.$MOL_COMPONENT['DB_ID'].')'][]=array($ENTITY_POS,$COMPONENT_POS);
			}
			foreach ($MOL_COMPONENT['MOLECULE'] as $MOL_POS=>&$MOLECULE_ENTRY)
			{
				if ($MOLECULE_ENTRY['MOL_TYPE']=='SM')
				{
					$SM_MAP["(".$MOL_COMPONENT['DB_ID'].','.$MOLECULE_ENTRY['ID'].')'][]=array($ENTITY_POS,$COMPONENT_POS,$MOL_POS);
				}
				if ($MOLECULE_ENTRY['MOL_TYPE']=='CONJ')
				{
					$CONJ_MAP["(".$MOL_COMPONENT['DB_ID'].','.$MOLECULE_ENTRY['ID'].')'][]=array($ENTITY_POS,$COMPONENT_POS,$MOL_POS);
				}
				if ($MOLECULE_ENTRY['MOL_TYPE']=='NA')
				{
					$NA_MAP["(".$MOL_COMPONENT['DB_ID'].','.$MOLECULE_ENTRY['ID'].')'][]=array($ENTITY_POS,$COMPONENT_POS,$MOL_POS);
				}
			}
		}
	}
	if ($CO_MAP!=array())
	{
		addLog("\t\t\tSearching molecular entity/components");
		$CHUNKS=array_chunk(array_keys($CO_MAP),1000);
		foreach ($CHUNKS as $CHUNK)
		{
			$res=runQuery("SELECT * FROM ".$SCHEMA.".molecular_entity_component_map WHERE (molecular_entity_id,molecular_component_id) IN (".implode(',',$CHUNK).')');
			foreach ($res as $line)
			{
				foreach ($CO_MAP["(".$line['molecular_entity_id'].','.$line['molecular_component_id'].')'] as $POS_INFO)
				{
					$MOL_ENTITY=&$MOL_ENTITIES[$POS_INFO[0]];
					$MOL_COMPONENT=&$MOL_ENTITY['COMPONENT'][$POS_INFO[1]];
					$MOL_COMPONENT['MAP_ID']=$line['molecular_entity_component_map_id'];
				}
			}
		}
	}
	if ($SM_MAP!=array())
	{
		addLog("\t\t\tSearching molecular component/small molecule");
		$CHUNKS=array_chunk(array_keys($SM_MAP),1000);
		foreach ($CHUNKS as $CHUNK)
		{
			$res=runQuery("SELECT * FROM ".$SCHEMA.".molecular_component_sm_map WHERE (molecular_component_id,sm_entry_id) IN (".implode(',',$CHUNK).')');
			
			foreach ($res as $line)
			{
				foreach ($SM_MAP["(".$line['molecular_component_id'].','.$line['sm_entry_id'].')'] as $POS_INFO)
				{
					$MOL_ENTITY=&$MOL_ENTITIES[$POS_INFO[0]];
					$MOL_COMPONENT=&$MOL_ENTITY['COMPONENT'][$POS_INFO[1]];
					$MOL_COMPONENT['MOLECULE'][$POS_INFO[2]]['DB_ID']=$line['molecular_component_sm_map_id'];
				}
			}
		}
	}
	if ($NA_MAP!=array())
	{
		addLog("\t\t\tSearching molecular component/nucleic acid");
		$CHUNKS=array_chunk(array_keys($NA_MAP),1000);
		foreach ($CHUNKS as $CHUNK)
		{
			$res=runQuery("SELECT * FROM ".$SCHEMA.".molecular_component_na_map WHERE (molecular_component_id,nucleic_acid_seq_id) IN (".implode(',',$CHUNK).')');
			
			foreach ($res as $line)
			{
				foreach ($NA_MAP["(".$line['molecular_component_id'].','.$line['nucleic_acid_seq_id'].')'] as $POS_INFO)
				{
					$MOL_ENTITY=&$MOL_ENTITIES[$POS_INFO[0]];
					$MOL_COMPONENT=&$MOL_ENTITY['COMPONENT'][$POS_INFO[1]];
					$MOL_COMPONENT['MOLECULE'][$POS_INFO[2]]['DB_ID']=$line['molecular_component_na_map_id'];
				}
			}
		}
	}

	if ($CONJ_MAP!=array())
	{
		addLog("\t\t\tSearching molecular components/conjugates");
		$CHUNKS=array_chunk(array_keys($CONJ_MAP),1000);
		foreach ($CHUNKS as $CHUNK)
		{
			$res=runQuery("SELECT * FROM ".$SCHEMA.".molecular_component_conj_map WHERE (molecular_component_id,conjugate_entry_id) IN (".implode(',',$CHUNK).')');
			
			foreach ($res as $line)
			{
				foreach ($CONJ_MAP["(".$line['molecular_component_id'].','.$line['conjugate_entry_id'].')'] as $POS_INFO)
				{
					$MOL_ENTITY=&$MOL_ENTITIES[$POS_INFO[0]];
					$MOL_COMPONENT=&$MOL_ENTITY['COMPONENT'][$POS_INFO[1]];
					$MOL_COMPONENT['MOLECULE'][$POS_INFO[2]]['DB_ID']=$line['molecular_component_conj_map_id'];
				}
			}
		}
	}
	addLog("\t\t\tPushing to files");
	foreach ($MOL_ENTITIES as $ENTITY_POS=>&$MOL_ENT)
	{
		if ($MOL_ENT['DB_ID']==-1)
		{
			++$DBIDS['molecular_entity'];
			$MOL_ENT['DB_ID']=$DBIDS['molecular_entity'];
			$FILE_STATUS['molecular_entity']=1;
			$STATS['NEW_MOL_ENTITY']++;
			$LIST_HASH=array();
			foreach ($MOL_ENT['COMPONENT'] as &$COMPONENT)$LIST_HASH[]=$COMPONENT['HASH'];
			sort($LIST_HASH);
			
			fputs($FILES['molecular_entity'],$DBIDS['molecular_entity']."\t".$MOL_ENT['HASH']."\t".$MOL_ENT['STRUCTURE_HASH']."\t".implode("|",$LIST_HASH)."\t".$MOL_ENT['STRUCTURE']."\n");
		}
		foreach ($MOL_ENT['COMPONENT'] as $COMPONENT_POS=>&$MOL_COMPONENT)
		{

			if ($MOL_COMPONENT['DB_ID']==-1)
			{
			++$DBIDS['molecular_component'];
			$MOL_COMPONENT['DB_ID']=$DBIDS['molecular_component'];
			$FILE_STATUS['molecular_component']=1;
			$STATS['NEW_MOL_COMPONENT']++;
			$LIST_HASH=array();
			foreach ($MOL_COMPONENT['MOLECULE'] as &$MOL)$LIST_HASH[]=$MOL['HASH'];
			sort($LIST_HASH);
			fputs($FILES['molecular_component'],$DBIDS['molecular_component']."\t".$MOL_COMPONENT['HASH']."\t".$MOL_COMPONENT['STRUCTURE_HASH']."\t".$MOL_COMPONENT['STRUCTURE']."\t".implode("|",$LIST_HASH)."\tNULL\n");
			}
			if ($MOL_COMPONENT['MAP_ID']==-1)
			{
				++$DBIDS['molecular_entity_component_map'];
				$FILE_STATUS['molecular_entity_component_map']=1;
				$STATS['NEW_MOL_ENTITY_COMPONENT_MAP']++;
				fputs($FILES['molecular_entity_component_map'],$DBIDS['molecular_entity_component_map']."\t".$MOL_ENT['DB_ID']."\t".$MOL_COMPONENT['DB_ID']."\t".$MOL_COMPONENT['MOLAR_FRACTION']."\n");
			}
			foreach ($MOL_COMPONENT['MOLECULE'] as &$MOL_INFO)
			{
				
				if ($MOL_INFO['DB_ID']!=-1)continue;
				if ($MOL_INFO['MOL_TYPE']=='SM')
				{
					$FILE_STATUS['molecular_component_sm_map']=1;
					++$DBIDS['molecular_component_sm_map'];				
					$STATS['NEW_MOL_COMPONENT_SM_MAP']++;
					fputs($FILES['molecular_component_sm_map'],$DBIDS['molecular_component_sm_map']."\t".$MOL_COMPONENT['DB_ID']."\t".$MOL_INFO['ID']."\t".$MOL_INFO['MOLAR_FRACTION']."\tSIN\n");
					
				}
				if ($MOL_INFO['MOL_TYPE']=='CONJ')
				{
					$FILE_STATUS['molecular_component_conj_map']=1;
					++$DBIDS['molecular_component_conj_map'];				
					$STATS['NEW_MOL_COMPONENT_CONJ_MAP']++;
					fputs($FILES['molecular_component_conj_map'],$DBIDS['molecular_component_conj_map']."\t".$MOL_COMPONENT['DB_ID']."\t".$MOL_INFO['ID']."\t".$MOL_INFO['MOLAR_FRACTION']."\n");
					
				}
				if ($MOL_INFO['MOL_TYPE']=='NA')
				{
					$FILE_STATUS['molecular_component_na_map']=1;
					++$DBIDS['molecular_component_na_map'];				
					$STATS['NEW_MOL_COMPONENT_NA_MAP']++;
					fputs($FILES['molecular_component_na_map'],$DBIDS['molecular_component_na_map']."\t".$MOL_COMPONENT['DB_ID']."\t".$MOL_INFO['ID']."\t".$MOL_INFO['MOLAR_FRACTION']."\n");
					
				}
				
			}
		}
	
	}


	global $GLB_VAR;
	global $DBIDS;
	global $JOB_ID;
	global $DB_INFO;


	/// We are going to insert all the records that have been marked for insertion
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
		// If no records have been written to the file we don't need to insert it
		if (!$FILE_STATUS[$NAME]){echo "SKIPPING ".$NAME."\t";continue;}
		
		// We close the file handler
		fclose($FILES[$NAME]);

		// Preparing the COPY command
		$command='\COPY '.$SCHEMA.'.'.$NAME.' '.$CTL.' FROM \'INSERT/'.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		
		echo $NAME."\t".$FILE_STATUS[$NAME]."\t";
		$res=array();
	
		// We run the command
		exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
		if ($return_code !=0 )	 failProcess($JOB_ID."008",'Unable to insert data into '.$NAME.' '.print_r($res,true));
	}




}


function loadCompoundSMI(&$DB_REC,$SMI)
{
	global $SOURCE_ID;
	$query="select se.sm_entry_Id, sm_name, smiles,sm_source_id,source_id, sm.sm_molecule_id, sc.sm_counterion_id, sc.counterion_smiles
	
	FROM sm_molecule SM
	LEFT JOIN sm_entry SE ON SM.sm_molecule_id = SE.sm_molecule_id
	LEFT JOIN sm_source SS ON SE.sm_Entry_Id =SS.sm_Entry_Id
	LEFT JOIN sm_counterion SC ON SC.sm_counterion_Id = SE.sm_counterion_id
	WHERE   smiles = '".$SMI."'";
	$res=runQuery($query);
	//	print_r($res);
	if ($res===false)																	failProcess("FCT_LOAD_CPD_001",'Unable to run query');
	foreach ($res as $line)
	{
		$DB_REC[$line['smiles']]['ID']=$line['sm_molecule_id'];
		if ($line['sm_entry_id']!='')$DB_REC[$line['smiles']]['CI'][$line['counterion_smiles']]['ID']=$line['sm_entry_id'];
		if ($line['source_id']!=$SOURCE_ID)continue;
		if ($line['sm_entry_id']!='')$DB_REC[$line['smiles']]['CI'][$line['counterion_smiles']][$line['sm_name']]=$line['sm_source_id'];

	}
}

function updateStat($table,$name,$expected,$JOB_ID)
{
	$res=runQuery("SELECT count(*) CO FROM ".$table);
	if ($res===false)failProcess("FCT_UPD_STAT_001",'Unable to get the number of '.$name); 
	 $CO=$res[0]['co'];

	if ($expected!=$CO)failProcess("FCT_UPD_STAT_002",'Different number of '.$name.'. Expected:'.$expected.' ; In database: '.$CO); 

	 $res=runQuery("SELECT n_record FROM GLB_STAT WHERE concept_name = '".$name."'");

	 if ($res===false)failProcess("FCT_UPD_STAT_003",'Unable to get GLB_STAT number'); 
	 if ($res==array())
	 {
		 echo "INSERT INTO GLB_STAT (concept_name,n_record) VALUES ('".$name."',".$CO.")";
		if (!runQueryNoRes("INSERT INTO GLB_STAT (concept_name,n_record) VALUES ('".$name."',".$CO.")"))failProcess("FCT_UPD_STAT_004",'Unable to insert the number of '.$name); 
	 }
	 else 
	 {
		 if (!runQueryNoRes("UPDATE GLB_STAT SET n_record = ".$CO." WHERE concept_name='".$name."'"))failProcess("FCT_UPD_STAT_005",'Unable to update the number of '.$name); 
	 }
}



function prepareTranslationTable()
{
	global $TG_DIR;
	global $GLB_VAR;
	global $JOB_INFO;
	global $JOB_ID;
	/// Translation table is used to translate codon to amino acid
	/// This function will check if the translation table is up to date and if not, it will update it
	addLog("\tGetting existing translation table from database");	

	/// Getting the existing translation table from the database
	$res=runQuery("SELECT * FROM translation_tbl");
	if ($res===false) 															failProcess($JOB_ID."FCT_TRANS_TBL_001",'Unable to retrieve translation table');
	$TR_TBL=array();
	$MAX_TR_TBL_ID=0;
	foreach ($res as $line)
	{
		/// Group by translation table primary key
		$TR_TBL[$line['translation_tbl_id']]=
			array(
				'NAME'=>$line['translation_tbl_name'],
				'DB_STATUS'=>'FROM_DB');

		/// Maximum translation table primary key value for future insertion
		$MAX_TR_TBL_ID=max($MAX_TR_TBL_ID,$line['translation_tbl_id']);
	}


	/// Getting the existing codon table got all translation tables:
	$res=runQuery("SELECT codon_id, translation_tbl_id, codon_name, aa_name FROM codon ");
	if ($res===false) 															failProcess($JOB_ID."FCT_TRANS_TBL_002",'Unable to retrieve codon table');
	$MAX_CODON_ID=0;
	foreach ($res as $line)
	{
		/// Group by translation table primary key => then codon name and its information
		$TR_TBL[$line['translation_tbl_id']]['CODON'][$line['codon_name']]=array($line['aa_name'],$line['codon_id'],'DB_STATUS'=>'FROM_DB');

		/// Maximum codon primary key value for future insertion
		$MAX_CODON_ID=max($MAX_CODON_ID,$line['codon_id']);
	}

	

	addLog("\tLoading translation table from static file");
	
	$TRANS_TABLE=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$JOB_INFO['DIR'].'/TRANS_TABLE';
	if (!checkFileExist($TRANS_TABLE))																failProcess($JOB_ID."FCT_TRANS_TBL_003",'Unable to find TRANS_TABLE in genome static dir');


	/// Loading the static file that contains the translation table downloaded from https://ftp.ncbi.nih.gov/entrez/misc/data/gc.prt
	/// Format look like this:
	///START   Standard
	// FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG
	// ---M------**--*----M---------------M----------------------------
	// TTTTTTTTTTTTTTTTCCCCCCCCCCCCCCCCAAAAAAAAAAAAAAAAGGGGGGGGGGGGGGGG
	// TTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGG
	// TCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAG
	// END

	$fp=fopen($TRANS_TABLE,'r'); if(!$fp)															failProcess($JOB_ID."FCT_TRANS_TBL_004",'Unable to open TRANS_TABLE in genome static dir');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");if ($line=='')continue;

		/// Looking for the start of a translation table
		if (substr($line,0,5)!='START')continue;
		$tab=explode("\t",$line); if (count($tab)!=2)												failProcess($JOB_ID."FCT_TRANS_TBL_005",'START block must have 2 columns');
		$name=$tab[1];
		$CURR_DBID=null;
		
		/// We assume that the translation table name is unique and if it exists in the DB, then it's valid
		foreach ($TR_TBL as $TBL_ID=>&$TBL_INFO)
		{
			if ($TBL_INFO['NAME']!=$name)continue;
			$CURR_DBID=$TBL_ID;
			$TR_TBL[$CURR_DBID]['DB_STATUS']='VALID';
			break;
		}

		/// If not, we create it
		if ($CURR_DBID==null)
		{
			++$MAX_TR_TBL_ID;
			$TR_TBL[$MAX_TR_TBL_ID]=array('NAME'=>$name,'DB_STATUS'=>'TO_INS');


			if (!runQueryNoRes('INSERT INTO translation_tbl (translation_tbl_id,translation_tbl_name) 
					VALUES ('.$MAX_TR_TBL_ID.",'".$name."')"))									failProcess($JOB_ID."FCT_TRANS_TBL_006",'Unable to insert '.$name);
			
			$CURR_DBID=$MAX_TR_TBL_ID;
		}


		/// Then we retrieve the translation table
		$AA_LINE=stream_get_line($fp,200,"\n");	// FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG
		$MOD_LINE=stream_get_line($fp,200,"\n");// ---M------**--*----M---------------M----------------------------
		$COD1_LINE=stream_get_line($fp,200,"\n");// TTTTTTTTTTTTTTTTCCCCCCCCCCCCCCCCAAAAAAAAAAAAAAAAGGGGGGGGGGGGGGGG
		$COD2_LINE=stream_get_line($fp,200,"\n");// TTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGGTTTTCCCCAAAAGGGG
		$COD3_LINE=stream_get_line($fp,200,"\n");// TCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAGTCAG
		$END_LINE=stream_get_line($fp,100,"\n");// END


		/// And we insert the codon if it does not exist
		for ($I=0;$I<strlen($AA_LINE);++$I)
		{
			$AA_NAME=substr($AA_LINE,$I,1);
			$CODON=substr($COD1_LINE,$I,1).substr($COD2_LINE,$I,1).substr($COD3_LINE,$I,1);
			if (!isset($TR_TBL[$CURR_DBID]['CODON'][$CODON]))
			{
				++$MAX_CODON_ID;
				$query='INSERT INTO codon (codon_id, translation_tbl_id, codon_name,aa_name) VALUES ('.$MAX_CODON_ID.",".$CURR_DBID.",'".$CODON."','".$AA_NAME."')";

				if (!runQueryNoRes($query))															failProcess($JOB_ID."FCT_TRANS_TBL_007",'Unable to insert codon '.$CODON);
			}
		}
		if (substr($END_LINE,0,3)!='END')															failProcess($JOB_ID."FCT_TRANS_TBL_008",'END block not found');
	}
	fclose($fp);
}





function convertGFFLine($str)
{
	$raw_info=explode(";",$str);
	$info=array();
	foreach ($raw_info as $i)
	{
		$pos=strpos($i,"=");
		$head=substr($i,0,$pos);
		if ($head=='Dbxref')
		{
			$gene_infoX=explode(",",substr($i,$pos+1));
			$dbxr=array();
			foreach ($gene_infoX as $v)
			{
				$pos=strpos($v,":");
				$dbxr[substr($v,0,$pos)]=substr($v,$pos+1);
			}
			$info[$head]=$dbxr;
		}
		else $info[$head]=substr($i,$pos+1);
	}
	return $info;
}


function loadBLOSSUMMAtrix($IS_DNA=false,$BLS='')
{
	global $TG_DIR;
	global $GLB_VAR;
	$PATH=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/XRAY/';
	if ($BLS=='')
	{
		if (!$IS_DNA)$PATH.='EBLOSUM62';
		else $PATH.='DNAFULL';
	}else $PATH.=$BLS;
	if (!checkFileExist($PATH))failProcess("FCT_LOAD_BLOSSUM_001",'Unable to find '.$PATH);
	$fp=fopen($PATH,'r');
	if (!$fp)failProcess("FCT_LOAD_BLOSSUM_002",'Unable to open '.$PATH);
	
	$MATRIX=array();
	$is_first=true;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		
		if ($line==''||$line[0]=='#')continue;
		if ($is_first)
		{
			$head=array_values(array_filter(explode(" ",$line)));
			
			$is_first=false;
			continue;
		}
		/// array_filter will remove empty values, but also 0
		/// So we replace 0 by A
		$tab=array_values(array_filter(explode(" ",str_replace("0","A",$line))));
		for ($i=1;$i<count($tab);++$i)
		{
			/// We replace A by 0
			if ($tab[$i]=='A')$tab[$i]=0;
			$MATRIX[$tab[0]][$head[$i-1]]=$tab[$i];
		
		}
	}
	fclose($fp);
	return $MATRIX;

}



class seq_mat
{
	private array $mat;
	private $REF_LEN;
	private $COMP_LEN;
	 private $map=array('score'=>0,'move'=>1,'x'=>2,'y'=>3,'x_next'=>4,'y_next'=>5);
	
	 function __construct($REF_LEN,$COMP_LEN)
	{
		$mat = new SplFixedArray($REF_LEN*$COMP_LEN);
		$this->REF_LEN=$REF_LEN;
		$this->COMP_LEN=$COMP_LEN;
		for ($X=0;$X<$REF_LEN;++$X)
		{
			for ($Y=0;$Y<$COMP_LEN;++$Y)
			{
				$this->mat[$X*$COMP_LEN+$Y]='0|4|-1|-1|-1|-1';
			}
		}
	}
	function __destruct()
	{
		$mat=null;
	}

	public function get($X,$Y,$TYPE)
	{
		
		$TPL=explode('|',$this->mat[$X*$this->COMP_LEN+$Y]);
		return $TPL[$this->map[$TYPE]];
	}

	public function set($X,$Y,$TYPE,$VALUE)
	{
		$TPL=explode('|',$this->mat[$X*$this->COMP_LEN+$Y]);
		$TPL[$this->map[$TYPE]]=$VALUE;
		$this->mat[$X*$this->COMP_LEN+$Y]=implode('|',$TPL);
	}
	public function setAll($X,$Y,$STR)
	{
		$this->mat[$X*$this->COMP_LEN+$Y]=$STR;
	
	}
}

function runSeqAlign(&$REF_SEQ,&$COMP_SEQ,&$MATRIX)
{
	// echo "RUNNING SEQ ALIGN\n";
	// echo $REF_SEQ."\n".$COMP_SEQ."\n";
	if (!defined('undefined')) define('undefined',0);
	if (!defined('diagonal'))define('diagonal',1);
	if (!defined('left'))define('left',2);
	if (!defined('above'))define('above',3);
	if (!defined('end'))define('end',4);

	echo "START:".memory_get_usage()."\n";
	$ExtendGapPenalty=-1;
    $GapOpenPenalty=-10;
    $threshold=0;


	$REF_SEQ='-'.$REF_SEQ;
	$COMP_SEQ='-'.$COMP_SEQ;

	$REF_LEN=strlen($REF_SEQ);
	$COMP_LEN=strlen($COMP_SEQ);

	/// Initialize matrix:
	$score_mat= new seq_mat($REF_LEN,$COMP_LEN);

	$BestMove=array(0,0);

	for ($Y=1;$Y<$COMP_LEN;++$Y)
	{
		$LETTER_COMP=$COMP_SEQ[$Y];
		for ($X=1;$X<$REF_LEN;++$X)
		{
			
			$LETTER_REF=$REF_SEQ[$X];
			$l_gap_penalty=$GapOpenPenalty;
			$u_gap_penalty=$GapOpenPenalty;

			if ( $score_mat->get($X,$Y-1,'move') == above ) $u_gap_penalty = $ExtendGapPenalty;
            if ( $score_mat->get(($X-1),$Y,'move') == left  ) $l_gap_penalty =$ExtendGapPenalty;
            
			
			$u_gap = $score_mat->get($X,($Y-1),'score') + $u_gap_penalty;
            
			
			$l_gap = $score_mat->get($X-1,$Y,'score') + $l_gap_penalty;
           
			$mm    = $score_mat->get(($X-1),$Y-1,'score')+ $MATRIX[$LETTER_REF][$LETTER_COMP];
			
			
			if ( $mm > $l_gap && $mm > $u_gap && $mm >= $threshold ) {
				//if ( $mm >= $l_gap && $mm >= $u_gap && $mm >= threshold ) {
				//std::cout << "came from diagonal with a score of " << $mm << std::endl;
				$score_mat->setAll($X,$Y,$mm.'|'.diagonal.'|'.$X.'|'.$Y.'|'.($X-1).'|'.($Y-1));
				
			} else if ( $l_gap >= $mm && $l_gap >= $u_gap && $l_gap >= $threshold ) {
				//std::cout << "came from left with a score of " << $l_gap << std::endl;

				$score_mat->setAll($X,$Y,$l_gap.'|'.left.'|'.$X.'|'.$Y.'|'.($X-1).'|'.$Y);

			} else if ( $u_gap >= $mm && $u_gap >= $l_gap && $u_gap >= $threshold ) {

				//std::cout << "came from above with a score of " << $u_gap << std::endl;
				$score_mat->setAll($X,$Y,$u_gap.'|'.above.'|'.$X.'|'.$Y.'|'.$X.'|'.($Y-1));

			} else {
				$score_mat->set($X,$Y,'x',$X);
				$score_mat->set($X,$Y,'y',$Y);
				$score_mat->set($X,$Y,'score',$threshold);
				$score_mat->set($X,$Y,'move',end);
			}

			if ( $score_mat->get($X,$Y,'score') > $score_mat->get($BestMove[0],$BestMove[1],'score') ) 
			{
				$BestMove=array($X,$Y);
			}
		}
	}


		// traceback
		$current_cell = $BestMove;
		$aligned_seq_x="";
		$aligned_seq_y="";
		$MAPPING=array();
		while ( 1 ) 
		{
			$current_x = $current_cell[0];
			$current_y = $current_cell[1];
			$current_move=$score_mat->get($current_x,$current_y,'move');
			if ($current_move  == diagonal ) {
	//            std::cout << " came from diagonal from score of " << current_x<<" " <<current_y << std::endl;
				$aligned_seq_x = $REF_SEQ[$current_x] . $aligned_seq_x;
				$aligned_seq_y = $COMP_SEQ[$current_y] .$aligned_seq_y;
				$MAPPING[]=array($current_x-1,$current_y-1);
				
	
			} else if ($current_move == left ) {
	//            std::cout << " came from left from score of " << current_x <<" /" << std::endl;
				$aligned_seq_x = $REF_SEQ[$current_x] .$aligned_seq_x;
				$aligned_seq_y = '-'. $aligned_seq_y;
				$MAPPING[]=array($current_x-1,-1);
				//            seq_y->insert_gap( current_y + 1 );
				// std::cout << "seq_x[" << current_x << "] = " << seq_x[current_x] << std::endl;
			} else if ( $current_move == above ) {
	//            std::cout << " came from above from score of " << "/ "<<current_y << std::endl;
				$aligned_seq_x = '-'. $aligned_seq_x;
				$aligned_seq_y = $COMP_SEQ[$current_y] . $aligned_seq_y;
				$MAPPING[]=array(-1,$current_y-1);
				
				//            seq_x->insert_gap( current_x + 1 );
			} else {
				
			}
			if ($score_mat->get($score_mat->get($current_x,$current_y,'x_next'),
							   $score_mat->get($current_x,$current_y,'y_next'),
							   'move')==end)break;
	
	
			$current_cell = array($score_mat->get($current_x,$current_y,'x_next'),
								  $score_mat->get($current_x,$current_y,'y_next'));
			//std::cout << aligned_seq_x << std::endl << aligned_seq_y << std::endl << std::endl;
		} // while ( current_cell->next() != 0 )
	
		//std::cout << std::endl << (*seq_x) << std::endl << (*seq_y) << std::endl;
		//std::cout << matrix << std::endl;
		//    std::cout << aligned_seq_x<<std::endl<<aligned_seq_y<<std::endl;
		$REF_SEQ=substr($REF_SEQ,1);
		$COMP_SEQ=substr($COMP_SEQ,1);
		// echo "\n";
		// echo $aligned_seq_x."\n";
		// echo $aligned_seq_y."\n";
		krsort($MAPPING);
		$MAPPING=array_values($MAPPING);
		$RESULTS=array('MAPPING'=>$MAPPING,
		'IDENTITY'=>0,'IDENTITY_COMMON'=>0,'SIMILARITY'=>0,'SIMILARITY_COMMON'=>0,'GAP_R'=>0,'GAP_C'=>0);
		if ($MAPPING==array())return $RESULTS;
		$REF_LEN--;
		$COMP_LEN--;
		$LEN_ALL=count($MAPPING);

		$START=&$MAPPING[0];
		$END=&$MAPPING[$LEN_ALL-1];
		$LEN_ALL+=$START[0]+$START[1]+$REF_LEN-$END[0]-1+$COMP_LEN-$END[1]-1;
		// print_R($START);
		// print_R($END);
		// echo $REF_LEN.' '.$COMP_LEN.' '.$LEN_ALL."\n";
		
		/// Compute score
		foreach ($MAPPING as &$K)
		{
			if ($K[0]==-1)
			{
				$RESULTS['GAP_R']++;
				continue;
			}
			if ($K[1]==-1)
			{
				$RESULTS['GAP_C']++;
				continue;
			}
			//echo $REF_SEQ[$K[0]].' '.$COMP_SEQ[$K[1]].' '.$MATRIX[$REF_SEQ[$K[0]]][$COMP_SEQ[$K[1]]]."\n";
			if ($REF_SEQ[$K[0]]==$COMP_SEQ[$K[1]])
			{
				
				$RESULTS['IDENTITY_COMMON']++;
				$RESULTS['SIMILARITY_COMMON']++;
			}
			else if ($MATRIX[$REF_SEQ[$K[0]]][$COMP_SEQ[$K[1]]]>0)
			{
				
				//echo $REF_SEQ[$K[0]].' '.$COMP_SEQ[$K[1]].' '.$MATRIX[$REF_SEQ[$K[0]]][$COMP_SEQ[$K[1]]]."\n";
				$RESULTS['SIMILARITY_COMMON']++;
			}
		}
		$RESULTS['IDENTITY']=round($RESULTS['IDENTITY_COMMON']/$LEN_ALL,5);
		$RESULTS['SIMILARITY']=round($RESULTS['SIMILARITY_COMMON']/$LEN_ALL,5);
		$RESULTS['IDENTITY_COMMON']=round($RESULTS['IDENTITY_COMMON']/count($MAPPING),5);
		$RESULTS['SIMILARITY_COMMON']=round($RESULTS['SIMILARITY_COMMON']/count($MAPPING),5);
	
		$score_mat=null;
	
		$MAPPING=null;
		unset($score_mat);unset($MAPPING);
		
		echo "\n".memory_get_usage()."\n";
		gc_collect_cycles();
		echo "=> END:".memory_get_usage() .";".memory_get_peak_usage()."\n";
		
		return $RESULTS;
}


function queryPubmed($PUB_QUERY,$MIN_DATE,$MAX_DATE,$LEVEL,$IN_FILE)
{
	global $JOB_ID;
	global $GLB_VAR;
	$CURR_RES=array('SUCCESS'=>false,'LIST'=>array());
	try
	{
		
		$T1=min($MIN_DATE,$MAX_DATE);
		$T2=max($MIN_DATE,$MAX_DATE);
		$MED=floor(($T2+$T1)/2);
		
		$date1 = date_create(date('Y/m/d',$T1));
		$date2 = date_create(date('Y/m/d',$T2));

		//difference between two dates
		$diff = date_diff($date1,$date2)->format("%a");

		//count days
		echo 'Days Count - '.$diff."\n";

		/// If the API key is set, we use it
		$API_INFO='';
		if ($GLB_VAR['PUBMED_API_ID']!='N/A')$API_INFO='&api_key='.$GLB_VAR['PUBMED_API_ID'];
		
		/// Building the URL
		$URL='https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term='.$PUB_QUERY.'&retmax=10000&retmode=json'.$API_INFO;
		$URL.='&mindate='.date('Y/m/d',$T1).'&maxdate='.date('Y/m/d',$T2);
		
		/// Remove the previous result file
		if (is_file($IN_FILE) && !unlink($IN_FILE))failProcess($JOB_ID."FCT_QUERY_PUBMED_001",'Unable to delete temp '.$IN_FILE.' file');
		
		/// Query pubmed:
		if (dl_file($URL,3,$IN_FILE)===false)return $CURR_RES;

		/// Convert the result to an array
		$res=array();
		$res=json_decode(file_get_contents($IN_FILE),true);
		
		if ($res===false || !isset($res['esearchresult']['count']))
		{
			echo "ISSUE - ".$PUB_QUERY."\n";
			print_R($res);
		}
		else 
		{
			//echo $res['esearchresult']['querytranslation']."\n";
			
			for ($I=0;$I<$LEVEL;++$I)echo "\t";
			echo date('Y/m/d',$T1).'-'.date('Y/m/d',$T2).'='.$res['esearchresult']['count']."\n";
			$EXPECTED=$res['esearchresult']['count'];
			
			/// There is a limit of 10K per results. Thus if we have more than 10K, we need to split the query by date and re-run the subsequent queries
			if ($res['esearchresult']['count']>10000)
			{
				/// More than 1 day difference between the two dates
				if ($diff>1)
				{
					/// So we use the median date. Here from start to median
					$NEW_RES=queryPubmed($PUB_QUERY,$T1,$MED,$LEVEL+1,$IN_FILE);
					if ($NEW_RES['SUCCESS']==false)return $CURR_RES;
					
					foreach ($NEW_RES['LIST'] as $K=>$V)$CURR_RES['LIST'][$K]=false;

					/// Then median to end
					$NEW_RES=queryPubmed($PUB_QUERY,$MED,$T2,$LEVEL+1,$IN_FILE);
					
					if ($NEW_RES['SUCCESS']==false)return $CURR_RES;
					
					foreach ($NEW_RES['LIST'] as $K=>$V)$CURR_RES['LIST'][$K]=false;
				}
				else if ($diff==1)
				{
					/// If it's just one day, we split the query in two
					/// The first half
					$NEW_RES=queryPubmed($PUB_QUERY,$T1,$T1,$LEVEL+1,$IN_FILE);
					if ($NEW_RES['SUCCESS']==false)return $CURR_RES;

					foreach ($NEW_RES['LIST'] as $K=>$V)$CURR_RES['LIST'][$K]=false;
					
					/// The second half
					$NEW_RES=queryPubmed($PUB_QUERY,$T2,$T2,$LEVEL+1,$IN_FILE);
					if ($NEW_RES['SUCCESS']==false)return $CURR_RES;
					
					foreach ($NEW_RES['LIST'] as $K=>$V)$CURR_RES['LIST'][$K]=false;
				}else 
				{
					foreach ($res['esearchresult']['idlist'] as $ID)$CURR_RES['LIST'][$ID]=false;
				}

			}
			else{
				foreach ($res['esearchresult']['idlist'] as $ID)$CURR_RES['LIST'][$ID]=false;
			}
			
		}
		/// We expect at the end to have the same number as the count provided.
		if (count($CURR_RES['LIST'])==$EXPECTED)
		{
			$CURR_RES['SUCCESS']=true;
			return $CURR_RES;
		}
	}
	catch(Exception $e)
	{
		failProcess($JOB_ID."FCT_QUERY_PUBMED_002",$e->getMessage());
	}
	for ($I=0;$I<$LEVEL;++$I)echo "\t";
	echo "DIFFERENT RESULTS : ".count($CURR_RES['LIST']).'/'.$EXPECTED."\n";
	return $CURR_RES;
}


function pushFilesToDB($LAST_CALL=false)
{
	global $COL_ORDER;
	global $FILES;
	global $GLB_VAR;
	global $DB_INFO;
	global $FILE_STATUS;
	global $JOB_ID;
	foreach ($COL_ORDER as $NAME=>$CTL)
	{
		// If no records have been written to the file we don't need to insert it
		if (!$FILE_STATUS[$NAME]){echo "SKIPPING ".$NAME."\n";continue;}
		$FILE_STATUS[$NAME]=false;
		
		//	if (in_array($NAME,$TO_FILTER))continue;
		echo $NAME."\n";

	
		addLog("inserting ".$NAME." records");
		$res=array();
		fclose($FILES[$NAME]);
		
		

		$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		
		
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code!=0)																failProcess($JOB_ID."FCT_PUSH_TO_DB_001",'Unable to insert '.$NAME);
		
		if ($LAST_CALL)continue;

		$FILES[$NAME]=fopen($NAME.'.csv','w');
		if (!$FILES[$NAME])																failProcess($JOB_ID."FCT_PUSH_TO_DB_002",'Unable to open '.$NAME.'.csv');
		
	}
}



?>
