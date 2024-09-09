<?php
if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA=array('TRANSCRIPTS'=>array());
$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
$INPUTS=array();
foreach ($USER_INPUT['PARAMS'] as $V)
{
	$T=explode(":",str_replace("-_-","/",str_replace("__"," ",$V)));
	if (count($T)!=2) return ;
	$INPUTS[]=$T;

}

try{

	$GENE_SEQ_LOC=getGeneSeqLoc($GN_ENTRY_ID);

	$CHR_SEQ_ID=array();
	foreach ($GENE_SEQ_LOC as $GS)
	{
		foreach ($INPUTS as $T)	
		if ($GS['CHR_NUM']==$T[0] 
	&& $GS['ASSEMBLY_UNIT']==$T[1]){$CHR_SEQ_ID[]=$GS['CHR_SEQ_ID'];break;}
	}

	if ($CHR_SEQ_ID!=array()){
	$T=getListExprTranscript($GN_ENTRY_ID);
	$TR_GTEX=array();
	foreach ($T as $K)$TR_GTEX[]=$K['TRANSCRIPT_ID'];

	$TISSUES=getListTissues();
	$TMP_T=array();foreach ($TISSUES as $T)$TMP_T[$T['RNA_TISSUE_ID']]=$T['ORGAN_NAME'].'_'.$T['TISSUE_NAME'];

	$MODULE_DATA=getListTranscripts($GN_ENTRY_ID,$CHR_SEQ_ID);

	if (isset($MODULE_DATA['TRANSCRIPTS'])){
	$TMP_LIST_TRANSCRIPT=array();
	foreach ($MODULE_DATA['TRANSCRIPTS'] as $T)$TMP_LIST_TRANSCRIPT[]=$T['TRANSCRIPT_ID'];
	$MODULE_DATA['PROT']=getTranscriptToProtein($TMP_LIST_TRANSCRIPT)['STAT'];


	$TMP=getMedExprTranscript($GN_ENTRY_ID);

	foreach ($MODULE_DATA['TRANSCRIPTS'] as $T=>&$INFO){
		if (!isset($TMP[$INFO['TRANSCRIPT_ID']]))continue;
		$TMP_E=array();
		foreach ($TMP[$INFO['TRANSCRIPT_ID']] as $TISSUE_ID=>$VALUE)$TMP_E[$TMP_T[$TISSUE_ID]]=$VALUE;
		$INFO['RNA_EXPR']=$TMP_E;
	}

	}

	}

}catch(Exception $e)

{
	
  if (strpos($e->getMessage(),'Error while running query')!==false)$MODULE_DATA['ERROR']="Unable to retrieve transcripts information";
  else $MODULE_DATA['ERROR']=$e->getMessage();

}
?>
