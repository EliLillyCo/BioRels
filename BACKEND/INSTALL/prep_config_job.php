<?php
ini_set('memory_limit','1000M');
/// Those are crude estimated of database size for the main data sources.
$DB_SIZE=array('TAXONOMY'=>290,
'GENE'=>18200,
'SEQ_ONTO'=>1,
'REFSEQ'=>0,
'ENSEMBL'=>0,
'GENOME'=>145086,
'VARIANT'=>1370400,
'TRANSCRIPT'=>26916,
'TRANSCRIPTOME'=>0,
'PUBLI'=>95000,
'GO'=>30,
'REACTOME'=>15,
'ECO'=>2,
'EFO'=>1,
'BIOASSAY_ONTO'=>1,
'CLINICAL_TRIAL'=>45000,
'LIVERTOX'=>500,
'GENEREVIEWS'=>500,
'UNIPROT'=>5876,
'SEQ_SIM'=>241105,
'TRANSLATE'=>4419,
'GENE_EXPR'=>213712,
'OPENTARGETS'=>1070,
'INTERPRO'=>1631,
'MONDO'=>98,
'SWISSLIPIDS'=>80,
'CELLAUSORUS'=>30,
'COMPOUNDS'=>0,
'CLINVAR'=>1548,
'UBERON'=>50,
'CHEMBL'=>4000,
'ONTOLOGY'=>100,
'WEBJOBS'=>0,
'SURECHEMBL'=>30000,
'DRUGBANK'=>200,
'OMIM'=>200,
'SM'=>58000,
'PMC'=>6000000);
$GLOBAL_OPTIONS=array('WEBJOB_LIMIT'=>20,
    'EMAIL'=>'',
    'EMAIL_FROM'=>'',
    'PRIVATE_ENABLED'=>'F',
    'PUBLI_W_ABSTRACT'=>'Y','PUBLI_W_CITATIONS'=>'Y',
    'PUBMED_API_ID'=>'N/A',
    'TAXON_LIMIT'=>'N/A',
    'XRAY_GENE'=>'N',
    'CHEMBL_GENE'=>'Y',
    'PROMOTER_RANGE'=>5000,
    'WITH_UNIPROT_TREMBL'=>	'Y',
    'WITH_UNIPROT_SP'=>'Y',
    'KEEP_PREVIOUS_DATA'=>'N',
    'OMIM_API_KEY'=>'N/A',
    'DRUGBANK_LOGIN'=>'N/A',
    'JOB_PREFIX'=>'BR_',
    'CHECK_ITER'=>3600,
    'CHECK_RUN'=>5,
    'WEBJOB_PREFIX'=>'TGWJT2');


/// Functions necessary in case of an issue
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function sendMail($ERROR_ID,$INFO)
{
	echo "SEND MAIL\n";
	echo $INFO."\n";
	global $GLB_VAR;
	
	if (isset($GLB_VAR['EMAIL']) && isset($GLB_VAR['EMAIL_FROM']))
	{

		$tab=explode("|",$GLB_VAR['EMAIL']);
		try{
		foreach ($tab as $EM)	mail($EM,'BIORELS - '.$ERROR_ID,$INFO,'From: '.$GLB_VAR['EMAIL_FROM']. "\r\n");
		}catch(Exception $e)
		{}
	}
	
}
function sendKillMail($ERROR_ID,$INFO)
{
	echo "SEND KILL MAIL\n";
	sendMail($ERROR_ID,$INFO);
	exit;
}
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////// PREPARATION /////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/// We get the environment variables
$TG_DIR=getEnv('TG_DIR');
if ($TG_DIR===false) die("No TG_DIR defined. Have you sourced setenv.sh");
if (!is_dir($TG_DIR))die("TG_DIR defined but is not a directory");
if (!is_dir($TG_DIR.'/BACKEND/SCRIPT/LIB/'))die("TG_DIR defined but couldn't find BACKEND/SCRIPT/LIB directory");
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/global.php');
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/fct_utils.php');
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader_process.php');
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader_timestamp.php');

if (!is_dir($TG_DIR.'/PRD_DATA') && !mkdir($TG_DIR.'/PRD_DATA'))die("TG_DIR defined but couldn't create PRD_DATA directory");
if (!is_dir($TG_DIR.'/PROCESS') && !mkdir($TG_DIR.'/PROCESS'))die("TG_DIR defined but couldn't create PROCESS directory");
if (!is_dir($TG_DIR.'/WEBJOBS') && !mkdir($TG_DIR.'/WEBJOBS'))die("TG_DIR defined but couldn't create WEBJOBS directory");


// Associative array mapping a data source to whether or not the user wants it
$DIRS=array();

/// Associate array mapping a data source to its dependent data sources.
$DEPS=array();
/// GLB_TREE contains all the scripts and the information provided in CONFIG_JOB
foreach ($GLB_TREE as &$REC)
{
	/// DIR is the directory name used for each data source
	/// 
    $DIRS[$REC['DIR']]=false;
	/// Now we look at the required scripts and find their data sources
	/// Those data sources will be the dependencies.
    foreach ($REC['REQUIRE'] as $RQ)
    {
        if ($RQ==-1)continue;
        $PARENT=&$GLB_TREE[$RQ];
        if ($PARENT['DIR']==$REC['DIR'])continue;
		/// So here we say that the data source defined in $REC['DIR] will be dependent on $PARENT['DIR]
        $DEPS[$REC['DIR']][$PARENT['DIR']]=true;
    }
}


/// In some cases there are some dependencies that are not exactly required so we remove them
unset($DEPS['PUBLI']['GENE']);
unset($DEPS['MONDO']['OPENTARGETS']);
unset($DEPS['UNIPROT']['CHEMBL']);




