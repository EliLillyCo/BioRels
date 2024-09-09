<?php

if (!defined("BIORELS")) {
    header("Location:/");
}



changeValue("stacked_barchart",'TAG',$MODULE_DATA['TAG']);
changeValue("stacked_barchart",'WIDTH',$MODULE_DATA['WIDTH']);
changeValue("stacked_barchart",'ID',$MODULE_DATA['ID']);
changeValue("stacked_barchart",'DATA',$MODULE_DATA['DATA']);
if (!isset($MODULE_DATA['PARENT']))removeBlock("stacked_barchart",'W_PARENT');
else changeValue("stacked_barchart","PARENT",$MODULE_DATA['PARENT']);
$MAX=0;
echo "<pre>";
$lines=explode("\n",$MODULE_DATA['DATA']);
foreach ($lines as $C=>$D)
{
    if ($C==0)continue;
  $tab=explode(",",$D);
  $SUM=0;
  foreach ($tab as $K=>$v)
  {
    if ($K==0)continue;
   $SUM+=$v;
   
  }
  if ($SUM>$MAX)$MAX=$SUM;
}
//echo $MAX."\n";
$MAX=ceil($MAX/5*6);
//echo $MAX;exit;
changeValue("stacked_barchart",'MAX_VALUE',$MAX);
?>