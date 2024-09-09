<?php

if (!defined("BIORELS")) header("Location:/");
$ALL_NAME='';

if (!isset($MODULE_DATA['ALIGNMENT']))
{
	changeValue("oss","SEQUENCE",$USER_INPUT['PAGE']['VALUE']);
	removeBlock("oss","VALID");
	return;
}
removeBlock("oss","INVALID");


$MAXHEAD=0;$LEN_AL=0;$CURR_VALUES=array();$INTER_POS=array();
foreach ($MODULE_DATA['ALIGNMENT'] as $TYPE=>&$LIST)
{
	$NAME='';
	if ($TYPE=="REF")
	{
		$D=&$MODULE_DATA['REF_SEQ'];
		$T=$D['ISO_ID'];
		if (strlen($D['ISO_ID'])>30)$T=substr($D['ISO_ID'],0,27).'...';
		$NAME=$D['PROT_IDENTIFIER'].'-'.$T;
		$ALL_NAME.=$NAME;
		foreach ($LIST as $AL_POS=>$DO_POS)
		{
			if (!isset($D['INTER'][$DO_POS]))continue;
			foreach ($D['INTER'][$DO_POS] as $INAME)
			{
				if (!isset($INTER_POS[$INAME['CLASS']]))$INTER_POS[$INAME['CLASS']]=array();
			if (!isset($INTER_POS[$INAME['CLASS']][$AL_POS]))$INTER_POS[$INAME['CLASS']][$AL_POS]=array($INAME,array());
				else $INTER_POS[$INAME['CLASS']][$AL_POS][0]=$INAME;
			}
		}
	}
	else
	{
		$D=&$MODULE_DATA['ALT'][$TYPE];
		
		$NAME=$D['INFO']['SYMBOL'].'-'.$D['INFO']['ISO_ID'].' '.$D['INFO']['SCIENTIFIC_NAME'];
		if (strlen($NAME)>50)$NAME=substr($NAME,0,47).'...';
		$ALL_NAME.=$NAME;
		foreach ($LIST as $AL_POS=>$DO_POS)
		{
			if (!isset($D['ALIGNMENT']['INTER'][$DO_POS]))continue;
			foreach ($D['ALIGNMENT']['INTER'][$DO_POS] as $INAME)
			{
				if (!isset($INTER_POS[$INAME['CLASS']]))$INTER_POS[$INAME['CLASS']]=array();
				if (!isset($INTER_POS[$INAME['CLASS']][$AL_POS]))$INTER_POS[$INAME['CLASS']][$AL_POS]=array(array(),$INAME);
				else $INTER_POS[$INAME['CLASS']][$AL_POS][1]=$INAME;
			}
		}
	}

	
	$MAXHEAD=max($MAXHEAD,strlen($NAME));
	$LEN_AL=count($LIST);
	foreach ($LIST as $K=>$V){
	if ($K!=''){$CURR_VALUES[$TYPE]=$K;break;}
	}
}



$N_PER_LINE=50;
if ($WIDTH!=-1)
{
$fs = 16;					/// fs and fc set for Courier New
	$fc = 1.61;
	
	$ratio=9.601907;
	$N_PER_LINE = floor(floor($fc*$WIDTH / $fs -$MAXHEAD-5-15)/10)*10;
}
$STR=array();
$T=array_keys($INTER_POS);
$STR_INT=array();
foreach ($T as $N)$STR_INT[$N]=array();
$ALL_NAME=substr(md5('orthologs_'.$ALL_NAME),10);
$LINES=ceil($LEN_AL/$N_PER_LINE);$POS=1;


changeValue("oss","MD5",$ALL_NAME);

$TTL_LIST=array();$N_TTL=0;
#EBEBEB
for ($IL=0;$IL<$LINES*(1+count($MODULE_DATA['ALIGNMENT']));++$IL)
{
	$STR[$IL]="";
	foreach ($INTER_POS as $ICLASS=>&$INFO)$STR_INT[$ICLASS][$IL]="";
}

