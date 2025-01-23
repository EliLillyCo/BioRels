<?php

if (!isset($TG_DIR))die();




/*
	$GLB_RUN_JOBS is an array that contains the list of jobs that are currently running
	$GLB_TREE is an array that contains the list of jobs
	$GLB_VAR is an array that contains the list of variables
	$TG_DIR is the root directory of the project

	submit_biorels_job will submit a job to the cluster or locally
		
*/
function submit_biorels_job($JOB_ID)
{
	global $GLB_RUN_JOBS;
	global $GLB_TREE;
	global $GLB_VAR;
	global $TG_DIR;
	
	
	/// we want to make sure that we do not submit the same job twice
	/// So we check if the job is already running
	foreach ($GLB_RUN_JOBS as $QJOB=>$ID) if ($ID==$JOB_ID)return;
	
	/// we get the job info
	$JOB_INFO=$GLB_TREE[$JOB_ID];

	/// we log the submission
	$STR_LOG= $JOB_ID.':'.$JOB_INFO['NAME']."\tSUBMISSION\n";
	
	if ($JOB_INFO['RUNTIME']=='S')
	{
		/// we get the path of the script
		$FPATH=$TG_DIR.'/'.$GLB_VAR['BACKEND_DIR'].'/CONTAINER_SHELL/'.$JOB_INFO['NAME'].'.sh';
		if (!checkFileExist($FPATH))die('Missing script file '.$FPATH);
	}
	
	
	/// Depending on the job type, as defined in CONFIG_JOB, we can set up the memory and the number of cores
	$ADD_DESC='';
	if ($JOB_INFO['MEM']!=-1)
	{
		if ($GLB_VAR['MONITOR_TYPE']=='SGE_CLUSTER')
		{
			$ADD_DESC.=' -l m_mem_free='.$JOB_INFO['MEM'].'M   -l h_rss='.$JOB_INFO['MEM'].'M ';
		}
		/// You can add other cluster specific parameters here:
		// else if ($GLB_VAR['MONITOR_TYPE']=='YOUR_CLUSTERING_TOOL')
		// {
		// 	$ADD_DESC.=' ';
		// }
	}

	/// Here we define the behavior of the job depending on the cluster type
	if ($GLB_VAR['MONITOR_TYPE']=='SGE_CLUSTER')
	{
		/// But also the behavior of the job depending on the runtime
		/// A "S" stands for Single job. Those jobs are submitted directly
		if ($JOB_INFO['RUNTIME']=='S')
		{
	
			/// We defined the command to submit the job:
			$query='qsub -v TG_DIR '.
			'-o '.$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o '.
			'-e '.$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e '.
			'-N '.$GLB_VAR['JOB_PREFIX'].'_'.$JOB_ID.' '.$ADD_DESC.' '.$FPATH;
			/// And execute it:
			exec($query,$res,$return_code);

			/// If the job was not submitted, we fail the process
			if ($return_code!=0) 
			{
				failProcess($JOB_ID."_SUBMIT_001","Unable to submit job ".$query);
			}
			/// We get the job id
			$tab=array_values(array_filter(explode(' ',$res[0])));

			/// Then we store the job id in the GLB_RUN_JOBS array, which defines the list of jobs that are currently running:
			$GLB_RUN_JOBS[$tab[2]]=$JOB_ID;
		}
		/// A "R" stands for Batch job. Those jobs are submitted as an array:
		else if ($JOB_INFO['RUNTIME']=='R')
		{
			$job_array_pid=submit_batch($JOB_ID,$JOB_INFO);
			$GLB_RUN_JOBS[$job_array_pid]=$JOB_ID;
		}
	}
	else if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')
	{
		
		/// We don't want to execute too many jobs in parallel:
		if (count($GLB_RUN_JOBS)>=$GLB_VAR['SINGLE_PARALLEL'])
			return 'SINGLE PARALLEL LIMIT REACHED '.$GLB_VAR['SINGLE_PARALLEL']. "\n";

		/// Single job, we submit it directly
		if ($JOB_INFO['RUNTIME']=='S')
		{
			/// We defined the command to submit the job:
			$arr=array();
			$outfile=$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o';
			$errfile=$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e';

			/// We execute the command:
			$command=sprintf("sh %s > %s 2>%s & echo $!", $FPATH, $outfile, $errfile);
			exec($command,$arr);
			

			/// Getting the PID of the process:
			$PID=$arr[0];
			

			/// Based on that PID, we can get the full PID of the process
			$c = 'ps -A -o "lstart " -o "|%p"';
			exec($c, $tmp);
			$val=array();
			//print_R($tmp);
			$FULL_PID='';
			foreach ($tmp as $V)
			{
				$test=explode("|",$V);
				
				if ($test[1]!=$PID)continue;
				$FULL_PID=str_replace(" ","|",trim($V));
			}
			$STR_LOG.= "\t=> ".$FULL_PID."\n";
			
			if ($FULL_PID=='')failProcess($JOB_ID."_SUBMIT_002","Unable to submit single job ");
			/// Then we store the job id in the GLB_RUN_JOBS array, which defines the list of jobs that are currently running:
			$GLB_RUN_JOBS[$FULL_PID]=$JOB_ID;
		}
		/// Batch job, we call the corresponding function:
		/// Which essentially should execute the ONLY single script in the job array
		else 
		{
			$job_array_pid=submit_batch($JOB_ID,$JOB_INFO);
			$GLB_RUN_JOBS[$job_array_pid]=$JOB_ID;
		}
	
	}
	// else if ($GLB_VAR['MONITOR_TYPE']=='YOUR_CLUSTERING_TOOL')
	// {
	// 	/// YOUR CODE HERE
	// 	/// The goal here is to submit the job to your clustering tool
	//	/// There are 2 situations. If the job is a single job, you can submit it directly
	//	/// If the job is a batch job, you need to call the function submit_batch($JOB_ID,$JOB_INFO)
	//	/// which will return the job id
	//	/// Then you store the job id in the GLB_RUN_JOBS array
	// if ($JOB_INFO['RUNTIME']=='S')
	// {
	// 	//// SUBMIT HERE
	//	/// The name of the job MUST be $GLB_VAR['JOB_PREFIX'].'_'.$JOB_ID
	// 	/// GET THE submitted job id as $SUBMITTED_JOB_ID
	// 	///$GLB_RUN_JOBS[$SUBMITTED_JOB_ID]=$JOB_ID;
	// }
	// else
	// {
	// 	$job_array_pid=submit_batch($JOB_ID,$JOB_INFO);
	// 	$GLB_RUN_JOBS[$job_array_pid]=$JOB_ID;
	// }

	// }
	else die('Unknown monitor type');


	/// Then we save the list of running jobs into a file as a backup
	/// That way, if the system crashes, we can still know which jobs are running
	refreshJobFile();
	return $STR_LOG;
}



