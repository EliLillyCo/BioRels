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
job_name = 'ck_chebi_rel'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name


W_DIR = TG_DIR + GLB_VAR['PROCESS_DIR']
if not os.path.isdir(W_DIR):
	fail_process(JOB_ID + '001', 'NO ' + W_DIR + ' found ')

W_DIR +=  JOB_INFO['DIR'] 
if not os.path.isdir(W_DIR):
	os.mkdir(W_DIR)
	if not os.path.isdir(W_DIR):
		fail_process(JOB_ID + '002', 'Unable to find and create ' + W_DIR)

os.chdir(W_DIR)

if (os.getcwd() != W_DIR):	
	fail_process(JOB_ID + '003', 'Unable to chdir ' + W_DIR+"\n Current directory: "+os.getcwd())

add_log("Working directory:"+W_DIR)


add_log("Get FTP_CHEBI link and ChEBI release date")
# Get FTP_CHEBI link
if not 'FTP_CHEBI' in GLB_VAR['LINK']:
	fail_process(JOB_ID + '004', 'FTP_CHEBI not found')

# Download index file from FTP_CHEBI
add_log("Download index file from FTP_CHEBI")
if (not dl_file(GLB_VAR['LINK']['FTP_CHEBI']+'/Flat_file_tab_delimited/' ,3, 'index.html')):
	fail_process(JOB_ID + '005', 'Unable to download index.html from FTP_CHEBI')

# Get the last update date from index.html
add_log("Get the last update date from index.html")
f = open('index.html', 'r')
lines = f.readlines()
f.close()

new_release_date='';
for line in lines:
	# Extract date from following line:
	#<tr><td valign="top"><img src="/icons/text.gif" alt="[TXT]"></td><td><a href="database_accession.tsv">database_accession.tsv</a></td><td align="right">2023-12-14 15:09  </td><td align="right"> 19M</td><td>&nbsp;</td></tr>
	if (line.find('database_accession.tsv') != -1):
		date = line.split('<td align="right">')[1].split('  ')[0]
		# Extract date portion from date
		date = date.split(' ')[0]
		# Check date format using regex
		if (not re.match(r'^\d{4}-\d{2}-\d{2}$', date)):
			fail_process(JOB_ID + '006', 'Unable to extract date from index.html')
		new_release_date=date
		break

if (new_release_date == ''):
	fail_process(JOB_ID + '007', 'Unable to extract date from index.html')
add_log("New release date:"+new_release_date)

#Delete the index.html file
add_log("Delete the index.html file")
os.remove('index.html')
if os.path.exists('index.html'):
	fail_process(JOB_ID + '008', 'Unable to remove index.html')

# Compare the last update date with the one in the database
add_log("Compare the last update date with the one in the database")
CURR_RELEASE=get_current_release_date('CHEBI',JOB_ID)


if (CURR_RELEASE == new_release_date):
	add_log("Same as current release date")
	# No new release found
	# Update
	success_process("VALID")


add_log("Update release tag due to new release date")
update_release_date(JOB_ID,'ChEBI',new_release_date)


add_log("Create working directory")
PROCESS_CONTROL['DIR']='N/A'

today_date=get_curr_date()
# Create directory
PROCESS_CONTROL['DIR'] = today_date
if (not os.path.isdir(today_date)):
	os.mkdir(today_date)
	if not os.path.isdir(today_date):
		fail_process(JOB_ID + '009', 'Unable to create ' + today_date + ' directory')

success_process()

