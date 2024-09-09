<?php

if (!defined("BIORELS")) header("Location:/");
//print_r($MODULE_DATA);
//exit;
$STR='';
$ratio = 9.601907;
$ID=0;

$MAP_DIV=array();
$TRMAPID=0;
foreach ($MODULE_DATA['ASSEMBLY'] as $ASSEMBLY_NAME=>&$ASS_INFO)
{
	$REL_TR=array('REGION'=>array(),'EXON'=>array());
	$STR.='<div class="sequence w3-col s12" id="premrna_seq_view" style="    width: 95%;height: fit-content;margin-bottom: 50px;min-height: 50px;">';
	$LEN_GENE=$ASS_INFO['RANGE'][1]-$ASS_INFO['RANGE'][0];
	//echo $LEN_GENE."\n";
	$STEP=floor(floor($LEN_GENE/10)/100)*100;
	
	$STR.='<div class="utrs" style="width:100%;margin-bottom:1px;height:20px;text-align:center;background-color:lightgrey;font-weight:bold;top:unset;display:flex">'
	.$ASSEMBLY_NAME.' ' .$ASS_INFO['RANGE'][0].' - '.$ASS_INFO['RANGE'][1].' ('.$ASS_INFO['STRAND'].')</div>';

	$STR.='<div class="utrs" style="width:100%;margin-bottom:1px;height:16px;top:unset;display:flex;flex-direction:row; align-items:center">';
	$STR.='<div style="font-style:italic;min-width:${MAX_LEN}px;width:${MAX_LEN}px">Position:</div><div style="flex-grow:1;position:relative;width:100%;top:-8px">';
	for ($I=0;$I<10;++$I)
	{
		$LEFT=round($I*$STEP/$LEN_GENE*100,3);
	$STR.='<div  style="position:absolute;left:'.$LEFT.'%;height:16px">|'.($I==0?1:($STEP*$I)).'</div>';
	}
	$STR.='</div></div>';

	$max_len=0;
	foreach ($ASS_INFO['GS'] as $G)
	{
		$LEFT=($G['START_POS']-$ASS_INFO['RANGE'][0])/$LEN_GENE*100;
		$WIDTH=max(($G['END_POS']-$G['START_POS'])/$LEN_GENE*100,0.2);
		
		$name=$G['GENE_SEQ_NAME'].(($G['GENE_SEQ_VERSION']!='')?'.'.$G['GENE_SEQ_VERSION']:'');
		$len_name=round(strlen($name)*$ratio,4)+1;
		if ($len_name>$max_len) $max_len=$len_name;
	$STR.='<div class="utrs" style="width:100%;margin-bottom:1px;height:16px;top:unset;display:flex;flex-direction:row; align-items:center">';
	$STR.='<div style="font-style:italic;min-width:${MAX_LEN}px;width:${MAX_LEN}px">'.$name.'</div>
	<div style="flex-grow:1;position:relative;width:100%;top:-8px">
	<div  style="border:1px solid orange; background-color:orange;position:absolute;left:'.$LEFT.'%;width:'.$WIDTH.'%;height:16px"></div>
	</div></div>';
	}
	
	foreach ($ASS_INFO['TR'] as $G)
	{
		
		$name=$G['TRANSCRIPT_NAME'].(($G['TRANSCRIPT_VERSION']!='')?'.'.$G['TRANSCRIPT_VERSION']:'');
		$len_name=round(strlen($name)*$ratio,4)+1;
		if ($len_name>$max_len) $max_len=$len_name;

		// echo '<pre>';
		// print_R($G);

	$STR.='<div class="utrs" style="width:100%;margin-bottom:1px;height:16px;top:unset;display:flex;flex-direction:row; align-items:center">';
	$STR.='<div style="font-style:italic;min-width:${MAX_LEN}px;width:${MAX_LEN}px">'.$name.'</div>
	<div style="flex-grow:1;position:relative;width:100%;top:-8px">';
	$EX_ORDER=array();
	$G_ORDER=array();
	
	
	foreach ($G['BOUNDARIES'] as $B)
	{
		
		if ($B['MIN_POS']=='' || $B['MAX_POS']=='')continue;
		$REL_POS=0;$END_POS=0;
		if ($ASS_INFO['STRAND']=='+')	$REL_POS=$B['MIN_POS']-$ASS_INFO['RANGE'][0];
		else							$REL_POS=$ASS_INFO['RANGE'][1]-$B['MAX_POS'];
		
		$END_POS=$REL_POS+($B['MAX_POS']-$B['MIN_POS']);
		if (!isset($EX_ORDER[$B['EXON_ID']]))$EX_ORDER[$B['EXON_ID']]=array($REL_POS,$END_POS,array(),'Exon '.$B['EXON_ID'],0,0);
		else $EX_ORDER[$B['EXON_ID']][1]=$END_POS;
		$EX_ORDER[$B['EXON_ID']][2][]=array($REL_POS,$END_POS,$B['TRANSCRIPT_POS_TYPE']);
		$G_ORDER[$B['TRANSCRIPT_POS_TYPE']]=array(0,0,0,0);
	
		$LEFT=($REL_POS)/$LEN_GENE*100;
		$WIDTH=max(($B['MAX_POS']-$B['MIN_POS'])/$LEN_GENE*100,0.2);
		if ($LEFT+$WIDTH>100) $WIDTH-=($LEFT+$WIDTH-100);
		$STR.='<div class="';
		if ($B['TRANSCRIPT_POS_TYPE'] == "5'UTR" || $B['TRANSCRIPT_POS_TYPE'] == "3'UTR" || $B['TRANSCRIPT_POS_TYPE'] == "3'UTR-INFERRED" || $B['TRANSCRIPT_POS_TYPE'] == "5'UTR-INFERRED") $STR .= "trsq_UTR_view";
		else if ($B['TRANSCRIPT_POS_TYPE'] == 'CDS' || $B['TRANSCRIPT_POS_TYPE'] == "CDS-INFERRED")$STR.= 'trsq_CDS_view';
		else if ($B['TRANSCRIPT_POS_TYPE'] == 'non-coded' || $B['TRANSCRIPT_POS_TYPE'] == "non-coded-INFERRED")$STR.= 'trsq_nc_view';
		else if ($B['TRANSCRIPT_POS_TYPE'] == 'poly-A' || $B['TRANSCRIPT_POS_TYPE'] == "unknown")$STR.= 'trsq_unk';
		

	 $STR.='" data-min="'.$B['MIN_POS'].'" data-max="'.$B['MAX_POS'].'" data-rmin="'.$REL_POS.'" data-rmax="'.$END_POS.'"  data-exon="'.$B['EXON_ID'].'" style="position:absolute;left:'.$LEFT.'%;width:'.$WIDTH.'%;height:16px"></div>';
	}
	

	$NEW_EX_ORDER=array();
	if ($EX_ORDER[1][0]>0)$NEW_EX_ORDER[]=array(0,$EX_ORDER[1][0]-1,array(array(0,$EX_ORDER[1][0]-1,'OUTSIDE')),'Before',0,0);
	$NEW_EX_ORDER[]=$EX_ORDER[1];
	for($I=2;$I<=count($EX_ORDER);++$I)
	{
		//echo $I."\t".count($EX_ORDER)."\t".count($NEW_EX_ORDER)."\n";
		$NEW_EX_ORDER[]=array($EX_ORDER[$I-1][1]+1,$EX_ORDER[$I][0]-1,array(array($EX_ORDER[$I-1][1]+1,$EX_ORDER[$I][0]-1,'INTRON')),'Intron '.($I-1).'-'.$I,0,0);
		$NEW_EX_ORDER[]=$EX_ORDER[$I];
	}
	$NEW_EX_ORDER[]=array($EX_ORDER[count($EX_ORDER)][1]+1,$REL_POS,array(array($EX_ORDER[count($EX_ORDER)][1]+1,$REL_POS,'OUTSIDE')),'After',0,0);
	$G_ORDER['INTRON']=array(0,0,0,0);
	$G_ORDER['OUTSIDE']=array(0,0,0,0);
	
	$REL_TR['REGION'][$name]=$G_ORDER;
	$REL_TR['EXON'][$name]=$NEW_EX_ORDER;
	$STR.='</div></div>';
//print_R($NEW_EX_ORDER);

	}
	//exit;
	
	
	
	
	$STR=str_replace('${MAX_LEN}',$max_len,$STR);

	++$ID;
	$RNA_SEQ_DATA[$ID]=array(implode('',$ASS_INFO['SEQUENCE']),$max_len,$REL_TR);
	$STR.='<div id="premrna_match_'.$ID.'"></div>';
	$STR.='</div>';
	$STR.='<div class="w3-container" id="premrna_info_'.$ID.'"></div>
	<div class="w3-col s12 m12 l12">
	<h4>Sequence location:</h4>
	<table style="display:none" class="table" id="premrna_tbl_'.$ID.'"></table>
	</div>';
	$STR.='<div class="w3-col s12 m12 l12" id="premrna_chart_'.$ID.'">
	<h4>Transcripts coverage</h4><select class="form-select" id="dr_'.$ID.'_sel" onchange="changeViewDCY_'.$ID.'()">';
	$STR.='<option value=""></option>'."\n";
	$TMP_DIV='';
	$LIST=array();
	$FIRST=true;
	foreach ($ASS_INFO['TR'] as $G)
	{
		
		
		$TRMAPID++;
		$MAP_DIV[$ID.'_'.$name]='drtr_'.$TRMAPID;
		$name=$G['TRANSCRIPT_NAME'].(($G['TRANSCRIPT_VERSION']!='')?'.'.$G['TRANSCRIPT_VERSION']:'');
		$STR.='<option value="'.$ID.'_'.$name.'">'.$name.'</option>'."\n";
		$TMP_DIV.='	<div id="drtr_'.$TRMAPID.'" style="display:'.(($FIRST)?'block':'none').'">'."\n";
		$LIST[]="'".$ID.'_'.$name."'";
		
		$TMP_DIV.='<div class="w3-container">	<div class="w3-col s12 m6 l6" id="drtr_'.$TRMAPID.'_EX" ></div>'."\n";
		$TMP_DIV.='	<div class="w3-col s12 m6 l6"  id="drtr_'.$TRMAPID.'_RE"></div>'."\n";
		$TMP_DIV.='</div></div>'."\n";
		$FIRST=false;
	}
	$STR.='</select>'.$TMP_DIV;
	$STR.='<script type="text/javascript"> list_tr_view_'.$ID.'=['.implode(',',$LIST).'];'."\n";
	$STR.='function changeViewDCY_'.$ID.'() {
		var sel=$("#dr_'.$ID.'_sel").val();
		
		for(var i=0;i<list_tr_view_'.$ID.'.length;++i){
		if(list_tr_view_'.$ID.'[i]==sel)
		{
			$("#"+map_div[list_tr_view_'.$ID.'[i]]).css("display","block");
			if (sel=="")continue;
			loadGraph(sel);
			
		}
		else
		$("#"+map_div[list_tr_view_'.$ID.'[i]]).css("display","none");
		}
		}
		</script>';

$STR.='</div>';
	$STR.='</div>';
	
}



changeValue("premrna","FULL_SEQ",str_replace("'","\\'",json_encode($RNA_SEQ_DATA	)));
changeValue("premrna","SEQ_VIEW",$STR);

$STR='';
foreach ($MAP_DIV as $K=>$V)$STR.='"'.$K.'":"'.$V.'",';
changeValue("premrna","MAP_DIV",substr($STR,0,-1));

?>