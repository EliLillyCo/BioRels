<?php

ini_set('memory_limit', '1024M');
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}



$TG_DIR= getenv('TG_DIR');

if ($TG_DIR===false)  die('NO TG_DIR found');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
if (!is_dir($TG_DIR.'/PROCESS') && !mkdir($TG_DIR.'/PROCESS'))die('TG_DIR/PROCESS can\'t be created');


require_once('./queries.php');
 $str=file_get_contents('./queries.php');




 $BLOCKS=findBlocks($str);


$FILE_TO_LOAD=array(
	'/LIB/global.php'=>0,
	'/LIB/fct_utils.php'=>0,
	'/LIB/loader_process.php'=>0
);


foreach ($FILE_TO_LOAD as $FILE=>$RULE)
{
	if ($RULE==1 && !defined("MONITOR_JOB"))continue;
	$PATH=$TG_DIR.'/BACKEND/SCRIPT/'.$FILE;
	if ((include $PATH)==TRUE)continue;
	sendKillMail('000003','Unable to load file: '.$PATH);
}

date_default_timezone_set($GLB_VAR['TIMEZONE']);

$DB_CONN=null;
$DB_INFO=array();
$GLB_VAR['DB_SCHEMA']=getenv('DB_SCHEMA');
$GLB_VAR['SCHEMA_PRIVATE']=getenv('SCHEMA_PRIVATE');

connectDB();



function processBlock(&$BLOCK)
{
	$lines=explode("\n",$BLOCK);
	$PARAMS=array();
	$TITLE='';
	$FUNCTION='';
	$DESCRIPTION='';
	$ALIAS=array();
	$PORTAL='';
	foreach ($lines as $line)
	{
		$pos=stripos($line,'Title:');
		if ($pos!==false)
		{
			$TITLE=trim(substr($line,$pos+6));
			continue;
		}
		$pos=stripos($line,'Function:');
		if ($pos!==false)
		{
			$FUNCTION=trim(substr($line,$pos+9));
			continue;
		}
		$pos=stripos($line,'Description:');
		if ($pos!==false)
		{
			$DESCRIPTION=trim(substr($line,$pos+12));
			continue;
		}
		$pos=stripos($line,'Portal:');
		if ($pos!==false)
		{
			$PORTAL=trim(substr($line,$pos+7));
			continue;
		}
		$pos=stripos($line,'Alias:');
		if ($pos!==false)
		{
			$ALIAS[]=trim(substr($line,$pos+6));
			continue;
		}
		$pos=stripos($line,'Parameter:');
		if ($pos!==false)
		{
			$tab=explode("|",substr($line,$pos+10));
			if (count($tab)<4) die('Unable to parse line '.$line);
			$tab[0]=str_replace('$',"",trim($tab[0]));
			
			//if (!is_numeric($tab[0]))die('Parameter name should be numeric in line '.$line);
			$tab[1]=trim($tab[1]);
			$tab[2]=trim($tab[2]);
			// if ($tab[2]!='int' && $tab[2]!='string' && $tab[2]!='float' && $tab[2]!='bool' && $tab[2]!='array')
			// die('Parameter type should be '.$tab[2].' for parameter '.$tab[0].' in line '.$line);
			$tab[3]=trim($tab[3]);
			//if ($tab[3]!='required' && $tab[3]!='optional')die('Parameter type should be required or optional in line '.$line);
			if (isset($tab[4]))
			{
				$pos=strpos($tab[4],'Default:');
				if ($pos!==false) $tab[4]=substr($tab[4],$pos+8);
				
				$tab[4]=trim($tab[4]);
				if ($tab[4]=="''")$tab[4]='';
			}
			$PARAMS[$tab[0]]=array('NAME'=>$tab[1],'TYPE'=>$tab[2],'REQUIRED'=>$tab[3],'DEFAULT'=>isset($tab[4])?$tab[4]:'');
			continue;
		}
	}
	//if ($TITLE=='' || $FUNCTION=='' || $DESCRIPTION=='')die('Unable to parse block '.$BLOCK);
	//if ($PARAMS==array())die('No parameter found in block '.$BLOCK);
	return array('TITLE'=>$TITLE,'FUNCTION'=>$FUNCTION,'DESCRIPTION'=>$DESCRIPTION,'PARAMS'=>$PARAMS,'ALIAS'=>$ALIAS,'PORTAL'=>$PORTAL);
}

function findBlocks(&$str_file)
{
	$BLOCKS=array();
	$N=0;$prev_pos=0;
   do {
        $pos = strpos($str_file, '$[API]',$prev_pos);
		
        $end_pos = strpos($str_file, '$[/API]', $pos);
		
        if ($pos !== false && $end_pos !== false) {
			$str=substr($str_file, $pos, $end_pos - $pos + 7);
			$BLOCKS[]=processBlock($str);
            
        }
		$prev_pos=$end_pos+7;
		
    } while ($pos !== false);
	return $BLOCKS;
}


$args=array();
$TO_CSV=false;
for($I=1;$I<count($argv);++$I)
{
	if ($argv[$I]=='--TO_CSV')
	{
		$TO_CSV=true;
		continue;
	}
	$args[]=$argv[$I];
}


if (!$TO_CSV)
{
echo json_encode(runAPIQuery($args,$BLOCKS),JSON_PRETTY_PRINT);
}
else
{
	$RESULTS=runAPIQuery($args,$BLOCKS);
	$CSV='';
	$KEYS=array();
	foreach ($RESULTS as $record)
	{
		foreach ($record as $K=>$V)
		{
			$KEYS[$K]=true;
		}
	}
	echo implode("\t",array_keys($KEYS))."\n";
	foreach ($RESULTS as $record)
	{
		$T='';
		foreach ($KEYS as $K=>&$DUMMY)
		{
			if (isset($record[$K]))$T.=$record[$K];
			else $T.="N/A";
			$T.="\t";
		}
		$CSV.=substr($T,0,-1)."\n";
	}
	echo $CSV;
}

