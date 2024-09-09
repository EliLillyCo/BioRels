<?php
ini_set('memory_limit','5000M');
error_reporting(E_ALL);
/**
 SCRIPT NAME: db_insert_Xray
 PURPOSE:     insert all xray files
 
*/
$JOB_NAME='db_insert_xray';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
require_once($TG_DIR.'/BACKEND/SCRIPT/XRAY/xray_functions.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
 

addLog("Access directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_xray')];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';  		 if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$E_DIR=$W_DIR.'/ENTRIES';
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
						   					   if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];


addLog("Database pre-load data");
	/// Each residue is assigned to a template
	/// so we donlowd the templates, first residues
	$res=runQuery( "SELECT xr_tpl_res_id, name FROM xr_tpl_res");	if ($res===false)	failProcess($JOB_ID."005",'Unable to get xr_tpl_res_id');
	$TPL_RES=array();
	$MAP=array();
	foreach ($res as $line) 
	{
		$MAP[$line['xr_tpl_res_id']]=$line['name'];
		$TPL_RES[$line['name']]=array('id'=>$line['xr_tpl_res_id'],'atom'=>array());
	}
	/// Then atoms
	$res=runQuery("SELECT xr_tpl_atom_id, name,xr_tpl_res_id FROM xr_tpl_atom");if ($res===false)failProcess($JOB_ID."006",'Unable to get xr_tpl_atom_id');
	foreach ($res as $line) $TPL_RES[$MAP[$line['xr_tpl_res_id']]]['atom'][$line['name']]=$line['xr_tpl_atom_id'];
	/// We also have interactions
	$INTERS=array();
	$res=runQuery("SELECT xr_inter_type_id, interaction_name FROM xr_inter_type");if ($res===false)failProcess($JOB_ID."007",'Unable to get xr inter type ID and interaction name');
	foreach ($res as $line) $INTERS[$line['interaction_name']]=$line['xr_inter_type_id'];


	

addLog("Get MAx DBIDS")	;
	/// Those are the tables we are going to insert into
	/// DBIDS will have the max primary keys for each table
	$DBIDS=array('xr_chain'=>-1,
	'xr_res'=>-1,
	'xr_atom'=>-1,
	'xr_bond'=>-1,
	'xr_inter_res'=>-1,
	'xr_ppi'=>-1,
	'xr_ch_prot_map'=>-1,
	'xr_ch_prot_pos'=>-1);

	//COL_ORDER will have the tables as key and the column order as values
	$COL_ORDER = array('xr_chain'=>'(xr_chain_id,xr_entry_id,chain_name,length,chain_type)',
	'xr_res'=>'(xr_res_id,xr_chain_id,xr_tpl_res_id,position,cacoord)',
	'xr_atom'=>'(xr_atom_id,identifier,xr_tpl_atom_id,xr_res_id,charge,mol2type,b_factor,x,y,z)',
	'xr_bond'=>'(xr_bond_id,bond_type,xr_atom_id_1,xr_atom_id_2,xr_chain_id)',
	'xr_inter_res'=>'(xr_inter_res_id,xr_atom_id_1,xr_res_id_1,atom_list_1,xr_res_id_2,atom_list_2,xr_atom_id_2,xr_inter_type_id,distance,angle)',
	'xr_ppi'=>'(xr_ppi_id,xr_chain_r_id,xr_chain_c_id)',
	'xr_ch_prot_map'=>'(xr_ch_prot_map_id,xr_chain_id,prot_seq_id,perc_sim,perc_identity,length,perc_sim_com,perc_identity_com,is_primary,is_chimeric,n_mutant)',
	'xr_ch_prot_pos'=>'(xr_ch_prot_pos_id,xr_res_id,prot_seq_pos_id,xr_prot_map_type,xr_ch_prot_map_id)'
	);	
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$res=runQuery('SELECT MAX('.$TBL.'_id) co FROM '.$TBL);if ($res===false)							failProcess($JOB_ID."008",'Unable to get Max '.$TBL.'id');
		if (count($res)==0)$DBIDS[$TBL]=0;
		else $DBIDS[$TBL]=$res[0]['co'];
	}

	
addLog("Open files");
	if (!is_dir('INSERT') && !mkdir('INSERT'))																failProcess($JOB_ID."009",'Unable to create INSERT directory');
	if (!chdir('INSERT'))																					failProcess($JOB_ID."010",'Unable to create INSERT directory');
	
	

addLog("Get MAX JOB_STATUS DBID");
	$res=runQuery('SELECT MAX(xr_status_id) co FROM xr_status');if ($res===false)							failProcess($JOB_ID."011",'Unable to get xr status id');
	if (count($res)==0)$MAX_STATUS_ID=0;
	else $MAX_STATUS_ID=$res[0]['co'];

