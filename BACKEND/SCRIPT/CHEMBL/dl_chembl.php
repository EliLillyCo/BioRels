<?php
/**
 SCRIPT NAME: dl_chembl
 PURPOSE:     Download new ChEMBL file
 
*/

/// Job name - Do not change
$JOB_NAME='dl_chembl';

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

addLog("Create directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_chembl_rel')];

	/// Create directory in PROCESS
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);

	$W_DIR.=getCurrDate();			   		   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
 						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
 	
	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=getCurrDate();

	
	
addLog("Download ChEMBL file");
	/// File names are version dependent, so we need the release version
 	$CURR_RELEASE=explode("-",getCurrentReleaseDate('CHEMBL',$JOB_ID))[0];
	/// And the files prefix
	$HEADER_FILE='chembl_'.$CURR_RELEASE.'_';
	/// Those are the files to download
	 $FILES_TO_DOWNLOAD=array('chemreps.txt.gz','hmmr.fa.gz','release_notes.txt','postgresql.tar.gz','chembl_uniprot_mapping.txt');
	 
	/// Check if FTP_CHEMBL path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_CHEMBL']))											failProcess($JOB_ID."005",'FTP_CHEMBL path no set');

	/// Download files
	foreach ($FILES_TO_DOWNLOAD as $F)
	{
		$PREFIX=$HEADER_FILE;
		if ($F=='chembl_uniprot_mapping.txt')$PREFIX='';
		if (!dl_file($GLB_VAR['LINK']['FTP_CHEMBL'].'/'.$PREFIX.$F,3))				failProcess($JOB_ID."006",'Unable to download file '.$F);
	}
	/// And we get the checksums
	if (!dl_file($GLB_VAR['LINK']['FTP_CHEMBL'].'/checksums.txt',3))					failProcess($JOB_ID."007",'Unable to download file checksums.txt');

addLog("Load checksums");
	/// Get the SHA256 checksums for each file
	$fp=fopen('checksums.txt','r'); if (!$fp)											failProcess($JOB_ID."008",'Unable to open checksums');
	$SHA256=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')continue;
		$tab=explode("\t",$line);
		if (count($tab)!=2)continue;
		$SHA256[$tab[1]]=$tab[0];
	}
	fclose($fp);


addLog("Check files");
	/// Checking SHA256 checksum for each file
	foreach ($FILES_TO_DOWNLOAD as $DL)
	{
		if ($DL=='release_notes.txt'||$DL=='chembl_uniprot_mapping.txt')continue;
		$res=array();exec('sha256sum '.$HEADER_FILE.$DL,$res,$return_code);
		if ($return_code!=0)															failProcess($JOB_ID."009",'Unable to get sha256 for '.$DL);
		$SHA=explode(" ",$res[0]);
		if ($SHA[0]!=$SHA256[$HEADER_FILE.$DL])											failProcess($JOB_ID."010",'Different hash for '.$DL);
	}

 addLog("Untar archive");
	/// All checksum valid? We can then decompress the archive 
 	foreach ($FILES_TO_DOWNLOAD as $DL)
	{
		if (substr($HEADER_FILE.$DL,-7)=='.tar.gz'&&!untar($HEADER_FILE.$DL))			failProcess($JOB_ID."011",'Unable to extract archive for '.$DL);
		if (substr($HEADER_FILE.$DL,-3)=='.gz'&&!ungzip($HEADER_FILE.$DL))				failProcess($JOB_ID."012",'Unable to extract archive for '.$DL);
	}

addLog("Drop schema if exist");
	/// We can't drop the Public schema, so we need to delete all tables individually
	$res=runQuery("SELECT
	'DROP TABLE IF EXISTS public.' || tablename || ' CASCADE' tbl_q
  from
	pg_tables WHERE schemaname = 'public'");
	foreach ($res as $line)
	{
		echo $line['tbl_q']."\n";
	if (!runQueryNoRes($line['tbl_q']))													failProcess($JOB_ID."013",'Unable to delete: '.$line['tbl_q']);
	}
	

addLog("Load ChEMBL schema");
	$query='/bin/pg_restore --no-owner -h '.getenv('DB_HOST').' -p '.getenv('DB_PORT').' -U '.getenv('PGUSER').' -d '.getenv('DB_NAME').' chembl_'.$CURR_RELEASE.'/chembl_'.$CURR_RELEASE.'_postgresql/chembl_'.$CURR_RELEASE.'_postgresql.dmp';
	exec($query,$res,$return_code);
	if ($return_code!=0)															failProcess($JOB_ID."014",'Unable to load chembl_'.$CURR_RELEASE.'_postgresql.dmp');
	print_r($res);
	


successProcess();

?>
