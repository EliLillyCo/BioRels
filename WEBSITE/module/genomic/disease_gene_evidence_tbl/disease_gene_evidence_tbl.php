<?php

if (!defined("BIORELS")) header("Location:/");

$str='';

$ORDER=array('t','a','i','m','r','d','f','c','o','s');


if (isset($MODULE_DATA['ERROR']))
{
	changeValue("disease_gene_evidence_tbl","table",'<div class="alert alert-info">'.$MODULE_DATA['ERROR'].'</div>');
	return;
}
else if (!isset($MODULE_DATA['RESULTS']))
{
	changeValue("disease_gene_evidence_tbl","table",'<div class="alert alert-info">No evidence found</div>');
	return;
}



foreach ($MODULE_DATA['RESULTS'] as &$ENTRY)
{
	if (!isset($ENTRY['TXT']))continue;
	$str.='<div><div  class="w3-col s1 center w3-col-600"><span style="font-weight:bold">'.$ENTRY['PMID'].'</span>
	<br/><span style="font-size:0.7em">Score: '.$ENTRY['OT_SCORE'].
	'<br/>'.count($ENTRY['TXT']).' Evidence'.((count($ENTRY['TXT'])==1)?'':'s').'</span><br/>
	<div class="plus radius" style="--l:17px;--t:1.2px;--s:4px;margin-left:10px" onclick="togglePubView(\'PUB_DISEASE_'.$ENTRY['PMID'].'\',this)"></div>
	</div><div class="w3-col s11 w3-col-600">';
	$USER_INPUT['PAGE']['VALUE']=$ENTRY['PMID'];
	$str.=loadHTMLAndRemove("PUBLICATION");
	$str.='<div id="PUB_DISEASE_'.$ENTRY['PMID'].'" style="display:none">';
	foreach ($ORDER as $O)
	{
		if (!isset($ENTRY['TXT'][$O]))continue;
	$LIST=&$ENTRY['TXT'][$O];
	{
		$str.='<h3>';
		switch ($O)
		{
			case 'o':$str.='Other:';break;
			case 'r':$str.='Results:';break;
			case 'a':$str.='Abstract:';break;
			case 'i':$str.='Introduction:';break;
			case 'm':$str.='Methods:';break;
			case 't':$str.='Title:';break;
			case 'f':$str.='Figure:';break;
			case 'c':$str.='Conclusion:';break;
			case 'd':$str.='Discussion:';break;
			case 's':$str.='Supplementary:';break;
		}
		$str.='</h3>';
		foreach ($LIST as $L)
		$str.='<p class="w3-justify">'.$L.'</p>';
	}
}
	
	$str.='</div></div></div>';
}

changeValue("disease_gene_evidence_tbl","table",$str);
?>