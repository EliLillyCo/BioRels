<?php
 if (!defined("BIORELS")) header("Location:/");
 if ($MODULE_DATA==array())
 {
	 
	 removeBlock("publi_batch","HAS_DATA");
	 return;
 }else removeBlock("publi_batch","NO_DATA");
 
 
 
$RAW_STR='<div class="pub_entry">

<a target="_blank" ${HREF}>${TITLE}</a>
<br/>
${AUTHORS}
<br/><span class="bold">Abstract:</span> <span id="${PMID}" style="cursor:pointer" onclick="$(\'#abst_${PMID}\').toggle();if ($(this).html()==\'Hide\'){$(this).html(\'Show\');}else {$(this).html(\'Hide\');}">Show</span><br/>

<div id="abst_${PMID}"  style="display:none;text-align:justify;line-height:1.7em" >${ABSTRACT}</div>


<span class="ital">${JOURNAL_ABBR}</span>, <span class="bold">${VOLUME}</span> ${PAGES} ${DATE}<a href="/PUBMED/${PMID}"><img id="publi_tool_but" src="/require/img/tools.png" style="width: 15px;margin-left:10px"/></a>

<br/>${TAGS}


</div>';


$MAP_ALL=array();
foreach ($MODULE_DATA as $PI=> &$ENTRY)if ($PI!='ORDER')$MAP_ALL[$ENTRY['ENTRY']['PMID']]=$PI;

