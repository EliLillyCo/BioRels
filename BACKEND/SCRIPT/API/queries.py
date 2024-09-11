import psycopg2
import json
import sys
import os
sys.path.append(os.getenv('TG_DIR')+"/BACKEND/SCRIPT/LIB_PYTHON/")
import time
from datetime import datetime
import smtplib
import psycopg2
import pickle
import sys
import pprint as pp
from fct_utils import *
from loader import *

# Function to convert JSON-like strings to Python dictionaries
def convert_to_dict(json_like_string):
    try:
        return json.loads(json_like_string.replace("'", "\""))
    except json.JSONDecodeError:
        return {}

# #################################################/
# #################################################/
# ################ GENOMIC ECOSYSTEM #####################
# #################################################/
# #################################################/

# #################################################/
# ######################/ TAXON  #######################
# #################################################/


# $[API]
# Title: Taxon Search By NCBI Tax ID
# Function: get_taxon_by_tax_id
# Description: Search for a taxon by using its Tax ID
# Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# Parameter: COMPLETE | Complete taxon information | boolean | false | optional | Default: false
# Return: Taxon ID, scientific name, common name, taxonomic lineage and child taxonomic entries
# Ecosystem: Genomics:Species
# Example: python3.12 biorels_api.py get_taxon_by_tax_id -TAX_ID 9606
# $[/API]
def get_taxon_by_tax_id(TAX_ID,COMPLETE=False):
    query = f"SELECT * FROM taxon WHERE tax_id='{TAX_ID}'"
    res = run_query(query)
    if not COMPLETE: 
        return res
    for line in res:

        line['CHILD'] = run_query(f"SELECT t.*, th2.tax_level FROM taxon_tree th1, taxon_tree th2, taxon t WHERE th1.taxon_id={line['taxon_id']} AND th1.level_left < th2.level_left AND th1.level_right > th2.level_right AND th2.taxon_id=t.taxon_id ORDER BY th2.tax_level DESC")
        line['PARENT'] = run_query(f"SELECT t.*, th2.tax_level FROM taxon_tree th1, taxon_tree th2, taxon t WHERE th1.taxon_id={line['taxon_id']} AND th1.level_left > th2.level_left AND th1.level_right < th2.level_right AND th2.taxon_id=t.taxon_id ORDER BY th2.tax_level ASC")
    return res




# $[API]
# Title: Taxon Search By Scientific Name
# Function: get_taxon_by_scientific_name
# Description: Search for a taxon by using its scientific name
# Parameter: SCIENTIFIC_NAME | Scientific name | string | Homo Sapiens | required
# Ecosystem: Genomics:Species
# Example: python3.12 biorels_api.py get_taxon_by_scientific_name -SCIENTIFIC_NAME Homo
# $[/API]
def get_taxon_by_scientific_name(SCIENTIFIC_NAME):
    query = f"SELECT * FROM taxon WHERE scientific_name='{SCIENTIFIC_NAME}'"
    res = run_query(query)
    if res == []:
        query = f"SELECT * FROM taxon WHERE scientific_name LIKE '%{SCIENTIFIC_NAME}%'"
        res = run_query(query)
    for line in res:
       
        line['CHILD'] = run_query(f"SELECT t.*, th2.tax_level FROM taxon_tree th1, taxon_tree th2, taxon t WHERE th1.taxon_id={line['taxon_id']} AND th1.level_left < th2.level_left AND th1.level_right > th2.level_right AND th2.taxon_id=t.taxon_id ORDER BY th2.tax_level DESC")
        line['PARENT'] = run_query(f"SELECT t.*, th2.tax_level FROM taxon_tree th1, taxon_tree th2, taxon t WHERE th1.taxon_id={line['taxon_id']} AND th1.level_left > th2.level_left AND th1.level_right < th2.level_right AND th2.taxon_id=t.taxon_id ORDER BY th2.tax_level ASC")
    return res

# $[API]
# Title: Get taxon lineage by taxon ID
# Function: get_taxon_parent_lineage
# Description: Get the taxonomic lineage of a taxon by using its taxon ID
# Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# Return: Taxon ID, scientific name, common name, taxonomic lineage
# Ecosystem: Genomics:Species
# Example: python3.12 biorels_api.py get_taxon_parent_lineage -TAX_ID 9606
# $[/API]
def get_taxon_parent_lineage(TAX_ID):
    query = f"SELECT t.*, th2.tax_level FROM taxon tr, taxon_tree th1, taxon_tree th2, taxon t WHERE th1.taxon_id=tr.taxon_id AND tr.tax_id='{TAX_ID}' AND th1.level_left > th2.level_left AND th1.level_right < th2.level_right AND th2.taxon_id=t.taxon_id ORDER BY th2.tax_level ASC"
    return run_query(query)




# $[API]
# Title: Get taxon lineage by taxon ID
# Function: get_taxon_child_lineage
# Description: Get the taxonomic lineage of a taxon by using its taxon ID
# Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# Parameter: DEPTH | Depth to add to the requested taxon's level | int | 2 | optional | Default: None
# Return: Taxon ID, scientific name, common name, taxonomic lineage
# Ecosystem: Genomics:Species
# Example: python3.12 biorels_api.py get_taxon_child_lineage -TAX_ID 9606
# $[/API]
def get_taxon_child_lineage(TAX_ID, DEPTH=None):
    query = f"SELECT t.*, th2.tax_level FROM taxon tr, taxon_tree th1, taxon_tree th2, taxon t WHERE th1.taxon_id=tr.taxon_id AND tr.tax_id='{TAX_ID}' AND th1.level_left < th2.level_left AND th1.level_right > th2.level_right AND th2.taxon_id=t.taxon_id "
    if DEPTH != None:
        query += f"AND th2.tax_level = th1.tax_level+{DEPTH}"
    query += " ORDER BY th2.tax_level ASC"
    return run_query(query)




# #################################################/
# ##################/ TAXON - LOCUS & GENE ####################
# #################################################/


# $[API]
# Title: Get all chromosome by taxon ID
# Function: get_chromosome_for_taxon
# Description: Get all chromosome for a taxon by using its NCBI taxon ID
# Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# Return: Locus ID, locus name, taxon ID, scientific name, taxonomic identifier
# Ecosystem: Genomics:Locus & Gene|Species
# Example: python3.12 biorels_api.py get_chromosome_for_taxon -TAX_ID 9606
# $[/API]
def get_chromosome_for_taxon(TAX_ID):
    query = f"SELECT c.* FROM chromosome c, taxon t WHERE c.taxon_id = t.taxon_id AND tax_id='{TAX_ID}'"
    return run_query(query)




# $[API]
# Title: Get all chromosome map for taxon ID
# Function: get_chromosome_map_for_taxon
# Description: Get all chromosome map for a taxon by using its NCBI taxon ID
# // Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# // Parameter: CHROMOSOME | Chromosome name | string | 1 |optional | Default: ''
# // Parameter: ARM | Chromosome arm | string | p | optional | Default: ''
# // Parameter: BAND | Chromosome band | string | 1 | optional | Default: ''
# // Parameter: SUBBAND | Chromosome subband | string | 1 | optional | 
# Return: Group by chromosome name, returns chromosome map _id, chromosome number, map location, position, arm, ban, subband.
# Ecosystem: Genomics:Locus & Gene|Species
# Example: python3.12 biorels_api.py get_chromosome_map_for_taxon -TAX_ID 9606 -CHROMOSOME 1
# Example: python3.12 biorels_api.py get_chromosome_map_for_taxon -TAX_ID 9606 -ARM p
# $[/API]
def get_chromosome_map_for_taxon(TAX_ID, CHROMOSOME=None, ARM=None, BAND=None, SUBBAND=None):
    query = f"SELECT cm.*, chr_num FROM chromosome c, chr_map cm, taxon t WHERE c.taxon_id = t.taxon_id AND cm.chr_id = c.chr_id AND tax_id='{TAX_ID}'"
    query += f" AND chr_num='{CHROMOSOME}'" if CHROMOSOME != None else ''
    query += f" AND arm='{ARM}'" if ARM != None else ''
    query += f" AND band='{BAND}'" if BAND != None else ''
    query += f" AND subband='{SUBBAND}'" if SUBBAND != None else ''
    query += " ORDER BY chr_num, map_location ASC"
    res = run_query(query)
    data = {}
    for line in res:
        if line['chr_num'] not in data:
            data[line['chr_num']] = []
        data[line['chr_num']].append(line)
    return data




# // $[API]
# // Title: Get all gene for taxon ID
# // Function: get_gene_for_taxon
# // Description: Get all gene for a taxon by using its NCBI taxon ID
# // Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# // Return: Gene ID, gene symbol, gene name, taxon ID, scientific name, taxonomic identifier
# // Ecosystem: Genomics:Locus & Gene|Species
# // Example:  python3.12 biorels_api.py  get_gene_for_taxon -TAX_ID 9606
# // Warning: Long query execution time
# // $[/API]

def get_gene_for_taxon(TAX_ID):
    
    query = f"""
        SELECT chr_num, map_location, c.chr_id, cm.chr_map_id, ge.*
        FROM chr_gn_map cgm, gn_entry ge, chromosome c, chr_map cm, taxon t
        WHERE cgm.chr_map_id = cm.chr_map_id
        AND cgm.gn_entry_Id = ge.gn_Entry_Id
        AND c.taxon_id = t.taxon_id
        AND cm.chr_id = c.chr_id
        AND tax_id='{TAX_ID}'
        ORDER BY chr_num, map_location ASC
    """
    
    rows = run_query(query)
    
    data = {}
    for line in rows:
        chr_num = line['chr_num']
        map_location = line['map_location']
        if chr_num not in data:
            data[chr_num] = {}
        if map_location not in data[chr_num]:
            data[chr_num][map_location] = []
        data[chr_num][map_location].append(line)
    
    
    return data



# // $[API]
# // Title: Get all gene for chromosome
# // Function: get_gene_for_chromosome
# // Description: Get all gene for a chromosome by using its NCBI taxon ID and chromosome name
# // Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# // Parameter: CHROMOSOME | Chromosome name | string | 1 | required
# // Parameter: MAP_LOCATION | Map location | string | 1p12 | optional
# // Return: NCBI Gene ID, symbol ,full name, gene type, map location, chromosome number, status
# // Ecosystem: Genomics:Locus & Gene|Species
# // Example: python3.12 biorels_api.py  get_gene_for_chromosome -TAX_ID 9606 -CHROMOSOME 1
# // $[/API]
def get_gene_for_chromosome(TAX_ID, CHROMOSOME, MAP_LOCATION=None):
    
    query = f"""
        SELECT chr_num, map_location, c.chr_id, cm.chr_map_id, ge.*
        FROM chr_gn_map cgm, gn_entry ge, chromosome c, chr_map cm, taxon t
        WHERE cgm.chr_map_id = cm.chr_map_id
        AND cgm.gn_entry_Id = ge.gn_Entry_Id
        AND c.taxon_id = t.taxon_id
        AND cm.chr_id = c.chr_id
        AND tax_id='{TAX_ID}'
        AND chr_num='{CHROMOSOME}'
        {'AND map_location=\'' + MAP_LOCATION + '\'' if MAP_LOCATION != None else ''}
        ORDER BY chr_num, map_location ASC
    """
    
    rows = run_query(query)
   
    data = {}
    for line in rows:
        chr_num = line['chr_num']
        map_location = line['map_location']
        
        data.setdefault(chr_num, {}).setdefault(map_location, []).append(line)
    
    
    return data



# // $[API]
# // Title: Get gene location by gene ID
# // Function: get_gene_location
# // Description: Get gene location by using its NCBI gene ID
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Taxon ID, scientific name, taxonomic identifier, chromosome number, map location, gene ID, gene symbol, gene name, gene type, gene status
# // Ecosystem: Genomics:Locus & Gene|Species
# // Example: python3.12 biorels_api.py get_gene_location -GENE_ID 1017
# // $[/API]
def get_gene_location(GENE_ID):
    
    query = f"""
        SELECT * FROM chr_gn_map cgm, gn_entry ge,chromosome c, chr_map cm, taxon t
	WHERE cgm.chr_map_id = cm.chr_map_id
	AND cgm.gn_entry_Id = ge.gn_Entry_Id
	AND c.taxon_id = t.taxon_id
	AND c.chr_id = cm.chr_id
	AND ge.gene_id='{GENE_ID}' 
	ORDER BY chr_num,map_location ASC
    """
    
    rows = run_query(query)
   
    return rows




# // $[API]
# // Title: Gene Search By NCBI ID
# // Function: get_gene_by_gene_id
# // Description: Search for a gene by using its gene ID
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Ecosystem: Genomics:gene
# // Example: python3.12 biorels_api.py get_gene_by_gene_id -GENE_ID 1017
# // $[/API]
def get_gene_by_gene_id(GENE_ID):
   

    query = f"""
        SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
        FROM mv_gene_sp
        WHERE GENE_ID={GENE_ID}
        GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
    """
    
    
    res = run_query(query)

    if len(res) == 0:
        NEW_GENE_ID = ''
        n = 0
        while n < 5:
            n += 1
            query=f"SELECT alt_gene_id, gn_entry_id FROM gn_history WHERE gene_id={GENE_ID}"
            res = run_query(query)
            if not res:
                return False
            if res[0][1] != '':
                NEW_GENE_ID = res[0][0]
                break
            elif res[0][0] != '-':
                GENE_ID = res[0][0]
            else:
                break
        
        if NEW_GENE_ID == '':
            return False
        
        query = f"""
            SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
            FROM MV_GENE
            WHERE GENE_ID={NEW_GENE_ID}
            GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
        """
        
        res=run_query(query)

    if len(res) > 0:
        for row in res:
            row['syn_value'] = row['syn_value'].split('|')
    
    return res





# // $[API]
# // Title: Gene Search By Symbol
# // Function: get_gene_by_gene_symbol
# // Description: Search for a gene by using its gene symbol
# // Parameter: SYMBOL | Gene symbol | string | CDK2 | required
# // Parameter: TAX_ID | Taxonomic identifier | array | 9606,10090 | optional | Default: None
# // Ecosystem: Genomics:gene
# // Example: python3.12 biorels_api.py get_gene_by_gene_symbol -SYMBOL CDK2
# // $[/API]
def get_gene_by_gene_symbol(SYMBOL, TAX_ID=None):
    
    STR_TAXON = ''
    TAXONS = []

    if TAX_ID is not None:
        if not all(tax.isnumeric() for tax in TAX_ID):
            raise ValueError("Taxonomic identifier must be numeric")
        TAXONS = [str("'"+tax+"'") for tax in TAX_ID]
        STR_TAXON = f" AND tax_id IN ({','.join(TAXONS)})"

    query = f"""
        SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
        FROM mv_gene
        WHERE symbol='{SYMBOL}' {STR_TAXON}
        GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
    """

    res=run_query(query)

    for row in res:
        row['syn_value'] = row['syn_value'].split('|')

    return res





# // $[API]
# // Title: Gene Search By Name
# // Function: get_gene_by_gene_name
# // Description: Search for a gene by using its gene name
# // Parameter: NAME | Gene name | string | Cyclin-dependent kinase | required
# // Parameter: TAX_ID | Taxonomic identifier | array | 9606,10090 | optional | Default: None
# // Ecosystem: Genomics:gene
# // Example: python3.12 biorels_api.py get_gene_by_gene_name -NAME cyclin-dependent kinase 2
# // $[/API]
def get_gene_by_gene_name(NAME, TAX_ID=None):
    
    STR_TAXON = ''
    TAXONS = []

    if TAX_ID is not None:
        if not all(isinstance(tax, int) for tax in TAX_ID):
            raise ValueError("Taxonomic identifier must be numeric")
        TAXONS = [str(tax) for tax in TAX_ID]
        STR_TAXON = f" AND tax_id IN ({','.join(TAXONS)})"

    query = f"""
        SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
        FROM mv_gene
        WHERE full_name='{NAME}' {STR_TAXON}
        GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
    """

    res=run_query(query)

    if res == []:
        query = f"""
            SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
            FROM mv_gene
            WHERE full_name LIKE '%{NAME}%' {STR_TAXON}
            GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
        """

        res=run_query(query)

    if res == []:
        query = f"""
            SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
            FROM mv_gene
            WHERE syn_value LIKE '%{NAME}%' {STR_TAXON}
            GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
        """

        res=run_query(query)

    if res == []:
        pos = NAME.find('.')
        if pos != -1:
            NAME = NAME[:pos]
        query = f"""
			SELECT SYMBOL, FULL_NAME,GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID, GENE_SEQ_NAME
			FROM gene_seq GS, mv_gene WHERE GENE_SEQ_NAME LIKE '{NAME}' AND SYMBOL IS NOT NULL
			'{STR_TAXON}'
			GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID, GENE_SEQ_NAME";
		"""
        res=run_query(query)

    for row in res:
        if (row['syn_value'] != None): row['syn_value'] = row['syn_value'].split('|')

    
    return res


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////// GENOME ASSEMBLY  ///////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: Get all genome assembly
# // Function: get_all_genome_assembly
# // Description: Get all genome assembly
# // Return: Genome assembly ID, taxon ID, scientific name, assembly name, assembly level, assembly type, assembly unit, assembly version, assembly date, assembly role, assembly role order, assembly role level, assembly role type, assembly role group, assembly role group order, assembly role group level, assembly role group type, assembly role group order
# // Parameter: DUMMY | Dummy parameter | string | optional | Default: None
# // Ecosystem: Genomics:genome assembly
# // Example: python3.12 biorels_api.py get_all_genome_assembly
# // $[/API]
def get_all_genome_assembly(Dummy=None):
	query = "SELECT * FROM genome_assembly"
	res = run_query(query)
	for line in res:
		line['annotation'] = json.loads(line['annotation'])
	return res

# // $[API]
# // Title: Get genome assembly by taxon
# // Function: get_genome_assembly_by_taxon
# // Description: Get genome assembly by using its NCBI taxon ID
# // Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# // Return: Scientific name, chromosome number, taxon ID, chromosome sequence ID, chromosome sequence name, RefSeq name, RefSeq version, GenBank name, GenBank version, sequence role, sequence length
# // Ecosystem: Genomics:genome assembly
# // Example: python3.12 biorels_api.py get_genome_assembly_by_taxon -TAX_ID 9606
# // $[/API]
def get_genome_assembly_by_taxon(TAX_ID):
	query = f"""
		SELECT *
		FROM genome_assembly g, taxon t 
	 	where t.taxon_id = g.taxon_id 
		AND tax_Id = '{TAX_ID}'
	"""
	
	res = run_query(query)
	for line in res:
		line['annotation'] = json.loads(line['annotation'])
	return res


# // $[API]
# // Title: Get chromosome assembly by taxon
# // Function: get_chromosome_assembly_by_taxon
# // Description: Get chromosome sequence information reported for a given assembly by using its NCBI taxon ID
# // Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# // Parameter: CHR_NUM | Chromosome number | string | 1 | optional | Default: None
# // Parameter: SEQ_ROLE | Sequence role | string | Scaffold | optional | Default: None
# // Parameter: CHR_SEQ_NAME | Chromosome sequence name: Genbank or refseq | string | HSCHR1_CTG9_UNLOCALIZED | optional | Default: None
# // Return: Scientific name, chromosome number, taxon ID, chromosome sequence ID, chromosome sequence name, RefSeq name, RefSeq version, GenBank name, GenBank version, sequence role, sequence length
# // Ecosystem: Genomics:genome assembly
# // Example: python3.12 biorels_api.py get_chromosome_assembly_by_taxon -TAX_ID 9606
# // Example: python3.12 biorels_api.py get_chromosome_assembly_by_taxon -TAX_ID 9606 -CHR_SEQ_NAME HSCHR1_CTG9_UNLOCALIZED
# // $[/API]
def get_chromosome_assembly_by_taxon(TAX_ID, CHR_NUM=None, SEQ_ROLE=None, CHR_SEQ_NAME=None):
    query = f"""
		SELECT scientific_name,chr_num,tax_Id, cs.chr_seq_Id,chr_seq_name, refseq_name,refseq_version,genbank_name,genbank_version,seq_Role,seq_len
     FROM taxon t, chromosome c, genome_assembly g, chr_seq cs
     where t.taxon_id = g.taxon_id 
     AND cs.chr_id = c.chr_id
     AND g.genome_assembly_id = cs.genome_assembly_id 
     {'AND chr_num=\'' + CHR_NUM + '\'' if CHR_NUM != None else ''}
     {'AND seq_role=\'' + SEQ_ROLE + '\'' if SEQ_ROLE != None else ''}"""
    if (CHR_SEQ_NAME != None):
        pos = CHR_SEQ_NAME.find('.')
        if pos != -1:
            CHR_SEQ_NAME = CHR_SEQ_NAME[:pos]
        query += f"AND (chr_seq_name='{CHR_SEQ_NAME}' OR refseq_name='{CHR_SEQ_NAME}' OR genbank_name='{CHR_SEQ_NAME}')" 
    query+= f"AND tax_Id = '{TAX_ID}' ORDER BY seq_role, chr_num 
    """
	
    res = run_query(query)
    return res


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////// GENOMIC SEQUENCE  ///////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Search a chromosome position record by name and position. 
# // Function: get_chromosome_position_info
# // Description: Search by chromosome by providing either the chromosome name or the RefSeq or GenBank name, and the position on the chromosome.
# // Parameter: CHR | chromsome name (1,2,3, X, Y, MT) or RefSeq Name or GenBank Name | string | 1 | required
# // Parameter: CHR_POS | Chromosome position | int | 1 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | optional | 9606 | Default: 9606
# // Return: Chromosome position, chromosome, chromosome sequence, RefSeq name, RefSeq version, GenBank name, GenBank version, sequence role, chromosome sequence name, assembly unit, nucleotide, position, scientific name, taxonomic identifier
# // Ecosystem: Genomics:chromosome|chromosome position
# // Example: python3.12 biorels_api.py get_chromosome_position_info -CHR 1 -CHR_POS 12
# // Example: python3.12 biorels_api.py get_chromosome_position_info -CHR MT -CHR_POS 1000
# // $[/API]
def get_chromosome_position_info(CHR, CHR_POS, TAX_ID='9606'):
    query = f"""
		SELECT CHR_SEQ_POS_ID, C.CHR_ID, CHR_NUM, CS.CHR_SEQ_ID, REFSEQ_NAME, REFSEQ_VERSION, GENBANK_NAME, GENBANK_VERSION, SEQ_ROLE, CHR_SEQ_NAME, ASSEMBLY_UNIT, NUCL, CHR_POS AS POSITION, SCIENTIFIC_NAME, TAX_ID
		FROM CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP, TAXON T
		WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
		AND T.TAXON_ID = C.TAXON_ID
		AND TAX_ID = '{TAX_ID}'
		AND (CHR_SEQ_NAME = '{CHR}' OR REFSEQ_NAME = '{CHR}' OR GENBANK_NAME = '{CHR}')
		AND CHR_POS = {CHR_POS}
	"""
    return run_query(query)


# // $[API]
# // Title: Get chromosome sequence into fasta format
# // Function: get_chromosome_seq_to_fasta
# // Description: Get the chromosome sequence in fasta format by providing the chromosome name or the RefSeq or GenBank name, and the start and end position on the chromosome.
# // Parameter: CHR_NAME | Chromosome name (1,2,3, X, Y, MT) or RefSeq Name or GenBank Name | string | 1 | required
# // Parameter: CHR_POS_START | Starting position in the chromosome | int | 10 | required
# // Parameter: CHR_POS_END | Ending position in the chromosome | int | 1231 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | optional | 9606 | Default: 9606
# // Return: Chromosome sequence in fasta format
# // Ecosystem: Genomics:chromosome|chromosome sequence
# // Example: python3.12 biorels_api.py get_chromosome_seq_to_fasta -CHR_NAME 1 -CHR_POS_START 1 -CHR_POS_END 1000
# // Example: python3.12 biorels_api.py get_chromosome_seq_to_fasta -CHR_NAME MT -CHR_POS_START 1 -CHR_POS_END 1000
# // $[/API]
def get_chromosome_seq_to_fasta(CHR_NAME, CHR_POS_START, CHR_POS_END, TAX_ID='9606'):
	res=get_chromosome_assembly_by_taxon(TAX_ID, CHR_SEQ_NAME=CHR_NAME)
	if res == []:
		res=get_chromosome_assembly_by_taxon(TAX_ID, CHR_NAME=CHR_NAME)
		if res == []:
			return []
	SEQS=[]
	for chr in res:
		STR=f"""{chr['chr_seq_name']}|{chr['refseq_name']}.{chr['refseq_version']}|{chr['genbank_name']}.{chr['genbank_version']}|{chr['seq_role']}|{chr['scientific_name']}|{chr['tax_id']}"""

		CHR_SEQ_ID = chr['chr_seq_id']
		
		STEP=50000
		#print(f">{CHR_NAME} {CHR_POS_START}-{CHR_POS_END}")
		CHR_POS=int(CHR_POS_START)
		END_POS=int(CHR_POS_END)
		
		while CHR_POS < END_POS:
			query = f"""
				SELECT NUCL
				FROM CHR_SEQ_POS
				WHERE CHR_SEQ_ID = {CHR_SEQ_ID}
				AND CHR_POS >= {CHR_POS}
				AND CHR_POS < {CHR_POS+STEP}
			"""
			res2 = run_query(query)
			
			for line in res2:
				STR+=line['nucl']
				CHR_POS+=1
				if (CHR_POS >= END_POS):
					break
				if (len(str) == 100):
					STR+="\n"
					
		SEQS.append(STR)
	return SEQS


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ORTHOLOGS /////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get orthologs
# // Function: get_orthologs
# // Description: Get orthologs by providing the gene ID
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Parameter: ONLY_MAIN | Only main species | boolean | false | optional | Default: false
# // Return: Orthologs gene ID, gene symbol, gene name, species, taxonomic identifier
# // Ecosystem: Genomics:orthologs
# // Example: python3.12 biorels_api.py  get_orthologs -GENE_ID 1017
# // Example: python3.12 biorels_api.py  get_orthologs -GENE_ID 1017 -ONLY_MAIN true
# // $[/API]
def get_orthologs(GENE_ID, ONLY_MAIN=False):
	query = f"""
		SELECT DISTINCT COMP_GN_ENTRY_ID, COMP_SYMBOL, COMP_GENE_ID, COMP_GENE_NAME, COMP_SPECIES, COMP_TAX_ID
		FROM (
			SELECT MGS2.GN_ENTRY_ID AS COMP_GN_ENTRY_ID, MGS2.SYMBOL AS COMP_SYMBOL, MGS2.GENE_ID AS COMP_GENE_ID, MGS2.FULL_NAME AS COMP_GENE_NAME, MGS2.SCIENTIFIC_NAME AS COMP_SPECIES, MGS2.TAX_ID AS COMP_TAX_ID
			FROM MV_GENE_SP MGS1, GN_REL GR, MV_GENE_SP MGS2
			WHERE MGS1.GN_ENTRY_Id = GR.GN_ENTRY_R_ID
			AND MGS2.GN_ENTRY_ID = GR.GN_ENTRY_C_ID
			AND MGS1.GENE_ID = '{GENE_ID}'
		"""
	if ONLY_MAIN:
		query += "AND MGS2.TAX_ID IN ('9606','10116','10090','9913','9615','9541')"
	query += """
		ORDER BY (
			CASE
				WHEN MGS2.TAX_ID = '9606' THEN 1
				WHEN MGS2.TAX_ID = '10116' THEN 2
				WHEN MGS2.TAX_ID = '10090' THEN 3
				WHEN MGS2.TAX_ID = '9913' THEN 4
				WHEN MGS2.TAX_ID = '9615' THEN 5
				WHEN MGS2.TAX_ID = '9541' THEN 6
			END
		) ASC, MGS2.TAX_ID ASC
	) t
	"""
	return run_query(query)


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// TRANSCRIPT /////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get a specific range of a transcript sequence
# // Function: get_transcript_sequence_range
# // Description: Get a specific range of a transcript sequence by providing the transcript name, the start and end position on the transcript, and the type of range (DNA or RNA). For DNA it corresponds to position on the chromosome
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 | required
# // Parameter: START_POS | Starting position on the transcript | int | 1 | optional | Default: None
# // Parameter: END_POS | Ending position on the transcript | int | 100 | optional | Default: None
# // Parameter: TYPE | Range type: (DNA or RNA) | string | RNA | optional | Default: RNA
# // Return: Transcript sequence, translation, and alignment information
# // Ecosystem: Genomics:transcript
# // Example: python3.12 biorels_api.py get_transcript_sequence_range -TRANSCRIPT_NAME NM_000546 -START_POS 1 -END_POS 100 -TYPE RNA
# // Example: python3.12 biorels_api.py get_transcript_sequence_range -TRANSCRIPT_NAME NM_000546 -START_POS 7674205 -END_POS 7674225 -TYPE DNA
# // $[/API]
def get_transcript_sequence_range(TRANSCRIPT_NAME,START_POS=None,END_POS=None,TYPE='RNA'):
	pos=TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION=TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME=TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""
		SELECT T.TRANSCRIPT_ID,TRANSCRIPT_POS_ID,TP.NUCL,SEQ_POS,SEQ_POS_TYPE_ID,EXON_ID,CHR_POS,C.CHR_SEQ_POS_ID, C.nucl as CHR_NUCL
		FROM TRANSCRIPT_POS TP LEFT JOIN CHR_SEQ_POS C ON C.CHR_SEQ_POS_id = TP.CHR_SEQ_POS_ID,
			TRANSCRIPT T
		WHERE T.TRANSCRIPT_ID=TP.TRANSCRIPT_ID	
		  AND TRANSCRIPT_NAME='{TRANSCRIPT_NAME}'
	"""
	if TYPE == "RNA":
		if START_POS != None:
			query += f" AND SEQ_POS >= {START_POS}"
		if END_POS != None:
			query += f" AND SEQ_POS <= {END_POS}"
	elif TYPE == "DNA":
		if START_POS != None:
			query += f" AND CHR_POS >= {START_POS}"
		if END_POS != None:
			query += f" AND CHR_POS <= {END_POS}"
	else:
		raise ValueError("Range type must be either DNA or RNA")
	if pos != -1:
		query += f" AND TRANSCRIPT_VERSION='{TRANSCRIPT_VERSION}'"
	query += " ORDER BY SEQ_POS ASC"
	
	TMP = run_query(query)
	TRANSCRIPT_ID = None
	TRANSCRIPT_POS_ID = []
	if TMP == []:
		return []
	ptypes_raw = run_query("SELECT * FROM TRANSCRIPT_POS_TYPE")
	PTYPE = {}
	for PT in ptypes_raw:
		PTYPE[PT['transcript_pos_type_id']] = PT['transcript_pos_type']

	CURR_POS_TYPE = 0
	DATA_TRANSCRIPT = {}
	for K,POS_INFO in enumerate(TMP):
		TRANSCRIPT_POS_ID.append(POS_INFO['transcript_pos_id'])
		POS_INFO['TYPE'] = PTYPE[POS_INFO['seq_pos_type_id']]
		del POS_INFO['seq_pos_type_id']
		if (POS_INFO['transcript_id'] not in DATA_TRANSCRIPT):
			DATA_TRANSCRIPT[POS_INFO['transcript_id']] = {'SEQUENCE': {}, 'ALIGN': {}}
		DATA_TRANSCRIPT[POS_INFO['transcript_id']]['SEQUENCE'][POS_INFO['chr_pos']] = POS_INFO
          
	if DATA_TRANSCRIPT == {}:	
		return []
	for TRANSCRIPT_ID in DATA_TRANSCRIPT:
		DATA_TRANSCRIPT[TRANSCRIPT_ID]['TRANSCRIPT'] = run_query(f"SELECT * FROM transcript WHERE TRANSCRIPT_ID={TRANSCRIPT_ID}")
		DATA_TRANSCRIPT[TRANSCRIPT_ID]['TRANSLATION'] = run_query(f"SELECT * FROM TRANSCRIPT T, TR_PROTSEQ_AL TUA, PROT_SEQ US WHERE T.TRANSCRIPT_ID = TUA.TRANSCRIPT_ID AND TUA.PROT_SEQ_ID = US.PROT_SEQ_ID AND T.TRANSCRIPT_ID = {TRANSCRIPT_ID}")
		DATA_TRANSCRIPT[TRANSCRIPT_ID]['ALIGN'] = {}
		LIST_UNSEQ = {}
		LIST_POS = {}
		for entry in DATA_TRANSCRIPT[TRANSCRIPT_ID]['TRANSLATION']:
			DATA_TRANSCRIPT[TRANSCRIPT_ID]['ALIGN'][entry['tr_protseq_al_id']] = {}
			LIST_UNSEQ[entry['prot_seq_id']] = []
		if len(LIST_UNSEQ) == 0:
			continue
		
		tmp = run_query(f"SELECT * FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN ({','.join(map(str,LIST_UNSEQ.keys()))}) ORDER BY PROT_SEQ_ID, POSITION ASC")	
		for line in tmp:
			LIST_POS[line['prot_seq_pos_id']] = [line['letter'], line['position']]
        
		tmp = run_query(f"SELECT * FROM tr_protseq_pos_al WHERE TR_PROTSEQ_AL_ID IN ({','.join(map(str,DATA_TRANSCRIPT[TRANSCRIPT_ID]['ALIGN'].keys()))}) AND TRANSCRIPT_POS_ID IN ({','.join(map(str,TRANSCRIPT_POS_ID))})")
		for line in tmp:
			UP = LIST_POS[line['prot_seq_pos_id']]
			DATA_TRANSCRIPT[TRANSCRIPT_ID]['ALIGN'][line['tr_protseq_al_id']][line['transcript_pos_id']] = [UP[0], UP[1], line['triplet_pos']]
	return DATA_TRANSCRIPT



