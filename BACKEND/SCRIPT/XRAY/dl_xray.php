<?php

/**
 SCRIPT NAME: dl_xray
 PURPOSE:     Download all xray files
 
*/
$JOB_NAME='dl_xray';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
require_once($TG_DIR.'/BACKEND/SCRIPT/XRAY/xray_functions.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
 


addLog("Create directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_xray_rel')];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';  		 if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$E_DIR=$W_DIR.'/ENTRIES';	 if (!is_dir($E_DIR) && !mkdir($E_DIR)) 				failProcess($JOB_ID."003",'Unable to find and create '.$W_DIR);
	
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	$FILECONV=$TG_DIR.'/'.$GLB_VAR['TOOL']['FILECONV']; if(!is_executable($FILECONV))	failProcess($JOB_ID."006",'Unable to Find fileconv '.$FILECONV);
	if(!isset($GLB_VAR['TOOL']['FILECONV_PARAM']))										failProcess($JOB_ID."007",'Unable to Find Fileconv PArams');
	$FC_PARAM=$GLB_VAR['TOOL']['FILECONV_PARAM'];
	$PREF_SMI=$TG_DIR.'/'.$GLB_VAR['TOOL']['PREF_SMI']; if(!is_executable($PREF_SMI))	failProcess($JOB_ID."008",'Unable to Find PREF_SMI '.$PREF_SMI);
	$CPDTODB=$TG_DIR.'/'.$GLB_VAR['TOOL']['CPDTODB']; if(!is_executable($CPDTODB))		failProcess($JOB_ID."009",'Unable to Find CPDTODB '.$CPDTODB);
	
		//echo $W_DIR;exit;

	$ARCHIVE=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$CK_INFO['DIR'].'/ARCHIVE';
	if (!is_dir($ARCHIVE) && !mkdir($ARCHIVE)) 										failProcess($JOB_ID."010",'Unable to create '.$ARCHIVE.' directory');
	$PRD_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'];
	if (!is_dir($PRD_DIR))			 												failProcess($JOB_ID."011",'Unable to find '.$PRD_DIR.' directory');
	


addLog("Download Xray file");
	/// Entries.idx contains all PDB IDs
	$FILE_NAME='index/entries.idx';
	if (!isset($GLB_VAR['LINK']['FTP_RCSB_DERIVED']))									failProcess($JOB_ID."012",'FTP_RCSB_DERIVED path no set');
	if (!checkFileExist('entries.idx')  && 
		!dl_file($GLB_VAR['LINK']['FTP_RCSB_DERIVED'].'/'.$FILE_NAME,3))				failProcess($JOB_ID."013",'Unable to download archive');


		//// Components.cif lists all HET Code
	if (!isset($GLB_VAR['LINK']['FTP_WWPDB_COMPONENT']))								failProcess($JOB_ID."014",'FTP_WWPDB_COMPONENT path no set');
	if (!checkFileExist('component.cif')  
		&&!dl_file($GLB_VAR['LINK']['FTP_WWPDB_COMPONENT'],3))							failProcess($JOB_ID."015",'Unable to download component.cif');
	

addLog("File check");
	if (!validateLineCount('entries.idx',150000))										failProcess($JOB_ID."016",'entries file is smaller than expected'); 




	/// We get the MAX DBIDS for each table to speed up file insertion
addLog("Get MAx DBIDS")	;
	$DBIDS=array('xr_tpl_res'=>-1,
	'xr_tpl_atom'=>-1,
	'xr_tpl_bond'=>-1);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{#TODO _ID?
		$res=array();
		$res=runQuery('SELECT MAX('.$TBL.'_ID) co FROM '.$TBL);
		if ($res===false)																failProcess($JOB_ID."017",'Unable to run query');
		//echo $TBL."\n";print_r($res);
		$DBIDS[$TBL]=($res[0]['co']=='')?0:$res[0]['co'];
	}
//	print_r($DBIDS);

	
	/// There's two prd paths for xray, XRAY_TPL and XRAY
	/// XRAY_TPL is a temporary prod file in which we have the latest templates for xray residues
	/// XRAY is a prod file with the lastest version of the data
	$PRD_PATH=$PRD_DIR.'/XRAY_TPL';
		

addLog("opening files");
		foreach ($DBIDS as $TBL=>&$POS)
		{
			$FILES[$TBL]=fopen($TBL.'.csv','w');
			if (!$FILES[$TBL])															failProcess($JOB_ID."018",'Unable to open '.$TBL.'.csv');
		}
	
	

	addLog("Processing");

		/// Then we run the tool. Here, there are two situations
		/// 1/ It's the first time running the script, so there's no version (-noV) to use
		/// 2/ There's a previous version of that file, then we will get the list of already processed residues from the database to avoid processing them again
		$PARAMS='';
		if (!is_dir($PRD_PATH))$PARAMS =' -noV ';
		else 
		{
			/// We don't want to reprocess the ones we have already, so we get a list of current residues from the database
			
			$PARAMS='  -exD LIST_CURRENT_RESIDUES ';
			runQueryToFile('SELECT name FROM xr_tpl_res','LIST_CURRENT_RESIDUES',$JOB_ID.'019');
		}

		/// Execute the program.
		/// This tool will generate a few files.
		/// 1. HETLIST & HETLIST.objx are files used by the xray programs to process xray structures
		/// 2. final.xml contains all new reisudes to insert
		 exec($CPDTODB.$PARAMS.' -oH HETLIST -oB HETLIST.objx -oXML final.xml -inD -c components.cif &> LOG',$res,$return_code);
		
		// echo $return_code;
		 if ($return_code!=0)													failProcess($JOB_ID."019",'Unable to process component file');
		


		/// To properly insert the residues, we need to map the atoms of those residues to the corresponding atomic element
	addLog("Get elements");
		$res=runQuery("SELECT xr_element_id,symbol FROM xr_element");if ($res===false)	failProcess($JOB_ID."020",'Unable to run query');
		$ELEMENT=array();
		foreach ($res as $line)	$ELEMENT[$line['symbol']]=$line['xr_element_id'];
		
		
		addLog("Generate XML file");
	$fp=fopen('final.xml','r');	if (!$fp)												failProcess($JOB_ID."021",'Unable to open final.xml file');	
	$fpS=fopen('smiles_ini.smi','w');	if (!$fpS)										failProcess($JOB_ID."022",'Unable to open smiles_ini.smi file');	
	$N_ENTRY=0;
	
	$NEW_SMILES=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		
		/// Each residue is defined by a xml block <Residue></Residue 
		if (strpos($line,'<Residue>')===false)continue;
		++$N_ENTRY;
		if ($N_ENTRY%100==0){echo $N_ENTRY."\n";}//break;}

		$STR=$line."\n";
		do
		{
			$line=stream_get_line($fp,10000,"\n");
		//	echo $line."\n";
			$STR.=$line."\n";
		}while($line!='</Residue>');
		//echo $STR."\n";
		
		/// One way to read an xml file/block is to convert it to json and decode it in json to get an array
		$xml = simplexml_load_string($STR, "SimpleXMLElement", LIBXML_NOCDATA);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);


		/// Insert xr_tpl_res -> residue name
	echo $array['ResidueName']."\n";
		$DBIDS['xr_tpl_res']++;
		fputs($FILES['xr_tpl_res'],$DBIDS['xr_tpl_res']."\t".
		$array['ResidueName']."\t".
		$array['Smiles']."\t".
		$array['Class']."\tNULL\tNULL\n"
		);

		/// This will be used to map compound to xray residue
		fputs($fpS,$array['Smiles'].' '.$DBIDS['xr_tpl_res']."\n");


	
		$NEW_SMILES[$array['Smiles']]=$DBIDS['xr_tpl_res'];
		if (isset($array['ReplacedBy']))$TO_REPLACE[$array['ResidueName']]=$array['ReplacedBy'];
	
		/// Then we look for atoms
		$MAP_AT=array();
		$ALL_ATOMS=null;
		if (isset($array['Atoms']['Atom'][0]))$ALL_ATOMS=&$array['Atoms']['Atom'];
		else $ALL_ATOMS=&$array['Atoms'];
		foreach ($ALL_ATOMS as &$AT)
		{
			$ATTR=$AT['@attributes'];
			if ($ATTR['atomName']=='')continue;
			$DBIDS['xr_tpl_atom']++;
			
			$MAP_AT[$ATTR['mappingIdentifier']]=$DBIDS['xr_tpl_atom'];
			fputs($FILES['xr_tpl_atom'],
			$DBIDS['xr_tpl_atom']."\t".
			$ATTR['atomName']."\t".
			$ELEMENT[$ATTR['elementSymbol']]."\t".
			$DBIDS['xr_tpl_res']."\t".
			$ATTR['mol2Type']."\tNULL\tNULL\n");
		}

		/// And the bonds
		$ALL_BONDS=null;
		if (isset($array['Bonds']['Bond'][0]))$ALL_BONDS=&$array['Bonds']['Bond'];
		else $ALL_BONDS=&$array['Bonds'];
		foreach ($ALL_BONDS as &$AT)
		{
			
			$ATTR=$AT['@attributes'];
			if ($ATTR['atom1']=='')continue;
			$DBIDS['xr_tpl_bond']++;
			
			fputs($FILES['xr_tpl_bond'],$DBIDS['xr_tpl_bond']."\t".$ATTR['bondType']."\t".$MAP_AT[$ATTR['atom1']]."\t".$MAP_AT[$ATTR['atom2']]."\n");
		}

	}
	fclose($fp);
	fclose($fpS);
			 	
			
	addLog("Pushing to database");
		# Those are the tables we are pushing data into, with the column order in the files
		$COL_ORDER = array('xr_tpl_res'=>'(xr_tpl_res_id,name,smiles,class,subclass,replaced_by_id)',
		'xr_tpl_atom'=>'(xr_tpl_atom_id,name,xr_element_id,xr_tpl_res_id,mol2type,stereo,charge)',
		'xr_tpl_bond'=>'(xr_tpl_bond_id,bond_type,xr_tpl_atom_id_1,xr_tpl_atom_id_2)'
		);	


		foreach ($COL_ORDER as $NAME=>$CTL)
		{
		//	if (in_array($NAME,$TO_FILTER))continue;
		echo $NAME."\n";


			addLog("inserting ".$NAME." records");
			$res=array();
			fclose($FILES[$NAME]);
			$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			exec($DB_INFO['COMMAND'].' -c "'.$command.'"',$res,$return_code);
			if ($return_code!=0)										failProcess($JOB_ID."023",'Unable to load '.$NAME.' in database');	
			$FILES[$NAME]=fopen($NAME.'.csv','w');
		}

	addLog("Lookup sm_entry <-> xr_tpl_res");
		/// smiles.ini contains all residues smiles.
		/// We are going to standardize them and look if we can find them in sm_entry table
		$QUERY="cat 'smiles_ini.smi' | ".$FILECONV.' '.$FC_PARAM.' -i smi - | '.$PREF_SMI.' - ';
			
		exec($QUERY,$res2,$return_code); 
		if ($return_code!=0)										failProcess($JOB_ID."024",'Unable to get smiles');	
		$SMI_I=array();
		foreach ($res2 as $line)
		{
			if ($line=='')continue;
			$tab=explode(" ",$line);
			$SMI_I["'".$tab[0]."'"][]=$tab[1];
		}
		$CHUNKS=array_chunk(array_keys($SMI_I),1000);
		
		foreach ($CHUNKS as $CHUNK)
		{
			
			$query="SELECT sm_molecule_id, smiles FROM sm_molecule WHERE smiles IN (".implode(',',$CHUNK).')';
			$res=runQuery($query);
			if ($res===false)failProcess($JOB_ID."025",'Unable to fetch SMILES ');
			foreach ($res as $line)
			{
				$SM_MOLE_ID=$line['sm_molecule_id'];
				$SMI="'".$line['smiles']."'";
				foreach ($SMI_I[$SMI] as $S)
				{
					$query="UPDATE xr_tpl_res SET sm_molecule_id = ".$SM_MOLE_ID.' WHERE xr_tpl_res_id = '.$S;
					echo $query."\n";
					if (!runQueryNoRes($query))
					failProcess($JOB_ID."026",'Unable to Update XR_TPL_RES_ID '.$S);
				}
			}
		}


		
		/// Here we create the temporary PRD_PATH
		if (is_link($PRD_PATH)){
				system('unlink '.$PRD_PATH,$return_code);
				if ($return_code !=0)												failProcess($JOB_ID."027",'Unable to unlink '.$PRD_PATH.' directory');
			}
			
		system('ln -s '.$W_DIR.' '.$PRD_PATH,$return_code);
		if ($return_code!=0)														failProcess($JOB_ID."028",'Unable to create symlink '.$PRD_PATH.' directory');
		



