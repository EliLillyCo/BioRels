<?php

$RULE_TYPE=$USER_INPUT['PAGE']['VALUE'];


$MATCH=checkRegex($RULE_TYPE,'REGEX:PUBLI_TOPIC');

if ($MATCH==array())throw new Exception("Wrong format for publication topic ".$RULE_TYPE,ERR_TGT_USR);
$time=microtime_float();
$MODULE_DATA['QUERY']=getPubliRule($MATCH[0]);

$MODULE_DATA['TIME']['RULE']=round(microtime_float()-$time,2);
if (count($MODULE_DATA['QUERY'])==0)throw new Exception("Unable to find ".$RULE_TYPE,ERR_TGT_USR);
$time=microtime_float();
$MODULE_DATA['STAT']=getCountPubliRule($MODULE_DATA['QUERY'][0]['PUBLI_RULE_ID']);
$MODULE_DATA['TIME']['STAT']=round(microtime_float()-$time,2);



?>