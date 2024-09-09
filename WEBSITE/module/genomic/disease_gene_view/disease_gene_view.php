<?php

if (!defined("BIORELS")) header("Location:/");
changeValue("disease_gene_view","GENE",$MODULE_DATA['GENE']['GENE_ID']);
changeValue("disease_gene_view","SYMBOL",$MODULE_DATA['GENE']['SYMBOL']);
changeValue("disease_gene_view","DISEASE",$MODULE_DATA['DISEASE']['DISEASE_TAG']);
changeValue("disease_gene_view","DISEASE_NAME",$MODULE_DATA['DISEASE']['DISEASE_NAME']);
changeValue("disease_gene_view","COUNT",$MODULE_DATA['STAT'][0]['ALL_COUNT']);
changeValue("disease_gene_view","NPAGE",ceil($MODULE_DATA['STAT'][0]['ALL_COUNT']/10));

?>