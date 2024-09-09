<?php


if (!is_dir($GLB_CONFIG['GLOBAL']['TG_DIR'])) throw new Exception("Unable to find TG_DIR",ERR_TGT_SYS);
$FILE=$GLB_CONFIG['GLOBAL']['TG_DIR'].'/PRD_DATA/COMPOUND/CHEMTOOLS_SCORE.csv';
if (!is_file($FILE)) throw new Exception("Unable to open file ",ERR_TGT_SYS);

$GENEID=$USER_INPUT['PARAMS'][0];
echo 'GENE ID :'.$GENEID;
$TMO=array();
if (!is_numeric($GENEID))throw new Exception("Unrecognized Gene ID format ".$GENEID,ERR_TGT_USR);
$fp=fopen($FILE,'r');
if ($fp)
{
	$HEAD=explode("\t",stream_get_line($fp,5000,"\n"));
	
	$LEN=strlen((string)$GENEID);
	$TMO=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,5000,"\n");
	//	echo $line."\n";
		if (substr($line,0,$LEN+1)!=((string)$GENEID)."\t")continue;
		
		$TMO=explode("\t",$line);break;
	}
	fclose($fp);
}
print_r($TMO);
$MODULE_DATA=array();
if (count($TMO)>0){
// echo "<pre>"; print_r($TMO); echo "</pre>";
foreach ($HEAD as $N=>$K){	$MODULE_DATA[$K]=$TMO[$N];}
}
unset($FILE,$GENEID,$fp,$HEAD,$LEN,$TMO,$HEAD,$N,$K);

?>