<?php
if (!defined("BIORELS")) header("Location:/");


$LINK='';
if ($MODULE_DATA['INPUT']=='GENE')
{
	$LINK='/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/';
	changeValue('pathways','ENTRY_NAME',$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
}
else 
{
	$LINK='/UNIPROT_ID/'.$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER'].'/';
	changeValue('pathways','ENTRY_NAME',$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
	
}
changeValue('pathways','LINK',$LINK);


if (isset($MODULE_DATA['ERROR']))
{
	removeBlock("pathways",'VALID');
	removeBlock("pathways",'INVALID');
	return;
}

if(!isset($MODULE_DATA['PATHWAYS']))
{
	removeBlock("pathways","VALID");
	removeBlock("pathways","ERROR");
	return;
}
else 
{
	removeBlock("pathways","INVALID");
	removeBlock("pathways","ERROR");
	
}

$TREE=array();

foreach ($MODULE_DATA['PATHWAYS'] as $P)
{
	
	
	foreach ($P['Lineage'] as $K=>&$L)
	{
		//echo $L['REAC_ID']."\t".$L['PW_LEVEL']."\t".$L['LEVEL_LEFT']."\t".$L['LEVEL_RIGHT']."\n";;
		
		$IDENTICAL=false;$RANGE=100000000;$RANGE_ID=-1;
		foreach ($TREE as $TK=>&$R)
		{
			if ($TK=='INI')continue;
			//echo "\tTEST:".$R['REAC_ID']."\t".$R['PW_LEVEL']."\t".$R['LEVEL_LEFT']."\t".$R['LEVEL_RIGHT']."\n";;
			if ($R['REAC_ID']==$L['REAC_ID'] && $L['LEVEL_LEFT']==$R['LEVEL_LEFT'] &&$L['LEVEL_RIGHT']==$R['LEVEL_RIGHT']){$IDENTICAL=true;break;}
			if ($L['LEVEL_RIGHT']<$R['LEVEL_LEFT'])continue;
			if ($L['LEVEL_LEFT']>$R['LEVEL_RIGHT'])continue;
			$DIFF=$R['LEVEL_RIGHT']-$L['LEVEL_RIGHT'] + $L['LEVEL_LEFT']-$R['LEVEL_LEFT'];
			if ($DIFF < $RANGE){$RANGE=$DIFF;$RANGE_ID=$TK;}
		}
		//echo "ID:".$IDENTICAL."\t".$RANGE."\t".$RANGE_ID."\n####\n";
		if ($IDENTICAL){continue;}
		if ($L['PW_LEVEL']==1){$TREE['INI'][$L['REAC_ID']]=true;$TREE[$L['REAC_ID']]=$L;continue;}
		$TREE[$L['REAC_ID']]=$L;
		if ($RANGE_ID==$L['REAC_ID'])continue;
		$TREE[$RANGE_ID]['CHILD'][]=$L['REAC_ID'];

	}
	//genTree($TREE,$P['Lineage'],0,$P);
}

function printTree(&$TREE,$ENTRIES,&$STR,&$COUNT,$LEVEL,&$GENEID,$LINK)
{
	
	foreach ($ENTRIES as $ID)
	{
		$V=&$TREE[$ID];
		//echo "V:".$LEVEL."\t".$ID."\n";
		
		//if ($V['PW_LEVEL']!=1)continue;
		$HAS_CHILD=(isset($V['CHILD']) && count($V['CHILD'])>0);
	//	print_r($V);
	++$COUNT;
	$STR.='<tr id="pathways_'.$GENEID.'_'.$COUNT.'" class="LEV'.$LEVEL.' " style="display:'.(($LEVEL==0)?'table-row':'none').'">
	<td style="padding-left:'.(20*$LEVEL+(($HAS_CHILD)?0:17)).'px">';
	if ($HAS_CHILD)$STR.='<div class="plus radius" style="--l:17px;--t:1.2px;--s:4px" onclick="toggleTreeView(this,'.($COUNT).',${LEV_'.$ID.'},'.$LEVEL.',\'pathways_'.$GENEID.'\')"></div>';
	$STR.='<a class="blk_font" href="'.$LINK.'PATHWAY/'.$V['REAC_ID'].'">'.$V['REAC_ID'].'</a></td><td>'.$V['PW_NAME'].'</td><td><a class="blk_font"  href="https://reactome.org/content/detail/'.$V['REAC_ID'].'">Details</a></td></tr>';
	
	if (isset($V['CHILD']) && count($V['CHILD'])>0)printTree($TREE,$V['CHILD'],$STR,$COUNT,$LEVEL+1,$GENEID,$LINK);
	
	$STR=str_replace('${LEV_'.$ID.'}',$COUNT,$STR);
	}
	
}

$count=0;
$level=0;
//echo "<pre>";
if (isset($TREE['INI']) && is_array($TREE['INI']) &&$TREE['INI']!=array())
printTree($TREE,array_keys($TREE['INI']),$STR,$count,$level,$GENEID,$LINK);
//print_r($TREE);exit;
/*
$STR='';
foreach  ($MODULE_DATA['PATHWAYS'] as $PATHWAY)
{
$STR.='<tr><td><a href="/GENEID/'.$GENEID.'/PATHWAY/'.$PATHWAY['REAC_ID'].'">'.$PATHWAY['REAC_ID'].'</a></td>
<td>'.$PATHWAY['PW_NAME'].'</td>
<td><a href="https://reactome.org/content/detail/'.$PATHWAY['REAC_ID'].'">Details</a></td></tr>';

}*/
changeValue('pathways','TBL',$STR);



$PUBS=array(31691815,29377902,29186351,29077811,28249561,28150241);
$STR='';
foreach ($PUBS as $PUB)
{
	$USER_INPUT['PAGE']['VALUE']=$PUB;
	$STR.=loadHTMLAndRemove('PUBLICATION');
}	

changeValue('pathways','REAC_SOURCE',$STR);

?>