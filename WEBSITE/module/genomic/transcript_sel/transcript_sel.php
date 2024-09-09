<?php

if (!defined("BIORELS")) header("Location:./");











$TRANSCRIPT_NAME=$MODULE_DATA['INFO']['TRANSCRIPT_NAME'].(($MODULE_DATA['INFO']['TRANSCRIPT_VERSION']!=null)?'.'.$MODULE_DATA['INFO']['TRANSCRIPT_VERSION']:'');
changeValue("transcript_sel","TRANSCRIPT_NAME",$TRANSCRIPT_NAME);
if (count($MODULE_DATA['SEQ'])==0 )
{
    removeBlock("transcript_sel","VALID_TRANSCRIPT_SEL");
    removeBlock("transcript_sel","INCOMPLETE_TRANSCRIPT_SEL");
    
    return;
}
else if (count($MODULE_DATA['SEQ'])!=$MODULE_DATA['LENGTH'])
{
    removeBlock("transcript_sel","VALID_TRANSCRIPT_SEL");
    removeBlock("transcript_sel","INVALID_TRANSCRIPT_SEL");
    
    return;
}
removeBlock("transcript_sel","INVALID_TRANSCRIPT_SEL");
removeBlock("transcript_sel","INCOMPLETE_TRANSCRIPT_SEL");

if ($MODULE_DATA['INFO']['STRAND']=="+")
{
	changeValue("transcript_sel","LEFT","5'");
	changeValue("transcript_sel","RIGHT","3'");
}
if ($MODULE_DATA['INFO']['STRAND']=="-")
{
	changeValue("transcript_sel","LEFT","3'");
	changeValue("transcript_sel","RIGHT","5'");
}
$SEQ='<div class="seq"  style="float:left">';
$SEQ_POS='';
$SEQ_HEAD='';
$STAT=array('EX'=>array(),'UT'=>array(),'IN'=>array());$K=-1;$INTRON=-1;$STARTED=false;$START_UT=false;$UT=-1;$SEQ_OFFSET=0;
foreach ($MODULE_DATA['SEQ'] as &$ENTRY)
{
   
	++$K;
    if ($ENTRY['SEQ_POS']%10==0)
    {
        $SEQ_HEAD.='|';
        $SEQ_POS.=$ENTRY['SEQ_POS'];
        $SEQ_OFFSET=strlen($ENTRY['SEQ_POS']);
    }else if ($SEQ_OFFSET>1){$SEQ_OFFSET--;$SEQ_HEAD.=' ';}
    else if ($ENTRY['SEQ_POS']%5==0){$SEQ_POS.='|';$SEQ_HEAD.='|';}
    else {$SEQ_POS.=' ';$SEQ_HEAD.=' ';}

	if (isset($ENTRY['SEL']))
	{
        if (isset($ENTRY['T']))$SEQ.='<span class="bold grey_bc">'.$ENTRY['T'].'</span>';
        else $SEQ.='<span class="bold grey_bc">'.$ENTRY['P'].'</span>';	
    }
    else if (isset($MODULE_DATA['MATCH_STR']) ) 
    {
        if (!isset($ENTRY['MATCH'])) $SEQ.='<span class="bold">'.((isset($ENTRY['T']))?$ENTRY['T']:$ENTRY['P']).'</span>';	
        else  $SEQ.='<span>'.((isset($ENTRY['T']))?$ENTRY['T']:$ENTRY['P']).'</span>';	
    }
	else {
	if (isset($ENTRY['T']))$SEQ.=$ENTRY['T'];
	else $SEQ.=$ENTRY['P'];
	}
	if (isset($ENTRY['EXON']))
	{
		$STARTED=false;
	if (!isset($STAT['EX'][$ENTRY['EXON']]))$STAT['EX'][$ENTRY['EXON']]=array('MIN'=>$K,'MAX'=>$K);
	else $STAT['EX'][$ENTRY['EXON']]['MAX']=$K;
	}else 
	{
		$START_UT=false;
		if ($STARTED)$STAT['IN'][$INTRON]['MAX']=$K;
		else {++$INTRON;$STARTED=true;$STAT['IN'][$INTRON]=array('MIN'=>$K,'MAX'=>$K);}
	}
	if (isset($ENTRY['TYPE']))
	{
		if ($START_UT)
		{
            if ($ENTRY['TYPE']==$STAT['UT'][$UT]['TYPE'])			$STAT['UT'][$UT]['MAX']=$K;
            else
            {
                ++$UT;$START_UT=true;
			    $STAT['UT'][$UT]=array('MIN'=>$K,'MAX'=>$K,'TYPE'=>$ENTRY['TYPE']);
            }
		}
		else {
			++$UT;$START_UT=true;
			$STAT['UT'][$UT]=array('MIN'=>$K,'MAX'=>$K,'TYPE'=>$ENTRY['TYPE']);}
	
	}else  $START_UT=false;
}
$SEQ.='</div>
<div class="seq"  style="position:relative;top:-11px;float:left;white-space:pre;font-family: Courier New !important;">'.$SEQ_HEAD.'</div>
<div class="seq"  style="position:relative;top:-22px;float:left;white-space:pre;font-family: Courier New !important;">'.$SEQ_POS.'</div>
<div  style="float:left;height:18px;">';
$ratio=9.601907;
$WIDTH_ALL=$ratio*$MODULE_DATA['LENGTH']+10;
changeValue("transcript_sel","PRIME3_LEFT",$WIDTH_ALL);



foreach ($STAT['EX'] as $EX_ID=>$RANGE)
{
$SEQ.='<div class="transcript_seq_info '.(($EX_ID%2)?"exon_even":"exon_odd").'" style="float:left;left:'.($RANGE['MIN']*$ratio).'px; width:'.(($RANGE['MAX']-$RANGE['MIN']+1)*$ratio).'px">Exon '.$EX_ID.'</div>';
}

foreach ($STAT['IN'] as $EX_ID=>$RANGE)
{
$SEQ.='<div class="transcript_seq_info intron" style="float:left;left:'.($RANGE['MIN']*$ratio).'px; width:'.(($RANGE['MAX']-$RANGE['MIN']+1)*$ratio).'px">Intron</div>';
}	
$SEQ.='</div><div  style="float:left;height:20px;margin-top:16px">';
foreach ($STAT['UT'] as $EX_ID=>$RANGE)
{
    if ($RANGE['TYPE']=="3'UTR"||$RANGE['TYPE']=="5'UTR")$CLASS='UTR';
    else $CLASS='CDS';
$SEQ.='<div  class="transcript_seq_info trsq_'.$CLASS.'_view" style="float:left;left:'.($RANGE['MIN']*$ratio).'px; width:'.(($RANGE['MAX']-$RANGE['MIN']+1)*$ratio).'px">'.$RANGE['TYPE'].'</div>';
}	
$SEQ.='</div>';



changeValue("transcript_sel","SEQ",$SEQ);
$RANGE_TTL=$MODULE_DATA['PARAMS']['RANGE']['START'].'_'.$MODULE_DATA['PARAMS']['RANGE']['END'];
changeValue("transcript_sel","RANGE",$RANGE_TTL);
$catgroups=array("#e6194b",
"#3cb44b",
"#4363d8",
"#f58231",
"#911eb4",
"#9a6324",
"#800000",
"#000075",
"#808000",
"#fabed4",
"#00ff00",
);

$hexgroups=array("#ff0000",
"#fe4400",
"#f86600",
"#ee8200",
"#df9b00",
"#cdb200",
"#b6c700",
"#98db00",
"#6fed00",
"#00ff00"
);




