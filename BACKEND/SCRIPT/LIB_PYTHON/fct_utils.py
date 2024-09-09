
import os
from os import path
import subprocess
import re
import subprocess
import json
import time
import shutil
import psycopg2
import glob
import hashlib
import sys
import smtplib
import math
from datetime import datetime
from collections import defaultdict
from datetime import datetime
from psycopg2.extras import RealDictCursor
import pprint



########################################################################
#################### CORE PROCESS GLOBAL VARIABLES #####################
########################################################################
### Those variables below are populated when the process is launched ###
### by loader.py. They are used by the process to run properly.      ###

# This dictionary contains the jobs and the requirements for each job
# This is all the configuration from CONFIG_JOB and CONFIG_USER
GLB_TREE = {}

# This dictionary contains the timestamp info
GLB_TIME = {}

# This dictionary contains the hierarchy level to run the scripts
GLB_TREE_LEVEL = {}

# Contains various global directories and files
# This is all the configuration from CONFIG_GLOBAL (LINK/TOOL)
# And CONFIG_USER (GENOME/PROTEOME)
GLB_VAR = {'GENOME': {}, 'PROTEOME': {}, 'LINK':{},'TOOL':{}}


def microtime_float():
    return time.time()

START_SCRIPT_TIME = microtime_float()
START_SCRIPT_TIMESTAMP = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

# Contains the list of data sources and their corresponding database id
SOURCE_LIST = {}


# Set DB_SCHEMA from environment variable
tmp_db_schema = os.getenv('DB_SCHEMA')
if tmp_db_schema is None:
    raise ValueError('No DB_SCHEMA set. Did you forget to source setenv file?')
GLB_VAR['DB_SCHEMA'] = tmp_db_schema

# Set WORKDIR from environment variable
tmp_workdir = os.getenv('TG_DIR')
if tmp_workdir is None:
    raise ValueError('No TG_DIR set. Did you forget to source setenv file?')
GLB_VAR['WORKDIR'] = tmp_workdir

MAIL_COMMENTS = []

# Contains a list of Job IDs and their qsub ID
GLB_RUN_JOBS = {}

# If web jobs are managed from this system, they will be listed here
GLB_RUN_WEBJOBS = {}

TG_DIR = os.getenv('TG_DIR')
DB_CONN = {}
DB_INFO = {}
DB_SCHEMA = None



########################################################################
#################### JOB SPECIFIC CONFIGURATION ########################
########################################################################
#### Here are defined variables used by the process to run properly ####
### They are specific to the job ####

# Source DB Identifier of the current job
SOURCE_ID=-1

# Job ID as defined in CONFIG_JOB for the current job
JOB_ID=-1

STATS={}

PROCESS_CONTROL = {
    'STEP': 0,
    'JOB_NAME': '',  # Assuming JOB_NAME is defined somewhere
    'DIR': '',
    'LOG': [],
    'STATUS': 'INIT',
    'START_TIME': microtime_float(),
    'END_TIME': '',
    'STEP_TIME': microtime_float(),
    'FILE_LOG': ''
}





def loadProcess():
    global TG_DIR
    global GLB_TREE
    global GLB_TIME
    global GLB_VAR
    
    PR_FILE = TG_DIR + '/BACKEND/SCRIPT/CONFIG/CONFIG_GLOBAL'
    # Check file existence:
    if not os.path.isfile(PR_FILE):
        send_kill_mail('A00001', 'No CONFIG_GLOBAL file at ' + PR_FILE)
    load_global_file(PR_FILE, False)
    PR_FILE = TG_DIR + '/BACKEND/PRIVATE_SCRIPT/CONFIG_GLOBAL'
    # Check file existence:
    if os.path.isfile(PR_FILE):
        load_global_file(PR_FILE, True)
    PR_FILE = TG_DIR + '/BACKEND/SCRIPT/CONFIG/CONFIG_JOB'
    # Check file existence:
    if not os.path.isfile(PR_FILE):
        send_kill_mail('A00002', 'No CONFIG_JOB file at ' + PR_FILE)
    load_config_file(PR_FILE, False)
    DB_SCHEMA = os.getenv('DB_SCHEMA')
    if DB_SCHEMA is not False:
        GLB_VAR['PUBLIC_SCHEMA'] = DB_SCHEMA
	
    PR_FILE = TG_DIR + '/BACKEND/SCRIPT/CONFIG/CONFIG_USER'
    # Check file existence:
    if not os.path.isfile(PR_FILE):
        send_kill_mail('A00003', 'No CONFIG_USER file at ' + PR_FILE)
    load_config_user(PR_FILE)
    for ID, INFO in GLB_TREE.items():
        if INFO['ENABLED'] == '':
            send_kill_mail('A00004', 'Missing Y/N if ' + INFO['NAME'] + ' is enabled in CONFIG_USER')
    if GLB_VAR['PRIVATE_ENABLED'] == 'T':
        PRIVATE_SCHEMA = os.getenv('SCHEMA_PRIVATE')
        if PRIVATE_SCHEMA is not False:
            GLB_VAR['SCHEMA_PRIVATE'] = PRIVATE_SCHEMA
        if GLB_VAR['SCHEMA_PRIVATE'] is False:
            send_kill_mail('A00005', 'SCHEM_PRIVATE is not set in setenv.sh but PRIVATE_ENABLED set to T(rue) in CONFIG_GLOBAL ')
        if 'SCHEMA_PRIVATE' not in GLB_VAR:
            send_kill_mail('A00006', 'No SCHEMA_PRIVATE defined ')
        if os.path.isdir(TG_DIR + '/BACKEND/PRIVATE_SCRIPT') and os.path.isfile(TG_DIR + '/BACKEND/PRIVATE_SCRIPT/CONFIG_JOB'):
            load_config_file(TG_DIR + '/BACKEND/PRIVATE_SCRIPT/CONFIG_JOB', True)





def load_config_user(pr_file):
    global GLB_VAR
    global GLB_TREE
    with open(pr_file, 'r') as fp:
        if not fp:
            send_kill_mail('A00007', 'Unable to open file ' + pr_file)

        MAP_T = {'Ensembl': 3, 'RefSeq': 4, 'Uniprot': 5, 'Transcriptome': 6}
        MAP = {}
        ORG_REL_OPTS = {
            1: 'Tax_Id', 2: 'Source', 3: 'Assembly_Acc', 4: 'Assembly_name',
            5: 'Gene_build', 6: 'organism_name', 7: 'version_status',
            8: 'release_type', 9: 'refseq_category', 10: 'annotation_date',
            11: 'group'
        }

        PROTEOME_RULES = {
            1: 'Proteome_ID', 2: 'Tax_Id', 3: 'OSCODE', 4: 'SUPERREGNUM',
            5: 'N_Fasta_Canonical', 6: 'N_Fasta_Isoform', 7: 'Gene2Acc',
            8: 'Species Name', 9: 'Tax_Id2'
        }

        for ID, INFO in GLB_TREE.items():
            MAP[INFO['NAME']] = ID

        while True:
            line = fp.readline()
            
            if line.strip() == "" and len(line) == 0:
                break
            line=line.strip()
            if (line == ""):
                continue
            if line[0] == "#" or line == "":
                continue

            tab = list(filter(None, line.split("\t")))
            
            if tab[0] == "GLOB":
                GLB_VAR[tab[1]] = tab[2]
            elif tab[0] == 'JOB':
                if len(tab) != 3:
                    send_kill_mail('A00008', 'Number of columns must be 3 for JOB definition in CONFIG_USER: ' + line)
                NAME = tab[1]
                if NAME not in MAP:
                    send_kill_mail('A00009', 'Unable to find ' + NAME + ' in CONFIG_JOB as defined by CONFIG_USER')
                GLB_TREE[MAP[NAME]]['ENABLED'] = tab[2]
            elif tab[0] == 'GENOME':
                if len(tab) != len(ORG_REL_OPTS) + 1:
                    send_kill_mail('A00010', 'Number of columns must be ' + str(len(ORG_REL_OPTS) + 1) +
                                    ' for GENOME definition in CONFIG_USER: ' + line)
                if not tab[1].isnumeric():
                    send_kill_mail('A00011', 'Column 2 must be numeric (NCBI taxonomic Identifier) ' + tab[1] +
                                   ' for line: ' + line)
                T = {ORG_REL_OPTS[K]: tab[K] for K in ORG_REL_OPTS}
                GLB_VAR['GENOME'][T['Tax_Id']] = GLB_VAR['GENOME'].get(T['Tax_Id'], []) + [T]
            elif tab[0] == 'PROTEOME':
                if len(tab) != len(PROTEOME_RULES) + 1:
                    send_kill_mail('A00010', 'Number of columns must be ' + str(len(PROTEOME_RULES) + 1) +
                                    ' for PROTEOME_RULES definition in CONFIG_USER: ' + line)
                if not tab[2].isnumeric():
                    send_kill_mail('A00011', 'Column 2 must be numeric (NCBI taxonomic Identifier) ' + tab[1] +
                                   ' for line: ' + line)
                T = {PROTEOME_RULES[K]: tab[K] for K in PROTEOME_RULES}
                GLB_VAR['PROTEOME'][T['Tax_Id']] = GLB_VAR['PROTEOME'].get(T['Tax_Id'], []) + [T]

    fp.close()


def load_global_file(pr_file, is_private):
    global GLB_VAR
    with open(pr_file, 'r') as fp:
        if not fp:
            send_kill_mail('A00013', 'Unable to open file ' + pr_file)

        while True:
            line = fp.readline()
            if line.strip() == "" and len(line) == 0:
                break
            line=line.strip()
            
            if (line == ""):
                continue
            tab = list(filter(None, line.split("\t")))

            if tab[0] == "GLOB":
                GLB_VAR[tab[1]] = tab[2]
            elif tab[0] == "LINK":
                if len(tab) != 3:
                    send_kill_mail('A00014', 'Wrong columns count for ' + line)
                
                GLB_VAR['LINK'][tab[1]] = tab[2]
            elif tab[0] == "TOOL":
                if len(tab) != 3:
                    send_kill_mail('A00015', 'Wrong columns count for ' + line)
                GLB_VAR['TOOL'][tab[1]] = tab[2]

    fp.close()


def load_config_file(pr_file, is_private):
    global GLB_TREE
    N=0
    with open(pr_file, 'r') as fp:
        if not fp:
            send_kill_mail('A00016', 'Unable to open file ' + pr_file)

        while True:
            line = fp.readline()
            if line.strip() == "" and len(line) == 0:
                break
            line=line.strip()
            
            if (line == ""):
                continue
            if line[0] == "#" or line == "":
                continue
            
            tab = list(filter(None, line.split("\t")))
            


            if tab[0] == 'SC':
                if tab[1] in GLB_TREE:
                    send_kill_mail('A00017', 'JOB ID ' + tab[1] + ' Already exists for line ' + line + ' ' +
                                   str(GLB_TREE[tab[1]]))
                if len(tab) != 14:
                    send_kill_mail('A00018', line + '  has ' + str(len(tab)) + ' columns instead of ')
                if tab[7] not in ['C', 'A', 'D']:
                    send_kill_mail('A00018', line + ' has wrong REQ_RULE value (either A or C or D)')
                if tab[8] not in ['D', 'P']:
                    send_kill_mail('A00019', line + ' has wrong DEV_JOB value (either P or D)')
                if tab[10] not in ['R', 'S']:
                    send_kill_mail('A00020', line + ' has wrong Runtime value (either R or S)')
                if not (tab[9].isnumeric() or tab[9] == 'P' or (tab[9][0] in ['W', 'D', 'M', 'H'])):
                    time = tab[9].split(":")
                    if len(time) != 2 or int(time[0]) < 0 or int(time[0]) > 23 or int(time[1]) < 0 or int(time[1]) > 59:
                        send_kill_mail('A00021', 'Unrecognized value for frequency ' + tab[9] + ' in line ' + line)
                if not bool(re.match(r'((([0-9]{1,5}\|{0,1}){1,50})|-1|/)', tab[11])):
                    send_kill_mail('A00021', line + ' has wrong concurrent rules')
                REQUIRED = [] if tab[3] == -1 or tab[5] == '/' else tab[3].split("|")
                REQ_TRIGGER = [] if tab[4] == -1 or tab[5] == '/' else tab[4].split("|")
                REQ_UPDATED = [] if tab[5] == -1 or tab[5] == '/' else tab[5].split("|")
                CONCURRENT=[] if (tab[11] == -1 or tab[11] == '/') else tab[11].split("|")
                GLB_TREE[tab[1]] = {
                    'NAME': tab[2], 
                    'REQUIRE': REQUIRED,
                    'REQ_ACTIVE':REQ_TRIGGER,
                    'REQ_UPDATED': REQ_UPDATED, 
                    'DIR': tab[6],
                    'REQ_RULE': tab[7], 'DEV_JOB': (tab[8] == 'D'),
                    'FREQ': tab[9], 'ENABLED': '',
                    'RUNTIME': tab[10], 'CONCURRENT':CONCURRENT,
                    'MEM': tab[12], 'DESC': tab[13],
                    'FAILED':0,
                    'IS_PRIVATE': is_private
                }


def check_tree():
    global TG_DIR
    global GLB_TREE
    global GLB_VAR

    for ID, INFO in GLB_TREE.items():
        for ID_R in INFO['REQUIRE']:
            if ID_R == -1:
                continue
            if ID_R in GLB_TREE:
                continue
            print(ID, end='\t')
            print(INFO['REQUIRE'])
            send_kill_mail('A00022', 'JOB ID ' + str(ID_R) + ' does not exists for JOB ID ' + str(ID) +
                           "\n" + str(INFO))


def gen_hierarchy():
    global GLB_TREE
    global GLB_TREE_LEVEL
    GLB_TREE_LEVEL = {0: []}
    TREE_CHECK = {}
    for ID, INFO in GLB_TREE.items():
        TREE_CHECK[ID] = -1

    for ID, INFO in GLB_TREE.items():
        if not (len(INFO['REQUIRE']) == 1 and INFO['REQUIRE'][0] == -1):
            continue
        GLB_TREE_LEVEL[0].append(ID)
        TREE_CHECK[ID] = 0

    LEV = 1
    while True:
        GLB_TREE_LEVEL[LEV] = []
        for ID, INFO in GLB_TREE.items():
            if TREE_CHECK[ID] != -1:
                continue
            ALL_GOOD = all(TREE_CHECK[V] == -1 or TREE_CHECK[V] == LEV for V in INFO['REQUIRE'])
            if not ALL_GOOD:
                continue
            GLB_TREE_LEVEL[LEV].append(ID)
            TREE_CHECK[ID] = LEV

        LEV += 1
        if LEV > 50:
            print(GLB_TREE_LEVEL)
            send_kill_mail('A00024', 'Tree level maxed out')
        if all(x != -1 for x in TREE_CHECK.values()):
            break

    GLB_TREE_LEVEL = {k: v for k, v in GLB_TREE_LEVEL.items() if v}































def monitor_qengine():
    global GLB_RUN_JOBS
    global GLB_TREE
    global GLB_VAR
    val = []
    subprocess.run(['qstat', '|', 'egrep', f'({GLB_VAR["JOB_PREFIX"]}_|arrayjob|NNPS)'], stdout=val)
    CHECK = GLB_RUN_JOBS.copy()

    for line in val:
        tab = list(filter(None, line.split(" ")))
        ID = tab[0]
        if ID in CHECK:
            print(f"CURRENTLY RUNNING: {ID}\t{CHECK[ID]}\t{GLB_TREE[CHECK[ID]]['NAME']}")
            del CHECK[ID]

    ENDED_JOB = list(CHECK.values())
    for QSTAT_ID, JOB_ID in CHECK.items():
        del GLB_RUN_JOBS[QSTAT_ID]

    print(f"NUMBER OF RUNNING JOBS: {len(GLB_RUN_JOBS)}\nENDED JOB: {len(ENDED_JOB)}")
    refresh_job_file()
    return ENDED_JOB


def is_job_monitored(JOB_ID):
    global TG_DIR
    global GLB_VAR
    global GLB_RUN_JOBS
    PATH = os.path.join(TG_DIR, GLB_VAR['MONITOR_DIR'], 'JOB_RUNNING.csv')
    if not os.path.isfile(PATH):
        fail_process(f"{JOB_ID}001", f"Unable to find JOB_RUNNING at {PATH}")
    with open(PATH, 'r') as fp:
        for line in fp:
            tab = line.split("\t")
            if tab[1] == JOB_ID:
                return True
    return False


