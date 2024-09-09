<?php
 if (!defined("BIORELS")) header("Location:/");
 $ORDER=array();
 $MODULE_DATA=array();
 if ($USER_INPUT['PAGE']['VALUE']!=array() && $USER_INPUT['PAGE']['VALUE']!='')
 {
$ORDER=explode("_",$USER_INPUT['PAGE']['VALUE']);
 }
else
{
if (isset($USER_INPUT['PARAMS'])&&$USER_INPUT['PARAMS']!=array())
{
	for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
	{
		if ($USER_INPUT['PARAMS'][$I]=='RID')$ORDER=explode("_",$USER_INPUT['PARAMS'][$I+1]);
	}
}
}


$PMIDs=array();
$POS=-1;
foreach ($ORDER as $PMID)
{
if (!preg_match("/[0-9]{2,11}/",$PMID))throw new Exception("Wrong format for publication ".$PMID,ERR_TGT_USR);
$PMIDS[]=$PMID;++$POS;

}

$MODULE_DATA=loadBatchPublicationData($PMIDS);
$MODULE_DATA['ORDER']=$ORDER;
foreach ($PMIDS as $ID)
{
	$FOUND=false;
	foreach ($MODULE_DATA as &$ENTRY)
	{
		if ($ENTRY['ENTRY']['PMID']==$ID){$FOUND=true;break;}
	}
	if ($FOUND)continue;
	$MODULE_DATA[]=array('ENTRY'=>array('PMID'=>$ID),'ISSUE'=>'NOT FOUND');
}



?>