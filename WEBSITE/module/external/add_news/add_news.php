<?php

if (!defined("BIORELS")) header("Location:./");

if ($USER['Access'][1]!=1)removeBlock("add_news","HAS_RED_ACCESS");
else removeBlock("add_news","HAS_GREEN_ACCESS");


if (isset($MODULE_DATA['ERROR']))
{
	changeValue("add_news","ERROR_MESSAGE",$MODULE_DATA['ERROR']);
	removeBlock("add_news","DEFAULT");
	return;
}else removeBlock("add_news","ACCESS_ERROR");




function getValidationMethod($key){
	global $USER_INPUT;
	$VALIDATION_STRING='';	
	$SPANBUTTONID= 'userAdded'.ucfirst(strtolower($key));	
	$SELECTBUTTONTEXT= 'Add '.ucfirst(strtolower($key));


	$USER_INPUT['PARAMS']=array(0=>1);
	
	$STR_V=loadHTMLAndRemove($key."_VALIDATE");
	 
	
	$MAP=array('GENE'=>'gn','DISEASE'=>'ds','DRUG'=>'dg','CLINICAL'=>'clinical','COMPANY'=>'company','NEWS'=>'news');

	$VALIDATION_STRING='Search/Add additional tags:<br/> <div id="'.$MAP[$key].'_search" style="display:inline-block; width:50%">'.$STR_V.
	
	'</div><span class="w3-bar-item"><input id="'.$SPANBUTTONID.'"  class="w3-btn btn" onclick="addTag(\''.$key.'\',\''.$MAP[$key].'\')" type="button" value="'.$SELECTBUTTONTEXT.'"></span>'					
	;	
	//echo $VALIDATION_STRING;				
	return $VALIDATION_STRING;

}

$STR='';

foreach ($GLB_CONFIG['GLOBAL']['EMAIL_GROUP'] as $GRPN=>$GRP_INFO)
{
	$STR.='<tr><td><input type="checkbox" name="email[]" value="'.$GRP_INFO['0'].'"></td><td>'.$GRP_INFO[0].'</td><td>'.$GRPN.'</td></tr>';

}
changeValue("add_news","LIST_EMAILS",$STR);


$STR='';
foreach  ( $MODULE_DATA['SOURCES'] as $S)
{
	$STR.='<option value="'.$S.'"';
	if (isset($MODULE_DATA['SOURCE_NAME']) && $MODULE_DATA['SOURCE_NAME']==$S)$STR.=' selected="selected"';
	$STR.='>'.$S.'</option>';
}
changeValue("add_news","LIST_SOURCE",$STR);


