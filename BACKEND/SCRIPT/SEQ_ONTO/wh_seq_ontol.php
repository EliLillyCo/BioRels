<?php


/**
 SCRIPT NAME: wh_seq_ontol
 PURPOSE:     Process the sequence ontology file and update the database
 
*/
$JOB_NAME='wh_seq_ontol';

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




addLog("Check directory");

	/// Get parent job information
	$CK_SEQ_ONTOL_INFO=$GLB_TREE[getJobIDByName('ck_seq_ontol')];
	
	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$CK_SEQ_ONTOL_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."002",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR))																failProcess($JOB_ID."003",'Unable to chdir to '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_SEQ_ONTOL_INFO['TIME']['DEV_DIR'];

	$F_FILE=$W_DIR.'/so.obo';				if (!checkFileExist($F_FILE))			failProcess($JOB_ID."004",'NO '.$F_FILE.' found ');
	
	$STATS=array('SO'=>0);



addLog("Loading existing data from table");
	

	$SQL='SELECT so_entry_Id, so_id, so_name,so_description FROM so_entry';
	$res=runQuery($SQL);
	if ($res===false)															failProcess($JOB_ID."005",'Return code failure ');

	/// Push all records from the database into $SEQ_ONTOL array
	$SEQ_ONTOL=array();
	$MAX_DBID=0;
	foreach ($res as $tab)
	{
		
		$SEQ_ONTOL[$tab['so_id']]=array(
			'DB'=>$tab['so_entry_id'],
			'NAME'=>$tab['so_name'],
			'DESC'=>$tab['so_description'],
			'STATUS'=>'DB');

			/// Get the max id
		if ($tab['so_entry_id']>$MAX_DBID)$MAX_DBID=$tab['so_entry_id'];
	}
	unset($res);
	
	//print_r($SEQ_ONTOL);

addLog("Process file");

	$fp=fopen($F_FILE,'r');	if (!$fp)												failProcess($JOB_ID."006",'Unable to open '.$F_FILE);
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");
		/// A record in the file is a block of line, starting with [Term] and end with an empty line
		if ($line!='[Term]')continue;
		$STATS['SO']++;
		$CURR_ID=-1;
		$so_id='';
		do
		{
			$line=stream_get_line($fp,10000,"\n");
			if ($line=='')break;/// Emtpy line -> end of record
			
			/// Extract header from value
			$pos=strpos($line,':');
			
			$head=substr($line,0,$pos);
			$val=substr($line,$pos+2);

			/// Lookup the header to check if the record exist in the dabtabase
			if ($head=='id')
			{
				$so_id=$val;
				$CURR_ID=$so_id;
				/// Not found -> Create it
				if (!isset($SEQ_ONTOL[$so_id]))
				{
					++$MAX_DBID;
					$SEQ_ONTOL[$CURR_ID]=array('DB'=>$MAX_DBID,'NAME'=>'','DESC'=>'','STATUS'=>'NEW');
				}
				/// Already exist -> set to valid
				else $SEQ_ONTOL[$CURR_ID]['STATUS']='VALID';
			}
		
			else if ($head=='name')
			{
				/// Name is different -> set to UPD, unless it's a new record
				if ($SEQ_ONTOL[$CURR_ID]['NAME']!=$val)
				{
					$SEQ_ONTOL[$CURR_ID]['NAME']=$val;
					if ($SEQ_ONTOL[$CURR_ID]['STATUS']!='NEW')
					{
						$SEQ_ONTOL[$CURR_ID]['STATUS']='UPD';
					}
				}
			}
			else if ($head=='def')
			{
				/// definition is different -> set to UPD, unless it's a new record
				$pos=strrpos($val,'"');
				$val=substr($val,1,$pos-1);
				if ($SEQ_ONTOL[$CURR_ID]['STATUS']=='NEW')
				{
					$SEQ_ONTOL[$CURR_ID]['DESC']=$val;
				}
				else if ($SEQ_ONTOL[$CURR_ID]['DESC']!=$val)
				{
					$SEQ_ONTOL[$CURR_ID]['DESC']=$val;
					$SEQ_ONTOL[$CURR_ID]['STATUS']='UPD';
				}
			}
			
			
		}while(!feof($fp));
		$so_id='';

	}
	fclose($fp);
	


addLog("Update records");
	$fp=fopen('so_insert.csv','w');	if(!$fp)								failProcess($JOB_ID."007",'Unable to open so_insert file');
	

	/// Now we have to update the records that need to be updated and push the new records into a file
	foreach ($SEQ_ONTOL as $so_id=>$INFO)
	{

		// Update the records that need to be updated
			if ($INFO['STATUS']=='UPD')
			{
				$QUERY="UPDATE so_entry 
				SET so_name='".str_replace("'","''",$INFO['NAME'])."', 
				so_description='".str_replace("'","''",$INFO['DESC'])."',
				so_id='".$so_id."'
				WHERE so_entry_id =".$INFO['DB'];
				if (!runQueryNoRes($QUERY))								failProcess($JOB_ID."008",'Unable to run query '.$QUERY);
			}
		/// Push into a file all new recods
			else if ($INFO['STATUS']=='NEW')
			{
				fputs($fp,
					$INFO['DB']."\t".
					$INFO['NAME']."\t".
					$so_id."\t".
					(($INFO['DESC']=='')?'NULL':'"'.str_replace('"','""',$INFO['DESC']).'"')."\n");
			}
	}

	fclose($fp);


addLog("Delete records");
/// Delete those that haven't been found in the file
	foreach ($SEQ_ONTOL as $ID=>$INFO)
	{
		if ($INFO['STATUS']!='DB')continue;///DELETION
	
		$query='DELETE FROM SO_ENTRY WHERE SO_ENTRY_ID='.$INFO['DB'].'"';
		if (!runQueryNoRes($query))										failProcess($JOB_ID."009",'Unable to run query '.$query);
		
	}

	/// Push the new records in the database
	$FCAV_NAME='so_insert.csv';
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.so_entry(so_entry_id,so_name,so_id,so_description)FROM \''.$FCAV_NAME."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )												failProcess($JOB_ID."010",'Unable to insert so_entry'); 


	$CLEANUP=array('so_insert.csv');
	foreach ($CLEANUP as $file) 
	{
		if (!is_file($file))continue;
		if (!unlink($file))											failProcess($JOB_ID."011",'Unable to delete '.$file);
	}


	updateStat('so_entry','so_entry',$STATS['SO'],$JOB_ID);


addLog("Push to prod");
	pushToProd();

	

successProcess();

?>
