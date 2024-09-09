<?php

/*
	<option value="PUBLICATION_SEARCH/PUBLI_ANY">Any field</option>
							<option value="PUBLICATION_SEARCH/PUBLI_TITLE">Title</option>
							<option value="PUBLICATION_SEARCH/PUBLI_AUTHOR">Author</option>
							<option value="PUBLICATION_SEARCH/PUBLI_INSTIT">Institution</option>
							<option value="PUBLICATION_SEARCH/PUBLI_PMID">Pubmed ID</option>
							<option value="PUBLICATION_SEARCH/PUBLI_DOI">DOI</option>
							<option value="PUBLICATION_SEARCH/PUBLI_PUBMED">Pubmed Search</option>
*/
ini_set('memory_limit','1000M');

$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['PUBLI']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';
$LIST_PUBLI=array();
$SEARCH_TYPE='PUBLI_ANY';
$SOURCE='';
try{
	
$LIST_TYPES=array('PUBLI_ANY','PUBLI_TITLE','PUBLI_AUTHOR','PUBLI_INSTIT','PUBLI_PMID','PUBLI_ORCID','PUBLI_DOI','PUBLI_PUBMED');
if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		 if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}


if ($SEARCH_TYPE=='PUBLI_ANY'||$SEARCH_TYPE=='PUBLI_TITLE'){


	$res=publi_portal_title($SEARCH_VALUE);
	
	foreach ($res as $line){
		if (!isset($LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]))$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]=array();
		$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])][$line['PMID']]=1;
	}
	
	$result['TIME']['Title']=microtime_float()-$time;$time=microtime_float();

}
if ($SEARCH_TYPE=='PUBLI_ANY'||$SEARCH_TYPE=='PUBLI_AUTHOR'){
	
		$res=publi_portal_author($SEARCH_VALUE);
		foreach ($res as $line){
			if (!isset($LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]))$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]=array();
		$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])][$line['PMID']]=1;
		}
		$result['TIME']['Author']=microtime_float()-$time;$time=microtime_float();
	
	}

	if ($SEARCH_TYPE=='PUBLI_ANY'||$SEARCH_TYPE=='PUBLI_INSTIT'){
	
		$res=publi_portal_instit($SEARCH_VALUE);
		foreach ($res as $line){
			if (!isset($LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]))$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]=array();
		$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])][$line['PMID']]=1;
		}
		$result['TIME']['Institution']=microtime_float()-$time;$time=microtime_float();
	
	}

	if ($SEARCH_TYPE=='PUBLI_ANY'||$SEARCH_TYPE=='PUBLI_PMID'){
	
		
		$res=publi_portal_pmid($SEARCH_VALUE);
		
		foreach ($res as $line){
			if (!isset($LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]))$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]=array();
		$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])][$line['PMID']]=1;
		}
		$result['TIME']['Institution']=microtime_float()-$time;$time=microtime_float();
	
	}
	if ($SEARCH_TYPE=='PUBLI_ANY'||$SEARCH_TYPE=='PUBLI_DOI'){
	
		
		$res=publi_portal_doi($SEARCH_VALUE);
		
		foreach ($res as $line){
			if (!isset($LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]))$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]=array();
		$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])][$line['PMID']]=1;
		}
		$result['TIME']['DOI']=microtime_float()-$time;$time=microtime_float();
	
	}
	if ($SEARCH_TYPE=='PUBLI_ANY'||$SEARCH_TYPE=='PUBLI_ORCID'){
	
		
		$res=publi_portal_orcid($SEARCH_VALUE);
		
		foreach ($res as $line){
			if (!isset($LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]))$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])]=array();
		$LIST_PUBLI[strtotime($line['PUBLICATION_DATE'])][$line['PMID']]=1;
		}
		$result['TIME']['DOI']=microtime_float()-$time;$time=microtime_float();
	
	}

	

	if ($SEARCH_TYPE=='PUBLI_ANY'||$SEARCH_TYPE=='PUBLI_PUBMED')
	{
		$SEARCH_VALUE=htmlentities(strip_tags( trim($USER_INPUT['PAGE']['VALUE'])));
		$SEARCH_RESULTS['JOB_ID']=submitJob('search_pubmed',array('QUERY'=>$SEARCH_VALUE),'search_pubmed','search_pubmed');
		print_r($SEARCH_RESULTS['JOB_ID']);exit;
	}

	krsort($LIST_PUBLI,SORT_NUMERIC);
	$SEARCH_RESULTS['PUBLI']=array();
	foreach ($LIST_PUBLI as $DATE=>&$LIST)
	foreach ($LIST as $PMID=>&$D)$SEARCH_RESULTS['PUBLI'][]=$PMID;
	
	
	

	if (count($SEARCH_RESULTS['PUBLI'])==1)
	{
		removeBlock("search_publi","MULTI");
		changeValue("search_publi","PUBMED_ID",$SEARCH_RESULTS['PUBLI'][0]);
	}
	else
	{
		changeValue("search_publi","result",json_encode($SEARCH_RESULTS['PUBLI']));
		changeValue("search_publi","COUNT",count($SEARCH_RESULTS['PUBLI']));
		changeValue("search_publi","NPAGE",ceil(count($SEARCH_RESULTS['PUBLI'])/10));
		changeValue("search_publi","QUERY_NAME",$SEARCH_VALUE);
		removeBlock("search_publi","SINGLE");

		$result['count']=count($SEARCH_RESULTS['PUBLI']);
	}
 removeBlock("search_publi","INVALID");
}
catch(Exception $e)
{
	removeBlock("search_publi","SINGLE");
	removeBlock("search_publi","MULTI");
}


cleanRules("search_publi");
if ($USER_INPUT['VTYPE']=='JSON'){
	if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");

	echo json_encode($SEARCH_RESULTS['PUBLI']);
	exit;
}
 $result['code']=$HTML["search_publi"];

 if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
 	echo json_encode($result);
 	exit;



?>
