<?php

if (!defined("BIORELS")) header("Location:/");

$GENE_ID=$USER_INPUT['PORTAL']['DATA']['GENE_ID'];
$res=getProteinSequences($GENE_ID);
$INPUT=$USER_INPUT['PAGE']['VALUE'];
$MODULE_DATA=array();
foreach ($res['SEQ'] as $info)
{
	
	if ($info['SEQ']['ISO_ID']!=$INPUT)continue;
	$MODULE_DATA['REF_SEQ']=$info['SEQ'];
	break;
}
if ($MODULE_DATA==array())throw new Exception("Unable to find record",ERR_TGT_USR);
$WIDTH=-1;
for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
{
	if ($USER_INPUT['PARAMS'][$I]!='WIDTH')continue;
	$WIDTH=$USER_INPUT['PARAMS'][$I+1];
	if (strpos($WIDTH,'px')!==false)$WIDTH=substr($WIDTH,0,-2);
	unset($USER_INPUT['PARAMS'][$I]);
	unset($USER_INPUT['PARAMS'][$I+1]);break;
}

if (count($USER_INPUT['PARAMS'])==2)
{
	$COMP_GENE_ID=$USER_INPUT['PARAMS'][0]; 
	if (!is_numeric($COMP_GENE_ID))throw new Exception("Expected gene ID to be numeric",ERR_TGT_USR);
	$res=getProteinSequences($COMP_GENE_ID);
	$INPUT=$USER_INPUT['PARAMS'][1];
	foreach ($res['SEQ'] as $info)
	{
		if ($info['SEQ']['ISO_ID']!=$INPUT)continue;
		$MODULE_DATA['ALT'][$info['SEQ']['PROT_SEQ_ID']]=$info['SEQ'];
		break;
	}
}
else 
{
	$res=getIsoSequence($USER_INPUT['PARAMS'][0]);
	foreach ($res['SEQ'] as $info)
	{
		if ($info['SEQ']['ISO_ID']!=$USER_INPUT['PARAMS'][0])continue;
		$MODULE_DATA['ALT'][$info['SEQ']['PROT_SEQ_ID']]=$info['SEQ'];
		break;
	}
}




$TMP=getSequenceAlignment($MODULE_DATA['REF_SEQ']['PROT_SEQ_ID'],array_keys($MODULE_DATA['ALT']));

$MODULE_DATA['REF_SEQ']['SEQ']=$TMP[0];
$MODULE_DATA['REF_SEQ']['INTER']=$TMP[2];
foreach ($MODULE_DATA['ALT'] as $PROT_DOM_ID=>&$INFO)
{
	if (isset($TMP[1][$PROT_DOM_ID]))$INFO['ALIGNMENT']=$TMP[1][$PROT_DOM_ID];
}

$MODULE_DATA['ALIGNMENT']=buildAlignment($MODULE_DATA);

function buildAlignment(&$DATA)
{
	$CURSOR=array();
	$ALIGNMENT=array('REF'=>array());$AL_POS=-1;
	/// Create lines for alignment
	foreach ($DATA['ALT'] as $C_ID=>&$INFO)
	{
		$ALIGNMENT[$C_ID]=array();
		ksort($INFO['ALIGNMENT']['AL']);
	} 
	/// First cover anything from the aligned sequences not covered by the alignment
	foreach ($DATA['ALT'] as $C_ID=>&$INFO)
	{
		$AL=&$INFO['ALIGNMENT']['AL'];
		foreach ($AL as $RP=>$CP)
		{
			$CURSOR[$C_ID]=$CP-1;
			if ($CP==1)break;
			for($I=1;$I<=$CP;++$I)
			{
				$AL_POS++;
				foreach ($ALIGNMENT as $AL_CID=>&$AL_SEQ) if ($AL_CID==$C_ID)$AL_SEQ[$AL_POS]=$I; else $AL_SEQ[$AL_POS]='';
			}
			
			break;
		}
	}
	$LEN_REF_SEQ=count($DATA['REF_SEQ']['SEQ']);
	$CURSOR['REF']=0;
	for ($IREF=1;$IREF<$LEN_REF_SEQ;++$IREF)
	{
		//print_r($CURSOR);
		/// First check if there are some inserted AA
		foreach ($DATA['ALT'] as $C_ID=>&$INFO)
		{
			$AL=&$INFO['ALIGNMENT']['AL'];
			if (isset($AL[$IREF]) && $AL[$IREF]>$CURSOR[$C_ID]+1)
			{
				for ($IP=$CURSOR[$C_ID]+1;$IP<$AL[$IREF];++$IP)
				{
					//echo "ADD SHIFT".$IP."\n";
					$AL_POS++;
					foreach ($ALIGNMENT as $AL_CID=>&$AL_SEQ) if ($AL_CID==$C_ID)$AL_SEQ[$AL_POS]=$IP; else $AL_SEQ[$AL_POS]='';
					$CURSOR[$C_ID]=$IP;
				}
				
			}
		}
		$AL_POS++;
		foreach ($DATA['ALT'] as $C_ID=>&$INFO)
		{
			$AL=&$INFO['ALIGNMENT']['AL'];
			if (isset($AL[$IREF]))
			{
			//	echo $IREF.' '.$AL[$IREF].' '.($CURSOR[$C_ID]+1)."\n";
				if ($AL[$IREF]!=$CURSOR[$C_ID]+1) throw new Exception("Failed previous position");
				$ALIGNMENT[$C_ID][$AL_POS]=$AL[$IREF];
				$CURSOR[$C_ID]=$AL[$IREF];
			}else $ALIGNMENT[$C_ID][$AL_POS]='';
		}
		$ALIGNMENT['REF'][$AL_POS]=$IREF;
		$CURSOR['REF']=$IREF;

	}
	foreach ($CURSOR as $C_ID=>$C_POS)
	{
		if ($C_ID=='REF')continue;
		$C_SEQ=&$DATA['ALT'][$C_ID]['ALIGNMENT']['SEQ'];
		$MAX_V=max(array_keys($C_SEQ));
		for(;$C_POS<=$MAX_V;++$C_POS)
		{
			++$AL_POS;
			foreach ($ALIGNMENT as $AL_CID=>&$AL_SEQ) if ($AL_CID==$C_ID)$AL_SEQ[$AL_POS]=$C_POS; else $AL_SEQ[$AL_POS]='';
		}
	}

	

	return $ALIGNMENT;
}

?>