<?php

/**
 SCRIPT NAME: db_scaffold
 PURPOSE:     Compute the different scaffolds for the molecules in the database
 
*/

/// Job name - Do not change
$JOB_NAME='db_scaffold';

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
	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 				failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$JOB_INFO['DIR'].'/';	   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=getCurrDate();		           if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
	if ( !chdir($W_DIR)) 															failProcess($JOB_ID."004",'Unable to access '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=getCurrDate();
	
	/// Checking tool paths
	$ABSTR=$GLB_VAR['TOOL']['MOL_ABSTR']; if(!is_executable($ABSTR))				failProcess($JOB_ID."005",'Unable to Find molecular abstraction tool '.$ABSTR);
	$SCAFFOLD_PARAMS=$GLB_VAR['TOOL']['SCAFFOLD_PARAM']; if(!is_executable($ABSTR))	failProcess($JOB_ID."006",'Unable to Find SCAFFOLD_PARAM in CONFIG_GLOBAL');
	
	/// Create the scaffold directory
	if (!is_dir('SCAFFOLD') && !mkdir('SCAFFOLD'))									failProcess($JOB_ID."007",'Unable to create scaffold dir');
	if (!chdir('SCAFFOLD'))															failProcess($JOB_ID."008",'Unable to access scaffold dir');

	addLog("Working directory:".$W_DIR);

	processScaffold($GLB_VAR['PUBLIC_SCHEMA']);
	
	if($GLB_VAR['PRIVATE_ENABLED']=='T')processScaffold($GLB_VAR['SCHEMA_PRIVATE']);


	successProcess();


function processScaffold($SCHEMA)
{
	global $ABSTR;
	global $SCAFFOLD_PARAMS;
	global $JOB_ID;
	global $JOB_INFO;
	addLog("Processing schema ".$SCHEMA);
	/// Get the max id for sm_scaffold
	$DBIDS=array('sm_scaffold'=>-1);
	$res=runQuery("SELECT MAX(sm_scaffold_id) CO FROM ".$SCHEMA.".sm_scaffold");
	if ($res===false)															failProcess($JOB_ID."A01",'Unable to get max id from sm_scaffold');
	if (count($res)==0)$DBIDS['sm_scaffold']=0;else $DBIDS['sm_scaffold']=$res[0]['co'];
	
	/// Get the latest date this job was run so we can only get the new molecules
	$DATE='';
	 if ($JOB_INFO['TIME']['DEV_DIR']!=-1)	
	 $DATE=' AND date_created > \''.date('Y-m-d',strtotime('-1 day', strtotime($JOB_INFO['TIME']['DEV_DIR']))).'\'';




	addLog("Getting data from database");

	///	We have to get the molecules that are valid and have not been processed yet
	/// However, that can be a lot of molecules so we will process them in chunks
	/// So first we need to know how many molecules we have to process
	$query="SELECT count(*) co FROM ".$SCHEMA.".sm_molecule where is_valid='T' ".$DATE;
	$res=runQuery($query);
	if ($res===false)																		failProcess($JOB_ID."A02",'Unable to get count');
	$CO=$res[0]['co'];
	if ($CO==0)return;

	global $DB_CONN;
	/// Then we create a statement to get the molecules in chunks
	$query="SELECT smiles,sm_molecule_id 
			FROM ".$SCHEMA.".sm_molecule 
			WHERE  is_valid='T'  ".$DATE. ' 
			ORDER BY sm_molecule_id ASC 
			LIMIT 5000 
			OFFSET :offset';
	$stmt=$DB_CONN->prepare($query);
	$N=0;	
	/// We are going to write the smiles to a file so that we can use the molecular abstraction tool
	$fp=fopen('INPUT.smi','w'); if (!$fp)												failProcess($JOB_ID."A03",'Unable to open INPUT.smi');
	
	/// Looping over each chunks
	for ($I=0;$I<$CO;$I+=5000)
	{
		/// Changing the offset and executing the query
		$stmt->execute(array('offset'=>$I));
		
		/// Writing the smiles to the string, so we can be more efficient (Less I/O)
		$STR='';
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) 
		{    
			$N++;
			$STR.=$row['smiles'].' '.$row['sm_molecule_id']."\n";
			
		}
		/// Writing the smiles to the file
		fputs($fp,$STR);
		echo $N."\n";
		$stmt->closeCursor();
	}
	/// Closing the file and the statement
	$stmt=null;
	unset($stmt);
	fclose($fp);

	/// If we have no molecules to process, we can exit
	if ($N==0)return;


 	addLog("Generate abstraction");
	
	/// Running the molecular abstraction tool
	exec($ABSTR.' '.$SCAFFOLD_PARAMS.'  INPUT.smi > scaffold.csv',$res,$return_code);
 	if ($return_code!=0)															failProcess($JOB_ID."A04",'Unable to compute scaffolds');

	/// Now we have to process the scaffold file
	if (!is_file('scaffold.csv'))													failProcess($JOB_ID."A05",'Unable to find scaffold.csv');
	$fp=fopen('scaffold.csv','r');if (!$fp)											failProcess($JOB_ID."A06",'Unable to open scaffold.csv');

	/// Against we are going to process the scaffolds in chunks
	while(!feof($fp))
	{
		$line=stream_get_line($fp,100000,"\n");if ($line=='')continue;
		$tab=explode(" " ,$line);

		/// Quote the smiles so that we can use them more easily in the query
		$SCAFF["'".$tab[0]."'"][]=$tab[1];
		if (count($SCAFF)<10000)continue;
		processScaff($SCHEMA,$SCAFF,$DBIDS);
		$SCAFF=array();
	}
	fclose($fp);
	processScaff($SCHEMA,$SCAFF,$DBIDS);
}

