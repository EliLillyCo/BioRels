<?php

if (isset($MODULE_DATA['ERROR']))
{
	removeBlock('variant_search','VALID');
	changeValue('variant_search','ERROR',$MODULE_DATA['ERROR']);
	return;
}else removeBlock('variant_search','INVALID');


$GENEID=$USER_INPUT['PORTAL']['DATA']['GENE_ID'];
$STR='';

foreach ($MODULE_DATA as &$ENTRY)
{
$STR.='<tr><td><a target="_blank" href="/GENEID/'.$GENEID.'/VARIANT/'.$ENTRY['RSID'].'">'.$ENTRY['RSID'].'</a></td>
		   <td>'.$ENTRY['REF_ALL'].'>'.$ENTRY['ALT_ALL'].'</td><td>';
		
		   $STR.='</td><td>'.$ENTRY['OV_FREQ'].'</td><td>'.$ENTRY['ST_FREQ'].'</td></tr>';

}
changeValue("variant_search","LIST",$STR);


$STR='';
foreach ($FILTERS as $F=>$V)
{
	switch ($F)
	{
		//'TRANSCRIPTS'=>array(),'LOCATION'=>array(),'CLINICAL'=>array(),'MUT_TYPE'=>array(),'IMPACT'=>array(),'FREQ_OV'=>array(0=>0,1=>100),'FREQ_ST'=>array(0=>1,1=>100),
		case 'TRANSCRIPTS':
			$STR.='<tr><td>Transcripts : </td><td>'.implode('<br/>',$V).'</td></tr>';break;
			case 'LOCATION':
			$STR.='<tr><td>Location : </td><td>'.implode(';',$V).'</td></tr>';break;
			case 'MUT_TYPE':
			$STR.='<tr><td>Alleles : </td><td>'.implode(';',$V).'</td></tr>';break;
			case 'IMPACT':
			$STR.='<tr><td>Impact : </td><td>'.implode(';',$V).'</td></tr>';break;
			case 'FREQ_OV':
			$STR.='<tr><td>Overall frequency range : </td><td>'.$V[0].' - '.$V[1].'</td></tr>';break;
			case 'FREQ_ST':
			$STR.='<tr><td>Study frequency range : </td><td>'.$V[0].' - '.$V[1].'</td></tr>';break;
		}
}
changeValue("variant_search","PARAMS",$STR);
?>