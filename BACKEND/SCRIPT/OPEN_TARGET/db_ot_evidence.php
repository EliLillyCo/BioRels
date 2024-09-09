<?php

/**
 SCRIPT NAME: db_ot_evidence
 PURPOSE:     Process gene to disease associations
 
*/

/// Job name - Do not change
$JOB_NAME='db_ot_evidence';


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
	
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_ot')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	
	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];


addLog("Get MAx DBIDS")	;
	//Those are the tables we are going to insert into
	/// So we get the max PK for faster insert
	$DBIDS=array('pmid_disease_gene'=>-1, 'pmid_disease_gene_txt'=>-1);
	foreach (array_keys($DBIDS) as $P)
	{
		$res=runQuery("SELECT MAX(".$P."_id) CO FROM ".$P);
		if ($res===false)																	failProcess($JOB_ID."005",'Unable to get max id from '.$P);
		if (count($res)==0)	$DBIDS[$P]=0;
		else 				$DBIDS[$P]=$res[0]['co'];

	}

	$ENSMAP=getEnsemblMap();



	
	$fp=fopen('europepmc.json','r'); if (!$fp)									failProcess($JOB_ID."006",'Unable to open europepmc_evidence.json ');
fseek($fp,233387925);
	$STACK=array();
	$STATS=array('PMID_NOT_FOUND'=>0,'DISEASE_NOT_FOUND'=>0,'GENE_NOT_FOUND'=>0);
	$N=0;


	while(!feof($fp))
	{
		
		$line=stream_get_line($fp,10000000,"\n");
		if ($line=='')continue;
		
		++$N;
		
		$TMP=json_decode($line,true);
		
		if ($TMP===false)															failProcess($JOB_ID."007",'Unable to interpret json string');
		/// To speed up the process, we do it by batch
		
		foreach ($TMP['textMiningSentences'] as &$KP)
		{
			/// Removing unnecessary fields
			unset($KP['dEnd'],$KP['dStart'],$KP['tStart'],$KP['tEnd']);
			
			if ($KP['section']!='other')continue;
			
			
			/// There has been some instances where words within the same sentence are repeated
			/// We remove those repetitions
			removeRepetition($KP['text']);
		}

		//print_R($TMP['textMiningSentences']);

		/// Also sometimes there will be duplicated sentences
		$DONE=array();
		foreach ($TMP['textMiningSentences'] as &$TMP_K)
		{
			$FOUND=false;
			foreach ($DONE as $TMP_D)
			{
				if ($TMP_D['section']==$TMP_K['section']
					||$TMP_D['text']==$TMP_K['text'])
					{
						$FOUND=true;break;
					}
			}
			if ($FOUND)continue;
			
			$DONE[]=$TMP_K;
		}
		$TMP['textMiningSentences']=$DONE;
		$STACK[]=$TMP;
		if (count($STACK)<1000)continue;

		echo "FILE POSITION:\n".ftell($fp)."\n\n\n";
		processStack($STACK);

		$STACK=array();
	}

	if ($STACK!=array())processStack($STACK);
print_R($STATS);

successProcess();