def submit_qengine(JOB_ID):
    global GLB_RUN_JOBS
    global GLB_TREE
    global GLB_VAR
    global TG_DIR
    for QJOB, ID in GLB_RUN_JOBS.items():
        if ID == JOB_ID:
            return

    JOB_INFO = GLB_TREE[JOB_ID]
    print("SUBMISSION")
    FPATH = os.path.join(TG_DIR, GLB_VAR['BACKEND_DIR'], 'CONTAINER_SHELL', f"{JOB_INFO['NAME']}.sh")
    if not check_file_exist(FPATH):
        fail_process(f"{JOB_ID}003", f"Missing script file {FPATH}")
    ADD_DESC = ''
    if JOB_INFO['MEM'] != -1:
        ADD_DESC += f" -l m_mem_free={JOB_INFO['MEM']}M   -l h_rss={JOB_INFO['MEM']}M "
    query = f"qsub -v TG_DIR -o {TG_DIR}/BACKEND/LOG/SGE_LOG/TG_{JOB_ID}_{time.strftime('%Y_%m_%d_%H_%M_%S')}.o " \
            f"-e {TG_DIR}/BACKEND/LOG/SGE_LOG/TG_{JOB_ID}_{time.strftime('%Y_%m_%d_%H_%M_%S')}.e " \
            f"-N {GLB_VAR['JOB_PREFIX']}_{JOB_ID} {ADD_DESC} {FPATH}"
    res = subprocess.run(query, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    if res.returncode != 0:
        fail_process(f"{JOB_ID}004", f"Unable to submit job {query}")
    tab = list(filter(None, res.stdout.decode('utf-8').split(' ')))
    GLB_RUN_JOBS[tab[2]] = JOB_ID
    refresh_job_file()


def qengine_log(JOB_ID):
    global GLB_TREE
    global GLB_VAR
    global TG_DIR
    JOB_INFO = GLB_TREE[JOB_ID]
    print(f"\tEND {JOB_ID}\t{JOB_INFO['NAME']}")
    LOG_FILE = os.path.join(TG_DIR, GLB_VAR['LOG_DIR'], f"{JOB_INFO['NAME']}.log")
    print(f"\tLOG: {LOG_FILE}")
    PROCESS_DATA = {}
    if not check_file_exist(LOG_FILE):
        print("\tLOG NOT FOUND - JOB KILLED OR DIED")
        GLB_TREE[JOB_ID]['TIME']['CHECK'] = time.time()
    else:
        content =''
        with open(LOG_FILE, 'rb') as f:
            content = f.read()
        PROCESS_DATA = json.loads(content)
        print(f"\tSTATUS: {PROCESS_DATA['STATUS']}")
        print(f"\tPROCESS DIR: {PROCESS_DATA['DIR']}")


def convertJsonString(s):
    p = re.compile('(?<!\\\\)\'')
    s = p.sub('\"', s)
    return s


def qengine_validate(JOB_ID):
    add_log("VALIDATE JOB")

    global GLB_TREE
    global GLB_VAR
    global TG_DIR
    JOB_INFO = GLB_TREE[JOB_ID]
    print(f"\tEND {JOB_ID}\t{JOB_INFO['NAME']}\n")

    LOG_FILE = os.path.join(TG_DIR, GLB_VAR['LOG_DIR'], f"{JOB_INFO['NAME']}.log")
    print(f"\tLOG:{LOG_FILE}\n")
    PROCESS_DATA = {}
    if not check_file_exist(LOG_FILE):
        PROCESS_DATA['STATUS'] = 'QUIT'
        GLB_TREE[JOB_ID]['TIME']['CHECK'] = time.time()
    else:
        content =''
        with open(LOG_FILE, 'rb') as f:
            content = f.readline()
        content=content.decode('utf-8')
        PROCESS_DATA = json.loads(convertJsonString(content))
        print(f"\tSTATUS:{PROCESS_DATA['STATUS']}\n")
        print(f"\tPROCESS DIR:{PROCESS_DATA['DIR']}\n")

        GLB_TREE[JOB_ID]['TIME']['CHECK'] = time.time()
        if PROCESS_DATA['STATUS'] == 'SUCCESS':
            GLB_TREE[JOB_ID]['TIME']['DEV'] = time.time()
            GLB_TREE[JOB_ID]['TIME']['DEV_DIR'] = PROCESS_DATA['DIR']

    STATUS_MAP = {'SUCCESS': 'T', 'VALID': 'T', 'QUIT': 'Q'}
    STATUS = 'F'

    if PROCESS_DATA['STATUS'] in STATUS_MAP:
        STATUS = STATUS_MAP[PROCESS_DATA['STATUS']]
    print(f"PROCESS DATA STATUS: {PROCESS_DATA['STATUS']}\t{STATUS}\n")
    refresh_timestamp(JOB_ID, STATUS)


def preload_jobs():
    global TG_DIR
    global GLB_RUN_JOBS
    global GLB_VAR
    PATH = os.path.join(TG_DIR, GLB_VAR['MONITOR_DIR'], 'JOB_RUNNING.csv')
    if not os.path.isfile(PATH):
        fail_process("006", f"Unable to find JOB_RUNNING at {PATH}")
    with open(PATH, 'r') as fp:
        for line in fp:
            if line == "":
                continue
            tab = line.split("\t")
            GLB_RUN_JOBS[tab[0]] = tab[1]


def refresh_job_file():
    global TG_DIR
    global GLB_RUN_JOBS
    global GLB_VAR
    PATH = os.path.join(TG_DIR, GLB_VAR['MONITOR_DIR'], 'JOB_RUNNING.csv')
    if not os.path.isfile(PATH):
        fail_process("008", f"Unable to find JOB_RUNNING at {PATH}")
    with open(PATH, 'w') as fp:
        for QID, JOB_ID in GLB_RUN_JOBS.items():
            fp.write(f"{QID}\t{JOB_ID}\n")


def monitor_single_cpu():
    global GLB_RUN_JOBS
    global GLB_TREE
    global GLB_VAR

    ENDED_JOB = []
    N_RUNNING = 0
    for QJOB, QJOB_INFO in GLB_RUN_JOBS.items():
        status = subprocess.run(['procstat', str(QJOB_INFO['PROCESS'])], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        print(status)
        if not status.returncode:
            ENDED_JOB.append(QJOB_INFO['JOB_ID'])
            QJOB_INFO['PROCESS'].terminate()
        else:
            print(f"RUNNING: {QJOB}\t{QJOB_INFO['JOB_ID']}\t{GLB_TREE[QJOB_INFO['JOB_ID']]['NAME']}\n")
            N_RUNNING += 1

    print(f"NUMBER OF RUNNING JOBS:{N_RUNNING}\nENDED JOB:{len(ENDED_JOB)}\n")
    refresh_job_file()
    return ENDED_JOB


def submit_single_cpu(JOB_ID):
    global GLB_RUN_JOBS
    global GLB_TREE
    global GLB_VAR
    global TG_DIR
    for QJOB, QJOB_INFO in GLB_RUN_JOBS.items():
        if QJOB_INFO['JOB_ID'] == JOB_ID:
            return

    JOB_INFO = GLB_TREE[JOB_ID]
    print("SUBMISSION\n")
    FPATH = os.path.join(TG_DIR, GLB_VAR['BACKEND_DIR'], 'CONTAINER_SHELL', f"{JOB_INFO['NAME']}.sh")
    if not check_file_exist(FPATH):
        fail_process(f"{JOB_ID}003", f"Missing script file {FPATH}")

    QJOB_PROCESS = {'JOB_ID': JOB_ID}

    QJOB_PROCESS['PROCESS'] = subprocess.Popen(
        [FPATH],
        stdin=subprocess.PIPE,
        stdout=open(os.path.join(TG_DIR, 'BACKEND/LOG/SGE_LOG', f'TG_{JOB_ID}_{time.strftime("%Y_%m_%d_%H_%M_%S")}.o'), 'w'),
        stderr=open(os.path.join(TG_DIR, 'BACKEND/LOG/SGE_LOG', f'TG_{JOB_ID}_{time.strftime("%Y_%m_%d_%H_%M_%S")}.e'), 'w'),
        cwd=TG_DIR
    )
    if QJOB_PROCESS['PROCESS'] == False:
        fail_process(f"{JOB_ID}004", f"Unable to submit job {JOB_INFO['NAME']}")
    GLB_RUN_JOBS[QJOB] = QJOB_PROCESS


def single_cpu_log(JOB_ID):
    global GLB_TREE
    global GLB_VAR
    global TG_DIR
    JOB_INFO = GLB_TREE[JOB_ID]
    print(f"\tEND {JOB_ID}\t{JOB_INFO['NAME']}\n")
    LOG_FILE = os.path.join(TG_DIR, GLB_VAR['LOG_DIR'], f"{JOB_INFO['NAME']}.log")
    print(f"\tLOG: {LOG_FILE}\n")
    PROCESS_DATA = {}
    if not check_file_exist(LOG_FILE):
        print("\tLOG NOT FOUND - JOB KILLED OR DIED\n")
        GLB_TREE[JOB_ID]['TIME']['CHECK'] = time.time()
    else:
        content =''
        with open(LOG_FILE, 'rb') as f:
            content = f.read()
        PROCESS_DATA = json.loads(content)
        print(f"\tSTATUS:{PROCESS_DATA['STATUS']}\n")
        print(f"\tPROCESS DIR:{PROCESS_DATA['DIR']}\n")


def single_cpu_validate(JOB_ID):
    add_log("VALIDATE JOB")

    global GLB_TREE
    global GLB_VAR
    global TG_DIR
    JOB_INFO = GLB_TREE[JOB_ID]
    print(f"\tEND {JOB_ID}\t{JOB_INFO['NAME']}\n")

    LOG_FILE = os.path.join(TG_DIR, GLB_VAR['LOG_DIR'], f"{JOB_INFO['NAME']}.log")
    print(f"\tLOG:{LOG_FILE}\n")
    PROCESS_DATA = {}
    if not check_file_exist(LOG_FILE):
        PROCESS_DATA['STATUS'] = 'QUIT'
        GLB_TREE[JOB_ID]['TIME']['CHECK'] = time.time()
    else:
        content =''
        with open(LOG_FILE, 'rb') as f:
            content = f.read()
        PROCESS_DATA = json.loads(content)
        print(f"\tSTATUS:{PROCESS_DATA['STATUS']}\n")
        print(f"\tPROCESS DIR:{PROCESS_DATA['DIR']}\n")

        GLB_TREE[JOB_ID]['TIME']['CHECK'] = time.time()
        if PROCESS_DATA['STATUS'] == 'SUCCESS':
            GLB_TREE[JOB_ID]['TIME']['DEV'] = time.time()
            GLB_TREE[JOB_ID]['TIME']['DEV_DIR'] = PROCESS_DATA['DIR']

    STATUS_MAP = {'SUCCESS': 'T', 'VALID': 'T', 'QUIT': 'Q'}
    STATUS = 'F'

    if PROCESS_DATA['STATUS'] in STATUS_MAP:
        STATUS = STATUS_MAP[PROCESS_DATA['STATUS']]
    print(f"PROCESS DATA STATUS: {PROCESS_DATA['STATUS']}\t{STATUS}\n")
    refresh_timestamp(JOB_ID, STATUS)





















def load_timestamps():
    global TG_DIR
    global GLB_TREE
    global GLB_TIME
    global GLB_VAR

    MAX_PUBLIC_ID = -1
    MAX_PRIVATE_ID = -1
    MAP = {}
    for ID, T in GLB_TREE.items():
        MAP[T['NAME']] = ID
    if 'PUBLIC_SCHEMA' not in GLB_VAR:
        send_kill_mail('000050', 'No PUBLIC_SCHEMA defined ')

    res = run_query(f"SELECT * FROM {GLB_VAR['PUBLIC_SCHEMA']}.biorels_timestamp")

    for line in res:
        MAX_PUBLIC_ID = max(MAX_PUBLIC_ID, line['br_timestamp_id'])
        try:
            
            last_date=str(line['last_check_date'])
            pos=last_date.find('.');
            if (pos!=-1):
                last_date=last_date.split('.')[0]
            GLB_TREE[MAP[line['job_name']]]['TIME'] = { 
                'DEV': -1,
                'DEV_DIR': -1,
                'CHECK': -1
            }

            if (line['processed_date'] != '' and  line['processed_date'] is not None): 
                proc_date=str(line['processed_date'])
                pos=proc_date.find('.');
                if (pos!=-1):
                    proc_date=proc_date.split('.')[0]
                GLB_TREE[MAP[line['job_name']]]['TIME']['DEV']= int(datetime.strptime(proc_date, '%Y-%m-%d %H:%M:%S').timestamp())
        
            if (line['current_dir'] != '' and  line['current_dir'] is not None): 
                GLB_TREE[MAP[line['job_name']]]['TIME']['DEV_DIR']= line['current_dir']
        
            if (line['last_check_date'] != '' and  line['last_check_date'] is not None): 
                chk_date=str(line['last_check_date'])
                pos=chk_date.find('.');
                if (pos!=-1):
                    chk_date=chk_date.split('.')[0]
                GLB_TREE[MAP[line['job_name']]]['TIME']['CHECK']= int(datetime.strptime(chk_date, '%Y-%m-%d %H:%M:%S').timestamp())
            
        except KeyError:
            print('WARNING: ' + line['job_name'] + ' not in GLB_TREE!')

    if GLB_VAR['PRIVATE_ENABLED'] == 'T':
        if 'SCHEMA_PRIVATE' not in GLB_VAR:
            send_kill_mail('000050', 'No SCHEMA_PRIVATE defined ')
        res = run_query(f"SELECT * FROM {GLB_VAR['SCHEMA_PRIVATE']}.biorels_timestamp")

        for line in res:
            MAX_PRIVATE_ID = max(MAX_PRIVATE_ID, line['br_timestamp_id'])
            proc_date=str(line['processed_date'])
            pos=proc_date.find('.');
            if (pos!=-1):
                proc_date=proc_date.split('.')[0]

            GLB_TREE[MAP[line['job_name']]]['TIME'] = { 
                'DEV': -1,
                'DEV_DIR': -1,
                'CHECK': -1
            }

            if (line['processed_date'] != '' and  line['processed_date'] is not None): 
                proc_date=str(line['processed_date'])
                pos=proc_date.find('.');
                if (pos!=-1):
                    proc_date=proc_date.split('.')[0]
                GLB_TREE[MAP[line['job_name']]]['TIME']['DEV']= int(datetime.strptime(proc_date, '%Y-%m-%d %H:%M:%S').timestamp())
        
            if (line['current_dir'] != '' and  line['current_dir'] is not None): 
                GLB_TREE[MAP[line['job_name']]]['TIME']['DEV_DIR']= line['current_dir']
        
            if (line['last_check_date'] != '' and  line['last_check_date'] is not None): 
                chk_date=str(line['last_check_date'])
                pos=chk_date.find('.');
                if (pos!=-1):
                    chk_date=chk_date.split('.')[0]
                GLB_TREE[MAP[line['job_name']]]['TIME']['CHECK']= int(datetime.strptime(chk_date, '%Y-%m-%d %H:%M:%S').timestamp())


            

    for INFO in GLB_TREE.values():
        if 'TIME' in INFO:
            continue
        if INFO['IS_PRIVATE'] == 1:
            MAX_PRIVATE_ID += 1
            query = (
                f"INSERT INTO {GLB_VAR['SCHEMA_PRIVATE']}.biorels_timestamp (br_timestamp_id, job_name) "
                f"VALUES ({MAX_PRIVATE_ID}, '{INFO['NAME']}')"
            )
            if not run_query_no_res(query):
                send_kill_mail('000050', f'Unable to insert in {GLB_VAR["SCHEMA_PRIVATE"]}.biorels_timestamp')
        else:
            MAX_PUBLIC_ID += 1
            query = (
                f"INSERT INTO {GLB_VAR['PUBLIC_SCHEMA']}.biorels_timestamp (br_timestamp_id, job_name) "
                f"VALUES ({MAX_PUBLIC_ID}, '{INFO['NAME']}')"
            )
            if not run_query_no_res(query):
                send_kill_mail('000050', f'Unable to insert in {GLB_VAR["PUBLIC_SCHEMA"]}.biorels_timestamp')
        INFO['TIME'] = {'DEV': -1, 'DEV_DIR': -1, 'CHECK': -1}

def load_timestamp_file(PR_FILE):
    global TG_DIR
    global GLB_TREE
    global GLB_TIME
    global GLB_VAR

    with open(PR_FILE, 'r') as fp:
        MAP = {T['NAME']: ID for ID, T in GLB_TREE.items()}

        while True:
            line = fp.readline()
            if not line:
                break

            if line.startswith("#") or line.strip() == "":
                continue

            tab = list(filter(None, line.strip().split("\t")))
            if len(tab) != 4:
                send_kill_mail('000054', f"{line} only has {len(tab)} columns")
            if tab[0] not in MAP:
                send_kill_mail('000055', f"Job name {tab[0]} does not exist")
            if not tab[1].isnumeric():
                send_kill_mail('000056', f"{tab[1]} is not numeric for column 1 in line {line}")

            GLB_TREE[MAP[tab[0]]]['TIME'] = {
                'DEV': int(tab[1]),
                'DEV_DIR': tab[2],
                'CHECK': int(tab[3])
            }

def refresh_timestamp(JOB_ID, STATUS):
    add_log(f"REFRESH TIMESTAMP {JOB_ID} -> {STATUS}")
    global TG_DIR
    global GLB_TREE
    global GLB_TIME
    global GLB_VAR

    JOB_INFO = GLB_TREE[JOB_ID]
    query = f"UPDATE {GLB_VAR['SCHEMA_PRIVATE'] if JOB_INFO['IS_PRIVATE'] == 1 else GLB_VAR['PUBLIC_SCHEMA']}.biorels_timestamp " \
            f"SET processed_date='{datetime.fromtimestamp(JOB_INFO['TIME']['DEV']).strftime('%Y-%m-%d %H:%M:%S')}', " \
            f"current_dir='{JOB_INFO['TIME']['DEV_DIR']}', is_success='{STATUS}', " \
            f"last_check_date='{datetime.fromtimestamp(JOB_INFO['TIME']['CHECK']).strftime('%Y-%m-%d %H:%M:%S')}' " \
            f"WHERE job_name='{JOB_INFO['NAME']}'"
    print(query)
    if not run_query_no_res(query):
        send_kill_mail('000060', f"Failed to update timestamp \n{query}")








































def bc_submit_news(NEWS):
    try:
        global DB_CONN
        CREATED_NEWS_ID = ''
        USER_ID = NEWS['USER_ID']
        NEWS_CONTENT = NEWS['NEWS_CONTENT']
        NEWS_HTML = NEWS['NEWS_HTML']
        TITLE = NEWS['TITLE']

        query = "INSERT INTO news VALUES (nextval('news_sq'), %s, %s, CURRENT_DATE, CURRENT_TIMESTAMP, %s, %s, %s, %s) RETURNING news_id"
        stmt = DB_CONN.prepare(query)
        stmt.execute(TITLE, NEWS_HTML, USER_ID, NEWS['SOURCE'], NEWS_CONTENT, NEWS['HASH'])
        row = stmt.fetchone()

        if row:
            CREATED_NEWS_ID = row['news_id']
        else:
            raise Exception('Unable to insert news')

        return CREATED_NEWS_ID

    except Exception as e:
        print(e)
        return None


def send_mail(ERROR_ID, INFO):
    print("SEND MAIL")
    print(INFO)
    global GLB_VAR

    if 'EMAIL' in GLB_VAR and 'EMAIL_FROM' in GLB_VAR:
        tab = GLB_VAR['EMAIL'].split('|')
        try:
            for EM in tab:
                # Assuming EMAIL_FROM is also defined somewhere
                server = smtplib.SMTP('localhost')
                server.sendmail(GLB_VAR['EMAIL_FROM'], EM, 'BIORELS - ' + ERROR_ID + '\n' + INFO)
                server.quit()
        except Exception:
            pass

def send_kill_mail(ERROR_ID, INFO):
    print("SEND KILL MAIL")
    send_mail(ERROR_ID, INFO)
    exit(1)

def bc_private_submit_news(NEWS):
    try:
        global GLB_VAR
        global DB_CONN
        CREATED_NEWS_ID = ''
        USER_ID = NEWS['USER_ID']
        NEWS_CONTENT = NEWS['NEWS_CONTENT']
        NEWS_HTML = NEWS['NEWS_HTML']
        TITLE = NEWS['TITLE']

        query = f"INSERT INTO {GLB_VAR['SCHEMA_PRIVATE']}.news VALUES (nextval('{GLB_VAR['SCHEMA_PRIVATE']}.news_sq'), %s, %s, CURRENT_DATE, CURRENT_TIMESTAMP, %s, %s, %s, %s) RETURNING news_id"
        stmt = DB_CONN.prepare(query)
        stmt.execute(TITLE, NEWS_HTML, USER_ID, NEWS['SOURCE'], NEWS_CONTENT, NEWS['HASH'])
        row = stmt.fetchone()

        if row:
            CREATED_NEWS_ID = row['news_id']
        else:
            raise Exception('Unable to insert news')

        return CREATED_NEWS_ID

    except Exception as e:
        print(e)
        return None

def get_foreign_tables(table, table_schema, _filter=None):
    global GLB_VAR
    global SCHEMAS

    SCHEMAS = [GLB_VAR['PUBLIC_SCHEMA']]
    if GLB_VAR['SCHEMA_PRIVATE'] != '':
        SCHEMAS.append(GLB_VAR['SCHEMA_PRIVATE'])

    add_log(f"Find parent dependent table for {table}")
    query = f"""
        SELECT DISTINCT
            tc.table_schema,
            tc.constraint_name,
            tc.table_name AS source_table_name,
            kcu.column_name AS source_column_name,
            ccu.constraint_schema,
            ccu.table_schema AS foreign_table_schema,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name
        FROM
            information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
        WHERE
            tc.constraint_type = 'FOREIGN KEY' AND tc.table_name='{table}' AND tc.table_schema='{table_schema}';
    """

    result = run_query(query)
    if result is None:
        fail_process("FCT_001", 'Unable to get dependent tables')

    dep_tables = {}
    for line in result:
        if line[0] not in SCHEMAS or line[5] not in SCHEMAS:
            continue
        if _filter is not None and line[2] in _filter:
            continue

        dep_tables[line[3]] = {
            'SCHEMA': line[5],
            'TABLE': line[6],
            'COLUMN': line[7]
        }

    return dep_tables

def get_dep_table_list(table, table_schema, _filter=None):
    global GLB_VAR
    global SCHEMAS

    SCHEMAS = [GLB_VAR['PUBLIC_SCHEMA']]
    if GLB_VAR['SCHEMA_PRIVATE'] != '':
        SCHEMAS.append(GLB_VAR['SCHEMA_PRIVATE'])

    add_log(f"Find child dependent table for {table}")
    query = f"""
        SELECT DISTINCT
            tc.table_schema,
            tc.constraint_name,
            tc.table_name,
            kcu.column_name,
            ccu.constraint_schema,
            ccu.table_schema AS foreign_table_schema,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name
        FROM
            information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
        WHERE
            tc.constraint_type = 'FOREIGN KEY' AND ccu.table_name='{table}' AND ccu.table_schema='{table_schema}';
    """

    result = run_query(query)
    if result is None:
        fail_process("FCT_001", 'Unable to get dependent tables')

    dep_tables = {}
    for line in result:
        if line[0] not in SCHEMAS or line[5] not in SCHEMAS:
            continue
        if _filter is not None and line[2] in _filter:
            continue

        dep_tables.setdefault(line[7], []).append({
            'SCHEMA': line[0],
            'TABLE': line[2],
            'COLUMN': line[3]
        })

    return dep_tables


def define_taxon_list():
    global GLB_VAR
    global GLB_TREE
    global TG_DIR
    TAXON_LIMIT_LIST = []

    if GLB_VAR['TAXON_LIMIT'] != 'N/A':
        tab = GLB_VAR['TAXON_LIMIT'].split('|')
        for t in tab:
            if not t.isnumeric():
                fail_process("FCT_DEFTAXON_001", f"In CONFIG_GLOBAL>TAXON_LIMIT {t} must be numeric")
            TAXON_LIMIT_LIST.append(int(t))

    molecule_INFO = GLB_TREE[get_job_id_by_name('dl_chembl')]
    if molecule_INFO['ENABLED'] == 'T' and molecule_INFO['TIME']['DEV_DIR'] != 'N/A':
        res = run_query("SELECT DISTINCT tax_id FROM public.target_dictionary")
        if res is False:
            fail_process("FCT_DEFTAXON_002", 'Unable to get targets from chEMBL')
        for line in res:
            if line['tax_id'] == '':
                continue
            if int(line['tax_id']) not in TAXON_LIMIT_LIST:
                TAXON_LIMIT_LIST.append(int(line['tax_id']))

    uniprot_INFO = GLB_TREE[get_job_id_by_name('dl_proteome')]
    if uniprot_INFO['ENABLED'] == 'T' and uniprot_INFO['TIME']['DEV_DIR'] != '-1':
        W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'])
        if not os.path.isdir(W_DIR):
            fail_process("FCT_DEFTAXON_003", f'NO {W_DIR} found ')
        W_DIR = os.path.join(W_DIR, uniprot_INFO['DIR'])
        if not os.path.isdir(W_DIR):
            fail_process("FCT_DEFTAXON_004", f'Unable to find and create {W_DIR}')
        W_DIR = os.path.join(W_DIR, uniprot_INFO['TIME']['DEV_DIR'])
        if not os.path.isdir(W_DIR):
            fail_process("FCT_DEFTAXON_005", f'Unable to find new process dir {W_DIR}')
        W_DIR = os.path.join(W_DIR, 'PROTEOMES')
        if not os.path.isdir(W_DIR):
            fail_process("FCT_DEFTAXON_006", f'Unable to find PROTEOMES')
        if os.path.isfile(os.path.join(W_DIR, 'proteome_list')):
            with open(os.path.join(W_DIR, 'proteome_list'), 'r') as fp:
                for line in fp:
                    if line == '':
                        continue
                    tab = line.strip().split('\t')
                    if len(tab) < 4 or tab[3] == '':
                        continue
                    tax_ids = [int(t) for t in tab[3].split('|') if t.isnumeric()]
                    for t in tax_ids:
                        if t not in TAXON_LIMIT_LIST:
                            TAXON_LIMIT_LIST.append(t)

    uniprot_INFO = GLB_TREE[get_job_id_by_name('dl_swissprot')]
    if uniprot_INFO['ENABLED'] == 'T' and uniprot_INFO['TIME']['DEV_DIR'] != '-1':
        W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'])
        if not os.path.isdir(W_DIR):
            fail_process("FCT_DEFTAXON_008", f'NO {W_DIR} found ')
        W_DIR = os.path.join(W_DIR, uniprot_INFO['DIR'])
        if not os.path.isdir(W_DIR):
            fail_process("FCT_DEFTAXON_009", f'Unable to find and create {W_DIR}')
        W_DIR = os.path.join(W_DIR, uniprot_INFO['TIME']['DEV_DIR'])
        if not os.path.isdir(W_DIR):
            fail_process("FCT_DEFTAXON_010", f'Unable to find new process dir {W_DIR}')
        W_DIR = os.path.join(W_DIR, 'SPROT')
        if not os.path.isdir(W_DIR):
            fail_process("FCT_DEFTAXON_011", f'Unable to find SPROT')
        if os.path.isfile(os.path.join(W_DIR, 'sprot_list')):
            with open(os.path.join(W_DIR, 'sprot_list'), 'r') as fp:
                for line in fp:
                    if line == '':
                        continue
                    tab = line.strip().split('\t')
                    if len(tab) < 4 or tab[3] == '':
                        continue
                    tax_ids = [int(t) for t in tab[3].split('|') if t.isnumeric()]
                    for t in tax_ids:
                        if t not in TAXON_LIMIT_LIST:
                            TAXON_LIMIT_LIST.append(t)

    TAXON_LIMIT_LIST.sort()
    add_log(f"{len(TAXON_LIMIT_LIST)} taxons to consider: {';'.join(map(str, TAXON_LIMIT_LIST))}")
    return TAXON_LIMIT_LIST


def load_biotypes():
    global TG_DIR
    global GLB_VAR
    BIOTYPES = {}
    res = run_query("SELECT seq_type,seq_btype_id FROM seq_btype")
    if res is False:
        fail_process("FCT_BIOTYPE_001", 'Unable to retrieve biotypes')
    for l in res:
        BIOTYPES[l['seq_type']] = l['seq_btype_id']

    if BIOTYPES:
        return BIOTYPES

    T_DIR = os.path.join(TG_DIR, GLB_VAR['STATIC_DIR'], 'GENOME/mapping')
    if not check_file_exist(T_DIR):
        fail_process("FCT_BIOTYPE_002", f'Unable to find {T_DIR}')

    res = run_query("SELECT so_entry_id,so_name FROM so_entry ")
    if res is False:
        fail_process("FCT_BIOTYPE_003", 'Unable to retrieve sequence ontology')
    SO = {line['so_name']: line['so_entry_id'] for line in res}

    with open(T_DIR, 'r') as fp:
        for line in fp:
            if line == '':
                continue
            tab = line.strip().split('\t')
            if tab[0] in BIOTYPES:
                continue
            SEQ_TYPE_ID = max(BIOTYPES.values()) + 1 if BIOTYPES else 1
            SO_ENTRY_ID = 'NULL'
            if len(tab) > 1 and tab[1] != '' and tab[1] in SO:
                SO_ENTRY_ID = SO[tab[1]]
            query = f"INSERT INTO SEQ_BTYPE (SEQ_BTYPE_ID, SEQ_TYPE, SO_ENTRY_ID) VALUES ({SEQ_TYPE_ID}, '{tab[0]}', {SO_ENTRY_ID})"
            if not run_query_no_res(query):
                fail_process("FCT_BIOTYPE_005", 'Unable to insert in SEQ_BTYPE')
            BIOTYPES[tab[0]] = SEQ_TYPE_ID

    return BIOTYPES

def get_dep_tables(table, table_schema, _filter=None):
    global GLB_VAR
    global SCHEMAS

    SCHEMAS = [GLB_VAR['PUBLIC_SCHEMA']]
    if GLB_VAR['SCHEMA_PRIVATE'] != '':
        SCHEMAS.append(GLB_VAR['SCHEMA_PRIVATE'])

    add_log(f"Find child dependent table for {table}")
    query = f"""
       SELECT DISTINCT                            
	tc.table_schema, 
	tc.constraint_name, 
	tc.table_name, 
	kcu.column_name, 
	ccu.constraint_schema,
	ccu.table_schema AS foreign_table_schema,
	ccu.table_name AS foreign_table_name,
	ccu.column_name AS foreign_column_name 
	FROM 
	information_schema.table_constraints AS tc 
	JOIN information_schema.key_column_usage AS kcu
	  ON tc.constraint_name = kcu.constraint_name
	  AND tc.table_schema = kcu.table_schema
	JOIN information_schema.constraint_column_usage AS ccu
	  ON ccu.constraint_name = tc.constraint_name
	  AND ccu.table_schema = tc.table_schema
	WHERE   tc.constraint_type = 'FOREIGN KEY' AND ccu.table_name='{table}' AND ccu.table_schema='{table_schema}';
    """

    result = run_query(query)
    
    if result is None:
        fail_process("FCT_001", 'Unable to get dependent tables')

    dep_tables = {}
    for line in result:
        
        if line['table_schema'] not in SCHEMAS or line['constraint_schema'] not in SCHEMAS:
            continue
        if _filter is not None and line['table_name'] in _filter:
            continue
        dep_tables[line['constraint_schema']+'.'+line['table_name']]=line['constraint_name'];
        
    
    return dep_tables

def get_median(arr):
    if not isinstance(arr, list):
        raise Exception('$arr must be an array!')

    if not arr:
        return False

    num = len(arr)
    middle_val = (num - 1) // 2

    if num % 2:
        return arr[middle_val]
    else:
        low_mid = arr[middle_val]
        high_mid = arr[middle_val + 1]
        return (low_mid + high_mid) / 2

def dl_file(path, max_tries=3, name='', timeout=-1):
    for i in range(max_tries):
        job = f'wget -q -c --no-check-certificate '
        if name:
            job += f'-O "{name}" '
        if timeout != -1:
            job = f'timeout {timeout} {job}'

        return_code = subprocess.call(f'{job} "{path}"', shell=True)

        if return_code == 0:
            return True
        time.sleep(1)

    return False

def del_tree(directory):
    # Use os.path.join to create paths for cross-platform compatibility
    files = [f for f in os.listdir(directory) if os.path.isfile(os.path.join(directory, f))]
    for file in files:
        os.remove(os.path.join(directory, file))
    os.rmdir(directory)

def unzip(path):
    return_code = subprocess.call(['unzip', path])
    return return_code == 0

def define_taxon_list():
    global GLB_VAR
    global GLB_TREE
    global TG_DIR
    taxon_limit_list = []
    
    if GLB_VAR['TAXON_LIMIT'] != 'N/A':
        # Split and map to integers in a more Pythonic way
        taxon_limit_list.extend(map(int, GLB_VAR['TAXON_LIMIT'].split('|')))

    chembl_info = GLB_TREE[get_job_id_by_name('dl_chembl')]
    if chembl_info['ENABLED'] == 'T' and chembl_info['TIME']['DEV_DIR'] != 'N/A':
        res = run_query(f"SELECT DISTINCT tax_id FROM {GLB_VAR['CHEMBL_SCHEMA']}.target_dictionary")
        # Extend the list directly with the result
        taxon_limit_list.extend(map(int, res))

    uniprot_info_proteome = GLB_TREE[get_job_id_by_name('dl_proteome')]
    if uniprot_info_proteome['ENABLED'] == 'T' and uniprot_info_proteome['TIME']['DEV_DIR'] != '-1':
        w_dir = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], uniprot_info_proteome['DIR'], uniprot_info_proteome['TIME']['DEV_DIR'], 'PROTEOMES')
        if os.path.exists(os.path.join(w_dir, 'proteome_list')):
            with open(os.path.join(w_dir, 'proteome_list'), 'r') as fp:
                for line in fp:
                    if not line.strip():
                        continue
                    tab = line.split('\t')
                    if len(tab) >= 4 and tab[3]:
                        tax_ids = map(int, tab[3].split('|'))
                        taxon_limit_list.extend(tax_ids)

    uniprot_info_sprot = GLB_TREE[get_job_id_by_name('dl_swissprot')]
    if uniprot_info_sprot['ENABLED'] == 'T' and uniprot_info_sprot['TIME']['DEV_DIR'] != '-1':
        w_dir = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], uniprot_info_sprot['DIR'], uniprot_info_sprot['TIME']['DEV_DIR'], 'SPROT')
        if os.path.exists(os.path.join(w_dir, 'sprot_list')):
            with open(os.path.join(w_dir, 'sprot_list'), 'r') as fp:
                for line in fp:
                    if not line.strip():
                        continue
                    tab = line.split('\t')
                    if len(tab) >= 4 and tab[3]:
                        tax_ids = map(int, tab[3].split('|'))
                        taxon_limit_list.extend(tax_ids)

    taxon_limit_list = list(set(taxon_limit_list))  # Remove duplicates
    taxon_limit_list.sort()

    add_log(f"{len(taxon_limit_list)} taxons to consider: {';'.join(map(str, taxon_limit_list))}")
    return taxon_limit_list

