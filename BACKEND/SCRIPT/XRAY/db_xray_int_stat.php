<?php

/**
 SCRIPT NAME: db_xray_int_stat
 PURPOSE:     Look up process entries to check if any uniprot record is missing
 
*/
error_reporting(E_ALL);
$JOB_NAME='db_xray_int_stat';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
require_once($TG_DIR.'/BACKEND/SCRIPT/XRAY/xray_functions.php');
addLog("Access directory");
$CK_INFO=$GLB_TREE[getJobIDByName('rmj_xray')];

	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';  		 if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$E_DIR=$W_DIR.'/ENTRIES';
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];


	
	$LAST_RUN_DATE=$JOB_INFO['TIME']['DEV_DIR'];
	

	$res=runQuery("SELECT MAX(xr_prot_int_stat_id) as co FROM xr_prot_int_stat");
	if ($res===false)failProcess($JOB_ID."005",'Unable to get max xr protint stat id');
	$MAX_ID = $res[0]['co'];
	$MAX_DOM_COV_ID=0;
	$res=runQuery("SELECT MAX(xr_prot_dom_cov_id) as co FROM xr_prot_dom_cov");
	if ($res===false)failProcess($JOB_ID."006",'Unable to get max xr_prot_dom_cov_id');
	$MAX_DOM_COV_ID = $res[0]['co'];
	$MAX_UN_ID=0;
	$res=runQuery("SELECT MAX(xr_prot_stat_id) as co FROM xr_prot_stat");
	if ($res===false)failProcess($JOB_ID."007",'Unable to get max xr prot stat id');
	$MAX_UN_ID = $res[0]['co'];
#TODO need to change xchum->xchpm
	$query="SELECT DISTINCT prot_entry_id FROM xr_chain xc,prot_seq us, xr_ch_prot_map xchum, xr_entry xe, xr_status xs,xr_jobs xj
	WHERE xe.xr_entry_id = xs.xr_entry_id AND xs.xr_job_id = xj.xr_job_id AND xc.xr_entry_id =xe.xr_entry_id AND xc.xr_chain_id  = xchum.xr_chain_id
	AND us.prot_seq_id = xchum.prot_seq_id 
	AND xr_job_name='blastp_load' AND status_value='OK'";
	//if ($LAST_RUN_DATE!=-1)$query.=" AND DATE_PROCESSED >=to_date('".$LAST_RUN_DATE."', 'yyyy-mm-dd')";
	$res=runQuery($query);
	if ($res===false)failProcess($JOB_ID."008",'Unable to run query');
echo count ($res);
	foreach ($res as $N=>$line)
	{
		echo $N."/".count($res)."\t";
		runQueryNoRes("DELETE FROM xr_prot_int_stat WHERE prot_entry_id = ".$line['prot_entry_id']);
		$res2=runQuery("SELECT usp.prot_seq_pos_id,xi.xr_inter_type_id,class,atom_list_1,COUNT(xr_inter_res_id) as co FROM xr_ch_prot_pos xchum,  prot_seq us,prot_seq_pos usp, xr_res xr, xr_inter_res xi, xr_res xr2, xr_tpl_res xtr
		WHERE us.prot_seq_id = usp.prot_seq_id
		AND usp.prot_seq_pos_id = xchum.prot_seq_pos_id
		AND prot_entry_id = ".$line['prot_entry_id']."
		AND xr.xr_res_id = xchum.xr_res_id
		AND xi.xr_res_id_1 = xr.xr_res_id
		AND xi.xr_res_id_2 = xr2.xr_res_id
		AND xr2.xr_tpl_res_id=xtr.xr_tpl_res_id
		AND xtr.class NOT IN ('AA','MOD_AA') GROUP BY usp.prot_seq_pos_id,xi.xr_inter_type_id,class,atom_list_1 ORDER BY  usp.prot_seq_pos_id ASC");
echo count($res2)."\t";

		foreach ($res2 as $line2)
		{
			++$MAX_ID;
			runQueryNoRes(
				"INSERT INTO xr_prot_int_stat (xr_prot_int_stat_id,
				prot_entry_id,
				prot_seq_pos_id,
				xr_inter_type_id,
				class,
				count_int,
				atom_list) VALUES (".$MAX_ID.",".$line['prot_entry_id'].",".$line2['prot_seq_pos_id'].",".
				$line2['xr_inter_type_id'].",'".$line2['class']."',".$line2['co'].",'".str_replace("'","''",$line2['atom_list_1'])."')");


			
		}

		runQueryNoRes("DELETE FROM xr_prot_dom_cov WHERE prot_dom_id IN (SELECT prot_dom_id FROM prot_dom ud WHERE  ud.prot_entry_id =".$line['prot_entry_id'].")");
		$res2=runQuery("SELECT ROUND(COUNT(xr.xr_res_id)/(pos_end-pos_start+1)*100,3) as range_v, xr.xr_chain_id,ud.prot_dom_id 
		FROM xr_res xr, xr_ch_prot_pos xchum,  prot_dom ud, prot_dom_seq us
		WHERE xr.xr_res_id = xchum.xr_res_id
		AND xchum.prot_seq_pos_id = us.prot_seq_pos_id
		AND us.prot_dom_id = ud.prot_dom_id 
		AND ud.prot_entry_id =".$line['prot_entry_id']." GROUP BY  xr.xr_chain_id,ud.prot_dom_id,pos_start,pos_end");
		foreach ($res2 as $line2)
		{
			++$MAX_DOM_COV_ID;
			runQueryNoRes(
								"INSERT INTO xr_prot_dom_cov (xr_prot_dom_cov_id,
								prot_dom_id,
								xr_chain_id,
								coverage) VALUES (".$MAX_DOM_COV_ID.",".$line2['prot_dom_id'].",".$line2['xr_chain_id'].",".
								$line2['range_v'].")");
		}

		runQueryNoRes("DELETE FROM xr_prot_stat WHERE prot_entry_id=".$line['prot_entry_id']);

		$res2=runQuery("SELECT COUNT(*) co FROM prot_seq us, xr_ch_prot_map xchum
		WHERE us.prot_seq_id = xchum.prot_seq_id AND prot_entry_id = ".$line['prot_entry_id']);

		foreach ($res2 as $line2)
		{
			++$MAX_UN_ID;
			runQueryNoRes(
								"INSERT INTO xr_prot_stat (xr_prot_stat_id,
								prot_entry_id,
								COUNT) VALUES (".$MAX_UN_ID.",".$line['prot_entry_id'].",".$line2['co'].")");
		}

		

		echo "END\n";
	}



	successProcess();
?>
