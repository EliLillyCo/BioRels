import os
import traceback

# Get root directories
TG_DIR = os.getenv('TG_DIR')
if TG_DIR is None:
    print('NO TG_DIR found')
    exit(1)
if not os.path.isdir(TG_DIR):
    print('TG_DIR value is not a directory ')
    exit(2)

from datetime import datetime
import sys
sys.path.append(os.getenv('TG_DIR')+"/BACKEND/SCRIPT/LIB_PYTHON/")
from datetime import datetime
from fct_utils import *
from fct_utils import DB_CONN
from loader import *
import pprint



# Create nested set representation that is going to assign boundary numbers.
# Let's say that the root has for boundary 1 10.
# The two childs:  A 2-5 and B 6-9
# And the A has a child C 3-4
# If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
# By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
# Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for the left side and <10 for the right side, leading to A B and C.
def def_levels(ENTRY, TAX_ID, LEVEL, VALUE, fp):
    LEVEL += 1
    VALUE += 1
    # Left boundary
    LEFT = VALUE
    print("LEVEL", LEVEL, "TAX_ID", TAX_ID, "VALUE", VALUE, "LEFT", LEFT, "RIGHT", VALUE, "ENTRY", ENTRY[TAX_ID][0],ENTRY[TAX_ID][4])
    tab = list(filter(None, ENTRY[TAX_ID][4].split("|")))
    for CHILD in tab:
        VALUE = def_levels(ENTRY, int(CHILD), LEVEL, VALUE, fp)
    # Right boundary
    VALUE += 1
    RIGHT = VALUE
    fp.write(f"{ENTRY[TAX_ID][0]}\t{LEVEL}\t{LEFT}\t{RIGHT}\n")
    return VALUE


def delete_taxons(TO_DEL, JOB_ID,DEP_TABLES):
    PREV = len(TO_DEL)
    print("DELETION OF ", PREV, " TAXONS")
    # Here we check if any entries that exist in the database but not in the files have been merged
    for TAX_ID, DBID in TO_DEL.items():
        print("DELETION OF TAXON", TAX_ID, 'DBID:', DBID)
        # Those functions can take a while. So we need to call them manually before deleting the taxon itself
        if not run_query_no_res(f"DELETE FROM assay_Target WHERE taxon_id={DBID}"):
            fail_process(JOB_ID + "A01", 'Unable to delete assay_target for  taxon dbid:' + str(DBID))
        if not run_query_no_res(f"DELETE FROM assay_entry WHERE taxon_id={DBID}"):
            fail_process(JOB_ID + "A01", 'Unable to delete assay_entry for  taxon dbid:' + str(DBID))
        if not run_query_no_res(f"DELETE FROM biorels_private.assay_Target WHERE taxon_id={DBID}"):
            fail_process(JOB_ID + "A01", 'Unable to delete assay_target for  taxon dbid:' + str(DBID))
        if not run_query_no_res(f"DELETE FROM biorels_private.assay_entry WHERE taxon_id={DBID}"):
            fail_process(JOB_ID + "A01", 'Unable to delete assay_entry for  taxon dbid:' + str(DBID))
        if not run_query_no_res(f"DELETE FROM prot_seq_al WHERE prot_seq_ref_id IN (SELECT prot_seq_Id FROM taxon t, prot_entry pe, prot_seq ps WHERE t.taxon_id = pe.taxon_id AND pe.prot_entry_id =ps.prot_entry_id AND t.taxon_id={DBID})"):
            fail_process(JOB_ID + "A01", 'Unable to delete prot seq ref al for  taxon dbid:' + str(DBID))
        if not run_query_no_res(f"DELETE FROM prot_seq_al WHERE prot_seq_comp_id IN (SELECT prot_seq_Id FROM taxon t, prot_entry pe, prot_seq ps WHERE t.taxon_id = pe.taxon_id AND pe.prot_entry_id =ps.prot_entry_id AND t.taxon_id={DBID})"):
            fail_process(JOB_ID + "A02", 'Unable to delete prot seq comp al for  taxon dbid:' + str(DBID))
        if not run_query_no_res(f"DELETE FROM prot_dom_al WHERE prot_dom_ref_id IN (SELECT prot_dom_id FROM taxon t, prot_entry pe, prot_dom pd WHERE t.taxon_id = pe.taxon_id AND pe.prot_entry_id =pd.prot_entry_Id AND t.taxon_id={DBID})"):
            fail_process(JOB_ID + "A03", 'Unable to delete prot dom ref al for  taxon dbid:' + str(DBID))
        if not run_query_no_res(f"DELETE FROM prot_dom_al WHERE prot_dom_comp_id IN (SELECT prot_dom_id FROM taxon t, prot_entry pe, prot_dom pd WHERE t.taxon_id = pe.taxon_id AND pe.prot_entry_id =pd.prot_entry_Id AND t.taxon_id={DBID})"):
            fail_process(JOB_ID + "A04", 'Unable to delete prot dom comp al for  taxon dbid:' + str(DBID))
        if TAX_ID in MERGED:
            # If so, then we first need to update the dependent table records to go from the previous taxon to the next one
            print("MERGED ENTRY:\t", TAX_ID, DBID, MERGED[TAX_ID], DATA[MERGED[TAX_ID]][0])
            for TBL, CNAME in DEP_TABLES.items():
                query = f"UPDATE {TBL} SET taxon_id = {DATA[MERGED[TAX_ID]][0]} WHERE taxon_id = {DBID}\n"
                # When gene is updated before taxonomy, a specific situation can happen.
                if not run_query_no_res(query):
                    if TBL != GLB_VAR['DB_SCHEMA'] + '.chromosome':
                        fail_process(JOB_ID + "A05", 'Unable to update the database ' + query)
        if not run_query_no_res(f"DELETE FROM taxon WHERE taxon_id ={DBID}"):
            fail_process(JOB_ID + "A06", 'Unable to delete tax id ' + str(TAX_ID))


