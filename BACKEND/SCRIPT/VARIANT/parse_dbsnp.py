# /usr/bin/env/ python
# -*- coding: utf-8 -*-

import json
import argparse
from  Bio.Seq import Seq
#from Bio.Alphabet import generic_dna
from pathlib import Path
import os
import sys
#import psutil

"""
Program to create three tsv files from one large dbsnp json file downloaded from NCBI. 
"""

DEBUG = False 

class RSID:
    """ class to describe an RSID entry """
    def __init__(self, refsnp_id, last_update,create_date, citations=[]):
        self.last_update = last_update.split('T')[0] if last_update else '0000-00-00'
        self.create_date = create_date.split('T')[0] if create_date else '0000-00-00'
        self.citations = citations
        self.refsnp_id = refsnp_id
        self.alleles = []
        self.genes = []
        self.transcripts = []
       
    
    def get_alleles(self,dbsnp):
        """get the DNA alleles for this variant and create objects """
        reference = ''
        alleles_by_reference = dbsnp['primary_snapshot_data']['placements_with_allele']
        var_type =  dbsnp['primary_snapshot_data']['variant_type']
        for ref in alleles_by_reference:
            # we only care about the DNA alleles
            if ref['placement_annot']['seq_type'] != 'refseq_chromosome':
                continue
            # Sometimes chrMT will report both 38 and 37 in the same placement annot
            if ref['placement_annot']['seq_id_traits_by_assembly']:
                reference = ref['placement_annot']['seq_id_traits_by_assembly'][-1]['assembly_name']

            # We only care about the latest reference at the moment
            if condense_reference(reference) != 38:
                continue
            for allele in ref['alleles']:
                allele_var_type = var_type
                seq_id = allele['allele']['spdi']['seq_id']
                pos = str(int(allele['allele']['spdi']['position'])+1)
                ref = allele['allele']['spdi']['deleted_sequence']
                alt = allele['allele']['spdi']['inserted_sequence']
                # create an object for this allele
                if ref == alt:
                    allele_var_type = 'ref'
                cur_allele = DNA_ALLELE(allele_var_type,reference,seq_id,pos,alt,ref)
                self.alleles.append(cur_allele)
    
    # Get the transcripts that this variant modifies
    def get_transcripts(self,allele_dict):
        var_list = allele_dict['primary_snapshot_data']['placements_with_allele']
        for x in var_list:
            if x['placement_annot']['mol_type'] == 'rna':
                is_aln_opposite_orientation = x['placement_annot']['is_aln_opposite_orientation']
                entry = x['alleles']
                for allele in entry:
                    pos =  str(int(allele['allele']['spdi']['position'])+1)
                    ref = allele['allele']['spdi']['deleted_sequence']
                    alt = allele['allele']['spdi']['inserted_sequence']
                    seq_id = allele['allele']['spdi']['seq_id']
                    is_mismatch = x['placement_annot']['is_mismatch'] if ref != alt else False
                   # print("adding transcript from rsid", seq_id, pos, ref, alt, is_aln_opposite_orientation, is_mismatch )
                    # It will report the reference allele that was a mismatch, too... so G>G when it's supposed to be
                    # A>A - no other info is provided for consequence or frequency so I don't see why I should include
                    if alt != ref:
                        current_transcript = Transcript(seq_id,pos,ref,alt, '','',is_mismatch)
                        current_transcript.deleted = ref
                        current_transcript.opposite_orientation = is_aln_opposite_orientation
                       # print("reversing", current_transcript.deleted, current_transcript.inserted)
                        current_transcript.allele_strand_conversion('-' if is_aln_opposite_orientation else '+')
                       # print("have they changed? ", current_transcript.deleted, current_transcript.inserted)
                        self.transcripts.append(current_transcript)


    def __str__(self):
        return '\t'.join([self.refsnp_id, self.create_date, self.last_update])
        
