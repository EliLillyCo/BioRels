<?php
ini_set('memory_limit','2000M');
$JOB_NAME='pmj_xray';/// Hijack pmj_xray
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];


/// File verifications:
addLog("Load CHECK");
$CK_INFO=$GLB_TREE[getJobIDByName('ck_xray_rel')];
$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
$W_DIR.='/'.$CK_INFO['DIR'].'/';  		 if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
$E_DIR=$W_DIR.'/ENTRIES';	 if (!is_dir($E_DIR) && !mkdir($E_DIR)) 				failProcess($JOB_ID."003",'Unable to find and create '.$W_DIR);

$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create new process dir '.$W_DIR);
											  if (!chdir($W_DIR)) 						failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

addLog("Check File");
$res=runQuery('SELECT full_common_name FROM xr_entry ORDER BY FULL_COMMON_NAME ASC');if ($res===false)failProcess($JOB_ID."006",'Unable to get Common Names');
	$LIST_CURRENT=array();
	foreach ($res as $line)$LIST_CURRENT[$line['full_common_name']]=true;



	$ORDER=array('GET_STRUCTURE','PDB_PREP','PDB_SEP','VOLSITE','BLASTP');//,'CLUSTERING','INTERS');
	$CPLT_STAT=array();
	foreach ($ORDER as $T) $CPLT_STAT[$T]=array();

function print_mem()
{

   $mem_usage = memory_get_usage();
   

   $mem_peak = memory_get_peak_usage();

   echo 'The script is now using: <strong>' . round($mem_usage / 1024) . 'KB</strong> of memory.'."\n";
   echo 'Peak usage: <strong>' . round($mem_peak / 1024) . 'KB</strong> of memory'."\n\n";;
}
print_mem();

$CK_INFO=$GLB_TREE[getJobIDByName('dl_xray')];
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 						failProcess($JOB_ID."007",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR))					 	failProcess($JOB_ID."008",'Unable to find and create '.$U_DIR);

	
	$N_ENTRY_DONE=0;$UNIPROT_STAT=array();$PROT_PAIR=array();$KILLED=0;
	foreach ($LIST_CURRENT as $PDB=>$STATUS)
	{
		
		++$N_ENTRY_DONE;

		if ($N_ENTRY_DONE%1000==0) {addLog("Processing ".$N_ENTRY_DONE);
			print_r($CPLT_STAT);
print_mem();}
	//echo $PDB."\n";
	$PD_DIR=$U_DIR.'ENTRIES/'.substr($PDB,1,2).'/'.$PDB.'/';
	
			if (!is_file($PD_DIR.'_data'))continue;
		$STATUS=json_decode(file_get_contents($PD_DIR.'_data'),true);
	
		///STATISTICS:
		
		if (isset($STATUS['PROCESS']['KILL']) && $STATUS['PROCESS']['KILL']=="YES"){fputs($fpS,"\tKILLED\n");$KILLED++;continue;}
		$term=false;
		foreach ($ORDER as $TYPE)
		{
			if (!isset($STATUS['PROCESS'][$TYPE]))continue;
			$STAT=$STATUS['PROCESS'][$TYPE];
			//if ($STAT ==''){echo $TYPE;print_r($STATUS['PROCESS']);}
			
			if (!isset($CPLT_STAT[$TYPE][$STAT]))$CPLT_STAT[$TYPE][$STAT]=0;
			++$CPLT_STAT[$TYPE][$STAT];
			if ($STAT=='TERM')break;
		}
		


		if ($STATUS['PROCESS']['CLUSTERING']!="OK" ||$STATUS['PROCESS']['BLASTP']!="OK")continue;
		
		$CH_MAP=array();
		foreach ($STATUS['BLASTP'] as $CHAIN=>$CHAIN_INFO)
		{
			if ($CHAIN_INFO['STATUS']=='OK'&& count($CHAIN_INFO['RESULTS'])==0){continue;}
			if (!isset($CHAIN_INFO['PRIMARY'])){echo $PDB."\t".$CHAIN."\tNO PRIM\n";continue;}
			if (!isset($CHAIN_INFO['PRIMARY'][0])){echo $PDB."\t".$CHAIN."\tNO 0\n";continue;}
			$CH_MAP[$CHAIN]=$CHAIN_INFO['PRIMARY'][0]['UNIPROT'];
			fputs($fpC,$PDB."\t".$CHAIN.
				"\t".$CHAIN_INFO['PRIMARY'][0]['UNIPROT']
				."\t".$CHAIN_INFO['PRIMARY'][0]['IDEN']
				."\t".$CHAIN_INFO['PRIMARY'][0]['SIM']
				."\t".$CHAIN_INFO['PRIMARY'][0]['IDEN_COM']
				."\t".$CHAIN_INFO['PRIMARY'][0]['SIM_COM']."\n");


		}


		foreach ($STATUS['CLUSTERING'] as $CLUSTERS)
		foreach ($CLUSTERS as $SITE=>$CAV_LIST)
		foreach ($CAV_LIST as $CAVNAME=>$CAV_INFO)
		{
			$CHAINS=array_keys($CAV_INFO['LIST_RES']);
			$DRUGG=100;
			foreach ($STATUS['CAVITIES'] as $JOB=>$CAVS)
			{
				foreach ($CAVS as $CN=>$CN_I){
					if ($CN!=$CAVNAME)continue;
					foreach ($CHAINS as $C)if (isset($CH_MAP[$C]))$UNIPROT_STAT[$CH_MAP[$C]][]=$CN_I['DRUGG'];
					break;
				}
				if ($DRUGG!=100)break;
			}
		}

		foreach ($STATUS['FILES']['STRUCTURE'] as $CHAIN_TYPE=>$INFO)
	{
		if ($INFO['TYPE']=='LIGAND')continue;
		if ($INFO['TYPE']=='SINGLE_CHAIN')
		{
			if (!isset($CH_MAP[$CHAIN_TYPE]))continue;
			$UNP=$CH_MAP[$CHAIN_TYPE];
			$PROT_PAIR[$UNP][]=array($PDB,$CHAIN_TYPE);
			if (!isset($LIST_UNIP["'".$UNP."'"]))$LIST_UNIP["'".$UNP."'"]=1;
			else $LIST_UNIP["'".$UNP."'"]++;
		}
		else if ($INFO['TYPE']=='RECEPTOR')
		{
			asort($CH_MAP);
			$PROT_PAIR[implode($CH_MAP,'|')][]=array($PDB,$CHAIN_TYPE);
		}
		else if ($INFO['TYPE']=='MULTIMER')
		{
			$tab=explode("_",$CHAIN_TYPE);
			$t=array();

			foreach ($tab as $CH)	
			{
					if (isset($CH_MAP[$CH]))$t[]=$CH_MAP[$CH];
			}
			
			if (count($t)!=count($tab))continue;
			sort($t);

			$PROT_PAIR[implode($t,'|')][]=array($PDB,$CHAIN_TYPE);
			
		}
	}

	


	}

	fclose($fp);
	fclose($fpC);

