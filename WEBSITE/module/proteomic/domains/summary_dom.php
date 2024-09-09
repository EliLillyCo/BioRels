<?php
global $TARGET;
global $USER_INPUT;
$QUERY="select count(*) as N_DOM, UN_IDENTIFIER, PROTEIN_NAME, MT.STATUS FROM
(select UN_ENTRY_ID, PROTEIN_NAME FROM UN_PNAME_MAP UPM, UN_PNAME UP 
  WHERE UP.UN_PROT_NAME_ID=UPM.UN_PROT_NAME_ID AND GROUP_ID=1 AND IS_PRIMARY='T' AND CLASS_NAME='REC' AND NAME_TYPE='REC') PN,
MV_TARGETS MT, UN_DOM UD
WHERE  UD.UN_ENTRY_ID = MT.UN_ENTRY_ID  AND GENE_ID=".$TARGET['GENE_ID']." 
AND UD.UN_ENTRY_ID=PN.UN_ENTRY_ID GROUP BY UN_IDENTIFIER, PROTEIN_NAME,STATUS ORDER BY STATUS DESC";

$res=runQuery($QUERY);



$STR='';

foreach ($res as $N=>$L)
{
$STR.='<div style="width:100%"><input type="radio" name="SQ" value="'.$L['UN_IDENTIFIER'].'" id="SQ_'.$L['UN_IDENTIFIER'].'" '.(($N==0)?'checked':'').'><label for="SQ_'.$L['UN_IDENTIFIER'].'">'.$L['UN_IDENTIFIER'].': '.$L['PROTEIN_NAME'].'</label></div><br/>';
}
changeValue("summary_dom","LIST_SEQ",$STR);
changeValue("summary_dom","GENEID",$TARGET['GENE_ID']);

changeValue("summary_dom",'SIM',($USER_INPUT['SIM']?'SIM/':''));

?>
