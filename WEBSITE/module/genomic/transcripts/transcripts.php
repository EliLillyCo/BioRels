<?php

if (!defined("BIORELS")) header("Location:/");

if (isset($MODULE_DATA['ERROR']))
{
	removeBlock("transcripts","VALID");
	removeBlock("transcripts","INVALID");
	changeValue("transcripts","ERR_MSG",$MODULE_DATA['ERROR']);
	return;
}
else if (count($MODULE_DATA['GENE_SEQ_LOC'])==0)
{
	removeBlock("transcripts","VALID");
	removeBlock("transcripts","INVALID_SEARCH");
	$str='<a href="'.$GLB_CONFIG['LINK']['REFSEQ']['GENE'].'/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'" target="_blank">NCBI Entry Gene for '.$USER_INPUT['PORTAL']['DATA']['SYMBOL'].'</a>';
	changeValue("transcripts","INVALID_LINK",$str);
	return;
}
 removeBlock("transcripts","INVALID");
 removeBlock("transcripts","INVALID_SEARCH");


changeValue("transcripts","GENE_ID",$USER_INPUT['PORTAL']['DATA']['GENE_ID']);
changeValue("transcripts","SYMBOL",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
changeValue("transcripts","FULL_NAME",$USER_INPUT['PORTAL']['DATA']['FULL_NAME']);
changeValue("transcripts","ORGANISM",$USER_INPUT['PORTAL']['DATA']['SCIENTIFIC_NAME']);
 

$DEFS=array(
	'Primary Assembly'=>'Represents the collection of assembled chromosomes, unlocalized and unplaced sequences that, when combined, should represent a non-redundant haploid genome. This excludes any of the alternate locus groups.',
	'alt-scaffold'=>'an alternate loci scaffold or a patch scaffold',
	'unlocalized-scaffold'=>' a scaffold that is associated with a particular chromosome but has not been localized to a specific position on the chromosome',
	'novel-patch'=>'represent the addition of new alternate loci to the assembly',
	'assembled-molecule'=>'Complete molecule',
	'non-nuclear'=>'a non-nuclear assembly-unit containing sequences from organelle(s)',
	'fix-patch'=>'Fix patches represent changes to existing assembly sequences. These are generally error corrections or assembly improvements',
	'unplaced-scaffold'=>' a scaffold that does not have a chromosome assignment',
);


$DATA_TTL=array(1=>'Assembly Name',2=>'Chromosome',3=>'Assembly Unit',4=>'Sequence Role',5=>'Number of transcripts');
$DATA_CO=5;



$STR='';
$TMP=array();
foreach ($MODULE_DATA['GENE_SEQ_LOC'] as $GENE_SEQ) $TMP[$GENE_SEQ['CHR_SEQ_ID']][]=$GENE_SEQ;	
	$IS_FIRST=true;
foreach ($TMP as $LIST_GS)
{

	$NAME=$LIST_GS[0]['CHR_NUM'].':'.str_replace('/','-_-',str_replace(" ","__",$LIST_GS[0]['ASSEMBLY_UNIT']));
	
		
	
	++$DATA_CO;
	$TTL='<table><tr><th class=\'boldright\'>Assembly accession:</th><td>'.$LIST_GS[0]['ASSEMBLY_ACCESSION'].'</td></tr>'.
	'<th class=\'boldright\'>Assembly version:</th><td>'.$LIST_GS[0]['ASSEMBLY_VERSION'].'</td></tr>'.
	'<th class=\'boldright\'>Assembly date:</th><td>'.$LIST_GS[0]['CREATION_DATE'].'</td></tr>'.
	'</table>';
	$ASSEMBLY_TTL=$DATA_CO;
	$DATA_TTL[$DATA_CO]=$TTL;


	++$DATA_CO;$SEQ_ROLE=$DATA_CO;
	$DATA_TTL[$DATA_CO]=$DEFS[$LIST_GS[0]['SEQ_ROLE']];
	
	
	$UNIT='';
	if (isset($DEFS[$LIST_GS[0]['ASSEMBLY_UNIT']]))
	{
		
	++$DATA_CO;
	$UNIT=' class="ttl_tr ttl_tag" title="Assembly" data-pos="'.$DATA_CO.'"';
	$DATA_TTL[$DATA_CO]=$DEFS[$LIST_GS[0]['ASSEMBLY_UNIT']];
	}
	$N_TR=0;
	foreach ($LIST_GS as $G)$N_TR+=$G['N_TRANSCRIPTS'];
	$STR.='<tr class="chr_seqs" id="'.$NAME.'">
		<td><input type="checkbox" onclick="showTranscripts()" name="chrseqs[]" '.(($IS_FIRST)?'checked="checked" ':'').' class="chrseqs" value="'.$NAME.'"/></td>
			<td class="ttl_tr ttl_tag" title="Assembly" data-pos="'.$ASSEMBLY_TTL.'">'.$LIST_GS[0]['ASSEMBLY_NAME'].'</td><td>'.$LIST_GS[0]['CHR_NUM'].
		  '</td><td '.$UNIT.'>'.$LIST_GS[0]['ASSEMBLY_UNIT'].
		  '</td><td class="ttl_tr ttl_tag" title="sequence role" data-pos="'.$SEQ_ROLE.'">'.$LIST_GS[0]['SEQ_ROLE'].
		  '</td><td>'.$N_TR.
		  '</td></tr>';
		  if ($IS_FIRST)	$IS_FIRST=false;

}

/*
<tr><th>Chromosome</th>
						<th>Assembly Unit</th>
						<th>Sequence Role</th>
							<th>Transcripts</th> */


	  changeValue("transcripts","GENE_SEQ_LOCS",$STR);
	//
	
	changeValue("transcripts","TOOLTIPS",str_replace("'","\\'",json_encode(str_replace("\n","",$DATA_TTL))));


?>