$ALL_STR='';
foreach ($MODULE_DATA['ORDER'] as $K=>$PMID)
{
	$ENTRY=$MODULE_DATA[$MAP_ALL[$PMID]];
	//print_r($ENTRY);
	if (isset($ENTRY['ISSUE']))
	{
		//if ($ORDER!=array())$ALL_STR.='<span class="bold">['.$K.']</span><br/>';
		$ALL_STR.='<div class="pub_entry"><div class="alert alert-info">'.$ENTRY['ENTRY']['PMID'].' has not been found in the database. Link to pubmed <a target="_blank" rel="noopener" href=\'https://pubmed.ncbi.nlm.nih.gov/'.$ENTRY['ENTRY']['PMID'].'\'>publication</a></div></div>';
		
		continue;
	}
	$NEW_STR='';
	//if ($ORDER!=array())$NEW_STR.='<span class="bold">['.$K.']</span><br/>';
	$NEW_STR.=$RAW_STR;
	
	
	$CHANGES=array();
	foreach ($CHANGES as $CHANGE=>$ALT)
	{
		$ENTRY['ENTRY']['TITLE']=str_Replace($CHANGE,$ALT,$ENTRY['ENTRY']['TITLE']);
		if (isset($ENTRY['ENTRY']['ABSTRACT']))	$ENTRY['ENTRY']['ABSTRACT']=str_replace($CHANGE,$ALT,$ENTRY['ENTRY']['ABSTRACT']);
	}
	$NEW_STR=str_replace('${TITLE}',$ENTRY['ENTRY']['TITLE'],$NEW_STR);
	$NEW_STR=str_replace('${PMID}',$ENTRY['ENTRY']['PMID'],$NEW_STR);

	if (isset($ENTRY['ENTRY']['ABSTRACT'])){
	   $STR='';
	   foreach ($ENTRY['ENTRY']['ABSTRACT'] as $NAME=>$TXT)
	   {
		if ($NAME!='Text')$STR.='<h5>'.$NAME.'</h5>'.$TXT;
		else $STR.=$TXT;
	   }
	   $ENTRY['ENTRY']['ABSTRACT']=$STR;
	   
	   $NEW_STR=str_replace('${ABSTRACT}',$ENTRY['ENTRY']['ABSTRACT'],$NEW_STR);
	}
	   if ($ENTRY['ENTRY']['JOURNAL_ABBR']!='')$NEW_STR=str_replace('${JOURNAL_ABBR}',$ENTRY['ENTRY']['JOURNAL_ABBR'],$NEW_STR);
	   if ($ENTRY['ENTRY']['VOLUME']!='')$NEW_STR=str_replace('${VOLUME}',$ENTRY['ENTRY']['VOLUME'],$NEW_STR);
	   if ($ENTRY['ENTRY']['PAGES']!='')$NEW_STR=str_replace('${PAGES}',$ENTRY['ENTRY']['PAGES'],$NEW_STR);
	   $DATE=date('Y-m-d',strtotime($ENTRY['ENTRY']['PUBLICATION_DATE']));
	   if ($ENTRY['ENTRY']['PMID_STATUS']=='aheadofprint')$DATE .=' <span style="font-weight:italic">Ahead of print</span>';
	   
	   if ($ENTRY['ENTRY']['PMID_STATUS']=='epublish')$DATE .=' <span style="font-weight:italic">e-Publication</span>';
	   
	   $NEW_STR=str_replace('${DATE}',$DATE,$NEW_STR);
$STR='';

if (isset($ENTRY['AUTHORS'])){
if (count($ENTRY['AUTHORS'])<30){
	foreach ($ENTRY['AUTHORS'] as &$AUTHOR)
	{
		$STR.=$AUTHOR['LAST_NAME'].' '.(($AUTHOR['INITIALS']!='')?$AUTHOR['INITIALS'].'.':'').' ; ';
	}
}else $STR.= count($ENTRY['AUTHORS']).' authors   ';
}
$NEW_STR=str_replace('${AUTHORS}',substr($STR,0,-3),$NEW_STR);
	
if (!in_array('FOR_MAIL',$USER_INPUT['PARAMS']))
{
	if (isset($ENTRY['ENTRY']['DOI']) 
	   && $ENTRY['ENTRY']['DOI']!='')$NEW_STR=str_replace('${HREF}',"href='https://dx.doi.org/".$ENTRY['ENTRY']['DOI']."'",$NEW_STR);
	   else $NEW_STR= str_replace('${HREF}',"href='https://pubmed.ncbi.nlm.nih.gov/".$ENTRY['ENTRY']['PMID']."'",$NEW_STR);

	
	$STR='';$N_FLAG=0;$IS_OPEN=false;$N_T=0;
	$MAP=array('DRUG'=>'molecule','CLINVAR'=>'','PATHWAY'=>'','GENE'=>'gene','DISEASE'=>'disease','PROT_FEAT'=>'protein','ASSAY'=>'assay','CELL'=>'','TISSUE'=>'tissue','EVIDENCE'=>'evidence');
	foreach ($MAP as $TAG=>$CSS_TAG)
	{
	if (isset($ENTRY['TAGS'][$TAG]))
	{
		if ($CSS_TAG=='')continue;
		if (!$IS_OPEN)$STR.='<div class="pub_tags">';$IS_OPEN=true;
		$STR.='<div onclick="showInfo('.$ENTRY['ENTRY']['PMID'].',\''.$TAG.'\')" class="grid-item home-grid" style="cursor:pointer;display:inline-block;width:50px;position: relative;margin-right:50px;">
		<div style="    position: absolute;
		top: 10px;
		left:';
		if ($ENTRY['TAGS'][$TAG]>=10)$STR.= '5px;';else $STR.='10px;';
		$STR.='font-size: 1em;
		font-weight: bold;">'.$ENTRY['TAGS'][$TAG].'</div>
		<div class="sprite_img src_'.$CSS_TAG.'"></div></div>';
		$N_FLAG++;;
	}
	}
	if ($IS_OPEN)$STR.='</div><div id="info_'.$ENTRY['ENTRY']['PMID'].'"></div>';
	
	// if (isset($ENTRY['TAGS']['DRUG']) && count($ENTRY['TAGS']['DRUG'])!=0)
	// {
	// 	if (!$IS_OPEN)$STR.='<div class="pub_tags">';$IS_OPEN=true;
		
	// 	foreach ($ENTRY['TAGS']['DRUG'] as &$AUTHOR)
	// 	{
	// 		$STR.='<div class="tags tags_compound"><a target="_blank" href="DRUG/'.$AUTHOR['DRUG_NAME'].'" title="'.$AUTHOR['DESCRIPTION'].'">'.ucfirst(strtolower($AUTHOR['DRUG_NAME'])).'</a></div>';
	// 		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 		++$N_FLAG;
	// 	}
	// }
	// if (isset($ENTRY['TAGS']['CLINVAR']) && count($ENTRY['TAGS']['CLINVAR'])!=0)
	// {
	// 	if (!$IS_OPEN)$STR.='<div class="pub_tags">';$IS_OPEN=true;
		
	// 	foreach ($ENTRY['TAGS']['CLINVAR'] as &$AUTHOR)
	// 	{
	// 		$STR.='<div class="tags tagsbrown"><a target="_blank" href="CLINVAR/'.$AUTHOR['CLINV_IDENTIFIER'].'">Clinvar '.$AUTHOR['CLINV_IDENTIFIER'].'</a></div>';
	// 		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 		++$N_FLAG;
	// 	}
	// }
	// if (isset($ENTRY['TAGS']['PATHWAY']) && count($ENTRY['TAGS']['PATHWAY'])!=0)
	// {
	// 	if (!$IS_OPEN)$STR.='<div class="pub_tags">';$IS_OPEN=true;
		
	// 	foreach ($ENTRY['TAGS']['PATHWAY'] as &$AUTHOR)
	// 	{
	// 		$STR.='<div class="tags tags_protein"><a target="_blank" href="PATHWAY/'.$AUTHOR['REAC_ID'].'">'.$AUTHOR['REAC_ID'].' ('.$AUTHOR['N_GENE'].' genes)</a></div>';
	// 	//	if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 		++$N_FLAG;
	// 	}
	// }

	// if (isset($ENTRY['TAGS']['GENE']) &&count($ENTRY['TAGS']['GENE'])!=0)
	// {
	// 	if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
	// 	foreach ($ENTRY['TAGS']['GENE'] as &$AUTHOR)
	// {
		
	// 	$STR.='<div class="tags tags_gene"><a href="/GENEID/'.$AUTHOR['GENE_ID'].'" title="'.$AUTHOR['FULL_NAME'].'">'.$AUTHOR['SYMBOL'].'</a></div>';
	// 	//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 	++$N_FLAG;
	// }
	// }
	// if (isset($ENTRY['TAGS']['DISEASE']) &&count($ENTRY['TAGS']['DISEASE'])!=0)
	// {
	// 	if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
	// 	foreach ($ENTRY['TAGS']['DISEASE'] as &$AUTHOR)
	// {
		
	// 	$STR.='<div class="tags tags_disease"><a href="/DISEASE/'.$AUTHOR['DISEASE_TAG'] .'" title="'.$AUTHOR['DISEASE_DEFINITION'].'">'.$AUTHOR['DISEASE_NAME'].'</a></div>';
	// 	//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 	++$N_FLAG;
	// }
	// }
	// if (isset($ENTRY['TAGS']['GO']) &&count($ENTRY['TAGS']['GO'])!=0)
	// {
	// 	if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
	// 	foreach ($ENTRY['TAGS']['GO'] as &$AUTHOR)
	// {
		
	// 	$STR.='<div class="tags tagsored"><a href="/GO/'.$AUTHOR['AC'].'">'.$AUTHOR['NAMESPACE'].': '.$AUTHOR['NAME'].'</a></div>';
	// 	//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 	++$N_FLAG;
	// }
	// }
	// if (isset($ENTRY['TAGS']['PROT_FEAT']) &&count($ENTRY['TAGS']['PROT_FEAT'])!=0)
	// {
	// 	if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
	// 	foreach ($ENTRY['TAGS']['PROT_FEAT'] as &$AUTHOR)
	// {
		
	// 	$STR.='<div class="tags tags_protein"><a href="/SEQUENCE/'.$AUTHOR['ISO_ID'].'">'.$AUTHOR['FEAT_NAME'].': '.$AUTHOR['PROT_IDENTIFIER'].' '.$AUTHOR['FEAT_VALUE'].'</a></div>';
	// 	//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 	++$N_FLAG;
	// }
	// }
	
	// if (isset($ENTRY['TAGS']['ASSAY']) &&count($ENTRY['TAGS']['ASSAY'])!=0)
	// {
	// 	if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
	// 	foreach ($ENTRY['TAGS']['ASSAY'] as &$AUTHOR)
	// {
		
	// 	$STR.='<div class="tags tags_assay"><a href="/ASSAY/'.$AUTHOR['ASSAY_NAME'].'" title="'.$AUTHOR['ASSAY_DESCRIPTION'].'">'.$AUTHOR['ASSAY_NAME'].'</a></div>';
	// 	//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 	++$N_FLAG;
	// }
	// }

	// if (isset($ENTRY['TAGS']['CELL']) &&count($ENTRY['TAGS']['CELL'])!=0)
	// {
	// 	if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
	// 	foreach ($ENTRY['TAGS']['CELL'] as &$AUTHOR)
	// {
		
	// 	$STR.='<div class="tags tags_tissue"><a href="/CELL/'.$AUTHOR['CELL_NAME'].'" title="'.$AUTHOR['CELL_TYPE'].'">Cell: '.$AUTHOR['CELL_NAME'].'</a></div>';
	// 	//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 	++$N_FLAG;
	// }
	// }

	// if (isset($ENTRY['TAGS']['TISSUE']) &&count($ENTRY['TAGS']['TISSUE'])!=0)
	// {
	// 	if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
	// 	foreach ($ENTRY['TAGS']['TISSUE'] as &$AUTHOR)
	// {
		
	// 	$STR.='<div class="tags tags_tissue"><a href="/TISSUE/'.$AUTHOR['ANATOMY_NAME'].'" title="'.$AUTHOR['ANATOMY_DEFINITION'].'">'.$AUTHOR['ANATOMY_NAME'].'</a></div>';
	// 	//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
	// 	++$N_FLAG;
	// }
	// }

	

}
else
{
	
	$NEW_STR=str_replace("HREF","href='http://".$_SERVER['HTTP_HOST'].'/PUBLI_LINK/'.$ENTRY['PMID']."'",$NEW_STR);
	$STR='<table><tr><td style="font-weight:bold">List of tags:</td>';$N_FLAG=0;$IS_OPEN=false;$N_T=0;
	if (count($ENTRY['TAGS']['RULE'])!=0)
	{
	
		
	foreach ($ENTRY['TAGS']['RULE'] as &$AUTHOR)
	{
		$COLOR='blue';
		switch ($AUTHOR['RULE_GROUP'])
		{
			case 'Organism':$COLOR='red';break;
			case 'Body':$COLOR='orange';break;
			case 'Disease':$COLOR='purple';break;
			case 'Clinical Study':$COLOR='royalblue';break;
		}
		$STR.='<td style="color:'.$COLOR.'">'.$AUTHOR['RULE_NAME'].'</td>';
		if ($N_FLAG==10){$STR.='</tr><tr>';$N_FLAG=0;}
		++$N_FLAG;
	}
	}
	if (count($ENTRY['TAGS']['PATHWAY'])!=0)
	{
		
		
	foreach ($ENTRY['TAGS']['PATHWAY'] as &$AUTHOR)
	{
		$STR.='<td style="color:#F5F5DC">'.$AUTHOR['REAC_ID'].' ('.$AUTHOR['N_GENE'].' genes)</td>';
		if ($N_FLAG==10){$STR.='</tr><tr>';$N_FLAG=0;}
		++$N_FLAG;
	}
	}

	if (count($ENTRY['TAGS']['GENE'])!=0)
	{
		
		
		foreach ($ENTRY['TAGS']['GENE'] as &$AUTHOR)
	{
		
		$STR.='<td style="color:brown">'.$AUTHOR['SYMBOL'].'</td>';
		if ($N_FLAG==10){$STR.='</tr><tr>';$N_FLAG=0;}
		++$N_FLAG;
	}
	}
	$STR.='</tr></table>';

}
$NEW_STR=str_replace('${TAGS}',$STR,$NEW_STR);
$ALL_STR.=$NEW_STR;
}
/*${HREF}>${TITLE}</a>
<br/>
<p>${ABSTRACT}</p>
<br/>
${JOURNAL_ABBR}, ${VOLUME} ${PAGES} ${DATE}
<div style="color:blue">${LINKS}</div>
*/
changeValue("publi_batch","PUBLIS",$ALL_STR);

?>