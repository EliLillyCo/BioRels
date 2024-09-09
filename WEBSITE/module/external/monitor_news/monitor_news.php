<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$MD5=substr(md5(microtime_float().rand(0,10000)),0,5);
changeValue("monitor_news",'DT',$MD5);
$DT=array('name'=>'Root','children'=>array());
changeValue("monitor_news",'DAYS',$DAYS);
foreach ($MODULE_DATA['JOB_LEVELS'] as $K=>&$L)
{
    
    $CH=array();
    ksort($L);
    foreach ($L as $C=>$CO) $CH[]=array('name'=>$C,'value'=>$CO);
    $DT['children'][]=array('name'=>$K,'children'=>$CH);

}

$USER_INPUT['PARAMS']=array('DATA',json_encode($DT));

changeValue("monitor_news",'SUNBURST',loadHTMLAndRemove('SUNBURST'));

$STR='';


foreach ($MODULE_DATA['USERS'] as $D)
{
    $UI=$D['INFO'];
    $STR.='<tr><td>'.$UI['LAST_NAME'].'</td>
<td>'.$UI['FIRST_NAME'].'</td>
<td>'.$UI['JOB_LEVEL'].'</td>
<td>'.$UI['JOB_FAMILY_GROUP'].'</td>
<td>'.$UI['JOB_FAMILY'].'</td>
<td>'.$UI['COUNTRY'].'</td>
<td>'.$UI['BUSINESS_TITLE'].'</td>
</tr>';
}

changeValue("monitor_news",'USERS',$STR);


$STR='';
foreach ($MODULE_DATA['JOB_FAMILY'] as $JFG=>&$LIST)
foreach ($LIST as $JF=>$C)
{
    $STR.='<tr><td>'.$JFG.'</td><td>'.$JF.'</td><td>'.$C.'</td></tr>';

}
changeValue("monitor_news",'JOB_FAMILY',$STR);


$STR='';
foreach ($MODULE_DATA['JOB_LEVELS'] as $JFG=>&$LIST)
foreach ($LIST as $JF=>$C)
{
    $STR.='<tr><td>'.$JFG.'</td><td>'.$JF.'</td><td>'.$C.'</td></tr>';

}
changeValue("monitor_news",'JOB_LEVEL',$STR);

$STR='';
foreach ($MODULE_DATA['ORG'][2] as $INFO)
{
    $STR.='<tr><td>'.$INFO['LAST_NAME'].'</td><td>'.$INFO['FIRST_NAME'].'</td><td>'.$INFO['BUSINESS_TITLE'].'</td><td>'.$INFO['USERS'].'</td></tr>';
}
changeValue("monitor_news",'ORG2',$STR);



$STR='';
foreach ($MODULE_DATA['ORG'][3] as $INFO)
{
    $STR.='<tr><td>'.$INFO['LAST_NAME'].'</td><td>'.$INFO['FIRST_NAME'].'</td><td>'.$INFO['BUSINESS_TITLE'].'</td><td>'.$INFO['USERS'].'</td></tr>';
}
changeValue("monitor_news",'ORG3',$STR);

?>