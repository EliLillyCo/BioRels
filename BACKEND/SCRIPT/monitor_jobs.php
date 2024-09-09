<?php

/**
 SCRIPT NAME: monitor_jobs
 PURPOSE:     Run and monitor biorels processes
*/

//////////////////////////////////

$JOB_NAME='monitor_jobs.php';

/// TG_DIR should be already set in environment variables using setenv.sh
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR parameter found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);

define("MONITOR_JOB",true);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');


	/// Verification that previous jobs are over or not.
preloadJobs();
preloadWebJobs();


/// Log information:
$STR_LOG='## BIORELS MONITORING SCRIPT'."\nDate/Time:".date('l jS \of F Y h:i:s A')."\n".monitor_qengine();
$N_LINES=count(explode("\n",$STR_LOG));
echo $STR_LOG;sleep(2);	

 function removeLine( $count = 1) {
	$STR='';
    foreach (range(1,$count) as $value){
        $STR.= "\r\x1b[K"; // remove this line
        $STR.= "\033[1A\033[K"; // cursor back
    }
	return $STR;
}
		
$STR_SPECS='';
/// Function to simplify console output:
function level(&$STR,$LEN)
{
	$L=strlen($STR);
	for ($L;$L<=$LEN;++$L)$STR.=' ';
}


$LIST_QUERIES=array();



function verifyJobParent($JOB_ID)
{
	global $GLB_TREE;
	global $GLB_RUN_JOBS;
	$CURRENT_JOB=$GLB_TREE[$JOB_ID];
	$CURRENT_JOB_TIMESTAMP=max($CURRENT_JOB['TIME']['DEV'],$CURRENT_JOB['TIME']['CHECK']);


	$STR="";
	/// If the job is running, we cannot run that job
	if (in_array($JOB_ID,$GLB_RUN_JOBS)) return array(false,'JOB_RUNNING'."\n");

	// If the parent job is running, we cannot run the current job
	foreach ($CURRENT_JOB['REQUIRE'] as &$P_ID)		
	{
		if (!in_array($P_ID,$GLB_RUN_JOBS))continue;
		$STR.="\t".$GLB_TREE[$P_ID]['NAME']."\tRUNNING\n";
		return array(false,$STR);	
	}
	
	/// Check the List of IDs that must have been successfully run at least once.
	/// If one of them is not done, then we cannot run the current job
	foreach ($CURRENT_JOB['REQ_UPDATED'] as &$P_ID)
	{
		if ($GLB_TREE[$P_ID]['TIME']['DEV']!=-1)continue;
		$STR.="\t".$GLB_TREE[$P_ID]['NAME']."\tNEED TO BE RUN FIRST\n";
		return array(false,$STR);
	}

	$VALID_RUN=true;
	$VALID_REQUIREMENTS=true;
	
	foreach ($CURRENT_JOB['REQUIRE'] as &$P_ID)		
	{
		$PARENT_JOB=$GLB_TREE[$P_ID];
		$PARENT_TIME=$PARENT_JOB['TIME']['DEV'];
		// If the parent job is disabled, then we ignore that parent job
		if ($PARENT_JOB['ENABLED']=='F'){
			$STR.="\t".$PARENT_JOB['NAME']."\tDISABLED\n";
			if ($CURRENT_JOB['REQ_RULE']=='C')
			{
				$STR.="\tALL PARENTS MUST BE DONE\n";
				return array(false,$STR);
			}
			continue;
		}
		if ($PARENT_TIME==-1)$VALID_RUN=false;
		/// If the current job was never run and the parent job was run
		/// or if the parent job is older than the current job
		if (($CURRENT_JOB['TIME']['DEV']==-1 && $PARENT_TIME!=-1)||
			 $PARENT_TIME> $CURRENT_JOB_TIMESTAMP)
		{
			
			$STR.="\t".$PARENT_JOB['NAME'].':'.date('Y-m-d H:i',$PARENT_TIME)." => OK\n";
			if ($CURRENT_JOB['REQ_RULE']=='A')
			{
				$STR.="\tANY UPDATED PARENT TRIGGER THE JOB => RUN \n";
				return array(true,$STR);
			}
		}
		/// Otherwise, we need that parent job to run first
		else
		{
			$STR.="\t".$GLB_TREE[$P_ID]['NAME'].':'.date('Y-m-d H:i',$PARENT_TIME)."\tNEEDED\n";
			$VALID_REQUIREMENTS=false;
		}
	}

	if ($CURRENT_JOB['REQ_TRIGGER']!=array() && $VALID_RUN)
	{
		$N_TRIG=0;
		foreach ($CURRENT_JOB['REQ_TRIGGER'] as &$TRIGGER_ID)
		{
			
			$TRIGGER_JOB=$GLB_TREE[$TRIGGER_ID];
			$STR.="\t".$TRIGGER_JOB['NAME'].':'.date('Y-m-d H:i',$TRIGGER_JOB['TIME']['DEV'])."\tTRIGGER JOB\n";
			if ($TRIGGER_JOB['TIME']['DEV']!=-1 && $TRIGGER_JOB['TIME']['DEV']>$CURRENT_JOB_TIMESTAMP)
			{
				
				return array(true,$STR);
			}else $N_TRIG++;
		}
		if ($N_TRIG==count($CURRENT_JOB['REQ_TRIGGER']))
		{
			
			if (!($VALID_REQUIREMENTS && $CURRENT_JOB['REQUIRE']!=array()))
			{
				$STR.="\t\tFULLY UP TO DATE\n";
				$VALID_REQUIREMENTS=false;
			}
		}
	}

	return array($VALID_REQUIREMENTS,$STR);
}