addLog("Get current list");
	$res=runQuery('SELECT full_common_name FROM xr_entry');if ($res===false)failProcess($JOB_ID."029",'Unable to get Common Names');
	$LIST_CURRENT=array();
	foreach ($res as $line)$LIST_CURRENT[$line['full_common_name']]=true;

addLog("Get MAX DBID");
	$res=runQuery('SELECT MAX(xr_entry_id) co FROM xr_entry');if ($res===false)failProcess($JOB_ID."030",'Unable to get Max XR ENTRY ID');
	if (count($res)==0)$MAX_DBID=0;
	else $MAX_DBID=$res[0]['co'];
addLog("Get MAX JOB_STATUS DBID");
	$res=runQuery('SELECT MAX(xr_status_id) co FROM xr_status');if ($res===false)failProcess($JOB_ID."031",'Unable to get Status');
	if (count($res)==0)$MAX_STATUS_ID=0;
	else $MAX_STATUS_ID=$res[0]['co'];
addLog("Get Download job id");
$res=runQuery("SELECT xr_job_id FROM xr_jobs WHERE xr_job_name='download'");
	if ($res===false || count($res)==0)											failProcess($JOB_ID."032",'Unable to get job download id'); 
	else $JOB_DL=$res[0]['xr_job_id'];
	$res=runQuery("SELECT xr_job_id FROM xr_jobs WHERE xr_job_name='setup'");
	if ($res===false || count($res)==0)											failProcess($JOB_ID."033",'Unable to get job setup id'); 
	else $JOB_ST=$res[0]['xr_job_id'];


