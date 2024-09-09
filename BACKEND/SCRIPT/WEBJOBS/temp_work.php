<?php

ini_set('memory_limit','10000M');
/**
 
 PURPOSE:     You can use this script to test without worrying about jobs and queues. 
			  This script is not a job, it is a simple script that you can run from the command line.
			  It is useful for testing and debugging.
 
*/



error_reporting(E_ALL);
$JOB_NAME='web_job';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');


?>

