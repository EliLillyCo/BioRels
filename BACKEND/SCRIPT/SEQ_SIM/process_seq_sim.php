<?php
/**
 * 
 * 	This script is running a blastp followed by a sequence alignment
 * 
 */

error_reporting(E_ALL);
ini_set('memory_limit','5000M');
$TOT_JOB=70;
$JOB_RUNID=$argv[1];
$JOB_TYPE=($argv[2]);echo $JOB_TYPE;if ($JOB_TYPE!='DOM' && $JOB_TYPE!='SEQ')die("Unrecognized job type");

$JOB_NAME='process_seq_sim';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];


addLog("Go to directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_seq_sim')];
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; 		if (!is_dir($U_DIR)) 					failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   					if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$U_DIR);
	$U_DIR.=$CK_INFO['TIME']['DEV_DIR'];				if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$U_DIR);

	$W_DIR=$U_DIR.'/JSON/';								if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create job dir '.$W_DIR);
	if (!chdir($W_DIR)) 																		failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	echo $W_DIR."\n";
	if (!isset($GLB_VAR['TOOL']['BLASTP'])) 													failProcess($JOB_ID."006",'Unable to find BLASTP path in CONFIG_GLOBAL');
	if (!isset($GLB_VAR['TOOL']['SEQALIGN'])) 													failProcess($JOB_ID."007",'Unable to find SEQ_ALIGN path in CONFIG_GLOBAL');
	$BLASTP=$GLB_VAR['TOOL']['BLASTP']; 		if(!is_executable($BLASTP))			failProcess($JOB_ID."008",'Unable to Find blastp '.$BLASTP);
	$SEQ_ALIGN=$GLB_VAR['TOOL']['SEQALIGN'];if(!is_executable($SEQ_ALIGN))			failProcess($JOB_ID."009",'Unable to Find seq_Align tool '.$SEQ_ALIGN);

	
	

	$COL_ORDER=array('prot_seq_al'=>'(prot_seq_al_id , prot_seq_ref_id , prot_seq_comp_id , perc_sim , perc_identity , length , e_value , bit_score , perc_sim_com , perc_identity_com)',
	'prot_seq_al_seq'=>'(prot_seq_al_seq_id , prot_seq_al_id , prot_seq_id_ref , prot_seq_id_comp )',
	'prot_dom_al'=>'(prot_dom_al_id , prot_dom_ref_id , prot_dom_comp_id , perc_sim , perc_identity , length , e_value , bit_score , perc_sim_com , perc_identity_com)',
	'prot_dom_al_seq'=>'(prot_dom_al_seq_id , prot_dom_al_id , prot_dom_seq_id_ref , prot_dom_seq_id_comp)'
);

addLog("Check inputs");
	$FILE_LIST=array($JOB_TYPE.'.fasta');
	foreach ($FILE_LIST as $FILE)	if (!checkFileExist($U_DIR.'/'.$FILE))				failProcess($JOB_ID."010",'Unable to access process dir '.$U_DIR.'/'.$FILE);

	$STATS=array('N_SEQ'=>0,'N_BLAST'=>0,'N_SEL'=>0,'N_ADDED'=>0,'N_VALID'=>0,'N_SEQ_VALID'=>0,'N_UPD'=>0,'N_DEL'=>0,'N_NEW'=>0,'N_SEQ_UPD'=>0);
	
addLog("Get list to process");
	$INPUT_FILE=$JOB_TYPE.'_pointer.csv';
	$fp=fopen($U_DIR.'/'.$INPUT_FILE,'r'); if (!$fp)									failProcess($JOB_ID."011",'Unable to open unique_pointers.csv '.$U_DIR.'/'.$FILE);
	
	
	/// This overall process is pretty long, so we split it in TOT_JOB (DEfault 50).
	$LINE_C=getLineCount($U_DIR.'/'.$INPUT_FILE);
	$N_P_JOB=ceil($LINE_C/$TOT_JOB);	
	$START=$N_P_JOB*($JOB_RUNID);
	$END=$N_P_JOB*($JOB_RUNID+1);
	$N_LINE=-1;
	$TO_PROCESS=array();
	echo $LINE_C."\t".$START."\t".$END."\n";
	/// Here we scan the pointer file and based on the START/END we store the lines we are going to process.
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");	if ($line=="")continue;
		
		$tab=explode("\t",$line);				if (count($tab)!=(($JOB_TYPE=='SEQ')?3:4)){continue;}
		
		$N_LINE++;								if ($N_LINE<$START || $N_LINE>=$END)continue;
		$TO_PROCESS[]=$tab;
	}
	fclose($fp);
	echo "NUMBER TO PROCESS:".count($TO_PROCESS)."\n";
	

