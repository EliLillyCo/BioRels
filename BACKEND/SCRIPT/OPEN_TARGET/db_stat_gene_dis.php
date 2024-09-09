<?php
ini_set('memory_limit','3000M');
/**
 SCRIPT NAME: db_stat_gene_dis
 PURPOSE:     Process pubmed data
 
*/
$JOB_NAME='db_stat_gene_dis';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];

addLog("Create directory");

$DL_INFO=$GLB_TREE[getJobIDByName('db_ot_evidence')];
$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_INFO['TIME']['DEV_DIR'];	

if (!is_dir($W_DIR)) 															failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');

$PROCESS_CONTROL['DIR']=$DL_INFO['TIME']['DEV_DIR'];
if (!chdir($W_DIR)) 															failProcess($JOB_ID."002",'Unable to access process dir '.$W_DIR);



addLog("Static file check");

	$fp=fopen("GENE_ACCELERATION.csv",'w');if (!$fp)							failProcess($JOB_ID."003",'Unable to open GENE_ACCELERATION.csv');
	
	$curr_year = date("Y");
	$curr_month = date("m");
addLog("Getting genes")	;
	$res=array();
	$res=runQuery("SELECT DISTINCT GN_ENTRY_ID FROM MV_GENE_SP WHERE TAX_ID='9606' ORDER BY GN_ENTRY_ID ASC");
	if ($res===false) 															failProcess($JOB_ID."004",'Unable to run MV_GENE_SP query');
	
	
	$LIST_GENES=array();
	foreach ($res as $l)$LIST_GENES[]=$l['gn_entry_id'];
$N_LINE=0;
	$CHUNKS=array_chunk($LIST_GENES,10);
	foreach ($CHUNKS as $CHUNK)
	{
		addLog("Processing")	;		
		$query='select EXTRACT(YEAR FROM PUBLICATION_DATE) as Y,EXTRACT(MONTH FROM PUBLICATION_DATE) as M, COUNT(*) as CO,GN_ENTRY_ID,DISEASE_ENTRY_ID
		FROM PMID_ENTRY PE, PMID_DISEASE_GENE P
		WHERE PE.PMID_ENTRY_ID = P.PMID_ENTRY_ID AND GN_ENTRY_ID IN ('.implode(',',$CHUNK).') AND 
		PUBLICATION_DATE >= NOW()- interval \'60 month\'
		GROUP BY EXTRACT(YEAR FROM PUBLICATION_DATE),EXTRACT(MONTH FROM PUBLICATION_DATE),GN_ENTRY_ID,DISEASE_ENTRY_ID
		ORDER BY DISEASE_ENTRY_ID ASC, Y ASC';
			echo $query."\n";
			$res=array();
			$res=runQuery($query);
			if ($res===false) 														failProcess($JOB_ID."005",'Unable to run PMID_JOURNAL query');
			
			$STAT=array();
			foreach ($res as $tab)$STAT[$tab['gn_entry_id']][$tab['disease_entry_id']][$tab['y']][$tab['m']]=$tab['co'];
			

			foreach ($STAT as $GN_ENTRY_ID=>&$DIS_GROUP)
			foreach ($DIS_GROUP as $DISEASE_ENTRY_ID=>&$YEARS)
			{
					krsort($YEARS);$Y_ST=array();
					$SUM12=0;
					$N_MONTH=-1;
					$COL_T=array(30=>array(0=>0,1=>0,2=>0,3=>0,4=>0),60=>array(0=>0,1=>0,2=>0,3=>0,4=>0));
					$SUM_ALL=0;
					for ($Y=$curr_year;$Y>=$curr_year-50;--$Y)
					{
						
						for ($M=($Y==$curr_year)?$curr_month:12;$M>=1;--$M)if (isset($YEARS[$Y][$M]))$SUM_ALL+=$YEARS[$Y][$M];
					}
					//print_r($YEARS);
					for ($Y=$curr_year;$Y>=$curr_year-50;--$Y)
					{
						$Y_ST[$Y]=0;
						for ($M=($Y==$curr_year)?$curr_month:12;$M>=1;--$M)
						{
								++$N_MONTH;
								//echo $GN_ENTRY_ID."\t".$Y." ".$M."\t".$N_MONTH."\t";
								
								if (isset($YEARS[$Y][$M]))
								{
									if ($N_MONTH<=12)$SUM12+=$YEARS[$Y][$M];
									//echo $Y."\t".$M."\t".$YEARS[$Y][$M]."\t".$N_MONTH."\t";
									foreach ($COL_T as $STEP=>&$STEP_V)
									{
										$VAL=floor($N_MONTH/$STEP);
										
										if (!isset($STEP_V[$VAL]))$STEP_V[$VAL]=$YEARS[$Y][$M];
										else $STEP_V[$VAL]+=$YEARS[$Y][$M];
										//echo $STEP.':'.$VAL."=".$YEARS[$Y][$M]."\t";
									}
									//echo "\n";
									$Y_ST[$Y]+=$YEARS[$Y][$M];
									
									
								}//else echo "\t";
								//echo "\n";
								//if ($N_MONTH==180)break;
						}
						//if ($N_MONTH==180)break;
					}
					/*foreach ($COL_T as $STEP=>&$STEP_V)
					{
						foreach ($STEP_V as $STEP_K=>$COUNT)
						{
							echo $STEP."\t".$GN_ENTRY_ID."\t".date("F Y", strtotime("-".$STEP_K*$STEP." months"))."\t".
							date("F Y", strtotime("-".($STEP_K+1)*$STEP." months"))."\t".
							$COUNT."\n";
						}
					}*/
					
					$VA1=round(($COL_T[30][0]-$COL_T[30][1])/30,5);
					$VA2=round(($COL_T[30][1]-$COL_T[30][2])/30,5);
					$A=round(($VA1-$VA2)/30,4);
					$VL1=round(($COL_T[60][0]-$COL_T[60][1])/60,5);
					$VL2=round(($COL_T[60][1]-$COL_T[60][2])/60,5);
					$AL=round(($VL1-$VL2)/60,4);
					echo $GN_ENTRY_ID."\t".$DISEASE_ENTRY_ID."\t".$VA1.";".$VA2."=>".$A."\t".$VL1.";".$VL2."=>".$AL."\n";
					//exit;
					++$N_LINE;
					fputs($fp,$N_LINE."\t".$GN_ENTRY_ID."\t".$DISEASE_ENTRY_ID."\t".$SUM_ALL."\t".$SUM12."\t".$VA1."\t".$A."\t".$VL1."\t".$AL."\n");
			}
			

	}
	fclose($fp);


  
       


if (!runQueryNoRes('TRUNCATE TABLE disease_gene_acc'))				failProcess($JOB_ID."006",'Unable to truncate disease_gene_acc');
$res=array();


$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.diseasE_gene_acc  (disease_gene_acc_id,gn_entry_id,disease_entry_id   ,all_count   ,year_count  ,speed30,accel30,speed60,accel60 ) FROM \''."GENE_ACCELERATION.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."007",'Unable to insert disease_hierarchy'); 

		   
successProcess();


	

?>
