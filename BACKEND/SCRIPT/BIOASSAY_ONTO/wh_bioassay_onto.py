import os
import shutil
import subprocess
import re
from datetime import datetime
import sys
sys.path.append(os.getenv('TG_DIR')+"/BACKEND/SCRIPT/LIB_PYTHON/")
import time
from datetime import datetime
import smtplib
import psycopg2
import pickle
import pprint
from fct_utils import *
from loader import *


job_name = 'wh_bioassay_onto'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name

TG_DIR = os.getenv('TG_DIR')

if TG_DIR is False:
    print('NO TG_DIR found')
    exit()

if not os.path.isdir(TG_DIR):
    print('TG_DIR value is not a directory', TG_DIR)
    exit()


add_log("Check directory")

# Get parent job info
CK_SEQ_ONTOL_INFO = GLB_TREE[get_job_id_by_name('ck_bioassay_onto')]


# Get to the directory set by ck_bioassay_onto
W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], JOB_INFO['DIR'], CK_SEQ_ONTOL_INFO['TIME']['DEV_DIR'])
if not os.path.isdir(W_DIR):
    fail_process(JOB_ID + "002", 'NO ' + W_DIR + ' found')
os.chdir(W_DIR)
if os.getcwd() != W_DIR:
    fail_process(JOB_ID + "003", 'Unable to chdir to ' + W_DIR)

# Assign the directory to the process control, so the next job knows where to look
PROCESS_CONTROL['DIR'] = CK_SEQ_ONTOL_INFO['TIME']['DEV_DIR']

# Check if the file bao_complete_merged.owl is present
F_FILE = os.path.join(W_DIR, 'bao_complete_merged.owl')
if not check_file_exist(F_FILE):
    fail_process(JOB_ID + "004", 'NO ' + F_FILE + ' found')

STATS = {'ENTRY': 0}


add_log("Load data from database")


# We are going to load the data from the database
DATA = {}
# MAX_DBID is the maximum bioassay_onto_entry_id in the database
MAX_DBID = 0
res = run_query("SELECT bioassay_onto_entry_id,bioassay_tag_id,bioassay_label,bioassay_definition FROM bioassay_onto_entry ORDER BY bioassay_tag_id ASC")
if res is False:
    fail_process(JOB_ID + "007", 'Unable to fetch from database')

for tab in res:
    DATA[tab['bioassay_tag_id']] = {
        'DB': tab['bioassay_onto_entry_id'],
        'NAME': tab['bioassay_label'],
        'DESC': tab['bioassay_definition'],
        'STATUS': 'FROM_DB',# STATUS is used to determine if the entry is new, updated or from the database
        'EXTDB': [],
        'CHILD': {}
    }
    # Update the MAX_DBID if the current bioassay_onto_entry_id is greater than the current MAX_DBID
    if tab['bioassay_onto_entry_id'] > MAX_DBID:
        MAX_DBID = tab['bioassay_onto_entry_id']

add_log("Load data from file")

# Load data from the owl file
ROOTS = {}
# This is an owl file, which is an xml file
# We are going to read the file line by line
with open('bao_complete_merged.owl', 'r') as fp:
    while True:
        line = fp.readline()
        if not line:
            break
        # Each record start with Class
        if '<owl:Class' not in line:
            continue
        
        # Extract the ID from the line
        P1 = line.rfind('/')
        P2 = line.rfind('"')
        ID = line[P1+5:P2]
        
        if P1 is False or P2 is False:
            continue
        # We are only interested in the BAO_ entries
        if not ID.startswith('BAO_'):
            continue
        
        # Initialize the variables
        STATS['ENTRY'] += 1
        N_OPEN = 0
        DESC = []
        NAME = ''
        PARENTS = []
        VALID = True
        EXTDB = []

		# Since a record is a set of lines, we are going to read the lines until we reach the end of the record
        while True:
            line = fp.readline()
            if not line:
                break
            if 'IAO_0000115' in line:
                #Description text
                DESC.append(get_in_between(line, 'obo:IAO_0000115') + "\n")
            if '<rdfs:label' in line:
                NAME = get_in_between(line, 'rdfs:label')
                
			# We don't want deprecated entries
            if 'owl:deprecated' in line:
                VALID = False
                break
            
            # Get the parent
            if '<rdfs:subClassOf' in line:
                tstr = get_tag(line, 'rdf:resource')
                PARENT = tstr[tstr.rfind('/')+1:]
                if PARENT.startswith('bao#BAO'):
                    PARENT = PARENT[4:]
                    if PARENT != '' and PARENT != 'owl#Thing':
                        PARENTS.append(PARENT)
            if '<owl:Class' in line:
                N_OPEN += 1
            if '</owl:Class' in line:
                if N_OPEN == 0:
                    break
                else:
                    N_OPEN -= 1
                    
		# If the record is Deprecated -> continue
        if not VALID:
            continue
		
        # Not existing in the database -> create the record
        if ID not in DATA:
            
            MAX_DBID += 1
            DATA[ID] = {
                'DB': MAX_DBID,
                'NAME': NAME,
                'DESC': DESC,
                'STATUS': 'TO_INS',
                'EXTDB': EXTDB,
                'CHILD': {}
            }
        # Some records in DATA can be created because of the child/parent, but don't have a DB ID, in that case, we create the record
        elif ID in DATA and 'DB' not in DATA[ID]:
            MAX_DBID += 1
            DATA[ID]['DB'] = MAX_DBID
            DATA[ID]['NAME'] = NAME
            DATA[ID]['DESC'] = DESC
            DATA[ID]['STATUS'] = 'TO_INS'
            DATA[ID]['EXTDB'] = EXTDB
        else:
            # The record exist -> set to valid, unless some data has changed
            DATA[ID]['STATUS'] = 'VALID'
            if NAME != DATA[ID]['NAME']:
                DATA[ID]['NAME'] = NAME
                DATA[ID]['STATUS'] = 'TO_UPD'
            if ''.join(DESC) != DATA[ID]['DESC']:
                DATA[ID]['DESC'] = DESC
                DATA[ID]['STATUS'] = 'TO_UPD'

		# That record has no parent -> Root 
		# We are going to use this information to create the nested set representation
        if not PARENTS:
            ROOTS[ID] = True
        else:
            for C in PARENTS:
                # This is causing the creation of a record in $DATA without name/description. See case above
                DATA[C]['CHILD'][ID] = True

