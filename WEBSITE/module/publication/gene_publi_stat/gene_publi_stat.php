<?php


if ($MODULE_DATA==array())
{
	removeBlock("gene_publi_stat","VALID");
	return;
}else removeBlock("gene_publi_stat","INVALID");

$STR='year,count\n';
$MIN=min(array_keys($MODULE_DATA));
echo $MIN."\n";
$MERGE='';
if ($MIN<=2005)
{
	$NEW=array();
	$N=0;
	for ($I=$MIN;$I<=2005;++$I)
	{
		if (isset($MODULE_DATA[$I]))
		{
		  $N+=$MODULE_DATA[$I];
		  unset($MODULE_DATA[$I]);
		}
	}
	//$MERGE=substr((string)($MIN),2).'-05'.','.$N.'\n';
	$MIN=2006;
	
}

$MAX=max(array_keys($MODULE_DATA));
if (count($MODULE_DATA)>20)
{
	if ($MERGE!='')$STR.=$MERGE;
	for ($I=$MIN;$I<=$MAX;$I+=2)
	{
		$STR.=substr((string)($I),2).'-'.substr((string)($I+1),2).',';
		$N=0;
		if (isset($MODULE_DATA[$I]))  $N+=$MODULE_DATA[$I];
		if (isset($MODULE_DATA[$I+1]))$N+=$MODULE_DATA[$I+1];
		
		 $STR.=$N.'\n';
	}
}
else{
	if ($MERGE!='')$STR.=$MERGE;
for ($I=$MIN;$I<=$MAX;++$I)
{
	if (isset($MODULE_DATA[$I]))$STR.=$I.','.$MODULE_DATA[$I].'\n';
	else $STR.=$I.',0\n';
}
}
//foreach($pub_stat as $year=>$count)	$STR.=$year.','.$count.'\n';

	changeValue("gene_publi_stat","PUBLI_DATA",$STR);


?>