# // $[API]
# // Title: Search a transcript by name
# // Function: search_transcript
# // Description: Search for a transcript by using its name
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 | required
# // Return: Transcript ID, transcript name, transcript version, start position, end position, sequence hash, gene sequence ID, chromosome sequence ID, support level, partial sequence, valid alignment, gene sequence name, gene sequence version, strand, gene entry ID, feature, biotype
# // Ecosystem: Genomics:transcript
# // Example: python3.12 biorels_api.py search_transcript -TRANSCRIPT_NAME NM_000546
# // $[/API]
def search_transcript(TRANSCRIPT_NAME):
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""
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
		AND  transcript_name = '{TRANSCRIPT_NAME}'
	"""
	if pos != -1:
		query += f" AND transcript_version='{TRANSCRIPT_VERSION}'"
	return run_query(query)




# // $[API]
# // Title: Get all exons for a transcript
# // Function: get_exon_location
# // Description: Get all exons for a transcript by using its name
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 | required
# // Return: Exon ID, minimum position, maximum position
# // Ecosystem: Genomics:transcript
# // Example: python3.12 biorels_api.py get_exon_location -TRANSCRIPT_NAME NM_000546
# // $[/API]
def get_exon_location(TRANSCRIPT_NAME):
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""
		SELECT EXON_ID,MIN(SEQ_POS) as MIN_POS,MAX(SEQ_POS) as MAX_POS
		FROM TRANSCRIPT_POS TP, TRANSCRIPT T
		WHERE  T.TRANSCRIPT_ID = TP.TRANSCRIPT_ID
		AND TRANSCRIPT_NAME = '{TRANSCRIPT_NAME}'
	"""
	if pos != -1:
		query += f" AND TRANSCRIPT_VERSION='{TRANSCRIPT_VERSION}'"
	query += "GROUP BY EXON_ID ORDER BY EXON_ID ASC"
	return run_query(query)



# // $[API]
# // Title: Get all exons and their location on the chromosome for a transcript
# // Function: get_exon_dna_location
# // Description: Get all exons for a transcript by using its name
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 | required
# // Return: Exon ID, minimum position, maximum position
# // Ecosystem: Genomics:transcript
# // Example: python3.12 biorels_api.py get_exon_dna_location -TRANSCRIPT_NAME NM_000546
# // $[/API]
def get_exon_dna_location(TRANSCRIPT_NAME):
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""
		SELECT EXON_ID,MIN(CHR_POS) as MIN_POS,MAX(CHR_POS) as MAX_POS,CHR_SEQ_NAME
		FROM TRANSCRIPT_POS TP 
		LEFT JOIN CHR_SEQ_POS CSP ON CSP.CHR_SEQ_POS_ID = TP.CHR_SEQ_POS_ID
		LEFT JOIN CHR_SEQ CS ON CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID, TRANSCRIPT T
		WHERE  T.TRANSCRIPT_ID = TP.TRANSCRIPT_ID
		AND TRANSCRIPT_NAME='{TRANSCRIPT_NAME}'
	"""
	if pos != -1:
		query += f" AND TRANSCRIPT_VERSION='{TRANSCRIPT_VERSION}'"
	query += "GROUP BY EXON_ID,CHR_SEQ_NAME ORDER BY CHR_SEQ_NAME,EXON_ID ASC"
	return run_query(query)


# // $[API]
# // Title: Get all regions (UTR, CDS) of a transcript
# // Function: get_region_transcript
# // Description: Get all regions (UTR, CDS) of a transcript by using its name
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 | required
# // Return: region minimum position, maximum position
# // Ecosystem: Genomics:transcript
# // Example: python3.12 biorels_api.py get_region_transcript -TRANSCRIPT_NAME NM_000546
# // $[/API]
def get_region_transcript(TRANSCRIPT_NAME):
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	
	query = f"""
		SELECT transcript_pos_type, MIN(SEQ_POS) as MIN_POS, MAX(SEQ_POS) as MAX_POS
		FROM transcript_pos tp, transcript_pos_type tpt, transcript t
		WHERE tp.transcript_id = t.transcript_id
		AND tp.seq_pos_type_id = tpt.transcript_pos_type_id
		AND transcript_name = '{TRANSCRIPT_NAME}'
	"""
	if pos != -1:
		query += f" AND transcript_version='{TRANSCRIPT_VERSION}'"
	query += " GROUP BY transcript_pos_type ORDER BY MIN_POS ASC"
	return run_query(query)





# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////// TRANSCRIPT - GENE & LOCUS ////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Get all transcripts associated to a chromosome or a specific region of it
# // Function: get_transcript_by_chromosome
# // Description: Search for a transcript by using its chromosome name and optionally a specific region of it
# // Parameter: CHR_NAME | Chromosome name (1,2,3, X, Y, MT) | string | 1 | required
# // Parameter: CHR_POS_START | Starting position in the chromosome | int | 10 | optional | Default: None
# // Parameter: CHR_POS_END | Ending position in the chromosome | int | 1000 | optional | Default: None
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
# // Return: Assembly name, assembly unit, chromosome sequence name, gene sequence name, gene sequence version, transcript name, transcript version, transcript ID
# // Ecosystem: Genomics:transcript|chromosome|taxon
# // Example: python3.12 biorels_api.py get_transcript_by_chromosome -CHR_NAME 1
# // Example: python3.12 biorels_api.py get_transcript_by_chromosome -CHR_NAME 1 -CHR_POS_START 1000 -CHR_POS_END 2000
# // $[/API]
def get_transcript_by_chromosome(CHR_NAME, CHR_POS_START=None, CHR_POS_END=None, TAX_ID='9606'):
	query = f"""
		SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
		FROM  TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE, CHR_SEQ CS, GENOME_ASSEMBLY G,TAXON TX
		WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
		AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
		AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID
		AND GS.GN_ENTRY_ID=GE.GN_ENTRY_ID
		AND TX.TAXON_ID = G.TAXON_ID
		AND CS.CHR_SEQ_NAME='{CHR_NAME}'
    """
	if CHR_POS_START != None:
		query += f" AND T.START_POS >= {CHR_POS_START}"
	if CHR_POS_END != None:
		query += f" AND T.END_POS <= {CHR_POS_END}"
	query += f" AND TAX_ID = '{TAX_ID}'"
	return run_query(query)



# // $[API]
# // Title: Get all transcripts associated to a specific locus
# // Function: get_transcript_by_locus
# // Description: Search for a transcript by using its locus
# // Parameter: CHR_MAP_LOCATION | Locus name | string | 1p36.33 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
# // Return: Assembly name, assembly unit, chromosome sequence name, gene sequence name, gene sequence version, transcript name, transcript version, transcript ID
# // Ecosystem: Genomics:transcript|locus|taxon
# // Example: python3.12 biorels_api.py get_transcript_by_locus -CHR_MAP_LOCATION 1p36.33
# // $[/API]
def get_transcript_by_locus(CHR_MAP_LOCATION, TAX_ID='9606'):
	query = f"""
		SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
		FROM  TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE, CHR_GN_MAP CGM, CHR_MAP CM, CHR_SEQ CS, CHROMOSOME C, GENOME_ASSEMBLY G,TAXON TX
		WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
		AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
		AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID
		AND GS.GN_ENTRY_ID=GE.GN_ENTRY_ID
		AND GE.GN_ENTRY_ID = CGM.GN_ENTRY_ID
		AND CGM.CHR_MAP_ID = CM.CHR_MAP_ID
		AND C.CHR_ID = CM.CHR_ID
		AND C.TAXON_ID = TX.TAXON_ID
		AND TX.TAXON_ID = G.TAXON_ID
		AND CM.MAP_LOCATION='{CHR_MAP_LOCATION}'
		AND TAX_ID = '{TAX_ID}'
	"""
	return run_query(query)


# // $[API]
# // Title: Get all transcripts associated to a NCBI Gene ID
# // Function: get_transcript_by_gene_id
# // Description: Search for a transcript by using its gene ID
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: transcript_id, transcript_name, transcript_version, t.start_pos,t.end_pos, seq_hash, gs.gene_seq_id, gs.chr_seq_id,support_level, partial_sequence, valid_alignment, gene_seq_name,gene_seq_version,strand, ge.gn_entry_id, f.seq_type as feature, b.seq_Type as biotype
# // Ecosystem: Genomics:transcript|gene
# // Example: python3.12 biorels_api.py get_transcript_by_gene_id -GENE_ID 1017
# // $[/API]
def get_transcript_by_gene_id(GENE_ID):
	query = f"""
		SELECT transcript_id, transcript_name, transcript_version, t.start_pos,t.end_pos, seq_hash, gs.gene_seq_id, gs.chr_seq_id,support_level, partial_sequence, valid_alignment, gene_seq_name,gene_seq_version,strand, ge.gn_entry_id, f.seq_type as feature, b.seq_Type as biotype
        FROM transcript t
        LEFT JOIN seq_btype f ON f.seq_btype_id = feature_id
        LEFT JOIN seq_btype b on b.seq_btype_id = biotype_id, gene_seq gs, gn_entry ge 
        WHERE t.gene_seq_id = gs.gene_seq_id
        AND gs.gn_entry_Id = ge.gn_entry_Id
        AND  gene_id = '{GENE_ID}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get all transcripts and their sequence associated to a NCBI Gene ID
# // Function: get_transcripts_sequence_by_gene
# // Description: Given a NCBI Gene ID, provides information about all the transcripts associated with the gene, including the sequence of the transcripts.
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Transcript ID, transcript name, transcript version, start position, end position, sequence hash, gene sequence ID, chromosome sequence ID, support level, partial sequence, valid alignment, gene sequence name, gene sequence version, strand, gene entry ID, feature, biotype
# // Ecosystem: Genomics:transcript|gene
# // Example: python3.12 biorels_api.py get_transcripts_sequence_by_gene -GENE_ID 1017
# // $[/API]
def get_transcripts_sequence_by_gene(GENE_ID):
	query = f"""
		SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
		FROM  TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE, CHR_SEQ CS, GENOME_ASSEMBLY G
		WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
		AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
		AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID
		AND GS.GN_ENTRY_ID=GE.GN_ENTRY_ID
		AND GENE_ID={GENE_ID}
	"""
	TMP = run_query(query)
	DATA = {}
	for l in TMP:
		DATA[l['transcript_id']] = {'INFO': l, 'SEQ': []}
	query = f"SELECT TRANSCRIPT_POS_ID,NUCL,SEQ_POS, TRANSCRIPT_ID FROM TRANSCRIPT_POS WHERE TRANSCRIPT_ID IN ({','.join(map(str,DATA.keys()))}) ORDER BY TRANSCRIPT_ID ASC,SEQ_POS ASC"
	TMP = run_query(query)
	for l in TMP:
		DATA[l['transcript_id']]['SEQ'].append(l)
	return DATA



# // $[API]
# // Title: Get all transcript sequences in Fasta associated to a NCBI Gene ID
# // Function: get_transcripts_sequence_in_fasta_by_gene
# // Description: Given a NCBI Gene ID, provides information about all the transcripts associated with the gene, including the sequence of the transcripts.
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Transcript sequences in fasta format
# // Ecosystem: Genomics:transcript|gene
# // Example: python3.12 biorels_api.py get_transcripts_sequence_in_fasta_by_gene -GENE_ID 1017
# // $[/API]
def get_transcripts_sequence_in_fasta_by_gene(GENE_ID):
	query = f"""
		SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
		FROM  TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE, CHR_SEQ CS, GENOME_ASSEMBLY G
		WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
		AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
		AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID
		AND GS.GN_ENTRY_ID=GE.GN_ENTRY_ID
		AND GENE_ID={GENE_ID}
            	"""
	TMP = run_query(query)
	DATA = {}
	for l in TMP:
		DATA[l['transcript_id']] = {'INFO': l, 'SEQ': '','FASTA':''}
	query = f"SELECT TRANSCRIPT_POS_ID,NUCL,SEQ_POS, TRANSCRIPT_ID FROM TRANSCRIPT_POS WHERE TRANSCRIPT_ID IN ({','.join(map(str,DATA.keys()))}) ORDER BY TRANSCRIPT_ID ASC,SEQ_POS ASC"
	TMP = run_query(query)
	for l in TMP:
		DATA[l['transcript_id']]['SEQ'] += l['nucl']
	for l in DATA:
		DATA[l['transcript_id']]['FASTA']=f">{DATA[l]['INFO']['transcript_name']}|{DATA[l]['INFO']['transcript_version']}|{DATA[l]['INFO']['assembly_name']}|{DATA[l]['INFO']['assembly_unit']}|{DATA[l]['INFO']['chr_seq_name']}|{DATA[l]['INFO']['gene_seq_name']}|{DATA[l]['INFO']['gene_seq_version']}"
		DATA[l['transcript_id']]+=('\n'.join([DATA[l]['SEQ'][i:i+100] for i in range(0, len(DATA[l]['SEQ']), 100)]))
	return DATA
      

# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////// TRANSCRIPT - SPECIES ////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all transcripts associated to a NCBI Tax id
# // Function: search_transcript_by_taxon
# // Description: Search for a transcript by using its taxonomic identifier
# // Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# // Return: transcript_id, transcript_name, transcript_version, t.start_pos,t.end_pos, seq_hash, gs.gene_seq_id, gs.chr_seq_id,support_level, partial_sequence, valid_alignment, gene_seq_name,gene_seq_version,strand, ge.gn_entry_id, f.seq_type as feature, b.seq_Type as biotype, gene_id, symbol, chr_name, chr_map
# // Ecosystem: Genomics:transcript|taxon
# // Example: python3.12 biorels_api.py search_transcript_by_taxon -TAX_ID 9606
# // Warning: Long query execution time
# // $[/API]
def search_transcript_by_taxon(TAX_ID):
	query = f"""
		SELECT transcript_id, transcript_name, transcript_version, t.start_pos,t.end_pos, seq_hash,
            gs.gene_seq_id, gs.chr_seq_id,support_level, partial_sequence, valid_alignment,
             gene_seq_name,gene_seq_version,strand,
                  ge.gn_entry_id, f.seq_type as feature, b.seq_Type as biotype,
                  gene_id, symbol, chr_num, map_location
          
        FROM transcript t
          LEFT JOIN seq_btype f ON f.seq_btype_id = feature_id
        LEFT JOIN seq_btype b on b.seq_btype_id = biotype_id, gene_seq gs, gn_entry ge, chr_gn_map cgm, chr_map cm, chromosome c, taxon tx 
		WHERE t.gene_seq_id = gs.gene_seq_id
        AND ge.gn_entry_id = cgm.gn_entry_id
        AND cgm.chr_map_id = cm.chr_map_id
        AND cm.chr_id = c.chr_id
        AND c.taxon_id = tx.taxon_id
		AND gs.gn_entry_Id = ge.gn_entry_Id
		AND  tax_id = '{TAX_ID}'
	"""
	return run_query(query)



# // $[API]
# // Title: Find transcript by chromosome position 
# // Function: get_transcript_from_chromosome_position
# // Description: First search for any gene sequence in which the provided chromosome position fall within its range. Then search for any transcript that is associated with the gene sequence.
# // Parameter: CHR_SEQ_ID | Chromosome sequence id | int | 1 | required
# // Parameter: START_POSITION | Starting Position in the chromosome | int | 1 | required
# // Parameter: END_POSITION | Ending position in the chromosome  | int | 1000 | required
# // Return: Transcript ID, gene sequence ID, gene sequence name, gene name, gene ID, transcript name, transcript version, strand, sequence position, exon ID, chromosome sequence position ID, transcript position type
# // Ecosystem: Genomics:chromosome position|transcript
# // Example: python3.12 biorels_api.py get_transcript_from_chromosome_position -CHR_SEQ_ID 1 -START_POSITION 1 -END_POSITION 1000
# // $[/API]
def get_transcript_from_chromosome_position(CHR_SEQ_ID, START_POSITION, END_POSITION):
	query = f"""
		SELECT DISTINCT * 
 		FROM transcript t, gene_seq gs
 		where gs.gene_seq_Id = t.gene_seq_id 
 		AND gs.chr_seq_id = {CHR_SEQ_ID} 
 		AND gs.start_pos >= {START_POSITION}
 		AND gs.end_pos <= {END_POSITION}
	"""
	res= run_query(query)
	
	for line in res:
		query2=f"""
			SELECT *
			FROM 
               transcript_pos tp LEFT JOIN chr_seq_pos csp ON csp.chr_seq_pos_id = tp.chr_seq_pos_id
 		WHERE  csp.chr_seq_id = {CHR_SEQ_ID}
 		AND tp.transcript_id = {line['transcript_id']}
        AND chr_pos >= {START_POSITION}
        AND chr_pos <= {END_POSITION}
		ORDER BY SEQ_POS asc
        """
		res2 = run_query(query2)
		line['TRANSCRIPT_POS'] = []
		for l in res2: 
			line['TRANSCRIPT_POS'].append(l)
	return res
    


# // $[API]
# // Title:  Search a chromosome position record by transcript position records
# // Function: get_chromosome_positions_info_by_transcript_pos_ids
# // Description: Given a list of transcript_pos_id, search for the corresponding chromosome position records.
# // Parameter: LIST | List of transcript_pos_id | array | 1,2,3,4,5 | required
# // Ecosystem: Genomics:chromosome position|transcript|transcript position
# // Example: python3.12 biorels_api.py get_chromosome_positions_info_by_transcript_pos_ids -LIST 1,2,3,4,5
# // $[/API]
def get_chromosome_positions_info_by_transcript_pos_ids(LIST):
	query = f"""
		SELECT TP.TRANSCRIPT_POS_ID, CSP.CHR_SEQ_POS_ID, C.CHR_ID, CHR_NUM, CS.CHR_SEQ_ID, REFSEQ_NAME, REFSEQ_VERSION, GENBANK_NAME, GENBANK_VERSION, SEQ_ROLE, CHR_SEQ_NAME, ASSEMBLY_UNIT, CSP.NUCL, CHR_POS AS POSITION, SCIENTIFIC_NAME, TAX_ID
		FROM CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP, TAXON T, TRANSCRIPT_POS TP
		WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
		AND T.TAXON_ID = C.TAXON_ID
		AND TP.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID
		AND TRANSCRIPT_POS_ID IN ({','.join(LIST)})
	"""
	res= run_query(query)
	data = {}
	
	for line in res:
		data[line['transcript_pos_id']] = line
	return data



# // $[API]
# // Title: Search a chromosome position record by a list of transcript position 
# // Function: get_chromosome_seq_info_from_transcript_position
# // Description: Given a list of transcript position and a transcript name, search for the corresponding chromosome position records.
# // Parameter: TRANSCRIPT_POSITION | List of transcript position ID | array | 1,2,3,4,5 | required
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | NM_001798 | required
# // Parameter: TRANSCRIPT_VERSION | Transcript version | string | | optional | Default: None
# // Ecosystem: Genomics:chromosome position|transcript
# // Example: python3.12 biorels_api.py get_chromosome_seq_info_from_transcript_position -TRANSCRIPT_POSITION 1,2,3,4 -TRANSCRIPT_NAME NM_001798
# // $[/API]

def get_chromosome_seq_info_from_transcript_position(TRANSCRIPT_POSITION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION=None):
	query=f"""
     SELECT CSP.CHR_SEQ_POS_ID, C.CHR_ID,CHR_NUM, CS.CHR_SEQ_ID,
    REFSEQ_NAME,REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION, SEQ_ROLE,CHR_SEQ_NAME,
     ASSEMBLY_UNIT,CSP.NUCL,CHR_POS As POSITION,SCIENTIFIC_NAME,TAX_ID
    FROM   CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP,TAXON T,TRANSCRIPT TT,TRANSCRIPT_POS TP
    WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
    AND T.TAXON_ID = C.TAXON_ID
    AND TT.transcript_id = TP.transcript_id
    AND TT.TRANSCRIPT_NAME='{TRANSCRIPT_NAME}'
    {"AND TT.TRANSCRIPT_VERSION='{TRANSCRIPT_VERSION}'" if TRANSCRIPT_VERSION != None else ''}
    AND TP.SEQ_POS IN ({','.join(TRANSCRIPT_POSITION)})
    AND TP.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID;
    """
    
	return run_query(query)




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////// GENOMIC ECOSYSTEM - VARIANT  ////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Get variant information by its dbSNP identifier rsid
# // Function: search_variant_by_rsid
# // Description: Search for a variant by using its dbSNP identifier rsid
# // Parameter: RSID | dbSNP identifier | string | rs12345 | required
# // Parameter: WITH_ALLELES | Include variant alleles | boolean | true | optional 
# // Parameter: WITH_FREQUENCY | Include variant alleles frequency | true | boolean | optional 
# // Parameter: WITH_GENE | Include gene information | boolean | true | optional 
# // Return: Variant entry record. VARIANT-> Variant entry record, ALLELES-> Variant alleles, FREQUENCY-> Variant alleles frequency, GENE-> Gene information
# // Ecosystem: Genomics:variant
# // Example: python3.12 biorels_api.py search_variant_by_rsid -RSID rs12345
# // Example: python3.12 biorels_api.py search_variant_by_rsid -RSID rs12345 -WITH_ALLELES True -WITH_FREQUENCY True -WITH_GENE True
# // $[/API]
def search_variant_by_rsid(RSID, WITH_ALLELES = True, WITH_FREQUENCY = True,WITH_GENE=True):
	VAL = RSID
	if (isinstance(RSID, str)):
		if (RSID[0:2] == 'rs'):
			VAL = RSID[2:]
		if not VAL.isnumeric():
			return []
	query = f"""
		SELECT * FROM VARIANT_ENTRY WHERE RSID = '{VAL}'
	"""
	data = {}
	data['VARIANT'] = run_query(query)
	if WITH_ALLELES:
		data['ALLELES'] = get_variant_alleles_by_rsid(VAL)
	if WITH_FREQUENCY:
		data['FREQUENCY'] = get_alleles_frequency_by_rsid(VAL)
	if WITH_GENE:
		data['GENE'] = find_gene_from_variant(VAL)
	return data