if ($MODULE_DATA['MATCHING_SEQ']!=array())
{
    
    $STR='';
    $LINES=array();$LINES_STAT=array();
    $N_LINE_ALL=12;
    for($I=0;$I<$N_LINE_ALL;++$I)
    {
        $LINES[$I]=array();
        $LINES_STAT[$I]=array_fill(0,$MODULE_DATA['LENGTH'],false);
    }
    $HAS_MATCH=false;
    foreach ($MODULE_DATA['MATCHING_SEQ'] as &$SQ_INFO)
    {
        if ($SQ_INFO['RES']==array())continue;
        foreach ($SQ_INFO['RES'] as &$SQ_MATCH)
        {
             echo "###################\n";
             
            if ($SQ_MATCH[0]>=$MODULE_DATA['PARAMS']['RANGE']['END'])continue;
            $LEFT=max(0,$SQ_MATCH[0]-$MODULE_DATA['PARAMS']['RANGE']['START']);
            $HAS_MATCH=true;
            $SHIFT_START=0;
            if ($LEFT==0)
            {
                $SHIFT_START=($MODULE_DATA['PARAMS']['RANGE']['START']-$SQ_MATCH[0]);
                $RIGHT=min($LEFT+strlen($SQ_INFO['INPUT']['SEQ']),$MODULE_DATA['PARAMS']['RANGE']['END'])-$SHIFT_START;
            }
            else 
            
            
            $RIGHT=min($SQ_MATCH[0]+strlen($SQ_INFO['INPUT']['SEQ']),$MODULE_DATA['PARAMS']['RANGE']['END']+1)-$MODULE_DATA['PARAMS']['RANGE']['START'];

            
            if ($LEFT==0 && $RIGHT==0)continue;
            if ($RIGHT<0)continue;
            $LEN_TXT=strlen($SQ_INFO['INPUT']['NAME'])+1;
            $LEN_BLOCK=$RIGHT-$LEFT+1;
  
                
            $STR_MM='';
            if ($SQ_MATCH[1]!=0)
            {
                $tab=explode("/",$SQ_MATCH[2]);
                $LEN_SEQ=strlen($SQ_INFO['INPUT']['SEQ']);
                $SQ_R=$SQ_INFO['INPUT']['SEQ'];
                if (!$SQ_MATCH[3])$SQ_R=$SQ_MATCH[4];
                for ($I_SQ=$SHIFT_START;$I_SQ<=$LEN_SEQ;++$I_SQ)
                {
                    if ($I_SQ+$SQ_MATCH[0]>$MODULE_DATA['PARAMS']['RANGE']['END'])break;
                    if (in_array($I_SQ,$tab))$STR_MM.='<span style="color:red">'.substr($SQ_R,$I_SQ,1).'</span>';
                    else $STR_MM.=substr($SQ_R,$I_SQ,1);
                }
            }else $STR_MM='';
            $color='green';
            echo $STR_MM;
           // if (!$SQ_MATCH[3])$color='grey';
            
            if (isset($SQ_INFO['INPUT']['POTENCY']) &&$MODULE_DATA['POTENCY_RANGE']['MAX']-$MODULE_DATA['POTENCY_RANGE']['MIN']>0)
            {
                
                $BLOCK_ID=floor(($SQ_INFO['INPUT']['POTENCY']-$MODULE_DATA['POTENCY_RANGE']['MIN'])/($MODULE_DATA['POTENCY_RANGE']['MAX']-$MODULE_DATA['POTENCY_RANGE']['MIN'])*10);
                $color=$hexgroups[$BLOCK_ID];
                echo $BLOCK_ID."=>".$color;
                
            }
           // if ($SQ_INFO['INPUT']['NAME']=='3998887')exit;


            $FOUND_BLOCK=false;
            ///TExt AFTER:
            $ALL_RIGHT=$RIGHT+$LEN_TXT;
            if ($ALL_RIGHT<$MODULE_DATA['LENGTH'])
            {
                for ($IL=0;$IL<$N_LINE_ALL;++$IL)
                {
                    $VALID=true;
                    for ($JL=$LEFT;$JL<=$ALL_RIGHT;++$JL)
                    {
                        if ($LINES_STAT[$IL][$JL]){$VALID=false;break;}
                    }
                    if (!$VALID)continue;
                    echo "LINE : ".$SQ_INFO['INPUT']['NAME'].' '.$IL.' '.$LEFT.'-'.$ALL_RIGHT."\tRIGHT BLOCK WORKS\n";
                    for ($JL=$LEFT-1;$JL<=$ALL_RIGHT+1;++$JL)    $LINES_STAT[$IL][$JL]=true;

                   
                    $LINES[$IL][$LEFT]=' <div  class="transcript_seq_info" placeholder="'.$SQ_INFO['INPUT']['NAME'].'"
                        style="left:'.(($LEFT)*$ratio-2).'px;'.((!$SQ_MATCH[3])?'border:2px solid black':'').'; color:'.(($STR_MM!='|')?'black':$color).';padding-left:2px;font-size:1em;height:20px;font-weight:bold;background-color:'.$color.';border-radius:10px;
                        width:'.(($RIGHT-$LEFT+1)*$ratio).'px">'.$STR_MM.'</div>';
                    
                    $LINES[$IL][$RIGHT]=' <div  class="transcript_seq_info" 
                    style="font-size:0.95em ; left:'.(($RIGHT+1)*$ratio).'px; font-weight:bold;
                    width:'.(($LEN_TXT)*$ratio).'px"> '.$SQ_INFO['INPUT']['NAME'].'</div>';
                
                        $FOUND_BLOCK=true;
                    break;
                }
            }
            if ($FOUND_BLOCK)continue;
                /// Text Before:
                $FOUND_BLOCK=false;
                $ALL_LEFT=$LEFT-$LEN_TXT;
                if ($ALL_LEFT>0)
                {
                    for ($IL=0;$IL<$N_LINE_ALL;++$IL)
                    {
                        $VALID=true;
                        for ($JL=$ALL_LEFT;$JL<=$RIGHT;++$JL)
                        {
                            if ($LINES_STAT[$IL][$JL]){$VALID=false;break;}
                        }
                        if (!$VALID)continue;
        echo "LINE : ".$SQ_INFO['INPUT']['NAME'].' '.$IL.' '.$ALL_LEFT.'-'.$RIGHT."\tLEFT BLOCK WORKS\n";
                        for ($JL=$ALL_LEFT-1;$JL<=$RIGHT+1;++$JL)    $LINES_STAT[$IL][$JL]=true;
                        $LINES[$IL][$ALL_LEFT]=' <div  class="transcript_seq_info" 
                        style="font-size:1em ; left:'.(($ALL_LEFT)*$ratio).'px; font-weight:bold;
                        width:'.(($LEN_TXT)*$ratio).'px">'.$SQ_INFO['INPUT']['NAME'].' </div>';
                        $LINES[$IL][$LEFT]=' <div  class="transcript_seq_info" 
                            style="font-size:1em;left:'.(($LEFT)*$ratio-2).'px;padding-left:2px; color:'.(($STR_MM!='|')?'black':$color).';font-weight:bold;height:20px;background-color:'.$color.';border-radius:10px;
                            width:'.(($RIGHT-$LEFT+1)*$ratio).'px">'.$STR_MM.'</div>';
                            $FOUND_BLOCK=true;
                        break;
                    }
                }
                if ($FOUND_BLOCK)continue;
        
                    ///Text below
                    for ($IL=0;$IL<$N_LINE_ALL;++$IL)
                    {
                        $VALID=true;
                        for ($JL=$LEFT;$JL<=$RIGHT;++$JL)
                        {
                            if ($LINES_STAT[$IL][$JL]){$VALID=false;break;}
                        }
                        for ($JL=$LEFT;$JL<=$LEFT+$LEN_TXT;++$JL)
                        {
                            if ($LINES_STAT[$IL+1][$JL]){$VALID=false;break;}
                        }
                        if (!$VALID)continue;
                echo "LINE : ".$TXT.' '.$IL.' '.$LEFT.'-'.$ALL_RIGHT."\tBELOW BLOCK WORKS\n";
                        for ($JL=$LEFT-1;$JL<=$RIGHT+1;++$JL)    $LINES_STAT[$IL][$JL]=true;
                        for ($JL=$LEFT-1;$JL<=$LEFT+$LEN_TXT+1;++$JL)    $LINES_STAT[$IL+1][$JL]=true;
                        $LINES[$IL][$LEFT]=' <div  class="transcript_seq_info" placeholder="'.$SQ_INFO['INPUT']['NAME'].'"
                            style="left:'.(($LEFT)*$ratio-2).'px;padding-left:2px; color:'.(($STR_MM!='|')?'black':$color).';height:20px;font-weight:bold;background-color:'.$color.';border-radius:10px;
                            width:'.(($RIGHT-$LEFT+1)*$ratio).'px">'.$STR_MM.'</div>';
                        $LINES[$IL+1][$LEFT]=' <div  class="transcript_seq_info" 
                        style="font-size:0.95em ; left:'.(($LEFT)*$ratio).'px; font-weight:bold;
                        width:'.(($LEN_TXT)*$ratio).'px">'.$SQ_INFO['INPUT']['NAME'].'</div>';
                    
                            $FOUND_BLOCK=true;
                        break;
                    }
           // }
        }
    }
if ($HAS_MATCH)
{
    foreach ($LINES as $STR_L)
    {
        $STR_M=implode('',$STR_L);if ($STR_M=='')continue;
        $STR.='<div  style="float:left;width:100%;height:25px;">'.$STR_M.'</div>';
    }
   


changeValue("transcript_sel","MATCHES",$STR);
}else removeBlock("transcript_sel","W_MATCH");

}else removeBlock("transcript_sel","W_MATCH");







