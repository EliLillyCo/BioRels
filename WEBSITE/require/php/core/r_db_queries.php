<?php


///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// GENE PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


// $[API]
// Title: Gene Search By NCBI ID
// Function: gene_portal_geneID
// Description: Search for a gene by using its gene ID
// Parameter: $GENE_ID | NCBI Gene ID | int | required
// $[/API]
function gene_portal_geneID(int $GENE_ID)
{
    global $GLB_CONFIG;
    $query = "
            SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
            FROM mv_gene_sp
            WHERE GENE_ID=$GENE_ID 
            GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
            ";

    $res = runQuery($query);


    if (count($res) > 0) {
        $res[0]['SYN_VALUE'] = explode("|", $res[0]['SYN_VALUE']);
        return $res[0];
    } else {

        $NEW_GENE_ID = '';
        $n = 0;
        do {

            ++$n;

            $res = runQuery("SELECT alt_gene_id,gn_entry_id FROM gn_history where gene_id=" . $GENE_ID);
            if ($res == array()) return false;
            print_r($res);
            if ($res[0]['GN_ENTRY_ID'] != '') {

                $NEW_GENE_ID = $res[0]['ALT_GENE_ID'];
                break;
            } else if ($res[0]['ALT_GENE_ID'] != '-') $GENE_ID = $res[0]['ALT_GENE_ID'];
            else break;
        } while ($n < 5);

        if ($NEW_GENE_ID == '') return false;
        $query = "SELECT SYMBOL,FULL_NAME,GENE_ID,GN_ENTRY_ID,STRING_AGG(SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as SYN_VALUE,SCIENTIFIC_NAME,TAX_ID
	 FROM MV_GENE WHERE GENE_ID=" . $NEW_GENE_ID . " GROUP BY SYMBOL,FULL_NAME,GENE_ID,GN_ENTRY_ID,SCIENTIFIC_NAME,TAX_ID";

        $res = runQuery($query);


        if (count($res) > 0) {
            $res[0]['SYN_VALUE'] = explode("|", $res[0]['SYN_VALUE']);

            return $res[0];
        } else return false;
    }
}



// $[API]
// Title: 
// Function: 
// Description: 
// Parameter: 
// $[/API]
function source_search($source_name)
{
    $query="SELECT * FROM source where LOWER(source_name) ='".strtolower($source_name)."'";
    
    $res=runQuery($query);
    if (count($res)!=0)return $res;
    $query="SELECT * FROM source where LOWER(source_name) LIKE '%".strtolower($source_name)."%'";
    
    $res=runQuery($query);

    if (count($res)!=0)return $res;
    return array();
}

function searchChrPosition($CHR,$CHR_POS)
{
	$res=runQuery("SELECT CHR_SEQ_POS_ID, C.CHR_ID,CHR_NUM, CS.CHR_SEQ_ID,
    REFSEQ_NAME,REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION, SEQ_ROLE,CHR_SEQ_NAME,
     ASSEMBLY_UNIT,NUCL,CHR_POS As POSITION,SCIENTIFIC_NAME,TAX_ID
    FROM   CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP,TAXON T
    WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
    AND T.TAXON_ID = C.TAXON_ID
    AND TAX_ID = '9606'
	AND (CHR_SEQ_NAME = '".$CHR."' OR REFSEQ_NAME = '".$CHR."' OR  GENBANK_NAME= '".$CHR."' )
	AND CHR_POS = ".$CHR_POS);
	return $res[0];
}

function searchChrPositionFromTrList($LIST)
{
	$res=runQuery("SELECT CSP.CHR_SEQ_POS_ID, C.CHR_ID,CHR_NUM, CS.CHR_SEQ_ID,
    REFSEQ_NAME,REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION, SEQ_ROLE,CHR_SEQ_NAME,
     ASSEMBLY_UNIT,CSP.NUCL,CHR_POS As POSITION,SCIENTIFIC_NAME,TAX_ID
    FROM   CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP,TAXON T,TRANSCRIPT_POS TP
    WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
    AND T.TAXON_ID = C.TAXON_ID
    AND TP.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID AND TRANSCRIPT_POS_ID IN (".implode(',',$LIST).')');
    $data=array();
    foreach ($res as $line)$data[$line['TRANSCRIPT_POS_ID']]=$line;
	return $data;
}

function searchChrPositionFromTr($TRANSCRIPT_POS_ID)
{
	$res=runQuery("SELECT CSP.CHR_SEQ_POS_ID, C.CHR_ID,CHR_NUM, CS.CHR_SEQ_ID,
    REFSEQ_NAME,REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION, SEQ_ROLE,CHR_SEQ_NAME,
     ASSEMBLY_UNIT,CSP.NUCL,CHR_POS As POSITION,SCIENTIFIC_NAME,TAX_ID
    FROM   CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP,TAXON T,TRANSCRIPT_POS TP
    WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
    AND T.TAXON_ID = C.TAXON_ID
    AND TP.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID AND TRANSCRIPT_POS_ID=".$TRANSCRIPT_POS_ID);
	return $res[0];
}
function getTranscriptFromChr($CHR_SEQ_ID,$CHR_SEQ_POS_ID,$CHR_POSITION)
{
$res=runQuery("SELECT transcript_id FROM transcript t, gene_seq gs
where gs.gene_seq_Id = t.gene_seq_id AND gs.chr_seq_id = ".$CHR_SEQ_ID." AND gs.start_pos <= ".$CHR_POSITION." AND gs.end_pos >= ".$CHR_POSITION);
$DATA=array();
foreach ($res as $line)
{
    $res2=runQuery("SELECT * 
    FROM gene_seq gs 
    LEFT JOIN gn_entry ge ON ge.gn_entry_id = gs.gn_entry_id,
     transcript t,
     transcript_pos tp
     WHERE tp.transcript_id = t.transcript_id
     AND t.gene_seq_id = gs.gene_seq_Id
     AND chr_seq_pos_id = ".$CHR_SEQ_POS_ID. "
     AND tp.transcript_id = ".$line['TRANSCRIPT_ID']);
    foreach ($res2 as $l)$DATA[]=$l;
}
return $DATA;
    
}
function getVariantsFromChr($CHR_SEQ_ID,$POSITION,$OFFSET=0)
{
    $res=runQuery("SELECT * 
    FROM chr_seq_pos csp_v, variant_position vp, variant_entry ve
     where  csp_v.chr_seq_pos_Id = vp.chr_Seq_pos_Id 
     AND vp.variant_entry_id = ve.variant_entry_Id 
     AND chr_pos >=".($POSITION-$OFFSET)." AND chr_pos <= ".($POSITION+$OFFSET)." AND chr_seq_id=".$CHR_SEQ_ID);
     return $res;
}


function getChrSequence($TAXON,$STRAND,$CHR,$POSITION,$RANGE=100)
{
    $query="SELECT  chr_seq_pos_id, nucl, chr_pos
    FROM taxon t, genome_assembly g, chr_seq cs, chr_seq_pos gs
    
    where t.taxon_id = g.taxon_id 
    AND g.genome_assembly_id = cs.genome_assembly_id 
    AND cs.chr_seq_id = gs.chr_seq_Id 
    AND chr_pos >= ".($POSITION-$RANGE)." AND chr_pos <=".($POSITION+$RANGE)."
    AND tax_Id = '".$TAXON."'
    AND (refseq_name = '".$CHR."' OR genbank_name = '".$CHR."' OR chr_seq_name = '".$CHR."') ORDER BY chr_pos ASC";
    $DATA=array();
    $res=runQuery($query); 
    foreach ($res as $line)$DATA[$line['CHR_SEQ_POS_ID']]=array('NUCL'=>$line['NUCL'],'CHR_POS'=>$line['CHR_POS']);
    
if ($DATA!=array())
{
    
    $query = "SELECT rsid,chr_seq_pos_id, va_r.variant_seq as ref_all, va.variant_seq as alt_all, variant_name, so_name as SNP_TYPE
	FROM  variant_entry ve, variant_position vp, variant_change VC, variant_allele va_r, variant_allele va, VARIANT_TYPE VT
	LEFT JOIN SO_ENTRY SO ON SO.SO_ENTRY_ID = VT.SO_ENTRY_ID
	where ve.variant_entry_id = vp.variant_entry_id
    ANd vp.variant_position_id = vc.variant_position_id 
    AND  vt.variant_Type_Id = vc.variant_type_id
    AND va.variant_allele_id = alt_all
    AND va_r.variant_allele_id = ref_all
    AND chr_seq_pos_Id IN (".implode(',',array_keys($DATA)).')';
    $res=runQuery($query); 
    foreach ($res as $line)
    {$P=$line['CHR_SEQ_POS_ID'];unset($line['CHR_SEQ_POS_ID']);
        
        
        $DATA[$P]['VARIANT'][]=$line;
    }



    $query="SELECT tr.transcript_id, transcript_name,transcript_version,strand, symbol, gene_id,gene_seq_name
    FROM taxon t, genome_assembly g, chr_seq cs, transcript tr,gene_seq gs
    LEFT JOIN gn_entry ge ON  ge.gn_entry_Id = gs.gn_entry_Id
    where t.taxon_id = g.taxon_id 
    AND g.genome_assembly_id = cs.genome_assembly_id 
    AND cs.chr_seq_id = gs.chr_seq_Id 
    AND tr.gene_seq_id = gs.gene_seq_Id
    AND ((gs.end_pos>=".($POSITION-$RANGE)." AND gs.start_pos <=".($POSITION+$RANGE)." AND gs.end_pos<=".($POSITION+$RANGE)." AND gs.start_pos >=".($POSITION-50000).")
    OR (gs.end_pos>=".($POSITION+$RANGE)." AND gs.start_pos <=".($POSITION+$RANGE).")
    OR (gs.end_pos>=".($POSITION-$RANGE)." AND gs.start_pos <=".($POSITION-$RANGE).")
    )
    AND tax_Id = '".$TAXON."'
    AND (refseq_name = '".$CHR."' OR genbank_name = '".$CHR."' OR chr_seq_name = '".$CHR."') ORDER BY gs.start_pos ASC";
    
    $res=runQuery($query);
    $LIST_TR=array();
    foreach ($res as $line)    $LIST_TR[$line['TRANSCRIPT_ID']]=$line;;
    if ($LIST_TR!=array())
    {
        $res=runQuery("SELECT transcript_id, nucl,seq_pos,exon_id,chr_seq_pos_id,transcript_pos_type FROM TRANSCRIPT_POS TP
        LEFT JOIN TRANSCRIPT_POS_TYPE TPT ON TP.seq_pos_type_id=TPT.transcript_pos_type_id
         WHERE  transcript_id IN (".implode(',',array_keys($LIST_TR)).') ORDER BY transcript_id,seq_pos ASC');
         $START=false;
        foreach ($res as $line)
        {
             $START=isset($DATA[$line['CHR_SEQ_POS_ID']]);
            
            if (!$START)continue;
            $LIST_TR[$line['TRANSCRIPT_ID']]['SEQUENCE'][$line['CHR_SEQ_POS_ID']]=$line;
        }
       
    }
    $DATA['TRANSCRIPT']=$LIST_TR;



}

    return $DATA;
}

function getClinVarFromTr($TRANSCRIPT_ID,$RANGE_RNA)
{
    $res=runQuery("SELECT * FROM transcript_pos where   transcript_id = ".$TRANSCRIPT_ID." AND  seq_pos >=".$RANGE_RNA['START']." AND seq_pos<=".$RANGE_RNA['END']);
    $MAP=array();
    $DATA=array();
    foreach ($res as $line) $MAP_TR[$line['CHR_SEQ_POS_ID']]=$line['SEQ_POS'];

    if ($MAP_TR==array())return array();
    $CHUNKS=array_chunk(array_keys($MAP_TR),10);
    
    foreach ($CHUNKS as $CHUNK)
    {
    $res=runQuery("SELECT * FROM clinical_Variant_entry cve
    LEFT JOIN clinical_variant_review_status cvr ON cve.clinical_variant_review_Status=cvr.clinvar_review_status_id 
    LEFT JOIN clinical_variant_type cvt ON cve.clinical_variant_Type_id=cvt.clinical_variant_Type_id,
    clinical_variant_map cvm, 
    variant_Entry ve, 
    variant_position vp 
    where cve.clinvar_entry_id = cvm.clinvar_entry_id 
    
    
    AND cvm.variant_entry_Id = ve.variant_entry_id 
    AND ve.variant_entry_id = vp.variant_entry_ID
    AND chr_seq_pos_id IN (".implode(',',$CHUNK).')');
    foreach ($res as $E)
    {
        $DATA[$E['CLINVAR_ENTRY_ID']]['INFO']=$E;
    }
    }
    if ($DATA==array())return;
    $res=runQuery("SELECT clinvar_entry_id,cvs.clinvar_submission_id, scv_id, collection_method,submitter,interpretation,last_evaluation_date,clin_sign,clin_sign_desc,clinvar_review_status_name,clinvar_review_status_Score
    FROM clinical_variant_submission cvs, clinical_significance cs, clinical_variant_review_status cvr 
    WHERE cvs.clin_sign_id = cs.clin_sign_id 
    AND cvs.clinical_variant_review_status=cvr.clinvar_review_status_id  
    AND clinvar_entry_id  IN (".implode(',',array_keys($DATA)).')');
    foreach ($res as $line)$DATA[$line['CLINVAR_ENTRY_ID']]['SUBMISSION'][]=$line;
   
    $TMP=array();
    foreach ($DATA as &$ENTRY)
    $TMP[$MAP_TR[$ENTRY['INFO']['CHR_SEQ_POS_ID']]][]=$ENTRY;
    return $TMP;
}

function getProteinInfoFromTr($TRANSCRIPT_ID,$RANGE_RNA,$WITH_FEAT=false)
{
    $res=runQuery("SELECT * FROM transcript_pos where   transcript_id = ".$TRANSCRIPT_ID." AND  seq_pos >=".$RANGE_RNA['START']." AND seq_pos<=".$RANGE_RNA['END']);
    $MAP=array();
    $DATA=array();
    foreach ($res as $line) $MAP_TR[$line['TRANSCRIPT_POS_ID']]=$line['SEQ_POS'];


    $res = runQuery("SELECT * FROM TRANSCRIPT T, TR_PROTSEQ_AL TUA, PROT_SEQ US WHERE
	T.TRANSCRIPT_ID = TUA.TRANSCRIPT_ID
    AND TUA.PROT_SEQ_ID = US.PROT_SEQ_ID AND T.TRANSCRIPT_ID =" . $TRANSCRIPT_ID);
    $MAP_AL=array();
    foreach ($res as $line)
    {
        $MAP_AL[$line['TR_PROTSEQ_AL_ID']]=$line['PROT_SEQ_ID'];
        $DATA['PROT'][$line['PROT_SEQ_ID']]['INFO']=$line;
        $SEL_POS[$line['PROT_SEQ_ID']]=array(100000000,0);

    }
    if ($DATA['PROT']!=array())
    {
    $res=runQuery("SELECT * FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN (". implode(',', array_keys($DATA['PROT'])) . ') ORDER BY PROT_SEQ_ID, POSITION ASC');
    $MAP_POS=array();
    foreach ($res as $line)
    {
        $MAP_POS[$line['PROT_SEQ_POS_ID']]=array($line['PROT_SEQ_ID'],$line['POSITION'],false);
    $DATA['PROT'][$line['PROT_SEQ_ID']]['SEQ'][$line['POSITION']]=$line;
    }
    
}
if ($MAP_AL!=array() && $MAP_TR!=array())
{
$query= 'SELECT * FROM tr_protseq_pos_al 
WHERE TR_PROTSEQ_AL_ID IN (' . implode(',', array_keys($MAP_AL)) . ') 
AND TRANSCRIPT_POS_ID IN (' . implode(',', array_keys($MAP_TR)) . ')';

    $res = runQuery($query);
    
    
    foreach ($res as $line) {
        $UP = &$MAP_POS[$line['PROT_SEQ_POS_ID']];
        $UP[2]=true;
        $SEL_POS[$UP[0]][0]=min($SEL_POS[$UP[0]][0],$UP[1]);
        $SEL_POS[$UP[0]][1]=max($SEL_POS[$UP[0]][1],$UP[1]);
        $DATA['TRANSLATION'][$line['TR_PROTSEQ_AL_ID']][$line['TRANSCRIPT_POS_ID']] = array($UP[0], $UP[1], $line['TRIPLET_POS']);
    }
    

    foreach ($MAP_POS as &$MAP_INFO)
    {
        if ($MAP_INFO[2]==false)unset($DATA['PROT'][$MAP_INFO[0]]['SEQ'][$MAP_INFO[1]]);
    }
}

if ($DATA['PROT']!=array() && $WITH_FEAT){
    $res=runQuery("SELECT feat_value,feat_name,prot_seq_id,start_pos,end_pos 
    FROM prot_feat  pf, prot_feat_type pft 
    where pf.prot_feat_type_id = pft.prot_feat_type_id
    AND prot_seq_id IN (".implode(',',array_keys($DATA['PROT'])).')');
    foreach ($res as $line)
    {
        foreach ($DATA['PROT'] as $PROT_SEQ_ID=>&$AL)
        {
            if ($PROT_SEQ_ID!=$line['PROT_SEQ_ID'])continue;
            $FOUND=false;
            for ($I=$line['START_POS'];$I<=$line['END_POS'];++$I)
            {
                if (!isset($AL['SEQ'][$I]))continue;
                
                $FOUND=true;
                break;
            }
            if (!$FOUND)continue;
            $AL['PROT_INFO'][]=$line;
        }
    }
}
return $DATA;


}


function getProteinOrthoFromTr($TRANSCRIPT_ID,$RANGE_RNA)
{
    $res=runQuery("SELECT GN_ENTRY_ID FROM GENE_SEQ GS, TRANSCRIPT T WHERE t.gene_seq_id = gs.gene_seq_Id AND Transcript_Id = ".$TRANSCRIPT_ID);
    if (!isset($res[0]['GN_ENTRY_ID']))throw new Exception('Unable to find gene');
    $GN_ENTRY_ID=$res[0]['GN_ENTRY_ID'];
    $res = runQuery("select DISTINCT gn_entry_r_id, symbol,full_name,gene_id, gn_entry_Id, scientific_name,tax_id FROM gn_rel gr, mv_gene_sp m where m.gn_entry_id = gr.gn_entry_c_id AND  gn_entry_r_id =" . $GN_ENTRY_ID );

    $DATA=array();
    
    foreach ($res as $line) { 
        $DATA['ORTHO'][$line['GN_ENTRY_ID']] = $line;
    }



    
    $res = runQuery("SELECT transcript_pos_id, nucl,seq_pos FROM transcript_pos where transcript_id = " . $TRANSCRIPT_ID . ' AND seq_pos >= ' . $RANGE_RNA['START'] . ' AND seq_pos <=' . $RANGE_RNA['END'] );
    $REF_TR_POS_ID = array();
    foreach ($res as $line) $REF_TR_POS_ID[$line['TRANSCRIPT_POS_ID']] = array('INI' => array($line['NUCL'], $line['SEQ_POS']));
    echo '<pre>';

    if (!isset($DATA['ORTHO']))return $DATA;


    $query= "sELECT t_o.transcript_id as o_transcript_id, t_o.transcript_name as o_transcript_name,
    gs_o.gn_entry_id as o_gn_entry_Id,tpa_o.tr_protseq_al_id as o_tpsa, tpa_o.perc_sim as perc_sim_o_tpsa,tpa_o.perc_iden as perc_iden_o_tpsa, ps_o.prot_seq_id as o_prot_seq_id, ps_o.iso_id as o_iso_id,ps_o.iso_name as o_iso_name, psa.*, ps.iso_id as r_iso_id, ps.iso_name as r_iso_name, tpa.tr_protseq_al_id as tpsa, tpa.perc_sim as perc_sim_tpsa,tpa.perc_iden as perc_iden_tpsa FROM gene_seq gs_o, transcript t_o, tr_protseq_al tpa_o, prot_seq ps_o, prot_seq_al psa, prot_seq ps, tr_protseq_al tpa, transcript t where                           
    t.transcript_id = tpa.transcript_id
    AND tpa.prot_seq_id = ps.prot_seq_id
    AND ps.prot_seq_id = psa.prot_seq_comp_id 
    AND psa.prot_seq_ref_id = ps_o.prot_seq_id
    AND ps_o.prot_seq_Id = tpa_o.prot_seq_id
    AND tpa_o.transcript_id = t_o.transcript_id
    AND t_o.gene_seq_Id = gs_o.gene_seq_Id
    ANd t.transcript_id = " . $TRANSCRIPT_ID . " AND gn_entry_id IN (" . implode(',', array_keys($DATA['ORTHO'])) . ")";
    $res = runQuery($query);
 if ($res==array())return $DATA;
    $PAIRS = array();

    $query = 'SELECT tppa_r.tr_protseq_al_id as r_tpsa, tppa_c.tr_protseq_al_Id as o_tpsa, tp_r.transcript_pos_id as r_tr_pos_id, tp_r.nucl as r_tr_nucl, tp_r.seq_pos as r_tr_seq_pos, tppa_r.triplet_pos as r_tr_triplet_pos, psp_r.letter as r_prot_aa, psp_r.position as r_prot_pos,
    psp_c.position as c_prot_pos ,psp_c.letter as c_prot_aa, tppa_c.triplet_pos as c_tr_triplet_pos, tp_c.seq_pos as c_tr_seq_pos, tp_c.nucl as c_tr_nucl,  tp_c.transcript_pos_id as c_tr_pos_id,tp_c.transcript_id as c_transcript_id
    FROM 
    transcript_pos tp_r,
    tr_protseq_pos_al tppa_r,
    prot_seq_pos psp_r,
    prot_seq_al_seq psap,
    prot_seq_pos psp_c,
    tr_protseq_pos_al tppa_c,
    transcript_pos tp_c
    WHERE
    tp_r.transcript_pos_id = tppa_r.transcript_pos_id AND
    tppa_r.prot_seq_pos_id = psp_r.prot_seq_pos_id AND 
    psp_r.prot_seq_pos_Id = psap.prot_seq_id_comp AND
    psap.prot_seq_id_ref = psp_c.prot_seq_pos_Id AND
    psp_c.prot_seq_pos_id = tppa_c.prot_Seq_pos_id AND
    tppa_c.transcript_pos_id = tp_c.transcript_pos_id 
    AND tp_r.transcript_pos_id IN (' . implode(',', array_keys($REF_TR_POS_ID)) . ')
    AND tppa_r.triplet_pos=tppa_c.triplet_pos
    AND (tppa_r.tr_protseq_al_id, tppa_c.tr_protseq_al_Id) IN (';

    foreach ($res as $line) {

        $R_TPSA = $line['TPSA'];
        $O_TPSA = $line['O_TPSA'];
        $query .= '(' . $R_TPSA . ',' . $O_TPSA . '),';
        $PAIRS[$R_TPSA . '|' . $O_TPSA]['INFO'] = $line;
    }
    $query = substr($query, 0, -1) . ') ORDER BY tppa_r.tr_protseq_al_id, tppa_c.tr_protseq_al_Id, tp_r.seq_pos ASC ';
    $res = runQuery($query);
    foreach ($res as $line) {
        $PAIRS[$line['R_TPSA'] . '|' . $line['O_TPSA']]['ALIGNMENT'][$line['R_TR_SEQ_POS']] = $line;
    }

    $DATA['ALIGNMENTS']= $PAIRS;
return $DATA;

}


function getProteinSimFromTr($TRANSCRIPT_ID,$RANGE_RNA)
{
    $res=runQuery("SELECT GN_ENTRY_ID FROM GENE_SEQ GS, TRANSCRIPT T WHERE t.gene_seq_id = gs.gene_seq_Id AND Transcript_Id = ".$TRANSCRIPT_ID);
    if (!isset($res[0]['GN_ENTRY_ID']))throw new Exception('Unable to find gene');
    $GN_ENTRY_ID=$res[0]['GN_ENTRY_ID'];

    
    $res = runQuery("SELECT transcript_pos_id, nucl,seq_pos FROM transcript_pos where transcript_id = " . $TRANSCRIPT_ID . ' AND seq_pos >= ' . $RANGE_RNA['START'] . ' AND seq_pos <=' . $RANGE_RNA['END'] );
    $REF_TR_POS_ID = array();
    foreach ($res as $line) $REF_TR_POS_ID[$line['TRANSCRIPT_POS_ID']] = array('INI' => array($line['NUCL'], $line['SEQ_POS']));
    


    $DATA=array();

    $query= "sELECT t_o.transcript_id as o_transcript_id, t_o.transcript_name as o_transcript_name,
    gs_o.gn_entry_id as o_gn_entry_Id,tpa_o.tr_protseq_al_id as o_tpsa, tpa_o.perc_sim as perc_sim_o_tpsa,tpa_o.perc_iden as perc_iden_o_tpsa, ps_o.prot_seq_id as o_prot_seq_id, ps_o.iso_id as o_iso_id,ps_o.iso_name as o_iso_name, psa.*, ps.iso_id as r_iso_id, ps.iso_name as r_iso_name, tpa.tr_protseq_al_id as tpsa, tpa.perc_sim as perc_sim_tpsa,tpa.perc_iden as perc_iden_tpsa FROM gene_seq gs_o, transcript t_o, tr_protseq_al tpa_o, prot_seq ps_o, prot_seq_al psa, prot_seq ps, tr_protseq_al tpa, transcript t where                           
    t.transcript_id = tpa.transcript_id
    AND tpa.prot_seq_id = ps.prot_seq_id
    AND ps.prot_seq_id = psa.prot_seq_comp_id 
    AND psa.prot_seq_ref_id = ps_o.prot_seq_id
    AND ps_o.prot_seq_Id = tpa_o.prot_seq_id
    AND tpa_o.transcript_id = t_o.transcript_id
    AND t_o.gene_seq_Id = gs_o.gene_seq_Id
    ANd t.transcript_id = " . $TRANSCRIPT_ID ;
    $res = runQuery($query);
    
    
    if ($res==array())return $DATA;
    $LIST_GN=array($GN_ENTRY_ID=>array());
    $MAP=array();
    foreach ($res as $line)
    {
        if ($line['O_GN_ENTRY_ID']!='')$LIST_GN[$line['O_GN_ENTRY_ID']]=array();
        $MAP[$line['O_TRANSCRIPT_ID']]=$line['O_GN_ENTRY_ID'];
    }

    if ($LIST_GN!=array())
    {
        $res2=runQuery("SELECT DISTINCT gene_id, gn_entry_Id, tax_id,symbol,full_name FROM mv_gene_sp WHERE gn_Entry_id IN (".implode(',',array_keys($LIST_GN)).')');

        foreach ($res2 as $l2)
        {
            $LIST_GN[$l2['GN_ENTRY_ID']]=$l2;
        }
        
        $R_TAX_ID=$LIST_GN[$GN_ENTRY_ID]['TAX_ID'];
        foreach ($LIST_GN as $O_GN_ENTRY_ID=>&$GN_INFO)
        {

            if ($O_GN_ENTRY_ID==$GN_ENTRY_ID || $GN_INFO['TAX_ID']!=$R_TAX_ID
            )unset($LIST_GN[$O_GN_ENTRY_ID]);
        }
        
    }
    

    

    $PAIRS = array();

    $query = 'SELECT tppa_r.tr_protseq_al_id as r_tpsa, tppa_c.tr_protseq_al_Id as o_tpsa,
     tp_r.transcript_pos_id as r_tr_pos_id, tp_r.nucl as r_tr_nucl, tp_r.seq_pos as r_tr_seq_pos, 
     tppa_r.triplet_pos as r_tr_triplet_pos, psp_r.letter as r_prot_aa, psp_r.position as r_prot_pos,
    psp_c.position as c_prot_pos ,psp_c.letter as c_prot_aa, tppa_c.triplet_pos as c_tr_triplet_pos,
     tp_c.seq_pos as c_tr_seq_pos, tp_c.nucl as c_tr_nucl,  tp_c.transcript_pos_id as c_tr_pos_id,tp_c.transcript_id as c_transcript_id
    FROM 
    transcript_pos tp_r,
    tr_protseq_pos_al tppa_r,
    prot_seq_pos psp_r,
    prot_seq_al_seq psap,
    prot_seq_pos psp_c,
    tr_protseq_pos_al tppa_c,
    transcript_pos tp_c
    WHERE
    tp_r.transcript_pos_id = tppa_r.transcript_pos_id AND
    tppa_r.prot_seq_pos_id = psp_r.prot_seq_pos_id AND 
    psp_r.prot_seq_pos_Id = psap.prot_seq_id_comp AND
    psap.prot_seq_id_ref = psp_c.prot_seq_pos_Id AND
    psp_c.prot_seq_pos_id = tppa_c.prot_Seq_pos_id AND
    tppa_c.transcript_pos_id = tp_c.transcript_pos_id 
    AND tp_r.transcript_pos_id IN (' . implode(',', array_keys($REF_TR_POS_ID)) . ')
    AND tppa_r.triplet_pos=tppa_c.triplet_pos
    AND (tppa_r.tr_protseq_al_id, tppa_c.tr_protseq_al_Id) IN (';
    $HAS_DATA=false;
    foreach ($res as $line) {

        if (!isset($LIST_GN[$MAP[$line['O_TRANSCRIPT_ID']]]))continue;
        $R_TPSA = $line['TPSA'];
        $O_TPSA = $line['O_TPSA'];
        $query .= '(' . $R_TPSA . ',' . $O_TPSA . '),';
        $HAS_DATA=true;
        $PAIRS[$R_TPSA . '|' . $O_TPSA]['INFO'] = $line;
    }

    if ($HAS_DATA)
    {
    $query = substr($query, 0, -1) . ') ORDER BY tppa_r.tr_protseq_al_id, tppa_c.tr_protseq_al_Id, tp_r.seq_pos ASC ';
    $res = runQuery($query);
    foreach ($res as $line) {
        $PAIRS[$line['R_TPSA'] . '|' . $line['O_TPSA']]['ALIGNMENT'][$line['R_TR_SEQ_POS']] = $line;
    }

    $res = runQuery("select DISTINCT gn_entry_id, symbol,full_name,gene_id, scientific_name,tax_id 
    FROM  mv_gene_sp m where gn_entry_id IN (" . implode(',',array_keys($LIST_GN)).')' );

    $DATA=array();
    
    foreach ($res as $line) { 
        $DATA['GENES'][$line['GN_ENTRY_ID']] = $line;
    }

    }
    $DATA['ALIGNMENTS']= $PAIRS;
return $DATA;

}

function getChrGeneRange($TAXON,$STRAND,$CHR,$POSITION,$RANGE=50000)
{

    $query="SELECT ge.*, gs.*
    FROM taxon t, genome_assembly g, chr_seq cs, gene_seq gs
    LEFT JOIN gn_entry ge ON  ge.gn_entry_Id = gs.gn_entry_Id
    where t.taxon_id = g.taxon_id 
    AND g.genome_assembly_id = cs.genome_assembly_id 
    AND cs.chr_seq_id = gs.chr_seq_Id 
    AND ((gs.end_pos>=".($POSITION-$RANGE)." AND gs.start_pos <=".($POSITION+$RANGE)." AND gs.end_pos<=".($POSITION+$RANGE)." AND gs.start_pos >=".($POSITION-50000).")
    OR (gs.end_pos>=".($POSITION+$RANGE)." AND gs.start_pos <=".($POSITION+$RANGE).")
    OR (gs.end_pos>=".($POSITION-$RANGE)." AND gs.start_pos <=".($POSITION-$RANGE).")
    )
    AND tax_Id = '".$TAXON."'
    AND (refseq_name = '".$CHR."' OR genbank_name = '".$CHR."' OR chr_seq_name = '".$CHR."') ORDER BY gs.start_pos ASC";
    
    $res=runQuery($query);
    
    if ($res !=array())return $res;
    return null;


}


function getTranscriptMutation($TRANSCRIPT_ID,$RANGE_RNA)
{
    $res=runQuery("SELECT * FROM transcript_pos where   transcript_id = ".$TRANSCRIPT_ID." AND  seq_pos >=".$RANGE_RNA['START']." AND seq_pos<=".$RANGE_RNA['END']);
    $MAP=array();
    $DATA=array('SEQ'=>array(),'STUDY'=>array());
    foreach ($res as $line) $MAP[$line['CHR_SEQ_POS_ID']]=$line['SEQ_POS'];
    
if ($MAP!=array())
{
    $CHUNKS=array_chunk(array_keys($MAP),10);
    $MAP_ALL=array();
    foreach ($CHUNKS as $CHUNK)
    {
    $query="SELECT DISTINCT chr_seq_pos_Id, alt_all,ref_count,alt_count,variant_freq_study_id
    FROM  variant_position vp,variant_change vc, variant_frequency vf
   where vp.variant_position_id = vc.variant_position_id
   AND vc.variant_change_id=vf.variant_change_id
   AND vc.variant_change_id = vf.variant_change_id
   AND vp.chr_seq_pos_id IN (".implode(",",$CHUNK).')';
   
    $res=runQuery($query);
    
    foreach ($res as $line)
    {
        if ($line['ALT_COUNT']==0)continue;
        if ($line['ALT_ALL']!='')$MAP_ALL[$line['ALT_ALL']]='';
        else {$line['VARIANT_SEQ']='';unset($line['ALT_ALL']);}
        $DATA['SEQ'][$line['VARIANT_FREQ_STUDY_ID']][$MAP[$line['CHR_SEQ_POS_ID']]][]=$line;
        $DATA['STUDY'][$line['VARIANT_FREQ_STUDY_ID']]=$line;
        
    }
    }
    if ($MAP_ALL!=array())
    {
        $res=runQuery("SELECT * FROM variant_allele where variant_allele_id IN (".implode(',',array_keys($MAP_ALL)).')');
        foreach ($res as $line)$MAP_ALL[$line['VARIANT_ALLELE_ID']]=$line['VARIANT_SEQ'];
        foreach ($DATA as &$V)
        foreach ($V as &$R)
        foreach ($R as &$M)
        if (is_array($M))
        foreach ($M as &$C)
        {
        
            if ($C['ALT_ALL']!='')$C['VARIANT_SEQ']=$MAP_ALL[$C['ALT_ALL']];
            else $C['VARIANT_SEQ']='';
            unset($C['ALT_ALL']);
        }
    }
}


    if ($DATA['STUDY']!=array())
    {
        $res=runQuery("SELECT * FROM variant_freq_study where variant_freq_study_id IN (".implode(',',array_keys($DATA['STUDY'])).')');
        foreach ($res as $line)$DATA['STUDY'][$line['VARIANT_FREQ_STUDY_ID']]=$line;
    }
    
    return $DATA;

}

function getChrTaxonInfo($TAXON)
{

    $query="SELECT scientific_name,chr_num,tax_Id, cs.chr_seq_Id,chr_seq_name, refseq_name,refseq_version,genbank_name,genbank_version,seq_Role,seq_len
    FROM taxon t, chromosome c, genome_assembly g, chr_seq cs
    where t.taxon_id = g.taxon_id 
    AND cs.chr_id = c.chr_id
    AND g.genome_assembly_id = cs.genome_assembly_id 
    AND tax_Id = '".$TAXON."' ORDER BY seq_role, chr_num ";
    
    $res=runQuery($query);
    return $res;

}

function getChrInfo($TAXON,$CHR,$POSITION)
{

    $query="SELECT scientific_name,tax_Id, chr_seq_pos_Id,cs.chr_seq_Id,chr_seq_name, refseq_name,refseq_version,genbank_name,genbank_version, chr_pos,nucl 
    FROM taxon t, genome_assembly g, chr_seq cs, chr_seq_pos csp 
    where t.taxon_id = g.taxon_id 
    AND g.genome_assembly_id = cs.genome_assembly_id 
    AND cs.chr_seq_id = csp.chr_seq_Id 
    AND chr_pos = ".$POSITION."
    AND tax_Id = '".$TAXON."'
    AND (refseq_name = '".$CHR."' OR genbank_name = '".$CHR."' OR chr_seq_name = '".$CHR."')";
    
    $res=runQuery($query);
    
    if ($res !=array())return $res[0];
    return null;

}

/**
 * @param string $GENE_SYMBOL
 * @param bool $IS_PRIMARY
 * @return array
 * @throws Exception
 */
function gene_portal_gene($GENE_SYMBOL, $IS_PRIMARY = false)
{
    global $DB_CONN;
    $GS = $DB_CONN->quote($GENE_SYMBOL);

    $res = runQuery("SELECT tax_id FROM taxon t, genome_Assembly g where g.taxon_id =t.taxon_id");
    $list = array("'9606'");
    $str = '';
    $n = 1;
    foreach ($res as $l) {
        $list[] = "'" . $l['TAX_ID'] . "'";
        if ($l['TAX_ID'] == '9606') continue;
        $n++;
        $str .= ' when tax_id = \'' . $l['TAX_ID'] . '\' then ' . $n . "\n";
    }
  
    $query = "
    SELECT * FROM (
        SELECT DISTINCT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
        FROM mv_gene_sp
        WHERE " . (($IS_PRIMARY) ? "SYMBOL=" . $GS : "LOWER(SYN_VALUE) = LOWER(" . $GS . ")") . "
            AND TAX_ID IN (" . implode(',', $list) . ")
        ) Sub
        order by (case
                 when tax_id='9606' then 1
                 " . $str . " end) asc, TAX_ID ASC
    ";

 
$res = runQuery($query);

if ($res==array())
{
    $GS = $DB_CONN->quote($GENE_SYMBOL . ((!$IS_PRIMARY ? '%' : '')));
    $query = "
        SELECT * FROM (
            SELECT DISTINCT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
		    FROM mv_gene_sp
            WHERE " . (($IS_PRIMARY) ? "SYMBOL=" . $GS : "LOWER(SYN_VALUE) LIKE LOWER(" . $GS . ")") . "
                AND TAX_ID IN (" . implode(',', $list) . ")
	        ) Sub
            order by (case
                     when tax_id='9606' then 1
                     " . $str . " end) asc, TAX_ID ASC
        ";
    $res = runQuery($query);
        }
    //print_r($res);exit;
    $LIST_ID = array();
    $MAP = array();
    foreach ($res as $K => $line) {
        $LIST_ID[] = $line['GN_ENTRY_ID'];
        $MAP[$line['GN_ENTRY_ID']] = $K;
    }
    $CHUNKS = array_chunk($LIST_ID, 1000);
    foreach ($CHUNKS as $CHUNK) {
        $query = "
            SELECT STRING_AGG(SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as syns, GN_ENTRY_ID
            FROM gn_syn GS, gn_syn_map GSM
            WHERE GSM.GN_ENTRY_ID IN (" . implode(',', $CHUNK) . ")
                AND GS.GN_SYN_ID = GSM.GN_SYN_ID
                AND SYN_TYPE='S'
            GROUP BY GN_ENTRY_ID";
        $res2 = runQuery($query);
        foreach ($res2 as $line) {
            $res[$MAP[$line['GN_ENTRY_ID']]]['SYN'] = $line['SYNS'];
        }
    }

    if (count($res) == 0) {
        $GS = $DB_CONN->quote('%' . $GENE_SYMBOL .  '%');
        $query = "
        SELECT * FROM (
            SELECT DISTINCT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
		    FROM mv_gene_sp
            WHERE " . (($IS_PRIMARY) ? "SYMBOL LIKE " . $GS : "LOWER(SYN_VALUE) LIKE LOWER(" . $GS . ")") . "
                AND TAX_ID IN (" . implode(',', $list) . ")
	        ) Sub
            order by (case
                     when tax_id='9606' then 1
                    " . $str . " end) asc, TAX_ID ASC
        ";

        $res = runQuery($query);
        $LIST_ID = array();
        $MAP = array();
        foreach ($res as $K => $line) {
            $LIST_ID[] = $line['GN_ENTRY_ID'];
            $MAP[$line['GN_ENTRY_ID']] = $K;
        }
        $CHUNKS = array_chunk($LIST_ID, 1000);
        foreach ($CHUNKS as $CHUNK) {
            $query = "
            SELECT STRING_AGG(SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as syns, GN_ENTRY_ID
            FROM gn_syn GS, gn_syn_map GSM
            WHERE GSM.GN_ENTRY_ID IN (" . implode(',', $CHUNK) . ")
                AND GS.GN_SYN_ID = GSM.GN_SYN_ID
                AND SYN_TYPE='S'
            GROUP BY GN_ENTRY_ID";
            $res2 = runQuery($query);
            foreach ($res2 as $line) {
                $res[$MAP[$line['GN_ENTRY_ID']]]['SYN'] = $line['SYNS'];
            }
        }
    }

    return $res;
}



function gene_portal_transcript($TRANSCRIPT_NAME)
{
    $pos = strpos($TRANSCRIPT_NAME, '.');
    $VAL = $TRANSCRIPT_NAME;
    if ($pos !== false) {
        $VAL = substr($TRANSCRIPT_NAME, 0, $pos);
    }

    $res = runQuery("SELECT DISTINCT SYMBOL, FULL_NAME, GENE_ID, sp.GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID,TRANSCRIPT_NAME,TRANSCRIPT_VERSION
	FROM transcript t 
    LEFT JOIN gene_seq gs ON t.gene_seq_id = gs.gene_seq_id 
    LEFT JOIN  mv_gene_sp sp ON sp.gn_entry_id = gs.gn_entry_id
	WHERE  t.transcript_name = '" . $VAL . "'");
    return $res;
}

function gene_portal_rsid($RSID)
{
    $VAL = $RSID;
    if (substr($RSID, 0, 2) == 'rs') $VAL = substr($RSID, 2);
    if (!is_numeric($VAL)) return array();

    $res = runQuery("SELECT DISTINCT SYMBOL, FULL_NAME, GENE_ID, sp.GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID,rsid
	FROM mv_gene_sp sp, gene_seq gs, chr_seq_pos csp, variant_position vp, variant_entry ve
	WHERE gs.chr_seq_id =csp.chr_seq_id AND csp.chr_pos >= gs.start_pos 
	AND csp.chr_pos <= gs.end_pos 
	AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
	AND vp.variant_entry_id = ve.variant_entry_id
	AND sp.gn_entry_id = gs.gn_entry_id
	AND rsid = " . $VAL);
    return $res;
}

function getPreMRNASequence(int $CHR_SEQ_ID, int $START_POS, int $END_POS, string $STRAND = "+"): array
{
    $DATA = array();
    $query = "SELECT " . (($STRAND == "+") ? "NUCL" : "CPL as NUCL") . ",CHR_POS 
    FROM CHR_SEQ_POS C " . (($STRAND == "+") ?
        " WHERE " : ", DNA_REV D WHERE D.NUCL=C.NUCL AND ") .
        " CHR_SEQ_ID = " . $CHR_SEQ_ID . " AND CHR_POS >=" . $START_POS . " AND CHR_POS<=" . $END_POS;
    $res = runQuery($query);

    foreach ($res as $line) $DATA[$line['CHR_POS']] = strtoupper($line['NUCL']);

    if ($STRAND == "+") ksort($DATA);
    else krsort($DATA);

    return $DATA;
}



function getTranscriptSequenceRange($TRANSCRIPT_NAME, $START_POS, $END_POS, $TYPE)
{
    $pos = strpos($TRANSCRIPT_NAME, '.');
    if ($pos !== false) {
        $TRANSCRIPT_VERSION = substr($TRANSCRIPT_NAME, $pos + 1);
        $TRANSCRIPT_NAME = substr($TRANSCRIPT_NAME, 0, $pos);
        if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric", ERR_TGT_USR);
    }
    $DATA_TRANSCRIPT = array('SEQUENCE' => array());
    global $DB_CONN;
    $TRANSCRIPT_NAME = $DB_CONN->quote($TRANSCRIPT_NAME);

    $query = "select T.TRANSCRIPT_ID,TRANSCRIPT_POS_ID,TP.NUCL,SEQ_POS,SEQ_POS_TYPE_ID,EXON_ID,CHR_POS,C.CHR_SEQ_POS_ID, C.nucl as CHR_NUCL
			FROM  TRANSCRIPT_POS TP LEFT JOIN CHR_SEQ_POS C ON C.CHR_SEQ_POS_id = TP.CHR_SEQ_POS_ID,
			 TRANSCRIPT T
			WHERE T.TRANSCRIPT_ID=TP.TRANSCRIPT_ID  
			  AND TRANSCRIPT_NAME=" . $TRANSCRIPT_NAME;
    if ($TYPE == "RNA") $query .= ' AND SEQ_POS >=' . $START_POS . ' AND SEQ_POS <=' . $END_POS;
    else if ($TYPE == "DNA") $query .= ' AND CHR_POS >=' . $START_POS . ' AND CHR_POS <=' . $END_POS;
    else throw new Exception("Range type must be either DNA or RNA", ERR_TGT_SYS);

    if ($pos !== false) $query .= ' AND TRANSCRIPT_VERSION=\'' . $TRANSCRIPT_VERSION . "'";

    $TMP = runQuery($query . ' ORDER BY SEQ_POS ASC');
    $TRANSCRIPT_ID = null;
    $TRANSCRIPT_POS_ID = array();
    if ($TMP != array()) $TRANSCRIPT_ID = $TMP[0]['TRANSCRIPT_ID'];
    $ptypes_raw = runQuery("SELECT * FROM TRANSCRIPT_POS_TYPE");
    $PTYPE = array();
    foreach ($ptypes_raw as $PT)    $PTYPE[$PT['TRANSCRIPT_POS_TYPE_ID']] = $PT['TRANSCRIPT_POS_TYPE'];
    $CURR_POS_TYPE = 0;

    foreach ($TMP as $K => $POS_INFO) {
        $TRANSCRIPT_POS_ID[] = $POS_INFO['TRANSCRIPT_POS_ID'];
        $POS_INFO['TYPE'] = $PTYPE[$POS_INFO['SEQ_POS_TYPE_ID']];
        unset($POS_INFO['SEQ_POS_TYPE_ID']);
        $DATA_TRANSCRIPT['SEQUENCE'][$POS_INFO['CHR_POS']] = $POS_INFO;
    }

    if ($TRANSCRIPT_ID == null) return $DATA_TRANSCRIPT;

    $DATA_TRANSCRIPT['TRANSLATION'] = runQuery("SELECT * FROM TRANSCRIPT T, TR_PROTSEQ_AL TUA, PROT_SEQ US WHERE
	T.TRANSCRIPT_ID = TUA.TRANSCRIPT_ID
    AND TUA.PROT_SEQ_ID = US.PROT_SEQ_ID AND T.TRANSCRIPT_ID =" . $TRANSCRIPT_ID);

    $DATA_TRANSCRIPT['ALIGN'] = array();
    $LIST_UNSEQ = array();
    $LIST_POS = array();
    foreach ($DATA_TRANSCRIPT['TRANSLATION'] as &$entry) {
        $DATA_TRANSCRIPT['ALIGN'][$entry['TR_PROTSEQ_AL_ID']] = array();
        $LIST_UNSEQ[$entry['PROT_SEQ_ID']] = array();
    }
    if (count($LIST_UNSEQ) == 0) return $DATA_TRANSCRIPT;

    $tmp = runQuery('SELECT * FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN (' . implode(',', array_keys($LIST_UNSEQ)) . ') ORDER BY PROT_SEQ_ID, POSITION ASC');
    foreach ($tmp as $line) $LIST_POS[$line['PROT_SEQ_POS_ID']] = array($line['LETTER'], $line['POSITION']);

    $tmp = runQuery('SELECT * FROM tr_protseq_pos_al WHERE TR_PROTSEQ_AL_ID IN (' . implode(',', array_keys($DATA_TRANSCRIPT['ALIGN'])) . ') AND TRANSCRIPT_POS_ID IN (' . implode(',', $TRANSCRIPT_POS_ID) . ')');
    foreach ($tmp as $line) {
        $UP = &$LIST_POS[$line['PROT_SEQ_POS_ID']];
        $DATA_TRANSCRIPT['ALIGN'][$line['TR_PROTSEQ_AL_ID']][$line['TRANSCRIPT_POS_ID']] = array($UP[0], $UP[1], $line['TRIPLET_POS']);
    }

    return $DATA_TRANSCRIPT;
}

function getTranscriptsSequence($GN_ENTRY_ID)
{
    $query = "SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
			FROM  TRANSCRIPT T, GENE_SEQ GS, CHR_SEQ CS, GENOME_ASSEMBLY G
			WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
			AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
			AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID 
	  		AND GN_ENTRY_ID=" . $GN_ENTRY_ID;

    $TMP = runQuery($query);
    $DATA = array();
    foreach ($TMP as $l) $DATA[$l['TRANSCRIPT_ID']] = array('INFO' => $l, 'SEQ' => array());
    $query = 'SELECT TRANSCRIPT_POS_ID,NUCL,SEQ_POS, TRANSCRIPT_ID 
	FROM TRANSCRIPT_POS WHERE TRANSCRIPT_ID IN (' . implodE(',', array_keys($DATA)) . ') ORDER BY TRANSCRIPT_ID ASC,SEQ_POS ASC';
    $TMP = runQuery($query);
    foreach ($TMP as $l) $DATA[$l['TRANSCRIPT_ID']]['SEQ'][] = $l;
    return $DATA;
}


function getTranscriptToProtein($LIST_TRANSCRIPTS, $WITH_ALIGN = false)
{
    $res = array();
    if ($LIST_TRANSCRIPTS != array())
        $res['STAT'] = runQuery("SELECT * FROM TRANSCRIPT T, TR_PROTSEQ_AL TUA, PROT_SEQ US WHERE
	T.TRANSCRIPT_ID = TUA.TRANSCRIPT_ID
	AND TUA.PROT_SEQ_ID = US.PROT_SEQ_ID AND T.TRANSCRIPT_ID IN (" . implode(',', $LIST_TRANSCRIPTS) . ')');
    if (!$WITH_ALIGN) return $res;
    $LIST_ALIGN = array();
    $LIST_UNSEQ = array();
    $LIST_POS = array();
    foreach ($res['STAT'] as &$entry) {
        $LIST_ALIGN[$entry['TR_PROTSEQ_AL_ID']] = array();
        $LIST_UNSEQ[$entry['PROT_SEQ_ID']] = array();
    }
    if (count($LIST_UNSEQ) == 0) return $res;
    $tmp = runQuery('SELECT * FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN (' . implode(',', array_keys($LIST_UNSEQ)) . ') ORDER BY PROT_SEQ_ID, POSITION ASC');
    foreach ($tmp as $line) $LIST_POS[$line['PROT_SEQ_POS_ID']] = array($line['LETTER'], $line['POSITION']);

    $tmp = runQuery('SELECT * FROM tr_protseq_pos_al WHERE TR_PROTSEQ_AL_ID IN (' . implode(',', array_keys($LIST_ALIGN)) . ')');
    foreach ($tmp as $line) {
        $UP = &$LIST_POS[$line['PROT_SEQ_POS_ID']];
        $LIST_ALIGN[$line['TR_PROTSEQ_AL_ID']][$line['TRANSCRIPT_POS_ID']] = array($UP[0], $UP[1], $line['TRIPLET_POS']);
    }
    $res['ALIGN'] = $LIST_ALIGN;
    return $res;
}


function getTranscriptBoundaries($TRANSCRIPTS,$STRAND="+")
{


    $res = runQuery("SELECT min(CHR_POS) as MIN_POS, MAX(CHR_POS) as MAX_POS, TRANSCRIPT_POS_TYPE,EXON_ID,TRANSCRIPT_ID ,
    min(SEQ_POS) as MIN_TR_POS, MAX(SEQ_POS) as MAX_TR_POS
					FROM TRANSCRIPT_POS TP
					LEFT JOIN CHR_SEQ_POS CS ON CS.CHR_SEQ_POS_ID=TP.CHR_SEQ_POS_ID,  TRANSCRIPT_POS_TYPE TPT
					WHERE TRANSCRIPT_ID IN (" . implode(',', $TRANSCRIPTS) . ")
					AND TPT.TRANSCRIPT_POS_TYPE_ID = TP.SEQ_POS_TYPE_ID
					
					GROUP BY TRANSCRIPT_ID,EXON_ID,TRANSCRIPT_POS_TYPE 
					ORDER BY TRANSCRIPT_ID, EXON_ID ASC, MIN(CHR_POS) ".(($STRAND=="-")?"DESC":"ASC"));
    $DATA = array();
    foreach ($res as $line)
        $DATA[$line['TRANSCRIPT_ID']][] = $line;
    return $DATA;
}


function getTranscriptInfo(string $TRANSCRIPT_NAME)
{
    // check if transcript name has a version and remove it to keep only the name
    $pos = strpos($TRANSCRIPT_NAME, '.');
    $TRANSCRIPT_VERSION = '';
    if ($pos !== false) {
        $TRANSCRIPT_VERSION = substr($TRANSCRIPT_NAME, $pos + 1);
        $TRANSCRIPT_NAME = substr($TRANSCRIPT_NAME, 0, $pos);
    }
    //	echo $TRANSCRIPT_NAME."\n";
    $DATA_TRANSCRIPT = array();
    global $DB_CONN;
    $TRANSCRIPT_NAME = $DB_CONN->quote($TRANSCRIPT_NAME);
    $TRANSCRIPT_VERSION = $DB_CONN->quote($TRANSCRIPT_VERSION);
    $query = "
        SELECT 
            TRANSCRIPT_ID, TRANSCRIPT_NAME, TRANSCRIPT_VERSION, T.START_POS, T.END_POS,
            SB.SEQ_TYPE as BIOTYPE_NAME,SB.SO_ID as BIOTYPE_SO_ID, SB.SO_NAME as BIOTYPE_SO_NAME,
            SB.SO_DESCRIPTION as BIOTYPE_SO_DESC, SF.SEQ_TYPE as FEATURE_NAME,SF.SO_ID as FEATURE_SO_ID,
            SF.SO_NAME as FEATURE_SO_NAME, SF.SO_DESCRIPTION as FEATURE_SO_DESC, SUPPORT_LEVEL,
            GENE_SEQ_NAME,GENE_SEQ_VERSION, STRAND,GS.START_POS as GENE_START, GS.END_POS as GENE_END,
            GENE_ID,SYMBOL,FULL_NAME, GS.CHR_SEQ_ID
        FROM GENE_SEQ GS 
        LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GS.GN_ENTRY_ID, TRANSCRIPT T 
        LEFT JOIN (
            SELECT SO_ID,SO_NAME,SO_DESCRIPTION, SEQ_TYPE,SEQ_BTYPE_ID 
            FROM SEQ_BTYPE SB LEFT JOIN SO_ENTRY S ON SB.SO_ENTRY_ID =  S.SO_ENTRY_ID
        ) SB ON SB.SEQ_BTYPE_ID=BIOTYPE_ID
        LEFT JOIN (
            SELECT SO_ID,SO_NAME,SO_DESCRIPTION, SEQ_TYPE,SEQ_BTYPE_ID 
            FROM SEQ_BTYPE SB LEFT JOIN SO_ENTRY S ON SB.SO_ENTRY_ID =  S.SO_ENTRY_ID
        ) SF ON SF.SEQ_BTYPE_ID=FEATURE_ID
        WHERE T.GENE_SEQ_ID = GS.GENE_SEQ_ID 
            AND TRANSCRIPT_NAME=$TRANSCRIPT_NAME";
    if ($pos !== false) $query .= ' AND TRANSCRIPT_VERSION=' . $TRANSCRIPT_VERSION;

    $DATA_TRANSCRIPT = runQuery($query);

    return $DATA_TRANSCRIPT;
}


function getTranscriptSequence($TRANSCRIPT_NAME)
{
    $pos = strpos($TRANSCRIPT_NAME, '.');
    $TRANSCRIPT_VERSION = '';
    if ($pos !== false) {
        $TRANSCRIPT_VERSION = substr($TRANSCRIPT_NAME, $pos + 1);
        $TRANSCRIPT_NAME = substr($TRANSCRIPT_NAME, 0, $pos);
    }
    $DATA_TRANSCRIPT = array('EXONS' => array(), 'POS_TYPE' => array(0 => array('MIN' => "1", 'MAX' => -1, 'TYPE' => '')), 'SEQUENCE' => array());
    global $DB_CONN;
    $TRANSCRIPT_NAME = $DB_CONN->quote($TRANSCRIPT_NAME);
    $TRANSCRIPT_VERSION = $DB_CONN->quote($TRANSCRIPT_VERSION);

    $query = "SELECT TRANSCRIPT_POS_ID,TP.NUCL,SEQ_POS,SEQ_POS_TYPE_ID,EXON_ID,CHR_POS
			FROM TRANSCRIPT_POS TP LEFT JOIN  CHR_SEQ_POS C ON C.CHR_SEQ_POS_ID = TP.CHR_SEQ_POS_ID , TRANSCRIPT T
			WHERE T.TRANSCRIPT_ID=TP.TRANSCRIPT_ID 
	  		AND TRANSCRIPT_NAME=" . $TRANSCRIPT_NAME;
    if ($pos !== false) $query .= ' AND TRANSCRIPT_VERSION=' . $TRANSCRIPT_VERSION;
    $TMP = runQuery($query . ' ORDER BY SEQ_POS ASC');
    $ptypes_raw = runQuery("SELECT * FROM TRANSCRIPT_POS_TYPE");
    $PTYPE = array();
    foreach ($ptypes_raw as $PT) $PTYPE[$PT['TRANSCRIPT_POS_TYPE_ID']] = $PT['TRANSCRIPT_POS_TYPE'];
    $CURR_POS_TYPE = 0;
    foreach ($TMP as $K => $POS_INFO) {
        if ($POS_INFO['EXON_ID'] != '') {
            if (!isset($DATA_TRANSCRIPT['EXONS'][$POS_INFO['EXON_ID']])) $DATA_TRANSCRIPT['EXONS'][$POS_INFO['EXON_ID']] = array('MIN' => $POS_INFO['SEQ_POS'], 'MAX' => -1);
            if ($POS_INFO['SEQ_POS'] > $DATA_TRANSCRIPT['EXONS'][$POS_INFO['EXON_ID']]['MAX']) $DATA_TRANSCRIPT['EXONS'][$POS_INFO['EXON_ID']]['MAX'] = $POS_INFO['SEQ_POS'];
        }
        if ($K == 0) $DATA_TRANSCRIPT['POS_TYPE'][0]['TYPE'] = $PTYPE[$POS_INFO['SEQ_POS_TYPE_ID']];
        if ($PTYPE[$POS_INFO['SEQ_POS_TYPE_ID']] != $DATA_TRANSCRIPT['POS_TYPE'][$CURR_POS_TYPE]['TYPE']) {
            ++$CURR_POS_TYPE;
            $DATA_TRANSCRIPT['POS_TYPE'][$CURR_POS_TYPE] = array('MIN' => $POS_INFO['SEQ_POS'], 'MAX' => -1, 'TYPE' => $PTYPE[$POS_INFO['SEQ_POS_TYPE_ID']]);
        } else $DATA_TRANSCRIPT['POS_TYPE'][$CURR_POS_TYPE]['MAX'] = $POS_INFO['SEQ_POS'];
        $POS = $POS_INFO['SEQ_POS'];
        unset($POS_INFO['SEQ_POS'], $POS_INFO['SEQ_POS_TYPE_ID'], $POS_INFO['EXON_ID']);
        $DATA_TRANSCRIPT['SEQUENCE'][$POS] = $POS_INFO;
    }

    foreach ($DATA_TRANSCRIPT['POS_TYPE'] as &$PT) {
        if ($PT['MAX'] == -1) $PT['MAX'] = $PT['MIN'];
    }

    return $DATA_TRANSCRIPT;
}


function getListTranscripts($GN_ENTRY_ID, $CHR_SEQ_IDS = array(),$CODING_ONLY=false)
{
    $INPUTS = '';
    if (is_array($CHR_SEQ_IDS)) {
        if (count($CHR_SEQ_IDS) > 1) $INPUTS .= ' AND CS.CHR_SEQ_ID IN (' . implode(',', $CHR_SEQ_IDS) . ')';
    } else $INPUTS .= ' AND CS.CHR_SEQ_ID =' . $CHR_SEQ_IDS;

    $query = "SELECT GS.GENE_SEQ_ID,GENE_SEQ_NAME,GENE_SEQ_VERSION,STRAND,START_POS,END_POS,REFSEQ_NAME, REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION,MAP_LOCATION, CS.CHR_SEQ_ID, CS.CHR_ID, C.CHR_NUM,
SB.SEQ_TYPE as BIOTYPE_NAME,SB.SO_ID as BIOTYPE_SO_ID, SB.SO_NAME as BIOTYPE_SO_NAME, SB.SO_DESCRIPTION as BIOTYPE_SO_DESC, ASSEMBLY_NAME, ASSEMBLY_UNIT
FROM CHROMOSOME C, CHR_SEQ CS, CHR_GN_MAP CGM, CHR_MAP CM,GENOME_ASSEMBLY GA,GENE_SEQ GS
LEFT JOIN (SELECT SO_ID,SO_NAME,SO_DESCRIPTION, SEQ_TYPE,SEQ_BTYPE_ID FROM SEQ_BTYPE SB LEFT JOIN SO_ENTRY S ON SB.SO_ENTRY_ID =  S.SO_ENTRY_ID) SB ON sb.seq_btype_id=GS.BIOTYPE_ID
WHERE GS.GN_ENTRY_ID = CGM.GN_ENTRY_ID AND CM.CHR_MAP_ID = CGM.CHR_MAP_ID AND CS.CHR_SEQ_ID=GS.CHR_SEQ_ID AND C.CHR_ID = CS.CHR_ID
AND GA.GENOME_ASSEMBLY_ID = CS.GENOME_ASSEMBLY_ID
AND GS.GN_ENTRY_ID=$GN_ENTRY_ID " . $INPUTS;

    $res = runQuery($query);
    $RESULTS = array();
    if (count($res) == 0) return $RESULTS;
    foreach ($res as $LINE) {
        $RESULTS['GENE_SEQ'][$LINE['GENE_SEQ_ID']] = $LINE;
    }

    $res = array();
    $query="SELECT GENE_SEQ_ID, T.TRANSCRIPT_ID, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,T.START_POS,T.END_POS,SEQ_HASH,
	SB.SEQ_TYPE as BIOTYPE_NAME,SB.SO_ID as BIOTYPE_SO_ID, SB.SO_NAME as BIOTYPE_SO_NAME, SB.SO_DESCRIPTION as BIOTYPE_SO_DESC,
	SF.SEQ_TYPE as FEATURE_NAME,SF.SO_ID as FEATURE_SO_ID, SF.SO_NAME as FEATURE_SO_NAME, SF.SO_DESCRIPTION as FEATURE_SO_DESC,partial_sequence, valid_alignment,
	SUPPORT_LEVEL,COUNT(*) as LENGTH
	FROM  TRANSCRIPT T 
	LEFT JOIN (SELECT SO_ID,SO_NAME,SO_DESCRIPTION, SEQ_TYPE,SEQ_BTYPE_ID FROM SEQ_BTYPE SB LEFT JOIN SO_ENTRY S ON SB.SO_ENTRY_ID =  S.SO_ENTRY_ID) SB ON SB.SEQ_BTYPE_ID = BIOTYPE_ID
	LEFT JOIN (SELECT SO_ID,SO_NAME,SO_DESCRIPTION, SEQ_TYPE,SEQ_BTYPE_ID FROM SEQ_BTYPE SB LEFT JOIN SO_ENTRY S ON SB.SO_ENTRY_ID =  S.SO_ENTRY_ID) SF ON SF.SEQ_BTYPE_ID=FEATURE_ID
    LEFT JOIN TRANSCRIPT_POS TP ON TP.TRANSCRIPT_ID=T.TRANSCRIPT_ID 
	WHERE   GENE_SEQ_ID IN (" . implode(',', array_keys($RESULTS['GENE_SEQ'])) . ") ";
    if ($CODING_ONLY)$query.=" AND  (SB.SEQ_TYPE='protein_coding' OR SB.SEQ_TYPE='mRNA')  ";
    $query.=" GROUP BY T.TRANSCRIPT_ID, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,T.START_POS,T.END_POS,SEQ_HASH,
	SB.SEQ_TYPE,SB.SO_ID, SB.SO_NAME, SB.SO_DESCRIPTION,
	SF.SEQ_TYPE,SF.SO_ID, SF.SO_NAME, SF.SO_DESCRIPTION,
	SUPPORT_LEVEL
    ORDER BY (case
	when SB.SEQ_TYPE='protein_coding' then 1
	when SB.SEQ_TYPE='processed_transcript' then 2
	when SB.SEQ_TYPE='nonsense_mediated_decay' then 3
    when SB.SEQ_TYPE='retained_intron' then 4
    else 5
	end) asc, length desc";
    $RESULTS['TRANSCRIPTS'] = runQuery($query);

    return $RESULTS;
}



/**
 * @param string $ENSG
 * @return array
 * @throws Exception
 */
function gene_Portal_ensembl(string $ENSG)
{
    global $DB_CONN;
    $pos = strpos($ENSG, '.');
    if ($pos !== false) $ENSG = substr($ENSG, 0, $pos);
    $ENSG = $DB_CONN->quote($ENSG);
    $query = "SELECT DISTINCT SYMBOL, FULL_NAME, GENE_ID, GE.GN_ENTRY_ID, SCIENTIFIC_NAME,TAX_ID, GENE_SEQ_NAME
		FROM gene_seq GS 
        LEFT JOIN mv_gene_sp GE ON GS.GN_ENTRY_ID = GE.GN_ENTRY_ID
		WHERE GENE_SEQ_NAME LIKE $ENSG AND SYMBOL IS NOT NULL";
    $res = runQuery($query);

    $LIST_ID = array();
    $MAP = array();
    foreach ($res as $K => &$line) {
        if ($line['GN_ENTRY_ID'] == '') {
            $line['SYN'] = '';
            continue;
        }
        $LIST_ID[] = $line['GN_ENTRY_ID'];
        $MAP[$line['GN_ENTRY_ID']] = $K;
    }
    // if ($LIST_ID!=array()){
    // $CHUNKS = array_chunk($LIST_ID, 1000);
    // foreach ($CHUNKS as $CHUNK) {
    //     $query = "
    //         SELECT string_agg(SYN_VALUE,'|' order by syn_value asc) as syns, GN_ENTRY_ID
    //         FROM gn_syn GS, gn_syn_map GSM
    //         WHERE GSM.GN_ENTRY_ID IN (" . implode(',', $CHUNK) . ")
    //             AND GS.GN_SYN_ID = GSM.GN_SYN_ID
    //             AND SYN_TYPE='S' GROUP BY GN_ENTRY_ID
    //         ";
    //     $res2 = runQuery($query);
    //     foreach ($res2 as $line) {
    //         $res[$MAP[$line['GN_ENTRY_ID']]]['SYN'] = $line['SYNS'];
    //     }
    // }

    ///}

    return $res;
}


/**
 * @param string $GENE_NAME
 * @return array
 * @throws Exception
 */
function gene_portal_geneName(string $GENE_NAME): array
{
    global $DB_CONN;
    $GENE_NAME = $DB_CONN->quote(strtolower($GENE_NAME) . '%');

    $res = runQuery("SELECT tax_id FROM taxon t, genome_Assembly g where g.taxon_id =t.taxon_id");
    $list = array();
    $str = '';
    $n = 1;
    foreach ($res as $l) {
        $list[] = "'" . $l['TAX_ID'] . "'";
        if ($l['TAX_ID'] == '9606') continue;
        $n++;
        $str .= ' when tax_id = \'' . $l['TAX_ID'] . '\' then ' . $n . "\n";
    }

    $query = "
    
    SELECT * FROM (

        SELECT  DISTINCT SYMBOL, FULL_NAME,GENE_ID,m.gn_entry_id,SCIENTIFIC_NAME,TAX_ID  
        FROM mv_gene_sp m, gn_syn gs, gn_syn_map gsm 
        where m.gn_entry_Id = gsm.gn_entry_Id 
        AND gsm.gn_syn_id = gs.gn_syn_id 
        AND lower(gs.syn_value) = " . $GENE_NAME . "  AND TAX_ID IN (" . implode(',', $list) . ")
    ) Sub
    ORDER BY (case
                when tax_id='9606' then 1
                " . $str . " end) asc, TAX_ID ASC";


$res = runQuery($query);

if ($res==array())
{
    $query = "
    
        SELECT * FROM (

			SELECT  DISTINCT SYMBOL, FULL_NAME,GENE_ID,m.gn_entry_id,SCIENTIFIC_NAME,TAX_ID  
            FROM mv_gene_sp m, gn_syn gs, gn_syn_map gsm 
            where m.gn_entry_Id = gsm.gn_entry_Id 
            AND gsm.gn_syn_id = gs.gn_syn_id 
            AND lower(gs.syn_value) LIKE " . $GENE_NAME . "  AND TAX_ID IN (" . implode(',', $list) . ")
        ) Sub
        ORDER BY (case
                    when tax_id='9606' then 1
                    " . $str . " end) asc, TAX_ID ASC";


    $res = runQuery($query);
        }
    $LIST_ID = array();
    $MAP = array();
    foreach ($res as $K => $line) {
        $LIST_ID[] = $line['GN_ENTRY_ID'];
        $MAP[$line['GN_ENTRY_ID']] = $K;
    }
    $CHUNKS = array_chunk($LIST_ID, 1000);
    foreach ($CHUNKS as $CHUNK) {
        $query = "SELECT STRING_AGG(SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as syns, GN_ENTRY_ID
		FROM gn_syn GS, gn_syn_map GSM
        WHERE GSM.GN_ENTRY_ID IN (" . implode(',', $CHUNK) . ")
            AND GS.GN_SYN_ID = GSM.GN_SYN_ID
            AND SYN_TYPE='S'
        GROUP BY GN_ENTRY_ID";
        $res2 = runQuery($query);
        foreach ($res2 as $line) {
            $res[$MAP[$line['GN_ENTRY_ID']]]['SYN'] = $line['SYNS'];
        }
    }
    return $res;
}
function getGeneLocation($GENE_ID)
{
    $DATA['LOCUS'] = runQuery("SELECT CHR_NUM, MAP_LOCATION,GENE_TYPE FROM CHROMOSOME C, CHR_MAP CM, CHR_GN_MAP CGM, GN_ENTRY GE
	WHERE GE.GN_ENTRY_ID = CGM.GN_ENTRY_Id
	AND cgm.chr_map_id=CM.CHR_MAP_ID
	AND CM.CHR_ID = C.CHR_ID
	AND GENE_ID= " . $GENE_ID);

    $TMP = runQuery("SELECT DISTINCT gs2.*, ge2.* 
    FROM gn_entry ge, gene_seq gs1, gene_seq gs2 
    LEFT JOIN gn_entry ge2 ON ge2.gn_entry_Id = gs2.gn_entry_Id 
    where ge.gn_entry_Id = gs1.gn_entry_id 
    AND gs1.chr_seq_id = gs2.chr_seq_id 
    AND ((gs2.start_pos >=gs1.start_pos-5000 AND gs2.start_pos<=gs1.end_pos+5000) 
    OR (gs2.end_pos >=gs1.start_pos-5000 AND gs2.end_pos <= gs1.end_pos+5000)
    OR (gs2.end_pos >=gs1.end_pos AND gs2.start_pos <= gs1.start_pos)
    )
     AND ge.gene_id=" . $GENE_ID);
    $CHR_SEQs = array();
    foreach ($TMP as $line) $DATA['GENE_LOC'][$line['CHR_SEQ_ID']][$line['GENE_ID']][] = $line;
    if (isset($DATA['GENE_LOC'])) {
        $res = runQuery("SELECT chr_seq_name, assembly_unit,assembly_name, assembly_version ,chr_seq_Id, chr_start_pos,chr_end_pos,seq_role
     FROM genome_assembly ga, chr_seq cs 
     where cs.genome_assembly_Id = ga.genome_assembly_id 
     AND chr_seq_Id IN (" . implode(',', array_keys($DATA['GENE_LOC'])) . ')');
        foreach ($res as $line) $DATA['GENE_LOC'][$line['CHR_SEQ_ID']]['CHR_SEQ'] = $line;
    }
    return $DATA;
}

function getGeneGTEXExprStat($GN_ENTRY_ID)
{
    $query = "SELECT ORGAN_NAME,T.TISSUE_NAME,N_SAMPLE,AUC,
	LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,AVG_VALUE,Q3,MAX_VALUE 
	FROM  GENE_SEQ GS, RNA_GENE_STAT RGS, RNA_TISSUE T
	WHERE GS.GN_ENTRY_ID = " . $GN_ENTRY_ID . " AND RGS.GENE_SEQ_ID = Gs.GENE_SEQ_ID 
	AND T.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID 
	ORDER BY MED_VALUE DESC";

    return runQuery($query);
}
function getTranscriptStats($GN_ENTRY_ID)
{
    $res = runQuery("SELECT SEQ_TYPE, SUPPORT_LEVEL, COUNT(*) NUMBERS FROM GENE_SEQ GS, TRANSCRIPT T, SEQ_BTYPE S
WHERE  T.GENE_SEQ_ID = Gs.GENE_SEQ_ID AND T.FEATURE_ID =S.SEQ_BTYPE_ID  AND GN_ENTRY_ID=" . $GN_ENTRY_ID . "
GROUP BY SEQ_TYPE, SUPPORT_LEVEL ORDER BY SUPPORT_LEVEL ASC");
    $DATA = array();
    foreach ($res as $line) {
        $DATA[$line['SUPPORT_LEVEL']][$line['SEQ_TYPE']] = $line['NUMBERS'];
    }
    return $DATA;
}



function getOrthologs($GENE_ID, $ONLY_MAIN = false)
{

    $query = "SELECT DISTINCT COMP_GN_ENTRY_ID, COMP_SYMBOL,COMP_GENE_ID, COMP_GENE_NAME, COMP_SPECIES,COMP_TAX_ID
	 FROM (SELECT  MGS2.GN_ENTRY_ID AS COMP_GN_ENTRY_ID, MGS2.SYMBOL AS COMP_SYMBOL,
	  MGS2.GENE_ID as COMP_GENE_ID, MGS2.FULL_NAME as COMP_GENE_NAME, 
	  MGS2.SCIENTIFIC_NAME as COMP_SPECIES, MGS2.TAX_ID as COMP_TAX_ID 
	  FROM MV_GENE_SP MGS1, GN_REL GR, MV_GENE_SP MGS2 
	  WHERE MGS1.GN_ENTRY_Id = GR.GN_ENTRY_R_ID AND MGS2.GN_ENTRY_ID = GR.GN_ENTRY_C_ID 
	  AND MGS1.GENE_ID='" . $GENE_ID . "'";
    if ($ONLY_MAIN) $query .= " AND MGS2.TAX_ID IN ('9606','10116','10090','9913','9615','9541') ";
    $query .= " order by (case
             when MGS2.tax_id='9606' then 1
             when MGS2.tax_id='10116' then 2
             when MGS2.tax_id='10090' then 3
             when MGS2.tax_id='9913' then 4
             when MGS2.tax_id='9615' then 5
		     when MGS2.tax_id='9541' then 6 end
) asc, MGS2.TAX_ID  ASC) t";

    return runQuery($query);
}

function geneToUniprot($GN_ENTRY_ID)
{
    $query = 'SELECT PROT_IDENTIFIER,UE.PROT_ENTRY_ID, STATUS
	FROM PROT_ENTRY UE, GN_PROT_MAP GUM
	WHERE GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
	AND  GN_ENTRY_ID=' . $GN_ENTRY_ID . ' ORDER BY STATUS DESC,PROT_IDENTIFIER ASC';

    $res = runQuery($query);
    return $res;
}

function getDrugGene(int $GN_ENTRY_ID)
{
    $res = runQuery("SELECT IS_APPROVED,IS_WITHDRAWN,MAX_CLIN_PHASE,COUNTERION_SMILES,SMILES,SM.IS_VALID,DE.DRUG_ENTRY_ID
	FROM DRUG_DISEASE DT, DRUG_ENTRY DE
    LEFT JOIN drug_mol_entity_map dmem ON DE.drug_entry_Id = dmem.drug_entry_Id
    LEFT JOIN molecular_entity me ON me.molecular_entity_id = dmem.molecular_entity_id
    LEFT JOIN sm_entry SE ON se.md5_hash=me.molecular_structure_hash 
    LEFT JOIN sm_molecule sm ON se.sm_molecule_id = sm.sm_molecule_id 
    LEFT JOIN sm_counterion sc on sc.sm_counterion_id =se.sm_counterion_id 
	WHERE DT.DRUG_ENTRY_ID = DE.DRUG_ENTRY_ID
	AND DT.GN_ENTRY_ID=" . $GN_ENTRY_ID . ' ORDER BY MAX_CLIN_PHASE DESC');
    $DATA = array();
    foreach ($res as $line) $DATA[$line['DRUG_ENTRY_ID']] = $line;
    if (count($DATA) == 0) return $DATA;
    $res = runQuery("SELECT DRUG_ENTRY_ID,DRUG_NAME,IS_PRIMARY,IS_TRADENAME FROM DRUG_NAME WHERE DRUG_ENTRY_ID IN (" . implode(',', array_keys($DATA)) . ')');
    foreach ($res as $line) $DATA[$line['DRUG_ENTRY_ID']]['NAME'][($line['IS_PRIMARY'] == 'T') ? 'PRIMARY' : ($line['IS_TRADENAME'] == 'T' ? 'TRADENAME' : 'SYNONYM')][] = $line['DRUG_NAME'];

    $res = runQuery("SELECT DRUG_DISEASE_ID,DD.DRUG_ENTRY_ID, EE.DISEASE_ENTRY_ID, EE.DISEASE_TAG, DISEASE_NAME, MAX_DISEASE_PHASE
	FROM DRUG_DISEASE DD, DISEASE_ENTRY EE
	WHERE EE.DISEASE_ENTRY_ID = DD.DISEASE_ENTRY_ID
	AND GN_ENTRY_ID = " . $GN_ENTRY_ID . " AND DRUG_ENTRY_ID IN (" . implode(',', array_keys($DATA)) . ') ORDER BY DRUG_DISEASE_ID ASC, MAX_DISEASE_PHASE DESC');

    foreach ($res as $line) $DATA[$line['DRUG_ENTRY_ID']]['DISEASE'][$line['DRUG_DISEASE_ID']] = $line;

    return $DATA;
}

function getGenie($GN_ENTRY_ID)
{
    try {

        $res = runQuery("select * FROM surface_genie s, gn_prot_map g where g.prot_entry_id = s.prot_entry_id AND gn_entry_id =" . $GN_ENTRY_ID);
        return $res;
    } catch (\Throwable $e) { // For PHP 7
        // handle $e
    } catch (\Exception $e) { // For PHP 5
        // handle $e
    }
}



function getGeneSeqLoc($GN_ENTRY_ID)
{
    return runQuery('SELECT cs.chr_seq_id, assembly_unit, chr_seq_name, seq_role, chr_num, g.gene_seq_name, g.gene_seq_version ,ASSEMBLY_ACCESSION,ASSEMBLY_VERSION,CREATION_DATE,assembly_name,
	COUNT(DISTINCT TRANSCRIPT_ID) N_TRANSCRIPTS
	 FROM genome_assembly ga, chromosome c,gene_seq g, chr_seq cs,transcript t  
	 WHERE ga.genome_assembly_id =  cs.genome_assembly_id 
	 AND t.gene_seq_id = g.gene_seq_id
	 AND c.chr_id = cs.chr_id 
	 AND cs.chr_seq_id = g.chr_seq_id 
	 AND gn_entry_id=' . $GN_ENTRY_ID . '
	 GROUP BY cs.chr_seq_id, assembly_unit, chr_seq_name, seq_role, chr_num, g.gene_seq_name, g.gene_seq_version ,ASSEMBLY_ACCESSION,ASSEMBLY_VERSION,assembly_name,CREATION_DATE');
}





///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// RNA EXPRESSION ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
function getListExprTranscript($GN_ENTRY_ID)
{
    return runQuery("SELECT DISTINCT T.* FROM  GENE_SEQ GS, TRANSCRIPT T, RNA_TRANSCRIPT RT
	WHERE  GS.GENE_SEQ_ID = T.GENE_SEQ_ID
	AND T.TRANSCRIPT_ID = RT.TRANSCRIPT_ID
	AND GN_ENTRY_ID=" . $GN_ENTRY_ID);
}


function getMedExprTranscript($GN_ENTRY_ID)
{
    $res = runQuery("SELECT T.TRANSCRIPT_ID, percentile_cont(0.5) within group (ORDER BY TPM) as MEDIAN_TPM, RNA_TISSUE_ID 
	FROM GENE_SEQ GS, TRANSCRIPT T, RNA_TRANSCRIPT RT, RNA_SAMPLE RS
	WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
	AND GS.GENE_SEQ_ID = T.GENE_SEQ_ID
	AND T.TRANSCRIPT_ID = RT.TRANSCRIPT_ID
	AND GN_ENTRY_ID = " . $GN_ENTRY_ID . " GROUP BY T.TRANSCRIPT_ID, RNA_TISSUE_ID");
    $DATA = array();
    foreach ($res as $line) $DATA[$line['TRANSCRIPT_ID']][$line['RNA_TISSUE_ID']] = $line['MEDIAN_TPM'];
    return $DATA;
}


function getTranscriptExprStat($GN_ENTRY_ID)
{
    return runQuery("SELECT ORGAN_NAME,TISSUE_NAME,NSAMPLE as N_SAMPLE,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE, TRANSCRIPT_NAME,TRANSCRIPT_VERSION
	FROM  GENE_SEQ GS, TRANSCRIPT T, RNA_TRANSCRIPT_STAT RGS, RNA_TISsuE RT
	WHERE GS.GN_ENTRY_ID = " . $GN_ENTRY_ID . " AND RGS.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND T.GENE_SEQ_ID = Gs.GENE_SEQ_ID 
	AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID 
	ORDER BY MED_VALUE DESC");
}


function getTranscriptGTEXExpr($TR_ID)
{
    return runQuery("SELECT TPM,RNA_TISSUE_ID FROM RNA_TRANSCRIPT RG, RNA_SAMPLE RS,RNA_SOURCE RO 
WHERE  RO.RNA_SOURCE_ID = RS.RNA_SOURCE_ID AND RS.RNA_SAMPLE_ID = RG.RNA_SAMPLE_ID AND ro.source_name='GTEX' AND  
TRANSCRIPT_ID=" . $TR_ID);
}

function getGeneGTEXExpr($GENE_SEQ_ID)
{
    return runQuery("SELECT TPM,RNA_TISSUE_ID FROM RNA_GENE RG, RNA_SAMPLE RS,RNA_SOURCE RO 
WHERE  RO.RNA_SOURCE_ID = RS.RNA_SOURCE_ID AND RS.RNA_SAMPLE_ID = RG.RNA_SAMPLE_ID AND ro.source_name='GTEX' AND  
GENE_SEQ_ID=" . $GENE_SEQ_ID);
}


///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// PUBLICATION_GENE //////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


function publi_portal_title($TITLE)
{
    $query = "SELECT pmid,publication_date
    FROM PMID_ENTRY PE 
    WHERE  LOWER(TITLE) LIKE LOWER('%" . str_replace("'", "''", $TITLE) . "%')";

    $res = runQuery($query);

    return $res;
}
function publi_portal_orcid($ORCID)
{
    $query = "SELECT pmid,publication_date
    FROM PMID_ENTRY PE, PMID_AUTHOR_MAP PAM, PMID_AUTHOR PA 
    WHERE PA.PMID_AUTHOR_ID = PAM.PMID_AUTHOR_ID
    AND PAM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID
    AND ORCID_ID = '" . $ORCID . "'";
    $res = runQuery($query);
    return $res;
}
function publi_portal_author($AUTHOR)
{
    $tab = explode(",", $AUTHOR);

    $query = "SELECT pmid,publication_date, last_name, first_name, initials
    FROM PMID_ENTRY PE, PMID_AUTHOR_MAP PAM, PMID_AUTHOR PA 
    WHERE PA.PMID_AUTHOR_ID = PAM.PMID_AUTHOR_ID
    AND PAM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID ";
    if (count($tab) == 1) {
        $query .= " AND LOWER(LAST_NAME) LIKE '%" . strtolower($AUTHOR) . "%'";
    } else $query .= " AND LOWER(LAST_NAME) LIKE '%" . trim(strtolower($tab[0])) . "%' AND LOWER(FIRST_NAME) LIKE  '%" . trim(strtolower($tab[1])) . "%'";
    $res = runQuery($query);
    return $res;
}

function publication_getFullText($PMID)
{

    $res=runQuery("SELECT * FROM pmc_entry PE LEFT JOIN pmid_entry PM ON PM.pMid_entry_Id = pE.pmid_entry_id WHERE  pmid=".$PMID);
    if ($res==array())return "Unable to find publication ".$PMID;
    
   
    $PMC_ENTRY_ID=$res[0]['PMC_ENTRY_ID'];    
    $DATA=array('INFO'=>$res[0]);
    $res=runQuery("SELECT * FROM  pmc_fulltext pf, pmc_section ps where ps.pmc_section_id = pf.pmc_section_id AND pmc_entry_id = ".$PMC_ENTRY_ID.' ORDER BY OFFSET_POS ASC');
    foreach ($res as $line)
    {
        
        $DATA['TEXT'][$line['PMC_FULLTEXT_ID']]=array('OFFSET_POS'=>$line['OFFSET_POS'],'SECTION_TYPE'=>$line['SECTION_TYPE'],'SECTION_SUBTYPE'=>$line['SECTION_SUBTYPE'],'FULL_TEXT'=>$line['FULL_TEXT'],'GROUP_ID'=>$line['GROUP_ID']);
    }


    $DATA['MATCH']=array('DRUG'=>array(),'DISEASE'=>array(),'ANATOMY'=>array(),'CELL'=>array(),'GO'=>array(),'COMPANY'=>array(),'CLINICAL'=>array(),'GENE'=>array());
    
    // "SELECT * FROM assay_pmid ap, assay_entry ae, activity_entry a,molecular_entity me, sm_entry s
    //  where  ap.assay_entry_id = ae.assay_entry_id
    //  AND ae.assay_entry_Id = a.assay_entry_id
    //  AND a.molecular_entity_id = me.molecular_entity_id
    //  AND me.molecular_structure_hash = s.md5_hash
    //  AND pmid_entry_id = ".$PMID_ENTRY_ID
   
    
    $DATA['METADATA']=array('DRUG'=>array(),'DISEASE'=>array(),'ANATOMY'=>array(),'CELL'=>array(),'GO'=>array(),'COMPANY'=>array(),'CLINICAL'=>array(),'GENE'=>array());


    /// Get Drug Info
    $DATA['MATCH']['DRUG']=runQuery("SELECT * FROM pmc_fulltext_drug_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
    
    foreach ($DATA['MATCH']['DRUG'] as $L)
    {
        if (isset($DATA['METADATA']['DRUG'][$L['DRUG_ENTRY_ID']]))continue;
        $DR_INFO=getDrugInfo($L['DRUG_ENTRY_ID']);
        $DATA['METADATA']['DRUG'][$L['DRUG_ENTRY_ID']]=$DR_INFO;
        $DATA['METADATA']['DRUG'][$L['DRUG_ENTRY_ID']]['DESC']=
        runQuery("SELECT TEXT_DESCRIPTION,TEXT_TYPE,SOURCE_NAME FROM DRUG_DESCRIPTION DD, SOURCE S WHERE S.SOURCE_ID=DD.SOURCE_ID AND DRUG_ENTRY_ID = ".$L['DRUG_ENTRY_ID']);  
    }

   // print_r($DATA);exit;
    /// Get Compound Info
    $DATA['MATCH']['SM']=runQuery("SELECT * FROM pmc_fulltext_sm_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
        
    foreach ($DATA['MATCH']['SM'] as $L)
    {
        if (isset($DATA['METADATA']['SM'][$L['SM_ENTRY_ID']]))continue;
        
        $DATA['METADATA']['SM'][$L['SM_ENTRY_ID']]=runQuery("SELECT SM_ENTRY_Id,md5_hash, FULL_SMILES FROM sm_entry where sm_entry_id = ".$L['SM_ENTRY_ID'])[0];
        $DATA['METADATA']['SM'][$L['SM_ENTRY_ID']]['DESC']=runQuery("SELECT description_text,description_type, source_name FROM sm_description sd, source s where s.source_id=sd.source_id and sm_entry_id = ".$L['SM_ENTRY_ID']);  
        $DATA['METADATA']['SM'][$L['SM_ENTRY_ID']]['NAME']=runQuery("SELECT sm_name, source_name FROM sm_source sn, source s where s.source_id=sn.source_id and sm_entry_id = ".$L['SM_ENTRY_ID']);
    }


    /// Get Disease Info
    $DATA['MATCH']['DISEASE']=runQuery("SELECT * FROM pmc_fulltext_disease_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
    foreach ($DATA['MATCH']['DISEASE'] as $L)
    {
        if (isset($DATA['METADATA']['DISEASE'][$L['DISEASE_ENTRY_ID']]))continue;
        $DATA['METADATA']['DISEASE'][$L['DISEASE_ENTRY_ID']]=array();
    }

    if ($DATA['METADATA']['DISEASE']!=array())
    {
        $res=runQuery("SELECT disease_entry_id, DISEASE_NAME,disease_tag,disease_definition
         FROM disease_entry where disease_entry_id in (".implode(',',array_keys($DATA['METADATA']['DISEASE'])).")");
        foreach ($res as $L)
        {
            $DATA['METADATA']['DISEASE'][$L['DISEASE_ENTRY_ID']]=$L;
            $T=getDiseaseInfo($L['DISEASE_ENTRY_ID']);
            // if ($T!=array()){echo '<pre>';print_R($T);}
            if (isset($T['OMIM']))
            {
                
                foreach ($T['OMIM'] as $TYPE=>&$DESC)
                {
                   // echo $TYPE."\t".strpos($TYPE,'Description')."\n";
                    if (strpos($TYPE,'Description')===false)continue;
                    $DATA['METADATA']['DISEASE'][$L['DISEASE_ENTRY_ID']]['DESC']=$DESC;
                   // print_r($DATA['METADATA']['DISEASE'][$L['DISEASE_ENTRY_ID']]);exit;
                }
            }
        }
    }

    
    //exit;

    /// Get Anatomy Info
    $DATA['MATCH']['ANATOMY']=runQuery("SELECT * FROM pmc_fulltext_anatomy_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
    foreach ($DATA['MATCH']['ANATOMY'] as $L)
    {
        if (isset($DATA['METADATA']['ANATOMY'][$L['ANATOMY_ENTRY_ID']]))continue;
        $DATA['METADATA']['ANATOMY'][$L['ANATOMY_ENTRY_ID']]=array();
    }
    if ($DATA['METADATA']['ANATOMY']!=array())
    {
        $res=runQuery("SELECT anatomy_entry_id, ANATOMY_NAME,ANATOMY_TAG,ANATOMY_DEFINITION FROM anatomy_entry where anatomy_entry_id in (".implode(',',array_keys($DATA['METADATA']['ANATOMY'])).")");
        foreach ($res as $L)
        {
            $DATA['METADATA']['ANATOMY'][$L['ANATOMY_ENTRY_ID']]=$L;
        }
    }


    /// Get Cell Info
    $DATA['MATCH']['CELL']=runQuery("SELECT * FROM pmc_fulltext_cell_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
    foreach ($DATA['MATCH']['CELL'] as $L)
    {
        if (isset($DATA['METADATA']['CELL'][$L['CELL_ENTRY_ID']]))continue;
        $DATA['METADATA']['CELL'][$L['CELL_ENTRY_ID']]=getCellLineInfo($L['CELL_ENTRY_ID']);
    }


    /// Get GO Info
    $DATA['MATCH']['GO']=runQuery("SELECT * FROM pmc_fulltext_go_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
    foreach ($DATA['MATCH']['GO'] as $L)
    {
        if (isset($DATA['METADATA']['GO'][$L['GO_ENTRY_ID']]))continue;
        $DATA['METADATA']['GO'][$L['GO_ENTRY_ID']]=array();
    }
    if ($DATA['METADATA']['GO']!=array())
    {
        $res=runQuery("SELECT go_entry_id, AC,NAME,DEFINITION FROM go_Entry where go_entry_id in (".implode(',',array_keys($DATA['METADATA']['GO'])).")");
        foreach ($res as $L)
        {
            $DATA['METADATA']['GO'][$L['GO_ENTRY_ID']]=$L;
        }
    }

    /// Get Company Info
    $DATA['MATCH']['COMPANY']=runQuery("SELECT * FROM pmc_fulltext_company_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
    foreach ($DATA['MATCH']['COMPANY'] as $L)
    {
        if (isset($DATA['METADATA']['COMPANY'][$L['COMPANY_ENTRY_ID']]))continue;
        $DATA['METADATA']['COMPANY'][$L['COMPANY_ENTRY_ID']]=array();
    }
    if ($DATA['METADATA']['COMPANY']!=array())
    {
        $res=runQuery("SELECT company_entry_id, COMPANY_NAME,COMPANY_TYPE FROM company_entry where company_entry_id in (".implode(',',array_keys($DATA['METADATA']['COMPANY'])).")");
        foreach ($res as $L)
        {
            $DATA['METADATA']['COMPANY'][$L['COMPANY_ENTRY_ID']]=$L;
        }
    }

    /// Get Clinical Info
    $DATA['MATCH']['CLINICAL']=runQuery("SELECT * FROM pmc_fulltext_clinical_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
    foreach ($DATA['MATCH']['CLINICAL'] as $L)
    {
        if (isset($DATA['METADATA']['CLINICAL'][$L['CLINICAL_TRIAL_ID']]))continue;
        $DATA['METADATA']['CLINICAL'][$L['CLINICAL_TRIAL_ID']]=array();
    }
    if ($DATA['METADATA']['CLINICAL']!=array())
    {
        $res=runQuery("SELECT clinical_trial_id, ALIAS_NAME,OFFICIAL_TITLE,CLINICAL_PHASE,CLINICAL_STATUS,BRIEF_SUMMARY FROM clinical_trial where clinical_trial_id in (".implode(',',array_keys($DATA['METADATA']['CLINICAL'])).")");
        foreach ($res as $L)
        {
            $DATA['METADATA']['CLINICAL'][$L['CLINICAL_TRIAL_ID']]=$L;
        }
    }

    /// Get Gene Info

    $DATA['MATCH']['GENE']=runQuery("SELECT * FROM pmc_fulltext_gn_map where  pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$PMC_ENTRY_ID.")");
    foreach ($DATA['MATCH']['GENE'] as $L)
    {
        if (isset($DATA['METADATA']['GENE'][$L['GN_ENTRY_ID']]))continue;
        $DATA['METADATA']['GENE'][$L['GN_ENTRY_ID']]=array();
    }
    if ($DATA['METADATA']['GENE']!=array())
    {
        $res=runQuery("SELECT gn_entry_id,GENE_ID, SYMBOL,FULL_NAME FROM gn_entry where gn_entry_id in (".implode(',',array_keys($DATA['METADATA']['GENE'])).")");
        foreach ($res as $L)
        {
            $DATA['METADATA']['GENE'][$L['GN_ENTRY_ID']]=$L;
        }
    }

  //  print_R($DATA);exit;
    return $DATA;
}

function publi_portal_instit($INSTIT)
{
    $query = "SELECT pmid,publication_date
    FROM PMID_ENTRY PE, PMID_AUTHOR_MAP PAM, PMID_AUTHOR PA , PMID_INSTIT PI
    WHERE PA.PMID_AUTHOR_ID = PAM.PMID_AUTHOR_ID 
    AND PAM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID
    AND PI.PMID_INSTIT_ID = PA.PMID_INSTIT_ID
    AND LOWER(INSTIT_NAME) LIKE '%" . strtolower($INSTIT) . "%'";
    $res = runQuery($query);
    return $res;
}

function publi_portal_doi($DOI)
{
    $tab = explode(";", $DOI);
    $str = '';
    foreach ($tab as $l) $str .= "'" . $l . '\',';
    $query = "SELECT pmid,publication_date
    FROM PMID_ENTRY PE
    WHERE  doi IN (" . substr($str, 0, -1) . ')';

    $res = runQuery($query);
    return $res;
}

function publi_portal_pmid($PMID)
{
    $tab = explode(";", $PMID);
    $str = '';
    foreach ($tab as $l) 
    {
     if (is_numeric($l))   $str .= $l . ',';
    }
    if ($str=='')return array();
    $query = "SELECT pmid,publication_date
    FROM PMID_ENTRY PE
    WHERE  PMID IN (" . substr($str, 0, -1) . ')';

    $res = runQuery($query);
    return $res;
}
function getAssayCountByCompoundId($COMPOUNDID)
{
    if ($COMPOUNDID==array())return 0;
    $query =  'SELECT COUNT(DISTINCT ae.assay_entry_id) CO 
    FROM activity_entry ae, active_sm_map asm 
    where asm.active_entry_id = ae.active_entry_id 
    and asm.sm_entry_id  IN (' . implode(',',$COMPOUNDID).')';
    $res = runQuery($query);
    return $res[0]['CO'];
}

function getAssayCountByCompoundDrugId($COMPOUNDID)
{
    $DRUG_ENTRY_ID = $COMPOUNDID['DRUG_ENTRY_ID'];
    $query =
        'SELECT COUNT(DISTINCT ae.assay_entry_id) CO FROM pmid_drug_map dpm, assay_pmid ap,assay_entry ae where dpm.pmid_entry_id = ap.pmid_entry_id and ae.assay_entry_id = ap.assay_entry_id and dpm.drug_entry_id=' . $DRUG_ENTRY_ID;
    $res = runQuery($query);
    return $res[0]['CO'];
}

function getAssaysByCompoundId($COMPOUNDID)
{

    if ($COMPOUNDID==array())return array();
    $DATA = array();
    $query =  'SELECT DISTINCT aee.assay_name
     FROM activity_entry ae, active_sm_map asm, assay_entry aee 
     where aee.assay_entry_id = ae.assay_entry_id 
     and asm.active_entry_id = ae.active_entry_id 
     and  asm.sm_entry_id IN (' . implode(',',$COMPOUNDID).')';
    $res = runQuery($query);
    foreach ($res as $L) $DATA[] = $L['ASSAY_NAME'];

    return $DATA;
}
function getAssaysByCompoundDrugId($COMPOUNDID)
{

    $DRUG_ENTRY_ID = $COMPOUNDID['DRUG_ENTRY_ID'];
    $DATA = array();
    $query =  'SELECT DISTINCT ae.assay_name FROM pmid_drug_map dpm, assay_pmid ap,assay_entry ae where dpm.pmid_entry_id = ap.pmid_entry_id and ae.assay_entry_id = ap.assay_entry_id and dpm.drug_entry_id=' . $DRUG_ENTRY_ID;
    $res = runQuery($query);
    foreach ($res as $L) $DATA[] = $L['ASSAY_NAME'];

    return $DATA;
}

function getCellLineInfo($CELL_ENTRY_ID)
{
    $DATA=array();
    $res=runQuery("SELECT * FROM cell_entry where cell_entry_id=".$CELL_ENTRY_ID);
    if ($res==array())return array();
    $DATA=$res[0];
    if ($DATA['CELL_TISSUE_ID']!='')
    $DATA['TISSUE']=runQuery("SELECT cell_tissue_name,anatomy_Tag,anatomy_name,anatomy_definition
     FROM cell_tissue ct LEFT JOIN anatomy_Entry ae on ae.anatomy_entry_Id = ct.anatomy_entry_Id where cell_tissue_id=".$DATA['CELL_TISSUE_ID']);

     $DATA['TAXON']=runQuery("SELECT * FROM cell_taxon_map ctn, taxon t where t.taxon_id = ctn.taxon_id and cell_entry_id=".$CELL_ENTRY_ID);
     $DATA['DISEASE']=runQuery("SELECT * FROM cell_disease ctn, disease_entry t WHERE t.disease_entry_id = ctn.disease_entry_id and cell_entry_id=".$CELL_ENTRY_ID);
     
     return $DATA;
}


function getCompoundAssays($LIST_ASSAYS)
{
    $list_names = "(";
    foreach ($LIST_ASSAYS as $Idx => $A) {
        $list_names .= "'" . $A . "',";
    }
    $list_name_string = substr($list_names, 0, -1) . ")";
    $query = "SELECT assay_name, assay_description, assay_type, assay_target_type_id, 
            ae.confidence_score as score_confidence, source_name, confidence_score, assay_variant_id, ace.cell_entry_id as cell_id,assay_tissue_name 
    FROM assay_entry ae 
    LEFT JOIN assay_cell ace ON ae.assay_cell_id = ace.assay_cell_id 
    LEFT JOIN assay_tissue ati ON ati.assay_tissue_id = ae.assay_tissue_id 
    LEFT JOIN anatomy_entry aen ON aen.anatomy_entry_id = ati.anatomy_entry_id, 
    assay_target att, assay_target_protein_map atp,assay_protein ap, prot_seq ps, prot_entry pe, source s, gn_prot_map gpm WHERE ae.assay_target_id = att.assay_Target_id AND ae.source_id = s.source_id AND att.assay_target_id = atp.assay_target_id AND atp.assay_protein_id = ap.assay_protein_id 
    AND ap.prot_seq_id = ps.prot_Seq_id AND ps.prot_entry_Id = pe.prot_entry_id AND gpm.prot_entry_Id = pe.prot_entry_id 
    AND ae.assay_name in " . $list_name_string;
    // ' . $list_names;
    // TODO this limit is set for dev purposes, figure out if limits need to be set for certain molecules


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
        $res = runQuery("SELECT assay_variant_id,mutation_list FROM assay_variant WHERE assay_variant_id IN (" . implode(',', array_keys($DATA['VARIANT'])) . ')');
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

function getCountActivity($LIST_ASSAYS, $FILTERS)
{

    $query = 'SELECT COUNT(*) CO FROM activity_entry ae, assay_entry aee where aee.assay_entry_id = ae.assay_entry_id AND assay_name IN (';
    foreach ($LIST_ASSAYS as $A) $query .= "'" . $A . "',";
    $query = substr($query, 0, -1) . ')';
    $res = runQuery($query);
    return $res[0]['CO'];
}


function getActivityFromAssay($LIST_ASSAYS, $PARAMS, $FILTERS = array())
{
    $list_names = '(';
    foreach ($LIST_ASSAYS as $A) $list_names .= "'" . $A . "',";
    $list_names = substr($list_names, 0, -1) . ')';
    $data = array();

    $time = microtime_float();


    $query = "SELECT activity_entry_id FROM (
		SELECT ROW_NUMBER() OVER( ORDER BY std_value ASC) R, activity_entry_id FROM activity_entry ae, assay_entry ase ";

    $query .= " WHERE ae.assay_entry_Id = ase.assay_entry_Id AND assay_name IN " . $list_names . ") sub WHERE  R<" . ($PARAMS['MAX'] + 1) . " AND R>=" . ($PARAMS['MIN'] + 1);

    $res = runQuery($query);
    $tmp = array();

    foreach ($res as $L) $tmp[] = $L['ACTIVITY_ENTRY_ID'];

    if ($tmp == array()) return $data;


    $res = runQuery("SELECT std_relation,std_value,std_units,std_type,mol_pos,ae.active_entry_Id,bao_endpoint FROM activity_entry ae WHERE
         activity_entry_id IN (" . implode(',', $tmp) . ') ORDER BY std_value ASC');
    $active_entries = array();
    $K = 0;
    foreach ($res as $line) {

        ++$K;
        $data[$K] = $line;
        $active_entries[$line['ACTIVE_ENTRY_ID']][] = $K;
    }

    $res = runQuery("SELECT ae.active_entry_id, se.sm_entry_id, inchi_key, smiles,is_valid,is_ambiguous,counterion_smiles,smiles 
    FROM active_entry ae, active_sm_map asm, sm_entry se 
    LEFT JOIN sm_counterion sc on se.sm_counterion_id = sc.sm_counterion_id,sm_molecule sm 
    where sm.sm_molecule_id = se.sm_molecule_id 
    ANd ae.active_entry_Id = asm.active_entry_id 
    AND asm.sm_entry_Id = se.sm_entry_id 
    AND ae.active_hash = se.md5_hash 
    AND ae.active_entry_Id IN (" . implode(",", array_keys($active_entries)) . ')');
    foreach ($res  as $line) {
        foreach ($active_entries[$line['ACTIVE_ENTRY_ID']] as $P) {
            $data[$P]['CPD'] = $line;
        }
    }




    return $data;
}



function getCountPubliDisease($DISEASE_ENTRY_ID, $FILTERS = array())
{

    $query = "SELECT COUNT(*) CO
    FROM PMID_ENTRY PE, MV_DISEASE_PUBLI PRM";
    $WH_CLAUSE = '';
    if ($FILTERS != array()) {
        $NSTEP = 0;

        foreach ($FILTERS as $TYPE => $LIST) {
            foreach ($LIST as $RULE_TYPE) {
                ++$NSTEP;
                if ($TYPE == 'topic') {
                    $query .= ', MV_PUBLI_RULE MPR' . $NSTEP . ', PUBLI_RULE PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.PUBLI_RULE_ID = MPR' . $NSTEP . '.PUBLI_RULE_ID AND PR' . $NSTEP . '.RULE_NAME=\'' . $RULE_TYPE . '\'';
                }
                if ($TYPE == 'gene') {
                    $query .= ', MV_GENE_PUBLI MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
                }
                if ($TYPE == 'disease') {
                    $query .= ', MV_DISEASE_PUBLI MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
                }
            }
        }
    }
    $query .= " WHERE PRM.DISEASE_ENTRY_ID=" . $DISEASE_ENTRY_ID . " AND PRM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID " . $WH_CLAUSE;

    return runQuery($query)[0];
}


function getPubliFromDisease($DISEASE_ENTRY_ID, $PARAMS, $FILTERS = array())
{
    $data = array();

    $time = microtime_float();
    $query = "SELECT PMID FROM (
		SELECT ROW_NUMBER() OVER( ORDER BY PE.publication_date DESC,PE.PMID ASC) R, PE.PMID FROM MV_DISEASE_PUBLI PE ";
    $NSTEP = 0;
    $WH_CLAUSE = '';
    foreach ($FILTERS as $TYPE => $LIST) {
        foreach ($LIST as $RULE_TYPE) {
            ++$NSTEP;
            if ($TYPE == 'topic') {
                $query .= ', MV_PUBLI_RULE MPR' . $NSTEP . ', PUBLI_RULE PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.PUBLI_RULE_ID = MPR' . $NSTEP . '.PUBLI_RULE_ID AND PR' . $NSTEP . '.RULE_NAME=\'' . $RULE_TYPE . '\'';
            }
            if ($TYPE == 'gene') {
                $query .= ', MV_GENE_PUBLI MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
            }
            if ($TYPE == 'disease') {
                $query .= ', MV_DISEASE_PUBLI MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
            }
        }
    }
    $query .= " WHERE PE.DISEASE_ENTRY_ID=" . $DISEASE_ENTRY_ID . $WH_CLAUSE . " ) sub WHERE  R<" . ($PARAMS['MAX'] + 1) . " AND R>=" . ($PARAMS['MIN'] + 1);
    //echo $query;exit;
    $res = runQuery($query);
    $tmp = array();
    foreach ($res as $L) $tmp[] = $L['PMID'];


    return $tmp;
}

function getCountPubliGene($GN_ENTRY_ID, $FILTERS = array())
{

    $query = "SELECT COUNT(*) CO
    FROM PMID_ENTRY PE, PMID_GENE_MAP PRM";
    $WH_CLAUSE = '';
    if ($FILTERS != array()) {
        $NSTEP = 0;

        foreach ($FILTERS as $TYPE => $LIST) {
            foreach ($LIST as $RULE_TYPE) {
                ++$NSTEP;
                if ($TYPE == 'topic') {
                    $query .= ', MV_PUBLI_RULE MPR' . $NSTEP . ', PUBLI_RULE PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.PUBLI_RULE_ID = MPR' . $NSTEP . '.PUBLI_RULE_ID AND PR' . $NSTEP . '.RULE_NAME=\'' . $RULE_TYPE . '\'';
                }
                if ($TYPE == 'gene') {
                    $query .= ', MV_GENE_PUBLI MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
                }
                if ($TYPE == 'disease') {
                    $query .= ', MV_DISEASE_PUBLI MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
                }
            }
        }
    }
    $query .= " WHERE PRM.GN_ENTRY_ID=" . $GN_ENTRY_ID . " AND PRM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID " . $WH_CLAUSE;

    return runQuery($query)[0];
}


function getPubliFromGene($GN_ENTRY_ID, $PARAMS, $FILTERS = array())
{
    $data = array();

    $time = microtime_float();
    $query = "SELECT PMID FROM (
		SELECT ROW_NUMBER() OVER( ORDER BY PE.publication_date DESC,PE.PMID ASC) R, PE.PMID FROM MV_GENE_PUBLI PE ";
    $NSTEP = 0;
    $WH_CLAUSE = '';
    foreach ($FILTERS as $TYPE => $LIST) {
        foreach ($LIST as $RULE_TYPE) {
            ++$NSTEP;
            if ($TYPE == 'topic') {
                $query .= ', MV_PUBLI_RULE MPR' . $NSTEP . ', PUBLI_RULE PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.PUBLI_RULE_ID = MPR' . $NSTEP . '.PUBLI_RULE_ID AND PR' . $NSTEP . '.RULE_NAME=\'' . $RULE_TYPE . '\'';
            }
            if ($TYPE == 'gene') {
                $query .= ', MV_GENE_PUBLI MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
            }
            if ($TYPE == 'disease') {
                $query .= ', MV_DISEASE_PUBLI MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
            }
        }
    }
    $query .= " WHERE PE.GN_ENTRY_ID=" . $GN_ENTRY_ID . $WH_CLAUSE . " ) sub WHERE  R<" . ($PARAMS['MAX'] + 1) . " AND R>=" . ($PARAMS['MIN'] + 1);
    //echo $query;exit;
    $res = runQuery($query);
    $tmp = array();
    foreach ($res as $L) $tmp[] = $L['PMID'];


    return $tmp;
}

function getCurrReleaseDate()
{
    $DATA = array();
    $res = runQuery("select * FROM biorels_datasource");

    foreach ($res as $line) $DATA['SOURCE'][$line['SOURCE_NAME']] = array($line['RELEASE_VERSION'], $line['DATE_RELEASED']);
    $res = runQuery("select * FROM genome_assembly  g, taxon t where t.taxon_id = g.taxon_id");

    foreach ($res as $line) $DATA['GENOME'][$line['TAX_ID']][] = $line;

    return $DATA;
}


function getGenomeInfo()
{
    $DATA=array();
    $res = runQuery("select * FROM genome_assembly  g, taxon t where t.taxon_id = g.taxon_id");

    foreach ($res as $line) $DATA['GENOME'][$line['TAX_ID']][] = $line;

    return $DATA;


}

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// COMPOUND PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



/**
 * @param string $CPD_NAME
 * @param bool $IS_PRIMARY
 * @return array
 * @throws Exception
 */
function compound_portal_compound_sm_name($SM_NAME, $IS_PRIMARY = false)
{
    global $DB_CONN;
    $SM_NAME = $DB_CONN->quote($SM_NAME);
    // CHECK FOR EMPTY STRING EARLY RETURN 

    if ($SM_NAME == '') return array();

    $DATA = array();
    $res = runQuery("SELECT * FROM SM_SOURCE WHERE SM_NAME = $SM_NAME");
    foreach ($res as $line) $DATA[$line['SM_ENTRY_ID']] = $line;

    return array_values($DATA)[0];
}

function compound_portal_compound_sm_id($SM_ID, $IS_PRIMARY = false)
{
    global $DB_CONN;
    $SM_ID = $DB_CONN->quote($SM_ID);
    // CHECK FOR EMPTY STRING EARLY RETURN 
    if ($SM_ID == '') return array();

    $DATA = array();
    $res = runQuery("SELECT * FROM SM_SOURCE WHERE SM_ENTRY_ID = $SM_ID");

    foreach ($res as $line) $DATA[$line['SM_ENTRY_ID']] = $line;

    return array_values($DATA)[0];
}



function getCountPubliCompound($CP_ENTRY_ID, $FILTERS = array())
{

    $query = "SELECT COUNT(*) CO
    FROM PMID_ENTRY PE, SM_PUBLI_MAP SMP           
    ";
    $WH_CLAUSE = '';
    if ($FILTERS != array()) {
        $NSTEP = 0;

        foreach ($FILTERS as $TYPE => $LIST) {
            foreach ($LIST as $RULE_TYPE) {
                ++$NSTEP;
                if ($TYPE == 'topic') {
                    $query .= ', MV_PUBLI_RULE MPR' . $NSTEP . ', PUBLI_RULE PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.PUBLI_RULE_ID = MPR' . $NSTEP . '.PUBLI_RULE_ID AND PR' . $NSTEP . '.RULE_NAME=\'' . $RULE_TYPE . '\'';
                }
                if ($TYPE == 'gene') {
                    $query .= ', MV_GENE_PUBLI MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
                }
                if ($TYPE == 'disease') {
                    $query .= ', MV_DISEASE_PUBLI MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
                }
                if ($TYPE == 'drug') {
                    $query .= ', MV_DRUG_PUBLI MPR' . $NSTEP . ', DRUG_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DRUG_ENTRY_ID = MPR' . $NSTEP . '.DRUG_ENTRY_ID AND PR' . $NSTEP . '.DRUG_ENTRY_ID=' . $RULE_TYPE;
                }
            }
        }
    }
    $query .= " WHERE SMP.SM_ENTRY_ID=" . $CP_ENTRY_ID . " AND PE.PMID_ENTRY_ID = SMP.PMID_ENTRY_ID " . $WH_CLAUSE;

    return runQuery($query)[0];
}


function getPubliFromCompound($CP_ENTRY_ID, $PARAMS, $FILTERS = array())
{
    $data = array();

    $time = microtime_float();
    $query = "SELECT PMID FROM (
		SELECT ROW_NUMBER() OVER( ORDER BY PE.publication_date DESC,PE.PMID ASC) R, PE.PMID FROM PMID_ENTRY PE, SM_PUBLI_MAP SMP, SM_ENTRY SM";
    $NSTEP = 0;
    $WH_CLAUSE = '';
    foreach ($FILTERS as $TYPE => $LIST) {
        foreach ($LIST as $RULE_TYPE) {
            ++$NSTEP;
            if ($TYPE == 'topic') {
                $query .= ', MV_PUBLI_RULE MPR' . $NSTEP . ', PUBLI_RULE PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.PUBLI_RULE_ID = MPR' . $NSTEP . '.PUBLI_RULE_ID AND PR' . $NSTEP . '.RULE_NAME=\'' . $RULE_TYPE . '\'';
            }
            if ($TYPE == 'gene') {
                $query .= ', MV_GENE_PUBLI MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
            }
            if ($TYPE == 'disease') {
                $query .= ', MV_DISEASE_PUBLI MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
            }
            if ($TYPE == 'drug') {
                $query .= ', MV_DRUG_PUBLI MPR' . $NSTEP . ', DRUG_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DRUG_ENTRY_ID = MPR' . $NSTEP . '.DRUG_ENTRY_ID AND PR' . $NSTEP . '.SM_ENTRY_ID=' . $RULE_TYPE;
            }
        }
    }
    $query .= " WHERE PE.PMID_ENTRY_ID = SMP.PMID_ENTRY_ID AND SM.SM_ENTRY_ID = SMP.SM_ENTRY_ID AND SM.SM_ENTRY_ID=" . $CP_ENTRY_ID . $WH_CLAUSE . " ) sub WHERE  R<" . ($PARAMS['MAX'] + 1) . " AND R>=" . ($PARAMS['MIN'] + 1);
    $res = runQuery($query);
    $tmp = array();
    foreach ($res as $L) $tmp[] = $L['PMID'];


    return $tmp;
}



///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// DRUG PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

function searchDrugByName($DRUG_NAME,$WITH_STRUCTURE=true)
{
    $res=runQuery("SELECT * FROM DRUG_ENTRY WHERE LOWER(DRUG_PRIMARY_NAME) LIKE LOWER('%".$DRUG_NAME."%')");
    $DATA=array();
    foreach ($res as $line)$DATA[$line['DRUG_ENTRY_ID']]=$line;
    if ($DATA==array())
    {

        $res=runQuery("SELECT de.* FROM drug_entry de, drug_name dn where de.drug_entry_id = dn.drug_entry_id and LOWER(DRUG_NAME) LIKE LOWER('%".$DRUG_NAME."%')");
        foreach ($res as $line)$DATA[$line['DRUG_ENTRY_ID']]=$line;
        
        if ($DATA==array())
        {
            $res=runQuery("SELECT de.* FROM drug_entry de, drug_extdb dn where de.drug_entry_id = dn.drug_entry_id and LOWER(drug_extdb_value) LIKE LOWER('%".$DRUG_NAME."%')");
            foreach ($res as $line)$DATA[$line['DRUG_ENTRY_ID']]=$line;
        }
        if ($DATA==array())return array();
    }
    if (!$WITH_STRUCTURE)return $DATA;
    $res=runQuery("SELECT * FROM drug_mol_entity_map dmem, molecular_entity me, sm_molecule sm, sm_entry se 
    LEFT JOIN sm_counterion sc on sc.sm_counterion_id =se.sm_counterion_id 
    WHERE se.sm_molecule_id = sm.sm_molecule_id 
    AND se.md5_hash=me.molecular_structure_hash 
    and me.molecular_entity_id = dmem.molecular_entity_id
    AND drug_entry_Id IN (".implode(',',array_keys($DATA)).')');
    
    foreach ($res as $line)
    $DATA[$line['DRUG_ENTRY_ID']]['SM'][]=$line;
    

    return $DATA;

    
}



function drug_portal_drug_name($DRUG_NAME, $IS_PRIMARY = false)

{
   $DATA=searchDrugByName($DRUG_NAME,false);
    if ($DATA==array())return array();
    return getDrugInfo(array_keys($DATA)[0]);
  
}

function getCountPubliDrug($DRUG_ENTRY_ID, $FILTERS = array())
{

    $query = "SELECT COUNT(*) CO
    FROM PMID_ENTRY PE, MV_DRUG_PUBLI SMP           
    ";
    $WH_CLAUSE = '';
    
    if ($FILTERS != array()) {
        $NSTEP = 0;

        foreach ($FILTERS as $TYPE => $LIST) {
            foreach ($LIST as $RULE_TYPE) {
                ++$NSTEP;
                if ($TYPE == 'topic') {
                    $query .= ', MV_PUBLI_RULE MPR' . $NSTEP . ', PUBLI_RULE PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.PUBLI_RULE_ID = MPR' . $NSTEP . '.PUBLI_RULE_ID AND PR' . $NSTEP . '.RULE_NAME=\'' . $RULE_TYPE . '\'';
                }
                if ($TYPE == 'gene') {
                    $query .= ', MV_GENE_PUBLI MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
                }
                if ($TYPE == 'disease') {
                    $query .= ', MV_DISEASE_PUBLI MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
                }
                if ($TYPE == 'drug') {
                    $query .= ', MV_DRUG_PUBLI MPR' . $NSTEP . ', DRUG_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DRUG_ENTRY_ID = MPR' . $NSTEP . '.DRUG_ENTRY_ID AND PR' . $NSTEP . '.DRUG_ENTRY_ID=' . $RULE_TYPE;
                }
                if ($TYPE == 'compound') {
                    $query .= ', SM_PUBLI_MAP MPR' . $NSTEP . ', SM_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND PE.PMID_ENTRY_ID=MPR' . $NSTEP . '.PMID_ENTRY_ID AND PR' . $NSTEP . '.SM_ENTRY_ID = MPR' . $NSTEP . '.SM_ENTRY_ID AND PR' . $NSTEP . '.SM_ENTRY_ID=' . $RULE_TYPE;
                }
            }
        }
    }
    $query .= " WHERE SMP.DRUG_ENTRY_ID=" . $DRUG_ENTRY_ID . " AND PE.PMID_ENTRY_ID = SMP.PMID_ENTRY_ID " . $WH_CLAUSE;
    
    return runQuery($query)[0];
}


function getPubliFromDrug($DRUG_ENTRY_ID, $PARAMS, $FILTERS = array())
{
    $data = array();

    $time = microtime_float();
    $query = "SELECT PMID FROM (
		SELECT ROW_NUMBER() OVER( ORDER BY PE.publication_date DESC,PE.PMID ASC) R, PE.PMID FROM PMID_ENTRY PE, MV_DRUG_PUBLI SMP";
    $NSTEP = 0;
    $WH_CLAUSE = '';
    foreach ($FILTERS as $TYPE => $LIST) {
        foreach ($LIST as $RULE_TYPE) {
            ++$NSTEP;
            if ($TYPE == 'topic') {
                $query .= ', MV_PUBLI_RULE MPR' . $NSTEP . ', PUBLI_RULE PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.PUBLI_RULE_ID = MPR' . $NSTEP . '.PUBLI_RULE_ID AND PR' . $NSTEP . '.RULE_NAME=\'' . $RULE_TYPE . '\'';
            }
            if ($TYPE == 'gene') {
                $query .= ', MV_GENE_PUBLI MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
            }
            if ($TYPE == 'disease') {
                $query .= ', MV_DISEASE_PUBLI MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
            }
            if ($TYPE == 'drug') {
                $query .= ', MV_DRUG_PUBLI MPR' . $NSTEP . ', DRUG_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID=MPR' . $NSTEP . '.PMID AND PR' . $NSTEP . '.DRUG_ENTRY_ID = MPR' . $NSTEP . '.DRUG_ENTRY_ID AND PR' . $NSTEP . '.DRUG_ENTRY_ID=' . $RULE_TYPE;
            }
            if ($TYPE == 'compound') {
                $query .= ', SM_PUBLI_MAP MPR' . $NSTEP . ', SM_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND PE.PMID_ENTRY_ID=MPR' . $NSTEP . '.PMID_ENTRY_ID AND PR' . $NSTEP . '.SM_ENTRY_ID = MPR' . $NSTEP . '.SM_ENTRY_ID AND PR' . $NSTEP . '.SM_ENTRY_ID=' . $RULE_TYPE;
            }
        }
    }
    $query .= " WHERE PE.PMID_ENTRY_ID = SMP.PMID_ENTRY_ID AND SMP.DRUG_ENTRY_ID=" . $DRUG_ENTRY_ID . $WH_CLAUSE . " ) sub WHERE  R<" . ($PARAMS['MAX'] + 1) . " AND R>=" . ($PARAMS['MIN'] + 1);
  
    $res = runQuery($query);
    $tmp = array();
    foreach ($res as $L) $tmp[] = $L['PMID'];


    return $tmp;
}




///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// TISSUE PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
function getListTissues()
{
    return runQuery("SELECT RNA_TISSUE_ID, TISSUE_NAME,ORGAN_NAME, SCIENTIFIC_NAME FROM RNA_TISSUE RT, TAXON T WHERE RT.TAXON_ID=T.TAXON_ID ORDER BY SCIENTIFIC_NAME, ORGAN_NAME, TISSUE_NAME");
}
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// PATHWAY PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


function getPathwayStats($GN_ENTRY_ID)
{
    return runQuery("SELECT PE2.PW_NAME, COUNT(*) CO FROM PW_ENTRY PE2, PW_HIERARCHY PH2, PW_HIERARCHY PH,
    PW_ENTRY PE, PW_GN_MAP PGM
	WHERE PGM.GN_ENTRY_ID=" . $GN_ENTRY_ID . " AND PGM.PW_ENTRY_ID=PE.PW_ENTRY_ID
	AND PE2.PW_ENTRY_ID = PH2.PW_ENTRY_ID AND PH.PW_ENTRY_ID =PE.PW_ENTRY_ID
	AND PH.LEVEL_LEFT >PH2.LEVEL_LEFT AND PH.LEVEL_RIGHT < PH2.LEVEL_RIGHT AND PH2.PW_LEVEL=1 GROUP BY PE2.PW_NAME ORDER BY COUNT(*) DESC");
}
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// PROTEIN PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////




function getUniprotDescription($PRIM_UN, $FUNCTION_ONLY = false)
{
    $query = "SELECT UD.PROT_DESC_ID FROM PROT_ENTRY UE, PROT_DESC UD 
     WHERE UD.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND PROT_IDENTIFIER='" . $PRIM_UN . "' ";
    if ($FUNCTION_ONLY) $query .= ' AND DESC_TYPE=\'FUNCTION\'';
    $res = runQuery($query);

    $DATA = array();
    foreach ($res as $L) {
        $query = "SELECT * FROM PROT_DESC UD  WHERE PROT_DESC_ID=" . $L['PROT_DESC_ID'];
        $line = runQuery($query)[0];
        //$DATA[$line['DESC_TYPE']][]=trim(stream_get_contents($line['DESCRIPTION']));
        $DATA[$line['DESC_TYPE']][] = trim(($line['DESCRIPTION']));
    }


    return $DATA;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// PATHWAY PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

function getPathwayFromGene($GN_ENTRY_ID)
{
    $query = 'SELECT PE.PW_ENTRY_ID, REAC_ID,PW_NAME,SCIENTIFIC_NAME,TAX_ID
			FROM PW_ENTRY PE, PW_GN_MAP PGM, TAXON T
			WHERE PE.PW_ENTRY_ID=PGM.PW_ENTRY_ID AND T.TAXON_ID = PE.TAXON_ID AND GN_ENTRY_ID=' . $GN_ENTRY_ID;

    $res = runQuery($query);
    $data = array();
    foreach ($res as $PW) {
        $PW['Lineage']=array();
        $data['PATHWAYS'][$PW['PW_ENTRY_ID']] = $PW;
    }
    if (count($data) == 0) return $data;
    $query = "SELECT PE2.PW_ENTRY_ID,PE.PW_NAME,PH1.PW_LEVEL,PE.REAC_ID, PH1.LEVEL_LEFT, PH1.LEVEL_RIGHT
	FROM PW_ENTRY PE, PW_HIERARCHY PH1, PW_HIERARCHY PH2, PW_ENTRY PE2
				WHERE PE.PW_ENTRY_ID = PH1.PW_ENTRY_ID
				AND PH1.LEVEL_LEFT<=PH2.LEVEL_LEFT
				AND PH1.LEVEL_RIGHT>=PH2.LEVEL_RIGHT
				AND PH2.PW_ENTRY_ID = PE2.PW_ENTRY_ID
				AND PE2.PW_ENTRY_ID IN  (" . implode(',', array_keys($data['PATHWAYS'])) . ")
				AND PE.TAXON_ID=PE2.TAXON_ID

				ORDER BY PE2.PW_ENTRY_ID, PH1.PW_LEVEL ASC";

    $res = runQuery($query);
    
    foreach ($res as $PW)
        $data['PATHWAYS'][$PW['PW_ENTRY_ID']]['Lineage'][] = $PW;
    return $data;
}

function getPathwayFromProtein($PROT_IDENTIFIER)
{
    $query = 'SELECT DISTINCT PE.PW_ENTRY_ID, REAC_ID,PW_NAME,SCIENTIFIC_NAME,TAX_ID
			FROM PW_ENTRY PE, PW_GN_MAP PGM, TAXON T, GN_PROT_MAP GPM, PROT_ENTRY PR
			WHERE PE.PW_ENTRY_ID=PGM.PW_ENTRY_ID
             AND T.TAXON_ID = PE.TAXON_ID 
             AND PR.PROT_ENTRY_Id = GPM.PROT_ENTRY_ID
             AND GPM.GN_ENTRY_Id = PGM.GN_ENTRY_ID
             AND PROT_IDENTIFIER=\'' . $PROT_IDENTIFIER . '\'';

    $res = runQuery($query);

    foreach ($res as $PW) {
        $data['PATHWAYS'][$PW['PW_ENTRY_ID']] = $PW;
    }
    if (count($data) == 0) return $data;
    $query = "SELECT PE2.PW_ENTRY_ID,PE.PW_NAME,PH1.PW_LEVEL,PE.REAC_ID, PH1.LEVEL_LEFT, PH1.LEVEL_RIGHT
	FROM PW_ENTRY PE, PW_HIERARCHY PH1, PW_HIERARCHY PH2, PW_ENTRY PE2
				WHERE PE.PW_ENTRY_ID = PH1.PW_ENTRY_ID
				AND PH1.LEVEL_LEFT<=PH2.LEVEL_LEFT
				AND PH1.LEVEL_RIGHT>=PH2.LEVEL_RIGHT
				AND PH2.PW_ENTRY_ID = PE2.PW_ENTRY_ID
				AND PE2.PW_ENTRY_ID IN  (" . implode(',', array_keys($data['PATHWAYS'])) . ")
				AND PE.TAXON_ID=PE2.TAXON_ID

				ORDER BY PE2.PW_ENTRY_ID, PH1.PW_LEVEL ASC";

    $res = runQuery($query);

    foreach ($res as $PW)
        $data['PATHWAYS'][$PW['PW_ENTRY_ID']]['Lineage'][] = $PW;
    return $data;
}

function getPathwayFromReacID($REAC_ID)
{
    $query = "SELECT PE.PW_ENTRY_ID, REAC_ID,PW_NAME,SCIENTIFIC_NAME,TAX_ID
	FROM PW_ENTRY PE , TAXON T
	WHERE T.TAXON_ID=PE.TAXON_ID AND REAC_ID='" . $REAC_ID . "'";

    $res = runQuery($query);
    $data = array();
    foreach ($res as $PW) {
        $data['PATHWAYS'][$PW['PW_ENTRY_ID']] = $PW;
    }
    if (count($data) == 0) return $data;
    //print_r($data);
    $query = "SELECT PE2.PW_ENTRY_ID,PE.PW_NAME,PH1.PW_LEVEL,PE.REAC_ID, PH1.LEVEL_LEFT, PH1.LEVEL_RIGHT
	FROM PW_ENTRY PE, PW_HIERARCHY PH1, PW_HIERARCHY PH2, PW_ENTRY PE2
				WHERE PE.PW_ENTRY_ID = PH1.PW_ENTRY_ID
				AND PH1.LEVEL_LEFT<=PH2.LEVEL_LEFT
				AND PH1.LEVEL_RIGHT>=PH2.LEVEL_RIGHT
				AND PH2.PW_ENTRY_ID = PE2.PW_ENTRY_ID
				AND PE2.PW_ENTRY_ID IN  (" . implode(',', array_keys($data['PATHWAYS'])) . ")
				AND PE.TAXON_ID=PE2.TAXON_ID

				ORDER BY PE2.PW_ENTRY_ID, PH1.PW_LEVEL ASC";
    $res = runQuery($query);
    foreach ($res as $PW)
        $data['PATHWAYS'][$PW['PW_ENTRY_ID']]['Lineage'][] = $PW;
    return $data;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// PUBLI PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


function getAllPubliRules()
{
    $res = runQuery("SELECT * FROM PUBLI_RULE ");
    $data = array();
    foreach ($res as $line) {
        $data[$line['RULE_GROUP']][$line['RULE_SUBGROUP']][$line['RULE_NAME']] = array($line['RULE_DESC'], $line['PUBMED_QUERY'], $line['PUBLI_RULE_ID']);
    }
    return $data;
}


function getPubliRule($RULE_NAME)
{
    $query = "SELECT * FROM PUBLI_RULE WHERE RULE_NAME='" . $RULE_NAME . "'";
    echo $query;
    return runQuery($query);
}

function getPubliRules($GROUP_NAME, $SUB_GROUP)
{
    return runQuery("SELECT * FROM PUBLI_RULE WHERE RULE_GROUP='" . $GROUP_NAME . "'" . (($SUB_GROUP == "*") ? "" : "AND RULE_SUBGROUP='" . $SUB_GROUP . "' "));
}

function loadSimplePublicationData($PMID)
{
    $DATA['ENTRY'] = runQuery("SELECT PMID, PUBLICATION_DATE::TIMESTAMP::DATE, TITLE,DOI,VOLUME,PAGES, JOURNAL_NAME,JOURNAL_ABBR FROM PMID_ENTRY PE LEFT JOIN PMID_JOURNAL PJ ON PJ.PMID_JOURNAL_ID = PE.PMID_JOURNAL_ID  WHERE  PMID=" . $PMID)[0];

    return $DATA;
}

function loadPublicationData($PMID, $TYPE = 'PMID')
{
    $DATA = array('ENTRY' => array());
    $query = "SELECT pmid_entry_id, pmid,publication_date::TIMESTAMP::DATE,title,doi,volume,issue,pages,pmid_status,
    journal_name,journal_abbr,issn_print,issn_online,iso_abbr,nlmid 
    FROM PMID_ENTRY PE 
    LEFT JOIN PMID_JOURNAL PJ ON PJ.PMID_JOURNAL_ID = PE.PMID_JOURNAL_ID ";
    if ($TYPE == 'PMID') $query .= " WHERE  PMID=" . $PMID;
    else $query .= " WHERE DOI='" . $PMID . "'";
    $TMP = runQuery($query);
    if ($TMP === false || count($TMP) == 0) return $DATA;
    $DATA['ENTRY'] = $TMP[0];
    if (count($DATA['ENTRY']) == 0) return $DATA;
    $res = runQuery("SELECT * FROM PMID_ABSTRACT WHERE PMID_ENTRY_ID = " . $DATA['ENTRY']['PMID_ENTRY_ID'] . ' ORDER BY PMID_ABSTRACT_ID ASC');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA['ENTRY']['ABSTRACT'][$line['ABSTRACT_TYPE']] = $line['ABSTRACT_TEXT'];
    }

    $DATA['AUTHORS'] = runQuery("SELECT LAST_NAME,FIRST_NAME, ORCID_ID, INSTIT_NAME,PA.PMID_AUTHOR_ID , PI.PMID_INSTIT_ID,PI.INSTIT_PRIM_ID
	FROM PMID_AUTHOR_MAP PAM, PMID_AUTHOR PA
	LEFT JOIN PMID_INSTIT PI ON pa.pmid_instit_id = pi.pmid_instit_id
	WHERE pam.pmid_author_id=PA.pmid_author_id AND PMID_ENTRY_ID=" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    $DATA['TAGS']['RULE'] = runQuery("SELECT RULE_NAME,RULE_GROUP,RULE_DESC FROM PUBLI_RULE_MAP PRM, PUBLI_RULE PR
	WHERE PR.PUBLI_RULE_ID = PRM.PUBLI_RULE_ID AND PMID_ENTRY_ID=" . $DATA['ENTRY']['PMID_ENTRY_ID'] . ' AND WEB_USER_ID IS NULL');

    $DATA['TAGS']['GENE'] = runQuery("SELECT SYMBOL,GENE_ID,full_name FROM PMID_GENE_MAP PRM, GN_ENTRY GR
	WHERE GR.GN_ENTRY_ID = PRM.GN_ENTRY_ID AND PMID_ENTRY_ID=" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    $DATA['TAGS']['GO'] = runQuery("SELECT DISTINCT AC,NAMESPACE,NAME FROM GO_ENTRY GE, GO_PMID_MAP GP
	WHERE GP.GO_ENTRY_ID = GE.GO_ENTRY_ID AND IS_OBSOLETE='F'  AND PMID_ENTRY_ID=" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    $DATA['TAGS']['DISEASE'] = runQuery("SELECT DISTINCT DISEASE_NAME ,DISEASE_TAG,disease_definition FROM DISEASE_ENTRY DE, MV_DISEASE_PUBLI  PD WHERE PD.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID AND PMID_ENTRY_ID=" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    $DATA['TAGS']['PROT_FEAT'] = runQuery("SELECT DISTINCT FEAT_NAME,PROT_IDENTIFIER,FEAT_VALUE,ISO_ID 
	FROM PROT_FEAT UF,PROT_SEQ US, PROT_FEAT_TYPE UFT, PROT_ENTRY UE, PROT_FEAT_PMID U WHERE 
	UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = UF.PROT_SEQ_ID AND UF.PROT_FEAT_ID=U.PROT_FEAT_ID AND UFT.PROT_FEAT_TYPE_ID = UF.PROT_FEAT_TYPE_ID  AND PMID_ENTRY_ID=" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    $DATA['TAGS']['DRUG'] = runQuery("SELECT PMID_ENTRY_ID,DRUG_PRIMARY_NAME FROM PMID_DRUG_MAP P, DRUG_ENTRY D WHERE  P.DRUG_ENTRY_Id = D.DRUG_ENTRY_ID  AND PMID_ENTRY_ID =" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    $DATA['TAGS']['CELL'] = runQuery("SELECT cell_name,pmid_entry_id,cell_type FROM cell_pmid_map c, cell_entry e where e.cell_entry_id = c.cell_entry_id AND PMID_ENTRY_ID  =" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    $DATA['TAGS']['ASSAY'] = runQuery("select assay_name, assay_description, pmid_entry_id FROM assay_entry a, assay_pmid p where p.assay_entry_id = a.assay_entry_id AND PMID_ENTRY_ID =" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    $DATA['TAGS']['TISSUE'] = runQuery("select anatomy_name, pmid_entry_id,anatomy_definition FROM anatomy_entry a, pmid_anatomy_map p where p.anatomy_entry_id = a.anatomy_entry_id AND PMID_ENTRY_ID  =" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    // $res = runQuery("SELECT DISTINCT PMID_ENTRY_ID,DISEASE_NAME FROM DISEASE_ENTRY DE, PMID_DISEASE_MAP  PD WHERE PD.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID AND PMID_ENTRY_ID  =" . $DATA['ENTRY']['PMID_ENTRY_ID']);
    // if ($res !== false && count($res) != 0) {

    //     foreach ($res as $line) $DATA['TAGS']['DISEASE'][] = $line;
    // }
    return $DATA;
}

function getEcoInfo($LIST)
{
    $query = 'SELECT ECO_ID,ECO_NAME,ECO_DESCRIPTION FROM ECO_ENTRY WHERE ECO_ID IN (';
    foreach ($LIST as $V) {
        if (is_numeric($V)) $query .= "'ECO_" . $V . "',";
        else $query .= "'" . $V . "',";
    }
    $query = substr($query, 0, -1) . ')';
    $res = runQuery($query);
    $DATA = array();
    foreach ($res as $line) $DATA[$line['ECO_ID']] = $line;
    return $DATA;
}




function getPublicationInfo($PMID, $TYPE)
{
    $ALLOWED = array('DRUG', 'CLINVAR', 'PATHWAY', 'GENE', 'DISEASE', 'PROT_FEAT', 'ASSAY', 'CELL', 'TISSUE', 'EVIDENCE');
    if (!in_array($TYPE, $ALLOWED)) return array();
    $DATA = array();
    switch ($TYPE) {
        case 'DRUG':
            $res = runQuery("SELECT DISTINCT DRUG_ENTRY_ID FROM PMID_DRUG_MAP P, PMID_ENTRY PE WHERE PE.PMID_ENTRY_ID = P.PMID_ENTRY_ID AND PMID  =" . $PMID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) $DATA[$line['DRUG_ENTRY_ID']] = getDrugInfo($line['DRUG_ENTRY_ID']);
            }

            break;
        case 'CLINVAR':
            break;
        case 'EVIDENCE':
            $res = runQuery("SELECT DISTINCT de.disease_tag, disease_name, gene_id,symbol,full_name,section,text_content 
            FROM pmid_entry p, pmid_disease_gene pdg, pmid_disease_gene_txt pdgt, disease_Entry de, gn_entry ge 
            where pdg.gn_entry_id = ge.gn_Entry_id 
            AND pdg.disease_entry_id = de.disease_entry_id 
            AND pdg.pmid_disease_gene_id = pdgt.pmid_Disease_gene_id
             AND pdg.pmid_entry_id = p.pmid_entry_id 
             AND pmid=" . $PMID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) {
                    $DATA['GENE'][$line['GENE_ID']] = array('SYMBOL' => $line['SYMBOL'], 'NAME' => $line['FULL_NAME']);
                    $DATA['DISEASE'][$line['DISEASE_TAG']] = array('NAME' => $line['DISEASE_NAME']);
                    $DATA['EVIDENCE'][$line['DISEASE_TAG']][$line['GENE_ID']][$line['SECTION']][] = $line['TEXT_CONTENT'];
                }
            }
            break;
        case 'PATHWAY':
            break;
        case 'GENE':
            $res = runQuery("SELECT SYMBOL,GENE_ID,full_name,DESCRIPTION,confidence FROM PMID_ENTRY P,PMID_GENE_MAP PRM, GN_ENTRY GR
             LEFT JOIN GN_PROT_MAP PGM ON PGM.GN_ENTRY_ID = GR.GN_ENTRY_ID
             LEFT JOIN PROT_DESC PE ON PE.PROT_ENTRY_ID = PGM.PROT_ENTRY_ID AND DESC_TYPE='FUNCTION'
     WHERE GR.GN_ENTRY_ID = PRM.GN_ENTRY_ID AND P.PMID_ENTRY_ID=PRM.PMID_ENTRY_ID AND PMID=" . $PMID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) {
                    $DATA[$line['GENE_ID']] = array('SYMBOL' => $line['SYMBOL'], 'NAME' => $line['FULL_NAME'],'CONFIDENCE'=>$line['CONFIDENCE']);
                    if ($line['DESCRIPTION'] != '') $DATA[$line['GENE_ID']]['DESCRIPTION'][] = $line['DESCRIPTION'];
                }
            }
            $res = runQuery("SELECT SYMBOL,GENE_ID,full_name,DESCRIPTION,confidence FROM PMID_ENTRY P,PMID_GENE_MAP PRM, GN_ENTRY GR
             LEFT JOIN GN_PROT_MAP PGM ON PGM.GN_ENTRY_ID = GR.GN_ENTRY_ID
             LEFT JOIN PROT_DESC PE ON PE.PROT_ENTRY_ID = PGM.PROT_ENTRY_ID AND DESC_TYPE='FUNCTION'
     WHERE GR.GN_ENTRY_ID = PRM.GN_ENTRY_ID AND P.PMID_ENTRY_ID=PRM.PMID_ENTRY_ID AND PMID=" . $PMID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) {
                    $DATA[$line['GENE_ID']] = array('SYMBOL' => $line['SYMBOL'], 'NAME' => $line['FULL_NAME'],'CONFIDENCE'=>$line['CONFIDENCE']);
                    if ($line['DESCRIPTION'] != '') $DATA[$line['GENE_ID']]['DESCRIPTION'][] = $line['DESCRIPTION'];
                }
            }
            break;
        case 'DISEASE':
            $res = runQuery("SELECT DISTINCT PE.PMID_ENTRY_ID,DISEASE_NAME,disease_tag,disease_definition,confidence
              FROM DISEASE_ENTRY DE, PMID_DISEASE_MAP  PD, PMID_ENTRY PE WHERE PE.PMID_ENTRY_Id = PD.PMID_ENTRY_ID AND PD.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID AND PMID=" . $PMID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) $DATA[$line['DISEASE_TAG']] = $line;
            }
            break;
        case 'PROT_FEAT':
            $res = runQuery("SELECT DISTINCT FEAT_NAME,PROT_IDENTIFIER,FEAT_VALUE,ISO_ID ,GENE_ID,SYMBOL,FULL_NAME,confidence
            FROM PROT_FEAT UF,PROT_SEQ US, PROT_FEAT_TYPE UFT, PROT_ENTRY UE
            LEFT JOIN GN_PROT_MAP GPM ON GPM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
            LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GPM.GN_ENTRY_ID, PROT_FEAT_PMID U, PMID_ENTRY PE WHERE 
            UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = UF.PROT_SEQ_ID AND UF.PROT_FEAT_ID=U.PROT_FEAT_ID 
            AND PE.PMID_ENTRY_ID = U.PMID_ENTRY_ID
            AND UFT.PROT_FEAT_TYPE_ID = UF.PROT_FEAT_TYPE_ID   AND PMID=" . $PMID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) $DATA[] = $line;
            }

            break;
        case 'ASSAY':
            break;
        case 'CELL':
            break;
        case 'TISSUE':
            $res = runQuery("SELECT anatomy_tag,anatomy_name,anatomy_definition ,confidence
            FROM anatomy_entry a, pmid_anatomy_map p ,pmid_entry pe
            where p.anatomy_entry_id = a.anatomy_entry_id  
            AND PE.pmid_entry_Id = p.pmid_entry_Id
            AND PMID=" . $PMID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) {
                    $DATA[$line['ANATOMY_TAG']] = $line;
                }
            }
            break;
    }
    return $DATA;



    // $res = runQuery("SELECT DISTINCT PMID_ENTRY_ID,AC,NAMESPACE,NAME FROM GO_ENTRY GE, GO_PMID_MAP GP
    // WHERE GP.GO_ENTRY_ID = GE.GO_ENTRY_ID AND IS_OBSOLETE='F'  AND PMID_ENTRY_ID  IN (" . implode(',',array_keys($DATA)).')');
    //  if ($res !== false && count($res) != 0) {

    //     foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['GO'][] = $line;
    // }
    // $res = runQuery("SELECT cell_name,pmid_entry_id,cell_type FROM cell_pmid_map c, cell_entry e where e.cell_entry_id = c.cell_entry_id AND PMID_ENTRY_ID  IN (" . implode(',',array_keys($DATA)).')');
    //  if ($res !== false && count($res) != 0) {

    //     foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['CELL'][] = $line;
    // }
    // $res = runQuery("select assay_name, pmid_entry_id, assay_description FROM assay_entry a, assay_pmid p where p.assay_entry_id = a.assay_entry_id AND PMID_ENTRY_ID  IN (" . implode(',',array_keys($DATA)).')');
    //  if ($res !== false && count($res) != 0) {

    //     foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['ASSAY'][] = $line;
    // }
    // $res = runQuery("select anatomy_name, pmid_entry_id,anatomy_definition FROM anatomy_entry a, pmid_anatomy_map p where p.anatomy_entry_id = a.anatomy_entry_id AND PMID_ENTRY_ID  IN (" . implode(',',array_keys($DATA)).')');
    //  if ($res !== false && count($res) != 0) {

    //     foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['TISSUE'][] = $line;
    // }

    // $res = runQuery("SELECT DISTINCT PMID_ENTRY_ID,DISEASE_NAME,disease_tag, disease_definition FROM DISEASE_ENTRY DE, PMID_DISEASE_GENE  PD WHERE PD.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID AND PMID_ENTRY_ID  IN (" . implode(',',array_keys($DATA)).')');
    // if ($res !== false && count($res) != 0) {

    //     foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['DISEASE'][$line['DISEASE_TAG']] = $line;
    // }
    // $res = runQuery("SELECT DISTINCT PMID_ENTRY_ID,DISEASE_NAME,disease_tag,disease_definition FROM DISEASE_ENTRY DE, PMID_DISEASE_MAP  PD WHERE PD.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID AND PMID_ENTRY_ID  IN (" . implode(',',array_keys($DATA)).')');
    // if ($res !== false && count($res) != 0) {

    //     foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['DISEASE'][$line['DISEASE_TAG']] = $line;
    // }
    // // $res = runQuery("SELECT PMID_ENTRY_ID,CE.CLINV_IDENTIFIER FROM CLINV_ENTRY CE, CLINV_ASSERT CA, CLINV_OBSV CO,CLINV_OBSVDT CD
    // // WHERE CE.CLINV_ENTRY_ID = CA.CLINV_ENTRY_ID AND CA.CLINV_ASSERT_ID = CO.CLINV_ASSERT_ID AND CO.CLINV_OBSV_ID = CD.CLINV_OBSV_ID AND PMID_ENTRY_ID  IN (" . implode(',',array_keys($DATA)).')');
    // //  if ($res !== false && count($res) != 0) {

    // //     foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['CLINVAR'][] = $line;
    // // }
    // $res = runQuery("SELECT PMID_ENTRY_ID,DRUG_NAME,DESCRIPTION FROM PMID_DRUG_MAP P,  DRUG_NAME D, DRUG_ENTRY DE WHERE DE.DRUG_ENTRY_ID = D.DRUG_ENTRY_ID AND P.DRUG_ENTRY_Id = D.DRUG_ENTRY_ID  AND IS_PRIMARY='T' AND IS_TRADENAME='F' AND PMID_ENTRY_ID  IN (" . implode(',',array_keys($DATA)).')');
    //  if ($res !== false && count($res) != 0) {

    //     foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['DRUG'][] = $line;
    // }

}
function loadBatchPublicationData($PMIDs)
{


    $DATA = array();
    $query = "SELECT pmid_entry_id, pmid,publication_date::TIMESTAMP::DATE,title,doi,volume,issue,pages,pmid_status,journal_name,journal_abbr,issn_print,issn_online,iso_abbr,nlmid FROM PMID_ENTRY PE LEFT JOIN PMID_JOURNAL PJ ON PJ.PMID_JOURNAL_ID = PE.PMID_JOURNAL_ID  WHERE  PMID IN (" . implode(',', $PMIDs) . ') ORDER BY PUBLICATION_DATE DESC';

    $TMP = runQuery($query);
    if ($TMP === false || count($TMP) == 0) return $DATA;
    foreach ($TMP as $line) {
        $DATA[$line['PMID_ENTRY_ID']] = array('ENTRY' => $line);
    }

    $res = runQuery("SELECT * FROM PMID_ABSTRACT WHERE PMID_ENTRY_ID IN (" . implode(',', array_keys($DATA)) . ') ORDER BY PMID_ABSTRACT_ID');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['ENTRY']['ABSTRACT'][$line['ABSTRACT_TYPE']] = $line['ABSTRACT_TEXT'];
    }

    $res = runQuery("SELECT DISTINCT PMID_ENTRY_ID,LAST_NAME,FIRST_NAME, INITIALS, ORCID_ID, INSTIT_NAME,PA.PMID_AUTHOR_ID , PI.PMID_INSTIT_ID,PI.INSTIT_PRIM_ID
	FROM PMID_AUTHOR_MAP PAM, PMID_AUTHOR PA
	LEFT JOIN PMID_INSTIT PI ON pa.pmid_instit_id = pi.pmid_instit_id
    WHERE pam.pmid_author_id=PA.pmid_author_id AND PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ')');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['AUTHORS'][] = $line;
    }

    $res = runQuery("SELECT PMID_ENTRY_ID,RULE_NAME,RULE_GROUP FROM PUBLI_RULE_MAP PRM, PUBLI_RULE PR
	WHERE PR.PUBLI_RULE_ID = PRM.PUBLI_RULE_ID AND PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ')');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['RULE'][] = $line;
    }

    $res = runQuery("SELECT pmid_entry_id , COUNT(*) co FROM (SELECT DISTINCT pmid_entry_id, text_content,disease_entry_id,gn_entry_id
    FROM  pmid_disease_gene pdg, pmid_disease_gene_txt pdgt
    where 
     pdg.pmid_disease_gene_id = pdgt.pmid_Disease_gene_id
     AND pmid_entry_Id IN (" . implode(',', array_keys($DATA)) . '))g GROUP BY  pmid_entry_Id');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['EVIDENCE'] = $line['CO'];
    }

    $res = runQuery("SELECT PMID_ENTRY_ID,COUNT(DISTINCT GN_ENTRY_ID) CO FROM PMID_GENE_MAP PRM
    WHERE  PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ') GROUP BY PMID_ENTRY_ID');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['GENE'] = $line['CO'];
    }
    $res = runQuery("SELECT DISTINCT PMID_ENTRY_ID, COUNT(DISTINCT GE.GO_ENTRY_ID) CO FROM GO_ENTRY GE, GO_PMID_MAP GP
    WHERE GP.GO_ENTRY_ID = GE.GO_ENTRY_ID AND IS_OBSOLETE='F'  AND PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ') GROUP BY PMID_ENTRY_ID');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['GO'] = $line['CO'];
    }
    $res = runQuery("SELECT pmid_entry_id, COUNT(DISTINCT CELL_ENTRY_ID) CO FROM  cell_pmid_map e 
    where  PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ') GROUP BY PMID_ENTRY_ID');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['CELL'] = $line['CO'];
    }
    $res = runQuery("SELECT  pmid_entry_id, COUNT(DISTINCT ASSAY_ENTRY_ID) CO
    FROM assay_pmid a WHERE PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ') GROUP BY PMID_ENTRY_ID');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['ASSAY'] = $line['CO'];
    }
    $res = runQuery("select  pmid_entry_id, COUNT(DISTINCT ANATOMY_ENTRY_ID) CO FROM pmid_anatomy_map p
     where  PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ') GROUP BY PMID_ENTRY_ID');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['TISSUE'] = $line['CO'];
    }

    $res = runQuery("SELECT  PMID_ENTRY_ID, COUNT(DISTINCT DISEASE_ENTRY_ID)  CO
    FROM MV_DISEASE_PUBLI  PD WHERE  PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ') GROUP BY PMID_ENTRY_ID');

    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['DISEASE'] = $line['CO'];
    }

    $res = runQuery("SELECT PMID_ENTRY_ID, COUNT(DISTINCT DRUG_ENTRY_ID) CO 
    FROM PMID_DRUG_MAP P 
    WHERE  PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ') GROUP BY PMID_ENTRY_ID');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['DRUG'] = $line['CO'];
    }
    $res = runQuery("SELECT  PMID_ENTRY_ID,COUNT(DISTINCT PROT_FEAT_ID) CO
	FROM  PROT_FEAT_PMID U WHERE 
   PMID_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ') GROUP BY PMID_ENTRY_ID');
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA[$line['PMID_ENTRY_ID']]['TAGS']['PROT_FEAT'] = $line['CO'];
    }






    return $DATA;
}


///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// DISEASE PORTAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

function disease_portal_disease_tag($INPUT)
{
    global $DB_CONN;
    $VALUE = $DB_CONN->quote('%' . strtolower($INPUT) . '%');

    $tab = explode("-", $INPUT);
    if (count($tab) == 1) $tab = explode("_", $INPUT);
    $query = "SELECT DISEASE_TAG, DISEASE_NAME, STRING_AGG( SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as SYN_V, STRING_AGG( CONCAT(CONCAT(SOURCE_NAME,'-'),DISEASE_EXTDB),'|' ORDER BY SOURCE_NAME ASC) as SOURCES
     FROM (
		SELECT DISTINCT DISEASE_TAG, DISEASE_NAME,SYN_VALUE,DISEASE_EXTDB, SOURCE_NAME
	FROM DISEASE_ENTRY DE
    LEFT JOIN DISEASE_EXTDB DX ON DX.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID
    LEFT JOIN SOURCE S ON S.SOURCE_ID = DX.SOURCE_ID
    LEFT JOIN DISEASE_SYN DS ON DS.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID,DISEASE_HIERARCHY DH,
	(SELECT DISEASE_LEVEL_LEFT, DISEASE_LEVEL_RIGHT
	FROM DISEASE_ENTRY DE, DISEASE_HIERARCHY DH
	WHERE DE.DISEASE_ENTRY_ID = DH.DISEASE_ENTRY_ID
	AND DISEASE_TAG='MONDO_0000001') DROOT
	wHERE (LOWER(DISEASE_TAG) LIKE " . $VALUE . " OR ";


    if (count($tab) == 1)    $query .= "LOWER(DISEASE_EXTDB) ='" . strtolower($tab[0]) . "' ";
    else $query .= "(LOWER(SOURCE_NAME)='" . strtolower($tab[0]) . "' AND LOWER(DISEASE_EXTDB) ='" . strtolower($tab[1]) . "')";
    $query .= ")
	AND DH.DISEASE_ENTRY_ID = dE.DISEASE_ENTRY_ID
	AND DH.DISEASE_LEVEL_LEFT>DROOT.DISEASE_LEVEL_LEFT AND DH.DISEASE_LEVEL_RIGHT < DROOT.DISEASE_LEVEL_RIGHT) Sub
	GROUP BY DISEASE_TAG, DISEASE_NAME";

    $res = runQuery($query);

    return $res;
}

function tissue_portal_id($TISSUE_ID)
{
    global $DB_CONN;
    $VALUE = $DB_CONN->quote('%' . strtolower($TISSUE_ID) . '%');
    $tab = explode("-", $TISSUE_ID);
    if (count($tab) == 1) $tab = explode("_", $TISSUE_ID);
    $query = "SELECT ANATOMY_TAG, ANATOMY_NAME, STRING_AGG( SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as SYN_V, STRING_AGG( CONCAT(CONCAT(SOURCE_NAME,'-'),ANATOMY_EXTDB),'|' ORDER BY SOURCE_NAME ASC) as SOURCES
     FROM (
		SELECT DISTINCT ANATOMY_TAG, ANATOMY_NAME,SYN_VALUE,ANATOMY_EXTDB, SOURCE_NAME
	FROM ANATOMY_ENTRY DE
    LEFT JOIN ANATOMY_EXTDB DX ON DX.ANATOMY_ENTRY_ID = DE.ANATOMY_ENTRY_ID
    LEFT JOIN SOURCE S ON S.SOURCE_ID = DX.SOURCE_ID
    , ANATOMY_SYN DS,ANATOMY_HIERARCHY DH,
	(SELECT ANATOMY_LEVEL_LEFT, ANATOMY_LEVEL_RIGHT
	FROM ANATOMY_ENTRY DE, ANATOMY_HIERARCHY DH
	WHERE DE.ANATOMY_ENTRY_ID = DH.ANATOMY_ENTRY_ID
	AND ANATOMY_NAME='anatomical entity') DROOT
	wHERE DS.ANATOMY_ENTRY_ID = DE.ANATOMY_ENTRY_ID
	AND (LOWER(ANATOMY_TAG) LIKE " . $VALUE . " OR ";
    if (count($tab) == 1)    $query .= "LOWER(ANATOMY_EXTDB) ='" . strtolower($tab[0]) . "' ";
    else $query .= "(LOWER(SOURCE_NAME)='" . strtolower($tab[0]) . "' AND LOWER(ANATOMY_EXTDB) ='" . strtolower($tab[1]) . "')";
    $query .= ")
	AND DH.ANATOMY_ENTRY_ID = dE.ANATOMY_ENTRY_ID
	AND DH.ANATOMY_LEVEL_LEFT>DROOT.ANATOMY_LEVEL_LEFT AND DH.ANATOMY_LEVEL_RIGHT < DROOT.ANATOMY_LEVEL_RIGHT) Sub
	GROUP BY ANATOMY_TAG, ANATOMY_NAME";

    $res = runQuery($query);

    return $res;
}

function tissue_portal_name($TISSUE_NAME)
{
    global $DB_CONN;
    $VALUE = $DB_CONN->quote('%' . strtolower($TISSUE_NAME) . '%');
    $query = "SELECT ANATOMY_TAG, ANATOMY_NAME, STRING_AGG( SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as SYN_V FROM (
		SELECT DISTINCT ANATOMY_TAG, ANATOMY_NAME,SYN_VALUE
	FROM ANATOMY_ENTRY DE, ANATOMY_SYN DS,ANATOMY_HIERARCHY DH,
	(SELECT ANATOMY_LEVEL_LEFT, ANATOMY_LEVEL_RIGHT
	FROM ANATOMY_ENTRY DE, ANATOMY_HIERARCHY DH
	WHERE DE.ANATOMY_ENTRY_ID = DH.ANATOMY_ENTRY_ID
	AND ANATOMY_NAME='anatomical entity') DROOT
	wHERE DS.ANATOMY_ENTRY_ID = DE.ANATOMY_ENTRY_ID
	AND (LOWER(ANATOMY_NAME) LIKE " . $VALUE . " OR LOWER(SYN_VALUE) LIKE " . $VALUE . ")
	AND DH.ANATOMY_ENTRY_ID = dE.ANATOMY_ENTRY_ID
	AND DH.ANATOMY_LEVEL_LEFT>DROOT.ANATOMY_LEVEL_LEFT AND DH.ANATOMY_LEVEL_RIGHT < DROOT.ANATOMY_LEVEL_RIGHT) Sub
	GROUP BY ANATOMY_TAG, ANATOMY_NAME";
    $res = runQuery($query);

    return $res;
}


function disease_portal_disease_name($INPUT)
{
    global $DB_CONN;
    $VALUE = $DB_CONN->quote('%' . strtolower($INPUT) . '%');
    $query = 
    
    "SELECT DISEASE_ENTRY_ID,DISEASE_TAG, DISEASE_NAME, STRING_AGG( SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as SYN_V FROM (
		SELECT DISTINCT DE.DISEASE_ENTRY_ID , DISEASE_TAG, DISEASE_NAME,SYN_VALUE
	FROM DISEASE_ENTRY DE LEFT JOIN DISEASE_SYN DS
	ON DS.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID
	WHERE (LOWER(DISEASE_NAME) LIKE " . $VALUE . " OR LOWER(SYN_VALUE) LIKE " . $VALUE . " OR disease_tag= '" . $INPUT . "')  ) k
	GROUP BY DISEASE_ENTRY_ID, DISEASE_TAG, DISEASE_NAME ORDER BY DISEASE_NAME ASC";
    
$res = runQuery($query);

    
    if (count($res) == 1) return $res[0];
    else return $res;
}

function getDiseaseParentOntology($DISEASE_ENTRY_ID)
{
    $res=runQuery(
        "SELECT DISTINCT EE.DISEASE_TAG, EE.DISEASE_NAME, EE.DISEASE_ENTRY_ID, EF.DISEASE_LEVEL, EF.DISEASE_LEVEL_RIGHT,EF.DISEASE_LEVEL_LEFT
        FROM DISEASE_ENTRY EE, DISEASE_HIERARCHY EF, DISEASE_HIERARCHY EPH
        WHERE  EF.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
        AND EF.DISEASE_LEVEL_LEFT <=EPH.DISEASE_LEVEL_LEFT
        AND EF.DISEASE_LEVEL_RIGHT >= EPH.DISEASE_LEVEL_RIGHT 
        AND EPH.DISEASE_ENTRY_ID = " . $DISEASE_ENTRY_ID . ' ORDER BY EF.DISEASE_LEVEL ASC');
    $DATA=array('ENTRY'=>array(),'ROOT'=>array());
    $RULES=array();
    $NEXT_RULES=array();
    $CURR_LEVEL=0;
    foreach ($res as $line)
    {
        if ($CURR_LEVEL!=$line['DISEASE_LEVEL'])
        {
            
            $CURR_LEVEL=$line['DISEASE_LEVEL'];
            $RULES=$NEXT_RULES;
            $NEXT_RULES=array();
        }
        $DATA['ENTRY'][$line['DISEASE_TAG']]=array('TITLE'=>$line['DISEASE_NAME']);
        if ($line['DISEASE_TAG']=='MONDO_0000001')$DATA['ROOT'][]=$line['DISEASE_TAG'];
        else
        foreach ($RULES as $R)
        {
            if ($line['DISEASE_LEVEL_LEFT']>$R['DISEASE_LEVEL_LEFT'] && $line['DISEASE_LEVEL_RIGHT']<$R['DISEASE_LEVEL_RIGHT'])
            {
                if (!isset($DATA['ENTRY'][$R['DISEASE_TAG']]['CHILD']))$DATA['ENTRY'][$R['DISEASE_TAG']]['CHILD']=array();
                if (!in_array($line['DISEASE_TAG'],$DATA['ENTRY'][$R['DISEASE_TAG']]['CHILD']))
                $DATA['ENTRY'][$R['DISEASE_TAG']]['CHILD'][]=$line['DISEASE_TAG'];
            }
        }
        $NEXT_RULES[$line['DISEASE_TAG']]=$line;

    }    
    return $DATA;
}

function getDiseaseChildOntology($DISEASE_ENTRY_ID)
{
    $res=runQuery(
        "SELECT DISTINCT EE.DISEASE_TAG, EE.DISEASE_NAME, EE.DISEASE_ENTRY_ID, EF.DISEASE_LEVEL, EF.DISEASE_LEVEL_RIGHT,EF.DISEASE_LEVEL_LEFT
        FROM DISEASE_ENTRY EE, DISEASE_HIERARCHY EF, DISEASE_HIERARCHY EPH
        WHERE  EF.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
        AND EF.DISEASE_LEVEL_LEFT >=EPH.DISEASE_LEVEL_LEFT
        AND EF.DISEASE_LEVEL_RIGHT <= EPH.DISEASE_LEVEL_RIGHT 
        AND EPH.DISEASE_ENTRY_ID = " . $DISEASE_ENTRY_ID . '
        AND EF.DISEASE_LEVEL <= EPH.DISEASE_LEVEL+3 
        ORDER BY EF.DISEASE_LEVEL,EE.DISEASE_TAG ASC');// 
    $DATA=array('ENTRY'=>array(),'ROOT'=>array());
    $RULES=array();
    $NEXT_RULES=array();
    $CURR_LEVEL=0;
    foreach ($res as $line)
    {
        if ($CURR_LEVEL!=$line['DISEASE_LEVEL'])
        {
            
            $CURR_LEVEL=$line['DISEASE_LEVEL'];
            $RULES=$NEXT_RULES;
            $NEXT_RULES=array();
        }
        $DATA['ENTRY'][$line['DISEASE_TAG']]=array('TITLE'=>$line['DISEASE_NAME']);
        
        if ($line['DISEASE_ENTRY_ID']==$DISEASE_ENTRY_ID && !in_array($line['DISEASE_TAG'],$DATA['ROOT']))$DATA['ROOT'][]=$line['DISEASE_TAG'];
        
        foreach ($RULES as $R)
        {
            if ($line['DISEASE_LEVEL_LEFT']>$R['DISEASE_LEVEL_LEFT'] && $line['DISEASE_LEVEL_RIGHT']<$R['DISEASE_LEVEL_RIGHT'])
            {
                if (!isset($DATA['ENTRY'][$R['DISEASE_TAG']]['CHILD']))$DATA['ENTRY'][$R['DISEASE_TAG']]['CHILD']=array();
                if (!in_array($line['DISEASE_TAG'],$DATA['ENTRY'][$R['DISEASE_TAG']]['CHILD']))
                $DATA['ENTRY'][$R['DISEASE_TAG']]['CHILD'][]=$line['DISEASE_TAG'];
            }
        }
        $NEXT_RULES[$line['DISEASE_TAG']]=$line;

    } 
    return $DATA;
}

function getDiseaseEntry($NAME, $IS_TAG = false, $WITH_CLIN_TRIALS = false)
{

    $DATA = array();
    if ($IS_TAG) $res = runQuery("SELECT * FROM DISEASE_ENTRY EE WHERE  LOWER(DISEASE_TAG)='" . strtolower($NAME) . "'  ");
    else $res = runQuery("SELECT * FROM DISEASE_ENTRY EE WHERE  LOWER(DISEASE_NAME)=LOWER('" . strtolower($NAME) . "') ");
    if ($res != array()) $DATA = $res[0];
    else return $DATA;

    $DATA['SYN'] = runQuery("SELECT * FROM DISEASE_SYN WHERE DISEASE_ENTRY_ID = " . $DATA['DISEASE_ENTRY_ID']);
    $DATA['EXTDB'] = runQuery("SELECT * FROM DISEASE_EXTDB D, SOURCE S WHERE S.SOURCE_ID=D.SOURCE_ID AND DISEASE_ENTRY_ID = " . $DATA['DISEASE_ENTRY_ID']);

    if (!$WITH_CLIN_TRIALS) return $DATA;

    $query = "SELECT COUNT(*) CO, MAX_DISEASE_PHASE FROM (SELECT  DISTINCT DRUG_DISEASE_ID, MAX_DISEASE_PHASE
    FROM DISEASE_ENTRY EE, DISEASE_HIERARCHY EF, DISEASE_HIERARCHY EPH, DISEASE_ENTRY EP, DRUG_DISEASE DD
    WHERE EP.DISEASE_ENTRY_ID = EPH.DISEASE_ENTRY_ID AND EF.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
    AND EF.DISEASE_LEVEL_LEFT >=EPH.DISEASE_LEVEL_LEFT
    AND EF.DISEASE_LEVEL_RIGHT <= EPH.DISEASE_LEVEL_RIGHT
    AND EF.DISEASE_LEVEL=EPH.DISEASE_LEVEL+1 AND DD.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
    AND EP.DISEASE_ENTRY_ID = " . $DATA['DISEASE_ENTRY_ID'] . ' ) Sub GROUP BY MAX_DISEASE_PHASE';
    $res = runQuery($query);

    foreach ($res as $line) $DATA['TRIALS'][$line['MAX_DISEASE_PHASE']] = $line['CO'];
    return $DATA;
}

function getLipidChildOntology($LIPID_ENTRY_ID, $LIPID_LEVEL = -1)
{
    $DATA = array();
    $query = "SELECT DISTINCT EE.LIPID_TAG, EE.LIPID_NAME, EE.LIPID_SMILES, EF.LIPID_LEVEL, EF.LIPID_LEVEL_RIGHT,EF.LIPID_LEVEL_LEFT
		FROM LIPID_ENTRY EE, LIPID_HIERARCHY EF, LIPID_HIERARCHY EPH, LIPID_ENTRY EP 
	WHERE EP.LIPID_ENTRY_ID = EPH.LIPID_ENTRY_ID AND EF.LIPID_ENTRY_ID = EE.LIPID_ENTRY_ID 
	AND EF.LIPID_LEVEL_LEFT >=EPH.LIPID_LEVEL_LEFT 
	AND EF.LIPID_LEVEL_RIGHT <= EPH.LIPID_LEVEL_RIGHT  AND EF.LIPID_LEVEL=EPH.LIPID_LEVEL+1
	AND EP.LIPID_ENTRY_ID = " . $LIPID_ENTRY_ID;
    if ($LIPID_LEVEL != -1) $query .= ' AND EPH.LIPID_LEVEL=' . $LIPID_LEVEL;
    $query .= ' ORDER BY EE.LIPID_NAME ASC';
    $res = runQuery($query);
    foreach ($res as $line) $DATA[$line['LIPID_TAG']] = $line;

    $query = "SELECT SM_NAME,SMILES FROM SM_MOLECULE SM,SM_ENTRY SE, SM_SOURCE SS, SOURCE S, LIPID_SM_MAP LS
		WHERE SM.SM_MOLECULE_ID = SE.SM_MOLECULE_ID 
		AND SE.SM_ENTRY_ID = LS.SM_ENTRY_ID 
		AND SE.SM_ENTRY_ID =SS.SM_ENTRY_ID AND ss.source_id= S.SOURCE_ID 
		AND SM_NAME LIKE 'SLM:%' AND SOURCE_NAME='SwissLipids' AND LS.LIPID_ENTRY_ID=" . $LIPID_ENTRY_ID;
    $res = runQuery($query);
    foreach ($res as $line) {
        $line['SM'] = true;
        $DATA[$line['SM_NAME']] = $line;
    }

    return $DATA;
}

function getPDBInfo($PDB_ID, $W_LIG = true, $W_UNSEQ = true, $W_PPI = true, $FILTERS = array())
{
    $CHAIN_FILTERS = array();
    $HAS_FILTERS = false;

    if (isset($FILTERS['CHAIN'])) {
        $HAS_FILTERS = true;
        foreach ($FILTERS['CHAIN'] as $T) $CHAIN_FILTERS["'" . $T . "'"] = true;
    }

    $query = "SELECT PROT_IDENTIFIER, XE.XR_ENTRY_ID, FULL_COMMON_NAME,EXPR_TYPE,RESOLUTION,
	        TO_CHAR(DEPOSITION_DATE, 'YYYY-MM-DD' ) as DEPOSITION_DATE, TITLE,XC.XR_CHAIN_ID, CHAIN_NAME,PERC_SIM,PERC_IDENTITY,PERC_SIM_COM,
			PERC_IDENTITY_COM, N_MUTANT,ISO_ID, US.IS_PRIMARY, US.PROT_SEQ_ID
			FROM XR_ENTRY XE
			LEFT JOIN XR_CHAIN XC ON XE.XR_ENTRY_ID = XC.XR_ENTRY_ID
			LEFT JOIN XR_CH_PROT_MAP XHM ON XC.XR_CHAIN_ID = XHM.XR_CHAIN_ID
			LEFT JOIN PROT_SEQ US ON US.PROT_SEQ_ID = XHM.PROT_SEQ_ID
			LEFT JOIN PROT_ENTRY UE ON UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID WHERE
			FULL_COMMON_NAME='" . $PDB_ID . "'";
    if ($CHAIN_FILTERS != array()) $query .= ' AND CHAIN_NAME IN (' . implode(',', array_keys($CHAIN_FILTERS)) . ')';

    $res = runQuery($query);
    $MAP = array();
    $ENTRY = array('ENTRY' => array(), 'PROT_SEQ' => array());
    foreach ($res as $line) {
        $ENTRY['ENTRY'] = array(
            'PDB_ID' => $line['FULL_COMMON_NAME'],
            'EXPR_TYPE' => $line['EXPR_TYPE'],
            'RESOLUTION' => $line['RESOLUTION'],
            'DEPOSITION_DATE' => $line['DEPOSITION_DATE'],
            'TITLE' => $line['TITLE']
        );
        unset($line['FULL_COMMON_NAME'], $line['EXPR_TYPE'], $line['RESOLUTION'], $line['DEPOSITION_DATE'], $line['TITLE']);
        if ($line['XR_CHAIN_ID'] == '') continue;
        $ENTRY['CHAIN'][$line['XR_CHAIN_ID']]['INFO'][$line['ISO_ID']] = $line;
        $ENTRY['CHAIN'][$line['XR_CHAIN_ID']]['CHAIN_NAME'] = $line['CHAIN_NAME'];
        if ($line['PROT_SEQ_ID'] == '') continue;
        $MAP[$line['PROT_SEQ_ID']] = $line['ISO_ID'];
        $ENTRY['PROT_SEQ'][$line['PROT_SEQ_ID']]['CHAIN'][] = $line['XR_CHAIN_ID'];
    }

    if ($W_LIG) {
        $query = "SELECT XC.CHAIN_NAME, XT.NAME,XT.CLASS,XT.SMILES,XR.POSITION
			 FROM XR_CHAIN XC,XR_RES XR,XR_TPL_RES XT
			 WHERE XT.XR_TPL_RES_ID = XR.XR_TPL_RES_ID AND XR.XR_CHAIN_ID = XC.XR_CHAIN_ID
			 AND CLASS NOT IN ('AA','MOD_AA','WATER')
			 AND XC.XR_CHAIN_ID IN (" . implode(',', array_keys($ENTRY['CHAIN'])) . ')';
        $ENTRY['LIGS'] = runQuery($query);
    }
    if ($W_PPI) {
        $query = 'SELECT XR_CHAIN_R_ID, XR_CHAIN_C_ID FROM XR_PPI WHERE XR_CHAIN_R_ID IN (' . implode(',', array_keys($ENTRY['CHAIN'])) . ')';
        $res = runQuery($query);
        foreach ($res as $line) {
            if ($line['XR_CHAIN_R_ID'] < $line['XR_CHAIN_C_ID'])
                $ENTRY['PPI'][] = array($ENTRY['CHAIN'][$line['XR_CHAIN_R_ID']], $ENTRY['CHAIN'][$line['XR_CHAIN_C_ID']]);
        }
    }

    if (!$W_UNSEQ || count($ENTRY['PROT_SEQ']) == 0) return $ENTRY;

    $query = 'SELECT DISTINCT PROT_SEQ_ID,SYMBOL, GENE_ID, FULL_NAME, SCIENTIFIC_NAME,PROT_IDENTIFIER,UE.PROT_ENTRY_ID, UE.STATUS, US.ISO_ID,US.ISO_NAME,US.IS_PRIMARY, US.DESCRIPTION,US.NOTE
	FROM PROT_SEQ US, PROT_ENTRY UE
    LEFT JOIN GN_PROT_MAP GUM ON  GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
    LEFT JOIN MV_GENE M ON  M.GN_ENTRY_ID = GUM.GN_ENTRY_ID
	WHERE US.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
	AND  PROT_SEQ_ID IN (' . implode(',', array_keys($ENTRY['PROT_SEQ'])) . ') ORDER BY STATUS DESC,PROT_IDENTIFIER ASC';

    $res = runQuery($query);

    $PROT_ENTRY = array();
    foreach ($res as $line) {
        if (!isset($line['PROT_SEQ_ID'])) print_r($line);
        $ENTRY['PROT_SEQ'][$line['PROT_SEQ_ID']]['INFO'] = $line;
        $PROT_ENTRY[$line['PROT_IDENTIFIER']][] = $line['PROT_SEQ_ID'];
    }
    foreach ($ENTRY['PROT_SEQ'] as $PROT_SEQ_ID => &$INFO) {

        $T = getProteinSequence($INFO['INFO']['ISO_ID'], false, true, true, false);
        $INFO['FT'] = $T['FT'];
        $INFO['EXTDB'] = $T['EXTDB'];
    }


    $res = getDomainInfo(array_keys($PROT_ENTRY));

    foreach ($res as $line) {

        foreach ($PROT_ENTRY[$line['PROT_IDENTIFIER']] as $US) $ENTRY['PROT_SEQ'][$US]['DOM'][] = $line;
    }

    $query = 'SELECT PROT_SEQ_POS_ID,PROT_SEQ_ID, POSITION,LETTER FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN  (' . implode(',', array_keys($ENTRY['PROT_SEQ'])) . ') ORDER BY PROT_SEQ_ID,POSITION ASC';
    $res = runQuery($query);
    foreach ($res as $line) {
        $ENTRY['PROT_SEQ'][$line['PROT_SEQ_ID']]['SEQ'][$line['POSITION']] = array($line['PROT_SEQ_POS_ID'], $line['LETTER']);
    }


    $query = 'SELECT XR_CHAIN_ID, XR.XR_RES_ID, NAME, XR.POSITION as XR_POS,US.POSITION AS US_POS,US.PROT_SEQ_POS_ID,LETTER ,XR_PROT_MAP_TYPE,PROT_SEQ_ID FROM XR_RES XR
	LEFT JOIN XR_CH_PROT_POS XCHUM ON XR.XR_RES_ID = XCHUM.XR_RES_ID
	LEFT JOIN PROT_SEQ_POS US ON US.PROT_SEQ_POS_ID = XCHUM.PROT_SEQ_POS_ID, XR_TPL_RES XT
	WHERE XR.XR_TPL_RES_ID= XT.XR_TPL_RES_ID AND XR_CHAIN_ID IN (' . implode(',', array_keys($ENTRY['CHAIN'])) . ')
	ORDER BY XR_CHAIN_ID ASC,XR.XR_RES_ID ASC';
    $res = runQuery($query);

    foreach ($res as $line) {
        $ENTRY['CHAIN'][$line['XR_CHAIN_ID']]['RES'][$line['XR_POS']] = array($line['NAME'], $line['US_POS'], $line['LETTER'], $line['XR_PROT_MAP_TYPE'], ($line['PROT_SEQ_ID'] != '' ? $MAP[$line['PROT_SEQ_ID']] : ''));
    }
    $res = runQuery("SELECT USP.PROT_SEQ_ID,USP.POSITION,INTERACTION_NAME,CLASS,COUNT_INT
	FROM PROT_SEQ_POS USP, XR_PROT_INT_STAT XS, XR_INTER_TYPE XI
	WHERE XI.XR_INTER_TYPE_ID = XS.XR_INTER_TYPE_ID
	AND USP.PROT_SEQ_POS_ID = XS.PROT_SEQ_POS_ID
	AND PROT_SEQ_ID IN  (" . implode(',', array_keys($ENTRY['PROT_SEQ'])) . ')');
    foreach ($res as $line) {
        $ENTRY['PROT_SEQ'][$line['PROT_SEQ_ID']]['SEQ'][$line['POSITION']]['INTER'][$line['INTERACTION_NAME']][] = array('CLASS' => $line['CLASS'], 'COUNT' => $line['COUNT_INT']);
    }

    return $ENTRY;
}

function getDomainInfo($LIST_UNIDENTIFIER)
{
    $query = "SELECT PROT_DOM_ID,DOMAIN_NAME,DOMAIN_TYPE,POS_START,POS_END,PROT_IDENTIFIER
	FROM PROT_ENTRY UE, PROT_DOM UD
	WHERE UD.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND PROT_IDENTIFIER IN (";
    foreach ($LIST_UNIDENTIFIER as $UNI) $query .= "'" . $UNI . "',";
    $query = substr($query, 0, -1) . ')';
    $res = runQuery($query);
    $LIST_UN = array();
    foreach ($res as $line) $LIST_UN[] = $line['PROT_DOM_ID'];
    if (count($LIST_UN) == 0) return $res;
    $query = 'SELECT COUNT(*) as len, PROT_DOM_ID FROM PROT_DOM_SEQ WHERE PROT_DOM_ID IN (' . implode(',', $LIST_UN) . ') GROUP BY PROT_DOM_ID';
    $res2 = runQuery($query);
    foreach ($res2 as $l) $MAP[$l['PROT_DOM_ID']] = $l['LEN'];
    foreach ($res as &$l) $l['LEN'] = $MAP[$l['PROT_DOM_ID']];

    return $res;
}

function getProteinSequence($SEQ_NAME, $W_MUTANT = true, $W_FEAT = true, $W_EXTDB = true, $W_TRANSCRIPT = true, $W_SIM = true, $W_PTM = true)
{
    $DATA = array();
    $time = microtime_float();
    $DATA['INFO'] = runQuery("   select UE.PROT_ENTRY_ID,GENE_ID,SYMBOL,FULL_NAME,PROT_SEQ_ID,UE.STATUS,UE.PROT_IDENTIFIER,CONFIDENCE,NOTE, ISO_ID, ISO_NAME,IS_PRIMARY,DESCRIPTION
    FROM   PROT_SEQ US,PROT_ENTRY UE
    LEFT JOIN GN_PROT_MAP GUM ON GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
    LEFT JOIN GN_ENTRY GE ON GUM.GN_ENTRY_ID = GE.GN_ENTRY_ID
    WHERE US.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
    AND ISO_ID='" . $SEQ_NAME . "' ORDER BY STATUS DESC,IS_PRIMARY DESC");
    $DATA['TIME']['INFO'] = round(microtime_float() - $time, 3);
    $time = microtime_float();
    if (count($DATA['INFO']) == 0) throw new Exception("No protein sequence with name " . $SEQ_NAME . " found", ERR_TGT_USR);
    $query = "select POSITION as POS,LETTER, PROT_SEQ_POS_ID FROM PROT_SEQ_POS USP WHERE PROT_SEQ_ID= " . $DATA['INFO'][0]['PROT_SEQ_ID'] . ' ORDER BY POSITION ASC';
    $res = runQuery($query);
    foreach ($res as $l) {
        $DATA['SEQ'][$l['PROT_SEQ_POS_ID']] = array('P' => $l['POS'], 'AA' => $l['LETTER']);
    }

    $DATA['TIME']['SEQ'] = round(microtime_float() - $time, 3);
    $time = microtime_float();

    if ($W_PTM) {
        $TMP = getPTMSitesFromProtein($SEQ_NAME);
        foreach ($TMP[$SEQ_NAME]['LIST_PTM'] as $PSP_ID => $LIST_PTM) {
            $DATA['SEQ'][$PSP_ID]['PTM'] = $LIST_PTM['PTM'];
        }
        $TMP = array();
        $DATA['TIME']['PTM'] = round(microtime_float() - $time, 3);
        $time = microtime_float();
    }

    if ($W_MUTANT) {
        $res = runQuery("select VE.RSID,VP.REF_ALL,CHR_POS AS POSITION,VC.ALT_ALL,TRIPLET_POS,TR_REF_ALL,
        TR_ALT_ALL,TUP.PROT_SEQ_POS_ID
        FROM CHR_SEQ_POS C, VARIANT_ENTRY VE, VARIANT_POSITION VP, VARIANT_CHANGE VC, VARIANT_TRANSCRIPT_MAP VTM,  tr_protseq_pos_al TUP, TR_PROTSEQ_AL TA
        WHERE TA.PROT_SEQ_ID=" . $DATA['INFO'][0]['PROT_SEQ_ID'] . "
        AND VE.VARIANT_ENTRY_ID = VP.VARIANT_ENTRY_ID
        AND ta.TR_PROTSEQ_AL_ID=TUP.TR_PROTSEQ_AL_ID
        AND C.CHR_SEQ_POS_ID = VP.CHR_SEQ_POS_ID
        AND tup.transcript_pos_id=VTM.TRANSCRIPT_POS_ID
        AND VTM.VARIANT_CHANGE_ID = VC.VARIANT_CHANGE_ID
        AND VC.VARIANT_POSITION_ID = VP.VARIANT_POSITION_ID");
        foreach ($res as $line) {
            $DATA['SEQ'][$line['PROT_SEQ_POS_ID']]['MUT'][$line['RSID']] = array(
                $line['POSITION'] . ':' . $line['REF_ALL'] . '>' . $line['ALT_ALL'],
                $line['TR_REF_ALL'] . '>' . $line['TR_ALT_ALL'], $line['TRIPLET_POS']
            );
        }
        $DATA['TIME']['MUTANT'] = round(microtime_float() - $time, 3);
        $time = microtime_float();
    }
    if ($W_FEAT) {
        $query = 'select UF.PROT_FEAT_ID, FEAT_VALUE,START_POS,END_POS,PMID,ECO_ID,ECO_NAME,FEAT_NAME,DESCRIPTION ,EE.ECO_ENTRY_ID,UF.PROT_FEAT_TYPE_ID
        FROM PROT_FEAT_TYPE UFT,  PROT_FEAT UF
        LEFT JOIN PROT_FEAT_PMID UFM ON UF.PROT_FEAT_ID = UFM.PROT_FEAT_ID
        LEFT JOIN PMID_ENTRY PE ON PE.PMID_ENTRY_ID = UFM.PMID_ENTRY_ID
        LEFT JOIN ECO_ENTRY EE ON EE.ECO_ENTRY_ID = UFM.ECO_ENTRY_ID
        WHERE UFT.PROT_FEAT_TYPE_ID=UF.PROT_FEAT_TYPE_ID AND PROT_SEQ_ID =' . $DATA['INFO'][0]['PROT_SEQ_ID'];
        $res = runQuery($query);
        $FT = array('ECO' => array(), 'FEAT_TYPE' => array(), 'FEATS' => array());
        foreach ($res as $line) {
            if ($line['ECO_ENTRY_ID'] != '') $FT['ECO'][$line['ECO_ENTRY_ID']] = array('ECO_ID' => $line['ECO_ID'], 'NAME' => $line['ECO_NAME']);
            $FT['FEAT_TYPE'][$line['PROT_FEAT_TYPE_ID']] = array('NAME' => $line['FEAT_NAME'], 'DESC' => $line['DESCRIPTION']);
            $FT['FEATS'][$line['PROT_FEAT_ID']] = array('START' => $line['START_POS'], 'END' => $line['END_POS'], 'VALUE' => $line['FEAT_VALUE'], 'TYPE' => $line['PROT_FEAT_TYPE_ID']);
            if ($line['PMID'] != '') $FT['FEATS'][$line['PROT_FEAT_ID']]['PMID'][$line['PMID']] = $line['ECO_ENTRY_ID'];
        }
        $DATA['FT'] = $FT;
        $DATA['TIME']['FT'] = round(microtime_float() - $time, 3);
        $time = microtime_float();

        if ($DATA['INFO'][0]['IS_PRIMARY'] == 'T') {
            $query = 'SELECT pos_start,pos_end, domain_name,domain_type FROM prot_dom where prot_entry_Id = ' . $DATA['INFO'][0]['PROT_ENTRY_ID'];
            $res = runQuery($query);
            foreach ($res as $line) {
                $DATA['FT']['FEAT_TYPE'][$line['DOMAIN_TYPE']] = array('NAME' => $line['DOMAIN_TYPE'], 'DESC' => '');
                $DATA['FT']['FEATS'][] = array('START' => $line['POS_START'], 'END' => $line['POS_END'], 'VALUE' => $line['DOMAIN_NAME'], 'TYPE' => $line['DOMAIN_TYPE']);
            }
        }
    }

    if ($W_EXTDB) {
        $query = 'select PROT_EXTDB_VALUE,PROT_EXTDBABBR, PROT_EXTDBURL,Category
        FROM PROT_EXTDB_MAP UEM, PROT_EXTDB UE WHERE UE.PROT_EXTDBID = UEM.PROT_EXTDB_ID AND PROT_SEQ_ID=' . $DATA['INFO'][0]['PROT_SEQ_ID'];
        $res = runQuery($query);
        foreach ($res as $line) {
            $t = $line;
            unset($t['CATEGOTY']);
            $DATA['EXTDB'][$line['CATEGORY']][] = $t;
        }
        $DATA['TIME']['EXTDB'] = round(microtime_float() - $time, 3);
        $time = microtime_float();
    }

    if ($W_TRANSCRIPT) {
        $query = 'select * FROM TR_PROTSEQ_AL U,TRANSCRIPT T WHERE U.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND PROT_SEQ_ID=' . $DATA['INFO'][0]['PROT_SEQ_ID'];
        $res = runQuery($query);
        foreach ($res as $line) {
            $DATA['TRANSCRIPT'][] = $line;
        }
        $DATA['TIME']['TRANSCRIPT'] = round(microtime_float() - $time, 3);
        $time = microtime_float();
    }

    if ($W_SIM) {
        $query = 'select COUNT(*) CO
            FROM PROT_SEQ_AL UDA, TAXON T, PROT_SEQ US,PROT_ENTRY UE
            LEFT JOIN GN_PROT_MAP GUM ON GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
            LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID=GUM.GN_ENTRY_ID
            WHERE uda.PROT_SEQ_comp_id=US.PROT_SEQ_ID
            AND US.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
            AND T.TAXON_ID = UE.TAXON_ID
            AND UDA.PROT_SEQ_REF_ID=' . $DATA['INFO'][0]['PROT_SEQ_ID'];
        $res = runQuery($query);
        $DATA['SEQ_SIM'] = $res[0]['CO'];
        $DATA['TIME']['SEQ_SIM'] = round(microtime_float() - $time, 3);
        $time = microtime_float();
    }
    return $DATA;
}


function searchXrayFromUniprot($LIST_UNIP, $OPTIONS)
{
    $STR = '';
    if (is_array($LIST_UNIP)) {
        foreach ($LIST_UNIP as $UP) $STR .= "'" . $UP . "',";
        $STR = substr($STR, 0, -1);
    } else $STR = "'" . $LIST_UNIP . "'";
    if ($OPTIONS['TYPE'] == 'BY_SIMDOM') {

        $query = "SELECT * FROM
	(SELECT ROW_NUMBER() OVER(ORDER BY FULL_COMMON_NAME,CHAIN_NAME ASC) R, P.*
	FROM (select GENE_ID, SYMBOL, SCIENTIFIC_NAME, PROT_IDENTIFIER, XE.XR_ENTRY_ID, FULL_COMMON_NAME,EXPR_TYPE,RESOLUTION,
	TO_CHAR(DEPOSITION_DATE, 'YYYY-MM-DD' ) as DEPOSITION_DATE, TITLE,XC.XR_CHAIN_ID, CHAIN_NAME,PERC_SIM,PERC_IDENTITY,PERC_SIM_COM, 
		PERC_IDENTITY_COM, N_MUTANT,ISO_ID, US.IS_PRIMARY, US.PROT_SEQ_ID 
	FROM 	XR_ENTRY XE,XR_CHAIN XC, XR_CH_PROT_MAP XHM, PROT_SEQ US, PROT_ENTRY UE
	LEFT JOIN GN_PROT_MAP GUM ON GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
	LEFT JOIN GN_ENTRY GE ON GUM.GN_ENTRY_ID = GE.GN_ENTRY_ID
	LEFT JOIN TAXON TT ON TT.TAXON_ID = UE.TAXON_ID
	
	
	WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = XHM.PROT_SEQ_ID 
	AND XE.XR_ENTRY_ID = XC.XR_ENTRY_ID AND XC.XR_CHAIN_ID = XHM.XR_CHAIN_ID 
	AND PROT_IDENTIFIER NOT IN (" . $STR . ") AND  PROT_IDENTIFIER  IN (select DISTINCT UE2.PROT_IDENTIFIER 
		 FROM PROT_ENTRY UE, PROT_DOM US, PROT_DOM_AL USA, PROT_DOM US2, PROT_ENTRY UE2
		  WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_DOM_ID = USA.PROT_DOM_REF_ID 
		  AND USA.PROT_DOM_COMP_ID = US2.PROT_DOM_ID AND US2.PROT_ENTRY_ID = UE2.PROT_ENTRY_ID 
		  AND UE.PROT_IDENTIFIER IN (" . $STR . "))) P)  WHERE R<" . $OPTIONS['MAX'] . ' AND R>=' . $OPTIONS['MIN'];
    } else if ($OPTIONS['TYPE'] == 'BY_SIMSEQ') {

        $query = "SELECT * FROM
	(SELECT ROW_NUMBER() OVER(ORDER BY FULL_COMMON_NAME,CHAIN_NAME ASC) R, P.*
	FROM (select GENE_ID, SYMBOL, SCIENTIFIC_NAME,PROT_IDENTIFIER, XE.XR_ENTRY_ID, FULL_COMMON_NAME,EXPR_TYPE,RESOLUTION,
	TO_CHAR(DEPOSITION_DATE, 'YYYY-MM-DD' ) as DEPOSITION_DATE, TITLE,XC.XR_CHAIN_ID, CHAIN_NAME,PERC_SIM,PERC_IDENTITY,PERC_SIM_COM, 
		PERC_IDENTITY_COM, N_MUTANT,ISO_ID, US.IS_PRIMARY, US.PROT_SEQ_ID 
	FROM 	XR_ENTRY XE,XR_CHAIN XC, XR_CH_PROT_MAP XHM, PROT_SEQ US, PROT_ENTRY UE
	LEFT JOIN GN_PROT_MAP GUM ON GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
	LEFT JOIN GN_ENTRY GE ON GUM.GN_ENTRY_ID = GE.GN_ENTRY_ID
	LEFT JOIN TAXONOMY_TREE TT ON TT.TAXONOMY_TREE_ID = UE.TAXONOMY_TREE_ID
	WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = XHM.PROT_SEQ_ID 
	AND XE.XR_ENTRY_ID = XC.XR_ENTRY_ID AND XC.XR_CHAIN_ID = XHM.XR_CHAIN_ID 
	AND PROT_IDENTIFIER NOT IN (" . $STR . ") AND  PROT_IDENTIFIER  IN (select DISTINCT UE2.PROT_IDENTIFIER FROM PROT_ENTRY UE, PROT_SEQ US, PROT_SEQ_AL USA, PROT_SEQ US2, PROT_ENTRY UE2
WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = USA.PROT_SEQ_REF_ID
AND USA.PROT_SEQ_COMP_ID = US2.PROT_SEQ_ID AND US2.PROT_ENTRY_ID = UE2.PROT_ENTRY_ID
AND UE.PROT_IDENTIFIER IN (" . $STR . "))) P)  WHERE R<" . $OPTIONS['MAX'] . ' AND R>=' . $OPTIONS['MIN'];
    } else



        $query = "SELECT * FROM
	(SELECT ROW_NUMBER() OVER(ORDER BY FULL_COMMON_NAME,CHAIN_NAME ASC) R, P.*
	FROM (select PROT_IDENTIFIER, XE.XR_ENTRY_ID, FULL_COMMON_NAME,EXPR_TYPE,RESOLUTION,
	TO_CHAR(DEPOSITION_DATE, 'YYYY-MM-DD' ) as DEPOSITION_DATE, TITLE,XC.XR_CHAIN_ID, CHAIN_NAME,PERC_SIM,PERC_IDENTITY,PERC_SIM_COM, 
		PERC_IDENTITY_COM, N_MUTANT,ISO_ID, US.IS_PRIMARY, US.PROT_SEQ_ID 
	FROM 	XR_ENTRY XE,XR_CHAIN XC, XR_CH_PROT_MAP XHM, PROT_ENTRY UE,PROT_SEQ US 
	WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = XHM.PROT_SEQ_ID 
	AND XE.XR_ENTRY_ID = XC.XR_ENTRY_ID AND XC.XR_CHAIN_ID = XHM.XR_CHAIN_ID 
	AND PROT_IDENTIFIER  IN (" . $STR . ")) P) PT WHERE R<" . $OPTIONS['MAX'] . ' AND R>=' . $OPTIONS['MIN'];
    //echo $query;exit;
    $res = runQuery($query);
    return $res;
}


function getXrayCountFromUniProt($LIST_UNIP)
{
    $STR = '';
    if (is_array($LIST_UNIP)) {
        foreach ($LIST_UNIP as $UP) $STR .= "'" . $UP . "',";
        $STR = substr($STR, 0, -1);
    } else $STR = "'" . $LIST_UNIP . "'";

    $query = 'SELECT COUNT(XR_CHAIN_ID)  CO
    FROM XR_CH_PROT_MAP XHM, PROT_ENTRY UE,PROT_SEQ US
    WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = XHM.PROT_SEQ_ID
    AND PROT_IDENTIFIER  IN (' . $STR . ')';
    $res = runQuery($query);
    $INFO = array();
    $INFO['COUNT'] = $res[0]['CO'];

    $query = 'SELECT COUNT(DISTINCT XC.XR_CHAIN_ID) CO
    FROM XR_ENTRY XE,XR_CHAIN XC, XR_CH_PROT_MAP XHM, PROT_ENTRY UE,PROT_SEQ US
    WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = XHM.PROT_SEQ_ID
    AND XE.XR_ENTRY_ID = XC.XR_ENTRY_ID AND XC.XR_CHAIN_ID = XHM.XR_CHAIN_ID
    AND PROT_IDENTIFIER NOT IN (' . $STR . ') AND
    PROT_IDENTIFIER IN (SELECT DISTINCT UE2.PROT_IDENTIFIER
    FROM PROT_ENTRY UE, PROT_SEQ US, PROT_SEQ_AL USA, PROT_SEQ US2, PROT_ENTRY UE2
    WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = USA.PROT_SEQ_REF_ID
    AND USA.PROT_SEQ_COMP_ID = US2.PROT_SEQ_ID AND US2.PROT_ENTRY_ID = UE2.PROT_ENTRY_ID
    AND UE.PROT_IDENTIFIER IN (' . $STR . '))';

    $res = runQuery($query);
    $INFO['COUNT_SIM_SEQ'] = $res[0]['CO'];

    $query = 'SELECT COUNT(DISTINCT XC.XR_CHAIN_ID) CO
	FROM XR_ENTRY XE,XR_CHAIN XC, XR_CH_PROT_MAP XHM, PROT_ENTRY UE,PROT_SEQ US
	WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = XHM.PROT_SEQ_ID
	AND XE.XR_ENTRY_ID = XC.XR_ENTRY_ID AND XC.XR_CHAIN_ID = XHM.XR_CHAIN_ID
	AND PROT_IDENTIFIER NOT IN (' . $STR . ') AND
    PROT_IDENTIFIER IN (SELECT DISTINCT UE2.PROT_IDENTIFIER
    FROM PROT_ENTRY UE, PROT_DOM US, PROT_DOM_AL USA, PROT_DOM US2, PROT_ENTRY UE2
    WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_DOM_ID = USA.PROT_DOM_REF_ID
    AND USA.PROT_DOM_COMP_ID = US2.PROT_DOM_ID AND US2.PROT_ENTRY_ID = UE2.PROT_ENTRY_ID
    AND UE.PROT_IDENTIFIER IN (' . $STR . '))';
    $res = runQuery($query);
    $INFO['COUNT_SIM_DOM'] = $res[0]['CO'];

    return $INFO;
}

function getPDBRes($PDB_ID, $CHAIN, $POSITION)
{
    $query = "SELECT XR_CHAIN_ID, CHAIN_NAME FROM XR_CHAIN XC,XR_ENTRY XR
	WHERE XR.XR_ENTRY_ID = XC.XR_ENTRY_ID AND FULL_COMMON_NAME='" . $PDB_ID . "'
	AND CHAIN_NAME ='" . $CHAIN . "'";
    $res = runQuery($query);
    $ENTRY = array();
    $MAP_CHAIN = array();
    foreach ($res as $line) {
        $MAP_CHAIN[$line['XR_CHAIN_ID']] = $line['CHAIN_NAME'];
        $ENTRY['CHAIN'][$line['CHAIN_NAME']] = array();
    }

    $query = "SELECT XR_CHAIN_ID, XR_RES_ID, NAME, POSITION,CLASS
	FROM XR_RES XR, XR_TPL_RES XT
	WHERE XR.XR_TPL_RES_ID= XT.XR_TPL_RES_ID
	AND XR_CHAIN_ID IN (" . implode(',', array_keys($MAP_CHAIN)) . ") AND POSITION ";
    if (is_array($POSITION)) $query .= '(' . implode(',', $POSITION) . ')';
    else $query .= '=' . $POSITION;


    $query .= " ORDER BY XR_CHAIN_ID ASC,POSITION ASC";
    $res = runQuery($query);

    $ENTRY['STAT']['RES'] = count($res);
    $MAP_RES = array();

    foreach ($res as $line) {
        $CH = $MAP_CHAIN[$line['XR_CHAIN_ID']];

        $ENTRY['CHAIN'][$CH][$line['POSITION']] = array('NAME' => $line['NAME'], 'ATOM' => array());
        $MAP_RES[$line['XR_RES_ID']] = &$ENTRY['CHAIN'][$CH][$line['POSITION']];

        if ($line['CLASS'] != 'AA' || $line['CLASS'] != 'MOD_AA' || $line['CLASS'] != 'WATER' || $line['CLASS'] != 'NUCLEIC') {

            $line['CHAIN'] = $CH;
            unset($line['XR_CHAIN_ID']);
            $ENTRY['LIGS'][] = $line;
        }
    }


    $res = runQuery("SELECT XR_ATOM_ID, XT.NAME,XR.XR_RES_ID, XA.CHARGE,XA.MOL2TYPE,B_FACTOR,X,Y,Z ,SYMBOL
	FROM   XR_RES XR , XR_ATOM XA
	LEFT JOIN XR_TPL_ATOM XT ON XA.XR_TPL_ATOM_ID= XT.XR_TPL_ATOM_ID
	LEFT JOIN XR_ELEMENT XE ON XE.XR_ELEMENT_ID = XT.XR_ELEMENT_ID
	WHERE XR.XR_RES_ID = XA.XR_RES_ID
	AND XR.XR_CHAIN_ID IN (" . implode(',', array_keys($MAP_CHAIN)) . ") AND XR.XR_RES_ID IN (" . implode(",", array_keys($MAP_RES)) . ") ORDER BY XR_CHAIN_ID ASC, XR_RES_ID ASC");

    $MAP_ATOM = array();
    $NATOM = 0;
    $ENTRY['STAT']['ATOM'] = count($res);
    foreach ($res as $line) {

        $RES = &$MAP_RES[$line['XR_RES_ID']];
        ++$NATOM;
        if ($line['MOL2TYPE'] == 'H') {
            $line['NAME'] = 'H';
            $line['SYMBOL'] = 'H';
        }
        unset($line['XR_RES_ID']);
        $XID = $line['XR_ATOM_ID'];
        $line['ATOM_NUM'] = $NATOM;
        $RES['ATOM'][$XID] = $line;
        $MAP_ATOM[$XID] = $NATOM;
    }

    $res = runQuery("SELECT BOND_TYPE,XR_ATOM_ID_1,XR_ATOM_ID_2
	FROM   XR_BOND
	WHERE XR_CHAIN_ID IN (" . implode(',', array_keys($MAP_CHAIN)) . ") AND XR_ATOM_ID_1 IN (" . implode(',', array_keys($MAP_ATOM)) . ')');
    $ENTRY['STAT']['BOND'] = count($res);
    foreach ($res as $line) {
        $ENTRY['BOND'][] = array($MAP_ATOM[$line['XR_ATOM_ID_1']], $MAP_ATOM[$line['XR_ATOM_ID_2']], $line['BOND_TYPE']);
    }
    return $ENTRY;
}

function getPDBResInfo($PDB_ID, $CHAIN, $RES_ID, $RES_NAME)
{
    $query = "SELECT CHAIN_NAME, XR.XR_CHAIN_ID, XR.XR_RES_ID, NAME, XR.POSITION as XR_POS,SMILES,CLASS
	FROM XR_ENTRY XE, XR_CHAIN XC, XR_RES XR,XR_TPL_RES XT
	WHERE XR.XR_TPL_RES_ID= XT.XR_TPL_RES_ID AND XE.XR_ENTRY_ID = XC.XR_ENTRY_ID AND XC.XR_CHAIN_ID = XR.XR_CHAIN_ID AND
	CHAIN_NAME='" . $CHAIN . "' AND FULL_COMMON_NAME='" . $PDB_ID . "' AND XR.POSITION=" . $RES_ID;

    $DATA['RES_INFO'] = runQuery($query)[0];
    if ($DATA['RES_INFO'] == array()) return;
    $DATA['RES_INFO']['FULL_COMMON_NAME'] = $PDB_ID;
    $tmp_data = runQuery('SELECT XR_INTER_TYPE_ID, INTERACTION_NAME FROM XR_INTER_TYPE');
    $INTER_TYPE = array();
    foreach ($tmp_data as $T) $INTER_TYPE[$T['XR_INTER_TYPE_ID']] = $T['INTERACTION_NAME'];
    $query = 'SELECT DISTINCT XR_INTER_TYPE_ID,ATOM_LIST_1,DISTANCE,ANGLE, ATOM_LIST_2, NAME, POSITION,CHAIN_NAME,XIR.XR_RES_ID_2
	FROM  XR_INTER_RES XIR,XR_TPL_RES XT,XR_CHAIN XC, XR_RES XR WHERE XR_RES_ID_1=' . $DATA['RES_INFO']['XR_RES_ID'] . '
	AND XC.XR_CHAIN_ID = XR.XR_CHAIN_ID
	AND XT.XR_TPL_RES_ID = XR.XR_TPL_RES_ID
	AND  XR.XR_RES_ID = XIR.XR_RES_ID_2';

    $res = runQuery($query);
    $chains = array();
    $MAP_INTER = array();
    foreach ($res as $K => $T) {
        $chains[$T['CHAIN_NAME']] = true;
        $T['INTER_NAME'] = $INTER_TYPE[$T['XR_INTER_TYPE_ID']];
        $MAP_INTER[$T['XR_RES_ID_2']][$T['XR_INTER_TYPE_ID']][$T['ATOM_LIST_2']][] = $K;
        unset($T['XR_INTER_TYPE_ID']);
        $DATA['INTERS'][$K] = $T;
    }

    $res = runQuery("SELECT XR_RES_ID,XR_INTER_TYPE_ID, ATOM_LIST,CLASS,COUNT_INT FROM XR_CH_PROT_POS XP, XR_PROT_INT_STAT XS
	WHERE XP.PROT_SEQ_POS_ID = XS.PROT_SEQ_POS_ID AND XR_RES_ID IN (" . implode(',', array_keys($MAP_INTER)) . ')');

    foreach ($res as $T) {
        if (!isset($MAP_INTER[$T['XR_RES_ID']][$T['XR_INTER_TYPE_ID']])) continue;
        foreach ($MAP_INTER[$T['XR_RES_ID']][$T['XR_INTER_TYPE_ID']] as $AT_LIST => $RL) {
            foreach ($RL as $K) {
                $DATA['INTERS'][$K]['STAT'][(($AT_LIST == $DATA['INTERS'][$K]['ATOM_LIST_2']) ? "T" : "F")][$T['CLASS']] = $T['COUNT_INT'];
            }
        }
    }

    $CHAIN_FILTER = array();
    foreach ($chains as $CH => $DUMMY) $CHAIN_FILTER['CHAIN'][] = $CH;
    $DATA['CHAIN_INFO'] = getPDBInfo($PDB_ID, false, true, false, $CHAIN_FILTER);

    return $DATA;
}

function getPDBStructure($PDB_ID, $FILTERS)
{
    $CHAIN_FILTERS = array();
    $HAS_FILTERS = false;

    if (isset($FILTERS['CHAIN'])) {
        $HAS_FILTERS = true;
        foreach ($FILTERS['CHAIN'] as $T) {

            $CHAIN_FILTERS["'" . $T . "'"] = true;
        }
    }

    $query = "SELECT XR_CHAIN_ID, CHAIN_NAME FROM XR_CHAIN XC,XR_ENTRY XR WHERE XR.XR_ENTRY_ID = XC.XR_ENTRY_ID AND FULL_COMMON_NAME='" . $PDB_ID . "'";
    if ($CHAIN_FILTERS != array()) $query .= ' AND CHAIN_NAME IN (' . implode(',', array_keys($CHAIN_FILTERS)) . ')';

    $res = runQuery($query);
    $ENTRY = array();
    $MAP_CHAIN = array();
    foreach ($res as $line) {
        $MAP_CHAIN[$line['XR_CHAIN_ID']] = $line['CHAIN_NAME'];
        $ENTRY['CHAIN'][$line['CHAIN_NAME']] = array();
    }

    $query = "SELECT XR_CHAIN_ID, XR_RES_ID, NAME, POSITION,CLASS
	FROM XR_RES XR, XR_TPL_RES XT
	WHERE XR.XR_TPL_RES_ID= XT.XR_TPL_RES_ID
	AND XR_CHAIN_ID IN (" . implode(',', array_keys($MAP_CHAIN)) . ") ";


    $query .= "ORDER BY XR_CHAIN_ID ASC,XR_RES_ID ASC";
    $res = runQuery($query);

    $ENTRY['STAT']['RES'] = count($res);
    $MAP_RES = array();

    foreach ($res as $line) {
        $CH = $MAP_CHAIN[$line['XR_CHAIN_ID']];

        $ENTRY['CHAIN'][$CH][$line['POSITION']] = array('NAME' => $line['NAME'], 'ATOM' => array());
        $MAP_RES[$line['XR_RES_ID']] = &$ENTRY['CHAIN'][$CH][$line['POSITION']];

        if ($line['CLASS'] != 'AA' || $line['CLASS'] != 'MOD_AA' || $line['CLASS'] != 'WATER') {

            $line['CHAIN'] = $CH;
            unset($line['XR_CHAIN_ID']);
            $ENTRY['LIGS'][] = $line;
        }
    }


    $res = runQuery("SELECT XR_ATOM_ID, XT.NAME,XR.XR_RES_ID, XA.CHARGE,XA.MOL2TYPE,B_FACTOR,X,Y,Z ,SYMBOL
	FROM   XR_RES XR , XR_ATOM XA
	LEFT JOIN XR_TPL_ATOM XT ON XA.XR_TPL_ATOM_ID= XT.XR_TPL_ATOM_ID
	LEFT JOIN XR_ELEMENT XE ON XE.XR_ELEMENT_ID = XT.XR_ELEMENT_ID
	WHERE XR.XR_RES_ID = XA.XR_RES_ID
	AND XR.XR_CHAIN_ID IN (" . implode(',', array_keys($MAP_CHAIN)) . ") ORDER BY XR_CHAIN_ID ASC, XR_RES_ID ASC,XR_ATOM_ID ASC");


    $MAP_ATOM = array();
    $NATOM = 0;
    $ENTRY['STAT']['ATOM'] = count($res);
    foreach ($res as $line) {

        $RES = &$MAP_RES[$line['XR_RES_ID']];
        ++$NATOM;
        if ($line['MOL2TYPE'] == 'H') {
            $line['NAME'] = 'H';
            $line['SYMBOL'] = 'H';
        }
        unset($line['XR_RES_ID']);
        $XID = $line['XR_ATOM_ID'];
        $line['ATOM_NUM'] = $NATOM;
        $RES['ATOM'][$XID] = $line;
        $MAP_ATOM[$XID] = $NATOM;
    }

    $res = runQuery("SELECT BOND_TYPE,XR_ATOM_ID_1,XR_ATOM_ID_2
	FROM   XR_BOND
	WHERE XR_CHAIN_ID IN (" . implode(',', array_keys($MAP_CHAIN)) . ")");
    $ENTRY['STAT']['BOND'] = count($res);
    foreach ($res as $line) {
        $ENTRY['BOND'][] = array($MAP_ATOM[$line['XR_ATOM_ID_1']], $MAP_ATOM[$line['XR_ATOM_ID_2']], $line['BOND_TYPE']);
    }
    return $ENTRY;
}

function loadBatchNewsData($LIST, $W_CONTENT = true,$IS_ID=true)
{

    $DATA = array();
    $MAP = array();
    try {
        if ($LIST == array()) return array();
        // check if recent is included in keys of this array
        $STR = '';

        foreach ($LIST as $S) $STR .= "" . $S . "',";
        $SSTR = rtrim(rtrim($STR, ","), "'");
        $query="select DISTINCT ON (n.news_id) news_hash HASH_V, 
    n.news_id NEWS_ID,
      n.news_title NEWS_TITLE,
       " . (($W_CONTENT) ? " n.news_content NEWS_CONTENT, " : "")
            . " n.news_release_date RELEASE_DATE, 
        w.last_name, w.first_name, w.email, s.SOURCE_NAME
     FROM news n 
     LEFT JOIN web_user w ON w.web_user_Id = n.user_id,source s where s.source_id =n.source_id ";
         if ($IS_ID) $query.= "AND n.news_id IN (" . implode(',', $LIST) . ") ";
         else $query.= " AND news_hash IN (" . implode(',', $LIST) . ") ";
         $query.= "ORDER BY n.news_id, n.news_release_date DESC";
         $res = runQuery($query);
        $TMP = array();
        foreach ($res as $line) {
            $TMP[$line['HASH_V']] = $line;
            $MAP[$line['NEWS_ID']] = $line['HASH_V'];
        }
        if ($MAP == array()) return $TMP;
       
        if ($IS_ID)
        foreach ($LIST as $ID) $DATA[$MAP[$ID]] = $TMP[$MAP[$ID]];
        else
        {
            
            foreach ($LIST as $ID) $DATA[substr($ID,1,-1)] = $TMP[substr($ID,1,-1)];
        }

        return $DATA;
    } catch (Exception $e) {
        $DATA["error"] = "there was an exception" . $e;
        return $DATA;
    }
}


function getCompanyByName($VALUE)
{
    $query="SELECT * FROM company_entry ce LEFT JOIN company_synonym cs 
    ON cs.company_entry_Id = ce.company_entry_id
    WHERE 
     (LOWER(company_syn_name) LIKE '%".strtolower($VALUE)."%'
    OR LOWER(company_name) LIKE '%".strtolower($VALUE)."%')";
    $res=runQuery($query);
    
    $DATA=array();
    foreach ($res as $line)
    {
        if (!isset( $DATA[$line['COMPANY_NAME']]))$DATA[$line['COMPANY_NAME']]=array('COMPANY_TYPE'=>$line['COMPANY_TYPE']);
        $DATA[$line['COMPANY_NAME']]['SYN'][]=$line['COMPANY_SYN_NAME'];
    }
    
    return $DATA;
}

function getNewsInfo($NEWS, $TYPE)
{
    $NEWS_ID='';
    if (is_numeric($NEWS))$NEWS_ID=$NEWS;
    else 
    {
    $query="SELECT NEWS_ID FROM NEWS N,SOURCE S WHERE S.SOURCE_ID = N.SOURCE_ID AND  news_hash='" . $NEWS . "'";
    $res = runQuery($query);
    if (count($res) == 0) return array();
    $NEWS_ID = $res[0]['NEWS_ID'];
    }
    $ALLOWED = array('DRUG', 'CLINVAR', 'PATHWAY', 'GENE', 'DISEASE', 'PROT_FEAT', 'ASSAY', 'CELL', 'TISSUE', 'EVIDENCE','COMPANY','CLINICAL','NEWS');
    if (!in_array($TYPE, $ALLOWED)) return array();
    $DATA = array();
    switch ($TYPE) {
        case 'DRUG':
            $res = runQuery("SELECT DISTINCT DRUG_ENTRY_ID, IS_PRIMARY FROM NEWS_DRUG_MAP P WHERE NEWS_ID  =" . $NEWS_ID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) 
                {
                    $DATA[$line['DRUG_ENTRY_ID']] = getDrugInfo($line['DRUG_ENTRY_ID']);
                    $DATA[$line['DRUG_ENTRY_ID']]['IS_PRIMARY']=$line['IS_PRIMARY'];
                }
            }
            break;
            case 'COMPANY':
                $res = runQuery("SELECT DISTINCT COMPANY_NAME,COMPANY_TYPE, IS_PRIMARY
              FROM COMPANY_ENTRY DE, NEWS_COMPANY_MAP  PD WHERE PD.COMPANY_ENTRY_ID = DE.COMPANY_ENTRY_ID AND NEWS_ID=" . $NEWS_ID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) $DATA[$line['COMPANY_NAME']] = $line;
            }
            break;
        case 'CLINVAR':
            break;

        case 'PATHWAY':
            break;
        case 'GENE':
            $res = runQuery("SELECT SYMBOL,GENE_ID,full_name,DESCRIPTION, IS_PRIMARY FROM NEWS_GN_MAP PRM, GN_ENTRY GR
             LEFT JOIN GN_PROT_MAP PGM ON PGM.GN_ENTRY_ID = GR.GN_ENTRY_ID
             LEFT JOIN PROT_DESC PE ON PE.PROT_ENTRY_ID = PGM.PROT_ENTRY_ID AND DESC_TYPE='FUNCTION'
     WHERE GR.GN_ENTRY_ID = PRM.GN_ENTRY_ID AND NEWS_ID=" . $NEWS_ID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) {
                    $DATA[$line['GENE_ID']] = array('SYMBOL' => $line['SYMBOL'], 'NAME' => $line['FULL_NAME'],'IS_PRIMARY'=>$line['IS_PRIMARY']);
                    if ($line['DESCRIPTION'] != '') $DATA[$line['GENE_ID']]['DESCRIPTION'][] = $line['DESCRIPTION'];
                }
            }

            break;
        case 'DISEASE':
            $res = runQuery("SELECT DISTINCT DISEASE_NAME,disease_tag,disease_definition,IS_PRIMARY
              FROM DISEASE_ENTRY DE, NEWS_DISEASE_MAP  PD WHERE PD.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID AND NEWS_ID=" . $NEWS_ID);
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) $DATA[$line['DISEASE_TAG']] = $line;
            }
            break;

            case 'CLINICAL':
                $res = runQuery("SELECT DISTINCT alias_name,official_title, clinical_phase,brief_summary,clinical_status,IS_PRIMARY
                  FROM CLINICAL_TRIAL_ALIAS CT, CLINICAL_TRIAL DE, NEWS_CLINICAL_TRIAL_MAP  PD 
                  WHERE CT.CLINICAL_TRIAL_ID = DE.CLINICAL_TRIAL_ID AND CT.ALIAS_TYPE='Primary' AND PD.CLINICAL_TRIAL_ID = DE.CLINICAL_TRIAL_ID AND NEWS_ID=" . $NEWS_ID);
                  
                if ($res !== false && count($res) != 0) {
    
                    foreach ($res as $line) $DATA[$line['ALIAS_NAME']] = $line;
                }
                break;

        case 'NEWS':
            $query="SELECT DISTINCT news_title,news_hash NEWS_HASH, source_name,news_release_date, last_name,first_name,email,business_title
            FROM  NEWS N,SOURCE S, NEWS_NEWS_MAP NNM, WEB_USER WU WHERE S.SOURCE_ID = N.SOURCE_ID AND WU.WEB_USER_ID=N.USER_ID
        AND  NNM.NEWS_ID=" . $NEWS_ID.' AND NNM.NEWS_PARENT_ID = N.NEWS_ID';
        
            $res = runQuery($query);
                
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) $DATA['PARENT'][$line['NEWS_HASH']] = $line;
            }
            $query="SELECT DISTINCT news_title,news_hash NEWS_HASH, source_name,news_release_date, last_name,first_name,email,business_title
            FROM NEWS N,SOURCE S, NEWS_NEWS_MAP NNM, WEB_USER WU WHERE S.SOURCE_ID = N.SOURCE_ID   AND WU.WEB_USER_ID=N.USER_ID
        AND  NNM.NEWS_PARENT_ID=" . $NEWS_ID.' AND NNM.NEWS_ID = N.NEWS_ID';
        
            $res = runQuery($query);
                
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) $DATA['CHILD'][$line['NEWS_HASH']] = $line;
            }
            break;

    }
    return $DATA;
}

function getNewsfile($HASH)
{
    $res = runQuery("SELECT NEWS_ID, document_name,document_content, document_description, document_hash, document_version,mime_type FROM NEWS_DOCUMENT  WHERE document_hash='" . $HASH . "'");
    if ($res[0]['DOCUMENT_CONTENT']!=null)
    $res[0]['DOCUMENT_CONTENT'] = stream_get_contents($res[0]['DOCUMENT_CONTENT']);
    if (count($res) >= 1) return $res[0];
    return null;
}

function updateNews($NEWS)
{
    try {
        global $DB_CONN;
        $CREATED_NEWS_ID = '';
        $USER_ID = $NEWS['USER_ID'];
        $NEWS_CONTENT = $NEWS['NEWS_CONTENT'];
        $NEWS_HTML = $NEWS['NEWS_HTML'];
        $TITLE = $NEWS['TITLE'];

        

        $res=runQuery("SELECT * FROM NEWS N, SOURCE S WHERE S.SOURCE_ID=N.SOURCE_ID AND news_hash='".$NEWS['NEWS_HASH']."'");
        if ($res ==array()) throw new Exception ('No news found with this identifier');
        if (count($res)>1) throw new Exception ('Multiple news found with this identifier');
        $ENTRY=$res[0];
        $ID=$ENTRY['NEWS_ID'];

        $query="UPDATE news 
        SET news_title = :news_title, 
        news_content= :news_content, 
        news_delta=:news_delta, 
        source_id=:source_id 
        WHERE news_id = ".$ID;
    
        $stmt = $DB_CONN->prepare($query);

        //echo strlen($content)."\n";
        $stmt->bindParam(':news_content', $NEWS_HTML, PDO::PARAM_STR);
        $stmt->bindParam(':news_delta', $NEWS_CONTENT, PDO::PARAM_STR);
        $stmt->bindParam(':news_title', $TITLE, PDO::PARAM_STR);
        $stmt->bindParam(':source_id', $NEWS['SOURCE'], PDO::PARAM_INT);
       $SSTRING= $stmt->execute();

        // $SSTRING = runQuery($query);
        // echo "OUTCOME:";

        // print_r($SSTRING);
        if ($SSTRING === false) throw new Exception('Unable to update news');

        return $ID;
    } catch (Exception $e) {
        print_r($e);
        return null;
    }
}



function submitNews($NEWS)
{

    
    try {
        global $DB_CONN;
        $CREATED_NEWS_ID = '';
        $USER_ID = $NEWS['USER_ID'];
        $NEWS_CONTENT = $NEWS['NEWS_CONTENT'];
        $NEWS_HTML = $NEWS['NEWS_HTML'];
        $TITLE = $NEWS['TITLE'];

        $query = "INSERT INTO news VALUES (nextval('news_sq'),'" . $TITLE . "',
       :document_html,CURRENT_DATE,CURRENT_TIMESTAMP," . $USER_ID . "," . $NEWS['SOURCE'] . ",:document_content,'".$NEWS['HASH']."') RETURNING news_id";
        $stmt = $DB_CONN->prepare($query);

        //echo strlen($content)."\n";
        $stmt->bindParam(':document_html', $NEWS_HTML, PDO::PARAM_STR);
        $stmt->bindParam(':document_content', $NEWS_CONTENT, PDO::PARAM_STR);
       $SSTRING= $stmt->execute();

        // $SSTRING = runQuery($query);
        // echo "OUTCOME:";

        // print_r($SSTRING);
        if ($SSTRING !== false) {
            
            $row=$stmt->fetch();
            
            $CREATED_NEWS_ID = $row['news_id'];
        }else throw new Exception('Unable to insert news');

        return $CREATED_NEWS_ID;
    } catch (Exception $e) {
        print_r($e);
        return null;
    }
}

function getFullNewsSources()
{
    $res = runQuery("SELECT source_id, source_name,source_metadata FROM source where subgroup='News'");
    $data = array();
    foreach ($res as $line) 
    {
        $D=array();
        if ($line['SOURCE_METADATA']!='')$D=json_decode($line['SOURCE_METADATA'],true);
        $data[$line['SOURCE_ID']] =array( $line['SOURCE_NAME'],$D);
    }
    return $data;
}

function getNewsSources()
{
    $res = runQuery("SELECT source_id, source_name FROM source where subgroup='News'");
    $data = array();
    foreach ($res as $line) $data[$line['SOURCE_ID']] = $line['SOURCE_NAME'];
    return $data;
}
function submitNewsFiles($NEWS_ID, &$FILES)
{

    $DATA = array('SUCCESS' => true);
    global $DB_CONN;
    try {
        for ($I = 0; $I < count($FILES); ++$I) {
            if ($FILES['fpath']['size'][$I] == 0) continue;
            $content = file_get_contents($FILES['fpath']['tmp_name'][$I]);
            $md5 = md5($content);
            $name = $FILES['fpath']['name'][$I];


            if ($FILES['fpath']['file_name'][$I] != '') $name = $FILES['fpath']['file_name'][$I];
            $desc = $FILES['fpath']['file_desc'][$I];
            $query = "INSERT INTO news_document (news_document_id,document_name,document_content,document_description,document_hash,creation_date,news_id,document_version,mime_type) VALUES
				(nextval('news_document_sq'),
                '" . $FILES['fpath']['name'][$I] . "',
                :document_content,
                :document_description,
                '" . $md5 . "',
                CURRENT_TIMESTAMP,
                " . $NEWS_ID . ",1,
                '" . $FILES['fpath']['mime'][$I] . "'
                ) ";
            echo $query . "\n";
            echo $desc;

            $stmt = $DB_CONN->prepare($query);

            //echo strlen($content)."\n";
            $stmt->bindParam(':document_content', $content, PDO::PARAM_LOB);
            $stmt->bindParam(':document_description', $desc, PDO::PARAM_STR);
            $stmt->execute();
        }
    } catch (Exception $e) {
        $DATA['SUCCESS'] = false;
        $DATA['ERROR_MESSAGE'] = $e->getMessage();
    }
    return $DATA;
}

function updateNewsAnnotations($NEWS_ID, $ANNOTATIONS)
{

    
      $DATA = array('SUCCESS' => true);
      try {
          $KEY_UPDATE_COUNT = 0;
  
  
  
          foreach ($ANNOTATIONS as $TAGS_KEY => $TAGS_ITEMS) {
              if ($TAGS_ITEMS == array()) continue;
              if ($TAGS_KEY == 'GENE') {
                  
                  $CURRENT=getNewsInfo($NEWS_ID,'GENE');
                 
                  foreach ($TAGS_ITEMS as $GENE_ID) {
                      $A_T=(in_array($GENE_ID,$ANNOTATIONS['GENE_PRIMARY'])?'T':'F');
                      if (isset($CURRENT[$GENE_ID]))
                      {
                          if ($A_T!=$CURRENT[$GENE_ID]['IS_PRIMARY'])
                          {
  
  
                              
                              $q="UPDATE  news_gn_map SET IS_PRIMARY='".$A_T."' WHERE  NEWS_ID = ".$NEWS_ID.' AND GN_ENTRY_ID =(SELECT gn_entry_id from gn_entry ge where ge.gene_id = '.$GENE_ID.')';
                               //echo $q;
                                  if (!runQueryNoRes($q)) {
                                      $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to update gene id ' . $GENE_ID);
                                      return $DATA;
                                  }
                          }
                          $CURRENT[$GENE_ID]['VALID']=true;continue;
  
                      }
                      
                      $q = "INSERT INTO news_gn_map VALUES (nextval('news_gn_map_sq')," . $NEWS_ID . ",(SELECT gn_entry_id from gn_entry ge where ge.gene_id = $GENE_ID), '".(in_array($GENE_ID,$ANNOTATIONS['GENE_PRIMARY'])?'T':'F')."')";
                      if (!runQuery($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert gene id ' . $GENE_ID);
                          return $DATA;
                      }
                      $KEY_UPDATE_COUNT++;
                  }
                  foreach ($CURRENT as $GENE_ID=>&$INFO)
                  {
                      if (isset($INFO['VALID']))continue;
                      $q="DELETE FROM news_gn_map WHERE NEWS_ID = ".$NEWS_ID.' AND GN_ENTRY_ID =(SELECT gn_entry_id from gn_entry ge where ge.gene_id = '.$GENE_ID.')';
                      if (!runQueryNoRes($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to delete gene id ' . $GENE_ID);
                          return $DATA;
                      }
                  }
                  
              }
              if ($TAGS_KEY == 'NEWS_REL') {
                  $CURRENT=getNewsInfo($NEWS_ID,'NEWS');
                  
                  foreach ($TAGS_ITEMS as $NEWS_HASH) {
                      if (isset($CURRENT['PARENT'][$NEWS_HASH])){$CURRENT['PARENT'][$NEWS_HASH]['VALID']=true;continue;}
                      $q = "INSERT INTO news_news_map VALUES (" . $NEWS_ID . ",(SELECT DISTINCT news_id from  NEWS CT, SoURCE S where ct.source_id =s.source_id
                      AND  news_hash = '" . $NEWS_HASH . "'))";
                      
                      if (!runQueryNoRes($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert news  ' . $NEWS_HASH);
                          return $DATA;
                      }
  
                      //$res=runQuery("WITH target_company_entry_id as (SELECT DISTINCT ce.company_entry_id from company_entry ce where ce.company_name = '$COMPANY_ID'), news_map_values as (select coalesce(max(news_company_map_id), 0) + 1 as news_company_map_id, $NEWS_ID as news_id, te.company_entry_id as company_entry_id from NEWS_COMPANY_MAP, target_company_entry_id te group by te.company_entry_id) INSERT INTO NEWS_COMPANY_MAP SELECT * FROM news_map_values RETURNING news_company_map_id");                                        
                      $KEY_UPDATE_COUNT++;
                  }
                  if (isset($CURRENT['PARENT']))
                  foreach ($CURRENT['PARENT'] as $NEWS_HASH=>&$INFO)
                  {
                      if (isset($INFO['VALID']))continue;
                      $q="DELETE FROM news_news_map WHERE NEWS_ID = ".$NEWS_ID." AND NEWS_PARENT_ID =(SELECT DISTINCT news_id from  NEWS CT, SoURCE S where ct.source_id =s.source_id
                      AND  news_hash = '" . $NEWS_HASH . "')";
                  //    echo $q;
                      if (!runQueryNoRes($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to delete news ' . $INFO['NEWS_TITLE']);
                          return $DATA;
                      }
                  }
            
  
  
                 
  
              }
              if ($TAGS_KEY == 'CLINICAL') {
  
                  $CURRENT=getNewsInfo($NEWS_ID,'CLINICAL');
                  // echo '<pre>CURR';
                  // print_R($CURRENT);
                  // echo 'TAGS:';
                  // print_R($TAGS_ITEMS);
                  // exit;
                  foreach ($TAGS_ITEMS as $CLINICAL_TRIAL_ID) {
                      $A_T=(in_array($CLINICAL_TRIAL_ID,$ANNOTATIONS['CLINICAL_PRIMARY'])?'T':'F');
                      if (isset($CURRENT[$CLINICAL_TRIAL_ID]))
                      {
                          if ($A_T!=$CURRENT[$CLINICAL_TRIAL_ID]['IS_PRIMARY'])
                          {
                              $q="UPDATE news_clinical_trial_map SET IS_PRIMARY='".$A_T."' WHERE NEWS_ID = ".$NEWS_ID.' AND CLINICAL_TRIAL_ID =(SELECT DISTINCT DE.clinical_trial_id
                                  FROM CLINICAL_TRIAL_ALIAS CT, CLINICAL_TRIAL DE, NEWS_CLINICAL_TRIAL_MAP  PD 
                                  WHERE CT.CLINICAL_TRIAL_ID = DE.CLINICAL_TRIAL_ID AND CT.ALIAS_TYPE=\'Primary\' AND PD.CLINICAL_TRIAL_ID = DE.CLINICAL_TRIAL_ID AND alias_name = \''.$CLINICAL_TRIAL_ID.'\')';
                             //  echo $q;
                                  if (!runQueryNoRes($q)) {
                                      $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to update clinical trial ' . $CLINICAL_TRIAL_ID);
                                      return $DATA;
                                  }
                          }
                          $CURRENT[$CLINICAL_TRIAL_ID]['VALID']=true;continue;
                      }
                      $res=runQuery("SELECT DISTINCT ct.clinical_trial_id from clinical_trial ct, clinical_Trial_alias cta
                      where cta.clinical_trial_id = ct.clinical_trial_id AND alias_name = '" . $CLINICAL_TRIAL_ID . "' AND alias_type='Primary'");
                      
                   //print_R($res);
                     foreach ($res as $line)
                     {
                     $q = "INSERT INTO news_clinical_trial_map VALUES (nextval('news_clinical_trial_map_sq')," . $NEWS_ID . ",".$line['CLINICAL_TRIAL_ID'].", '".$A_T."')";
                    // echo $q;
                     if (!runQueryNoRes($q)) {
                         $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert clinical id ' . $line['CLINICAL_TRIAL_ID']);
                         return $DATA;
                     }
                 
                     $KEY_UPDATE_COUNT++;
                     }
                  }
                     foreach ($CURRENT as $CLINICAL_TRIAL_ID=>&$INFO)
                  {
                      if (isset($INFO['VALID']))continue;
                      $q="DELETE FROM news_clinical_trial_map WHERE NEWS_ID = ".$NEWS_ID.' AND CLINICAL_TRIAL_ID =(SELECT DISTINCT DE.clinical_trial_id
                      FROM CLINICAL_TRIAL_ALIAS CT, CLINICAL_TRIAL DE, NEWS_CLINICAL_TRIAL_MAP  PD 
                      WHERE CT.CLINICAL_TRIAL_ID = DE.CLINICAL_TRIAL_ID AND CT.ALIAS_TYPE=\'Primary\' AND PD.CLINICAL_TRIAL_ID = DE.CLINICAL_TRIAL_ID AND alias_name = \''.$CLINICAL_TRIAL_ID.'\')';
                    // echo $q;
                      if (!runQueryNoRes($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to delete clinical trial ' . $CLINICAL_TRIAL_ID);
                          return $DATA;
                      }
                  }
                  
                  // global $DB_CONN;$DB_CONN->rollback();
                  // exit;
                
              }
              if ($TAGS_KEY == 'DISEASE') {
                  $CURRENT=getNewsInfo($NEWS_ID,'DISEASE');
                  // echo '<pre>CURR';
                  // print_R($CURRENT);
                  // echo 'TAGS:';
                  // print_R($TAGS_ITEMS);
                  // $TAGS_ITEMS=array('MONDO_0011561');
                  foreach ($TAGS_ITEMS as $DISEASE_ID) {
                      $A_T=(in_array($DISEASE_ID,$ANNOTATIONS['DISEASE_PRIMARY'])?'T':'F');
                      if (isset($CURRENT[$DISEASE_ID]))
                      {
                          if ($A_T!=$CURRENT[$DISEASE_ID]['IS_PRIMARY'])
                          {
  
  
                              $q="UPDATE news_disease_map SET IS_PRIMARY='".$A_T."' WHERE NEWS_ID = ".$NEWS_ID.' AND DISEASE_ENTRY_ID =(SELECT DISEASE_ENTRY_ID FROM DISEASE_ENTRY WHERE DISEASE_TAG=\''.$DISEASE_ID.'\')';
                              
                             //  echo $q;
                                  if (!runQueryNoRes($q)) {
                                      $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to update disease ' . $INFO['DISEASE_NAME']);
                                      return $DATA;
                                  }
                          }
                          $CURRENT[$DISEASE_ID]['VALID']=true;continue;
                      }
  
                      
                    $res=runQuery ("SELECT DISTINCT de.disease_entry_id from DISEASE_ENTRY de where de.disease_tag = '" . $DISEASE_ID . "'");
                    foreach ($res as $line)
                      {
                      $q = "INSERT INTO news_disease_map VALUES (nextval('news_disease_map_sq')," . $NEWS_ID . ",".$line['DISEASE_ENTRY_ID'].", '".(in_array($DISEASE_ID,$ANNOTATIONS['DISEASE_PRIMARY'])?'T':'F')."')";
                      if (!runQueryNoRes($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert disease id ' . $DISEASE_ID);
                          return $DATA;
                      }
  
                      // $res=runQuery("WITH target_disease_entry_id as (SELECT DISTINCT de.disease_entry_id from DISEASE_ENTRY de where de.disease_name = '$DISEASE_ID'), news_map_values as (select coalesce(max(news_disease_map_id), 0) + 1 as news_disease_map_id, $NEWS_ID as news_id, te.disease_entry_id as disease_entry_id from NEWS_DISEASE_MAP, target_disease_entry_id te group by te.disease_entry_id) INSERT INTO NEWS_DISEASE_MAP SELECT * FROM news_map_values RETURNING news_disease_map_id");                                        
                      $KEY_UPDATE_COUNT++;
                  }
                  }
                  foreach ($CURRENT as $DISEASE_ID=>&$INFO)
                  {
                      if (isset($INFO['VALID']))continue;
                      $q="DELETE FROM news_disease_map WHERE NEWS_ID = ".$NEWS_ID.' AND DISEASE_ENTRY_ID =(SELECT DISEASE_ENTRY_ID FROM DISEASE_ENTRY WHERE DISEASE_TAG=\''.$DISEASE_ID.'\')';
                  //    echo $q;
                      if (!runQueryNoRes($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to delete disease ' . $INFO['DISEASE_NAME']);
                          return $DATA;
                      }
                  }
  
                 
              }
              if ($TAGS_KEY == 'COMPANY') {
                  $CURRENT=getNewsInfo($NEWS_ID,'COMPANY');
                  foreach ($TAGS_ITEMS as $COMPANY_ID) {
                  $A_T=(in_array($COMPANY_ID,$ANNOTATIONS['COMPANY_PRIMARY'])?'T':'F');
                      if (isset($CURRENT[$COMPANY_ID]))
                      {
                          if ($A_T!=$CURRENT[$COMPANY_ID]['IS_PRIMARY'])
                          {
  
  
                              $q="UPDATE  news_company_map SET IS_PRIMARY='".$A_T."' WHERE NEWS_ID = ".$NEWS_ID." AND COMPANY_ENTRY_ID =(SELECT DISTINCT ce.company_entry_id from company_entry ce where ce.company_name = '" . $COMPANY_ID . "')";
                              
                              // echo $q;
                                  if (!runQueryNoRes($q)) {
                                      $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to update  company ' . $INFO['COMPANY_ID']);
                                      return $DATA;
                                  }
                          }
                          $CURRENT[$COMPANY_ID]['VALID']=true;continue;
                      }
                  foreach ($TAGS_ITEMS as $COMPANY_ID) {
                      if (isset($CURRENT[$COMPANY_ID])){$CURRENT[$COMPANY_ID]['VALID']=true;continue;}
                      $q = "INSERT INTO news_company_map VALUES (nextval('news_company_map_sq')," . $NEWS_ID . ",(SELECT DISTINCT ce.company_entry_id from company_entry ce where ce.company_name = '" . $COMPANY_ID . "'), '".(in_array($COMPANY_ID,$ANNOTATIONS['COMPANY_PRIMARY'])?'T':'F')."')";
                      
                      if (!runQueryNoRes($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert company  ' . $COMPANY_ID);
                          return $DATA;
                      }
  
                      //$res=runQuery("WITH target_company_entry_id as (SELECT DISTINCT ce.company_entry_id from company_entry ce where ce.company_name = '$COMPANY_ID'), news_map_values as (select coalesce(max(news_company_map_id), 0) + 1 as news_company_map_id, $NEWS_ID as news_id, te.company_entry_id as company_entry_id from NEWS_COMPANY_MAP, target_company_entry_id te group by te.company_entry_id) INSERT INTO NEWS_COMPANY_MAP SELECT * FROM news_map_values RETURNING news_company_map_id");                                        
                      $KEY_UPDATE_COUNT++;
                  }
                  foreach ($CURRENT as $COMPANY_ID=>&$INFO)
                  {
                      if (isset($INFO['VALID']))continue;
                      $q="DELETE FROM news_company_map WHERE NEWS_ID = ".$NEWS_ID." AND COMPANY_ENTRY_ID =(SELECT DISTINCT ce.company_entry_id from company_entry ce where ce.company_name = '" . $COMPANY_ID . "')";
                  //    echo $q;
                      if (!runQueryNoRes($q)) {
                          $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to delete company ' . $INFO['COMPANY_ID']);
                          return $DATA;
                      }
                  }
                }
  
  
  
                 
  
              }
              if ($TAGS_KEY == 'DRUG') {
                  $CURRENT=getNewsInfo($NEWS_ID,'DRUG');
                //   echo '<pre>CURR';
                //   print_R($CURRENT);
                //   echo 'TAGS:';
                //   print_R($TAGS_ITEMS);
                  
                  foreach ($TAGS_ITEMS as $DRUG_ID) {
                    echo "TEST DRUG \t".$DRUG_ID."\n";
                    //echo "START ".$DRUG_ID."\t";
                      $found=false;
                      $A_T=(in_array($DRUG_ID,$ANNOTATIONS['DRUG_PRIMARY'])?'T':'F');
                      foreach ($CURRENT as $DRUG_DB_ID=>&$DRUG_ENTRY)
                      {
                        if (strtolower($DRUG_ENTRY['DRUG_PRIMARY_NAME'])==strtolower($DRUG_ID))$found=true;
                        if (!$found)continue;
                        echo "\t\tFOUND\n";
                        $DRUG_ENTRY['VALID']=true;
                        if ($A_T!=$DRUG_ENTRY['IS_PRIMARY'])
                        {

                            $q="UPDATE news_drug_map SET IS_PRIMARY='".$A_T."' WHERE NEWS_ID = ".$NEWS_ID." AND DRUG_ENTRY_ID =".$DRUG_DB_ID;
                            echo $q;
                                if (!runQueryNoRes($q)) {
                                    $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to update drug  ' . $INFO['DRUG_PRIMARY_NAME']);
                                    return $DATA;
                                }
                        }
                        break;
                      
                      }
                      
                      if ($found)continue;
                      echo "PUBLIC\t".$INFO['DRUG_PRIMARY_NAME']."\tADD\n";
                      //since drug validate searches by compound rather than drug name/drug entry id and returns sm_entry in order to store the drug entry id, we need to pull from it first
                      // $DRUGQUERY= "INSERT INTO NEWS_DRUG_MAP VALUES ((select coalesce(max(news_drug_map_id), 0) + 1 FROM NEWS_DRUG_MAP),".$NEWS_ID.',(SELECT DISTINCT de.drug_entry_id from drug_name de where LOWER(de.drug_name) =\''.strtolower($DRUG_ID).'\'))';
                      //"WITH target_drug_entry_id as (SELECT DISTINCT de.drug_entry_id from drug_name de where de.drug_name ='$DRUG_ID'), news_map_values as (select coalesce(max(news_drug_map_id), 0) + 1 as news_drug_map_id, $NEWS_ID as news_id, te.drug_entry_id as drug_entry_id from NEWS_DRUG_MAP,	target_drug_entry_id te GROUP BY te.drug_entry_id) INSERT INTO NEWS_DRUG_MAP SELECT * FROM news_map_values RETURNING news_drug_map_id";                        
                      $res=runQuery ("SELECT DISTINCT de.drug_entry_id from drug_name de where LOWER(de.drug_name) ='" . strtolower($DRUG_ID) . "' ");
                      foreach ($res as $line)
                      {
                          $q = "INSERT INTO news_drug_map  VALUES (nextval('news_drug_map_sq')," . $NEWS_ID . ",".$line['DRUG_ENTRY_ID'].", '".(in_array($DRUG_ID,$ANNOTATIONS['DRUG_PRIMARY'])?'T':'F')."')";
                        //  echo $q."\n";
                          if (!runQueryNoRes($q)) {
                              $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert drug  ' . $DRUG_ID);
                              return $DATA;
                          }
                      }
                      $KEY_UPDATE_COUNT++;
                  }
                  
                  foreach ($CURRENT as $DRUG_ID=>&$INFO)
                  {
                      
                      if (isset($INFO['VALID']))continue;
                      echo "PUBLIC\t".$INFO['DRUG_PRIMARY_NAME'].' TO DEL'."\n";
                      
                          $q="DELETE FROM news_drug_map WHERE NEWS_ID = ".$NEWS_ID." AND DRUG_ENTRY_ID =".$DRUG_ID;
                          //echo $q;
                          if (!runQueryNoRes($q)) {
                              $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to delete drug ' . $INFO['DRUG_PRIMARY_NAME']);
                              return $DATA;
                          }
                      
                  }
  
                  //echo "SUCCESS";
                  //$CURRENT=getNewsInfo($NEWS_ID,'DRUG');
                  // echo '<pre>CURR';
                  // print_R($CURRENT);
                  // global $DB_CONN;
                  // $DB_CONN->commit();
                  // exit;
              }
          }
  
          return $DATA;
      } catch (Exception $e) {
          $DATA['SUCCESS'] = false;
          $DATA['ERROR_MESSAGE'] = $e;
          return $DATA;
      }
  }


function submitNewsAnnotations($NEWS_ID, $ANNOTATIONS)
{

    $DATA = array('SUCCESS' => true);
    try {
        $KEY_UPDATE_COUNT = 0;



        foreach ($ANNOTATIONS as $TAGS_KEY => $TAGS_ITEMS) {
            if ($TAGS_ITEMS == array()) continue;
            if ($TAGS_KEY == 'GENE') {
                foreach ($TAGS_ITEMS as $K=>$GENE_ID) {
                    $q = "INSERT INTO news_gn_map VALUES (nextval('news_gn_map_sq')," . $NEWS_ID . ",(SELECT gn_entry_id from gn_entry ge where ge.gene_id = $GENE_ID), '".(in_array($GENE_ID,$ANNOTATIONS['GENE_PRIMARY'])?'T':'F')."')";
                    if (!runQuery($q)) {
                        $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert gene id ' . $GENE_ID);
                        return $DATA;
                    }
                    $KEY_UPDATE_COUNT++;
                }
            }
            if ($TAGS_KEY == 'CLINICAL') {
                foreach ($TAGS_ITEMS as $K=> $CLINICAL_TRIAL_ID) {

                    $res=runQuery("SELECT DISTINCT ct.clinical_trial_id from clinical_trial ct, clinical_Trial_alias cta
                    where cta.clinical_trial_id = ct.clinical_trial_id AND alias_name = '" . $CLINICAL_TRIAL_ID . "' AND alias_type='Primary'");
                    
                 
                   foreach ($res as $line)
                   {
                   $q = "INSERT INTO news_clinical_trial_map VALUES (nextval('news_clinical_trial_map_sq')," . $NEWS_ID . ",".$line['CLINICAL_TRIAL_ID'].", '".(in_array($CLINICAL_TRIAL_ID,$ANNOTATIONS['CLINICAL_PRIMARY'])?'T':'F')."')";
                   if (!runQueryNoRes($q)) {
                       $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert clinical id ' . $line['CLINICAL_TRIAL_ID']);
                       return $DATA;
                   }
               
                   $KEY_UPDATE_COUNT++;
                   }
                }
            }
            if ($TAGS_KEY == 'NEWS_REL') {
                foreach ($TAGS_ITEMS as $NEWS_HASH) {

                    $res=runQuery("SELECT DISTINCT news_id from  NEWS CT, SoURCE S where ct.source_id =s.source_id
                    AND  news_hash = '" . $NEWS_HASH . "'");
                    
                 
                   foreach ($res as $line)
                   {
                   $q = "INSERT INTO news_news_map VALUES (" . $NEWS_ID . ",".$line['NEWS_ID'].")";
                   
                   if (!runQueryNoRes($q)) {
                       $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert news id ' . $line['NEWS_ID']);
                       return $DATA;
                   }
               
                   $KEY_UPDATE_COUNT++;
                   }
                }
                
            }
            if ($TAGS_KEY == 'DISEASE') {
                foreach ($TAGS_ITEMS as $K=>$DISEASE_ID) {
                  $res=runQuery ("SELECT DISTINCT de.disease_entry_id from DISEASE_ENTRY de where de.disease_tag = '" . $DISEASE_ID . "'");
                  foreach ($res as $line)
                    {
                    $q = "INSERT INTO news_disease_map VALUES (nextval('news_disease_map_sq')," . $NEWS_ID . ",".$line['DISEASE_ENTRY_ID'].", '".(in_array($DISEASE_ID,$ANNOTATIONS['DISEASE_PRIMARY'])?'T':'F')."')";
                    if (!runQueryNoRes($q)) {
                        $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert disease id ' . $DISEASE_ID);
                        return $DATA;
                    }

                    // $res=runQuery("WITH target_disease_entry_id as (SELECT DISTINCT de.disease_entry_id from DISEASE_ENTRY de where de.disease_name = '$DISEASE_ID'), news_map_values as (select coalesce(max(news_disease_map_id), 0) + 1 as news_disease_map_id, $NEWS_ID as news_id, te.disease_entry_id as disease_entry_id from NEWS_DISEASE_MAP, target_disease_entry_id te group by te.disease_entry_id) INSERT INTO NEWS_DISEASE_MAP SELECT * FROM news_map_values RETURNING news_disease_map_id");                                        
                    $KEY_UPDATE_COUNT++;
                }
                }
            }
            if ($TAGS_KEY == 'COMPANY') {
                foreach ($TAGS_ITEMS as $K=>$COMPANY_ID) {

                    $q = "INSERT INTO news_company_map VALUES (nextval('news_company_map_sq')," . $NEWS_ID . ",(SELECT DISTINCT ce.company_entry_id from company_entry ce where ce.company_name = '" . $COMPANY_ID . "'), '".(in_array($COMPANY_ID,$ANNOTATIONS['COMPANY_PRIMARY'])?'T':'F')."')";
                    
                    if (!runQueryNoRes($q)) {
                        $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert company  ' . $COMPANY_ID);
                        return $DATA;
                    }

                    //$res=runQuery("WITH target_company_entry_id as (SELECT DISTINCT ce.company_entry_id from company_entry ce where ce.company_name = '$COMPANY_ID'), news_map_values as (select coalesce(max(news_company_map_id), 0) + 1 as news_company_map_id, $NEWS_ID as news_id, te.company_entry_id as company_entry_id from NEWS_COMPANY_MAP, target_company_entry_id te group by te.company_entry_id) INSERT INTO NEWS_COMPANY_MAP SELECT * FROM news_map_values RETURNING news_company_map_id");                                        
                    $KEY_UPDATE_COUNT++;
                }
            }
            if ($TAGS_KEY == 'DRUG') {
                foreach ($TAGS_ITEMS as $K=> $DRUG_ID) {
                    $res=runQuery ("SELECT DISTINCT de.drug_entry_id from drug_entry de where LOWER(de.drug_primary_name) ='" . strtolower($DRUG_ID) . "' ");
                    foreach ($res as $line)
                    {
                        $q = "INSERT INTO news_drug_map  VALUES (nextval('news_drug_map_sq')," . $NEWS_ID . ",".$line['DRUG_ENTRY_ID'].", '".(in_array($DRUG_ID,$ANNOTATIONS['DRUG_PRIMARY'])?'T':'F')."')";
                        if (!runQueryNoRes($q)) {
                            $DATA = array('SUCCESS' => false, 'ERROR_MESSAGE' => 'Unable to insert drug  ' . $DRUG_ID);
                            return $DATA;
                        }
                    }
                    $KEY_UPDATE_COUNT++;
                }
            }
        }

        return $DATA;
    } catch (Exception $e) {
        $DATA['SUCCESS'] = false;
        $DATA['ERROR_MESSAGE'] = $e;
        return $DATA;
    }
}

function getCountPubliNews($FILTERS = array())
{
    $query = "SELECT COUNT(*) CO FROM NEWS N ";
    $WH_CLAUSE = $FILTERS ? " WHERE N.NEWS_RELEASE_DATE IS NOT NULL" : "WHERE N.NEWS_RELEASE_DATE < CURRENT_DATE - 30";
    if ($FILTERS != array()) {
        $NSTEP = 0;

        foreach ($FILTERS as $TYPE => $LIST) {
            foreach ($LIST as $RULE_TYPE) {
                ++$NSTEP;
                if ($TYPE == 'gene') {
                    $query .= ', NEWS_GN_MAP MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND N.NEWS_ID=MPR' . $NSTEP . '.NEWS_ID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
                }
                if ($TYPE == 'disease') {
                    $query .= ', NEWS_DISEASE_MAP MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND N.NEWS_ID=MPR' . $NSTEP . '.NEWS_ID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
                }
                if ($TYPE == 'drug') {
                    $query .= ', NEWS_DRUG_MAP MPR' . $NSTEP . ', DRUG_ENTRY PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND N.NEWS_ID=MPR' . $NSTEP . '.NEWS_ID AND PR' . $NSTEP . '.DRUG_ENTRY_ID = MPR' . $NSTEP . '.DRUG_ENTRY_ID AND PR' . $NSTEP . '.DRUG_ENTRY_ID=' . $RULE_TYPE;
                }
                if ($TYPE == 'source') {
                    $query .= ', SOURCE S' . $NSTEP ;
                    $WH_CLAUSE .= ' AND S'.$NSTEP.'.SOURCE_ID=N.SOURCE_ID AND  SOURCE_NAME = \'' . $RULE_TYPE . "'";
                }
                if ($TYPE == 'clinical') {
                    $query .= ', NEWS_CLINICAL_TRIAL_MAP MPR' . $NSTEP . ', CLINICAL_TRIAL PR' . $NSTEP;
                    $WH_CLAUSE .= ' AND N.NEWS_ID=MPR' . $NSTEP . '.NEWS_ID AND PR' . $NSTEP . '.CLINICAL_trial_ID = MPR' . $NSTEP . '.CLINICAL_trial_ID AND PR' . $NSTEP . '.trial_id=\'' . $RULE_TYPE . "'";
                }
               
            }
        }
    }
    $query .= $WH_CLAUSE;

    return runQuery($query)[0];
}

function getPubliFromNews($PARAMS, $FILTERS = array())
{
    $query = "SELECT sub.NEWS_ID as RID FROM (
		SELECT ROW_NUMBER() OVER(ORDER BY N.NEWS_RELEASE_DATE DESC, N.NEWS_ID ASC) R, N.NEWS_ID FROM NEWS N ";
    $FETCH_RECENT = FALSE;
    $NSTEP = 0;
    $MAX = $PARAMS['MAX'] + 1;
    $MIN = $PARAMS['MIN'] + 1;

    if (isset($PARAMS['RECENT'])) {
        $FETCH_RECENT = TRUE;
    }
    $PARAM_STR = " < " . $MAX . " AND sub.R >= " . $MIN;

    $WH_CLAUSE = $FETCH_RECENT ? " WHERE N.NEWS_RELEASE_DATE < CURRENT_DATE - 30" : " WHERE N.NEWS_RELEASE_DATE IS NOT NULL";

    foreach ($FILTERS as $TYPE => $LIST) {
        foreach ($LIST as $RULE_TYPE) {
            ++$NSTEP;
            if ($TYPE == 'gene') {
                $query .= ', NEWS_GN_MAP MPR' . $NSTEP . ', GN_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND N.NEWS_ID=MPR' . $NSTEP . '.NEWS_ID AND PR' . $NSTEP . '.GN_ENTRY_ID = MPR' . $NSTEP . '.GN_ENTRY_ID AND PR' . $NSTEP . '.GENE_ID=' . $RULE_TYPE;
            }
            if ($TYPE == 'disease') {
                $query .= ', NEWS_DISEASE_MAP MPR' . $NSTEP . ', DISEASE_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND  N.NEWS_ID=MPR' . $NSTEP . '.NEWS_ID AND PR' . $NSTEP . '.DISEASE_ENTRY_ID = MPR' . $NSTEP . '.DISEASE_ENTRY_ID AND PR' . $NSTEP . '.DISEASE_TAG=\'' . $RULE_TYPE . "'";
            }
            if ($TYPE == 'drug') {
                $query .= ', NEWS_DRUG_MAP MPR' . $NSTEP . ', DRUG_ENTRY PR' . $NSTEP;
                $WH_CLAUSE .= ' AND N.NEWS_ID=MPR' . $NSTEP . '.NEWS_ID AND PR' . $NSTEP . '.DRUG_ENTRY_ID = MPR' . $NSTEP . '.DRUG_ENTRY_ID AND PR' . $NSTEP . '.DRUG_ENTRY_ID=' . $RULE_TYPE;
            }
            if ($TYPE == 'source') {
                $query .= ', SOURCE S' . $NSTEP ;
                $WH_CLAUSE .= ' AND S'.$NSTEP.'.SOURCE_ID=N.SOURCE_ID AND  SOURCE_NAME = \'' . $RULE_TYPE . "'";
            }
            if ($TYPE == 'clinical') {
                $query .= ', NEWS_CLINICAL_TRIAL_MAP MPR' . $NSTEP . ', CLINICAL_TRIAL PR' . $NSTEP;
                $WH_CLAUSE .= ' AND N.NEWS_ID=MPR' . $NSTEP . '.NEWS_ID AND PR' . $NSTEP . '.CLINICAL_trial_ID = MPR' . $NSTEP . '.CLINICAL_trial_ID AND PR' . $NSTEP . '.trial_id=\'' . $RULE_TYPE . "'";
            }
        }
    }
    $query .=  $WH_CLAUSE . ") sub WHERE sub.R" . $PARAM_STR;
    $res = runQuery($query);

    $data = array();
    foreach ($res as $L) {
        $data[] = $L['RID'];
    }
    return $data;
}




function getNewsByTitle($NEWSTITLE)
{

    $query = "SELECT NEWS_CONTENT FROM NEWS WHERE NEWS_TITLE='$NEWSTITLE'";
    $res = runQuery($query);
    $data = array();
    foreach ($res as $L) {
        $data[] = $L['NEWS_CONTENT'];
    }
    return $data;
}
function getNewsByHash($HASH,$WITH_DELTA=false,$WITH_INFO=false)
{

    $query = "SELECT NEWS_CONTENT,NEWS_ADDED_DATE,SOURCE_NAME,NEWS_TITLE,NEWS_ID,".(($WITH_DELTA)?'NEWS_DELTA,':'')." news_hash md5_HASH,
     last_name,first_name,business_title,email
      FROM NEWS N LEFT JOIN  WEB_USER WU ON  WU.web_user_Id = n.user_id , SOURCE S
       WHERE  S.SOURCE_ID=N.SOURCE_ID AND news_hash='$HASH'";
    
    $res = runQuery($query);


    $DATA = array();
    $NEWS_ID = '';
    foreach ($res as $L) {
        $DATA['MD5'] = $L['MD5_HASH'];
        $DATA['NEWS_TITLE'] = $L['NEWS_TITLE'];
        $DATA['NEWS_DATE'] = $L['NEWS_ADDED_DATE'];
        $DATA['SOURCE_NAME'] = $L['SOURCE_NAME'];
        $DATA['CONTENT'][] = $L['NEWS_CONTENT'];
        $DATA['AUTHOR']=array($L['LAST_NAME'],$L['FIRST_NAME'],$L['BUSINESS_TITLE'],$L['EMAIL']);
        if ($WITH_DELTA)$DATA['DELTA']=$L['NEWS_DELTA'];
        $NEWS_ID = $L['NEWS_ID'];
    }
    if ($NEWS_ID == '') return $DATA;

    $res=runQuery("SELECT count(*) co FROM web_user_stat where page LIKE '%NEWS_CONTENT%".$HASH."%'");
    $DATA['VIEWS']=$res[0]['CO'];

    $res = runQuery(
        "SELECT 
        COUNT(DISTINCT DISEASE_ENTRY_ID) CO
        FROM 
             NEWS_DISEASE_MAP  PD             
        WHERE  NEWS_ID=" . $NEWS_ID
    );

    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA['TAGS']['DISEASE'] = $line['CO'];
    }

    $res = runQuery("SELECT COUNT(GN_ENTRY_ID) CO FROM NEWS_GN_MAP PRM
    WHERE NEWS_ID=" . $NEWS_ID);
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA['TAGS']['GENE'] = $line['CO'];
    }

    $res = runQuery("SELECT COUNT(DRUG_ENTRY_ID) CO FROM NEWS_DRUG_MAP
    WHERE 
     NEWS_ID =" . $NEWS_ID);
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA['TAGS']['DRUG'] = $line['CO'];
    }
    $res = runQuery("SELECT COUNT(COMPANY_ENTRY_ID) CO FROM NEWS_COMPANY_MAP
    WHERE 
     NEWS_ID =" . $NEWS_ID);
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA['TAGS']['COMPANY'] = $line['CO'];
    }
    $res = runQuery("SELECT COUNT(*) CO FROM NEWS_CLINICAL_TRIAL_MAP P
    WHERE  NEWS_ID=" . $NEWS_ID);
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA['TAGS']['CLINICAL'] = $line['CO'];
    }
    $res = runQuery("SELECT COUNT(*) CO FROM NEWS_NEWS_MAP P
    WHERE  NEWS_ID=" . $NEWS_ID.' OR NEWS_PARENT_ID = '.$NEWS_ID);
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA['TAGS']['NEWS'] = $line['CO'];
    }

    $res = runQuery("SELECT NEWS_ID, document_name, document_description, document_hash, document_version FROM NEWS_DOCUMENT 
    WHERE   NEWS_ID  =" . $NEWS_ID);
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) $DATA['DOCS'][] = $line;
    }

    if ($WITH_INFO)
{
    $ALLOWED=array('DRUG','CLINVAR','PATHWAY','GENE','DISEASE','PROT_FEAT','ASSAY','CELL','TISSUE','EVIDENCE','CLINICAL','COMPANY');
    
        foreach ($ALLOWED as $TYPE)$DATA['TAGS'][$TYPE]=private_getNewsInfo($NEWS_ID,$TYPE);
        
}
    return $DATA;
}


function getGeneDescription($GN_ENTRY_ID)
{
   
    $DATA = array();
   
    $res2 = runQuery('SELECT * FROM GN_INFO DI,SOURCE S WHERE S.SOURCE_ID = DI.SOURCE_ID AND GN_ENTRY_ID=' . $GN_ENTRY_ID);
    foreach ($res2 as $line) {
        $DATA[$line['SOURCE_NAME']][$line['INFO_TYPE']] = ($line['INFO_TEXT']);
    }


//     $res=runQuery("SELECT news_hash, document_hash,mime_type, document_name ,gn_entry_id,source_name, document_version
//     FROM news_document ned,news_gn_map nd, news n, source s 
//     where ned.news_id = nd.news_id AND nd.news_id = n.news_id 
//     AND n.source_id = s.source_id 
//     AND Source_name  IN ('Liver Tox','Gene Reviews') 
   
//    AND gn_entry_id =  " . $GN_ENTRY_ID.' ORDER BY news_hash, document_Version ASC' );
//     foreach ($res as $line)$DATA['DOCS'][$line['NEWS_HASH']][$line['MIME_TYPE']]=$line;



    return $DATA;
    
}

function getDiseaseInfo($DISEASE_ENTRY_ID)
{
   
    $res = runQuery('SELECT DISEASE_INFO_ID FROM DISEASE_INFO DI WHERE DISEASE_ENTRY_ID=' . $DISEASE_ENTRY_ID);
    $DATA = array();
    foreach ($res as $l) {
        $res2 = runQuery('SELECT * FROM DISEASE_INFO DI,SOURCE S WHERE S.SOURCE_ID = DI.SOURCE_ID AND DISEASE_INFO_ID=' . $l['DISEASE_INFO_ID']);
        foreach ($res2 as $line) {
            $DATA[$line['SOURCE_NAME']][$line['INFO_TYPE']] = ($line['INFO_TEXT']);
        }
    }

    // $res = runQuery('select ce.cell_entry_Id, cell_acc,cell_name,cell_type,cell_donor_sex,cell_donor_age,cell_version,date_updated, cell_tissue_name,ae.anatomy_entry_Id, anatomy_name, anatomy_definition,anatomy_tag FROM disease_entry de, cell_disease dgm, cell_entry ce LEFT JOIN cell_tissue ct ON CT.cell_tissue_Id = ce.cell_tissue_id LEFT JOIN anatomy_entry ae ON ae.anatomy_entry_Id = ct.anatomy_entry_id where ce.cell_entry_Id = dgm.cell_entry_id AND dgm.disease_entry_id = de.disease_entry_id AND de.disease_entry_Id=' . $DISEASE_ENTRY_ID);
    // foreach ($res as $line)
    //     $DATA['CELL_LINE'][] = $line;

   

    $res = runQuery("select n.news_id, news_title,news_content,news_release_date,last_name,first_name,email,source_name FROM  news_disease_map ndm, news n LEFT JOIN web_user w ON w.web_user_Id = n.user_id,source s where s.source_id =n.source_id AND  n.news_id = ndm.news_id AND ndm.disease_entry_Id = " . $DISEASE_ENTRY_ID . " ORDER BY news_added_date DESC LIMIT 5");
    foreach ($res as $line) {
        $line['hash_news'] = md5($line['NEWS_TITLE'] . '|' . $line['SOURCE_NAME'] . '|' . $line['NEWS_RELEASE_DATE']);
        $DATA['NEWS'][$line['NEWS_ID']] = $line;
    }


    $res=runQuery("SELECT news_hash, document_hash,mime_type, document_name ,disease_entry_id,source_name, document_version
    FROM news_document ned,news_disease_map nd, news n, source s 
    where ned.news_id = nd.news_id AND nd.news_id = n.news_id 
    AND n.source_id = s.source_id 
    AND Source_name  IN ('Liver Tox','Gene Reviews') 
    AND mime_type='application/pdf'
   AND disease_entry_id =  " . $DISEASE_ENTRY_ID );
    foreach ($res as $line)$DATA['DOCS'][]=$line;



    return $DATA;
}

function getDiseasePubStat($DISEASE_ENTRY_ID,$WITH_CHILD=false)
{
    $LIST=array($DISEASE_ENTRY_ID);
    if ($WITH_CHILD)
    {
         $TMP=getAllChildDisease($DISEASE_ENTRY_ID, true);
         //print_r($TMP);
    foreach ($TMP as &$E)$LIST[]=$E['DISEASE_ENTRY_ID'];
    }
   
  //  echo count($LIST);exit;
    $DATA=array();
    $res = runQuery("select count(DISTINCT pe.pmid_entry_id) CO, 
    EXTRACT(YEAR FROM publication_date) year_pub
     FROM pmid_disease_map pd, pmid_entry pe 
     where pe.pmid_entry_id = pd.pmid_entry_Id 
     AND  disease_entry_Id IN (" . implode(',',$LIST) . ") GROUP BY EXTRACT(YEAR FROM publication_date) ");
    foreach ($res as $line)
        $DATA['PUB_DATE'][$line['YEAR_PUB']] = $line['CO'];
return $DATA;
}


function getAllChildDisease($DISEASE_ENTRY_ID, $WITH_ITSELF=false)
{
    $DATA = array();
    $query = "SELECT DISTINCT EE.DISEASE_ENTRY_ID, EE.DISEASE_TAG, EE.DISEASE_NAME, EE.DISEASE_DEFINITION, EF.DISEASE_LEVEL, EF.DISEASE_LEVEL_RIGHT,EF.DISEASE_LEVEL_LEFT
	FROM DISEASE_ENTRY EE, DISEASE_HIERARCHY EF, DISEASE_HIERARCHY EPH, DISEASE_ENTRY EP
    WHERE EP.DISEASE_ENTRY_ID = EPH.DISEASE_ENTRY_ID
    AND EF.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
    AND EF.DISEASE_LEVEL_LEFT >".(($WITH_ITSELF)?'=':'')."EPH.DISEASE_LEVEL_LEFT
    AND EF.DISEASE_LEVEL_RIGHT <".(($WITH_ITSELF)?'=':'')."EPH.DISEASE_LEVEL_RIGHT
    AND EP.DISEASE_ENTRY_ID = " . $DISEASE_ENTRY_ID;
    $query .= ' ORDER BY EE.DISEASE_NAME ASC';
    $res = runQuery($query);
    foreach ($res as $line) $DATA[$line['DISEASE_TAG']] = $line;
    return $DATA;
}

function getChildDisease($DISEASE_ENTRY_ID, $DISEASE_LEVEL = -1)
{
    $DATA = array();
    $query = "SELECT DISTINCT EE.DISEASE_ENTRY_ID, EE.DISEASE_TAG, EE.DISEASE_NAME, EE.DISEASE_DEFINITION, EF.DISEASE_LEVEL, EF.DISEASE_LEVEL_RIGHT,EF.DISEASE_LEVEL_LEFT
	FROM DISEASE_ENTRY EE, DISEASE_HIERARCHY EF, DISEASE_HIERARCHY EPH, DISEASE_ENTRY EP
    WHERE EP.DISEASE_ENTRY_ID = EPH.DISEASE_ENTRY_ID
    AND EF.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
    AND EF.DISEASE_LEVEL_LEFT >=EPH.DISEASE_LEVEL_LEFT
    AND EF.DISEASE_LEVEL_RIGHT <= EPH.DISEASE_LEVEL_RIGHT
    AND EF.DISEASE_LEVEL=EPH.DISEASE_LEVEL+1
    AND EP.DISEASE_ENTRY_ID = " . $DISEASE_ENTRY_ID;
    if ($DISEASE_LEVEL != -1) $query .= ' AND EPH.DISEASE_LEVEL=' . $DISEASE_LEVEL;
    $query .= ' ORDER BY EE.DISEASE_NAME ASC';
    $res = runQuery($query);
    foreach ($res as $line) $DATA[$line['DISEASE_TAG']] = $line;
    return $DATA;
}

function getDiseaseHierarchy($DISEASE_ENTRY_ID)
{
    $DATA = array();
    $query = "SELECT DISTINCT EE.DISEASE_ENTRY_ID, EE.DISEASE_NAME, EE.DISEASE_TAG, EF.DISEASE_LEVEL, EF.DISEASE_LEVEL_LEFT, EF.DISEASE_LEVEL_RIGHT
	FROM DISEASE_ENTRY EE, DISEASE_HIERARCHY EF, DISEASE_HIERARCHY EPH, DISEASE_ENTRY EP
	WHERE EP.DISEASE_ENTRY_ID = EPH.DISEASE_ENTRY_ID 
    AND EF.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
	AND EF.DISEASE_LEVEL_LEFT <=EPH.DISEASE_LEVEL_LEFT 
	AND EF.DISEASE_LEVEL_RIGHT >= EPH.DISEASE_LEVEL_RIGHT 
	AND EF.DISEASE_LEVEL<=EPH.DISEASE_LEVEL
	AND EP.DISEASE_ENTRY_ID = " . $DISEASE_ENTRY_ID . ' ORDER BY EF.DISEASE_LEVEL ASC';
    $N = 0;
    $res = runQuery($query);
    foreach ($res as $line) {
        foreach ($DATA as &$T) {
            if ($line['DISEASE_LEVEL'] != $T['DISEASE_LEVEL'] + 1) continue;
            if ($T['DISEASE_LEVEL_LEFT'] > $line['DISEASE_LEVEL_LEFT']) continue;
            if ($T['DISEASE_LEVEL_RIGHT'] < $line['DISEASE_LEVEL_RIGHT']) continue;
            $T['CHILD'][] = $line['DISEASE_TAG'];
            $line['PARENT'] = $T['DISEASE_TAG'];
        }
        ++$N;
        $line['DISEASE_TAG'] .= '_' . $N;
        $DATA[] = $line;
    }


    $query = "SELECT DISTINCT EE.DISEASE_TAG, EE.DISEASE_NAME, EE.DISEASE_DEFINITION, EF.DISEASE_LEVEL, EF.DISEASE_LEVEL_RIGHT,EF.DISEASE_LEVEL_LEFT
	FROM DISEASE_ENTRY EE, DISEASE_HIERARCHY EF, DISEASE_HIERARCHY EPH, DISEASE_ENTRY EP
    WHERE EP.DISEASE_ENTRY_ID = EPH.DISEASE_ENTRY_ID AND EF.DISEASE_ENTRY_ID = EE.DISEASE_ENTRY_ID
    AND EF.DISEASE_LEVEL_LEFT >EPH.DISEASE_LEVEL_LEFT
    AND EF.DISEASE_LEVEL_RIGHT < EPH.DISEASE_LEVEL_RIGHT
    AND EP.DISEASE_ENTRY_ID = " . $DISEASE_ENTRY_ID;
    $query .= ' ORDER BY EE.DISEASE_NAME ASC';
    $res = runQuery($query);
    foreach ($res as $line) {
        foreach ($DATA as &$T) {
            if ($line['DISEASE_LEVEL'] != $T['DISEASE_LEVEL'] + 1) continue;
            if ($T['DISEASE_LEVEL_LEFT'] > $line['DISEASE_LEVEL_LEFT']) continue;
            if ($T['DISEASE_LEVEL_RIGHT'] < $line['DISEASE_LEVEL_RIGHT']) continue;
            $T['CHILD'][] = $line['DISEASE_TAG'];
            $line['PARENT'] = $T['DISEASE_TAG'];
        }
        ++$N;
        $line['DISEASE_TAG'] .= '_' . $N;
        $DATA[] = $line;
    }
    return $DATA;
}

function getPubliFromDiseaseGene($DISEASE_ENTRY_ID, $GN_ENTRY_ID, $PARAMS)
{

    $query = "SELECT * FROM (
		SELECT ROW_NUMBER() OVER( ORDER BY PDG.OT_SCORE DESC, PE.publication_date DESC,PE.PMID ASC) R, PE.PMID, OT_SCORE, PMID_DISEASE_GENE_ID  
        FROM PMID_DISEASE_GENE PDG, PMID_ENTRY PE WHERE PE.PMID_ENTRY_ID = PDG.PMID_ENTRY_ID AND GN_ENTRY_ID = " . $GN_ENTRY_ID . " AND DISEASE_ENTRY_ID=" . $DISEASE_ENTRY_ID . ") P
		WHERE R<=" . $PARAMS['MAX'] . " AND R>=" . $PARAMS['MIN'];
    $DATA = array();

    $res = runQuery($query);
    foreach ($res as $line) {
        $DATA[$line['PMID_DISEASE_GENE_ID']] = $line;
    }
    if (count($DATA) == 0) return $DATA;
    $res = runQuery("SELECT DISTINCT PMID_DISEASE_GENE_ID, SECTION,TEXT_CONTENT FROM PMID_DISEASE_GENE_TXT WHERE PMID_DISEASE_GENE_ID IN (" . implode(',', array_keys($DATA)) . ')');
    foreach ($res as $line)
        $DATA[$line['PMID_DISEASE_GENE_ID']]['TXT'][$line['SECTION']][] = $line['TEXT_CONTENT'];

    return $DATA;
}

function getDiseasePMIDStat($DISEASE_ENTRY_ID, $GN_ENTRY_ID = -1)
{


    $query = "
        SELECT SYMBOL, GENE_ID, FULL_NAME, ALL_COUNT, YEAR_COUNT, SPEED30, ACCEL30,SPEED60, ACCEL60 
        FROM DISEASE_GENE_ACC D,GN_ENTRY DE 
            WHERE DE.GN_ENTRY_ID=D.GN_ENTRY_ID 
            AND DISEASE_ENTRY_ID=$DISEASE_ENTRY_ID";
    if ($GN_ENTRY_ID != -1) $query .= " AND DE.GN_ENTRY_ID=$GN_ENTRY_ID";

    $res = runQuery($query);
    return $res;
}

function getDiseasePMIDStatGene($GN_ENTRY_ID)
{
    $query = 'SELECT DISEASE_NAME, DISEASE_TAG, ALL_COUNT,YEAR_COUNT,SPEED30,ACCEL30,SPEED60,ACCEL60 
        FROM DISEASE_GENE_ACC D,DISEASE_ENTRY DE WHERE DE.DISEASE_ENTRY_ID = D.DISEASE_ENTRY_ID AND GN_ENTRY_ID=' . $GN_ENTRY_ID;

    $res = runQuery($query);
    return $res;
}


function searchMutations($GN_ENTRY_ID, $FILTERS)
{

    //$FILTERS=array('CLINICAL'=>array(),'MUT_TYPE'=>array(),'FREQ_OV'=>array(),'FREQ_ST'=>array(),'PAGE'=>0,'N_PER_PAGE'=>10);

    $LIST_SELECTED = array();

    $IMPACT_TYPE_ID = array();
    if (count($FILTERS['IMPACT']) > 0) {
        $LIST_ALLOWED = array(
            "'coding_sequence_variant'",
            "'intron_variant'",
            "'upstream_transcript_variant'",
            "'5_prime_UTR_variant'",
            "'3_prime_UTR_variant'",
            "'downstream_transcript_variant'",
            "'splice_donor_variant'",
            "'terminator_codon_variant'",
            "'genic_downstream_transcript_variant'",
            "'genic_upstream_transcript_variant'"
        );
        $res = runQuery("SELECT SO_NAME,SO_ENTRY_ID FROM SO_ENTRY WHERE SO_NAME IN (" . implode(',', $LIST_ALLOWED) . ')');
        foreach ($res as $line) if (in_array($line['SO_NAME'], $FILTERS['IMPACT'])) $IMPACT_TYPE_ID[] = $line['SO_ENTRY_ID'];
    }

    $R_MUT_TYPE = array();
    $L_MUT_TYPE = array();
    if (count($FILTERS['MUT_TYPE']) > 0) {
        foreach ($FILTERS['MUT_TYPE'] as $K) {
            $tab = explode("_", $K);
            $R_MUT_TYPE[$tab[0]] = -1;
            $L_MUT_TYPE[$tab[1]] = -1;
            $ALL["'" . $tab[0] . "'"] = -1;
            $ALL["'" . $tab[1] . "'"] = -1;
        }
        $res = runQuery("SELECT variant_allele_id, variant_seq FROM variant_allele where variant_seq IN (" . implode(',', array_keys($ALL)) . ')');
        foreach ($res as $line) {
            foreach ($R_MUT_TYPE as $R => &$ID) if ($R == $line['VARIANT_SEQ']) $ID = $line['VARIANT_ALLELE_ID'];
            foreach ($L_MUT_TYPE as $L => &$ID) if ($L == $line['VARIANT_SEQ']) $ID = $line['VARIANT_ALLELE_ID'];
        }
    }


    $ALL_TRANSCRIPTS = array();
    $TR_IDS = array();

    $TRANSCRIPTS_DATA = getListTranscripts($GN_ENTRY_ID);

    foreach ($TRANSCRIPTS_DATA['TRANSCRIPTS'] as $T) {
        $ALL_TRANSCRIPTS[] = $T['TRANSCRIPT_ID'];
        if (count($FILTERS['TRANSCRIPTS']) != 0) {
            $NAME = $T['TRANSCRIPT_NAME'];
            if ($T['TRANSCRIPT_VERSION'] != '') $NAME .= '.' . $T['TRANSCRIPT_VERSION'];
            if (in_array($NAME, $FILTERS['TRANSCRIPTS'])) $TR_IDS[] = $T['TRANSCRIPT_ID'];
        }
    }




    $LOC_ID = array();
    if (count($FILTERS['LOCATION']) > 0) {
        $res = runQuery("SELECT TRANSCRIPT_POS_TYPE_ID, TRANSCRIPT_POS_TYPE FROM TRANSCRIPT_POS_TYPE");

        foreach ($res as $line) if (in_array($line['TRANSCRIPT_POS_TYPE'], $FILTERS['LOCATION'])) $LOC_ID[] = $line['TRANSCRIPT_POS_TYPE_ID'];
    }


    // select VC.VARIANT_CHANGE_ID FROM variant_transcript_map VT, VARIANT_CHANGE VC, CHR_SEQ_POS CSP, VARIANT_POSITION VP, VARIANT_ENTRY VE, (SELECT MIN(START_POS) as SP,MAX(END_POS) as EP,CHR_SEQ_ID 
    // FROM  GENE_SEQ GS WHERE GN_ENTRY_ID=76973 GROUP BY GN_ENTRY_ID,CHR_SEQ_ID ) P WHERE VP.VARIANT_POSITION_ID = VC.VARIANT_POSITION_ID AND VP.VARIANT_ENTRY_ID = VE.VARIANT_ENTRY_ID
    //  AND Vp.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID AND CSP.CHR_SEQ_ID=P.CHR_SEQ_ID AND CSP.CHR_POS>=SP AND CSP.CHR_POS <=EP AND VT.VARIANT_CHANGE_ID = VC.VARIANT_CHANGE_ID AND VT.TRANSCRIPT_ID IN (103481);


    $query = 'select  RSID,REF_ALL,ALT_ALL, VC.VARIANT_CHANGE_ID FROM variant_transcript_map VT';
    if (count($LOC_ID)) $query .= ' LEFT JOIN TRANSCRIPT_POS TP ON TP.TRANSCRIPT_POS_ID=VT.TRANSCRIPT_POS_ID';
    $query .= ', VARIANT_CHANGE VC, CHR_SEQ_POS CSP, VARIANT_POSITION VP, VARIANT_ENTRY VE,
		   (SELECT MIN(START_POS) as SP,MAX(END_POS) as EP,CHR_SEQ_ID FROM  GENE_SEQ GS
		   WHERE    GN_ENTRY_ID=' . $GN_ENTRY_ID . '
		   GROUP BY GN_ENTRY_ID,CHR_SEQ_ID ) P WHERE
		   VP.VARIANT_POSITION_ID = VC.VARIANT_POSITION_ID AND 
		   VP.VARIANT_ENTRY_ID = VE.VARIANT_ENTRY_ID AND 
		   Vp.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID AND  REF_ALL!=ALT_ALL AND
		   CSP.CHR_SEQ_ID=P.CHR_SEQ_ID AND CSP.CHR_POS>=SP 
		   AND CSP.CHR_POS <=EP AND
		   VT.VARIANT_CHANGE_ID = VC.VARIANT_CHANGE_ID ';
    if (count($TR_IDS) > 0) $query .= ' AND VT.TRANSCRIPT_ID IN (' . implode(',', $TR_IDS) . ') ';
    else $query .= ' AND VT.TRANSCRIPT_Id in (' . implode(',', $ALL_TRANSCRIPTS) . ') ';
    if ($R_MUT_TYPE != array()) $query .= ' AND REF_ALL IN (' . implode(',', $R_MUT_TYPE) . ') ';
    if ($L_MUT_TYPE != array()) $query .= ' AND ALT_ALL IN (' . implode(',', $L_MUT_TYPE) . ') ';

    if (count($IMPACT_TYPE_ID) > 0) $query .= ' AND SO_ENTRY_ID IN (' . implode(',', $IMPACT_TYPE_ID) . ') ';
    if (count($LOC_ID)) $query .= ' AND SEQ_POS_TYPE_ID IN (' . implode(',', $LOC_ID) . ') ';

    $res = runQuery($query);

    $LIST_FOUND = array();

    foreach ($res as $line) {
        $LIST_FOUND[$line['VARIANT_CHANGE_ID']] = $line;
    }
    echo count($LIST_FOUND) . "\n";
    if ($LIST_FOUND != array()) {
        $query = 'SELECT  VARIANT_CHANGE_ID,SUM(CAST (REF_COUNT as DOUBLE PRECISION)) as r_sum, SUM(CAST(ALT_COUNT as DOUBLE PRECISION)) as a_sum FROM VARIANT_FREQUENCY WHERE ALT_COUNT>0 AND VARIANT_CHANGE_ID  IN (' . implode(',', array_keys($LIST_FOUND)) . ') GROUP BY VARIANT_CHANGE_ID';
        $res = runQuery($query);
        echo '<pre>';
        foreach ($res as $line) {
            $ENTRY = &$LIST_FOUND[$line['VARIANT_CHANGE_ID']];
            $FQ = round((float)($line['R_SUM'] / $line['A_SUM'] * 100), 3);
            $LIST_FOUND[$line['VARIANT_CHANGE_ID']]['OV_FREQ'] = $FQ . '%';

            if ($FQ < $FILTERS['FREQ_OV'][0] || $FQ > $FILTERS['FREQ_OV'][1]) {
                unset($LIST_FOUND[$line['VARIANT_CHANGE_ID']]);
                continue;
            }
        }
    }

    if ($LIST_FOUND != array()) {
        $query = 'SELECT  VARIANT_CHANGE_ID,MAX(CAST (REF_COUNT as DOUBLE PRECISION)/CAST(ALT_COUNT as DOUBLE PRECISION)) as FQ FROM VARIANT_FREQUENCY WHERE ALT_COUNT>0 AND VARIANT_CHANGE_ID  IN (' . implode(',', array_keys($LIST_FOUND)) . ') GROUP BY VARIANT_CHANGE_ID';

        $res = runQuery($query);

        foreach ($res as $line) {

            $FQ = round((float)$line['FQ'] * 100, 3);
            $LIST_FOUND[$line['VARIANT_CHANGE_ID']]['ST_FREQ'] = $FQ . '%';

            if ($FQ < $FILTERS['FREQ_ST'][0] || $FQ > $FILTERS['FREQ_ST'][1]) {
                unset($LIST_FOUND[$line['VARIANT_CHANGE_ID']]);
                continue;
            }
        }
    }


    $LT = array();
    foreach ($LIST_FOUND as $K => &$ENTRY) {
        if (!isset($ENTRY['ST_FREQ']) || !isset($ENTRY['OV_FREQ'])) {
            unset($LIST_FOUND[$K]);
            continue;
        }
        $LT[$ENTRY['REF_ALL']] = -1;
        $LT[$ENTRY['ALT_ALL']] = -1;
    }
    if (count($LT)) {
        $query = 'SELECT variant_seq, variant_allele_id FROM variant_allele where variant_allele_id IN (' . implode(',', array_keys($LT)) . ')';
        $res = runQuery($query);

        foreach ($res as $line)
            foreach ($LIST_FOUND as &$ENTRY) {
                if ($ENTRY['REF_ALL'] == $line['VARIANT_ALLELE_ID'])    $ENTRY['REF_ALL'] = $line['VARIANT_SEQ'];
                if ($ENTRY['ALT_ALL'] == $line['VARIANT_ALLELE_ID'])    $ENTRY['ALT_ALL'] = $line['VARIANT_SEQ'];
            }
    }

    return $LIST_FOUND;
}

function getRSIDFromChrPos($CHR_SEQ_POS_ID)
{
    $res=runQuery("SELECT DISTINCT RSID FROM variant_entry ve, variant_position vp where ve.variant_entry_id =vp.variant_entry_Id AND chr_seq_pos_id = ".$CHR_SEQ_POS_ID);
    $data=array();
    foreach ($res as $line)$data[]=$line['RSID'];
    return $data;
}

function getClinvarByChrPos($CHR_SEQ_POS_ID)
{
    $query=' SELECT * FROM clinical_variant_submission cvs
    LEFT JOIN clinical_Variant_gn_map cvg ON cvg.clinvar_submission_id = cvs.clinvar_submission_id
    LEFT JOIN clinical_Variant_disease_map cvd ON cvd.clinvar_submission_id = cvs.clinvar_submission_id
    LEFT JOIN clinical_significance cs ON cs.clin_sign_id = cvs.clin_sign_id
    
    LEFT JOIN gn_entry g ON cvg.gn_entry_Id = g.gn_entry_id
    LEFT JOIN disease_entry ds On ds.disease_entry_Id = cvd.disease_entry_id
    LEFT JOIN clinical_Variant_entry cve ON cve.clinvar_entry_id = cvs.clinvar_entry_id
    
    LEFT JOIN clinical_Variant_map cvm ON cvm.clinvar_entry_id = cve.clinvar_entry_id
    LEFT JOIN variant_position vp ON vp.variant_entry_id = cvm.variant_entry_id
    WHERE chr_seq_pos_Id='. $CHR_SEQ_POS_ID;
    $res=runQuery($query);
    $data=array();
    $MAP=array();
    foreach ($res as $line)
    {
        $MAP[$line['CLINVAR_SUBMISSION_ID']]=$line['CLINICAL_VARIANT_NAME'];
        $data[$line['CLINICAL_VARIANT_NAME']][$line['CLINVAR_SUBMISSION_ID']]['RECORD']=$line;
    }
    
    if ($data!=array() && array_filter(array_keys($MAP))!=array())
    {
        $query="SELECT pmid,clinvar_submission_id FROM clinical_variant_pmid_map c, pmid_entry p where p.pmid_entry_id = c.pmid_entry_id
        AND clinvar_submission_id IN (".implode(',',array_filter(array_keys($MAP))).')';
        
        $res=runQuery($query);
        
        foreach ($res as $line)
        {
            $data[$MAP[$line['CLINVAR_SUBMISSION_ID']]][$line['CLINVAR_SUBMISSION_ID']]['PMID'][]=$line['PMID'];
        }
    }
    return $data;
}

function getMutationInfo($RSID)
{
    $ENTRY = array();


    $query = 'SELECT VE.VARIANT_ENTRY_ID, RSID, DATE_CREATED,DATE_UPDATED
     FROM VARIANT_ENTRY VE
     WHERE RSID = ' . $RSID;
    $res = runQuery($query);
    if (count($res) == 0) return $ENTRY;

    $ENTRY[$RSID] = $res[0];
    $ENTRY[$RSID]['PMID'] = array();;


    $query = 'SELECT VARIANT_POSITION_ID, VP.CHR_SEQ_POS_ID, C.CHR_ID,CHR_NUM, CS.CHR_SEQ_ID,
    REFSEQ_NAME,REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION, SEQ_ROLE,CHR_SEQ_NAME,
     ASSEMBLY_UNIT,NUCL,CHR_POS As POSITION,SCIENTIFIC_NAME,TAX_ID,VARIANT_SEQ as REF_ALL
    FROM  VARIANT_POSITION VP, CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP,TAXON T, VARIANT_ALLELE VA
    WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
    AND VA.VARIANT_ALLELE_ID = REF_ALL
    AND CSP.CHR_SEQ_POS_ID = VP.CHR_SEQ_POS_ID
    AND T.TAXON_ID = C.TAXON_ID
    AND VARIANT_ENTRY_ID = ' . $ENTRY[$RSID]['VARIANT_ENTRY_ID'];
    $res = runQuery($query);

    $ENTRY[$RSID]['VARIANT'] = array();

    foreach ($res as $line) $ENTRY[$RSID]['VARIANT'][$line['VARIANT_POSITION_ID']] = $line;



   $query=' SELECT * FROM clinical_variant_submission cvs
    LEFT JOIN clinical_Variant_gn_map cvg ON cvg.clinvar_submission_id = cvs.clinvar_submission_id
    LEFT JOIN clinical_Variant_disease_map cvd ON cvd.clinvar_submission_id = cvs.clinvar_submission_id
    LEFT JOIN clinical_significance cs ON cs.clin_sign_id = cvs.clin_sign_id
    
    LEFT JOIN gn_entry g ON cvg.gn_entry_Id = g.gn_entry_id
    LEFT JOIN disease_entry ds On ds.disease_entry_Id = cvd.disease_entry_id
    LEFT JOIN clinical_Variant_entry cve ON cve.clinvar_entry_id = cvs.clinvar_entry_id
    
    LEFT JOIN clinical_Variant_map cvm ON cvm.clinvar_entry_id = cve.clinvar_entry_id
    WHERE variant_entry_id='. $ENTRY[$RSID]['VARIANT_ENTRY_ID'];
    $res=runQuery($query);
    $ENTRY[$RSID]['CLINICAL']=array();
    $MAP=array();
    foreach ($res as $line)
    {
        $MAP[$line['CLINVAR_SUBMISSION_ID']]=$line['CLINICAL_VARIANT_NAME'];
        $ENTRY[$RSID]['CLINICAL'][$line['CLINICAL_VARIANT_NAME']][$line['CLINVAR_SUBMISSION_ID']]['RECORD']=$line;
    }
    
    if ($ENTRY[$RSID]['CLINICAL']!=array() && array_filter(array_keys($MAP))!=array())
    {
        $query="SELECT pmid,clinvar_submission_id FROM clinical_variant_pmid_map c, pmid_entry p where p.pmid_entry_id = c.pmid_entry_id
        AND clinvar_submission_id IN (".implode(',',array_filter(array_keys($MAP))).')';
        
        $res=runQuery($query);
        
        foreach ($res as $line)
        {
            $ENTRY[$RSID]['CLINICAL'][$MAP[$line['CLINVAR_SUBMISSION_ID']]][$line['CLINVAR_SUBMISSION_ID']]['PMID'][]=$line['PMID'];
        }
    }

    $query = "SELECT variant_change_id, variant_position_id, variant_seq as alt_all, variant_name, so_name, so_description
	FROM variant_change VC, variant_allele va, VARIANT_TYPE VT
	LEFT JOIN SO_ENTRY SO ON SO.SO_ENTRY_ID = VT.SO_ENTRY_ID
	where vt.variant_Type_Id = vc.variant_type_id
    AND va.variant_allele_id = alt_all
	AND variant_position_id IN (" . implode(',', array_keys($ENTRY[$RSID]['VARIANT'])) . ')';

    $res = array();
    $res = runQuery($query);
    $MAP_POS = array();
    foreach ($res as $line) {
        $MAP_POS[$line['VARIANT_CHANGE_ID']] = $line['VARIANT_POSITION_ID'];
        $ENTRY[$RSID]['VARIANT'][$line['VARIANT_POSITION_ID']]['CHANGE'][$line['VARIANT_CHANGE_ID']] = $line;
    }

    $query = 'SELECT VARIANT_CHANGE_ID, REF_COUNT,ALT_COUNT,VARIANT_FREQ_STUDY_NAME,DESCRIPTION ,SHORT_NAME
    FROM VARIANT_FREQUENCY VF, VARIANT_FREQ_STUDY VFS
     WHERE vf.variant_freq_study_id=vfs.variant_freq_study_id 
     AND VF.VARIANT_CHANGE_ID IN (' . implode(',', array_keys($MAP_POS)) . ')';
    $res = array();
    $res = runQuery($query);
    foreach ($res as $line) {
        $ENTRY[$RSID]['VARIANT'][$MAP_POS[$line['VARIANT_CHANGE_ID']]]['CHANGE'][$line['VARIANT_CHANGE_ID']]['FREQ'][$line['VARIANT_FREQ_STUDY_NAME']] = array('SHORT_NAME' => $line['SHORT_NAME'], 'REF_COUNT' => $line['REF_COUNT'], 'ALT_COUNT' => $line['ALT_COUNT']);
        $ENTRY['STUDY_DESC'][$line['VARIANT_FREQ_STUDY_NAME']] = $line['DESCRIPTION'];
    }

    $query = 'SELECT PMID FROM VARIANT_PMID_MAP VPM, pmid_entry PE WHERE VPM.PMID_ENTRY_ID = PE.PMID_ENTRY_ID AND VPM.VARIANT_ENTRY_ID = ' . $ENTRY[$RSID]['VARIANT_ENTRY_ID'];
    $res = array();
    $res = runQuery($query);
    foreach ($res as $line) $ENTRY[$RSID]['PMID'][] = $line['PMID'];

    if ($MAP_POS != array()) {
        $query = 'SELECT variant_Transcript_id,variant_change_id,SO_ID, SO_NAME,SO_DESCRIPTION, R_VA.VARIANT_SEQ AS TR_REF_ALL,C_VA.VARIANT_SEQ AS  TR_ALT_ALL, seq_pos,
	        TRANSCRIPT_NAME, TRANSCRIPT_VERSION, T.START_POS,T.END_POS,support_level, 
			gene_seq_name, gene_seq_Version,strand, gs.start_pos as gene_Seq_start_pos, gs.end_pos as gene_Seq_end_pos, strand,
			 symbol, gene_id, full_name
	FROM  VARIANT_TRANSCRIPT_MAP VTM
    LEFT JOIN TRANSCRIPT_POS TP ON TP.TRANSCRIPT_POS_ID = VTM.TRANSCRIPT_POS_ID
    LEFT JOIN VARIANT_ALLELE R_VA ON R_VA.VARIANT_ALLELE_ID = TR_REF_ALL
    LEFT JOIN VARIANT_ALLELE C_VA ON C_VA.VARIANT_ALLELE_ID = TR_ALT_ALL , TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE , SO_ENTRY SO
	WHERE  GE.GN_ENTRY_ID =GS.GN_ENTRY_ID
	AND GS.GENE_SEQ_ID = T.GENE_SEQ_ID 
	AND SO.SO_ENTRY_ID = VTM.SO_ENTRY_ID
	AND T.TRANSCRIPT_ID = VTM.TRANSCRIPT_ID 
	AND VTM.VARIANT_CHANGE_ID IN (' . implode(',', array_keys($MAP_POS)) . ')';

        $res = array();
        $res = runQuery($query);
        $MAP_TR = array();
        foreach ($res as $line) {
            $MAP_TR[$line['VARIANT_TRANSCRIPT_ID']] = $line['VARIANT_CHANGE_ID'];
            $ENTRY[$RSID]['VARIANT'][$MAP_POS[$line['VARIANT_CHANGE_ID']]]['CHANGE'][$line['VARIANT_CHANGE_ID']]['TRANSCRIPT'][$line['VARIANT_TRANSCRIPT_ID']] = $line;
        }

        if ($MAP_TR != array()) {
            $query = 'SELECT variant_protein_id, variant_transcript_id, ps.prot_seq_id, iso_id,  vr.variant_prot_seq as ref_prot_all, vc.variant_prot_seq as comp_prot_all,
vpm.so_entry_id, so_id,psp.position as seq_pos, psp.letter as seq_letter, psp.prot_seq_pos_id
FROM prot_seq ps, variant_protein_map vpm
LEFT JOIN so_entry s ON s.so_entry_id = vpm.so_entry_id
LEFT JOIN variant_prot_allele vr ON vr.variant_prot_allele_id = vpm.prot_ref_all
LEFT JOIN variant_prot_allele vc ON vc.variant_prot_allele_id = vpm.prot_alt_all
LEFT JOIN prot_seq_pos psp ON psp.prot_seq_pos_id = vpm.prot_seq_pos_id
WHERE ps.prot_seq_id = vpm.prot_seq_id 
AND variant_transcript_id IN (' . implode(',', array_keys($MAP_TR)) . ')';
            $res = runQuery($query);

            foreach ($res as $line) {

                $ENTRY[$RSID]['VARIANT'][$MAP_POS[$MAP_TR[$line['VARIANT_TRANSCRIPT_ID']]]]['CHANGE'][$MAP_TR[$line['VARIANT_TRANSCRIPT_ID']]]['TRANSCRIPT'][$line['VARIANT_TRANSCRIPT_ID']]['PROTEIN'][] = $line;
            }
        }

        $res = runQuery("SELECT variant_change_id,prop_value,gwas_descriptor_name,gwas_descriptor_desc,
    phenotype_name,phenotype_tag,n_cases,n_control,gwas_study_name,gwas_study_Type
     FROM gwas_variant gv, gwas_variant_prop gvp, gwas_descriptor gd, gwas_phenotype gp, gwas_study gs
    where gs.gwas_study_id = gp.gwas_study_id 
    AND gp.gwas_phenotype_id = gv.gwas_phenotype_id
    AND gv.gwas_variant_id = gvp.gwas_variant_id
    AND gd.gwas_descriptor_id = gvp.gwas_descriptor_id
    AND variant_change_Id IN (" . implode(',', array_keys($MAP_POS)) . ')');
        foreach ($res as $line) {

            $ENTRY[$RSID]['VARIANT'][$MAP_POS[$line['VARIANT_CHANGE_ID']]]['CHANGE'][$line['VARIANT_CHANGE_ID']]['GWAS'][$line['GWAS_STUDY_NAME'] . '_' . $line['GWAS_STUDY_TYPE']][$line['PHENOTYPE_NAME']][$line['GWAS_DESCRIPTOR_NAME']] = array($line['GWAS_DESCRIPTOR_DESC'], $line['PROP_VALUE']);
        }
    }



    // $query = 'SELECT * FROM VARIANT_ENTRY VE, VARIANT_CHANGE VC WHERE RSID != ' . $RSID . ' AND POSITION=' . $ENTRY[$RSID]['VARIANT']['POSITION'] . ' AND CHR_ID=' . $ENTRY[$RSID]['VARIANT']['CHR_ID'] . ' AND VC.VARIANT_ENTRY_ID = VE.VARIANT_ENTRY_ID';
    // $res = array();
    // $res = runQuery($query);
    // foreach ($res as $line) $ENTRY[$RSID]['ALT_RECORDS'][$line['VARIANT_ENTRY_ID']][$line['VARIANT_CHANGE_ID']][] = $line;

    return $ENTRY;
}

function getDistinctClinSign($GN_ENTRY_ID)
{
    // $res = runQuery('SELECT distinct clin_sign_desc FROM CLINV_ASSERT CA, CLINV_ASSERT_MEAS CAM, CLINV_MEAS_GNMAP CMG
    // WHERE CA.CLINV_ASSERT_ID = CAM.CLINV_ASSERT_ID AND CAM.CLINV_MEASURE_ID = CMG.CLINV_MEASURE_ID AND GN_ENTRY_ID=' . $GN_ENTRY_ID . '
    // ORDER BY clin_sign_desc ASC');
    $DATA = array();
    // foreach ($res as $l) $DATA[] = $l['CLIN_SIGN_DESC'];
    return $DATA;
}

function getCoRNATissue($TISSUE_ID, $FILTERS)
{
    $query = 'SELECT COUNT(*) CO FROM RNA_GENE_STAT R ';
    $where = ' WHERE RNA_TISSUE_ID=' . $TISSUE_ID;

    
    $LIST = $FILTERS;

    if ($LIST != array()) {
        $query .= ", (SELECT DISTINCT GENE_SEQ_ID FROM GO_ENTRY GO, GO_PROT_MAP GUM, GN_PROT_MAP GU, GENE_SEQ GS
WHERE GO.GO_ENTRY_ID = GUM.GO_ENTRY_ID AND GUM.PROT_ENTRY_ID = GU.PROT_ENTRY_ID AND GU.GN_ENTRY_ID = GS.GN_ENTRY_ID AND AC IN (" . implode(",", $LIST) . ")) T ";
        $where .= ' AND R.GENE_SEQ_ID = T.GENE_SEQ_ID';
    }


    return runQuery($query . $where)[0]['CO'];
}

function getRNATissue($TISSUE_ID, $PARAMS, $FILTERS)
{
    $query = "SELECT * FROM (
		SELECT ROW_NUMBER() OVER( ORDER BY MED_VALUE DESC) R, 
		 N_SAMPLE,AUC,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE,SYMBOL,FULL_NAME,GENE_ID FROM RNA_GENE_STAT RG,GENE_SEQ GS, GN_ENTRY GE ";
    $where = "WHERE GE.GN_ENTRY_ID = GS.GN_ENTRY_ID AND GS.GENE_SEQ_ID = RG.GENE_SEQ_ID AND RNA_TISSUE_ID= " . $TISSUE_ID;
    $LIST=$FILTERS;

    if ($LIST != array()) {
        $query .= ", (SELECT DISTINCT GENE_SEQ_ID FROM GO_ENTRY GO, GO_PROT_MAP GUM, GN_PROT_MAP GU, GENE_SEQ GS
WHERE GO.GO_ENTRY_ID = GUM.GO_ENTRY_ID AND GUM.PROT_ENTRY_ID = GU.PROT_ENTRY_ID AND GU.GN_ENTRY_ID = GS.GN_ENTRY_ID AND AC IN (" . implode(",", $LIST) . ")) T ";
        $where .= ' AND RG.GENE_SEQ_ID = T.GENE_SEQ_ID';
    }
    $addl = ")
		WHERE R<" . $PARAMS['MAX'] . " AND R>=" . $PARAMS['MIN'];
    return runQuery($query . $where . $addl);
}
function getListDomainFromIso($ISO_ID)
{
    $query = "SELECT GENE_ID,SYMBOL,FULL_NAME,GE.GN_ENTRY_ID,PROT_IDENTIFIER,UE.PROT_ENTRY_ID,US.PROT_SEQ_ID,ISO_NAME,ISO_ID,DESCRIPTION,DOMAIN_NAME,PROT_DOM_ID,POS_START,POS_END,DOMAIN_TYPE 
    FROM PROT_SEQ US, PROT_DOM UD, PROT_ENTRY UE 
    LEFT JOIN GN_PROT_MAP GUM ON GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID 
    LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GUM.GN_ENTRY_ID 
    WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID
     AND UE.PROT_ENTRY_ID = UD.PROT_ENTRY_ID
    AND ISO_ID='" . $ISO_ID . "' AND US.IS_PRIMARY='T'";
    $res = runQuery($query);
    return $res;
}
function getListDomain($GENE_ID)
{
    $query = "SELECT GENE_ID,SYMBOL,FULL_NAME,GE.GN_ENTRY_ID,PROT_IDENTIFIER,UE.PROT_ENTRY_ID,US.PROT_SEQ_ID,ISO_NAME,ISO_ID,DESCRIPTION,DOMAIN_NAME,PROT_DOM_ID,POS_START,POS_END,DOMAIN_TYPE 
    FROM GN_ENTRY GE, GN_PROT_MAP GUM, PROT_ENTRY UE, PROT_SEQ US, PROT_DOM UD
    WHERE GE.GN_ENTRY_ID = GUM.GN_ENTRY_ID AND GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND UE.PROT_ENTRY_ID = UD.PROT_ENTRY_ID
    AND GENE_ID=" . $GENE_ID . " AND US.IS_PRIMARY='T'";
    $res = runQuery($query);
    return $res;
}

function getAllDomainInfo($PROT_DOM_ID, $COUNT_ONLY = false)
{
    $res = runQuery("SELECT GENE_ID,SYMBOL,FULL_NAME,GE.GN_ENTRY_ID,PROT_IDENTIFIER,UE.PROT_ENTRY_ID,US.PROT_SEQ_ID,ISO_NAME,ISO_ID,DESCRIPTION,DOMAIN_NAME,PROT_DOM_ID,POS_START,POS_END,DOMAIN_TYPE FROM GN_ENTRY GE, GN_PROT_MAP GUM, PROT_ENTRY UE, PROT_SEQ US, PROT_DOM UD
	WHERE GE.GN_ENTRY_ID = GUM.GN_ENTRY_ID AND GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND UE.PROT_ENTRY_ID = UD.PROT_ENTRY_ID
	AND PROT_DOM_ID = " . $PROT_DOM_ID . " AND US.IS_PRIMARY='T'");
    
    $DATA['DOM'] = $res[0];
    $DATA['IP_ENTRY']=array();
    $query = "SELECT IP_SIGN_DBNAME,IP_SIGN_DBKEY,IP_SIGN_NAME,START_POS,END_POS,MODEL,EVIDENCE,SCORE,US.PROT_SEQ_ID,IP_ENTRY_ID 
    FROM IP_SIGNATURE ISA, IP_SIGN_PROT_SEQ ISUS,PROT_SEQ US
	WHERE US.PROT_SEQ_ID = ISUS.PROT_SEQ_ID 
    AND ISUS.IP_SIGNATURE_ID = ISA.IP_SIGNATURE_ID
	AND US.PROT_SEQ_ID =" . $DATA['DOM']['PROT_SEQ_ID'];
    $res = runQuery($query);
    foreach ($res as $line) {
        if ($line['END_POS'] < $DATA['DOM']['POS_START']) continue;
        if ($line['START_POS'] > $DATA['DOM']['POS_END']) continue;
        $DATA['IP_ENTRY'][$line['IP_ENTRY_ID']]['SIGN'][] = $line;
    }
    if ($DATA['IP_ENTRY']!=array())
    {
    $query = 'SELECT IP_ENTRY_ID, IPR_ID, NAME,ABSTRACT,ENTRY_TYPE FROM IP_ENTRY WHERE IP_ENTRY_ID  IN (' . implode(',', array_keys($DATA['IP_ENTRY'])) . ')';
    $res = runQuery($query);
    foreach ($res as $line) {
        $DATA['IP_ENTRY'][$line['IP_ENTRY_ID']]['INFO'] = $line;
        //$DATA['IP_ENTRY'][$line['IP_ENTRY_ID']]['INFO']['ABSTRACT']=stream_get_contents($line['ABSTRACT']);
    }
}
    if (!$COUNT_ONLY)
        $query = 'SELECT PERC_SIM,PERC_IDENTITY,PERC_SIM_COM,PERC_IDENTITY_COM,DOMAIN_NAME,DOMAIN_TYPE,PROT_IDENTIFIER,SCIENTIFIC_NAME,TAX_ID,SYMBOL,GENE_ID,FULL_NAME ';
    else $query = 'SELECT COUNT(*) CO ';

    $query .= ' FROM PROT_DOM_AL UDA, PROT_DOM UD, TAXON T,PROT_ENTRY UE
		LEFT JOIN GN_PROT_MAP GUM ON GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
		LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID=GUM.GN_ENTRY_ID
		WHERE uda.PROT_DOM_comp_id=UD.PROT_DOM_ID AND UD.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND T.TAXON_ID = UE.TAXON_ID AND UDA.PROT_DOM_REF_ID=' . $DATA['DOM']['PROT_DOM_ID'];

    if ($COUNT_ONLY) $DATA['SIM_DOM'] = runQuery($query)[0]['CO'];
    else $DATA['SIM_DOM'] = runQuery($query);

    if ($COUNT_ONLY) {
        $query = "SELECT count(*) CO FROM XR_PROT_DOM_COV WHERE PROT_DOM_ID =" . $DATA['DOM']['PROT_DOM_ID'];
        $res = runQuery($query);
        $DATA['XRAY'] = $res[0]['CO'];
    } else {
        $query = "SELECT COVERAGE, FULL_COMMON_NAME,EXPR_TYPE,RESOLUTION,TITLE FROM XR_PROT_DOM_COV XUD, XR_CHAIN XC,XR_ENTRY XE  WHERE XE.XR_ENTRY_ID = XC.XR_ENTRY_ID AND XC.XR_CHAIN_ID = XUD.XR_CHAIN_ID AND PROT_DOM_ID =" . $DATA['DOM']['PROT_DOM_ID'];
        $DATA['XRAY'] = $res;
    }
    if (!$COUNT_ONLY) $query = "SELECT XTR.NAME, XTR.CLASS, COUNT(DISTINCT XR.XR_CHAIN_ID) CO ";
    else $query = "SELECT COUNT(DISTINCT XTR.XR_TPL_RES_ID) CO ";
    $query .= " FROM  XR_TPL_RES XTR, XR_RES XR2, xr_inter_res XIR, XR_RES XR, XR_CH_PROT_POS XCUP, PROT_SEQ_POS USP,PROT_DOM_seq UDS
		WHERE   XCUP.PROT_SEQ_POS_ID = USP.PROT_SEQ_POS_ID AND USP.PROT_SEQ_POS_ID = UDS.PROT_SEQ_POS_ID AND UDS.PROT_DOM_ID=" . $DATA['DOM']['PROT_DOM_ID'] . "
		AND XR.XR_RES_ID = XCUP.XR_RES_ID AND XR.XR_RES_ID = XIR.XR_RES_ID_1 AND XIR.XR_RES_ID_2=XR2.XR_RES_ID AND XR2.XR_TPL_RES_ID = XTR.XR_TPL_RES_ID
		AND XTR.CLASS NOT IN ('AA','MOD_AA','WATER') GROUP BY XTR.NAME, XTR.CLASS";

    $DATA['XRAY_LIG'] = runQuery($query);

    return $DATA;
}

function getDomainAlignment($DOM_REF_ID, $LIST_DOMS)
{
    $DATA = array();
    $res = runQuery("SELECT * FROM PROT_DOM_AL WHERE PROT_DOM_REF_ID=" . $DOM_REF_ID . " AND PROT_DOM_COMP_ID IN (" . implode(",", $LIST_DOMS) . ')');
    if ($res == array()) return $DATA;
    $COMP_DOM = array();
    $LIST_DOMS_ID = array($DOM_REF_ID);
    $CURSOR = array();
    foreach ($res as $line) {
        $tmp = $line;
        unset($tmp['PROT_DOM_AL_ID'], $tmp['PROT_DOM_REF_ID'], $tmp['PROT_DOM_COMP_ID']);
        $DATA[$line['PROT_DOM_COMP_ID']] = $tmp;
        $COMP_DOM[$line['PROT_DOM_AL_ID']] = $line['PROT_DOM_COMP_ID'];
        $LIST_DOMS_ID[] = $line['PROT_DOM_COMP_ID'];
    }

    $TMP = array();
    $res = runQuery("SELECT PROT_DOM_ID,PROT_DOM_SEQ_ID, UDS.POSITION, USP.LETTER
	 FROM PROT_DOM_SEQ UDS, PROT_SEQ_POS USP
	 WHERE USP.PROT_SEQ_POS_ID = UDS.PROT_SEQ_POS_ID
	 AND PROT_DOM_ID IN (" . implode(",", $LIST_DOMS_ID) . ')');
    foreach ($res as $line) {
        $TMP[$line['PROT_DOM_SEQ_ID']] = array($line['PROT_DOM_ID'], $line['POSITION']);
        if ($line['PROT_DOM_ID'] != $DOM_REF_ID) $DATA[$line['PROT_DOM_ID']]['SEQ'][$line['POSITION']] = $line['LETTER'];
    }


    $res = runQuery(" SELECT UDA.PROT_DOM_AL_ID, PROT_DOM_seq_id_ref,PROT_DOM_seq_id_comp
    FROM PROT_DOM_al_seq UDA
	WHERE uda.PROT_DOM_al_id= (" . implode(",", array_keys($COMP_DOM)) . ')');
    foreach ($res as $line) $DATA[$COMP_DOM[$line['PROT_DOM_AL_ID']]]['AL'][$TMP[$line['PROT_DOM_SEQ_ID_REF']][1]] = $TMP[$line['PROT_DOM_SEQ_ID_COMP']][1];

    foreach ($DATA as $C => &$INFO) {
        if (!isset($INFO['AL'])) return array('ERROR' => 'Unable to retrieve alignment' . "\n");
        ksort($INFO['AL']);
    }


    $res = runQuery("SELECT PROT_DOM_ID,PROT_DOM_SEQ_ID, UDS.POSITION, USP.LETTER
	 FROM PROT_DOM_SEQ UDS, PROT_SEQ_POS USP
	 WHERE USP.PROT_SEQ_POS_ID = UDS.PROT_SEQ_POS_ID
	 AND PROT_DOM_ID =" . $DOM_REF_ID);
    $REF_SEQ = array();
    foreach ($res as $line) {
        $REF_SEQ[$line['POSITION']] = $line['LETTER'];
        $TMP[$line['PROT_DOM_SEQ_ID']] = array('REF', $line['POSITION']);
    }
    $CHUNKS = array_chunk(array_keys($TMP), 1000);
    $INTER_REF = array();
    foreach ($CHUNKS as $CHUNK) {
        $res = runQuery("SELECT uds.PROT_DOM_seq_id,CLASS,SUM(COUNT_INT) AS COUNT_INT,INTERACTION_NAME FROM PROT_DOM_SEQ UDS,XR_PROT_INT_STAT X,XR_INTER_TYPE XI
		WHERE UDS.PROT_SEQ_POS_ID=X.PROT_SEQ_POS_ID AND X.XR_INTER_TYPE_ID = XI.XR_INTER_TYPE_ID
		AND PROT_DOM_SEQ_ID IN (" . implode(',', $CHUNK) . ") AND CLASS!='WATER' GROUP BY uds.PROT_DOM_seq_id,CLASS,INTERACTION_NAME");
        foreach ($res as $line) {
            $E = &$TMP[$line['PROT_DOM_SEQ_ID']];
            $T = $line;
            unset($T['PROT_DOM_SEQ_ID']);
            if ($E[0] == 'REF') $INTER_REF[$E[1]][] = $T;
            else $DATA[$E[0]]['INTER'][$E[1]][] = $T;
        }
    }

    // $res = runQuery(" SELECT UDA.PROT_DOM_AL_ID, UDS1.POSITION as REF_POS, UDS2.POSITION as COMP_POS
    // FROM PROT_DOM_al_seq UDA, PROT_DOM_SEQ UDS1, PROT_SEQ_POS USP1, PROT_DOM_SEQ UDS2, PROT_SEQ_POS USP2
    // WHERE uda.PROT_DOM_seq_id_ref=UDS1.PROT_DOM_SEQ_ID AND UDS1.PROT_SEQ_POS_ID = USP1.PROT_SEQ_POS_ID
    // AND uda.PROT_DOM_seq_id_comp=UDS2.PROT_DOM_SEQ_ID AND UDS2.PROT_SEQ_POS_ID = USP2.PROT_SEQ_POS_ID
    // AND uda.PROT_DOM_al_id= (" . implode(",", array_keys($COMP_DOM)) . ')');
    // foreach ($res as $line) $DATA[$COMP_DOM[$line['PROT_DOM_AL_ID']]]['AL'][$line['REF_POS']] = $line['COMP_POS'];
    // print_r($res);exit;
    // foreach ($DATA as $C => &$INFO)
    // {
    //     if (!isset($INFO['AL'])) return array('ERROR'=>'Unable to retrieve alignment'."\n");
    //     ksort($INFO['AL']);

    // } 

    // $res = runQuery("SELECT PROT_DOM_ID,PROT_DOM_SEQ_ID, UDS.POSITION, USP.LETTER
    //  FROM PROT_DOM_SEQ UDS, PROT_SEQ_POS USP
    //  WHERE USP.PROT_SEQ_POS_ID = UDS.PROT_SEQ_POS_ID
    //  AND PROT_DOM_ID IN (" . implode(",", $COMP_DOM) . ')');
    // foreach ($res as $line) {
    //     $TMP[$line['PROT_DOM_SEQ_ID']] = array($line['PROT_DOM_ID'], $line['POSITION']);
    //     $DATA[$line['PROT_DOM_ID']]['SEQ'][$line['POSITION']] = $line['LETTER'];
    // }

    // $res = runQuery("SELECT PROT_DOM_ID,PROT_DOM_SEQ_ID, UDS.POSITION, USP.LETTER
    //  FROM PROT_DOM_SEQ UDS, PROT_SEQ_POS USP
    //  WHERE USP.PROT_SEQ_POS_ID = UDS.PROT_SEQ_POS_ID
    //  AND PROT_DOM_ID =" . $DOM_REF_ID);
    // $REF_SEQ = array();
    // foreach ($res as $line) {
    //     $REF_SEQ[$line['POSITION']] = $line['LETTER'];
    //     $TMP[$line['PROT_DOM_SEQ_ID']] = array('REF', $line['POSITION']);
    // }
    // $CHUNKS = array_chunk(array_keys($TMP), 1000);
    // $INTER_REF = array();
    // foreach ($CHUNKS as $CHUNK) {
    //     $res = runQuery("SELECT uds.PROT_DOM_seq_id,CLASS,SUM(COUNT_INT) AS COUNT_INT,INTERACTION_NAME FROM PROT_DOM_SEQ UDS,XR_PROT_INT_STAT X,XR_INTER_TYPE XI
    // 	WHERE UDS.PROT_SEQ_POS_ID=X.PROT_SEQ_POS_ID AND X.XR_INTER_TYPE_ID = XI.XR_INTER_TYPE_ID
    // 	AND PROT_DOM_SEQ_ID IN (" . implode(',', $CHUNK) . ") AND CLASS!='WATER' GROUP BY uds.PROT_DOM_seq_id,CLASS,INTERACTION_NAME");
    //     foreach ($res as $line) {
    //         $E = &$TMP[$line['PROT_DOM_SEQ_ID']];
    //         $T = $line;
    //         unset($T['PROT_DOM_SEQ_ID']);
    //         if ($E[0] == 'REF') $INTER_REF[$E[1]][] = $T;
    //         else $DATA[$E[0]]['INTER'][$E[1]][] = $T;
    //     }
    // }

    return array($REF_SEQ, $DATA, $INTER_REF);
}

function searchSimDomByDomain($PROT_DOM_ID, $OPTIONS)
{

    $query = 'SELECT * FROM
		(SELECT ROW_NUMBER() OVER(ORDER BY PERC_IDENTITY_COM DESC) R, P.*
		FROM (SELECT PERC_SIM,PERC_IDENTITY,PERC_SIM_COM,PERC_IDENTITY_COM,DOMAIN_NAME,
                DOMAIN_TYPE,POS_START,POS_END,ISO_ID,PROT_IDENTIFIER,SCIENTIFIC_NAME,TAX_ID,SYMBOL,GENE_ID,FULL_NAME
		        FROM PROT_DOM_AL UDA, PROT_DOM UD, TAXON T, PROT_SEQ US,PROT_ENTRY UE
		        LEFT JOIN GN_PROT_MAP GUM ON GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
		        LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID=GUM.GN_ENTRY_ID
		        WHERE uda.PROT_DOM_comp_id=UD.PROT_DOM_ID 
                AND UD.PROT_ENTRY_ID = UE.PROT_ENTRY_ID 
                AND US.PROT_ENTRY_ID = UE.PROT_ENTRY_ID 
                AND US.IS_PRIMARY=\'T\'
                 AND T.TAXON_ID = UE.TAXON_ID AND UDA.PROT_DOM_REF_ID=' . $PROT_DOM_ID . ") P) C  WHERE R<" . $OPTIONS['MAX'] . ' AND R>=' . $OPTIONS['MIN'];

    return runQuery($query);
}

function searchXrayCoverageByDomain($PROT_DOM_ID, $OPTIONS)
{

    $query = 'SELECT * FROM
		(SELECT ROW_NUMBER() OVER(ORDER BY DEPOSITION_DATE DESC) R, P.*
		FROM (SELECT COVERAGE, FULL_COMMON_NAME,CHAIN_NAME,EXPR_TYPE,RESOLUTION,TITLE,TO_CHAR(DEPOSITION_DATE, \'YYYY-MM-DD\' ) as DEPOSITION_DATE FROM XR_PROT_DOM_COV XUD, XR_CHAIN XC,XR_ENTRY XE  WHERE XE.XR_ENTRY_ID = XC.XR_ENTRY_ID AND XC.XR_CHAIN_ID = XUD.XR_CHAIN_ID AND PROT_DOM_ID =' . $PROT_DOM_ID . ") P)  WHERE R<" . $OPTIONS['MAX'] . ' AND R>=' . $OPTIONS['MIN'];

    return runQuery($query);
}

function getProteinSequences($GENE_ID)
{
    if (!is_numeric($GENE_ID)) throw new Exception("Provided Gene ID is not numeric", ERR_TGT_USR);
    $query = "SELECT PROT_SEQ_ID,UE.STATUS,UE.PROT_IDENTIFIER, ISO_ID, ISO_NAME,IS_PRIMARY,DESCRIPTION, CONFIDENCE
	FROM GN_ENTRY GE, GN_PROT_MAP GUM, PROT_ENTRY UE, PROT_SEQ US WHERE
	GUM.GN_ENTRY_ID = GE.GN_ENTRY_ID AND GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND US.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND GENE_ID=" . $GENE_ID . "
	AND UE.STATUS!='D'
	ORDER BY UE.STATUS DESC,IS_PRIMARY DESC";
    $res = runQuery($query);
    $DATA = array('SEQ' => array());
    foreach ($res as $line) {
        $DATA['SEQ'][$line['PROT_SEQ_ID']]['SEQ'] = $line;
    }

    if (count($DATA['SEQ']) == 0) return $DATA;

    $query = 'SELECT * FROM TR_PROTSEQ_AL U, TRANSCRIPT T WHERE U.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND PROT_SEQ_ID IN   (' . implode(',', array_keys($DATA['SEQ'])) . ')';
    $res = runQuery($query);
    foreach ($res as $line) {
        $DATA['SEQ'][$line['PROT_SEQ_ID']]['TRANSCRIPT'][] = $line;
    }

    $query = 'SELECT * FROM PROT_SEQ_AL WHERE PROT_SEQ_REF_ID IN (' . implode(',', array_keys($DATA['SEQ'])) . ') AND PROT_SEQ_COMP_ID IN (' . implode(',', array_keys($DATA['SEQ'])) . ')';
    $DATA['SIM'] = runQuery($query);
    $res = runQuery("SELECT COUNT(*) CO, PROT_SEQ_ID FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN  (" . implode(',', array_keys($DATA['SEQ'])) . ') GROUP BY PROT_SEQ_ID');
    foreach ($res as $line) $DATA['SEQ'][$line['PROT_SEQ_ID']]['SEQ']['LEN'] = $line['CO'];

    return $DATA;
}

function getInterProFromUnDom($LIST_UNIDENTIFIER)
{

    $query = "SELECT IP_SIGN_DBNAME,IP_SIGN_DBKEY,IP_SIGN_NAME,START_POS,END_POS,MODEL,EVIDENCE,SCORE,US.PROT_SEQ_ID,IP_ENTRY_ID FROM IP_SIGNATURE ISA, IP_SIGN_PROT_SEQ ISUS, PROT_ENTRY UE, PROT_SEQ US
   WHERE UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = ISUS.PROT_SEQ_ID AND ISUS.IP_SIGNATURE_ID = ISA.IP_SIGNATURE_ID
   AND UE.PROT_IDENTIFIER IN (";
    foreach ($LIST_UNIDENTIFIER as $UNI) $query .= "'" . $UNI . "',";
    $query = substr($query, 0, -1) . ")";
    $res1 = runQuery($query);
    print_r($res1);
    $data = array();
    $LIST_IPD = array();
    foreach ($res1 as $line) {

        $data[$line['IP_ENTRY_ID']]['SEQ'][$line['PROT_SEQ_ID']]['SIGN'][] = $line;
    }
    foreach ($data as $IP_ENTRY_ID => &$INFO) {
        $query = 'SELECT IP_ENTRY_ID, IPR_ID, NAME,ABSTRACT,ENTRY_TYPE FROM IP_ENTRY WHERE IP_ENTRY_ID =' . $IP_ENTRY_ID;
        $res = runQuery($query);
        foreach ($res as $line) {
            $data[$IP_ENTRY_ID]['INFO'] = $line;
        }

        $query = "SELECT IP2.IPR_ID,IP2.ENTRY_TYPE,IP2.NAME, IP2.IP_LEVEL FROM IP_ENTRY IP, IP_ENTRY IP2 WHERE IP.IP_LEVEL_LEFT>= IP2.IP_LEVEL_LEFT AND IP.IP_LEVEL_RIGHT <= IP2.IP_LEVEL_RIGHT AND IP.IP_ENTRY_ID=" . $IP_ENTRY_ID . " ORDER BY IP2.IP_LEVEL ASC";
        $res2 = runQuery($query);

        foreach ($res2 as $line) {
            $data[$IP_ENTRY_ID]['INFO']['TREE'][$line['IP_LEVEL']] = $line;
        }
    }

    return $data;
}


function getExternalLinksFromProtein($PROT_IDENTIFIER)
{
    $res = runQuery('SELECT PROT_IDENTIFIER, UE.STATUS, CONFIDENCE, PROT_EXTDB_VALUE, PROT_SEQ_ID, PROT_EXTDBAC, PROT_EXTDBABBR, PROT_EXTDBNAME, PROT_EXTDBSERVER, PROT_EXTDBURL, CATEGORY 
    FROM  PROT_ENTRY UE, PROT_EXTDB_MAP UEM, PROT_EXTDB UD
	WHERE UE.PROT_ENTRY_ID = UEM.PROT_ENTRY_ID
	AND UEM.PROT_EXTDB_ID = UD.PROT_EXTDBID AND PROT_IDENTIFIER=\'' . $PROT_IDENTIFIER . "'");
    $data = array();
    foreach ($res as $line) {
        if (!isset($data[$line['CATEGORY']][$line['PROT_EXTDBNAME']])) $data[$line['CATEGORY']][$line['PROT_EXTDBNAME']] = array('INFO' => array('ABBR' => $line['PROT_EXTDBABBR'], 'SERVER' => $line['PROT_EXTDBSERVER'], 'URL' => $line['PROT_EXTDBURL']), 'VALUES' => array());
        $data[$line['CATEGORY']][$line['PROT_EXTDBNAME']]['VALUES'][$line['PROT_EXTDB_VALUE']][] = array($line['PROT_IDENTIFIER'], $line['STATUS'], $line['CONFIDENCE'], $line['PROT_SEQ_ID']);
    }
    return $data;
}

function getExternalLinksFromGene($GENE_ID)
{
    $res = runQuery('SELECT PROT_IDENTIFIER, UE.STATUS, CONFIDENCE, PROT_EXTDB_VALUE, PROT_SEQ_ID, PROT_EXTDBAC, PROT_EXTDBABBR, PROT_EXTDBNAME, PROT_EXTDBSERVER, PROT_EXTDBURL, CATEGORY 
    FROM GN_ENTRY GE, GN_PROT_MAP GUM, PROT_ENTRY UE, PROT_EXTDB_MAP UEM, PROT_EXTDB UD
	WHERE GE.GN_ENTRY_ID = GUM.GN_ENTRY_ID
	AND GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
	AND UE.PROT_ENTRY_ID = UEM.PROT_ENTRY_ID
	AND UEM.PROT_EXTDB_ID = UD.PROT_EXTDBID AND GENE_ID=' . $GENE_ID);
    $data = array();
    foreach ($res as $line) {
        if (!isset($data[$line['CATEGORY']][$line['PROT_EXTDBNAME']])) $data[$line['CATEGORY']][$line['PROT_EXTDBNAME']] = array('INFO' => array('ABBR' => $line['PROT_EXTDBABBR'], 'SERVER' => $line['PROT_EXTDBSERVER'], 'URL' => $line['PROT_EXTDBURL']), 'VALUES' => array());
        $data[$line['CATEGORY']][$line['PROT_EXTDBNAME']]['VALUES'][$line['PROT_EXTDB_VALUE']][] = array($line['PROT_IDENTIFIER'], $line['STATUS'], $line['CONFIDENCE'], $line['PROT_SEQ_ID']);
    }
    return $data;
}

function getIsoformAlignment($SEQ_REF_ID)
{
    $DATA = array();
    $res = runQuery("SELECT UE.PROT_IDENTIFIER, US2.ISO_ID,US2.PROT_SEQ_ID
	FROM  PROT_SEQ US, GN_PROT_MAP GUM,  GN_PROT_MAP GUM2, PROT_SEQ US2,PROT_ENTRY UE
		WHERE US.PROT_ENTRY_ID = GUM.PROT_ENTRY_ID AND gum.gn_entry_id=gum2.gn_entry_id
		AND UE.PROT_ENTRY_ID = US2.PROT_ENTRY_ID AND  GUM2.PROT_ENTRY_ID = US2.PROT_ENTRY_ID AND
		US.PROT_SEQ_ID=" . $SEQ_REF_ID);
    foreach ($res as $line) $DATA['SEQ'][$line['PROT_SEQ_ID']] = $line;

    $res = runQuery("SELECT * FROM PROT_SEQ_AL WHERE PROT_SEQ_REF_ID=" . $SEQ_REF_ID . " AND PROT_SEQ_COMP_ID IN (" . implode(",", array_keys($DATA['SEQ'])) . ')');
    if ($res == array()) return $DATA;

    $COMP_SEQ = array();
    $CURSOR = array();
    foreach ($res as $line) {
        $tmp = $line;
        unset($tmp['PROT_SEQ_AL_ID'], $tmp['PROT_SEQ_REF_ID'], $tmp['PROT_SEQ_COMP_ID']);
        $DATA[$line['PROT_SEQ_COMP_ID']] = $tmp;
        $COMP_SEQ[$line['PROT_SEQ_AL_ID']] = $line['PROT_SEQ_COMP_ID'];
    }

    foreach ($COMP_SEQ as $AL_ID => &$DUMMY) {
        $query = " SELECT UDA.PROT_SEQ_AL_ID, USP1.POSITION as REF_POS, USP2.POSITION as COMP_POS
	FROM PROT_SEQ_al_seq UDA,  PROT_SEQ_POS USP1, PROT_SEQ_POS USP2
	WHERE uda.PROT_SEQ_ID_ref= USP1.PROT_SEQ_POS_ID
	AND uda.PROT_SEQ_ID_comp= USP2.PROT_SEQ_POS_ID
	AND uda.PROT_SEQ_al_id = " . $AL_ID;
        $res = runQuery($query);
        echo $query;
        foreach ($res as $line) $DATA[$COMP_SEQ[$line['PROT_SEQ_AL_ID']]]['AL'][$line['REF_POS']] = $line['COMP_POS'];
    }
    foreach ($DATA as $C => &$INFO) {
        if ($C != 'REF' && $C != 'SEQ') ksort($INFO['AL']);
    }

    $res = runQuery("SELECT PROT_SEQ_ID,PROT_SEQ_POS_ID, POSITION, LETTER
	 FROM PROT_SEQ_POS USP
	 WHERE PROT_SEQ_ID IN (" . implode(",", $COMP_SEQ) . ')');
    foreach ($res as $line) {
        $TMP[$line['PROT_SEQ_POS_ID']] = array($line['PROT_SEQ_ID'], $line['POSITION']);
        $DATA[$line['PROT_SEQ_ID']]['SEQ'][$line['POSITION']] = $line['LETTER'];
    }


    $res = runQuery("SELECT PROT_SEQ_ID,PROT_SEQ_POS_ID, POSITION, LETTER
	FROM PROT_SEQ_POS USP
	WHERE PROT_SEQ_ID =" . $SEQ_REF_ID);
    $REF_SEQ = array();
    foreach ($res as $line) {
        $REF_SEQ[$line['POSITION']] = $line['LETTER'];
        $TMP[$line['PROT_SEQ_POS_ID']] = array('REF', $line['POSITION']);
    }
    $CHUNKS = array_chunk(array_keys($TMP), 1000);
    $INTER_REF = array();
    foreach ($CHUNKS as $CHUNK) {
        $res = runQuery("SELECT PROT_SEQ_pos_id,CLASS,SUM(COUNT_INT) AS COUNT_INT,INTERACTION_NAME FROM XR_PROT_INT_STAT X,XR_INTER_TYPE XI
		WHERE X.XR_INTER_TYPE_ID = XI.XR_INTER_TYPE_ID
		AND PROT_SEQ_POS_ID IN (" . implode(',', $CHUNK) . ") AND CLASS!='WATER' GROUP BY x.PROT_SEQ_pos_id,CLASS,INTERACTION_NAME");
        foreach ($res as $line) {
            $E = &$TMP[$line['PROT_SEQ_POS_ID']];
            $T = $line;
            unset($T['PROT_SEQ_POS_ID']);
            if ($E[0] == 'REF') $INTER_REF[$E[1]][] = $T;
            else $DATA[$E[0]]['INTER'][$E[1]][] = $T;
        }
    }

    return array($REF_SEQ, $DATA, $INTER_REF);
}

function getOrthologDomainAlign($DOM_REF_ID)
{
    $DATA = array();
    $res = runQuery("SELECT US2.DOMAIN_TYPE,US2.DOMAIN_NAME, US2.POS_START,US2.POS_END,US2.PROT_DOM_ID,SYMBOL,GENE_ID,FULL_NAME, SCIENTIFIC_NAME,UE.STATUS
	FROM TAXON T, PROT_DOM US, GN_PROT_MAP GUM, GN_REL GR, GN_PROT_MAP GUM2, PROT_DOM US2,PROT_ENTRY UE, GN_ENTRY GE
	WHERE US.PROT_ENTRY_ID = GUM.PROT_ENTRY_ID AND gum.gn_entry_id=gr.gn_entry_r_id
	AND T.TAXON_ID = UE.TAXON_ID AND UE.PROT_ENTRY_ID = US2.PROT_ENTRY_ID
	AND GE.GN_ENTRY_ID = GR.GN_ENTRY_C_ID AND GR.GN_ENTRY_C_ID = GUM2.GN_ENTRY_ID
	AND GUM2.PROT_ENTRY_ID = US2.PROT_ENTRY_ID AND
	US.PROT_DOM_ID=" . $DOM_REF_ID);
    foreach ($res as $line) $DATA['SEQ'][$line['PROT_DOM_ID']] = $line;

    $res = runQuery("SELECT * FROM PROT_DOM_AL WHERE PROT_DOM_REF_ID=" . $DOM_REF_ID . " AND PROT_DOM_COMP_ID IN (" . implode(",", array_keys($DATA['SEQ'])) . ')');
    if ($res == array()) return $DATA;
    $COMP_DOM = array();
    $CURSOR = array();
    foreach ($res as $line) {
        $tmp = $line;
        unset($tmp['PROT_DOM_AL_ID'], $tmp['PROT_DOM_REF_ID'], $tmp['PROT_DOM_COMP_ID']);
        $DATA[$line['PROT_DOM_COMP_ID']] = $tmp;
        $COMP_DOM[$line['PROT_DOM_AL_ID']] = $line['PROT_DOM_COMP_ID'];
    }


    $res = runQuery("SELECT UDA.PROT_DOM_AL_ID, UDS1.POSITION as REF_POS, UDS2.POSITION as COMP_POS FROM PROT_DOM_al_seq UDA, PROT_DOM_SEQ UDS1, PROT_SEQ_POS USP1, PROT_DOM_SEQ UDS2, PROT_SEQ_POS USP2
	WHERE uda.PROT_DOM_seq_id_ref=UDS1.PROT_DOM_SEQ_ID AND UDS1.PROT_SEQ_POS_ID = USP1.PROT_SEQ_POS_ID
	AND uda.PROT_DOM_seq_id_comp=UDS2.PROT_DOM_SEQ_ID AND UDS2.PROT_SEQ_POS_ID = USP2.PROT_SEQ_POS_ID
	AND uda.PROT_DOM_al_id IN (" . implode(",", array_keys($COMP_DOM)) . ')');
    foreach ($res as $line) $DATA[$COMP_DOM[$line['PROT_DOM_AL_ID']]]['AL'][$line['REF_POS']] = $line['COMP_POS'];

    foreach ($DATA as $C => &$INFO) {
        if ($C != 'SEQ') ksort($INFO['AL']);
    }

    $res = runQuery("SELECT PROT_DOM_ID,PROT_DOM_SEQ_ID, UDS.POSITION, USP.LETTER
	 FROM PROT_DOM_SEQ UDS, PROT_SEQ_POS USP
	 WHERE USP.PROT_SEQ_POS_ID = UDS.PROT_SEQ_POS_ID
	 AND PROT_DOM_ID IN (" . implode(",", $COMP_DOM) . ')');
    foreach ($res as $line) {
        $TMP[$line['PROT_DOM_SEQ_ID']] = array($line['PROT_DOM_ID'], $line['POSITION']);
        $DATA[$line['PROT_DOM_ID']]['SEQ'][$line['POSITION']] = $line['LETTER'];
    }


    $res = runQuery("SELECT PROT_DOM_ID,PROT_DOM_SEQ_ID, UDS.POSITION, USP.LETTER
	 FROM PROT_DOM_SEQ UDS, PROT_SEQ_POS USP
	 WHERE USP.PROT_SEQ_POS_ID = UDS.PROT_SEQ_POS_ID
	 AND PROT_DOM_ID =" . $DOM_REF_ID);
    $REF_SEQ = array();
    foreach ($res as $line) {
        $REF_SEQ[$line['POSITION']] = $line['LETTER'];
        $TMP[$line['PROT_DOM_SEQ_ID']] = array('REF', $line['POSITION']);
    }
    $CHUNKS = array_chunk(array_keys($TMP), 1000);
    $INTER_REF = array();
    foreach ($CHUNKS as $CHUNK) {
        $res = runQuery("SELECT uds.PROT_DOM_seq_id,CLASS,SUM(COUNT_INT) AS COUNT_INT,INTERACTION_NAME FROM PROT_DOM_SEQ UDS,XR_PROT_INT_STAT X,XR_INTER_TYPE XI
		WHERE UDS.PROT_SEQ_POS_ID=X.PROT_SEQ_POS_ID AND X.XR_INTER_TYPE_ID = XI.XR_INTER_TYPE_ID
		AND PROT_DOM_SEQ_ID IN (" . implode(',', $CHUNK) . ") AND CLASS!='WATER' GROUP BY uds.PROT_DOM_seq_id,CLASS,INTERACTION_NAME");
        foreach ($res as $line) {
            $E = &$TMP[$line['PROT_DOM_SEQ_ID']];
            $T = $line;
            unset($T['PROT_DOM_SEQ_ID']);
            if ($E[0] == 'REF') $INTER_REF[$E[1]][] = $T;
            else $DATA[$E[0]]['INTER'][$E[1]][] = $T;
        }
    }

    return array($REF_SEQ, $DATA, $INTER_REF);
}

function getOrthologAlignment($SEQ_REF_ID)
{
    $DATA = array();
    $res = runQuery("SELECT US2.ISO_ID,US2.PROT_SEQ_ID, US2.IS_PRIMARY,SYMBOL,GENE_ID,FULL_NAME, SCIENTIFIC_NAME,UE.STATUS  FROM TAXON T, PROT_SEQ US, GN_PROT_MAP GUM, GN_REL GR, GN_PROT_MAP GUM2, PROT_SEQ US2,PROT_ENTRY UE, GN_ENTRY GE
	WHERE US.PROT_ENTRY_ID = GUM.PROT_ENTRY_ID AND gum.gn_entry_id=gr.gn_entry_r_id AND T.TAXON_ID = UE.TAXON_ID AND UE.PROT_ENTRY_ID = US2.PROT_ENTRY_ID AND GE.GN_ENTRY_ID = GR.GN_ENTRY_C_ID AND GR.GN_ENTRY_C_ID = GUM2.GN_ENTRY_ID AND GUM2.PROT_ENTRY_ID = US2.PROT_ENTRY_ID AND
	US.PROT_SEQ_ID=" . $SEQ_REF_ID);
    foreach ($res as $line) $DATA['SEQ'][$line['PROT_SEQ_ID']] = $line;

    $res = runQuery("SELECT * FROM PROT_SEQ_AL WHERE PROT_SEQ_REF_ID=" . $SEQ_REF_ID . " AND PROT_SEQ_COMP_ID IN (" . implode(",", array_keys($DATA['SEQ'])) . ')');
    if ($res == array()) return $DATA;

    $COMP_SEQ = array();
    $CURSOR = array();
    foreach ($res as $line) {
        $tmp = $line;
        unset($tmp['PROT_SEQ_AL_ID'], $tmp['PROT_SEQ_REF_ID'], $tmp['PROT_SEQ_COMP_ID']);
        $DATA[$line['PROT_SEQ_COMP_ID']] = $tmp;
        $COMP_SEQ[$line['PROT_SEQ_AL_ID']] = $line['PROT_SEQ_COMP_ID'];
    }

    foreach ($COMP_SEQ as $ALID => &$DUMMY) {
        $query = " SELECT UDA.PROT_SEQ_AL_ID, USP1.POSITION as REF_POS, USP2.POSITION as COMP_POS
	FROM PROT_SEQ_al_seq UDA,  PROT_SEQ_POS USP1, PROT_SEQ_POS USP2
	WHERE uda.PROT_SEQ_ID_ref= USP1.PROT_SEQ_POS_ID
	AND uda.PROT_SEQ_ID_comp= USP2.PROT_SEQ_POS_ID
	AND uda.PROT_SEQ_al_id = " . $ALID;
        $res = runQuery($query);
        echo $query;
        foreach ($res as $line) $DATA[$COMP_SEQ[$line['PROT_SEQ_AL_ID']]]['AL'][$line['REF_POS']] = $line['COMP_POS'];
    }
    foreach ($DATA as $C => &$INFO) {
        if ($C != 'REF' && $C != 'SEQ') ksort($INFO['AL']);
    }

    $res = runQuery("SELECT PROT_SEQ_ID,PROT_SEQ_POS_ID, POSITION, LETTER
	 FROM PROT_SEQ_POS USP
	 WHERE PROT_SEQ_ID IN (" . implode(",", $COMP_SEQ) . ')');
    foreach ($res as $line) {
        $TMP[$line['PROT_SEQ_POS_ID']] = array($line['PROT_SEQ_ID'], $line['POSITION']);
        $DATA[$line['PROT_SEQ_ID']]['SEQ'][$line['POSITION']] = $line['LETTER'];
    }


    $res = runQuery("SELECT PROT_SEQ_ID,PROT_SEQ_POS_ID, POSITION, LETTER
	FROM PROT_SEQ_POS USP
	WHERE PROT_SEQ_ID =" . $SEQ_REF_ID);
    $REF_SEQ = array();
    foreach ($res as $line) {
        $REF_SEQ[$line['POSITION']] = $line['LETTER'];
        $TMP[$line['PROT_SEQ_POS_ID']] = array('REF', $line['POSITION']);
    }
    $CHUNKS = array_chunk(array_keys($TMP), 1000);
    $INTER_REF = array();
    foreach ($CHUNKS as $CHUNK) {
        $res = runQuery("SELECT PROT_SEQ_pos_id,CLASS,SUM(COUNT_INT) AS COUNT_INT,INTERACTION_NAME FROM XR_PROT_INT_STAT X,XR_INTER_TYPE XI
		WHERE X.XR_INTER_TYPE_ID = XI.XR_INTER_TYPE_ID
		AND PROT_SEQ_POS_ID IN (" . implode(',', $CHUNK) . ") AND CLASS!='WATER' GROUP BY x.PROT_SEQ_pos_id,CLASS,INTERACTION_NAME");
        foreach ($res as $line) {
            $E = &$TMP[$line['PROT_SEQ_POS_ID']];
            $T = $line;
            unset($T['PROT_SEQ_POS_ID']);
            if ($E[0] == 'REF') $INTER_REF[$E[1]][] = $T;
            else $DATA[$E[0]]['INTER'][$E[1]][] = $T;
        }
    }

    return array($REF_SEQ, $DATA, $INTER_REF);
}

function getProtExpression($GENE_ID)
{
    $query = "SELECT tissue_name,cell_type,expression,confidence
	FROM PA_EXPRESSION PA, GENE_SEQ GS, GN_ENTRY GE, PA_TISSUE T
	WHERE PA.GN_SEQ_ID = GS.GENE_SEQ_ID
	AND GS.GN_ENTRY_ID = GE.GN_ENTRY_ID
	AND PA.PA_TISSUE_ID = T.PA_TISSUE_ID
	AND GENE_ID=" . $GENE_ID . " ORDER BY EXPRESSION DESC";
    return runQuery($query);
}

function getProteinResidueInfo($GENE_ID, $RES_NAME, $RES_ID, $RES3L)
{
    $DATA = array();

    $query = "SELECT XR_TPL_ATOM_ID, ATOMIC_RADIUS, XA.NAME,CHARGE,XE.SYMBOL as ATM_NAME
	FROM XR_TPL_ATOM XA, XR_TPL_RES XR, XR_ELEMENT XE
	WHERE XE.XR_ELEMENT_ID = XA.XR_ELEMENT_ID AND XA.XR_TPL_RES_ID = XR.XR_TPL_RES_ID AND XR.NAME='" . $RES3L . "'";
    $DATA['TPL'] = array();
    $res = runQuery($query);
    foreach ($res as $line) $DATA['TPL']['ATOM'][$line['XR_TPL_ATOM_ID']] = $line;

    $query = "SELECT BOND_TYPE, XR_TPL_ATOM_ID_1, XR_TPL_ATOM_ID_2
	FROM XR_TPL_BOND WHERE XR_TPL_ATOM_ID_1 IN (" . implode(",", array_keys($DATA['TPL']['ATOM'])) . ') OR XR_TPL_ATOM_ID_2 IN (' . implode(",", array_keys($DATA['TPL']['ATOM'])) . ')';
    $DATA['TPL']['BD'] = runQuery($query);


    $query = "SELECT SYMBOL,GENE_ID, PROT_IDENTIFIER, ISO_NAME, ISO_ID, DESCRIPTION, POSITION,LETTER,PROT_SEQ_POS_ID  FROM GN_ENTRY GE,GN_PROT_MAP GUM, PROT_ENTRY UE,PROT_SEQ US,PROT_SEQ_POS USP
	WHERE GE.GN_ENTRY_ID = GUM.GN_ENTRY_ID AND GUM.PROT_ENTRY_ID =UE.PROT_ENTRY_ID AND UE.PROT_ENTRY_ID = US.PROT_ENTRY_ID AND US.PROT_SEQ_ID = USP.PROT_SEQ_ID
	AND POSITION=" . $RES_ID . " AND GENE_ID=" . $GENE_ID . " AND LETTER='" . $RES_NAME . "'";
    $DATA['INFO'] = runQuery($query);
    if ($DATA['INFO'] == array()) return $DATA;

    $LIST_UNSEQID = array();
    foreach ($DATA['INFO'] as &$t) $LIST_UNSEQID[] = $t['PROT_SEQ_POS_ID'];
    $query = "SELECT XR.XR_RES_ID,XR_PROT_MAP_TYPE,XC.XR_CHAIN_ID,XR.POSITION,CHAIN_NAME, XE.FULL_COMMON_NAME,RESOLUTION,EXPR_TYPE,TITLE,TO_CHAR(DEPOSITION_DATE, 'YYYY-MM-DD' ) AS DEPOSITION_DATE FROM XR_CH_PROT_POS XCUP, XR_RES XR, XR_CHAIN XC, XR_ENTRY XE
	WHERE XCUP.PROT_SEQ_POS_ID IN (" . implode(",", $LIST_UNSEQID) . ") AND XCUP.XR_RES_ID =XR.XR_RES_ID AND XR.XR_CHAIN_ID =XC.XR_CHAIN_ID AND XC.XR_ENTRY_ID = XE.XR_ENTRY_Id";
    $res = runQuery($query);
    foreach ($res as $line)
        $DATA['XRAY'][$line['XR_RES_ID']] = $line;


    $tmp_data = runQuery('SELECT XR_INTER_TYPE_ID, INTERACTION_NAME FROM XR_INTER_TYPE');
    $INTER_TYPE = array();
    foreach ($tmp_data as $T) $INTER_TYPE[$T['XR_INTER_TYPE_ID']] = $T['INTERACTION_NAME'];
    $query = 'SELECT DISTINCT XR_RES_ID_1,XR_INTER_TYPE_ID,ATOM_LIST_1,DISTANCE,ANGLE, ATOM_LIST_2, NAME, POSITION,CHAIN_NAME,XIR.XR_RES_ID_2,XT.CLASS
	FROM  XR_INTER_RES XIR,XR_TPL_RES XT,XR_CHAIN XC
   , XR_RES XR WHERE XR_RES_ID_1 IN (' . implode(",", array_keys($DATA['XRAY'])) . ')
	AND XC.XR_CHAIN_ID = XR.XR_CHAIN_ID
	 AND XT.XR_TPL_RES_ID = XR.XR_TPL_RES_ID
	 AND  XR.XR_RES_ID = XIR.XR_RES_ID_2';
    $res = runQuery($query);
    foreach ($res as $line) {
        $line['INTER_NAME'] = $INTER_TYPE[$line['XR_INTER_TYPE_ID']];

        unset($line['XR_INTER_TYPE_ID']); //,$T['XR_RES_ID_2']);
        $DATA['XRAY'][$line['XR_RES_ID_1']]['INTER'][] = $line;
    }


    return $DATA;
}

function getIsoSequence($ISO_ID)
{
    $query = "SELECT PROT_SEQ_ID,UE.STATUS,UE.PROT_IDENTIFIER, ISO_ID, ISO_NAME,IS_PRIMARY,DESCRIPTION, CONFIDENCE
	FROM PROT_ENTRY UE, PROT_SEQ US 
    WHERE US.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND ISO_ID='" . $ISO_ID . "' AND UE.STATUS!='D' 
	ORDER BY UE.STATUS DESC,IS_PRIMARY DESC";
    $res = runQuery($query);
    $DATA = array('SEQ' => array());
    foreach ($res as $line) {
        $DATA['SEQ'][$line['PROT_SEQ_ID']]['SEQ'] = $line;
    }

    if (count($DATA['SEQ']) == 0) return $DATA;
    $query = 'SELECT * FROM TR_PROTSEQ_AL U,TRANSCRIPT T WHERE U.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND PROT_SEQ_ID IN   (' . implode(',', array_keys($DATA['SEQ'])) . ')';
    $res = runQuery($query);
    foreach ($res as $line) {
        $DATA['SEQ'][$line['PROT_SEQ_ID']]['TRANSCRIPT'][] = $line;
    }
    $query = 'SELECT * FROM PROT_SEQ_AL WHERE PROT_SEQ_REF_ID IN (' . implode(',', array_keys($DATA['SEQ'])) . ') AND PROT_SEQ_COMP_ID IN (' . implode(',', array_keys($DATA['SEQ'])) . ')';
    $DATA['SIM'] = runQuery($query);
    $res = runQuery("SELECT COUNT(*) CO, PROT_SEQ_ID FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN  (" . implode(',', array_keys($DATA['SEQ'])) . ') GROUP BY PROT_SEQ_ID');
    foreach ($res as $line) $DATA['SEQ'][$line['PROT_SEQ_ID']]['SEQ']['LEN'] = $line['CO'];

    return $DATA;
}

function getSequenceAlignment($SEQ_REF_ID, $LIST_SEQS)
{
    $DATA = array();
    $res = runQuery("SELECT * FROM PROT_SEQ_AL WHERE PROT_SEQ_REF_ID=" . $SEQ_REF_ID . " AND PROT_SEQ_COMP_ID IN (" . implode(",", $LIST_SEQS) . ')');
    if ($res == array()) return $DATA;

    $COMP_SEQ = array();
    $CURSOR = array();
    foreach ($res as $line) {
        $tmp = $line;
        unset($tmp['PROT_SEQ_AL_ID'], $tmp['PROT_SEQ_REF_ID'], $tmp['PROT_SEQ_COMP_ID']);
        $DATA[$line['PROT_SEQ_COMP_ID']] = $tmp;
        $COMP_SEQ[$line['PROT_SEQ_AL_ID']] = $line['PROT_SEQ_COMP_ID'];
    }

    $res = runQuery(" SELECT UDA.PROT_SEQ_AL_ID, USP1.POSITION as REF_POS, USP2.POSITION as COMP_POS
	FROM PROT_SEQ_al_seq UDA,  PROT_SEQ_POS USP1, PROT_SEQ_POS USP2
	WHERE uda.PROT_SEQ_ID_ref= USP1.PROT_SEQ_POS_ID
	AND uda.PROT_SEQ_ID_comp= USP2.PROT_SEQ_POS_ID
	AND uda.PROT_SEQ_al_id IN (" . implode(",", array_keys($COMP_SEQ)) . ')');
    foreach ($res as $line) $DATA[$COMP_SEQ[$line['PROT_SEQ_AL_ID']]]['AL'][$line['REF_POS']] = $line['COMP_POS'];
    foreach ($DATA as $C => &$INFO) ksort($INFO['AL']);

    $res = runQuery("SELECT PROT_SEQ_ID,PROT_SEQ_POS_ID, POSITION, LETTER
	 FROM PROT_SEQ_POS USP
	 WHERE PROT_SEQ_ID IN (" . implode(",", $COMP_SEQ) . ')');
    foreach ($res as $line) {
        $TMP[$line['PROT_SEQ_POS_ID']] = array($line['PROT_SEQ_ID'], $line['POSITION']);
        $DATA[$line['PROT_SEQ_ID']]['SEQ'][$line['POSITION']] = $line['LETTER'];
    }


    $res = runQuery("SELECT PROT_SEQ_ID,PROT_SEQ_POS_ID, POSITION, LETTER
	FROM PROT_SEQ_POS USP
	WHERE PROT_SEQ_ID =" . $SEQ_REF_ID);
    $REF_SEQ = array();
    foreach ($res as $line) {
        $REF_SEQ[$line['POSITION']] = $line['LETTER'];
        $TMP[$line['PROT_SEQ_POS_ID']] = array('REF', $line['POSITION']);
    }
    $CHUNKS = array_chunk(array_keys($TMP), 1000);
    $INTER_REF = array();
    foreach ($CHUNKS as $CHUNK) {
        $res = runQuery("SELECT PROT_SEQ_pos_id,CLASS,SUM(COUNT_INT) AS COUNT_INT,INTERACTION_NAME FROM XR_PROT_INT_STAT X,XR_INTER_TYPE XI
		WHERE X.XR_INTER_TYPE_ID = XI.XR_INTER_TYPE_ID
		AND PROT_SEQ_POS_ID IN (" . implode(',', $CHUNK) . ") AND CLASS!='WATER' GROUP BY x.PROT_SEQ_pos_id,CLASS,INTERACTION_NAME");
        foreach ($res as $line) {
            $E = &$TMP[$line['PROT_SEQ_POS_ID']];
            $T = $line;
            unset($T['PROT_SEQ_POS_ID']);
            if ($E[0] == 'REF') $INTER_REF[$E[1]][] = $T;
            else $DATA[$E[0]]['INTER'][$E[1]][] = $T;
        }
    }

    return array($REF_SEQ, $DATA, $INTER_REF);
}

function searchSimSeqBySeq($PROT_SEQ_ID, $OPTIONS)
{
    $query = 'SELECT * FROM
		(SELECT ROW_NUMBER() OVER(ORDER BY PERC_IDENTITY_COM DESC) R, P.*
		FROM (SELECT PERC_SIM,PERC_IDENTITY,PERC_SIM_COM,PERC_IDENTITY_COM,SCIENTIFIC_NAME,ISO_ID,DESCRIPTION,PROT_IDENTIFIER,SYMBOL,GENE_ID,FULL_NAME
		FROM PROT_SEQ_AL UDA, TAXON T, PROT_SEQ US,PROT_ENTRY UE
		LEFT JOIN GN_PROT_MAP GUM ON GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
		LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID=GUM.GN_ENTRY_ID
		WHERE uda.PROT_SEQ_comp_id=US.PROT_SEQ_ID
        AND US.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
        AND T.TAXON_ID = UE.TAXON_ID
        AND UDA.PROT_SEQ_REF_ID=' . $PROT_SEQ_ID . ") P) C  WHERE R<" . $OPTIONS['MAX'] . ' AND R>=' . $OPTIONS['MIN'];
    return runQuery($query);
}

/**
 * @param int $GN_ENTRY_ID
 * @return array
 * @throws Exception
 */
function getGenePubliYearStat(int $GN_ENTRY_ID): array
{
    $query = "
        SELECT EXTRACT(YEAR FROM PUBLICATION_DATE) as Y, COUNT(*) as CO
        FROM pmid_entry PE, pmid_gene_map P
        WHERE PE.PMID_ENTRY_ID = P.PMID_ENTRY_ID AND GN_ENTRY_ID=$GN_ENTRY_ID GROUP BY EXTRACT(YEAR FROM PUBLICATION_DATE)
	";
    $res = runQuery($query);
    $data = array();
    foreach ($res as $line) $data[$line['Y']] = $line['CO'];
    ksort($data);
    return $data;
}

function loadPubliAuthorData($AUTHOR)
{
    if (!is_numeric($AUTHOR)) throw new Exception("Wrong format for publication author " . $AUTHOR, ERR_TGT_USR);

    $DATA = array('ENTRY' => array(), 'ALT' => array());
    $DATA['ENTRY'] = runQuery("SELECT PE.PMID_AUTHOR_ID,PE.NAME,PE.PMID_INSTIT_ID,PE.ORCID_ID, PI.INSTIT_NAME, PI2.INSTIT_NAME as PRIM_NAME, PI.INSTIT_PRIM_ID as PRIM_ID
	FROM PMID_AUTHOR PE
   LEFT JOIN PMID_INSTIT PI ON PI.PMID_INSTIT_ID = PE.PMID_INSTIT_ID
   LEFT JOIN PMID_INSTIT PI2 ON PI.INSTIT_PRIM_ID = PI2.PMID_INSTIT_ID
   WHERE  PMID_AUTHOR_ID=" . $AUTHOR);

    if (count($DATA['ENTRY']) == 0) return $DATA;
    $PRIM_INSTIT = $DATA['ENTRY'][0]['PRIM_ID'];
    $AUTHOR_IDS = array($DATA['ENTRY'][0]['PMID_AUTHOR_ID']);
    if ($PRIM_INSTIT != '') {
        $DATA['ALT'] = runQuery("SELECT PE.PMID_AUTHOR_ID,PE.NAME,PE.PMID_INSTIT_ID,PE.ORCID_ID, PI.INSTIT_NAME, PI2.INSTIT_NAME as PRIM_NAME, PI.INSTIT_PRIM_ID as PRIM_ID
	 FROM PMID_AUTHOR PE
	LEFT JOIN PMID_INSTIT PI ON PI.PMID_INSTIT_ID = PE.PMID_INSTIT_ID
	LEFT JOIN PMID_INSTIT PI2 ON PI.INSTIT_PRIM_ID = PI2.PMID_INSTIT_ID
	WHERE PI2.PMID_INSTIT_ID=" . $PRIM_INSTIT . " AND NAME='" . $DATA['ENTRY'][0]['NAME'] . "'");

        foreach ($DATA['ALT'] as $T) $AUTHOR_IDS[] = $T['PMID_AUTHOR_ID'];
    }

    $res = runQuery("SELECT PE.PMID_AUTHOR_ID,PE.NAME,PE.PMID_INSTIT_ID,PE.ORCID_ID, PI.INSTIT_NAME, PI2.INSTIT_NAME as PRIM_NAME, PI.INSTIT_PRIM_ID as PRIM_ID
	 FROM PMID_AUTHOR PE
	LEFT JOIN PMID_INSTIT PI ON PI.PMID_INSTIT_ID = PE.PMID_INSTIT_ID
	LEFT JOIN PMID_INSTIT PI2 ON PI.INSTIT_PRIM_ID = PI2.PMID_INSTIT_ID
	WHERE  NAME LIKE '" . $DATA['ENTRY'][0]['NAME'] . "%'");
    $DATA['IDENTITY'] = array();
    foreach ($res as $rec) {
        $found = false;
        foreach ($DATA['ALT'] as &$T) if ($T['PMID_AUTHOR_ID'] == $rec['PMID_AUTHOR_ID']) {
            $found = true;
            break;
        }
        if (!$found) $DATA['IDENTITY'][] = $rec;
    }


    $DATA['PUBS'] = runQuery('SELECT PMID,PMID_AUTHOR_ID FROM PMID_ENTRY PE, PMID_AUTHOR_MAP PAM
	WHERE PE.PMID_ENTRY_ID = PAM.PMID_ENTRY_ID AND PMID_AUTHOR_ID IN (' . implode(',', $AUTHOR_IDS) . ') ORDER BY PUBLICATION_DATE DESC');
    foreach ($DATA['PUBS'] as &$T) {
        $T['INFO'] = loadPublicationData($T['PMID']);
    }
    return $DATA;
}

function getCountPubliRule($RULE_ID, $FILTERS = array())
{
    $GN_ENTRIES = array();
    $PUBLI_RULE = array($RULE_ID);
    $query_g = 'SELECT GN_ENTRY_ID FROM GN_ENTRY WHERE GENE_ID IN (';
    $query_t = "SELECT PUBLI_RULE_ID FROM PUBLI_RULE WHERE RULE_NAME IN (";
    foreach ($FILTERS as $TYPE => $LIST) {
        if (is_array($LIST))
            foreach ($LIST as $RULE_TYPE) {
                if ($TYPE == 'topic') $query_t .= "'" . $RULE_TYPE . "',";
                else if ($TYPE == 'gene') $query_g .= $RULE_TYPE . ',';
            }
    }
    if ($query_g != 'SELECT GN_ENTRY_ID FROM GN_ENTRY WHERE GENE_ID IN (') {
        $query_g = substr($query_g, 0, -1) . ')';
        $res = runQuery($query_g);
        foreach ($res as $l) $GN_ENTRIES[] = $l['GN_ENTRY_ID'];
    }
    if ($query_t != "SELECT PUBLI_RULE_ID FROM PUBLI_RULE WHERE RULE_NAME IN (") {
        $query_t = substr($query_t, 0, -1) . ')';
        $res = runQuery($query_t);
        foreach ($res as $l) $PUBLI_RULE[] = $l['PUBLI_RULE_ID'];
    }

    if ($PUBLI_RULE == array() && $GN_ENTRIES != array()) {
        $query = 'SELECT COUNT(*) CO FROM (
				SELECT  PMID, COUNT(PMID) CO FROM MV_GENE_PUBLI MPR
				WHERE GN_ENTRY_ID IN (' . implode(",", $GN_ENTRIES) . ') GROUP BY PMID ) C WHERE CO =' . count($GN_ENTRIES);
    } else if ($PUBLI_RULE != array() && $GN_ENTRIES == array()) {

        $query = 'SELECT COUNT(*) CO FROM (
			SELECT  PMID, COUNT(PMID) CO FROM MV_PUBLI_RULE MPR
			WHERE PUBLI_RULE_ID IN (' . implode(",", $PUBLI_RULE) . ') GROUP BY PMID ) C WHERE CO =' . count($PUBLI_RULE);
    } else if ($PUBLI_RULE != array() && $GN_ENTRIES != array()) {
        $query = 'SELECT COUNT(*) CO FROM (
				SELECT  PMID, COUNT(PMID) CO_P FROM MV_PUBLI_RULE MPR
				WHERE PUBLI_RULE_ID IN (' . implode(",", $PUBLI_RULE) . ') GROUP BY PMID ) P,
				(
					SELECT  PMID, COUNT(PMID) CO_G FROM MV_GENE_PUBLI MPR
					WHERE GN_ENTRY_ID IN (' . implode(",", $GN_ENTRIES) . ') GROUP BY PMID ) G WHERE 
					P.PMID = G.PMID AND 
					CO_P =' . count($PUBLI_RULE) . ' AND CO_G = ' . count($GN_ENTRIES);
    }
    return runQuery($query)[0];
}


function getPubliFromRule($RULE_ID, $PARAMS, $FILTERS = array())
{


    $GN_ENTRIES = array();
    $PUBLI_RULE = array($RULE_ID);
    $query_g = 'SELECT GN_ENTRY_ID FROM GN_ENTRY WHERE GENE_ID IN (';
    $query_t = "SELECT PUBLI_RULE_ID FROM PUBLI_RULE WHERE RULE_NAME IN (";
    foreach ($FILTERS as $TYPE => $LIST) {
        if (is_array($LIST))
            foreach ($LIST as $RULE_TYPE) {
                if ($TYPE == 'topic') $query_t .= "'" . $RULE_TYPE . "',";
                else if ($TYPE == 'gene') $query_g .= $RULE_TYPE . ',';
            }
    }
    if ($query_g != 'SELECT GN_ENTRY_ID FROM GN_ENTRY WHERE GENE_ID IN (') {
        $query_g = substr($query_g, 0, -1) . ')';
        $res = runQuery($query_g);
        foreach ($res as $l) $GN_ENTRIES[] = $l['GN_ENTRY_ID'];
    }
    if ($query_t != "SELECT PUBLI_RULE_ID FROM PUBLI_RULE WHERE RULE_NAME IN (") {
        $query_t = substr($query_t, 0, -1) . ')';
        $res = runQuery($query_t);
        foreach ($res as $l) $PUBLI_RULE[] = $l['PUBLI_RULE_ID'];
    }
    $N_MONTH = 0;
    $START = '';
    $END = '';
    $SEL = array();
    do {

        $today = new DateTime($FILTERS['DATE']); // This will create a DateTime object with the current date
        $today->modify('-' . $N_MONTH . ' month');
        $N_MONTH += 6;
        $today2 = new DateTime($FILTERS['DATE']); // This will create a DateTime object with the current date
        $today2->modify('-' . $N_MONTH . ' month');
        $START = $today->format('Y-d-m');
        $END = $today2->format('Y-d-m');
        $FINAL_DATE = '';
        $LIST_T = array();
        $DIFF_SEL = 0;
        $FIRST = true;
        $VALID = true;
        $N_D = 0;
        $DATE_MAP = array();
        foreach ($PUBLI_RULE as $R) {
            $res = runQuery('SELECT PMID,PUBLICATION_DATE FROM MV_PUBLI_RULE WHERE PUBLI_RULE_ID=' . $R . " AND PUBLICATION_DATE < TO_DATE('" . $START . "','YYYY-DD-MM') AND PUBLICATION_DATE > TO_DATE('" . $END . "','YYYY-DD-MM')");
            if (count($res) == 0) {
                $VALID = false;
                break;
            }
            $N_D++;
            $TMP_DATE_MAP = array();
            echo count($res) . "\t";
            foreach ($res as $line) {
                $TMP_DATE_MAP[$line['PMID']] = $line['PUBLICATION_DATE'];
                if ($FIRST) $LIST_T[$line['PMID']] = 1;
                else if (isset($LIST_T[$line['PMID']])) $LIST_T[$line['PMID']] += 1;
            }
            $FIRST = false;
            if ($N_D == 1) continue;
            $AT_LEAST_ONE = false;
            foreach ($LIST_T as $T) {
                if ($T == $N_D) {
                    $AT_LEAST_ONE = true;
                    break;
                }
            }
            if (!$AT_LEAST_ONE) {
                $VALID = false;
                break;
            }
        }

        if (!$VALID) continue;
        foreach ($GN_ENTRIES as $R) {
            $res = runQuery('SELECT PMID,PUBLICATION_DATE FROM MV_GENE_PUBLI WHERE GN_ENTRY_ID=' . $R . " AND PUBLICATION_DATE < TO_DATE('" . $START . "','YYYY-DD-MM') AND PUBLICATION_DATE > TO_DATE('" . $END . "','YYYY-DD-MM')");
            if (count($res) == 0) {
                $VALID = false;
                break;
            }
            echo count($res) . "\t";
            foreach ($res as $line) {
                $TMP_DATE_MAP[$line['PMID']] = $line['PUBLICATION_DATE'];
                if ($FIRST) $LIST_T[$line['PMID']] = 1;
                else if (isset($LIST_T[$line['PMID']])) $LIST_T[$line['PMID']] += 1;
            }
            $FIRST = false;
        }
        if (!$VALID) continue;

        $CO = count($PUBLI_RULE) + count($GN_ENTRIES);
        $NEW_SEL = array();
        foreach ($LIST_T as $P => $N)
            if ($N == $CO) {
                $NEW_SEL[] = $P;
                $DATE_MAP[strtotime($TMP_DATE_MAP[$P])][] = $P;
            }

        krsort($DATE_MAP);

        //	print_r($DATE_MAP);exit;
        if (count($NEW_SEL) + count($SEL) > $PARAMS['PER_PAGE']) {
            //echo "IN";
            $NEED = $PARAMS['PER_PAGE'] - count($SEL);
            foreach ($DATE_MAP as $D => &$LIST_D) {
                //	echo $FILTERS['SHIFT']."\n";;
                $FINAL_DATE = $D;
                $DIFF_SEL = 0;
                sort($LIST_D);
                foreach ($LIST_D as $K) {
                    if ($FILTERS['SHIFT'] > 0) {
                        $DIFF_SEL++;
                        $FILTERS['SHIFT']--;
                        continue;
                    }
                    $SEL[] = $K;
                    $NEED -= 1;
                    $DIFF_SEL++;
                    if ($NEED == 0) break;
                }
                if ($NEED == 0) break;
            }
        } else {
            foreach ($DATE_MAP as $D => &$LIST_D) {
                sort($LIST_D);
                foreach ($LIST_D as $K) {
                    $SEL[] = $K;
                }
            }
        }
        echo count($LIST_T) . "\tSEL:" . count($SEL) . "\n";
    } while (count($SEL) < $PARAMS['PER_PAGE'] && $N_MONTH < 600);
    //sort($SEL);

    $tmp = array('DATE' => $START, 'DIFF' => $DIFF_SEL, 'LIST' => $SEL);


    return $tmp;
}
function getClinicalTrialInfo($NCT_ID)
{
    $res = runQuery("SELECT * FROM CLINICAL_TRIAL WHERE TRIAL_ID = '" . $NCT_ID . "'");
    $DATA = array();
    
    if (count($res) == 0) return $DATA;
    if ($res[0]['DETAILS']=='') throw new Exception('No trial information');
    $DATA = json_decode($res[0]['DETAILS'], true);

    unset($res[0]['DETAILS']);
    $DATA['ENTRY'] = $res[0];
    

    $res = runQuery("SELECT DRUG_ENTRY_ID,DISEASE_ENTRY_ID,GN_ENTRY_ID FROM CLINICAL_TRIAL_DRUG CTD, DRUG_DISEASE DD
	WHERE DD.DRUG_DISEASE_ID = CTD.DRUG_DISEASE_ID AND CLINICAL_TRIAL_ID=" . $DATA['ENTRY']['CLINICAL_TRIAL_ID']);
    foreach ($res as $line) {
        $DATA['DRUG'][$line['DRUG_ENTRY_ID']] = array();
        $DATA['DISEASE'][$line['DISEASE_ENTRY_ID']] = array();
        $DATA['TARGET'][$line['GN_ENTRY_ID']] = array();
    }


    $DATA['ARMS']=runQuery("SELECT * FROM clinical_trial_arm where  clinical_trial_id = ". $DATA['ENTRY']['CLINICAL_TRIAL_ID']);
    $res=runQuery("SELECT * FROM clinical_trial_intervention cti  WHERE clinical_trial_id = ". $DATA['ENTRY']['CLINICAL_TRIAL_ID']);
    foreach ($res as $line)
    {
        $DATA['INTERVENTION'][$line['CLINICAL_TRIAL_INTERVENTION_ID']]=$line;
    }
    if (isset($DATA['INTERVENTION']))
    {
        $res=runQuery("SELECT * FROM clinical_trial_intervention_drug_map ctdm  WHERE clinical_trial_intervention_id IN (".implode(',',array_keys($DATA['INTERVENTION'])).')');
        foreach ($res as $line)
        {
            $DATA['INTERVENTION'][$line['CLINICAL_TRIAL_INTERVENTION_ID']]['DRUG'][]=$line['DRUG_ENTRY_ID'];
            if (!isset($DATA['DRUG'][$line['DRUG_ENTRY_ID']]))$DATA['DRUG'][$line['DRUG_ENTRY_ID']]=array();
        }
    }
    
    $DATA['ARM_INTERVENTION']=runQuery("SELECT * FROM clinical_trial_arm_intervention_map where  clinical_trial_id = ". $DATA['ENTRY']['CLINICAL_TRIAL_ID']);


    $res=runQuery("SELECT * FROM clinical_trial_condition where clinical_trial_id = ". $DATA['ENTRY']['CLINICAL_TRIAL_ID']);

foreach ($res as $line)$DATA['DISEASE'][$line['DISEASE_ENTRY_ID']]=array();
    if (isset($DATA['DRUG']))
        foreach ($DATA['DRUG'] as $DR => &$INFO) {
            if ($DR != '') {
                $INFO = getDrugInfo($DR);
                $INFO['DESC']=getDrugDescription($DR);
            }
        }
    if (isset($DATA['TARGET']))
        foreach ($DATA['TARGET'] as $DR => &$INFO) {
            if ($DR != '') {

                $T = getGeneInfo($DR);
                $INFO = $T[0];
            }
        }
    if (isset($DATA['DISEASE']))
        foreach ($DATA['DISEASE'] as $DR => &$INFO) {
    if ($DR!='')
            $INFO = runQuery("SELECT DISEASE_NAME FROM DISEASE_ENTRY WHERE DISEASE_ENTRY_ID = " . $DR)[0];
        }

    return $DATA;
}
function getClinicalTrialById($CLI_ID)
{

    $query="SELECT clinical_trial_id FROM CLINICAL_TRIAL_ALIAS WHERE LOWER(ALIAS_NAME)  LIKE '%".strtolower($CLI_ID)."%'";
    
    
    $res=runQuery($query);
    
    $DATA=array();
    foreach ($res as $line)$DATA[$line['CLINICAL_TRIAL_ID']]=array();
if ($DATA==array())return $DATA;
$query="SELECT ct.clinical_trial_id, trial_id, clinical_phase,clinical_status,official_title,alias_type,alias_name,start_date 
FROM CLINICAL_TRIAL CT, clinical_trial_alias cta where ct.clinical_trial_id =cta.clinical_trial_id
 AND ct.clinical_trial_id IN (".implode(',',array_keys($DATA)).')';
 
 
    $res = runQuery($query);
    
    foreach ($res as $line)
    {
        $DATA[$line['CLINICAL_TRIAL_ID']][]=$line;
    }
     return $DATA;
    
}

function news_search_name($NAME)
{
    global $COMMON_WORDS;
    $tab=explode(" ",$NAME);
    $tmp=array();$list=array();
    if (count($tab)<10)
    {
    foreach ($tab as $n)
    {
        if (in_array(strtolower($n),$COMMON_WORDS))continue;
    $query="SELECT news_id FROM NEWS WHERE  LOWER(news_title)  LIKE '%".strtolower(str_replace("'","''",$n))."%'  ";
    
    $res=runQuery($query);
    foreach ($res as $line)
    {
        if(!isset($tmp[$line['NEWS_ID']]))$tmp[$line['NEWS_ID']]=1;
        else $tmp[$line['NEWS_ID']]++;

        $list[]=$line['NEWS_ID'];
    }
    }
    $ORDER=array();
    foreach ($tmp as $id=>$co)$ORDER[$co][]=$id;
    }
    else
    {
        $query="SELECT NEWS_ID FROM NEWS WHERE  LOWER(news_title)  LIKE '%".strtolower($NAME)."%'  ";
        
    $res=runQuery($query);
    
    foreach ($res as $line)
    {
        $list[]=$line['NEWS_ID'];
        $ORDER[1][]=$line['NEWS_ID'];
    }
}
    
   
    
    $DATA=array();
    
if ($ORDER==array())return $DATA;
$query="SELECT news_id,news_title,source_name,news_added_date, news_hash NEWS_HASH  
FROM NEWS CT, SoURCE S where ct.source_id =s.source_id
 AND ct.news_id IN (".implode(',',$list).')';
 
 
    $res = runQuery($query);
    $tmp=array();
    foreach ($res as $line)
    {
        $tmp[$line['NEWS_ID']]=$line;
    }
    krsort($ORDER);
    
    $is_first=true;
    foreach ($ORDER as &$listing){
        
    foreach ($listing as $id)$DATA[$id]=$tmp[$id];
    //if (!$is_first)
    break;
    //if ($is_first)$is_first=false;
    
    }
     return $DATA;
}

function getClinicalTrialByName($NAME)
{
    global $COMMON_WORDS;
    $tab=explode(" ",$NAME);
    $tmp=array();$list=array();
    if (count($tab)<10)
    {
    foreach ($tab as $n)
    {
        if (in_array(strtolower($n),$COMMON_WORDS))continue;
    $query="SELECT clinical_trial_id FROM CLINICAL_TRIAL WHERE  LOWER(OFFICIAL_TITLE)  LIKE '%".strtolower(str_replace("'","''",$n))."%'  ";
    $res=runQuery($query);
    foreach ($res as $line)
    {
        if(!isset($tmp[$line['CLINICAL_TRIAL_ID']]))$tmp[$line['CLINICAL_TRIAL_ID']]=1;
        else $tmp[$line['CLINICAL_TRIAL_ID']]++;

        $list[]=$line['CLINICAL_TRIAL_ID'];
    }
    }
    $ORDER=array();
    foreach ($tmp as $id=>$co)$ORDER[$co][]=$id;
    }
    else
    {
        $query="SELECT clinical_trial_id FROM CLINICAL_TRIAL WHERE  LOWER(OFFICIAL_TITLE)  LIKE '%".strtolower($NAME)."%'  ";
        
    $res=runQuery($query);
    
    foreach ($res as $line)
    {
        $list[]=$line['CLINICAL_TRIAL_ID'];
        $ORDER[1][]=$line['CLINICAL_TRIAL_ID'];
    }
}
    
   
    
    $DATA=array();
    
if ($ORDER==array())return $DATA;
$query="SELECT ct.clinical_trial_id, trial_id, clinical_phase,clinical_status,official_title,alias_type,alias_name,start_date 
FROM CLINICAL_TRIAL CT, clinical_trial_alias cta where ct.clinical_trial_id =cta.clinical_trial_id
 AND ct.clinical_trial_id IN (".implode(',',$list).')';
 
 
    $res = runQuery($query);
    $tmp=array();
    foreach ($res as $line)
    {
        $tmp[$line['CLINICAL_TRIAL_ID']][]=$line;
    }
    krsort($ORDER);
    
    $is_first=true;
    foreach ($ORDER as &$listing){
        
    foreach ($listing as $id)$DATA[$id]=$tmp[$id];
    //if (!$is_first)
    break;
    //if ($is_first)$is_first=false;
    
    }
     return $DATA;
    
}
function getCompoundsFromDrug($DRUG_ENTRY_ID)
{
    $res = runQuery("SELECT * FROM  DRUG_ENTRY DE
    LEFT JOIN SM_ENTRY SE ON  DE.SM_ENTRY_ID = SE.SM_ENTRY_ID
    WHERE DE.DRUG_ENTRY_ID =" . $DRUG_ENTRY_ID );
   
$DATA=array();$INCHI_KEY='';
    if (isset($res[0]['SM_ENTRY_ID']))
    {$SM_ENTRY_ID=$res[0]['SM_ENTRY_ID'];
        $INCHI_KEY=$res[0]['INCHI_KEY'];
        $DATA[$res[0]['SM_ENTRY_ID']] = true;
    }
    if ($DATA==array())return $DATA;
    if ($INCHI_KEY=='')return $DATA;


        $tab = explode("-", $INCHI_KEY);
        if ($tab[0]=='')return $DATA;
        
        $query="SELECT * FROM SM_MOLECULE SM,
       SM_ENTRY SE
    LEFT JOIN SM_COUNTERION SC ON SE.SM_COUNTERION_ID = SC.SM_COUNTERION_ID 
    WHERE SM.SM_MOLECULE_ID = SE.SM_MOLECULE_ID AND SM_ENTRY_ID != " . $SM_ENTRY_ID . " AND INCHI_KEY LIKE '" . $tab[0] . "%'";
    
    $res = runQuery($query);
        foreach ($res as $line)if ($line['SM_ENTRY_ID']!='')$DATA[$line['SM_ENTRY_ID']] = true;
        
    return array_keys($DATA);

}
function getDrugInfo($DRUG_ENTRY_ID)
{

    $res=runQuery("SELECT * FROM DRUG_ENTRY WHERE DRUG_ENTRY_ID=" . $DRUG_ENTRY_ID);
    if ($res==array())return array();
    $DATA=$res[0];
    
    $res=runQuery("SELECT * FROM drug_mol_entity_map dmem, molecular_entity me, sm_molecule sm, sm_entry se 
    LEFT JOIN sm_counterion sc on sc.sm_counterion_id =se.sm_counterion_id 
    WHERE se.sm_molecule_id = sm.sm_molecule_id 
    AND se.md5_hash=me.molecular_structure_hash 
    and me.molecular_entity_id = dmem.molecular_entity_id
    AND drug_entry_Id = " . $DRUG_ENTRY_ID);
    
    foreach ($res as $line)
    $DATA['SM'][$line['SM_ENTRY_ID']]=$line;

    if (!isset($DATA['SM'])||$DATA['SM']==array())return $DATA;
    $res=runQuery("SELECT s.source_id,sm_entry_id, description_type, description_text, source_name
     FROM sm_description sm, source s where s.source_id = sm.source_id AND sm_entry_id IN (".implode(',',array_keys($DATA['SM'])).')');
    foreach ($res as $line)
    $DATA['SM'][$line['SM_ENTRY_ID']]['DESC'][$line['SOURCE_ID']]=$line;
    return $DATA;
}

/**
 * @param int $GN_ENTRY_ID
 * @return void[]
 * @throws Exception
 */
function getGeneInfo($GN_ENTRY_ID): array
{
    $query = "
		SELECT GN_ENTRY_ID,GENE_ID,SYMBOL,FULL_NAME,SCIENTIFIC_NAME
		FROM mv_gene_sp 
		WHERE GN_ENTRY_ID=$GN_ENTRY_ID
		";
    return runQuery($query);
}

function getClinicalTrialGene($GN_ENTRY_ID)
{
    $query = "SELECT DD.DRUG_DISEASE_ID, CLINICAL_PHASE, CLINICAL_STATUS,
    OFFICIAL_TITLE, TO_CHAR(START_DATE,'YYYY-MM-DD') START_DATE, 
    details->'protocolSection'->'statusModule' as STATUS,
     DISEASE_NAME,TRIAL_ID,DISEASE_TAG,D.IS_APPROVED, D.IS_WITHDRAWN,DRUG_PRIMARY_NAME
	FROM DISEASE_ENTRY EP, DRUG_DISEASE DD, DRUG_ENTRY D,
	CLINICAL_TRIAL C, CLINICAL_TRIAL_DRUG CD 
	WHERE  C.CLINICAL_TRIAL_ID = CD.CLINICAL_TRIAL_ID AND D.DRUG_ENTRY_ID = DD.DRUG_ENTRY_ID AND
	CD.DRUG_DISEASE_ID = DD.DRUG_DISEASE_ID
	AND EP.DISEASE_ENTRY_ID = dD.DISEASE_ENTRY_ID
    
    AND details is not null
	AND GN_ENTRY_ID=" . $GN_ENTRY_ID;

    $res = runQuery($query);

    return $res;
}

function getClinicalTrialDisease($DISEASE_ENTRY_ID,$WITH_CHILD=false)
{
    $LIST=array($DISEASE_ENTRY_ID);
    if ($WITH_CHILD)
    {
        
        $TMP=getChildDisease($DISEASE_ENTRY_ID);
        foreach ($TMP as $E)
        {
            
            $LIST[]=$E['DISEASE_ENTRY_ID'];
        }
        
        
    }
    

    $res = runQuery("SELECT DISEASE_NAME,DISEASE_TAG,DD.DRUG_DISEASE_ID, CLINICAL_PHASE,OFFICIAL_TITLE, CLINICAL_STATUS, TO_CHAR(START_DATE,'YYYY-MM-DD') START_DATE,  details->'protocolSection'->'statusModule' as STATUS,SYMBOL, GENE_ID,FULL_NAME,TRIAL_ID,D.IS_APPROVED, D.IS_WITHDRAWN,DRUG_PRIMARY_NAME
	FROM DISEASE_ENTRY EP, DRUG_DISEASE DD LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = DD.GN_ENTRY_ID, DRUG_ENTRY D,
	CLINICAL_TRIAL C, CLINICAL_TRIAL_DRUG CD 
	WHERE  C.CLINICAL_TRIAL_ID = CD.CLINICAL_TRIAL_ID AND D.DRUG_ENTRY_ID = DD.DRUG_ENTRY_ID AND
	CD.DRUG_DISEASE_ID = DD.DRUG_DISEASE_ID
    AND details is not null
	AND EP.DISEASE_ENTRY_ID = dD.DISEASE_ENTRY_ID
	AND DD.DISEASE_ENTRY_ID IN (".implode(',',$LIST).')');

    return $res;
}



function getCompoundInchi($Inchi)
{
    if ($Inchi == '') return array();
    $res = runQuery("SELECT * FROM SM_ENTRY E,SM_SOURCE N WHERE E.SM_ENTRY_ID = N.SM_ENTRY_ID AND (INCHI LIKE '%" . $Inchi . "%' OR INCHI_KEY LIKE '%" . $Inchi . "%')");

    if (count($res) == 0) return array();
    $DATA = array();
    $SM_LIST = array();
    foreach ($res as $line) {
        if (isset($SM_LIST[$line['SM_ENTRY_ID']])) continue;
        $SM_LIST[$line['SM_ENTRY_ID']] = true;
        $T = getCompoundInfo($line['SM_NAME']);
        foreach ($T as $V) $DATA[$V['INI']['SM_ENTRY_ID']] = $V;
    }
    return array_values($DATA);
}

function getGenePubStat($GN_ENTRY_ID)
{
    $res=runQuery("SELECT count(*) co, EXTRACT(year FROM publication_date) y FROM pmid_entry pe WHERE pmid_entry_id IN (SELECT pmid_entry_id FROM pmid_gene_map WHERE gn_entry_id=".$GN_ENTRY_ID.") group by EXTRACT(year FROM publication_date)");
$DATA=array();
    foreach ($res as $line)
    {
        $DATA[$line['Y']]=$line['CO'];
    }
    return $DATA;
}


function getCompoundInfo($CPD_NAME)
{
    if ($CPD_NAME == '') return array();
    $DATA = array();
    // BUILD OUT COMPOUND INI DATA
    $res = runQuery("SELECT * FROM SM_SOURCE WHERE SM_NAME = '" . $CPD_NAME . "'");
    
    if ($res==array())
    {

    $res = runQuery("SELECT * FROM SM_SOURCE WHERE SM_NAME LIKE '" . $CPD_NAME . "%'");
    }
    foreach ($res as $line) $DATA[$line['SM_ENTRY_ID']]['INI'] = $line;

    $query = "SELECT DISTINCT * FROM DRUG_ENTRY DE,  SM_ENTRY SE
    WHERE  DE.SM_ENTRY_ID = SE.SM_ENTRY_ID   AND (LOWER(DRUG_PRIMARY_NAME)=LOWER('" . $CPD_NAME . "') ";
    if ($DATA != array()) $query .= 'OR SE.SM_ENTRY_ID IN (' . implode(',', array_keys($DATA)) . ')';
    $res = runQuery($query . ')');
    foreach ($res as $line) {
        $DATA[$line['SM_ENTRY_ID']]['INI'] = $line;
    }

    if ($DATA == array()) return $DATA;

    // BUILD OUT COMPOUND STRUCTURE DATA
    $res = runQuery("SELECT * FROM SM_MOLECULE SM,
	SM_ENTRY SE
	LEFT JOIN SM_COUNTERION SC ON SE.SM_COUNTERION_ID = SC.SM_COUNTERION_ID 
	WHERE SM.SM_MOLECULE_ID = SE.SM_MOLECULE_ID AND SM_ENTRY_ID IN (" . implode(',', array_keys($DATA)) . ')');
    $SM_ENTRY_ID = -1;

    foreach ($res as $line) {
        $SM_ENTRY_ID = $line['SM_ENTRY_ID'];
        $DATA[$line['SM_ENTRY_ID']]['STRUCTURE'] = $line;
    }

    // BUILD OUT COMPOUND NAME DATA
    $res = runQuery("SELECT SM_ENTRY_ID, SM_NAME,SOURCE_NAME FROM SM_SOURCE SS, SOURCE S WHERE S.SOURCE_ID = Ss.SOURCE_ID AND SM_ENTRY_ID  IN (" . implode(',', array_keys($DATA)) . ')');
    foreach ($res as $line) $DATA[$line['SM_ENTRY_ID']]['NAME'][] = $line;

    // BUILD OUT COMPOUND DRUG DATA 
    $res = runQuery("SELECT * FROM DRUG_ENTRY DE WHERE SM_ENTRY_ID   IN (" . implode(',', array_keys($DATA)) . ')');
    $DR_MAP = array();
    foreach ($res as $line) {
        $DR_MAP[$line['DRUG_ENTRY_ID']][] = $line['SM_ENTRY_ID'];
        $DATA[$line['SM_ENTRY_ID']]['DRUG'][] = $line;
    }
    // IF DRUG DATA PRESENT ADD DrugBank/OpenTarget data to DATA[entryid][NAME] array
    if ($DR_MAP != array()) {
        $res = runQuery("SELECT * FROM DRUG_NAME DE WHERE DRUG_ENTRY_ID    IN (" . implode(',', array_keys($DR_MAP)) . ')');

        foreach ($res as $line) {

            foreach ($DR_MAP[$line['DRUG_ENTRY_ID']] as $SM) {
                $DATA[$SM]['NAME'][] = array('SOURCE_NAME' => 'DrugBank/OpenTarget', 'SM_NAME' => $line['DRUG_NAME'], 'IS_PRIMARY' => $line['IS_PRIMARY'], 'IS_TRADENAME' => $line['IS_TRADENAME']);
            }
        }
    }
    // SPLIT INCHI KEY AND RETRIEVE ALT STRUCTURE AND ALT SALT VALUES 
    $tab = explode("-", $DATA[$SM_ENTRY_ID]['STRUCTURE']['INCHI_KEY']);
    $res = runQuery("SELECT * FROM SM_MOLECULE SM,
					 SM_ENTRY SE
					LEFT JOIN SM_COUNTERION SC ON SE.SM_COUNTERION_ID = SC.SM_COUNTERION_ID 
					WHERE SM.SM_MOLECULE_ID = SE.SM_MOLECULE_ID AND SM_ENTRY_ID != " . $SM_ENTRY_ID . " AND INCHI_KEY LIKE '" . $tab[0] . "%'");
    foreach ($res as $line) $DATA[$SM_ENTRY_ID]['ALT_STRUCTURE'][$line['SM_ENTRY_ID']] = $line;
    $res = runQuery("SELECT * FROM SM_MOLECULE SM,
					SM_ENTRY SE
					LEFT JOIN SM_COUNTERION SC ON SE.SM_COUNTERION_ID = SC.SM_COUNTERION_ID 
					WHERE SM.SM_MOLECULE_ID = SE.SM_MOLECULE_ID AND SM.SM_MOLECULE_ID=" . $DATA[$SM_ENTRY_ID]['STRUCTURE']['SM_MOLECULE_ID']);

    foreach ($res as $line) $DATA[$SM_ENTRY_ID]['ALT_SALT'][$line['SM_ENTRY_ID']] = $line;

    // BUILD OUT DESC DATA
    $res = runQuery("SELECT DESCRIPTION_TEXT,DESCRIPTION_TYPE, SOURCE_NAME
	 FROM SM_DESCRIPTION SD, SOURCE SS 
	 WHERE SS.SOURCE_ID = SD.SOURCE_ID AND SM_ENTRY_ID=" . $SM_ENTRY_ID);
    foreach ($res as $l) {
        $DATA[$SM_ENTRY_ID]['DESC'] = $l;
    }


    return array_values($DATA);
}

function getDrugDescription($DRUG_ENTRY_ID)
{
$DATA=array();
    $res=runQuery("SELECT * FROM drug_description d, source s where s.source_id=d.source_id AND drug_entry_id = ".$DRUG_ENTRY_ID);
    foreach ($res as $line) $DATA['DESCRIPTION'][]=$line;

    $res=runQuery("SELECT drug_entry_id FROM sm_Description sd, sm_entry se, molecular_entity me, drug_mol_entity_map medm 
    where sd.sm_entry_id = se.sm_entry_id and se.md5_hash = me.molecular_structure_hash and me.molecular_entity_id = medm.molecular_entity_id and drug_entry_id = ".$DRUG_ENTRY_ID);
    
    foreach ($res as $line) $DATA['SM_DESCRIPTION'][]=$line;

   return $DATA;
}

function getDrugPortalInfo($DRUG_ENTRY_ID)
{
    if ($DRUG_ENTRY_ID == '') return array();
    $DATA = array();
    
    

    $DATA['ENTRY'] = runQuery("SELECT * FROM DRUG_ENTRY WHERE  DRUG_ENTRY_ID = " . $DRUG_ENTRY_ID);
    $res = runQuery("SELECT DRUG_NAME,IS_PRIMARY,IS_TRADENAME,SOURCE_NAME FROM DRUG_NAME DN,SOURCE S WHERE S.SOURCE_ID=DN.SOURCE_ID AND DRUG_ENTRY_ID = " . $DRUG_ENTRY_ID);
    foreach ($res as $line) 
    {
        $RULE=($line['IS_PRIMARY'] == 'T') ? 'PRIMARY' : ($line['IS_TRADENAME'] == 'T' ? 'TRADENAME' : 'SYNONYM');
        $DATA['NAME'][$RULE][$line['DRUG_NAME']][]=$line['SOURCE_NAME'];
    }

    $res=runQuery("SELECT drug_extdb_value, s.source_name as source_extdb, s2.source_name providing_source
                     FROM drug_extdb de, source s, source s2 
                     WHERE s.source_id = de.source_id
                      AND s2.source_id = de.source_origin_id 
                      AND drug_entry_id = " . $DRUG_ENTRY_ID);
    foreach ($res as $line)
    {
        $DATA['EXTDB'][$line['SOURCE_EXTDB']][$line['DRUG_EXTDB_VALUE']][]=$line['PROVIDING_SOURCE'];
    }

$DATA['TYPE']=runQuery("SELECT dt.* FROM drug_type_map dtm, drug_type dt where dt.drug_type_id = dtm.drug_type_id AND drug_entry_Id = ".$DRUG_ENTRY_ID);

    $res=runQuery("SELECT DISTINCT ae.*, ah.atc_level , ah.atc_level_left, ah.atc_level_right
    FROM atc_entry ae, atc_hierarchy ah,atc_hierarchy ad, drug_atc_map d 
    where d.atc_entry_Id =ad.atc_entry_id 
    AND ae.atc_entry_id = ah.atc_entry_id 
    AND ah.atc_level_left <= ad.atc_level_left 
    AND ah.atc_level_right >=ad.atc_level_right 
    and drug_entry_id=".$DRUG_ENTRY_ID."
     ORDER BY ah.atc_level ASC");
    $DATA['ATC']=array('ENTRY'=>array(),'ROOT'=>array());
    $RULES=array();
    $NEXT_RULES=array();
    $CURR_LEVEL=0;
    foreach ($res as $line)
    {
        if ($CURR_LEVEL!=$line['ATC_LEVEL'])
        {
            
            $CURR_LEVEL=$line['ATC_LEVEL'];
            $RULES=$NEXT_RULES;
            $NEXT_RULES=array();
        }
        $DATA['ATC']['ENTRY'][$line['ATC_CODE']]=array('TITLE'=>$line['ATC_TITLE']);
        if ($line['ATC_LEVEL']==1)$DATA['ATC']['ROOT'][]=$line['ATC_CODE'];
        else
        foreach ($RULES as $R)
        {
            if ($line['ATC_LEVEL_LEFT']>$R['ATC_LEVEL_LEFT'] && $line['ATC_LEVEL_RIGHT']<$R['ATC_LEVEL_RIGHT'])
            {
                $DATA['ATC']['ENTRY'][$R['ATC_CODE']]['CHILD'][]=$line['ATC_CODE'];
            }
        }
        $NEXT_RULES[$line['ATC_CODE']]=$line;

    }
    
    $res=runQuery("SELECT * FROM drug_description d, source s where s.source_id=d.source_id AND drug_entry_id = ".$DRUG_ENTRY_ID);
    foreach ($res as $line) $DATA['DESCRIPTION'][]=$line;
   

   
    $res=runQuery("SELECT news_hash, document_hash,mime_type, document_name ,source_name
    FROM news_document ned,news_drug_map nd, news n, source s 
    where ned.news_id = nd.news_id AND nd.news_id = n.news_id 
    AND n.source_id = s.source_id 
    AND Source_name  IN ('Liver Tox','Gene Reviews') 
    AND mime_type='application/pdf'
   AND DRUG_ENTRY_ID = ".$DRUG_ENTRY_ID);
   
    foreach ($res as $line)$DATA['DOCS'][]=$line;


    return $DATA;
}

function getDrugPubliStat($DRUG_ENTRY_ID)
{


    $DATA=array();
    $res=runQuery("SELECT count(*) co, EXTRACT(year FROM publication_date) y FROM pmid_entry pe WHERE pmid_entry_id IN (SELECT pmid_entry_id FROM pmid_drug_map WHERE drug_entry_id=".$DRUG_ENTRY_ID.") group by EXTRACT(year FROM publication_date)");

    foreach ($res as $line) $DATA['PUBLICATION'][$line['Y']]=$line['CO'];
    return $DATA;
}


function getDrugNews($DRUG_ENTRY_ID)
{
    $query = "select n.news_id, news_title,news_content,news_release_date,last_name,first_name,email,source_name FROM  news_drug_map ndm, news n LEFT JOIN web_user w ON w.web_user_Id = n.user_id,source s where s.source_id =n.source_id AND  n.news_id = ndm.news_id AND ndm.drug_entry_Id = " . $DRUG_ENTRY_ID . " ORDER BY news_added_date DESC LIMIT 5";
    $DATA = array();
    $res = runQuery($query);
    foreach ($res as $line) {
        $line['hash_news'] = md5($line['NEWS_TITLE'] . '|' . $line['SOURCE_NAME'] . '|' . $line['NEWS_RELEASE_DATE']);
        $DATA[$line['NEWS_ID']] = $line;
    }
    return $DATA;
}

function getGeneNews($GN_ENTRY_ID)
{
    $query = "select n.news_id, news_title,news_content,news_release_date,last_name,first_name,email,source_name FROM  news_gn_map ndm, news n LEFT JOIN web_user w ON w.web_user_Id = n.user_id,source s where s.source_id =n.source_id AND  n.news_id = ndm.news_id AND ndm.gn_entry_Id = " . $GN_ENTRY_ID . " ORDER BY news_added_date DESC LIMIT 5";
    $DATA = array();
    $res = runQuery($query);
    foreach ($res as $line) {
        $line['hash_news'] = md5($line['NEWS_TITLE'] . '|' . $line['SOURCE_NAME'] . '|' . $line['NEWS_RELEASE_DATE']);
        $DATA[$line['NEWS_ID']] = $line;
    }
    return $DATA;
}


function getClinicalTrialsCountsDisease($DISEASE_ENTRY_ID)
{
   $DATA['ITSELF']=runQuery("SELECT clinical_phase,clinical_status, EXTRACT(YEAR FROM start_date), count(*) co 
    FROM clinical_trial_condition ctc, 
    clinical_Trial ct 
    WHERE ct.clinical_trial_id = ctc.clinical_trial_id 
    AND disease_entry_id=".$DISEASE_ENTRY_ID." 
    GROUP BY clinical_status,clinical_phase, EXTRACT(YEAR FROM start_date)  ");

    $DATA['ALL']=runQuery("SELECT clinical_phase,clinical_status, EXTRACT(YEAR FROM start_date), count(*) co 
    FROM clinical_trial_condition ctc, 
    clinical_Trial ct 
    WHERE ct.clinical_trial_id = ctc.clinical_trial_id 
    AND disease_entry_id IN 
        (SELECT distinct dh2.disease_entry_id FROM 
            disease_hierarchy dh1,
            disease_hierarchy dh2
            where dh1.disease_entry_id=".$DISEASE_ENTRY_ID." 
            AND dh2.disease_level_left >= dh1.disease_level_left
            AND dh2.disease_level_right <= dh1.disease_level_right)
    GROUP BY clinical_status,clinical_phase, EXTRACT(YEAR FROM start_date)  ");

    return $DATA;
}





function getClinicalTrialsCountsDrug($DRUG_ENTRY_ID)
{
    $res=runQuery("SELECT clinical_phase,clinical_status, EXTRACT(YEAR FROM start_date),arm_type, count(*) co 
    FROM clinical_trial_intervention_drug_map ctidm, 
    clinical_trial_arm_intervention_map ctaim, 
    clinical_trial_arm cta, 
    clinical_Trial ct 
    WHERE ct.clinical_trial_id = cta.clinical_trial_id 
    AND cta.clinical_trial_arm_id = ctaim.clinical_trial_arm_id 
    AND ctaim.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id 
    AND drug_entry_Id=".$DRUG_ENTRY_ID." 
    GROUP BY clinical_status,clinical_phase, EXTRACT(YEAR FROM start_date) ,arm_type ");
    return $res;
}


function getClinicalTrialStatDrug($DRUG_ENTRY_ID,$ONGOING_ONLY=false)
{
    $query = "SELECT DISTINCT DISEASE_ENTRY_ID, DD.DRUG_DISEASE_ID, CLINICAL_PHASE, 
    CLINICAL_STATUS, 
    TO_CHAR(START_DATE,'YYYY-MM-DD') START_DATE, 
    EXTRACT(YEAR FROM START_DATE) year_start,
    GN_ENTRY_ID,
    TRIAL_ID
	FROM   
    DRUG_DISEASE DD ,
    CLINICAL_TRIAL C, 
    CLINICAL_TRIAL_DRUG CD 
	WHERE  
    C.CLINICAL_TRIAL_ID = CD.CLINICAL_TRIAL_ID AND 
    CD.DRUG_DISEASE_ID = DD.DRUG_DISEASE_ID AND 
    DD.DRUG_ENTRY_ID = " . $DRUG_ENTRY_ID;
    if ($ONGOING_ONLY)
    {
        $query.=" AND LOWER(CLINICAL_STATUS) IN ('available','recruiting','active, not recruiting','not yet recruiting')"; 
    }

$time=microtime_float();
    $res = runQuery($query);
   
    $DATA=array('GENE'=>array(),'DISEASE'=>array(),'LIST'=>array());
    foreach ($res as $line)
    {
        $FOUND=false;
        foreach ($DATA['LIST'] as $L)
        {
            if ($L[0]!=$line['GN_ENTRY_ID']||$L[1]!=$line['DISEASE_ENTRY_ID'])continue;
            $FOUND=true;
            break;
        }
        if (!$FOUND)        $DATA['LIST'][]=array($line['GN_ENTRY_ID'],$line['DISEASE_ENTRY_ID']);
        if ($line['GN_ENTRY_ID']!='' && !isset($DATA['GENE'][$line['GN_ENTRY_ID']]))$DATA['GENE'][$line['GN_ENTRY_ID']]=array();
        if ($line['DISEASE_ENTRY_ID']!='' && !isset($DATA['DISEASE'][$line['DISEASE_ENTRY_ID']]))$DATA['DISEASE'][$line['DISEASE_ENTRY_ID']]= array();
        if ($line['CLINICAL_PHASE']!='')$line['CLINICAL_PHASE']=str_replace("PHASE","",$line['CLINICAL_PHASE']);
       
        if ($line['CLINICAL_STATUS']!='' &&in_array(strtolower($line['CLINICAL_STATUS']),array('available','recruiting','active, not recruiting','not yet recruiting')))
        {
            $DATA['ONGOING'][]=$line;
        }
       if (!isset( $DATA['PHASES'][$line['CLINICAL_PHASE']])) $DATA['PHASES'][$line['CLINICAL_PHASE']]=0;
        $DATA['PHASES'][$line['CLINICAL_PHASE']]++;
        if (!isset( $DATA['STATUS'][$line['CLINICAL_STATUS']])) $DATA['STATUS'][$line['CLINICAL_STATUS']]=0;
        $DATA['STATUS'][$line['CLINICAL_STATUS']]++;
        if (!isset( $DATA['YEAR'][$line['YEAR_START']])) $DATA['YEAR'][$line['YEAR_START']]=0;
        $DATA['YEAR'][$line['YEAR_START']]++;
    }



    $LIST=array_filter(array_keys($DATA['DISEASE']));
    
    if ($LIST!=array())
    {
    $res=runQuery("SELECT DISEASE_TAG,DISEASE_NAME,DISEASE_DEFINITION,DISEASE_ENTRY_ID FROM DISEASE_ENTRY WHERE DISEASE_ENTRY_ID IN (".implode(',',$LIST).')');
    foreach ($res as $line)$DATA['DISEASE'][$line['DISEASE_ENTRY_ID']]=$line;
    
    }
    $LIST_GN=array_filter(array_keys($DATA['GENE']));
    if ($LIST_GN!=array())
    {
    $res=runQuery("SELECT GN_ENTRY_ID, GENE_ID,SYMBOL,FULL_NAME FROM GN_ENTRY WHERE GN_ENTRY_ID IN (".implode(',',$LIST_GN).')');
    
    foreach ($res as $line)$DATA['GENE'][$line['GN_ENTRY_ID']]=$line;
    }
        
    

    return $DATA;
}



function getClinicalTrialStatDisease($DISEASE_ENTRY_ID,$ONGOING_ONLY=false,$WITH_ADD_INFO=false)
{

    $DATA=array('GENE'=>array(),'DRUG'=>array(),'LIST'=>array(),'DISEASE'=>getAllChildDisease($DISEASE_ENTRY_ID,true));
    
$LIST=array($DISEASE_ENTRY_ID);
    foreach ($DATA['DISEASE'] as &$E)$LIST[]=$E['DISEASE_ENTRY_ID'];
    
    $query = "SELECT DISTINCT DRUG_ENTRY_ID, TO_CHAR(START_DATE,'YYYY-MM-DD') START_DATE, 
    EXTRACT(YEAR FROM START_DATE) year_start,DD.DRUG_DISEASE_ID, CLINICAL_PHASE,  DISEASE_ENTRY_ID,
    CLINICAL_STATUS, 
    
    GN_ENTRY_ID,
    TRIAL_ID
	FROM   
    DRUG_DISEASE DD ,
    CLINICAL_TRIAL C, 
    CLINICAL_TRIAL_DRUG CD 
	WHERE  
    C.CLINICAL_TRIAL_ID = CD.CLINICAL_TRIAL_ID AND 
    CD.DRUG_DISEASE_ID = DD.DRUG_DISEASE_ID AND 
    DD.DISEASE_ENTRY_ID  IN (" . implode(',',$LIST).')';
    if ($ONGOING_ONLY)
    {
        $query.=" AND CLINICAL_STATUS IN ('Available','Recruiting','Active, not recruiting','Not yet recruiting')"; 
    }
    $query.=' ORDER BY START_DATE DESC LIMIT 2000';
$time=microtime_float();
    $res = runQuery($query);

    foreach ($res as $line)
    {
        $FOUND=false;
        foreach ($DATA['LIST'] as $L)
        {
            if ($L[0]!=$line['GN_ENTRY_ID']||$L[1]!=$line['DRUG_ENTRY_ID'])continue;
            $FOUND=true;
            break;
        }
        if (!$FOUND)        $DATA['LIST'][]=array($line['GN_ENTRY_ID'],$line['DRUG_ENTRY_ID']);
        if ($line['GN_ENTRY_ID']!='' && !isset($DATA['GENE'][$line['GN_ENTRY_ID']]))$DATA['GENE'][$line['GN_ENTRY_ID']]=array();
        if ($line['DRUG_ENTRY_ID']!='' && !isset($DATA['DRUG'][$line['DRUG_ENTRY_ID']]))$DATA['DRUG'][$line['DRUG_ENTRY_ID']]= array();
        if ($line['CLINICAL_PHASE']!='')$line['CLINICAL_PHASE']=str_replace("PHASE","",$line['CLINICAL_PHASE']);
       
        if (in_array($line['CLINICAL_STATUS'],array('Available','Recruiting','Active, not recruiting','Not yet recruiting')))
        {
            $DATA['ONGOING'][]=$line;
        }
       if (!isset( $DATA['PHASES'][$line['CLINICAL_PHASE']])) $DATA['PHASES'][$line['CLINICAL_PHASE']]=0;
        $DATA['PHASES'][$line['CLINICAL_PHASE']]++;
        if (!isset( $DATA['STATUS'][$line['CLINICAL_STATUS']])) $DATA['STATUS'][$line['CLINICAL_STATUS']]=0;
        $DATA['STATUS'][$line['CLINICAL_STATUS']]++;
        if (!isset( $DATA['YEAR'][$line['YEAR_START']])) $DATA['YEAR'][$line['YEAR_START']]=0;
        $DATA['YEAR'][$line['YEAR_START']]++;
    }

    if ($WITH_ADD_INFO)
    {


        $LIST=array_filter(array_keys($DATA['DRUG']));
        
        if ($LIST!=array())
        {
            
            $res = runQuery("SELECT drug_primary_name,drug_entry_id FROM DRUG_ENTRY WHERE  DRUG_ENTRY_ID IN (" . implode(',',$LIST).')');
            
            foreach ($res as $line)
            {
               
                $DATA['DRUG'][$line['DRUG_ENTRY_ID']]=$line;
            }
       
        }
        $LIST_GN=array_filter(array_keys($DATA['GENE']));
        if ($LIST_GN!=array())
        {
        $res=runQuery("SELECT GN_ENTRY_ID, GENE_ID,SYMBOL,FULL_NAME FROM GN_ENTRY WHERE GN_ENTRY_ID IN (".implode(',',$LIST_GN).')');
        
        foreach ($res as $line)$DATA['GENE'][$line['GN_ENTRY_ID']]=$line;
        }
            
    }
    

    return $DATA;
}



function getClinicalTrialDrug($DRUG_ENTRY_ID,$ONGOING_ONLY=false)
{
    $query = "SELECT DISTINCT DISEASE_NAME, DD.DRUG_DISEASE_ID,OFFICIAL_TITLE, 
    CLINICAL_PHASE, CLINICAL_STATUS, TO_CHAR(START_DATE,'YYYY-MM-DD') START_DATE, SYMBOL, GENE_ID,FULL_NAME,TRIAL_ID
	FROM  DISEASE_ENTRY EP, DRUG_DISEASE DD 
    LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = DD.GN_ENTRY_ID, DRUG_ENTRY D,
	CLINICAL_TRIAL C, 
    CLINICAL_TRIAL_DRUG CD 
	WHERE  C.CLINICAL_TRIAL_ID = CD.CLINICAL_TRIAL_ID AND D.DRUG_ENTRY_ID = DD.DRUG_ENTRY_ID AND 
	CD.DRUG_DISEASE_ID = DD.DRUG_DISEASE_ID
	AND EP.DISEASE_ENTRY_ID = dD.DISEASE_ENTRY_ID
    AND details is not null
	AND D.DRUG_ENTRY_ID = " . $DRUG_ENTRY_ID;
    if ($ONGOING_ONLY)
    {
        $query.=" AND LOWER(CLINICAL_STATUS) IN ('available','recruiting','active, not recruiting','not yet recruiting')"; 
    }


    $res = runQuery($query);
    ;
    return $res;
}

function getClinicalTrialCompound($SM_ENTRY_ID,$ONGOING_ONLY=false)
{
    $query = "SELECT DISTINCT DISEASE_NAME, DD.DRUG_DISEASE_ID, CLINICAL_PHASE, 
    OFFICIAL_TITLE,CLINICAL_STATUS, TO_CHAR(START_DATE,'YYYY-MM-DD') START_DATE, SYMBOL, GENE_ID,FULL_NAME,TRIAL_ID, SM_ENTRY_ID
	FROM  DISEASE_ENTRY EP, DRUG_DISEASE DD LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = DD.GN_ENTRY_ID, DRUG_ENTRY D,
	CLINICAL_TRIAL C, CLINICAL_TRIAL_DRUG CD 
	WHERE  C.CLINICAL_TRIAL_ID = CD.CLINICAL_TRIAL_ID AND D.DRUG_ENTRY_ID = DD.DRUG_ENTRY_ID AND 
	CD.DRUG_DISEASE_ID = DD.DRUG_DISEASE_ID
	AND EP.DISEASE_ENTRY_ID = dD.DISEASE_ENTRY_ID
    AND details is not null
	AND SM_ENTRY_ID = " . $SM_ENTRY_ID;
    if ($ONGOING_ONLY)
    {
        $query.=" AND LOWER(CLINICAL_STATUS) IN ('Available','Recruiting','Active, not recruiting','Not yet recruiting','RECRUITING')"; 
    }
    
    $res = runQuery($query);
    return $res;
}

function getOntologyEntry($NAME, $IS_TAG = false, $WITH_CLIN_TRIALS = false)
{
    $DATA = array();
    if ($IS_TAG) $res = runQuery("SELECT * FROM ONTOLOGY_ENTRY EE, ONTOLOGY_HIERARCHY EH WHERE EH.ONTOLOGY_ENTRY_ID = EE.ONTOLOGY_ENTRY_ID AND  ONTOLOGY_TAG='" . $NAME . "'  ORDER BY ONTOLOGY_LEVEL ASC");
    else $res = runQuery("SELECT * FROM ONTOLOGY_ENTRY EE, ONTOLOGY_HIERARCHY EH WHERE EH.ONTOLOGY_ENTRY_ID = EE.ONTOLOGY_ENTRY_ID AND  LOWER(ONTOLOGY_NAME)=LOWER('" . $NAME . "') ORDER BY ONTOLOGY_LEVEL ASC");
    if ($res != array()) $DATA = $res[0];

    return $DATA;
}

function getChildOntology($ONTOLOGY_ENTRY_ID, $ONTOLOGY_LEVEL = -1)
{
    $DATA = array();
    $query = "SELECT DISTINCT EE.ONTOLOGY_ENTRY_ID, EE.ONTOLOGY_TAG, EE.ONTOLOGY_NAME, EE.ONTOLOGY_DEFINITION, EF.ONTOLOGY_LEVEL, EF.ONTOLOGY_LEVEL_RIGHT,EF.ONTOLOGY_LEVEL_LEFT
	FROM ONTOLOGY_ENTRY EE, ONTOLOGY_HIERARCHY EF, ONTOLOGY_HIERARCHY EPH, ONTOLOGY_ENTRY EP 
    WHERE EP.ONTOLOGY_ENTRY_ID = EPH.ONTOLOGY_ENTRY_ID AND EF.ONTOLOGY_ENTRY_ID = EE.ONTOLOGY_ENTRY_ID 
    AND EF.ONTOLOGY_LEVEL_LEFT >=EPH.ONTOLOGY_LEVEL_LEFT 
    AND EF.ONTOLOGY_LEVEL_RIGHT <= EPH.ONTOLOGY_LEVEL_RIGHT  AND EF.ONTOLOGY_LEVEL=EPH.ONTOLOGY_LEVEL+1
    AND EP.ONTOLOGY_ENTRY_ID = " . $ONTOLOGY_ENTRY_ID;
    if ($ONTOLOGY_LEVEL != -1) $query .= ' AND EPH.ONTOLOGY_LEVEL=' . $ONTOLOGY_LEVEL;
    $query .= ' ORDER BY EE.ONTOLOGY_NAME ASC';

    $res = runQuery($query);
    foreach ($res as $line) $DATA[$line['ONTOLOGY_TAG']] = $line;

    return $DATA;
}


function getPTMSitesFromGene($GN_ENTRY_ID)
{
    $query = 'SELECT prot_identifier,iso_name,iso_id, letter,position,ptm_seq_sgi,ptm_seq_id,ptm_type_name,psp.prot_seq_pos_Id
     FROM ptm_type ptmt, gn_prot_map pgm,ptm_seq ptm, prot_seq_pos psp, prot_seq ps, prot_entry pe 
     where pgm.prot_entry_Id = pe.prot_entry_Id 
     AND ptm.ptm_type_Id = ptmt.ptm_type_Id
      AND  pe.prot_entry_id = ps.prot_entry_id 
      AND ps.prot_seq_id = psp.prot_Seq_id 
      AND psp.prot_seq_pos_id = ptm.prot_seq_pos_id 
      AND ptmt.ptm_type_Id = ptm.ptm_type_Id
      AND gn_entry_Id =' . $GN_ENTRY_ID;
    $DATA = array();
    $res = runQuery($query);
    //   echo '<pre>';
    //   print_r($res);
    //   exit;
    $LIST = array();
    foreach ($res as $line) {
        if (!isset($DATA[$line['ISO_ID']])) {
            $DATA[$line['ISO_ID']] = array('INFO' => array('UNIPROT_ID' => $line['PROT_IDENTIFIER'], 'ISO_NAME' => $line['ISO_NAME']), 'LIST_PTM' => array());
        }
        if (!isset($DATA[$line['ISO_ID']]['LIST_PTM'][$line['PROT_SEQ_POS_ID']]))
            $DATA[$line['ISO_ID']]['LIST_PTM'][$line['PROT_SEQ_POS_ID']] = array('INFO' => array('LETTER' => $line['LETTER'], 'POSITION' => $line['POSITION']), 'PTM' => array());

        $DATA[$line['ISO_ID']]['LIST_PTM'][$line['PROT_SEQ_POS_ID']]['PTM'][$line['PTM_SEQ_ID']] = array($line['PTM_SEQ_SGI'], $line['PTM_TYPE_NAME']);
        $LIST[$line['PTM_SEQ_ID']] = array($line['ISO_ID'], $line['PROT_SEQ_POS_ID']);
    }
    if ($LIST != array()) {
        $query = 'SELECT psp.position,psp.letter, pv.*, ve.rsid 
        FROM prot_seq_pos psp, ptm_var pv 
        LEFT JOIN variant_protein_map vp ON  vp.variant_protein_id = pv.variant_protein_id 
        LEFT JOIN variant_transcript_map vt ON vt.variant_transcript_id = vp.variant_transcript_id 
        LEFT JOIN variant_change vc ON vc.variant_change_id = vt.variant_change_id 
        LEFT JOIN variant_position vpo ON vpo.variant_position_id=vc.variant_position_id 
        LEFT JOIN variant_entry ve ON ve.variant_entry_Id = vpo.variant_entry_Id 
        WHERE vp.prot_seq_pos_Id = psp.prot_Seq_pos_id 
        AND ptm_seq_Id IN (' . implode(',', array_keys($LIST)) . ')';
        $res = runQuery($query);


        foreach ($res as $line) {
            $E = $LIST[$line['PTM_SEQ_ID']];
            $DATA[$E[0]]['LIST_PTM'][$E[1]]['PTM'][$line['PTM_SEQ_ID']]['VAR'] = array($line['LETTER'] . $line['POSITION'], $line['PTM_VAR_AA'], $line['PTM_VAR_CLASS'], $line['RSID']);
        }

        $query = 'SELECT ptm_disease_alteration,ptm_seq_id, disease_name,disease_tag, disease_definition 
        FROM ptm_disease pd, disease_Entry de where de.disease_entry_Id = pd.disease_Entry_id
        AND ptm_seq_Id IN (' . implode(',', array_keys($LIST)) . ')';
        $res = runQuery($query);


        foreach ($res as $line) {
            $E = $LIST[$line['PTM_SEQ_ID']];
            $DATA[$E[0]]['LIST_PTM'][$E[1]]['PTM'][$line['PTM_SEQ_ID']]['DISEASE'] = array($line['PTM_DISEASE_ALTERATION'], $line['DISEASE_NAME'], $line['DISEASE_TAG']);
        }
    }
    return $DATA;
}


function getPTMSitesFromProtein($ISO_ID)
{
    $query = 'SELECT prot_identifier,iso_name,iso_id, letter,position,ptm_seq_sgi,ptm_seq_id,ptm_type_name,psp.prot_seq_pos_Id
     FROM ptm_type ptmt, gn_prot_map pgm,ptm_seq ptm, prot_seq_pos psp, prot_seq ps, prot_entry pe 
     where pgm.prot_entry_Id = pe.prot_entry_Id 
     AND ptm.ptm_type_Id = ptmt.ptm_type_Id
      AND  pe.prot_entry_id = ps.prot_entry_id 
      AND ps.prot_seq_id = psp.prot_Seq_id 
      AND psp.prot_seq_pos_id = ptm.prot_seq_pos_id 
      AND ptmt.ptm_type_Id = ptm.ptm_type_Id
      AND iso_id =\'' . $ISO_ID . '\'';
    $DATA = array();
    $res = runQuery($query);
    //   echo '<pre>';
    //   print_r($res);
    //   exit;
    $LIST = array();
    foreach ($res as $line) {
        if (!isset($DATA[$line['ISO_ID']])) {
            $DATA[$line['ISO_ID']] = array('INFO' => array('UNIPROT_ID' => $line['PROT_IDENTIFIER'], 'ISO_NAME' => $line['ISO_NAME']), 'LIST_PTM' => array());
        }
        if (!isset($DATA[$line['ISO_ID']]['LIST_PTM'][$line['PROT_SEQ_POS_ID']]))
            $DATA[$line['ISO_ID']]['LIST_PTM'][$line['PROT_SEQ_POS_ID']] = array('INFO' => array('LETTER' => $line['LETTER'], 'POSITION' => $line['POSITION']), 'PTM' => array());

        $DATA[$line['ISO_ID']]['LIST_PTM'][$line['PROT_SEQ_POS_ID']]['PTM'][$line['PTM_SEQ_ID']] = array($line['PTM_SEQ_SGI'], $line['PTM_TYPE_NAME']);
        $LIST[$line['PTM_SEQ_ID']] = array($line['ISO_ID'], $line['PROT_SEQ_POS_ID']);
    }
    if ($LIST != array()) {
        $query = 'SELECT psp.position,psp.letter, pv.*, ve.rsid 
        FROM prot_seq_pos psp, ptm_var pv 
        LEFT JOIN variant_protein_map vp ON  vp.variant_protein_id = pv.variant_protein_id 
        LEFT JOIN variant_transcript_map vt ON vt.variant_transcript_id = vp.variant_transcript_id 
        LEFT JOIN variant_change vc ON vc.variant_change_id = vt.variant_change_id 
        LEFT JOIN variant_position vpo ON vpo.variant_position_id=vc.variant_position_id 
        LEFT JOIN variant_entry ve ON ve.variant_entry_Id = vpo.variant_entry_Id 
        WHERE vp.prot_seq_pos_Id = psp.prot_Seq_pos_id 
        AND ptm_seq_Id IN (' . implode(',', array_keys($LIST)) . ')';
        $res = runQuery($query);


        foreach ($res as $line) {
            $E = $LIST[$line['PTM_SEQ_ID']];
            $DATA[$E[0]]['LIST_PTM'][$E[1]]['PTM'][$line['PTM_SEQ_ID']]['VAR'] = array($line['LETTER'] . $line['POSITION'], $line['PTM_VAR_AA'], $line['PTM_VAR_CLASS'], $line['RSID']);
        }

        $query = 'SELECT ptm_disease_alteration,ptm_seq_id, disease_name,disease_tag, disease_definition 
        FROM ptm_disease pd, disease_Entry de where de.disease_entry_Id = pd.disease_Entry_id
        AND ptm_seq_Id IN (' . implode(',', array_keys($LIST)) . ')';
        $res = runQuery($query);


        foreach ($res as $line) {
            $E = $LIST[$line['PTM_SEQ_ID']];
            $DATA[$E[0]]['LIST_PTM'][$E[1]]['PTM'][$line['PTM_SEQ_ID']]['DISEASE'] = array($line['PTM_DISEASE_ALTERATION'], $line['DISEASE_NAME'], $line['DISEASE_TAG']);
        }
    }
    return $DATA;
}


function getListTranscriptPosType()
{
    $query = 'SELECT TRANSCRIPT_POS_TYPE, TRANSCRIPT_POS_TYPE_ID FROM TRANSCRIPT_POS_TYPE';
    $res = runQuery($query);
    $DATA = array();
    foreach ($res as $l) $DATA[$l['TRANSCRIPT_POS_TYPE_ID']] = $l['TRANSCRIPT_POS_TYPE'];
    return $DATA;
}

function assay_portal_name($ASSAY_NAME)
{

    $res = runQuery("SELECT assay_name ,assay_description, assay_category,
    aty.assay_desc as assay_type,
        ace.cell_name as assay_cell_name,t.scientific_name,
        source_name
FROM assay_entry ae
LEFT JOIN assay_type aty on aty.assay_type_id =ae.assay_type 
LEFT JOIN assay_cell ace ON ae.assay_cell_id = ace.assay_cell_id 
LEFT JOIN taxon t ON t.taxon_id = ae.taxon_id
LEFT JOIN source s ON s.source_id = ae.source_id
WHERE LOWER(assay_name)  LIKE LOWER('%" . $ASSAY_NAME . "%') OR  LOWER(assay_description)  LIKE LOWER('%" . $ASSAY_NAME . "%') LIMIT 10000");

    return $res;
}


function getAssayOptions($LIST_ASSAYS)
{
    $ASSAY_IDS = $LIST_ASSAYS;
    if (is_array($LIST_ASSAYS))
        $ASSAY_IDS = implode(",", $LIST_ASSAYS);

    $INFO = array('REL' => array(), 'UNITS' => array(), 'TYPE' => array());
    $res = runQuery("SELECT distinct std_relation FROM activity_entry WHERE assay_entry_id IN (" . $LIST_ASSAYS . ')');
    foreach ($res as $line) $INFO['REL'][] = $line['STD_RELATION'];
    $res = runQuery("SELECT min(std_value) as min_v,max(std_value) as max_v, std_units,std_type FROM activity_entry WHERE assay_entry_id IN (" . $LIST_ASSAYS . ') group by std_type,std_units');
    foreach ($res as $line) {

        $INFO['RANGE'][$line['STD_TYPE']][$line['STD_UNITS']] = array($line['MIN_V'], $line['MAX_V']);
    }

    return $INFO;
}

function getAssayInformation($ASSAY_ENTRY_ID)
{
    $res = runQuery("SELECT assay_name, assay_name,assay_description, assay_test_type,assay_category,
                    aty.assay_desc as assay_type,
                        boe.bioassay_tag_id,boe.bioassay_label, boe.bioassay_definition,
                        ace.cell_name as assay_cell_name, ace.cell_description as assay_cell_description ,ace.cell_source_tissue as assay_cell_source_tissue,
                        ce.cell_acc, ce.cell_name,ce.cell_type,ce.cell_donor_sex,ce.cell_donor_age,ce.cell_version,
                        ati.assay_tissue_name,anatomy_tag,anatomy_name,anatomy_definition,
                        t.tax_id,t.scientific_name,
                        av.mutation_list, av.ac as mutation_ac, ps_av.iso_name as mutation_prot_iso, ps_av.description as mutation_prot_desc,
                        aco.confidence_score as SCORE_CONFIDENCE, aco.description as confidence_description,
                        source_name,
                        atg.assay_target_name,atg.assay_target_longname, species_group_flag, atax.tax_id as assay_target_tax,atax.scientific_name as assay_target_taxname,
                        assay_target_type_name,assay_target_type_desc
                FROM assay_entry ae
                LEFT JOIN assay_type aty on aty.assay_type_id =ae.assay_type 
                LEFT JOIN bioassay_onto_entry boe ON boe.bioassay_onto_entry_Id = ae.bioassay_onto_entry_id
                LEFT JOIN assay_cell ace ON ae.assay_cell_id = ace.assay_cell_id 
                LEFT JOIN cell_entry ce ON ce.cell_entry_Id = ace.cell_entry_Id
                LEFT JOIN assay_tissue ati ON ati.assay_tissue_id =ae.assay_tissue_id
                LEFT JOIN anatomy_entry ane ON ane.anatomy_entry_Id = ati.anatomy_entry_id
                LEFT JOIN taxon t ON t.taxon_id = ae.taxon_id
                LEFT JOIN assay_variant av ON av.assay_variant_id = ae.assay_variant_id
                LEFT JOIN prot_seq ps_av ON ps_av.prot_Seq_id = av.prot_seq_id
                LEFT JOIN source s ON s.source_id = ae.source_id
                LEFT JOIN assay_target atg ON atg.assay_target_id = ae.assay_target_id
                LEFT JOIN taxon atax ON atax.taxon_id = atg.taxon_id
                LEFT JOIN assay_target_type att ON att.assay_target_type_Id = atg.assay_target_type_id
                LEFT JOIN assay_confidence aco ON aco.confidence_score = ae.confidence_score
                WHERE assay_entry_id=" . $ASSAY_ENTRY_ID);
    $DATA['ASSAY_INFO'] = $res[0];

    $res = runQuery("SELECT is_homologue, accession, iso_id, iso_name, prot_identifier, ge.gn_entry_id, ps.prot_seq_id,pe.prot_entry_id, symbol, tax_id, gene_id, scientific_name ,full_name
               FROM assay_entry ae,assay_target at, assay_target_protein_map atpm, assay_protein ap
               LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
               LEFT JOIN prot_entry pe ON pe.prot_entry_id =ps.prot_entry_Id
               LEFT JOIN gn_prot_map gpm ON gpm.prot_entry_Id = pe.prot_entrY_Id
               LEFT JOIN gn_entry ge ON gpm.gn_entry_Id = ge.gn_entry_Id
               LEFT JOIN taxon t ON t.taxon_id = pe.taxon_id
               where ae.assay_target_id = at.assay_target_id 
               AND atpm.assay_target_id = at.assay_target_Id
               AND atpm.assay_protein_id = ap.assay_protein_id
               AND assay_entry_Id= " . $ASSAY_ENTRY_ID);
    foreach ($res as $line) {
        $line['TARGET_TYPE'] = 'PROTEIN';
        $line['DESC'] = getUniprotDescription($line['PROT_IDENTIFIER']);
        $DATA['ASSAY_TARGET'][] = $line;
    }
    $res = runQuery("SELECT is_homologue, accession,genetic_description, sequence,ge.gn_entry_id, gene_seq_name, gene_seq_version, transcript_name, transcript_version, symbol, tax_id, gene_id, scientific_name 
               FROM assay_entry ae,assay_target at, assay_target_genetic_map atpm, assay_genetic ap
               LEFT JOIN gene_seq ps ON ps.gene_seq_id = ap.gene_seq_id
               LEFT JOIN transcript tr ON tr.transcript_id = ap.transcript_id
               LEFT JOIN gn_entry ge ON ps.gn_entry_Id = ge.gn_entry_Id
               LEFT JOIN taxon t ON t.taxon_id = ap.taxon_id
               where ae.assay_target_id = at.assay_target_id 
               AND atpm.assay_target_id = at.assay_target_Id
               AND atpm.assay_genetic_id = ap.assay_genetic_id
               AND assay_entry_Id= " . $ASSAY_ENTRY_ID);
    foreach ($res as $line) {
        $line['TARGET_TYPE'] = 'GENETIC';
        $DATA['ASSAY_TARGET'][] = $line;
    }

    $DATA['ASSAY_UNITS'] = runQuery("select std_type, std_units, min(std_value), max(std_value), count(*) CO FROM activity_entry where assay_entry_id=" . $ASSAY_ENTRY_ID . " group by std_type, std_units ORDER BY count(*) DESC");
    $DATA['ASSAY_PUBLI'] = runQuery("SELECT pmid FROM pmid_entry pe, assay_pmid ap WHERE ap.pmid_entry_Id = pe.pmid_entry_Id AND assay_entry_Id = " . $ASSAY_ENTRY_ID);
    $DATA['RELATED_ASSAY'] = array();
    if ($DATA['ASSAY_PUBLI'] != array()) {
        $STR = "SELECT assay_name, assay_name,assay_description, assay_test_type,assay_category,
                        aty.assay_desc as assay_type,
                            boe.bioassay_tag_id,boe.bioassay_label, boe.bioassay_definition,
                            ace.cell_name as assay_cell_name, ace.cell_description as assay_cell_description ,ace.cell_source_tissue as assay_cell_source_tissue,
                            ce.cell_acc, ce.cell_name,ce.cell_type,ce.cell_donor_sex,ce.cell_donor_age,ce.cell_version,
                            ati.assay_tissue_name,anatomy_tag,anatomy_name,anatomy_definition,
                            t.tax_id,t.scientific_name,
                            av.mutation_list, av.ac as mutation_ac, ps_av.iso_name as mutation_prot_iso, ps_av.description as mutation_prot_desc,
                            aco.confidence_score as SCORE_CONFIDENCE, aco.description as confidence_description,
                            source_name,
                            atg.assay_target_name,atg.assay_target_longname, species_group_flag, atax.tax_id as assay_target_tax,atax.scientific_name as assay_target_taxname,
                            assay_target_type_name,assay_target_type_desc
                    FROM pmid_entry pe, assay_pmid ap, assay_entry ae
                    LEFT JOIN assay_type aty on aty.assay_type_id =ae.assay_type 
                    LEFT JOIN bioassay_onto_entry boe ON boe.bioassay_onto_entry_Id = ae.bioassay_onto_entry_id
                    LEFT JOIN assay_cell ace ON ae.assay_cell_id = ace.assay_cell_id 
                    LEFT JOIN cell_entry ce ON ce.cell_entry_Id = ace.cell_entry_Id
                    LEFT JOIN assay_tissue ati ON ati.assay_tissue_id =ae.assay_tissue_id
                    LEFT JOIN anatomy_entry ane ON ane.anatomy_entry_Id = ati.anatomy_entry_id
                    LEFT JOIN taxon t ON t.taxon_id = ae.taxon_id
                    LEFT JOIN assay_variant av ON av.assay_variant_id = ae.assay_variant_id
                    LEFT JOIN prot_seq ps_av ON ps_av.prot_Seq_id = av.prot_seq_id
                    LEFT JOIN source s ON s.source_id = ae.source_id
                    LEFT JOIN assay_target atg ON atg.assay_target_id = ae.assay_target_id
                    LEFT JOIN taxon atax ON atax.taxon_id = atg.taxon_id
                    LEFT JOIN assay_target_type att ON att.assay_target_type_Id = atg.assay_target_type_id
                    LEFT JOIN assay_confidence aco ON aco.confidence_score = ae.confidence_score
                    WHERE ae.assay_entry_id = ap.assay_entry_Id
                    AND ap.pmid_entry_id =pe.pmid_entry_id 
                    AND ae.assay_entry_id !=" . $ASSAY_ENTRY_ID . "
                    AND pmid IN (";
        foreach ($DATA['ASSAY_PUBLI'] as $p) $STR .= $p['PMID'] . ',';
        $STR = substr($STR, 0, -1) . ')';
        $DATA['RELATED_ASSAY'] = runQuery($STR);
    }
    return $DATA;
}

function assay_portal($ASSAY_NAME)
{
    $query = 'SELECT assay_name,assay_description,assay_entry_id,assay_variant_id,bioassay_onto_entry_id,assay_tissue_id,assay_target_id
    FROM assay_entry  where assay_name=\'' . $ASSAY_NAME . '\'';
    
    return runQuery($query)[0];
}


function getGeneAssays($GN_ENTRY_ID)
{
    $query = 'SELECT assay_name, assay_description, assay_type,  assay_target_type_id,
   ae.confidence_score as score_confidence, source_name,confidence_score,assay_variant_id,
   ace.cell_entry_id as cell_id, assay_tissue_name 
   FROM assay_entry ae 
   LEFT JOIN assay_cell ace ON ae.assay_cell_id = ace.assay_cell_id 
   LEFT JOIN assay_tissue ati ON ati.assay_tissue_id = ae.assay_tissue_id 
   LEFT JOIN anatomy_entry aen ON aen.anatomy_entry_id = ati.anatomy_entry_id, 
   assay_target at, 
   assay_target_protein_map atp, 
   assay_protein ap,
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
        $res = runQuery("SELECT assay_variant_id,mutation_list FROM assay_variant WHERE assay_variant_id IN (" . implode(',', array_keys($DATA['VARIANT'])) . ')');

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


function getProtAssays($PROT_IDENTIFIER)
{
    $query = 'SELECT assay_name, assay_description, assay_type,  assay_target_type_id,
   ae.confidence_score as score_confidence, source_name,confidence_score,assay_variant_id,
   ace.cell_entry_id as cell_id, assay_tissue_name 
   FROM assay_entry ae 
   LEFT JOIN assay_cell ace ON ae.assay_cell_id = ace.assay_cell_id 
   LEFT JOIN assay_tissue ati ON ati.assay_tissue_id = ae.assay_tissue_id 
   LEFT JOIN anatomy_entry aen ON aen.anatomy_entry_id = ati.anatomy_entry_id, 
   assay_target at, 
   assay_target_protein_map atp, 
   assay_protein ap,
   prot_seq ps,
   prot_entry pe,
   source s
   WHERE ae.assay_target_id = at.assay_Target_id
   AND ae.source_id = s.source_id
   AND at.assay_target_id = atp.assay_Target_id 
   AND atp.assay_protein_id = ap.assay_protein_id
   AND ap.prot_seq_id = ps.prot_Seq_id 
   AND ps.prot_entry_Id = pe.prot_entry_id
   AND prot_identifier = \'' . $PROT_IDENTIFIER . '\'';
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
        $res = runQuery("SELECT assay_variant_id,mutation_list FROM assay_variant WHERE assay_variant_id IN (" . implode(',', array_keys($DATA['VARIANT'])) . ')');

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



function protein_portal_protName($PROT_NAME, $IS_HUMAN = true)
{
    $query = "SELECT PROT_IDENTIFIER, scientific_name, tax_id,p.status, symbol,gene_id 
        FROM prot_entry p LEFT JOIN gn_prot_map gpm ON gpm.prot_entry_id = p.prot_entry_id
        LEFT JOIN gn_entry gn ON gn.gn_entry_Id = gpm.gn_entrY_id, taxon t , prot_name_map pnm, prot_name pn
        WHERE p.prot_entry_id = pnm.prot_entry_id
        AND pn.prot_name_id = pnm.prot_namE_id
        AND t.taxon_id = p.taxon_id AND LOWER(protein_name) LIKE LOWER('%" . $PROT_NAME . "%' )";
    if ($IS_HUMAN) $query .= " AND tax_id='9606'";

    $res = runQuery($query);
    return $res;
}

function protein_portal_uniprotID($UNIPROT_ID, $IS_HUMAN = true)
{
    global $DB_CONN;
    $query = "SELECT DISTINCT  PROT_IDENTIFIER, scientific_name, tax_id,p.status, symbol,gene_id
         FROM prot_entry p 
         LEFT JOIN gn_prot_map gpm ON gpm.prot_entry_id = p.prot_entry_id 
         LEFT JOIN gn_entry gn ON gn.gn_entry_Id = gpm.gn_entrY_id, taxon t , prot_name_map pnm, prot_name pn 
         WHERE t.taxon_id = p.taxon_id 
         AND p.prot_entry_id = pnm.prot_entry_id 
         AND pn.prot_name_id = pnm.prot_namE_id
         AND LOWER(PROT_IDENTIFIER)  LIKE LOWER('" . $UNIPROT_ID . "%') ";
    if ($IS_HUMAN) $query .= " AND tax_id='9606'";

    $res = runQuery($query);

    return $res;
}
function protein_portal_uniprotAC($AC, $IS_HUMAN = true)
{
    $query = "SELECT DISTINCT  PROT_IDENTIFIER, scientific_name, tax_id,p.status, symbol,gene_id FROM PROT_ENTRY p
        LEFT JOIN gn_prot_map gpm ON gpm.prot_entry_id = p.prot_entry_id 
         LEFT JOIN gn_entry gn ON gn.gn_entry_Id = gpm.gn_entrY_id, taxon t, prot_AC pa WHERE  t.taxon_id = p.taxon_id AND  pA.prot_ENTRY_ID=p.prot_ENTRY_ID AND AC='" . $AC . "'";
    if ($IS_HUMAN) $query .= " AND tax_id='9606'";
    $res = runQuery($query);
    return $res;
}
function protein_portal_uniprotSeqName($SEQ, $IS_HUMAN = true)
{
    $query = 'SELECT DISTINCT  PROT_IDENTIFIER, scientific_name, tax_id,p.status, symbol,gene_id,iso_id,iso_name,description FROM PROT_ENTRY p
    LEFT JOIN gn_prot_map gpm ON gpm.prot_entry_id = p.prot_entry_id 
     LEFT JOIN gn_entry gn ON gn.gn_entry_Id = gpm.gn_entrY_id, taxon t, prot_seq pa WHERE  t.taxon_id = p.taxon_id AND  pA.prot_ENTRY_ID=p.prot_ENTRY_ID AND (iso_name=\'' . $SEQ . "' OR iso_id = '" . $SEQ . "' OR LOWER(description) LIKE LOWER('%" . $SEQ . "%'))";
    if ($IS_HUMAN) $query .= " AND tax_id='9606'";

    $res = runQuery($query);
    return $res;
}
function protein_portal_uniprotDomName($SEQ, $IS_HUMAN = true)
{
    $query = 'SELECT DISTINCT  PROT_IDENTIFIER, scientific_name, tax_id,p.status, symbol,gene_id,domain_name FROM PROT_ENTRY p
    LEFT JOIN gn_prot_map gpm ON gpm.prot_entry_id = p.prot_entry_id 
     LEFT JOIN gn_entry gn ON gn.gn_entry_Id = gpm.gn_entrY_id, taxon t, prot_dom pa WHERE  t.taxon_id = p.taxon_id AND  pA.prot_ENTRY_ID=p.prot_ENTRY_ID AND LOWER(domain_name) LIKE LOWER(\'%' . $SEQ . "%')";
    if ($IS_HUMAN) $query .= " AND tax_id='9606'";

    $res = runQuery($query);
    return $res;
}

function getUniprotNames($PROT_IDENTIFIER)
{
    $res = runQuery("select distinct prot_identifier,group_id,class_name,name_type,name_subtype,name_link,is_primary, protein_name,ec_number FROM prot_entry pe, prot_name_map pnm, prot_name pn
    where pe.prot_entry_id = pnm.prot_entry_id
    AND pn.prot_name_id = pnm.prot_namE_id
    AND prot_identifier='" . $PROT_IDENTIFIER . "'");
    $DATA = array();
    foreach ($res as $line) $DATA[$line['GROUP_ID']][] = $line;
    return $DATA;
}


function uniprotToGene($PROT_IDENTIFIER)
{
    $query = 'SELECT SYMBOL, GENE_ID,GE.GN_ENTRY_ID, full_name
	FROM PROT_ENTRY UE, GN_PROT_MAP GUM, GN_ENTRY GE
	WHERE GUM.PROT_ENTRY_ID = UE.PROT_ENTRY_ID
    AND GE.GN_ENTRY_Id = GUM.GN_ENTRY_ID
	AND  PROT_IDENTIFIER=\'' . $PROT_IDENTIFIER . '\'';

    $res = runQuery($query);
    return $res;
}

function getProteinSequencesFromProtEntry($PROT_IDENTIFIER)
{
    $query = "SELECT PROT_SEQ_ID,UE.STATUS,UE.PROT_IDENTIFIER, ISO_ID, ISO_NAME,IS_PRIMARY,DESCRIPTION, CONFIDENCE
	FROM PROT_ENTRY UE, PROT_SEQ US WHERE US.PROT_ENTRY_ID = UE.PROT_ENTRY_ID AND PROT_IDENTIFIER='" . $PROT_IDENTIFIER . "'
	AND UE.STATUS!='D'
	ORDER BY UE.STATUS DESC,IS_PRIMARY DESC";
    $res = runQuery($query);
    $DATA = array('SEQ' => array());
    foreach ($res as $line) {
        $DATA['SEQ'][$line['PROT_SEQ_ID']]['SEQ'] = $line;
    }

    if (count($DATA['SEQ']) == 0) return $DATA;

    $query = 'SELECT * FROM TR_PROTSEQ_AL U, TRANSCRIPT T WHERE U.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND PROT_SEQ_ID IN   (' . implode(',', array_keys($DATA['SEQ'])) . ')';
    $res = runQuery($query);
    foreach ($res as $line) {
        $DATA['SEQ'][$line['PROT_SEQ_ID']]['TRANSCRIPT'][] = $line;
    }

    $query = 'SELECT * FROM PROT_SEQ_AL WHERE PROT_SEQ_REF_ID IN (' . implode(',', array_keys($DATA['SEQ'])) . ') AND PROT_SEQ_COMP_ID IN (' . implode(',', array_keys($DATA['SEQ'])) . ')';
    $DATA['SIM'] = runQuery($query);
    $res = runQuery("SELECT COUNT(*) CO, PROT_SEQ_ID FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN  (" . implode(',', array_keys($DATA['SEQ'])) . ') GROUP BY PROT_SEQ_ID');
    foreach ($res as $line) $DATA['SEQ'][$line['PROT_SEQ_ID']]['SEQ']['LEN'] = $line['CO'];

    return $DATA;
}


function submitJob($JOB_NAME,$PARAMS,$DESCRIPTION,$TITLE)
{
    
    global $USER;
    $MD5=md5(microtime().$USER['DB_ID'].$JOB_NAME);
    $query="INSERT INTO web_job (web_job_id,lly_user_id,job_name,params,md5id,job_description,job_title,time_start) 
    VALUES (nextval('web_job_sq'),".$USER['DB_ID'].",'".$JOB_NAME."','".str_replace("'","''",json_encode($PARAMS))."','".$MD5."','".$DESCRIPTION."','".$TITLE."',CURRENT_TIMESTAMP)";
    if (!runQueryNoRes($query)) return false;
    
    
    return $MD5;
}


function searchNewsByGeneIds($LIST_GENES)
{
    
    $query="SELECT DISTINCT news_hash, gene_id FROM news ne,news_gn_map n, gn_entry g where ne.news_id = n.news_id AND g.gn_entry_Id = n.gn_entry_id AND gene_id IN (".implode(',',$LIST_GENES).')';
   
    $res=runQuery($query);
    return $res;
}

function searchNewsByDiseaseTag($LIST_DISEASES)
{
    
    $lt=array();
    foreach ($LIST_DISEASES as $D)$lt[]="'".$D."'";
    $res=runQuery("SELECT DISTINCT news_hash, disease_tag FROM news ne, news_disease_map n, disease_entry g where ne.news_id = n.news_id AND g.disease_entry_Id = n.disease_entry_id AND disease_tag IN (".implode(',',$lt).')');
    return $res;
}

function searchNewsByDrugEntryId($LIST_DRUGS)
{
    
    $res=runQuery("SELECT DISTINCT news_hash, drug_entry_id FROM news ne, news_drug_map n where ne.news_id = n.news_id AND drug_entry_id IN (".implode(',',$LIST_DRUGS).')');
    return $res;
}

function searchNewsByClinicalTrial($LIST_TRIAL_NAMES)
{
    
    $lt=array();
    foreach ($LIST_TRIAL_NAMES as $D)$lt[]="'".$D."'";
    $query='SELECT alias_name, news_hash FROM news ne, news_clinical_trial_map n, clinical_trial_alias cta where ne.news_id = n.news_id AND n.clinical_trial_id =cta.clinical_trial_id
    AND alias_name IN ('.implode(',',$lt).')';
    $res=runQuery($query);
    return $res;

}
function searchNewsByCompany($LIST_COMPANY)
{
    
    $lt=array();
    foreach ($LIST_COMPANY as $D)$lt[]="'".$D."'";
    $query='SELECT company_name, news_hash FROM  news ne,news_company_map n, company_entry cta where ne.news_id = n.news_id AND n.company_entry_Id = cta.company_entry_id
    AND company_name IN ('.implode(',',$lt).')';
    $res=runQuery($query);
    return $res;

}

function searchNewsBySource($LIST_SOURCE)
{
    
    $lt=array();
    foreach ($LIST_SOURCE as $D)$lt[]="'".$D."'";
    $query='SELECT source_name, news_hash FROM news n, source cta where n.source_id = cta.source_id
    AND source_name IN ('.implode(',',$lt).')';
    $res=runQuery($query);
    return $res;

}


function getNewsSourceStat()
{
    return runQuery("SELECT COUNT(*) co, source_name FROM news n, source s where s.source_id = n.source_id group by source_name ORDER BY SOURCE_NAME ASC");
}

function getNewsAnnot($LIST_IDS)
{

    $res=runQuery("SELECT * FROM source_type");
    $ST=array();
    foreach ($res as $line)$ST[$line['SOURCE_TYPE']]=$line['SOURCE_TYPE_NAME'];
 
    $IDS=array();
    foreach ($LIST_IDS as $ID)$IDS[]="'".$ID."'";
    $DATA=array();
    $query="SELECT NEWS_hash, source_name, subgroup,source_type, news_added_date FROM NEWS N,SOURCE S WHERE S.SOURCE_ID = N.SOURCE_ID AND  news_hash IN (" . implode(',',$IDS) . ")";
    $res = runQuery($query);
    if (count($res) == 0) return array();
    foreach ($res as $line)
    {
        $time=strtotime($line['NEWS_ADDED_DATE']);
        $DATA['DATE'][date('Y',$time).'Q'.ceil(date('m',$time)/3)]['MATCH'][]=$line['NEWS_HASH'];
        $DATA['SOURCE'][$line['SOURCE_NAME']]['MATCH'][]=$line['NEWS_HASH'];
        $DATA['SOURCE_TYPE'][$ST[$line['SOURCE_TYPE']]]['MATCH'][]=$line['NEWS_HASH'];
        $DATA['SOURCE_SUBGROUP'][$line['SUBGROUP']]['MATCH'][]=$line['NEWS_HASH'];
    }
    
    
    $res = runQuery("SELECT DISTINCT DRUG_ENTRY_ID, IS_PRIMARY, NEWS_HASH FROM NEWS_DRUG_MAP P, NEWS N WHERE N.NEWS_ID=P.NEWS_ID AND   news_hash IN (" . implode(',',$IDS) . ")");
    if ($res !== false && count($res) != 0) {

        foreach ($res as $line) 
        {
           
            if (!isset($DATA['DRUG'][$line['DRUG_ENTRY_ID']])) 
            {
                $TMP= getDrugInfo($line['DRUG_ENTRY_ID']);
                $DATA['DRUG'][$line['DRUG_ENTRY_ID']]= array(
                    'Drug Name' => $TMP['DRUG_PRIMARY_NAME']
                );
            }
            $DATA['DRUG'][$line['DRUG_ENTRY_ID']]['MATCH'][]=$line['NEWS_HASH'];
        }
    }
   
    $res = runQuery("SELECT DISTINCT COMPANY_NAME,COMPANY_TYPE, IS_PRIMARY,NEWS_HASH
              FROM COMPANY_ENTRY DE, NEWS_COMPANY_MAP  PD, NEWS N WHERE PD.COMPANY_ENTRY_ID = DE.COMPANY_ENTRY_ID AND N.NEWS_ID=PD.NEWS_ID AND   news_hash IN (" . implode(',',$IDS) . ")");
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) $DATA['COMPANY'][$line['COMPANY_NAME']]['MATCH'][]=$line['NEWS_HASH'];
            }
      
       
            $res = runQuery("SELECT SYMBOL,GENE_ID,full_name,DESCRIPTION, IS_PRIMARY,NEWS_HASH FROM NEWS_GN_MAP PRM, NEWS N , GN_ENTRY GR
             LEFT JOIN GN_PROT_MAP PGM ON PGM.GN_ENTRY_ID = GR.GN_ENTRY_ID
             LEFT JOIN PROT_DESC PE ON PE.PROT_ENTRY_ID = PGM.PROT_ENTRY_ID AND DESC_TYPE='FUNCTION'
     WHERE GR.GN_ENTRY_ID = PRM.GN_ENTRY_ID  AND N.NEWS_ID=PRM.NEWS_ID AND   news_hash IN (" . implode(',',$IDS) . ")");
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line) {
                    if (!isset($DATA['GENE'][$line['GENE_ID']]))
                    {
                    $ENTRY=array('Symbol'=>$line['SYMBOL'],
												'Gene Name'=>$line['FULL_NAME'],
												'Gene ID'=>$line['GENE_ID']);
								$DATA['GENE'][$line['GENE_ID']]=$ENTRY;
                    }
                    $DATA['GENE'][$line['GENE_ID']]['MATCH'][]=$line['NEWS_HASH'];
                    
                }
            }

         
            $res = runQuery("SELECT DISTINCT DISEASE_NAME,disease_tag,disease_definition,IS_PRIMARY,NEWS_HASH
              FROM DISEASE_ENTRY DE, NEWS N , NEWS_DISEASE_MAP  PD WHERE PD.DISEASE_ENTRY_ID = DE.DISEASE_ENTRY_ID  AND N.NEWS_ID=PD.NEWS_ID AND   news_hash IN (" . implode(',',$IDS) . ")");
            if ($res !== false && count($res) != 0) {

                foreach ($res as $line)
                {
                    if (!isset($DATA['DISEASE'][$line['DISEASE_TAG']]))
                    {
                        $DATA['DISEASE'][$line['DISEASE_TAG']] = array('Disease ID'=>$line['DISEASE_TAG'],
                        'Disease Name'=>$line['DISEASE_NAME']);
                    }
                    $DATA['DISEASE'][$line['DISEASE_TAG']]['MATCH'][]=$line['NEWS_HASH'];
                }
            }
          
                $res = runQuery("SELECT DISTINCT DE.clinical_trial_id, alias_name,official_title, clinical_phase,brief_summary,clinical_status,IS_PRIMARY,news_hash,alias_type
                  FROM CLINICAL_TRIAL_ALIAS CT, CLINICAL_TRIAL DE, NEWS_CLINICAL_TRIAL_MAP  PD, NEWS N  
                  WHERE CT.CLINICAL_TRIAL_ID = DE.CLINICAL_TRIAL_ID AND CT.ALIAS_TYPE='Primary' AND PD.CLINICAL_TRIAL_ID = DE.CLINICAL_TRIAL_ID  AND N.NEWS_ID=PD.NEWS_ID AND   news_hash IN (" . implode(',',$IDS) . ")");
                  $T=array();
                if ($res !== false && count($res) != 0) {
    
                   
                
                foreach ($res as $line){$T[$line['CLINICAL_TRIAL_ID']][]=$line;}
                foreach ($T as $line)
                {
                    $prim_name='';$alias=array();
                    foreach ($line as &$v)if ($v['ALIAS_TYPE']=='Primary')$prim_name=$v['ALIAS_NAME'];
                    else $alias[$v['ALIAS_NAME']]=$v['ALIAS_TYPE'];
                    if (!isset($DATA['CLINICAL'][$prim_name]))
                    {
                    $ENTRY=array('trial_id'=>$prim_name ,'Alias'=>$alias,'Title'=>$line[0]['OFFICIAL_TITLE'],
                     'clinical_phase'=>$line[0]['CLINICAL_PHASE'],
                     'clinical_status'=>$line[0]['CLINICAL_STATUS'],
                     'start_date'=>$line[0]['START_DATE']);					
                    $DATA['CLINICAL'][$prim_name]=$ENTRY;
                    }
                    foreach ($line as &$v)
                    $DATA['CLINICAL'][$prim_name]['MATCH'][]=$v['NEWS_HASH'];
				
				}
            }

            
       

    
    return $DATA;
}