function submit_batch($JOB_ID,$JOB_INFO)
{
	global $TG_DIR;
	global $GLB_VAR;
	global $GLB_TREE;


	/// Get job name
	$JOB_INFO=$GLB_TREE[$JOB_ID];
	$JOB_NAME=$JOB_INFO['NAME'];

	/// We define the process control job array as if it was a single job
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

	/// Now the information about the working directory is in the parent job
	/// So we need to get the parent job information
	/// We can get the parent job name by replacing the first letter of the job name
	$JOB_PMJ=str_replace('rmj_','pmj_',$JOB_NAME);
	/// We get the parent job info:
	$PMJ_INFO=$GLB_TREE[getJobIDByName($JOB_PMJ)];
	/// We set the process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL_JOB['DIR']=$PMJ_INFO['TIME']['DEV_DIR'];

	/// Now based on the parent job information, we can get the working directory for this script:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];
	$W_DIR.='/'.$PMJ_INFO['DIR'].'/';   		
	$W_DIR.='/'.$PMJ_INFO['TIME']['DEV_DIR'].'/';  
	if (!is_dir($W_DIR)) 				failProcess($JOB_ID."_SUBMIT_BATCH_001",'Unable to find  '.$W_DIR,$PROCESS_CONTROL_JOB);
	
	/// We check the master script that will be used to submit the job array is present:
	$ALL_FILE=$W_DIR.'/master.sh';
	if (!checkFileExist($ALL_FILE)) 				failProcess($JOB_ID."_SUBMIT_BATCH_002",'Unable to find  master.sh in '.$W_DIR,$PROCESS_CONTROL_JOB);
	
	/// We get the number of jobs defined in the job array:
	$LC=getLineCount($ALL_FILE);

	/// Rules for a SGE cluster:
	if ($GLB_VAR['MONITOR_TYPE']=='SGE_CLUSTER')
	{
		/// Check script directory:
		if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."_SUBMIT_BATCH_004",'SCRIPT_DIR not set ',$PROCESS_CONTROL_JOB);
		$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."_SUBMIT_BATCH_005",'SCRIPT_DIR not found ',$PROCESS_CONTROL_JOB);
		
		/// Checking job array
		if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."_SUBMIT_BATCH_006",'JOBARRAY NOT FOUND ',$PROCESS_CONTROL_JOB);
		$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
		if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."_SUBMIT_BATCH_007",'JOBARRAY file NOT FOUND '.$JOBARRAY,$PROCESS_CONTROL_JOB);

		

		
		/// Submit job:
		exec('qsub  -tc '.$LC.
				' -o '.$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o '.
				' -e '.$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e '.
				' -v TG_DIR '.
				' -N '.$GLB_VAR['JOB_PREFIX'].'_'.$JOB_ID.
				' -t 1-'.$LC.':1 '.$JOBARRAY.' '.$ALL_FILE,$res,$return_code);
		if ($return_code!=0)															failProcess($JOB_ID."_SUBMIT_BATCH_008",'Unable to submit master job file at '.$ALL_FILE,$PROCESS_CONTROL_JOB);

		// get the job id
		$tab=array_values(array_filter(explode(' ',$res[0])));
		$t2=explode(".",$tab[2]);

		return $t2[0];
	}
	else if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')
	{
		if ($LC!=1)																		failProcess($JOB_ID."_SUBMIT_BATCH_009",'Only one job can be submitted in single mode',$PROCESS_CONTROL_JOB);
		$fp=fopen("master.sh",'r'); if(!$fp)											failProcess($JOB_ID."_SUBMIT_BATCH_010",'Unable to open master.sh',$PROCESS_CONTROL_JOB);
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			if ($line=="")continue;
			/// We don't want to execute too many jobs in parallel:
			if (count($GLB_RUN_JOBS)>=$GLB_VAR['SINGLE_PARALLEL'])
			return 'SINGLE PARALLEL LIMIT REACHED '.$GLB_VAR['SINGLE_PARALLEL']. "\n";

			/// We defined the command to submit the job:
			$arr=array();
			$outfile=$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o';
			$errfile=$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e';

			/// We execute the command:
			$command=sprintf("sh %s > %s 2>%s & echo $!", $line, $outfile, $errfile);
			exec($command,$arr);
			

			/// Getting the PID of the process:
			$PID=$arr[0];
			

			/// Based on that PID, we can get the full PID of the process
			$c = 'ps -A -o "lstart " -o "|%p"';
			exec($c, $tmp);
			$val=array();
			//print_R($tmp);
			$FULL_PID='';
			foreach ($tmp as $V)
			{
				$test=explode("|",$V);
				
				if ($test[1]!=$PID)continue;
				$FULL_PID=str_replace(" ","|",trim($V));
			}
			$STR_LOG.= "\t=> ".$FULL_PID."\n";
			
			if ($FULL_PID=='')											failProcess($JOB_ID."_SUBMIT_BATCH_011",'Unable to submit single job',$PROCESS_CONTROL_JOB);
			/// Then we store the job id in the GLB_RUN_JOBS array, which defines the list of jobs that are currently running:
			return $FULL_PID;
		}
		fclose($fp);
	}
	else if ($GLB_VAR['MONITOR_TYPE']=='YOUR_CLUSTERING_TOOL')
	{
		/// execute your job array and retrieve the job id ($JOB_ARRAY_ID)
		// return $JOB_ARRAY_ID;
	}
}



