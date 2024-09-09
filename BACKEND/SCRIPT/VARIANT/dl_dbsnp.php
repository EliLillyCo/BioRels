<?php

/**
 SCRIPT NAME: dl_dbsnp
 PURPOSE:     Download all dbsnp files
 
*/

/// Job name - Do not change
$JOB_NAME='dl_dbsnp';

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
	/// GEt parent info
	$CK_DBSNP_INFO=$GLB_TREE[getJobIDByName('ck_dbsnp_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_DBSNP_INFO['DIR'].'/DBSNP/';if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_DBSNP_INFO['TIME']['DEV_DIR'];	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_DBSNP_INFO['TIME']['DEV_DIR'];;

addLog("Working directory: ".$W_DIR);

	///Check FTP path:
	if (!isset($GLB_VAR['LINK']['FTP_DBSNP']))											failProcess($JOB_ID."005",'FTP_DBSNP path no set');

	/// Create web link
 	$WLINK=$GLB_VAR['LINK']['FTP_DBSNP'].'/latest_release/JSON/';

	/// List all chromosomes:
	$LIST_CHROM=array('X','Y','MT','withdrawn');
	for($I=1;$I<=22;++$I)$LIST_CHROM[]=$I;

addLog("Download SNP Files");

	foreach ($LIST_CHROM as $CHR_NUM)
	{
		/// Withdrawn file has a different name
		if ($CHR_NUM=='withdrawn') $FILE_NAME='refsnp-withdrawn.json.bz2';
		else $FILE_NAME='refsnp-chr'.$CHR_NUM.'.json.bz2';

		/// Download the file
		if (!dl_file($WLINK.$FILE_NAME,3))												failProcess($JOB_ID."006",'Unable to download archive');
		/// Download the md5 hash
		if (!dl_file($WLINK.$FILE_NAME.'.md5',3))										failProcess($JOB_ID."007",'Unable to download md5');

		/// Compare the hash. The md5 file contains the hash and the file name, so we need to extract the hash only
		if (md5_file($FILE_NAME) != explode(" ",file_get_contents($FILE_NAME.'.md5'))[0])failProcess($JOB_ID."008",'md5 hash different');

		/// Remove hash file 
		if (!unlink($FILE_NAME.'.md5'))													failProcess($JOB_ID."009",'Unable to remove md5');
		/// Extract the archive
		if (!unbzip2($FILE_NAME))														failProcess($JOB_ID."010",'Unable to extract archive');
	}

addLog("Download studies");
	/// Download the different frequency studies:
	if (!dl_file($WLINK.'/frequency_studies.json',3))									failProcess($JOB_ID."011",'Unable to download frequency studies');

	$WLINK=$GLB_VAR['LINK']['FTP_DBSNP'].'/latest_release/release_notes.txt';
	if (!dl_file($WLINK,3))																failProcess($JOB_ID."012",'Unable to download release_notes');



addLog("Download alfa");
	if (!is_dir('ALFA') && !mkdir('ALFA'))												failProcess($JOB_ID."013",'Unable to create ALFA directory');
	if (!chdir('ALFA'))																	failProcess($JOB_ID."014",'Unable to change to  ALFA directory');
	$WLINK=$GLB_VAR['LINK']['FTP_DBSNP'].'/population_frequency/latest_release/';

	/// Download ALFA frequency file:
	if (!checkFileExist('freq.vcf.gz') &&
	!dl_file($WLINK.'/freq.vcf.gz',3,'freq.vcf.gz'))									failProcess($JOB_ID."015",'Unable to download freq.vcf.gz');
	
	/// Download hash:
	if (!checkFileExist('freq.vcf.gz.md5') &&
	!dl_file($WLINK.'/freq.vcf.gz.md5',3,'freq.vcf.gz.md5'))							failProcess($JOB_ID."016",'Unable to download freq.vcf.gz.md5');
	
	/// Compare hash:
	/// The md5 file contains the hash and the file name, so we need to extract the hash only
	if (md5_file('freq.vcf.gz') != explode(" ",file_get_contents('freq.vcf.gz.md5'))[0])failProcess($JOB_ID."017",'md5 hash different');
	
	/// Remove hash file and extract archive:
	if (!unlink('freq.vcf.gz.md5'))														failProcess($JOB_ID."018",'Unable to remove freq.vcf.gz.md5');
	if (!ungzip('freq.vcf.gz'))															failProcess($JOB_ID."019",'Unable to extract archive');

	$WLINK.='supplement/';
	if (!checkFileExist('ALFA.html') &&
	!dl_file($WLINK,3,'ALFA.html'))														failProcess($JOB_ID."020",'Unable to download ALFA.html');

	$fpB=fopen('ALFA.html','r');if (!$fpB) 												failProcess($JOB_ID."021",'Unable to open ALFA.html');
	$tag='.gz';
	$path=$WLINK;
	while(!feof($fpB))
	{
		$line=stream_get_line($fpB,10000,"\n");
		$tab=explode(">",$line);
		
		
		
		$t2=explode('"',$tab[0]);
		if (!isset($t2[1]))continue;
		$name=$t2[1];
	;	if ($tag!='' && strpos($name,$tag)===false)continue;
		echo "DOWNLOADING ".$path.'/'.$name."\n";
		$path_all='';
		$path_all.=$path.'/';
		$path_all.=$name;
		$out='';
		
		$out.=$name;
		if (!checkFileExist($name) &&
			!dl_file($path_all,3,$out))              							     failProcess($JOB_ID."022",'Unable to download '.$path.$name);
	}


	if (!dl_file($WLINK,3))															failProcess($JOB_ID."023",'Unable to download ALFA frequency ');

successProcess();

?>
