<?php

/**
 SCRIPT NAME: ck_drugbank_rel
 PURPOSE:     Check for new drugbank release
 
*/

/// Job name - Do not change
$JOB_NAME='ck_drugbank_rel';

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

addLog("Check variables");
	///Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID.'001','NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID.'002','Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID.'003','Unable to chdir '.$W_DIR);

	/// Check if FTP_DRUGBANK path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['LINK']['FTP_DRUGBANK']))										failProcess($JOB_ID.'004','FTP_DRUGBANK path no set');
	
	/// Check if DRUGBANK_LOGIN path is set in CONFIG_GLOBAL
	if (!isset($GLB_VAR['DRUGBANK_LOGIN']))												failProcess($JOB_ID.'005','DRUGBANK_LOGIN not found');

	/// Check if DRUGBANK_LOGIN path is set in CONFIG_GLOBAL
	if ($GLB_VAR['DRUGBANK_LOGIN']=='N/A')												failProcess($JOB_ID.'006','DRUGBANK_LOGIN not set');

	/// Define the login
	$DRUGBANK_LOGIN=$GLB_VAR['DRUGBANK_LOGIN'];
	

	$GLB_PROXY='';
	if (!isset($GLB_VAR['PROXY']))														failProcess($JOB_ID.'007','PROXY not found');
	if ($GLB_VAR['PROXY']!='N/A')$GLB_PROXY=$GLB_VAR['PROXY'];
	
addLog("Get releases");
	/// Create a CURL request to get the latest release
	$header[] = 'Content-length: 0';
    $header[] = 'Content-type: application/json';
    $header[] = 'Accept: application/json';
    $options = array(
        CURLOPT_RETURNTRANSFER => true, // return web page
        CURLOPT_HEADER => false, // don't return headers
        CURLOPT_FOLLOWLOCATION => true, // follow redirects
        CURLOPT_ENCODING => "", // handle all encodings
        CURLOPT_USERPWD => $DRUGBANK_LOGIN,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_AUTOREFERER => true, // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
        CURLOPT_TIMEOUT => 120, // timeout on response
        CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
        CURLOPT_SSL_VERIFYPEER => false, // Disabled SSL Cert checks
        CURLOPT_PROXYPORT => 9000,
        //CURLOPT_HTTP_VERSION    =>CURL_HTTP_VERSION_2_0
    );
	if ($GLB_PROXY!='')$options[CURLOPT_PROXY]=$GLB_PROXY;

	/// Defining path:
	$ch = curl_init($GLB_VAR['LINK']['FTP_DRUGBANK'].'/downloads.json');
	/// Set options
    curl_setopt_array($ch, $options);

	/// Execute the request
	$res=curl_exec($ch);
	if ($res===false)																	failProcess($JOB_ID.'008','Unable to query drugbank');
 	
addLog("Process releases");
	/// This part is a bit redundant and useless.
	/// But if an issue happen, we can still have the data
	$fp=fopen('CURRENT_DOWNLOADS','w');if (!$fp)										failProcess($JOB_ID.'009','Unable to create file');
	fputs($fp,$res);
	fclose($fp);

	/// Getting the content of the file, which is the same as the response
	$res=file_get_contents('CURRENT_DOWNLOADS');

	/// Decode the response
    $response = json_decode($res, true);
	/// If the response is false, then the format is not recognized
	if ($response===false)																failProcess($JOB_ID.'010','Unrecognized format');
	
	/// Looking for the latest release
	$max_time=0;$max_record=array();
	foreach ($response as &$record)
	{
		/// So we convert the date to a timestamp
		$time=strtotime($record['created_at']);
		
		/// If the time is less than the max time, we continue
		if ($time<$max_time)continue;
		$max_time=$time;
		$max_record=$record;
	}
	
	/// We create the NEW_RELEASE value
	$NEW_RELEASE=$max_record['id'].';'.$record['created_at'];
   
addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('DRUGBANK',$JOB_ID);



addLog("Update release tag");
	updateReleaseDate($JOB_ID,'DRUGBANK',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	/// Create the directory based on date
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID.'011','Unable to create new process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();

	successProcess();
	

?>