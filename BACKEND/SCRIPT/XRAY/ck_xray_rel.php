<?php
$JOB_NAME='ck_xray_rel';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];

addLog("Download release note");
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	if (!chdir($W_DIR))																	failProcess($JOB_ID."003",'Unable to chdir '.$W_DIR);
	if (!isset($GLB_VAR['LINK']['FTP_RCSB_DERIVED']))									failProcess($JOB_ID."004",'FTP_RCSB_DERIVED path no set');
	if (is_file('index.html') && !unlink('index.html'))									failProcess($JOB_ID."005",'Unable to delete former index.html');
	if (!dl_file($GLB_VAR['LINK']['FTP_RCSB_DERIVED'],3))								failProcess($JOB_ID."006",'Unable to download archive');
	
	
	

addLog("Process release note");
		$fp=fopen('index.html','r')	;if (!$fp)											failProcess($JOB_ID."007",'Unable to open so.obo');
		$NEW_RELEASE='';
		while(!feof($fp))
		{
			$line=stream_get_line($fp,500,"\n");
			
			if (strpos($line,'pdb_entry_type.txt')===false)continue;
			preg_match("/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/",$line,$matches);
			$NEW_RELEASE=$matches[0];
			
			break;
		}
		fclose($fp);
		



addLog("Validate release note");
	$tab2=explode("-",$NEW_RELEASE);
	if ($tab2[0]!=date("Y") && $tab2[0]!=(date("Y")-1))									failProcess($JOB_ID."008",'Unexpected year format');
	

addLog("Get current release date");
	$CURR_RELEASE=getCurrentReleaseDate('RCSB',$JOB_ID);


addLog("Compare release");
	unlink($W_DIR.'/index.html');
	if ($CURR_RELEASE == $NEW_RELEASE){	successProcess('VALID');}
	
	


addLog("Update release tag");
	updateReleaseDate($JOB_ID,'RCSB',$NEW_RELEASE);


addLog("Create directory");
	$PROCESS_CONTROL['DIR']='N/A';

	$JOB_INFO=$GLB_TREE[getJobIDByName($JOB_NAME)];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."009",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."010",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."011",'Unable to create new process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=getCurrDate();
		if (is_link('LATEST'))unlink('LATEST');
		symlink($W_DIR,'LATEST');
	successProcess();

?>
