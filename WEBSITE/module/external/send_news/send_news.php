<?php

if (!defined("BIORELS")) header("Location:./");


if ($USER['Access'][1]!=1)removeBlock("send_news","HAS_RED_ACCESS");
else removeBlock("send_news","HAS_GREEN_ACCESS");


if (isset($MODULE_DATA['ERROR']))
{
	changeValue("send_news","ERROR_MESSAGE",$MODULE_DATA['ERROR']);
	removeBlock("send_news","DEFAULT");
	return;
}else removeBlock("send_news","ACCESS_ERROR");


if (isset($MODULE_DATA['EMAIL_JOB']))
changeValue("send_news","ERR_MSG",'<div class="alert alert-info">Mail successfully put in queue: '.$MODULE_DATA['EMAIL_JOB'].'</div>');
else if (isset($MODULE_DATA['EMAIL_FAIL']))
{
	changeValue("send_news","ERR_MSG",'<div class="alert alert-error">'.$MODULE_DATA['ERROR'].'</div>');	
}


$STR='';

foreach ($GLB_CONFIG['GLOBAL']['EMAIL_GROUP'] as $GRPN=>$GRP_INFO)
{
	$STR.='<tr><td><input type="checkbox" name="email[]" value="'.$GRP_INFO['0'].'"></td><td>'.$GRP_INFO[0].'</td><td>'.$GRPN.'</td></tr>';

}


changeValue("send_news","LIST_EMAILS",$STR);




$ADDITIONAL_NEWS=array(
	
);
	
$STR_TEAMS='<div class="w3-container w3-col s12 m6 l3">
<h4>VITAL</h4>
<table class="table table-sm">';
foreach  ( $MODULE_DATA['SOURCES'] as $K=> $S)
{
	
	if ($S[1]==array())continue;
	
	$STR_TEAMS.='<tr><td style="padding-top:0px;padding-bottom:0px;"><button style="padding:1px" class="btn btn-primary" onclick="submitToTeams(\''.$S[1]['team_id'].'\',\''.$S[1]['channel_id'].'\',\''.$S[0].'\')">'.$S[0].'</button></td></tr>';
}
$STR_TEAMS.='</table></div>';
$CURR_NAME='';
$IS_OPEN=false;
foreach ($ADDITIONAL_NEWS as &$ENTRY)
{
	if ($ENTRY[0]!=$CURR_NAME)
	{
		if ($IS_OPEN)$STR_TEAMS.='</table></div>';
		$IS_OPEN=true;
		$STR_TEAMS.='<div class="w3-container w3-col s12 m6 l3">
		<h4>'.$ENTRY[0].'</h4><table class="table table-sm">';
		$CURR_NAME=$ENTRY[0];
	}
	$STR_TEAMS.='<tr><td style="padding-top:0px;padding-bottom:0px;"><button style="padding:1px" class="btn btn-primary" onclick="submitToTeams(\''.$ENTRY[2].'\',\''.$ENTRY[3].'\',\''.$ENTRY[1].'\')">'.$ENTRY[1].'</button></td></tr>';
}
if ($IS_OPEN)$STR_TEAMS.='</table></div>';

changeValue("send_news","TEAMS",$STR_TEAMS);


if (isset($MODULE_DATA['NEWS_INPUT']))
{
	changeValue("send_news",'INI_TITLE',trim($MODULE_DATA['NEWS_INPUT']['TITLE']));
	changeValue("send_news",'DEFAULT_TEXT_VALUE',$MODULE_DATA['CONTENT'][0]);
	
	changeValue("send_news",'ERR_MSG','<div class="alert alert-info">'.$MODULE_DATA['MESSAGE'].'</div>');
	
}
else if (isset($MODULE_DATA['EDIT_MODE']))
{
	removeBlock("send_news","DISABLE_FILE");
	changeValue("send_news",'ADD_PATH',$MODULE_DATA['HASH']);
	changeValue("send_news",'NEWS_ID',$MODULE_DATA['HASH']);
	changeValue("send_news",'INI_TITLE',trim($MODULE_DATA['NEWS_TITLE']));
	changeValue("send_news",'DEFAULT_TEXT_VALUE',$MODULE_DATA['CONTENT'][0]);
	changeValue("send_news",'HAS_ANNOTS',($MODULE_DATA['HAS_ANNOTS'])?'true':'false');
	changeValue("send_news",'ANNOTS','<div class="w3-container">'.$MODULE_DATA['TAGS_HTML'].'</div>');
	
}

            






?>