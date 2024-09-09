<?php


if (!defined("BIORELS")) header("Location:/");

$LOG='';
foreach ($MODULE_DATA['INFO']['JOB_STATUS']['LOG'] as $LOG_V)
{
$LOG.='<tr><td style="width:175px">'.$LOG_V[1].'</td><td>'.$LOG_V[0].'</td></tr>';
}
changeValue("sirna_orthologs_results",'LOG',$LOG);


changeValue("sirna_orthologs_results","INPUT",$MODULE_DATA['INFO']['PARAMS']['SEQUENCE']);
changeValue("sirna_orthologs_results","MISMATCH",$MODULE_DATA['INFO']['PARAMS']['MISMATCH']);
changeValue("sirna_orthologs_results","SYMBOL",$MODULE_DATA['INFO']['PARAMS']['GENE_INFO']['SYMBOL']);
changeValue("sirna_orthologs_results","FULL_NAME",$MODULE_DATA['INFO']['PARAMS']['GENE_INFO']['FULL_NAME']);

$STR='';
if ($MODULE_DATA['INFO']['PARAMS']['SENSE']&&$MODULE_DATA['INFO']['PARAMS']['ANTISENSE'])$STR=' Sense & Antisense';
else if ($MODULE_DATA['INFO']['PARAMS']['SENSE'])$STR='Sense';
else $STR='Antisense';
changeValue("sirna_orthologs_results","STRANDS",$STR);



$STR='';
foreach ($MODULE_DATA['INFO']['PARAMS']['ORGANISM'] as $ORGANISM)$STR.='<li>'.$MODULE_DATA['PRE_INPUT']['GENOME'][$ORGANISM][0]['SCIENTIFIC_NAME'].'</li>';
changeValue("sirna_orthologs_results","LIST_ORGS",$STR);



changeValue("sirna_orthologs_results","HASH",$MD5_HASH);

if (!isset($MODULE_DATA['INFO']['JOB_STATUS']))
{
	changeValue("sirna_orthologs_results","MONITOR_STR","Job is in queue");
	return;
}
else if (strpos($MODULE_DATA['INFO']['JOB_STATUS']['STATUS'],'Success')===false
&& $MODULE_DATA['INFO']['JOB_STATUS']['STATUS']!='Failed')
{
	changeValue("sirna_orthologs_results","MONITOR_STR","Job is currently running - This page will refresh in 3s");
	return;

}
else removeBlock("sirna_orthologs_results","MONITOR");



$RES_ALL=&$MODULE_DATA['FILES'][0]['DOCUMENT_CONTENT'];
 $TOT=2;
 $N_MENU=1;
// $STR='';
// $STR_MENU='';
// $STR_DIV='';
// $STR_JS='<script type="text/javascript">';$R_ID=0;
// foreach ($RES_ALL as $ORGANISM=>&$ORG_RES)
// {
// 	$TAX_NAME=$MODULE_DATA['PRE_INPUT']['GENOME'][$ORGANISM][0]['SCIENTIFIC_NAME'];
// 	++$N_MENU;
// 	$STR_MENU.='<li id="ssr_'.$MD5_HASH.'_tab_'.$N_MENU.'" class="active nav-item nav-link" onclick="showMenu(\'ssr_'.$MD5_HASH.'\','.$N_MENU.','.$TOT.')">'.$TAX_NAME.'</li>'."\n";
//  	$STR.='<div id="ssr_'.$MD5_HASH.'_view_'.$N_MENU.'" class="container-grey w3-container" style="display:none" >';
//  	$STR.='<h3 style="text-align:center;margin-top:30px;margin-bottom:10px">'.$TAX_NAME.'</h3>';
// 	foreach ($ORG_RES as $SEQ=>&$SEQ_INFO)
// 	{
// 		foreach ($SEQ_INFO as $SEQ_POS=>&$TR_INFO)
// 		{
// 		++$R_ID;
// 		$TRN=$TR_INFO['transcript_name'];
// 		if ($TR_INFO['transcript_version']!='')$TRN.='.'.$TR_INFO['transcript_version'];
// 		$STR.='<div class="container-grey w3-container">
// 		<h5>'.$TRN.'</h5>
// 		<div id="R_'.$R_ID.'"></div></div><br/>';
		