class DNA_ALLELE:
    """Class to represent a DNA allele in a refsnp ID"""
    def __init__(self,type,reference,seq_id,position,inserted_sequence,deleted_sequence):
        self.reference = str(condense_reference(reference))
        self.seq_id = str(condense_seq(seq_id))
        self.inserted_sequence = inserted_sequence
        self.deleted_sequence = deleted_sequence
        self.frequencies = []
        self.position = position
        self.clinvar_id = []
        self.transcripts = []
        self.type = type
       
    
    def allele_match(self,allele_dict):
        """method to find if two alleles are the same"""
        allele_annotations = allele_dict['primary_snapshot_data']['allele_annotations']
        allele = []
        for allele in allele_annotations:
            # This seems to be the only way to check which allele we're working with
            if self.inserted_sequence and allele['frequency']:
                alt = allele['frequency'][0]['observation']['inserted_sequence']
                if alt == self.inserted_sequence:
                    return allele


    def get_clinvar_ids(self,allele_dict):
        """
        get the clinvar RCV IDs that are associated with this allele"""
        ids = []
        matching_allele = ''
        matching_allele = self.allele_match(allele_dict)
        if matching_allele and matching_allele['clinical']:
            clinical_significance = matching_allele['clinical']
            if clinical_significance:
                # There can be more than one clinvar ID per variant]
                for clinvar_entry in clinical_significance:
                    # We report the RCVs, they are versioned X.x format
                    self.clinvar_id.append(clinvar_entry['accession_version'])

    
    
    def get_frequencies(self,allele_dict):
        """Get the population frequencies that are associated with this allele"""
        matching_allele = self.allele_match(allele_dict)
        if matching_allele and self.inserted_sequence:
            for freq in matching_allele['frequency']:
                study_name = freq['study_name']
                allele_count = freq['allele_count']
                total_count = freq['total_count']
                current_frequency = Frequency(study_name,allele_count,total_count)
                self.frequencies.append(current_frequency)
                
    def __str__(self):
        return '\t'.join([self.reference,self.seq_id,
                          self.deleted_sequence,self.inserted_sequence,str(self.position),self.type])

    
class Frequency:
    """Class representing the frequencies that map to a specific allele object"""
    def __init__(self,study_name,allele_count,total_count):
        self.study_name = study_name
        self.allele_count = allele_count
        self.total_count = total_count
        self.freq = allele_count/float(total_count)
        
        
    def __str__(self):
        return ','.join([self.study_name,str(self.allele_count),str(self.total_count),str("{:.3f}".format(self.freq))])

    
class Protein:
    """Class representing a protein change for this allele"""
    def __init__(self,seq_id,pos='',inserted='',deleted='', so_id = ''):
        self.seq_id = seq_id
        self.position = pos
        self.inserted = inserted
        self.deleted = deleted
        self.so_id = so_id
    
    
    def __str__(self):
        return '\t'.join([str(x) for x in [self.seq_id, self.position,self.deleted,self.inserted, self.so_id]])

    
