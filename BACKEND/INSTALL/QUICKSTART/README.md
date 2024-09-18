

# Miminal requirements:

* Linux environment
* Singularity-CE
* Postgresql server


# Quick Installation & Run:

## Set up:

* Create a postgres database and keep your user/password/host/port and database name handy
* Please modify the following variables in setenv.sh located in BACKEND/SCRIPT/SHELL/:
* * TG_DIR: Path to the root directory of BioRels
* * PGPASSWORD=Postgres password
* * PGUSER=Postgres user
* * DB_HOST=Postgres host
* * DB_PORT=Postgres port
* * DB_NAME=Database name
* * DB_SCHEMA=Database schema
* * If you are behind a proxy, please set up the proxy variables accordingly.
* Please execute the following command to load the environment variables: source BACKEND/SCRIPT/SHELL/setenv.sh

You can check this was successful by running echo $TG_DIR to confirm the root path is set

## Compile singularity container

Please go to $TG_DIR/BACKEND/CONTAINER and run the following command:
singularity build biorels_container.sif biorels.sing.txt

## Quick install and configuration

Please go to $TG_DIR/BACKEND/INSTALL and run the following commands:
biorels_php runInstall.php
And follow the instructions.
Please execute the following file:
cp QUICKSTART/CONFIG_USER $TG_DIR/BACKEND/SCRIPT/CONFIG/
This will set up the system to process the following data sources:
* Taxonomy
* NCBI Gene (Only Homo sapiens genes)
* Gene Ontology
* Sequence, ECO, EFO, Bioassay, MONDO Ontologies
* SwissLipids
* Cellausorus
* Gene Reviews
* LiverTox

## Execution
While still in $TG_DIR/BACKEND/INSTALL, please execute the following command:
biorels_php gen_single_script.php > biorels_execute.sh
sh biorels_execute.sh

This will execute the necessary scripts in the correct order. It will take about an hour and a half.


## Querying the database

Please go to $TG_DIR/BACKEND/SCRIPT/API
You can execute the following queries:
* biorels_php biorels_api.php  get_taxon_by_tax_id -TAX_ID 9606
* biorels_php biorels_api.php  get_taxon_by_scientific_name -SCIENTIFIC_NAME "Homo sapiens"
* biorels_php biorels_api.php get_chromosome_for_taxon -TAX_ID 9606
* biorels_php biorels_api.php get_gene_for_chromosome -TAX_ID 9606 -CHROMOSOME 1
* biorels_php biorels_api.php get_gene_location -GENE_ID 1017
* biorels_php biorels_api.php get_cell_info -ACC 'CVCL_B6YM'
* biorels_php biorels_api.php search_cell_line_by_disease -DISEASE_TAG 'MONDO_0009692'
* biorels_php biorels_api.php search_gene_ontology -AC 'GO:0010389'
* biorels_php biorels_api.php get_child_gene_ontology -AC 'GO:0010389' -MAX_LEVEL 4


## Exporting & Importing:

Please go to $TG_DIR/BACKEND/SCRIPT/BIORJ/
You can execute the following scripts:
* biorels_php api_import.php --JSON Example/HeLa.biorj
* biorels_php api_export.php -JSON_OUTPUT=CVCL_B6YM.biorj --JSON_PRETTY_PRINT CELL CVCL_B6YM && more CVCL_B6YM.biorj
* biorels_php api_import.php --JSON Example/rs712.json
