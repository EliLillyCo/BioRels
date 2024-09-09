<?php
ini_set('memory_limit','3000M');

/**
 SCRIPT NAME: db_stat_pubmed_gene
 PURPOSE:    Compute statistics on gene publications
 
*/
$JOB_NAME='db_stat_pubmed_gene';

/// Get root directories
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

/// Get job id
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
/// Get job info
$JOB_INFO=$GLB_TREE[$JOB_ID];


addLog("Setting up");
	/// Get parent job information
	$DL_PUBMED_INFO=$GLB_TREE[getJobIDByName('dl_pubmed')];

	/// Set up directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_PUBMED_INFO['TIME']['DEV_DIR'];	

	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$DL_PUBMED_INFO['TIME']['DEV_DIR'];



	$fp=fopen("GENE_ACCELERATION.csv",'w');if (!$fp)							failProcess($JOB_ID."003",'Unable to open GENE_ACCELERATION.csv');
	
	$curr_year = date("Y");
	$curr_month = date("m");



addLog("Getting genes")	;
	
$res=array();
	$res=runQuery("SELECT DISTINCT gn_entry_id FROM mv_gene_sp WHERE tax_id='9606' ORDER BY gn_entry_id ASC");
	if ($res===false) 														failProcess($JOB_ID."004",'Unable to get gene from mv_gene_sp query')	;

	$LIST_GENES=array();
	foreach ($res as $l)
	{
		$LIST_GENES[]=$l['gn_entry_id'];
	}

	/// Now processing the list of genes in chunks of 10
	$CHUNKS=array_chunk($LIST_GENES,10);
	foreach ($CHUNKS as $CHUNK)
	{
		addLog("Processing")	;	
		/// Getting the number of publications withing the last 15 years, grouped by year and month	
		$query='SELECT EXTRACT(YEAR FROM publication_date) as Y,EXTRACT(MONTH FROM publication_date) as M, COUNT(*) as co,gn_entry_id
			FROM pmid_entry PE, pmid_gene_map P
			WHERE PE.pmid_entry_id = P.pmid_entry_id AND gn_entry_id IN ('.implode(',',$CHUNK).") AND 
			publication_date >= CURRENT_DATE - INTERVAL '15 years'
			GROUP BY EXTRACT(YEAR FROM publication_date),EXTRACT(MONTH FROM publication_date),gn_entry_id";
		echo $query."\n";
		$res=array();
		$res=runQuery($query);
		if ($res===false) 														failProcess($JOB_ID."005",'Unable to extract statistics');
		
		$STAT=array();
		foreach ($res as $tab)
		{
			$STAT[$tab['gn_entry_id']][$tab['y']][$tab['m']]=$tab['co'];
		}
		
		/// Now for each gene, we will compute the acceleration
		foreach ($STAT as $gn_entry_id=>&$YEARS)
		{
			/// By sort by descending year
			krsort($YEARS);
			$Y_ST=array();
			$SUM12=0;
			$N_MONTH=-1;
			$COL_T=array(30=>array(0=>0,1=>0,2=>0,3=>0,4=>0),60=>array(0=>0,1=>0,2=>0,3=>0,4=>0));
			$SUM_ALL=0;

			for ($Y=$curr_year;$Y>=$curr_year-50;--$Y)
			{
				/// Depending on the year, we will start from the current month (if it's this year) or from January
				$start_month=($Y==$curr_year)?$curr_month:12;
				for ($M=$start_month;$M>=1;--$M)if (isset($YEARS[$Y][$M]))$SUM_ALL+=$YEARS[$Y][$M];
			}
			//print_r($YEARS);
			for ($Y=$curr_year;$Y>=$curr_year-50;--$Y)
			{
				$Y_ST[$Y]=0;
				/// Depending on the year, we will start from the current month (if it's this year) or from January
				$curr_month=($Y==$curr_year)?$curr_month:12;

				/// We will then go through the months and compute the acceleration
				for ($M=$curr_month;$M>=1;--$M)
				{
					++$N_MONTH;
					
					if (!isset($YEARS[$Y][$M]))continue;
					
					if ($N_MONTH<=12)$SUM12+=$YEARS[$Y][$M];
					
					foreach ($COL_T as $STEP=>&$STEP_V)
					{
						$VAL=floor($N_MONTH/$STEP);
						
						if (!isset($STEP_V[$VAL]))$STEP_V[$VAL]=$YEARS[$Y][$M];
						else $STEP_V[$VAL]+=$YEARS[$Y][$M];
						//echo $STEP.':'.$VAL."=".$YEARS[$Y][$M]."\t";
					}
					//echo "\n";
					$Y_ST[$Y]+=$YEARS[$Y][$M];		
				}
				
			}
			
			/// Compute speed
			$VA1=round(($COL_T[30][0]-$COL_T[30][1])/30,5);
			$VA2=round(($COL_T[30][1]-$COL_T[30][2])/30,5);
			/// Compute acceleration
			$A=round(($VA1-$VA2)/30,4);

			/// Compute speed for the last 60 months
			$VL1=round(($COL_T[60][0]-$COL_T[60][1])/60,5);
			$VL2=round(($COL_T[60][1]-$COL_T[60][2])/60,5);
			/// Compute acceleration for the last 60 months
			$AL=round(($VL1-$VL2)/60,4);
			echo $gn_entry_id."\t".$VA1.";".$VA2."=>".$A."\t".$VL1.";".$VL2."=>".$AL."\n";
			//exit;
			fputs($fp,$gn_entry_id."\t".$SUM_ALL."\t".$SUM12."\t".$VA1."\t".$A."\t".$VL1."\t".$AL."\n");
		}
	}
	fclose($fp);
try{
	$DB_CONN->beginTransaction();

	  if (!runQueryNoRes('TRUNCATE TABLE pmid_gene_stat')) 														failProcess($JOB_ID."006",'Unable to truncate PMID_gene_stat');

	addLog("Load data in table");
	
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.pmid_gene_stat(gn_entry_id,all_count,year_count,speed30,accel30,speed60,accel60) FROM \''."GENE_ACCELERATION.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	
	if ($return_code !=0 )throw PDOException("Unable to insert in pmid_gene_stat");
		
	$DB_CONN->commit();
	}catch (PDOException $e)
	{
		$DB_CONN->rollBack();
		failProcess($JOB_ID."007",'Unable to insert in PMID_gene_stat');
	}
				   
successProcess();


	

?>
