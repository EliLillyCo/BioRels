<?php

if (!defined("BIORELS")) header("Location:/");
//print_r($MODULE_DATA);
//exit;



if (in_array('NO_OPTION',$USER_INPUT['PARAMS']))
{
	
	removeBlock("transcripts_seq_summary","W_OPTION");
}
changeValue("transcripts_seq_summary","PARENT_DIV",$PARENT_DIV);
$pos=array_search("OPT_GROUP",$USER_INPUT['PARAMS'],true);
if ($pos!==false)
{
	if ($pos+1==count($USER_INPUT['PARAMS'])) throw new Exception("OPT_GROUP requires a value",ERR_TGT_SYS);
	if (!is_numeric($USER_INPUT['PARAMS'][$pos+1])) throw new Exception("OPT_GROUP requires a numeric value",ERR_TGT_SYS);
	changeValue("transcripts_seq_summary","OPT_GROUP",$USER_INPUT['PARAMS'][$pos+1]);
}
if (in_array('EXON_SEL',$USER_INPUT['PARAMS']))
changeValue("transcripts_seq_summary","EXON_SEL",'checked="checked"');
if (isset($MODULE_DATA['SEQUENCE']))
changeValue("transcripts_seq_summary","REFSEQ",$MODULE_DATA['SEQUENCE']);
changeValue("transcripts_seq_summary","SYMBOL",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);

changeValue("transcripts_seq_summary","TRANSCRIPT_SEQUENCE",str_replace("'","\\'",json_encode($MODULE_DATA)));


if (isset($MODULE_DATA['MATCHING_SEQ']) && $W_MATCH_TBL)
{
	switch ($MODULE_DATA['MATCHING_TYPE'])
	{
		case 1:changeValue("transcripts_seq_summary","HEAD",'<tr><th>Sequence</th><th>Position</th><th>Sense</th><th>Mismatch</th></tr>');break;
		case 2:changeValue("transcripts_seq_summary","HEAD",'<tr><th>Sequence</th><th>Name</th><th>Position</th><th>Sense</th><th>Mismatch</th></tr>');break;
		case 3:
		case 4:changeValue("transcripts_seq_summary","HEAD",'<tr><th>Sequence</th><th>Name</th><th>Potency</th><th>Position</th><th>Sense</th><th>Mismatch</th></tr>');break;
		
	}
	
	$N=0;
	$STR='';
	foreach ($MODULE_DATA['MATCHING_SEQ'] as &$REC)
	{
		
		if ($REC['RES']!=array())
		{
			$N++;
			foreach ($REC['RES'] as &$M)
			{
			switch ($MODULE_DATA['MATCHING_TYPE'])
			{
				case 1:$STR.='<tr><td>'.$REC['INPUT']['SEQ'].'</td><td>'.$M[0].'</td><td>'.$M[3].'</td><td>'.$M[1].'</td></tr>';break;
				case 2:$STR.='<tr><td>'.$REC['INPUT']['SEQ'].'</td><td>'.$REC['INPUT']['NAME'].'</td><td>'.$M[0].'</td><td>'.$M[3].'</td><td>'.$M[1].'</td></tr>';break;
				case 3:
				case 4:$STR.='<tr><td>'.$REC['INPUT']['SEQ'].'</td>
				<td>'.$REC['INPUT']['NAME'].'</td>
				<td>'.$REC['INPUT']['POTENCY'].'</td>
				<td>'.$M[0].'</td>
				<td>'.$M[3].'</td>
				<td>'.$M[1].'</td></tr>';break;
				//array($OFFSET+$POSSIBLE_MATCH[$SEQ_L1],1,$I,$IS_ACT,$SEQ_L1);}
				
			}
		}
		}
		else 
		{
			switch ($MODULE_DATA['MATCHING_TYPE'])
			{
				case 1:$STR.='<tr><td>'.$REC['INPUT']['SEQ'].'</td><td colspan="3">No match</td></tr>';break;
				case 2:$STR.='<tr><td>'.$REC['INPUT']['SEQ'].'</td><td>'.$REC['INPUT']['NAME'].'</td><td colspan="3">No match</td></tr>';break;
				case 3:
				case 4:$STR.='<tr><td>'.$REC['INPUT']['SEQ'].'</td><td>'.$REC['INPUT']['NAME'].'</td><td>'.$REC['INPUT']['POTENCY'].'</td><td colspan="3">No match</td></tr>';break;
				
			}	
		}
	}
	changeValue("transcripts_seq_summary","INFO",$N.'/'.count($MODULE_DATA['MATCHING_SEQ']).' sequence(s) found');
	changeValue("transcripts_seq_summary","MATCHES",$STR);
}else removeBlock("transcripts_seq_summary","W_MATCH");


?>