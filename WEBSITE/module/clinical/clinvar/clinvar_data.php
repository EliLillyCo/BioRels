<?php
if (!defined("BIORELS")) header("Location:/");


$CLINV_ID=$USER_INPUT['PAGE']['VALUE'];
if (!preg_match("/[0-9]{2,11}/",$CLINV_ID))throw new Exception("Wrong format for variant ".$CLINV_ID,ERR_TGT_USR);

$MODULE_DATA=array();
$MODULE_DATA=getClinvarData($CLINV_ID);

?>