$DATA_LOG=array();
$N_LOG=0;
if (isset($MODULE_DATA['CLINVAR']))
{
    $RULE=array('Benign'=>0,
'Likely benign'=>0,                    
'Uncertain significance'=>1,
'Likely pathogenic'=>4,
'Pathogenic'=>4,          
'Likely pathogenic, low penetrance'=>4,          
'Pathogenic, low penetrance'=>4,                 
'Uncertain risk allele'=>1,                      
'Likely risk allele'=>2,                         
'Established risk allele'=>2,                    
'drug response'=>3,                              
'association'=>3,                                
'protective'=>5,                                 
'Affects'=>3,                                    
'conflicting data from submitters'=>1,           
'not provided'=>1,                        
'risk factor'=>2,                                
'confers sensitivity'=>2,                        
'histocompatibility'=>1,                         
'association not found'=>1,                      
'other'=>1,                                      
);
   
    $LINES=array();$LINES_STAT=array();
    $N_LINE_ALL=12;
    for($I=0;$I<$N_LINE_ALL;++$I)
    {
        $LINES[$I]=array();
        $LINES_STAT[$I]=array_fill(0,$MODULE_DATA['LENGTH'],false);
    }
    $LEFT=-1;
    

    foreach ($MODULE_DATA['SEQ'] as $CHR_POS=>&$TR_SEQ_INFO)
    {
        ++$LEFT;
       
        if (!isset($MODULE_DATA['CLINVAR'][$TR_SEQ_INFO['SEQ_POS']]))continue;
    foreach ($MODULE_DATA['CLINVAR'][$TR_SEQ_INFO['SEQ_POS']] as &$ENTRY)
    {
        $RIGHT=$LEFT+1;
        for ($IL=0;$IL<$N_LINE_ALL;++$IL)
            {
                $VALID=true;
                for ($JL=$LEFT;$JL<=$RIGHT;++$JL)
                {
                    if ($LINES_STAT[$IL][$JL]){$VALID=false;break;}
                }
                
                if (!$VALID)continue;
        
                for ($JL=$LEFT-1;$JL<=$RIGHT+1;++$JL)    $LINES_STAT[$IL][$JL]=true;
                $SCORES=array();
                foreach ($ENTRY['SUBMISSION'] as &$SUB)
                {
                    if (!isset($SCORES[$RULE[$SUB['CLIN_SIGN']]]))$SCORES[$RULE[$SUB['CLIN_SIGN']]]=1;
                    else $SCORES[$RULE[$SUB['CLIN_SIGN']]]++;
                }
                $BEST_S=0;$BEST_T=0;
                foreach ($SCORES as $S=>$V)
                {
                    if ($V>$BEST_S)
                    {
                        $BEST_S=$V;
                        $BEST_T=$S;
                    }
                }
               // print_r($SCORES);
                
                switch ($BEST_T)
                {
                    case 0:$color='blue';break;
                    case 1:$color='grey';break;
                    case 2:$color='orange';break;
                    case 3:$color='cyan';break;
                    case 4:$color='red';break;
                }
               // echo $BEST_S.' '.$BEST_T.' '.$color;
                $LINES[$IL][$LEFT]=' <div  class="seq  ttl_clv_'.$RANGE_TTL.' transcript_seq_info" title="A" data-pos="'.$N_LOG.'"  
                    style="left:'.(($LEFT)*$ratio).'px; color:'.$color.';font-weight:bold;background-color:'.$color.';border-radius:10px;
                    width:'.((1)*$ratio).'px">|</div>';
                   
                    $DATA_LOG[$N_LOG]='<h4>'.$ENTRY['INFO']['CLINICAL_VARIANT_NAME'].'</h4>
                    <table>
                    <tr>
                        <th>Review:</th>
                        <td>'.$ENTRY['INFO']['CLINVAR_REVIEW_STATUS_NAME'].'</td>
                    </tr>
                    <tr>
                    <th>Type:</th><td>'.$ENTRY['INFO']['CLINICAL_VARIANT_TYPE'].'</td></tr></table><br/><table class=\'table table-sm\'>'
                    .'<thead><tr><th>Submission ID</th><th>Collection</th><th>Submitter</th><th>Significance</th><th>Review Status</th></tr></thead><tbody>';
                   foreach ($ENTRY['SUBMISSION'] as &$SUB)
                   {
                       $DATA_LOG[$N_LOG].='<tr><td>'.$SUB['SCV_ID'].'</td><td>'
                       .$SUB['COLLECTION_METHOD'].'</td><td>'
                       .$SUB['SUBMITTER'].'</td><td>'
                       .$SUB['CLIN_SIGN'].'</td><td>'
                       .$SUB['CLINVAR_REVIEW_STATUS_NAME'].'</td></tr>';
           
                   }
                   $DATA_LOG[$N_LOG].='</tbody></table>';
                   $N_LOG++;
                    
                break;
            }
        
     
     

    }
}
    
//exit;
    changeValue("transcript_sel","TOOLTIPS_CLIN",str_replace("'","\\'",json_encode(str_replace("\n","",$DATA_LOG))));
    $STR='<div class=" ui-state-default seq scroll " id="transcript_sel" style="float:left;width:100%;margin-top:20px">
    <div style="display:block;font-family:auto;font-size:1em;width:${OVERALL_WIDTH}px;background-color:lightgrey">Clinical variant';
    $STR.=' <span style="color:blue">Benign</span> ; <span style="color:grey">Uncertain</span> ; <span style="color:orange">Some risk</span> ; <span style="color:cyan">Other</span> ; <span style="color:red">Pathogenic</span>  ';
    $STR.='</div>';
    foreach ($LINES as $STR_L)
        {
            $STR_M=implode('',$STR_L);if ($STR_M=='')continue;
            $STR.='<div  style="float:left;width:100%;height:20px;">'.$STR_M.'</div>';
        }
        
    changeValue("transcript_sel","CLINVAR",$STR.'</div>');
    

}