addLog("Get Download job id");
	/// Getting the list of 3-D structure jobs
	$res=runQuery("SELECT xr_job_id, xr_job_name FROM xr_jobs");if ($res===false)							failProcess($JOB_ID."012",'Unable to get xr job id, job name');
	$JOB_IDS=array();
	foreach ($res as $line)$JOB_IDS[$line['xr_job_name']]=$line['xr_job_id'];

echo getcwd();

addLog("Get List of current entries");

	$fp=fopen('../LIST_TO_PROCESS','r');if (!$fp)															failProcess($JOB_ID."013",'Unable to open LIST_TO_PROCESS');
	$N_ENTRY=0;$START=false;$SUCCESS=array('DB_LOAD'=>0,'BLASTP'=>0);
	/// Order of the different xray jobs
	$ORDER=array('download'=>'GET_STRUCTURE',
				'prepare'=>'PDB_PREP',
				'pdb_sep'=>'PDB_SEP',
				'db_load'=>'PDB_SEP',
				'blastp'=>'BLASTP',
				'blastp_load'=>'BLASTP');//,'VOLSITE','CLUSTERING');
	


	while(!feof($fp))
	{
		/// Get PDB ID
		$PDB_ID=stream_get_line($fp,1000,"\n");
	
		++$N_ENTRY;
		echo "##################\n".$PDB_ID."\t".$N_ENTRY."\t";
		/// Then we load the entry from the data file
		$ENTRY=loadEntry($PDB_ID,$E_DIR);
		echo $ENTRY['INIT']['DBID']."\t";

		/// Then we download the different status from the database for that entry
		/// It is order by date_processed. This is important, because we want the lastest status for that entry
		$STATUS=array();
		$res=runQuery("SELECT xr_job_name, status_value 
						FROM xr_status xs, xr_jobs xj 
						WHERE xj.xr_job_id = xs.xr_job_id 
						AND xr_entry_id = ".$ENTRY['INIT']['DBID'].
						' ORDER BY date_processed ASC');
		if ($res===false)																				failProcess($JOB_ID."014",'Unable to get job name, status value');
		foreach ($res as $line)	$STATUS[$line['xr_job_name']]=$line['status_value'];
		
		//print_R($STATUS);
		if (!isset($ENTRY['CODE'])||$ENTRY['CODE']==array()){echo "\n";continue;}
		
		
		//// If you want to clean/push to db again, uncomment these lines:
		// $STATUS['db_load']='';
		// $STATUS['blastp_load']='';
		// if (!runQueryNoRes('DELETE FROM xr_ch_prot_map WHERE xr_chain_id IN (SELECT xr_chain_id FROM xr_chain where xr_entry_id = '.$ENTRY['INIT']['DBID'].')'))continue;
		// if (!runQueryNoRes('DELETE FROM xr_chain WHERE xr_chain_id IN (SELECT xr_chain_id FROM xr_chain where xr_entry_id = '.$ENTRY['INIT']['DBID'].')'))continue;


		/// Now we look at each process
		foreach ($ORDER as $DBJOB_NAME=>$JOB_NAME)
		{
			echo $DBJOB_NAME."\t".$JOB_NAME."\tDBSTATUS:".((isset($STATUS[$DBJOB_NAME]))?$STATUS[$DBJOB_NAME]:'N/A')."\tJOB_STATUS:".((isset($ENTRY['CODE'][$JOB_NAME]))?$ENTRY['CODE'][$JOB_NAME]:'')."\n";


			/// To see if it is complete or not
			if (isset($STATUS[$DBJOB_NAME])&& $STATUS[$DBJOB_NAME]=='OK')
			{
				/// db_load is currently the last process. If it's done: Great ! nothing to do
				if ($DBJOB_NAME=='db_load')
				{
					$SUCCESS['DB_LOAD']++;
					continue;
				}

				/// If the last job is blast
				if ($DBJOB_NAME=='blastp_load')
				{
					/// Then we want to see if we have them all in the database
					$res=runQuery("SELECT xr_ch_prot_map_id FROM xr_ch_prot_map xchum, xr_chain xc WHERE xc.xr_chain_id = xchum.xr_chain_id AND xr_entry_id=".$ENTRY['INIT']['DBID'] );
					if ($res===false)																	failProcess($JOB_ID."015",'Unable to get xr ch prot map id');
					$N=0;
					foreach ($ENTRY['BLASTP'] as &$T)
					if(isset($T['ALIGNMENT_DATA']))++$N;
					if($N==count($res)){
						$SUCCESS['BLASTP']++;
						continue;
					}
					else 
					{ /// No? We delete those uniprot to chains mapping and their subsequent alignments
						echo "DELETION\t".count($ENTRY['BLASTP'])."\t".$N."\t".count($res)."\n";
						runQueryNoRes("DELETE FROM xr_ch_prot_map WHERE xr_chain_id IN (SELECT xr_chain_id FROM xr_chain WHERE xr_entry_id=".$ENTRY['INIT']['DBID'].')');
					}
				}
				
			}
			/// Now we check if any of those jobs have a T status, which is a TERM status, then we terminate the process for that entry
			if (isset($STATUS[$DBJOB_NAME]) && substr($STATUS[$DBJOB_NAME],0,1)=='T')break;
			switch ($DBJOB_NAME)
			{
				/// Preparing the pdb file
				case 'pdb_sep':
					$RET_CODE=$ENTRY['CODE'][$JOB_NAME];
					++$MAX_STATUS_ID;	
					$QUERY="INSERT INTO xr_status (xr_status_id,xr_entry_id,xr_job_id,date_processed,status_value) VALUES (".$MAX_STATUS_ID.','.$ENTRY['INIT']['DBID'].",".$JOB_IDS['pdb_sep'].",current_timestamp,'".$RET_CODE."')"; #TODO bit here, timestamp?
					$res=runQueryNoRes($QUERY);
					break;
					/// Pushing data to t
				case 'db_load':
				$RET_CODE=$ENTRY['CODE'][$JOB_NAME];
					if ($RET_CODE=='OK'){
						$RET_CODE=insertXrayStructure($ENTRY);
						$ENTRY['PROCESS']['DB_INSERT']=($RET_CODE=='OK')?'OK':'TERM';
						updateDataFile($ENTRY);
						++$MAX_STATUS_ID;
						$QUERY="INSERT INTO xr_status (xr_status_id,xr_entry_id,xr_job_id,date_processed,status_value) VALUES (".$MAX_STATUS_ID.','.$ENTRY['INIT']['DBID'].",".$JOB_IDS['db_load'].",current_timestamp,'".$RET_CODE."')";
						$res=runQueryNoRes($QUERY);
						echo "=>".$RET_CODE;
						if ($RET_CODE=='OK')$SUCCESS['DB_LOAD']++;
					}
					break;
				case 'blastp':
					$RET_CODE=$ENTRY['CODE'][$JOB_NAME];
					++$MAX_STATUS_ID;	
					$QUERY="INSERT INTO xr_status (xr_status_id,xr_entry_id,xr_job_id,date_processed,status_value) VALUES (".$MAX_STATUS_ID.','.$ENTRY['INIT']['DBID'].",".$JOB_IDS['blastp'].",current_timestamp,'".$RET_CODE."')";
					$res=runQueryNoRes($QUERY);
					break;
				case 'blastp_load':
					$RET_CODE=$ENTRY['CODE'][$JOB_NAME];
					if ($RET_CODE=='OK'){
						$RET_CODE=insertBLASTP($ENTRY);
						$ENTRY['PROCESS']['DB_INSERT']=($RET_CODE=='OK')?'OK':'TERM';
						updateDataFile($ENTRY);
						++$MAX_STATUS_ID;
						$QUERY="INSERT INTO xr_status (xr_status_id,xr_entry_id,xr_job_id,date_processed,status_value) VALUES (".$MAX_STATUS_ID.','.$ENTRY['INIT']['DBID'].",".$JOB_IDS['blastp_load'].",current_timestamp,'".$RET_CODE."')";
						$res=runQueryNoRes($QUERY);
						echo "=>".$RET_CODE;
						if ($RET_CODE=='OK')$SUCCESS['BLASTP']++;
					}
					break;
			}
			
		}
		

		
		echo "\nSUCCESS ENTRIES:".$SUCCESS['DB_LOAD']."\t".round($SUCCESS['DB_LOAD']/$N_ENTRY*100,2)."\t".$SUCCESS['BLASTP']."\t".round($SUCCESS['BLASTP']/$N_ENTRY*100,2)."\n";
		
	$ENTRY=null;
	gc_collect_cycles();
	//exit;
	//break;
}
	fclose($fp);
