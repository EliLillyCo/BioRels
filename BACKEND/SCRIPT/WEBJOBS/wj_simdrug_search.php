<?php

ini_set('memory_limit','3000M');
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
updateWebJobStatus($MD5_HASH,'Initiate search drug');


updateWebJobStatus($MD5_HASH,'Verifying input parameters');

    $INPUT_DATA=json_decode($RAW_INFO[0]['params'],true);
    if ($INPUT_DATA==null)    failedWebJob($MD5_HASH,'Unable to interpret parameters');
    if (!isset($INPUT_DATA['STRUCTURE']))	failedWebJob($MD5_HASH,'No query information found');
    if (!isset($INPUT_DATA['SEARCH_TYPE']))failedWebJob($MD5_HASH,'No search type defined');


    $SEARCH_TYPE=$INPUT_DATA['SEARCH_TYPE'];
	
	
    if ($SEARCH_TYPE !='SUBSTRUCTURE' && $SEARCH_TYPE!='SIMILARITY') failedWebJob($MD5_HASH,'Unrecognized search type');

updateWebJobStatus($MD5_HASH,'Setting up directory');

    $W_DIR=$TG_DIR.'/'.$JOB_INFO['DIR'].'/'; if (!is_dir($W_DIR) && !mkdir($W_DIR))failedWebJob($MD5_HASH,'Unable to create web job directory');
    $W_DIR.=$MD5_HASH;if (!is_dir($W_DIR) && !mkdir($W_DIR))failedWebJob($MD5_HASH,'Unable to create job directory');
    if (!chdir($W_DIR))failedWebJob($MD5_HASH,'Unable to access job directory');


updateWebJobStatus($MD5_HASH,'Listing drugs');
    $res=runQuery("select de.drug_entry_id, smiles from drug_entry de,drug_mol_entity_map dem, molecular_Entity me, sm_entry se, sm_molecule sm where  de.drug_entry_id = dem.drug_entry_id AND dem.molecular_entity_id = me.molecular_Entity_id AND me.molecular_structure_hash = se.md5_hash AND se.sm_molecule_id = sm.sm_molecule_id AND se.is_valid='1'");

updateWebJobStatus($MD5_HASH,count($res).' drugs found');
    $fp=fopen('drugs.smi','w'); if (!$fp)failedWebJob($MD5_HASH,'Unable to open file');
    foreach ($res as $line)fputs($fp,$line['smiles'].' '.$line['drug_entry_id']."\n");
    fclose($fp);
 
	$TEXT=&$INPUT_DATA['STRUCTURE'];
	
	$fp=fopen('input.smi','w'); 
    if (!$fp)failedWebJob($MD5_HASH,'Unable to create input file');
    fputs($fp,$TEXT.' query'."\n");fclose($fp);
	