if (isset($MODULE_DATA['NEWS_INPUT']))
{
	changeValue("add_news",'INI_TITLE','value="'.$MODULE_DATA['NEWS_INPUT']['TITLE'].'"');
	changeValue("add_news",'DEFAULT_TEXT_VALUE',$MODULE_DATA['NEWS_INPUT']['NEWS_HTML']);
	changeValue("add_news",'OUTPUT','<div class="w3-container">'.$MODULE_DATA['NEWS_INPUT']['NEWS_HTML'].'</div>');
	changeValue("add_news",'ERR_MSG','<div class="alert alert-info">'.$MODULE_DATA['MESSAGE'].'</div>');
	
}
else if (isset($MODULE_DATA['EDIT_MODE']))
{
//	echo '<pre>';print_R($MODULE_DATA);exit;
	removeBlock("add_news","DISABLE_FILE");
	changeValue("add_news",'ADD_PATH','/PARAMS/NEWSID/'.$MODULE_DATA['HASH']);
	changeValue("add_news",'NEWS_ID',$MODULE_DATA['HASH']);
	changeValue("add_news",'INI_TITLE','value="'.$MODULE_DATA['NEWS_TITLE'].'"');
	changeValue("add_news",'DEFAULT_TEXT_VALUE',$MODULE_DATA['CONTENT'][0]);
	changeValue("add_news",'OUTPUT','<div class="w3-container">'.$MODULE_DATA['CONTENT'][0].'</div>');
	if (isset($MODULE_DATA['TAGS']['DISEASE']))
	{
		$STR='';
		foreach ($MODULE_DATA['TAGS']['DISEASE'] as &$DS)
		{
			
			$STR.='<tr><td><input type="checkbox" checked="checked" class="userCheck validate_tags_disease" name="disease[]" value="'.$DS['DISEASE_TAG'].'" id="disease-'.$DS['DISEASE_TAG'].'"></td>
			<td>'.$DS['DISEASE_NAME'].'</td><td></td><td><input type="checkbox" class="userCheck validate_tags_drug" name="disease_primary[]"  value="'.$DS['DISEASE_TAG'].'" id="drug_primary-'.$DS['DISEASE_TAG'].'" '.(($DS['IS_PRIMARY']=='T')?'checked="checked"':'').'></td></tr>';
		}
		changeValue("add_news","DISEASE_MATCH",$STR);
	}
	if (isset($MODULE_DATA['TAGS']['DRUG']))
	{
		$STR='';
		foreach ($MODULE_DATA['TAGS']['DRUG'] as &$DS)
		{
			
			$STR.='<tr><td><input type="checkbox"  checked="checked" class="userCheck validate_tags_drug" name="drug[]" value="'.$DS['DRUG_PRIMARY_NAME'].'" id="drug-'.$DS['DRUG_PRIMARY_NAME'].'"></td>
			<td>'.ucfirst(strtolower($DS['DRUG_PRIMARY_NAME'])).'</td>
			<td></td><td><input type="checkbox" class="userCheck validate_tags_drug" name="drug_primary[]"  value="'.$DS['DRUG_PRIMARY_NAME'].'" id="drug_primary-'.$DS['DRUG_PRIMARY_NAME'].'" '.(($DS['IS_PRIMARY']=='T')?'checked="checked"':'').'></td></tr>';
		}
		changeValue("add_news","DRUG_MATCH",$STR);
	}
	if (isset($MODULE_DATA['TAGS']['COMPANY']))
	{
		$STR='';
		foreach ($MODULE_DATA['TAGS']['COMPANY'] as $N=> &$DS)
		{
			
			$STR.='<tr><td><input type="checkbox"  checked="checked"  class="userCheck validate_tags_company" name="company[]" value="'.$N.'" id="company-'.$N.'"></td><td>'.$N.'</td><td></td><td><input type="checkbox" class="userCheck validate_tags_drug" name="company_primary[]"  value="'.$N.'" id="drug_primary-'.$N.'" '.(($DS['IS_PRIMARY']=='T')?'checked="checked"':'').'></td></tr>';
		}
		changeValue("add_news","COMPANY_MATCH",$STR);
	}
	if (isset($MODULE_DATA['TAGS']['CLINICAL']))
	{
		$STR='';
		foreach ($MODULE_DATA['TAGS']['CLINICAL'] as $N=> &$DS)
		{
			
			$STR.='<tr><td><input type="checkbox"  checked="checked" class="userCheck validate_tags_clinical" name="clinical[]" value="'.$N.'" id="clinical-'.$N.'"></td><td>'.$N.'</td><td></td><td><input type="checkbox" class="userCheck validate_tags_drug" name="clinical_primary[]"  value="'.$N.'" id="drug_primary-'.$N.'" '.(($DS['IS_PRIMARY']=='T')?'checked="checked"':'').'></td></tr>';
		}
		changeValue("add_news","CLINICAL_MATCH",$STR);
	}
	if (isset($MODULE_DATA['TAGS']['GENE']))
	{
		$STR='';
		foreach ($MODULE_DATA['TAGS']['GENE'] as $N=> &$DS)
		{
			
			$STR.='<tr><td><input type="checkbox"  checked="checked" class="userCheck validate_tags_gene" name="gene[]" value="'.$N.'" id="gene-'.$N.'"></td><td>'.$N.'</td><td>'.$DS['SYMBOL'].'</td><td></td><td><input type="checkbox" class="userCheck validate_tags_drug" name="gene_primary[]"  value="'.$N.'" id="drug_primary-'.$N.'" '.(($DS['IS_PRIMARY']=='T')?'checked="checked"':'').'></td></tr>';
		}
		changeValue("add_news","GENE_MATCH",$STR);
	}
	if (isset($MODULE_DATA['TAGS']['NEWS']['PARENT']))
	{
		$STR='';
		
		foreach ($MODULE_DATA['TAGS']['NEWS']['PARENT'] as $N=> &$DS)
		{
			
			$STR.='<tr><td><input type="checkbox"  checked="checked" class="userCheck validate_tags_news" name="news[]" value="'.$N.'" id="news-'.$N.'"></td><td>'.$DS['NEWS_TITLE'].' ('.$DS['SOURCE_NAME'].')</td><td></td></tr>';
		}
		changeValue("add_news","NEWS_MATCH",$STR);
	}
}
		
      





