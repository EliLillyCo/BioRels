<?php


$list_str="14472370	University of Miami Miller School of Medicine, Department of Psychiatry & Behavioral, Miami, FL, USA.	6894	
4767173	University of Miami Miller School of Medicine - Urology, Miami, FL, USA.	6894	
6664528	From the University of Miami Miller School of Medicine, Miami.	6894	
12171114	University of Miami Miller School of Medicine, Psychiatry and Behavioral Sciences, Miami, FL, USA.	6894	
10110565	University of Miami Miller School of Medicine, Miami, FL, USA. sweiss2@med.miami.edu.	6894	
1654334	University of Miami Miller School of Medicine, 1600 NW 10th Ave, Miami, FL, 33136, USA.	6894	
9314392	University of Miami Miller School of Medicine, 1150 NW 14th St., Miami, Florida,  33136, USA.	6894	
20713185	University of Miami School of Medicine, Miami, FL, USA. hliddle@med.miami.edu	6894";
$list=explode("\n",$list_str);
$list_arr=array();
foreach ($list as $l)
{
	$T=array_reverse(cleanInstit(cleanName($l,true)));
	$T_N=array();
	foreach ($T as $TP)	if (!is_numeric($TP))$T_N[]=$TP;
	$list_arr[]=$T_N;
	
}
unset($list,$T,$T_N,$TP);
$ENTRIES=array();
foreach ($list_arr as $rec)
{
	for ($I=0;$I<count($rec);++$I)
	{
		$BEST_V=-1;$BEST_P=-1;
		foreach ($ENTRIES as $P=>&$V)
		{
			foreach ($V['L'] as $V_E)
			{
				$S1=compare(explode(" ",$V_E),explode(" ",$rec[$I]));
				if ($S1>$BEST_V){$BEST_V=$S1;$BEST_P=$P;}
			}
			
		}
		if ($BEST_V>=0.8)
		{
			if (!in_array($rec[$I], $ENTRIES[$BEST_P]['L']) )$ENTRIES[$BEST_P]['L'][]=$rec[$I];
		}
		else $ENTRIES[]=array('L'=>array($rec[$I]),'C'=>array());	
	}
}
unset($rec,$I,$BEST_P,$BEST_V,$V_E,$S1);

foreach ($list_arr as $rec)
{
	for ($I=0;$I<count($rec)-1;++$I)
		{
			
			$CURR=$rec[$I];$CURR_P=-1;
			$NEXT=$rec[$I+1];$NEXT_P=-1;
			foreach ($ENTRIES as $P=>&$V)
			{
				
					if (in_array($NEXT, $V['L']))$NEXT_P=$P;
					if (in_array($CURR, $V['L']))$CURR_P=$P;
			}
		
		
		if (!isset($ENTRIES[$CURR_P]['C'][$NEXT_P]))$ENTRIES[$CURR_P]['C'][$NEXT_P]=1;
			else $ENTRIES[$CURR_P]['C'][$NEXT_P]++;

		}
	}
		print_r($ENTRIES);
exit;








	foreach ($list as $l)
	{
		$list_e=array_reverse(cleanInstit(cleanName($l,true)));
		foreach ($list_e as $K=>$V)
		{
			if (is_numeric($V))unset($list_e[$K]);
			
		}
		
		$list_e=array_values($list_e);
		
		$SHIFT=0;
		for ($I=0;$I<count($list_e)-1;++$I)
		{
			
			$CURR=$list_e[$I];$CURR_P=-1;
			$NEXT=$list_e[$I+1];$NEXT_P=-1;
			
			foreach ($ENTRIES as $P=>&$V)
			{
				
					if (in_array($NEXT, $V['L']))$NEXT_P=$P;
					if (in_array($CURR, $V['L']))$CURR_P=$P;
			}
			
			echo $CURR."\t".$NEXT."\t".$NEXT_P."\t".$CURR_P."\n";
			if (!isset($ENTRIES[$CURR_P]['C'][$NEXT_P]))$ENTRIES[$CURR_P]['C'][$NEXT_P]=1;
			else $ENTRIES[$CURR_P]['C'][$NEXT_P]++;
print_r($ENTRIES);
			
		}
	}
	print_r($ENTRIES);

