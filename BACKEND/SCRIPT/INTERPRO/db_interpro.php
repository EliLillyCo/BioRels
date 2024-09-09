<?php


error_reporting(E_ALL);
ini_set('memory_limit','2000M');
/**
 SCRIPT NAME: db_interpro
 PURPOSE:     Process all interpro files
 
*/

/// Job name - Do not change
$JOB_NAME='db_interpro';


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
	
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_interpro')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];if (!is_dir($W_DIR) && !mkdir($W_DIR)) 			failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
											  if (!chdir($W_DIR)) 						failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
	
	
addLog("Working directory:".$W_DIR);
	

	
addLog("Load GO Entry");
	$res=runQuery('SELECT go_entry_id,ac FROM go_entry');
	if ($res===false)																	failProcess($JOB_ID."006",'Unable to run query');
	$GO_ENTRY=array();foreach ($res as $line)$GO_ENTRY[$line['ac']]=$line['go_entry_id'];

addLog("load Tree");
	/// First we read the tree to create the hierarchy
	$fp=fopen("tree.txt",'r');if (!$fp)													failProcess($JOB_ID."007",'Unable to open tree.txt');
	$ENTRY=array('ROOT'=>array(0,0,0,array()));
	$PARENT=array();
	///IPR000109::Proton-dependent oligopeptide transporter family::
	///--IPR004768::Oligopeptide transporter::
	///--IPR005279::Dipeptide/tripeptide permease::
	///----IPR023517::Amino acid/peptide transporter family, dipeptide and tripeptide permease A::

	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		$pos=0;
		/// We find the first I, 
		for ($I=0;$I<100;++$I) {if (substr($line,$I,1)=='I'){$pos=$I;break;}}
		//which determine the level
		if ($pos===false){$LVL=0;$pos=0;}
		else $LVL=(int)($pos/2);
		/// Then we get the name
		$pos2=strpos($line,':');
		$name=substr($line,$pos,$pos2-$pos);
	//	echo $pos.' '.$pos2.' '.$LVL.'|'.$line."|".$name."|\n";


		$PARENT[$LVL]=$name;
		$ENTRY[$name]=array($LVL,0,0,array());
		/// no level -> root
		if ($LVL==0)$ENTRY['ROOT'][3][]=$name;
		else 
		{	/// Otherwise we map it as parent
			$PA=$PARENT[$LVL-1];
			$ENTRY[$PA][3][]=$name;
		}
		//echo "\n\n\n";
	}
	
	/// Create nested set representation that is going to assign boundary numbers.
	//// Let's say that the root has for boundary 1 10.
	//// The two childs:  A 2-5 and B 6-9
	/// And the A has a child C 3-4
	/// If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
	//// By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
	//// Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for theleft side and <10 for the right side, leading to A B and C.
	defLevels($ENTRY,'ROOT',-1,-1);
	function defLevels(&$ENTRY,$NAME,$LEVEL,$VALUE)
	{
		global $fp;
		global $INV_CLASS;
		global $MAX_DBID;
		++$LEVEL;$VALUE++;		
		$LEFT=$VALUE;
		$ENTRY[$NAME][0]=$LEVEL;
		$ENTRY[$NAME][1]=$VALUE;
		
		//echo $LEVEL."\t".$TAX_ID."\t".count($tab)."\n";
		
		foreach ($ENTRY[$NAME][3] as $CHILD)	$VALUE=defLevels($ENTRY,$CHILD,$LEVEL,$VALUE);
		
		++$VALUE;
		
		$ENTRY[$NAME][2]=$VALUE;
		return $VALUE;
	}
	$TREE=$ENTRY;
	

addLog("Load IP_SIGNATURE Entry");
	$res=runQuery('SELECT ip_sign_dbkey, ip_sign_dbname, ip_signature_id FROM ip_signature');
	if ($res===false)																	failProcess($JOB_ID."008",'Unable to get current signatures');
	$IP_SIGN=array();
	foreach ($res as $line)
		$IP_SIGN[$line['ip_sign_dbname']][$line['ip_sign_dbkey']]=$line['ip_signature_id'];



	
