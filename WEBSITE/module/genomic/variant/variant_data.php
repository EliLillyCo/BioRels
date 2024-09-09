<?php
if (!defined("BIORELS")) header("Location:/");


$RSID=$USER_INPUT['PAGE']['VALUE'];

if (!preg_match("/[0-9]{2,11}/",$RSID))throw new Exception("Wrong format for mutation ".$RSID,ERR_TGT_USR);





try{
$MODULE_DATA=getMutationInfo($RSID);

}catch(Exception $e)
{
	print_r($e);
	$MODULE_DATA['ERROR']='Unable to retrieve results';
}



?>