successProcess();

function insertBLASTP(&$ENTRY)
{
	
	$TBLS=array('xr_ch_prot_map','xr_ch_prot_pos');
	global $STATIC_DIR;
	$FILES=array();
	global $DBIDS;
	global $ORL_PAR;
	$REF_DBIDS=$DBIDS;
	foreach ($TBLS as $TBL)
	{
		$FILES[$TBL]=fopen($TBL.'.csv','w');
		if (!$FILES[$TBL])		failProcess($JOB_ID."016",'Unable to open '.$TBL.'.csv');
	}
	$COUNTS=array('xr_ch_prot_pos'=>0,'xr_ch_prot_map'=>0);
	$MAP_XRCH=array();

	

	$res=runQuery('SELECT xr_res_id,xc.xr_chain_id, chain_name,position FROM xr_chain xc,xr_res xr WHERE xc.xr_chain_id = xr.xr_chain_id AND xr_entry_id = '.$ENTRY['INIT']['DBID']);
	if ($res===false) return 'E1';
	$XRES=array();$XCHAIN=array();
	foreach ($res as $line)	
	{
		$XCHAIN[$line['chain_name']]=$line['xr_chain_id'];
		$XRES[$line['chain_name']][$line['position']]=$line['xr_res_id'];
	}
	
	if (count($XRES)==0)
	{
		echo "NO CHAIN =>INSERTING XRAY STRUCTURE\n";
		$STATUS=	insertXrayStructure($ENTRY);
		if ($STATUS!='OK')return $STATUS;
		$res=runQuery('SELECT xr_res_id,xc.xr_chain_id, chain_name,position FROM xr_chain xc,xr_res xr WHERE xc.xr_chain_id = xr.xr_chain_id AND xr_entry_id = '.$ENTRY['INIT']['DBID']);
		if ($res===false) return 'E1';
		$XRES=array();$XCHAIN=array();
		foreach ($res as $line)	
		{
			$XCHAIN[$line['chain_name']]=$line['xr_chain_id'];
			$XRES[$line['chain_name']][$line['position']]=$line['xr_res_id'];
		}
		if (count($XRES)==0) return 'E1.1';
	
	}
	$STR='';
	
	foreach ($ENTRY['BLASTP'] as $CHAIN=>&$BP_CHAIN)
	{
		if ($BP_CHAIN['ALIGNMENT_DATA']==array())continue;
		foreach ($BP_CHAIN['ALIGNMENT_DATA'] as &$ALIGN_DATA)	$STR.="'".$ALIGN_DATA['DB_ALIGN_INFO']['UNIPROT']."',";
	}
	$UN_ENTRY=array();
	if ($STR!=''){
	$res=runQuery("SELECT prot_identifier,prot_seq_id
	FROM prot_seq us, prot_entry ue 
	WHERE ue.prot_entry_id = us.prot_entry_id 
	AND prot_identifier IN (".substr($STR,0,-1).") AND is_primary='T' ORDER BY prot_identifier"); #TODO this bit order by
	if ($res===false)return 'E2';
	foreach ($res as $line)	$UN_ENTRY[$line['prot_identifier']]=$line['prot_seq_id'];
	}
	foreach ($ENTRY['BLASTP'] as $CHAIN=>&$BP_CHAIN)
	{
		if ($BP_CHAIN['ALIGNMENT_DATA']==array())continue;
		foreach ($BP_CHAIN['ALIGNMENT_DATA'] as &$ALIGN_DATA)
		{
			$ALIGN_INFO=&$ALIGN_DATA['DB_ALIGN_INFO'];
			$ALIGN_LIST=&$ALIGN_DATA['DB_ALIGNMENT'];//[]=array($DB_XR,$DB_UN,$TYPE);
			if (!isset($UN_ENTRY[$ALIGN_INFO['UNIPROT']]))return 'E3';
			$UN_ENTRY_ID=$UN_ENTRY[$ALIGN_INFO['UNIPROT']];
			$COUNTS['xr_ch_prot_map']++;
			
			++$DBIDS['xr_ch_prot_map'];
			fputs($FILES['xr_ch_prot_map'],
			$DBIDS['xr_ch_prot_map']."\t".
			$XCHAIN[$CHAIN]."\t".
			$UN_ENTRY_ID."\t".
			$ALIGN_INFO['PERC_SIM']."\t".
			$ALIGN_INFO['PERC_IDEN']."\t".
			$ALIGN_INFO['LEN_ALIGN']."\t".
			$ALIGN_INFO['PERC_SIM_COM']."\t".
			$ALIGN_INFO['PERC_IDEN_COM']."\t".
			"T\tF\t".
			$ALIGN_INFO['MUTANT']."\n");
	
			foreach ($ALIGN_LIST as &$AL_POS)
			{
				if ($AL_POS[1]=='NULL')continue;
				$COUNTS['xr_ch_prot_pos']++;
				if (($AL_POS[0]!='NULL') && !isset($XRES[$CHAIN][$AL_POS[0]]))
				{
					echo "E4 ERROR\n";
					print_r($ALIGN_LIST);
					echo $AL_POS[0];
					print_r($XRES[$CHAIN]);
					//exit;
					return 'E4';
				}
				//array($DB_XR,$DB_UN,$TYPE);
				++$DBIDS['xr_ch_prot_pos'];
				fputs($FILES['xr_ch_prot_pos'],
			$DBIDS['xr_ch_prot_pos']."\t".
			(($AL_POS[0]!='NULL')?$XRES[$CHAIN][$AL_POS[0]]:'NULL')."\t".
			$AL_POS[1]."\t".
			$AL_POS[2]."\t".$DBIDS['xr_ch_prot_map']."\n");
			}
	
		}
	}


	
$VALID=true;
$ERR_CODE_ID=5;
$ERR_CODE='E';
global $COL_ORDER;
global $GLB_VAR;
global $DB_INFO;
#TODO IS this good?
$TO_PROCESS=array('xr_ch_prot_map','xr_ch_prot_pos');
foreach ($COL_ORDER as $NAME=>$CTL)
{
	if (!in_array($NAME,$TO_PROCESS))continue;
echo $NAME."\n";


	addLog("inserting ".$NAME." records");
	$res=array();
	fclose($FILES[$NAME]);
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	//echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	$FILES[$NAME]=fopen($NAME.'.csv','w');
}



echo "\tIS VALID:".(($VALID)?"YES":"NO")."\t";

if ($VALID){
	$QUERIES=array('xr_ch_prot_map'=>'SELECT COUNT(*) co FROM xr_ch_prot_map xch, xr_chain xc WHERE xc.xr_chain_id = xch.xr_chain_id AND  xr_entry_id = '.$ENTRY['INIT']['DBID'],
	'xr_ch_prot_pos'=>'SELECT COUNT(*) co FROM xr_ch_prot_pos xp, xr_ch_prot_map xch, xr_chain xc WHERE xp.xr_ch_prot_map_id=xch.xr_ch_prot_map_id AND  xc.xr_chain_id = xch.xr_chain_id AND  xr_entry_id = '.$ENTRY['INIT']['DBID'],
);
	foreach ($COUNTS as $TBL=>$VALUE)
	{
		
		$res=runQuery($QUERIES[$TBL]); if ($res===false)failProcess($JOB_ID."017",'Unable to run query'); #TODO any reason I wouldn't put fail process here?
		++$ERR_CODE_ID;
		if ($res[0]['co']!=$VALUE){
			echo "\tDIFFERENT VALUE ".$TBL." ".$VALUE."=>".$res[0]['co'];
			$VALID=false;
			$ERR_CODE.=$ERR_CODE_ID;break;}
	}

}
echo "\tIS VALID:".(($VALID)?"YES":"NO")."\n";
if ($VALID)return 'OK';



foreach ($TBLS as $TBL)	runQueryNoRes("DELETE FROM ".$TBL." WHERE ".$TBL.'_id >= '.$REF_DBIDS[$TBL]);

return 'E9';

}


function insertXrayStructure(&$ENTRY)
{
	
	global $STATIC_DIR;
	$FILES=array();
	global $DBIDS;
	global $ORL_PAR;
	$REF_DBIDS=$DBIDS;
	foreach ($DBIDS as $TBL=>$POS)
		{
			$FILES[$TBL]=fopen($TBL.'.csv','w');
			if (!$FILES[$TBL])		failProcess($JOB_ID."018",'Unable to open '.$TBL.'.csv');
		}
	
	global $INTERS;
	$VALID=true;
	global $TPL_RES;
	$path=$ENTRY['DIR'].'/STRUCTURE/'.$ENTRY['INIT']['ENTRY_NAME'].'.xml';
	if (!checkFileExist($path))return 'E8';
	$content=file_get_contents($path);
	$xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
	$json = json_encode($xml);
	$scontent = json_decode($json,TRUE);
	echo "INSERTING\n";
		$COUNTS=array('xr_chain'=>0,'xr_res'=>0,'xr_atom'=>0,'xr_bond'=>0,'xr_inter_res'=>0,'xr_ppi'=>0);

$MAP_RES=array();$MAP_ATOM=array();$MAP_CHAIN=array();
		$ALL_CHAINS=null;
		if (isset($scontent['Chains']['Chain'][0]))$ALL_CHAINS=&$scontent['Chains']['Chain'];
		else $ALL_CHAINS=&$scontent['Chains'];
	foreach ($ALL_CHAINS as &$CHAIN_INFO)
	{
		
		$DBIDS['xr_chain']++;
		$COUNTS['xr_chain']++;
		$MAP_CHAIN[$CHAIN_INFO['@attributes']['name']]=$DBIDS['xr_chain'];
		fputs($FILES['xr_chain'],
			$DBIDS['xr_chain']."\t".
			$ENTRY['INIT']['DBID']."\t".
			$CHAIN_INFO['@attributes']['name']."\t".
			$CHAIN_INFO['@attributes']['nres']."\t".
			$CHAIN_INFO['@attributes']['type']."\n");
		
			$ALL_RESIDUES=null;
			if (isset($CHAIN_INFO['Residues']['Residue'][0]))$ALL_RESIDUES=&$CHAIN_INFO['Residues']['Residue'];
			else $ALL_RESIDUES=&$CHAIN_INFO['Residues'];

		foreach ($ALL_RESIDUES as &$RESIDUE)
		{
			$DBIDS['xr_res']++;
			$COUNTS['xr_res']++;
			$MAP_RES[$RESIDUE['@attributes']['identifier']]=$DBIDS['xr_res'];
			$RNAME=$RESIDUE['@attributes']['rname'];
			fputs($FILES['xr_res'],
				$DBIDS['xr_res']."\t".
				$DBIDS['xr_chain']."\t".
				$TPL_RES[$RNAME]['id']."\t".
				$RESIDUE['@attributes']['rid']."\t");
			if (isset($RESIDUE['@attributes']['cacoo']))fputs($FILES['xr_res'],$RESIDUE['@attributes']['cacoo']."\n");
			else fputs($FILES['xr_res'],"NULL\n");
		//print_r($TPL_RES[$RNAME]);

			$ALL_ATOMS=null;
			if (isset($RESIDUE['Atoms']['Atom'][0]))$ALL_ATOMS=&$RESIDUE['Atoms']['Atom'];
			else $ALL_ATOMS=&$RESIDUE['Atoms'];
			foreach ($ALL_ATOMS as &$ATOM)
			{
				if ($ATOM==array() || $ATOM==null)continue;
				if (!isset($ATOM["@attributes"]))continue;
				$DBIDS['xr_atom']++;	
				$COUNTS['xr_atom']++;
				$ATTR=&$ATOM['@attributes'];
				$TPL_ID='NULL';
				if ($ATTR['mol2type']!='H' && $ATTR['mol2type']!='Du')
				{
					if ($ATTR['aname']=='CH3' && $RNAME=='NME')$ATTR['aname']='C';
					if ($ATTR['aname']=='OXT' && $RNAME=='ACE')$ATTR['aname']='O';
					if (!isset($TPL_RES[$RNAME]['atom'][$ATTR['aname']]))
					{
						echo "######\n".$ATTR['aname']."\n";
						print_r($TPL_RES[$RNAME]);
						exit;
						return 'E12';
						
					}
					$TPL_ID=$TPL_RES[$RNAME]['atom'][$ATTR['aname']];
				}
				$MAP_ATOM[$ATTR['identifier']]=$DBIDS['xr_atom'];
				fputs($FILES['xr_atom'],
					$DBIDS['xr_atom']."\t".
					trim($ATTR['identifier'])."\t".
					$TPL_ID."\t".
					$DBIDS['xr_res']."\t".
					$ATTR['charge']."\t".
					$ATTR['mol2type']."\t".
					$ATTR['BFACTOR']."\t".
					$ATTR['x']."\t".
					$ATTR['y']."\t".
					$ATTR['z']."\n");
				
			}

		}
	}
	foreach ($ALL_CHAINS as &$CHAIN_INFO)
	{
		$CHAIN_NAME=$CHAIN_INFO['@attributes']['name'];
		$ALL_BONDS=null;
			if (isset($CHAIN_INFO['Bonds']['Bond'][0]))$ALL_BONDS=&$CHAIN_INFO['Bonds']['Bond'];
			else $ALL_BONDS=&$CHAIN_INFO['Bond'];

		foreach ($ALL_BONDS as &$BOND)
		{
			$COUNTS['xr_bond']++;
			$DBIDS['xr_bond']++;
			$STR=$DBIDS['xr_bond']."\t";
			switch ($BOND['@attributes']['type'])
			{
				case 'SINGLE':$STR.='1';break;
				case 'DOUBLE':$STR.='2';break;
				case 'TRIPLE':$STR.='3';break;
				case 'DELOCALIZED':$STR.='de';break;
				case 'AROMATIC Bond':$STR.='ar';break;
				case 'AROMATIC_BD':$STR.='ar';break;
				case 'AMIDE':$STR.='am';break;
				case 'QUADRUPLE':$STR.='qa';break;
				case 'DUMMY':$STR.='du';break;
				case 'FUSED':$STR.='fu';break;
				case 'UNDEFINED':$STR.='un';break;
				default:return 'E9';
			}

			
			$STR.="\t".$MAP_ATOM[$BOND['@attributes']['atom1']]."\t".$MAP_ATOM[$BOND['@attributes']['atom2']]."\t".$MAP_CHAIN[$CHAIN_NAME]."\n";
			fputs($FILES['xr_bond'],$STR);
		}
	
	}


	
	
	
foreach ($scontent['Inters']['Inter'] as &$IT)
{
	$INTER=&$IT['@attributes'];
	if ($INTER['inter_type']=='Weak H-Bond')continue;
	//$INTERS
	$COUNTS['xr_inter_res']+=2;
	$DBIDS['xr_inter_res']++;
	$STR=$DBIDS['xr_inter_res']."\t";
	
	if (isset($INTER['atom1']) && $INTER['atom1']!='')$STR.=$MAP_ATOM[$INTER['atom1']]."\t";else $STR.="NULL\t";
	if ($INTER['res1']!='')$STR.=$MAP_RES[$INTER['res1']]."\t";else $STR.="NULL\t";
	$STR.=$INTER['atom1_list']."\t";
	if ($INTER['res2']!='')$STR.=$MAP_RES[$INTER['res2']]."\t";else $STR.="NULL\t";
	$STR.=$INTER['atom2_list']."\t";
	if (isset($INTER['atom2']) && $INTER['atom2']!='')$STR.=$MAP_ATOM[$INTER['atom2']]."\t";else $STR.="NULL\t";

	switch ($INTER['inter_type'])
	{
		case "Hydrophobic": $STR.=$INTERS['Hydrophobic']."\t";break;
		case "H-Bond": $STR.=$INTERS['H-Bond']."\t";break;
		case "Ionic": $STR.=$INTERS['Ionic']."\t";break;
		case "Aromatic EF": $STR.=$INTERS['Aromatic EF']."\t";break;
		case "Aromatic PD": $STR.=$INTERS['Aromatic PD']."\t";break;
		case "Metal": $STR.=$INTERS['Metal']."\t";break;
		//case "Weak H-Bond"},
		case "Cation PI": $STR.=$INTERS['Cation PI']."\t";break;
		case "Halogen Bond": $STR.=$INTERS['Halogen Bond']."\t";break;
		case "H-Arene": $STR.=$INTERS['H-Arene']."\t";break;
		case "Halogen Arom": $STR.=$INTERS['Halogen Arom']."\t";break;
		case "Halogen HBond": $STR.=$INTERS['Halogen HBond']."\t";break;
		case "Carbonyl PI": $STR.=$INTERS['Carbonyl PI']."\t";break;
		case "ANION PI": $STR.=$INTERS['Anion PI']."\t";break;
		default: return 'E10';
		//case "Clash": $STR.=$INTERS['Hydrophobic']."\t";break;

	}
	$STR.=$INTER['dist']."\t";
	if (isset($INTER['angle']))$STR.=$INTER['angle'];else $STR.='NULL';
	$STR.="\n";
	
	fputs($FILES['xr_inter_res'],$STR);
	$DBIDS['xr_inter_res']++;
	$STR=$DBIDS['xr_inter_res']."\t";
	if (isset($INTER['atom2']) && $INTER['atom2']!='')$STR.=$MAP_ATOM[$INTER['atom2']]."\t";else $STR.="NULL\t";
	if ($INTER['res2']!='')$STR.=$MAP_RES[$INTER['res2']]."\t";else $STR.="NULL\t";
	$STR.=$INTER['atom2_list']."\t";
	if ($INTER['res1']!='')$STR.=$MAP_RES[$INTER['res1']]."\t";else $STR.="NULL\t";
	$STR.=$INTER['atom1_list']."\t";
	if (isset($INTER['atom1']) && $INTER['atom1']!='')$STR.=$MAP_ATOM[$INTER['atom1']]."\t";else $STR.="NULL\t";
	
	switch ($INTER['inter_type'])
	{
		case "Hydrophobic": $STR.=$INTERS['Hydrophobic']."\t";break;
		case "H-Bond": $STR.=$INTERS['H-Bond']."\t";break;
		case "Ionic": $STR.=$INTERS['Ionic']."\t";break;
		case "Aromatic EF": $STR.=$INTERS['Aromatic EF']."\t";break;
		case "Aromatic PD": $STR.=$INTERS['Aromatic PD']."\t";break;
		case "Metal": $STR.=$INTERS['Metal']."\t";break;
		//case "Weak H-Bond"},
		case "Cation PI": $STR.=$INTERS['Cation PI']."\t";break;
		case "Halogen Bond": $STR.=$INTERS['Halogen Bond']."\t";break;
		case "H-Arene": $STR.=$INTERS['H-Arene']."\t";break;
		case "Halogen Arom": $STR.=$INTERS['Halogen Arom']."\t";break;
		case "Halogen HBond": $STR.=$INTERS['Halogen HBond']."\t";break;
		case "Carbonyl PI": $STR.=$INTERS['Carbonyl PI']."\t";break;
		case "ANION PI": $STR.=$INTERS['Anion PI']."\t";break;
		default: return 'E10';
		//case "Clash": $STR.=$INTERS['Hydrophobic']."\t";break;

	}
	$STR.=$INTER['dist']."\t";
	if (isset($INTER['angle']))$STR.=$INTER['angle'];else $STR.='NULL';
	$STR.="\n";


	fputs($FILES['xr_inter_res'],$STR);
}


foreach ($ENTRY['FILES']['STRUCTURE'] as $NAME=>&$S)
{
	if ($S['TYPE']!='MULTIMER')continue;
	$tab=explode("_",$NAME);
	if (count($tab)!=2)continue;
	$COUNTS['xr_ppi']+=2;
	$DBIDS['xr_ppi']++;fputs($FILES['xr_ppi'],$DBIDS['xr_ppi']."\t".$MAP_CHAIN[$tab[0]]."\t".$MAP_CHAIN[$tab[1]]."\n");
	$DBIDS['xr_ppi']++;fputs($FILES['xr_ppi'],$DBIDS['xr_ppi']."\t".$MAP_CHAIN[$tab[1]]."\t".$MAP_CHAIN[$tab[0]]."\n");
	
}



#TODO fix sqlldr and ctl things
global $COL_ORDER;
global $GLB_VAR;
global $DB_INFO;

$VALID=true;
		$ERR_CODE_ID=11;
		$ERR_CODE='E';
		echo "INSERTING";
	
		#TODO does this econd \copy section need to be any different
		foreach ($COL_ORDER as $NAME=>$CTL)
		{
		//	if (in_array($NAME,$TO_FILTER))continue;
		echo $NAME."\n";
			addLog("inserting ".$NAME." records");
			$res=array();
			fclose($FILES[$NAME]);
			$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.'.$NAME.' '.$CTL.' FROM \''.$NAME.".csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
			$FILES[$NAME]=fopen($NAME.'.csv','w');
		}
		echo "\n";
		echo "\tIS VALID:".(($VALID)?"YES":"NO")."\t";
		
		if ($VALID){
			$QUERIES=array('xr_chain'=>'SELECT COUNT(*) co FROM xr_chain WHERE xr_entry_id = '.$ENTRY['INIT']['DBID'],
			'xr_res'=>'SELECT COUNT(*) co FROM xr_chain xc ,xr_res xr WHERE xr.xr_chain_id = xc.xr_chain_id AND xr_entry_id = '.$ENTRY['INIT']['DBID'],
			'xr_atom'=>'SELECT COUNT(*) co FROM xr_chain xc ,xr_res xr,xr_atom xa WHERE xa.xr_res_id=xr.xr_res_id AND xr.xr_chain_id = xc.xr_chain_id AND xr_entry_id = '.$ENTRY['INIT']['DBID'],
			'xr_bond'=>'SELECT COUNT(*) co FROM xr_chain xc ,xr_res xr,xr_atom xa,xr_bond xb WHERE xb.xr_atom_id_1 = xa.xr_atom_id AND xa.xr_res_id=xr.xr_res_id AND xr.xr_chain_id = xc.xr_chain_id AND xr_entry_id = '.$ENTRY['INIT']['DBID'],
			'xr_ppi'=>'SELECT COUNT(*) co FROM xr_chain xc ,xr_ppi xp WHERE  xc.xr_chain_id = xp.xr_chain_r_id AND xr_entry_id = '.$ENTRY['INIT']['DBID'],
			'xr_inter_res'=>'SELECT COUNT(*) co FROM xr_chain xc ,xr_res xr,xr_inter_res xa WHERE xa.xr_res_id_1=xr.xr_res_id AND xr.xr_chain_id = xc.xr_chain_id AND xr_entry_id = '.$ENTRY['INIT']['DBID']);
			foreach ($COUNTS as $TBL=>$VALUE)
			{
				
				$res=runQuery($QUERIES[$TBL]); if ($res===false)failProcess($JOB_ID."019",'Unable to run query'); #TODO any reason I wouldn't need fail process
				++$ERR_CODE_ID;
				if ($res[0]['co']!=$VALUE){
					echo "\tDIFFERENT VALUE ".$TBL." ".$VALUE."=>".$res[0]['co'];
					$VALID=false;
					$ERR_CODE.=$ERR_CODE_ID;break;}
			}

		}
		echo "\tIS VALID:".(($VALID)?"YES":"NO")."\n";
		if ($VALID)return 'OK';

		$ORDER=array('xr_ppi','xr_inter_res','xr_bond','xr_atom','xr_res','xr_chain');
		
		foreach ($ORDER as $TBL)	runQueryNoRes("DELETE FROM ".$TBL." WHERE ".$TBL.'_id >= '.$REF_DBIDS[$TBL]);
		echo "FAIL\n";
	
		return 'E22';
}

?>	
