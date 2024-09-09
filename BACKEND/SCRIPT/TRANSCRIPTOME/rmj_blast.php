<?php
function rmj_blast()
{
	global $TG_DIR;
	global $GLB_TREE;
	global $GLB_VAR;


	/// Job name - Do not change
	$JOB_NAME='rmj_blast';

	/// Get root directories
	$TG_DIR= getenv('TG_DIR');
	if ($TG_DIR===false)  die('NO TG_DIR found ');
	if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);

	/// Get job id
	$JOB_ID=getJobIDByName($JOB_NAME);
	
	/// Get job info
	$JOB_INFO=$GLB_TREE[$JOB_ID];
  
	/// Since rmj_ functions are called within the monitor_job
	/// We create a specif $PROCESS_CONTROL_JOB specific for this submission in case this process fails
	$PROCESS_CONTROL_JOB=array(
		'STEP'=>0,
		'JOB_NAME'=>$JOB_NAME,
		'DIR'=>'',
		'LOG'=>array(),
		'STATUS'=>'INIT',
		'START_TIME'=>microtime_float(),
		'END_TIME'=>'',
		'STEP_TIME'=>microtime_float(),
		'FILE_LOG'=>''
	);

			
addLog("Create directory");
	
	/// Get parent info
	$PMJ_INFO=$GLB_TREE[getJobIDByName('pmj_blast')];

	/// Set working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];  if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ',$PROCESS_CONTROL_JOB);
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';   		 if (!is_dir($W_DIR) && !mkdir($W_DIR)) failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR,$PROCESS_CONTROL_JOB);
	$W_DIR.='/'.$PMJ_INFO['TIME']['DEV_DIR'].'/';if (!is_dir($W_DIR) && !mkdir($W_DIR)) failProcess($JOB_ID."003",'Unable to find and create '.$W_DIR,$PROCESS_CONTROL_JOB);
	$PROCESS_CONTROL['DIR']=$PMJ_INFO['TIME']['DEV_DIR'];
													
	/// Check script directory:
	if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."004",'SCRIPT_DIR not set ',$PROCESS_CONTROL_JOB);
	$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."005",'SCRIPT_DIR not found ',$PROCESS_CONTROL_JOB);
	
	/// Checking job array
	if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."006",'JOBARRAY NOT FOUND ',$PROCESS_CONTROL_JOB);
	$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
	if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."007",'JOBARRAY file NOT FOUND '.$JOBARRAY,$PROCESS_CONTROL_JOB);


	/// Checking master script:
	$ALL_FILE=$W_DIR.'/SCRIPTS_BLAST/all.sh';
	if (!is_file($ALL_FILE))															failProcess($JOB_ID."008",'Unable to find master job file at '.$ALL_FILE,$PROCESS_CONTROL_JOB);
	
	
	$LC=getLineCount($ALL_FILE);
	
	/// Submit job:
	exec('qsub  -tc '.$LC.
			  ' -o '.$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o '.
			  ' -e '.$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e '.
			  ' -v TG_DIR '.
			  ' -N '.$GLB_VAR['JOB_PREFIX'].'_'.$JOB_ID.
			  ' -t 1-'.$LC.':1 '.$JOBARRAY.' '.$ALL_FILE,$res,$return_code);
	if ($return_code!=0)															failProcess($JOB_ID."009",'Unable to submit master job file at '.$ALL_FILE,$PROCESS_CONTROL_JOB);

	// get the job id
	$tab=array_values(array_filter(explode(' ',$res[0])));
	$t2=explode(".",$tab[2]);

	return $t2[0];

}

function rmj_blast_term()
{
	
	global $TG_DIR;
	global $GLB_TREE;
	global $GLB_VAR;

	
	/// Job name - Do not change
	$JOB_NAME='rmj_blast';
	$JOB_ID=getJobIDByName($JOB_NAME);

	/// Get Parent info
	$PMJ_INFO=$GLB_TREE[getJobIDByName('pmj_blast')];
	

	/// The overall process is a bit different for this job
	/// We will need to submit a job array to the SGE
	/// and this function will be called from the master script
	/// So we need to have a way to return the PROCESS_CONTROL for a job array
	/// Therefore we create this PROCESS_CONTROL_JOB that will be sent to successProcess function
	$PROCESS_CONTROL_JOB=array('STEP'=>0,
		    'JOB_NAME'=>$JOB_NAME,
			'DIR'=>$PMJ_INFO['TIME']['DEV_DIR'],
		    'LOG'=>array(),
		   	'STATUS'=>false,
	       	'START_TIME'=>microtime_float(),
	       	'END_TIME'=>'',
	       	'STEP_TIME'=>microtime_float(),
	      	'FILE_LOG'=>''
		);


	/// Get working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."010",'NO '.$W_DIR.' found ',$PROCESS_CONTROL_JOB);

	$W_DIR.='/'.$PMJ_INFO['DIR'].'/';   		if (!is_dir($W_DIR)) 					failProcess($JOB_ID."011",'Unable to find  '.$W_DIR,$PROCESS_CONTROL_JOB);
	$W_DIR.='/'.$PMJ_INFO['TIME']['DEV_DIR'].'/';   if (!is_dir($W_DIR)) 				failProcess($JOB_ID."012",'Unable to find  '.$W_DIR,$PROCESS_CONTROL_JOB);
	
	/// Checking master script:
	$ALL_FILE=$W_DIR.'/SCRIPTS_BLAST/all.sh';
	if (!is_file($ALL_FILE))															failProcess($JOB_ID."013",'Unable to find master job file at '.$ALL_FILE,$PROCESS_CONTROL_JOB);

	$LC=getLineCount($ALL_FILE);


	/// Check if all the jobs are done successfully
	$SUCCESS=true;
														
	for ($I=0;$I<$LC;++$I)
	{
		if (!checkFileExist($W_DIR.'/status_'.$I))$SUCCESS=false;
		if (file_get_contents($W_DIR.'/status_'.$I)!=0)$SUCCESS=false;
	}

	if ($SUCCESS)$PROCESS_CONTROL_JOB['STATUS']=true;

	successProcess((($SUCCESS)?'SUCCESS':'FAIL'),$PROCESS_CONTROL_JOB);

}


?>