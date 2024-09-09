<?php

if (!defined("BIORELS")) header("Location:/");



changeValue("publication_portal","TITLE",$MODULE_DATA['ENTRY']['TITLE']);
if (isset($MODULE_DATA['ENTRY']['DOI']) 
	   && $MODULE_DATA['ENTRY']['DOI']!='')changeValue("publication_portal","HREF","href='https://dx.doi.org/".$MODULE_DATA['ENTRY']['DOI']."'");
	   changeValue("publication_portal","PMID",$MODULE_DATA['ENTRY']['PMID']);


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
	//    $KEYWD=array('BACKGROUND:','OBJECTIVE:','OBJECTIVES:','METHODS:','RESULTS:','CONCLUSION:');
	//    foreach ($KEYWD as $K)
	//    {
	// 	$pos=stripos($MODULE_DATA['ENTRY']['ABSTRACT'],$K);
	// 	if ($pos===false)continue;
	// 	$MODULE_DATA['ENTRY']['ABSTRACT']=str_ireplace($K,(($pos==0)?'':'<br/><br/>').'<span class="bold">'.$K.'</span><br/>',$MODULE_DATA['ENTRY']['ABSTRACT']);
	//    }
	   

changeValue("publication_portal","ABSTRACT",$STR);
changeValue("publication_portal","JOURNAL_ABBR",$MODULE_DATA['ENTRY']['JOURNAL_ABBR']);
changeValue("publication_portal","VOLUME",$MODULE_DATA['ENTRY']['VOLUME']);
changeValue("publication_portal","PAGES",$MODULE_DATA['ENTRY']['PAGES']);
changeValue("publication_portal","DATE",$MODULE_DATA['ENTRY']['PUBLICATION_DATE']);
if ($MODULE_DATA['ENTRY']['DOI']!='NULL')changeValue("publication_portal","DOI",$MODULE_DATA['ENTRY']['DOI']);
changeValue("publication_portal","JOURNAL_NAME",$MODULE_DATA['ENTRY']['JOURNAL_NAME']);
changeValue("publication_portal","JOURNAL_ABBR",$MODULE_DATA['ENTRY']['JOURNAL_ABBR']);
$PMID=$MODULE_DATA['ENTRY']['PMID'];
$STR='';
foreach ($MODULE_DATA['AUTHORS'] as &$AUTHOR)
{
	$STR.='<tr><td style="width:20%">'.$AUTHOR['LAST_NAME'].' '.$AUTHOR['FIRST_NAME'].'</td>
	<td>'.$AUTHOR['INSTIT_NAME'].'</td><td style="width:15%">'.$AUTHOR['ORCID_ID'].'</td></tr>';
}
changeValue("publication_portal","AUTHORS",$STR);



$map=array('ppublish'=>'Print-format','epublish'=>'Electronic format','aheadofprint'=>'Ahead of print');
changeValue("publication_portal","STATUS",$map[$MODULE_DATA['ENTRY']['PMID_STATUS']]);
// changeValue("publication_portal","INFO",$STR);

if($MODULE_DATA['FT']['CO']!=0)
{
changeValue("publication_portal","FULL_TEXT",'<div class="w3-col s12 l12 m12" style="text-align:center"><a href="/PUBLICATION_FULLTEXT/'.$PMID.'" target="_blank" style="cursor:pointer;margin:0 auto" class="btn btn-primary">Full text</a></div>');
}


$MAP=array('DRUG'=>'molecule','CLINVAR'=>'','PATHWAY'=>'','GENE'=>'gene','DISEASE'=>'disease','PROT_FEAT'=>'protein','ASSAY'=>'assay','CELL'=>'','TISSUE'=>'tissue','EVIDENCE'=>'evidence');
$STR='';
$STR_B='';
$STR_J='';
$N=2;
echo '<pre>';print_r($MODULE_DATA);
	foreach ($MAP as $TAG=>$CSS_TAG)
	{
	if (!isset($MODULE_DATA['TAGS'][$TAG]))continue;
	if ($MODULE_DATA['TAGS'][$TAG]==array())continue;
	
		
		if ($CSS_TAG=='')continue;
		++$N;
		$STR_B.='<div  id="publication_menu_'.$PMID.'_view_'.$N.'" class="container-grey w3-container w3-padding-16" style="display: none;">
		</div>';
		$STR.='<li id="publication_menu_'.$PMID.'_tab_'.$N.'" class="nav-item nav-link" onclick="showMenu(\'publication_menu_'.$PMID.'\','.$N.',${NTOT})">'.ucfirst(strtolower($TAG)).'</li>';
		$STR_J.='loadModule("publication_menu_'.$PMID.'_view_'.$N.'","/CONTENT/PUBLI_INFO/'.$PMID.'/PARAMS/'.$TAG.'");'."\n";
	
	}
	//echo $STR_B."\n".$STR."\n".$STR_J."\n";exit;
	changeValue("publication_portal","BLOCKS_T",$STR_B);
	changeValue("publication_portal","BLOCKS",$STR);
	changeValue("publication_portal","JS",$STR_J);
	changeValue("publication_portal","NTOT",$N);

?>