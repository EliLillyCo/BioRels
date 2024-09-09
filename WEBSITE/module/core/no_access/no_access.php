<?php

if (!defined("BIORELS")) header("Location:/");

if (!isset($USER_INPUT['PARAMS'])||$USER_INPUT['PARAMS']==array())
changeValue("no_access",'MESSAGE',"You don't have access to this feature.");
?>