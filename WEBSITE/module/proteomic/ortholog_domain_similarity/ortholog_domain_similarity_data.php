<?php

if (!defined("BIORELS")) header("Location:/");

$GENE_ID=$USER_INPUT['PORTAL']['DATA']['GENE_ID'];
$res=getListDomain($GENE_ID);

$START_POS=0;$END_POS=0;$ISO_ID='';


print_r($USER_INPUT);
if (count($USER_INPUT['PARAMS'])!=4)throw new Exception("Expected 3 values",ERR_TGT_USR);
$INPUT=$USER_INPUT['PARAMS'][0];;
$START_POS=$USER_INPUT['PARAMS'][1]; if (!is_numeric($START_POS))throw new Exception("Expected Start position to be numeric",ERR_TGT_USR);
$END_POS=$USER_INPUT['PARAMS'][2]; if (!is_numeric($END_POS))throw new Exception("Expected end position to be numeric",ERR_TGT_USR);
$ISO_ID=$USER_INPUT['PARAMS'][3]; if (!is_string($ISO_ID))throw new Exception("Expected Start position to be numeric",ERR_TGT_USR);
$MODULE_DATA=array();

foreach ($res as $info)
{
	if ($info['DOMAIN_NAME']!=$INPUT||$info['POS_START']!=$START_POS||$info['POS_END']!=$END_POS||$info['ISO_ID']!=$ISO_ID)continue;
	$MODULE_DATA['REF_DOMAIN']=$info;
	break;
}
if ($MODULE_DATA==array())throw new Exception("Unable to find record",ERR_TGT_USR);

$TMP=getOrthologDomainAlign($MODULE_DATA['REF_DOMAIN']['UN_DOM_ID']);


$MODULE_DATA['REF_DOMAIN']['SEQ']=$TMP[0];
$MODULE_DATA['REF_DOMAIN']['INTER']=$TMP[2];


foreach ($TMP[1] as $DOM_ID=>$INFO)
{
	if ($DOM_ID=='SEQ')continue;
	$MODULE_DATA['ALT'][$DOM_ID]['ALIGNMENT']=$INFO;
	$MODULE_DATA['ALT'][$DOM_ID]['INFO']=$TMP[1]['SEQ'][$DOM_ID];
}




foreach ($MODULE_DATA['ALT'] as $UN_DOM_ID=>&$INFO)
{
	if (isset($TMP[1][$UN_DOM_ID]))$INFO['ALIGNMENT']=$TMP[1][$UN_DOM_ID];
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
	$LEN_REF_SEQ=count($DATA['REF_DOMAIN']['SEQ']);
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