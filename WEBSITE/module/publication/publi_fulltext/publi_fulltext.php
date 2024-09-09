<?php

changeValue("publi_fulltext","PMID",$USER_INPUT['PAGE']['VALUE']);


$MAP_PMC_OFFSET=array();
foreach ($MODULE_DATA['TEXT'] as $ID=> &$RECORD) $MAP_PMC_OFFSET[$ID]=$RECORD['OFFSET_POS'];

$MAP_TYPE_ID=array(
	'SM'=>'SM_ENTRY_ID',
	'DRUG'=>'DRUG_ENTRY_ID',
	'GENE'=>'GN_ENTRY_ID',
	'DISEASE'=>'DISEASE_ENTRY_ID',
	'ANATOMY'=>'ANATOMY_ENTRY_ID',
	'CELL'=>'CELL_ENTRY_ID',
	'GO'=>'GO_ENTRY_ID',
	'CLINICAL'=>'CLINICAL_TRIAL_ID',
	'COMPANY'=>'COMPANY_ENTRY_ID'
);

$RULES=array();
$LINE_MATCHES=array();
foreach ($MODULE_DATA['MATCH'] as $TYPE=>&$MATCH)
{
	if ($MATCH==array())continue;
	
	foreach ($MATCH as &$M)
	{
		$LINE_MATCHES[$M['PMC_FULLTEXT_ID']][]=array('TYPE'=>$TYPE,'ID'=>$M[$MAP_TYPE_ID[$TYPE]],'LOC_INFO'=>$M['LOC_INFO']);
		$RULES[$TYPE][$M[$MAP_TYPE_ID[$TYPE]]][]=$MAP_PMC_OFFSET[$M['PMC_FULLTEXT_ID']];
	}
	
}



//exit;



$STR='<select id="filter_${ID}" class="fulltext_filters w3-select"><option value="NO">No filters</option>';
$STR_S='';
$MAP_TYPE=array(
	'SM'=>'Compound',
	'DRUG'=>'Drug',
	'GENE'=>'Gene',
	'DISEASE'=>'Disease',
	'ANATOMY'=>'Anatomy/Tissue',
	'CELL'=>'Cell line',
	'GO'=>'Gene Ontology',
	'CLINICAL'=>'Clinical Trial',
	'COMPANY'=>'Company'
);


foreach ($MODULE_DATA['METADATA'] as $TYPE=>&$MATCH)
{
	if ($MATCH==array())continue;
	$STR.='<option value="'.$TYPE.'">'.$MAP_TYPE[$TYPE].'</option>';
	$STR_S.='<select id="filter_${ID}_'.$TYPE.'" class="w3-select sub_filters filters_${ID}" style="display:none">
	<option value="NO_FILTERS" selected="selected">No filters</option>';
	foreach ($MATCH as &$M)
	{
		
		if (!isset($RULES[$TYPE][$M[$MAP_TYPE_ID[$TYPE]]]))continue;
		$LIST_LINES=$RULES[$TYPE][$M[$MAP_TYPE_ID[$TYPE]]];
		sort($LIST_LINES);
		$STR_LINES=implode(",",array_unique($LIST_LINES));
		
		switch ($TYPE)
		{
			case 'DRUG':$STR_S.='<option value="'.$STR_LINES.'">'.$M['DRUG_PRIMARY_NAME'].'</option>';break;
			case 'DISEASE':$STR_S.='<option value="'.$STR_LINES.'">'.$M['DISEASE_NAME'].'</option>';break;
			case 'ANATOMY':$STR_S.='<option value="'.$STR_LINES.'">'.$M['ANATOMY_NAME'].'</option>';break;
			case 'CELL':$STR_S.='<option value="'.$STR_LINES.'">'.$M['CELL_NAME'].' ('.$M['CELL_TYPE'].')</option>';break;
			case 'GO':$STR_S.='<option value="'.$STR_LINES.'">'.$M['NAME'].'</option>';break;
			case 'GENE':$STR_S.='<option value="'.$STR_LINES.'">'.$M['SYMBOL'].'</option>';break;
			case 'SM':$STR_S.='<option value="'.$STR_LINES.'">'.$M['NAME'][0]['SM_NAME'].'</option>';break;
			case 'CLINICAL':$STR_S.='<option value="'.$STR_LINES.'">'.$M['CLINICAL_TRIAL_NAME'].'</option>';break;
		}

		// switch ($TYPE)
		// {
		// 	case 'DRUG':$STR_S.='<option value="'.$M['DRUG_PRIMARY_NAME'].'">'.$M['DRUG_PRIMARY_NAME'].'</option>';break;
		// 	case 'DISEASE':$STR_S.='<option value="'.$M['DISEASE_TAG'].'">'.$M['DISEASE_NAME'].'</option>';break;
		// 	case 'ANATOMY':$STR_S.='<option value="'.$M['ANATOMY_TAG'].'">'.$M['ANATOMY_NAME'].'</option>';break;
		// 	case 'CELL':$STR_S.='<option value="'.$M['CELL_ACC'].'">'.$M['CELL_NAME'].' ('.$M['CELL_TYPE'].')</option>';break;
		// 	case 'GO':$STR_S.='<option value="'.$M['AC'].'">'.$M['NAME'].'</option>';break;
		// 	case 'GENE':$STR_S.='<option value="'.$M['GENE_ID'].'">'.$M['SYMBOL'].'</option>';break;
		// 	case 'SM':$STR_S.='<option value="'.$M['MD5_HASH'].'">'.$M['NAME'][0]['SM_NAME'].'</option>';break;
		// 	case 'CLINICAL':$STR_S.='<option value="'.$M['TRIAL_ID'].'">'.$M['CLINICAL_TRIAL_NAME'].'</option>';break;
		// }
	}
	$STR_S.='</select>';
}
$STR.='</select>';
$STR_ALL='';
for ($I=1;$I<=3;++$I)
{
	$STR_T=$STR.$STR_S;
	$STR_T=str_replace('${ID}',$I,$STR_T);
	$STR_ALL.='<div class="w3-col s12 m4 l4">'.$STR_T.'</div>';
}
changeValue("publi_fulltext","FILTERS",$STR_ALL);



