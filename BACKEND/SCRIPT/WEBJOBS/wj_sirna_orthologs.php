<?php

ini_set('memory_limit','10000M');
/**
 
 PURPOSE:    Find whether a siRNA sequence is conserved in orthologous genes
 
*/
$MD5_HASH=$argv[1];
error_reporting(E_ALL);
$JOB_NAME='web_job';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

$JOB_ID=getJobIDByName($JOB_NAME);
$JOB_INFO=$GLB_TREE[$JOB_ID];	

$RAW_INFO=runQuery("SELECT * FROM web_job where md5id = '".$MD5_HASH."'");
if ($RAW_INFO==array())exit;
cleanWebJobDoc($MD5_HASH);
date_default_timezone_set($GLB_VAR['TIMEZONE']);
$STATUS_INFO=array('STATUS'=>'Running',
					'LOG'=>array());
updateWebJobStatus($MD5_HASH,'Initiate siRNA orthologs');


updateWebJobStatus($MD5_HASH,'Verifying input parameters');

$INPUT_DATA=json_decode($RAW_INFO[0]['params'],true);
if ($INPUT_DATA==null)    failedWebJob($MD5_HASH,'Unable to interpret parameters');

if (!isset($INPUT_DATA['SEQUENCE']))	failedWebJob($MD5_HASH,'No sequence information found');
if (!isset($INPUT_DATA['ORGANISM']))	failedWebJob($MD5_HASH,'No organism provided');
if (!isset($INPUT_DATA['GENE']))	failedWebJob($MD5_HASH,'No gene provided');
if (!is_numeric($INPUT_DATA['GENE']))	failedWebJob($MD5_HASH,'Wrong format for  gene id');
if (!isset($INPUT_DATA['MISMATCH']))	failedWebJob($MD5_HASH,'No number of mismatch provided');
if (!isset($INPUT_DATA['SENSE']) && !isset($INPUT_DATA['ANTISENSE']))	failedWebJob($MD5_HASH,'No strand provided');//
//$INPUT_DATA['MISMATCH']=5;
//$INPUT_DATA['REGION']=array('premRNA');

$INPUT_DATA['SEQUENCE']=str_replace("U","T",$INPUT_DATA['SEQUENCE']);
//echo json_encode($INPUT_DATA);exit;

