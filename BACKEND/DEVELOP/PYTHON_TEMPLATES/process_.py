############################################################################################################
### process_${DATASOURCE}.py
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
import math
import sys
from fct_utils import *
from loader import *
from rdkit import Chem
import csv


# Get job name
job_name = 'process_${DATASOURCE}'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name

#Get parent job info
CK_INFO=GLB_TREE[get_job_id_by_name('pmj_${DATASOURCE}')]
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

add_log("Load static data")
#If you have data from some relatively small tables that you wish to load in memory to speed up the process:
STATIC_DATA={}

# QUERY='select eco_id,eco_entry_id FROM eco_entry';
# res=run_query(QUERY);if res==False:												fail_process(JOB_ID+"011","Unable to run query ",QUERY);
# for tab in res:	STATIC_DATA['ECO'][tab['eco_id']]=tab['eco_entry_id'];

add_log("Load data to process")
ENTRIES_TO_PROCESS=0

TOT_JOBS=get_line_count(CK_INFO['TIME']['DEV_DIR']+'/SCRIPTS/all.sh')

N_P_JOB=math.ceil(ENTRIES_TO_PROCESS/TOT_JOBS)
START=N_P_JOB*(JOB_RUNID)
END=N_P_JOB*(JOB_RUNID+1)

TO_PROCESS=[]
for i in range(START,END):
	TO_PROCESS.append([])

add_log("Processing records")
fpO=open(str(JOB_RUNID)+'.json','w')
if not fpO:									fail_process(JOB_ID+"014",'Unable to open  '+str(JOB_RUNID)+'.json');
fpE=open(str(JOB_RUNID)+'.err','w')
if not fpE:									fail_process(JOB_ID+"015",'Unable to open  '+str(JOB_RUNID)+'.err');

N_PROCESS=0

for ENTRY in TO_PROCESS:
	++N_PROCESS
	print("###### "+ENTRY[0]+"\t"+str(N_PROCESS)+"\n")
	process_entry(ENTRY,fpO,fpE)


print("END\t"+str(N_PROCESS)+"\n")

fpO.close()
fpE.close()





# fpO = file output
# fpE: File error
function process_entry(ENTRY,fpO,fpE)
{

}
