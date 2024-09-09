<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$DAYS=$USER_INPUT['PARAMS'][0];


$TMP=runQuery("SELECT web_user_id, 
EXTRACT('month' FROM date_accessed) mon, 
EXTRACT('year' FROM date_accessed) y, 
count(*) co
 
FROM web_user_stat
WHERE date_accessed >= (current_date - interval '".$DAYS."' day)
 group by web_user_id, y, mon");

$MODULE_DATA['USERS_STATS']=array();
$MODULE_DATA['MONTHLY']=array();
$TMPT=array();
foreach ($TMP as $R)
{
    if (!isset($TMPT[$R['WEB_USER_ID']]))$TMPT[$R['WEB_USER_ID']]=0;
    $TMPT[$R['WEB_USER_ID']]+=$R['CO'];
    $MODULE_DATA['MONTHLY'][$R['Y']][$R['MON']][$R['WEB_USER_ID']]=$R['CO'];
    $MODULE_DATA['USERS_STATS'][$R['WEB_USER_ID']]['USAGE'][$R['Y']][$R['MON']]=$R['CO'];
}  
$MODULE_DATA['HIGH_USER']=array();
foreach ($TMPT as $RID=>$CO)$MODULE_DATA['HIGH_USER'][$CO][]=$RID;
krsort($MODULE_DATA['HIGH_USER']);
if ($MODULE_DATA['USERS_STATS']!=array())
{
    $res=runQuery("SELECT * FROM web_user where web_user_id IN (".implode(',',array_keys($MODULE_DATA['USERS_STATS'])).')');
        foreach ($res as $line)
        $MODULE_DATA['USERS_STATS'][$line['WEB_USER_ID']]['INFO']=$line;
   

}


$res=runQuery("SELECT count(DISTINCT date_trunc('day',date_accessed)) co, web_user_id FROM web_user_stat 
WHERE date_accessed >= (current_date - interval '".$DAYS."' day)
group by web_user_Id");
$MODULE_DATA['DISTINCT_DAYS']=array();
$MODULE_DATA['DISTINCT_DAYS_STAT']=array();
foreach ($res as $line)
{
    if (!isset($MODULE_DATA['DISTINCT_DAYS_STAT'][$line['CO']]))$MODULE_DATA['DISTINCT_DAYS_STAT'][$line['CO']]=0;
    $MODULE_DATA['DISTINCT_DAYS_STAT'][$line['CO']]++;
    $MODULE_DATA['USERS_STATS'][$line['WEB_USER_ID']]['DISTINCT_DAYS']=$line['CO'];
}



$MODULE_DATA['JOB_FAMILY']=array();
$MODULE_DATA['JOB_LEVELS']=array();
foreach ($MODULE_DATA['USERS_STATS'] as &$USER)
{
    if (!isset($MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']]))$MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']]=array();
    if (!isset($MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']][$USER['INFO']['JOB_FAMILY']]))$MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']][$USER['INFO']['JOB_FAMILY']]=1;
    else $MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']][$USER['INFO']['JOB_FAMILY']]++;
    if (!isset($MODULE_DATA['JOB_LEVELS'][substr($USER['INFO']['JOB_LEVEL'],0,1)][substr($USER['INFO']['JOB_LEVEL'],1)]))
    $MODULE_DATA['JOB_LEVELS'][substr($USER['INFO']['JOB_LEVEL'],0,1)][substr($USER['INFO']['JOB_LEVEL'],1)]=1;
    else $MODULE_DATA['JOB_LEVELS'][substr($USER['INFO']['JOB_LEVEL'],0,1)][substr($USER['INFO']['JOB_LEVEL'],1)]++;
}

$FIRST_YEAR=min(array_keys($MODULE_DATA['MONTHLY']));
$MODULE_DATA['USER_GROWTH']=array();
$MODULE_DATA['MONTHLY_GROUP']=array();
$TMP_LIST=array();
for ($I=$FIRST_YEAR;$I<=date('Y');++$I)
for ($J=1;$J<=12;++$J)
{
    if (!isset($MODULE_DATA['MONTHLY'][$I][$J]))continue;
    foreach($MODULE_DATA['MONTHLY'][$I][$J] as $U=>&$CO)
    {
        if (isset($TMP_LIST[$U]))continue;
        $TMP_LIST[$U]=true;
        $MODULE_DATA['USER_GROWTH'][$J.'/'.$I][]=$U;
        $MODULE_DATA['MONTHLY_GROUP'][$J.'/'.$I][$USER['INFO']['JOB_LEVEL']][]=$CO;
    }

}



$res=runQuery("SELECT lly_user_id, job_name, time_start,time_end,job_cluster_id,
  EXTRACT('month' FROM time_start) mon, EXTRACT('year' FROM time_start) y 
  FROM web_job where time_start >= (current_date - interval '".$DAYS."' day) 
  ");
$MODULE_DATA['WEB_JOB']['TOT']=count($res);
foreach ($res as $line)
{
    if ($line['TIME_END']!='')
    $MODULE_DATA['WEB_JOB'][$line['JOB_NAME']]['TIME'][]=strtotime($line['TIME_END'])-strtotime($line['TIME_START']);
    if (!isset($MODULE_DATA['WEB_JOB'][$line['JOB_NAME']][$line['LLY_USER_ID']])) $MODULE_DATA['WEB_JOB'][$line['JOB_NAME']]['USER'][$line['LLY_USER_ID']]=0;
    $MODULE_DATA['WEB_JOB'][$line['JOB_NAME']]['USER'][$line['LLY_USER_ID']]++;
    if (!isset($MODULE_DATA['WEB_JOB'][$line['JOB_NAME']]['DATE'][$line['Y']][$line['MON']]))$MODULE_DATA['WEB_JOB'][$line['JOB_NAME']]['DATE'][$line['Y']][$line['MON']]=0;
    $MODULE_DATA['WEB_JOB'][$line['JOB_NAME']]['DATE'][$line['Y']][$line['MON']]++;
}



?>