def load_biotypes():
    global TG_DIR
    global GLB_VAR
    global JOB_INFO
    biotypes = {}
    res = run_query("SELECT seq_type,seq_btype_id FROM seq_btype")
    if res is False:
        fail_process(f"{JOB_ID}F01", 'Unable to retrieve biotypes')

    for l in res:
        biotypes[l['seq_type']] = l['seq_btype_id']

    # Load the mapping file if no biotypes are found
    if not biotypes:
        t_dir = os.path.join(TG_DIR, GLB_VAR['STATIC_DIR'], JOB_INFO['DIR'], 'mapping')
        if not check_file_exist(t_dir):
            fail_process(f"{JOB_ID}F02", f'Unable to find {t_dir}')

        res = run_query("SELECT so_entry_id,so_name FROM so_entry ")
        if res is False:
            fail_process(f"{JOB_ID}F03", 'Unable to retrieve sequence ontology')

        so = {line['so_name']: line['so_entry_id'] for line in res}

        with open(t_dir, 'r') as fp:
            if biotypes:
                seq_type_id = max(biotypes.values())
            else:
                seq_type_id = 0

            for line in fp:
                if not line.strip():
                    continue
                tab = line.split('\t')
                if tab[0] in biotypes:
                    continue
                seq_type_id += 1
                so_entry_id = 'NULL'
                if len(tab) >= 2 and tab[1] and tab[1] in so:
                    so_entry_id = so[tab[1]]
                query = f"INSERT INTO SEQ_BTYPE (SEQ_BTYPE_ID, SEQ_TYPE,SO_ENTRY_ID) VALUES ({seq_type_id}, '{tab[0]}', {so_entry_id})"
                if not run_query_no_res(query):
                    fail_process(f"{JOB_ID}F05", 'Unable to insert in SEQ_BTYPE')
                biotypes[tab[0]] = seq_type_id

    return biotypes