for ($IL=0;$IL<$LINES;++$IL)
{
	
	$NR=0;
	$END=$POS+$N_PER_LINE;$START=$POS;
////	echo "LINE : ".$IL." ".$START." " .$END."\n";;
	foreach ($MODULE_DATA['ALIGNMENT'] as $TYPE=>&$LIST)
	{
		//echo $TYPE."\n";
		$LINE_ID=$NR+$IL*(1+count($MODULE_DATA['ALIGNMENT']));++$NR;
		//echo $LINE_ID."\n";
		
		$NAME='';
	if ($TYPE=="REF")
	{
		$D=&$MODULE_DATA['REF_SEQ'];
		$T=$D['ISO_ID'];
		if (strlen($D['ISO_ID'])>30)$T=substr($D['DOMAIN_NAME'],0,27).'...';
		$NAME=$D['PROT_IDENTIFIER'].'-'.$T;
	}
	else
	{
		$D=&$MODULE_DATA['ALT'][$TYPE];
		$T=$D['INFO']['ISO_ID'].' ';
		if (strlen($D['INFO']['SCIENTIFIC_NAME'])>20)$T.=substr($D['INFO']['SCIENTIFIC_NAME'],0,17).'...';
		else $T.=$D['INFO']['SCIENTIFIC_NAME'];
		$NAME=$D['INFO']['SYMBOL'].'-'.$T;
	}
	$STR[$LINE_ID]=$NAME;

	foreach ($INTER_POS as $ICLASS=>&$INFO)$STR_INT[$ICLASS][$LINE_ID]=$NAME;
		for ($I=strlen($NAME);$I<$MAXHEAD;++$I){
			$STR[$LINE_ID].=' ';
			foreach ($INTER_POS as $ICLASS=>&$INFO)$STR_INT[$ICLASS][$LINE_ID].=' ';
		}
		$STR[$LINE_ID].=' '.$CURR_VALUES[$TYPE];
		foreach ($INTER_POS as $ICLASS=>&$INFO)$STR_INT[$ICLASS][$LINE_ID].=' '.$CURR_VALUES[$TYPE];
		for ($I=strlen((string)$CURR_VALUES[$TYPE]);$I<=4;++$I){
			$STR[$LINE_ID].=' ';
			foreach ($INTER_POS as $ICLASS=>&$INFO)$STR_INT[$ICLASS][$LINE_ID].=' ';
		}
		for ($T=$START;$T<$END;++$T)
		{
			if ($T>=$LEN_AL)break;
			$V=&$LIST[$T];
			if ($V==''){$STR[$LINE_ID].= '-';
				foreach ($INTER_POS as $ICLASS=>&$INFO)$STR_INT[$ICLASS][$LINE_ID].= '-';
				if ($T%10==0){$STR[$LINE_ID].=' ';foreach ($INTER_POS as $ICLASS=>&$INFO)$STR_INT[$ICLASS][$LINE_ID].= ' ';}continue;}
			$CURR_VALUES[$TYPE]=$V;
			if ($TYPE=='REF')
			{
				foreach ($INTER_POS as $ICLASS=>&$INFO){
					if (isset($INFO[$T]))
					{
						$STR_INT[$ICLASS][$LINE_ID].= '<span class="AA AA_'.$MODULE_DATA['REF_SEQ']['SEQ'][$V];
					if ($INFO[$T][0]!=array()){
					// 	$STR_INT[$ICLASS][$LINE_ID].=';border-bottom:1px solid black;cursor:pointer;" data-toggle="tooltip" class="ttn"  data-html="true" title="<table><tr><th>Class</th><th>Interaction</th><th>Instance</th></tr>';
					
					// 	$STR_INT[$ICLASS][$LINE_ID].= '<tr><td>'.$INFO[$T][0]['CLASS'].'</td><td>'.$INFO[$T][0]['INTERACTION_NAME'].'</td><td>'.$INFO[$T][0]['COUNT_INT'].'</td></tr>';
					
					// $STR_INT[$ICLASS][$LINE_ID].='</table>"';
					$N_TTL++;
						$TTL_LIST[$N_TTL]='<table class=\"table\"><tr><th>Class</th><th>Interaction</th><th>Instance</th></tr><tr><td>'.ucfirst(strtolower($INFO[$T][0]['CLASS'])).'</td><td>'.$INFO[$T][0]['INTERACTION_NAME'].'</td><td>'.$INFO[$T][0]['COUNT_INT'].'</td></tr></table>';
						$STR_INT[$ICLASS][$LINE_ID].=' ttl" title="Issue while loading" data-pos="'.$N_TTL.'"';
					}else $STR_INT[$ICLASS][$LINE_ID].= '"';
					
					
					
					$STR_INT[$ICLASS][$LINE_ID].= '>'.$MODULE_DATA['REF_SEQ']['SEQ'][$V].'</span>';
					}
					else $STR_INT[$ICLASS][$LINE_ID].= '-';
				}
				
				$STR[$LINE_ID].= '<span class="AA AA_'.$MODULE_DATA['REF_SEQ']['SEQ'][$V].'">'.$MODULE_DATA['REF_SEQ']['SEQ'][$V].'</span>';
			}
			else 
			{
				foreach ($INTER_POS as $ICLASS=>&$INFO){
					if (isset($INFO[$T]))
					{
						$STR_INT[$ICLASS][$LINE_ID].= '<span class="AA AA_'.$MODULE_DATA['ALT'][$TYPE]['ALIGNMENT']['SEQ'][$V];
						if ($INFO[$T][1]!=array()){
						// 	$STR_INT[$ICLASS][$LINE_ID].=';border-bottom:1px solid black;cursor:pointer;"data-toggle="tooltip" class="ttn"  data-html="true" title="<table><tr><th>Class</th><th>Interaction</th><th>Instance</th></tr>';
						
						// 	$STR_INT[$ICLASS][$LINE_ID].= '<tr><td>'.$INFO[$T][1]['CLASS'].'</td><td>'.$INFO[$T][1]['INTERACTION_NAME'].'</td><td>'.$INFO[$T][1]['COUNT_INT'].'</td></tr>';
						
						// $STR_INT[$ICLASS][$LINE_ID].='</table>"';
						$N_TTL++;
						$TTL_LIST[$N_TTL]='<table class=\"table\"><tr><th>Class</th><th>Interaction</th><th>Instance</th></tr><tr><td>'.ucfirst(strtolower($INFO[$T][1]['CLASS'])).'</td><td>'.$INFO[$T][1]['INTERACTION_NAME'].'</td><td>'.$INFO[$T][1]['COUNT_INT'].'</td></tr></table>';
						$STR_INT[$ICLASS][$LINE_ID].=' ttl" title="Issue while loading" data-pos="'.$N_TTL.'"';
						}else $STR_INT[$ICLASS][$LINE_ID].= '"';
						$STR_INT[$ICLASS][$LINE_ID].= '>'.$MODULE_DATA['ALT'][$TYPE]['ALIGNMENT']['SEQ'][$V].'</span>';
					}
					else $STR_INT[$ICLASS][$LINE_ID].= '-';
				}

				$STR[$LINE_ID].= '<span class="AA AA_'.$MODULE_DATA['ALT'][$TYPE]['ALIGNMENT']['SEQ'][$V].'">'.$MODULE_DATA['ALT'][$TYPE]['ALIGNMENT']['SEQ'][$V].'</span>';
			}
			if ($T%10==0){$STR[$LINE_ID].=' ';foreach ($INTER_POS as $ICLASS=>&$INFO)$STR_INT[$ICLASS][$LINE_ID].= ' ';}
		}
		
		
	}
	$POS+=$N_PER_LINE;
	
	
	

}


