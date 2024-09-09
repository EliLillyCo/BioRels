<?php

if (!isset($TG_DIR))die();


function loadTimestamps()
{

	global $TG_DIR;
	global $GLB_TREE;
	global $GLB_TIME;
	global $GLB_VAR;
	//$MAX_PUBLIC_ID=-1;
	//$MAX_PRIVATE_ID=-1;

	$MAP=array();
	foreach ($GLB_TREE as $ID=>$T)
	{
		if (!isset($T['NAME']))die('Name not defined for '.$ID.'.'.print_r($T,true));
		$MAP[$T['NAME']]=$ID;
	}

 	if (!isset($GLB_VAR['PUBLIC_SCHEMA']))sendKillMail('000050','No PUBLIC_SCHEMA defined ');
	$res=runQuery("SELECT * FROM ".$GLB_VAR['PUBLIC_SCHEMA'].".biorels_timestamp");
	foreach ($res as $line){
		
	//$MAX_PUBLIC_ID=max($MAX_PUBLIC_ID,$line['br_timestamp_id']);
	if (!isset($MAP[$line['job_name']]))die ('Unable to find '.$line['job_name'].' in GLB_TREE');
	$GLB_TREE[$MAP[$line['job_name']]]['TIME']=array(
		'DEV'=>($line['processed_date']=='')?'-1':strtotime($line['processed_date']),
		'DEV_DIR'=>($line['current_dir']=='')?'-1':$line['current_dir'],
		'CHECK'=>($line['last_check_date']=='')?'-1':strtotime($line['last_check_date']));
	}

	if ($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
		if (!isset($GLB_VAR['SCHEMA_PRIVATE']))sendKillMail('000050','No SCHEMA_PRIVATE defined ');
	$res=runQuery("SELECT * FROM ".$GLB_VAR['SCHEMA_PRIVATE'].".biorels_timestamp");
	foreach ($res as $line)
	{
		if (!isset($MAP[$line['job_name']]))die ('Unable to find '.$line['job_name'].' in GLB_TREE');
	//	$MAX_PRIVATE_ID=max($MAX_PRIVATE_ID,$line['br_timestamp_id']);
	$GLB_TREE[$MAP[$line['job_name']]]['TIME']=array(
		'DEV'=>($line['processed_date']=='')?'-1':strtotime($line['processed_date']),
		'DEV_DIR'=>($line['current_dir']=='')?'-1':$line['current_dir'],
		'CHECK'=>($line['last_check_date']=='')?'-1':strtotime($line['last_check_date']));
	}
	}

	foreach ($GLB_TREE as $JOB_ID=> &$INFO)
	{

		if (isset($INFO['TIME']))continue;
		if ($INFO['IS_PRIVATE']==1)
		{
			//++$MAX_PRIVATE_ID;
			$query='INSERT INTO '.$GLB_VAR['SCHEMA_PRIVATE'].".biorels_timestamp ( br_timestamp_id,job_name) VALUES (".$JOB_ID.",'".$INFO['NAME']."')";
			if (!runQueryNoRes($query))sendKillMail('000050','Unable to insert in '.$GLB_VAR['SCHEMA_PRIVATE'].".biorels_timestamp");
		}
		else
		{
			//++$MAX_PUBLIC_ID;
			$query='INSERT INTO '.$GLB_VAR['PUBLIC_SCHEMA'].".biorels_timestamp ( br_timestamp_id,job_name) VALUES (".$JOB_ID.",'".$INFO['NAME']."')";
			if (!runQueryNoRes($query))sendKillMail('000050','Unable to insert in '.$GLB_VAR['PUBLIC_SCHEMA'].".biorels_timestamp");
		}
		$INFO['TIME']=array('DEV'=>-1,'DEV_DIR'=>-1,'CHECK'=>-1);
	}
}