def download_ftp_file(path, outfile, tag='', w_path=True, create_dir=True):
    if not check_file_exist(outfile + '.html') and not dl_file(path, 3, outfile + '.html'):
        fail_process("FCT_001", f'Unable to download {outfile}.html')
    
    print(f'DOWNLOAD {path} TO {outfile}\n')
    with open(outfile + '.html', 'r') as fpB:
        if create_dir and not os.path.isdir(outfile) and not os.mkdir(outfile):
            fail_process("FCT_003", f'Unable to create {outfile} directory')

        for line in fpB:
            tab = line.split(">")
            if len(tab) < 6:
                continue
            
            if tab[5] == '':
                continue
            
            name = tab[5].split('"')[1]
            
            if tag != '' and tag not in name:
                continue

            print(f'DOWNLOADING {path}/{name}\t')
            path_all = '' if not w_path else path + '/'
            path_all += name
            out = '' if not create_dir else outfile + '/'
            out += name

            if check_file_exist(out):
                print('EXISTS\n')
                continue

            if not dl_file(path_all, 3, out):
                fail_process("FCT_004", f'Unable to download {path}{name}')
            print('DONE\n')

    os.remove(outfile + '.html')

def untar(path):
    return_code = subprocess.call(['tar', '-zxf', path])
    return return_code == 0

def ungzip(path):
    return_code = subprocess.call(['gzip', '-f', '-d', path])
    return return_code == 0

def unbzip2(path):
    return_code = subprocess.call(['bzip2', '-f', '-d', path])
    return return_code == 0

def check_file_exist(file):
    if not os.path.isfile(file):
        return False
    
    os.stat(file)
    if os.path.getsize(file) == 0:
        return False
    
    return True

def get_line_count(file):
    linecount = 0
    with open(file, 'r') as handle:
        for line in handle:
            linecount += line.count(os.linesep)

    return linecount

def validate_line_count(file, min_lines):
    return get_line_count(file) >= min_lines

def get_curr_date():
    return subprocess.check_output(['date', '+%Y-%m-%d']).decode().strip()

def get_tsp_to_date(timestamp):
    return subprocess.check_output(['date', '-d', f'@{timestamp}', '+%Y-%m-%d-%H-%M-%S']).decode().strip()

def get_curr_date_time():
    return subprocess.check_output(['date', '+%Y-%m-%d-%H-%M-%S']).decode().strip()



def is_dir_empty(directory):
    if not path.exists(directory) or not path.isdir(directory):
        return None
    return len(os.listdir(directory)) == 2

def clean_directory(directory):
    if directory == '/' or directory == '':
        return True
    for root, dirs, files in os.walk(directory, topdown=False):
        for file in files:
            try: 
                os.remove(path.join(root, file))
            except OSError as error:
                return False
        for dir_name in dirs:
            try:
                os.rmdir(path.join(root, dir_name))
            except OSError as error:
                return False
    try:
        os.rmdir(directory)
    except OSError as error:
        return False
    return True

def add_log(info):
    global PROCESS_CONTROL
    global TG_DIR
    global GLB_VAR
    TIME=microtime_float()
    print(info)
    if PROCESS_CONTROL['STEP'] > 0:
        PROCESS_CONTROL['LOG'][PROCESS_CONTROL['STEP'] - 1] += '|' + str(round(TIME - PROCESS_CONTROL['STEP_TIME'], 2))
    PROCESS_CONTROL['STEP'] += 1
    PROCESS_CONTROL['LOG'].append(get_curr_date_time() + '|' + info)
    PROCESS_CONTROL['STEP_TIME'] = TIME

    with open(path.join(TG_DIR, GLB_VAR['LOG_DIR'], PROCESS_CONTROL['JOB_NAME'] + '.log'), 'w') as fp:
        fp.write(str(PROCESS_CONTROL) + '\n')

def prep_string(name):
    # name = name.replace("'", "''")
    name = name.replace('"', '""')
    return name

def fail_process(ID, INFO, PCC=None):
    global PROCESS_CONTROL
    global TG_DIR
    global GLB_VAR
    global START_SCRIPT_TIME
    global START_SCRIPT_TIMESTAMP
    global GLB_TREE
    GLB = False

    print ("TEST")
    print(PROCESS_CONTROL)
    if PCC is None:
        PCC = PROCESS_CONTROL
        GLB = True

    PCC['STATUS'] = 'FAIL'

    TIME = time.time()
    JOB_ID = get_job_id_by_name(PCC['JOB_NAME'])
    JOB_INFO = GLB_TREE[JOB_ID]
    SCHEMA = GLB_VAR['PUBLIC_SCHEMA']

    if JOB_INFO['IS_PRIVATE'] == 1:
        SCHEMA = GLB_VAR['SCHEMA_PRIVATE']

    res = run_query_no_res(
        f"INSERT INTO {SCHEMA}.biorels_job_history VALUES ((SELECT br_timestamp_id FROM {SCHEMA}.biorels_timestamp where job_name='{PCC['JOB_NAME']}'), '{START_SCRIPT_TIMESTAMP}', {round(TIME - START_SCRIPT_TIME)}, 'F', '{INFO}')"
    )
    print("PCC")
    print(PCC)
    print("STEP "+ str(PCC['STEP']))

    if PCC['STEP'] > 0:
        if PCC['STEP'] not in PCC['LOG']:
            
            PCC['LOG'].insert(PCC['STEP'], '|' + str(round(TIME - PCC['STEP_TIME'], 2)))
        else:
            PCC['LOG'][PCC['STEP']] += '|' + str(round(TIME - PCC['STEP_TIME'], 2))

    PCC['STEP'] += 1
    PCC['LOG'].append(f"{get_curr_date_time()}|{INFO}")
    PCC['LOG'].append(f"TIME_COMPUT|{round(TIME - START_SCRIPT_TIME, 3)}\n")

    PCC['STEP_TIME'] = TIME
    print(f"{ID}\t{INFO}")

    log_file_path = os.path.join(TG_DIR, GLB_VAR['LOG_DIR'], PCC['JOB_NAME'] + '.log')
    with open(log_file_path, 'w') as fp:
        fp.write(str(PCC) + "\n")

    if not os.getenv("MONITOR_JOB"):
        qengine_validate(get_job_id_by_name(PCC['JOB_NAME']))

    if GLB:
        send_kill_mail(ID, INFO)
    else:
        send_mail(ID, INFO)





def success_process(status_tag='SUCCESS', pcc=None):
    add_log("SUCCESS PROCESS")
    global MAIL_COMMENTS
    global PROCESS_CONTROL
    global GLB_VAR
    global TG_DIR
    global GLB_TREE
    global START_SCRIPT_TIME
    global START_SCRIPT_TIMESTAMP
    GLB = False

    if pcc is None:
        pcc = PROCESS_CONTROL
        GLB = True
    
    pcc['STATUS'] = status_tag
    pcc['STEP'] += 1
    current_time = microtime_float()
    pprint.pp(pcc)
    job_id = get_job_id_by_name(pcc['JOB_NAME'])
    job_info = GLB_TREE[job_id]
    schema = GLB_VAR['PUBLIC_SCHEMA']

    if job_info['IS_PRIVATE'] == 1:
        schema = GLB_VAR['SCHEMA_PRIVATE']

    run_query_no_res("INSERT INTO " + schema + ".biorels_job_history VALUES ((SELECT br_timestamp_id FROM "+ schema+".biorels_timestamp where job_name='" + pcc['JOB_NAME'] + "'),'" + START_SCRIPT_TIMESTAMP + "'," + str(math.ceil(current_time - START_SCRIPT_TIME)) + ",'T',NULL)")

    pcc['LOG'].append(get_curr_date_time() + '|' + str(round(current_time - pcc['STEP_TIME'], 2)) + '|END')
    pcc['LOG'].append('TIME_COMPUT|' + str(round(current_time - START_SCRIPT_TIME, 3)) + "\n")
    pcc['STEP_TIME'] = current_time

    log_file = path.join(TG_DIR, GLB_VAR['LOG_DIR'], pcc['JOB_NAME'] + '.log')
    with open(log_file, 'w') as fp:
        fp.write(str(pcc) + "\n")
    print("PUT IN " + log_file + "\n")

    if MAIL_COMMENTS != []:
        send_mail(ID, '\n'.join(MAIL_COMMENTS))

    if not "MONITOR_JOB" in locals():
        qengine_validate(get_job_id_by_name(pcc['JOB_NAME']))

    if GLB:
        exit(0)

def get_job_id_by_name(name, is_primary=False):
    global GLB_TREE
    global JOB_ID
    for job_id, info in GLB_TREE.items():
        if info['NAME'] == name:
            if (is_primary):JOB_ID=job_id
            return job_id

    print("No job with the name: |" + name + "|\n")
    exit(1)

def create_lock():
    global TG_DIR
    global GLB_VAR
    lock_file = path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], 'lock')

    if check_file_exist(lock_file):
        former_pid = int(open(lock_file).read())
        if path.exists("/proc/" + str(former_pid)):
            print('Process currently running')
            exit()

    pid = os.getpid()
    with open(lock_file, 'w') as fp:
        fp.write(str(pid))


def update_release_date(id, tag, value):
    global TG_DIR
    global GLB_VAR

    res = run_query("SELECT * FROM biorels_datasource WHERE source_name = '"+ tag+"'")
    
    if res is False:
        send_kill_mail(id + '_FCT_UPDATE_RELEASE_DATE', "updateReleaseData - Unable to query biorels_datasource")

    if len(res) == 0:
        query = "INSERT INTO biorels_datasource (source_name, release_version, date_released) VALUES ('"+tag+"','"+value+"', CURRENT_TIMESTAMP)"
        
        if not run_query_no_res(query):
            send_kill_mail(id + '_FCT_UPDATE_RELEASE_DATE-INSERT', "updateReleaseData - Unable to insert biorels_datasource")
    else:
        query = "UPDATE biorels_datasource SET release_version='"+value+"', date_released=CURRENT_TIMESTAMP WHERE source_name='"+tag+"'"
        if not run_query_no_res(query):
            send_kill_mail(id + '_FCT_UPDATE_RELEASE_DATE-UPDATE', "updateReleaseData - Unable to update biorels_datasource")

def get_in_between(line, tag):
    
    pos = line.find('<' + tag)
    
    if pos == -1:
        return ""
    
    pos_s = line.find('>', pos + 1)
    
    if pos_s == -1:
        return ""
    
    pos_e = line.find('</' + tag, pos_s)
    
    if pos_e == -1:
        return ""
    
    return line[pos_s + 1: pos_e]

def get_tag(line, tag):
    pos = line.find(tag + '="')
    if pos == -1:
        return ''

    pos_s = line.find('"', pos + len(tag) + 3)
    if pos_s == -1:
        return ''

    return line[pos + len(tag) + 2: pos_s]

N_QUERY = 0

def delete_dir(dir_path):
    if not path.isdir(dir_path):
        return True

    if dir_path[-1] != '/':
        dir_path += '/'

    files = glob.glob(dir_path + '*', recursive=True)
    for file in files:
        if file == '.' or file == '..':
            continue

        if path.isdir(file):
            delete_dir(file)
        else:
            os.unlink(file)

    return shutil.rmtree(dir_path)

