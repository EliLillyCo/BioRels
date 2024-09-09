import os
import time
from datetime import datetime
import smtplib
import psycopg2
import pickle
import sys
from webjob_utils import preload_web_jobs
import fct_utils
from fct_utils import *

def microtime_float():
    return time.time()
START_SCRIPT_TIME = microtime_float()
START_SCRIPT_TIMESTAMP = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
# PROCESS_CONTROL = {
#     'STEP': 0,
#     'JOB_NAME': JOB_NAME,  # Assuming JOB_NAME is defined somewhere
#     'DIR': '',
#     'LOG': [],
#     'STATUS': 'INIT',
#     'START_TIME': microtime_float(),
#     'END_TIME': '',
#     'STEP_TIME': microtime_float(),
#     'FILE_LOG': ''
# }
time_t = microtime_float()
if TG_DIR is False:
    send_kill_mail('000001', 'NO TG_DIR found')
if not os.path.isdir(TG_DIR):
    send_kill_mail('000002', 'TG_DIR value is not a directory ' + TG_DIR)
if not os.path.isdir(TG_DIR + '/PROCESS') and not os.makedirs(TG_DIR + '/PROCESS'):
    send_kill_mail('000002', 'TG_DIR/PROCESS can\'t be created')
loadProcess()
if ('MONITOR_JOB' in locals()):
	checkTree()
	genHierarchy()
time_t = microtime_float()
os.environ['TZ'] = GLB_VAR['TIMEZONE']
time.tzset()
GLB_VAR['DB_SCHEMA'] = os.getenv('DB_SCHEMA')
GLB_VAR['SCHEMA_PRIVATE'] = os.getenv('SCHEMA_PRIVATE')
connect_db()

load_timestamps()