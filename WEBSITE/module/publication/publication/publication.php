<?php

 if (!defined("BIORELS")) header("Location:/");

 
if ($MODULE_DATA['ENTRY']==array())
{
	
	removeBlock("publication","HAS_ENTRY");
	return;
}else removeBlock("publication","NO_ENTRY");



$CHANGES=array('<'=>'&lt;', '>'=>'&gt;','/'=>'&#47;');
foreach ($CHANGES as $CHANGE=>$ALT)
{
	$MODULE_DATA['ENTRY']['TITLE']=str_Replace($CHANGE,$ALT,$MODULE_DATA['ENTRY']['TITLE']);
	$MODULE_DATA['ENTRY']['ABSTRACT']=str_Replace($CHANGE,$ALT,$MODULE_DATA['ENTRY']['ABSTRACT']);
}

//

	changeValue("publication","TITLE",$MODULE_DATA['ENTRY']['TITLE']);
	   changeValue("publication","PMID",$MODULE_DATA['ENTRY']['PMID']);

	   $STR='';$FIRST=true;
	   foreach ($MODULE_DATA['ENTRY']['ABSTRACT'] as $NAME=>$TXT)
	   {
		if ($NAME!='Text')
		{
			$STR.=((!$FIRST)?'<br/>':'').'<span style="font-weight:bold;font-size:1.1em">'.$NAME.':</span><br/>'."\n".$TXT."\n";//$STR.='<h4>'.$NAME.'</h4>'."\n".$TXT."\n";
			$FIRST=false;
		}
		else $STR.=$TXT."<br/>\n";
	   }
	   $MODULE_DATA['ENTRY']['ABSTRACT']=$STR;


	if (isset($MODULE_DATA['ENTRY']['ABSTRACT']))
	changeValue("publication","ABSTRACT",$MODULE_DATA['ENTRY']['ABSTRACT']);
	changeValue("publication","JOURNAL_ABBR",$MODULE_DATA['ENTRY']['JOURNAL_ABBR']);
	changeValue("publication","VOLUME",$MODULE_DATA['ENTRY']['VOLUME']);
	changeValue("publication","PAGES",$MODULE_DATA['ENTRY']['PAGES']);
	changeValue("publication","DATE",$MODULE_DATA['ENTRY']['PUBLICATION_DATE']);

$STR='';

