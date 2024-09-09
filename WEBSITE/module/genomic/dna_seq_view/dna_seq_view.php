<?php

if (!defined("BIORELS")) header("Location:/");

$LINES=array();
$COVERS=array();
$MAX_LINE=0;
echo '<pre>';
for ($I=0;$I<10;++$I)
for($J=0;$J<=count($MODULE_DATA['DNA_SEQUENCE']);++$J)$COVERS[$I][$J]=false;


$REV=array('A'=>'T','T'=>'A','C'=>'G','G'=>'C');
$STR_L='';$STR_L2='';$OFFSET=0;
	$STR='';
	$STR2='';
	$START=-1;
	foreach ($MODULE_DATA['DNA_SEQUENCE'] as &$CHR_POS)
	{
		if ($CHR_POS['CHR_POS']%10==0){$STR_L2.='|';$STR_L.=$CHR_POS['CHR_POS'];$OFFSET=strlen($CHR_POS['CHR_POS'])-1;}
		else if ($OFFSET>0){$OFFSET--;$STR_L2.=' ';}
		else {$STR_L.=' ';$STR_L2.=' ';}
		$START++;
		if ($CHR_POS['CHR_POS']!=$PARAMS['POSITION']){
			$STR.=$CHR_POS['NUCL'];
			$STR2.=$REV[strtoupper($CHR_POS['NUCL'])];
		}else
		{
			$STR.='<span style="font-weight:bold;background-color:black;color:white;">'.$CHR_POS['NUCL'].'</span>';
			$STR2.='<span style="font-weight:bold;background-color:black;color:white;">'.$REV[strtoupper($CHR_POS['NUCL'])].'</span>';
		}
		if (!isset($CHR_POS['VARIANT']))continue;
		foreach ($CHR_POS['VARIANT'] as &$VAR)
		{
			if ($VAR['REF_ALL']==$VAR['ALT_ALL'])continue;
			$TITLE="|-><a target='_blank' href='/VARIANT/".$VAR['RSID']."'>".$VAR['REF_ALL'].'>'.$VAR['ALT_ALL'].'</a>';
			$HTML_TITLE="|->".$VAR['REF_ALL'].'>'.$VAR['ALT_ALL'];
			$LEN=strlen($HTML_TITLE);
			$LINE=-1;
			
			for ($I=0;$I<10;++$I)
			{
				$VALID=true;
				
				for ($J=floor($START)-1;$J<=ceil($START+$LEN)+1;++$J)
				{
					if (isset($COVERS[$I][$J]) && $COVERS[$I][$J]){$VALID=false;break;}
				}
				if (!$VALID)continue;
				
				for ($J=floor($START)-1;$J<=ceil($START+$LEN)+1;++$J)
				{
					if (isset($COVERS[$I][$J]))$COVERS[$I][$J]=true;
				}
				$LINES[$I][$START]=array($TITLE,$LEN);
				$MAX_LINE=max($MAX_LINE,$I);
				break;
			}
		}
	}

	// for ($I=0;$I<10;++$I)
	// {
	// 	echo "\n";
	// 	for($J=0;$J<=100;++$J)
	// 	echo ($COVERS[$I][$J])?"1":"0";
	// 	echo "\n";
	// }
	// exit;

	
	$STR_LINES='';
	for ($I=0;$I<=$MAX_LINE;++$I)
	{
		$STR_LINES.="\n";
		for ($J=0;$J<=count($MODULE_DATA['DNA_SEQUENCE']);++$J)
		{
			if (!isset($LINES[$I][$J]))$STR_LINES.=' ';
			else {$STR_LINES.=$LINES[$I][$J][0];$J+=$LINES[$I][$J][1]-1;}
		}
		

	}
	$STR_ALL=$STR_L."\n".$STR_L2."\n".$STR."\n".$STR2.$STR_LINES;

	changeValue("dna_seq_view",'DNA_SEQ',$STR_ALL);
	$STR_ALL='';
	$RANGE=13.81;
	$CURR_T='';$START_T=0;$N=0;
	if (isset($MODULE_DATA['TRANSCRIPT']))
	{
		//echo "TRANSCRIPT";
		foreach ($MODULE_DATA['TRANSCRIPT'] as &$TR_INFO)
		{
			$N=-1;$START_T=0;$CURR_T='';
			if (!isset($TR_INFO['SEQUENCE']))continue;
			$STR_ALL.="\n<div>";
			$PREV=0;$FIRST=true;
			//print_R($TR_INFO);
			foreach ($MODULE_DATA['DNA_SEQUENCE'] as $CHR_SEQ_POS_ID=> &$CHR_POS)
			{
				++$N;
			//	echo $CHR_SEQ_POS_ID."\n";
				if (!isset($TR_INFO['SEQUENCE'][$CHR_SEQ_POS_ID])){
					if ($CURR_T!='')						
					{

						echo "J:".$TR_INFO['SYMBOL'].' '.$TR_INFO['TRANSCRIPT_NAME'].' '.$CURR_T.' '.$START_T.' '.$N."<br/>";
						$COLOR='';

									if (strpos($CURR_T,'CDS'))$COLOR='purple';
									if (strpos($CURR_T,'UTR'))$COLOR='green';
									if (strpos($CURR_T,'non-coded'))$COLOR='darkblue';
									$WIDTH=(($N-$START_T+1)*$RANGE);
									$NAME=$TR_INFO['SYMBOL'].' '.$TR_INFO['TRANSCRIPT_NAME'].' '.$CURR_T;
									$LEFT=($START_T*$RANGE)-$PREV;
									$PREV=$LEFT+$WIDTH;
									$STR_ALL.='<div style="display:inline-block;background-color:'.$COLOR.';border-radius:20px;position:relative;    padding-left: 10px;margin-top: 5px;left:'.$LEFT.'px;width:'.$WIDTH.'px;'.(($WIDTH>200)?'':'height:20px;display:inline-block').'">'.(($WIDTH>200)?$NAME:'').'</div>';
					if ($WIDTH<200)$STR_ALL.='<div style="left: 20px;color: black;position:relative;display: inline-block;top: -5px;">'.$NAME.'</div><br/>';
					$FIRST=false;
					}
					$CURR_T='';
					
					continue;}
				
				// if ($TR_INFO['SEQUENCE'][$CHR_SEQ_POS_ID]['NUCL']!=$CHR_POS['NUCL'])
				// $STR_ALL.=$TR_INFO['SEQUENCE'][$CHR_SEQ_POS_ID]['NUCL'];
				// else 
				// {
					$TR_POS=$TR_INFO['SEQUENCE'][$CHR_SEQ_POS_ID];
					
					if ($CURR_T!='Exon '.$TR_POS['EXON_ID'].'-'.$TR_POS['TRANSCRIPT_POS_TYPE'])
					{
				//		echo $CURR_T.' '.$TR_INFO['TRANSCRIPT_NAME'].' '.'Exon '.$TR_POS['EXON_ID'].'-'.$TR_POS['TRANSCRIPT_POS_TYPE']."<br/>";
						if ($CURR_T!='' )
						{
							echo "A:".$TR_INFO['SYMBOL'].' '.$TR_INFO['TRANSCRIPT_NAME'].' '.$CURR_T.' '.$START_T.' '.$N."<br/>";
							$COLOR='';
							if (strpos($CURR_T,'CDS'))$COLOR='purple';
							if (strpos($CURR_T,'UTR'))$COLOR='green';
							if (strpos($CURR_T,'non-coded'))$COLOR='darkblue';
							$WIDTH=(($N-$START_T+1)*$RANGE);
							$LEFT=($START_T*$RANGE)-$PREV;
									$PREV=$LEFT+$WIDTH;
							$NAME=$TR_INFO['SYMBOL'].' '.$TR_INFO['TRANSCRIPT_NAME'].' '.$CURR_T;
							$STR_ALL.='<div style="display:inline-block;background-color:'.$COLOR.';border-radius:20px;position:relative;    padding-left: 10px;margin-top: 5px;left:'.$LEFT.'px;width:'.$WIDTH.'px;'.(($WIDTH>200)?'':'height:20px;display:inline-block').'">'.(($WIDTH>200)?$NAME:'').'</div>';
			if ($WIDTH<200)$STR_ALL.='<div style="left: 20px;color: black;position:relative;display: inline-block;top: -5px;">'.$NAME.'</div><br/>';
			$FIRST=false;
						}
						$CURR_T='Exon '.$TR_POS['EXON_ID'].'-'.$TR_POS['TRANSCRIPT_POS_TYPE'];
						$START_T=$N;
					}
			//	}
				
			}
			if ($CURR_T!='')						
			{

				echo "E:".$TR_INFO['SYMBOL'].' '.$TR_INFO['TRANSCRIPT_NAME'].' '.$CURR_T.' '.$START_T.' '.$N."<br/>";
				$COLOR='';

							if (strpos($CURR_T,'CDS'))$COLOR='purple';
							if (strpos($CURR_T,'UTR'))$COLOR='green';
							if (strpos($CURR_T,'non-coded'))$COLOR='darkblue';
							$WIDTH=(($N-$START_T+((!$FIRST)?0:1))*$RANGE);
							$NAME=$TR_INFO['SYMBOL'].' '.$TR_INFO['TRANSCRIPT_NAME'].' '.$CURR_T;
							$LEFT=(($START_T+((!$FIRST)?1:0))*$RANGE)-$PREV;
									$PREV=$LEFT+$WIDTH;

			$STR_ALL.='<div style="display:inline-block;background-color:'.$COLOR.';border-radius:20px;position:relative;    padding-left: 10px;margin-top: 5px;left:'.$LEFT.'px;width:'.$WIDTH.'px;'.(($WIDTH>200)?'':'height:20px;display:inline-block').'">'.(($WIDTH>200)?$NAME:'').'</div>';
			if ($WIDTH<200)$STR_ALL.='<div  style="left: 20px;color: black;position:relative;display: inline-block;top: -5px;">'.$NAME.'</div><br/>';
			$FIRST=false;
			}
			$STR_ALL.='</div>';
		}
	}
	
//	exit;

	changeValue("dna_seq_view",'TR',$STR_ALL);

	

	// $STR.='<div class="w3-col s12">
	// <div class="w3-col s12" style="position:relative;top:-10px;left:'.round($RANGES['RATIO']*($PARAMS['POSITION']-$RANGES['MIN']),3);'%;width:1%;border:1px solid black"></div></div>';
	




?>