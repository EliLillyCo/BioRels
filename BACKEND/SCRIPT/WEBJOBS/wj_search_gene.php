<?php

ini_set('memory_limit','3000M');
/**
 
 PURPOSE:     Get and update list of internal assays
 
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
updateWebJobStatus($MD5_HASH,'Initiate search gene');


updateWebJobStatus($MD5_HASH,'Verifying input parameters');

$INPUT_DATA=json_decode($RAW_INFO[0]['params'],true);
if ($INPUT_DATA==null)    failedWebJob($MD5_HASH,'Unable to interpret parameters');

if (!isset($INPUT_DATA['QUERY']))	failedWebJob($MD5_HASH,'No query information found');
$TEXT=&$INPUT_DATA['QUERY'];
updateWebJobStatus($MD5_HASH,'Searching gene');
$MATCH=array();
$AVOID=array('the','of','to','and','a','in','is','it','you','that','arm','ph2','gov','jan','feb','mar','avr','may','jun','jul','aug','sep','oct','nov','dec','he','was','for','on','post','are','with','as','I','his','they','be','at','one','have','this','from','or','had','by','hot','but','some','what','there','we','can','out','other','were','all','your','when','up','use','word','how','said','an','each','she','which','do','their','time','if','will','way','about','many','then','them','would','write','like','so','these','her','long','make','thing','see','him','two','has','look','more','day','could','go','come','did','my','sound','no','most','number','who','over','know','water','than','call','first','people','may','down','side','been','now','find','any','new','work','part','take','get','mad','plan','march','mar','get','place','made','live','where','after','back','little','only','round','man','year','came','show','every','good','me','give','our','under','name','very','through','just','form','much','great','think','say','help','low','line','before','turn','cause','same','mean','differ','move','right','boy','old','too','does','tell','sentence','set','three','want','air','well','also','play','small','end','put','home','read','hand','port','large','spell','add','even','land','here','must','big','high','such','follow','act','why','ask','men','change','went','light','kind','off','need','house','picture','try','us','again','animal','point','mother','world','near','build','self','earth','father','head','stand','own','page','should','country','found','answer','school','grow','study','still','learn','plant','cover','food','sun','four','thought','let','keep','eye','never','last','door','between','city','tree','cross','since','hard','start','might','story','saw','far','sea','draw','left','late','run','don’t','while','press','close','night','real','life','few','stop','open','seem','together','next','white','children','begin','got','walk','example','ease','paper','often','always','music','those','both','mark','book','letter','until','mile','river','car','feet','care','second','group','carry','took','rain','eat','room','friend','began','idea','fish','mountain','north','once','base','hear','horse','cut','sure','watch','color','face','wood','main','enough','plain','girl','usual','young','ready','above','ever','red','list','though','feel','talk','bird','soon','body','dog','family','direct','pose','leave','song','measure','state','product','black','short','numeral','class','wind','question','happen','complete','ship','area','half','rock','order','fire','south','problem','piece','told','knew','pass','farm','top','whole','king','size','heard','best','hour','better','true','during','hundred','am','remember','step','early','hold','west','ground','interest','reach','fast','five','sing','listen','six','table','travel','less','morning','ten','simple','several','vowel','toward','war','lay','against','pattern','slow','center','love','person','money','serve','appear','road','map','science','rule','govern','pull','cold','notice','voice','fall','power','town','fine','certain','fly','unit','lead','cry','dark','machine','note','wait','plan','figure','star','box','noun','field','rest','correct','able','pound','done','beauty','drive','stood','contain','front','teach','week','final','gave','green','oh','quick','develop','sleep','warm','free','minute','strong','special','mind','behind','clear','tail','produce','fact','street','inch','lot','nothing','course','stay','wheel','full','force','blue','object','decide','surface','deep','moon','island','foot','yet','busy','test','record','boat','common','gold','possible','plane','age','dry','wonder','laugh','thousand','ago','ran','check','game','shape','yes','hot','miss','brought','heat','snow','bed','bring','sit','perhaps','fill','east','weight','language','among');

$F_PATH=$TG_DIR.'/PRIVATE_PROCESS/SCI/ANNOT/GENE';
function checkMatch(&$TEXT,&$query,$with_case=false)
{
	global $AVOID;
	//echo "A\n";
	if (in_array(strtolower($query),$AVOID))return false;
	$CHARS=array('[','\'','"','(',' ','?','!',';',',','-','_',']',')','.',"\n","\t",'','>','<','/','&',chr(32),chr(13),chr(160),chr(10));
	if ($with_case) $pos=strpos($TEXT,$query);
	else $pos=stripos($TEXT,$query);
	if ($pos===false)return false;				
	$prev='';
	if ($pos-1>0)$prev=substr($TEXT,$pos-1,1);
	$next='';
	if ($pos+1!=strlen($TEXT))$next=substr($TEXT,$pos+strlen($query),1);
	
	if (in_array($prev,$CHARS) && in_array($next,$CHARS))return true;
	return false;
				
}
$MATCH=array();


$TITLE=html_entity_decode($RAW_INFO[0]['job_title']);

$TEXT= html_entity_decode($TEXT);

$fp=fopen($F_PATH,'r');
while(!feof($fp))	
{
	$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
	$tab=explode("\t",$line);
	$syns=explode("|",$tab[2]);
	$gene_id=$tab[0];
	$symbol=$tab[1];
	foreach ($syns as $s)
	{
		if (strlen($s)<=3)continue;
		if (checkMatch($TITLE,$s))
		{
			if (!isset($MATCH[$gene_id]))
			$MATCH[$gene_id]=array('MATCH'=>array($s),'TEXT'=>$symbol,'GeneID'=>$gene_id,'SOURCE'=>'Title');
			else if (!in_array($s,$MATCH[$gene_id]['MATCH']))$MATCH[$gene_id]['MATCH'][]=$s;
		}
		if (checkMatch($TEXT,$s))
		{
			if (!isset($MATCH[$gene_id]))
			$MATCH[$gene_id]=array('MATCH'=>array($s),'TEXT'=>$symbol,'GeneID'=>$gene_id,'SOURCE'=>'Text');
			else if (!in_array($s,$MATCH[$gene_id]['MATCH']))$MATCH[$gene_id]['MATCH'][]=$s;
		}
	}
}	
fclose($fp);
print_r($MATCH);
			if (isset($INPUT_DATA['HASH']))
			{
				
				$HASH=$INPUT_DATA['HASH'];
				pushAnnotToDb($HASH,$MATCH,$GLB_VAR['SCHEMA_PRIVATE']);
				pushAnnotToDb($HASH,$MATCH,$GLB_VAR['PUBLIC_SCHEMA']);
			}

else uploadWebJobDoc($MD5_HASH,'results.json','application/json',json_encode($MATCH),'search gene');

function pushAnnotToDb($HASH,&$MATCH,$SCHEMA)
{
	print_R($MATCH);
	$res=runQuery("SELECT news_id FROM ".$SCHEMA.".news where news_hash = '".$HASH."'");
	//print_R($res);
	if (count($res)==0) return false;
	$news_id=$res[0]['news_id'];
	//echo $news_id."\n";
	
	runQueryNoRes("DELETE FROM " . $SCHEMA . ".news_gn_map WHERE news_id = ".$news_id);
	
	foreach ($MATCH as $C_NAME=>$C_INFO)
	{
		//if ($C_INFO['CO']==1)continue;

		
		$q = "INSERT INTO " . $SCHEMA . ".news_gn_map  VALUES (nextval('" . $SCHEMA . ".news_gn_map_sq')," . $news_id . ",(SELECT gn_entry_id FROM gn_entry where gene_id = ".$C_NAME."), 'T')";
		echo $q."\n";
		if (!runQueryNoRes($q)) {
			failedWebJob($MD5_HASH,'Unable to push drug annotation');
		}
                    

		
	}
	return true;
}


$STATUS_INFO['STATUS']='Success';

$STATUS_INFO['LOG'][]=array('Job successfully finished',date("F j, Y, g:i a"));


runQueryNoRes("Update web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."', time_end=CURRENT_TIMESTAMP WHERE md5id = '".$MD5_HASH."'");


?>