function getTranscriptBoundaries($TRANSCRIPTS)
{


    $res = runQuery("SELECT min(SEQ_POS) as MIN_POS, MAX(SEQ_POS) as MAX_POS, TRANSCRIPT_POS_TYPE,EXON_ID,TRANSCRIPT_ID 
					FROM TRANSCRIPT_POS TP,  TRANSCRIPT_POS_TYPE TPT
					WHERE TRANSCRIPT_ID IN (" . implode(',', $TRANSCRIPTS) . ")
					AND TPT.TRANSCRIPT_POS_TYPE_ID = TP.SEQ_POS_TYPE_ID
					
					GROUP BY TRANSCRIPT_ID,EXON_ID,TRANSCRIPT_POS_TYPE 
					ORDER BY TRANSCRIPT_ID, EXON_ID, MIN(SEQ_POS)");
    $DATA = array();
    foreach ($res as $line)
    {
        $line['transcript_pos_type']=str_replace('-INFERRED','',$line['transcript_pos_type']);
        $line['transcript_pos_type']=str_replace('-DIFFER','',$line['transcript_pos_type']);
        $DATA[$line['transcript_id']][] = $line;
    }
    return $DATA;
}

updateWebJobStatus($MD5_HASH,'Looking for orthologus genes and transcripts');
$RESULTS=array();
    $query="SELECT DISTINCT  m2.gene_id as gene_id,m2.symbol as symbol, m2.full_name as full_name , m2.gn_entry_id, m2.tax_id
    FROM mv_gene_sp m1, gn_rel , mv_gene_sp m2 where m1.gn_entry_id = gn_entry_r_id 
    AND m2.gn_entry_id = gn_entry_c_id AND m1.gene_id = ".$INPUT_DATA['GENE']. " AND m2.tax_id IN ('9606'";
    $t='';
    foreach ($INPUT_DATA['ORGANISM'] as $TAX_ID)
    {
        if (!is_numeric($TAX_ID))continue;
        $t.=",'".$TAX_ID."'";
    }
    if ($t=='')failedWebJob($MD5_HASH,'No valid organism found');

    $res=runQuery($query.$t.')');
    $GENES=array($INPUT_DATA['GENE']);
    foreach ($res as $line)
    {
        $GENES[$line['gn_entry_id']]=$line;
        $RESULTS[$line['tax_id']][$line['gene_id']]['GENE']=$line;
    }
    $res=runQuery("SELECT DISTINCT gene_id, symbol, full_name, gn_entry_id FROM gn_entry where gene_Id = ".$INPUT_DATA['GENE']);
    foreach ($res as $line)
    {
        $line['tax_id']='9606';
        $GENES[$line['gn_entry_id']]=$line;
        $RESULTS[$line['tax_id']][$line['gene_id']]['GENE']=$line;
    }



    $query='SELECT * FROM transcript t, gene_seq gs WHERE t.gene_seq_id = gs.gene_seq_id AND gn_entry_Id IN ('.implode(',',array_keys($GENES)).')';
    $res=runQuery($query);
    $TEST=0;

    $TR_IDS=array();
    $TR_BOUNDARIES=array();
    foreach ($res as $line)$TR_IDS[]=$line['transcript_id'];
    $TR_BOUNDARIES=getTranscriptBoundaries($TR_IDS);
    

    
    $REF_POSSIBLE=array();
    $REF_TR_INFO=array();$N_TR_INFO=0;
    $LEN_QUERY=strlen($INPUT_DATA['SEQUENCE']);
    foreach ($res as $line)
    {
        $INFO_TR=$line;
        foreach ($GENES[$line['gn_entry_id']] as $K=>$V)$INFO_TR[$K]=$V;
        updateWebJobStatus($MD5_HASH,'Testing transcript '.$INFO_TR['transcript_name']);
        $res2=runQuery("SELECT nucl FROM transcript_pos where transcript_id = ".$line['transcript_id'].' ORDER BY SEQ_POS ASC');
        $TRANSCRIPT='';
        foreach ($res2 as $l2)  $TRANSCRIPT.=$l2['nucl'];
        ++$N_TR_INFO;
        $REF_TR_INFO[$N_TR_INFO]=$INFO_TR;
        $LEN_TR=strlen($TRANSCRIPT);
        for ($I=0;$I<=$LEN_TR-$LEN_QUERY;++$I)
        {
            $REF_POSSIBLE[substr($TRANSCRIPT,$I,$LEN_QUERY)][]=array($N_TR_INFO,$I);
        }
    }



updateWebJobStatus($MD5_HASH,'Generating alternative sequences');
$LEN_SEQ=strlen($INPUT_DATA['SEQUENCE']);
$LIST_ALT=array();
$N_FOUND=array();
    $TEST=0;
for ($I=0;$I<=$INPUT_DATA['MISMATCH'];++$I)
{$LIST_ALT[$I]=0;
    $N_FOUND[$I]=0;
}

if (isset($INPUT_DATA['SENSE']))genAlt($INPUT_DATA['SEQUENCE'] ,$INPUT_DATA['MISMATCH']);
if (isset($INPUT_DATA['ANTISENSE']))genAlt(genReverse($INPUT_DATA['SEQUENCE'] ),$INPUT_DATA['MISMATCH']);

foreach ($LIST_ALT as $M=>$L)
updateWebJobStatus($MD5_HASH,$L.' for '.$M.' mismatch(es)');


updateWebJobStatus($MD5_HASH,$TEST.' tests run');
foreach ($N_FOUND as $I=>$N)
updateWebJobStatus($MD5_HASH,$N.' match with '.$I.' mismatch(es)');
//print_r($RESULTS);

function genReverse($STR)
{
	$REV='';
	$REV_STR=strrev($STR);
	for($I=0;$I<strlen($STR);++$I)
	{
		switch ($REV_STR[$I])
		{
		case 'A':$REV.='T';break;
		case 'T':$REV.='A';break;
		case 'C':$REV.='G';break;
		case 'G':$REV.='C';break;
		}
	}
return $REV;
}

function testSeq($SEQ_TEST,$N_MISMATCH,$MISMATCH_LIST)
{
    global $REF_POSSIBLE;
    global $REF_TR_INFO;
    global $RESULTS;
    global $TEST;
    global $N_FOUND;  
    global $TR_BOUNDARIES;  
    ++$TEST;
    if (!isset($REF_POSSIBLE[$SEQ_TEST]))return;
    foreach ($REF_POSSIBLE[$SEQ_TEST] as &$MATCH)
    {
        $INFO_TR=$REF_TR_INFO[$MATCH[0]]   ;
        $N_FOUND[$N_MISMATCH]++;
        $INFO_TR['MISMATCH']=$MISMATCH_LIST;
        $INFO_TR['POS_MATCH']=$MATCH[1]+1;
        echo $INFO_TR['transcript_name']."\t".($MATCH[1]+1)."\t".$N_MISMATCH."\t".$SEQ_TEST."\t".$MISMATCH_LIST."\n";
        
        $REGIONS=array();
        for ($I=0;$I<strlen($SEQ_TEST);++$I)
        {
            $POS_TR=$I+$MATCH[1]+1;
            foreach ($TR_BOUNDARIES[$INFO_TR['transcript_id']] as $B)
            {
                if ($POS_TR<$B['min_pos']||$POS_TR>$B['max_pos'])continue;
                if (!isset($REGIONS[$B['transcript_pos_type']]))$REGIONS[$B['transcript_pos_type']]=1;
                else $REGIONS[$B['transcript_pos_type']]++;
            }
        }
        $INFO_TR['REGIONS']=$REGIONS;
        $RESULTS[$INFO_TR['tax_id']][$INFO_TR['gene_id']]['RES']['N'.$N_MISMATCH][$SEQ_TEST][]=$INFO_TR;
    }
}

function genAlt($SEQ,$MAX)
{
global $LIST_ALT;
	
	$LIST_ALT[0]++;
    testSeq($SEQ,0,'');
	$RULES=array('A','T','C','G');
	$LEN=strlen($SEQ);
	for ($I=0;$I<$LEN;++$I)
	{
		foreach ($RULES as $N)
		{
			if ($SEQ[$I]==$N)continue;
			$SEQ_L1=substr($SEQ,0,$I).$N.substr($SEQ,$I+1);
            testSeq($SEQ_L1,1,$I);
			$LIST_ALT[1]++;
			//echo $SEQ_L1."<br/>";
			for ($J=$I+1;$J<$LEN;++$J)
			{	
				foreach ($RULES as $N2)
				{
					if ($SEQ[$J]==$N2)continue;
					$SEQ_L2=substr($SEQ_L1,0,$J).$N2.substr($SEQ_L1,$J+1);
                    testSeq($SEQ_L2,2,$I.'/'.$J);
					$LIST_ALT[2]++;
                    if ($MAX>=3)
					for ($K=$J+1;$K<$LEN;++$K)
					{	
						foreach ($RULES as $N3)
						{
							if ($SEQ[$K]==$N3)continue;
							$SEQ_L3=substr($SEQ_L2,0,$K).$N3.substr($SEQ_L2,$K+1);
                            testSeq($SEQ_L3,3,$I.'/'.$J.'/'.$K);
							$LIST_ALT[3]++;
                            if ($MAX>=4)
                            for ($L=$K+1;$L<$LEN;++$L)
                            {	
                                foreach ($RULES as $N4)
                                {
                                    if ($SEQ[$L]==$N4)continue;
                                    $SEQ_L4=substr($SEQ_L3,0,$L).$N4.substr($SEQ_L3,$L+1);
                                    testSeq($SEQ_L4,4,$I.'/'.$J.'/'.$K.'/'.$L);
                                    $LIST_ALT[4]++;
                                    if ($MAX>=5)
                                    for ($M=$L+1;$M<$LEN;++$M)
                                    {	
                                        foreach ($RULES as $N5)
                                        {
                                            if ($SEQ[$M]==$N5)continue;
                                            $LIST_ALT[5]++;
                                            $SEQ_L5=substr($SEQ_L4,0,$M).$N5.substr($SEQ_L4,$M+1);
                                            testSeq($SEQ_L5,5,$I.'/'.$J.'/'.$K.'/'.$L.'/'.$M);
                                    
                                            if ($MAX>=6)
                                            for ($N=$M+1;$N<$LEN;++$N)
                                            {	
                                                foreach ($RULES as $N6)
                                                {
                                                    if ($SEQ[$N]==$N6)continue;
                                                    $LIST_ALT[6]++;
                                                    $SEQ_L6=substr($SEQ_L5,0,$N).$N6.substr($SEQ_L5,$N+1);
                                                    testSeq($SEQ_L6,6,$I.'/'.$J.'/'.$K.'/'.$L.'/'.$M.'/'.$N);
                                            
                                                    
                                                }
                                            }
                                            
                                        }
                                    }
                                }
                            }
						}
					}
				}
			}
		}
	}
	
}

uploadWebJobDoc($MD5_HASH,'results.json','application/json',json_encode($RESULTS),'siRNA ortholog analysis');


print_r($RESULTS);
$STATUS_INFO['STATUS']='Success';

$STATUS_INFO['LOG'][]=array('Job successfully finished',date("F j, Y, g:i a"));


runQueryNoRes("Update web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."', time_end=CURRENT_TIMESTAMP WHERE md5id = '".$MD5_HASH."'");


?>
