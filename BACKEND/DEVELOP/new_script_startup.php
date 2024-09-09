<?php



/// TG_DIR should be already set in environment variables using setenv.sh
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR parameter found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);

require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');


/// Loading all currently existing jobs and hierarchy
preloadJobs();
preloadWebJobs();
checkTree();
genHierarchy();

/// Finding the highest job ID
$TREE_MAX_ID=max(array_keys($GLB_TREE));



/// User parameters where all the magic happens
$USER_PARAMS=
array(
	'USER_INI_PARAMS'=>
	array(
		'W_CPD'=>false,
		'SMALL_JOB'=>false,
		'PARALLEL_JOB'=>false,
		'PREPARE_JOB'=>false,
		'PHP'=>false,
		'PRIVATE'=>false,
		
		'LOGIN'=>false,
	),
	'CURR_MAX_JOB_ID'=>round(($TREE_MAX_ID+20)/20)*20,
	'DATA_SOURCE'=>'',
	'FTP_PATH'=>'',
	'JOBS'=>array(),
	'FILES'=>array()
);

step_1_ini_questions($USER_PARAMS);

step_2_init_jobs($USER_PARAMS);

if (isset($USER_PARAMS['JOBS']['cpd'])) list_concurrent($USER_PARAMS['JOBS']['cpd'],$GLB_TREE,'cpd');
if (isset($USER_PARAMS['JOBS']['rmj'])) list_concurrent($USER_PARAMS['JOBS']['rmj'],$GLB_TREE,'rmj');

step_3_dependencies($USER_PARAMS,$GLB_TREE);

exportAll($USER_PARAMS);

exit(0);












/// Here we are going to ask the user a few questions to determine the type of process needed
function step_1_ini_questions(&$USER_PARAMS)
{

	/// First, we ask the user if the process is small enough to be covered by 1 script
	echo 'Is your process small enough to be covered by 1 script? (Y/N): ';
	$answer = strtoupper(trim(fgets(STDIN)));
	if ($answer === 'Y') {
		$USER_PARAMS['USER_INI_PARAMS']['SMALL_JOB'] = true;
	} else if ($answer=='N'){
		$USER_PARAMS['USER_INI_PARAMS']['SMALL_JOB'] = false;
	}else die('Unrecognized answer');


	/// But if not, we ask a few more questions to determine the type of process
	if (!$USER_PARAMS['USER_INI_PARAMS']['SMALL_JOB'])
	{
		$questions = array(
			'W_CPD' => 'Does your process includes processing compounds (Y/N): ',
			'PARALLEL_JOB' => 'Does your process requires to run parallel jobs? (Y/N): ',
			'PREPARE_JOB'=>'Do you need a preparation/cleanup script prior to the processing script? (Y/N): ',
		);

		foreach ($questions as $param => $question) {
			echo $question;
			$answer = strtoupper(trim(fgets(STDIN)));

			if ($answer === 'Y') {
				$USER_PARAMS['USER_INI_PARAMS'][$param] = true;
			} else if ($answer=='N'){
				$USER_PARAMS['USER_INI_PARAMS'][$param] = false;
			}else die('Unrecognized answer');
		}
	}

	/// In the end, we ask the user if he prefers to code in PHP or Python, and if the data source is private or requires a login
	$questions=array(
		'PHP' 	 => 'Do you prefer to code in PHP (N for Python)? (Y/N): ',
		'PRIVATE'=> 'Is this a private data source? (Y/N):',
		'LOGIN'	 => 'Does it require a login/password? (Y/N):'
	);

	foreach ($questions as $param => $question) {
		echo $question;
		$answer = strtoupper(trim(fgets(STDIN)));

		if ($answer === 'Y') {
			$USER_PARAMS['USER_INI_PARAMS'][$param] = true;
		} else if ($answer=='N'){
			$USER_PARAMS['USER_INI_PARAMS'][$param] = false;
		}else die('Unrecognized answer');
	}


	/// Since we download files from the FTP/HTTPS, we ask the user for the FTP path
	echo "What would be a good root FTP/HTTPS Path for the location of the files: ";
	$answer = trim(fgets(STDIN));
	if (strpos($answer,' ')!==false)die('No space please');
	$USER_PARAMS['FTP_PATH']=$answer;

	/// Finally, we ask the user for the name of the data source, this will be used for the directory name, script names etc
	echo "What is the name of the data source (no space):";
	$answer = trim(fgets(STDIN));
	if ($answer=='') die("No value provided");
	if (strpos($answer,' ')!==false)die('No space please');
	$USER_PARAMS['DATA_SOURCE']=$answer;

}