# // $[API]
# // Title: Get variant information by its dbSNP identifier rsid
# // Function: get_variant_alleles_by_rsid
# // Description: List all variant alleles for a variant by using its dbSNP identifier rsid
# // Parameter: RSID | dbSNP identifier | string | rs12345 | required
# // Return: Variant alleles
# // Ecosystem: Genomics:variant
# // Example: python3.12 biorels_api.py get_variant_alleles_by_rsid -RSID rs12345
# // $[/API]
def get_variant_alleles_by_rsid(RSID):
	VAL = RSID
	if (isinstance(RSID, str)):
		if (RSID[0:2] == 'rs'):
			VAL = RSID[2:]
		if not VAL.isnumeric():
			return []
	query = f"""
		SELECT TAX_ID, SCIENTIFIC_NAME, CHR_SEQ_NAME, CHR_POS, RSID, VA_R.VARIANT_SEQ AS REF_ALL, VA.VARIANT_SEQ AS ALT_ALL, VARIANT_NAME, SO_NAME AS SNP_TYPE
		FROM VARIANT_ENTRY VE, VARIANT_POSITION VP, VARIANT_CHANGE VC, VARIANT_ALLELE VA_R, VARIANT_ALLELE VA, VARIANT_TYPE VT
		LEFT JOIN SO_ENTRY SO ON SO.SO_ENTRY_ID = VT.SO_ENTRY_ID,
		CHR_SEQ_POS CSP, CHR_SEQ CS, GENOME_ASSEMBLY GA, TAXON T
		WHERE VE.VARIANT_ENTRY_ID = VP.VARIANT_ENTRY_ID
		AND VP.VARIANT_POSITION_ID = VC.VARIANT_POSITION_ID
		AND VT.VARIANT_TYPE_ID = VC.VARIANT_TYPE_ID
		AND va.variant_allele_id = alt_all
    AND va_r.variant_allele_id = ref_all
	AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
	AND csp.chr_seq_id = cs.chr_seq_id
	AND cs.genome_assembly_id = ga.genome_assembly_id
	AND ga.taxon_id = t.taxon_id
	AND rsid = '{VAL}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get variant alleles frequency by its dbSNP identifier rsid
# // Function: get_alleles_frequency_by_rsid
# // Description: List all variant alleles frequency for a variant by using its dbSNP identifier rsid
# // Parameter: RSID | dbSNP identifier | string | rs12345 | required
# // Return: Variant alleles frequency
# // Ecosystem: Genomics:variant
# // Example: python3.12 biorels_api.py get_alleles_frequency_by_rsid -RSID rs12345
# // $[/API]
def get_alleles_frequency_by_rsid(RSID):
	VAL = RSID
	if (isinstance(RSID, str)):
		if (RSID[0:2] == 'rs'):
			VAL = RSID[2:]
		if not VAL.isnumeric():
			return []
	query = f"""
		SELECT RSID, VA_R.VARIANT_SEQ AS REF_ALL, VA.VARIANT_SEQ AS ALT_ALL, VARIANT_NAME, SO_NAME AS SNP_TYPE, REF_COUNT, ALT_COUNT AS TOT_COUNT, SHORT_NAME, DESCRIPTION, VARIANT_FREQ_STUDY_NAME
		FROM VARIANT_ENTRY VE, VARIANT_POSITION VP, VARIANT_CHANGE VC, VARIANT_ALLELE VA_R, VARIANT_ALLELE VA, VARIANT_TYPE VT
		LEFT JOIN SO_ENTRY SO ON SO.SO_ENTRY_ID = VT.SO_ENTRY_ID,
		VARIANT_Frequency vf, variant_freq_study vfs
	where ve.variant_entry_id = vp.variant_entry_id
    ANd vp.variant_position_id = vc.variant_position_id 
    AND  vt.variant_Type_Id = vc.variant_type_id
    AND va.variant_allele_id = alt_all
    AND va_r.variant_allele_id = ref_all
	AND vc.variant_change_id = vf.variant_change_id
	AND vf.variant_freq_study_id = vfs.variant_freq_study_id
	AND rsid = '{VAL}'
	"""
	return run_query(query)




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////// VARIANT - GENOMIC SEQUENCE //////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Variant search in a chromosome range
# // Function: getVariantsFromChromosomeRange
# // Description: Given a chromsome, a position and a range around that position, search for any variants that fall within that range.
# // Parameter: CHR_SEQ_NAME | Chromosome sequence name | string | 22 | required
# // Parameter: START_POS | Start position | int | 25459492 | required
# // Parameter: END_POS | End position | int | 25459492 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
# // Parameter: WITH_ALLELES | Include variant alleles | boolean | true | optional | Default: true
# // Parameter: WITH_FREQUENCY | Include variant alleles frequency | boolean | true | optional | Default: true
# // Parameter: WITH_GENE | Include gene information | boolean | true | optional | Default: true
# // Return: First level array with chromosome position as key and second level array with rsid as key and variant information as value
# // Ecosystem: Genomics:genomic sequence|variant|gene
# // Example: python3.12 biorels_api.py getVariantsFromChromosomeRange -CHR_SEQ_NAME 22 -START_POS 25459492 -END_POS 25459492
# // $[/API]
def getVariantsFromChromosomeRange(CHR_SEQ_NAME, START_POS, END_POS, TAX_ID='9606', WITH_ALLELES=True, WITH_FREQUENCY=True, WITH_GENE=True):
	query = f"""
		SELECT DISTINCT rsid, chr_pos
		FROM variant_entry ve, variant_position vp, chr_seq_pos csp, chr_seq cs, genome_assembly ga, taxon t
		WHERE ve.variant_entry_id = vp.variant_entry_id
		AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
		AND csp.chr_seq_id = cs.chr_seq_id
		AND cs.genome_assembly_id = ga.genome_assembly_id
		AND ga.taxon_id = t.taxon_id
		AND (chr_seq_name='{CHR_SEQ_NAME}' OR refseq_name='{CHR_SEQ_NAME}' OR genbank_name='{CHR_SEQ_NAME}')
		AND chr_pos >= {START_POS}
		AND chr_pos <= {END_POS}
		AND tax_id = '{TAX_ID}'
	"""
	list = run_query(query)
	
	data = {}
	for e in list:
		if (e['chr_pos'] not in data):
			data[e['chr_pos']] = {}
		data[e['chr_pos']][e['rsid']] = search_variant_by_rsid(e['rsid'], WITH_ALLELES, WITH_FREQUENCY, WITH_GENE)
	return data






# /////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////// VARIANT - Gene & Locus //////////////////////////////////////
# /////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Find all genes by variant
# // Function: find_gene_from_variant
# // Description: Search for all genes that are located on a variant by using its dbSNP identifier rsid
# // Parameter: RSID | dbSNP identifier | string | rs12345 | required
# // Return: Gene symbol, gene full name, gene ID, gene entry ID, scientific name, taxonomic identifier, rsid
# // Ecosystem: Genomics:variant|gene
# // Example: python3.12 biorels_api.py find_gene_from_variant -RSID rs12345
# // $[/API]
def find_gene_from_variant(RSID):
	VAL = RSID
	if (isinstance(RSID, str)):
		if (RSID[0:2] == 'rs'):
			VAL = RSID[2:]
		if not VAL.isnumeric():
			return []
	query = f"""
		SELECT DISTINCT SYMBOL, FULL_NAME, GENE_ID, sp.GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID,rsid
	FROM mv_gene_sp sp, gene_seq gs, chr_seq_pos csp, variant_position vp, variant_entry ve
	WHERE gs.chr_seq_id =csp.chr_seq_id AND csp.chr_pos >= gs.start_pos 
	AND csp.chr_pos <= gs.end_pos 
	AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
	AND vp.variant_entry_id = ve.variant_entry_id
	AND sp.gn_entry_id = gs.gn_entry_id
	AND rsid = '{VAL}'
	"""
	return run_query(query)




# // $[API]
# // Title: Find all variants by gene
# // Function: find_variant_from_gene
# // Description: Search for all variants that are associated with a gene by using its NCBI Gene ID
# // Description: This will search for all the DNA location of the given gene and then search for all the variants that fall within that range.
# // Description: Depending on the parameters, it will return the variant entry record, variant alleles, variant alleles frequency and gene information.
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Parameter: WITH_ALLELES | Include variant alleles | boolean | false | optional 
# // Parameter: WITH_FREQUENCY | Include variant alleles frequency | boolean | false | optional
# // Parameter: WITH_GENE | Include gene information | boolean | false | optional 
# // Return: Variant entry record. VARIANT-> Variant entry record, ALLELES-> Variant alleles, FREQUENCY-> Variant alleles frequency, GENE-> Gene information
# // Ecosystem: Genomics:variant|gene
# // Example: python3.12 biorels_api.py find_variant_from_gene -GENE_ID 1017
# // Example: python3.12 biorels_api.py find_variant_from_gene -GENE_ID 1017 -WITH_ALLELES True -WITH_FREQUENCY True -WITH_GENE True
# // $[/API]
def find_variant_from_gene(GENE_ID, WITH_ALLELES=False, WITH_FREQUENCY=False, WITH_GENE=False):
	res = run_query(f"""
		SELECT Start_pos,end_pos,chr_seq_id 
	FROM gn_entry sp, gene_seq gs
	WHERE sp.gn_entry_id = gs.gn_entry_id 
	AND gene_id = {GENE_ID}
	""")
	RANGES = {}
	for e in res:
		if e['chr_seq_id'] not in RANGES:
			RANGES[e['chr_seq_id']] = {'MIN': 10000000000, 'MAX': 0}
		RANGES[e['chr_seq_id']]['MIN'] = min(RANGES[e['chr_seq_id']]['MIN'], e['start_pos'])
		RANGES[e['chr_seq_id']]['MAX'] = max(RANGES[e['chr_seq_id']]['MAX'], e['start_pos'])
		RANGES[e['chr_seq_id']]['MIN'] = min(RANGES[e['chr_seq_id']]['MIN'], e['end_pos'])
		RANGES[e['chr_seq_id']]['MAX'] = max(RANGES[e['chr_seq_id']]['MAX'], e['end_pos'])
	LIST = {}
	for CHR_SEQ_ID, RANGE in RANGES.items():
		for I in range(RANGE['MIN'], RANGE['MAX'], 10000):
			MAX = min(I + 10000, RANGE['MAX'])
			res = run_query(f"""
				SELECT DISTINCT rsid 
				FROM variant_entry ve, variant_position vp, chr_seq_pos csp
				WHERE ve.variant_entry_id = vp.variant_entry_id
				AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
				AND csp.chr_seq_id = {CHR_SEQ_ID}
				AND csp.chr_pos >= {I}
				AND csp.chr_pos <= {MAX}
			""")
			for line in res:
				if line['rsid'] not in LIST:
					LIST[line['rsid']] = {}
	if not WITH_ALLELES and not WITH_FREQUENCY and not WITH_GENE:
		return list(LIST.keys())
	data = []
	for rsid in LIST:
		data.append(search_variant_by_rsid(rsid, WITH_ALLELES, WITH_FREQUENCY, WITH_GENE))
	return data


# // $[API]
# // Title: List all variant types
# // Function: list_variant_type
# // Description: List all variant types
# // Return: Variant types
# // Parameter: Dummy | Dummy parameter | string | optional | Default: None
# // Ecosystem: Genomics:variant
# // Example: python3.12 biorels_api.py list_variant_type
# // $[/API]
def list_variant_type(Dummy=None):
	return run_query("SELECT DISTINCT variant_name FROM variant_type")




# // $[API]
# // Title: Find all variants by gene
# // Function: find_variant_type_from_gene
# // Description: Search for all variants that are associated with a gene by using its NCBI Gene ID and a specific variant type
# // Description: This will search for all the DNA location of the given gene and then search for all the variants that fall within that range.
# // Description: Depending on the parameters, it will return the variant entry record, variant alleles, variant alleles frequency and gene information.
# // Description: For the list of different variant types, call list_variant_type
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Parameter: SNP_TYPE | Variant type. Call list_variant_type to see the options | string | del | required
# // Parameter: WITH_ALLELES | Include variant alleles | boolean | false | optional
# // Parameter: WITH_FREQUENCY | Include variant alleles frequency | boolean | false | optional
# // Parameter: WITH_GENE | Include gene information | boolean | false | optional
# // Return: Variant entry record. VARIANT-> Variant entry record, ALLELES-> Variant alleles, FREQUENCY-> Variant alleles frequency, GENE-> Gene information
# // Ecosystem: Genomics:variant|gene
# // Example: python3.12 biorels_api.py find_variant_type_from_gene -GENE_ID 1017 -SNP_TYPE 'del'
# // Example: python3.12 biorels_api.py find_variant_type_from_gene -GENE_ID 1017 -SNP_TYPE 'del' -WITH_ALLELES True -WITH_FREQUENCY True -WITH_GENE True
# // $[/API]
def find_variant_type_from_gene(GENE_ID, SNP_TYPE, WITH_ALLELES=False, WITH_FREQUENCY=False, WITH_GENE=False):
	res = run_query(f"""
		SELECT Start_pos,end_pos,chr_seq_id 
	FROM gn_entry sp, gene_seq gs
	WHERE sp.gn_entry_id = gs.gn_entry_id 
	AND gene_id = {GENE_ID}
	""")
	RANGES = {}
	for e in res:
		if e['chr_seq_id'] not in RANGES:
			RANGES[e['chr_seq_id']] = {'MIN': 10000000000, 'MAX': 0}
		RANGES[e['chr_seq_id']]['MIN'] = min(RANGES[e['chr_seq_id']]['MIN'], e['start_pos'])
		RANGES[e['chr_seq_id']]['MAX'] = max(RANGES[e['chr_seq_id']]['MAX'], e['start_pos'])
		RANGES[e['chr_seq_id']]['MIN'] = min(RANGES[e['chr_seq_id']]['MIN'], e['end_pos'])
		RANGES[e['chr_seq_id']]['MAX'] = max(RANGES[e['chr_seq_id']]['MAX'], e['end_pos'])
	LIST = {}
	for CHR_SEQ_ID, RANGE in RANGES.items():
		for I in range(RANGE['MIN'], RANGE['MAX'], 100):
			MAX = min(I + 100, RANGE['MAX'])
			res = run_query(f"""
				SELECT DISTINCT rsid 
				FROM variant_entry ve, variant_position vp, variant_change vc, variant_Type vt, chr_seq_pos csp
				WHERE ve.variant_entry_id = vp.variant_entry_id
				AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
				AND  vt.variant_Type_Id = vc.variant_type_id
				AND vc.variant_position_id = vp.variant_position_id
				AND variant_name='{SNP_TYPE}'
				AND csp.chr_seq_id = {CHR_SEQ_ID}
				AND csp.chr_pos >= {I}
				AND csp.chr_pos <= {MAX}
			""")
			for line in res:
				if line['rsid'] not in LIST:
					LIST[line['rsid']] = {}
	if not WITH_ALLELES and not WITH_FREQUENCY and not WITH_GENE:
		return list(LIST.keys())
	data = []
	for rsid in LIST:
		data.append(search_variant_by_rsid(rsid, WITH_ALLELES, WITH_FREQUENCY, WITH_GENE))
	return data


# /////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////// VARIANT - transcript //////////////////////////////////////
# /////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all variants associated to a specific transcript
# // Function: get_variant_for_transcript
# // Description: Search for all variants that are associated with a transcript by using its transcript name
# // Description: Can also search for variants that fall within a specific range of the transcript
# // Description: The list of allowed values for the TRANSCRIPT_VARIANT_IMPACT parameter are: 
# // Description: coding_sequence_variant, intron_variant, upstream_transcript_variant, 5_prime_UTR_variant, 
# // Description: 3_prime_UTR_variant, downstream_transcript_variant, splice_donor_variant, terminator_codon_variant
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | NM_000546.5 | required
# // Parameter: START_POS | Start position | int | 1 | optional | Default: None
# // Parameter: END_POS | End position | int | 10 | optional | Default: None
# // Parameter: TRANSCRIPT_VARIANT_IMPACT | Transcript variant impact | string | coding_sequence_variant |  optional | Default: None
# // Return: Variant entry record
# // Ecosystem: Genomics:variant|transcript
# // Example: python3.12 biorels_api.py get_variant_for_transcript -TRANSCRIPT_NAME 'NM_000546.5'
# // $[/API]
def get_variant_for_transcript(TRANSCRIPT_NAME, START_POS=None, END_POS=None, TRANSCRIPT_VARIANT_IMPACT=None):
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
	query = f"""
		SELECT transcript_id 
	FROM transcript 
	WHERE transcript_name = '{TRANSCRIPT_NAME}'
	"""
	res = run_query(query)
	LIST_TR = []
	for line in res:
		LIST_TR.append(line['transcript_id'])
	if len(LIST_TR) == 0:
		return []
	LIST_INFO = {'ALL': {}, 'POS_TYPE': {}, 'SO': {}, 'VARIANT': {}}
	if TRANSCRIPT_VARIANT_IMPACT != None:
		LIST_ALLOWED = [
			"coding_sequence_variant",
			"intron_variant",
			"upstream_transcript_variant",
			"5_prime_UTR_variant",
			"3_prime_UTR_variant",
			"downstream_transcript_variant",
			"splice_donor_variant",
			"terminator_codon_variant",
			"genic_downstream_transcript_variant",
			"genic_upstream_transcript_variant"
		]
		if TRANSCRIPT_VARIANT_IMPACT not in LIST_ALLOWED:
			print("Here is the list of allowed values for the TRANSCRIPT_VARIANT_IMPACT parameter:")
			print(LIST_ALLOWED)
			exit()
	FILTER = -1
	res = run_query("SELECT * FROM SO_ENTRY")
	for line in res:
		LIST_INFO['SO'][line['so_entry_id']] = line['so_name']
		if line['so_name'] == TRANSCRIPT_VARIANT_IMPACT:
			FILTER = line['so_entry_id']
	query = f"""
		SELECT rsid, ref_all, alt_all, variant_Type_id, so_entry_id, tr_ref_all, tr_alt_all, nucl, seq_pos,seq_pos_type_id, exon_id, transcript_name, transcript_version
	FROM variant_entry ve, variant_position vp, variant_change vc, variant_transcript_map vtm
	LEFT JOIN transcript_pos tp ON tp.transcript_pos_id = vtm.transcript_pos_id, transcript t
	WHERE ve.variant_entry_id = vp.variant_entry_id
	AND vp.variant_position_id = vc.variant_position_id
	AND vc.variant_change_id = vtm.variant_change_id
	AND vtm.transcript_id = t.transcript_id
	AND t.transcript_id IN ({','.join(map(str, LIST_TR))})
	"""
	if FILTER != -1:
		query += f" AND vtm.so_entry_id = {FILTER}"
	if START_POS != None or END_POS != None:
		query += ' AND ( vtm.transcript_pos_id IS NULL OR ('
		if START_POS != None and END_POS != None:
			query += f' seq_pos >= {START_POS} AND seq_pos <= {END_POS}'
		elif START_POS != None:
			query += f' seq_pos >= {START_POS}'
		elif END_POS != None:
			query += f' seq_pos <= {END_POS}'
		query += '))'
	data = run_query(query)
	for line in data:
		if line['ref_all'] != '':
			LIST_INFO['ALL'][line['ref_all']] = ''
		if line['alt_all'] != '':
			LIST_INFO['ALL'][line['alt_all']] = ''
		if line['tr_ref_all'] != '':
			LIST_INFO['ALL'][line['tr_ref_all']] = ''
		if line['tr_alt_all'] != '':
			LIST_INFO['ALL'][line['tr_alt_all']] = ''
		if line['seq_pos_type_id'] != '':
			LIST_INFO['POS_TYPE'][line['seq_pos_type_id']] = ''
		line['transcript_variant_change'] = LIST_INFO['SO'][line['so_entry_id']]
		if line['variant_type_id'] != '':
			LIST_INFO['VARIANT'][line['variant_type_id']] = ''
	if len(LIST_INFO['ALL']) != 0:
		res = run_query(f"SELECT * FROM variant_allele WHERE variant_allele_id IN ({','.join(str(x) for x in LIST_INFO['ALL'].keys())})")
		for line in res:
			LIST_INFO['ALL'][line['variant_allele_id']] = line['variant_seq']
	if len(LIST_INFO['POS_TYPE']) != 0:
		res = run_query(f"SELECT * FROM transcript_pos_type WHERE transcript_pos_type_id IN ({','.join(str(x) for x in LIST_INFO['POS_TYPE'].keys())})")
		for line in res:
			LIST_INFO['POS_TYPE'][line['transcript_pos_type']] = line['transcript_pos_type']	
	if len(LIST_INFO['VARIANT']) != 0:
		res = run_query(f"SELECT * FROM variant_Type WHERE variant_type_id IN ({','.join(str(x) for x in LIST_INFO['VARIANT'].keys())})")
		for line in res:
			LIST_INFO['VARIANT'][line['variant_type_id']] = line['variant_name']
	for line in data:
		line['ref_all'] = LIST_INFO['ALL'][line['ref_all']]
		line['alt_all'] = LIST_INFO['ALL'][line['alt_all']]
		line['tr_ref_all'] = LIST_INFO['ALL'][line['tr_ref_all']]
		line['tr_alt_all'] = LIST_INFO['ALL'][line['tr_alt_all']]
		line['seq_pos_type_id'] = LIST_INFO['POS_TYPE'][line['seq_pos_type_id']]
		line['variant_type_id'] = LIST_INFO['VARIANT'][line['variant_type_id']]
	return data

# /////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////// VARIANT - protein //////////////////////////////////////
# /////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all variants associated to a specific protein sequence
# // Function: get_variant_for_protein
# // Description: Search for all variants that are associated with a protein by using its protein isoform id
# // Description: Can also search for variants that fall within a specific range of the protein
# // Description: The list of allowed values for the PROTEIN_VARIANT_IMPACT parameter are:
# // Description: stop_lost, missense_variant, stop_gained, synonymous_variant, inframe_indel, inframe_deletion
# // Parameter: PROTEIN_ISO_ID | Protein name | string | P04066-1 | required
# // Parameter: START_POS | Start position | int | optional | 1 | Default: None
# // Parameter: END_POS | End position | int | optional | 10 | Default: None
# // Parameter: PROTEIN_VARIANT_IMPACT | Protein variant impact | string | missense_variant | optional | Default: None
# // Return: Variant entry record
# // Ecosystem: Genomics:variant|transcript;Proteomic:protein
# // Example: python3.12 biorels_api.py get_variant_for_protein -PROTEIN_ISO_ID 'P04066-1' -START_POS 1
# // $[/API]
def get_variant_for_protein(PROTEIN_ISO_ID, START_POS=None, END_POS=None, PROTEIN_VARIANT_IMPACT=None):
	query = f"""
		SELECT prot_seq_id 
	FROM prot_seq 
	WHERE iso_id = '{PROTEIN_ISO_ID}'
	"""
	res = run_query(query)
	LIST_TR = []
	for line in res:
		LIST_TR.append(line['prot_seq_id'])
	if len(LIST_TR) == 0:
		return []
	LIST_INFO = {'ALL': {}, 'SO': {}, 'VARIANT': {}, 'PR_ALL': {}}
	if PROTEIN_VARIANT_IMPACT != None:
		LIST_ALLOWED = [
			"stop_lost",
			"missense_variant",
			"stop_gained",
			"synonymous_variant",
			"inframe_indel",
			"inframe_deletion"
		]
		if PROTEIN_VARIANT_IMPACT not in LIST_ALLOWED:
			print("Here is the list of allowed values for the PROTEIN_VARIANT_IMPACT parameter:")
			print(LIST_ALLOWED)
			exit()
	FILTER = -1
	res = run_query("SELECT * FROM SO_ENTRY")
	for line in res:
		LIST_INFO['SO'][line['so_entry_id']] = line['so_name']
		if line['so_name'] == PROTEIN_VARIANT_IMPACT:
			FILTER = line['so_entry_id']
	query = f"""
		SELECT rsid, ref_all, alt_all, variant_Type_id, vtm.so_entry_id as tr_impact, tr_ref_all, tr_alt_all, vpm.so_entry_id as pr_impact, prot_ref_all, prot_alt_all, position, letter, iso_id, iso_name
	FROM variant_entry ve, variant_position vp, variant_change vc, variant_transcript_map vtm, variant_protein_map vpm
	LEFT JOIN prot_seq_pos tp ON tp.prot_Seq_pos_id = vpm.prot_seq_pos_Id, prot_seq ps
	WHERE ve.variant_entry_id = vp.variant_entry_id
	AND vp.variant_position_id = vc.variant_position_id
	AND vc.variant_change_id = vtm.variant_change_id
	AND vtm.variant_transcript_id = vpm.variant_transcript_id 
	AND vpm.prot_seq_id = ps.prot_seq_id
	AND ps.prot_seq_id IN ({','.join(map(str, LIST_TR))})
	"""
	if FILTER != -1:
		query += f" AND vtm.so_entry_id = {FILTER}"
	if START_POS != None or END_POS != None:
		query += ' AND ( vpm.prot_seq_id IS NULL OR ('
		if START_POS != None and END_POS != None:
			query += f' position >= {START_POS} AND position <= {END_POS}'
		elif START_POS != None:
			query += f' position >= {START_POS}'
		elif END_POS != None:
			query += f' position <= {END_POS}'
		query += '))'
	data = run_query(query)
	for line in data:
		if line['ref_all'] != '':
			LIST_INFO['ALL'][line['ref_all']] = ''
		if line['alt_all'] != '':
			LIST_INFO['ALL'][line['alt_all']] = ''
		if line['tr_ref_all'] != '':
			LIST_INFO['ALL'][line['tr_ref_all']] = ''
		if line['tr_alt_all'] != '':
			LIST_INFO['ALL'][line['tr_alt_all']] = ''
		if line['prot_ref_all'] != '':
			LIST_INFO['PR_ALL'][line['prot_ref_all']] = ''
		if line['prot_alt_all'] != '':
			LIST_INFO['PR_ALL'][line['prot_alt_all']] = ''
		if line['tr_impact'] != '':
			line['tr_impact'] = LIST_INFO['SO'][line['tr_impact']] = ''
		if line['pr_impact'] != '':
			line['pr_impact'] = LIST_INFO['SO'][line['pr_impact']]= ''
		if line['variant_type_id'] != '':
			LIST_INFO['VARIANT'][line['variant_type_id']] = ''
	if len(LIST_INFO['ALL']) != 0:
		res = run_query(f"SELECT * FROM variant_allele WHERE variant_allele_id IN ({','.join(str(x) for x in LIST_INFO['ALL'].keys())})")
		for line in res:
			LIST_INFO['ALL'][line['variant_allele_id']] = line['variant_seq']
	if len(LIST_INFO['PR_ALL']) != 0:
		res = run_query(f"SELECT * FROM biorels.variant_prot_allele WHERE variant_prot_allele_id  IN ({','.join(str(x) for x in LIST_INFO['PR_ALL'].keys())})")
		for line in res:
			LIST_INFO['PR_ALL'][line['variant_prot_allele_id']] = line['variant_prot_seq']
	if len(LIST_INFO['VARIANT']) != 0:
		res = run_query(f"SELECT * FROM variant_Type WHERE variant_type_id IN ({','.join(str(x) for x in LIST_INFO['VARIANT'].keys())})")
		for line in res:
			LIST_INFO['VARIANT'][line['variant_type_id']] = line['variant_name']
	for line in data:
		line['ref_all'] = LIST_INFO['ALL'][line['ref_all']]
		line['alt_all'] = LIST_INFO['ALL'][line['alt_all']]
		line['tr_ref_all'] = LIST_INFO['ALL'][line['tr_ref_all']]
		line['tr_alt_all'] = LIST_INFO['ALL'][line['tr_alt_all']]
		line['prot_ref_all'] = LIST_INFO['PR_ALL'][line['prot_ref_all']]
		line['prot_alt_all'] = LIST_INFO['PR_ALL'][line['prot_alt_all']]
		line['tr_impact'] = LIST_INFO['SO'][line['tr_impact']]
		line['pr_impact'] = LIST_INFO['SO'][line['pr_impact']]
		line['variant_type_id'] = LIST_INFO['VARIANT'][line['variant_type_id']]
	return data


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////////// GLOBAL ///////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Search source by name
# // Function: source_search
# // Description: Search for a source by using its name. First by exact match, then by partial match. Case insensitive.
# // Parameter: source_name | Source name | string | UniProt | required
# // Ecosystem: Global
# // Example: python3.12 biorels_api.py source_search -source_name 'NCBI'
# // Example: python3.12 biorels_api.py source_search -source_name 'unip'
# // $[/API]
def source_search(source_name):
    query = f"SELECT * FROM source where LOWER(source_name) ='{source_name.lower()}'"
    res = run_query(query)
    if len(res) != 0:
        return res
    query = f"SELECT * FROM source where LOWER(source_name) LIKE '%{source_name.lower()}%'"
    res = run_query(query)
    return res







# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////////// PROTEOMIC ///////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////////// PROTEIN  //////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get protein information by protein identifier
# // Function: search_protein_by_identifier
# // Description: Search for a protein by using its identifier
# // Parameter: IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein record
# // Ecosystem: Proteomic:protein
# // Example: python3.12 biorels_api.py search_protein_by_identifier -IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
def search_protein_by_identifier(IDENTIFIER):
	query=f"""
		SELECT * FROM prot_entry WHERE PROT_IDENTIFIER='{IDENTIFIER}'
	"""
	return run_query(query)


# // $[API]
# // Title: Get protein information by protein accession
# // Function: search_protein_by_accession
# // Description: Search for a protein by using its UniProt accession
# // Description: By default, only search protein records that have the accession as primary.
# // Parameter: ACCESSION | Protein accession | string | P24941 | required
# // Parameter: IS_PRIMARY | Is primary accession | boolean | true | optional  | Default: true
# // Return: Protein record
# // Ecosystem: Proteomic:protein
# // Example: python3.12 biorels_api.py search_protein_by_accession -ACCESSION 'P24941'
# // Example: python3.12 biorels_api.py search_protein_by_accession -ACCESSION 'P24941' -IS_PRIMARY false
# // $[/API]
def search_protein_by_accession(ACCESSION, IS_PRIMARY=True):
	query=f"""
		SELECT * FROM prot_entry PE, prot_ac A 
		WHERE pe.prot_entry_Id = a.prot_entry_id 
		AND is_primary = '{'T' if IS_PRIMARY else 'F'}'
		AND ac='{ACCESSION}'
	"""
	return run_query(query)



# // $[API]
# // Title: List all protein accessions for a protein
# // Function: get_protein_accession
# // Description: List all UniProt protein accessions for a protein
# // Parameter: IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein accession, is primary
# // Ecosystem: Proteomic:protein
# // Example: python3.12 biorels_api.py get_protein_accession -IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
def get_protein_accession(IDENTIFIER):
	query=f"""
		SELECT AC,is_primary FROM prot_entry PE, prot_ac A 
		WHERE pe.prot_entry_Id = a.prot_entry_id 
		AND prot_identifier='{IDENTIFIER}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get protein names by protein identifier
# // Function: get_protein_names
# // Description: Get all protein names for a protein by using its identifier
# // Parameter: IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein name, protein name type
# // Ecosystem: Proteomic:protein
# // Example: python3.12 biorels_api.py get_protein_names -IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
def get_protein_names(IDENTIFIER):
	query=f"""
		SELECT A.*, pnm.* FROM prot_entry PE, prot_name A , prot_name_map pnm
		WHERE pe.prot_entry_Id = pnm.prot_entry_id 
		AND pnm.prot_name_id = a.prot_name_id
		AND prot_identifier='{IDENTIFIER}'
	"""
	return run_query(query)


# // $[API]
# // Title: Search protein by protein name
# // Function: search_protein_by_name
# // Description: Search for a protein by using its name
# // Parameter: NAME | Protein name (Case sensitive) | string | Cyclin-dependent kinase 2 | required
# // Return: Protein record
# // Ecosystem: Proteomic:protein
# // Example: python3.12 biorels_api.py search_protein_by_name -NAME 'Cyclin-dependent kinase 2'
# // $[/API]
def search_protein_by_name(NAME):
	query=f"""
		SELECT * FROM prot_entry PE, prot_name A , prot_name_map pnm
		WHERE pe.prot_entry_Id = pnm.prot_entry_id
		AND pnm.prot_name_id = a.prot_name_id
		AND protein_name='{NAME}'
	"""
	return run_query(query)
	


# // $[API]
# // Title: Search protein by EC Number
# // Function: search_protein_by_EC_number
# // Description: Search for a protein by the Enzyme Commission number
# // Description: The search is done by including the sub-levels of the EC number.
# // Parameter: EC | Enzyme commission number | string | 2.7.11.22 | required
# // Return: Protein record
# // Ecosystem: Proteomic:protein
# // Example: python3.12 biorels_api.py search_protein_by_EC_number -EC '2.7.11.22'
# // Example: python3.12 biorels_api.py search_protein_by_EC_number -EC '2.7'
# // $[/API]
def search_protein_by_EC_number(EC):
	query=f"""
		SELECT * FROM prot_entry PE, prot_name A , prot_name_map pnm
		WHERE pe.prot_entry_Id = pnm.prot_entry_id
		AND pnm.prot_name_id = a.prot_name_id
		AND ec_number LIKE '{EC}%'
	"""
	return run_query(query)
	




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////////// PROTEIN - GENE  ///////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get protein information by NBCI gene identifier
# // Function: get_protein_by_gene
# // Description: Search for a protein by using its NCBI gene identifier
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Protein record
# // Ecosystem: Genomics:gene;Proteomic:protein
# // Example: python3.12 biorels_api.py get_protein_by_gene -GENE_ID 1017
# // $[/API]
def get_protein_by_gene(GENE_ID):
	query=f"""
		SELECT * FROM prot_entry PE, gn_prot_map PGM, gn_entry GE
		WHERE pe.prot_entry_Id = pgm.prot_entry_id 
		AND GE.gn_entry_id = pgm.gn_entry_id
		AND gene_id='{GENE_ID}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get protein by gene symbol
# // Function: get_protein_by_gene_symbol
# // Description: Search for a protein by using its gene symbol
# // Parameter: SYMBOL | Gene symbol | string | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | optional | Default: 9606
# // Return: Protein record
# // Ecosystem: Genomics:gene;Proteomic:protein
# // Example: python3.12 biorels_api.py get_protein_by_gene_symbol -SYMBOL 'CDK2'
# // $[/API]
def get_protein_by_gene_symbol(SYMBOL, TAX_ID='9606'):
	data=get_gene_by_gene_symbol(SYMBOL,[TAX_ID])
	if len(data)==0:
		return []
	for record in data:
		record['PROTEIN']=get_protein_by_gene(record['gene_id'])
	return data



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////// PROTEIN - TAXON  //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get protein by taxon
# // Function: list_protein_by_taxon
# // Description: Get all protein records for a given taxonomic identifier
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | required
# // Return: Protein record
# // Ecosystem: Proteomic:protein;Genomics:taxon
# // Example: python3.12 biorels_api.py list_protein_by_taxon -TAX_ID 9606
# // Warning: High volume
# // $[/API]
def list_protein_by_taxon(TAX_ID):
	query=f"""
		SELECT * FROM prot_entry PE, taxon t
		WHERE pe.taxon_id=t.taxon_id
		AND tax_id='{TAX_ID}'
	"""
	return run_query(query)






# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////// PROTEIN SEQUENCE  //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all protein sequence record by protein identifier
# // Function: get_protein_sequences_for_entry
# // Description: Search for a protein sequence by using the protein identifier
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Parameter: CANONICAL_ONLY | Is canonical sequence | boolean | false | optional | Default: false
# // Return: Protein sequence record
# // Ecosystem: Proteomic:protein|protein sequence
# // Example: python3.12 biorels_api.py get_protein_sequences_for_entry -PROT_IDENTIFIER 'CDK2_HUMAN' -CANONICAL_ONLY false
# // $[/API]
def get_protein_sequences_for_entry(PROT_IDENTIFIER, CANONICAL_ONLY=False):
	query=f"""
		SELECT * 
		FROM prot_seq ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  prot_identifier='{PROT_IDENTIFIER}'
	"""
	if CANONICAL_ONLY:
		query += " AND is_primary='T'"
	return run_query(query)



# // $[API]
# // Title: Get protein sequence by protein identifier
# // Function: search_protein_sequence_by_isoform_id
# // Description: Search for a protein sequence by using the isoform identifier
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Protein sequence record
# // Ecosystem: Proteomic:protein|protein sequence
# // Example: python3.12 biorels_api.py search_protein_sequence_by_isoform_id -ISOFORM_ID 'P24941-1'
# // $[/API]
def search_protein_sequence_by_isoform_id(ISOFORM_ID):
	query=f"""
		SELECT * 
		FROM prot_seq ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  iso_id='{ISOFORM_ID}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get information for a protein isoform
# // Function: get_isoform_info
# // Description: Get information for a protein isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Protein isoform record
# // Ecosystem: Proteomic:protein|protein sequence
# // Example: python3.12 biorels_api.py get_isoform_info -ISOFORM_ID 'P24941-1'
# // $[/API]
def get_isoform_info(ISOFORM_ID):
	query=f"""
		SELECT * 
		FROM prot_seq ps, prot_entry pe,taxon t
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND t.taxon_id = pe.taxon_id
		AND  iso_id='{ISOFORM_ID}'
	"""
	data=run_query(query)
	if len(data)==0:
		return None
	for entry in data:
		res=run_query(f"SELECT * FROM prot_seq_pos WHERE prot_seq_id={entry['prot_seq_id']} ORDER BY position ASC")
		str=''
		if len(res)==0:
			continue
		for l in res:
			str+=l['letter']
		entry['sequence']=str
	return data



# // $[API]
# // Title: Get protein sequence in fasta by isoform
# // Function: get_fasta_sequence
# // Description: Retrieve fasta sequence of a protein isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Protein sequence in fasta format
# // Ecosystem: Proteomic:protein|protein sequence
# // Example: python3.12 biorels_api.py get_fasta_sequence -ISOFORM_ID 'P24941-1'
# // $[/API]
def get_fasta_sequence(ISOFORM_ID):
	query=f"""
		SELECT * 
		FROM prot_seq ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  iso_id='{ISOFORM_ID}'
	"""
	data=run_query(query)
	if len(data)==0:
		return None
	for entry in data:
		res=run_query(f"SELECT * FROM prot_seq_pos WHERE prot_seq_id={entry['prot_seq_id']} ORDER BY position ASC")
		str=''
		if len(res)==0:
			continue
		for l in res:
			str+=l['letter']
		print(">"+entry['prot_identifier']+"|"+entry['iso_id'])
		print("\n".join([str[i:i+100] for i in range(0, len(str), 100)]))
	return None


# // $[API]
# // Title: Get all protein sequences in fasta by protein identifier
# // Function: get_fasta_sequences
# // Description: Retrieve fasta sequences of a protein by using the protein identifier
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein sequences in fasta format
# // Ecosystem: Proteomic:protein|protein sequence
# // Example: python3.12 biorels_api.py get_fasta_sequences -PROT_IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
def get_fasta_sequences(PROT_IDENTIFIER):
	query=f"""
		SELECT * 
		FROM prot_seq ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  prot_identifier='{PROT_IDENTIFIER}'
	"""
	data=run_query(query)
	if len(data)==0:
		return None
	for entry in data:
		res=run_query(f"SELECT * FROM prot_seq_pos WHERE prot_seq_id={entry['prot_seq_id']} ORDER BY position ASC")
		str=''
		if len(res)==0:
			continue
		for l in res:
			str+=l['letter']
		print(">"+entry['prot_identifier']+"|"+entry['iso_id'])
		print("\n".join([str[i:i+100] for i in range(0, len(str), 100)]))
	return None

	

# // $[API]
# // Title: Get all protein sequences in fasta for a given gene
# // Function: get_fasta_sequences_by_gene
# // Description: Retrieve fasta sequences of a protein by using the NCBI gene identifier
# // Parameter: NCBI_GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Protein sequences in fasta format
# // Ecosystem: Proteomic:protein|protein sequence;Genomics:gene
# // Example: python3.12 biorels_api.py get_fasta_sequences_by_gene -NCBI_GENE_ID 1017
# // $[/API]
def get_fasta_sequences_by_gene(NCBI_GENE_ID):
	query=f"""
		SELECT * 
		FROM prot_seq ps, prot_entry pe, gn_prot_map pgm, gn_entry ge
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND ge.gn_entry_id = pgm.gn_entry_id
		AND pe.prot_entry_Id = pgm.prot_entry_id 
		AND  gene_id='{NCBI_GENE_ID}'
	"""
	data=run_query(query)
	if len(data)==0:
		return None
	for entry in data:
		res=run_query(f"SELECT * FROM prot_seq_pos WHERE prot_seq_id={entry['prot_seq_id']} ORDER BY position ASC")
		str=''
		if len(res)==0:
			continue
		for l in res:
			str+=l['letter']
		print(">"+entry['prot_identifier']+"|"+entry['iso_id'])
		print("\n".join([str[i:i+100] for i in range(0, len(str), 100)]))
	return None
	






# // $[API]
# // Title: Get all features for a protein isoform
# // Function: get_isoform_feature
# // Description: Get all features for a protein isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Feature record
# // Ecosystem: Proteomic:protein sequence
# // Example: python3.12 biorels_api.py get_isoform_feature -ISOFORM_ID 'P24941-1'
# // $[/API]
def get_isoform_feature(ISOFORM_ID):
	query=f"""
		SELECT pf.*, tag,pft.description FROM prot_seq ps, prot_feat pf, prot_feat_Type pft
		WHERE ps.prot_seq_id = pf.prot_seq_id
		AND pf.prot_feat_type_id = pft.prot_feat_type_id
		AND iso_id='{ISOFORM_ID}'
	"""
	tmp=run_query(query)
	data={}
	for t in tmp:
		data[t['prot_feat_id']]=t
	if len(data)==0:
		return None
	query=f"""
		SELECT prot_feat_id, pmid,eco_id,eco_name,title, publication_date 
		FROM prot_feat_pmid pfm, eco_Entry ee, pmid_entry pe
		WHERE pfm.eco_entry_id = ee.eco_entry_id
		AND pe.pmid_entry_id = pfm.pmid_entry_id
		AND prot_feat_id IN ({','.join([str(x) for x in data.keys()])})
	"""
	tmp=run_query(query)
	for t in tmp:
		data[t['prot_feat_id']]['PMID']=[]
		data[t['prot_feat_id']]['PMID'].append(t)
	return data



# // $[API]
# // Title: Get all textual descriptions for a protein 
# // Function: get_protein_description
# // Description: Get all textual descriptions for a protein
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | required
# // Return: Protein description record
# // Ecosystem: Proteomic:protein
# // Example: python3.12 biorels_api.py get_protein_description -PROT_IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
def get_protein_description(PROT_IDENTIFIER):
	query=f"""
		SELECT * FROM prot_entry PE, prot_desc A 
		WHERE pe.prot_entry_Id = a.prot_entry_id 
		AND prot_identifier='{PROT_IDENTIFIER}'
	"""
	return run_query(query)







# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////// PROTEIN DOMAIN  //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all protein domain record by protein identifier
# // Function: get_protein_domains_for_entry
# // Description: Search for a protein domain by using the protein identifier
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein domain record
# // Ecosystem: Proteomic:protein|protein domain
# // Example: python3.12 biorels_api.py get_protein_domains_for_entry -PROT_IDENTIFIER 'CDK2_HUMAN' 
# // $[/API]
def get_protein_domains_for_entry(PROT_IDENTIFIER):
	query=f"""
		SELECT * 
		FROM prot_dom ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  prot_identifier='{PROT_IDENTIFIER}'
	"""
	return run_query(query)


# // $[API]
# // Title: Get protein domain by protein identifier
# // Function: search_protein_domain_by_domain_name
# // Description: Search for a protein domain by using the d identifier
# // Parameter: DOMAIN_NAME | Name of domain | string | Protein kinase | required
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | optional | Default: None
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: None
# // Return: Protein domain record
# // Ecosystem: Proteomic:protein|protein domain
# // Example: python3.12 biorels_api.py search_protein_domain_by_domain_name -DOMAIN_NAME 'Protein kinase' -PROT_IDENTIFIER 'CDK2_HUMAN'
# // Example: python3.12 biorels_api.py search_protein_domain_by_domain_name -DOMAIN_NAME 'Protein kinase'  -TAX_ID 9606
# // $[/API]
def search_protein_domain_by_domain_name(DOMAIN_NAME, PROT_IDENTIFIER=None, TAX_ID=None):
	query=f"""
		SELECT * 
		FROM prot_dom ps, prot_entry pe 
	"""
	if TAX_ID != None:
		query += ", taxon t "
	query += " WHERE pe.prot_entry_Id = ps.prot_entry_id AND  domain_name='"+DOMAIN_NAME+"'"
	if PROT_IDENTIFIER != None:
		query += f" AND  prot_identifier='{PROT_IDENTIFIER}'"
	if TAX_ID != None:
		query += f" AND pe.taxon_id=t.taxon_id AND tax_id='{TAX_ID}'"

	return run_query(query)





# // $[API]
# // Title: Get all protein domains in fasta by protein identifier
# // Function: get_fasta_domains
# // Description: Retrieve fasta domains of a protein by using the protein identifier
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein domains in fasta format
# // Ecosystem: Proteomic:protein|protein domain
# // Example: python3.12 biorels_api.py get_fasta_domains -PROT_IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
def get_fasta_domains(PROT_IDENTIFIER):
	query=f"""
		SELECT * 
		FROM prot_dom ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  prot_identifier='{PROT_IDENTIFIER}'
	"""
	data=run_query(query)
	if len(data)==0:
		return None
	for entry in data:
		res=run_query(f"SELECT * FROM prot_dom_seq pds, prot_seq_pos psp WHERE psp.prot_seq_pos_id = pds.prot_Seq_pos_id AND prot_dom_id={entry['prot_dom_id']} ORDER BY pds.position ASC")
		str_l=''
		if len(res)==0:
			continue
		for l in res:
			str_l+=l['letter']
		
		print(">"+entry['prot_identifier']+"|"+entry['domain_type']+"|"+entry['domain_name']+"|"+str(entry['pos_start'])+"|"+str(entry['pos_end']))
		print("\n".join([str_l[i:i+100] for i in range(0, len(str_l), 100)]))
	return None