def run_clob_query(query, pos):
    global N_QUERY
    N_QUERY += 1
    
    try:
        global DB_CONN
        with DB_CONN.cursor() as cursor:
            cursor.execute(query)
            
            if query[:6] == "INSERT":
                return ""

            results = cursor.fetchall()
            if results:
                for l in results:
                    t = l[pos].read()
                    l[pos].close()
                    l[pos] = t

            return results

    except psycopg2.Error as e:
        print("Error while running query\n" + str(e) + "\n\n" + query + "\n")
        return False

def rrmdir(src):
    for file in os.listdir(src):
        full_path = path.join(src, file)
        if path.isdir(full_path):
            if not rrmdir(full_path):
                return False
        else:
            if not os.unlink(full_path):
                return False

    if not os.rmdir(src):
        return False

    return True


def array_change_key_case_recursive(arr):
    return {k.upper(): array_change_key_case_recursive(v) if isinstance(v, dict) else v for k, v in arr.items()}



def connect_db():
    global DB_CONN
    global GLB_VAR
    global DB_INFO
    global DB_SCHEMA
    if 'NO_DB_CONNECTION' not in globals():
        DB_INFO['HOST']= os.getenv('DB_HOST')
        DB_INFO['PORT']= os.getenv('DB_PORT')
        DB_INFO['NAME']= os.getenv('DB_NAME')
        DB_INFO['USER']= os.getenv('PGUSER')
        DB_INFO['PWD']= os.getenv('PGPASSWORD')
        
        DB_INFO['COMMAND'] = f"psql -h {DB_INFO['HOST']} -p {DB_INFO['PORT']} -U {DB_INFO['USER']} -d {DB_INFO['NAME']}"
        DB_CONN = None
        
        try:
            
            DB_CONN = psycopg2.connect(
                host=DB_INFO['HOST'],
                port=DB_INFO['PORT'],
                database=DB_INFO['NAME'],
                user=DB_INFO['USER'],
                password=DB_INFO['PWD']
            )
            DB_CONN.set_session(autocommit=True)
        except psycopg2.Error as e:
            raise Exception("Unable to connect to the database\n" + str(e))
       
        DB_SCHEMA = GLB_VAR['DB_SCHEMA']
        
        try:
            
            run_query_no_res('SET SESSION search_path TO ' + DB_SCHEMA + ';')
           
        except psycopg2.Error as e:
            raise Exception("Unable to set search_path\n" + str(e))


def get_db_info():
    global DB_CONN
    return DB_CONN

def run_query(query):
    global DB_CONN
    
    try:
        

        with DB_CONN.cursor() as cursor:
            cursor.execute(query)
            results = [dict(zip([column[0] for column in cursor.description], row)) for row in cursor.fetchall()]

            if 'SQL_UPPER_PARAM' in globals():
                return array_change_key_case_recursive(results)

            return results

    except psycopg2.Error as e:
        if e.pgcode == 'HY000':
            # Sleep and retry in case of transient error
            import time
            time.sleep(1200)
            return run_query(query)
        else:
            raise Exception(f"Error while running query\n{e}\n\n{query}\n\nERROR CODE: {e.pgcode}\n")
    

def run_query_no_res(query):
    global DB_CONN
    try:
        

        with DB_CONN.cursor() as cursor:
            cursor.execute(query)

        return True

    except psycopg2.Error as e:
        print(f"Error while running query\n{e}\n\n{query}\n\nERROR CODE: {e.pgcode}\n")
        return False
   

def run_query_to_file(query, file_path):
    global DB_CONN
    try:
       

        with DB_CONN.cursor() as cursor:
            cursor.execute(query)

            if query.upper().startswith("INSERT"):
                return False

            with open(file_path, 'w') as file:
                first = True
                for row in cursor.fetchall():
                    if first:
                        file.write("\t".join([str(column[0]) for column in cursor.description]) + "\n")
                        first = False
                    file.write("\t".join(map(str, row)) + "\n")

        return True

    except psycopg2.Error as e:
        print(f"Error while running query\n{e}\n\n{query}\n")
        return False
    

def get_current_release_date(name, job_id):
    global DB_CONN
    try:
        

        with DB_CONN.cursor(cursor_factory=RealDictCursor) as cursor:
            print(name)
            cursor.execute(f"SELECT * FROM biorels_datasource WHERE source_name = '{name}'")
            res = cursor.fetchall()
            print(res)
            if res:
                return res[0]['release_version']
            else:
                return "-1"

    except psycopg2.Error as e:
        print(f"Error while getting current release date\n{e}")
        return -1
    

def process_counterion(SCHEMA, STD_FNAME, FAILED_FNAME):
    global SOURCE_ID
    
    
    global STATS
    global GLB_VAR
    global DB_INFO
    global JOB_ID

    add_log(f"Processing counterion for {SCHEMA}")
    DBIDS={}
    FILES = {'sm_counterion': open('INSERT/sm_counterion.csv', 'w')}
    for name, file_handle in FILES.items():
        if not file_handle:
            fail_process("FCT_COUNTERION_001", f"Unable to open {name}.csv")

    for table_name in FILES.keys():
        res = run_query(f"SELECT MAX({table_name}_id) AS CO FROM {SCHEMA}.{table_name}")
        if res is False:
            fail_process("FCT_COUNTERION_002", f"Unable to get max id from {table_name}")
        if len(res) == 0:
            DBIDS[table_name] = 0
        elif res[0]['co']==None:
            DBIDS[table_name] = 1
        else:
            DBIDS[table_name] = res[0]['co']

    RECORD = {}
    STATS = {'INI_COUNTER': 0, 'NEW_COUNTER': 0}
    MAP_COUNTERIONS = {}

    add_log(f"\tProcessing standardized counterion for {SCHEMA}")

    # Now we start with standardized compounds
    with open(STD_FNAME, 'r') as fp:
        DONE_CT = {}
        HAS_NEW_COUNTER = False
        N_CT = 0

        for line in fp:
            line=line.strip()
            if (len(line)==0):continue
            N_CT += 1
            if N_CT % 5000 == 0:
                add_log(f"\t\tProcessed {N_CT} counterions")
            
            tab = line.split(" ")
            STD_COUNTER = tab[0]
            ORIG_COUNTER = tab[1]

            if ORIG_COUNTER in MAP_COUNTERIONS:
                continue

            res = run_query(f"SELECT * FROM {SCHEMA}.sm_counterion WHERE counterion_smiles = '{STD_COUNTER}'")
            if res is False:
                fail_process("FCT_COUNTERION_004", "Unable to query sm_counterion")

            if len(res)>0:
                MAP_COUNTERIONS[ORIG_COUNTER] = [res[0]['sm_counterion_id'], res[0]['counterion_smiles']]
                DONE_CT[STD_COUNTER] = res[0]['sm_counterion_id']
            else:
                SM_CT = -1
                if STD_COUNTER in DONE_CT:
                    SM_CT = DONE_CT[STD_COUNTER]
                    MAP_COUNTERIONS[ORIG_COUNTER] = [SM_CT, STD_COUNTER]
                    continue
                else:
                    HAS_NEW_COUNTER = True
                    DBIDS['sm_counterion'] += 1
                    SM_CT = DBIDS['sm_counterion']
                    DONE_CT[STD_COUNTER] = SM_CT
                    MAP_COUNTERIONS[ORIG_COUNTER] = [SM_CT, STD_COUNTER]
                    #run_query_no_res("INSERT INTO sm_counterion VALUES ("+SM_CT+",'"+ORIG_COUNTER+"','F'");
                    FILES['sm_counterion'].write(f"{SM_CT}\t{STD_COUNTER}\tT\n")

    # Proceed to process the rejected records
    if os.path.exists(FAILED_FNAME):
        add_log("\tProcessing Rejected counterions records")

        with open(FAILED_FNAME, 'r') as fp:
            N_CT = 0

            for line in fp:
                line=line.strip()
                N_CT += 1
                if N_CT % 5000 == 0:
                    add_log(f"\t\tProcessed rejected counterions {N_CT}")
                if (len(line)==0):continue
                tab = line.split(" ")
                if (len(tab)!=2):continue
                ORIG_COUNTER = tab[1]

                if ORIG_COUNTER in MAP_COUNTERIONS:
                    continue

                res = run_query(f"SELECT * FROM {SCHEMA}.sm_counterion WHERE counterion_smiles = '{ORIG_COUNTER}'")
                if res is False:
                    fail_process("FCT_COUNTERION_006", "Unable to query sm_counterion")

                if len(res)>0:
                    MAP_COUNTERIONS[ORIG_COUNTER] = [res[0]['sm_counterion_id'], res[0]['counterion_smiles']]
                    DONE_CT[ORIG_COUNTER] = res[0]['sm_counterion_id']
                else:
                    SM_CT = -1
                    if ORIG_COUNTER in DONE_CT:
                        SM_CT = DONE_CT[ORIG_COUNTER]
                        MAP_COUNTERIONS[ORIG_COUNTER] = [SM_CT, ORIG_COUNTER]
                        continue
                    else:
                        HAS_NEW_COUNTER = True
                        DBIDS['sm_counterion'] += 1
                        SM_CT = DBIDS['sm_counterion']
                        DONE_CT[ORIG_COUNTER] = SM_CT
                        MAP_COUNTERIONS[ORIG_COUNTER] = [SM_CT, ORIG_COUNTER]
                        
                        #run_query_no_res("INSERT INTO sm_counterion VALUES ("+SM_CT+",'"+ORIG_COUNTER+"','F'");
                        FILES['sm_counterion'].write(f"{SM_CT}\t{ORIG_COUNTER}\tF\n")

    FILES['sm_counterion'].close()
    if HAS_NEW_COUNTER:
        
        command = f'\\COPY {SCHEMA}.sm_counterion(sm_counterion_id,counterion_smiles,is_valid) FROM \'' + os.getcwd()+"/INSERT/sm_counterion.csv" + '\'  (DELIMITER E\'\\t\', null \\\"NULL\\\" ,format CSV )'
        return_code = os.system(f"{DB_INFO['COMMAND']} -c \"{command}\"")
        if return_code != 0:
            fail_process("FCT_COUNTERION_007", "Unable to insert sm_counterion")
   # exit(0)
    return MAP_COUNTERIONS


def standardize_compounds(WITH_MOL_ENTITY=False,WITH_PUBLIC_SCHEMA=True):
    global GLB_VAR
    global TG_DIR

    add_log("Standardize Compounds")

    if 'TOOL' not in GLB_VAR:
        fail_process("FCT_STD_CPD_000", 'Unable to Find TOOL')

    if 'FILECONV' not in GLB_VAR['TOOL']:
        fail_process("FCT_STD_CPD_002", 'Unable to Find fileconv executable')
    
    if 'PYTHON' not in GLB_VAR['TOOL']:
        fail_process("FCT_STD_CPD_003", 'Unable to Find Python executable')
    
    if 'PREF_SMI' not in GLB_VAR['TOOL']:
        fail_process("FCT_STD_CPD_004", 'Unable to Find PREF_SMI executable')
    
    if 'FILECONV_PARAM' not in GLB_VAR['TOOL']:
        fail_process("FCT_STD_CPD_005", 'Unable to Find FILECONV_PARAM')
    
    if 'FILECONV_COUNTER_PARAM' not in GLB_VAR['TOOL']:
        fail_process("FCT_STD_CPD_006", 'Unable to Find FILECONV_COUNTER_PARAM')
    
    if 'PREF_SMI_COUNTER' not in GLB_VAR['TOOL']:
        fail_process("FCT_STD_CPD_007", 'Unable to Find PREF_SMI_COUNTER')

    

    FILECONV = GLB_VAR['TOOL']['FILECONV']
    PYTHON = GLB_VAR['TOOL']['PYTHON']
    PREF_SMI = GLB_VAR['TOOL']['PREF_SMI']
    FILECONV_PARAM = GLB_VAR['TOOL']['FILECONV_PARAM']
    FILECONV_COUNTER_PARAM = GLB_VAR['TOOL']['FILECONV_COUNTER_PARAM']
    PREF_SMI_COUNTER = GLB_VAR['TOOL']['PREF_SMI_COUNTER']

    if not os.path.isfile(FILECONV) or not os.access(FILECONV, os.X_OK):
        fail_process("FCT_STD_CPD_008", f'Unable to Find fileconv {FILECONV}')
    if not os.path.isfile(PYTHON) or not os.access(PYTHON, os.X_OK):
        fail_process("FCT_STD_CPD_007", f'Unable to Find Python {PYTHON}')
    if not os.path.isfile(PREF_SMI) or not os.access(PREF_SMI, os.X_OK):
        fail_process("FCT_STD_CPD_009", f'Unable to Find PREF_SMI {PREF_SMI}')

    FILECONV_PARAM = GLB_VAR['TOOL']['FILECONV_PARAM']
    if 'LOG_INSERT' not in os.listdir():
        os.mkdir('LOG_INSERT')
        if 'LOG_INSERT' not in os.listdir():
            fail_process("FCT_STD_CPD_010", 'Unable to create LOG_INSERT')
    if 'INSERT' not in os.listdir():
        os.mkdir('INSERT')
        if 'INSERT' not in os.listdir():
            fail_process("FCT_STD_CPD_011", 'Unable to create INSERT')
    if 'STD' not in os.listdir():
        os.mkdir('STD')
        if 'STD' not in os.listdir():
            fail_process("FCT_STD_CPD_012", 'Unable to create STD')

    add_log("\tConvert counterion")
    # Standardize molecules with a set of params. STD/counterion.smi is input. Valid molecules go in STD/counterion_out.smi.
    # Bad molecules go in STD/counterion_rejected.smi.
    str_cmd = f"{FILECONV} {FILECONV_COUNTER_PARAM} -B log=STD/counterion_rejected.smi " \
              f"-S STD/counterion_out.smi -L STD/counterion_rejected.smi  STD/counterion.smi  &> STD/counterion_log.smi"
    ret=os.system(str_cmd)
    if ret != 0:
        fail_process("FCT_STD_CPD_013", 'Unable to run fileconv')

    # Second step for standardization:
    res = []
    ret=os.system(f"{PREF_SMI} {PREF_SMI_COUNTER} STD/counterion_out.smi > STD/counterion_std.smi")
    if ret != 0:
        fail_process("FCT_STD_CPD_014", 'Unable to run preferred_smiles')
    if not os.path.isfile('STD/counterion_std.smi'):
        fail_process("FCT_STD_CPD_015", 'Unable to find counterion_std.smi')

    add_log("\tStandardization molecule")

    add_log("\t\tExecute fileconv")
    # Step1_molecule_inchi STRUCTURE: 	FULL_SMILES [SPACE] ID | Inchi | InchiKey | Counterion | Molecule_smiles
    # Standardize FULL_SMILES STD/step1_molecule_inchi.smi is input.
    # Valid molecules go in STD/step2_molecule_std.smi. Bad molecules go in STD/step2_molecule_wrong.smi
    ret=os.system(f"{FILECONV} {FILECONV_PARAM}  -B log=STD/step1_molecule_wrong.smi STD/molecule.smi  "
              f"-S STD/step1_molecule_out.smi &> STD/step1_molecule_log.txt")
    if ret != 0:
        fail_process("FCT_STD_CPD_016", 'Unable to run fileconv')

    add_log("\t\tExecute preferred smiles")
    # Second step for standardization:
    ret=os.system(f"{PREF_SMI} STD/step1_molecule_out.smi > STD/step1_molecule_std.smi")
    if ret != 0:
        fail_process("FCT_STD_CPD_017", 'Unable to run preferred_smiles')
    if not os.path.isfile('STD/step1_molecule_std.smi'):
        fail_process("FCT_STD_CPD_018", 'Unable to find step1_molecule_std.smi')

    # Step1_molecule_out STRUCTURE: 	FULL_SMILES(S) [SPACE] ID | Inchi | InchiKey | Counterion | Molecule_smiles
    add_log("\t\tSwitching full molecule smiles with molecule smiles")
    # Step 2 merges step1 files and switches FULL_SMILES(s) with molecule_smiles.
    with open('STD/step2_molecule_ini.smi', 'w') as fpO:
        with open('STD/step1_molecule_std.smi', 'r') as fp:
            for line in fp:
                tab = line.strip().split(" ")
                if (len(tab)!=2):continue
                tab2 = tab[1].split("|")
                FULL_SMILES = tab[0]
                NAME = tab2[0]
                INCHI = tab2[1]
                INCHI_KEY = tab2[2]
                COUNTERION = tab2[3]
                MOLECULE = tab2[4]
                fpO.write(f"{MOLECULE} {NAME}|{INCHI}|{INCHI_KEY}|{COUNTERION}|{FULL_SMILES}|T\n")

        with open('STD/step1_molecule_wrong.smi', 'r') as fp:
            for line in fp:
                tab = line.strip().split(" ")
                if (len(tab)!=2):continue
                tab2 = tab[1].split("|")
                FULL_SMILES = tab[0]
                NAME = tab2[0]
                INCHI = tab2[1]
                INCHI_KEY = tab2[2]
                COUNTERION = tab2[3]
                MOLECULE = tab2[4]
                fpO.write(f"{MOLECULE} {NAME}|{INCHI}|{INCHI_KEY}|{COUNTERION}|{FULL_SMILES}|F\n")

    add_log("\t\tGenerate Inchi")
    # Molecule.smi STRUCTURE: 	Molecule_smiles [SPACE] ID | Inchi | InchiKey | Counterion | FULL_SMILES(s) | FULL_SMILES_STD_SUCCESS
    str_cmd = f"{PYTHON} {TG_DIR}/BACKEND/SCRIPT/LIB_PYTHON/rdkit_utils.py smiles_to_inchi_file " \
              f"STD/step2_molecule_ini.smi STD/step3_molecule_inchi.smi &> STD/step3_molecule_inchi.log"
    ret=os.system(str_cmd)
    if ret != 0:
        fail_process("FCT_STD_CPD_019", 'Unable to run smiles_to_inchi_file')
    if not os.path.isfile('STD/step3_molecule_inchi.smi'):
        fail_process("FCT_STD_CPD_020", 'Unable to find step3_molecule_inchi.smi')

    #Molecule.smi STRUCTURE: 	Molecule_smiles [SPACE] ID | Inchi(s) | InchiKey(s) | Counterion | FULL_SMILES(s) | FULL_SMILES_STD_SUCCESS

	
	# Now we can standardize our MOLECULE_SMILES
    add_log("\t\tExecute fileconv")
    # Standardize MOLECULE_SMILES STD/step3_molecule_ini.smi is input. 
    # Valid molecules goes in STD/step4_molecule_std.smi. Bad molecules goes in STD/step4_molecule_wrong.smi
    ret=os.system(f"{FILECONV} {FILECONV_PARAM} -B log=STD/step4_molecule_wrong.smi STD/step3_molecule_inchi.smi "
              f"-S STD/step4_molecule_out.smi &> STD/step4_molecule_log.txt")
    if ret != 0:
        fail_process("FCT_STD_CPD_021", 'Unable to run fileconv')
    
    # Step4_molecule_out STRUCTURE: 	Molecule_smiles(s) [SPACE] ID | Inchi(s) | InchiKey(s) | Counterion | FULL_SMILES(s) | FULL_SMILES_STD_SUCCESS
    # Second step for standardization:
    add_log("\t\tExecute preferred smiles")
    ret=os.system(f"{PREF_SMI} STD/step4_molecule_out.smi > STD/step4_molecule_std.smi")
    if ret != 0:
        fail_process("FCT_STD_CPD_022", 'Unable to run preferred_smiles')
    if not os.path.isfile('STD/step4_molecule_std.smi'):
        fail_process("FCT_STD_CPD_023", 'Unable to find step4_molecule_std.smi')

    if (WITH_PUBLIC_SCHEMA==True):
        DBIDS = {}
        FILES = {}
        STATS = {}
        MAP_COUNTERIONS = process_counterion(GLB_VAR['PUBLIC_SCHEMA'], 'STD/counterion_std.smi', 'STD/counterion_rejected.smi')
        process_compounds(GLB_VAR['PUBLIC_SCHEMA'], MAP_COUNTERIONS, WITH_MOL_ENTITY)

    W_PRIVATE = (GLB_VAR['PRIVATE_ENABLED'] == 'T')
    if W_PRIVATE:
        DBIDS = {}
        FILES = {}
        STATS = {}
        MAP_COUNTERIONS = process_counterion(GLB_VAR['SCHEMA_PRIVATE'], 'STD/counterion_std.smi',
                                             'STD/counterion_rejected.smi')
        process_compounds(GLB_VAR['SCHEMA_PRIVATE'], MAP_COUNTERIONS, WITH_MOL_ENTITY)


