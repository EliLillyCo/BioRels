<?php

if (!defined("BIORELS")) header("Location:/");

$tab=explode("-",$USER_INPUT['PAGE']['VALUE']);
$TMP=getPDBResInfo($tab[0],$tab[1], $tab[3],$tab[2]);
$MODULE_DATA=array('RES_INFO'=>$TMP['RES_INFO'],'INTERS'=>$TMP['INTERS'],'ENTRY_INFO'=>$TMP['CHAIN_INFO']['ENTRY']);

$MAP=array();
foreach ($TMP['CHAIN_INFO']['UN_SEQ'] as $UN_SEQ_ID=>&$UN_SEQ_INFO)	$MAP[$UN_SEQ_INFO['INFO']['ISO_ID']]=$UN_SEQ_ID;

foreach ($TMP['CHAIN_INFO']['CHAIN'] as $CHID=>&$CH_INFO) {
	$CH_NAME=$CH_INFO['CHAIN_NAME'];
	$MODULE_DATA['CHAIN'][$CH_NAME]['RES']=$CH_INFO['RES'];
	$SEQ_MAP=array();
	$RANGE=array();$NR=-1;$ONGOING=false;
	$ORDER=array();$POS=0;
	foreach ($CH_INFO['RES'] as $RID=>&$RINFO) {
		$ORDER[$POS]=$RID;++$POS;
		if ($RINFO[4]==''){$ONGOING=false;continue;}
		$SEQ_MAP[$RINFO[4]][$RINFO[1]]=$RID;
		if (!$ONGOING){++$NR;$RANGE[$NR]['START']=$RINFO[1];$ONGOING=true;}
		$RANGE[$NR]['END']=$RINFO[1];
	}
	$INTERS=array();
	foreach ($TMP['INTERS'] as &$INT) if ($INT['CHAIN_NAME']==$CH_INFO['CHAIN_NAME'])$INTERS[]=$INT;
	$MODULE_DATA['CHAIN'][$CH_NAME]['INTER']=$INTERS;
	$MODULE_DATA['CHAIN'][$CH_NAME]['ORDER']=$ORDER;
	 
	foreach ($SEQ_MAP as $ISO_ID=>&$RES_MAP) {
		$UN_ENTRY=&$TMP['CHAIN_INFO']['UN_SEQ'][$CH_INFO['INFO'][$ISO_ID]['UN_SEQ_ID']];
		$FTS=array();
		foreach ($UN_ENTRY['FT']['FEATS'] as &$FTI) {
			foreach ($RANGE as $RR) {
				if ($FTI['START']>$RR['END'])continue;
				if ($FTI['END']<$RR['START'])continue;
				$ST=max($FTI['START'],$RR['START']);
				$EN=min($FTI['END'],$RR['END']);
				if (!isset($SEQ_MAP[$ISO_ID][$ST]))continue;
				if (!isset($SEQ_MAP[$ISO_ID][$EN]))continue;
				$START=$SEQ_MAP[$ISO_ID][$ST];
				$END=$SEQ_MAP[$ISO_ID][$EN]	;
				
				$FTS[]=array($START,$END,$FTI['VALUE'],$FTI['TYPE']);
			}			
		}
		$DOMS=array();
		foreach ($UN_ENTRY['DOM'] as &$DOM) {	
			foreach ($RANGE as $RR) {
				if ($DOM['POS_START']>$RR['END'])continue;
				if ($DOM['POS_END']<$RR['START'])continue;
				$ST=max($DOM['POS_START'],$RR['START']);
				$EN=min($DOM['POS_END'],$RR['END']);
				$START=$SEQ_MAP[$ISO_ID][$ST];
				$END=$SEQ_MAP[$ISO_ID][$EN]	;
				
				$DOMS[]=array($START,$END,$DOM['DOMAIN_NAME'],$DOM['DOMAIN_TYPE']);
			}			
		}
		
		$MODULE_DATA['CHAIN'][$CH_NAME]['UNIP'][$ISO_ID]=array('STAT'=>$CH_INFO['INFO'][$ISO_ID],'INFO'=>$UN_ENTRY['INFO'],'RANGE'=>$RANGE,'FTS'=>$FTS,'DOM'=>$DOMS,'FT_TYPE'=>$UN_ENTRY['FT']['FEAT_TYPE']);
	}
}

?>