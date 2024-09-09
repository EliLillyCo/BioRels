<?php

function getPrivateGeneAssays($GN_ENTRY_ID)
{
    global $DB_SCHEMA_PRIVATE;
    $query = 'SELECT assay_name, assay_description, assay_type,  assay_target_type_id,
   ae.confidence_score as score_confidence, source_name,confidence_score,assay_variant_id,
   ace.cell_entry_id as cell_id, assay_tissue_name 
   FROM ' . $DB_SCHEMA_PRIVATE . '.assay_entry ae 
   LEFT JOIN ' . $DB_SCHEMA_PRIVATE . '.assay_cell ace ON ae.assay_cell_id = ace.assay_cell_id 
   LEFT JOIN ' . $DB_SCHEMA_PRIVATE . '.assay_tissue ati ON ati.assay_tissue_id = ae.assay_tissue_id 
   LEFT JOIN anatomy_entry aen ON aen.anatomy_entry_id = ati.anatomy_entry_id, 
   ' . $DB_SCHEMA_PRIVATE . '.assay_target at, 
   ' . $DB_SCHEMA_PRIVATE . '.assay_target_protein_map atp, 
   ' . $DB_SCHEMA_PRIVATE . '.assay_protein ap,
   prot_seq ps,
   prot_entry pe,
   source s,
   gn_prot_map gpm
   WHERE ae.assay_target_id = at.assay_Target_id
   AND ae.source_id = s.source_id
   AND at.assay_target_id = atp.assay_Target_id 
   AND atp.assay_protein_id = ap.assay_protein_id
   AND ap.prot_seq_id = ps.prot_Seq_id 
   AND ps.prot_entry_Id = pe.prot_entry_id
   AND gpm.prot_entry_Id = pe.prot_entry_id
   AND gpm.gn_entry_Id = ' . $GN_ENTRY_ID;

    $DATA['ASSAYS'] = runQuery($query);
    $DATA['CELL'] = array();
    foreach ($DATA['ASSAYS'] as &$A) if ($A['CELL_ID'] != '') $DATA['CELL'][$A['CELL_ID']] = array();
    if ($DATA['CELL'] != array()) {
        $res = runQuery("SELECT cell_entry_id, cell_name,cell_type,cell_donor_sex,cell_donor_age FROM cell_entry WHERE cell_entry_id IN (" . implode(',', array_keys($DATA['CELL'])) . ')');

        foreach ($res as $line) {
            $DATA['CELL'][$line['CELL_ENTRY_ID']] = array($line['CELL_NAME'], $line['CELL_TYPE'], $line['CELL_DONOR_SEX'], $line['CELL_DONOR_AGE']);
        }
    }

    $DATA['VARIANT'] = array();
    foreach ($DATA['ASSAYS'] as &$A) if ($A['ASSAY_VARIANT_ID'] != '') $DATA['VARIANT'][$A['ASSAY_VARIANT_ID']] = array();
    if ($DATA['VARIANT'] != array()) {
        $res = runQuery("SELECT assay_variant_id,mutation_list FROM " . $DB_SCHEMA_PRIVATE . ".assay_variant WHERE assay_variant_id IN (" . implode(',', array_keys($DATA['VARIANT'])) . ')');

        foreach ($res as $line) {
            $DATA['VARIANT'][$line['ASSAY_VARIANT_ID']] = $line['MUTATION_LIST'];
        }
    }

    $DATA['TYPE'] = array();
    $res = runQuery("SELECT assay_type_id,assay_desc FROM assay_type");
    foreach ($res as $line) $DATA['TYPE'][$line['ASSAY_TYPE_ID']] = $line['ASSAY_DESC'];


    $DATA['CONFIDENCE'] = array();
    $res = runQuery("SELECT confidence_score,description,target_mapping FROM assay_confidence");
    foreach ($res as $line) $DATA['CONFIDENCE'][$line['CONFIDENCE_SCORE']] = array('DESC' => $line['DESCRIPTION'], 'NAME' => $line['TARGET_MAPPING']);


    $DATA['TARGET_TYPE'] = array();
    $res = runQuery("SELECT assay_target_type_id,assay_target_type_name,assay_target_type_desc FROM assay_target_type");
    foreach ($res as $line) $DATA['TARGET_TYPE'][$line['ASSAY_TARGET_TYPE_ID']] = array($line['ASSAY_TARGET_TYPE_DESC'], $line['ASSAY_TARGET_TYPE_NAME']);

    return $DATA;
}
?>