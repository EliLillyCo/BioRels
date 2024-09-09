<?php
error_reporting(E_ALL);
ini_set('memory_limit','2000M');
function encode_data($text)
{
	$ivlen = openssl_cipher_iv_length($cipher="aes-256-ctr");
	$iv=file_get_contents("iv.txt");
	$key=file_get_contents("key.txt");
	$ciphertext_raw = openssl_encrypt($text, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
	$hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
	
	$ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
	return $ciphertext;
}

function decode_data($ciphertext)
{
	//$ciphertext=file_get_contents("access.txt");
	//$iv=file_get_contents("iv.txt");
	$key=file_get_contents("key.txt");
	//decrypt later....
	$c = base64_decode($ciphertext);
	$ivlen = openssl_cipher_iv_length($cipher="aes-256-ctr");
	$iv = substr($c, 0, $ivlen);
	$hmac = substr($c, $ivlen, $sha2len=32);
	$ciphertext_raw = substr($c, $ivlen+$sha2len);
	$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
	$calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
	
	    return $original_plaintext;
	

}
echo cleanName("1201 West Cypress Creek Road, Suite 101 - Ft. Lauderdale - FL");
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
///////////////////
/////////////////// file: r_db_connect.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 11/26/18
/////////////////// purpose: Connect to the database
/////////////////// LOG:
///////////////////  1/14/19: Add dbconn.txt to hide connexion data from plain sight


try {
	
	$str=decode_data(file_get_contents("dbconn.txt"));



	$CONN_DATA=explode("|",$str);
	
	if (count($CONN_DATA)!=6)	throw new Exception("connexion file corrupted\n");

    $DB_CONN = new PDO($CONN_DATA[0], 
			$CONN_DATA[1], 
			$CONN_DATA[2], 
			array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=> false ));
} catch(PDOException $e) {
	throw new Exception("Unable to connect to the database\n".$e->getMessage());
}

try {
    $DB_XRAY = new PDO($CONN_DATA[3],$CONN_DATA[4],$CONN_DATA[5]);
    $DB_XRAY->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	throw new Exception("Unable to connect to the database\n".$e->getMessage());
}



function runQuery($query)
{
	try{
//echo $query."<br/>";
		global $DB_CONN;
		$stmt=$DB_CONN->prepare($query);
		$stmt->execute();
		if (substr($query,0,6)=="INSERT")return "";
		$results=$stmt->fetchAll(PDO::FETCH_ASSOC);
		return $results;
	} catch(PDOException $e) {
		throw new Exception("Error while running query\n".$e->getMessage()."\n\n".$query);
	}
}
function checkFileExist($FILE)
{
	if (!is_file($FILE)) return false;
	clearstatcache ();
	if (filesize($FILE)==0)return false;
	return true;

}