def process_compounds(SCHEMA, MAP_COUNTERIONS, WITH_MOL_ENTITY=False):
    global SOURCE_ID
    global DBIDS
    global FILES
    global STATS
    global JOB_ID

    add_log("\t\tProcessing compounds to database")

    FILES = {
        'sm_molecule': open('INSERT/sm_molecule.csv', 'w'),
        'sm_entry': open('INSERT/sm_entry.csv', 'w'),
        'sm_source': open('INSERT/sm_source.csv', 'w')
    }

    for name, file in FILES.items():
        if not file:
            fail_process("FCT_PROCESS_CPD_001", f'Unable to open {name}.csv')

    DBIDS = {}

    for table in FILES.keys():
        res = run_query(f"SELECT MAX({table}_id) CO FROM {SCHEMA}.{table}")
        if res is False:
            fail_process("FCT_PROCESS_CPD_002", f'Unable to get max id from {table}')
        DBIDS[table] = 0 if not res else res[0]['co']

    RECORD ={}

    STATS = {
        'INI_CPD': 0,
        'NEW_NAME': 0,
        'VALID_NAME': 0,
        'NEW_SMILES': 0,
        'NEW_COUNTERION': 0,
        'NEW_ENTRY': 0
    }

    fp = open('STD/step4_molecule_std.smi', 'r')
    if not fp:
        fail_process("FCT_PROCESS_CPD_003", 'Unable to open molecule_std.smi')



    # STRUCTURE: 	Molecule_smiles(s) [SPACE] ID | Inchi(s) | InchiKey(s) | Counterion | FULL_SMILES(s) | FULL_SMILES_STD_SUCCESS
    while True:
        line = fp.readline()
        if not line:
            break
        if (len(line)==0):continue
        tab = line.split(" ")
        tab2 = tab[1].split("|")
        SMI = tab[0]
        NAME = tab2[0]
        INCHI = tab2[1]
        INCHI_KEY = tab2[2]
        COUNTERION = '' if tab2[3] == 'NULL' else tab2[3]
        COUNTERION_ID = 'NULL' if tab2[3] == 'NULL' else MAP_COUNTERIONS[COUNTERION][0]
        COUNTERION_STD = '' if tab2[3] == 'NULL' else MAP_COUNTERIONS[COUNTERION][1]
        FULL_SMILES = tab2[4]
        FULL_VALID = True if tab2[5] == 'T' else False
        
        MD5 = hashlib.md5(f"{INCHI}_{INCHI_KEY}_{SMI}_{COUNTERION_STD}".encode()).hexdigest()

        str_hash = f"'{MD5}'"

        if str_hash not in RECORD:
            RECORD[str_hash] = {
                'SMILES': SMI,
                'INCHI': INCHI,
                'KEY': INCHI_KEY,
                'COUNTERION': COUNTERION_STD,
                'COUNTERION_ID': COUNTERION_ID,
                'NAME': {NAME: -1},
                'DBID': -1,
                'FULL_SMILES': FULL_SMILES,
                'FULL_VALID': FULL_VALID,
                'VALID': False
            }
        else:
            RECORD[str_hash]['NAME'][NAME] = -1
        
        
        STATS['INI_CPD'] += 1

        if STATS['INI_CPD'] % 5000 == 0:
            add_log(f"\t\t\tProcessed {STATS['INI_CPD']} compounds\n")

        if len(RECORD) < 5000:
            continue
        
        process_compound_record(RECORD,  SCHEMA, WITH_MOL_ENTITY)

        RECORD = {}

        add_log(f"\t\t\tFILE POS:{fp.tell()}")

    fp.close()
    
    if len(RECORD)>0:
        process_compound_record(RECORD,  SCHEMA, WITH_MOL_ENTITY)
    add_log("\t\t\tEnd Processing file   ")
    
    RECORD = {}

    if os.path.isfile('STD/step4_molecule_wrong.smi'):
        add_log("\t\tProcess Rejected records")
        fp = open('STD/step4_molecule_wrong.smi', 'r')
        if not fp:
            fail_process("FCT_PROCESS_CPD_004", 'Unable to open molecule_wrong.smi')

        while True:
            line = fp.readline()
            if not line:
                break

            tab = line.split(" ")
            tab2 = tab[1].split("|")
            SMI = tab[0]
            NAME = tab2[0]
            INCHI = tab2[1]
            INCHI_KEY = tab2[2]
            COUNTERION = '' if tab2[3] == 'NULL' else tab2[3]
            COUNTERION_ID = 'NULL' if tab2[3] == 'NULL' else MAP_COUNTERIONS[COUNTERION][0]
            COUNTERION_STD = '' if tab2[3] == 'NULL' else MAP_COUNTERIONS[COUNTERION][1]
            FULL_SMILES = tab2[4]
            FULL_VALID = True if tab2[5] == 'T' else False

            str_hash = f"'{hashlib.md5(f'{INCHI}_{INCHI_KEY}_{SMI}_{COUNTERION_STD}'.encode()).hexdigest()}'"

            if str_hash not in RECORD:
                RECORD[str_hash] = {
                    'SMILES': SMI,
                    'INCHI': INCHI,
                    'KEY': INCHI_KEY,
                    'COUNTERION': COUNTERION_STD,
                    'COUNTERION_ID': COUNTERION_ID,
                    'NAME': {NAME: -1},
                    'DBID': -1,
                    'FULL_SMILES': FULL_SMILES,
                    'FULL_VALID': FULL_VALID,
                    'VALID': False
                }
            else:
                RECORD[str_hash]['NAME'][NAME] = -1

            STATS['INI_CPD'] += 1

            if STATS['INI_CPD'] % 5000 == 0:
                add_log(f"\t\t\t{STATS['INI_CPD']}")

            if len(RECORD) < 50000:
                continue

            process_compound_record(RECORD,  SCHEMA, WITH_MOL_ENTITY)
            RECORD = {}
            add_log(f"\t\t\tFILE POS:{fp.tell()}")

        fp.close()
        add_log("\t\t\tEnd file Processing file")
        process_compound_record(RECORD,  SCHEMA, WITH_MOL_ENTITY)