addLog("Load entries file");


/// Now we are going to look for all the new entries
/// and create the directory, download the file, verify it's a protein ...etc
	$fp=fopen('entries.idx','r');if (!$fp)										failProcess($JOB_ID."034",'Unable to open entriex.idx'); 
	$tab=fgetcsv($fp,10000,"\n");
	$tab=fgetcsv($fp,10000,"\n");
	$LIST_NEW=array();
	while(!feof($fp))
	{
		//$tab=fgetcsv($fp,10000,"\n");
		$line=stream_get_line($fp,10000,"\n");
		if ($line=='')continue;
		$tab=explode("\t",$line);
		
		
		if (isset($LIST_CURRENT[$tab[0]]))continue;
		
		$LIST_NEW[]=$tab[0];
		++$MAX_DBID;
		if ($tab[6]=='NOT'||$tab[6]=='')$tab[6]='NULL';
		else 
		{
		$tmp=explode(",",str_replace(" ","",$tab[6]));
		$tab[6]=min($tmp);
		}
		$QUERY="INSERT INTO xr_entry (xr_entry_id,
		full_common_name,
		expr_type,
		resolution,
		deposition_date,
		date_created,
		date_updated,
		title) VALUES (".$MAX_DBID.",'".$tab[0]."','".$tab[7]."',".$tab[6].",TO_DATE('".$tab[2]."','mm/dd/yy'),CURRENT_TIMESTAMP,NULL,'".str_replace("'","''",$tab[3])."')";
		if (runQueryNoRes($QUERY)===false)									failProcess($JOB_ID."035",'Unable to run query '.$QUERY); 
#TODO VALUES() (timestamp, null) above? Entry_data below?
		$ENTRY_DATA=array( 'DIR'=>'',
		'PROCESS'=>array('DIR'=>'',
				 'GET_STRUCTURE'=>'',
				 'PDB_PREP'=>'',
				 'PDB_SEP'=>'',
				 'DB_INSERT'=>'',
				 'VOLSITE'=>'',
				 'BLASTP'=>'',
				 'INTERS'=>'',
				 'CLUSTERING'=>''),
	 	'FILES'=>array(),
	 	'BLASTP'=>array(),
	 	'CAVITIES'=>array());
		$ENTRY_DATA['INIT']=array('ENTRY_NAME'=>$tab[0],'DBID'=>$MAX_DBID);
		$RET_CODE=createEntryDir($ENTRY_DATA,$E_DIR,$JOB_ID);
		echo $tab[0]."\t".$RET_CODE."\t";
		++$MAX_STATUS_ID;
		$QUERY="INSERT INTO xr_status (xr_status_id,xr_entry_id,xr_job_id,date_processed,status_value) VALUES (".$MAX_STATUS_ID.','.$MAX_DBID.",".$JOB_ST.",CURRENT_TIMESTAMP,'".$RET_CODE."')";
		$res=runQueryNoRes($QUERY);if ($res===false)									failProcess($JOB_ID."036",'Unable to get xr_status'); 

		$RET_CODE=getIniPDBEntry($ENTRY_DATA);
		echo $RET_CODE."\n";
		++$MAX_STATUS_ID;
		$QUERY="INSERT INTO xr_status (xr_status_id,xr_entry_id,xr_job_id,date_processed,status_value) VALUES (".$MAX_STATUS_ID.','.$MAX_DBID.",".$JOB_DL.",CURRENT_TIMESTAMP,'".$RET_CODE."')";
		$res=runQueryNoRes($QUERY);if ($res===false)									failProcess($JOB_ID."037",'Unable to get xr_status'); 
	
	}
	fclose($fp);


successProcess();

?>
