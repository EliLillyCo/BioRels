<?php
/**
 SCRIPT NAME: ck_alfa_rel
 PURPOSE:     Check for new release of alfa & license
 
*/

/// Job name - Do not change
$JOB_NAME = 'ck_alfa_rel';

/// Get root directories
$TG_DIR = getenv('TG_DIR');

if ($TG_DIR === false)
	die('NO TG_DIR found ');

if (!is_dir($TG_DIR))
	die('TG_DIR value is not a directory '.$TG_DIR);

require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

/// Get job id
$JOB_ID = getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR'] = 'N/A';
/// Get job info
$JOB_INFO = $GLB_TREE[$JOB_ID];


addLog("Download release note");
	/// Define working directory in PROCESS
	$W_DIR  = $TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];
	if (!is_dir($W_DIR))
		failProcess($JOB_ID."001", 
					'NO '.$W_DIR.' found ');

	$W_DIR .= '/'.$JOB_INFO['DIR'].'/';

	if (!is_dir($W_DIR) && 
		!mkdir($W_DIR)) 	
		failProcess($JOB_ID."002",
					'Unable to find and create '.$W_DIR);

	if (!chdir($W_DIR))
		failProcess($JOB_ID."003",
					'Unable to chdir '.$W_DIR);
	
	

addLog("Working directory: ".$W_DIR);

	///Check FTP path:
	if (!isset($GLB_VAR['LINK']['FTP_DBSNP']))
		failProcess($JOB_ID."004",
					'FTP_DBSNP path no set');

	if (checkFileExist('freq.vcf')) 
		successProcess("VALID");
	
	$WLINK = $GLB_VAR['LINK']['FTP_DBSNP'].
			 '/population_frequency/latest_release/';


	/// Download file
	if (!dl_file($WLINK, 3))
		failProcess($JOB_ID."005",
					'Unable to download README.txt');
	
	
addLog("Process index.html");
	
	/// Open the README.txt file and search for the release notes file
	$fp = fopen('index.html','r');
	if (!$fp)											
		failProcess($JOB_ID."006",
					'Unable to open README.txt');
	
	$NEW_RELEASE = '';
	while(!feof($fp))
	{
		$line = stream_get_line($fp,1000,"\n");
		/// There should be a release note file, from whihc we extract the year
		if (!preg_match('/"freq.vcf.gz"/', $line, $matches))continue;
		if (!preg_match('/[0-9\-]{10}/', $line, $matches))	continue;
		$NEW_RELEASE = $matches[0];
		break;
	}
	fclose($fp);
	
	if (!unlink($W_DIR.'/index.html'))	
		failProcess($JOB_ID."008",
					'Unable to remove README.txt');



	
addLog("Get current release date");
	$CURR_RELEASE = getCurrentReleaseDate('NEW-ALFA', $JOB_ID);


addLog("Compare release");
	if ($CURR_RELEASE == $NEW_RELEASE)	successProcess('VALID');
	if ($CURR_RELEASE != -1 && 
		$CURR_RELEASE != getCurrentReleaseDate('ALFA', $JOB_ID))
	{
		addLog("Waiting for current release to be pushed to production");
		successProcess("VALID");
	}
	
addLog("Update release tag");
	updateReleaseDate($JOB_ID,
						'NEW-ALFA',
						$NEW_RELEASE);


addLog("Create working directory");
	$PROCESS_CONTROL['DIR'] = 'N/A';

	$JOB_INFO = $GLB_TREE[getJobIDByName($JOB_NAME)];
	
	$W_DIR = $TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];
	
	if (!is_dir($W_DIR)) 				
		failProcess($JOB_ID."014",
					'NO '.$W_DIR.' found ');
	
	$W_DIR .='/'.$JOB_INFO['DIR'].'/';	   
	
	if (!is_dir($W_DIR) && 
		!mkdir($W_DIR)) 	
		failProcess($JOB_ID."015",
					'Unable to find and create '.$W_DIR);
	
	$W_DIR .= getCurrDate();		           
	if (!is_dir($W_DIR) && 
		!mkdir($W_DIR)) 	
		failProcess($JOB_ID."016",
					'Unable to create new process dir '.$W_DIR);
	
	/// We assign the directory to the process control, 
	// so the next job knows where to look
	$PROCESS_CONTROL['DIR'] = getCurrDate();

	successProcess();

?>