$DATE=date("Y-m-d");
if (!is_dir($TG_DIR.'/BACKEND/INSTALL/'.$DATE) && !mkdir($TG_DIR.'/BACKEND/INSTALL/'.$DATE)) die('Unable to create '.$TG_DIR.'/BACKEND/INSTALL/'.$DATE. ' directory');
if (!chdir($TG_DIR.'/BACKEND/INSTALL/'.$DATE)) die('Unable to access '.$TG_DIR.'/BACKEND/INSTALL/'.$DATE.' directory');


$JOB_SPECIFIC_RULE=array();
$LIST_RESOURCES=array();
$GENOMES=array();
$PROTEOMES=array();



if (!is_file('GENOMIC'))  step1_defGenomic();
$GENOMES=step1_loadGenomic();


if (!is_file('PROTEOMES'))step2_defProteome();
$PROTEOMES= step2_loadProteome();

$PREV_RESOURCES=defineOrgsRequirements();

if (!is_file('DATASOURCES')) $LIST_RESOURCES=step3_defResources($PREV_RESOURCES);
else $LIST_RESOURCES=step3_loadResources();
showSummary();

if (!is_file('GLOBAL_OPTIONS'))step4_defOptions();
else loadGlobalOptions();

generateFile();



exit;


function generateFile()
{
    global $TG_DIR;
    global $DATE;
// Then we generate the new  CONFIG_JOB file
$fp=fopen($TG_DIR.'/BACKEND/SCRIPT/CONFIG/CONFIG_USER','r');
$fpO=fopen('NEW_CONFIG_USER','w');
if (!$fp)return;
while(!feof($fp))
{
    // Read the line
    $line=stream_get_line($fp,1000,"\n");
    echo $line."\n";
    if ($line=='#[GLOB]') generateGlobFile($fp,$fpO);
    else if ($line=='#[PROTEOME]') generateProteomeFile($fp,$fpO);
    else if ($line=='#[GENOME]') generateGenomeFile($fp,$fpO);
    else if ($line=='#[JOB]') generateJobFile($fp,$fpO);
    else fputs($fpO,$line."\n");
}
fclose($fpO);
fclose($fp);

echo "NEW_CONFIG_USER generated. Once reviewed, please execute the following command:\n";
echo "cp ./".$DATE."/NEW_CONFIG_USER \$TG_DIR/BACKEND/SCRIPT/CONFIG/CONFIG_USER\n";

}


function generateGenomeFile(&$fp,&$fpO)
{
    fputs($fpO,"\n#[GENOME]\n");
    echo "updating Genome Section\n";
    while(!feof($fp))
    {
        // Read the line
        $line=stream_get_line($fp,1000,"\n");
        if ($line=='#[/GENOME]') break;
    }   
    if (feof($fp)) die('Reached end of file instead of #[/GENOME] - Killed');

    global $GENOMES;
    foreach ($GENOMES as $TAX_ID=>&$LIST)
    foreach ($LIST as &$INFO)
    {
        fputs($fpO,"GENOME\t".implode("\t",$INFO)."\n");
    }
    fputs($fpO,"\n#[/GENOME]\n");
    
}



function generateProteomeFile(&$fp,&$fpO)
{
    fputs($fpO,"\n#[PROTEOME]\n");
    echo "updating Proteome Section\n";
    while(!feof($fp))
    {
        // Read the line
        $line=stream_get_line($fp,1000,"\n");
        if ($line=='#[/PROTEOME]') break;
    }   
    if (feof($fp)) die('Reached end of file instead of #[/PROTEOME] - Killed');

    global $PROTEOMES;
    foreach ($PROTEOMES as $TAX_ID=>&$LIST)
    foreach ($LIST as &$INFO)
    {
        fputs($fpO,"PROTEOME\t".implode("\t",$INFO)."\n");
    }
    fputs($fpO,"\n#[/PROTEOME]\n");
    
}



function generateGlobFile(&$fp,&$fpO)
{
    fputs($fpO,"\n#[GLOB]\n");
    echo "updating Global variables Section\n";
    global $GLOBAL_OPTIONS;
    while(!feof($fp))
    {
        // Read the line
        $line=stream_get_line($fp,1000,"\n");
        if ($line=='#[/GLOB]') {fputs($fpO,$line."\n");return;}
        if (substr($line,0,4)!='GLOB') {fputs($fpO,$line."\n");continue;}

        $tab=explode("\t",$line);
        $N=0;$V=0;$HEAD='';
        for ($I=1;$I<count($tab);++$I)
        {
            
            if ($tab[$I]!='')$N++;
            else continue;
            
            if ($N==1)$HEAD=$tab[$I];
            if ($N==2)$V=$I;
           
        }

        if (!isset($GLOBAL_OPTIONS[$HEAD])) {fputs($fpO,$line."\n");continue;}
        $tab[$V]=$GLOBAL_OPTIONS[$HEAD];
        fputs($fpO,implode("\t",$tab)."\n");
    }
    die('Expected #[/GLOB] line. Didn\'t find it - Killed');
}


