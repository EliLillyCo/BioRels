


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
job_name = 'db_${DATASOURCE}_cpd'
JOB_ID = get_job_id_by_name(job_name,True)
JOB_INFO=GLB_TREE[JOB_ID]
PROCESS_CONTROL['JOB_NAME'] =job_name

#Get parent job info
CK_INFO=GLB_TREE[get_job_id_by_name('${CPD_PARENT}')]
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

SOURCE_ID=get_source("${DATASOURCE_NC}",True)	


if (not os.path.isdir('STD')):
	add_log("Creating STD directory")
	os.mkdir('STD')
	if not os.path.isdir('STD'):
		fail_process(JOB_ID + '005', 'Unable to create STD')


														   

def convert(file_name):
	sppl = Chem.SDMolSupplier(file_name)
	
	out_file= open('STD/molecule.smi',"w")
	counterion_file= open('STD/counterion.smi',"w")
	COUNTERION_MAP={}
	for mol in sppl:
		if mol is None: continue
		name=mol.GetProp("${DATASOURCE} ID")
		full_smiles='NULL'
		if (mol.HasProp("SMILES")):full_smiles=mol.GetProp("SMILES")
		else: full_smiles=Chem.MolToSmiles(mol)
		if (full_smiles.find('*')!=-1):continue
		inchi='NULL'
		if (mol.HasProp("InChI")):inchi=mol.GetProp("InChI")
		inchikey='NULL'
		if (mol.HasProp("InChIKey")):inchikey=mol.GetProp("InChIKey")
		ALT=[]
		tabS=full_smiles.split(".")
		#We are going to consider the longuest SMILES string as the primary molecule and the rest as counterions
		#counterions WILL NOT be standardized, but follow another manual process
		MAX_LEN=0;
		for t in tabS:MAX_LEN=max(MAX_LEN,len(t))
		SMI=''
		for t in tabS:
			if (len(t)==MAX_LEN):SMI=t
			else:ALT.append(t)
		ALT.sort()
		ALT_V='NULL'
		if (len(ALT)!=0):ALT_V='.'.join(ALT)
		#fputs($fpO,$tab[$HEAD['canonical_smiles']].' '.$tab[$HEAD['chembl_id']]."|".$tab[$HEAD['standard_inchi']]."|".$tab[$HEAD['standard_inchi_key']]."|".implode(".",$ALT)."|".$SMI."\n");
		out_file.write(f"{full_smiles}\t{name}|{inchi}|{inchikey}|{ALT_V}|{SMI}\n")
		if(ALT_V!='NULL'):COUNTERION_MAP[ALT_V]=True
	out_file.close()

	#Now we are going to create a map of counterions
	for c in COUNTERION_MAP:
		counterion_file.write(f"{c} {c}\n")
	counterion_file.close()





convert('${DATASOURCE}_complete_3star.sdf')
standardize_compounds(True)

success_process()

