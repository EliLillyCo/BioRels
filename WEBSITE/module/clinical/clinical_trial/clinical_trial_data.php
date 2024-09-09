<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

//try{
    $NCT_ID = $USER_INPUT['PAGE']['VALUE'];
        $MODULE_DATA= getClinicalTrialInfo($NCT_ID);
       
// }catch (Exception $e)
// {
//     $MODULE_DATA['ERROR']='Unable to retrieve information';
// }
//echo '<pre>';print_r($MODULE_DATA);exit;
?>
   