function generateJobFile(&$fp,&$fpO)
{
    fputs($fpO,"\n#[JOB]\n");
    echo "updating Job Section\n";
    global $GLOBAL_OPTIONS;
    global $GLB_TREE;
    global $LIST_RESOURCES;
    
    $MAP=array();
    foreach ($GLB_TREE as $ID=>&$INFO)$MAP[$INFO['NAME']]=$ID;
    
    $IGNORE_JOBS=array('web_job','ck_faers_rel','wh_faers','wh_meddra');

    while(!feof($fp))
    {
        // Read the line
        $line=stream_get_line($fp,1000,"\n");
       // echo $line."\t".'|'.substr($line,0,3).'|'."\n";;
        if ($line=='#[/JOB]') {fputs($fpO,$line."\n");return;}
        if (substr($line,0,3)!='JOB') {fputs($fpO,$line."\n");continue;}

        $tab=explode("\t",$line);
        $N=0;$V=0;$HEAD='';
        for ($I=1;$I<count($tab);++$I)
        {
            
            if ($tab[$I]!='')$N++;
            else continue;
            
            if ($N==1)$HEAD=$tab[$I];
            if ($N==2)$V=$I;
          //  echo $I."\t".$tab[$I]."\t".$N."\t".$HEAD."\t".$V."\n";
        }
        if (!isset($MAP[$HEAD]))
        {
            //echo "Didint' find ".$HEAD."\n";
            fputs($fpO,$line."\n");
            continue;}
        
        $JOB_INFO=$GLB_TREE[$MAP[$HEAD]];
        $STATUS=$tab[$V];
        echo $HEAD."\t".$JOB_INFO['DIR']."\t".((isset($LIST_RESOURCES[$JOB_INFO['DIR']]))?'Y':'N')."\n";
        if (isset($LIST_RESOURCES[$JOB_INFO['DIR']]))$STATUS='T';
        if (isset($JOB_SPECIFIC_RULE[$HEAD]))$STATUS=$JOB_SPECIFIC_RULE[$HEAD];
        /// Process_ scripts are run by rmj_ scripts and MUST NOT be run by themselves
        if (substr($HEAD,0,8)=='process_')$STATUS='F';
        if (in_array($HEAD,$IGNORE_JOBS))$STATUS='F';
        if ($STATUS !=$tab[$V]) echo "update ".$HEAD." From ".$tab[$V].' to '.$STATUS."\n";
        $tab[$V]=$STATUS;
        fputs($fpO,implode("\t",$tab)."\n");
    }
    die('Expected #[/JOB] line. Didn\'t find it - Killed');
}

