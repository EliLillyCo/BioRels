<?php

ini_set('memory_limit','2000M');
/**
 
 PURPOSE:     Get and update list of internal assays
 
*/
$MD5_HASH=$argv[1];
error_reporting(E_ALL);
$JOB_NAME='web_job';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

$JOB_ID=getJobIDByName($JOB_NAME);
$JOB_INFO=$GLB_TREE[$JOB_ID];	

$RAW_INFO=runQuery("SELECT * FROM web_job where md5id = '".$MD5_HASH."'");
if ($RAW_INFO==array())exit;
cleanWebJobDoc($MD5_HASH);
date_default_timezone_set($GLB_VAR['TIMEZONE']);
$STATUS_INFO=array('STATUS'=>'Running',
					'LOG'=>array());
updateWebJobStatus($MD5_HASH,'Initiate sequence search');


updateWebJobStatus($MD5_HASH,'Verifying input parameters');

$INPUT_DATA=json_decode($RAW_INFO[0]['params'],true);
if ($INPUT_DATA==null)    failedWebJob($MD5_HASH,'Unable to interpret parameters');

if (!isset($INPUT_DATA['SEQUENCE']))	failedWebJob($MD5_HASH,'No sequence information found');
if (!isset($INPUT_DATA['SEQUENCE_NAME']))	failedWebJob($MD5_HASH,'No sequence name information found');
if (!isset($INPUT_DATA['ORGANISM']))	failedWebJob($MD5_HASH,'No organism provided');
if (!isset($INPUT_DATA['TOOL']))	failedWebJob($MD5_HASH,'No tool provided');
if (!isset($INPUT_DATA['REGION']))	failedWebJob($MD5_HASH,'No region provided');//
//$INPUT_DATA['REGION']=array('premRNA');


//-word_size 4
$TOOL_MAP=array('Blastn'=>$GLB_VAR['TOOL']['BLASTN'],
'Bowtie'=>$GLB_VAR['TOOL']['BOWTIE'],
'Bowtie2'=>$GLB_VAR['TOOL']['BOWTIE2']);



foreach ($INPUT_DATA['TOOL'] as $tool)
{
    if (!is_executable($TOOL_MAP[$tool]))	failedWebJob($MD5_HASH,'Unable to find requested tool');
}

$W_DIR=$TG_DIR.'/'.$JOB_INFO['DIR'].'/'; if (!is_dir($W_DIR) && !mkdir($W_DIR))failedWebJob($MD5_HASH,'Unable to create web job directory');
$W_DIR.=$MD5_HASH;if (!is_dir($W_DIR) && !mkdir($W_DIR))failedWebJob($MD5_HASH,'Unable to create job directory');
if (!chdir($W_DIR))failedWebJob($MD5_HASH,'Unable to access job directory');

$fp=fopen('input_seq','w'); if (!$fp)failedWebJob($MD5_HASH,'Unable to open input file');
$query='>'.$INPUT_DATA['SEQUENCE_NAME']."\n".str_replace("\r","",$INPUT_DATA['SEQUENCE']);

$LEN_SEQ=strlen(str_replace("\n","",$INPUT_DATA['SEQUENCE']));
$t=array();
$OPT_BLAST='';

fputs($fp,$query);

fclose($fp);

$MAP_REGION=array("3'UTR"=>'3UTR',"5'UTR"=>'5UTR',"premRNA"=>'GENE_SEQ','promoter'=>'PROMOTER','transcriptome'=>'transcriptome','CDS'=>'CDS');

if ($LEN_SEQ<50)
{
$OPT_BLAST=' -evalue 1000  -word_size 4 ';
updateWebJobStatus($MD5_HASH,'Detected sequence shorter than 50 nucleotides - switching evalue to 10 and word size to 4 for blastn - if requested');
}

$N_RUN=count($INPUT_DATA['ORGANISM'])*count($INPUT_DATA['TOOL'])*count($INPUT_DATA['REGION']);
updateWebJobStatus($MD5_HASH,$N_RUN.' calculation to perform');


$query="SELECT * FROM taxon where tax_id IN (";
foreach ($INPUT_DATA['ORGANISM'] as $T)$query.="'".$T."',";


$res=runQuery(substr($query,0,-1).')');
$MAP_TAX=array();foreach ($res as $l)$MAP_TAX[$l['tax_id']]=$l['scientific_name'];

