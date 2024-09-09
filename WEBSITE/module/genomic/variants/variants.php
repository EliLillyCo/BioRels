<?php

changeValue("variants","SYMBOL",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
changeValue("variants","GENE_ID",$USER_INPUT['PORTAL']['DATA']['GENE_ID']);

if (count($MODULE_DATA['TRANSCRIPTS'])==0)
{
	removeBlock("variants","VALID");
	return;
}
else
{
	removeBlock("variants","INVALID");
}

$TPT=getListTranscriptPosType();
$STR='';
foreach ($TPT as $ID=>$VAL)	$STR.='<option value="'.$VAL.'">'.$VAL.'</option>';
$STR.='<option value="intron">Intron</option>';
changeValue("variants","LIST_TRANSCRIPT_LOC",$STR);

$STR='';
foreach ($MODULE_DATA['CLIN_SIGN'] as $K)
$STR.='<option value="'.$K.'">'.$K.'</option>';
changeValue("variants","LIST_CLIN_SIGN",$STR);


$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
$MODULE_DATA['TRANSCRIPTS']=getListTranscripts($GN_ENTRY_ID);

$STR='';
foreach ($MODULE_DATA['TRANSCRIPTS']['TRANSCRIPTS'] as &$INFO)
{
	$NAME=$INFO['TRANSCRIPT_NAME'];
	if ($INFO['TRANSCRIPT_VERSION']!='')$NAME.='.'.$INFO['TRANSCRIPT_VERSION'];
	$STR.='<option value="'.$NAME.'">'.$NAME.'</option>';
}
changeValue("variants","LIST_TRANSCRIPT",$STR);

$STR='';
foreach ($MODULE_DATA['MUT_LIST'] as $REF=>&$ALTS)
{
	if ($REF=='')$P='[EMPTY]';else $P=$REF;
	$STR.='<optgroup label="'.$P.'">';
	foreach ($ALTS as &$T)
	{
		if ($T=='')$K='[EMPTY]';else $K=$T;
		if ($T==$REF)continue;
		$STR.='<option value="'.$REF.'_'.$T.'">'.$K.'</option>';
	}
	$STR.='</optgroup>';
}
changeValue("variants","LIST_MUT_TYPE",$STR);

$LIST_ALLOWED=array(
	'coding_sequence_variant',
	'intron_variant',
	'upstream_transcript_variant',
	'5_prime_UTR_variant',
	'3_prime_UTR_variant',
	'downstream_transcript_variant',
	'splice_donor_variant',
	'terminator_codon_variant',
	'genic_downstream_transcript_variant',
	'genic_upstream_transcript_variant'
);
$STR='';
foreach ($LIST_ALLOWED as $K)$STR.='<option value="'.$K.'">'.str_replace('_',' ',$K).'</option>';
changeValue("variants","LIST_TRANSCRIPT_IMPACT",$STR);
?>