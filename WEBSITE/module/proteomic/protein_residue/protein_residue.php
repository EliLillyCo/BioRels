<?php

if (!defined("BIORELS")) header("Location:/");


$TEMPLATE=array("nodes"=>array(),"links"=>array());
$MAP=array();
$K=0;

// $res=runQuery('select XR_TPL_ATOM_ID,X,Y,Z,XR_RES_ID FROM XR_ATOM WHERE XR_RES_ID  IN ('.implode(",",array_keys($MODULE_DATA['XRAY'])).') AND XR_TPL_ATOM_ID IN (1095039,1095040,1095041,1095042)');
// foreach ($res as $line)
// {
// 	$DATA[$line['XR_RES_ID']][$line['XR_TPL_ATOM_ID']]=$line;
// }



$STR='';
$STR_INTER='';
foreach ($MODULE_DATA['XRAY'] as &$XR)
{
	$STR.='<tr><td>'.$XR['FULL_COMMON_NAME'].'</td><td>'.$XR['CHAIN_NAME'].'</td><td>'.$XR['POSITION'].'</td><td>'.$XR['EXPR_TYPE'].'</td><td>'.$XR['RESOLUTION'].'</td><td>'.$XR['DEPOSITION_DATE'].'</td><td>'.(isset($XR['INTER'])?count($XR['INTER']):'0').'</td></tr>';
	if (!isset($XR['INTER']))continue;
	foreach ($XR['INTER'] as &$INTER)
	{
		$STR_INTER.='<tr><td>'.$XR['FULL_COMMON_NAME'].'</td><td>'.$XR['CHAIN_NAME'].'</td><td>'.$XR['POSITION'].'</td><td>'.$INTER['ATOM_LIST_1'].'</td><td>'.$INTER['ATOM_LIST_2'].'</td><td>'.$INTER['NAME'].'</td><td>'.$INTER['POSITION'].'</td><td>'.$INTER['CLASS'].'</td><td>'.$INTER['DISTANCE'].'</td><td>'.$INTER['ANGLE'].'</td><td>'.$INTER['INTER_NAME'].'</td></tr>';
	}
}
changeValue("protein_residue","XRAY_LIST",$STR);
changeValue("protein_residue","INTER_LIST",$STR_INTER);



foreach ($MODULE_DATA['TPL']['ATOM'] as $ID=>&$INFO)
{
	
	if ($INFO['ATM_NAME']=='H')continue;
	$MAP[$ID]=$K;
	$TEMPLATE['nodes'][]=array("atom"=>$INFO['NAME'],"size"=>$INFO['ATOMIC_RADIUS']*10);
	++$K;
}
foreach ($MODULE_DATA['TPL']['BD'] as $BD)
{
	if (!isset($MAP[$BD['XR_TPL_ATOM_ID_1']]) ||!isset($MAP[$BD['XR_TPL_ATOM_ID_2']]))continue;
	$TEMPLATE['links'][]=array("source"=>$MAP[$BD['XR_TPL_ATOM_ID_1']],"target"=>$MAP[$BD['XR_TPL_ATOM_ID_2']],'bond'=>$BD['BOND_TYPE']);
}
changeValue("protein_residue","TEMPLATE",json_encode($TEMPLATE));

?>