function step4_defOptions()
{
    global $TG_DIR;
    
global $GENOMES;
global $PROTEOMES;
global $GLOBAL_OPTIONS;
global $JOB_SPECIFIC_RULE;
global $PREV_RESOURCES;
global $LIST_RESOURCES;

$SCHEMA_PRIVATE= getenv('SCHEMA_PRIVATE');

if ($SCHEMA_PRIVATE!==false) 
{
    echo "A Private schema name has been set in setenv.sh: ".$SCHEMA_PRIVATE."\n";
    echo "Do you want to use it? (Y/N)\n";
    echo "Your choice: ";
    $VAL= str_replace("\n","",fgets(STDIN));
    if ($VAL=='Y')$GLOBAL_OPTIONS['PRIVATE_ENABLED']='T';
    else $GLOBAL_OPTIONS['PRIVATE_ENABLED']='F';
    if (!is_dir($TG_DIR.'/PRIVATE_PROCESS') && !mkdir($TG_DIR.'/PRIVATE_PROCESS'))die("TG_DIR defined but couldn't create PRIVATE_PROCESS directory");
}
echo "Do you want to keep files from previous releases (Y/N)\n";
echo "Your choice: ";
$VAL= str_replace("\n","",fgets(STDIN));
$GLOBAL_OPTIONS['KEEP_PREVIOUS_DATA']=$VAL;
echo "Please provide an email address to send issues to\n";
echo "Your choice: ";
$VAL= str_replace("\n","",fgets(STDIN));
$GLOBAL_OPTIONS['EMAIL']=$VAL;
echo "Please provide an email address from which the email will be sent from \n";
echo "Your choice: ";
$VAL= str_replace("\n","",fgets(STDIN));
$GLOBAL_OPTIONS['EMAIL_FROM']=$VAL;
echo "Please provide a prefix for the job names (Default BR_) - Max 5 letters \n";
echo "Your choice: ";
$VAL= str_replace("\n","",fgets(STDIN));
if (strlen($VAL)>5) die('Prefix too long');
$GLOBAL_OPTIONS['JOB_PREFIX']=$VAL;



    if (isset($LIST_RESOURCES['CHEMBL']))
    {
        
        if (!isset($LIST_RESOURCES['GENE']))
        {
            echo "ChEMBL: Genes are not required by default for ChEMBL\n";
        }
        echo "Do you want to add Gene annotations for ChEMBL records? (Y/N)\n";
        echo "Your choice: ";
        $VAL= str_replace("\n","",fgets(STDIN));
        if ($VAL=='Y')
        {
            if (!isset($LIST_RESOURCES['GENE']))
            {
                $LIST_RESOURCES['GENE']='REQUESTED';
                if (!isset($LIST_RESOURCES['TAXONOMY']))$LIST_RESOURCES['TAXONOMY']='REQUESTED';
                updateDataSourceFile();
            }
            $GLOBAL_OPTIONS['CHEMBL_GENE']='Y';
        }else  $GLOBAL_OPTIONS['CHEMBL_GENE']='N';
        
       
        
    }else $GLOBAL_OPTIONS['CHEMBL_GENE']='N';
    if (isset($LIST_RESOURCES['XRAY']))
    {
        if (!isset($LIST_RESOURCES['GENE']))
        {
            echo "X-Ray: Genes are not required by default for X-Ray\n";
        }
        echo "Do you want to add Gene annotations for X-Ray records? (Y/N)\n";
        echo "Your choice: ";
        $VAL= str_replace("\n","",fgets(STDIN));
        if ($VAL=='Y')
        {
            if (!isset($LIST_RESOURCES['GENE']))
            {
                $LIST_RESOURCES['GENE']='REQUESTED';
                if (!isset($LIST_RESOURCES['TAXONOMY']))$LIST_RESOURCES['TAXONOMY']='REQUESTED';
                updateDataSourceFile();
            }
            $GLOBAL_OPTIONS['XRAY_GENE']='Y';
        }else  $GLOBAL_OPTIONS['XRAY_GENE']='N';
    }else  $GLOBAL_OPTIONS['XRAY_GENE']='N';
    if (isset($LIST_RESOURCES['DRUGBANK']))
    {
        echo "Please provide your Drugbank API login (Format: USER:PASSWORD)\n";
        echo "N/A if you don't have one - this will disable Drugbank\n";
        echo "Your choice: ";
        $VAL= str_replace("\n","",fgets(STDIN));
        if ($VAL=='N/A') 
        {
            unset($LIST_RESOURCES['DRUGBANK']);
            updateDataSourceFile();
        }
        $GLOBAL_OPTIONS['DRUGBANK_LOGIN']=$VAL;

           
    }
    if (isset($LIST_RESOURCES['OMIM']))
    {
        echo "Please provide your OMIM API  Key\n";
        echo "N/A if you don't have one - this will disable OMIM\n";
        echo "Your choice: ";
        $VAL= str_replace("\n","",fgets(STDIN));
        if ($VAL=='N/A') 
        {
            unset($LIST_RESOURCES['OMIM']);
            updateDataSourceFile();
        }
        $GLOBAL_OPTIONS['OMIM_API_KEY']=$VAL;
           
    }
    if (isset($LIST_RESOURCES['UNIPROT']))
    {
        if (askCheck('Uniprot: Do you want to download/process Swiss-Prot? Y/N'))$GLOBAL_OPTIONS['WITH_UNIPROT_SP']='Y';
        else $GLOBAL_OPTIONS['WITH_UNIPROT_SP']='N';
        if (askCheck('Uniprot: Do you want to download TrEMBL (Note: Please check documentation before answering)? Y/N'))
             $GLOBAL_OPTIONS['WITH_UNIPROT_TREMBL']='Y';
        else $GLOBAL_OPTIONS['WITH_UNIPROT_TREMBL']='N';
    }
    if (isset($LIST_RESOURCES['PUBLI']))
    {
        if (askCheck('Pubmed: Do you want to store abstracts? Y/N'))$GLOBAL_OPTIONS['PUBLI_W_ABSTRACT']='Y';
        else $GLOBAL_OPTIONS['PUBLI_W_ABSTRACT']='N';
        if (askCheck('Pubmed: Do you want to store citations? Y/N'))$GLOBAL_OPTIONS['PUBLI_W_CITATIONS']='Y';
        else $GLOBAL_OPTIONS['PUBLI_W_CITATIONS']='N';
        if (isset($LIST_RESOURCES['GENE']))
        {
            if (askCheck('Pubmed: Do you want to query pubmed for genes? Y/N'))$JOB_SPECIFIC_RULE['db_publi_gene']='T';
            else $JOB_SPECIFIC_RULE['db_publi_gene']='F';
        }else $JOB_SPECIFIC_RULE['db_publi_gene']='F';
        if (isset($LIST_RESOURCES['OPENTARGETS'])||isset($LIST_RESOURCES['DRUGBANK']))
        {
            if (askCheck('Pubmed: Do you want to query pubmed for drugs? Y/N'))$JOB_SPECIFIC_RULE['db_publi_drug']='T';
            else $JOB_SPECIFIC_RULE['db_publi_drug']='F';
        }else $JOB_SPECIFIC_RULE['db_publi_drug']='F';
        if (isset($LIST_RESOURCES['MONDO']))
        {
            if (askCheck('Pubmed: Do you want to query pubmed for disease? Y/N'))$JOB_SPECIFIC_RULE['db_publi_disease']='T';
            else $JOB_SPECIFIC_RULE['db_publi_disease']='F';
        }else $JOB_SPECIFIC_RULE['db_publi_disease']='F';
        if (isset($LIST_RESOURCES['UBERON']))
        {
            if (askCheck('Pubmed: Do you want to query pubmed for tissues? Y/N'))$JOB_SPECIFIC_RULE['db_publi_tissues']='T';
            else $JOB_SPECIFIC_RULE['db_publi_tissues']='F';
        }else $JOB_SPECIFIC_RULE['db_publi_tissues']='F';
       
        echo 'Pubmed: Please provide your Pubmed API Key (N/A if none)?'.
            "\nfor more information: https://support.nlm.nih.gov/knowledgebase/article/KA-05317/en-us\n".
            "Pubmed API Key:";
            $GLOBAL_OPTIONS['PUBMED_API_ID'] = str_replace("\n","",fgets(STDIN));   
    }
    $TAXONS=array();
    foreach ($GENOMES as $TAX_ID=>&$LIST_O)$TAXONS[$TAX_ID]=true;
    foreach ($PROTEOMES as $TAX_ID=>&$LIST_O)$TAXONS[$TAX_ID]=true;
    if (isset($LIST_RESOURCES['CLINICAL_TRIAL']))   $TAXONS['9606']=true;   
    
    echo "Gene annotation\n";
    if ($TAXONS!=array())
    {
        echo "Based on genomes/proteomes, genes from those taxId will be considered: ".implode("/",array_keys($TAXONS))."\n";
        if ($GLOBAL_OPTIONS['XRAY_GENE']=='Y')echo "Genes from X-Ray data will be considered\n";
        if ($GLOBAL_OPTIONS['CHEMBL_GENE']=='Y')echo "Genes from ChEMBL data will be considered\n";
        if ($GLOBAL_OPTIONS['WITH_UNIPROT_SP']=='Y') echo "Genes from Swiss-Prot will be considered\n";
        echo "Do you want to consider other organisms for gene annotations? \n";
        echo "List taxonomic Identifiers, seperated by space.\n";
        echo "N/A if no additional taxons\n";
        $VAL= str_replace("\n","",fgets(STDIN));
        if ($VAL=='N/A') 
        {

        }
        else {
            $tab=explode(" ",$VAL);
            foreach ($tab as $V) if (!is_numeric($V)) die($V.' is not numeric');
            foreach ($tab as $V) $TAXONS[$V]=true;
        }

    }
    if ($TAXONS==array())    $GLOBAL_OPTIONS['TAXON_LIMIT']='N/A';
    else   $GLOBAL_OPTIONS['TAXON_LIMIT']=implode("|",array_keys($TAXONS));
    updateGlobalOptions();   
}

