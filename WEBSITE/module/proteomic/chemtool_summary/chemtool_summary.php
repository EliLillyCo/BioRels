<?php


$OUTPUT[0]=array("<tr><td class='boldright'>Number of genes:</td><td  style='text-align:center'>N/A</td>",
	"<tr><td class='boldright'>Best ChemTool Activity:	  </td><td style='text-align:center'>".
	((!isset($MODULE_DATA['CHEMTOOLACT']))?'N/A':$MODULE_DATA['CHEMTOOLACT'])
	.'</td>',
	"<tr><td class='boldright'>Best ChemTool Scaffold:	  </td><td style='text-align:center'>".((!isset($MODULE_DATA['CHEMTOOLSCAFFOLD']))?'N/A':$MODULE_DATA['CHEMTOOLSCAFFOLD']).'</td>',
	"<tr><td class='boldright'>Best ChemTool Assay:		  </td><td style='text-align:center'>".((!isset($MODULE_DATA['CHEMTOOLASSAY']))?'N/A':$MODULE_DATA['CHEMTOOLASSAY']).'</td>',
	"<tr><td class='boldright'>Number of active compounds:</td><td style='text-align:center'>".((!isset($MODULE_DATA['N_ACTIVE']))?'N/A':$MODULE_DATA['N_ACTIVE']).'</td>',
	"<tr><td class='boldright'>Number of active scaffolds:</td><td style='text-align:center'>".((!isset($MODULE_DATA['N_SCAFF']))?'N/A':$MODULE_DATA['N_SCAFF']).'</td>',
	"<tr><td class='boldright'>Number of 3-D Structures:  </td><td style='text-align:center'>".((!isset($MODULE_DATA['XRAY']))?'N/A':$MODULE_DATA['XRAY']).'</td>');


$OUTPUT[1]=$OUTPUT[0];

$SUBT=array('COUNT','CHEMTOOLACT','CHEMTOOLASSAY','CHEMTOOLSCAFF','N_ACTIVE','N_SCAFF','N_XRAY');
$TT=array('SS','DO','CO');
for ($I=0;$I<=1;++$I)
foreach ($TT as $T)
foreach ($SUBT as $K=>$ST)
{
$H=($I==0)?'H_':'';
$N=$H.$T.'_'.$ST;
//if($N=="CO_N_SCAFF")$OUTPUT[$I][$K].="<td></td>";
//else
 $OUTPUT[$I][$K].='<td  style="text-align:center">'.(isset($MODULE_DATA[$N])?$MODULE_DATA[$N]:'N/A').'</td>';
}

changeValue("chemtool_summary","TBL_DATA",implode($OUTPUT[0],"\n"));
changeValue("chemtool_summary","TBL_DATA_2",implode($OUTPUT[1],"\n"));

?>