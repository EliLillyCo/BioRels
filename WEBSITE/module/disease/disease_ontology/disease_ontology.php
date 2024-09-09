<?php

if (!defined("BIORELS")) header("Location:/");


function processATC(&$LIST,$CURR_ATC,$LEVEL,&$STR,$IS_PARENT,$SHOW=true)
{
	global $USER_INPUT;

	$CODE=$CURR_ATC;
	$TITLE=$LIST[$CURR_ATC]['TITLE'];
	if ($IS_PARENT && $TITLE=='disease')$SHOW=true;
	
	if ($SHOW)
	{
		$STR.='<div class="onto_entry"><div style="position:relative">';
		for ($i=0;$i<$LEVEL;$i++)
		{
			$STR.='<span style="position:absolute;float:left; left:'.($i*10).'px" class="onto_bar">|</span>';
		}
		$STR.='<span style="position:absolute;float:left; left:'.($LEVEL*10).'px"><span class="onto_bar">|</span><span class="onto_dash">-</span></span>';;
		$STR.='</div><div style="padding-left:'.($LEVEL*20+10).'px"><a href="/DISEASE/'.$CODE.'" ';
		if ($USER_INPUT['PORTAL']['DATA']['DISEASE_TAG']==$CODE) $STR.=' style="font-weight:bold;color:#196F91;" ';
		$STR.='>'.$TITLE.'</a></div></div>';
	}
	else $LEVEL-=1;
	if (!isset($LIST[$CURR_ATC]['CHILD']))return;
	sort($LIST[$CURR_ATC]['CHILD']);
	foreach ($LIST[$CURR_ATC]['CHILD'] as &$C)
	{
		processATC($LIST,$C,$LEVEL+1,$STR,$IS_PARENT,$SHOW);
	}

}




$STR='';
	foreach ($MODULE_DATA['PARENT']['ROOT'] as $R)
	{
		$STR.='<div style="width:max-content">';
		$STR.='<table class="table">';
		processATC($MODULE_DATA['PARENT']['ENTRY'],$R,0,$STR,true,false);
		$STR.='</table></div>';
	}
	changeValue("disease_ontology",'PARENT',$STR);



$STR='';
foreach ($MODULE_DATA['CHILD']['ROOT'] as $R)
{
	$STR.='<div  style="width:max-content">';
	$STR.='<table class="table">';
	processATC($MODULE_DATA['CHILD']['ENTRY'],$R,0,$STR,false,true);
	$STR.='</table></div>';
}
changeValue("disease_ontology",'CHILD',$STR);

?>