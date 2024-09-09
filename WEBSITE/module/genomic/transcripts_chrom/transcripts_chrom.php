<?php

if (!defined("BIORELS")) header("Location:/");


if (isset($MODULE_DATA['ERROR']))
{
	removeBlock("transcripts_chrom","VALID");
	removeBlock("transcripts_chrom","INVALID");
	changeValue("transcripts_chrom","ERR_MSG",$MODULE_DATA['ERROR']);
	return;
}
else if (count($MODULE_DATA['TRANSCRIPTS'])==0)
{
	removeBlock("transcripts_chrom","VALID");
	removeBlock("transcripts_chrom","ERROR");
	$str='<a href="'.$GLB_CONFIG['LINK']['REFSEQ']['GENE'].'/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'" target="_blank">NCBI Entry Gene for '.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'</a>';
	changeValue("transcripts_chrom","INVALID_LINK",$str);
	return;
}
 removeBlock("transcripts_chrom","INVALID");
 removeBlock("transcripts_chrom","ERROR");



changeValue("transcripts_chrom","GENE_ID",$USER_INPUT['PORTAL']['DATA']['GENE_ID']);

$GROUPS=array();
foreach ($MODULE_DATA['GENE_SEQ'] as &$GS)
{
	$A_TTL=$GS['ASSEMBLY_NAME'].'|'.$GS['ASSEMBLY_UNIT'];
	$TRs=array();
	foreach ($MODULE_DATA['TRANSCRIPTS'] as $K=>&$TR)
	{
		if ($TR['GENE_SEQ_ID']==$GS['GENE_SEQ_ID'])$TRs[]=$K;
	}
	$GROUPS[$A_TTL][]=array($GS['GENE_SEQ_ID'],$TRs);
}

foreach ($GROUPS as $A_TTL=>&$LIST_ATTL)
{
	//print_r($LIST_ATTL);
	$ALL_TR=array();$SEL=array();
	foreach ($LIST_ATTL as $GS_ATTL){
	foreach ($GS_ATTL[1] as $P)
	{
		//echo "|".$P."|\n";
		$SEL[$P]=false;
		$ALL_TR[]=$P;
	}
}
	$ORDERS=array();$N=0;
	foreach ($ALL_TR as $K=>$P)
	{
		if ($SEL[$P])continue;
		++$N;
		$ORDERS[$N]=array($P);
		$SEL[$P]=true;
		if ($MODULE_DATA['TRANSCRIPTS'][$P]['SEQ_HASH']=='')continue;
		for ($I=$K+1;$I<count($ALL_TR);++$I)
		{
			if ($MODULE_DATA['TRANSCRIPTS'][$P]['SEQ_HASH']!=$MODULE_DATA['TRANSCRIPTS'][$ALL_TR[$I]]['SEQ_HASH'])continue;
			$ORDERS[$N][]=$ALL_TR[$I];
			$SEL[$ALL_TR[$I]]['SEL']=true;
		}
	}
	$LIST_ATTL['ORDER']=$ORDERS;
}


$STR='';
foreach ($TISSUES as $TISSUE)
{
$STR.='<option value="'.$TISSUE['ORGAN_NAME'].'_'.$TISSUE['TISSUE_NAME'].'">';
if ($TISSUE['ORGAN_NAME']!=$TISSUE['TISSUE_NAME'])$STR.=$TISSUE['ORGAN_NAME'].': '.$TISSUE['TISSUE_NAME'];
else $STR.=$TISSUE['ORGAN_NAME'];
$STR.='</option>';
}

echo '<pre>';
print_r($GROUPS);


changeValue("transcripts_chrom","TISSUES_OPTS",$STR);

$TMP_R=array();
foreach ($MODULE_DATA['TRANSCRIPTS'] as &$TR_INFO)if (isset($TR_INFO['RNA_EXPR']))$TMP_R[$TR_INFO['TRANSCRIPT_NAME']]=$TR_INFO['RNA_EXPR'];

changeValue("transcripts_chrom","RNA_EXPR",str_replace("'","\\'",json_encode($TMP_R)));


$DATA_TTL=array();
$DATA_CO=0;

$MAP_TTL_GS=array();

$STR='';
$STR_LIST='';
//Source	Transcript	Length	Sequence Type	Protocol	Support level	Options

