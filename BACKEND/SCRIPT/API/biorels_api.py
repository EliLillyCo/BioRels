import os
import json
import sys
sys.path.append(os.getenv('TG_DIR')+"/BACKEND/SCRIPT/LIB_PYTHON/")
import time
from datetime import datetime
import smtplib
import psycopg2
import pickle
import sys
from fct_utils import *
from loader import *
from queries import *

def process_block(BLOCK):
    lines = BLOCK.split("\n")
    PARAMS = {}
    TITLE = ''
    FUNCTION = ''
    DESCRIPTION = ''
    ALIAS = []
    PORTAL = ''
    for line in lines:
        pos = line.find('Title:')
        if pos != -1:
            TITLE = line[pos + 6:].strip()
            continue
        pos = line.find('Function:')
        if pos != -1:
            FUNCTION = line[pos + 9:].strip()
            continue
        pos = line.find('Description:')
        if pos != -1:
            DESCRIPTION = line[pos + 12:].strip()
            continue
        pos = line.find('Portal:')
        if pos != -1:
            PORTAL = line[pos + 7:].strip()
            continue
        pos = line.find('Alias:')
        if pos != -1:
            ALIAS.append(line[pos + 6:].strip())
            continue
        pos = line.find('Parameter:')
        if pos != -1:
            tab = line[pos + 10:].split("|")
            if len(tab) < 4:
                raise ValueError('Unable to parse line ' + line)
            tab[0] = tab[0].replace('$', '').strip()
            tab[1] = tab[1].strip()
            tab[2] = tab[2].strip()
            tab[3] = tab[3].strip()
            if len(tab) > 4:
                tab[4] = tab[4][tab[4].find('Default:')+8:].strip()
                if tab[4] == "''":
                    tab[4] = ''
                elif tab[4] == 'None':
                    tab[4] = None
            PARAMS[tab[0]] = {'NAME': tab[1], 'TYPE': tab[2], 'REQUIRED': tab[3], 'DEFAULT': tab[4] if len(tab) > 4 else ''}
    return {'TITLE': TITLE, 'FUNCTION': FUNCTION, 'DESCRIPTION': DESCRIPTION, 'PARAMS': PARAMS, 'ALIAS': ALIAS, 'PORTAL': PORTAL}

def find_blocks(str_file):
    BLOCKS = []
    prev_pos = 0
    while True:
        pos = str_file.find('$[API]', prev_pos)
        end_pos = str_file.find('$[/API]', pos)
        if pos != -1 and end_pos != -1:
            str_block = str_file[pos:end_pos + 7]
            BLOCKS.append(process_block(str_block))
            prev_pos = end_pos + 7
        else:
            break
    return BLOCKS

def run_api_query(args, BLOCKS):
    function_name = args[0]
    USER_PARAM = {}
    for i in range(1, len(args)):
        if args[i][0] == '-':
            param = args[i][1:]
            if i + 1 >= len(args):
                raise ValueError('No value for parameter:' + param + ' for ' + function_name)
            value = args[i + 1]
            USER_PARAM[param] = value
    for BLOCK in BLOCKS:
        FOUND = False
        if BLOCK['FUNCTION'] == function_name:
            FOUND = True
        if 'ALIAS' in BLOCK and function_name in BLOCK['ALIAS']:
            FOUND = True
        if not FOUND:
            continue
        PARAMS = BLOCK['PARAMS']
        N_PARAM = 0
        FCT_VALUES = {}
        for KEY_PARAM, PARAM in PARAMS.items():
            if PARAM['REQUIRED'] == 'required' and (KEY_PARAM not in USER_PARAM or USER_PARAM[KEY_PARAM] == ''):
                raise ValueError('Parameter ' + KEY_PARAM + ' is required')
            if PARAM['REQUIRED'] == 'optional' and (KEY_PARAM not in USER_PARAM or USER_PARAM[KEY_PARAM] == ''):
                N_PARAM += 1
                FCT_VALUES[N_PARAM] = PARAM['DEFAULT']
                continue
            if KEY_PARAM not in USER_PARAM:
                continue
            value = USER_PARAM[KEY_PARAM]
            if PARAM['TYPE'] == 'array':
                value = value.split(",")
            if PARAM['TYPE'] == 'int' and not value.isdigit():
                raise ValueError('Parameter ' + KEY_PARAM + ' should be int: ' + value)
            if PARAM['TYPE'] == 'float' and not isinstance(value, float):
                raise ValueError('Parameter ' + KEY_PARAM + ' should be float')
            if PARAM['TYPE'] == 'bool':
                if value == 'True':
                    value = True
                elif value == 'False':
                    value = False
                elif value == 'true':
                    value = True
                elif value == 'false':
                    value = False
                elif value == '1':
                    value = True
                elif value == '0':
                    value = False
                elif value == 'on':
                    value = True
                elif value == 'off':
                    value = False
                elif value == 'yes':
                    value = True
                elif value == 'no':
                    value = False
                elif value == 'y':
                    value = True
                elif value == 'n':
                    value = False
                elif value == 'Y':
                    value = True
                elif value == 'N':
                    value = False
                else:
                    raise ValueError('Parameter ' + KEY_PARAM + ' should be bool')
            if PARAM['TYPE'] == 'multi_array':
                tab=value.split(";")
                N_PARAM += 1
                FCT_VALUES[N_PARAM]={}
                for record in tab:
                    pos=record.find("=")
                    if pos==-1:
                        raise ValueError('Unable to parse multi_array')
                    key=record[:pos]
                    value=record[pos+1:].split(",")
                    FCT_VALUES[N_PARAM][key]=value
                    pprint.pp(FCT_VALUES)
                continue
            N_PARAM += 1
            FCT_VALUES[N_PARAM] = value
        RESULTS = []
        pprint.pp(FCT_VALUES)
        if N_PARAM == 0:
            RESULTS = function_name()
        elif N_PARAM in range(1, 9):
            RESULTS = eval(function_name)(*FCT_VALUES.values())
        return RESULTS
    raise ValueError('Unable to find ' + function_name)


def microtime_float():
    import time
    return time.time()

TG_DIR = os.getenv('TG_DIR')

if TG_DIR is False:
    raise ValueError('NO TG_DIR found')
if not os.path.isdir(TG_DIR):
    raise ValueError('TG_DIR value is not a directory ' + TG_DIR)
if not os.path.isdir(os.path.join(TG_DIR, 'PROCESS')):
    os.mkdir(os.path.join(TG_DIR, 'PROCESS'))

with open('./queries.py', 'r') as file:
    str_content = file.read()

BLOCKS = find_blocks(str_content)


import datetime
GLB_VAR = {}
GLB_VAR['TIMEZONE'] = os.getenv('TIMEZONE')


args = []
import sys
for i in range(1, len(sys.argv)):
    args.append(sys.argv[i])

results=run_api_query(args, BLOCKS)
if (results is not None):
	print(json.dumps(results, indent=4,default=str))
