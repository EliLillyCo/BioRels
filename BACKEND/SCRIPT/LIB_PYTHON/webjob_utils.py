import subprocess
import os
from datetime import datetime

def update_web_job_status(md5_hash, log_str):
    global status_info
    print(log_str)
    status_info['LOG'].append([log_str, datetime.now().strftime('%B %d, %Y, %I:%M %p')])
    run_query_no_res(f"Update web_job set job_status = '{str(status_info)}' WHERE md5id = '{md5_hash}'")
    print(f"Update web_job set job_status = '{str(status_info)}' WHERE md5id = '{md5_hash}'")

def clean_web_job_doc(md5_hash):
    run_query_no_res(f"DELETE FROM web_job_document where web_job_id IN (SELECT web_job_id FROM web_job where md5id='{md5_hash}')")

def upload_web_job_doc(md5_hash, doc_name, doc_type, file_content, file_desc):
    global DB_CONN
    query = f"INSERT INTO web_job_document (web_job_document_id,document_name,document_content,document_description,document_hash,create_date,mime_type,web_job_id) VALUES \
    (nextval('web_job_document_sq'), \
    '{doc_name.replace("'", "''")}', \
    :document_content, \
    '{file_desc.replace("'", "''")}', \
    '{hashlib.md5(file_content.encode()).hexdigest()}', \
    CURRENT_TIMESTAMP, \
    '{doc_type}', \
    (SELECT web_job_id FROM web_job where md5id='{md5_hash}') \
    )"

    stmt = DB_CONN.prepare(query)

    stmt.bind_param(':document_content', file_content, PDO.PARAM_LOB)
    stmt.bind_param(':document_description', file_desc, PDO.PARAM_STR)
    stmt.execute()

def failed_web_job(md5_hash, log_info):
    status_info['STATUS'] = 'Failed'
    status_info['LOG'].append([log_info, datetime.now().strftime('%B %d, %Y, %I:%M %p')])
    run_query_no_res(f"UPDATE web_job set job_status = '{str(status_info)}', time_end=CURRENT_TIMESTAMP WHERE md5id = '{md5_hash}'")
    exit(0)

def monitor_web_job():
    global GLB_RUN_WEBJOBS
    global GLB_VAR
    print("CHECK JOBS\n")

    val = []
    subprocess.run(['qstat | egrep "(' + GLB_VAR['WEBJOB_PREFIX'] + '_)"'], shell=True, stdout=val)
    check = GLB_RUN_WEBJOBS
    n_curr_job = len(val)
    
    for line in val:
        tab = list(filter(None, line.split(" ")))
        job_id = tab[0]
        if job_id in check:
            print("CURRENTLY RUNNING: " + job_id + "\t" + check[job_id] + "\n")
            del check[job_id]

    ended_job = []

    for qstat_id, job_id in check.items():
        ended_job.append(job_id)
        run_query_no_res(f"UPDATE web_job SET time_end = CURRENT_TIMESTAMP WHERE md5id = '{job_id}' AND time_end is null")
        print(f"UPDATE web_job SET time_end = CURRENT_TIMESTAMP WHERE md5id = '{job_id}'\n")
        del GLB_RUN_WEBJOBS[qstat_id]

    res = run_query("SELECT  md5id, job_name FROM web_job WHERE time_end IS NULL AND job_cluster_id IS null")

    for line in res:
        n_curr_job += 1
        if n_curr_job >= GLB_VAR['WEBJOB_LIMIT']:
            print("REACHED LIMIT OF " + str(GLB_VAR['WEBJOB_LIMIT']) + "\n")
            break
        print("SUBMITTING " + line['md5id'] + ' ' + line['job_name'] + "\n")

        webjob_submit(line['md5id'], line['job_name'])

def webjob_submit(job_id, job_name):
    global GLB_RUN_WEBJOBS
    global GLB_VAR
    global TG_DIR

    print("SUBMISSION\n")
    fpath = TG_DIR + '/' + GLB_VAR['BACKEND_DIR'] + '/CONTAINER_SHELL/webjob_' + job_name + '.sh'

    if not check_file_exist(fpath):
        fail_process(job_id + "001", 'Missing script file ' + fpath)

    add_desc = ''
    query = f'qsub -v TG_DIR -o {TG_DIR}/BACKEND/LOG/WEB_LOG/{job_id}_{datetime.now().strftime("%Y_%m_%d_%H_%M_%S")}.o -e {TG_DIR}/BACKEND/LOG/WEB_LOG/{job_id}_{datetime.now().strftime("%Y_%m_%d_%H_%M_%S")}.e -N {GLB_VAR["WEBJOB_PREFIX"]}_{job_id} {add_desc} {fpath} {job_id}'
    print(query)
    
    try:
        subprocess.run(query, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=True)
    except subprocess.CalledProcessError:
        fail_process(job_id + "002", f"Unable to submit job {query}")

    tab = list(filter(None, query.split(' ')))
    run_query_no_res(f"UPDATE web_job SET job_cluster_id = '{tab[2]}' WHERE md5id = '{job_id}'")
    GLB_RUN_WEBJOBS[tab[2]] = job_id

def preload_web_jobs():
    global TG_DIR
    global GLB_RUN_WEBJOBS
    res = run_query("SELECT job_cluster_id, md5id FROM web_job WHERE time_end IS NULL AND job_cluster_id!=null")

    for line in res:
        GLB_RUN_WEBJOBS[line['job_cluster_id']] = line['md5id']
