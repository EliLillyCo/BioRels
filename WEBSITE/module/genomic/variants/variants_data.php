<?php
if (!defined("BIORELS")) header("Location:/");
ini_set('memory_limit','1000M');

$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
$MODULE_DATA['TRANSCRIPTS']=getListTranscripts($GN_ENTRY_ID);
if (count($MODULE_DATA['TRANSCRIPTS']))
{
$START=1000000000;
$END=-1;
foreach ($MODULE_DATA['TRANSCRIPTS']['GENE_SEQ'] as $T)
{
	if ($T['START_POS']<$START)$START=$T['START_POS'];
	if ($T['END_POS']>$END)$END=$T['END_POS'];
	$CHR_ID=$T['CHR_ID'];
}
//$MODULE_DATA['MUT_LIST']=getDistinctMutations($CHR_ID,$START,$END);
$MODULE_DATA['CLIN_SIGN']=getDistinctClinSign($GN_ENTRY_ID);

}


?>