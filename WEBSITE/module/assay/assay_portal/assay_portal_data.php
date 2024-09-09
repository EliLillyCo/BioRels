<?php
if (!defined("BIORELS")) header("Location:/");

global $USER_INPUT;

$TARGET=$USER_INPUT['PORTAL']['DATA'];
if (isset($TARGET['ASSAY_ENTRY_ID']))
{
	$MODULE_DATA=getAssayInformation($TARGET['ASSAY_ENTRY_ID']);
}


/*
rray
(
    [ASSAY_INFO] => Array
        (
            [ASSAY_NAME] => CHEMBL1002021
            [ASSAY_DESCRIPTION] => Inhibition of human CDK2 by HTRF assay
            [ASSAY_TEST_TYPE] => 
            [ASSAY_CATEGORY] => 
            [ASSAY_TYPE] => Binding
            [BIOASSAY_TAG_ID] => BAO_0000357
            [BIOASSAY_LABEL] => single protein format
            [BIOASSAY_DEFINITION] => one protein sequence

            [ASSAY_CELL_NAME] => 
            [ASSAY_CELL_DESCRIPTION] => 
            [ASSAY_CELL_SOURCE_TISSUE] => 
            [CELL_ACC] => 
            [CELL_NAME] => 
            [CELL_TYPE] => 
            [CELL_DONOR_SEX] => 
            [CELL_DONOR_AGE] => 
            [CELL_VERSION] => 
            [ASSAY_TISSUE_NAME] => 
            [ANATOMY_TAG] => 
            [ANATOMY_NAME] => 
            [ANATOMY_DEFINITION] => 
            [TAX_ID] => 9606
            [SCIENTIFIC_NAME] => Homo sapiens
            [MUTATION_LIST] => 
            [MUTATION_AC] => 
            [MUTATION_PROT_ISO] => 
            [MUTATION_PROT_DESC] => 
            [CONFIDENCE_SCORE] => 9
            [SOURCE_NAME] => ChEMBL
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