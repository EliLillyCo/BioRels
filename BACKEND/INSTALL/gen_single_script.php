<?php

/**
 SCRIPT NAME: monitor_jobs
 PURPOSE:     Run and monitor biorels processes
*/

//////////////////////////////////


/// TG_DIR should be already set in environment variables using setenv.sh
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR parameter found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);

require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');


	/// Verification that previous jobs are over or not.
preloadJobs();
preloadWebJobs();
 checkTree();
 genHierarchy();

	$CURR_LEVEL=0;


	///Listing all jobs, based on the tree->starting with low level to higher levels
	foreach ($GLB_TREE_LEVEL as $LEV=>$LIST_JOBS)
	{
		
		foreach ($LIST_JOBS as &$JOB_ID)
		{
			$JOB_INFO=&$GLB_TREE[$JOB_ID];
			if ($JOB_INFO['ENABLED']=='F')continue;
			$FPATH=$TG_DIR.'/'.$GLB_VAR['BACKEND_DIR'].'/CONTAINER_SHELL/'.$JOB_INFO['NAME'].'.sh';
			echo 'echo "JOB '.$JOB_ID.' '.$JOB_INFO['NAME'].' '.$FPATH.'"'."\n";
			$outfile=$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.o';
			$errfile=$TG_DIR.'/BACKEND/LOG/SGE_LOG/TG_'.$JOB_ID.'_'.date("Y_m_d_H_i_s").'.e';
			$command=sprintf("sh %s > %s 2>%s ", $FPATH, $outfile, $errfile);
			echo $command."\n";
		}
	}
	



?>
