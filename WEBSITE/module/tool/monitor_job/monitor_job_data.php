<?php

ini_set('memory_limit','2000M');
if (!defined("BIORELS")) header("Location:/");


$MD5_HASH=$USER_INPUT['PAGE']['VALUE'];

$MODULE_DATA['INFO']=runQuery("SELECT * FROM web_job where md5id = '".$MD5_HASH."'")[0];
$MODULE_DATA['INFO']['JOB_STATUS']=json_decode($MODULE_DATA['INFO']['JOB_STATUS'],true);
if ($MODULE_DATA['INFO']['JOB_STATUS']==null) $MODULE_DATA['INFO']['JOB_STATUS']=array('STATUS'=>'Submitted','LOG'=>array());
$MODULE_DATA['INFO']['PARAMS']=json_decode($MODULE_DATA['INFO']['PARAMS'],true);

?>