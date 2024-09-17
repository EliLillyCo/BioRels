<?php 
ini_set('memory_limit','5000M');

$TG_DIR= getenv('TG_DIR');

if ($TG_DIR===false)  die('NO TG_DIR found');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
if (!is_dir($TG_DIR.'/PROCESS') && !mkdir($TG_DIR.'/PROCESS'))die('TG_DIR/PROCESS can\'t be created');

$FILE_TO_LOAD=array(
	'/LIB/global.php'=>0,
	'/LIB/fct_utils.php'=>0,
	'/LIB/loader_process.php'=>0
);

$DEBUG=false;

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}



foreach ($FILE_TO_LOAD as $FILE=>$RULE)
{
	if ($RULE==1 && !defined("MONITOR_JOB"))continue;
	$PATH=$TG_DIR.'/BACKEND/SCRIPT/'.$FILE;
	if ((include $PATH)==TRUE)continue;
	sendKillMail('000003','Unable to load file: '.$PATH);
}


date_default_timezone_set($GLB_VAR['TIMEZONE']);

$DB_CONN=null;
$DB_INFO=array();
$GLB_VAR['DB_SCHEMA']=getenv('DB_SCHEMA');
$GLB_VAR['SCHEMA_PRIVATE']=getenv('SCHEMA_PRIVATE');

connectDB();

if (!is_file($TG_DIR.'/BACKEND/SCRIPT/BIORJ/api_lib.php'))die('api_lib.php not found');
require_once($TG_DIR.'/BACKEND/SCRIPT/BIORJ/api_lib.php');


if ($argc<2)
{
echo "Usage: php api_export.php [OPTIONS] ENTITY_TYPE ENTITY_VALUE

ENTITY_VALUE is a list of values in csv format:
	 Separator = , Enclosure = '  Escape = \\

List of ENTITY_TYPEs and their required values:
	-> GENE	    : gene_id
	-> PMID	    : pmid
	-> PROTEIN  : prot_identifier
	-> GO 	    : ac
	-> CELL	    : cell_acc
	-> DRUG	    : drug_primary_name
	-> DISEASE	: disease_tag
	-> CLINICAL	: trial_id
	-> GENE_SEQ	: gene_seq_name
	-> VARIANT	: rsid
	-> SM	    : md5_hash
	-> ASSAY	: assay_name
	-> MOLECULAR_ENTITY	: molecular_entity_hash
	-> ACTIVITY	: mol_pos
	-> PMC	    : pmc_id
	-> DOMAIN	: ipr_id

OPTIONS:
	-SCHEMA=SCHEMA_NAME : Specify the schema to use
	-JSON_OUTPUT=FILE_NAME : Specify the file to output the JSON
	--JSON_PRETTY_PRINT : Output the JSON in pretty print format
	-LINEARIZE=FILE_NAME : Specify the file to output the linearized file
	--STD_OUT : Output the JSON to standard output
";
exit(0);
}
$ENTITY_TYPE=null;
$ENTITY_VALUE=null;
$SCHEMA=null;
$JSON_PRETTY_PRINT=false;
$JSON_OUTPUT=null;
$JSON_STDOUT=false;
$LINEARIZE=null;
$ALLOWED_ENTITY_TYPE=array('GENE','PMID','PROTEIN','GO','CELL','DOMAIN','DRUG','DISEASE','CLINICAL','GENE_SEQ','VARIANT','SM','ASSAY','MOLECULAR_ENTITY','ACTIVITY','PMC');
foreach ($argv as $argc_id=>$value)
{
	if ($argc_id==0)continue;
	if (strpos($value,'--')===0)
	{
		if ($value=='--JSON_PRETTY_PRINT')$JSON_PRETTY_PRINT=true;
		else if ($value=='--STD_OUT')$JSON_STDOUT=true;
		else die('Invalid option: '.$value);
	}
	else
	if (strpos($value,'-')===0)
	{
		$tab=explode('=',$value);
		if (count($tab)!=2)die('Invalid option: '.$value."\nFormat is OPTION=VALUE");
		if ($tab[0]=='-SCHEMA')$SCHEMA=$tab[1];
		else if ($tab[0]=='-JSON_OUTPUT')$JSON_OUTPUT=$tab[1];
		else if ($tab[0]=='-LINEARIZE')$LINEARIZE=$tab[1];
		
		else die('Invalid option: '.$tab[0]);
	}
	else
	{
		if (!in_array(strtoupper($value),$ALLOWED_ENTITY_TYPE))die('Invalid ENTITY_TYPE: '.$value);
		$ENTITY_TYPE=strtoupper($value);
		if (!isset($argv[$argc_id+1]))die('Missing ENTITY_VALUE');
		
		$ENTITY_VALUE=str_getcsv($argv[$argc_id+1]);
		
		break;
	}
}
if ($ENTITY_TYPE==null)die('Missing ENTITY_TYPE');
if ($ENTITY_VALUE==null)die('Missing ENTITY_VALUE');
if ($SCHEMA==null)$SCHEMA=getenv('DB_SCHEMA');
if ($JSON_STDOUT==false && $LINEARIZE==null && $JSON_OUTPUT==null)die('Missing output file. Either LINEARIZE or JSON_OUTPUT');

