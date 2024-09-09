<?php

if (!defined("BIORELS")) header("Location:/");
if (!isset($USER_INPUT['PARAMS'])) throw new Exception("Parameter required");
if (!isset($USER_INPUT['PARAMS'][0])) throw new Exception("Parameter must be provided");
changeValue("company_validate",'TAG',$USER_INPUT['PARAMS'][0]);


?>