if( isset($MODULE_DATA['ORTHO']) )
{
    $STR='
    ';
    foreach ($MODULE_DATA['ORTHO']['ORTHO'] as &$ORTHO_GENE)
    {
        $STR_HEAD='<div class=" ui-state-default seq scroll " id="transcript_sel" style="float:left;width:100%;margin-top:20px">
        <div style="display:block;font-family:auto;font-size:1em;width:${OVERALL_WIDTH}px;background-color:lightgrey">';
$STR_HEAD.=$ORTHO_GENE['SCIENTIFIC_NAME'].' <a href="/GENE/'.$ORTHO_GENE['GENE_ID'].'" target="_blank">'.$ORTHO_GENE['SYMBOL'].'</a>';
$IS_FIRST=true;
$MATCHES=array();
        foreach ($MODULE_DATA['ORTHO']['ALIGNMENTS'] as &$AL_RECORD)
        {
            
            if ($AL_RECORD['INFO']['O_GN_ENTRY_ID']!=$ORTHO_GENE['GN_ENTRY_ID'])continue;
            if($AL_RECORD['ALIGNMENT']==null)continue;
           
            $START_POS=array_keys($AL_RECORD['ALIGNMENT'])[0];
            //echo '<pre>'.$ORTHO_GENE['SCIENTIFIC_NAME'].' '.$ORTHO_GENE['SYMBOL']."\n";
            $STR_PROT='';
            $STR_NUCL='';
            $MAPT=array();$MAPV=array();
            foreach ($MODULE_DATA['SEQ'] as &$TR_INFO)
            {

            
                $TR_POS=&$TR_INFO['SEQ_POS'];
                if (!isset($AL_RECORD['ALIGNMENT'][$TR_POS]))
                {
                    $STR_PROT.=' ';
                    $STR_NUCL.=' ';
                    $MAPT[]=false;
                    $MAPV[]=false;

                }
                else 
                {
                    $AL_POS=&$AL_RECORD['ALIGNMENT'][$TR_POS];
                  $MAPT[]= ( $AL_POS['C_PROT_AA']==$AL_POS['R_PROT_AA']);
                  $MAPV[]= ( $AL_POS['C_TR_NUCL']==$AL_POS['R_TR_NUCL']);
               
                if ($AL_POS['C_TR_TRIPLET_POS']==2) $STR_PROT.=$AL_POS['C_PROT_AA'];
                else                                $STR_PROT.='-';
                $STR_NUCL.=$AL_POS['C_TR_NUCL'];
                //echo $STR_PROT."\t".$STR_NUCL."\t".$AL_POS['C_TR_TRIPLET_POS']."\t|".( $AL_POS['C_PROT_AA']==$AL_POS['R_PROT_AA']).'|'.( $AL_POS['C_TR_NUCL']==$AL_POS['R_TR_NUCL'])."\n";
                }
            }
//echo count($MAPT).' '.count($MAPV)."\n";
            $CURR_V=$MAPT[0];
            $CURR_I=0;
            $NEW_STR='';
            for ($I=1;$I<count($MAPT);++$I)
            {
                if ($CURR_V!=$MAPT[$I])
                {
                    if ($CURR_V)$NEW_STR.='<span style="color:green">';
                    else $NEW_STR.='<span style="color:red;font-weight:bold">';
                    $NEW_STR.=substr($STR_PROT,$CURR_I,$I-$CURR_I).'</span>';
                    
                    $CURR_V=$MAPT[$I];
                    $CURR_I=$I;
                  //  echo '|'.$I.'| |'.$NEW_STR."|\t|".$CURR_I."|\t|".$CURR_V."|\n";
                }
            }
          //  echo "PREV:".$NEW_STR."\t".$CURR_V.' '.$CURR_I."\n";
            if ($CURR_V)$NEW_STR.='<span style="color:green">';
            else $NEW_STR.='<span style="color:red;font-weight:bold">';
            $NEW_STR.=substr($STR_PROT,$CURR_I,count($MAPT)-$CURR_I).'</span>';
         //   echo $NEW_STR."\n";
           // exit;
            $STR_PROT='<div class="seq" style="float:left;white-space:pre">'.$NEW_STR;


            $CURR_V=$MAPV[0];
            $CURR_I=0;
            $NEW_STR='';
            for ($I=1;$I<count($MAPV);++$I)
            {
                if ($CURR_V!=$MAPV[$I])
                {
                    if ($CURR_V)$NEW_STR.='<span style="color:green">';
                    else $NEW_STR.='<span style="color:red;font-weight:bold">';
                    $NEW_STR.=substr($STR_NUCL,$CURR_I,$I-$CURR_I).'</span>';
                    $CURR_V=$MAPV[$I];$CURR_I=$I;
                }
            }
            if ($CURR_V)$NEW_STR.='<span style="color:green">';
            else $NEW_STR.='<span style="color:red;font-weight:bold">';
            $NEW_STR.=substr($STR_NUCL,$CURR_I,count($MAPV)-$CURR_I).'</span>';
            
            $STR_NUCL='<div class="seq" style="float:left;white-space:pre">'.$NEW_STR;

            if ($IS_FIRST){$IS_FIRST=false;$STR.=$STR_HEAD;}
            
            $MATCHES[$STR_PROT.'</div>'.$STR_NUCL.'</div>'][$AL_RECORD['INFO']['O_ISO_ID'].'/'.$AL_RECORD['INFO']['O_ISO_NAME']][]=$AL_RECORD['INFO']['O_TRANSCRIPT_NAME'];
            

           // $STR.=$STR_PROT.'</div>'.$STR_NUCL.='</div>';
           
        }
        
        if (count($MATCHES)==1 )
        {
            if (count($MATCHES[array_key_first($MATCHES)])==1)
            {
                $STR.=' ';
                // foreach ($MATCHES as $DT=>$HEAD)
                // foreach ($HEAD as $PR=>&$TR)
                // {
                //     $STR.=implode(';',$TR).'>'.$PR;
                // }
                $STR.='</div>';
                
                $STR.=array_key_first($MATCHES);
               // $STR.='F-</div>';
            }
            else
            {
                $STR.='</div>';
                foreach ($MATCHES as $DT=>$HEAD)
                {
                    // foreach ($HEAD as $PR=>&$TR)
                    // {
                    //     $STR.='<div class="seq" style="float:left;font-family:auto;font-size:1em;width:${OVERALL_WIDTH}px;background-color:lightgrey">'.implode(';',$TR).'>'.$PR.'</div>';
                    // }
                    $STR.=$DT;
                }
                $STR.='</div>';
            }
            $STR.='</div>';
        }
        else
        {
            //$STR.='</div>';
            foreach ($MATCHES as $DT=>$HEAD)
            {
                $STR.='<div class="seq" style="float:left;font-family:auto;font-size:1em;width:${OVERALL_WIDTH}px;background-color:lightgrey">';
                // foreach ($HEAD as $PR=>&$TR)
                // {
                    
                    
                    
                    
                //     $STR.=implode(';',$TR).'>'.$PR;
                // }
                $STR.='</div>';
                
                $STR.=$DT;
            }
           // $STR.='S</div>';
        }
         
         
    }
    
    
    changeValue("transcript_sel",'ORTHO',$STR);
}
//exit;