# // $[API]
# // Title: Get all protein domains in fasta for a given gene
# // Function: get_fasta_domains_by_gene
# // Description: Retrieve fasta domains of a protein by using the NCBI gene identifier
# // Parameter: NCBI_GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Protein domains in fasta format
# // Ecosystem: Proteomic:protein|protein domain;Genomics:gene
# // Example: python3.12 biorels_api.py get_fasta_domains_by_gene -NCBI_GENE_ID 1017
# // $[/API]
def get_fasta_domains_by_gene(NCBI_GENE_ID):
	query=f"""
		SELECT * 
		FROM prot_dom ps, prot_entry pe, gn_prot_map pgm, gn_entry ge
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND ge.gn_entry_id = pgm.gn_entry_id
		AND pe.prot_entry_Id = pgm.prot_entry_id 
		AND  gene_id='{NCBI_GENE_ID}'
	"""
	data=run_query(query)
	if len(data)==0:
		return None
	for entry in data:
		res=run_query(f"SELECT * FROM prot_dom_seq pds, prot_seq_pos psp WHERE psp.prot_seq_pos_id = pds.prot_Seq_pos_id AND prot_dom_id={entry['prot_dom_id']} ORDER BY pds.position ASC")
		str_l=''
		if len(res)==0:
			continue
		for l in res:
			str_l+=l['letter']
		print(">"+str(entry['gene_id'])+"|"+entry['symbol']+"|"+entry['prot_identifier']+"|"+entry['domain_type']+"|"+entry['domain_name']+"|"+str(entry['pos_start'])+"|"+str(entry['pos_end']))
		print("\n".join([str_l[i:i+100] for i in range(0, len(str_l), 100)]))
	return None




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////// PROTEIN SEQUENCE ALIGNMENT  ////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: list all protein sequence alignment by protein isoform
# // Function: list_protein_sequence_alignment
# // Description: Get all protein sequence alignment for a protein isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Protein sequence alignment record
# // Ecosystem: Proteomic:protein|protein sequence alignment
# // Example: python3.12 biorels_api.py list_protein_sequence_alignment -ISOFORM_ID 'P24941-1'
# // $[/API]
def list_protein_sequence_alignment(ISOFORM_ID):
	query=f"""
		SELECT pc.iso_name,pc.iso_id, pc.is_primary, pc.description, perc_sim,perc_identity,length,e_value,bit_score,perc_sim_com,perc_identity_com
	 FROM prot_seq ps, prot_seq_al psa, prot_seq pc
	WHERE ps.prot_seq_id = psa.prot_seq_ref_id
	AND pc.prot_seq_id = psa.prot_seq_comp_id
	AND  ps.iso_id='{ISOFORM_ID}'
	"""
	return run_query(query)


# // $[API]
# // Title: Get protein sequence alignment by protein isoform
# // Function: get_protein_sequence_alignment
# // Description: Get the protein sequence alignment for a protein isoform
# // Parameter: REF_ISO_ID | Reference isoform identifier | string | P24941-1 | required
# // Parameter: COMP_ISO_ID | Comparison isoform identifier | string | P24941-1 | required
# // Return: Protein sequence alignment record
# // Ecosystem: Proteomic:protein|protein sequence alignment
# // Example: python3.12 biorels_api.py get_protein_sequence_alignment -REF_ISO_ID 'P24941-1' -COMP_ISO_ID 'P24941-1'
# // $[/API]
def get_protein_sequence_alignment(REF_ISO_ID,COMP_ISO_ID):
	query=f"""
		SELECT prot_seq_al_id,perc_sim,perc_identity,length,e_value,bit_score,perc_sim_com,perc_identity_com
	 FROM prot_seq ps, prot_seq_al psa, prot_seq pc
	WHERE ps.prot_seq_id = psa.prot_seq_ref_id
	AND pc.prot_seq_id = psa.prot_seq_comp_id
	AND  ps.iso_id='{REF_ISO_ID}'
	AND pc.iso_id='{COMP_ISO_ID}'
	"""
	tmp= run_query(query)
	if len(tmp)==0:
		return None

	SEQ_REF={}
	SEQ_COMP={}
	query=f"""
		SELECT iso_id, position, letter FROM prot_seq_pos psp, prot_seq ps
		 WHERE ps.prot_seq_id = psp.prot_seq_id
		 AND (ps.iso_id='{REF_ISO_ID}' OR ps.iso_id = '{COMP_ISO_ID}') ORDER BY iso_id,position ASC
	"""
	res=run_query(query)
	for r in res:
		if r['iso_id']==REF_ISO_ID:
			SEQ_REF[r['position']]=r['letter']
		else:
			SEQ_COMP[r['position']]=r['letter']

	data={}
	for t in tmp:
		data[t['prot_seq_al_id']]={}
		data[t['prot_seq_al_id']]['INFO']=t
		list_match=run_query(f"""
			SELECT USP1.POSITION as REF_POS, USP2.POSITION as COMP_POS
			FROM PROT_SEQ_al_seq UDA,  PROT_SEQ_POS USP1, PROT_SEQ_POS USP2
			WHERE uda.PROT_SEQ_ID_ref= USP1.PROT_SEQ_POS_ID
			AND uda.PROT_SEQ_ID_comp= USP2.PROT_SEQ_POS_ID
			AND uda.PROT_SEQ_al_id = {t['prot_seq_al_id']} ORDER BY USP1.POSITION ASC
		""")
		CURR_REF_POS=1
		CURR_ALT_POS=1
		data[t['prot_seq_al_id']]['MATCH']=[]
		for m in list_match:
			for _ in range(CURR_REF_POS,m['ref_pos']):
				data[t['prot_seq_al_id']]['MATCH'].append([CURR_REF_POS,SEQ_REF[CURR_REF_POS],'','',''])
				CURR_REF_POS+=1
			for _ in range(CURR_ALT_POS,m['comp_pos']):
				data[t['prot_seq_al_id']]['MATCH'].append(['','',CURR_ALT_POS,SEQ_COMP[CURR_ALT_POS],''])
				CURR_ALT_POS+=1
			data[t['prot_seq_al_id']]['MATCH'].append([m['ref_pos'],SEQ_REF[m['ref_pos']],m['comp_pos'],SEQ_COMP[m['comp_pos']],'MATCH' if SEQ_REF[m['ref_pos']]==SEQ_COMP[m['comp_pos']] else 'MISMATCH'])
			CURR_REF_POS+=1
			CURR_ALT_POS+=1
		for _ in range(CURR_REF_POS,max(SEQ_REF.keys())):
			data[t['prot_seq_al_id']]['MATCH'].append([CURR_REF_POS,SEQ_REF[CURR_REF_POS],'',''])
			CURR_REF_POS+=1
		for _ in range(CURR_ALT_POS,max(SEQ_COMP.keys())):
			data[t['prot_seq_al_id']]['MATCH'].append(['','',CURR_ALT_POS,SEQ_COMP[CURR_ALT_POS]])
			CURR_ALT_POS+=1
	return data

	




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////// PROTEIN TRANSCRIPT  //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get protein transcript translation
# // Function: get_translation
# // Description: Get the mapping between a transcript and a translated protein
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | ENST00000379031 | required
# // Return: Protein record and sequence, transcript record and sequence, translation order by transcript sequence
# // Ecosystem: Proteomic:protein;Genomics:transcript
# // Example: python3.12 biorels_api.py get_translation -ISOFORM_ID 'P24941-1' -TRANSCRIPT_NAME 'ENST00000379031'
# // $[/API]
def get_translation(ISOFORM_ID, TRANSCRIPT_NAME):
	data={}
	data['TRANSCRIPT']=search_transcript(TRANSCRIPT_NAME)
	data['ISOFORM']=search_protein_sequence_by_isoform_id(ISOFORM_ID)
	if data['TRANSCRIPT']==[] or data['ISOFORM']==[]:
		return []
	for TR in data['TRANSCRIPT']:
		TR['SEQ']={}
		res=run_query("SELECT * FROM transcript_pos WHERE transcript_id="+str(TR['transcript_id'])+' ORDER BY seq_pos ASC')
		for line in res:
			TR['SEQ'][line['transcript_pos_id']]=[line['nucl'],line['seq_pos']]
	for PROT in data['ISOFORM']:
		PROT['SEQ']={}
		res=run_query("SELECT * FROM prot_seq_pos WHERE prot_seq_id="+str(PROT['prot_seq_id'])+' ORDER BY position ASC')
		for line in res:
			PROT['SEQ'][line['prot_seq_pos_id']]=[line['letter'],line['position']]
	for TR in data['TRANSCRIPT']:
		for PROT in data['ISOFORM']:
			res=run_query("SELECT * FROM tr_protseq_al WHERE prot_seq_id="+str(PROT['prot_seq_id'])+' AND transcript_id='+str(TR['transcript_id']))
			if len(res)==0:
				continue
			data['TRANSLATION']={}
			for al in res:
				data['TRANSLATION'][al['tr_protseq_al_id']]={}
				
				for k,v in TR['SEQ'].items():
					data['TRANSLATION'][al['tr_protseq_al_id']][v[1]]=[v[0],v[1],'','','']
				res2=run_query("SELECT * FROM tr_protseq_pos_al WHERE tr_protseq_al_id="+str(al['tr_protseq_al_id']))
				for l2 in res2:
					data['TRANSLATION'][al['tr_protseq_al_id']][TR['SEQ'][l2['transcript_pos_id']][1]]=[TR['SEQ'][l2['transcript_pos_id']][0],TR['SEQ'][l2['transcript_pos_id']][1],PROT['SEQ'][l2['prot_seq_pos_id']][0],PROT['SEQ'][l2['prot_seq_pos_id']][1],l2['triplet_pos']]
				data['TRANSLATION'][al['tr_protseq_al_id']]=dict(sorted(data['TRANSLATION'][al['tr_protseq_al_id']].items()))
	return data
	  


# // $[API]
# // Title: Get protein transcript translation for a given isoform
# // Function: get_translation_for_isoform
# // Description: Get the mapping between a transcript and a translated protein for a given isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Protein record and sequence, transcript record and sequence, translation order by transcript sequence
# // Ecosystem: Proteomic:protein;Genomics:transcript
# // Example: python3.12 biorels_api.py get_translation_for_isoform -ISOFORM_ID 'P24941-1'
# // $[/API]
def get_translation_for_isoform(ISOFORM_ID):
	res=run_query("SELECT * FROM transcript T, tr_protseq_al tua, prot_seq ps WHERE T.transcript_id = tua.transcript_id AND tua.prot_seq_id = ps.prot_seq_id AND ps.iso_id='"+ISOFORM_ID+"'")
	if len(res)==0:
		return []
	data=[]
	for line in res:
		data.append(get_translation(ISOFORM_ID,line['transcript_name']+('.'+line['transcript_version'] if line['transcript_version']!='' else '')))

	return data



# // $[API]
# // Title: Get protein transcript translation for a given transcript
# // Function: get_translation_for_transcript
# // Description: Get the mapping between a transcript and a translated protein for a given transcript
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | NM_001798 | required
# // Return: Protein record and sequence, transcript record and sequence, translation order by transcript sequence
# // Ecosystem: Proteomic:protein;Genomics:transcript
# // Example: python3.12 biorels_api.py get_translation_for_transcript -TRANSCRIPT_NAME NM_001798
# // $[/API]
def get_translation_for_transcript(TRANSCRIPT_NAME):
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""

		SELECT *
		FROM PROT_SEQ PS, TR_PROTSEQ_AL TUA, TRANSCRIPT T
		WHERE T.TRANSCRIPT_ID = TUA.TRANSCRIPT_ID
		AND TUA.PROT_SEQ_ID = PS.PROT_SEQ_ID AND T.TRANSCRIPT_NAME ='{TRANSCRIPT_NAME}'
	"""
	if pos != -1:
		query += f" AND T.TRANSCRIPT_VERSION='{TRANSCRIPT_VERSION}'"
	res = run_query(query)
	if len(res) == 0:
		return []
	data = []
	for line in res:
		data.append(get_translation(line['iso_id'], TRANSCRIPT_NAME))
	return data


# // $[API]
# // Title: Get protein transcript translation for a given transcript position
# // Function: get_translation_transcript_pos
# // Description: Get the mapping between a transcript and a translated protein for a given transcript position
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | NM_001798 | required
# // Parameter: POSITION | Transcript position | int | 400 | required
# // Return: Protein record and sequence, transcript record and sequence, translation order by transcript sequence
# // Ecosystem: Proteomic:protein;Genomics:transcript
# // Example: python3.12 biorels_api.py get_translation_transcript_pos -TRANSCRIPT_NAME NM_001798 -POSITION 400
# // $[/API]
def get_translation_transcript_pos(TRANSCRIPT_NAME, POSITION):
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""
		SELECT triplet_pos,iso_id, iso_name, letter,position, psp.prot_seq_pos_id, ps.prot_seq_id,prot_entry_id 
		FROM PROT_SEQ_POS PSP,PROT_SEQ PS, TR_PROTSEQ_AL TUA, TR_PROTSEQ_POS_AL TPSP, TRANSCRIPT T, TRANSCRIPT_POS TP WHERE
		PSP.PROT_SEQ_ID = PS.PROT_SEQ_ID AND PSP.PROT_SEQ_POS_ID = TPSP.PROT_SEQ_POS_ID
		AND TUA.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND T.TRANSCRIPT_ID = TP.TRANSCRIPT_ID
		AND TP.TRANSCRIPT_POS_ID = TPSP.TRANSCRIPT_POS_ID
		AND TUA.PROT_SEQ_ID = PS.PROT_SEQ_ID 
		AND TP.SEQ_POS={POSITION} AND T.TRANSCRIPT_NAME ='{TRANSCRIPT_NAME}'
	"""
	if pos != -1:
		query += f" AND T.TRANSCRIPT_VERSION='{TRANSCRIPT_VERSION}'"
	res = run_query(query)
	return res



# // $[API]
# // Title: Get protein transcript translation for a given protein position
# // Function: get_translation_isoform_pos
# // Description: Get the mapping between a transcript and a translated protein for a given protein position
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Parameter: POSITION | Transcript position | int | 74 | required
# // Return: Protein record and sequence, transcript record and sequence, translation
# // Ecosystem: Proteomic:protein;Genomics:transcript
# // Example: python3.12 biorels_api.py get_translation_isoform_pos -ISOFORM_ID P24941-1 -POSITION 74
# // $[/API]
def get_translation_isoform_pos(ISOFORM_ID, POSITION):
	query = f"""
		SELECT transcript_name, transcript_version,  exon_id, seq_pos,nucl,  triplet_pos, seq_pos_type_id,T.transcript_id, TP.transcript_pos_id, chr_seq_pos_id
		FROM PROT_SEQ_POS PSP,PROT_SEQ PS, TR_PROTSEQ_AL TUA, TR_PROTSEQ_POS_AL TPSP, TRANSCRIPT T, TRANSCRIPT_POS TP WHERE
		PSP.PROT_SEQ_ID = PS.PROT_SEQ_ID AND PSP.PROT_SEQ_POS_ID = TPSP.PROT_SEQ_POS_ID
		AND TUA.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND T.TRANSCRIPT_ID = TP.TRANSCRIPT_ID
		AND TP.TRANSCRIPT_POS_ID = TPSP.TRANSCRIPT_POS_ID
		AND TUA.PROT_SEQ_ID = PS.PROT_SEQ_ID
		AND POSITION={POSITION} AND ISO_ID ='{ISOFORM_ID}'
	"""
	res = run_query(query)
	return res



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////  GENE ONTOLOGY  //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: Search gene ontology record by name and/or accession and/or namespace
# // Function: search_gene_ontology
# // Description: Search for a gene ontology record by using its name and/or accession and/or namespace
# // Parameter: AC | Gene ontology accession | string | GO:0010389 | optional | Default: None
# // Parameter: NAME | Gene ontology name | string | cell cycle | optional | Default: None
# // Parameter: NAMESPACE | Gene ontology namespace | string | biological_process | optional | Default: None
# // Return: Gene ontology record
# // Ecosystem: Proteomic:gene ontology
# // Example: python3.12 biorels_api.py search_gene_ontology -AC 'GO:0010389'
# // Example: python3.12 biorels_api.py search_gene_ontology -NAME 'cell cycle'
# // Example: python3.12 biorels_api.py search_gene_ontology -NAMESPACE 'biological_process'
# // $[/API]
def search_gene_ontology(AC=None, NAME=None, NAMESPACE=None):
	query = "SELECT * FROM go_entry WHERE 1=1"
	if AC != None:
		query += f" AND AC='{AC}'"
	if NAME != None:
		query += f" AND name LIKE '%{NAME}%'"
	if NAMESPACE != None:
		query += f" AND namespace='{NAMESPACE}'"
	return run_query(query)


# // $[API]
# // Title: List external identifiers for a gene ontology record
# // Function: get_gene_ontology_dbref
# // Description: Get all external identifiers for a gene ontology record
# // Parameter: AC | Gene ontology accession | string | GO:0010389 | required
# // Return: Gene ontology external identifiers
# // Ecosystem: Proteomic:gene ontology
# // Example: python3.12 biorels_api.py get_gene_ontology_dbref -AC 'GO:0010389'
# // $[/API]
def get_gene_ontology_dbref(AC):
	query = f"""
		SELECT * 
		FROM GO_DBREF G, GO_ENTRY GE , SOURCE S
		WHERE GE.GO_ENTRY_ID = G.GO_ENTRY_ID 
		AND S.Source_ID = G.Source_ID
		AND AC='{AC}'
	"""
	return run_query(query)




# // $[API]
# // Title: Get all child gene ontology records for a gene ontology record
# // Function: get_child_gene_ontology
# // Description: Get all child gene ontology records for a gene ontology record
# // Parameter: AC | Gene ontology accession | string | GO:0010389 | required
# // Parameter: MAX_LEVEL | Maximum level of child gene ontology records | int | 1 | optional | Default: 1
# // Parameter: WITH_OBSOLETE | Include obsolete records | boolean | false | optional 
# // Return: List of Gene ontology record that are children of the given gene ontology record
# // Ecosystem: Proteomic:gene ontology
# // Example: python3.12 biorels_api.py get_child_gene_ontology -AC 'GO:0010389'
# // Example: python3.12 biorels_api.py get_child_gene_ontology -AC 'GO:0010389' -MAX_LEVEL 2
# // $[/API]
def get_child_gene_ontology(AC, MAX_LEVEL=1, WITH_OBSOLETE=False):
	RELS = {}
	LIST_DONE = []
	res = run_query(f"SELECT * FROM go_entry where AC='{AC}'")
	for line in res:
		if not RELS:
			RELS[0]={}
		RELS[0][line['ac']] = line
		LIST_DONE.append(AC)
	for I in range(1, int(MAX_LEVEL)+1):
		query = f"""
			SELECT gr.rel_Type, gr.subrel_type,gp.* 
			FROM go_entry gp, go_rel gr, go_entry gc
			WHERE gp.go_entry_id = gr.go_from_id
			AND gc.go_entry_id = gr.go_to_id
			AND gc.AC IN ('{','.join(RELS[I-1].keys())}')
		"""
		if not WITH_OBSOLETE:
			query += " AND gp.is_obsolete='F'"
		res = run_query(query)
		for line in res:
			if line['ac'] in LIST_DONE:
				continue
			if I not in RELS:
				RELS[I]={}
			RELS[I][line['ac']] = line
			LIST_DONE.append(line['ac'])
		if not RELS[I] or RELS[I] == {}:
			break
	return RELS


# // $[API]
# // Title: Get all parent gene ontology records for a gene ontology record
# // Function: get_parent_gene_ontology
# // Description: Get all parent gene ontology records for a gene ontology record
# // Parameter: AC | Gene ontology accession | string | GO:0010389 | required
# // Parameter: MAX_LEVEL | Maximum level of parent gene ontology records | int | 1 | optional | Default: 1
# // Parameter: WITH_OBSOLETE | Include obsolete records | boolean | false | optional 
# // Return: List of Gene ontology record that are parent of the given gene ontology record
# // Ecosystem: Proteomic:gene ontology
# // Example: python3.12 biorels_api.py get_parent_gene_ontology -AC 'GO:0010389'
# // Example: python3.12 biorels_api.py get_parent_gene_ontology -AC 'GO:0010389' -MAX_LEVEL 2
# // $[/API]
def get_parent_gene_ontology(AC, MAX_LEVEL=1, WITH_OBSOLETE=False):
	RELS = {}
	LIST_DONE = []
	res = run_query(f"SELECT * FROM go_entry where AC='{AC}'")
	for line in res:
		RELS[0]={}
		RELS[0][line['ac']] = line
		LIST_DONE.append(AC)
	for I in range(1, int(MAX_LEVEL)+1):
		query = f"""
			SELECT gr.rel_Type, gr.subrel_type,gc.* 
			FROM go_entry gp, go_rel gr, go_entry gc
			WHERE gp.go_entry_id = gr.go_from_id
			AND gc.go_entry_id = gr.go_to_id
			AND gp.AC IN ('{','.join(RELS[I-1].keys())}')
		"""
		if not WITH_OBSOLETE:
			query += " AND gc.is_obsolete='F'"
		res = run_query(query)
		
		for line in res:
			if line['ac'] in LIST_DONE:
				continue
			if I not in RELS:
				RELS[I]={}
			RELS[I][line['ac']] = line
			LIST_DONE.append(line['ac'])
		if not RELS[I] or RELS[I] == {}:
			break
	return RELS


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////// PROTEIN - GENE ONTOLOGY  //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get gene ontology by protein identifier
# // Function: get_gene_onto_by_protein
# // Description: Get the gene ontology for a protein by using its identifier
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Gene ontology record
# // Ecosystem: Proteomic:protein|gene ontology
# // Example: python3.12 biorels_api.py get_gene_onto_by_protein -PROT_IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
def get_gene_onto_by_protein(PROT_IDENTIFIER):
	query=f"""
		SELECT g.*, pgm.evidence, source_name FROM go_entry g, prot_entry PE, prot_go_map PGM,source S
		WHERE pe.prot_entry_Id = pgm.prot_entry_id 
		AND g.GO_entry_ID = pgm.GO_entry_ID
		AND pgm.source_id = s.source_id
		AND  prot_identifier='{PROT_IDENTIFIER}'
	"""
	return run_query(query)


# // $[API]
# // Title: Search protein records by gene ontology accession
# // Function: search_protein_by_gene_onto_ac
# // Description: Search for a protein by using its gene ontology accession
# // Parameter: GO_AC | Gene ontology accession | string | GO:0010389 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | optional | 9606 | Default: None
# // Return: Protein record
# // Ecosystem: Proteomic:protein|gene ontology
# // Example: python3.12 biorels_api.py search_protein_by_gene_onto_ac -GO_AC 'GO:0010389'
# // Example: python3.12 biorels_api.py search_protein_by_gene_onto_ac -GO_AC 'GO:0010389' -TAX_ID 9606
# // $[/API]
def search_protein_by_gene_onto_ac(GO_AC, TAX_ID=None):
	query=f"""
		SELECT pe.*, pgm.evidence
		FROM prot_entry PE, prot_go_map PGM, go_entry GE, taxon t
		WHERE pe.prot_entry_Id = pgm.prot_entry_id
		AND t.taxon_id = pe.taxon_id
		AND GE.GO_entry_ID = PGM.GO_entry_ID
		AND AC='{GO_AC}'
	"""
	if TAX_ID != None:
		query += f" AND tax_id='{TAX_ID}'"
	return run_query(query)




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////// GENE - GENE ONTOLOGY  //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Get gene ontology by gene_id
# // Function: get_gene_onto_by_gene_id
# // Description: Get the gene ontology for a gene by using its NCBI gene identifier
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Gene ontology record
# // Ecosystem: Proteomic:gene ontology;Genomics:gene
# // Example: python3.12 biorels_api.py get_gene_onto_by_gene_id -GENE_ID 1017
# // $[/API]
def get_gene_onto_by_gene_id(GENE_ID):
	query=f"""
		SELECT g.*, pgm.evidence, source_name
		FROM go_entry g, prot_entry PE, prot_go_map PGM,source S, gn_prot_map GPM, gn_entry GE
		WHERE pe.prot_entry_Id = pgm.prot_entry_id 
		AND ge.gn_entry_id = gpm.gn_entry_id
		AND gpm.prot_entry_id = pe.prot_entry_Id
		AND g.GO_entry_ID = pgm.GO_entry_ID
		AND pgm.source_id = s.source_id
		AND gene_id={GENE_ID}
	"""
	return run_query(query)



# // $[API]
# // Title: Search gene records by gene ontology accession
# // Function: search_gene_by_gene_onto_ac
# // Description: Search for a gene by using its gene ontology accession
# // Parameter: GO_AC | Gene ontology accession | string | GO:0010389 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional
# // Return: Gene record
# // Ecosystem: Proteomic:gene ontology;Genomics:gene
# // Example: python3.12 biorels_api.py search_gene_by_gene_onto_ac -GO_AC 'GO:0010389'
# // Example: python3.12 biorels_api.py search_gene_by_gene_onto_ac -GO_AC 'GO:0010389' -TAX_ID 9606
# // $[/API]
def search_gene_by_gene_onto_ac(GO_AC, TAX_ID=''):
	query=f"""
		SELECT DISTINCT ge.*, pgm.evidence, source_name, tax_id
		FROM go_entry g, prot_entry PE, prot_go_map PGM,source S, gn_prot_map GPM, gn_entry GE, taxon t
		WHERE pe.prot_entry_Id = pgm.prot_entry_id
		AND ge.gn_entry_id = gpm.gn_entry_id
		AND gpm.prot_entry_id = pe.prot_entry_Id
		AND g.GO_entry_ID = pgm.GO_entry_ID
		AND pgm.source_id = s.source_id
		AND t.taxon_id = pe.taxon_id
		AND AC='{GO_AC}'
	"""
	if TAX_ID != '':
		query += f" AND tax_id='{TAX_ID}'"
	return run_query(query)




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////  MOLECULAR ENTITY ECOSYSTEM  //////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Search small molecule by name
# // Function: search_small_molecule_by_name
# // Description: Search for a small molecule by using its name
# // Parameter: NAME | Small molecule name | string | ATP | required
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py search_small_molecule_by_name -NAME 'ATP'
# // $[/API]
def search_small_molecule_by_name(NAME):
	query=f"""
	SELECT source_name, sm_name,inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, scaffold_smiles
	 FROM source s, sm_source ss, sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND ss.sm_entry_id = se.sm_entry_id
    AND s.source_id = ss.source_id
	AND sm_name='{NAME}
	"""
	return run_query(query)


# // $[API]
# // Title: Search small molecule by inchi
# // Function: search_small_molecule_by_inchi_key
# // Description: Search for a small molecule by using its InChI, values separated by comma
# // Parameter: INCHI_KEYs | Small molecule InChI KEY | array | ZKHQWZAMYRWXGA-KQYNXXCUSA-N | required
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py search_small_molecule_by_inchi_key -INCHI_KEYs 'ZKHQWZAMYRWXGA-KQYNXXCUSA-N'
# // $[/API]
def search_small_molecule_by_inchi_key(INCHI_KEYs):
	if INCHI_KEYs==[]:
		return []
	query=f"""
	SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND inchi_key IN ('{','.join(INCHI_KEYs)}')
	"""
	return run_query(query)


# // $[API]
# // Title: Search small molecule by full smiles
# // Function: search_small_molecule_by_full_smiles
# // Description: Search for a small molecule by using its smiles string, including counterions if any
# // Parameter: SMILES | Small molecule SMILES | array | Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O | required
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py search_small_molecule_by_full_smiles -SMILES 'Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O'
# // $[/API]
def search_small_molecule_by_full_smiles(SMILES):
	if SMILES==[]:
		return []
	query=f"""
	SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND full_smiles IN ('{','.join(SMILES)}')
	"""
	return run_query(query)




# // $[API]
# // Title: Search small molecule by smiles
# // Function: search_small_molecule_by_smiles
# // Description: Search for a small molecule by using its smiles string - without counterions
# // Parameter: SMILES | Small molecule SMILES | array | Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O | required
# // Parameter: WITHOUT_COUNTERION | True if only molecule without counterion requested | boolean | false | optional
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py search_small_molecule_by_smiles -SMILES 'Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O'
# // Example: python3.12 biorels_api.py search_small_molecule_by_smiles -SMILES 'Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O' -WITHOUT_COUNTERION true
# // $[/API]
def search_small_molecule_by_smiles(SMILES, WITHOUT_COUNTERION=False):
	if SMILES==[]:
		return []
	query=f"""
	SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND smiles IN ('{','.join(SMILES)}')
	"""
	if WITHOUT_COUNTERION:
		query += " AND se.sm_counterion_id IS NULL"
	return run_query(query)


# // $[API]
# // Title: Search small molecule by scaffold smiles
# // Function: search_small_molecule_by_Scaffold
# // Description: Search for a small molecule by using its smiles string - without counterions
# // Parameter: SCAFFOLD_SMILES | Small molecule scaffold as SMILES | array | c1cccc1 | required
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py search_small_molecule_by_Scaffold -SCAFFOLD_SMILES 'c1ccccc1'
# // $[/API]
def search_small_molecule_by_Scaffold(SCAFFOLD_SMILES):
	if SCAFFOLD_SMILES==[]:
		return []
	query=f"""
	SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND SCAFFOLD_SMILES IN ('{','.join(SCAFFOLD_SMILES)}')
	"""
	return run_query(query)




# // $[API]
# // Title: Get small molecule information
# // Function: get_small_molecule
# // Description: Get the small molecule information by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Parameter: COMPLETE | True if all information is requested | boolean | false | optional
# // Return: Small molecule record with names, patents, descriptions, counterions and scaffolds
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py  get_small_molecule -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
def get_small_molecule(MD5_HASH, COMPLETE=False):
	query=f"""
	SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se 
	 LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND md5_hash='{MD5_HASH}'
	"""
	data= run_query(query)
	if not COMPLETE:
		return data
	for line in data:
		line['NAMES']=get_small_molecule_names(line['md5_hash'])
		line['PATENT']=get_small_molecule_patent(line['md5_hash'])
		line['DESCRIPTION']=get_small_molecule_description(line['md5_hash'])
	return data



# // $[API]
# // Title: Get small molecule names
# // Function: get_small_molecule_names
# // Description: Get the small molecule names by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule names
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py  get_small_molecule_names -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
def get_small_molecule_names(MD5_HASH):
	query=f"""
	SELECT sm_name,source_name 
	FROM sm_source sn, source s, sm_entry se
	 WHERE sn.source_id = s.source_id 
	 AND sn.sm_entry_id = se.sm_entry_id
	 AND md5_hash='{MD5_HASH}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get small molecule patent
# // Function: get_small_molecule_patent
# // Description: Get the small molecule patent by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule patent
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py  get_small_molecule_patent -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
def get_small_molecule_patent(MD5_HASH):
	query=f"""
	SELECT patent_application 
	FROM sm_patent_map sp, patent_entry p, sm_entry se
	WHERE sp.patent_entry_id = p.patent_entry_id
	AND sp.sm_entry_id = se.sm_entry_id
	AND md5_hash='{MD5_HASH}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get small molecule description
# // Function: get_small_molecule_description
# // Description: Get the small molecule description by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule description
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py  get_small_molecule_description -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
def get_small_molecule_description(MD5_HASH):
	query=f"""
	SELECT description_text,description_type,source_name 
	FROM sm_description sd, sm_entry se, source s
	WHERE sd.sm_entry_id = se.sm_entry_id
	AND sd.source_id = s.source_id
	AND md5_hash='{MD5_HASH}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get small molecule counterion
# // Function: get_small_molecule_counterion
# // Description: Get the small molecule counterion by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule counterion
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py  get_small_molecule_counterion -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
def get_small_molecule_counterion(MD5_HASH):
	query=f"""
	SELECT counterion_smiles
	FROM sm_counterion sc, sm_entry se
	WHERE sc.sm_counterion_id = se.sm_counterion_id
	AND md5_hash='{MD5_HASH}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get small molecule scaffold
# // Function: get_small_molecule_scaffold
# // Description: Get the small molecule scaffold by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule scaffold
# // Ecosystem: Molecular entity:small molecule
# // Example: python3.12 biorels_api.py  get_small_molecule_scaffold -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
def get_small_molecule_scaffold(MD5_HASH):
	query=f"""
	SELECT scaffold_smiles 
	FROM sm_scaffold sc, sm_entry se, sm_molecule sm
	WHERE sc.sm_scaffold_id = sm.sm_scaffold_id
	AND sm.sm_molecule_id = se.sm_molecule_id
	AND md5_hash='{MD5_HASH}'
	"""
	return run_query(query)



# // $[API]
# // Title: Get molecular entity information
# // Function: get_molecular_entity
# // Description: Get the molecular entity information with its component by using its hash
# // Parameter: MOLECULAR_ENTITY_HASH | Molecular entity hash | string | 962fb0e3e47bc03f831ebe9b759d027e | required
# // Return: Molecular entity record with components, small molecules, conjugates and nucleic acids
# // Ecosystem: Molecular entity:molecular entity
# // Example: python3.12 biorels_api.py  get_molecular_entity -MOLECULAR_ENTITY_HASH '962fb0e3e47bc03f831ebe9b759d027e'
# // $[/API]
def get_molecular_entity(MOLECULAR_ENTITY_HASH):
	query=f""" SELECT * FROM molecular_entity WHERE molecular_entity_hash='{MOLECULAR_ENTITY_HASH}'"""
	data= run_query(query)
	for line in data:
		query=f""" SELECT mc.molecular_component_id, molecular_component_hash, molecular_component_structure_hash, molecular_component_structure, components, molar_fraction 
		FROM molecular_component mc, molecular_entity_component_map mecm
		WHERE mc.molecular_component_id = mecm.molecular_component_id
		AND mecm.molecular_entity_id={line['molecular_entity_id']}"""
		line['COMPONENTS']=run_query(query)
		for comp in line['COMPONENTS']:
			query=f""" SELECT md5_hash, molar_fraction,compound_type
			 FROM molecular_Component_sm_map cs,sm_entry se
			WHERE se.sm_entry_id = cs.sm_entry_id
			AND molecular_component_id={comp['molecular_component_id']}"""
			res2=run_query(query)
			if res2!=[]:
				comp['SM']=[]
			for line2 in res2:
				sm_dt=get_small_molecule(line2['md5_hash'],False)
				
				sm_dt[0]['molar_fraction']=line2['molar_fraction']
				sm_dt[0]['compound_type']=line2['compound_type']
				comp['SM'].append(sm_dt)
			query=f""" SELECT * FROM conjugate_entry ce, molecular_component_conj_map cm
			WHERE ce.conjugate_entry_id = cm.conjugate_entry_id
			AND cm.molecular_component_id={comp['molecular_component_id']}"""
			if res2!=[]:
				comp['CONJUGATE']=[]
			res2=run_query(query)
			for line2 in res2:
				comp['CONJUGATE'].append(line2)
			query=f""" SELECT helm_hash FROM molecular_component_na_map mcna, nucleic_acid_seq nas
			WHERE mcna.nucleic_acid_seq_id = nas.nucleic_acid_seq_id
			AND mcna.molecular_component_id={comp['molecular_component_id']}"""
			res2=run_query(query)
			if res2!=[]:
				comp['NA']=[]
			for line2 in res2:
				comp['NA'].append(get_nucleic_acid_seq(line2['helm_hash']))
	return data
	



