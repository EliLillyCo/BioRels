<?php
/*
 SCRIPT NAME: pmj_${DATASOURCE}
 PURPOSE:     Prepare scripts for ${DATASOURCE} Processing
 
*/

/// Job name - Do not change
$JOB_NAME='pmj_${DATASOURCE}';

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
	$CK_INFO=$GLB_TREE[getJobIDByName('${PP_PARENT}')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 						failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 		failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	
	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];


	/// Check process_${DATASOURCE}.php
	$RUNSCRIPT=$SCRIPT_DIR.'/'.$JOB_INFO['DIR'].'/process_${DATASOURCE}.php';
	if (!checkFileExist($RUNSCRIPT))													failProcess($JOB_ID."008",$RUNSCRIPT.' file not found');

	
	
	/// Check JOBARRAY
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."010",'JOBARRAY file NOT FOUND '.$JOBARRAY);

addLog("Working directory: ".$W_DIR);	


	/// Working path:
	$W_DIR_PATH='$TG_DIR/'.$GLB_VAR['PROCESS_DIR'].'/'.$CK_INFO['DIR'].'/'.$CK_INFO['TIME']['DEV_DIR'];
	
	if (!is_dir("SCRIPTS") && !mkdir("SCRIPTS"))										failProcess($JOB_ID."015",'Unable to create jobs directory');
	if (!is_dir("JSON") && !mkdir("JSON"))												failProcess($JOB_ID."016",'Unable to create jobs directory');
	
	
	/// Create master script:
	$fpA=fopen("SCRIPTS/all.sh",'w'); if(!$fpA)											failProcess($JOB_ID."017",'Unable to open all.sh');
	$N_JOB=50;
	if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')$N_JOB=1;

	for($I=0;$I<$N_JOB;++$I)
	{
		$COMMANDS[]='biorels_${LANGUAGE} '.$RUNSCRIPT_PATH.' '.$I.' F &> SCRIPTS/LOG_'.$I;
	}
	prepare_batch($COMMANDS,$W_DIR_PATH);





successProcess();

?>


