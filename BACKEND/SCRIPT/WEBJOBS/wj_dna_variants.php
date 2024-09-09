<?php

ini_set('memory_limit','3000M');
/**
 
 PURPOSE:     Get and update list of internal assays
 
*/
$AVOID=array('the','of','to','and','a','in','is','it','you','that','he','was','for','on','are','with','as','I','his','they','be','at','one','have','this','from','or','had','by','hot','but','some','what','there','we','can','out','other','were','all','your','when','up','use','word','how','said','an','each','she','which','do','their','time','if','will','way','about','many','then','them','would','write','like','so','these','her','long','make','thing','see','him','two','has','look','more','day','could','go','come','did','my','sound','no','most','number','who','over','know','water','than','call','first','people','may','down','side','been','now','find','any','new','work','part','take','get','place','made','live','where','after','back','little','only','round','man','year','came','show','every','good','me','give','our','under','name','very','through','just','form','much','great','think','say','help','low','line','before','turn','cause','same','mean','differ','move','right','boy','old','too','do
es','tell','sentence','set','three','want','air','well','also','play','small','end','put','home','read','hand','port','large','spell','add','even','land','here','must','big','high','such','follow','act','why','ask','men','change','went','light','kind','off','need','house','picture','try','us','again','animal','point','mother','world','near','build','self','earth','father','head','stand','own','page','should','country','found','answer','school','grow','study','still','learn','plant','cover','food','sun','four','thought','let','keep','eye','never','last','door','between','city','tree','cross','since','hard','start','might','story','saw','far','sea','draw','left','late','run','don’t','while','press','close','night','real','life','few','stop','open','seem','together','next','white','children','begin','got','walk','example','ease','paper','often','always','music','those','both','mark','book','letter','until','mile','river','car','feet','care','second','group','carry','took','rain','eat','
room','friend','began','idea','fish','mountain','north','once','base','hear','horse','cut','sure','watch','color','face','wood','main','enough','plain','girl','usual','young','ready','above','ever','red','list','though','feel','talk','bird','soon','body','dog','family','direct','pose','leave','song','measure','state','product','black','short','numeral','class','wind','question','happen','complete','ship','area','half','rock','order','fire','south','problem','piece','told','knew','pass','farm','top','whole','king','size','heard','best','hour','better','true','during','hundred','am','remember','step','early','hold','west','ground','interest','reach','fast','five','sing','listen','six','table','travel','less','morning','ten','simple','several','vowel','toward','war','lay','against','pattern','slow','center','love','person','money','serve','appear','road','map','science','rule','govern','pull','cold','notice','voice','fall','power','town','fine','certain','fly','unit','lead','cry','dark','
machine','note','wait','plan','figure','star','box','noun','field','rest','correct','able','pound','done','beauty','drive','stood','contain','front','teach','week','final','gave','green','oh','quick','develop','sleep','warm','free','minute','strong','special','mind','behind','clear','tail','produce','fact','street','inch','lot','nothing','course','stay','wheel','full','force','blue','object','decide','surface','deep','moon','island','foot','yet','busy','test','record','boat','common','gold','possible','plane','age','dry','wonder','laugh','thousand','ago','ran','check','game','shape','yes','hot','miss','brought','heat','snow','bed','bring','sit','perhaps','fill','east','weight','language','among');

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
//print_r($RAW_INFO);
if ($RAW_INFO==array())exit;
cleanWebJobDoc($MD5_HASH);
date_default_timezone_set($GLB_VAR['TIMEZONE']);
$STATUS_INFO=array('STATUS'=>'Running',
					'LOG'=>array());
updateWebJobStatus($MD5_HASH,'Initiate search DNA Variants');


updateWebJobStatus($MD5_HASH,'Verifying input parameters');

$INPUT_DATA=json_decode($RAW_INFO[0]['params'],true);
if ($INPUT_DATA==null)    failedWebJob($MD5_HASH,'Unable to interpret parameters');

updateWebJobStatus($MD5_HASH,'Searching DNA Variants');




$W_DIR=$TG_DIR.'/'.$JOB_INFO['DIR'].'/'; if (!is_dir($W_DIR) && !mkdir($W_DIR))failedWebJob($MD5_HASH,'Unable to create web job directory');
$W_DIR.=$MD5_HASH;if (!is_dir($W_DIR) && !mkdir($W_DIR))failedWebJob($MD5_HASH,'Unable to create job directory');
if (!chdir($W_DIR))failedWebJob($MD5_HASH,'Unable to access job directory');

$LIST_TR=array();
if (isset($INPUT_DATA['GENE']))
{
   $TR= getListTranscripts($INPUT_DATA['GENE']);
   
   foreach ($TR as $T)$LIST_TR[]=$T['transcript_id'];
}
// 
$fpS=fopen('STATS','w');
$HEAD="CHROMOSOME\tPOSITION\tMAX_FREQ";
if ($LIST_TR!=array())$HEAD.="\tEXON_RATIO";
fputs($fpS,$HEAD."\n");
$fp=fopen('VARIANTS','w');
$HEAD="CHROMOSOME\tPOSITION\tNUCL\tREF_ALL\tALT_ALLELE\tREF_COUNT\tALT_COUNT\tSTUDY\tFREQ";
if ($LIST_TR!=array())$HEAD.="\tEXON_RATIO";
fputs($fp,$HEAD."\n");
$RANGE=$INPUT_DATA['END_POS']-$INPUT_DATA['START_POS'];
$STEP=ceil($RANGE/100);
$GROUPS=array();
for ($I=0;$I<100;$I+=5)
$GROUPS[$INPUT_DATA['START_POS']+$I*$STEP]=$I;
for ($I=$INPUT_DATA['START_POS']; $I<=$INPUT_DATA['END_POS'] ;++$I)
{
    if (isset($GROUPS[$I]))updateWebJobStatus($MD5_HASH,$GROUPS[$I].'% done');

$RATIO='';
if ($LIST_TR!=array())
{
    $res=runQuery('SELECT transcript_id FROM transcript_pos tp, chr_seq_pos csp where csp.chr_seq_pos_id = tp.chr_Seq_pos_id AND chr_seq_id = '.$INPUT_DATA['CHR_INFO']['CHR_SEQ_ID'].' AND chr_pos='.$I.' AND transcript_id IN ('.implode(',',$LIST_TR).')');
    $RATIO=round(count($res)/count($LIST_TR)*100,3);
}

$query='SELECT chr_pos,nucl,vr.variant_seq as ref_all, vk.variant_seq as alt_all,ref_count,alt_count,short_name
    FROM chr_seq_pos c,
    variant_position vp 
    LEFT JOIN variant_allele vr ON vr.variant_allele_id = ref_all,
    variant_change vc  LEFT JOIN variant_allele vk ON vk.variant_allele_id = alt_all, variant_frequency vf, variant_freq_study vfs
    where 
    chr_seq_id = '.$INPUT_DATA['CHR_INFO']['CHR_SEQ_ID'].' AND chr_pos='.$I.'    AND c.chr_seq_pos_id = vp.chr_seq_pos_id
    AND vp.variant_position_id = vc.variant_position_id
    AND vc.variant_change_id = vf.variant_change_id
    AND vf.variant_freq_study_id = vfs.variant_freq_study_Id';
$res=runQuery($query);
$MAX=0;$NUCL='';
foreach ($res as $line)
{
    $NUCL=$line['nucl'];
    $fq=0;
    if ($line['alt_count']!=0)
    $fq=round($line['ref_count']/$line['alt_count']*100,3);
  
    if ($line['ref_all']!=$line['alt_all'] && $fq>$MAX && $line['alt_count']>500)$MAX=$fq;
    $VAL=$INPUT_DATA['CHROMOSOME']."\t".implode("\t",$line)."\t".$fq;
    if ($LIST_TR!=array())$VAL.="\t".$RATIO;
    fputs($fp, $VAL."\n");
}
$VAL=$INPUT_DATA['CHROMOSOME']."\t".$I."\t".$MAX;
    if ($LIST_TR!=array())$VAL.="\t".$RATIO;
fputs($fpS,$VAL."\n");
}

fclose($fp);fclose($fpS);
uploadWebJobDoc($MD5_HASH,'results_'.$INPUT_DATA['CHROMOSOME'].'_'.$INPUT_DATA['START_POS'].'_'.$INPUT_DATA['END_POS'].'.csv','text/csv',file_get_contents('VARIANTS'),'List of variants');
uploadWebJobDoc($MD5_HASH,'results_'.$INPUT_DATA['CHROMOSOME'].'_'.$INPUT_DATA['START_POS'].'_'.$INPUT_DATA['END_POS'].'_summary.csv','text/csv',file_get_contents('STATS'),'Summarized results');
$STATUS_INFO['STATUS']='Success';

$STATUS_INFO['LOG'][]=array('Job successfully finished',date("F j, Y, g:i a"));


runQueryNoRes("Update web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."', time_end=CURRENT_TIMESTAMP WHERE md5id = '".$MD5_HASH."'");

function getListTranscripts($GENE_ID)
{
   

    $res = array();
    $query="SELECT  T.TRANSCRIPT_ID, TRANSCRIPT_NAME,TRANSCRIPT_VERSION
    FROM  TRANSCRIPT T
    LEFT JOIN (SELECT SO_ID,SO_NAME,SO_DESCRIPTION, SEQ_TYPE,SEQ_BTYPE_ID FROM SEQ_BTYPE SB LEFT JOIN SO_ENTRY S ON SB.SO_ENTRY_ID =  S.SO_ENTRY_ID) SB ON SB.SEQ_BTYPE_ID = BIOTYPE_ID ,GENE_SEQ GS,GN_ENTRY GE
	WHERE  GE.GN_ENTRY_ID = GS.GN_ENTRY_ID AND GS.GENE_SEQ_ID = T.GENE_SEQ_ID  AND  (SB.SEQ_TYPE='protein_coding' OR SB.SEQ_TYPE='mRNA') AND GE.GENE_ID= ".$GENE_ID;
    
    return runQuery($query);

}
?>