addLog("Get Max DBIDS")	;
	/// Get max PK values for each table so we can do batch insert
	$DBIDS=array('ip_entry'=>-1,
	'ip_signature'=>-1,
	'ip_ext_db'=>-1,
	'ip_go_map'=>-1,
	'ip_sign_prot_seq'=>-1,
	'ip_pmid_map'=>-1);
	
	foreach ($DBIDS as $TBL=>&$POS)
	{
		$res=array();
		$res=runQuery('SELECT MAX('.$TBL.'_id) co FROM '.$TBL);
		if ($res===false)																failProcess($JOB_ID."009",'Unable to get Max ID for '.$TBL);
		
		$DBIDS[$TBL]=($res[0]['co']=='')?0:$res[0]['co'];
	}

	/// COL_ORDER list all tables that we are going to insert into and their columns 
	$COL_ORDER=array('ip_entry'=>'(ip_entry_id,ipr_id,protein_count,short_name,entry_type,abstract,name,ip_level, ip_level_left,ip_level_right)',
	'ip_signature'=>'(ip_signature_id,ip_entry_id,ip_sign_dbname,ip_sign_dbkey,ip_sign_name)',	
	'ip_ext_db'=>'(ip_ext_db_id, ip_entry_id, db_name, db_val)',
	'ip_go_map'=>'(ip_go_map_id,ip_entry_id,go_entry_id)',
	'ip_pmid_map'=>'(ip_pmid_map_id,pmid_entry_id,ip_entry_id)',
	'ip_sign_prot_seq'=>'(ip_sign_prot_seq_id,ip_signature_id,prot_seq_id,start_pos,end_pos,status,model,evidence,score)'
	);

	print_r($DBIDS);
	
addLog("Creating directories");
	if (!is_dir('INSERT') && !mkdir('INSERT'))											failProcess($JOB_ID."010",'Unable to create INSERT directory');
	if (!chdir('INSERT'))																failProcess($JOB_ID."011",'Unable to access INSERT directory');
	

	if (is_dir('COMMENTS') && !deleteDir('COMMENTS'))									failProcess($JOB_ID."012",'Unable to delete comments directory');
	if (!mkdir('COMMENTS'))																failProcess($JOB_ID."013",'Unable to create comments directory');



addLog("opening files");
		foreach ($DBIDS as $TBL=>&$POS)
		{
			$FILES[$TBL]=fopen($TBL.'.csv','w');
			if (!$FILES[$TBL])															failProcess($JOB_ID."014",'Unable to open '.$TBL.'.csv');
		}

		$DBIDS['DESC_FILES']=0;

		

addLog("Get List Uniprots");
		/// We are only going to process the interpro signature for which we have uniprot records
		if (!is_file('LIST_UNIP'))
		{
			runQueryToFile("SELECT prot_seq_id, prot_identifier 
			FROM prot_seq us, prot_entry ue
			 WHERE ue.prot_entry_id = us.prot_entry_id  AND is_primary='T'",'LIST_UNIP',$JOB_ID.'015');
		}
		
		
		$fp=fopen('LIST_UNIP','r');	if (!$fp)											failProcess($JOB_ID."016",'Unable to open LIST_UNIP');
		$UNIP_LIST=array();
		$line=stream_get_line($fp,1000,"\n");
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000,"\n");
			if ($line=="") continue;
			$tab=explode("\t",$line);
			$UNIP_LIST[$tab[1]]=$tab[0];
		}
		fclose($fp);
		

		
			
addLog("Read interpro file");
	$fp=fopen('../interpro.xml','r');	if (!$fp)											failProcess($JOB_ID."017",'Unable to open interpro.xml');
	
	$N_ENTRY=0;
	while(!feof($fp))
	{	
		echo "file position".ftell($fp)."\n";
		$line=stream_get_line($fp,1000,"\n");
		
		/// Looking for the next interpro record
		if (strpos($line,'<interpro ')===false)continue;
		++$N_ENTRY;
		
		if ($N_ENTRY%100==0){echo $N_ENTRY."\n";}//break;}

		/// Getting all the lines within that record
		$STR=$line."\n";
		do
		{
			$line=stream_get_line($fp,10000,"\n");
			$STR.=$line."\n";
		}
		while($line!='</interpro>');
		
		/// When the text is within a <p> HTML tag, the process has trouble to interpret it,
		/// so we bound the content  with <![CDATA[  ]] so it's not interpreted
		$STR= preg_replace('/<p>(.{1,10000})<\/p>/s','<p><![CDATA[$1]]></p>',$STR);
		/// Read it as xml
		$xml = simplexml_load_string($STR, "SimpleXMLElement", LIBXML_NOCDATA);
		
		
		/// Convert it into an array
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);
		
		// Process the entry
		addLog("PROCESS ENTRY");
		$ENTRY=processEntry($array);
		
		addLog("PROCESS DB");
		processToDB($ENTRY);
		
		addLog("END ENTRY");
		if ($N_ENTRY%1000!=0)continue;
		///Every batch, we save the interpro records into files
		
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
			if ($return_code !=0 )																	failProcess($JOB_ID."018",'Unable to insert '.$NAME); 
			
			$FILES[$NAME]=fopen($NAME.'.csv','w');
			if (!$FILES[$NAME])																	failProcess($JOB_ID."019",'Unable to open '.$NAME.'.csv');
			
		}

	}

	fclose($fp);


