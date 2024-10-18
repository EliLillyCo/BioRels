
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


DROP SCHEMA IF EXISTS DB_SCHEMA_NAME CASCADE;
CREATE SCHEMA IF NOT EXISTS DB_SCHEMA_NAME;
SET  SESSION search_path TO DB_SCHEMA_NAME;

SET default_tablespace = '';

CREATE TABLE DB_SCHEMA_NAME.aaname (
    aanameid smallint NOT NULL,
    resname character varying(3) NOT NULL,
    lettername character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.aaname IS 'List of amino acids';

COMMENT ON COLUMN DB_SCHEMA_NAME.aaname.aanameid IS 'Primary key defining a given amino acid';

COMMENT ON COLUMN DB_SCHEMA_NAME.aaname.resname IS '3 Letter name of a given amino acid: ALA, PHE, TYR';

COMMENT ON COLUMN DB_SCHEMA_NAME.aaname.lettername IS '1 letter defining a given amino acid: A, F, Y ';

CREATE TABLE DB_SCHEMA_NAME.activity_entry (
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
    units character varying(100),
    unit_type character varying(100) NOT NULL,
    run_date date,
    is_active character varying(1),
    well_group_id integer,
    relation character varying(10)
);

COMMENT ON TABLE DB_SCHEMA_NAME.activity_entry IS 'Table providing activity data for an molecular entity in a given assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.activity_entry_id IS 'Unique ID for the activity row';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.assay_entry_id IS 'Foreign key to the assay table (describing an assay)';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.std_relation IS 'Symbol constraining the activity value';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.std_value IS 'Measurement transformed into common units';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.std_units IS 'Standardized experimental units';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.std_flag IS 'Shows whether the standardised columns have been curated/set (1) or just default to the published data (0)';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.std_type IS 'Standardised version of the published_activity_type (e.g. IC50 rather than Ic-50/Ic50/ic50/ic-50)';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.bao_endpoint IS 'Foreign key to the BioAssay Ontology table';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.mol_pos IS 'Numbering of the molecule as it appears in the publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.molecular_entity_id IS 'Foreign key to the molecular entity table, i.e. the compound tested';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.source_id IS 'Foreign key to the source table, i.e. where this activity data is coming from';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.value IS 'Experimental value as reported';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.units IS 'Reported experimental unit';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.unit_type IS ' Type of experimental unit';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.run_date IS 'Date the experiment was run';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.is_active IS 'Is this compound considered active (criteria to be set by scientst)';

COMMENT ON COLUMN DB_SCHEMA_NAME.activity_entry.well_group_id IS 'Identifier of the well';

CREATE TABLE DB_SCHEMA_NAME.anatomy_entry (
    anatomy_entry_id integer NOT NULL,
    anatomy_tag character varying(20) NOT NULL,
    anatomy_name character varying(2000) NOT NULL,
    anatomy_definition character varying(3000) NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.anatomy_entry IS 'Represents body parts, organs and tissues';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_entry.anatomy_entry_id IS 'Unique ID of a given anatomy part';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_entry.anatomy_tag IS 'Tag uniquely defining an anatomy part';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_entry.anatomy_name IS 'Name of the anatomy part';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_entry.anatomy_definition IS 'Description of the anatomy part';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_entry.source_id IS 'Foreign key to the source from which this anatomy part is originally defined';

CREATE TABLE DB_SCHEMA_NAME.anatomy_extdb (
    anatomy_extdb_id integer NOT NULL,
    anatomy_entry_id integer NOT NULL,
    source_id smallint NOT NULL,
    anatomy_extdb character varying(2000) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.anatomy_extdb IS 'External identifiers for anatomy parts';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_extdb.anatomy_extdb_id IS 'Unique ID for an exeternal identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_extdb.anatomy_entry_id IS 'Foreign key to the anatomy entry, i.e the anatomy part';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_extdb.source_id IS 'Foreign key to source table - provide the source from where this identifier is defined';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_extdb.anatomy_extdb IS 'External database identifier';

CREATE TABLE DB_SCHEMA_NAME.anatomy_hierarchy (
    anatomy_entry_id integer NOT NULL,
    anatomy_level smallint NOT NULL,
    anatomy_level_left integer NOT NULL,
    anatomy_level_right integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.anatomy_hierarchy IS 'Nested set representation of the Anatomy ontology. To get all children, search for inner boundaries of the query entry. To get all parent, outer boundaries';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_hierarchy.anatomy_entry_id IS 'Foreign key to Anatomy table';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_hierarchy.anatomy_level IS 'Relative level of this Anatomy ontology record in the ontology hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_hierarchy.anatomy_level_left IS 'Left boundary for the nested set representation.';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_hierarchy.anatomy_level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.anatomy_syn (
    anatomy_syn_id integer NOT NULL,
    anatomy_entry_id integer NOT NULL,
    syn_type character varying(20),
    syn_value character varying(2000),
    source_id smallint
);

COMMENT ON TABLE DB_SCHEMA_NAME.anatomy_syn IS 'Synonyms of given anatomy parts';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_syn.anatomy_syn_id IS 'Unique ID for a given anatomy synonym';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_syn.anatomy_entry_id IS 'Foreign key to anatomy table';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_syn.syn_type IS 'Type of synonym. exact, close, related ';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_syn.syn_value IS 'Anatomy synonym';

COMMENT ON COLUMN DB_SCHEMA_NAME.anatomy_syn.source_id IS 'Foreign key to source table, providing the source of this synonym';

CREATE TABLE DB_SCHEMA_NAME.assay_cell (
    assay_cell_id integer NOT NULL,
    cell_name character varying(50) NOT NULL,
    cell_description character varying(200) NOT NULL,
    cell_source_tissue character varying(50),
    chembl_id character varying(20) NOT NULL,
    taxon_id bigint,
    cell_entry_id integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_cell IS 'Cell line associated to a given assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_cell.assay_cell_id IS 'Unique ID of a cell line association to an assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_cell.cell_name IS 'Name of the cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_cell.cell_description IS 'Description of the cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_cell.cell_source_tissue IS ' Tissue this cell line is derived from';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_cell.chembl_id IS 'ChEMBL identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_cell.taxon_id IS 'Foreign key to taxon table, providing the organism this cell is derived from';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_cell.cell_entry_id IS 'Foreign key to cell table. Can be NULL';

CREATE TABLE DB_SCHEMA_NAME.assay_confidence (
    confidence_score smallint NOT NULL,
    description character varying(100) NOT NULL,
    target_mapping character varying(30) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_confidence IS 'Shows level of confidence in assignment of the precise molecular target of the assay';

CREATE TABLE DB_SCHEMA_NAME.assay_entry (
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
    date_updated date
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_entry IS 'Reported assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_entry_id IS 'Unique ID for a given assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_name IS 'Name of the assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_description IS 'Description of the reported assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_type IS 'Assay classification: B=Binding assay ; A=ADME Assay ; F =Functional assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_test_type IS 'Type of assay system (in-vitro or in-vivo)';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_category IS 'screening, confirmatory (ie: dose-response), summary, panel or other.';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.curated_by IS 'Indicates the level of curation of the target assignment. ';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.bioassay_onto_entry_id IS 'foreign key for the corresponding format type in BioAssay Ontology (e.g., cell-based, biochemical, organism-based etc)';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_cell_id IS 'Foreign Key  to assay_cell table, describing the cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_tissue_id IS 'Foreign key to the assay tissue table, tissue used in the assay system (e.g., for tissue-based assays) or from which the assay system was derived (e.g., for cell/subcellular fraction-based assays).';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.taxon_id IS 'Foreign key to the taxon table, describing the organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_variant_id IS 'Foreign key to assay_variant table, providing variant information';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.confidence_score IS 'Foreign key to assay_confidence table, describing how well the target is defined';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.source_id IS 'Foreign key to the source table, defining where the assay is reported';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.assay_target_id IS 'Foreign key to assay_target, describing the target system';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_entry.date_updated IS 'Date this record was last updated';

CREATE TABLE DB_SCHEMA_NAME.assay_genetic (
    assay_genetic_id integer NOT NULL,
    genetic_description character varying(200) NOT NULL,
    taxon_id integer NOT NULL,
    gene_seq_id bigint,
    transcript_id bigint,
    accession character varying(25),
    sequence text
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_genetic IS 'Describes a genetic target for a given assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_genetic.assay_genetic_id IS 'Unique ID for a genetic id';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_genetic.genetic_description IS 'Textual description for a genetic target';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_genetic.taxon_id IS 'Foreign key to the target organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_genetic.gene_seq_id IS 'Foreign key to gene sequence table';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_genetic.transcript_id IS 'Foreign key to transcript table';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_genetic.accession IS 'Accession Identifier of the genetic entry. Ensembl gene, transcript. Should be used as reference if there is no match to a gene_seq_id or transcript_Id';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_genetic.sequence IS 'Complete nucleotide sequence';

CREATE TABLE DB_SCHEMA_NAME.assay_pmid (
    assay_pmid_entry integer NOT NULL,
    assay_entry_id integer NOT NULL,
    pmid_entry_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_pmid IS 'Association between assay and publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_pmid.assay_pmid_entry IS 'Unique ID connecting an assay to a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_pmid.assay_entry_id IS 'Foreign key to assay_entry table, specifying an assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_pmid.pmid_entry_id IS ' Foreign key to pmid_entry table, specificying a publication';

CREATE TABLE DB_SCHEMA_NAME.assay_protein (
    assay_protein_id integer NOT NULL,
    accession character varying(25),
    sequence_md5sum character varying(32),
    prot_seq_id bigint,
    gn_entry_id bigint,
    CONSTRAINT assay_protein_either_id CHECK (((accession IS NOT NULL) OR (gn_entry_id IS NOT NULL)))
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_protein IS 'Target protein for assays';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_protein.assay_protein_id IS 'Unique ID of a target protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_protein.accession IS 'UniProt Accession ID defining a target protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_protein.sequence_md5sum IS 'md5 has of the target protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_protein.prot_seq_id IS 'Foreign key to prot_seq table, defining a protein sequence matching that accession';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_protein.gn_entry_id IS 'Foreign key to gn_entry table, defining a gene';

CREATE TABLE DB_SCHEMA_NAME.assay_target (
    assay_target_id integer NOT NULL,
    taxon_id integer,
    assay_target_name character varying(50) NOT NULL,
    assay_target_longname character varying(200) NOT NULL,
    assay_target_type_id smallint NOT NULL,
    species_group_flag smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_target IS 'Theoretical group of one or multiple protein or genetic targets';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target.assay_target_id IS 'Primary key for assay_target';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target.taxon_id IS 'Foreign key to taxon table, defining the target organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target.assay_target_name IS 'Simple name for the overall target';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target.assay_target_longname IS 'Descriptive name of the overall target';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target.assay_target_type_id IS 'Foreign key to assay_target_type, describing what type of group this target is';

CREATE TABLE DB_SCHEMA_NAME.assay_target_genetic_map (
    assay_target_genetic_map_id integer NOT NULL,
    assay_target_id integer NOT NULL,
    assay_genetic_id integer NOT NULL,
    is_homologue smallint
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_target_genetic_map IS 'Association table between a Theoretical target group and a genetic target';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_genetic_map.assay_target_genetic_map_id IS 'Unique ID defining a genetic target to a target group';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_genetic_map.assay_target_id IS 'Foreign key to assay_target table, defining a target group';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_genetic_map.assay_genetic_id IS 'Foreign key to assay_genetic table, defining a genetic target';

CREATE TABLE DB_SCHEMA_NAME.assay_target_protein_map (
    assay_target_protein_map_id integer NOT NULL,
    assay_target_id integer NOT NULL,
    assay_protein_id integer NOT NULL,
    is_homologue smallint
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_target_protein_map IS 'Association table between a Theoretical target group and a protein target';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_protein_map.assay_target_protein_map_id IS 'Unique ID defining a protein target to a target group';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_protein_map.assay_target_id IS 'Foreign key to assay_target table, defining a target group';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_protein_map.assay_protein_id IS 'Foreign key to assay_protein table, defining a protein target';

CREATE TABLE DB_SCHEMA_NAME.assay_target_type (
    assay_target_type_id smallint NOT NULL,
    assay_target_type_name character varying(30) NOT NULL,
    assay_target_type_desc character varying(250) NOT NULL,
    assay_target_type_parent character varying(30)
);

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_type.assay_target_type_id IS 'Unique ID defining a target type';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_type.assay_target_type_name IS 'Target type ';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_type.assay_target_type_desc IS 'Description of target type';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_target_type.assay_target_type_parent IS 'Higher level classification of target_type, allowing grouping of e.g., all PROTEIN targets, all NON-MOLECULAR targets etc.';

CREATE TABLE DB_SCHEMA_NAME.assay_tissue (
    assay_tissue_id integer NOT NULL,
    assay_tissue_name character varying(100) NOT NULL,
    anatomy_entry_id integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_tissue IS 'Association between an assay and the corresponding tissue/cell type';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_tissue.assay_tissue_id IS 'Primary key of assay tissue definition table';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_tissue.assay_tissue_name IS 'Tissue name as reported by assay information';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_tissue.anatomy_entry_id IS 'Foreign key to anatomy table describing tissues';

CREATE TABLE DB_SCHEMA_NAME.assay_type (
    assay_type_id character varying(1) NOT NULL,
    assay_desc character varying(250)
);

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_type.assay_type_id IS 'Single character representing assay type';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_type.assay_desc IS 'Description of assay type';

CREATE TABLE DB_SCHEMA_NAME.assay_variant (
    assay_variant_id integer NOT NULL,
    mutation_list character varying(2000) NOT NULL,
    prot_seq_id bigint,
    ac character varying(10)
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_variant IS 'List of possible variant on protein sequence defined in different assay';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_variant.assay_variant_id IS 'Unique ID defining a set of protein sequence variant(s)';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_variant.mutation_list IS 'List of variants with their positions in the protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_variant.prot_seq_id IS 'Foreign key to protein sequence entry.';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_variant.ac IS 'Protein sequence Accession ID, in case the protein is not in the database';

CREATE TABLE DB_SCHEMA_NAME.assay_variant_pos (
    assay_variant_pos_id integer NOT NULL,
    assay_variant_id integer NOT NULL,
    prot_seq_pos_id bigint,
    variant_protein_id bigint
);

COMMENT ON TABLE DB_SCHEMA_NAME.assay_variant_pos IS 'Table associating variants in assays to protein variants';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_variant_pos.assay_variant_id IS 'Foreign key to assay_variant table, defining a variant in an assay''s target';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_variant_pos.prot_seq_pos_id IS 'Foreign key to protein_seq_pos table, defining an amino acid position in a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.assay_variant_pos.variant_protein_id IS 'Foreign key to variant_protein table, defining the impact of a variant on a protein sequence position';


CREATE TABLE DB_SCHEMA_NAME.ATC_entry (
    ATC_entry_id smallint not null,
    ATC_code character varying(10) not null,
    ATC_title character varying(700) not null,
    primary key (ATC_entry_id),
    unique(ATC_code)
);

COMMENT ON TABLE DB_SCHEMA_NAME.ATC_entry IS 'Anatomical Therapeutic Chemical (ATC) classification system';

COMMENT ON COLUMN DB_SCHEMA_NAME.ATC_entry.ATC_entry_id IS 'Primary key for ATC table';

COMMENT ON COLUMN DB_SCHEMA_NAME.ATC_entry.ATC_code IS 'ATC code';

COMMENT ON COLUMN DB_SCHEMA_NAME.ATC_entry.ATC_title IS 'Textual description of the ATC code';

CREATE TABLE DB_SCHEMA_NAME.ATC_hierarchy (
    ATC_entry_id smallint not null,
    ATC_level smallint not null,
    ATC_level_left integer not null,
    ATC_level_right integer not null
);

COMMENT ON TABLE DB_SCHEMA_NAME.ATC_hierarchy IS 'Nested set representation of the ATC ontology. To get all children, search for inner boundaries of the query entry. To get all parent, outer boundaries';

COMMENT ON COLUMN DB_SCHEMA_NAME.ATC_hierarchy.ATC_entry_id IS 'Foreign key to ATC table';

COMMENT ON COLUMN DB_SCHEMA_NAME.ATC_hierarchy.ATC_level IS 'Relative level of this ATC ontology record in the ontology hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.ATC_hierarchy.ATC_level_left IS 'Left boundary for the nested set representation.';

COMMENT ON COLUMN DB_SCHEMA_NAME.ATC_hierarchy.ATC_level_right IS 'Right boundary for the nested set representation';


CREATE TABLE DB_SCHEMA_NAME.bioassay_onto_entry (
    bioassay_onto_entry_id integer NOT NULL,
    bioassay_tag_id character varying(20) NOT NULL,
    bioassay_label character varying(2000) NOT NULL,
    bioassay_definition character varying(3000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.bioassay_onto_entry IS 'BioAssay Ontology table';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_entry.bioassay_onto_entry_id IS 'Unique ID defining a Bioassay ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_entry.bioassay_tag_id IS 'BioAssay identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_entry.bioassay_label IS 'BioAssay record name';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_entry.bioassay_definition IS 'Definition of a bioassay record';

CREATE TABLE DB_SCHEMA_NAME.bioassay_onto_extdb (
    bioassay_onto_extdb_id integer NOT NULL,
    bioassay_onto_entry_id integer NOT NULL,
    source_id smallint NOT NULL,
    bioassay_onto_extdb_name character varying(2000) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.bioassay_onto_extdb IS 'External identifiers for BioAssay ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_extdb.bioassay_onto_extdb_id IS 'Unique ID for an external identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_extdb.bioassay_onto_entry_id IS 'Foreign key to the bioasay_onto_entry entry, i.e the BioAssay record';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_extdb.source_id IS 'Foreign key to source table - provide the source from where this identifier is defined';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_extdb.bioassay_onto_extdb_name IS 'External database identifier';

CREATE TABLE DB_SCHEMA_NAME.bioassay_onto_hierarchy (
    bioassay_onto_entry_id integer NOT NULL,
    bioassay_onto_level smallint NOT NULL,
    bioassay_onto_level_left integer NOT NULL,
    bioassay_onto_level_right integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.bioassay_onto_hierarchy IS 'Nested set representation of the BioAssay ontology. To get all children, search for inner boundaries of the query entry. To get all parent, outer boundaries';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_hierarchy.bioassay_onto_entry_id IS 'Foreign key to BioAssay table';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_hierarchy.bioassay_onto_level IS 'Relative level of this BioAssay ontology record in the ontology hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_hierarchy.bioassay_onto_level_left IS 'Left boundary for the nested set representation.';

COMMENT ON COLUMN DB_SCHEMA_NAME.bioassay_onto_hierarchy.bioassay_onto_level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.biorels_datasource (
    source_name character varying(200) NOT NULL,
    release_version character varying(200) NOT NULL,
    date_released date NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.biorels_datasource IS 'List all data sources and their current version processed by DB_SCHEMA_NAME';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_datasource.source_name IS 'Data source name';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_datasource.release_version IS 'Current version of this data source';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_datasource.date_released IS 'Date this data source has been made available in DB_SCHEMA_NAME';

CREATE TABLE DB_SCHEMA_NAME.biorels_timestamp (
    br_timestamp_id smallint NOT NULL,
    job_name character varying(200) NOT NULL,
    processed_date timestamp without time zone,
    current_dir character varying(50),
    last_check_date timestamp without time zone,
    is_success character(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.biorels_timestamp IS 'Backend table providing timestamps information for the various jobs';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_timestamp.br_timestamp_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_timestamp.job_name IS 'Unique Name of a backend job';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_timestamp.processed_date IS 'Last Date this job was successfully processed';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_timestamp.current_dir IS 'Current working directory of this job';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_timestamp.last_check_date IS 'Last Date this job was run';


CREATE TABLE DB_SCHEMA_NAME.biorels_job_history (
    br_timestamp_id smallint not null,
    run_date timestamp not null,
    time_run_sec integer not null,
    is_success character(1),
    error_msg character varying(2000));

COMMENT ON TABLE DB_SCHEMA_NAME.biorels_job_history IS 'History of executed job';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_job_history.br_timestamp_id IS 'Foreign key to biorels_timestamp. Defines the job run';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_job_history.run_date IS 'Date/time the job was executed';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_job_history.time_run_sec IS 'Time in second for the execution of the job';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_job_history.is_success IS 'T: Successful. F: failed';

COMMENT ON COLUMN DB_SCHEMA_NAME.biorels_job_history.error_msg IS 'Textual description of the error leading to failure';


CREATE TABLE DB_SCHEMA_NAME.cell_disease (
    cell_disease_id integer NOT NULL,
    cell_entry_id integer NOT NULL,
    disease_entry_id integer NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.cell_disease IS 'Association between a disease and a cell line ';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_disease.cell_disease_id IS 'Primary key mapping cell lines to disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_disease.cell_entry_id IS 'Foreign key to cell_entry table defining a cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_disease.disease_entry_id IS 'Foreign key to disease_entry table defining a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_disease.source_id IS 'Source of the relationship';



CREATE TABLE DB_SCHEMA_NAME.cell_entry (
    cell_entry_id integer NOT NULL,
    cell_acc character varying(200) NOT NULL,
    cell_name character varying(200) NOT NULL,
    cell_type character varying(50) NOT NULL,
    cell_donor_sex character varying(1),
    cell_donor_age character varying(40),
    cell_version smallint NOT NULL,
    date_updated timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    cell_tissue_id smallint
);

COMMENT ON TABLE DB_SCHEMA_NAME.cell_entry IS 'Definition of a cell line ';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.cell_entry_id IS 'Primary key to a cell line record';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.cell_acc IS 'Primary accession for a cell line - defined by Cellausorus';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.cell_name IS 'Primary name for a cell line - defined by Cellausorus';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.cell_type IS 'Type of cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.cell_donor_sex IS 'Gender at birth of this cell line donor';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.cell_donor_age IS 'Age of this cell line donor';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.cell_version IS 'Version of this record';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.date_updated IS 'Last updated date';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_entry.cell_tissue_id IS 'Foreign key to cell tissue table';

CREATE TABLE DB_SCHEMA_NAME.cell_patent_map (
    cell_patent_map_id smallint NOT NULL,
    cell_entry_id integer NOT NULL,
    patent_entry_id integer NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.cell_patent_map IS 'Association between a patent and a cell line. This can be either the patent defines the cell line or uses it ';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_patent_map.cell_patent_map_id IS 'Primary key to cell patent mapping table';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_patent_map.cell_entry_id IS 'Foreign key to cell line table';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_patent_map.patent_entry_id IS 'Foreign key to patent table';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_patent_map.source_id IS 'Source of the mapping';

CREATE TABLE DB_SCHEMA_NAME.cell_pmid_map (
    cell_pmid_id integer NOT NULL,
    cell_entry_id integer NOT NULL,
    pmid_entry_id integer NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.cell_pmid_map IS 'Association between a publication and a cell line ';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_pmid_map.cell_pmid_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_pmid_map.cell_entry_id IS 'Foreign key to cell_entry. Defines a cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_pmid_map.pmid_entry_id IS 'Foreign key to pmid_entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_pmid_map.source_id IS 'Foreign key to source table. Source of the connection between and cell line and a publication';

CREATE TABLE DB_SCHEMA_NAME.cell_syn (
    cell_syn_id integer NOT NULL,
    cell_syn_name character varying(400) NOT NULL,
    cell_entry_id integer NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.cell_syn IS 'Alternative names for a cell line ';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_syn.cell_syn_id IS 'Primary key of a cell line synonym';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_syn.cell_syn_name IS 'Synonym of a given cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_syn.cell_entry_id IS 'Foreign key to a cell line entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_syn.source_id IS 'Source of the synonym';

CREATE TABLE DB_SCHEMA_NAME.cell_taxon_map (
    cell_taxon_id integer NOT NULL,
    cell_entry_id integer NOT NULL,
    taxon_id integer NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.cell_taxon_map IS 'Which species this cell line has been derived from';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_taxon_map.cell_taxon_id IS 'Primary key for cell line/taxon mapping';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_taxon_map.cell_entry_id IS 'Foreign key to a cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_taxon_map.taxon_id IS 'Foreign key to a species';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_taxon_map.source_id IS 'Source of the mapping';

CREATE TABLE DB_SCHEMA_NAME.cell_tissue (
    cell_tissue_id smallint NOT NULL,
    cell_tissue_name character varying(500) NOT NULL,
    anatomy_entry_id integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.cell_tissue IS 'Which tissue this cell line has been derived from';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_tissue.cell_tissue_id IS 'Primary key to a cell line tissue definition';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_tissue.cell_tissue_name IS 'Tissue name as defined by Cellausorus';

COMMENT ON COLUMN DB_SCHEMA_NAME.cell_tissue.anatomy_entry_id IS 'Foreign key to the tissue/anatomy table';

CREATE TABLE DB_SCHEMA_NAME.chr_gn_map (
    chr_gn_map_id integer NOT NULL,
    chr_map_id integer NOT NULL,
    gn_entry_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.chr_gn_map IS 'Mapping table between chromosome location (locus) and genes';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_gn_map.chr_gn_map_id IS 'Primary key for locus to gene mapping';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_gn_map.chr_map_id IS 'Foreign key to chr_map table, i.e. the locus';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_gn_map.gn_entry_id IS 'Foreign key to the gene table.';

CREATE TABLE DB_SCHEMA_NAME.chr_map (
    chr_map_id integer NOT NULL,
    chr_id integer NOT NULL,
    map_location character varying(50) NOT NULL,
    arm character varying(8),
    subband integer,
    "position" character varying(4) DEFAULT 'NULL'::character varying,
    band character varying(8)
);

COMMENT ON TABLE DB_SCHEMA_NAME.chr_map IS 'A cytogenetic location on a chromosome, i.e locus';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_map.chr_map_id IS 'Primary key to a locus';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_map.chr_id IS 'Foreign key to the chromosome table. Chromosome on which this locus is present';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_map.map_location IS 'cytogenetic location';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_map.arm IS 'Arm of the chromosome';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_map.subband IS 'Sub bands within a band';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_map."position" IS 'Usually the same as the chromosome name';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_map.band IS 'Region on a chromosome arm that can be seen using stainin';

CREATE TABLE DB_SCHEMA_NAME.chr_seq (
    chr_seq_id smallint NOT NULL,
    refseq_name character varying(15) NOT NULL,
    refseq_version smallint NOT NULL,
    genbank_name character varying(20) NOT NULL,
    genbank_version smallint NOT NULL,
    seq_role character varying(50) NOT NULL,
    chr_seq_name character varying(100) NOT NULL,
    assembly_unit character varying(100) NOT NULL,
    chr_id integer NOT NULL,
    genome_assembly_id smallint NOT NULL,
    seq_len bigint,
    md5_seq_hash character varying(35) DEFAULT NULL::character varying,
    chr_start_pos integer,
    chr_end_pos integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.chr_seq IS 'DNA Sequence summary, representing a complete genomic sequence, contig, patch or scaffold';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.chr_seq_id IS 'Primary key of a chr_seq record';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.refseq_name IS 'Name of this DNA sequence from NCBI RefSeq';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.refseq_version IS 'Version of this DNA sequence from NCBI RefSeq';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.genbank_name IS 'Name of this DNA sequence from Genbank';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.genbank_version IS 'Version of this DNA sequence from Genbank';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.seq_role IS 'Type of sequence: primary, patch, scaffold';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.chr_seq_name IS 'Name of the chromosome sequence. Can be different from the chromosome name in the chromosome table';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.assembly_unit IS 'Specify if it is the primary assembly, Mitochondrial or other';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.chr_id IS 'Foreign key to a Chromosome record that this DNA sequence summary is describing';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.genome_assembly_id IS 'Foreign key to the genome assembly this Chromosome DNA sequence summary is based from ';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.seq_len IS 'Length for the DNA sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.md5_seq_hash IS 'md5 hash of the overall DNA sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.chr_start_pos IS 'Starting position of this DNA sequence based on the primary chromosome sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq.chr_end_pos IS 'Ending position of this DNA sequence based on the primary chromosome sequence';

CREATE TABLE DB_SCHEMA_NAME.chr_seq_pos (
    chr_seq_pos_id bigint NOT NULL,
    chr_seq_id smallint NOT NULL,
    nucl character(1) NOT NULL,
    chr_pos bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.chr_seq_pos IS 'Individual nucleotide in a DNA Sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq_pos.chr_seq_pos_id IS 'Primary key for a nucleotide in DNA';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq_pos.chr_seq_id IS 'Foreign key of a DNA Sequence summary record';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq_pos.nucl IS 'Nucleotide at that given position in that DNA Sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.chr_seq_pos.chr_pos IS 'Position of the nucleotide in the DNA Sequence';

CREATE TABLE DB_SCHEMA_NAME.chromosome (
    chr_id integer NOT NULL,
    date_created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    date_updated timestamp without time zone,
    taxon_id integer NOT NULL,
    chr_num character varying(200) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.chromosome IS 'A given chromosome in a specific organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.chromosome.chr_id IS 'Primary key of a Chromosome entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.chromosome.date_created IS 'Date this chromosome record has been created in NCBI';

COMMENT ON COLUMN DB_SCHEMA_NAME.chromosome.date_updated IS 'Date this chromosome record has been updated by NCBI';

COMMENT ON COLUMN DB_SCHEMA_NAME.chromosome.taxon_id IS 'Foreign key to the organism is chromosome exists in';

COMMENT ON COLUMN DB_SCHEMA_NAME.chromosome.chr_num IS 'Chromosome name';

CREATE TABLE DB_SCHEMA_NAME.clinical_significance (
    clin_sign_id smallint NOT NULL,
    clin_sign character varying(200) NOT NULL,
    clin_sign_desc character varying(1000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_significance IS 'Definition of the different type of clinical significance for a variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_significance.clin_sign_id IS 'Primary key for clinical significance';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_significance.clin_sign IS 'Type of the clinical significance of a variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_significance.clin_sign_desc IS 'Textual description of the clinical significance of a variant';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_significance_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_submission_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.clinical_trial (
    clinical_trial_id integer NOT NULL,
    trial_id character varying(100) NOT NULL,
    clinical_status character varying(50),
    start_date timestamp without time zone,
    source_id smallint NOT NULL,
    brief_title character varying(500),
    official_title character varying(2000),
    org_study_id character varying(200),
    brief_summary text,
    clinical_phase character varying(20),
    details json
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial IS 'Research that studies new treatments and their effect on human health';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.clinical_trial_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.trial_id IS 'Identifier of the clinical trial. Usually the NCT id for US clinical trials';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.clinical_status IS 'Current status of a clinical trial: Recruiting/Active/Completed ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.start_date IS 'Date a clinical trial has begun';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.source_id IS 'Source providing the information about this clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.brief_title IS 'Short title for the clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.official_title IS 'Official title of the clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.org_study_id IS 'Organization study identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.brief_summary IS 'Brief summary of the trical';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.clinical_phase IS 'Clinical phase (1, 2, 3, 4, 0.5, N/A)';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial.details IS 'JSON string with all the information about the trial';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_sq START 1 INCREMENT 1;


CREATE TABLE DB_SCHEMA_NAME.clinical_trial_alias (
    clinical_trial_alias_id integer NOT NULL,
    clinical_trial_id integer NOT NULL,
    alias_name character varying(2000) NOT NULL,
    alias_type character varying(10) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_alias IS 'Alternative names for a given clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_alias.clinical_trial_alias_id IS 'Primary key to clinical trial alias';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_alias.clinical_trial_id IS 'Foreign key to clinical trial entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_alias.alias_name IS 'Alias for the clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_alias.alias_type IS 'Type of alias';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_alias_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



CREATE TABLE DB_SCHEMA_NAME.clinical_trial_arm (
    clinical_trial_arm_id integer NOT NULL,
    clinical_trial_id integer NOT NULL,
    arm_label character varying(2000) NOT NULL,
    arm_type character varying(100) NOT NULL,
    arm_description text,
    primary key(clinical_Trial_arm_id)
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_arm IS 'List of arms for a given clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm.clinical_trial_arm_id IS 'Primary key to clinical trial arm';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm.clinical_trial_id IS 'Foreign key to clinical trial entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm.arm_label IS 'Label for the arm';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm.arm_type IS 'Type of arm';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm.arm_description IS 'Description of the arm';


CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_arm_sq START 1 INCREMENT 1;


CREATE TABLE DB_SCHEMA_NAME.clinical_trial_intervention (
    clinical_trial_intervention_id integer NOT NULL,
    clinical_trial_id integer NOT NULL,
    intervention_type character varying(50) NOT NULL,
    intervention_name character varying(2000) NOT NULL,
    intervention_description character varying(2000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_intervention IS 'List of interventions for a given clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_intervention.clinical_trial_intervention_id IS 'Primary key to clinical trial intervention';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_intervention.clinical_trial_id IS 'Foreign key to clinical trial entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_intervention.intervention_type IS 'Type of intervention';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_intervention.intervention_name IS 'Name of the intervention';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_intervention.intervention_description IS 'Description of the intervention';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_intervention_sq START 1 INCREMENT 1;




CREATE TABLE DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map (
    clinical_trial_intervention_drug_map_id integer NOT NULL,
    clinical_trial_intervention_id integer NOT NULL,
    drug_entry_id integer NOT NULL,
    source_id smallint not null
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map IS 'Mapping table between clinical trial intervention and drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map.clinical_trial_intervention_drug_map_id IS 'Primary key to clinical trial intervention/drug mapping';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map.clinical_trial_intervention_id IS 'Foreign key to clinical trial intervention';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map.drug_entry_id IS 'Foreign key to drug entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map.source_id IS 'Primary Source of the mapping';


CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_intervention_drug_map_sq START 1 INCREMENT 1;


CREATE TABLE DB_SCHEMA_NAME.clinical_trial_arm_intervention_map (
    clinical_trial_arm_intervention_map_id integer NOT NULL,
    clinical_trial_id integer NOT NULL,
    clinical_trial_arm_id integer NOT NULL,
    clinical_trial_intervention_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_arm_intervention_map IS 'Mapping table between clinical trial arm and intervention';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm_intervention_map.clinical_trial_arm_intervention_map_id IS 'Primary key to clinical trial arm/intervention mapping';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm_intervention_map.clinical_trial_id IS 'Foreign key to clinical trial entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm_intervention_map.clinical_trial_arm_id IS 'Foreign key to clinical trial arm';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_arm_intervention_map.clinical_trial_intervention_id IS 'Foreign key to clinical trial intervention';


CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_arm_intervention_map_sq START 1 INCREMENT 1;




CREATE TABLE DB_SCHEMA_NAME.clinical_trial_company_map (
    clinical_trial_id integer NOT NULL,
    company_entry_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_company_map IS 'Table associating company sponsors to a clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_company_map.clinical_trial_id IS 'Foreign key to clinical trial entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_company_map.company_entry_id IS 'Foreign key to company entry';


CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_company_map_sq START 1 INCREMENT 1;

CREATE TABLE DB_SCHEMA_NAME.clinical_trial_date (
    clinical_trial_date_id integer NOT NULL,
    clinical_trial_id integer NOT NULL,
    date_type character varying(100) NOT NULL,
    date_value date NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_date IS 'List of various dates describing the different checkpoints of a clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_date.clinical_trial_date_id IS 'Primary key for clinical trial date';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_date.clinical_trial_id IS 'Foreign key to a clinical trial record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_date.date_type IS 'Type of date information - data released ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_date.date_value IS 'Actual date';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_date_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.clinical_trial_condition (
    clinical_trial_condition_id integer not null,
    clinical_trial_id integer NOT NULL,
    disease_entry_id integer,
    condition_name character varying(500) not null,
    primary key (clinical_trial_condition_id)
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_condition IS 'List of conditions reported in the clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_condition.clinical_trial_id IS 'Foreign key to clinical trial entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_condition.disease_entry_id IS 'Foreign key to disease entry. Map to the condition';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_condition.condition_name IS 'Name of the Condition being studied';


CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_condition_sq START 1 INCREMENT 1;

CREATE TABLE DB_SCHEMA_NAME.clinical_trial_drug (
    clinical_trial_drug_id integer NOT NULL,
    clinical_trial_id integer NOT NULL,
    drug_disease_id integer NOT NULL,
    ot_score float
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_drug IS 'List of drug/disease association reported in the clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_drug.clinical_trial_drug_id IS 'Primary key to clinical trial drug/disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_drug.clinical_trial_id IS 'Foreign key to clinical trial entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_drug.drug_disease_id IS 'Foreign key to drug/disease entry. Map to the drug/disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_drug.ot_score IS 'Open Targets score for this drug';

CREATE UNIQUE INDEX clinical_trial_drug_clinical_trial_id_drug_disease_id_key ON DB_SCHEMA_NAME.clinical_trial_drug USING btree (clinical_trial_id, drug_disease_id);

CREATE INDEX ctd_dd_idx ON DB_SCHEMA_NAME.clinical_trial_drug USING btree (drug_disease_id);


CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_drug_sq START 1 INCREMENT 1;


CREATE TABLE DB_SCHEMA_NAME.clinical_trial_pmid_map (
    clinical_trial_pmid_id integer NOT NULL,
    clinical_trial_id integer NOT NULL,
    pmid_entry_id integer NOT NULL,
    is_results_ref character(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_trial_pmid_map IS 'List of publications reported in a given clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_pmid_map.clinical_trial_pmid_id IS 'Primary key to mapping table between clinical trial and publications';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_pmid_map.clinical_trial_id IS 'Foreign key to clinical trial entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_trial_pmid_map.pmid_entry_id IS 'Foreign key to a publication';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_trial_pmid_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.clinical_variant_disease_map (
    clinvar_disease_map_id integer NOT NULL,
    clinvar_submission_id integer NOT NULL,
    disease_entry_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_variant_disease_map IS 'List of diseases a clinical variant might be involved in';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_disease_map.clinvar_disease_map_id IS 'Primary key for mapping table between disease and clinical variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_disease_map.clinvar_submission_id IS 'Foreign key to clinical variant submission record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_disease_map.disease_entry_id IS 'Foreign key to disease record';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_variant_disease_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.clinical_variant_entry (
    clinvar_entry_id integer NOT NULL,
    clinvar_variation_id integer NOT NULL,
    clinical_variant_type_id smallint NOT NULL,
    clinical_variant_review_status smallint NOT NULL,
    n_submitters smallint NOT NULL,
    clinical_variant_name character varying(2000) NOT NULL,
    last_submitted_date date
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_variant_entry IS 'Clinical variant record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_entry.clinvar_entry_id IS 'Primary key to a clinical entry record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_entry.clinvar_variation_id IS 'Clinvar Variation identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_entry.clinical_variant_type_id IS 'Type of variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_entry.clinical_variant_review_status IS 'Has this record been reviewed';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_entry.n_submitters IS 'Number of submitters';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_entry.clinical_variant_name IS 'Clinical variant name';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_entry.last_submitted_date IS 'Last submission date';

CREATE TABLE DB_SCHEMA_NAME.clinical_variant_gn_map (
    clinvar_gn_map_id integer NOT NULL,
    clinvar_submission_id integer NOT NULL,
    gn_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_variant_gn_map IS 'List of genes potentially impacted by a clinical variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_gn_map.clinvar_gn_map_id IS 'Primary key for mapping table between clinical record and gene record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_gn_map.clinvar_submission_id IS 'Foreign key to clinical variant submission record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_gn_map.gn_entry_id IS 'Foreign key to gene record';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_variant_gn_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



CREATE TABLE DB_SCHEMA_NAME.clinical_variant_map (
    clinical_variant_map_id integer NOT NULL,
    clinvar_entry_id integer NOT NULL,
    variant_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_variant_map IS 'List of DNA Variants a clinical variant might be involved in';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_map.clinical_variant_map_id IS 'Primary key to mapping between variant and clinical record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_map.clinvar_entry_id IS 'Foreign key to a clinical entry record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_map.variant_entry_id IS 'Foreign key to a variant entry record';

CREATE TABLE DB_SCHEMA_NAME.clinical_variant_pmid_map (
    clinvar_pmid_map_id integer NOT NULL,
    clinvar_submission_id integer NOT NULL,
    pmid_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_variant_pmid_map IS 'List of publications mentioning this a clinical variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_pmid_map.clinvar_pmid_map_id IS 'Primary key associating clinical variant record to publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_pmid_map.clinvar_submission_id IS 'Foreign key to clinical variant submission record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_pmid_map.pmid_entry_id IS 'Foreign key to publication record';

CREATE TABLE DB_SCHEMA_NAME.clinical_variant_review_status (
    clinvar_review_status_id smallint NOT NULL,
    clinvar_review_status_name character varying(100) NOT NULL,
    clinvar_review_status_desc character varying(4000) NOT NULL,
    clinvar_review_status_score smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_variant_review_status IS 'List of the different review status for a clinical variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_review_status.clinvar_review_status_id IS 'Primary key defining clinical variant review status';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_review_status.clinvar_review_status_name IS 'Name of a clinical variant review status';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_review_status.clinvar_review_status_desc IS 'Description of a clinical variant review status';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_review_status.clinvar_review_status_score IS 'The higher the score, the higher the depth of the review';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_variant_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.clinical_variant_submission (
    clinvar_submission_id integer NOT NULL,
    clinvar_entry_id integer NOT NULL,
    clin_sign_id smallint NOT NULL,
    clinical_variant_review_status smallint NOT NULL,
    scv_id character varying(30) NOT NULL,
    collection_method character varying(1000),
    submitter character varying(1000),
    interpretation character varying(4000),
    last_evaluation_date date,
    comments text
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_variant_submission IS 'Submitted analysis for clinical variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.clinvar_submission_id IS 'Primary key for clinical variant submission record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.clinvar_entry_id IS 'Foreign key to a clinical entry record';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.clin_sign_id IS 'Foreign key to clinical significance table';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.clinical_variant_review_status IS 'Foreign key to clinical variant review status table';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.scv_id IS 'SCV Identifier from clinvar';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.collection_method IS 'Collection method';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.submitter IS 'Name of the submitter';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.interpretation IS 'Interpretation of the submitter';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.last_evaluation_date IS 'Evaluation date';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_submission.comments IS 'Comments';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_variant_submission_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.clinical_variant_type (
    clinical_variant_type_id smallint NOT NULL,
    clinical_variant_type character varying(50) NOT NULL,
    so_entry_id integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.clinical_variant_type IS 'Type of clinical variant - insertion, deletion, inversion, variation, mapped to sequence ontology';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_type.clinical_variant_type_id IS 'Primary key to clinical variant type';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_type.clinical_variant_type IS 'Type of clinical variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.clinical_variant_type.so_entry_id IS 'Foreign key to Sequence Ontology record';

CREATE SEQUENCE DB_SCHEMA_NAME.clinical_variant_type_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.codon (
    codon_id integer NOT NULL,
    translation_tbl_id smallint NOT NULL,
    codon_name character(3) NOT NULL,
    aa_name character(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.codon IS 'Static table providing mRNA to protein translation in different organisms';

COMMENT ON COLUMN DB_SCHEMA_NAME.codon.codon_id IS 'Primary key of a codon';

COMMENT ON COLUMN DB_SCHEMA_NAME.codon.translation_tbl_id IS 'Foreign key to Translation table specific to an organism that maps codon to amino acid';

COMMENT ON COLUMN DB_SCHEMA_NAME.codon.codon_name IS 'String representing the 3 nucleotides of a codon';

COMMENT ON COLUMN DB_SCHEMA_NAME.codon.aa_name IS 'Amino acid that is encoded by the codon';

CREATE TABLE DB_SCHEMA_NAME.company_entry (
    company_entry_id integer NOT NULL,
    company_name character varying(2000) NOT NULL,
    company_type character varying(200)
);

COMMENT ON TABLE DB_SCHEMA_NAME.company_entry IS 'List of different organisations involved in drug development';

COMMENT ON COLUMN DB_SCHEMA_NAME.company_entry.company_entry_id IS 'Primary key to a company';

COMMENT ON COLUMN DB_SCHEMA_NAME.company_entry.company_name IS 'Name of the company';

COMMENT ON COLUMN DB_SCHEMA_NAME.company_entry.company_type IS 'Type of company';

CREATE SEQUENCE DB_SCHEMA_NAME.company_entry_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.company_synonym (
    company_synonym_id integer NOT NULL,
    company_entry_id integer NOT NULL,
    company_syn_name character varying(2000) NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.company_synonym.company_synonym_id IS 'Primary key for alternative names for a company';

COMMENT ON COLUMN DB_SCHEMA_NAME.company_synonym.company_entry_id IS 'Foreign key to company entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.company_synonym.company_syn_name IS 'Alternative name';



CREATE TABLE DB_SCHEMA_NAME.conjugate_entry (
    conjugate_entry_id integer NOT NULL,
    conjugate_smiles character varying(4000),
    conjugate_hash character varying(35),
    conjugate_symbol character varying(35),
    conjugate_name character varying(500),
    conjugate_alias character varying(500),
    conjugate_polymertype character varying(500),
    conjugate_monomertype character varying(500),
    conjugate_unconjugate character varying(4000),
    primary key(conjugate_entry_id)
);


CREATE SEQUENCE DB_SCHEMA_NAME.conjugate_entry_conjugate_entry_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



CREATE TABLE DB_SCHEMA_NAME.disease_anatomy_map (
    disease_anatomy_map_id smallint NOT NULL,
    disease_entry_id integer NOT NULL,
    anatomy_entry_id integer NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.disease_anatomy_map IS 'Mapping of  disease groups specifically mapping to some tissues';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_anatomy_map.disease_anatomy_map_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_anatomy_map.disease_entry_id IS 'Foreign key to disease entry record';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_anatomy_map.anatomy_entry_id IS 'Foreign key to anatomy entry. This represent a tissue a disease class would be involved in';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_anatomy_map.source_id IS 'Foreign key to source table. Source of the mapping';

CREATE TABLE DB_SCHEMA_NAME.disease_entry (
    disease_entry_id integer NOT NULL,
    disease_tag character varying(50) NOT NULL,
    disease_name character varying(2000) NOT NULL,
    disease_definition character varying(3000),
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.disease_entry IS 'Definition of a disease, i.e. a disorder in a Human or other organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_entry.disease_entry_id IS 'Primary key representing a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_entry.disease_tag IS 'External and unique identifier defining a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_entry.disease_name IS 'Name of the disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_entry.disease_definition IS 'Simple Description of the disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_entry.source_id IS 'Foreign key to source table. Source that provided this disease entry';

CREATE TABLE DB_SCHEMA_NAME.disease_extdb (
    disease_extdb_id bigint NOT NULL,
    disease_entry_id bigint NOT NULL,
    source_id smallint NOT NULL,
    disease_extdb character varying(2000) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.disease_extdb IS 'External database identifiers for diseases';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_extdb.disease_extdb_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_extdb.disease_entry_id IS 'Foreign key to disease_entry representing a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_extdb.source_id IS 'Foreign key to source table, that provided this disease identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_extdb.disease_extdb IS 'External database identifier';

CREATE TABLE DB_SCHEMA_NAME.disease_gene_acc (
    disease_gene_acc_id integer NOT NULL,
    disease_entry_id integer NOT NULL,
    gn_entry_id bigint NOT NULL,
    all_count integer NOT NULL,
    year_count integer NOT NULL,
    speed30 double precision NOT NULL,
    accel30 double precision NOT NULL,
    speed60 double precision NOT NULL,
    accel60 double precision NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.disease_gene_acc_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.disease_entry_id IS 'Foreign key to a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.gn_entry_id IS 'Foreign key to a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.all_count IS 'Total number of publications for the pair gene/disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.year_count IS 'Total number of publications within 12 months for the pair gene/disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.speed30 IS 'Speed over the last 30 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.accel30 IS 'Acceleration over the last 30 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.speed60 IS 'Speed over the last 60 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_gene_acc.accel60 IS 'Acceleration over the last 60 months';

CREATE TABLE DB_SCHEMA_NAME.disease_hierarchy (
    disease_entry_id bigint NOT NULL,
    disease_level smallint NOT NULL,
    disease_level_left integer NOT NULL,
    disease_level_right integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.disease_hierarchy IS 'Nested set representation of the disease hierarchy. To get all children, search for inner boundaries of the query entry. To get all parent, outer boundaries';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_hierarchy.disease_entry_id IS 'Foreign key to disease table defining a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_hierarchy.disease_level IS 'Relative level of this disease record in the disease hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_hierarchy.disease_level_left IS 'Left boundary for the nested set representation.';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_hierarchy.disease_level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.disease_info (
    disease_info_id integer NOT NULL,
    disease_entry_id integer NOT NULL,
    info_type character varying(50) NOT NULL,
    source_id smallint NOT NULL,
    info_text text NOT NULL,
    info_status character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.disease_info IS 'Any textual information provided for a given disease by a given source';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_info.disease_info_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_info.disease_entry_id IS 'Foreign key to disease_entry table, i.e. a given disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_info.info_type IS 'Type of information. The title of the section';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_info.source_id IS 'Foreign key to source table, defining where this information is coming from';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_info.info_text IS 'Textual information for that disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_info.info_status IS ' Status ';

CREATE TABLE DB_SCHEMA_NAME.disease_pmid (
    disease_pmid_id integer NOT NULL,
    disease_entry_id integer NOT NULL,
    pmid_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.disease_pmid IS 'Connect publication to diseases. Can come from various sources';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_pmid.disease_pmid_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_pmid.disease_entry_id IS 'Foreign key to the corresponding disease entry ';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_pmid.pmid_entry_id IS 'Foreign key to the corresponding publication';

CREATE TABLE DB_SCHEMA_NAME.disease_syn (
    disease_syn_id integer NOT NULL,
    disease_entry_id integer NOT NULL,
    syn_type character varying(20) NOT NULL,
    syn_value character varying(2000) NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.disease_syn IS 'List of synonyms for a given disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_syn.disease_syn_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_syn.disease_entry_id IS 'Foreign key to the corresponding disease entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_syn.syn_type IS 'Type of synonym. Exact, related ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_syn.syn_value IS ' Synonym of the disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.disease_syn.source_id IS 'Foreign to the source table defining where this synonym is coming from';

CREATE TABLE DB_SCHEMA_NAME.dna_rev (
    nucl character varying(1) NOT NULL,
    cpl character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.dna_rev IS 'Nucleotide Complement table';

COMMENT ON COLUMN DB_SCHEMA_NAME.dna_rev.nucl IS 'Nucleotide';

COMMENT ON COLUMN DB_SCHEMA_NAME.dna_rev.cpl IS 'Nucleotide complement';

CREATE TABLE DB_SCHEMA_NAME.drug_atc_map (
    drug_atc_map_id integer NOT NULL,
    drug_entry_id integer NOT NULL,
    atc_entry_id smallint not null,
    primary key (drug_atc_map_id)
);

COMMENT ON TABLE DB_SCHEMA_NAME.drug_atc_map IS 'Mapping between a drug and an ATC code';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_atc_map.drug_atc_map_id IS 'Primary key for a drug ATC mapping';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_atc_map.drug_entry_id IS 'Foreign key to drug table. Defines a drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_atc_map.atc_entry_id IS 'Foreign key to ATC table. Defines an ATC code';


CREATE TABLE DB_SCHEMA_NAME.drug_disease (
    drug_disease_id integer NOT NULL,
    drug_entry_id integer NOT NULL,
    disease_entry_id integer NOT NULL,
    max_disease_phase double precision NOT NULL,
    gn_entry_id bigint
);

COMMENT ON TABLE DB_SCHEMA_NAME.drug_disease IS 'Drug indication on a given disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_disease.drug_disease_id IS 'Primary key for a drug indication';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_disease.drug_entry_id IS 'Foreign key to the drug table. ';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_disease.disease_entry_id IS 'Foreign key to disease table.';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_disease.max_disease_phase IS 'Maximal clinical phase for this drug indication';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_disease.gn_entry_id IS 'Foreign key to the gene entry. The field is provided when the target gene is known';

CREATE TABLE DB_SCHEMA_NAME.drug_entry (
    drug_entry_id integer NOT NULL,
    drug_primary_name character varying(500) NOT NULL,
    is_approved character(1) NOT NULL,
    is_withdrawn character(1) NOT NULL,
    is_investigational character(1),
    is_experimental character(1),
    is_nutraceutical character(1),
    is_illicit character(1),
    is_vet_approved character(1),
    max_clin_phase character varying(4) NOT NULL,
    drugbank_id character varying(10),
    chembl_id character varying(16),
    CONSTRAINT drug_entry_chk1 CHECK ((((is_approved)::text = 'T'::text) OR ((is_approved)::text = 'F'::text))),
    CONSTRAINT drug_entry_chk2 CHECK ((((is_withdrawn)::text = 'T'::text) OR ((is_withdrawn)::text = 'F'::text))),
    CONSTRAINT drug_entry_chk3 CHECK ((((is_investigational)::text = 'T'::text) OR ((is_investigational)::text = 'F'::text))),
    CONSTRAINT drug_entry_chk4 CHECK ((((is_experimental)::text = 'T'::text) OR ((is_experimental)::text = 'F'::text))),
    CONSTRAINT drug_entry_chk5 CHECK ((((is_nutraceutical)::text = 'T'::text) OR ((is_nutraceutical)::text = 'F'::text))),
    CONSTRAINT drug_entry_chk6 CHECK ((((is_illicit)::text = 'T'::text) OR ((is_illicit)::text = 'F'::text))),
    CONSTRAINT drug_entry_chk7 CHECK ((((is_vet_approved)::text = 'T'::text) OR ((is_vet_approved)::text = 'F'::text)))
    
);

COMMENT ON TABLE DB_SCHEMA_NAME.drug_entry IS 'A molecular entity with a physiological action';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.drug_entry_id IS 'Unique ID for a drug entry row';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.is_approved IS 'Is this drug approved for use';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.is_withdrawn IS 'Does this drug has been withdrawn from the market';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.max_clin_phase IS 'Maximal clinical phase the drug has reached';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.is_investigational IS 'has reached clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.is_experimental IS 'Drug is being researched pre-clinically';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.is_nutraceutical IS 'drugs which are regulated and processed at a pharmaceutical grade and have a demonstrable nutritional effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.is_illicit IS 'Prohibited drugs or limited in their distribution';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_entry.is_vet_approved IS 'Approved for use in animals';

CREATE TABLE DB_SCHEMA_NAME.drug_extdb (
    drug_extdb_id integer NOT NULL,
    drug_entry_id integer NOT NULL,
    source_id smallint NOT NULL,
    source_origin_id smallint not null,
    drug_extdb_value character varying(200) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.drug_extdb IS ' Identifier from external databases for drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_extdb.drug_extdb_id IS 'Primary key to drug external database identifiers';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_extdb.drug_entry_id IS 'Foreign key to drug_entry. Defines a drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_extdb.source_id IS 'Foreign key to source. Source of the identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_extdb.source_origin_id IS 'Foreign key to source. Provider of the external identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_extdb.drug_extdb_value IS 'External identifier';

CREATE TABLE DB_SCHEMA_NAME.drug_description (
 drug_description_id integer not null,
 drug_entry_id integer not null,
 source_id smallint not null,  
 text_description text not null,
 text_Type character varying(200) not null,
 primary key (drug_description_id),
 unique (drug_entry_id, source_id,text_type));
 
COMMENT ON COLUMN DB_SCHEMA_NAME.drug_description.drug_entry_id IS 'Foreign key to drug_entry. Defines a drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_description.source_id IS 'Foreign key to source. Source of the identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_description.text_description IS 'Textual description';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_description.drug_description_id IS 'Primary key';


CREATE TABLE DB_SCHEMA_NAME.drug_name (
    drug_name_id integer NOT NULL,
    drug_entry_id integer NOT NULL,
    drug_name character varying(600) NOT NULL,
    is_primary character varying(1) NOT NULL,
    is_tradename character varying(1) NOT NULL,
    source_id smallint not null
);

COMMENT ON TABLE DB_SCHEMA_NAME.drug_name IS 'Names and synonyms of a given drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_name.drug_name_id IS 'Unique ID for a drug name row';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_name.drug_entry_id IS 'Foreign key to drug table (containing the list of drugs)';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_name.drug_name IS 'Name of a drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_name.is_primary IS 'Is this name the primary name of the drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_name.is_tradename IS 'Is this drug name a trade name';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_name.source_id IS 'Foreign key to source. Source of the identifier';

CREATE TABLE DB_SCHEMA_NAME.drug_mol_entity_map (
drug_mol_entity_map_id integer not null,
drug_entry_id integer not null,
molecular_entity_id integer not null,
is_preferred boolean not null,
source_id smallint not null,
primary key (drug_mol_entity_map_id),
unique(drug_entry_id,molecular_entity_id, source_id));


COMMENT ON TABLE DB_SCHEMA_NAME.drug_mol_entity_map IS 'Mapping between a drug and a molecular entity';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_mol_entity_map.drug_mol_entity_map_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_mol_entity_map.drug_entry_id IS 'Foreign key to drug_entry. Defines a drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_mol_entity_map.molecular_entity_id IS 'Foreign key to molecular entity. Defines a molecule or set of molecules';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_mol_entity_map.is_preferred IS 'Is this the preferred small molecule for this drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_mol_entity_map.source_id IS 'Foreign key to source. Source of the identifier';

CREATE TABLE DB_SCHEMA_NAME.drug_type (
    drug_type_id smallint NOT NULL,
    drug_type_name character varying(100) NOT NULL,
    drug_type_group character varying(100) NOT NULL,
    primary key (drug_type_id)
);    

COMMENT ON TABLE DB_SCHEMA_NAME.drug_type IS 'Table descripting the different drug modality';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_type.drug_type_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_type.drug_type_name IS 'Name of the drug modality';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_type.drug_type_group IS 'Group of the drug modality';


CREATE TABLE DB_SCHEMA_NAME.drug_type_map (
    drug_type_map_id integer NOT NULL,
    drug_entry_id integer NOT NULL,
    drug_type_id smallint NOT NULL,
    primary key (drug_type_map_id)
);

COMMENT ON TABLE DB_SCHEMA_NAME.drug_type_map IS 'Mapping between a drug and a drug type';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_type_map.drug_type_map_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_type_map.drug_entry_id IS 'Foreign key to drug_entry. Defines a drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.drug_type_map.drug_type_id IS 'Foreign key to drug_type. Defines a drug type';

CREATE TABLE DB_SCHEMA_NAME.eco_entry (
    eco_entry_id integer NOT NULL,
    eco_id character varying(15) NOT NULL,
    eco_name character varying(200) NOT NULL,
    eco_description character varying(2000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.eco_entry IS 'Evidence and conclusion ontology table';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_entry.eco_entry_id IS 'Unique Id for an evidence and conclusion record';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_entry.eco_id IS 'Unique ECO identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_entry.eco_name IS 'Name of ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_entry.eco_description IS 'Textual description of an ECO ontology record';

CREATE TABLE DB_SCHEMA_NAME.eco_hierarchy (
    eco_entry_id integer NOT NULL,
    eco_level smallint NOT NULL,
    level_left integer NOT NULL,
    level_right integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.eco_hierarchy IS 'Nested set representation of the ECO ontology. To get all children, search for inner boundaries of the query entry. To get all parent, outer boundaries';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_hierarchy.eco_entry_id IS 'Foreign key to ECO table defining a Evidence and conclusion ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_hierarchy.eco_level IS 'Relative level of this ECO ontology record in the ontology hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_hierarchy.level_left IS 'Left boundary for the nested set representation.';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_hierarchy.level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.eco_rel (
    eco_rel_id integer NOT NULL,
    eco_entry_ref integer NOT NULL,
    eco_entry_comp integer NOT NULL,
    rel_type character varying(2) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.eco_rel IS 'Relationship between different ECO ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_rel.eco_rel_id IS 'Primary key defining relationship between ECO entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_rel.eco_entry_ref IS 'Foreign key to ECO_entry. Reference ECO entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_rel.eco_entry_comp IS 'Foreign key to ECO Entry. ECO entry related to the reference ECO entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.eco_rel.rel_type IS 'Relationship type';

CREATE TABLE DB_SCHEMA_NAME.efo_entry (
    efo_entry_id integer NOT NULL,
    efo_tag_id character varying(20) NOT NULL,
    efo_label character varying(2000) NOT NULL,
    is_org_class character varying(1) NOT NULL,
    efo_definition character varying(3000),
    efo_id character varying(11)
);

COMMENT ON TABLE DB_SCHEMA_NAME.efo_entry IS 'Experimental factor ontology table';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_entry.efo_entry_id IS 'Unique Id for an experimental factor record';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_entry.efo_tag_id IS 'Unique EFO identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_entry.efo_label IS 'Name of ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_entry.efo_definition IS 'Textual description of an EFO ontology record';

CREATE TABLE DB_SCHEMA_NAME.efo_extdb (
    efo_extdb_id integer NOT NULL,
    efo_entry_id integer NOT NULL,
    source_id smallint NOT NULL,
    efo_extdb_name character varying(2000) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.efo_extdb IS 'External database identifier associated to a given EFO ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_extdb.efo_extdb_id IS 'Unique ID for an external database identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_extdb.efo_entry_id IS 'Foreign key to EFO record';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_extdb.source_id IS 'Foreign key to source table (defining all the different databases)';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_extdb.efo_extdb_name IS 'External identifier name';

CREATE TABLE DB_SCHEMA_NAME.efo_hierarchy (
    efo_entry_id integer NOT NULL,
    efo_level smallint NOT NULL,
    efo_level_left integer NOT NULL,
    efo_level_right integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.efo_hierarchy IS 'Nested set representation of the EFO ontology. To get all children, search for inner boundaries of the query entry. To get all parent, outer boundaries';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_hierarchy.efo_entry_id IS 'Foreign key to EFO table defining a Evidence and conclusion ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_hierarchy.efo_level IS 'Relative level of this EFO ontology record in the ontology hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_hierarchy.efo_level_left IS 'Left boundary for the nested set representation.';

COMMENT ON COLUMN DB_SCHEMA_NAME.efo_hierarchy.efo_level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.gene_seq (
    gene_seq_id integer NOT NULL,
    gene_seq_name character varying(30) NOT NULL,
    gene_seq_version smallint,
    strand character varying(1) NOT NULL,
    start_pos bigint NOT NULL,
    end_pos bigint NOT NULL,
    biotype_id smallint NOT NULL,
    chr_seq_id smallint NOT NULL,
    gn_entry_id bigint
);

COMMENT ON TABLE DB_SCHEMA_NAME.gene_seq IS 'Location of genes in different chromosome sequences';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.gene_seq_id IS 'Primary key of a gene location on a chromosome';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.gene_seq_name IS 'Gene Sequence name.';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.gene_seq_version IS 'Version of the gene sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.strand IS 'Location of the gene: + on the position strand, - on the negative strand of the DNA';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.start_pos IS 'Starting position of the gene relative to the 5'' end of the chromosome ';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.end_pos IS 'End position of the gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.biotype_id IS 'Type of Gene. Foreign key to seq_btype';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.chr_seq_id IS 'Foreign key to Chromosome Sequence table. Defines the chromosome this gene is located on';

COMMENT ON COLUMN DB_SCHEMA_NAME.gene_seq.gn_entry_id IS 'Foreign key to gn_entry table (optional). Defines the gene this gene sequence is related to';

CREATE TABLE DB_SCHEMA_NAME.genome_assembly (
    genome_assembly_id smallint NOT NULL,
    assembly_accession character varying(30) NOT NULL,
    assembly_version smallint NOT NULL,
    assembly_name character varying(50) NOT NULL,
    taxon_id integer NOT NULL,
    last_update_date date NOT NULL,
    creation_date date NOT NULL,
    annotation text
);

COMMENT ON TABLE DB_SCHEMA_NAME.genome_assembly IS 'Version and accession of the different genome assemblies considered';

COMMENT ON COLUMN DB_SCHEMA_NAME.genome_assembly.genome_assembly_id IS 'Primary key defined is genome assembly';

COMMENT ON COLUMN DB_SCHEMA_NAME.genome_assembly.assembly_accession IS 'Genome assembly accession';

COMMENT ON COLUMN DB_SCHEMA_NAME.genome_assembly.assembly_version IS 'Current version of the genome assembly accession';

COMMENT ON COLUMN DB_SCHEMA_NAME.genome_assembly.assembly_name IS 'Name of the genome assembly';

COMMENT ON COLUMN DB_SCHEMA_NAME.genome_assembly.taxon_id IS 'Foreign key to taxon table. Define the organism this genome assembly represent';

COMMENT ON COLUMN DB_SCHEMA_NAME.genome_assembly.last_update_date IS 'Date this genome assembly was last updated';

COMMENT ON COLUMN DB_SCHEMA_NAME.genome_assembly.creation_date IS 'Date this genome assembly was first released';

COMMENT ON COLUMN DB_SCHEMA_NAME.genome_assembly.annotation IS 'Information about the assembly in json format';

CREATE TABLE DB_SCHEMA_NAME.glb_stat (
    concept_name character varying(100) NOT NULL,
    n_record bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.glb_stat IS 'Statistics for different table - depreciated';

COMMENT ON COLUMN DB_SCHEMA_NAME.glb_stat.concept_name IS 'Scientific concept for which we want to keep statistics';

COMMENT ON COLUMN DB_SCHEMA_NAME.glb_stat.n_record IS 'Statistical value (usually count of records)';

CREATE TABLE DB_SCHEMA_NAME.gn_entry (
    gn_entry_id bigint NOT NULL,
    symbol character varying(100) NOT NULL,
    full_name character varying(1500) NOT NULL,
    gene_id bigint NOT NULL,
    gene_type character varying(20),
    date_created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    date_updated timestamp without time zone,
    last_checked timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    status character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.gn_entry IS 'NCBI Gene definition - symbol, name, Identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.gn_entry_id IS 'Primary key for a gene record';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.symbol IS 'Primary symbol of a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.full_name IS 'Complete name of a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.gene_id IS 'NCBI Gene Identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.gene_type IS 'Type of gene: protein-coding, miRNA ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.date_created IS 'Date this gene record was created';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.date_updated IS 'Date this gene record was updated';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.last_checked IS 'Date this gene record was last checked';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_entry.status IS 'Status of the gene.';



CREATE TABLE DB_SCHEMA_NAME.gn_fam_map (
    gn_fam_map_id bigint NOT NULL,
    gn_family_id bigint NOT NULL,
    gn_entry_id bigint NOT NULL,
    confidence character varying(1) NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.gn_fam_map_prev (
    gn_fam_map_id bigint NOT NULL,
    gn_family_id bigint NOT NULL,
    gn_entry_id bigint NOT NULL,
    confidence character varying(1) NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.gn_history (
    gn_history_id bigint NOT NULL,
    gene_id integer NOT NULL,
    alt_gene_id character varying(10) NOT NULL,
    gn_entry_id bigint,
    date_discontinued date NOT NULL,
    tax_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.gn_history IS 'Historical gene Identifier mapping to current one';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_history.gn_history_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_history.gene_id IS 'Discontinued gene_id';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_history.alt_gene_id IS 'Alternative gene id. Null if gene_id is discontinued';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_history.gn_entry_id IS 'Foreign key to the current gn_entry table';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_history.date_discontinued IS 'Date on which the gene record was discontinued';

CREATE TABLE DB_SCHEMA_NAME.gn_info (
gn_info_id integer not null,
gn_entry_id integer not null,
source_id smallint not null,
source_entry character varying(50) not null,
info_type character varying(50) not null,
info_text text not null);

COMMENT ON TABLE DB_SCHEMA_NAME.gn_info IS 'Any textual information provided for a given gene by a given source';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_info.gn_info_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_info.gn_entry_id IS 'Foreign key to gn_entry table, i.e. a given gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_info.source_id IS 'Foreign key to source table, defining where this information is coming from';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_info.source_entry IS 'Identifier provided by the Source';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_info.info_type IS 'Type of information. The title of the section';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_info.info_text IS 'Textual information for that gene';




CREATE TABLE DB_SCHEMA_NAME.gn_prot_map (
    gn_prot_map_id bigint NOT NULL,
    gn_entry_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.gn_prot_map IS 'Mapping between uniprot (prot_entry) and gene (gn_entry)';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_prot_map.gn_prot_map_id IS 'Primary key associating a gene record to a protein record ';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_prot_map.gn_entry_id IS 'Foreign key to gn_entry, describing a gene record';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_prot_map.prot_entry_id IS 'Foreign key to prot_entry, describing a protein record';

CREATE TABLE DB_SCHEMA_NAME.gn_rel (
    gn_ortho_id bigint NOT NULL,
    gn_entry_r_id bigint,
    gn_entry_c_id bigint,
    rel_type character varying(1) DEFAULT 'O'::character varying NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.gn_rel IS 'Relationship between genes (Orthologs, paralogs, homolog)';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_rel.gn_ortho_id IS 'Primary key defining the relationship between different gene records';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_rel.gn_entry_r_id IS 'Foreign key to gn_entry, describing the reference gene record';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_rel.gn_entry_c_id IS 'Foreign key to gn_entry, describing the associated gene record';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_rel.rel_type IS 'Relationship type: O=Orthologs, P=Paralog, H=Homolog';

CREATE TABLE DB_SCHEMA_NAME.gn_syn (
    gn_syn_id bigint NOT NULL,
    syn_type character varying(1) NOT NULL,
    syn_value character varying(10000) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.gn_syn IS 'Alternative names for a gene symbol or name';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_syn.gn_syn_id IS 'Primary key describing a unique gene name or symbol';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_syn.syn_type IS 'Type of gene text: S=Symbol, N=Name';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_syn.syn_value IS 'Gene symbol/name text-  uniquely defined';

CREATE TABLE DB_SCHEMA_NAME.gn_syn_map (
    gn_syn_map_id bigint NOT NULL,
    gn_entry_id bigint NOT NULL,
    gn_syn_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.gn_syn_map IS 'Mapping synonyms to genes';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_syn_map.gn_syn_map_id IS 'Primary key mapping a gene record to its name(s)/symbol(s)';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_syn_map.gn_entry_id IS 'Foreign key to a gene record';

COMMENT ON COLUMN DB_SCHEMA_NAME.gn_syn_map.gn_syn_id IS 'Foreign key to a name/symbol';

CREATE TABLE DB_SCHEMA_NAME.go_dbref (
    go_dbref_id bigint NOT NULL,
    go_entry_id bigint NOT NULL,
    source_id smallint NOT NULL,
    db_value character varying(300) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.go_dbref IS 'Gene Ontology External identifiers';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_dbref.go_dbref_id IS 'Primary key to a Gene Ontology External identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_dbref.go_entry_id IS 'Foreign key to a Gene ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_dbref.source_id IS 'Foreign key to source table, defining the external database name';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_dbref.db_value IS 'External identifier value';

CREATE TABLE DB_SCHEMA_NAME.go_entry (
    go_entry_id bigint NOT NULL,
    ac character varying(10) NOT NULL,
    name character varying(250) NOT NULL,
    definition character varying(1500),
    namespace character varying(20) NOT NULL,
    comments character varying(2000),
    is_obsolete character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.go_entry IS 'Gene ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_entry.go_entry_id IS 'Primary key to a Gene Ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_entry.ac IS 'Gene Ontology Accession ';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_entry.name IS 'Gene Ontology name';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_entry.definition IS 'Textual definition of the Gene Ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_entry.namespace IS 'Type of record: biological_process, molecular_function ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_entry.comments IS 'Additional comments';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_entry.is_obsolete IS 'T if the record is obsolete, F otherwise';

CREATE TABLE DB_SCHEMA_NAME.go_pmid_map (
    go_pmid_map_id bigint NOT NULL,
    go_entry_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL,
    go_def_type character varying(2) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.go_pmid_map IS 'Publication describing/reporting a gene ontology concept';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_pmid_map.go_pmid_map_id IS 'Primary key mapping Gene Ontology records to publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_pmid_map.go_entry_id IS 'Gene Ontology record. foreign key to go_entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_pmid_map.pmid_entry_id IS 'Pulibcation record. Foreign key to Pmid_entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_pmid_map.go_def_type IS 'Section the publication was extracted from. DF: Definition, SY: Synonym, QR:Query';

CREATE TABLE DB_SCHEMA_NAME.go_prot_map_prev (
    go_prot_map_id bigint NOT NULL,
    go_entry_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    evidence character varying(3),
    source character varying(40),
    CONSTRAINT go_prot_map_chk1_prev CHECK (((evidence)::text = ANY (ARRAY[('EXP'::character varying)::text, ('IDA'::character varying)::text, ('IPI'::character varying)::text, ('IMP'::character varying)::text, ('IGI'::character varying)::text, ('IEP'::character varying)::text, ('HTP'::character varying)::text, ('HDA'::character varying)::text, ('HMP'::character varying)::text, ('HGI'::character varying)::text, ('HEP'::character varying)::text, ('ISS'::character varying)::text, ('ISO'::character varying)::text, ('ISA'::character varying)::text, ('ISM'::character varying)::text, ('IGC'::character varying)::text, ('IBA'::character varying)::text, ('IBD'::character varying)::text, ('IKR'::character varying)::text, ('IRD'::character varying)::text, ('RCA'::character varying)::text, ('TAS'::character varying)::text, ('NAS'::character varying)::text, ('IC'::character varying)::text, ('ND'::character varying)::text, ('IEA'::character varying)::text])))
);

CREATE TABLE DB_SCHEMA_NAME.go_rel (
    go_rel_id bigint NOT NULL,
    go_from_id bigint NOT NULL,
    go_to_id bigint NOT NULL,
    rel_type character varying(3) NOT NULL,
    subrel_type character varying(3)
);

COMMENT ON TABLE DB_SCHEMA_NAME.go_rel IS 'Relationship between the different gene ontology records';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_rel.go_rel_id IS 'Primary key for Gene Ontology relationship';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_rel.go_from_id IS 'Foreign key to GO_entry. Relationship starts from this record';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_rel.go_to_id IS 'Foreign key to GO_entry. Relationship goes to this record';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_rel.rel_type IS 'DJF: Disjoint_from ; ITO: Intersection_of ; IVO: Inverse_of ; ISA: is_a ; TRO: Transitive_over ; END: ends_during ; HAP: has_part ; NER: negatively_regulates ; OCI: Occurs_in ; PO: part_of ; PR: positively_regulates ; REG: regulates ; CON: considers';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_rel.subrel_type IS 'DJF: Disjoint_from ; ITO: Intersection_of ; IVO: Inverse_of ; ISA: is_a ; TRO: Transitive_over ; END: ends_during ; HAP: has_part ; NER: negatively_regulates ; OCI: Occurs_in ; PO: part_of ; PR: positively_regulates ; REG: regulates ; CON: considers';

CREATE TABLE DB_SCHEMA_NAME.go_syn (
    go_syn_id bigint NOT NULL,
    go_entry_id bigint,
    syn_value character varying(400) NOT NULL,
    syn_type character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.go_syn IS 'Alternative names for the different gene ontology records';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_syn.go_syn_id IS 'Gene Ontology synonyms - primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_syn.go_entry_id IS 'Foreign key to GO_entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_syn.syn_value IS 'Synonym name';

COMMENT ON COLUMN DB_SCHEMA_NAME.go_syn.syn_type IS 'N: Narrow ; B: Broad ; R: Related ; E:Exact';

CREATE TABLE DB_SCHEMA_NAME.gwas_descriptor (
    gwas_descriptor_id smallint NOT NULL,
    gwas_descriptor_name character varying(100) NOT NULL,
    gwas_descriptor_desc character varying(1000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.gwas_descriptor IS 'Statistical descriptor in gwas analysis';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_descriptor.gwas_descriptor_name IS 'Name of the statistical descriptor';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_descriptor.gwas_descriptor_desc IS 'Description of the statistical descriptor';

CREATE TABLE DB_SCHEMA_NAME.gwas_phenotype (
    gwas_phenotype_id integer NOT NULL,
    gwas_study_id integer NOT NULL,
    phenotype_name character varying(500) NOT NULL,
    phenotype_desc character varying(1000),
    phenotype_tag character varying(500) NOT NULL,
    disease_entry_id integer,
    n_cases integer,
    n_control integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.gwas_phenotype IS 'Individual phenotype analyzed within a GWAS study';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_phenotype.gwas_phenotype_id IS 'Primary key of an analyzed phenotype in a GWAS study';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_phenotype.gwas_study_id IS 'Foreign key to a gwas study record';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_phenotype.phenotype_name IS 'Name of the analyzed Phenotype';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_phenotype.phenotype_desc IS 'Description of the analyzed Phenotype';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_phenotype.disease_entry_id IS 'Foreign key to the disease table when the phenotype is a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_phenotype.n_cases IS 'Number of individuals reported having  the given phenotype in this gwas study';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_phenotype.n_control IS 'Number of individuals reported and not having  the given phenotype in this gwas study';

CREATE TABLE DB_SCHEMA_NAME.gwas_study (
    gwas_study_id smallint NOT NULL,
    gwas_study_name character varying(50) NOT NULL,
    gwas_study_type character varying(10) NOT NULL,
    gwas_study_version smallint NOT NULL,
    cohort_size integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.gwas_study IS 'Genome-Wide Association Study';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_study.gwas_study_id IS 'Primary key for a Genome-Wide Association Study';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_study.gwas_study_name IS 'Formal name of the Genome-Wide Association Study';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_study.gwas_study_type IS 'In case where multiple analysis have been performed within a GWAS study, speficy the analysis type';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_study.gwas_study_version IS 'GWAS release Version';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_study.cohort_size IS 'Number of individuals involved in this study';

CREATE TABLE DB_SCHEMA_NAME.gwas_variant (
    gwas_variant_id bigint NOT NULL,
    gwas_phenotype_id integer NOT NULL,
    variant_change_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.gwas_variant IS 'Association of a variant against a given phenotype analyzed in a GWAS study';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_variant.gwas_variant_id IS 'Primary key defining a variant analyzed for a given phenotype';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_variant.gwas_phenotype_id IS 'Foreign key to a gwas phenotype';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_variant.variant_change_id IS 'Foreign key to a variant change table';

CREATE TABLE DB_SCHEMA_NAME.gwas_variant_prop (
    gwas_variant_prop_id bigint NOT NULL,
    gwas_descriptor_id smallint NOT NULL,
    gwas_variant_id bigint NOT NULL,
    prop_value numeric(11,8) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.gwas_variant_prop IS 'Statistical values charaterizing a variant within a given phenotype';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_variant_prop.gwas_descriptor_id IS 'Primary key of statistical value';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_variant_prop.gwas_variant_id IS 'Foreign key to a phenotype-flag variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.gwas_variant_prop.prop_value IS 'Value ';


CREATE TABLE DB_SCHEMA_NAME.ip_entry (
    ip_entry_id integer NOT NULL,
    ipr_id character varying(9) NOT NULL,
    protein_count integer NOT NULL,
    short_name character varying(50) NOT NULL,
    entry_type character varying(30) NOT NULL,
    name character varying(120) NOT NULL,
    abstract text ,
    ip_level integer,
    ip_level_left integer,
    ip_level_right integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.ip_entry IS 'Protein domain definition (InterPro)';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.ip_entry_id IS 'Primary key of a protein domain record (Interpro)';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.ipr_id IS 'InterPro Identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.protein_count IS 'Number of proteins associated with this record';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.short_name IS 'Short name';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.entry_type IS 'Type of record - domain, active site, PTM, repeat, binding site ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.name IS 'Full name of the record';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.abstract IS 'Textual description';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.ip_level IS 'Level of hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.ip_level_left IS 'Left boundary for the nested set representation';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_entry.ip_level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.ip_ext_db (
    ip_ext_db_id integer NOT NULL,
    ip_entry_id integer NOT NULL,
    db_name character varying(30) NOT NULL,
    db_val character varying(2000) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.ip_ext_db IS 'Protein domain external identifiers';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_ext_db.ip_ext_db_id IS 'Primary key for external identifiers of protein domain';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_ext_db.ip_entry_id IS 'Foreign key to InterPro (protein domain) record';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_ext_db.db_name IS 'Database name';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_ext_db.db_val IS 'Database identifier';

CREATE TABLE DB_SCHEMA_NAME.ip_go_map (
    ip_go_map_id integer NOT NULL,
    ip_entry_id integer NOT NULL,
    go_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.ip_go_map IS 'Mapping between a protein domain and its potential functions (gene ontology)';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_go_map.ip_go_map_id IS 'Primary key of Gene Ontology to Protein domain mapping table';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_go_map.ip_entry_id IS 'Foreign key to IP_Entry defining a protein domain annotation';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_go_map.go_entry_id IS 'Foreign key to GO_entry defining a Gene Ontology record';

CREATE TABLE DB_SCHEMA_NAME.ip_pmid_map (
    ip_pmid_map_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL,
    ip_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.ip_pmid_map IS 'Mapping between publications and protein domains';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_pmid_map.ip_pmid_map_id IS 'Primary key of Publication to protein domain mapping table';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_pmid_map.pmid_entry_id IS 'Foreign key to PMID_Entry, defining a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_pmid_map.ip_entry_id IS 'Foreign key to IP_Entry defining a protein domain annotation';

CREATE TABLE DB_SCHEMA_NAME.ip_sign_prot_seq (
    ip_sign_prot_seq_id bigint NOT NULL,
    ip_signature_id integer NOT NULL,
    prot_seq_id bigint NOT NULL,
    start_pos integer NOT NULL,
    end_pos integer NOT NULL,
    status character varying(1) NOT NULL,
    model character varying(40) NOT NULL,
    evidence character varying(40) NOT NULL,
    score character varying(20) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.ip_sign_prot_seq IS 'Mapping between a signature and a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.ip_sign_prot_seq_id IS 'Protein signature to protein sequence primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.ip_signature_id IS 'Foreign key to IP_Signature defining a protein signature';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.prot_seq_id IS 'Foreign key to Prot_seq defining a protein isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.start_pos IS 'Starting position in the protein isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.end_pos IS 'End position in the protein isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.status IS 'T if valid, F otherwise';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.model IS 'Identifier from the bioinformatic resource';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.evidence IS 'Bioinformatic resource defining this signature onto this protein sequence region';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_sign_prot_seq.score IS 'Confidence score';

CREATE TABLE DB_SCHEMA_NAME.ip_signature (
    ip_signature_id integer NOT NULL,
    ip_entry_id integer NOT NULL,
    ip_sign_dbname character varying(50) NOT NULL,
    ip_sign_dbkey character varying(200) NOT NULL,
    ip_sign_name character varying(2000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.ip_signature IS 'Protein domain definitions (Signature) from different bioinformatic resources';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_signature.ip_signature_id IS 'Primary key for a protein domain signature';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_signature.ip_entry_id IS 'Foreign key to IP_Entry - specifiying the protein domain entry this signature matches';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_signature.ip_sign_dbname IS 'Bioinformatic resource defining this signature';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_signature.ip_sign_dbkey IS 'Bioinformatic resource identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.ip_signature.ip_sign_name IS 'Bioinformatic resource signature name';

CREATE TABLE DB_SCHEMA_NAME.lipid_entry (
    lipid_entry_id integer NOT NULL,
    lipid_tag character varying(20) NOT NULL,
    lipid_class_type character varying(40) NOT NULL,
    lipid_name character varying(500) NOT NULL,
    lipid_smiles character varying(4000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.lipid_entry IS 'Lipid class record (SwissLipid)';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_entry.lipid_entry_id IS 'Primary key of a Lipid class';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_entry.lipid_tag IS 'Lipid tag name (From SwissLipids)';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_entry.lipid_class_type IS 'Type of lipid class';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_entry.lipid_name IS 'Name of lipid class';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_entry.lipid_smiles IS 'SMILES representation of lipid class';

CREATE TABLE DB_SCHEMA_NAME.lipid_hierarchy (
    lipid_entry_id integer NOT NULL,
    lipid_level smallint NOT NULL,
    lipid_level_left bigint NOT NULL,
    lipid_level_right bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.lipid_hierarchy IS 'Lipid classification hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_hierarchy.lipid_entry_id IS 'Foreign key to Lipid_entry. Represent a lipid class';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_hierarchy.lipid_level IS 'Level in the lipid hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_hierarchy.lipid_level_left IS 'Left boundary for the nested set representation';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_hierarchy.lipid_level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.lipid_sm_map (
    lipid_sm_map_id integer NOT NULL,
    lipid_entry_id integer NOT NULL,
    sm_entry_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.lipid_sm_map IS 'Mapping between small molecules and lipid hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_sm_map.lipid_sm_map_id IS 'Primary key for lipid class to compound association';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_sm_map.lipid_entry_id IS 'Foreign key to Lipid_entry. Represent a lipid class';

COMMENT ON COLUMN DB_SCHEMA_NAME.lipid_sm_map.sm_entry_id IS 'Foreign key to sm_entry. Represent a small molecule';

CREATE TABLE DB_SCHEMA_NAME.meddra_entry (
    meddra_entry_id integer NOT NULL,
    meddra_code integer NOT NULL,
    meddra_name character varying(100) NOT NULL,
    meddra_code_type character(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.meddra_entry IS 'Meddra records';

CREATE SEQUENCE DB_SCHEMA_NAME.meddra_entry_meddra_entry_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE DB_SCHEMA_NAME.meddra_entry_meddra_entry_id_seq OWNED BY DB_SCHEMA_NAME.meddra_entry.meddra_entry_id;

CREATE TABLE DB_SCHEMA_NAME.meddra_hierarchy (
    meddra_entry_id integer NOT NULL,
    meddra_level smallint NOT NULL,
    meddra_level_left integer NOT NULL,
    meddra_level_right integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.meddra_hierarchy IS 'Nested set representation of the MedDra ontology. To get all children, search for inner boundaries of the query entry. To get all parent, outer boundaries';

COMMENT ON COLUMN DB_SCHEMA_NAME.meddra_hierarchy.meddra_entry_id IS 'Foreign key to MedDra table';

COMMENT ON COLUMN DB_SCHEMA_NAME.meddra_hierarchy.meddra_level IS 'Relative level of this MedDra ontology record in the ontology hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.meddra_hierarchy.meddra_level_left IS 'Left boundary for the nested set representation.';

COMMENT ON COLUMN DB_SCHEMA_NAME.meddra_hierarchy.meddra_level_right IS 'Right boundary for the nested set representation';



CREATE TABLE DB_SCHEMA_NAME.mod_pattern (
    mod_pattern_id integer NOT NULL,
    name character varying(100),
    active_length smallint NOT NULL,
    hash character varying(100) NOT NULL,
    passive_length smallint DEFAULT 1 NOT NULL
);


CREATE TABLE DB_SCHEMA_NAME.mod_pattern_pos (
    mod_pattern_pos_id integer NOT NULL,
    mod_pattern_id integer NOT NULL,
    change character varying(100) NOT NULL,
    change_position smallint NOT NULL,
    change_location character varying(2) NOT NULL,
    isactivestrand boolean NOT NULL
);





CREATE TABLE DB_SCHEMA_NAME.molecular_component (
    molecular_component_id bigint NOT NULL,
    molecular_component_hash character varying(35) NOT NULL,
    molecular_component_structure_hash character varying(35) NOT NULL,
    molecular_component_structure character varying(4000),
    components character varying(4000),
    ontology_entry_id integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.molecular_component IS 'A molecular component. This can be a single molecule, or a set of molecules, connected (siRNA-conjugate) or not (LNP component)';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component.molecular_component_id IS 'This is the primary key of molecular component';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component.molecular_component_hash IS 'md5 hash uniquely representing a molecular component with its structure and molar_fraction - This can be a single molecule, or a set of molecules, connected (siRNA-conjugate) or not (LNP component)';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component.molecular_component_structure_hash IS 'md5 hash uniquely representing the structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component.ontology_entry_id IS 'Foreign key to ontology describing the type of molecule or set of molecules (LNP)';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component.components IS 'Hash of individual molecules composing this molecular component';

CREATE TABLE DB_SCHEMA_NAME.molecular_component_sm_map (
    molecular_component_sm_map_id bigint NOT NULL,
    molecular_component_id bigint NOT NULL,
    sm_entry_id bigint NOT NULL,
    molar_fraction real,
    compound_type character varying(3) NOT NULL,
    CONSTRAINT molecular_component_sm_map_compound_type_check CHECK ((((compound_type)::text = 'SIN'::text) OR ((compound_type)::text = 'LIN'::text) OR ((compound_type)::text = 'CON'::text)))
);


COMMENT ON TABLE DB_SCHEMA_NAME.molecular_component_sm_map IS 'Mapping table connecting an molecular entity to a small molecular entity';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component_sm_map.molecular_component_id IS 'Foreign key to an molecular entity record';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component_sm_map.sm_entry_id IS 'Foreign key to a small molecule record';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component_sm_map.molar_fraction IS 'If the small molecule is part of a mixture, provides the molar_fraction, NULL otherwise';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component_sm_map.compound_type IS 'Defines the role of the molecule, especially within in mixture. Allowed values are "SIN" for singleton molecule, "LIN" for linker, "CON" for conjugate';


CREATE TABLE DB_SCHEMA_NAME.molecular_entity (
    molecular_entity_id bigint NOT NULL,
    molecular_entity_hash character varying(35) NOT NULL,
    molecular_structure_hash character varying(35) NOT NULL,
    molecular_components character varying(4000) not null,
    molecular_structure text
);



COMMENT ON TABLE DB_SCHEMA_NAME.molecular_entity IS 'A molecular entity is the final molecular product. It can be a small molecule, a peptide, an antibody, a LNP, a siRNA, or a combination of them.';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_entity.molecular_entity_id IS 'This is the primary key of  molecular entity';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_entity.molecular_entity_hash IS 'md5 hash uniquely representing a molecular entity and their molarities.'; 

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_entity.molecular_structure_hash IS 'md5 hash uniquely representing the structure of a molecular entity.';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_entity.molecular_components IS 'Hash of individual molecular components composing this molecular entity';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_entity.molecular_structure IS 'Full Structure of the molecular entity. HELM or smiles';

CREATE TABLE DB_SCHEMA_NAME.molecular_entity_component_map (
    molecular_entity_component_map_id bigint NOT NULL,
    molecular_entity_id bigint NOT NULL,
    molecular_component_id bigint NOT NULL,
    molar_fraction real not null
);

COMMENT ON TABLE DB_SCHEMA_NAME.molecular_entity_component_map IS 'Mapping table connecting an molecular entity to its set of molecular components';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_entity_component_map.molecular_entity_id IS 'Foreign key to an molecular entity record';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_entity_component_map.molecular_component_id IS 'Foreign key to a molecular component record';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_entity_component_map.molar_fraction IS 'If the molecular component is part of a mixture, provides the molar_fraction,  otherwise should be 1';


CREATE TABLE DB_SCHEMA_NAME.molecular_component_na_map (
molecular_component_na_map_id bigint not null,
molecular_component_id bigint not null,
nucleic_acid_seq_id bigint not null,
molar_fraction real,
Primary key(molecular_component_na_map_id) );
CREATE INDEX molecular_component_na_m_c ON DB_SCHEMA_NAME.molecular_component_na_map (molecular_component_id);
CREATE INDEX molecular_component_na_m_n ON DB_SCHEMA_NAME.molecular_component_na_map (nucleic_acid_seq_id);

COMMENT ON TABLE DB_SCHEMA_NAME.molecular_component_na_map IS 'Mapping table connecting an molecular entity to a nucleic acid entity';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component_na_map.molecular_component_id IS 'Foreign key to an molecular entity record';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component_na_map.nucleic_acid_seq_id IS 'Foreign key to a nucleic acid record';

COMMENT ON COLUMN DB_SCHEMA_NAME.molecular_component_na_map.molar_fraction IS 'If the nucleic acid is part of a mixture, provides the molar_fraction, NULL otherwise';



CREATE TABLE DB_SCHEMA_NAME.molecular_component_conj_map (
molecular_component_conj_map_id bigint not null,
molecular_component_id bigint not null,
conjugate_entry_id bigint not null,
molar_fraction real,
Primary key(molecular_component_conj_map_id) );
CREATE INDEX molecular_component_conj_m_c ON DB_SCHEMA_NAME.molecular_component_conj_map (molecular_component_id);
CREATE INDEX molecular_component_conj_m_n ON DB_SCHEMA_NAME.molecular_component_conj_map (conjugate_entry_id);




CREATE TABLE DB_SCHEMA_NAME.mrna_biotype (
    mrna_biotype_id smallint NOT NULL,
    biotype character varying(100) NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.mv_anatomy_publi (
    anatomy_entry_id integer,
    pmid_entry_id bigint,
    pmid bigint,
    publication_date timestamp without time zone
);

CREATE TABLE DB_SCHEMA_NAME.mv_disease (
    disease_tag character varying(50),
    disease_name character varying(2000),
    syn_value character varying(2000)
);

CREATE TABLE DB_SCHEMA_NAME.mv_disease_publi (
    disease_entry_id integer,
    pmid_entry_id bigint,
    pmid bigint,
    publication_date timestamp without time zone
);

CREATE TABLE DB_SCHEMA_NAME.mv_drug_publi (
    drug_entry_id bigint,
    pmid_entry_id bigint,
    pmid bigint,
    publication_date timestamp without time zone
);

CREATE TABLE DB_SCHEMA_NAME.mv_gene (
    symbol character varying(100),
    full_name character varying(1500),
    gene_id bigint,
    gn_entry_id bigint,
    syn_value character varying(10000),
    scientific_name character varying(200),
    tax_id character varying(10)
);

CREATE TABLE DB_SCHEMA_NAME.mv_gene_publi (
    gn_entry_id bigint,
    pmid_entry_id bigint,
    pmid bigint,
    publication_date timestamp without time zone
);

CREATE TABLE DB_SCHEMA_NAME.mv_gene_sp (
    symbol character varying(100) NOT NULL,
    full_name character varying(1500) NOT NULL,
    gene_id bigint NOT NULL,
    gn_entry_id bigint NOT NULL,
    syn_value character varying(10000),
    scientific_name character varying(200) NOT NULL,
    tax_id character varying(10)
);

CREATE TABLE DB_SCHEMA_NAME.mv_gene_taxon (
    gene_id bigint,
    gn_entry_id bigint,
    tax_id character varying(10),
    taxon_id integer
);

CREATE TABLE DB_SCHEMA_NAME.mv_pw_publi (
    publication_date timestamp without time zone NOT NULL,
    pw_entry_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL,
    n_gene bigint
);



CREATE TABLE DB_SCHEMA_NAME.nucleic_acid_match (
    nucleic_acid_match_id integer NOT NULL,
    ref_type_id smallint NOT NULL,
    comp_type_id smallint NOT NULL
);


CREATE TABLE DB_SCHEMA_NAME.nucleic_acid_pos_match (
    nucleic_acid_pos_match_id integer NOT NULL,
    nucleic_acid_match_id integer NOT NULL,
    ref_pos_id integer NOT NULL,
    comp_pos_id integer NOT NULL
);


CREATE TABLE DB_SCHEMA_NAME.nucleic_acid_seq (
    nucleic_acid_seq_id integer NOT NULL,
    parent_seq_id integer,
    helm_string text,
    active_strand character varying(2000) NOT NULL,
    passive_strand character varying(2000),
    mod_pattern_id integer,
    nucleic_acid_type_id smallint,
    helm_hash character varying(32),
    image bytea,
    primary key(nucleic_acid_seq_id)

);


CREATE TABLE DB_SCHEMA_NAME.nucleic_acid_seq_pos (
    nucleic_acid_seq_pos_id integer NOT NULL,
    nucleic_acid_seq_id integer NOT NULL,
    "position" smallint NOT NULL,
    isactivestrand character varying(1) NOT NULL,
    nucleic_acid_struct_id smallint NOT NULL
);


CREATE TABLE DB_SCHEMA_NAME.nucleic_acid_seq_prop (
    nucleic_acid_seq_prop_id integer NOT NULL,
    nucleic_acid_seq_id integer NOT NULL,
    prop_name character varying(30) NOT NULL,
    prop_value text NOT NULL
);


CREATE TABLE DB_SCHEMA_NAME.nucleic_acid_struct (
    nucleic_acid_struct_id integer NOT NULL,
    nucleic_acid_name character varying(10) NOT NULL,
    orig_nucleic_acid character varying(1) NOT NULL,
    smiles text
);


CREATE TABLE DB_SCHEMA_NAME.nucleic_acid_target (
    nucleic_acid_target_id integer NOT NULL,
    transcript_pos_id bigint NOT NULL,
    is_target character varying(1) NOT NULL,
    is_primary_organism character varying(1) NOT NULL,
    nucleic_acid_seq_id integer NOT NULL,
    n_mismatch smallint
);


CREATE TABLE DB_SCHEMA_NAME.nucleic_acid_type (
    nucleic_acid_type_id smallint NOT NULL,
    seq_type_name character varying(30) NOT NULL,
    active_strand_shift smallint NOT NULL,
    passive_strand_shift smallint NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.side_effect_drug (
    side_effect_drug_id bigint NOT NULL,
    side_effect_report_id integer NOT NULL,
    drug_entry_id integer,
    drug_role character(3),
    trade_name character varying(2000) NOT NULL,
    dose character varying(30),
    dose_unit character varying(10),
    count_dose double precision,
    unit_per_dose double precision,
    tot_dose double precision,
    tot_dose_unit character varying(10),
    dosage_text character varying(2000),
    dosage_form character varying(200),
    admin_route character varying(200),
    treatment_duration character varying(20),
    treatment_duration_unit character(1),
    recurrence_admin character(1),
    recurrence_reaction character(1),
    drug_batch character varying(300)
);

COMMENT ON TABLE DB_SCHEMA_NAME.side_effect_drug IS 'Drug side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.side_effect_drug_id IS 'Primary key to side_effect_drug. Provides context about the drug ';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.side_effect_report_id IS 'Foriegn key to side_effect_Report. Report describing the side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.drug_entry_id IS 'Foreign key to drug_entry. Defines the drug potentially involved in the side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.drug_role IS 'Assumed role of the drug in this side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.trade_name IS 'Name of the drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.dose IS 'Drug dose';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.dose_unit IS 'Unit of the dose';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.count_dose IS 'Number of dose';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.unit_per_dose IS 'Number of unit per dose';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.tot_dose IS 'Total dosage';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.tot_dose_unit IS 'Unit for total dosage';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.dosage_text IS 'Textual description of dosage';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.dosage_form IS 'Form of the dose (liquid/solid)';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.admin_route IS 'Route of administration';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.treatment_duration IS 'Treatment duration';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.treatment_duration_unit IS 'Unit';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.recurrence_admin IS 'What this a recurrent administration';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.recurrence_reaction IS 'What this a recurrent reaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug.drug_batch IS 'Batch number';

CREATE TABLE DB_SCHEMA_NAME.side_effect_drug_reaction (
    side_effect_drug_reaction_id bigint NOT NULL,
    meddra_entry_id integer NOT NULL,
    side_effect_report_id integer NOT NULL,
    outcome character(3)
);

COMMENT ON TABLE DB_SCHEMA_NAME.side_effect_drug_reaction IS 'Impact on the Human body of the drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug_reaction.side_effect_drug_reaction_id IS 'Primary key to side_effect_drug_reaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug_reaction.meddra_entry_id IS 'Foreign key to meddra_entry. Defines the type of side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug_reaction.side_effect_report_id IS 'Foreign key to side_effect_report. Defines the report';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_drug_reaction.outcome IS 'Outcome';

CREATE TABLE DB_SCHEMA_NAME.side_effect_report (
    side_effect_report_id integer NOT NULL,
    side_effect_seriousness_id smallint NOT NULL,
    source_id smallint NOT NULL,
    source_report_id character varying(30) NOT NULL,
    company_number character varying(200),
    transmission_date date NOT NULL,
    receive_date date NOT NULL,
    receipt_date date NOT NULL,
    patient_weight double precision,
    patient_age double precision,
    patient_age_unit character(1),
    patient_age_group character(1),
    patient_sex character(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.side_effect_report IS 'Report on a drug side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.side_effect_report_id IS 'Primary key to side_effect_report';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.side_effect_seriousness_id IS 'Foreign key to side_effect_seriousness. Defines the impact of the side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.source_id IS 'Foreign key to source. Source of the report';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.source_report_id IS 'Report identifier provided by the source';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.company_number IS 'Company number';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.transmission_date IS 'Date this report was transmitted';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.receive_date IS 'Date this report was received';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.receipt_date IS 'Date this report was receipt';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.patient_weight IS 'Patient weight';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.patient_age IS 'Patient age';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.patient_age_unit IS 'Age unit';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.patient_age_group IS 'Age group';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_report.patient_sex IS 'Gender';

CREATE MATERIALIZED VIEW DB_SCHEMA_NAME.mv_side_effect AS
 SELECT sed.drug_entry_id,
    sedr.meddra_entry_id,
    sed.side_effect_report_id
   FROM DB_SCHEMA_NAME.side_effect_drug sed,
    DB_SCHEMA_NAME.side_effect_drug_reaction sedr,
    DB_SCHEMA_NAME.side_effect_report ser
  WHERE ((sed.side_effect_report_id = sedr.side_effect_report_id) AND (sedr.side_effect_report_id = ser.side_effect_report_id))
  WITH NO DATA;

CREATE TABLE DB_SCHEMA_NAME.mv_targets (
    scientific_name character varying(200),
    tax_id character varying(10),
    rank character varying(30),
    chr_num character varying(200),
    map_location character varying(50),
    symbol character varying(100),
    full_name character varying(1500),
    gene_id bigint,
    gene_type character varying(20),
    prot_identifier character varying(20),
    taxon_id bigint,
    chr_id bigint,
    chr_map_id bigint,
    gn_entry_id bigint,
    prot_entry_id bigint,
    status character varying(1)
);

CREATE TABLE DB_SCHEMA_NAME.mv_targets_prev (
    scientific_name character varying(200),
    tax_id character varying(10),
    rank character varying(30),
    chr_num character varying(200),
    map_location character varying(50),
    symbol character varying(100),
    full_name character varying(1500),
    gene_id bigint,
    gene_type character varying(20),
    prot_identifier character varying(20),
    taxon_id bigint,
    chr_id bigint,
    chr_map_id bigint,
    gn_entry_id bigint,
    prot_entry_id bigint,
    status character varying(1)
);

CREATE TABLE DB_SCHEMA_NAME.mw_pathway (
    reac_id character varying(30) NOT NULL,
    pw_name character varying(1000) NOT NULL,
    scientific_name character varying(200) NOT NULL,
    tax_id character varying(10) NOT NULL,
    lineage character varying(4000),
    pw_entry_id bigint NOT NULL,
    taxon_id bigint NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.news (
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

COMMENT ON TABLE DB_SCHEMA_NAME.news IS 'News article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.news_id IS 'Primary key for a news record';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.news_title IS 'Title of the article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.news_content IS 'Content of the article in html format';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.news_release_date IS 'Date the article was released';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.news_added_date IS 'Date the article was added';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.user_id IS 'Foreign key to web_user. Author of the article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.source_id IS 'Foreign key to source. Equivalent to journal/feed';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.news_delta IS 'Raw format for the article to allow for edition';

COMMENT ON COLUMN DB_SCHEMA_NAME.news.news_hash IS 'Unique identifier (md5) hash';

CREATE TABLE DB_SCHEMA_NAME.news_clinical_trial_map (
    news_clinical_trial_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    clinical_trial_id integer NOT NULL,
    is_primary character(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.news_clinical_trial_map IS 'Mapping article to specific clinical trials';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_clinical_trial_map.news_clinical_trial_map_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_clinical_trial_map.news_id IS 'Foreign key to news. News article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_clinical_trial_map.clinical_trial_id IS 'Foreign key to clinical_trial_entry. Clinical trial reported in the article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_clinical_trial_map.is_primary IS 'T If this clinical trial is the main focus of this article. F if the clinical trial is mentioned as support';

CREATE SEQUENCE DB_SCHEMA_NAME.news_clinical_trial_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.news_company_map (
    news_company_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    company_entry_id integer NOT NULL,
    is_primary character(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.news_company_map IS 'Companies mentioned in news article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_company_map.news_company_map_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_company_map.news_id IS 'Foreign key to news. News article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_company_map.company_entry_id IS 'Foreign key to company_entry. Company reported in the article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_company_map.is_primary IS 'T If this company is the main focus of this article. F if the company is mentioned as support';

CREATE SEQUENCE DB_SCHEMA_NAME.news_company_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.news_disease_map (
    news_disease_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    disease_entry_id integer NOT NULL,
    is_primary character(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.news_disease_map IS 'Diseases mentioned in news article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_disease_map.news_disease_map_id IS 'Primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_disease_map.news_id IS 'Foreign key to news. News article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_disease_map.disease_entry_id IS 'Foreign key to disease_entry. Disease reported in the article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_disease_map.is_primary IS 'T If this disease is the main focus of this article. F if the disease is mentioned as support';

CREATE SEQUENCE DB_SCHEMA_NAME.news_disease_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.news_document (
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

COMMENT ON TABLE DB_SCHEMA_NAME.news_document IS 'Supplementary materials to a news article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.news_document_id IS 'News_Document primary key';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.document_name IS 'Name of the document';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.document_description IS 'Description of the document';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.document_hash IS 'md5 hash of the document';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.document_content IS 'Content (in binary format or not) of the document. Can also be a link';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.creation_date IS 'Creation date of this document';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.news_id IS 'Foreign key to news. News article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.document_version IS 'Version of this document';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_document.mime_type IS 'MIME type. can be LINK for weblink or SHAREPOINT for sharepoint';

CREATE SEQUENCE DB_SCHEMA_NAME.news_document_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.news_drug_map (
    news_drug_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    drug_entry_id integer NOT NULL,
    is_primary character(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.news_drug_map IS 'Drugs mentioned in news article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_drug_map.news_drug_map_id IS 'Primary key for drug/news article association';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_drug_map.news_id IS 'Foreign key to news. News article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_drug_map.drug_entry_id IS 'Foreign key to drug_entry. Drug reported in the article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_drug_map.is_primary IS 'T If this drug is the main focus of this article. F if the drug is mentioned as support';

CREATE SEQUENCE DB_SCHEMA_NAME.news_drug_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.news_gn_map (
    news_gn_map_id bigint NOT NULL,
    news_id bigint NOT NULL,
    gn_entry_id integer NOT NULL,
    is_primary character(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.news_gn_map IS 'Gene mentioned in news article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_gn_map.news_gn_map_id IS 'Primary key for gene/news article association';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_gn_map.news_id IS 'Foreign key to news. News article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_gn_map.gn_entry_id IS 'Foreign key to gn_entry. Gene reported in the article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_gn_map.is_primary IS 'T If this gene is the main focus of this article. F if the gene is mentioned as support';

CREATE SEQUENCE DB_SCHEMA_NAME.news_gn_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.news_news_map (
    news_id integer NOT NULL,
    news_parent_id integer NOT NULL,
    is_match character(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.news_news_map IS 'News article mentioned in news article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_news_map.news_id IS 'Foreign key to news. News article reported in the parent article';

COMMENT ON COLUMN DB_SCHEMA_NAME.news_news_map.news_parent_id IS 'Foreign key to news. Parent article';

CREATE SEQUENCE DB_SCHEMA_NAME.news_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.ontology_entry (
    ontology_entry_id integer NOT NULL,
    ontology_tag character varying(15),
    ontology_name character varying(200),
    ontology_definition character varying(4000),
    ontology_group character varying(2),
    w_pubmed char(1) default 'F'
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_entry.ontology_entry_id IS 'Primary key for a given ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_entry.ontology_tag IS 'Ontology tag';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_entry.ontology_name IS 'Name of the ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_entry.ontology_definition IS 'Definition';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_entry.ontology_group IS 'Group';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_entry.w_pubmed IS 'Flag to indicate if this ontology record will be searched in pubmed';

CREATE TABLE DB_SCHEMA_NAME.ontology_extdb (
    ontology_extdb_id bigint,
    ontology_entry_id bigint,
    source_id smallint,
    ontology_extdb character varying(200)
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_extdb.ontology_extdb_id IS 'PRimary key defining an external identifier of a database';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_extdb.ontology_entry_id IS 'Foreign key to ontology table';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_extdb.source_id IS 'Foreign key to source table. Source of the external identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_extdb.ontology_extdb IS 'External database value';

CREATE TABLE DB_SCHEMA_NAME.ontology_hierarchy (
    ontology_entry_id integer,
    ontology_level smallint,
    ontology_level_left bigint,
    ontology_level_right bigint
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_hierarchy.ontology_entry_id IS 'Foreign key to ontology record ';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_hierarchy.ontology_level IS 'Level in the ontology hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_hierarchy.ontology_level_left IS 'Left boundary for the nested set representation';

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_hierarchy.ontology_level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.ontology_pmid (
    ontology_pmid_id integer,
    ontology_entry_id integer,
    pmid_entry_id bigint
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ontology_pmid.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

CREATE TABLE DB_SCHEMA_NAME.ontology_syn (
    ontology_syn_id integer,
    ontology_entry_id integer,
    syn_type character varying(20),
    syn_value character varying(2000),
    source_id smallint
);

CREATE TABLE DB_SCHEMA_NAME.org_chart (
    org_chart_level smallint NOT NULL,
    org_chart_left integer NOT NULL,
    org_chart_right integer NOT NULL,
    web_user_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.org_chart IS 'For organization, organization hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_chart.org_chart_level IS 'Level in the organization chart';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_chart.org_chart_left IS 'Left boundary for the nested set representation';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_chart.org_chart_right IS 'Right boundary for the nested set representation';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_chart.web_user_id IS 'Foreign key to web_user. Represent a scientist in the organization';

CREATE TABLE DB_SCHEMA_NAME.org_group (
    org_group_id integer NOT NULL,
    org_group_name character varying(2000) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.org_group IS 'Groups within a organization';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_group.org_group_id IS 'Primary key for a group within the organization';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_group.org_group_name IS 'Group name';

CREATE TABLE DB_SCHEMA_NAME.org_group_map (
    org_group_map_id bigint NOT NULL,
    org_group_id integer NOT NULL,
    web_user_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.org_group_map IS 'Groups a user can be in';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_group_map.org_group_map_id IS 'Primary key for user/group association table';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_group_map.org_group_id IS 'Foreign key to org_group representing an organization group';

COMMENT ON COLUMN DB_SCHEMA_NAME.org_group_map.web_user_id IS 'Foreign key to web_user representing a scientist';

CREATE SEQUENCE DB_SCHEMA_NAME.org_group_map_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE SEQUENCE DB_SCHEMA_NAME.org_group_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.p_xrprot_site (
    p_xrprot_site_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    site_num smallint NOT NULL,
    subsite_id smallint DEFAULT 0 NOT NULL,
    comments character varying(2000)
);

CREATE TABLE DB_SCHEMA_NAME.p_xrprot_site_rel (
    p_xrprot_site_sel_id bigint NOT NULL,
    p_xrprot_site_id_ref bigint NOT NULL,
    p_xrprot_site_id_comp bigint NOT NULL,
    relation character varying(500) NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.p_xrprot_site_seq (
    p_xrprot_site_seq_id bigint NOT NULL,
    p_xrprot_site_id bigint NOT NULL,
    prot_seq_pos_id bigint NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.p_xrprot_site_xray_map (
    p_xrunsite_xray_map_id bigint NOT NULL,
    p_xrprot_site_id bigint NOT NULL,
    xr_site_id bigint NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.patent_entry (
    patent_entry_id integer NOT NULL,
    patent_application character varying(30) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.patent_entry IS 'List of patent';

COMMENT ON COLUMN DB_SCHEMA_NAME.patent_entry.patent_entry_id IS 'Primary key defining a patent';

COMMENT ON COLUMN DB_SCHEMA_NAME.patent_entry.patent_application IS 'Patent application number';

CREATE TABLE DB_SCHEMA_NAME.pmid_abstract (
    pmid_abstract_id integer NOT NULL,
    pmid_entry_id integer NOT NULL,
    abstract_type character varying(500),
    abstract_text text NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_abstract IS 'Abstract of publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_abstract.pmid_abstract_id IS 'Primary key of application abstract';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_abstract.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_abstract.abstract_type IS 'Section of an abstract';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_abstract.abstract_text IS 'Content of the asbstract section';

CREATE TABLE DB_SCHEMA_NAME.pmid_anatomy_map (
    pmid_entry_id integer NOT NULL,
    anatomy_entry_id integer NOT NULL,
    confidence smallint DEFAULT 1
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_anatomy_map IS 'Tissues/anatomy mentioned in publications';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_anatomy_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_anatomy_map.anatomy_entry_id IS 'Foreign key to anatomy_entry. Defines a tissue/anatomy part';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_anatomy_map.confidence IS 'Confidence score. 1: pubmed query. 2: from public resource using automation process. 3: Manual curation';

CREATE TABLE DB_SCHEMA_NAME.pmid_author (
    pmid_author_id bigint NOT NULL,
    last_name character varying(300) NOT NULL,
    first_name character varying(100),
    initials character varying(20),
    is_valid_author character varying(1) NOT NULL,
    pmid_instit_id bigint,
    orcid_id character varying(100),
    md5_hash character varying(35)
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_author IS 'Scientist and its affiliation';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author.pmid_author_id IS 'Primary key defining a pair author/institution';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author.last_name IS 'Last Name of the author';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author.first_name IS 'First name of the author';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author.initials IS 'Initials of the author';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author.is_valid_author IS 'Valid author (pubmed)';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author.pmid_instit_id IS 'Foreign key to pmid_instit - defines the institution';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author.orcid_id IS 'ORCID Number of the scientist';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author.md5_hash IS 'md5 Hash representing the author full name and institution';

CREATE TABLE DB_SCHEMA_NAME.pmid_author_map (
    pmid_author_map_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL,
    pmid_author_id bigint NOT NULL,
    "position" smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_author_map IS 'List of scientists that contributed to a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author_map.pmid_author_map_id IS 'Primary key associating publication to authors';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author_map.pmid_author_id IS 'Foreign key to pmid_author, defines a scientist who contributed to the publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_author_map."position" IS 'Position of the scientist in the list of authors';

CREATE TABLE DB_SCHEMA_NAME.pmid_citation (
    pmid_entry_id bigint NOT NULL,
    citation_pmid_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_citation IS 'Publicated cited by another publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_citation.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_citation.citation_pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

CREATE TABLE DB_SCHEMA_NAME.pmid_company_map (
    pmid_entry_id bigint NOT NULL,
    company_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_company_map IS 'Company cited by/involved in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_company_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_company_map.company_entry_id IS 'Foreign key to company_entry, defines a company';

CREATE TABLE DB_SCHEMA_NAME.pmid_disease_gene (
    pmid_disease_gene_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL,
    gn_entry_id bigint NOT NULL,
    disease_entry_id integer NOT NULL,
    ot_score real
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_disease_gene IS 'Gene disease association';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene.pmid_disease_gene_id IS 'Primary key of a gene/disease association found in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene.gn_entry_id IS 'Foreign key to gn_entry. Defines a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene.disease_entry_id IS 'Foreign key to disease_entry. Defines a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene.ot_score IS 'Open Target score';

CREATE TABLE DB_SCHEMA_NAME.pmid_disease_gene_txt (
    pmid_disease_gene_txt_id bigint NOT NULL,
    pmid_disease_gene_id bigint NOT NULL,
    section character varying(1) NOT NULL,
    text_content character varying(4000) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_disease_gene_txt IS 'Text supporting a gene disease association';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene_txt.pmid_disease_gene_txt_id IS 'Primary key of the text showing a gene/disease association found in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene_txt.pmid_disease_gene_id IS 'Foreign key to pmid_disease_gene.';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene_txt.section IS 'Section of the publication: Abstract, title, reference ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_gene_txt.text_content IS 'Text showing the gene/disease association';

CREATE TABLE DB_SCHEMA_NAME.pmid_disease_map (
    pmid_entry_id integer NOT NULL,
    disease_entry_id integer NOT NULL,
    confidence smallint DEFAULT 1
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_disease_map IS 'Disease mentioned in a publication ';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_map.disease_entry_id IS 'Foreign key to disease_entry. Defines a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_disease_map.confidence IS 'Confidence score 1: pubmed query. 2: from public resource using automation process. 3: Manual curation';

CREATE TABLE DB_SCHEMA_NAME.pmid_drug_map (
    pmid_entry_id bigint NOT NULL,
    drug_entry_id bigint NOT NULL,
    confidence smallint DEFAULT 1
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_drug_map IS 'Drug mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_map.drug_entry_id IS 'Foreign key to drug_entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_map.confidence IS 'Confidence score. 1: pubmed query. 2: from public resource using automation process. 3: Manual curation';




CREATE TABLE DB_SCHEMA_NAME.pmid_drug_stat (
    drug_entry_id bigint NOT NULL,
    all_count integer NOT NULL,
    year_count integer NOT NULL,
    speed30 double precision NOT NULL,
    accel30 double precision NOT NULL,
    speed60 double precision NOT NULL,
    accel60 integer NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_stat.drug_entry_id IS 'Foreign key to drug_entry, represent a drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_stat.all_count IS 'Total number of publications mentioning this drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_stat.year_count IS 'Number of publications within 12 months mentioning this drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_stat.speed30 IS 'Speed across the last 30 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_stat.accel30 IS 'Acceleration across the last 30 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_stat.speed60 IS 'Speed accoss the last 60 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_drug_stat.accel60 IS 'Acceleration across the last 60 months';

CREATE TABLE DB_SCHEMA_NAME.pmid_entry (
    pmid_entry_id bigint NOT NULL,
    pmid bigint NOT NULL,
    publication_date timestamp without time zone NOT NULL,
    title character varying(4000),
    doi character varying(200),
    volume character varying(200),
    issue character varying(200),
    pages character varying(50),
    pmid_status character varying(200),
    pmid_journal_id integer,
    month_1910 integer,
    full_text_date timestamp default null
);

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_entry_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_entry IS 'Publication record';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.pmid_entry_id IS 'Primary key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.pmid IS 'Pubmed Identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.publication_date IS 'Date the publication was published';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.title IS 'Title of the publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.doi IS 'Digital Object Identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.volume IS 'Volumne';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.issue IS 'Issue';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.pages IS 'Page range';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.pmid_status IS 'Publication status';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.pmid_journal_id IS 'Foreign key to pmid_journal. Defines the journal this publication was published in';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.month_1910 IS 'Number of months between January 1910 and the publication date' ;

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_entry.full_text_date IS 'Date the full text has been processed';

CREATE TABLE DB_SCHEMA_NAME.pmid_gene_map (
    pmid_entry_id bigint NOT NULL,
    gn_entry_id bigint NOT NULL,
    confidence smallint DEFAULT 1
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_gene_map IS 'Gene mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_map.gn_entry_id IS 'Foreign key to gn_entry. Defines a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_map.confidence IS 'Confidence score. 1: pubmed query. 2: from public resource using automation process. 3: Manual curation';

CREATE TABLE DB_SCHEMA_NAME.pmid_gene_stat (
    gn_entry_id bigint NOT NULL,
    all_count integer NOT NULL,
    year_count integer NOT NULL,
    speed30 double precision NOT NULL,
    accel30 double precision NOT NULL,
    speed60 double precision NOT NULL,
    accel60 double precision NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_stat.gn_entry_id IS 'Foreign key to gn_entry. Defines a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_stat.all_count IS 'Total number of publications mentioning this gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_stat.year_count IS 'Number of publications within 12 months mentioning this drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_stat.speed30 IS 'Speed across the last 30 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_stat.accel30 IS 'Acceleration across the last 30 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_stat.speed60 IS 'Speed accoss the last 60 months';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_gene_stat.accel60 IS 'Acceleration across the last 60 months';

CREATE TABLE DB_SCHEMA_NAME.pmid_instit (
    pmid_instit_id bigint NOT NULL,
    instit_name text NOT NULL,
    instit_hash character varying(50)
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_instit IS 'Publishing Institution/University/Company ';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_instit.pmid_instit_id IS 'Primary key for an institution';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_instit.instit_name IS 'Institution name';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_instit.instit_hash IS 'Hash value';

CREATE TABLE DB_SCHEMA_NAME.pmid_journal (
    pmid_journal_id integer NOT NULL,
    journal_name character varying(250) NOT NULL,
    journal_abbr character varying(150),
    issn_print character varying(10),
    issn_online character varying(10),
    iso_abbr character varying(150),
    nlmid character varying(20)
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_journal IS 'Journal/Publisher';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_journal.pmid_journal_id IS 'Primary key to a publication journal';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_journal.journal_name IS 'Name of journal';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_journal.journal_abbr IS 'Journal abbreviation';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_journal.issn_print IS 'ISSN print number';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_journal.issn_online IS 'ISSN online number';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_journal.iso_abbr IS 'ISO abbreviation';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_journal.nlmid IS 'NLM Identifier';


CREATE TABLE DB_SCHEMA_NAME.pmid_onto_map (
    pmid_entry_id bigint NOT NULL,
    ontology_entry_id bigint NOT NULL,
    confidence smallint DEFAULT 1
);

COMMENT ON TABLE DB_SCHEMA_NAME.pmid_onto_map IS 'Ontology terms mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_onto_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_onto_map.ontology_entry_id IS 'Foreign key to Ontology_entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_onto_map.confidence IS 'Confidence score. 1: pubmed query. 2: from public resource using automation process. 3: Manual curation';



CREATE TABLE DB_SCHEMA_NAME.pmid_ptm_disease_map (
    pmid_entry_id bigint NOT NULL,
    ptm_disease_id integer NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_ptm_disease_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmid_ptm_disease_map.ptm_disease_id IS 'Foreign key to PTM_disease';


 CREATE TABLE DB_SCHEMA_NAME.pmc_entry (
    pmc_entry_id bigint NOT NULL,
    pmc_id character varying(20) NOT NULL,
    license character varying(100) NOT NULL,
    date_added date not null,
    date_processed date NOT NULL,
    pmc_last_update date NOT NULL,
    status_code smallint,
    pmid_entry_id bigint,
    primary key (pmc_entry_id),
    unique (pmc_id)
 );

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_entry IS 'PMC literature record';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_entry.pmc_entry_id IS 'Primary key to pmc_Entry. Defines a literature document';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_entry.pmc_id IS 'PMC Identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_entry.license IS 'License of the document';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_entry.date_added IS 'Date the document was added to the database';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_entry.pmc_last_update IS 'Date the document was last updated by PMC';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_entry.status_code IS 'Status code of the processing. 0: not processed. 1: processed. 2: 1st attempt failed. 3: 2nd attempt failed. 4: 3rd attempt failed';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_entry.date_processed IS 'Date the full text has been processed';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_entry.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

CREATE TABLE DB_SCHEMA_NAME.pmc_section(
pmc_section_id smallint not null,
section_type character varying(60) not null,
section_subtype character varying(60) not null,
Primary key(pmc_section_id));

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_section IS 'Section Types of a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_section.pmc_section_id IS 'Primary key of a publication section';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_section.section_type IS 'Type of section';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_section.section_subtype IS 'Subtype of section';

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_section_sq START 1 INCREMENT 1;


CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext (
pmc_fulltext_id bigint not null,
pmc_entry_id bigint not null,
pmc_section_id smallint not null,
offset_pos int not null,
group_id smallint,
full_text text not null,
Primary key(pmc_fulltext_id));

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext IS 'Full text of a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext.pmc_fulltext_id IS 'Primary key of a publication line';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext.pmc_entry_id IS 'Foreign key to pmc_entry. Defines a PMC document';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext.pmc_section_id IS 'Foreign key to pmc_section. Defines the section of the publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext.offset_pos IS 'Offset position of the line in the publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext.group_id IS 'Group (paragraph) of the line';


COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext.full_text IS 'Content of the paragraph';



CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_sq START 1 INCREMENT 1;



CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_drug_map (
pmc_fulltext_drug_map_id bigint not null,
pmc_fulltext_id bigint not null,
drug_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_drug_map_id));

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_drug_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_drug_map IS 'Drugs mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_drug_map.pmc_fulltext_drug_map_id IS 'Primary key of a drug mentioned in a publication paragraph ';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_drug_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_drug_map.drug_entry_id IS 'Foreign key to drug_entry. Defines a drug';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_drug_map.loc_info IS 'Location of the drug in the paragraph. Sentence position|Word position|Number of words';




CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_disease_map (
pmc_fulltext_disease_map_id bigint not null,
pmc_fulltext_id bigint not null,
disease_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_disease_map_id));
CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_disease_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_disease_map IS 'Diseases mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_disease_map.pmc_fulltext_disease_map_id IS 'Primary key of a disease mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_disease_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_disease_map.disease_entry_id IS 'Foreign key to disease_entry. Defines a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_disease_map.loc_info IS 'Location of the disease in the publication. Sentence position|Word position|Number of words';





CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_anatomy_map (
pmc_fulltext_anatomy_map_id bigint not null,
pmc_fulltext_id bigint not null,
anatomy_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_anatomy_map_id));

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_anatomy_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_anatomy_map IS 'Tissues/anatomy mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_anatomy_map.pmc_fulltext_anatomy_map_id IS 'Primary key of a tissue/anatomy mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_anatomy_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_anatomy_map.anatomy_entry_id IS 'Foreign key to anatomy_entry. Defines a tissue/anatomy part';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_anatomy_map.loc_info IS 'Location of the tissue/anatomy in the publication. Sentence position|Word position|Number of words';






CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_ontology_map (
pmc_fulltext_ontology_map_id bigint not null,
pmc_fulltext_id bigint not null,
ontology_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_ontology_map_id));

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_ontology_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_ontology_map IS 'Ontology mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_ontology_map.pmc_fulltext_ontology_map_id IS 'Primary key of a ontology mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_ontology_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_ontology_map.ontology_entry_id IS 'Foreign key to ontology_entry. Defines an ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_ontology_map.loc_info IS 'Location of the ontology in the publication. Sentence position|Word position|Number of words';


CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_gn_map (
pmc_fulltext_gn_map_id bigint not null,
pmc_fulltext_id bigint not null,
gn_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_gn_map_id));

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_gn_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_gn_map IS 'Gene mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_gn_map.pmc_fulltext_gn_map_id IS 'Primary key of a gene mentioned in a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_gn_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_gn_map.gn_entry_id IS 'Foreign key to gn_entry. Defines a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_gn_map.loc_info IS 'Location of the gene in the publication. Sentence position|Word position|Number of words';





CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_go_map (
pmc_fulltext_go_map_id bigint not null,
pmc_fulltext_id bigint not null,
go_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_go_map_id));


CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_go_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_go_map IS 'Gene Ontology mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_go_map.pmc_fulltext_go_map_id IS 'Primary key of a gene ontology mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_go_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_go_map.go_entry_id IS 'Foreign key to go_entry. Defines a gene ontology';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_go_map.loc_info IS 'Location of the gene ontology in the publication. Sentence position|Word position|Number of words';




CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_company_map (
pmc_fulltext_company_map_id bigint not null,
pmc_fulltext_id bigint not null,
company_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_company_map_id));


CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_company_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_company_map IS 'Company mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_company_map.pmc_fulltext_company_map_id IS 'Primary key of a company mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_company_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_company_map.company_entry_id IS 'Foreign key to company_entry. Defines a company';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_company_map.loc_info IS 'Location of the company in the publication. Sentence position|Word position|Number of words';



CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_cell_map (
pmc_fulltext_cell_map_id bigint not null,
pmc_fulltext_id bigint not null,
cell_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_cell_map_id));


CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_cell_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_cell_map IS 'Cell line mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_cell_map.pmc_fulltext_cell_map_id IS 'Primary key of a cell line mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_cell_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_cell_map.cell_entry_id IS 'Foreign key to cell_entry. Defines a cell line';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_cell_map.loc_info IS 'Location of the cell line in the publication. Sentence position|Word position|Number of words';


CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_clinical_map (
pmc_fulltext_clinical_map_id bigint not null,
pmc_fulltext_id bigint not null,
clinical_trial_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_clinical_map_id));

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_clinical_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_clinical_map IS 'Clinical trial mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_clinical_map.pmc_fulltext_clinical_map_id IS 'Primary key of a clinical trial mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_clinical_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_clinical_map.clinical_trial_id IS 'Foreign key to clinical_trial. Defines a clinical trial';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_clinical_map.loc_info IS 'Location of the clinical trial in the publication. Sentence position|Word position|Number of words';


CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_sm_map (
pmc_fulltext_sm_map_id bigint not null,
pmc_fulltext_id bigint not null,
sm_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_sm_map_id));

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_sm_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_sm_map IS 'Small molecule mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_sm_map.pmc_fulltext_sm_map_id IS 'Primary key of a small molecule mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_sm_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_sm_map.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_sm_map.loc_info IS 'Location of the small molecule in the publication. Sentence position|Word position|Number of words';

CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_pub_map (
pmc_fulltext_pub_map_id bigint not null,
pmc_fulltext_id bigint not null,
pmid_entry_id bigint not null,
loc_info character varying(20) not null,
Primary key(pmc_fulltext_pub_map_id));

CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_pub_map_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_pub_map IS 'Publication mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_pub_map.pmc_fulltext_pub_map_id IS 'Primary key of a publication mentioned in a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_pub_map.pmc_fulltext_id IS 'Foreign key to pmc_fulltext. Defines a publication paragraph';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_pub_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_pub_map.loc_info IS 'Location of the publication in the publication. Sentence position|Word position|Number of words';





CREATE TABLE DB_SCHEMA_NAME.pmc_fulltext_file (
pmc_fulltext_file_id bigint not null,
pmc_entry_id bigint not null,
file_name character varying(300) not null,
file_id character varying(100) not null,
mime_type character varying(200) not null,
file_content bytea not null, 
primary key (pmc_fulltext_file_id));


CREATE SEQUENCE DB_SCHEMA_NAME.pmc_fulltext_file_sq START 1 INCREMENT 1;

COMMENT ON TABLE DB_SCHEMA_NAME.pmc_fulltext_file IS 'Images or supporting information of a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_file.pmc_fulltext_file_id IS 'Primary key of a file';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_file.pmc_entry_id IS 'Foreign key to pmc_entry. Defines a literature document';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_file.file_name IS 'Name of the file';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_file.file_id IS 'File identifier in the publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_file.mime_type IS 'MIME type of the file';

COMMENT ON COLUMN DB_SCHEMA_NAME.pmc_fulltext_file.file_content IS 'Content of the file';


CREATE TABLE DB_SCHEMA_NAME.prot_ac (
    prot_ac_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    ac character varying(10) NOT NULL,
    is_primary character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_ac IS 'Protein accession identifier - Uniprot';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_ac.prot_ac_id IS 'Primary key to Protein Accession (Uniprot AC)';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_ac.prot_entry_id IS 'Foreign key to prot_entry. Defines a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_ac.ac IS 'Uniprot Accession';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_ac.is_primary IS 'T is AC is the primary accession number for this record';

CREATE TABLE DB_SCHEMA_NAME.prot_desc (
    prot_desc_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    desc_type character varying(60) NOT NULL,
    description text NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_desc IS 'Textual description for a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_desc.prot_desc_id IS 'Primary key for protein description';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_desc.prot_entry_id IS 'Foreign key to prot_entry. Defines a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_desc.desc_type IS 'Type of description';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_desc.description IS 'Textual information';

CREATE TABLE DB_SCHEMA_NAME.prot_desc_pmid (
    prot_desc_pmid_id bigint NOT NULL,
    prot_desc_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL,
    eco_entry_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_desc_pmid IS 'Publication supporting protein description';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_desc_pmid.prot_desc_pmid_id IS 'Primary key to publication supporting protein description';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_desc_pmid.prot_desc_id IS 'Foreign key to prot_desc, defining a protein description';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_desc_pmid.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_desc_pmid.eco_entry_id IS 'Foreign key to ECO_entry providing evidence ontology';

CREATE TABLE DB_SCHEMA_NAME.prot_dom (
    prot_dom_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    domain_name character varying(600),
    modification_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    domain_type character varying(30) NOT NULL,
    pos_start integer NOT NULL,
    pos_end integer NOT NULL,
    status smallint DEFAULT 1 NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_dom IS 'Protein domain in a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom.prot_dom_id IS 'Primary key - Protein domain';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom.prot_entry_id IS 'Foreign key to prot_entry, defines the protein record this domain belongs to';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom.domain_name IS 'Domain name';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom.modification_date IS 'Last modification date';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom.domain_type IS 'Domain type';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom.pos_start IS 'Starting position of the domain in the canonical sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom.pos_end IS 'Ending position of the domain in the canonical sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom.status IS 'Status';

CREATE TABLE DB_SCHEMA_NAME.prot_dom_al (
    prot_dom_al_id bigint NOT NULL,
    prot_dom_ref_id bigint NOT NULL,
    prot_dom_comp_id bigint NOT NULL,
    perc_sim real NOT NULL,
    perc_identity real NOT NULL,
    length integer NOT NULL,
    e_value character varying(8),
    bit_score integer,
    perc_sim_com real NOT NULL,
    perc_identity_com real NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_dom_al IS 'Protein domain sequence alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.prot_dom_al_id IS 'Primary key to protein domain alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.prot_dom_ref_id IS 'Foreign key to prot_domain. Reference domain';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.prot_dom_comp_id IS 'Foreign key to prot_domain. Aligned domain';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.perc_sim IS 'Overall percentage similarity';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.perc_identity IS 'Overall percentage identity';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.length IS 'Length of the alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.e_value IS 'E-value of the alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.bit_score IS 'Bit score';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.perc_sim_com IS 'Percentage similarity of the aligned section';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al.perc_identity_com IS 'Percentage identity of the aligned section';

CREATE TABLE DB_SCHEMA_NAME.prot_dom_al_seq (
    prot_dom_al_seq_id bigint NOT NULL,
    prot_dom_al_id bigint NOT NULL,
    prot_dom_seq_id_ref bigint NOT NULL,
    prot_dom_seq_id_comp bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_dom_al_seq IS 'Individual amino acids aligned in a in protein domain sequence alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al_seq.prot_dom_al_seq_id IS 'Primary key to amino-acid matching for domain alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al_seq.prot_dom_al_id IS 'Foreign key to prot_dom_al, defining an alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al_seq.prot_dom_seq_id_ref IS 'Foreign key to prot_dom_seq, defining an amino-acid in the reference protein domain sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_al_seq.prot_dom_seq_id_comp IS 'Foreign key to prot_dom_seq, defining an amino-acid in the aligned protein domain sequence';

CREATE TABLE DB_SCHEMA_NAME.prot_dom_seq (
    prot_dom_seq_id bigint NOT NULL,
    prot_dom_id bigint NOT NULL,
    prot_seq_pos_id bigint NOT NULL,
    "position" integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_dom_seq IS 'Protein domain sub-sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_seq.prot_dom_seq_id IS 'Primary key for protein domain sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_seq.prot_dom_id IS 'Foreign key to prot_dom, defining a protein domain';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_seq.prot_seq_pos_id IS 'Foreign key to prot_seq_pos, defining an amino-acid in the canonical protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_dom_seq."position" IS 'Position of the amino-acid in the protein domain sequence';

CREATE TABLE DB_SCHEMA_NAME.prot_entry (
    prot_entry_id bigint NOT NULL,
    prot_identifier character varying(20) NOT NULL,
    date_created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    date_updated timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(1) NOT NULL,
    taxon_id bigint,
    confidence smallint
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_entry IS 'Protein entry record';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_entry.prot_entry_id IS 'Primary key to prot_entry, defining a protein entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_entry.prot_identifier IS 'Protein identifier (Uniprot Identifier)';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_entry.date_created IS 'Date this record was created';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_entry.date_updated IS 'Date this record was updated';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_entry.status IS 'Status';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_entry.taxon_id IS 'Foreign key to taxon, defining the organism this protein entry is related to';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_entry.confidence IS 'Confidence score.';

CREATE TABLE DB_SCHEMA_NAME.prot_extdb (
    prot_extdbid integer NOT NULL,
    prot_extdbac character varying(20) NOT NULL,
    prot_extdbabbr character varying(100) NOT NULL,
    prot_extdbname character varying(600) NOT NULL,
    prot_extdbserver character varying(2000) NOT NULL,
    prot_extdburl character varying(2000) NOT NULL,
    category character varying(200) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_extdb IS 'External databases linked to a protein record';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb.prot_extdbid IS 'Primary key for prot_extdb';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb.prot_extdbac IS 'Database accession';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb.prot_extdbabbr IS 'Database abbreviation';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb.prot_extdbname IS 'Database name';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb.prot_extdbserver IS 'Server path';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb.prot_extdburl IS 'URL';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb.category IS 'Type of database';

CREATE TABLE DB_SCHEMA_NAME.prot_extdb_map (
    prot_extdb_map_id bigint NOT NULL,
    prot_extdb_id integer NOT NULL,
    prot_entry_id bigint NOT NULL,
    prot_extdb_value character varying(2000) NOT NULL,
    prot_seq_id bigint
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_extdb_map IS 'Mapping between a protein record and external databases, i.e. providing external identifiers';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb_map.prot_extdb_map_id IS 'Primary key to prot_extdb_map record, defining an external identifier to a protein record';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb_map.prot_extdb_id IS 'Foreign key to prot_extdb, defining a database';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb_map.prot_entry_id IS 'Foreign key to prot_entry. Defines a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb_map.prot_extdb_value IS 'External identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_extdb_map.prot_seq_id IS 'Foreign key to prot_seq - optional - defines a protein sequence';

CREATE TABLE DB_SCHEMA_NAME.prot_feat (
    prot_feat_id bigint NOT NULL,
    prot_feat_type_id smallint NOT NULL,
    feat_value character varying(4000),
    feat_link character varying(200),
    prot_seq_id bigint NOT NULL,
    start_pos character varying(20) NOT NULL,
    end_pos character varying(20) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_feat IS 'Protein features found across a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat.prot_feat_id IS 'Primary key for protein feature position';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat.prot_feat_type_id IS 'Foreign key to prot_feat_type, defines the type of feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat.feat_value IS 'Textual description of the feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat.feat_link IS 'Link of the feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat.prot_seq_id IS 'Foreign key to prot_seq, defines a protein isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat.start_pos IS 'Starting position of the feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat.end_pos IS 'Ending position of the feature';

CREATE TABLE DB_SCHEMA_NAME.prot_feat_pmid (
    prot_feat_pmid_id bigint NOT NULL,
    prot_feat_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL,
    eco_entry_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_feat_pmid IS 'Publication supporting the evidence of a protein feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_pmid.prot_feat_pmid_id IS 'PRimary key to prot_feat_pmid, defining publication supporting a protein feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_pmid.prot_feat_id IS 'Foreign key to prot_feat, defines a protein feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_pmid.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_pmid.eco_entry_id IS 'Foreign key to eco_entry, defines the type of evidence';

CREATE TABLE DB_SCHEMA_NAME.prot_feat_seq (
    prot_feat_seq_id bigint NOT NULL,
    prot_feat_id bigint NOT NULL,
    prot_seq_pos_id bigint NOT NULL,
    "position" integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_feat_seq IS 'Individual amino-acid involved in a protein feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_seq.prot_feat_seq_id IS 'Primary key to prot_feat_seq, defining a specific amino-acid involved in a protein feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_seq.prot_feat_id IS 'Foreign key to prot_feat, defining a protein feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_seq.prot_seq_pos_id IS 'Foreign key to prot_seq_pos, defining an amino-acid in a protein isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_seq."position" IS 'Position of the amino-acid relative to the starting position of the feature';

CREATE TABLE DB_SCHEMA_NAME.prot_feat_type (
    feat_name character varying(200) NOT NULL,
    description character varying(200) NOT NULL,
    section character varying(50) NOT NULL,
    tag character varying(20) NOT NULL,
    prot_feat_type_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_feat_type IS 'Definition of different protein features';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_type.feat_name IS 'Feature name';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_type.description IS 'Description of the protein feature';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_type.section IS 'Group name for the feature: Molecule processing/Regions/Sites/Amino acid modifications ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_type.tag IS 'Short tag';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_feat_type.prot_feat_type_id IS 'Primary key to prot_feat_type defining the type of protein features';

CREATE TABLE DB_SCHEMA_NAME.prot_go_map (
    prot_go_map_id bigint NOT NULL,
    go_entry_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    evidence character varying(3),
    source_id smallint,
    CONSTRAINT go_prot_map_chk1 CHECK (((evidence)::text = ANY (ARRAY[('EXP'::character varying)::text, ('IDA'::character varying)::text, ('IPI'::character varying)::text, ('IMP'::character varying)::text, ('IGI'::character varying)::text, ('IEP'::character varying)::text, ('HTP'::character varying)::text, ('HDA'::character varying)::text, ('HMP'::character varying)::text, ('HGI'::character varying)::text, ('HEP'::character varying)::text, ('ISS'::character varying)::text, ('ISO'::character varying)::text, ('ISA'::character varying)::text, ('ISM'::character varying)::text, ('IGC'::character varying)::text, ('IBA'::character varying)::text, ('IBD'::character varying)::text, ('IKR'::character varying)::text, ('IRD'::character varying)::text, ('RCA'::character varying)::text, ('TAS'::character varying)::text, ('NAS'::character varying)::text, ('IC'::character varying)::text, ('ND'::character varying)::text, ('IEA'::character varying)::text])))
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_go_map IS 'Connecting protein to its function';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_go_map.prot_go_map_id IS 'Primary key to prot_go_map table, mapping gene ontologies to protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_go_map.go_entry_id IS 'Foreign key to go_entry. Defines a gene ontology record';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_go_map.prot_entry_id IS 'Foreign key to prot_entry. Defines a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_go_map.evidence IS 'type of evidence supporting this relationship';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_go_map.source_id IS 'Foreign key to source. Defines the database providing this information';

CREATE TABLE DB_SCHEMA_NAME.prot_name (
    prot_name_id bigint NOT NULL,
    protein_name character varying(2000) NOT NULL,
    ec_number character varying(60),
    date_created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_name IS 'Name of a protein or a protein region';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name.prot_name_id IS 'Primary key to prot_name, defines a protein name';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name.protein_name IS 'Protein name';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name.ec_number IS 'If enzyme, provides the enzyme commission number';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name.date_created IS 'Date this protein name record was created';

CREATE TABLE DB_SCHEMA_NAME.prot_name_map (
    prot_name_map_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    prot_name_id bigint NOT NULL,
    group_id smallint NOT NULL,
    class_name character varying(10) NOT NULL,
    name_type character varying(3) NOT NULL,
    name_subtype character varying(1) NOT NULL,
    name_link character varying(1000),
    is_primary character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_name_map IS 'Mapping table between a protein and its name';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.prot_name_map_id IS 'Primary key to prot_name_map, which associate a protein to different protein names';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.prot_entry_id IS 'Foreign key to prot_entry. Defines a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.prot_name_id IS 'Foreign key to prot_name. Defines a protein name';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.group_id IS 'Group';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.class_name IS 'Class - defines whether it is a recommended name (REC) or alternative name (ALT)';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.name_type IS 'Type  - defines whether it is a recommended name (REC) or alternative name (ALT)';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.name_subtype IS 'Subtype - tell whether this is a  Short name (S) or Full name (F)';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.name_link IS 'Link - usually evidence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_name_map.is_primary IS 'Is primary name';

CREATE TABLE DB_SCHEMA_NAME.prot_pmid_map (
    prot_pmid_map_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL,
    comments character varying(2000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_pmid_map IS 'Publication that were used to describe the protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_pmid_map.prot_pmid_map_id IS 'Primary key for prot_pmid_map table';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_pmid_map.prot_entry_id IS 'Foreign key to prot_entry. Defines a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_pmid_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_pmid_map.comments IS 'Additional comments';

CREATE TABLE DB_SCHEMA_NAME.prot_seq (
    prot_seq_id bigint NOT NULL,
    prot_entry_id bigint NOT NULL,
    iso_name character varying(400) NOT NULL,
    iso_id character varying(20) NOT NULL,
    is_primary character varying(1) NOT NULL,
    description character varying(400),
    modification_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    note character varying(4000),
    status smallint DEFAULT 1 NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_seq IS 'Protein sequence - isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.prot_seq_id IS 'Primary key for prot_seq table';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.prot_entry_id IS 'Foreign key to prot_entry. Defines a protein entry record';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.iso_name IS 'Isoform name';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.iso_id IS 'Isoform identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.is_primary IS 'Is this the canonical sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.description IS 'Full Name of the sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.modification_date IS 'Date last modified';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.note IS 'Additional notes';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq.status IS '1=Valid, 9=To delete';

CREATE TABLE DB_SCHEMA_NAME.prot_seq_al (
    prot_seq_al_id bigint NOT NULL,
    prot_seq_ref_id bigint NOT NULL,
    prot_seq_comp_id bigint NOT NULL,
    perc_sim real NOT NULL,
    perc_identity real NOT NULL,
    length integer NOT NULL,
    e_value character varying(8) NOT NULL,
    bit_score integer NOT NULL,
    perc_sim_com real NOT NULL,
    perc_identity_com real NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_seq_al IS 'Protein sequence alignment summary';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.prot_seq_al_id IS 'Primary key to prot_seq_al, defines a protein sequence alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.prot_seq_ref_id IS 'Foreign key to prot_seq_ref, defines the reference sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.prot_seq_comp_id IS 'Foreign key to prot_seq_comp, defines the aligned sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.perc_sim IS 'Percentage of similarity between the two sequences';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.perc_identity IS 'Percentage of identity between the two sequences';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.length IS 'number of aligned amino-acids';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.e_value IS 'E-Value';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.bit_score IS 'Bit score';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.perc_sim_com IS 'Percentage of similarity between the two sequences for the aligned region';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al.perc_identity_com IS 'Percentage of identity between the two sequences for the aligned region';

CREATE TABLE DB_SCHEMA_NAME.prot_seq_al_seq (
    prot_seq_al_seq_id bigint NOT NULL,
    prot_seq_al_id bigint NOT NULL,
    prot_seq_id_ref bigint NOT NULL,
    prot_seq_id_comp bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_seq_al_seq IS 'Protein sequence alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al_seq.prot_seq_al_seq_id IS 'Primary key to prot_seq_al_seq, defines a pair of aligned amino-acid in an alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al_seq.prot_seq_al_id IS 'Foreign key to prot_seq_pos, defines the amino-acid in the reference sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_al_seq.prot_seq_id_ref IS 'Foreign key to prot_seq_pos, defines the amino-acid in the aligned sequence';

CREATE TABLE DB_SCHEMA_NAME.prot_seq_pos (
    prot_seq_pos_id bigint NOT NULL,
    prot_seq_id bigint NOT NULL,
    "position" integer NOT NULL,
    letter character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.prot_seq_pos IS 'Individual amino-acid making the protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_pos.prot_seq_pos_id IS 'Primay key to prot_seq_pos table, defines an amino-acid in a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_pos.prot_seq_id IS 'Foreign key to prot_seq. Defines a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_pos."position" IS 'Position of the amino-acid in the protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.prot_seq_pos.letter IS 'Amino-acid 1 Letter code';

CREATE TABLE DB_SCHEMA_NAME.ptm_abbreviations (
    ptm_abv_id integer NOT NULL,
    ptm_abv_name character varying(5),
    ptm_type_id integer NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_abbreviations.ptm_type_id IS 'Foreign key to PTM Type. Defines a type of Post Translational Modification.';

CREATE TABLE DB_SCHEMA_NAME.ptm_disease (
    ptm_disease_id integer NOT NULL,
    ptm_seq_id integer NOT NULL,
    ptm_disease_alteration character varying(150),
    ptm_disease_notes character varying(500),
    disease_entry_id integer
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_disease.ptm_disease_id IS 'Primary key to ptm_disease. Defines a PTM associated to a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_disease.ptm_seq_id IS 'Foreign key to PTM_Seq. defines a PTM at a given protein sequence position';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_disease.ptm_disease_alteration IS 'Type of alteration';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_disease.ptm_disease_notes IS 'Additional notes';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_disease.disease_entry_id IS 'Foreign key to disease_entry. Defines a disease';

CREATE TABLE DB_SCHEMA_NAME.ptm_seq (
    ptm_seq_id integer NOT NULL,
    prot_seq_pos_id integer NOT NULL,
    ptm_type_id integer NOT NULL,
    ptm_seq_sgi integer
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_seq.ptm_seq_id IS 'Primary key to PTM_Seq table. Defines a PTM at a given protein sequence position';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_seq.prot_seq_pos_id IS 'Foreign key to prot_seq_pos table, defines an amino-acid in a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_seq.ptm_type_id IS 'Foreign key to PTM Type. Defines a type of Post Translational Modification.';

CREATE TABLE DB_SCHEMA_NAME.ptm_syn (
    ptm_syn_id integer NOT NULL,
    ptm_syn_name character varying(50),
    ptm_type_id integer NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_syn.ptm_syn_id IS 'Primary key for ptm_syn, defining a ptm synonym';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_syn.ptm_syn_name IS 'Synonym ';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_syn.ptm_type_id IS 'Foreign key to PTM Type. Defines a type of Post Translational Modification.';

CREATE TABLE DB_SCHEMA_NAME.ptm_type (
    ptm_type_id integer NOT NULL,
    ptm_type_name character varying(50) NOT NULL,
    ptm_type_description character varying(500) NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_type.ptm_type_id IS 'Primary key to PTM Type. Defines a type of Post Translational Modification.';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_type.ptm_type_name IS 'Full name of a given PTM Type.';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_type.ptm_type_description IS 'Description of the role/structure of a Post Translational Modification.';

CREATE SEQUENCE DB_SCHEMA_NAME.ptm_type_ptm_type_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.ptm_var (
    ptm_var_id integer NOT NULL,
    ptm_seq_id integer NOT NULL,
    ptm_var_prot_seq_pos_id integer NOT NULL,
    ptm_var_aa character varying(1),
    ptm_var_class character varying(4)
);

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_var.ptm_var_id IS 'Primary key for PTM_Var, defining variants around PTM';

COMMENT ON COLUMN DB_SCHEMA_NAME.ptm_var.ptm_seq_id IS 'Foreign key to PTM_Seq. defines a PTM at a given protein sequence position';

CREATE TABLE DB_SCHEMA_NAME.pw_entry (
    pw_entry_id bigint NOT NULL,
    reac_id character varying(30) NOT NULL,
    pw_name character varying(1000) NOT NULL,
    taxon_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.pw_entry IS 'Pathway record';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_entry.pw_entry_id IS 'Primary key to pathway';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_entry.reac_id IS 'Reactome Identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_entry.pw_name IS 'Pathway name';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_entry.taxon_id IS 'Foreign key to taxon. Defines the organism this pathway is involved in';

CREATE TABLE DB_SCHEMA_NAME.pw_gn_map (
    pw_gn_map_id bigint NOT NULL,
    gn_entry_id bigint NOT NULL,
    pw_entry_id bigint NOT NULL,
    evidence_code character varying(15) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.pw_gn_map IS 'Gene involved in a pathway';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_gn_map.pw_gn_map_id IS 'Primary key to pw_gn_map, mapping a gene to its pathway(s)';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_gn_map.gn_entry_id IS 'Foreign key to gn_entry. Defines a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_gn_map.pw_entry_id IS 'Foreign key to pw_entry. defines a pathway';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_gn_map.evidence_code IS 'Evidence of the association';

CREATE TABLE DB_SCHEMA_NAME.pw_hierarchy (
    pw_entry_id integer NOT NULL,
    pw_level smallint NOT NULL,
    level_left integer NOT NULL,
    level_right integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.pw_hierarchy IS 'Hierarchy of the pathways';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_hierarchy.pw_entry_id IS 'Foreign key to pw_entry,defining a pathway';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_hierarchy.pw_level IS 'Pathway Level in the hierarchy - based on nested set representation';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_hierarchy.level_left IS 'Left boundary in the hierarchy - based on nested set representation';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_hierarchy.level_right IS 'Right boundary in the hierarchy - based on nested set representation';

CREATE TABLE DB_SCHEMA_NAME.pw_rel (
    pw_rel_id bigint NOT NULL,
    pw_from_id bigint NOT NULL,
    pw_to_id bigint NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_rel.pw_rel_id IS 'Pathway relationship';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_rel.pw_from_id IS 'Pathway from';

COMMENT ON COLUMN DB_SCHEMA_NAME.pw_rel.pw_to_id IS 'Pahtway to';

CREATE TABLE DB_SCHEMA_NAME.rna_gene (
    rna_gene_id bigint NOT NULL,
    gene_seq_id integer NOT NULL,
    rna_sample_id integer NOT NULL,
    tpm double precision NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.rna_gene IS 'RNA Expression level of a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene.rna_gene_id IS 'Primary key to RNA_Gene. Defines RNA Expression level for a gene in a given tissue';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene.gene_seq_id IS 'Foreign key to Gene_Seq. Defines a gene sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene.rna_sample_id IS 'Foreign key to RNA_Sample. defines a sample';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene.tpm IS 'Transcript Per Million value';

CREATE TABLE DB_SCHEMA_NAME.rna_gene_stat (
    rna_gene_stat_id bigint NOT NULL,
    gene_seq_id integer NOT NULL,
    rna_tissue_id bigint NOT NULL,
    n_sample smallint NOT NULL,
    auc real,
    lower_value double precision,
    lr double precision,
    min_value double precision NOT NULL,
    q1 double precision NOT NULL,
    med_value double precision NOT NULL,
    avg_value double precision,
    q3 double precision NOT NULL,
    max_value double precision NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.rna_gene_stat IS 'Summarization of RNA Expression level for a gene';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.rna_gene_stat_id IS 'Primary key for RNA Gene stat table. Provide statistics for a given gene across all samples of a given tissue';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.gene_seq_id IS 'Foreign key to Gene_Seq. Defines a gene sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.rna_tissue_id IS 'Foreign key to RNA Tissue. Defines a tissue';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.n_sample IS 'Number of samples';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.auc IS 'Area under curve';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.lower_value IS 'Lowest PTM value';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.min_value IS 'Minimal value';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.q1 IS 'First quartile';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.med_value IS 'Median';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.avg_value IS 'Average';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.q3 IS 'Third quartile';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_gene_stat.max_value IS 'Maximal value';

CREATE TABLE DB_SCHEMA_NAME.rna_sample (
    rna_sample_id bigint NOT NULL,
    sample_id character varying(100) NOT NULL,
    rna_tissue_id bigint NOT NULL,
    rna_source_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.rna_sample IS 'Individual sample used for RNA Expression';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_sample.rna_sample_id IS 'Primary key to RNA Sample';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_sample.sample_id IS 'Identifier of the sample';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_sample.rna_tissue_id IS 'Foreign key to RNA_Tissue. Defines a tissue';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_sample.rna_source_id IS 'Foreign key to rna_source. Defines the source of the RNA Sample';

CREATE TABLE DB_SCHEMA_NAME.rna_source (
    rna_source_id integer NOT NULL,
    source_name character varying(200)
);

COMMENT ON TABLE DB_SCHEMA_NAME.rna_source IS 'Source of RNA Expression data';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_source.rna_source_id IS 'Primary key for RNA_Source';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_source.source_name IS 'Source name';

CREATE TABLE DB_SCHEMA_NAME.rna_tissue (
    rna_tissue_id smallint NOT NULL,
    anatomy_entry_id smallint,
    efo_entry_id smallint,
    organ_name character varying(50),
    tissue_name character varying(50)
);

COMMENT ON TABLE DB_SCHEMA_NAME.rna_tissue IS 'Tissue definition for RNA Expression. Map to anatomy table';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_tissue.rna_tissue_id IS 'Primary key to RNA Tissue';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_tissue.anatomy_entry_id IS 'Foreign key to anatomy_entry. defines a tissue';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_tissue.organ_name IS 'Name of the organ';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_tissue.tissue_name IS 'Name of the tissue';

CREATE TABLE DB_SCHEMA_NAME.rna_transcript (
    rna_sample_id bigint NOT NULL,
    transcript_id bigint NOT NULL,
    tpm double precision NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.rna_transcript IS 'RNA Expression level of a transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript.rna_sample_id IS 'Foreign key to RNA Sample. defines a sample';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript.transcript_id IS 'Foreign key to transcript. Defines a transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript.tpm IS 'Transcript Per Million value of the transcript in this RNA Sample';

CREATE TABLE DB_SCHEMA_NAME.rna_transcript_stat (
    rna_transcript_stat_id bigint NOT NULL,
    transcript_id bigint NOT NULL,
    rna_tissue_id bigint NOT NULL,
    auc real NOT NULL,
    lower_value double precision NOT NULL,
    lr double precision,
    min_value double precision NOT NULL,
    q1 double precision NOT NULL,
    med_value double precision NOT NULL,
    q3 double precision NOT NULL,
    max_value double precision NOT NULL,
    nsample smallint NOT NULL,
    avg_value double precision
);

COMMENT ON TABLE DB_SCHEMA_NAME.rna_transcript_stat IS 'Summarization of RNA Expression level for a transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.rna_transcript_stat_id IS 'Primary key for RNA transcript stat table. Provide statistics for a given transcript across all samples of a given tissue';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.transcript_id IS 'Foreign key to transcript. Defines a transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.rna_tissue_id IS 'Foreign key to RNA_Tissue. Defines a tissue';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.auc IS 'Area under curve';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.min_value IS 'mimimal TPM value';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.q1 IS 'First quartile';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.med_value IS 'Median value';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.q3 IS 'Third quartile';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.max_value IS 'Maximal value';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.nsample IS 'Number of samples';

COMMENT ON COLUMN DB_SCHEMA_NAME.rna_transcript_stat.avg_value IS 'Average value';

CREATE TABLE DB_SCHEMA_NAME.seq_btype (
    seq_btype_id smallint NOT NULL,
    seq_type character varying(50) NOT NULL,
    so_entry_id bigint
);

COMMENT ON COLUMN DB_SCHEMA_NAME.seq_btype.seq_btype_id IS 'Primary key for sequence type';

COMMENT ON COLUMN DB_SCHEMA_NAME.seq_btype.seq_type IS 'Sequence type';

COMMENT ON COLUMN DB_SCHEMA_NAME.seq_btype.so_entry_id IS 'Foreign key to so_entry. ';

CREATE TABLE DB_SCHEMA_NAME.sharepoint_config (
    sharepoint_config_id integer NOT NULL,
    sharepoint_name character varying(2000) NOT NULL,
    sharepoint_client_id character varying(2000) NOT NULL,
    sharepoint_client_secret character varying(2000) NOT NULL,
    owner_id integer NOT NULL
);

CREATE SEQUENCE DB_SCHEMA_NAME.sharepoint_config_sharepoint_config_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE DB_SCHEMA_NAME.sharepoint_config_sharepoint_config_id_seq OWNED BY DB_SCHEMA_NAME.sharepoint_config.sharepoint_config_id;

CREATE TABLE DB_SCHEMA_NAME.sharepoint_doc_author_map (
    sharepoint_doc_clinical_trial_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    web_user_id integer NOT NULL,
    page smallint
);

CREATE TABLE DB_SCHEMA_NAME.sharepoint_doc_clinical_trial_map (
    sharepoint_doc_clinical_trial_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    clinical_trial_id integer NOT NULL,
    page smallint
);

CREATE TABLE DB_SCHEMA_NAME.sharepoint_doc_company_map (
    sharepoint_doc_company_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    company_entry_id integer NOT NULL,
    page smallint
);

CREATE TABLE DB_SCHEMA_NAME.sharepoint_doc_disease_map (
    sharepoint_doc_company_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    disease_entry_id integer NOT NULL,
    page smallint
);

CREATE TABLE DB_SCHEMA_NAME.sharepoint_doc_drug_map (
    sharepoint_doc_drug_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    drug_entry_id integer NOT NULL,
    page smallint
);

CREATE TABLE DB_SCHEMA_NAME.sharepoint_doc_gn_map (
    sharepoint_doc_gn_map_id bigint NOT NULL,
    sharepoint_document_id bigint NOT NULL,
    gn_entry_id integer NOT NULL,
    page smallint
);

CREATE TABLE DB_SCHEMA_NAME.sharepoint_document (
    sharepoint_document_id bigint NOT NULL,
    sharepoint_config_id integer NOT NULL,
    document_name character varying(2000) NOT NULL,
    document_hash character varying(32) NOT NULL,
    creation_date date NOT NULL,
    updated_date date,
    mime_type character varying(200)
);

CREATE TABLE DB_SCHEMA_NAME.side_effect_seriousness (
    side_effect_seriousness_id smallint NOT NULL,
    overall_seriousness character(1) NOT NULL,
    death character(1) NOT NULL,
    life_threatening character(1) NOT NULL,
    hospitalization character(1) NOT NULL,
    disabling character(1) NOT NULL,
    congenital_anomaly character(1) NOT NULL,
    other character(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.side_effect_seriousness IS 'Classification of the seriousness of a drug side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_seriousness.side_effect_seriousness_id IS 'Primary key to side_effect_seriousness. Defines the impact of the side effect';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_seriousness.overall_seriousness IS 'Overall seriousness';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_seriousness.death IS 'Led to death';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_seriousness.life_threatening IS 'Led to life threatening situation';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_seriousness.hospitalization IS 'Led to hospitalization';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_seriousness.disabling IS 'Led to being disabled';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_seriousness.congenital_anomaly IS 'Led to congenital anomalu';

COMMENT ON COLUMN DB_SCHEMA_NAME.side_effect_seriousness.other IS 'Other complication';

CREATE TABLE DB_SCHEMA_NAME.sm_counterion (
    sm_counterion_id integer NOT NULL,
    counterion_smiles character varying(1000) NOT NULL,
    is_valid character(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.sm_counterion IS 'Small molecule counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_counterion.sm_counterion_id IS 'Primary key to sm_counterion.Defines a counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_counterion.counterion_smiles IS 'Counterion smiles';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_counterion.is_valid IS 'T if successfully standardized, F otherwise';



CREATE TABLE DB_SCHEMA_NAME.sm_description (
    sm_description_id integer NOT NULL,
    sm_entry_id integer NOT NULL,
    description_text text NOT NULL,
    description_type character varying(40) NOT NULL,
    source_id smallint NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_description.sm_description_id IS 'Primary key to sm_description';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_description.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_description.description_text IS 'Textual description';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_description.description_type IS 'Type of description';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_description.source_id IS 'Foreign key to source. Defines the source of the description';

CREATE TABLE DB_SCHEMA_NAME.sm_entry (
    sm_entry_id bigint NOT NULL,
    inchi character varying(4000),
    inchi_key character varying(1000),
    full_smiles character varying(4000) not null,
    sm_molecule_id bigint NOT NULL,
    sm_counterion_id integer,
    is_valid character varying(1) DEFAULT 'F'::character varying,
    md5_hash character varying(35) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.sm_entry IS 'Small molecule record - made of a molecule and eventually a counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_entry.sm_entry_id IS 'Primary key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_entry.inchi IS 'INCHI representation of the molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_entry.inchi_key IS 'INCHI-Key representation of the molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_entry.full_smiles IS 'Full (standardized) SMILES representation of the molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_entry.sm_molecule_id IS 'Foreign key to sm_molecule. Defines a molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_entry.sm_counterion_id IS 'Foreign key to sm_counterion. Defines a counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_entry.is_valid IS 'T if properly standardized';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_entry.md5_hash IS 'Unique hash representation of the molecule';

CREATE TABLE DB_SCHEMA_NAME.sm_molecule (
    sm_molecule_id bigint NOT NULL,
    smiles character varying(4000) NOT NULL,
    is_valid character varying(1) NOT NULL,
    date_created DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sm_scaffold_id bigint
);

COMMENT ON TABLE DB_SCHEMA_NAME.sm_molecule IS 'Small molecule structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_molecule.sm_molecule_id IS 'Primary key to molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_molecule.smiles IS 'SMILES of the molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_molecule.is_valid IS 'T if properly standardized';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_molecule.date_created IS 'Date this molecule record has been created. Can be replaced by your internal creation date';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_molecule.sm_scaffold_id IS 'Foreign key to sm_Scaffold. Scaffold of the molecule (if applicable)';

CREATE TABLE DB_SCHEMA_NAME.sm_patent_map (
    sm_patent_map_id integer NOT NULL,
    sm_entry_id integer NOT NULL,
    patent_entry_id integer NOT NULL,
    field character varying(1) NOT NULL,
    field_freq smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.sm_patent_map IS 'Patent mentioning a molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_patent_map.sm_patent_map_id IS 'Primary key to sm_patent_map, mapping patent to molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_patent_map.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_patent_map.patent_entry_id IS 'Foreign key to patent_entry. Defines a patent';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_patent_map.field IS 'Provides the field in which the molecule was found in the patent';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_patent_map.field_freq IS 'Frequency';

CREATE TABLE DB_SCHEMA_NAME.sm_publi_map (
    sm_publi_map_id integer NOT NULL,
    sm_entry_id integer NOT NULL,
    pmid_entry_id integer NOT NULL,
    source_id smallint NOT NULL,
    sub_type character varying(20),
    disease_entry_id integer,
    confidence smallint DEFAULT 1
);

COMMENT ON TABLE DB_SCHEMA_NAME.sm_publi_map IS 'Publication mentioning a molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_publi_map.sm_publi_map_id IS 'Primary key to sm_publi_map. ';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_publi_map.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_publi_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_publi_map.source_id IS 'Foreign key to source. Defines the source of the matching';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_publi_map.sub_type IS 'Sub type';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_publi_map.disease_entry_id IS 'Foreign key to disease_entry. Defines a disease';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_publi_map.confidence IS 'Confidence';


CREATE TABLE DB_SCHEMA_NAME.sm_scaffold (
    sm_scaffold_id bigint NOT NULL,
    scaffold_smiles character varying(4000) NOT NULL,
    is_valid character varying(1) NOT NULL,
    date_created DATE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE DB_SCHEMA_NAME.sm_scaffold IS 'Small molecule scaffold';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_scaffold.sm_scaffold_id IS 'Primary key to sm_scaffold. Defines a scaffold';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_scaffold.scaffold_smiles IS 'SMILES of the scaffold';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_scaffold.is_valid IS 'T if properly standardized';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_scaffold.date_created IS 'Date this scaffold record has been created. Can be replaced by your internal creation date';


CREATE TABLE DB_SCHEMA_NAME.sm_source (
    sm_source_id bigint NOT NULL,
    sm_entry_id bigint NOT NULL,
    source_id smallint NOT NULL,
    sm_name character varying(4000) NOT NULL,
    sm_name_status character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.sm_source IS 'Alternative names for the molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_source.sm_source_id IS 'Primary key to sm_source. Defines the names of the molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_source.sm_entry_id IS 'Foreign key to sm_entry. Defines a small molecule with a potential counterion';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_source.source_id IS 'Foreign key to source';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_source.sm_name IS 'Name of the molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.sm_source.sm_name_status IS 'Status';

CREATE TABLE DB_SCHEMA_NAME.so_entry (
    so_entry_id smallint NOT NULL,
    so_id character varying(100) NOT NULL,
    so_name character varying(150) NOT NULL,
    so_description character varying(1400)
);

COMMENT ON TABLE DB_SCHEMA_NAME.so_entry IS 'Sequence ontology';

COMMENT ON COLUMN DB_SCHEMA_NAME.so_entry.so_entry_id IS 'Primary key to so_entry - Sequence ontology';

COMMENT ON COLUMN DB_SCHEMA_NAME.so_entry.so_id IS 'Sequence Ontology identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.so_entry.so_name IS 'Sequence ontology name';

COMMENT ON COLUMN DB_SCHEMA_NAME.so_entry.so_description IS 'Sequence ontology description';

CREATE TABLE DB_SCHEMA_NAME.source (
    source_id smallint NOT NULL,
    source_name character varying(100) NOT NULL,
    version character varying(20),
    user_name character varying(100),
    subgroup character varying(100),
    source_type character(1),
    source_metadata character varying(1000)
);

COMMENT ON TABLE DB_SCHEMA_NAME.source IS 'Name of different database that are used as source';

COMMENT ON COLUMN DB_SCHEMA_NAME.source.source_id IS 'Primary key to source. Defines public/internal databases';

COMMENT ON COLUMN DB_SCHEMA_NAME.source.source_name IS 'Name of the source';

COMMENT ON COLUMN DB_SCHEMA_NAME.source.version IS 'Version of the source';

COMMENT ON COLUMN DB_SCHEMA_NAME.source.user_name IS 'User responsible for this resource';

COMMENT ON COLUMN DB_SCHEMA_NAME.source.subgroup IS 'Group';

COMMENT ON COLUMN DB_SCHEMA_NAME.source.source_type IS 'Type of database/resource';

COMMENT ON COLUMN DB_SCHEMA_NAME.source.source_metadata IS 'Additional information';

CREATE SEQUENCE DB_SCHEMA_NAME.source_seq
    START WITH 2000
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.source_type (
    source_type character(1),
    source_type_name character varying(50)
);

CREATE TABLE DB_SCHEMA_NAME.surface_genie (
    prot_entry_id bigint NOT NULL,
    spc smallint NOT NULL,
    surfy smallint NOT NULL,
    town smallint NOT NULL,
    cunha smallint NOT NULL,
    diaz smallint NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.taxon (
    taxon_id integer NOT NULL,
    scientific_name character varying(200) NOT NULL,
    tax_id character varying(10) NOT NULL,
    rank character varying(30)
);

COMMENT ON TABLE DB_SCHEMA_NAME.taxon IS 'List of all biological organisms';

COMMENT ON COLUMN DB_SCHEMA_NAME.taxon.taxon_id IS 'Primary key defining a biological organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.taxon.scientific_name IS 'Scientific name given to a biological organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.taxon.tax_id IS 'NCBI Taxonomic identifier unique to a biological organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.taxon.rank IS 'Relative level of a group or individual organism(s) in the taxonomic hierarchy';

CREATE TABLE DB_SCHEMA_NAME.taxon_tree (
    taxon_id integer NOT NULL,
    tax_level smallint NOT NULL,
    level_left integer NOT NULL,
    level_right integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.taxon_tree IS 'Nested set representation of the taxonomic hierarchy. To get all children, search for inner boundaries of the query entry. To get all parent, outer boundaries';

COMMENT ON COLUMN DB_SCHEMA_NAME.taxon_tree.taxon_id IS 'Foreign key to taxon table defining a biological organism';

COMMENT ON COLUMN DB_SCHEMA_NAME.taxon_tree.tax_level IS 'Relative level of this taxon record in the taxonomic hierarchy';

COMMENT ON COLUMN DB_SCHEMA_NAME.taxon_tree.level_left IS 'Left boundary for the nested set representation.';

COMMENT ON COLUMN DB_SCHEMA_NAME.taxon_tree.level_right IS 'Right boundary for the nested set representation';

CREATE TABLE DB_SCHEMA_NAME.tr_protseq_al (
    tr_protseq_al_id integer NOT NULL,
    prot_seq_id bigint NOT NULL,
    transcript_id bigint NOT NULL,
    from_uniprot boolean NOT NULL,
    perc_sim real,
    perc_iden real,
    perc_sim_com real,
    perc_iden_com real
);

COMMENT ON TABLE DB_SCHEMA_NAME.tr_protseq_al IS 'mRNA/Protein translation summary';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_al.tr_protseq_al_id IS 'Primary key for mRNA/protein translation summary';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_al.prot_seq_id IS 'Foreign key to prot_seq. Defines a protein isoforom';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_al.transcript_id IS 'Foreign key to transcript. Defines a mRNA transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_al.from_uniprot IS 'T if this association is provided by uniprot';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_al.perc_sim IS 'Overall percent similarity between the translated CDS and the isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_al.perc_iden IS 'Overall percent identity between the translated CDS and the isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_al.perc_sim_com IS ' percent similarity of the aligned region between the translated CDS and the isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_al.perc_iden_com IS ' percent identity of the aligned region between the translated CDS and the isoform';

CREATE TABLE DB_SCHEMA_NAME.tr_protseq_pos_al (
    tr_protseq_pos_al_id bigint NOT NULL,
    tr_protseq_al_id bigint NOT NULL,
    prot_seq_pos_id bigint NOT NULL,
    transcript_pos_id bigint NOT NULL,
    triplet_pos smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.tr_protseq_pos_al IS 'Nucleotide-amino acid translation for mRNA protein translation';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_pos_al.tr_protseq_pos_al_id IS 'Primary key for mRNA/protein translation - individual mapping';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_pos_al.tr_protseq_al_id IS 'Foreign key for mRNA/protein translation summary';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_pos_al.prot_seq_pos_id IS 'Foreign key for prot_Seq_pos, defining an amino-acid in a protein isoform sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_pos_al.transcript_pos_id IS 'Foreign key to transcript_pos, defining a nucleotide in a mRNA sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.tr_protseq_pos_al.triplet_pos IS 'Position of the nucleotide in the triplet coding for the amino-acid (from 1 to 3)';

CREATE TABLE DB_SCHEMA_NAME.transcript (
    transcript_id bigint NOT NULL,
    transcript_name character varying(60) NOT NULL,
    transcript_version character varying(5),
    start_pos bigint NOT NULL,
    end_pos bigint NOT NULL,
    biotype_id smallint,
    feature_id smallint,
    gene_seq_id integer NOT NULL,
    seq_hash character varying(32),
    chr_seq_id smallint NOT NULL,
    support_level character varying(2),
    partial_sequence character varying(1),
    valid_alignment character varying(1)
);

COMMENT ON TABLE DB_SCHEMA_NAME.transcript IS 'Summary information for a transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.transcript_id IS 'Primary key to transcript. Defines a transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.transcript_name IS 'Name of the transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.transcript_version IS 'Version of the transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.start_pos IS 'Starting position on the chromosome';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.end_pos IS 'Ending position on the chromosome';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.biotype_id IS 'Biotype. Foreign key to seq_btype';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.feature_id IS 'Feature type. Foreign key to seq_btype';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.gene_seq_id IS 'Foreign key to gene_seq. Defines the gene sequence this transcript is coded in';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.seq_hash IS 'md5 hash combining the sequence and its regions/exons/DNA positions';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.chr_seq_id IS 'Foreign key to chr_seq. Defines the chromosome/scaffold/patch sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.support_level IS 'Support level';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.partial_sequence IS 'T if the sequence is partial';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript.valid_alignment IS 'T if the alignment against DNA was successful';

CREATE TABLE DB_SCHEMA_NAME.transcript_al (
    transcript_al_id bigint NOT NULL,
    transcript_ref_id bigint NOT NULL,
    transcript_comp_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.transcript_al IS 'Transcript alignment summary';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_al.transcript_al_id IS 'Primary key for transcript alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_al.transcript_ref_id IS 'Foreign key to transcript. Reference transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_al.transcript_comp_id IS 'Foreign key to transcript. Aligned transcript';

CREATE TABLE DB_SCHEMA_NAME.transcript_al_pos (
    transcript_al_pos_id bigint NOT NULL,
    transcript_al_id bigint NOT NULL,
    transcript_pos_ref_id bigint NOT NULL,
    transcript_pos_comp_id bigint NOT NULL,
    al_pos integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.transcript_al_pos IS 'Nucleotide mapping for 2 aligned transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_al_pos.transcript_al_pos_id IS 'Primary key for nucleotide mapping in a transcript sequence alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_al_pos.transcript_al_id IS 'Foreign key to transcript_al. Defines a transcript alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_al_pos.transcript_pos_ref_id IS 'Foreign key to transcript_pos. Defines a nucleotide in the reference transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_al_pos.transcript_pos_comp_id IS 'Foreign key to transcript_pos. Defines a nucleotide in the aligned transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_al_pos.al_pos IS 'Order of the alignment';

CREATE TABLE DB_SCHEMA_NAME.transcript_pos (
    transcript_pos_id bigint NOT NULL,
    transcript_id bigint NOT NULL,
    nucl character varying(1),
    seq_pos integer NOT NULL,
    seq_pos_type_id smallint NOT NULL,
    exon_id smallint,
    chr_seq_pos_id bigint
);

COMMENT ON TABLE DB_SCHEMA_NAME.transcript_pos IS 'Transcript sequence by individual nucleotide';

CREATE TABLE DB_SCHEMA_NAME.transcript_pos_type (
    transcript_pos_type_id smallint NOT NULL,
    transcript_pos_type character varying(20) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.transcript_pos_type IS 'Region a nucleotide is in UTR, CDS';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_pos_type.transcript_pos_type_id IS 'Primary key to transcript_pos_type';

COMMENT ON COLUMN DB_SCHEMA_NAME.transcript_pos_type.transcript_pos_type IS 'Type of transcript position (UTR, CDS, poly-A)';

CREATE TABLE DB_SCHEMA_NAME.translation_tbl (
    translation_tbl_id smallint NOT NULL,
    translation_tbl_name character varying(500)
);

COMMENT ON TABLE DB_SCHEMA_NAME.translation_tbl IS 'Static data defining the type of translation table described by the codon table ';

COMMENT ON COLUMN DB_SCHEMA_NAME.translation_tbl.translation_tbl_id IS 'Primary key for each RNA to protein translation table';

COMMENT ON COLUMN DB_SCHEMA_NAME.translation_tbl.translation_tbl_name IS 'Name of a given RNA to protein translation table';

CREATE TABLE DB_SCHEMA_NAME.variant_allele (
    variant_allele_id integer NOT NULL,
    variant_seq text
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_allele IS 'List of possible alternative alleles';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_allele.variant_allele_id IS 'Primary key for variant allele. Defines potential allele sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_allele.variant_seq IS 'Variant allele sequence';

CREATE TABLE DB_SCHEMA_NAME.variant_change (
    variant_change_id bigint NOT NULL,
    variant_position_id bigint NOT NULL,
    alt_all integer,
    variant_type_id smallint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_change IS 'Reported allele for a given variant position';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_change.variant_change_id IS 'Primary key for variant_change. Defines an allele change for a given DNA position';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_change.variant_position_id IS 'Foreign key for variant_position. Defines the position of a variant in the  DNA ';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_change.alt_all IS 'Foreign key to variant_allele. Defines the allele sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_change.variant_type_id IS 'Foreign key to variant_type. Defines the type of variant this variant change represent (SNP, insertion, deletion)';

CREATE TABLE DB_SCHEMA_NAME.variant_clinv_assert_map (
    variant_clinv_assert_id bigint NOT NULL,
    clinv_assert_id integer NOT NULL,
    variant_change_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_clinv_assert_map IS 'Association between a variant and a clinical record';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_clinv_assert_map.variant_clinv_assert_id IS 'Primary key to variant - clinvar association';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_clinv_assert_map.clinv_assert_id IS 'Foreign key to clinv_assert';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_clinv_assert_map.variant_change_id IS 'Foreign key to variant_change.';

CREATE TABLE DB_SCHEMA_NAME.variant_entry (
    variant_entry_id bigint NOT NULL,
    rsid bigint NOT NULL,
    date_created date,
    date_updated date
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_entry IS 'Variant record - defined by dbSNP';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_entry.variant_entry_id IS 'Primary key for variant entry. Defines a variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_entry.rsid IS 'rsid (dbSNP identifier)';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_entry.date_created IS 'Date created';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_entry.date_updated IS 'Date updated';

CREATE TABLE DB_SCHEMA_NAME.variant_freq_study (
    variant_freq_study_id smallint NOT NULL,
    variant_freq_study_name character varying(100) NOT NULL,
    description character varying(2000),
    short_name character varying(50),
    source_id smallint
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_freq_study IS 'Description of various study providing allele frequency';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_freq_study.variant_freq_study_id IS 'Primary key for variant_Freq_study. Defines a variant frequency study';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_freq_study.variant_freq_study_name IS 'Variant frequency study name';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_freq_study.description IS 'Description';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_freq_study.short_name IS 'Short name';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_freq_study.source_id IS 'Foreign key to source. Deifnes the database source of a study';

CREATE TABLE DB_SCHEMA_NAME.variant_frequency (
    variant_frequency_id bigint NOT NULL,
    variant_change_id bigint NOT NULL,
    variant_freq_study_id smallint NOT NULL,
    ref_count integer NOT NULL,
    alt_count integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_frequency IS 'Frequency in different studies of a given allele';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_frequency.variant_frequency_id IS 'Primary key to variant_frequency';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_frequency.variant_change_id IS 'Foreign key to variant_change. Defines a allele ';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_frequency.variant_freq_study_id IS 'Foreign key to variant_freq_study. Defines a study';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_frequency.ref_count IS 'Reference count';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_frequency.alt_count IS 'Total count';


CREATE SEQUENCE DB_SCHEMA_NAME.variant_frequency_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




CREATE TABLE DB_SCHEMA_NAME.variant_info (
     variant_info_id  integer not null, 
 variant_entry_id  integer not null ,
 source_id    smallint not null ,
 source_entry character varying(50)  not null ,
 info_type  character varying(300) not null ,
 info_text  text  not null ,
 prot_variant  character varying(200));

COMMENT ON TABLE DB_SCHEMA_NAME.variant_info IS 'Additional information about a variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_info.variant_info_id IS 'Primary key to variant info';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_info.variant_entry_id IS 'Foreign key to variant entry';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_info.source_id IS 'Foreign key to source table. Source of the information';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_info.source_entry IS 'Identifier provided by the source';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_info.info_type IS 'Type of information. Title of the section';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_info.info_text IS 'Textual information';



CREATE TABLE DB_SCHEMA_NAME.variant_pmid_map (
    variant_pmid_map_id bigint NOT NULL,
    variant_entry_id bigint NOT NULL,
    pmid_entry_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_pmid_map IS 'Publication mentioning a specific variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_pmid_map.variant_pmid_map_id IS 'Primary key to variant_pmid_map, associating publications mentioning a variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_pmid_map.variant_entry_id IS 'Foreign key to variant_entry. defines a variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_pmid_map.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

CREATE TABLE DB_SCHEMA_NAME.variant_position (
    variant_position_id bigint NOT NULL,
    variant_entry_id bigint NOT NULL,
    ref_all integer NOT NULL,
    chr_seq_pos_id bigint NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_position IS 'Position and reference allele of the variant record in the different chromosome sequence(s)';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_position.variant_position_id IS 'Primary key to variant_position. Defines the position of a variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_position.variant_entry_id IS 'Foreign key to variant_entry. Defines a variant record';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_position.ref_all IS 'Foreign key to variant_allele. Defines the sequence of the reference allele';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_position.chr_seq_pos_id IS 'Foreign key to chr_seq_pos. Defines the DNA position the variant is located on';

CREATE TABLE DB_SCHEMA_NAME.variant_prot_allele (
    variant_prot_allele_id integer NOT NULL,
    variant_prot_seq text
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_prot_allele IS 'List of possible alternative alleles from a protein sequence perspective.';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_prot_allele.variant_prot_allele_id IS 'Primary key. ';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_prot_allele.variant_prot_seq IS 'Unique variant allele amino-acid sequence';

CREATE TABLE DB_SCHEMA_NAME.variant_protein_map (
    variant_protein_id bigint NOT NULL,
    variant_transcript_id bigint NOT NULL,
    prot_seq_id bigint NOT NULL,
    prot_seq_pos_id bigint,
    so_entry_id smallint,
    prot_ref_all integer,
    prot_alt_all integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_protein_map IS 'Impact of a given allele on a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_protein_map.variant_protein_id IS 'Primary key to variant_protein. Defines the impact of a variant on a protein isoform';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_protein_map.variant_transcript_id IS 'Foreign key to variant_transcript. Defines the impact of a variant on the mRNA transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_protein_map.prot_seq_id IS 'Foreign key to prot_seq. Defines the protein isoform impacted by the variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_protein_map.prot_seq_pos_id IS 'Foreign key to prot_seq_pos. Defines the position in the protein isoform that is impacted (optional)';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_protein_map.so_entry_id IS 'Foreign key to so_entry. Defines the type of change';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_protein_map.prot_ref_all IS 'Foreign key to variant_prot_allele. Defines the amino-acid sequence that is modified';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_protein_map.prot_alt_all IS 'Foreign key to variant_prot_allele. Defines the amino-acid sequence this is replacing the reference allele';

CREATE TABLE DB_SCHEMA_NAME.variant_transcript_map (
    variant_transcript_id bigint NOT NULL,
    variant_change_id bigint NOT NULL,
    transcript_id bigint NOT NULL,
    transcript_pos_id bigint,
    so_entry_id smallint,
    tr_ref_all integer,
    tr_alt_all integer
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_transcript_map IS 'Impact of a given allele on a transcript sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_transcript_map.variant_transcript_id IS 'Primary key to variant_transcript. Impact of a variant on a transcript';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_transcript_map.variant_change_id IS 'Foreign key to variant_change. Defines the alternative allele of a variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_transcript_map.transcript_id IS 'Foreign key to transcript. Defines the transcript impact by the variant change';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_transcript_map.transcript_pos_id IS 'Foreign key to transcript_pos. Defines the nucleotide of the transcript impacted by the variant change';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_transcript_map.so_entry_id IS 'Foreign key to so_entry. Defines the type of change';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_transcript_map.tr_ref_all IS 'Foreign key to variant_allele. Defines the nucleotide sequence that is modified';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_transcript_map.tr_alt_all IS 'Foreign key to variant_allele. Defines the nucleotide sequence that is modified';

CREATE TABLE DB_SCHEMA_NAME.variant_type (
    variant_type_id smallint NOT NULL,
    variant_name character varying(10) NOT NULL,
    so_entry_id smallint
);

COMMENT ON TABLE DB_SCHEMA_NAME.variant_type IS 'Type of variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_type.variant_type_id IS 'Primary key to variant_Type. Defines the type of variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_type.variant_name IS 'Name of variant';

COMMENT ON COLUMN DB_SCHEMA_NAME.variant_type.so_entry_id IS 'Foreign key to so_entry.';

CREATE TABLE DB_SCHEMA_NAME.web_job (
    web_job_id integer NOT NULL,
    lly_user_id integer NOT NULL,
    job_name character varying(20) NOT NULL,
    params text NOT NULL,
    md5id character varying(200) NOT NULL,
    is_private character(1),
    job_description character varying(2000),
    job_title character varying(500) NOT NULL,
    time_start timestamp without time zone NOT NULL,
    time_end timestamp without time zone,
    job_status text,
    job_cluster_id character varying(30)
);

COMMENT ON TABLE DB_SCHEMA_NAME.web_job IS 'Job triggered from the website';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.web_job_id IS 'Primary key to web_job. Defines a job submitted by the user';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.lly_user_id IS 'Foreign key to web_user. Defines the user that submitted the the job';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.job_name IS 'Name of the job';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.params IS 'Parameters in json format';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.md5id IS 'unique job identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.is_private IS 'Is it a private job?';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.job_description IS 'Textual description provided by the user';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.job_title IS 'Title of the job provided by the user';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.time_start IS 'Time the job started';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.time_end IS 'Time the job ended';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.job_status IS 'Status of the job';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job.job_cluster_id IS 'Job identifier on the cluster';

CREATE TABLE DB_SCHEMA_NAME.web_job_document (
    web_job_document_id bigint NOT NULL,
    document_name character varying(2000) NOT NULL,
    document_description character varying(4000) NOT NULL,
    document_hash character varying(32) NOT NULL,
    document_content bytea NOT NULL,
    create_date timestamp without time zone NOT NULL,
    mime_type character varying(200) NOT NULL,
    web_job_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.web_job_document IS 'Results of a job';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job_document.web_job_document_id IS 'Primary key for documents (or results) associated to a job';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job_document.document_name IS 'Name of the document';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job_document.document_description IS 'Description of the file';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job_document.document_hash IS 'md5 hash of the file';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job_document.document_content IS '(binary) Content of the file';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job_document.create_date IS 'Date created';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job_document.mime_type IS 'mime type';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_job_document.web_job_id IS 'Foreign key to web_job. defines the parent web job';

CREATE SEQUENCE DB_SCHEMA_NAME.web_job_document_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE SEQUENCE DB_SCHEMA_NAME.web_job_sq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.web_logs (
    log_id integer NOT NULL,
    log_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    log_level character varying(10),
    message text,
    ip_address character varying(50),
    user_agent character varying(200)
);

COMMENT ON TABLE DB_SCHEMA_NAME.web_logs IS 'Log';

ALTER TABLE DB_SCHEMA_NAME.web_logs ALTER COLUMN log_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME DB_SCHEMA_NAME.web_logs_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

CREATE SEQUENCE DB_SCHEMA_NAME.web_stat_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.web_user (
    web_user_id bigint NOT NULL,
    global_id character varying(15) NOT NULL,
    last_name character varying(200) NOT NULL,
    first_name character varying(200) NOT NULL,
    email character varying(500),
    job_level character varying(10),
    country character varying(200),
    job_family_group character varying(200),
    job_family character varying(200),
    business_title character varying(500),
    manager_id bigint,
    worker_type character varying(200)
);

COMMENT ON TABLE DB_SCHEMA_NAME.web_user IS 'User of the database';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.web_user_id IS 'Primary key to web_user. Defines a user';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.global_id IS 'Company/Institution User identifier';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.last_name IS 'Last name';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.first_name IS 'First name';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.email IS 'Email';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.job_level IS 'Job level';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.country IS 'Country the person is working in';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.job_family_group IS 'Family group of the job position the user is in';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.job_family IS 'Job family';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.business_title IS 'Business title';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.manager_id IS 'Foreign key to web_user - defines the manager';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user.worker_type IS 'Type of worker - employee or contingent';

CREATE SEQUENCE DB_SCHEMA_NAME.web_user_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE DB_SCHEMA_NAME.web_user_stat (
    web_user_stat_id bigint NOT NULL,
    web_user_id bigint NOT NULL,
    date_accessed timestamp without time zone NOT NULL,
    website character varying(10) NOT NULL,
    portal_value character varying(2000),
    page text NOT NULL,
    ip_addr character varying(200),
    portal character varying(30),
    "time" double precision
);

COMMENT ON TABLE DB_SCHEMA_NAME.web_user_stat IS 'Log of user usage';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user_stat.web_user_stat_id IS 'Primary key for web_user_Stat. Logs user access to the website';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user_stat.web_user_id IS 'Foreign key to web_user. Defines a user';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user_stat.date_accessed IS 'Date the user accessed the website';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user_stat.website IS 'If multiple website, provides which website';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user_stat.portal_value IS 'Portal';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user_stat.page IS 'Full page';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user_stat.ip_addr IS 'IP Address';

COMMENT ON COLUMN DB_SCHEMA_NAME.web_user_stat."time" IS 'Time';

CREATE TABLE DB_SCHEMA_NAME.xr_atom (
    xr_atom_id bigint NOT NULL,
    identifier character varying(100) NOT NULL,
    xr_tpl_atom_id bigint,
    xr_res_id bigint NOT NULL,
    charge smallint NOT NULL,
    mol2type character varying(6) NOT NULL,
    b_factor real NOT NULL,
    x double precision NOT NULL,
    y double precision NOT NULL,
    z double precision NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_atom IS 'Atom reported in a crystal structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.xr_atom_id IS 'Primary key for xr_atom. Defines an atom in a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.identifier IS 'Unique Atom identifier, combines pdb id, atom, atom id, residu, residue id, chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.xr_tpl_atom_id IS 'Foreign key to xr_tpl_atom. Defines the template atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.xr_res_id IS 'Foreign key to xr_res. Defines the residue this atom is in';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.charge IS 'Charge of the atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.mol2type IS 'MOL2 Type of the atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.b_factor IS 'B-Factor of the atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.x IS 'X coordinate of the atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.y IS 'Y coordinate of the atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_atom.z IS 'Z coordinate of the atom';

CREATE TABLE DB_SCHEMA_NAME.xr_bond (
    xr_bond_id bigint NOT NULL,
    bond_type character varying(2) NOT NULL,
    xr_atom_id_1 bigint NOT NULL,
    xr_atom_id_2 bigint NOT NULL,
    xr_chain_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_bond IS 'Bond between two atoms';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_bond.xr_bond_id IS 'Primary key to xr_Bond. Defines a bond between two atoms';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_bond.bond_type IS 'Type of bond (single,double, triple, aromatic ...)';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_bond.xr_atom_id_1 IS 'Foreign key to xr_atom. Defines the first atom involved in the bond';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_bond.xr_atom_id_2 IS 'Foreign key to xr_atom. Defines the second atom involved in the bond';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_bond.xr_chain_id IS 'Foreign key to xr_chain. Defines the chain those two atoms are from';

CREATE TABLE DB_SCHEMA_NAME.xr_cav (
    xr_cav_id bigint NOT NULL,
    cavity_name character varying(2000) NOT NULL,
    xr_site_id bigint NOT NULL,
    site_coverage real NOT NULL,
    source character varying(1) NOT NULL,
    context character varying(3) NOT NULL,
    volume double precision NOT NULL,
    perc_hydrophobicity real NOT NULL,
    drug_score_1 real NOT NULL,
    drug_score_2 real NOT NULL,
    file_path character varying(500) NOT NULL,
    origin character varying(2) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_cav IS 'Protein cavity';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.xr_cav_id IS 'Primary key to xr_cav, defines a cavity made by the protein structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.cavity_name IS 'Identification of the cavity';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.xr_site_id IS 'Foreign key to xr_site, defining a (binding) site';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.site_coverage IS 'Coverage of this cavity versus the defined binding site';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.source IS 'V:Volsite ; S:Sitemap';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.context IS 'L:Liganded ; U:Unliganded ; I:Interface; D:Disrupter; R:RNA/DNA';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.volume IS 'Volume in Angstroems^3 of the cavity';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.perc_hydrophobicity IS 'Percentage hydrophobicity';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.drug_score_1 IS 'Predicted druggability';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.drug_score_2 IS 'Predicted druggability';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.file_path IS 'Path to the file';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_cav.origin IS 'C: Chain ; L:Ligand ; CL: Lig_Cav ; CP:CPLX';

CREATE TABLE DB_SCHEMA_NAME.xr_ch_lig_map (
    xr_ch_lig_map_id bigint NOT NULL,
    xr_chain_id bigint NOT NULL,
    xr_lig_id bigint NOT NULL,
    "position" integer DEFAULT '-1'::integer NOT NULL,
    xr_site_id bigint
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_ch_lig_map IS 'Mapping between a ligand and a chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_lig_map.xr_ch_lig_map_id IS 'Primary key to xr_ch_lig_map, mapping ligand to chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_lig_map.xr_chain_id IS 'Foreign key to xr_chain ,defining a chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_lig_map.xr_lig_id IS 'Foreign key to xr_lig, defining a ligand';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_lig_map."position" IS 'Position';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_lig_map.xr_site_id IS 'Site';

CREATE TABLE DB_SCHEMA_NAME.xr_ch_prot_map (
    xr_ch_prot_map_id integer NOT NULL,
    xr_chain_id bigint NOT NULL,
    prot_seq_id bigint NOT NULL,
    perc_sim real NOT NULL,
    perc_identity real NOT NULL,
    length integer NOT NULL,
    perc_sim_com real NOT NULL,
    perc_identity_com real NOT NULL,
    is_primary character varying(1) NOT NULL,
    is_chimeric character varying(1) DEFAULT 'F'::character varying NOT NULL,
    n_mutant smallint DEFAULT 0 NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_ch_prot_map IS 'Summary of the connection between a protein chain and a protein sequence (% identity,similarity)';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.xr_ch_prot_map_id IS 'Primary key to xr_ch_prot_map table. Map 3-D chain to protein sequnence';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.xr_chain_id IS 'Foreign key to xr_chain, defining a chain in a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.prot_seq_id IS 'Foreign key to prot_seq, defining a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.perc_sim IS 'Overall percent similarity';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.perc_identity IS 'Overall percent identity';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.length IS 'Length of the alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.perc_sim_com IS 'Percent similarity of the aligned region';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.perc_identity_com IS 'Percent identity of the aligned region';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.is_chimeric IS 'T if the 3-D chain is made of different proteins';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_map.n_mutant IS 'Number of identified mutant';

CREATE TABLE DB_SCHEMA_NAME.xr_ch_prot_pos (
    xr_ch_prot_pos_id bigint NOT NULL,
    xr_res_id bigint,
    prot_seq_pos_id bigint NOT NULL,
    xr_prot_map_type character varying(1) DEFAULT 'F'::character varying NOT NULL,
    xr_ch_prot_map_id bigint NOT NULL,
    CONSTRAINT xr_ch_prot_pos_chk1 CHECK (((xr_res_id IS NOT NULL) OR (prot_seq_pos_id IS NOT NULL)))
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_ch_prot_pos IS 'Individual mapping between a residu in a protein chain and an amino acid in a protein sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_pos.xr_ch_prot_pos_id IS 'Primary key to xr_ch_prot_pos. individual amino-acid alignment between a 3-D residu and an amino-acid';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_pos.xr_res_id IS 'Foreign key to xr_res. Defines a 3-D residu';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_pos.prot_seq_pos_id IS 'Foreign key to prot_seq_pos. Defines an amino-acid';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_pos.xr_prot_map_type IS 'Type of mapping';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_prot_pos.xr_ch_prot_map_id IS 'Foreign key to xr_ch_prot_map. Defines the alignment';

CREATE TABLE DB_SCHEMA_NAME.xr_ch_udom_map (
    xr_ch_dom_map_id bigint NOT NULL,
    xr_chain_id bigint NOT NULL,
    prot_dom_id bigint NOT NULL,
    perc_sim real NOT NULL,
    perc_identity real NOT NULL,
    length integer NOT NULL,
    perc_sim_com real NOT NULL,
    perc_identity_com real NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_ch_udom_map IS 'Summary of the connection between a protein chain and a protein domain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_udom_map.xr_ch_dom_map_id IS 'Primary key to xr_ch_dom_map. Map 3-D chain to protein chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_udom_map.xr_chain_id IS 'Foreign key to xr_chain, defining a chain in a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_udom_map.prot_dom_id IS 'Foreign key to prot_dom, defining a protein domain sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_udom_map.perc_sim IS 'Overall percent similarity';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_udom_map.perc_identity IS 'Overall percent identity';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_udom_map.length IS 'Length of the alignment';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_udom_map.perc_sim_com IS 'Percent similarity of the aligned region';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ch_udom_map.perc_identity_com IS 'Percent identity of the aligned region';

CREATE TABLE DB_SCHEMA_NAME.xr_chain (
    xr_chain_id bigint NOT NULL,
    xr_entry_id integer NOT NULL,
    chain_name character varying(4) NOT NULL,
    length integer DEFAULT '-1'::integer,
    chain_type character varying(20) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_chain IS 'Chain reported in a crystal structure ';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_chain.xr_chain_id IS 'Primary key to xr_chain. Defines a 3-D chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_chain.xr_entry_id IS 'Foreign key to xr_entry, defining a 3-D structure this 3-D chain is reported in';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_chain.chain_name IS 'Name of the chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_chain.length IS 'Number of residu in the chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_chain.chain_type IS 'Chain Type: Protein/Peptide/Nucleic acid...';

CREATE TABLE DB_SCHEMA_NAME.xr_element (
    xr_element_id smallint NOT NULL,
    atomic_radius double precision,
    is_biologic character(1) NOT NULL,
    mass double precision NOT NULL,
    name character varying(50) NOT NULL,
    symbol character varying(5) NOT NULL,
    vdwradius double precision
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_element IS 'Individual atomic definition';

CREATE TABLE DB_SCHEMA_NAME.xr_entry (
    xr_entry_id integer NOT NULL,
    full_common_name character varying(100) NOT NULL,
    expr_type character varying(60) NOT NULL,
    resolution real,
    deposition_date timestamp without time zone,
    date_created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    date_updated timestamp without time zone,
    title character varying(600)
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_entry IS 'Crystal structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry.xr_entry_id IS 'Primary key to xr_entry. Defines a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry.full_common_name IS 'PDB Code';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry.expr_type IS 'Experimental type: X-Ray, NMR, Cryo-EM ...';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry.resolution IS 'Resolution of the 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry.deposition_date IS 'Date the structure was deposited';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry.date_created IS 'Date the record was created';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry.date_updated IS 'Date the record was updated';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry.title IS 'Description of the 3-D Structure';

CREATE TABLE DB_SCHEMA_NAME.xr_entry_pmid (
    xr_entry_pmid_id integer NOT NULL,
    xr_entry_id integer NOT NULL,
    pmid_entry_id bigint NOT NULL,
    is_primary character varying(1) NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_entry_pmid IS 'Publication reporting this crystal structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry_pmid.xr_entry_pmid_id IS 'Primary key to xr_entry_pmid. Map publications to 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry_pmid.xr_entry_id IS 'Foreign key to xr_entry, defining a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry_pmid.pmid_entry_id IS 'Foreign key to pmid_Entry. Defines a publication';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_entry_pmid.is_primary IS 'T if this publication was the one reporting this 3-D structure';

CREATE TABLE DB_SCHEMA_NAME.xr_inter_res (
    xr_inter_res_id bigint NOT NULL,
    xr_atom_id_1 bigint,
    xr_res_id_1 bigint NOT NULL,
    atom_list_1 character varying(30) NOT NULL,
    xr_res_id_2 bigint NOT NULL,
    atom_list_2 character varying(30) NOT NULL,
    xr_atom_id_2 bigint,
    xr_inter_type_id integer NOT NULL,
    distance double precision NOT NULL,
    angle double precision
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_inter_res IS 'Non-bonded interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.xr_inter_res_id IS 'Primary key to xr_inter_res. Defines a non-convalent interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.xr_atom_id_1 IS 'Foreign key to xr_atom (optional). Defines the first atom in the interaction. NULL if multiple atom (aromatic cycle) involved';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.xr_res_id_1 IS 'Foreign key to xr_residu. Defines the first residu in the interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.atom_list_1 IS 'Textual list of atoms from the first residu involved in the interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.xr_res_id_2 IS 'Foreign key to xr_residu. Defines the second residu in the interaction.';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.atom_list_2 IS 'Textual list of atoms from the second residu involved in the interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.xr_atom_id_2 IS 'Foreign key to xr_atom (optional). Defines the second atom in the interaction.NULL if multiple atom (aromatic cycle) involved';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.xr_inter_type_id IS 'Foreign key to xr_inter_type. Defines the type of interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.distance IS 'Distance between the two groups of atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_res.angle IS 'Angle (degree) - Optional';

CREATE TABLE DB_SCHEMA_NAME.xr_inter_type (
    xr_inter_type_id smallint NOT NULL,
    angle_threshold double precision,
    interaction_description character varying(150) NOT NULL,
    distance_threshold double precision NOT NULL,
    interaction_name character varying(30) NOT NULL,
    angle_extent real
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_inter_type IS 'Non bonded interaction type';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_type.xr_inter_type_id IS 'Primary key to xr_inter_type. Defines a type of interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_type.angle_threshold IS 'Threshold for the angle';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_type.interaction_description IS 'Description of the interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_type.distance_threshold IS 'Threshold for the distance';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_type.interaction_name IS 'Name of the interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_inter_type.angle_extent IS 'Range in degrees for the angle';

CREATE TABLE DB_SCHEMA_NAME.xr_jobs (
    xr_job_id smallint NOT NULL,
    xr_job_name character varying(200) NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_jobs.xr_job_id IS 'Primary key to xr_job. Defines a type of processing job';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_jobs.xr_job_name IS 'Job name';

CREATE TABLE DB_SCHEMA_NAME.xr_lig (
    xr_lig_id bigint NOT NULL,
    lig_name character varying(30),
    internal_flag character varying(1),
    smiles character varying(2000),
    class character varying(10)
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_lig IS 'Mapping between a ligand and a small molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_lig.xr_lig_id IS 'Primary key to xr_lig, defines a ligand in a 3-d structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_lig.lig_name IS 'Ligand name';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_lig.internal_flag IS 'T if internal structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_lig.smiles IS 'SMILES of the molecule';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_lig.class IS 'Class of the molecule';

CREATE TABLE DB_SCHEMA_NAME.xr_ppi (
    xr_ppi_id integer NOT NULL,
    xr_chain_r_id integer NOT NULL,
    xr_chain_c_id integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_ppi IS 'Protein/Protein chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ppi.xr_ppi_id IS 'Primary key for xr_ppi. Defines a protein protein interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ppi.xr_chain_r_id IS 'Foreign key to xr_chain, defining a chain in a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_ppi.xr_chain_c_id IS 'Foreign key to xr_chain, defining a chain in a 3-D structure';

CREATE TABLE DB_SCHEMA_NAME.xr_prot_dom_cov (
    xr_prot_dom_cov_id integer NOT NULL,
    prot_dom_id bigint NOT NULL,
    xr_chain_id bigint NOT NULL,
    coverage real NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_dom_cov.xr_prot_dom_cov_id IS 'Primary key to prot_dom_cov';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_dom_cov.prot_dom_id IS 'Foreign key to prot_dom. defines a protein domain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_dom_cov.xr_chain_id IS 'Foreign key to xr_chain, defining a chain in a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_dom_cov.coverage IS 'coverage';

CREATE TABLE DB_SCHEMA_NAME.xr_prot_int_stat (
    xr_prot_int_stat_id integer NOT NULL,
    prot_entry_id bigint NOT NULL,
    prot_seq_pos_id bigint NOT NULL,
    xr_inter_type_id smallint NOT NULL,
    class character varying(20) NOT NULL,
    count_int integer NOT NULL,
    atom_list character varying(30) NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_int_stat.xr_prot_int_stat_id IS 'Primary key to xr_prot_int_stat, provides statistics of non-bonded interaction for a given amino-acid';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_int_stat.prot_entry_id IS 'Foreign key to prot_entry. Defines a protein';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_int_stat.prot_seq_pos_id IS 'Foreign key to prot_Seq_pos. Defines an amino-acid in a protein isoform sequence';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_int_stat.xr_inter_type_id IS 'Foreign key to xr_inter_type. Defines the type of non-bonded interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_int_stat.class IS 'Type of residu involved in the interaction';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_int_stat.count_int IS 'Number of interactions';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_prot_int_stat.atom_list IS 'list of atoms';

CREATE TABLE DB_SCHEMA_NAME.xr_prot_stat (
    xr_prot_stat_id integer NOT NULL,
    prot_entry_id bigint NOT NULL,
    count integer NOT NULL
);

CREATE TABLE DB_SCHEMA_NAME.xr_res (
    xr_res_id bigint NOT NULL,
    xr_chain_id bigint NOT NULL,
    xr_tpl_res_id integer NOT NULL,
    "position" smallint NOT NULL,
    cacoord character varying(33)
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_res IS 'Crystal structure residu';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_res.xr_res_id IS 'Primary key to xr_res. defines a residu in a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_res.xr_chain_id IS 'Foreign key to xr_chain, defining a chain in a #-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_res.xr_tpl_res_id IS 'Foreign key to xr_tpl_res. Defines a template residu from the dictionary';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_res."position" IS 'Number of the residu in the chain';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_res.cacoord IS 'Calpha coordinates.';

CREATE TABLE DB_SCHEMA_NAME.xr_site (
    xr_site_id integer NOT NULL,
    xr_entry_id integer NOT NULL,
    site_n smallint NOT NULL,
    subsite_n smallint DEFAULT 0 NOT NULL,
    original character varying(2)
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_site IS 'Binding site';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_site.xr_site_id IS 'Primary key to xr_site. Defines a binding site';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_site.xr_entry_id IS 'Foreign key to xr_entry, defining a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_site.site_n IS 'Classification - site number';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_site.subsite_n IS 'Subsite number';

CREATE TABLE DB_SCHEMA_NAME.xr_site_res (
    xr_site_res_id bigint NOT NULL,
    xr_site_id integer NOT NULL,
    xr_res_id bigint NOT NULL,
    perc real NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_site_res IS 'Residus involved in a binding site';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_site_res.xr_site_res_id IS 'Primary key to xr_site_res. Defines a residu involved in a site';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_site_res.xr_site_id IS 'Foreign key to xr_site, defining a site';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_site_res.xr_res_id IS 'Foreign key to xr_res, defining a residu in a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_site_res.perc IS 'Percentage';

CREATE TABLE DB_SCHEMA_NAME.xr_status (
    xr_status_id bigint NOT NULL,
    xr_entry_id bigint NOT NULL,
    xr_job_id smallint NOT NULL,
    date_processed timestamp without time zone NOT NULL,
    status_value character varying(3) NOT NULL
);

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_status.xr_status_id IS 'Primary key to xr_status, defining the jobs status for a given 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_status.xr_entry_id IS 'Foreign key to xr_entry, defining a 3-D structure';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_status.xr_job_id IS 'Foreign key to xr_job. Defines a processing job';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_status.date_processed IS 'Date processed';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_status.status_value IS 'Status';

CREATE TABLE DB_SCHEMA_NAME.xr_tpl_atom (
    xr_tpl_atom_id bigint NOT NULL,
    name character varying(5) NOT NULL,
    xr_element_id smallint NOT NULL,
    xr_tpl_res_id integer NOT NULL,
    mol2type character varying(15) NOT NULL,
    stereo integer,
    charge smallint
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_tpl_atom IS 'Atom in Residu template';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_atom.xr_tpl_atom_id IS 'Primary key to xr_tpl_atom. Defines a template atom from the dictionary';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_atom.name IS 'Name of the atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_atom.xr_element_id IS 'Foreign key to xr_element. Defines the atomic type of the template atom';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_atom.xr_tpl_res_id IS 'Foreign key to xr_tpl_res. Defines a template residu from the dictionary';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_atom.mol2type IS 'MOL2 Type';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_atom.stereo IS 'Stereo';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_atom.charge IS 'Charge of the atom';

CREATE TABLE DB_SCHEMA_NAME.xr_tpl_bond (
    xr_tpl_bond_id bigint NOT NULL,
    bond_type character varying(2) NOT NULL,
    xr_tpl_atom_id_1 integer NOT NULL,
    xr_tpl_atom_id_2 integer NOT NULL
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_tpl_bond IS 'Bond in Residu template';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_bond.xr_tpl_bond_id IS 'Primary key to xr_tpl_bond. Defines a template bond from the dictionary';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_bond.bond_type IS 'Type of bond (single, double, triple...)';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_bond.xr_tpl_atom_id_1 IS 'Foreign key to xr_tpl_atom. Defines a template atom from the dictionary';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_bond.xr_tpl_atom_id_2 IS 'Foreign key to xr_tpl_atom. Defines a template atom from the dictionary';

CREATE TABLE DB_SCHEMA_NAME.xr_tpl_res (
    xr_tpl_res_id integer NOT NULL,
    date_created timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    name character varying(30) NOT NULL,
    smiles character varying(2000) NOT NULL,
    class character varying(10) NOT NULL,
    subclass character varying(40),
    replaced_by_id integer,
    sm_molecule_id bigint
);

COMMENT ON TABLE DB_SCHEMA_NAME.xr_tpl_res IS 'Template residu';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_res.xr_tpl_res_id IS 'Primary key to xr_tpl_res. Defines a template residu from the dictionary';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_res.date_created IS 'Date created';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_res.name IS 'Residue code';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_res.smiles IS 'SMILES of the residu';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_res.class IS 'Class of the residu';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_res.subclass IS 'Subclass of the residu';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_res.replaced_by_id IS 'If replaced, foreign key to xr_tpl_res, defining the new residu';

COMMENT ON COLUMN DB_SCHEMA_NAME.xr_tpl_res.sm_molecule_id IS 'Foreign key to sm_molecule, defining a small molecule';


/* $[NO_PRIVATE]
This section will be deleted if no private schema provided */

CREATE TABLE DB_SCHEMA_NAME.internal_molecule (
internal_molecule_id bigint not null,
internal_id bigint not null,
molecular_entity_id bigint not null,
date_created date not null,
date_updated date not null,
inventory_mg float,
primary key (internal_molecule_id));

CREATE UNIQUE INDEX internal_mol_uk ON DB_SCHEMA_NAME.internal_molecule(internal_id);
CREATE INDEX internal_mol_mol ON DB_SCHEMA_NAME.molecular_entity(molecular_entity_id);
CREATE INDEX internal_mol_dt ON DB_SCHEMA_NAME.internal_molecule(date_created);

CREATE TABLE DB_SCHEMA_NAME.internal_library (
internal_library_id integer not null,
internal_library_identifier character varying(100) not null,
internal_library_name character varying(200) not null,
internal_library_description character varying(2000),
internal_library_creator_id integer not null,
internal_library_date_created date not null,
internal_library_date_updated date not null,
Primary key (internal_library_id));

CREATE INDEX internal_library_cr_id  ON DB_SCHEMA_NAME.internal_library(internal_library_creator_id);
CREATE INDEX internal_library_date_id  ON DB_SCHEMA_NAME.internal_library(internal_library_date_created);
CREATE UNIQUE INDEX internal_library_uk ON DB_SCHEMA_NAME.internal_library(internal_library_identifier);
CREATE UNIQUE INDEX internal_library_name_uk ON DB_SCHEMA_NAME.internal_library(internal_library_name);



CREATE TABLE  DB_SCHEMA_NAME.internal_library_molecular_map(
internal_library_molecular_map_id integer not null,
internal_library_id integer not null,
internal_molecule_id bigint not null,
date_added date not null
);


CREATE UNIQUE INDEX internal_lib_mol_lib_uk ON DB_SCHEMA_NAME.internal_library_molecular_map(internal_library_id);
CREATE UNIQUE INDEX internal_lib_mol_mol_uk ON DB_SCHEMA_NAME.internal_library_molecular_map(internal_molecule_id);


/* $[/NO_PRIVATE]*/













ALTER TABLE ONLY DB_SCHEMA_NAME.meddra_entry ALTER COLUMN meddra_entry_id SET DEFAULT nextval('DB_SCHEMA_NAME.meddra_entry_meddra_entry_id_seq'::regclass);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_config ALTER COLUMN sharepoint_config_id SET DEFAULT nextval('DB_SCHEMA_NAME.sharepoint_config_sharepoint_config_id_seq'::regclass);

ALTER TABLE ONLY DB_SCHEMA_NAME.aaname
    ADD CONSTRAINT aaname_pkey PRIMARY KEY (aanameid);

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_component
    ADD CONSTRAINT molecular_component_pkey PRIMARY KEY (molecular_component_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_component_sm_map
    ADD CONSTRAINT molecular_component_sm_map_pkey PRIMARY KEY (molecular_component_sm_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_entity
    ADD CONSTRAINT mol_en_pk PRIMARY KEY (molecular_entity_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_entity_component_map
    ADD CONSTRAINT mol_en_cm_pk PRIMARY KEY (molecular_entity_component_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.activity_entry
    ADD CONSTRAINT activity_entry_pkey PRIMARY KEY (activity_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_entry
    ADD CONSTRAINT anatomy_entry_anatomy_tag_key UNIQUE (anatomy_tag);

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_entry
    ADD CONSTRAINT anatomy_entry_pkey PRIMARY KEY (anatomy_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_extdb
    ADD CONSTRAINT anatomy_extdb_anatomy_entry_id_source_id_anatomy_extdb_key UNIQUE (anatomy_entry_id, source_id, anatomy_extdb);

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_extdb
    ADD CONSTRAINT anatomy_extdb_pkey PRIMARY KEY (anatomy_extdb_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_hierarchy
    ADD CONSTRAINT anatomy_hierarchy_pkey PRIMARY KEY (anatomy_entry_id, anatomy_level, anatomy_level_left, anatomy_level_right);

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_syn
    ADD CONSTRAINT anatomy_syn_pkey PRIMARY KEY (anatomy_syn_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_cell
    ADD CONSTRAINT assay_cell_pkey PRIMARY KEY (assay_cell_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_pkey PRIMARY KEY (assay_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_genetic
    ADD CONSTRAINT assay_genetic_pkey PRIMARY KEY (assay_genetic_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_pmid
    ADD CONSTRAINT assay_pmid_pkey PRIMARY KEY (assay_pmid_entry);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_protein
    ADD CONSTRAINT assay_protein_pkey PRIMARY KEY (assay_protein_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target_genetic_map
    ADD CONSTRAINT assay_target_genetic_map_pkey PRIMARY KEY (assay_target_genetic_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target
    ADD CONSTRAINT assay_target_pkey PRIMARY KEY (assay_target_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target_protein_map
    ADD CONSTRAINT assay_target_protein_map_pkey PRIMARY KEY (assay_target_protein_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target_type
    ADD CONSTRAINT assay_target_type_pkey PRIMARY KEY (assay_target_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_tissue
    ADD CONSTRAINT assay_tissue_pkey PRIMARY KEY (assay_tissue_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_type
    ADD CONSTRAINT assay_type_pk PRIMARY KEY (assay_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_variant
    ADD CONSTRAINT assay_variant_pkey PRIMARY KEY (assay_variant_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_variant_pos
    ADD CONSTRAINT assay_variant_pos_pkey PRIMARY KEY (assay_variant_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.bioassay_onto_entry
    ADD CONSTRAINT bioassay_onto_entry_pkey PRIMARY KEY (bioassay_onto_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.bioassay_onto_extdb
    ADD CONSTRAINT bioassay_onto_extdb_pkey PRIMARY KEY (bioassay_onto_extdb_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.bioassay_onto_hierarchy
    ADD CONSTRAINT bioassay_onto_hierarchy_pkey PRIMARY KEY (bioassay_onto_entry_id, bioassay_onto_level, bioassay_onto_level_left, bioassay_onto_level_right);

ALTER TABLE ONLY DB_SCHEMA_NAME.biorels_datasource
    ADD CONSTRAINT biorels_datasource_pkey PRIMARY KEY (source_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.biorels_timestamp
    ADD CONSTRAINT biorels_timestamp_job_name_key UNIQUE (job_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.biorels_timestamp
    ADD CONSTRAINT biorels_timestamp_pkey PRIMARY KEY (br_timestamp_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_disease
    ADD CONSTRAINT cell_disease_pkey PRIMARY KEY (cell_disease_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_entry
    ADD CONSTRAINT cell_entry_pkey PRIMARY KEY (cell_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_patent_map
    ADD CONSTRAINT cell_patent_map_pkey PRIMARY KEY (cell_patent_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_pmid_map
    ADD CONSTRAINT cell_pmid_map_pkey PRIMARY KEY (cell_pmid_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_syn
    ADD CONSTRAINT cell_syn_pkey PRIMARY KEY (cell_syn_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_taxon_map
    ADD CONSTRAINT cell_taxon_map_pkey PRIMARY KEY (cell_taxon_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_tissue
    ADD CONSTRAINT cell_tissue_cell_tissue_name_key UNIQUE (cell_tissue_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_tissue
    ADD CONSTRAINT cell_tissue_pkey PRIMARY KEY (cell_tissue_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_gn_map
    ADD CONSTRAINT chr_gn_map_chr_map_id_gn_entry_id_key UNIQUE (chr_map_id, gn_entry_id);

COMMENT ON CONSTRAINT chr_gn_map_chr_map_id_gn_entry_id_key ON DB_SCHEMA_NAME.chr_gn_map IS 'A gene is located once on a given cytogenetic location';

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_gn_map
    ADD CONSTRAINT chr_gn_map_pkey PRIMARY KEY (chr_gn_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_map
    ADD CONSTRAINT chr_map_chr_id_map_location_key UNIQUE (chr_id, map_location);

COMMENT ON CONSTRAINT chr_map_chr_id_map_location_key ON DB_SCHEMA_NAME.chr_map IS 'A cytogenetic location exist once in a given chromosome';

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_map
    ADD CONSTRAINT chr_map_pkey PRIMARY KEY (chr_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_seq
    ADD CONSTRAINT chr_seq_pkey PRIMARY KEY (chr_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_seq_pos
    ADD CONSTRAINT chr_seq_pos_pkey PRIMARY KEY (chr_seq_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.chromosome
    ADD CONSTRAINT chromosome_pkey PRIMARY KEY (chr_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.chromosome
    ADD CONSTRAINT chromosome_taxon_id_chr_num_key UNIQUE (taxon_id, chr_num);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_significance
    ADD CONSTRAINT clinical_significance_clin_sign_key UNIQUE (clin_sign);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_significance
    ADD CONSTRAINT clinical_significance_pkey PRIMARY KEY (clin_sign_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_alias
    ADD CONSTRAINT clinical_trial_alias_clinical_trial_id_alias_name_key UNIQUE (clinical_trial_id, alias_name,alias_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_alias
    ADD CONSTRAINT clinical_trial_alias_pkey PRIMARY KEY (clinical_trial_alias_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_intervention
    ADD CONSTRAINT clinical_trial_intervention_pkey PRIMARY KEY (clinical_trial_intervention_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_company_map
    ADD CONSTRAINT clinical_trial_company_map_pkey PRIMARY KEY (clinical_trial_id, company_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_date
    ADD CONSTRAINT clinical_trial_date_pkey PRIMARY KEY (clinical_trial_date_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial
    ADD CONSTRAINT clinical_trial_pkey PRIMARY KEY (clinical_trial_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_pmid_map
    ADD CONSTRAINT clinical_trial_pmid_map_pkey PRIMARY KEY (clinical_trial_pmid_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_disease_map
    ADD CONSTRAINT clinical_variant_disease_map_clinvar_submission_id_disease__key UNIQUE (clinvar_submission_id, disease_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_disease_map
    ADD CONSTRAINT clinical_variant_disease_map_pkey PRIMARY KEY (clinvar_disease_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_entry
    ADD CONSTRAINT clinical_variant_entry_pkey PRIMARY KEY (clinvar_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_gn_map
    ADD CONSTRAINT clinical_variant_gn_map_clinvar_submission_id_gn_entry_id_key UNIQUE (clinvar_submission_id, gn_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_gn_map
    ADD CONSTRAINT clinical_variant_gn_map_pkey PRIMARY KEY (clinvar_gn_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_map
    ADD CONSTRAINT clinical_variant_map_clinvar_entry_id_variant_entry_id_key UNIQUE (clinvar_entry_id, variant_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_map
    ADD CONSTRAINT clinical_variant_map_pkey PRIMARY KEY (clinical_variant_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_review_status
    ADD CONSTRAINT clinical_variant_review_status_clinvar_review_status_name_key UNIQUE (clinvar_review_status_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_review_status
    ADD CONSTRAINT clinical_variant_review_status_pkey PRIMARY KEY (clinvar_review_status_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_submission
    ADD CONSTRAINT clinical_variant_submission_clinvar_entry_id_scv_id_key UNIQUE (clinvar_entry_id, scv_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_submission
    ADD CONSTRAINT clinical_variant_submission_pkey PRIMARY KEY (clinvar_submission_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_type
    ADD CONSTRAINT clinical_variant_type_clinical_variant_type_key UNIQUE (clinical_variant_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_type
    ADD CONSTRAINT clinical_variant_type_pkey PRIMARY KEY (clinical_variant_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_pmid_map
    ADD CONSTRAINT clinvar_pmid_map_clinvar_submission_id_pmid_entry_id_key UNIQUE (clinvar_submission_id, pmid_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_pmid_map
    ADD CONSTRAINT clinvar_pmid_map_pkey PRIMARY KEY (clinvar_pmid_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.codon
    ADD CONSTRAINT codon_pkey PRIMARY KEY (codon_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.company_entry
    ADD CONSTRAINT company_entry_company_name_key UNIQUE (company_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.company_entry
    ADD CONSTRAINT company_entry_pkey PRIMARY KEY (company_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.company_synonym
    ADD CONSTRAINT company_synonym_company_entry_id_company_syn_name_key UNIQUE (company_entry_id, company_syn_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.company_synonym
    ADD CONSTRAINT company_synonym_pkey PRIMARY KEY (company_synonym_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_confidence
    ADD CONSTRAINT confidence_score_lookup_pk PRIMARY KEY (confidence_score);

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_anatomy_map
    ADD CONSTRAINT disease_anatomy_map_pkey PRIMARY KEY (disease_anatomy_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_entry
    ADD CONSTRAINT disease_entry_pkey PRIMARY KEY (disease_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_extdb
    ADD CONSTRAINT disease_extdb_pkey PRIMARY KEY (disease_extdb_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_gene_acc
    ADD CONSTRAINT disease_gene_acc_pkey PRIMARY KEY (disease_gene_acc_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_hierarchy
    ADD CONSTRAINT disease_hierarchy_pkey PRIMARY KEY (disease_entry_id, disease_level, disease_level_left, disease_level_right);

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_info
    ADD CONSTRAINT disease_info_pkey PRIMARY KEY (disease_info_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_pmid
    ADD CONSTRAINT disease_pmid_pkey PRIMARY KEY (disease_pmid_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_syn
    ADD CONSTRAINT disease_syn_pkey PRIMARY KEY (disease_syn_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.dna_rev
    ADD CONSTRAINT dna_rev_pkey PRIMARY KEY (nucl);

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_disease
    ADD CONSTRAINT drug_disease_pkey PRIMARY KEY (drug_disease_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_entry
    ADD CONSTRAINT drug_entry_pkey PRIMARY KEY (drug_entry_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.drug_extdb
    ADD CONSTRAINT drug_extdb_pkey PRIMARY KEY (drug_extdb_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_name
    ADD CONSTRAINT drug_name_pkey PRIMARY KEY (drug_name_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.eco_entry
    ADD CONSTRAINT eco_entry_pkey PRIMARY KEY (eco_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.eco_hierarchy
    ADD CONSTRAINT eco_hierarchy_pkey PRIMARY KEY (eco_entry_id, eco_level, level_left, level_right);

ALTER TABLE ONLY DB_SCHEMA_NAME.eco_rel
    ADD CONSTRAINT eco_rel_pkey PRIMARY KEY (eco_rel_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.efo_entry
    ADD CONSTRAINT efo_entry_pkey PRIMARY KEY (efo_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.efo_extdb
    ADD CONSTRAINT efo_extdb_pkey PRIMARY KEY (efo_extdb_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.efo_hierarchy
    ADD CONSTRAINT efo_hierarchy_pkey PRIMARY KEY (efo_entry_id, efo_level, efo_level_left, efo_level_right);

ALTER TABLE ONLY DB_SCHEMA_NAME.gene_seq
    ADD CONSTRAINT gene_seq_gene_seq_name_gene_seq_version_chr_seq_id_key UNIQUE (gene_seq_name, gene_seq_version, chr_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gene_seq
    ADD CONSTRAINT gene_seq_pkey PRIMARY KEY (gene_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.genome_assembly
    ADD CONSTRAINT genome_assembly_assembly_accession_key UNIQUE (assembly_accession);

ALTER TABLE ONLY DB_SCHEMA_NAME.genome_assembly
    ADD CONSTRAINT genome_assembly_pkey PRIMARY KEY (genome_assembly_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_entry
    ADD CONSTRAINT gn_entry_gene_id_key UNIQUE (gene_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_entry
    ADD CONSTRAINT gn_entry_pkey PRIMARY KEY (gn_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_info
    ADD CONSTRAINT gn_info_pkey PRIMARY KEY (gn_info_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_fam_map
    ADD CONSTRAINT gn_fam_map_pkey PRIMARY KEY (gn_fam_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_fam_map_prev
    ADD CONSTRAINT gn_fam_map_prev_pkey PRIMARY KEY (gn_fam_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_history
    ADD CONSTRAINT gn_history_pkey PRIMARY KEY (gn_history_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_prot_map
    ADD CONSTRAINT gn_prot_map_gn_entry_id_prot_entry_id_key UNIQUE (gn_entry_id, prot_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_prot_map
    ADD CONSTRAINT gn_prot_map_pkey PRIMARY KEY (gn_prot_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_rel
    ADD CONSTRAINT gn_rel_gn_entry_r_id_gn_entry_c_id_key UNIQUE (gn_entry_r_id, gn_entry_c_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_rel
    ADD CONSTRAINT gn_rel_pkey PRIMARY KEY (gn_ortho_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_syn_map
    ADD CONSTRAINT gn_syn_map_pkey PRIMARY KEY (gn_syn_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_syn
    ADD CONSTRAINT gn_syn_pkey PRIMARY KEY (gn_syn_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_dbref
    ADD CONSTRAINT go_dbref_go_entry_id_source_id_db_value_key UNIQUE (go_entry_id, source_id, db_value);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_dbref
    ADD CONSTRAINT go_dbref_pkey PRIMARY KEY (go_dbref_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_entry
    ADD CONSTRAINT go_entry_pkey PRIMARY KEY (go_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_pmid_map
    ADD CONSTRAINT go_pmid_map_pkey PRIMARY KEY (go_pmid_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_go_map
    ADD CONSTRAINT go_prot_map_go_entry_id_prot_entry_id_evidence_source_key UNIQUE (go_entry_id, prot_entry_id, evidence, source_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_go_map
    ADD CONSTRAINT go_prot_map_pkey PRIMARY KEY (prot_go_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_prot_map_prev
    ADD CONSTRAINT go_prot_map_prev_go_entry_id_prot_entry_id_evidence_source_key UNIQUE (go_entry_id, prot_entry_id, evidence, source);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_prot_map_prev
    ADD CONSTRAINT go_prot_map_prev_pkey PRIMARY KEY (go_prot_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_rel
    ADD CONSTRAINT go_rel_go_from_id_go_to_id_rel_type_subrel_type_key UNIQUE (go_from_id, go_to_id, rel_type, subrel_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_rel
    ADD CONSTRAINT go_rel_pkey PRIMARY KEY (go_rel_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.go_syn
    ADD CONSTRAINT go_syn_pkey PRIMARY KEY (go_syn_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_descriptor
    ADD CONSTRAINT gwas_descriptor_gwas_descriptor_name_key UNIQUE (gwas_descriptor_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_descriptor
    ADD CONSTRAINT gwas_descriptor_pkey PRIMARY KEY (gwas_descriptor_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_phenotype
    ADD CONSTRAINT gwas_phenotype_gwas_study_id_phenotype_tag_key UNIQUE (gwas_study_id, phenotype_tag);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_phenotype
    ADD CONSTRAINT gwas_phenotype_pkey PRIMARY KEY (gwas_phenotype_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_study
    ADD CONSTRAINT gwas_study_gwas_study_name_gwas_study_type_cohort_size_key UNIQUE (gwas_study_name, gwas_study_type, cohort_size);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_study
    ADD CONSTRAINT gwas_study_pkey PRIMARY KEY (gwas_study_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_variant
    ADD CONSTRAINT gwas_variant_gwas_phenotype_id_variant_change_id_key UNIQUE (gwas_phenotype_id, variant_change_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_variant
    ADD CONSTRAINT gwas_variant_pkey PRIMARY KEY (gwas_variant_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_variant_prop
    ADD CONSTRAINT gwas_variant_prop_gwas_variant_id_gwas_descriptor_id_key UNIQUE (gwas_variant_id, gwas_descriptor_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_variant_prop
    ADD CONSTRAINT gwas_variant_prop_pkey PRIMARY KEY (gwas_variant_prop_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_variant
    ADD CONSTRAINT gwas_variant_variant_change_id_gwas_phenotype_id_key UNIQUE (variant_change_id, gwas_phenotype_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_entry
    ADD CONSTRAINT ip_entry_ipr_id_key UNIQUE (ipr_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_entry
    ADD CONSTRAINT ip_entry_pkey PRIMARY KEY (ip_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_ext_db
    ADD CONSTRAINT ip_ext_db_pkey PRIMARY KEY (ip_ext_db_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_go_map
    ADD CONSTRAINT ip_go_map_pkey PRIMARY KEY (ip_go_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_pmid_map
    ADD CONSTRAINT ip_pmid_map_pkey PRIMARY KEY (ip_pmid_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_sign_prot_seq
    ADD CONSTRAINT ip_sign_prot_seq_pkey PRIMARY KEY (ip_sign_prot_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_signature
    ADD CONSTRAINT ip_signature_pkey PRIMARY KEY (ip_signature_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.lipid_entry
    ADD CONSTRAINT lipid_entry_lipid_tag_key UNIQUE (lipid_tag);

ALTER TABLE ONLY DB_SCHEMA_NAME.lipid_entry
    ADD CONSTRAINT lipid_entry_pkey PRIMARY KEY (lipid_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.lipid_hierarchy
    ADD CONSTRAINT lipid_hierarchy_pkey PRIMARY KEY (lipid_entry_id, lipid_level, lipid_level_left, lipid_level_right);

ALTER TABLE ONLY DB_SCHEMA_NAME.web_user
    ADD CONSTRAINT lly_user_global_id_key UNIQUE (global_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.web_user
    ADD CONSTRAINT lly_user_pkey PRIMARY KEY (web_user_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.web_user_stat
    ADD CONSTRAINT lly_user_stat_pkey PRIMARY KEY (web_user_stat_id);


ALTER TABLE DB_SCHEMA_NAME.molecular_component_na_map 
    ADD CONSTRAINT mol_comp_na_m_c_fk FOREIGN KEY (molecular_component_id) REFERENCES DB_SCHEMA_NAME.molecular_component(molecular_component_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.molecular_component_na_map 
    ADD CONSTRAINT mol_comp_na_m_na_fk FOREIGN KEY (nucleic_acid_seq_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_seq(nucleic_acid_seq_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.molecular_component_conj_map 
    ADD CONSTRAINT mol_comp_conj_m_c_fk FOREIGN KEY (molecular_component_id) REFERENCES DB_SCHEMA_NAME.molecular_component(molecular_component_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.molecular_component_conj_map 
    ADD CONSTRAINT mol_comp_conj_m_conj_fk FOREIGN KEY (conjugate_entry_id) REFERENCES DB_SCHEMA_NAME.conjugate_entry(conjugate_entry_id) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.meddra_entry
    ADD CONSTRAINT meddra_entry_meddra_code_key UNIQUE (meddra_code);

ALTER TABLE ONLY DB_SCHEMA_NAME.meddra_entry
    ADD CONSTRAINT meddra_entry_pkey PRIMARY KEY (meddra_entry_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.mod_pattern
    ADD CONSTRAINT mod_pattern_pkey PRIMARY KEY (mod_pattern_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.mod_pattern_pos
    ADD CONSTRAINT mod_pattern_pos_pkey PRIMARY KEY (mod_pattern_pos_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.mrna_biotype
    ADD CONSTRAINT mrna_biotype_pkey PRIMARY KEY (mrna_biotype_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_clinical_trial_map
    ADD CONSTRAINT news_clinical_trial_map_news_id_clinical_trial_id_key UNIQUE (news_id, clinical_trial_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_clinical_trial_map
    ADD CONSTRAINT news_clinical_trial_map_pkey PRIMARY KEY (news_clinical_trial_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_company_map
    ADD CONSTRAINT news_company_map_news_id_company_entry_id_key UNIQUE (news_id, company_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_company_map
    ADD CONSTRAINT news_company_map_pkey PRIMARY KEY (news_company_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_disease_map
    ADD CONSTRAINT news_disease_map_news_id_disease_entry_id_key UNIQUE (news_id, disease_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_disease_map
    ADD CONSTRAINT news_disease_map_pkey PRIMARY KEY (news_disease_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_document
    ADD CONSTRAINT news_document_news_id_document_name_document_version_key UNIQUE (news_id, document_name, document_version);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_document
    ADD CONSTRAINT news_document_pkey PRIMARY KEY (news_document_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_drug_map
    ADD CONSTRAINT news_drug_map_news_id_drug_entry_id_key UNIQUE (news_id, drug_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_drug_map
    ADD CONSTRAINT news_drug_map_pkey PRIMARY KEY (news_drug_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_gn_map
    ADD CONSTRAINT news_gn_map_news_id_gn_entry_id_key UNIQUE (news_id, gn_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_gn_map
    ADD CONSTRAINT news_gn_map_pkey PRIMARY KEY (news_gn_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news_news_map
    ADD CONSTRAINT news_news_map_pkey PRIMARY KEY (news_id, news_parent_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news
    ADD CONSTRAINT news_pkey PRIMARY KEY (news_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.news
    ADD CONSTRAINT news_uniq UNIQUE (news_title, news_release_date, source_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_match
    ADD CONSTRAINT nucleic_acid_match_pkey PRIMARY KEY (nucleic_acid_match_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_pos_match
    ADD CONSTRAINT nucleic_acid_pos_match_pkey PRIMARY KEY (nucleic_acid_pos_match_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq_pos
    ADD CONSTRAINT nucleic_acid_seq_pos_pkey PRIMARY KEY (nucleic_acid_seq_pos_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq_prop
    ADD CONSTRAINT nucleic_acid_seq_prop_nucleic_acid_seq_id_prop_name_key UNIQUE (nucleic_acid_seq_id, prop_name);


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq_prop
    ADD CONSTRAINT nucleic_acid_seq_prop_pkey PRIMARY KEY (nucleic_acid_seq_prop_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_struct
    ADD CONSTRAINT nucleic_acid_struct_pkey PRIMARY KEY (nucleic_acid_struct_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_target
    ADD CONSTRAINT nucleic_acid_target_pkey PRIMARY KEY (nucleic_acid_target_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_type
    ADD CONSTRAINT nucleic_acid_type_pkey PRIMARY KEY (nucleic_acid_type_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.ontology_entry
    ADD CONSTRAINT ontology_entry_pkey PRIMARY KEY (ontology_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.org_group_map
    ADD CONSTRAINT org_group_map_pkey PRIMARY KEY (org_group_id, web_user_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.org_group
    ADD CONSTRAINT org_group_org_group_name_key UNIQUE (org_group_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.org_group
    ADD CONSTRAINT org_group_pkey PRIMARY KEY (org_group_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site
    ADD CONSTRAINT p_xrprot_site_pkey PRIMARY KEY (p_xrprot_site_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site_rel
    ADD CONSTRAINT p_xrprot_site_rel_pkey PRIMARY KEY (p_xrprot_site_sel_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site_seq
    ADD CONSTRAINT p_xrprot_site_seq_pkey PRIMARY KEY (p_xrprot_site_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site_xray_map
    ADD CONSTRAINT p_xrprot_site_xray_map_pkey PRIMARY KEY (p_xrunsite_xray_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.patent_entry
    ADD CONSTRAINT patent_entry_pkey PRIMARY KEY (patent_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.taxon
    ADD CONSTRAINT pk_taxon PRIMARY KEY (taxon_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.taxon_tree
    ADD CONSTRAINT pk_taxon_tree PRIMARY KEY (taxon_id, tax_level, level_left, level_right);

COMMENT ON CONSTRAINT pk_taxon_tree ON DB_SCHEMA_NAME.taxon_tree IS 'A taxon is defined by a given level within a set of boundaries';

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_abstract
    ADD CONSTRAINT pmid_abstract_pkey PRIMARY KEY (pmid_abstract_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_abstract
    ADD CONSTRAINT pmid_abstract_pmid_entry_id_abstract_type_key UNIQUE (pmid_entry_id, abstract_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_anatomy_map
    ADD CONSTRAINT pmid_anatomy_map_pkey PRIMARY KEY (pmid_entry_id, anatomy_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_author_map
    ADD CONSTRAINT pmid_author_map_pkey PRIMARY KEY (pmid_author_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_author
    ADD CONSTRAINT pmid_author_pkey PRIMARY KEY (pmid_author_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_citation
    ADD CONSTRAINT pmid_citation_citation_pmid_entry_id_pmid_entry_id_key UNIQUE (citation_pmid_entry_id, pmid_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_citation
    ADD CONSTRAINT pmid_citation_pkey PRIMARY KEY (pmid_entry_id, citation_pmid_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_company_map
    ADD CONSTRAINT pmid_company_map_company_entry_id_pmid_entry_id_key UNIQUE (company_entry_id, pmid_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_company_map
    ADD CONSTRAINT pmid_company_map_pkey PRIMARY KEY (pmid_entry_id, company_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_gene
    ADD CONSTRAINT pmid_disease_gene_pkey PRIMARY KEY (pmid_disease_gene_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_gene_txt
    ADD CONSTRAINT pmid_disease_gene_txt_pkey PRIMARY KEY (pmid_disease_gene_txt_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_map
    ADD CONSTRAINT pmid_disease_map_pkey PRIMARY KEY (pmid_entry_id, disease_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_drug_map
    ADD CONSTRAINT pmid_drug_map_pkey PRIMARY KEY (pmid_entry_id, drug_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_entry
    ADD CONSTRAINT pmid_entry_pkey PRIMARY KEY (pmid_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_gene_map
    ADD CONSTRAINT pmid_gene_map_pkey PRIMARY KEY (pmid_entry_id, gn_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_onto_map
    ADD CONSTRAINT pmid_onto_map_pkey PRIMARY KEY (pmid_entry_id, ontology_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_instit
    ADD CONSTRAINT pmid_instit_pkey PRIMARY KEY (pmid_instit_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_instit
    ADD CONSTRAINT pmid_instit_unique UNIQUE (instit_hash);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_journal
    ADD CONSTRAINT pmid_journal_pkey PRIMARY KEY (pmid_journal_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_ptm_disease_map
    ADD CONSTRAINT pmid_ptm_disease_map_pkey PRIMARY KEY (pmid_entry_id, ptm_disease_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_ac
    ADD CONSTRAINT prot_ac_pkey PRIMARY KEY (prot_ac_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_ac
    ADD CONSTRAINT prot_ac_prot_entry_id_ac_key UNIQUE (prot_entry_id, ac);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_desc
    ADD CONSTRAINT prot_desc_pkey PRIMARY KEY (prot_desc_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_desc_pmid
    ADD CONSTRAINT prot_desc_pmid_pkey PRIMARY KEY (prot_desc_pmid_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_al
    ADD CONSTRAINT prot_dom_al_pkey PRIMARY KEY (prot_dom_al_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_al_seq
    ADD CONSTRAINT prot_dom_al_seq_pkey PRIMARY KEY (prot_dom_al_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom
    ADD CONSTRAINT prot_dom_pkey PRIMARY KEY (prot_dom_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom
    ADD CONSTRAINT prot_dom_prot_entry_id_domain_name_pos_start_pos_end_domain_key UNIQUE (prot_entry_id, domain_name, pos_start, pos_end, domain_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_seq
    ADD CONSTRAINT prot_dom_seq_pkey PRIMARY KEY (prot_dom_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_seq
    ADD CONSTRAINT prot_dom_seq_prot_dom_id_position_key UNIQUE (prot_dom_id, "position");

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_entry
    ADD CONSTRAINT prot_entry_pkey PRIMARY KEY (prot_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_extdb_map
    ADD CONSTRAINT prot_extdb_map_pkey PRIMARY KEY (prot_extdb_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_extdb_map
    ADD CONSTRAINT prot_extdb_map_prot_seq_id_prot_extdb_value_prot_entry_id_p_key UNIQUE (prot_seq_id, prot_extdb_value, prot_entry_id, prot_extdb_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_extdb
    ADD CONSTRAINT prot_extdb_pkey PRIMARY KEY (prot_extdbid);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat
    ADD CONSTRAINT prot_feat_pkey PRIMARY KEY (prot_feat_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_pmid
    ADD CONSTRAINT prot_feat_pmid_pkey PRIMARY KEY (prot_feat_pmid_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_pmid
    ADD CONSTRAINT prot_feat_pmid_prot_feat_id_pmid_entry_id_eco_entry_id_key UNIQUE (prot_feat_id, pmid_entry_id, eco_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_seq
    ADD CONSTRAINT prot_feat_seq_pkey PRIMARY KEY (prot_feat_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_seq
    ADD CONSTRAINT prot_feat_seq_prot_feat_id_position_key UNIQUE (prot_feat_id, "position");

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_type
    ADD CONSTRAINT prot_feat_type_feat_name_key UNIQUE (feat_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_type
    ADD CONSTRAINT prot_feat_type_pkey PRIMARY KEY (prot_feat_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_name_map
    ADD CONSTRAINT prot_name_map_pkey PRIMARY KEY (prot_name_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_name
    ADD CONSTRAINT prot_name_pkey PRIMARY KEY (prot_name_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_name
    ADD CONSTRAINT prot_name_protein_name_ec_number_key UNIQUE (protein_name, ec_number);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_pmid_map
    ADD CONSTRAINT prot_pmid_map_pkey PRIMARY KEY (prot_pmid_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_al
    ADD CONSTRAINT prot_seq_al_pkey PRIMARY KEY (prot_seq_al_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_al_seq
    ADD CONSTRAINT prot_seq_al_seq_pkey PRIMARY KEY (prot_seq_al_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq
    ADD CONSTRAINT prot_seq_pkey PRIMARY KEY (prot_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_pos
    ADD CONSTRAINT prot_seq_pos_pkey PRIMARY KEY (prot_seq_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_abbreviations
    ADD CONSTRAINT ptm_abbreviations_pkey PRIMARY KEY (ptm_abv_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_abbreviations
    ADD CONSTRAINT ptm_abbreviations_ptm_abv_name_key UNIQUE (ptm_abv_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_disease
    ADD CONSTRAINT ptm_disease_pkey PRIMARY KEY (ptm_disease_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_seq
    ADD CONSTRAINT ptm_seq_pkey PRIMARY KEY (ptm_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_syn
    ADD CONSTRAINT ptm_syn_pkey PRIMARY KEY (ptm_syn_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_syn
    ADD CONSTRAINT ptm_syn_ptm_syn_name_key UNIQUE (ptm_syn_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_type
    ADD CONSTRAINT ptm_type_pkey PRIMARY KEY (ptm_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_type
    ADD CONSTRAINT ptm_type_ptm_type_name_key UNIQUE (ptm_type_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_var
    ADD CONSTRAINT ptm_var_pkey PRIMARY KEY (ptm_var_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_entry
    ADD CONSTRAINT pw_entry_pkey PRIMARY KEY (pw_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_entry
    ADD CONSTRAINT pw_entry_reac_id_key UNIQUE (reac_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_gn_map
    ADD CONSTRAINT pw_gn_map_gn_entry_id_pw_entry_id_key UNIQUE (gn_entry_id, pw_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_gn_map
    ADD CONSTRAINT pw_gn_map_pkey PRIMARY KEY (pw_gn_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_gn_map
    ADD CONSTRAINT pw_gn_map_pw_entry_id_gn_entry_id_key UNIQUE (pw_entry_id, gn_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_hierarchy
    ADD CONSTRAINT pw_hierarchy_pkey PRIMARY KEY (pw_entry_id, pw_level, level_left, level_right);

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_rel
    ADD CONSTRAINT pw_rel_pkey PRIMARY KEY (pw_rel_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_rel
    ADD CONSTRAINT pw_rel_pw_from_id_pw_to_id_key UNIQUE (pw_from_id, pw_to_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_gene
    ADD CONSTRAINT rna_gene_pkey PRIMARY KEY (rna_gene_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_gene_stat
    ADD CONSTRAINT rna_gene_stat_pkey PRIMARY KEY (rna_gene_stat_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_sample
    ADD CONSTRAINT rna_sample_pkey PRIMARY KEY (rna_sample_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_sample
    ADD CONSTRAINT rna_sample_sample_id_tissue_id_key UNIQUE (sample_id, rna_tissue_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_sample
    ADD CONSTRAINT rna_sample_tissue_id_sample_id_key UNIQUE (rna_tissue_id, sample_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_source
    ADD CONSTRAINT rna_source_pkey PRIMARY KEY (rna_source_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_tissue
    ADD CONSTRAINT rna_tissue_organ_name_tissue_name_key UNIQUE (organ_name, tissue_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_tissue
    ADD CONSTRAINT rna_tissue_pkey PRIMARY KEY (rna_tissue_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_transcript
    ADD CONSTRAINT rna_transcript_rna_sample_id_transcript_id_key UNIQUE (rna_sample_id, transcript_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_transcript_stat
    ADD CONSTRAINT rna_transcript_stat_pkey PRIMARY KEY (rna_transcript_stat_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.seq_btype
    ADD CONSTRAINT seq_btype_pkey PRIMARY KEY (seq_btype_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.seq_btype
    ADD CONSTRAINT seq_btype_seq_type_key UNIQUE (seq_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_config
    ADD CONSTRAINT sharepoint_config_pkey PRIMARY KEY (sharepoint_config_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_author_map
    ADD CONSTRAINT sharepoint_doc_author_map_pkey PRIMARY KEY (sharepoint_doc_clinical_trial_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_author_map
    ADD CONSTRAINT sharepoint_doc_author_map_sharepoint_document_id_web_user_i_key UNIQUE (sharepoint_document_id, web_user_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_clinical_trial_map
    ADD CONSTRAINT sharepoint_doc_clinical_trial_map_pkey PRIMARY KEY (sharepoint_doc_clinical_trial_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_clinical_trial_map
    ADD CONSTRAINT sharepoint_doc_clinical_trial_sharepoint_document_id_clinic_key UNIQUE (sharepoint_document_id, clinical_trial_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_company_map
    ADD CONSTRAINT sharepoint_doc_company_map_pkey PRIMARY KEY (sharepoint_doc_company_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_company_map
    ADD CONSTRAINT sharepoint_doc_company_map_sharepoint_document_id_company_e_key UNIQUE (sharepoint_document_id, company_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_disease_map
    ADD CONSTRAINT sharepoint_doc_disease_map_pkey PRIMARY KEY (sharepoint_doc_company_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_disease_map
    ADD CONSTRAINT sharepoint_doc_disease_map_sharepoint_document_id_disease_e_key UNIQUE (sharepoint_document_id, disease_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_drug_map
    ADD CONSTRAINT sharepoint_doc_drug_map_pkey PRIMARY KEY (sharepoint_doc_drug_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_drug_map
    ADD CONSTRAINT sharepoint_doc_drug_map_sharepoint_document_id_drug_entry_i_key UNIQUE (sharepoint_document_id, drug_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_gn_map
    ADD CONSTRAINT sharepoint_doc_gn_map_pkey PRIMARY KEY (sharepoint_doc_gn_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_gn_map
    ADD CONSTRAINT sharepoint_doc_gn_map_sharepoint_document_id_gn_entry_id_key UNIQUE (sharepoint_document_id, gn_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_document
    ADD CONSTRAINT sharepoint_document_pkey PRIMARY KEY (sharepoint_document_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_drug
    ADD CONSTRAINT side_effect_drug_pkey PRIMARY KEY (side_effect_drug_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_drug_reaction
    ADD CONSTRAINT side_effect_drug_reaction_pkey PRIMARY KEY (side_effect_drug_reaction_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_report
    ADD CONSTRAINT side_effect_report_pkey PRIMARY KEY (side_effect_report_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_seriousness
    ADD CONSTRAINT side_effect_seriousness_pkey PRIMARY KEY (side_effect_seriousness_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_counterion
    ADD CONSTRAINT sm_counterion_pkey PRIMARY KEY (sm_counterion_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_description
    ADD CONSTRAINT sm_description_pkey PRIMARY KEY (sm_description_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_description
    ADD CONSTRAINT sm_description_sm_entry_id_source_id_description_type_key UNIQUE (sm_entry_id, source_id, description_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_entry
    ADD CONSTRAINT sm_entry_pkey PRIMARY KEY (sm_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_molecule
    ADD CONSTRAINT sm_molecule_pkey PRIMARY KEY (sm_molecule_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_patent_map
    ADD CONSTRAINT sm_patent_map_pkey PRIMARY KEY (sm_patent_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_publi_map
    ADD CONSTRAINT sm_publi_map_pkey PRIMARY KEY (sm_publi_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_publi_map
    ADD CONSTRAINT sm_publi_map_sm_entry_id_pmid_entry_id_sub_type_source_id_d_key UNIQUE (sm_entry_id, pmid_entry_id, sub_type, source_id, disease_entry_id);


ALTER TABLE ONLY DB_SCHEMA_NAME.sm_scaffold
    ADD CONSTRAINT sm_scaffold_pkey PRIMARY KEY (sm_scaffold_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_source
    ADD CONSTRAINT sm_source_pkey PRIMARY KEY (sm_source_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_source
    ADD CONSTRAINT sm_source_sm_entry_id_source_id_sm_name_key UNIQUE (sm_entry_id, source_id, sm_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.so_entry
    ADD CONSTRAINT so_entry_pkey PRIMARY KEY (so_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.source
    ADD CONSTRAINT source_pkey PRIMARY KEY (source_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.source
    ADD CONSTRAINT source_source_name_version_user_name_key UNIQUE (source_name, version, user_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.tr_protseq_al
    ADD CONSTRAINT tr_protseq_al_pkey PRIMARY KEY (tr_protseq_al_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.tr_protseq_pos_al
    ADD CONSTRAINT tr_protseq_pos_al_pkey PRIMARY KEY (tr_protseq_pos_al_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al
    ADD CONSTRAINT transcript_al_pkey PRIMARY KEY (transcript_al_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al_pos
    ADD CONSTRAINT transcript_al_pos_pkey PRIMARY KEY (transcript_al_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al_pos
    ADD CONSTRAINT transcript_al_pos_transcript_al_id_al_pos_key UNIQUE (transcript_al_id, al_pos);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al
    ADD CONSTRAINT transcript_al_transcript_ref_id_transcript_comp_id_key UNIQUE (transcript_ref_id, transcript_comp_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript
    ADD CONSTRAINT transcript_pkey PRIMARY KEY (transcript_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_pos
    ADD CONSTRAINT transcript_pos_pkey PRIMARY KEY (transcript_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_pos
    ADD CONSTRAINT transcript_pos_transcript_id_seq_pos_key UNIQUE (transcript_id, seq_pos);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_pos_type
    ADD CONSTRAINT transcript_pos_type_pkey PRIMARY KEY (transcript_pos_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_pos_type
    ADD CONSTRAINT transcript_pos_type_transcript_pos_type_key UNIQUE (transcript_pos_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript
    ADD CONSTRAINT transcript_transcript_name_transcript_version_gene_seq_id_c_key UNIQUE (transcript_name, transcript_version, gene_seq_id, chr_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.translation_tbl
    ADD CONSTRAINT translation_tbl_pkey PRIMARY KEY (translation_tbl_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_allele
    ADD CONSTRAINT variant_allele_pkey PRIMARY KEY (variant_allele_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_change
    ADD CONSTRAINT variant_change_pkey PRIMARY KEY (variant_change_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_change
    ADD CONSTRAINT variant_change_variant_entry_id_alt_all_key UNIQUE (variant_position_id, alt_all);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_clinv_assert_map
    ADD CONSTRAINT variant_clinv_assert_map_pkey PRIMARY KEY (variant_clinv_assert_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_entry
    ADD CONSTRAINT variant_entry_pkey PRIMARY KEY (variant_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_freq_study
    ADD CONSTRAINT variant_freq_study_pkey PRIMARY KEY (variant_freq_study_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_freq_study
    ADD CONSTRAINT variant_freq_study_variant_freq_study_name_key UNIQUE (variant_freq_study_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_frequency
    ADD CONSTRAINT variant_frequency_pkey PRIMARY KEY (variant_frequency_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_frequency
    ADD CONSTRAINT variant_frequency_uk UNIQUE (variant_change_id, variant_freq_study_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_info
    ADD CONSTRAINT variant_info_pkey PRIMARY KEY (variant_info_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_info
    ADD CONSTRAINT variant_info_uq UNIQUE (variant_entry_id, source_id,source_entry,info_type);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_pmid_map
    ADD CONSTRAINT variant_pmid_map_pkey PRIMARY KEY (variant_pmid_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_pmid_map
    ADD CONSTRAINT variant_pmid_map_variant_entry_id_pmid_entry_id_key UNIQUE (variant_entry_id, pmid_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_position
    ADD CONSTRAINT variant_position_pkey PRIMARY KEY (variant_position_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_position
    ADD CONSTRAINT variant_position_uk1 UNIQUE (variant_entry_id, ref_all, chr_seq_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_prot_allele
    ADD CONSTRAINT variant_prot_allele_pkey PRIMARY KEY (variant_prot_allele_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_protein_map
    ADD CONSTRAINT variant_protein_map_pkey PRIMARY KEY (variant_protein_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_transcript_map
    ADD CONSTRAINT variant_transcript_map_pkey PRIMARY KEY (variant_transcript_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_transcript_map
    ADD CONSTRAINT variant_transcript_map_variant_change_id_transcript_id_tran_key UNIQUE (variant_change_id, transcript_id, transcript_pos_id, so_entry_id, tr_ref_all, tr_alt_all);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_type
    ADD CONSTRAINT variant_type_pkey PRIMARY KEY (variant_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_type
    ADD CONSTRAINT variant_type_variant_name_key UNIQUE (variant_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.web_job_document
    ADD CONSTRAINT web_job_document_pkey PRIMARY KEY (web_job_document_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.web_job_document
    ADD CONSTRAINT web_job_document_web_job_id_document_hash_key UNIQUE (web_job_id, document_hash);

ALTER TABLE ONLY DB_SCHEMA_NAME.web_job
    ADD CONSTRAINT web_job_pkey PRIMARY KEY (web_job_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_atom
    ADD CONSTRAINT xr_atom_pkey PRIMARY KEY (xr_atom_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_bond
    ADD CONSTRAINT xr_bond_pkey PRIMARY KEY (xr_bond_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_cav
    ADD CONSTRAINT xr_cav_pkey PRIMARY KEY (xr_cav_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_lig_map
    ADD CONSTRAINT xr_ch_lig_map_pkey PRIMARY KEY (xr_ch_lig_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_prot_map
    ADD CONSTRAINT xr_ch_prot_map_pkey PRIMARY KEY (xr_ch_prot_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_prot_pos
    ADD CONSTRAINT xr_ch_prot_pos_pkey PRIMARY KEY (xr_ch_prot_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_udom_map
    ADD CONSTRAINT xr_ch_udom_map_pkey PRIMARY KEY (xr_ch_dom_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_chain
    ADD CONSTRAINT xr_chain_pkey PRIMARY KEY (xr_chain_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_element
    ADD CONSTRAINT xr_element_name_key UNIQUE (name);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_element
    ADD CONSTRAINT xr_element_pkey PRIMARY KEY (xr_element_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_element
    ADD CONSTRAINT xr_element_symbol_key UNIQUE (symbol);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_entry
    ADD CONSTRAINT xr_entry_full_common_name_key UNIQUE (full_common_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_entry
    ADD CONSTRAINT xr_entry_pkey PRIMARY KEY (xr_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_entry_pmid
    ADD CONSTRAINT xr_entry_pmid_pkey PRIMARY KEY (xr_entry_pmid_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_inter_res
    ADD CONSTRAINT xr_inter_res_pkey PRIMARY KEY (xr_inter_res_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_inter_type
    ADD CONSTRAINT xr_inter_type_interaction_name_key UNIQUE (interaction_name);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_inter_type
    ADD CONSTRAINT xr_inter_type_pkey PRIMARY KEY (xr_inter_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_jobs
    ADD CONSTRAINT xr_jobs_pkey PRIMARY KEY (xr_job_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_lig
    ADD CONSTRAINT xr_lig_pkey PRIMARY KEY (xr_lig_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ppi
    ADD CONSTRAINT xr_ppi_pkey PRIMARY KEY (xr_ppi_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ppi
    ADD CONSTRAINT xr_ppi_xr_chain_r_id_xr_chain_c_id_key UNIQUE (xr_chain_r_id, xr_chain_c_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_dom_cov
    ADD CONSTRAINT xr_prot_dom_cov_pkey PRIMARY KEY (xr_prot_dom_cov_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_int_stat
    ADD CONSTRAINT xr_prot_int_stat_pkey PRIMARY KEY (xr_prot_int_stat_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_stat
    ADD CONSTRAINT xr_prot_stat_pkey PRIMARY KEY (xr_prot_stat_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_res
    ADD CONSTRAINT xr_res_pkey PRIMARY KEY (xr_res_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_site
    ADD CONSTRAINT xr_site_pkey PRIMARY KEY (xr_site_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_site_res
    ADD CONSTRAINT xr_site_res_pkey PRIMARY KEY (xr_site_res_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_status
    ADD CONSTRAINT xr_status_pkey PRIMARY KEY (xr_status_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_atom
    ADD CONSTRAINT xr_tpl_atom_pkey PRIMARY KEY (xr_tpl_atom_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_bond
    ADD CONSTRAINT xr_tpl_bond_pkey PRIMARY KEY (xr_tpl_bond_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_bond
    ADD CONSTRAINT xr_tpl_bond_xr_tpl_atom_id_1_xr_tpl_atom_id_2_key UNIQUE (xr_tpl_atom_id_1, xr_tpl_atom_id_2);

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_res
    ADD CONSTRAINT xr_tpl_res_pkey PRIMARY KEY (xr_tpl_res_id);

CREATE INDEX a_p_idx ON DB_SCHEMA_NAME.assay_pmid USING btree (assay_entry_id);

CREATE INDEX aaname_index1 ON DB_SCHEMA_NAME.aaname USING btree (resname);

CREATE INDEX aaname_index2 ON DB_SCHEMA_NAME.aaname USING btree (lettername);

CREATE INDEX act_sm_map_act ON DB_SCHEMA_NAME.molecular_component_sm_map USING btree (sm_entry_id);

CREATE INDEX act_sm_map_sm ON DB_SCHEMA_NAME.molecular_component_sm_map USING btree (molecular_component_id);

CREATE UNIQUE INDEX molecular_component_hash_uk ON DB_SCHEMA_NAME.molecular_component USING btree (molecular_component_hash);

CREATE INDEX molecular_component_s_hash_uk ON DB_SCHEMA_NAME.molecular_component USING btree (molecular_component_structure_hash);

CREATE INDEX activity_entry_active ON DB_SCHEMA_NAME.activity_entry USING btree (molecular_entity_id);

CREATE INDEX activity_entry_assay ON DB_SCHEMA_NAME.activity_entry USING btree (assay_entry_id);

CREATE INDEX activity_entry_source ON DB_SCHEMA_NAME.activity_entry USING btree (source_id);

CREATE INDEX anatomy_entry_id1 ON DB_SCHEMA_NAME.anatomy_entry USING btree (anatomy_name);

CREATE INDEX anatomy_syn_name ON DB_SCHEMA_NAME.anatomy_syn USING btree (syn_value);

CREATE UNIQUE INDEX anatomy_syn_uk ON DB_SCHEMA_NAME.anatomy_syn USING btree (anatomy_entry_id, syn_type, syn_value);

CREATE INDEX assau_tissue_anatomy ON DB_SCHEMA_NAME.assay_tissue USING btree (anatomy_entry_id);

CREATE UNIQUE INDEX assay_cell_id1 ON DB_SCHEMA_NAME.assay_cell USING btree (chembl_id);

CREATE INDEX assay_cell_id2 ON DB_SCHEMA_NAME.assay_cell USING btree (taxon_id);

CREATE INDEX assay_cell_id3 ON DB_SCHEMA_NAME.assay_cell USING btree (cell_entry_id);

CREATE UNIQUE INDEX assay_cell_name ON DB_SCHEMA_NAME.assay_cell USING btree (cell_name, taxon_id) WHERE (taxon_id IS NOT NULL);

CREATE UNIQUE INDEX assay_cell_name2 ON DB_SCHEMA_NAME.assay_cell USING btree (cell_name, taxon_id) WHERE (taxon_id IS NULL);

CREATE INDEX assay_entry_anatomy ON DB_SCHEMA_NAME.assay_entry USING btree (assay_tissue_id);

CREATE INDEX assay_entry_assay_name_idx ON DB_SCHEMA_NAME.assay_entry USING btree (assay_name);

CREATE INDEX assay_entry_assay_target ON DB_SCHEMA_NAME.assay_entry USING btree (assay_target_id);

CREATE INDEX assay_entry_cell ON DB_SCHEMA_NAME.assay_entry USING btree (assay_cell_id);

CREATE INDEX assay_entry_source ON DB_SCHEMA_NAME.assay_entry USING btree (source_id);

CREATE INDEX assay_entry_taxon ON DB_SCHEMA_NAME.assay_entry USING btree (taxon_id);

CREATE UNIQUE INDEX assay_entry_uk1 ON DB_SCHEMA_NAME.assay_entry USING btree (assay_name, source_id);

CREATE INDEX assay_entry_variant ON DB_SCHEMA_NAME.assay_entry USING btree (assay_variant_id);

CREATE UNIQUE INDEX assay_genetic_uk1 ON DB_SCHEMA_NAME.assay_genetic USING btree (taxon_id, genetic_description, accession);

CREATE INDEX assay_pmid_idx ON DB_SCHEMA_NAME.assay_pmid USING btree (pmid_entry_id, assay_entry_id);

CREATE UNIQUE INDEX assay_pmid_uk ON DB_SCHEMA_NAME.assay_pmid USING btree (assay_entry_id, pmid_entry_id);

CREATE INDEX assay_protein_protseq ON DB_SCHEMA_NAME.assay_protein USING btree (prot_seq_id);

CREATE UNIQUE INDEX assay_protein_uk ON DB_SCHEMA_NAME.assay_protein USING btree (accession, sequence_md5sum);

CREATE UNIQUE INDEX assay_target_name ON DB_SCHEMA_NAME.assay_target USING btree (assay_target_name);

CREATE UNIQUE INDEX assay_target_type_uk ON DB_SCHEMA_NAME.assay_target_type USING btree (assay_target_type_name);

CREATE UNIQUE INDEX assay_tissue_uk ON DB_SCHEMA_NAME.assay_tissue USING btree (assay_tissue_name);

CREATE UNIQUE INDEX assay_variant_pos_idx ON DB_SCHEMA_NAME.assay_variant_pos USING btree (assay_variant_id, prot_seq_pos_id);

CREATE INDEX assay_variant_pos_idx2 ON DB_SCHEMA_NAME.assay_variant_pos USING btree (variant_protein_id);

CREATE UNIQUE INDEX assay_variant_uk ON DB_SCHEMA_NAME.assay_variant USING btree (mutation_list, prot_seq_id) WHERE (prot_seq_id IS NOT NULL);

CREATE UNIQUE INDEX assay_variant_uk2 ON DB_SCHEMA_NAME.assay_variant USING btree (mutation_list, prot_seq_id) WHERE (prot_seq_id IS NULL);

CREATE UNIQUE INDEX assay_variant_uk3 ON DB_SCHEMA_NAME.assay_variant USING btree (mutation_list, ac);

CREATE INDEX ATC_code_uk ON DB_SCHEMA_NAME.ATC_entry USING btree (ATC_code);

CREATE INDEX ATC_hierarchy_pk ON DB_SCHEMA_NAME.ATC_hierarchy USING btree (ATC_entry_id);

CREATE INDEX ATC_hierarchy_bd ON DB_SCHEMA_NAME.ATC_hierarchy USING btree (ATC_level_left, ATC_level_right);

CREATE INDEX br_job_his_job ON DB_SCHEMA_NAME.biorels_job_history USING btree (br_timestamp_id);

CREATE INDEX br_job_his_time ON DB_SCHEMA_NAME.biorels_job_history USING btree (run_date);

CREATE UNIQUE INDEX bioassay_onto_entry_index1 ON DB_SCHEMA_NAME.bioassay_onto_entry USING btree (bioassay_tag_id);

CREATE UNIQUE INDEX bioassay_onto_extdb_index1 ON DB_SCHEMA_NAME.bioassay_onto_extdb USING btree (bioassay_onto_entry_id, source_id, bioassay_onto_extdb_name);

CREATE INDEX bioassay_onto_hierarchy_index1 ON DB_SCHEMA_NAME.bioassay_onto_hierarchy USING btree (bioassay_onto_entry_id);

CREATE INDEX bioassay_onto_hierarchy_index2 ON DB_SCHEMA_NAME.bioassay_onto_hierarchy USING btree (bioassay_onto_level_left, bioassay_onto_level_right);

CREATE UNIQUE INDEX cell_disease_id ON DB_SCHEMA_NAME.cell_disease USING btree (cell_entry_id, disease_entry_id, source_id);

CREATE INDEX cell_entry_index1 ON DB_SCHEMA_NAME.cell_entry USING btree (cell_acc);

CREATE INDEX cell_entry_index2 ON DB_SCHEMA_NAME.cell_entry USING btree (cell_name);

CREATE INDEX cell_entry_index3 ON DB_SCHEMA_NAME.cell_entry USING btree (cell_type);

CREATE UNIQUE INDEX cell_patent_map_index1 ON DB_SCHEMA_NAME.cell_patent_map USING btree (cell_entry_id, patent_entry_id);

CREATE UNIQUE INDEX cell_pmid_map_id ON DB_SCHEMA_NAME.cell_pmid_map USING btree (cell_entry_id, pmid_entry_id, source_id);

CREATE UNIQUE INDEX cell_syn_uk ON DB_SCHEMA_NAME.cell_syn USING btree (cell_entry_id, cell_syn_name, source_id);

CREATE UNIQUE INDEX cell_taxon_map_id ON DB_SCHEMA_NAME.cell_taxon_map USING btree (cell_entry_id, taxon_id, source_id);

CREATE UNIQUE INDEX chr_seq_pos_index1 ON DB_SCHEMA_NAME.chr_seq_pos USING btree (chr_seq_id, chr_pos);

CREATE INDEX chr_seq_pos_index2 ON DB_SCHEMA_NAME.chr_seq_pos USING btree (chr_seq_id);

COMMENT ON INDEX DB_SCHEMA_NAME.chromosome_taxon_id_chr_num_key IS 'A chromosome with a given name exists only once for a given organism';

CREATE UNIQUE INDEX clinical_trial_arm_idx ON DB_SCHEMA_NAME.clinical_trial_arm USING btree (clinical_trial_id, arm_label, arm_type,arm_description) WHERE (arm_description IS NOT NULL);

CREATE UNIQUE INDEX clinical_trial_arm_idx2 ON DB_SCHEMA_NAME.clinical_trial_arm USING btree (clinical_trial_id, arm_label, arm_type,arm_description) WHERE (arm_description IS  NULL);

CREATE UNIQUE INDEX clinical_trial_arm_intervention_map_idx ON DB_SCHEMA_NAME.clinical_trial_arm_intervention_map USING btree (clinical_trial_id, clinical_trial_arm_id, clinical_trial_intervention_id);

CREATE UNIQUE INDEX clinical_trial_condition_idx ON DB_SCHEMA_NAME.clinical_trial_condition USING btree (clinical_trial_id, condition_name) WHERE disease_entry_id IS NULL;

CREATE UNIQUE INDEX clinical_trial_condition_idx2 ON DB_SCHEMA_NAME.clinical_trial_condition USING btree (clinical_trial_id, condition_name,disease_entry_id) WHERE disease_entry_id IS NULL;

CREATE UNIQUE INDEX clinical_trial_intervention_idx ON DB_SCHEMA_NAME.clinical_trial_intervention USING btree (clinical_trial_id, intervention_type, intervention_name,intervention_description);

CREATE UNIQUE INDEX clinical_trial_intervention_drug_map_idx ON DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map USING btree (clinical_trial_intervention_id, drug_entry_id);

CREATE UNIQUE INDEX clinical_trial_index1 ON DB_SCHEMA_NAME.clinical_trial USING btree (trial_id);

COMMENT ON INDEX DB_SCHEMA_NAME.clinical_trial_index1 IS 'A clinical trial is uniquely identified by its identifier';

CREATE INDEX clinical_variant_idx ON DB_SCHEMA_NAME.clinical_variant_entry USING btree (clinvar_variation_id);

CREATE INDEX clinvar_entry_type ON DB_SCHEMA_NAME.clinical_variant_entry(clinical_variant_Type_id);

CREATE INDEX clinvar_entry_status ON DB_SCHEMA_NAME.clinical_variant_entry(clinical_variant_review_status);

CREATE INDEX clinvar_disease_map_idx ON DB_SCHEMA_NAME.clinical_variant_disease_map USING btree (disease_entry_id);

CREATE INDEX clinvar_gn_map_idx ON DB_SCHEMA_NAME.clinical_variant_gn_map USING btree (gn_entry_id);

CREATE INDEX clinvar_map_idx ON DB_SCHEMA_NAME.clinical_variant_map USING btree (variant_entry_id);

CREATE INDEX clinvar_pmid_map_idx ON DB_SCHEMA_NAME.clinical_variant_pmid_map USING btree (pmid_entry_id);

CREATE INDEX clinvar_sub_entry ON DB_SCHEMA_NAME.clinical_variant_submission USING btree (clinvar_entry_id);

CREATE INDEX clinvar_sub_sign ON DB_SCHEMA_NAME.clinical_variant_submission USING btree (clin_sign_id);

CREATE INDEX clinvar_sub_entry_r ON DB_SCHEMA_NAME.clinical_variant_submission USING btree (clinical_variant_Review_status);

CREATE INDEX clinvar_type_so ON DB_SCHEMA_NAME.clinical_variant_type USING btree (so_entry_id);

CREATE UNIQUE INDEX codon_trans_name ON DB_SCHEMA_NAME.codon USING btree (translation_tbl_id, codon_name);

CREATE UNIQUE INDEX disease_anatomy_uk1 ON DB_SCHEMA_NAME.disease_anatomy_map USING btree (disease_entry_id, anatomy_entry_id, source_id);

CREATE UNIQUE INDEX disease_entry_index1 ON DB_SCHEMA_NAME.disease_entry USING btree (disease_tag);

CREATE UNIQUE INDEX disease_extdb_index1 ON DB_SCHEMA_NAME.disease_extdb USING btree (disease_entry_id, source_id, disease_extdb);

COMMENT ON INDEX DB_SCHEMA_NAME.disease_extdb_index1 IS 'a source can only provide one identifier for a given disease';

CREATE UNIQUE INDEX disease_gene_acc_index1 ON DB_SCHEMA_NAME.disease_gene_acc USING btree (disease_entry_id, gn_entry_id);

CREATE INDEX disease_gene_acc_index2 ON DB_SCHEMA_NAME.disease_gene_acc USING btree (gn_entry_id);

CREATE INDEX disease_hierarchy_index1 ON DB_SCHEMA_NAME.disease_hierarchy USING btree (disease_entry_id);

CREATE INDEX disease_hierarchy_index2 ON DB_SCHEMA_NAME.disease_hierarchy USING btree (disease_level_left, disease_level_right);

CREATE UNIQUE INDEX disease_info_index1 ON DB_SCHEMA_NAME.disease_info USING btree (disease_entry_id, source_id, info_type);

COMMENT ON INDEX DB_SCHEMA_NAME.disease_info_index1 IS 'A section of a given source for a given disease must be unique';

CREATE UNIQUE INDEX disease_pmid_index1 ON DB_SCHEMA_NAME.disease_pmid USING btree (disease_entry_id, pmid_entry_id);

CREATE INDEX disease_pmid_index2 ON DB_SCHEMA_NAME.disease_pmid USING btree (pmid_entry_id);

CREATE UNIQUE INDEX disease_syn_index1 ON DB_SCHEMA_NAME.disease_syn USING btree (disease_entry_id, syn_type, syn_value);

CREATE UNIQUE INDEX drug_disease_index1 ON DB_SCHEMA_NAME.drug_disease USING btree (drug_entry_id, disease_entry_id, gn_entry_id);

COMMENT ON INDEX DB_SCHEMA_NAME.drug_disease_index1 IS 'A drug indication is defined by a drug for a given disease for which the target is eventually known';

CREATE INDEX drug_atc_map_index1 ON DB_SCHEMA_NAME.drug_atc_map USING btree (drug_entry_id);

CREATE INDEX drug_atc_map_index2 ON DB_SCHEMA_NAME.drug_atc_map USING btree (atc_entry_id);

CREATE UNIQUE INDEX drug_atc_map_index3 ON DB_SCHEMA_NAME.drug_atc_map USING btree (drug_entry_id, atc_entry_id);

CREATE INDEX drug_disease_index2 ON DB_SCHEMA_NAME.drug_disease USING btree (gn_entry_id);

CREATE INDEX drug_disease_index3 ON DB_SCHEMA_NAME.drug_disease USING btree (disease_entry_id);

CREATE INDEX drug_entry_index1 ON DB_SCHEMA_NAME.drug_entry USING btree (drug_primary_name);

CREATE UNIQUE INDEX drug_entry_index2 ON DB_SCHEMA_NAME.drug_entry USING btree (drugbank_id);

CREATE UNIQUE INDEX drug_entry_index3 ON DB_SCHEMA_NAME.drug_entry USING btree (chembl_id);

CREATE INDEX drug_extdb_s ON DB_SCHEMA_NAME.drug_extdb(source_id);

CREATE INDEX drug_extdb_s_o ON DB_SCHEMA_NAME.drug_extdb(source_origin_id);

CREATE INDEX drug_extdb_v ON DB_SCHEMA_NAME.drug_extdb(drug_extdb_value);

CREATE INDEX drug_extdb_d ON DB_SCHEMA_NAME.DRug_extdb(drug_entry_id);

CREATE UNIQUE INDEX drug_extdb_u ON DB_SCHEMA_NAME.drug_extdb (drug_entry_id,source_id,source_origin_id,drug_extdb_value);

CREATE INDEX drug_name_index1 ON DB_SCHEMA_NAME.drug_name USING btree (drug_entry_id);

CREATE INDEX drug_name_index4 ON DB_SCHEMA_NAME.drug_name USING btree (source_id);

CREATE UNIQUE INDEX drug_name_index2 ON DB_SCHEMA_NAME.drug_name USING btree (drug_name, drug_entry_id,source_id);

COMMENT ON INDEX DB_SCHEMA_NAME.drug_name_index2 IS 'A name must be defined once for a given drug';

CREATE INDEX drug_name_index3 ON DB_SCHEMA_NAME.drug_name USING btree (lower((drug_name)::text));

CREATE INDEX drug_mol_entity_map_dr ON DB_SCHEMA_NAME.drug_mol_entity_map(drug_entry_id);

CREATE INDEX drug_mol_entity_map_sm ON DB_SCHEMA_NAME.drug_mol_entity_map(molecular_entity_id);

CREATE INDEX drug_mol_entity_map_s ON DB_SCHEMA_NAME.drug_mol_entity_map(source_id);

CREATE INDEX drug_type_s ON DB_SCHEMA_NAME.drug_type(drug_type_id);

CREATE UNIQUE INDEX drug_type_n_uk ON DB_SCHEMA_NAME.drug_type(drug_type_name);

CREATE INDEX drug_type_map_index1 ON DB_SCHEMA_NAME.drug_type_map USING btree (drug_entry_id);

CREATE INDEX drug_type_map_index2 ON DB_SCHEMA_NAME.drug_type_map USING btree (drug_type_id);

CREATE UNIQUE INDEX drug_type_map_index3 ON DB_SCHEMA_NAME.drug_type_map USING btree (drug_type_id, drug_entry_id);

CREATE INDEX dr_de_dr ON DB_SCHEMA_NAME.drug_description(drug_entry_id);

CREATE INDEX dr_de_s ON DB_SCHEMA_NAME.drug_description (source_id);
                                                             
CREATE UNIQUE INDEX eco_entry_index1 ON DB_SCHEMA_NAME.eco_entry USING btree (eco_id);

CREATE INDEX eco_entry_index2 ON DB_SCHEMA_NAME.eco_entry USING btree (eco_name);

CREATE INDEX eco_hierarchy_index1 ON DB_SCHEMA_NAME.eco_hierarchy USING btree (eco_entry_id);

CREATE INDEX eco_hierarchy_index2 ON DB_SCHEMA_NAME.eco_hierarchy USING btree (level_left, level_right);

CREATE UNIQUE INDEX eco_rel_index1 ON DB_SCHEMA_NAME.eco_rel USING btree (eco_entry_ref, eco_entry_comp, rel_type);

CREATE UNIQUE INDEX efo_entry_index1 ON DB_SCHEMA_NAME.efo_entry USING btree (efo_tag_id);

CREATE UNIQUE INDEX efo_extdb_index1 ON DB_SCHEMA_NAME.efo_extdb USING btree (efo_entry_id, source_id, efo_extdb_name);

CREATE INDEX efo_hierarchy_index1 ON DB_SCHEMA_NAME.efo_hierarchy USING btree (efo_entry_id);

CREATE INDEX efo_hierarchy_index2 ON DB_SCHEMA_NAME.efo_hierarchy USING btree (efo_level_left, efo_level_right);

CREATE INDEX fi_chr_map_chr ON DB_SCHEMA_NAME.chr_gn_map USING btree (chr_map_id);

CREATE INDEX fi_chr_map_gn ON DB_SCHEMA_NAME.chr_gn_map USING btree (gn_entry_id);

CREATE INDEX fi_chr_tax ON DB_SCHEMA_NAME.chromosome USING btree (taxon_id);

CREATE INDEX fi_gum_gn ON DB_SCHEMA_NAME.gn_prot_map USING btree (gn_entry_id);

CREATE INDEX fi_gum_un ON DB_SCHEMA_NAME.gn_prot_map USING btree (prot_entry_id);

CREATE INDEX fi_prot_ac_un ON DB_SCHEMA_NAME.prot_ac USING btree (prot_entry_id);

CREATE INDEX fi_prot_dom_al_dc ON DB_SCHEMA_NAME.prot_dom_al USING btree (prot_dom_comp_id);

CREATE INDEX fi_prot_dom_al_dr ON DB_SCHEMA_NAME.prot_dom_al USING btree (prot_dom_ref_id);

CREATE INDEX fi_prot_dom_seq_dom ON DB_SCHEMA_NAME.prot_dom_seq USING btree (prot_dom_id);

CREATE INDEX fi_prot_dom_seq_prot_seq ON DB_SCHEMA_NAME.prot_dom_seq USING btree (prot_seq_pos_id);

CREATE INDEX fi_prot_dom_un ON DB_SCHEMA_NAME.prot_dom USING btree (prot_entry_id);

CREATE INDEX fi_prot_e_taxid ON DB_SCHEMA_NAME.prot_entry USING btree (taxon_id);

CREATE INDEX fi_prot_feat_ftype ON DB_SCHEMA_NAME.prot_feat USING btree (prot_feat_type_id);

CREATE INDEX fi_prot_feat_seq ON DB_SCHEMA_NAME.prot_feat USING btree (prot_seq_id);

CREATE INDEX fi_prot_fseq_feat ON DB_SCHEMA_NAME.prot_feat_seq USING btree (prot_feat_id);

CREATE INDEX fi_prot_fseq_seqpos ON DB_SCHEMA_NAME.prot_feat_seq USING btree (prot_seq_pos_id);

CREATE INDEX fi_prot_name_map_une ON DB_SCHEMA_NAME.prot_name_map USING btree (prot_entry_id);

CREATE INDEX fi_prot_name_map_upm ON DB_SCHEMA_NAME.prot_name_map USING btree (prot_name_id);

CREATE UNIQUE INDEX uk_prot_name_map_upm ON DB_SCHEMA_NAME.prot_name_map USING btree (prot_entry_id,prot_name_id,group_id,class_name,name_type,name_subtype);

CREATE INDEX gene_seq_index1 ON DB_SCHEMA_NAME.gene_seq USING btree (gn_entry_id);

CREATE INDEX gene_seq_index2 ON DB_SCHEMA_NAME.gene_seq USING btree (chr_seq_id);

CREATE INDEX gene_seq_index3 ON DB_SCHEMA_NAME.gene_seq USING btree (strand);

CREATE INDEX genome_assembly_idx1 ON DB_SCHEMA_NAME.genome_assembly USING btree (taxon_id);

CREATE UNIQUE INDEX gn_fam_map_index1 ON DB_SCHEMA_NAME.gn_fam_map USING btree (gn_family_id, gn_entry_id);

CREATE UNIQUE INDEX gn_fam_map_index1_prev ON DB_SCHEMA_NAME.gn_fam_map_prev USING btree (gn_family_id, gn_entry_id);

CREATE UNIQUE INDEX gn_fam_map_index2 ON DB_SCHEMA_NAME.gn_fam_map USING btree (gn_entry_id, gn_family_id);

CREATE UNIQUE INDEX gn_fam_map_index2_prev ON DB_SCHEMA_NAME.gn_fam_map_prev USING btree (gn_entry_id, gn_family_id);

CREATE UNIQUE INDEX gn_history_index1 ON DB_SCHEMA_NAME.gn_history USING btree (gene_id);

CREATE INDEX gn_history_index2 ON DB_SCHEMA_NAME.gn_history USING btree (alt_gene_id);

CREATE INDEX gn_history_index3 ON DB_SCHEMA_NAME.gn_history USING btree (gn_entry_id);

CREATE INDEX gn_syn_idx_lower ON DB_SCHEMA_NAME.gn_syn USING btree (lower((syn_value)::text));

CREATE INDEX go_dbref_index1 ON DB_SCHEMA_NAME.go_dbref USING btree (go_entry_id);

CREATE UNIQUE INDEX go_pmid_map_index1 ON DB_SCHEMA_NAME.go_pmid_map USING btree (go_entry_id, pmid_entry_id, go_def_type);

CREATE INDEX go_pmid_map_index2 ON DB_SCHEMA_NAME.go_pmid_map USING btree (pmid_entry_id);

CREATE INDEX go_prot_map_index1 ON DB_SCHEMA_NAME.prot_go_map USING btree (go_entry_id, prot_entry_id);

CREATE INDEX go_prot_map_index1_prev ON DB_SCHEMA_NAME.go_prot_map_prev USING btree (go_entry_id, prot_entry_id);

CREATE INDEX go_prot_map_index2 ON DB_SCHEMA_NAME.prot_go_map USING btree (prot_entry_id, go_entry_id);

CREATE INDEX go_prot_map_index2_prev ON DB_SCHEMA_NAME.go_prot_map_prev USING btree (prot_entry_id, go_entry_id);

CREATE INDEX go_prot_map_index3 ON DB_SCHEMA_NAME.prot_go_map USING btree (prot_entry_id);

CREATE INDEX go_prot_map_index3_prev ON DB_SCHEMA_NAME.go_prot_map_prev USING btree (prot_entry_id);

CREATE INDEX go_rel_index1 ON DB_SCHEMA_NAME.go_rel USING btree (go_from_id, go_rel_id);

CREATE INDEX go_rel_index2 ON DB_SCHEMA_NAME.go_rel USING btree (go_to_id, go_from_id);

CREATE INDEX go_syn_index1 ON DB_SCHEMA_NAME.go_syn USING btree (go_entry_id);

CREATE UNIQUE INDEX go_syn_index2 ON DB_SCHEMA_NAME.go_syn USING btree (go_entry_id, syn_value, syn_type);

CREATE INDEX id_gn_ortho_c ON DB_SCHEMA_NAME.gn_rel USING btree (gn_entry_c_id);

CREATE INDEX id_gn_ortho_r ON DB_SCHEMA_NAME.gn_rel USING btree (gn_entry_r_id);

CREATE INDEX id_p_xrprot_site_un ON DB_SCHEMA_NAME.p_xrprot_site USING btree (prot_entry_id);

CREATE UNIQUE INDEX id_xclm_chli ON DB_SCHEMA_NAME.xr_ch_lig_map USING btree (xr_chain_id, xr_lig_id, "position");

CREATE UNIQUE INDEX id_xclm_lich ON DB_SCHEMA_NAME.xr_ch_lig_map USING btree (xr_lig_id, xr_chain_id, "position");

CREATE INDEX id_xclm_xr_ch ON DB_SCHEMA_NAME.xr_ch_lig_map USING btree (xr_chain_id);

CREATE INDEX id_xclm_xr_lig ON DB_SCHEMA_NAME.xr_ch_lig_map USING btree (xr_lig_id);

CREATE INDEX id_xr_res_chain ON DB_SCHEMA_NAME.xr_res USING btree (xr_chain_id);

CREATE UNIQUE INDEX idu_p_xrprot_site_unn ON DB_SCHEMA_NAME.p_xrprot_site USING btree (prot_entry_id, site_num, subsite_id);

CREATE UNIQUE INDEX idu_xr_cav_name ON DB_SCHEMA_NAME.xr_cav USING btree (cavity_name);

CREATE UNIQUE INDEX idu_xr_cav_site ON DB_SCHEMA_NAME.xr_cav USING btree (cavity_name, xr_site_id);

CREATE INDEX idu_xr_res_chain_res ON DB_SCHEMA_NAME.xr_res USING btree (xr_chain_id, "position", xr_tpl_res_id);

CREATE UNIQUE INDEX idu_xr_site_entry ON DB_SCHEMA_NAME.xr_site USING btree (xr_entry_id, xr_site_id);

CREATE UNIQUE INDEX idu_xr_site_res ON DB_SCHEMA_NAME.xr_site_res USING btree (xr_site_id, xr_res_id);

CREATE UNIQUE INDEX idu_gn_info ON DB_SCHEMA_NAME.gn_info USING btree (gn_entry_id, source_id, source_entry, info_type);

CREATE  INDEX id_gn_info_gn ON DB_SCHEMA_NAME.gn_info USING btree (gn_entry_id);

CREATE  INDEX id_gn_info_s ON DB_SCHEMA_NAME.gn_info USING btree (source_id);

CREATE INDEX idx_gsm_ge ON DB_SCHEMA_NAME.gn_syn_map USING btree (gn_entry_id);

CREATE INDEX idx_gsm_gsyn ON DB_SCHEMA_NAME.gn_syn_map USING btree (gn_syn_id);

CREATE UNIQUE INDEX idx_gsn_gegs ON DB_SCHEMA_NAME.gn_syn_map USING btree (gn_entry_id, gn_syn_id);

CREATE UNIQUE INDEX idx_gsn_gsge ON DB_SCHEMA_NAME.gn_syn_map USING btree (gn_syn_id, gn_entry_id);

CREATE INDEX ind_residueatom_resatom ON DB_SCHEMA_NAME.xr_tpl_atom USING btree (xr_tpl_res_id, name);

CREATE INDEX index1 ON DB_SCHEMA_NAME.xr_tpl_bond USING btree (xr_tpl_atom_id_1);

CREATE INDEX index19 ON DB_SCHEMA_NAME.xr_tpl_bond USING btree (xr_tpl_atom_id_2);

CREATE INDEX index4 ON DB_SCHEMA_NAME.xr_atom USING btree (xr_tpl_atom_id);

CREATE INDEX index8 ON DB_SCHEMA_NAME.xr_tpl_atom USING btree (xr_tpl_res_id);

CREATE INDEX ip_ext_db_index1 ON DB_SCHEMA_NAME.ip_ext_db USING btree (ip_entry_id);

CREATE UNIQUE INDEX ip_go_map_index1 ON DB_SCHEMA_NAME.ip_go_map USING btree (ip_entry_id, go_entry_id);

CREATE INDEX ip_go_map_index2 ON DB_SCHEMA_NAME.ip_go_map USING btree (go_entry_id);

CREATE INDEX ip_pmid_map_index1 ON DB_SCHEMA_NAME.ip_pmid_map USING btree (pmid_entry_id);

CREATE INDEX ip_pmid_map_index2 ON DB_SCHEMA_NAME.ip_pmid_map USING btree (ip_entry_id);

CREATE UNIQUE INDEX ip_sign_prot_seq_index1 ON DB_SCHEMA_NAME.ip_sign_prot_seq USING btree (ip_signature_id, prot_seq_id, start_pos, end_pos);

CREATE INDEX ip_sign_prot_seq_index2 ON DB_SCHEMA_NAME.ip_sign_prot_seq USING btree (prot_seq_id, ip_signature_id);

CREATE INDEX ip_signature_index1 ON DB_SCHEMA_NAME.ip_signature USING btree (ip_entry_id);

CREATE UNIQUE INDEX ip_signature_index2 ON DB_SCHEMA_NAME.ip_signature USING btree (ip_entry_id, ip_sign_dbname, ip_sign_dbkey);

CREATE INDEX jd_index3 ON DB_SCHEMA_NAME.xr_atom USING btree (xr_res_id);

CREATE INDEX lly_user_stat_index1 ON DB_SCHEMA_NAME.web_user_stat USING btree (web_user_id);

CREATE INDEX lly_user_stat_index2 ON DB_SCHEMA_NAME.web_user_stat USING btree (portal_value);

CREATE UNIQUE INDEX md5_hash_cpd ON DB_SCHEMA_NAME.sm_entry USING btree (md5_hash);

CREATE UNIQUE INDEX pr_mod_pattern_hash_uk ON DB_SCHEMA_NAME.mod_pattern USING btree (hash);

CREATE UNIQUE INDEX pr_mod_pattern_pos_uk ON DB_SCHEMA_NAME.mod_pattern_pos USING btree (mod_pattern_id, change_position, change_location, isactivestrand);

CREATE UNIQUE INDEX uk_mole_comp_hash ON DB_SCHEMA_NAME.molecular_component USING btree (molecular_component_hash);

CREATE INDEX uk_mole_comp_s_hash ON DB_SCHEMA_NAME.molecular_component USING btree (molecular_component_structure);

CREATE UNIQUE INDEX uk_mcsm ON DB_SCHEMA_NAME.molecular_component_sm_map USING btree (molecular_component_id,sm_entry_id);

CREATE  INDEX id_mcsm_mc ON DB_SCHEMA_NAME.molecular_component_sm_map USING btree (molecular_component_id);

CREATE  INDEX id_mcsm_se ON DB_SCHEMA_NAME.molecular_component_sm_map USING btree (sm_entry_id);

CREATE UNIQUE INDEX uk_me_hash ON DB_SCHEMA_NAME.molecular_entity USING btree (molecular_entity_hash);

CREATE UNIQUE INDEX uk_me_mc ON DB_SCHEMA_NAME.molecular_entity USING btree (molecular_components);

CREATE UNIQUE INDEX uk_mecm ON DB_SCHEMA_NAME.molecular_entity_component_map USING btree (molecular_entity_id,molecular_component_id);

CREATE  INDEX mecm_id1 ON DB_SCHEMA_NAME.molecular_entity_component_map USING btree (molecular_entity_id);

CREATE  INDEX mecm_id2 ON DB_SCHEMA_NAME.molecular_entity_component_map USING btree (molecular_component_id);

CREATE UNIQUE INDEX mrna_biotype_name_idx ON DB_SCHEMA_NAME.mrna_biotype USING btree (biotype);

CREATE INDEX mv_anatomy_publi_idx1 ON DB_SCHEMA_NAME.mv_anatomy_publi USING btree (anatomy_entry_id, publication_date);

CREATE INDEX mv_anatomy_publi_idx2 ON DB_SCHEMA_NAME.mv_anatomy_publi USING btree (anatomy_entry_id, pmid);

CREATE INDEX mv_disease_publi_idx1 ON DB_SCHEMA_NAME.mv_disease_publi USING btree (disease_entry_id, publication_date);

CREATE INDEX mv_disease_publi_idx2 ON DB_SCHEMA_NAME.mv_disease_publi USING btree (disease_entry_id, pmid);

CREATE INDEX mv_drug_publi_idx1 ON DB_SCHEMA_NAME.mv_drug_publi USING btree (drug_entry_id, publication_date);

CREATE INDEX mv_drug_publi_idx2 ON DB_SCHEMA_NAME.mv_drug_publi USING btree (drug_entry_id, pmid);

CREATE INDEX mv_gene2_gene_id_idx1 ON DB_SCHEMA_NAME.mv_gene USING btree (gene_id);

CREATE INDEX mv_gene2_gn_entry_id_idx1 ON DB_SCHEMA_NAME.mv_gene USING btree (gn_entry_id);

CREATE INDEX mv_gene2_lower_idx1 ON DB_SCHEMA_NAME.mv_gene USING btree (lower((symbol)::text));

CREATE INDEX mv_gene2_symbol_idx1 ON DB_SCHEMA_NAME.mv_gene USING btree (symbol);

CREATE INDEX mv_gene2_tax_id_idx1 ON DB_SCHEMA_NAME.mv_gene USING btree (tax_id);

CREATE INDEX mv_gene_publi_idx1 ON DB_SCHEMA_NAME.mv_gene_publi USING btree (gn_entry_id, publication_date);

CREATE INDEX mv_gene_publi_idx2 ON DB_SCHEMA_NAME.mv_gene_publi USING btree (gn_entry_id, pmid);

CREATE INDEX mv_gene_sp_index1 ON DB_SCHEMA_NAME.mv_gene_sp USING btree (symbol);

CREATE INDEX mv_gene_sp_index2 ON DB_SCHEMA_NAME.mv_gene_sp USING btree (gene_id);

CREATE INDEX mv_gene_sp_index3 ON DB_SCHEMA_NAME.mv_gene_sp USING btree (gn_entry_id);

CREATE INDEX mv_gene_sp_index4 ON DB_SCHEMA_NAME.mv_gene_sp USING btree (tax_id);

CREATE INDEX mv_gene_sp_index5 ON DB_SCHEMA_NAME.mv_gene_sp USING btree (syn_value);

CREATE INDEX mv_gene_sp_lower_idx ON DB_SCHEMA_NAME.mv_gene_sp USING btree (lower((syn_value)::text));

CREATE INDEX mv_gene_taxon_gene_id_idx ON DB_SCHEMA_NAME.mv_gene_taxon USING btree (gene_id);

CREATE INDEX mv_gene_taxon_gn_entry_id_idx ON DB_SCHEMA_NAME.mv_gene_taxon USING btree (gn_entry_id);

CREATE INDEX mv_gene_taxon_tax_id_idx ON DB_SCHEMA_NAME.mv_gene_taxon USING btree (tax_id);

CREATE INDEX mv_gene_taxon_taxon_id_idx ON DB_SCHEMA_NAME.mv_gene_taxon USING btree (taxon_id);

CREATE INDEX mv_se_dr ON DB_SCHEMA_NAME.mv_side_effect USING btree (drug_entry_id);

CREATE INDEX mv_targets_index1 ON DB_SCHEMA_NAME.mv_targets USING btree (taxon_id);

CREATE INDEX mv_targets_index1_prev ON DB_SCHEMA_NAME.mv_targets_prev USING btree (taxon_id);

CREATE INDEX mv_targets_index2 ON DB_SCHEMA_NAME.mv_targets USING btree (gn_entry_id);

CREATE INDEX mv_targets_index2_prev ON DB_SCHEMA_NAME.mv_targets_prev USING btree (gn_entry_id);

CREATE INDEX mv_targets_index3 ON DB_SCHEMA_NAME.mv_targets USING btree (prot_entry_id);

CREATE INDEX mv_targets_index4 ON DB_SCHEMA_NAME.mv_targets USING btree (gene_id);

CREATE INDEX mv_targets_index4_prev ON DB_SCHEMA_NAME.mv_targets_prev USING btree (gene_id);

CREATE INDEX mv_targets_index5 ON DB_SCHEMA_NAME.mv_targets USING btree (prot_identifier);

CREATE INDEX mv_targets_index5_prev ON DB_SCHEMA_NAME.mv_targets_prev USING btree (prot_identifier);

CREATE INDEX mv_targets_index6 ON DB_SCHEMA_NAME.mv_targets USING btree (symbol);

CREATE INDEX mv_targets_index6_prev ON DB_SCHEMA_NAME.mv_targets_prev USING btree (symbol);

CREATE INDEX mv_targets_index7 ON DB_SCHEMA_NAME.mv_targets USING btree (tax_id);

CREATE INDEX mv_targets_index7_prev ON DB_SCHEMA_NAME.mv_targets_prev USING btree (tax_id);

CREATE INDEX news_title_idx ON DB_SCHEMA_NAME.news USING btree (news_title);

CREATE INDEX nn_idx1 ON DB_SCHEMA_NAME.news_news_map USING btree (news_id);

CREATE INDEX nn_idx2 ON DB_SCHEMA_NAME.news_news_map USING btree (news_parent_id);

CREATE INDEX nucliec_acid_seq_strand_idx ON DB_SCHEMA_NAME.nucleic_acid_seq USING btree (active_strand);


CREATE UNIQUE INDEX pr_nucleic_acid_namytype_uk ON DB_SCHEMA_NAME.nucleic_acid_struct USING btree (nucleic_acid_name, orig_nucleic_acid);


CREATE UNIQUE INDEX pr_nucleic_acid_seq_helm_uk ON DB_SCHEMA_NAME.nucleic_acid_seq USING btree (helm_string);


CREATE UNIQUE INDEX pr_nucleic_acid_target_tr_uk ON DB_SCHEMA_NAME.nucleic_acid_target USING btree (transcript_pos_id, nucleic_acid_seq_id);


CREATE UNIQUE INDEX pr_nucleic_acid_type_name_uk ON DB_SCHEMA_NAME.nucleic_acid_type USING btree (seq_type_name);


CREATE UNIQUE INDEX pr_nucleic_pos_strand_uk ON DB_SCHEMA_NAME.nucleic_acid_seq_pos USING btree (nucleic_acid_seq_id, "position", isactivestrand);


CREATE UNIQUE INDEX p_xrprot_site_rel_index1 ON DB_SCHEMA_NAME.p_xrprot_site_rel USING btree (p_xrprot_site_id_ref, p_xrprot_site_id_comp);

CREATE INDEX p_xrprot_site_rel_index2 ON DB_SCHEMA_NAME.p_xrprot_site_rel USING btree (p_xrprot_site_id_comp, p_xrprot_site_id_ref);

CREATE INDEX p_xrprot_site_seq_index1 ON DB_SCHEMA_NAME.p_xrprot_site_seq USING btree (p_xrprot_site_id, prot_seq_pos_id);

CREATE INDEX p_xrprot_site_seq_index2 ON DB_SCHEMA_NAME.p_xrprot_site_seq USING btree (prot_seq_pos_id, p_xrprot_site_id);

CREATE UNIQUE INDEX p_xrprot_site_xray_map_index1 ON DB_SCHEMA_NAME.p_xrprot_site_xray_map USING btree (p_xrprot_site_id, xr_site_id);

CREATE UNIQUE INDEX p_xrprot_site_xray_map_index2 ON DB_SCHEMA_NAME.p_xrprot_site_xray_map USING btree (xr_site_id, p_xrprot_site_id);

CREATE UNIQUE INDEX patent_entry_index1 ON DB_SCHEMA_NAME.patent_entry USING btree (patent_application);

CREATE INDEX pgweb_idx ON DB_SCHEMA_NAME.pmid_instit USING gin (to_tsvector('english'::regconfig, instit_name));

CREATE INDEX pmid_abstract_pmid ON DB_SCHEMA_NAME.pmid_abstract USING btree (pmid_entry_id);

CREATE UNIQUE INDEX pmid_anatomy_map_uk1 ON DB_SCHEMA_NAME.pmid_anatomy_map USING btree (anatomy_entry_id, pmid_entry_id);

CREATE INDEX pmid_author_index1 ON DB_SCHEMA_NAME.pmid_author USING btree (last_name, first_name);



CREATE INDEX pmid_author_index2 ON DB_SCHEMA_NAME.pmid_author USING btree (pmid_instit_id);

CREATE INDEX pmid_author_index3 ON DB_SCHEMA_NAME.pmid_author USING btree (last_name, pmid_instit_id);

CREATE INDEX pmid_author_index4 ON DB_SCHEMA_NAME.pmid_author USING btree (orcid_id);

CREATE INDEX pmid_author_index5 ON DB_SCHEMA_NAME.pmid_author USING btree (LOWER(last_name), LOWER(first_name));

CREATE INDEX pmid_author_map_index1 ON DB_SCHEMA_NAME.pmid_author_map USING btree (pmid_entry_id);

CREATE INDEX pmid_author_map_index2 ON DB_SCHEMA_NAME.pmid_author_map USING btree (pmid_author_id);

CREATE UNIQUE INDEX pmid_author_uk ON DB_SCHEMA_NAME.pmid_author USING btree (md5_hash);

CREATE INDEX pmid_cit_pm ON DB_SCHEMA_NAME.pmid_citation USING btree (pmid_entry_id);

CREATE UNIQUE INDEX pmid_cit_uk ON DB_SCHEMA_NAME.pmid_citation USING btree (pmid_entry_id, citation_pmid_entry_id);

CREATE INDEX pmid_disease_gene_index1 ON DB_SCHEMA_NAME.pmid_disease_gene USING btree (pmid_entry_id);

CREATE INDEX pmid_disease_gene_index2 ON DB_SCHEMA_NAME.pmid_disease_gene USING btree (gn_entry_id);

CREATE UNIQUE INDEX pmid_disease_gene_index3 ON DB_SCHEMA_NAME.pmid_disease_gene USING btree (disease_entry_id, gn_entry_id, pmid_entry_id);

CREATE INDEX pmid_disease_gene_txt_index1 ON DB_SCHEMA_NAME.pmid_disease_gene_txt USING btree (pmid_disease_gene_id);

CREATE UNIQUE INDEX pmid_disease_map_uk1 ON DB_SCHEMA_NAME.pmid_disease_map USING btree (disease_entry_id, pmid_entry_id);

CREATE UNIQUE INDEX pmid_drug_map_index1 ON DB_SCHEMA_NAME.pmid_drug_map USING btree (drug_entry_id, pmid_entry_id);

CREATE UNIQUE INDEX pmid_drug_stat_index1 ON DB_SCHEMA_NAME.pmid_drug_stat USING btree (drug_entry_id);

CREATE UNIQUE INDEX pmid_entry_index1 ON DB_SCHEMA_NAME.pmid_entry USING btree (pmid);

CREATE INDEX pmid_entry_index2 ON DB_SCHEMA_NAME.pmid_entry USING btree (publication_date);

CREATE INDEX pmid_entry_index4 ON DB_SCHEMA_NAME.pmid_entry USING btree (month_1910);

CREATE UNIQUE INDEX pmid_gene_map_index1 ON DB_SCHEMA_NAME.pmid_gene_map USING btree (gn_entry_id, pmid_entry_id);

CREATE UNIQUE INDEX pmid_gene_stat_index1 ON DB_SCHEMA_NAME.pmid_gene_stat USING btree (gn_entry_id);

CREATE UNIQUE INDEX pmid_pk_id ON DB_SCHEMA_NAME.pmid_entry USING btree (pmid_entry_id, pmid);

CREATE UNIQUE INDEX pmid_onto_map_index1 ON DB_SCHEMA_NAME.pmid_onto_map USING btree (ontology_entry_id, pmid_entry_id);


CREATE INDEX pmc_entry_index1 ON DB_SCHEMA_NAME.pmc_entry USING btree (pmid_entry_id);

CREATE UNIQUE INDEX pmc_fulltext_gn_uk ON DB_SCHEMA_NAME.pmc_fulltext_gn_map USING btree(pmc_fulltext_id,gn_entry_id,loc_info);

CREATE INDEX pmc_fulltext_gn_id1 ON DB_SCHEMA_NAME.pmc_fulltext_gn_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_gn_id2 ON DB_SCHEMA_NAME.pmc_fulltext_gn_map USING btree(gn_entry_id);


CREATE UNIQUE INDEX pmc_fulltext_ontology_uk ON DB_SCHEMA_NAME.pmc_fulltext_ontology_map USING btree(pmc_fulltext_id,ontology_entry_id,loc_info);

CREATE INDEX pmc_fulltext_ontology_id1 ON DB_SCHEMA_NAME.pmc_fulltext_ontology_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_ontology_id2 ON DB_SCHEMA_NAME.pmc_fulltext_ontology_map USING btree(ontology_entry_id);

CREATE UNIQUE INDEX pmc_fulltext_anatomy_uk ON DB_SCHEMA_NAME.pmc_fulltext_anatomy_map USING btree(pmc_fulltext_id,anatomy_entry_id,loc_info);

CREATE INDEX pmc_fulltext_anatomy_id1 ON DB_SCHEMA_NAME.pmc_fulltext_anatomy_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_anatomy_id2 ON DB_SCHEMA_NAME.pmc_fulltext_anatomy_map USING btree(anatomy_entry_id);

CREATE UNIQUE INDEX pmc_fulltext_disease_uk ON DB_SCHEMA_NAME.pmc_fulltext_disease_map USING btree(pmc_fulltext_id,disease_entry_id,loc_info);

CREATE INDEX pmc_fulltext_disease_id1 ON DB_SCHEMA_NAME.pmc_fulltext_disease_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_disease_id2 ON DB_SCHEMA_NAME.pmc_fulltext_disease_map USING btree(disease_entry_id);

CREATE UNIQUE INDEX pmc_fulltext_drug_uk ON DB_SCHEMA_NAME.pmc_fulltext_drug_map USING btree(pmc_fulltext_id,drug_entry_id,loc_info);

CREATE INDEX pmc_fulltext_drug_id1 ON DB_SCHEMA_NAME.pmc_fulltext_drug_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_drug_id2 ON DB_SCHEMA_NAME.pmc_fulltext_drug_map USING btree(drug_entry_id);


CREATE INDEX pmc_fulltext_sm_id1 ON DB_SCHEMA_NAME.pmc_fulltext_sm_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_sm_id2 ON DB_SCHEMA_NAME.pmc_fulltext_sm_map USING btree(sm_entry_id);

CREATE INDEX pmc_fulltext_pub_id2 ON DB_SCHEMA_NAME.pmc_fulltext_pub_map USING btree(pmid_entry_id);

CREATE INDEX pmc_fulltext_pub_id1 ON DB_SCHEMA_NAME.pmc_fulltext_pub_map USING btree(pmc_fulltext_id);


CREATE UNIQUE INDEX pmc_section_uk ON DB_SCHEMA_NAME.pmc_section USING btree (section_type,section_subtype);

CREATE UNIQUE INDEX pmc_fulltext_uk ON DB_SCHEMA_NAME.pmc_fulltext USING btree(pmc_entry_id, pmc_section_id,offset_pos);

CREATE INDEX pmc_fulltext_id1 ON DB_SCHEMA_NAME.pmc_fulltext USING btree(pmc_entry_id);

CREATE UNIQUE INDEX pmc_fulltext_go_uk ON DB_SCHEMA_NAME.pmc_fulltext_go_map USING btree(pmc_fulltext_id,go_entry_id,loc_info);

CREATE INDEX pmc_fulltext_go_id1 ON DB_SCHEMA_NAME.pmc_fulltext_go_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_go_id2 ON DB_SCHEMA_NAME.pmc_fulltext_go_map USING btree(go_entry_id);

CREATE UNIQUE INDEX pmc_fulltext_company_uk ON DB_SCHEMA_NAME.pmc_fulltext_company_map USING btree(pmc_fulltext_id,company_entry_id,loc_info);

CREATE INDEX pmc_fulltext_company_id1 ON DB_SCHEMA_NAME.pmc_fulltext_company_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_company_id2 ON DB_SCHEMA_NAME.pmc_fulltext_company_map USING btree(company_entry_id);

CREATE UNIQUE INDEX pmc_fulltext_cell_uk ON DB_SCHEMA_NAME.pmc_fulltext_cell_map USING btree(pmc_fulltext_id,cell_entry_id,loc_info);

CREATE INDEX pmc_fulltext_cell_id1 ON DB_SCHEMA_NAME.pmc_fulltext_cell_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_cell_id2 ON DB_SCHEMA_NAME.pmc_fulltext_cell_map USING btree(cell_entry_id);

CREATE UNIQUE INDEX pmc_fulltext_clinical_uk ON DB_SCHEMA_NAME.pmc_fulltext_clinical_map USING btree(pmc_fulltext_id,clinical_trial_id,loc_info);

CREATE INDEX pmc_fulltext_clinical_id1 ON DB_SCHEMA_NAME.pmc_fulltext_clinical_map USING btree(pmc_fulltext_id);

CREATE INDEX pmc_fulltext_clinical_id2 ON DB_SCHEMA_NAME.pmc_fulltext_clinical_map USING btree(clinical_trial_id);

CREATE UNIQUE INDEX pmc_fulltext_file_uk  ON DB_SCHEMA_NAME.pmc_fulltext_file USING btree(pmc_entry_id,file_id);

CREATE INDEX pmc_fulltext_file_pm  ON DB_SCHEMA_NAME.pmc_fulltext_file USING btree(pmc_entry_id);

CREATE UNIQUE INDEX pname_uni_idx ON DB_SCHEMA_NAME.prot_name USING btree (protein_name, ec_number) WHERE (ec_number IS NOT NULL);

CREATE UNIQUE INDEX pname_uni_idx2 ON DB_SCHEMA_NAME.prot_name USING btree (protein_name) WHERE (ec_number IS NULL);

CREATE INDEX prot_ac_ac ON DB_SCHEMA_NAME.prot_ac USING btree (ac);

CREATE UNIQUE INDEX prot_desc_pmid_index1 ON DB_SCHEMA_NAME.prot_desc_pmid USING btree (prot_desc_id, pmid_entry_id, eco_entry_id);

CREATE INDEX prot_desc_pmid_index2 ON DB_SCHEMA_NAME.prot_desc_pmid USING btree (pmid_entry_id);

CREATE INDEX prot_description_index1 ON DB_SCHEMA_NAME.prot_desc USING btree (prot_entry_id, desc_type);

CREATE UNIQUE INDEX prot_dom_al_index1 ON DB_SCHEMA_NAME.prot_dom_al USING btree (prot_dom_ref_id, prot_dom_comp_id);

CREATE INDEX prot_dom_al_seq_index1 ON DB_SCHEMA_NAME.prot_dom_al_seq USING btree (prot_dom_al_id);

CREATE INDEX prot_dom_al_seq_index2 ON DB_SCHEMA_NAME.prot_dom_al_seq USING btree (prot_dom_seq_id_ref);

CREATE INDEX prot_dom_al_seq_index3 ON DB_SCHEMA_NAME.prot_dom_al_seq USING btree (prot_dom_seq_id_comp);

CREATE INDEX prot_dom_index1 ON DB_SCHEMA_NAME.prot_dom USING btree (domain_name);

CREATE UNIQUE INDEX prot_extdb_index1 ON DB_SCHEMA_NAME.prot_extdb USING btree (prot_extdbac);

CREATE INDEX prot_extdb_index2 ON DB_SCHEMA_NAME.prot_extdb USING btree (prot_extdbabbr);

CREATE INDEX prot_extdb_map_index1 ON DB_SCHEMA_NAME.prot_extdb_map USING btree (prot_extdb_id);

CREATE INDEX prot_extdb_map_index2 ON DB_SCHEMA_NAME.prot_extdb_map USING btree (prot_entry_id);

CREATE INDEX prot_extdb_map_index3 ON DB_SCHEMA_NAME.prot_extdb_map USING btree (prot_seq_id);

CREATE INDEX prot_extdb_search ON DB_SCHEMA_NAME.prot_extdb_map USING btree (prot_extdb_value);

CREATE INDEX prot_feat_pmid_index1 ON DB_SCHEMA_NAME.prot_feat_pmid USING btree (prot_feat_id);

CREATE INDEX prot_feat_pmid_index2 ON DB_SCHEMA_NAME.prot_feat_pmid USING btree (pmid_entry_id);

CREATE UNIQUE INDEX prot_gn_syntype_pair ON DB_SCHEMA_NAME.gn_syn USING btree (syn_type, syn_value);

CREATE INDEX prot_pmid_map_index1 ON DB_SCHEMA_NAME.prot_pmid_map USING btree (prot_entry_id, pmid_entry_id);

CREATE INDEX prot_pmid_map_index2 ON DB_SCHEMA_NAME.prot_pmid_map USING btree (pmid_entry_id, prot_entry_id);

CREATE UNIQUE INDEX prot_seq_al_index1 ON DB_SCHEMA_NAME.prot_seq_al USING btree (prot_seq_ref_id, prot_seq_comp_id);

CREATE INDEX prot_seq_al_index2 ON DB_SCHEMA_NAME.prot_seq_al USING btree (prot_seq_comp_id);

CREATE INDEX prot_seq_al_index3 ON DB_SCHEMA_NAME.prot_seq_al USING btree (prot_seq_ref_id);

CREATE INDEX prot_seq_al_seq_index1 ON DB_SCHEMA_NAME.prot_seq_al_seq USING btree (prot_seq_al_id);

CREATE INDEX prot_seq_al_seq_index2 ON DB_SCHEMA_NAME.prot_seq_al_seq USING btree (prot_seq_id_ref);

CREATE INDEX prot_seq_al_seq_index3 ON DB_SCHEMA_NAME.prot_seq_al_seq USING btree (prot_seq_id_comp);

CREATE INDEX prot_seq_index1 ON DB_SCHEMA_NAME.prot_seq USING btree (description);

CREATE INDEX prot_seq_index2 ON DB_SCHEMA_NAME.prot_seq USING btree (iso_name);

CREATE UNIQUE INDEX prot_seq_index3 ON DB_SCHEMA_NAME.prot_seq USING btree (prot_entry_id, iso_id);

CREATE INDEX prot_seq_pos_index1 ON DB_SCHEMA_NAME.prot_seq_pos USING btree (prot_seq_id, "position");

CREATE INDEX prot_seq_pos_index2 ON DB_SCHEMA_NAME.prot_seq_pos USING btree (prot_seq_id);

CREATE INDEX pw_entry_index1 ON DB_SCHEMA_NAME.pw_entry USING btree (lower((pw_name)::text));

CREATE INDEX pw_hierarchy_index1 ON DB_SCHEMA_NAME.pw_hierarchy USING btree (pw_entry_id);

CREATE INDEX pw_hierarchy_index2 ON DB_SCHEMA_NAME.pw_hierarchy USING btree (level_left, level_right);

CREATE INDEX pw_rel_index1 ON DB_SCHEMA_NAME.pw_rel USING btree (pw_from_id, pw_rel_id);

CREATE INDEX pw_rel_index2 ON DB_SCHEMA_NAME.pw_rel USING btree (pw_to_id, pw_from_id);

CREATE UNIQUE INDEX rna_gene_index1 ON DB_SCHEMA_NAME.rna_gene USING btree (gene_seq_id, rna_sample_id);

CREATE INDEX rna_gene_index2 ON DB_SCHEMA_NAME.rna_gene USING btree (rna_sample_id);

CREATE INDEX rna_gene_stat_index1 ON DB_SCHEMA_NAME.rna_gene_stat USING btree (gene_seq_id);

CREATE INDEX rna_gene_stat_index2 ON DB_SCHEMA_NAME.rna_gene_stat USING btree (rna_tissue_id);

CREATE INDEX rna_gene_stat_index3 ON DB_SCHEMA_NAME.rna_gene_stat USING btree (rna_tissue_id, med_value DESC);

CREATE INDEX rna_gtex_index1 ON DB_SCHEMA_NAME.rna_transcript USING btree (transcript_id);

CREATE INDEX rna_gtex_sample_index1 ON DB_SCHEMA_NAME.rna_sample USING btree (rna_source_id);

CREATE INDEX rna_gtex_stat_index1 ON DB_SCHEMA_NAME.rna_transcript_stat USING btree (transcript_id);

CREATE INDEX rna_gtex_stat_index2 ON DB_SCHEMA_NAME.rna_transcript_stat USING btree (rna_tissue_id);

CREATE INDEX rna_sample_index1 ON DB_SCHEMA_NAME.rna_sample USING btree (sample_id);

CREATE INDEX rna_sample_index2 ON DB_SCHEMA_NAME.rna_sample USING btree (rna_tissue_id);

CREATE UNIQUE INDEX rna_source_index1 ON DB_SCHEMA_NAME.rna_source USING btree (source_name);

CREATE INDEX rna_tissue_id1 ON DB_SCHEMA_NAME.rna_tissue USING btree (anatomy_entry_id);

CREATE INDEX rna_tissue_id2 ON DB_SCHEMA_NAME.rna_tissue USING btree (efo_entry_id);

CREATE INDEX rna_transcript_stat_index1 ON DB_SCHEMA_NAME.rna_transcript_stat USING btree (rna_tissue_id, med_value DESC);

CREATE INDEX scv_id ON DB_SCHEMA_NAME.clinical_variant_submission USING btree (scv_id);

CREATE INDEX sda_a ON DB_SCHEMA_NAME.sharepoint_doc_author_map USING btree (web_user_id);

CREATE INDEX sda_sd ON DB_SCHEMA_NAME.sharepoint_doc_author_map USING btree (sharepoint_document_id);

CREATE INDEX sdcm_co ON DB_SCHEMA_NAME.sharepoint_doc_company_map USING btree (company_entry_id);

CREATE INDEX sdcm_sd ON DB_SCHEMA_NAME.sharepoint_doc_company_map USING btree (sharepoint_document_id);

CREATE INDEX sdct_ct ON DB_SCHEMA_NAME.sharepoint_doc_clinical_trial_map USING btree (clinical_trial_id);

CREATE INDEX sdct_sd ON DB_SCHEMA_NAME.sharepoint_doc_clinical_trial_map USING btree (sharepoint_document_id);

CREATE INDEX sdd_sd ON DB_SCHEMA_NAME.sharepoint_doc_drug_map USING btree (sharepoint_document_id);

CREATE INDEX sdd_sde ON DB_SCHEMA_NAME.sharepoint_doc_drug_map USING btree (drug_entry_id);

CREATE INDEX sdds_ds ON DB_SCHEMA_NAME.sharepoint_doc_disease_map USING btree (disease_entry_id);

CREATE INDEX sdds_sd ON DB_SCHEMA_NAME.sharepoint_doc_disease_map USING btree (sharepoint_document_id);

CREATE INDEX sdg_g ON DB_SCHEMA_NAME.sharepoint_doc_gn_map USING btree (gn_entry_id);

CREATE INDEX sdg_sd ON DB_SCHEMA_NAME.sharepoint_doc_gn_map USING btree (sharepoint_document_id);

CREATE INDEX sed_dre ON DB_SCHEMA_NAME.side_effect_drug USING btree (drug_entry_id);

CREATE INDEX sed_ser ON DB_SCHEMA_NAME.side_effect_drug USING btree (side_effect_report_id);

CREATE INDEX sedr_me ON DB_SCHEMA_NAME.side_effect_drug_reaction USING btree (meddra_entry_id);

CREATE INDEX sedr_re ON DB_SCHEMA_NAME.side_effect_drug_reaction USING btree (side_effect_report_id);

CREATE INDEX ser_se ON DB_SCHEMA_NAME.side_effect_report USING btree (side_effect_seriousness_id);

CREATE INDEX ser_so ON DB_SCHEMA_NAME.side_effect_report USING btree (source_id);

CREATE UNIQUE INDEX sm_counterion_index1 ON DB_SCHEMA_NAME.sm_counterion USING btree (counterion_smiles);

CREATE INDEX sm_entry_extid ON DB_SCHEMA_NAME.sm_entry USING btree (sm_molecule_id, sm_counterion_id);

CREATE INDEX sm_entry_index2 ON DB_SCHEMA_NAME.sm_entry USING btree (inchi_key);

CREATE UNIQUE INDEX sm_molecule_index1 ON DB_SCHEMA_NAME.sm_molecule USING btree (smiles);

CREATE INDEX sm_molecule_index2 ON DB_SCHEMA_NAME.sm_molecule USING btree (sm_scaffold_id);

CREATE INDEX sm_patent_map_sme ON DB_SCHEMA_NAME.sm_patent_map USING btree (sm_entry_id);

CREATE UNIQUE INDEX sm_patent_map_uk1 ON DB_SCHEMA_NAME.sm_patent_map USING btree (patent_entry_id, sm_entry_id, field);

CREATE UNIQUE INDEX sm_scaffold_index2 ON DB_SCHEMA_NAME.sm_scaffold USING btree (scaffold_smiles);

CREATE INDEX sm_source_id ON DB_SCHEMA_NAME.sm_source USING btree (source_id);

CREATE INDEX sm_source_index1 ON DB_SCHEMA_NAME.sm_source USING btree (sm_name);

CREATE UNIQUE INDEX so_entry_index1 ON DB_SCHEMA_NAME.so_entry USING btree (so_id);

CREATE UNIQUE INDEX source_name_uk ON DB_SCHEMA_NAME.source USING btree (source_name);

CREATE INDEX table1_index1 ON DB_SCHEMA_NAME.xr_inter_res USING btree (xr_atom_id_1);

CREATE INDEX table1_index2 ON DB_SCHEMA_NAME.xr_inter_res USING btree (xr_atom_id_2);

CREATE INDEX table1_index3 ON DB_SCHEMA_NAME.xr_inter_res USING btree (xr_res_id_1);

CREATE INDEX table1_index4 ON DB_SCHEMA_NAME.xr_inter_res USING btree (xr_res_id_2);

CREATE INDEX table1_index5 ON DB_SCHEMA_NAME.xr_inter_res USING btree (xr_inter_type_id);

CREATE INDEX taxon_index1 ON DB_SCHEMA_NAME.taxon_tree USING btree (tax_level);

CREATE INDEX taxon_index2 ON DB_SCHEMA_NAME.taxon_tree USING btree (level_left, level_right);

CREATE UNIQUE INDEX taxon_index3 ON DB_SCHEMA_NAME.taxon USING btree (tax_id);

CREATE UNIQUE INDEX tr_protseq_al_index1 ON DB_SCHEMA_NAME.tr_protseq_al USING btree (prot_seq_id, transcript_id);

CREATE INDEX tr_protseq_al_index2 ON DB_SCHEMA_NAME.tr_protseq_al USING btree (transcript_id, prot_seq_id);

CREATE INDEX tr_protseq_pos_al_index1 ON DB_SCHEMA_NAME.tr_protseq_pos_al USING btree (tr_protseq_al_id);

CREATE INDEX tr_protseq_pos_al_index2 ON DB_SCHEMA_NAME.tr_protseq_pos_al USING btree (prot_seq_pos_id);

CREATE INDEX tr_protseq_pos_al_index3 ON DB_SCHEMA_NAME.tr_protseq_pos_al USING btree (transcript_pos_id);

CREATE INDEX transcript_index1 ON DB_SCHEMA_NAME.transcript USING btree (gene_seq_id);

CREATE INDEX transcript_seq_index1 ON DB_SCHEMA_NAME.transcript_pos USING btree (transcript_id);

CREATE UNIQUE INDEX translation_name ON DB_SCHEMA_NAME.translation_tbl USING btree (translation_tbl_id);

CREATE UNIQUE INDEX u_drug_disease_no_gene ON DB_SCHEMA_NAME.drug_disease USING btree (drug_entry_id, disease_entry_id) WHERE (gn_entry_id IS NULL);

CREATE INDEX ui_prot_e_iden ON DB_SCHEMA_NAME.prot_entry USING btree (prot_identifier);

CREATE UNIQUE INDEX uk_assay_target_genetic_map ON DB_SCHEMA_NAME.assay_target_genetic_map USING btree (assay_target_id, assay_genetic_id);

CREATE UNIQUE INDEX uk_assay_target_protein_map ON DB_SCHEMA_NAME.assay_target_protein_map USING btree (assay_target_id, assay_protein_id);

CREATE UNIQUE INDEX ukdx_go_entry_ac ON DB_SCHEMA_NAME.go_entry USING btree (ac);

CREATE UNIQUE INDEX va_uk ON DB_SCHEMA_NAME.variant_allele USING btree (variant_seq);

CREATE UNIQUE INDEX variant_change_uk_nullall ON DB_SCHEMA_NAME.variant_change USING btree (variant_position_id, ((alt_all IS NULL))) WHERE (alt_all IS NULL);

CREATE UNIQUE INDEX variant_clinv_assert_map_index1 ON DB_SCHEMA_NAME.variant_clinv_assert_map USING btree (clinv_assert_id, variant_change_id);

CREATE UNIQUE INDEX variant_clinv_assert_map_index2 ON DB_SCHEMA_NAME.variant_clinv_assert_map USING btree (variant_change_id, clinv_assert_id);

CREATE INDEX variant_clinv_assert_map_index3 ON DB_SCHEMA_NAME.variant_clinv_assert_map USING btree (variant_change_id);

CREATE UNIQUE INDEX variant_entry_rsid ON DB_SCHEMA_NAME.variant_entry USING btree (rsid);

CREATE INDEX variant_frequency_index1 ON DB_SCHEMA_NAME.variant_frequency USING btree (variant_change_id);

CREATE INDEX variant_frequency_index2 ON DB_SCHEMA_NAME.variant_frequency USING btree (variant_freq_study_id);

CREATE INDEX variant_info_gn ON DB_SCHEMA_NAME.variant_info USING btree (variant_entry_id);

CREATE INDEX variant_info_s ON DB_SCHEMA_NAME.variant_info USING btree (source_id);

CREATE INDEX variant_pmid_map_index1 ON DB_SCHEMA_NAME.variant_pmid_map USING btree (variant_entry_id);

CREATE INDEX variant_pmid_map_index2 ON DB_SCHEMA_NAME.variant_pmid_map USING btree (pmid_entry_id);

CREATE INDEX variant_position_pos ON DB_SCHEMA_NAME.variant_position USING btree (chr_seq_pos_id);

CREATE INDEX variant_position_type ON DB_SCHEMA_NAME.variant_position USING btree (ref_all);

CREATE UNIQUE INDEX variant_prot_allele_uk ON DB_SCHEMA_NAME.variant_prot_allele USING btree (variant_prot_seq);

CREATE UNIQUE INDEX variant_prot_uk1 ON DB_SCHEMA_NAME.variant_protein_map USING btree (variant_transcript_id, prot_seq_id, prot_seq_pos_id, so_entry_id);

CREATE INDEX variant_protein_map_index1 ON DB_SCHEMA_NAME.variant_protein_map USING btree (variant_transcript_id);

CREATE INDEX variant_protein_map_index2 ON DB_SCHEMA_NAME.variant_protein_map USING btree (prot_seq_id);

CREATE INDEX variant_protein_map_index3 ON DB_SCHEMA_NAME.variant_protein_map USING btree (prot_seq_pos_id);

CREATE INDEX variant_protein_map_index4 ON DB_SCHEMA_NAME.variant_protein_map USING btree (so_entry_id);

CREATE INDEX variant_transcript_map_index1 ON DB_SCHEMA_NAME.variant_transcript_map USING btree (variant_change_id);

CREATE INDEX variant_transcript_map_index2 ON DB_SCHEMA_NAME.variant_transcript_map USING btree (transcript_id);

CREATE INDEX variant_transcript_map_index3 ON DB_SCHEMA_NAME.variant_transcript_map USING btree (transcript_pos_id);

CREATE INDEX variant_transcript_map_index4 ON DB_SCHEMA_NAME.variant_transcript_map USING btree (so_entry_id);

CREATE UNIQUE INDEX variant_transcript_uk_nullall ON DB_SCHEMA_NAME.variant_transcript_map USING btree (variant_change_id, transcript_id, transcript_pos_id, tr_ref_all, ((tr_alt_all IS NULL))) WHERE (tr_alt_all IS NULL);

CREATE UNIQUE INDEX web_job_uk ON DB_SCHEMA_NAME.web_job USING btree (md5id);

CREATE INDEX xc_xe ON DB_SCHEMA_NAME.xr_chain USING btree (xr_entry_id);

CREATE UNIQUE INDEX xc_xec ON DB_SCHEMA_NAME.xr_chain USING btree (xr_entry_id, chain_name);

CREATE INDEX xcudm_ud ON DB_SCHEMA_NAME.xr_ch_udom_map USING btree (prot_dom_id);

CREATE UNIQUE INDEX xcudm_udxc ON DB_SCHEMA_NAME.xr_ch_udom_map USING btree (prot_dom_id, xr_chain_id);

CREATE INDEX xcudm_xc ON DB_SCHEMA_NAME.xr_ch_udom_map USING btree (xr_chain_id);

CREATE UNIQUE INDEX xcudm_xcud ON DB_SCHEMA_NAME.xr_ch_udom_map USING btree (xr_chain_id, prot_dom_id);

CREATE INDEX xcum_ue ON DB_SCHEMA_NAME.xr_ch_prot_map USING btree (prot_seq_id);

CREATE UNIQUE INDEX xcum_uexc ON DB_SCHEMA_NAME.xr_ch_prot_map USING btree (prot_seq_id, xr_chain_id);

CREATE INDEX xcum_xc ON DB_SCHEMA_NAME.xr_ch_prot_map USING btree (xr_chain_id);

CREATE INDEX xcum_xcue ON DB_SCHEMA_NAME.xr_ch_prot_map USING btree (xr_chain_id, prot_seq_id);

CREATE UNIQUE INDEX xr_atom_atomres ON DB_SCHEMA_NAME.xr_atom USING btree ((
CASE
    WHEN (xr_tpl_atom_id IS NOT NULL) THEN xr_res_id
    ELSE NULL::bigint
END), (
CASE
    WHEN (xr_tpl_atom_id IS NOT NULL) THEN xr_tpl_atom_id
    ELSE NULL::bigint
END));

CREATE UNIQUE INDEX xr_bond_index1 ON DB_SCHEMA_NAME.xr_bond USING btree (xr_atom_id_1, xr_atom_id_2);

CREATE INDEX xr_bond_index2 ON DB_SCHEMA_NAME.xr_bond USING btree (xr_atom_id_2, xr_atom_id_1);

CREATE INDEX xr_bond_index3 ON DB_SCHEMA_NAME.xr_bond USING btree (xr_chain_id);

CREATE UNIQUE INDEX xr_entry_pmid_index1 ON DB_SCHEMA_NAME.xr_entry_pmid USING btree (xr_entry_id, pmid_entry_id);

CREATE UNIQUE INDEX xr_jobs_index1 ON DB_SCHEMA_NAME.xr_jobs USING btree (xr_job_name);

CREATE UNIQUE INDEX xr_lig_index1 ON DB_SCHEMA_NAME.xr_lig USING btree (lig_name);

CREATE UNIQUE INDEX xr_prot_dom_cov_index1 ON DB_SCHEMA_NAME.xr_prot_dom_cov USING btree (prot_dom_id, xr_chain_id);

CREATE UNIQUE INDEX xr_prot_dom_cov_index2 ON DB_SCHEMA_NAME.xr_prot_dom_cov USING btree (xr_chain_id, prot_dom_id);

CREATE INDEX xr_prot_int_stat_index1 ON DB_SCHEMA_NAME.xr_prot_int_stat USING btree (prot_entry_id);

CREATE INDEX xr_prot_int_stat_index3 ON DB_SCHEMA_NAME.xr_prot_int_stat USING btree (xr_inter_type_id);

CREATE UNIQUE INDEX xr_prot_int_stat_index4 ON DB_SCHEMA_NAME.xr_prot_int_stat USING btree (prot_seq_pos_id, xr_inter_type_id, atom_list, class);

CREATE UNIQUE INDEX xr_res_useqp_map_index1 ON DB_SCHEMA_NAME.xr_ch_prot_pos USING btree (xr_res_id, prot_seq_pos_id, xr_ch_prot_map_id);

CREATE INDEX xr_res_useqp_map_index2 ON DB_SCHEMA_NAME.xr_ch_prot_pos USING btree (prot_seq_pos_id, xr_res_id);

CREATE UNIQUE INDEX xr_status_index1 ON DB_SCHEMA_NAME.xr_status USING btree (xr_entry_id, xr_status_id);

CREATE INDEX xr_tpl_res_class_idx ON DB_SCHEMA_NAME.xr_tpl_res USING btree (class);

CREATE INDEX xr_tpl_res_index1 ON DB_SCHEMA_NAME.xr_tpl_res USING btree (smiles);

CREATE UNIQUE INDEX xr_tpl_res_name_uk ON DB_SCHEMA_NAME.xr_tpl_res USING btree (name);

CREATE INDEX year_pmid ON DB_SCHEMA_NAME.pmid_entry USING btree (date_part('year'::text, publication_date));



ALTER TABLE ONLY DB_SCHEMA_NAME.biorels_job_history
    ADD CONSTRAINT FK_biorels_job_history_job FOREIGN KEY (br_timestamp_id) REFERENCES DB_SCHEMA_NAME.biorels_timestamp(br_timestamp_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_ptm_disease_map
    ADD CONSTRAINT "FK_pmid_ptm_disease_map.pmid_entry_id" FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_ptm_disease_map
    ADD CONSTRAINT "FK_pmid_ptm_disease_map.ptm_disease_id" FOREIGN KEY (ptm_disease_id) REFERENCES DB_SCHEMA_NAME.ptm_disease(ptm_disease_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_abbreviations
    ADD CONSTRAINT "FK_ptm_abbreviations.ptm_type_id" FOREIGN KEY (ptm_type_id) REFERENCES DB_SCHEMA_NAME.ptm_type(ptm_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_disease
    ADD CONSTRAINT "FK_ptm_disease.ptm_seq_id" FOREIGN KEY (ptm_seq_id) REFERENCES DB_SCHEMA_NAME.ptm_seq(ptm_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_seq
    ADD CONSTRAINT "FK_ptm_seq.prot_seq_pos_id" FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_seq
    ADD CONSTRAINT "FK_ptm_seq.ptm_type_id" FOREIGN KEY (ptm_type_id) REFERENCES DB_SCHEMA_NAME.ptm_type(ptm_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_syn
    ADD CONSTRAINT "FK_ptm_syn.ptm_type_id" FOREIGN KEY (ptm_type_id) REFERENCES DB_SCHEMA_NAME.ptm_type(ptm_type_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_var
    ADD CONSTRAINT "FK_ptm_var.prot_seq_pos_id" FOREIGN KEY (ptm_var_prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_var
    ADD CONSTRAINT "FK_ptm_var.ptm_seq_id" FOREIGN KEY (ptm_seq_id) REFERENCES DB_SCHEMA_NAME.ptm_seq(ptm_seq_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_component_sm_map
    ADD CONSTRAINT molecular_component_sm_map_fk1 FOREIGN KEY (molecular_component_id) REFERENCES DB_SCHEMA_NAME.molecular_component(molecular_component_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_component_sm_map
    ADD CONSTRAINT molecular_component_sm_map_fk2 FOREIGN KEY (sm_entry_id) REFERENCES DB_SCHEMA_NAME.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.activity_entry
    ADD CONSTRAINT activity_entry_fk1 FOREIGN KEY (assay_entry_id) REFERENCES DB_SCHEMA_NAME.assay_entry(assay_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.activity_entry
    ADD CONSTRAINT activity_entry_fk2 FOREIGN KEY (bao_endpoint) REFERENCES DB_SCHEMA_NAME.bioassay_onto_entry(bioassay_onto_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.activity_entry
    ADD CONSTRAINT activity_entry_fk3 FOREIGN KEY (molecular_entity_id) REFERENCES DB_SCHEMA_NAME.molecular_entity(molecular_entity_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.activity_entry
    ADD CONSTRAINT activity_entry_fk4 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_entry
    ADD CONSTRAINT anatomy_entry_source_fk FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_extdb
    ADD CONSTRAINT anatomy_extdb_fk1 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_extdb
    ADD CONSTRAINT anatomy_extdb_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_hierarchy
    ADD CONSTRAINT anatomy_hierarchy_fk1 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_syn
    ADD CONSTRAINT anatomy_syn_entry_fk1 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.anatomy_syn
    ADD CONSTRAINT anatomy_syn_source_fk FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_cell
    ADD CONSTRAINT assay_cell_fk1 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.assay_cell
    ADD CONSTRAINT assay_cell_fk2 FOREIGN KEY (cell_entry_id) REFERENCES DB_SCHEMA_NAME.cell_entry(cell_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_fk1 FOREIGN KEY (assay_cell_id) REFERENCES DB_SCHEMA_NAME.assay_cell(assay_cell_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_fk3 FOREIGN KEY (assay_tissue_id) REFERENCES DB_SCHEMA_NAME.assay_tissue(assay_tissue_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_fk4 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_fk5 FOREIGN KEY (assay_variant_id) REFERENCES DB_SCHEMA_NAME.assay_variant(assay_variant_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_fk6 FOREIGN KEY (confidence_score) REFERENCES DB_SCHEMA_NAME.assay_confidence(confidence_score) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_fk7 FOREIGN KEY (assay_type) REFERENCES DB_SCHEMA_NAME.assay_type(assay_type_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_entry
    ADD CONSTRAINT assay_entry_fk8 FOREIGN KEY (assay_target_id) REFERENCES DB_SCHEMA_NAME.assay_target(assay_target_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_genetic
    ADD CONSTRAINT assay_genetic_fk1 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_genetic
    ADD CONSTRAINT assay_genetic_fk2 FOREIGN KEY (gene_seq_id) REFERENCES DB_SCHEMA_NAME.gene_seq(gene_seq_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_genetic
    ADD CONSTRAINT assay_genetic_fk3 FOREIGN KEY (transcript_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_pmid
    ADD CONSTRAINT assay_pmid_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_pmid
    ADD CONSTRAINT assay_pmid_fk2 FOREIGN KEY (assay_entry_id) REFERENCES DB_SCHEMA_NAME.assay_entry(assay_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_protein
    ADD CONSTRAINT assay_protein_fk1 FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_protein
    ADD CONSTRAINT assay_protein_fk2 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target
    ADD CONSTRAINT assay_target_fk1 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target
    ADD CONSTRAINT assay_target_fk2 FOREIGN KEY (assay_target_type_id) REFERENCES DB_SCHEMA_NAME.assay_target_type(assay_target_type_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_variant
    ADD CONSTRAINT assay_variant_fk1 FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_variant_pos
    ADD CONSTRAINT assay_variant_pos_fk1 FOREIGN KEY (assay_variant_id) REFERENCES DB_SCHEMA_NAME.assay_variant(assay_variant_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_variant_pos
    ADD CONSTRAINT assay_variant_pos_fk2 FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_variant_pos
    ADD CONSTRAINT assay_variant_pos_fk3 FOREIGN KEY (variant_protein_id) REFERENCES DB_SCHEMA_NAME.variant_protein_map(variant_protein_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.ATC_hierarchy
    ADD CONSTRAINT ATC_hierarchy_fk1 FOREIGN KEY (atc_entry_id) REFERENCES DB_SCHEMA_NAME.ATC_entry(atc_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.bioassay_onto_extdb
    ADD CONSTRAINT bioassay_onto_extdb_fk1 FOREIGN KEY (bioassay_onto_entry_id) REFERENCES DB_SCHEMA_NAME.bioassay_onto_entry(bioassay_onto_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.bioassay_onto_extdb
    ADD CONSTRAINT bioassay_onto_extdb_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.bioassay_onto_hierarchy
    ADD CONSTRAINT bioassay_onto_hierarchy_fk1 FOREIGN KEY (bioassay_onto_entry_id) REFERENCES DB_SCHEMA_NAME.bioassay_onto_entry(bioassay_onto_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_disease
    ADD CONSTRAINT cell_disease_fk1 FOREIGN KEY (cell_entry_id) REFERENCES DB_SCHEMA_NAME.cell_entry(cell_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_disease
    ADD CONSTRAINT cell_disease_fk2 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_disease
    ADD CONSTRAINT cell_disease_fk3 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.cell_entry
    ADD CONSTRAINT cell_entry_fk_tissue FOREIGN KEY (cell_tissue_id) REFERENCES DB_SCHEMA_NAME.cell_tissue(cell_tissue_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_patent_map
    ADD CONSTRAINT cell_patent_map_fk1 FOREIGN KEY (cell_entry_id) REFERENCES DB_SCHEMA_NAME.cell_entry(cell_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_patent_map
    ADD CONSTRAINT cell_patent_map_fk2 FOREIGN KEY (patent_entry_id) REFERENCES DB_SCHEMA_NAME.patent_entry(patent_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_patent_map
    ADD CONSTRAINT cell_patent_map_fk3 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_pmid_map
    ADD CONSTRAINT cell_pmid_map_fk1 FOREIGN KEY (cell_entry_id) REFERENCES DB_SCHEMA_NAME.cell_entry(cell_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_pmid_map
    ADD CONSTRAINT cell_pmid_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_pmid_map
    ADD CONSTRAINT cell_pmid_map_fk3 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.cell_syn
    ADD CONSTRAINT cell_syn_fk1 FOREIGN KEY (cell_entry_id) REFERENCES DB_SCHEMA_NAME.cell_entry(cell_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_syn
    ADD CONSTRAINT cell_syn_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_taxon_map
    ADD CONSTRAINT cell_taxon_map_fk1 FOREIGN KEY (cell_entry_id) REFERENCES DB_SCHEMA_NAME.cell_entry(cell_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_taxon_map
    ADD CONSTRAINT cell_taxon_map_fk2 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.cell_taxon_map
    ADD CONSTRAINT cell_taxon_map_fk3 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.cell_tissue
    ADD CONSTRAINT cell_tissue_anatomy_fk1 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_tissue
    ADD CONSTRAINT cell_tissue_anatomy_fk1 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_seq
    ADD CONSTRAINT chr_seq_fk1 FOREIGN KEY (chr_id) REFERENCES DB_SCHEMA_NAME.chromosome(chr_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_seq
    ADD CONSTRAINT chr_seq_fk2 FOREIGN KEY (genome_assembly_id) REFERENCES DB_SCHEMA_NAME.genome_assembly(genome_assembly_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_seq_pos
    ADD CONSTRAINT chr_seq_pos_fk1 FOREIGN KEY (chr_seq_id) REFERENCES DB_SCHEMA_NAME.chr_seq(chr_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_company_map
    ADD CONSTRAINT clinical_trial_company_map_clinical_trial_id_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_company_map
    ADD CONSTRAINT clinical_trial_company_map_company_entry_id_fkey FOREIGN KEY (company_entry_id) REFERENCES DB_SCHEMA_NAME.company_entry(company_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_date
    ADD CONSTRAINT clinical_trial_date_clinical_trial_id_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_condition
    ADD CONSTRAINT clinical_trial_condition_ct_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_condition
    ADD CONSTRAINT clinical_trial_condition_ds_fkey FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map
    ADD CONSTRAINT clinical_trial_intervention_drug_map_pkey PRIMARY KEY (clinical_trial_intervention_drug_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_Trial_intervention_drug_map
    ADD CONSTRAINT cti_dr_map_cti_fk FOREIGN KEY (clinical_trial_intervention_id) REFERENCES DB_SCHEMA_NAME.clinical_trial_intervention(clinical_trial_intervention_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_intervention_drug_map
    ADD CONSTRAINT ctidm_s_fk FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_intervention_drug_map
    ADD CONSTRAINT cti_dr_map_dr_fk FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_arm_intervention_map
    ADD CONSTRAINT ctai_map_pk PRIMARY KEY (clinical_trial_arm_intervention_map_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_arm_intervention_map
    ADD CONSTRAINT ctai_map_ct_fk FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_arm_intervention_map
    ADD CONSTRAINT ctai_map_arm_fk FOREIGN KEY (clinical_trial_arm_id) REFERENCES DB_SCHEMA_NAME.clinical_trial_arm(clinical_trial_arm_id) ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_arm_intervention_map
    ADD CONSTRAINT ctai_map_int_fk FOREIGN KEY (clinical_trial_intervention_id) REFERENCES DB_SCHEMA_NAME.clinical_trial_intervention(clinical_trial_intervention_id) ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_arm
    ADD CONSTRAINT clinical_trial_arm_clinical_trial_id_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_intervention
    ADD CONSTRAINT cti_ct_id_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial
    ADD CONSTRAINT clinical_trial_fk3 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_pmid_map
    ADD CONSTRAINT clinical_trial_pmid_map_clinical_trial_id_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_pmid_map
    ADD CONSTRAINT clinical_trial_pmid_map_pmid_entry_id_fkey FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_drug
    ADD CONSTRAINT clinical_trial_drug_pkey PRIMARY KEY (clinical_trial_drug_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_drug
    ADD CONSTRAINT clinical_trial_drug_clinical_trial_id_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_drug
    ADD CONSTRAINT clinical_trial_drug_drug_disease_id_fkey FOREIGN KEY (drug_disease_id) REFERENCES DB_SCHEMA_NAME.drug_disease(drug_disease_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_entry
    ADD CONSTRAINT clinvar_entry_review_fk FOREIGN KEY (clinical_variant_review_status) REFERENCES DB_SCHEMA_NAME.clinical_variant_review_status(clinvar_review_status_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_entry
    ADD CONSTRAINT clinvar_entry_type_fk FOREIGN KEY (clinical_variant_type_id) REFERENCES DB_SCHEMA_NAME.clinical_variant_type(clinical_variant_type_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_pmid_map
    ADD CONSTRAINT clinvar_pmid_map_fk1 FOREIGN KEY (clinvar_submission_id) REFERENCES DB_SCHEMA_NAME.clinical_variant_submission(clinvar_submission_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_pmid_map
    ADD CONSTRAINT clinvar_pmid_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_type
    ADD CONSTRAINT clinvar_type_so FOREIGN KEY (so_entry_id) REFERENCES DB_SCHEMA_NAME.so_entry(so_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_disease_map
    ADD CONSTRAINT clinvar_variant_disease_map_fk1 FOREIGN KEY (clinvar_submission_id) REFERENCES DB_SCHEMA_NAME.clinical_variant_submission(clinvar_submission_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_disease_map
    ADD CONSTRAINT clinvar_variant_disease_map_fk2 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_gn_map
    ADD CONSTRAINT clinvar_variant_gn_map_fk1 FOREIGN KEY (clinvar_submission_id) REFERENCES DB_SCHEMA_NAME.clinical_variant_submission(clinvar_submission_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_gn_map
    ADD CONSTRAINT clinvar_variant_gn_map_fk2 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_map
    ADD CONSTRAINT clinvar_variant_map_fk1 FOREIGN KEY (clinvar_entry_id) REFERENCES DB_SCHEMA_NAME.clinical_variant_entry(clinvar_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_map
    ADD CONSTRAINT clinvar_variant_map_fk2 FOREIGN KEY (variant_entry_id) REFERENCES DB_SCHEMA_NAME.variant_entry(variant_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_submission
    ADD CONSTRAINT clinvar_variant_sub_fk1 FOREIGN KEY (clin_sign_id) REFERENCES DB_SCHEMA_NAME.clinical_significance(clin_sign_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_submission
    ADD CONSTRAINT clinvar_variant_sub_fk2 FOREIGN KEY (clinvar_entry_id) REFERENCES DB_SCHEMA_NAME.clinical_variant_entry(clinvar_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_variant_submission
    ADD CONSTRAINT clinvar_variant_sub_fk3 FOREIGN KEY (clinical_variant_review_status) REFERENCES DB_SCHEMA_NAME.clinical_variant_review_status(clinvar_review_status_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.company_synonym
    ADD CONSTRAINT company_syn_entry_fk FOREIGN KEY (company_entry_id) REFERENCES DB_SCHEMA_NAME.company_entry(company_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.ptm_disease
    ADD CONSTRAINT constraint_name FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id);

ALTER TABLE ONLY DB_SCHEMA_NAME.clinical_trial_alias
    ADD CONSTRAINT cta_fk_ct FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_anatomy_map
    ADD CONSTRAINT disease_anatomy_fk1 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_anatomy_map
    ADD CONSTRAINT disease_anatomy_fk2 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_anatomy_map
    ADD CONSTRAINT disease_anatomy_fk3 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_entry
    ADD CONSTRAINT disease_entry_fk1 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_extdb
    ADD CONSTRAINT disease_extdb_fk1 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_extdb
    ADD CONSTRAINT disease_extdb_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_gene_acc
    ADD CONSTRAINT disease_gene_acc_fk1 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_gene_acc
    ADD CONSTRAINT disease_gene_acc_fk2 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_hierarchy
    ADD CONSTRAINT disease_hierarchy_fk1 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_info
    ADD CONSTRAINT disease_info_fk1 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_info
    ADD CONSTRAINT disease_info_fk2 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_pmid
    ADD CONSTRAINT disease_pmid_fk1 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_pmid
    ADD CONSTRAINT disease_pmid_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_syn
    ADD CONSTRAINT disease_syn_fk1 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.disease_syn
    ADD CONSTRAINT disease_syn_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_disease
    ADD CONSTRAINT drug_disease_fk1 FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_disease
    ADD CONSTRAINT drug_disease_fk2 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_disease
    ADD CONSTRAINT drug_disease_fk3 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.Drug_ATC_map
    ADD CONSTRAINT fk_dr_atc_m FOREIGN KEY (ATC_entry_id) REFERENCES  DB_SCHEMA_NAME.ATC_entry(ATC_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE DB_SCHEMA_NAME.Drug_ATC_map
    ADD CONSTRAINT fk_dr_atc_d FOREIGN KEY (drug_entry_id) REFERENCES  DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;
    
ALTER TABLE ONLY DB_SCHEMA_NAME.drug_extdb
    ADD CONSTRAINT drug_extdb_d FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_extdb
    ADD CONSTRAINT drug_extdb_s FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_extdb
    ADD CONSTRAINT drug_extdb_s2 FOREIGN KEY (source_origin_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_name
    ADD CONSTRAINT drugs_name_fk1 FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_name
    ADD CONSTRAINT drugs_name_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_type_map
    ADD CONSTRAINT drugs_type_map_fk1 FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.drug_type_map
    ADD CONSTRAINT drugs_type_map_fk2 FOREIGN KEY (drug_type_id) REFERENCES DB_SCHEMA_NAME.drug_type(drug_type_id) ON UPDATE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.drug_description 
    ADD CONSTRAINT fk_dr_de_dr FOREIGN KEY (drug_entry_id) REFERENCES drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.drug_mol_entity_map 
    ADD CONSTRAINT fk_de_sm_de FOREIGN KEY (drug_entry_id) REFERENCES drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.drug_mol_entity_map 
    ADD CONSTRAINT fk_de_sm_sm FOREIGN KEY (molecular_entity_id) REFERENCES molecular_entity(molecular_entity_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE DB_SCHEMA_NAME.drug_mol_entity_map 
    ADD CONSTRAINT fk_de_sm_so FOREIGN KEY (source_id) REFERENCES source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE DB_SCHEMA_NAME.Drug_description 
    ADD CONSTRAINT fk_dr_de_s FOREIGN KEY (source_id) REFERENCES  source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE DB_SCHEMA_NAME.Drug_type_map
    ADD CONSTRAINT fk_dr_ty_m FOREIGN KEY (drug_type_id) REFERENCES  drug_type(drug_type_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE DB_SCHEMA_NAME.Drug_type_map
    ADD CONSTRAINT fk_dr_ty_d FOREIGN KEY (drug_entry_id) REFERENCES  drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.eco_hierarchy
    ADD CONSTRAINT eco_hierarchy_fk1 FOREIGN KEY (eco_entry_id) REFERENCES DB_SCHEMA_NAME.eco_entry(eco_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.eco_rel
    ADD CONSTRAINT eco_rel_fk1 FOREIGN KEY (eco_entry_ref) REFERENCES DB_SCHEMA_NAME.eco_entry(eco_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.eco_rel
    ADD CONSTRAINT eco_rel_fk2 FOREIGN KEY (eco_entry_comp) REFERENCES DB_SCHEMA_NAME.eco_entry(eco_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.efo_extdb
    ADD CONSTRAINT efo_extdb_fk1 FOREIGN KEY (efo_entry_id) REFERENCES DB_SCHEMA_NAME.efo_entry(efo_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.efo_extdb
    ADD CONSTRAINT efo_extdb_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.efo_hierarchy
    ADD CONSTRAINT efo_hierarchy_fk1 FOREIGN KEY (efo_entry_id) REFERENCES DB_SCHEMA_NAME.efo_entry(efo_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target_genetic_map
    ADD CONSTRAINT fk1_assay_target_genetic_gn FOREIGN KEY (assay_genetic_id) REFERENCES DB_SCHEMA_NAME.assay_genetic(assay_genetic_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target_genetic_map
    ADD CONSTRAINT fk1_assay_target_genetic_tg FOREIGN KEY (assay_target_id) REFERENCES DB_SCHEMA_NAME.assay_target(assay_target_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target_protein_map
    ADD CONSTRAINT fk1_assay_target_protein_pr FOREIGN KEY (assay_protein_id) REFERENCES DB_SCHEMA_NAME.assay_protein(assay_protein_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.assay_target_protein_map
    ADD CONSTRAINT fk1_assay_target_protein_tg FOREIGN KEY (assay_target_id) REFERENCES DB_SCHEMA_NAME.assay_target(assay_target_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_gn_map
    ADD CONSTRAINT fk_cgm_to_chr_map FOREIGN KEY (chr_map_id) REFERENCES DB_SCHEMA_NAME.chr_map(chr_map_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_gn_map
    ADD CONSTRAINT fk_cgm_to_gn_entry FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.chr_map
    ADD CONSTRAINT fk_chr_map_to_chr FOREIGN KEY (chr_id) REFERENCES DB_SCHEMA_NAME.chromosome(chr_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.chromosome
    ADD CONSTRAINT fk_chro_to_tax FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.codon
    ADD CONSTRAINT fk_codon_translation FOREIGN KEY (translation_tbl_id) REFERENCES DB_SCHEMA_NAME.translation_tbl(translation_tbl_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_rel
    ADD CONSTRAINT fk_gn_ortho_c_gn_entry FOREIGN KEY (gn_entry_c_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_rel
    ADD CONSTRAINT fk_gn_ortho_r_gn_entry FOREIGN KEY (gn_entry_r_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_prot_map
    ADD CONSTRAINT fk_gn_prot_map_to_gn_entry FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_prot_map
    ADD CONSTRAINT fk_gn_prot_map_to_prot_entry FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_syn_map
    ADD CONSTRAINT fk_gsm_to_gn_entry FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_syn_map
    ADD CONSTRAINT fk_gsm_to_gn_syn FOREIGN KEY (gn_syn_id) REFERENCES DB_SCHEMA_NAME.gn_syn(gn_syn_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_bond
    ADD CONSTRAINT fk_id_atom1 FOREIGN KEY (xr_tpl_atom_id_1) REFERENCES DB_SCHEMA_NAME.xr_tpl_atom(xr_tpl_atom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_bond
    ADD CONSTRAINT fk_id_atom2 FOREIGN KEY (xr_tpl_atom_id_2) REFERENCES DB_SCHEMA_NAME.xr_tpl_atom(xr_tpl_atom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_atom
    ADD CONSTRAINT fk_id_residue FOREIGN KEY (xr_tpl_res_id) REFERENCES DB_SCHEMA_NAME.xr_tpl_res(xr_tpl_res_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_res
    ADD CONSTRAINT fk_id_residue_replacedby FOREIGN KEY (replaced_by_id) REFERENCES DB_SCHEMA_NAME.xr_tpl_res(xr_tpl_res_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_atom
    ADD CONSTRAINT fk_id_residueatom FOREIGN KEY (xr_tpl_atom_id) REFERENCES DB_SCHEMA_NAME.xr_tpl_atom(xr_tpl_atom_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_atom
    ADD CONSTRAINT fk_id_xr_element FOREIGN KEY (xr_element_id) REFERENCES DB_SCHEMA_NAME.xr_element(xr_element_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_atom
    ADD CONSTRAINT fk_id_xrayresidue FOREIGN KEY (xr_res_id) REFERENCES DB_SCHEMA_NAME.xr_res(xr_res_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_ext_db
    ADD CONSTRAINT fk_ied_ie FOREIGN KEY (ip_entry_id) REFERENCES DB_SCHEMA_NAME.ip_entry(ip_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.internal_library_molecular_map  
    ADD CONSTRAINT internal_lib_mol_lib_fk FOREIGN KEY (internal_library_id) REFERENCES DB_SCHEMA_NAME.internal_library(internal_library_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE DB_SCHEMA_NAME.internal_library_molecular_map  
    ADD CONSTRAINT internal_lib_mol_mol_fk FOREIGN KEY (internal_molecule_id) REFERENCES DB_SCHEMA_NAME.internal_molecule(internal_molecule_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE DB_SCHEMA_NAME.internal_molecule  
    ADD CONSTRAINT internal_mol_mol_en_fk FOREIGN KEY (molecular_entity_id) REFERENCES DB_SCHEMA_NAME.molecular_entity(molecular_entity_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE DB_SCHEMA_NAME.internal_library  
    ADD CONSTRAINT internal_lib_creator_fk FOREIGN KEY (internal_library_creator_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_SCHEMA_NAME.web_user_stat
    ADD CONSTRAINT fk_llus_usr FOREIGN KEY (web_user_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.mod_pattern_pos
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_mod_pattern_pos_mod_pattern_id FOREIGN KEY (mod_pattern_id) REFERENCES DB_SCHEMA_NAME.mod_pattern(mod_pattern_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.news
    ADD CONSTRAINT fk_news_source FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_match
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_match_comp_type_id FOREIGN KEY (comp_type_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_type(nucleic_acid_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_match
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_match_ref_type_id FOREIGN KEY (ref_type_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_type(nucleic_acid_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_pos_match
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_pos_match_nucleic_acid_pos_matc FOREIGN KEY (nucleic_acid_match_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_match(nucleic_acid_match_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_seq_mod_pattern_id FOREIGN KEY (mod_pattern_id) REFERENCES DB_SCHEMA_NAME.mod_pattern(mod_pattern_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_seq_nucleic_acid_type_id FOREIGN KEY (nucleic_acid_type_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_type(nucleic_acid_type_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_seq_parent_seq_id FOREIGN KEY (parent_seq_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_seq(nucleic_acid_seq_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq_pos
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_seq_pos_nucleic_acid_seq_id FOREIGN KEY (nucleic_acid_seq_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_seq(nucleic_acid_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq_pos
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_seq_pos_nucleic_acid_type_id FOREIGN KEY (nucleic_acid_struct_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_struct(nucleic_acid_struct_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_target
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_target_nucleic_acid_seq_id FOREIGN KEY (nucleic_acid_seq_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_seq(nucleic_acid_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_target
    ADD CONSTRAINT fk_DB_SCHEMA_NAME_nucleic_acid_target_transcript_pos_id FOREIGN KEY (transcript_pos_id) REFERENCES DB_SCHEMA_NAME.transcript_pos(transcript_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.nucleic_acid_seq_prop
    ADD CONSTRAINT nasp_nasid_fk FOREIGN KEY (nucleic_acid_seq_id) REFERENCES DB_SCHEMA_NAME.nucleic_acid_seq(nucleic_acid_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_entry
    ADD CONSTRAINT fk_pmc_ft_pmid FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_go_map
    ADD CONSTRAINT fk_pmid_ft_go_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_go_map
    ADD CONSTRAINT fk_pmid_ft_go_dr FOREIGN KEY (go_entry_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext
    ADD CONSTRAINT fk_pmid_ft_pm FOREIGN KEY (pmc_entry_id) REFERENCES DB_SCHEMA_NAME.pmc_entry(pmc_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext
    ADD CONSTRAINT fk_pmid_ft_sc FOREIGN KEY (pmc_section_id) REFERENCES DB_SCHEMA_NAME.pmc_section(pmc_section_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_drug_map
    ADD CONSTRAINT fk_pmid_ft_drug_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_drug_map
    ADD CONSTRAINT fk_pmid_ft_drug_dr FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_disease_map
    ADD CONSTRAINT fk_pmid_ft_disease_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_disease_map
    ADD CONSTRAINT fk_pmid_ft_disease_dr FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_anatomy_map
    ADD CONSTRAINT fk_pmid_ft_anatomy_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_anatomy_map
    ADD CONSTRAINT fk_pmid_ft_anatomy_dr FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_gn_map
    ADD CONSTRAINT fk_pmid_ft_gn_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_gn_map
    ADD CONSTRAINT fk_pmid_ft_gn_dr FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_ontology_map
    ADD CONSTRAINT fk_pmid_ft_onto_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_ontology_map
    ADD CONSTRAINT fk_pmid_ft_onto_dr FOREIGN KEY (ontology_entry_id) REFERENCES DB_SCHEMA_NAME.ontology_entry(ontology_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_company_map
    ADD CONSTRAINT fk_pmid_ft_company_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_company_map
    ADD CONSTRAINT fk_pmid_ft_company_dr FOREIGN KEY (company_entry_id) REFERENCES DB_SCHEMA_NAME.company_entry(company_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_cell_map
    ADD CONSTRAINT fk_pmid_ft_cell_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_cell_map
    ADD CONSTRAINT fk_pmid_ft_cell_dr FOREIGN KEY (cell_entry_id) REFERENCES DB_SCHEMA_NAME.cell_entry(cell_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_clinical_map
    ADD CONSTRAINT fk_pmid_ft_clinical_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_clinical_map
    ADD CONSTRAINT fk_pmid_ft_clinical_dr FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_sm_map
    ADD CONSTRAINT fk_pmid_ft_sm_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_sm_map
    ADD CONSTRAINT fk_pmid_ft_sm_dr FOREIGN KEY (sm_entry_id) REFERENCES DB_SCHEMA_NAME.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_pub_map
    ADD CONSTRAINT fk_pmid_ft_sm_ft FOREIGN KEY (pmc_fulltext_id) REFERENCES DB_SCHEMA_NAME.pmc_fulltext(pmc_fulltext_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_pub_map
    ADD CONSTRAINT fk_pmid_ft_sm_dr FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY DB_SCHEMA_NAME.prot_ac
    ADD CONSTRAINT fk_prot_ac_to_prot_entry FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_seq
    ADD CONSTRAINT fk_prot_dom_seq_to_prot_dom FOREIGN KEY (prot_dom_id) REFERENCES DB_SCHEMA_NAME.prot_dom(prot_dom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_seq
    ADD CONSTRAINT fk_prot_dom_seq_to_prot_seq_pos FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom
    ADD CONSTRAINT fk_prot_dom_to_prot_entry FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_entry
    ADD CONSTRAINT fk_prot_entry_to_tax_id FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_seq
    ADD CONSTRAINT fk_prot_feat_seq_to_prot_feat FOREIGN KEY (prot_feat_id) REFERENCES DB_SCHEMA_NAME.prot_feat(prot_feat_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_seq
    ADD CONSTRAINT fk_prot_feat_seq_to_prot_seq_pos FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat
    ADD CONSTRAINT fk_prot_feat_to_prot_seq FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_pos
    ADD CONSTRAINT fk_prot_seq_pos_to_prot_seq FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq
    ADD CONSTRAINT fk_prot_seq_to_prot_entry FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_entry
    ADD CONSTRAINT fk_pwe_tax FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_gn_map
    ADD CONSTRAINT fk_pwgm_gn FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_gn_map
    ADD CONSTRAINT fk_pwgm_pw FOREIGN KEY (pw_entry_id) REFERENCES DB_SCHEMA_NAME.pw_entry(pw_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_al
    ADD CONSTRAINT fk_uda_comp_to_ud FOREIGN KEY (prot_dom_comp_id) REFERENCES DB_SCHEMA_NAME.prot_dom(prot_dom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_al
    ADD CONSTRAINT fk_uda_ref_to_ud FOREIGN KEY (prot_dom_ref_id) REFERENCES DB_SCHEMA_NAME.prot_dom(prot_dom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_al_seq
    ADD CONSTRAINT fk_uda_to_uda FOREIGN KEY (prot_dom_al_id) REFERENCES DB_SCHEMA_NAME.prot_dom_al(prot_dom_al_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_al_seq
    ADD CONSTRAINT fk_udas_comp_to_uds FOREIGN KEY (prot_dom_seq_id_comp) REFERENCES DB_SCHEMA_NAME.prot_dom_seq(prot_dom_seq_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_dom_al_seq
    ADD CONSTRAINT fk_udas_ref_to_uds FOREIGN KEY (prot_dom_seq_id_ref) REFERENCES DB_SCHEMA_NAME.prot_dom_seq(prot_dom_seq_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_name_map
    ADD CONSTRAINT fk_upn_map_to_prot_entry FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_name_map
    ADD CONSTRAINT fk_upn_map_to_prot_name FOREIGN KEY (prot_name_id) REFERENCES DB_SCHEMA_NAME.prot_name(prot_name_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_al
    ADD CONSTRAINT fk_usa_comp_to_us FOREIGN KEY (prot_seq_comp_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_al
    ADD CONSTRAINT fk_usa_ref_to_us FOREIGN KEY (prot_seq_ref_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_al_seq
    ADD CONSTRAINT fk_usas_comp_to_usp FOREIGN KEY (prot_seq_id_ref) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_al_seq
    ADD CONSTRAINT fk_usas_ref_to_usp FOREIGN KEY (prot_seq_id_comp) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_seq_al_seq
    ADD CONSTRAINT fk_usas_to_usa FOREIGN KEY (prot_seq_al_id) REFERENCES DB_SCHEMA_NAME.prot_seq_al(prot_seq_al_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_lig_map
    ADD CONSTRAINT fk_xclm_xrch FOREIGN KEY (xr_chain_id) REFERENCES DB_SCHEMA_NAME.xr_chain(xr_chain_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_lig_map
    ADD CONSTRAINT fk_xclm_xrlig FOREIGN KEY (xr_lig_id) REFERENCES DB_SCHEMA_NAME.xr_lig(xr_lig_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_lig_map
    ADD CONSTRAINT fk_xclm_xrsite FOREIGN KEY (xr_site_id) REFERENCES DB_SCHEMA_NAME.xr_site(xr_site_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_site_res
    ADD CONSTRAINT fk_xr_site_res_res FOREIGN KEY (xr_res_id) REFERENCES DB_SCHEMA_NAME.xr_res(xr_res_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_site_res
    ADD CONSTRAINT fk_xr_site_res_xr_site FOREIGN KEY (xr_site_id) REFERENCES DB_SCHEMA_NAME.xr_site(xr_site_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_chain
    ADD CONSTRAINT fk_xrc_2_xre FOREIGN KEY (xr_entry_id) REFERENCES DB_SCHEMA_NAME.xr_entry(xr_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_udom_map
    ADD CONSTRAINT fk_xrcdm_2_ud FOREIGN KEY (prot_dom_id) REFERENCES DB_SCHEMA_NAME.prot_dom(prot_dom_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_udom_map
    ADD CONSTRAINT fk_xrcdm_2_xrc FOREIGN KEY (xr_chain_id) REFERENCES DB_SCHEMA_NAME.xr_chain(xr_chain_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_prot_map
    ADD CONSTRAINT fk_xrcum_2_ue FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_prot_map
    ADD CONSTRAINT fk_xrcum_2_xrc FOREIGN KEY (xr_chain_id) REFERENCES DB_SCHEMA_NAME.xr_chain(xr_chain_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ppi
    ADD CONSTRAINT fk_xrppi_2_xrc_c FOREIGN KEY (xr_chain_c_id) REFERENCES DB_SCHEMA_NAME.xr_chain(xr_chain_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ppi
    ADD CONSTRAINT fk_xrppi_2_xrc_r FOREIGN KEY (xr_chain_r_id) REFERENCES DB_SCHEMA_NAME.xr_chain(xr_chain_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gene_seq
    ADD CONSTRAINT gene_seq_fk1 FOREIGN KEY (biotype_id) REFERENCES DB_SCHEMA_NAME.seq_btype(seq_btype_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gene_seq
    ADD CONSTRAINT gene_seq_fk2 FOREIGN KEY (chr_seq_id) REFERENCES DB_SCHEMA_NAME.chr_seq(chr_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gene_seq
    ADD CONSTRAINT gene_seq_fk3 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.genome_assembly
    ADD CONSTRAINT genome_assembly_fk1 FOREIGN KEY (taxon_id) REFERENCES DB_SCHEMA_NAME.taxon(taxon_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_info
    ADD CONSTRAINT gn_info_fk1 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gn_info
    ADD CONSTRAINT gn_info_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;


ALTER TABLE ONLY DB_SCHEMA_NAME.gn_history
    ADD CONSTRAINT gn_history_fk1 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_dbref
    ADD CONSTRAINT go_dbref_go_entry_fk1 FOREIGN KEY (go_entry_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_dbref
    ADD CONSTRAINT go_dbref_source FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_pmid_map
    ADD CONSTRAINT go_pmid_map_fk1 FOREIGN KEY (go_entry_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_pmid_map
    ADD CONSTRAINT go_pmid_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_go_map
    ADD CONSTRAINT go_prot_map_go_entry_fk1 FOREIGN KEY (go_entry_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_prot_map_prev
    ADD CONSTRAINT go_prot_map_go_entry_fk1_prev FOREIGN KEY (go_entry_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_go_map
    ADD CONSTRAINT go_prot_map_prot_entry_fk1 FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_prot_map_prev
    ADD CONSTRAINT go_prot_map_prot_entry_fk1_prev FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_rel
    ADD CONSTRAINT go_rel_go_entry_fk1 FOREIGN KEY (go_to_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_rel
    ADD CONSTRAINT go_rel_go_entry_fk2 FOREIGN KEY (go_from_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.go_syn
    ADD CONSTRAINT go_syn_go_entry_fk1 FOREIGN KEY (go_entry_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_phenotype
    ADD CONSTRAINT gwas_phenotype_fk1 FOREIGN KEY (gwas_study_id) REFERENCES DB_SCHEMA_NAME.gwas_study(gwas_study_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_variant_prop
    ADD CONSTRAINT gwas_variant_prop_fk1 FOREIGN KEY (gwas_variant_id) REFERENCES DB_SCHEMA_NAME.gwas_variant(gwas_variant_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.gwas_variant_prop
    ADD CONSTRAINT gwas_variant_prop_fk2 FOREIGN KEY (gwas_descriptor_id) REFERENCES DB_SCHEMA_NAME.gwas_descriptor(gwas_descriptor_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_go_map
    ADD CONSTRAINT ip_go_map_fk1 FOREIGN KEY (go_entry_id) REFERENCES DB_SCHEMA_NAME.go_entry(go_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_go_map
    ADD CONSTRAINT ip_go_map_fk2 FOREIGN KEY (ip_entry_id) REFERENCES DB_SCHEMA_NAME.ip_entry(ip_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_pmid_map
    ADD CONSTRAINT ip_pmid_map_fk1 FOREIGN KEY (ip_entry_id) REFERENCES DB_SCHEMA_NAME.ip_entry(ip_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_pmid_map
    ADD CONSTRAINT ip_pmid_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_sign_prot_seq
    ADD CONSTRAINT ip_sign_prot_seq_fk1 FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_sign_prot_seq
    ADD CONSTRAINT ip_sign_prot_seq_fk2 FOREIGN KEY (ip_signature_id) REFERENCES DB_SCHEMA_NAME.ip_signature(ip_signature_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.ip_signature
    ADD CONSTRAINT ip_signature_fk1 FOREIGN KEY (ip_entry_id) REFERENCES DB_SCHEMA_NAME.ip_entry(ip_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.lipid_hierarchy
    ADD CONSTRAINT lipid_hierarchy_fk1 FOREIGN KEY (lipid_entry_id) REFERENCES DB_SCHEMA_NAME.lipid_entry(lipid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.lipid_sm_map
    ADD CONSTRAINT lipid_sm_map_fk1 FOREIGN KEY (lipid_entry_id) REFERENCES DB_SCHEMA_NAME.lipid_entry(lipid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.lipid_sm_map
    ADD CONSTRAINT lipid_sm_map_fk2 FOREIGN KEY (sm_entry_id) REFERENCES DB_SCHEMA_NAME.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_component_sm_map
    ADD CONSTRAINT mcsm_fk_sm FOREIGN KEY (sm_entry_id) REFERENCES DB_SCHEMA_NAME.sm_entry(sm_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_component_sm_map
    ADD CONSTRAINT mcsm_fk_mc FOREIGN KEY (molecular_component_id) REFERENCES DB_SCHEMA_NAME.molecular_component(molecular_component_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_entity_component_map
    ADD CONSTRAINT mecm_fk_sm FOREIGN KEY (molecular_entity_id) REFERENCES DB_SCHEMA_NAME.molecular_entity(molecular_entity_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.molecular_entity_component_map
    ADD CONSTRAINT mecm_fk_mc FOREIGN KEY (molecular_component_id) REFERENCES DB_SCHEMA_NAME.molecular_component(molecular_component_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_clinical_trial_map
    ADD CONSTRAINT news_clinical_trial_fk_ct FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_clinical_trial_map
    ADD CONSTRAINT news_clinical_trial_fk_news FOREIGN KEY (news_id) REFERENCES DB_SCHEMA_NAME.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_company_map
    ADD CONSTRAINT news_company_fk_disease FOREIGN KEY (company_entry_id) REFERENCES DB_SCHEMA_NAME.company_entry(company_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_company_map
    ADD CONSTRAINT news_company_fk_news FOREIGN KEY (news_id) REFERENCES DB_SCHEMA_NAME.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_disease_map
    ADD CONSTRAINT news_disease_fk_disease FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_disease_map
    ADD CONSTRAINT news_disease_fk_news FOREIGN KEY (news_id) REFERENCES DB_SCHEMA_NAME.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_document
    ADD CONSTRAINT news_disease_fk_news FOREIGN KEY (news_id) REFERENCES DB_SCHEMA_NAME.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_drug_map
    ADD CONSTRAINT news_drug_fk_drug FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_drug_map
    ADD CONSTRAINT news_drug_fk_news FOREIGN KEY (news_id) REFERENCES DB_SCHEMA_NAME.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.news
    ADD CONSTRAINT news_fk_user FOREIGN KEY (user_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_gn_map
    ADD CONSTRAINT news_gn_fk_gn FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_gn_map
    ADD CONSTRAINT news_gn_fk_news FOREIGN KEY (news_id) REFERENCES DB_SCHEMA_NAME.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_news_map
    ADD CONSTRAINT news_news_map_news_id_fkey FOREIGN KEY (news_id) REFERENCES DB_SCHEMA_NAME.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.news_news_map
    ADD CONSTRAINT news_news_map_news_parent_id_fkey FOREIGN KEY (news_parent_id) REFERENCES DB_SCHEMA_NAME.news(news_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.org_chart
    ADD CONSTRAINT org_chart_web_user_id_fkey FOREIGN KEY (web_user_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.org_group_map
    ADD CONSTRAINT org_group_map_org_group_id_fkey FOREIGN KEY (org_group_id) REFERENCES DB_SCHEMA_NAME.org_group(org_group_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.org_group_map
    ADD CONSTRAINT org_group_map_web_user_fkey FOREIGN KEY (web_user_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site
    ADD CONSTRAINT p_xrprot_site_prot_entry_fk1 FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site_rel
    ADD CONSTRAINT p_xrprot_site_rel_p_xrprot_si_fk1 FOREIGN KEY (p_xrprot_site_sel_id) REFERENCES DB_SCHEMA_NAME.p_xrprot_site(p_xrprot_site_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site_rel
    ADD CONSTRAINT p_xrprot_site_rel_p_xrprot_si_fk2 FOREIGN KEY (p_xrprot_site_id_comp) REFERENCES DB_SCHEMA_NAME.p_xrprot_site(p_xrprot_site_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site_seq
    ADD CONSTRAINT p_xrprot_site_seq_p_xrprot_si_fk1 FOREIGN KEY (p_xrprot_site_id) REFERENCES DB_SCHEMA_NAME.p_xrprot_site(p_xrprot_site_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site_seq
    ADD CONSTRAINT p_xrprot_site_seq_prot_seq_po_fk1 FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.p_xrprot_site_xray_map
    ADD CONSTRAINT p_xrprot_site_xray_map_p_xr_fk1 FOREIGN KEY (p_xrprot_site_id) REFERENCES DB_SCHEMA_NAME.p_xrprot_site(p_xrprot_site_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_abstract
    ADD CONSTRAINT pmid_abstract_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_anatomy_map
    ADD CONSTRAINT pmid_anatomy_map_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_anatomy_map
    ADD CONSTRAINT pmid_anatomy_map_fk2 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_author
    ADD CONSTRAINT pmid_author_fk1 FOREIGN KEY (pmid_instit_id) REFERENCES DB_SCHEMA_NAME.pmid_instit(pmid_instit_id) ON UPDATE CASCADE NOT VALID;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_author_map
    ADD CONSTRAINT pmid_author_map_fk1 FOREIGN KEY (pmid_author_id) REFERENCES DB_SCHEMA_NAME.pmid_author(pmid_author_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_author_map
    ADD CONSTRAINT pmid_author_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_citation
    ADD CONSTRAINT pmid_cit_cit FOREIGN KEY (citation_pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_citation
    ADD CONSTRAINT pmid_cit_ref FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_company_map
    ADD CONSTRAINT pmid_company_map_company_entry_id_fkey FOREIGN KEY (company_entry_id) REFERENCES DB_SCHEMA_NAME.company_entry(company_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_company_map
    ADD CONSTRAINT pmid_company_map_pmid_entry_id_fkey FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_gene
    ADD CONSTRAINT pmid_disease_gene_fk1 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_gene
    ADD CONSTRAINT pmid_disease_gene_fk2 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_gene
    ADD CONSTRAINT pmid_disease_gene_fk3 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmc_fulltext_file
    ADD CONSTRAINT pmc_fulltext_file_fk FOREIGN KEY (pmc_entry_id) REFERENCES DB_SCHEMA_NAME.pmc_entry(pmc_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;    

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_gene_txt
    ADD CONSTRAINT pmid_disease_gene_txt_fk1 FOREIGN KEY (pmid_disease_gene_id) REFERENCES DB_SCHEMA_NAME.pmid_disease_gene(pmid_disease_gene_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_map
    ADD CONSTRAINT pmid_disease_map_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_disease_map
    ADD CONSTRAINT pmid_disease_map_fk2 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_drug_map
    ADD CONSTRAINT pmid_drug_map_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_drug_map
    ADD CONSTRAINT pmid_drug_map_fk2 FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_entry
    ADD CONSTRAINT pmid_entry_fk1 FOREIGN KEY (pmid_journal_id) REFERENCES DB_SCHEMA_NAME.pmid_journal(pmid_journal_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_gene_map
    ADD CONSTRAINT pmid_gene_map_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_gene_map
    ADD CONSTRAINT pmid_gene_map_fk2 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_gene_stat
    ADD CONSTRAINT pmid_gene_stat_fk1 FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_onto_map
    ADD CONSTRAINT pmid_onto_map_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pmid_onto_map
    ADD CONSTRAINT pmid_onto_map_fk2 FOREIGN KEY (ontology_entry_id) REFERENCES DB_SCHEMA_NAME.ontology_entry(ontology_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_desc_pmid
    ADD CONSTRAINT prot_desc_pmid_fk1 FOREIGN KEY (prot_desc_id) REFERENCES DB_SCHEMA_NAME.prot_desc(prot_desc_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_desc_pmid
    ADD CONSTRAINT prot_desc_pmid_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_desc_pmid
    ADD CONSTRAINT prot_desc_pmid_fk3 FOREIGN KEY (eco_entry_id) REFERENCES DB_SCHEMA_NAME.eco_entry(eco_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_desc
    ADD CONSTRAINT prot_description_fk1 FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_extdb_map
    ADD CONSTRAINT prot_extdb_map_fk1 FOREIGN KEY (prot_extdb_id) REFERENCES DB_SCHEMA_NAME.prot_extdb(prot_extdbid) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_extdb_map
    ADD CONSTRAINT prot_extdb_map_fk2 FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_extdb_map
    ADD CONSTRAINT prot_extdb_map_fk3 FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat
    ADD CONSTRAINT prot_feat_fk1 FOREIGN KEY (prot_feat_type_id) REFERENCES DB_SCHEMA_NAME.prot_feat_type(prot_feat_type_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_pmid
    ADD CONSTRAINT prot_feat_pmid_fk1 FOREIGN KEY (prot_feat_id) REFERENCES DB_SCHEMA_NAME.prot_feat(prot_feat_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_pmid
    ADD CONSTRAINT prot_feat_pmid_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_feat_pmid
    ADD CONSTRAINT prot_feat_pmid_fk3 FOREIGN KEY (eco_entry_id) REFERENCES DB_SCHEMA_NAME.eco_entry(eco_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_pmid_map
    ADD CONSTRAINT prot_pmid_map_fk1 FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.prot_pmid_map
    ADD CONSTRAINT prot_pmid_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_hierarchy
    ADD CONSTRAINT pw_hierarchy_fk1 FOREIGN KEY (pw_entry_id) REFERENCES DB_SCHEMA_NAME.pw_entry(pw_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_rel
    ADD CONSTRAINT pw_rel_pw_entry_fk1 FOREIGN KEY (pw_to_id) REFERENCES DB_SCHEMA_NAME.pw_entry(pw_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.pw_rel
    ADD CONSTRAINT pw_rel_pw_entry_fk2 FOREIGN KEY (pw_from_id) REFERENCES DB_SCHEMA_NAME.pw_entry(pw_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_gene
    ADD CONSTRAINT rna_gene_fk1 FOREIGN KEY (gene_seq_id) REFERENCES DB_SCHEMA_NAME.gene_seq(gene_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_gene
    ADD CONSTRAINT rna_gene_fk2 FOREIGN KEY (rna_sample_id) REFERENCES DB_SCHEMA_NAME.rna_sample(rna_sample_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_gene_stat
    ADD CONSTRAINT rna_gene_stat_fk1 FOREIGN KEY (rna_tissue_id) REFERENCES DB_SCHEMA_NAME.rna_tissue(rna_tissue_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_gene_stat
    ADD CONSTRAINT rna_gene_stat_fk2 FOREIGN KEY (gene_seq_id) REFERENCES DB_SCHEMA_NAME.gene_seq(gene_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_transcript
    ADD CONSTRAINT rna_gtex_fk1 FOREIGN KEY (transcript_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_transcript
    ADD CONSTRAINT rna_gtex_fk2 FOREIGN KEY (rna_sample_id) REFERENCES DB_SCHEMA_NAME.rna_sample(rna_sample_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_sample
    ADD CONSTRAINT rna_gtex_sample_fk1 FOREIGN KEY (rna_source_id) REFERENCES DB_SCHEMA_NAME.rna_source(rna_source_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_transcript_stat
    ADD CONSTRAINT rna_gtex_stat_fk2 FOREIGN KEY (transcript_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_sample
    ADD CONSTRAINT rna_sample_fk1 FOREIGN KEY (rna_tissue_id) REFERENCES DB_SCHEMA_NAME.rna_tissue(rna_tissue_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_tissue
    ADD CONSTRAINT rna_tissue_fk1 FOREIGN KEY (anatomy_entry_id) REFERENCES DB_SCHEMA_NAME.anatomy_entry(anatomy_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_tissue
    ADD CONSTRAINT rna_tissue_fk2 FOREIGN KEY (efo_entry_id) REFERENCES DB_SCHEMA_NAME.efo_entry(efo_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.rna_transcript_stat
    ADD CONSTRAINT rna_transcript_stat_fk1 FOREIGN KEY (rna_tissue_id) REFERENCES DB_SCHEMA_NAME.rna_tissue(rna_tissue_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.seq_btype
    ADD CONSTRAINT seq_btype_fk1 FOREIGN KEY (so_entry_id) REFERENCES DB_SCHEMA_NAME.so_entry(so_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_author_map
    ADD CONSTRAINT sharepoint_doc_author_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_SCHEMA_NAME.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_author_map
    ADD CONSTRAINT sharepoint_doc_author_map_web_user_id_fkey FOREIGN KEY (web_user_id) REFERENCES DB_SCHEMA_NAME.web_user(web_user_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_clinical_trial_map
    ADD CONSTRAINT sharepoint_doc_clinical_trial_map_clinical_trial_id_fkey FOREIGN KEY (clinical_trial_id) REFERENCES DB_SCHEMA_NAME.clinical_trial(clinical_trial_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_clinical_trial_map
    ADD CONSTRAINT sharepoint_doc_clinical_trial_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_SCHEMA_NAME.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_company_map
    ADD CONSTRAINT sharepoint_doc_company_map_company_entry_id_fkey FOREIGN KEY (company_entry_id) REFERENCES DB_SCHEMA_NAME.company_entry(company_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_company_map
    ADD CONSTRAINT sharepoint_doc_company_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_SCHEMA_NAME.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_disease_map
    ADD CONSTRAINT sharepoint_doc_disease_map_disease_entry_id_fkey FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_disease_map
    ADD CONSTRAINT sharepoint_doc_disease_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_SCHEMA_NAME.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_drug_map
    ADD CONSTRAINT sharepoint_doc_drug_map_drug_entry_id_fkey FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_drug_map
    ADD CONSTRAINT sharepoint_doc_drug_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_SCHEMA_NAME.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_gn_map
    ADD CONSTRAINT sharepoint_doc_gn_map_gn_entry_id_fkey FOREIGN KEY (gn_entry_id) REFERENCES DB_SCHEMA_NAME.gn_entry(gn_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_doc_gn_map
    ADD CONSTRAINT sharepoint_doc_gn_map_sharepoint_document_id_fkey FOREIGN KEY (sharepoint_document_id) REFERENCES DB_SCHEMA_NAME.sharepoint_document(sharepoint_document_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sharepoint_document
    ADD CONSTRAINT sharepoint_document_sharepoint_config_id_fkey FOREIGN KEY (sharepoint_config_id) REFERENCES DB_SCHEMA_NAME.sharepoint_config(sharepoint_config_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_drug
    ADD CONSTRAINT side_effect_drug_drug_entry_id_fkey FOREIGN KEY (drug_entry_id) REFERENCES DB_SCHEMA_NAME.drug_entry(drug_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_drug_reaction
    ADD CONSTRAINT side_effect_drug_reaction_meddra_entry_id_fkey FOREIGN KEY (meddra_entry_id) REFERENCES DB_SCHEMA_NAME.meddra_entry(meddra_entry_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_drug_reaction
    ADD CONSTRAINT side_effect_drug_reaction_side_effect_report_id_fkey FOREIGN KEY (side_effect_report_id) REFERENCES DB_SCHEMA_NAME.side_effect_report(side_effect_report_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_drug
    ADD CONSTRAINT side_effect_drug_side_effect_report_id_fkey FOREIGN KEY (side_effect_report_id) REFERENCES DB_SCHEMA_NAME.side_effect_report(side_effect_report_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.side_effect_report
    ADD CONSTRAINT side_effect_report_side_effect_seriousness_id_fkey FOREIGN KEY (side_effect_seriousness_id) REFERENCES DB_SCHEMA_NAME.side_effect_seriousness(side_effect_seriousness_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_description
    ADD CONSTRAINT sm_description_fk1 FOREIGN KEY (sm_entry_id) REFERENCES DB_SCHEMA_NAME.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_description
    ADD CONSTRAINT sm_desription_source_fk FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;



ALTER TABLE ONLY DB_SCHEMA_NAME.sm_entry
    ADD CONSTRAINT sm_entry_fk1 FOREIGN KEY (sm_molecule_id) REFERENCES DB_SCHEMA_NAME.sm_molecule(sm_molecule_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_entry
    ADD CONSTRAINT sm_entry_fk2 FOREIGN KEY (sm_counterion_id) REFERENCES DB_SCHEMA_NAME.sm_counterion(sm_counterion_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_molecule
    ADD CONSTRAINT sm_molecule_fk1 FOREIGN KEY (sm_scaffold_id) REFERENCES DB_SCHEMA_NAME.sm_scaffold(sm_scaffold_id) ON UPDATE CASCADE ON DELETE SET NULL;


ALTER TABLE ONLY DB_SCHEMA_NAME.sm_patent_map
    ADD CONSTRAINT sm_patent_map_fk1 FOREIGN KEY (sm_entry_id) REFERENCES DB_SCHEMA_NAME.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_patent_map
    ADD CONSTRAINT sm_patent_map_fk2 FOREIGN KEY (patent_entry_id) REFERENCES DB_SCHEMA_NAME.patent_entry(patent_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_publi_map
    ADD CONSTRAINT sm_publi_map_fk1 FOREIGN KEY (sm_entry_id) REFERENCES DB_SCHEMA_NAME.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_publi_map
    ADD CONSTRAINT sm_publi_map_fk2 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_publi_map
    ADD CONSTRAINT sm_publi_map_fk3 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_publi_map
    ADD CONSTRAINT sm_publi_map_fk4 FOREIGN KEY (disease_entry_id) REFERENCES DB_SCHEMA_NAME.disease_entry(disease_entry_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_source
    ADD CONSTRAINT sm_source_fk2 FOREIGN KEY (sm_entry_id) REFERENCES DB_SCHEMA_NAME.sm_entry(sm_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.sm_source
    ADD CONSTRAINT sm_so_so_fk FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_inter_res
    ADD CONSTRAINT table1_interaction_type_fk1 FOREIGN KEY (xr_inter_type_id) REFERENCES DB_SCHEMA_NAME.xr_inter_type(xr_inter_type_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_inter_res
    ADD CONSTRAINT table1_xray_atom_fk1 FOREIGN KEY (xr_atom_id_1) REFERENCES DB_SCHEMA_NAME.xr_atom(xr_atom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_inter_res
    ADD CONSTRAINT table1_xray_atom_fk2 FOREIGN KEY (xr_atom_id_2) REFERENCES DB_SCHEMA_NAME.xr_atom(xr_atom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_inter_res
    ADD CONSTRAINT table1_xray_residue_fk1 FOREIGN KEY (xr_res_id_1) REFERENCES DB_SCHEMA_NAME.xr_res(xr_res_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_inter_res
    ADD CONSTRAINT table1_xray_residue_fk2 FOREIGN KEY (xr_res_id_2) REFERENCES DB_SCHEMA_NAME.xr_res(xr_res_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.tr_protseq_al
    ADD CONSTRAINT tr_protseq_al_fk1 FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.tr_protseq_al
    ADD CONSTRAINT tr_protseq_al_fk2 FOREIGN KEY (transcript_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.tr_protseq_pos_al
    ADD CONSTRAINT tr_protseq_pos_al_fk1 FOREIGN KEY (tr_protseq_al_id) REFERENCES DB_SCHEMA_NAME.tr_protseq_al(tr_protseq_al_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.tr_protseq_pos_al
    ADD CONSTRAINT tr_protseq_pos_al_fk2 FOREIGN KEY (transcript_pos_id) REFERENCES DB_SCHEMA_NAME.transcript_pos(transcript_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.tr_protseq_pos_al
    ADD CONSTRAINT tr_protseq_pos_al_fk3 FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al
    ADD CONSTRAINT transcript_al_comp FOREIGN KEY (transcript_comp_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al_pos
    ADD CONSTRAINT transcript_al_pos_al FOREIGN KEY (transcript_al_id) REFERENCES DB_SCHEMA_NAME.transcript_al(transcript_al_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al_pos
    ADD CONSTRAINT transcript_al_pos_comp FOREIGN KEY (transcript_pos_comp_id) REFERENCES DB_SCHEMA_NAME.transcript_pos(transcript_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al_pos
    ADD CONSTRAINT transcript_al_pos_ref FOREIGN KEY (transcript_pos_ref_id) REFERENCES DB_SCHEMA_NAME.transcript_pos(transcript_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_al
    ADD CONSTRAINT transcript_al_ref FOREIGN KEY (transcript_ref_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript
    ADD CONSTRAINT transcript_fk1 FOREIGN KEY (biotype_id) REFERENCES DB_SCHEMA_NAME.seq_btype(seq_btype_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript
    ADD CONSTRAINT transcript_fk2 FOREIGN KEY (feature_id) REFERENCES DB_SCHEMA_NAME.seq_btype(seq_btype_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript
    ADD CONSTRAINT transcript_fk3 FOREIGN KEY (chr_seq_id) REFERENCES DB_SCHEMA_NAME.chr_seq(chr_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript
    ADD CONSTRAINT transcript_fk4 FOREIGN KEY (gene_seq_id) REFERENCES DB_SCHEMA_NAME.gene_seq(gene_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_pos
    ADD CONSTRAINT transcript_pos_chr_pos_fk FOREIGN KEY (chr_seq_pos_id) REFERENCES DB_SCHEMA_NAME.chr_seq_pos(chr_seq_pos_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_pos
    ADD CONSTRAINT transcript_seq_fk2 FOREIGN KEY (seq_pos_type_id) REFERENCES DB_SCHEMA_NAME.transcript_pos_type(transcript_pos_type_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.transcript_pos
    ADD CONSTRAINT transcript_seq_fk3 FOREIGN KEY (transcript_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_change
    ADD CONSTRAINT variant_change_fk1 FOREIGN KEY (variant_position_id) REFERENCES DB_SCHEMA_NAME.variant_position(variant_position_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_change
    ADD CONSTRAINT variant_change_fk2 FOREIGN KEY (variant_type_id) REFERENCES DB_SCHEMA_NAME.variant_type(variant_type_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_change
    ADD CONSTRAINT variant_change_fk3 FOREIGN KEY (alt_all) REFERENCES DB_SCHEMA_NAME.variant_allele(variant_allele_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_clinv_assert_map
    ADD CONSTRAINT variant_clinv_assert_map_fk2 FOREIGN KEY (variant_change_id) REFERENCES DB_SCHEMA_NAME.variant_change(variant_change_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_position
    ADD CONSTRAINT variant_entry_fk3 FOREIGN KEY (ref_all) REFERENCES DB_SCHEMA_NAME.variant_allele(variant_allele_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_freq_study
    ADD CONSTRAINT variant_freq_study_fk1 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_frequency
    ADD CONSTRAINT variant_frequency_fk1 FOREIGN KEY (variant_change_id) REFERENCES DB_SCHEMA_NAME.variant_change(variant_change_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_frequency
    ADD CONSTRAINT variant_frequency_fk2 FOREIGN KEY (variant_freq_study_id) REFERENCES DB_SCHEMA_NAME.variant_freq_study(variant_freq_study_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_info
    ADD CONSTRAINT variant_info_fk1 FOREIGN KEY (variant_entry_id) REFERENCES DB_SCHEMA_NAME.variant_entry(variant_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_info
    ADD CONSTRAINT variant_info_fk2 FOREIGN KEY (source_id) REFERENCES DB_SCHEMA_NAME.source(source_id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_pmid_map
    ADD CONSTRAINT variant_pmid_map_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_pmid_map
    ADD CONSTRAINT variant_pmid_map_fk2 FOREIGN KEY (variant_entry_id) REFERENCES DB_SCHEMA_NAME.variant_entry(variant_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_position
    ADD CONSTRAINT variant_position_fk1 FOREIGN KEY (chr_seq_pos_id) REFERENCES DB_SCHEMA_NAME.chr_seq_pos(chr_seq_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_position
    ADD CONSTRAINT variant_position_fk4 FOREIGN KEY (variant_entry_id) REFERENCES DB_SCHEMA_NAME.variant_entry(variant_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_protein_map
    ADD CONSTRAINT variant_protein_map_fk1 FOREIGN KEY (variant_transcript_id) REFERENCES DB_SCHEMA_NAME.variant_transcript_map(variant_transcript_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_protein_map
    ADD CONSTRAINT variant_protein_map_fk2 FOREIGN KEY (prot_seq_id) REFERENCES DB_SCHEMA_NAME.prot_seq(prot_seq_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_protein_map
    ADD CONSTRAINT variant_protein_map_fk3 FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_protein_map
    ADD CONSTRAINT variant_protein_map_fk4 FOREIGN KEY (so_entry_id) REFERENCES DB_SCHEMA_NAME.so_entry(so_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_protein_map
    ADD CONSTRAINT variant_protein_map_fk5 FOREIGN KEY (prot_ref_all) REFERENCES DB_SCHEMA_NAME.variant_prot_allele(variant_prot_allele_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_protein_map
    ADD CONSTRAINT variant_protein_map_fk6 FOREIGN KEY (prot_alt_all) REFERENCES DB_SCHEMA_NAME.variant_prot_allele(variant_prot_allele_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_transcript_map
    ADD CONSTRAINT variant_transcript_map_fk1 FOREIGN KEY (variant_change_id) REFERENCES DB_SCHEMA_NAME.variant_change(variant_change_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_transcript_map
    ADD CONSTRAINT variant_transcript_map_fk2 FOREIGN KEY (transcript_id) REFERENCES DB_SCHEMA_NAME.transcript(transcript_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_transcript_map
    ADD CONSTRAINT variant_transcript_map_fk3 FOREIGN KEY (transcript_pos_id) REFERENCES DB_SCHEMA_NAME.transcript_pos(transcript_pos_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_transcript_map
    ADD CONSTRAINT variant_transcript_map_fk4 FOREIGN KEY (so_entry_id) REFERENCES DB_SCHEMA_NAME.so_entry(so_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_transcript_map
    ADD CONSTRAINT variant_transcript_map_fk5 FOREIGN KEY (tr_ref_all) REFERENCES DB_SCHEMA_NAME.variant_allele(variant_allele_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_transcript_map
    ADD CONSTRAINT variant_transcript_map_fk6 FOREIGN KEY (tr_alt_all) REFERENCES DB_SCHEMA_NAME.variant_allele(variant_allele_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.variant_type
    ADD CONSTRAINT variant_type_fk1 FOREIGN KEY (so_entry_id) REFERENCES DB_SCHEMA_NAME.so_entry(so_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_bond
    ADD CONSTRAINT xr_bond_fk1 FOREIGN KEY (xr_chain_id) REFERENCES DB_SCHEMA_NAME.xr_chain(xr_chain_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_cav
    ADD CONSTRAINT xr_cav_xr_site_fk1 FOREIGN KEY (xr_site_id) REFERENCES DB_SCHEMA_NAME.xr_site(xr_site_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_prot_pos
    ADD CONSTRAINT xr_ch_prot_pos_fk1 FOREIGN KEY (xr_ch_prot_map_id) REFERENCES DB_SCHEMA_NAME.xr_ch_prot_map(xr_ch_prot_map_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_entry_pmid
    ADD CONSTRAINT xr_entry_pmid_fk1 FOREIGN KEY (pmid_entry_id) REFERENCES DB_SCHEMA_NAME.pmid_entry(pmid_entry_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_entry_pmid
    ADD CONSTRAINT xr_entry_pmid_fk2 FOREIGN KEY (xr_entry_id) REFERENCES DB_SCHEMA_NAME.xr_entry(xr_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_dom_cov
    ADD CONSTRAINT xr_prot_dom_cov_fk1 FOREIGN KEY (xr_chain_id) REFERENCES DB_SCHEMA_NAME.xr_chain(xr_chain_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_dom_cov
    ADD CONSTRAINT xr_prot_dom_cov_fk2 FOREIGN KEY (prot_dom_id) REFERENCES DB_SCHEMA_NAME.prot_dom(prot_dom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_int_stat
    ADD CONSTRAINT xr_prot_int_stat_fk1 FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_int_stat
    ADD CONSTRAINT xr_prot_int_stat_fk2 FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_int_stat
    ADD CONSTRAINT xr_prot_int_stat_fk3 FOREIGN KEY (xr_inter_type_id) REFERENCES DB_SCHEMA_NAME.xr_inter_type(xr_inter_type_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_prot_stat
    ADD CONSTRAINT xr_prot_stat_fk1 FOREIGN KEY (prot_entry_id) REFERENCES DB_SCHEMA_NAME.prot_entry(prot_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_res
    ADD CONSTRAINT xr_res_fk1 FOREIGN KEY (xr_tpl_res_id) REFERENCES DB_SCHEMA_NAME.xr_tpl_res(xr_tpl_res_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_prot_pos
    ADD CONSTRAINT xr_res_useqp_map_prot_seq_p_fk1 FOREIGN KEY (prot_seq_pos_id) REFERENCES DB_SCHEMA_NAME.prot_seq_pos(prot_seq_pos_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_ch_prot_pos
    ADD CONSTRAINT xr_res_useqp_map_xr_res_fk1 FOREIGN KEY (xr_res_id) REFERENCES DB_SCHEMA_NAME.xr_res(xr_res_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_res
    ADD CONSTRAINT xr_res_xr_chain_fk1 FOREIGN KEY (xr_chain_id) REFERENCES DB_SCHEMA_NAME.xr_chain(xr_chain_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_status
    ADD CONSTRAINT xr_status_fk1 FOREIGN KEY (xr_entry_id) REFERENCES DB_SCHEMA_NAME.xr_entry(xr_entry_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_status
    ADD CONSTRAINT xr_status_fk2 FOREIGN KEY (xr_job_id) REFERENCES DB_SCHEMA_NAME.xr_jobs(xr_job_id) ON UPDATE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_tpl_res
    ADD CONSTRAINT xr_tpl_res_fk1 FOREIGN KEY (sm_molecule_id) REFERENCES DB_SCHEMA_NAME.sm_molecule(sm_molecule_id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_bond
    ADD CONSTRAINT xray_atom2_fk1 FOREIGN KEY (xr_atom_id_1) REFERENCES DB_SCHEMA_NAME.xr_atom(xr_atom_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY DB_SCHEMA_NAME.xr_bond
    ADD CONSTRAINT xray_atom_fk1 FOREIGN KEY (xr_atom_id_2) REFERENCES DB_SCHEMA_NAME.xr_atom(xr_atom_id) ON UPDATE CASCADE ON DELETE CASCADE;





Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (1,'download');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (2,'validate');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (3,'prepare');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (5,'db_load');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (6,'blastp');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (4,'pdb_sep');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (7,'cavity_det');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (8,'clustering');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (0,'setup');
Insert into XR_JOBS (XR_JOB_ID,XR_JOB_NAME) values (10,'blastp_load');


Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (1,0,'F',0,'Actinium','Ac',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (2,1.53,'F',107.86,'Silver','Ag',1.72);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (3,1.18,'F',26.98,'Aluminium','Al',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (4,0,'F',0,'Americium','Am',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (5,0.97,'F',39.94,'Argon','Ar',1.88);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (6,1.19,'F',74.92,'Arsenic','As',1.85);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (7,0,'F',0,'Astatine','At',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (8,1.44,'F',196.96,'Gold','Au',1.66);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (9,0.82,'F',10.81,'Boron','B',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (10,1.98,'F',137.32,'Barium','Ba',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (11,0.9,'F',9.01,'Beryllium','Be',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (12,0,'F',0,'Bohrium','Bh',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (13,1.46,'F',208.98,'Bismuth','Bi',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (14,0,'F',0,'Berkelium','Bk',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (15,1.48,'F',112.41,'Cadmium','Cd',1.58);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (16,0,'F',140.11,'Cerium','Ce',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (17,0,'F',0,'Californium','Cf',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (18,0,'F',0,'Curium','Cm',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (19,0,'F',0,'Copernicium','Cn',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (20,1.27,'F',51.99,'Chromium','Cr',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (21,2.25,'F',132.9,'Cesium','Cs',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (22,1.38,'F',63.54,'Copper','Cu',1.4);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (23,0,'F',0,'Dubnium','Db',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (24,0,'F',0,'Darmstadtium','Ds',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (25,0,'F',162.5,'Dysprosium','Dy',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (26,0,'F',167.25,'Erbium','Er',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (27,0,'F',0,'Einsteinium','Es',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (28,0,'F',151.96,'Europium','Eu',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (29,0,'F',0,'Fermium','Fm',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (30,0,'F',0,'Francium','Fr',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (31,1.26,'F',69.72,'Gallium','Ga',1.87);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (32,0,'F',157.25,'Gadolinium','Gd',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (33,1.22,'F',72.64,'Germanium','Ge',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (34,0.32,'F',4,'Helium','He',1.4);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (35,1.5,'F',178.49,'Hafnium','Hf',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (36,1.49,'F',200.59,'Mercury','Hg',1.55);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (37,0,'F',164.93,'Holmium','Ho',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (38,0,'F',0,'Hassium','Hs',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (39,1.44,'F',114.81,'Indium','In',1.93);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (40,1.37,'F',192.21,'Iridium','Ir',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (41,1.96,'F',39.09,'Potassium','K',2.75);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (42,1.1,'F',83.79,'Krypton','Kr',2.02);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (43,1.69,'F',138.9,'Lanthanum','La',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (44,1.34,'F',6.94,'Lithium','Li',1.82);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (45,0,'F',0,'Lawrencium','Lr',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (46,1.6,'F',174.96,'Lutetium','Lu',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (47,0,'F',0,'Mendelevium','Md',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (48,1.3,'F',24.3,'Magnesium','Mg',1.73);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (49,1.45,'F',95.96,'Molybdenum','Mo',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (50,0,'F',0,'Meitnerium','Mt',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (51,1.54,'F',22.98,'Sodium','Na',2.27);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (52,1.37,'F',92.9,'Niobium','Nb',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (53,0,'F',144.24,'Neodymium','Nd',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (54,0.69,'F',20.17,'Neon','Ne',1.54);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (55,1.21,'F',58.69,'Nickel','Ni',1.63);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (56,0,'F',0,'Nobelium','No',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (57,0,'F',0,'Neptunium','Np',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (58,1.28,'F',190.23,'Osmium','Os',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (59,0,'F',231.03,'Protactinium','Pa',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (60,1.47,'F',207.2,'Lead','Pb',2.02);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (61,1.31,'F',106.42,'Palladium','Pd',1.63);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (62,0,'F',0,'Promethium','Pm',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (63,0,'F',0,'Polonium','Po',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (64,0,'F',140.9,'Praseodymium','Pr',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (65,1.28,'F',195.08,'Platinum','Pt',1.75);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (66,0,'F',0,'Plutonium','Pu',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (67,0,'F',0,'Radium','Ra',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (68,2.11,'F',85.46,'Rubidium','Rb',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (69,1.59,'F',186.2,'Rhenium','Re',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (70,0,'F',0,'Rutherfordium','Rf',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (71,0,'F',0,'Roentgenium','Rg',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (72,1.35,'F',102.9,'Rhodium','Rh',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (73,1.45,'F',0,'Radon','Rn',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (74,1.26,'F',101.07,'Ruthenium','Ru',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (75,1.38,'F',121.76,'Antimony','Sb',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (76,1.44,'F',44.95,'Scandium','Sc',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (77,1.16,'F',78.96,'Selenium','Se',1.9);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (78,0,'F',0,'Seaborgium','Sg',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (79,1.11,'F',28.08,'Silicon','Si',2.1);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (80,0,'F',150.36,'Samarium','Sm',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (81,1.41,'F',118.71,'Tin','Sn',2.17);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (82,1.92,'F',87.62,'Strontium','Sr',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (83,1.38,'F',180.94,'Tantalum','Ta',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (84,0,'F',158.92,'Terbium','Tb',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (85,1.56,'F',0,'Technetium','Tc',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (86,1.35,'F',127.6,'Tellurium','Te',2.06);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (87,0,'F',232.03,'Thorium','Th',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (88,1.36,'F',47.86,'Titanium','Ti',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (89,1.48,'F',204.38,'Thallium','Tl',1.96);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (90,0,'F',168.93,'Thulium','Tm',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (91,0,'F',238.02,'Uranium','U',1.86);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (92,1.25,'F',50.94,'Vanadium','V',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (93,1.46,'F',183.84,'Tungsten','W',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (94,1.3,'F',131.29,'Xenon','Xe',2.16);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (95,1.62,'F',88.9,'Yttrium','Y',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (96,0,'F',173.05,'Ytterbium','Yb',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (97,1.48,'F',91.22,'Zirconium','Zr',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (98,1.14,'T',79.9,'Bromine','Br',1.85);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (99,0.77,'T',12.01,'Carbon','C',1.7);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (100,1.74,'T',40.07,'Calcium','Ca',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (101,0.99,'T',35.45,'Chlorine','Cl',1.75);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (102,1.26,'T',58.93,'Cobalt','Co',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (103,0.71,'T',18.99,'Fluorine','F',1.47);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (104,1.25,'T',55.84,'Iron','Fe',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (105,0.37,'T',1,'Hydrogen','H',1.2);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (106,1.33,'T',126.9,'Iodine','I',1.98);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (107,1.39,'T',54.93,'Manganese','Mn',0);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (108,0.75,'T',14,'Nitrogen','N',1.55);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (109,0.73,'T',15.99,'Oxygen','O',1.52);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (110,1.06,'T',30.97,'Phosphorus','P',1.8);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (111,1.02,'T',32.06,'Sulfur','S',1.8);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (112,1.31,'T',65.38,'Zinc','Zn',1.39);
Insert into XR_ELEMENT (XR_ELEMENT_ID,ATOMIC_RADIUS,IS_BIOLOGIC,MASS,NAME,SYMBOL,VDWRADIUS) values (120,0,'0',0,'Dummy','Du',0);

Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (1,'5''UTR');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (2,'CDS');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (3,'3''UTR');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (4,'non-coded');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (5,'poly-A');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (6,'CDS-INFERRED');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (7,'3''UTR-INFERRED');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (8,'5''UTR-INFERRED');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (9,'non-coded-INFERRED');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (10,'unknown');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (11,'5''UTR-DIFFER');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (12,'CDS-DIFFER');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (13,'3''UTR-DIFFER');
Insert into TRANSCRIPT_POS_TYPE (TRANSCRIPT_POS_TYPE_ID,TRANSCRIPT_POS_TYPE) values (14,'non-coded-DIFFER');



INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Initiator methionine','Cleavage of the initiator methionine','Molecule processing','INIT_MET','1');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Signal','Sequence targeting proteins to the secretory pathway or periplasmic space','Molecule processing','SIGNAL','2');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Transit peptide','Extent of a transit peptide for organelle targeting','Molecule processing','TRANSIT','3');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Propeptide','Part of a protein that is cleaved during maturation or activation','Molecule processing','PROPEP','4');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Chain','Extent of a polypeptide chain in the mature protein','Molecule processing','CHAIN','5');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Peptide','Extent of an active peptide in the mature protein','Molecule processing','PEPTIDE','6');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Topological domain','Location of non-membrane regions of membrane-spanning proteins','Regions','TOPO_DOM','7');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Transmembrane','Extent of a membrane-spanning region','Regions','TRANSMEM','8');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Intramembrane','Extent of a region located in a membrane without crossing it','Regions','INTERMEM','9');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Domain','Position and type of each modular protein domain','Regions','DOMAIN','10');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Repeat','Positions of repeated sequence motifs or repeated domains','Regions','REPEAT','11');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Calcium binding','Position(s) of calcium binding region(s) within the protein','Regions','CA_BIND','12');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Zinc finger','Position(s) and type(s) of zinc fingers within the protein','Regions','ZN_FING','13');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('DNA binding','Position and type of a DNA-binding domain','Regions','DNA_BIND','14');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Nucleotide binding','Nucleotide phosphate binding region','Regions','NP_BIND','15');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Region','Region of interest in the sequence','Regions','REGION','16');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Coiled coil','Positions of regions of coiled coil within the protein','Regions','COILED','17');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Motif','Short (up to 20 amino acids) sequence motif of biological interest','Regions','MOTIF','18');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Compositional bias','Region of compositional bias in the protein','Regions','COMPBIAS','19');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Active site','Amino acid(s) directly involved in the activity of an enzyme','Sites','ACT_SITE','20');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Metal binding','Binding site for a metal ion','Sites','METAL','21');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Binding site','Binding site for any chemical group (co-enzyme, prosthetic group, etc.)','Sites','BINDING','22');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Site','Any interesting single amino acid site on the sequence','Sites','SITE','23');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Non-standard residue','Occurence of non-standard amino acids (selenocysteine and pyrrolysine) in the protein sequence','Amino acid modifications','NON_STD','24');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Modified residue','Modified residues excluding lipids, glycans and protein cross-links','Amino acid modifications','MOD_RES','25');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Lipidation','Covalently attached lipid group(s)','Amino acid modifications','LIPID','26');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Glycosylation','Covalently attached glycan group(s)','Amino acid modifications','CARBOHYD','27');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Disulfide bond','Cysteine residues participating in disulfide bonds','Amino acid modifications','DISULFID','28');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Cross-link','Residues participating in covalent linkage(s) between proteins','Amino acid modifications','CROSSLNK','29');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Alternative sequence','Amino acid change(s) producing alternate protein isoforms','Natural variations','VAR_SEQ','30');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Natural variant','Description of a natural variant of the protein','Natural variations','VARIANT','31');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Mutagenesis','Site which has been experimentally altered by mutagenesis','Experimental info','MUTAGEN','32');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Sequence uncertainty','Regions of uncertainty in the sequence','Experimental info','UNSURE','33');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Sequence conflict','Description of sequence discrepancies of unknown origin','Experimental info','CONFLICT','34');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Non-adjacent residues','Indicates that two residues in a sequence are not consecutive','Experimental info','NON_CONS','35');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Non-terminal residue','The sequence is incomplete. Indicate that a residue is not the terminal residue of the complete protein','Experimental info','NON_TER','36');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Helix','Helical regions within the experimentally determined protein structure','Secondary structure','HELIX','37');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Turn','Turns within the experimentally determined protein structure','Secondary structure','TURN','38');
INSERT INTO prot_feat_type(feat_name,description,section,tag,prot_feat_type_id) VALUES ('Beta strand','Beta strand regions within the experimentally determined protein structure','Secondary structure','STRAND','39');



INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (1,180, 'Hydrogen Bond',3.6,'H-Bond',60);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (2,NULL,'Apolar contact',4.5,'Hydrophobic',NULL);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (3,NULL,'Aromatic Edge-to-Face interaction', 8,'Aromatic EF',NULL);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (4,NULL,'Parallel displaced aromatic interaction', 6,'Aromatic PD',NULL);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (5,0,'Interaction between a cation and an aromatic ring',6,'Cation PI',45);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (6,0,'Hydrogen above the plane of an aromatic ring',6,'H-Arene',45);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (7,0,'Halogen above the plane of an aromatic ring',4,'Halogen Arom',45);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (8,180,'Halogen bond',3.6,'Halogen Bond',45);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (9,135,'Halogen bond with Halogen as HBond Acceptor',3.4,'Halogen HBond',60);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (10,NULL,'Cation/Anion interaction',4,'Ionic',NULL);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (11,180,'Apolar Hydrogen in an H-Bond',4,'Weak H-Bond',30);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (12,0,'Carbonyl group/aromatic ring',5,'Carbonyl PI',90);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (13,NULL,'Anion on top of aromatic ring',4,'Anion PI',NULL);
INSERT INTO xr_inter_type (xr_inter_type_id, angle_threshold, interaction_description, distance_threshold, interaction_name, angle_extent) VALUES (14,NULL,'Interaction with a lone metal',2.6,'Metal',NULL);


INSERT INTO DB_SCHEMA_NAME.source VALUES (1436, 'DrugCentral', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1437, 'TG-GATEs', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1438, 'OpenTargets', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1468, 'TGEMO', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1469, 'MPD', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1502, 'NORD', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1535, 'PubChem Substance', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1536, 'RxCUI', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1537, 'PubChem Compound', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1538, 'BindingDB', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1539, 'PharmGKB', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1540, 'Human Metabolome Database (HMDB)', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1541, 'ZINC', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1542, 'Drugs Product Database (DPD)', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1543, 'Guide to Pharmacology', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1544, 'GenBank', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1577, 'EMolecules', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1610, 'vo', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1643, '1HAEo-', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1676, 'icd11.foundation', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1709, 'NANDO', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1742, '', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1775, 'DB_SCHEMA_NAME', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1808, 'EMA', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1809, 'USAN', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1810, 'INN', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1843, 'TreeGrafter', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1876, 'BSPO', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1877, 'FBql', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1400, 'RRID', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1401, 'FBcv', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1435, 'OMIA', NULL, NULL, NULL, NULL, NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (1, 'caloha', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (2, 'fma', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (3, 'go', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (4, 'kupo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (5, 'vhog', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (6, 'wbbt', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (8, 'bto', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (9, 'caro', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (10, 'ma', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (11, 'ncithesaurus', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (12, 'emapa', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (13, 'fbbt', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (14, 'vsao', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (15, 'ev', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (16, 'aeo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (17, 'aao', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (18, 'efo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (19, 'fao', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (20, 'ehdaa2', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (21, 'nifstd', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (22, 'mesh', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (23, 'isbn', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (24, 'zfa', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (25, 'mp', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (26, 'sao', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (27, 'th', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (28, 'goc', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (29, 'ilx', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (30, 'ncit', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (31, 'iupharobj', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (32, 'uniprotkb', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (33, 'reactome', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (34, 'gaid', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (35, 'mat', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (36, 'opencyc', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (37, 'umls', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (38, 'galen', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (39, 'ehdaa', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (40, 'miaa', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (41, 'tao', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (42, 'bams', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (43, 'birnlex', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (44, 'bm', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (45, 'dhba', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (46, 'hba', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (47, 'http', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (48, 'bila', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (49, 'ehda', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (50, 'nlxanat', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (51, 'hao', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (52, 'tgma', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (53, 'bsa', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (54, 'tao_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (55, 'zfa_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (56, 'spd', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (57, 'dmba', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (58, 'envo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (59, 'bilado', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (60, 'fbdv', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (61, 'wbls', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (62, 'xtrodo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (63, 'hsapdv', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (64, 'mmusdv', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (65, 'oges', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (66, 'zfs', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (67, 'emapa_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (68, 'span', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (69, 'bils', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (70, 'olatdv', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (71, 'pdumdv', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (72, 'nlx', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (73, 'aniseed', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (74, 'tads', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (75, 'ehdaa2_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (76, 'nif_subcellular', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (77, 'ogem', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (78, 'mba', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (79, 'wikipediacategory', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (80, 'aba', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (81, 'pba', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (82, 'retired_ehdaa2', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (83, 'nifstd_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (84, 'mfmo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (85, 'map', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (86, 'fma_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (87, 'oldneuronames', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (88, 'te', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (89, 'aeo_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (90, 'vhog_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (91, 'drerdo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (92, 'evm', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (93, 'nif_organism', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (94, 'cp', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (95, 'stid', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (96, 'murdoch', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (97, 'vsao_retired', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (98, 'neuronamescnid', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (99, 'uberontemp', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (100, 'emaps', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (101, 'noid', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (102, 'cl', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (103, 'radlex', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (104, 'talairach', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (105, 'mpath', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (106, 'phenoscape', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (107, 'ta2', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (108, 'wikipediaversioned', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (109, 'xao', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (7, 'UBERON', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (110, 'PMID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (111, 'VT', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (112, 'HP', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (113, 'https', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (114, 'SNOMED', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (115, 'CMO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (116, 'doi', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (117, 'PR', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (118, 'SNOMEDCT', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (119, 'BFO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (120, 'ATCC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (121, 'Beilstein', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (122, 'ChEMBL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (123, 'ChemIDplus', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (124, 'CiteXplore', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (125, 'DrugBank', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (126, 'Gmelin', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (127, 'KEGG COMPOUND', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (128, 'KEGG DRUG', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (129, 'Patent', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (130, 'Wikipedia', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (131, 'NIST Chemistry WebBook', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (132, 'CAS', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (133, 'HMDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (134, 'KEGG', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (135, 'LIPID_MAPS_instance', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (136, 'Reaxys', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (137, 'MetaCyc', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (138, 'PDBeChem', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (139, 'Chemspider', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (140, 'Drug_Central', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (141, 'UM-BBD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (142, 'MO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (143, 'COMe', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (144, 'KNApSAcK', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (145, 'LINCS', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (146, 'BPDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (147, 'ChEBI', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (148, 'WebElements', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (149, 'MFO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (150, 'LIPID MAPS', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (151, 'MolBase', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (152, 'UM-BBD_compID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (153, 'PPDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (637, 'GlyGen', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (638, 'GlyTouCan', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (639, 'LIPID_MAPS_class', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (640, 'AGR', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (815, 'dto', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (154, 'FAO/WHO_standards', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (155, 'PRO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (156, 'SUBMITTER', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (157, 'PDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (158, 'FMAID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (159, 'SAEL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (160, 'PO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (161, 'TO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (162, 'NIF_Cell', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (163, 'similar to CL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (164, 'MONDO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (165, 'DOID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (166, 'ICD10', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (167, 'ICD9', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (168, 'SCTID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (169, 'ICD9CM', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (170, 'GARD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (171, 'VFB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (172, 'RESID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (173, 'OMIM', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (174, 'MSH', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (175, 'MedDRA', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (176, 'MedlinePlus', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (177, 'SNOMEDCT_US', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (178, 'Fyler', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (179, 'Orphanet', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (180, 'OAE', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (181, 'ICD-10', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (182, 'EPCC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (183, 'OMIMPS', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (184, 'ONCOTREE', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (185, 'ICDO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (186, 'CSP', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (187, 'NDFRT', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (188, 'MFOMD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (189, 'OGMS', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (190, 'HGNC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (191, 'Wikidata', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (192, 'SCDO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (193, 'UMLS_CUI', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (194, 'GTR', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (195, 'MEDGEN', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (196, 'LOINC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (197, 'ICD10CM', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (198, 'ICD11', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (199, 'GC_ID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (200, 'PATO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (201, 'PO_GIT', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (202, 'APweb', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (203, 'ZEA', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (204, 'ORCiD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (205, 'COHD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (206, 'NCIm', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (207, 'CRISP Thesaurus 2006, Term Number 2000-0386, http', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (208, 'ORDO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (209, 'PERSON', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (210, 'MCC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (211, 'CLO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (212, 'Germplasm', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (213, 'JAX', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (214, 'RGD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (215, 'NCI Metathesaurus', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (216, 'ATCC number', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (217, 'birn_anat', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (218, 'IDOMAL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (219, 'CASRN', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (220, 'DSSTox_Generic_SID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (221, 'DSSTox_CID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (222, 'STRUCTURE_ChemicalName_IUPAC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (223, 'STRUCTURE_Formula', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (224, 'OBI', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (225, 'MTH', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (226, 'obo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (227, 'SCTID_2010_1_31', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (228, 'ATC_code', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (229, 'IDO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (230, 'FBtc', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (231, 'UniProt', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (232, 'NCI_Thesaurus', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (233, 'ERO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (234, 'BAO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (235, 'UMLS CUI', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (236, 'modelled on http', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (237, 'SNOMEDCT_US_2018_03_01', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (238, 'ISBN-10', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (239, 'ISBN-13', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (240, 'SYMP', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (241, 'OMIT', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (242, 'CHMO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (243, 'PRIDE', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (244, 'DI', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (245, 'ENM', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (246, 'NPO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (247, 'TXPO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (248, 'SNOMEDCT_US_2021_03_01', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (249, 'DERMO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (250, 'MeDRA', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (251, 'NCI', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (252, 'DC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (253, 'url', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (254, 'SUBSET_SIREN', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (255, 'SNOMEDCT_2010_1_31', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (256, 'ICD10EXP', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (257, 'OMOP', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (258, 'IMDRF', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (259, 'Cellausorus', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (260, 'SwissLipids', 'January 03 2022', NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (261, 'ClinicalTrials', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (262, 'FDA', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (263, 'DailyMed', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (264, 'ATC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (265, 'b0379d68-e54b-4dac-851c-0336cfb85a8f', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (266, 'b247b724-0e9c-44bd-bbd2-b84e6cb5b072', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (267, 'bdc28b9d-8fe6-4175-ae67-002f61493279', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (268, 'c4f989c4-2805-49a3-8241-b5e37405e9ca', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (269, 'c97fd8d8-cf3e-4326-938d-bea58477423d', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (270, 'f17c9aff-4a92-418e-989f-b8e9dd62caf6', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (271, 'faea86d9-5281-441b-be89-0f0be1d49129', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (272, '1ddd2e2d-2ace-4c87-8ec6-d3b5730b3e7c', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (273, '2cb7c05f-bfbf-4393-8c2a-70218b824b41', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (274, '2018775c-cd54-4503-88bc-9b62d35a1fdb', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (275, '3b77fb04-1e62-43af-a84b-5d5eb7388525', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (276, '3bde5870-7193-4f1c-a9c3-930cef534038', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (277, '3f8eb04d-3229-487c-9c0e-b65e1a1f035e', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (278, '53cf86f8-2122-4125-bc72-117201b4f4c4', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (279, '58938a8e-359c-49d8-b3f7-5e1fd7e1a109', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (280, '8b514716-1381-474b-93d5-1ac01c28f54c', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (281, '8378426c-67ca-4bab-a9cf-531093b9b95e', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (282, 'bspotemp', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (283, 'nbo', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (284, 'ncbitaxon', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (285, 'pco', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (286, 'ro', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (287, 'so', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (288, 'uberon#', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (289, 'mgi:102949', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (290, 'mgi:2151253', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (291, 'mgi:2385287', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (292, 'mgi:2674311', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (293, 'mgi:96837', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (294, 'mgi:96925', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (295, 'mgi:98297', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (296, 'mgi:98733', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (297, 'mgi:99604', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (298, 'owl#nothing', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (299, 'ICD10WHO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (300, 'IEDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (301, 'dbSNP', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (355, 'dbSNP-ALFA', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (366, 'PhosphoSite Plus', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (367, 'EC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (368, 'RHEA', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (369, 'KEGG_REACTION', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (370, 'UM-BBD_enzymeID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (371, 'UM-BBD_reactionID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (372, 'TC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (373, 'KEGG_PATHWAY', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (374, 'UM-BBD_pathwayID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (375, 'UniPathway', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (376, 'SABIO-RK', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (377, 'VZ', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (378, 'InterPro', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (379, 'UniProtKB-KW', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (380, 'CORUM', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (381, 'BioCyc', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (382, 'Intact', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (383, 'Quantification', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (384, 'GO_Central', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (385, 'Ensembl', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (386, 'UniProtKB-SubCell', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (387, 'ProtInc', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (388, 'HPA', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (389, 'BHF-UCL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (390, 'UniProtKB-UniPathway', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (391, 'ParkinsonsUK-UCL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (392, 'NTNU_SB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (393, 'ARUK-UCL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (394, 'MGI', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (395, 'HGNC-UCL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (396, 'DFLAT', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (397, 'CAFA', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (398, 'FlyBase', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (399, 'ComplexPortal', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (400, 'WormBase', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (401, 'UniProtKB-EC', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (402, 'LIFEdb', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (403, 'AgBase', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (404, 'CACAO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (405, 'UniProtKB-UniRule', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (406, 'SynGO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (407, 'SGD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (408, 'YuBioLab', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (409, 'GDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (410, 'SYSCILIA_CCNET', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (411, 'Alzheimers_University_of_Toronto', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (412, 'SynGO-UCL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (413, 'GOC-OWL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (414, 'Roslin', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (415, 'TAIR', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (416, 'Gramene', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (417, 'EnsemblPlants', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (418, 'GeneDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (419, 'CGD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (420, 'dictyBase', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (421, 'ZFIN', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (422, 'PomBase', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (423, 'EnsemblFungi', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (424, 'MTBBASE', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (425, 'AspGD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (426, 'EcoliWiki', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (427, 'EcoCyc', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (428, 'PseudoCAP', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (429, 'EnsemblMetazoa', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (430, 'PAMGO_VMD', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (431, 'PAMGO_MGG', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (432, 'MENGO', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (433, 'JCVI', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (434, 'PHI-base', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (435, 'CollecTF', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (436, 'ASAP', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (437, 'TIGR', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (438, 'PAMGO_GAT', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (439, 'EnsemblProtists', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (440, 'DIBU', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (441, 'neuronames', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (442, 'kegg.module', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (500, 'DECIPHER', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (566, 'nifext', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (567, 'snap', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (600, 'DisProt', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (633, 'VSDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (634, 'ECMDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (635, 'YMDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (636, 'FooDB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (641, 'PMCID', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (642, 'Pesticides', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (676, 'SureChEMBL', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (809, 'image', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (810, 'ilxtr', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (811, 'pirsf', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (812, 'panther', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (813, 'iuphar', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (814, 'iupharfam', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (887, 'PubChem', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (888, 'T3DB', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (921, 'iao', NULL, NULL, NULL, 'D', NULL);
INSERT INTO DB_SCHEMA_NAME.source VALUES (987, 'GAZ', NULL, NULL, NULL, 'D', NULL);

INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (1, 'Standard');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (2, 'Vertebrate Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (3, 'Yeast Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (4, 'Mold Mitochondrial; Protozoan Mitochondrial; Coelenterate Mitochondrial; Mycoplasma; Spiroplasma');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (5, 'Invertebrate Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (6, 'Ciliate Nuclear; Dasycladacean Nuclear; Hexamita Nuclear ');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (7, 'Echinoderm Mitochondrial; Flatworm Mitochondrial ');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (8, 'Euplotid Nuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (9, 'Bacterial Archaeal and Plant Plastid');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (10, 'Alternative Yeast Nuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (11, 'Ascidian Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (12, 'Alternative Flatworm Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (13, 'Blepharisma Macronuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (14, 'Chlorophycean Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (15, 'Trematode Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (16, 'Scenedesmus obliquus Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (17, 'Thraustochytrium Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (18, 'Rhabdopleuridae Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (19, 'Candidate Division SR1 and Gracilibacteria');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (20, 'Pachysolen tannophilus Nuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (21, 'Karyorelict Nuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (22, 'Condylostoma Nuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (23, 'Mesodinium Nuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (24, 'Peritrich Nuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (25, 'Blastocrithidia Nuclear');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (26, 'Balanophoraceae Plastid');
INSERT INTO DB_SCHEMA_NAME.translation_tbl VALUES (27, 'Cephalodiscidae Mitochondrial');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1, 1, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (2, 1, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (3, 1, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (4, 1, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (5, 1, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (6, 1, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (7, 1, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (8, 1, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (9, 1, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (10, 1, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (11, 1, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (12, 1, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (13, 1, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (14, 1, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (15, 1, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (16, 1, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (17, 1, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (18, 1, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (19, 1, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (20, 1, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (21, 1, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (22, 1, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (23, 1, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (24, 1, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (25, 1, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (26, 1, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (27, 1, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (28, 1, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (29, 1, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (30, 1, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (31, 1, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (32, 1, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (33, 1, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (34, 1, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (35, 1, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (36, 1, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (37, 1, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (38, 1, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (39, 1, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (40, 1, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (41, 1, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (42, 1, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (43, 1, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (44, 1, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (45, 1, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (46, 1, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (47, 1, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (48, 1, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (49, 1, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (50, 1, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (51, 1, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (52, 1, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (53, 1, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (54, 1, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (55, 1, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (56, 1, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (57, 1, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (58, 1, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (59, 1, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (60, 1, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (61, 1, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (62, 1, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (63, 1, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (64, 1, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (65, 2, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (66, 2, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (67, 2, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (68, 2, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (69, 2, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (70, 2, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (71, 2, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (72, 2, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (73, 2, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (74, 2, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (75, 2, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (76, 2, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (77, 2, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (78, 2, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (79, 2, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (80, 2, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (81, 2, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (82, 2, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (83, 2, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (84, 2, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (85, 2, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (86, 2, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (87, 2, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (88, 2, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (89, 2, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (90, 2, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (91, 2, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (92, 2, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (93, 2, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (94, 2, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (95, 2, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (96, 2, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (97, 2, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (98, 2, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (99, 2, 'ATA', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (100, 2, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (101, 2, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (102, 2, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (103, 2, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (104, 2, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (105, 2, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (106, 2, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (107, 2, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (108, 2, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (109, 2, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (110, 2, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (111, 2, 'AGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (112, 2, 'AGG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (113, 2, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (114, 2, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (115, 2, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (116, 2, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (117, 2, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (118, 2, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (119, 2, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (120, 2, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (121, 2, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (122, 2, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (123, 2, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (124, 2, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (125, 2, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (126, 2, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (127, 2, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (128, 2, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (129, 3, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (130, 3, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (131, 3, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (132, 3, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (133, 3, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (134, 3, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (135, 3, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (136, 3, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (137, 3, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (138, 3, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (139, 3, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (140, 3, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (141, 3, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (142, 3, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (143, 3, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (144, 3, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (145, 3, 'CTT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (146, 3, 'CTC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (147, 3, 'CTA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (148, 3, 'CTG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (149, 3, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (150, 3, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (151, 3, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (152, 3, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (153, 3, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (154, 3, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (155, 3, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (156, 3, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (157, 3, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (158, 3, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (159, 3, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (160, 3, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (161, 3, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (162, 3, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (163, 3, 'ATA', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (164, 3, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (165, 3, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (166, 3, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (167, 3, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (168, 3, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (169, 3, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (170, 3, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (171, 3, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (172, 3, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (173, 3, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (174, 3, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (175, 3, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (176, 3, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (177, 3, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (178, 3, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (179, 3, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (180, 3, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (181, 3, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (182, 3, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (183, 3, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (184, 3, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (185, 3, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (186, 3, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (187, 3, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (188, 3, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (189, 3, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (190, 3, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (191, 3, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (192, 3, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (193, 4, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (194, 4, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (195, 4, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (196, 4, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (197, 4, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (198, 4, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (199, 4, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (200, 4, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (201, 4, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (202, 4, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (203, 4, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (204, 4, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (205, 4, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (206, 4, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (207, 4, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (208, 4, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (209, 4, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (210, 4, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (211, 4, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (212, 4, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (213, 4, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (214, 4, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (215, 4, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (216, 4, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (217, 4, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (218, 4, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (219, 4, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (220, 4, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (221, 4, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (222, 4, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (223, 4, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (224, 4, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (225, 4, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (226, 4, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (227, 4, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (228, 4, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (229, 4, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (230, 4, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (231, 4, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (232, 4, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (233, 4, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (234, 4, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (235, 4, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (236, 4, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (237, 4, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (238, 4, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (239, 4, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (240, 4, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (241, 4, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (242, 4, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (243, 4, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (244, 4, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (245, 4, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (246, 4, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (247, 4, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (248, 4, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (249, 4, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (250, 4, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (251, 4, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (252, 4, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (253, 4, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (254, 4, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (255, 4, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (256, 4, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (257, 5, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (258, 5, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (259, 5, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (260, 5, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (261, 5, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (262, 5, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (263, 5, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (264, 5, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (265, 5, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (266, 5, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (267, 5, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (268, 5, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (269, 5, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (270, 5, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (271, 5, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (272, 5, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (273, 5, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (274, 5, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (275, 5, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (276, 5, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (277, 5, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (278, 5, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (279, 5, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (280, 5, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (281, 5, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (282, 5, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (283, 5, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (284, 5, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (285, 5, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (286, 5, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (287, 5, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (288, 5, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (289, 5, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (290, 5, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (291, 5, 'ATA', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (292, 5, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (293, 5, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (294, 5, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (295, 5, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (296, 5, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (297, 5, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (298, 5, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (299, 5, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (300, 5, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (301, 5, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (302, 5, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (303, 5, 'AGA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (304, 5, 'AGG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (305, 5, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (306, 5, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (307, 5, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (308, 5, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (309, 5, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (310, 5, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (311, 5, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (312, 5, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (313, 5, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (314, 5, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (315, 5, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (316, 5, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (317, 5, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (318, 5, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (319, 5, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (320, 5, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (321, 6, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (322, 6, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (323, 6, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (324, 6, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (325, 6, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (326, 6, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (327, 6, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (328, 6, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (329, 6, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (330, 6, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (331, 6, 'TAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (332, 6, 'TAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (333, 6, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (334, 6, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (335, 6, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (336, 6, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (337, 6, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (338, 6, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (339, 6, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (340, 6, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (341, 6, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (342, 6, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (343, 6, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (344, 6, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (345, 6, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (346, 6, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (347, 6, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (348, 6, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (349, 6, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (350, 6, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (351, 6, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (352, 6, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (353, 6, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (354, 6, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (355, 6, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (356, 6, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (357, 6, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (358, 6, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (359, 6, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (360, 6, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (361, 6, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (362, 6, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (363, 6, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (364, 6, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (365, 6, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (366, 6, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (367, 6, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (368, 6, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (369, 6, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (370, 6, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (371, 6, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (372, 6, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (373, 6, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (374, 6, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (375, 6, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (376, 6, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (377, 6, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (378, 6, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (379, 6, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (380, 6, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (381, 6, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (382, 6, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (383, 6, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (384, 6, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (385, 7, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (386, 7, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (387, 7, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (388, 7, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (389, 7, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (390, 7, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (391, 7, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (392, 7, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (393, 7, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (394, 7, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (395, 7, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (396, 7, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (397, 7, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (398, 7, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (399, 7, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (400, 7, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (401, 7, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (402, 7, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (403, 7, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (404, 7, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (405, 7, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (406, 7, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (407, 7, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (408, 7, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (409, 7, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (410, 7, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (411, 7, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (412, 7, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (413, 7, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (414, 7, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (415, 7, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (416, 7, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (417, 7, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (418, 7, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (419, 7, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (420, 7, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (421, 7, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (422, 7, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (423, 7, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (424, 7, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (425, 7, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (426, 7, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (427, 7, 'AAA', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (428, 7, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (429, 7, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (430, 7, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (431, 7, 'AGA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (432, 7, 'AGG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (433, 7, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (434, 7, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (435, 7, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (436, 7, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (437, 7, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (438, 7, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (439, 7, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (440, 7, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (441, 7, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (442, 7, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (443, 7, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (444, 7, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (445, 7, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (446, 7, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (447, 7, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (448, 7, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (449, 8, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (450, 8, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (451, 8, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (452, 8, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (453, 8, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (454, 8, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (455, 8, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (456, 8, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (457, 8, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (458, 8, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (459, 8, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (460, 8, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (461, 8, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (462, 8, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (463, 8, 'TGA', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (464, 8, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (465, 8, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (466, 8, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (467, 8, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (468, 8, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (469, 8, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (470, 8, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (471, 8, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (472, 8, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (473, 8, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (474, 8, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (475, 8, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (476, 8, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (477, 8, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (478, 8, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (479, 8, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (480, 8, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (481, 8, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (482, 8, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (483, 8, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (484, 8, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (485, 8, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (486, 8, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (487, 8, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (488, 8, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (489, 8, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (490, 8, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (491, 8, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (492, 8, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (493, 8, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (494, 8, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (495, 8, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (496, 8, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (497, 8, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (498, 8, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (499, 8, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (500, 8, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (501, 8, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (502, 8, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (503, 8, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (504, 8, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (505, 8, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (506, 8, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (507, 8, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (508, 8, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (509, 8, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (510, 8, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (511, 8, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (512, 8, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (513, 9, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (514, 9, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (515, 9, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (516, 9, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (517, 9, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (518, 9, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (519, 9, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (520, 9, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (521, 9, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (522, 9, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (523, 9, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (524, 9, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (525, 9, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (526, 9, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (527, 9, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (528, 9, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (529, 9, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (530, 9, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (531, 9, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (532, 9, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (533, 9, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (534, 9, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (535, 9, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (536, 9, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (537, 9, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (538, 9, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (539, 9, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (540, 9, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (541, 9, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (542, 9, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (543, 9, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (544, 9, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (545, 9, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (546, 9, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (547, 9, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (548, 9, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (549, 9, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (550, 9, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (551, 9, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (552, 9, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (553, 9, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (554, 9, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (555, 9, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (556, 9, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (557, 9, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (558, 9, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (559, 9, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (560, 9, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (561, 9, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (562, 9, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (563, 9, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (564, 9, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (565, 9, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (566, 9, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (567, 9, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (568, 9, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (569, 9, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (570, 9, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (571, 9, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (572, 9, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (573, 9, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (574, 9, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (575, 9, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (576, 9, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (577, 10, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (578, 10, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (579, 10, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (580, 10, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (581, 10, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (582, 10, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (583, 10, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (584, 10, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (585, 10, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (586, 10, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (587, 10, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (588, 10, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (589, 10, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (590, 10, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (591, 10, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (592, 10, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (593, 10, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (594, 10, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (595, 10, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (596, 10, 'CTG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (597, 10, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (598, 10, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (599, 10, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (600, 10, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (601, 10, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (602, 10, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (603, 10, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (604, 10, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (605, 10, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (606, 10, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (607, 10, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (608, 10, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (609, 10, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (610, 10, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (611, 10, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (612, 10, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (613, 10, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (614, 10, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (615, 10, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (616, 10, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (617, 10, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (618, 10, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (619, 10, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (620, 10, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (621, 10, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (622, 10, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (623, 10, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (624, 10, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (625, 10, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (626, 10, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (627, 10, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (628, 10, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (629, 10, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (630, 10, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (631, 10, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (632, 10, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (633, 10, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (634, 10, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (635, 10, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (636, 10, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (637, 10, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (638, 10, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (639, 10, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (640, 10, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (641, 11, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (642, 11, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (643, 11, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (644, 11, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (645, 11, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (646, 11, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (647, 11, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (648, 11, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (649, 11, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (650, 11, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (651, 11, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (652, 11, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (653, 11, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (654, 11, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (655, 11, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (656, 11, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (657, 11, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (658, 11, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (659, 11, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (660, 11, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (661, 11, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (662, 11, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (663, 11, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (664, 11, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (665, 11, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (666, 11, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (667, 11, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (668, 11, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (669, 11, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (670, 11, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (671, 11, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (672, 11, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (673, 11, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (674, 11, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (675, 11, 'ATA', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (676, 11, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (677, 11, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (678, 11, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (679, 11, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (680, 11, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (681, 11, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (682, 11, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (683, 11, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (684, 11, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (685, 11, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (686, 11, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (687, 11, 'AGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (688, 11, 'AGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (689, 11, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (690, 11, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (691, 11, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (692, 11, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (693, 11, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (694, 11, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (695, 11, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (696, 11, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (697, 11, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (698, 11, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (699, 11, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (700, 11, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (701, 11, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (702, 11, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (703, 11, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (704, 11, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (705, 12, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (706, 12, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (707, 12, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (708, 12, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (709, 12, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (710, 12, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (711, 12, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (712, 12, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (713, 12, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (714, 12, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (715, 12, 'TAA', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (716, 12, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (717, 12, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (718, 12, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (719, 12, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (720, 12, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (721, 12, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (722, 12, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (723, 12, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (724, 12, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (725, 12, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (726, 12, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (727, 12, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (728, 12, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (729, 12, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (730, 12, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (731, 12, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (732, 12, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (733, 12, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (734, 12, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (735, 12, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (736, 12, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (737, 12, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (738, 12, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (739, 12, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (740, 12, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (741, 12, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (742, 12, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (743, 12, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (744, 12, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (745, 12, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (746, 12, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (747, 12, 'AAA', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (748, 12, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (749, 12, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (750, 12, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (751, 12, 'AGA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (752, 12, 'AGG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (753, 12, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (754, 12, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (755, 12, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (756, 12, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (757, 12, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (758, 12, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (759, 12, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (760, 12, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (761, 12, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (762, 12, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (763, 12, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (764, 12, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (765, 12, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (766, 12, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (767, 12, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (768, 12, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (769, 13, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (770, 13, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (771, 13, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (772, 13, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (773, 13, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (774, 13, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (775, 13, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (776, 13, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (777, 13, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (778, 13, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (779, 13, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (780, 13, 'TAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (781, 13, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (782, 13, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (783, 13, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (784, 13, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (785, 13, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (786, 13, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (787, 13, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (788, 13, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (789, 13, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (790, 13, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (791, 13, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (792, 13, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (793, 13, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (794, 13, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (795, 13, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (796, 13, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (797, 13, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (798, 13, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (799, 13, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (800, 13, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (801, 13, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (802, 13, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (803, 13, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (804, 13, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (805, 13, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (806, 13, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (807, 13, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (808, 13, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (809, 13, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (810, 13, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (811, 13, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (812, 13, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (813, 13, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (814, 13, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (815, 13, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (816, 13, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (817, 13, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (818, 13, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (819, 13, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (820, 13, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (821, 13, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (822, 13, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (823, 13, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (824, 13, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (825, 13, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (826, 13, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (827, 13, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (828, 13, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (829, 13, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (830, 13, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (831, 13, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (832, 13, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (833, 14, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (834, 14, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (835, 14, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (836, 14, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (837, 14, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (838, 14, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (839, 14, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (840, 14, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (841, 14, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (842, 14, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (843, 14, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (844, 14, 'TAG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (845, 14, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (846, 14, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (847, 14, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (848, 14, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (849, 14, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (850, 14, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (851, 14, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (852, 14, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (853, 14, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (854, 14, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (855, 14, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (856, 14, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (857, 14, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (858, 14, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (859, 14, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (860, 14, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (861, 14, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (862, 14, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (863, 14, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (864, 14, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (865, 14, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (866, 14, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (867, 14, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (868, 14, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (869, 14, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (870, 14, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (871, 14, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (872, 14, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (873, 14, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (874, 14, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (875, 14, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (876, 14, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (877, 14, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (878, 14, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (879, 14, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (880, 14, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (881, 14, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (882, 14, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (883, 14, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (884, 14, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (885, 14, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (886, 14, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (887, 14, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (888, 14, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (889, 14, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (890, 14, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (891, 14, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (892, 14, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (893, 14, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (894, 14, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (895, 14, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (896, 14, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (897, 15, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (898, 15, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (899, 15, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (900, 15, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (901, 15, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (902, 15, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (903, 15, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (904, 15, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (905, 15, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (906, 15, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (907, 15, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (908, 15, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (909, 15, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (910, 15, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (911, 15, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (912, 15, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (913, 15, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (914, 15, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (915, 15, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (916, 15, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (917, 15, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (918, 15, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (919, 15, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (920, 15, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (921, 15, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (922, 15, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (923, 15, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (924, 15, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (925, 15, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (926, 15, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (927, 15, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (928, 15, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (929, 15, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (930, 15, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (931, 15, 'ATA', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (932, 15, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (933, 15, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (934, 15, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (935, 15, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (936, 15, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (937, 15, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (938, 15, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (939, 15, 'AAA', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (940, 15, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (941, 15, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (942, 15, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (943, 15, 'AGA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (944, 15, 'AGG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (945, 15, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (946, 15, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (947, 15, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (948, 15, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (949, 15, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (950, 15, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (951, 15, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (952, 15, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (953, 15, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (954, 15, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (955, 15, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (956, 15, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (957, 15, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (958, 15, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (959, 15, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (960, 15, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (961, 16, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (962, 16, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (963, 16, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (964, 16, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (965, 16, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (966, 16, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (967, 16, 'TCA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (968, 16, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (969, 16, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (970, 16, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (971, 16, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (972, 16, 'TAG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (973, 16, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (974, 16, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (975, 16, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (976, 16, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (977, 16, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (978, 16, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (979, 16, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (980, 16, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (981, 16, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (982, 16, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (983, 16, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (984, 16, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (985, 16, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (986, 16, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (987, 16, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (988, 16, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (989, 16, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (990, 16, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (991, 16, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (992, 16, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (993, 16, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (994, 16, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (995, 16, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (996, 16, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (997, 16, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (998, 16, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (999, 16, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1000, 16, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1001, 16, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1002, 16, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1003, 16, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1004, 16, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1005, 16, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1006, 16, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1007, 16, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1008, 16, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1009, 16, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1010, 16, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1011, 16, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1012, 16, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1013, 16, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1014, 16, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1015, 16, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1016, 16, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1017, 16, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1018, 16, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1019, 16, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1020, 16, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1021, 16, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1022, 16, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1023, 16, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1024, 16, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1025, 17, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1026, 17, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1027, 17, 'TTA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1028, 17, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1029, 17, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1030, 17, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1031, 17, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1032, 17, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1033, 17, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1034, 17, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1035, 17, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1036, 17, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1037, 17, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1038, 17, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1039, 17, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1040, 17, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1041, 17, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1042, 17, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1043, 17, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1044, 17, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1045, 17, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1046, 17, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1047, 17, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1048, 17, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1049, 17, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1050, 17, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1051, 17, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1052, 17, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1053, 17, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1054, 17, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1055, 17, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1056, 17, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1057, 17, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1058, 17, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1059, 17, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1060, 17, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1061, 17, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1062, 17, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1063, 17, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1064, 17, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1065, 17, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1066, 17, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1067, 17, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1068, 17, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1069, 17, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1070, 17, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1071, 17, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1072, 17, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1073, 17, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1074, 17, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1075, 17, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1076, 17, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1077, 17, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1078, 17, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1079, 17, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1080, 17, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1081, 17, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1082, 17, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1083, 17, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1084, 17, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1085, 17, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1086, 17, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1087, 17, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1088, 17, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1089, 18, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1090, 18, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1091, 18, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1092, 18, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1093, 18, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1094, 18, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1095, 18, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1096, 18, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1097, 18, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1098, 18, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1099, 18, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1100, 18, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1101, 18, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1102, 18, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1103, 18, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1104, 18, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1105, 18, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1106, 18, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1107, 18, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1108, 18, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1109, 18, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1110, 18, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1111, 18, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1112, 18, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1113, 18, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1114, 18, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1115, 18, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1116, 18, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1117, 18, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1118, 18, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1119, 18, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1120, 18, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1121, 18, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1122, 18, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1123, 18, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1124, 18, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1125, 18, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1126, 18, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1127, 18, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1128, 18, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1129, 18, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1130, 18, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1131, 18, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1132, 18, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1133, 18, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1134, 18, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1135, 18, 'AGA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1136, 18, 'AGG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1137, 18, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1138, 18, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1139, 18, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1140, 18, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1141, 18, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1142, 18, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1143, 18, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1144, 18, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1145, 18, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1146, 18, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1147, 18, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1148, 18, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1149, 18, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1150, 18, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1151, 18, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1152, 18, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1153, 19, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1154, 19, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1155, 19, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1156, 19, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1157, 19, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1158, 19, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1159, 19, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1160, 19, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1161, 19, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1162, 19, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1163, 19, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1164, 19, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1165, 19, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1166, 19, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1167, 19, 'TGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1168, 19, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1169, 19, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1170, 19, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1171, 19, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1172, 19, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1173, 19, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1174, 19, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1175, 19, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1176, 19, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1177, 19, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1178, 19, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1179, 19, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1180, 19, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1181, 19, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1182, 19, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1183, 19, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1184, 19, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1185, 19, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1186, 19, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1187, 19, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1188, 19, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1189, 19, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1190, 19, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1191, 19, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1192, 19, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1193, 19, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1194, 19, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1195, 19, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1196, 19, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1197, 19, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1198, 19, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1199, 19, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1200, 19, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1201, 19, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1202, 19, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1203, 19, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1204, 19, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1205, 19, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1206, 19, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1207, 19, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1208, 19, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1209, 19, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1210, 19, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1211, 19, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1212, 19, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1213, 19, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1214, 19, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1215, 19, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1216, 19, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1217, 20, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1218, 20, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1219, 20, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1220, 20, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1221, 20, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1222, 20, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1223, 20, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1224, 20, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1225, 20, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1226, 20, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1227, 20, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1228, 20, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1229, 20, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1230, 20, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1231, 20, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1232, 20, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1233, 20, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1234, 20, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1235, 20, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1236, 20, 'CTG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1237, 20, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1238, 20, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1239, 20, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1240, 20, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1241, 20, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1242, 20, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1243, 20, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1244, 20, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1245, 20, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1246, 20, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1247, 20, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1248, 20, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1249, 20, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1250, 20, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1251, 20, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1252, 20, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1253, 20, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1254, 20, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1255, 20, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1256, 20, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1257, 20, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1258, 20, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1259, 20, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1260, 20, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1261, 20, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1262, 20, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1263, 20, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1264, 20, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1265, 20, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1266, 20, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1267, 20, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1268, 20, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1269, 20, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1270, 20, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1271, 20, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1272, 20, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1273, 20, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1274, 20, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1275, 20, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1276, 20, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1277, 20, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1278, 20, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1279, 20, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1280, 20, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1281, 21, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1282, 21, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1283, 21, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1284, 21, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1285, 21, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1286, 21, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1287, 21, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1288, 21, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1289, 21, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1290, 21, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1291, 21, 'TAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1292, 21, 'TAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1293, 21, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1294, 21, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1295, 21, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1296, 21, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1297, 21, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1298, 21, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1299, 21, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1300, 21, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1301, 21, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1302, 21, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1303, 21, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1304, 21, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1305, 21, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1306, 21, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1307, 21, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1308, 21, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1309, 21, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1310, 21, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1311, 21, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1312, 21, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1313, 21, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1314, 21, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1315, 21, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1316, 21, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1317, 21, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1318, 21, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1319, 21, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1320, 21, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1321, 21, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1322, 21, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1323, 21, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1324, 21, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1325, 21, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1326, 21, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1327, 21, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1328, 21, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1329, 21, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1330, 21, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1331, 21, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1332, 21, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1333, 21, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1334, 21, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1335, 21, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1336, 21, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1337, 21, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1338, 21, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1339, 21, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1340, 21, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1341, 21, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1342, 21, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1343, 21, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1344, 21, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1345, 22, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1346, 22, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1347, 22, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1348, 22, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1349, 22, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1350, 22, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1351, 22, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1352, 22, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1353, 22, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1354, 22, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1355, 22, 'TAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1356, 22, 'TAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1357, 22, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1358, 22, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1359, 22, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1360, 22, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1361, 22, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1362, 22, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1363, 22, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1364, 22, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1365, 22, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1366, 22, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1367, 22, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1368, 22, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1369, 22, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1370, 22, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1371, 22, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1372, 22, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1373, 22, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1374, 22, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1375, 22, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1376, 22, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1377, 22, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1378, 22, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1379, 22, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1380, 22, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1381, 22, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1382, 22, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1383, 22, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1384, 22, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1385, 22, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1386, 22, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1387, 22, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1388, 22, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1389, 22, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1390, 22, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1391, 22, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1392, 22, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1393, 22, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1394, 22, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1395, 22, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1396, 22, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1397, 22, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1398, 22, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1399, 22, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1400, 22, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1401, 22, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1402, 22, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1403, 22, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1404, 22, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1405, 22, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1406, 22, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1407, 22, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1408, 22, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1409, 23, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1410, 23, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1411, 23, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1412, 23, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1413, 23, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1414, 23, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1415, 23, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1416, 23, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1417, 23, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1418, 23, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1419, 23, 'TAA', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1420, 23, 'TAG', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1421, 23, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1422, 23, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1423, 23, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1424, 23, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1425, 23, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1426, 23, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1427, 23, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1428, 23, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1429, 23, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1430, 23, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1431, 23, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1432, 23, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1433, 23, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1434, 23, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1435, 23, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1436, 23, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1437, 23, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1438, 23, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1439, 23, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1440, 23, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1441, 23, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1442, 23, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1443, 23, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1444, 23, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1445, 23, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1446, 23, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1447, 23, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1448, 23, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1449, 23, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1450, 23, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1451, 23, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1452, 23, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1453, 23, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1454, 23, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1455, 23, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1456, 23, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1457, 23, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1458, 23, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1459, 23, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1460, 23, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1461, 23, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1462, 23, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1463, 23, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1464, 23, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1465, 23, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1466, 23, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1467, 23, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1468, 23, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1469, 23, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1470, 23, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1471, 23, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1472, 23, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1473, 24, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1474, 24, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1475, 24, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1476, 24, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1477, 24, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1478, 24, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1479, 24, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1480, 24, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1481, 24, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1482, 24, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1483, 24, 'TAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1484, 24, 'TAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1485, 24, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1486, 24, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1487, 24, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1488, 24, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1489, 24, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1490, 24, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1491, 24, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1492, 24, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1493, 24, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1494, 24, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1495, 24, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1496, 24, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1497, 24, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1498, 24, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1499, 24, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1500, 24, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1501, 24, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1502, 24, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1503, 24, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1504, 24, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1505, 24, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1506, 24, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1507, 24, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1508, 24, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1509, 24, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1510, 24, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1511, 24, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1512, 24, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1513, 24, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1514, 24, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1515, 24, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1516, 24, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1517, 24, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1518, 24, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1519, 24, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1520, 24, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1521, 24, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1522, 24, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1523, 24, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1524, 24, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1525, 24, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1526, 24, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1527, 24, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1528, 24, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1529, 24, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1530, 24, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1531, 24, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1532, 24, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1533, 24, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1534, 24, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1535, 24, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1536, 24, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1537, 25, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1538, 25, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1539, 25, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1540, 25, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1541, 25, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1542, 25, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1543, 25, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1544, 25, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1545, 25, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1546, 25, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1547, 25, 'TAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1548, 25, 'TAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1549, 25, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1550, 25, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1551, 25, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1552, 25, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1553, 25, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1554, 25, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1555, 25, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1556, 25, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1557, 25, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1558, 25, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1559, 25, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1560, 25, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1561, 25, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1562, 25, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1563, 25, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1564, 25, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1565, 25, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1566, 25, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1567, 25, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1568, 25, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1569, 25, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1570, 25, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1571, 25, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1572, 25, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1573, 25, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1574, 25, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1575, 25, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1576, 25, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1577, 25, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1578, 25, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1579, 25, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1580, 25, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1581, 25, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1582, 25, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1583, 25, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1584, 25, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1585, 25, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1586, 25, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1587, 25, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1588, 25, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1589, 25, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1590, 25, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1591, 25, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1592, 25, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1593, 25, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1594, 25, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1595, 25, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1596, 25, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1597, 25, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1598, 25, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1599, 25, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1600, 25, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1601, 26, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1602, 26, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1603, 26, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1604, 26, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1605, 26, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1606, 26, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1607, 26, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1608, 26, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1609, 26, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1610, 26, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1611, 26, 'TAA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1612, 26, 'TAG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1613, 26, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1614, 26, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1615, 26, 'TGA', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1616, 26, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1617, 26, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1618, 26, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1619, 26, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1620, 26, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1621, 26, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1622, 26, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1623, 26, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1624, 26, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1625, 26, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1626, 26, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1627, 26, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1628, 26, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1629, 26, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1630, 26, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1631, 26, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1632, 26, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1633, 26, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1634, 26, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1635, 26, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1636, 26, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1637, 26, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1638, 26, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1639, 26, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1640, 26, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1641, 26, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1642, 26, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1643, 26, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1644, 26, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1645, 26, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1646, 26, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1647, 26, 'AGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1648, 26, 'AGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1649, 26, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1650, 26, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1651, 26, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1652, 26, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1653, 26, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1654, 26, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1655, 26, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1656, 26, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1657, 26, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1658, 26, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1659, 26, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1660, 26, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1661, 26, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1662, 26, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1663, 26, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1664, 26, 'GGG', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1665, 27, 'TTT', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1666, 27, 'TTC', 'F');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1667, 27, 'TTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1668, 27, 'TTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1669, 27, 'TCT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1670, 27, 'TCC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1671, 27, 'TCA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1672, 27, 'TCG', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1673, 27, 'TAT', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1674, 27, 'TAC', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1675, 27, 'TAA', 'Y');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1676, 27, 'TAG', '*');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1677, 27, 'TGT', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1678, 27, 'TGC', 'C');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1679, 27, 'TGA', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1680, 27, 'TGG', 'W');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1681, 27, 'CTT', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1682, 27, 'CTC', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1683, 27, 'CTA', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1684, 27, 'CTG', 'L');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1685, 27, 'CCT', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1686, 27, 'CCC', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1687, 27, 'CCA', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1688, 27, 'CCG', 'P');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1689, 27, 'CAT', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1690, 27, 'CAC', 'H');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1691, 27, 'CAA', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1692, 27, 'CAG', 'Q');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1693, 27, 'CGT', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1694, 27, 'CGC', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1695, 27, 'CGA', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1696, 27, 'CGG', 'R');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1697, 27, 'ATT', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1698, 27, 'ATC', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1699, 27, 'ATA', 'I');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1700, 27, 'ATG', 'M');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1701, 27, 'ACT', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1702, 27, 'ACC', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1703, 27, 'ACA', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1704, 27, 'ACG', 'T');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1705, 27, 'AAT', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1706, 27, 'AAC', 'N');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1707, 27, 'AAA', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1708, 27, 'AAG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1709, 27, 'AGT', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1710, 27, 'AGC', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1711, 27, 'AGA', 'S');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1712, 27, 'AGG', 'K');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1713, 27, 'GTT', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1714, 27, 'GTC', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1715, 27, 'GTA', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1716, 27, 'GTG', 'V');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1717, 27, 'GCT', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1718, 27, 'GCC', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1719, 27, 'GCA', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1720, 27, 'GCG', 'A');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1721, 27, 'GAT', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1722, 27, 'GAC', 'D');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1723, 27, 'GAA', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1724, 27, 'GAG', 'E');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1725, 27, 'GGT', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1726, 27, 'GGC', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1727, 27, 'GGA', 'G');
INSERT INTO DB_SCHEMA_NAME.codon VALUES (1728, 27, 'GGG', 'G');

INSERT INTO DB_SCHEMA_NAME.dna_rev VALUES ('A','T');
INSERT INTO DB_SCHEMA_NAME.dna_rev VALUES ('T','A');
INSERT INTO DB_SCHEMA_NAME.dna_rev VALUES ('C','G');
INSERT INTO DB_SCHEMA_NAME.dna_rev VALUES ('G','C');
INSERT INTO DB_SCHEMA_NAME.dna_rev VALUES ('g','c');
INSERT INTO DB_SCHEMA_NAME.dna_rev VALUES ('c','g');
INSERT INTO DB_SCHEMA_NAME.dna_rev VALUES ('a','t');
INSERT INTO DB_SCHEMA_NAME.dna_rev VALUES ('t','a');


INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (0,'complex',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (2,'copy number loss',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:0001743'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (3,'deletion',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:0000159'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (4,'duplication',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:1000035'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (5,'fusion',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:0000806'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (6,'indel',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:1000032'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (7,'insertion',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:0000667'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (8,'inversion',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:1000036'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (9,'microsatellite',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:0000289'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (10,'protein_only',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (11,'single nucleotide variant',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:0001483'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (12,'tandem duplication',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (13,'translocation',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:0000199'));
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (14,'variation',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_Variant_type VALUES (15,'copy number gain',(SELECT so_entry_id FROM SO_ENTRY  WHERE SO_ID='SO:0001742'));


INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (1,	'practice guideline'	,'Information reviewed by the ClinGen Steering Committee.',	4);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (2,	'reviewed by expert panel'	,'Panel membership:<br/><ul><li>A membership list must be provided for review when requesting Expert Panel status for submissions.</li><li>It is recommended that the expert panel include medical professionals caring for patients relevant to the disease gene in question, medical geneticists, clinical laboratory diagnosticians and/or molecular pathologists who report such findings and appropriate researchers relevant to the disease, gene, functional assays and statistical analyses.</li><li>It is expected that the individuals comprising the expert panel process represent multiple institutions</li><li>It is expected that the individuals comprising the expert panel should be international in scope, and are considered by the community to be experts in the field based on publications and long-standing scope of work.</li><li>ClinGen hopes that there is only one expert panel per gene and that the panel is inclusive of known experts in the field. Therefore, if you have expertise in a gene that is already evaluated by an expert panel, please consider joining efforts with the existing panel or provide justification for the necessity of an additional panel.</li></ul><br/>Information should be provided with regard to any potential financial conflicts of interest of the panel members and how conflicts are managed.',	3);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (3,	'criteria provided, multiple submitters, no conflicts',	'Assertion criteria refers to a citation (PubMed ID, PubMedCentral ID, or DOI) or an electronic document (a Word document or PDF) that describes:

the variant classification terms used by the submitter (e.g. pathogenic, uncertain significance, benign, or appropriate terms for other types of variation) and
the criteria required to assign a variant to each category'	,2);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (4,	'criteria provided, conflicting classifications',	'Multiple submitted records with a classification, where assertion criteria and evidence for the classification (or a public contact) were provided. However there are conflicting classifications. The conflicting values for the classification are enumerated.',	1);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (5,	'criteria provided, single submitter',	'One submitter provided an interpretation with assertion criteria and evidence (or a public contact). For a submission to achieve this status, the submitter must:

Document that the allele or genotype was classified according to a comprehensive review of evidence consistent with, or more thorough than, current practice guidelines ( e.g . review of case data, genetic data and functional evidence from the literature and analysis of population frequency and computational predictions)
Include a clinical significance assertion using a variant scoring system with a minimum of three levels for monogenic disease variants (pathogenic, uncertain significance, benign) or appropriate terms for other types of variation.
Provide a publication or other electronic document (such as a PDF) that describes the variant assessment terms used ( e.g. pathogenic, uncertain significance, benign or appropriate terms for other types of variation) and the criteria required to assign a variant to each category. This document will be available to ClinVar users via the ClinVar website (link provided for all submitted assertions).
Submit available supporting evidence or rationale for classification ( e.g. literature citations, total number of case observations, descriptive summary of evidence, web link to site with additional data, etc .) or be willing to be contacted by ClinVar users to provide supporting evidence. In other words, contact information for one person on the submission must be submitted as ''public''.',	1);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (6,	'no classification for the individual variant',	'The variant was not classified directly in any submitted record; it was submitted to ClinVar only as part of a haplotype or a genotype.',	0);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (7,	'no assertion criteria provided',	
'There are one or more submitted records with a classification but without assertion criteria and evidence for the classification (or a public contact).',	0);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (8,	'no classification provided',	'There are one or more submitted records without a classification.',	0);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (9,     'flagged submission','Issue with the submission',0);
INSERT INTO DB_SCHEMA_NAME.clinical_variant_review_status VALUES (10,    'no classifications from unflagged records','Unflagged records',0);


INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(1,'Benign',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 2,'Likely benign',NULL);
           INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 3,'Uncertain significance',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 4,'Likely pathogenic',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 5,'Pathogenic',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 6,'Likely pathogenic, low penetrance',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 7,'Pathogenic, low penetrance',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 8,'Uncertain risk allele',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 9,'Likely risk allele',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(10,'Established risk allele',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(11,'drug response',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(12,'association'                      ,'For variants identified in GWAS study and further interpreted for their clinical significance');
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(13,'protective'                       ,'For variants that decrease the risk of a disorder, including infections');
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(14,'Affects'                          ,'For variants that cause a non-disease phenotype, such as lactose intolerance');
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(15,'conflicting data from submitters' ,'Only for submissions from a consortium, where groups within the consortium have conflicting intepretations of a variant but provide a single submission to ClinVar');
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(16,'other'                            ,'If ClinVar does not have the appropriate term for your submission, we ask that you submit "other" as clinical significance and contact us to discuss if there are other terms we should add');
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(17,'not provided'                     ,'For submissions without an interpretation of clinical significance. The primary goal of ClinVar is to archive reports of clinical significance of variants. Therefore submissions with a clinical significance of "not provided" should be limited to:
"literature only" submissions that report a publication about the variant, without interpreting the clinical significance                                                                                                                             +
"research" submissions that provide functional significance (e.g. undetectable protein level) but no interpretation of clinical significance                                                                                                          +
"phenotyping only" submissions from clinics or physicians that provide additional information about individuals with the variant, such as observed phenotypes, but do not interpret the clinical significance');
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(18,'risk factor',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(19,'confers sensitivity',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES( 20,'histocompatibility',NULL);
INSERT INTO DB_SCHEMA_NAME.clinical_significance VALUES(21,'association not found',NULL);
