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
if ($RAW_INFO==array()){die("no job");exit;}
cleanWebJobDoc($MD5_HASH);
date_default_timezone_set($GLB_VAR['TIMEZONE']);
$STATUS_INFO=array('STATUS'=>'Running',
					'LOG'=>array());
updateWebJobStatus($MD5_HASH,'Initiate search disease');

$AVOID=array('the','of','to','and','a','in','is','it','you','that','arm','ph2','gov','jan','feb','mar','avr','may','jun','jul','aug','sep','oct','nov','dec','he','was','for','on','are','with','as','I','his','they','be','at','one','have','this','from','or','had','by','hot','but','some','what','there','we','can','out','other','were','all','your','when','up','use','word','how','said','an','each','she','which','do','their','time','if','will','way','about','many','then','them','would','write','like','so','these','her','long','make','thing','see','him','two','has','look','more','day','could','go','come','did','my','sound','no','most','number','who','over','know','water','than','call','first','people','may','down','side','been','now','find','any','new','work','part','take','get','mad','plan','march','mar','get','place','made','live','where','after','back','little','only','round','man','year','came','show','every','good','me','give','our','under','name','very','through','just','form','much','g
reat','think','say','help','low','line','before','turn','cause','same','mean','differ','move','right','boy','old','too','does','tell','sentence','set','three','want','air','well','also','play','small','end','put','home','read','hand','port','large','spell','add','even','land','here','must','big','high','such','follow','act','why','ask','men','change','went','light','kind','off','need','house','picture','try','us','again','animal','point','mother','world','near','build','self','earth','father','head','stand','own','page','should','country','found','answer','school','grow','study','still','learn','plant','cover','food','sun','four','thought','let','keep','eye','never','last','door','between','city','tree','cross','since','hard','start','might','story','saw','far','sea','draw','left','late','run','don’t','while','press','close','night','real','life','few','stop','open','seem','together','next','white','children','begin','got','walk','example','ease','paper','often','always','music','tho
se','both','mark','book','letter','until','mile','river','car','feet','care','second','group','carry','took','rain','eat','room','friend','began','idea','fish','mountain','north','once','base','hear','horse','cut','sure','watch','color','face','wood','main','enough','plain','girl','usual','young','ready','above','ever','red','list','though','feel','talk','bird','soon','body','dog','family','direct','pose','leave','song','measure','state','product','black','short','numeral','class','wind','question','happen','complete','ship','area','half','rock','order','fire','south','problem','piece','told','knew','pass','farm','top','whole','king','size','heard','best','hour','better','true','during','hundred','am','remember','step','early','hold','west','ground','interest','reach','fast','five','sing','listen','six','table','travel','less','morning','ten','simple','several','vowel','toward','war','lay','against','pattern','slow','center','love','person','money','serve','appear','road','map','scienc
e','rule','govern','pull','cold','notice','voice','fall','power','town','fine','certain','fly','unit','lead','cry','dark','machine','note','wait','plan','figure','star','box','noun','field','rest','correct','able','pound','done','beauty','drive','stood','contain','front','teach','week','final','gave','green','oh','quick','develop','sleep','warm','free','minute','strong','special','mind','behind','clear','tail','produce','fact','street','inch','lot','nothing','course','stay','wheel','full','force','blue','object','decide','surface','deep','moon','island','foot','yet','busy','test','record','boat','common','gold','possible','plane','age','dry','wonder','laugh','thousand','ago','ran','check','game','shape','yes','hot','miss','brought','heat','snow','bed','bring','sit','perhaps','fill','east','weight','language','among');

$INPUT_DATA=json_decode($RAW_INFO[0]['params'],true);
if ($INPUT_DATA==null)    failedWebJob($MD5_HASH,'Unable to interpret parameters');

if (!isset($INPUT_DATA['QUERY']))	failedWebJob($MD5_HASH,'No query information found');
$TEXT=&$INPUT_DATA['QUERY'];
updateWebJobStatus($MD5_HASH,'Searching disease');
$TMP=array();

function checkMatch(&$TEXT,&$query,$with_case=false)
{
	global $AVOID;
	//echo "A\n";
	if (in_array(strtolower($query),$AVOID))return false;
	$CHARS=array('[','\'','"','(',' ','?','!',';',',','-','_',']',')','.',"\n","\t",'','>','<','/','&',chr(32),chr(13),chr(160),chr(10));
	if ($with_case) $pos=strpos($TEXT,$query);
	else $pos=stripos($TEXT,$query);
	//echo '|'.$pos.'|';
	if ($pos===false)return false;				
	$prev='';
	if ($pos-1>0)$prev=substr($TEXT,$pos-1,1);
	$next='';
	if ($pos+1!=strlen($TEXT))$next=substr($TEXT,$pos+strlen($query),1);
	//echo '|'.$prev.'|'.$next.'|'."\n";
	if (in_array($prev,$CHARS) && in_array($next,$CHARS))return true;
	return false;
				
}
runAnalysis($TEXT,$RAW_INFO[0]['job_title'],$TMP,'Text');

function runAnalysis($TEXT,$TITLE,&$TMP,$SOURCE)
{

$TEXT= html_entity_decode($TEXT);
global $TG_DIR;
$fp=fopen($TG_DIR.'/PRIVATE_PROCESS/SCI/ANNOT/DISEASE','r');
if ($fp){
	echo "IN\n";;
while(!feof($fp))
{
	$line=stream_Get_line($fp,100000,"\n");if ($line=='')continue;
	$tab=explode("\t",$line);
	//if ($tab[0]!='MONDO_0004703')continue;
	if (count($tab)!=4)continue;
	
	$tab[2]=explode("|",$tab[2]);
	$tab[2][]=$tab[1];
	sort($tab[2]);
	$tab[2]=array_unique($tab[2]);
	
	foreach ($tab[2] as $syn)
	{
		if (strlen($syn)<=4)continue;
		//echo $syn."\n";
		if (checkMatch($TEXT,$syn))
		{
			if (!isset($TMP[$tab[0]]))
				$TMP[$tab[0]]=array('MATCH'=>array($syn),'TEXT'=>$tab[1],'SOURCE'=>'Text');
				else if (!in_array($syn,$TMP[$tab[0]]['MATCH']))$TMP[$tab[0]]['MATCH'][]=$syn;
	
		}
		if (checkMatch($TITLE,$syn))
		{
			if (!isset($TMP[$tab[0]]))
				$TMP[$tab[0]]=array('MATCH'=>array($syn),'TEXT'=>$tab[1],'SOURCE'=>'Title');
				else if (!in_array($syn,$TMP[$tab[0]]['MATCH']))$TMP[$tab[0]]['MATCH'][]=$syn;
	
		}
	}
}
fclose($fp);
}
}
			
         $MATCH=$TMP;
print_R($MATCH);

if (isset($INPUT_DATA['HASH']))
			{
			//	print_R($MATCH);
				$HASH=$INPUT_DATA['HASH'];
				pushDiseaseToDb($HASH,$MATCH,$GLB_VAR['SCHEMA_PRIVATE']);
				pushDiseaseToDb($HASH,$MATCH,$GLB_VAR['PUBLIC_SCHEMA']);
			}

else uploadWebJobDoc($MD5_HASH,'results.json','application/json',json_encode($MATCH),'search disease');

function pushDiseaseToDb($HASH,&$MATCH,$SCHEMA)
{
	$res=runQuery("SELECT news_id FROM ".$SCHEMA.".news where news_hash = '".$HASH."'");
	//print_R($res);
	if (count($res)==0) return false;
	$news_id=$res[0]['news_id'];
	//echo $news_id."\n";
	
	runQueryNoRes("DELETE FROM " . $SCHEMA . ".news_disease_map WHERE news_id = ".$news_id);
	
	foreach ($MATCH as $C_NAME=>$C_INFO)
	{
		$res=runQuery ("SELECT DISTINCT de.disease_entry_id from DISEASE_ENTRY de where de.disease_tag = '" . $C_NAME . "'");
                  foreach ($res as $line)
                    {
                    $q = "INSERT INTO " . $SCHEMA . ".news_disease_map VALUES (nextval('" . $SCHEMA . ".news_disease_map_sq')," . $news_id . ",".$line['disease_entry_id'].", 'T')";
                    if (!runQueryNoRes($q)) {
                        failedWebJob($MD5_HASH,'Unable to insert disease id ' . $C_NAME);
                        
                    }
				}

		
	}
	return true;
}

$STATUS_INFO['STATUS']='Success';

$STATUS_INFO['LOG'][]=array('Job successfully finished',date("F j, Y, g:i a"));


runQueryNoRes("Update web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."', time_end=CURRENT_TIMESTAMP WHERE md5id = '".$MD5_HASH."'");


?>
