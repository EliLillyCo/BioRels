<?php

if (!defined("BIORELS")) {
    header("Location:/");
}
if (!isset($USER_INPUT['PARAMS'][0]))return;
$NEWS_HASH=$USER_INPUT['PARAMS'][0];


$MODULE_DATA['ACCESS']=runQuery("SELECT web_user_id, 
EXTRACT('month' FROM date_accessed) mon, 
EXTRACT('year' FROM date_accessed) y,
count(*) co
FROM web_user_stat 
WHERE page LIKE 'CONTENT%".$NEWS_HASH."%'
group by web_user_id, mon, y

");


$MODULE_DATA['USERS']=array();
foreach ($MODULE_DATA['ACCESS'] as $line)
$MODULE_DATA['USERS'][$line['WEB_USER_ID']]=array();

if ($MODULE_DATA['USERS']!=array())
{
    $res=runQuery("SELECT * FROM web_user where web_user_id IN (".implode(',',array_keys($MODULE_DATA['USERS'])).')');
        foreach ($res as $line)
        $MODULE_DATA['USERS'][$line['WEB_USER_ID']]['INFO']=$line;
   
    $res=runQuery("SELECT * FROM org_chart oc, org_chart op, web_user wu
    where op.org_chart_left <= oc.org_chart_left
    AND  op.org_chart_right >= oc.org_chart_right
    AND op.web_user_id = wu.web_user_id
    AND oc.web_user_id IN (".implode(',',array_keys($MODULE_DATA['USERS']))." ) ORDER BY OP.ORG_CHART_LEVEL ASC");
    $ORGS=array();
    foreach ($res as $line)
    {
        if (!isset($ORGS['LEVEL'][$line['WEB_USER_ID']]))$ORGS['LEVEL'][$line['WEB_USER_ID']]=$line;
        if (!isset($ORGS['LEVEL'][$line['ORG_CHART_LEVEL']-1]))continue;
        foreach ($ORGS['LEVEL'][$line['ORG_CHART_LEVEL']-1] as $K=>$V)
        {
            if ($line['ORG_CHART_LEFT']>$V['ORG_CHART_LEFT'] && $line['ORG_CHART_RIGHT']<$V['ORG_CHART_RIGHT'])
            {
                $ORGS['LEVEL'][$line['ORG_CHART_LEVEL']-1][$K]['CHILDREN'][$line['WEB_USER_ID']]=true;
                break;
            }
        }
    }
    

}

// exit;

$MODULE_DATA['ORG']=array();
$MAX_LEVEL=0;

$LIST_WEB_USER=array();
//foreach ($MODULE_DATA['ACCESS'] as $T)$LIST_WEB_USER[$T['WEB_USER_ID']]=
echo '<pre>';
foreach ($ORGS['LEVEL'] as $T)
{
    $MODULE_DATA['ORG'][$T['ORG_CHART_LEVEL']][$T['WEB_USER_ID']]=$T;
    $MAX_LEVEL=max($MAX_LEVEL,$T['ORG_CHART_LEVEL']);
}
for ($I=$MAX_LEVEL;$I>1;--$I)
{
    foreach ($MODULE_DATA['ORG'][$I] as $W_ID=> &$P)
    {
       
        $P['PARENT']=array();
        if (!isset($P['USERS']))$P['USERS']=0;
        if (isset($MODULE_DATA['USERS'][$W_ID]))$P['USERS']++;
        if (!isset($MODULE_DATA['ORG'][$I-1]))continue;
        //echo $I."\t".$P['LAST_NAME']." ".$P['FIRST_NAME']."\t".$P['USERS']."\n";
        
        foreach ($MODULE_DATA['ORG'][$I-1] as $PARENT_ID=>&$T)
        {
            if ($T['ORG_CHART_LEFT']<$P['ORG_CHART_LEFT'] && $T['ORG_CHART_RIGHT']>$P['ORG_CHART_RIGHT'])
            {

                $P['PARENT'][]=$PARENT_ID;
                $T['CHILD'][]=$W_ID;
                if (!isset($T['USERS']))$T['USERS']=0;
                
                if ($P['USERS']>0)$T['USERS']+=$P['USERS'];
               // echo "\tPARENT:".$T['LAST_NAME']." ".$T['FIRST_NAME']."\t".$T['USERS']."\n";
                
            }
        }
    }
   // exit;
}
$ORGS=array();unset($ORGS);






$MODULE_DATA['JOB_FAMILY']=array();
$MODULE_DATA['JOB_LEVELS']=array();
foreach ($MODULE_DATA['USERS'] as &$USER)
{
    if (!isset($MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']]))$MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']]=array();
    if (!isset($MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']][$USER['INFO']['JOB_FAMILY']]))$MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']][$USER['INFO']['JOB_FAMILY']]=1;
    else $MODULE_DATA['JOB_FAMILY'][$USER['INFO']['JOB_FAMILY_GROUP']][$USER['INFO']['JOB_FAMILY']]++;
    if (!isset($MODULE_DATA['JOB_LEVELS'][substr($USER['INFO']['JOB_LEVEL'],0,1)][substr($USER['INFO']['JOB_LEVEL'],1)]))
    $MODULE_DATA['JOB_LEVELS'][substr($USER['INFO']['JOB_LEVEL'],0,1)][substr($USER['INFO']['JOB_LEVEL'],1)]=1;
    else $MODULE_DATA['JOB_LEVELS'][substr($USER['INFO']['JOB_LEVEL'],0,1)][substr($USER['INFO']['JOB_LEVEL'],1)]++;
}


?>