class Transcript:
    """Class representing a transcript that was modified by this allele change"""

    #In the case where there is no protein included
    def __init__(self,seq_id, pos,ref,alt, so, protein, mismatch):
        self.seq_id = seq_id
        seq_and_version = get_seq_version(seq_id)
        self.seq = seq_and_version[0]
        self.version = seq_and_version[1]
        self.inserted = alt
        self.deleted = ref
        self.position = pos
        self.so = so
        self.protein = protein
        self.mismatch = mismatch
        self.opposite_orientation = False

    def get_variant_from_codon(self):
        """Reported coding change variants will give the entire codon, we just want the location of the variant"""
        #If we are dealing with a substitution, Note: This will not do anything in the case of an indel coding change
        allele_inserted = ''
        start_found = False
        allele_deleted = ''
        if len(self.deleted) == 3 and len(self.inserted) == 3:
            for x in range(0,3):
                if self.inserted[x] is not self.deleted[x]:
                    allele_inserted += self.inserted[x]
                    allele_deleted += self.deleted[x]
                    if not start_found:
                        self.position = str(int(self.position) + x)
                        start_found = True
            if allele_inserted and allele_deleted:
                self.inserted = allele_inserted
                self.deleted = allele_deleted

    def set_position(self, allele_dict):
        """
        SPDI reports positions left shifted, we want to right-shift them for VCF notation joins
        :param allele_dict: dictionary of the alleles from JSON in memory
        :return:
        """
        var_list = allele_dict['primary_snapshot_data']['placements_with_allele']
        for x in var_list:
            if x['placement_annot']['mol_type'] == 'rna':
                entry = x['alleles']
                if DEBUG:
                    print("new entry")
                for allele in entry:
                    if DEBUG:
                        print(allele)
                    pos = str(int(allele['allele']['spdi']['position'])+1)
                    ref = allele['allele']['spdi']['deleted_sequence']
                    alt = allele['allele']['spdi']['inserted_sequence']
                    seq_id = allele['allele']['spdi']['seq_id']
                    if seq_id == self.seq_id:
                        self.position = pos
                        if not self.inserted and ref is not alt:
                            self.inserted = alt
                        if not self.deleted:
                            self.deleted = ref
    
    

    def allele_strand_conversion(self, strand, reverse=False):
        """
        we want to be able to match up to the DNA variant which is pos strand by default
        :param strand: whether it's on the plus or minus strand
        :param reverse: whether we have already reversed and need to go back
        :return:
        """

        if self.inserted and self.deleted:
          #  print(self.deleted, self.inserted, strand, reverse)
            if strand == '-' or reverse:
                self.inserted = str(Seq(self.inserted).reverse_complement())
                self.deleted = str(Seq(self.deleted).reverse_complement())
         

    def __eq__(self, obj):
        return isinstance(obj, Transcript) and obj.seq_id == self.seq_id and obj.version == self.version and obj.position == self.position and obj.deleted == self.deleted and obj.inserted == self.inserted

    
    def __str__(self):
        return '\t'.join([str(x) for x in [self.seq, self.version,str(self.position),self.deleted,self.inserted,self.so]])

    
class Gene:
    """
    Class representing a gene as it pertains to this RSID
    """
    def __init__(self,gene_id,orientation):
        self.id = str(gene_id)
        self.orientation = '-' if orientation == 'minus' else '+'
        self.transcripts = []
        
        
    def get_protein_and_rnas(self,rna_dict,allele_dict):
        """
        In the JSON files, genes and their effects are reported separately from the allele placements
        :param rna_dict: dictionary of the RNAs in json format for this gene
        :return:
        """
        for rna in rna_dict:
            hgvs=''
            # We don't want to process non-variant entries
            if 'hgvs' in rna.keys():
                hgvs = rna['hgvs']
                if '=' in hgvs:
                    continue
            # Set our loop variables
            prot_ins = ''
            prot_pos = ''
            prot_del = ''
            sequence_ontology_id = ''
            protein_id = ''
            protein_so_id = ''
            sequence_ontology_name = ''
            # If a protein product was included in this change
            if 'product_id' in rna.keys():
                protein_id = rna['product_id']
                if 'protein' in rna.keys() and "codon_aligned_transcript_change" in rna.keys():
                    if 'sequence_ontology' in rna['protein'].keys() and rna['protein']['sequence_ontology']:
                        protein_so_id = rna['protein']['sequence_ontology'][0]['accession']
                    if 'frameshift' in rna['protein']['variant'].keys():
                         prot_pos = str(int(rna['protein']['variant']['frameshift']['position'])+1)
                         prot_ins = 'frameshift'
                    else:
                         prot_ins = rna['protein']['variant']['spdi']['inserted_sequence']
                         prot_del = rna['protein']['variant']['spdi']['deleted_sequence']
                         prot_pos = str(int(rna['protein']['variant']['spdi']['position'])+1)

            seq_id = rna['id']
            prot = Protein(protein_id, prot_pos, prot_ins, prot_del, protein_so_id)

            if rna['sequence_ontology']:
                sequence_ontology_id = rna['sequence_ontology'][0]['accession']
                sequence_ontology_name = rna['sequence_ontology'][0]['name']

            if "codon_aligned_transcript_change" in rna.keys():
                allele = rna['codon_aligned_transcript_change']['inserted_sequence']
                ref = rna['codon_aligned_transcript_change']['deleted_sequence']
                # have to convert SPDI position to VCF
                pos = str(int(rna['codon_aligned_transcript_change']['position'])+1)
                current_transcript = Transcript(seq_id, pos, ref, allele, sequence_ontology_id, prot, False)
                # if it's not an indel/frameshift (note that pos will not be right on frameshift)
                if len(ref) == len(allele):
                    current_transcript.get_variant_from_codon()
                self.transcripts.append(current_transcript)
            else:
                # we have a "noncoding" change
                if "UTR" in sequence_ontology_name:
                    var_list = allele_dict['primary_snapshot_data']['placements_with_allele']
                    for x in var_list:
                        if x['placement_annot']['mol_type'] == 'rna':
                            entry = x['alleles']
                            for allele in entry:
                                allele_pos = str(int(allele['allele']['spdi']['position']) + 1)
                                allele_ref = allele['allele']['spdi']['deleted_sequence']
                                allele_alt = allele['allele']['spdi']['inserted_sequence']
                                allele_seq_id = allele['allele']['spdi']['seq_id']
                                if seq_id == allele_seq_id:
                                # We should add this to the gene
                                    current_transcript = Transcript(seq_id, allele_pos, allele_ref, allele_alt, sequence_ontology_id, prot,
                                                                False)
                                    self.transcripts.append(current_transcript)
                else:
                    current_transcript = Transcript(seq_id, '','','', sequence_ontology_id,
                                                    prot,
                                                    False)
                    self.transcripts.append(current_transcript)

                    
    def __eq__(self, obj):
        return isinstance(obj, Gene) and self.id == obj.id and self.orientation == obj.orientation

    def __str__(self):
        return '\t'.join([self.id, self.orientation])


