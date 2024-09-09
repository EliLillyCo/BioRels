<?php
$JOB_NAME='prd_xray';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$JOB_INFO=$GLB_TREE[$JOB_ID];




addLog("Check directory");
	$TG_INFO=$GLB_TREE[getJobIDByName('db_xray_int_stat')];
	$T_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/';
	$W_DIR=$T_DIR.$TG_INFO['TIME']['DEV_DIR'];
	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$ARCHIVE=$T_DIR.'/ARCHIVE';
	if (!is_dir($ARCHIVE) && !mkdir($ARCHIVE)) 										failProcess($JOB_ID."002",'Unable to create '.$ARCHIVE.' directory');
	$PRD_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'];
	if (!is_dir($PRD_DIR))			 												failProcess($JOB_ID."003",'Unable to find '.$PRD_DIR.' directory');
	$PROCESS_CONTROL['DIR']=$TG_INFO['TIME']['DEV_DIR'];



addLog("Push to prod");
if ($JOB_INFO['TIME']['DEV_DIR']!=-1)
{
	if ($JOB_INFO['TIME']['DEV_DIR']!=-1){
		$CURR_PROD_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$JOB_INFO['TIME']['DEV_DIR'];	

		if (is_dir($CURR_PROD_DIR)){
			system('tar -czf '.$ARCHIVE.'/'.$JOB_INFO['TIME']['DEV_DIR'].'.tar.gz '.$CURR_PROD_DIR,$return_code);
			if ($return_code !=0)												failProcess($JOB_ID."004",'Unable to create '.$ARCHIVE.'/'.$JOB_INFO['TIME']['DEV_DIR'].'.tar.gz archive');
			system('rm -rf '.$CURR_PROD_DIR,$return_code);	
			if ($return_code!=0)												failProcess($JOB_ID."005",'Unable to delete '.$CURR_PROD_DIR.' directory');
		}
	}	
}

$PRD_PATH=$PRD_DIR.'/'.$JOB_INFO['DIR'];

if (is_link($PRD_PATH)){
		system('unlink '.$PRD_PATH,$return_code);
		if ($return_code !=0)												failProcess($JOB_ID."006",'Unable to unlink '.$PRD_PATH.' directory');
	}
	
system('ln -s '.$W_DIR.' '.$PRD_PATH,$return_code);
if ($return_code!=0)														failProcess($JOB_ID."007",'Unable to create symlink '.$PRD_PATH.' directory');


successProcess();
?>
