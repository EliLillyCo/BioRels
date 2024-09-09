<?php

if (!defined("BIORELS")) header("Location:/");/// BIORELS defined in index.php. Not existing? Go to index.php



 changeValue("prot_seq","TITLE",$MODULE_DATA['INFO'][0]['DESCRIPTION']);
 changeValue("prot_seq","SEQ_NAME",$MODULE_DATA['INFO'][0]['ISO_NAME']);
 changeValue("prot_seq","GENE_ID",$MODULE_DATA['INFO'][0]['GENE_ID']);
 changeValue("prot_seq","SYMBOL",$MODULE_DATA['INFO'][0]['SYMBOL']);
 changeValue("prot_seq","FullName",$MODULE_DATA['INFO'][0]['FULL_NAME']);
 changeValue("prot_seq","SEQ_ID",$MODULE_DATA['INFO'][0]['ISO_ID']);
 $LEN_SEQ=count($MODULE_DATA['SEQ']);
 changeValue("prot_seq","LEN",$LEN_SEQ);
 changeValue("prot_seq","PROTEIN_SEQUENCE",json_encode(array_values($MODULE_DATA['SEQ'])));
 changeValue("prot_seq","FEATS",json_encode($MODULE_DATA['FT']));


$RULE_SET=array(
	'Active site'=>'This is used for enzymes and indicates the residues directly involved in catalysis.<br/><a href="https://www.uniprot.org/help/act_site" rel="noopener" target="_blank">More...</a>'
	);


 $MW_M=array(
	'A'=>89.09,
	'R'=>174.2,
	'N'=>132.1,
	'D'=>133.1,
	'C'=>121.2,
	'E'=>147.1,
	'Q'=>146.2,
	'G'=>75.07,
	'H'=>155.2,
	'I'=>131.2,
	'L'=>131.2,
	'K'=>146.2,
	'M'=>149.2,
	'F'=>165.2,
	'P'=>115.1,
	'S'=>105.09,
	'T'=>119.1,
	'W'=>204.2,
	'Y'=>181.2,
	'V'=>117.1,
 );
 $MW=0;

 foreach ($MODULE_DATA['SEQ'] as $T) $MW+=$MW_M[$T['AA']];
 changeValue("prot_seq","WEIGHT",round($MW,2));



 $str='<a rel="noopener" href="${UNIPROT_LINK}" target="_blank">'.$MODULE_DATA['INFO'][0]['PROT_IDENTIFIER'].'</a>';
 if ($MODULE_DATA['INFO'][0]['CONFIDENCE']!='')
 {
	 $str.='<div style="    position: relative;
	 cursor: pointer;height:5px;
	 top: -10px;"  title="Please wait..."  class="helptt"><span style="color:gold" data-link="/CONTENT/HELP/uniprot_evidence" id="'.$MODULE_DATA['INFO'][0]['ISO_ID'].'">';
	 for ($I=5;$I>=1;--$I)
	 {
		 $str.='&#8902;';
		 if ($I==$MODULE_DATA['INFO'][0]['CONFIDENCE'])$str.='</span>';
	 }
	 $str.='</div>';
 }
 
 changeValue("prot_seq","UNIPROT_NAME",$str);
 changeValue("prot_seq","UNIPROT_LINK",str_replace('${LINK}',$MODULE_DATA['INFO'][0]['PROT_IDENTIFIER'],$GLB_CONFIG['LINK']['UNIPROT']['UNIID']));
