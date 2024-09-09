<?php

if (!isset($TG_DIR))die();

function monitor_qengine()
{
	global $GLB_RUN_JOBS;
	global $GLB_TREE;
	global $GLB_VAR;

	$val=array();
		
	if ($GLB_VAR['MONITOR_TYPE']=='CLUSTER')
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
		
	
	}else die('Unknown monitor type');




	$CHECK=$GLB_RUN_JOBS;
	$STR_LOG='';
	foreach ($val as $line)
	{
		$tab=array_values(array_filter(explode(" ",$line)));
	
		$ID=$tab[0];
		//echo "\tTEST ".$ID."\n";
		if (isset($CHECK[$ID]))
		{	
			
			
			//echo "IN\t".$ID."\n";
			$STR_LOG.= "CURRENTLY RUNNING: ".$ID."\t".$CHECK[$ID]."\t".$GLB_TREE[$CHECK[$ID]]['NAME']."\n";
			unset($CHECK[$ID]);
		}

	}

	$ENDED_JOB=array();
	

	foreach ($CHECK as $QSTAT_ID=>$JOB_ID)
	{
		$ENDED_JOB[]=$JOB_ID;
		unset($GLB_RUN_JOBS[$QSTAT_ID]);
	}
	foreach ($ENDED_JOB as $QID=>&$JOB_ID)
	{
		$JOB_INFO=&$GLB_TREE[$JOB_ID];
		$DD=getcwd();
		//print_R($JOB_INFO);
		if ($JOB_INFO['RUNTIME']!='S') 
		{
			$STR_LOG.="CALLING FUNCTION ".$JOB_INFO['NAME'].'_term'."\n";
			call_user_func($JOB_INFO['NAME'].'_term');
		}
		chdir($DD);
		$STR_LOG.=qengine_validate($JOB_ID);

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


function submit_qengine($JOB_ID)
{
	global $GLB_RUN_JOBS;
	global $GLB_TREE;
	global $GLB_VAR;
	global $TG_DIR;
	$STR_LOG='';
	foreach ($GLB_RUN_JOBS as $QJOB=>$ID) if ($ID==$JOB_ID)return;
	
	$JOB_INFO=$GLB_TREE[$JOB_ID];
	$STR_LOG.= $JOB_ID.':'.$JOB_INFO['NAME']."\tSUBMISSION\n";
	$FPATH=$TG_DIR.'/'.$GLB_VAR['BACKEND_DIR'].'/CONTAINER_SHELL/'.$JOB_INFO['NAME'].'.sh';
//	$FPATH=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'].'/SHELL/'.$JOB_INFO['NAME'].'.sh';
	if (!checkFileExist($FPATH))die('Missing script file '.$FPATH);
	$ADD_DESC='';
	if ($JOB_INFO['MEM']!=-1)$ADD_DESC.=' -l m_mem_free='.$JOB_INFO['MEM'].'M   -l h_rss='.$JOB_INFO['MEM'].'M ';


	if ($GLB_VAR['MONITOR_TYPE']=='CLUSTER')
	{

	
		$query='qsub -v TG_DIR -o '.$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o -e '.$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e -N '.$GLB_VAR['JOB_PREFIX'].'_'.$JOB_ID.' '.$ADD_DESC.' '.$FPATH;
		exec($query,$res,$return_code);
		if ($return_code!=0) 
		{
			failProcess($JOB_ID."004","Unable to submit job ".$query);
		}
		$tab=array_values(array_filter(explode(' ',$res[0])));
		$GLB_RUN_JOBS[$tab[2]]=$JOB_ID;
	}
	else if ($GLB_VAR['MONITOR_TYPE']=='SINGLE')
	{
		if (count($GLB_RUN_JOBS)>=$GLB_VAR['SINGLE_PARALLEL'])return 'SINGLE PARALLEL LIMIT REACHED '.$GLB_VAR['SINGLE_PARALLEL']. "\n";
		$arr=array();
		$outfile=$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o';
		$errfile=$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e';
		$command=sprintf("sh %s > %s 2>%s & echo $!", $FPATH, $outfile, $errfile);
		exec($command,$arr);
		


		$PID=$arr[0];
		//echo "=>".$PID."\n";
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
		
		if ($FULL_PID=='')die('Unable to submit process');
		$GLB_RUN_JOBS[$FULL_PID]=$JOB_ID;
	
	}else die('Unknown monitor type');









	
	refreshJobFile();
	return $STR_LOG;
}

function qengine_log($JOB_ID)
{
	global $GLB_TREE;
	global $GLB_VAR;
	global $TG_DIR;
	$JOB_INFO=$GLB_TREE[$JOB_ID];
	echo "\tEND ".$JOB_ID."\t".$JOB_INFO['NAME']."\n";
	$LOG_FILE=$TG_DIR.'/'.$GLB_VAR['LOG_DIR'].'/'.$JOB_INFO['NAME'].'.log';
	echo "\tLOG: ".$LOG_FILE."\n";
	$PROCESS_DATA=array();
		if (!checkFileExist($LOG_FILE))	
		{
			echo "\tLOG NOT FOUND - JOB KILLED OR DIED\n";
			$GLB_TREE[$JOB_ID]['TIME']['CHECK']=time();
		}	
		else{

		$PROCESS_DATA=unserialize(file_get_contents($LOG_FILE));
		echo "\tSTATUS:".$PROCESS_DATA['STATUS']."\n";
		echo "\tPROCESS DIR:".$PROCESS_DATA['DIR']."\n";

		}
		
}
function qengine_validate($JOB_ID)
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

function refreshJobFile()
{
global $TG_DIR;
global $GLB_VAR;
	global $GLB_RUN_JOBS;
	$PATH=$TG_DIR.'/'.$GLB_VAR['MONITOR_DIR'].'/JOB_RUNNING.csv';
	if (!is_file($PATH))failProcess($JOB_ID."008",'Unable to find JOB_RUNNING at '.$PATH);
	$fp=fopen(	$TG_DIR.'/'.$GLB_VAR['MONITOR_DIR'].'/JOB_RUNNING.csv','w');
	if (!$fp) 						failProcess($JOB_ID."009",'Unable to open JOB_RUNNING ');
	foreach ($GLB_RUN_JOBS as $QID=>$JOB_ID)	fputs($fp,$QID."\t".$JOB_ID."\n");
	fclose($fp);
	
}








?>