<?php
error_reporting(E_ALL);
ini_set('memory_limit','1000M');
$JOB_NAME='wh_ontology';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false) die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];
 
$US_STATES=getChildsOf('United States of America');
$fp=fopen('list_miami.csv','r');
$N=0;

while(!feof($fp))
{
	++$N;
	if ($N==10)break;
	$line=stream_get_line($fp,10000,"\n");
	$tab=explode("\t",$line);
	$INSTIT_ID = $tab[0];
	$INSTIT_NAME=$tab[1];
	echo "\n\n\n\n\n";
	echo $INSTIT_NAME."\n";
	
	
	$ADDR_INFO=findAddr(cleanInstit($INSTIT_NAME));
	;
	echo $INSTIT_NAME."\n";
		print_r($ADDR_INFO[max(array_keys($ADDR_INFO))]);
		echo "BEST SCORE:".max(array_keys($ADDR_INFO))."\n";
		echo $INSTIT_NAME."\n";
}
fclose($fp);


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
function testAddr($LIST,$CURR_LEVEL,$SCORE,$PARENT,&$RESULTS,&$CURR_RECORD)
{
	$NEXT=$LIST[count($LIST)-$CURR_LEVEL];
	if (is_numeric($NEXT)){$CURR_LEVEL+=1;$NEXT=$LIST[count($LIST)-$CURR_LEVEL];}

	$q1="select * FROM ONTOLOGY_ENTRY OE,  ONTOLOGY_HIERARCHY OH WHERE ONTOLOGY_NAME='".str_replace("'","''",$PARENT)."'
	AND OH.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID ";
	$res=runQuery($q1);$list=array();
	foreach ($res as $l)
	{
		$query="SELECT OE.ONTOLOGY_ENTRY_ID,OS.SYN_VALUE,OE.ONTOLOGY_NAME 
		FROM ONTOLOGY_ENTRY OE, ONTOLOGY_SYN OS, ONTOLOGY_HIERARCHY OH
		WHERE OH.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID 
		AND OS.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID
		AND (OH.ONTOLOGY_LEVEL = ".$l['ONTOLOGY_LEVEL']."+2 OR OH.ONTOLOGY_LEVEL = ".$l['ONTOLOGY_LEVEL']."+1)
		AND OH.ONTOLOGY_LEVEL_LEFT >= ".$l['ONTOLOGY_LEVEL_LEFT']."
		AND OH.ONTOLOGY_LEVEL_RIGHT <= ".$l['ONTOLOGY_LEVEL_RIGHT'];
		//echo $query."\n";
		$res2=runQuery($query);
		
		foreach ($res2 as $line2)$list[]=$line2;

	}
	
	foreach ($list as $line2)
	{
		$ST_T2=explode(" ",$NEXT);
		$ST_N2=explode(" ",$line2['ONTOLOGY_NAME']);
		$ST_S2=explode(" ",$line2['SYN_VALUE']);
		$SC1_2=  compare($ST_T2,$ST_N2);
		$SC2_2=0;
		if ($line2['SYN_VALUE']!='')$SC2_2=compare($ST_T2,$ST_S2);
		$MAX_V2=max($SC1_2,$SC2_2);
		if ($MAX_V2<0.6)continue;
		$CURR_RECORD[$CURR_LEVEL]=$line2;
		$RESULTS[(string)($MAX_V2+$SCORE)][]=$CURR_RECORD;
for ($I=0;$I<$CURR_LEVEL;++$I)echo "\t";
		echo $SC1_2.' ' .$SC2_2.' MAX_SCORE:'.($SCORE+$MAX_V2).' |' .$NEXT.'|' .$line2['ONTOLOGY_NAME'].'|' .$line2['SYN_VALUE']."|\n";
		if ($CURR_LEVEL+1<=count($LIST)){testAddr($LIST,$CURR_LEVEL+1,$SCORE+$MAX_V2,$line2['ONTOLOGY_NAME'],$RESULTS,$CURR_RECORD);}
	}
}
function findAddr($ADDR)
{
	$RESULTS=array();
	$START=$ADDR[count($ADDR)-1];
	
	$res=runQuery("SELECT OE.ONTOLOGY_ENTRY_ID,OS.SYN_VALUE,OE.ONTOLOGY_NAME 
	 FROM ONTOLOGY_ENTRY OE LEFT JOIN ONTOLOGY_SYN OS ON OS.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID, ONTOLOGY_HIERARCHY OH,
	 (select * FROM ONTOLOGY_ENTRY OE,  ONTOLOGY_HIERARCHY OH WHERE ONTOLOGY_NAME='Geography'
	 AND OH.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID ) OG
	 WHERE OH.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID 
	 AND OH.ONTOLOGY_LEVEL <= OG.ONTOLOGY_LEVEL+3
	 AND OH.ONTOLOGY_LEVEL_LEFT >= OG.ONTOLOGY_LEVEL_LEFT
	 AND OH.ONTOLOGY_LEVEL_RIGHT <= OG.ONTOLOGY_LEVEL_RIGHT");
	  if (count($res)==0)return;
	  
	  foreach ($res as $line)
	  {
		  
		  $ST_T=explode(" ",$START);
		  $ST_N=explode(" ",$line['ONTOLOGY_NAME']);
		  $ST_S=explode(" ",$line['SYN_VALUE']);
		$SC1=  compare($ST_T,$ST_N);
		$SC2=compare($ST_T,$ST_S);
		//echo "LEVEL1:\t".$line['ONTOLOGY_NAME']."\t".$line['SYN_VALUE']." <=> ".$START."\t".$SC1."\t".$SC2."\n";
		$MAX_V=max($SC1,$SC2);
		if ($MAX_V<0.6)continue;
		$CURR_RECORD=array();
		  $CURR_RECORD[0]=$line;
		  $RESULTS[$MAX_V][]=$CURR_RECORD;
		echo $SC1.' ' .$SC2.' ' .$START.' ' .$line['ONTOLOGY_NAME'].' ' .$line['SYN_VALUE']."\n";
		testAddr($ADDR,2,$MAX_V,$line['ONTOLOGY_NAME'],$RESULTS,$CURR_RECORD);
		//print_r($RESULTS);
		
		
	  }
	return $RESULTS;
// 	

// 	
// echo $query;
// 	print_r($res);





	// $t='';foreach ($ADDR as $A)$t.="'".$A."',";$t=substr($t,0,-1);
	// $res=
	//  $IDS=array();
	// foreach ($res as $l)$IDS[]=$l['ONTOLOGY_ENTRY_ID'];
	// $res=runQuery("select * FROM ONTOLOGY_ENTRY OE, ONTOLOGY_HIERARCHY OH
	// WHERE  OH.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID 
	// AND OE.ONTOLOGY_ENTRY_ID IN (".implode(",",$IDS).") ORDER BY ONTOLOGY_LEVEL ASC");
	// $TREE=array();
	// foreach ($res as $line)
	// {
		
	// }
}


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


function getChildsOfByID($ID,$DEPTH=1)
{
	$res=runQuery("select DISTINCT OE.ONTOLOGY_ENTRY_ID,OE.ONTOLOGY_NAME,SYN_VALUE FROM ONTOLOGY_ENTRY OE LEFT JOIN ONTOLOGY_SYN OS ON OS.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID, ONTOLOGY_HIERARCHY OH, ONTOLOGY_HIERARCHY OPH, ONTOLOGY_ENTRY OP
	WHERE OH.ONTOLOGY_LEVEL_LEFT >OPH.ONTOLOGY_LEVEL_LEFT 
	AND OH.ONTOLOGY_LEVEL_RIGHT<OPH.ONTOLOGY_LEVEL_RIGHT 
	AND OH.ONTOLOGY_LEVEL<=OPH.ONTOLOGY_LEVEL+".$DEPTH."
	AND OH.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID
	AND OPH.ONTOLOGY_ENTRY_ID = OP.ONTOLOGY_ENTRY_ID
	AND OP.ONTOLOGY_ENTRY_ID=".$ID);
	
	
	$RES=array('BY_NAME'=>array(),'BY_ID'=>array());
	foreach ($res as $line) 
	{
		if (isset($RES['BY_NAME'][$line['ONTOLOGY_NAME']]) && $RES['BY_NAME'][$line['ONTOLOGY_NAME']]!=$line['ONTOLOGY_ENTRY_ID']){
			echo "NAME\t".$line['ONTOLOGY_NAME']."\t".$RES['BY_NAME'][$line['ONTOLOGY_NAME']]." DUPLICATION\n";
			}
		if (isset($RES['BY_NAME'][$line['SYN_VALUE']]) && $RES['BY_NAME'][$line['SYN_VALUE']]!=$line['ONTOLOGY_ENTRY_ID'])
		{
			echo "SYN\t".$line['SYN_VALUE']."\t".$RES['BY_NAME'][$line['SYN_VALUE']]." DUPLICATION\n";
			// print_r($line);
			// print_r($RES['BY_NAME'][$line['SYN_VALUE']]);
			 
			}
		$RES['BY_NAME'][$line['ONTOLOGY_NAME']]=$line['ONTOLOGY_ENTRY_ID'];
		$RES['BY_NAME'][$line['SYN_VALUE']]=$line['ONTOLOGY_ENTRY_ID'];
		$RES['BY_ID'][$line['ONTOLOGY_ENTRY_ID']]['P']=$line['ONTOLOGY_NAME'];
		$RES['BY_ID'][$line['ONTOLOGY_ENTRY_ID']][]=$line['SYN_VALUE'];
	}
	return $RES;
}

function getChildsOf($NAME,$DEPTH=1)
{
	$res=runQuery("select OE.ONTOLOGY_ENTRY_ID,OE.ONTOLOGY_NAME,SYN_VALUE FROM ONTOLOGY_ENTRY OE LEFT JOIN ONTOLOGY_SYN OS ON OS.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID, ONTOLOGY_HIERARCHY OH, ONTOLOGY_HIERARCHY OPH, ONTOLOGY_ENTRY OP
	WHERE OH.ONTOLOGY_LEVEL_LEFT >OPH.ONTOLOGY_LEVEL_LEFT 
	AND OH.ONTOLOGY_LEVEL_RIGHT<OPH.ONTOLOGY_LEVEL_RIGHT 
	AND OH.ONTOLOGY_LEVEL<=OPH.ONTOLOGY_LEVEL+".$DEPTH."
	AND OH.ONTOLOGY_ENTRY_ID = OE.ONTOLOGY_ENTRY_ID
	AND OPH.ONTOLOGY_ENTRY_ID = OP.ONTOLOGY_ENTRY_ID
	AND OP.ONTOLOGY_NAME='".$NAME."'");
	
	
	$RES=array('BY_NAME'=>array(),'BY_ID'=>array());
	foreach ($res as $line) 
	{
		if (isset($RES['BY_NAME'][$line['ONTOLOGY_NAME']]) && $RES['BY_NAME'][$line['ONTOLOGY_NAME']]!=$line['ONTOLOGY_ENTRY_ID']){ throw new Exception($line['ONTOLOGY_NAME'].' found twice');}
		if ($line['SYN_VALUE']!=''&&isset($RES['BY_NAME'][$line['SYN_VALUE']]) && $RES['BY_NAME'][$line['SYN_VALUE']]!=$line['ONTOLOGY_ENTRY_ID']){ print_r($RES);print_r($line);throw new Exception($line['SYN_VALUE'].' '.$line['ONTOLOGY_NAME'].' found twice');}
		$RES['BY_NAME'][$line['ONTOLOGY_NAME']]=$line['ONTOLOGY_ENTRY_ID'];
		if ($line['SYN_VALUE']!='')$RES['BY_NAME'][$line['SYN_VALUE']]=$line['ONTOLOGY_ENTRY_ID'];
		$RES['BY_ID'][$line['ONTOLOGY_ENTRY_ID']]['P']=$line['ONTOLOGY_NAME'];
		if ($line['SYN_VALUE']!='')$RES['BY_ID'][$line['ONTOLOGY_ENTRY_ID']][]=$line['SYN_VALUE'];
	}
	return $RES;
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
