<?php   #every php file must start with this (always end with the opposite one on line 9)

#print_r($USER_INPUT);
#exit;       #stops the rest of the program form running (good debugging method)

#echo '<pre>';print_r($MODULE_DATA);exit;

if (!defined("BIORELS")) header("Location:/");


if ($MODULE_DATA['INPUT'] == 'PROTEIN') {
    changeValue("ptmome", "ENTRY_NAME", $USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
}

else if ($MODULE_DATA['INPUT'] == 'GENE') {
    changeValue("ptmome", "ENTRY_NAME", $USER_INPUT['PORTAL']['DATA']['SYMBOL']);
}


$str='';
foreach ($MODULE_DATA['SITES'] as $UNI_SEQ=>&$UNI_SEQ_INFO)
foreach ($UNI_SEQ_INFO['LIST_PTM'] as $AA=>&$AA_DATA )
foreach ($AA_DATA['PTM'] as $PTM_ID=>&$PTM_INFO)
{
    $str.='<tr><td>'.$UNI_SEQ_INFO['INFO']['UNIPROT_ID'].'</td>
    <td>'.$AA_DATA['INFO']['LETTER'].'</td>
    <td>'.$AA_DATA['INFO']['POSITION'].'</td>
	<td>'.$PTM_INFO[1].'</td>
    <td><a noopener target="_blank" href="https://www.phosphosite.org/siteAction.action?id='.$PTM_ID.'">'.$PTM_ID.'</a></td>
</tr>';

}

changeValue("ptmome","LIST_SITES", $str);

?> 

