<?php

if (!defined("BIORELS")) {
    header("Location:/");
}


function checkData($TMP)
{
    if (!isset($TMP['name'])) throw new Exception('No name provided');
    if (!isset($TMP['children']) && !isset($TMP['value'])) throw new Exception('No children or count provided');
    if (isset($TMP['children']) && isset($TMP['value'])) throw new Exception('Can\' have children and count provided');
    if (isset($TMP['children']))
    {
    foreach ($TMP['children'] as $child)   checkData($child);
    }
    
}


try{
$MODULE_DATA=array('TAG'=>substr(md5(microtime_float()),0,7),'WIDTH'=>600,'ID'=>substr(md5(microtime_float()),0,7),'DATA'=>array());
for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
{

//echo $I."<br/>";echo $USER_INPUT['PARAMS'][$I].'<br/>';
//print_r($NEWS);

switch ($USER_INPUT['PARAMS'][$I])
{
    case 'ID':
        $I++;
        $MODULE_DATA['ID']=$USER_INPUT['PARAMS'][$I];
        
        break;
        case 'TAG':
            $I++;
            $MODULE_DATA['TAG']=$USER_INPUT['PARAMS'][$I];
            
            break;
    case 'WIDTH':
        $I++;
        $MODULE_DATA['WIDTH']=$USER_INPUT['PARAMS'][$I];
        
        break;
    case 'DATA':
        $I++;
        $TMP=null;
        $TMP=json_decode($USER_INPUT['PARAMS'][$I],true);
        if ($TMP==null)throw new Exception('DATA is not in json format');
        checkData($TMP);
        $MODULE_DATA['DATA']=$TMP;
        break;

    }
}
if ($MODULE_DATA['DATA']==array()) throw new Exception('No data provided');

}catch(Exception $e)
{
    $MODULE_DATA['ERROR']=$e;
}


?>