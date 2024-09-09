<?php

if (!isset($TG_DIR))die();

function loadProcess()
{
	global $TG_DIR;
	global $GLB_TREE;
	global $GLB_TIME;
	global $GLB_VAR;

	$PR_FILE=$TG_DIR.'/BACKEND/SCRIPT/CONFIG/CONFIG_GLOBAL';
	///Check file existence:
	if (!is_file($PR_FILE))sendKillMail('A00001','No CONFIG_GLOBAL file at '.$PR_FILE);
	loadGlobalFile($PR_FILE,false);
	$PR_FILE=$TG_DIR.'/BACKEND/PRIVATE_SCRIPT/CONFIG_GLOBAL';
	
	///Check file existence:
	if (is_file($PR_FILE))loadGlobalFile($PR_FILE,true);

	$PR_FILE=$TG_DIR.'/BACKEND/SCRIPT/CONFIG/CONFIG_JOB';
	///Check file existence:
	if (!is_file($PR_FILE))sendKillMail('A00002','No CONFIG_JOB file at '.$PR_FILE);
	loadConfigFile($PR_FILE,false);
    $DB_SCHEMA=getenv('DB_SCHEMA');          if ($DB_SCHEMA!==false) $GLB_VAR['PUBLIC_SCHEMA']=$DB_SCHEMA;

	
	$PR_FILE=$TG_DIR.'/BACKEND/SCRIPT/CONFIG/CONFIG_USER';
	///Check file existence:
	if (!is_file($PR_FILE))sendKillMail('A00003','No CONFIG_USER file at '.$PR_FILE);
	loadConfigUser($PR_FILE);

	foreach ($GLB_TREE as $ID=>&$INFO)
	if ($INFO['ENABLED']=='') sendKillMail('A00004','Missing Y/N if '.$INFO['NAME'].' is enabled in CONFIG_USER');

	if ($GLB_VAR['PRIVATE_ENABLED']=='T')
	{
        $PRIVATE_SCHEMA=getenv('SCHEMA_PRIVATE');if ($PRIVATE_SCHEMA!==false)$GLB_VAR['SCHEMA_PRIVATE']=$PRIVATE_SCHEMA;
		if ($GLB_VAR['SCHEMA_PRIVATE']===false)sendKillMail('A00005','SCHEM_PRIVATE is not set in setenv.sh but PRIVATE_ENABLED set to T(rue) in CONFIG_GLOBAL ');
		if (!isset($GLB_VAR['SCHEMA_PRIVATE']))sendKillMail('A00006','No SCHEMA_PRIVATE defined ');
		if (is_dir($TG_DIR.'/BACKEND/PRIVATE_SCRIPT') && is_file($TG_DIR.'/BACKEND/PRIVATE_SCRIPT/CONFIG_JOB'))
				loadConfigFile($TG_DIR.'/BACKEND/PRIVATE_SCRIPT/CONFIG_JOB',true);
		if (is_dir($TG_DIR.'/BACKEND/PRIVATE_SCRIPT') && is_file($TG_DIR.'/BACKEND/PRIVATE_SCRIPT/CONFIG_USER'))
				loadConfigUser($TG_DIR.'/BACKEND/PRIVATE_SCRIPT/CONFIG_USER');
	}

}