function refreshJobFile()
{
	global $TG_DIR;
	global $GLB_VAR;
	global $GLB_RUN_JOBS;
	/// We save the list of running jobs into a file as a backup
	/// In TG_DIR/BACKEND/MONITOR/JOB_RUNNING.csv:
	$PATH=$TG_DIR.'/'.$GLB_VAR['MONITOR_DIR'].'/JOB_RUNNING.csv';
	if (!is_file($PATH))failProcess($JOB_ID."008",'Unable to find JOB_RUNNING at '.$PATH);

	/// We open the file
	$fp=fopen(	$TG_DIR.'/'.$GLB_VAR['MONITOR_DIR'].'/JOB_RUNNING.csv','w');
	if (!$fp) 						failProcess($JOB_ID."009",'Unable to open JOB_RUNNING ');

	/// We write the list of running jobs as CLUSTER_JOB_ID_OR_PID => BioRels_JOB_ID
	foreach ($GLB_RUN_JOBS as $QID=>$JOB_ID)	fputs($fp,$QID."\t".$JOB_ID."\n");
	fclose($fp);
	
}



/*
	$GLB_RUN_JOBS is an array that contains the list of jobs that are currently running
	$GLB_TREE is an array that contains the list of jobs
	$GLB_VAR is an array that contains the list of variables
	$TG_DIR is the root directory of the project

	monitor_running_jobs goal is to check the status of the jobs that are currently running
		
*/
function monitor_running_jobs()
{
	global $GLB_RUN_JOBS;
	global $GLB_TREE;
	global $GLB_VAR;

	$val=array();
		
	if ($GLB_VAR['MONITOR_TYPE']=='SGE_CLUSTER')
	{
		exec('qstat | egrep "('.$GLB_VAR['JOB_PREFIX'].'_|arrayjob|NNPS)" ',$val);
	}
	else if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')
	{
		$c = 'ps -A -o "lstart " -o "|%p"';
		exec($c, $tmp);
		$val=array();
		foreach ($tmp as $V)
		{
			//echo "|".$V."|\n";
			$tab=str_replace(" ","|",trim($V));
			//echo "=>".$tab."\n";
			
			$val[]=$tab;
		}
		
	
	}
	/*
	else if ($GLB_VAR['MONITOR_TYPE']=='YOUR_CLUSTERING_TOOL')
	{
		/// YOUR CODE HERE
		/// The goal here is to get the list of running jobs and return it as an array $val
		/// so that in the next step we can compare the list of running jobs with the list of jobs we have submitted
	}
	
	
	
	*/
	
	
	else die('Unknown monitor type');



	///We copy the list of running jobs into a variable $CHECK:
	$CHECK=$GLB_RUN_JOBS;
	$STR_LOG='';

	/// We go through the list of running jobs and check if the job is still running
	foreach ($val as $line)
	{
		$tab=array_values(array_filter(explode(" ",$line)));
	
		$ID=$tab[0];
		/// Still running, we remove it from the list of jobs to check
		if (isset($CHECK[$ID]))
		{	
			//echo "IN\t".$ID."\n";
			$STR_LOG.= "CURRENTLY RUNNING: ".$ID."\t".$CHECK[$ID]."\t".$GLB_TREE[$CHECK[$ID]]['NAME']."\n";
			unset($CHECK[$ID]);
		}

	}

	/// Therefore in CHECK, we only have the jobs that are not running anymore

	$ENDED_JOB=array();
	

	foreach ($CHECK as $QSTAT_ID=>$JOB_ID)
	{
		$ENDED_JOB[]=$JOB_ID;
		unset($GLB_RUN_JOBS[$QSTAT_ID]);
	}
	/// We go through the list of jobs that are not running anymore
	foreach ($ENDED_JOB as $QID=>&$JOB_ID)
	{
		/// Get their info:
		$JOB_INFO=&$GLB_TREE[$JOB_ID];
		$DD=getcwd();
		/// If it's a batch job, we need to call the corresponding function:
		//print_R($JOB_INFO);
		if ($JOB_INFO['RUNTIME']!='S') 
		{
			$STR_LOG.=validate_batch($JOB_ID);
		}
		else 
		{
		
			/// Then we validate the job
			$STR_LOG.=validate_biorels_job($JOB_ID);
		}
		chdir($DD);

	}

	$STR_LOG.= "NUMBER OF RUNNING JOBS:".count($GLB_RUN_JOBS)."\nENDED JOB:".count($ENDED_JOB)."\n";
	refreshJobFile();
	return $STR_LOG;
	
}


