<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$MD5=substr(md5(microtime_float().rand(0,10000)),0,5);
changeValue("monitor_admin_info",'DT',$MD5);
$DT=array('name'=>'Root ','children'=>array());
changeValue("monitor_admin_info",'DAYS',$DAYS);
foreach ($MODULE_DATA['JOB_LEVELS'] as $K=>&$L)
{
    
    $CH=array();
    ksort($L);
    foreach ($L as $C=>$CO) $CH[]=array('name'=>$C,'value'=>$CO);
    $DT['children'][]=array('name'=>$K,'children'=>$CH);

}

$USER_INPUT['PARAMS']=array('DATA',json_encode($DT));

changeValue("monitor_admin_info",'SUNBURST',loadHTMLAndRemove('SUNBURST'));

$STR='';
foreach ($MODULE_DATA['HIGH_USER'] as $CO=>$LT)
foreach ($LT as $U)
{
    $UI=&$MODULE_DATA['USERS_STATS'][$U]['INFO'];
    $STR.='<tr><td>'.$CO.'</td><td>'.$UI['LAST_NAME'].'</td><td>'.$UI['FIRST_NAME'].'</td><td>'.$UI['JOB_LEVEL'].'</td><td>'.$UI['JOB_FAMILY'].'</td><td>'.$MODULE_DATA['USERS_STATS'][$U]['DISTINCT_DAYS'].'</td></tr>';
}
changeValue("monitor_admin_info",'USERS',$STR);


$DT=array();
ksort($MODULE_DATA['MONTHLY']);
foreach ( $MODULE_DATA['MONTHLY'] as $Y=>&$Y_F)
{
    ksort($Y_F);
foreach ($Y_F as $M=>$M_F)
    $DT[]=array('name'=>$Y.'/'.$M,'value'=>array_sum($M_F));
}   

$USER_INPUT['PARAMS']=array('DATA',json_encode($DT));
changeValue("monitor_admin_info",'MONTHLY',loadHTMLAndRemove("BARCHART"));


	$DL=array();
	foreach ($MODULE_DATA['USER_GROWTH'] as $Y=>$V)
	{
		$DL[]=array('name'=>$Y,'value'=>count($V));
	}

	$USER_INPUT['PARAMS']=array('DATA',json_encode($DL),'PARENT','user_growth_'.$DAYS);
	$STR=loadHTMLAndRemove("BARCHART");
    changeValue("monitor_admin_info",'USER_GROWTH',$STR);
?>