# // $[API]
# // Title: Get nucleic acid sequence
# // Function: get_nucleic_acid_seq
# // Description: Get the nucleic acid sequence by using its hash
# // Parameter: HELM_HASH | Nucleic acid HELM hash | string | 962fb0e3e47bc03f831ebe9b759d027e | required
# // Return: Nucleic acid sequence record with modifications
# // Ecosystem: Molecular entity:nucleic acid
# // Example: python3.12 biorels_api.py  get_nucleic_acid_seq -HELM_HASH '962fb0e3e47bc03f831ebe9b759d027e'
# // $[/API]
def get_nucleic_acid_seq(HELM_HASH):
	query=f""" SELECT * FROM nucleic_acid_seq WHERE helm_hash='{HELM_HASH}'"""
	data= run_query(query)
	for line in data:
		if line['mod_pattern_id']!=None:
			query=f"""SELECT * FROM mod_pattern mp, mod_pattern_pos mpp
			where mp.mod_pattern_id = mpp.mod_pattern_id
			mp.mod_pattern_id={line['mod_pattern_id']}"""
			line['MOD_PATTERN']=run_query(query)
	return data






# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////  DISEASE/ANATOMY ECOSYSTEM  //////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: Get disease information
# // Function: get_disease_information
# // Description: Get the disease information by using its tag
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Return: Disease record with synonyms and external database references
# // Ecosystem: Disease_anatomy:disease
# // Example: python3.12 biorels_api.py get_disease_information -TAG 'MONDO_0005087'
# // $[/API]
def get_disease_information(TAG):
	query=f"""
	SELECT * FROM disease_entry WHERE disease_tag='{TAG}'
	"""
	data= run_query(query)
	if data==[]:
		return []
	for line in data:
		line['SYNONYMS']=run_query(f"SELECT syn_type,syn_value,source_name FROM disease_syn ds,source s WHERE s.source_id = ds.source_id AND disease_entry_id={line['disease_entry_id']}")
		line['EXT_DB']=run_query(f"SELECT disease_extdb, source_name FROM disease_extdb de, source s WHERE de.source_id = s.source_id AND disease_entry_id={line['disease_entry_id']}")
		line['CHILDREN']=get_child_disease(TAG)
		line['PARENTS']=get_parent_disease(TAG)
	return data


# // $[API]
# // Title: Search disease by name
# // Function: search_disease_by_name
# // Description: Search for a disease by using its name
# // Parameter: NAME | Disease name | string | Cancer | required
# // Return: Disease record
# // Ecosystem: Disease_anatomy:disease
# // Example: python3.12 biorels_api.py search_disease_by_name -NAME 'Cancer'
# // $[/API]
def search_disease_by_name(NAME):
	query=f"""
	SELECT * FROM disease_entry WHERE LOWER(disease_name)=LOWER('{NAME}')
	"""
	data= run_query(query)
	if data!=[]:
		return data
	query=f"""
	SELECT * FROM disease_syn ds, disease_entry de WHERE ds.disease_entry_id = de.disease_entry_id AND LOWER(syn_value)=LOWER('{NAME}')
	"""
	data= run_query(query)
	if data!=[]:
		return data
	query=f"""
	SELECT * FROM disease_entry WHERE disease_name LIKE '%{NAME}%'
	"""
	data= run_query(query)
	if data!=[]:
		return data
	query=f"""
	SELECT * FROM disease_syn ds, disease_entry de WHERE ds.disease_entry_id = de.disease_entry_id AND syn_value LIKE '%{NAME}%'
	"""
	data= run_query(query)
	if data!=[]:
		return data
	tab=NAME.split(" ")
	list={}
	for rec in tab:
		data=run_query(f"SELECT disease_entry_id FROM disease_entry WHERE disease_name LIKE '%{rec}%'")
		for line in data:
			if line['disease_entry_id'] not in list:
				list[line['disease_entry_id']]=0
			list[line['disease_entry_id']]+=1
	max=0
	for line in list:
		if list[line]>max:
			max=list[line]
	tab=[]
	for line in list:
		if list[line]==max:
			tab.append(line)
	if tab==[]:
		return []
	query=f"""
	SELECT * FROM disease_entry WHERE disease_entry_id IN ({','.join(tab)})
	"""
	return run_query(query)




# // $[API]
# // Title: Search disease by tag
# // Function: search_disease_by_tag
# // Description: Search for a disease by using its tag
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Return: Disease record
# // Ecosystem: Disease_anatomy:disease
# // Example: python3.12 biorels_api.py search_disease_by_tag -TAG 'MONDO_0005087'
# // $[/API]
def search_disease_by_tag(TAG):
	query=f"""
	SELECT * FROM disease_entry WHERE disease_tag='{TAG}'
	"""
	return run_query(query)


# // $[API]
# // Title: Search disease by synonym
# // Function: search_disease_by_synonym
# // Description: Search for a disease by using its synonym
# // Parameter: SYNONYM | Disease synonym | string | Cancer | required
# // Return: Disease record
# // Ecosystem: Disease_anatomy:disease
# // Example: python3.12 biorels_api.py search_disease_by_synonym -SYNONYM 'Cancer'
# // $[/API]
def search_disease_by_synonym(SYNONYM):
	query=f"""
	SELECT * FROM disease_entry de, disease_syn ds
	WHERE ds.disease_entry_id = de.disease_entry_id
	 AND syn_value LIKE '%{SYNONYM}%'
	"""
	return run_query(query)



# // $[API]
# // Title: Search disease by identifier
# // Function: search_disease_by_identifier
# // Description: Search for a disease by using its identifier
# // Parameter: ID | Disease identifier | string | MONDO_0005087 | required
# // Parameter: SOURCE | Source of the identifier | string | MONDO | optional | Default: None
# // Return: Disease record
# // Ecosystem: Disease_anatomy:disease
# // Example: python3.12 biorels_api.py search_disease_by_identifier -ID J45 -SOURCE  ICD10CM
# // $[/API]
def search_disease_by_identifier(ID,SOURCE=None):
	query=f"""
	SELECT * FROM disease_entry de, disease_extdb ds,source s
	WHERE ds.disease_entry_id = de.disease_entry_id
	AND ds.source_id = s.source_id
	 AND ((ds.disease_extdb = '{ID}'
	"""
	if SOURCE!=None:
		query+=f" AND LOWER(s.source_name)=LOWER('{SOURCE}')"
	query+=") OR disease_tag='{ID}')"
	return run_query(query)



# // $[API]
# // Title: Get all diseases that are children of a given disease
# // Function: get_child_disease
# // Description: Get all diseases that are children of a given disease
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: MAX_DEPTH | Maximum depth of child diseases | int | 1 | optional | Default: 1
# // Return: List of disease records that are children of the given disease
# // Ecosystem: Disease_anatomy:disease
# // Example: python3.12 biorels_api.py get_child_disease -TAG 'MONDO_0005087'
# // $[/API]
def get_child_disease(TAG,MAX_DEPTH=1):
	query=f"""
	SELECT dh2.disease_level, de2.disease_tag, de2.disease_name, de2.disease_definition, dh2.disease_level_left, dh2.disease_level_Right, de2.disease_entry_id
	FROM disease_entry de,
	disease_hierarchy dh1, 
	disease_hierarchy dh2,
	disease_entry de2
	WHERE 
	de.disease_entry_id = dh1.disease_entry_id
	AND dh1.disease_level_left < dh2.disease_level_left
	AND dh1.disease_level_right > dh2.disease_level_right
	AND de2.disease_entry_id = dh2.disease_entry_id
	AND de.disease_tag='{TAG}'
	AND dh2.disease_level <= dh1.disease_level+{MAX_DEPTH}
	ORDER BY dh2.disease_level ASC
	"""
	return run_query(query)



# // $[API]
# // Title: Get all diseases that are parent of a given disease
# // Function: get_parent_disease
# // Description: Get all diseases that are parent of a given disease
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: MAX_DEPTH | Maximum depth of parent diseases | int | 1 | optional | Default: 15
# // Return: List of disease records that are parent of the given disease
# // Ecosystem: Disease_anatomy:disease
# // Example: python3.12 biorels_api.py get_parent_disease -TAG 'MONDO_0005087'
# // $[/API]
def get_parent_disease(TAG,MAX_DEPTH=15):
	query=f"""
	SELECT dh2.disease_level, de2.disease_tag, de2.disease_name, de2.disease_definition, dh2.disease_level_left, dh2.disease_level_Right
	FROM disease_entry de,
	disease_hierarchy dh1, 
	disease_hierarchy dh2,
	disease_entry de2
	WHERE 
	de.disease_entry_id = dh1.disease_entry_id
	AND dh1.disease_level_left > dh2.disease_level_left
	AND dh1.disease_level_right < dh2.disease_level_right
	AND de2.disease_entry_id = dh2.disease_entry_id
	AND de.disease_tag='{TAG}'
	AND dh2.disease_level >= dh1.disease_level-{MAX_DEPTH}
	ORDER BY dh2.disease_level ASC
	"""
	return run_query(query)





# // $[API]
# // Title: get disease information
# // Function: get_disease_info
# // Description: Get the disease information by using its tag
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: SOURCE_NAME | Source of the disease information | string | MONDO | optional
# // Return: Textual information about the disease
# // Ecosystem: Disease_anatomy:disease
# // Example: python3.12 biorels_api.py get_disease_info -TAG 'MONDO_0005087'
# // $[/API]
def get_disease_info(TAG,SOURCE_NAME=''):
	query=f"""SELECT di.*, source_name
	FROM disease_entry de, disease_info di, source s
	WHERE de.disease_entry_Id = di.disease_entry_id 
	AND di.source_id = s.source_id
	AND de.disease_tag='{TAG}'"""
	if (SOURCE_NAME!=''):
		query+=f" AND LOWER(s.source_name)=LOWER('{SOURCE_NAME}')";
	return run_query(query)




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////////  ANATOMY  ////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get anatomy information
# // Function: get_anatomy_information
# // Description: Get the anatomy information by using its tag
# // Parameter: TAG | anatomy tag | string | UBERON_0000955 | required
# // Parameter: COMPLETE | True if all information is requested | boolean | false | optional
# // Return: anatomy record with synonyms and external database references
# // Ecosystem: Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py  get_anatomy_information -TAG 'UBERON_0000955'
# // $[/API]
def get_anatomy_information(TAG,COMPLETE=False):
	query=f"""
	SELECT * FROM anatomy_entry WHERE anatomy_tag='{TAG}'
	"""
	data= run_query(query)
	if data==[]:
		return []
	if not COMPLETE:
		return data
	for line in data:
		line['SYNONYMS']=run_query(f"SELECT syn_type,syn_value,source_name FROM anatomy_syn ds,source s WHERE s.source_id = ds.source_id AND anatomy_entry_id={line['anatomy_entry_id']}")
		line['EXT_DB']=run_query(f"SELECT anatomy_extdb, source_name FROM anatomy_extdb de, source s WHERE de.source_id = s.source_id AND anatomy_entry_id={line['anatomy_entry_id']}")
		line['CHILDREN']=get_child_anatomy(TAG)
		line['PARENTS']=get_parent_anatomy(TAG,10)
	return data




# // $[API]
# // Title: Search anatomy by name
# // Function: search_anatomy_by_name
# // Description: Search for a anatomy by using its name
# // Parameter: NAME | anatomy name | string | Brain | required
# // Parameter: IS_EXACT | True if exact match is required | boolean | true | optional | Default: true
# // Return: anatomy record
# // Ecosystem: Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py  search_anatomy_by_name -NAME 'Brain'
# // $[/API]
def search_anatomy_by_name(NAME, IS_EXACT=True):
	query=f"""
	SELECT * FROM anatomy_entry WHERE {'LOWER(anatomy_name)=LOWER(\''+NAME+'\')' if IS_EXACT else 'LOWER(anatomy_name) LIKE \'%'+NAME.lower()+'%\''}
	"""
	return run_query(query)



# // $[API]
# // Title: Search anatomy by tag
# // Function: search_anatomy_by_tag
# // Description: Search for a anatomy by using its tag
# // Parameter: TAG | anatomy tag | string | UBERON_0000955 | required
# // Return: anatomy record
# // Ecosystem: Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py  search_anatomy_by_tag -TAG 'UBERON_0000955'
# // $[/API]
def search_anatomy_by_tag(TAG):
	query=f"""
	SELECT * FROM anatomy_entry WHERE anatomy_tag='{TAG}'
	"""
	return run_query(query)


# // $[API]
# // Title: Search anatomy by synonym
# // Function: search_anatomy_by_synonym
# // Description: Search for a anatomy by using its synonym
# // Parameter: SYNONYM | anatomy synonym | string | Brain | required
# // Return: anatomy record
# // Ecosystem: Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py  search_anatomy_by_synonym -SYNONYM 'Brain'
# // $[/API]
def search_anatomy_by_synonym(SYNONYM):
	query=f"""
	SELECT * FROM anatomy_entry de, anatomy_syn ds
	WHERE ds.anatomy_entry_id = de.anatomy_entry_id
	 AND LOWER(syn_value) LIKE LOWER('%{SYNONYM}%')
	"""
	return run_query(query)

# // $[API]
# // Title: Search anatomy by identifier
# // Function: search_anatomy_by_identifier
# // Description: Search for a anatomy by using its identifier
# // Parameter: ID | anatomy identifier | string | 0000955 | required
# // Parameter: SOURCE | Source of the identifier | string | UBERON | optional | Default: None
# // Return: anatomy record
# // Ecosystem: Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py  search_anatomy_by_identifier -ID 0000955 -SOURCE UBERON
# // $[/API]
def search_anatomy_by_identifier(ID,SOURCE=None):
	query=f"""
	SELECT * FROM anatomy_entry de, anatomy_extdb ds,source s
	WHERE ds.anatomy_entry_id = de.anatomy_entry_id
	AND ds.source_id = s.source_id
	 AND ds.anatomy_extdb = '{ID}'
	"""
	if SOURCE!=None:
		query+=f" AND LOWER(s.source_name)=LOWER('{SOURCE}')"
	return run_query(query)




# // $[API]
# // Title: Get all anatomys that are children of a given anatomy
# // Function: get_child_anatomy
# // Description: Get all anatomys that are children of a given anatomy
# // Parameter: TAG | anatomy tag | string | UBERON_0000955 | required
# // Parameter: MAX_DEPTH | Maximum depth of child anatomys | int | 1 | optional | Default: 1
# // Return: List of anatomy records that are children of the given anatomy
# // Ecosystem: Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py  get_child_anatomy -TAG UBERON_0000955
# // $[/API]
def get_child_anatomy(TAG,MAX_DEPTH=1):
	query=f"""
	SELECT dh2.anatomy_level, de2.anatomy_tag, de2.anatomy_name, de2.anatomy_definition, dh2.anatomy_level_left, dh2.anatomy_level_Right
	FROM anatomy_entry de,
	anatomy_hierarchy dh1, 
	anatomy_hierarchy dh2,
	anatomy_entry de2
	WHERE 
	de.anatomy_entry_id = dh1.anatomy_entry_id
	AND dh1.anatomy_level_left < dh2.anatomy_level_left
	AND dh1.anatomy_level_right > dh2.anatomy_level_right
	AND de2.anatomy_entry_id = dh2.anatomy_entry_id
	AND de.anatomy_tag='{TAG}'
	AND dh2.anatomy_level <= dh1.anatomy_level+{MAX_DEPTH}
	ORDER BY dh2.anatomy_level ASC
	"""
	return run_query(query)


# // $[API]
# // Title: Get all anatomys that are parent of a given anatomy
# // Function: get_parent_anatomy
# // Description: Get all anatomys that are parent of a given anatomy
# // Parameter: TAG | anatomy tag | string | UBERON_0000955 | required
# // Parameter: MAX_DEPTH | Maximum depth of parent anatomys | int | 1 | optional | Default: 1
# // Return: List of anatomy records that are parent of the given anatomy
# // Ecosystem: Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py  get_parent_anatomy -TAG UBERON_0000955
# // $[/API]
def get_parent_anatomy(TAG,MAX_DEPTH=1):
	query=f"""
	SELECT dh2.anatomy_level, de2.anatomy_tag, de2.anatomy_name, de2.anatomy_definition, dh2.anatomy_level_left, dh2.anatomy_level_Right
	FROM anatomy_entry de,
	anatomy_hierarchy dh1, 
	anatomy_hierarchy dh2,
	anatomy_entry de2
	WHERE 
	de.anatomy_entry_id = dh1.anatomy_entry_id
	AND dh1.anatomy_level_left > dh2.anatomy_level_left
	AND dh1.anatomy_level_right < dh2.anatomy_level_right
	AND de2.anatomy_entry_id = dh2.anatomy_entry_id
	AND de.anatomy_tag='{TAG}'
	AND dh2.anatomy_level >= dh1.anatomy_level-{MAX_DEPTH}
	ORDER BY dh2.anatomy_level ASC
	"""
	return run_query(query)




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////  CELL LINE  ///////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: search cell line by disease
# // Function: search_cell_line_by_disease
# // Description: Search for a cell line by a disease or its child diseases
# // Parameter: DISEASE_TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: INCLUDE_CHILD_DISEASE | True if child diseases should be included | boolean | false | optional
# // Return: Cell line record
# // Ecosystem: Disease_anatomy:cell line|disease
# // Example: python3.12 biorels_api.py search_cell_line_by_disease -DISEASE_TAG 'MONDO_0005087'
# // Example: python3.12 biorels_api.py search_cell_line_by_disease -DISEASE_TAG 'MONDO_0005087' -INCLUDE_CHILD_DISEASE true
# // $[/API]
def search_cell_line_by_disease(DISEASE_TAG,INCLUDE_CHILD_DISEASE=False):
	query=f"""
	SELECT * FROM cell_entry ce, cell_disease cd, disease_entry de
	WHERE ce.cell_entry_id = cd.cell_entry_id
	AND de.disease_entry_id = cd.disease_entry_id
	"""
	if not INCLUDE_CHILD_DISEASE:
		query+= f" AND de.disease_tag='{DISEASE_TAG}'"
	else:
		query+= f""" AND de.disease_entry_id IN (
			SELECT de2.disease_entry_id 
			FROM disease_hierarchy dh1, disease_Entry de1,
				 disease_hierarchy dh2, disease_entry de2
			WHERE de1.disease_entry_id = dh1.disease_entry_id
			AND de2.disease_entry_id = dh2.disease_entry_id
			AND dh1.disease_level_left <= dh2.disease_level_left
			AND dh1.disease_level_right >= dh2.disease_level_right
			AND de1.disease_tag='{DISEASE_TAG}')"""
	
	return run_query(query)




# // $[API]
# // Title: get all cell line information
# // Function: get_cell_info
# // Description: Get all information about a cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Parameter: COMPLETE | True if complete information is required | boolean | false | optional
# // Return: Cell line record
# // Ecosystem: Disease_anatomy:cell line|disease|anatomy|taxon
# // Example: python3.12 biorels_api.py get_cell_info -ACC 'CVCL_B6YM'
# // $[/API]
def get_cell_info(ACC,COMPLETE=False):
	res=run_query(f"SELECT * FROM cell_entry where cell_acc='{ACC}'")
	if res==[]:
		return []
	data=[]
	for line in res:
		line['SYNONYMS']=get_cell_line_synonyms(ACC)
		line['TAXONOMY']=get_cell_line_taxon(ACC)
		line['DISEASE']=get_cell_line_disease(ACC,COMPLETE)
		line['TISSUE']=get_cell_line_tissue(ACC,COMPLETE)
		line['PATENT']=get_cell_line_patent(ACC)
		data.append(line)
	return data


# // $[API]
# // Title: get cell line synonyms
# // Function: get_cell_line_synonyms
# // Description: Get all synonyms of a cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Return: List of synonyms
# // Ecosystem: Disease_anatomy:cell line
# // Example: python3.12 biorels_api.py get_cell_line_synonyms -ACC 'CVCL_B6YM'
# // $[/API]
def get_cell_line_synonyms(ACC):
	res=run_query(f"""SELECT cell_syn_name, source_name 
			   FROM cell_syn cs, source s 
			   WHERE s.source_id = cs.source_id 
			   AND cell_entry_id IN (SELECT cell_entry_id FROM cell_entry WHERE cell_acc='{ACC}')""")
	return res


# // $[API]
# // Title: get disease information of a cell line
# // Function: get_cell_line_disease
# // Description: Get all diseases of a cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Parameter: COMPLETE | True if extended disease information is requested | boolean | false | optional
# // Return: List of diseases
# // Ecosystem: Disease_anatomy:cell line|disease
# // Example: python3.12 biorels_api.py get_cell_line_disease -ACC 'CVCL_B6YM'
# // Example: python3.12 biorels_api.py get_cell_line_disease -ACC 'CVCL_B6YM' -COMPLETE true
# // $[/API]
def get_cell_line_disease(ACC,COMPLETE=False):
	res=run_query(f"""SELECT disease_tag, disease_name, source_name 
	FROM disease_entry de, cell_disease cd, source s  
	WHERE s.source_id = cd.source_id 
	AND de.disease_entry_id = cd.disease_entry_id 
	AND cell_entry_id IN (SELECT cell_entry_id FROM cell_entry WHERE cell_acc='{ACC}')""")
	if COMPLETE:
		for line in res:
			line['INFO']=get_disease_information(line['disease_tag'])
	return res


# // $[API]
# // Title: get tissue information of a cell line
# // Function: get_cell_line_tissue
# // Description: Get all tissues of a cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Parameter: COMPLETE | True if extended tissue information is requested | boolean | false | optional
# // Return: List of tissues
# // Ecosystem: Disease_anatomy:cell line|anatomy
# // Example: python3.12 biorels_api.py get_cell_line_tissue -ACC 'CVCL_B6YM'
# // Example: python3.12 biorels_api.py get_cell_line_tissue -ACC 'CVCL_B6YM' -COMPLETE true
# // $[/API]
def get_cell_line_tissue(ACC,COMPLETE=False):
	res=run_query(f"""SELECT anatomy_tag, anatomy_name 
	FROM anatomy_entry te, cell_tissue ct
	WHERE te.anatomy_entry_id = ct.anatomy_entry_id 
	AND cell_tissue_id IN (SELECT cell_tissue_id FROM cell_entry WHERE cell_acc='{ACC}')""")
	if COMPLETE:
		for line in res:
			line['INFO']=get_tissue_information(line['anatomy_tag'])
	return res



# // $[API]
# // Title: get organism information for a given cell line
# // Function: get_cell_line_taxon
# // Description: Get the organism information for a given cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Return: Organism information
# // Ecosystem: Disease_anatomy:cell line|Genomics:taxon
# // Example: python3.12 biorels_api.py get_cell_line_taxon -ACC 'CVCL_B6YM'
# // $[/API]
def get_cell_line_taxon(ACC):
	res=run_query(f"""SELECT tax_id, scientific_name,source_name 
	FROM taxon t, cell_Taxon_map ctm, source s 
	WHERE s.sourcE_id = ctm.source_id 
	AND t.taxon_id = ctm.taxon_id 
	AND cell_entry_id IN (SELECT cell_entry_id FROM cell_entry WHERE cell_acc='{ACC}')""")
	return res



# // $[API]
# // Title: get patent information for a given cell line
# // Function: get_cell_line_patent
# // Description: Get the patent information for a given cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Return: Patent information
# // Ecosystem: Disease_anatomy:cell line|Scientific_community:patent
# // Example: python3.12 biorels_api.py get_cell_line_patent -ACC 'CVCL_B6YM'
# // $[/API]
def get_cell_line_patent(ACC):
	res=run_query(f"""SELECT source_name, patent_application 
	FROM patent_entry p, cell_patent_map cp, source s 
	WHERE s.source_id = cp.source_id 
	AND p.patent_entry_id = cp.patent_entry_id 
	AND cell_entry_id IN (SELECT cell_entry_id FROM cell_entry WHERE cell_acc='{ACC}')""")
	return res



# // $[API]
# // Title: list cell line types
# // Function: list_cell_line_type
# // Description: List the different types of cell lines
# // Return: List of cell line types
# // Parameter: Dummy | Dummy parameter | string | optional | Default: None
# // Ecosystem: Disease_anatomy:cell line
# // Example: python3.12 biorels_api.py list_cell_line_type
# // $[/API]
def list_cell_line_type(Dummy=None):
	res=run_query(f"""SELECT count(*) n_cell_line, cell_type 
	FROM cell_entry
	group by cell_type 
	order by n_cell_line DESC""")
	return res


# // $[API]
# // Title: Count the number of cell lines per organism
# // Function: list_cell_line_taxon
# // Description: Count the number of cell lines per organism
# // Return: Count, scientific name, tax id
# // Parameter: Dummy | Dummy parameter | string | optional | Default: None
# // Ecosystem: Disease_anatomy:cell line;Genomics:taxon
# // Example: python3.12 biorels_api.py list_cell_line_taxon
# // $[/API]
def list_cell_line_taxon(Dummy=None):
	res=run_query(f"""SELECT count(*) n_cell_line, scientific_name, tax_id 
	FROM taxon t, cell_Taxon_map ctm 
	WHERE t.taxon_id = ctm.taxon_id group by scientific_name ,tax_id order by n_cell_line DESC""")
	return res


# // $[API]
# // Title: Count the number of cell lines per tissue
# // Function: list_cell_line_tissue
# // Description: Count the number of cell lines per tissue
# // Return: Count, tissue name, tissue tag
# // Ecosystem: Disease_anatomy:cell line|anatomy
# // Parameter: Dummy | Dummy parameter | string | optional | Default: None
# // Example: python3.12 biorels_api.py list_cell_line_tissue
# // $[/API]
def list_cell_line_tissue(Dummy:None):
	res=run_query(f"""SELECT count(*) n_cell_line, anatomy_name, anatomy_tag 
	FROM anatomy_entry te, cell_tissue ct 
	WHERE te.anatomy_entry_id = ct.anatomy_entry_id 
	group by anatomy_name, anatomy_tag
	order by n_cell_line DESC""")
	return res


# // $[API]
# // Title: Search cell line by different parameters
# // Function: search_cell_line
# // Description: Search for a cell line by using different parameters. Use $PARAMS=array('NAME'=>array(),'SYN'=>array(),'AC'=>array(),'TAX_ID'=>array(),'DISEASE'=>array(),'CELL_TYPE'=>array());
# // Parameter: PARAMS | List of parameters | multi_array | multi | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Cell line record
# // Ecosystem: Disease_anatomy:cell line
# // Example: python3.12 biorels_api.py search_cell_line -PARAMS "CELL_TYPE=Cancer cell line"
# // $[/API]
def search_cell_line(PARAMS,COMPLETE=false):
	query="SELECT * FROM cell_entry ce";
	WHERE=[]
	if 'NAME' in PARAMS and PARAMS['NAME']!=[]:
		WHERE.append(f"(cell_name IN ('{"','".join(PARAMS['NAME'])}'))")
	if 'SYN' in PARAMS and PARAMS['SYN']!=[]:
		WHERE.append(f"(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_syn WHERE cell_syn_name IN ('{"','".join(PARAMS['SYN'])}')))")
	if 'AC' in PARAMS and PARAMS['AC']!=[]:
		WHERE.append(f"(ce.cell_acc IN  ('{','.join(PARAMS['AC'])}'))")
	if 'TAX_ID' in PARAMS and PARAMS['TAX_ID']!=[]:
		WHERE.append(f"(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_Taxon_map ctm, taxon t WHERE t.taxon_id= ctm.taxon_id AND tax_id IN ('{','.join(PARAMS['TAX_ID'])}')))")
	if 'DISEASE' in PARAMS and PARAMS['DISEASE']!=[]:
		WHERE.append(f"(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_disease WHERE disease_entry_id IN (SELECT disease_entry_id FROM disease_entry WHERE disease_tag IN ('{','.join(PARAMS['DISEASE'])}'))))")
	if 'CELL_TYPE' in PARAMS and PARAMS['CELL_TYPE']!=[]:
		WHERE.append(f"(ce.cell_type IN ('{','.join(PARAMS['CELL_TYPE'])}'))")
	if WHERE!=[]:
		query+=f" WHERE {' AND '.join(WHERE)}"
	else :
		return []
	
	res= run_query(query)
	if res==[]:
		query="SELECT * FROM cell_entry ce";
		WHERE=[]
		if 'NAME' in PARAMS and PARAMS['NAME']!=[]:
			s="%') OR LOWER(cell_name) LIKE LOWER('%"
			WHERE.append(f"(LOWER(cell_name) LIKE LOWER('%{s.join(PARAMS['NAME'])}%'))")
		if 'SYN' in PARAMS and PARAMS['SYN']!=[]:
			s="%') OR LOWER(cell_syn_name) LIKE LOWER('%"
			WHERE.append(f"(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_syn WHERE LOWER(cell_syn_name) LIKE LOWER('%{s.join(PARAMS['SYN'])}%')))")
		if 'AC' in PARAMS and PARAMS['AC']!=[]:
			WHERE.append(f"(ce.cell_acc IN  ('{','.join(PARAMS['AC'])}'))")
		if 'TAX_ID' in PARAMS and PARAMS['TAX_ID']!=[]:
			WHERE.append(f"(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_Taxon_map ctm, taxon t WHERE t.taxon_id= ctm.taxon_id AND tax_id IN ('{','.join(PARAMS['TAX_ID'])}')))")
		if 'DISEASE' in PARAMS and PARAMS['DISEASE']!=[]:
			WHERE.append(f"(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_disease WHERE disease_entry_id IN (SELECT disease_entry_id FROM disease_entry WHERE disease_tag IN ('{','.join(PARAMS['DISEASE'])}'))))")
		if 'CELL_TYPE' in PARAMS and PARAMS['CELL_TYPE']!=[]:
			WHERE.append(f"(ce.cell_type IN ('{','.join(PARAMS['CELL_TYPE'])}'))")
		if WHERE!=[]:
			query+=f" WHERE {' AND '.join(WHERE)}"
		else :
			return []
		res= run_query(query)
	data=[]
	for line in res:
		data.append(get_cell_info(line['cell_acc'],COMPLETE))
	return data




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////////// RNA EXPRESSION ///////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: list RNA Expression samples
# // Function: list_rna_expression_samples
# // Description: List all RNA expression samples with their source, organ, and tissue information
# // Ecosystem: RNA expression
# // Parameter: Dummy | Dummy parameter | string | optional | Default: None
# // Example: python3.12 biorels_api.py  list_rna_expression_samples
# // $[/API]
def list_rna_expression_samples(Dummy=None):
	query=f"""
		SELECT rs.rna_sample_id, sample_id, source_name,organ_name,tissue_name,anatomy_tag,anatomy_name,anatomy_definition 
		FROM 
			RNA_SAMPLE RS,
			RNA_SOURCE RO, 
			RNA_TISSUE RT 
			LEFT JOIN ANATOMY_ENTRY AE ON AE.ANATOMY_ENTRY_ID = RT.ANATOMY_ENTRY_ID 
		WHERE  RO.RNA_SOURCE_ID = RS.RNA_SOURCE_ID 
		AND RT.RNA_TISSUE_ID = RS.RNA_TISSUE_ID
		"""
	return run_query(query)



# // $[API]
# // Title: Search RNA Expression samples
# // Function: search_rna_expression_samples
# // Description: Search for RNA expression samples by using their source, organ, tissue, sample ID, and anatomy tag
# // Parameter: SOURCE_NAME | Source name | array | GTEX | optional | Default: None
# // Parameter: ORGAN | Organ name | array | Brain | optional | Default: None
# // Parameter: TISSUE | Tissue name | array | Cerebellum | optional | Default: None
# // Parameter: SAMPLE_ID | Sample ID | array | GTEX-1117F | optional | Default: None
# // Parameter: ANATOMY_TAG | Anatomy tag | array | UBERON_0002037 | optional | Default: None
# // Ecosystem: RNA expression
# // Example: python3.12 biorels_api.py  search_rna_expression_samples -SOURCE_NAME 'GTEX' -ORGAN 'Brain' -TISSUE 'Cerebellum' -SAMPLE_ID 'GTEX-1117F' -ANATOMY_TAG 'UBERON_0002037'
# // $[/API]
def search_rna_expression_samples(SOURCE_NAME=None,ORGAN=None, TISSUE=None, SAMPLE_ID=None, ANATOMY_TAG=None):
	query=f"""
		SELECT rs.rna_sample_id, sample_id, source_name,organ_name,tissue_name,anatomy_tag,anatomy_name,anatomy_definition 
		FROM 
			RNA_SAMPLE RS,
			RNA_SOURCE RO, 
			RNA_TISSUE RT 
			LEFT JOIN ANATOMY_ENTRY AE ON AE.ANATOMY_ENTRY_ID = RT.ANATOMY_ENTRY_ID 
		WHERE  RO.RNA_SOURCE_ID = RS.RNA_SOURCE_ID 
		AND RT.RNA_TISSUE_ID = RS.RNA_TISSUE_ID
	"""
	if SOURCE_NAME != None:
		query += f" AND source_name IN ('{"','".join(SOURCE_NAME)}')"
	if ORGAN != None:
		query += f" AND organ_name IN ('{"','".join(ORGAN)}')"
	if TISSUE != None:
		query += f" AND tissue_name IN ('{"','".join(TISSUE)}')"
	if SAMPLE_ID != None:
		query += f" AND sample_id IN ('{"','".join(SAMPLE_ID)}')"
	if ANATOMY_TAG != None:
		query += f" AND anatomy_tag IN ('{"','".join(ANATOMY_TAG)}')"
	return run_query(query)



