<?php

$STR='';	

	if ($MODULE_DATA['RESULTS']!=array())
	{	
			
		$USER_INPUT['PARAMS']=array(0=>'RID', 1=>implode('_',$MODULE_DATA['RESULTS']));	
		changeValue("publi_news_search","PUBLI",loadHTMLAndRemove('NEWS_BATCH'));	
	}
	else {
	 changeValue("publi_news_search","PUBLI",'<div class="alert alert-info">No News retrieved with these parameters');	
	}	
?>