$LIST_TRANSCRIPTS=array();
$NUMBERING=0;
foreach ($GROUPS as $ASSEMBLY_NAME=>&$DATA_ASSEMBLY)
{
	
	$tab=explode("|",$ASSEMBLY_NAME);
	$STR.='<tr><th colspan="11" style="background-color:lightgrey">'.$tab[0].' - '.$tab[1].'</th></tr>';
	foreach ($DATA_ASSEMBLY['ORDER'] as $ORDER_ID=>$LIST_TR){$NUMBERING++;
	foreach ($LIST_TR as $K=>$P)
	{
		$TR=&$MODULE_DATA['TRANSCRIPTS'][$P];
		$NAME=$TR['TRANSCRIPT_NAME'];
		if ($TR['TRANSCRIPT_VERSION']!=null)$NAME.='.'.$TR['TRANSCRIPT_VERSION'];
		
		$LIST_TRANSCRIPTS[]=$NAME;
		$STR.='<tr><td><input type="checkbox" class="ads_Checkbox" name="transcripts_sel[]" value="'.$NAME.'"/></td>';
		// if ($K==0 && count($LIST_TR)>1)$STR.='<td rowspan="'.count($LIST_TR).'">'.$NUMBERING.'</td>';
		// else if (count($LIST_TR)==1)
		$STR.='<td>'.$NUMBERING.'</td>';

		$ENS=false;
		if (substr($TR['TRANSCRIPT_NAME'],0,3)=='ENS')$ENS=true;
		

		$GENE_SEQ=&$MODULE_DATA['GENE_SEQ'][$TR['GENE_SEQ_ID']];

		$POS_TTL=0;
		if (!isset($MAP_TTL_GS[$TR['GENE_SEQ_ID']]))
		{
			++$DATA_CO;$POS_TTL=$DATA_CO;
			$TTL='<table><tr><th class=\'boldright\'>Source:</th><td>'.(($ENS)?'Ensembl':'RefSeq').'</td></tr>'.
			'<th class=\'boldright\'>Genbank:</th><td>'.$GENE_SEQ['GENBANK_NAME'].'.'.$GENE_SEQ['GENBANK_VERSION'].'</td></tr>'.
			'<th class=\'boldright\'>Strand:</th><td>'.$GENE_SEQ['STRAND'].'</td></tr>'.
			'<th class=\'boldright\'>Range:</th><td>['.$GENE_SEQ['START_POS'].'-'.$GENE_SEQ['END_POS'].']</td></tr>'.
			'</table>';
			$DATA_TTL[$DATA_CO]=$TTL;
			$MAP_TTL_GS[$TR['GENE_SEQ_ID']]=$DATA_CO;
		}else $POS_TTL=$MAP_TTL_GS[$TR['GENE_SEQ_ID']];

		$STR.='<td class="ttl" title="Assembly" data-pos="'.$POS_TTL.'">'.$GENE_SEQ['GENE_SEQ_NAME'];
		if ($GENE_SEQ['GENE_SEQ_VERSION']!=null)$STR.='.'.$GENE_SEQ['GENE_SEQ_VERSION'];
		


	
		 
		$STR.='</td><td>';
		
		$STR.=$NAME;
		
		if ($TR['VALID_ALIGMNENT']=='F')
		{
			$POS_TTL=0;
			$MSG='This sequence couldn\'t be aligned properly against the reference genome';
			if (!isset($MAP_TTL_GS[$MSG]))
			{
				++$DATA_CO;$POS_TTL=$DATA_CO;
				$DATA_TTL[$DATA_CO]=$MSG;
				$MAP_TTL_GS[$MSG]=$DATA_CO;
			}else $POS_TTL=$MAP_TTL_GS[$MSG];
		 $STR.='<span  class="ttl" title="Alignment issue" data-pos="'.$POS_TTL.'" style="color:red">&#9888;</span>';
		}
		
		$STR.='</td><td>'.$TR['LENGTH'].'</td><td>'.(($TR['BIOTYPE_NAME']=='NULL')?'N/A':str_replace('_',' ',$TR['BIOTYPE_NAME'])).'</td><td>'.str_replace('_',' ',$TR['FEATURE_NAME']).'</td>
		<td ><div >';

		$sp=$TR['SUPPORT_LEVEL'];
		
		for ($i=5;$i>=1;$i--)
		{	
			if ($sp==0){$STR.='<div style="display:inline-block" id="'.$NAME.$i.'" class=" grey_bc confidence_block"></div>';   continue;}
			if ($i < $sp){$STR.='<div style="display:inline-block"  id="'.$NAME.$i.'"  class=" grey_bc confidence_block"></div>';continue;}
			if ($sp==5){$STR.='<div  style="display:inline-block" id="'.$NAME.$i.'"  class=" dgrey_bc confidence_block"></div>';  continue;}
			if ($sp==4){$STR.='<div style="display:inline-block" id="'.$NAME.$i.'"  class=" dred_bc confidence_block"></div>';continue;}
			if ($sp==3){$STR.='<div style="display:inline-block" id="'.$NAME.$i.'"  class=" orange_bc confidence_block"></div>';continue;}
			if ($sp==2){$STR.='<div style="display:inline-block" id="'.$NAME.$i.'"  class=" dgreen_bc confidence_block"></div>';continue;}
			if ($sp==1){$STR.='<div style="display:inline-block" id="'.$NAME.$i.'"  class=" green_bc confidence_block"></div>';continue;}
		}
		$STR.='</div></td><td id="rna_'.$TR['TRANSCRIPT_NAME'].'">'.(($ENS)?"":"N/A").'</td><td>';
		if(isset($MODULE_DATA['PROT']))
		{
			$STR_T='';
			
		foreach ($MODULE_DATA['PROT'] as &$PROT)
		{
			if ($PROT['TRANSCRIPT_ID']!=$TR['TRANSCRIPT_ID'])continue;
			$STR_T.=$PROT['ISO_ID'].'<div class="w3-dropdown-hover">
			&#128279;
			<div class="w3-dropdown-content w3-bar-block w3-border">
			<a href="/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/SEQUENCE/'.$PROT['ISO_NAME'].'"  class="w3-bar-item w3-button" >Show protein sequence</a>
			
			</div></div>
		  </div><br/>';
			

		}
		if ($STR_T=='')$STR.='N/A';
		else $STR.=substr($STR_T,0,-5);
		}else $STR.='N/A';
		$STR.='</td><td>
		<div class="w3-dropdown-hover">
			<img class="transcript_seq_tool_but" src="/require/img/tools.png" />
			<div class="w3-dropdown-content w3-bar-block w3-border" style="left:-175px">
		<a href="/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/TRANSCRIPT/'.$NAME.'"     class="w3-bar-item w3-button">Show transcript sequence</a>';


if (in_array($TR['TRANSCRIPT_ID'],$TR_GTEX))$STR.='		<a href="/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/RNA_TR_EXPR_BP/'.$NAME.'"   class="w3-bar-item w3-button">Show RNA Expression</a>';
		$STR.='<a href="/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/PREMRNA/PARAMS/TRANSCRIPT/'.$NAME.'"    class="w3-bar-item w3-button">Show pre-mRNA sequence</a>
		<a href="/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/FASTA/TRANSCRIPT/'.$NAME.'"    class="w3-bar-item w3-button" >Get Fasta sequence</a>
		<a href="/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/BLASTN/PARAMS/TRANSCRIPT/'.$TR['TRANSCRIPT_NAME'].'"    class="w3-bar-item w3-button" >Blast this sequence</a></div></div>';
		
	}}
}
	