addLog("Processing");

	$fpJS=fopen('JOB_'.$JOB_TYPE.'_'.$JOB_RUNID.'.json','w');if (!$fpJS)				failProcess($JOB_ID."012",'JOB_'.$JOB_TYPE.'_'.$JOB_RUNID.'.json');

	$START=false;$NTEST=0;
	$N_PROCESS=0;$N_MMATCH=0;
	foreach ($TO_PROCESS as $ENTRY)
	{
		++$N_PROCESS;
		echo "###### ".$ENTRY[0]."\t".$N_PROCESS."\t".$N_MMATCH."\t";
		
		 processEntry($ENTRY);
		 $STATS['N_SEQ']++;
		echo "STAT\tNSEQ:".$STATS['N_SEQ']."\tNBLAST:".$STATS['N_BLAST']."\tNSEL:".$STATS['N_SEL']."\tNADDED:".$STATS['N_ADDED']."\tNVALID:".$STATS['N_VALID']."\tNSEQVALID:".$STATS['N_SEQ_VALID']."\tNUPD:".$STATS['N_UPD']."\tNDEL:".$STATS['N_DEL']."\tNNEW:".$STATS['N_NEW']."\tNSEQUPD:".$STATS['N_SEQ_UPD']."\n";

	 	gc_collect_cycles();
	 	++$NTEST;  
	}
	echo "END\t".$N_PROCESS."\t".$N_MMATCH."\n";
	


	function processEntry($ENTRY)
	{
		
		global $fpJS;
		global $JOB_TYPE;
		global $JOB_RUNID;
		global $U_DIR;
		global $GLB_VAR;
		global $TG_DIR;
		global $STATS;
		global $BLASTP;
		global $SEQ_ALIGN;
		$DB_TYPE=strtolower($JOB_TYPE);
		$IN_FILE=$JOB_TYPE.'_'.$JOB_RUNID.'_input.fasta';
		
		$OUT_FILE=$JOB_TYPE.'_'.$JOB_RUNID.'_out.csv';
		$fp=fopen('../'.$JOB_TYPE.'.fasta','r');if (!$fp)	failProcess($JOB_ID."013",'Unable to open '.$JOB_TYPE.'.fasta');
		fseek($fp,$ENTRY[1]);
		$fpK=fopen($IN_FILE,'w');


		$str=array();
		$str=array_filter(explode("\n",fread($fp,$ENTRY[2])));
		fputs($fpK,implode("\n",$str));
		unset($str[0]);
		$REF_SEQ=implode("",$str);
		echo strlen($REF_SEQ)."\n";
		if (strlen($REF_SEQ)<30)return;

		
		fclose($fp);
		fclose($fpK);
		unlink($OUT_FILE);
		$command_line=$BLASTP.' ';
		if ($JOB_TYPE=='DOM') $command_line.=' -word_size=3 -max_hsps 1 -evalue 100 ';
		
		$command_line.= ' -query '.$IN_FILE.' -db ../'.$JOB_TYPE.'.fasta -outfmt "6 qseqid sseqid pident qlen slen length nident mismatch gapopen evalue bitscore" -out '.$OUT_FILE;
		echo $command_line;
		exec($command_line,$res,$return_code);
		if ($return_code !=0){fputs($fpE,$ENTRY[0]."\tBLAST\n");
			return;}
		
		$bpres=explode("\n",file_get_contents($OUT_FILE));
		print_r($bpres);
		$Nbpres=count($bpres);
		$STATS['N_BLAST']+=count($bpres);



		//if (count($bpres)==0)return;
		$selected=array();
		
		/// get list of current alignments:
		$query="SELECT * FROM prot_".$DB_TYPE.'_al WHERE prot_'.$DB_TYPE.'_ref_id = '.$ENTRY[0];
		
		$res=runQuery($query);
		$DB_LIST=array();
		foreach ($res as &$L)
		{
			$L['DB_STATUS']='FROM_DB';
			$DB_LIST[$L['prot_'.$DB_TYPE.'_comp_id']]=$L;
			//$selected[$L['prot_SEQ_comP_id']]=array();
		}
		
		
/*
/// 0   qseqid      REF_ISO_id
/// 1   sseqid      COMP_ISO_id
/// 2   pident      %identity
/// 3   qlen        Length of reference sequence
/// 4   slen        Length of compared sequence
/// 5   length      Alignment length
/// 6   nident      Number of identical matches
/// 7   mismatch    Number of mismatches
/// 8   gapopen     Number of gap openings
/// 9   evalue      E-Value
/// 10  bitscore    Bit Score

*/

		foreach ($bpres as $line)
		{
			if ($line=='')continue;
			$tab=explode("\t",$line);
			if ($tab[1]==$tab[0])continue;
			$RNAME=explode("-",$tab[0]);
			$CNAME=array();
			if (strpos($tab[1],'|')!==false)
			{
				$CNAME=explode("|",$tab[1]);
				unset($CNAME[0]);$CNAME=array_values($CNAME);
			}
			else $CNAME=explode("-",$tab[1]);
			
			if ($JOB_TYPE=='DOM')
			{
				if (($RNAME[1]=='CHAIN'&& $CNAME[1]!='CHAIN') || !($RNAME[1]!='CHAIN' && $CNAME[1]!='CHAIN'))continue;
			}
			
			if ($tab[2]<30)continue;
			$tab[1]=$CNAME[0];
			$tab[0]=$RNAME[0];
			$qlen =$tab[3];
			$length=$tab[5];
			if ($length/$qlen<0.7)continue;
			$selected[$tab[1]]=$tab;
		}
		$N_SEL=count($selected);
		

		$STATS['N_SEL']+=$N_SEL;

		/// Then we add orthologs and isoforms - primary only
		$query="select uS2.prot_".$DB_TYPE."_id
		FROM prot_".$DB_TYPE." US,  gn_prot_map GUM, gn_prot_map GUM2,prot_".$DB_TYPE." US2
		WHERE US.prot_entry_id = GUM.prot_entry_id AND GUM.gn_entry_id = GUM2.gn_entry_id  
		AND GUM2.prot_entry_id = US2.prot_entry_id AND US.prot_".$DB_TYPE."_id !=US2.prot_".$DB_TYPE."_id
		AND US.prot_".$DB_TYPE."_id=".$ENTRY[0].' AND US2.status!=9';
		if ($JOB_TYPE=='SEQ')	$query.=" AND US2.is_primary='T'";
			echo $query."\n";
		$res=runQuery($query);
		foreach ($res as $line)
		{
			if (isset($selected[$line["prot_".$DB_TYPE."_id"]]))continue;
			$selected[$line["prot_".$DB_TYPE."_id"]]=array($ENTRY[0],$line["prot_".$DB_TYPE."_id"],'ADDED'=>true);
		}
		
		$query="select DISTINCT uS2.prot_".$DB_TYPE."_id 
		FROM prot_".$DB_TYPE." US,  gn_prot_map GUM, gn_rel GR, gn_prot_map GUM2, prot_".$DB_TYPE." US2
		WHERE US.prot_entry_id = GUM.prot_entry_id AND GUM.gn_entry_id = gr.gn_entry_r_id 
		AND gr.gn_Entry_c_id = gum2.gn_entry_id
		AND GUM2.prot_entry_id = US2.prot_entry_id 
		AND US.prot_".$DB_TYPE."_id !=US2.prot_".$DB_TYPE."_id
		AND US.prot_".$DB_TYPE."_id=".$ENTRY[0].' AND US2.status!=9 ';
		if ($JOB_TYPE=='SEQ')	$query.=" AND US2.is_primary='T'";
		echo $query."\n";
		$res=runQuery($query);
		foreach ($res as $line)
		{
			if (isset($selected[$line["prot_".$DB_TYPE."_id"]]))continue;
			$selected[$line["prot_".$DB_TYPE."_id"]]=array($ENTRY[0],$line["prot_".$DB_TYPE."_id"],'ADDED'=>true);
		}
		
		echo "DONE\n";

		$STATS['N_ADDED']+=count($selected)-$N_SEL;

		if ($selected==array())return;
		
		$INPUT_FILE=$JOB_TYPE.'_pointer.csv';
		
		$fp=fopen($U_DIR.'/'.$INPUT_FILE,'r'); if (!$fp)									failProcess($JOB_ID."014",'Unable to open unique_pointers.csv '.$U_DIR.'/'.$FILE);
		$N_LINE=0;$NT=0;
		while(!feof($fp))
		{
			
			$line=stream_get_line($fp,1000,"\n");	if ($line=="")continue;
			$tab=explode("\t",$line);				if (count($tab)!=(($JOB_TYPE=='SEQ')?3:4)){continue;}
			
			if (isset($selected[$tab[0]])) $selected[$tab[0]]['fpos']=$tab;
		}
		
		fclose($fp);
		
		
		echo "Process Selected\n";
		
		foreach ($selected as $cid=>&$info)
		{
			if (!isset($info['fpos']))continue;
			echo $cid."\t";
			
			$fp=fopen('../'.strtoupper($JOB_TYPE).'.fasta','r');
			fseek($fp,$info['fpos'][1]);
			$str=array();
			$str=array_filter(explode("\n",fread($fp,$info['fpos'][2])));
			unset($str[0]);
			$info['SEQ']=implode("",$str);
			if (strlen($info['SEQ'])<30){echo "Too short\n";continue;}
			fclose($fp);
			$res=array();
			echo "ALIGNMENT\t";
			exec($SEQ_ALIGN.' -all -i -rn R -cn C '.$REF_SEQ.' '.$info['SEQ'],$res,$return_code);
			if ($return_code!=0)
			{
				echo "ISSUE\n";
				echo $ENTRY[0]."\t".$cid."\n";
				print_r($res);
				continue;
			}
			
			$info['ALIGN']['REF']=$res[1];
			$info['ALIGN']['COMP']=$res[3];
			$STAT=explode("\t",$res[4]);
			
			if ($JOB_TYPE=='DOM')
			{
				
				$NMATCH=0;
				for ($I=0;$I<strlen($info['ALIGN']['REF']);++$I)
				{
					$RL=substr($info['ALIGN']['REF'],$I,1);
					$CL=substr($info['ALIGN']['COMP'],$I,1);
					
					if ($RL!='-' && $CL!='-')$NMATCH++;
				}
			//	echo $NMATCH."\t".strlen(str_replace("-","",$info['ALIGN']['REF']))."\t".($NMATCH/strlen(str_replace("-","",$info['ALIGN']['REF'])))."\n";
				if ($NMATCH/strlen(str_replace("-","",$info['ALIGN']['REF']))<0.4)continue;
				if (isset($info['ADDED']) && $STAT[0]<0.3)continue;
			}
			
			//SPA.getIdentity()<<"\t"<<SPA.getSimilarity()<<"\t"<<SPA.getIdentityCommon()<<"\t"<<SPA.getSimilarityCommon()<<"\t"<<SPA.getScore()<<"\t"<<sqR.getName()<<"\t"<<sqC.getName()<<"\n";
			if (isset($DB_LIST[$info['fpos'][0]]))
			{
				echo "EXIST\t";
				//print_r($DB_LIST[$info['fpos'][0]]);
				$RECORD=&$DB_LIST[$info['fpos'][0]];
				$RECORD['DB_STATUS']='VALID';
				
				if (abs($RECORD['perc_identity']-round($STAT[0],3))>0.2){$RECORD['perc_identity']=round($STAT[0],3);$RECORD['DB_STATUS']='TO_UPD';}
				if (abs($RECORD['perc_sim']		-round($STAT[1],3))>0.2){$RECORD['perc_sim']	 =round($STAT[1],3);$RECORD['DB_STATUS']='TO_UPD';}
				if (abs($RECORD['perc_identity_com']-round($STAT[2],3))>0.2){$RECORD['perc_identity_com']=round($STAT[2],3);$RECORD['DB_STATUS']='TO_UPD';}
				if (abs($RECORD['perc_sim_com']-round($STAT[3],3))>0.2){$RECORD['perc_sim_com']=round($STAT[3],3);$RECORD['DB_STATUS']='TO_UPD';}
				if ($RECORD['length']!=strlen($info['ALIGN']['REF'])){$RECORD['length']=strlen($info['ALIGN']['REF']);$RECORD['DB_STATUS']='TO_UPD';}

				$res=array();
				if ($JOB_TYPE=='DOM')
				{
					
					echo "QUERY\t";
					$res=runQuery("select USP.letter as ref_letter, UDP.position as ref_position, USP2.letter AS comp_letter, UDP2.position as comp_position
					FROM prot_dom_al_seq USAS, prot_seq_pos USP, prot_dom_seq UDP, prot_seq_pos USP2, prot_dom_seq UDP2
					WHERE prot_dom_al_id=".$RECORD['prot_'.$DB_TYPE.'_al_id']." AND USAS.prot_dom_seq_id_ref=UDP.prot_dom_seq_id AND USP.prot_seq_pos_id = UDP.prot_seq_pos_id
					AND USP2.prot_seq_pos_id = UDP2.prot_seq_pos_id AND UDP2.prot_dom_seq_id = prot_dom_seq_id_comp  ORDER BY  UDP.position ASC ");
				}
				else
				{
					$res=runQuery("select USP.letter as ref_letter, USP.position as ref_position, USP2.letter AS comp_letter, USP2.position as comp_position
				FROM prot_seq_al_seq USAS, prot_seq_pos USP, prot_seq_pos USP2
				WHERE prot_seq_al_id=".$RECORD['prot_'.$DB_TYPE.'_al_id']." AND USP.prot_seq_pos_id = prot_seq_id_ref AND USP2.prot_seq_pos_id=prot_seq_id_comp ORDER BY prot_seq_al_seq_id ASC ");
				}
				
				$MAP=array();
				foreach ($res as $line)$MAP[$line['ref_position']]=$line['comp_position'];
				$RP=0;$CP=0;
				$VALID=true;
				for ($I=0;$I<strlen($info['ALIGN']['REF']);++$I)
				{
					$RL=substr($info['ALIGN']['REF'],$I,1);
					$CL=substr($info['ALIGN']['COMP'],$I,1);
					
					if ($RL!='-')$RP++;
					if ($CL!='-')$CP++;
					if ($RL=='-'||$CL=='-')continue;
					//echo $I." ".$RL.$RP." ".$CL.$CP." ".$MAP[$RP]."\n";
					if ($MAP[$RP]!=$CP){$VALID=false;break;}
					unset($MAP[$RP]);
				}
				if (count($MAP)==0 &&$VALID){$RECORD['SEQ_STATUS']='VALID';continue;}
				//echo "\n\n\nUPDATE\n\n\n";
				$RECORD['SEQ_STATUS']='TO_UPD';
				//print_r($info['ALIGN']);
				echo "GET SEQ\t";
				$res=runQuery("SELECT position,letter,prot_seq_id,prot_seq_pos_id FROM prot_seq_pos WHERE prot_seq_id IN (".$info[0].','.$info[1].')');
				if ($res===false){echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tUNABLE TO GET SEQUENCE\n";unset($DB_LIST[$info['fpos'][0]]);continue;}
				echo "SAVE RECORD\t";
				$MAPIDS=array();
				foreach ($res as $line)$MAPIDS[$line['prot_seq_id']][$line['position']]=array($line['letter'],$line['prot_seq_pos_id']);
				$RLEN=strlen(str_replace("-","",$info['ALIGN']['REF']));
				if ($RLEN!=count($MAPIDS[$info[0]])) 
				{echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tREF DIFFERNET SIZE\t".$RLEN."\t".count($MAPIDS[$info[0]])."\n";exit;}
				$RLEN=strlen(str_replace("-","",$info['ALIGN']['COMP']));
				if ($RLEN!=count($MAPIDS[$info[1]])) 
				{echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tREF DIFFERNET SIZE\t".$RLEN."\t".count($MAPIDS[$info[1]])."\n";exit;}
				$VALID=true;$RP=0;$CP=0;
				for ($I=0;$I<strlen($info['ALIGN']['REF']);++$I)
				{
					$RL=substr($info['ALIGN']['REF'],$I,1);
					$CL=substr($info['ALIGN']['COMP'],$I,1);
					
					if ($RL!='-')$RP++;
					if ($CL!='-')$CP++;
					if ($RL=='-'||$CL=='-')continue;
					if ($CL!=$MAPIDS[$info[1]][$CP][0] ){$VALID=false;break;}
					if ($RL!=$MAPIDS[$info[0]][$RP][0] ){$VALID=false;break;}
					$RECORD['ALIGN'][]=array($MAPIDS[$info[0]][$RP][1],$MAPIDS[$info[1]][$CP][1]);
				}
				echo "DONE\n";
				if (!$VALID){echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tUNABLE TO CREATE ALIGNMENT\n";unset($DB_LIST[$info['fpos'][0]]);continue;}
				
				
			}
			else
			{
				echo "NEW\t";
				//echo "\n\n\nINSERT\n\n\n";
				//print_r($info['ALIGN']);
				$res=array();
				if ($JOB_TYPE=='SEQ')$res	=runQuery("SELECT position,letter,prot_seq_id,prot_seq_pos_id FROM prot_seq_pos WHERE prot_seq_id IN (".$info[0].','.$info[1].')');
				else if ($JOB_TYPE=='DOM')$res	=runQuery("SELECT UDP.position,letter,prot_dom_id,prot_dom_seq_id FROM prot_Seq_pos USP, prot_dom_seq UDP WHERE UDP.prot_seq_pos_id = USP.prot_seq_pos_id AND prot_dom_id IN (".$info[0].','.$info[1].')');


				if ($res===false){echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tUNABLE TO GET SEQUENCE\n";unset($DB_LIST[$info['fpos'][0]]);continue;}
				//print_r($res);
				$MAPIDS=array();
				foreach ($res as $line)$MAPIDS[$line['prot_'.$DB_TYPE.'_id']][$line['position']]=array($line['letter'],$line[(($JOB_TYPE=='SEQ')?'prot_seq_pos_id':'prot_dom_seq_id')]);
				if (!isset($MAPIDS[$info[1]])||!isset($MAPIDS[$info[0]]))continue;
				$RECORD=array('prot_'.$DB_TYPE.'_ref_id'=>$info[0],'prot_'.$DB_TYPE.'_comp_id'=>$info[1]);
				$RECORD['DB_STATUS']='TO_INS';
				echo $info[0]."\t".$info[1]."\t%Iden:".round($STAT[0],3)."\t%sim:".round($STAT[1],3)."\t%iden_com:".round($STAT[2],3)."\t%sim_com:".round($STAT[3],3)."\t".((isset($info['ADDED'])?"ADDED":"BLAST" ))."\n";
				$RECORD['perc_identity']=round($STAT[0],3);
				$RECORD['perc_sim']	 =round($STAT[1],3);
				$RECORD['perc_identity_com']=round($STAT[2],3);
				$RECORD['perc_sim_com']=round($STAT[3],3);
				$RECORD['length']=strlen($info['ALIGN']['REF']);
				$RECORD['SEQ_STATUS']='TO_INS';
				$VALID=true;$RP=min(array_keys($MAPIDS[$info[0]]))-1;$CP=min(array_keys($MAPIDS[$info[1]]))-1;
				$RLEN=strlen(str_replace("-","",$info['ALIGN']['REF']));
				if ($RLEN!=count($MAPIDS[$info[0]])) 
				{echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tREF DIFFERNET SIZE\t".$RLEN."\t".count($MAPIDS[$info[0]])."\n";exit;}
				$RLEN=strlen(str_replace("-","",$info['ALIGN']['COMP']));
				if ($RLEN!=count($MAPIDS[$info[1]])) 
				{echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tREF DIFFERNET SIZE\t".$RLEN."\t".count($MAPIDS[$info[1]])."\n";exit;}
				// print_r($info);
				// print_R($MAPIDS);
				for ($I=0;$I<strlen($info['ALIGN']['REF']);++$I)
				{
					$RL=substr($info['ALIGN']['REF'],$I,1);
					$CL=substr($info['ALIGN']['COMP'],$I,1);
					
					if ($RL!='-')$RP++;
					if ($CL!='-')$CP++;
					if ($RL=='-'||$CL=='-')continue;
					//echo $RL.$RP.' '.$CL.$CP.' > '.$MAPIDS[$info[1]][$CP][0].$MAPIDS[$info[1]][$CP][1].' '.$MAPIDS[$info[0]][$RP][0].$MAPIDS[$info[0]][$RP][1]."\t".count($MAPIDS[$info[1]])."\t".count($MAPIDS[$info[0]])."\n";
					if ($CL!=$MAPIDS[$info[1]][$CP][0] ){$VALID=false;break;}
					if ($RL!=$MAPIDS[$info[0]][$RP][0] ){$VALID=false;break;}
					$RECORD['ALIGN'][]=array($MAPIDS[$info[0]][$RP][1],$MAPIDS[$info[1]][$CP][1]);
				}
				$DB_LIST[$info['fpos'][0]]=$RECORD;
			}
			
		}

		//// PRocess changes:
	foreach ($DB_LIST as $CID=>&$RECORD)
	{
		if ($RECORD['DB_STATUS']=='FROM_DB')
		{
			$STATS['N_DEL']++;
			$query="DELETE FROM prot_".$DB_TYPE."_al WHERE prot_".$DB_TYPE."_al_id=".$RECORD['prot_'.$DB_TYPE."_al_id"];
			if (!runQueryNoRes($query)) {echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tFAIL DELETION\n";}
			continue;
		}
		if ($RECORD['DB_STATUS']=='VALID')
		{
			$STATS['N_VALID']++;
			if ($RECORD['SEQ_STATUS']=='VALID'){
				$STATS['N_SEQ_VALID']++;
			}
			else
			{
				$STATS['N_SEQ_UPD']++;
				if (!runQueryNoRes("DELETE FROM prot_".$DB_TYPE.'_al_seq WHERE prot_'.$DB_TYPE.'_al_id = '.$RECORD['prot_'.$DB_TYPE."_al_id"]))
				{
					echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tFAIL SEQUENCE DELETION\n";
					continue;
				}

			}
		}
		if ($RECORD['DB_STATUS']=='TO_UPD')
		{
			$STATS['N_UPD']++;
			$query='UPDATE prot_'.$DB_TYPE.'_al SET perc_sim='.$RECORD['perc_sim'].
			',perc_identity='.$RECORD['perc_identity'].
			',perc_sim_com='.$RECORD['perc_sim_com'].
			',perc_identity_com='.$RECORD['perc_identity_com'].
			',length='.$RECORD['length'].
			' WHERE prot_seq_al_id='.$RECORD['prot_'.$DB_TYPE."_al_id"];
			if (!runQueryNoRes($query)) {echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tFAIL UPDATE\n";continue;}
			if ($RECORD['SEQ_STATUS']=='VALID')continue;
			if (!runQueryNoRes("DELETE FROM prot_".$DB_TYPE.'_al_seq WHERE prot_'.$DB_TYPE.'_al_id = '.$RECORD['prot_'.$DB_TYPE."_al_id"]))
			{
				echo $RECORD['prot_'.$DB_TYPE."_al_id"]."\tFAIL SEQUENCE DELETION\n";
				continue;
			}
		}
		if ($RECORD['DB_STATUS']=='TO_INS')$STATS['N_NEW']++;
		fputs($fpJS,json_encode($RECORD)."\n");
		
	}

	
		
}





















	


?>
