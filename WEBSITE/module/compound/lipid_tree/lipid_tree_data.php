<?php

if (!defined("BIORELS")) header("Location:/");





if (!isset($USER_INPUT['PARAMS']) ||$USER_INPUT['PARAMS']==array())
{
	
$ENTRY=getLipidOntologyEntry('Lipid');

$MODULE_DATA=array();
$MODULE_DATA[]=array("id"=>$ENTRY['LIPID_TAG'],'parent'=>'#','text'=>$ENTRY['LIPID_NAME'],'data'=>array('level'=>$ENTRY['LIPID_LEVEL']));

$CHILDS=getLipidChildOntology($ENTRY['LIPID_ENTRY_ID'],$ENTRY['LIPID_LEVEL']);
foreach ($CHILDS as $CHILD)
{
$MODULE_DATA[]=array("id"=>$CHILD['LIPID_TAG'],'parent'=>$ENTRY['LIPID_TAG'],'text'=>ucfirst($CHILD['LIPID_NAME']),'children'=>true,'data'=>array('level'=>$CHILD['LIPID_LEVEL']/*,'left'=>$CHILD['EFO_LEVEL_LEFT'],'right'=>$CHILD['EFO_LEVEL_RIGHT']*/));
}
}
else if (count($USER_INPUT['PARAMS'])==2)
{
	$NAME=$USER_INPUT['PARAMS'][0];
	$LEVEL=$USER_INPUT['PARAMS'][1];
	$ENTRY=getLipidOntologyEntry($NAME,true);
	
	$TMP=getLipidChildOntology($ENTRY['LIPID_ENTRY_ID'],$LEVEL);
	$CHILDS=array();
	foreach ($TMP as $T)
	{
		if (!isset($T['SM']))$CHILDS[$T['LIPID_TAG']]=$T;
		else $CHILDS[$T['SM_NAME']]=$T;
	}
	
	
foreach ($CHILDS as $CHILD)
{
	if (isset($CHILD['SM']))
		{
			$MODULE_DATA[]=array("id"=>$CHILD['SM_NAME'],'parent'=>$NAME,'text'=>ucfirst($CHILD['SM_NAME']), 'children'=>false,'data'=>array('SMI'=>$CHILD['SMILES']));
			//$MODULE_DATA['ST'][$CHILD['SM_NAME']]=$CHILD['SMILES'];
			//.' <div style="max-width:250px;position:relative;width:250px;display:inline-block;margin:10px;padding:5px;vertical-align:top;height:250px;" class="boxShadow" id="P_CPD_IMG_'.$CHILD['SM_NAME'].'">	<div id="CPD_IMG_5757" style="margin: 0px auto; width: fit-content; position: absolute; top: 8.5px;"></div></div>'
		}
	else $MODULE_DATA[]=array("id"=>$CHILD['LIPID_TAG'],'parent'=>$ENTRY['LIPID_TAG'],'text'=>ucfirst($CHILD['LIPID_NAME']),'data'=>array('level'=>$CHILD['LIPID_LEVEL']/*,'left'=>$CHILD['EFO_LEVEL_LEFT'],'right'=>$CHILD['EFO_LEVEL_RIGHT']*/),'children'=>($CHILD['LIPID_LEVEL_RIGHT']-$CHILD['LIPID_LEVEL_LEFT']>1));
}


}
else if (count($USER_INPUT['PARAMS'])==1)
{
	$NAME=$USER_INPUT['PARAMS'][0];
	
	$ENTRY=getLipidEntry($NAME,true);
	$TMP=getLipidHierarchy($ENTRY['LIPID_ENTRY_ID'],true);
	
	foreach ($TMP as $CHILD)
	{
		if (isset($CHILD['SM']))
		{
			$MODULE_DATA[]=array("id"=>$CHILD['LIPID_NAME'],'parent'=>$NAME,'text'=>ucfirst($CHILD['LIPID_NAME']), 'children'=>false);
		}
	else $MODULE_DATA[]=array("id"=>$CHILD['LIPID_TAG'],'parent'=>isset($CHILD['PARENT'])?$CHILD['PARENT']:"#",'text'=>ucfirst($CHILD['LIPID_NAME']));
	}


// 	$CHILDS=array();
// 	foreach ($TMP as $T)
// 	{
// 		$CHILDS[$T['ONTOLOGY_TAG']]=$T;
// 	}
	
// foreach ($CHILDS as $CHILD)
// {
// $MODULE_DATA[]=array("id"=>$CHILD['LIPID_TAG'],'parent'=>$ENTRY['LIPID_TAG'],'text'=>ucfirst($CHILD['LIPID_NAME']),'data'=>array('level'=>$CHILD['LIPID_LEVEL']/*,'left'=>$CHILD['EFO_LEVEL_LEFT'],'right'=>$CHILD['EFO_LEVEL_RIGHT']*/),'children'=>($CHILD['LIPID_LEVEL_RIGHT']-$CHILD['LIPID_LEVEL_LEFT']>1));
// }


}

/*
{ "id": "ajson1", "parent": "#", "text": "Simple root node" },
                           { "id": "ajson2", "parent": "#", "text": "Root node 2" },
                           { "id": "ajson3", "parent": "ajson2", "text": "Child 1" },
                           { "id": "ajson4", "parent": "ajson2", "text": "Child 2" }, */
?>