function getEnsemblMap()
{
	global $TG_DIR;
	global $GLB_VAR;
	$ENSMAP=array();
	if (checkFileExist($TG_DIR.'/'.$GLB_VAR['PRD_DIR'].'/GENE/gene2ensembl'))
	{
		$fp=fopen($TG_DIR.'/'.$GLB_VAR['PRD_DIR'].'/GENE/gene2ensembl','r');
		if (!$fp)																		failProcess($JOB_ID."A01",'Unable to open gene2ensembl');
		$MAP=array();
		while(!feof($fp))
		{
			$line=stream_get_line($fp,10000,"\n");
			if ($line=='')continue;
			$tab=explode("\t",$line);
			
			/// Only human genes
			if ($tab[0]!=9606)continue;

			$MAP[$tab[1]][]=$tab[2];
		}
		fclose($fp);
	
		if ($MAP!=array())
		{
			$res=runQuery("SELECT gn_entry_id , gene_id 
						FROM gn_entry 
						where gene_id IN (".implode(',',array_keys($MAP)).')');
			if ($res===false)																failProcess($JOB_ID."A02",'Unable to search for genes ');
			foreach ($res as $line)
			{
				foreach ($MAP[$line['gene_id']] as $ENS)$ENSMAP[$ENS]=$line['gn_entry_id'];
			}
		}
	}

	$res=runQuery("SELECT gn_entry_id, gene_seq 
					FROM gene_seq 
					where gene_seq_name LIKE 'ENSG%' 
					ANd gn_entry_id IS NOT NULL");
	if ($res===false)																	failProcess($JOB_ID."A03",'Unable to search for genes ');
	foreach ($res as $line)
	{
		$ENSMAP[$line['gene_seq']]=$line['gn_entry_id'];
	}

	return $ENSMAP;
}



function removeRepetition(&$STR)
{
	$T=explode(' ',$STR);
	$NSTR=array();
	$co=count($T);
	
	for($i=0;$i<$co;++$i)
	{
		if ($i+1<$co && $T[$i]==$T[$i+1]){$i++;}
		$NSTR[]=$T[$i];
	}
	$NT=implode(' ',$NSTR);
	
	if ($NT!=trim($STR))
	{
		$STR=$NT;
	}
}


function processStack(&$STACK)
{
	global $DBIDS;
	global $STATS;
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	
	/// Boolean that states whether we have new records or not. 
	/// This i sto avoid running psql for nothing
	$NEW_ENTRY=false;
	$NEW_TEXT=false;
	echo "START\t";
	$LIST_GENE=array();
	$LIST_PUBLI=array();
	$LIST_DISEASE=array();
	foreach ($STACK as &$RAW_DATA)
	{
		/// We ensure each record have the necessary columns
		if (!isset($RAW_DATA['targetId']))					{$STATS['NO_TARGET_ID']++;continue;}
		if (!isset($RAW_DATA['diseaseFromSourceMappedId']))	{$STATS['NO_DISEASE_ID']++;continue;}
		if (!isset($RAW_DATA['literature'])
		 ||!is_numeric($RAW_DATA['literature'][0]))			{$STATS['NO_PUBLICATIONS']++;continue;}
		if (count($RAW_DATA['literature'])>1) 				{$STATS['MULTIPLE_PUBLICATIONS']++;continue;}
		/// Then we list the genes, publications, diseases
		
		$LIST_GENE["'".$RAW_DATA['targetId']."'"]=-1;
		$LIST_PUBLI[$RAW_DATA['literature'][0]]=-1;
		$LIST_DISEASE["'".strtolower($RAW_DATA['diseaseFromSourceMappedId'])."'"]=-1;
	}

	/// Search the publications
	getPublications($LIST_PUBLI);
	
	getGenes($LIST_GENE);
	
	getDiseases($LIST_DISEASE);

	echo count($LIST_GENE)."\t".count($LIST_PUBLI)."\t".count($LIST_DISEASE)."\t";
	
	$MAP_PUBLI=array();
	$MAP_DISEASE=array();
	$fpG=fopen('PMID_DISEASE_GENE.csv','w');if (!$fpG)													failProcess($JOB_ID."B01",'Unable to open PMID_DISEASE_GENE');
	$fpT=fopen('PMID_DISEASE_GENE_TXT.csv','w');if (!$fpG)												failProcess($JOB_ID."B02",'Unable to open PMID_DISEASE_GENE_TXT');

	$MAP_ID=array();
	$MAP=array();

	/// Then for each record in the stack
	foreach ($STACK as $ID_STACK=> &$RAW_DATA)
	{
		if ($ID_STACK%500==0)echo "\t".$ID_STACK."\n";
		/// We want to ensure we have the target, the disease and the publication
		if (!isset($RAW_DATA['targetId']))
		{
			$RAW_DATA['VALID']=false;
			continue;
		}
		if (!isset($RAW_DATA['diseaseFromSourceMappedId']))
		{
			$RAW_DATA['VALID']=false;
			continue;
		}
		if (!isset($RAW_DATA['literature'])||
			!is_numeric($RAW_DATA['literature'][0]))
		{
			$RAW_DATA['VALID']=false;
			continue;
		}
		
		/// Then we ensure that we actually have a gene in the database
		$gn_entry_id=$LIST_GENE["'".$RAW_DATA['targetId']."'"];
		
		if ($gn_entry_id==-1||$gn_entry_id=='')
		{
			echo "MISS_GENE\t".$RAW_DATA['targetId']."\t".$RAW_DATA['targetFromSourceId'] ."\n";
			
			$STATS['GENE_NOT_FOUND']++;
			$RAW_DATA['VALID']=false;
			continue;
			
		}
		/// Then we ensure that we actually have a publication in the database
		
		if (!isset($LIST_PUBLI[$RAW_DATA['literature'][0]])
		   ||$LIST_PUBLI[$RAW_DATA['literature'][0]]==-1)
		{
			$STATS['PMID_NOT_FOUND']++;
			echo "MISS PMID\t".$RAW_DATA['literature'][0]."\n";
			$RAW_DATA['VALID']=false;
			continue;
		}
		
		$pmid_entry_id=$LIST_PUBLI[$RAW_DATA['literature'][0]];

		/// Then we ensure that we actually have a disease in the database
		$disease_entry_id=$LIST_DISEASE["'".strtolower($RAW_DATA['diseaseFromSourceMappedId'])."'"];
		
		if ($disease_entry_id==-1)
		{
			echo "MISS_DISEASE\t".$RAW_DATA['diseaseFromSourceMappedId']."\t".
			(isset($RAW_DATA['diseaseFromSource'])?$RAW_DATA['diseaseFromSource']:'NO SOURCE')."\t".
			(isset($RAW_DATA['diseaseLabel'])?$RAW_DATA['diseaseLabel']:' NO LABEL')."\t".
			$RAW_DATA['diseaseId'] ."\n";
			$STATS['DISEASE_NOT_FOUND']++;
			$RAW_DATA['VALID']=false;
			continue;
		}




		/// Now we assign the ids
		$RAW_DATA['pmid_entry_id']=$pmid_entry_id;
		$RAW_DATA['disease_entry_id']=$disease_entry_id;
		$RAW_DATA['gn_entry_id']=$gn_entry_id;
		$RAW_DATA['VALID']=true;
		$MAP_DISEASE[$pmid_entry_id][$disease_entry_id]=false;
		$MAP_PUBLI[$pmid_entry_id][$gn_entry_id]=false;
		$LIST_TXT=array();$PMID_DISEASE_GENE_ID=-1;
		/// Here we create as key a triplet as the parameters for the query and as value the position in the stack
		$MAP_ID["(".$pmid_entry_id.','.$gn_entry_id.','.$disease_entry_id.')'][]=$ID_STACK;
	}

	addLog("Query PMID_DISEASE_GENE");
	$UPD_SCORES=array();
	///So we search those triplets
	if ($MAP_ID!=array())
	{
		$res=runQuery("SELECT pmid_Disease_gene_id,ot_score,pmid_entry_id,gn_entry_id,disease_entry_id 
		FROM pmid_disease_gene 
		WHERE (pmid_entry_id,gn_entry_id,disease_entry_id) IN (".implode(',',array_keys($MAP_ID)).')');
		if ($res===false)																			failProcess($JOB_ID."B03",'Unable to search PMID_DISEASE_GENE');
		foreach ($res as $line)
		{
			$STR="(".$line['pmid_entry_id'].','.$line['gn_entry_id'].",".$line['disease_entry_id'].')';
			/// And use those mapping to the stack
			foreach ($MAP_ID[$STR] as $ID_STACK)
			{
				$RAW_DATA=&$STACK[$ID_STACK];
				/// We assign to each record the database id
				$RAW_DATA['PMID_DISEASE_GENE_ID']=$line['pmid_disease_gene_id'];
				/// And see if the score is the same  or not
				if ($line['ot_score']!=$RAW_DATA['score'])
				{
					$UPD_SCORES[$RAW_DATA['score']][]=$line['pmid_disease_gene_id'];
					
					$STATS['UPDATED_SCORE']++;
				}
				$STATS['PDG_FOUND']++;
				$MAP[$line['pmid_disease_gene_id']][]=$ID_STACK;
			}
		}
	}


	if ($UPD_SCORES!=array())
	{
		foreach ($UPD_SCORES as $S=>&$L)
		if (!runQueryNoRes('UPDATE pmid_disease_gene 
							SET ot_score='.$S.' 
							WHERE pmid_disease_gene_id IN ('.implode(',',$L).')'))				failProcess($JOB_ID."B04",'Unable to update PMID_DISEASE_GENE');
	}


		$NEW_TRIPLET=array();
		foreach ($STACK as $ID_STACK=> &$RAW_DATA)
		{
			///Those that don't have DB ID need to be inserted
			if (isset($RAW_DATA['PMID_DISEASE_GENE_ID']))continue;
			if (!$RAW_DATA['VALID'])continue;
		
			$STR=$RAW_DATA['pmid_entry_id']."\t".$RAW_DATA['gn_entry_id']."\t".$RAW_DATA['disease_entry_id'];
			/// There can be some duplications among the batch, so we ensure that doesn't happen
			if (!isset($NEW_TRIPLET[$STR]))
			{
				++$DBIDS['pmid_disease_gene'];
				$PMID_DISEASE_GENE_ID=$DBIDS['pmid_disease_gene'];
				$RAW_DATA['PMID_DISEASE_GENE_ID']=$PMID_DISEASE_GENE_ID;
				$NEW_TRIPLET[$STR]=array($PMID_DISEASE_GENE_ID,$RAW_DATA['score']);
				$STATS['PMID_DISEASE_GENE_INSERT']++;
				$NEW_ENTRY=true;
			}
			else {
				$RAW_DATA['PMID_DISEASE_GENE_ID']=$NEW_TRIPLET[$STR][0];
				$NEW_TRIPLET[$STR][1]=max($NEW_TRIPLET[$STR][1],$RAW_DATA['score']);
			}
		//	print_r($RAW_DATA);
		}
		/// Push to file
		foreach($NEW_TRIPLET as $STR=>$D)fputs($fpG,$D[0]."\t".$STR."\t".$D[1]."\n");

		addLog("Query PMID_DISEASE_GENE_TXT");
		if ($MAP!=array())
		{
			$res=runQuery('SELECT  pmid_disease_gene_txt_id,
			pmid_disease_gene_id,
			section,
			text_content 
			FROM pmid_disease_gene_txt 
			WHERE pmid_disease_gene_id IN ('.implode(',',array_keys($MAP)).')');
			if ($res===false)																			failProcess($JOB_ID."B05",'Unable to search PMID_DISEASE_GENE_TXT');
			
			foreach ($res as $line)
			{

				$line['DB_STATUS']='FROM_DB';
				foreach ($MAP[$line['pmid_disease_gene_id']] as $P)
				$STACK[$P]['INI_TEXT'][]=$line;
			}
		}
		
addLog("Comparison");
		$TO_DEL=array();
		
		
	foreach ($STACK as $ID_STACK=> &$RAW_DATA){
		if (!$RAW_DATA['VALID'])continue;
		foreach ($RAW_DATA['textMiningSentences'] as $TXT)
		{
			$FOUND=false;
			$TXT['text']=htmlspecialchars($TXT['text']);
			if ($TXT['text']=='')continue;
			
			if (isset($RAW_DATA['INI_TEXT']))
			foreach ($RAW_DATA['INI_TEXT'] as &$T)
			{
				if ($T['section']!=substr($TXT['section'],0,1))continue;
				if ($T['text_content']!=$TXT['text'])continue;
				$T['DB_STATUS']='VALID';
				$STATS['PDGT_VALID']++;
				$FOUND=true;break;
			}
			if ($FOUND)continue;
			
			++$DBIDS['pmid_disease_gene_txt'];
			$NEW_TEXT=true;
			fputs($fpT,$DBIDS['pmid_disease_gene_txt']."\t".
						$RAW_DATA['PMID_DISEASE_GENE_ID']."\t".
						substr($TXT['section'],0,1)."\t".
						'"'.str_replace('"','""',$TXT['text']).'"'."\n");
			$STATS['PDGT_NEW']++;
		}
		if (isset($RAW_DATA['INI_TEXT']))
		{
			foreach ($RAW_DATA['INI_TEXT'] as &$T)
			{
				if ($T['DB_STATUS']!='FROM_DB')continue;
				$TO_DEL[]=$T['pmid_disease_gene_txt_id'];
			}
		}
		
	}
	
		// if($TO_DEL!=array()){print_r($STACK);
		// 	print_r($RAW_DATA);print_r($TO_DEL);exit;}
	
	if ($TO_DEL!=array())
	{
		addLog("DELETE ".count($TO_DEL));
		if (!runQueryNoRes("DELETE FROM PMID_DISEASE_GENE_TXT
			 WHERE PMID_DISEASE_GENE_TXT_ID IN (".implode(',',$TO_DEL).')'))				failProcess($JOB_ID."B06",'Unable to delete PMID_DISEASE_GENE_TXT');
			$STATS['SUCCESS_PMID_DISEASE_GENE_TEXT_DELETE']+=count($TO_DEL);
	}
	fclose($fpG);
	fclose($fpT);

	if ($NEW_ENTRY)
	{
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_disease_gene(pmid_disease_gene_id, pmid_entry_id,gn_entry_id,disease_entry_id,ot_score) FROM \'PMID_DISEASE_GENE.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
				echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
				system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
				if ($return_code !=0 )failProcess($JOB_ID."B07",'Unable to insert pmid_disease_gene'); 
	}
	if ($NEW_TEXT)
	{
				$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_disease_gene_txt( pmid_disease_gene_txt_id,pmid_disease_gene_id,section,text_content) FROM \'PMID_DISEASE_GENE_TXT.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
				echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
				system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
				if ($return_code !=0 )failProcess($JOB_ID."B08",'Unable to insert pmid_disease_gene_txt'); 
	}
	print_r($STATS);
				

	if (count($MAP_PUBLI)>0)
	{
		echo "SEARCH PUBLICATIONS\n";
		$query='SELECT pmid_entry_id,gn_entry_id FROM pmid_gene_map WHERE (pmid_entry_id,gn_entry_id) IN (';
		foreach ($MAP_PUBLI as $pmid_entry_id=>$LG)
		foreach ($LG as $GN=>$T)$query.='('.$pmid_entry_id.','.$GN.'),';
		$query=substr($query,0,-1).')';
		$res=runQuery($query);
		if ($res===false)																			failProcess($JOB_ID."B09",'Unable to search in pmid_gene_map');
		foreach ($res as $line){
			$MAP_PUBLI[$line['pmid_entry_id']][$line['gn_entry_id']]=true;
		}
		
		$HAS_NEW_PMID_GENE_MAP=false;
		$fpPGM=fopen('pmid_gene_map.csv','w');if (!$fpPGM)											failProcess($JOB_ID."B10",'Unable to open  pmid_gene_map');
		foreach ($MAP_PUBLI as $pmid_entry_id=>$LG)
		{
			$PMD_L=array();
		foreach ($LG as $gn_entry_id=>$T)
		{
			if ($T)continue;
			if (isset($PMD_L[$gn_entry_id]))continue;
			$HAS_NEW_PMID_GENE_MAP=true;
			fputs($fpPGM,$pmid_entry_id."\t".$gn_entry_id."\n");
			$PMD_L[$gn_entry_id]=true;
			}
		}
		fclose($fpPGM);
		if ($HAS_NEW_PMID_GENE_MAP)
		{
			$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_gene_map(pmid_entry_id,gn_entry_id) FROM \'pmid_gene_map.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
			if ($return_code !=0 )failProcess($JOB_ID."B11",'Unable to insert pmid_gene_map'); 
		}
		
	}


	if (count($MAP_DISEASE)>0)
	{
		echo "SEARCH PUBLICATIONS\n";
		$query='SELECT pmid_entry_id,disease_entry_id 
				FROM pmid_disease_map 
				WHERE (pmid_entry_id,disease_entry_id) IN (';

		foreach ($MAP_DISEASE as $pmid_entry_id=>$LG)
		foreach ($LG as $DS=>$T)$query.='('.$pmid_entry_id.','.$DS.'),';
		$query=substr($query,0,-1).')';
		
		$res=runQuery($query);
		if ($res===false)																			failProcess($JOB_ID."B12",'Unable to search in pmid_gene_map');
		
		foreach ($res as $line)
		{
			$MAP_DISEASE[$line['pmid_entry_id']][$line['disease_entry_id']]=true;
		}
		
		$HAS_NEW_PMID_DS_MAP=false;
		$fpPGM=fopen('pmid_disease_map.csv','w');if (!$fpPGM)										failProcess($JOB_ID."B13",'Unable to open  pmid_gene_map');
		foreach ($MAP_DISEASE as $pmid_entry_id=>$LG)
		foreach ($LG as $disease_entry_id=>$T)
		{
			if ($T)continue;
			$HAS_NEW_PMID_DS_MAP=true;
			fputs($fpPGM,$pmid_entry_id."\t".$disease_entry_id."\n");
		
		}
		fclose($fpPGM);
		
		
		if ($HAS_NEW_PMID_DS_MAP)
		{
			$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_disease_map(pmid_entry_id,disease_entry_id) FROM \'pmid_disease_map.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
			if ($return_code !=0 )failProcess($JOB_ID."B14",'Unable to insert pmid_disease_map'); 
		}
		
	}
	

			
	$LIST_GENE=array_filter($LIST_GENE);
	$LIST_PUBLI=array_filter($LIST_PUBLI);
	$LIST_DISEASE=array_filter($LIST_DISEASE);
	echo count($LIST_GENE)."\t".count($LIST_PUBLI)."\t".count($LIST_DISEASE)."\n";

}


function getPublications(&$LIST_PUBLI)
{

	/// Then we search the papers by chunks of 5000
	$CHUNKS=array_chunk(array_keys($LIST_PUBLI),5000);
	
	
	$N_PUB=0;
	foreach ($CHUNKS as $CHUNK)
	{
		$res=runQuery("SELECT pmid_entry_id, pmid 
					FROM pmid_entry 
					WHERE pmid IN (".implode(',',$CHUNK).')');
		if ($res===false)																		failProcess($JOB_ID."C01",'Unable to search for publications ');
		$N_PUB+=count($res);
		foreach ($res as $line)
		{
			$LIST_PUBLI[$line['pmid']]=$line['pmid_entry_id'];
		}
	}
	echo "PUBLI:".$N_PUB.'/'.count($LIST_PUBLI);
}


function getGenes(&$LIST_GENE)
{
	global $ENSMAP;
	/// Search the genes by chunks of 5000
	/// Genes are ENSEMBL genes that we need to map back to NCBI
	$CHUNKS=array_chunk(array_keys($LIST_GENE),5000);
	echo "GENE_INI:".count($LIST_GENE);
	$N_GENE=0;
	foreach ($CHUNKS as $CHUNK)
	{
		$res=runQuery("SELECT gene_seq_name,gn_entry_id  
						FROM gene_seq GS 
						WHERE gene_seq_name IN (".implode(',',$CHUNK).')');
		if ($res===false)																		failProcess($JOB_ID."D01",'Unable to search for genes ');
		$N_GENE+=count($res);
		foreach ($res as $line)$LIST_GENE["'".$line['gene_seq_name']."'"]=$line['gn_entry_id'];
	}
	echo " ; FOUND:".$N_GENE."\n";
	
	/// In case where we didn't find it, we use the mapping from NCBI to ENSEMBL
	foreach ($LIST_GENE as $GS=>&$GID)
	{
		if ($GID!=-1)continue;
		if (isset($ENSMAP[substr($GS,1,-1)]))$GID=$ENSMAP[substr($GS,1,-1)];
	}
}



function getDiseases(&$LIST_DISEASE)
{
	/// Search the diseases
	$CHUNKS=array_chunk(array_keys($LIST_DISEASE),5000);
	foreach ($CHUNKS as $CHUNK)
	{
		$res=runQuery("SELECT disease_entry_id, disease_tag 
						FROM disease_entry 
						WHERE LOWER(disease_tag) IN (".implode(',',$CHUNK).')');
		if ($res===false)																		failProcess($JOB_ID."E01",'Unable to search for diseases');
		foreach ($res as $line)
		{
			$LIST_DISEASE["'".strtolower($line['disease_tag'])."'"]=$line['disease_entry_id'];
		}
	}
	
	
	$DONE=false;
	$MISSING=array();
	/// If some diseases are missing, we have another way to look them up.
	foreach ($LIST_DISEASE as $K=>$V)
	{
		if ($V!=-1)continue;
		$MISSING[]=explode("_",substr($K,1,-1));
	}
	
	if(count($MISSING)==0)return ;
	
	
	$CHUNKS=array_chunk($MISSING,5000);
	
	foreach ($CHUNKS as &$CHUNK)
	{
		if (count($CHUNK)==0)continue;
		/// So we search the missing ones by database identifiers
		$query="SELECT disease_entry_id, CONCAT(s.source_name,'_',disease_extdb) db_name 
				FROM  disease_extdb E, source S 
				WHERE S.source_id = E.source_id 
				AND (LOWER(source_name),disease_extdb) IN (";
		
		foreach ($CHUNK as &$tab)$query.= "('".strtolower($tab[0])."','".$tab[1]."'),";
		
		$query=substr($query,0,-1).')';
		
		$res=runQuery($query);

		if ($res===false)																		failProcess($JOB_ID."E02",'Unable to search for diseases via extenral identifiers ');
		//print_r($res);exit;
		foreach ($res as $line)
		{
			$LIST_DISEASE["'".strtolower($line['db_name'])."'"]=$line['disease_entry_id'];
		}
	}
	


}
?>