/// Based on user options we load the template files, create the jobs and prepare the scripts
function step_2_init_jobs(&$USER_PARAMS)
{
	$PAR=&$USER_PARAMS['USER_INI_PARAMS'];

	/// This is a template array describing all the parameters of a job defined in CONFIG_JOB
	$TEMPLATE_JOB=array('NAME'=>'',
					'REQUIRE'=>array(),
					'REQ_TRIGGER'=>array(),
					'REQ_UPDATED'=>array(),
					'DIR'=>strtoupper($USER_PARAMS['DATA_SOURCE']),
					'REQ_RULE'=>'C',
					'DEV_JOB'=>'D',
					'FREQ'=>'P',
					'ENABLED'=>'',
					'RUNTIME'=>'S',
					'CONCURRENT'=>array(),
					'MEM'=>'-1',
					'DESC'=>'',
					'FAILED'=>0,
					'IS_PRIVATE'=>$PAR['PRIVATE']?'T':'F',
					'ID'=>-1);
					$LOW_SOURCE=strtolower($USER_PARAMS['DATA_SOURCE']);


	/// If it's a small job, we need to create a check (ck_) job and a wh_ job (whole job)
	
	if ($PAR['SMALL_JOB']=='T')
	{
		$CK_JOB=$TEMPLATE_JOB;
		$CK_JOB['NAME']='ck_'.strtolower($LOW_SOURCE).'_rel';
		$CK_JOB['DESC']='Check for new release of '.$USER_PARAMS['DATA_SOURCE'];
		/// New job ID
		$USER_PARAMS['CURR_MAX_JOB_ID']++;
		$CK_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
		/// Frequency of the job, 10 after midnight
		$CK_JOB['FREQ']='00:10';
		$USER_PARAMS['JOBS']['ck']=$CK_JOB;
		/// Load the template file for the check job:
		$USER_PARAMS['FILES']['ck']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/ck_rel.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));

		$WH_JOB=$TEMPLATE_JOB;
		
		$WH_JOB['NAME']='wh_'.strtolower($USER_PARAMS['DATA_SOURCE']);
		$WH_JOB['FREQ']='P';
		$WH_JOB['DEV_JOB']='P';
		$WH_JOB['DESC']='Complete processing of '.$USER_PARAMS['DATA_SOURCE'];
		/// Depends on the check job
		$WH_JOB['REQUIRE'][]=$CK_JOB['ID'];
		/// New job ID
		$USER_PARAMS['CURR_MAX_JOB_ID']++;
		$WH_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
		$USER_PARAMS['JOBS']['wh']=$WH_JOB;
		$USER_PARAMS['FILES']['wh']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/wh_.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));
	}
	else /// Otherwise it's more complex
	{

		/// We always start with a check job, a download job and finish with a database job and a production job
		$CK_JOB=$TEMPLATE_JOB;
		
		$CK_JOB['NAME']='ck_'.strtolower($USER_PARAMS['DATA_SOURCE']).'_rel';
		/// Frequency of the job, 10 after midnight
		$CK_JOB['FREQ']='00:10';
		$CK_JOB['DESC']='Check for new release of '.$USER_PARAMS['DATA_SOURCE'];
		/// New Job ID:
		$USER_PARAMS['CURR_MAX_JOB_ID']++;
		$CK_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
		$USER_PARAMS['JOBS']['ck']=$CK_JOB;
		/// Load the template file for the check job:
		$USER_PARAMS['FILES']['ck']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/ck_rel.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));


		$DL_JOB=$TEMPLATE_JOB;
		$USER_PARAMS['CURR_MAX_JOB_ID']++;
		$DL_JOB['NAME']='dl_'.strtolower($USER_PARAMS['DATA_SOURCE']);
		$DL_JOB['DESC']='Download '.$USER_PARAMS['DATA_SOURCE'].' files';
		$DL_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
		/// Depends on the check job
		$DL_JOB['REQUIRE'][]=$CK_JOB['ID'];
		$USER_PARAMS['JOBS']['dl']=$DL_JOB;
		$USER_PARAMS['FILES']['dl']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/dl_.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));

		
			/// Here we create the new jobs and load the template files BUT we do not add the dependencies yet:
		if ($PAR['PREPARE_JOB'])
		{
			$PREP_JOB=$TEMPLATE_JOB;
			$USER_PARAMS['CURR_MAX_JOB_ID']++;
			$PREP_JOB['NAME']='pp_'.strtolower($USER_PARAMS['DATA_SOURCE']);
			$PREP_JOB['DESC']='Preparation script for '.$USER_PARAMS['DATA_SOURCE'];
			$PREP_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
			$USER_PARAMS['JOBS']['pp']=$PREP_JOB;
			$USER_PARAMS['FILES']['pp']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/pp_.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));
		}
		if ($PAR['W_CPD'])
		{
			$CPD_JOB=$TEMPLATE_JOB;
			$USER_PARAMS['CURR_MAX_JOB_ID']++;
			$CPD_JOB['NAME']='db_'.strtolower($USER_PARAMS['DATA_SOURCE']).'_cpd';
			$CPD_JOB['DESC']='Processing compounds from '.$USER_PARAMS['DATA_SOURCE'];
			$CPD_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
			$USER_PARAMS['JOBS']['cpd']=$CPD_JOB;
			$USER_PARAMS['FILES']['cpd']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/db_cpd.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));
		}
		if ($PAR['PARALLEL_JOB'])
		{
			$PMJ_JOB=$TEMPLATE_JOB;
			$USER_PARAMS['CURR_MAX_JOB_ID']++;
			$PMJ_JOB['NAME']='pmj_'.strtolower($USER_PARAMS['DATA_SOURCE']);
			$PMJ_JOB['DESC']='Preparing parallel jobs for '.$USER_PARAMS['DATA_SOURCE'];
			$PMJ_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
			$USER_PARAMS['JOBS']['pmj']=$PMJ_JOB;
			$USER_PARAMS['FILES']['pmj']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/pmj_.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));

			$RMJ_JOB=$TEMPLATE_JOB;
			$USER_PARAMS['CURR_MAX_JOB_ID']++;
			$RMJ_JOB['NAME']='rmj_'.strtolower($USER_PARAMS['DATA_SOURCE']);
			$RMJ_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
			$RMJ_JOB['DESC']='Running parallel jobs for '.$USER_PARAMS['DATA_SOURCE'];
			$RMJ_JOB['RUNTIME']='R';
			$USER_PARAMS['JOBS']['rmj']=$RMJ_JOB;
			$USER_PARAMS['FILES']['rmj']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/rmj_.php');

			$PRO_JOB=$TEMPLATE_JOB;
			$USER_PARAMS['CURR_MAX_JOB_ID']++;
			$PRO_JOB['NAME']='process_'.strtolower($USER_PARAMS['DATA_SOURCE']);
			$PRO_JOB['DESC']='Script for parallel processing of '.$USER_PARAMS['DATA_SOURCE'];
			$PRO_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
			$USER_PARAMS['JOBS']['pro']=$PRO_JOB;
			$USER_PARAMS['FILES']['pro']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/process_.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));
			
			
		}

		/// Then we create the database and production jobs
		$DB_JOB=$TEMPLATE_JOB;
		$USER_PARAMS['CURR_MAX_JOB_ID']++;
		$DB_JOB['NAME']='db_'.strtolower($USER_PARAMS['DATA_SOURCE']);
		$DB_JOB['DESC']='Pushing to database the data for '.$USER_PARAMS['DATA_SOURCE'];
		$DB_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
		$USER_PARAMS['JOBS']['db']=$DB_JOB;
		$USER_PARAMS['FILES']['db']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/db_.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));
		

		$PRD_JOB=$TEMPLATE_JOB;
		$USER_PARAMS['CURR_MAX_JOB_ID']++;
		$PRD_JOB['NAME']='prd_'.strtolower($USER_PARAMS['DATA_SOURCE']);
		$PRD_JOB['DEV_JOB']='P';
		$PRD_JOB['DESC']='Switching '.$USER_PARAMS['DATA_SOURCE'].' to production' ;
		$PRD_JOB['ID']=$USER_PARAMS['CURR_MAX_JOB_ID'];
		$USER_PARAMS['JOBS']['prd']=$PRD_JOB;
		$USER_PARAMS['FILES']['prd']=file_get_contents(($USER_PARAMS['USER_INI_PARAMS']['PHP']?'PHP':'PYTHON').'_TEMPLATES/prd_.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'));


		/// Now depending on the type of process we add the corresponding dependencies
		if ($PAR['PREPARE_JOB'] && $PAR['W_CPD'] && $PAR['PARALLEL_JOB'])
		{
			$USER_PARAMS['JOBS']['pp']['REQUIRE'][]=$USER_PARAMS['JOBS']['dl']['ID'];	$USER_PARAMS['FILES']['pp']=str_replace('${PP_PARENT}' ,'dl_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['pp']);
			$USER_PARAMS['JOBS']['cpd']['REQUIRE'][]=$USER_PARAMS['JOBS']['pp']['ID'];	$USER_PARAMS['FILES']['cpd']=str_replace('${CPD_PARENT}','pp_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['cpd']);
			$USER_PARAMS['JOBS']['pmj']['REQUIRE'][]=$USER_PARAMS['JOBS']['cpd']['ID'];	$USER_PARAMS['FILES']['pmj']=str_replace('${PMJ_PARENT}','cpd_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['pmj']);
			$USER_PARAMS['JOBS']['rmj']['REQUIRE'][]=$USER_PARAMS['JOBS']['pmj']['ID'];	$USER_PARAMS['FILES']['rmj']=str_replace('${RMJ_PARENT}','pmj_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['rmj']);
			$USER_PARAMS['JOBS']['db']['REQUIRE'][]=$USER_PARAMS['JOBS']['rmj']['ID'];	$USER_PARAMS['FILES']['db']=str_replace('${DB_PARENT}' ,'rmj_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['db']);
			$USER_PARAMS['JOBS']['prd']['REQUIRE'][]=$USER_PARAMS['JOBS']['db']['ID'];	$USER_PARAMS['FILES']['prd']=str_replace('${PRD_PARENT}','db_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['prd']);
		}
		else if ($PAR['PREPARE_JOB'] && !$PAR['W_CPD'] && $PAR['PARALLEL_JOB'])
		{
			$USER_PARAMS['JOBS']['pp']['REQUIRE'][]=$USER_PARAMS['JOBS']['dl']['ID'];	$USER_PARAMS['FILES']['pp']=str_replace('${PP_PARENT}' ,'dl_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['pp']);
			$USER_PARAMS['JOBS']['pmj']['REQUIRE'][]=$USER_PARAMS['JOBS']['pp']['ID'];	$USER_PARAMS['FILES']['pmj']=str_replace('${PMJ_PARENT}','pp_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['pmj']);
			$USER_PARAMS['JOBS']['rmj']['REQUIRE'][]=$USER_PARAMS['JOBS']['pmj']['ID'];	$USER_PARAMS['FILES']['rmj']=str_replace('${RMJ_PARENT}','pmj_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['rmj']);
			$USER_PARAMS['JOBS']['db']['REQUIRE'][]=$USER_PARAMS['JOBS']['rmj']['ID'];	$USER_PARAMS['FILES']['db']=str_replace('${DB_PARENT}' ,'rmj_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['db']);
			$USER_PARAMS['JOBS']['prd']['REQUIRE'][]=$USER_PARAMS['JOBS']['db']['ID'];	$USER_PARAMS['FILES']['prd']=str_replace('${PRD_PARENT}','db_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['prd']);
		}
		else if ($PAR['PREPARE_JOB'] && $PAR['W_CPD'] && !$PAR['PARALLEL_JOB'])
		{
			$USER_PARAMS['JOBS']['pp']['REQUIRE'][] =$USER_PARAMS['JOBS']['dl']['ID'];	$USER_PARAMS['FILES']['pp']=str_replace('${PP_PARENT}'  ,'dl_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['pp']);
			$USER_PARAMS['JOBS']['cpd']['REQUIRE'][]=$USER_PARAMS['JOBS']['pp']['ID'];	$USER_PARAMS['FILES']['cpd']=str_replace('${CPD_PARENT}' ,'pp_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['cpd']);
			$USER_PARAMS['JOBS']['db']['REQUIRE'][] =$USER_PARAMS['JOBS']['cpd']['ID'];	$USER_PARAMS['FILES']['db']=str_replace('${DB_PARENT}'  ,'cpd_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['db']);
			$USER_PARAMS['JOBS']['prd']['REQUIRE'][]=$USER_PARAMS['JOBS']['db']['ID'];	$USER_PARAMS['FILES']['prd']=str_replace('${PRD_PARENT}' ,'db_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['prd']);
		}
		else if (!$PAR['PREPARE_JOB'] && $PAR['W_CPD'] && $PAR['PARALLEL_JOB'])
		{
			$USER_PARAMS['JOBS']['cpd']['REQUIRE'][]=$USER_PARAMS['JOBS']['dl']['ID'];	$USER_PARAMS['FILES']['cpd']=str_replace('${CPD_PARENT}' ,'dl_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['cpd']);
			$USER_PARAMS['JOBS']['pmj']['REQUIRE'][]=$USER_PARAMS['JOBS']['cpd']['ID'];	$USER_PARAMS['FILES']['pmj']=str_replace('${PMJ_PARENT}' ,'cpd_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['pmj']);
			$USER_PARAMS['JOBS']['rmj']['REQUIRE'][]=$USER_PARAMS['JOBS']['pmj']['ID'];	$USER_PARAMS['FILES']['rmj']=str_replace('${RMJ_PARENT}' ,'pmj_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['rmj']);
			$USER_PARAMS['JOBS']['db']['REQUIRE'][]=$USER_PARAMS['JOBS']['rmj']['ID'];	$USER_PARAMS['FILES']['db']=str_replace('${DB_PARENT}'  ,'rmj_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['db']);
			$USER_PARAMS['JOBS']['prd']['REQUIRE'][]=$USER_PARAMS['JOBS']['db']['ID'];	$USER_PARAMS['FILES']['prd']=str_replace('${PRD_PARENT}' ,'db_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['prd']);
		}
		else if (!$PAR['PREPARE_JOB'] && !$PAR['W_CPD'] && $PAR['PARALLEL_JOB'])
		{
			$USER_PARAMS['JOBS']['pmj']['REQUIRE'][]=$USER_PARAMS['JOBS']['dl']['ID'];	$USER_PARAMS['FILES']['pmj']=str_replace('${PMJ_PARENT}' ,'dl_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['dl']);
			$USER_PARAMS['JOBS']['rmj']['REQUIRE'][]=$USER_PARAMS['JOBS']['pmj']['ID'];	$USER_PARAMS['FILES']['rmj']=str_replace('${RMJ_PARENT}' ,'pmj_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['pmj']);
			$USER_PARAMS['JOBS']['db']['REQUIRE'][]=$USER_PARAMS['JOBS']['rmj']['ID'];	$USER_PARAMS['FILES']['db']=str_replace('${DB_PARENT}'  ,'rmj_'.strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['rmj']);
			$USER_PARAMS['JOBS']['prd']['REQUIRE'][]=$USER_PARAMS['JOBS']['db']['ID'];	$USER_PARAMS['FILES']['prd']=str_replace('${PRD_PARENT}' ,'db_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['db']);
		}
		else if (!$PAR['PREPARE_JOB'] && $PAR['W_CPD'] && !$PAR['PARALLEL_JOB'])
		{
			$USER_PARAMS['JOBS']['cpd']['REQUIRE'][]=$USER_PARAMS['JOBS']['dl']['ID'];	$USER_PARAMS['FILES']['cpd']=str_replace('${CPD_PARENT}' ,'dl_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['cpd']);
			$USER_PARAMS['JOBS']['db']['REQUIRE'][]=$USER_PARAMS['JOBS']['cpd']['ID'];	$USER_PARAMS['FILES']['db']=str_replace('${DB_PARENT}' ,'cpd_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['db']);
			$USER_PARAMS['JOBS']['prd']['REQUIRE'][]=$USER_PARAMS['JOBS']['db']['ID'];	$USER_PARAMS['FILES']['prd']=str_replace('${PRD_PARENT}' ,'db_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['prd']);
		}
		else if ($PAR['PREPARE_JOB'] && !$PAR['W_CPD'] && !$PAR['PARALLEL_JOB'])
		{
			$USER_PARAMS['JOBS']['pp']['REQUIRE'][]=$USER_PARAMS['JOBS']['dl']['ID'];	$USER_PARAMS['FILES']['pp']=str_replace('${PP_PARENT}' ,'dl_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['pp']);
			$USER_PARAMS['JOBS']['db']['REQUIRE'][]=$USER_PARAMS['JOBS']['pp']['ID'];	$USER_PARAMS['FILES']['db']=str_replace('${DB_PARENT}' ,'pp_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['db']);
			$USER_PARAMS['JOBS']['prd']['REQUIRE'][]=$USER_PARAMS['JOBS']['db']['ID'];	$USER_PARAMS['FILES']['prd']=str_replace('${PRD_PARENT}' ,'db_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['prd']);
		}
		else if (!$PAR['PREPARE_JOB'] && !$PAR['W_CPD'] && !$PAR['PARALLEL_JOB'])
		{
			$USER_PARAMS['JOBS']['db']['REQUIRE'][]=$USER_PARAMS['JOBS']['dl']['ID'];	$USER_PARAMS['FILES']['db']=str_replace('${DB_PARENT}' ,'dl_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['db']);
			$USER_PARAMS['JOBS']['prd']['REQUIRE'][]=$USER_PARAMS['JOBS']['db']['ID'];	$USER_PARAMS['FILES']['prd']=str_replace('${PRD_PARENT}' ,'db_'. strtolower($USER_PARAMS['DATA_SOURCE']),$USER_PARAMS['FILES']['prd']);
		}



	}


	/// Then we modify the templates by replacing some template words by the data source and the language
	foreach ($USER_PARAMS['FILES'] as &$F_C)
	{
		$F_C=str_replace('${DATASOURCE}' ,strtolower($USER_PARAMS['DATA_SOURCE']),$F_C);
		$F_C=str_replace('${DATASOURCE_NC}' ,$USER_PARAMS['DATA_SOURCE'],$F_C);
		$F_C=str_replace('${LANGUAGE}',($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'python'),$F_C);
	}




}






/// We ask the user to specify the critical dependencies of the data source
function step_3_dependencies(&$USER_PARAMS,&$GLB_TREE)
{
	/// So we list the data sources:
	$DATA_SOURCES=array();
	foreach ($GLB_TREE as $JOB)$DATA_SOURCES[$JOB['DIR']]=true;
	$DATA_SOURCES=array_keys($DATA_SOURCES);

	/// Then ask the question:
	echo "Which data sources are critical dependencies of ".$USER_PARAMS['DATA_SOURCE'].' ?'."\n";
	foreach ($DATA_SOURCES as $K=>$S)
	{
		echo $S."\t";
		if ($K%5==0)echo "\n";
	}
	echo "\nList all critical dependencies, separated by space: ";
	$answer = strtoupper(trim(fgets(STDIN)));
	$tab=explode(" ",$answer);

	/// We then compare the answer with the list of data sources and add the dependencies if found
	$LIST_DEPENDENCIES=array();
	foreach ($tab as $t)
	{
		$t_u=strtoupper($t);
		$FOUND=false;
		foreach ($DATA_SOURCES as $S)
		{
			$S_u=strtoupper($S);
			if ($S_u!=$t_u)continue;
			$FOUND=true;
			$LIST_DEPENDENCIES[]=$S;
			break;
		}
		if (!$FOUND) die("Unrecognized data source: ".$t);
	}

	/// From there, we need to find the jobs that are critical dependencies for those datasources

	$DEP_IDS=array();
	foreach ($GLB_TREE as $JOB_ID=>$JOB)
	{
		if (!in_array($JOB['DIR'],$LIST_DEPENDENCIES))continue;
		
		if (substr($JOB['NAME'],0,3)=='db_'||substr($JOB['NAME'],0,3)=='wh_')
		{
			$DEP_IDS[]=$JOB_ID;
			echo "adding ".$JOB['NAME']." as critical dependency script \n";
		}		
	}

	/// And add them as dependencies to the jobs of the current data source
	/// But also make them concurrent jobs, i.e. they shouldn't run if the critical dependencies are running
	if ($DEP_IDS!=array())
	foreach ($USER_PARAMS['JOBS'] as $TYPE=>&$JOB_INFO)
	{
		if (!($TYPE=='db'||$TYPE=='rmj'||$TYPE=='pp'||$TYPE=='wh'))continue;
		foreach ($DEP_IDS as $DEP)
		{
			$JOB_INFO['REQUIRE'][]=$DEP;
			$GLB_TREE[$DEP]['CHANGED']=true;
			$GLB_TREE[$DEP]['CONCURRENT'][]=$JOB_INFO['ID'];
		}
	}
}


function list_concurrent(&$T_JOB,&$GLB_TREE,$TYPE)
{
	foreach ($GLB_TREE as $ID=> &$JOB)
	{
		//echo $JOB['NAME']." ".substr($JOB['NAME'],-4)."\n";
		if ($TYPE=='cpd' && substr($JOB['NAME'],-4)=='_cpd') 
		{
			$JOB['CONCURRENT'][]=$T_JOB['ID'];
			$T_JOB['CONCURRENT'][]=$ID;
			$JOB['CHANGED']=true;
		}
		if ($TYPE=='rmj' && substr($JOB['NAME'],0,4)=='rmj_') 
		{
			$JOB['CHANGED']=true;
			$JOB['CONCURRENT'][]=$T_JOB['ID'];
			$T_JOB['CONCURRENT'][]=$ID;;
		}
	}

	
}








function exportAll(&$USER_PARAMS)
{
	global $TG_DIR;
	global $GLB_TREE;
	$UC_DT=strtoupper($USER_PARAMS['DATA_SOURCE']);

	/// Create the directory:
	if (is_dir($UC_DT)) cleanDirectory($UC_DT);
	mkdir($UC_DT);
	chdir($UC_DT);


	// Create a sub-directory for the PHP and Python scripts
	mkdir($UC_DT);
	chdir($UC_DT);
	///Save all the script files:
	foreach ($USER_PARAMS['FILES'] as $F=>$T)
	{
		$fp=fopen($USER_PARAMS['JOBS'][$F]['NAME'].'.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py'),'w');
		fputs($fp,$T);
		fclose($fp);	
	}
	chdir('..');


	/// Now open the changes file to write the instructions
	$fp=fopen('CONFIG_CHANGES','w');


	$STR='# Files generated on '.date('Y-m-d H:i:s')."\n";
	$STR.='# Data source: '.$USER_PARAMS['DATA_SOURCE']."\n";
	$STR.='# PHP: '.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'Y':'N')."\n";
	$STR.='# Private: '.($USER_PARAMS['USER_INI_PARAMS']['PRIVATE']?'Y':'N')."\n";
	$STR.='# Login: '.($USER_PARAMS['USER_INI_PARAMS']['LOGIN']?'Y':'N')."\n";
	$STR.='# Small job: '.($USER_PARAMS['USER_INI_PARAMS']['SMALL_JOB']?'Y':'N')."\n";
	$STR.='# Parallel job: '.($USER_PARAMS['USER_INI_PARAMS']['PARALLEL_JOB']?'Y':'N')."\n";
	$STR.='# Prepare job: '.($USER_PARAMS['USER_INI_PARAMS']['PREPARE_JOB']?'Y':'N')."\n";
	$STR.='# W_CPD: '.($USER_PARAMS['USER_INI_PARAMS']['W_CPD']?'Y':'N')."\n";
	$STR.='# FTP Path: '.$USER_PARAMS['FTP_PATH']."\n";
	$STR.="\n\n";
	$STR.="To finalize the setup, please follow the instructions below\n";
	
	$STR.="1) Copy the directory ".$UC_DT." to SCRIPT:\n";
	$STR.="		cp -r ".$UC_DT." ".$TG_DIR."/BACKEND/SCRIPT/\n";
	$STR.="2) Copy the files in SHELL directory to BACKEND/SCRIPT/SHELL\n";
	$STR.="     cp SHELL/* ".$TG_DIR."/BACKEND/SCRIPT/SHELL/\n";
	$STR.="3) Copy the files in CONTAINER_SHELL directory to BACKEND/CONTAINER_SHELL\n";
	$STR.="     cp SHELL/* ".$TG_DIR."/BACKEND/CONTAINER_SHELL/\n";
	
	fputs($fp,$STR);




	$PRIVATE_PREFIX='';
	if ($USER_PARAMS['USER_INI_PARAMS']['PRIVATE'])$PRIVATE_PREFIX='PRIVATE_SCRIPT/';
	fputs($fp,"\n\n\n4) Please add at the end of ".$PRIVATE_PREFIX."CONFIG_JOB the following lines");
	fputs($fp,"\n######################### COPY FROM THE LINE BELOW ########################\n\n# ".$USER_PARAMS['DATA_SOURCE']."\n");

	$STR_USER="\n\n\n5) Please add at the end of ".$PRIVATE_PREFIX."CONFIG_USER the following lines
#Important note: All scripts are disabled (F) by default. When you are developing each script, switch F to T when ready
######################### COPY FROM THE LINE BELOW ########################

# ".$USER_PARAMS['DATA_SOURCE']."\n\n";

	
	/// We write the jobs lines for the CONFIG_JOB file
	foreach ($USER_PARAMS['JOBS'] as $F )
	{
		//print_R($F);
		$STR="SC\t".$F['ID']."\t".$F['NAME']."\t\t\t\t\t";
		$T=implode("|",$F['REQUIRE']);  	if ($T=='')$T='-1';		$STR.=$T."\t\t\t\t\t\t\t";
		$T=implode("|",$F['REQ_TRIGGER']);  if ($T=='')$T='-1';		$STR.=$T."\t\t";
		$T=implode("|",$F['REQ_UPDATED']);  	if ($T=='')$T='-1';		$STR.=$T."\t\t";
		$STR.=$F['DIR']."\t\t".$F['REQ_RULE']."\t".$F['DEV_JOB']."\t".$F['FREQ']."\t".$F['RUNTIME']."\t";
		$T=implode("|",$F['CONCURRENT']);  	if ($T=='')$T='/';		$STR.=$T."\t\t\t\t\t\t";
		$STR.=$F['MEM']."\t";
		$STR.=$F['DESC']."\n";
		fputs($fp,$STR);
		
		$STR_USER.="JOB\t".$F['NAME']."\tF\n";

	}

	$N_TASK=5;

	if ($USER_PARAMS['USER_INI_PARAMS']['LOGIN'])
	{
		++$N_TASK;
		$STR_USER.="######################### COPY UP TO THE LINE ABOVE ########################\n";
		$STR_USER.="\n\n\n".$N_TASK.") Please add at the end of Global section of CONFIG_USER the following lines\n";
		$STR_USER.="######################### COPY FROM THE LINE BELOW ########################\n";
		$STR_USER.="GLOB\t".$UC_DT."_LOGIN\tN/A\n";
	}



	fputs($fp,"######################### COPY UP TO THE LINE ABOVE ########################\n");
	fputs($fp,$STR_USER."######################### COPY UP TO THE LINE ABOVE ########################\n");


	/// Now if the current jobs have been changed, we need to add a note to the user
	
	$STR='';
	foreach ($GLB_TREE as $ID=>&$F)
	{
		
		if (!isset($F['CHANGED'])) continue;
		if ($F['IS_PRIVATE'])continue;
		$STR.="SC\t".$ID."\t".$F['NAME']."\t\t\t\t\t";
		$T=implode("|",$F['REQUIRE']);  	if ($T=='')$T='-1';		$STR.=$T."\t\t\t\t\t\t\t";
		$T=implode("|",$F['REQ_TRIGGER']);  if ($T=='')$T='-1';		$STR.=$T."\t\t";
		$T=implode("|",$F['REQ_UPDATED']);  	if ($T=='')$T='-1';		$STR.=$T."\t\t";
		$STR.=$F['DIR']."\t\t".$F['REQ_RULE']."\t".$F['DEV_JOB']."\t".$F['FREQ']."\t".$F['RUNTIME']."\t";
		$T=implode("|",$F['CONCURRENT']);  	if ($T=='')$T='/';		$STR.=$T."\t\t\t\t\t\t";
		$STR.=$F['MEM']."\t";
		$STR.=$F['DESC']."\n";
		
	}
	if ($STR!='')	
	{
		++$N_TASK;
		fputs($fp,"\n\n\n\n\n".$N_TASK.") Please REPLACE in CONFIG_JOB the lines for the following jobs:\n".$STR);
	}

	
	$STR='';
	foreach ($GLB_TREE as $ID=>&$F)
	{
		if (!isset($F['CHANGED'])) continue;
		if (!$F['IS_PRIVATE'])continue;
		$STR.="SC\t".$ID."\t".$F['NAME']."\t\t\t\t\t";
		$T=implode("|",$F['REQUIRE']);  	if ($T=='')$T='-1';		$STR.=$T."\t\t\t\t\t\t\t";
		$T=implode("|",$F['REQ_TRIGGER']);  if ($T=='')$T='-1';		$STR.=$T."\t\t";
		$T=implode("|",$F['REQ_UPDATED']);  	if ($T=='')$T='-1';		$STR.=$T."\t\t";
		$STR.=$F['DIR']."\t\t".$F['REQ_RULE']."\t".$F['DEV_JOB']."\t".$F['FREQ']."\t".$F['RUNTIME']."\t";
		$T=implode("|",$F['CONCURRENT']);  	if ($T=='')$T='/';		$STR.=$T."\t\t\t\t\t\t";
		$STR.=$F['MEM']."\t";
		$STR.=$F['DESC']."\n";
		
	}
	if ($STR!='')
	{
		$N_TASK++;
		fputs($fp,"\n\n\n\n\n".$N_TASK.") Please REPLACE in PRIVATE_SCRIPT/CONFIG_JOB the lines for the following jobs:\n");
		fputs($fp,$STR);
	}



	if ($USER_PARAMS['FTP_PATH']!='')
	{
		++$N_TASK;
		fputs($fp,"\n\n\n\n\n".$N_TASK.") Please add at the end of ".$PRIVATE_PREFIX."CONFIG_GLOBAL the following lines");
		fputs($fp,"\n######################### COPY FROM THE LINE BELOW ########################\n");
		fputs($fp,"LINK\tFTP_".$UC_DT."\t".$USER_PARAMS['FTP_PATH']."\n");
		fputs($fp,"######################### COPY UP TO THE LINE ABOVE ########################\n");
	}


	fclose($fp);

	/// Create the SHELL directory and the SHELL script
 
	mkdir('SHELL');
	chdir('SHELL');

	foreach ($USER_PARAMS['FILES'] as $F=>$T)
	{
	$STR='#!/bin/sh
source $TG_DIR/BACKEND/SCRIPT/SHELL/setenv.sh'."\n";
		if ($F=='rmj')$STR.='php $TG_DIR/BACKEND/SCRIPT/'.$UC_DT.'/'.$USER_PARAMS['JOBS'][$F]['NAME'].".php\n";
 		else 		  $STR.=($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'python').' $TG_DIR/BACKEND/SCRIPT/'.$UC_DT.'/'.$USER_PARAMS['JOBS'][$F]['NAME'].'.'.($USER_PARAMS['USER_INI_PARAMS']['PHP']?'php':'py')."\n";

		$fp=fopen($USER_PARAMS['JOBS'][$F]['NAME'].'.sh','w');
		fputs($fp,$STR);
		fclose($fp);	
		chmod($USER_PARAMS['JOBS'][$F]['NAME'].'.sh',0755);
	}

	chdir('..');

	/// Create the CONTAINER_SHELL directory and the CONTAINER_SHELL script

	mkdir('CONTAINER_SHELL');
	chdir('CONTAINER_SHELL');


	$SING_COMMAND='biorels_exe ';


	foreach ($USER_PARAMS['FILES'] as $F=>$T)
	{
		$file=$USER_PARAMS['JOBS'][$F]['NAME'].'.sh';
		$fpO=fopen($file,'w');
		$fp=fopen('../SHELL/'.$file,'r');

		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000,"\n");
			if ($line=='')continue;
			if (substr($line,0,1)=='#'|| strpos($line,'source')!==false)fputs($fpO,$line."\n");
			else 
			{
				$path=$TG_DIR.substr($line,strpos($line,'/'));
				
				fputs($fpO,$SING_COMMAND.$line."\n");
			}

		}
		fclose($fp);
		fclose($fpO);
	
	}

}


?>