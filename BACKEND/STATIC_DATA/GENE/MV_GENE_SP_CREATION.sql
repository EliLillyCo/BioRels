CREATE TABLE MV_GENE_SP_NEW AS (Select symbol,full_name,gene_id, gn.gn_entry_id, syn_value, scientific_name, tax_id
From Taxonomy_Tree Tt 
Right Join Chromosome Ch On Ch.Taxonomy_Tree_Id = Tt.Taxonomy_Tree_Id
Full Outer Join Chr_Map Cm On Cm.Chr_Id = Ch.Chr_Id 
Full Outer Join Chr_Gn_Map Cgm On Cgm.Chr_Map_Id = Cm.Chr_Map_Id
Full Outer Join Gn_Entry Gn On Cgm.Gn_Entry_Id = Gn.Gn_Entry_Id 
FULL OUTER JOIN (SELECT SYN_VALUE, GN_ENTRY_ID FROM GN_SYN GS, GN_SYN_MAP GSM WHERE GS.GN_SYN_ID=GSM.GN_SYN_ID AND SYN_TYPE='S') S ON S.GN_ENTRY_ID = GN.GN_ENTRY_ID 
WHERE TAX_ID IN (9606,9544,10090,10116,9615,9541,9913,9986))