function loadConfigUser($PR_FILE)
{
	global $GLB_VAR;
	global $GLB_TREE;
	$fp=fopen($PR_FILE,'r');
	if (!$fp)sendKillMail('A00007','Unable to open file '.$PR_FILE);

	$MAP_T=array('Ensembl'=>3,'RefSeq'=>4,'Uniprot'=>5,'Transcriptome'=>6);
$MAP=array();


$ORG_REL_OPTS=array(
	1=>'Tax_Id',
	2=>'Source',
	3=>'Assembly_Acc',
	4=>'Assembly_name',
	5=>'Gene_build',
	6=>'organism_name',
	7=>'version_status',
	8=>'release_type',
	9=>'refseq_category',
	10=>'annotation_date',
	11=>'group');
	
	$PROTEOME_RULES=array(
		    #(1) Number of entries in main fasta (canonical)
    #(2) Number of entries in additional fasta (isoforms)
    #(3) Number of entries in gene2acc mapping file
    
1=>'Proteome_ID',
2=>'Tax_Id',
3=>'OSCODE',
4=>'SUPERREGNUM',
5=>'N_Fasta_Canonical',
6=>'N_Fasta_Isoform',
7=>'Gene2Acc',
8=>'Species Name',
9=>'Tax_Id2'
	);

foreach ($GLB_TREE as $ID=>&$INFO)$MAP[$INFO['NAME']]=$ID;

	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if (substr($line,0,1)=="#"||$line=="")continue;
		/// Break the line by tabulation and remove column with null values:
		$tab=array_values(array_filter(explode("\t",$line)));

		if ($tab[0]=="GLOB")$GLB_VAR[$tab[1]]=$tab[2];
		else if ($tab[0]=='JOB')
		{
			if (count($tab)!=3) sendKillMail('A00008','Number of columns must be 3 for JOB definition in CONFIG_USER: '.$line);
			$NAME=$tab[1];
			if (!isset($MAP[$NAME])) sendKillMail('A00009','Unable to find '.$NAME.' in CONFIG_JOB as defined by CONFIG_USER');
			$GLB_TREE[$MAP[$NAME]]['ENABLED']=$tab[2];
		}
		else if ($tab[0]=='GENOME')
		{
			if (count($tab)!=count($ORG_REL_OPTS)+1) sendKillMail('A00010','Number of columns must be '.(count($ORG_REL_OPTS)+1).' for GENOME definition in CONFIG_USER: '.$line);
			if (!is_numeric($tab[1]))sendKillMail('A00011','Column 2 must be numeric (NCBI taxonomic Identifier) '.$tab[1].' for line: '.$line);
			$T=array();
			foreach ($ORG_REL_OPTS as $K=>$V)$T[$V]=$tab[$K];
			$GLB_VAR['GENOME'][$T['Tax_Id']][]=$T;
		}
		else if ($tab[0]=='PROTEOME')
		{
			if (count($tab)!=count($PROTEOME_RULES)+1) sendKillMail('A00010','Number of columns must be '.(count($PROTEOME_RULES)+1).' for PROTEOME_RULES definition in CONFIG_USER: '.$line);
			if (!is_numeric($tab[2]))sendKillMail('A00011','Column 2 must be numeric (NCBI taxonomic Identifier) '.$tab[1].' for line: '.$line);
			$T=array();
			foreach ($PROTEOME_RULES as $K=>$V)$T[$V]=$tab[$K];
			$GLB_VAR['PROTEOME'][$T['Tax_Id']][]=$T;
		}
	}
	
	fclose($fp);
}


function loadGlobalFile($PR_FILE,$IS_PRIVATE)
{
	global $GLB_VAR;
	$fp=fopen($PR_FILE,'r');
	if (!$fp)sendKillMail('A00013','Unable to open file '.$PR_FILE);
	
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if (substr($line,0,1)=="#"||$line=="")continue;
		/// Break the line by tabulation and remove column with null values:
		$tab=array_values(array_filter(explode("\t",$line)));


		if ($tab[0]=="GLOB")$GLB_VAR[$tab[1]]=$tab[2];
		else if ($tab[0]=="REGEX")
		{
			if (count($tab)!=3)sendKillMail('A00030','Wrong columns count for '.$line);
			$GLB_VAR['REGEX'][$tab[1]][]=$tab[2];
		}
		else if ($tab[0]=="LINK")
		{
			if (count($tab)!=3)sendKillMail('A00014','Wrong columns count for '.$line);
			$GLB_VAR['LINK'][$tab[1]]=$tab[2];
		}else if ($tab[0]=="TOOL")
		{
			if (count($tab)!=3)sendKillMail('A00015','Wrong columns count for '.$line);
			$GLB_VAR['TOOL'][$tab[1]]=$tab[2];
		}
	}
	fclose($fp);
	
}

