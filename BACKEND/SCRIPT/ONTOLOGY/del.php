<?php
error_reporting(E_ALL);
ini_set('memory_limit','1000M');
$JOB_NAME='wh_ontology';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false) die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
$res=runQueryNoRes("DELETE FROM VARIANT_FREQUENCY WHERE ALT_COUNT<10") ;
exit;


$FROM=$argv[1];
$TO=$argv[2];
echo $FROM.' '.$TO;
/// Load FROM Entry
$res=runQuery("SELECT ONTOLOGY_ENTRY_ID, ONTOLOGY_NAME FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_NAME='".str_replace("'","''",$FROM)."'");
if (count($res)!=1){echo "UNABLE TO FIND FROM";exit;}
$FROM_ENTRY=$res[0];

$res=runQuery("SELECT * FROM ONTOLOGY_SYN  WHERE ONTOLOGY_ENTRY_ID = ".$FROM_ENTRY['ONTOLOGY_ENTRY_ID']);
$FROM_ENTRY['SYN']=$res;
$res=runQuery("SELECT * FROM ONTOLOGY_HIERARCHY  WHERE ONTOLOGY_ENTRY_ID = ".$FROM_ENTRY['ONTOLOGY_ENTRY_ID']);
$FROM_ENTRY['HIER']=$res;
$res=runQuery("SELECT ONTOLOGY_ENTRY_ID, ONTOLOGY_NAME FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_NAME='".str_replace("'","''",$TO)."'");
if (count($res)!=1){echo "UNABLE TO FIND TO";exit;}
$TO_ENTRY=$res[0];
$res=runQuery("SELECT * FROM ONTOLOGY_SYN  WHERE ONTOLOGY_ENTRY_ID = ".$TO_ENTRY['ONTOLOGY_ENTRY_ID']);
$TO_ENTRY['SYN']=$res;
$res=runQuery("SELECT * FROM ONTOLOGY_HIERARCHY  WHERE ONTOLOGY_ENTRY_ID = ".$TO_ENTRY['ONTOLOGY_ENTRY_ID']);
$TO_ENTRY['HIER']=$res;

if (isset($FROM_ENTRY['SYN']) && $FROM_ENTRY['SYN']!=array()){
	$query ="UPDATE ONTOLOGY_SYN SET ONTOLOGY_ENTRY_ID = ".$TO_ENTRY['ONTOLOGY_ENTRY_ID'].' WHERE ONTOLOGY_SYN_ID IN (';
	foreach ($FROM_ENTRY['SYN'] as $S)$query.=$S['ONTOLOGY_SYN_ID'].',';
	$query=substr($query,0,-1).')';
	if (!runQueryNoRes($query)){echo $query;exit;}
}


if (isset($FROM_ENTRY['HIER']) && $FROM_ENTRY['HIER']!=array()){
	$query ="UPDATE ONTOLOGY_HIERARCHY SET ONTOLOGY_ENTRY_ID = ".$TO_ENTRY['ONTOLOGY_ENTRY_ID'].' WHERE ONTOLOGY_ENTRY_ID = '.$FROM_ENTRY['ONTOLOGY_ENTRY_ID'];
	
	if (!runQueryNoRes($query)){echo $query;exit;}
}
$res=runQuery("SELECT MAX(ONTOLOGY_SYN_ID) S FROM ONTOLOGY_SYN");
$M=$res[0]['S'];
$query='INSERT INTO ONTOLOGY_SYN (ONTOLOGY_SYN_ID,ONTOLOGY_ENTRY_ID,
			SYN_TYPE,
SYN_VALUE,
SOURCE_ID) VALUES ('.($M+1).",".$TO_ENTRY['ONTOLOGY_ENTRY_ID'].",'EXACT','".str_replace("'","''",$FROM_ENTRY['ONTOLOGY_NAME'])."',(SELECT SOURCE_ID FROM SOURCE WHERE SOURCE_NAME='Internal'))";
if (!runQueryNoRes($query)){echo $query;exit;}


$res=runQueryNoRes("DELETE FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_ENTRY_ID = ".$FROM_ENTRY['ONTOLOGY_ENTRY_ID']);
echo "\nSUCCESS\n";



?>

