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
 

addLog("Create directory");
	
	$CURR_DATE=getCurrDate();
	$PROCESS_CONTROL['DIR']=$CURR_DATE;
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'];   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	
	$ARCHIVE=$W_DIR.'/ARCHIVE';
	
	if (!is_dir($ARCHIVE) && !mkdir($ARCHIVE)) 											failProcess($JOB_ID."003",'Unable to create '.$ARCHIVE.' directory');
	$W_DIR.='/'.$CURR_DATE.'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."004",'Unable to find and create '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	$PRD_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'];
	if (!is_dir($PRD_DIR))			 													failProcess($JOB_ID."006",'Unable to find '.$PRD_DIR.' directory');
	
	  echo $W_DIR."\n";
	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/ONTOLOGY/';	if (!is_dir($STATIC_DIR))		failProcess($JOB_ID."007",'ONTOLOGY static dir not found '.$STATIC_DIR);
	
	$RECORDS=array();
	$res=runQuery("SELECT OE.ONTOLOGY_ENTRY_ID, MIN(ONTOLOGY_LEVEL) M,ONTOLOGY_TAG FROM  ONTOLOGY_HIERARCHY OH, ONTOLOGY_ENTRY OE 
	WHERE OE.ONTOLOGY_ENTRY_ID = OH.ONTOLOGY_ENTRY_ID GROUP BY ONTOLOGY_TAG, OE.ONTOLOGY_ENTRY_ID ORDER BY MIN(ONTOLOGY_LEVEL) ASC ");
	$LEVEL=array();
	foreach ($res as $line)
	{
		$RECORDS[$line['ONTOLOGY_ENTRY_ID']]=array(false,$line['ONTOLOGY_TAG']);
		$LEVEL[$line['M']][]=$line['ONTOLOGY_ENTRY_ID'];
	}
	ksort($LEVEL);
	
$fp=fopen('EXAMPLE_2','w');

	foreach ($LEVEL as $LV=>&$LIST)
	{
		
		$CHUNKS=array_chunk($LIST,1000);
		foreach ($CHUNKS as $CHUNK)
		{
			$res=runQuery("SELECT ONTOLOGY_ENTRY_ID,
			ONTOLOGY_TAG,
			ONTOLOGY_NAME,
			ONTOLOGY_DEFINITION FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_ENTRY_ID IN (".implode(",",$CHUNK).')');
			$T=array();
			foreach ($res as $line)	
			{
					$T[$line['ONTOLOGY_ENTRY_ID']]=$line;
					$RECORDS[$line['ONTOLOGY_ENTRY_ID']][0]=true;
			}
			
			$res=runQuery("SELECT ONTOLOGY_ENTRY_ID, ONTOLOGY_SYN_ID,SYN_TYPE,SYN_VALUE FROM ONTOLOGY_SYN WHERE ONTOLOGY_ENTRY_ID IN (".implode(",",$CHUNK).')');
			foreach ($res as $line)		$T[$line['ONTOLOGY_ENTRY_ID']]['SYN'][]=$line;
			
			$res=runQuery("select PAR.ONTOLOGY_ENTRY_ID as PAR_ID, OH.ONTOLOGY_ENTRY_ID
			FROM ONTOLOGY_HIERARCHY OH, ONTOLOGY_HIERARCHY PAR 
			WHERE 
			PAR.ONTOLOGY_LEVEL = OH.ONTOLOGY_LEVEL -1
			AND PAR.ONTOLOGY_LEVEL_LEFT <=OH.ONTOLOGY_LEVEL_LEFT AND 
			PAR.ONTOLOGY_LEVEL_RIGHT >= OH.ONTOLOGY_LEVEL_RIGHT AND
			OH.ONTOLOGY_ENTRY_ID IN (".implode(",",$CHUNK).')');
			foreach ($res as $line)	
			{
				// if ($RECORDS[$line['PAR_ID']][0]==false)
				// {
				// 	echo $line['ONTOLOGY_ENTRY_ID'].' '.$line['PAR_ID']."\n";
				// 	die("Entrie not processed yet");
				// }
				$T[$line['ONTOLOGY_ENTRY_ID']]['PAR'][]=$RECORDS[$line['PAR_ID']][1];
			}

			foreach ($T as $E)
			{
				
				$S="\nSTART\t".$E['ONTOLOGY_TAG']."\n".
					"NAME\t".$E['ONTOLOGY_NAME']."\n".
					"CR_NAME\tDESAPHY Jeremy\n".
					"CR_DATE\t2021/08/20\n".
					"DESCRIP\t".$E['ONTOLOGY_DEFINITION']."\n";
					if (isset($E['SYN']))foreach ($E['SYN'] as $P)$S.="SYNONYM\t".$P['SYN_VALUE']."\n";
					if (isset($E['PAR']))foreach ($E['PAR'] as $P)$S.="CHILDOF\t".$P."\n";
				fputs($fp,$S."\nEND\n");
			}

		}

	}
	
	// START	LSO_0000363
	// NAME	130 West Maxwell Boulevard - Montgomery - AL
	// SYNONYM	130 W Maxwell Blvd
	// SYNONYM	130 West Maxwell Boulevard
	// CR_NAME	DESAPHY	Jeremy
	// CR_DATE	2021/08/20
	// DESCRIP	130 West Maxwell Boulevard in Montgomery, Alabama
	// CHILDOF	LSO_0000362
	// END
	
?>

