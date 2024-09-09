<?php
if (!defined("BIORELS")) header("Location:/");


$AUTHOR=$USER_INPUT['PAGE']['VALUE'];

if (!is_numeric($AUTHOR))throw new Exception("Wrong format,  publication author ID is expected to be numeric: ".$AUTHOR,ERR_TGT_USR);

$MODULE_DATA=array();
$MODULE_DATA=loadPubliAuthorData($AUTHOR);

?>