addLog("Process match complete");



	$fp=fopen('../match_complete.xml','r');if (!$fp)											failProcess($JOB_ID."020",'Unable to open match_complex.xml');
	$fpE=fopen("failed.json",'w');		   if (!$fpE)											failProcess($JOB_ID."021",'Unable to open failed.json');
	$N_ENTRY=0;$N_PRO=0;$N_BATCH_PRO=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if (strpos($line,'<protein ')===false)continue;
		++$N_ENTRY;
		if ($N_ENTRY%10000==0){echo $N_ENTRY."\t".$N_PRO."\n";}
		//if ($N_ENTRY<218370000)continue;
		if (substr($line,-2)=='/>')continue;
		$matches=array();
		preg_match('/name=\"([A-Za-z0-9_]{1,50})\"/',$line,$matches);
		
		if (count($matches)==0|| !isset($UNIP_LIST[$matches[1]]))continue;
		
		/// Getting all the lines within that record
		$STR=$line."\n";
		do
		{
			$line=stream_get_line($fp,10000,"\n");
			$STR.=$line."\n";
		}
		while($line!='</protein>');
		
		/// Reading it as xml
		$xml = simplexml_load_string($STR, "SimpleXMLElement", LIBXML_NOCDATA);
		/// Converting it into an array via json
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);
		
		
		if (processProtein($array,$matches[1],$fpE)){$N_BATCH_PRO++;$N_PRO++;}
		if ($N_BATCH_PRO<1000)continue;
		$N_BATCH_PRO=0;
		pushToDB();
		
	//if ($N_PRO>=100)break;
	}


pushToDB();

pushToProd();

successProcess();












//////////////////////////////

function pushToDB()
{
	global $COL_ORDER;
	global $FILES;
	global $GLB_VAR;
	global $DB_INFO;
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
			if ($return_code!=0)																failProcess($JOB_ID."A01",'Unable to insert '.$NAME);
			
			$FILES[$NAME]=fopen($NAME.'.csv','w');
			if (!$FILES[$NAME])																failProcess($JOB_ID."A02",'Unable to open '.$NAME.'.csv');
			
		}
}








function loadUniInfo($UNI)
{
	global $JOB_ID;
	$res=runQuery("SELECT ip_sign_prot_seq_id,isi.ip_signature_id, us.prot_seq_id,start_pos,end_pos, 
				iu.status, model, evidence, score,ip_sign_dbname,ip_sign_dbkey
			FROM ip_signature isi,ip_sign_prot_seq iu,prot_seq us, prot_entry ue 
			WHERE ue.prot_entry_id = us.prot_entry_id AND us.prot_seq_id = iu.prot_seq_id
			 AND iu.ip_signature_id=isi.ip_signature_id AND prot_identifier ='".$UNI."'");
	if ($res===false)failProcess($JOB_ID."B01",'Unable to load Uniprot info '.$UNI);
	$DATA=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[]=$line;
	}
	return $DATA;

}