def condense_seq(seq_id):
    """
    Convert the chromsome ID to an integer if possible, to standardize how we report integers
    :param seq_id:
    :return:
    """
    if 'NC' in seq_id and '0000' in seq_id:
        stripped_seq = seq_id[3:].lstrip('0')
        seq_int = stripped_seq.split('.')[0]
        return seq_int
    #ChrMT is somewhat different in its presentation
    if seq_id == 'NC_012920.1':
        return 'MT'

    # in the case of an unlocalized sequence...
    return seq_id

def get_seq_version(seq):
    if '.' in seq:
        return [seq.split('.')[0],seq.split('.')[1]]
    if seq:
        return [seq,0]
    else:
        #TODO throw exception
        return [-1,-1]


def condense_reference(reference):
    """
    We want to standardize the reference genome value to 37 or 38
    :param reference: the string representing ref genome
    :return:
    """
    if 'GRCh38' in reference:
        return 38
    if 'GRCh37' in reference:
        return 37
    else:
        return reference

# For each RSID we want to build up its DNA alleles
def build_rsid_dna_variants(dbsnp_entry,current_rsid):
    current_rsid.get_alleles(dbsnp_entry)
    for allele in current_rsid.alleles:
        allele.get_frequencies(dbsnp_entry)
        allele.get_clinvar_ids(dbsnp_entry)

        
def build_rsid_transcript_gene(dbsnp_entry,current_rsid):
    allele_annotations = dbsnp_entry['primary_snapshot_data']['allele_annotations']
    if DEBUG:
        print("this is how many allele annotations: ", len(allele_annotations))

    for annotation in allele_annotations:
        if DEBUG:
            print('new anno: ', annotation)

        anno = annotation['assembly_annotation']
        for feature in anno:
            if DEBUG:
                print('we have this many genes: ', len(current_rsid.genes))
                print('this is how many genes the feature has ', len(feature['genes']))
            genes = feature['genes']
            for gene in genes:
                gene_id = gene['id']
                strand = gene['orientation']
                current_gene = Gene(gene_id,strand)
                current_gene.get_protein_and_rnas(gene['rnas'], dbsnp_entry)
                for transcript in current_gene.transcripts:
                    transcript.allele_strand_conversion(current_gene.orientation)
                if DEBUG:
                    print([str(x) for x in current_rsid.genes])
                if current_rsid.genes:
                    for g in current_rsid.genes:
                        if DEBUG:
                            print('gene id: ',str(gene_id))
                        if str(gene_id) == str(g.id):
                            if DEBUG:
                                print("already saw this gene")
                                print("transcript size: ", sys.getsizeof(current_gene.transcripts))
                            for trans in current_gene.transcripts:
                                if DEBUG:
                                    print(str(trans))
                                if trans not in g.transcripts:
                                    g.transcripts.append(trans)
                                    if DEBUG:
                                        print('we have now this many transcripts for ', len(g.transcripts))
                        #If this is a new gene to us
                        elif current_gene not in current_rsid.genes:
                            current_rsid.genes.append(current_gene)
                else:
                    current_rsid.genes.append(current_gene)
                if DEBUG:
                    print([str(x) for x in current_rsid.genes])
                    print('that was our genes')
                    print([str(x) for x in current_rsid.genes[0].transcripts])
                    print("that was our transcripts list")

                
