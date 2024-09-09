<?php

/**
 SCRIPT NAME: db_uniprot_extdb
 PURPOSE:     Refresh UN_EXTDB table, which contains all the external database information from Uniprot
 NOTE:		  This Should probably be linked to the SOurCE table at some point.
 
*/

/// Job name - Do not change
$JOB_NAME='db_uniprot_extdb';

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


addLog("Access directory");
	/// GEt parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_uniprot_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												   if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];



addLog("Static file check");
	if (!checkFileExist('dbxref.txt'))											failProcess($JOB_ID."005",'Unable to find dbxref.txt file');



addLog("Load existing data");
	$res=runQuery("SELECT prot_extdbid, prot_extdbac, prot_extdbabbr,prot_extdbname, prot_extdbserver,prot_extdburl, category 
					FROM prot_extdb");
	if ($res===false)																failProcess($JOB_ID."006",'Unable to run query');
	
	$DATA=array();
	$MAX_DBID=-1;
	foreach ($res as $tab)
	{
	
		$DATA[$tab['prot_extdbac']]=array(
		'DBID'	=>$tab['prot_extdbid'],
		'ABBR'	=>$tab['prot_extdbabbr'],
		'DBNAME'=>$tab['prot_extdbname'],
		'SERVER'=>$tab['prot_extdbserver'],
		'DBURL'	=>$tab['prot_extdburl'],
		'CAT'	=>$tab['category'],
		'STATUS'=>'FROM_DB');
		if ($tab['prot_extdbid']>$MAX_DBID)$MAX_DBID=$tab['prot_extdbid'];
	}

/// Here we load the proteome file
addLog("process File");
	$fp=fopen('dbxref.txt','r'); if (!$fp)														failProcess($JOB_ID."007",'Unable to open dbxref.txt FILE');
	
	$PROTEOMES=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,6000,"\n");
		
		if ($line==''||$line[0]=="#")continue;
		
		if (substr($line,0,2)!='AC')continue;
		
		$AC=getValue($line)[1];
		$ENTRY=array();
		
		do
		{
			$line=stream_get_line($fp,6000,"\n");
			if ($line=="")break;
			$tab=getValue($line);
			$ENTRY[$tab[0]]=$tab[1];
		
		}while(!feof($fp));
	
		if (isset($DATA[$AC]))
		{
			$TO_UPD=false;
			$ENTRY['STATUS']='VALID';
			if ($ENTRY['Abbrev']!= $DATA[$AC]['ABBR'])   {echo $DATA[$AC]['ABBR']."\t".$ENTRY['Abbrev']."\n";	$DATA[$AC]['ABBR']=$ENTRY['Abbrev'];	$TO_UPD=true;}
			if ($ENTRY['Server']!= $DATA[$AC]['SERVER']) {echo $DATA[$AC]['SERVER']."\t".$ENTRY['Server']."\n"; $DATA[$AC]['SERVER']=$ENTRY['Server'];	$TO_UPD=true;}
			if ($ENTRY['Db_URL']!= $DATA[$AC]['DBURL'])	 {echo $DATA[$AC]['DBURL']."\t".$ENTRY['Db_URL']."\n";	$DATA[$AC]['DBURL']=$ENTRY['Db_URL'];	$TO_UPD=true;}
			if ($ENTRY['Cat']   != $DATA[$AC]['CAT']) 	 {echo $DATA[$AC]['CAT']."\t".$ENTRY['Cat']."\n";		$DATA[$AC]['CAT']=$ENTRY['Cat'];		$TO_UPD=true;}
			if ($ENTRY['Name']  != $DATA[$AC]['DBNAME']) {echo $DATA[$AC]['DBNAME']."\t".$ENTRY['Name'] ."\n"; 	$DATA[$AC]['DBNAME']=$ENTRY['Name'];	$TO_UPD=true;}
			if ($TO_UPD!=true)continue;
			
				
			$SQL="UPDATE prot_extdb SET 
			prot_extdbac='".$AC."',
			prot_extdbabbr='".$DATA[$AC]['ABBR']."',
			prot_extdbname='".$DATA[$AC]['DBNAME']."',
			prot_extdbserver='".$DATA[$AC]['SERVER']."',
			prot_extdburl='".$DATA[$AC]['DBURL']."',
			category='".$DATA[$AC]['CAT']."' WHERE prot_extdbid = ".$DATA[$AC]['DBID'];
			$res=runQueryNoRes($SQL);
			if ($res===false)																		failProcess($JOB_ID."008",'Unable to run query'."\n".$SQL);
			
		}
		else
		{
			++$MAX_DBID;
			
			$SQL='INSERT INTO prot_extdb (prot_extdbid,prot_extdbac,prot_extdbabbr,prot_extdbname,prot_extdbserver,prot_extdburl,category) VALUES ('.$MAX_DBID.",'".
			$AC."','".
			$ENTRY['Abbrev']."','".
			$ENTRY['Name']."','".$ENTRY['Server']."','".$ENTRY['Db_URL']."','".$ENTRY['Cat']."')";
			
			if (!runQueryNoRes($SQL))																	failProcess($JOB_ID."009",'Unable to run query'."\n".$SQL);
		}
	}
	fclose($fp);

	

successProcess();






function getValue($line)
{
	$pos=strpos($line,':');
	if ($pos===false)return '';
	return array(trim(substr($line,0,$pos)),trim(substr($line,$pos+2)));
}

?>