// foreach ($MODULE_DATA['ALIGNMENT'] as $TYPE=>&$LIST)
// 	{
		
// 		foreach ($LIST as $V)
// 		{
// 			if ($V==''){$STR.= '-';continue;}
// 			if ($TYPE=='REF')$STR.= $MODULE_DATA['REF_SEQ']['SEQ'][$V];
// 			else $STR.= $MODULE_DATA['ALT'][$TYPE]['ALIGNMENT']['SEQ'][$V];
// 		}
// 		$STR.= "\n";
// 	}
	
	changeValue("oss","SEQUENCE",implode("\n",$STR));
	$NBLOCK=1;
	$STRHEAD='';
	$STRBL='';
	foreach ($STR_INT as $K=>$V)
	{
		++$NBLOCK;
		$STRHEAD.='<li id="ortho_al_'.$ALL_NAME.'_tab_'.$NBLOCK.'" class="nav-item nav-link" onclick="showMenu(\'ortho_al_'.$ALL_NAME.'\','.$NBLOCK.','.(count($STR_INT)+1).')">'.ucfirst(strtolower($K)).'</li>'."\n";
		
		$STRBL.='<div id="ortho_al_'.$ALL_NAME.'_view_'.$NBLOCK.'" class="sequence w3-col s12 w3-container pre" style="white-space: pre;display:none" >';
		$STRBL.=implode("\n",$V);
		$STRBL.='</div>';
	}
	
	changeValue("oss","NBLOCK",count($STR_INT)+1);
	changeValue("oss","LI_BLOCKS",$STRHEAD);
	changeValue("oss","LIST_BLOCKS",$STRBL);
	changeValue("oss","LIST",json_encode ($TTL_LIST));

?>