// 		$STR_JS.="loadModule('R_".$R_ID."','/CONTENT/GENEID/".$TR_INFO['gene_id']."/TRANSCRIPT_SEL/".$TRN."/PARAMS/RNA/".$SEQ_POS.'/'.((int)($SEQ_POS)+strlen($SEQ))."/HIGHLIGHT/0');\n";

// 		}
// 	}
// 	$STR.='</div>';

// }

$STR='';
$STR_MENU='';
$R_ID=0;
$STR_JS='<script type="text/javascript">';
$STR_DIV='';
	++$N_MENU;
	$STR_MENU.='<li id="ssr_'.$MD5_HASH.'_tab_'.$N_MENU.'" class="active nav-item nav-link" onclick="showMenu(\'ssr_'.$MD5_HASH.'\','.$N_MENU.','.$TOT.')">Results</li>'."\n";
 	$STR.='<div id="ssr_'.$MD5_HASH.'_view_'.$N_MENU.'" class="container-grey w3-container" style="display:none" >';
 	$STR.='<h3 style="text-align:center;margin-top:30px;margin-bottom:10px">Results</h3>';

	$STR.='<table class="table" style="text-align:center">
			<thead>
			<tr>
				<th>Organism</th>
				<th>Gene</th>';
	 for ($I=0;$I<=$MODULE_DATA['INFO']['PARAMS']['MISMATCH'];++$I)
	 {
		$STR.='	<th>';
		switch ($I)
		{ 
			case 0: $STR.='Exact match';break;
			case 1: $STR.='1 mismatch';break;
			default: $STR.=$I.' mismatches';break;
		}
		$STR.='</th>';
	}
	$STR.='</tr></thead><tbody>';
	
	foreach ($MODULE_DATA['INFO']['PARAMS']['ORGANISM'] as $TAX_ID)
	{
		$TAX_NAME=$MODULE_DATA['PRE_INPUT']['GENOME'][$TAX_ID][0]['SCIENTIFIC_NAME'];
		$FIRST=true;
		

		foreach ($RES_ALL[$TAX_ID] as $GENE_ID=>&$GENE_RES)
		{
			$STR.='<tr>';
			//echo '<pre>';print_r($GENE_RES);
			if ($FIRST)
			$STR.='<td rowspan="'.count($RES_ALL[$TAX_ID]).'">'.$TAX_NAME.'</td>'."\n";
			$FIRST=false;
			$STR.='<td><a href="/GENEID/'.$GENE_RES['GENE']['gene_id'].'">'.$GENE_RES['GENE']['symbol'].'</a></td>'."\n";
			
			for ($I=0;$I<=$MODULE_DATA['INFO']['PARAMS']['MISMATCH'];++$I)
			{
				$STR_MM='';
				switch ($I)
				{ 
					case 0: $STR_MM='Exact match';break;
					case 1: $STR_MM='1 mismatch';break;
					default: $STR_MM=$I.' mismatches';break;
				}
				if (isset($GENE_RES['RES']['N'.$I]))
				{
					$LIST_TYPES=array();
					foreach ($GENE_RES['RES']['N'.$I] as $SEQ=>&$SEQ_MATCH)
					foreach ($SEQ_MATCH as &$MATCH_INFO)
					{
						ksort($MATCH_INFO['REGIONS']);
						$STRR='';$STRM='';
						$PREV_V=0;
						foreach ($MATCH_INFO['REGIONS'] as $TYPE=>$V)
						{
							switch ($TYPE)
							{
								case "3'UTR":
						case "5'UTR":
							$STRR.=' green '.$PREV_V.'%,';
							$STRM.=$TYPE.','; $STRR.=' green '.floor($V/strlen($SEQ)*100).'%,';break;
						case "CDS":$STRM.=$TYPE.',';
						$STRR.=' purple '.$PREV_V.'%,';
						$STRR.=' purple '.floor($V/strlen($SEQ)*100).'%,';break;
						default:
						$STRR.=' darkblue '.$PREV_V.'%,';
						$STRM.=$TYPE.',';$STRR.=' darkblue '.floor($V/strlen($SEQ)*100).'%,';break;
							}
						}
						$PREV_V=floor($V/strlen($SEQ)*100);
					}
					$LIST_TYPES[substr($STRR,0,-1)]=substr($STRM,0,-1);
					
				$STR.='<td style="cursor:pointer" onclick="prep_dialog_R_'.$GENE_ID.'_'.$I.'()">';$N_K=0;
				foreach ($LIST_TYPES as $TYPE=>&$NAME_TYPE)

				{
					
					$STR.='<span style="background: linear-gradient(to right, '.$TYPE.');color:white;font-weight:bold;border:1px solid;border-radius: 50px;padding: 1px 6px;position:relative;top:'.($N_K*3).'px;">'.$NAME_TYPE.'</span><br/>';
					++$N_K;
				}
				$STR.='</td>'."\n";
				$STR_DIV.='<div id="R_'.$GENE_ID.'_'.$I.'" style="display:none;text-align:center;" title="'.$TAX_NAME.' - '.$GENE_RES['GENE']['symbol'].' - '.$STR_MM.'">';
				

				$STR_JS.='function prep_dialog_R_'.$GENE_ID.'_'.$I.'()
				{
					';
				foreach ($GENE_RES['RES']['N'.$I] as $SEQ=>&$SEQ_MATCH)
				{
					$STR_DIV.='<h4>'.$SEQ.'</h4>';
					foreach ($SEQ_MATCH as &$MATCH_INFO)
					{
						++$R_ID;
				 	 		$TRN=$MATCH_INFO['transcript_name'];
				 	 		if ($MATCH_INFO['transcript_version']!='')$TRN.='.'.$MATCH_INFO['transcript_version'];
							$STR_DIV.='<h5>'.$TRN.'</h5><div id="R_'.$R_ID.'" style="margin-bottom:15px"></div>';
							$SEQ_POS=$MATCH_INFO['POS_MATCH'];
					 		$STR_JS.="loadModule('R_".$R_ID."','/CONTENT/GENEID/".$MATCH_INFO['gene_id']."/TRANSCRIPT_SEL/".$TRN."/PARAMS/RNA/".$SEQ_POS.'/'.((int)($SEQ_POS)+strlen($SEQ));
							if ($MATCH_INFO['MISMATCH']!='')
							{
								
								$tab=explode("/",$MATCH_INFO['MISMATCH']);
								foreach ($tab as $K)$STR_JS.='/HIGHLIGHT/'.($SEQ_POS+$K);
								
							}
							$STR_JS.="');\n";

					}
				}
				$STR_DIV.='</div>';
				$STR_JS.='$("#R_'.$GENE_ID.'_'.$I.'" ).dialog({maxWidth:600,
                    maxHeight: 500,
                    width: 600,
                    height: 500,
                    modal: true});
					}';
				//foreach ($SEQ_INFO as $SEQ_POS=>&$TR_INFO)
				// 	 			{
				// 	 		++$R_ID;
				// 	 		$TRN=$TR_INFO['transcript_name'];
				// 	 		if ($TR_INFO['transcript_version']!='')$TRN.='.'.$TR_INFO['transcript_version'];
				// 	 		$STR.='
				// 	 		<span>'.$TRN.'</span>
				// 	 		<span id="R_'.$R_ID.'"></span>';
							 
				// 	 		$STR_JS.="loadModule('R_".$R_ID."','/CONTENT/GENEID/".$TR_INFO['gene_id']."/TRANSCRIPT_SEL/".$TRN."/PARAMS/RNA/".$SEQ_POS.'/'.((int)($SEQ_POS)+strlen($SEQ));
				// 	 		if ($TR_INFO['MISMATCH']!='')
				// 	 		{
								 
				// 	 			$tab=explode("/",$TR_INFO['MISMATCH']);
				// 	 			foreach ($tab as $K)$STR_JS.='/HIGHLIGHT/'.($SEQ_POS+$K);
								 
				// 	 		}
				// 	 		$STR_JS.="');\n";
					 
				// 	 			}
				// 	 		}
				// 	 		$STR.='</div>';


				}
				else 
				{
				$STR.='<td></td>';
				}
			}
			$STR.='</tr>';
		}
		
		
		
	}
	$STR.='</tbody></table>';



