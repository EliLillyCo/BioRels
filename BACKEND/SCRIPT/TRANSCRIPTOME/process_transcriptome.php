<?php


error_reporting(E_ALL);
ini_set('memory_limit','5000M');


$JOB_RUNID=$argv[1];

$JOB_NAME='process_transcriptome';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];



addLog("Go to directory");
	$CK_INFO=$GLB_TREE[getJobIDByName('pmj_transcriptome')];
	$U_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($U_DIR)) 					failProcess($JOB_ID."001",'NO '.$U_DIR.' found ');
	$U_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$U_DIR);
	$U_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($U_DIR) && !mkdir($U_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$U_DIR);
	echo $U_DIR."\n";
	$W_DIR=$U_DIR.'/RESULTS/';						if (!is_dir($W_DIR) && !chdir($W_DIR)) 	failProcess($JOB_ID."004",'Unable to create job dir '.$W_DIR);
	if (!chdir($W_DIR)) 																failProcess($JOB_ID."005",'Unable to access process dir '.$W_DIR);
	
    if (is_dir('DATA_'.$JOB_RUNID)) cleanDirectory('DATA_'.$JOB_RUNID);
    if (!mkdir('DATA_'.$JOB_RUNID))					failProcess($JOB_ID."006",'Unable to create data dir '.$W_DIR);
    if (!chdir('DATA_'.$JOB_RUNID))					failProcess($JOB_ID."007",'Unable to access data dir '.$W_DIR);
	

    addLog("Read options");

    $OPT_W_PREMRNA=false; if ($GLB_VAR['TRANSCRIPTOME_W_PREMRNA']=='Y')$OPT_W_PREMRNA=true;
    $OPT_W_PROMOTER=false; if ($GLB_VAR['TRANSCRIPTOME_W_PROMOTER']=='Y')$OPT_W_PROMOTER=true;
    $OPT_W_SEEDS=false; if ($GLB_VAR['TRANSCRIPTOME_W_SEEDS']=='Y')$OPT_W_SEEDS=true;
    $OPT_W_REGION=false; if ($GLB_VAR['TRANSCRIPTOME_W_REGION']=='Y')$OPT_W_REGION=true;
    
    addLog("Output pre-mRNA sequences ".($OPT_W_PREMRNA)?"YES":"NO");
    addLog("Output promotor region ".($OPT_W_PROMOTOR)?"YES":"NO");
    addLog("Output Seeds region ".($OPT_W_SEEDS)?"YES":"NO");
    addLog("Output mRNA regions (3'UTR/CDS/5'UTR) ".($OPT_W_REGION)?"YES":"NO");



