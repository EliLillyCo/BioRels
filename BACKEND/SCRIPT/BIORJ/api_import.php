<?php

ini_set('memory_limit','5000M');


/// Setting up directory:
$TG_DIR= getenv('TG_DIR');

if ($TG_DIR===false)  die('NO TG_DIR found');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
if (!is_dir($TG_DIR.'/PROCESS') && !mkdir($TG_DIR.'/PROCESS'))die('TG_DIR/PROCESS can\'t be created');


/// Get library file:
require_once($TG_DIR.'/BACKEND/SCRIPT/BIORJ/api_lib.php');

/// Load additional file to connect to db and processed:
$FILE_TO_LOAD=array(
	'/LIB/global.php'=>0,
	'/LIB/fct_utils.php'=>0,
	'/LIB/loader_process.php'=>0
);

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}



foreach ($FILE_TO_LOAD as $FILE=>$RULE)
{
	if ($RULE==1 && !defined("MONITOR_JOB"))continue;
	$PATH=$TG_DIR.'/BACKEND/SCRIPT/'.$FILE;
	if ((include $PATH)==TRUE)continue;
	sendKillMail('000003','Unable to load file: '.$PATH);
}


date_default_timezone_set($GLB_VAR['TIMEZONE']);


/// Setting up DB connection:
$DB_CONN=null;
$DB_INFO=array();
$GLB_VAR['DB_SCHEMA']=getenv('DB_SCHEMA');
$GLB_VAR['SCHEMA_PRIVATE']=getenv('SCHEMA_PRIVATE');

connectDB();

$DEBUG=false;


if ($argc<2)
{
	echo "Usage: php api_import.php [OPTIONS] INPUT_FILE

		INPUT_FILE must be either the linearize file
		If json_file, use the --JSON option

	OPTIONS:
		-SCHEMA=SCHEMA_NAME : Specify the schema to insert into
	";
	exit(0);
}


/// Processing arguments:
$INPUT_FILE=null;
$SCHEMA=null;
$JSON=false;
foreach ($argv as $argc_id=>$value)
{
	if ($argc_id==0)continue;
	
	if ($value=='--JSON')
	{
		$JSON=true;
		continue;
	}
	/// First character is a '-' => option as OPTION=VALUE
	if (strpos($value,'-')===0)
	{
		$tab=explode('=',$value);
		if (count($tab)!=2)die('Invalid option: '.$value."\nFormat is OPTION=VALUE");
		if ($tab[0]=='-SCHEMA')$SCHEMA=$tab[1];
		else die('Invalid option: '.$tab[0]);
	}
	/// First character is not a '-' => file
	else
	{
		$INPUT_FILE=$value;
	}
}
if ($INPUT_FILE==null)die('No file provided');


$JSON_PARENT_FOREIGN=array(); 
/// Set up schema
if ($SCHEMA!=null)
{
	echo 'SET SCHEMA TO '.$SCHEMA."\n";
	runQueryNoRes("SET SESSION SEARCH_PATH to ".$SCHEMA);
	/// Get foreign key relationships:
	getAllForeignRel(array("'".$SCHEMA."'"));
}
/// Get foreign key relationships:
else 
{
	$SCHEMA=$GLB_VAR['DB_SCHEMA'];
	getAllForeignRel();
}


/// Load API rules:
$HIERARCHY=array();
$KEYS=array();
$BLOCKS=loadAPIRules($TG_DIR,$HIERARCHY,$KEYS);
$PRIMARY_KEYS=get_primary_keys($SCHEMA);
$NOT_NULL=getNotNullCols($SCHEMA);


/// If it's a JSON file, linearize it then process it
if ($JSON)
{
	/// Decode the file
	$JSON_FILE=json_decode(file_get_contents($INPUT_FILE),true);
	if ($JSON_FILE===false)
	{
		die("JSON ERROR:".json_last_error_msg());
	}
	$HIERARCHY=$JSON_FILE['BIORJ_HIERARCHY'];
	unset($JSON_FILE['BIORJ_HIERARCHY']);
	/// Linearize the file into a temp file:
	$fname='tmp_file_biorj'.substr(md5(microtime_float()),0,6);
	json_to_csv($JSON_FILE,$HIERARCHY,$fname);
	
	/// Then process the linearized file:
	process_document($SCHEMA,$fname);	
	// Remove the temp file:
	if (!unlink($fname))die('Data inserted successfully but unable to remove temp file');
}
else process_document($SCHEMA,$INPUT_FILE);


?>
