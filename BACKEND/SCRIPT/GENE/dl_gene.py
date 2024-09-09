import os


# Get root directories
TG_DIR = os.getenv('TG_DIR')
if TG_DIR is None:
    print('NO TG_DIR found')
    exit(1)
if not os.path.isdir(TG_DIR):
    print('TG_DIR value is not a directory ')
    exit(2)



import shutil
import subprocess
from datetime import datetime
import sys
sys.path.append(os.getenv('TG_DIR')+"/BACKEND/SCRIPT/LIB_PYTHON/")
from datetime import datetime
from fct_utils import *
from loader import *


job_name = 'dl_gene'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name

import os
from datetime import datetime

add_log("Create directory")

# Since this is the first script for the GENE process, it is not referred to another script to get the directory
# but will rather create the directory
W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'])
if not os.path.isdir(W_DIR):
    fail_process(JOB_ID + "001", 'NO ' + W_DIR + ' found')
W_DIR = os.path.join(W_DIR, JOB_INFO['DIR'])
if not os.path.isdir(W_DIR):
    os.mkdir(W_DIR)
os.chdir(W_DIR)
if (os.getcwd() != W_DIR):
    fail_process(JOB_ID + "002", 'Unable to find and create ' + W_DIR)

# by getting the current date
W_DIR = os.path.join(W_DIR, datetime.now().strftime('%Y-%m-%d'))
if not os.path.isdir(W_DIR):
    os.mkdir(W_DIR)
if not os.path.isdir(W_DIR):
    fail_process(JOB_ID + "003", 'Unable to create new process dir ' + W_DIR)
os.chdir(W_DIR)
if (os.getcwd() != W_DIR):
    fail_process(JOB_ID + "004", 'Unable to access process dir ' + W_DIR)
print(W_DIR)

PROCESS_CONTROL['DIR'] = datetime.now().strftime('%Y-%m-%d')

add_log("Download Gene file")

# We check that we have the ftp weblink
if 'FTP_NCBI' not in GLB_VAR['LINK']:
    fail_process(JOB_ID + "005", 'FTP_NCBI_GENE path not set')
if not dl_file(GLB_VAR['LINK']['FTP_NCBI'] + '/gene/DATA/gene_info.gz', 3):
    fail_process(JOB_ID + "006", 'Unable to download archive')

add_log("Untar archive")
if not ungzip('gene_info.gz'):
    fail_process(JOB_ID + "007", 'Unable to extract archive')

add_log("File check")
if not validate_line_count('gene_info', 24000000):
    fail_process(JOB_ID + "008", 'gene_info is smaller than expected')

add_log("Download and Extract Gene History")
if not dl_file(GLB_VAR['LINK']['FTP_NCBI'] + '/gene/DATA/gene_history.gz', 3):
    fail_process(JOB_ID + "009", 'Unable to download gene history archive')
if not ungzip('gene_history.gz'):
    fail_process(JOB_ID + "010", 'Unable to extract archive')
if not validate_line_count('gene_history', 11305361):
    fail_process(JOB_ID + "011", 'gene_history smaller than expected')

add_log("Download and Extract Gene Ensembl Mapping")
if not dl_file(GLB_VAR['LINK']['FTP_NCBI'] + '/gene/DATA/gene2ensembl.gz', 3):
    fail_process(JOB_ID + "012", 'Unable to download gene2ensembl archive')
if not ungzip('gene2ensembl.gz'):
    fail_process(JOB_ID + "013", 'Unable to extract archive')
if not validate_line_count('gene2ensembl', 2500000):
    fail_process(JOB_ID + "014", 'gene2ensembl smaller than expected')

success_process()