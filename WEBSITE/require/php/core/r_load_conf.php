<?php

///////////////////
/////////////////// file: r_load_conf.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 11/26/18
/////////////////// purpose: Load configuration file
/// BIORELS defined in index.php. Not existing? Go to index.php
if (!defined("BIORELS")) header("Location:/");/// BIORELS defined in index.php. Not existing? Go to index.php

if (!is_file('WEBSITE_CONFIG'))throw new Exception("Unable to find CONFIG File ",ERR_TGT_SYS);
else loadConfig('WEBSITE_CONFIG',false);


if (is_file('private/WEBSITE_CONFIG')) loadConfig('private/WEBSITE_CONFIG',true);


function loadConfig($file,$IS_PRIVATE)
{
	global $GLB_CONFIG;
	$fp = fopen($file,'r'); if(!$fp)throw new Exception("Unable to open CONFIG File ".$file,ERR_TGT_SYS);
	while (!feof($fp))
	{
		$line = trim(str_replace( "\r",'',stream_get_line($fp,500,"\n")));
		
		if (substr($line,0,1)=="#" || strlen($line)==0)continue;
		// echo "<pre>";print_r(substr($line,0,1)=="#" || strlen($line)==0);exit;
		$tab=array_values(array_filter(explode("\t",$line)));
		if ($tab[0]=="START" )processGroup($tab,$fp,$GLB_CONFIG,$IS_PRIVATE);
		else if ($tab[0]=="EXPORT_TAGS") $GLB_CONFIG['EXPORT_TAG']=explode("|",$tab[1]);
		
		else if ($tab[0]=="LINK")
		{
			unset($tab[0]);
			if (count($tab)==3)$GLB_CONFIG['LINK'][$tab[1]][$tab[2]]=$tab[3];
			else $GLB_CONFIG['LINK'][$tab[1]]=$tab[2];
		} else if ($tab[0]=="REGEX") {
			if (count($tab)==3)$GLB_CONFIG['REGEX'][$tab[1]][]=$tab[2];
			
		}else if ($tab[0]=='FILE')
		{
			if (count($tab)==5)$GLB_CONFIG['FILE'][$tab[1]][$tab[2]]=array($tab[3],$tab[4]);
		}
	}

	fclose($fp);	
}

function processGroup($tab_info, &$fp, &$GLB_CONFIG, $IS_PRIVATE)
{
	$GRP = array();
	if ($IS_PRIVATE) $GRP['IS_PRIVATE'] = true;
	
	do {
		
		$line = trim(str_replace( "\r",'',stream_get_line($fp,500,"\n")));
		if (substr($line,0,1)=='#')continue;
		if ($line == '') continue;
		$tab = array_values(array_filter(explode("\t", $line)));
		
		if ($tab[0] == "END")break;
		
		if ($tab_info[1] == 'GLOBAL'){
			
			if ($tab[0]=="EMAIL_GROUP") {
				if (count($tab)==4)$GRP['EMAIL_GROUP'][$tab[1]]=array($tab[2],$tab[3]);
				
			} else 		$GRP[$tab[0]] = $tab[1];
			continue;
		}
		$NAME = $tab[0];unset($tab[0]);
		if ($NAME=="TAG")$GRP[$NAME][]=array_values($tab);
		else if ($NAME=="LEVEL")$GRP[$NAME]=$tab[1];
		else if ($NAME=="LOC")$GRP[$NAME]=$tab[1];
		else if ($NAME=="HTML_TAG")$GRP[$NAME]=$tab[1];
		else if ($NAME=="FNAME")$GRP[$NAME]=$tab[1];
		else if ($NAME=="DESC")$GRP[$NAME]=$tab[1];
		else if ($NAME=="WITH_EXPORT")$GRP[$NAME]=$tab[1];
		else if ($NAME=="WITH_DATA")$GRP[$NAME]=$tab[1];
		else if ($NAME=="PORTAL_ID")$GRP[$NAME]=$tab[1];
		else if ($NAME=="NAME")$GRP[$NAME]=$tab[1];
		// TODO: check this
		else if ($NAME=="IS_PRIVATE")$GRP[$NAME]=$tab[1];
		else throw new Exception("Unrecognized tag in ".$line,ERR_TGT_SYS);
	}while(!feof($fp));
	if (!$IS_PRIVATE && !isset($GRP['LEVEL']))
	{
		$GRP['LEVEL']='1111111111';
	}
	if ($tab_info[1]=="GLOBAL")
	{
		if (!isset($GLB_CONFIG[$tab_info[1]]))$GLB_CONFIG[$tab_info[1]]=$GRP;
		else $GLB_CONFIG[$tab_info[1]]=array_merge($GLB_CONFIG[$tab_info[1]],$GRP);
	}
	else $GLB_CONFIG[$tab_info[1]][$tab_info[2]][$IS_PRIVATE]=$GRP;
}

