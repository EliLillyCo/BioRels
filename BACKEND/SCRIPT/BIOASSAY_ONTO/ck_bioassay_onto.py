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
global PROCESS_CONTROL


job_name = 'ck_bioassay_onto'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name


TG_DIR = os.getenv('TG_DIR')
if TG_DIR is None:
    raise ValueError('NO TG_DIR found')
if not os.path.isdir(TG_DIR):
    raise ValueError(f'TG_DIR value is not a directory {TG_DIR}')

add_log("Download release note")

W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], JOB_INFO['DIR'])
os.makedirs(W_DIR, exist_ok=True)
os.chdir(W_DIR)

if 'FTP_BAO' not in GLB_VAR['LINK']:
    fail_process(f"{JOB_ID}004", 'FTP_BAO path not set')

if not dl_file(f"{GLB_VAR['LINK']['FTP_BAO']}/bao_complete_merged.owl", 3):
    fail_process(f"{JOB_ID}005", 'Unable to download archive')

add_log("Process release note")
content = subprocess.check_output(['grep', 'owl:versionInfo', 'bao_complete_merged.owl'], text=True)
match = re.search(r'(\d{0,4}\.\d{0,3}\.\d{0,3})', content)
if not match:
    fail_process(f"{JOB_ID}006", 'Unable to verify date')

NEW_RELEASE = match.group(0)

add_log("Get current release date")
CURR_RELEASE = get_current_release_date('BIOASSAY', JOB_ID)

add_log("Compare release")
if CURR_RELEASE == NEW_RELEASE:
    os.unlink(os.path.join(W_DIR, 'bao_complete_merged.owl'))
    success_process('VALID')

add_log("Compare License")
if os.path.isfile('LICENSE'):
    os.unlink('LICENSE')

with open('LICENSE', 'w') as f:
    subprocess.run(['grep', 'dc:license', 'bao_complete_merged.owl'], stdout=f, check=True)

if not os.path.isfile('CURRENT_LICENSE'):
    os.rename('LICENSE', 'CURRENT_LICENSE')
else:
    with open('LICENSE', 'r') as f, open('CURRENT_LICENSE', 'r') as f2:
        valid = all(line.strip() == line2.strip() for line, line2 in zip(f, f2))
    os.unlink(os.path.join(W_DIR, 'LICENSE'))
    if not valid:
        fail_process(f"{JOB_ID}014", 'License file is different')

add_log("Update release tag")
update_release_date(JOB_ID, 'BIOASSAY', NEW_RELEASE)


add_log("Create directory")

W_DIR = os.path.join(TG_DIR, GLB_VAR['PROCESS_DIR'], JOB_INFO['DIR'], get_curr_date())
if not os.path.isdir(W_DIR): os.makedirs(W_DIR)
shutil.move('bao_complete_merged.owl', os.path.join(W_DIR, 'bao_complete_merged.owl'))
PROCESS_CONTROL['DIR'] = get_curr_date()
print(PROCESS_CONTROL)

success_process()