$ALL_GOOD=true;
$N_J=0;
foreach ($INPUT_DATA['ORGANISM'] as $TAX_ID)
{
    
    $DATA=array();
foreach ($INPUT_DATA['REGION'] as $REGION)
{
    
    
    foreach ($INPUT_DATA['TOOL'] as $TOOL)
    {
        try{
        echo "\n\n\n\n###########START ".$TAX_ID.' ' .$REGION.' ' .$TOOL."\n";
        ++$N_J;
        updateWebJobStatus($MD5_HASH,$N_J.'/'.$N_RUN.' Running '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
        switch ($TOOL)
        {
            case 'Blastn':
                {
                $DIR=$TG_DIR.'/PRD_DATA/TRANSCRIPTOME/DATA/'.$TAX_ID.'/'.$MAP_REGION[$REGION].'_BLASTN/';
                echo $DIR."\n";
                if (!is_dir($DIR))
                {
                    updateWebJobStatus($MD5_HASH,'Unable to find data directory for '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
                    break;
                }
                $command=$TOOL_MAP['Blastn'].' -query input_seq -db '.$DIR.$TAX_ID.'_'.$MAP_REGION[$REGION].(($REGION=='promoter')?'':'_BLASTN').' -outfmt 13 '.$OPT_BLAST.' -html -out '.$TAX_ID.'_'.$MAP_REGION[$REGION].'_BLASTN ';
                echo $command;
                exec($command,$res,$return_code);
                if ($return_code!=0)
                {
                    updateWebJobStatus($MD5_HASH,'Blastn failed for '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
                    break;
                }
                
                    updateWebJobStatus($MD5_HASH,'Blastn successful for '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
                    
                    if (!is_file($TAX_ID.'_'.$MAP_REGION[$REGION].'_BLASTN_1.json'))
                    {
                        echo $TAX_ID.'_'.$MAP_REGION[$REGION].'_BLASTN_1.json not found\n';
                        updateWebJobStatus($MD5_HASH,'Unable to find result file for '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
                        break;
                    }
                  
                    $f=file_get_contents($TAX_ID.'_'.$MAP_REGION[$REGION].'_BLASTN_1.json');
  
                    $TMP=json_decode($f,true);
                    //if ($REGION=="3'UTR")print_r($TMP);
                    if($REGION=='premRNA'||$REGION=='promoter') loadpremRNABlast($TMP,$REGION,$TOOL,$DATA);
		            else loadBlast($TMP,$REGION,$TOOL,$DATA);
                    
                    if ($f!='') uploadWebJobDoc($MD5_HASH,'results_'.$MAP_TAX[$TAX_ID].'_'.$REGION.'_BLASTN.json','application/json',$f,'Blastn results against '.$MAP_TAX[$TAX_ID].' transcriptome - '.$REGION);
                
                
            }
            
            break;
            case 'Bowtie':
                {
                $DIR=$TG_DIR.'/PRD_DATA/TRANSCRIPTOME/DATA/'.$TAX_ID.'/'.$MAP_REGION[$REGION].'_BOWTIE/';
                if (!is_dir($DIR))
                {
                    updateWebJobStatus($MD5_HASH,'Unable to find data directory for '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
                    break;
                }
                $command=$TOOL_MAP['Bowtie'].'  -f -v 3 -e 1000000 -a '.(($REGION=='premrna'||$REGION=='promoter')?'':' --norc ').' -p 1  -k 10 '.$DIR.'BOWTIE_'.$MAP_REGION[$REGION].'_'.$TAX_ID.' input_seq BOWTIE_'.$MAP_REGION[$REGION].'_'.$TAX_ID.'_results ';
                echo $command."\n";
                exec($command,$res,$return_code);
                if ($return_code!=0)
                {
                    updateWebJobStatus($MD5_HASH,'BOWTIE failed for '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
                    break;
                }
                
                    updateWebJobStatus($MD5_HASH,'BOWTIE successful for '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
                    
                    if (!is_file('BOWTIE_'.$MAP_REGION[$REGION].'_'.$TAX_ID.'_results'))
                    {
                        echo 'BOWTIE_'.$MAP_REGION[$REGION].'_'.$TAX_ID.'_results not found\n';
                        updateWebJobStatus($MD5_HASH,'Unable to find result file for '.$TOOL.' on '.$REGION.' '.$MAP_TAX[$TAX_ID]);
                        break;
                    }
                    $f=file_get_contents('BOWTIE_'.$MAP_REGION[$REGION].'_'.$TAX_ID.'_results');

                    
                    $TMP=explode("\n",$f);
                    if($REGION=='premRNA'||$REGION=='promoter') loadpremRNABowtie($TMP,$REGION,$TOOL,$DATA);
		            else loadBowtie($TMP,$REGION,$TOOL,$DATA);
                    
                    if ($f!='')uploadWebJobDoc($MD5_HASH,'results_'.$MAP_TAX[$TAX_ID].'_'.$REGION.'_BOWTIE.json','application/json',$f,'BOWTIE results against '.$MAP_TAX[$TAX_ID].' transcriptome - '.$REGION);
                
            }
            break;
        }
       
    }catch(Exception $e)
    {
       // echo 'Error: '.$e->getMessage();exit;
        updateWebJobStatus($MD5_HASH,'Error: '.$e->getMessage());
        $ALL_GOOD=false;
    }
}
}
//$fp=fopen($TAX_ID.'_bundle.json','w');fputs($fp,json_encode($DATA)."\n");fclose($fp);
//print_r($DATA);
uploadWebJobDoc($MD5_HASH,'results_'.$MAP_TAX[$TAX_ID].'.json','application/json',json_encode($DATA),'Bundled results against '.$MAP_TAX[$TAX_ID]);

}

if ($ALL_GOOD)$STATUS_INFO['STATUS']='Success';
else $STATUS_INFO['STATUS']='Success - incomplete';
$STATUS_INFO['LOG'][]=array('Job successfully finished',date("F j, Y, g:i a"));


runQueryNoRes("Update web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."', time_end=CURRENT_TIMESTAMP WHERE md5id = '".$MD5_HASH."'");



function loadBowtie(&$CONTENT,$REGION,$TOOL,&$DATA)
{
	// echo '<pre>';
	// print_r($CONTENT);exit;

/*
Bowtie output is an alignment file in SAM format, where one line is one alignment.
 Each line is a collection of 8 fields separated by tabs.
  The fields are: 
  name of the aligned reads, 
  reference strand aligned to, 
  name of reference sequence where the alignment occurs,
   0-based offset into the forward reference strand where leftmost character of the alignment occurs,
    read sequence, read qualities, 
	the number of other instances where the same sequence is aligned against the same reference characters,
	 and comma-separated list of mismatch descriptors. */

	
					foreach ($CONTENT as $line)
					{
						if ($line=='')continue;
						$tab=explode("\t",$line);
                        
						
						$id=explode(";",$tab[2]);
						$tr=explode("=",$id[0])[1];
						$symbol=explode("=",$id[3])[1];
						$geneid=explode("=",$id[2])[1];
                        
                        $DATA[$geneid][$id[0]][$tab[3]+1][$REGION.'_'.$TOOL]=array($tab[4],$tab[5],$tab[6],$tab[7]);
					}
				
			
}
function loadBlast(&$CONTENT,$REGION,$TOOL,&$DATA)
{
	echo "LOAD ".$REGION.' '.$TOOL."\t".count($DATA);
	$STR='';
	foreach ($CONTENT['BlastOutput2']['report']['results'] as $T=>&$QUERY)
	{
		
		
		$HITS=&$QUERY['hits'];
		//echo '<pre>';print_r($HITS);exit;
		foreach ($HITS as &$HIT)
		{
			
			$TMP=explode(";",$HIT['description'][0]['title']);
            $ID=array();
            foreach ($TMP as $K)
            {
                $tab=explode("=",$K);
                $ID[$tab[0]]=$tab[1];
            }
			//echo $HIT['description'][0]['title']."\n";
            
			if ($ID['transcript'][0]=='>')$ID['transcript']=substr($ID['transcript'],1);
			


			$DATA[$ID['gid']][$ID['transcript']][$HIT['hsps'][0]['hit_from']][$REGION.'_'.$TOOL]=array($HIT['hsps'][0]['identity'],$HIT['hsps'][0]['align_len'],$HIT['hsps'][0]['evalue'],$HIT['hsps'][0]['qseq'],$HIT['hsps'][0]['midline'],$HIT['hsps'][0]['hseq']);
			
		}
		
	
	}
	echo  "\t".count($DATA)."\n";
	
}
		
function loadpremRNABowtie(&$CONTENT,$REGION,$TOOL,&$DATA)
{


	 foreach ($CONTENT as $line)
	 {
		 if ($line=='')continue;
		 $tab=explode("\t",$line);
		 
		 
		$id=array();
		$tab[2]=explode(";",$tab[2]);
		foreach ($tab[2] as $K)
		{
			$K2=explode("=",$K);
			$id[$K2[0]]=$K2[1];
		}
       
        $DATA[$id['gid']]['DNA'][$tab[3]+1][$REGION.'_'.$TOOL]=array($tab[4],$tab[5],$tab[6],$tab[7]);
		
		 
		 
	 }
 

}
		
function loadpremRNABlast(&$CONTENT,$REGION,$TOOL,&$DATA)
{
	$STR='';
	foreach ($CONTENT['BlastOutput2']['report']['results'] as $T=>&$QUERY)
	{
		
		
		$HITS=&$QUERY['hits'];
		//echo '<pre>';print_r($HITS);exit;
		foreach ($HITS as &$HIT)
		{
			
			$ID_I=explode(";",$HIT['description'][0]['title']);
			
		 
		 
			$id=array();
			
			foreach ($ID_I as $K)
			{
				$K2=explode("=",$K);
				$id[$K2[0]]=$K2[1];
			}

            
              
			$DATA[$id['gid']]['DNA'][$HIT['hsps'][0]['hit_from']][$REGION.'_'.$TOOL]=array('N/A',$HIT['hsps'][0]['identity'],$HIT['hsps'][0]['align_len'],$HIT['hsps'][0]['evalue'],$HIT['hsps'][0]['qseq'],$HIT['hsps'][0]['midline'],$HIT['hsps'][0]['hseq']);

			
			
		}
		
	
	}
	
	
}

?>
