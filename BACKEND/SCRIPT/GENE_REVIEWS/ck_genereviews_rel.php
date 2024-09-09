<?php




/**
 SCRIPT NAME: ck_genereviews_rel
 PURPOSE:     Check for new gene reviews release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_genereviews_rel';


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




addLog("Download release note");
	///Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);

addLog($W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);


	/// Check if FTP_GENEREVIEWS path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_GENEREVIEWS']))									failProcess($JOB_ID."004",'FTP_GENEREVIEWS path no set');
	/// Check if FTP_GENEREVIEWS_MAP path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_GENEREVIEWS_MAP']))								failProcess($JOB_ID."005",'FTP_GENEREVIEWS_MAP path no set');
	if (checkFileExist('index.html')&& !unlink('index.html'))							failProcess($JOB_ID."006",'Unable to delete index.html');
	
	
	/// Download the file
	if (!dl_file($GLB_VAR['LINK']['FTP_GENEREVIEWS'].'/',3))							failProcess($JOB_ID."006",'Unable to download release notes');
	
	/// Open the file
	$fp=fopen('index.html','r');if (!$fp)												failProcess($JOB_ID."007",'Unable to open index.html');
	$FNAME='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
		
		/// Check if the line contains the release note
		if (strpos($line,'gene_')===false) continue;
		
		$tab=array_values(array_filter(explode(" ",$line)));
		
		$NEW_RELEASE= $tab[2];
		$FNAME=explode('"',$tab[1])[1];
		echo $FNAME.'|'.$NEW_RELEASE."\n";	
	}
	
	

addLog("Get current release date for GENE REVIEWS");
	$CURR_RELEASE=getCurrentReleaseDate('GENE REVIEWS',$JOB_ID);
	
addLog("Compare release date ".$CURR_RELEASE."\t".$NEW_RELEASE);
	if (!unlink('index.html'))															failProcess($JOB_ID."008",'Unable to delete index.html');
	if ($CURR_RELEASE == $NEW_RELEASE) successProcess('VALID');
	


	
addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Setting the directory with current date:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."009",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."010",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."011",'Unable to create new process dir '.$W_DIR);
	
	/// Change to the new directory
	if (!chdir($W_DIR))																	failProcess($JOB_ID."012",'Unable to change to new process dir '.$W_DIR);
	

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=getCurrDate();
	
	/// Download the file
	if (!dl_file($GLB_VAR['LINK']['FTP_GENEREVIEWS'].'/'.$FNAME,3))						failProcess($JOB_ID."013",'Unable to download file');
	if (!untar($FNAME))																	failProcess($JOB_ID."014",'Unable to untar files');

	/// Download the mapping files
	$files=array(
		'GRshortname_NBKid_genesymbol_dzname.txt',
		'GRtitle_shortname_NBKid.txt',
		'NBKid_shortname_OMIM.txt',
		'NBKid_shortname_genesymbol.txt',
		'NBKid_shortname_genesymbol_UniProt.txt');
	foreach ($files as $f)
	if (!dl_file($GLB_VAR['LINK']['FTP_GENEREVIEWS_MAP'].'/'.$f,3))						failProcess($JOB_ID."015",'Unable to download file '.$f);
	
addLog("Update release tag for GENE REVIEWS");
	updateReleaseDate($JOB_ID,'GENE REVIEWS',$NEW_RELEASE);


successProcess();
?>
