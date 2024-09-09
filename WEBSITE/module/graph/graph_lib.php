<?php



function checkBarChartData($TMP)
{
    foreach ($TMP as $N=>$RECORD)
    {
        if (!isset($RECORD['name'])) throw new Exception('Record '.$N.' does not have a name');
        if (!isset($RECORD['value'])) throw new Exception('Record '.$N.' does not have a value');
    }
    
}


?>