def create_hierarchy():
    global DB_INFO
    global GLB_VAR
    global JOB_ID
    global DATA
    add_log("Create hierarchy")
    pprint.pp(DATA[1])
    # Now we create the hierarchy
    with open('tree.csv', 'w') as fp:
        if not fp:
            fail_process(JOB_ID + "B01", 'Unable to open tree.csv')
        def_levels(DATA, 1, -1, -1, fp)
    
    add_log("delete content of taxon tree")
    
    if not run_query_no_res("TRUNCATE TABLE taxon_tree"):
        fail_process(JOB_ID + "B02", 'Unable to truncate taxon_tree')

    add_log("load tree")

    FCAV_NAME = 'tree.csv'
    command = f"\\COPY {GLB_VAR['DB_SCHEMA']}.taxon_tree(taxon_id,tax_level,level_left,level_right) FROM '{FCAV_NAME}' (DELIMITER E'\\t', null \\\"NULL\\\", format CSV, HEADER)"

    print(DB_INFO['COMMAND'] + ' -c "' + command + '"\n')
    return_code = os.system(DB_INFO['COMMAND'] + ' -c "' + command + '"')

    if return_code != 0:
        fail_process(JOB_ID + "B03", 'Unable to insert tree')


def load_merged():
    MERGED = {}
    # merged.dmp contains the list of merged records
    with open('merged.dmp', 'r') as fp:
        if not fp:
            fail_process(JOB_ID + "C01", 'Unable to open merged.dmp')
        for line in fp:
            line = line.strip()
            if line == '':
                continue
            tab = line.split("|")
            if len(tab) != 3:
                continue
            FORMER = tab[0].strip()
            NEW = tab[1].strip()
            MERGED[FORMER] = NEW
    return MERGED




job_name = 'wh_taxonomy'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name


# File/parameters verifications:
add_log("Static file check")

if 'FTP_NCBI' not in GLB_VAR['LINK']:
    fail_process(JOB_ID + "001", 'FTP_NCBI path not set')
 
add_log("Create directory")
W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'])
if not os.path.isdir(W_DIR):
    fail_process(JOB_ID + "002", 'NO ' + W_DIR + ' found')
W_DIR = os.path.join(W_DIR, JOB_INFO['DIR'])
if not os.path.isdir(W_DIR) and not os.mkdir(W_DIR):
    fail_process(JOB_ID + "003", 'Unable to find and create ' + W_DIR)