function checkGroup(&$CURR_GROUP_ID,$NEW_GROUP_ID,&$IS_OPEN)
{
	$STR='';
	if ($CURR_GROUP_ID==$NEW_GROUP_ID)return '';
	if ($CURR_GROUP_ID!=-1)$STR.='</p><p class="infos w3-container" id="block_info_'.$CURR_GROUP_ID.'" style="display:none"></p>';
	$STR.='<p id="block_'.$NEW_GROUP_ID.'">';
	$CURR_GROUP_ID=$NEW_GROUP_ID;
	$IS_OPEN=true;
	return $STR;
}




$STR='';
$IS_OPEN=false;
$IN_ABSTRACT=false;
$DIV_OPEN=false;
$CURR_GROUP_ID=-1;
foreach ($MODULE_DATA['TEXT'] as $ID=> &$RECORD)
{
	if ($RECORD['SECTION_TYPE']=='TITLE')
	{
		if ($DIV_OPEN)
		{
			$STR.='</div>';$DIV_OPEN=false;
		}
		$STR.='<h1>'.$RECORD['FULL_TEXT'].'</h1>';
	}
	else if ($RECORD['SECTION_TYPE']=='ABSTRACT')
	{
		if ($DIV_OPEN)
		{
			$STR.='</div>';$DIV_OPEN=false;
		}
		if ($RECORD['SECTION_SUBTYPE']=='section')
		{
			$STR.='<h2>Abstract</h2>';
			$IN_ABSTRACT=true;
		}
		else if ($RECORD['SECTION_SUBTYPE']=='title')
		{
			$STR.='<h3>'.$RECORD['FULL_TEXT'].'</h3>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='text')
		{
			$STR.=checkGroup($CURR_GROUP_ID,$RECORD['GROUP_ID'],$IS_OPEN);
			$STR.='<span class="line_ft" id="line_'.$RECORD['OFFSET_POS'].'">'.convertLine($MODULE_DATA,$ID,$LINE_MATCHES,$CURR_GROUP_ID).'. </span>';
		}
		else
		{
		die ("Unrecognized section subtypeA:".$RECORD['SECTION_SUBTYPE']);
		}

		
	
	}
	else if ($RECORD['SECTION_TYPE']=='SECTION')
	{
		$IS_OPEN=false;
		$STR.='</p>';
		if ($RECORD['SECTION_SUBTYPE']=='title_2')
		{
		$STR.='<h2>'.$RECORD['FULL_TEXT'].'</h2>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='title_3')
		{
		$STR.='<h3>'.$RECORD['FULL_TEXT'].'</h3>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='title_4')
		{
		$STR.='<h4>'.$RECORD['FULL_TEXT'].'</h4>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='title_5')
		{
		$STR.='<h5>'.$RECORD['FULL_TEXT'].'</h5>';
		}
		
	}
	else if ($RECORD['SECTION_TYPE']=='BACKGROUND'
	||$RECORD['SECTION_TYPE']=='DISCUSSION'
	||$RECORD['SECTION_TYPE']=='CONCLUSION'
	||$RECORD['SECTION_TYPE']=='RESULTS'
	||$RECORD['SECTION_TYPE']=='COPYRIGHT'
	||$RECORD['SECTION_TYPE']=='REFERENCES'
	||$RECORD['SECTION_TYPE']=='LICENSE')
	{
		if ($DIV_OPEN)
		{
			$STR.='</div>';$DIV_OPEN=false;
		}
		 if ($RECORD['SECTION_SUBTYPE']=='title')
		{
			$STR.='<h3>'.$RECORD['FULL_TEXT'].'</h3>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='text')
		{
			$STR.=checkGroup($CURR_GROUP_ID,$RECORD['GROUP_ID'],$IS_OPEN);
			$STR.='<span class="line_ft" id="line_'.$RECORD['OFFSET_POS'].'">'.convertLine($MODULE_DATA,$ID,$LINE_MATCHES,$CURR_GROUP_ID).'. </span>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='section')
		{
			$IS_OPEN=false;
		$STR.='</p>';
		$STR.='<h2>'.$RECORD['FULL_TEXT'].'</h2>';
		}
		else
		{
		die ("Unrecognized section subtypeB:".$RECORD['SECTION_SUBTYPE']);
		}
	}
	else if ($RECORD['SECTION_TYPE']=='TEXT')
	{
		if ($DIV_OPEN)
		{
			$STR.='</div>';$DIV_OPEN=false;
		}
		$STR.=checkGroup($CURR_GROUP_ID,$RECORD['GROUP_ID'],$IS_OPEN);
			$STR.='<span class="line_ft" id="line_'.$RECORD['OFFSET_POS'].'">'.convertLine($MODULE_DATA,$ID,$LINE_MATCHES,$CURR_GROUP_ID).'. </span>';
	}
	else if ($RECORD['SECTION_TYPE']=='FIGURE')
	{
		if ($RECORD['SECTION_SUBTYPE']=='fig_info')
		{
			if ($DIV_OPEN)
		{
			$STR.='</div>';$DIV_OPEN=false;
		}
			$DIV_OPEN=true;
			
			$STR.='<div style="width:100%;text-align:center"><img src="/PUBLICATION_IMAGE/'.$MODULE_DATA['INFO']['PMC_ID'].'/PARAMS/'.$RECORD['FULL_TEXT'].'"/><br/> ';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='fig_title')
		{
			$STR.='<h5>'.$RECORD['FULL_TEXT'].'</h5>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='fig_label')
		{
			$STR.='<h5>'.$RECORD['FULL_TEXT'].'</h5>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='fig_text')
		{
			$STR.=checkGroup($CURR_GROUP_ID,$RECORD['GROUP_ID'],$IS_OPEN);
			$STR.='<span class="line_ft" id="line_'.$RECORD['OFFSET_POS'].'">'.convertLine($MODULE_DATA,$ID,$LINE_MATCHES,$CURR_GROUP_ID).'. </span>';
		}
		else die("Unrecognized Figure section subtype:".$RECORD['SECTION_SUBTYPE']);
	}
	else if ($RECORD['SECTION_TYPE']=='TABLE')
	{
		if ($RECORD['SECTION_SUBTYPE']=='table_id')
		{
			if ($DIV_OPEN)
		{
			$STR.='</div>';$DIV_OPEN=false;
		}
			
		}
		
		else if ($RECORD['SECTION_SUBTYPE']=='table_label')
		{
			if ($DIV_OPEN)
			{
				$STR.='</div>';$DIV_OPEN=false;
			}
			$STR.='<h5>'.$RECORD['FULL_TEXT'].'</h5>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='table_caption')
		{
			if ($DIV_OPEN)
			{
				$STR.='</div>';$DIV_OPEN=false;
			}
			$STR.='<h5>'.$RECORD['FULL_TEXT'].'</h5>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='table_text')
		{
			$STR.=checkGroup($CURR_GROUP_ID,$RECORD['GROUP_ID'],$IS_OPEN);
			$STR.='<table class="table" id="line_'.$RECORD['OFFSET_POS'].'">'.$RECORD['FULL_TEXT'].'</table>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='table_foot')
		{
			$STR.=checkGroup($CURR_GROUP_ID,$RECORD['GROUP_ID'],$IS_OPEN);
			$STR.='<span id="line_'.$RECORD['OFFSET_POS'].'">'.$RECORD['FULL_TEXT'].'</span>';
		}
		else die ("unrecognized Table section subtype:".$RECORD['SECTION_SUBTYPE']);
	}
	else if ($RECORD['SECTION_TYPE']=='REF')
	{
		if ($DIV_OPEN)
		{
			$STR.='</div>';$DIV_OPEN=false;
		}
		$T=convertBiorelsTags($RECORD['FULL_TEXT'],$CURR_GROUP_ID);
		
		$STR.=$T;
		
	}
	else if ($RECORD['SECTION_TYPE']=='SUPPL')
	{
		if ($DIV_OPEN)
		{
			$STR.='</div>';$DIV_OPEN=false;
		}
		
		 if ($RECORD['SECTION_SUBTYPE']=='title_3')
		{
			$STR.='<h5>'.$RECORD['FULL_TEXT'].'</h5>';
		}
		else if ($RECORD['SECTION_SUBTYPE']=='suppl_caption')
		{
			$STR.='<p>'.$RECORD['FULL_TEXT'].'</p>';
		}
		// else if ($RECORD['SECTION_SUBTYPE']=='text')
		// {
		// 	$STR.=checkGroup($CURR_GROUP_ID,$RECORD['GROUP_ID'],$IS_OPEN);
		// 	$STR.='<span class="line_ft" id="line_'.$RECORD['OFFSET_POS'].'">'.convertLine($MODULE_DATA,$ID,$LINE_MATCHES).'. </span>';
		// }
		// else if ($RECORD['SECTION_SUBTYPE']=='section')
		// {
		// 	$IS_OPEN=false;
		// $STR.='</p>';
		// $STR.='<h2>'.$RECORD['FULL_TEXT'].'</h2>';
		// }
		else
		{
			print_R($RECORD);
		die ("Unrecognized section subtype for suppl:".$RECORD['SECTION_SUBTYPE']);
		}
	}
	else 
	{
		print_R($RECORD);
		die ("Unknow section type:".$RECORD['SECTION_TYPE']);
	}

}

changeValue("publi_fulltext",'TEXT',$STR);


function convertLine(&$MODULE_DATA,&$ID,&$LINE_MATCHES,$GROUP_ID)
{
	 $COLORS=array('GENE'=>'#263f6a',
	'DISEASE'=>'#196F91',
	'DRUG'=>'#a30015',
	'ANATOMY'=>'rgba(189, 45, 135, 1)',
	'CLINICAL'=>'#d76565',
	'GO'=>'#966e3c',
	'COMPANY'=>'#ff0000',
	'CELL'=>'#00ffff',
	'SM'=>'purple');
$DEBUG=false;
	$LINE=$MODULE_DATA['TEXT'][$ID]['FULL_TEXT'];
	if (!isset($LINE_MATCHES[$ID]))return convertBiorelsTags($LINE,$GROUP_ID);
	$MATCHES=$LINE_MATCHES[$ID];
	if ($DEBUG)
	{echo '<pre>';

	echo $LINE."\n";
	foreach ($MATCHES as $M)
	{
		echo $M['TYPE']."\t".$M['ID']."\t".$M['LOC_INFO']."\n";
	}
}

	$coverage=array();
	$words=explode(" ",$LINE);
	$n_words=count($words);
	for ($I=0;$I<$n_words;++$I)
	{
		$coverage[$I]=array();
	}
	
	
	$list_matches=array();
	
	foreach ($MATCHES as $id_match=>&$MATCH)
	{
		$tab=explode("|",$MATCH['LOC_INFO']);
		$min=(int)$tab[0];
		$max=(int)$tab[1]+$min;
		for ($I=$min;$I<$max;++$I)
		{
			if ($I>=$n_words)break;
			$coverage[$I][]=$id_match;
		}
	}
	$FINAL_LINE='';
	if ($DEBUG)
	{
	echo "COVERAGE:\n";
	foreach ($coverage as $I=>&$LIST)
	{
		echo $I.":".implode(",",$LIST)."\t";
	}
	echo "\n";
	

	echo "PROCESSING\n";
}
	for ($ID_WORD=0;$ID_WORD<$n_words;++$ID_WORD)
	{
		if ($DEBUG)echo $ID_WORD." ".$words[$ID_WORD];
		/// No annotation for that word - we continue
		if (count($coverage[$ID_WORD])==0)
		{
			if ($DEBUG)echo "\tNO ANNOT\n";
			$FINAL_LINE.=$words[$ID_WORD].' ';
			continue;
		}
		/// Listing all annotations for that range
		$list_range_match=array();
		foreach ($coverage[$ID_WORD] as $value)
		{
			if (in_array($value,$list_range_match))continue;
			$list_range_match[]=$value;
		}
		if ($DEBUG)echo "\tCOVERAGE ".$ID_WORD." ".count($list_range_match)."\n";
		$FINAL_LINE.='||START||'.$words[$ID_WORD];
		for ($ID_WORD2=$ID_WORD+1;$ID_WORD2<=$n_words;++$ID_WORD2)
		{
			if ($DEBUG)echo "\t\tWORD ".$ID_WORD2." ";
			if ($ID_WORD2!=$n_words)$words[$ID_WORD2]."\n";
			$VALID=true;

			if ($ID_WORD2==$n_words)$VALID=false;
			else 
			{
				if (count($coverage[$ID_WORD2])==0)$VALID=false;
				$FOUND=false;
				foreach ($coverage[$ID_WORD2] as $value)
				{
					if (in_array($value,$list_range_match))$FOUND=true;
					
				}
			
				if (!$FOUND)$VALID=false;
			}
			if (!$VALID)
			{
				if ($DEBUG)echo "\t\tFINAL COVERAGE ".count($list_range_match)."\n";
				$rule_str='';
				$types=array();
				foreach ($list_range_match as $value)
				{
					$type=$MATCHES[$value]['TYPE'];
					if (!in_array($type,$types)) $types[]=$type;
					$rule_str.=$type.':'.$MATCHES[$value]['ID'].'/';
				}
				$color='';
				if ($DEBUG)echo "\t\tTYPES:\t".implode(",",$types)."\n";
				
				
				if (count($types)==1)
				{
					$color=$COLORS[$types[0]];
				}
				else
				{
					$color="red";
				}
				$FINAL_LINE=str_replace('||START||','<span style="color:'.$color.';font-weight:bold;font-size:1.1em" onmouseover="showPubliData(\''.substr($rule_str,0,-1).'\',\''.$GROUP_ID.'\')">',$FINAL_LINE);
				if (substr($FINAL_LINE,-1)==')')$FINAL_LINE=substr($FINAL_LINE,0,-1).'</span>)';
				else $FINAL_LINE.='</span> ';
				break;
			}
			else
			{
				if ($DEBUG)echo "\t\tALT COVERAGE:".count($coverage[$ID_WORD2])."\n";
				foreach ($coverage[$ID_WORD2] as $value)
				{
					if (in_array($value,$list_range_match))continue;
					$list_range_match[]=$value;
				}
				$FINAL_LINE.=' '.$words[$ID_WORD2];
				$ID_WORD++;
			}
		}
	}
	if ($DEBUG)echo "END\n";
	//$FINAL_LINE=substr(0,$FINAL_LINE-1).'.';
	if ($DEBUG)echo $FINAL_LINE."\n";
	//exit;

	return convertBiorelsTags($FINAL_LINE,$GROUP_ID);
}

$CPDS=array();
$STR='';
$STR_JS='';
$ID=0;
if (isset($MODULE_DATA['METADATA']['DRUG']))
{
	foreach ($MODULE_DATA['METADATA']['DRUG'] as &$G)
	{
		if (!isset($G['SM']))continue;
		foreach ($G['SM'] as &$S)
		{
			if (isset($S['DESC']))
			{
				foreach ($S['DESC'] as $D)
				$G['DESC'][]=array('TEXT_TYPE'=>'Complete','TEXT_DESCRIPTION'=>$D['DESCRIPTION_TEXT'],'SOURCE_NAME'=>$D['DESCRIPTION_TYPE']);
			}
			if (isset($CPDS[$S['SMILES']]))
			{
				$G['SM']=$CPDS[$S['SMILES']];
			}
			else 
			{
				++$ID;
				$G['SM']=$ID;
				$CPDS[$S['SMILES']]=$ID;
			}
			break;

		}
	}
}

if (isset($MODULE_DATA['METADATA']['SM']))
{
	foreach ($MODULE_DATA['METADATA']['SM'] as &$G)
	{
		// echo '<pre>';
		// print_R($G);
		// print_R($CPDS);
		if (isset($CPDS[$G['FULL_SMILES']]))
			{
				echo "EXIST\n";
				$G['FULL_SMILES']=$CPDS[$G['FULL_SMILES']];
			}
			else 
			{
				
				++$ID;
				$CPDS[$G['FULL_SMILES']]=$ID;
				$G['FULL_SMILES']=$ID;
				
			}
			
	}
}


$STR='';
$STR_JS='';
foreach ($CPDS as $SMI=>&$ID)
{
	
	$STR.='<div id="SM_IMG_'.$ID.'" style="display:none;"></div>';
	$STR_JS.='getCompoundImage("'.str_replace("\\\\","\\",$SMI).'","SM_IMG_'.$ID.'",400);'."\n";
}

changeValue("publi_fulltext","SMILES",$STR);
changeValue("publi_fulltext","JS",$STR_JS);


//unset($MODULE_DATA['TEXT']);
changeValue("publi_fulltext","JSON",
str_replace("\\\"","\\\\\"",
str_replace("'","\\'",
//str_replace('\\\\','\\\\\\',
//str_replace('\/','\\/',
str_replace('\t','',
str_replace('\n','',json_encode($MODULE_DATA['METADATA']))))//)
)
//)
);

// //11446902 ATPAse 6 => remove space?
// //11438045 => image


/*
<script>
	RAW_DATA=JSON.parse('${JSON}');


	function convertText(id,is_img=false)
	{
		var MATCH_T=[];
		MATCH_T['GENE']='GN_ENTRY_ID';
		MATCH_T['DISEASE']='DISEASE_ENTRY_ID';
		MATCH_T['GO']='GO_ENTRY_ID';
		MATCH_T['COMPANY']='COMPANY_ENTRY_ID';
		MATCH_T['CLINICAL']='CLINICAL_TRIAL_ID';
		MATCH_T['CELL']='CELL_ENTRY_ID';
		MATCH_T['DRUG']='DRUG_ENTRY_ID';
		MATCH_T['ANATOMY']='ANATOMY_ENTRY_ID';
		MATCH_T['SM']='SM_ENTRY_ID';
		
		
		
		

		TEXT=RAW_DATA['TEXT'][id]['FULL_TEXT'];
		if (TEXT===undefined)return '';
		if(is_img)
		{
			TMP_INI_TEXT=TEXT.split('|');
			TEXT=TMP_INI_TEXT[1];
		}
		console.log(TEXT);
		LINES=TEXT.split(".");
		coverage=[];
		$.each(LINES, function(id_line,str)
		{
			coverage[id_line]=[];
			for (I=0;I<=str.split(" ").length;I++)
			{
				coverage[id_line][I]=[];
			}
			
		});
		list_matches=[];
		id_match=0;
		$.each (RAW_DATA['MATCH'],function(type,list_match)
		{
			$.each (list_match,function(id_match_e,match_info)
			{
				if (match_info['PMID_FULLTEXT_ID']!=id)return true;
				tab=match_info['LOC_INFO'].split("|");
				console.log(type);
				console.log(match_info);
				console.log(RAW_DATA['METADATA'][type][match_info[MATCH_T[type]]]);
				console.log(LINES[tab[0]].split(" "));
				min=parseInt(tab[1]);
				max=parseInt(tab[2])+parseInt(tab[1]);
				console.log("MIN:"+min+" MAX:"+max);
				for (I=min;I<max;I++)
				{
					if (I>=LINES[tab[0]].split(" ").length)break;
					console.log("\tWORD: "+I+" "+LINES[tab[0]].split(" ")[I]);
					coverage[tab[0]][I].push(id_match);
				}
				match_info['TYPE']=type;
				list_matches[id_match]=match_info;
				++id_match;
			});
		});

		console.log("COMPLETE COVERAGE");
		console.log(coverage);
		console.log(list_matches);
		console.log("REBUILD");
		final_str='';
		$.each(LINES, function(id_line,str_line)
		{
			words=str_line.split(" ");
			final_line='';
			n_words=words.length;
			for (id_word=0;id_word<n_words;++id_word)
			{
				//console.log(id_line+" "+id_word+" "+coverage[id_line][id_word]);
				if (coverage[id_line][id_word].length==0)
				{
					final_line+=words[id_word]+" ";
					continue;
				}
				console.log("START AT WORD "+id_word+" "+words[id_word]);
				list_range_match=[];
				$.each (coverage[id_line][id_word],function(dummy,value){
					if (list_range_match.includes(value))return true;
					list_range_match.push(value);
				});
				console.log("COVERAGE "+list_range_match.length);
						console.log(list_range_match);
						$.each(list_range_match, function (value,dummy)
						{
							console.log(list_matches[value]);
						});

				console.log("CONTINUE");
				final_line+='||START||'+words[id_word];
				for (id_word2=id_word+1;id_word2<=n_words;++id_word2)
				{
					console.log("\t\tWORD "+id_word2+" "+words[id_word2]);
					if (coverage[id_line][id_word2].length==0)
					{
						console.log("\t\tFINAL COVERAGE "+list_range_match.length);
						console.log(list_range_match);
						rule_str='';
						types=[];
						$.each(list_range_match, function (dummy,value)
						{
							type=list_matches[value]['TYPE'];
							if (!types.includes(type)) types.push(type);
							rule_str+=type+':'+list_matches[value][MATCH_T[type]]+'/';
							console.log(list_matches[value]);
						});
						color='';
						console.log("TYPES:");
						console.log(types);
						console.log(types.length);
						if (types.length==1)
						{
							color=types[0]];
						}
						else
						{
							color="red";
						}
						final_line=final_line.replace('||START||','<span style="color:'+color+';font-weight:bold;font-size:1.1em" onmouseover="showPubliData(\''+rule_str.slice(0,-1)+'\',\''+id+'\')">');
						
						
						
						final_line+='</span> ';
						
						break;
					}
					else
					{
						console.log("\t\tALT COVERAGE:"+coverage[id_line][id_word2]);
						$.each (coverage[id_line][id_word2],function(dummy,value){
							if (list_range_match.includes(value))return true;
							list_range_match.push(value);
						});
						
						final_line+=' '+words[id_word2];
						id_word++;
					}
				}
				console.log("END");
				//console.log("\tFINAL LINE:"+final_line);	
			}
			console.log("FINAL LINE:"+final_line);
			final_str+=final_line.slice(0,-1)+".";
		});
		if(is_img)
		{
			final_str=TMP_INI_TEXT[0]+"|"+final_str;
		}
		
		//console.log(LINES);
		return final_str;
	}


	function showPubliData(rule_set,id)
	{
		str='';
		rules=rule_set.split("/");
		cpds=[];
		$.each(rules, function(id,info)
		{
			
			tab=info.split(":");
			
			switch(tab[0])
			{
				case 'DRUG':
					ENTRY=RAW_DATA['METADATA']['DRUG'][tab[1]];
					str+='<div class="w3-col s12 l12 m12"><h5>Drug - '+RAW_DATA['METADATA']['DRUG'][tab[1]]['DRUG_PRIMARY_NAME']+'</h5>';
						
					if ("SM" in ENTRY)
					{
						str+='<div class="w3-col s12 m12 l4">'+$("#SM_IMG_"+ENTRY['SM']).html()+'</div>';
						
						
					
					str+='<div class="w3-col s12 m12 l8" id="sm_info_'+tab[1]+'">';
					}	else str+='<div class="w3-col s12 m12 l12" id="sm_info_'+tab[1]+'">';
					if ("DESC" in ENTRY)
					$.each (ENTRY['DESC'], function (id_desc,info_desc)
					{
						if (info_desc['TEXT_DESCRIPTION']!='')
						str+='<p style="max-height:300px;overflow-y:scroll"><span style="font-weight: bold">'+info_desc['SOURCE_NAME']+' - '+info_desc['TEXT_TYPE']+': </span>'+info_desc['TEXT_DESCRIPTION']+'</p>';
					});	
					str+='</div>';
					
					
					break;
				case 'ANATOMY':
					ENTRY=RAW_DATA['METADATA']['ANATOMY'][tab[1]];
					str+='<div class="w3-col s12 l12 m12"><h5>Anatomy/Tissue - '+ENTRY['ANATOMY_NAME']+'</h5><p>'+ENTRY['ANATOMY_DEFINITION']+'</p></div>';
				break;
				case 'CELL':
					ENTRY=RAW_DATA['METADATA']['CELL'][tab[1]];
					str+='<div class="w3-col s12 l12 m12"><h5>Cell - '+ENTRY['CELL_NAME']+'</h5><br/>';
					str+='<table class="table">\
						<tr><th>Accession:</th><td>'+ENTRY['CELL_ACC']+'</td></tr>\
						<tr><th>Cell line type:</th><td>'+ENTRY['CELL_TYPE']+'</td></tr>\
						<tr><th>Gender of Donor :</th><td>'+ENTRY['CELL_DONOR_SEX']+'</td></tr>\
						<tr><th>Age of Donor:</th><td>'+ENTRY['CELL_DONOR_AGE']+'</td></tr>';
						if ("DISEASE" in ENTRY)
						{
							$.each(ENTRY['DISEASE'], function (id_disease,info_disease)
							{
								str+='<tr><th>Disease:</th><td>'+info_disease['DISEASE_NAME']+'</td></tr>';
								str+='<tr><td colspan="2">'+info_disease['DISEASE_DEFINITION']+'</td></tr>';
							});
						}

						str+='</table>';
						
						
						str+='</div>';
				break;
				case 'DISEASE':
					ENTRY=RAW_DATA['METADATA']['DISEASE'][tab[1]];
					str+='<div class="w3-col s12 l12 m12"><h5>Disease - '+ENTRY['DISEASE_NAME']+'</h5>'+ENTRY['DISEASE_DEFINITION'];
						if ("DESC" in ENTRY)
						{
							str+"</br><p>"+ENTRY['DESC']+"</p>";
						}
						str+='</div>';
				break;
				case 'GENE':
					ENTRY=RAW_DATA['METADATA']['GENE'][tab[1]];
					str+='<div class="w3-col s12 l12 m12"><h5>Gene - '+ENTRY['SYMBOL']+'</h5>'+ENTRY['FULL_NAME']+'</div>';
				break;
				case 'SM':
					
						

					ENTRY=RAW_DATA['METADATA']['SM'][tab[1]];
					console.log(ENTRY);
					str+='<div class="w3-col s12 l12 m12"><h5>Compound</h5>';
					
						
					if ("FULL_SMILES" in ENTRY)
					{
						str+='<div class="w3-col s12 m12 l4" >'+$("#SM_IMG_"+ENTRY['FULL_SMILES']).html()+'</div>';
						
						str+='<div class="w3-col s12 m12 l8" id="sm_info_'+tab[1]+'">';
					}	else str+='<div class="w3-col s12 m12 l12" id="sm_info_'+tab[1]+'">';
					str+='<h5>Names:</h5>';
						$.each (ENTRY['NAME'], function (id_n,name)
					{
						str+=name['SM_NAME']+" ";
					});
					str+='<br/>';
					if ("DESC" in ENTRY)
					$.each (ENTRY['DESC'], function (id_desc,info_desc)
					{
						if (info_desc['DESCRIPTION_TEXT']!='')
						str+='<p style="max-height:300px;overflow-y:scroll"><span style="font-weight: bold">'+info_desc['SOURCE_NAME']+' - '+info_desc['DESCRIPTION_TYPE']+': </span>'+info_desc['DESCRIPTION_TEXT']+'</p>';
					});	
						
						str+='</div>';
				break;
				case 'GO':
					ENTRY=RAW_DATA['METADATA']['GO'][tab[1]];
					str+='<div class="w3-col s12 l12 m12"><h5>Gene Ontology - '+ENTRY['AC']+' '+ENTRY['NAME']+'</h5>'+ENTRY['DEFINITION']+'</div>';
				break;
				
			}

		});
		$(".infos").each(function(i,obj){
			if ($(this).attr("id")!="info_"+id)
			{
				$(this).css("display","none");
			}
		});
		$('#info_'+id).html(str);
		$('#info_'+id).css("display","block");
		
		
		
	}


	function generateText()
	{
		str='';
		HAS_ABSTRACT=false;
		HAS_INTRO=false;
		HAS_METHODS=false;
		HAS_RESULTS=false;
		HAS_CONCL=false;
		$.each(RAW_DATA['TEXT'],function(id,v){
			console.log(v['SECTION_TYPE']);



			switch (v['SECTION_TYPE'])
			{
				case 'TITLE':
					switch (v['SECTION_SUBTYPE'])
					{
						case 'title':
							str+='<h1>'+convertText(id).slice(0,-1)+'</h1>';
						break;
						case 'title_2':
							str+='<h2>'+convertText(id).slice(0,-1)+'</h2>';
						break;
						case 'title_3':
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3>';
						break;
						case 'title_4':
							str+='<h4>'+convertText(id).slice(0,-1)+'</h4>';
						break;
						case 'title_5':
							str+='<h5>'+convertText(id).slice(0,-1)+'</h5>';
						break;
						default:
							throw new Error('Unknown TITLE subtype: '+v['SECTION_SUBTYPE']);
					}
					break;


				case 'ABSTRACT':
					switch (v['SECTION_SUBTYPE'])
					{
						case 'title':
							if (!HAS_ABSTRACT)
							{
								str+='<h2>Abstract</h2>';
								HAS_ABSTRACT=true;
							}
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'section':
							str+='<h3>SECTION:'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
						case 'text':
							str+='<p>'+convertText(id)+'</p><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						default:
							throw new Error('Unknown ABSTRACT subtype: '+v['SECTION_SUBTYPE']);
					}
					break;
				case 'SECTION':
					switch(v['SECTION_SUBTYPE'])
					{
						case 'intro':
							if (!HAS_INTRO)
							{
								str+='<h2>Introduction</h2>';
								HAS_INTRO=true;
							}
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'methods':
							if (!HAS_METHODS)
							{
								str+='<h2>Methods</h2>';
								HAS_METHODS=true;
							}
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'results':
							if (!HAS_RESULTS)
							{
								str+='<h2>Results</h2>';
								HAS_RESULTS=true;
							}
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'discussion':
							if (!HAS_CONCL)
							{
								str+='<h2>Conclusions:</h2>';
								HAS_CONCL=true;
							}
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						default:
							throw new Error('Unknown SECTION subtype: '+v['SECTION_SUBTYPE']);
					}
					break;

				
				case 'FIGURE':
					switch (v['SECTION_SUBTYPE'])
					{
						case 'fig_label':
							tmp=convertText(id).split('|');
							console.log("FIG CAPTION");
							console.log(tmp);
							str+='<div style="width:100%;text-align:center"><img src="/PUBLICATION_IMAGE/${PMID}/PARAMS/'+tmp[0]+'"/>\
								<br/><span style="font-weight:bold">Fig '+tmp[0].slice(1)+": </span> "+ tmp[1]+'</p></div>\
								<p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'fig_text':
							str+='<p>'+convertText(id).slice(0,-1)+'</p><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'fig_title':
							
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';;
							break;
						default:
							throw new Error('Unknown FIG subtype: '+v['SECTION_SUBTYPE']);
					}
					break;
				case 'TABLE':
					switch (v['SECTION_SUBTYPE'])
					{
						case 'table_caption':
							str+='<h5>'+convertText(id).slice(0,-1)+'</h5><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'table_text':
							str+=v['FULL_TEXT'];
							break;
						case 'table_label':
						case 'table_foot':
							str+='<p>'+convertText(id).slice(0,-1)+'</p>';
							break;
						default:
							throw new Error('Unknown TABLE subtype: '+v['SECTION_SUBTYPE']);
						
					}
					case 'NOTES':
					switch (v['SECTION_SUBTYPE'])
					{
						case 'section':
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'title':
							if (!HAS_ABSTRACT)
							{
								str+='<h2>Abstract</h2>';
								HAS_ABSTRACT=true;
							}
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'text':
							str+='<p>'+convertText(id)+'</p><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						default:
							throw new Error('Unknown NOTES subtype: '+v['SECTION_SUBTYPE']);
					}
					break;
					case 'COPYRIGHT':
					switch (v['SECTION_SUBTYPE'])
					{
						case 'section':
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'title':
							if (!HAS_ABSTRACT)
							{
								str+='<h2>Abstract</h2>';
								HAS_ABSTRACT=true;
							}
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'text':
							str+='<p>'+convertText(id)+'</p><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						default:
							throw new Error('Unknown COPYRIGHT subtype: '+v['SECTION_SUBTYPE']);
					}
					break;
					case 'LICENSE':
					switch (v['SECTION_SUBTYPE'])
					{
						case 'section':
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'title':
							
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'text':
							str+='<p>'+convertText(id)+'</p><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						default:
							throw new Error('Unknown LICENSE subtype: '+v['SECTION_SUBTYPE']);
					}
					break;
				case 'SUPPL':
					switch (v['SECTION_SUBTYPE'])
					{
						case 'section':
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'title':
							
							str+='<h3>'+convertText(id).slice(0,-1)+'</h3><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
							case 'title_3':
							
							str+='<h5>'+convertText(id).slice(0,-1)+'</h5><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'text':
							str+='<p>'+convertText(id)+'</p><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						case 'suppl_caption':
							str+='<p>'+convertText(id)+'</p><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
							break;
						default:
							throw new Error('Unknown SUPPL subtype: '+v['SECTION_SUBTYPE']);
					}
					break;
				case 'TEXT':
					switch (v['SECTION_SUBTYPE'])
					{
						
						case 'text':
							str+='<p>'+convertText(id)+'</p><p style="display:none" class="infos w3-container" id="info_'+id+'"></p>';
						break;
						default:
							throw new Error('Unknown TEXT subtype: '+v['SECTION_SUBTYPE']);
					}
					break;
				
           
			}
		
		});
		console.log(str);
		$('#fulltext').html(str);
	}

	

</script>

<script>
	$(document).ready(function(){
		generateText();
		${JS}
	});
</script> */
?>