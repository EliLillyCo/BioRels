<?php
if (!defined("BIORELS")) header("Location:/");

if (!isset($MODULE_DATA['DOM_INFO']))
{
	removeBlock("domains","DOMAIN");
	return;
}
else removeBlock("domains","N_DOMAIN");
$DOM_TYPE_IMG=array("CHAIN"=>array("C","#a5a5a5"),"REPEAT"=>array("R","#ed7d31"),"DOMAIN"=>array("D","#70ad47"),"REGION"=>array("G","#843c0c"));

// $STR='';

// foreach ($MODULE_DATA['DOM_INFO'] as $DOM_INFO)
// {
// 	$STR.='<h3><img src="/require/img/DO_'.$DOM_TYPE_IMG[strtoupper($DOM_INFO['INFO']['ENTRY_TYPE'])][0].'.png" class="ss_row_name_img"/> '.$DOM_INFO['INFO']['NAME'].'</h3>';
// }
// changeValue("domains",'VAL',$STR);
$STR='';
foreach ($MODULE_DATA['DOM_INFO'] as  $DOM_ID=>&$DOM_INFO)
{
	$RID=0;
	//echo $DOM_INFO['INFO']['ABSTRACT'];
	preg_match_all("/\{([A-Za-z:0-9, ]){1,1000}\}/",$DOM_INFO['INFO']['ABSTRACT'],$matches);
	$DOM_INFO['INFO']['ABSTRACT'].='<h5>Relevant publications - <span style="text-align:left;cursor:pointer" onclick="$(\'#publi_'.$DOM_ID.'\').toggle();if ($(this).html()==\'Hide\'){$(this).html(\'Show\');}else {$(this).html(\'Hide\');}">Show</span></h5><div id="publi_'.$DOM_ID.'" style="display:none;font-size:0.8em">';
	$LT=array();$RIDs=array();
	foreach ($matches[0] as $match)
	{
		++$RID;
		$DOM_INFO['INFO']['ABSTRACT']=str_replace($match,'<sup>['.$RID.']</sup>',$DOM_INFO['INFO']['ABSTRACT']);
	//	echo $match;
		preg_match_all("/Pubmed:([0-9]{1,10})/",$match,$pub_match);
	//	echo "<pre>";
	//	print_r($pub_match);
		
		
		foreach ($pub_match[1] as $PMID){$LT[]=$PMID;$RIDs[]=$RID;}
			
		
		
		
	}
	$USER_INPUT['PAGE']['VALUE']=implode("_",$LT);
	$USER_INPUT['PARAMS']=array('RID',implode("_",$RIDs));
	if ($RIDs!=array())
	{
	$DOM_INFO['INFO']['ABSTRACT'].=loadHTMLAndRemove('PUBLICATION_BATCH');
	}else $DOM_INFO['INFO']['ABSTRACT'].='N/A';
	$DOM_INFO['INFO']['ABSTRACT'].='</div>';
	//
	//echo "<pre>";
	
	//print_r($matches);//exit;
$STR.='<div style="display:none" id="'.$DOM_INFO['INFO']['IPR_ID'].'_abst">'.$DOM_INFO['INFO']['ABSTRACT'].'</div>';
unset($DOM_INFO['INFO']['ABSTRACT']);
}


changeValue("domains","ABSTRACT",$STR);
changeValue("domains","PORTAL_PATH",$USER_INPUT['PORTAL']['TYPE']."/".$USER_INPUT['PORTAL']['VALUE']);
changeValue("domains","RAW_DATA",json_encode($MODULE_DATA));







?>