# // $[API]
# // Title: Get RNA Expression samples for a transcript and a list of samples
# // Function: get_transcript_expression
# // Description: Get the expression of a transcript in a list of samples
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | ENST00000379031 | required
# // Parameter: SAMPLE_IDS | List of sample IDs | array | GTEX-ZVZQ-0011-R11a-SM-51MS6,GTEX-ZVT3-0011-R11b-SM-57WBI | optional | Default: None
# // Return: TPM, sample ID
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:transcript
# // Example: python3.12 biorels_api.py  get_transcript_expression -TRANSCRIPT_NAME 'ENST00000379031' -SAMPLE_IDS GTEX-ZVZQ-0011-R11a-SM-51MS6,GTEX-ZVT3-0011-R11b-SM-57WBI
# // $[/API]
def get_transcript_expression(TRANSCRIPT_NAME, SAMPLE_IDS=None):
	LIST_SAMPLES = []
	if SAMPLE_IDS != None:
		for S in SAMPLE_IDS:
			LIST_SAMPLES.append(f"'{S}'")
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
		
	query=f"""
		SELECT TPM, SAMPLE_ID
		FROM TRANSCRIPT T, RNA_TRANSCRIPT RT, RNA_SAMPLE RS
		WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
		AND T.TRANSCRIPT_ID = RT.TRANSCRIPT_ID
		AND TRANSCRIPT_NAME='{TRANSCRIPT_NAME}'
	"""
	if pos != -1:
		query += f" AND TRANSCRIPT_VERSION='{TRANSCRIPT_VERSION}'"
	if LIST_SAMPLES != []:
		query += f" AND RS.SAMPLE_ID IN ({','.join(LIST_SAMPLES)})"
	return run_query(query)




# // $[API]
# // Title: Get RNA Expression for all genes for a given sample
# // Function: get_sample_rna_expression
# // Description: Get the expression of all genes in a given sample
# // Parameter: SAMPLE_ID | Sample ID | string | GTEX-ZVZQ-0011-R11a-SM-51MS6 | required
# // Parameter: SOURCE_NAME | Source name | string | GTEX | required
# // Return: TPM, transcript name, transcript version, gene sequence name, gene sequence version, symbol, gene ID
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene|transcript
# // Example: python3.12 biorels_api.py  get_sample_rna_expression -SAMPLE_ID 'GTEX-ZVZQ-0011-R11a-SM-51MS6' -SOURCE_NAME 'GTEX'
# // $[/API]
def get_sample_rna_expression(SAMPLE_ID, SOURCE_NAME):
	query=f"""
		SELECT TPM,TRANSCRIPT_NAME,TRANSCRIPT_VERSION,GENE_SEQ_NAME,GENE_SEQ_VERSION,SYMBOL,GENE_ID
		FROM GENE_SEQ GS LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GS.GN_ENTRY_ID ,
		TRANSCRIPT T, RNA_TRANSCRIPT RT, RNA_SAMPLE RS, RNA_SOURCE RO
		WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
		AND RS.RNA_SOURCE_ID = RO.RNA_SOURCE_ID
		AND  GS.GENE_SEQ_ID= T.GENE_SEQ_ID 
		AND T.TRANSCRIPT_ID = RT.TRANSCRIPT_ID
		AND SAMPLE_ID='{SAMPLE_ID}'
		AND LOWER(SOURCE_NAME)=LOWER('{SOURCE_NAME}')
	"""
	return run_query(query)


# // $[API]
# // Title: Get statistics of RNA expression for a transcript across different tissues
# // Function: get_transcript_rna_expression_stat
# // Description: Get the statistics of RNA expression for a transcript across different tissues
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | ENST00000379031 | required
# // Return: Organ name, tissue name, number of samples, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:transcript
# // Example: python3.12 biorels_api.py  get_transcript_rna_expression_stat -TRANSCRIPT_NAME 'ENST00000379031'
# // $[/API]
def get_transcript_rna_expression_stat(TRANSCRIPT_NAME):
	pos = TRANSCRIPT_NAME.find('.')
	if pos != -1:
		TRANSCRIPT_VERSION = TRANSCRIPT_NAME[pos+1:]
		TRANSCRIPT_NAME = TRANSCRIPT_NAME[:pos]
		if not TRANSCRIPT_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""
		SELECT ORGAN_NAME,TISSUE_NAME,NSAMPLE as N_SAMPLE,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
		FROM   TRANSCRIPT T, RNA_TRANSCRIPT_STAT RGS, RNA_TISSUE RT
		WHERE  RGS.TRANSCRIPT_ID = T.TRANSCRIPT_ID 
		AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID 
		AND TRANSCRIPT_NAME='{TRANSCRIPT_NAME}'
	"""
	if pos != -1:
		query += f" AND TRANSCRIPT_VERSION='{TRANSCRIPT_VERSION}'"
	return run_query(query)





# // $[API]
# // Title: Get statistics of Transcript RNA expression 
# // Function: get_tissue_transcript_rna_expression_stat
# // Description: Get the statistics of RNA expression for all transcript across a set of different tissues/source/organ/anatomy_tag
# // Parameter: ORGAN | Organ name | array | optional | Default: None
# // Parameter: TISSUE | Tissue name | array | optional | Default: None
# // Parameter: ANATOMY_TAG | Anatomy tag | array | optional | Default: None
# // Return: Transcript name, transcript version, organ name, tissue name, number of samples, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:transcript
# // Example: python3.12 biorels_api.py  get_tissue_transcript_rna_expression_stat -ORGAN 'Brain'
# // Example: python3.12 biorels_api.py  get_tissue_transcript_rna_expression_stat  -TISSUE 'Cerebellum,Cortex' 
# // Example: python3.12 biorels_api.py  get_tissue_transcript_rna_expression_stat  -ANATOMY_TAG 'UBERON_0002037' 
# // $[/API]
def get_tissue_transcript_rna_expression_stat(SOURCE_NAME=None,ORGAN=None, TISSUE=None, ANATOMY_TAG=None):
	query=f"""
		SELECT TRANSCRIPT_NAME,TRANSCRIPT_VERSION, ORGAN_NAME,TISSUE_NAME,NSAMPLE as N_SAMPLE,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
		FROM   TRANSCRIPT T, RNA_TRANSCRIPT_STAT RGS, RNA_TISSUE RT
		WHERE  RGS.TRANSCRIPT_ID = T.TRANSCRIPT_ID 
		AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID 
	"""
	if ORGAN != None:
		query += f" AND organ_name IN ('{"','".join(ORGAN)}')"
	if TISSUE != None:
		query += f" AND tissue_name IN ('{"','".join(TISSUE)}')"
	if ANATOMY_TAG != None:
		query += f" AND anatomy_tag IN ('{"','".join(ANATOMY_TAG)}')"
	return run_query(query)





# // $[API]
# // Title: Get RNA Expression samples for a gene and a list of samples
# // Function: get_gene_expression
# // Description: Get the expression of a gene in a list of samples
# // Parameter: GENE_SEQ_NAME | Ensembl Gene Seq | string | ENSG00000223972 | required
# // Parameter: SAMPLE_IDS | List of sample IDs | array | GTEX-ZVZQ-0011-R11a-SM-51MS6,GTEX-ZVT3-0011-R11b-SM-57WBI | optional | Default: None
# // Return: TPM, sample ID
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: python3.12 biorels_api.py get_gene_expression -GENE_SEQ_NAME 'ENSG00000223972' -SAMPLE_IDS GTEX-ZVZQ-0011-R11a-SM-51MS6,GTEX-ZVT3-0011-R11b-SM-57WBI
# // $[/API]
def get_gene_expression(GENE_SEQ_NAME, SAMPLE_IDS=None):
	LIST_SAMPLES = []
	if SAMPLE_IDS != None:
		for S in SAMPLE_IDS:
			LIST_SAMPLES.append(f"'{S}'")
	pos = GENE_SEQ_NAME.find('.')
	if pos != -1:
		GENE_SEQ_VERSION = GENE_SEQ_NAME[pos+1:]
		GENE_SEQ_NAME = GENE_SEQ_NAME[:pos]
		if not GENE_SEQ_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""
		SELECT TPM,SAMPLE_ID
		FROM GENE_SEQ T, RNA_GENE RT, RNA_SAMPLE RS
		WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
		AND T.GENE_SEQ_ID = RT.GENE_SEQ_ID
		AND GENE_SEQ_NAME='{GENE_SEQ_NAME}'
	"""
	if pos != -1:
		query += f" AND GENE_SEQ_VERSION='{GENE_SEQ_VERSION}'"
	if LIST_SAMPLES != []:
		query += f" AND RS.SAMPLE_ID IN ({','.join(LIST_SAMPLES)})"
	return run_query(query)


# // $[API]
# // Title: Get RNA Expression for all genes for a given sample
# // Function: get_sample_gene_rna_expression
# // Description: Get the expression of all genes in a given sample
# // Parameter: SAMPLE_ID | Sample ID | string | GTEX-ZVZQ-0011-R11a-SM-51MS6 | required
# // Parameter: SOURCE_NAME | Source name | string | GTEX | required
# // Return: TPM,  gene sequence name, gene sequence version, symbol, gene ID
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: python3.12 biorels_api.py get_sample_rna_expression -SAMPLE_ID 'GTEX-ZVZQ-0011-R11a-SM-51MS6' -SOURCE_NAME 'GTEX'
# // $[/API]
def get_sample_gene_rna_expression(SAMPLE_ID, SOURCE_NAME):
	query=f"""
		SELECT TPM,GENE_SEQ_NAME,GENE_SEQ_VERSION,SYMBOL,GENE_ID
		FROM GENE_SEQ GS LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GS.GN_ENTRY_ID ,
		RNA_GENE RT, RNA_SAMPLE RS, RNA_SOURCE RO
		WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
		AND RS.RNA_SOURCE_ID = RO.RNA_SOURCE_ID
		AND GS.GENE_SEQ_ID= RT.GENE_SEQ_ID
		AND SAMPLE_ID='{SAMPLE_ID}'
		AND LOWER(SOURCE_NAME)=LOWER('{SOURCE_NAME}')
	"""
	return run_query(query)


# // $[API]
# // Title: Get statistics of RNA expression for a gene across different tissues
# // Function: get_gene_rna_expression_stat
# // Description: Get the statistics of RNA expression for a gene across different tissues
# // Parameter: GENE_SEQ_NAME | Gene sequence name | string | ENSG00000223972 | required
# // Return: Organ name, tissue name, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: python3.12 biorels_api.py get_gene_rna_expression_stat -GENE_SEQ_NAME 'ENSG00000223972'
# // $[/API]
def get_gene_rna_expression_stat(GENE_SEQ_NAME):
	pos = GENE_SEQ_NAME.find('.')
	if pos != -1:
		GENE_SEQ_VERSION = GENE_SEQ_NAME[pos+1:]
		GENE_SEQ_NAME = GENE_SEQ_NAME[:pos]
		if not GENE_SEQ_VERSION.isnumeric():
			raise ValueError("Version number is not numeric")
	query = f"""
		SELECT ORGAN_NAME,TISSUE_NAME,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
		FROM   GENE_SEQ T, RNA_GENE_STAT RGS, RNA_TISSUE RT
		WHERE  RGS.GENE_SEQ_ID = T.GENE_SEQ_ID 
		AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID 
		AND GENE_SEQ_NAME='{GENE_SEQ_NAME}'
	"""
	if pos != -1:
		query += f" AND GENE_SEQ_VERSION='{GENE_SEQ_VERSION}'"
	return run_query(query)



# // $[API]
# // Title: Get statistics of GEne RNA expression 
# // Function: get_tissue_gene_rna_expression_stat
# // Description: Get the statistics of RNA expression for all gene across a set of different tissues/source/organ/anatomy_tag
# // Parameter: ORGAN | Organ name | array | optional | Brain | Default: None
# // Parameter: TISSUE | Tissue name | array | optional | Cerebellum,Cortex | Default: None
# // Parameter: ANATOMY_TAG | Anatomy tag | array | optional | UBERON_0002037 | Default: None
# // Return: gene seq name, gene version, organ name, tissue name, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: python3.12 biorels_api.py get_tissue_gene_rna_expression_stat -ORGAN 'Brain'
# // Example: python3.12 biorels_api.py get_tissue_gene_rna_expression_stat  -TISSUE 'Cerebellum,Cortex' 
# // Example: python3.12 biorels_api.py get_tissue_gene_rna_expression_stat  -ANATOMY_TAG 'UBERON_0002037' 
# // $[/API]
def get_tissue_gene_rna_expression_stat(SOURCE_NAME=None,ORGAN=None, TISSUE=None, ANATOMY_TAG=None):
	query=f"""
		SELECT GENE_SEQ_NAME,GENE_SEQ_VERSION, ORGAN_NAME,TISSUE_NAME,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
		FROM   GENE_SEQ T, RNA_GENE_STAT RGS, RNA_TISSUE RT
		WHERE  RGS.GENE_SEQ_ID = T.GENE_SEQ_ID 
		AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID 
	"""
	if ORGAN != None:
		query += f" AND organ_name IN ('{"','".join(ORGAN)}')"
	if TISSUE != None:
		query += f" AND tissue_name IN ('{"','".join(TISSUE)}')"
	if ANATOMY_TAG != None:
		query += f" AND anatomy_tag IN ('{"','".join(ANATOMY_TAG)}')"
	return run_query(query)


# // $[API]
# // Title: Get statistics of Gene RNA expression 
# // Function: get_tissue_gene_rna_expression_stat
# // Description: Get the statistics of RNA expression for all gene across a set of different tissues/source/organ/anatomy_tag
# // Parameter: ORGAN | Organ name | array | Brain | optional
# // Parameter: TISSUE | Tissue name | array | Cerebellum,Cortex | optional
# // Parameter: ANATOMY_TAG | Anatomy tag | array | UBERON_0002037 | optional
# // Return: gene seq name, gene version, organ name, tissue name, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: python3.12 biorels_api.py get_tissue_gene_rna_expression_stat -ORGAN 'Brain'
# // Example: python3.12 biorels_api.py get_tissue_gene_rna_expression_stat  -TISSUE 'Cerebellum,Cortex' 
# // Example: python3.12 biorels_api.py get_tissue_gene_rna_expression_stat  -ANATOMY_TAG 'UBERON_0002037'
# // $[/API]
def get_tissue_gene_rna_expression_stat(ORGAN=[],TISSUE=[],ANATOMY_TAG=[]):
	query=f"""SELECT GENE_SEQ_NAME,GENE_SEQ_VERSION, ORGAN_NAME,TISSUE_NAME,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
	FROM   GENE_SEQ T, RNA_GENE_STAT RGS, RNA_TISSUE RT
	WHERE  RGS.GENE_SEQ_ID = T.GENE_SEQ_ID 
	AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID """
	if ORGAN!=None and ORGAN!=[]:
		query+=f" AND organ_name IN ('{','.join(ORGAN)}')"
	if TISSUE!=None and TISSUE!=[]:
		query+=f" AND tissue_name IN ('{','.join(TISSUE)}')"
	if ANATOMY_TAG!=None and ANATOMY_TAG!=[]:
		query+=f" AND anatomy_tag IN ('{','.join(ANATOMY_TAG)}')"
	return run_query(query)




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////////// Clinical variant ////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////







# // $[API]
# // Title: List and count the number of clinical variant by type
# // Function: list_clinical_variant_type
# // Description: List and count the number of clinical variant by type
# // Parameter: Dummy | Dummy parameter | string | optional | Default: None
# // Return: Count, clinical variant type
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: python3.12 biorels_api.py list_clinical_variant_type
# // $[/API]
def list_clinical_variant_type(Dummy:None):
	return run_query("""SELECT count(*) n_clinical_variant, clinical_variant_type 
	FROM clinical_variant_entry cve, clinical_variant_type cvt
	WHERE cve.clinical_variant_type_id = cvt.clinical_variant_type_id
	group by clinical_variant_type 
	order by n_clinical_variant DESC""")


# // $[API]
# // Title: List and count the number of clinical variant by gene
# // Function: list_clinical_variant_by_gene
# // Description: List and count the number of clinical variant by gene
# // Return: Count, gene ID, symbol, full name
# // Parameter: Dummy | Dummy parameter | string | optional | Default: None
# // Ecosystem: Disease_anatomy:clinical variant;Genomics:gene
# // Example: python3.12 biorels_api.py list_clinical_variant_by_gene
# // $[/API]
def list_clinical_variant_by_gene(Dummy:None):
	return run_query("""SELECT count(*) n_clinical_variant, gene_id, symbol, full_name
	FROM clinical_variant_entry cve, clinical_variant_submission cvs, 
	clinical_variant_gn_map cvgm, gn_entry ge
	WHERE cve.clinvar_entry_id = cvs.clinvar_entry_id
	AND cvs.clinvar_submission_id = cvgm.clinvar_submission_id
	AND cvgm.gn_entry_id = ge.gn_entry_id
	group by gene_id, symbol, full_name""")


# // $[API]
# // Title: List and count the number of clinical variant by significance
# // Function: list_clinical_variant_significance
# // Description: List and count the number of clinical variant by significance
# // Return: Count, clinical significance
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: python3.12 biorels_api.py list_clinical_variant_significance
# // $[/API]
def list_clinical_variant_significance():
	return run_query("""SELECT count(distinct clinvar_entry_id) n_clinical_variant, clin_sign as clinical_significance
	FROM clinical_variant_submission cvs, clinical_significance cs
	WHERE cvs.clin_sign_id = cs.clin_sign_id
	group by clin_sign""")


# // $[API]
# // Title: clinical variant information
# // Function: get_clinical_variant_information
# // Description: Get all information about a clinical variant
# // Parameter: CLINICAL_VARIANT_NAME | Clinical variant name | string | NM_000059.3:c.35G>A | required
# // Return: Clinical variant record
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: python3.12 biorels_api.py get_clinical_variant_information -CLINICAL_VARIANT_NAME 'NM_001798.5(CDK2):c.28A>C (p.Ile10Leu)'
# // $[/API]

def get_clinical_variant_information(CLINICAL_VARIANT_NAME):
	query=f"""SELECT * 
	FROM clinical_variant_entry cve, 
		 clinical_variant_type cvt,
		 clinical_Variant_review_status cvrs
	WHERE cve.clinical_variant_type_id = cvt.clinical_variant_type_id
	AND cve.clinical_variant_review_status = cvrs.clinvar_review_status_id
	AND LOWER(clinical_variant_name) LIKE LOWER('%{CLINICAL_VARIANT_NAME}%')"""
	res=run_query(query)
	for line in res:
		res2=run_query(f"""SELECT scv_id FROM clinical_variant_submission cvs WHERE clinvar_entry_id ={line['clinvar_entry_id']}""")
		if (res2!=[]):line['SUBMISSION']={}
		for l2 in res2:
			line['SUBMISSION'][l2['scv_id']]=get_clinical_variant_submission(l2['scv_id'])
	return res





# // $[API]
# // Title: Get clinical variant submission
# // Function: get_clinical_variant_submission
# // Description: Get all information about a clinical variant submission
# // Parameter: SCV_ID | SCV ID | string | SCV004922085.1 | required
# // Return: Clinical variant submission record
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: python3.12 biorels_api.py get_clinical_variant_submission -SCV_ID 'SCV004922085.1'
# // $[/API]
def get_clinical_variant_submission(SCV_ID):
	query=f"""SELECT *
	FROM clinical_variant_submission cvs, clinical_significance cs, clinical_variant_review_status cvrs
	WHERE cvs.clin_sign_id = cs.clin_sign_id
	AND cvs.clinical_variant_review_status = cvrs.clinvar_review_status_id
	AND scv_id='{SCV_ID}'"""
	res=run_query(query)
	for line in res:
		res2=run_query(f"""SELECT g.gene_id 
		FROM gn_entry g, clinical_variant_gn_map cvgm
		WHERE g.gn_entry_id = cvgm.gn_entry_id
		AND cvgm.clinvar_submission_id = '{line['clinvar_submission_id']}'""")
		if (res2!=[]):line['GENE']={}
		for line2 in res2:
			line['GENE'][line2['gene_id']]=get_gene_by_gene_id(line2['gene_id'])

		res2=run_query(f"""SELECT d.disease_tag
		FROM disease_entry d, clinical_variant_disease_map cvdm
		WHERE d.disease_entry_id = cvdm.disease_entry_id
		AND cvdm.clinvar_submission_id = '{line['clinvar_submission_id']}'""")
		if (res2!=[]):line['DISEASE']={}
		for line2 in res2:
			line['DISEASE'][line2['disease_tag']]=search_disease_by_tag(line2['disease_tag'])
		res2=run_query(f"""SELECT pmid FROM clinical_variant_pmid_map cvp, pmid_entry pe 
		WHERE pe.pmid_entry_id = cvp.pmid_entry_id
		AND clinvar_submission_id='{line['clinvar_submission_id']}'""")
		if (res2!=[]):line['PMID']={}
		for line2 in res2:
			line['PMID'][line2['pmid']]=line2['pmid']
	return res



# // $[API]
# // Title: Search clinical variant by different parameters
# // Function: search_clinical_variant
# // Description: Search for a clinical variant by using different parameters: name, type, status, gene id, transcript, clinical significance, transcript
# // Parameter: PARAMS | List of parameters, Use $PARAMS=array('NAMES'=>array(),'TYPE'=>array(),'STATUS'=>array(),'GENE_IDS'=>array(),'TRANSCRIPTS'=>array(),'SIGNIFICANCE'=>array()); | multi_array | required
# // Return: Clinical variant record
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: python3.12 biorels_api.py search_clinical_variant -PARAMS "SIGNIFICANCE=Pathogenic"
# // Example: python3.12 biorels_api.py search_clinical_variant -PARAMS "GENE_IDS=1017,1018"
# // Example: python3.12 biorels_api.py search_clinical_variant -PARAMS "NAMES=NM_001798.5(CDK2):c.28A>C (p.Ile10Leu)"
# // Example: python3.12 biorels_api.py search_clinical_variant -PARAMS "TYPE=deletion"
# // Example: python3.12 biorels_api.py search_clinical_variant -PARAMS "STATUS=1"
# // Example: python3.12 biorels_api.py search_clinical_variant -PARAMS "DISEASE=MONDO_0005087"

# // $[/API]
def search_clinical_variant(PARAMS):
	rule=False
	query="SELECT DISTINCT clinical_variant_name FROM clinical_variant_entry cve,clinical_variant_submission cvs"
	if 'TYPE' in PARAMS and PARAMS['TYPE']!=[]:
		rule=True
		query+=", clinical_variant_type cvt"
	if 'STATUS' in PARAMS and PARAMS['STATUS']!=[]:
		rule=True
		query+=", clinical_Variant_review_status cvrs"
	if 'SIGNIFICANCE' in PARAMS and PARAMS['SIGNIFICANCE']!=[]:
		rule=True
		query+=", clinical_significance cs"
	if 'GENE_IDS' in PARAMS and PARAMS['GENE_IDS']!=[]:
		rule=True
		query+=", clinical_variant_gn_map cvgm, gn_entry ge"
	if 'DISEASE' in PARAMS and PARAMS['DISEASE']!=[]:
		rule=True
		query+=", clinical_variant_disease_map cvdm, disease_entry de"
	query+=" WHERE cve.clinvar_entry_id = cvs.clinvar_entry_id"
	if 'TYPE' in PARAMS and PARAMS['TYPE']!=[]:
		rule=True
		query+=" AND cve.clinical_variant_type_id = cvt.clinical_variant_type_id"
	if 'STATUS' in PARAMS and PARAMS['STATUS']!=[]:
		rule=True
		query+=" AND cve.clinical_variant_review_status = cvrs.clinvar_review_status_id"
	if 'DISEASE' in PARAMS and PARAMS['DISEASE']!=[]:
		rule=True
		query+=" AND cvs.clinvar_submission_id = cvdm.clinvar_submission_id AND cvdm.disease_entry_id = de.disease_entry_id"
		query+=f" AND de.disease_tag IN ('{"','".join(PARAMS['DISEASE'])}')"
	if 'SIGNIFICANCE' in PARAMS and PARAMS['SIGNIFICANCE']!=[]:
		rule=True
		query+=" AND cs.clin_sign_id = cvs.clin_sign_id"
	if 'NAMES' in PARAMS and PARAMS['NAMES']!=[]:
		rule=True
		s="%') OR LOWER(clinical_variant_name) LIKE LOWER('%"
		query+=f" AND (LOWER(clinical_variant_name) LIKE LOWER('%{s.join(PARAMS['NAMES'])}%'))"
	if 'TYPE' in PARAMS and PARAMS['TYPE']!=[]:
		rule=True
		query+=f" AND clinical_variant_type IN ('{"','".join(PARAMS['TYPE'])}')"
	if 'STATUS' in PARAMS and PARAMS['STATUS']!=[]:
		rule=True
		query+=f" AND cvs.clinical_variant_review_status IN ('{"','".join(PARAMS['STATUS'])}')"
	if 'GENE_IDS' in PARAMS and PARAMS['GENE_IDS']!=[]:
		rule=True
		query+=f" AND cvs.clinvar_submission_id = cvgm.clinvar_submission_id AND cvgm.gn_entry_id = ge.gn_entry_id AND gene_id IN ({','.join(PARAMS['GENE_IDS'])})"
	if 'SIGNIFICANCE' in PARAMS and PARAMS['SIGNIFICANCE']!=[]:
		rule=True
		query+=f" AND LOWER(clin_sign) IN ('{','.join(PARAMS['SIGNIFICANCE'])}')"
	if 'TRANSCRIPTS' in PARAMS and PARAMS['TRANSCRIPTS']!=[]:
		rule=True
		s="%') OR LOWER(clinical_variant_name) LIKE LOWER('%"
		query+=f" AND (LOWER(clinical_variant_name) LIKE LOWER('%{s.join(PARAMS['TRANSCRIPTS'])}%'))"
	if (not rule):
		print("No search criteria provided")
		return []
	res= run_query(query)
	data=[]
	for line in res:
		data.append(get_clinical_variant_information(line['clinical_variant_name']))
	return data













# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////////// DRUG/CLINICAL TRIALS //////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// CLINICAL TRIALS ////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: get information for a clinical trial 
# // Function: get_clinical_trial_information
# // Description: Get all information about a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Clinical trial record
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py get_clinical_trial_information -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_information(TRIAL_ID,COMPLETE=False):
	query=f"SELECT * FROM clinical_trial WHERE trial_id='{TRIAL_ID}'"
	res=run_query(query)
	
	if res==[]:
		return []
	for line in res:
		if not COMPLETE:
			del line['details']
	return res



# // $[API]
# // Title: List and count the number of clinical trials by phase
# // Function: list_clinical_phases
# // Description: List and count the number of clinical trials by phase
# // Return: Count, clinical phase
# // Parameter: Dummy | Dummy parameter | string | optional | Default: None
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py list_clinical_phases
# // $[/API]
def list_clinical_phases(Dummy:None):
	return run_query("""SELECT count(*) n_clinical_trial, clinical_phase
	FROM clinical_trial group by clinical_phase order by n_clinical_trial DESC""")


# // $[API]
# // Title: Search clinical trial by different parameters
# // Function: search_clinical_trial
# // Description: Search for a clinical trial by using different parameters.
# // Parameter: PARAMS | List of parameters, Use $PARAMS=array('phase'=>array(),'status'=>array(),'id'=>array(),'gene_symbol'=>array(),'title'=>array(),'alias'=>array(),'intervention_type'=>array(),'intervention_name'=>array(),'condition'=>array(),'company'=>array(),'pmid'=>array(),'after_date'=>array(),'disease_tag'=>array(),'arm'=>array(),'drug_name'=>array()); | multi_array | multi | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional 
# // Return: Clinical trial record
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py search_clinical_trial -PARAMS "phase=1,2;status=Recruiting"
# // Example: python3.12 biorels_api.py search_clinical_trial -PARAMS "phase=1,2;status=Recruiting" -COMPLETE true
# // Example: python3.12 biorels_api.py search_clinical_trial -PARAMS "gene_symbol=CDK2"
# // $[/API]
def search_clinical_trial(PARAMS,COMPLETE=False):
	query=[]
	TABLES={}
	TABLES['clinical_trial ct']=True
	CONNECT={}
	if 'phase' in PARAMS:
		query.append(f"clinical_phase IN ('{"','".join(PARAMS['phase'])}')")
	if 'status' in PARAMS:
		query.append(f"clinical_status IN ('{"','".join(PARAMS['status'])}')")
	if 'id' in PARAMS:
		query.append(f"trial_id IN ('{"','".join(PARAMS['id'])}')")
	if 'title' in PARAMS:
		query.append(f"(LOWER(official_title) LIKE LOWER('%{"%') OR LOWER(official_title) LIKE LOWER('%".join(PARAMS['title'])}%'))")
	if 'alias' in PARAMS:
		TABLES['clinical_trial_alias cta']=True
		CONNECT["ct.clinical_trial_id = cta.clinical_trial_id"]=True
		query.append(f"(LOWER(alias_name) LIKE LOWER('%{"%') OR LOWER(alias_name) LIKE LOWER('%".join(PARAMS['alias'])}%'))")
	if 'intervention_type' in PARAMS:
		TABLES['clinical_trial_intervention cti']=True
		CONNECT["ct.clinical_trial_id = cti.clinical_trial_id"]=True
		query.append(f"intervention_type IN ('{"','".join(PARAMS['intervention_type'])}')")
	if 'ATC_code' in PARAMS:
		list=[]
		if 'WITH_ATC_CHILD' in PARAMS:
			for A in PARAMS['ATC_code']:
				res=get_ATC_child_hierarchy(A)
				for l in res:
					list.append(l['atc_code'])
		else:
			list=PARAMS['ATC_code']
		TABLES['clinical_trial_intervention cti']=True
		TABLES['clinical_trial_intervention_drug_map ctidm']=True
		TABLES['drug_entry de']=True
		TABLES['drug_atc_map dam']=True
		TABLES['atc_entry ac']=True
		CONNECT['ct.clinical_trial_id = cti.clinical_trial_id']=True
		CONNECT["cti.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id"]=True
		CONNECT["ctidm.drug_entry_id = de.drug_entry_id"]=True
		CONNECT["de.drug_entry_id = dam.drug_entry_id"]=True
		CONNECT["dam.atc_entry_id = ac.atc_entry_id"]=True
		query.append(f"ac.atc_code IN ('{"','".join(list)}')")

	if 'intervention_name' in PARAMS:
		TABLES['clinical_trial_intervention cti']=True
		CONNECT["ct.clinical_trial_id = cti.clinical_trial_id"]=True
		query.append(f"(LOWER(intervention_name) LIKE LOWER('%{"%') OR LOWER(intervention_name) LIKE LOWER('%".join(PARAMS['intervention_name'])}%'))")
	if 'condition' in PARAMS:
		TABLES['clinical_trial_condition ctc']=True
		CONNECT["ct.clinical_trial_id = ctc.clinical_trial_id"]=True
		query.append(f"(LOWER(condition_name) LIKE LOWER('%{"%') OR LOWER(condition_name) LIKE LOWER('%".join(PARAMS['condition'])}%'))")
	if 'company' in PARAMS:
		TABLES['clinical_trial_company_map ctcm']=True
		TABLES['company_entry coe']=True
		CONNECT["ctcm.company_entry_Id = coe.company_entry_id"]=True
		CONNECT["ct.clinical_trial_id = ctcm.clinical_trial_id"]=True
		query.append(f"(LOWER(company_name) LIKE LOWER('%{"%') OR LOWER(company_name) LIKE LOWER('%".join(PARAMS['company'])}%'))")
	if 'pmid' in PARAMS:
		TABLES['clinical_trial_pmid_map ctpm, pmid_entry pe']=True
		CONNECT["ct.clinical_trial_id = ctpm.clinical_trial_id"]=True
		CONNECT["ctpm.pmid_entry_id = pe.pmid_entry_id"]=True
		query.append(f"pmid IN ('{"','".join(PARAMS['pmid'])}')")
	if 'after_date' in PARAMS:
		query.append(f"start_date > '{PARAMS['after_date'][0]}'")
	if 'gene_symbol' in PARAMS:
		TABLES['clinical_trial_drug ctd']=True
		TABLES['drug_disease dd']=True
		TABLES['gn_entry g']=True
		CONNECT["ct.clinical_trial_id = ctd.clinical_trial_id"]=True
		CONNECT["ctd.drug_disease_id = dd.drug_disease_id"]=True
		CONNECT["dd.gn_entry_id = g.gn_entry_id"]=True
		s="%') OR LOWER(symbol) LIKE LOWER('%"
		query.append(f"(LOWER(symbol) LIKE LOWER('%{s.join(PARAMS['gene_symbol'])}%'))")
		
	if 'disease_tag' in PARAMS:
		TABLES['clinical_trial_disease_map ctdm']=True
		TABLES['disease_entry di']=True
		CONNECT["ct.clinical_trial_id = ctdm.clinical_trial_id"]=True
		CONNECT["di.disease_entry_id = ctdm.disease_entry_id"]=True
		query+=f"disease_tag IN ('{"','".join(PARAMS['disease_tag'])}')"
	if 'arm' in PARAMS:
		TABLES['clinical_trial_arm cta']=True
		CONNECT[" ct.clinical_trial_id = cta.clinical_trial_id"]=True
		query.append(f"(LOWER(arm_name) LIKE LOWER('%{"%') OR LOWER(arm_name) LIKE LOWER('%".join(PARAMS['arm'])}%'))")
	if 'drug_name' in PARAMS:
		TABLES['clinical_trial_intervention cti']=True
		TABLES['clinical_trial_intervention_drug_map ctidm']=True
		TABLES['drug_entry de']=True
		CONNECT['ct.clinical_trial_id = cti.clinical_trial_id']=True
		CONNECT["cti.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id"]=True
		CONNECT["ctidm.drug_entry_id = de.drug_entry_id"]=True

		s="%') OR LOWER(drug_primary_name) LIKE LOWER('%"
		query.append(f"(LOWER(drug_primary_name) LIKE LOWER('%{s.join(PARAMS['drug_name'])}%'))")
	if query==[]:
		print("No search criteria provided")
		return []
	for C in CONNECT:
		query.append(C)
	full_query='SELECT trial_id FROM '+','.join(TABLES.keys())+' WHERE '+' AND '.join(query)+' ORDER BY CT.START_DATE DESC'
	
	res=run_query(full_query)
	if (not COMPLETE): return res
	data=[]
	for line in res:
		data.append(get_clinical_trial_information(line['trial_id'],COMPLETE))
	return data
	