addLog("Read input file");
    $INPUT_FILE=$U_DIR.'/SCRIPTS/job_input_'.$JOB_RUNID;
    if (!checkFileExist($INPUT_FILE))											failProcess($JOB_ID."008",'Unable to find  '.$INPUT_FILE);
    $fpI=fopen($INPUT_FILE,'r');
    if (!$fpI)											failProcess($JOB_ID."009",'Unable to open  '.$INPUT_FILE);
    
    while(!feof($fpI))
    {
        $line=stream_get_line($fpI,100000,"\n");
        if ($line=='')continue;
        $tab=explode("\t",$line);
        $gn_entry_id=$tab[0];
        processGene($gn_entry_id);
    }


    function processGene(&$gn_entry_id)
    {
        global $JOB_ID;
        addLog("Process gene ".$gn_entry_id);


        $query="SELECT TRANSCRIPT_ID,TRANSCRIPT_NAME,TRANSCRIPT_VERSION,GENE_ID,SYMBOL,T2.TAX_ID,GENE_SEQ_NAME,CHR_SEQ_NAME,CHR_NUM,GENE_SEQ_VERSION
            FROM  TRANSCRIPT T,CHROMOSOME C, taxon T2, CHR_SEQ CS, GENE_SEQ GS 
            LEFT JOIN (SELECT DISTINCT GN_ENTRY_ID, GENE_ID, SYMBOL FROM MV_GENE) G ON G.GN_ENTRY_ID = GS.GN_ENTRY_ID 
            WHERE GS.GENE_SEQ_ID = T.GENE_SEQ_ID AND C.CHR_ID = CS.CHR_ID
            AND cs.chr_seq_id=GS.CHR_SEQ_ID AND T2.taxon_id = C.taxon_id AND g.gn_entry_Id  ";
            if ($gn_entry_id!='')$query.=' ='.$gn_entry_id;
            else $query.=' IS NULL';

			$res=runQuery($query);
			addLog("\t".count($res)." transcripts");
            if ($res===false)	failProcess($JOB_ID."010",'Unable to run query '.$query);

            foreach ($res as $tab)
            {
                $NAME='transcript='.$tab['transcript_name'].((isset($tab['transcript_version']))?'.'.$tab['transcript_version']:'').';taxId='.$tab['tax_id'].';gid='.$tab['gene_id'].';symbol='.$tab['symbol']
                .';chr='.$tab['chr_num'].';chr_seq_name='.$tab['chr_seq_name'].';gene_seqname='.$tab['gene_seq_name'].((isset($tab['gene_seq_version']))?'.'.$tab['gene_seq_version']:'');

                processTranscript($tab['tax_id'],$NAME,$tab['transcript_id']);
            }

    }


    function processTranscript(&$TAX_ID,&$NAME,&$TRANSCRIPT_ID)
    {
		global $OPT_W_REGION;
		global $OPT_W_SEEDS;
		addLog("\t\tProcess transcript ".$NAME);
        global $JOB_ID;
        if (!is_dir($TAX_ID) &&!mkdir($TAX_ID)) failProcess($JOB_ID."011",'Unable to create taxon directory '.$TAX_ID);
        
        
		$TR_SEQS=array();$TR_TYPE=array();
		addLog("\t\t\t Get transcript sequence ".$NAME);
        $query='SELECT TRANSCRIPT_ID, NUCL,SEQ_POS,TRANSCRIPT_POS_TYPE FROM TRANSCRIPT_POS TP LEFT JOIN transcript_pos_type tpt ON seq_pos_type_id = transcript_pos_type_id   WHERE TRANSCRIPT_ID='.$TRANSCRIPT_ID.' ORDER BY TRANSCRIPT_ID, SEQ_POS ASC';
        $res=array();
		$res=runQuery($query);
		
        if ($res===false)														failProcess($JOB_ID."012",'Unable to get transcript sequence for '.$TAX_ID);
		addLog("\t\t\t Length transcript ".count($res));
		if (count($res)==0)return;
        foreach ($res as $tab)
        {
            $TR_SEQS[$tab['seq_pos']]=$tab['nucl'];
            if (!isset($TR_TYPE[$tab['transcript_pos_type']]))$TR_TYPE[$tab['transcript_pos_type']]=array($tab['seq_pos'],$tab['seq_pos']);
            $TR_TYPE[$tab['transcript_pos_type']][1]=$tab['seq_pos'];
		}
		
		addLog("\t\t\t Save to transcriptome ");
		$fp=fopen($TAX_ID.'/'.$TAX_ID.'_transcriptome.fa','a');
        fputs($fp,'>'.$NAME."\n");
		fputs($fp,implode("\n",str_split(implode("",$TR_SEQS),80))."\n");
		fclose($fp);
			
		if ($OPT_W_REGION){
		addLog("\t\t\t Save to groups ");
		$fp3P=fopen($TAX_ID.'/'.$TAX_ID.'_3UTR.fa','a');
        $fp5P=fopen($TAX_ID.'/'.$TAX_ID.'_5UTR.fa','a');
        $fpCDS=fopen($TAX_ID.'/'.$TAX_ID.'_CDS.fa','a');
        $SEQ_TR=implode("",$TR_SEQS);
        if (isset($TR_TYPE["3'UTR"]))fputs($fp3P,'>'.$NAME."\n".substr($SEQ_TR,$TR_TYPE["3'UTR"][0]-1,$TR_TYPE["3'UTR"][1]-$TR_TYPE["3'UTR"][0]+1)."\n");
        if (isset($TR_TYPE["5'UTR"]))fputs($fp5P,'>'.$NAME."\n".substr($SEQ_TR,$TR_TYPE["5'UTR"][0]-1,$TR_TYPE["5'UTR"][1]-$TR_TYPE["5'UTR"][0]+1)."\n");
		if (isset($TR_TYPE["CDS"]))fputs($fpCDS,'>'.$NAME."\n".substr($SEQ_TR,$TR_TYPE["CDS"][0]-1,$TR_TYPE["CDS"][1]-$TR_TYPE["CDS"][0]+1)."\n");
		fclose($fp3P);
		fclose($fp5P);
		fclose($fpCDS);
		}
		
		if ($OPT_W_SEEDS){
			addLog("\t\t\t Save to SEEDS ");
			$fpSeed=fopen('SEEDS.csv','a');
			$STR_BULK='';
            for($I=0;$I<=strlen($SEQ_TR)-7;++$I)
            {
                    $seed=substr($SEQ_TR,$I+1,7);
                    if (strlen($seed)!=7)continue;
                    $TYPES=array();
                    for ($J=$I+1;$J!=$I+8;++$J)
                    {
                        foreach ($TR_TYPE as $TYPE=>$RANGE)
                        {
                            if ($J>=$RANGE[0] && $J<=$RANGE[1])$TYPES[$TYPE]=true;
                        }
                    }
					$STR_BULK.=$seed."\t".str_replace(":","\t",$NAME)."\t".($I+1)."\t".implode(":",array_keys($TYPES))."\n";
					if (strlen($STR_BULK)>100000)
					{
						fputs($fpSeed,$STR_BULK);$STR_BULK='';
					}
                    
			}
			fputs($fpSeed,$STR_BULK);$STR_BULK='';
           
fclose($fpSeed);
	}
    }


    ?>
