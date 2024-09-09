<?php

if (!defined("BIORELS")) header("Location:/");


$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];
$MODULE_DATA=array();
try{

$FILTERS=array('TRANSCRIPTS'=>array(),'LOCATION'=>array(),'CLINICAL'=>array(),'MUT_TYPE'=>array(),'IMPACT'=>array(),'FREQ_OV'=>array(0=>0,1=>100),'FREQ_ST'=>array(0=>1,1=>100),'PAGE'=>0,'N_PER_PAGE'=>10);
for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
{
	switch($USER_INPUT['PARAMS'][$I])
	{
		case 'TRANSCRIPTS':
			$FILTERS['TRANSCRIPTS']=explode("|",$USER_INPUT['PARAMS'][$I+1]);
			++$I;
			break;
		case 'LOCATION':
			$FILTERS['LOCATION']=explode("|",str_replace('&#039;','\'',$USER_INPUT['PARAMS'][$I+1]));
			
			++$I;
			break;
		case 'IMPACT':
			$FILTERS['IMPACT']=explode("|",$USER_INPUT['PARAMS'][$I+1]);

			++$I;
			break;
		
		case 'CLINICAL':
			$FILTERS['CLINICAL']=explode("|",$USER_INPUT['PARAMS'][$I+1]);
			++$I;
			break;
		case 'MUT_TYPE':
			$FILTERS['MUT_TYPE']=explode("|",$USER_INPUT['PARAMS'][$I+1]);
			++$I;
			break;
		case 'FREQ_OV':
			$FILTERS['FREQ_OV']=explode("_",$USER_INPUT['PARAMS'][$I+1]);
			$I+=1;
			break;
		case 'FREQ_ST':
			$FILTERS['FREQ_ST']=explode("_",$USER_INPUT['PARAMS'][$I+1]);
			$I+=1;
			break;
		case 'PAGE':
			$FILTERS['PAGE']=$USER_INPUT['PARAMS'][$I+1];
			$I+=1;
			break;
		case 'N_PER_PAGE':
			$FILTERS['N_PER_PAGE']=$USER_INPUT['PARAMS'][$I+1];
			$I+=1;
			break;
	}
}


///Validation

foreach ($FILTERS['TRANSCRIPTS'] as $T)
{
	if (!checkRegex($T,'REGEX:TRANSCRIPT'))throw new Exception("Unrecognized Transcript Name ".$T,ERR_TGT_USR);
	
	  
}

foreach ($FILTERS['CLINICAL'] as $C)
{
//	if (preg_match("/[\W]+/",$C))throw new Exception("Non-word character not allowed "+$C,ERR_TGT_SYS);
}


$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];

$MODULE_DATA=searchMutations($GN_ENTRY_ID,$FILTERS);


}catch (Exception $e)
{
	$MODULE_DATA['ERROR']=$e->getMessage();
}




?>