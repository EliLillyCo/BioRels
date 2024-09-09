<?php


if (!defined("BIORELS")) header("Location:/");




$LOG='';
foreach ($MODULE_DATA['INFO']['JOB_STATUS']['LOG'] as $LOG_V)
{
$LOG.='<tr><td style="width:175px">'.$LOG_V[1].'</td><td>'.$LOG_V[0].'</td></tr>';
}
changeValue("monitor_job",'LOG',$LOG);

?>