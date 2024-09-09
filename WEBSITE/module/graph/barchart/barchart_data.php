<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

require_once('module/graph/graph_lib.php');

try{
 
  //  $USER_INPUT['PARAMS']=array('DATA','[{"name":"2023\/4","value":3270},{"name":"2023\/5","value":9278},{"name":"2023\/6","value":11882},{"name":"2023\/7","value":2306},{"name":"2023\/8","value":13040},{"name":"2023\/9","value":28}]');
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
        case 'COLOR':$MODULE_DATA['COLOR']=true;++$I;break;
        case 'TAG':
            $I++;
            $MODULE_DATA['TAG']=$USER_INPUT['PARAMS'][$I];
            
            break;
            case 'PARENT':
                $I++;
                $MODULE_DATA['PARENT']=$USER_INPUT['PARAMS'][$I];
                
                break;
    case 'WIDTH':
        $I++;
        $MODULE_DATA['WIDTH']=$USER_INPUT['PARAMS'][$I];
        
        break;
    case 'DATA':
        $I++;
        $TMP=null;
       $STR=html_entity_decode(str_replace("&quot;",'"',$USER_INPUT['PARAMS'][$I]));
     
        $TMP=json_decode($STR,true);
        if ($TMP==null)throw new Exception('DATA is not in json format');
        checkBarChartData($TMP);
        $MODULE_DATA['DATA']=$TMP;
        break;

    
   
    }
}

if ($MODULE_DATA['DATA']==array()) throw new Exception('No data provided');

}catch(Exception $e)
{
    echo $e->getMessage();
    exit;
    $MODULE_DATA['ERROR']=$e;
}

?>