// $ORDER=array();
// foreach ($ORDER_T as $K=>$V)
// {
// $ORDER[(string)round($V['SUM']/$V['CO'],2)][]=$K;
// }
// print_r($ORDER_T);
// ksort($ORDER);
// print_r($ORDER);

//print_r($STR);
exit;



function cleanName($NAME,$IS_INSTIT=false)
{
	$CHANGES=array('"'=>'',
					", ,"=>",",'By-pass'=>'Bypass',
					
					
					
);
	$RULES=array('/\s+/'=>' ',
	'/,+/'=>',',
	"/ Rd(.{0,1})([\s,]|$){1}/"=>' Road${2}',
	"/ Dr(.{0,1})([\s,]|$){1}/"=>' Drive${2}',
	"/ St(.{0,1})([\s,]|$){1}/"=>' Street${2}',
	"/ Ave(.{0,1})([\s,]|$){1}/"=>' Avenue${2}',
	"/ Ste(.{0,1})([\s,]|$){1}/"=>' Suite${2}',
	"/ Pkwy(.{0,1})([\s,]|$){1}/"=>' Parkway${2}',
	"/ Bldg(.{0,1})([\s,]|$){1}/"=>' Building${2}',
	"/ Blvd(.{0,1})([\s,]|$){1}/"=>' Boulevard${2}',
	"/ Int'l(.{0,1})([\s,]|$){1}/"=>' International${2}',
	"/ Ft(.{0,1})([\s,]|$){1}/"=>' Fort${2}',
	"/ Hwy(.{0,1})([\s,]|$){1}/"=>' Highway${2}',
	"/ Fl(.{1})([\s,]|$){1}/"=>' Floor${2}',
	"/ \([0-9]{1,5}\)/"=>'',
	'/^([0-9]{1,5})([A-Z]{1}) /'=>'${1} ${2} '
);
$RULES2=array('/ Us /'=>' US ','/U\.s\./'=>'U.S. ',
'/P\.o\./'=>'P.O.','/ Po Box/'=>'P.O. Box','/\s+/'=>' ');
	//$NAME_TAB=explode(" ",str_replace('"','',str_replace(", ,",",",preg_replace('/,+/', ',',preg_replace('/\s+/', ' ', $NAME)))));
	foreach ($RULES as $R=>$V)
	{
		//echo $NAME."\t";
		$NAME=preg_replace($R,$V,$NAME);
		
	}
	foreach ($CHANGES as $R=>$V) $NAME=str_replace($R,$V,$NAME);
	$NAME_TAB=explode(" ",$NAME);
	$NAME_CLEAN='';
	foreach ($NAME_TAB as $T)
	{
		$T2=ucfirst(strtolower($T));
		if (isset($CORRECTIONS[$T2]))$T2=$CORRECTIONS[$T2];
		$NAME_CLEAN.=$T2.' ';
	}
	$NAME_CLEAN=substr($NAME_CLEAN,0,-1);
//"/[\s,]{1}([SsNnEeWw]){1}(\.){0,1}([EeWw]){0,1}(.{0,1})([\s,]|$){1}/"
	$NAME_CLEAN=preg_replace_callback("/[\s,]{1}([SsNnEeWw]){1}(\.){0,1}([EeWw]){0,1}(.{0,1})([\s,]|$){1}/", 
	function ($matches) {
		if ($matches[3]!='')		return ' '.strtoupper($matches[1]).".".strtoupper($matches[3]).".".$matches[5];
		else
		{
			switch (strtoupper($matches[1]))
			{
				case 'N':return ' North'.$matches[5];break;
				case 'S':return ' South'.$matches[5];break;
				case 'E':return ' East'.$matches[5];break;
				case 'W':return ' West'.$matches[5];break;

			}
			
		} 
	
	},$NAME_CLEAN);// ' ${1}.${3}.${5}'
if (!$IS_INSTIT)
	foreach ($RULES2 as $R=>$V)
	{
		
		$NAME_CLEAN=preg_replace($R,$V,$NAME_CLEAN);
		
	}
	return $NAME_CLEAN;
}


