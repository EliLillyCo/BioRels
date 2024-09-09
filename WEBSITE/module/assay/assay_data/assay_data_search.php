<?php
if (!defined("BIORELS")) header("Location:/");




 if ($MODULE_DATA['RESULTS']==array())
{
	removeBlock("assay_data_search","INVALID");
	removeBlock("assay_data_search","VALID");
	return;
}
else 
{
removeBlock("assay_data_search","INVALID");
removeBlock("assay_data_search","NO_RES");
}


$STR='';
$LIST=array();
$N=0;
foreach ($MODULE_DATA['RESULTS'] as $ENTRY)
{
	
	++$N;

$STR.='<div class="cpd_size" style="width:260px;text-align:center; float:left;padding:10px;">
<div class="cpd_size" style="width:100%;max-width:260px" id="list_'.$N.'"></div>
<span style="text-align:center;margin:0 auto;width:100%">'.$ENTRY['STD_TYPE'].': '.$ENTRY['STD_RELATION'].' '.$ENTRY['STD_VALUE'].$ENTRY['STD_UNITS'].'</span>
</div>
';


// $STR.='<tr><td style="width:330px" id="list_'.$N.'"></td>
// <td style="vertical-align:middle">'.$ENTRY['STD_TYPE'].'</td>
// <td style="vertical-align:middle">'.$ENTRY['STD_RELATION'].'</td>
// <td style="vertical-align:middle">'.$ENTRY['STD_VALUE'].'</td>
// <td style="vertical-align:middle">'.$ENTRY['STD_UNITS'].'</td></tr>';
$LIST[$N]['Structure']=$ENTRY['CPD']['SMILES'];
}

changeValue("assay_data_search","VALUES",$STR);
changeValue("assay_data_search","result",str_replace("\\","\\\\",json_encode($LIST)));

/*
rray
(
    [ASSAY_INFO] => Array
        (
            [ASSAY_NAME] => CHEMBL1002021
            [ASSAY_DESCRIPTION] => Inhibition of human CDK2 by HTRF assay
            [ASSAY_TEST_TYPE] => 	X
            [ASSAY_CATEGORY] => 
            [ASSAY_TYPE] => Binding	X
            [BIOASSAY_TAG_ID] => BAO_0000357
            [BIOASSAY_LABEL] => single protein format	X
            [BIOASSAY_DEFINITION] => one protein sequence

            [ASSAY_CELL_NAME] => X
            [ASSAY_CELL_DESCRIPTION] => X
            [ASSAY_CELL_SOURCE_TISSUE] => X
            [CELL_ACC] => X
            [CELL_NAME] => X
            [CELL_TYPE] => X
            [CELL_DONOR_SEX] => X
            [CELL_DONOR_AGE] =>X 
            [CELL_VERSION] => X
            [ASSAY_TISSUE_NAME] => 
            [ANATOMY_TAG] => 
            [ANATOMY_NAME] => 
            [ANATOMY_DEFINITION] => 
            [TAX_ID] => 9606	X
            [SCIENTIFIC_NAME] => Homo sapiens	X
            [MUTATION_LIST] => 
            [MUTATION_AC] => 
            [MUTATION_PROT_ISO] => 
            [MUTATION_PROT_DESC] => 
            [CONFIDENCE_SCORE] => 9		X
            [SOURCE_NAME] => ChEMBL		X
            [ASSAY_TARGET_NAME] => CHEMBL301
            [ASSAY_TARGET_LONGNAME] => Cyclin-dependent kinase 2
            [SPECIES_GROUP_FLAG] => 0
            [ASSAY_TARGET_TAX] => 9606
            [ASSAY_TARGET_TAXNAME] => Homo sapiens
            [ASSAY_TARGET_TYPE_NAME] => SINGLE PROTEIN
            [ASSAY_TARGET_TYPE_DESC] => Target is a single protein chain
        )

    [ASSAY_TARGET] => Array
        (
            [0] => Array
                (
                    [IS_HOMOLOGUE] => 0
                    [ACCESSION] => P24941
                    [ISO_ID] => P24941-1
                    [ISO_NAME] => Displayed
                    [PROT_IDENTIFIER] => CDK2_HUMAN
                    [GN_ENTRY_ID] => 76973
                    [PROT_SEQ_ID] => 934232
                    [PROT_ENTRY_ID] => 893661
                    [SYMBOL] => CDK2
                    [TAX_ID] => 9606
                    [GENE_ID] => 1017
                    [SCIENTIFIC_NAME] => Homo sapiens
                    [TARGET_TYPE] => PROTEIN
                )

        )

)
 */
?>