<?php

if (!defined("BIORELS")) header("Location:/");

$RANGES=array();
foreach ($MODULE_DATA['GENE_LOC'] as $CHR_ID=>&$CHR_INFO)
{

	$MAX=0;
	$MIN=100000000000000000000000;
	foreach ($CHR_INFO as $GENE_ID=>&$INFO_GENE)
	{
		if ($GENE_ID=='CHR_SEQ')continue;
		foreach ($INFO_GENE as $T=>&$G)
		{
			
			
			
				$MAX=max($MAX,$G['END_POS']);
				$MIN=min($MIN,$G['START_POS']);
				$MAX=max($MAX,$G['END_POS']);
				$MIN=min($MIN,$G['START_POS']);
			
		}
		
	}
	$RANGE=max($MAX,$MIN)-min($MAX,$MIN);
$RATIO=100/$RANGE;
	$RANGES[$CHR_ID]=array('MIN'=>$MIN,'MAX'=>$MAX,'RANGE'=>$RANGE,'RATIO'=>$RATIO);

}



//echo "RANGE:".$MIN."\t".$MAX."\t".$RANGE."\n";
$STR='';
foreach ($MODULE_DATA['GENE_LOC'] as $CHR_ID=>&$CHR_INFO)
{

	$STR.='<div style="width:100%;margin-top:30px;margin-bottom:1px;min-height:20px;text-align:center;background-color:lightgrey;font-weight:bold;top:unset;display:flex">';
	$STR.=$CHR_INFO['CHR_SEQ']['ASSEMBLY_NAME'].'.'.$CHR_INFO['CHR_SEQ']['ASSEMBLY_VERSION'].'|'.$CHR_INFO['CHR_SEQ']['CHR_SEQ_NAME'].' '.$CHR_INFO['CHR_SEQ']['ASSEMBLY_UNIT'];
	$STR.='</div>';
	foreach ($CHR_INFO as $GENE_ID=>&$INFO_GENE)
	{
		if ($GENE_ID!=$USER_INPUT['PORTAL']['VALUE'])continue;
		if ($GENE_ID=='CHR_SEQ')continue;
		$STR.='<div class="w3-col s2"></div><div class="w3-col s10"><div style="width:100%;text-align:center; font-weight:bold;margin:0 auto; ">'.$INFO_GENE[0]['SYMBOL'].' '.$INFO_GENE[0]['FULL_NAME'].' (Gene ID:'.$INFO_GENE[0]['GENE_ID'].')'.'</div></div>';
		foreach ($INFO_GENE as &$G)
		{
			$START=round($RANGES[$CHR_ID]['RATIO']*($G['START_POS']-$RANGES[$CHR_ID]['MIN']),3);
			$WIDTH=round($RANGES[$CHR_ID]['RATIO']*($G['END_POS']-$G['START_POS']),3);
			echo $G['STRAND'].' '.$G['SYMBOL'].' '.$G['START_POS'].'->'.$G['END_POS']."\t".$START."\t".$WIDTH."\n";
			$STR.='
				<div class="w3-col s12 m3 l3" style="font-size:0.85em">'.$G['GENE_SEQ_NAME'].'</div>
				<div class="w3-col s12 m9 l9">
						<div style="position:relative;background-color:'.(($G['STRAND']=='+')?"red":"green").';color:white;margin-top:5px;height:20px;left:'.$START.'%;width:'.$WIDTH.'%;">
							
							<div class="arrow-'.(($G['STRAND']=='+')?"right":"left").'"></div>
						</div>
						</div>';
		}
		
	}
	foreach ($CHR_INFO as $GENE_ID=>&$INFO_GENE)
	{
		echo "TEST ".$GENE_ID."\n";
		if ($GENE_ID==$USER_INPUT['PORTAL']['VALUE'])continue;
		if ($GENE_ID=='CHR_SEQ')continue;
		echo "TEST ".$GENE_ID."\n";
		if ($GENE_ID!='')
		$STR.='<div class="w3-col s12 m3 l3"></div>
		<div class="w3-col s12 m9 l9"><div style="width:100%;text-align:center; font-weight:bold;margin:0 auto; ">'.$INFO_GENE[0]['SYMBOL'].' '.$INFO_GENE[0]['FULL_NAME'].' (Gene ID:'.$INFO_GENE[0]['GENE_ID'].')'.'</div></div>';
		
		else 
		$STR.='<div class="w3-col s12 m3 l3"></div>
		<div class="w3-col s12 m9 l9"><div style="width:100%;text-align:center; font-weight:bold;margin:0 auto; ">Undefined NCBI Gene(s)</div></div>';
		
		foreach ($INFO_GENE as &$G)
		{
			
			$START=round($RANGES[$CHR_ID]['RATIO']*($G['START_POS']-$RANGES[$CHR_ID]['MIN']),3);
			$WIDTH=round($RANGES[$CHR_ID]['RATIO']*($G['END_POS']-$G['START_POS']),3);
			echo $G['STRAND'].' '.$G['SYMBOL'].' '.$G['START_POS'].'->'.$G['END_POS']."\t".$START."\t".$WIDTH."\n";
				$STR.='
				<div class="w3-col s12 m3 l3" style="font-size:0.85em" >'.$G['GENE_SEQ_NAME'].'</div>
				<div class="w3-col s12 m9 l9">
						<div style="position:relative;background-color:'.(($G['STRAND']=='+')?"red":"green").';color:white;margin-top:5px;height:20px;left:'.$START.'%;width:'.$WIDTH.'%;">
							
							<div class="arrow-'.(($G['STRAND']=='+')?"right":"left").'"></div>
						</div>
						</div>';
			
			
		}
		
	}

}//exit;

changeValue("genes_location",'SHOW_OFF',$STR);

?>