if ($MODULE_DATA['INFO'][0]['NOTE']!='')
{
	$str='<p><span class="bold">Compared to the primary sequence:</span> '.$MODULE_DATA['INFO'][0]['NOTE'].'</p>';
	changeValue("prot_seq","NOTE",$str);
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////// FEATURES ////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$NRID=0;
$STR='';$LIST_FT=array();
$GROUPS=array();
foreach ($MODULE_DATA['FT']['FEATS'] as $K=>&$FT )
{
	$NAME=$MODULE_DATA['FT']['FEAT_TYPE'][$FT['TYPE']]['NAME'];
	//if ($NAME=='Helix'||$NAME=='Turn'||$NAME=='Beta strand')continue;
	$GROUPS[$NAME][$FT['START']][]=$K;
}

$JSON_INFO=array();$N_JSON=0;

foreach ($GROUPS as $NAME =>&$LIST)
{
	$STR.='<h3>'.$NAME.'</h3><div>';
	ksort($LIST);
	switch ($NAME)
	{
		case 'Turn':
		case 'Beta strand':
		case 'Helix':
		
			$STR.='<table class="table table-sm" style="width:100%">
		<thead><tr><th style="max-width:25px;width:25px"><input type="checkbox" class="seq_tag_check_all" data-pos_start="'.($N_JSON+1).'" data-pos_end="|END_P|"/></th><th style="max-width:200px">View</th><th>Range</th></tr></thead>
		<tbody>';

		break;
		default:		
		$STR.='<table class="table table-sm"  style="width:100%">
		<thead><tr><th style="max-width:25px;width:25px"><input type="checkbox" class="seq_tag_check_all" data-pos_start="'.($N_JSON+1).'" data-pos_end="|END_P|"/></th><th style="max-width:10%">Feature type</th><th>Name</th><th style="max-width:200px">Range</th></tr></thead>
		<tbody>';
	}

	
	foreach ($LIST as &$SUB_LIST)
	foreach ($SUB_LIST as $K)
	{
		$FT=&$MODULE_DATA['FT']['FEATS'][$K];
		++$N_JSON;
			$JSON_INFO[$N_JSON]=array($NAME,$FT['START'],$FT['END'],false);
		if ($NAME=='Turn'||$NAME=='Beta strand'|| $NAME=='Helix')
		{
			
				$STR.='<tr class="seq_tag" id="seq_tag_'.$N_JSON.'"><td><input type="checkbox" class="seq_tag_check" data-pos="'.$N_JSON.'"/> </td><td >
						<div style="width:100%;border:1px solid black;height:20px;">
							<div style="height:20px;position:relative;background-color:orange;top:-1px; left:'.($FT['START']/$LEN_SEQ*100).'%;width:'.max(($FT['END']-$FT['START']+1)/$LEN_SEQ*100,2).'%" ></div>
							</div></td>
							<td>'.$FT['START'].' - '.$FT['END'].'</td></tr>';
							
			}
			else
			{
	$VALUE=$FT['VALUE'];
	//echo $FT['VALUE']."<br/>\n";
	$LIST_FT[$NAME]=true;
	if (isset($FT['PMID']))
	{
		$TMP_STR='';
		foreach ($FT['PMID'] as $PMID=>$ECO_ID)
		{
			$TMP_STR.=$MODULE_DATA['FT']['ECO'][$ECO_ID]['ECO_ID'].':PubMed:'.$PMID.'|';
		}

	//echo $TMP_STR."\n";

	 $USER_INPUT['PAGE']['VALUE']=$TMP_STR;
		++$NRID;
		$USER_INPUT['PARAMS']=array('ID',$NRID);
		//echo strlen(loadHTMLAndRemove('PUBLI_ECO'));
		$VALUE.='<div>'.loadHTMLAndRemove('PUBLI_ECO').'</div>';
	}
	 if (preg_match("/dbSNP:rs([0-9]{1,12})/",$FT['VALUE'],$matches))
	{
		//print_r($matches);
		$USER_INPUT['PAGE']['VALUE']=$matches[1];
		

		//echo strlen(loadHTMLAndRemove('PUBLI_ECO'));
		$VALUE.='<span style="cursor:pointer;font-style:italic" onclick=" showDialog(\'/CONTENT/MUTATION/'.$matches[1].'\')">More information</span>';
	
	}

	$STR.='<tr  class="seq_tag" id="seq_tag_'.$N_JSON.'"><td><input type="checkbox" class="seq_tag_check" data-pos="'.$N_JSON.'"/> </td>
	<td>'.$VALUE.'</td><td><div style="width:100px;border:1px solid black;height:20px;"><div style="height:20px;position:relative;background-color:orange;top:-1px; left:'.($FT['START']/$LEN_SEQ*100).'%;width:'.max(($FT['END']-$FT['START']+1)/$LEN_SEQ*100,2).'%" ></div></div></td>';
	
		$STR.='<td>'.$FT['START'].(($FT['START']==$FT['END'])?'':' - '.$FT['END']).'</td>';
	
	$STR.='</tr>';
			}

	}
	$STR.='</tbody>
	</table></div>';
	$STR=str_replace("|END_P|",$N_JSON,$STR);

}



changeValue("prot_seq","CO_SIMSEQ",$MODULE_DATA['SEQ_SIM']);
changeValue("prot_seq","NPAGE_SIMSEQ",floor($MODULE_DATA['SEQ_SIM']/10));
changeValue("prot_seq","FEATURES",$STR);
changeValue("prot_seq","RULES",str_replace("'","\\'",json_encode($JSON_INFO)));


$UNI_ID=$MODULE_DATA['INFO'][0]['PROT_IDENTIFIER'];
 $STR='';
 if (isset($MODULE_DATA['EXTDB']))
foreach ($MODULE_DATA['EXTDB'] as $CAT=>$LIST_DB)
{
	for ($I=0;$I<count($LIST_DB);++$I)
	{
		$STR.='<tr> ';
		if ($I==0) $STR.='<td rowspan="'.count($LIST_DB).'">'.$CAT.'</td>';
		//else $STR.='></td>';
		$STR.='<td>'.$LIST_DB[$I]['PROT_EXTDBABBR'].'</td>';

		$tab=explode(";",$LIST_DB[$I]['PROT_EXTDB_VALUE']);
		if (strpos($LIST_DB[$I]['PROT_EXTDBURL'],'%s')===false)
		{
			$STR.='<td><a target="_blank"  href="'.str_replace("%u",$UNI_ID,str_replace("%s",$tab[0],$LIST_DB[$I]['PROT_EXTDBURL'])).'">'.$tab[0].'</a>';
			unset($tab[0]);
			$STR.=implode(";",$tab).': ';
			$STR.= $UNI_ID.' ';
			$STR.='</td>';
		}
		
		else
		{
			
			$STR.='<td><a target="_blank" href="'.str_replace("%u",$UNI_ID,str_replace("%s",$tab[0],$LIST_DB[$I]['PROT_EXTDBURL'])).'">'.$tab[0].'</a>';
			$T2=$tab;unset($T2[0]);
			$STR.=' ; '.implode(";",$T2);
			$STR.='</td>';
			
		}
		$STR.='</tr>';

	} //,PROT_EXTDBABBR, PROT_EXTDBURL,Category
}
changeValue("prot_seq","LIST_LINK",$STR);

?>