if( isset($MODULE_DATA['SIM']))
{
    $STR='
    ';
    foreach ($MODULE_DATA['SIM']['GENES'] as &$SIM_GENE)
    {
        $STR_HEAD='<div class=" ui-state-default seq scroll " id="transcript_sel" style="float:left;width:100%;margin-top:20px">
        <div style="display:block;font-family:auto;font-size:1em;width:${OVERALL_WIDTH}px;background-color:lightgrey">';
$STR_HEAD.=$SIM_GENE['SCIENTIFIC_NAME'].' <a href="/GENEID/'.$SIM_GENE['GENE_ID'].'" target="_blank">'.$SIM_GENE['SYMBOL'].' </a>';

$IS_FIRST=true;
        $MATCHES=array();
        echo '<pre>'.$ORTHO_GENE['SCIENTIFIC_NAME'].' '.$ORTHO_GENE['SYMBOL']."\n";
        foreach ($MODULE_DATA['SIM']['ALIGNMENTS'] as &$AL_RECORD)
        {
            
            if ($AL_RECORD['INFO']['O_GN_ENTRY_ID']!=$SIM_GENE['GN_ENTRY_ID'])continue;
            if($AL_RECORD['ALIGNMENT']==null)continue;
           
            $START_POS=array_keys($AL_RECORD['ALIGNMENT'])[0];
            
            $STR_PROT='';
            $STR_NUCL='';
            $MAPT=array();$MAPV=array();
            // print_r($AL_RECORD['INFO']);
            // echo "\n\n\n".$SIM_GENE['SCIENTIFIC_NAME'].' <a href="/GENEID/'.$SIM_GENE['GENE_ID'].'" target="_blank">'.$SIM_GENE['SYMBOL'].' '.$AL_RECORD['INFO']['TRANSCRIPT_NAME'].' </a>'."\n";
            foreach ($MODULE_DATA['SEQ'] as &$TR_INFO)
            {

            
                $TR_POS=&$TR_INFO['SEQ_POS'];
                if (!isset($AL_RECORD['ALIGNMENT'][$TR_POS]))
                {
                    $STR_PROT.=' ';
                    $STR_NUCL.=' ';
                    $MAPT[]=false;
                    $MAPV[]=false;
                    echo "|N:".$TR_POS."\t|".$STR_PROT."|\t|".$STR_NUCL."|\t|".$AL_POS['C_TR_TRIPLET_POS']."|\t|".( $AL_POS['C_PROT_AA']==$AL_POS['R_PROT_AA']).'|'.( $AL_POS['C_TR_NUCL']==$AL_POS['R_TR_NUCL'])."|\n";
                }
                else 
                {
                    $AL_POS=&$AL_RECORD['ALIGNMENT'][$TR_POS];
               //     echo  $AL_POS['C_PROT_AA'].' '.$AL_POS['R_PROT_AA'].' '.$AL_POS['C_TR_NUCL'].' '.$AL_POS['R_TR_NUCL']."\t".( $AL_POS['C_PROT_AA']==$AL_POS['R_PROT_AA'])."\t".( $AL_POS['C_TR_NUCL']==$AL_POS['R_TR_NUCL'])."\n";
                  $MAPT[]= ( $AL_POS['C_PROT_AA']==$AL_POS['R_PROT_AA']);
                  $MAPV[]= ( $AL_POS['C_TR_NUCL']==$AL_POS['R_TR_NUCL']);
               
                if ($AL_POS['C_TR_TRIPLET_POS']==2) $STR_PROT.=$AL_POS['C_PROT_AA'];
                else                                $STR_PROT.='-';
                $STR_NUCL.=$AL_POS['C_TR_NUCL'];
                echo "|W:".$TR_POS."\t|".$STR_PROT."|\t|".$STR_NUCL."|\t|".$AL_POS['C_TR_TRIPLET_POS']."|\t|".( $AL_POS['C_PROT_AA']==$AL_POS['R_PROT_AA']).'|'.( $AL_POS['C_TR_NUCL']==$AL_POS['R_TR_NUCL'])."|\n";
                }
            }

            $CURR_V=$MAPT[0];
            $CURR_I=0;
            $NEW_STR='';
            for ($I=1;$I<count($MAPT);++$I)
            {
                if ($CURR_V!=$MAPT[$I])
                {
                    if ($CURR_V)$NEW_STR.='<span style="color:green">';
                    else $NEW_STR.='<span style="color:red;font-weight:bold">';
                    $NEW_STR.=substr($STR_PROT,$CURR_I,$I-$CURR_I).'</span>';
                    $CURR_V=$MAPT[$I];$CURR_I=$I;
                    echo '|'.$I.'| |'.$NEW_STR."|\t|".$CURR_I."|\t|".$CURR_V."|\n";
                }
            }
            if ($CURR_V)$NEW_STR.='<span style="color:green">';
            else $NEW_STR.='<span style="color:red;font-weight:bold">';
            $NEW_STR.=substr($STR_PROT,$CURR_I,count($MAPT)-$CURR_I).'</span>';
            echo $CURR_V.' '.$CURR_I."\n";
            echo "FINAL".$NEW_STR."\n";
            $STR_PROT='<div class="seq" style="float:left;white-space:pre;font-family: Courier New !important;">'.$NEW_STR;


            $CURR_V=$MAPV[0];
            $CURR_I=0;
            $NEW_STR='';
            for ($I=1;$I<count($MAPV);++$I)
            {
                if ($CURR_V!=$MAPV[$I])
                {
                    if ($CURR_V)$NEW_STR.='<span style="color:green">';
                    else $NEW_STR.='<span style="color:red;font-weight:bold">';
                    $NEW_STR.=substr($STR_NUCL,$CURR_I,$I-$CURR_I).'</span>';
                    $CURR_V=$MAPV[$I];$CURR_I=$I;
                }
            }
            if ($CURR_V)$NEW_STR.='<span style="color:green">';
            else $NEW_STR.='<span style="color:red;font-weight:bold">';
            $NEW_STR.=substr($STR_NUCL,$CURR_I,count($MAPV)-$CURR_I).'</span>';
            
            $STR_NUCL='<div class="seq" style="float:left;white-space:pre;font-family: Courier New !important;">'.$NEW_STR;
//             echo implode("",$MAPT)."\n";
// echo $STR_PROT.'<br/>'.$STR_NUCL."\n";
// echo implode("",$MAPV)."\n\n\n\n\n\n";
            //if ($IS_P_OPEN)$STR_PROT.='</span>';
            //if ($IS_N_OPEN)$STR_NUCL.='</span>';
            if ($IS_FIRST){$IS_FIRST=false;$STR.=$STR_HEAD;}
             $MATCHES[$STR_PROT.'</div>'.$STR_NUCL.'</div>'][$AL_RECORD['INFO']['O_ISO_ID'].'/'.$AL_RECORD['INFO']['O_ISO_NAME']][]=$AL_RECORD['INFO']['O_TRANSCRIPT_NAME'];
           
        }
        if (count($MATCHES)==1 )
        {
            if (count($MATCHES[array_key_first($MATCHES)])==1)
            {
                $STR.=' ';
                // foreach ($MATCHES as $DT=>$HEAD)
                // foreach ($HEAD as $PR=>&$TR)
                // {
                //     $STR.=implode(';',$TR).'>'.$PR;
                // }
                 $STR.='</div>';
                 $STR.=array_key_first($MATCHES);
               // $STR.='F-</div>';
            }
            else
            {
                $STR.='</div>';
                foreach ($MATCHES as $DT=>$HEAD)
                {
                    // foreach ($HEAD as $PR=>&$TR)
                    // {
                    //     $STR.='<div class="seq" style="float:left;font-family:auto;font-size:1em;width:${OVERALL_WIDTH}px;background-color:lightgrey">'.implode(';',$TR).'>E-'.$PR.'</div>';
                    // }
                    $STR.=$DT;
                }
              //  $STR.='J-</div>';
            }
            $STR.='</div>';
        }
        else
        {
            //$STR.='</div>';
            foreach ($MATCHES as $DT=>$HEAD)
            {
                $STR.='<div class="seq" style="float:left;font-family:auto;font-size:1em;width:${OVERALL_WIDTH}px;background-color:lightgrey">';
                // foreach ($HEAD as $PR=>&$TR)
                // {
                    
                    
                    
                    
                //     $STR.=implode(';',$TR).'>'.$PR;
                // }
               $STR.='</div>';
                
                $STR.=$DT;
            }
           // $STR.='S</div>';
        }
    }
    changeValue("transcript_sel",'SIM',$STR);
}
//exit;
if (isset($MODULE_DATA['PROT']))
{

    $STR='';
    $STR2='';$STR2_OFFSET=0;
    $SHIFT_PROT=0;$HAS_PROT=false;
    foreach ($MODULE_DATA['PROT']['TRANSLATION'] as &$TRANS)
    {
        $FIRST=true;
        $MAP_POS=array();$CURR_PROT_ID=-1;
        $STR.='${HEADER}<div style="white-space:pre;font-family: Courier New !important;position:relative;left:${SHIFT}px">';
        foreach ($MODULE_DATA['SEQ'] as &$TR_INFO)
        {

            
            $TR_ID=&$TR_INFO['TRID'];
            if (!isset($TRANS[$TR_ID])){
                if ($HAS_PROT) {$STR.=' ';$MAP_POS[$TR_ID]='';}
                else $SHIFT_PROT++;
            }
            else 
            {
                $HAS_PROT=true;
                $TRANS_POS=&$TRANS[$TR_ID];
                $CURR_PROT_ID=$TRANS_POS[0];
                $MAP_POS[$TR_ID]=$TRANS_POS[1];
                
                
                if ($TRANS_POS[1]%5==0)
                    {
                        if ($TRANS_POS[2]==2)
                        {
                            $STR2.=$TRANS_POS[1];
                            $STR2_OFFSET=strlen($TRANS_POS[1])-1;
                        }
                        else if ($STR2_OFFSET>0)$STR2_OFFSET--;
                        else $STR2.=' ';
                    }else if ($STR2_OFFSET>0)$STR2_OFFSET--;
                    else $STR2.=' ';
                        

                if ($TRANS_POS[2]==1)
                {
                    $STR.='-';
                }
                else if ($TRANS_POS[2]==2)
                {
                    $STR.=$MODULE_DATA['PROT']['PROT'][$TRANS_POS[0]]['SEQ'][$TRANS_POS[1]]['LETTER'];
                    
                }
                else if ($TRANS_POS[2]==3)
                {
                    if ($FIRST)
                    {
                        $STR.=$MODULE_DATA['PROT']['PROT'][$TRANS_POS[0]]['SEQ'][$TRANS_POS[1]]['LETTER'];
                    }
                    else $STR.='-';
                }
            }
            $FIRST=false;
        }
        $STR=str_replace('${SHIFT}',$SHIFT_PROT*$ratio,$STR);
        $STR=str_replace('${HEADER}','<div style="display:block;font-family:auto;font-size:0.75em;position:relative;left:'.$SHIFT_PROT*$ratio.'px">'.$MODULE_DATA['PROT']['PROT'][$CURR_PROT_ID]['INFO']['ISO_ID'].' '.$MODULE_DATA['PROT']['PROT'][$CURR_PROT_ID]['INFO']['DESCRIPTION'].'</div>',$STR).'</div>';
        $STR.='<div class="seq" style="white-space:pre;font-family: Courier New !important;position: relative;top: -6px;left:'.$SHIFT_PROT*$ratio.'px">'.$STR2.'</div>';
        
        echo '<pre>';
        if (!isset($MODULE_DATA['PROT']['PROT'][$CURR_PROT_ID]['PROT_INFO']))continue;
        $PROT_INFO=&$MODULE_DATA['PROT']['PROT'][$CURR_PROT_ID]['PROT_INFO'];
        $FOLD=array();
        foreach ($PROT_INFO as $K=>&$PS)
        {
            if (!($PS['FEAT_NAME']=='Helix'||$PS['FEAT_NAME']=='Beta strand'||$PS['FEAT_NAME']=='Turn'))continue;
            $FOLD[]=$PS;
        }

        if ($FOLD!=array())
        {
            $STR.='
            <div style="display:block;font-family:auto;font-size:0.75em">3-D fold (<span style="color:red">Helix</span> ; <span style="color:blue">Turn</span> ; <span style="color:orange">Beta strand</span>) </div>
            <div  style="float:left">';
            foreach ($FOLD as &$PS)
            {
                $START_POS=$PS['START_POS'];
                $END_POS=$PS['END_POS'];
                $LEFT=0;$RIGHT=0;
                $N=-1;$START=false;
                foreach ($MAP_POS as $PROT_POS)
                {
                    ++$N;
                  //  echo $N.' '.$START_POS.' '.$END_POS.' '.$PROT_POS;
                    if ($PROT_POS==$START_POS && !$START){$LEFT=$N;$START=true; }
                    if ($START)
                    {
                        if ($PROT_POS>$END_POS)$START=false;
                        else $RIGHT=$N;
                    }
                }
                    $color='';
                    if ($PS['FEAT_NAME']=='Helix')$color='red';
                    if ($PS['FEAT_NAME']=='Turn')$color='blue';
                    if ($PS['FEAT_NAME']=='Beta strand')$color='orange';
                    $STR.='<div  class="transcript_seq_info" 
                    style="left:'.(($SHIFT_PROT+$LEFT)*$ratio).'px; font-weight:bold;background-color:'.$color.';color:'.$color.';border-radius:10px;
                    width:'.(($RIGHT-$LEFT+1)*$ratio).'px">|</div>';    
                

                
                

            }
            $STR.='</div><br/>';
        }

        $LINES=array();$LINES_STAT=array();
        $N_LINE_ALL=12;
        for($I=0;$I<$N_LINE_ALL;++$I)
        {
            $LINES[$I]=array();
            $LINES_STAT[$I]=array_fill(0,$MODULE_DATA['LENGTH']-$SHIFT_PROT,false);
        }
        foreach ($PROT_INFO as &$PS)
        {
             if ($PS['FEAT_NAME']=='Helix'||$PS['FEAT_NAME']=='Beta strand'||$PS['FEAT_NAME']=='Turn')continue;
            $START_POS=$PS['START_POS'];
            $END_POS=$PS['END_POS'];

            ///Get range
            $LEFT=0;$RIGHT=0;
            $N=-1;$START=false;
            
            foreach ($MAP_POS as $PROT_POS)
            {
                if ($PROT_POS=='')continue;
                ++$N;
            
                if ($PROT_POS==$START_POS && !$START){$LEFT=$N;$START=true; }
                if ($START)
                {
                    if ($PROT_POS>$END_POS)$START=false;
                    else $RIGHT=$N;
                }
            }
            if ($LEFT==0&&$RIGHT==0)$RIGHT=1;
            $TXT=$PS['FEAT_NAME'].':'.$PS['FEAT_VALUE'];
            
            $LEN_TXT=strlen($TXT);
            $LEN_BLOCK=$RIGHT-$LEFT+1;

            /// 4 situations. Text before, text after, text within, text on two lines
            //Text within
            if ($LEN_TXT<$LEN_BLOCK)
            {
                for ($IL=0;$IL<10;++$IL)
                {
                    $VALID=true;
                    for ($JL=$LEFT;$JL<=$RIGHT;++$JL)
                    {
                        if ($LINES_STAT[$IL][$JL]){$VALID=false;break;}
                    }
                    if (!$VALID)continue;
echo "LINE : ".$IL.' '.$LEFT.'-'.$RIGHT;
                    for ($JL=$LEFT-1;$JL<=$RIGHT+1;++$JL)    $LINES_STAT[$IL][$JL]=true;
                    $LINES[$IL][$LEFT]=' <div  class="transcript_seq_info" 
                          style="font-size:0.95em ; left:'.(($SHIFT_PROT+$LEFT)*$ratio).'px; font-weight:bold;padding-left:10px;background-color:green;border-radius:10px;color:white;
                          width:'.(($RIGHT-$LEFT+1)*$ratio).'px">'.$TXT.'</div>';
                    break;
                }
            }
            else
            {
                
                /// Text Before:
                $FOUND_BLOCK=false;
                $ALL_LEFT=$LEFT-$LEN_TXT;
                if ($ALL_LEFT>0)
                {
                    for ($IL=0;$IL<$N_LINE_ALL;++$IL)
                    {
                        $VALID=true;
                        for ($JL=$ALL_LEFT;$JL<=$RIGHT;++$JL)
                        {
                            if ($LINES_STAT[$IL][$JL]){$VALID=false;break;}
                        }
                        if (!$VALID)continue;
    echo "LINE : ".$TXT.' '.$IL.' '.$ALL_LEFT.'-'.$RIGHT."\tLEFT BLOCK WORKS\n";
                        for ($JL=$ALL_LEFT-1;$JL<=$RIGHT+1;++$JL)    $LINES_STAT[$IL][$JL]=true;
                        $LINES[$IL][$ALL_LEFT]=' <div  class="transcript_seq_info" 
                        style="font-size:0.95em ; left:'.(($SHIFT_PROT+$ALL_LEFT)*$ratio).'px; font-weight:bold;
                        width:'.(($LEN_TXT)*$ratio).'px">'.$TXT.'</div>';
                        $LINES[$IL][$LEFT]=' <div  class="transcript_seq_info" 
                            style="left:'.(($SHIFT_PROT+$LEFT)*$ratio).'px; color:green;font-weight:bold;padding-left:10px;background-color:green;border-radius:10px;
                            width:'.(($RIGHT-$LEFT+1)*$ratio).'px">|</div>';
                            $FOUND_BLOCK=true;
                        break;
                    }
                }
                if ($FOUND_BLOCK)continue;
///TExt AFTER:
                $ALL_RIGHT=$RIGHT+$LEN_TXT;
                if ($ALL_RIGHT<$MODULE_DATA['LENGTH'])
                {
                    for ($IL=0;$IL<$N_LINE_ALL;++$IL)
                    {
                        $VALID=true;
                        for ($JL=$LEFT;$JL<=$ALL_RIGHT;++$JL)
                        {
                            if ($LINES_STAT[$IL][$JL]){$VALID=false;break;}
                        }
                        if (!$VALID)continue;
                echo "LINE : ".$TXT.' '.$IL.' '.$LEFT.'-'.$ALL_RIGHT."\tRIGHT BLOCK WORKS\n";
                        for ($JL=$LEFT-1;$JL<=$ALL_RIGHT+1;++$JL)    $LINES_STAT[$IL][$JL]=true;
                        $LINES[$IL][$LEFT]=' <div  class="transcript_seq_info" placeholder="'.$TXT.'"
                            style="left:'.(($SHIFT_PROT+$LEFT)*$ratio).'px; color:green;font-weight:bold;padding-left:10px;background-color:green;border-radius:10px;
                            width:'.(($RIGHT-$LEFT+1)*$ratio).'px">|</div>';
                        $LINES[$IL][$RIGHT]=' <div  class="transcript_seq_info" 
                        style="font-size:0.95em ; left:'.(($SHIFT_PROT+$RIGHT+1)*$ratio).'px; font-weight:bold;
                        width:'.(($LEN_TXT)*$ratio).'px">'.$TXT.'</div>';
                       
                            $FOUND_BLOCK=true;
                        break;
                    }
                }
                if ($FOUND_BLOCK)continue;
                    ///Text below
                    for ($IL=0;$IL<$N_LINE_ALL;++$IL)
                    {
                        $VALID=true;
                        for ($JL=$LEFT;$JL<=$RIGHT;++$JL)
                        {
                            if ($LINES_STAT[$IL][$JL]){$VALID=false;break;}
                        }
                        for ($JL=$LEFT;$JL<=$LEFT+$LEN_TXT;++$JL)
                        {
                            if ($LINES_STAT[$IL+1][$JL]){$VALID=false;break;}
                        }
                        if (!$VALID)continue;
                echo "LINE : ".$TXT.' '.$IL.' '.$LEFT.'-'.$ALL_RIGHT."\tBELOW BLOCK WORKS\n";
                        for ($JL=$LEFT-1;$JL<=$RIGHT+1;++$JL)    $LINES_STAT[$IL][$JL]=true;
                        for ($JL=$LEFT-1;$JL<=$LEFT+$LEN_TXT+1;++$JL)    $LINES_STAT[$IL+1][$JL]=true;
                        $LINES[$IL][$LEFT]=' <div  class="transcript_seq_info" placeholder="'.$TXT.'"
                            style="left:'.(($SHIFT_PROT+$LEFT)*$ratio).'px; color:green;font-weight:bold;padding-left:10px;background-color:green;border-radius:10px;
                            width:'.(($RIGHT-$LEFT+1)*$ratio).'px">|</div>';
                        $LINES[$IL+1][$LEFT]=' <div  class="transcript_seq_info" 
                        style="font-size:0.95em ; left:'.(($SHIFT_PROT+$LEFT)*$ratio).'px; font-weight:bold;
                        width:'.(($LEN_TXT)*$ratio).'px">'.$TXT.'</div>';
                       
                            $FOUND_BLOCK=true;
                        break;
                    }
            }
            

        }

       
        foreach ($LINES as $STR_L)
        {
            $STR_M=implode('',$STR_L);if ($STR_M=='')continue;
            $STR.='<div  style="float:left;width:100%;height:20px;">'.$STR_M.'</div>';
        }
       
    }
    
  
    changeValue("transcript_sel","PROT",$STR);
}


