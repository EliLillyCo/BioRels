<?php

if (!defined("BIORELS")) header("Location:/");
$STR='';
changeValue('varclin','GENE_ID',$USER_INPUT['PORTAL']['DATA']['GENE_ID']);
$ORDER=array('CLINV_MEASURE_TYPE','CLINV_IDENTIFIER','TITLE','CLIN_SIGN_DESC','DATE_UPDATED','MUT','RSID');
foreach ($MODULE_DATA as $DATA)
{
	$STR.='<tr>';
	foreach ($ORDER as $N)
	{
		$STR.='<td>';
		if ($N=='MUT')
		{
		 if ($DATA['POSITION']!='')	$STR.=$DATA['POSITION'].'.'.$DATA['REF_ALL'].'>'.$DATA['ALT_ALL'];
		 else $STR.='N/A';
		}
		else if ($N=='CLINV_IDENTIFIER')
		$STR.='<a href="/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/CLINVAR/'.$DATA[$N].'">'.$DATA[$N].'</a>';
		else if ($N=='RSID')
		$STR.='<a href="/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID'].'/MUTATION/'.$DATA[$N].'">'.$DATA[$N].'</a>';
		else $STR.=$DATA[$N];
		$STR.='</td>';
	}
	$STR.='</tr>';
}
changeValue('varclin','LIST',$STR);
?>