function loadConfigFile($PR_FILE,$IS_PRIVATE)
{
	global $GLB_TREE;
	$fp=fopen($PR_FILE,'r');
	if (!$fp)sendKillMail('A00016','Unable to open file '.$PR_FILE);
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		
		if (substr($line,0,1)=="#"||$line=="")continue;
		$tab=array_values(array_filter(explode("\t",$line)));
	
		 if ($tab[0]=='SC')
		{

			/// Each ID should be unique:
			if (isset($GLB_TREE[$tab[1]]))	 	sendKillMail('A00017','JOB ID '.$tab[1].' Already exists for line '.$line.' '.var_export($GLB_TREE[$tab[1]]));
			if (count($tab)!=14)		 	sendKillMail('A00018',$line.' only has '.count($tab).' columns');
			if ($tab[7]!='C' && $tab[7]!='A' && $tab[7]!='D')	sendKillMail('A00018',$line.' has wrong REQ_RULE value (either A or C or D)');
			if ($tab[8]!='D' && $tab[8]!='P')	sendKillMail('A00019',$line.' has wrong DEV_JOB value (either P or D)');
			if ($tab[10]!='R' && $tab[10]!='S')	sendKillMail('A00020',$line.' has wrong Runtime value (either R or S)');
			if (!is_numeric($tab[9]) && $tab[9]!='P' &&$tab[9][0]!='W'&&$tab[9][0]!='D'&&$tab[9][0]!='M'&&$tab[9][0]!='H')
			{	
				
				$time=explode(":",$tab[9]);
				if (count($time)!=2 || $time[0]<0|| $time[0]>23|| $time[1]<0 ||$time[1]>59)
								sendKillMail('A00021','Unrecognized value for frequency '.$tab[9].' in line '.$line);
			}
			if (preg_match('/((([0-9]{1,5}\|{0,1}){1,50})|-1)/',$tab[11])===false)sendKillMail('A00021',$line.' has wrong concurrent rules');
			$REQUIRED=array();if ($tab[3]!=-1)$REQUIRED=explode("|",$tab[3]);
			$REQ_TRIGGER=array();if ($tab[4]!=-1)$REQ_TRIGGER=explode("|",$tab[4]);
			$REQ_UPDATED=array();if ($tab[5]!=-1)$REQ_UPDATED=explode("|",$tab[5]);
			
		 	$GLB_TREE[$tab[1]]=array('NAME'=>$tab[2],
							 'REQUIRE'=>$REQUIRED,
							 'REQ_TRIGGER'=>$REQ_TRIGGER,
							 'REQ_UPDATED'=>$REQ_UPDATED,
						 'DIR'=>$tab[6],
						 'REQ_RULE'=>$tab[7],
						 'DEV_JOB'=>($tab[8]=='D'),
						 'FREQ'=>$tab[9],
						 'ENABLED'=>'',
						 'RUNTIME'=>$tab[10],
						 'CONCURRENT'=>($tab[11]!='-1'?explode("|",$tab[11]):array()),
						 'MEM'=>$tab[12],
						 'DESC'=>$tab[13],
						 'FAILED'=>0,
						'IS_PRIVATE'=>$IS_PRIVATE);
		
		}
	}





	fclose($fp);
}

function checkTree()
{
	global $TG_DIR;
	global $GLB_TREE;
	global $GLB_VAR;
	
	foreach ($GLB_TREE as $ID=>$INFO)
	{
		foreach ($INFO['REQUIRE'] as $ID_R) 
		{
			if ($ID_R==-1)continue;///Root always exists
			if (isset($GLB_TREE[$ID_R]))continue;	
			echo $ID."\t";print_r($INFO['REQUIRE']);
			sendKillMail('A00022','JOB ID '.$ID_R.' does not exists for JOB ID '.$ID."\n".var_export($INFO));
				
		}
		if ($INFO['RUNTIME']!='S')continue;/// Only Script (S) should have a script.
		$FPATH='';
		if ($INFO['IS_PRIVATE'])$FPATH=$TG_DIR.'/BACKEND/PRIVATE_SCRIPT/SHELL/'.$INFO['NAME'].'.sh';
		else $FPATH=$TG_DIR.$GLB_VAR['SCRIPT_DIR'].'/SHELL/'.$INFO['NAME'].'.sh';
		if (!is_file($FPATH))sendKillMail('A00023','JOB ID '.$ID." shell script does not exist ".$FPATH."\n".var_export($INFO));
	}
}


function genHierarchy()
{
global $GLB_TREE;
global $GLB_TREE_LEVEL;
	$GLB_TREE_LEVEL=array(0=>array());
	$TREE_CHECK=array();
	foreach ($GLB_TREE as $ID=>$INFO)$TREE_CHECK[$ID]=-1;
	
	/// Start with job having root as requirements
	foreach ($GLB_TREE as $ID=>$INFO)
	{
		if (!(count($INFO['REQUIRE'])==1 && $INFO['REQUIRE'][0]==-1))continue;
		$GLB_TREE_LEVEL[0][]=$ID;
		$TREE_CHECK[$ID]=0;
		
	}
	/// When we look at all other jobs.
	/// If all their requirements have been done on previous levels, then we add them in the level
	/// Until all jobs are processed.
	$LEV=1;
	do
	{
		$GLB_TREE_LEVEL[$LEV]=array();
		foreach ($GLB_TREE as $ID=>$INFO)
		{
			if ($TREE_CHECK[$ID]!=-1)continue;
			$ALL_GOOD=true;
			foreach ($INFO['REQUIRE'] as $V) if ($TREE_CHECK[$V]==-1||$TREE_CHECK[$V]==$LEV)$ALL_GOOD=false;
			if (!$ALL_GOOD)continue;
			
			$GLB_TREE_LEVEL[$LEV][]=$ID;
			$TREE_CHECK[$ID]=$LEV;
		}

	++$LEV;
	if ($LEV>50){print_r($GLB_TREE_LEVEL);sendKillMail('A00024','Tree level maxed out');}
	}while(array_search(-1,$TREE_CHECK)!==false);
	$GLB_TREE_LEVEL=array_filter($GLB_TREE_LEVEL);
}
$time=microtime_float();
loadProcess();


if (defined('MONITOR_JOB'))
{
	checkTree();


genHierarchy();
}
?>