def build_rsid_from_single_file(fname, outdir):
    """
    Taking a single large file, move through it in chunks parsing each RSID entry into multiple tables
    """
    rsids = []
    count = 1

    with open(fname,"r",encoding="utf-8") as f:
        while True:
            line = f.readline()
            #print(count)

            if not line and rsids:
                output_new_tables_to_file(rsids,fname, outdir)
                break
            if not line:
                break
            dbsnp_entry = json.loads(line)
            current_rsid = RSID(dbsnp_entry['refsnp_id'], dbsnp_entry['last_update_date'], dbsnp_entry['create_date'], dbsnp_entry['citations'])
            if DEBUG:
                print(str(current_rsid))
            current_rsid.get_transcripts(dbsnp_entry)
            #print('this rsid has ', len(current_rsid.transcripts))
            build_rsid_dna_variants(dbsnp_entry,current_rsid)
            build_rsid_transcript_gene(dbsnp_entry,current_rsid)
            rsids.append(current_rsid)
            #could run into an issue here if rsids gets out of hand in size, so we do it in chunks. 
            #for now the memory use is minimal.
            if count % 10000==0 :
                output_new_tables_to_file(rsids,fname, outdir)
                rsids *= 0 

            count+=1
            

def output_new_tables_to_file(rsids,fname, outdir):
    """
    Create new tables representing what we want in the database
    :param rsids: array representing the RSIDs objects to print
    :param fname: user-provided prefix. NCBI provides JSON in chromosome files
    :return:
    """
    
    # Get our chromosome name and write to files one by one
    # This could be done in a loop, it's an artifact of my debug process.
    base = os.path.basename(fname)
    chromosome = os.path.splitext(base)[0]

    dbsnp_pub_fh = open(outdir + chromosome + '-dbsnp_publication.tsv', 'a')
    output_dbsnp_publication_table(dbsnp_pub_fh, rsids)
    dbsnp_pub_fh.close()

    var_freq_fh = open(outdir + chromosome + '-variant_frequency.tsv', 'a')
    output_dbsnp_frequency_table(var_freq_fh, rsids)
    var_freq_fh.close()
    
    var_fh = open(outdir + chromosome + '-variant.tsv', 'a')
    output_dbsnp_variation_table(var_fh, rsids)
    var_fh.close()

    dbsnp_clinvar_fh = open(outdir + chromosome + '-dbsnp_clinvar.tsv', 'a')
    output_dbsnp_clinvar(dbsnp_clinvar_fh, rsids)
    dbsnp_clinvar_fh.close()

    trans_fh = open(outdir + chromosome + '-transcript.tsv', 'a')
    output_transcript_table(trans_fh, rsids)
    trans_fh.close()


def output_dbsnp_clinvar(file_handle,rsids):
    """
    Output clinvar associations for each RSID
    :param file_handle:
    :param rsid:
    :return:
    """
    for rsid in rsids:
        for dna_allele in rsid.alleles:
            if dna_allele.clinvar_id:
                for clinvar_id in dna_allele.clinvar_id:
                    file_handle.write('\t'.join([rsid.refsnp_id, clinvar_id, dna_allele.reference, dna_allele.seq_id,
                                                dna_allele.position, dna_allele.inserted_sequence, dna_allele.deleted_sequence]) + '\n')

#output table dbsnp_publication to a file
def output_dbsnp_publication_table(file_handle,rsids):
        for rsid in rsids:
            if rsid.citations:
                for citation in rsid.citations:
                    file_handle.write('\t'.join([str(rsid.refsnp_id),str(citation)])+'\n')

#output table dbsnp_variation to a file
def output_dbsnp_variation_table(file_handle,rsids):
        for rsid in rsids:
            for dna_allele in rsid.alleles:
                file_handle.write(rsid.refsnp_id + '\t' + str(dna_allele) + '\n')


