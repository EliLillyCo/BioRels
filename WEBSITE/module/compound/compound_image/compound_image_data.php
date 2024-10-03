<?php

if (!defined("BIORELS")) header("Location:/");

try{

$SMILES='';
$WIDTH=1000;
$HEIGHT=1000;
$BG=array(0,0,0,0);
$FONT=1;
$LINE=3;
$N_P=count($USER_INPUT['PARAMS']);
for ($I=0;$I<$N_P;++$I)
{
  if ($USER_INPUT['PARAMS'][$I]=="SMILES")
  {
    if ($I+1>=$N_P)throw new Exception("SMILES is not provided",ERR_TGT_SYS);
    $SMILES=$USER_INPUT['PARAMS'][$I+1];
	$I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=="WIDTH")
  {
    if ($I+1>=$N_P)throw new Exception("WIDTH is not provided",ERR_TGT_SYS);
    $WIDTH=$USER_INPUT['PARAMS'][$I+1];
	$I+=1;
	if (!is_numeric($WIDTH))throw new Exception("WIDTH must be numeric",ERR_TGT_SYS);
	if ($WIDTH<100)throw new Exception("WIDTH must be higher than 100",ERR_TGT_SYS);
	if ($WIDTH>2000)throw new Exception("WIDTH must be lower than 2000",ERR_TGT_SYS);
  }
  if ($USER_INPUT['PARAMS'][$I]=="HEIGHT")
  {
    if ($I+1>=$N_P)throw new Exception("HEIGHT is not provided",ERR_TGT_SYS);
    $HEIGHT=$USER_INPUT['PARAMS'][$I+1];
	if (!is_numeric($HEIGHT))throw new Exception("HEIGHT must be numeric",ERR_TGT_SYS);
	
	if ($HEIGHT<100)throw new Exception("HEIGHT must be higher than 100",ERR_TGT_SYS);
	if ($HEIGHT>2000)throw new Exception("HEIGHT must be lower than 2000",ERR_TGT_SYS);
	$I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=="FONT")
  {
    if ($I+1>=$N_P)throw new Exception("FONT is not provided",ERR_TGT_SYS);
    $FONT=$USER_INPUT['PARAMS'][$I+1];
	if (!is_numeric($FONT))throw new Exception("FONT must be numeric",ERR_TGT_SYS);
	
	if ($FONT<1)throw new Exception("HEIGHT must be higher than 1",ERR_TGT_SYS);
	if ($FONT>4)throw new Exception("HEIGHT must be lower than 4",ERR_TGT_SYS);
	
	$I+=1;
  }
  if ($USER_INPUT['PARAMS'][$I]=="LINE")
  {
    if ($I+1>=$N_P)throw new Exception("LINE is not provided",ERR_TGT_SYS);
    $LINE=$USER_INPUT['PARAMS'][$I+1];
	$I+=1;
	if (!is_numeric($LINE))throw new Exception("LINE must be numeric",ERR_TGT_SYS);
	
	if ($LINE<1)throw new Exception("LINE must be higher than 1",ERR_TGT_SYS);
	if ($LINE>4)throw new Exception("LINE must be lower than 4",ERR_TGT_SYS);
	
  }
  if ($USER_INPUT['PARAMS'][$I]=="BG")
  {
    if ($I+4>=$N_P)throw new Exception("BG is not provided",ERR_TGT_SYS);
    $BG=array($USER_INPUT['PARAMS'][$I+1],$USER_INPUT['PARAMS'][$I+2],$USER_INPUT['PARAMS'][$I+3],$USER_INPUT['PARAMS'][$I+4]);
	$I+=4;

	foreach ($BG as $K=>$V)
	{
		if (!is_numeric($V))throw new Exception("LINE must be numeric",ERR_TGT_SYS);
	
		if ($V<0)throw new Exception("LINE must be higher than 0",ERR_TGT_SYS);
		if ($V>1)throw new Exception("LINE must be lower than 1",ERR_TGT_SYS);
			
	}
  }
  
}


$command='python3 /var/www/html/require/python/gen_img.py -s \''.$SMILES.'\''.
' -w '.$WIDTH.' -ht '.$HEIGHT.' -bg '.implode(" ",$BG).' -f '.$FONT.' -l '.$LINE;
ob_end_clean();
ob_start();
passthru($command);
$var=ob_get_contents();
ob_end_clean();

$MODULE_DATA['IMAGE']=$var;
$MODULE_DATA['BASE64']=base64_encode($var);
$MODULE_DATA['BASE64_H']='data:image/png;base64,';

}catch (Exception $e)
{
	
	$MODULE_DATA['ERROR']=$e->getMessage();
}

?>