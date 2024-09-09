<?php


if (!defined("BIORELS")) header("Location:/");

if (!isset($USER_INPUT['PARAMS'])) throw new Exception("Parameter required");
if (!is_numeric($USER_INPUT['PARAMS'][0])) throw new Exception("Parameter must be a number");
changeValue("tissue_validate",'TAG',$USER_INPUT['PARAMS'][0]);
if (isset($USER_INPUT['PARAMS'][1]) && $USER_INPUT['PARAMS'][1]=='ALL')
	changeValue("tissue_validate",'HUMAN_FILTER','false');
else changeValue("tissue_validate",'HUMAN_FILTER','true');

?>