def process_compound_record(RECORD,  SCHEMA, WITH_MOL_ENTITY=False):
    global STATS
    global SOURCE_ID
    global JOB_ID
    global DB_INFO
    global GLB_VAR
    
    HAS_NEW_ENTRY = False
    HAS_NEW_COUNTER = False
    HAS_NEW_MOLECULE = False
    HAS_NEW_SOURCE = False
    DEBUG = False
    
    if not RECORD:
        return

    DBIDS = {
        'sm_molecule': -1,
        'sm_entry': -1,
        'sm_source': -1
    }
    if (SOURCE_ID==-1): fail_process(f"{JOB_ID}000",f'No source provided')

    # Initialize FILE_STATUS and FILES dictionaries
    FILE_STATUS = {}
    FILES = {}
    for TBL in DBIDS:
        query = f"SELECT MAX({TBL}_id) CO FROM {SCHEMA}.{TBL}"
        res = run_query(query)
        if res is False:
            fail_process(f"{JOB_ID}010", f"Unable to run query {query}")
        if (res[0]['co']==None):DBIDS[TBL]=1
        else: DBIDS[TBL] = res[0]['co']

        FILE_STATUS[TBL] = 0
        FILES[TBL] = open(f'INSERT/{TBL}.csv', 'w')
        if not FILES[TBL]:
            fail_process(f"{JOB_ID}005", f"Unable to open file {TBL}.csv")

    MOL_ENTITIES = []

    add_log("\t\t\tProcessing batch to db")

    # Searching all hash
    query = f"""
        SELECT md5_hash, se.sm_entry_id, sm_name, smiles, sm_source_id, inchi, inchi_key,
               source_id, sm.sm_molecule_id, se.sm_counterion_id
        FROM {SCHEMA}.sm_molecule SM
        LEFT JOIN {SCHEMA}.sm_entry SE ON SM.sm_molecule_id = SE.sm_molecule_id
        LEFT JOIN {SCHEMA}.sm_source SS ON SE.sm_entry_id = SS.sm_entry_id
        WHERE md5_hash IN ({','.join(map(lambda x: f"{x}", RECORD.keys()))})
    """

   
    VALID_SOURCE_ID = []

    res = run_query(query)
    if res is False:
        fail_process("FCT_CPD_RECORD_001", "Unable to retrieve records from hash")

    if DEBUG:
        print("MD5 Search results:")
        print(res)

    for line in res:
        DB_MD5 =  line['md5_hash']
        
        if "'"+DB_MD5+"'" not in RECORD:
            continue
        
        ENTRY = RECORD["'"+DB_MD5+"'"]

        if DEBUG:
            print(f"{DB_MD5}\t{line['sm_name']}\t{SOURCE_ID}::{line['source_id']}")

        if line['sm_counterion_id'] == '':
            line['sm_counterion_id'] = None
        if (ENTRY['KEY']=='NULL'):ENTRY['KEY']=None
        if (ENTRY['COUNTERION_ID']=='NULL'):ENTRY['COUNTERION_ID']=None
        if (line['smiles'].replace("'","") != ENTRY['SMILES']): fail_process("FCT_CPD_RECORD_002", f"Integrity compromised - different smiles. Input: |{ENTRY['SMILES']}|; DB: |{line['smiles']}|; HASH: {DB_MD5}")
        if (line['inchi'] != ENTRY['INCHI']) : fail_process("FCT_CPD_RECORD_003", f"Integrity compromised - different inchi. Input: {ENTRY['INCHI']}; DB: {line['inchi']}; HASH: {DB_MD5}")
        if (line['inchi_key'] != ENTRY['KEY']): fail_process("FCT_CPD_RECORD_004", f"Integrity compromised - different inchi key. Input: {ENTRY['KEY']}; DB: {line['inchi_key']}; HASH: {DB_MD5}")
        if (line['sm_counterion_id'] != ENTRY['COUNTERION_ID']): fail_process("FCT_CPD_RECORD_005", f"Integrity compromised - different counterion. Input: {ENTRY['COUNTERION_ID']}; DB: {line['sm_counterion_id']}; HASH: {DB_MD5}")
        
        if (ENTRY['KEY']==None):ENTRY['KEY']='NULL'
        if (ENTRY['COUNTERION_ID']==None):ENTRY['COUNTERION_ID']='NULL'
        ENTRY['DBID'] = line['sm_entry_id']

        TO_DEL_NAME=[]
        for NAME, ID in ENTRY['NAME'].items():
            if NAME == line['sm_name'] and SOURCE_ID == line['source_id']:
                ID = line['sm_source_id']
                VALID_SOURCE_ID.append(line['sm_source_id'])
                STATS['VALID_NAME'] += 1
                TO_DEL_NAME.append(NAME)
        
        if (len(TO_DEL_NAME)>0):
            for NAME in TO_DEL_NAME:
                del ENTRY['NAME'][NAME]

        if not ENTRY['NAME']:
            if WITH_MOL_ENTITY:
                MOL_ENTITY = {
                    'STRUCTURE_HASH': DB_MD5,
                    'STRUCTURE': ENTRY['FULL_SMILES'],
                    'DB_ID': -1,
                    'COMPONENT': [
                        {
                            'STRUCTURE_HASH': DB_MD5,
                            'STRUCTURE': ENTRY['FULL_SMILES'],
                            'MOLAR_FRACTION': 1,
                            'DB_ID': -1,
                            'MOLECULE': [
                                {
                                    'MOL_TYPE': 'SM',
                                    'ID': ENTRY['DBID'],
                                    'HASH': DB_MD5,
                                    'TYPE': 'SIN',
                                    'MOLAR_FRACTION': 1,
                                    'DB_ID': -1
                                }
                            ]
                        }
                    ]
                }
                MOL_ENTITIES.append(MOL_ENTITY)
            del RECORD["'"+DB_MD5+"'"]

    if DEBUG:
        print("RECORD AFTER PROCESSING:")
        print(RECORD)
    
    if VALID_SOURCE_ID and not run_query_no_res(f"UPDATE {SCHEMA}.SM_SOURCE SET sm_name_status='T' WHERE sm_source_id IN ({','.join(map(str, VALID_SOURCE_ID))})"):
        fail_process("FCT_CPD_RECORD_006", "Unable to update sm_source")

    # We validated all the sm source existing. Now we need to process those missing
    # Starting at the basic level, checking if the SMILES is already in the database
    # and whether the counterion in also in the database
    MISSING_SMILES = {}

    for MD5_HASH, ENTRY in RECORD.items():

        if ENTRY['DBID'] == -1:
            MISSING_SMILES["'"+ENTRY['SMILES']+"'"] = MISSING_SMILES.get("'"+ENTRY['SMILES']+"'", []) + [MD5_HASH]
        if ENTRY['COUNTERION_ID'] == '':
            ENTRY['COUNTERION_ID'] = 'NULL'
    
    # Check if there are missing smiles
    if MISSING_SMILES:
        # Get SMILES and SM_MOLECULE_ID from the database
        smiles_list = ",".join([f"{smiles}" for smiles in MISSING_SMILES.keys()])
        res = run_query(f"SELECT SMILES, SM_MOLECULE_ID FROM {SCHEMA}.SM_MOLECULE WHERE SMILES IN ({smiles_list})")

        if res is False:
            fail_process("FCT_CPD_RECORD_007", "Unable to get SMILES")

        for line in res:
            for MD5_HASH in MISSING_SMILES.get("'"+line['smiles']+"'", []):
                ENTRY = RECORD[MD5_HASH]
                ENTRY['SM_MOLECULE_ID'] = line['sm_molecule_id']
            MISSING_SMILES.pop("'"+line['smiles']+"'", None)

        # Now insert new smiles
        for SMI, LIST_RECORD in MISSING_SMILES.items():
            IS_VALID = all(RECORD[MD5_HASH]['VALID'] for MD5_HASH in LIST_RECORD)
            DBIDS['sm_molecule'] += 1
            STATS['NEW_SMILES'] += 1
            HAS_NEW_MOLECULE = True
            
            FILES['sm_molecule'].write(f"{DBIDS['sm_molecule']}\t{SMI}\t{'T' if IS_VALID else 'F'}\n")

            for MD5_HASH in LIST_RECORD:
                ENTRY = RECORD[MD5_HASH]
                ENTRY['SM_MOLECULE_ID'] = DBIDS['sm_molecule']

   # For remaining records that need to be inserted, check if the name already exists
    NEW_COMPOUND_NAMES = {}

    for MD5_HASH, ENTRY in RECORD.items():
        for NAME, ID in ENTRY['NAME'].items():
            NEW_COMPOUND_NAMES[NAME] = NEW_COMPOUND_NAMES.get("'"+NAME+"'", []) + [MD5_HASH]

    if NEW_COMPOUND_NAMES:
        # Query to check if names already exist
        query = f"""
            SELECT md5_hash, se.sm_entry_id, sm_name, smiles, sm_source_id, inchi, inchi_key,
                source_id, sm.sm_molecule_id, se.sm_counterion_id
            FROM {SCHEMA}.sm_molecule SM
            LEFT JOIN {SCHEMA}.sm_entry SE ON SM.sm_molecule_id = SE.sm_molecule_id
            LEFT JOIN {SCHEMA}.sm_source SS ON SE.sm_entry_id = SS.sm_entry_id
            WHERE SM_NAME IN ('{"','".join( NEW_COMPOUND_NAMES.keys())}')
            AND source_id = {SOURCE_ID}
        """

        res = run_query(query)
        if res is False:
            fail_process("FCT_CPD_RECORD_008", "Unable to get names")

        # Prepare and execute DELETE query for existing names
        delete_query = 'DELETE FROM sm_source WHERE sm_source_id IN ('
        for line in res:
            SM_NAME = line['sm_name']
            print("####")
            print(f"DBV\t{line['sm_name']}\t{line['smiles']}\t{line['inchi']}\t{line['inchi_key']}\t{line['sm_counterion_id']}\t{line['md5_hash']}")
            for MD5_HASH in NEW_COMPOUND_NAMES.get("'"+SM_NAME+"'", []):
                ENTRY = RECORD[MD5_HASH]
                print(f"ENT\tN/A\t{ENTRY['SMILES']}\t{ENTRY['INCHI']}\t{ENTRY['KEY']}\t{ENTRY['COUNTERION_ID']}\t{MD5_HASH}")
            delete_query += f"{line['sm_source_id']},"

        if res:
            print(delete_query)
            if not run_query_no_res(delete_query[:-1] + ')'):
                fail_process("FCT_CPD_RECORD_009", "Unable to delete sm_source records")


    # /// Then for those new records, we need to check if the pair sm_molecule_Id, sm_counterion_id is already in the system or not.
    # /// If we find a match, then the difference can be in the Inchi and Inchi-Key.
    # /// But, because the md5_hash is a combination of molecule,counterion,inchi and inchi-key, there shouldn't be a match
    # /// However, it can help us in debugging

    NEW_COMPOUND_IDS = {}
    NEW_COMPOUND_IDS_NOCO = {}

    for MD5_HASH, ENTRY in RECORD.items():
        if ENTRY['DBID'] != -1:
            continue
        
        if ENTRY['COUNTERION_ID'] == 'NULL':
            NEW_COMPOUND_IDS_NOCO[ENTRY['SM_MOLECULE_ID']] = NEW_COMPOUND_IDS_NOCO.get(ENTRY['SM_MOLECULE_ID'], []) + [MD5_HASH]
        else:
            NEW_COMPOUND_IDS[f"({ENTRY['SM_MOLECULE_ID']},{ENTRY['COUNTERION_ID']})"] = NEW_COMPOUND_IDS.get(f"({ENTRY['SM_MOLECULE_ID']},{ENTRY['COUNTERION_ID']})", []) + [MD5_HASH]

    # Construct query to check if new compound IDs already exist
    HAS_RULE=False
    query = f"""
        SELECT SM_MOLECULE_ID, SM_COUNTERION_ID, SM_ENTRY_ID, INCHI, INCHI_KEY
        FROM {SCHEMA}.SM_ENTRY
        WHERE
        """
    if (len(NEW_COMPOUND_IDS)>0):
        HAS_RULE=True
        query += f"""
         {'( (SM_MOLECULE_ID,SM_COUNTERION_ID) IN (' + ','.join(NEW_COMPOUND_IDS.keys()) + ')) ' if NEW_COMPOUND_IDS else ''}
        """
    if (len(NEW_COMPOUND_IDS_NOCO)>0):
        if (HAS_RULE): query+=" OR "
        query+=f"""
            {'  ((SM_MOLECULE_ID) IN (' + ','.join(map(str, NEW_COMPOUND_IDS_NOCO.keys())) + ') AND SM_COUNTERION_ID IS NULL) ' if NEW_COMPOUND_IDS_NOCO else ''}
    """

    if NEW_COMPOUND_IDS or NEW_COMPOUND_IDS_NOCO:
        
        res = run_query(query)
        if res is False:
            fail_process("FCT_CPD_RECORD_010", "Unable to get sm_entries")

        for line in res:
            if NEW_COMPOUND_IDS.get(f"({line['sm_molecule_id']},{line['sm_counterion_id']})"):
                for MD5_HASH in NEW_COMPOUND_IDS[f"({line['sm_molecule_id']},{line['sm_counterion_id']})"]:
                    ENTRY = RECORD[MD5_HASH]
                    if ENTRY['INCHI'] == line['inchi'] and ENTRY['KEY'] == line['inchi_key']:
                        ENTRY['DBID'] = line['sm_entry_id']

            if NEW_COMPOUND_IDS_NOCO.get(line['sm_molecule_id']):
                for MD5_HASH in NEW_COMPOUND_IDS_NOCO[line['sm_molecule_id']]:
                    ENTRY = RECORD[MD5_HASH]
                    if ENTRY['INCHI'] == line['inchi'] and ENTRY['KEY'] == line['inchi_key']:
                        ENTRY['DBID'] = line['sm_entry_id']


    for MD5_HASH, ENTRY in RECORD.items():
        if ENTRY['DBID'] == -1:
            DBIDS['sm_entry'] += 1
            ENTRY['DBID'] = DBIDS['sm_entry']
            STATS['NEW_ENTRY'] += 1
            HAS_NEW_ENTRY = True

            if DEBUG:
                print(f"\tCREATE ENTRY\t{DBIDS['sm_entry']}")
            FV='F'
            if (ENTRY['FULL_VALID']==True):FV='T'
            FILES['sm_entry'].write(f"{DBIDS['sm_entry']}\t{ENTRY['INCHI']}\t{ENTRY['KEY']}\t{ENTRY['COUNTERION_ID']}\t{ENTRY['SM_MOLECULE_ID']}\t{MD5_HASH[1:-1]}\t{FV}\t{ENTRY['FULL_SMILES']}\n")

            for NAME, ID in ENTRY['NAME'].items():
                STATS['NEW_NAME'] += 1
                DBIDS['sm_source'] += 1
                ID = DBIDS['sm_source']
                HAS_NEW_SOURCE = True

                if DEBUG:
                    print(f"\tNEW_NAME\t{DBIDS['sm_source']}\t|\t{SOURCE_ID}\t{NAME}\tT")
                FILES['sm_source'].write(f"{DBIDS['sm_source']}\t{ENTRY['DBID']}\t{SOURCE_ID}\t{NAME}\tT\n")
        else:
            for NAME, ID in ENTRY['NAME'].items():
                if ID != -1:
                    continue
                STATS['NEW_NAME'] += 1
                DBIDS['sm_source'] += 1
                ID = DBIDS['sm_source']
                HAS_NEW_SOURCE = True

                if DEBUG:
                    print(f"\tNEW_NAME\t{DBIDS['sm_source']}\t|\t{SOURCE_ID}\t{NAME}\tT")
                FILES['sm_source'].write(f"{DBIDS['sm_source']}\t{ENTRY['DBID']}\t{SOURCE_ID}\t{NAME}\tT\n")

        if not WITH_MOL_ENTITY:
            continue
        
        MOL_ENTITY = {
            'STRUCTURE_HASH': MD5_HASH.replace("'", ""),
            'STRUCTURE': ENTRY['FULL_SMILES'],
            'DB_ID': -1,
            'COMPONENT': [
                {
                    'STRUCTURE_HASH': MD5_HASH.replace("'", ""),
                    'STRUCTURE': ENTRY['FULL_SMILES'],
                    'MOLAR_FRACTION': 1,
                    'DB_ID': -1,
                    'MOLECULE': [
                        {
                            'MOL_TYPE': 'SM',
                            'ID': ENTRY['DBID'],
                            'HASH': MD5_HASH.replace("'", ""),
                            'TYPE': 'SIN',
                            'MOLAR_FRACTION': 1,
                            'DB_ID': -1
                        }
                    ]
                }
            ]
        }
        
        MOL_ENTITIES.append(MOL_ENTITY)

    # Close files
    FILES['sm_source'].close()
    FILES['sm_molecule'].close()
    FILES['sm_entry'].close()

    # Run PostgreSQL COPY commands
    if HAS_NEW_MOLECULE:
        command = f'\\COPY {SCHEMA}.sm_molecule(sm_molecule_id,smiles,is_valid) FROM \'' + "INSERT/sm_molecule.csv" + '\'  (DELIMITER E\'\\t\', null \\\"NULL\\\" ,format CSV )'
        print(f"{DB_INFO['COMMAND']} -c \"{command}\"\n")
        return_code = os.system(f"{DB_INFO['COMMAND']} -c \"{command}\"")
        if return_code != 0:
            fail_process("FCT_CPD_RECORD_011", 'Unable to insert sm_molecule')

    if HAS_NEW_ENTRY:
        command = f'\\COPY {SCHEMA}.sm_entry(sm_entry_id,inchi,inchi_key,sm_counterion_id,sm_molecule_id,md5_hash,is_valid,full_smiles) FROM \'' + "INSERT/sm_entry.csv" + '\'  (DELIMITER E\'\\t\', null \\\"NULL\\\" ,format CSV )'
        print(f"{DB_INFO['COMMAND']} -c \"{command}\"\n")
        return_code = os.system(f"{DB_INFO['COMMAND']} -c \"{command}\"")
        if return_code != 0:
            fail_process("FCT_CPD_RECORD_012", 'Unable to insert sm_entry')

    if HAS_NEW_SOURCE:
        command = f'\\COPY {SCHEMA}.sm_source(sm_source_id,sm_entry_id,source_id,sm_name,sm_name_status) FROM \'' + "INSERT/sm_source.csv" + '\'  (DELIMITER E\'\\t\', null \\\"NULL\\\" ,format CSV )'
        print(f"{DB_INFO['COMMAND']} -c \"{command}\"\n")
        return_code = os.system(f"{DB_INFO['COMMAND']} -c \"{command}\"")
        if return_code != 0:
            fail_process("FCT_CPD_RECORD_013", 'Unable to insert sm_source')

    # Run additional processing for WITH_MOL_ENTITY
    if WITH_MOL_ENTITY:
        create_mol_entities(MOL_ENTITIES, SCHEMA)

    # Reopen files
    FILES['sm_source'] = open('INSERT/sm_source.csv', 'w')
    if not FILES['sm_source']:
        fail_process("FCT_CPD_RECORD_014", 'Unable to open sm_source')

    FILES['sm_molecule'] = open('INSERT/sm_molecule.csv', 'w')
    if not FILES['sm_molecule']:
        fail_process("FCT_CPD_RECORD_015", 'Unable to open sm_molecule')

    FILES['sm_entry'] = open('INSERT/sm_entry.csv', 'w')
    if not FILES['sm_entry']:
        fail_process("FCT_CPD_RECORD_016", 'Unable to open sm_entry')


def chunk_list(lst, chunk_size):
    for i in range(0, len(lst), chunk_size):
        yield lst[i:i + chunk_size]



def get_source(source_name,is_primary=False):
    global SOURCE_LIST
    global SOURCE_ID
    source_name_lower = source_name.lower()
    if source_name_lower in SOURCE_LIST:
        return SOURCE_LIST[source_name_lower]

    res = run_query(f"SELECT source_id FROM source WHERE LOWER(source_name) = LOWER('{source_name.replace("'", "''")}')")
    if res is False:
        fail_process("FCT_GET_SOURCE_001", f"Failed to fetch source {source_name}")

    source_id = -1
    if not res:
        res = run_query("SELECT nextval('source_seq') v")
        if res is False:
            fail_process("FCT_GET_SOURCE_002", "Unable to get Max source")
        max_dbid = res[0]['v']

        query = f"INSERT INTO source (source_Id, source_name, version, user_name) VALUES ({max_dbid}, " \
                f"'{source_name.replace("'", "''")}', NULL, NULL)"
        if not run_query_no_res(query):
            fail_process("FCT_GET_SOURCE_003", f"Unable to create {source_name} source")
        source_id = max_dbid
    else:
        source_id = res[0]['source_id']

    SOURCE_LIST[source_name_lower] = source_id
    if (is_primary): SOURCE_ID=source_id
    return source_id