add_log("Create nested set representation")

if not ROOTS:
    fail_process(JOB_ID + "009", 'No root found')

with open('TREE.csv', 'w') as fp:
    VALUE = 0


	# Create nested set representation that is going to assign boundary numbers.
	# Let's say that the root has for boundary 1 10.
	# The two childs:  A 2-5 and B 6-9
	# And the A has a child C 3-4
	# If we want ALL parents of C, we are going to look outside the boundaries, i.e. <3 for the left side and >4 for the right side.
	# By doing so we get A 2-5 and root 1-10 but not B because the left boundary 6 is above C left boundary.
	# Similarly, if we want children of Root, we will look inside the boundaries i.e >1 for theleft side and <10 for the right side, leading to A B and C.
    def gen_tree(DATA, ROOTS, LEVEL,LEVEL_V):
        LEVEL += 1
        for RID in ROOTS.keys():
            if RID not in DATA:
                print(RID)
                continue
            LEVEL_V += 1
            LEVEL_LEFT = LEVEL_V
            if 'CHILD' in DATA[RID]:
                print(RID+" "+str(DATA[RID]['CHILD'])+" "+str(LEVEL)+" "+str(LEVEL_V))
                LEVEL_V=gen_tree(DATA, DATA[RID]['CHILD'], LEVEL, LEVEL_V)
            LEVEL_V += 1
            LEVEL_RIGHT = LEVEL_V
            fp.write(f"{DATA[RID]['DB']}\t{LEVEL}\t{LEVEL_LEFT}\t{LEVEL_RIGHT}\n")
        return LEVEL_V
    gen_tree(DATA, ROOTS, 0, VALUE)

	# $DATA now contains both the data from the database and from the file
	# So we look at each of those to see what needs to be deleted, updated, inserted

print("Pushing changes to database")


#print(DATA['BAO_0002235'])
#exit()

# Open the file that is going to contain the new records
with open('ENTRY.csv', 'w') as fpE:
    HAS_NEW_DATA = False
    #We are going to look at each record and see what we need to do
    for ID, INFO in DATA.items():
        # FROM_DB => record is not in the file anymore -> DELETE IT	
        if INFO['STATUS'] == 'FROM_DB':
            print(ID)
            print(INFO)
            QUERY = f"DELETE FROM bioassay_onto_entry WHERE bioassay_onto_entry_id = {INFO['DB']}"
            if not run_query_no_res(QUERY):
                fail_process(JOB_ID + "012", 'Unable to run query ' + QUERY)
        # Update the record
        elif INFO['STATUS'] == 'TO_UPD':
            print(INFO)
            QUERY = f"UPDATE bioassay_onto_entry SET bioassay_tag_id = '{prep_string(ID)}', bioassay_label = '{prep_string(INFO['NAME'])}', bioassay_definition = '{prep_string(''.join(INFO['DESC']))}' WHERE bioassay_onto_entry_id = {INFO['DB']}"
            print(QUERY)
            if not run_query_no_res(QUERY):
                fail_process(JOB_ID + "013", 'Unable to run query ' + QUERY)
        elif INFO['STATUS'] == 'TO_INS':
            HAS_NEW_DATA = True
            fpE.write(f"{INFO['DB']}\t{ID}\t\"{INFO['NAME']}\"\t\"{''.join(INFO['DESC'])}\"\n")

print("Pushing new records to database")

if HAS_NEW_DATA:
    command = f"\COPY {GLB_VAR['DB_SCHEMA']}.bioassay_onto_entry(bioassay_onto_entry_id,bioassay_tag_id,bioassay_label,bioassay_definition) FROM 'ENTRY.csv' (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )"
    print(f"{DB_INFO['COMMAND']} -c \"{command}\"")
    return_code=os.system(f"{DB_INFO['COMMAND']} -c \"{command}\"")
    if return_code != 0:
        fail_process(JOB_ID + "014", 'Unable to insert bioassay entry')

print("Delete content of bioassay_onto_hierarchy")

if not run_query_no_res("TRUNCATE TABLE bioassay_onto_hierarchy"):
    fail_process(JOB_ID + "015", 'Unable to truncate bioassay_onto_hierarchy')

print("Pushing tree to database")

command = f"\COPY {GLB_VAR['DB_SCHEMA']}.bioassay_onto_hierarchy(bioassay_onto_entry_id,bioassay_onto_level,bioassay_onto_level_left,bioassay_onto_level_right) FROM 'TREE.csv' (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV, HEADER )"
print(f"{DB_INFO['COMMAND']} -c \"{command}\"")
return_code=os.system(f"{DB_INFO['COMMAND']} -c \"{command}\"")
if return_code != 0:
    fail_process(JOB_ID + "016", 'Unable to insert tree')

update_stat('bioassay_onto_entry', 'bioassay_onto', STATS['ENTRY'], JOB_ID)

print("Delete obsolete files")
list_files = ['TREE.csv', 'ENTRY.csv']
for F in list_files:
    if os.path.isfile(F):
        os.unlink(F)

print("Push to prod")
push_to_prod(JOB_INFO,W_DIR)


success_process()