function defineOrgsRequirements()
{
    global $GENOMES;
    global $PROTEOMES;
    global $JOB_SPECIFIC_RULE;
    global $GLOBAL_OPTIONS;
    $RESOURCES=array();
    if ($GENOMES!=array())
    {
    
        $W_PROMOTER=false;
        foreach ($GENOMES as $TAX_ID=>&$LIST_O)
        foreach ($LIST_O as &$RECORD)
        {
            if ($RECORD[1]=='REFSEQ'){$RESOURCES['REFSEQ']=true;$RESOURCES['GENOME']=true;}
            if ($RECORD[1]=='ENSEMBL'){$RESOURCES['ENSEMBL']=true;$RESOURCES['GENOME']=true;}
            if ($RECORD['TRANSCRIPTOME']=='Y')$RESOURCES['TRANSCRIPTOME']=true;
            if ($RECORD['regions']=='Y')$RESOURCES['TRANSCRIPTOME']=true;
            if ($RECORD['pre-mRNA']=='Y'){$JOB_SPECIFIC_RULE['gen_gene_seq']=true;}
            if ($RECORD['promoter']=='Y'){$JOB_SPECIFIC_RULE['gen_gene_seq']=true;$W_PROMOTER=true;}
            //if ($RECORD['TRANSCRIPTOME']=='Y')$RESOURCES['TRANSCRIPTOME']=true;

        }
        if ($W_PROMOTER && !isset($GLOBAL_OPTIONS['PROMOTER_RANGE']))
        {
            echo "You have selected to generate promoter regions. Please select the number of nucleotides prior to 5' end to consider for the promoter region:\n";
            $strChar = str_replace("\n","",fgets(STDIN));
            if (!is_numeric($strChar)) die("The value must be numeric\n");
            $GLOBAL_OPTIONS['PROMOTER_RANGE']=$strChar;
            updateGlobalOptions();
        }
    }
    if ($PROTEOMES!=array())
    {
        $RESOURCES['UNIPROT']=true;
        
    }
    $SEL=array_keys($RESOURCES);
        $ADDED=addDeps($SEL);
    foreach ($ADDED as $D)$RESOURCES[$D]=true;
    return array_keys($RESOURCES);
}

function loadGlobalOptions()
{
    global $GLOBAL_OPTIONS;
    if (!is_file('GLOBAL_OPTIONS'))return;
    $fp=fopen('GLOBAL_OPTIONS','r');
    while(!feof($fp))
    {
        $line=stream_get_line($fp,1000,"\n");
        if ($line=='')continue;
        $tab=explode("\t",$line);
        if (count($tab)!=2)continue;
        $GLOBAL_OPTIONS[$tab[0]]=$tab[1];
    }
    fclose($fp);
}

function updateGlobalOptions()
{
    global $GLOBAL_OPTIONS;
    $fp=fopen('GLOBAL_OPTIONS','w');
    foreach ($GLOBAL_OPTIONS as $K=>&$V)fputs($fp,$K."\t".$V."\n");
    fclose($fp);
}



function step1_loadGenomic()
{
    $fp=fopen('GENOMIC','r');
    $GENOMES=array();
    while(!feof($fp))
    {
        $line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
        if ($line=='N/A') return array();
        $tab=explode("\t",$line);
        $GENOMES[$tab[0]][]=$tab;
    }
    fclose($fp);
   return $GENOMES;
}

function step2_defProteome()
{
    global $GLB_VAR;
    if (!askCheck("Do you want to process proteomes?"))
    {
        $fp=fopen('PROTEOMES','w');fputs($fp,'N/A');fclose($fp);
        return;
    }
    if (!is_file('proteome.txt'))
    {
        echo "\tPlease wait while we download the list of proteomes\n";
        if (!dl_file($GLB_VAR['LINK']['FTP_UNIPROTEOME'].'/README',3,'proteome.txt'))	die('Unable to download proteome list');
    }
    $fp=fopen('proteome.txt','r');if (!$fp)												failProcess($JOB_ID."016",'Unable to open proteome.txt'); 
	$line=stream_get_line($fp,1000,"\n");
    $PROTEOMES=array();
	while (!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		
		if ($line=="")continue;
		$tab=explode("\t",$line);
		
		if (substr($line,0,2)!='UP')continue;
		//çecho $line."\t".isset($PROTEOMES[$tab[1]])."\n";
		//if (!isset($PROTEOMES[$tab[1]]))continue;
		$PROTEOMES[$tab[1]][]=$tab;

	}
	fclose($fp);
 
    $PROTEOMES_SEL=array();
    $N=0;
    do
    {
        echo count($PROTEOMES)." proteomes listed\n";
        $RES=defineProteomes	($PROTEOMES);
        if ($RES===false)break;
        ++$N;
        foreach ($RES as $line)$PROTEOMES_SEL[]=$line;
        $RES=array();
    }while($N<10);
    if ($N==10) echo "It is not recommended to go about 10 species\n";
   
    
    $fp=fopen('PROTEOMES','w');
    //print_R($SPECIES);
    foreach ($PROTEOMES_SEL as $ENTRY)
    {
        fputs($fp,implode("\t",$ENTRY)."\n");
    }
    fclose($fp);



	
}

