<?php

$STR='';

	foreach ($MODULE_DATA['RESULTS']['LIST'] as &$PUBLI)
	{
		$USER_INPUT['PAGE']['VALUE']=$PUBLI;
		$STR.=loadHTMLAndRemove('PUBLICATION');
	}

	changeValue("publi_topic_search","PUBLI",$STR);	

?>