<?php

if (!defined("BIORELS")) header("Location:/");

$RANGES=array();
$MAX=0;
	$MIN=100000000000000000000000.0;
	
	$GENE_LIST=array();
foreach ($MODULE_DATA['GENE_RANGE'] as &$G)
{

	$GENE_LIST[$G['GENE_ID']][]=$G;
			
			
	$MAX=max($MAX,$G['END_POS']);
	$MIN=min($MIN,$G['START_POS']);
	$MAX=max($MAX,$G['END_POS']);
	$MIN=min($MIN,$G['START_POS']);
			
		
}		
$MAX=max($MAX,$PARAMS['POSITION']);
$MIN=min($MIN,$PARAMS['POSITION']);
	
	$RANGE=max($MAX,$MIN)-min($MAX,$MIN);
$RATIO=100/$RANGE;
	$RANGES=array('MIN'=>$MIN,'MAX'=>$MAX,'RANGE'=>$RANGE,'RATIO'=>$RATIO);





//echo "RANGE:".$MIN."\t".$MAX."\t".$RANGE."\n";
$STR='';

$LINES=array();
$COVERS=array();
$MAX_LINE=0;
echo '<pre>';
for ($I=0;$I<10;++$I)
for($J=0;$J<=100;++$J)$COVERS[$I][$J]=false;
$R_TEXT=13/21;

$STR.='<div class="w3-col s12" style="position:relative">
	<div  style="position:absolute;
	top:-10px;
	left:'.round($RANGES['RATIO']*($PARAMS['POSITION']-$RANGES['MIN']),3).'%;width:1px;opacity:0.2;border:2px solid black;height:${HEIGHT}px"></div>';
		
		
		$STR.='</div>';
	foreach ($GENE_LIST as $GENE_ID=>&$INFO_GENE)
	{
		
		// if ($GENE_ID!='')
		// $STR.='<div class="w3-col s2"></div>
		// <div class="w3-col s10"><div style="width:100%;text-align:center; font-weight:bold;margin:0 auto; ">'.$INFO_GENE[0]['SYMBOL'].' '.$INFO_GENE[0]['FULL_NAME'].' (Gene ID:'.$INFO_GENE[0]['GENE_ID'].')'.'</div></div>';
		
		// else 
		// $STR.='<div class="w3-col s2"></div>
		// <div class="w3-col s10"><div style="width:100%;text-align:center; font-weight:bold;margin:0 auto; ">Undefined NCBI Gene(s)</div></div>';
		
		foreach ($INFO_GENE as $K=>&$G)
		{
			
			$START=round($RANGES['RATIO']*($G['START_POS']-$RANGES['MIN']),3);
			$WIDTH=round($RANGES['RATIO']*($G['END_POS']-$G['START_POS']),3);
			$S=strlen($G['SYMBOL'].' '.$G['GENE_SEQ_NAME'])*$R_TEXT;
			if ($S>$WIDTH)$WIDTH=$S;
			echo $G['STRAND'].' '.$G['SYMBOL'].' '.$G['START_POS'].'->'.$G['END_POS']."\t".$START."\t".$WIDTH."\n";
			$LINE=-1;
			for ($I=0;$I<10;++$I)
			{
				$VALID=true;
				
				for ($J=floor($START)-1;$J<=ceil($START+$WIDTH)+1;++$J)
				{
					if (isset($COVERS[$I][$J]) && $COVERS[$I][$J]){$VALID=false;break;}
				}
				if (!$VALID)continue;
				
				for ($J=floor($START)-1;$J<=ceil($START+$WIDTH)+1;++$J)
				{
					if (isset($COVERS[$I][$J]))$COVERS[$I][$J]=true;
				}
				$LINES[$I][]=array($GENE_ID,$K);
				$MAX_LINE=max($MAX_LINE,$I);
				break;
			}
			// for ($I=0;$I<10;++$I)
			// {
			// 	echo "\n";
			// 	for($J=0;$J<=100;++$J)
			// 	echo ($COVERS[$I][$J])?"1":"0";
			// 	echo "\n";
			// }
		}
	}
	
	for ($I=0;$I<=$MAX_LINE;++$I)
	{
		$STR.='<div class="w3-col s12">';
		$STR2='<div class="w3-col s12" style="position:relative;top:-10px;">';
		
		foreach ($LINES[$I] as $G_POS)
		{
			$G=&$GENE_LIST[$G_POS[0]][$G_POS[1]];
			$START=round($RANGES['RATIO']*($G['START_POS']-$RANGES['MIN']),3);
			
			$WIDTH=round($RANGES['RATIO']*($G['END_POS']-$G['START_POS']),3);
			$STR.='<div class="range" style="background-color:'.(($G['STRAND']=='+')?"red":"green").';left:'.$START.'%;width:'.$WIDTH.'%;">
							
			 			<div class="arrow-'.(($G['STRAND']=='+')?"right":"left").'"></div>
			 		</div>';
			$STR2.='<div class="range" style="left:'.$START.'%;"><a class="blk_font" href="/GENEID/'.$G['GENE_ID'].'" target="_blank">'.$G['SYMBOL'].' '.$G['GENE_SEQ_NAME'].'</a></div>';
		}
		$STR.='</div>'.$STR2.'</div>';
		

	}
	changeValue("dna_view",'SHOW_OFF',$STR);

	$HEIGHT=12*($MAX_LINE*2+3);
	changeValue("dna_view",'HEIGHT',$HEIGHT);




?>