// $N_R=" style=\"width:".(floor(100/($MODULE_DATA['INFO']['PARAMS']['MISMATCH']+2))-2)."%;margin-left:1%;margin-right:1% ";
// $STR.='<div class="w3-container w3-col s12">
// <div class="w3-col" '.$N_R.'">Organism</div>';
	
// 	 for ($I=0;$I<=$MODULE_DATA['INFO']['PARAMS']['MISMATCH'];++$I)
// 	 {
// 	$STR.='	<div class="w3-col" '.$N_R.'">';
// 	 switch ($I)
// 	 { 
// 	 	case 0: $STR.='Exact match';break;
// 	 	case 1: $STR.='1 mismatch';break;
// 	 	default: $STR.=$I.' mismatches';break;
// 	 }
// 	 $STR.='</div>';
// 	 }
// 	 $STR.='</div>';
// 	 foreach ($RES_ALL as $ORGANISM=>&$ORG_RES)
// 	 {
// 	 	$TAX_NAME=$MODULE_DATA['PRE_INPUT']['GENOME'][$ORGANISM][0]['SCIENTIFIC_NAME'];
// 		 $STR.='<div class="w3-container w3-col s12">
// 		 <div class="w3-col" '.$N_R.'">'.$TAX_NAME.'</div>';
	 	
