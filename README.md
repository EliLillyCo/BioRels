# Welcome to the Eli Lilly BioRels (BIOlogical RELationshipS) implementation 

## Background
BioRels is a library for any scientist involved in drug discovery, biology or chemistry. 
This repository contains all the scripts and configuration files necessary for you to build
a database aggregating the public data of up to 32 data sources.

The [documentation file](DOCS/Biorels_Complete_documentation.docx) will provides you will all the necessary information to configure, run, take advantage of, and expand upon it.

## REQUIREMENTS:

BioRels will require the following:
* An enabled PostGreSQL server (https://www.postgresql.org/download/) 
* Server path, port, and connection information for the PostGreSQL server
* Singularity-CE
* Disk space:
  * 1 to 2Tb preferably - Depends highly on the data sources.
  * Up to 50 Tb if all data sources are required
* Computing power:
  * SGE Cluster
  * OR single cpu (experimental)
  * Cloud computing with Nextflow coming soon


## Quickstart

If you want to evaluate BioRels with the minimal amount of configuration, please go to [QUICKSTART](BACKEND/INSTALL/QUICKSTART/README.md)

## INSTALLATION:

Please follow the guidelines provided in the documentation file. 

## CONTACT

For any questions, please contact desaphy_jeremy[@]lilly.com

## LICENSE

GPL V3


## COPYRIGHT

Copyright (c)  2024 Eli Lilly and Company All rights reserved.
All source files is subject to the license and copyright provided in this repository.

## DISCLAIMER

* We make no warranties regarding the correctness of the data, and disclaim liability for damages resulting from its use. 
* We do not provided unrestricted permission regarding the use of the data, as some data may be covered by patents or other rights. 
* User is responsible for complying with the legal terms for BioRels, any data source and packages used in BioRels


# DATA SOURCES

The list of data sources with the license and release frequency are provided below to the best of our knowledge:

| Data type | Data Source | Release frequency | License |
| --------- | --------- | --------- | --------- |
| Taxonomy  | NCBI Taxonomy | Daily | NCBI Policy |
| Gene  | NCBI Gene | Daily | NCBI Policy |
|  | Ensembl | Bi-Weekly | No restrictions |
| Orthologs  | NCBI Orthologs | Daily | NCBI Policy |
|  | HCOP | |  | CC0 |
| Variant/Mutation  | dbSNP | No schedule | NCBI Policy |
| Small molecule | ChEMBL | No schedule | CC BY-SA 3.0 |
| Clinical variant  | Clinvar | Weekly | NCBI Policy |
| RNA Expression  | GTEX | Version 8  | GTEx License |
| Protein domain  | InterPro | 1-3 Months | CC 0 |
| Drug | DrugBank | Weekly | DrugBank license |
| Disease  | MONDO | Bi-Monthly | CC BY 4.0   |
| Anatomy  | UBERON | Bi-Monthly | CC BY 3.0 |
| Small molecule/Lipid | SwissLipids | Weekly | CC BY 4.0 |
| Small molecule/Patent | SureChEMBL | No schedule | CC BY-SA 3.0 |
| Cell Lines  | Cellosaurus  | No schedule | CC BY 4.0 |
| Disease/Drug/Clinical trials  | Open Target | Bi-Monthly | CC 0 |
| Disease Ontology  | EFO | Monthly | Apache 2.0 |
| Sequence Ontology  | SO | No schedule | CC BY-SA 4.0 |
| Evidence Ontology  | ECO | Monthly | CC0 1.0 Public domain |
| BioAssay Ontology  | BAO  | No schedule | CC BY 4.0 |
| Protein information  | UniProt | Quarterly | CC BY 4.0 |
| Publication  | PubMed | Daily | NCBI Policy |
| Pathway  | Reactome | Bi-monthly | CC 0 |
| Gene Ontology  | GO | Monthly | CC BY 4.0 |
| Gene/DNA/RNA  | RefSeq | No schedule | NCBI Policy |  |
|  | Ensembl |  | No restrictions |
| Clinical Trials | US Clinical trials | Daily | US CT.gov Terms of use |
| Disease/Gene information | OMIM | Daily | OMIM License |
| Gene information | Gene Reviews® |  Frequently | Gene Reviews Copyright | 
| Liver Toxicity | LiverTox |  Frequently | Freely available |
| PubMed Central (PMC) | PMC | Daily | CC Only |