if ($SEARCH_TYPE=='SIMILARITY')
{
updateWebJobStatus($MD5_HASH,'similarity search requested');
    if (!isset($INPUT_DATA['THRESHOLD']))	failedWebJob($MD5_HASH,'No threshold information found');
    $THRESHOLD=1-$INPUT_DATA['THRESHOLD']/100;
    

    $MATCH=array();

    $TOOL_MAP=array('Temperature'=>$GLB_VAR['TOOL']['TEMPERATURE'],
    'iwfp'=>$GLB_VAR['TOOL']['IWFP'],
    'maccskeys'=>$GLB_VAR['TOOL']['MACCSKEYS']);

    $THRESHOLD=0.4;

    foreach ($TOOL_MAP as $tool)
    {
        if (!is_executable($tool))	failedWebJob($MD5_HASH,'Unable to find requested tool');
    }

updateWebJobStatus($MD5_HASH,'Creating databases');
    exec($TOOL_MAP['Temperature'].' '.$GLB_VAR['TOOL']['TEMPERATURE_PARAM'].' drugs.smi | '.$TOOL_MAP['maccskeys'].' '.$GLB_VAR['TOOL']['MACCSKEYS_PARAM'].' | '.$TOOL_MAP['iwfp'].' '.$GLB_VAR['TOOL']['IWFP_PARAM'].' >drugs.gfp');

    
    exec($TOOL_MAP['Temperature'].' '.$GLB_VAR['TOOL']['TEMPERATURE_PARAM'].' input.smi | '.$TOOL_MAP['maccskeys'].' '.$GLB_VAR['TOOL']['MACCSKEYS_PARAM'].' | '.$TOOL_MAP['iwfp'].' '.$GLB_VAR['TOOL']['IWFP_PARAM'].' >input.gfp');

updateWebJobStatus($MD5_HASH,'Computing similarity');
    exec($GLB_VAR['TOOL']['GFPLN'].' -n 100 -p drugs.gfp input.gfp | '.$GLB_VAR['TOOL']['NNPLOT'].' -j \'tab\' - > results');

updateWebJobStatus($MD5_HASH,'Processing results');
$N_FOUND=0;
    if (!is_file('results'))failedWebJob($MD5_HASH,'Unable to find results file');
    else 
    {
        $RESULTS=array();
        $fp=fopen('results','r');
        if (!$fp)failedWebJob($MD5_HASH,'Unable to open results file');
        while(!feof($fp))
        {
            $line=stream_Get_line($fp,10000,"\n");if ($line=='')continue;
            $tab=explode("\t",$line);
            $r=explode(" ",$tab[0]);
            $c=explode(" ",$tab[1]);
            if ($c[3]>$THRESHOLD)continue;
            $N_FOUND++;
            $RESULTS[(string)(100-$c[3]*100)]=getDrugInfo($r[1]);
        }
    }
    krsort($RESULTS);
updateWebJobStatus($MD5_HASH,$N_FOUND.' molecules found');

}
else
{
    updateWebJobStatus($MD5_HASH,'Substructure search requested');
	echo $GLB_VAR['TOOL']['TSUBSTRUCTURE'].'  -q M:input.smi -m results.smi drugs.smi';
    exec($GLB_VAR['TOOL']['TSUBSTRUCTURE'].'  -q M:input.smi -m results.smi drugs.smi',$res,$return_code);
    if ($return_code!=0)failedWebJob($MD5_HASH,'Failed to compute substructure search');
    updateWebJobStatus($MD5_HASH,'Processing results');
    $N_FOUND=0;
        if (!is_file('results.smi'))failedWebJob($MD5_HASH,'Unable to find results file');
        else 
        {
            $RESULTS=array();
            $fp=fopen('results.smi','r');
            if (!$fp)failedWebJob($MD5_HASH,'Unable to open results file');
            while(!feof($fp))
            {
                $line=stream_Get_line($fp,10000,"\n");if ($line=='')continue;
                $tab=explode(" ",$line);
				
                $N_FOUND++;
                $data=getDrugInfo($tab[1]);
				
				foreach ($data as $k=>&$v)$RESULTS[$k]=$v;
            }
        }
    
}
uploadWebJobDoc($MD5_HASH,'results.json','application/json',json_encode($RESULTS),'search drug');



$STATUS_INFO['STATUS']='Success';

$STATUS_INFO['LOG'][]=array('Job successfully finished',date("F j, Y, g:i a"));


runQueryNoRes("Update web_job set job_status = '".str_replace("'","''",json_encode($STATUS_INFO))."', time_end=CURRENT_TIMESTAMP WHERE md5id = '".$MD5_HASH."'");

function getDrugInfo($DRUG_ENTRY_ID)
{

    $res = runQuery(
    "SELECT 
        DE.DRUG_ENTRY_ID, 
        
        IS_APPROVED,
        IS_WITHDRAWN,
        MAX_CLIN_PHASE,
       
        COUNTERION_SMILES,
        SMILES,
        SE.IS_VALID
	 FROM 
        drug_entry de,drug_mol_entity_map dem, molecular_Entity me, sm_entry se
	    
	    LEFT JOIN SM_COUNTERION SC ON SC.SM_COUNTERION_ID = SE.SM_COUNTERION_ID
	    LEFT JOIN SM_MOLECULE SM ON SE.SM_MOLECULE_ID = SM.SM_MOLECULE_ID
	    WHERE DE.DRUG_ENTRY_ID=" . $DRUG_ENTRY_ID.'
         AND  de.drug_entry_id = dem.drug_entry_id AND dem.molecular_entity_id = me.molecular_Entity_id AND me.molecular_structure_hash = se.md5_hash AND se.sm_molecule_id = sm.sm_molecule_id');

    $DATA = array();
    foreach ($res as $line){
        $DATA[$line['drug_entry_id']] = $line;   
    }     
    if (count($DATA) == 0) return $DATA;
    $res = runQuery("SELECT DRUG_ENTRY_ID,DRUG_NAME,IS_PRIMARY,IS_TRADENAME FROM DRUG_NAME WHERE DRUG_ENTRY_ID IN (" . implode(',', array_keys($DATA)) . ')');
    foreach ($res as $line) $DATA[$line['drug_entry_id']]['NAME'][($line['is_primary'] == 'T') ? 'PRIMARY' : ($line['is_tradename'] == 'T' ? 'TRADENAME' : 'SYNONYM')][] = $line['drug_name'];
    return $DATA;
}
?>