// 	 	for ($I=0;$I<=$MODULE_DATA['INFO']['PARAMS']['MISMATCH'];++$I)
// 	 	{
// 	 		if (!isset($ORG_RES['N'.$I])){$STR.='<div  class="w3-col" '.$N_R.'"></div>';continue;}
// 	 		$STR.='<div  class="w3-col" '.$N_R.';max-height:90px;overflow-y:scroll">';
// 	 		foreach ($ORG_RES['N'.$I] as $SEQ=>&$SEQ_INFO)
// 	 		{
// 	 			foreach ($SEQ_INFO as $SEQ_POS=>&$TR_INFO)
// 	 			{
// 	 		++$R_ID;
// 	 		$TRN=$TR_INFO['transcript_name'];
// 	 		if ($TR_INFO['transcript_version']!='')$TRN.='.'.$TR_INFO['transcript_version'];
// 	 		$STR.='
// 	 		<span>'.$TRN.'</span>
// 	 		<span id="R_'.$R_ID.'"></span>';
			 
// 	 		$STR_JS.="loadModule('R_".$R_ID."','/CONTENT/GENEID/".$TR_INFO['gene_id']."/TRANSCRIPT_SEL/".$TRN."/PARAMS/RNA/".$SEQ_POS.'/'.((int)($SEQ_POS)+strlen($SEQ));
// 	 		if ($TR_INFO['MISMATCH']!='')
// 	 		{
				 
// 	 			$tab=explode("/",$TR_INFO['MISMATCH']);
// 	 			foreach ($tab as $K)$STR_JS.='/HIGHLIGHT/'.($SEQ_POS+$K);
				 
// 	 		}
// 	 		$STR_JS.="');\n";
	 
// 	 			}
// 	 		}
// 	 		$STR.='</div>';
			 
// 	 	}
// 	 	$STR.='</div>';
// 	 }

// $STR.='</div></div>';


changeValue("sirna_orthologs_results","HASH",$MD5_HASH);
changeValue("sirna_orthologs_results","COUNT",$TOT);
changeValue("sirna_orthologs_results","MENU_LIST",$STR_MENU);
changeValue("sirna_orthologs_results","CONTENT_LIST",$STR.$STR_DIV.$STR_JS.'</script>');



?>