<?php
 if (!defined("BIORELS")) header("Location:/");
$SM_HASH=substr($NEWS_HASH,0,7);
changeValue("news_content","HASH_SM",$SM_HASH);
 if ($MODULE_DATA['CONTENT'][0])
 changeValue("news_content","VALUE",str_replace('\"','"',$MODULE_DATA['CONTENT'][0]));
else if (isset($MODULE_DATA['DOCS'][0]))
changeValue("news_content","VALUE",str_replace('\"','"',$MODULE_DATA['DOCS'][0]['DOCUMENT_DESCRIPTION']));
 $STR='';$N_FLAG=0;$IS_OPEN=false;$N_T=0;
 $MAP=array('DRUG'=>'molecule','CLINVAR'=>'','PATHWAY'=>'','GENE'=>'gene','DISEASE'=>'disease','PROT_FEAT'=>'protein','ASSAY'=>'assay','CELL'=>'','TISSUE'=>'tissue','EVIDENCE'=>'evidence','CLINICAL'=>'clinical','COMPANY'=>'company','NEWS'=>'news');

 if (!$NO_TITLE)
 {
	changeValue("news_content","TITLE",'<h1 style="font-family:DDIN;">'.$MODULE_DATA['NEWS_TITLE'].'</h1>');
 changeValue("news_content","STYLE","style='padding:30px'");
 }
 else changeValue("news_content","OVERFLOW","max-height: 500px; overflow-y: scroll");

if (!is_array($MODULE_DATA['AUTHOR']) || $MODULE_DATA['AUTHOR']==array() || $MODULE_DATA['AUTHOR'][3]=='')
removeBlock("news_content","HAS_AUTHOR");
else changeValue("news_content","AUTHOR",'{"'.$MODULE_DATA['AUTHOR'][3].'":{"First_name":"'.$MODULE_DATA['AUTHOR'][1].'","Last_name":"'.$MODULE_DATA['AUTHOR'][0].'","ACCESS":["'.$MODULE_DATA['AUTHOR'][2].'"]}}');




 foreach ($MAP as $TAG=>$CSS_TAG)
 {
 if (isset($MODULE_DATA['TAGS'][$TAG])&& $MODULE_DATA['TAGS'][$TAG]!=0)
 {
	 if ($CSS_TAG=='')continue;
	 if (!$IS_OPEN)$STR.='<div class="pub_tags">';$IS_OPEN=true;
	 $STR.='<div onclick="showNewsInfo_'.$SM_HASH.'(\''.$MODULE_DATA['MD5'].'\',\''.$TAG.'\')" class="grid-item home-grid" style="cursor:pointer;display:inline-block;width:50px;position: relative;margin-right:50px;">
	 <div class="tag_count" style=" left:';
	 if ($MODULE_DATA['TAGS'][$TAG]>=10)$STR.= '5px;';else $STR.='10px;';
	 $STR.='">'.$MODULE_DATA['TAGS'][$TAG].'</div>
	 <div class="sprite_img src_'.$CSS_TAG.'"></div></div>';
	 $N_FLAG++;;
 }
 }
 changeValue("news_content","VALUE",str_replace('\"','"',$MODULE_DATA['DOCS'][0]['DOCUMENT_DESCRIPTION']));
 if ($MODULE_DATA['DOCS'] != array()){
	
	if(!$IS_OPEN)
	{	
		$STR.='<div class="pub_tags">';
		$IS_OPEN=true;
	}
	$STR.='<div style="position:relative; top:-12px;display:inline-block;margin-right:5px">Supporting files:<select class="sel_file"><option></option>';
	foreach ($MODULE_DATA['DOCS'] as $F)
	{
		
		// NEWS_ID, document_name, document_description, document_hash, document_version
		$STR.='<option value=\'/NEWS_FILE/'.$F['DOCUMENT_HASH'].'\'>'.$F['DOCUMENT_NAME'];
		if ($F['DOCUMENT_VERSION']!=1){
			$STR.=' (Version '.$F['DOCUMENT_VERSION'].')';				
		}	
		$STR.='</option>';
	}		
	$STR.='</select></div>';
	
}
if ($IS_OPEN)$STR.='</div>';
 $STR.='<div id="info_'.$MODULE_DATA['MD5'].'"></div>';
 changeValue("news_content","TAGS",$STR);


if (!isset($_SESSION['USER_DATA']))
{
	$_SESSION['USER_DATA']=getUserGroups($USER['id']);

}

$STR='';

    
    
	$STR.='<a target="_blank" style="margin-right:5px" class="search_button btn btn-primary" href="/ADD_NEWS/PARAMS/NEWSID/'.$MODULE_DATA['MD5'].'">Edit</a>';
	$STR.='<a target="_blank" style="margin-right:5px" class="search_button btn btn-primary" href="/SEND_NEWS/'.$MODULE_DATA['MD5'].'">Send</a>';
	$STR.='<a target="_blank" style="margin-right:5px" class="search_button btn btn-primary" href="/MONITOR_NEWS/PARAMS/'.$MODULE_DATA['MD5'].'">Analyze</a>';
	



$actual_link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
	
	$STR.='<button class="search_button btn btn-primary" style="margin-right:5px" onclick="copyToClipboard(\''.$actual_link.'/NEWS_CONTENT/PARAMS/newsTitle/'.$MODULE_DATA['MD5'].'\')">Copy link</button>';
	$STR.='<i>'.$MODULE_DATA['VIEWS'].' views</i>';
	changeValue("news_content","BUTTONS",$STR);



 ?>