function is_job_monitored($JOB_ID)
{
	global $TG_DIR;
	global $TG_DIR;
global $GLB_VAR;
	global $GLB_RUN_JOBS;
	$PATH=$TG_DIR.'/'.$GLB_VAR['MONITOR_DIR'].'/JOB_RUNNING.csv';
	if (!is_file($PATH))failProcess($JOB_ID."001",'Unable to find JOB_RUNNING at '.$PATH);
	$fp=fopen(	$TG_DIR.'/'.$GLB_VAR['MONITOR_DIR'].'/JOB_RUNNING.csv','r');
	if (!$fp) 						failProcess($JOB_ID."002",'Unable to open JOB_RUNNING ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100,"\n");if ($line=='')continue;
		$tab=explode("\t",$line);
		if ($tab[1]==$JOB_ID){fclose($fp);return true;}
	}
	fclose($fp);
	return false;
}



function validate_biorels_job($JOB_ID)
{
	

	global $GLB_TREE;
	global $GLB_VAR;
	global $TG_DIR;
	$JOB_INFO=$GLB_TREE[$JOB_ID];
	$STR_LOG= $JOB_ID.":".$JOB_INFO['NAME']."\tEND\n";


	$LOG_FILE=$TG_DIR.'/'.$GLB_VAR['LOG_DIR'].'/'.$JOB_INFO['NAME'].'.log';
	$STR_LOG.= "\tLOG:".$LOG_FILE."\n";
	$PROCESS_DATA=array();
	if (!checkFileExist($LOG_FILE))	
	{
		$PROCESS_DATA['STATUS']='QUIT';
		$GLB_TREE[$JOB_ID]['TIME']['CHECK']=time();
	}	
	else{
	echo $LOG_FILE."\t".is_file($LOG_FILE)."\n";
	$PROCESS_DATA=unserialize(file_get_contents($LOG_FILE));
	$STR_LOG.= "\tSTATUS:".$PROCESS_DATA['STATUS']."\n";
	$STR_LOG.= "\tPROCESS DIR:".$PROCESS_DATA['DIR']."\n";

	$GLB_TREE[$JOB_ID]['TIME']['CHECK']=time();
	if ($PROCESS_DATA['STATUS']=='SUCCESS' )
	{

		$GLB_TREE[$JOB_ID]['TIME']['DEV']=time();
		$GLB_TREE[$JOB_ID]['TIME']['DEV_DIR']=$PROCESS_DATA['DIR'];	
	}
	}	
	//else if ($PROCESS_DATA['STATUS']=='VALID')$GLB_TREE[$JOB_ID]['TIME']['DEV']=time();
	$STATUS_MAP=array('SUCCESS'=>'T','VALID'=>'T','QUIT'=>'Q');
	$STATUS='F';
	
	if (isset($STATUS_MAP[$PROCESS_DATA['STATUS']]))$STATUS=$STATUS_MAP[$PROCESS_DATA['STATUS']];
	
	/// we want to keep track of the number of time a job failed
	if ($STATUS=='F')$GLB_TREE[$JOB_ID]['FAILED']++;
	else $GLB_TREE[$JOB_ID]['FAILED']=0;
	$STR_LOG.= "\tPROCESS DATA STATUS: ".$PROCESS_DATA['STATUS']."\t".$STATUS."\n";
	refreshTimestamp($JOB_ID, $STATUS);
	//if ($JOB_INFO['DEV_JOB']==false) refreshVersioning();
	return $STR_LOG;
}



