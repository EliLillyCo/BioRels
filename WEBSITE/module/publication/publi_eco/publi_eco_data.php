<?php
 if (!defined("BIORELS")) header("Location:/");




$PMID=$USER_INPUT['PAGE']['VALUE'];

$results=preg_match_all("/(ECO[:_]([0-9]{7})\|){0,1}PubMed:([0-9]{4,9})/",$PMID,$matches);
print_r($matches);
$MODULE_DATA=array();


if (count($matches)>0)
{
	$LIST_ID=array();
	for ($I=0;$I<count($matches[0]);$I+=1)
	{
		
		if ($matches[3][$I]!='' && $matches[2][$I]!=''){
			$LIST_ID[$matches[2][$I]]=true;
		$MODULE_DATA[$I]['ECO']=array('ID'=>$matches[2][$I]);
		$MODULE_DATA[$I]['PUBLI']=loadSimplePublicationData($matches[3][$I]);
		
		}
		else if ($matches[3][$I]!='' && $matches[2][$I]=='')
		{
			$MODULE_DATA[$I]['ECO']=array('ID'=>'');
			$MODULE_DATA[$I]['PUBLI']=loadSimplePublicationData($matches[3][$I]);
		}
	}

	if (count($LIST_ID)!=0)	{
	$IDS=getEcoInfo(array_keys($LIST_ID));
	
	foreach ($MODULE_DATA as $I=>&$INFO)
	{
		if (isset($IDS['ECO_'.$INFO['ECO']['ID']]))$INFO['ECO']=$IDS['ECO_'.$INFO['ECO']['ID']];
		else if (isset($IDS[$INFO['ECO']['ID']]))$INFO['ECO']=$IDS[$INFO['ECO']['ID']];
	}
}
}




?>