$JSON_PARENT_FOREIGN=array(); 
/// Set up schema
if ($SCHEMA!=null)
{
	echo 'SET SCHEMA TO '.$SCHEMA."\n";
	runQueryNoRes("SET SESSION SEARCH_PATH to ".$SCHEMA);
	/// Get foreign key relationships:
	getAllForeignRel(array("'".$SCHEMA."'"));
}
/// Get foreign key relationships:
else 
{
	$SCHEMA=$GLB_VAR['DB_SCHEMA'];
	getAllForeignRel();
}


echo "ALL GOOD\n";

$HIERARCHY=array();
$KEYS=array();
$BLOCKS=loadAPIRules($TG_DIR,$HIERARCHY,$KEYS);
$NOT_NULL=getNotNullCols($SCHEMA);
// print_r($HIERARCHY);
// exit;


switch ($ENTITY_TYPE)
{
	case 'GENE':
		
		export_record($SCHEMA,'gn_entry','gene_id',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'PMID':
		export_record($SCHEMA,'pmid_entry','pmid',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'PROTEIN':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'prot_entry','prot_identifier',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'GO':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'go_entry','ac',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'CELL':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'cell_entry','cell_acc',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'DRUG':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'drug_entry','drug_primary_name',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'DISEASE':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'disease_entry','disease_tag',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'CLINICAL':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'clinical_trial','trial_id',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'GENE_SEQ':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'gene_seq','gene_seq_name',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'VARIANT':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'variant_entry','rsid',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'SM':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'sm_entry','md5_hash',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'ASSAY':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'assay_entry','assay_name',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'MOLECULAR_ENTITY':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'molecular_entity','molecular_entity_hash',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'ACTIVITY':
		export_record($SCHEMA,'activity_entry','mol_pos',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'PMC':
		export_record($SCHEMA,'pmc_entry','pmc_id',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	case 'DOMAIN':
		quoteValues($ENTITY_VALUE);
		export_record($SCHEMA,'ip_entry','ipr_id',$ENTITY_VALUE,$RESULTS,true,true);
		break;
	default:
		die('Invalid ENTITY_TYPE: '.$ENTITY_TYPE);
}

	if ($LINEARIZE!=null)json_to_csv($RESULTS,$HIERARCHY,$LINEARIZE);
	$RESULTS['BIORJ_HIERARCHY']=$HIERARCHY;
	if ($JSON_OUTPUT!=null)
	{
		$fp=fopen($JSON_OUTPUT,'w');
		fputs($fp,json_encode($RESULTS,$JSON_PRETTY_PRINT?JSON_PRETTY_PRINT:0));
		fclose($fp);
	}
	if ($JSON_STDOUT)
	{
		echo json_encode($RESULTS,$JSON_PRETTY_PRINT?JSON_PRETTY_PRINT:0);
	}
	


exit;

//   export_record('biorels','ip_entry','ipr_id',array("'IPR000014'"),$RESULTS,true,true) ;

//export_record('biorels','gn_entry','gene_id',array('1017','1018'),$RESULTS,true,true);
// export_record('biorels','pmid_entry','pmid',array('21105149'),$RESULTS,true,true);
// export_record('biorels','prot_entry','prot_identifier',array("'CDK2_HUMAN'"),$RESULTS,true,true);
 //export_record('biorels','go_entry','ac',array("'GO:0000010'"),$RESULTS,true,true);


 //export_record('biorels','cell_entry','cell_acc',array("'CVCL_VG99'"),$RESULTS,true,true);
  //export_record('biorels','drug_entry','drug_primary_name',array("'Omeprazole'"),$RESULTS,true,true);
//export_record('biorels','disease_entry','disease_tag',array("'MONDO_0005138'"),$RESULTS,true,true);
 //export_record('biorels','clinical_trial','trial_id',array("'NCT01996436'"),$RESULTS,true,true);
 //export_record('biorels','gene_seq','gene_seq_name',array("'RIPK1'"),$RESULTS,true,true);

 
// export_record('biorels','variant_transcript_map','variant_transcript_id',array("63"),$RESULTS,true,true);
 //export_record('biorels','variant_entry','rsid',array("665"),$RESULTS,true,true);
 //export_record('biorels','sm_entry','md5_hash',array("'eac4794e7414e70272deb71360246dc7'"),$RESULTS,true,true);
 //export_record('biorels','assay_entry','assay_name',array("'CHEMBL4888485'"),$RESULTS,true,true);
 //export_record('biorels','molecular_entity','molecular_entity_hash',array("'962fb0e3e47bc03f831ebe9b759d027e'"),$RESULTS,true,true);
 //export_record('biorels','activity_entry','mol_pos',array("'MMV1646320'"),$RESULTS,true,true);
 


?>