$lt=array('GENE'=>true,'COMPANY'=>true,'DRUG'=>true,'DISEASE'=>true,'CLINICAL'=>true,'NEWS'=>true);
foreach ($lt as $Type=>&$DUMMY)
changeValue("add_news",$Type.'_VALIDATE',getValidationMethod($Type));

if (isset($MATCH['RUN']) && !isset($MATCH['SUBMIT_SUCCESS']))
 {
	
foreach ($lt as $v=>$k) if (!isset($MATCH[$v]))$MATCH[$v]=array();

	removeBlock("add_news","DEFAULT");	
	$STR='';
	$TYPES=array_keys($MATCH);
	$TYPECOUNT=1;	
	// build menu header
	$STR='  
	<div id="annotations" class="w3-container w3-col s12 ">
		<ul class="nav nav-tabs">';			
		foreach ($TYPES as $HEADER) {
			if ($HEADER=='RUN'){continue;}												
				$CLICKSTRING="showMenu('annotation_menu',$TYPECOUNT, 20)"; 
				$STR.='<li id="annotation_menu_tab_'.$TYPECOUNT.'" onclick="'.$CLICKSTRING.'" class="active nav-item nav-link">'.$HEADER.'</li>';															
				$TYPECOUNT++;			
		}				
	$STR.='</ul>';

// build out individual tabs

$TABCOUNT=1;

foreach ($MATCH as $TYPE=>&$LIST){
	// first element we skip 
	echo $TYPE;
	if ($TYPE=='RUN'){continue;}					
	$DISPLAY_TAB= $TABCOUNT > 1 ? "none":"";			
	
	$STR.='	
		<div id="annotation_menu_view_'.$TABCOUNT.'" class="w3-container  container-grey annotation_container" name= "'.$TYPE.'" style="display:'.$DISPLAY_TAB.'"><br>';			
		foreach ($LIST as $ANNOTATION_KEY=>$INFO) {							
			
			$ANNOTATIONCLASS = 'tags_'.strtolower($TYPE);						
			$ID=$INFO['ID'];
			$DISPLAY=$INFO['DISPLAY'];
			$ANNOTATIONFOR = strtolower($TYPE).'-'.$ID;			

			$STR.='<input type="checkbox"
			 class="userCheck validate_'.$ANNOTATIONCLASS.'" 
			 name="'.strtolower($TYPE).'[]" 
			 value="'.$ID.'"
			   id="'.$ANNOTATIONFOR.'"/>
			  <label class="tags '.$ANNOTATIONCLASS.'" style="float:unset;font-size:1em;color:white;left:30px" for="'.$ANNOTATIONFOR.'">'.$DISPLAY.'</label><br/>';															
		}					
		// this is where we add validation method input		
		
	if ($lt[$TYPE])	$STR.= getValidationMethod($TYPE, strval($TABCOUNT));
		
	
	$STR.='</div>';
	$TABCOUNT++;
}	

$STR.='</div>';
changeValue("add_news","LIST",$STR);
}



elseif (isset($MATCH['RUN']) && isset($MATCH['SUBMIT_SUCCESS']) && ($MODULE_DATA==array()))
{	

removeBlock("add_news","DEFAULT");	
$STR = $MODULE_DATA['MESSAGE'];  
changeValue("add_news","LIST", $STR);

}
else 
removeBlock("add_news","SELECTION");

?>