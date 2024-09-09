<?php


$SEARCH_VALUE=htmlentities(strip_tags( trim(str_replace("___","/",$USER_INPUT['PAGE']['VALUE']))));
$SEARCH_RESULTS['COMPANY']=array();
$time=microtime_float();
$result['TIME']=array();
$FIRST=true;
$SP='ALL';


$SEARCH_TYPE='COMPANY_ANY';
$SOURCE='';
 try{
$LIST_TYPES=array('COMPANY_ANY','COMPANY_NAME');

if (isset($USER_INPUT['PARAMS']) && count($USER_INPUT['PARAMS'])>0)
{
	
	foreach ($USER_INPUT['PARAMS'] as $VAL)
	{
		 if (in_array($VAL,$LIST_TYPES))$SEARCH_TYPE=$VAL;
	}
}

if ($SEARCH_TYPE=='COMPANY_ANY'||$SEARCH_TYPE=='COMPANY_NAME'){

	
	$res=getCompanyByName($SEARCH_VALUE);
	
	$SEARCH_RESULTS['COMPANY']=$res;
	
	
	$result['TIME']['COMPANY_ID']=microtime_float()-$time;$time=microtime_float();

}

if (count($SEARCH_RESULTS['COMPANY'])==1)
{
	removeBlock("search_company","MULTI");
		foreach ($SEARCH_RESULTS['COMPANY'] as $K=>&$V)
	changeValue("search_company","ADDON",'/COMPANY/'.$K);
	

}
else
{
//changeValue("search_company","result",$RESULTS);
changeValue("search_company","result",str_replace("\\","\\\\",json_encode(array_values($SEARCH_RESULTS['COMPANY']))));
		changeValue("search_company","COUNT",count($SEARCH_RESULTS['COMPANY']));
		changeValue("search_company","NPAGE",ceil(count($SEARCH_RESULTS['COMPANY'])/10));
		changeValue("search_company","QUERY_NAME",$SEARCH_VALUE);
		removeBlock("search_company","SINGLE");

		$result['count']=count($SEARCH_RESULTS['COMPANY']);
removeBlock("search_company","SINGLE");

$result['count']=count($SEARCH_RESULTS['COMPANY']);
}
removeBlock("search_company","INVALID");
}
catch(Exception $e)
{
	removeBlock("search_company","SINGLE");
	removeBlock("search_company","MULTI");
}


cleanRules("search_company");
if ($USER_INPUT['VTYPE']=='JSON'){
	if (ob_get_contents())ob_end_clean();
	header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
	echo json_encode($SEARCH_RESULTS['COMPANY']);exit;}
$result['code']=$HTML["search_company"];

if (ob_get_contents())ob_end_clean();
header('Content-type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
	echo json_encode($result);
	exit;
?>