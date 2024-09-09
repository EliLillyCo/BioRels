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


job_name = 'ck_seq_ontol'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name


add_log("Download release note")

# Setting up directory path
W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'])
if not os.path.isdir(W_DIR):
    fail_process(JOB_ID + "001", 'NO ' + W_DIR + ' found ')
W_DIR = os.path.join(W_DIR, JOB_INFO['DIR'])
if not os.path.isdir(W_DIR):
    os.makedirs(W_DIR)
os.chdir(W_DIR)
if (os.getcwd() != W_DIR):
    fail_process(JOB_ID + "003", 'Unable to chdir ' + W_DIR+" "+os.getcwd())

# Checking the ftp path
if 'LINK' not in GLB_VAR or 'FTP_SEQ_ONTO' not in GLB_VAR['LINK']:
    fail_process(JOB_ID + "004", 'FTP_SEQ_ONTO path not set')

# License terms are found in the README on github
if 'FTP_SEQ_ONTO_GT' not in GLB_VAR['LINK']:
    fail_process(JOB_ID + "005", 'FTP_SEQ_ONTO_G path not set')
if os.path.isfile(os.path.join(W_DIR, 'README.md')):
    os.unlink(os.path.join(W_DIR, 'README.md'))

# Downloading the file
if subprocess.call(["wget", GLB_VAR['LINK']['FTP_SEQ_ONTO_GT'] + '/README.md', '-P', W_DIR]) != 0:
    fail_process(JOB_ID + "006", 'Unable to download README')

# Grep the license
good_license = False
with open('README.md') as f:
    if 'Creative Commons Attribution-ShareAlike 4.0 International License' in f.read():
        good_license = True
if not good_license:
    fail_process(JOB_ID + "007", 'License is different')

# Download the sequence ontology file to get the release date
if subprocess.call(["wget", GLB_VAR['LINK']['FTP_SEQ_ONTO'] + '/so.obo', '-P', W_DIR]) != 0:
    fail_process(JOB_ID + "008", 'Unable to download archive')

add_log("Process release note")

# Read the file to get the version
NEW_RELEASE = ''
with open('so.obo', 'r') as fp:
    for line in fp:
        if 'data-version:' not in line:
            continue
        NEW_RELEASE = line.split()[1]
        break

add_log("Validate release note")
tab2 = NEW_RELEASE.split("-")
if tab2[0] != datetime.now().year and int(tab2[0]) < (datetime.now().year - 3):
    fail_process(JOB_ID + "010", 'Unexpected year format ' + NEW_RELEASE)

add_log("Get current release date")
# Assuming getCurrentReleaseDate is some function to get current release date
CURR_RELEASE = get_current_release_date('SEQ_ONTO',JOB_ID)

add_log("Compare release "+CURR_RELEASE+"<>"+NEW_RELEASE)
# When it's the same, we don't want to keep the file
if CURR_RELEASE == NEW_RELEASE:
    os.unlink(os.path.join(W_DIR, 'so.obo'))
    if os.path.isfile(os.path.join(W_DIR, 'so.obo')):
        fail_process(JOB_ID + "011", 'Unable to remove so.obo')
    success_process('VALID')

add_log("Update release tag")
# Assuming updateReleaseDate is some function to update release date
update_release_date(JOB_ID, 'SEQ_ONTO', NEW_RELEASE)

add_log("Create directory")


# Creating the corresponding directory
W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'])
if not os.path.isdir(W_DIR):
    fail_process(JOB_ID + "012", 'NO ' + W_DIR + ' found ')
W_DIR = os.path.join(W_DIR, JOB_INFO['DIR'])
if not os.path.isdir(W_DIR):
    os.makedirs(W_DIR)
W_DIR = os.path.join(W_DIR, str(datetime.now().date()))

if not os.path.isdir(W_DIR):
    os.makedirs(W_DIR)

# Moving the file to the new directory
if not shutil.move('so.obo', os.path.join(W_DIR, 'so.obo')):
    fail_process(JOB_ID + "015", 'Unable to move go.obo to ' + W_DIR)

# Updating the process control directory path
PROCESS_CONTROL['DIR'] = str(datetime.now().date())

success_process()