function preloadJobs()
{
	global $TG_DIR;
	global $GLB_RUN_JOBS;
	global $GLB_VAR;
	$PATH=$TG_DIR.'/'.$GLB_VAR['MONITOR_DIR'].'/JOB_RUNNING.csv';
	if (!is_file($PATH))failProcess($JOB_ID."006",'Unable to find JOB_RUNNING at '.$PATH);
	$fp=fopen(	$TG_DIR.'/'.$GLB_VAR['MONITOR_DIR'].'/JOB_RUNNING.csv','r');
	if (!$fp) 						failProcess($JOB_ID."007",'Unable to open JOB_RUNNING ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100,"\n");
		if ($line=="")continue;
		$tab=explode("\t",$line);
		$GLB_RUN_JOBS[$tab[0]]=$tab[1];		
	}
	fclose($fp);
}





function prepare_batch($COMMANDS,$W_DIR)
{
	global $GLB_VAR;
	global $TG_DIR;
	global $JOB_ID;

	if ($GLB_VAR['MONITOR_TYPE']=='SINGLE' && count($COMMANDS)>1)failProcess($JOB_ID."_PREPARE_BATCH_000",'Only one job can be submitted in single mode');
	
	chdir($W_DIR);
	$fpA=fopen("master.sh",'w'); if(!$fpA)													failProcess($JOB_ID."_PREPARE_BATCH_001",'Unable to open master.sh');
	if (!is_dir("jobs") && !mkdir("jobs"))												failProcess($JOB_ID."_PREPARE_BATCH_002",'Unable to create jobs directory');
	if (!isset($COMMANDS[0]))															failProcess($JOB_ID."_PREPARE_BATCH_003",'JOB ID MUST START AT 0');

	if ($GLB_VAR['MONITOR_TYPE']=='SGE_CLUSTER')
	{
		/// Find setenv file:
		if (!isset($GLB_VAR['SCRIPT_DIR'])) 												failProcess($JOB_ID."_PREPARE_BATCH_003",'SCRIPT_DIR not set ');
		$SCRIPT_DIR=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'];if (!is_dir($SCRIPT_DIR))			failProcess($JOB_ID."_PREPARE_BATCH_004",'SCRIPT_DIR not found ');
		$SETENV=$SCRIPT_DIR.'/SHELL/setenv.sh'; 		if (!checkFileExist($SETENV))		failProcess($JOB_ID."_PREPARE_BATCH_005",'Setenv file not found ');

		/// Check if JOBARRAY is set in CONFIG_GLOBAL
		/// This is for running multiple job in parallel in SGE
		if (!isset($GLB_VAR['JOBARRAY']))													failProcess($JOB_ID."_PREPARE_BATCH_006",'JOBARRAY NOT FOUND ');
		$JOBARRAY=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/'.$GLB_VAR['JOBARRAY'];
		if (!checkFileExist($JOBARRAY))														failProcess($JOB_ID."_PREPARE_BATCH_007",'JOBARRAY file NOT FOUND '.$JOBARRAY);


		foreach ($COMMANDS as $JOB_NUM=>$JOB_COMMANDS)
		{
			$JOB_NAME="jobs/job_".$JOB_NUM.".sh";
			$fp=fopen($JOB_NAME,"w");if(!$fpA)												failProcess($JOB_ID."_PREPARE_BATCH_008",'Unable to open jobs/job_'.$JOB_NUM.'.sh');
			
			/// Add the script path to master script
			fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");

			/// Add the script to the job
			fputs($fp,'#!/bin/sh'."\n");
			/// Add the environment script
			fputs($fp,"source ".$SETENV."\n");

			/// Add the command to run the script
			fputs($fp,'cd '.$W_DIR."\n");
			fputs($fp,implode ("\n",$JOB_COMMANDS)."\n");
			/// Retrieve the job status and save it to a file for post-processing
			fputs($fp,'echo $? > '.$W_DIR.'/jobs/status_'.$JOB_NUM."\n");
			fclose($fp);
		}
	}
	/// Single mode
	else if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')
	{
		foreach ($COMMANDS as $I=>$JOB_COMMANDS)
		{
			$JOB_NAME="jobs/job_".$JOB_NUM.".sh";
			$fp=fopen($JOB_NAME,"w");if(!$fpA)												failProcess($JOB_ID."_PREPARE_BATCH_010",'Unable to open jobs/job_'.$JOB_NUM.'.sh');
			
			/// Add the script path to master script
			fputs($fpA,"sh ".$W_DIR.'/'.$JOB_NAME."\n");

			/// Add the script to the job
			fputs($fp,'#!/bin/sh'."\n");
			/// Add the environment script
			fputs($fp,"source ".$SETENV."\n");

			/// Add the command to run the script
			fputs($fp,'cd '.$W_DIR."\n");
			fputs($fp,implode ("\n",$JOB_COMMANDS)."\n");
			/// Retrieve the job status and save it to a file for post-processing
			fputs($fp,'echo $? > '.$W_DIR.'/jobs/status_'.$I."\n");
			fclose($fp);
		}
		
	}
	// else if ($GLB_VAR['MONITOR_TYPE']=='YOUR_CLUSTERING_TOOL')
	// {
	// 	/// YOUR CODE HERE
	// 	/// The goal here is to prepare the batch job
	//	/// A master script is already opened
	//	/// Each record in $COMMANDS contains the shell commands" for each script to run
	//	/// You need to create a script for each record in $COMMANDS
	//	/// The script should be saved in the jobs directory
	// }
	fclose($fpA);


}