function loadTimestampfile($PR_FILE)
{
	global $TG_DIR;
	global $GLB_TREE;
	global $GLB_TIME;
	global $GLB_VAR;

	$fp=fopen($PR_FILE,'r');
	if (!$fp)sendKillMail('000053','Unable to open file '.$PR_FILE);

	$MAP=array();
	foreach ($GLB_TREE as $ID=>$T)$MAP[$T['NAME']]=$ID;
// $MAX_PRIVATE_ID=0;
// $MAX_PUBLIC_ID=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		
		if (substr($line,0,1)=="#"||$line=="")continue;
		/// Break the line by tabulation and remove column with null values:
		$tab=array_values(array_filter(explode("\t",$line)));

		if (count($tab)!=4)		sendKillMail('000054',$line.' only has '.count($tab).' columns');
		if (!isset($MAP[$tab[0]]))	sendKillMail('000055','Job name '.$tab[0].' does not exists');
		if (!is_numeric($tab[1]))sendKillMail('000056',$tab[1].' is not numeric for column 1 in line '.$line);


		// if ($GLB_TREE[$MAP[$tab[0]]]['IS_PRIVATE']==1)
		// {
		// 	++$MAX_PRIVATE_ID;
		// 	//$query='INSERT INTO '.$GLB_VAR['SCHEMA_PRIVATE'].".biorels_timestamp ( br_timestamp_id,job_name,processed_date,current_dir,last_check_date) VALUES (".$MAX_PRIVATE_ID.",'".$tab[0]."','".date('Y/m/d/H:i:s',$tab[1])."','".$tab[2]."','".date('Y/m/d/H:i:s',$tab[3])."')";
		// 	$query='INSERT INTO '.$GLB_VAR['SCHEMA_PRIVATE'].".biorels_timestamp ( br_timestamp_id,job_name,processed_date,current_dir,last_check_date) VALUES (".$MAX_PRIVATE_ID.",'".$tab[0]."','".date('Y-m-d H:i:s',$tab[1])."','".$tab[2]."','".date('Y-m-d H:i:s',$tab[3])."')";
 		// 	if (!runQueryNoRes($query))sendKillMail('000050','Unable to insert in '.$GLB_VAR['SCHEMA_PRIVATE'].".biorels_timestamp");
		// }
		// else 
		// {
		// 	++$MAX_PUBLIC_ID;
		// 	$query='INSERT INTO '.$GLB_VAR['PUBLIC_SCHEMA'].".biorels_timestamp ( br_timestamp_id,job_name,processed_date,current_dir,last_check_date) VALUES (".$MAX_PUBLIC_ID.",'".$tab[0]."','".date('Y-m-d H:i:s',$tab[1])."','".$tab[2]."','".date('Y-m-d H:i:s',$tab[3])."')";
 		// 	if (!runQueryNoRes($query))sendKillMail('000050','Unable to insert in '.$GLB_VAR['PUBLIC_SCHEMA'].".biorels_timestamp");
		// }

		$GLB_TREE[$MAP[$tab[0]]]['TIME']=array('DEV'	=>$tab[1],
						       'DEV_DIR'=>$tab[2],
						       'CHECK'	=>$tab[3]);
	}
	fclose($fp);
}



function refreshTimestamp($JOB_ID,$STATUS)
{
	//addLog("REFRESH TIMESTAMP ".$JOB_ID.' -> '.$STATUS);
	global $TG_DIR;
	global $GLB_TREE;
	global $GLB_TIME;
	global $GLB_VAR;


$JOB_INFO=$GLB_TREE[$JOB_ID];
$query='UPDATE ';
if ($JOB_INFO['IS_PRIVATE']==1)$query.=$GLB_VAR['SCHEMA_PRIVATE'];else $query.=$GLB_VAR['PUBLIC_SCHEMA'];

/// We only update the processed date and directory if the job was successful 
$SUCCESS_INFO='';
if ($STATUS== 'Y'||$STATUS=='T')$SUCCESS_INFO="processed_date='".date('Y-m-d H:i:s',$JOB_INFO['TIME']['DEV'])."' ,current_dir='".$JOB_INFO['TIME']['DEV_DIR']."',";
$query.=".biorels_timestamp SET ".$SUCCESS_INFO." is_success='".$STATUS."', last_check_date='".date('Y-m-d H:i:s',$JOB_INFO['TIME']['CHECK'])."' WHERE job_name='".$JOB_INFO['NAME']."'";
//echo $query."\n";
if (!runQueryNoRes($query))sendKillMail('000060','Failed to update timestamp '."\n".$query);


}


#Column 1:	FILE name
#column 2:	development date
#column 3:	development dir
#column 4:	Production date
#column 5:	Production dir
#column 6:	Last check date
?>