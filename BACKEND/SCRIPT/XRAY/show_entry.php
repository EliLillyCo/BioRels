<?php
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
require_once("xray_functions.php");
$PDB_ID=strtoupper($argv[1]);

$CK_INFO=$GLB_TREE[getJobIDByName('dl_xray')];
$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 						failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR))					 	failProcess($JOB_ID."002",'Unable to find and create '.$U_DIR);

$PDB_DIR=$U_DIR.'ENTRIES/'.substr($PDB_ID,1,2).'/'.$PDB_ID;	
if (!is_dir($PDB_DIR)){echo "NO DIRECTORY \n".$PDB_DIR."\n";exit;}
if (!is_file($PDB_DIR.'/_data')){echo "NO _data file ".$PDB_DIR.'_data'."\n";exit;}

$ENTRY_DATA=json_decode(file_get_contents($PDB_DIR.'/_data'),true);
print_r($ENTRY_DATA);





?>

