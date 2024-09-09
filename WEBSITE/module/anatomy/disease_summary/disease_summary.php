<?php
$KEY=array_keys($MODULE_DATA['ENTRIES'])[0];
$ENTRY=&$MODULE_DATA['ENTRIES'][$KEY];
$STR='';
$STR_N='<div style="display:flex">';
$DR_I=array(1=>'I',2=>'II',3=>'III',4=>'IV');
$MAX_LEV=0;
foreach ($MODULE_DATA['TRIALS'] as $K=>$T)if ($T!=0)$MAX_LEV=max($K,$MAX_LEV);
for ($I=1;$I<=4;++$I)
{
	$STR_N.='<div class="w3-col s3_1"><div class="text-circle blk_font" style="margin:0 auto">'.(isset($MODULE_DATA['TRIALS'][$I])?$MODULE_DATA['TRIALS'][$I]:0).'</div></div>';
	$STR.='<div  class="chevron w3-col s3_1" style="';
	if ($I>$MAX_LEV)$STR.='background-color:grey';
	$STR.='">'.$DR_I[$I].'</div>';
}
changeValue("DISEASE_SUMMARY","TRIALS",$STR_N.'</div>'.$STR);
changeValue("DISEASE_SUMMARY","LABEL",$ENTRY['DISEASE_NAME']);
changeValue("DISEASE_SUMMARY","DEFINITION",$ENTRY['DISEASE_DEFINITION']);

changeValue("DISEASE_SUMMARY","LINKS",'<a href="/DISEASE/'.$ENTRY['DISEASE_NAME'].'">Go to '.$ENTRY['DISEASE_NAME'].'</a>');


?>