<?php
 if (!defined("BIORELS")) header("Location:/");
 
 if($MODULE_DATA==array())
 {	 
	removeBlock("news_batch","HAS_DATA");	
	return;	
 } else removeBlock("news_batch","NO_DATA");

	
$RAW_STR= '<div class="news_entry" id="${HASH_SM}_entry">
<div class="news_header" onclick="loadRecord(\'${HASH}\')">

<div class="news_title${LEN}"><div class="news_arrow" id="arrow_${HASH_SM}">&#x25b6;</div>${TITLE}</div>
<div class="news_source">
${SOURCE} <br/> on <span style="font-weight:bold">${DATE}</span></div></div>
<div class="w3-container news_content w3-col s12 l12 m12" style="display:none" id="${HASH_SM}_content">
</div>
</div>';




$ALL_STR='';
$LIST_HASH=array();
$CONTENT_ARRAY= array();
$MAP=array();
if ($IS_ID)
{
foreach ($MODULE_DATA['DATA'] as $HASH=>&$ENTRY)$MAP[$ENTRY['NEWS_ID']]=$HASH;
}
foreach ($MODULE_DATA['ORDER'] as &$HASH)
{
	
	if ($IS_ID)$HASH=$MAP[$HASH];
	$ENTRY=&$MODULE_DATA['DATA'][$HASH];
	// starting out with open html string
	$NEW_STR=$RAW_STR;
	$LIST_HASH[]=$HASH;
	$NEW_STR=str_replace('${TITLE}',$ENTRY['NEWS_TITLE'],$NEW_STR);
	if (strlen($ENTRY['NEWS_TITLE'])>50)
	{
		
		$NEW_STR=str_replace('${LEN}','_long',$NEW_STR);
	}
	$NEW_STR=str_replace('${DATE}',$ENTRY['RELEASE_DATE'],$NEW_STR);				
		
	$NEW_STR=str_replace('${HASH}',$HASH, $NEW_STR);	
	$NEW_STR=str_replace('${HASH_SM}',substr($HASH,0,6), $NEW_STR);
	
	
	
	 if ($ENTRY['LAST_NAME']!=''){
		$NEW_STR=str_replace('${SOURCE}',$ENTRY['LAST_NAME'].' '.$ENTRY['FIRST_NAME'],$NEW_STR);
	} 
	else {
		 $NEW_STR=str_replace('${SOURCE}',$ENTRY['SOURCE_NAME'],$NEW_STR); 
	}
	

$ALL_STR.=$NEW_STR;
}	  




changeValue("news_batch","PUBLIS",$ALL_STR);
changeValue("news_batch","HASHES",json_encode($LIST_HASH));

?>