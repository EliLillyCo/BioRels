<?php
# FILE : global.php
#OWNER: 	DESAPHY JEremy
#DATE:		05/30/2019
#PURPOSE:	SET global variables


/// This array contains the jobs and the requirements for each job
$GLB_TREE=array();

/// This array contains the timestamp info
$GLB_TIME=array();

/// This array contains the hierarchy level to run the scripts
$GLB_TREE_LEVEL=array();

/// Contains various global directories and files
$GLB_VAR=array('GENOME'=>array(),'PROTEOME'=>array());
$tmp_db_schema=getenv('DB_SCHEMA');
if ($tmp_db_schema===false) die('No DB_SCHEMA set. Did you forget to source setenv file?');
$GLB_VAR['DB_SCHEMA']=$tmp_db_schema;
$tmp_workdir=getenv('TG_DIR');
if ($tmp_workdir===false) die('No TG_DIR set. Did you forget to source setenv file?');
$GLB_VAR['WORKDIR']=$tmp_workdir;

$MAIL_COMMENTS=array();

/// COntains list of Jobs IDs and their qsub ID
$GLB_RUN_JOBS=array();


/// I fweb jobs are managed from this system, they will be listed here:
$GLB_RUN_WEBJOBS=array();

$SOURCE_LIST=array();
?>