$DATA_LOG=array();
$N_LOG=0;
if (isset($MODULE_DATA['MUTS']))
{
    $FGPS=array();
    $FGP_STR=array();
    $SCORES=array();
    changeValue("transcript_sel","WIDTH_MUT",$ratio*$MODULE_DATA['LENGTH']+20);

    
    foreach ($MODULE_DATA['MUTS']['SEQ'] as $STUDY_ID=>&$LIST_MUTS)
    {
        $STR='<div style="display:block;width:'.$WIDTH_ALL.'px;height:20px;margin-bottom:2px">';
        $FGP='';$SCORE=0;$N_EMPTY=0;
        foreach ($MODULE_DATA['SEQ'] as &$ENTRY)
        {
            if (!isset($LIST_MUTS[$ENTRY['SEQ_POS']]))
            {
                ++$N_EMPTY;
                
                $FGP.="\t|";
            }
            else 
            {
                if ($N_EMPTY>0)
                {
                    $STR.='<div class="blk_V" style="width:'.($N_EMPTY*$ratio).'px;"></div>';
                    $N_EMPTY=0;
                }
                $E=&$LIST_MUTS[$ENTRY['SEQ_POS']];
                $E=unique_matrix($E);
                
                
                $REF_ALL='';
                if (isset($ENTRY['T']))$REF_ALL.=$ENTRY['T'];
	            else $REF_ALL.=$ENTRY['P'];
               $STR2='';
                $STR_LOG='<span style=\'font-weight:bold\'>Current allele:</span> '.$REF_ALL.'<br/><table class=\'table table-sm\'><tr><th class=\'boldright\'>Allele</th><th>N</th><th>Tot</th><th>Freq</th></tr>';
                $CURR_PERC=0;$MAX_PERC=0;$MAJOR='';$FIRST=true;
                foreach ($E as &$V_I)
                {
                    $PERC=0;

                    if ($V_I['ALT_COUNT']>0)
                    $PERC=$V_I['REF_COUNT']/$V_I['ALT_COUNT']*100;
                    
                    $STR_LOG.='<tr><td>'.(($MODULE_DATA['INFO']['STRAND']=='+')?$V_I['VARIANT_SEQ']:getReverse($V_I['VARIANT_SEQ'])).'</td><td>'.$V_I['REF_COUNT'].'</td><td>'.$V_I['ALT_COUNT'].'</td><td>'.round($PERC,2).'%</td></tr>';

                    $FGP.="\t".floor($PERC/5)*5;
                    
                // echo $ENTRY['SEQ_POS'].' ' .$PERC;
                
                    if ($V_I['VARIANT_SEQ']==(($MODULE_DATA['INFO']['STRAND']=='+')?$REF_ALL:getReverse($REF_ALL)))
                    {
                        if ($PERC>$MAX_PERC){$MAX_PERC=$PERC;$MAJOR='';}
                        $COLOR='green';
                    }
                    else 
                    {
                        if ($PERC>$MAX_PERC){$MAX_PERC=$PERC;if (strlen($V_I['VARIANT_SEQ'])==1)$MAJOR=$V_I['VARIANT_SEQ'];}
                        $SCORE+=floor($PERC/5)*5;
                    $COLOR='black';
                    switch ($V_I['VARIANT_SEQ'])
                    {
                        case 'A':$COLOR="purple";break;
                        case 'T':$COLOR="orange";break;
                        case 'C':$COLOR="blue";break;
                        case 'G':$COLOR="red";break;
                    }
                }
                    $STR2.= ','.$COLOR;
                    if ($CURR_PERC!=0 ||!$FIRST) $STR2.= ' '.round($CURR_PERC,2).'%';
                    $FIRST=false;
                    $STR2.= ', '.$COLOR.' '.round($CURR_PERC+$PERC,2).'%';
                    $CURR_PERC+=$PERC;
                    

                    
                }
                $STR_LOG.='</table>';
                $N_POS=-1;
                if (isset($DATA_LOG[$STR_LOG]))$N_POS=$DATA_LOG[$STR_LOG];
                else {
                    ++$N_LOG;$N_POS=$N_LOG;$DATA_LOG[$STR_LOG]=$N_POS;
                }
                $STR.= '<div class="blk_V ttl_'.$RANGE_TTL.'" title="A" data-pos="'.$N_POS.'"  style="color:white;font-weight:bold;width:'.$ratio.'px; background: -webkit-linear-gradient(bottom'.$STR2;
                $STR.=')">'.$MAJOR.'</div>';

                // grey, grey 40%, white 40%, white);
                // $STR.='<div class="blk_V" style="width:'.$ratio.'px;">';
                // foreach ($E as &$V_I)

                // {
                //     $PERC=0;

                //     if ($V_I['ALT_COUNT']>0)
                //     $PERC=$V_I['REF_COUNT']/$V_I['ALT_COUNT']*100;
                    
                //     $FGP.="\t".floor($PERC/5)*5;
                // // echo $ENTRY['SEQ_POS'].' ' .$PERC;
                //     if ($V_I['VARIANT_SEQ']==$REF_ALL)$COLOR='green';
                //     else 
                //     {
                //         $SCORE+=floor($PERC/5)*5;
                //     $COLOR='black';
                //     switch ($V_I['VARIANT_SEQ'])
                //     {
                //         case 'A':$COLOR="purple";break;
                //         case 'T':$COLOR="orange";break;
                //         case 'C':$COLOR="blue";break;
                //         case 'G':$COLOR="red";break;
                //     }
                // }
                // $STR.='<div class="blk_V" style="width:'.$ratio.'px;height:'.$PERC.'%; background-color:'.$COLOR.'"></div>';
            
            
          // $STR.= '</div>';
            }
        }
        $STR.='</div>';
        $SCORES[$SCORE][$FGP]=true;
        $FGPS[$FGP][]=$STUDY_ID;
        $FGP_STR[$FGP]=$STR;
       // $STR.=' <div style="float:left;font-family:auto">'.$MODULE_DATA['MUTS']['STUDY'][$STUDY_ID]['SHORT_NAME'].'</div><br/>';
    }
    
    

krsort($SCORES);
$STR='';
foreach ($SCORES as $SCORE=>&$FGP_LIST)
{
foreach ($FGP_LIST as $FGP=>&$DUMMY)
{

    $STUDIES=&$FGPS[$FGP];
    $STR.=' <div style="display:block;font-family:auto;font-size:0.75em">';
    if (count($STUDIES))
    {
    foreach ($STUDIES as $SU)$STR.=$MODULE_DATA['MUTS']['STUDY'][$SU]['SHORT_NAME'].' ; ';
    $STR=substr($STR,0,-3);
    }
    $STR.=' '.round($SCORE/$MODULE_DATA['LENGTH'],3).'% variability</div>';
    $STR.=$FGP_STR[$FGP];

}
}
$STR.='<div style="display:block;width:'.$WIDTH_ALL.'px;height:20px;margin-bottom:2px">
<div class="blk_V" style="width:100px;height:100%; font-weight:bold">Legend:</div> '.
'<div class="blk_V" style="width:'.($ratio*4).'px;height:100%; background-color:green;margin-left:10px;color:white;">Self</div>'.
'<div class="blk_V" style="width:'.$ratio.'px;height:100%; background-color:purple;margin-left:10px;color:white;">A</div>'.
'<div class="blk_V" style="width:'.$ratio.'px;height:100%; background-color:orange;margin-left:10px;color:white;">T</div>'.
'<div class="blk_V" style="width:'.$ratio.'px;height:100%; background-color:blue;margin-left:10px;color:white;">C</div>'.
'<div class="blk_V" style="width:'.$ratio.'px;height:100%; background-color:red;margin-left:10px;color:white;">G</div>'.
'<div class="blk_V" style="width:'.($ratio*5).'px;height:100%; background-color:black;margin-left:10px;color:white;">Other</div></div>';

changeValue("transcript_sel","TOOLTIPS",str_replace("'","\\'",json_encode(str_replace("\n","",array_flip($DATA_LOG)))));

changeValue("transcript_sel","MUTS",$STR);
}else removeBlock("transcript_sel","W_MUT");