changeValue("transcripts_chrom","TOOLTIPS",str_replace("'","\\'",json_encode(str_replace("\n","",$DATA_TTL))));

unset($MODULE_DATA);
changeValue("transcripts_chrom","LIST_TRANSCRIPTS",$STR);
changeValue("transcripts_chrom","LIST_TRANSCRIPTS_VIEW",implode(";",$LIST_TRANSCRIPTS));
changeValue("transcripts_chrom","LIST_IDS",$STR_LIST);
		
/*
	$str.=$NAME.'<div class="w3-dropdown-hover">
		<span>&#128279;</span>
		<div class="w3-dropdown-content w3-bar-block w3-border"> */

	// $USER_INPUT['PARAMS']=array();
	// $USER_INPUT['PAGE']['VALUE']='transcript_support';
	// 	$STR=loadHTMLAndRemove('HELP');
		
	// 	changeValue("transcripts_chrom","HELP",$STR);

/*
TRANSCRIPT_ID: "704848",
TRANSCRIPT_NAME: "NM_001798",
TRANSCRIPT_VERSION: "5",
START_POS: "55966830",
END_POS: "55972789",
BIOTYPE_NAME: "NULL",
BIOTYPE_SO_ID: null,
BIOTYPE_SO_NAME: null,
BIOTYPE_SO_DESC: null,
FEATURE_NAME: "mRNA",
FEATURE_SO_ID: "SO:0000234",
FEATURE_SO_NAME: "mRNA",
FEATURE_SO_DESC: "Messenger RNA is the intermediate molecule between DNA and protein. It includes UTR and coding sequences. It does not contain introns. ",
SUPPORT_LEVEL: "5",
GENE_SEQ_NAME: "CDK2",
GENE_SEQ_VERSION: null,
STRAND: "+",
GENE_START: "55966830",
GENE_END: "55972789",
GENE_ID: "1017",
SYMBOL: "CDK2",
FULL_NAME: "cyclin dependent kinase 2"*/
?>