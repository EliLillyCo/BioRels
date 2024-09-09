<?php

 if (!defined("BIORELS")) header("Location:/");
changeValue("grna_exprs","GENE_ID",$USER_INPUT['PORTAL']['VALUE']);


$STR='<optgroup label="Transcripts">';
foreach ($MODULE_DATA['TRANSCRIPTS'] as &$TR)
{
	$TR_NAME=$TR['TRANSCRIPT_NAME'];
	if ($TR['TRANSCRIPT_VERSION']!='')$TR_NAME.='.'.$TR['TRANSCRIPT_VERSION'];
	$STR.='<option value="'.$TR_NAME.'">'.$TR_NAME.'</option>';
}


foreach ($TISSUE_NAME as $TAX_ID=>$ORGANS)
{
	ksort($ORGANS);
	foreach ($ORGANS as $ORGAN=>$LIST)
	{
		$STR.='<optgroup label="'.$TAX_ID.' - '.$ORGAN.'">';
		foreach ($LIST as $TISSUE_NAME=>$FULL_NAME)
		{
			$STR.='<option value="TS:'.$TAX_ID.'__'.$ORGAN.'__'.$TISSUE_NAME.'">'.$FULL_NAME.'</option>';
		}
		$STR.='</optgroup>';
	
	}
	$STR.='</optgroup>';
}
changeValue("grna_exprs","OPTIONS",$STR);
?>