# // $[API]
# // Title: Get clinical trials by disease
# // Function: get_clinical_trial_by_disease
# // Description: Get all clinical trials for a given disease
# // Parameter: DISEASE_TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Parameter: WITH_CHILDREN | True if clinical trials for children diseases are requested | false | boolean | optional
# // Return: Clinical trial record
# // Ecosystem: Drug_clinical_trial:clinical trial|Disease_anatomy:disease
# // Example: python3.12 biorels_api.py get_clinical_trial_by_disease -DISEASE_TAG 'MONDO_0005087'
# // $[/API]
def get_clinical_trial_by_disease(DISEASE_TAG,COMPLETE=False,WITH_CHILDREN=False):
	list_child=[]
	if (WITH_CHILDREN):
		
		res=get_child_disease(DISEASE_TAG)
		
		for line in res:
			list_child.append(line['disease_tag'])
		
	else:
		list_child.append(DISEASE_TAG)
	query=f"""SELECT DISTINCT trial_id FROM clinical_trial ct,clinical_trial_condition ctc,disease_entry de
	WHERE ct.clinical_trial_id = ctc.clinical_trial_id AND ctc.disease_entry_id = de.disease_entry_id
	AND disease_tag IN ('{"','".join(list_child)}')"""
	
	res=run_query(query)
	if not COMPLETE: return res

	data=[]
	for line in res:
		data.append(get_clinical_trial_information(line['trial_id']))
	return data


# // $[API]
# // Title: Get clinical trials by gene
# // Function: get_clinical_trial_by_gene
# // Description: Get all clinical trials for a given gene
# // Parameter: GENE_SYMBOL | Gene symbol | string | BRCA1 | required
# // Return: Clinical trial record
# // Ecosystem: Drug_clinical_trial:clinical trial;Genomics:gene
# // Example: python3.12 biorels_api.py get_clinical_trial_by_gene -GENE_SYMBOL 'BRCA1'
# // $[/API]
def get_clinical_trial_by_gene(GENE_SYMBOL):
	query=f"""SELECT trial_id FROM clinical_trial ct,clinical_trial_drug ctd,drug_disease dd,gn_entry g
	WHERE ct.clinical_trial_id = ctd.clinical_trial_id AND ctd.drug_disease_id = dd.drug_disease_id
	AND dd.gn_entry_id = g.gn_entry_id
	AND LOWER(symbol) LIKE LOWER('%{GENE_SYMBOL}%')"""
	res=run_query(query)
	data=[]
	for line in res:
		data.append(get_clinical_trial_information(line['trial_id']))
	return data



# # // $[API]
# # // Title: Get clinical trials by drug
# # // Function: search_clinical_trial_by_drug
# # // Description: Search all clinical trials for a given drug
# # // Parameter: DRUG_NAME | Drug name | string | Omeprazole | required
# # // Return: Clinical trial record
# # // Ecosystem: Drug_clinical_trial:clinical trial|drug
# # // Example: python3.12 biorels_api.py search_clinical_trial_by_drug -DRUG_NAME 'Omeprazole'
# # // $[/API]
def search_clinical_trial_by_drug(DRUG_NAME):
	res=search_drug_by_name(DRUG_NAME,False)
	if res==[]: return []
	data=[]
	for line in res:
		DRUG_PRIMARY_NAME=line['drug_primary_name']
		query=f"""SELECT trial_id FROM clinical_trial ct,clinical_trial_intervention cti,clinical_trial_intervention_drug_map ctidm,drug_entry de
		WHERE ct.clinical_trial_id = cti.clinical_trial_id AND cti.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id
		AND ctidm.drug_entry_id = de.drug_entry_id
		AND drug_primary_name ='{DRUG_PRIMARY_NAME}'"""
		res2=run_query(query)
		for line in res2:
			data.append(get_clinical_trial_information(line['trial_id']))
	return data




# // $[API]
# // Title: Get the title of a clinical trial
# // Function: get_clinical_trial_title
# // Description: Get the title of a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Clinical trial title
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py get_clinical_trial_title -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_title(TRIAL_ID):
	query=f"SELECT official_title FROM clinical_trial WHERE trial_id='{TRIAL_ID}'"
	res=run_query(query)
	if res==[]: return ""
	return res[0]['official_title']



# // $[API]
# // Title: Get the brief summary of a clinical trial
# // Function: get_clinical_trial_brief_summary
# // Description: Get the brief summary of a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Clinical trial brief summary
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py get_clinical_trial_brief_summary -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_brief_summary(TRIAL_ID):
	query=f"SELECT brief_summary FROM clinical_trial WHERE trial_id='{TRIAL_ID}'"
	res=run_query(query)
	if res==[]: return ""
	return res[0]['brief_summary']

# // $[API]
# // Title: Get the list of interventions for a clinical trial
# // Function: get_clinical_trial_intervention
# // Description: Get the list of interventions for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Intervention name, intervention type, intervention description
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py get_clinical_trial_intervention -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_intervention(TRIAL_ID):
	query=f"""SELECT intervention_name,intervention_type,intervention_description
	FROM clinical_trial_intervention cti, clinical_trial ct
	WHERE ct.clinical_trial_id = cti.clinical_trial_id
	AND trial_id='{TRIAL_ID}'"""
	res=run_query(query)
	return res


# // $[API]
# // Title: Get the list of arms for a clinical trial
# // Function: get_clinical_trial_arms
# // Description: Get the list of arms for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Arm label, arm type, arm description
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py get_clinical_trial_arms -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_arms(TRIAL_ID):
	query=f"""SELECT arm_label,arm_type,arm_description
	FROM clinical_trial_arm cta, clinical_trial ct
	WHERE ct.clinical_trial_id = cta.clinical_trial_id
	AND trial_id='{TRIAL_ID}'"""
	res=run_query(query)
	return res


# // $[API]
# // Title: Get the list of conditions for a clinical trial
# // Function: get_clinical_trial_condition
# // Description: Get the list of conditions for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Condition name
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py get_clinical_trial_condition -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_condition(TRIAL_ID):
	query=f"""SELECT condition_name
	FROM clinical_trial_condition ctc, clinical_trial ct
	WHERE ct.clinical_trial_id = ctc.clinical_trial_id
	AND trial_id='{TRIAL_ID}'"""
	res=run_query(query)
	return res


# // $[API]
# // Title: Get the mapping of arms to interventions for a clinical trial
# // Function: get_clinical_trial_arm_intervention
# // Description: Get the mapping of arms to interventions for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219|  required
# // Return: Arm label, intervention name
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: python3.12 biorels_api.py get_clinical_trial_arm_intervention -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_arm_intervention(TRIAL_ID):
	query=f"""SELECT arm_label,arm_type,arm_description,intervention_name,intervention_type,intervention_description
	FROM clinical_trial_arm_intervention_map ctam, clinical_trial_arm cta,clinical_trial_intervention cti, clinical_trial ct
	WHERE ct.clinical_trial_id = cta.clinical_trial_id
	AND cta.clinical_trial_arm_id = ctam.clinical_trial_arm_id
	AND ctam.clinical_trial_intervention_id = cti.clinical_trial_intervention_id
	AND trial_id='{TRIAL_ID}'"""
	res=run_query(query)
	return res


# // $[API]
# // Title: Get the drugs listed in a clinical trial
# // Function: get_clinical_trial_drug
# // Description: Get the drugs listed in a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional
# // Return: Drug name
# // Ecosystem: Drug_clinical_trial:clinical trial|drug
# // Example: python3.12 biorels_api.py get_clinical_trial_drug -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_drug(TRIAL_ID,COMPLETE=False):
	query=f"""SELECT drug_primary_name
	FROM clinical_trial_intervention_drug_map ctidm, clinical_trial_intervention cti, drug_entry de, clinical_trial ct
	WHERE ct.clinical_trial_id = cti.clinical_trial_id
	AND cti.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id
	AND ctidm.drug_entry_id = de.drug_entry_id
	AND trial_id='{TRIAL_ID}'"""
	res=run_query(query)
	if COMPLETE:
		for line in res:
			line['drug_info']=get_drug_information(line['drug_primary_name'])
	return res



# // $[API]
# // Title: Get the list of publications for a clinical trial
# // Function: get_clinical_trial_publications
# // Description: Get the list of publications for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | required
# // Return: PMID
# // Ecosystem: Drug_clinical_trial:clinical trial;Scientific_Community:publication
# // Example: python3.12 biorels_api.py get_clinical_trial_publications -TRIAL_ID 'NCT00005219'
# // $[/API]
def get_clinical_trial_publications(TRIAL_ID):
	query=f"""SELECT pmid 
	FROM clinical_trial_pmid_map ctpm, pmid_entry pe, clinical_trial ct
	WHERE ct.clinical_trial_id = ctpm.clinical_trial_id
	AND ctpm.pmid_entry_id = pe.pmid_entry_id
	AND trial_id='{TRIAL_ID}'"""
	res=run_query(query)
	return res


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////////// DRUGS //////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Search drug by their identifier
# // Function: search_drug_by_identifier
# // Description: Find a drug by their identifier or name. The identifier can be the drug primary name, the drug external database value, the ChEMBL ID or the DrugBank ID
# // Parameter: ID | Drug identifier | string | CHEMBL1201583 | required
# // Return: Drug record
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py search_drug_by_identifier -ID 'DB00006'
# // Example: python3.12 biorels_api.py search_drug_by_identifier -ID 'CHEMBL1201583'
# // Example: python3.12 biorels_api.py search_drug_by_identifier -ID 'DRUGBANK:DB00006'
# // $[/API]
def search_drug_by_identifier(ID):
	RULES=[]
	tab=ID.split(':')
	RULES.append(f"drug_extdb_value='{ID}'")
	if len(tab)==2:
		RULES.append(f"LOWER(source_name) = LOWER('{tab[0]}') AND drug_extdb_value = '{tab[1]}'")
	tab=ID.split('_')
	if len(tab)==2:
		RULES.append(f"LOWER(source_name) = LOWER('{tab[0]}') AND drug_extdb_value = '{tab[1]}'")
	RULES.append(f"drug_primary_name='{ID}'")
	RULES.append(f"chembl_id='{ID}'")
	RULES.append(f"drugbank_id='{ID}'")
	query=f"""SELECT distinct drug_primary_name FROM drug_entry de,drug_extdb dx,source s
	WHERE  de.drug_entry_Id = dx.drug_entry_Id
	AND dx.source_id = s.source_id
	AND ({" OR ".join(RULES)})"""
	res=run_query(query)
	data=[]
	for line in res:
		data.append(get_drug_information(line['drug_primary_name']))
	return data


# // $[API]
# // Title: Search drug by their name
# // Function: search_drug_by_name
# // Description: Find a drug by their name. The search is case insensitive
# // Parameter: NAME | Drug name | string | Omeprazole | required
# // Parameter: WITH_DRUG_INFORMATION | True if drug information is requested | boolean | true | optional | Default: true
# // Parameter: COMPLETE | True if complete drug information is requested | boolean | false | optional
# // Return: Drug record
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py search_drug_by_name -NAME 'Omeprazole'
# // $[/API]
def search_drug_by_name(NAME,WITH_DRUG_INFORMATION=True,COMPLETE=False):
	query="SELECT distinct drug_primary_name FROM drug_entry de,drug_name dn"
	query+=f" WHERE  de.drug_entry_Id = dn.drug_entry_Id AND (LOWER(drug_name) =LOWER('{NAME}') OR LOWER(drug_primary_name) = LOWER('{NAME}'))"
	res=run_query(query)
	if res==[]:
		query="SELECT distinct drug_primary_name FROM drug_entry de,drug_name dn"
		query+=f" WHERE  de.drug_entry_Id = dn.drug_entry_Id AND (LOWER(drug_name) LIKE LOWER('%{NAME}%') OR LOWER(drug_primary_name) LIKE LOWER('%{NAME}%'))"
		res=run_query(query)
	if not WITH_DRUG_INFORMATION and not COMPLETE:
		return res
	data=[]
	for line in res:
		data.append(get_drug_information(line['drug_primary_name'],COMPLETE))
	return data



# // $[API]
# // Title: get drug information
# // Function: get_drug_information
# // Description: Get all information about a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | true | optional | Default: true
# // Return: Drug record
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py get_drug_information -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
def get_drug_information(DRUG_PRIMARY_NAME,COMPLETE=True):
	query=f"SELECT * FROM drug_entry WHERE LOWER(drug_primary_name)=LOWER('{DRUG_PRIMARY_NAME}')"
	res=run_query(query)
	if COMPLETE:
		for line in res:
			line['names']=get_drug_names(DRUG_PRIMARY_NAME)
			line['extdb']=get_drug_extdb(DRUG_PRIMARY_NAME)
			line['type']=get_drug_type(DRUG_PRIMARY_NAME)
			line['atc_class']=get_drug_atc_class(DRUG_PRIMARY_NAME)
			line['atc_hierarchy']=get_drug_atc_hierarchy(DRUG_PRIMARY_NAME)
			res2=run_query(f"""SELECT molecular_entity_hash, is_preferred,source_name 
					FROM drug_mol_entity_map dm, molecular_entity me, source s
					WHERE dm.molecular_entity_id = me.molecular_entity_id
					AND dm.source_id = s.source_id
					AND drug_entry_id = '{line['drug_entry_id']}'""")
			line['molecular_entity']=[]
			for l in res2:
				line['molecular_entity'].append({'is_preferred':l['is_preferred'],'source_name':l['source_name'],'structure':get_molecular_entity(l['molecular_entity_hash'])})
		
	return res




# // $[API]
# // Title: get drug activity
# // Function: get_drug_activity
# // Description: Get the activity of a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Parameter: WITH_ASSAY | True if assay information is requested | boolean | false | optional 
# // Parameter: WITH_EXTENDED_ASSAY_INFO | True if extended assay information is requested | boolean | false | optional
# // Return: Molecular entity hash, is preferred, source name, activity
# // Ecosystem: Drug_clinical_trial:drug;Assay:assay|activity
# // Example: python3.12 biorels_api.py get_drug_activity -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
def get_drug_activity(DRUG_PRIMARY_NAME,WITH_ASSAY=False,WITH_EXTENDED_ASSAY_INFO=False):
	
	res=run_query(f"""SELECT molecular_entity_hash, is_preferred,source_name 
	FROM drug_entry de, drug_mol_entity_map dm, molecular_entity me, source s
	WHERE de.drug_entry_id = dm.drug_entry_id
	AND dm.molecular_entity_id = me.molecular_entity_id
	AND dm.source_id = s.source_id
	AND drug_primary_name = '{DRUG_PRIMARY_NAME}'""")
	for l in res:
		l['activity']=get_molecular_entity_activity(l['molecular_entity_hash'],WITH_ASSAY,WITH_EXTENDED_ASSAY_INFO)
	return res



# // $[API]
# // Title: get drug names
# // Function: get_drug_names
# // Description: Get all names for a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: Drug name, is primary, is tradename, source name
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py get_drug_names -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
def get_drug_names(DRUG_PRIMARY_NAME):
	query=f"""SELECT drug_name,is_primary,is_tradename,source_name
	FROM drug_entry de, drug_name dn, source s
	WHERE s.source_id = dn.source_id
	AND de.drug_entry_id = dn.drug_entry_id
	AND drug_primary_name='{DRUG_PRIMARY_NAME}'"""
	res=run_query(query)
	data=[]
	for line in res:
		data.append({'drug_name':line['drug_name'],'is_primary':line['is_primary'],'is_tradename':line['is_tradename'],'source_name':line['source_name']})
	return data

# // $[API]
# // Title: get drug external identifiers
# // Function: get_drug_extdb
# // Description: Get all external identifiers for a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: Source name, drug external database value
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py get_drug_extdb -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
def get_drug_extdb(DRUG_PRIMARY_NAME):
	query=f"""SELECT source_name,drug_extdb_value FROM drug_entry de, drug_extdb dx, source s
	WHERE de.drug_entry_id = dx.drug_entry_id
	AND dx.source_id = s.source_id
	AND drug_primary_name='{DRUG_PRIMARY_NAME}'"""
	res=run_query(query)
	data=[]
	for line in res:
		data.append({'source_name':line['source_name'],'drug_extdb_value':line['drug_extdb_value']})
	return data


# // $[API]
# // Title: get drug type
# // Function: get_drug_type
# // Description: Get the type of a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: Drug type name, drug type group
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py get_drug_type -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
def get_drug_type(DRUG_PRIMARY_NAME):
	query=f"""SELECT drug_type_name,drug_Type_group FROM drug_entry de, drug_type_map dtm, drug_type dt
	WHERE  de.drug_entry_id = dtm.drug_entry_id
	AND dtm.drug_type_id = dt.drug_type_id
	AND drug_primary_name='{DRUG_PRIMARY_NAME}'"""
	return run_query(query)


# // $[API]
# // Title: get drug ATC class
# // Function: get_drug_atc_class
# // Description: Get the ATC class of a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py get_drug_atc_class -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
def get_drug_atc_class(DRUG_PRIMARY_NAME):
	query=f"""SELECT atc_code,atc_title FROM drug_entry de, drug_atc_map da, atc_entry ae
	WHERE  de.drug_entry_id = da.drug_entry_id
	AND da.atc_entry_id = ae.atc_entry_id
	AND drug_primary_name='{DRUG_PRIMARY_NAME}'"""
	return run_query(query)


# // $[API]
# // Title: get drug ATC hierarchy
# // Function: get_drug_atc_hierarchy
# // Description: Get the ATC hierarchy of a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: ATC level, ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py get_drug_atc_hierarchy -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
def get_drug_atc_hierarchy(DRUG_PRIMARY_NAME):
	query=f"""SELECT distinct atc_entry_id FROM drug_entry de, drug_atc_map da
	WHERE  de.drug_entry_id = da.drug_entry_id
	AND drug_primary_name='{DRUG_PRIMARY_NAME}'"""
	res= run_query(query)
	data=[]
	for line in res:
		query=f"""SELECT ah2.atc_level,ae.atc_code,ae.atc_title FROM atc_hierarchy ah1, atc_hierarchy ah2, atc_entry ae
		WHERE ah1.atc_entry_id = {line['atc_entry_id']}
		AND ah1.atc_level_left >= ah2.atc_level_left
		AND ah1.atc_level_right <= ah2.atc_level_right
		AND ah2.atc_entry_id = ae.atc_entry_id ORDER BY ah2.atc_level"""
		data.append(run_query(query))
	return data



# // $[API]
# // Title: Find drugs from ATC code
# // Function: get_drug_from_ATC_Code
# // Description: Get all drugs for a given ATC code
# // Parameter: ATC_CODE | ATC code | string | A02BC01 | required
# // Parameter: WITH_CHILD | True if child ATC codes are included | boolean | true | optional | Default: true
# // Return: Drug record
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py get_drug_from_ATC_Code -ATC_CODE 'A02BC01'
# // $[/API]
def get_drug_from_ATC_Code(ATC_CODE,WITH_CHILD=True):
	query=f"""SELECT drug_primary_name, ae.atc_code, ae.atc_title
	FROM drug_entry de, drug_atc_map dam, atc_entry ae"""
	if WITH_CHILD:
		query+=", atc_hierarchy ah1, atc_hierarchy ah2, atc_entry ae2"
	query+=f""" WHERE de.drug_entry_id = dam.drug_entry_id
	AND dam.atc_entry_id = ae.atc_entry_id """
	if WITH_CHILD:
		query+=f""" AND ae.atc_entry_id = ah1.atc_entry_id
		AND ah1.atc_level_left >= ah2.atc_level_left
		AND ah1.atc_level_right <= ah2.atc_level_right
		AND ah2.atc_entry_id = ae2.atc_entry_id 
		AND ae2.atc_code = '{ATC_CODE}'"""
	else:
		query+=f""" AND ae.atc_code = '{ATC_CODE}'"""
	res= run_query(query)
	data=[]
	for line in res:
		entry=get_drug_information(line['drug_primary_name'],False)
		entry[0]['ATC_code']=line['atc_code']
		entry[0]['ATC_title']=line['atc_title']
		data.append(entry[0])
	return data

	


# // $[API]
# // Title: List target statistics for a given ATC code
# // Function: get_target_stat_for_ATC_Code
# // Description: List the targets for a given ATC code
# // Parameter: ATC_CODE | ATC code | string | A02 | required
# // Parameter: WITH_CHILD | True if child ATC codes are included | boolean | false | optional | Default: false
# // Return: Target symbol, gene ID, count
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py  get_target_stat_for_ATC_Code -ATC_CODE 'A02'
# // $[/API]
def get_target_stat_for_ATC_Code(ATC_CODE,WITH_CHILD=False):
	list=[]
	if WITH_CHILD:
		res=get_ATC_child_hierarchy(ATC_CODE)
		for l in res:
			list.append(l['atc_code'])
	else:
		list.append(ATC_CODE)
	query=f"""SELECT symbol, gene_id, count(*) as count
	FROM atc_entry ae, drug_atc_map dam, drug_entry de, drug_disease dd, gn_entry g"""
	query+=f""" WHERE ae.atc_entry_id = dam.atc_entry_id
	AND dam.drug_entry_id = de.drug_entry_id
	AND de.drug_entry_id = dd.drug_entry_id
	AND dd.gn_entry_id = g.gn_entry_id
	AND ae.atc_code IN ('{"','".join(list)}')"""
	query+=" GROUP BY symbol, gene_id"
	res= run_query(query)
	return res


# // $[API]
# // Title: get information about ATC code
# // Function: get_ATC_info
# // Description: Get information about an ATC code
# // Parameter: ATC_CODE | ATC code | string | A02BC01 | required
# // Return: ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py  get_ATC_info -ATC_CODE 'A02BC01'
# // $[/API]
def get_ATC_info(ATC_CODE):
	query=f"SELECT atc_code, atc_title FROM atc_entry WHERE atc_code='{ATC_CODE}'"
	res= run_query(query)
	return res


# // $[API]
# // Title: get ATC hierarchy
# // Function: get_ATC_hierarchy
# // Description: Get the ATC hierarchy for a given ATC code
# // Parameter: ATC_CODE | ATC code | string | A02BC01 | required
# // Return: ATC level, ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py  get_ATC_hierarchy -ATC_CODE 'A02BC01'
# // $[/API]
def get_ATC_hierarchy(ATC_CODE):
	query=f"""SELECT ah1.atc_level, ae.atc_code, ae.atc_title 
	FROM atc_entry ae , atc_hierarchy ah1, atc_hierarchy ah2, atc_entry ae2
	WHERE ah1.atc_entry_id = ae.atc_entry_id 
	AND ah1.atc_level_left <= ah2.atc_level_left
	AND ah1.atc_level_right >= ah2.atc_level_right
	AND ah2.atc_entry_id = ae2.atc_entry_id
	AND ae2.atc_code='{ATC_CODE}'
	ORDER BY atc_level ASC"""
	res= run_query(query)
	return res

# // $[API]
# // Title: get ATC code children
# // Function: get_ATC_child_hierarchy
# // Description: Get the children ATC code for a given ATC code
# // Parameter: ATC_CODE | ATC code | string | A02 | required
# // Return: ATC level, ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: python3.12 biorels_api.py  get_ATC_child_hierarchy -ATC_CODE 'A02'
# // $[/API]
def get_ATC_child_hierarchy(ATC_CODE):
	query=f"""SELECT ah1.atc_level, ae.atc_code, ae.atc_title 
	FROM atc_entry ae , atc_hierarchy ah1, atc_hierarchy ah2, atc_entry ae2
	WHERE ah1.atc_entry_id = ae.atc_entry_id 
	AND ah1.atc_level_left >= ah2.atc_level_left
	AND ah1.atc_level_right <= ah2.atc_level_right
	AND ah2.atc_entry_id = ae2.atc_entry_id
	AND ae2.atc_code='{ATC_CODE}'
	ORDER BY atc_level ASC"""
	res= run_query(query)
	return res



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////////// ASSAY //////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: search assay by name
# // Function: search_assay_by_name
# // Description: Search assay by name
# // Parameter: NAME | Assay name | string | BRCA1 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py search_assay_by_name -NAME 'BRCA1'
# // $[/API]
def search_assay_by_name(NAME,COMPLETE=False):
	query=f"""SELECT assay_name,source_name FROM assay_entry ae, source s 
	WHERE s.source_id = ae.source_id AND
	LOWER(assay_name) LIKE LOWER('%{NAME}%')"""
	res=run_query(query)
	data=[]
	for line in res:
		data.append(get_assay_information(line['assay_name'],line['source_name'],COMPLETE))
	return data

# // $[API]
# // Title: search assay by description
# // Function: search_assay_by_description
# // Description: Search assay by description
# // Parameter: DESC | Assay description | string | BRCA1 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py search_assay_by_description -DESC 'BRCA1'
# // $[/API]
def search_assay_by_description(DESC,COMPLETE=False):
	query=f"""SELECT assay_name,source_name
	FROM assay_entry ae, source s 
	WHERE s.source_id = ae.source_id
	AND  LOWER(assay_description) LIKE LOWER('%{DESC}%')"""
	res=run_query(query)
	if (not COMPLETE): return res
	data=[]
	list={}
	for line in res:
		if line['source_name'] not in list:
			list[line['source_name']]=[]
		list[line['source_name']].append(line['assay_name'])
	for source in list:
		tmp=get_batch_assay_info(source,list[source],COMPLETE)
		
		for l in tmp:
			data.append(tmp[l])
	return data



# // $[API]
# // Title: list assay types
# // Function: list_assay_type
# // Description: Provide a list of assay types and the number of assays for each type (Functional, Binding, etc.)
# // Parameter: Dummy | Dummy parameter | string | | optional
# // Return: Assay type, number of assays
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py list_assay_type
# // $[/API]
def list_assay_type(Dummy=None):
	query=f"""SELECT assay_desc, count(*) n_assay
	FROM assay_type at, assay_entry a
	WHERE at.assay_type_id = a.assay_type
	GROUP BY assay_desc
	ORDER BY count(*) DESC"""
	res=run_query(query)
	return res


# // $[API]
# // Title: search assay by type
# // Function: search_assay_by_type
# // Description: Search assay by type
# // Parameter: ASSAY_TYPE | Assay type | string | Binding | required
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py search_assay_by_type -ASSAY_TYPE 'Binding'
# // $[/API]
def search_assay_by_type(ASSAY_TYPE):
	query=f"""SELECT assay_name,source_name
	FROM assay_entry ae,assay_type at, source s
	 WHERE at.assay_type_id = ae.assay_type
	 AND s.source_id = ae.source_id
	 AND LOWER(assay_desc) LIKE LOWER('%{ASSAY_TYPE}%')"""
	res=run_query(query)
	data=[]
	list={}
	for line in res:
		if line['source_name'] not in list:
			list[line['source_name']]=[]
		list[line['source_name']].append(line['assay_name'])
	for source in list:
		rt=get_batch_assay_info(source,list[source])
		for l in rt:
			data.append(l)
		
	return data




# // $[API]
# // Title: list all assay categories and the number of assays for each category
# // Function: list_assay_category
# // Description: Provide a list of assay categories and the number of assays for each category
# // Parameter: Dummy | Dummy parameter | string |  | optional
# // Return: Assay category name, number of assays
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py list_assay_category
# // $[/API]
def list_assay_category(Dummy:None):
	query=f"""SELECT assay_category, count(*) n_assay
	FROM assay_entry
	GROUP BY assay_category
	ORDER BY count(*) DESC"""
	res=run_query(query)
	return res



# // $[API]
# // Title: search assay by category
# // Function: search_assay_by_category
# // Description: Search assay by category
# // Parameter: CATEGORY | Assay category | string | Screening | required
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py search_assay_by_category -CATEGORY 'Screening'
# // $[/API]
def search_assay_by_category(CATEGORY):
	query=f"""SELECT assay_name, source_name
	FROM assay_entry ae,source s
	 WHERE s.source_id = ae.source_id
	AND  LOWER(assay_category) LIKE LOWER('%{CATEGORY}%')"""
	res=run_query(query)
	data=[]
	for line in res:
		data.append(get_assay_information(line['assay_name'],line['source_name']))
	return data










# // $[API]
# // Title: get assay information
# // Function: get_assay_information
# // Description: Get all information about an assay
# // Parameter: ASSAY_NAME | Assay NAME | string | CHEMBL944488 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record with taxon, assay type, assay tissue, assay cell, assay target, confidence
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py get_assay_information -ASSAY_NAME 'CHEMBL944488' -SOURCE_NAME ChEMBL
# // Example: python3.12 biorels_api.py get_assay_information -ASSAY_NAME 'CHEMBL944488,CHEMBL944489' -SOURCE_NAME ChEMBL
# // Example: python3.12 biorels_api.py get_assay_information -ASSAY_NAME 'CHEMBL944488,CHEMBL944489' -SOURCE_NAME ChEMBL -COMPLETE true
# // $[/API]
def get_assay_information(ASSAY_NAME,SOURCE_NAME,COMPLETE=False):
	LIST=[]
	if type(ASSAY_NAME)==str: LIST=ASSAY_NAME.split(',')
	else: LIST=ASSAY_NAME

	query=f"""SELECT s.source_name, ae.* , boe.* FROM source s,assay_entry ae
	LEFT JOIN taxon t on t.taxon_id = ae.taxon_id
	LEFT JOIN assay_type at ON at.assay_type_id = ae.assay_type
	LEFT JOIN assay_Confidence cs ON cs.confidence_score = ae.confidence_score
	LEFT JOIN bioassay_onto_entry boe ON boe.bioassay_onto_entry_id = ae.bioassay_onto_entry_id
	WHERE s.source_id = ae.source_id
	AND LOWER(source_name)='{SOURCE_NAME.lower()}'
	AND assay_name  IN ('{'\',\''.join(LIST)}')"""
	res=run_query(query)
	if not COMPLETE: return res
	for line in res:
		line['cell']=get_assay_cell(line['assay_name'],line['source_name'])
		line['tissue']=get_assay_tissue(line['assay_name'],line['source_name'])
		line['target']=get_assay_target(line['assay_name'],line['source_name'])
		line['variant']=get_assay_variant(line['assay_name'],line['source_name'])
	return res



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - TAXON //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: search assay by organism
# // Function: search_assay_by_taxon
# // Description: Search assay by organism, using the NCBI taxonomy ID
# // Parameter: NCBI_TAX_ID | NCBI taxonomy ID | string | 9606 | required
# // Parameter: COMPLETE | True if complete information is requested | false | boolean | optional
# // Return: Assay record
# // Ecosystem: Assay:assay;Genomics:taxon
# // Example: python3.12 biorels_api.py search_assay_by_taxon -NCBI_TAX_ID '9606'
# // $[/API]
def search_assay_by_taxon(NCBI_TAX_ID,COMPLETE=False):
	query=f"""SELECT assay_name, source_name
	FROM assay_entry ae,source s, taxon t
	 WHERE t.taxon_id = ae.taxon_id
	 AND s.source_id = ae.source_id
	 AND tax_id='{NCBI_TAX_ID}'"""
	res=run_query(query)
	data=[]
	list={}
	for line in res:
		if line['source_name'] not in list:
			list[line['source_name']]=[]
		list[line['source_name']].append(line['assay_name'])
	for source in list:
		tmp=get_batch_assay_info(source,list[source],COMPLETE)
		
		for l in tmp:
			data.append(l)
	return data



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - TISSUE //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: list assay tissues
# // Function: list_assay_tissue
# // Description: Provide a list of assay tissues and the number of assays for each tissue
# // Parameter: Dummy | Dummy parameter | string | | optional
# // Return: Assay tissue name, anatomy tag, anatomy name, number of assays
# // Ecosystem: Assay:assay;Disease_anatomy:tissue
# // Example: python3.12 biorels_api.py list_assay_tissue
# // $[/API]
def list_assay_tissue(Dummy=None):
	query=f"""SELECT assay_tissue_name,anatomy_tag,anatomy_name, count(*) n_assay
	FROM assay_tissue at,anatomy_entry an
	 WHERE at.anatomy_entry_id = an.anatomy_entry_id
	GROUP BY assay_tissue_name,anatomy_tag,anatomy_name
	ORDER BY count(*) DESC"""
	res=run_query(query)
	return res


# // $[API]
# // Title: search assay by anatomy tag
# // Function: search_assay_by_anatomy_tag
# // Description: Search assay by anatomy tag
# // Parameter: ANATOMY_TAG | Anatomy tag | string | UBERON_0001004 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py search_assay_by_anatomy_tag -ANATOMY_TAG 'UBERON_0001004'
# // $[/API]
def search_assay_by_anatomy_tag(ANATOMY_TAG,COMPLETE=False):
	query=f"""SELECT assay_name, source_name
	FROM assay_entry ae,source s,assay_tissue at,anatomy_entry an
	 WHERE at.assay_tissue_id = ae.assay_tissue_id
	 AND s.source_id = ae.source_id
	 AND at.anatomy_entry_id = an.anatomy_entry_id
	 AND anatomy_tag='{ANATOMY_TAG}'"""
	res=run_query(query)
	data=[]
	for line in res:
		data.append(get_assay_information(line['assay_name'],line['source_name'],COMPLETE))
	return data







