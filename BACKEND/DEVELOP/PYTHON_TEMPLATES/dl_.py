

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
job_name = 'dl_${DATASOURCE}'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name

#Get parent job info
CK_INFO=GLB_TREE[get_job_id_by_name('ck_${DATASOURCE}_rel')]
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



# Example of how to download file(s)
# # Download index file from FTP_${DATASOURCE}
# add_log("Download index file from FTP_${DATASOURCE}")
# if (not dl_file(GLB_VAR['LINK']['FTP_${DATASOURCE}']+"/Flat_file_tab_delimited/" ,3, 'index.html')):
# 	fail_process(JOB_ID + '005', 'Unable to download index.html from FTP_${DATASOURCE}')

# add_log("Downloading files")
# with open('index.html', 'r') as f:
# 	line=f.readline()
# 	while line:
# 		#print("##"+line)
# 		st=line.split('"')
# 		if (len(st)>10):
# 		#	print("TAB7: +"+st[5])
# 			if (st[5]=='[TXT]' or st[5]=='[   ]'):
# 				add_log("\tDownloading "+st[7])
# 				if (not dl_file(GLB_VAR['LINK']['FTP_${DATASOURCE}']+"/Flat_file_tab_delimited/"+st[7],3,st[7])):
# 					fail_process(JOB_ID + '006', 'Unable to download '+st[7])
# 		line=f.readline()
# f.close()

# add_log("\tDownloading ${DATASOURCE} Complete")
# if (not dl_file(GLB_VAR['LINK']['FTP_${DATASOURCE}']+"/SDF/${DATASOURCE}_complete.sdf.gz",3)):
# 	fail_process(JOB_ID + '007', 'Unable to download SDF/${DATASOURCE}_complete.sdf.gz')

# if (not dl_file(GLB_VAR['LINK']['FTP_${DATASOURCE}']+"/SDF/${DATASOURCE}_complete_3star.sdf.gz",3)):
# 	fail_process(JOB_ID + '008', 'Unable to download SDF/${DATASOURCE}_complete_3star.sdf.gz')






success_process()