# NOTE: sure we are printing only 38 for now
# the ALLELE has coding change transcripts and the GENE has all transcripts. We need to match them up!
def output_transcript_table(file_handle,rsids):
    for rsid in rsids:
    # get the dna allele
        unique_transcripts = []
        for gene in rsid.genes:
            for transcript in gene.transcripts:
            # we do not care about transcripts that have no variants
                if transcript.inserted == transcript.deleted and transcript.inserted and transcript.deleted:
                    continue
                if transcript not in unique_transcripts:
                    unique_transcripts.append(transcript)

        if unique_transcripts:
            # Match transcript to a variant
            for var in rsid.alleles:
                for transcript in unique_transcripts:
                    if var.inserted_sequence == var.deleted_sequence:
                        continue
                    empty = False
                    indel_match = False
                    for var_trans in rsid.transcripts:
                        if var_trans == transcript and var_trans.mismatch:
                            transcript.mismatch = True

                    if var.type == 'delins':
                        alt_size = len(var.inserted_sequence)
                        ref_size = len(var.deleted_sequence)
                        trans_alt_size = len(transcript.inserted)
                        trans_ref_size = len(transcript.deleted)
                        if alt_size < ref_size and trans_alt_size < trans_ref_size and trans_ref_size - trans_alt_size == ref_size - alt_size or  alt_size > ref_size and trans_alt_size > trans_ref_size and trans_alt_size - trans_ref_size == alt_size - ref_size: 
                            if var.inserted_sequence in transcript.inserted and var.deleted_sequence in transcript.deleted:
                                indel_match = True
                # This pops up with noncoding changes all the time
                    if not transcript.inserted and not transcript.deleted:
                        empty = True
                        
                # bases must be equal, or it must be an indel or it must be a mismatch
                    if var.reference == '38' and var.inserted_sequence != var.deleted_sequence and (var.inserted_sequence == transcript.inserted and
                                              var.deleted_sequence == transcript.deleted or transcript.mismatch or indel_match or empty):
                        #if gene.orientation == '-' and not empty:
                        if transcript.opposite_orientation and not empty:                        
# Convert back to the transcript allele to match with other tables that are stranded
                            transcript.allele_strand_conversion('-',True)
                        file_handle.write('\t'.join([rsid.refsnp_id, var.reference, var.seq_id, var.position,  var.deleted_sequence, var.inserted_sequence, transcript.so,
                                                transcript.position, transcript.deleted, transcript.inserted,
                                                transcript.seq, transcript.version])+ '\t' + str(transcript.protein) + '\n')


def output_dbsnp_frequency_table(file_handle, rsids):
    for rsid in rsids:
        for dna_allele in rsid.alleles:
            for frequency in dna_allele.frequencies:
                file_handle.write('\t'.join(
                    [rsid.refsnp_id, dna_allele.reference, dna_allele.seq_id, str(dna_allele.position),
                    dna_allele.deleted_sequence,
                    dna_allele.inserted_sequence, frequency.study_name, str(frequency.allele_count),
                    str(frequency.total_count)]) + '\n')


def command_line():
    """
    Command-line interface.
    :return: arguments via a parser.parse_args() object
    """

    class MyParser(argparse.ArgumentParser):
        """
        Override default behavior, print the whole help message for any CLI
        error.
        """

        def error(self, message):
            print("error: %s\n" % message, file=sys.stderr)
            self.print_help()
            sys.exit(2)

    parser = MyParser(description="Condense large dbsnp json file into 5 tsv files to be loaded into res.data.")
    parser.add_argument(
        "--filename",
        required= True,
        help="Full path to the directory the unzipped files are in (unix format)",
    )

    parser.add_argument(
        "--out",
        required=True,
        help="Full path to the intended output directory",
    )

    return parser.parse_args()

def main():
    arguments = command_line()
    filename = str(arguments.filename)
    outdir = str(arguments.out)
    if not outdir.endswith('/'):
        outdir = outdir + '/'
    if filename.endswith('.json'):
        build_rsid_from_single_file(filename, outdir)

if __name__ == '__main__':
    main()