function validate_batch($JOB_ID)
{
	global $GLB_TREE;
	global $GLB_VAR;
	global $TG_DIR;
	$JOB_INFO= &$GLB_TREE[$JOB_ID];
	$JOB_NAME=$JOB_INFO['NAME'];
	$JOB_PMJ=str_replace('rmj_','pmj_',$JOB_NAME);
	$PMJ_INFO=$GLB_TREE[getJobIDByName($JOB_PMJ)];
	/// Get working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];

	$W_DIR.='/'.$PMJ_INFO['DIR'].'/';   		
	$W_DIR.='/'.$PMJ_INFO['TIME']['DEV_DIR'].'/';  
	
	/// Checking master script:
	$ALL_FILE=$W_DIR.'/master.sh';
	$LC=getLineCount($ALL_FILE);
	$JOB_INFO['TIME']['DEV_DIR']=$PMJ_INFO['TIME']['DEV_DIR'];
	$JOB_INFO['TIME']['CHECK']=time();
	$JOB_INFO['TIME']['DEV']=time();
	/// Check if all the jobs are done successfully
	$STATUS='T';
	$INFO='';												
	for ($I=0;$I<$LC;++$I)
	{
		if (!checkFileExist($W_DIR.'/jobs/status_'.$I))
		{
			$STR_LOG.= "\t=> ".$W_DIR.'/jobs/status_'.$I." MISSING\n";
			$STATUS='Q';
			$INFO.='MISSING job '.$I.';';
		}
		if (file_get_contents($W_DIR.'/jobs/status_'.$I)!=0)
		{
			$STATUS='F';
			$STR_LOG.= "\t=> ".$W_DIR.'/jobs/status_'.$I." FAILED\n";
			$INFO.='FAILED job '.$I.';';

		}
	}

	$STR_LOG.= $JOB_ID.":".$JOB_INFO['NAME']."\tEND\n";

	/// we want to keep track of the number of time a job failed
	if ($STATUS=='F')$GLB_TREE[$JOB_ID]['FAILED']++;
	else $GLB_TREE[$JOB_ID]['FAILED']=0;	

	$SCHEMA=$GLB_VAR['PUBLIC_SCHEMA'];
	if ($JOB_INFO['IS_PRIVATE']==1)$SCHEMA=$GLB_VAR['SCHEMA_PRIVATE'];
	$res=runQueryNoRes("INSERT INTO ".$SCHEMA.".biorels_job_history 
						VALUES (
							(SELECT br_timestamp_id 
							FROM  ".$SCHEMA.".biorels_timestamp 
							WHERE job_name='".$JOB_NAME."'),
						CURRENT_TIMESTAMP,
						0,
						'".$STATUS."',
						'".$INFO."')");

	refreshTimestamp($JOB_ID, $STATUS);
	return $STR_LOG;

}




?>