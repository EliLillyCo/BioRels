

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

DROP SCHEMA IF EXISTS DB_PRIVATE_SCHEMA CASCADE;
CREATE SCHEMA DB_PRIVATE_SCHEMA;
SET SESSION search_path to DB_PRIVATE_SCHEMA;

SET default_tablespace = '';


CREATE TABLE DB_PRIVATE_SCHEMA.activity_entry (
    activity_entry_id integer NOT NULL,
    assay_entry_id integer NOT NULL,
    std_relation character varying(2),
    std_value numeric,
    std_units character varying(100),
    std_flag character varying(1),
    std_type character varying(100),
    bao_endpoint integer NOT NULL,
    mol_pos character varying(250),
    molecular_entity_id bigint,
    source_id smallint,
    value numeric,
    units character varying(100) NOT NULL,
    unit_type character varying(100) NOT NULL,
    run_date date,
    is_active character varying(1),
    well_group_id integer,
    relation character varying(10)
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.activity_entry IS 'Table providing activity data for an molecular entity in a given assay';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.activity_entry_id IS 'Unique ID for the activity row';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.assay_entry_id IS 'Foreign key to the assay table (describing an assay)';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.std_relation IS 'Symbol constraining the activity value';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.std_value IS 'Measurement transformed into common units';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.std_units IS 'Standardized experimental units';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.std_flag IS 'Shows whether the standardised columns have been curated/set (1) or just default to the published data (0)';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.std_type IS 'Standardised version of the published_activity_type (e.g. IC50 rather than Ic-50/Ic50/ic50/ic-50)';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.bao_endpoint IS 'Foreign key to the BioAssay Ontology table';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.mol_pos IS 'Numbering of the molecule as it appears in the publication';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.molecular_entity_id IS 'Foreign key to the molecular entity table, i.e. the compound tested';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.source_id IS 'Foreign key to the source table, i.e. where this activity data is coming from';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.value IS 'Experimental value as reported';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.units IS 'Reported experimental unit';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.unit_type IS ' Type of experimental unit';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.run_date IS 'Date the experiment was run';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.is_active IS 'Is this compound considered active (criteria to be set by scientst)';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.activity_entry.well_group_id IS 'Identifier of the well';


CREATE SEQUENCE DB_PRIVATE_SCHEMA.activity_entry_activity_entry_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE DB_PRIVATE_SCHEMA.activity_entry_activity_entry_id_seq OWNED BY DB_PRIVATE_SCHEMA.activity_entry.activity_entry_id;


CREATE TABLE DB_PRIVATE_SCHEMA.assay_cell (
    assay_cell_id integer NOT NULL,
    cell_name character varying(50) NOT NULL,
    cell_description character varying(200) NOT NULL,
    cell_source_tissue character varying(50),
    chembl_id character varying(20) NOT NULL,
    taxon_id bigint,
    cell_entry_id integer
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_cell IS 'Cell line associated to a given assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_cell.assay_cell_id IS 'Unique ID of a cell line association to an assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_cell.cell_name IS 'Name of the cell line';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_cell.cell_description IS 'Description of the cell line';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_cell.cell_source_tissue IS ' Tissue this cell line is derived from';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_cell.chembl_id IS 'ChEMBL identifier';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_cell.taxon_id IS 'Foreign key to taxon table, providing the organism this cell is derived from';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_cell.cell_entry_id IS 'Foreign key to cell table. Can be NULL';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_confidence (
    confidence_score smallint NOT NULL,
    description character varying(100) NOT NULL,
    target_mapping character varying(30) NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.assay_entry (
    assay_entry_id integer NOT NULL,
    assay_name character varying(500) NOT NULL,
    assay_description character varying(4000),
    assay_type character varying(1),
    assay_test_type character varying(20),
    assay_category character varying(50),
    curated_by character varying(1),
    bioassay_onto_entry_id integer,
    assay_cell_id integer,
    assay_tissue_id integer,
    taxon_id integer,
    assay_variant_id integer,
    confidence_score smallint,
    source_id integer NOT NULL,
    assay_target_id integer,
    assay_id character varying(50),
    date_updated date
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_entry IS 'Reported assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_entry_id IS 'Unique ID for a given assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_name IS 'Name of the assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_description IS 'Description of the reported assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_type IS 'Assay classification: B=Binding assay ; A=ADME Assay ; F =Functional assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_test_type IS 'Type of assay system (in-vitro or in-vivo)';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_category IS 'screening, confirmatory (ie: dose-response), summary, panel or other.';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.curated_by IS 'Indicates the level of curation of the target assignment. ';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.bioassay_onto_entry_id IS 'foreign key for the corresponding format type in BioAssay Ontology (e.g., cell-based, biochemical, organism-based etc)';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_cell_id IS 'Foreign Key  to assay_cell table, describing the cell line';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_tissue_id IS 'Foreign key to the assay tissue table, tissue used in the assay system (e.g., for tissue-based assays) or from which the assay system was derived (e.g., for cell/subcellular fraction-based assays).';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.taxon_id IS 'Foreign key to the taxon table, describing the organism';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_variant_id IS 'Foreign key to assay_variant table, providing variant information';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.confidence_score IS 'Foreign key to assay_confidence table, describing how well the target is defined';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.source_id IS 'Foreign key to the source table, defining where the assay is reported';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_entry.assay_target_id IS 'Foreign key to assay_target, describing the target system';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_genetic (
    assay_genetic_id integer NOT NULL,
    genetic_description character varying(200) NOT NULL,
    taxon_id integer NOT NULL,
    gene_seq_id bigint,
    transcript_id bigint,
    accession character varying(25),
    sequence text
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_genetic IS 'Describes a genetic target for a given assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_genetic.assay_genetic_id IS 'Unique ID for a genetic id';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_genetic.genetic_description IS 'Textual description for a genetic target';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_genetic.taxon_id IS 'Foreign key to the target organism';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_genetic.gene_seq_id IS 'Foreign key to gene sequence table';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_genetic.transcript_id IS 'Foreign key to transcript table';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_genetic.accession IS 'Accession Identifier of the genetic entry. Ensembl gene, transcript. Should be used as reference if there is no match to a gene_seq_id or transcript_Id';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_genetic.sequence IS 'Complete nucleotide sequence';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_pmid (
    assay_pmid_entry integer NOT NULL,
    assay_entry_id integer NOT NULL,
    pmid_entry_id integer NOT NULL
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_pmid IS 'Association between assay and publication';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_pmid.assay_pmid_entry IS 'Unique ID connecting an assay to a publication';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_pmid.assay_entry_id IS 'Foreign key to assay_entry table, specifying an assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_pmid.pmid_entry_id IS ' Foreign key to pmid_entry table, specificying a publication';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_protein (
    assay_protein_id integer NOT NULL,
    accession character varying(25),
    sequence_md5sum character varying(32),
    prot_seq_id bigint,
    gn_entry_id bigint,
    CONSTRAINT assay_protein_either_id CHECK (((accession IS NOT NULL) OR (gn_entry_id IS NOT NULL)))
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_protein IS 'Target protein for assays';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_protein.assay_protein_id IS 'Unique ID of a target protein';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_protein.accession IS 'UniProt Accession ID defining a target protein';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_protein.sequence_md5sum IS 'md5 has of the target protein sequence';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_protein.prot_seq_id IS 'Foreign key to prot_seq table, defining a protein sequence matching that accession';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_target (
    assay_target_id integer NOT NULL,
    taxon_id integer,
    assay_target_name character varying(50) NOT NULL,
    assay_target_longname character varying(200) NOT NULL,
    assay_target_type_id smallint NOT NULL,
    species_group_flag smallint NOT NULL
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_target IS 'Theoretical group of one or multiple protein or genetic targets';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target.taxon_id IS 'Foreign key to taxon table, defining the target organism';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target.assay_target_name IS 'Simple name for the overall target';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target.assay_target_longname IS 'Descriptive name of the overall target';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target.assay_target_type_id IS 'Foreign key to assay_target_type, describing what type of group this target is';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_target_genetic_map (
    assay_target_genetic_map_id integer NOT NULL,
    assay_target_id integer NOT NULL,
    assay_genetic_id integer NOT NULL,
    is_homologue smallint
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_target_genetic_map IS 'Association table between a Theoretical target group and a genetic target';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_genetic_map.assay_target_genetic_map_id IS 'Unique ID defining a genetic target to a target group';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_genetic_map.assay_target_id IS 'Foreign key to assay_target table, defining a target group';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_genetic_map.assay_genetic_id IS 'Foreign key to assay_genetic table, defining a genetic target';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_target_protein_map (
    assay_target_protein_map_id integer NOT NULL,
    assay_target_id integer NOT NULL,
    assay_protein_id integer NOT NULL,
    is_homologue smallint
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_target_protein_map IS 'Association table between a Theoretical target group and a protein target';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_protein_map.assay_target_protein_map_id IS 'Unique ID defining a protein target to a target group';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_protein_map.assay_target_id IS 'Foreign key to assay_target table, defining a target group';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_protein_map.assay_protein_id IS 'Foreign key to assay_protein table, defining a protein target';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_target_type (
    assay_target_type_id smallint NOT NULL,
    assay_target_type_name character varying(30) NOT NULL,
    assay_target_type_desc character varying(250) NOT NULL,
    assay_target_type_parent character varying(30)
);


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_type.assay_target_type_id IS 'Unique ID defining a target type';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_type.assay_target_type_name IS 'Target type ';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_type.assay_target_type_desc IS 'Description of target type';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_target_type.assay_target_type_parent IS 'Higher level classification of target_type, allowing grouping of e.g., all PROTEIN targets, all NON-MOLECULAR targets etc.';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_tissue (
    assay_tissue_id integer NOT NULL,
    assay_tissue_name character varying(100) NOT NULL,
    anatomy_entry_id integer
);


CREATE TABLE DB_PRIVATE_SCHEMA.assay_type (
    assay_type_id character varying(1) NOT NULL,
    assay_desc character varying(250)
);


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_type.assay_type_id IS 'Single character representing assay type';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_type.assay_desc IS 'Description of assay type';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_variant (
    assay_variant_id integer NOT NULL,
    mutation_list character varying(2000) NOT NULL,
    prot_seq_id bigint,
    ac character varying(10)
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_variant IS 'List of possible variant on protein sequence defined in different assay';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_variant.assay_variant_id IS 'Unique ID defining a set of protein sequence variant(s)';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_variant.mutation_list IS 'List of variants with their positions in the protein sequence';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_variant.prot_seq_id IS 'Foreign key to protein sequence entry.';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_variant.ac IS 'Protein sequence Accession ID, in case the protein is not in the database';


CREATE TABLE DB_PRIVATE_SCHEMA.assay_variant_pos (
    assay_variant_pos_id integer NOT NULL,
    assay_variant_id integer NOT NULL,
    prot_seq_pos_id bigint,
    variant_protein_id bigint
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.assay_variant_pos IS 'Table associating variants in assays to protein variants';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_variant_pos.assay_variant_id IS 'Foreign key to assay_variant table, defining a variant in an assay''s target';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_variant_pos.prot_seq_pos_id IS 'Foreign key to protein_seq_pos table, defining an amino acid position in a protein sequence';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.assay_variant_pos.variant_protein_id IS 'Foreign key to variant_protein table, defining the impact of a variant on a protein sequence position';


CREATE TABLE DB_PRIVATE_SCHEMA.biorels_datasource (
    source_name character varying(200),
    release_version character varying(200),
    date_released date
);


CREATE TABLE DB_PRIVATE_SCHEMA.biorels_timestamp (
    br_timestamp_id smallint NOT NULL,
    job_name character varying(200) NOT NULL,
    processed_date timestamp without time zone,
    current_dir character varying(50),
    last_check_date timestamp without time zone,
    is_success character(1)
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.biorels_timestamp IS 'Backend table providing timestamps information for the various jobs';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_timestamp.br_timestamp_id IS 'Primary key';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_timestamp.job_name IS 'Unique Name of a backend job';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_timestamp.processed_date IS 'Last Date this job was successfully processed';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_timestamp.current_dir IS 'Current working directory of this job';


COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_timestamp.last_check_date IS 'Last Date this job was run';



CREATE TABLE DB_PRIVATE_SCHEMA.biorels_job_history (
    br_timestamp_id smallint not null,
    run_date timestamp not null,
    time_run_sec integer not null,
    is_success character(1),
    error_msg character varying(2000));

COMMENT ON TABLE DB_PRIVATE_SCHEMA.biorels_job_history IS 'History of executed job';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_job_history.br_timestamp_id IS 'Foreign key to biorels_timestamp. Defines the job run';
COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_job_history.run_date IS 'Date/time the job was executed';
COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_job_history.time_run_sec IS 'Time in second for the execution of the job';
COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_job_history.is_success IS 'T: Successful. F: failed';
COMMENT ON COLUMN DB_PRIVATE_SCHEMA.biorels_job_history.error_msg IS 'Textual description of the error leading to failure';


CREATE TABLE DB_PRIVATE_SCHEMA.conjugate_entry (
    conjugate_entry_id integer NOT NULL,
    conjugate_smiles character varying(4000),
    conjugate_hash character varying(35),
    conjugate_symbol character varying(35),
    conjugate_name character varying(500),
    conjugate_alias character varying(500),
    conjugate_polymertype character varying(500),
    conjugate_monomertype character varying(500),
    conjugate_unconjugate character varying(4000)
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.conjugate_entry_conjugate_entry_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE DB_PRIVATE_SCHEMA.conjugate_entry_conjugate_entry_id_seq OWNED BY DB_PRIVATE_SCHEMA.conjugate_entry.conjugate_entry_id;


CREATE TABLE DB_PRIVATE_SCHEMA.conjugate_sm_map (
    conjugate_entry_id bigint NOT NULL,
    sm_molecule_id bigint NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.lipid_sm_map (
    lipid_sm_map_id integer NOT NULL,
    lipid_entry_id integer NOT NULL,
    sm_entry_id integer NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.molecular_component (
    molecular_component_id bigint NOT NULL,
    molecular_component_hash character varying(35) NOT NULL,
    molecular_component_structure_hash character varying(35) NOT NULL,
    molecular_component_structure character varying(4000),
    components character varying(4000),
    ontology_entry_id integer
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.molecular_component IS 'A molecular component. This can be a single molecule, or a set of molecules, connected (siRNA-conjugate) or not (LNP component)';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component.molecular_component_id IS 'This is the primary key of molecular component';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component.molecular_component_hash IS 'md5 hash uniquely representing a molecular component with its structure and molar_fraction - This can be a single molecule, or a set of molecules, connected (siRNA-conjugate) or not (LNP component)';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component.molecular_component_structure_hash IS 'md5 hash uniquely representing the structure';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component.ontology_entry_id IS 'Foreign key to ontology describing the type of molecule or set of molecules (LNP)';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component.components IS 'Hash of individual molecules composing this molecular component';

CREATE TABLE DB_PRIVATE_SCHEMA.molecular_component_sm_map (
    molecular_component_sm_map_id bigint NOT NULL,
    molecular_component_id bigint NOT NULL,
    sm_entry_id bigint NOT NULL,
    molar_fraction real,
    compound_type character varying(3) NOT NULL,
    CONSTRAINT molecular_component_sm_map_compound_type_check CHECK ((((compound_type)::text = 'SIN'::text) OR ((compound_type)::text = 'LIN'::text) OR ((compound_type)::text = 'CON'::text)))
);


COMMENT ON TABLE DB_PRIVATE_SCHEMA.molecular_component_sm_map IS 'Mapping table connecting an molecular entity to a small molecular entity';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component_sm_map.molecular_component_id IS 'Foreign key to an molecular entity record';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component_sm_map.sm_entry_id IS 'Foreign key to a small molecule record';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component_sm_map.molar_fraction IS 'If the small molecule is part of a mixture, provides the molar_fraction, NULL otherwise';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component_sm_map.compound_type IS 'Defines the role of the molecule, especially within in mixture. Allowed values are "SIN" for singleton molecule, "LIN" for linker, "CON" for conjugate';


CREATE TABLE DB_PRIVATE_SCHEMA.molecular_entity (
    molecular_entity_id bigint NOT NULL,
    molecular_entity_hash character varying(35) NOT NULL,
    molecular_structure_hash character varying(35) NOT NULL,
    molecular_components character varying(4000) not null,
    molecular_structure text
);



COMMENT ON TABLE DB_PRIVATE_SCHEMA.molecular_entity IS 'A molecular entity is the final molecular product. It can be a small molecule, a peptide, an antibody, a LNP, a siRNA, or a combination of them.';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_entity.molecular_entity_id IS 'This is the primary key of  molecular entity';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_entity.molecular_entity_hash IS 'md5 hash uniquely representing a molecular entity and their molarities.'; 

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_entity.molecular_structure_hash IS 'md5 hash uniquely representing the structure of a molecular entity.';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_entity.molecular_components IS 'Hash of individual molecular components composing this molecular entity';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_entity.molecular_structure IS 'Full Structure of the molecular entity. HELM or smiles';

CREATE TABLE DB_PRIVATE_SCHEMA.molecular_entity_component_map (
    molecular_entity_component_map_id bigint NOT NULL,
    molecular_entity_id bigint NOT NULL,
    molecular_component_id bigint NOT NULL,
    molar_fraction real not null
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.molecular_entity_component_map IS 'Mapping table connecting an molecular entity to its set of molecular components';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_entity_component_map.molecular_entity_id IS 'Foreign key to an molecular entity record';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_entity_component_map.molecular_component_id IS 'Foreign key to a molecular component record';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_entity_component_map.molar_fraction IS 'If the molecular component is part of a mixture, provides the molar_fraction,  otherwise should be 1';


CREATE TABLE DB_PRIVATE_SCHEMA.molecular_component_na_map (
molecular_component_na_map_id bigint not null,
molecular_component_id bigint not null,
nucleic_acid_seq_id bigint not null,
molar_fraction real,
Primary key(molecular_component_na_map_id) );
CREATE INDEX molecular_component_na_m_c ON DB_PRIVATE_SCHEMA.molecular_component_na_map (molecular_component_id);
CREATE INDEX molecular_component_na_m_n ON DB_PRIVATE_SCHEMA.molecular_component_na_map (nucleic_acid_seq_id);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.molecular_component_na_map IS 'Mapping table connecting an molecular entity to a nucleic acid entity';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component_na_map.molecular_component_id IS 'Foreign key to an molecular entity record';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component_na_map.nucleic_acid_seq_id IS 'Foreign key to a nucleic acid record';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.molecular_component_na_map.molar_fraction IS 'If the nucleic acid is part of a mixture, provides the molar_fraction, NULL otherwise';



CREATE TABLE DB_PRIVATE_SCHEMA.molecular_component_conj_map (
molecular_component_conj_map_id bigint not null,
molecular_component_id bigint not null,
conjugate_entry_id bigint not null,
molar_fraction real,
Primary key(molecular_component_conj_map_id) );
CREATE INDEX molecular_component_conj_m_c ON DB_PRIVATE_SCHEMA.molecular_component_conj_map (molecular_component_id);
CREATE INDEX molecular_component_conj_m_n ON DB_PRIVATE_SCHEMA.molecular_component_conj_map (conjugate_entry_id);



CREATE TABLE DB_PRIVATE_SCHEMA.mod_pattern (
    mod_pattern_id integer NOT NULL,
    name character varying(100),
    active_length smallint NOT NULL,
    hash character varying(100) NOT NULL,
    passive_length smallint DEFAULT 1 NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.mod_pattern_pos (
    mod_pattern_pos_id integer NOT NULL,
    mod_pattern_id integer NOT NULL,
    change character varying(100) NOT NULL,
    change_position smallint NOT NULL,
    change_location character varying(2) NOT NULL,
    isactivestrand boolean NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.mod_sirna_base (
    mod_sirna_base smallint NOT NULL,
    mod_base_name character varying(200) NOT NULL,
    mod_base_format character varying(10) NOT NULL,
    mod_base_value character varying(10) NOT NULL,
    mod_r_color smallint NOT NULL,
    mod_g_color smallint NOT NULL,
    mod_b_color smallint NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.mod_sirna_bond (
    mod_sirna_bond smallint NOT NULL,
    mod_bond_name character varying(200) NOT NULL,
    mod_bond_format character varying(10) NOT NULL,
    mod_r_color smallint NOT NULL,
    mod_g_color smallint NOT NULL,
    mod_b_color smallint NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.mod_sirna_ribose (
    mod_sirna_ribose smallint NOT NULL,
    mod_ribose_name character varying(200) NOT NULL,
    mod_ribose_format character varying(10) NOT NULL,
    mod_r_color smallint NOT NULL,
    mod_g_color smallint NOT NULL,
    mod_b_color smallint NOT NULL
);



CREATE TABLE DB_PRIVATE_SCHEMA.news (
    news_id bigint NOT NULL,
    news_title character varying(2000) NOT NULL,
    news_content text,
    news_release_date date NOT NULL,
    news_added_date timestamp without time zone NOT NULL,
    user_id integer,
    source_id smallint NOT NULL,
    news_delta text,
    news_hash character varying(50)
);


CREATE TABLE DB_PRIVATE_SCHEMA.news_clinical_trial_map (
    news_clinical_trial_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    clinical_trial_id integer NOT NULL,
    is_primary character(1)
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.news_clinical_trial_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


CREATE TABLE DB_PRIVATE_SCHEMA.news_company_map (
    news_company_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    company_entry_id integer NOT NULL,
    is_primary character(1)
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.news_company_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


CREATE TABLE DB_PRIVATE_SCHEMA.news_disease_map (
    news_disease_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    disease_entry_id integer NOT NULL,
    is_primary character(1)
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.news_disease_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


CREATE TABLE DB_PRIVATE_SCHEMA.news_document (
    news_document_id bigint NOT NULL,
    document_name character varying(2000) NOT NULL,
    document_description character varying(4000) NOT NULL,
    document_hash character varying(32) NOT NULL,
    document_content bytea NOT NULL,
    creation_date timestamp without time zone NOT NULL,
    news_id integer NOT NULL,
    document_version smallint NOT NULL,
    mime_type character varying(200) NOT NULL
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.news_document_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


CREATE TABLE DB_PRIVATE_SCHEMA.news_drug_map (
    news_drug_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    drug_entry_id integer NOT NULL,
    is_primary character(1)
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.news_drug_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


CREATE TABLE DB_PRIVATE_SCHEMA.news_gn_map (
    news_gn_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    gn_entry_id integer NOT NULL,
    is_primary character(1)
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.news_gn_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


CREATE TABLE DB_PRIVATE_SCHEMA.news_news_map (
    news_id integer NOT NULL,
    news_parent_id integer NOT NULL,
    is_match character(1)
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.news_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


CREATE TABLE DB_PRIVATE_SCHEMA.nucleic_acid_match (
    nucleic_acid_match_id integer NOT NULL,
    ref_type_id smallint NOT NULL,
    comp_type_id smallint NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.nucleic_acid_pos_match (
    nucleic_acid_pos_match_id integer NOT NULL,
    nucleic_acid_match_id integer NOT NULL,
    ref_pos_id integer NOT NULL,
    comp_pos_id integer NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.nucleic_acid_seq (
    nucleic_acid_seq_id integer NOT NULL,
    parent_seq_id integer,
    helm_string text,
    active_strand character varying(2000) NOT NULL,
    passive_strand character varying(2000),
    mod_pattern_id integer,
    nucleic_acid_type_id smallint,
    helm_hash character varying(32),
    image bytea
);


CREATE TABLE DB_PRIVATE_SCHEMA.nucleic_acid_seq_pos (
    nucleic_acid_seq_pos_id integer NOT NULL,
    nucleic_acid_seq_id integer NOT NULL,
    "position" smallint NOT NULL,
    isactivestrand character varying(1) NOT NULL,
    nucleic_acid_struct_id smallint NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.nucleic_acid_seq_prop (
    nucleic_acid_seq_prop_id integer NOT NULL,
    nucleic_acid_seq_id integer NOT NULL,
    prop_name character varying(30) NOT NULL,
    prop_value text NOT NULL
);


CREATE TABLE DB_PRIVATE_SCHEMA.nucleic_acid_struct (
    nucleic_acid_struct_id integer NOT NULL,
    nucleic_acid_name character varying(10) NOT NULL,
    orig_nucleic_acid character varying(1) NOT NULL,
    smiles text
);


CREATE TABLE DB_PRIVATE_SCHEMA.nucleic_acid_target (
    nucleic_acid_target_id integer NOT NULL,
    transcript_pos_id bigint NOT NULL,
    is_target character varying(1) NOT NULL,
    is_primary_organism character varying(1) NOT NULL,
    nucleic_acid_seq_id integer NOT NULL,
    n_mismatch smallint
);


CREATE TABLE DB_PRIVATE_SCHEMA.nucleic_acid_type (
    nucleic_acid_type_id smallint NOT NULL,
    seq_type_name character varying(30) NOT NULL,
    active_strand_shift smallint NOT NULL,
    passive_strand_shift smallint NOT NULL
);



CREATE TABLE DB_PRIVATE_SCHEMA.sharepoint_config (
    sharepoint_config_id integer NOT NULL,
    sharepoint_name character varying(2000) NOT NULL,
    sharepoint_client_id character varying(2000) NOT NULL,
    sharepoint_client_secret character varying(2000) NOT NULL,
    owner_id integer NOT NULL
);


CREATE SEQUENCE DB_PRIVATE_SCHEMA.sharepoint_config_sharepoint_config_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE DB_PRIVATE_SCHEMA.sharepoint_config_sharepoint_config_id_seq OWNED BY DB_PRIVATE_SCHEMA.sharepoint_config.sharepoint_config_id;


CREATE TABLE DB_PRIVATE_SCHEMA.sharepoint_doc_author_map (
    sharepoint_doc_clinical_trial_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    web_user_id integer NOT NULL,
    page smallint
);


CREATE TABLE DB_PRIVATE_SCHEMA.sharepoint_doc_clinical_trial_map (
    sharepoint_doc_clinical_trial_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    clinical_trial_id integer NOT NULL,
    page smallint
);


CREATE TABLE DB_PRIVATE_SCHEMA.sharepoint_doc_company_map (
    sharepoint_doc_company_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    company_entry_id integer NOT NULL,
    page smallint
);


CREATE TABLE DB_PRIVATE_SCHEMA.sharepoint_doc_disease_map (
    sharepoint_doc_company_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    disease_entry_id integer NOT NULL,
    page smallint
);


CREATE TABLE DB_PRIVATE_SCHEMA.sharepoint_doc_drug_map (
    sharepoint_doc_drug_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    drug_entry_id integer NOT NULL,
    page smallint
);


CREATE TABLE DB_PRIVATE_SCHEMA.sharepoint_doc_gn_map (
    sharepoint_doc_gn_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    gn_entry_id integer NOT NULL,
    page smallint
);


CREATE TABLE DB_PRIVATE_SCHEMA.sharepoint_document (
    sharepoint_document_id bigint NOT NULL,
    sharepoint_config_id integer NOT NULL,
    document_name character varying(2000) NOT NULL,
    document_hash character varying(32) NOT NULL,
    creation_date date NOT NULL,
    updated_date date,
    mime_type character varying(200)
);


CREATE TABLE DB_PRIVATE_SCHEMA.sm_counterion (
    sm_counterion_id integer NOT NULL,
    counterion_smiles character varying(1000) NOT NULL,
    is_valid character(1) NOT NULL
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.sm_counterion IS 'Small molecule counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_counterion.sm_counterion_id IS 'Primary key to sm_counterion.Defines a counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_counterion.counterion_smiles IS 'Counterion smiles';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_counterion.is_valid IS 'T if successfully standardized, F otherwise';



CREATE TABLE DB_PRIVATE_SCHEMA.sm_description (
    sm_description_id integer NOT NULL,
    sm_entry_id integer NOT NULL,
    description_text text NOT NULL,
    description_type character varying(40) NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_description.sm_description_id IS 'Primary key to sm_description';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_description.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_description.description_text IS 'Textual description';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_description.description_type IS 'Type of description';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_description.source_id IS 'Foreign key to source. Defines the source of the description';

CREATE TABLE DB_PRIVATE_SCHEMA.sm_entry (
    sm_entry_id bigint NOT NULL,
    inchi character varying(4000),
    inchi_key character varying(1000),
    full_smiles character varying(4000) not null,
    sm_molecule_id bigint NOT NULL,
    sm_counterion_id integer,
    is_valid character varying(1) DEFAULT 'F'::character varying,
    md5_hash character varying(35) NOT NULL
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.sm_entry IS 'Small molecule record - made of a molecule and eventually a counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_entry.sm_entry_id IS 'Primary key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_entry.inchi IS 'INCHI representation of the molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_entry.inchi_key IS 'INCHI-Key representation of the molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_entry.full_smiles IS 'Full (standardized) SMILES representation of the molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_entry.sm_molecule_id IS 'Foreign key to sm_molecule. Defines a molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_entry.sm_counterion_id IS 'Foreign key to sm_counterion. Defines a counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_entry.is_valid IS 'T if properly standardized';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_entry.md5_hash IS 'Unique hash representation of the molecule';

CREATE TABLE DB_PRIVATE_SCHEMA.sm_molecule (
    sm_molecule_id bigint NOT NULL,
    smiles character varying(4000) NOT NULL,
    is_valid character varying(1) NOT NULL,
    date_created DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sm_scaffold_id bigint
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.sm_molecule IS 'Small molecule structure';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_molecule.sm_molecule_id IS 'Primary key to molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_molecule.smiles IS 'SMILES of the molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_molecule.is_valid IS 'T if properly standardized';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_molecule.date_created IS 'Date this molecule record has been created. Can be replaced by your internal creation date';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_molecule.sm_scaffold_id IS 'Foreign key to sm_Scaffold. Scaffold of the molecule (if applicable)';

CREATE TABLE DB_PRIVATE_SCHEMA.sm_patent_map (
    sm_patent_map_id integer NOT NULL,
    sm_entry_id integer NOT NULL,
    patent_entry_id integer NOT NULL,
    field character varying(1) NOT NULL,
    field_freq smallint NOT NULL
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.sm_patent_map IS 'Patent mentioning a molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_patent_map.sm_patent_map_id IS 'Primary key to sm_patent_map, mapping patent to molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_patent_map.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_patent_map.patent_entry_id IS 'Foreign key to patent_entry. Defines a patent';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_patent_map.field IS 'Provides the field in which the molecule was found in the patent';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_patent_map.field_freq IS 'Frequency';

CREATE TABLE DB_PRIVATE_SCHEMA.sm_publi_map (
    sm_publi_map_id integer NOT NULL,
    sm_entry_id integer NOT NULL,
    pmid_entry_id integer NOT NULL,
    source_id smallint NOT NULL,
    sub_type character varying(20),
    disease_entry_id integer,
    confidence smallint DEFAULT 1
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.sm_publi_map IS 'Publication mentioning a molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_publi_map.sm_publi_map_id IS 'Primary key to sm_publi_map. ';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_publi_map.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_publi_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_publi_map.source_id IS 'Foreign key to source. Defines the source of the matching';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_publi_map.sub_type IS 'Sub type';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_publi_map.disease_entry_id IS 'Foreign key to disease_entry. Defines a disease';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_publi_map.confidence IS 'Confidence';

CREATE TABLE DB_PRIVATE_SCHEMA.sm_source (
    sm_source_id bigint NOT NULL,
    sm_entry_id bigint NOT NULL,
    source_id smallint NOT NULL,
    sm_name character varying(4000) NOT NULL,
    sm_name_status character varying(1) NOT NULL
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.sm_source IS 'Alternative names for the molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_source.sm_source_id IS 'Primary key to sm_source. Defines the names of the molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_source.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_source.source_id IS 'Foreign key to source';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_source.sm_name IS 'Name of the molecule';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_source.sm_name_status IS 'Status';

CREATE TABLE DB_PRIVATE_SCHEMA.sm_scaffold (
    sm_scaffold_id bigint NOT NULL,
    scaffold_smiles character varying(4000) NOT NULL,
    is_valid character varying(1) NOT NULL,
    date_created DATE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE DB_PRIVATE_SCHEMA.sm_scaffold IS 'Small molecule scaffold';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_scaffold.sm_scaffold_id IS 'Primary key to sm_scaffold. Defines a scaffold';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_scaffold.scaffold_smiles IS 'SMILES of the scaffold';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_scaffold.is_valid IS 'T if properly standardized';

COMMENT ON COLUMN DB_PRIVATE_SCHEMA.sm_scaffold.date_created IS 'Date this scaffold record has been created. Can be replaced by your internal creation date';



CREATE TABLE DB_PRIVATE_SCHEMA.internal_molecule (
internal_molecule_id bigint not null,
internal_id bigint not null,
molecular_entity_id bigint not null,
date_created date not null,
date_updated date not null,
primary key (internal_molecule_id));

CREATE UNIQUE INDEX internal_mol_uk ON DB_PRIVATE_SCHEMA.internal_molecule(internal_id);
CREATE INDEX internal_mol_mol ON DB_PRIVATE_SCHEMA.molecular_entity(molecular_entity_id);
CREATE INDEX internal_mol_dt ON DB_PRIVATE_SCHEMA.internal_molecule(date_created);

CREATE TABLE DB_PRIVATE_SCHEMA.internal_library (
internal_library_id integer not null,
internal_library_identifier character varying(100) not null,
internal_library_name character varying(200) not null,
internal_library_description character varying(2000),
internal_library_creator_id integer not null,
internal_library_date_created date not null,
internal_library_date_updated date not null,
Primary key (internal_library_id));

CREATE INDEX internal_library_cr_id  ON DB_PRIVATE_SCHEMA.internal_library(internal_library_creator_id);
CREATE INDEX internal_library_date_id  ON DB_PRIVATE_SCHEMA.internal_library(internal_library_date_created);
CREATE UNIQUE INDEX internal_library_uk ON DB_PRIVATE_SCHEMA.internal_library(internal_library_identifier);
CREATE UNIQUE INDEX internal_library_name_uk ON DB_PRIVATE_SCHEMA.internal_library(internal_library_name);

ALTER TABLE DB_PRIVATE_SCHEMA.internal_library  ADD CONSTRAINT internal_lib_creator_fk FOREIGN KEY (internal_library_creator_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE RESTRICT;


CREATE TABLE  DB_PRIVATE_SCHEMA.internal_library_molecular_map(
internal_library_molecular_map_id integer not null,
internal_library_id integer not null,
internal_molecule_id bigint not null,
date_added date not null
);


CREATE UNIQUE INDEX internal_lib_mol_lib_uk ON DB_PRIVATE_SCHEMA.internal_library_molecular_map(internal_library_id);
CREATE UNIQUE INDEX internal_lib_mol_mol_uk ON DB_PRIVATE_SCHEMA.internal_library_molecular_map(internal_molecule_id);
ALTER TABLE DB_PRIVATE_SCHEMA.internal_library_molecular_map  ADD CONSTRAINT internal_lib_mol_lib_fk FOREIGN KEY (internal_library_id) REFERENCES DB_PRIVATE_SCHEMA.internal_library(internal_library_id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE DB_PRIVATE_SCHEMA.internal_library_molecular_map  ADD CONSTRAINT internal_lib_mol_mol_fk FOREIGN KEY (internal_molecule_id) REFERENCES DB_PRIVATE_SCHEMA.internal_molecule(internal_molecule_id) ON UPDATE CASCADE ON DELETE RESTRICT;





ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_config ALTER COLUMN sharepoint_config_id SET DEFAULT nextval('DB_PRIVATE_SCHEMA.sharepoint_config_sharepoint_config_id_seq'::regclass);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.activity_entry
    ADD CONSTRAINT activity_entry_pkey PRIMARY KEY (activity_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_cell
    ADD CONSTRAINT assay_cell_pkey PRIMARY KEY (assay_cell_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_confidence
    ADD CONSTRAINT assay_confidence_pkey PRIMARY KEY (confidence_score);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_pkey PRIMARY KEY (assay_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_genetic
    ADD CONSTRAINT assay_genetic_pkey PRIMARY KEY (assay_genetic_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_pmid
    ADD CONSTRAINT assay_pmid_pkey PRIMARY KEY (assay_pmid_entry);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_protein
    ADD CONSTRAINT assay_protein_pkey PRIMARY KEY (assay_protein_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target_genetic_map
    ADD CONSTRAINT assay_target_genetic_map_pkey PRIMARY KEY (assay_target_genetic_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target
    ADD CONSTRAINT assay_target_pkey PRIMARY KEY (assay_target_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target_protein_map
    ADD CONSTRAINT assay_target_protein_map_pkey PRIMARY KEY (assay_target_protein_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target_type
    ADD CONSTRAINT assay_target_type_pkey PRIMARY KEY (assay_target_type_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_tissue
    ADD CONSTRAINT assay_tissue_pkey PRIMARY KEY (assay_tissue_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_type
    ADD CONSTRAINT assay_type_pk PRIMARY KEY (assay_type_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_variant
    ADD CONSTRAINT assay_variant_pkey PRIMARY KEY (assay_variant_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_variant_pos
    ADD CONSTRAINT assay_variant_pos_pkey PRIMARY KEY (assay_variant_pos_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.biorels_timestamp
    ADD CONSTRAINT biorels_timestamp_job_name_key UNIQUE (job_name);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.biorels_timestamp
    ADD CONSTRAINT biorels_timestamp_pkey PRIMARY KEY (br_timestamp_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.conjugate_entry
    ADD CONSTRAINT conjugate_entry_conjugate_hash_key UNIQUE (conjugate_hash);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.conjugate_entry
    ADD CONSTRAINT conjugate_entry_pkey PRIMARY KEY (conjugate_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.conjugate_sm_map
    ADD CONSTRAINT conjugate_sm_map_pkey PRIMARY KEY (conjugate_entry_id, sm_molecule_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.lipid_sm_map
    ADD CONSTRAINT lipid_sm_map_lipid_entry_id_sm_entry_id_key UNIQUE (lipid_entry_id, sm_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.lipid_sm_map
    ADD CONSTRAINT lipid_sm_map_pkey PRIMARY KEY (lipid_sm_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_pattern
    ADD CONSTRAINT mod_pattern_pkey PRIMARY KEY (mod_pattern_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_pattern_pos
    ADD CONSTRAINT mod_pattern_pos_pkey PRIMARY KEY (mod_pattern_pos_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_base
    ADD CONSTRAINT mod_sirna_base_mod_base_format_key UNIQUE (mod_base_format);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_base
    ADD CONSTRAINT mod_sirna_base_mod_base_name_key UNIQUE (mod_base_name);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_base
    ADD CONSTRAINT mod_sirna_base_pkey PRIMARY KEY (mod_sirna_base);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_bond
    ADD CONSTRAINT mod_sirna_bond_mod_bond_format_key UNIQUE (mod_bond_format);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_bond
    ADD CONSTRAINT mod_sirna_bond_mod_bond_name_key UNIQUE (mod_bond_name);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_bond
    ADD CONSTRAINT mod_sirna_bond_pkey PRIMARY KEY (mod_sirna_bond);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_ribose
    ADD CONSTRAINT mod_sirna_ribose_mod_ribose_format_key UNIQUE (mod_ribose_format);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_ribose
    ADD CONSTRAINT mod_sirna_ribose_mod_ribose_name_key UNIQUE (mod_ribose_name);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_sirna_ribose
    ADD CONSTRAINT mod_sirna_ribose_pkey PRIMARY KEY (mod_sirna_ribose);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.molecular_component
    ADD CONSTRAINT mol_comp_pk PRIMARY KEY (molecular_component_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.molecular_entity
    ADD CONSTRAINT mol_ent_pk PRIMARY KEY (molecular_entity_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_clinical_trial_map
    ADD CONSTRAINT news_clinical_trial_map_news_id_clinical_trial_id_key UNIQUE (news_id, clinical_trial_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_clinical_trial_map
    ADD CONSTRAINT news_clinical_trial_map_pkey PRIMARY KEY (news_clinical_trial_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_company_map
    ADD CONSTRAINT news_company_map_news_id_company_entry_id_key UNIQUE (news_id, company_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_company_map
    ADD CONSTRAINT news_company_map_pkey PRIMARY KEY (news_company_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_disease_map
    ADD CONSTRAINT news_disease_map_news_id_disease_entry_id_key UNIQUE (news_id, disease_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_disease_map
    ADD CONSTRAINT news_disease_map_pkey PRIMARY KEY (news_disease_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_document
    ADD CONSTRAINT news_document_news_id_document_name_document_version_key UNIQUE (news_id, document_name, document_version);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_document
    ADD CONSTRAINT news_document_pkey PRIMARY KEY (news_document_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_drug_map
    ADD CONSTRAINT news_drug_map_news_id_drug_entry_id_key UNIQUE (news_id, drug_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_drug_map
    ADD CONSTRAINT news_drug_map_pkey PRIMARY KEY (news_drug_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_gn_map
    ADD CONSTRAINT news_gn_map_news_id_gn_entry_id_key UNIQUE (news_id, gn_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_gn_map
    ADD CONSTRAINT news_gn_map_pkey PRIMARY KEY (news_gn_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_news_map
    ADD CONSTRAINT news_news_map_pkey PRIMARY KEY (news_id, news_parent_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news
    ADD CONSTRAINT news_pkey PRIMARY KEY (news_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news
    ADD CONSTRAINT news_uniq UNIQUE (news_title, news_release_date, source_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_match
    ADD CONSTRAINT nucleic_acid_match_pkey PRIMARY KEY (nucleic_acid_match_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_pos_match
    ADD CONSTRAINT nucleic_acid_pos_match_pkey PRIMARY KEY (nucleic_acid_pos_match_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq
    ADD CONSTRAINT nucleic_acid_seq_pkey PRIMARY KEY (nucleic_acid_seq_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq_pos
    ADD CONSTRAINT nucleic_acid_seq_pos_pkey PRIMARY KEY (nucleic_acid_seq_pos_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq_prop
    ADD CONSTRAINT nucleic_acid_seq_prop_nucleic_acid_seq_id_prop_name_key UNIQUE (nucleic_acid_seq_id, prop_name);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq_prop
    ADD CONSTRAINT nucleic_acid_seq_prop_pkey PRIMARY KEY (nucleic_acid_seq_prop_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_struct
    ADD CONSTRAINT nucleic_acid_struct_pkey PRIMARY KEY (nucleic_acid_struct_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_target
    ADD CONSTRAINT nucleic_acid_target_pkey PRIMARY KEY (nucleic_acid_target_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_type
    ADD CONSTRAINT nucleic_acid_type_pkey PRIMARY KEY (nucleic_acid_type_id);



ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_config
    ADD CONSTRAINT sharepoint_config_pkey PRIMARY KEY (sharepoint_config_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_author_map
    ADD CONSTRAINT sharepoint_doc_author_map_pkey PRIMARY KEY (sharepoint_doc_clinical_trial_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_author_map
    ADD CONSTRAINT sharepoint_doc_author_map_sharepoint_document_id_web_user_i_key UNIQUE (sharepoint_document_id, web_user_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_clinical_trial_map
    ADD CONSTRAINT sharepoint_doc_clinical_trial_map_pkey PRIMARY KEY (sharepoint_doc_clinical_trial_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_clinical_trial_map
    ADD CONSTRAINT sharepoint_doc_clinical_trial_sharepoint_document_id_clinic_key UNIQUE (sharepoint_document_id, clinical_trial_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_company_map
    ADD CONSTRAINT sharepoint_doc_company_map_pkey PRIMARY KEY (sharepoint_doc_company_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_company_map
    ADD CONSTRAINT sharepoint_doc_company_map_sharepoint_document_id_company_e_key UNIQUE (sharepoint_document_id, company_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_disease_map
    ADD CONSTRAINT sharepoint_doc_disease_map_pkey PRIMARY KEY (sharepoint_doc_company_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_disease_map
    ADD CONSTRAINT sharepoint_doc_disease_map_sharepoint_document_id_disease_e_key UNIQUE (sharepoint_document_id, disease_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_drug_map
    ADD CONSTRAINT sharepoint_doc_drug_map_pkey PRIMARY KEY (sharepoint_doc_drug_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_drug_map
    ADD CONSTRAINT sharepoint_doc_drug_map_sharepoint_document_id_drug_entry_i_key UNIQUE (sharepoint_document_id, drug_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_gn_map
    ADD CONSTRAINT sharepoint_doc_gn_map_pkey PRIMARY KEY (sharepoint_doc_gn_map_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_gn_map
    ADD CONSTRAINT sharepoint_doc_gn_map_sharepoint_document_id_gn_entry_id_key UNIQUE (sharepoint_document_id, gn_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_document
    ADD CONSTRAINT sharepoint_document_pkey PRIMARY KEY (sharepoint_document_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_counterion
    ADD CONSTRAINT sm_counterion_pkey PRIMARY KEY (sm_counterion_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_description
    ADD CONSTRAINT sm_description_pkey PRIMARY KEY (sm_description_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_description
    ADD CONSTRAINT sm_description_sm_entry_id_source_id_description_type_key UNIQUE (sm_entry_id, source_id, description_type);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_entry
    ADD CONSTRAINT sm_entry_pkey PRIMARY KEY (sm_entry_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_molecule
    ADD CONSTRAINT sm_molecule_pkey PRIMARY KEY (sm_molecule_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_patent_map
    ADD CONSTRAINT sm_patent_map_pkey PRIMARY KEY (sm_patent_map_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT sm_publi_map_pkey PRIMARY KEY (sm_publi_map_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT sm_publi_map_sm_entry_id_pmid_entry_id_sub_type_source_id_d_key UNIQUE (sm_entry_id, pmid_entry_id, sub_type, source_id, disease_entry_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_source
    ADD CONSTRAINT sm_source_pkey PRIMARY KEY (sm_source_id);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_source
    ADD CONSTRAINT sm_source_sm_entry_id_source_id_sm_name_key UNIQUE (sm_entry_id, source_id, sm_name);

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_scaffold
    ADD CONSTRAINT sm_scaffold_pkey PRIMARY KEY (sm_scaffold_id);

ALTER TABLE DB_PRIVATE_SCHEMA.molecular_component_na_map 
    ADD CONSTRAINT mol_comp_na_m_c_fk FOREIGN KEY (molecular_component_id) REFERENCES DB_PRIVATE_SCHEMA.molecular_component(molecular_component_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE DB_PRIVATE_SCHEMA.molecular_component_na_map 
    ADD CONSTRAINT mol_comp_na_m_na_fk FOREIGN KEY (nucleic_acid_seq_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_seq(nucleic_acid_seq_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE DB_PRIVATE_SCHEMA.molecular_component_conj_map 
    ADD CONSTRAINT mol_comp_conj_m_c_fk FOREIGN KEY (molecular_component_id) REFERENCES DB_PRIVATE_SCHEMA.molecular_component(molecular_component_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE DB_PRIVATE_SCHEMA.molecular_component_conj_map 
    ADD CONSTRAINT mol_comp_conj_m_conj_fk FOREIGN KEY (conjugate_entry_id) REFERENCES DB_PRIVATE_SCHEMA.conjugate_entry(conjugate_entry_id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE INDEX activity_entry_active ON DB_PRIVATE_SCHEMA.activity_entry USING btree (molecular_entity_id);


CREATE INDEX activity_entry_assay ON DB_PRIVATE_SCHEMA.activity_entry USING btree (assay_entry_id);


CREATE INDEX activity_entry_source ON DB_PRIVATE_SCHEMA.activity_entry USING btree (source_id);


CREATE UNIQUE INDEX assay_cell_id1 ON DB_PRIVATE_SCHEMA.assay_cell USING btree (chembl_id);


CREATE INDEX assay_cell_id2 ON DB_PRIVATE_SCHEMA.assay_cell USING btree (taxon_id);


CREATE INDEX assay_cell_id3 ON DB_PRIVATE_SCHEMA.assay_cell USING btree (cell_entry_id);


CREATE UNIQUE INDEX assay_cell_name ON DB_PRIVATE_SCHEMA.assay_cell USING btree (cell_name, taxon_id) WHERE (taxon_id IS NOT NULL);


CREATE UNIQUE INDEX assay_cell_name2 ON DB_PRIVATE_SCHEMA.assay_cell USING btree (cell_name, taxon_id) WHERE (taxon_id IS NULL);


CREATE INDEX assay_entry_anatomy ON DB_PRIVATE_SCHEMA.assay_entry USING btree (assay_tissue_id);


CREATE INDEX assay_entry_assay_target ON DB_PRIVATE_SCHEMA.assay_entry USING btree (assay_target_id);


CREATE INDEX assay_entry_cell ON DB_PRIVATE_SCHEMA.assay_entry USING btree (assay_cell_id);


CREATE INDEX assay_entry_source ON DB_PRIVATE_SCHEMA.assay_entry USING btree (source_id);


CREATE INDEX assay_entry_taxon ON DB_PRIVATE_SCHEMA.assay_entry USING btree (taxon_id);


CREATE UNIQUE INDEX assay_entry_uk1 ON DB_PRIVATE_SCHEMA.assay_entry USING btree (assay_name, source_id);


CREATE INDEX assay_entry_variant ON DB_PRIVATE_SCHEMA.assay_entry USING btree (assay_variant_id);


CREATE UNIQUE INDEX assay_genetic_uk1 ON DB_PRIVATE_SCHEMA.assay_genetic USING btree (taxon_id, genetic_description, accession);


CREATE INDEX assay_pmid_idx ON DB_PRIVATE_SCHEMA.assay_pmid USING btree (pmid_entry_id, assay_entry_id);


CREATE UNIQUE INDEX assay_pmid_uk ON DB_PRIVATE_SCHEMA.assay_pmid USING btree (assay_entry_id, pmid_entry_id);


CREATE INDEX assay_protein_protseq ON DB_PRIVATE_SCHEMA.assay_protein USING btree (prot_seq_id);


CREATE UNIQUE INDEX assay_protein_uk ON DB_PRIVATE_SCHEMA.assay_protein USING btree (accession, sequence_md5sum);


CREATE UNIQUE INDEX assay_target_name ON DB_PRIVATE_SCHEMA.assay_target USING btree (assay_target_name);


CREATE UNIQUE INDEX assay_target_type_uk ON DB_PRIVATE_SCHEMA.assay_target_type USING btree (assay_target_type_name);


CREATE INDEX assay_tissue_anatomy ON DB_PRIVATE_SCHEMA.assay_tissue USING btree (anatomy_entry_id);


CREATE UNIQUE INDEX assay_tissue_uk ON DB_PRIVATE_SCHEMA.assay_tissue USING btree (assay_tissue_name);


CREATE UNIQUE INDEX assay_variant_pos_idx ON DB_PRIVATE_SCHEMA.assay_variant_pos USING btree (assay_variant_id, prot_seq_pos_id);


CREATE INDEX assay_variant_pos_idx2 ON DB_PRIVATE_SCHEMA.assay_variant_pos USING btree (variant_protein_id);


CREATE UNIQUE INDEX assay_variant_uk ON DB_PRIVATE_SCHEMA.assay_variant USING btree (mutation_list, prot_seq_id) WHERE (prot_seq_id IS NOT NULL);


CREATE UNIQUE INDEX assay_variant_uk2 ON DB_PRIVATE_SCHEMA.assay_variant USING btree (mutation_list, prot_seq_id) WHERE (prot_seq_id IS NULL);


CREATE UNIQUE INDEX assay_variant_uk3 ON DB_PRIVATE_SCHEMA.assay_variant USING btree (mutation_list, ac);


CREATE UNIQUE INDEX bp_sm_md5 ON DB_PRIVATE_SCHEMA.sm_entry USING btree (md5_hash);


CREATE INDEX nn_idx1 ON DB_PRIVATE_SCHEMA.news_news_map USING btree (news_id);


CREATE INDEX nn_idx2 ON DB_PRIVATE_SCHEMA.news_news_map USING btree (news_parent_id);


CREATE INDEX nucliec_acid_seq_strand_idx ON DB_PRIVATE_SCHEMA.nucleic_acid_seq USING btree (active_strand);


CREATE INDEX p_news_title_idx ON DB_PRIVATE_SCHEMA.news USING btree (news_title);


CREATE UNIQUE INDEX pr_mod_pattern_hash_uk ON DB_PRIVATE_SCHEMA.mod_pattern USING btree (hash);


CREATE UNIQUE INDEX pr_mod_pattern_pos_uk ON DB_PRIVATE_SCHEMA.mod_pattern_pos USING btree (mod_pattern_id, change_position, change_location, isactivestrand);


CREATE UNIQUE INDEX md5_hash_cpd ON DB_PRIVATE_SCHEMA.sm_entry USING btree (md5_hash);

CREATE UNIQUE INDEX uk_mole_comp_hash ON DB_PRIVATE_SCHEMA.molecular_component USING btree (molecular_component_hash);

CREATE INDEX uk_mole_comp_s_hash ON DB_PRIVATE_SCHEMA.molecular_component USING btree (molecular_component_structure);

CREATE UNIQUE INDEX uk_mcsm ON DB_PRIVATE_SCHEMA.molecular_component_sm_map USING btree (molecular_component_id,sm_entry_id);

CREATE  INDEX id_mcsm_mc ON DB_PRIVATE_SCHEMA.molecular_component_sm_map USING btree (molecular_component_id);

CREATE  INDEX id_mcsm_se ON DB_PRIVATE_SCHEMA.molecular_component_sm_map USING btree (sm_entry_id);

CREATE UNIQUE INDEX uk_me_hash ON DB_PRIVATE_SCHEMA.molecular_entity USING btree (molecular_entity_hash);

CREATE UNIQUE INDEX uk_me_mc ON DB_PRIVATE_SCHEMA.molecular_entity USING btree (molecular_components);

CREATE UNIQUE INDEX uk_mecm ON DB_PRIVATE_SCHEMA.molecular_entity_component_map USING btree (molecular_entity_id,molecular_component_id);

CREATE  INDEX mecm_id1 ON DB_PRIVATE_SCHEMA.molecular_entity_component_map USING btree (molecular_entity_id);

CREATE  INDEX mecm_id2 ON DB_PRIVATE_SCHEMA.molecular_entity_component_map USING btree (molecular_component_id);


CREATE UNIQUE INDEX pr_nucleic_acid_namytype_uk ON DB_PRIVATE_SCHEMA.nucleic_acid_struct USING btree (nucleic_acid_name, orig_nucleic_acid);


CREATE UNIQUE INDEX pr_nucleic_acid_seq_helm_uk ON DB_PRIVATE_SCHEMA.nucleic_acid_seq USING btree (helm_string);


CREATE UNIQUE INDEX pr_nucleic_acid_target_tr_uk ON DB_PRIVATE_SCHEMA.nucleic_acid_target USING btree (transcript_pos_id, nucleic_acid_seq_id);


CREATE UNIQUE INDEX pr_nucleic_acid_type_name_uk ON DB_PRIVATE_SCHEMA.nucleic_acid_type USING btree (seq_type_name);


CREATE UNIQUE INDEX pr_nucleic_pos_strand_uk ON DB_PRIVATE_SCHEMA.nucleic_acid_seq_pos USING btree (nucleic_acid_seq_id, "position", isactivestrand);


CREATE INDEX sda_a ON DB_PRIVATE_SCHEMA.sharepoint_doc_author_map USING btree (web_user_id);


CREATE INDEX sda_sd ON DB_PRIVATE_SCHEMA.sharepoint_doc_author_map USING btree (sharepoint_document_id);


CREATE INDEX sdcm_co ON DB_PRIVATE_SCHEMA.sharepoint_doc_company_map USING btree (company_entry_id);


CREATE INDEX sdcm_sd ON DB_PRIVATE_SCHEMA.sharepoint_doc_company_map USING btree (sharepoint_document_id);


CREATE INDEX sdct_ct ON DB_PRIVATE_SCHEMA.sharepoint_doc_clinical_trial_map USING btree (clinical_trial_id);


CREATE INDEX sdct_sd ON DB_PRIVATE_SCHEMA.sharepoint_doc_clinical_trial_map USING btree (sharepoint_document_id);


CREATE INDEX sdd_sd ON DB_PRIVATE_SCHEMA.sharepoint_doc_drug_map USING btree (sharepoint_document_id);


CREATE INDEX sdd_sde ON DB_PRIVATE_SCHEMA.sharepoint_doc_drug_map USING btree (drug_entry_id);


CREATE INDEX sdds_ds ON DB_PRIVATE_SCHEMA.sharepoint_doc_disease_map USING btree (disease_entry_id);


CREATE INDEX sdds_sd ON DB_PRIVATE_SCHEMA.sharepoint_doc_disease_map USING btree (sharepoint_document_id);


CREATE INDEX sdg_g ON DB_PRIVATE_SCHEMA.sharepoint_doc_gn_map USING btree (gn_entry_id);


CREATE INDEX sdg_sd ON DB_PRIVATE_SCHEMA.sharepoint_doc_gn_map USING btree (sharepoint_document_id);

CREATE UNIQUE INDEX sm_counterion_index1 ON DB_PRIVATE_SCHEMA.sm_counterion USING btree (counterion_smiles);


CREATE INDEX sm_entry_extid ON DB_PRIVATE_SCHEMA.sm_entry USING btree (sm_molecule_id, sm_counterion_id);


CREATE INDEX sm_entry_index2 ON DB_PRIVATE_SCHEMA.sm_entry USING btree (inchi_key);


CREATE UNIQUE INDEX sm_molecule_index1 ON DB_PRIVATE_SCHEMA.sm_molecule USING btree (smiles);


CREATE INDEX sm_molecule_index2 ON DB_PRIVATE_SCHEMA.sm_molecule USING btree (sm_scaffold_id);


CREATE UNIQUE INDEX sm_scaffold_index2 ON DB_PRIVATE_SCHEMA.sm_scaffold USING btree (scaffold_smiles);


CREATE INDEX sm_patent_map_sme ON DB_PRIVATE_SCHEMA.sm_patent_map USING btree (sm_entry_id);


CREATE UNIQUE INDEX sm_patent_map_uk1 ON DB_PRIVATE_SCHEMA.sm_patent_map USING btree (patent_entry_id, sm_entry_id, field);


CREATE INDEX sm_source_id ON DB_PRIVATE_SCHEMA.sm_source USING btree (source_id);


CREATE INDEX sm_source_index1 ON DB_PRIVATE_SCHEMA.sm_source USING btree (sm_name);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.biorels_job_history
    ADD CONSTRAINT FK_biorels_job_history_job FOREIGN KEY (br_timestamp_id) REFERENCES DB_PRIVATE_SCHEMA.biorels_timestamp(br_timestamp_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.activity_entry
    ADD CONSTRAINT activity_entry_fk1 FOREIGN KEY (assay_entry_id) REFERENCES DB_PRIVATE_SCHEMA.assay_entry(assay_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.activity_entry
    ADD CONSTRAINT activity_entry_fk2 FOREIGN KEY (bao_endpoint) REFERENCES DB_SCHEMA_NAME.bioassay_onto_entry(bioassay_onto_entry_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.activity_entry
    ADD CONSTRAINT activity_entry_fk3 FOREIGN KEY (molecular_entity_id) REFERENCES DB_PRIVATE_SCHEMA.molecular_entity(molecular_entity_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.activity_entry
    ADD CONSTRAINT activity_entry_fk4 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;



ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_cell
    ADD CONSTRAINT assay_cell_fk2 FOREIGN KEY (cell_entry_id) REFERENCES DB_SCHEMA_NAME.cell_entry(cell_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_fk1 FOREIGN KEY (assay_cell_id) REFERENCES DB_PRIVATE_SCHEMA.assay_cell(assay_cell_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_fk3 FOREIGN KEY (assay_tissue_id) REFERENCES DB_PRIVATE_SCHEMA.assay_tissue(assay_tissue_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_fk4 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_fk5 FOREIGN KEY (assay_variant_id) REFERENCES DB_PRIVATE_SCHEMA.assay_variant(assay_variant_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_fk6 FOREIGN KEY (confidence_score) REFERENCES DB_PRIVATE_SCHEMA.assay_confidence(confidence_score) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_fk7 FOREIGN KEY (assay_type) REFERENCES DB_PRIVATE_SCHEMA.assay_type(assay_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_entry
    ADD CONSTRAINT assay_entry_fk8 FOREIGN KEY (assay_target_id) REFERENCES DB_PRIVATE_SCHEMA.assay_target(assay_target_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_genetic
    ADD CONSTRAINT assay_genetic_fk1 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_genetic
    ADD CONSTRAINT assay_genetic_fk2 FOREIGN KEY (gene_seq_id) REFERENCES DB_SCHEMA_NAME.gene_seq(gene_seq_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_genetic
    ADD CONSTRAINT assay_genetic_fk3 FOREIGN KEY (transcript_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_pmid
    ADD CONSTRAINT assay_pmid_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_pmid
    ADD CONSTRAINT assay_pmid_fk2 FOREIGN KEY (assay_entry_id) REFERENCES DB_PRIVATE_SCHEMA.assay_entry(assay_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_protein
    ADD CONSTRAINT assay_protein_fk1 FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_protein
    ADD CONSTRAINT assay_protein_fk2 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target
    ADD CONSTRAINT assay_target_fk1 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target
    ADD CONSTRAINT assay_target_fk2 FOREIGN KEY (assay_target_type_id) REFERENCES DB_PRIVATE_SCHEMA.assay_target_type(assay_target_type_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_tissue
    ADD CONSTRAINT assay_tissue_anatomy_fk1 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_variant
    ADD CONSTRAINT assay_variant_fk1 FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_variant_pos
    ADD CONSTRAINT assay_variant_pos_fk1 FOREIGN KEY (assay_variant_id) REFERENCES DB_PRIVATE_SCHEMA.assay_variant(assay_variant_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_variant_pos
    ADD CONSTRAINT assay_variant_pos_fk2 FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_variant_pos
    ADD CONSTRAINT assay_variant_pos_fk3 FOREIGN KEY (variant_protein_id) REFERENCES DB_SCHEMA_NAME.variant_protein_map(variant_protein_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.conjugate_sm_map
    ADD CONSTRAINT conjugate_sm_map_conjugate_entry_id_fkey FOREIGN KEY (conjugate_entry_id) REFERENCES DB_PRIVATE_SCHEMA.conjugate_entry(conjugate_entry_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.conjugate_sm_map
    ADD CONSTRAINT conjugate_sm_map_sm_molecule_id_fkey FOREIGN KEY (sm_molecule_id) REFERENCES DB_PRIVATE_SCHEMA.sm_molecule(sm_molecule_id);


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target_genetic_map
    ADD CONSTRAINT fk1_assay_target_genetic_gn FOREIGN KEY (assay_genetic_id) REFERENCES DB_PRIVATE_SCHEMA.assay_genetic(assay_genetic_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target_genetic_map
    ADD CONSTRAINT fk1_assay_target_genetic_tg FOREIGN KEY (assay_target_id) REFERENCES DB_PRIVATE_SCHEMA.assay_target(assay_target_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target_protein_map
    ADD CONSTRAINT fk1_assay_target_protein_pr FOREIGN KEY (assay_protein_id) REFERENCES DB_PRIVATE_SCHEMA.assay_protein(assay_protein_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.assay_target_protein_map
    ADD CONSTRAINT fk1_assay_target_protein_tg FOREIGN KEY (assay_target_id) REFERENCES DB_PRIVATE_SCHEMA.assay_target(assay_target_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE DB_PRIVATE_SCHEMA.internal_molecule  
    ADD CONSTRAINT internal_mol_mol_en_fk FOREIGN KEY (molecular_entity_id) REFERENCES DB_PRIVATE_SCHEMA.molecular_entity(molecular_entity_id) ON UPDATE CASCADE ON DELETE RESTRICT;



ALTER TABLE ONLY DB_PRIVATE_SCHEMA.mod_pattern_pos
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_mod_pattern_pos_mod_pattern_id FOREIGN KEY (mod_pattern_id) REFERENCES DB_PRIVATE_SCHEMA.mod_pattern(mod_pattern_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.molecular_component_sm_map
    ADD CONSTRAINT mcsm_fk_sm FOREIGN KEY (sm_entry_id) REFERENCES DB_PRIVATE_SCHEMA.sm_entry(sm_entry_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.molecular_component_sm_map
    ADD CONSTRAINT mcsm_fk_mc FOREIGN KEY (molecular_component_id) REFERENCES DB_PRIVATE_SCHEMA.molecular_component(molecular_component_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.molecular_entity_component_map
    ADD CONSTRAINT mcsm_fk_sm FOREIGN KEY (molecular_entity_id) REFERENCES DB_PRIVATE_SCHEMA.molecular_entity(molecular_entity_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.molecular_entity_component_map
    ADD CONSTRAINT mcsm_fk_mc FOREIGN KEY (molecular_component_id) REFERENCES DB_PRIVATE_SCHEMA.molecular_component(molecular_component_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_match
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_match_comp_type_id FOREIGN KEY (comp_type_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_type(nucleic_acid_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_match
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_match_ref_type_id FOREIGN KEY (ref_type_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_type(nucleic_acid_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_pos_match
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_pos_match_nucleic_acid_pos_matc FOREIGN KEY (nucleic_acid_match_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_match(nucleic_acid_match_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_seq_mod_pattern_id FOREIGN KEY (mod_pattern_id) REFERENCES DB_PRIVATE_SCHEMA.mod_pattern(mod_pattern_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_seq_nucleic_acid_type_id FOREIGN KEY (nucleic_acid_type_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_type(nucleic_acid_type_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_seq_parent_seq_id FOREIGN KEY (parent_seq_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_seq(nucleic_acid_seq_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq_pos
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_seq_pos_nucleic_acid_seq_id FOREIGN KEY (nucleic_acid_seq_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_seq(nucleic_acid_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq_pos
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_seq_pos_nucleic_acid_type_id FOREIGN KEY (nucleic_acid_struct_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_struct(nucleic_acid_struct_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_target
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_target_nucleic_acid_seq_id FOREIGN KEY (nucleic_acid_seq_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_seq(nucleic_acid_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_target
    ADD CONSTRAINT fk_DB_PRIVATE_SCHEMA_nucleic_acid_target_transcript_pos_id FOREIGN KEY (transcript_pos_id) REFERENCES DB_SCHEMA_NAME.transcript_pos(transcript_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.nucleic_acid_seq_prop
    ADD CONSTRAINT nasp_nasid_fk FOREIGN KEY (nucleic_acid_seq_id) REFERENCES DB_PRIVATE_SCHEMA.nucleic_acid_seq(nucleic_acid_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_description
    ADD CONSTRAINT sm_description_fk1 FOREIGN KEY (sm_entry_id) REFERENCES DB_PRIVATE_SCHEMA.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_molecule
    ADD CONSTRAINT sm_molecule_fk1 FOREIGN KEY (sm_scaffold_id) REFERENCES DB_PRIVATE_SCHEMA.sm_scaffold(sm_scaffold_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_entry
    ADD CONSTRAINT sm_entry_fk1 FOREIGN KEY (sm_molecule_id) REFERENCES DB_PRIVATE_SCHEMA.sm_molecule(sm_molecule_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_entry
    ADD CONSTRAINT sm_entry_fk2 FOREIGN KEY (sm_counterion_id) REFERENCES DB_PRIVATE_SCHEMA.sm_counterion(sm_counterion_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_patent_map
    ADD CONSTRAINT sm_patent_map_fk1 FOREIGN KEY (sm_entry_id) REFERENCES DB_PRIVATE_SCHEMA.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_patent_map
    ADD CONSTRAINT sm_patent_map_fk2 FOREIGN KEY (patent_entry_id) REFERENCES DB_SCHEMA_NAME.patent_entry(patent_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT sm_publi_map_fk1 FOREIGN KEY (sm_entry_id) REFERENCES DB_PRIVATE_SCHEMA.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT sm_publi_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT sm_publi_map_fk3 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT sm_publi_map_fk4 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_source
    ADD CONSTRAINT sm_source_fk2 FOREIGN KEY (sm_entry_id) REFERENCES DB_PRIVATE_SCHEMA.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_source
    ADD CONSTRAINT sm_so_so_fk FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news
    ADD CONSTRAINT fk_news_source FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_clinical_trial_map
    ADD CONSTRAINT news_clinical_trial_fk_ct FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_clinical_trial_map
    ADD CONSTRAINT news_clinical_trial_fk_news FOREIGN KEY (news_id) REFERENCES DB_PRIVATE_SCHEMA.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_company_map
    ADD CONSTRAINT news_company_fk_disease FOREIGN KEY (company_entry_id) REFERENCES DB_SCHEMA_NAME.company_entry(company_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_company_map
    ADD CONSTRAINT news_company_fk_news FOREIGN KEY (news_id) REFERENCES DB_PRIVATE_SCHEMA.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_disease_map
    ADD CONSTRAINT news_disease_fk_disease FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_disease_map
    ADD CONSTRAINT news_disease_fk_news FOREIGN KEY (news_id) REFERENCES DB_PRIVATE_SCHEMA.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_document
    ADD CONSTRAINT news_disease_fk_news FOREIGN KEY (news_id) REFERENCES DB_PRIVATE_SCHEMA.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_drug_map
    ADD CONSTRAINT news_drug_fk_drug FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_drug_map
    ADD CONSTRAINT news_drug_fk_news FOREIGN KEY (news_id) REFERENCES DB_PRIVATE_SCHEMA.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news
    ADD CONSTRAINT news_fk_user FOREIGN KEY (user_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_gn_map
    ADD CONSTRAINT news_gn_fk_gn FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_gn_map
    ADD CONSTRAINT news_gn_fk_news FOREIGN KEY (news_id) REFERENCES DB_PRIVATE_SCHEMA.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_news_map
    ADD CONSTRAINT news_news_map_news_id_fkey FOREIGN KEY (news_id) REFERENCES DB_PRIVATE_SCHEMA.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.news_news_map
    ADD CONSTRAINT news_news_map_news_parent_id_fkey FOREIGN KEY (news_parent_id) REFERENCES DB_PRIVATE_SCHEMA.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.lipid_sm_map
    ADD CONSTRAINT p_lsm_l FOREIGN KEY (sm_entry_id) REFERENCES DB_PRIVATE_SCHEMA.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.lipid_sm_map
    ADD CONSTRAINT p_lsm_sm FOREIGN KEY (lipid_entry_id) REFERENCES DB_SCHEMA_NAME.lipid_entry(lipid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;



ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT p_sm_publi_map_fk1 FOREIGN KEY (sm_entry_id) REFERENCES DB_PRIVATE_SCHEMA.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT p_sm_publi_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT p_sm_publi_map_fk3 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sm_publi_map
    ADD CONSTRAINT p_sm_publi_map_fk4 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;



ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_author_map
    ADD CONSTRAINT sharepoint_doc_author_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_PRIVATE_SCHEMA.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_author_map
    ADD CONSTRAINT sharepoint_doc_author_map_web_user_id_fkey FOREIGN KEY (web_user_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_clinical_trial_map
    ADD CONSTRAINT sharepoint_doc_clinical_trial_map_clinical_trial_id_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_clinical_trial_map
    ADD CONSTRAINT sharepoint_doc_clinical_trial_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_PRIVATE_SCHEMA.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_company_map
    ADD CONSTRAINT sharepoint_doc_company_map_company_entry_id_fkey FOREIGN KEY (company_entry_id) REFERENCES DB_SCHEMA_NAME.company_entry(company_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_company_map
    ADD CONSTRAINT sharepoint_doc_company_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_PRIVATE_SCHEMA.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_disease_map
    ADD CONSTRAINT sharepoint_doc_disease_map_disease_entry_id_fkey FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_disease_map
    ADD CONSTRAINT sharepoint_doc_disease_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_PRIVATE_SCHEMA.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_drug_map
    ADD CONSTRAINT sharepoint_doc_drug_map_drug_entry_id_fkey FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_drug_map
    ADD CONSTRAINT sharepoint_doc_drug_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_PRIVATE_SCHEMA.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_gn_map
    ADD CONSTRAINT sharepoint_doc_gn_map_gn_entry_id_fkey FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_doc_gn_map
    ADD CONSTRAINT sharepoint_doc_gn_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_PRIVATE_SCHEMA.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_PRIVATE_SCHEMA.sharepoint_document
    ADD CONSTRAINT sharepoint_document_sharepoint_config_id_fkey FOREIGN KEY (sharepoint_config_id) REFERENCES DB_PRIVATE_SCHEMA.sharepoint_config(sharepoint_config_id) ON UPDATE CASCADE ON DELETE CASCADE;
























































































































