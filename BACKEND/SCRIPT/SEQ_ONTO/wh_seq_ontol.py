import os


# Get root directories
TG_DIR = os.getenv('TG_DIR')
if TG_DIR is None:
    print('NO TG_DIR found')
    exit(1)
if not os.path.isdir(TG_DIR):
    print('TG_DIR value is not a directory ')
    exit(2)



import sys
sys.path.append(os.getenv('TG_DIR')+"/BACKEND/SCRIPT/LIB_PYTHON/")
from fct_utils import *
from loader import *
import csv
import pprint

job_name = 'wh_seq_ontol'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name

pprint.pp(JOB_INFO)
add_log("Check directory")

# Get parent job information
parent_job_name = 'ck_seq_ontol'
PARENT_JOB_ID = get_job_id_by_name(parent_job_name,True)
CK_SEQ_ONTOL_INFO = GLB_TREE[PARENT_JOB_ID]



# Set up directory
W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], JOB_INFO['DIR'], CK_SEQ_ONTOL_INFO['TIME']['DEV_DIR'])
if not os.path.isdir(W_DIR):
    fail_process(JOB_ID + "002", 'NO ' + W_DIR + ' found ')
os.chdir(W_DIR)
if (os.getcwd() != W_DIR):
    fail_process(JOB_ID + "003", 'Unable to chdir to ' + W_DIR)

# Update process control directory
PROCESS_CONTROL['DIR'] = CK_SEQ_ONTOL_INFO['TIME']['DEV_DIR']

F_FILE = os.path.join(W_DIR, 'so.obo')
if not os.path.isfile(F_FILE):
    fail_process(JOB_ID + "004", 'NO ' + F_FILE + ' found ')

STATS = {'SO': 0}

add_log("Loading existing data from table")

# Assuming runQuery is defined somewhere
SQL = 'SELECT so_entry_Id, so_id, so_name, so_description FROM so_entry'
res = run_query(SQL)
if res is False:
    fail_process(JOB_ID + "005", 'Return code failure ')

SEQ_ONTOL = {}
MAX_DBID = 0
for tab in res:
    SEQ_ONTOL[tab['so_id']] = {
        'DB': tab['so_entry_id'],
        'NAME': tab['so_name'],
        'DESC': tab['so_description'],
        'STATUS': 'DB'
    }
    if tab['so_entry_id'] > MAX_DBID:
        MAX_DBID = tab['so_entry_id']
del res

print("Number of records from db: "+str(len(SEQ_ONTOL)))

add_log("Process file")

with open(F_FILE, 'r') as fp:
    for line in fp:
        if line.strip() != '[Term]':
            continue
        
        STATS['SO'] += 1
        CURR_ID = -1
        so_id = ''
        for line in fp:
            if line.strip() == '':
                break
            pos = line.find(':')
            head = line[:pos]
            val = line[pos+2:].strip()
            if head == 'id':
                so_id = val
                CURR_ID = so_id
                if so_id not in SEQ_ONTOL:
                    MAX_DBID += 1
                    
                    SEQ_ONTOL[CURR_ID] = {'DB': MAX_DBID, 'NAME': '', 'DESC': '', 'STATUS': 'NEW'}
                else:
                    SEQ_ONTOL[CURR_ID]['STATUS'] = 'VALID'
            elif head == 'name':
                if SEQ_ONTOL[CURR_ID]['NAME'] != val:
                    SEQ_ONTOL[CURR_ID]['NAME'] = val
                    if SEQ_ONTOL[CURR_ID]['STATUS'] != 'NEW':
                        SEQ_ONTOL[CURR_ID]['STATUS'] = 'UPD'
            elif head == 'def':
                pos = val.rfind('"')
                val = val[1:pos]
                if SEQ_ONTOL[CURR_ID]['STATUS'] == 'NEW':
                    SEQ_ONTOL[CURR_ID]['DESC'] = val
                elif SEQ_ONTOL[CURR_ID]['DESC'] != val:
                    SEQ_ONTOL[CURR_ID]['DESC'] = val
                    SEQ_ONTOL[CURR_ID]['STATUS'] = 'UPD'

add_log("Update records")


with open(os.getcwd()+'/so_insert.csv', 'w') as fp:
    writer = csv.writer(fp, delimiter='\t')
    for so_id, info in SEQ_ONTOL.items():
        if info['STATUS'] == 'UPD':
            query = f"UPDATE so_entry SET so_name='{info['NAME'].replace("'", "''")}', " \
                    f"so_description='{info['DESC'].replace("'", "''")}', so_id='{so_id}' " \
                    f"WHERE so_entry_id={info['DB']}"
            print(query)
            if not run_query_no_res(query):
                fail_process(JOB_ID + "008", 'Unable to run query ' + query)
        elif info['STATUS'] == 'NEW':
            pprint.pp(info)
            writer.writerow([info['DB'], info['NAME'], so_id, info['DESC'] if info['DESC'] else 'NULL'])

add_log("Delete records")

for ID, info in SEQ_ONTOL.items():
    if info['STATUS'] != 'DB':
        continue
    query = f"DELETE FROM SO_ENTRY WHERE SO_ENTRY_ID={info['DB']}"
    print(query)
    if not run_query_no_res(query):
        fail_process(JOB_ID + "009", 'Unable to run query ' + query)

FCAV_NAME = 'so_insert.csv'
command = f"\\COPY {GLB_VAR['DB_SCHEMA']}.so_entry(so_entry_id,so_name,so_id,so_description) " \
          f"FROM '{FCAV_NAME}' (DELIMITER E'\\t', null 'NULL', format CSV )"
return_code=os.system(f"{DB_INFO['COMMAND']} -c \"{command}\"")
if return_code != 0:
    fail_process(JOB_ID + "010", 'Unable to insert sequence_ontology entry')





CLEANUP = ['so_insert.csv']
for file in CLEANUP:
    if os.path.isfile(file):
        os.unlink(file)

update_stat('so_entry', 'so_entry', STATS['SO'], JOB_ID)
push_to_prod(JOB_INFO,W_DIR)

success_process()
