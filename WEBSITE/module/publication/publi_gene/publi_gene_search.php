<?php

$STR='';

	// foreach ($MODULE_DATA['RESULTS'] as &$PUBLI)
	// {
	// 	$USER_INPUT['PAGE']['VALUE']=$PUBLI;
	// 	$STR.=loadHTMLAndRemove('PUBLICATION');
	// }

	if ($MODULE_DATA['RESULTS']!=array())
	{
	$USER_INPUT['PAGE']['VALUE']=implode("_",$MODULE_DATA['RESULTS']);
	
	
$STR.=loadHTMLAndRemove('PUBLICATION_BATCH');

	changeValue("publi_gene_search","PUBLI",$STR);	
	}else changeValue("publi_gene_search","PUBLI",'<div class="alert alert-info">No publication retrieved with these parameters');	
?>