<?php

ini_set('memory_limit','2000M');
if (!defined("BIORELS")) header("Location:/");


$MD5_HASH=$USER_INPUT['PAGE']['VALUE'];

$MODULE_DATA['INFO']=runQuery("SELECT * FROM web_job where md5id = '".$MD5_HASH."'")[0];
$MODULE_DATA['INFO']['JOB_STATUS']=json_decode($MODULE_DATA['INFO']['JOB_STATUS'],true);
$MODULE_DATA['INFO']['PARAMS']=json_decode($MODULE_DATA['INFO']['PARAMS'],true);
$MODULE_DATA['INFO']['PARAMS']['GENE_INFO']=gene_portal_geneID($MODULE_DATA['INFO']['PARAMS']['GENE']);
if (!in_array('NO_FILE',$USER_INPUT['PARAMS']))
{
	
$MODULE_DATA['FILES']=runQuery("SELECT document_name,document_description,document_content 
FROM web_job_document where  web_job_id = ".$MODULE_DATA['INFO']['WEB_JOB_ID']);
foreach ($MODULE_DATA['FILES'] as &$F)
{
	$F['DOCUMENT_CONTENT']=json_decode(stream_get_contents($F['DOCUMENT_CONTENT']),true);
}
}
$MODULE_DATA['PRE_INPUT']=getGenomeInfo();
?>