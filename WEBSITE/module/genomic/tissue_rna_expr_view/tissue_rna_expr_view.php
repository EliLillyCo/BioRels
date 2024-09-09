<?php

if (!defined("BIORELS")) header("Location:/");


changeValue("tissue_rna_expr_view","TISSUE",$USER_INPUT['PORTAL']['VALUE']);


$co=$MODULE_DATA['STAT'];
//foreach ($MODULE_DATA['STAT'] as $IN)$co+=$IN['CO'];
changeValue("tissue_rna_expr_view","COUNT",$MODULE_DATA['STAT']);
changeValue("tissue_rna_expr_view","NPAGE",(ceil($co/10)));


?>