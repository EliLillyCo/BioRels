<?php

if (!defined("BIORELS")) header("Location:/");
if (!isset($USER_INPUT['PARAMS'])) throw new Exception("Parameter required");
if (!isset($USER_INPUT['PARAMS'][0])) throw new Exception("Parameter must be provided");
changeValue("gene_validate",'TAG',$USER_INPUT['PARAMS'][0]);
if (isset($USER_INPUT['PARAMS'][1]) && $USER_INPUT['PARAMS'][1]=='ALL')
	changeValue("gene_validate",'HUMAN_FILTER','false');
else changeValue("gene_validate",'HUMAN_FILTER','true');

?>