function step2_loadProteome()
{
    $fp=fopen('PROTEOMES','r');
    $PROTEOMES=array();
    while(!feof($fp))
    {
        $line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
        if ($line=='N/A') return array();
        $tab=explode("\t",$line);
        $PROTEOMES[$tab[1]][]=$tab;
    }
    fclose($fp);
   return $PROTEOMES;   
}


function defineProteomes(&$PROTEOMES)
{
    echo 'Please provide the NCBI Taxonomy Identifier for the organism of interest or N to stop. '."\n";
    echo "Note: If don't know it, please search for it here: https://www.ncbi.nlm.nih.gov/taxonomy\nYour value: ";
    $strChar = str_replace("\n","",fgets(STDIN));
    if ($strChar=='N')return false;
    if (!is_numeric($strChar))
    {
        echo "Must be a numeric value\n";
        return defineProteomes($PROTEOMES);
    }
    $TAX_ID=$strChar;
    
    if (!isset($PROTEOMES[$TAX_ID])) 
    {
        echo "No assemblies defined with this Tax id\n";
        return  defineProteomes($PROTEOMES);
    }
    $TAX_PROTEOMES=&$PROTEOMES[$TAX_ID];
    echo "ID\tPROTEOME_ID\tSUPERREGNUM\tSPECIES_NAME\n";
    foreach ($TAX_PROTEOMES as $K=>$V)echo $K."\t".$V[0]."\t".$V[3]."\t".$V[7]."\n";
    echo "Please choose among the following proteomes below\n";
    echo "If you wish multiple proteomes, list the IDs separated by comma. Example: 1,3\n";
    $PROTEOMES_SEL=array();
    $strChar = str_replace("\n","",fgets(STDIN));
    $tab=explode(",",$strChar);
    $str='You have selected ';
    foreach ($tab as $id)
    {
        if (!isset($TAX_PROTEOMES[$id])) die("No ".$id.' found in the list of proteomes'."\n");
        $str.= ' '.$TAX_PROTEOMES[$id][1].' ('.$TAX_PROTEOMES[$id][0].') ';
        $TAX_PROTEOMES[$id]['TAX']=$TAX_ID;
        $PROTEOMES_SEL[]=$TAX_PROTEOMES[$id];
    }
    echo $str."\n";
    return $PROTEOMES_SEL;

}



function step1_defGenomic()
{
global $GLB_VAR;

echo "STEP 1 - Select organisms\n";
if (!askCheck("Do you want to process genomic assemblies?"))
{
    $fp=fopen('GENOMIC','w');fputs($fp,'N/A');fclose($fp);
    return;
}

    if (!is_file('assembly_summary_refseq.txt'))
    {
        echo "\tPlease wait while we download RefSeq list of assemblies\n";
        if (!dl_file($GLB_VAR['LINK']['FTP_REFSEQ_ASSEMBLY'].'/assembly_summary_refseq.txt'))die('Unable to download RefSeq assembly_summary');
    }
    if (!is_file('species_EnsemblVertebrates.txt'))
    {
        echo "\tPlease wait while we download Ensembl list of assemblies\n";
        
        $RELEASE_FTP=$GLB_VAR['LINK']['FTP_ENSEMBL'].'/release-'.getEnsemblRelease().'/';
	    if (!dl_file($RELEASE_FTP.'/species_EnsemblVertebrates.txt',3))										failProcess($JOB_ID."012",'Unable to download species_EnsemblVertebrates.txt ');

    }
    $fp=fopen('assembly_summary_refseq.txt','r');
    stream_get_line($fp,100000,"\n");
    $HEAD=explode("\t",stream_get_line($fp,100000,"\n"));
    $ASSEMBLIES=array();
    while(!feof($fp))
    {
        $line=stream_get_line($fp,100000,"\n");if ($line=='')continue;
        $tab=explode("\t",$line);
        $ENTRY=array();

        foreach ($HEAD as $K=>$V)$ENTRY[$V]=$tab[$K];
        $ASSEMBLIES[$ENTRY['taxid']][]=array('REFSEQ',
                
                $ENTRY['gbrs_paired_asm'],//GCA_000001405.29
                $ENTRY['asm_name'],//GRCh38.p14
                $ENTRY['#assembly_accession'],//GCF_000001405.40
                $ENTRY['organism_name'],
                $ENTRY['version_status'],//latest
                $ENTRY['release_type'],//Patch
                $ENTRY['refseq_category'],//reference genome
                $ENTRY['annotation_date'],
                $ENTRY['group']);
    }
    fclose($fp);
    $fp=fopen('species_EnsemblVertebrates.txt','r');
    $HEAD=explode("\t",stream_get_line($fp,100000,"\n"));
    
    while(!feof($fp))
    {
        $line=stream_get_line($fp,100000,"\n");if ($line=='')continue;
        $tab=explode("\t",$line);
        $ENTRY=array();
        foreach ($HEAD as $K=>$V)$ENTRY[$V]=$tab[$K];
        $ASSEMBLIES[$ENTRY['taxonomy_id']][]=array(
            'ENSEMBL',
            $ENTRY['assembly_accession'],//GCA_000001405.29
            $ENTRY['assembly'],//GRCh38.p14
            $ENTRY['genebuild'],
            $ENTRY['#name'],
            'N/A',
            'N/A',
            'N/A',
            'N/A',
            'N/A'
           );
    }
    fclose($fp);
 
    
$GENOMES=array();
    $N=0;
    do
    {
        $RES=defineAssembly		($ASSEMBLIES);
        if ($RES===false)break;
        ++$N;
        foreach ($RES as $line)$GENOMES[]=$line;
        $RES=array();
    }while($N<10);
    if ($N==10) echo "It is not recommended to go about 10 species\n";
   
    
    $fp=fopen('GENOMIC','w');
    //print_R($SPECIES);
    foreach ($GENOMES as $ENTRY)
    {
        fputs($fp,$ENTRY['TAX']."\t");
        unset($ENTRY['TAX']);
        fputs($fp,implode("\t",$ENTRY)."\n");
    }
    fclose($fp);


}