def create_mol_entities(MOL_ENTITIES, SCHEMA):
    if not MOL_ENTITIES:
        return

    global GLB_VAR
    global DB_INFO
    global JOB_ID
    
    global STATS
    global SOURCE_ID

    DBIDS = {
        'molecular_entity': -1,
        'molecular_component': -1,
        'molecular_component_sm_map': -1,
		'molecular_component_na_map':-1,
		'molecular_component_conj_map':-1,
        'molecular_entity_component_map': -1
    }

    # This will contain the order of the columns for the COPY command
    COL_ORDER = {
        'molecular_entity': '(molecular_entity_id,molecular_entity_hash,molecular_structure_hash,molecular_components,molecular_structure)',
        'molecular_component': '(molecular_component_id,molecular_component_hash,molecular_component_structure_hash,molecular_component_structure,components,ontology_entry_id)',
        'molecular_component_sm_map': '(molecular_component_sm_map_id,molecular_component_id,sm_entry_id,molar_fraction,compound_type)',
        'molecular_component_na_map': '(molecular_component_na_map_id,molecular_component_id,nucleic_acid_seq_id,molar_fraction)',
		'molecular_component_conj_map': '(molecular_component_conj_map_id,molecular_component_id,conjugate_entry_id,molar_fraction)',
		'molecular_entity_component_map': '(molecular_entity_component_map_id,molecular_entity_id,molecular_component_id,molar_fraction)'
    }
    
    # So first, we are going to get the max Primary key values for each of those tables for faster insert.
    # FILE_STATUS will tell us for each file if we need to trigger the data insertion or not
    FILE_STATUS = {}

    # FILES will be the file handlers for each of the files we are going to insert into
    FILES = {}

    for TBL, POS in DBIDS.items():
        query = f'SELECT MAX({TBL}_id) CO FROM {SCHEMA}.{TBL}'
        res = run_query(query)
        if res is False:
            fail_process(f'{JOB_ID}010', f'Unable to run query {query}')
        if (res[0]['co']==None):DBIDS[TBL]=1
        else: DBIDS[TBL] = res[0]['co']
        FILE_STATUS[TBL] = 0
        FILES[TBL] = open(f'INSERT/{TBL}.csv', 'w')
        if not FILES[TBL]:
            fail_process(f'{JOB_ID}005', f'Unable to open file {TBL}.csv')



    # STEP1 => Generating the hash for each of the molecular entity
    for ENTITY_POS, MOL_ENTITY in enumerate(MOL_ENTITIES):
        STR_ENTITY = ''
        for COMPONENT_POS, MOL_COMPONENT in enumerate(MOL_ENTITY['COMPONENT']):
            STR_COMPONENT = ''
            MOL_COMPONENT['MAP_ID'] = -1
            for MOL_POS, MOLECULE in enumerate(MOL_COMPONENT['MOLECULE']):
                STR_COMPONENT += f'{MOLECULE["HASH"]}:{MOLECULE["MOLAR_FRACTION"]}|'

            MOL_COMPONENT['HASH'] = hashlib.md5(STR_COMPONENT[:-1].encode()).hexdigest()
            STR_ENTITY += f'{MOL_COMPONENT["HASH"]}:{MOL_COMPONENT["MOLAR_FRACTION"]}|'

        MOL_ENTITY['HASH'] = hashlib.md5(STR_ENTITY[:-1].encode()).hexdigest()

    MOL_ENTITIES_MAP = {}
    MOL_COMPONENTS_MAP = {}

    for ENTITY_POS, MOL_ENTITY in enumerate(MOL_ENTITIES):
        MOL_ENTITIES_MAP[MOL_ENTITY['HASH']] = MOL_ENTITIES_MAP.get(MOL_ENTITY['HASH'], []) + [ENTITY_POS]
        for COMPONENT_POS, MOL_COMPONENT in enumerate(MOL_ENTITY['COMPONENT']):
            MOL_COMPONENTS_MAP[MOL_COMPONENT['HASH']] = MOL_COMPONENTS_MAP.get(MOL_COMPONENT['HASH'], []) + [(ENTITY_POS, COMPONENT_POS)]

    if MOL_ENTITIES_MAP:
        query=f"SELECT molecular_entity_id, molecular_entity_hash FROM {SCHEMA}.molecular_entity WHERE molecular_entity_hash IN ("
        for MOL_ENTITY_HASH in MOL_ENTITIES_MAP:
            query+="'"+MOL_ENTITY_HASH+"',"
        res = run_query(query[0:-1]+')')
        if res is False:
            fail_process("FCT_CREATE_MOL_ENT_001", "Unable to get molecular_entity")
        for line in res:
            for ENTITY_POS in MOL_ENTITIES_MAP.get(line['molecular_entity_hash'], []):
                MOL_ENTITY = MOL_ENTITIES[ENTITY_POS]
                MOL_ENTITY['DB_ID'] = line['molecular_entity_id']
    
    if MOL_COMPONENTS_MAP:
        query=f"SELECT molecular_component_id, molecular_component_hash FROM {SCHEMA}.molecular_component WHERE molecular_component_hash IN ("
        for MOL_COMPONENT_HASH in MOL_COMPONENTS_MAP:
            query+="'"+MOL_COMPONENT_HASH+"',"

        res = run_query(query[0:-1]+')')
        if res is False:
            fail_process("FCT_CREATE_MOL_ENT_002", "Unable to get mol_component")
        for line in res:
            for COMPONENT_POS_INFO in MOL_COMPONENTS_MAP.get(line['molecular_component_hash'], []):
                ENTITY_POS, COMPONENT_POS = COMPONENT_POS_INFO
                MOL_ENTITY = MOL_ENTITIES[ENTITY_POS]
                MOL_COMPONENT = MOL_ENTITY['COMPONENT'][COMPONENT_POS]
                MOL_COMPONENT['DB_ID'] = line['molecular_component_id']
    
    
    SM_MAP = {}
    CO_MAP = {}
    NA_MAP = {}
    

    for ENTITY_POS, MOL_ENTITY in enumerate(MOL_ENTITIES):
        for COMPONENT_POS, MOL_COMPONENT in enumerate(MOL_ENTITY['COMPONENT']):
            if MOL_COMPONENT['DB_ID'] == -1:
                continue
            if MOL_ENTITY['DB_ID'] != -1:
                CO_MAP[(MOL_ENTITY['DB_ID'], MOL_COMPONENT['DB_ID'])] = CO_MAP.get((MOL_ENTITY['DB_ID'], MOL_COMPONENT['DB_ID']), []) + [(ENTITY_POS, COMPONENT_POS)]
            for MOL_POS, MOLECULE_ENTRY in enumerate(MOL_COMPONENT['MOLECULE']):
                if MOLECULE_ENTRY['MOL_TYPE'] == 'SM':
                    SM_MAP[(MOL_COMPONENT['DB_ID'], MOLECULE_ENTRY['ID'])] = SM_MAP.get((MOL_COMPONENT['DB_ID'], MOLECULE_ENTRY['ID']), []) + [(ENTITY_POS, COMPONENT_POS, MOL_POS)]

    if CO_MAP:
        CHUNKS = [chunk for chunk in chunk_list(list(CO_MAP.keys()), 1000)]
        for CHUNK in CHUNKS:
            res = run_query(f"SELECT * FROM {SCHEMA}.molecular_entity_component_map WHERE (molecular_entity_id, molecular_component_id) IN ({','.join(map(lambda x: f'({x[0]},{x[1]})', CHUNK))})")
            for line in res:
                for POS_INFO in CO_MAP.get((line['molecular_entity_id'], line['molecular_component_id']), []):
                    ENTITY_POS, COMPONENT_POS = POS_INFO
                    MOL_ENTITY = MOL_ENTITIES[ENTITY_POS]
                    MOL_COMPONENT = MOL_ENTITY['COMPONENT'][COMPONENT_POS]
                    MOL_COMPONENT['MAP_ID'] = line['molecular_entity_component_map_id']

    if SM_MAP:
        CHUNKS = [chunk for chunk in chunk_list(list(SM_MAP.keys()), 1000)]
        for CHUNK in CHUNKS:
            res = run_query(f"SELECT * FROM {SCHEMA}.molecular_component_sm_map WHERE (molecular_component_id, sm_entry_id) IN ({','.join(map(lambda x: f'({x[0]},{x[1]})', CHUNK))})")
            for line in res:
                for POS_INFO in SM_MAP.get((line['molecular_component_id'], line['sm_entry_id']), []):
                    ENTITY_POS, COMPONENT_POS, MOL_POS = POS_INFO
                    MOL_ENTITY = MOL_ENTITIES[ENTITY_POS]
                    MOL_COMPONENT = MOL_ENTITY['COMPONENT'][COMPONENT_POS]
                    MOL_COMPONENT['MOLECULE'][MOL_POS]['DB_ID'] = line['molecular_component_sm_map_id']

    for ENTITY_POS, MOL_ENT in enumerate(MOL_ENTITIES):
        if MOL_ENT['DB_ID'] == -1:
            DBIDS['molecular_entity'] += 1
            MOL_ENT['DB_ID'] = DBIDS['molecular_entity']
            FILE_STATUS['molecular_entity'] = 1
            if ('NEW_MOL_ENTITY' not in STATS):STATS['NEW_MOL_ENTITY']=1
            STATS['NEW_MOL_ENTITY'] += 1
            LIST_HASH = [COMPONENT['HASH'] for COMPONENT in MOL_ENT['COMPONENT']]
            LIST_HASH.sort()
            FILES['molecular_entity'].write(f"{DBIDS['molecular_entity']}\t{MOL_ENT['HASH']}\t{MOL_ENT['STRUCTURE_HASH']}\t{'|'.join(LIST_HASH)}\t{MOL_ENT['STRUCTURE']}\n")

        for COMPONENT_POS, MOL_COMPONENT in enumerate(MOL_ENT['COMPONENT']):
            if MOL_COMPONENT['DB_ID'] == -1:
                DBIDS['molecular_component'] += 1
                MOL_COMPONENT['DB_ID'] = DBIDS['molecular_component']
                FILE_STATUS['molecular_component'] = 1
                if ('NEW_MOL_COMPONENT' not in STATS):STATS['NEW_MOL_COMPONENT']=1
                STATS['NEW_MOL_COMPONENT'] += 1
                LIST_HASH = [MOL['HASH'] for MOL in MOL_COMPONENT['MOLECULE']]
                LIST_HASH.sort()
                FILES['molecular_component'].write(f"{DBIDS['molecular_component']}\t{MOL_COMPONENT['HASH']}\t{MOL_COMPONENT['STRUCTURE_HASH']}\t{MOL_COMPONENT['STRUCTURE']}\t{'|'.join(LIST_HASH)}\tNULL\n")

            if MOL_COMPONENT['MAP_ID'] == -1:
                DBIDS['molecular_entity_component_map'] += 1
                FILE_STATUS['molecular_entity_component_map'] = 1
                if ('NEW_MOL_ENTITY_COMPONENT_MAP' not in STATS):STATS['NEW_MOL_ENTITY_COMPONENT_MAP']=1
                STATS['NEW_MOL_ENTITY_COMPONENT_MAP'] += 1
                FILES['molecular_entity_component_map'].write(f"{DBIDS['molecular_entity_component_map']}\t{MOL_ENT['DB_ID']}\t{MOL_COMPONENT['DB_ID']}\t{MOL_COMPONENT['MOLAR_FRACTION']}\n")

            for MOL_INFO in MOL_COMPONENT['MOLECULE']:
                if MOL_INFO['DB_ID'] != -1:
                    continue
                FILE_STATUS['molecular_component_sm_map'] = 1
                DBIDS['molecular_component_sm_map'] += 1
                if ('NEW_MOL_COMPONENT_SM_MAP' not in STATS):STATS['NEW_MOL_COMPONENT_SM_MAP']=1
                STATS['NEW_MOL_COMPONENT_SM_MAP'] += 1
                FILES['molecular_component_sm_map'].write(f"{DBIDS['molecular_component_sm_map']}\t{MOL_COMPONENT['DB_ID']}\t{MOL_INFO['ID']}\t{MOL_INFO['MOLAR_FRACTION']}\tSIN\n")

    for NAME, CTL in COL_ORDER.items():
        # If no records have been written to the file, we don't need to insert it
        if not FILE_STATUS[NAME]:
            print(f"SKIPPING {NAME}\t")
            continue

        # We close the file handler
        FILES[NAME].close()

        # Preparing the COPY command
        command = f"\\COPY {SCHEMA}.{NAME} {CTL} FROM 'INSERT/{NAME}.csv'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )"

        print(f"{NAME}\tStatus:{FILE_STATUS[NAME]}\t")
        
        # We run the command
        result=[]
        try:
            result = subprocess.check_output(f"{DB_INFO['COMMAND']} -c \"{command}\"", shell=True, stderr=subprocess.STDOUT)
            print(result)
        except subprocess.CalledProcessError as e:
            print(result)
            fail_process(JOB_ID + "008", f"Unable to insert data into {NAME} {e.output.decode('utf-8')}")



def load_compound_smi(db_rec, smi):
    global SOURCE_ID

    query = f"SELECT se.sm_entry_Id, sm_name, smiles, sm_source_id, source_id, sm.sm_molecule_id, sc.sm_counterion_id, sc.counterion_smiles " \
            f"FROM sm_molecule SM " \
            f"LEFT JOIN sm_entry SE ON SM.sm_molecule_id = SE.sm_molecule_id " \
            f"LEFT JOIN sm_source SS ON SE.sm_Entry_Id =SS.sm_Entry_Id " \
            f"LEFT JOIN sm_counterion SC ON SC.sm_counterion_Id = SE.sm_counterion_id " \
            f"WHERE smiles = '{smi}'"

    res = run_query(query)
    if res is False:
        fail_process("FCT_028", "Unable to run query")

    for line in res:
        db_rec[line['smiles']]['ID'] = line['sm_molecule_id']

        if line['sm_entry_id'] != '':
            db_rec[line['smiles']]['CI'][line['counterion_smiles']]['ID'] = line['sm_entry_id']

        if line['source_id'] != SOURCE_ID:
            continue

        if line['sm_entry_id'] != '':
            db_rec[line['smiles']]['CI'][line['counterion_smiles']][line['sm_name']] = line['sm_source_id']


def update_stat(table, name, expected, job_id):
    res = run_query(f"SELECT COUNT(*) CO FROM {table}")

    if res is False:
        fail_process("FCT_029", f"Unable to get the number of {name}")

    co = res[0]['co']

    if expected != co:
        fail_process("FCT_030", f"Different number of {name}. Expected: {expected}; In database: {co}")

    res = run_query(f"SELECT n_record FROM GLB_STAT WHERE concept_name = '{name}'")

    if res is False:
        fail_process("FCT_031", "Unable to get GLB_STAT number")

    if not res:
        query = f"INSERT INTO GLB_STAT (concept_name, n_record) VALUES ('{name}', {co})"
        print(query)  # Print the SQL query for reference
        if not run_query_no_res(query):
            fail_process("FCT_032", f"Unable to insert the number of {name}")
    else:
        query = f"UPDATE GLB_STAT SET n_record = {co} WHERE concept_name = '{name}'"
        print(query)  # Print the SQL query for reference
        if not run_query_no_res(query):
            fail_process("FCT_033", f"Unable to update the number of {name}")






def push_to_prod(JOB_INFO,W_DIR):
    global TG_DIR
    global GLB_VAR
    global JOB_ID
    global PROCESS_CONTROL

    ARCHIVE = ''
    if GLB_VAR['KEEP_PREVIOUS_DATA'] == 'Y':
        # Define the directory where the previous version is going to be archived
        ARCHIVE = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], JOB_INFO['DIR'], 'ARCHIVE')
        if not os.path.isdir(ARCHIVE):
            os.makedirs(ARCHIVE)

    PRD_DIR = os.path.join(TG_DIR, GLB_VAR['PRD_DIR'])
    if not os.path.isdir(PRD_DIR):
        os.makedirs(PRD_DIR)

    PRD_PATH = os.path.join(PRD_DIR, JOB_INFO['DIR'])

    # Remove the previous symlink
    if os.path.islink(PRD_PATH):
        os.unlink(PRD_PATH)

    # If the dev directory is -1, it means it's the first time
    # so we can just create a symlink to the working directory
    if JOB_INFO['TIME']['DEV_DIR'] == -1:
        os.symlink(W_DIR, PRD_PATH)
        return

    curr_ini_dir = os.getcwd()

    os.chdir(os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], JOB_INFO['DIR']))

    dirs = os.listdir('.')
    for dir in dirs:
        if dir == '.' or dir == '..':
            continue
        if dir == PROCESS_CONTROL['DIR']:
            continue

        print("Clean up", os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], JOB_INFO['DIR'], dir))

        if GLB_VAR['KEEP_PREVIOUS_DATA'] == 'Y':
            print("\tArchiving")
            shutil.make_archive(os.path.join(ARCHIVE, dir), 'gztar', dir)
            if os.path.exists(os.path.join(ARCHIVE, dir)):
                shutil.rmtree(dir)

    os.chdir(curr_ini_dir)

    os.symlink(W_DIR, PRD_PATH)