function processScaff($SCHEMA,&$SCAFF,&$DBIDS)
{
	
	global $GLB_VAR;
	global $DB_INFO;
	
	
	/// We have to process the scaffolds in chunks
	/// So we take the SMILES that are the keys in $SCAFF
	/// and search for them in the database
	$CHUNKS=array_chunk(array_keys($SCAFF),500);

	/// We have to check if the scaffold is already in the database
	foreach ($CHUNKS as $CHK)
	{
		$res=runQuery("SELECT sm_scaffold_id,scaffold_smiles 
			FROM ".$SCHEMA.".sm_scaffold 
			WHERE scaffold_smiles IN (".implode(",",$CHK).')');
		if ($res===false)													failProcess($JOB_ID."B01",'Unable to get scaffolds');

		/// For all existing scaffolds, we have to update the molecules to have the scaffold id
		foreach ($res as $line)
		{
			$SCAFF_ID=$line['sm_scaffold_id'];
			$SCAFF_SMI=$line['scaffold_smiles'];
			if (!isset($SCAFF["'".$SCAFF_SMI."'"]))continue;
			$RECS=&$SCAFF["'".$SCAFF_SMI."'"];
			$CHUNKS2=array_chunk($RECS,1000);
			
			
			foreach ($CHUNKS2 as $CHK2)
			{
				$query= 'UPDATE '.$SCHEMA.'.sm_molecule 
						SET sm_scaffold_id='.$SCAFF_ID.' 
						WHERE sm_molecule_id IN ('.implode(",",$CHK2).')';
				if (!runQueryNoRes($query))										failProcess($JOB_ID."B02",'Unable to run query '.$query);
			}

			/// We have to remove the scaffold from the list
			unset($SCAFF["'".$SCAFF_SMI."'"]);
		}
	}



	/// Now we are left with the scaffolds that are not in the database
	/// We have to insert them in the database by first writing them to a file
	$fp=fopen('SCAFFOLD_O.csv','w');if (!$fp)									failProcess($JOB_ID."B03",'Unable to open SCAFFOLD_O.csv');
	$MAP=array();
	foreach ($SCAFF as $SCAFF_SMI=>$LIST_SC)
	{
		++$DBIDS['sm_scaffold'];
		$MAP[$SCAFF_SMI]=$DBIDS['sm_scaffold'];
		fputs($fp,$DBIDS['sm_scaffold']."\t".substr($SCAFF_SMI,1,-1)."\tT\n");
	}
	fclose($fp);

	/// Create the copy command
	$command='\COPY '.$SCHEMA.'.sm_scaffold (sm_scaffold_id, scaffold_Smiles,is_valid) FROM \'SCAFFOLD_O.csv\''."  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	
	/// Run the command
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )													failProcess($JOB_ID."B04","ERROR:".'Unable to insert sm_Scaffold'."\n");
	
	

	foreach ($SCAFF as $SCAFF_SMI=>$LIST_SC)
	{
		/// We have to update the molecules that are mapped to the scaffold id
		/// However, we have to do this in chunks
		$CHUNKS2=array_chunk($RECS,1000);

		foreach ($CHUNKS2 as $CHK2)
		{
			$query= 'UPDATE '.$SCHEMA.'.sm_molecule 
					SET sm_scaffold_id='.$SCAFF_ID.'
					WHERE sm_molecule_id IN ('.implode(",",$CHK2).')';
			if (!runQueryNoRes($query))										failProcess($JOB_ID."B05",'Unable to run query '.$query);
		}
	}
}

	

	
	
?>