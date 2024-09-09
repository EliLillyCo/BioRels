############################################################################################################
### pp_${DATASOURCE}.py
############################################################################################################


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
from rdkit import Chem
import csv


# Get job name
job_name = 'pp_${DATASOURCE}'
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



# If you need to review the changes between the database and the input files prior to processing
# This can be useful when you need to cleanup the database before performing any changes due to potential conflict
# Or if you need to prepare the input files first before comparing the data with the database.


success_process()