function processProtein($ini_data,$UNI_NAME,&$fpE)
{
	/// Process IP signature to protein sequence

	//echo $UNI_NAME."\n";
	global $JOB_ID;
	global $FILES;
	global $DBIDS;
	global $IP_SIGN;
	$AC=$ini_data['@attributes']['id'];
	$UNI=$ini_data['@attributes']['name'];

	/// 
	$ENTRY=loadUniInfo($UNI);
	$res=runQuery("SELECT prot_seq_id 
					FROM prot_seq us, prot_entry ue 
					WHERE ue.prot_entry_id = us.prot_entry_id 
					AND prot_identifier='".$UNI_NAME."' and is_primary='T'");
	if ($res===false)													failProcess($JOB_ID."C01",'Unable to run query for '.$UNI_NAME);
	if (count($res)==0)return false;
	
	$UN_SEQ_ID=$res[0]['prot_seq_id'];
	foreach ($ini_data['match'] as $match)
	{
		if (!isset($match['ipr']))continue;
		//echo "IN2\n";
		foreach ($match['lcn'] as $L)
		{
			//echo "IN3\n";
			$START='';$END='';$SCORE='';
			if (!isset($L['@attributes']))	$data=array_merge($match['@attributes'],$L);
			
			else 	$data=array_merge($match['@attributes'],$L['@attributes']);
				
			$FOUND=false;
			#TODO: is the ='VALID' on line 355 need to be lower case? Really everything here?
			foreach ($ENTRY as $REF_MATCH)
			{
				if ($REF_MATCH['ip_sign_dbname']!=$data['dbname'])continue;
				if ($REF_MATCH['ip_sign_dbkey']!=$data['id'])continue;
				if ($REF_MATCH['start_pos']!=$data['start'])continue;
				if ($REF_MATCH['end_pos']!=$data['end'])continue;
				$REF_MATCH['db_status']='VALID';
				if ($REF_MATCH['status']!=$data['status']){$REF_MATCH['status']=$data['status'];$REF_MATCH['db_status']='to_upd';}
				if ($REF_MATCH['model']!=$data['model']){$REF_MATCH['model']=$data['model'];$REF_MATCH['db_status']='to_upd';}
				if ($REF_MATCH['evidence']!=$data['evd']){$REF_MATCH['evidence']=$data['evd'];$REF_MATCH['db_status']='to_upd';}
				if ($REF_MATCH['score']!=$data['score']){$REF_MATCH['score']=$data['score'];$REF_MATCH['db_status']='to_upd';}
								
				$FOUND=true;
			}

			if ($FOUND)continue;
			//echo "IN4444\n";
			if (!isset($IP_SIGN[$data['dbname']][$data['id']])) 
			{
				fputs($fpE,json_encode($data)."\n");
				continue;
			}
			$DBIDS['ip_sign_prot_seq']++;
			fputs($FILES['ip_sign_prot_seq'],$DBIDS['ip_sign_prot_seq']."\t".
			$IP_SIGN[$data['dbname']][$data['id']]."\t".
			$UN_SEQ_ID."\t".
			$data['start']."\t".
			$data['end']."\t".
			$data['status']."\t".
			$data['model']."\t".
			$data['evd']."\t".
			$data['score']."\n");
		}
	}
	return true;

}



function loadData($IPR_ID)
{

	/// Loading all information relate to that record
	global $JOB_ID;
	global $TREE;
	$DATA=array('INFO'=>array(),'PUBLI'=>array(),'IP_SIGN'=>array(),'EXTDB'=>array(),'GO'=>array());
	$res=array();
	
	
	
	/// Getting ip_entry information
	$query="SELECT ip_entry_id,ipr_id,protein_count,short_name,entry_type,name,abstract,ip_level,ip_level_left,ip_level_right 
	FROM ip_entry 
	WHERE ipr_id='".$IPR_ID."'";
	$res=runQuery($query);
	
	
	if ($res===false) failProcess($JOB_ID."C01","Unable to run query ".$query);
	
	/// No data -> create basic information
	if (count($res)==0)
	{
		$DATA['INFO']=array('ip_entry_id'=>-1,
							'ipr_id'=>$IPR_ID,
							'protein_count'=>'',
							'short_name'=>'',
							'entry_type'=>'Domain',
							'name'=>'',
							'abstract'=>'',
							'ip_level'=>isset($TREE[$IPR_ID][0])?$TREE[$IPR_ID][0]:'',
							'ip_level_left'=>isset($TREE[$IPR_ID][1])?$TREE[$IPR_ID][1]:'',
							'ip_level_right'=>isset($TREE[$IPR_ID][2])?$TREE[$IPR_ID][2]:'',
							'DB_STATUS'=>'TO_INS'

							);
		
		return $DATA;
	}



	$DATA['INFO']=$res[0];
	$DATA['INFO']['ABST_STATUS']='VALID';
	$DATA['INFO']['DB_STATUS']='VALID';

	/// Comparing tree level with current level
	if (isset($TREE[$IPR_ID]))
	{
		if ($TREE[$IPR_ID][0]!=$DATA['INFO']['ip_level'])		{$DATA['INFO']['ip_level']=$TREE[$IPR_ID][0];$DATA['INFO']['DB_STATUS']='TO_UPD';}
		if ($TREE[$IPR_ID][1]!=$DATA['INFO']['ip_level_left'])	{$DATA['INFO']['ip_level_left']=$TREE[$IPR_ID][1];$DATA['INFO']['DB_STATUS']='TO_UPD';}
		if ($TREE[$IPR_ID][2]!=$DATA['INFO']['ip_level_right']){$DATA['INFO']['ip_level_right']=$TREE[$IPR_ID][2];$DATA['INFO']['DB_STATUS']='TO_UPD';}
	}

	/// Getting PMID entries
	$query='SELECT pmid, ip.ip_pmid_map_id 
			FROM pmid_entry pe, ip_pmid_map ip 
			WHERE ip.pmid_entry_id=pe.pmid_entry_id 
			AND ip_entry_id = '.$DATA['INFO']['ip_entry_id'];
	$res=runQuery($query);
	if ($res===false) 																							failProcess($JOB_ID."C02","Unable to run query ".$query);
	foreach ($res as $line)
	{
		$DATA['PUBLI'][$line['pmid']]=array(
			'ip_pmid_map_id'=>$line['ip_pmid_map_id'],
			'DB_STATUS'=>'FROM_DB');
	}

	/// Getting the list of signatures:
	$query='SELECT ip_signature_id,ip_entry_id,ip_sign_dbname,ip_sign_dbkey,ip_sign_name
			FROM ip_signature 
				WHERE  ip_entry_id = '.$DATA['INFO']['ip_entry_id'];
	$res=runQuery($query);
	if ($res===false)																							 failProcess($JOB_ID."C03","Unable to run query ".$query);
	foreach ($res as $line)
	{
		$DATA['IP_SIGN'][]=array(
			'DBID'=>$line['ip_signature_id'],
			'ip_sign_dbname'=>$line['ip_sign_dbname'],
			'ip_sign_dbkey'=>$line['ip_sign_dbkey'],
			'ip_sign_name'=>$line['ip_sign_name'],
			'DB_STATUS'=>'FROM_DB');
	}

	/// Getting the list of external identifiers:
	$query='SELECT ip_ext_db_id, db_name, db_val FROM ip_ext_db WHERE  ip_entry_id = '.$DATA['INFO']['ip_entry_id'];
	$res=runQuery($query);
	if ($res===false)																							 failProcess($JOB_ID."C04","Unable to run query ".$query);
	
	
	foreach ($res as $line)
	{
		$DATA['EXTDB'][]=array(
			'DBID'=>$line['ip_ext_db_id'],
			'db_name'=>$line['db_name'],
			'db_value'=>$line['db_val'],
			'DB_STATUS'=>'FROM_DB');
	}

	$query='SELECT ac,ge.go_entry_id, ip_go_map_id 
			FROM ip_go_map igm, go_entry ge 
			WHERE ge.go_entry_id = igm.go_entry_id 
			AND  ip_entry_id = '.$DATA['INFO']['ip_entry_id'];
	$res=runQuery($query);
	if ($res===false)																							 failProcess($JOB_ID."C05","Unable to run query ".$query);
	foreach ($res as $line)
	{
		$DATA['GO'][]=array(
			'DBID'=>$line['ip_go_map_id'],
			'AC'=>$line['ac'],
			'go_entry_id'=>$line['go_entry_id'],
			'DB_STATUS'=>'FROM_DB');
	}
	return $DATA;
}


function processToDB(&$ENTRY)
{
	global $DBIDS;
	global $FILES;
	global $STATIC_DIR;
	global $ORL_PAR;
	global $JOB_ID;
	global $DB_CONN;
	//echo "DB:".$ENTRY['INFO']['DB_STATUS']."\n";
	//print_r($DATA['INFO']);

	if ($ENTRY['INFO']['DB_STATUS']=='TO_INS')
	{
		addLog("INSERTION ");
		$DBIDS['ip_entry']++;
		$ENTRY['INFO']['ip_entry_id']=$DBIDS['ip_entry'];
		$ABSTR=isset($ENTRY['INFO']['abstract'])?$ENTRY['INFO']['abstract']:'NULL';
		if ($ABSTR!='NULL')$ABSTR=str_replace('"','""',$ABSTR);

		fputs($FILES['ip_entry'],$DBIDS['ip_entry']."\t".
			$ENTRY['INFO']['ipr_id']."\t".
			$ENTRY['INFO']['protein_count']."\t".
			$ENTRY['INFO']['short_name']."\t".
			$ENTRY['INFO']['entry_type']."\t".
			'"'.$ABSTR.'"'."\t".
			'"'.str_replace('"','""',$ENTRY['INFO']['name']).'"'."\t".
			(($ENTRY['INFO']['ip_level']!='')?$ENTRY['INFO']['ip_level']:'NULL')."\t".
			(($ENTRY['INFO']['ip_level_left']!='')?$ENTRY['INFO']['ip_level_left']:'NULL')."\t".
			(($ENTRY['INFO']['ip_level_right']!='')?$ENTRY['INFO']['ip_level_right']:'NULL')."\n");
	
	}
	else if ($ENTRY['INFO']['DB_STATUS']=='TO_UPD' && $ENTRY['INFO']['abstract']!='')
	{
		addLog("UPDATE");
		$DB_CONN->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		try {
			
			$query="UPDATE ip_entry SET ipr_id='".$ENTRY['INFO']['ipr_id']."', 
			protein_count=".$ENTRY['INFO']['protein_count'].", 
			short_name='".str_replace("'","''",$ENTRY['INFO']['short_name'])."',
			entry_type='".str_replace("'","''",$ENTRY['INFO']['entry_type'])."', 
			name='".str_replace("'","''",$ENTRY['INFO']['name'])."',
			ip_level=".(($ENTRY['INFO']['ip_level']=='')?'NULL':$ENTRY['INFO']['ip_level']).",
			ip_level_left=".(($ENTRY['INFO']['ip_level_left']=='')?'NULL':$ENTRY['INFO']['ip_level_left']).",
			ip_level_right=".(($ENTRY['INFO']['ip_level_right']=='')?'NULL':$ENTRY['INFO']['ip_level_right'])."";

			if ($ENTRY['INFO']['ABST_STATUS']=='TO_UPD')
			{   
				#TODO :CLOB?
				$query.=",abstract= :CLOB WHERE ip_entry_id=".$ENTRY['INFO']['ip_entry_id'];
				//echo $query."\n";
				$stmt = $DB_CONN->prepare($query);
				$stmt->bindParam(':CLOB', $ENTRY['INFO']['abstract'], PDO::PARAM_STR, strlen($ENTRY['INFO']['abstract']));
				$blob = NULL;
				$DB_CONN->beginTransaction();
				$stmt->execute();
				$DB_CONN->commit();
				$stmt=null;
			}
			else
			{
				$query.=" WHERE ip_entry_id=".$ENTRY['INFO']['ip_entry_id'];
				//echo $query."\t=>";
				echo runQueryNoRes($query)."\n";
			}
				
			
			
			
		} catch(PDOException $e){
			echo "Exception ". $e->getMessage();
			throw new Exception($e);
		}
		
	} 
	else if ($ENTRY['INFO']['DB_STATUS']=='FROM_DB') 
	{	print_r($ENTRY);
		echo "IP ENTRY SHOULDN'T BE DELETED";exit;
		if (!runQueryNoRes("DELETE FROM ip_entry WHERE ip_entry_id=".$ENTRY['INFO']['ip_entry_id'])) failProcess($JOB_ID."D01",'Unable to delete');
		return;
	}

	
	addLog("PUBLI STATUS");
	foreach ($ENTRY['PUBLI'] as $PMID=>$INFO)
	{
		if ($INFO['DB_STATUS']=='FROM_DB')
		{
			if (!isset($INFO['ip_pmid_map_id']))print_r($INFO);
			$query="DELETE FROM ip_pmid_map WHERE ip_pmid_map_id=".$INFO['ip_pmid_map_id'];
			if (!runQueryNoRes($query)) 															failProcess($JOB_ID."D02",'Unable to delete IP_PMID_MAP_ID='.$INFO['IP_PMID_MAP_ID']);
		}
		else if ($INFO['DB_STATUS']=='TO_INS')
		{
			
			$DBIDS['ip_pmid_map']++;
			$res=runQuery('SELECT pmid_entry_id FROM pmid_entry WHERE pmid='.$PMID);
			if ($res===false)																		failProcess($JOB_ID."D03",'Unable to run query with PMID='.$PMID);
			if (count($res)==0)continue;
			$PMID_DBID=$res[0]['pmid_entry_id'];
			echo $DBIDS['ip_pmid_map']."\t".$PMID_DBID."\t".$ENTRY['INFO']['ip_entry_id']."\n";
			fputs($FILES['ip_pmid_map'],$DBIDS['ip_pmid_map']."\t".$PMID_DBID."\t".$ENTRY['INFO']['ip_entry_id']."\n");
		
		}

	}

	addLog("IP SIGNATURE ");
	//print_r($ENTRY['IP_SIGN']);
	foreach ($ENTRY['IP_SIGN'] as &$INFO)
	{
		
		//echo $INFO['DB_STATUS']."\t|".($INFO['DB_STATUS']=='TO_INS')."|\n";
		if ($INFO['DB_STATUS']=='FROM_DB')
		{
			
			#TODO DBID below?
			if (!runQueryNoRes("DELETE FROM ip_signature WHERE ip_signature_id=".$INFO['DBID'])) failProcess($JOB_ID."D04",'Unable to delete IP_SIGNATURE_ID='.$INFO['DBID']);
		}
		else if ($INFO['DB_STATUS']=='TO_INS')
		{
			
			$DBIDS['ip_signature']++;
			echo "NEWSIGN\t".$DBIDS['ip_signature']."\t".$ENTRY['INFO']['ip_entry_id']."\t".$INFO['ip_sign_dbname']."\t".$INFO['ip_sign_dbkey']."\t".$INFO['ip_sign_name']."\n";
			fputs($FILES['ip_signature'],
				$DBIDS['ip_signature']."\t".
				$ENTRY['INFO']['ip_entry_id']."\t".
				$INFO['ip_sign_dbname']."\t".
				$INFO['ip_sign_dbkey']."\t".
				$INFO['ip_sign_name']."\n");
				
		}
	}
	//exit;
	addLog("GO ENTRY");
	foreach ($ENTRY['GO'] as &$INFO)
	{
		if ($INFO['DB_STATUS']=='FROM_DB')
		{
			
			if (!runQueryNoRes("DELETE FROM ip_go_map WHERE ip_go_map_id=".$INFO['DBID'])) failProcess($JOB_ID."D05",'Unable to delete IP_GO_MAP_ID='.$INFO['DBID']);
		}
		#TODO below - go_entry_id not recognized
		else if ($INFO['DB_STATUS']=='TO_INS' && $INFO['go_entry_id']!='')
		{
			$DBIDS['ip_go_map']++;
			fputs($FILES['ip_go_map'],$DBIDS['ip_go_map']."\t".$ENTRY['INFO']['ip_entry_id']."\t".$INFO['go_entry_id']."\n");
		}
	}
///$DATA['EXTDB'][]=array('DBID'=>-1,'db_name'=>$DB,'DB_VALUE'=>$DBKEY,'DB_STATUS'=>'TO_INS');
addLog("EXTDB");
	foreach ($ENTRY['EXTDB'] as &$INFO)
	{
		
		if ($INFO['DB_STATUS']=='FROM_DB')
		{
			
			if (!runQueryNoRes("DELETE FROM ip_ext_db WHERE ip_ext_db_id=".$INFO['DBID'])) failProcess($JOB_ID."D06",'Unable to delete IP_EXT_DB_ID='.$INFO['DBID']);
		}
		else if ($INFO['DB_STATUS']=='TO_INS')
		{
			$DBIDS['ip_ext_db']++;
			#TODO below db_name, db_value unrecognized?
			fputs($FILES['ip_ext_db'],$DBIDS['ip_ext_db']."\t".$ENTRY['INFO']['ip_entry_id']."\t".$INFO['db_name']."\t".$INFO['db_value']."\n");
		}
	}
	addLog("END DB");

}







function processEntry(&$ini_data)
{
global $GO_ENTRY;

	$IPR_ID=$ini_data['@attributes']['id'];
	echo $IPR_ID."\n";
	/// Getting data from database
	$DATA=loadData($IPR_ID);
	
	//echo $DATA['INFO']['DB_STATUS']."\n";

	/// Comparing primary descriptors
	$SH=$ini_data['@attributes']['short_name'];
	if ($SH!=$DATA['INFO']['short_name'])	
	{
		$DATA['INFO']['short_name']=$SH;	
		if ($DATA['INFO']['DB_STATUS']!='TO_INS')$DATA['INFO']['DB_STATUS']='TO_UPD';
		echo "SNAME\n";
	}


	$SH=$ini_data['@attributes']['protein_count'];
	if ($SH!=$DATA['INFO']['protein_count'])
	{	
		$DATA['INFO']['protein_count']=$SH;
		if ($DATA['INFO']['DB_STATUS']!='TO_INS')$DATA['INFO']['DB_STATUS']='TO_UPD';
		echo "PC\n";
	}


	$SH=$ini_data['@attributes']['type'];
	if ($SH!=$DATA['INFO']['entry_type'])	
	{
		$DATA['INFO']['entry_type']=$SH;	
		if ($DATA['INFO']['DB_STATUS']!='TO_INS')$DATA['INFO']['DB_STATUS']='TO_UPD';
		echo "TYPE\n";
	}



	$SH=$ini_data['name'];
	if ($SH!=$DATA['INFO']['name'])			
	{
		$DATA['INFO']['name']=$SH;			
		if ($DATA['INFO']['DB_STATUS']!='TO_INS')$DATA['INFO']['DB_STATUS']='TO_UPD';
		echo "NAME\n";
	}
	
	// Looking at the abstract
	$ABSTRACT='';
	if (isset($ini_data['abstract']['p']))
	{
		if (is_array($ini_data['abstract']['p']))$ABSTRACT=implode("\n",$ini_data['abstract']['p']);
		else $ABSTRACT=$ini_data['abstract']['p'];
	}
	else if (isset($ini_data['abstract']['P']))
	{
		if (is_array($ini_data['abstract']['P']))$ABSTRACT=implode("\n",$ini_data['abstract']['P']);
		else $ABSTRACT=$ini_data['abstract']['P'];
	}
	
	
	if (isset($ini_data['pub_list']))
	{
		// No attributes means multiple records
		if (!isset($ini_data['pub_list']['publication']['@attributes']))
		{
			/// looking over each record
			foreach ($ini_data['pub_list']['publication'] as $PI)
			{
				if (!isset($PI['@attributes']['id']))print_R($ini_data);
				$IPR_PM_ID=$PI['@attributes']['id'];
				
				$INI_PMID='';
				if (!isset($PI['db_xref']['@attributes']))continue;
				#Ignore everything that is not pubmed
				if ($PI['db_xref']['@attributes']['db']!='PUBMED')continue;
				$INI_PMID=$PI['db_xref']['@attributes']['dbkey'];
				$ABSTRACT=str_replace('<cite idref="'.$IPR_PM_ID.'"/>'."\n",'Pubmed:'.$INI_PMID,$ABSTRACT);

				$FOUND=false;
				foreach ($DATA['PUBLI'] as $PMID=>&$PM_INFO)
				{
					if ($PMID!=$INI_PMID)continue;
					$FOUND=true;
					$PM_INFO['DB_STATUS']='VALID';
				}
				if ($FOUND)continue;
				$DATA['PUBLI'][$INI_PMID]=array('dbid'=>-1,'DB_STATUS'=>'TO_INS');
			}
		}else
		{
			/// Only one record, so getting the data from the attributes
			$PI=$ini_data['pub_list']['publication'];
			
			
			$IPR_PM_ID=$PI['@attributes']['id'];
				
			$INI_PMID='';
			#TODO Pubmed below?
			if (isset($PI['db_xref']['@attributes']) && $PI['db_xref']['@attributes']['db']!='PUBMED')
			{
				$INI_PMID=$PI['db_xref']['@attributes']['dbkey'];
				$ABSTRACT=str_replace('<cite idref="'.$IPR_PM_ID.'"/>'."\n",'Pubmed:'.$INI_PMID,$ABSTRACT);

				$FOUND=false;
				foreach ($DATA['PUBLI'] as $PMID=>&$PM_INFO)
				{
					if ($PMID!=$INI_PMID)continue;
					$FOUND=true;
					$PM_INFO['DB_STATUS']='VALID';
				}
				if (!$FOUND)			$DATA['PUBLI'][$INI_PMID]=array('dbid'=>-1,'DB_STATUS'=>'TO_INS');
			}
			
		}
	}
	
//echo $DATA['INFO']['DB_STATUS']."\n";

	/// Processing the abstract
	$ABSTRACT=trim(preg_replace('!\s+!', ' ',str_replace("]",'}',str_replace("[\n",'{',$ABSTRACT))));
	if ($ABSTRACT!=$DATA['INFO']['abstract']&& $ABSTRACT."\n"!=$DATA['INFO']['abstract'])
	{
	//	echo "######\n|".$ABSTRACT."|\n|".$DATA['INFO']['abstract']."|\n\n#####\n\n";
		$DATA['INFO']['abstract']=$ABSTRACT;
		if ($DATA['INFO']['DB_STATUS']!='TO_INS')$DATA['INFO']['DB_STATUS']='TO_UPD';echo "ABST\n";
		$DATA['INFO']['ABST_STATUS']='TO_UPD';
	}
	/// Looking at external identifiers
	foreach ($ini_data['member_list']['db_xref'] as $L)
	{
		$DB='';$DBKEY='';$NAME='';
		if (!isset($L['@attributes']))
		{
			$DB=$L['db'];
			$DBKEY=$L['dbkey'];
			$NAME=$L['name'];
		}
		else 
		{
			$DB=$L['@attributes']['db'];
			$DBKEY=$L['@attributes']['dbkey'];
			$NAME=$L['@attributes']['name'];
		}
		
		$FOUND=false;
		foreach ($DATA['IP_SIGN'] as &$SIGN)
		{///
			if ($SIGN['ip_sign_dbname']!=$DB ||$SIGN['ip_sign_dbkey']!=$DBKEY ||$SIGN['ip_sign_name']!=$NAME)continue;
			$SIGN['DB_STATUS']='VALID';
			$FOUND=true;
		}
		if ($FOUND)continue;
		$DATA['IP_SIGN'][]=array('DBID'=>-1,'ip_sign_dbname'=>$DB,'ip_sign_dbkey'=>$DBKEY,'ip_sign_name'=>$NAME,'DB_STATUS'=>'TO_INS');
	}#TODO DBID above to lc?
	

	if (isset($ini_data['external_doc_list']))
	foreach ($ini_data['external_doc_list']['db_xref'] as $L)
	{
		$DB='';$DBKEY='';
		if (!isset($L['@attributes']))
		{
			$DB=$L['db'];
			$DBKEY=$L['dbkey'];
			
		}
		else 
		{
			$DB=$L['@attributes']['db'];
			$DBKEY=$L['@attributes']['dbkey'];
			
		}
		$FOUND=false;
		foreach ($DATA['EXTDB'] as &$SIGN)
		{///
			//echo $SIGN['db_name'].'::'.$DB."\t".$SIGN['db_value']."\t".$DBKEY."\n";
			if ($SIGN['db_name']!=$DB ||$SIGN['db_value']!=$DBKEY )continue;
			
			$SIGN['DB_STATUS']='VALID';
			$FOUND=true;
		}
		if ($FOUND)continue;
		$DATA['EXTDB'][]=array('DBID'=>-1,'db_name'=>$DB,'db_value'=>$DBKEY,'DB_STATUS'=>'TO_INS');
	}#TODO above line?


	if (isset($ini_data['class_list']))
	foreach ($ini_data['class_list']['classification'] as $K=>$L)
	{
		if ($K=='category'||$K=='description')continue;
		
		$DB='';
		if (!isset($L['@attributes']))
		{
			if (!isset($L['id'])){echo "\n";print_r($ini_data['class_list']);echo "FAILEd\n";exit;}
			$DB=$L['id'];
		}
		else 	$DB=$L['@attributes']['id'];
			
			
		
		$FOUND=false;
		foreach ($DATA['GO'] as &$SIGN)
		{///
			if ($SIGN['AC']!=$DB )continue;
			$SIGN['DB_STATUS']='VALID';
			$FOUND=true;
		}
		if ($FOUND)continue;
		$DATA['GO'][]=array('DBID'=>-1,'AC'=>$DB,'go_entry_id'=>$GO_ENTRY[$DB],'DB_STATUS'=>'TO_INS');
	} #TODO above? 
//	print_r($ini_data['class_list']);
//echo $DATA['INFO']['DB_STATUS']."\tEND\n";	
return $DATA;
}

	
?>