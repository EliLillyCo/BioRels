<?php

$STR='';

	// foreach ($MODULE_DATA['RESULTS'] as &$PUBLI)
	// {
	// 	$USER_INPUT['PAGE']['VALUE']=$PUBLI;
	// 	$STR.=loadHTMLAndRemove('PUBLICATION');
	// }
	$USER_INPUT['PAGE']['VALUE']=implode("_",$MODULE_DATA['RESULTS']);
$STR.=loadHTMLAndRemove('PUBLICATION_BATCH');

	changeValue("publi_disease_search","PUBLI",$STR);	

?>