W_DIR = os.path.join(W_DIR, datetime.now().strftime('%Y-%m-%d'))
if not os.path.isdir(W_DIR):
	os.mkdir(W_DIR)
	if not os.path.isdir(W_DIR):
         fail_process(JOB_ID + "004", 'Unable to create new process dir ' + W_DIR)
os.chdir(W_DIR)
if (os.getcwd() != W_DIR):
    fail_process(JOB_ID + "005", 'Unable to access process dir ' + W_DIR)

# Update process control directory to the current release so that the next job can use it
PROCESS_CONTROL['DIR'] = datetime.now().strftime('%Y-%m-%d')

DB_CONN=get_db_info()


# Start transaction
with DB_CONN.cursor():
	try:
		
		
		# Because some taxons can be merged, we need to be able to redirect records from other tables accordingly
		# Using foreign key constraints, we are going to find all tables having a column referencing taxon_id
		add_log("Find dependent table")
		DEP_TABLES = get_dep_tables('taxon', GLB_VAR['DB_SCHEMA'])

		# If we haven't already downloaded the files (for instance, if the previous run crashed for some reason)
		if not os.path.isfile('names.dmp') and not os.path.isfile('nodes.dmp'):
			add_log("Download Taxonomy file")
			if not dl_file(GLB_VAR['LINK']['FTP_NCBI'] + '/pub/taxonomy/taxdump.tar.gz', 3):
				fail_process(JOB_ID + "006", 'Unable to download archive')

			add_log("Untar archive")
			if not untar('taxdump.tar.gz'):
				fail_process(JOB_ID + "007", 'Unable to extract archive')

			add_log("Remove all unnecessary files")
			FILES_TO_DEL = ['citations.dmp', 'delnodes.dmp', 'division.dmp', 'gc.prt',
							'gencode.dmp', 'readme.txt', 'taxdump.tar.gz']

			for FILE in FILES_TO_DEL:
				if (os.path.isfile(FILE)):
					os.unlink(FILE)
				if (os.path.isfile(FILE)):
					fail_process(JOB_ID + "008", 'Unable to remove ' + FILE)

			add_log("File check")
			if not validate_line_count('names.dmp', 2800000):
				fail_process(JOB_ID + "009", 'names.dmp is smaller than expected. ' + str(get_line_count('names.dmp')) + '/2800000')
			if not validate_line_count('nodes.dmp', 2000000):
				fail_process(JOB_ID + "010", 'nodes.dmp is smaller than expected. ' + str(get_line_count('nodes.dmp')) + '/2000000')

		add_log("Load taxons from database")
		res = run_query("SELECT tax_id, taxon_id, scientific_name, rank FROM taxon")
		if res is False:
			fail_process(JOB_ID + "011", 'Unable to query the database')

		DATA = {}
		MAX_DBID = -1
		CLASS = {}
		N_CLASS = 0
		INV_CLASS = {}

		for line in res:
			RANK = line['rank']
			if RANK not in CLASS:
				N_CLASS += 1
				CLASS[RANK] = N_CLASS
				INV_CLASS[N_CLASS] = RANK

			DATA[int(line['tax_id'])] = [line['taxon_id'], line['scientific_name'], 'FROM_DB', CLASS[RANK], '']
			MAX_DBID = max(MAX_DBID, line['taxon_id'])
		
		add_log("Load taxons")
		fp = open('names.dmp', 'r')
		N_INSERT = 0
		EXPECTED_TAXONS = 0
		for line in fp:
			line = line.strip()
			if line == '':
				continue
			tab = line.split("|")
			if len(tab) != 5:
				continue
			type = tab[3].strip()
			if type != 'scientific name':
				continue
			
			taxid = int(tab[0].strip())
			name = tab[1].strip()
			
			EXPECTED_TAXONS += 1
			if taxid in DATA:
				ENTRY = DATA[taxid]
				
				ENTRY[2] = 'VALID'
				if ENTRY[1] == name:
					continue
				ENTRY[1] = name
				ENTRY[2] = 'TO_UPD'
			else:
				
				MAX_DBID += 1
				N_INSERT += 1
				DATA[taxid] = [MAX_DBID, name, 'TO_INSERT', '', '']
		fp.close()

		add_log("Load merged entries")
		MERGED = load_merged()

		add_log("Load tree")
		fp = open('nodes.dmp', 'r')
		N = 0
		for line in fp:
			line = line.strip()
			if line == '':
				continue
			tab = line.split("|")
			if len(tab) != 14:
				continue
			TAX_ID = int(tab[0].strip())
			PARENT_ID = int(tab[1].strip())
			RANK = tab[2].strip()
			if RANK not in CLASS:
				N_CLASS += 1
				CLASS[RANK] = N_CLASS
				INV_CLASS[N_CLASS] = RANK
			if TAX_ID not in DATA:
				fail_process(JOB_ID + "015", str(TAX_ID) + ' not found in dataset - Unexpected behavior')
			
			if DATA[TAX_ID][2] == 'TO_INSERT':
				DATA[TAX_ID][3] = CLASS[RANK]
			elif DATA[TAX_ID][2] == 'VALID' or DATA[TAX_ID][2] == 'TO_UPD':
				if DATA[TAX_ID][3] != CLASS[RANK]:
					DATA[TAX_ID][3] = CLASS[RANK]
					DATA[TAX_ID][2] = 'TO_UPD'
			if TAX_ID != PARENT_ID and TAX_ID != '':
				DATA[PARENT_ID][4] += str(TAX_ID) + '|'
				
		fp.close()
		
		add_log("Update/Insert")
		TO_DEL = {}
		for tax_id, info in DATA.items():
			if info[2] == 'TO_UPD':
				res = run_query_no_res("UPDATE taxon SET scientific_name='" + info[1].replace("'", "''") + "', rank='" + INV_CLASS[info[3]].replace("'", "''") + "' WHERE taxon_id=" + str(info[0]))
				if res is False:
					fail_process(JOB_ID + "016", 'Unable to update tax ID ' + tax_id)
			elif info[2] == 'TO_INSERT' and N_INSERT < 100:
				print("NEW TAXON", str(tax_id))
				query = "INSERT INTO taxon (taxon_id, tax_Id, scientific_name, rank) VALUES (" + str(info[0]) + ",'" + str(tax_id) + "','" + info[1].replace("'", "''") + "','" + INV_CLASS[info[3]].replace("'", "''") + "')"
				res = run_query_no_res(query)
				if res is False:
					fail_process(JOB_ID + "017", 'Unable to insert tax ID ' + tax_id)
			elif info[2] == 'FROM_DB':
				TO_DEL[tax_id] = info[0]

		add_log("Bulk Insert")
		if N_INSERT > 100:
			fp = open('insert.csv', 'w')
			if not fp:
				fail_process(JOB_ID + "018", 'Unable to update tax ID ' + tax_id)
			fp.write("taxon_id\ttax_Id\tscientific_name\trank\n")
			for tax_id, info in DATA.items():
				if info[2] != 'TO_INSERT':
					continue
				print("NEW TAXON", tax_id)
				fp.write(str(info[0]) + "|" + str(tax_id) + "|" + info[1] + "|" + INV_CLASS[info[3]] + "\n")
			fp.close()
			command = '\\COPY ' + GLB_VAR['DB_SCHEMA'] + '.taxon(taxon_id,tax_id,scientific_name,rank) FROM \'insert.csv\'' + "  (DELIMITER E'|', QUOTE '~', null 'NULL' ,format CSV, HEADER )"
			return_code=os.system(DB_INFO['COMMAND'] + ' -c "' + command + '"')
			if return_code != 0:
				fail_process(JOB_ID + "019", 'Unable to insert taxons')

		add_log("Updating former taxonomy entries")
		# Commit transaction
		if TO_DEL:
			delete_taxons(TO_DEL, JOB_ID,DEP_TABLES)
		DB_CONN.commit()
	except Exception as e:
		print(traceback.format_exc())
		pprint.pp(e)
		# Rollback transaction
		DB_CONN.rollback()

add_log("Create hierarchy")
create_hierarchy()

update_stat('taxon', 'taxonomy', EXPECTED_TAXONS, JOB_ID)

list_files = ['insert.csv', 'tree.csv']
for F in list_files:
    if not check_file_exist(F):
        continue
    os.unlink(F)
    if os.path.isfile(F):
        fail_process(JOB_ID + "020", 'Unable to delete ' + F)

add_log("Push to prod")
update_release_date(JOB_ID, 'TAXONOMY', get_curr_date())

push_to_prod(JOB_INFO,W_DIR)

success_process()
