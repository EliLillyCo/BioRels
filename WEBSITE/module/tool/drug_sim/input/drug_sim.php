<?php

if (!defined("BIORELS")) header("Location:/");



if (isset($MODULE_DATA['INPUT']))
{
	if (isset($MODULE_DATA['INPUT']['STRUCTURE']))changeValue("drug_sim",'SMILES',$MODULE_DATA['INPUT']['STRUCTURE']);
	


}
if ($TITLE!='')changeValue("drug_sim",'TITLE',$TITLE);
if ($DESCRIPTION!='')changeValue("drug_sim",'DESCRIPTION',$DESCRIPTION);
if (isset($MODULE_DATA['ERROR']))
{
	changeValue("drug_sim",'ALERT','<div class="w3-container alert alert-info">'.$MODULE_DATA['ERROR'].'</div>');
}
//$MODULE_DATA['HASH']='286b046cf8e3b9846ac40322bff69e66';
if (isset($MODULE_DATA['HASH']))
changeValue("drug_sim",'HASH',$MODULE_DATA['HASH']);
else removeBlock("drug_sim",'MONITOR');


for ($i=100;$i>=0;--$i)
{
	$STR.='<option value="'.$i.'"' .(($i==80)?' selected="selected"':'').'>'.$i.'%</option>';
}
changeValue("drug_sim",'LIST',$STR);

?>