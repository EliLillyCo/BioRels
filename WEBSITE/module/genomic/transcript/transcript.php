<?php

 if (!defined("BIORELS")) header("Location:/");




changeValue("transcript","GENE_PORTAL","/GENEID/".$USER_INPUT['PORTAL']['DATA']['GENE_ID']);


if (isset($MODULE_DATA['ERROR']))
{
	removeBlock("transcript",'VALID_TRANSCRIPT');
	changeValue("transcript","ERR_MSG",$MODULE_DATA['ERROR']);
	return;
}else removeBlock("transcript",'INVALID_TRANSCRIPT');



	if ($USER['Access'][1]!=1)removeBlock("transcript","HAS_RED_ACCESS");


$TRANSCRIPT_NAME=$MODULE_DATA['INFO']['TRANSCRIPT_NAME'].(($MODULE_DATA['INFO']['TRANSCRIPT_VERSION']!=null)?'.'.$MODULE_DATA['INFO']['TRANSCRIPT_VERSION']:'');
changeValue("transcript","TRANSCRIPT_NAME",$TRANSCRIPT_NAME);
$ENS=false;
if (substr($TRANSCRIPT_NAME,0,3)=='ENS')
{
	$ENS=true;
	changeValue("transcript","TRANSCRIPT_LINK",str_replace('${LINK}',$TRANSCRIPT_NAME,$GLB_CONFIG['LINK']['ENSEMBL']['TRANSCRIPT']));
}
else 	changeValue("transcript","TRANSCRIPT_LINK",str_replace('${LINK}',$TRANSCRIPT_NAME,$GLB_CONFIG['LINK']['REFSEQ']['TRANSCRIPT']));


$MAP=array();
foreach ($MODULE_DATA['SEQUENCE']['SEQUENCE'] as $POS=>&$INFO)$MAP[$INFO['TRANSCRIPT_POS_ID']]=$POS;
if (isset($MODULE_DATA['PROT'])&& count($MODULE_DATA['PROT'])>0)
		{
			$STR_L='';
			$STR='';
			foreach ($MODULE_DATA['PROT']['STAT'] as $PR)
			{
				$STR_L.="'".$PR['TR_PROTSEQ_AL_ID']."',";
				$STR.='<input type="checkbox" id="prot_'.$PR['TR_PROTSEQ_AL_ID'].'" checked="checked"/><label for="trans_seq_pos">Show Translation of '.$PR['ISO_NAME'].'</label><br/>';
			}
			changeValue("transcript","LIST_PROT",substr($STR,0,-1));
			changeValue("transcript","LIST_ALIGN",$STR_L);
if (isset($MODULE_DATA['PROT']['ALIGN']))
foreach ($MODULE_DATA['PROT']['ALIGN'] as $ALIGN_ID =>&$ALIGNMENT)
{
	$CORRECTION=array();
foreach ($ALIGNMENT as $TR_POS=>&$TR_INFO)$CORRECTION[$MAP[$TR_POS]]=$TR_INFO;
ksort($CORRECTION);
$MODULE_DATA['PROT']['ALIGN'][$ALIGN_ID]=$CORRECTION;
		}

		}else changeValue("transcript","LIST_ALIGN",'');

//changeValue("transcript","TRANSCRIPT_SEQUENCE",str_replace("'","\\'",json_encode($MODULE_DATA)));
changeValue("transcript","TRANSCRIPT_NAME",$TRANSCRIPT_NAME);
changeValue("transcript","SEQ_LEN",max(array_keys($MODULE_DATA['SEQUENCE']['SEQUENCE'])));
changeValue("transcript","BIOTYPE",$MODULE_DATA['INFO']['BIOTYPE_NAME']);
changeValue("transcript","FEATURE",$MODULE_DATA['INFO']['FEATURE_NAME']);
changeValue("transcript","RANGE",'['.$MODULE_DATA['INFO']['START_POS'].' - '.$MODULE_DATA['INFO']['END_POS'].']');
$str='';
$sp=(int)$MODULE_DATA['INFO']['SUPPORT_LEVEL'];

for ($i=5;$i>=1;$i--)
		{	
			if ($sp==0){$str.='<div class="blk_bc confidence_block"></div>';continue;}
			if ($i < $MODULE_DATA['INFO']['SUPPORT_LEVEL']){$str.='<div class="grey_bc confidence_block"></div>';continue;}
			if ($sp==5){$str.='<div class="dgrey_bc confidence_block"></div>';continue;}
			if ($sp==4){$str.='<div class="dred_bc confidence_block"></div>';continue;}
			if ($sp==3){$str.='<div class="orange_bc confidence_block"></div>';continue;}
			if ($sp==2){$str.='<div class="dgreen_bc confidence_block"></div>';continue;}
			if ($sp==1){$str.='<div class="green_bc confidence_block"></div>';continue;}
		}
		
		changeValue("transcript","SUPPORT_LEVEL",$str);



		$GENE_NAME=$MODULE_DATA['INFO']['GENE_SEQ_NAME'].(($MODULE_DATA['INFO']['GENE_SEQ_VERSION']!=null)?'.'.$MODULE_DATA['INFO']['GENE_SEQ_VERSION']:'');
		if (substr($GENE_NAME,0,3)=='ENS')
		{
			changeValue("transcript","GENESEQ_LINK",str_replace('${LINK}',$GENE_NAME,$GLB_CONFIG['LINK']['ENSEMBL']['GENE']));
			
		}
		else 	changeValue("transcript","GENESEQ_LINK",str_replace('${LINK}',$MODULE_DATA['INFO']['GENE_ID'],$GLB_CONFIG['LINK']['REFSEQ']['GENE']));
		
		changeValue("transcript","GENESEQ_NAME",$GENE_NAME);
		changeValue("transcript","GENESEQ_RANGE",'['.$MODULE_DATA['INFO']['GENE_START'].' - '.$MODULE_DATA['INFO']['GENE_END'].']');
		changeValue("transcript","STRAND",(($MODULE_DATA['INFO']['STRAND']=="+")?"Positive":"Negative"));
		changeValue("transcript","GENE_ID",$MODULE_DATA['INFO']['GENE_ID']);
		changeValue("transcript","SYMBOL",$MODULE_DATA['INFO']['SYMBOL']);
		
?>