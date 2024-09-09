<?php

if (!defined("BIORELS")) header("Location:/");


$tab=explode("-",$USER_INPUT['PAGE']['VALUE']);
$AAS=array("ALA"=>"A","GLY"=>"G","ILE"=>"I","LEU"=>"L","PRO"=>"P","VAL"=>"V","PHE"=>"F","TRP"=>"W","TYR"=>"Y","ASP"=>"D","GLU"=>"E","ARG"=>"R","HIS"=>"H","LYS"=>"K","SER"=>"S","THR"=>"T","CYS"=>"C","MET"=>"M","ASN"=>"N","GLN"=>"Q");
if (!isset($tab[0],$AAS))throw new Exception("Unrecognized Amino-acid",ERR_TGT_USR);
if (!is_numeric($tab[1]))throw new Exception("Position must be numeric",ERR_TGT_USR);

$MODULE_DATA=getProteinResidueInfo($USER_INPUT['PORTAL']['VALUE'],$AAS[$tab[0]],$tab[1],$tab[0]);


?>