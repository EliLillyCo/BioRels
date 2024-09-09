<?php

if (!defined("BIORELS")) header("Location:/");

   
$str='';
for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
{
	if ($USER_INPUT['PARAMS'][$I]!='ID')continue;
	if (!isset($USER_INPUT['PARAMS'][$I+1]))throw new Exception("No Id provided",ERR_TGT_SYS);
	if (!is_numeric($USER_INPUT['PARAMS'][$I+1]))throw new Exception("No Id provided",ERR_TGT_SYS);
	changeValue("publi_eco","ID",$USER_INPUT['PARAMS'][$I+1]);
}

if ($MODULE_DATA==array())
{
	removeBlock("publi_eco","VALID");
	return;
}
removeBlock("publi_eco","INVALID");
	

foreach ($MODULE_DATA as &$ENTRY)
{
	if (isset($ENTRY['PUBLI']['ENTRY']['DOI']) 
	   && $ENTRY['PUBLI']['ENTRY']['DOI']!='')$LINK="href='https://dx.doi.org/".$ENTRY['PUBLI']['ENTRY']['DOI']."'";
	   else $LINK="href='https://pubmed.ncbi.nlm.nih.gov/".$ENTRY['PUBLI']['ENTRY']['PMID']."'";
		if ($ENTRY['ECO']['ECO_ID']!='')
	$str.='<table class="table table-sm"><tr><td rowspan="2" style="width:90px;vertical-align:middle">'.$ENTRY['PUBLI']['ENTRY']['PMID'].'</td>
	<td>'.ucfirst($ENTRY['ECO']['ECO_NAME']).' (<span class="ital">'.$ENTRY['ECO']['ECO_ID'].')</span></td></tr>
		   <tr><td><a target="_blank" '.$LINK.'>'.$ENTRY['PUBLI']['ENTRY']['TITLE'].'</a><br/>'.$ENTRY['PUBLI']['ENTRY']['PUBLICATION_DATE'].' <span class="ital">'.$ENTRY['PUBLI']['ENTRY']['JOURNAL_ABBR'].'</span></td></tr></table>';
		   else
		   $str.='<table class="table table-sm"><tr><td  style="width:90px;vertical-align:middle">'.$ENTRY['PUBLI']['ENTRY']['PMID'].'</td>
	<td><a target="_blank" '.$LINK.'>'.$ENTRY['PUBLI']['ENTRY']['TITLE'].'</a><br/>'.$ENTRY['PUBLI']['ENTRY']['PUBLICATION_DATE'].' <span class="ital">'.$ENTRY['PUBLI']['ENTRY']['JOURNAL_ABBR'].'</span></td></tr></table>';
}
changeValue("publi_eco","CONTENT",$str);
changeValue("publi_eco","COUNT",count($MODULE_DATA));
if (count($MODULE_DATA)>1)changeValue("publi_eco","MULTI",'s');
?>