if (count($MODULE_DATA['AUTHORS'])<20){
	foreach ($MODULE_DATA['AUTHORS'] as &$AUTHOR)
	{
		$STR.=$AUTHOR['LAST_NAME'].' ; ';
	}
}else $STR.= count($MODULE_DATA['AUTHORS']).' authors   ';
	changeValue("publication","AUTHORS",substr($STR,0,-3));
	


	if (isset($MODULE_DATA['ENTRY']['DOI']) 
	   && $MODULE_DATA['ENTRY']['DOI']!='')changeValue("publication","HREF","href='https://dx.doi.org/".$MODULE_DATA['ENTRY']['DOI']."'");
	   else changeValue("publication","HREF","href='https://pubmed.ncbi.nlm.nih.gov/".$MODULE_DATA['ENTRY']['PMID']."'");

	
	$STR='';$N_FLAG=0;$IS_OPEN=false;$N_T=0;
	if (count($MODULE_DATA['TAGS']['RULE'])!=0)
	{
		$STR.='<div class="pub_tags">';$IS_OPEN=true;
		
	foreach ($MODULE_DATA['TAGS']['RULE'] as &$AUTHOR)
	{
		$COLOR='tagsblue';
		switch ($AUTHOR['RULE_GROUP'])
		{
			case 'Organism':$COLOR='tagsred';break;
			case 'Body':$COLOR='tagsorange';break;
			case 'Disease':$COLOR='tagspurple';break;
			case 'Clinical Study':$COLOR='tagsrb';break;
		}
		$STR.='<div class="tags '.$COLOR.'"><a href="/PUBLI_TOPIC/'.$AUTHOR['RULE_NAME'].'" title="'.$AUTHOR['RULE_DESC'].'">'.$AUTHOR['RULE_NAME'].'</a></div>';
		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
		++$N_FLAG;
	}
	}

	if (isset($MODULE_DATA['TAGS']['DRUG']) && count($MODULE_DATA['TAGS']['DRUG'])!=0)
	{
		if (!$IS_OPEN)$STR.='<div class="pub_tags">';$IS_OPEN=true;
		
		foreach ($MODULE_DATA['TAGS']['DRUG'] as &$AUTHOR)
		{
			$STR.='<div class="tags tags_compound"><a target="_blank" href="DRUG/'.$AUTHOR['DRUG_NAME'].'" title="'.$AUTHOR['DESCRIPTION'].'">'.ucfirst(strtolower($AUTHOR['DRUG_NAME'])).'</a></div>';
			//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
			++$N_FLAG;
		}
	}
	if (isset($MODULE_DATA['TAGS']['CLINVAR']) && count($MODULE_DATA['TAGS']['CLINVAR'])!=0)
	{
		if (!$IS_OPEN)$STR.='<div class="pub_tags">';$IS_OPEN=true;
		
		foreach ($MODULE_DATA['TAGS']['CLINVAR'] as &$AUTHOR)
		{
			$STR.='<div class="tags tagsbrown"><a target="_blank" href="CLINVAR/'.$AUTHOR['CLINV_IDENTIFIER'].'">Clinvar '.$AUTHOR['CLINV_IDENTIFIER'].'</a></div>';
			//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
			++$N_FLAG;
		}
	}
	if (isset($MODULE_DATA['TAGS']['PATHWAY']) && count($MODULE_DATA['TAGS']['PATHWAY'])!=0)
	{
		if (!$IS_OPEN)$STR.='<div class="pub_tags">';$IS_OPEN=true;
		
		foreach ($MODULE_DATA['TAGS']['PATHWAY'] as &$AUTHOR)
		{
			$STR.='<div class="tags tags_protein"><a target="_blank" href="PATHWAY/'.$AUTHOR['REAC_ID'].'">'.$AUTHOR['REAC_ID'].' ('.$AUTHOR['N_GENE'].' genes)</a></div>';
		//	if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
			++$N_FLAG;
		}
	}

	if (isset($MODULE_DATA['TAGS']['GENE']) &&count($MODULE_DATA['TAGS']['GENE'])!=0)
	{
		if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
		foreach ($MODULE_DATA['TAGS']['GENE'] as &$AUTHOR)
	{
		
		$STR.='<div class="tags tags_gene"><a href="/GENEID/'.$AUTHOR['GENE_ID'].'" title="'.$AUTHOR['FULL_NAME'].'">'.$AUTHOR['SYMBOL'].'</a></div>';
		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
		++$N_FLAG;
	}
	}
	if (isset($MODULE_DATA['TAGS']['DISEASE']) &&count($MODULE_DATA['TAGS']['DISEASE'])!=0)
	{
		if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
	//	echo '<pre>';print_R($MODULE_DATA['TAGS']['DISEASE']);exit;
		foreach ($MODULE_DATA['TAGS']['DISEASE'] as &$AUTHOR)
	{
		
		$STR.='<div class="tags tags_disease"><a href="/DISEASE/'.$AUTHOR['DISEASE_TAG'] .'" title="'.$AUTHOR['DISEASE_DEFINITION'].'">'.$AUTHOR['DISEASE_NAME'].'</a></div>';
		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
		++$N_FLAG;
	}
	}
	if (isset($MODULE_DATA['TAGS']['GO']) &&count($MODULE_DATA['TAGS']['GO'])!=0)
	{
		if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
		foreach ($MODULE_DATA['TAGS']['GO'] as &$AUTHOR)
	{
		
		$STR.='<div class="tags tagsored"><a href="/GO/'.$AUTHOR['AC'].'">'.$AUTHOR['NAMESPACE'].': '.$AUTHOR['NAME'].'</a></div>';
		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
		++$N_FLAG;
	}
	}
	if (isset($MODULE_DATA['TAGS']['PROT_FEAT']) &&count($MODULE_DATA['TAGS']['PROT_FEAT'])!=0)
	{
		if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
		foreach ($MODULE_DATA['TAGS']['PROT_FEAT'] as &$AUTHOR)
	{
		
		$STR.='<div class="tags tags_protein"><a href="/SEQUENCE/'.$AUTHOR['ISO_ID'].'">'.$AUTHOR['FEAT_NAME'].': '.$AUTHOR['PROT_IDENTIFIER'].' '.$AUTHOR['FEAT_VALUE'].'</a></div>';
		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
		++$N_FLAG;
	}
	}
	
	if (isset($MODULE_DATA['TAGS']['ASSAY']) &&count($MODULE_DATA['TAGS']['ASSAY'])!=0)
	{
		if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
		foreach ($MODULE_DATA['TAGS']['ASSAY'] as &$AUTHOR)
	{
		
		$STR.='<div class="tags tags_assay"><a href="/ASSAY/'.$AUTHOR['ASSAY_NAME'].'" title="'.$AUTHOR['ASSAY_DESCRIPTION'].'">'.$AUTHOR['ASSAY_NAME'].'</a></div>';
		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
		++$N_FLAG;
	}
	}

	if (isset($MODULE_DATA['TAGS']['CELL']) &&count($MODULE_DATA['TAGS']['CELL'])!=0)
	{
		if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
		foreach ($MODULE_DATA['TAGS']['CELL'] as &$AUTHOR)
	{
		
		$STR.='<div class="tags tags_tissue"><a href="/CELL/'.$AUTHOR['CELL_NAME'].'" title="'.$AUTHOR['CELL_TYPE'].'">Cell: '.$AUTHOR['CELL_NAME'].'</a></div>';
		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
		++$N_FLAG;
	}
	}

	if (isset($MODULE_DATA['TAGS']['TISSUE']) &&count($MODULE_DATA['TAGS']['TISSUE'])!=0)
	{
		if (!$IS_OPEN){$STR.='<div class="pub_tags">';$IS_OPEN=true;}
		
		foreach ($MODULE_DATA['TAGS']['TISSUE'] as &$AUTHOR)
	{
		
		$STR.='<div class="tags tags_tissue"><a href="/TISSUE/'.$AUTHOR['ANATOMY_NAME'].'" title="'.$AUTHOR['ANATOMY_DEFINITION'].'">'.$AUTHOR['ANATOMY_NAME'].'</a></div>';
		//if ($N_FLAG==10){$STR.='</div><br/><div class="pub_tags">';$N_FLAG=0;}
		++$N_FLAG;
	}
	}

	if ($IS_OPEN){$STR.='</div>';--$N_T;}


changeValue("publication","TAGS",$STR);



?>