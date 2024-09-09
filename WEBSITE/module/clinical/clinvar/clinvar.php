<?php

if ($MODULE_DATA['ENTRY']==array())
{
	removeBlock("clinvar","VALID");
}else removeBlock("clinvar","INVALID");

changeValue('clinvar','CLINVAR_ID',$MODULE_DATA['ENTRY']['CLINV_IDENTIFIER']);
changeValue('clinvar','CLINVAR_STATUS',$MODULE_DATA['ENTRY']['STATUS']);
changeValue('clinvar','CLINVAR_NAME',$MODULE_DATA['ENTRY']['TITLE']);
print_r($MODULE_DATA);
$STR='';$STR_REF='';
foreach ($MODULE_DATA['ASSERT'] as &$ASSERT)
{
	$STR_A=file_get_contents('module/'.$CURRENT_MODULE['LOC'].'/clinvar_assert.html');
	$STR_A=replaceTag('IS_REF',($ASSERT['ENTRY']['IS_RCV']=='T')?'Reference':'',$STR_A);
	$NAME=$ASSERT['ENTRY']['ASSERT_ACC'].'.'.$ASSERT['ENTRY']['ASSERT_VERSION'];
	$STR_A=replaceTag('ACC_ID','<a target="_blank" href="'.str_replace('${LINK}',$NAME,$GLB_CONFIG['LINK']['CLINVAR']['RCV']).'">'.$NAME.'</a>',$STR_A);
	$STR_A=replaceTag('STATUS',$ASSERT['ENTRY']['ASSERT_STATUS'],$STR_A);
	$STR_A=replaceTag('TYPE',$ASSERT['ENTRY']['ASSERT_TYPE'],$STR_A);
	$STR_A=replaceTag('DATE_CREATED',$ASSERT['ENTRY']['DATE_CREATED'],$STR_A);
	$STR_A=replaceTag('DATE_UPDATED',$ASSERT['ENTRY']['DATE_UPDATED'],$STR_A);
	if ($ASSERT['ENTRY']['CLIN_SIGN_DESC']=='Pathogenic')
	$STR_A=replaceTag('CLIN_SIGN_DESC','<span class="red_c">'.$ASSERT['ENTRY']['CLIN_SIGN_DESC'].'</span>',$STR_A);
	else $STR_A=replaceTag('CLIN_SIGN_DESC',$ASSERT['ENTRY']['CLIN_SIGN_DESC'],$STR_A);
	$STR_A=replaceTag('CLIN_SIGN_EVAL',$ASSERT['ENTRY']['CLIN_SIGN_STATUS'].' ('.$ASSERT['ENTRY']['CLIN_SIGN_DATE'].')',$STR_A);
	
	$MEAS_STR='';
	if (isset($ASSERT['MEAS']))
	foreach ($ASSERT['MEAS'] as &$MEASURE)
	{
		$NAME=$MEASURE['ENTRY']['ASSERT_MEASURE_ACC'].'.'.$MEASURE['ENTRY']['ASSERT_MEASURE_VERSION_ID'];
		$MEAS_STR.='<div><h2>Allele Description</h2><table style="width:100%" class="table"><tr>
		<th>Variant ID:</th><td><a target="_blank" href="'.str_replace('${LINK}',$NAME,$GLB_CONFIG['LINK']['CLINVAR']['VARIATION']).'">'.$NAME.'</a></td>
		<th>Type:</th><td>'.$MEASURE['ENTRY']['CLINV_MEASURE_TYPE'].'</td></tr>
		<tr><th>Name:</th><td>'.$MEASURE['ENTRY']['CLINV_MEASURE_NAME'].'</td><th>SPDI:</th><td>'.$MEASURE['ENTRY']['CANONICAL_SPDI'].'</td></tr>
		</table><br/><h2>Impact on gene:</h2>';
		if (!isset($MEASURE['GNMAP']) || count($MEASURE['GNMAP'])==0){$MEAS_STR.='No impact reported<br/>';}
		else 
		{
				$MEAS_STR.='<table class="table"><thead><tbody><tr><th>Symbol</th><th>Name</th><th>Gene ID</th><th>Type</th><th>Relationship</th></tr></thead><tbody>';
		foreach ($MEASURE['GNMAP'] as $GNM)
		{

			$MEAS_STR.='<tr><th  class="boldright">'.$GNM['SYMBOL'].'</td><td>'.$GNM['FULL_NAME'].'</td><td><a href="/GENEID/'.$GNM['GENE_ID'].'">'.$GNM['GENE_ID'].'</a></td><td>'.$GNM['GENE_TYPE'].'</td><td>'.$GNM['RELATIONSHIP'].'</td></tr>';
		}
		$MEAS_STR.='</tbody></table>';
		}
		$MEAS_STR.='</div>';
	}
	if (isset($ASSERT['TRAIT']))
	foreach ($ASSERT['TRAIT'] as $TRAIT)
		{
			$MEAS_STR.='<div><h2>Condition</h2><table class="table"><tr><th class="boldright" style="width:25%">Condition type:</th><th> '.$TRAIT['ENTRY']['CLINV_TRAIT_TYPE'].'</th></tr>';
			$ALT_NAME='';
			$EXT=array();
			foreach ($TRAIT['NAME'] as $T)
			{
			if ($T['NTYPE']=='P'){$MEAS_STR.='<tr><th class="boldright">Name:</th><td>'.$T['NAME'].'</td></tr>';}
			else $ALT_NAME.=$T['NAME'].' ';
			foreach ($T['EXTDB'] as $K)
			$EXT[$K[0]][]=$K[1];
			}
			if ($ALT_NAME!='') $MEAS_STR.= '<tr><th class="boldright">Synonyms:</th><td>'.$ALT_NAME.'</td></tr>';
			$EXT_STR='';
			foreach ($EXT as $K=>$V)
			{
				$V=array_unique($V);
				foreach ($V as $F=>$P){if (isset($GLB_CONFIG['LINK']['CLINVAR'][strtoupper($K)]))$V[$F]='<a target="_blank" href="'.str_replace('${LINK}',$P,$GLB_CONFIG['LINK']['CLINVAR'][strtoupper($K)]).'">'.$P.'</a>';}
				$EXT_STR.=$K.': '.implode(',',$V).'<br/>';
			}
			$MEAS_STR.='<tr><th class="boldright">Links:</th><td>'.$EXT_STR.'</td></tr></table></div>';
		}
	$STR_A=replaceTag('MEASURE',$MEAS_STR,$STR_A);
		if($ASSERT['ENTRY']['IS_RCV']=='T')$STR_REF=$STR_A;
	else $STR.=$STR_A;

}

changeValue('clinvar','ASSERT',$STR);
changeValue('clinvar','REF_ASSERT',$STR_REF);



if (in_array('NO_MUTATION',$USER_INPUT['PARAMS']))removeBlock('clinvar','NO_MUTATION');
else 
{
	$STR='';
	foreach ($MODULE_DATA['MUT'] as $MUT)
	{
		$USER_INPUT['PAGE']['VALUE']=$MUT['RSID'];
		$USER_INPUT['PARAMS'][0]='NO_CLINICAL';
		$STR.=loadHTMLAndRemove('MUTATION');
	}
	
	changeValue('clinvar','MUTATION',$STR);
	
}

	$STR='';
	foreach ($MODULE_DATA['PUB'] as $PUB)
	{
		$USER_INPUT['PAGE']['VALUE']=$PUB['PMID'];
		$STR.=loadHTMLAndRemove('PUBLICATION');
	}
	
	changeValue('clinvar','PUBLI',$STR);
	



?>