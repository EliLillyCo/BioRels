<?php

if (isset($USER_INPUT['PORTAL']['DATA']['GENE_ID']))
{
changeValue("3D_structures","GENE_ID",$USER_INPUT['PORTAL']['DATA']['GENE_ID']);
changeValue("3D_structures","LINK",'/GENEID/'.$USER_INPUT['PORTAL']['DATA']['GENE_ID']);
changeValue("3D_structures","ORGANISM",$USER_INPUT['PORTAL']['DATA']['SCIENTIFIC_NAME']);
changeValue("3D_structures","SYMBOL",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
changeValue("3D_structures","PROT_NAME",$USER_INPUT['PORTAL']['DATA']['FULL_NAME']);		
}
else
{
    changeValue("3D_structures","NAME",$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
    changeValue("3D_structures","LINK",'/UNIPROT_ID/'.$USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
    changeValue("3D_structures","ORGANISM",$USER_INPUT['PORTAL']['DATA']['SCIENTIFIC_NAME']);
}
changeValue("3D_structures","NPAGE_GENE",ceil($MODULE_DATA['COUNT']/10));
changeValue("3D_structures","N_ENTRY",$MODULE_DATA['COUNT']);
changeValue("3D_structures","N_SIM_SEQ",$MODULE_DATA['COUNT_SIM_SEQ']);
changeValue("3D_structures","N_SIM_DOM",$MODULE_DATA['COUNT_SIM_DOM']);
changeValue("3D_structures","NPAGE_SIMDOM",ceil($MODULE_DATA['COUNT_SIM_DOM']/10));
changeValue("3D_structures","NPAGE_SIMSEQ",ceil($MODULE_DATA['COUNT_SIM_SEQ']/10));
		
/*
[CDK2_HUMAN] => Array
        (
            [28291] => Array
                (
                    [UN_IDENTIFIER] => CDK2_HUMAN
                    [XR_ENTRY_ID] => 28291
                    [FULL_COMMON_NAME] => 1Y91
                    [EXPR_TYPE] => X-RAY DIFFRACTION
                    [RESOLUTION] => 2.15
                    [DEPOSITION_DATE] => 14-DEC-04 12.00.00.000000 AM
                    [TITLE] => Crystal structure of human CDK2 complexed with a pyrazolo[1,5-a]pyrimidine inhibitor
                    [XR_CHAIN_ID] => 56982
                    [CHAIN_NAME] => A
                    [PERC_SIM] => 100
                    [PERC_IDENTITY] => 100
                    [PERC_SIM_COM] => 100
                    [PERC_IDENTITY_COM] => 100
                    [N_MUTANT] => 0
                    [ISO_ID] => P24941-1
                    [IS_PRIMARY] => T
                    [UN_SEQ_ID] => 204527
*/
?>