if ($MODULE_DATA['PARAMS']['WITH_HEADER'])
{
    $STR='<div>Simplified view:</div><div style="height:16px;float:left;width:100%;"><div style="position:relative" >';
    changeValue("transcript_sel","LEN_SEQ",$MODULE_DATA['TR_BLOCK']['LEN']);
    $OPTIONS='';
    if ($MODULE_DATA['PARAMS']['WITH_PROTEIN'])$OPTIONS.='/WITH_PROTEIN';
    if ($MODULE_DATA['PARAMS']['WITH_MUTATION'])$OPTIONS.='/WITH_MUTATION';
    changeValue("transcript_sel","OPTIONS",$OPTIONS);

    $sum=0;
    
    foreach ($MODULE_DATA['TR_BLOCK']['EXONS'] as $EXON_ID=>&$INFO_EXON)
    {
        if ($EXON_ID=='')continue;
        $LEFT=round(($INFO_EXON['MIN']-1)*100/$MODULE_DATA['TR_BLOCK']['LEN'],4);
        $WIDTH=round(($INFO_EXON['MAX']-1)*100/$MODULE_DATA['TR_BLOCK']['LEN']-$LEFT,4);
        $STR.="<div  class=' ";
        $STR.='exon_even_sim';
        $STR.="' style='font-size: 0.7em;font-style: italic;position:absolute;display: inline-table;left:".$LEFT."%;width:".$WIDTH."%;'></div>";
        
    }
	
	//console.log(sum);
	$STR.='</div></div><div style="height:16px;float:left;width:100%"><div style="position:relative;">';
	foreach ($MODULE_DATA['TR_BLOCK']['POS_TYPE'] as &$INFO_POS)
	{
			
			$LEFT=round(($INFO_POS['MIN']-1)*100/$MODULE_DATA['TR_BLOCK']['LEN'],4);
			$WIDTH=round((($INFO_POS['MAX']-1)*100/$MODULE_DATA['TR_BLOCK']['LEN'])-$LEFT,4);
			$STR.="<div  class='";
			if ($INFO_POS['TYPE']=="5'UTR"||$INFO_POS['TYPE']=="3'UTR"||$INFO_POS['TYPE']=="3'UTR-INFERRED"|| $INFO_POS['TYPE']=="5'UTR-INFERRED") $STR.="trsq_UTR_view";
			else if ($INFO_POS['TYPE']=='CDS'||$INFO_POS['TYPE']=="CDS-INFERRED")$STR.='trsq_CDS_view';
			else if ($INFO_POS['TYPE']=='non-coded'||$INFO_POS['TYPE']=="non-coded-INFERRED")$STR.='trsq_nc_view';
			else if ($INFO_POS['TYPE']=='poly-A'||$INFO_POS['TYPE']=="unknown")$STR.='trsq_unk';

			$STR.="' style='font-size: 0.7em;font-style: italic;position:absolute;display: inline-table;left:".$LEFT."%;width:".$WIDTH."%;";
			
			
			$STR.="'>";
			if ($INFO_POS['TYPE']=="3'UTR-INFERRED"|| $INFO_POS['TYPE']=="5'UTR-INFERRED") $STR.="</div>";
			else $STR.=$INFO_POS['TYPE']."</div>";
	}
	$STR.='</div></div>';
    $LEFT=round(($RANGE_RNA['START']-1)*100/$MODULE_DATA['TR_BLOCK']['LEN'],4);
    $WIDTH=round(($RANGE_RNA['END']-1)*100/$MODULE_DATA['TR_BLOCK']['LEN']-$LEFT,4);
    $STR.='<div  id="area_sel" style="position:absolute;left:'.$LEFT.'%;width:'.$WIDTH.'%;border:3px solid black;height:35px"></div>';


    changeValue("transcript_sel","HEADER",$STR);

}else removeBlock("transcript_sel","W_HEAD");
changeValue("transcript_sel","OVERALL_WIDTH",$WIDTH_ALL);
?>