addLog("Finalizing statistics");
$PREV_V=0;
	fputs($fpS,  "STAT\tKILLED\t".$KILLED."\n");
		foreach ($ORDER as $TYPE)
		{
			fputs($fpS,  "STAT\t".$N_ENTRY_DONE."\t".$TYPE."\tPREV:".$PREV_V." (".round($PREV_V/($N_ENTRY_DONE-$KILLED)*100,2)."%)");;
			$NEW_PREV=$PREV_V;
			foreach ($CPLT_STAT[$TYPE] as $T=>$L)
			{
				if ($T=='')
				{
					$T='TODO';
					$L-=$PREV_V;
				}
				if ($T!='OK')$NEW_PREV+=$L;
				fputs($fpS,  "\t".$T.":".$L." (".round($L/($N_ENTRY_DONE-$KILLED)*100,2)."%)");
			}
			$PREV_V=$NEW_PREV;
			fputs($fpS,  "\n");
		}
	fclose($fpS);


addLog("Export Uniprot");

foreach ($PROT_PAIR as $TAG=>$LIST)
{
	foreach ($LIST as $ENTRY)
	fputs($fpU,$TAG."\t".$ENTRY[0]."\t".$ENTRY[1]."\n");
}
fclose($fpU);

exec('cat '.$XRAY_DIR.'/T',$L);
$LIST_UNIP=array();
foreach ($L as $T)
{
	$tab=array_values(array_filter(explode(" ",$T)));
	$LIST_UNIP["'".$tab[1]."'"]=$tab[0];
}


addLog("Extract Gene Ids");
	$CHUNKS=array_chunk(array_keys($LIST_UNIP),999);
	$GENE_INFO=array();
	foreach ($CHUNKS as $CHUNK)
	{
		$query='SELECT GENE_ID, UN_IDENTIFIER FROM MV_TARGETS WHERE UN_IDENTIFIER IN ('.implode($CHUNK,',').')';

		$RES=runDBQuery($query,'BIO',' GENE_ID,UN_IDENTIFIER N,S ');
		if ($RES===false)failProcess($JOB_ID."009",'Unable to run query '); 
unset($RES[0]);
		foreach ($RES as $LIGNE){
			$L=explode("\t",$LIGNE);
			if (!isset($GENE_INFO[$L[0]]))$GENE_INFO[$L[0]]=$LIST_UNIP["'".$L[1]."'"];

			else $GENE_INFO[$L[0]]+=$LIST_UNIP["'".$L[1]."'"];
		}
		
	}

	$fp=fopen($XRAY_DIR.'/'.$GLB_VAR['CHECK']['XRAY_GENES'],'w');
	fputs($fp,"COUNTS\tGENE_ID\n");
	foreach ($GENE_INFO as $GENE_ID=>$COUNTS){
		if ($GENE_ID!='')	fputs($fp,$COUNTS."\t".$GENE_ID."\n");
	}
	fclose($fp);
successProcess();
?>

