<?php



if (!isset($TG_DIR))die();


function updateWebJobStatus($MD5_HASH,$LOG_STR)
{
global $STATUS_INFO;
echo $LOG_STR."\n";
	$STATUS_INFO['LOG'][]=array($LOG_STR,date("F j, Y, g:i a"));
runQueryNoRes("Update web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."' WHERE md5id = '".$MD5_HASH."'");
echo "Update web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."' WHERE md5id = '".$MD5_HASH."'"."\n";



}

function cleanWebJobDoc($MD5_HASH)
{
	runQueryNoRes("DELETE FROM web_job_document where web_job_id IN (SELECT web_job_id FROM web_job where md5id='" . $MD5_HASH . "')");
}

function uploadWebJobDoc($MD5_HASH,$DOC_NAME,$DOC_TYPE,$FILE_CONTENT,$FILE_DESC)
 {
	if ($FILE_CONTENT=='')return;
	global $DB_CONN;
	$query = "INSERT INTO web_job_document (web_job_document_id,document_name,document_content,document_description,document_hash,create_date,mime_type,web_job_id) VALUES
	(nextval('web_job_document_sq'),
	'" . str_replace("'","''",$DOC_NAME) . "',
	:document_content,
	:document_description,
	'" . md5($FILE_CONTENT) . "',
	CURRENT_TIMESTAMP,
	'" . $DOC_TYPE . "',
	(SELECT web_job_id FROM web_job where md5id='" . $MD5_HASH . "')
	) ";


$stmt = $DB_CONN->prepare($query);

//echo strlen($content)."\n";
$stmt->bindParam(':document_content', $FILE_CONTENT, PDO::PARAM_LOB);
$stmt->bindParam(':document_description', $FILE_DESC, PDO::PARAM_STR);
$stmt->execute();
 }

function  failedWebJob($MD5_HASH,$LOG_INFO,$STOP_JOB=true)
{
	$STATUS_INFO['STATUS']='Failed';
$STATUS_INFO['LOG'][]=array($LOG_INFO,date("F j, Y, g:i a"));
runQueryNoRes("UPDATE web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."', time_end=CURRENT_TIMESTAMP WHERE md5id = '".$MD5_HASH."'");
if ($STOP_JOB)exit(0);
}
function monitorWebJob()
{

	global $GLB_RUN_WEBJOBS;
	
	global $GLB_VAR;
$STR_LOG= "CHECK JOBS\n";
	

	$val=array();
	exec('qstat | egrep "('.$GLB_VAR['WEBJOB_PREFIX'].'_)" ',$val);
	$CHECK=$GLB_RUN_WEBJOBS;
	$N_CURR_JOB=count($val);
	foreach ($val as $line)
	{
		$tab=array_values(array_filter(explode(" ",$line)));
		$ID=$tab[0];
		if (isset($CHECK[$ID]))
		{	$STR_LOG.= "CURRENTLY RUNNING: ".$ID."\t".$CHECK[$ID]."\n";
			unset($CHECK[$ID]);
		}

	}
	$ENDED_JOB=array();
	

	foreach ($CHECK as $QSTAT_ID=>$JOB_ID)
	{
		$ENDED_JOB[]=$JOB_ID;
	runQueryNoRes("UPDATE web_job SET time_end = CURRENT_TIMESTAMP WHERE md5id = '".$JOB_ID."' AND time_end is null"); 
		//echo "UPDATE web_job SET time_end = CURRENT_TIMESTAMP WHERE md5id = '".$JOB_ID."'"."\n";
		unset($GLB_RUN_WEBJOBS[$QSTAT_ID]);
	}

	$res=runQuery("SELECT  md5id, job_name FROM web_job WHERE time_end IS NULL AND job_cluster_id IS null");
	
	foreach ($res as $line)
	{
		++$N_CURR_JOB;
		if ($N_CURR_JOB>=$GLB_VAR['WEBJOB_LIMIT'])  {echo "REACHED LIMIT OF ".$GLB_VAR['WEBJOB_LIMIT']."\n";break;}
		$STR_LOG.= "SUBMITTING ".$line['md5id'].' '.$line['job_name']."\n";
		
		webjob_submit($line['md5id'],$line['job_name']);
	}
	return $STR_LOG;

}


function webjob_submit($JOB_ID,$JOB_NAME)
{
	global $GLB_RUN_WEBJOBS;
	
	global $GLB_VAR;
	global $TG_DIR;
    
    echo "SUBMISSION ".$JOB_NAME."\n";
	$FPATH=$TG_DIR.'/'.$GLB_VAR['BACKEND_DIR'].'/CONTAINER_SHELL/wj_'.$JOB_NAME.'.sh';
//	$FPATH=$TG_DIR.'/'.$GLB_VAR['SCRIPT_DIR'].'/SHELL/'.$JOB_INFO['NAME'].'.sh';
	if (!checkFileExist($FPATH))
	{
		failedWebJob($JOB_ID,"Unable to find script - Please contact admin",false);
		sendMail('Unable to find script '.$FPATH,'Please contact admin');
		return;
	}
	if ($JOB_NAME=='send_newsmail')
	{
		echo "SUBMITTING SEND NEWSMAIL ".$JOB_ID."\n";
			exec("timeout 30s php ".$TG_DIR."/BACKEND/SCRIPT/WEBJOBS/wj_send_newsmail.php ".$JOB_ID,$res,$returnc_ide);
			print_R($res);
			$CLUSTER_ID=-1;
	}
	else
	{

	
		$ADD_DESC='';
		
		$query='qsub -v TG_DIR -o '.$TG_DIR.'/BACKEND/LOG/WEB_LOG/'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o -e '.$TG_DIR.'/BACKEND/LOG/WEB_LOG/'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e -N '.$GLB_VAR['WEBJOB_PREFIX'].'_'.$JOB_ID.' '.$ADD_DESC.' '.$FPATH.' '.$JOB_ID;
		echo $query;
		exec($query,$res,$return_code);
		if ($return_code!=0) 
		{
			failProcess($JOB_ID."002","Unable to submit job ".$query);
		}
		$tab=array_values(array_filter(explode(' ',$res[0])));
		$CLUSTER_ID=$tab[2];
	}	
	runQueryNoRes("UPDATE web_job SET job_cluster_id = '".$CLUSTER_ID."' WHERE md5id = '".$JOB_ID."'");
	$GLB_RUN_WEBJOBS[$CLUSTER_ID]=$JOB_ID;

	
}


function preloadWebJobs()
{
	global $TG_DIR;
	global $GLB_RUN_WEBJOBS;
	$res=runQuery("SELECT job_cluster_id, md5id FROM web_job WHERE time_end IS NULL AND job_cluster_id!=null");
	foreach ($res as $line)
	{
		$GLB_RUN_WEBJOBS[$line['job_cluster_id']]=$line['md5id'];
	}
}


?>