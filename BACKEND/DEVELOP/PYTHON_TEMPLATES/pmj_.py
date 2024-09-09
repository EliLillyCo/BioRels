

################## WARNING - UNTESTED CODE ######################

import sys
import os
sys.path.append(os.getenv('TG_DIR')+"/BACKEND/SCRIPT/LIB_PYTHON/")
import time
from datetime import datetime
import smtplib
import psycopg2
import pickle
import sys
from fct_utils import *
from loader import *

# Get job name
job_name = 'pmj_${DATASOURCE}'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name

#Get parent job info
CK_INFO=GLB_TREE[get_job_id_by_name('${PP_PARENT}')]
PROCESS_CONTROL['DIR']=CK_INFO['TIME']['DEV_DIR']

W_DIR = TG_DIR + GLB_VAR['PROCESS_DIR']
if not os.path.isdir(W_DIR):
	fail_process(JOB_ID + '001', 'NO ' + W_DIR + ' found ')


W_DIR +=  JOB_INFO['DIR']+'/' 
if not os.path.isdir(W_DIR):
	os.mkdir(W_DIR)
	if not os.path.isdir(W_DIR):
		fail_process(JOB_ID + '002', 'Unable to find and create ' + W_DIR)
os.chdir(W_DIR)

W_DIR += CK_INFO['TIME']['DEV_DIR']
if not os.path.isdir(W_DIR):
	fail_process(JOB_ID + '003', 'NO ' + W_DIR + ' found ')
os.chdir(W_DIR)
if (os.getcwd() != W_DIR):	
	fail_process(JOB_ID + '004', 'Unable to chdir ' + W_DIR)


# Check SCRIPT_DIR
if 'SCRIPT_DIR' not in GLB_VAR:
	fail_process(JOB_ID + '005', 'SCRIPT_DIR not set ')
SCRIPT_DIR = TG_DIR + '/' + GLB_VAR['SCRIPT_DIR']
if not os.path.isdir(SCRIPT_DIR):
	fail_process(JOB_ID + '006', 'SCRIPT_DIR not found ')

# Check setenv.sh
SETENV = SCRIPT_DIR + '/SHELL/setenv.sh'
if not os.path.isfile(SETENV):
	fail_process(JOB_ID + '007', 'Setenv file not found ')

# Check process_${DATASOURCE}.php
RUNSCRIPT = SCRIPT_DIR + '/' + JOB_INFO['DIR'] + '/process_${DATASOURCE}.php'
if not os.path.isfile(RUNSCRIPT):
	fail_process(JOB_ID + '008', RUNSCRIPT + ' file not found')
	
RUNSCRIPT_PATH = '$TG_DIR/' + GLB_VAR['SCRIPT_DIR'] + '/' + JOB_INFO['DIR'] + '/process_${DATASOURCE}.php'
if 'JOBARRAY' not in GLB_VAR:
	fail_process(JOB_ID + '009', 'JOBARRAY NOT FOUND ')

add_log('Working directory: ' + W_DIR)
W_DIR_PATH = '$TG_DIR/' + GLB_VAR['PROCESS_DIR'] + '/' + CK_INFO['DIR'] + '/' + CK_INFO['TIME']['DEV_DIR']
if not os.path.isdir("SCRIPTS") and not os.mkdir("SCRIPTS"):
	fail_process(JOB_ID + '015', 'Unable to create jobs directory')
if not os.path.isdir("JSON") and not os.mkdir("JSON"):
	fail_process(JOB_ID + '016', 'Unable to create jobs directory')

# Create master script
fpA = open("SCRIPTS/all.sh", 'w')
if not fpA:
	fail_process(JOB_ID + '017', 'Unable to open all.sh')
N_JOB = 50

for I in range(N_JOB):
	JOB_NAME = "SCRIPTS/job_" + str(I) + ".sh"
	fp = open(JOB_NAME, "w")
	if not fp:
		fail_process(JOB_ID + '018', 'Unable to open jobs/job_' + str(I) + '.sh')
	fpA.write(" sh " + W_DIR_PATH + '/' + JOB_NAME + "\n")
	fp.write('#!/bin/sh\n')
	fp.write("source " + SETENV + "\n")
	fp.write('cd ' + W_DIR_PATH + "\n")
	fp.write('biorels_${LANGUAGE} ' + RUNSCRIPT_PATH + ' ' + str(I) + ' F &> SCRIPTS/LOG_' + str(I) + "\n")
	fp.write('echo $? > SCRIPTS/status_' + str(I) + "\n")
	fp.close()
fpA.close()
success_process()