$TIME_QUERY=microtime_float();



//// Monitoring part, running an infinite loop
do
{
	$CURR_LEVEL=0;
$STR_SPECS='';
	$STR_LOG='## BIORELS MONITORING SCRIPT'."\nDate/Time:".date('l jS \of F Y h:i:s A')."\n";

	///Listing all jobs, based on the tree->starting with low level to higher levels
	foreach ($GLB_TREE_LEVEL as $LEV=>$LIST_JOBS)
	{
		$STR_SPECS.="############################################# LEVEL ";
		if ($LEV <10)$STR_SPECS.=' ';
		$STR_SPECS.=$LEV; 
		$STR_SPECS.=" #############################################\n";
	
		foreach ($LIST_JOBS as &$JOB_ID)
		{
			$JOB_INFO=&$GLB_TREE[$JOB_ID];
			if ($JOB_INFO['ENABLED']=='F')continue;
			/// Prepare Job information:
			$STR=$LEV;level($STR,6);
			$STR.=$JOB_ID;level($STR,13);
			$STR.=$JOB_INFO['NAME'];level($STR,30);
			$STR.=$JOB_INFO['TIME']['DEV_DIR'];level($STR,41);
			if (substr($JOB_INFO['NAME'],0,8)=='process_'){$STR_SPECS.=$STR."\tPROCESS JOB - SKIP\n";continue;}
			/// Find out if the job is currently running:
			$IS_RUNNING=false;
			if (in_array($JOB_ID,$GLB_RUN_JOBS)) {$STR.='Y';$IS_RUNNING=true;}else $STR.='N';level($STR,43);

			$CURR_TIMESTAMP=time();
			/// Check if the job is ready for update:
			$MAX_T=max($JOB_INFO['TIME']['DEV'],$JOB_INFO['TIME']['CHECK']);

			$STR.= date('Y-m-d H:i',$MAX_T); level($STR,61);

			
			/// IF update is depending on (P)arent, we check the parents:
			if ($JOB_INFO['FREQ']=='P')
			{
			
				$results=verifyJobParent($JOB_ID);
				$STR_SPECS.=$STR."\n".$results[1];
				if (!$results[0])continue;
			}
			else if ($JOB_INFO['FREQ'][0]=="W") 
			{
				$time=substr($JOB_INFO['FREQ'],1);
				$req_time=$time*7*3600*24;//7 days x 24h x 3600 s

				if ($CURR_TIMESTAMP < $MAX_T+$req_time)
				{
					$STR_SPECS.= $STR."\tNEXT SUBMISSION: ".date('Y-m-d H:i',$MAX_T+$req_time)."\n";;
					continue;
				}
				
			}else if ($JOB_INFO['FREQ'][0]=="D") 
			{
				$time=(int)substr($JOB_INFO['FREQ'],1);
				
				$req_time=$time*3600*24;// 24h x 3600 s

				if ($CURR_TIMESTAMP < $MAX_T+$req_time)
				{
					$STR_SPECS.= $STR."\tNEXT SUBMISSION: ".date('Y-m-d H:i',$MAX_T+$req_time)."\n";;
					continue;
				}
				$results=verifyJobParent($JOB_ID);
				$STR_SPECS.=$STR."\n".$results[1];
				if (!$results[0])continue;
				
			}else if ($JOB_INFO['FREQ'][0]=="M") 
			{
				$time=substr($JOB_INFO['FREQ'],1);
				$req_time=$time*3600*24*30.5;// 24h x 3600 s

				if ($CURR_TIMESTAMP < $MAX_T+$req_time)
				{
					$STR_SPECS.= $STR."\tNEXT SUBMISSION: ".date('Y-m-d H:i',$MAX_T+$req_time)."\n";;
					continue;
				}
				
			}else if ($JOB_INFO['FREQ'][0]=="H") 
			{
				$time=substr($JOB_INFO['FREQ'],1);
				$req_time=$time*3600;// 24h x 3600 s

				if ($CURR_TIMESTAMP < $MAX_T+$req_time)
				{
					$STR_SPECS.= $STR."\tNEXT SUBMISSION: ".date('Y-m-d H:i',$MAX_T+$req_time)."\n";;
					continue;
				}
				
			}
			else 
			{
				/// The last possibility is that the job is submitted at a specific time
				// $JOB_INFO['FREQ'] will be of the format HH:MM
				/// so we break it down into hours and minutes
				$time=explode(":",$JOB_INFO['FREQ']);
				
				/// We then calculate the timestamp for the next submission, based on the current day at midnight
				$req_time=strtotime('today midnight')+$time[0]*3600+$time[1]*60;
				
				// if the current job timestamp is higher than the next timestamp submission
				// then it means it was already submitted today, so we skip it
				if ($MAX_T > $req_time)
				{
					$STR_SPECS.= $STR."\tNEXT SUBMISSION: ".date('Y-m-d H:i',$req_time)."\n";;
					continue;
				}
				/// If the current timestamp is lower than the next submission timestamp
				/// it means we need to wait until the next submission time
				
				if (strtotime("now")<$req_time)
				{
					$STR_SPECS.= $STR."\tHOLD UNTIL SUBMISSION: ".date('Y-m-d H:i',$req_time)."\n";;
					continue;
				}
				
			}
			/// Some jobs can be concurrent to each other.
			/// Meaning they are not direct parent but need the data.
			$HAS_CONCURRENT=false;
			foreach ($GLB_RUN_JOBS as $QSUB_ID=>$JOB_C_ID)
			{
				if (in_array($JOB_C_ID,$JOB_INFO['CONCURRENT']))
				{
					$STR_SPECS.= $STR."\tCONCURRENT JOB RUNNING ".$GLB_TREE[$JOB_C_ID]['NAME']."- WAIT \n";
					$HAS_CONCURRENT=true;break;
				}
			}
			if ($HAS_CONCURRENT)continue;
			if ($JOB_INFO['FAILED']>=3) {
				$STR_LOG.=$JOB_ID.":".$JOB_INFO['NAME']." FAILED \n";
				$STR_SPECS.=$STR."\tJOB FAILED ".$JOB_INFO['FAILED']." TIMES - HOLD \n";
				continue;
			}
	
			/// If the job is ready to run, we check if it is enabled (as set in config file)
			if ($JOB_INFO['ENABLED']=='T'){
					
				if (!$IS_RUNNING){
					$STR_SPECS.=$STR."\tSUBMIT\n";
					if ($JOB_INFO['RUNTIME']=='S')$STR_LOG.=submit_qengine($JOB_ID);
					else
					{
						$GLB_RUN_JOBS[call_user_func($JOB_INFO['NAME'])]=$JOB_ID;
						sleep(10);
						refreshJobFile();
						
						
	
					 }
				}else $STR_SPECS.=$STR."\tRUNNING\n";
			}
			 


		}
	}
	for ($I=19;$I>=0;--$I)
	{
		$PREV='MONITOR_STATUS';
		if ($I>0)$PREV.='_'.$I;
		$NEXT='MONITOR_STATUS_'.($I+1);
		if (is_file($TG_DIR.'/BACKEND/LOG/'.$PREV))rename($TG_DIR.'/BACKEND/LOG/'.$PREV,$TG_DIR.'/BACKEND/LOG/'.$NEXT);
	}
	$fpK=fopen($TG_DIR.'/BACKEND/LOG/MONITOR_STATUS','w');
	if ($fpK)	fputs($fpK,$STR_SPECS);
	fclose($fpK);
	loadTimestamps();
	
	
	/// Once we checked every job to see if we need to submit them, we check their status
	$STR_LOG.=monitor_qengine();
	//$STR_LOG.=$STR_SPECS;
	
	/// Then we pause the process depending on whether a job is running or not.
	//if (count($GLB_RUN_JOBS)>0)	
	$time=microtime_float();
	do
	{
		$STR_LOG.=monitorWebJob();
		sleep(5);
	}while(microtime_float()-$time<$GLB_VAR['CHECK_RUN']);
	//sleep($GLB_VAR['CHECK_RUN']);
	// else if (count($ENDED_JOBS)>0)	sleep($GLB_VAR['CHECK_RUN']);
	// else				sleep($GLB_VAR['CHECK_ITER']);///
	//$STR_LOG.=$STR_SPECS;
	$res=runQuery('SELECT job_name, current_dir,processed_date,last_check_date, run_date, time_run_sec, bj.is_success, error_msg 
	FROM biorels_timestamp bt, biorels_job_history bj
	 where bj.br_timestamp_id = bt.br_timestamp_id 
	 ANd run_Date BETWEEN NOW() - INTERVAL \'24 HOURS\' AND NOW() ORDER By run_date DESC LIMIT 20');
	$STR_LOG.="## LAST RUN JOBS: ##\n";
	$STR_LOG.="\tjob_id\tjob_name\tcurrent_dir\tprocessed_date\tlast_check_date\trun_date\ttime_run_sec\tis_success\n";
	foreach ($res as $line)
	{
		
		$error_msg=$line['error_msg'];
		unset($line['error_msg']);
		$STR_LOG.="\t".getJobIDByName($line['job_name'])."\t".implode("\t",$line)."\n";
		if ($error_msg!='')$STR_LOG.="\t\t=> ERROR: ".$error_msg."\n";
	}


	$N_PREV_LINES=$N_LINES;
	$N_LINES=count(explode("\n",$STR_LOG));
	echo removeLine($N_PREV_LINES).$STR_LOG."\n";
	
	$DIFF_TIME_QUERY=microtime_float()-$TIME_QUERY;
	if ($DIFF_TIME_QUERY< 1200)continue;
	$DIFF_TIME_QUERY=microtime_float();
	foreach ($LIST_QUERIES as $Q)runQueryNoRes($Q);


}while(1);



?>
