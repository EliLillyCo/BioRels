<?php
/// BIORELS defined in index.php. Not existing? Go to index.php
if (!defined("BIORELS")) header("Location:/");

changeValue("disease_trait","MAIN_TITLE",implode(' ; ',array_keys($MODULE_DATA['NAME']['P'])));

$STR_SYN='';
foreach ($MODULE_DATA['NAME']['A'] as $NAME=>$LINKS)
{
	$STR_SYN.='<h3>'.$NAME.'</h3><ul>';
	foreach ($LINKS as $DBNAME=>$DBID_L)
	{
		$STR_SYN.='<li>'.$DBNAME.' (';
		foreach ($DBID_L as $K=>$ID){if ($K%10==0 && $K>0)$STR_SYN.='<br/>';$STR_SYN.=$ID.',';}
		$STR_SYN =substr($STR_SYN,0,-1).')</li>';
	}
	$STR_SYN.='</ul>';
}
changeValue("disease_trait","SYNONYMS",$STR_SYN);

foreach ($MODULE_DATA['CLINV'] as $CLINV_ID=>&$LIST)
{
	$STR_G='';
	$STR_I='';
foreach ($LIST as &$line)
{
	$STR_G.='<a href="/TEAM/RNAEDIT/GENEID/'.$line['GENE_ID'].'">'.$line['SYMBOL'].'</a><br/>';
	$STR_I.='<a href="/TEAM/RNAEDIT/GENEID/'.$line['GENE_ID'].'">'.$line['GENE_ID'].'</a><br/>';
		
}
	$STR.='<tr><td><a href="/TEAM/RNAEDIT/MUTATION/'.$line['RSID'].'">'.$line['RSID'].'</a></td>
	<td>'.substr($STR_G,0,-5).'</td>
	<td>'.substr($STR_I,0,-5).'</td>';
	$POS='';
	if ($line['POSITION']!='')$POS=$line['POSITION'].':'.$line['REF_ALL'].'>'.$line['ALT_ALL'];
	$STR.='<td>'.$POS.'</td><td>'.$line['TITLE'].'</td><td><a href="/TEAM/RNAEDIT/CLINVAR/'.$line['CLINV_IDENTIFIER'].'">'.$line['CLINV_IDENTIFIER'].'</a></td>';
	$STR.='<td>'.$line['CLIN_SIGN_DESC'].'</td><td>'.$line['CLIN_SIGN_STATUS'].'</td><td>'.$line['DATE_UPDATED'].'</td></tr>';



}
changeValue("disease_trait","LIST",$STR);
changeValue("disease_trait","TAG_ID",$USER_INPUT['PAGE']['VALUE']);
?>