function defineAssembly(&$ASSEMBLIES)
{
    echo 'Please provide the NCBI Taxonomy Identifier for the organism of interest or N to stop. '."\n";
    echo "Note: If don't know it, please search for it here: https://www.ncbi.nlm.nih.gov/taxonomy\nYour value: ";
    $strChar = str_replace("\n","",fgets(STDIN));
    if ($strChar=='N')return false;
    if (!is_numeric($strChar))
    {
        echo "Must be a numeric value\n";
        return defineAssembly($ASSEMBLIES);
    }
    $TAX_ID=$strChar;
    
    if (!isset($ASSEMBLIES[$TAX_ID])) 
    {
        echo "No assemblies defined with this Tax id\n";
        return defineAssembly($ASSEMBLIES);
    }
    $TAX_ASSEMBLY=&$ASSEMBLIES[$TAX_ID];
    echo "ID\tSOURCE\tASSEMBLY_ACCESSION\tASSEMBLY_NAME\n";
    foreach ($TAX_ASSEMBLY as $K=>$V)echo $K."\t".implode("\t",$V)."\n";
    echo "Please choose among the following assemblies below\n";
    echo "If you wish multiple assemblies, list the IDs separated by comma. Example: 1,3\n";
    $GENOMES=array();
    $strChar = str_replace("\n","",fgets(STDIN));
    $tab=explode(",",$strChar);
    $str='You have selected ';
    foreach ($tab as $id)
    {
        if (!isset($TAX_ASSEMBLY[$id])) die("No ".$id.' found in the list of assemblies'."\n");
        $str.= ' '.$TAX_ASSEMBLY[$id][1].' ('.$TAX_ASSEMBLY[$id][0].') ';
        $TAX_ASSEMBLY[$id]['TAX']=$TAX_ID;
        $GENOMES[]=$TAX_ASSEMBLY[$id];
    }
    echo $str."\n";
    return $GENOMES;

}

function getEnsemblRelease()
{
    global $GLB_VAR;
    if (!dl_file($GLB_VAR['LINK']['FTP_ENSEMBL'].'/current_README',3))					failProcess($JOB_ID."007",'Unable to download current_README ');

	$NEW_RELEASE=-1;
	$fp=fopen('current_README','r');if (!$fp)											failProcess($JOB_ID."008",'Unable to open current_README ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		preg_match('/The current release is Ensembl ([0-9]{1,4})/',$line,$matches);
		if (count($matches)==0)continue;
		$NEW_RELEASE=$matches[1];
		break;
	}
	fclose($fp);
    
    unlink('current_README');
	if (!is_numeric($NEW_RELEASE)|| $NEW_RELEASE==-1)									failProcess($JOB_ID."009",'Unable to find current release ');
    return $NEW_RELEASE;
}


function step3_loadResources()
{
    global $DB_SIZE;
    if (!is_file('DATASOURCES')) die('Unable to find DATASOURCES');
    $fp=fopen('DATASOURCES','r');if (!$fp) die('Unable to open DATASOURCES');
    $LIST_RESOURCES=array();
    while(!feof($fp))
    {
        $line=stream_get_line($fp,100,"\n");if ($line=='')continue;
        $tab=explode("\t",$line); if (count($tab)!=2) die('Wrong format for line '.$line.'. Expected 2 columns, got '.count($tab).' columns');
        if (!isset($DB_SIZE[$tab[0]])) die('Unrecognized data source: '.$tab[0]);
        $LIST_RESOURCES[$tab[0]]=$tab[1];
    }
    fclose($fp);
    if (count($LIST_RESOURCES)==0) die('No datasources reported');
    return $LIST_RESOURCES;
}