$N_QUERY=0;
function runQueryNoRes($query)
{
	global $N_QUERY;
	++$N_QUERY;
try{
//echo $query."<br/>";
	global $DB_CONN;
	$stmt=$DB_CONN->prepare($query);
	$res= $stmt->execute();
	$stmt->closeCursor();
	unset($stmt);
	$stmt=null;
	return $res;
	
} catch(PDOException $e) {
	echo "Error while running query\n".$e->getMessage()."\n\n".$query."\n";
	return false;
}
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




$US_STATES=getChildsOf('United States of America');


$CORRECTIONS=array('Cincinnatti'=>'Cincinnati','Cambrige'=>'Cambridge','Aubum'=>'Auburn');

$ENTRIES=array();
$fp=fopen('InstitutionCampus.csv','r');
$HEAD=array_flip(fgetcsv($fp));
$ORDER=array();
while(!feof($fp))
{
	$tab=fgetcsv($fp);
	if ($tab===false)continue;
	
	$ADDRESS=$tab[7];
	$T_ADDR=explode(",",str_replace(", ,",",",preg_replace('/,+/', ',',$tab[7])));
	$POST_COD=explode(" ",trim($T_ADDR[count($T_ADDR)-1]));
	//echo $ADDRESS."\n";
	$FOUND=false;
	$PO_V='';
	foreach ($POST_COD as $PO)
	{
		if (!isset($US_STATES['BY_NAME'][$PO]))continue;
		$PO_V=$PO;
	$FOUND=true;	
	//	print_r($US_STATES['BY_ID'][$US_STATES['BY_NAME'][$PO]]);
	}
	if (!$FOUND)continue;
	
	//echo $ADDRESS."\n";
	$CITY_ORIG=str_replace("`","",str_replace("&","_",str_replace("/","_",trim($T_ADDR[count($T_ADDR)-2]))));
	
	if (preg_match("/[0-9]{1,5}/",$CITY_ORIG))continue;
	if ($CITY_ORIG=='')echo $ADDRESS."\n";
	$CITY_CLEAN=cleanName($CITY_ORIG,true);
	
	

	$ADDR_ORIG='';
	for ($I=0;$I<count($T_ADDR)-2;++$I)$ADDR_ORIG.=	trim($T_ADDR[$I]).', ';
	$ADDR_ORIG=substr($ADDR_ORIG,0,-2);
	if ($ADDR_ORIG=='')continue;
	$ADDR_CLEAN=cleanName($ADDR_ORIG);

	$INSTIT_NAME=cleanName($tab[3],true);
	$UP_NAME=cleanName($tab[4]);
	if ($UP_NAME=='-')
	{
		// if (isset($ENTRIES[$INSTIT_NAME]['ORIG'])){
		// 	print_r($ENTRIES[$INSTIT_NAME]['ORIG']);
		// 	echo $INSTIT_NAME;exit;}
		$ENTRIES[$INSTIT_NAME][]=array($INSTIT_NAME,$ADDR_CLEAN,$CITY_CLEAN,$PO_V);

	}
	else 
	{
		
		$ENTRIES[$UP_NAME][]=array($INSTIT_NAME,$ADDR_CLEAN,$CITY_CLEAN,$PO_V);
	}
	
	if (!isset($ORDER[$PO_V][$CITY_CLEAN]))$ORDER[$PO_V][$CITY_CLEAN]=array('SYN'=>array($CITY_ORIG=>true),
																						 'ADDR'=>array($ADDR_CLEAN=>array('SYN'=>array($ADDR_ORIG=>true,$ADDR_CLEAN=>true),
																						 								'INSTIT'=>array($INSTIT_NAME=>array($tab[3]=>true)))));
		else 
		{
			$ORDER[$PO_V][$CITY_CLEAN]['SYN'][$CITY_ORIG]=true;
			if (isset($ORDER[$PO_V][$CITY_CLEAN]['ADDR'][$ADDR_CLEAN]))
			{
				
				$ORDER[$PO_V][$CITY_CLEAN]['ADDR'][$ADDR_CLEAN]['SYN'][$ADDR_ORIG]=true;
				$ORDER[$PO_V][$CITY_CLEAN]['ADDR'][$ADDR_CLEAN]['SYN'][$ADDR_CLEAN]=true;
				
			}
			else $ORDER[$PO_V][$CITY_CLEAN]['ADDR'][$ADDR_CLEAN]=array('SYN'=>array($ADDR_ORIG=>true,$ADDR_CLEAN=>true));
			$ORDER[$PO_V][$CITY_CLEAN]['ADDR'][$ADDR_CLEAN]['INSTIT'][$INSTIT_NAME][$tab[3]]=true;
			
		}
	//echo $tab[3]."|".$tab[4]."|".$ADDR_CLEAN."|".$CITY_CLEAN."|".$PO_V."\n";
	//echo $ADDR_ORIG."\n";
	//echo $CITY_CLEAN."\n";
	//exit;

}


//print_r($ORDER);exit;


// foreach ($ORDER as $STATE=>&$LIST_CITIES)
// {
// 	$STATE_ID=$US_STATES['BY_NAME'][$STATE];
// 	$STATE_NAME=$US_STATES['BY_ID'][$STATE_ID]['P'];
// 	$CITIES=getChildsOfByID($STATE_ID);
// 	foreach ($LIST_CITIES as $CITY=>&$LIST_ADDRESSES)
// 	{
// 		if (!isset($CITIES['BY_NAME'][$CITY]))
// 		{
// 			//print_r($CITIES['BY_NAME']);
// 			echo "##########" .$CITY."\n";
// 			$CITY=str_replace("/","%2F",$CITY);
// 			$INPUT=array('ename'=>$CITY.' - '.$STATE,'parent'=>array($STATE_NAME),'edesc'=>$CITY.' city in '.$STATE_NAME,'esyn'=>array($CITY),'lname'=>'DESAPHY','fname'=>'Jeremy','epub'=>array());
// 			pushEntryToDB($INPUT);
			
			
// 			$CITIES=getChildsOfByID($STATE_ID);
		
// 		}
// 		$CITY_ID=$CITIES['BY_NAME'][$CITY];
// 		$CITY_NAME=$CITIES['BY_ID'][$CITY_ID]['P'];
		
// 		$ADDRESSES=getChildsOfByID($CITY_ID);
		
// 		foreach ($LIST_ADDRESSES['ADDR'] as $ADDRESS=>&$LIST_INSTIT)
// 		{
			
// 			if (!isset($ADDRESSES['BY_NAME'][$ADDRESS]))
// 			{
				
				
// 				echo "##########" .$ADDRESS."\n";
				
// 				$ADDRESS=str_replace("/","%2F",$ADDRESS);
// 				$INPUT=array('ename'=>$ADDRESS.' - '.$CITY_NAME,'parent'=>array($CITY_NAME),'edesc'=>$ADDRESS.' in '.$CITY.', '.$STATE_NAME,'esyn'=>array_keys($LIST_INSTIT['SYN']),'lname'=>'DESAPHY','fname'=>'Jeremy','epub'=>array());
// 				pushEntryToDB($INPUT);
	
				
// 				$ADDRESSES=getChildsOfByID($CITY_ID);
			
// 			}
			

// 		}
// 	}
// }



$INSTITUTIONS=getChildsOf('Higher Education institutions');
foreach ($ENTRIES as $INSTIT_NAME=>&$LIST_ADDR)
{
	
	if (isset($INSTITUTIONS['BY_NAME'][$INSTIT_NAME]))
	{

	}else
	{
		echo "###############\n\n\n\n";
	echo $INSTIT_NAME."\n";
		echo "\tNEW\n";
		$PARENT=array('Higher Education institutions');
		foreach ($LIST_ADDR as $ADDR)
		{
			echo "\tTEST ADDR:".implode(" || ",$ADDR)."\n";
			$ADDR_INFO=findAddr($ADDR);
			if ($ADDR_INFO===false)continue;
			print_r($ADDR_INFO);
			$PARENT[]=$ADDR_INFO[3]['ONTOLOGY_NAME'];
			//$ADDR_INFO[2]
		}
		echo "OUT\n";
		
		sort($PARENT);$PARENT=array_unique($PARENT);
		$INPUT=array('ename'=>$INSTIT_NAME,'parent'=>$PARENT,'edesc'=>$INSTIT_NAME.' institution','esyn'=>array($INSTIT_NAME),'lname'=>'DESAPHY','fname'=>'Jeremy','epub'=>array());
		
		print_r($INPUT);
		pushEntryToDB($INPUT);
		
	}
	
}
function testAddr($LIST,$CURR_LEVEL,$SCORE,$PARENT,&$RESULTS,&$CURR_RECORD)
{
	$NEXT=$LIST[count($LIST)-$CURR_LEVEL];

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
	 
	 AND OH.ONTOLOGY_LEVEL_LEFT >= OG.ONTOLOGY_LEVEL_LEFT
	 AND OH.ONTOLOGY_LEVEL_RIGHT <= OG.ONTOLOGY_LEVEL_RIGHT
	  AND SYN_VALUE ='".$START."' OR OE.ONTOLOGY_NAME ='".$START."'");
	  if (count($res)==0)return;
	  
	  foreach ($res as $line)
	  {
		  echo "LEVEL1:\t".$line['ONTOLOGY_NAME']."\n";
		  $ST_T=explode(" ",$START);
		  $ST_N=explode(" ",$line['ONTOLOGY_NAME']);
		  $ST_S=explode(" ",$line['SYN_VALUE']);
		$SC1=  compare($ST_T,$ST_N);
		$SC2=compare($ST_T,$ST_S);
		$MAX_V=max($SC1,$SC2);
		if ($MAX_V<0.6)continue;
		$CURR_RECORD=array();
		  $CURR_RECORD[0]=$line;
		  $RESULTS[$MAX_V][]=$CURR_RECORD;
		echo $SC1.' ' .$SC2.' ' .$START.' ' .$line['ONTOLOGY_NAME'].' ' .$line['SYN_VALUE']."\n";
		testAddr($ADDR,2,$MAX_V,$line['ONTOLOGY_NAME'],$RESULTS,$CURR_RECORD);
		print_r($RESULTS);
		
		
	  }
	  if (!isset($RESULTS["3"]))
		{
			echo "FAILED\n";//exit;
			return false;
		}
		return $RESULTS[3][0];
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

	function pushEntryToDB($INPUT)
	{

		// Check uniqueness
		$res=runQuery("SELECT * FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_NAME='".str_replace("'","''",$INPUT['ename'])."'");
		if (count($res)!=0){echo "Duplication";return false;}

		// /// Check Parent exists:
		$PAR_ID=array();$PAR_LS="";
		foreach ($INPUT['parent'] as &$P)
		{
			//echo "SELECT * FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_NAME='".str_replace("'","''",trim($P))."'";
			$res=runQuery("SELECT DISTINCT * FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_NAME='".str_replace("'","''",trim($P))."'");
			
			if (count($res)==0){echo $P." parent not found ";return false;}
			$PAR_ID[]=$res[0];
			$PAR_LS.="CHILDOF\t".$res[0]['ONTOLOGY_TAG']."\n";
			
		}


		/// Check parent
		$VALID=true;
		foreach ($PAR_ID as &$PARENT_ID)
		{
			
			echo "##### ".$PARENT_ID['ONTOLOGY_ENTRY_ID']."\n";

			$res=runQuery("select  OH.ONTOLOGY_LEVEL, OH.ONTOLOGY_LEVEL_LEFT, OH.ONTOLOGY_LEVEL_RIGHT 
				FROM ontology_hierarchy OH
				WHERE  oh.ontology_entry_id=".$PARENT_ID['ONTOLOGY_ENTRY_ID']);
				
				foreach ($res as $line)
				{
					$PARENT_ID['INFO'][]=$line;
					$res2=runQuery("SELECT MAX(ONTOLOGY_LEVEL_RIGHT) C FROM ONTOLOGY_HIERARCHY WHERE ONTOLOGY_LEVEL_LEFT>=".$line['ONTOLOGY_LEVEL_LEFT']
					.' AND ONTOLOGY_LEVEL_RIGHT <= '.$line['ONTOLOGY_LEVEL_RIGHT'].' AND ONTOLOGY_LEVEL='.($line['ONTOLOGY_LEVEL']+1));
					$MAX=$line['ONTOLOGY_LEVEL_LEFT']+1;
					$R_L=array('LEVEL'=>$line['ONTOLOGY_LEVEL']+1);
					if (count($res2)!=0 && $res2[0]['C']!='')
					{
						$MAX=$res2[0]['C']+1;
					
					}
					$R_L['L']=$MAX;
					$R_L['R']=floor($R_L['L']+($line['ONTOLOGY_LEVEL_RIGHT']-$MAX)/10);
				//	echo $line['ONTOLOGY_LEVEL_LEFT'].' ' .$line['ONTOLOGY_LEVEL_RIGHT'].' '.$MAX.' '.($line['ONTOLOGY_LEVEL_RIGHT']-$MAX)."\n";
					if ($MAX==$line['ONTOLOGY_LEVEL_RIGHT']){$VALID=false;break;}
					if ($R_L['L']==$R_L['R']){$VALID=false;break;}
					
					//echo ($line['ONTOLOGY_LEVEL_RIGHT']-$MAX)."\n";
					$PARENT_ID['CHILD'][]=$R_L;
				}
			if (!$VALID)break;

		}

		

		/// Get MAX TAG:
		$res=runQuery("select MAX(ONTOLOGY_TAG) T FROM ONTOLOGY_ENTRY");
		$T=explode("_",$res[0]['T'])[1];
		$MAX_TAG="LSO_".str_pad(($T+1), 7, "0", STR_PAD_LEFT);

		$res=runQuery("select MAX(ONTOLOGY_ENTRY_ID) T FROM ONTOLOGY_ENTRY");
		$ID=$res[0]['T']+1;

		$res=runQuery("select MAX(ONTOLOGY_PMID_ID) T FROM ONTOLOGY_PMID");
		$PUBID=$res[0]['T']+1;

		$res=runQuery("select MAX(ONTOLOGY_SYN_ID) T FROM ONTOLOGY_SYN");
		$SYNID=$res[0]['T']+1;

	$query='INSERT INTO ONTOLOGY_ENTRY (ONTOLOGY_ENTRY_ID,
	ONTOLOGY_TAG,
	ONTOLOGY_NAME,
	ONTOLOGY_DEFINITION) VAlues ('.$ID.",'".$MAX_TAG."','".str_replace("'","''",$INPUT['ename'])."','".str_replace("'","''",$INPUT['edesc'])."')";
echo "CREATE ENTRY AS ";
	if (!runQueryNoRes($query))
		{echo "Unable to insert\n".$query; return false;}
echo $ID."\n";
		if (count($INPUT['esyn'])>0)
		{
			
		foreach($INPUT['esyn'] as $SYN)
		{
			$query='INSERT INTO ONTOLOGY_SYN (ONTOLOGY_SYN_ID,ONTOLOGY_ENTRY_ID,
				SYN_TYPE,
	SYN_VALUE,
	SOURCE_ID) VALUES ('.$SYNID.",".$ID.",'EXACT','".str_replace("'","''",$SYN)."',(SELECT SOURCE_ID FROM SOURCE WHERE SOURCE_NAME='Internal'))";

			if (!runQueryNoRes($query)){echo  "Unable to insert synonym";
				runQueryNoRes("DELETE FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_ENTRY_ID=".$ID);
				return false;
			}
	++$SYNID;
		}
	}

	if (count($INPUT['epub'])>0)
	foreach($INPUT['epub'] as $PUB)
	{
		$res=runQuery("SELECT PMID_ENTRY_ID FROM PMID_ENTRY WHERE PMID=".$PUB);
		if (count($res)==0){echo "Unable to find Publication with PMID ".$PUB."\n";
			runQueryNoRes("DELETE FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_ENTRY_ID=".$ID);
			return false;}
		$query='INSERT INTO ONTOLOGY_PMID (ONTOLOGY_PMID_ID,
		ONTOLOGY_ENTRY_ID,
		PMID_ENTRY_ID) VALUES ('.$PUBID.",".$ID.",".$res[0]['PMID_ENTRY_ID'].")";
		if (!runQueryNoRes($query)){echo "Unable to insert pmid"."\n";
			runQueryNoRes("DELETE FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_ENTRY_ID=".$ID);
			return false;}
++$PUBID;
	}



	$TMP=explode("\n",$INPUT['edesc']);
	$STRT="";
	foreach( $TMP as $l)$STRT.="DESCRIP\t".$l."\n";
	
	$STRP="";
	foreach( $INPUT['epub'] as $l)$STRP.="PUBLI\t".$l."\n";
	$STRS="";
	foreach( $INPUT['esyn'] as $l)$STRS.="SYNONYM\t".$l."\n";
	$fp=fopen('PRD_DATA/ONTOLOGY/NEXT_VERSION.csv','a');
	if (!$fp){changeValue("ONTOLOGY","ERR_MESS","Unable to save the record into file");removeBlock("ONTOLOGY","VALID");return;}
	fputs($fp,"\n".
	"START\t".$MAX_TAG."\n".
	"NAME\t".$INPUT['ename']."\n".$STRP.$STRS.
	"CR_NAME\t".$INPUT['lname']."\t".$INPUT['fname']."\n".
	"CR_DATE\t".date("Y/m/d")."\n".
	$STRT.
	$PAR_LS.
	"END\n\n");



	if ($VALID)
	{
		
		foreach ($PAR_ID as &$PARENT_ID)
		{
			
			foreach ($PARENT_ID['CHILD'] as &$CHI)
			{
			$query='INSERT INTO ONTOLOGY_HIERARCHY (ONTOLOGY_ENTRY_ID,
			ONTOLOGY_LEVEL,
			ONTOLOGY_LEVEL_LEFT,
			ONTOLOGY_LEVEL_RIGHT) VALUES ('.$ID.",".($CHI['LEVEL']).",".$CHI['L'].",".$CHI['R'].")";
			echo $query."\n";
			if (!runQueryNoRes($query)){runQueryNoRes("DELETE FROM ONTOLOGY_ENTRY WHERE ONTOLOGY_ENTRY_ID=".$ID);echo "Unable to insert hierarchy\nFAILED QUERY:".$query."\n";return false;}
			}
		}
	}
	else {
		
		$res=runQuery("select oh.ontology_entry_id as par, oh2.ontology_entry_Id as chi
		FROM ontology_hierarchy oh, ontology_hierarchy oh2
		WHERE oh.ontology_level_left < oh2.ontology_level_left
		AND oh.ontology_level_right > oh2.ontology_level_right
		AND oh.ontology_level+1 = oh2.ontology_level");
		$REC=array();


		foreach ($res as $line){
			if (!isset($REC[$line['CHI']]))$REC[$line['CHI']]=array('ST'=>false);
			if (!isset($REC[$line['PAR']]))$REC[$line['PAR']]=array('ST'=>false);
		$REC[$line['PAR']]['CHILD'][$line['CHI']]=true;

		$REC[$line['CHI']]['PARENT'][$line['PAR']]=true;
		}
		
		foreach ($PAR_ID as $P)
		{
			
			$REC[$P['ONTOLOGY_ENTRY_ID']]['CHILD'][$ID]=true;
		}
		if (!isset($REC[$ID]))$REC[$ID]=array();
		
		$VALUE=0;
		//print_r($ROOTS);
		if (!runQueryNoRes("TRUNCATE TABLE ONTOLOGY_HIERARCHY")){changeValue("ONTOLOGY","ERR_MESS","Unable to delete hierarchy");removeBlock("ONTOLOGY","VALID");return;}
		$ROOTS=array();
		$ROOTS[0]=true;
		print_r($REC);
		genTree($REC,$ROOTS,0,$VALUE);
		foreach ($REC as $P=>$C)if (!$C['ST']){echo $P."\n";}
	}
}

	function genTree(&$DATA,$ROOTS,$LEVEL,&$LEVEL_V)
	{
		//global $fp;
		++$LEVEL;
	//	echo $LEVEL."\n";
	//	print_R($ROOTS);
		foreach ($ROOTS as $RID=>$T)
		{
			
			if (!isset($DATA[$RID])){echo $RID."\n";exit;;}
			$DATA[$RID]['ST']=true;
			//if (!isset($DATA[$RID]['DB'])){echo "DB:".$RID."\n";continue;}
			// for($I=0;$I<$LEVEL;++$I)echo "\t";
			// echo "PROCESSING ".$RID."\n";
			// print_r($DATA[$RID]);
			if ($LEVEL!=1)$LEVEL_V+=pow(10,13-$LEVEL);
			$LEVEL_LEFT=$LEVEL_V;
			if (isset($DATA[$RID]['CHILD']))
			{
				
				genTree($DATA,$DATA[$RID]['CHILD'],$LEVEL,$LEVEL_V);
				$LEVEL_V+=pow(10,13-$LEVEL);
			}else $LEVEL_V+=200;
			//for($I=0;$I<$LEVEL;++$I)echo "\t";

			++$LEVEL_V;$LEVEL_RIGHT=$LEVEL_V;
			$query='INSERT INTO ONTOLOGY_HIERARCHY (ONTOLOGY_ENTRY_ID,
			ONTOLOGY_LEVEL,
			ONTOLOGY_LEVEL_LEFT,
			ONTOLOGY_LEVEL_RIGHT) VALUES ('.$RID.",".$LEVEL.",".$LEVEL_LEFT.",".$LEVEL_RIGHT.")";
		//	echo $query."\n";
			if (!runQueryNoRes($query)){changeValue("ONTOLOGY","ERR_MESS","Unable to insert hierarchy");removeBlock("ONTOLOGY","VALID");return;}
			//fputs($fp,$DATA[$RID]['DB']."\t".$LEVEL."\t".$LEVEL_LEFT."\t".$LEVEL_RIGHT."\n");
		}
	}


	function compare(&$REF,&$ALT,$DEBUG=false)
		{
			if (implode(" " ,$REF)==implode(" " ,$ALT))return 1;
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
			return $FSCORE;
		
		}

?>