function runAPIQuery(&$args,&$BLOCKS)
{
	
	
	$function_name=$args[0];
	$USER_PARAM=array();
	for ($I=1;$I<count($args);++$I)
	{
		if (substr($args[$I],0,1)=='-')
		{
			$param=substr($args[$I],1);
			if (!isset($args[$I+1]))die('No value for parameter:'.$param_name.' for '.$function_name);
			$value=$args[$I+1];
			$USER_PARAM[$param]=$value;
		}
	}

	
	foreach ($BLOCKS as &$BLOCK)
	{
		$FOUND=false;
		if ($BLOCK['FUNCTION']==$function_name)$FOUND=true;
		if (isset($BLOCK['ALIAS']) && in_array($function_name,$BLOCK['ALIAS']))$FOUND=true;	
		if (!$FOUND)continue;

		$PARAMS=&$BLOCK['PARAMS'];
		
		//if (count($PARAMS)!=count($args)-1) die("Different number of parameters");
		$N_PARAM=0;
		$FCT_VALUES=array();
		foreach ($PARAMS as $KEY_PARAM=>&$PARAM)
		{
			
			if ($PARAM['REQUIRED']=='required' && (!isset($USER_PARAM[$KEY_PARAM]) || $USER_PARAM[$KEY_PARAM]==''))
				die('Parameter '.$KEY_PARAM.' is required' );
			if ($PARAM['REQUIRED']=='optional' && (!isset($USER_PARAM[$KEY_PARAM]) || $USER_PARAM[$KEY_PARAM]==''))
			{
				
				if (isset($PARAM['DEFAULT'])&& $PARAM['DEFAULT']!='')
				{
				
				$N_PARAM++;
				if (strtolower($PARAM['DEFAULT'])=="false")$FCT_VALUES[$N_PARAM]=false;
				else if (strtolower($PARAM['DEFAULT'])=="true")$FCT_VALUES[$N_PARAM]=true;
				else $FCT_VALUES[$N_PARAM]=$PARAM['DEFAULT'];
				}
				continue;
			}
			
			if (!isset($USER_PARAM[$KEY_PARAM])) continue;
			$value=$USER_PARAM[$KEY_PARAM];
			
			if ($PARAM['TYPE']=='array')
			{
				
				$value=explode(",",$value);
			}
			if ($PARAM['TYPE']=='int' && !is_numeric($value))
				die ('Parameter '.$KEY_PARAM.' should be int: '. $value );
			if ($PARAM['TYPE']=='float' && !is_float($value))
				die ('Parameter '.$KEY_PARAM.' should be float' );
			if ($PARAM['TYPE']=='multi_array')
			{
				$tab=explode(";",$value);
				
				++$N_PARAM;
				foreach ($tab as $record)
				{
					if ($record=='')continue;
					$pos=strpos($record,"=");
					if ($pos===false)die('Unable to parse multi_array parameter '.$KEY_PARAM.' with value '.$value);
					
					$FCT_VALUES[$N_PARAM][substr($record,0,$pos)]=explode(",",str_Replace("'","''",substr($record,$pos+1)));
					
				}
				continue;
				
			}
			if ($PARAM['TYPE']=='boolean')
			{
				if ($value=='true')$value=true;
				else if ($value=='false')$value=false;
				else if ($value=='1')$value=true;
				else if ($value=='0')$value=false;
				else if ($value=='Y')$value=true;
				else if ($value=='N')$value=false;
				else if ($value=='yes')$value=true;
				else if ($value=='no')$value=false;
				
			}
			$N_PARAM++;
			$FCT_VALUES[$N_PARAM]=str_replace("'","''",$value);
			
			

		}


		$RESULTS=array();
		switch ($N_PARAM)
		{
			case 0: $RESULTS=call_user_func($function_name);break;
			case 1: $RESULTS=call_user_func($function_name,$FCT_VALUES[1]);break;
			case 2: $RESULTS=call_user_func($function_name,$FCT_VALUES[1],$FCT_VALUES[2]);break;
			case 3: $RESULTS=call_user_func($function_name,$FCT_VALUES[1],$FCT_VALUES[2],$FCT_VALUES[3]);break;
			case 4: $RESULTS=call_user_func($function_name,$FCT_VALUES[1],$FCT_VALUES[2],$FCT_VALUES[3],$FCT_VALUES[4]);break;
			case 5: $RESULTS=call_user_func($function_name,$FCT_VALUES[1],$FCT_VALUES[2],$FCT_VALUES[3],$FCT_VALUES[4],$FCT_VALUES[5]);break;
			case 6: $RESULTS=call_user_func($function_name,$FCT_VALUES[1],$FCT_VALUES[2],$FCT_VALUES[3],$FCT_VALUES[4],$FCT_VALUES[5],$FCT_VALUES[6]);break;
			case 7: $RESULTS=call_user_func($function_name,$FCT_VALUES[1],$FCT_VALUES[2],$FCT_VALUES[3],$FCT_VALUES[4],$FCT_VALUES[5],$FCT_VALUES[6],$FCT_VALUES[7]);break;
			case 8: $RESULTS=call_user_func($function_name,$FCT_VALUES[1],$FCT_VALUES[2],$FCT_VALUES[3],$FCT_VALUES[4],$FCT_VALUES[5],$FCT_VALUES[6],$FCT_VALUES[7],$FCT_VALUES[8]);break;
			default: "Too many parameters. Please add an additional case in prep_queries.php";break;
		}
		return $RESULTS;
	}
	die ('Unable to find '.$function_name);
}

?>