function cleanInstit($line)
		{
		
			$list_replace=array('phone','email','fax');
			foreach ($list_replace as $t)
			{
				$pos=stripos($line,$t.':');
				if ($pos!==false)$line=str_replace($t.':','',$line);
				$pos=stripos($line,$t);
				if ($pos!==false)$line=str_replace($t,'',$line);
			}
			$patterns = array('<[\w.-]+@[\w.-]+>', '<\w{3,6}:(?:(?://)|(?:\\\\))[^\s]+>','<(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}>');
			$matches = array('', '');
			$newString = preg_replace($patterns, $matches, $line);
			$newString=preg_replace('!\s+!', ' ',$newString);
		
			$newString=str_ireplace('United States of America', 'USA',$newString);
			$pos=-1;
			for($I=0;$I<strlen($newString);++$I)
			{
				if (is_numeric($newString[$I]))$pos=$I;
				else break;
			}
			if ($pos!=-1)$newString=substr($newString,$pos+1);
			$pos=strrpos($newString,'.');
			if ($pos!==false)$newString=substr($newString,0,$pos);
			$tab=explode(",",strtolower(trim($newString)));
			foreach ($tab as $K=>&$V)	$V=trim($V);
			
			return $tab;
		}



function compare(&$REF,&$ALT,$DEBUG=false)
{
	
	if (strtolower(implode(" " ,$REF))==strtolower(implode(" " ,$ALT)))return 1;
	//foreach ($REF as &$T)$T=strtolower($T);
	//foreach ($ALT as &$T)$T=strtolower($T);
	//echo "|".implode(' ',$REF).'|'.implode(' ',$ALT)."|\n";
	//$DEBUG=true;
	//if (count($REF)<=2 || count($ALT)<2)return 0;
	$MATRIX=array();
	$MATRIX_A=array();
	$MATRIX_R=array();
	$SCORES=array();
	$MAP_R=array_fill(0,count($REF),-1);
	$MAP_A=array_fill(0,count($ALT),-1);
	$SCORE=0;
	for($I=0;$I<count($REF)-1;++$I)
	{
		if ($DEBUG)echo $I."\t";
		for($J=0;$J<count($ALT);++$J)
		{
			
			if (strlen($REF[$I]." ".$REF[$I+1])>=255 || strlen($ALT[$J])>=255)continue;
			else $NS=levenshtein($REF[$I]." ".$REF[$I+1],$ALT[$J]);
			if ($NS < 0.2*strlen($REF[$I]." ".$REF[$I+1])
			&&  $NS < 0.2*strlen($ALT[$J]))
			{
				if ($MAP_R[$I]!=-1 || $MAP_R[$I+1]!=-1)continue;
				if ($MAP_A[$J]!=-1)continue;
				if ($DEBUG)echo "M";
				$MAP_R[$I]=$J;
				$MAP_R[$I+1]=$J;
				$MAP_A[$J]=$I;
				if ($DEBUG)echo "MAPPING\t".$REF[$I]." ".$REF[$I+1]." ".$ALT[$J]." =>".$NS."\t".min(abs($I-$J),abs($I+1-$J))."\n";
				$impact=1;
				if ($I==0||$J==0)$impact+=0.5;
				$SCORE+=$NS*(min(abs($I-$J),abs($I+1-$J))+$impact);
			}
			$MATRIX_R[$I][$J]=$NS;
			if ($DEBUG)echo  $NS."\t";
		}
		if ($DEBUG)echo "\n";
		//echo $REF[$I]." ".$ALT[$SCORE[1]]." ".$SCORE[0]."\n";
	}
	if ($DEBUG)echo "####\n";
	for($I=0;$I<count($REF);++$I)
	{
		if ($DEBUG)echo $I."\t";
		for($J=0;$J<count($ALT)-1;++$J)
		{
			
			if (strlen($REF[$I])>=255 || strlen($ALT[$J]." ".$ALT[$J+1])>=255)continue;
			else   $NS=levenshtein($REF[$I],$ALT[$J]." ".$ALT[$J+1]);
			
			if ($NS < 0.2*strlen($REF[$I])
			&&  $NS < 0.2*strlen($ALT[$J]." ".$ALT[$J+1]))
			{
				if ($MAP_A[$J]!=-1 || $MAP_A[$J+1]!=-1)continue;
				if ($MAP_R[$I]!=-1)continue;
				if ($DEBUG)echo "A";
				$MAP_R[$I]=$J;
				$MAP_A[$J+1]=$I;
				$MAP_A[$J]=$I;
				if ($DEBUG)echo "MAPPING\t".$REF[$I]." ".$ALT[$J]." ".$ALT[$J+1]." =>".$NS."\t".min(abs($I-$J),abs($I+1-$J))."\n";
				$impact=1;
				if ($I==0||$J==0)$impact+=0.5;
				$SCORE+=$NS*(min(abs($I-$J),abs($I+1-$J))+$impact);
			}
		}
		if ($DEBUG)echo "\n";
		//echo $REF[$I]." ".$ALT[$SCORE[1]]." ".$SCORE[0]."\n";
	}
	if ($DEBUG)echo "####\n";
	for($I=0;$I<count($REF);++$I)
	{
		if ($DEBUG)echo $I."\t";
		for($J=0;$J<count($ALT);++$J)
		{
			if (strlen($REF[$I])>=255 || strlen($ALT[$J])>=255)continue;
			else 
			{
				 $NS=levenshtein($REF[$I],$ALT[$J]);
				//echo "NOT LARGE ".$REF[$I].'-'.$ALT[$J]." =>".$NS.';'.levenshtein($ALT[$J],$REF[$I])."\n";
			}
			if ($ALT[$J]!='' && $REF[$I]!='')
			{
				if (strpos($REF[$I],$ALT[$J])!==false)$NS=ceil($NS/2);
				if (strpos($ALT[$J],$REF[$I])!==false)$NS=ceil($NS/2);
				
			}
			
			$impact=1;
			if ($I==0||$J==0)$impact+=0.5;
			$SC=$NS*(abs($I-$J)+$impact);
			$MATRIX[$I][$J]=$SC;
			$SCORES[$SC][]=array($I,$J);
			if ($DEBUG)echo  $NS."=>".$SC."\t";
		}
		if ($DEBUG)echo "\n";
		//echo $REF[$I]." ".$ALT[$SCORE[1]]." ".$SCORE[0]."\n";
	}
	ksort($SCORES);
	
	foreach ($SCORES as $NUM=>$LIST)
	foreach ($LIST as $T)
	{
		if ($MAP_R[$T[0]]!=-1)continue;
		if ($MAP_A[$T[1]]!=-1)continue;
		
		$MAP_R[$T[0]]=$T[1];
		$MAP_A[$T[1]]=$T[0];
		if ($DEBUG)echo "MAPPING\t".$REF[$T[0]]." ".$ALT[$T[1]]." =>".$NUM."\t".abs($T[0]-$T[1])."\n";
		
		$SCORE+=$NUM*(abs($T[0]-$T[1])+1);
	}
	foreach ($MAP_A as $P=>$N)
	{
		if ($N==-1)$SCORE+=strlen($ALT[$P]);
	}
	$MAX_V=max(strlen(implode(" ",$REF)),strlen(implode(" ",$ALT)));
	$FSCORE=round(($MAX_V-$SCORE)/$MAX_V,3);
	if ($DEBUG)echo "FROM ".$SCORE."/MAXV:".$MAX_V."/TOT SCORE:".$FSCORE."\t".strlen(implode(" ",$REF))."\t".strlen(implode(" ",$ALT))."\n";
	if (count($ALT)==1 && strlen($ALT[0])<10 && $FSCORE!=1)return 0;
	return $FSCORE;

}

?>