function showSummary()
{
    global $GENOMES;
    global $PROTEOMES;
    global $GLOBAL_OPTIONS;
    global $JOB_SPECIFIC_RULE;
    global $PREV_RESOURCES;
    global $LIST_RESOURCES;
    echo "##################\n##################\n##################\nSummary of current selection:\n";
    if ($GENOMES !=array())
    {
        echo "-> Genomes:\n";
        foreach ($GENOMES as $TAX_ID=>&$LIST)
        foreach ($LIST as &$INFO)
        {
            echo "\t\t".$TAX_ID."\t".implode("\t",$INFO)."\n";
        }
    }
    if ($PROTEOMES !=array())
    {
        echo "-> Proteomes:\n";
        foreach ($PROTEOMES as $TAX_ID=>&$LIST)
        foreach ($LIST as &$INFO)
        {
            echo "\t\t".$TAX_ID."\t".implode("\t",$INFO)."\n";
        }
    }
    if ($PREV_RESOURCES!=array())
    {
        echo "-> Data source needed based on Genome/Proteome selection:\n";
        foreach ($PREV_RESOURCES as $P) echo "\t\t".$P."\n";
    }
    if ($LIST_RESOURCES!=array())
    {
        echo "-> Data source you requested or depends upon:\n";
        foreach ($LIST_RESOURCES as $P=>$V) echo "\t\t".$P."\t".$V."\n";
    }
}
function step3_getUserDefResources()
{
    global $DIRS;
    echo "Please provide the list of databases you wish to consider among the list below, each separated by space:\n";
    echo "Or N/A if already covered by previous steps\n";
    echo "Or ALL if you want all resources\n";
    $N=0;
    ///UNRELATED lists some subspecific data sources
    $UNRELATED=array('REFSEQ','ENSEMBL','OMIM','COMPOUNDS','ACTIVITY','ONTOLOGY','WEBJOBS');
    foreach ($DIRS as $D=>&$DUMMY)
    {
        ++$N;
        if (in_array($D,$UNRELATED))continue;
        echo $D."\n";
        
    }
    echo "\nYour choice(s): ";
    $strChar = str_replace("\n","",fgets(STDIN));
    if ($strChar=='N/A')return array();
    if ($strChar=='ALL')
    {
        $SEL=array();
        foreach ($DIRS as $k=>$v)$SEL[]=$k;
        return $SEL;
    }
        
    $SEL=explode(" ",$strChar);
    foreach ($SEL as $n=>$v)
    {
        if (!isset($DIRS[$v]))
        {
            echo ('Unrecognized '.$v."\n"); 
            return step3_getUserDefResources();
        }
    }
    return $SEL;
    
    
    
}

function step3_defResources(&$PREV_RESOURCES)
{
    global $argv;
    global $DIRS;
    global $DEPS;
    global $DB_SIZE;

    if ($PREV_RESOURCES!=array())
    {
        echo "Based on genome information and options, you will need the following data sources: \n";
        echo implode(" ",$PREV_RESOURCES)."\n";
    }
    $SEL=step3_getUserDefResources();
    foreach ($PREV_RESOURCES as $P) if (!in_array($P,$SEL))  $SEL[]=$P;
    foreach ($SEL as $D)$DIRS[$D]=true;
    /// Based on that selected list, we add the dependencies:
    $ADDED=addDeps($SEL);
    foreach ($ADDED as $D)$DIRS[$D]=true;

    $STR='You have selected '.implode(', ',$SEL)."\n";
    $STR.="You will need those datasources too: ".implode(", ",$ADDED)."\n";

    /// We can crudely compute the database size:
    $DB_SIZE_ALL=0;
    foreach($DIRS as $D=>$ST)if ($ST && isset($DB_SIZE[$D]))$DB_SIZE_ALL+=$DB_SIZE[$D]; 
    $STR.='Crudely estimated database size: '.$DB_SIZE_ALL."Mb\n";

    echo $STR."\n";
    if(!askCheck('Do you want to proceed'))exit(0);
    $fp=fopen('DATASOURCES','w'); if(!$fp) die('Unable to open DATASOURCES');
    $LIST_RESOURCES=array();
    foreach ($SEL as $S)    {fputs($fp,$S."\tREQUESTED\n"); $LIST_RESOURCES[$S]='REQUESTED';}
    foreach ($ADDED as $S)  {fputs($fp,$S."\tNEEDED\n");    $LIST_RESOURCES[$S]='ADDED';}
    fclose($fp);
    return $LIST_RESOURCES;
}

function updateDataSourceFile()
{
    global $LIST_RESOURCES;
    $fp=fopen('DATASOURCES','w'); if(!$fp) die('Unable to open DATASOURCES');
    foreach ($LIST_RESOURCES as $K=>$V)fputs($fp,$K."\t".$V."\n");
    fclose($fp);
}


function askCheck($QUESTION,$ANSWERS=array('Y'=>array('',true),'N'=>array('You did not agreed',false)))
{
    $resSTDIN=fopen("php://stdin","r");
    echo $QUESTION.' '.implode('/',array_keys($ANSWERS)).'. Then press return: ';
    $strChar = stream_get_contents($resSTDIN, 1);
    if (!isset($ANSWERS[$strChar]))
    {
        echo "We didn't understood the answer\n";
        return askCheck($QUESTION,$ANSWERS);
    }
    $VALUE=$ANSWERS[$strChar];
    if ($VALUE[0]!='')echo $VALUE[0]."\n";
    return $VALUE[1];
}








	


    function addDeps($SEL)
    {
        global $DEPS;
        global $DIRS;
        $DEP_ADDED=array();
        foreach ($SEL as $DB)
        {
            if (isset($DEPS[$DB]))
            foreach ($DEPS[$DB] as $DB_DEP=>&$DUMMY)
            {
                if ($DIRS[$DB_DEP])continue;
                if (in_array($DB_DEP,$DEP_ADDED))continue;
                if (in_array($DB_DEP,$SEL))continue;
                $DEP_ADDED[]=$DB_DEP;
            }
    
        }
        if ($DEP_ADDED!=array())
        {
            $DEPTH_ADDED=addDeps($DEP_ADDED);
            foreach ($DEPTH_ADDED as $D)
            {
                if (in_array($D,$DEP_ADDED))continue;
                if (in_array($D,$SEL))continue;
                $DEP_ADDED[]=$D;
            }
        }
        return $DEP_ADDED;
    }



?>