# // $[API]
# // Title: search assay by anatomy name
# // Function: search_assay_by_anatomy_name
# // Description: Search assay by anatomy/tissue name
# // Parameter: ANATOMY_NAME | Anatomy name | string | Valve | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Disease_anatomy:anatomy
# // Example: python3.12 biorels_api.py search_assay_by_anatomy_name -ANATOMY_NAME 'Valve'
# // $[/API]
def search_assay_by_anatomy_name(ANATOMY_NAME,COMPLETE=False):
	TAGS=search_anatomy_by_name(ANATOMY_NAME)
	if TAGS==[]:
		TAGS=search_anatomy_by_name(ANATOMY_NAME,False)
	if TAGS==[]:
		TAGS=search_anatomy_by_synonym(ANATOMY_NAME)
	LIST_ID=[]
	for line in TAGS:
		LIST_ID.append(line['anatomy_entry_id'])
	query=f"""SELECT assay_name, source_name
	FROM source s, assay_entry ae, assay_tissue at
	LEFT JOIN anatomy_entry an ON an.anatomy_entry_id = at.anatomy_entry_id
	WHERE at.assay_tissue_id = ae.assay_tissue_id
	AND s.source_id = ae.source_id
	AND (LOWER(assay_Tissue_name) LIKE LOWER('%{ANATOMY_NAME}%')"""
	if LIST_ID!=[]:
		query+=f" OR at.anatomy_entry_id IN ({','.join([str(x) for x in LIST_ID])})"
	query+=')'
	res=run_query(query)
	data=[]
	for line in res:
		data.append(get_assay_information(line['assay_name'],line['source_name'],COMPLETE))
	return data
	




# // $[API]
# // Title: get assay tissue information
# // Function: get_assay_tissue
# // Description: Get all information about a tissue used in an assay
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL967748 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Return: Assay tissue record
# // Ecosystem: Assay:assay;Disease_anatomy:tissue
# // Example: python3.12 biorels_api.py get_assay_tissue -ASSAY_NAME 'CHEMBL2176111' -SOURCE_NAME 'CHEMBL'
# // $[/API]
def get_assay_tissue(ASSAY_NAME,SOURCE_NAME):
	res=run_query(f"""SELECT at.*,an.anatomy_tag 
	FROM assay_tissue at
	LEFT JOIN anatomy_entry an ON an.anatomy_entry_id = at.anatomy_entry_id,
	assay_entry ae, source s
	WHERE at.assay_tissue_id = ae.assay_tissue_id
	AND ae.source_id = s.source_id
	AND assay_name='{ASSAY_NAME}'
	AND LOWER(source_name)='{SOURCE_NAME.lower()}'""")
	for line in res:
		if line['anatomy_tag']!=None:
			line['anatomy']=get_anatomy_information(line['anatomy_tag'])
	return res




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - CELL //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: list cell lines with the number of assays
# // Function: list_assay_cell
# // Description: Provide a list of cell lines and the number of assays for each cell line
# // Parameter: Dummy | Dummy parameter | string | | optional
# // Return: Cell line accession, cell line name, number of assays
# // Ecosystem: Assay:assay;Disease_anatomy:cell
# // Example: python3.12 biorels_api.py list_assay_cell
# // $[/API]
def list_assay_cell(Dummy=None):
	query=f"""SELECT ac.cell_name,cell_acc, count(*) n_assay
	FROM assay_cell ac
	LEFT JOIN cell_Entry ce ON ac.cell_entry_id = ce.cell_entry_id, assay_entry a
	WHERE ac.assay_cell_id = a.assay_Cell_id
	GROUP BY cell_acc,ac.cell_name
	ORDER BY count(*) DESC"""
	res=run_query(query)
	return res



# // $[API]
# // Title: search assay by cell line
# // Function: search_assay_by_cell_line
# // Description: Search assay by cell line name, synonym or accession
# // Parameter: CELL_LINE | Cell line name | string | A549 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional 
# // Parameter: FULLY_COMPLETE | True if complete information about cell line is requested | boolean | false | optional 
# // Return: Assay record
# // Ecosystem: Assay:assay;Disease_anatomy:cell line
# // Example: python3.12 biorels_api.py search_assay_by_cell_line -CELL_LINE 'A549'
# // $[/API]
def search_assay_by_cell_line(CELL_LINE,COMPLETE=False,FULLY_COMPLETE=False):
	input={}
	input['NAME']=[CELL_LINE]
	res=search_cell_line(input)
	if res==[]: 
		input={}
		input['SYN']=[CELL_LINE]
		res=search_cell_line(input)
	if res==[]:
		input={}
		input['ACC']=[CELL_LINE]
		res=search_cell_line(input)
	query=f"""SELECT assay_cell_id FROM assay_cell ac
	WHERE 
	 cell_name= '{CELL_LINE}'
	OR cell_description = '{CELL_LINE}'"""
	res2=run_query(query)
	if res2==[]:
		query=f"""SELECT * FROM assay_cell ac
		WHERE  LOWER(cell_name) LIKE '%{CELL_LINE.lower()}%'
		OR LOWER(cell_description) LIKE '%{CELL_LINE.lower()}%'"""
		res2=run_query(query)
	if res2==[] and res==[]: return []
	data=[]
	query=f"""SELECT assay_name, source_name
	FROM assay_entry ae,source s, assay_cell ac
	WHERE s.source_id = ae.source_id
	AND
	ac.assay_cell_id = ae.assay_cell_id AND ("""
	rules=[]
	if res!=[]:
		list=[]
		
		for line in res:
			
			list.append(str(line[0]['cell_entry_id']))
		rules.append(f" ac.cell_entry_id IN ({','.join(list)})")
	if res2!=[]:
		list=[]

		for line in res2:
			
			list.append(str(line['assay_cell_id']))
		rules.append(f"  ac.assay_cell_id IN ({','.join(list)})")
	query+=f"""{' OR '.join(rules)})"""
	
	res=run_query(query)
	if not COMPLETE: return res
	for line in res:
		data.append(get_assay_information(line['assay_name'],line['source_name'],FULLY_COMPLETE))
	return data
	


# // $[API]
# // Title: get assay cell line information
# // Function: get_assay_cell
# // Description: Get all information about a cell line used in an assay
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL967748 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Return: Assay cell record
# // Ecosystem: Assay:assay;Disease_anatomy:cell line
# // Example: python3.12 biorels_api.py get_assay_cell -ASSAY_NAME 'CHEMBL967748' -SOURCE_NAME 'CHEMBL'
# // $[/API]
def get_assay_cell(ASSAY_NAME,SOURCE_NAME):
	res=run_query(f"""SELECT ac.*,cell_acc, t.* FROM assay_cell ac
	LEFT JOIN taxon t on t.taxon_id =ac.taxon_Id
	LEFT JOIN cell_entry ce ON ce.cell_entry_Id = ac.cell_entry_Id,
	assay_entry ae,source s
	WHERE ac.assay_cell_id = ae.assay_cell_id
	AND ae.source_id = s.source_id
	AND assay_name='{ASSAY_NAME}'
	AND LOWER(source_name)='{SOURCE_NAME.lower()}'""")
	for line in res:
		line['cell_line']=get_cell_info(line['cell_acc'])
	return res







# // $[API]
# // Title: get assay target information
# // Function: get_assay_target
# // Description: Get all information about a target used in an assay
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL967748 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Return: Assay target record
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py get_assay_target -ASSAY_NAME 'CHEMBL967748' -SOURCE_NAME 'CHEMBL'
# // Example: python3.12 biorels_api.py get_assay_target -ASSAY_NAME 'CHEMBL1061685' -SOURCE_NAME 'CHEMBL'
# // $[/API]
def get_assay_target(ASSAY_NAME,SOURCE_NAME):
	res=run_query(f"""SELECT at.*,att.*
	FROM assay_target at
	LEFT JOIN taxon t ON t.taxon_id = at.taxon_id,
	assay_target_type att, assay_entry ae,source s
	WHERE att.assay_target_type_id = at.assay_target_type_id
	AND at.assay_target_id = ae.assay_target_id
	AND ae.source_id = s.source_id
	AND assay_name='{ASSAY_NAME}'
	AND LOWER(source_name)='{SOURCE_NAME.lower()}'""")
	for line in res:
		query=f"""SELECT is_homologue,accession, sequence_md5sum, iso_id, gene_id
		FROM assay_target_protein_map atpm, assay_protein ap
		LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
		LEFT JOIN prot_entry pe ON pe.prot_entry_id = ps.prot_entry_id
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = ap.gn_entry_id
		WHERE atpm.assay_protein_id = ap.assay_protein_id
		AND atpm.assay_target_id = {line['assay_target_id']}"""
		line['protein']=run_query(query)
		for l in line['protein']:
			if l['gene_id']!=None:
				
				l['gene']=get_gene_by_gene_id(l['gene_id'])
			if l['iso_id']!=None:
				l['isoform']=get_isoform_info(l['iso_id'])
		query=f"""SELECT  genetic_description,tax_id,gene_seq_name,gene_seq_Version,transcript_name,transcript_version, accession, sequence,gene_id,tax_id,is_homologue
		 FROM assay_Target_genetic_map atgm, assay_genetic ag
		 LEFT JOIN taxon t ON t.taxon_id = ag.taxon_id
		 LEFT JOIN gene_seq gs ON gs.gene_seq_id = ag.gene_seq_id
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = gs.gn_entry_id
		LEFT JOIN transcript tr ON tr.transcript_id = ag.transcript_id
		WHERE atgm.assay_genetic_id = ag.assay_genetic_id
		AND atgm.assay_target_id = {line['assay_target_id']}"""
		line['genetic']=run_query(query)
		for l in line['genetic']:
			if l['tax_id']!=None:
				l['taxon']=get_taxon_by_tax_id(l['tax_id'],False)
			if l['gene_id']!=None:
				l['gene']=get_gene_by_gene_id(l['gene_id'])
			if l['transcript_name']!=None:
				l['transcript']=search_transcript(l['transcript_name'])
	return res


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - VARIANT //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: get assay variant information
# // Function: get_assay_variant
# // Description: Get all information about a variant used in an assay
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL1218392 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Return: Assay variant record
# // Ecosystem: Assay:assay
# // Example: python3.12 biorels_api.py get_assay_variant -ASSAY_NAME 'CHEMBL1218392' -SOURCE_NAME 'CHEMBL'
# // $[/API]
def get_assay_variant(ASSAY_NAME,SOURCE_NAME):
	query=f"""SELECT mutation_list,iso_id, ac,av.assay_variant_id FROM assay_Variant av
	LEFT JOIN prot_seq ps ON ps.prot_seq_Id = av.prot_Seq_id, assay_entry ae, source s
	WHERE av.assay_variant_id = ae.assay_variant_id
	AND ae.source_id = s.source_id
	AND assay_name='{ASSAY_NAME}'
	AND LOWER(source_name)='{SOURCE_NAME.lower()}'"""
	data=run_query(query)
	if data==[]: return data
	for line in data:
		line['isoform']=get_isoform_info(line['iso_id'])
		query=f"""SELECT * 
		FROM assay_variant_pos avp
		LEFT JOIN variant_protein_map vpm ON vpm.variant_protein_id = avp.variant_protein_id
		LEFT JOIN prot_seq_pos psp ON psp.prot_seq_pos_id = vpm.prot_seq_pos_id
		LEFT JOIN prot_seq ps ON ps.prot_seq_id = psp.prot_seq_id
		LEFT JOIN so_entry so ON so.so_entry_Id = vpm.so_entry_id
		WHERE avp.assay_variant_id = {line['assay_variant_id']}"""
		line['position']=run_query(query)
	return data




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - GENE //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: get all assay by gene
# // Function: get_assay_by_gene
# // Description: Get all assays for a given gene ID
# // Parameter: GENE_ID | Gene ID | string | 1017 |  required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Genomics:gene
# // Example: python3.12 biorels_api.py get_assay_by_gene -GENE_ID '1017'
# // $[/API]
def get_assay_by_gene(GENE_ID,COMPLETE=False):
	query=f"""SELECT DISTINCT assay_name,source_name
	FROM source s,assay_entry ae
	LEFT JOIN assay_target_protein_map atpm ON ae.assay_target_id = atpm.assay_target_id
	LEFT JOIN  assay_protein ap ON atpm.assay_protein_id = ap.assay_protein_id
	LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
	LEFT JOIN gn_prot_map pe ON pe.prot_entry_id = ps.prot_entry_id
	LEFT JOIN gn_entry ge ON ge.gn_entry_id = ap.gn_entry_id
	LEFT JOIN gn_Entry ger ON ger.gn_entry_id = pe.gn_entry_id
	LEFT JOIN assay_Target_genetic_map atgm ON ae.assay_target_id = atgm.assay_target_id
	LEFT JOIN assay_Genetic ag ON ag.assay_genetic_id = atgm.assay_genetic_id
	LEFT JOIN gene_seq gs ON gs.gene_seq_id = ag.gene_seq_id
	LEFT JOIN gn_entry ge2 ON ge2.gn_entry_id = gs.gn_entry_id
	WHERE ae.source_id = s.source_id
	AND (ge.gene_id = {GENE_ID} OR ger.gene_id = {GENE_ID} OR ge2.gene_id = {GENE_ID})"""
	res=run_query(query)
	if not COMPLETE: return res
	data=[]
	list={}
	for line in res:
		if line['source_name'] not in list:
			list[line['source_name']]=[]
		list[line['source_name']].append(line['assay_name'])
	for source in list:
		data.append(get_assay_information(list[source],source))
	return data


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - PROTEIN //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: get all assay by protein accession
# // Function: get_assay_by_prot_accession
# // Description: Get all assays for a given protein accession
# // Parameter: AC | Protein accession | string | P24941 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Proteomic:protein
# // Example: python3.12 biorels_api.py get_assay_by_prot_accession -AC 'P24941'
# // $[/API]
def get_assay_by_prot_accession(AC,COMPLETE=False):
	query=f"""SELECT DISTINCT assay_name,source_name
	FROM source s,assay_entry ae, assay_target_protein_map atpm, assay_protein ap
	LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
	LEFT JOIN prot_entry pe ON pe.prot_entry_id = ps.prot_entry_id
	LEFT JOIN prot_Ac pa ON pa.prot_entry_id = pe.prot_entry_id
	WHERE ae.assay_target_id = atpm.assay_target_id
	AND atpm.assay_protein_id = ap.assay_protein_id
	AND ae.source_id = s.source_id
	AND (accession = '{AC}' OR ac= '{AC}')"""
	res=run_query(query)
	if not COMPLETE: return res
	data=[]
	list={}
	for line in res:
		if line['source_name'] not in list:
			list[line['source_name']]=[]
		list[line['source_name']].append(line['assay_name'])
	for source in list:
		data.append(get_assay_information(list[source],source))
	return data



# // $[API]
# // Title: search assay by multiple parameter
# // Function: search_assay
# // Description: Search assay by multiple parameters 'DESCRIPTION','TAX_ID','TYPE','CATEGORY','TISSUE','ANATOMY','CELL_ACC','GENE_ID','AC','VARIANT'
# // Parameter: PARAMS | Parameters | multi_array | | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay-Genomic-Proteomic-Disease_anatomy:assay|gene|protein|isoform|transcript|cell line|tissue|anatomy
# // Example: python3.12 biorels_api.py search_assay -PARAMS 'DESCRIPTION=BRCA1'
# // Example: python3.12 biorels_api.py search_assay -PARAMS 'DESCRIPTION=BRCA1,TAX_ID=9606'
# // Example: python3.12 biorels_api.py search_assay -PARAMS 'DESCRIPTION=BRCA1,TAX_ID=9606,TYPE=Binding'
# // Example: python3.12 biorels_api.py search_assay -PARAMS 'DESCRIPTION=BRCA1,TAX_ID=9606,TYPE=Binding,CATEGORY=Functional'
# // Example: python3.12 biorels_api.py search_assay -PARAMS 'TISSUE=Valve'
# // Example: python3.12 biorels_api.py search_assay -PARAMS 'CELL_ACC=A549'
# // $[/API]
def search_assay(PARAMS,COMPLETE=False):
	TABLES={}
	TABLES['assay_entry ae']=True
	TABLES['source s']=True
	CONDITIONS=[]
	JOIN={}
	JOIN['ae.source_id = s.source_id']=True
	PRESEL={}
	for KEY in PARAMS:
		match KEY:
			case 'DESCRIPTION':
				s= '%\') OR LOWER(ae.assay_description) LIKE LOWER(\'%'
				CONDITIONS.append(f"( LOWER(ae.assay_description) LIKE LOWER('%{s.join(PARAMS[KEY])}%'))")
			case 'NAME':
				s= '%\') OR LOWER(ae.assay_name) LIKE LOWER(\'%'
				CONDITIONS.append(f"( LOWER(ae.assay_name) LIKE LOWER('%{s.join(PARAMS[KEY])}%'))")
			case 'TAX_ID':
				TABLES['taxon t']=True
				JOIN['t.taxon_id = ae.taxon_id']=True
				CONDITIONS.append(f"t.tax_id IN ('{','.join(PARAMS[KEY])}')")
			case 'SOURCE':
				CONDITIONS.append(f"LOWER(s.source_name) IN ('{','.join(map(str.lower,PARAMS[KEY]))}')")
			case 'TYPE':
				TABLES['assay_type at']=True
				JOIN['at.assay_type_id = ae.assay_type']=True
				CONDITIONS.append(f"LOWER(at.assay_desc) IN ('{','.join(map(str.lower,PARAMS[KEY]))}')")
			case 'CATEGORY':
				CONDITIONS.append(f"LOWER(ae.assay_category) IN ('{','.join(map(str.lower,PARAMS[KEY]))}')")
			case 'ANATOMY_TAG':
				TABLES['assay_tissue at']=True
				TABLES['anatomy_entry an']=True
				JOIN['at.assay_tissue_id = ae.assay_tissue_id']=True
				JOIN['at.anatomy_entry_id = an.anatomy_entry_id']=True
				CONDITIONS.append(f"an.anatomy_tag IN ('{','.join(PARAMS[KEY])}')")
			case 'TISSUE':
				TABLES['assay_tissue at2 LEFT JOIN anatomy_entry an2 ON at2.anatomy_entry_id= an2.anatomy_entry_id']=True
				JOIN['at2.assay_tissue_id = ae.assay_tissue_id']=True
				s= '%\') OR LOWER(at2.assay_tissue_name) LIKE LOWER(\'%'
				s2= '%\') OR LOWER(an2.anatomy_name) LIKE LOWER(\'%'
				CONDITIONS.append(f"( LOWER(at2.assay_tissue_name) LIKE LOWER('%{s.join(PARAMS[KEY])}%') OR LOWER(an2.anatomy_name) LIKE LOWER('%{s2.join(PARAMS[KEY])}%'))")
			case 'CELL_ACC':
				list={}
				for V in PARAMS[KEY]:
					s=search_assay_by_cell_line(V)
					for l in s:
						
						list[l['assay_name']+'||'+l['source_name']]=True

				if list=={}: return []
				if PRESEL=={}: PRESEL=list
				else:
					for K in PRESEL:
						if K not in list: PRESEL[K]=False
			case 'GENE_ID':
				list={}
				for V in PARAMS[KEY]:
					s=get_assay_by_gene(V)
					for l in s:list[l['assay_name']+'||'+l['source_name']]=True
				if list=={}: return []
				if PRESEL=={}: PRESEL=list
				else:
					for K in PRESEL:
						if K not in list: PRESEL[K]=False
			case 'AC':
				list={}
				for V in PARAMS[KEY]:
					s=get_assay_by_prot_accession(V)
					for l in s:list[l['assay_name']+'||'+l['source_name']]=True
				if list=={}: return []
				if PRESEL=={}: PRESEL=list
				else:
					for K in PRESEL:
						if K not in list: PRESEL[K]=False

	if not CONDITIONS==[]:
		query='SELECT DISTINCT assay_name,source_name FROM '+",\n".join(TABLES)+' WHERE '+ "\n AND ".join(CONDITIONS)+' AND '+ "\n AND ".join(JOIN)
		res=run_query(query)
		if PRESEL=={}: 
			for line in res:
				PRESEL[line['assay_name']+'||'+line['source_name']]=True
		else:
			list={}
			for line in res:
				list[line['assay_name']+'||'+line['source_name']]=True
			for K in PRESEL:
				if K not in list: PRESEL[K]=False
	data=[]
	pprint.pprint(PRESEL)
	if not COMPLETE:
		for K in PRESEL:
			if PRESEL[K]: data.append(K.split('||'))
		return data
	list={}
	for K in PRESEL:
		T=K.split('||')
		if T[1] not in list: list[T[1]]=[]
		list[T[1]].append(T[0])
	
	for SOURCE_NAME in list:
		lt=get_batch_assay_info(SOURCE_NAME,list[SOURCE_NAME])
		for line in lt:
			data.append(line)	
	return data



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - ACTIVITY //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: get assay activity
# // Function: get_assay_activity
# // Description: Get all activity for a given assay name and source name
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL1218392 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Parameter: WITH_STRUCTURE | True if full molecular entity information is requested | boolean | false | optional | Default: false
# // Return: Assay activity record
# // Ecosystem: Assay-Molecular entity:molecular entity|assay|activity
# // Example: python3.12 biorels_api.py get_assay_activity -ASSAY_NAME 'CHEMBL1218392' -SOURCE_NAME 'CHEMBL'
# // $[/API]
def get_assay_activity(ASSAY_NAME,SOURCE_NAME,WITH_STRUCTURE=False):
	query=f"""SELECT me.*,aa.*,bo.bioassay_label, bo.bioassay_tag_id
	 FROM molecular_entity me,activity_entry aa
	 LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= bao_endpoint,assay_entry ae,source s
	WHERE aa.assay_entry_id = ae.assay_entry_id
	AND me.molecular_entity_id = aa.molecular_entity_id
	AND ae.source_id = s.source_id
	AND assay_name='{ASSAY_NAME}'
	AND LOWER(source_name)='{SOURCE_NAME.lower()}'"""
	res=run_query(query)
	if not WITH_STRUCTURE: return res
	for line in res:
		line['molecular_entity']=get_molecular_entity(line['molecular_entity_hash'])
	return res





def get_batch_assay_info(SOURCE_NAME,LIST_ASSAY,WITH_EXTENDED_ASSAY_INFO=False):
	query=f"""SELECT s.source_name, ae.* ,scientific_name,tax_id,bo.*
	 FROM source s,assay_entry ae
	 LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= ae.bioassay_onto_entry_id
	LEFT JOIN taxon t on t.taxon_id = ae.taxon_id
	LEFT JOIN assay_type at ON at.assay_type_id = ae.assay_type
	LEFT JOIN assay_Confidence cs ON cs.confidence_score = ae.confidence_score
	WHERE s.source_id = ae.source_id
	AND LOWER(source_name)='{SOURCE_NAME.lower()}'
	AND assay_name  IN ('{'\',\''.join(LIST_ASSAY)}')"""
	tmp=run_query(query)
	if not WITH_EXTENDED_ASSAY_INFO: return tmp
	data={}
	REL={'ASSAY_CELL':{},'ASSAY_TISSUE':{},'ASSAY_TARGET':{},'ASSAY_VARIANT':{}}
	V=0
	for K in tmp:
		data[V]=K
		if K['assay_cell_id']!=None:
			if K['assay_cell_id'] not in REL['ASSAY_CELL']: REL['ASSAY_CELL'][K['assay_cell_id']]=[]
			REL['ASSAY_CELL'][K['assay_cell_id']].append(V)
		if K['assay_tissue_id']!=None: 
			if K['assay_tissue_id'] not in REL['ASSAY_TISSUE']: REL['ASSAY_TISSUE'][K['assay_tissue_id']]=[]
			REL['ASSAY_TISSUE'][K['assay_tissue_id']].append(V)
		if K['assay_target_id']!=None: 
			if K['assay_target_id'] not in REL['ASSAY_TARGET']: REL['ASSAY_TARGET'][K['assay_target_id']]=[]
			REL['ASSAY_TARGET'][K['assay_target_id']].append(V)
		if K['assay_variant_id']!=None: 
			if K['assay_variant_id'] not in REL['ASSAY_VARIANT']: REL['ASSAY_VARIANT'][K['assay_variant_id']]=[]
			REL['ASSAY_VARIANT'][K['assay_variant_id']].append(V)
		V+=1

	
	if REL['ASSAY_CELL']!={}:
		res=run_query(f"""SELECT assay_cell_id, ce.cell_name,cell_description,
		cell_source_tissue,chembl_id as assay_cell_chembl_id,
		cell_acc,scientific_name as cell_taxon_scientific_name,tax_id as cell_Tax_id, rank as cell_tax_rank
		FROM assay_cell ac
		LEFT JOIN taxon t on t.taxon_id =ac.taxon_Id
		LEFT JOIN cell_entry ce ON ce.cell_entry_Id = ac.cell_entry_Id
		WHERE assay_cell_id IN ({','.join([str(x) for x in REL['ASSAY_CELL'].keys()])})""")
		for line in res:
			for K in REL['ASSAY_CELL'][line['assay_cell_id']]:
				for X in line:
					data[K][X]=line[X]
					


	if REL['ASSAY_TISSUE']!={}:
		res=run_query(f"""SELECT assay_tissue_id,anatomy_tag,anatomy_name,assay_tissue_name
		FROM assay_tissue at
		LEFT JOIN anatomy_entry an ON an.anatomy_entry_id = at.anatomy_entry_id
		WHERE assay_tissue_id IN ({','.join([str(x) for x in REL['ASSAY_TISSUE'].keys()])})""")
		for line in res:
			for K in REL['ASSAY_TISSUE'][line['assay_tissue_id']]:
				for X in line:
					data[K][X]=line[X]
					
	if REL['ASSAY_TARGET']!={}:
		res=run_query(f"""SELECT assay_target_id,assay_target_name,assay_target_longname,
		species_group_flag,assay_target_Type_name,assay_target_type_desc,
		assay_Target_type_parent,scientific_name as assay_target_taxon_name,
		tax_id as assay_target_tax_id,rank as assay_target_tax_rank
		FROM assay_target at
		LEFT JOIN taxon t ON t.taxon_id=at.taxon_id
		LEFT JOIN assay_target_type att ON att.assay_target_type_id = at.assay_target_type_id
		WHERE assay_target_id IN ({','.join([str(x) for x in REL['ASSAY_TARGET'].keys()])})""")
		targets = {}
		for line in res:
			targets[line['assay_target_id']] = line
		

		query = f"""SELECT is_homologue,accession, sequence_md5sum, iso_id, gene_id,atpm.assay_target_id
		FROM assay_target_protein_map atpm, assay_protein ap
		LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
		LEFT JOIN prot_entry pe ON pe.prot_entry_id = ps.prot_entry_id
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = ap.gn_entry_id
		WHERE atpm.assay_protein_id = ap.assay_protein_id
		AND atpm.assay_target_id IN ({','.join([str(x) for x in REL['ASSAY_TARGET'].keys()])})"""
		tmp = run_query(query)
		for line in tmp:
			if line['gene_id']!=None: line['gene'] = get_gene_by_gene_id(line['gene_id'])
			if line['iso_id']!=None: line['isoform'] = get_isoform_info(line['iso_id'])
			if 'protein' not in targets[line['assay_target_id']] : targets[line['assay_target_id']]['protein'] = []
			targets[line['assay_target_id']]['protein'].append(line)
	


		query=f"""SELECT genetic_description,tax_id,gene_seq_name,gene_seq_Version,
		transcript_name,transcript_version, accession, sequence,gene_id,tax_id,is_homologue, atgm.assay_target_id
		 FROM assay_Target_genetic_map atgm, assay_genetic ag
		LEFT JOIN taxon t ON t.taxon_id = ag.taxon_id
		LEFT JOIN gene_seq gs ON gs.gene_seq_id = ag.gene_seq_id
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = gs.gn_entry_id
		LEFT JOIN transcript tr ON tr.transcript_id = ag.transcript_id
		WHERE atgm.assay_genetic_id = ag.assay_genetic_id
		AND atgm.assay_target_id IN ({','.join([str(x) for x in REL['ASSAY_TARGET'].keys()])})"""
		tmp=run_query(query)
		for line in tmp:
			if line['tax_id']!=None: line['taxon'] = get_taxon_by_tax_id(line['tax_id'],False)
			if line['gene_id']!=None: line['gene'] = get_gene_by_gene_id(line['gene_id'])
			if line['transcript_name']!=None: line['transcript'] = search_transcript(line['transcript_name'])
			targets[line['assay_target_id']]['genetic'].append(line)
		



		if targets!=[]:
			for assay_target_id in targets.keys():
				for K in REL['ASSAY_TARGET'][assay_target_id]:
						data[K]['target']=targets[assay_target_id]
						

		if REL['ASSAY_VARIANT']!={}:

			query=f"""SELECT mutation_list,iso_id, ac,av.assay_variant_id FROM assay_Variant av
			LEFT JOIN prot_seq ps ON ps.prot_seq_Id = av.prot_Seq_id
			WHERE av.assay_variant_id IN ({','.join([str(x) for x in REL['ASSAY_VARIANT'].keys()])})"""
			tmp=run_query(query)
			if tmp!=[]:
				for line in tmp:
					line['isoform']=get_isoform_info(line['iso_id'])
					query=f"""SELECT * 
					FROM assay_variant_pos avp
					LEFT JOIN variant_protein_map vpm ON vpm.variant_protein_id = avp.variant_protein_id
					LEFT JOIN prot_seq_pos psp ON psp.prot_seq_pos_id = vpm.prot_seq_pos_id
					LEFT JOIN prot_seq ps ON ps.prot_seq_id = psp.prot_seq_id
					LEFT JOIN so_entry so ON so.so_entry_Id = vpm.so_entry_id
					WHERE avp.assay_variant_id = {line['assay_variant_id']}"""
					line['position']=run_query(query)
				for line in tmp:
					for K in REL['ASSAY_VARIANT'][line['assay_variant_id']]:
						data[K]['VARIANT']=line

					
	return data.values()






# ///////////////////////////////////////////////////////////////////////////////////////////////////
# /////////////////////////////////// ACTIVITY - MOLECULAR ENTITY ///////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: get assay activity by molecular entity
# // Function: get_molecular_entity_activity
# // Description: Get all activity for a given molecular entity hash
# // Parameter: MOLECULAR_ENTITY_HASH | Molecular entity hash | string | d8c4c21996d99a71d75cf788d964b6cf | required
# // Parameter: WITH_ASSAY | True if full assay information is requested | boolean | false | optional | Default: false
# // Return: Assay activity record
# // Ecosystem: Assay:assay;Molecular entity:molecular entity|activity
# // Example: python3.12 biorels_api.py get_molecular_entity_activity -MOLECULAR_ENTITY_HASH 'd8c4c21996d99a71d75cf788d964b6cf'
# // $[/API]
def get_molecular_entity_activity(MOLECULAR_ENTITY_HASH,WITH_ASSAY=False,WITH_EXTENDED_ASSAY_INFO=False):
	query=f"""SELECT me.*,aa.*,bo.bioassay_label, bo.bioassay_tag_id, source_name,assay_name
	 FROM molecular_entity me,activity_entry aa
	 LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= bao_endpoint,
	 assay_entry ae,source s
	WHERE aa.molecular_entity_id = me.molecular_entity_id
	AND ae.assay_entry_id = aa.assay_entry_id
	AND ae.source_id = s.source_id
	AND me.molecular_entity_hash = '{MOLECULAR_ENTITY_HASH}'"""
	res=run_query(query)
	if not WITH_ASSAY: return res
	
	LIST_ASSAY={}
	K=0
	for line in res:
		if line['source_name'] not in LIST_ASSAY:
			LIST_ASSAY[line['source_name']]={}
		if line['assay_name'] not in LIST_ASSAY[line['source_name']]:
			LIST_ASSAY[line['source_name']][line['assay_name']]=[]
		LIST_ASSAY[line['source_name']][line['assay_name']].append(K)
		K+=1
	for SOURCE_NAME in LIST_ASSAY:

			tmp=get_batch_assay_info(SOURCE_NAME,LIST_ASSAY[SOURCE_NAME].keys(),WITH_EXTENDED_ASSAY_INFO)
			for r in tmp:
				for k in LIST_ASSAY[SOURCE_NAME][r['assay_name']]:
					for X in r:
						res[k][X]=r[X]
	return res



# // $[API]
# // Title: get scaffold activity
# // Function: get_scaffold_activity
# // Description: Get all activity for a given scaffold smiles
# // Parameter: SCAFFOLD_SMILES | Scaffold smiles | string | c1ncncc1 | required
# // Parameter: WITH_ASSAY | True if full assay information is requested | boolean | false | optional | Default: false
# // Parameter: WITH_EXTENDED_ASSAY_INFO | True if extended assay information is requested | boolean | false | optional | Default: false
# // Return: Assay activity record
# // Ecosystem: Assay:assay;Molecular entity:molecular entity|activity|scaffold
# // Example: python3.12 biorels_api.py get_scaffold_activity -SCAFFOLD_SMILES 'c1ncncc1'
# // $[/API]
def get_scaffold_activity(SCAFFOLD_SMILES,WITH_ASSAY=False,WITH_EXTENDED_ASSAY_INFO=False):
	query=f"""SELECT me.*,aa.*,bo.bioassay_label, bo.bioassay_tag_id, source_name,assay_name
	 FROM molecular_entity me,sm_entry se, sm_molecule sm, sm_scaffold sc,activity_entry aa
	 LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= bao_endpoint,
	 assay_entry ae,source s
	WHERE aa.molecular_entity_id = me.molecular_entity_id
	AND me.molecular_structure_hash=se.md5_hash
	AND sm.sm_scaffold_id = sc.sm_scaffold_id
	AND se.sm_molecule_id = sm.sm_molecule_id
	AND sc.scaffold_smiles = '{SCAFFOLD_SMILES}'"""
	res=run_query(query)
	if not WITH_ASSAY : return res
	LIST_ASSAY={}
	K=0
	for line in res:
		if line['source_name'] not in LIST_ASSAY:
			LIST_ASSAY[line['source_name']]={}
		if line['assay_name'] not in LIST_ASSAY[line['source_name']]:
			LIST_ASSAY[line['source_name']][line['assay_name']]=[]
		LIST_ASSAY[line['source_name']][line['assay_name']].append(K)
		K+=1
	for SOURCE_NAME in LIST_ASSAY:
		tmp=get_batch_assay_info(SOURCE_NAME,LIST_ASSAY[SOURCE_NAME].keys(),WITH_EXTENDED_ASSAY_INFO)
		for r in tmp:
			for k in LIST_ASSAY[SOURCE_NAME][r['assay_name']]:
				for X in r:
					res[k][X]=r[X]
	return res




